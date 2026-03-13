<?php
// ══════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — 403 Forbidden (Site Público)
// Ficheiro: status/403.php  (profundidade: ../)
// NÃO chamar checkPlatformStatus() aqui — evita loop infinito
// ══════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../include/site.php';

// HTTP 403 obrigatório
http_response_code(403);

// ── Ler dados da plataforma e do visitante ────────────────────────────
$site_name     = cfg('site_name',     'Wasom Upfy');
$support_email = cfg('support_email', 'suporte@wasomupfy.com');
$platform_ver  = getPlatform()['version'] ?? '2.0';

// Razão do bloqueio — ler da _visitor pelo IP
$block_reason_raw  = null;
$block_until_raw   = null;
$block_type_raw    = null;
$block_notes_raw   = null;
$is_platform_block = false; // bloqueio global da plataforma vs bloqueio de IP

try {
    $db  = getSiteDB();
    $ip  = getVisitorIp();

    // Verificar se é bloqueio global da plataforma
    $platform = getPlatform();
    if ($platform['status'] === 'unauthorized') {
        $is_platform_block = true;
    } else {
        // Tentar obter motivo do bloqueio do IP
        $stmt = $db->prepare("
            SELECT block_reason, block_type, block_until, block_notes
            FROM _visitor
            WHERE ip_address = ? AND status_visitor = 'blocked'
            ORDER BY id_visitor DESC
            LIMIT 1
        ");
        $stmt->execute([$ip]);
        $row = $stmt->fetch();

        if ($row) {
            $block_reason_raw = $row['block_reason'];
            $block_type_raw   = $row['block_type'];
            $block_until_raw  = $row['block_until'];
            $block_notes_raw  = $row['block_notes'];
        }
    }

} catch (Throwable $e) {
    if (APP_ENV === 'development') error_log('[403] ' . $e->getMessage());
}

// ── Labels legíveis para cada razão de bloqueio ───────────────────────
$reason_labels = [
    'spam'              => 'Actividade de spam detectada',
    'security'          => 'Violação de segurança',
    'bot'               => 'Comportamento automático (bot)',
    'multiple_failures' => 'Múltiplas tentativas falhadas',
    'suspicious'        => 'Actividade suspeita',
    'other'             => 'Violação das políticas de uso',
];

$reason_icons = [
    'spam'              => 'bi-envelope-x',
    'security'          => 'bi-shield-x',
    'bot'               => 'bi-robot',
    'multiple_failures' => 'bi-exclamation-triangle',
    'suspicious'        => 'bi-eye-slash',
    'other'             => 'bi-slash-circle',
];

$reason_label = $is_platform_block
    ? 'Acesso restrito pela plataforma'
    : ($reason_labels[$block_reason_raw ?? ''] ?? 'Acesso não autorizado');

$reason_icon = $is_platform_block
    ? 'bi-lock'
    : ($reason_icons[$block_reason_raw ?? ''] ?? 'bi-shield-exclamation');

// ── Formatar data de expiração do bloqueio ────────────────────────────
$block_until_fmt = null;
if (!$is_platform_block && $block_type_raw === 'temporary' && !empty($block_until_raw)) {
    $ts = strtotime($block_until_raw);
    if ($ts > time()) {
        $block_until_fmt = date('d/m/Y \à\s H:i', $ts) . ' (WAT)';
    }
}

$is_permanent = (!$is_platform_block && $block_type_raw === 'permanent');
$is_temporary = (!$is_platform_block && $block_type_raw === 'temporary' && $block_until_fmt);
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <meta name="author" content="José Mbenga da Costa" />
    <title>403 — Acesso Negado · <?php echo htmlspecialchars($site_name); ?></title>
    <link rel="shortcut icon" href="../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="apple-touch-icon" href="../assets/img/icones/wasomupfy_fiv_512.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <style>
    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    :root {
        --red: #f87171;
        --red-dark: #dc2626;
        --red-glow: rgba(248, 113, 113, .25);
        --pink: #FF0089;
        --bg: #08080f;
        --card: rgba(255, 255, 255, .04);
        --border: rgba(255, 255, 255, .08);
        --border-red: rgba(248, 113, 113, .2);
        --text: #e8e8f0;
        --muted: rgba(255, 255, 255, .45);
    }

    html,
    body {
        height: 100%;
        margin: 0;
        padding: 0;
        background: var(--bg);
        color: var(--text);
        font-family: 'Segoe UI', Arial, sans-serif;
        overflow-x: hidden;
    }

    /* ── Orbs ── */
    .bg-canvas {
        position: fixed;
        inset: 0;
        z-index: 0;
        overflow: hidden;
        pointer-events: none;
    }

    .bg-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: .14;
        animation: floatOrb 16s ease-in-out infinite;
    }

    .bg-orb:nth-child(1) {
        width: 480px;
        height: 480px;
        background: #dc2626;
        top: -140px;
        left: -100px;
        animation-delay: 0s;
    }

    .bg-orb:nth-child(2) {
        width: 380px;
        height: 380px;
        background: #7f1d1d;
        bottom: -110px;
        right: -80px;
        animation-delay: -7s;
    }

    .bg-orb:nth-child(3) {
        width: 260px;
        height: 260px;
        background: #FF0089;
        top: 40%;
        left: 58%;
        animation-delay: -12s;
    }

    @keyframes floatOrb {

        0%,
        100% {
            transform: translate(0, 0) scale(1);
        }

        33% {
            transform: translate(24px, -32px) scale(1.04);
        }

        66% {
            transform: translate(-16px, 20px) scale(.97);
        }
    }

    /* ── Layout ── */
    .page-wrap {
        position: relative;
        z-index: 1;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem 4rem;
    }

    /* ── Logo ── */
    .brand-logo {
        font-size: 1.05rem;
        font-weight: 900;
        letter-spacing: .14em;
        color: var(--pink);
        font-family: Arial, sans-serif;
        margin-bottom: 2rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 9px;
    }

    .brand-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--pink);
        box-shadow: 0 0 10px var(--pink);
        animation: pulsePink 2s ease-in-out infinite;
    }

    @keyframes pulsePink {

        0%,
        100% {
            box-shadow: 0 0 6px var(--pink);
        }

        50% {
            box-shadow: 0 0 18px var(--pink), 0 0 32px rgba(255, 0, 137, .3);
        }
    }

    /* ── Card ── */
    .main-card {
        background: var(--card);
        border: 1px solid var(--border-red);
        border-radius: 28px;
        padding: 2.8rem 2.4rem;
        max-width: 560px;
        width: 100%;
        text-align: center;
        backdrop-filter: blur(22px);
        -webkit-backdrop-filter: blur(22px);
        box-shadow: 0 0 60px rgba(220, 38, 38, .08), 0 24px 64px rgba(0, 0, 0, .45);
    }

    @media(max-width:576px) {
        .main-card {
            padding: 2rem 1.4rem;
            border-radius: 20px;
        }
    }

    /* ── Código de erro ── */
    .error-code {
        font-size: 5.5rem;
        font-weight: 900;
        line-height: 1;
        color: var(--red);
        letter-spacing: -.04em;
        font-family: 'Courier New', monospace;
        text-shadow: 0 0 40px rgba(248, 113, 113, .3);
        margin-bottom: .3rem;
        animation: glitch 6s ease-in-out infinite;
    }

    @keyframes glitch {

        0%,
        94%,
        100% {
            transform: none;
            text-shadow: 0 0 40px rgba(248, 113, 113, .3);
        }

        95% {
            transform: skewX(-2deg) translateX(2px);
            text-shadow: -2px 0 var(--pink), 2px 0 #60a5fa;
        }

        97% {
            transform: skewX(1deg) translateX(-1px);
            text-shadow: 2px 0 var(--pink), -2px 0 #34d399;
        }
    }

    /* ── Badge ── */
    .error-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(248, 113, 113, .1);
        border: 1px solid rgba(248, 113, 113, .28);
        border-radius: 999px;
        padding: 4px 14px;
        font-size: .71rem;
        font-weight: 800;
        color: var(--red);
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: 1rem;
    }

    .pulse-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--red);
        animation: pulseRed 1.4s ease-in-out infinite;
    }

    @keyframes pulseRed {

        0%,
        100% {
            box-shadow: 0 0 4px var(--red);
            opacity: 1;
        }

        50% {
            box-shadow: 0 0 12px var(--red);
            opacity: .6;
        }
    }

    /* ── Ícone ── */
    .error-icon {
        width: 76px;
        height: 76px;
        border-radius: 22px;
        background: rgba(248, 113, 113, .09);
        border: 1.5px solid rgba(248, 113, 113, .2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.4rem;
        font-size: 2.1rem;
        color: var(--red);
        animation: shake 8s ease-in-out infinite;
    }

    @keyframes shake {

        0%,
        90%,
        100% {
            transform: rotate(0deg);
        }

        92% {
            transform: rotate(-6deg);
        }

        94% {
            transform: rotate(6deg);
        }

        96% {
            transform: rotate(-3deg);
        }

        98% {
            transform: rotate(3deg);
        }
    }

    /* ── Textos ── */
    .error-title {
        font-size: 1.6rem;
        font-weight: 900;
        color: #fff;
        margin-bottom: .5rem;
        line-height: 1.25;
    }

    .error-title span {
        color: var(--red);
    }

    .error-desc {
        font-size: .87rem;
        color: var(--muted);
        line-height: 1.75;
        margin-bottom: 1.4rem;
    }

    /* ── Info box de razão ── */
    .reason-box {
        background: rgba(248, 113, 113, .07);
        border: 1px solid rgba(248, 113, 113, .16);
        border-radius: 14px;
        padding: 1rem 1.2rem;
        margin-bottom: 1.6rem;
        text-align: left;
    }

    .reason-box .reason-label {
        font-size: .72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: rgba(255, 255, 255, .3);
        margin-bottom: .6rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .reason-box .reason-value {
        font-size: .88rem;
        color: var(--red);
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .reason-box .reason-note {
        font-size: .78rem;
        color: var(--muted);
        margin-top: .4rem;
        line-height: 1.55;
    }

    /* ── Timer de expiração ── */
    .expiry-box {
        background: rgba(251, 191, 36, .06);
        border: 1px solid rgba(251, 191, 36, .15);
        border-radius: 12px;
        padding: .8rem 1.2rem;
        margin-bottom: 1.6rem;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: .83rem;
    }

    .expiry-box i {
        color: #fbbf24;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .expiry-box strong {
        color: rgba(255, 255, 255, .7);
    }

    /* ── Permanent box ── */
    .permanent-box {
        background: rgba(248, 113, 113, .07);
        border: 1px solid rgba(248, 113, 113, .18);
        border-radius: 12px;
        padding: .8rem 1.2rem;
        margin-bottom: 1.6rem;
        font-size: .82rem;
        color: var(--red);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .permanent-box i {
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    /* ── Separador ── */
    .divider {
        height: 1px;
        background: var(--border);
        margin: 1.4rem 0;
    }

    /* ── Botões ── */
    .action-row {
        display: flex;
        gap: .6rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .btn-home {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--pink);
        border: none;
        color: #fff;
        border-radius: 12px;
        padding: .65rem 1.6rem;
        font-size: .85rem;
        font-weight: 700;
        text-decoration: none;
        transition: all .2s;
        cursor: pointer;
    }

    .btn-home:hover {
        background: #c8006e;
        transform: translateY(-1px);
        color: #fff;
    }

    .btn-support {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, .06);
        border: 1px solid var(--border);
        color: var(--text);
        border-radius: 12px;
        padding: .65rem 1.4rem;
        font-size: .84rem;
        font-weight: 700;
        text-decoration: none;
        transition: all .2s;
    }

    .btn-support:hover {
        background: rgba(248, 113, 113, .1);
        border-color: var(--red);
        color: var(--red);
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, .04);
        border: 1px solid var(--border);
        color: rgba(255, 255, 255, .4);
        border-radius: 12px;
        padding: .65rem 1.2rem;
        font-size: .8rem;
        font-weight: 600;
        text-decoration: none;
        transition: all .2s;
    }

    .btn-back:hover {
        border-color: rgba(255, 255, 255, .18);
        color: rgba(255, 255, 255, .7);
    }

    /* ── Countdown do desbloqueio ── */
    .cd-wrap {
        display: flex;
        gap: 8px;
        justify-content: center;
        margin: .8rem 0;
        flex-wrap: wrap;
    }

    .cd-block {
        background: rgba(255, 255, 255, .05);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: .6rem .9rem;
        min-width: 58px;
        text-align: center;
    }

    .cd-num {
        font-size: 1.5rem;
        font-weight: 900;
        color: #fbbf24;
        font-family: 'Courier New', monospace;
        line-height: 1;
        display: block;
        margin-bottom: .15rem;
    }

    .cd-lbl {
        font-size: .55rem;
        color: var(--muted);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    /* ── Footer ── */
    .page-footer {
        position: relative;
        z-index: 1;
        text-align: center;
        margin-top: 1.8rem;
        font-size: .7rem;
        color: rgba(255, 255, 255, .25);
    }

    .page-footer a {
        color: inherit;
        text-decoration: none;
    }

    .page-footer a:hover {
        color: var(--pink);
    }
    </style>
</head>

<body>

    <div class="bg-canvas">
        <div class="bg-orb"></div>
        <div class="bg-orb"></div>
        <div class="bg-orb"></div>
    </div>

    <div class="page-wrap">

        <!-- Logo -->
        <a class="brand-logo" href="../home">
            <img src="../assets/img/brand/wasomupfy_brand.png" alt="<?php echo htmlspecialchars($site_name); ?>"
                height="32" style="filter:brightness(0) invert(1)"
                onerror="this.style.display='none';this.nextElementSibling.style.display='inline'" />
            <span style="display:none">
                <span class="brand-dot"></span><?php echo strtoupper(htmlspecialchars($site_name)); ?>
            </span>
        </a>

        <div class="main-card">

            <!-- Código de erro -->
            <div class="error-code">403</div>

            <div class="error-badge">
                <span class="pulse-dot"></span>
                Acesso Negado
            </div>

            <!-- Ícone -->
            <div class="error-icon">
                <i class="bi <?php echo $reason_icon; ?>"></i>
            </div>

            <h1 class="error-title">
                <?php if ($is_platform_block): ?>
                Acesso <span>Restrito</span>
                <?php elseif ($is_permanent): ?>
                Acesso <span>Bloqueado</span>
                <?php else: ?>
                Acesso <span>Temporariamente<br>Suspenso</span>
                <?php endif; ?>
            </h1>

            <p class="error-desc">
                <?php if ($is_platform_block): ?>
                O acesso ao site <?php echo htmlspecialchars($site_name); ?> está temporariamente
                restrito. Se acreditas que isto é um erro, contacta o suporte.
                <?php elseif ($is_permanent): ?>
                O teu endereço IP foi bloqueado permanentemente devido a uma violação
                das políticas de uso da plataforma <?php echo htmlspecialchars($site_name); ?>.
                Para contestar este bloqueio, contacta o suporte.
                <?php else: ?>
                O teu acesso foi suspenso temporariamente. Aguarda o fim do período
                indicado abaixo ou contacta o suporte caso acredites que existe um erro.
                <?php endif; ?>
            </p>

            <!-- Razão do bloqueio (se disponível) -->
            <?php if (!$is_platform_block && $block_reason_raw): ?>
            <div class="reason-box">
                <div class="reason-label">
                    <i class="bi bi-info-circle"></i>Motivo do bloqueio
                </div>
                <div class="reason-value">
                    <i class="bi <?php echo $reason_icon; ?>"></i>
                    <?php echo htmlspecialchars($reason_label); ?>
                </div>
                <?php if (!empty($block_notes_raw)): ?>
                <div class="reason-note">
                    <?php echo htmlspecialchars($block_notes_raw); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Bloqueio permanente -->
            <?php if ($is_permanent): ?>
            <div class="permanent-box">
                <i class="bi bi-ban"></i>
                <span>Este bloqueio é <strong>permanente</strong> e não expira automaticamente.</span>
            </div>

            <!-- Bloqueio temporário com countdown -->
            <?php elseif ($is_temporary): ?>
            <div class="expiry-box">
                <i class="bi bi-clock-history"></i>
                <div>
                    <div style="margin-bottom:.3rem">
                        Acesso restaurado a <strong><?php echo $block_until_fmt; ?></strong>
                    </div>
                    <div class="cd-wrap" id="cdWrap">
                        <div class="cd-block"><span class="cd-num" id="cdH">--</span><span class="cd-lbl">Horas</span>
                        </div>
                        <div class="cd-block"><span class="cd-num" id="cdM">--</span><span class="cd-lbl">Min</span>
                        </div>
                        <div class="cd-block"><span class="cd-num" id="cdS">--</span><span class="cd-lbl">Seg</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="divider"></div>

            <!-- Acções -->
            <div class="action-row">
                <a href="../home" class="btn-home">
                    <i class="bi bi-house"></i> Início
                </a>
                <a href="../page/support/support" class="btn-support">
                    <i class="bi bi-headset"></i> Contactar Suporte
                </a>
                <a href="javascript:history.back()" class="btn-back">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>

        </div><!-- /main-card -->

        <div class="page-footer">
            <p>
                <?php echo htmlspecialchars($site_name); ?> &nbsp;·&nbsp; Luanda, Angola
                &nbsp;·&nbsp; v<?php echo htmlspecialchars($platform_ver); ?>
            </p>
            <p>
                <a href="../page/politicies/terms">Termos de Uso</a>
                &nbsp;·&nbsp;
                <a href="../page/politicies/privacy">Política de Privacidade</a>
                &nbsp;·&nbsp;
                <a
                    href="mailto:<?php echo htmlspecialchars($support_email); ?>"><?php echo htmlspecialchars($support_email); ?></a>
            </p>
        </div>

    </div><!-- /page-wrap -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php if ($is_temporary && $block_until_raw): ?>
    <script>
    // Countdown até fim do bloqueio temporário
    var blockUntil = <?php echo (int)strtotime($block_until_raw); ?> * 1000;

    var elH = document.getElementById('cdH');
    var elM = document.getElementById('cdM');
    var elS = document.getElementById('cdS');

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function tickUnblock() {
        var now = Date.now();
        var diff = Math.max(0, Math.floor((blockUntil - now) / 1000));

        elH.textContent = pad(Math.floor(diff / 3600));
        elM.textContent = pad(Math.floor((diff % 3600) / 60));
        elS.textContent = pad(diff % 60);

        if (diff <= 0) {
            // Bloqueio expirou — recarregar para o site.php desbloquear na BD
            clearInterval(unblockTimer);
            window.location.reload();
        }
    }

    var unblockTimer = setInterval(tickUnblock, 1000);
    tickUnblock();
    </script>
    <?php endif; ?>

</body>

</html>