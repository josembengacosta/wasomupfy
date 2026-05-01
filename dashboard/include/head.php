<?php
// head.php — Wasom Upfy Dashboard
// Incluir dentro de <head>...</head> em todas as páginas do dashboard
// Uso: <?php include __DIR__ . '/../include/head.php'; ?>

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />

<!-- Ocultar do Google — painel privado -->
<meta name="robots" content="noindex, nofollow" />
<meta name="author" content="José Mbenga da Costa" />

<!-- Theme color — adapta ao tema claro/escuro -->
<meta name="theme-color" content="#FF0089" media="(prefers-color-scheme: dark)" />
<meta name="theme-color" content="#FF0089" media="(prefers-color-scheme: light)" />

<!-- CSRF para chamadas AJAX -->
<meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>" />

<!-- ── PWA ──────────────────────────────────────────── -->
<meta name="mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-title" content="Wasom Upfy" />
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
<meta name="application-name" content="Wasom Upfy" />
<meta name="msapplication-TileColor" content="#FF0089" />

<!-- Manifest — scope /dashboard/ -->
<link rel="manifest" href="<?php echo APP_URL . '/' . APP_URL_PANEL; ?>/manifest.json" crossorigin="use-credentials" />

<!-- Ícones -->
<link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/png" />
<link rel="apple-touch-icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv_512.png" />
<link rel="apple-touch-startup-image" href="<?php echo APP_URL; ?>/assets/img/screenshots/splash.png" />

<!-- ── Preconnect CDNs ───────────────────────────────── -->
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin />
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin />
<link rel="dns-prefetch" href="https://cdn.jsdelivr.net" />
<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com" />

<!-- ── CSS ──────────────────────────────────────────── -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?php echo APP_URL; ?>/css/dashboard-style.css" />
<link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<!-- Toastr -->
<script>
// ── Conexão offline ─────────────────────────────────
function checkConnection() {
    if (!navigator.onLine) {
        var toast = bootstrap.Toast.getOrCreateInstance(document.getElementById('connectionToast'));
        toast.show();
    }
}
checkConnection();
window.addEventListener('offline', checkConnection);
window.addEventListener('online', function() {
    var toastEl = document.getElementById('connectionToast');
    var toast = bootstrap.Toast.getInstance(toastEl);
    if (toast) toast.hide();
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Seleciona todos os links do offcanvas e bottom-nav que tenham a classe 'active'
    const activeLinks = document.querySelectorAll(
        '.offcanvas .nav-link.active, .bottom-nav .nav-link.active');
    activeLinks.forEach(link => {
        link.style.color = '#ff0089';
        link.style.fontWeight = '600';
    });
});
</script>
<!-- ── PWA ─────────────────────────────────── -->
<script src="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/js/pwa-dashboard.js" defer></script>

<!-- ── Estilos inline PWA standalone ────────────────── -->
<style>
/* Quando instalado como PWA: remove elementos só do browser */
@media (display-mode: standalone) {
    .hide-in-pwa {
        display: none !important;
    }

    body {
        padding-top: env(safe-area-inset-top);
    }
}

.offcanvas .nav-link.active {
    color: #ff0089 !important;
    font-weight: 600;
}

.bottom-nav .nav-link.active {
    color: #ff0089 !important;
}

.dropdown-item.active,
.dropdown-item:active {
    color: #FF0089 !important;
    background-color: rgba(255, 0, 137, 0.1) !important;
}

.user-dropdown {
    max-height: 80vh;
    overflow-y: auto;
}
</style>