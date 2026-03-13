<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Página Unauthorized (401)
// Arquivo: dashboard/status/unauthorized.php
// Contexto: sessão expirada, token inválido, acesso sem
// autenticação. Diferente do 403 (autenticado mas sem
// permissão) — aqui o utilizador simplesmente não está
// identificado ou a sua sessão expirou.
// ══════════════════════════════════════════════════════
http_response_code(401);
header('WWW-Authenticate: FormBased realm="Wasom Upfy Dashboard"');

$platform_ver  = '2.0';
$contact_email = 'suporte@wasomupfy.com';
$is_logged_in  = false;

try {
    require_once __DIR__ . '../../../authentic/include/functions.php';
    startSecureSession();
    // NÃO chamamos requireLogin() — esta página é o destino do redirect
    $is_logged_in = isset($_SESSION['id_users']);

    $db = getDB();
    $pq = $db->query("SELECT version, contact_email FROM _platform WHERE id_platform = 1 LIMIT 1");
    if ($pq) {
        $row = $pq->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $platform_ver  = htmlspecialchars($row['version']       ?? '2.0');
            $contact_email = htmlspecialchars($row['contact_email'] ?? 'suporte@wasomupfy.com');
        }
    }
} catch (Throwable $e) {
    error_log('[unauthorized page] ' . $e->getMessage());
}

// Se por algum motivo já está logado, mandar para o painel
if ($is_logged_in) {
    header('Location: ../painel');
    exit;
}

// Preservar URL de origem para redirect pós-login
$redirect_after = isset($_GET['from'])
    ? urlencode(htmlspecialchars(strip_tags($_GET['from'])))
    : '';
$login_url = '../../login' . ($redirect_after ? '?redirect=' . $redirect_after : '');
?>
<!DOCTYPE html>
<html lang="pt-ao" data-theme="dark">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="Wasom Upfy" />
    <title>Sessão Expirada — Wasom Upfy</title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800;900&family=DM+Sans:wght@300;400;500&display=swap"
        rel="stylesheet" />
    <style>
    /* ══ Variables ══ */
    :root {
        --bg: #0a0a0f;
        --surface: rgba(255, 255, 255, .04);
        --border: rgba(255, 255, 255, .08);
        --text: #e8e8f0;
        --muted: rgba(232, 232, 240, .45);
        --accent: #FF0089;
        --accent-d: #c8006e;
        --glow: rgba(255, 0, 137, .22);
        --blue: #6366f1;
        --blue-d: #4338ca;
        --blue-glow: rgba(99, 102, 241, .22);
    }

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
    }

    /* Grid dots */
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        z-index: 0;
        background-image: radial-gradient(circle, rgba(255, 255, 255, .06) 1px, transparent 1px);
        background-size: 32px 32px;
        pointer-events: none;
    }

    /* Radial glow — índigo */
    body::after {
        content: '';
        position: fixed;
        top: -20%;
        left: 50%;
        transform: translateX(-50%);
        width: 700px;
        height: 500px;
        background: radial-gradient(ellipse, rgba(99, 102, 241, .12) 0%, transparent 70%);
        z-index: 0;
        pointer-events: none;
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
        background: rgba(10, 10, 15, .9);
        backdrop-filter: blur(18px);
        border-bottom: 1px solid var(--border);
    }

    .nav-brand {
        font-family: 'Syne', sans-serif;
        font-weight: 900;
        font-size: 1.1rem;
        color: var(--text);
        text-decoration: none;
        letter-spacing: .5px;
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .nav-brand .brand-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--accent);
        box-shadow: 0 0 10px var(--accent);
        animation: pulseDot 2s ease-in-out infinite;
    }

    @keyframes pulseDot {

        0%,
        100% {
            box-shadow: 0 0 6px var(--accent);
        }

        50% {
            box-shadow: 0 0 18px var(--accent), 0 0 32px var(--glow);
        }
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

    .http-badge {
        font-family: 'Syne', sans-serif;
        font-size: .65rem;
        font-weight: 800;
        letter-spacing: 1.5px;
        background: rgba(99, 102, 241, .12);
        color: var(--blue);
        border: 1px solid rgba(99, 102, 241, .3);
        border-radius: 999px;
        padding: 3px 10px;
        text-transform: uppercase;
    }

    @media(max-width:576px) {
        .status-nav {
            padding: .8rem 1rem;
        }
    }

    /* ══ Page layout ══ */
    .page-wrap {
        position: relative;
        z-index: 1;
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 100px 1.2rem 80px;
        text-align: center;
    }

    /* ══ Icon ══ */
    .icon-wrap {
        margin-bottom: 1.5rem;
    }

    .session-svg {
        width: 72px;
        height: 72px;
        animation: sessionFloat 5s ease-in-out infinite;
    }

    @keyframes sessionFloat {

        0%,
        100% {
            transform: translateY(0) rotate(0deg);
        }

        30% {
            transform: translateY(-7px) rotate(-3deg);
        }

        65% {
            transform: translateY(-3px) rotate(2deg);
        }
    }

    /* ══ Error code ══ */
    .error-code {
        font-family: 'Syne', sans-serif;
        font-size: clamp(7rem, 20vw, 14rem);
        font-weight: 900;
        line-height: 1;
        background: linear-gradient(135deg, #6366f1 0%, #FF0089 60%, #a855f7 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: .2rem;
        animation: pulseGlow 4s ease-in-out infinite;
        filter: drop-shadow(0 0 40px rgba(99, 102, 241, .3));
    }

    @keyframes pulseGlow {

        0%,
        100% {
            filter: drop-shadow(0 0 30px rgba(99, 102, 241, .2));
        }

        50% {
            filter: drop-shadow(0 0 60px rgba(99, 102, 241, .45));
        }
    }

    /* ══ Text ══ */
    .error-title {
        font-family: 'Syne', sans-serif;
        font-size: clamp(1.4rem, 4vw, 2rem);
        font-weight: 900;
        color: var(--text);
        margin-bottom: .6rem;
        line-height: 1.2;
    }

    .error-title span {
        color: var(--blue);
    }

    .error-desc {
        font-size: .95rem;
        color: var(--muted);
        line-height: 1.8;
        max-width: 460px;
        margin: 0 auto 1.8rem;
    }

    /* ══ Info box ══ */
    .info-box {
        display: inline-flex;
        align-items: flex-start;
        gap: 10px;
        background: rgba(99, 102, 241, .08);
        border: 1px solid rgba(99, 102, 241, .22);
        border-radius: 14px;
        padding: .85rem 1.2rem;
        font-size: .82rem;
        color: rgba(232, 232, 240, .7);
        max-width: 460px;
        width: 100%;
        margin: 0 auto 2rem;
        text-align: left;
    }

    .info-box i {
        color: var(--blue);
        font-size: 1rem;
        flex-shrink: 0;
        margin-top: 1px;
    }

    /* ══ CTA Login ══ */
    .btn-login-cta {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: linear-gradient(135deg, var(--blue) 0%, var(--accent) 100%);
        border: none;
        color: #fff;
        border-radius: 14px;
        padding: .9rem 2.2rem;
        font-family: 'Syne', sans-serif;
        font-size: .95rem;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        transition: all .25s;
        box-shadow: 0 4px 20px rgba(99, 102, 241, .3);
        letter-spacing: .3px;
        margin-bottom: 1rem;
    }

    .btn-login-cta:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 32px rgba(99, 102, 241, .45);
        color: #fff;
    }

    .btn-login-cta i {
        font-size: 1.15rem;
    }

    /* ══ Secondary actions ══ */
    .action-group {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 2.5rem;
    }

    .btn-secondary-action {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, .06);
        border: 1.5px solid var(--border);
        color: var(--text);
        border-radius: 12px;
        padding: .72rem 1.4rem;
        font-family: 'Syne', sans-serif;
        font-size: .85rem;
        font-weight: 700;
        text-decoration: none;
        transition: all .2s;
    }

    .btn-secondary-action:hover {
        background: rgba(99, 102, 241, .08);
        border-color: rgba(99, 102, 241, .4);
        color: var(--blue);
        transform: translateY(-2px);
    }

    /* ══ Auto-redirect notice ══ */
    .redirect-notice {
        font-size: .74rem;
        color: rgba(255, 255, 255, .25);
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    #redirectCountdown {
        font-family: 'Syne', sans-serif;
        font-weight: 900;
        color: rgba(99, 102, 241, .7);
    }

    /* ══ Divider ══ */
    .ql-divider {
        font-family: 'Syne', sans-serif;
        font-size: .68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: rgba(255, 255, 255, .2);
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 1rem;
        max-width: 460px;
        width: 100%;
    }

    .ql-divider::before,
    .ql-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border);
    }

    /* ══ Quick links ══ */
    .quick-links {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 10px;
        width: 100%;
        max-width: 460px;
        margin: 0 auto;
    }

    .quick-link {
        display: flex;
        align-items: center;
        gap: 9px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: .7rem 1rem;
        font-size: .82rem;
        color: var(--muted);
        text-decoration: none;
        transition: all .2s;
    }

    .quick-link i {
        color: var(--blue);
        font-size: 1rem;
        flex-shrink: 0;
    }

    .quick-link:hover {
        background: rgba(99, 102, 241, .08);
        border-color: rgba(99, 102, 241, .3);
        color: var(--text);
        transform: translateY(-2px);
    }

    /* ══ Footer ══ */
    .status-footer {
        position: relative;
        z-index: 1;
        text-align: center;
        margin-top: 2rem;
        font-size: .7rem;
        color: rgba(255, 255, 255, .22);
        padding-bottom: 2rem;
    }

    .status-footer a {
        color: inherit;
        text-decoration: none;
        transition: color .2s;
    }

    .status-footer a:hover {
        color: var(--accent);
    }

    .status-footer p+p {
        margin-top: .3rem;
    }

    /* ══ Fade-in ══ */
    .fade-in {
        opacity: 0;
        animation: fadeUp .5s ease forwards;
    }

    .fade-in:nth-child(1) {
        animation-delay: .05s;
    }

    .fade-in:nth-child(2) {
        animation-delay: .13s;
    }

    .fade-in:nth-child(3) {
        animation-delay: .21s;
    }

    .fade-in:nth-child(4) {
        animation-delay: .29s;
    }

    .fade-in:nth-child(5) {
        animation-delay: .37s;
    }

    .fade-in:nth-child(6) {
        animation-delay: .44s;
    }

    .fade-in:nth-child(7) {
        animation-delay: .51s;
    }

    .fade-in:nth-child(8) {
        animation-delay: .58s;
    }

    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(18px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    </style>
</head>

<body>

    <!-- ═══ NAVBAR ═══ -->
    <nav class="status-nav">
        <a class="nav-brand" href="../../">
            <span class="brand-dot"></span>
            WASOM <span>UPFY</span>
        </a>
        <div class="nav-right">
            <span class="http-badge"><i class="bi bi-person-lock me-1"></i>401</span>
            <span class="version-pill">v<?php echo $platform_ver; ?></span>
        </div>
    </nav>

    <!-- ═══ CONTEÚDO ═══ -->
    <div class="page-wrap">

        <div class="fade-in icon-wrap">
            <svg class="session-svg" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="blueGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#6366f1" />
                        <stop offset="60%" stop-color="#FF0089" />
                        <stop offset="100%" stop-color="#a855f7" />
                    </linearGradient>
                </defs>
                <!-- Silhueta do utilizador -->
                <circle cx="36" cy="22" r="11" fill="rgba(99,102,241,.12)" stroke="url(#blueGrad)" stroke-width="2.5" />
                <!-- Corpo em tracejado = sessão interrompida -->
                <path d="M17 54 C17 42 55 42 55 54" stroke="url(#blueGrad)" stroke-width="2.5" stroke-linecap="round"
                    stroke-dasharray="5 3" />
                <!-- Sinal X (sessão expirada) -->
                <circle cx="54" cy="18" r="11" fill="rgba(99,102,241,.1)" stroke="url(#blueGrad)" stroke-width="2" />
                <line x1="50" y1="14" x2="58" y2="22" stroke="url(#blueGrad)" stroke-width="2.5"
                    stroke-linecap="round" />
                <line x1="58" y1="14" x2="50" y2="22" stroke="url(#blueGrad)" stroke-width="2.5"
                    stroke-linecap="round" />
            </svg>
        </div>

        <div class="fade-in">
            <div class="error-code">401</div>
        </div>

        <div class="fade-in">
            <h1 class="error-title">Sessão <span>Expirada</span></h1>
            <p class="error-desc">
                A tua sessão expirou ou não estás autenticado.
                Inicia sessão novamente para continuar a aceder à plataforma.
            </p>
        </div>

        <div class="fade-in info-box">
            <i class="bi bi-shield-check"></i>
            <span>
                As sessões expiram automaticamente por razões de segurança.
                Os teus dados estão seguros — apenas precisas de voltar a entrar.
            </span>
        </div>

        <!-- CTA principal -->
        <div class="fade-in">
            <a href="<?php echo htmlspecialchars($login_url); ?>" class="btn-login-cta">
                <i class="bi bi-box-arrow-in-right"></i>
                Iniciar Sessão
            </a>
        </div>

        <!-- Auto-redirect notice -->
        <div class="fade-in redirect-notice">
            <i class="bi bi-clock"></i>
            Redireccionado automaticamente em
            <span id="redirectCountdown">45</span>s
        </div>

        <div class="fade-in action-group">
            <a href="../../register" class="btn-secondary-action">
                <i class="bi bi-person-plus"></i>Criar Conta
            </a>
            <a href="../../" class="btn-secondary-action">
                <i class="bi bi-house-door"></i>Página Inicial
            </a>
        </div>

        <div class="fade-in ql-divider">Mais opções</div>

        <div class="fade-in quick-links">
            <a class="quick-link" href="../../">
                <i class="bi bi-house-door"></i>Início
            </a>
            <a class="quick-link" href="../../login">
                <i class="bi bi-person"></i>Entrar
            </a>
            <a class="quick-link" href="../../forgot-password">
                <i class="bi bi-key"></i>Recuperar senha
            </a>
            <a class="quick-link" href="mailto:<?php echo $contact_email; ?>">
                <i class="bi bi-envelope"></i>Contacto
            </a>
        </div>

    </div><!-- /page-wrap -->

    <div class="status-footer">
        <p>Wasom Upfy &nbsp;·&nbsp; Luanda, Angola &nbsp;·&nbsp; v<?php echo $platform_ver; ?></p>
        <p>
            <a href="../page/terms">Termos de Uso</a>
            &nbsp;·&nbsp;
            <a href="../page/privacy">Política de Privacidade</a>
            &nbsp;·&nbsp;
            <a href="mailto:<?php echo $contact_email; ?>"><?php echo $contact_email; ?></a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {

        // ── Auto-redirect countdown ──
        var AUTO_SECS = 45;
        var remaining = AUTO_SECS;
        var elCount = document.getElementById('redirectCountdown');
        var autoTimer = null;
        var cancelled = false;
        var LOGIN_URL = '<?php echo htmlspecialchars($login_url); ?>';

        function tick() {
            remaining--;
            if (elCount) elCount.textContent = remaining;
            if (remaining <= 0) {
                clearInterval(autoTimer);
                window.location.href = LOGIN_URL;
            }
        }
        autoTimer = setInterval(tick, 1000);

        // Cancelar o auto-redirect se o utilizador interagir (ex: quer ir para a home)
        ['click', 'keydown', 'touchstart'].forEach(function(evt) {
            document.addEventListener(evt, function(e) {
                if (!cancelled) {
                    // Apenas cancelar se o clique NÃO for no botão de login
                    var target = e.target.closest('a, button');
                    if (target && target.href && target.href.indexOf('login') !== -1) return;
                    cancelled = true;
                    clearInterval(autoTimer);
                    var notice = document.querySelector('.redirect-notice');
                    if (notice) notice.style.display = 'none';
                }
            }, {
                once: true
            });
        });

        // ── Tema (wu_theme) ──
        function applyTheme(theme) {
            var isDark = theme === 'dark' ||
                (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
            document.body.style.background = isDark ? '#0a0a0f' : '#f4f4f8';
            document.body.style.color = isDark ? '#e8e8f0' : '#111';
        }
        var saved = localStorage.getItem('wu_theme') || 'dark';
        applyTheme(saved);
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
            if ((localStorage.getItem('wu_theme') || 'dark') === 'auto') applyTheme('auto');
        });

    });
    </script>
</body>

</html>