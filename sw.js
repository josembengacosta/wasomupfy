// ══════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Service Worker
// Ficheiro: /wasomupfy/sw.js  (DEVE estar na RAIZ do projecto)
//
// Responsabilidades:
//   1. Cachear a página offline no install
//   2. Interceptar navegações falhadas (sem rede)
//   3. Redirecionar para status/offline.php?from=URL_ORIGINAL
//      para que o utilizador seja devolvido à página certa
//      quando a ligação regressar
// ══════════════════════════════════════════════════════════════════════

const SW_VERSION   = 'wu-v1';
const CACHE_NAME   = 'wu-offline-' + SW_VERSION;
const OFFLINE_PAGE = '/status/offline';

// Recursos a cachear no install — o mínimo necessário para a offline.php
// funcionar sem rede. Não adicionar CDNs (não são acessíveis offline).
const PRECACHE_URLS = [
    OFFLINE_PAGE,
];

// ── INSTALL — cachear a página offline ───────────────────────────────
self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function (cache) {
                return cache.addAll(PRECACHE_URLS);
            })
            .then(function () {
                // Activar imediatamente sem esperar que os tabs antigos fechem
                return self.skipWaiting();
            })
    );
});

// ── ACTIVATE — limpar caches antigas ─────────────────────────────────
self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys()
            .then(function (keys) {
                return Promise.all(
                    keys
                        .filter(function (key) { return key !== CACHE_NAME; })
                        .map(function (key) { return caches.delete(key); })
                );
            })
            .then(function () {
                // Controlar todos os tabs imediatamente após activação
                return self.clients.claim();
            })
    );
});

// ── FETCH — interceptar pedidos de navegação ─────────────────────────
self.addEventListener('fetch', function (event) {

    // Só interceptar navegações HTML (não CSS, JS, imagens, etc.)
    if (event.request.mode !== 'navigate') return;

    // Se o próprio pedido já é para a offline page → servir do cache
    // (evita loop infinito quando o browser tenta seguir o redirect)
    var requestUrl = event.request.url;
    if (requestUrl.indexOf(OFFLINE_PAGE) !== -1) {
        event.respondWith(
            caches.match(OFFLINE_PAGE).then(function (cached) {
                return cached || fetch(event.request);
            })
        );
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then(function (response) {
                // Resposta normal — devolver sem alterar
                return response;
            })
            .catch(function () {
                // ── Rede falhou → servir offline page com URL original ──
                // Usamos Response.redirect() para que o browser navegue para
                // offline.php?from=URL_ORIGINAL.
                // O SW intercepta esse segundo pedido e serve do cache.
                // A offline.php lê o ?from= e redireciona para lá quando
                // a ligação for restaurada.
                var from = encodeURIComponent(requestUrl);
                return Response.redirect(
                    OFFLINE_PAGE + '?from=' + from,
                    302
                );
            })
    );
});