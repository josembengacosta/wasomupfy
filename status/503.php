<?php
// ══════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — 503 Service Unavailable (Site Público)
// Ficheiro: status/503.php  (profundidade: ../)
// NÃO chamar checkPlatformStatus() aqui — evita loop infinito
// ══════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../include/site.php';

// HTTP 503 obrigatório + header de retry
http_response_code(503);
header('Retry-After: 3600');

// ── Ler dados da plataforma ───────────────────────────────────────────
$site_name     = cfg('site_name',     'Wasom Upfy');
$support_email = cfg('support_email', 'suporte@wasomupfy.com');

$platform         = getPlatform();
$platform_ver     = $platform['version'] ?? '2.0';

$maint_msg        = 'O serviço está temporariamente indisponível. A equipa técnica já foi notificada e está a trabalhar para restaurar o acesso o mais rapidamente possível.';
$maint_end_ts     = null;
$maint_start_ts   = null;
$seconds_remaining = 0;
$seconds_total     = 0;
$services          = [];

try {
    if (!empty($platform['maintenance_msg'])) {
        $maint_msg = htmlspecialchars($platform['maintenance_msg']);
    }
    if (!empty($platform['maintenance_end'])) {
        $maint_end_ts = strtotime($platform['maintenance_end']);
    }
    if (!empty($platform['maintenance_start'])) {
        $maint_start_ts = strtotime($platform['maintenance_start']);
    }
    if (!empty($platform['maintenance_services'])) {
        $decoded = json_decode($platform['maintenance_services'], true);
        if (is_array($decoded)) $services = $decoded;
    }
} catch (Throwable $e) {
    if (APP_ENV === 'development') error_log('[503] ' . $e->getMessage());
}

// ── Countdown ─────────────────────────────────────────────────────────
$now = time();
if ($maint_end_ts && $maint_end_ts > $now) {
    $seconds_remaining = $maint_end_ts - $now;
}
if ($maint_start_ts && $maint_end_ts) {
    $seconds_total = max(1, $maint_end_ts - $maint_start_ts);
} elseif ($seconds_remaining > 0) {
    $seconds_total = $seconds_remaining;
}

$maint_end_fmt = ($maint_end_ts && $maint_end_ts > $now)
    ? date('d/m/Y \à\s H:i', $maint_end_ts) . ' (WAT)'
    : 'Em breve';

// ── Serviços por defeito ──────────────────────────────────────────────
if (empty($services)) {
    $services = [
        'auth'          => 'down',
        'distribution'  => 'down',
        'analytics'     => 'down',
        'finances'      => 'down',
        'notifications' => 'down',
    ];
}

$service_labels = [
    'auth'          => 'Autenticação e sessões',
    'distribution'  => 'Distribuição e lançamentos',
    'analytics'     => 'Analytics e streams',
    'finances'      => 'Finanças e royalties',
    'notifications' => 'Notificações e Service Worker',
    'support'       => 'Sistema de suporte',
    'youtube'       => 'Integração YouTube',
    'storage'       => 'Armazenamento de ficheiros',
];

$state_map = [
    'ok'      => ['dot' => 's-ok',      'badge' => 'badge-ok',      'icon' => 'bi-check2',          'text' => 'Online'],
    'working' => ['dot' => 's-working', 'badge' => 'badge-working', 'icon' => 'bi-arrow-repeat',    'text' => 'A recuperar'],
    'pending' => ['dot' => 's-pending', 'badge' => 'badge-pending', 'icon' => 'bi-hourglass-split', 'text' => 'Pendente'],
    'down'    => ['dot' => 's-down',    'badge' => 'badge-down',    'icon' => 'bi-x-lg',            'text' => 'Indisponível'],
];

// ── Contagem de serviços por estado ──────────────────────────────────
$count_down    = count(array_filter($services, fn($s) => $s === 'down'));
$count_ok      = count(array_filter($services, fn($s) => $s === 'ok'));
$count_working = count(array_filter($services, fn($s) => $s === 'working'));
$total_services = count($services);
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
    <title>503 — Serviço Indisponível · <?php echo htmlspecialchars($site_name); ?></title>
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
        --orange: #fb923c;
        --orange-dark: #ea580c;
        --orange-glow: rgba(251, 146, 60, .25);
        --pink: #FF0089;
        --bg: #08080f;
        --card: rgba(255, 255, 255, .04);
        --border: rgba(255, 255, 255, .08);
        --border-orng: rgba(251, 146, 60, .2);
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
        opacity: .15;
        animation: floatOrb 16s ease-in-out infinite;
    }

    .bg-orb:nth-child(1) {
        width: 500px;
        height: 500px;
        background: #ea580c;
        top: -150px;
        left: -120px;
        animation-delay: 0s;
    }

    .bg-orb:nth-child(2) {
        width: 370px;
        height: 370px;
        background: #9a3412;
        bottom: -110px;
        right: -80px;
        animation-delay: -7s;
    }

    .bg-orb:nth-child(3) {
        width: 270px;
        height: 270px;
        background: #FF0089;
        top: 42%;
        left: 57%;
        animation-delay: -12s;
    }

    @keyframes floatOrb {

        0%,
        100% {
            transform: translate(0, 0) scale(1);
        }

        33% {
            transform: translate(26px, -34px) scale(1.04);
        }

        66% {
            transform: translate(-18px, 22px) scale(.97);
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
        border: 1px solid var(--border-orng);
        border-radius: 28px;
        padding: 2.8rem 2.4rem;
        max-width: 600px;
        width: 100%;
        text-align: center;
        backdrop-filter: blur(22px);
        -webkit-backdrop-filter: blur(22px);
        box-shadow: 0 0 60px rgba(251, 146, 60, .07), 0 24px 64px rgba(0, 0, 0, .45);
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
        color: var(--orange);
        letter-spacing: -.04em;
        font-family: 'Courier New', monospace;
        text-shadow: 0 0 40px rgba(251, 146, 60, .35);
        margin-bottom: .3rem;
        animation: flicker 8s ease-in-out infinite;
    }

    @keyframes flicker {

        0%,
        89%,
        91%,
        93%,
        100% {
            opacity: 1;
        }

        90% {
            opacity: .6;
        }

        92% {
            opacity: .9;
        }
    }

    /* ── Badge ── */
    .error-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(251, 146, 60, .1);
        border: 1px solid rgba(251, 146, 60, .28);
        border-radius: 999px;
        padding: 4px 14px;
        font-size: .71rem;
        font-weight: 800;
        color: var(--orange);
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: 1rem;
    }

    .pulse-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--orange);
        animation: pulseOrng 1.4s ease-in-out infinite;
    }

    @keyframes pulseOrng {

        0%,
        100% {
            box-shadow: 0 0 4px var(--orange);
            opacity: 1;
        }

        50% {
            box-shadow: 0 0 12px var(--orange);
            opacity: .65;
        }
    }

    /* ── Ícone ── */
    .error-icon {
        width: 82px;
        height: 82px;
        border-radius: 22px;
        background: rgba(251, 146, 60, .09);
        border: 1.5px solid rgba(251, 146, 60, .2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.4rem;
        font-size: 2.2rem;
        color: var(--orange);
        animation: bounce 4s ease-in-out infinite;
    }

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-7px);
        }
    }

    /* ── Textos ── */
    .error-title {
        font-size: 1.65rem;
        font-weight: 900;
        color: #fff;
        margin-bottom: .5rem;
        line-height: 1.25;
    }

    .error-title span {
        color: var(--orange);
    }

    .error-desc {
        font-size: .87rem;
        color: var(--muted);
        line-height: 1.75;
        margin-bottom: .5rem;
    }

    /* ── ETA ── */
    .eta-line {
        font-size: .75rem;
        color: rgba(255, 255, 255, .35);
        margin-bottom: 1.6rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .eta-line i {
        color: var(--orange);
    }

    /* ── Countdown ── */
    .cd-wrap {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-bottom: 1.6rem;
        flex-wrap: wrap;
    }

    .cd-block {
        background: rgba(255, 255, 255, .05);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: .85rem 1.1rem;
        min-width: 68px;
        text-align: center;
    }

    .cd-num {
        font-size: 1.9rem;
        font-weight: 900;
        color: var(--orange);
        font-family: 'Courier New', monospace;
        line-height: 1;
        display: block;
        margin-bottom: .2rem;
    }

    .cd-lbl {
        font-size: .6rem;
        color: var(--muted);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    /* ── Barra de progresso ── */
    .prog-wrap {
        margin-bottom: 1.8rem;
    }

    .prog-track {
        height: 5px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .07);
        overflow: hidden;
        margin-bottom: .4rem;
    }

    .prog-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--orange), var(--orange-dark));
        transition: width 1s linear;
        box-shadow: 0 0 10px rgba(251, 146, 60, .4);
    }

    .prog-meta {
        display: flex;
        justify-content: space-between;
        font-size: .68rem;
        color: rgba(255, 255, 255, .25);
    }

    /* ── Resumo de estado dos serviços ── */
    .status-summary {
        display: flex;
        gap: .6rem;
        justify-content: center;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .summary-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: .3rem .85rem;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 700;
    }

    .pill-down {
        background: rgba(248, 113, 113, .12);
        color: #f87171;
        border: 1px solid rgba(248, 113, 113, .2);
    }

    .pill-ok {
        background: rgba(34, 197, 94, .1);
        color: #22c55e;
        border: 1px solid rgba(34, 197, 94, .2);
    }

    .pill-working {
        background: rgba(251, 146, 60, .1);
        color: var(--orange);
        border: 1px solid rgba(251, 146, 60, .2);
    }

    /* ── Lista de serviços ── */
    .services-title {
        text-align: left;
        font-size: .72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: rgba(255, 255, 255, .3);
        margin-bottom: .5rem;
    }

    .service-list {
        list-style: none;
        padding: 0;
        margin: 0 0 1.8rem;
        text-align: left;
    }

    .service-list li {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: .48rem .6rem;
        border-radius: 9px;
        font-size: .81rem;
        color: var(--muted);
        border-bottom: 1px solid rgba(255, 255, 255, .04);
    }

    .service-list li:last-child {
        border-bottom: none;
    }

    .s-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .s-ok {
        background: #22c55e;
        box-shadow: 0 0 6px rgba(34, 197, 94, .5);
    }

    .s-working {
        background: var(--orange);
        box-shadow: 0 0 6px var(--orange-glow);
        animation: pulseOrng 1.6s ease-in-out infinite;
    }

    .s-pending {
        background: #fbbf24;
        box-shadow: 0 0 6px rgba(251, 191, 36, .4);
    }

    .s-down {
        background: #f87171;
        box-shadow: 0 0 6px rgba(248, 113, 113, .4);
    }

    .s-badge {
        margin-left: auto;
        font-size: .67rem;
        font-weight: 800;
        padding: .18rem .55rem;
        border-radius: 999px;
        white-space: nowrap;
    }

    .badge-ok {
        background: rgba(34, 197, 94, .12);
        color: #22c55e;
    }

    .badge-working {
        background: rgba(251, 146, 60, .12);
        color: var(--orange);
    }

    .badge-pending {
        background: rgba(251, 191, 36, .12);
        color: #fbbf24;
    }

    .badge-down {
        background: rgba(248, 113, 113, .12);
        color: #f87171;
    }

    /* ── Done msg ── */
    .done-msg {
        display: none;
        border-radius: 14px;
        background: rgba(34, 197, 94, .1);
        border: 1px solid rgba(34, 197, 94, .25);
        padding: 1rem;
        margin-bottom: 1.4rem;
        font-size: .86rem;
        color: #22c55e;
        font-weight: 600;
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

    .btn-reload {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--orange);
        border: none;
        color: #fff;
        border-radius: 12px;
        padding: .65rem 1.5rem;
        font-size: .85rem;
        font-weight: 700;
        text-decoration: none;
        transition: all .2s;
        cursor: pointer;
    }

    .btn-reload:hover {
        background: var(--orange-dark);
        transform: translateY(-1px);
        color: #fff;
    }

    .btn-reload.spinning i {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
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
        background: rgba(251, 146, 60, .1);
        border-color: var(--orange);
        color: var(--orange);
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

            <div class="error-code">503</div>

            <div class="error-badge">
                <span class="pulse-dot"></span>
                Serviço Indisponível
            </div>

            <div class="error-icon">
                <i class="bi bi-cloud-slash"></i>
            </div>

            <h1 class="error-title">
                Serviço <span>Temporariamente<br>Indisponível</span>
            </h1>

            <p class="error-desc"><?php echo $maint_msg; ?></p>

            <div class="eta-line">
                <i class="bi bi-clock"></i>
                Regresso previsto:
                <strong style="color:rgba(255,255,255,.6)"><?php echo $maint_end_fmt; ?></strong>
            </div>

            <!-- Countdown -->
            <div class="cd-wrap">
                <div class="cd-block">
                    <span class="cd-num" id="cdHours">--</span>
                    <span class="cd-lbl">Horas</span>
                </div>
                <div class="cd-block">
                    <span class="cd-num" id="cdMinutes">--</span>
                    <span class="cd-lbl">Minutos</span>
                </div>
                <div class="cd-block">
                    <span class="cd-num" id="cdSeconds">--</span>
                    <span class="cd-lbl">Segundos</span>
                </div>
            </div>

            <!-- Barra de progresso -->
            <div class="prog-wrap">
                <div class="prog-track">
                    <div class="prog-fill" id="progFill" style="width:100%"></div>
                </div>
                <div class="prog-meta">
                    <span id="progElapsed">—</span>
                    <span id="progPct">—</span>
                </div>
            </div>

            <!-- Resumo rápido -->
            <div class="status-summary">
                <?php if ($count_down > 0): ?>
                <span class="summary-pill pill-down">
                    <i class="bi bi-x-circle-fill"></i>
                    <?php echo $count_down; ?> indisponível<?php echo $count_down !== 1 ? 'eis' : ''; ?>
                </span>
                <?php endif; ?>
                <?php if ($count_working > 0): ?>
                <span class="summary-pill pill-working">
                    <i class="bi bi-arrow-repeat"></i>
                    <?php echo $count_working; ?> a recuperar
                </span>
                <?php endif; ?>
                <?php if ($count_ok > 0): ?>
                <span class="summary-pill pill-ok">
                    <i class="bi bi-check-circle-fill"></i>
                    <?php echo $count_ok; ?> online
                </span>
                <?php endif; ?>
            </div>

            <!-- Estado detalhado dos serviços -->
            <div class="services-title">
                <i class="bi bi-hdd-network me-1"></i>Estado dos Serviços
            </div>
            <ul class="service-list">
                <?php foreach ($services as $key => $state):
                    $label = $service_labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                    $state = array_key_exists($state, $state_map) ? $state : 'down';
                    $s     = $state_map[$state];
                ?>
                <li>
                    <span class="s-dot <?php echo $s['dot']; ?>"></span>
                    <?php echo htmlspecialchars($label); ?>
                    <span class="s-badge <?php echo $s['badge']; ?>">
                        <i class="bi <?php echo $s['icon']; ?> me-1"></i><?php echo $s['text']; ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>

            <!-- Mensagem quando countdown termina -->
            <div class="done-msg" id="doneMsg">
                <i class="bi bi-check-circle-fill me-2"></i>
                Serviço restaurado! A redirecionar para o site…
            </div>

            <div class="divider"></div>

            <!-- Acções -->
            <div class="action-row">
                <button class="btn-reload" id="btnReload" type="button">
                    <i class="bi bi-arrow-clockwise" id="reloadIcon"></i>
                    Tentar novamente
                </button>
                <a href="../page/support/support" class="btn-support">
                    <i class="bi bi-headset"></i> Suporte
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

    <script>
    var SECONDS_REMAINING = <?php echo (int) $seconds_remaining; ?>;
    var SECONDS_TOTAL = <?php echo (int) max(1, $seconds_total ?: $seconds_remaining ?: 1); ?>;
    var SELF_URL = window.location.pathname;
    var HOME_URL = '../home';

    document.addEventListener('DOMContentLoaded', function() {

        // ══════════════════════════════════════════════════════════════
        // COUNTDOWN
        // ══════════════════════════════════════════════════════════════
        var remaining = SECONDS_REMAINING;
        var elH = document.getElementById('cdHours');
        var elM = document.getElementById('cdMinutes');
        var elS = document.getElementById('cdSeconds');
        var elFill = document.getElementById('progFill');
        var elEl = document.getElementById('progElapsed');
        var elPct = document.getElementById('progPct');
        var elDone = document.getElementById('doneMsg');
        var timer = null;

        function pad(n) {
            return String(n).padStart(2, '0');
        }

        function formatElapsed(secs) {
            var h = Math.floor(secs / 3600);
            var m = Math.floor((secs % 3600) / 60);
            var s = secs % 60;
            if (h > 0) return h + 'h ' + pad(m) + 'm ' + pad(s) + 's decorridos';
            if (m > 0) return pad(m) + 'm ' + pad(s) + 's decorridos';
            return s + 's decorridos';
        }

        function tick() {
            if (remaining < 0) return;

            var h = Math.floor(remaining / 3600);
            var m = Math.floor((remaining % 3600) / 60);
            var s = remaining % 60;

            elH.textContent = pad(h);
            elM.textContent = pad(m);
            elS.textContent = pad(s);

            var pct = SECONDS_TOTAL > 0 ? (remaining / SECONDS_TOTAL) * 100 : 0;
            var elapsed = SECONDS_TOTAL - remaining;

            elFill.style.width = Math.max(0, pct).toFixed(1) + '%';
            elEl.textContent = formatElapsed(Math.max(0, elapsed));
            elPct.textContent = Math.round(100 - pct) + '% concluído';

            if (remaining === 0) {
                onDone();
                return;
            }
            remaining--;
        }

        function onDone() {
            clearInterval(timer);
            elFill.style.width = '0%';
            elEl.textContent = 'Serviço restaurado';
            elPct.textContent = '100% concluído';
            if (elDone) elDone.style.display = 'block';
            setTimeout(function() {
                window.location.href = HOME_URL;
            }, 3000);
        }

        if (SECONDS_REMAINING <= 0 && SECONDS_TOTAL <= 1) {
            elH.textContent = '--';
            elM.textContent = '--';
            elS.textContent = '--';
            elFill.style.width = '50%';
            elEl.textContent = 'Tempo indeterminado';
            elPct.textContent = '';
        } else {
            tick();
            timer = setInterval(tick, 1000);
        }

        // ══════════════════════════════════════════════════════════════
        // AUTO-VERIFICAÇÃO a cada 3 minutos
        // ══════════════════════════════════════════════════════════════
        setInterval(function() {
            fetch(SELF_URL, {
                    method: 'GET',
                    credentials: 'same-origin',
                    redirect: 'follow'
                })
                .then(function(r) {
                    // Se o PHP redireccionou para fora do 503, o serviço voltou
                    if (r.redirected && r.url.indexOf('503') === -1) {
                        window.location.href = r.url;
                    }
                })
                .catch(function() {});
        }, 3 * 60 * 1000);

        // ══════════════════════════════════════════════════════════════
        // BOTÃO "TENTAR NOVAMENTE" com cooldown de 10s
        // ══════════════════════════════════════════════════════════════
        var btnReload = document.getElementById('btnReload');
        var reloadIcon = document.getElementById('reloadIcon');
        var reloadCooldown = false;

        btnReload.addEventListener('click', function() {
            if (reloadCooldown) return;

            reloadCooldown = true;
            btnReload.classList.add('spinning');
            btnReload.disabled = true;

            // Tentar ir para a home — se estiver disponível redireccionará
            fetch(HOME_URL, {
                    method: 'HEAD',
                    credentials: 'same-origin',
                    redirect: 'follow'
                })
                .then(function(r) {
                    if (r.ok || r.redirected) {
                        window.location.href = HOME_URL;
                    } else {
                        resetBtn();
                    }
                })
                .catch(function() {
                    resetBtn();
                });

            function resetBtn() {
                var cd = 10;
                var cdInterval = setInterval(function() {
                    cd--;
                    btnReload.innerHTML =
                        '<i class="bi bi-arrow-clockwise" id="reloadIcon"></i> Aguarda ' + cd +
                        's';
                    if (cd <= 0) {
                        clearInterval(cdInterval);
                        btnReload.innerHTML =
                            '<i class="bi bi-arrow-clockwise" id="reloadIcon"></i> Tentar novamente';
                        btnReload.classList.remove('spinning');
                        btnReload.disabled = false;
                        reloadCooldown = false;
                    }
                }, 1000);
            }
        });

    });
    </script>
</body>

</html>