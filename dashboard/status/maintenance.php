<?php
// ══════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Página de Manutenção
// Ficheiro: maintenance.php  (raiz do projecto — fora do dashboard)
//

require_once __DIR__ . '../../../authentic/include/functions.php';

// ══════════════════════════════════════════════════════════════════════
// AJAX — registo de e-mail na tabela _maintenance_notify
// ══════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'];

    // ── Registar e-mail ──────────────────────────────────────────────
    if ($action === 'notify_register') {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

        if (!$email) {
            echo json_encode(['ok' => false, 'message' => 'E-mail inválido.']);
            exit;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        try {
            $db = getDB();

            // Verificar se já existe
            $stmt = $db->prepare(
                "SELECT id_notify FROM _maintenance_notify WHERE email_notify = ? LIMIT 1"
            );
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                echo json_encode([
                    'ok'      => true,
                    'message' => 'Este e-mail já está na lista de avisos.'
                ]);
                exit;
            }

            // Inserir novo registo
            $ins = $db->prepare(
                "INSERT INTO _maintenance_notify (email_notify, ip_notify, sent, creat_notify)
                 VALUES (?, ?, 0, NOW())"
            );
            $ins->execute([$email, $ip]);

            echo json_encode([
                'ok'      => true,
                'message' => 'Registado! Serás avisado assim que a plataforma regressar.'
            ]);

        } catch (PDOException $e) {
            error_log('[maintenance notify] ' . $e->getMessage());
            echo json_encode([
                'ok'      => false,
                'message' => 'Erro interno. Tenta novamente.'
            ]);
        }
        exit;
    }

    // ── Contar registos pendentes ────────────────────────────────────
    if ($action === 'notify_count') {
        try {
            $db  = getDB();
            $cnt = (int) $db->query(
                "SELECT COUNT(*) FROM _maintenance_notify WHERE sent = 0"
            )->fetchColumn();
            echo json_encode(['ok' => true, 'count' => $cnt]);
        } catch (PDOException $e) {
            echo json_encode(['ok' => false, 'count' => 0]);
        }
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Acção inválida.']);
    exit;
}

// ══════════════════════════════════════════════════════════════════════
// LEITURA DA BASE DE DADOS — _platform + _site_config
// ══════════════════════════════════════════════════════════════════════
$platform         = null;
$is_maintenance   = false;
$maint_msg        = 'Estamos a realizar melhorias técnicas para te oferecer a melhor experiência musical.';
$maint_end_ts     = null;
$maint_start_ts   = null;
$seconds_remaining = 0;
$seconds_total     = 0;
$services          = [];
$cfg               = [];

try {
    $db = getDB();

    // Ler registo da plataforma
    $pq       = $db->query("SELECT * FROM _platform WHERE id_platform = 1 LIMIT 1");
    $platform = $pq->fetch(PDO::FETCH_ASSOC);

    if ($platform) {
        $is_maintenance = ($platform['status'] === 'maintenance');

        // Se NÃO estiver em manutenção → redirecionar para o painel imediatamente
        if (!$is_maintenance) {
            header('Location: ../dashboard/painel');
            exit;
        }

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
            if (is_array($decoded)) {
                $services = $decoded;
            }
        }
    }

    // Ler configurações públicas do site
    $cq = $db->query(
        "SELECT config_key, config_value FROM _site_config WHERE is_public = 1"
    );
    foreach ($cq->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cfg[$row['config_key']] = $row['config_value'];
    }

} catch (PDOException $e) {
    // BD indisponível → mostrar a página de manutenção na mesma
    error_log('[maintenance page] DB error: ' . $e->getMessage());
    $is_maintenance = true;
}

// ── Calcular countdown em segundos (base para o JS) ───────────────────
$now = time();

if ($maint_end_ts && $maint_end_ts > $now) {
    $seconds_remaining = $maint_end_ts - $now;
}

if ($maint_start_ts && $maint_end_ts) {
    $seconds_total = max(1, $maint_end_ts - $maint_start_ts);
} elseif ($seconds_remaining > 0) {
    $seconds_total = $seconds_remaining; // fallback sem start definido
}

// ── Formatar data de regresso para exibição ───────────────────────────
$maint_end_fmt = ($maint_end_ts && $maint_end_ts > $now)
    ? date('d/m/Y \à\s H:i', $maint_end_ts) . ' (WAT)'
    : 'Em breve';

// ── Dados de apresentação ──────────────────────────────────────────────
$site_name     = htmlspecialchars($cfg['site_name']     ?? 'Wasom Upfy');
$support_email = htmlspecialchars($cfg['support_email'] ?? 'suporte@wasomupfy.com');
$platform_ver  = htmlspecialchars($platform['version']  ?? '2.0');

// ── Serviços por defeito se JSON não foi configurado ──────────────────
if (empty($services)) {
    $services = [
        'auth'          => 'ok',
        'distribution'  => 'working',
        'analytics'     => 'working',
        'finances'      => 'ok',
        'notifications' => 'pending',
    ];
}

// ── Mapa de labels legíveis para cada serviço ─────────────────────────
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

// ── Mapa de estilos por estado ────────────────────────────────────────
$state_map = [
    'ok'      => ['dot' => 's-ok',      'badge' => 'badge-ok',      'icon' => 'bi-check2',          'text' => 'Online'],
    'working' => ['dot' => 's-working', 'badge' => 'badge-working', 'icon' => 'bi-arrow-repeat',    'text' => 'A actualizar'],
    'pending' => ['dot' => 's-pending', 'badge' => 'badge-pending', 'icon' => 'bi-hourglass-split', 'text' => 'Pendente'],
    'down'    => ['dot' => 's-down',    'badge' => 'badge-down',    'icon' => 'bi-x-lg',            'text' => 'Indisponível'],
];
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
    <meta property="og:locale" content="pt_AO" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="<?php echo $site_name; ?>" />
    <title>Em Manutenção — <?php echo $site_name; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <style>
    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    :root {
        --pink: #FF0089;
        --pink-dark: #c8006e;
        --pink-glow: rgba(255, 0, 137, .25);
        --bg: #08080f;
        --card: rgba(255, 255, 255, .04);
        --border: rgba(255, 255, 255, .08);
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

    /* ── Orbs de fundo ── */
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
        opacity: .16;
        animation: floatOrb 16s ease-in-out infinite;
    }

    .bg-orb:nth-child(1) {
        width: 520px;
        height: 520px;
        background: #FF0089;
        top: -160px;
        left: -110px;
        animation-delay: 0s;
    }

    .bg-orb:nth-child(2) {
        width: 400px;
        height: 400px;
        background: #6f42c1;
        bottom: -120px;
        right: -90px;
        animation-delay: -6s;
    }

    .bg-orb:nth-child(3) {
        width: 300px;
        height: 300px;
        background: #0d6efd;
        top: 42%;
        left: 56%;
        animation-delay: -11s;
    }

    @keyframes floatOrb {

        0%,
        100% {
            transform: translate(0, 0) scale(1);
        }

        33% {
            transform: translate(28px, -38px) scale(1.05);
        }

        66% {
            transform: translate(-18px, 22px) scale(.96);
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
        animation: pulseDot 2s ease-in-out infinite;
    }

    @keyframes pulseDot {

        0%,
        100% {
            box-shadow: 0 0 6px var(--pink);
        }

        50% {
            box-shadow: 0 0 18px var(--pink), 0 0 32px var(--pink-glow);
        }
    }

    /* ── Card ── */
    .main-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 28px;
        padding: 2.8rem 2.4rem;
        max-width: 580px;
        width: 100%;
        text-align: center;
        backdrop-filter: blur(22px);
        -webkit-backdrop-filter: blur(22px);
        box-shadow: 0 0 60px rgba(255, 0, 137, .07), 0 24px 64px rgba(0, 0, 0, .45);
    }

    @media(max-width:576px) {
        .main-card {
            padding: 2rem 1.4rem;
            border-radius: 20px;
        }
    }

    /* ── Ícone animado ── */
    .maint-icon {
        width: 88px;
        height: 88px;
        border-radius: 24px;
        background: rgba(255, 0, 137, .1);
        border: 1.5px solid rgba(255, 0, 137, .22);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.6rem;
        font-size: 2.5rem;
        color: var(--pink);
        animation: iconAnim 3.5s ease-in-out infinite;
    }

    @keyframes iconAnim {

        0%,
        100% {
            transform: translateY(0) rotate(0deg);
        }

        30% {
            transform: translateY(-8px) rotate(-6deg);
        }

        60% {
            transform: translateY(-3px) rotate(4deg);
        }
    }

    /* ── Badge ── */
    .maint-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(255, 0, 137, .1);
        border: 1px solid rgba(255, 0, 137, .28);
        border-radius: 999px;
        padding: 4px 14px;
        font-size: .71rem;
        font-weight: 800;
        color: var(--pink);
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: .9rem;
    }

    .live-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--pink);
        animation: pulseDot 1.4s ease-in-out infinite;
    }

    /* ── Textos ── */
    .maint-title {
        font-size: 1.85rem;
        font-weight: 900;
        color: #fff;
        margin-bottom: .5rem;
        line-height: 1.25;
    }

    .maint-title span {
        color: var(--pink);
    }

    .maint-desc {
        font-size: .87rem;
        color: var(--muted);
        line-height: 1.75;
        margin-bottom: .5rem;
    }

    .maint-endtime {
        font-size: .75rem;
        color: rgba(255, 255, 255, .35);
        margin-bottom: 1.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .maint-endtime i {
        color: var(--pink);
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
        color: var(--pink);
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
        background: linear-gradient(90deg, var(--pink), var(--pink-dark));
        transition: width 1s linear;
        box-shadow: 0 0 10px rgba(255, 0, 137, .45);
    }

    .prog-meta {
        display: flex;
        justify-content: space-between;
        font-size: .68rem;
        color: rgba(255, 255, 255, .25);
    }

    /* ── Serviços ── */
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
        background: var(--pink);
        box-shadow: 0 0 6px var(--pink-glow);
        animation: pulseDot 1.6s ease-in-out infinite;
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
        background: rgba(255, 0, 137, .12);
        color: var(--pink);
    }

    .badge-pending {
        background: rgba(251, 191, 36, .12);
        color: #fbbf24;
    }

    .badge-down {
        background: rgba(248, 113, 113, .12);
        color: #f87171;
    }

    /* ── Formulário ── */
    .notify-section {
        margin-bottom: 1.6rem;
    }

    .notify-lbl {
        font-size: .76rem;
        color: var(--muted);
        display: block;
        text-align: left;
        margin-bottom: .5rem;
    }

    .notify-group {
        display: flex;
        gap: 8px;
    }

    .notify-input {
        flex: 1;
        background: rgba(255, 255, 255, .06);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: .58rem 1rem;
        color: #fff;
        font-size: .83rem;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }

    .notify-input::placeholder {
        color: rgba(255, 255, 255, .25);
    }

    .notify-input:focus {
        border-color: var(--pink);
        box-shadow: 0 0 0 3px rgba(255, 0, 137, .14);
    }

    .btn-notify {
        background: var(--pink);
        border: none;
        color: #fff;
        border-radius: 12px;
        padding: .58rem 1.2rem;
        font-size: .82rem;
        font-weight: 700;
        cursor: pointer;
        transition: all .2s;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .btn-notify:hover {
        background: var(--pink-dark);
        transform: translateY(-1px);
    }

    .btn-notify:disabled {
        opacity: .6;
        cursor: not-allowed;
        transform: none;
    }

    .notify-fb {
        font-size: .74rem;
        margin-top: .4rem;
        display: none;
        text-align: left;
    }

    .notify-fb.ok {
        color: #22c55e;
    }

    .notify-fb.err {
        color: #f87171;
    }

    .notify-count {
        font-size: .7rem;
        color: rgba(255, 255, 255, .2);
        text-align: right;
        margin-top: .3rem;
    }

    /* ── Botão voltar ── */
    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, .06);
        border: 1px solid var(--border);
        color: var(--text);
        border-radius: 12px;
        padding: .6rem 1.5rem;
        font-size: .84rem;
        font-weight: 700;
        text-decoration: none;
        transition: all .2s;
    }

    .btn-back:hover {
        background: rgba(255, 0, 137, .1);
        border-color: var(--pink);
        color: var(--pink);
    }

    /* ── Conclusão ── */
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

    /* ── Rodapé ── */
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

    <!-- ── Fundo animado ── -->
    <div class="bg-canvas">
        <div class="bg-orb"></div>
        <div class="bg-orb"></div>
        <div class="bg-orb"></div>
    </div>

    <div class="page-wrap">

        <!-- Logo -->
        <a class="brand-logo" href="../dashboard/painel">
            <span class="brand-dot"></span>
            <?php echo strtoupper($site_name); ?>
        </a>

        <!-- Card principal -->
        <div class="main-card">

            <div class="maint-icon"><i class="bi bi-tools"></i></div>

            <div class="maint-badge">
                <span class="live-dot"></span>
                Manutenção em curso
            </div>

            <h1 class="maint-title">
                Melhorias em<br><span>progresso</span>
            </h1>

            <p class="maint-desc"><?php echo $maint_msg; ?></p>

            <div class="maint-endtime">
                <i class="bi bi-clock"></i>
                Regresso previsto:
                <strong style="color:rgba(255,255,255,.6)"><?php echo $maint_end_fmt; ?></strong>
            </div>

            <!-- Countdown — segundos calculados no PHP a partir da BD -->
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

            <!-- Estado dos serviços (lido da BD via JSON em _platform.maintenance_services) -->
            <div class="services-title">
                <i class="bi bi-hdd-network me-1"></i>Estado dos Serviços
            </div>
            <ul class="service-list">
                <?php foreach ($services as $key => $state):
            $label = $service_labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
            $state = array_key_exists($state, $state_map) ? $state : 'ok';
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

            <!-- Formulário → e-mail guardado em _maintenance_notify -->
            <div class="notify-section">
                <label class="notify-lbl" for="notifyEmail">
                    <i class="bi bi-envelope me-1"></i>
                    Queres ser avisado quando regressarmos?
                </label>
                <div class="notify-group">
                    <input type="email" id="notifyEmail" class="notify-input" placeholder="O teu endereço de e-mail"
                        autocomplete="email" />
                    <button class="btn-notify" id="btnNotify" type="button">
                        <i class="bi bi-bell me-1"></i>Avisar-me
                    </button>
                </div>
                <div class="notify-fb" id="notifyFb"></div>
                <div class="notify-count" id="notifyCount"></div>
            </div>

            <!-- Mensagem quando o countdown termina -->
            <div class="done-msg" id="doneMsg">
                <i class="bi bi-check-circle-fill me-2"></i>
                Manutenção concluída! A redirecionar para a plataforma…
            </div>

            <a href="../../dashboard/painel" class="btn-back">
                <i class="bi bi-arrow-left"></i>
                Tentar aceder à plataforma
            </a>

        </div><!-- /main-card -->

        <div class="page-footer">
            <p>
                <?php echo $site_name; ?> &nbsp;·&nbsp; Luanda, Angola
                &nbsp;·&nbsp; v<?php echo $platform_ver; ?>
            </p>
            <p>
                <a href="../../dashboard/page/terms">Termos de Uso</a>
                &nbsp;·&nbsp;
                <a href="../../dashboard/page/privacy">Política de Privacidade</a>
                &nbsp;·&nbsp;
                <a href="mailto:<?php echo $support_email; ?>"><?php echo $support_email; ?></a>
            </p>
        </div>

    </div><!-- /page-wrap -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Dados vindos da base de dados — PHP escreve os valores reais aqui -->
    <script>
    var SECONDS_REMAINING = <?php echo (int) $seconds_remaining; ?>;
    var SECONDS_TOTAL = <?php echo (int) max(1, $seconds_total ?: $seconds_remaining ?: 1); ?>;
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {

        // ════════════════════════════════════════════════════════════════
        // COUNTDOWN — alimentado pelos segundos reais da base de dados
        // ════════════════════════════════════════════════════════════════
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
            elEl.textContent = 'Manutenção concluída';
            elPct.textContent = '100% concluído';
            if (elDone) elDone.style.display = 'block';
            // Redirecionar após 3s — o servidor irá confirmar se a manutenção terminou
            setTimeout(function() {
                window.location.href = '../dashboard/painel';
            }, 3000);
        }

        // Sem tempo definido na BD → mostrar traços estáticos
        if (SECONDS_REMAINING <= 0 && SECONDS_TOTAL <= 1) {
            elH.textContent = '--';
            elM.textContent = '--';
            elS.textContent = '--';
            elFill.style.width = '50%';
            elEl.textContent = 'Tempo indeterminado';
            elPct.textContent = '';
        } else {
            tick(); // renderizar imediatamente sem esperar 1s
            timer = setInterval(tick, 1000);
        }

        // ════════════════════════════════════════════════════════════════
        // AUTO-VERIFICAÇÃO — consulta o servidor a cada 5 minutos para
        // detectar se a manutenção foi levantada antes do countdown
        // ════════════════════════════════════════════════════════════════
        setInterval(function() {
            fetch(window.location.pathname, {
                    method: 'GET',
                    credentials: 'same-origin',
                    redirect: 'follow'
                })
                .then(function(r) {
                    // PHP redireccionará para painel se status !== 'maintenance'
                    if (r.redirected && r.url.indexOf('painel') !== -1) {
                        window.location.href = r.url;
                    }
                })
                .catch(function() {
                    /* sem ligação, aguardar */
                });
        }, 5 * 60 * 1000);

        // ════════════════════════════════════════════════════════════════
        // FORMULÁRIO — regista e-mail na tabela _maintenance_notify
        // ════════════════════════════════════════════════════════════════
        var btnNotify = document.getElementById('btnNotify');
        var inputEmail = document.getElementById('notifyEmail');
        var fbEl = document.getElementById('notifyFb');
        var cntEl = document.getElementById('notifyCount');

        function showFb(msg, isOk) {
            fbEl.textContent = msg;
            fbEl.className = 'notify-fb ' + (isOk ? 'ok' : 'err');
            fbEl.style.display = 'block';
        }

        function isValidEmail(e) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);
        }

        function loadCount() {
            var fd = new FormData();
            fd.append('action', 'notify_count');
            fetch(window.location.pathname, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    if (data.ok && data.count > 0) {
                        cntEl.textContent = data.count + ' pessoa' +
                            (data.count !== 1 ? 's' : '') + ' aguardam o regresso';
                    }
                })
                .catch(function() {});
        }

        btnNotify.addEventListener('click', function() {
            var email = inputEmail.value.trim();
            if (!email) {
                showFb('Introduz o teu endereço de e-mail.', false);
                return;
            }
            if (!isValidEmail(email)) {
                showFb('E-mail inválido. Verifica e tenta novamente.', false);
                return;
            }

            btnNotify.disabled = true;
            btnNotify.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>A registar…';

            var fd = new FormData();
            fd.append('action', 'notify_register');
            fd.append('email', email);

            fetch(window.location.pathname, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    if (data.ok) {
                        showFb(data.message, true);
                        inputEmail.value = '';
                        btnNotify.innerHTML = '<i class="bi bi-check2 me-1"></i>Registado';
                        loadCount();
                    } else {
                        showFb(data.message, false);
                        btnNotify.disabled = false;
                        btnNotify.innerHTML = '<i class="bi bi-bell me-1"></i>Avisar-me';
                    }
                })
                .catch(function() {
                    showFb('Erro de rede. Tenta novamente.', false);
                    btnNotify.disabled = false;
                    btnNotify.innerHTML = '<i class="bi bi-bell me-1"></i>Avisar-me';
                });
        });

        inputEmail.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') btnNotify.click();
        });

        // Carregar contagem inicial ao abrir a página
        loadCount();

    });
    </script>
</body>

</html>