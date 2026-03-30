// Public visitor presence tracker
(function () {
  "use strict";

  if (window.__wuVisitorPresenceInit) {
    return;
  }
  window.__wuVisitorPresenceInit = true;

  const currentScript = document.currentScript;
  const csrfToken = document.querySelector('meta[name="site-csrf"]')?.content || "";
  const configuredPageUrl =
    document.querySelector('meta[name="visitor-page-url"]')?.content || "";
  if (!csrfToken) {
    return;
  }

  function resolveAppUrl() {
    if (typeof window.APP_URL === "string" && window.APP_URL.trim() !== "") {
      return window.APP_URL.replace(/\/$/, "");
    }

    if (currentScript && currentScript.src) {
      return currentScript.src.replace(/\/js\/visitor-presence\.js(?:\?.*)?$/, "");
    }

    const match = window.location.pathname.match(/^(.*?)(?:\/js\/|\/home|$)/);
    return window.location.origin + (match && match[1] ? match[1] : "");
  }

  const APP_URL = resolveAppUrl();
  const PING_URL = APP_URL + "/ajax/visitor_ping.php";
  const INTERVAL = 60 * 1000;
  const TAB_HEARTBEAT = 15 * 1000;
  const TAB_TTL = 90 * 1000;
  const TAB_STORAGE_KEY = "wu_public_tabs";
  const TAB_ID_KEY = "wu_public_tab_id";
  const tabId =
    sessionStorage.getItem(TAB_ID_KEY) ||
    "pub-" + Math.random().toString(36).slice(2, 12) + Date.now().toString(36);
  sessionStorage.setItem(TAB_ID_KEY, tabId);

  const pageStart = Date.now();
  let unloadHandled = false;

  function getPageUrl() {
    if (configuredPageUrl) {
      return configuredPageUrl;
    }

    try {
      const appPath = new URL(APP_URL).pathname.replace(/\/$/, "");
      let currentPath = window.location.pathname || "/";
      if (appPath && currentPath.startsWith(appPath)) {
        currentPath = currentPath.slice(appPath.length) || "/";
      }
      return currentPath.startsWith("/") ? currentPath : "/" + currentPath;
    } catch (error) {
      return window.location.pathname || "/";
    }
  }

  function readTabs() {
    try {
      return JSON.parse(localStorage.getItem(TAB_STORAGE_KEY) || "{}");
    } catch (error) {
      return {};
    }
  }

  function writeTabs(tabs) {
    try {
      localStorage.setItem(TAB_STORAGE_KEY, JSON.stringify(tabs));
    } catch (error) {
      // Ignore storage failures.
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

  function updateTabState() {
    const tabs = pruneTabs(readTabs());
    tabs[tabId] = { ts: Date.now(), path: getPageUrl() };
    writeTabs(tabs);
    return tabs;
  }

  function removeTabState() {
    const tabs = pruneTabs(readTabs());
    delete tabs[tabId];
    writeTabs(tabs);
    return tabs;
  }

  function buildPayload(status, timeOnPage) {
    const fd = new FormData();
    fd.append("csrf_token", csrfToken);
    fd.append("page", getPageUrl());
    fd.append("title", document.title || "");
    fd.append("status", status);

    if (typeof timeOnPage === "number" && Number.isFinite(timeOnPage)) {
      fd.append("time_on_page", String(Math.max(0, Math.floor(timeOnPage))));
    }

    return fd;
  }

  async function ping(status, timeOnPage) {
    try {
      await fetch(PING_URL, {
        method: "POST",
        body: buildPayload(status, timeOnPage),
        credentials: "same-origin",
        keepalive: status === "offline",
      });
    } catch (error) {
      // Tracking failure should never interrupt the public page.
    }
  }

  function syncOnline() {
    updateTabState();
    ping("online");
  }

  function handleUnload() {
    if (unloadHandled) {
      return;
    }

    unloadHandled = true;
    const tabs = removeTabState();
    const stillOpen = Object.keys(pruneTabs(tabs)).length > 0;
    const payload = buildPayload(stillOpen ? "online" : "offline", (Date.now() - pageStart) / 1000);

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

  syncOnline();
  window.setInterval(syncOnline, INTERVAL);
  window.setInterval(updateTabState, TAB_HEARTBEAT);
  window.addEventListener("focus", syncOnline);
  window.addEventListener("pageshow", syncOnline);
  window.addEventListener("storage", function (event) {
    if (event.key === TAB_STORAGE_KEY) {
      updateTabState();
    }
  });
  window.addEventListener("beforeunload", handleUnload);
  window.addEventListener("pagehide", handleUnload);
})();
