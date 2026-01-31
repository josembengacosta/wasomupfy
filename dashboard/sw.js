const CACHE_NAME = "wasom-upfy-cache-v2.0";
const urlsToCache = [
  "/",
  "/painel.html",
  "/settings.html",
  "/help.html",
  "/launch/releases.html",
  "/analytics/statistics.html",
  "/finances/overview.html",
  "/artists/artists-list.html",
  "/youtube.html",
  "/user/profile.html",
  "/notifications.html",
  "/css/bootstrap.min.css",
  "/css/bootstrap-icons.css",
  "/css/dashboard-style.css",
  "/js/bootstrap.bundle.min.js",
  "/assets/img/icones/wasomupfy_fiv.png",
  "/assets/img/icones/wasomupfy_fiv_512.png",
  "/assets/img/icones/wasomupfy_fiv_maskable.png",
  "/assets/img/screenshots/dashboard.png",
  "/assets/img/screenshots/settings.png",
  "/offline.html",
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(urlsToCache);
    })
  );
  self.skipWaiting(); // Ativar novo service worker imediatamente
});

self.addEventListener("activate", (event) => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (!cacheWhitelist.includes(cacheName)) {
            return caches.delete(cacheName); // Limpar caches antigos
          }
        })
      );
    })
  );
  self.clients.claim(); // Forçar clientes a usar o novo service worker
});

self.addEventListener("fetch", (event) => {
  const requestUrl = new URL(event.request.url);

  // Estratégia cache-first para recursos estáticos (HTML, CSS, JS, imagens)
  if (
    urlsToCache.some(
      (url) =>
        requestUrl.pathname === url || requestUrl.pathname.includes("/assets/")
    )
  ) {
    event.respondWith(
      caches.match(event.request).then((response) => {
        return (
          response ||
          fetch(event.request)
            .then((networkResponse) => {
              if (networkResponse && networkResponse.status === 200) {
                const responseToCache = networkResponse.clone();
                caches.open(CACHE_NAME).then((cache) => {
                  cache.put(event.request, responseToCache);
                });
              }
              return networkResponse;
            })
            .catch(() => {
              if (event.request.mode === "navigate") {
                return caches.match("/offline.html");
              }
            })
        );
      })
    );
  } else {
    // Estratégia network-first para recursos dinâmicos (futuras APIs)
    event.respondWith(
      fetch(event.request)
        .then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseToCache);
            });
          }
          return networkResponse;
        })
        .catch(() => {
          return caches.match(event.request);
        })
    );
  }
});

self.addEventListener("sync", (event) => {
  if (event.tag === "sync-settings") {
    event.waitUntil(syncSettings());
  }
});

function syncSettings() {
  // Simulação de sincronização para configurações salvas offline
  return new Promise((resolve) => {
    console.log("Sincronizando configurações salvas offline...");
    // No futuro, envie dados para o backend aqui
    resolve();
  });
}

self.addEventListener("push", (event) => {
  let data = { title: "Wasom Upfy", body: "Nova notificação!" };
  if (event.data) {
    data = event.data.json();
  }
  const options = {
    body: data.body,
    icon: "/assets/img/icones/wasomupfy_fiv.png",
    badge: "/assets/img/icones/wasomupfy_fiv.png",
    vibrate: [200, 100, 200], // Vibração em dispositivos móveis
    data: { url: data.url || "/painel.html" },
  };
  event.waitUntil(self.registration.showNotification(data.title, options));
});

self.addEventListener("notificationclick", (event) => {
  event.notification.close();
  event.waitUntil(clients.openWindow(event.notification.data.url));
});
