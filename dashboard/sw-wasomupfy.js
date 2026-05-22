// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0.1.1 — Service Worker
// Arquivo: sw-wasomupfy.js  (na raiz: /sw-wasomupfy.js)
// Scope: /dashboard/ — não cobre /collab/ nem site público
// ══════════════════════════════════════════════════════

const SW_VERSION = "2.0.1";
const CACHE_STATIC = `wasom-static-v${SW_VERSION}`;
const CACHE_PAGES = `wasom-pages-v${SW_VERSION}`;

// ══════════════════════════════════════════════════════
// RECURSOS ESTÁTICOS — Cache imediato no install
// ══════════════════════════════════════════════════════
const STATIC_ASSETS = [
  // Ícones PWA
  "/assets/img/icones/wasomupfy_fiv.png",
  "/assets/img/icones/wasomupfy_fiv_512.png",
  "/assets/img/icones/wasomupfy_fiv_maskable.png",

  // CSS do dashboard
  "/dashboard/css/dashboard-style.css",
  "/dashboard/css/lastest-style.css",

  // JS do dashboard
  "/dashboard/js/theme.wp.js",
  "/dashboard/js/wp.tools.js",

  // CDN — Bootstrap CSS + Icons + JS
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css",
  "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css",
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js",

  // CDN — Font Awesome
  "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css",

  // Página offline
  "/dashboard/status/offline",
];

// ══════════════════════════════════════════════════════
// PÁGINAS DO DASHBOARD — Network-first + cache
// Exclui: /collab/, /ajax/, *_process, *-process
// ══════════════════════════════════════════════════════
const CACHEABLE_PAGES = [
  // Raiz do dashboard
  "/dashboard",
  "/dashboard/painel",
  "/dashboard/all-plans",
  "/dashboard/onboarding_done",

  // Finanças
  "/dashboard/overview",
  "/dashboard/transactions",
  "/dashboard/withdraw",

  // Lançamentos
  "/dashboard/releases",
  "/dashboard/creat-release",
  "/dashboard/draft-release",
  "/dashboard/edit-release",

  // Analytics
  "/dashboard/statistics",
  "/dashboard/export",
  "/dashboard/report",
  "/dashboard/compare",
  "/dashboard/artist-details",
  "/dashboard/country-details",
  "/dashboard/playlist-details",

  // Artistas
  "/dashboard/artists-list",
  "/dashboard/add-artist",

  // Perfil e conta
  "/dashboard/user/profile",
  "/dashboard/account/manage-account",

  // Páginas internas
  "/dashboard/page/notifications",
  "/dashboard/page/settings",
  "/dashboard/page/support",
  "/dashboard/page/faq",
  "/dashboard/page/help",
  "/dashboard/page/about",
  "/dashboard/page/politicies/terms",
  "/dashboard/page/politicies/privacy",

  // Serviços e pagamento
  "/dashboard/services/available-services",
  "/dashboard/payment/pay",
];

// ══════════════════════════════════════════════════════
// ROTAS NUNCA CACHEADAS — Sempre network-only
// Process files, AJAX, dados sensíveis
// ══════════════════════════════════════════════════════
const NETWORK_ONLY_PATTERNS = [
  /\/_process/, // login_process, payment_process, etc.
  /-process\/?$/, // creat_release_process, etc.
  /\/ajax\//, // /dashboard/ajax/*
  /\/logout\/?$/, // logout
  /\/collab\//, // páginas de colaborador — fora do scope PWA
  /\/finances\/withdrawal_process/,
  /\/finances\/account_process/,
  /\/finances\/split_process/,
  /\/payment\/payment_process/,
];

// ══════════════════════════════════════════════════════
// INSTALL — cache estático
// ══════════════════════════════════════════════════════
self.addEventListener("install", function (event) {
  console.log("[SW Wasom] Instalando v" + SW_VERSION);

  event.waitUntil(
    caches
      .open(CACHE_STATIC)
      .then(function (cache) {
        return cache.addAll(STATIC_ASSETS).catch(function (err) {
          console.warn("[SW Wasom] Alguns assets não foram cacheados:", err);
        });
      })
      .then(function () {
        return self.skipWaiting();
      })
  );
});

// ══════════════════════════════════════════════════════
// ACTIVATE — limpa caches antigos + preload páginas críticas
// ══════════════════════════════════════════════════════
self.addEventListener("activate", function (event) {
  console.log("[SW Wasom] Activado v" + SW_VERSION);

  event.waitUntil(
    Promise.all([
      // 1. Limpar caches de versões antigas
      caches.keys().then(function (keys) {
        return Promise.all(
          keys
            .filter(function (key) {
              return (
                key !== CACHE_STATIC &&
                key !== CACHE_PAGES &&
                key.startsWith("wasom-")
              );
            })
            .map(function (key) {
              console.log("[SW Wasom] Removendo cache antigo:", key);
              return caches.delete(key);
            })
        );
      }),

      // 2. Tomar controlo imediato de todos os clientes
      self.clients.claim(),

      // 3. Preload em background das páginas críticas
      preloadCriticalPages(),
    ])
  );
});

// Preload silencioso das páginas mais visitadas
async function preloadCriticalPages() {
  const critical = [
    "/dashboard/painel",
    "/dashboard/overview",
    "/dashboard/releases",
    "/dashboard/page/notifications",
  ];

  try {
    const cache = await caches.open(CACHE_PAGES);
    for (const url of critical) {
      const cached = await cache.match(url);
      if (!cached) {
        fetch(url, { credentials: "include" })
          .then(function (res) {
            if (res && res.status === 200) {
              cache.put(url, res.clone());
            }
          })
          .catch(function () {});
      }
    }
  } catch (e) {
    // Silencioso — não bloqueia activate
  }
}

// ══════════════════════════════════════════════════════
// FETCH — estratégias de cache por tipo de pedido
// ══════════════════════════════════════════════════════
self.addEventListener("fetch", function (event) {
  var req = event.request;
  var url;

  // Só processar GET
  if (req.method !== "GET") return;

  try {
    url = new URL(req.url);
  } catch (e) {
    return;
  }

  // Ignorar extensões de browser
  if (url.protocol === "chrome-extension:" || url.protocol === "chrome:")
    return;

  // ── 1. Network Only — process files, AJAX, collab, logout ──
  if (isNetworkOnly(url)) {
    // Não interceptar — deixar passar directamente
    return;
  }

  // Codigo local: network-first para evitar JS/CSS antigo.
  if (isLocalCodeAsset(url)) {
    event.respondWith(networkFirstAsset(req, CACHE_STATIC));
    return;
  }

  // ── 2. Recursos estáticos — Cache First ────────────────────
  if (isStaticAsset(url)) {
    event.respondWith(cacheFirst(req, CACHE_STATIC));
    return;
  }

  // ── 3. CDN externo (Bootstrap, FA, etc.) — Stale While Revalidate ──
  if (isExternalCDN(url)) {
    event.respondWith(staleWhileRevalidate(req, CACHE_STATIC));
    return;
  }

  // ── 4. Páginas cacheáveis do dashboard — Network First ─────
  if (isCacheablePage(url)) {
    event.respondWith(networkFirst(req, CACHE_PAGES));
    return;
  }

  // ── 5. Resto dentro do /dashboard/ — Network com fallback ──
  if (url.pathname.startsWith("/dashboard/")) {
    event.respondWith(networkWithFallback(req));
    return;
  }

  // Fora do scope — não interceptar
});

// ══════════════════════════════════════════════════════
// CLASSIFICADORES DE PEDIDOS
// ══════════════════════════════════════════════════════

function isNetworkOnly(url) {
  return NETWORK_ONLY_PATTERNS.some(function (pattern) {
    return pattern.test(url.pathname);
  });
}

function isLocalCodeAsset(url) {
  return (
    url.hostname === self.location.hostname &&
    /\.(css|js)(\?.*)?$/.test(url.pathname)
  );
}

function isStaticAsset(url) {
  // Ficheiros locais com extensão estática
  return (
    url.hostname === self.location.hostname &&
    /\.(css|js|png|jpg|jpeg|ico|webp|woff2?|svg|gif)(\?.*)?$/.test(url.pathname)
  );
}

function isExternalCDN(url) {
  return (
    url.hostname === "cdn.jsdelivr.net" ||
    url.hostname === "cdnjs.cloudflare.com" ||
    url.hostname === "fonts.googleapis.com" ||
    url.hostname === "fonts.gstatic.com" ||
    url.hostname === "unpkg.com"
  );
}

function isCacheablePage(url) {
  if (url.hostname !== self.location.hostname) return false;
  return CACHEABLE_PAGES.some(function (p) {
    return url.pathname === p || url.pathname === p + "/";
  });
}

// ══════════════════════════════════════════════════════
// ESTRATÉGIAS DE CACHE
// ══════════════════════════════════════════════════════

// Network First para CSS/JS locais: actualiza rapido, mas funciona offline.
async function networkFirstAsset(req, cacheName) {
  try {
    const res = await fetch(req, {
      cache: "no-store",
      credentials: "same-origin",
    });
    if (res && res.status === 200) {
      const cache = await caches.open(cacheName);
      cache.put(req, res.clone()).catch(function () {});
    }
    return res;
  } catch (e) {
    const cached = await caches.match(req);
    if (cached) return cached;
    return new Response("", { status: 404 });
  }
}

// Cache First — estáticos (imagens, CSS, JS locais)
async function cacheFirst(req, cacheName) {
  const cached = await caches.match(req);
  if (cached) return cached;

  try {
    const res = await fetch(req);
    if (res && res.status === 200) {
      const cache = await caches.open(cacheName);
      cache.put(req, res.clone()).catch(function () {});
    }
    return res;
  } catch (e) {
    // Imagem offline — retornar SVG placeholder
    if (/\.(png|jpg|jpeg|gif|webp)(\?.*)?$/.test(req.url)) {
      return new Response(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">' +
          '<rect width="100" height="100" fill="#1a1a1a"/>' +
          '<text x="50" y="55" text-anchor="middle" fill="#444" font-size="12">Offline</text>' +
          "</svg>",
        { headers: { "Content-Type": "image/svg+xml" } }
      );
    }
    return new Response("", { status: 404 });
  }
}

// Stale While Revalidate — CDN externo
async function staleWhileRevalidate(req, cacheName) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(req);

  // Atualizar em background independentemente
  fetch(req)
    .then(function (res) {
      if (res && res.ok) {
        cache.put(req, res.clone()).catch(function () {});
      }
    })
    .catch(function () {});

  // Servir imediatamente do cache se existir, senão aguardar network
  if (cached) return cached;

  try {
    const res = await fetch(req);
    if (res && res.ok) {
      cache.put(req, res.clone()).catch(function () {});
    }
    return res;
  } catch (e) {
    return new Response("", { status: 503 });
  }
}

// Network First — páginas do dashboard
async function networkFirst(req, cacheName) {
  try {
    const res = await fetch(req, { credentials: "include" });
    if (res && res.status === 200) {
      const cache = await caches.open(cacheName);
      cache.put(req, res.clone()).catch(function () {});
    }
    return res;
  } catch (e) {
    const cached = await caches.match(req);
    if (cached) return cached;
    return offlinePage();
  }
}

// Network com fallback — resto do dashboard
async function networkWithFallback(req) {
  try {
    return await fetch(req, { credentials: "include" });
  } catch (e) {
    const cached = await caches.match(req);
    return cached || offlinePage();
  }
}

// Página offline inline (sem depender de ficheiro externo)
function offlinePage() {
  return new Response(
    "<!DOCTYPE html>" +
      '<html lang="pt-ao">' +
      "<head>" +
      '<meta charset="utf-8">' +
      '<meta name="viewport" content="width=device-width,initial-scale=1">' +
      "<title>Offline — Wasom Upfy</title>" +
      "<style>" +
      "body{font-family:Arial,sans-serif;display:flex;align-items:center;justify-content:center;" +
      "min-height:100vh;margin:0;background:#0f0f0f;color:#fff}" +
      ".box{text-align:center;padding:2rem;max-width:380px}" +
      ".logo{font-size:1.6rem;font-weight:900;color:#FF0089;margin-bottom:1.5rem;letter-spacing:.5px}" +
      ".icon{font-size:3.5rem;margin-bottom:1rem}" +
      "h1{font-size:1.3rem;margin-bottom:.5rem}" +
      "p{color:#888;font-size:.9rem;line-height:1.6}" +
      ".btn{display:inline-block;margin-top:1.5rem;padding:.65rem 2rem;background:#FF0089;" +
      "color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:.9rem}" +
      ".btn:hover{background:#cc006e}" +
      "</style>" +
      "</head>" +
      "<body>" +
      '<div class="box">' +
      '<div class="logo">WASOM UPFY</div>' +
      '<div class="icon">📡</div>' +
      "<h1>Sem ligação à internet</h1>" +
      "<p>Verifica a tua conexão e tenta novamente.<br>Algumas páginas podem estar disponíveis em cache.</p>" +
      '<a href="javascript:location.reload()" class="btn">Tentar novamente</a>' +
      "</div>" +
      "</body></html>",
    {
      status: 503,
      headers: {
        "Content-Type": "text/html; charset=utf-8",
        "X-Offline-Mode": "true",
      },
    }
  );
}

// ══════════════════════════════════════════════════════
// PUSH — notificações do servidor
// ══════════════════════════════════════════════════════
self.addEventListener("push", function (event) {
  var data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (e) {
    data = {
      title: "Wasom Upfy",
      body: event.data ? event.data.text() : "Nova notificação.",
    };
  }

  var title = data.title || "Wasom Upfy";
  var body = data.body || "Tens uma nova notificação.";
  var type = data.type || "info";
  var url = data.url || "/dashboard/page/notifications";
  var tag = data.tag || "notif-" + Date.now();

  var iconMap = {
    payment: "/dashboard/assets/img/notif/payment.png",
    music: "/dashboard/assets/img/notif/music.png",
    system: "/dashboard/assets/img/notif/system.png",
    warning: "/dashboard/assets/img/notif/warning.png",
    error: "/dashboard/assets/img/notif/error.png",
    broadcast: "/dashboard/assets/img/notif/broadcast.png",
  };

  var options = {
    body: body,
    icon: iconMap[type] || "/assets/img/icones/wasomupfy_fiv_512.png",
    badge: "/assets/img/icones/wasomupfy_fiv.png",
    tag: tag,
    renotify: true,
    requireInteraction: type === "payment" || type === "error",
    vibrate: [200, 100, 200],
    data: { url: url, type: type, id: data.id || null },
    actions: buildActions(type),
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

function buildActions(type) {
  switch (type) {
    case "payment":
      return [
        { action: "view", title: "💰 Ver pagamento" },
        { action: "later", title: "⏰ Ver mais tarde" },
      ];
    case "music":
      return [
        { action: "view", title: "🎵 Ver lançamento" },
        { action: "dismiss", title: "Dispensar" },
      ];
    case "error":
      return [
        { action: "view", title: "⚠️ Ver detalhes" },
        { action: "support", title: "🎧 Contactar suporte" },
      ];
    case "broadcast":
      return [
        { action: "view", title: "📢 Ler comunicado" },
        { action: "dismiss", title: "Dispensar" },
      ];
    default:
      return [
        { action: "view", title: "👀 Ver notificação" },
        { action: "dismiss", title: "Dispensar" },
      ];
  }
}

// ══════════════════════════════════════════════════════
// NOTIFICATION CLICK
// ══════════════════════════════════════════════════════
self.addEventListener("notificationclick", function (event) {
  event.notification.close();

  var action = event.action;
  var data = event.notification.data || {};
  var type = data.type || "info";
  var dest = "/dashboard/page/notifications";

  // Acção "later" e "dismiss" — só fecha
  if (action === "later" || action === "dismiss") return;

  // Acção "support"
  if (action === "support") {
    dest = "/dashboard/page/support";
  } else {
    // Acção "view" ou clique directo no corpo
    switch (type) {
      case "payment":
        dest = "/dashboard/overview";
        break;
      case "music":
        dest = "/dashboard/releases";
        break;
      case "error":
        dest = "/dashboard/page/support";
        break;
      case "broadcast":
        dest = "/dashboard/page/notifications";
        break;
      default:
        dest = data.url || dest;
    }
  }

  event.waitUntil(
    clients
      .matchAll({ type: "window", includeUncontrolled: true })
      .then(function (clientList) {
        for (var i = 0; i < clientList.length; i++) {
          var c = clientList[i];
          if (c.url.includes(dest) && "focus" in c) {
            return c.focus();
          }
        }
        if (clients.openWindow) {
          return clients.openWindow(dest);
        }
      })
  );
});

self.addEventListener("notificationclose", function (event) {
  var data = event.notification.data || {};
  console.log("[SW Wasom] Notificação dispensada:", data.type, data.id);
});

// ══════════════════════════════════════════════════════
// MESSAGE — comunicação com a página
// ══════════════════════════════════════════════════════
self.addEventListener("message", function (event) {
  if (!event.data) return;

  switch (event.data.type) {
    case "SKIP_WAITING":
      self.skipWaiting();
      break;

    case "GET_VERSION":
      if (event.ports && event.ports[0]) {
        event.ports[0].postMessage({
          version: SW_VERSION,
          cacheStatic: CACHE_STATIC,
          cachePages: CACHE_PAGES,
        });
      }
      break;

    case "CLEAR_CACHE":
      caches
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
        .then(function () {
          if (event.ports && event.ports[0]) {
            event.ports[0].postMessage({ success: true });
          }
        });
      break;

    case "PRELOAD_PAGE":
      // Permite que a página force cache de uma rota específica
      var pageUrl = event.data.url;
      if (pageUrl && pageUrl.startsWith("/dashboard/")) {
        caches.open(CACHE_PAGES).then(function (cache) {
          fetch(pageUrl, { credentials: "include" })
            .then(function (res) {
              if (res && res.status === 200) cache.put(pageUrl, res);
            })
            .catch(function () {});
        });
      }
      break;
  }
});

// ══════════════════════════════════════════════════════
// BACKGROUND SYNC — reenviar acções offline
// ══════════════════════════════════════════════════════
self.addEventListener("sync", function (event) {
  if (event.tag === "sync-mark-read") {
    event.waitUntil(syncPendingReads());
  }
  if (event.tag === "sync-prefs") {
    event.waitUntil(syncPendingPrefs());
  }
});

async function syncPendingReads() {
  // Ler da IndexedDB e enviar para /dashboard/ajax/notifications_api
  console.log("[SW Wasom] Background sync: mark-read");
}

async function syncPendingPrefs() {
  console.log("[SW Wasom] Background sync: prefs");
}

// ══════════════════════════════════════════════════════
console.log("[SW Wasom] v" + SW_VERSION + " carregado | scope: /dashboard/");
