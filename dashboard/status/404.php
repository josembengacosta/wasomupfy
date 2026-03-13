<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Página de Erro 404
// Arquivo: dashboard/status/404.php
// Nota: NÃO requer login — página de erro acessível a todos
// ══════════════════════════════════════════════════════
http_response_code(404);

// Tenta carregar funções base sem exigir login
$platform = null;
$is_logged_in = false;

try {
    require_once __DIR__ . '../../../authentic/include/functions.php';
    startSecureSession();
    $is_logged_in = isset($_SESSION['id_users']);

    // Lê configurações da plataforma
    $db   = getDB();
    $stmt = $db->query("SELECT version, contact_email, stores_count FROM _platform LIMIT 1");
    $platform = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
} catch (Throwable $e) {
    // Falha silenciosa — página de erro nunca pode quebrar
}

$version       = htmlspecialchars($platform['version']       ?? '2.0');
$contact_email = htmlspecialchars($platform['contact_email'] ?? 'suporte@wasomupfy.com');
$back_url      = $is_logged_in ? '../painel' : '../../';
$back_label    = $is_logged_in ? 'Ir para o Dashboard' : 'Ir para a Página Inicial';
?>
<!DOCTYPE html>
<html lang="pt-ao" data-theme="auto">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <title>404 — Página Não Encontrada · Wasom Upfy</title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800;900&family=DM+Sans:wght@300;400;500&display=swap"
        rel="stylesheet" />
    <style>
    /* ══ CSS Variables ══ */
    :root {
        --bg: #0a0a0f;
        --surface: #111118;
        --border: rgba(255, 255, 255, .07);
        --text: #e8e8f0;
        --muted: rgba(232, 232, 240, .45);
        --accent: #FF0089;
        --accent2: #ff4dab;
        --glow: rgba(255, 0, 137, .18);
    }

    [data-theme="light"] {
        --bg: #f5f4f9;
        --surface: #ffffff;
        --border: rgba(0, 0, 0, .08);
        --text: #1a1a2e;
        --muted: rgba(26, 26, 46, .45);
        --glow: rgba(255, 0, 137, .1);
    }

    /* ══ Reset & Base ══ */
    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    html,
    body {
        height: 100%;
    }

    body {
        font-family: 'DM Sans', sans-serif;
        background: var(--bg);
        color: var(--text);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        overflow-x: hidden;
        transition: background .3s, color .3s;
    }

    /* ══ Background decoration ══ */
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        background:
            radial-gradient(ellipse 700px 500px at 15% 10%, rgba(255, 0, 137, .08) 0%, transparent 70%),
            radial-gradient(ellipse 500px 400px at 85% 80%, rgba(255, 0, 137, .05) 0%, transparent 70%);
    }

    /* ══ Grid dots ══ */
    body::after {
        content: '';
        position: fixed;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        background-image: radial-gradient(circle, rgba(255, 0, 137, .12) 1px, transparent 1px);
        background-size: 32px 32px;
        opacity: .4;
    }

    [data-theme="light"] body::after {
        opacity: .15;
    }

    /* ══ Navbar ══ */
    .status-nav {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 100;
        padding: .9rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(10, 10, 15, .85);
        backdrop-filter: blur(18px);
        border-bottom: 1px solid var(--border);
        transition: background .3s;
    }

    [data-theme="light"] .status-nav {
        background: rgba(245, 244, 249, .88);
    }

    .nav-brand {
        font-family: 'Syne', sans-serif;
        font-weight: 900;
        font-size: 1.15rem;
        color: var(--text);
        text-decoration: none;
        letter-spacing: .5px;
    }

    .nav-brand span {
        color: var(--accent);
    }

    .nav-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .version-pill {
        font-family: 'Syne', sans-serif;
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: 1.5px;
        background: rgba(255, 0, 137, .12);
        color: var(--accent);
        border: 1px solid rgba(255, 0, 137, .25);
        border-radius: 999px;
        padding: 3px 10px;
    }

    .theme-btn {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: var(--surface);
        border: 1px solid var(--border);
        color: var(--text);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
        cursor: pointer;
        transition: all .2s;
    }

    .theme-btn:hover {
        border-color: var(--accent);
        color: var(--accent);
    }

    /* ══ Main layout ══ */
    main {
        flex: 1;
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 100px 2rem 60px;
    }

    .error-wrap {
        max-width: 700px;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 0;
    }

    /* ══ Giant 404 ══ */
    .error-code {
        font-family: 'Syne', sans-serif;
        font-size: clamp(7rem, 22vw, 14rem);
        font-weight: 900;
        line-height: .9;
        letter-spacing: -4px;
        background: linear-gradient(135deg, #FF0089 0%, #ff4dab 40%, rgba(255, 0, 137, .25) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        filter: drop-shadow(0 0 60px var(--glow));
        animation: pulse-glow 3s ease-in-out infinite;
        user-select: none;
    }

    @keyframes pulse-glow {

        0%,
        100% {
            filter: drop-shadow(0 0 40px var(--glow));
        }

        50% {
            filter: drop-shadow(0 0 80px rgba(255, 0, 137, .35));
        }
    }

    /* ══ Vinyl record SVG ══ */
    .vinyl-wrap {
        position: relative;
        margin: -1.5rem 0 1.5rem;
        animation: spin-slow 12s linear infinite;
    }

    .vinyl-wrap:hover {
        animation-play-state: paused;
    }

    @keyframes spin-slow {
        to {
            transform: rotate(360deg);
        }
    }

    .vinyl-svg {
        width: 90px;
        height: 90px;
    }

    /* ══ Content ══ */
    .error-title {
        font-family: 'Syne', sans-serif;
        font-size: clamp(1.4rem, 3vw, 2rem);
        font-weight: 800;
        margin-bottom: .7rem;
        color: var(--text);
    }

    .error-desc {
        font-size: .95rem;
        line-height: 1.8;
        color: var(--muted);
        max-width: 460px;
        margin-bottom: 2.2rem;
    }

    .error-desc a {
        color: var(--accent);
        text-decoration: none;
    }

    .error-desc a:hover {
        text-decoration: underline;
    }

    /* ══ Action buttons ══ */
    .action-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: center;
        margin-bottom: 2.5rem;
    }

    .btn-primary-custom {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: var(--accent);
        color: #fff;
        padding: .65rem 1.6rem;
        border-radius: 12px;
        font-family: 'Syne', sans-serif;
        font-weight: 700;
        font-size: .88rem;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all .2s;
        box-shadow: 0 4px 24px var(--glow);
    }

    .btn-primary-custom:hover {
        background: var(--accent2);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 32px rgba(255, 0, 137, .35);
    }

    .btn-secondary-custom {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: transparent;
        color: var(--text);
        padding: .65rem 1.6rem;
        border-radius: 12px;
        font-family: 'Syne', sans-serif;
        font-weight: 700;
        font-size: .88rem;
        text-decoration: none;
        border: 1.5px solid var(--border);
        cursor: pointer;
        transition: all .2s;
    }

    .btn-secondary-custom:hover {
        border-color: var(--accent);
        color: var(--accent);
        transform: translateY(-2px);
    }

    /* ══ Quick links ══ */
    .quick-links {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .quick-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: .78rem;
        font-weight: 500;
        color: var(--muted);
        text-decoration: none;
        padding: .35rem .9rem;
        border-radius: 999px;
        border: 1px solid var(--border);
        transition: all .2s;
    }

    .quick-link:hover {
        color: var(--accent);
        border-color: rgba(255, 0, 137, .3);
        background: rgba(255, 0, 137, .06);
    }

    .quick-link i {
        font-size: .75rem;
    }

    /* ══ Divider ══ */
    .error-divider {
        width: 100%;
        max-width: 460px;
        border: none;
        border-top: 1px solid var(--border);
        margin: 2rem 0 1.5rem;
    }

    .divider-label {
        font-size: .72rem;
        color: var(--muted);
        font-weight: 500;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }

    /* ══ Footer bar ══ */
    .status-footer {
        position: relative;
        z-index: 1;
        padding: 1.2rem 2rem;
        border-top: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .5rem;
        font-size: .72rem;
        color: var(--muted);
    }

    .status-footer a {
        color: var(--muted);
        text-decoration: none;
        transition: color .2s;
    }

    .status-footer a:hover {
        color: var(--accent);
    }

    .footer-right {
        display: flex;
        gap: 1.2rem;
    }

    /* ══ Error code badge ══ */
    .http-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 0, 137, .1);
        color: var(--accent);
        border: 1px solid rgba(255, 0, 137, .2);
        border-radius: 8px;
        padding: .3rem .9rem;
        font-family: 'Syne', sans-serif;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }

    /* ══ Staggered entrance ══ */
    .fade-in {
        opacity: 0;
        animation: fade-up .55s ease forwards;
    }

    .fade-in:nth-child(1) {
        animation-delay: .05s;
    }

    .fade-in:nth-child(2) {
        animation-delay: .15s;
    }

    .fade-in:nth-child(3) {
        animation-delay: .22s;
    }

    .fade-in:nth-child(4) {
        animation-delay: .30s;
    }

    .fade-in:nth-child(5) {
        animation-delay: .38s;
    }

    .fade-in:nth-child(6) {
        animation-delay: .45s;
    }

    .fade-in:nth-child(7) {
        animation-delay: .52s;
    }

    @keyframes fade-up {
        from {
            opacity: 0;
            transform: translateY(18px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ══ Responsive ══ */
    @media (max-width: 576px) {
        .status-nav {
            padding: .8rem 1.2rem;
        }

        main {
            padding: 90px 1.2rem 50px;
        }

        .action-row {
            flex-direction: column;
            align-items: center;
        }

        .btn-primary-custom,
        .btn-secondary-custom {
            width: 100%;
            justify-content: center;
        }

        .status-footer {
            justify-content: center;
            text-align: center;
        }

        .footer-right {
            justify-content: center;
        }
    }
    </style>
</head>

<body>

    <!-- ═══ NAVBAR ═══ -->
    <nav class="status-nav">
        <a class="nav-brand" href="<?php echo $back_url; ?>">
            WASOM <span>UPFY</span>
        </a>
        <div class="nav-right">
            <span class="version-pill">v<?php echo $version; ?></span>
            <button class="theme-btn" id="themeBtn" title="Alternar tema" aria-label="Alternar tema">
                <i class="bi bi-sun" id="themeIcon"></i>
            </button>
        </div>
    </nav>

    <!-- ═══ MAIN ═══ -->
    <main>
        <div class="error-wrap">

            <div class="fade-in error-code">404</div>

            <!-- Vinil animado -->
            <div class="fade-in vinyl-wrap" aria-hidden="true">
                <svg class="vinyl-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <!-- Outer ring -->
                    <circle cx="50" cy="50" r="46" fill="#1a1a1a" stroke="rgba(255,0,137,.3)" stroke-width="1.5" />
                    <!-- Groove rings -->
                    <circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,.05)" stroke-width="1" />
                    <circle cx="50" cy="50" r="34" fill="none" stroke="rgba(255,255,255,.05)" stroke-width="1" />
                    <circle cx="50" cy="50" r="28" fill="none" stroke="rgba(255,255,255,.05)" stroke-width="1" />
                    <circle cx="50" cy="50" r="22" fill="none" stroke="rgba(255,255,255,.05)" stroke-width="1" />
                    <!-- Label -->
                    <circle cx="50" cy="50" r="16" fill="#FF0089" opacity=".9" />
                    <!-- Center hole -->
                    <circle cx="50" cy="50" r="4" fill="#0a0a0f" />
                    <!-- Highlight arc -->
                    <path d="M 20 38 Q 24 20 42 17" stroke="rgba(255,255,255,.15)" stroke-width="3" fill="none"
                        stroke-linecap="round" />
                </svg>
            </div>

            <div class="fade-in http-badge">
                <i class="bi bi-exclamation-circle-fill"></i>
                HTTP 404 · Não Encontrado
            </div>

            <h1 class="fade-in error-title">Página não encontrada</h1>

            <p class="fade-in error-desc">
                A página que procuras não existe, foi movida ou o endereço foi digitado
                incorrectamente. Verifica o URL ou usa uma das opções abaixo para
                continuar.<br><br>
                Se acreditas que isto é um erro, contacta o
                <a href="../page/support">suporte</a>.
            </p>

            <div class="fade-in action-row">
                <a href="<?php echo $back_url; ?>" class="btn-primary-custom">
                    <i class="bi bi-house-fill"></i>
                    <?php echo $back_label; ?>
                </a>
                <button class="btn-secondary-custom" onclick="history.back()">
                    <i class="bi bi-arrow-left"></i>
                    Voltar atrás
                </button>
            </div>

            <hr class="error-divider fade-in">
            <p class="divider-label fade-in">Ou vai directamente para</p>

            <div class="fade-in quick-links">
                <?php if ($is_logged_in): ?>
                <a class="quick-link" href="../painel"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="quick-link" href="../launch/releases"><i class="bi bi-disc"></i> Lançamentos</a>
                <a class="quick-link" href="../analytics/statistics"><i class="bi bi-bar-chart"></i> Estatísticas</a>
                <a class="quick-link" href="../finances/overview"><i class="bi bi-currency-dollar"></i> Finanças</a>
                <a class="quick-link" href="../page/support"><i class="bi bi-headset"></i> Suporte</a>
                <a class="quick-link" href="../page/faq"><i class="bi bi-chat-left-text"></i> FAQ</a>
                <?php else: ?>
                <a class="quick-link" href="../../"><i class="bi bi-house"></i> Início</a>
                <a class="quick-link" href="../../authentic/login"><i class="bi bi-box-arrow-in-right"></i> Entrar</a>
                <a class="quick-link" href="../../authentic/register"><i class="bi bi-person-plus"></i> Registar</a>
                <a class="quick-link" href="mailto:<?php echo $contact_email; ?>"><i class="bi bi-envelope"></i>
                    Contacto</a>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <!-- ═══ FOOTER ═══ -->
    <footer class="status-footer">
        <span>© <?php echo date('Y'); ?> Wasom Upfy · Todos os direitos reservados</span>
        <div class="footer-right">
            <a href="../page/terms">Termos de Uso</a>
            <a href="../page/privacy">Privacidade</a>
            <a href="mailto:<?php echo $contact_email; ?>"><?php echo $contact_email; ?></a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {

        // ── Theme system auto-contido (sem depender de theme.wp.js) ──
        var html = document.documentElement;
        var themeBtn = document.getElementById('themeBtn');
        var themeIcon = document.getElementById('themeIcon');
        var THEMES = ['auto', 'dark', 'light'];
        var ICONS = {
            auto: 'bi-circle-half',
            dark: 'bi-moon-stars-fill',
            light: 'bi-sun-fill'
        };

        function getSystemTheme() {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        function applyTheme(theme) {
            var resolved = theme === 'auto' ? getSystemTheme() : theme;
            html.setAttribute('data-theme', resolved);
            if (themeIcon) {
                themeIcon.className = 'bi ' + ICONS[theme];
            }
            try {
                localStorage.setItem('wu_theme', theme);
            } catch (e) {}
        }

        function cycleTheme() {
            var current = (function() {
                try {
                    return localStorage.getItem('wu_theme') || 'auto';
                } catch (e) {
                    return 'auto';
                }
            }());
            var next = THEMES[(THEMES.indexOf(current) + 1) % THEMES.length];
            applyTheme(next);
        }

        if (themeBtn) themeBtn.addEventListener('click', cycleTheme);

        // Sistema de media query
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
            try {
                if ((localStorage.getItem('wu_theme') || 'auto') === 'auto') applyTheme('auto');
            } catch (e) {}
        });

        // Inicializa
        (function() {
            try {
                applyTheme(localStorage.getItem('wu_theme') || 'auto');
            } catch (e) {
                applyTheme('auto');
            }
        }());

    });
    </script>
</body>

</html>