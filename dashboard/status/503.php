<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Página 503 Serviço Indisponível
// Arquivo: dashboard/status/503.php
// Nota: Serviço temporariamente indisponível (sobrecarga,
// deploy, reinício). Diferente do 500 (erro de código)
// e do maintenance (desactivação manual pelo admin).
// ══════════════════════════════════════════════════════
http_response_code(503);
header('Retry-After: 3600');

$platform_ver  = '2.0';
$contact_email = 'suporte@wasomupfy.com';
$is_logged_in  = false;

try {
    require_once __DIR__ . '/../../authentic/include/functions.php';
    startSecureSession();
    checkRememberMe();
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
    // BD indisponível — defaults são suficientes
    error_log('[503 page] ' . $e->getMessage());
}

$back_url      = $is_logged_in ?  APP_URL . '/' . APP_URL_PANEL . '/painel' : APP_URL;
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
    <title>503 — Serviço Indisponível | <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="<?php echo APP_URL ?>/assets/img/icones/wasomupfy_fiv.png" />
    <link rel="apple-touch-icon" href="<?php echo APP_URL ?>/assets/img/icones/wasomupfy_fiv_512.png" />
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
        --amber: #eab308;
        --amber-d: #a16207;
        --amber-glow: rgba(234, 179, 8, .2);
    }

    /* ══ Reset ══ */
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

    /* ══ Grid dots ══ */
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        z-index: 0;
        background-image: radial-gradient(circle, rgba(255, 255, 255, .06) 1px, transparent 1px);
        background-size: 32px 32px;
        pointer-events: none;
    }

    /* ══ Radial glow — âmbar para o 503 ══ */
    body::after {
        content: '';
        position: fixed;
        top: -20%;
        left: 50%;
        transform: translateX(-50%);
        width: 700px;
        height: 500px;
        background: radial-gradient(ellipse, rgba(234, 179, 8, .1) 0%, transparent 70%);
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
        background: rgba(234, 179, 8, .12);
        color: var(--amber);
        border: 1px solid rgba(234, 179, 8, .3);
        border-radius: 999px;
        padding: 3px 10px;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .pulse-amber {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--amber);
        animation: pulseAmber 1.4s ease-in-out infinite;
    }

    @keyframes pulseAmber {

        0%,
        100% {
            box-shadow: 0 0 4px var(--amber);
            opacity: 1;
        }

        50% {
            box-shadow: 0 0 12px var(--amber);
            opacity: .6;
        }
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

    /* ══ Error code ══ */
    .error-code {
        font-family: 'Syne', sans-serif;
        font-size: clamp(7rem, 20vw, 14rem);
        font-weight: 900;
        line-height: 1;
        background: linear-gradient(135deg, #eab308 0%, #f97316 45%, #FF0089 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: .2rem;
        animation: pulseGlow 4s ease-in-out infinite;
        filter: drop-shadow(0 0 40px rgba(234, 179, 8, .25));
    }

    @keyframes pulseGlow {

        0%,
        100% {
            filter: drop-shadow(0 0 30px rgba(234, 179, 8, .2));
        }

        50% {
            filter: drop-shadow(0 0 60px rgba(234, 179, 8, .4));
        }
    }

    /* ══ Clock SVG ══ */
    .clock-wrap {
        margin-bottom: 1.5rem;
    }

    .clock-svg {
        width: 72px;
        height: 72px;
        animation: clockPulse 3s ease-in-out infinite;
    }

    @keyframes clockPulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.07);
        }
    }

    /* Ponteiro dos segundos roda */
    .clock-second {
        transform-origin: 36px 36px;
        animation: rotateSec 4s linear infinite;
    }

    @keyframes rotateSec {
        to {
            transform: rotate(360deg);
        }
    }

    .clock-minute {
        transform-origin: 36px 36px;
        animation: rotateMin 60s linear infinite;
    }

    @keyframes rotateMin {
        to {
            transform: rotate(360deg);
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
        color: var(--amber);
    }

    .error-desc {
        font-size: .95rem;
        color: var(--muted);
        line-height: 1.8;
        max-width: 460px;
        margin: 0 auto 1.6rem;
    }

    /* ══ Auto-retry countdown ══ */
    .retry-countdown {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: rgba(234, 179, 8, .08);
        border: 1px solid rgba(234, 179, 8, .22);
        border-radius: 14px;
        padding: .75rem 1.3rem;
        font-size: .84rem;
        color: rgba(234, 179, 8, .85);
        margin-bottom: 2rem;
    }

    .retry-countdown i {
        font-size: 1rem;
        flex-shrink: 0;
    }

    #retryCountdown {
        font-family: 'Syne', sans-serif;
        font-weight: 900;
        font-size: 1.1rem;
        color: var(--amber);
        min-width: 28px;
    }

    /* ══ Progress bar ══ */
    .retry-bar-wrap {
        max-width: 320px;
        width: 100%;
        margin: 0 auto 2rem;
    }

    .retry-bar-track {
        height: 3px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .06);
        overflow: hidden;
    }

    .retry-bar-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--amber), #f97316);
        box-shadow: 0 0 8px rgba(234, 179, 8, .4);
        transition: width .5s linear;
        width: 100%;
    }

    /* ══ Action buttons ══ */
    .action-group {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 2.5rem;
    }

    .btn-primary-action {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--accent);
        border: none;
        color: #fff;
        border-radius: 12px;
        padding: .72rem 1.6rem;
        font-family: 'Syne', sans-serif;
        font-size: .85rem;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: all .2s;
    }

    .btn-primary-action:hover {
        background: var(--accent-d);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(255, 0, 137, .3);
        color: #fff;
    }

    .btn-secondary-action {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, .06);
        border: 1.5px solid var(--border);
        color: var(--text);
        border-radius: 12px;
        padding: .72rem 1.6rem;
        font-family: 'Syne', sans-serif;
        font-size: .85rem;
        font-weight: 700;
        text-decoration: none;
        transition: all .2s;
        cursor: pointer;
    }

    .btn-secondary-action:hover {
        background: rgba(234, 179, 8, .08);
        border-color: rgba(234, 179, 8, .4);
        color: var(--amber);
        transform: translateY(-2px);
    }

    /* ══ Quick links ══ */
    .quick-links {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 10px;
        width: 100%;
        max-width: 500px;
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
        color: var(--accent);
        font-size: 1rem;
        flex-shrink: 0;
    }

    .quick-link:hover {
        background: rgba(255, 0, 137, .08);
        border-color: rgba(255, 0, 137, .3);
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
        max-width: 500px;
        width: 100%;
    }

    .ql-divider::before,
    .ql-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border);
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
        <a class="nav-brand" href="<?php echo $back_url; ?>">
            <span class="brand-dot"></span>
            WASOM <span>UPFY</span>
        </a>
        <div class="nav-right">
            <span class="http-badge">
                <span class="pulse-amber"></span>503
            </span>
            <span class="version-pill">v<?php echo $platform_ver; ?></span>
        </div>
    </nav>

    <!-- ═══ CONTEÚDO ═══ -->
    <div class="page-wrap">

        <!-- Relógio SVG animado -->
        <div class="fade-in clock-wrap">
            <svg class="clock-svg" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="amberGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#eab308" />
                        <stop offset="50%" stop-color="#f97316" />
                        <stop offset="100%" stop-color="#FF0089" />
                    </linearGradient>
                </defs>
                <!-- Círculo do relógio -->
                <circle cx="36" cy="36" r="26" fill="rgba(234,179,8,.08)" stroke="url(#amberGrad)" stroke-width="3" />
                <!-- Marcas das horas (12, 3, 6, 9) -->
                <line x1="36" y1="13" x2="36" y2="17" stroke="url(#amberGrad)" stroke-width="2.5"
                    stroke-linecap="round" />
                <line x1="59" y1="36" x2="55" y2="36" stroke="url(#amberGrad)" stroke-width="2.5"
                    stroke-linecap="round" />
                <line x1="36" y1="59" x2="36" y2="55" stroke="url(#amberGrad)" stroke-width="2.5"
                    stroke-linecap="round" />
                <line x1="13" y1="36" x2="17" y2="36" stroke="url(#amberGrad)" stroke-width="2.5"
                    stroke-linecap="round" />
                <!-- Ponteiro dos minutos -->
                <line class="clock-minute" x1="36" y1="36" x2="36" y2="18" stroke="url(#amberGrad)" stroke-width="2.5"
                    stroke-linecap="round" />
                <!-- Ponteiro dos segundos -->
                <line class="clock-second" x1="36" y1="36" x2="36" y2="15" stroke="#FF0089" stroke-width="1.5"
                    stroke-linecap="round" />
                <!-- Centro -->
                <circle cx="36" cy="36" r="3" fill="url(#amberGrad)" />
                <!-- Sinal de aviso / !! -->
                <text x="48" y="22" font-family="Syne,Arial" font-weight="900" font-size="11" fill="url(#amberGrad)"
                    text-anchor="middle">!</text>
            </svg>
        </div>

        <div class="fade-in">
            <div class="error-code">503</div>
        </div>

        <div class="fade-in">
            <h1 class="error-title">Serviço <span>Temporariamente</span> Indisponível</h1>
            <p class="error-desc">
                O servidor está sobrecarregado ou em actualização.
                Não perdeste nenhum dado — o serviço regressa em instantes.
            </p>
        </div>

        <!-- Countdown de auto-retry -->
        <div class="fade-in retry-countdown">
            <i class="bi bi-arrow-clockwise"></i>
            <span>Nova tentativa automática em</span>
            <span id="retryCountdown">30</span>
            <span>s</span>
        </div>

        <!-- Barra de progresso do countdown -->
        <div class="fade-in retry-bar-wrap">
            <div class="retry-bar-track">
                <div class="retry-bar-fill" id="retryBarFill"></div>
            </div>
        </div>

        <div class="fade-in action-group">
            <button class="btn-primary-action" id="btnRetryNow" type="button">
                <i class="bi bi-arrow-clockwise"></i>
                Tentar Agora
            </button>
            <a href="<?php echo $back_url; ?>" class="btn-secondary-action">
                <i class="bi bi-house-door"></i>
                <?php echo $is_logged_in ? 'Ir para o Painel' : 'Página Inicial'; ?>
            </a>
        </div>

        <div class="fade-in ql-divider">Acesso rápido</div>

        <div class="fade-in quick-links">
            <?php if ($is_logged_in): ?>
            <a class="quick-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/painel">
                <i class="bi bi-house-door"></i>Painel
            </a>
            <a class="quick-link" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/support">
                <i class="bi bi-headset"></i>Suporte
            </a>
            <a class="quick-link" href="javascript:history.back()">
                <i class="bi bi-chevron-left"></i>Anterior
            </a>
            <a class="quick-link" href="<?php echo APP_URL ?>/logout">
                <i class="bi bi-box-arrow-right"></i>Sair
            </a>
            <?php else: ?>
            <a class="quick-link" href="<?php echo APP_URL ?>">
                <i class="bi bi-house-door"></i>Início
            </a>
            <a class="quick-link" href="<?php echo APP_URL ?>/login">
                <i class="bi bi-person"></i>Entrar
            </a>
            <a class="quick-link" href="javascript:history.back()">
                <i class="bi bi-chevron-left"></i>Anterior
            </a>
            <a class="quick-link" href="mailto:<?php echo $contact_email; ?>">
                <i class="bi bi-envelope"></i>Contacto
            </a>
            <?php endif; ?>
        </div>

    </div><!-- /page-wrap -->

    <div class="status-footer">
        <p>Wasom Upfy &nbsp;·&nbsp; Luanda, Angola &nbsp;·&nbsp; v<?php echo $platform_ver; ?></p>
        <p>
            <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/politicies/terms">Termos de Uso</a>
            &nbsp;·&nbsp;
            <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/politicies/privacy">Política de Privacidade</a>
            &nbsp;·&nbsp;
            <a href="mailto:<?php echo $contact_email; ?>"><?php echo $contact_email; ?></a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {

        // ══════════════════════════════════════════════════
        // AUTO-RETRY COUNTDOWN — 30 segundos, depois recarrega
        // ══════════════════════════════════════════════════
        var RETRY_SECS = 30;
        var remaining = RETRY_SECS;
        var elCount = document.getElementById('retryCountdown');
        var elBar = document.getElementById('retryBarFill');
        var btnRetryNow = document.getElementById('btnRetryNow');
        var autoTimer = null;

        function doReload() {
            window.location.reload();
        }

        function tick() {
            remaining--;
            if (elCount) elCount.textContent = remaining;
            if (elBar) elBar.style.width = ((remaining / RETRY_SECS) * 100).toFixed(1) + '%';

            if (remaining <= 0) {
                clearInterval(autoTimer);
                doReload();
            }
        }

        autoTimer = setInterval(tick, 1000);

        // Botão manual — cancela o timer e recarrega imediatamente
        if (btnRetryNow) {
            btnRetryNow.addEventListener('click', function() {
                clearInterval(autoTimer);
                btnRetryNow.disabled = true;
                btnRetryNow.innerHTML =
                    '<i class="bi bi-arrow-clockwise" style="animation:spin .7s linear infinite;display:inline-block"></i> A recarregar…';
                setTimeout(doReload, 600);
            });
        }

        // ══════════════════════════════════════════════════
        // Tema (wu_theme) — padrão das páginas de estado
        // ══════════════════════════════════════════════════
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