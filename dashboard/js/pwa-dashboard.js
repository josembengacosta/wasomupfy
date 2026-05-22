// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0.1.1 — PWA Dashboard Manager
// Arquivo: dashboard/js/pwa-dashboard.js
// ══════════════════════════════════════════════════════

// ── Protecção contra dupla execução ──
if (
  typeof WasomInstallManager === "undefined" &&
  typeof WasomNativeManager === "undefined"
) {
  const WASOM_PWA_VERSION_URL = "/dashboard/manifest.json";
  const WASOM_PWA_VERSION_KEY = "wasom_pwa_manifest_version";
  const WASOM_PWA_CHECK_INTERVAL = 10 * 60 * 1000;

  let wasomUpdateRegistration = null;
  let wasomLatestPlatformVersion = null;
  let wasomApplyingUpdate = false;
  let wasomVersionTimerStarted = false;

  function _getStoredPlatformVersion() {
    try {
      return localStorage.getItem(WASOM_PWA_VERSION_KEY);
    } catch (e) {
      return null;
    }
  }

  function _setStoredPlatformVersion(version) {
    try {
      localStorage.setItem(WASOM_PWA_VERSION_KEY, version);
    } catch (e) {}
  }

  function _queuePlatformUpdateToast(reg) {
    if (reg) wasomUpdateRegistration = reg;

    const renderToast = function (triesLeft) {
      if (document.querySelector("[data-wasom-update-now]")) return;

      if (
        window._wasomNative &&
        window.WasomPWA &&
        typeof window.WasomPWA.showToast === "function"
      ) {
        window.WasomPWA.showToast(
          '<i class="bi bi-arrow-repeat me-1"></i> Nova versao disponivel <button type="button" class="wasom-toast-action" data-wasom-update-now>Actualizar agora</button>',
          "info",
          0
        );
        return;
      }

      if (triesLeft > 0) {
        setTimeout(function () {
          renderToast(triesLeft - 1);
        }, 500);
      }
    };

    renderToast(12);
  }

  function _clearWasomCaches() {
    if (!("caches" in window)) return Promise.resolve();

    return caches
      .keys()
      .then(function (keys) {
        return Promise.all(
          keys
            .filter(function (key) {
              return key.indexOf("wasom-") === 0;
            })
            .map(function (key) {
              return caches.delete(key);
            })
        );
      })
      .catch(function () {});
  }

  function _applyPlatformUpdate() {
    if (wasomApplyingUpdate) return;
    wasomApplyingUpdate = true;

    const registrationPromise =
      "serviceWorker" in navigator
        ? wasomUpdateRegistration
          ? Promise.resolve(wasomUpdateRegistration)
          : navigator.serviceWorker.getRegistration("/dashboard/")
        : Promise.resolve(null);

    registrationPromise
      .then(function (reg) {
        if (reg) wasomUpdateRegistration = reg;

        const waitingWorker = reg && reg.waiting;
        let reloaded = false;

        function reloadOnce() {
          if (reloaded) return;
          reloaded = true;
          if (wasomLatestPlatformVersion) {
            _setStoredPlatformVersion(wasomLatestPlatformVersion);
          }
          window.location.reload();
        }

        return _clearWasomCaches().then(function () {
          if (waitingWorker && "serviceWorker" in navigator) {
            navigator.serviceWorker.addEventListener(
              "controllerchange",
              reloadOnce,
              { once: true }
            );
            waitingWorker.postMessage({ type: "SKIP_WAITING" });
            setTimeout(reloadOnce, 1500);
            return;
          }

          setTimeout(reloadOnce, 150);
        });
      })
      .catch(function () {
        _clearWasomCaches().then(function () {
          window.location.reload();
        });
      });
  }

  function _checkPlatformVersion(reg) {
    if (!window.fetch) return;

    fetch(WASOM_PWA_VERSION_URL + "?check=" + Date.now(), {
      cache: "no-store",
      credentials: "same-origin",
    })
      .then(function (response) {
        return response.ok ? response.json() : null;
      })
      .then(function (manifest) {
        const version =
          manifest && manifest.version ? String(manifest.version) : "";
        if (!version) return;

        const storedVersion = _getStoredPlatformVersion();
        if (!storedVersion) {
          _setStoredPlatformVersion(version);
          return;
        }

        if (storedVersion !== version) {
          wasomLatestPlatformVersion = version;
          _queuePlatformUpdateToast(reg);
        }
      })
      .catch(function () {});
  }

  function _setupPlatformUpdateChecks(reg) {
    if (!reg) return;

    if (reg.waiting && navigator.serviceWorker.controller) {
      _queuePlatformUpdateToast(reg);
    }

    _checkPlatformVersion(reg);
    if (typeof reg.update === "function") {
      reg.update().catch(function () {});
    }

    if (!wasomVersionTimerStarted) {
      wasomVersionTimerStarted = true;
      setInterval(function () {
        if (typeof reg.update === "function") {
          reg.update().catch(function () {});
        }
        _checkPlatformVersion(reg);
      }, WASOM_PWA_CHECK_INTERVAL);

      document.addEventListener("visibilitychange", function () {
        if (document.hidden) return;
        if (typeof reg.update === "function") {
          reg.update().catch(function () {});
        }
        _checkPlatformVersion(reg);
      });

      window.addEventListener("online", function () {
        if (typeof reg.update === "function") {
          reg.update().catch(function () {});
        }
        _checkPlatformVersion(reg);
      });
    }
  }

  document.addEventListener("click", function (event) {
    const action =
      event.target.closest && event.target.closest("[data-wasom-update-now]");
    if (!action) return;

    event.preventDefault();
    action.disabled = true;
    action.textContent = "A actualizar...";
    _applyPlatformUpdate();
  });

  // ── Registo do Service Worker ─────────────────────────
  if ("serviceWorker" in navigator) {
    window.addEventListener("load", function () {
      navigator.serviceWorker
        .register("/dashboard/sw-wasomupfy.js", { scope: "/dashboard/" })
        .then(function (reg) {
          console.log("[WasomPWA] SW registado | scope:", reg.scope);
          wasomUpdateRegistration = reg;

          // Notificar utilizador quando há nova versão
          reg.addEventListener("updatefound", function () {
            const newWorker = reg.installing;
            if (!newWorker) return;

            newWorker.addEventListener("statechange", function () {
              if (
                newWorker.state === "installed" &&
                navigator.serviceWorker.controller
              ) {
                _queuePlatformUpdateToast(reg);
              }
            });
          });

          _setupPlatformUpdateChecks(reg);
        })
        .catch(function (err) {
          console.warn("[WasomPWA] Erro no SW:", err);
        });

      // Escutar mensagens do SW (ex: badge update)
      navigator.serviceWorker.addEventListener("message", function (event) {
        if (event.data && event.data.type === "BADGE_UPDATE") {
          _updateBadgeFromSW(event.data.count);
        }
      });
    });
  }

  // ══════════════════════════════════════════════════════
  // CLASS: WasomInstallManager
  // ══════════════════════════════════════════════════════
  class WasomInstallManager {
    constructor() {
      this.deferredPrompt = null;
      this.isInstalled = false;
      this.isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
      this.isAndroid = /Android/.test(navigator.userAgent);
      this.supportsPWA = false;

      this._checkInstallation();
      this._setupListeners();
    }

    _checkInstallation() {
      this.isInstalled =
        window.matchMedia("(display-mode: standalone)").matches ||
        window.navigator.standalone === true ||
        document.referrer.includes("android-app://");

      if (this.isInstalled) {
        document.documentElement.classList.add("pwa-installed");
      }
    }

    _setupListeners() {
      window.addEventListener("beforeinstallprompt", (e) => {
        e.preventDefault();
        this.deferredPrompt = e;
        this.supportsPWA = true;

        if (this.isIOS || this.isAndroid) {
          document.addEventListener(
            "click",
            () => {
              if (!this.isInstalled && this.deferredPrompt) this._showBtn();
            },
            { once: true }
          );

          setTimeout(() => {
            if (!this.isInstalled && this.deferredPrompt) this._showBtn();
          }, 30000);
        } else {
          setTimeout(() => {
            if (!this.isInstalled && this.deferredPrompt) this._showBtn();
          }, 5000);
        }
      });

      window.addEventListener("appinstalled", () => {
        this.isInstalled = true;
        this.deferredPrompt = null;
        this._hideBtn();
        document.documentElement.classList.add("pwa-installed");
        if (window.WasomPWA) {
          window.WasomPWA.showToast(
            '<i class="bi bi-check-circle-fill me-1"></i> Wasom Upfy instalado! Encontra o ícone no ecrã inicial.',
            "success"
          );
        }
      });
    }

    _showBtn() {
      if (document.getElementById("wasomPwaBtn") || this.isInstalled) return;

      const btn = document.createElement("button");
      btn.id = "wasomPwaBtn";
      btn.className = "wasom-pwa-install-btn";
      btn.setAttribute("aria-label", "Instalar Wasom Upfy");
      btn.innerHTML = `
                <i class="bi bi-download"></i>
                <span class="btn-text">Instalar App</span>
                <span class="btn-sub">Offline &amp; Rápido</span>
            `;

      btn.addEventListener("click", () => this.install());
      document.body.appendChild(btn);

      requestAnimationFrame(() => btn.classList.add("show"));

      this._autoHide = setTimeout(() => this._hideBtn(), 30000);
    }

    _hideBtn() {
      const btn = document.getElementById("wasomPwaBtn");
      if (!btn) return;
      btn.classList.remove("show");
      setTimeout(() => btn && btn.parentNode && btn.remove(), 300);
      if (this._autoHide) clearTimeout(this._autoHide);
    }

    async install() {
      if (!this.deferredPrompt) {
        this._showPlatformInstructions();
        return;
      }
      try {
        this.deferredPrompt.prompt();
        const { outcome } = await this.deferredPrompt.userChoice;
        if (outcome !== "accepted" && window.WasomPWA) {
          window.WasomPWA.showToast(
            "Podes instalar mais tarde pelo menu do browser.",
            "info"
          );
        }
        this.deferredPrompt = null;
        this._hideBtn();
      } catch (err) {
        console.warn("[WasomPWA] Erro no install:", err);
        this._showPlatformInstructions();
      }
    }

    _showPlatformInstructions() {
      let html = "";
      if (this.isIOS) {
        html = `<strong><i class="bi bi-apple"></i> Instalar no iPhone/iPad</strong>
                        <ol class="mt-2 mb-0 ps-3">
                          <li>Toca em <i class="bi bi-box-arrow-up"></i> (partilhar)</li>
                          <li>Escolhe "Adicionar ao ecrã inicial"</li>
                          <li>Confirma tocando em "Adicionar"</li>
                        </ol>`;
      } else {
        html = `<strong><i class="bi bi-android2"></i> Instalar no Android / Desktop</strong>
                        <p class="mt-1 mb-0">Abre o menu do browser (⋮) e escolhe "Instalar aplicativo" ou "Adicionar ao ecrã inicial".</p>`;
      }

      if (window.WasomPWA) {
        window.WasomPWA.showToast(html, "info", 8000);
      }
    }
  }

  // ══════════════════════════════════════════════════════
  // CLASS: WasomNativeManager
  // ══════════════════════════════════════════════════════
  class WasomNativeManager {
    constructor() {
      this.isStandalone = window.matchMedia(
        "(display-mode: standalone)"
      ).matches;
      this.isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
      this.isAndroid = /Android/.test(navigator.userAgent);
      this.isPWA = window.navigator.standalone || this.isStandalone;
      this.hasHaptic = "vibrate" in navigator;
      this.hasBadge = "setAppBadge" in navigator;
      this.hasShare = "share" in navigator;
      this.hasClipboard = "clipboard" in navigator;

      this._init();
    }

    _init() {
      this._setupNetwork();

      if (!this.isPWA) return;

      console.log("[WasomPWA] A correr como app nativo");

      this._addNativeClasses();
      this._showSplash();
      this._setupNativeBehavior();
      this._setupBadge();
      this._exposeAPI();
    }

    _addNativeClasses() {
      document.documentElement.classList.add("pwa-native", "pwa-launched");
      if (this.isIOS) document.documentElement.classList.add("pwa-ios");
      if (this.isAndroid) document.documentElement.classList.add("pwa-android");
      if (this.isIOS) this._addSafeAreaStyles();
    }

    _addSafeAreaStyles() {
      const s = document.createElement("style");
      s.textContent = `
                /* iOS safe areas para o dashboard */
                .pwa-ios .dashboard-topbar,
                .pwa-ios .navbar,
                .pwa-ios .topbar {
                    padding-top: env(safe-area-inset-top) !important;
                }
                .pwa-ios .wasom-pwa-install-btn,
                .pwa-ios .back-to-top-btn {
                    margin-bottom: env(safe-area-inset-bottom);
                }
                .pwa-ios body {
                    padding-bottom: env(safe-area-inset-bottom);
                }
            `;
      document.head.appendChild(s);
    }

    _showSplash() {
      if (document.readyState === "complete") return;

      const splash = document.createElement("div");
      splash.id = "wasomSplash";
      splash.style.cssText = `
                position: fixed;
                top: 0; left: 0; width: 100%; height: 100%;
                background: #181818;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                z-index: 99999;
                transition: opacity .4s ease;
            `;

      splash.innerHTML = `
                <img src="/assets/img/icones/wasomupfy_fiv.png"
                     alt="Wasom Upfy"
                     style="width: 48px; height: 48px; border-radius: 12px; margin-bottom: 24px;" />

                <div class="wasom-spinner"></div>

                <div style="position: absolute; bottom: 48px; left: 0; right: 0; text-align: center;
                            color: #888; font-size: 0.75rem; font-weight: 400; letter-spacing: 0.3px;">
                   from
                   <br> 
                   WASOM MUSIC GROUP
                </div>
            `;

      const style = document.createElement("style");
      style.textContent = `
                .wasom-spinner {
                    width: 24px;
                    height: 24px;
                    border: 2px solid rgba(255,255,255,0.2);
                    border-top-color: #FF0089;
                    border-radius: 50%;
                    animation: wasomSpin 0.7s linear infinite;
                }
                @keyframes wasomSpin {
                    to { transform: rotate(360deg); }
                }
            `;
      document.head.appendChild(style);

      document.body.appendChild(splash);

      const removeSplash = () => {
        splash.style.opacity = "0";
        setTimeout(() => splash.remove(), 400);
      };

      if (document.readyState === "complete") {
        setTimeout(removeSplash, 1200);
      } else {
        window.addEventListener("load", () => setTimeout(removeSplash, 1200), {
          once: true,
        });
      }
    }

    _setupNativeBehavior() {
      if (this.isIOS) {
        let lastTouch = 0;
        document.addEventListener(
          "touchend",
          (e) => {
            const now = Date.now();
            if (now - lastTouch <= 300) e.preventDefault();
            lastTouch = now;
          },
          { passive: false }
        );
      }

      this._setupSwipeBack();

      if (this.isAndroid) {
        const meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.content = "#181818";
      }

      window.addEventListener("orientationchange", () => {
        setTimeout(() => {
          const portrait = window.innerHeight > window.innerWidth;
          document.documentElement.classList.toggle("pwa-landscape", !portrait);
          document.documentElement.classList.toggle("pwa-portrait", portrait);
        }, 100);
      });
    }

    _setupSwipeBack() {
      let startX = 0,
        startY = 0,
        startTime = 0;

      document.addEventListener(
        "touchstart",
        (e) => {
          if (e.touches[0].clientX < 50) {
            startX = e.touches[0].pageX;
            startY = e.touches[0].pageY;
            startTime = Date.now();
          }
        },
        { passive: true }
      );

      document.addEventListener(
        "touchend",
        (e) => {
          if (!startX) return;
          const dx = e.changedTouches[0].pageX - startX;
          const dy = Math.abs(e.changedTouches[0].pageY - startY);
          if (dx > 50 && dy < 100 && Date.now() - startTime < 500) {
            this.vibrate(50);
            if (window.history.length > 1) window.history.back();
          }
          startX = startY = startTime = 0;
        },
        { passive: true }
      );
    }

    _setupNetwork() {
      window.addEventListener("online", () => {
        this.vibrate(100);
        this.showToast(
          '<i class="bi bi-wifi me-1"></i> Ligação restaurada',
          "success"
        );
      });

      window.addEventListener("offline", () => {
        this.vibrate([100, 50, 100]);
        this.showToast(
          '<i class="bi bi-wifi-off me-1"></i> Sem ligação — modo offline activo',
          "warning",
          0
        );
      });

      if (!navigator.onLine) {
        setTimeout(() => {
          this.showToast(
            '<i class="bi bi-wifi-off me-1"></i> Estás offline. Algumas páginas estão em cache.',
            "warning",
            5000
          );
        }, 1500);
      }
    }

    _setupBadge() {
      if (!this.hasBadge) return;
      this._syncBadge();
      setInterval(() => this._syncBadge(), 60000);
    }

    async _syncBadge() {
      try {
        const res = await fetch(
          "/dashboard/ajax/notifications_api?action=unread_count",
          {
            credentials: "include",
          }
        );
        if (!res.ok) return;
        const data = await res.json();
        const count = parseInt(data.count ?? data.unread ?? 0, 10);
        _updateBadgeFromSW(count);
      } catch (e) {
        /* silencioso */
      }
    }

    vibrate(pattern) {
      if (this.hasHaptic) {
        try {
          navigator.vibrate(pattern);
        } catch (e) {}
      }
    }

    showToast(message, type = "info", duration = 3000) {
      const existing = document.querySelector(".wasom-native-toast");
      if (existing) {
        existing.classList.add("hiding");
        setTimeout(() => existing.remove(), 300);
      }

      const toast = document.createElement("div");
      toast.className = `wasom-native-toast ${type}`;
      toast.innerHTML = `<span>${message}</span>`;

      if (duration === 0) {
        const close = document.createElement("button");
        close.innerHTML = '<i class="bi bi-x-lg"></i>';
        close.style.cssText =
          "background:none;border:none;color:inherit;cursor:pointer;margin-left:8px;padding:0;font-size:.85rem;";
        close.onclick = () => {
          toast.classList.add("hiding");
          setTimeout(() => toast.remove(), 300);
        };
        toast.appendChild(close);
      }

      document.body.appendChild(toast);

      if (duration > 0) {
        setTimeout(() => {
          toast.classList.add("hiding");
          setTimeout(() => toast.parentNode && toast.remove(), 300);
        }, duration);
      }
    }

    _exposeAPI() {
      window.WasomPWA.vibrate = (p) => this.vibrate(p);
      window.WasomPWA.isIOS = this.isIOS;
      window.WasomPWA.isAndroid = this.isAndroid;
      window.WasomPWA.isPWA = this.isPWA;
      window.WasomPWA.hasHaptic = this.hasHaptic;
      window.WasomPWA.hasBadge = this.hasBadge;
      window.WasomPWA.syncBadge = () => this._syncBadge();
    }
  }

  // ── Função global de badge ──
  function _updateBadgeFromSW(count) {
    if ("setAppBadge" in navigator) {
      if (count > 0) {
        navigator.setAppBadge(count).catch(() => {});
      } else {
        navigator.clearAppBadge().catch(() => {});
      }
    }
  }

  // ══════════════════════════════════════════════════════
  // ESTILOS INJETADOS
  // ══════════════════════════════════════════════════════
  (function injectStyles() {
    if (document.getElementById("wasom-pwa-styles")) return;

    const s = document.createElement("style");
    s.id = "wasom-pwa-styles";
    s.textContent = `
            .wasom-pwa-install-btn {
                position: fixed;
                bottom: 88px;
                right: 18px;
                z-index: 9990;
                background: linear-gradient(135deg, #FF0089 0%, #cc006e 100%);
                color: #fff;
                border: none;
                padding: 10px 18px;
                border-radius: 50px;
                font-weight: 700;
                font-size: .82rem;
                cursor: pointer;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 2px;
                box-shadow: 0 6px 22px rgba(255,0,137,.45);
                opacity: 0;
                transform: translateY(20px);
                transition: opacity .3s ease, transform .3s ease, box-shadow .2s ease;
            }
            .wasom-pwa-install-btn.show {
                opacity: 1;
                transform: translateY(0);
            }
            .wasom-pwa-install-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 28px rgba(255,0,137,.6);
            }
            .wasom-pwa-install-btn:active {
                transform: scale(.95);
            }
            .wasom-pwa-install-btn .btn-text { font-size: .82rem; }
            .wasom-pwa-install-btn .btn-sub  { font-size: .7rem; opacity: .8; }
            .pwa-installed .wasom-pwa-install-btn { display: none !important; }

            .wasom-native-toast {
                position: fixed;
                bottom: 72px;
                left: 50%;
                transform: translateX(-50%);
                background: #1e1e1e;
                color: #f8f8f8;
                padding: 10px 20px;
                border-radius: 50px;
                z-index: 10001;
                box-shadow: 0 4px 20px rgba(0,0,0,.45);
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: .85rem;
                font-weight: 500;
                max-width: 92vw;
                border-left: 3px solid #FF0089;
                animation: wasomToastIn .3s ease forwards;
            }
            .wasom-native-toast > span {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }
            .wasom-toast-action {
                border: 0;
                border-radius: 999px;
                background: #FF0089;
                color: #fff;
                cursor: pointer;
                font: inherit;
                font-weight: 700;
                line-height: 1;
                padding: 7px 12px;
                white-space: nowrap;
            }
            .wasom-toast-action:disabled {
                cursor: wait;
                opacity: .75;
            }
            .wasom-native-toast.success { border-color: #10b981; background: #0d2b22; }
            .wasom-native-toast.warning { border-color: #f59e0b; background: #2b1f09; }
            .wasom-native-toast.error   { border-color: #ef4444; background: #2b0e0e; }
            .wasom-native-toast.hiding  { animation: wasomToastOut .3s ease forwards; }

            @keyframes wasomToastIn {
                from { opacity:0; transform: translateX(-50%) translateY(16px); }
                to   { opacity:1; transform: translateX(-50%) translateY(0); }
            }
            @keyframes wasomToastOut {
                from { opacity:1; transform: translateX(-50%) translateY(0); }
                to   { opacity:0; transform: translateX(-50%) translateY(16px); }
            }

            @supports (padding-bottom: env(safe-area-inset-bottom)) {
                .pwa-ios .wasom-pwa-install-btn {
                    bottom: calc(88px + env(safe-area-inset-bottom));
                }
                .pwa-ios .wasom-native-toast {
                    bottom: calc(72px + env(safe-area-inset-bottom));
                }
            }
        `;
    document.head.appendChild(s);
  })();

  // ══════════════════════════════════════════════════════
  // INICIALIZAÇÃO
  // ══════════════════════════════════════════════════════
  window.WasomPWA = {
    showToast: function (msg, type, duration) {
      if (window._wasomNative)
        window._wasomNative.showToast(msg, type, duration);
    },
  };

  document.addEventListener("DOMContentLoaded", function () {
    setTimeout(function () {
      window._wasomInstall = new WasomInstallManager();
      window._wasomNative = new WasomNativeManager();

      window.WasomPWA.showToast = function (msg, type, duration) {
        window._wasomNative.showToast(msg, type, duration);
      };

      window.wasomPWAStatus = function () {
        return {
          installed: window._wasomInstall.isInstalled,
          isPWA: window._wasomNative.isPWA,
          isIOS: window._wasomNative.isIOS,
          isAndroid: window._wasomNative.isAndroid,
          hasBadge: window._wasomNative.hasBadge,
          sw: "serviceWorker" in navigator,
          manifest: !!document.querySelector('link[rel="manifest"]'),
        };
      };

      console.log("[WasomPWA] Managers inicializados", window.wasomPWAStatus());
    }, 800);
  });

  console.log("[WasomPWA] pwa-dashboard.js carregado");
} // fim da verificação de duplicação
