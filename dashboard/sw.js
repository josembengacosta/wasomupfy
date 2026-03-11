// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Service Worker
// Arquivo: sw-wasomupfy.js  (na raiz do domínio)
// ══════════════════════════════════════════════════════

const SW_VERSION   = '2.0.1';
const CACHE_STATIC = `wasom-static-v${SW_VERSION}`;
const CACHE_PAGES  = `wasom-pages-v${SW_VERSION}`;

// ── Recursos a fazer cache imediato ───────────────────
const STATIC_ASSETS = [
    '/dashboard/assets/img/icones/wasomupfy_fiv.png',
    '/dashboard/assets/img/icones/wasomupfy_fiv_512.png',
    '/dashboard/css/dashboard-style.css',
    '/dashboard/css/lastest-style.css',
    '/dashboard/js/theme.wp.js',
    '/dashboard/js/wp.tools.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
];

// ── Rotas de páginas a fazer cache (network-first) ────
const CACHEABLE_PAGES = [
    '/dashboard/painel',
    '/dashboard/page/notifications',
    '/dashboard/page/faq',
    '/dashboard/page/help',
    '/dashboard/page/settings',
];

// ══════════════════════════════════════════════════════
// INSTALL — cache estático
// ══════════════════════════════════════════════════════
self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_STATIC).then(function (cache) {
            return cache.addAll(STATIC_ASSETS).catch(function (err) {
                console.warn('[SW] Alguns assets não foram cacheados:', err);
            });
        }).then(function () {
            return self.skipWaiting();
        })
    );
});

// ══════════════════════════════════════════════════════
// ACTIVATE — limpa caches antigos
// ══════════════════════════════════════════════════════
self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (key) {
                    return key !== CACHE_STATIC && key !== CACHE_PAGES;
                }).map(function (key) {
                    return caches.delete(key);
                })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

// ══════════════════════════════════════════════════════
// FETCH — estratégias de cache
// ══════════════════════════════════════════════════════
self.addEventListener('fetch', function (event) {
    var req = event.request;
    var url = new URL(req.url);

    // Só trata GET
    if (req.method !== 'GET') return;

    // Ignora pedidos AJAX/API
    if (url.pathname.includes('/ajax/')) return;

    // ── Recursos estáticos: Cache First ──────────────
    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(req));
        return;
    }

    // ── Páginas do dashboard: Network First ──────────
    if (isCacheablePage(url)) {
        event.respondWith(networkFirst(req));
        return;
    }

    // ── Resto: Network com fallback ───────────────────
    event.respondWith(networkWithFallback(req));
});

function isStaticAsset(url) {
    return /\.(css|js|png|jpg|jpeg|ico|webp|woff2?|svg)(\?.*)?$/.test(url.pathname)
        || url.hostname === 'cdn.jsdelivr.net';
}

function isCacheablePage(url) {
    return CACHEABLE_PAGES.some(function (p) { return url.pathname.startsWith(p); });
}

// Cache First (estáticos)
async function cacheFirst(req) {
    var cached = await caches.match(req);
    if (cached) return cached;
    try {
        var res = await fetch(req);
        if (res && res.status === 200) {
            var cache = await caches.open(CACHE_STATIC);
            cache.put(req, res.clone());
        }
        return res;
    } catch (e) {
        return new Response('Offline', { status: 503 });
    }
}

// Network First (páginas)
async function networkFirst(req) {
    try {
        var res = await fetch(req);
        if (res && res.status === 200) {
            var cache = await caches.open(CACHE_PAGES);
            cache.put(req, res.clone());
        }
        return res;
    } catch (e) {
        var cached = await caches.match(req);
        if (cached) return cached;
        return offlinePage();
    }
}

// Network com fallback para cache
async function networkWithFallback(req) {
    try {
        return await fetch(req);
    } catch (e) {
        var cached = await caches.match(req);
        return cached || offlinePage();
    }
}

function offlinePage() {
    return new Response(
        '<!DOCTYPE html><html lang="pt-ao"><head><meta charset="utf-8"><title>Offline — Wasom Upfy</title>' +
        '<meta name="viewport" content="width=device-width,initial-scale=1">' +
        '<style>body{font-family:Arial,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#0f0f0f;color:#fff}' +
        '.box{text-align:center;padding:2rem}.logo{font-size:1.5rem;font-weight:900;color:#FF0089;margin-bottom:1.5rem}' +
        '.icon{font-size:4rem;margin-bottom:1rem}h1{font-size:1.4rem;margin-bottom:.5rem}p{color:#aaa;font-size:.9rem}' +
        '.btn{display:inline-block;margin-top:1.5rem;padding:.6rem 1.8rem;background:#FF0089;color:#fff;border-radius:10px;text-decoration:none;font-weight:700}</style></head>' +
        '<body><div class="box"><div class="logo">WASOM UPFY</div>' +
        '<div class="icon">📡</div>' +
        '<h1>Sem ligação à internet</h1>' +
        '<p>Verifica a tua conexão e tenta novamente.</p>' +
        '<a href="javascript:location.reload()" class="btn">Tentar novamente</a></div></body></html>',
        { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
}

// ══════════════════════════════════════════════════════
// PUSH — receber notificações do servidor
// ══════════════════════════════════════════════════════
self.addEventListener('push', function (event) {
    var data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { title: 'Wasom Upfy', body: event.data ? event.data.text() : 'Nova notificação.' };
    }

    var title   = data.title   || 'Wasom Upfy';
    var body    = data.body    || 'Tens uma nova notificação.';
    var type    = data.type    || 'info';
    var url     = data.url     || '/dashboard/page/notifications';
    var tag     = data.tag     || ('notif-' + Date.now());

    // Ícone e badge por tipo
    var iconMap = {
        payment  : '/dashboard/assets/img/notif/payment.png',
        music    : '/dashboard/assets/img/notif/music.png',
        system   : '/dashboard/assets/img/notif/system.png',
        warning  : '/dashboard/assets/img/notif/warning.png',
        error    : '/dashboard/assets/img/notif/error.png',
        broadcast: '/dashboard/assets/img/notif/broadcast.png',
    };
    var icon   = iconMap[type] || '/dashboard/assets/img/icones/wasomupfy_fiv_512.png';
    var badge  = '/dashboard/assets/img/icones/wasomupfy_fiv.png';

    // Acções dependendo do tipo
    var actions = buildActions(type, data);

    var options = {
        body          : body,
        icon          : icon,
        badge         : badge,
        tag           : tag,
        renotify      : true,
        requireInteraction: type === 'payment' || type === 'error',
        vibrate       : [200, 100, 200],
        data          : { url: url, type: type, id: data.id || null },
        actions       : actions,
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// ── Construir acções do push por tipo ─────────────────
function buildActions(type, data) {
    switch (type) {
        case 'payment':
            return [
                { action: 'view',  title: '💰 Ver pagamento',   icon: '' },
                { action: 'later', title: '⏰ Ver mais tarde',   icon: '' },
            ];
        case 'music':
            return [
                { action: 'view',    title: '🎵 Ver lançamento', icon: '' },
                { action: 'dismiss', title: 'Dispensar',         icon: '' },
            ];
        case 'error':
            return [
                { action: 'view',    title: '⚠️ Ver detalhes',   icon: '' },
                { action: 'support', title: '🎧 Contactar suporte', icon: '' },
            ];
        case 'broadcast':
            return [
                { action: 'view',    title: '📢 Ler comunicado', icon: '' },
                { action: 'dismiss', title: 'Dispensar',         icon: '' },
            ];
        default:
            return [
                { action: 'view',    title: '👀 Ver notificação', icon: '' },
                { action: 'dismiss', title: 'Dispensar',          icon: '' },
            ];
    }
}

// ══════════════════════════════════════════════════════
// NOTIFICATION CLICK — tratar cliques nas acções
// ══════════════════════════════════════════════════════
self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    var action = event.action;
    var data   = event.notification.data || {};
    var type   = data.type  || 'info';

    // Destino dependendo da acção
    var dest = '/dashboard/page/notifications';

    if (action === 'view' || action === '') {
        // Determinar destino pelo tipo de notificação
        switch (type) {
            case 'payment':  dest = '/dashboard/finances/overview';    break;
            case 'music':    dest = '/dashboard/launch/releases';       break;
            case 'error':    dest = '/dashboard/page/support';          break;
            case 'broadcast':dest = '/dashboard/page/notifications';   break;
            default:         dest = data.url || '/dashboard/page/notifications';
        }
    } else if (action === 'support') {
        dest = '/dashboard/page/support';
    } else if (action === 'later') {
        // "Ver mais tarde" — apenas fecha a notificação, não redireciona
        return;
    } else if (action === 'dismiss') {
        return;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            // Se já há uma janela aberta com o destino, foca-a
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if (client.url.includes(dest) && 'focus' in client) {
                    return client.focus();
                }
            }
            // Caso contrário, abre nova aba
            if (clients.openWindow) {
                return clients.openWindow(dest);
            }
        })
    );
});

// ══════════════════════════════════════════════════════
// NOTIFICATION CLOSE — rastrear dispensar
// ══════════════════════════════════════════════════════
self.addEventListener('notificationclose', function (event) {
    // Opcional: poderia enviar analytics de que o user dispensou
    var data = event.notification.data || {};
    console.log('[SW] Notificação dispensada:', data.type, data.id);
});

// ══════════════════════════════════════════════════════
// MESSAGE — comunicação com a página (badge update)
// ══════════════════════════════════════════════════════
self.addEventListener('message', function (event) {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    if (event.data && event.data.type === 'GET_VERSION') {
        event.ports[0].postMessage({ version: SW_VERSION });
    }
});

// ══════════════════════════════════════════════════════
// BACKGROUND SYNC — tentar reenviar acções offline
// ══════════════════════════════════════════════════════
self.addEventListener('sync', function (event) {
    if (event.tag === 'sync-mark-read') {
        event.waitUntil(syncPendingReads());
    }
    if (event.tag === 'sync-prefs') {
        event.waitUntil(syncPendingPrefs());
    }
});

async function syncPendingReads() {
    // Ler da IndexedDB (implementar no cliente) e enviar para o servidor
    // Placeholder — a implementação completa usa idb-keyval ou similar
    console.log('[SW] Background sync: mark-read');
}

async function syncPendingPrefs() {
    console.log('[SW] Background sync: prefs');
}