// ============================================
// SERVICE WORKER - WASOM UPFY v2.0 (OTIMIZADO)
// ============================================

const APP_VERSION = "2.0.0";
const CACHE_NAME = `wasom-upfy-pwa-v${APP_VERSION.replace(/\./g, "-")}`;

// ========== CACHE STRATEGIES ==========

// 1. CACHE IMMEDIATELY - Recursos essenciais
const IMMEDIATE_CACHE = [
  // HTML Core
  "/",
  "/painel.html",
  "/offline.html",

  // Manifest & Icons
  "/manifest.json",
  "/assets/img/icones/wasomupfy_fiv.png",
  "/assets/img/icones/wasomupfy_fiv_512.png",
  "/assets/img/icones/wasomupfy_fiv_maskable.png",

  // Core CSS
  "/css/bootstrap.min.css",
  "/css/bootstrap-icons.css",
  "/css/dashboard-style.css",

  // Core JS
  "/js/bootstrap.bundle.min.js",
];

// 2. CACHE LAZY - Recursos importantes (cache após instalação)
const LAZY_CACHE = [
  // Other HTML pages
  "/settings.html",
  "/help.html",
  "/launch/releases.html",
  "/analytics/statistics.html",
  "/finances/overview.html",
  "/artists/artists-list.html",
  "/youtube.html",
  "/user/profile.html",
  "/notifications.html",

  // Screenshots
  "/assets/img/screenshots/dashboard.png",
  "/assets/img/screenshots/settings.png",
];

// 3. NETWORK ONLY - Nunca cachear
const NETWORK_ONLY = [
  /\/api\//i,
  /\/submit/i,
  /contact-form/i,
];

// 4. PATTERNS FOR DYNAMIC CACHING
const PATTERNS = {
  html: /\.html$/i,
  css: /\.css$/i,
  js: /\.js$/i,
  images: /\.(png|jpg|jpeg|gif|svg|ico|webp)$/i,
  fonts: /\.(woff|woff2|ttf|eot)$/i,
};

// ========== INSTALL - Cache Immediate Resources ==========

self.addEventListener("install", (event) => {
  console.log(`SW Wasom Upfy v${APP_VERSION} instalando...`);

  event.waitUntil(
    caches
      .open(CACHE_NAME)
      .then((cache) => {
        console.log(" Cacheando recursos essenciais...");
        return cache.addAll(IMMEDIATE_CACHE).catch((err) => {
          console.warn(" Alguns recursos não puderam ser cacheados:", err);
          // Continuar mesmo com erros
        });
      })
      .then(() => {
        console.log("Instalação completa!");
        return self.skipWaiting(); // Ativar novo service worker imediatamente
      })
  );
});

// ========== ACTIVATE - Cleanup & Claim ==========

self.addEventListener("activate", (event) => {
  console.log(`SW Wasom Upfy v${APP_VERSION} ativado`);

  event.waitUntil(
    Promise.all([
      // Limpar caches antigos
      caches.keys().then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== CACHE_NAME && cacheName.startsWith("wasom-")) {
              console.log(` Removendo cache antigo: ${cacheName}`);
              return caches.delete(cacheName);
            }
          })
        );
      }),

      // Forçar clientes a usar o novo service worker
      self.clients.claim(),

      // Cachear recursos lazy em background
      cacheLazyResources(),
    ])
  );
});

// ========== FETCH - Intelligent Caching Strategy ==========

self.addEventListener("fetch", (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorar requisições não-GET
  if (request.method !== "GET") return;

  // Ignorar extensões do browser
  if (url.protocol === "chrome-extension:" || url.protocol === "chrome:") return;

  // Ignorar data URLs
  if (url.protocol === "data:") return;

  // Determinar estratégia baseada na URL
  const strategy = getStrategy(url, request);

  switch (strategy) {
    case "network-only":
      event.respondWith(networkOnly(request));
      break;

    case "cache-first":
      event.respondWith(cacheFirst(request));
      break;

    case "stale-while-revalidate":
      event.respondWith(staleWhileRevalidate(request));
      break;

    case "network-first":
    default:
      event.respondWith(networkFirst(request));
      break;
  }
});

// ========== STRATEGY DETECTION ==========

function getStrategy(url, request) {
  // Network only patterns (APIs, formulários)
  for (const pattern of NETWORK_ONLY) {
    if (pattern.test(url.pathname)) {
      return "network-only";
    }
  }

  // Recursos externos
  if (url.hostname !== self.location.hostname) {
    return "stale-while-revalidate";
  }

  // Navegação (páginas HTML)
  if (request.mode === "navigate") {
    return "network-first";
  }

  // Assets estáticos
  if (PATTERNS.images.test(url.pathname)) return "cache-first";
  if (PATTERNS.fonts.test(url.pathname)) return "cache-first";
  if (PATTERNS.js.test(url.pathname)) return "cache-first";
  if (PATTERNS.css.test(url.pathname)) return "stale-while-revalidate";

  // Padrão: network first
  return "network-first";
}

// ========== CACHING STRATEGIES IMPLEMENTATION ==========

// 1. NETWORK ONLY
async function networkOnly(request) {
  return fetch(request);
}

// 2. NETWORK FIRST (com fallback offline)
async function networkFirst(request) {
  try {
    const networkResponse = await fetch(request);

    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone()).catch((err) => {
        console.warn(" Não pude cachear resposta:", err);
      });
    }

    return networkResponse;
  } catch (error) {
    console.log(" Offline, buscando no cache:", request.url);

    const cachedResponse = await caches.match(request);
    if (cachedResponse) return cachedResponse;

    if (request.mode === "navigate") {
      return getOfflinePage();
    }

    return new Response("Offline", {
      status: 408,
      headers: { "Content-Type": "text/plain" },
    });
  }
}

// 3. CACHE FIRST (para assets estáticos)
async function cacheFirst(request) {
  const cachedResponse = await caches.match(request);
  if (cachedResponse) {
    updateCacheInBackground(request);
    return cachedResponse;
  }

  try {
    const networkResponse = await fetch(request);

    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone()).catch((err) => {
        console.warn(" Não pude cachear asset:", err);
      });
    }

    return networkResponse;
  } catch (error) {
    if (PATTERNS.images.test(request.url)) {
      return new Response(
        `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
          <rect width="100" height="100" fill="#181818"/>
          <text x="50" y="55" text-anchor="middle" fill="#FF0089" font-size="12">Offline</text>
        </svg>`,
        { headers: { "Content-Type": "image/svg+xml" } }
      );
    }

    return new Response("", { status: 404 });
  }
}

// 4. STALE WHILE REVALIDATE (para CSS e recursos externos)
async function staleWhileRevalidate(request) {
  const cache = await caches.open(CACHE_NAME);
  const cachedResponse = await cache.match(request);

  const fetchPromise = fetch(request)
    .then((networkResponse) => {
      if (networkResponse.ok) {
        cache.put(request, networkResponse.clone());
      }
      return networkResponse;
    })
    .catch(() => {});

  return cachedResponse || fetchPromise;
}

// ========== HELPER FUNCTIONS ==========

async function cacheLazyResources() {
  try {
    const cache = await caches.open(CACHE_NAME);

    const batchSize = 5;
    for (let i = 0; i < LAZY_CACHE.length; i += batchSize) {
      const batch = LAZY_CACHE.slice(i, i + batchSize);
      await Promise.allSettled(
        batch.map((url) =>
          cache.add(url).catch((err) => {
            console.log(` Não cacheado: ${url}`, err.message);
          })
        )
      );
    }

    console.log(" Cache lazy completo!");
  } catch (error) {
    console.error(" Erro no cache lazy:", error);
  }
}

async function updateCacheInBackground(request) {
  fetch(request)
    .then((response) => {
      if (response.ok) {
        caches
          .open(CACHE_NAME)
          .then((cache) => cache.put(request, response))
          .catch(() => {});
      }
    })
    .catch(() => {});
}

async function getOfflinePage() {
  const cache = await caches.open(CACHE_NAME);
  const offlinePage = await cache.match("/offline.html");

  if (offlinePage) return offlinePage;

  // Página offline embutida como fallback
  return new Response(
    `<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Offline | Wasom Upfy</title>
      <style>
        body {
          font-family: sans-serif;
          background: #181818;
          color: #f8fafc;
          display: flex;
          align-items: center;
          justify-content: center;
          min-height: 100vh;
          margin: 0;
          padding: 20px;
          text-align: center;
        }
        h1 { color: #FF0089; margin-bottom: 20px; }
        p { line-height: 1.6; color: #aaa; }
        .icon { font-size: 4rem; margin-bottom: 20px; }
      </style>
    </head>
    <body>
      <div>
        <div class="icon">📡</div>
        <h1>Você está offline</h1>
        <p>Conecte-se à internet para usar o Wasom Upfy.</p>
        <p>Algumas funcionalidades podem não estar disponíveis.</p>
      </div>
    </body>
    </html>`,
    {
      status: 200,
      headers: {
        "Content-Type": "text/html; charset=utf-8",
        "X-Offline-Mode": "true",
      },
    }
  );
}

// ========== MESSAGE HANDLING ==========

self.addEventListener("message", (event) => {
  const { type } = event.data || {};

  switch (type) {
    case "GET_VERSION":
      event.source.postMessage({
        type: "VERSION_INFO",
        version: APP_VERSION,
        cacheName: CACHE_NAME,
      });
      break;

    case "CLEAR_CACHE":
      caches
        .delete(CACHE_NAME)
        .then(() => {
          event.source.postMessage({ type: "CACHE_CLEARED", success: true });
        })
        .catch((error) => {
          event.source.postMessage({
            type: "CACHE_CLEARED",
            success: false,
            error: error.message,
          });
        });
      break;

    case "UPDATE_SW":
      self.skipWaiting();
      event.source.postMessage({ type: "SW_UPDATED", success: true });
      break;
  }
});

// ========== PUSH NOTIFICATIONS ==========

self.addEventListener("push", (event) => {
  let data = { title: "Wasom Upfy", body: "Nova notificação!" };
  if (event.data) {
    data = event.data.json();
  }

  const options = {
    body: data.body,
    icon: "/assets/img/icones/wasomupfy_fiv.png",
    badge: "/assets/img/icones/wasomupfy_fiv.png",
    vibrate: [200, 100, 200],
    data: { url: data.url || "/painel.html" },
    actions: [
      { action: "open", title: "Abrir" },
      { action: "close", title: "Fechar" },
    ],
  };

  event.waitUntil(self.registration.showNotification(data.title, options));
});

self.addEventListener("notificationclick", (event) => {
  event.notification.close();
  if (event.action !== "close") {
    event.waitUntil(clients.openWindow(event.notification.data.url));
  }
});

// ========== BACKGROUND SYNC ==========

self.addEventListener("sync", (event) => {
  if (event.tag === "sync-settings") {
    event.waitUntil(syncSettings());
  }
});

function syncSettings() {
  return new Promise((resolve) => {
    console.log(" Sincronizando configurações salvas offline...");
    // No futuro, envie dados para o backend aqui
    resolve();
  });
}

// ========== CONSOLE LOG ==========

console.log(` Service Worker Wasom Upfy v${APP_VERSION} carregado e pronto!`);
console.log(` Cache: ${CACHE_NAME}`);
console.log(` Host: ${self.location.hostname}`);