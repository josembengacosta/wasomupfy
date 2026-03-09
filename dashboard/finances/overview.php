<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Finanças: Visão Geral
// Arquivo: dashboard/finances/overview.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];
$user     = getUserById($id_users);
if (!$user) {
    redirect('authentic/logout');
}

// ── Dados básicos ─────────────────────────────
$first_name     = htmlspecialchars($user['first_name']);
$email_verified = (bool)$user['email_verified'];
$plan_selected  = $user['plan_selected'];

// ── Plano ──────────────────────────────────────
$plan      = null;
$plan_paid = ($user['status_user'] === 'active' && !empty($user['plan_activated_at']));
if ($plan_selected) {
    $ps = $db->prepare('SELECT * FROM _plans WHERE id_plan = ?');
    $ps->execute([$plan_selected]);
    $plan = $ps->fetch();
}
$plan_name = $plan ? htmlspecialchars($plan['name_plan']) : 'Sem plano';


// Tem artistas?
$as = getDB()->prepare('SELECT COUNT(*) as total FROM _artist WHERE id_users = ?');
$as->execute([$id_users]);
$has_artist = (int)($as->fetch()['total'] ?? 0) > 0;

// Dados de sessão e segurança
$ls = getDB()->prepare('SELECT last_login_at, last_login_ip FROM _users_security WHERE id_users = ?');
$ls->execute([$id_users]);
$sec = $ls->fetch();
$days_inactive = 0;
if ($sec && $sec['last_login_at']) {
    $days_inactive = (int)floor((time() - strtotime($sec['last_login_at'])) / 86400);
}

// Sessão activa actual
$sess_stmt = getDB()->prepare("
    SELECT ip_address, user_agent, country, city, creat_session, last_activity
    FROM _users_sessions
    WHERE id_users = ? AND is_active = 1
    ORDER BY last_activity DESC LIMIT 1
");
$sess_stmt->execute([$id_users]);
$current_session = $sess_stmt->fetch();

// Calcular tempo de sessão activa
$session_duration_str = '—';
if ($current_session && $current_session['creat_session']) {
    $secs = time() - strtotime($current_session['creat_session']);
    if ($secs < 60) $session_duration_str = $secs . 's';
    elseif ($secs < 3600) $session_duration_str = floor($secs / 60) . 'min';
    elseif ($secs < 86400) $session_duration_str = floor($secs / 3600) . 'h ' . floor(($secs % 3600) / 60) . 'min';
    else $session_duration_str = floor($secs / 86400) . 'd ' . floor(($secs % 86400) / 3600) . 'h';
}

// Conta desde quando
$member_since = $user['creat_user'] ? date('d/m/Y', strtotime($user['creat_user'])) : '—';
$last_login_str = ($sec && $sec['last_login_at'])
    ? date('d/m/Y H:i', strtotime($sec['last_login_at']))
    : '—';

// Browser simplificado a partir do user_agent
$ua_raw    = $current_session['user_agent'] ?? '';
$browser   = 'Navegador desconhecido';
if (str_contains($ua_raw, 'Edg'))     $browser = 'Microsoft Edge';
elseif (str_contains($ua_raw, 'Chrome'))  $browser = 'Google Chrome';
elseif (str_contains($ua_raw, 'Firefox')) $browser = 'Mozilla Firefox';
elseif (str_contains($ua_raw, 'Safari'))  $browser = 'Safari';
elseif (str_contains($ua_raw, 'Opera'))   $browser = 'Opera';

$sess_location = trim(($current_session['city'] ?? '') . ', ' . ($current_session['country'] ?? ''), ', ');
if (!$sess_location) $sess_location = 'Localização desconhecida';
$sess_ip = $current_session['ip_address'] ?? ($sec['last_login_ip'] ?? '—');

// ── Wallet ──────────────────────────────────────
$w = $db->prepare('SELECT * FROM _wallet WHERE id_users = ?');
$w->execute([$id_users]);
$wallet = $w->fetch() ?: ['balance_aoa' => 0, 'balance_usd' => 0, 'total_earned' => 0, 'total_withdrawn' => 0];
$balance_aoa  = (float)$wallet['balance_aoa'];
$balance_usd  = (float)$wallet['balance_usd'];
$total_earned = (float)$wallet['total_earned'];
$total_withdrawn = (float)$wallet['total_withdrawn'];

// ── Conta bancária padrão verificada ──────────
$bq = $db->prepare("SELECT * FROM _account WHERE id_users = ? AND status_account = 'verified' AND is_default = 1 LIMIT 1");
$bq->execute([$id_users]);
$bank_account = $bq->fetch() ?: null;

// Todas as contas verificadas (para o modal de saque)
$allbanks_q = $db->prepare("SELECT * FROM _account WHERE id_users = ? AND status_account = 'verified' ORDER BY is_default DESC");
$allbanks_q->execute([$id_users]);
$all_banks = $allbanks_q->fetchAll(PDO::FETCH_ASSOC);

// ── Condição de saque ──────────────────────────
$min_withdrawal = 10000.00;
$can_withdraw   = $plan_paid && $bank_account && ($balance_aoa >= $min_withdrawal);

// ── Royalties reais ───────────────────────────
$royalties_q = $db->prepare("
    SELECT r.id_royalty, r.year_royalty, r.month_royalty,
           r.gross_revenue, r.net_royalty, r.net_royalty_aoa,
           r.currency, r.status_royalty, r.paid_at,
           t.title_track, a.title_album
    FROM _royalty r
    JOIN _track t ON t.id_track = r.id_track
    JOIN _album a ON a.id_album = t.id_album
    WHERE r.id_users = ?
    ORDER BY r.year_royalty DESC, r.month_royalty DESC
    LIMIT 50
");
$royalties_q->execute([$id_users]);
$royalties = $royalties_q->fetchAll(PDO::FETCH_ASSOC);

// ── Saques reais ──────────────────────────────
$withdrawals_q = $db->prepare("
    SELECT w.id_withdrawal, w.amount_requested, w.amount_net,
           w.currency, w.status_withdrawal, w.creat_withdrawal,
           w.paid_at, ac.type_account, ac.full_name_account
    FROM _withdrawal w
    LEFT JOIN _account ac ON ac.id_account = w.id_account
    WHERE w.id_users = ?
    ORDER BY w.creat_withdrawal DESC
    LIMIT 50
");
$withdrawals_q->execute([$id_users]);
$withdrawals = $withdrawals_q->fetchAll(PDO::FETCH_ASSOC);

// ── Saque pendente (bloqueia novo pedido) ──────
$pending_q = $db->prepare("SELECT COUNT(*) FROM _withdrawal WHERE id_users = ? AND status_withdrawal IN ('pending','processing')");
$pending_q->execute([$id_users]);
$has_pending_withdrawal = (int)$pending_q->fetchColumn() > 0;

// ── Helpers ────────────────────────────────────
$months_pt = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

$royalty_status = [
    'pending'    => ['label' => 'Pendente',    'class' => 'bg-warning text-dark'],
    'processing' => ['label' => 'A processar', 'class' => 'bg-primary text-white'],
    'paid'       => ['label' => 'Pago',        'class' => 'bg-success text-white'],
    'cancelled'  => ['label' => 'Cancelado',   'class' => 'bg-secondary text-white'],
];
$withdrawal_status = [
    'pending'    => ['label' => 'Pendente',    'class' => 'bg-warning text-dark'],
    'processing' => ['label' => 'A processar', 'class' => 'bg-primary text-white'],
    'approved'   => ['label' => 'Aprovado',    'class' => 'bg-success text-white'],
    'rejected'   => ['label' => 'Recusado',    'class' => 'bg-danger text-white'],
    'cancelled'  => ['label' => 'Cancelado',   'class' => 'bg-secondary text-white'],
];

$user_artist_name = htmlspecialchars($user['name_artist_band'] ?? $user['first_name']);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png" />
    <link rel="apple-touch-startup-image" href="../../assets/img/screenshots/splash.png" />
    <link rel="manifest" href="../manifest.json" />
    <title>Finanças — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <link rel="stylesheet" href="../../css/dashboard-style.css" />
    <link rel="stylesheet" href="../../css/lastest-style.css" />
</head>

<body>

    <?php include __DIR__ . '/_modal_withdrawal.php'; ?>
    <!-- Tela de Carregamento -->
    <!-- <div class="loading-screen" id="loadingScreen">
        <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg" class="loading-logo">
            <rect x="2" y="2" width="116" height="36" rx="5" fill="none" stroke="#ff0089" stroke-width="2"/>
            <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold" fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
        </svg>
        <div class="spinner"></div>
    </div> -->

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <!-- Menu Button (Left) -->
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu"
                aria-controls="offcanvasMenu">
                <span class="navbar-toggler-icon"><i class="bi bi-list text-white fs-1"></i></span>
            </button>

            <!-- Logo (Center on Mobile, Left on Desktop) -->
            <a class="navbar-brand" href="../painel">
                <!-- SVG Logo Wasom Upfy -->
                <!-- <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="116" height="36" rx="5" fill="none" stroke="#ff0089" stroke-width="2" />
                    <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold"
                        fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
                </svg> -->
                <span class="text-light" style="
              font-weight: bold;
              box-sizing: border-box;
              text-transform: capitalize;
              font-family: Arial, sans-serif;
            ">WASOM UPFY</span>
            </a>

            <!-- Desktop Menu -->
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav m-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i> Lançamentos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../analytics/statistics"><i class="bi bi-bar-chart"></i>
                            Estatísticas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="overview"><i class="bi bi-currency-dollar"></i> Finanças</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i> Artistas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../artists/youtube/ucy"><i class="bi bi-youtube"></i> Unificação de
                            canal
                            YouTube</a>
                    </li>
                </ul>
            </div>

            <!-- User Icon (Right) -->
            <div class="user-menu d-flex align-items-center">
                <!-- Theme Toggle Button -->
                <a class="theme-toggle text-white me-2" id="themeToggle">
                    <i class="bi bi-sun" id="themeIcon"></i>
                </a>
                <a href="../page/notifications" class="text-white me-2" aria-label="Notificações">
                    <i class="bi bi-bell fs-4"></i>
                    <span class="badge bg-danger">9</span>
                </a>
                <a href="#" class="text-white" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-4"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="../user/profile"><i class="bi bi-person me-2"></i>
                            <strong><?php echo $first_name; ?></strong></a>
                        <div class="text-white-50">
                            &nbsp; &nbsp; &nbsp; &nbsp; (Conta <?php echo str_pad($id_users, 6, "0", STR_PAD_LEFT); ?>)
                        </div>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <a class="dropdown-item" href="../user/profile"><i class="bi bi-person me-2"></i> Meu Perfil</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../account/manage-account"><i class="bi bi-tools me-2"></i>
                            Gestão de
                            Conta</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <a class="dropdown-item" href="../page/settings"><i class="bi bi-gear me-2"></i>
                            Configurações</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../page/notifications"><i class="bi bi-bell me-2"></i>
                            Notificações</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../services/available-services"><i class="bi bi-star me-2"></i>
                            Conta e
                            serviços disponíveis</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#?logout-wasomupfy" data-bs-toggle="modal"
                            data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right me-2"></i>
                            Desconectar-se</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <a class="dropdown-item" href="../page/about"><i class="bi bi-info-circle me-2"></i> Sobre</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../page/support"><i class="bi bi-headset me-2"></i> Enviar pedido
                            de
                            suporte</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../page/faq"><i class="bi bi-chat-left-text me-2"></i> Perguntas
                            frequentes</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../page/help"><i class="bi bi-question-circle me-2"></i>
                            Ajuda</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <span class="dropdown-item-text" id="versionDropdown"></span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Offcanvas Menu para Mobile e Desktop -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasMenuLabel">
                <!-- <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="116" height="36" rx="5" fill="none" stroke="#ff0089" stroke-width="2" />
                    <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold"
                        fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
                </svg> -->
                <span class="text-light" style="
              font-weight: bold;
              box-sizing: border-box;
              text-transform: capitalize;
              font-family: Arial, sans-serif;
            ">WASOM UPFY</span>
            </h5>
            <button type="button" class="btn-close text-white" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i> Lançamentos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../analytics/statistics"><i class="bi bi-bar-chart"></i> Estatísticas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../finances/overview"><i class="bi bi-currency-dollar"></i> Finanças</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i> Artistas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../artists/youtube/ucy"><i class="bi bi-youtube"></i> Unificação de canal
                        YouTube</a>
                </li>
                <!-- Links secundários exibidos apenas em mobile -->
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../user/profile"><i class="bi bi-person-circle"></i> Meu Perfil</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link active" href="../page/settings"><i class="bi bi-gear"></i> Configurações</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../page/notifications"><i class="bi bi-bell"></i> Notificações</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../page/about"><i class="bi bi-info-circle"></i> Sobre</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../services/available-services"><i class="bi bi-star"></i> Conta e
                        serviços
                        disponíveis</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../page/help"><i class="bi bi-question-circle"></i> Ajuda</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="#?logout-wasomupfy" data-bs-toggle="modal"
                        data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right"></i> Desconectar-se</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Toast para Notificações de Status -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="connectionToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">Conexão</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
            <div class="toast-body">
                Você está offline. Alguns dados podem estar desatualizados.
                <div class="mt-2">
                    <button class="btn btn-pink btn-sm" onclick="tryReconnect()">
                        Tentar Reconectar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-4">

        <?php /* ============================================
    BANNERS DE NOTIFICACAO DO PAINEL
    ============================================ */ ?>

        <?php if (!$email_verified): ?>
            <div class="alert alert-warning alert-dismissible d-flex align-items-center mb-3" role="alert"
                id="banner-email">
                <i class="bi bi-envelope-exclamation-fill fs-5 me-2"></i>
                <div>
                    <strong>Email por verificar.</strong>
                    O teu email ainda não foi verificado. Acede ao teu perfil e usa o codigo que recebeste para verificar.
                    <a href="../account/manage-account" class="alert-link ms-1">Verificar agora &rarr;</a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!$has_artist): ?>
            <div class="alert alert-info alert-dismissible d-flex align-items-center mb-3" role="alert" id="banner-artist">
                <i class="bi bi-person-plus-fill fs-5 me-2"></i>
                <div>
                    <strong>Cria o teu perfil de artista.</strong>
                    Para comecar a distribuir música, primeiro precisas de criar um perfil de artista.
                    <a href="../artists/add-artist" class="alert-link ms-1">Criar agora &rarr;</a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($plan && !$plan_paid): ?>
            <!-- Tem plano escolhido mas pagamento pendente -->
            <div class="alert alert-warning alert-dismissible d-flex align-items-center mb-3" role="alert"
                id="banner-plan-pending">
                <i class="bi bi-clock-history fs-5 me-2 flex-shrink-0"></i>
                <div class="flex-grow-1">
                    <strong>Pagamento pendente — Plano <?php echo htmlspecialchars($plan['name_plan']); ?>.</strong>
                    O plano foi seleccionado mas o pagamento ainda não foi confirmado.
                    <a href="../payment/pay" class="alert-link ms-1 fw-bold">Finalizar pagamento &rarr;</a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif (!$plan): ?>
            <!-- Sem plano nenhum -->
            <div class="alert alert-danger d-flex align-items-center mb-3" role="alert" id="banner-plan">
                <i class="bi bi-credit-card-fill fs-5 me-2 flex-shrink-0"></i>
                <div class="flex-grow-1">
                    <strong>Sem plano activo.</strong>
                    Escolhe um plano para comecar a distribuir a tua música.
                    <a href="../all-plans" class="alert-link ms-1 fw-bold">Ver planos &rarr;</a>
                </div>
            </div>
        <?php endif; ?>


        <!-- Cabeçalho -->
        <div class="page-header">
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <div class="page-header-compact">
                        <h1><i class="bi bi-currency-dollar me-3"></i>Finanças</h1>
                        <p class="lead">Acompanha o histórico de receitas, saques realizados e saldo disponível.</p>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="../analytics/report" class="btn btn-pink">
                        <i class="bi bi-file-text me-1"></i> Relatórios
                    </a>
                </div>
            </div>
        </div>

        <!-- Balance Card -->
        <div class="balance-card mb-4">
            <div class="card">
                <h6 style="color: #ff0089">Saldo disponível para saque</h6>
                <h2 id="balance"><?php echo number_format($balance_aoa, 2, ",", "."); ?> AOA</h2>

                <?php if (!$plan_paid): ?>
                    <p class="text-warning small mb-2">
                        <i class="bi bi-lock-fill me-1"></i>
                        Activa o teu plano para começar a receber royalties.
                    </p>
                <?php elseif (!$bank_account): ?>
                    <p class="text-muted small mb-2">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        Para sacar, primeiro regista uma conta bancária.
                    </p>
                <?php elseif ($balance_aoa < $min_withdrawal): ?>
                    <p class="text-muted small mb-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Mínimo para saque: <strong>10.000 Kz</strong>
                        (tens <?php echo number_format($balance_aoa, 0, ',', '.'); ?> Kz).
                    </p>
                <?php else: ?>
                    <p class="small mb-2" style="color:#ccc">
                        Os teus rendimentos estão prontos. Solicita o saque agora.
                    </p>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-outline-pink disabled" onclick="setMoeda('AOA')" id="btnAOA"
                        data-bs-toggle="tooltip" data-bs-placement="top" title="Ver em Kwanza">
                        <i class="bi bi-currency-exchange"></i> AOA
                    </button>

                    <?php if (!$bank_account): ?>
                        <!-- Sem conta: leva para criar conta bancária -->
                        <a href="withdraw" class="btn btn-pink">
                            <i class="bi bi-bank me-1"></i> Criar Conta Bancária
                        </a>
                    <?php elseif ($can_withdraw): ?>
                        <!-- Pode sacar -->
                        <button class="btn btn-pink" data-bs-toggle="modal" data-bs-target="#sake">
                            <i class="bi bi-wallet2 me-2"></i> Sacar
                        </button>
                    <?php else: ?>
                        <!-- Saldo insuficiente ou plano inactivo -->
                        <button class="btn btn-pink" disabled title="Saldo mínimo de 10.000 Kz necessário">
                            <i class="bi bi-wallet2 me-2"></i> Sacar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Atalhos -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="launch-card">
                    <div class="card">
                        <div class="d-flex align-items-center">
                            <div class="m-auto w-100 text-center">
                                <a href="withdraw" class="btn btn-default w-100" style="color:#ff0089;font-weight:bold">
                                    <h5 class="mb-0"><i class="bi bi-credit-card-fill me-3"></i>Contas de Saque</h5>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="launch-card">
                    <div class="card">
                        <div class="d-flex align-items-center">
                            <div class="m-auto w-100 text-center">
                                <a href="transactions" class="btn btn-default w-100"
                                    style="color:#ff0089;font-weight:bold">
                                    <h5 class="mb-0"><i class="bi bi-cash-coin me-3"></i>Divisão de Royalties</h5>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela Royalties -->
        <div class="table-card mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-music-note-beamed me-2 text-pink"></i>Histórico de Royalties</h6>
                    <span class="badge bg-secondary"><?php echo count($royalties); ?> registos</span>
                </div>
                <div class="table-responsive">
                    <?php if (empty($royalties)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-music-note-list fs-1 d-block mb-2 opacity-25"></i>
                            <div class="small">Nenhum royalty registado ainda.</div>
                            <div class="small">Os royalties aparecem aqui após a aprovação dos teus lançamentos.</div>
                        </div>
                    <?php else: ?>
                        <table id="royaltiesTable" class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Faixa / Álbum</th>
                                    <th>Período</th>
                                    <th>Bruto (USD)</th>
                                    <th>Líquido (USD)</th>
                                    <th>Líquido (AOA)</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($royalties as $r):
                                    $rs  = $royalty_status[$r['status_royalty']] ?? $royalty_status['pending'];
                                    $per = $months_pt[(int)$r['month_royalty']] . '/' . $r['year_royalty'];
                                ?>
                                    <tr>
                                        <td class="text-muted small"><?php echo (int)$r['id_royalty']; ?></td>
                                        <td>
                                            <div class="fw-semibold small"><?php echo htmlspecialchars($r['title_track']); ?>
                                            </div>
                                            <div class="text-muted" style="font-size:.72rem">
                                                <?php echo htmlspecialchars($r['title_album']); ?></div>
                                        </td>
                                        <td class="small"><?php echo $per; ?></td>
                                        <td class="small fw-semibold">
                                            $<?php echo number_format((float)$r['gross_revenue'], 4); ?></td>
                                        <td class="small fw-semibold text-success">
                                            $<?php echo number_format((float)$r['net_royalty'], 4); ?></td>
                                        <td class="small fw-semibold">
                                            <?php echo $r['net_royalty_aoa'] ? number_format((float)$r['net_royalty_aoa'], 2, ',', '.') . ' Kz' : '—'; ?>
                                        </td>
                                        <td><span class="badge <?php echo $rs['class']; ?>"><?php echo $rs['label']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tabela Saques -->
        <div class="table-card mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-cash-stack me-2 text-pink"></i>Histórico de Saques</h6>
                    <span class="badge bg-secondary"><?php echo count($withdrawals); ?> registos</span>
                </div>
                <div class="table-responsive">
                    <?php if (empty($withdrawals)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-bank fs-1 d-block mb-2 opacity-25"></i>
                            <div class="small">Nenhum saque realizado ainda.</div>
                        </div>
                    <?php else: ?>
                        <table id="withdrawalsTable" class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Data</th>
                                    <th>Conta destino</th>
                                    <th>Valor pedido</th>
                                    <th>Valor líquido</th>
                                    <th>Moeda</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($withdrawals as $w):
                                    $ws = $withdrawal_status[$w['status_withdrawal']] ?? $withdrawal_status['pending'];
                                ?>
                                    <tr>
                                        <td class="text-muted small"><?php echo (int)$w['id_withdrawal']; ?></td>
                                        <td class="small"><?php echo date('d/m/Y', strtotime($w['creat_withdrawal'])); ?></td>
                                        <td class="small">
                                            <div class="fw-semibold">
                                                <?php echo htmlspecialchars($w['full_name_account'] ?? '—'); ?></div>
                                            <div class="text-muted" style="font-size:.7rem">
                                                <?php echo htmlspecialchars($w['type_account'] ?? ''); ?></div>
                                        </td>
                                        <td class="small fw-semibold">
                                            <?php echo number_format((float)$w['amount_requested'], 2, ',', '.'); ?></td>
                                        <td class="small fw-semibold text-success">
                                            <?php echo number_format((float)$w['amount_net'], 2, ',', '.'); ?></td>
                                        <td class="small"><?php echo htmlspecialchars($w['currency']); ?></td>
                                        <td><span class="badge <?php echo $ws['class']; ?>"><?php echo $ws['label']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /container -->

    <!-- Bottom Nav Mobile -->
    <nav class="bottom-nav d-lg-none">
        <ul class="nav justify-content-around">
            <li class="nav-item"><a class="nav-link" href="../painel"><i
                        class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../launch/releases"><i
                        class="bi bi-disc"></i><span>Lançamentos</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../analytics/statistics"><i
                        class="bi bi-bar-chart"></i><span>Estatísticas</span></a></li>
            <li class="nav-item"><a class="nav-link active" href="overview"><i
                        class="bi bi-currency-dollar"></i><span>Finanças</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i
                        class="bi bi-person"></i><span>Artistas</span></a></li>
        </ul>
    </nav>


    <!-- Modal para saque contas -->
    <div class="modal fade" id="sake" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="sakeLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark" id="sakeLabel">
                        <i class="bi bi-wallet2 me-2 text-pink"></i>Solicitar Saque
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($can_withdraw && $bank_account): ?>

                        <!-- ─ Estado: pode sacar ─ -->
                        <p class="text-muted small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            O valor é processado pela equipa em até 48 horas. Receberás uma notificação por e-mail.
                        </p>
                        <form method="post" action="finances/withdrawal_process" class="needs-validation row g-3" novalidate
                            id="withdrawal-form">
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <!-- Valor (preenchido automaticamente) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Valor de Saque <span
                                        class="text-muted">(AOA)</span></label>
                                <input type="text" class="form-control" readonly
                                    value="<?php echo number_format($balance_aoa, 2, ',', '.'); ?>">
                                <div class="form-text">Valor total disponível</div>
                            </div>

                            <!-- Conta destino -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Conta Destino</label>
                                <div class="form-control bg-light d-flex align-items-center gap-2"
                                    style="height:auto;padding:.6rem .9rem">
                                    <?php if (in_array($bank_account['type_account'], ['IBAN', 'Multicaixa'])): ?>
                                        <i class="bi bi-bank text-primary"></i>
                                        <div>
                                            <div class="fw-semibold small">
                                                <?php echo htmlspecialchars($bank_account['full_name_account']); ?></div>
                                            <div class="text-muted" style="font-size:.75rem">IBAN ·
                                                <?php echo $bank_account['iban'] ? substr(htmlspecialchars($bank_account['iban']), -8) : 'N/A'; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <i class="bi bi-phone text-success"></i>
                                        <div>
                                            <div class="fw-semibold small">
                                                <?php echo htmlspecialchars($bank_account['full_name_account']); ?></div>
                                            <div class="text-muted" style="font-size:.75rem">Express ·
                                                <?php echo htmlspecialchars($bank_account['tel_account'] ?? 'N/A'); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Senha de confirmação -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Confirmar com a tua senha <span
                                        class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" required
                                    placeholder="Senha da tua conta Wasom Upfy" autocomplete="current-password">
                                <div class="invalid-feedback">Insere a tua senha para confirmar o saque.</div>
                            </div>

                            <div class="col-12">
                                <div class="alert alert-warning py-2 small mb-0">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Ao confirmar, autorizes o envio de
                                    <strong><?php echo number_format($balance_aoa, 2, ',', '.'); ?> AOA</strong>
                                    para a conta registada. Esta operação não pode ser revertida.
                                </div>
                            </div>

                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-pink">
                                    <i class="bi bi-send me-2"></i>Confirmar Saque
                                </button>
                            </div>
                        </form>

                    <?php else: ?>
                        <!-- ─ Estado: não pode sacar ─ -->
                        <div class="text-center py-4">
                            <i class="bi bi-lock fs-1 text-muted mb-3 d-block"></i>
                            <?php if (!$plan_paid): ?>
                                <h6>Plano não activo</h6>
                                <p class="text-muted small">Activa o teu plano para começar a receber royalties e fazer
                                    saques.</p>
                                <a href="payment" class="btn btn-pink btn-sm">Activar Plano</a>
                            <?php elseif (!$bank_account): ?>
                                <h6>Sem conta bancária registada</h6>
                                <p class="text-muted small">Para sacar os teus royalties, primeiro regista uma conta
                                    bancária (IBAN ou Multicaixa Express).</p>
                                <a href="withdraw" class="btn btn-pink btn-sm">
                                    <i class="bi bi-bank me-1"></i>Registar Conta Bancária
                                </a>
                            <?php else: ?>
                                <h6>Saldo insuficiente</h6>
                                <p class="text-muted small">O mínimo para saque é <strong>10.000 Kz</strong>. O teu saldo
                                    actual é <strong><?php echo number_format($balance_aoa, 2, ',', '.'); ?> AOA</strong>.
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
    <!-- Modal para saque contas fim -->


    <!-- ════ MODAL — Logout ════ -->
    <div class="modal fade" id="logoutwasomupfy" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="logoutwasomupfyLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:44px;height:44px;background:rgba(220,53,69,.12)">
                            <i class="bi bi-box-arrow-right fs-5 text-danger"></i>
                        </div>
                        <div>
                            <h5 class="modal-title text-dark mb-0" id="logoutwasomupfyLabel">Terminar sessão</h5>
                            <small class="text-muted">@<?php echo $user_name; ?></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body pt-2">
                    <!-- Informação da sessão actual -->
                    <div class="rounded-3 p-3 mb-3" style="background:rgba(0,0,0,.04)">
                        <div class="row g-2" style="font-size:.82rem">
                            <div class="col-6 d-flex gap-2 align-items-start">
                                <i class="bi bi-clock text-muted mt-1 flex-shrink-0"></i>
                                <div>
                                    <div class="text-muted">Duração da sessão</div>
                                    <div class="fw-semibold text-dark"><?php echo $session_duration_str; ?></div>
                                </div>
                            </div>
                            <div class="col-6 d-flex gap-2 align-items-start">
                                <i class="bi bi-calendar3 text-muted mt-1 flex-shrink-0"></i>
                                <div>
                                    <div class="text-muted">Último acesso</div>
                                    <div class="fw-semibold text-dark"><?php echo $last_login_str; ?></div>
                                </div>
                            </div>
                            <div class="col-6 d-flex gap-2 align-items-start">
                                <i class="bi bi-globe text-muted mt-1 flex-shrink-0"></i>
                                <div>
                                    <div class="text-muted">Localização</div>
                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($sess_location); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 d-flex gap-2 align-items-start">
                                <i class="bi bi-browser-chrome text-muted mt-1 flex-shrink-0"></i>
                                <div>
                                    <div class="text-muted">Navegador</div>
                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($browser); ?></div>
                                </div>
                            </div>
                            <div class="col-6 d-flex gap-2 align-items-start">
                                <i class="bi bi-hdd-network text-muted mt-1 flex-shrink-0"></i>
                                <div>
                                    <div class="text-muted">IP</div>
                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($sess_ip); ?></div>
                                </div>
                            </div>
                            <div class="col-6 d-flex gap-2 align-items-start">
                                <i class="bi bi-person-badge text-muted mt-1 flex-shrink-0"></i>
                                <div>
                                    <div class="text-muted">Membro desde</div>
                                    <div class="fw-semibold text-dark"><?php echo $member_since; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-dark text-center mb-0" style="font-size:.9rem">
                        Tens a certeza que queres terminar a sessão?<br>
                        <span class="text-muted" style="font-size:.8rem">Terás de iniciar sessão novamente para aceder
                            ao painel.</span>
                    </p>
                </div>

                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">
                        <i class="bi bi-arrow-left me-1"></i>Não, continuar
                    </button>
                    <button class="btn btn-danger flex-fill" type="button" onclick="logout_wasomupfy()">
                        <i class="bi bi-box-arrow-right me-1"></i>Sim, terminar
                    </button>
                </div>

            </div>
        </div>
    </div>
    <!-- ════ MODAL — Logout  FIM ════ -->

    <script>
        function logout_wasomupfy() {
            window.location = '../logout';
        }
    </script>

    <!-- ═══ JS ═══ -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="../../js/theme.wp.js"></script>
    <script src="../../js/wp.tools.js"></script>
    <script>
        // Tooltips
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

        // DataTables (apenas se a tabela tiver linhas)
        $(document).ready(function() {
            <?php if (!empty($royalties)): ?>
                $('#royaltiesTable').DataTable({
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    lengthChange: false,
                    pageLength: 10,
                    order: [
                        [2, 'desc']
                    ],
                    language: {
                        search: 'Pesquisar royalties:',
                        info: 'A mostrar _START_ a _END_ de _TOTAL_',
                        paginate: {
                            next: 'Próximo',
                            previous: 'Anterior'
                        },
                        emptyTable: 'Sem royalties registados.'
                    }
                });
            <?php endif; ?>

            <?php if (!empty($withdrawals)): ?>
                $('#withdrawalsTable').DataTable({
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    lengthChange: false,
                    pageLength: 10,
                    order: [
                        [1, 'desc']
                    ],
                    language: {
                        search: 'Pesquisar saques:',
                        info: 'A mostrar _START_ a _END_ de _TOTAL_',
                        paginate: {
                            next: 'Próximo',
                            previous: 'Anterior'
                        },
                        emptyTable: 'Sem saques registados.'
                    }
                });
            <?php endif; ?>
        });

        // Validação form saque
        <?php if ($can_withdraw && !$has_pending_withdrawal): ?>
            document.getElementById('withdrawal-form')?.addEventListener('submit', function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                }
                this.classList.add('was-validated');
            });
        <?php endif; ?>
    </script>
</body>