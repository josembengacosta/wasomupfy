<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Página de Pagamento
// Arquivo: dashboard/payment.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
require_once __DIR__ . '/../include/platform.php';
startSecureSession();
checkRememberMe();
requireLogin();
$platform = checkDashboardStatus();
$user     = checkUserAccess((int)$_SESSION['id_users']);

$id_users       = (int)$user['id_users'];
$first_name     = htmlspecialchars($user['first_name']);
$user_name      = htmlspecialchars($user['user_name'] ?? '');
$email_verified = (bool)$user['email_verified'];
$plan_selected  = $user['plan_selected'];
$onboard_done   = (bool)($user['onboarding_done'] ?? false);
$user_photo     = $user['photo_user'] ?? null;
$name_artist_band = htmlspecialchars($user['name_artist_band'] ?? 'Cria Perfil Artístico');
$notif_count    = getUnreadNotifCount($id_users);
$db             = getDB();


// ─── Determinar plano ──────────────────────────────────
// Aceita ?plan=single|album|artist|label ou usa o plano guardado no utilizador
$plan_slug           = $_GET['plan'] ?? null;
$requested_intent_id = (int)($_GET['intent'] ?? 0);
$plan                = null;

if ($plan_slug) {
    $plan = getPlanBySlug($plan_slug);
} elseif ($user['plan_selected']) {
    $ps = getDB()->prepare("SELECT * FROM _plans WHERE id_plan = ?");
    $ps->execute([$user['plan_selected']]);
    $plan = $ps->fetch();
}

// Sem plano → redirecionar para página de planos
if (!$plan) {
    redirect('/all-plans');
}

// ─── Verificar se já tem pagamento pendente para este plano ─
$intent = null;

if ($requested_intent_id > 0) {
    $by_id = getDB()->prepare("
        SELECT *
        FROM _payment_intent
        WHERE id_intent = ? AND id_users = ? AND id_plan = ?
        LIMIT 1
    ");
    $by_id->execute([$requested_intent_id, $id_users, $plan['id_plan']]);
    $intent = $by_id->fetch() ?: null;

    if ($intent && $intent['status'] === 'expired') {
        $intent = null;
    }
}

if (!$intent) {
    $existing = getDB()->prepare("
        SELECT *
        FROM _payment_intent
        WHERE id_users = ? AND id_plan = ?
        AND status IN ('created','waiting_payment','under_review')
        AND expires_at > NOW()
        ORDER BY creat_intent DESC LIMIT 1
    ");
    $existing->execute([$id_users, $plan['id_plan']]);
    $intent = $existing->fetch() ?: null;
}

// ─── Criar novo Payment Intent se não existir ────────────
if (!$intent) {
    $ref_code = 'WUF-' . strtoupper(substr(base_convert(bin2hex(random_bytes(6)), 16, 36), 0, 9));
    $expires  = date('Y-m-d H:i:s', time() + 3600); // 60 minutos

    $ins = getDB()->prepare("
        INSERT INTO _payment_intent
        (id_users, id_plan, reference_code, amount_expected, status, expires_at, ip_address, user_agent)
        VALUES (?, ?, ?, ?, 'created', ?, ?, ?)
    ");
    $ins->execute([
        $id_users,
        $plan['id_plan'],
        $ref_code,
        $plan['price_plan'],
        $expires,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);

    $intent_id = (int)getDB()->lastInsertId();

    // Guardar plan_selected no utilizador se ainda não estava
    if (!$user['plan_selected']) {
        getDB()->prepare("UPDATE _users SET plan_selected = ? WHERE id_users = ?")
            ->execute([$plan['id_plan'], $id_users]);
    }

    $intent = [
        'id_intent'       => $intent_id,
        'id_users'        => $id_users,
        'id_plan'         => $plan['id_plan'],
        'reference_code'  => $ref_code,
        'amount_expected' => $plan['price_plan'],
        'expires_at'      => $expires,
        'status'          => 'created',
    ];
}

// ─── Verificar se já tem comprovativo enviado ────────────
$proof_stmt = getDB()->prepare("
    SELECT * FROM _payment_proof WHERE id_intent = ? ORDER BY uploaded_at DESC LIMIT 1
");
$proof_stmt->execute([$intent['id_intent']]);
$proof = $proof_stmt->fetch();

$payment_stmt = getDB()->prepare("
    SELECT p.*, up.status_plan
    FROM _payment p
    LEFT JOIN _user_plan up ON up.id_payment = p.id_payment AND up.id_users = p.id_users
    WHERE p.payment_ref = ? AND p.id_users = ?
    ORDER BY p.id_payment DESC
    LIMIT 1
");
$payment_stmt->execute([$intent['reference_code'], $id_users]);
$payment_row = $payment_stmt->fetch() ?: null;

// ─── Dados para a página ────────────────────────────────
$reference     = $intent['reference_code'];
$amount        = number_format($intent['amount_expected'], 2, ',', '.');
$expires_ts    = strtotime($intent['expires_at']);
$first_name    = htmlspecialchars($user['first_name']);
$plan_name     = htmlspecialchars($plan['name_plan']);
$plan_slug_    = $plan['slug_plan'];
$royalty       = $plan['royalty_rate'];
$max_releases  = $plan['max_releases'] ?? 'Ilimitado';
$max_tracks    = $plan['max_tracks_per_release'] ?? 'Ilimitado';
$max_artists   = $plan['max_artists'] ?? 1;
$intent_id     = $intent['id_intent'];
$trust_score   = (int)($user['trust_score'] ?? 50);
$proof_status  = $proof['status'] ?? null;
$is_processing = $proof
    && $proof_status === 'pending'
    && $payment_row
    && $payment_row['status_payment'] === 'pending';
$auto_approve_ts = $is_processing ? (strtotime($proof['uploaded_at']) + 1800) : null;
$pay_page_url    = APP_URL . '/' . APP_URL_PANEL . '/payment/pay?plan=' . urlencode((string)$plan['slug_plan']);
$intent_page_url = $pay_page_url . '&intent=' . $intent_id;

if ($plan['slug_plan'] === 'label') {
    $review_notice = 'O teu plano so sera activado apos validacao manual da nossa equipa.';
} elseif ($trust_score < 30) {
    $review_notice = 'Depois de enviares o comprovativo, este pagamento passara por validacao manual antes da activacao.';
} else {
    $review_notice = 'Depois de enviares o comprovativo, o pagamento entra em processamento e a activacao costuma acontecer em cerca de 30 minutos.';
}

$proof_notice = ($plan['slug_plan'] === 'label' || $trust_score < 30)
    ? 'O comprovativo sera analisado pela nossa equipa antes da activacao do plano.'
    : 'O comprovativo pode ser validado automaticamente ou encaminhado para revisao manual, conforme o plano e o historico da conta.';

// Ícone por plano
$plan_icons = [
    'single' => 'bi-music-note',
    'album'  => 'bi-disc',
    'artist' => 'bi-person-badge',
    'label'  => 'bi-building',
];
$plan_icon = $plan_icons[$plan_slug_] ?? 'bi-star';

// Determinar step inicial
$initial_step = 1;
if ($proof || in_array($intent['status'], ['approved', 'rejected'], true)) {
    $initial_step = 4; // Já enviou comprovativo
} elseif ($intent['status'] === 'waiting_payment') {
    $initial_step = 3; // Já viu as instruções
}

// Buscar plano ativo atual do usuário (se houver)
$current_active_plan = null;
$stmt = $db->prepare("
    SELECT p.name_plan, p.slug_plan, up.releases_used, up.releases_limit
    FROM _user_plan up
    JOIN _plans p ON p.id_plan = up.id_plan
    WHERE up.id_users = ? AND up.status_plan = 'active'
    LIMIT 1
");
$stmt->execute([$id_users]);
$current_active_plan = $stmt->fetch();

$is_upgrade = $current_active_plan && $current_active_plan['slug_plan'] !== $plan['slug_plan'];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Pagamento — <?php echo $plan_name; ?> — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="<?php echo APP_URL  ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/dashboard-style.css">
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/lastest-style.css">
    <style>
    :root {
        --pink: #FF0089;
        --pink-dark: #cc006e;
        --pink-soft: rgba(255, 0, 137, .10);
        --surface: #0f0f13;
        --card: #18181f;
        --border: #2a2a35;
        --text: #e8e8f0;
        --muted: #e8e8f0;
    }

    * {
        box-sizing: border-box;
    }

    body {
        background: var(--surface);
        color: var(--text);
        font-family: 'Segoe UI', system-ui, sans-serif;
        min-height: 100vh;
    }

    /* ─── Topbar ─── */
    .pay-topbar {
        background: var(--card);
        border-bottom: 1px solid var(--border);
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .pay-topbar .brand {
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--pink);
        letter-spacing: .5px;
    }

    .pay-topbar .back-link {
        color: var(--muted);
        font-size: .9rem;
        text-decoration: none;
    }

    .pay-topbar .back-link:hover {
        color: var(--text);
    }

    /* ─── Layout ─── */
    .pay-wrapper {
        max-width: 820px;
        margin: 2.5rem auto;
        padding: 0 1rem;
    }

    /* ─── Step indicator ─── */
    .steps {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        margin-bottom: 2.5rem;
    }

    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .35rem;
        position: relative;
        flex: 1;
    }

    .step-item:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 16px;
        left: calc(50% + 16px);
        right: calc(-50% + 16px);
        height: 2px;
        background: var(--border);
        transition: background .4s;
    }

    .step-item.done::after {
        background: var(--pink);
    }

    .step-circle {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: var(--border);
        border: 2px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .8rem;
        font-weight: 700;
        transition: all .3s;
        position: relative;
        z-index: 1;
    }

    .step-item.active .step-circle {
        background: var(--pink);
        border-color: var(--pink);
        color: #fff;
        box-shadow: 0 0 0 4px rgba(255, 0, 137, .2);
    }

    .step-item.done .step-circle {
        background: var(--pink);
        border-color: var(--pink);
        color: #fff;
    }

    .step-label {
        font-size: .72rem;
        color: var(--muted);
        text-align: center;
    }

    .step-item.active .step-label {
        color: var(--pink);
        font-weight: 600;
    }

    /* ─── Cards ─── */
    .pay-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1.75rem;
        margin-bottom: 1.25rem;
    }

    .pay-card-title {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: var(--pink);
        margin-bottom: 1rem;
    }

    /* ─── Plan badge ─── */
    .plan-badge-wrap {
        display: flex;
        align-items: center;
        gap: 1.25rem;
    }

    .plan-icon-box {
        width: 56px;
        height: 56px;
        background: var(--pink-soft);
        border: 1px solid rgba(255, 0, 137, .25);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        color: var(--pink);
        flex-shrink: 0;
    }

    .plan-name {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0;
    }

    .plan-desc {
        color: var(--muted);
        font-size: .875rem;
        margin: .25rem 0 0;
    }

    .plan-features {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .feature-pill {
        background: var(--pink-soft);
        border: 1px solid rgba(255, 0, 137, .2);
        color: var(--pink);
        padding: .2rem .75rem;
        border-radius: 20px;
        font-size: .75rem;
        font-weight: 500;
    }

    .price-row {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--border);
    }

    .price-label {
        color: var(--muted);
        font-size: .875rem;
    }

    .price-amount {
        font-size: 2rem;
        font-weight: 800;
        color: #fff;
    }

    .price-currency {
        font-size: .9rem;
        color: var(--muted);
        margin-left: .3rem;
    }

    /* ─── Payment instructions ─── */
    .method-tabs {
        display: flex;
        gap: .75rem;
        margin-bottom: 1.25rem;
    }

    .method-tab {
        flex: 1;
        padding: .75rem;
        background: transparent;
        border: 1.5px solid var(--border);
        border-radius: 10px;
        color: var(--muted);
        cursor: pointer;
        transition: all .2s;
        display: flex;
        align-items: center;
        gap: .6rem;
        font-size: .875rem;
        font-weight: 500;
    }

    .method-tab:hover {
        border-color: var(--pink);
        color: var(--text);
    }

    .method-tab.active {
        border-color: var(--pink);
        background: var(--pink-soft);
        color: var(--pink);
    }

    .method-panel {
        display: none;
    }

    .method-panel.active {
        display: block;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .75rem;
        background: rgba(255, 255, 255, .04);
        border-radius: 8px;
        margin-bottom: .5rem;
    }

    .info-row-label {
        font-size: .8rem;
        color: var(--muted);
    }

    .info-row-value {
        font-weight: 600;
        font-size: .95rem;
    }

    .copy-btn {
        background: transparent;
        border: none;
        color: var(--pink);
        cursor: pointer;
        font-size: .85rem;
        padding: .2rem .5rem;
        border-radius: 5px;
        transition: background .15s;
    }

    .copy-btn:hover {
        background: var(--pink-soft);
    }

    .ref-box {
        background: linear-gradient(135deg, rgba(255, 0, 137, .12), rgba(255, 77, 77, .08));
        border: 1.5px solid rgba(255, 0, 137, .3);
        border-radius: 10px;
        padding: 1rem 1.25rem;
        margin-top: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .ref-code {
        font-size: 1.4rem;
        font-weight: 800;
        letter-spacing: 3px;
        color: var(--pink);
    }

    .ref-note {
        font-size: .75rem;
        color: var(--muted);
        margin-top: .25rem;
    }

    /* ─── Countdown ─── */
    .countdown-bar-wrap {
        margin-top: 1.25rem;
        padding: .875rem 1rem;
        background: rgba(255, 200, 0, .06);
        border: 1px solid rgba(255, 200, 0, .2);
        border-radius: 10px;
    }

    .countdown-label {
        font-size: .78rem;
        color: #e6b800;
        margin-bottom: .4rem;
    }

    .countdown-timer {
        font-size: 1.1rem;
        font-weight: 700;
        color: #ffd700;
        font-variant-numeric: tabular-nums;
    }

    .countdown-bar {
        height: 4px;
        background: var(--border);
        border-radius: 4px;
        margin-top: .5rem;
        overflow: hidden;
    }

    .countdown-fill {
        height: 100%;
        background: linear-gradient(90deg, #ffd700, #ff9500);
        transition: width 1s linear;
    }

    /* ─── Upload ─── */
    .upload-area {
        border: 2px dashed var(--border);
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: all .2s;
        position: relative;
    }

    .upload-area:hover,
    .upload-area.drag-over {
        border-color: var(--pink);
        background: var(--pink-soft);
    }

    .upload-area input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .upload-icon {
        font-size: 2.5rem;
        color: var(--muted);
        margin-bottom: .75rem;
    }

    .upload-area:hover .upload-icon {
        color: var(--pink);
    }

    .upload-types {
        font-size: .78rem;
        color: var(--muted);
        margin-top: .5rem;
    }

    .form-label {
        font-size: .85rem;
        color: var(--muted);
        margin-bottom: .35rem;
    }

    .form-control,
    .form-select {
        background: rgba(255, 255, 255, .04);
        border: 1.5px solid var(--border);
        border-radius: 8px;
        color: var(--text);
        padding: .65rem .9rem;
    }

    .form-control:focus,
    .form-select:focus {
        background: rgba(255, 255, 255, .06);
        border-color: var(--pink);
        color: var(--text);
        box-shadow: 0 0 0 3px rgba(255, 0, 137, .15);
    }

    .form-select option {
        background: #1e1e28;
    }

    /* ─── Buttons ─── */
    .btn-pay {
        background: linear-gradient(135deg, #FF0089, #FF4D4D);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: .8rem 2rem;
        font-weight: 700;
        font-size: 1rem;
        width: 100%;
        transition: all .25s;
        cursor: pointer;
    }

    .btn-pay:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(255, 0, 137, .35);
    }

    .btn-pay:disabled {
        opacity: .55;
        transform: none;
        cursor: not-allowed;
    }

    .btn-outline-pay {
        background: transparent;
        border: 1.5px solid var(--border);
        color: var(--muted);
        border-radius: 10px;
        padding: .7rem 1.5rem;
        font-weight: 500;
        cursor: pointer;
        transition: all .2s;
    }

    .btn-outline-pay:hover {
        border-color: var(--pink);
        color: var(--pink);
    }

    /* ─── Status step 4 ─── */
    .status-icon-wrap {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin: 0 auto 1.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
    }

    .status-pending {
        background: rgba(255, 200, 0, .1);
        border: 2px solid #ffd700;
        color: #ffd700;
    }

    .status-approved {
        background: rgba(0, 200, 100, .1);
        border: 2px solid #00c864;
        color: #00c864;
    }

    .status-rejected {
        background: rgba(255, 50, 50, .1);
        border: 2px solid #ff3232;
        color: #ff3232;
    }

    /* ─── Security footer ─── */
    .pay-security {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        color: var(--muted);
        font-size: .78rem;
        margin-top: 2rem;
        padding-bottom: 2rem;
    }

    /* ─── Alert ─── */
    .alert-dark-warn {
        background: rgba(255, 160, 0, .07);
        border: 1px solid rgba(255, 160, 0, .25);
        border-radius: 10px;
        padding: .875rem 1rem;
        font-size: .85rem;
        color: #e6b800;
    }

    .alert-dark-info {
        background: rgba(0, 140, 255, .07);
        border: 1px solid rgba(0, 140, 255, .2);
        border-radius: 10px;
        padding: .875rem 1rem;
        font-size: .85rem;
        color: #5bc0f5;
    }

    /* ─── Responsive ─── */
    @media (max-width: 576px) {
        .price-amount {
            font-size: 1.5rem;
        }

        .ref-code {
            font-size: 1.1rem;
            letter-spacing: 1.5px;
        }

        .step-label {
            display: none;
        }
    }
    </style>
</head>

<body>

    <!-- Topbar -->
    <div class="pay-topbar">
        <span class="brand text-light" style="
              font-weight: bold;
              box-sizing: border-box;
              text-transform: uppercase;
              font-family: Arial, sans-serif;"><?php echo APP_NAME; ?></span>
        <a class="back-link cursor-pointer" style="cursor:pointer" onclick="window.history.back()"><i
                class="bi bi-arrow-left me-1"></i>Voltar ao
            Painel</a>
    </div>

    <div class="pay-wrapper">

        <!-- Steps indicator -->
        <div class="steps" id="steps-nav">
            <div class="step-item <?php echo $initial_step >= 1 ? ($initial_step > 1 ? 'done' : 'active') : ''; ?>"
                data-step="1">
                <div class="step-circle"><?php echo $initial_step > 1 ? '<i class="bi bi-check"></i>' : '1'; ?></div>
                <span class="step-label">Plano</span>
            </div>
            <div class="step-item <?php echo $initial_step >= 2 ? ($initial_step > 2 ? 'done' : 'active') : ''; ?>"
                data-step="2">
                <div class="step-circle"><?php echo $initial_step > 2 ? '<i class="bi bi-check"></i>' : '2'; ?></div>
                <span class="step-label">Pagamento</span>
            </div>
            <div class="step-item <?php echo $initial_step >= 3 ? ($initial_step > 3 ? 'done' : 'active') : ''; ?>"
                data-step="3">
                <div class="step-circle"><?php echo $initial_step > 3 ? '<i class="bi bi-check"></i>' : '3'; ?></div>
                <span class="step-label">Comprovativo</span>
            </div>
            <div class="step-item <?php echo $initial_step >= 4 ? 'active' : ''; ?>" data-step="4">
                <div class="step-circle">4</div>
                <span class="step-label">Confirmação</span>
            </div>
        </div>

        <!-- ═══════════════════════════════════════ -->
        <!-- STEP 1 — Resumo do plano               -->
        <!-- ═══════════════════════════════════════ -->
        <div class="pay-step" id="step-1" <?php echo $initial_step !== 1 ? 'style="display:none"' : ''; ?>>
            <div class="pay-card">
                <div class="pay-card-title"><i class="bi bi-receipt me-1"></i>Resumo do Pedido</div>

                <div class="plan-badge-wrap">
                    <div class="plan-icon-box"><i class="bi <?php echo $plan_icon; ?>"></i></div>
                    <div>
                        <p class="plan-name">Plano <?php echo $plan_name; ?></p>
                        <p class="plan-desc"><?php echo htmlspecialchars($plan['description_plan'] ?? ''); ?></p>
                    </div>
                </div>

                <div class="plan-features">
                    <span class="feature-pill"><i class="bi bi-percent me-1"></i><?php echo $royalty; ?>%
                        royalties</span>
                    <?php if ($max_releases !== 'Ilimitado'): ?>
                    <span class="feature-pill"><i class="bi bi-disc me-1"></i><?php echo $max_releases; ?>
                        lançamento(s)</span>
                    <?php else: ?>
                    <span class="feature-pill"><i class="bi bi-infinity me-1"></i>Lançamentos ilimitados</span>
                    <?php endif; ?>
                    <?php if ($max_tracks !== 'Ilimitado'): ?>
                    <span class="feature-pill"><i class="bi bi-music-note-list me-1"></i><?php echo $max_tracks; ?>
                        faixas/lançamento</span>
                    <?php else: ?>
                    <span class="feature-pill"><i class="bi bi-music-note-list me-1"></i>Faixas ilimitadas</span>
                    <?php endif; ?>
                    <?php if ($max_artists > 1): ?>
                    <span class="feature-pill"><i class="bi bi-people me-1"></i>Até <?php echo $max_artists; ?>
                        artistas</span>
                    <?php endif; ?>
                    <span class="feature-pill"><i class="bi bi-globe me-1"></i>+150 plataformas</span>
                </div>

                <div class="price-row">
                    <div>
                        <div class="price-label">Total a pagar</div>
                        <?php if ($plan['type_plan'] === 'subscription'): ?>
                        <div class="price-label" style="margin-top:.2rem;font-size:.75rem">Plano anual</div>
                        <?php else: ?>
                        <div class="price-label" style="margin-top:.2rem;font-size:.75rem">Por lançamento</div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="price-amount"><?php echo $amount; ?></span>
                        <span class="price-currency">AOA</span>
                    </div>
                </div>

                <?php if ($is_upgrade): ?>
                <div class="alert alert-warning d-flex gap-2 mb-3"
                    style="background:rgba(255,193,7,.1);border:1px solid rgba(255,193,7,.3);border-radius:10px">
                    <i class="bi bi-arrow-repeat fs-5"></i>
                    <div>
                        <strong>Estás a alterar o teu plano</strong><br>
                        <span style="font-size:.85rem">
                            Atualmente tens o plano
                            <strong><?php echo htmlspecialchars($current_active_plan['name_plan']); ?></strong>.
                            Ao activares o plano <strong><?php echo $plan_name; ?></strong>, o plano anterior será
                            desactivado e os lançamentos restantes não serão transferidos.
                        </span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($current_active_plan && $current_active_plan['releases_limit'] !== null): ?>
                <div class="mb-3 p-3 rounded" style="background:rgba(255,255,255,.04);border:1px solid var(--border)">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Uso do plano atual</span>
                        <strong><?php echo $current_active_plan['releases_used']; ?> /
                            <?php echo $current_active_plan['releases_limit']; ?></strong>
                    </div>
                    <div class="progress" style="height:6px">
                        <div class="progress-bar bg-warning"
                            style="width:<?php echo min(100, ($current_active_plan['releases_used'] / $current_active_plan['releases_limit']) * 100); ?>%">
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="alert-dark-info mb-3">
                <i class="bi bi-info-circle me-2"></i>
                Tens <strong>60 minutos</strong> para completar o pagamento após avançar.
                O teu plano só será activado após confirmação do comprovativo pela nossa equipa.
            </div>

            <button class="btn-pay" onclick="goStep(2)">
                <i class="bi bi-lock-fill me-2"></i>Proceder ao Pagamento
            </button>
        </div>

        <!-- ═══════════════════════════════════════ -->
        <!-- STEP 2 — Instruções de pagamento       -->
        <!-- ═══════════════════════════════════════ -->
        <div class="pay-step" id="step-2" <?php echo $initial_step !== 2 ? 'style="display:none"' : ''; ?>>

            <div class="pay-card">
                <div class="pay-card-title"><i class="bi bi-credit-card me-1"></i>Como Pagar</div>

                <div class="method-tabs">
                    <button class="method-tab active" onclick="switchMethod('express', this)">
                        <i class="bi bi-phone-fill"></i>Multicaixa Express
                    </button>
                    <button class="method-tab" onclick="switchMethod('iban', this)">
                        <i class="bi bi-bank"></i>Transferência Bancária
                    </button>
                </div>

                <!-- Método: Express -->
                <div class="method-panel active" id="panel-express">
                    <div class="alert-dark-warn mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Envia o valor exacto de <strong><?php echo $amount; ?> AOA</strong> para o número abaixo.
                        No campo de descrição/referência, indica o código de referência.
                    </div>
                    <div class="info-row">
                        <div>
                            <div class="info-row-label">Número Multicaixa Express</div>
                            <div class="info-row-value">+244 9XX XXX XXX</div>
                        </div>
                        <button class="copy-btn" onclick="copyText('+244900000000', this)">
                            <i class="bi bi-clipboard me-1"></i>Copiar
                        </button>
                    </div>
                    <div class="info-row">
                        <div>
                            <div class="info-row-label">Nome do destinatário</div>
                            <div class="info-row-value">Wasom Upfy Lda</div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div>
                            <div class="info-row-label">Valor exacto a enviar</div>
                            <div class="info-row-value" style="color:var(--pink)"><?php echo $amount; ?> AOA</div>
                        </div>
                        <button class="copy-btn"
                            onclick="copyText('<?php echo str_replace(',', '.', $amount); ?>', this)">
                            <i class="bi bi-clipboard me-1"></i>Copiar
                        </button>
                    </div>
                </div>

                <!-- Método: IBAN -->
                <div class="method-panel" id="panel-iban">
                    <div class="alert-dark-warn mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Faz a transferência do valor exacto de <strong><?php echo $amount; ?> AOA</strong>.
                        Na descrição da transferência, inclui obrigatoriamente o código de referência.
                    </div>
                    <div class="info-row">
                        <div>
                            <div class="info-row-label">IBAN</div>
                            <div class="info-row-value" style="font-size:.85rem;letter-spacing:1px">AO06 XXXX XXXX XXXX
                                XXXX XXXX X</div>
                        </div>
                        <button class="copy-btn" onclick="copyText('AO06XXXXXXXXXXXXXXXXXXXX', this)">
                            <i class="bi bi-clipboard me-1"></i>Copiar
                        </button>
                    </div>
                    <div class="info-row">
                        <div>
                            <div class="info-row-label">Titular da conta</div>
                            <div class="info-row-value">Wasom Upfy Lda</div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div>
                            <div class="info-row-label">Banco</div>
                            <div class="info-row-value">Banco de Fomento Angola (BFA)</div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div>
                            <div class="info-row-label">Valor exacto</div>
                            <div class="info-row-value" style="color:var(--pink)"><?php echo $amount; ?> AOA</div>
                        </div>
                    </div>
                </div>

                <!-- Referência interna -->
                <div class="ref-box">
                    <div>
                        <div style="font-size:.75rem;color:var(--muted);margin-bottom:.3rem">
                            <i class="bi bi-tag me-1"></i>Código de Referência (inclui na descrição do pagamento)
                        </div>
                        <div class="ref-code"><?php echo $reference; ?></div>
                        <div class="ref-note">Este código identifica o teu pagamento no nosso sistema</div>
                    </div>
                    <button class="copy-btn" style="font-size:1rem"
                        onclick="copyText('<?php echo $reference; ?>', this)" title="Copiar referência">
                        <i class="bi bi-clipboard2-check"></i>
                    </button>
                </div>

                <!-- Countdown -->
                <div class="countdown-bar-wrap">
                    <div class="countdown-label"><i class="bi bi-clock me-1"></i>Tempo restante para completar</div>
                    <div class="countdown-timer" id="countdown-timer">--:--</div>
                    <div class="countdown-bar">
                        <div class="countdown-fill" id="countdown-fill" style="width:100%"></div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn-outline-pay" onclick="goStep(1)">
                    <i class="bi bi-arrow-left me-1"></i>Voltar
                </button>
                <button class="btn-pay" onclick="confirmPaymentSeen()" style="flex:1">
                    Já efectuei o pagamento <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>

        <!-- ═══════════════════════════════════════ -->
        <!-- STEP 3 — Upload do comprovativo        -->
        <!-- ═══════════════════════════════════════ -->
        <div class="pay-step" id="step-3" <?php echo $initial_step !== 3 ? 'style="display:none"' : ''; ?>>
            <div class="pay-card">
                <div class="pay-card-title"><i class="bi bi-upload me-1"></i>Enviar Comprovativo</div>

                <form id="proof-form" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
                    <input type="hidden" name="intent_id" value="<?php echo $intent_id; ?>" />
                    <input type="hidden" name="plan_id" value="<?php echo $plan['id_plan']; ?>" />
                    <input type="hidden" name="amount" value="<?php echo $plan['price_plan']; ?>" />
                    <input type="hidden" name="method_used" id="method_used" value="express" />

                    <!-- Upload area -->
                    <div class="upload-area mb-3" id="upload-area">
                        <input type="file" name="comprovativo" id="comprovativo"
                            accept="image/jpeg,image/png,image/webp,application/pdf" required />
                        <div class="upload-icon"><i class="bi bi-cloud-upload" id="upload-icon-i"></i></div>
                        <div id="upload-label" style="font-weight:600">Clica ou arrasta o comprovativo aqui</div>
                        <div class="upload-types">JPG, PNG, WebP ou PDF · Máximo 5 MB</div>
                    </div>
                    <div id="file-preview" style="display:none" class="mb-3 text-center"></div>

                    <!-- Método de pagamento usado -->
                    <div class="mb-3">
                        <label class="form-label">Método de pagamento utilizado</label>
                        <select class="form-select" name="method" id="method-select" required>
                            <option value="">Selecciona o método</option>
                            <option value="express">Multicaixa Express</option>
                            <option value="iban">Transferência Bancária (IBAN)</option>
                        </select>
                    </div>

                    <!-- Nome completo do titular -->
                    <div class="mb-3">
                        <label class="form-label">Nome completo do titular da conta que efectuou o pagamento</label>
                        <input type="text" class="form-control" name="full_name"
                            value="<?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['second_name'] ?? '')); ?>"
                            placeholder="Nome como aparece no extracto" required minlength="4" maxlength="100" />
                    </div>

                    <!-- Telefone -->
                    <div class="mb-3">
                        <label class="form-label">Telefone de contacto</label>
                        <input type="tel" class="form-control" name="phone"
                            value="<?php echo htmlspecialchars($user['tel_user'] ?? ''); ?>"
                            placeholder="+244 9XX XXX XXX" maxlength="20" />
                    </div>

                    <!-- Referência (readonly, auto-preenchida) -->
                    <div class="mb-3">
                        <label class="form-label">Código de referência</label>
                        <div class="info-row">
                            <div class="info-row-value"><?php echo $reference; ?></div>
                            <span style="font-size:.75rem;color:var(--muted)">Automático</span>
                        </div>
                    </div>

                    <div class="alert-dark-warn mb-3">
                        <i class="bi bi-shield-check me-2"></i>
                        O comprovativo é analisado pela nossa equipa. <strong>Comprovativos falsos resultam em suspensão
                            permanente da conta.</strong>
                    </div>

                    <!-- Erro do servidor -->
                    <div id="upload-error" style="display:none" class="alert-dark-warn mb-3" style="color:#ff6060">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn-outline-pay" onclick="goStep(2)">
                            <i class="bi bi-arrow-left me-1"></i>Voltar
                        </button>
                        <button type="submit" class="btn-pay" id="submit-proof" style="flex:1">
                            <i class="bi bi-send me-2"></i>Enviar Comprovativo
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ═══════════════════════════════════════ -->
        <!-- STEP 4 — Estado / Confirmação          -->
        <!-- ═══════════════════════════════════════ -->
        <div class="pay-step" id="step-4" <?php echo $initial_step !== 4 ? 'style="display:none"' : ''; ?>>
            <div class="pay-card text-center">
                <?php
                $proof_status = $proof['status'] ?? 'pending';
                if ($proof_status === 'validated' || $intent['status'] === 'approved'): ?>

                <div class="status-icon-wrap status-approved">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h4 class="fw-bold mb-2">Pagamento Confirmado!</h4>
                <p class="text-muted mb-3">O teu plano <strong><?php echo $plan_name; ?></strong> foi activado com
                    sucesso.</p>
                <a href="<?php  echo APP_URL .'/'. APP_URL_PANEL ?>/painel" class="btn-pay d-block">
                    <i class="bi bi-speedometer2 me-2"></i>Ir ao Painel
                </a>

                <?php elseif ($proof_status === 'rejected' || $intent['status'] === 'rejected'): ?>

                <div class="status-icon-wrap status-rejected">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <h4 class="fw-bold mb-2">Comprovativo Rejeitado</h4>
                <?php if ($proof['reject_reason']): ?>
                <div class="alert-dark-warn mb-3" style="text-align:left">
                    <i class="bi bi-chat-left-text me-2"></i>
                    <strong>Motivo:</strong> <?php echo htmlspecialchars($proof['reject_reason']); ?>
                </div>
                <?php endif; ?>
                <p class="text-muted mb-3">Podes enviar um novo comprovativo correcto.</p>
                <button class="btn-pay" onclick="goStep(2)">
                    <i class="bi bi-arrow-repeat me-2"></i>Tentar Novamente
                </button>

                <?php else: // pending / under_review 
                ?>

                <div class="status-icon-wrap status-pending">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <h4 class="fw-bold mb-2">Em Análise</h4>
                <p class="text-muted">
                    O teu comprovativo foi recebido e está a ser verificado pela nossa equipa.
                    Normalmente demoramos <strong>até 24 horas</strong>.
                    Vais receber uma notificação quando estiver pronto.
                </p>
                <hr style="border-color:var(--border);margin:1.25rem 0">

                <div class="info-row text-start mb-2">
                    <div>
                        <div class="info-row-label">Plano</div>
                        <div class="info-row-value"><?php echo $plan_name; ?></div>
                    </div>
                </div>
                <div class="info-row text-start mb-2">
                    <div>
                        <div class="info-row-label">Referência</div>
                        <div class="info-row-value"><?php echo $reference; ?></div>
                    </div>
                </div>
                <div class="info-row text-start mb-3">
                    <div>
                        <div class="info-row-label">Valor</div>
                        <div class="info-row-value" style="color:var(--pink)"><?php echo $amount; ?> AOA</div>
                    </div>
                </div>

                <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/painel" class="btn-outline-pay d-block"
                    style="text-align:center">
                    <i class="bi bi-house me-2"></i>Voltar ao Painel
                </a>

                <?php endif; ?>
            </div>
        </div>

        <!-- Security footer -->
        <div class="pay-security">
            <i class="bi bi-shield-lock-fill"></i>
            Ligação segura · Dados protegidos · Wasom Upfy &copy; <?php echo date('Y'); ?>
        </div>

    </div><!-- /pay-wrapper -->

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>

    <script>
    // ══════════════════════════════════════════════
    // CONFIG
    // ══════════════════════════════════════════════
    const EXPIRES_AT = <?php echo $expires_ts; ?> * 1000;
    const TOTAL_SECS = 3600;
    const AUTO_APPROVE_WINDOW_SECS = 1800;
    const BASE_URL = <?php echo json_encode(APP_URL . '/' . APP_URL_PANEL); ?>;
    const PAY_PAGE_URL = <?php echo json_encode($pay_page_url); ?>;
    const INTENT_PAGE_URL = <?php echo json_encode($intent_page_url); ?>;
    const INTENT_ID = <?php echo $intent_id; ?>;
    const CSRF = <?php echo json_encode($_SESSION['csrf_token']); ?>;
    const PLAN_NAME = <?php echo json_encode($plan_name); ?>;
    const REFERENCE_CODE = <?php echo json_encode($reference); ?>;
    const AMOUNT_LABEL = <?php echo json_encode($amount . ' AOA'); ?>;
    const PROOF_STATUS = <?php echo json_encode($proof_status); ?>;
    const INTENT_STATUS = <?php echo json_encode($intent['status']); ?>;
    const REJECT_REASON = <?php echo json_encode($proof['reject_reason'] ?? ''); ?>;
    const IS_PROCESSING = <?php echo $is_processing ? 'true' : 'false'; ?>;
    const INITIAL_AUTO_APPROVE_TS = <?php echo $auto_approve_ts ?: 'null'; ?>;
    let currentStep = <?php echo $initial_step; ?>;
    let autoApproveInterval = null;

    // ══════════════════════════════════════════════
    // NAVEGAÇÃO ENTRE STEPS
    // ══════════════════════════════════════════════
    function goStep(n) {
        document.querySelectorAll('.pay-step').forEach(s => s.style.display = 'none');
        document.getElementById('step-' + n).style.display = 'block';
        currentStep = n;
        updateStepsNav(n);
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function updateStepsNav(active) {
        document.querySelectorAll('.step-item').forEach(item => {
            const s = parseInt(item.dataset.step);
            const circle = item.querySelector('.step-circle');
            item.classList.remove('active', 'done');
            if (s < active) {
                item.classList.add('done');
                circle.innerHTML = '<i class="bi bi-check"></i>';
            } else if (s === active) {
                item.classList.add('active');
                circle.textContent = s;
            } else {
                circle.textContent = s;
            }
        });
    }

    function statusCard() {
        const step4 = document.getElementById('step-4');
        return step4 ? step4.querySelector('.pay-card') : null;
    }

    function syncIntentUrl() {
        window.history.replaceState({}, '', INTENT_PAGE_URL);
    }

    function stopAutoApproveCountdown() {
        if (autoApproveInterval) {
            clearInterval(autoApproveInterval);
            autoApproveInterval = null;
        }
    }

    function renderRejectedStatus(message = REJECT_REASON) {
        const card = statusCard();
        if (!card) return;

        card.innerHTML = `
                <div class="status-icon-wrap status-rejected">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <h4 class="fw-bold mb-2">Comprovativo Rejeitado</h4>
                ${message ? `
                    <div class="alert-dark-warn mb-3" style="text-align:left">
                        <i class="bi bi-chat-left-text me-2"></i>
                        <strong>Motivo:</strong> ${message}
                    </div>
                ` : ''}
                <p class="text-muted mb-3">Cria uma nova referencia e envia um novo comprovativo correcto.</p>
                <a href="${PAY_PAGE_URL}" class="btn-pay d-block">
                    <i class="bi bi-arrow-repeat me-2"></i>Tentar Novamente
                </a>`;
    }

    function renderReviewStatus(message) {
        const card = statusCard();
        if (!card) return;

        card.innerHTML = `
                <div class="status-icon-wrap status-pending">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <h4 class="fw-bold mb-2">Em Revisao</h4>
                <p class="text-muted mb-3">${message}</p>
                <div class="info-row text-start mb-2">
                    <div>
                        <div class="info-row-label">Plano</div>
                        <div class="info-row-value">${PLAN_NAME}</div>
                    </div>
                </div>
                <div class="info-row text-start mb-3">
                    <div>
                        <div class="info-row-label">Referencia</div>
                        <div class="info-row-value">${REFERENCE_CODE}</div>
                    </div>
                </div>
                <a href="${BASE_URL}/painel" class="btn-outline-pay d-block" style="text-align:center">
                    <i class="bi bi-house me-2"></i>Voltar ao Painel
                </a>`;
    }

    function renderProcessingStatus(message, autoApproveTs) {
        const card = statusCard();
        if (!card) return;

        card.innerHTML = `
                <div class="status-icon-wrap" style="background:rgba(99,102,241,.1);border:2px solid #6366f1;color:#6366f1">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <h4 class="fw-bold mb-2">A processar pagamento</h4>
                <p class="text-muted mb-3">${message}</p>
                <div id="processing-timer" style="background:rgba(99,102,241,.07);border:1px solid rgba(99,102,241,.25);border-radius:10px;padding:.875rem 1rem;margin-bottom:1.25rem">
                    <div style="font-size:.78rem;color:#818cf8;margin-bottom:.35rem">Activacao automatica em</div>
                    <div id="auto-approve-countdown" style="font-size:1.4rem;font-weight:700;color:#a5b4fc;font-variant-numeric:tabular-nums">--:--</div>
                    <div class="countdown-bar mt-2">
                        <div id="auto-approve-fill" class="countdown-fill" style="background:linear-gradient(90deg,#6366f1,#818cf8);width:100%"></div>
                    </div>
                </div>
                <div class="info-row text-start mb-2">
                    <div>
                        <div class="info-row-label">Plano</div>
                        <div class="info-row-value">${PLAN_NAME}</div>
                    </div>
                </div>
                <div class="info-row text-start mb-2">
                    <div>
                        <div class="info-row-label">Referencia</div>
                        <div class="info-row-value">${REFERENCE_CODE}</div>
                    </div>
                </div>
                <div class="info-row text-start mb-3">
                    <div>
                        <div class="info-row-label">Valor</div>
                        <div class="info-row-value" style="color:var(--pink)">${AMOUNT_LABEL}</div>
                    </div>
                </div>
                <p style="font-size:.8rem;color:var(--muted);margin-top:1rem">
                    Vais receber uma notificacao e um email quando o plano for activado.
                </p>
                <a href="${BASE_URL}/painel" class="btn-outline-pay d-block" style="text-align:center">
                    <i class="bi bi-house me-2"></i>Voltar ao Painel
                </a>`;

        startAutoApproveCountdown(autoApproveTs);
    }

    function startAutoApproveCountdown(autoApproveTs) {
        stopAutoApproveCountdown();
        if (!autoApproveTs) return;

        const timerEl = document.getElementById('auto-approve-countdown');
        const fillEl = document.getElementById('auto-approve-fill');
        if (!timerEl || !fillEl) return;

        const tick = () => {
            const remaining = Math.max(0, autoApproveTs - Math.floor(Date.now() / 1000));
            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            const percentage = Math.max(0, Math.min(100, (remaining / AUTO_APPROVE_WINDOW_SECS) * 100));

            timerEl.textContent = remaining > 0 ?
                `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}` :
                'A verificar...';
            fillEl.style.width = `${percentage}%`;

            if (remaining === 0) {
                stopAutoApproveCountdown();
                setTimeout(() => {
                    window.location.href = INTENT_PAGE_URL;
                }, 3000);
            }
        };

        tick();
        autoApproveInterval = setInterval(tick, 1000);
    }

    function syncInitialStep4State() {
        if (currentStep !== 4) return;

        syncIntentUrl();

        if (PROOF_STATUS === 'rejected' || INTENT_STATUS === 'rejected') {
            renderRejectedStatus(REJECT_REASON);
            return;
        }

        if (IS_PROCESSING) {
            renderProcessingStatus(
                'O teu comprovativo foi recebido. O plano sera activado automaticamente assim que a validacao terminar, normalmente em cerca de 30 minutos.',
                INITIAL_AUTO_APPROVE_TS
            );
            return;
        }

        if (PROOF_STATUS === 'pending' || INTENT_STATUS === 'under_review') {
            renderReviewStatus(
                'O teu comprovativo foi recebido e esta em validacao manual. Vais receber uma notificacao assim que o processo estiver concluido.'
            );
        }
    }

    // ══════════════════════════════════════════════
    // MÉTODO DE PAGAMENTO (Express / IBAN)
    // ══════════════════════════════════════════════
    function switchMethod(method, btn) {
        document.querySelectorAll('.method-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.method-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('panel-' + method).classList.add('active');
        document.getElementById('method_used').value = method;
        const sel = document.getElementById('method-select');
        if (sel) sel.value = method;
    }

    // ══════════════════════════════════════════════
    // COPIAR PARA CLIPBOARD
    // ══════════════════════════════════════════════
    function copyText(text, btn) {
        navigator.clipboard.writeText(text).then(() => {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Copiado!';
            btn.style.color = '#00c864';
            setTimeout(() => {
                btn.innerHTML = orig;
                btn.style.color = '';
            }, 2000);
        });
    }

    // ══════════════════════════════════════════════
    // COUNTDOWN
    // ══════════════════════════════════════════════
    function startCountdown() {
        const timer = document.getElementById('countdown-timer');
        const fill = document.getElementById('countdown-fill');
        if (!timer) return;

        function tick() {
            const remaining = Math.max(0, Math.floor((EXPIRES_AT - Date.now()) / 1000));
            const m = Math.floor(remaining / 60);
            const s = remaining % 60;
            timer.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            const pct = (remaining / TOTAL_SECS) * 100;
            fill.style.width = pct + '%';
            if (remaining <= 300) { // últimos 5 min → vermelho
                fill.style.background = 'linear-gradient(90deg, #ff4444, #ff2222)';
                timer.style.color = '#ff4444';
            }
            if (remaining === 0) {
                timer.textContent = 'EXPIRADO';
                clearInterval(iv);
                document.getElementById('step-2').innerHTML +=
                    '<div class="alert-dark-warn mt-3"><i class="bi bi-clock-history me-2"></i>A tua referência expirou. <a href="" style="color:var(--pink)">Clica aqui para gerar uma nova.</a></div>';
            }
        }
        tick();
        const iv = setInterval(tick, 1000);
    }
    startCountdown();
    syncInitialStep4State();

    // ══════════════════════════════════════════════
    // CONFIRMAR QUE VIU AS INSTRUÇÕES
    // ══════════════════════════════════════════════
    function confirmPaymentSeen() {
        fetch('payment_process', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'seen',
                intent_id: INTENT_ID,
                csrf: CSRF
            })
        }).finally(() => goStep(3));
    }

    // ══════════════════════════════════════════════
    // DRAG & DROP + PREVIEW FICHEIRO
    // ══════════════════════════════════════════════
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('comprovativo');

    uploadArea.addEventListener('dragover', e => {
        e.preventDefault();
        uploadArea.classList.add('drag-over');
    });
    uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag-over'));
    uploadArea.addEventListener('drop', e => {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });

    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (!file) return;
        document.getElementById('upload-icon-i').className = 'bi bi-file-earmark-check';
        document.getElementById('upload-label').textContent = file.name;

        const preview = document.getElementById('file-preview');
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => {
                preview.style.display = 'block';
                preview.innerHTML =
                    `<img src="${e.target.result}" style="max-height:150px;border-radius:8px;border:1px solid var(--border)" alt="preview">`;
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'block';
            preview.innerHTML =
                `<div style="color:var(--muted)"><i class="bi bi-file-pdf fs-3"></i><br><small>PDF seleccionado</small></div>`;
        }
    });

    // ══════════════════════════════════════════════
    // SUBMETER COMPROVATIVO
    // ══════════════════════════════════════════════
    document.getElementById('proof-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const btn = document.getElementById('submit-proof');
        const err = document.getElementById('upload-error');
        err.style.display = 'none';

        // Validar ficheiro
        const file = fileInput.files[0];
        if (!file) {
            err.style.display = 'block';
            err.textContent = 'Selecciona um comprovativo antes de enviar.';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            err.style.display = 'block';
            err.textContent = 'O ficheiro é muito grande. Máximo 5 MB.';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A enviar...';

        const formData = new FormData(this);
        fetch('payment_process', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    // Sempre aprovado automaticamente — vai directo para step 4 (estado aprovado)
                    // Recarrega a página para mostrar o estado correcto vindo da BD
                    window.location.href = BASE_URL + '/painel?plan=&approved=1#step4';
                } else {
                    err.style.display = 'block';
                    err.textContent = data.message || 'Erro ao enviar. Tenta novamente.';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-send me-2"></i>Enviar Comprovativo';
                }
            })
            .catch(() => {
                err.style.display = 'block';
                err.textContent = 'Erro de ligação. Verifica a tua internet e tenta novamente.';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send me-2"></i>Enviar Comprovativo';
            });
    });

    document.getElementById('proof-form').addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        const btn = document.getElementById('submit-proof');
        const err = document.getElementById('upload-error');
        err.style.display = 'none';

        const file = fileInput.files[0];
        if (!file) {
            err.style.display = 'block';
            err.textContent = 'Selecciona um comprovativo antes de enviar.';
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            err.style.display = 'block';
            err.textContent = 'O ficheiro e muito grande. Maximo 5 MB.';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A enviar...';

        fetch('payment_process', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) {
                    err.style.display = 'block';
                    err.textContent = data.message || 'Erro ao enviar. Tenta novamente.';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-send me-2"></i>Enviar Comprovativo';
                    return;
                }

                syncIntentUrl();
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send me-2"></i>Enviar Comprovativo';

                if (data.state === 'processing') {
                    renderProcessingStatus(
                        data.message || 'Comprovativo recebido. O teu plano esta em processamento.',
                        data.auto_approve_ts || null
                    );
                } else {
                    renderReviewStatus(
                        data.message || 'O teu comprovativo foi recebido e esta em validacao manual.'
                    );
                }

                goStep(4);
            })
            .catch(() => {
                err.style.display = 'block';
                err.textContent = 'Erro de ligacao. Verifica a tua internet e tenta novamente.';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send me-2"></i>Enviar Comprovativo';
            });
    }, true);
    </script>
</body>

</html>