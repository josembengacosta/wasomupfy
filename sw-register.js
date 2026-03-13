// ══════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Registo do Service Worker
// Ficheiro: /wasomupfy/js/sw-register.js
//
// Incluir em TODAS as páginas públicas antes de </body>:
//   <script src="js/sw-register.js"></script>          ← páginas raiz
//   <script src="../../js/sw-register.js"></script>    ← page/politicies/ etc.
//   <script src="../js/sw-register.js"></script>       ← status/
//
// O SW deve estar na raiz do projecto (/wasomupfy/sw.js)
// para ter scope sobre todo o /wasomupfy/.
// ══════════════════════════════════════════════════════════════════════

(function () {
    'use strict';

    // Service Workers requerem HTTPS ou localhost
    if (!('serviceWorker' in navigator)) return;

    window.addEventListener('load', function () {
        navigator.serviceWorker
            .register('/wasomupfy/sw.js', { scope: '/wasomupfy/' })
            .then(function (reg) {
                // Verificar se há uma versão nova disponível
                reg.addEventListener('updatefound', function () {
                    var newWorker = reg.installing;
                    if (!newWorker) return;

                    newWorker.addEventListener('statechange', function () {
                        // Nova versão instalada e pronta — activar na próxima navegação
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            console.info('[SW] Nova versão disponível — activa no próximo reload.');
                        }
                    });
                });
            })
            .catch(function (err) {
                // Falha no registo não deve quebrar a página
                console.warn('[SW] Registo falhou:', err);
            });
    });
})();