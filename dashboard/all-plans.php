<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Página de Planos
// Arquivo: dashboard/all-plans.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../authentic/include/functions.php';
require_once __DIR__ . '/include/platform.php';
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

// ── Saldo ─────────────────────────────────────
$w = $db->prepare('SELECT balance_aoa FROM _wallet WHERE id_users = ?');
$w->execute([$id_users]);
$balance = $w->fetch() ?: ['balance_aoa' => 0];

// ── Plano ─────────────────────────────────────
$plan_id     = (int)$user['plan_selected'];
$plan        = null;
$max_artists = 1;
if ($plan_id) {
    $ps = $db->prepare('SELECT * FROM _plans WHERE id_plan = ?');
    $ps->execute([$plan_id]);
    $plan = $ps->fetch();
    if ($plan) $max_artists = (int)($plan['max_artists'] ?? 1);
}
$plan_name = $plan ? htmlspecialchars($plan['name_plan']) : 'Sem plano';

// ── Plano ─────────────────────────────────────
$plan      = null;
$plan_paid = ($user['status_user'] === 'active' && !empty($user['plan_activated_at']));
if ($plan_selected) {
    $ps = $db->prepare('SELECT * FROM _plans WHERE id_plan = ?');
    $ps->execute([$plan_selected]);
    $plan = $ps->fetch();
}

// Adicionar verificação de expiração do plano
$plan_expired = false;
if ($plan_paid && !empty($user['plan_expires_at'])) {
    $plan_expired = strtotime($user['plan_expires_at']) < time();
}

// ── Artistas ──────────────────────────────────
$as = $db->prepare('SELECT COUNT(*) AS total FROM _artist WHERE id_users = ?');
$as->execute([$id_users]);
$has_artist = (int)($as->fetch()['total'] ?? 0) > 0;

// ── Conta bancária ────────────────────────────
$ba = $db->prepare("SELECT id_account FROM _account WHERE id_users = ? AND status_account = 'verified' LIMIT 1");
$ba->execute([$id_users]);
$bank_account = $ba->fetch();

// ── Conta rejeitada ───────────────────────────
$rejected_account = null;
if ($plan_paid) {
    $rj = $db->prepare("SELECT type_account, reject_reason FROM _account WHERE id_users = ? AND status_account = 'rejected' LIMIT 1");
    $rj->execute([$id_users]);
    $rejected_account = $rj->fetch();
}

// ── Sessão info (modal logout) ────────────────
$ls = $db->prepare('SELECT last_login_at, last_login_ip FROM _users_security WHERE id_users = ?');
$ls->execute([$id_users]);
$sec = $ls->fetch();

$sess_stmt = $db->prepare("
    SELECT ip_address, user_agent, country, city, creat_session, last_activity
    FROM _users_sessions WHERE id_users = ? AND is_active = 1
    ORDER BY last_activity DESC LIMIT 1
");
$sess_stmt->execute([$id_users]);
$current_session  = $sess_stmt->fetch();
$session_duration_str = '—';
if ($current_session && $current_session['creat_session']) {
    $secs = time() - strtotime($current_session['creat_session']);
    if ($secs < 60)     $session_duration_str = $secs . 's';
    elseif ($secs < 3600)  $session_duration_str = floor($secs / 60) . 'min';
    elseif ($secs < 86400) $session_duration_str = floor($secs / 3600) . 'h ' . floor(($secs % 3600) / 60) . 'min';
    else                   $session_duration_str = floor($secs / 86400) . 'd ' . floor(($secs % 86400) / 3600) . 'h';
}
$member_since   = $user['creat_user'] ? date('d/m/Y', strtotime($user['creat_user'])) : '—';
$last_login_str = ($sec && $sec['last_login_at']) ? date('d/m/Y H:i', strtotime($sec['last_login_at'])) : '—';
$ua_raw   = $current_session['user_agent'] ?? '';
$browser  = 'Desconhecido';
if (str_contains($ua_raw, 'Edg'))     $browser = 'Microsoft Edge';
elseif (str_contains($ua_raw, 'Chrome'))  $browser = 'Google Chrome';
elseif (str_contains($ua_raw, 'Firefox')) $browser = 'Mozilla Firefox';
elseif (str_contains($ua_raw, 'Safari'))  $browser = 'Safari';
elseif (str_contains($ua_raw, 'Opera'))   $browser = 'Opera';
$sess_location = trim(($current_session['city'] ?? '') . ', ' . ($current_session['country'] ?? ''), ', ') ?: 'Desconhecida';
$sess_ip       = $current_session['ip_address'] ?? ($sec['last_login_ip'] ?? '—');

$logged_in    = isLoggedIn();
$id_users     = $logged_in ? (int)$_SESSION['id_users'] : 0;
$user_plan_id = $logged_in ? ($_SESSION['plan_selected'] ?? null) : null;
$plan_paid    = $logged_in && ($_SESSION['status'] ?? '') === 'active';

// Buscar planos activos
$stmt  = getDB()->query("SELECT * FROM _plans WHERE is_active = 1 ORDER BY display_order ASC");
$plans = $stmt->fetchAll();

// Metadados visuais por slug
$plan_meta = [
    'single' => ['badge' => 'Iniciante',                'btn' => 'btn-wasomupfy',      'featured' => true],
    'album'  => ['badge' => 'Popular',                     'btn' => 'btn-outline-primary', 'featured' => true],
    'artist' => ['badge' => 'Profissional',                 'btn' => 'btn-wasomupfy',       'featured' => true],
    'label'  => ['badge' => 'Empresarial',                     'btn' => 'btn-outline-primary', 'featured' => true],
];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/include/head.php'; ?>
    <title>Planos — <?php echo APP_NAME; ?></title>
    <style>
    /* Espaçamento extra para o badge no topo dos featured cards */
    .pt-6 {
        padding-top: 3rem !important;
    }

    :root {
        --pink: #FF0089;
        --pink-dark: #cc006e;
        --pink-soft: rgba(255, 0, 137, .10);
        --surface: #0f0f13;
        --card: #18181f;
        --border: #2a2a35;
        --text: #e8e8f0;
        --muted: #888899;
    }

    .back-link {
        color: var(--muted);
        font-size: .9rem;
        text-decoration: none;
    }

    .back-link:hover {
        color: var(--text);
    }

    /* Badges personalizados */
    .badge.bg-wasomupfy {
        background-color: rgba(255, 0, 157, 0.1) !important;
        color: #ff009d !important;
        border: 1px solid rgba(255, 0, 157, 0.2);
    }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand"
                href="<?php echo $logged_in ? APP_URL . '/' . APP_URL_PANEL . '/painel' : APP_URL . '/' . 'home'; ?>">
                <span class="text-light"
                    style="font-weight:bold;font-family:Arial,sans-serif;text-transform:capitalize"><?php echo APP_NAME; ?></span>
            </a>
            <div class="ms-auto d-flex align-items-center gap-2">
                <?php if ($logged_in): ?>
                <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/painel" class="back-link"><i
                        class="bi bi-arrow-left me-1"></i>Voltar ao
                    Painel</a>
                <?php else: ?>
                <a href="<?php echo APP_URL ?>/login" class="btn btn-sm btn-outline-secondary">Entrar</a>
                <a href="<?php echo APP_URL ?>/register" class="btn btn-sm btn-wasomupfy">Criar Conta</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="py-5 text-center"
        style="background:linear-gradient(135deg,#0f0f1a,#1a0a14,#0f0f1a);padding-top:4rem!important;padding-bottom:5rem!important;position:relative;overflow:hidden">
        <div
            style="position:absolute;inset:0;background:radial-gradient(ellipse 60% 50% at 50% 0%,rgba(255,0,137,.15),transparent 70%)">
        </div>
        <div class="container position-relative">
            <span class="badge bg-wasomupfy text-white fw-semibold px-3 py-2 mb-3">Distribui a tua música</span>
            <h1 class="display-5 fw-bold text-white mb-3">Escolhe o teu Plano</h1>
            <p class="lead mb-4" style="color:#aaa;max-width:520px;margin-left:auto;margin-right:auto">
                Distribui para +150 plataformas digitais. Paga uma vez, lança para o mundo.
            </p>

            <?php
            // Verificar se algum plano tem pacote anual
            $has_annual = false;
            foreach ($plans as $p) {
                if ($p['price_annual'] && $p['annual_qty']) {
                    $has_annual = true;
                    break;
                }
            }
            ?>
            <?php if ($has_annual): ?>
            <div class="d-inline-flex align-items-center gap-3 rounded-pill px-4 py-2"
                style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12)">
                <span class="text-light small">Por lançamento</span>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="billingToggle"
                        style="width:2.5rem;height:1.25rem;cursor:pointer">
                </div>
                <span class="text-light small">Pacote&nbsp;<span class="badge bg-success">Poupança</span></span>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Cards -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4 justify-content-center">

                <?php foreach ($plans as $plan):
                    $slug    = $plan['slug_plan'];
                    $meta    = $plan_meta[$slug] ?? ['badge' => null, 'btn' => 'btn-outline-primary', 'featured' => false];
                    $is_curr = $logged_in && $user_plan_id == $plan['id_plan'];
                    $is_paid = $is_curr && $plan_paid;
                    $is_pend = $is_curr && !$plan_paid;

                    // Poupança no pacote anual
                    $savings = 0;
                    if ($plan['price_annual'] && $plan['annual_qty']) {
                        $total = $plan['price_plan'] * $plan['annual_qty'];
                        $savings = $total > 0 ? round((1 - $plan['price_annual'] / $total) * 100) : 0;
                    }

                    // Borda do card
                    if ($is_paid)              $border = 'border-success border-3';
                    elseif ($meta['featured']) $border = 'border-wasom border-3';
                    else                       $border = 'border-0';

                    // Botão CTA
                    if ($is_paid) {
                        $btn_href  = '#';
                        $btn_label = '<i class="bi bi-check-circle me-1"></i>Plano Activo';
                        $btn_class = 'btn-success disabled';
                    } elseif ($is_pend) {
                        $btn_href  = '../dashboard/payment/pay';
                        $btn_label = '<i class="bi bi-credit-card me-1"></i>Finalizar Pagamento';
                        $btn_class = 'btn-wasomupfy';
                    } elseif ($logged_in) {
                        $btn_href  = '../dashboard/payment/pay?plan=' . $slug;
                        $btn_label = 'Escolher ' . htmlspecialchars($plan['name_plan']);
                        $btn_class = $meta['btn'];
                    } else {
                        $btn_href  = '../register?plan=' . $slug;
                        $btn_label = 'Começar com ' . htmlspecialchars($plan['name_plan']);
                        $btn_class = $meta['btn'];
                    }

                    $tracks  = $plan['max_tracks_per_release'];
                    $artists = $plan['max_artists'];
                    $colabs  = $slug === 'label' ? 5 : 1;
                ?>
                <div class="col-xl-3 col-lg-6" data-cue="zoomIn">
                    <div class="pricing-card card <?php echo $border; ?> h-100 shadow-lg hover-lift position-relative">

                        <!-- Badge topo -->
                        <?php if ($is_paid): ?>
                        <div class="position-absolute top-0 start-50 translate-middle">
                            <span class="badge bg-success text-white fw-semibold px-4 py-2">
                                <i class="bi bi-check-circle me-1"></i>Plano activo
                            </span>
                        </div>
                        <?php elseif ($is_pend): ?>
                        <div class="position-absolute top-0 start-50 translate-middle">
                            <span class="badge bg-warning text-dark fw-semibold px-4 py-2">
                                <i class="bi bi-clock me-1"></i>Pagamento pendente
                            </span>
                        </div>
                        <?php elseif ($meta['featured']): ?>
                        <div class="position-absolute top-0 start-50 translate-middle">
                            <span class="badge bg-wasomupfy text-white fw-semibold px-4 py-2">
                                <?php echo htmlspecialchars($meta['badge']); ?>
                            </span>
                        </div>
                        <?php endif; ?>

                        <div
                            class="card-header border-0 <?php echo ($meta['featured'] || $is_curr) ? 'pt-6' : 'pt-5'; ?> pb-4 text-center">

                            <?php if (!$meta['featured'] && $meta['badge'] && !$is_curr): ?>
                            <span class="badge bg-wasom-light text-wasom fw-semibold px-3 py-2 mb-3">
                                <?php echo $meta['badge']; ?>
                            </span>
                            <?php endif; ?>

                            <h3 class="h2 fw-bold mb-3"><?php echo htmlspecialchars($plan['name_plan']); ?></h3>

                            <div class="price-display">
                                <!-- Preço por lançamento -->
                                <div class="monthly-price">
                                    <span class="price-amount display-4 fw-bold">
                                        <?php echo number_format($plan['price_plan'], 0, ',', '.'); ?>
                                    </span>
                                    <span class="price-period h5 text-muted fw-normal">
                                        Kz/<?php
                                                if ($plan['type_plan'] === 'subscription') echo 'ano';
                                                elseif ($slug === 'album') echo 'álbum';
                                                else echo 'single';
                                                ?>
                                    </span>
                                </div>
                                <!-- Pacote anual (oculto por defeito) -->
                                <?php if ($plan['price_annual'] && $plan['annual_qty']): ?>
                                <div class="annual-price d-none">
                                    <span class="price-amount display-4 fw-bold">
                                        <?php echo number_format($plan['price_annual'], 0, ',', '.'); ?>
                                    </span>
                                    <span class="price-period h5 text-muted fw-normal">
                                        Kz/<?php echo $plan['annual_qty']; ?>
                                        <?php echo $slug === 'album' ? 'álbuns' : 'singles'; ?>
                                    </span>
                                    <?php if ($savings > 0): ?>
                                    <div class="mt-1"><span class="badge bg-success">Economize
                                            <?php echo $savings; ?>%</span></div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <p class="text-muted mb-0"><?php echo htmlspecialchars($plan['description_plan'] ?? ''); ?>
                            </p>
                        </div>

                        <div class="card-body pt-4 pb-5 px-4">
                            <ul class="list-unstyled mb-4">
                                <li class="d-flex align-items-start mb-3">
                                    <i class="bi bi-check-circle-fill text-success mt-1 me-3"></i>
                                    <span><strong><?php echo $plan['royalty_rate']; ?>% Royalties</strong> — ficas com
                                        quase tudo</span>
                                </li>
                                <li class="d-flex align-items-start mb-3">
                                    <i class="bi bi-check-circle-fill text-success mt-1 me-3"></i>
                                    <span>Upload de
                                        <strong><?php echo $tracks ? $tracks . ' faixa' . ($tracks > 1 ? 's' : '') : 'faixas ilimitadas'; ?></strong></span>
                                </li>
                                <li class="d-flex align-items-start mb-3">
                                    <i class="bi bi-check-circle-fill text-success mt-1 me-3"></i>
                                    <span><strong><?php echo $artists; ?>
                                            Artista<?php echo $artists > 1 ? 's' : ''; ?></strong>
                                        <?php echo $artists > 1 ? 'na conta' : 'principal'; ?></span>
                                </li>
                                <li class="d-flex align-items-start mb-3">
                                    <i class="bi bi-check-circle-fill text-success mt-1 me-3"></i>
                                    <span><strong><?php echo $colabs; ?>
                                            Colaborador<?php echo $colabs > 1 ? 'es' : ''; ?></strong> por faixa</span>
                                </li>
                                <li class="d-flex align-items-start mb-3">
                                    <i class="bi bi-check-circle-fill text-success mt-1 me-3"></i>
                                    <span>Análise de dados <strong>avançados</strong></span>
                                </li>
                                <li class="d-flex align-items-start mb-3">
                                    <i class="bi bi-check-circle-fill text-success mt-1 me-3"></i>
                                    <span><strong>ISRC e UPC grátis</strong></span>
                                </li>
                                <li class="d-flex align-items-start mb-3">
                                    <i class="bi bi-check-circle-fill text-success mt-1 me-3"></i>
                                    <span>Smartlink e pre-save</span>
                                </li>
                                <li class="d-flex align-items-start mb-3">
                                    <i class="bi bi-check-circle-fill text-success mt-1 me-3"></i>
                                    <span>Lançamento em <strong>72h</strong></span>
                                </li>
                                <li class="d-flex align-items-start mb-3">
                                    <i class="bi bi-check-circle-fill text-success mt-1 me-3"></i>
                                    <span>Suporte local (WhatsApp + E-mail)</span>
                                </li>
                                <li class="d-flex align-items-start <?php echo $slug !== 'single' ? 'mb-3' : ''; ?>">
                                    <i class="bi bi-check-circle-fill text-success mt-1 me-3"></i>
                                    <span>Agendar lançamentos</span>
                                </li>
                                <?php if ($slug !== 'single'): ?>
                                <li class="d-flex align-items-start">
                                    <i class="bi bi-star-fill text-warning mt-1 me-3"></i>
                                    <span><strong>Personalizar nome de selo</strong></span>
                                </li>
                                <?php endif; ?>
                            </ul>

                            <div class="d-grid">
                                <a href="<?php echo $btn_href; ?>" class="btn <?php echo $btn_class; ?> btn-lg">
                                    <?php echo $btn_label; ?>
                                </a>
                            </div>

                            <?php if ($is_pend): ?>
                            <div class="alert alert-warning mt-3 py-2 small mb-0">
                                <i class="bi bi-clock me-1"></i>
                                Tens um pagamento pendente para este plano.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
        </div>
    </section>

    <!-- Tabela comparativa -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center mb-5">
                <div class="col-lg-10 text-center">
                    <span class="badge bg-wasomupfy text-white fw-semibold px-3 py-2 mb-3">Comparação</span>
                    <h2 class="display-6 fw-bold mb-4">Compare Todos os Planos</h2>
                    <p class="text-muted lead mb-0">Veja detalhadamente o que cada plano oferece</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width:28%">Recursos</th>
                            <?php foreach ($plans as $p): ?>
                            <th class="text-center py-4 <?php echo $p['is_featured'] ? 'bg-wasom-light' : ''; ?>">
                                <h5 class="mb-1"><?php echo htmlspecialchars($p['name_plan']); ?></h5>
                                <div class="text-wasom fw-bold" style="font-size:.85rem">
                                    <?php echo number_format($p['price_plan'], 0, ',', '.'); ?>Kz/<?php
                                                                                                        echo $p['type_plan'] === 'subscription' ? 'ano' : ($p['slug_plan'] === 'album' ? 'álbum' : 'single');
                                                                                                        ?>
                                </div>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold">Royalties</td>
                            <?php foreach ($plans as $p): ?>
                            <td class="text-center <?php echo $p['is_featured'] ? 'bg-wasom-light' : ''; ?>">
                                <?php echo $p['royalty_rate']; ?>%</td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Faixas</td>
                            <?php foreach ($plans as $p): ?>
                            <td class="text-center <?php echo $p['is_featured'] ? 'bg-wasom-light' : ''; ?>">
                                <?php echo $p['max_tracks_per_release']
                                        ? $p['max_tracks_per_release'] . ' faixa' . ($p['max_tracks_per_release'] > 1 ? 's' : '')
                                        : '<strong>Ilimitadas</strong>'; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Artistas</td>
                            <?php foreach ($plans as $p): ?>
                            <td class="text-center <?php echo $p['is_featured'] ? 'bg-wasom-light' : ''; ?>">
                                <?php echo $p['max_artists']; ?> artista<?php echo $p['max_artists'] > 1 ? 's' : ''; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Colaboradores</td>
                            <?php foreach ($plans as $p): ?>
                            <td class="text-center <?php echo $p['is_featured'] ? 'bg-wasom-light' : ''; ?>">
                                <?php echo $p['slug_plan'] === 'label' ? '5' : '1'; ?> por faixa
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php foreach (
                            [
                                'Análise de Dados'     => true,
                                'ISRC e UPC'           => true,
                                'Smartlink e Pre-save' => true,
                                'Agendar Lançamentos'  => true,
                            ] as $label => $check
                        ): ?>
                        <tr>
                            <td class="fw-semibold"><?php echo $label; ?></td>
                            <?php foreach ($plans as $p): ?>
                            <td class="text-center <?php echo $p['is_featured'] ? 'bg-wasom-light' : ''; ?>">
                                <i class="bi bi-check-lg text-success"></i>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td class="fw-semibold">Tempo de Lançamento</td>
                            <?php foreach ($plans as $p): ?>
                            <td class="text-center <?php echo $p['is_featured'] ? 'bg-wasom-light' : ''; ?>">72 horas
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Suporte</td>
                            <?php foreach ($plans as $p): ?>
                            <td class="text-center <?php echo $p['is_featured'] ? 'bg-wasom-light' : ''; ?>">WhatsApp +
                                E-mail</td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Nome do Selo</td>
                            <?php foreach ($plans as $p): ?>
                            <td class="text-center <?php echo $p['is_featured'] ? 'bg-wasom-light' : ''; ?>">
                                <i
                                    class="bi <?php echo $p['slug_plan'] !== 'single' ? 'bi-check-lg text-success' : 'bi-dash text-muted'; ?>"></i>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td></td>
                            <?php foreach ($plans as $p):
                                $is_c = $logged_in && $user_plan_id == $p['id_plan'];
                                $i_pd = $is_c && $plan_paid;
                                $i_pn = $is_c && !$plan_paid;
                                $sl   = $p['slug_plan'];
                                $m    = $plan_meta[$sl] ?? ['btn' => 'btn-outline-primary'];
                                if ($i_pd) {
                                    $h = '#';
                                    $l = 'Activo';
                                    $c = 'btn-success disabled';
                                } elseif ($i_pn) {
                                    $h = '../dashboard/payment/pay';
                                    $l = 'Pagar agora';
                                    $c = 'btn-wasomupfy';
                                } elseif ($logged_in) {
                                    $h = '../dashboard/payment/pay?plan=' . $sl;
                                    $l = 'Escolher';
                                    $c = $m['btn'];
                                } else {
                                    $h = '../register?plan=' . $sl;
                                    $l = 'Começar';
                                    $c = $m['btn'];
                                }
                            ?>
                            <td class="text-center pt-4 <?php echo $p['is_featured'] ? 'bg-wasom-light' : ''; ?>">
                                <a href="<?php echo $h; ?>"
                                    class="btn <?php echo $c; ?> btn-outline-primary w-100"><?php echo $l; ?></a>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <footer class="py-4 text-center  small border-top">
        <div class="container">
            Tens dúvidas? <a
                href="<?php echo $logged_in ? APP_URL . '/' . APP_URL_PANEL . '/help' : APP_URL . '/' . 'contact'; ?>"
                class="text-wasom">Consulta-nos</a> &nbsp;·&nbsp;
            &copy; <?php echo date('Y'); ?> Wasom Upfy
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <script>
    const t = document.getElementById('billingToggle');
    if (t) {
        t.addEventListener('change', function() {
            document.querySelectorAll('.monthly-price').forEach(el => el.classList.toggle('d-none', this
                .checked));
            document.querySelectorAll('.annual-price').forEach(el => el.classList.toggle('d-none', !this
                .checked));
        });
    }
    </script>
</body>

</html>