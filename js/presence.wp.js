// Dashboard Presence Ping
(function () {
  "use strict";

  if (window.__wuPresenceInit) {
    return;
  }
  window.__wuPresenceInit = true;

  const currentScript = document.currentScript;

  function resolveAppUrl() {
    if (typeof window.APP_URL === "string" && window.APP_URL.trim() !== "") {
      return window.APP_URL.replace(/\/$/, "");
    }

    if (currentScript && currentScript.src) {
      return currentScript.src.replace(/\/js\/presence\.wp\.js(?:\?.*)?$/, "");
    }

    const match = window.location.pathname.match(
      /^(.*?)(?:\/dashboard\/|\/authentic\/|\/wu-panel-2026\/|\/js\/|$)/
    );

    return window.location.origin + (match && match[1] ? match[1] : "");
  }

  const APP_URL = resolveAppUrl();
  const PING_URL = APP_URL + "/dashboard/ajax/presence_ping";
  const LOGIN_URL = APP_URL + "/login?notice=session";
  const INTERVAL = 60 * 1000;
  const TAB_HEARTBEAT = 15 * 1000;
  const TAB_TTL = 90 * 1000;
  const TAB_STORAGE_KEY = "wu_presence_tabs";
  const STATUS_STORAGE_KEY = "wu_presence_manual_status";
  const TAB_ID_KEY = "wu_presence_tab_id";
  const allowedStatuses = ["online", "away", "busy", "invisible", "offline"];
  const manualStatuses = ["online", "away", "busy", "invisible"];

  const tabId =
    sessionStorage.getItem(TAB_ID_KEY) ||
    "tab-" + Math.random().toString(36).slice(2, 12) + Date.now().toString(36);
  sessionStorage.setItem(TAB_ID_KEY, tabId);

  let unloadHandled = false;

function getCurrentActivity() {
    const path = window.location.pathname.toLowerCase();

    // Lançamentos (releases)
    if (path.includes("/releases") || path.includes("/creat-release") || path.includes("/draft-release") || path.includes("/edit-release") || path.includes("/release_process") || path.includes("/creat_release_process")) {
        return "releases";
    }

    // Finanças
    if (path.includes("/finances") || path.includes("/overview") || path.includes("/transactions") || path.includes("/withdraw") || path.includes("/payment/pay") || path.includes("/_modal_withdrawal") || path.includes("/account_process") || path.includes("/split_process")) {
        return "finances";
    }

    // Artistas
    if (path.includes("/artists") || path.includes("/add-artist") || path.includes("/add_artist_process") || path.includes("/youtube/ucy")) {
        return "artists";
    }

    // Analytics / Estatísticas
    if (path.includes("/analytics") || path.includes("/statistics") || path.includes("/artist-details") || path.includes("/country-details") || path.includes("/playlist-details") || path.includes("/compare") || path.includes("/report") || path.includes("/export")) {
        return "analytics";
    }

    // Perfil do usuário
    if (path.includes("/user/profile")) {
        return "profile";
    }

    // Configurações da conta (manage-account)
    if (path.includes("/account/manage-account") || path.includes("/account/add-user")) {
        return "account_settings";
    }

    // Configurações gerais (settings)
    if (path.includes("/page/settings")) {
        return "settings";
    }

    // Suporte
    if (path.includes("/support") || path.includes("/page/support") || path.includes("/ajax/support_dashboard")) {
        return "support";
    }

    // Notificações
    if (path.includes("/notifications") || path.includes("/page/notifications") || path.includes("/ajax/notifications_api")) {
        return "notifications";
    }

    // Planos
    if (path.includes("/all-plans")) {
        return "plans";
    }

    // FAQ / Ajuda
    if (path.includes("/faq") || path.includes("/help")) {
        return "help";
    }

    // Serviços disponíveis
    if (path.includes("/services/available-services")) {
        return "services";
    }

    // Páginas de erro
    if (path.includes("/error/403") || path.includes("/error/404") || path.includes("/error/500") || path.includes("/error/503") || path.includes("/maintenance")) {
        return "error_page";
    }

    // Páginas de políticas
    if (path.includes("/politicies/terms") || path.includes("/politicies/privacy")) {
        return "policies";
    }

    // Offline
    if (path.includes("/offline")) {
        return "offline";
    }

    // Painel principal (dashboard/painel)
    if (path === "/dashboard/" || path === "/dashboard/painel" || path.endsWith("/dashboard") || path.endsWith("/painel") || path === "/dashboard" || path.includes("/dashboard")) {
        return "dashboard";
    }

    // Fallback
    return "dashboard";
}

  function readJsonStorage(key, fallback) {
    try {
      const raw = localStorage.getItem(key);
      if (!raw) {
        return fallback;
      }

      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === "object" ? parsed : fallback;
    } catch (error) {
      return fallback;
    }
  }

  function readTabs() {
    return readJsonStorage(TAB_STORAGE_KEY, {});
  }

  function writeTabs(tabs) {
    try {
      localStorage.setItem(TAB_STORAGE_KEY, JSON.stringify(tabs));
    } catch (error) {
      // Ignorar quota/storage.
    }
  }

  function pruneTabs(tabs) {
    const now = Date.now();
    Object.keys(tabs).forEach((key) => {
      if (!tabs[key] || now - Number(tabs[key].ts || 0) > TAB_TTL) {
        delete tabs[key];
      }
    });
    return tabs;
  }

  function updateTabState(isVisible) {
    const tabs = pruneTabs(readTabs());
    tabs[tabId] = {
      visible: Boolean(isVisible),
      ts: Date.now(),
      path: window.location.pathname,
    };
    writeTabs(tabs);
    return tabs;
  }

  function removeTabState() {
    const tabs = pruneTabs(readTabs());
    delete tabs[tabId];
    writeTabs(tabs);
    return tabs;
  }

  function getManualStatus() {
    const value = localStorage.getItem(STATUS_STORAGE_KEY) || "";
    return manualStatuses.includes(value) ? value : "";
  }

  function setManualStatus(status) {
    if (!manualStatuses.includes(status)) {
      localStorage.removeItem(STATUS_STORAGE_KEY);
      return;
    }

    localStorage.setItem(STATUS_STORAGE_KEY, status);
  }

  function getEffectiveStatus(tabs) {
    const safeTabs = pruneTabs(tabs || readTabs());
    const openTabs = Object.keys(safeTabs).length;

    if (!openTabs) {
      return "offline";
    }

    const manual = getManualStatus();
    if (manual) {
      return manual;
    }

    const hasVisibleTab = Object.values(safeTabs).some((tab) => tab && tab.visible);
    return hasVisibleTab ? "online" : "away";
  }

  function buildPayload(status) {
    const fd = new FormData();
    fd.append("page", window.location.pathname);
    fd.append("activity_type", getCurrentActivity());
    fd.append("status", status);
    return fd;
  }

  async function ping(status) {
    if (!allowedStatuses.includes(status)) {
      status = "online";
    }

    try {
      const response = await fetch(PING_URL, {
        method: "POST",
        body: buildPayload(status),
        credentials: "same-origin",
        keepalive: status !== "online",
      });

      const data = await response.json().catch(() => ({}));
      if (data.expired) {
        window.location.href = LOGIN_URL;
      }
    } catch (error) {
      // Falha de rede não deve quebrar a UI.
    }
  }

  function syncPresence(statusOverride) {
    const tabs = updateTabState(!document.hidden);
    const status = statusOverride || getEffectiveStatus(tabs);
    ping(status);
  }

  function handleUnload() {
    if (unloadHandled) {
      return;
    }

    unloadHandled = true;
    const tabs = removeTabState();
    const status = getEffectiveStatus(tabs);
    const payload = buildPayload(status);

    if (navigator.sendBeacon) {
      navigator.sendBeacon(PING_URL, payload);
      return;
    }

    fetch(PING_URL, {
      method: "POST",
      body: payload,
      credentials: "same-origin",
      keepalive: true,
    }).catch(() => {});
  }

  updateTabState(!document.hidden);
  ping(getEffectiveStatus());

  window.setInterval(() => {
    updateTabState(!document.hidden);
    ping(getEffectiveStatus());
  }, INTERVAL);

  window.setInterval(() => {
    updateTabState(!document.hidden);
  }, TAB_HEARTBEAT);

  document.addEventListener("visibilitychange", function () {
    syncPresence();
  });

  window.addEventListener("focus", function () {
    syncPresence();
  });

  window.addEventListener("storage", function (event) {
    if (event.key === TAB_STORAGE_KEY || event.key === STATUS_STORAGE_KEY) {
      syncPresence();
    }
  });

  window.addEventListener("beforeunload", handleUnload);
  window.addEventListener("pagehide", handleUnload);

  window.WUPresence = {
    setStatus(status) {
      if (!manualStatuses.includes(status)) {
        throw new Error("Status de presença inválido.");
      }
      setManualStatus(status);
      syncPresence(status);
    },
    clearStatus() {
      localStorage.removeItem(STATUS_STORAGE_KEY);
      syncPresence();
    },
    getStatus() {
      return getManualStatus() || getEffectiveStatus();
    },
    sync() {
      syncPresence();
    },
  };

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-presence-status]").forEach((element) => {
      element.addEventListener("click", function () {
        const status = String(element.getAttribute("data-presence-status") || "").toLowerCase();
        if (status === "auto" || status === "default") {
          window.WUPresence.clearStatus();
          return;
        }

        if (manualStatuses.includes(status)) {
          window.WUPresence.setStatus(status);
        }
      });
    });
  });
})();
