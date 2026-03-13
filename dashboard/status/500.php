<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Página 500 Erro Interno do Servidor
// Arquivo: dashboard/status/500.php
// CRÍTICO: BD pode estar em baixo — página NUNCA deve
// depender de qualquer recurso externo para carregar.
// ══════════════════════════════════════════════════════
http_response_code(500);

$platform_ver  = '2.0';
$contact_email = 'suporte@wasomupfy.com';
$is_logged_in  = false;

// Tentar ler da BD — mas aceitar graciosamente se falhar
// (um 500 frequentemente significa que a BD já está em baixo)
try {
    require_once __DIR__ . '../../../authentic/include/functions.php';
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
    // BD indisponível — mostra a página com os defaults
    error_log('[500 page] ' . $e->getMessage());
}

$back_url = $is_logged_in ? '../painel' : '../../';
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
    <title>500 — Erro Interno | Wasom Upfy</title>
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
        --danger: #ef4444;
        --danger-d: #b91c1c;
        --danger-glow: rgba(239, 68, 68, .22);
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
        background-image:
            radial-gradient(circle, rgba(255, 255, 255, .06) 1px, transparent 1px);
        background-size: 32px 32px;
        pointer-events: none;
    }

    /* ══ Radial glow — vermelho para o 500 ══ */
    body::after {
        content: '';
        position: fixed;
        top: -20%;
        left: 50%;
        transform: translateX(-50%);
        width: 700px;
        height: 500px;
        background: radial-gradient(ellipse, rgba(239, 68, 68, .1) 0%, transparent 70%);
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
        background: rgba(239, 68, 68, .12);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, .3);
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

    /* ══ Error code ══ */
    .error-code {
        font-family: 'Syne', sans-serif;
        font-size: clamp(7rem, 20vw, 14rem);
        font-weight: 900;
        line-height: 1;
        background: linear-gradient(135deg, #ef4444 0%, #FF0089 55%, #a855f7 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: .2rem;
        animation: pulseGlow 4s ease-in-out infinite;
        filter: drop-shadow(0 0 40px rgba(239, 68, 68, .3));
    }

    @keyframes pulseGlow {

        0%,
        100% {
            filter: drop-shadow(0 0 30px rgba(239, 68, 68, .2));
        }

        50% {
            filter: drop-shadow(0 0 60px rgba(239, 68, 68, .45));
        }
    }

    /* ══ Bug SVG ══ */
    .bug-wrap {
        margin-bottom: 1.5rem;
    }

    .bug-svg {
        width: 72px;
        height: 72px;
        animation: bugFloat 5s ease-in-out infinite;
    }

    @keyframes bugFloat {

        0%,
        100% {
            transform: translateY(0) rotate(0deg);
        }

        25% {
            transform: translateY(-8px) rotate(-4deg);
        }

        75% {
            transform: translateY(-4px) rotate(3deg);
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
        color: var(--danger);
    }

    .error-desc {
        font-size: .95rem;
        color: var(--muted);
        line-height: 1.8;
        max-width: 480px;
        margin: 0 auto 2rem;
    }

    /* ══ Alert box ══ */
    .alert-box {
        display: inline-flex;
        align-items: flex-start;
        gap: 10px;
        background: rgba(239, 68, 68, .08);
        border: 1px solid rgba(239, 68, 68, .22);
        border-radius: 14px;
        padding: .85rem 1.2rem;
        font-size: .82rem;
        color: rgba(232, 232, 240, .7);
        max-width: 480px;
        width: 100%;
        margin: 0 auto 2rem;
        text-align: left;
    }

    .alert-box i {
        color: var(--danger);
        font-size: 1rem;
        flex-shrink: 0;
        margin-top: 1px;
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
        background: rgba(239, 68, 68, .08);
        border-color: rgba(239, 68, 68, .4);
        color: var(--danger);
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

    /* ══ Retry button state ══ */
    .btn-retry-spin .bi {
        animation: spin .7s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
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
        animation-delay: .45s;
    }

    .fade-in:nth-child(7) {
        animation-delay: .53s;
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
            <span class="http-badge"><i class="bi bi-exclamation-triangle me-1"></i>500</span>
            <span class="version-pill">v<?php echo $platform_ver; ?></span>
        </div>
    </nav>

    <!-- ═══ CONTEÚDO ═══ -->
    <div class="page-wrap">

        <div class="fade-in bug-wrap">
            <!-- Ícone SVG de bug/erro de servidor -->
            <svg class="bug-svg" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="errGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#ef4444" />
                        <stop offset="55%" stop-color="#FF0089" />
                        <stop offset="100%" stop-color="#a855f7" />
                    </linearGradient>
                </defs>
                <!-- Corpo do servidor -->
                <rect x="14" y="18" width="44" height="36" rx="6" fill="rgba(239,68,68,.1)" stroke="url(#errGrad)"
                    stroke-width="3" />
                <!-- Luzes do servidor -->
                <circle cx="24" cy="29" r="3" fill="url(#errGrad)" />
                <circle cx="34" cy="29" r="3" fill="rgba(239,68,68,.3)" />
                <!-- Barra de progresso interrompida -->
                <rect x="22" y="39" width="28" height="4" rx="2" fill="rgba(255,255,255,.08)" stroke="none" />
                <rect x="22" y="39" width="14" height="4" rx="2" fill="url(#errGrad)" stroke="none" />
                <!-- X de erro -->
                <line x1="44" y1="25" x2="52" y2="33" stroke="url(#errGrad)" stroke-width="3" stroke-linecap="round" />
                <line x1="52" y1="25" x2="44" y2="33" stroke="url(#errGrad)" stroke-width="3" stroke-linecap="round" />
                <!-- Relâmpago -->
                <path d="M34 52 L30 62 L36 57 L32 67" stroke="url(#errGrad)" stroke-width="2.5" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </div>

        <div class="fade-in">
            <div class="error-code">500</div>
        </div>

        <div class="fade-in">
            <h1 class="error-title">Erro Interno do <span>Servidor</span></h1>
            <p class="error-desc">
                Algo correu mal do nosso lado. A nossa equipa foi notificada
                e está a trabalhar para resolver o problema o mais depressa possível.
            </p>
        </div>

        <div class="fade-in alert-box">
            <i class="bi bi-info-circle-fill"></i>
            <span>
                Se o problema persistir, podes contactar o suporte em
                <a href="mailto:<?php echo $contact_email; ?>" style="color:var(--danger);text-decoration:none">
                    <?php echo $contact_email; ?>
                </a>
                e descrever o que estavas a fazer quando o erro ocorreu.
            </span>
        </div>

        <div class="fade-in action-group">
            <button class="btn-primary-action" id="btnRetry" type="button">
                <i class="bi bi-arrow-clockwise"></i>
                Tentar Novamente
            </button>
            <a href="<?php echo $back_url; ?>" class="btn-secondary-action">
                <i class="bi bi-house-door"></i>
                <?php echo $is_logged_in ? 'Ir para o Painel' : 'Página Inicial'; ?>
            </a>
        </div>

        <div class="fade-in ql-divider">Acesso rápido</div>

        <div class="fade-in quick-links">
            <?php if ($is_logged_in): ?>
            <a class="quick-link" href="../painel">
                <i class="bi bi-house-door"></i>Painel
            </a>
            <a class="quick-link" href="../page/support">
                <i class="bi bi-headset"></i>Suporte
            </a>
            <a class="quick-link" href="javascript:history.back()">
                <i class="bi bi-chevron-left"></i>Anterior
            </a>
            <a class="quick-link" href="../logout">
                <i class="bi bi-box-arrow-right"></i>Sair
            </a>
            <?php else: ?>
            <a class="quick-link" href="../../">
                <i class="bi bi-house-door"></i>Início
            </a>
            <a class="quick-link" href="../../login">
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

        // ── Botão "Tentar Novamente" com feedback visual ──
        var btnRetry = document.getElementById('btnRetry');
        if (btnRetry) {
            btnRetry.addEventListener('click', function() {
                btnRetry.classList.add('btn-retry-spin');
                btnRetry.disabled = true;
                btnRetry.innerHTML = '<i class="bi bi-arrow-clockwise"></i> A recarregar…';
                setTimeout(function() {
                    window.location.reload();
                }, 800);
            });
        }

        // ── Sistema de tema (wu_theme) — idêntico às outras páginas de estado ──
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