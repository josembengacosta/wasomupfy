<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Perfil do Utilizador
// Arquivo: dashboard/user/profile.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$user = getUserById((int)$_SESSION['id_users']);
if (!$user) {
    session_destroy();
    redirect('/login');
}

$id_users = (int)$user['id_users'];
$db       = getDB();

// ── Security record ───────────────────────────
$sec = $db->prepare("SELECT * FROM _users_security WHERE id_users = ?");
$sec->execute([$id_users]);
$security = $sec->fetch() ?: [];

// ── Plan ──────────────────────────────────────
$plan = null;
if ($user['plan_selected']) {
    $ps = $db->prepare("SELECT * FROM _plans WHERE id_plan = ?");
    $ps->execute([$user['plan_selected']]);
    $plan = $ps->fetch();
}

// ── Wallet ────────────────────────────────────
$wl = $db->prepare("SELECT * FROM _wallet WHERE id_users = ?");
$wl->execute([$id_users]);
$wallet = $wl->fetch() ?: ['balance_usd' => 0, 'total_earned' => 0, 'total_withdrawn' => 0];

// ── Artists count ─────────────────────────────
$ac = $db->prepare("SELECT COUNT(*) FROM _artist WHERE id_users = ?");
$ac->execute([$id_users]);
$artist_count = (int)$ac->fetchColumn();

// ── Albums count ──────────────────────────────
$alc = $db->prepare("SELECT COUNT(*) FROM _album WHERE id_users = ? AND status_album = 'approved'");
$alc->execute([$id_users]);
$album_count = (int)$alc->fetchColumn();

// ── Payment method ────────────────────────────
$pm = $db->prepare("SELECT payment_method FROM _payment WHERE id_users = ? AND status_payment='approved' ORDER BY creat_payment DESC LIMIT 1");
$pm->execute([$id_users]);
$payment_row = $pm->fetch();
$payment_method = $payment_row ? $payment_row['payment_method'] : null;

// ── Bank account ──────────────────────────────
$ba = $db->prepare("SELECT type_account FROM _account WHERE id_users = ? AND is_default = 1 LIMIT 1");
$ba->execute([$id_users]);
$bank = $ba->fetch();

// ── Active sessions ───────────────────────────
$sess_st = $db->prepare("
    SELECT id_session, ip_address, user_agent, country, city, creat_session, last_activity
    FROM _users_sessions WHERE id_users = ? AND is_active = 1
    ORDER BY last_activity DESC
");
$sess_st->execute([$id_users]);
$sessions = $sess_st->fetchAll(PDO::FETCH_ASSOC);

// ── Current session token ─────────────────────
$current_token = $_SESSION['session_token'] ?? '';

// ── Helpers ───────────────────────────────────
$csrf       = htmlspecialchars($_SESSION['csrf_token']);
$photo_base = rtrim(APP_URL, '/') . '/assets/comprovantes/uploads/users/';
$photo_url  = $user['photo_user'] ? $photo_base . $user['photo_user'] : null;
$full_name  = trim($user['first_name'] . ' ' . ($user['second_name'] ?? ''));
$joined     = date('d \d\e F \d\e Y', strtotime($user['creat_user']));
$account_id = str_pad($id_users, 6, '0', STR_PAD_LEFT);

// Plan badge colour
$plan_slug   = $plan ? $plan['slug_plan'] : 'none';
$plan_colors = [
    'single' => ['bg' => '#1d6bf3', 'text' => '#fff', 'label' => 'Single'],
    'album'  => ['bg' => '#198754', 'text' => '#fff', 'label' => 'Album'],
    'artist' => ['bg' => '#6f42c1', 'text' => '#fff', 'label' => 'Artist'],
    'label'  => ['bg' => 'linear-gradient(135deg,#b8860b,#ffd700)', 'text' => '#3d2800', 'label' => 'Label'],
];
$pc = $plan_colors[$plan_slug] ?? ['bg' => '#6c757d', 'text' => '#fff', 'label' => ucfirst($plan_slug)];

// Plan expiry
$plan_expires_str = '—';
$plan_days_left   = null;
if ($user['plan_expires_at']) {
    $exp = strtotime($user['plan_expires_at']);
    $plan_days_left = max(0, ceil(($exp - time()) / 86400));
    $plan_expires_str = date('d/m/Y', $exp);
} elseif ($user['plan_activated_at']) {
    $plan_expires_str = 'Sem expiração';
}

// Method labels
$method_labels = [
    'bank_transfer' => 'Transferência Bancária',
    'multicaixa' => 'Multicaixa',
    'paypal' => 'PayPal',
    'card' => 'Cartão',
    'other' => 'Outro'
];
$bank_labels = [
    'IBAN' => 'IBAN',
    'Express' => 'Multicaixa Express',
    'PayPal' => 'PayPal',
    'Multicaixa' => 'Multicaixa TPA',
    'TPA' => 'TPA'
];

// Gender labels
$gender_labels = ['M' => 'Masculino', 'F' => 'Feminino', 'Outro' => 'Outro'];

// 2FA status
$twofa_on = !empty($security['two_factor_enabled']);

// Can generate recovery (session flag)
$can_recovery = !empty($_SESSION['can_generate_recovery']);

// Success message from email verification redirect
$verify_status = $_GET['verify'] ?? '';

// Active section (URL hash fallback)
$active_section = 'perfil';

// Email verified at
$verified_at_str = $user['email_verified'] && $user['email_verified_at']
    ? date('d/m/Y', strtotime($user['email_verified_at'])) : null;
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <title>Meu Perfil — Wasom Upfy</title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <link rel="stylesheet" href="../../css/dashboard-style.css" />
    <link rel="stylesheet" href="../../css/lastest-style.css" />
    <style>
        :root {
            --wasom: #FF0089;
            --wasom-dark: #cc006d;
        }

        /* ── Layout ── */
        .profile-layout {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 24px;
            align-items: start;
        }

        @media(max-width:768px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                display: none;
            }
        }

        /* ── Sidebar nav ── */
        .profile-sidebar {
            position: sticky;
            top: 80px;
        }

        .sidebar-nav .nav-link {
            border-radius: 12px;
            padding: 10px 14px;
            font-size: .875rem;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all .2s;
            margin-bottom: 2px;
            font-weight: 500;
        }

        .sidebar-nav .nav-link:hover {
            background: rgba(255, 0, 137, .08);
            color: var(--wasom);
        }

        .sidebar-nav .nav-link.active {
            background: rgba(255, 0, 137, .12);
            color: var(--wasom);
            font-weight: 700;
        }

        .sidebar-nav .nav-link i {
            font-size: 1.05rem;
            width: 20px;
            text-align: center;
        }

        /* ── Section ── */
        .profile-section {
            display: none;
        }

        .profile-section.active {
            display: block;
        }

        /* ── Avatar ── */
        .avatar-ring {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 3px solid var(--wasom);
            object-fit: cover;
            box-shadow: 0 4px 20px rgba(255, 0, 137, .25);
            display: block;
        }

        .avatar-ring-ph {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 3px solid var(--wasom);
            background: #f1f3f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.8rem;
            color: #ccc;
            box-shadow: 0 4px 20px rgba(255, 0, 137, .1);
        }

        .avatar-upload-btn {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--wasom);
            color: #fff;
            border: 2px solid #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: .8rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .25);
            transition: transform .2s;
        }

        .avatar-upload-btn:hover {
            transform: scale(1.1);
        }

        /* ── Profile header card ── */
        .profile-hero {
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            margin-bottom: 20px;
        }

        .profile-hero-cover {
            height: 100px;
            background: linear-gradient(135deg, #FF0089 0%, #FF4D4D 100%);
        }

        .profile-hero-body {
            padding: 0 24px 24px;
            border: 1.5px solid rgba(0, 0, 0, .07);
            border-top: none;
            border-radius: 0 0 20px 20px;
        }

        .avatar-wrapper {
            position: relative;
            display: inline-block;
            margin-top: -52px;
            margin-bottom: 12px;
        }

        /* ── Info rows ── */
        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 0, 0, .06);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-row-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(255, 0, 137, .08);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--wasom);
            flex-shrink: 0;
            font-size: .9rem;
        }

        .info-row-label {
            font-size: .72rem;
            color: #999;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 2px;
        }

        .info-row-value {
            font-size: .9rem;
            font-weight: 600;
        }

        /* ── Email badge ── */
        .email-verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 700;
            cursor: default;
        }

        .email-verified-badge.verified {
            background: rgba(25, 135, 84, .1);
            color: #198754;
            border: 1px solid rgba(25, 135, 84, .2);
        }

        .email-verified-badge.unverified {
            background: rgba(255, 193, 7, .15);
            color: #856404;
            border: 1px solid rgba(255, 193, 7, .3);
            cursor: pointer;
            transition: all .2s;
        }

        .email-verified-badge.unverified:hover {
            background: rgba(255, 193, 7, .3);
        }

        /* ── Plan badge ── */
        .plan-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .5px;
        }

        /* ── Stats grid ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 12px;
        }

        .stat-card {
            border-radius: 14px;
            padding: 16px;
            text-align: center;
            background: rgba(255, 0, 137, .05);
            border: 1px solid rgba(255, 0, 137, .1);
        }

        .stat-card .num {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--wasom);
        }

        .stat-card .lbl {
            font-size: .7rem;
            color: #999;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-top: 2px;
        }

        /* ── Section card ── */
        .section-card {
            border-radius: 18px;
            border: 1.5px solid rgba(0, 0, 0, .07);
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .04);
        }

        .section-title {
            font-size: 1rem;
            font-weight: 800;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--wasom);
        }

        /* ── Password strength ── */
        .strength-bar {
            height: 5px;
            border-radius: 4px;
            transition: width .3s, background .3s;
        }

        /* ── Session item ── */
        .session-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 0, 0, .05);
        }

        .session-item:last-child {
            border-bottom: none;
        }

        .session-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255, 0, 137, .08);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--wasom);
            flex-shrink: 0;
        }

        .session-current {
            background: rgba(25, 135, 84, .08);
        }

        .session-current .session-icon {
            background: rgba(25, 135, 84, .12);
            color: #198754;
        }

        /* ── Recovery key display ── */
        .recovery-display {
            background: rgba(0, 0, 0, .04);
            border: 2px dashed var(--wasom);
            border-radius: 14px;
            padding: 20px;
            font-family: monospace;
            font-size: .78rem;
            word-break: break-all;
            line-height: 2;
            letter-spacing: .5px;
            user-select: all;
        }

        .recovery-segment {
            display: inline-block;
            margin: 2px;
        }

        /* ── 2FA secret display ── */
        .totp-secret {
            font-family: monospace;
            font-size: .85rem;
            background: rgba(0, 0, 0, .04);
            border-radius: 10px;
            padding: 10px 14px;
            letter-spacing: 2px;
            word-break: break-all;
            user-select: all;
        }

        /* ── Danger zone ── */
        .danger-card {
            border: 2px solid rgba(220, 53, 69, .3);
            border-radius: 16px;
            padding: 20px;
            background: rgba(220, 53, 69, .02);
            margin-bottom: 16px;
        }

        /* ── Toggle switch ── */
        .form-switch .form-check-input:checked {
            background-color: var(--wasom);
            border-color: var(--wasom);
        }

        .form-switch .form-check-input:focus {
            border-color: var(--wasom);
            box-shadow: 0 0 0 .2rem rgba(255, 0, 137, .2);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--wasom);
            box-shadow: 0 0 0 .2rem rgba(255, 0, 137, .2);
        }

        /* ── Section divider ── */
        .sec-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 24px 0 16px;
        }

        .sec-divider span {
            font-weight: 700;
            font-size: .78rem;
            color: var(--wasom);
            white-space: nowrap;
        }

        .sec-divider::before,
        .sec-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(0, 0, 0, .08);
        }
    </style>
</head>

<body>


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
                        <a class="nav-link" href="../finances/overview"><i class="bi bi-currency-dollar"></i>
                            Finanças</a>
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
                <a href="../notifications" class="text-white me-2" aria-label="Notificações">
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
                        <a class="dropdown-item" href="../page/plans"><i class="bi bi-star me-2"></i> Planos</a>
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

    <!-- Main -->
    <div class="container my-4">

        <!-- Mobile section tabs -->
        <div class="d-flex gap-2 mb-3 overflow-auto d-md-none pb-1" style="scrollbar-width:none">
            <?php foreach ([['perfil', 'person', 'Perfil'], ['seguranca', 'shield-lock', 'Segurança'], ['notificacoes', 'bell', 'Notif.'], ['sessoes', 'display', 'Sessões'], ['perigo', 'exclamation-triangle', 'Perigo']] as [$sid, $icon, $label]): ?>
                <button class="btn btn-sm btn-outline-secondary flex-shrink-0 mobile-tab"
                    data-section="<?php echo $sid; ?>">
                    <i class="bi bi-<?php echo $icon; ?> me-1"></i><?php echo $label; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="profile-layout">

            <!-- ══ SIDEBAR ══ -->
            <aside class="profile-sidebar d-none d-md-block">
                <div class="section-card p-3 mb-3 text-center">
                    <div class="d-flex justify-content-center mb-2">
                        <?php if ($photo_url): ?>
                            <img src="<?php echo htmlspecialchars($photo_url); ?>" class="avatar-ring" alt="Foto"
                                id="sidebar-avatar" />
                        <?php else: ?>
                            <div class="avatar-ring-ph" id="sidebar-avatar-ph"><i class="bi bi-person"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="fw-bold"><?php echo htmlspecialchars($full_name); ?></div>
                    <div class="text-muted small">@<?php echo htmlspecialchars($user['user_name'] ?? '—'); ?></div>
                    <div class="mt-2">
                        <span class="plan-badge"
                            style="background:<?php echo $pc['bg']; ?>;color:<?php echo $pc['text']; ?>">
                            <i class="bi bi-star-fill" style="font-size:.6rem"></i>
                            <?php echo $pc['label']; ?>
                        </span>
                    </div>
                </div>
                <nav class="sidebar-nav">
                    <a href="#" class="nav-link active" data-section="perfil">
                        <i class="bi bi-person"></i>Perfil
                    </a>
                    <a href="#" class="nav-link" data-section="seguranca">
                        <i class="bi bi-shield-lock"></i>Segurança
                    </a>
                    <a href="#" class="nav-link" data-section="notificacoes">
                        <i class="bi bi-bell"></i>Notificações
                    </a>
                    <a href="#" class="nav-link" data-section="sessoes">
                        <i class="bi bi-display"></i>Sessões
                    </a>
                    <a href="#" class="nav-link text-danger" data-section="perigo">
                        <i class="bi bi-exclamation-triangle"></i>Zona de Perigo
                    </a>
                </nav>
            </aside>

            <!-- ══ CONTENT ══ -->
            <div>

                <!-- ████ SECÇÃO 1 — PERFIL ████ -->
                <div class="profile-section active" id="sec-perfil">

                    <!-- Hero card -->
                    <div class="profile-hero">
                        <div class="profile-hero-cover"></div>
                        <div class="profile-hero-body">
                            <div class="d-flex align-items-end justify-content-between flex-wrap gap-2">
                                <div>
                                    <div class="avatar-wrapper">
                                        <?php if ($photo_url): ?>
                                            <img src="<?php echo htmlspecialchars($photo_url); ?>" class="avatar-ring"
                                                id="hero-avatar" />
                                        <?php else: ?>
                                            <div class="avatar-ring-ph" id="hero-avatar-ph"><i class="bi bi-person"></i>
                                            </div>
                                        <?php endif; ?>
                                        <!-- Hover overlay for edit -->
                                        <div class="avatar-upload-btn"
                                            onclick="showSection('seguranca');setTimeout(()=>document.getElementById('editProfileModal')&&new bootstrap.Modal(document.getElementById('editProfileModal')).show(),200)"
                                            title="Editar perfil">
                                            <i class="bi bi-pencil-fill" style="font-size:.65rem"></i>
                                        </div>
                                    </div>
                                    <h2 class="fw-bold mb-0"><?php echo htmlspecialchars($full_name); ?></h2>
                                    <div class="text-muted small mb-2">
                                        @<?php echo htmlspecialchars($user['user_name'] ?? '—'); ?> ·
                                        #<?php echo $account_id; ?></div>
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <span class="plan-badge"
                                            style="background:<?php echo $pc['bg']; ?>;color:<?php echo $pc['text']; ?>">
                                            <i class="bi bi-star-fill"
                                                style="font-size:.6rem"></i><?php echo $pc['label']; ?>
                                        </span>
                                        <span class="badge bg-secondary fw-normal" style="font-size:.72rem">
                                            <i class="bi bi-shield-check me-1"></i>Administrador
                                        </span>
                                        <?php if ($twofa_on): ?>
                                            <span class="badge"
                                                style="background:rgba(25,135,84,.12);color:#198754;font-size:.72rem">
                                                <i class="bi bi-lock-fill me-1"></i>2FA Activo
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <button class="btn btn-sm px-3" style="background:var(--wasom);color:#fff"
                                    data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                    <i class="bi bi-pencil me-1"></i>Editar Perfil
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="stats-grid mb-4">
                        <div class="stat-card">
                            <div class="num"><?php echo $album_count; ?></div>
                            <div class="lbl">Álbuns Aprovados</div>
                        </div>
                        <div class="stat-card">
                            <div class="num"><?php echo $artist_count; ?></div>
                            <div class="lbl">Artistas</div>
                        </div>
                        <div class="stat-card">
                            <div class="num">Kz<?php echo number_format((float)$wallet['total_earned'], 2); ?></div>
                            <div class="lbl">Total Faturado</div>
                        </div>
                        <div class="stat-card">
                            <div class="num">Kz<?php echo number_format((float)$wallet['total_withdrawn'], 2); ?></div>
                            <div class="lbl">Total Sacado</div>
                        </div>
                    </div>

                    <!-- Info personal -->
                    <div class="section-card">
                        <div class="section-title"><i class="bi bi-person-vcard"></i>Informações Pessoais</div>

                        <!-- Email -->
                        <div class="info-row">
                            <div class="info-row-icon"><i class="bi bi-envelope"></i></div>
                            <div style="flex:1">
                                <div class="info-row-label">Email</div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span
                                        class="info-row-value"><?php echo htmlspecialchars($user['email_user']); ?></span>
                                    <?php if ($user['email_verified']): ?>
                                        <span class="email-verified-badge verified">
                                            <i
                                                class="bi bi-patch-check-fill"></i>Verificado<?php echo $verified_at_str ? ' em ' . $verified_at_str : ''; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="email-verified-badge unverified" onclick="resendVerifyEmail()"
                                            title="Clica para enviar email de verificação">
                                            <i class="bi bi-exclamation-circle"></i>Não verificado · Verificar agora
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="info-row">
                            <div class="info-row-icon"><i class="bi bi-telephone"></i></div>
                            <div>
                                <div class="info-row-label">Telefone</div>
                                <div class="info-row-value"><?php echo htmlspecialchars($user['tel_user'] ?? '—'); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="info-row">
                            <div class="info-row-icon"><i class="bi bi-geo-alt"></i></div>
                            <div>
                                <div class="info-row-label">Localização</div>
                                <div class="info-row-value">
                                    <?php echo htmlspecialchars(implode(', ', array_filter([$user['city_user'] ?? '', $user['country_user'] ?? ''])) ?: '—'); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Gender -->
                        <div class="info-row">
                            <div class="info-row-icon"><i class="bi bi-person-badge"></i></div>
                            <div>
                                <div class="info-row-label">Género</div>
                                <div class="info-row-value">
                                    <?php echo htmlspecialchars($gender_labels[$user['gender'] ?? ''] ?? '—'); ?></div>
                            </div>
                        </div>

                        <!-- Member since -->
                        <div class="info-row">
                            <div class="info-row-icon"><i class="bi bi-calendar3"></i></div>
                            <div>
                                <div class="info-row-label">Membro desde</div>
                                <div class="info-row-value"><?php echo $joined; ?></div>
                            </div>
                        </div>

                        <!-- Artist / Band name -->
                        <div class="info-row">
                            <div class="info-row-icon"><i class="bi bi-mic"></i></div>
                            <div>
                                <div class="info-row-label">Nome Artístico / Banda</div>
                                <div class="info-row-value">
                                    <?php echo htmlspecialchars($user['name_artist_band'] ?? '—'); ?></div>
                            </div>
                        </div>

                        <!-- Bio -->
                        <?php if ($user['about_user']): ?>
                            <div class="info-row">
                                <div class="info-row-icon"><i class="bi bi-file-text"></i></div>
                                <div>
                                    <div class="info-row-label">Bio</div>
                                    <div class="info-row-value" style="font-weight:400;line-height:1.6">
                                        <?php echo nl2br(htmlspecialchars($user['about_user'])); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Website -->
                        <?php if ($user['url_user']): ?>
                            <div class="info-row">
                                <div class="info-row-icon"><i class="bi bi-globe"></i></div>
                                <div>
                                    <div class="info-row-label">Website</div>
                                    <div class="info-row-value">
                                        <a href="<?php echo htmlspecialchars($user['url_user']); ?>" target="_blank"
                                            rel="noopener" style="color:var(--wasom)">
                                            <?php echo htmlspecialchars($user['url_user']); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Plan & Finances -->
                    <div class="section-card">
                        <div class="section-title"><i class="bi bi-credit-card"></i>Plano & Finanças</div>

                        <div class="info-row">
                            <div class="info-row-icon"><i class="bi bi-star"></i></div>
                            <div>
                                <div class="info-row-label">Plano Activo</div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span
                                        class="info-row-value"><?php echo $plan ? htmlspecialchars($plan['name_plan']) : 'Sem plano'; ?></span>
                                    <?php if ($plan): ?>
                                        <span class="badge text-muted" style="background:rgba(0,0,0,.06);font-size:.7rem">
                                            <?php echo $plan['royalty_rate']; ?>% royalties
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-row-icon"><i class="bi bi-calendar-check"></i></div>
                            <div>
                                <div class="info-row-label">Expiração do Plano</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="info-row-value"><?php echo $plan_expires_str; ?></span>
                                    <?php if ($plan_days_left !== null): ?>
                                        <span
                                            class="badge <?php echo $plan_days_left <= 14 ? 'bg-warning text-dark' : 'bg-success'; ?>"
                                            style="font-size:.7rem">
                                            <?php echo $plan_days_left; ?> dia<?php echo $plan_days_left != 1 ? 's' : ''; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-row-icon"><i class="bi bi-wallet2"></i></div>
                            <div>
                                <div class="info-row-label">Saldo Actual</div>
                                <div class="info-row-value" style="color:var(--wasom)">
                                    $<?php echo number_format((float)$wallet['balance_usd'], 2); ?></div>
                            </div>
                        </div>

                        <?php if ($payment_method): ?>
                            <div class="info-row">
                                <div class="info-row-icon"><i class="bi bi-receipt"></i></div>
                                <div>
                                    <div class="info-row-label">Método de Pagamento</div>
                                    <div class="info-row-value">
                                        <?php echo $method_labels[$payment_method] ?? $payment_method; ?></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($bank): ?>
                            <div class="info-row">
                                <div class="info-row-icon"><i class="bi bi-bank"></i></div>
                                <div>
                                    <div class="info-row-label">Conta Bancária</div>
                                    <div class="info-row-value">
                                        <?php echo $bank_labels[$bank['type_account']] ?? $bank['type_account']; ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div><!-- /sec-perfil -->


                <!-- ████ SECÇÃO 2 — SEGURANÇA ████ -->
                <div class="profile-section" id="sec-seguranca">

                    <!-- Edit Profile modal trigger -->
                    <div class="section-card mb-4">
                        <div class="section-title"><i class="bi bi-pencil-square"></i>Editar Perfil</div>
                        <p class="text-muted small mb-3">Actualiza o teu nome, nome de utilizador, localização, bio e
                            foto de perfil.</p>
                        <button class="btn btn-sm px-4" style="background:var(--wasom);color:#fff"
                            data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="bi bi-pencil me-1"></i>Editar Perfil
                        </button>
                    </div>

                    <!-- Change password -->
                    <div class="section-card">
                        <div class="section-title"><i class="bi bi-key"></i>Alterar Senha</div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Senha Actual <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="old-password"
                                        placeholder="••••••••" />
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePwd('old-password',this)"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Nova Senha <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new-password"
                                        placeholder="Mín. 8 caracteres" oninput="checkStrength(this.value)" />
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePwd('new-password',this)"><i class="bi bi-eye"></i></button>
                                </div>
                                <div class="mt-1">
                                    <div class="progress" style="height:4px;border-radius:4px">
                                        <div class="strength-bar" id="strength-bar" style="width:0%;background:#dc3545">
                                        </div>
                                    </div>
                                    <small id="strength-label" class="text-muted" style="font-size:.7rem"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Confirmar Senha <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm-password"
                                        placeholder="Repete a nova senha" />
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePwd('confirm-password',this)"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-sm px-4" style="background:var(--wasom);color:#fff"
                                    onclick="changePassword()">
                                    <i class="bi bi-key me-1"></i>Alterar Senha
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2FA -->
                    <div class="section-card">
                        <div class="section-title"><i class="bi bi-shield-check"></i>Autenticação de Dois Factores</div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="fw-semibold small mb-1">Estado actual</div>
                                <?php if ($twofa_on): ?>
                                    <span class="badge" style="background:rgba(25,135,84,.12);color:#198754">
                                        <i class="bi bi-lock-fill me-1"></i>Activado
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background:rgba(220,53,69,.1);color:#dc3545">
                                        <i class="bi bi-unlock me-1"></i>Desactivado
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="2fa-toggle" role="switch"
                                    <?php echo $twofa_on ? 'checked' : ''; ?> onchange="toggle2FA(this)" />
                            </div>
                        </div>
                        <!-- 2FA setup area -->
                        <div id="2fa-setup" class="d-none">
                            <div class="sec-divider"><span>Configuração</span></div>
                            <div class="row g-3 align-items-start">
                                <div class="col-auto text-center">
                                    <img id="2fa-qr" src="" alt="QR Code" class="border rounded-3 p-1"
                                        style="width:160px;height:160px" />
                                </div>
                                <div class="col">
                                    <p class="small text-muted mb-2">Escaneia o QR Code com o teu autenticador (Google
                                        Authenticator, Authy, etc.) ou insere o código manualmente:</p>
                                    <div class="totp-secret mb-3" id="2fa-secret-display">—</div>
                                    <label class="form-label fw-semibold small">Código de verificação (6
                                        dígitos)</label>
                                    <div class="input-group" style="max-width:220px">
                                        <input type="text" class="form-control" id="totp-code" maxlength="6"
                                            placeholder="000000" inputmode="numeric" pattern="[0-9]{6}" />
                                        <button class="btn btn-sm" style="background:var(--wasom);color:#fff"
                                            onclick="confirm2FA()">
                                            Confirmar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Disable 2FA password confirmation -->
                        <div id="2fa-disable" class="d-none">
                            <div class="sec-divider"><span>Desactivar 2FA</span></div>
                            <div class="input-group" style="max-width:320px">
                                <input type="password" class="form-control form-control-sm" id="2fa-disable-pwd"
                                    placeholder="Confirma a tua senha" />
                                <button class="btn btn-sm btn-outline-danger" onclick="disable2FA()">Desactivar</button>
                            </div>
                        </div>
                    </div>

                    <!-- Recovery Key -->
                    <div class="section-card">
                        <div class="section-title"><i class="bi bi-life-preserver"></i>Chave de Recuperação</div>
                        <p class="text-muted small mb-3">
                            A chave de recuperação é usada para aceder à tua conta caso percas o acesso ao teu email ou
                            2FA.
                            São <strong>24 segmentos</strong> de 4 caracteres (estilo Mega).
                            <strong>Guarda-a offline em local seguro.</strong>
                        </p>
                        <?php if (!$can_recovery): ?>
                            <div class="alert alert-warning small d-flex gap-2 mb-3">
                                <i class="bi bi-lock-fill flex-shrink-0 mt-1"></i>
                                <div>Para gerar uma nova chave, deves primeiro <strong>alterar a tua senha</strong> na
                                    secção acima. Isto garante que só o dono real da conta pode revogar a chave anterior.
                                </div>
                            </div>
                        <?php endif; ?>
                        <div id="recovery-key-display" class="d-none mb-3">
                            <div class="recovery-display" id="recovery-key-text"></div>
                            <div class="text-muted mt-2" style="font-size:.72rem"><i
                                    class="bi bi-exclamation-triangle me-1"></i>Copia ou faz download agora — esta chave
                                não voltará a ser mostrada.</div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-sm btn-outline-secondary" id="btn-gen-recovery"
                                <?php echo $can_recovery ? '' : 'disabled'; ?> onclick="generateRecovery()">
                                <i class="bi bi-arrow-repeat me-1"></i>Gerar Nova Chave
                            </button>
                            <button class="btn btn-sm btn-outline-secondary d-none" id="btn-download-recovery"
                                onclick="downloadRecovery()">
                                <i class="bi bi-download me-1"></i>Download TXT
                            </button>
                            <button class="btn btn-sm btn-outline-secondary d-none" id="btn-copy-recovery"
                                onclick="copyRecovery()">
                                <i class="bi bi-clipboard me-1"></i>Copiar
                            </button>
                        </div>
                    </div>

                </div><!-- /sec-seguranca -->


                <!-- ████ SECÇÃO 3 — NOTIFICAÇÕES ████ -->
                <div class="profile-section" id="sec-notificacoes">
                    <div class="section-card">
                        <div class="section-title"><i class="bi bi-bell"></i>Preferências de Notificação</div>
                        <p class="text-muted small mb-4">Escolhe como e quando queres ser notificado.</p>

                        <?php
                        $notifs = [
                            [
                                'id' => 'notif_email',
                                'icon' => 'bi-envelope',
                                'label' => 'Notificações por Email',
                                'desc' => 'Recebe emails sobre streams, levantamentos e lançamentos.',
                                'val' => $user['notif_email'] ?? 1
                            ],
                            [
                                'id' => 'notif_push',
                                'icon' => 'bi-bell-fill',
                                'label' => 'Notificações Push',
                                'desc' => 'Alertas em tempo real no navegador.',
                                'val' => $user['notif_push'] ?? 0
                            ],
                            [
                                'id' => 'notif_weekly',
                                'icon' => 'bi-calendar-week',
                                'label' => 'Resumo Semanal',
                                'desc' => 'Recebe um resumo das actividades da semana, todos os segundas.',
                                'val' => $user['notif_weekly'] ?? 1
                            ],
                            [
                                'id' => 'notif_releases',
                                'icon' => 'bi-disc',
                                'label' => 'Actualizações de Lançamentos',
                                'desc' => 'Notificações quando um lançamento é aprovado, rejeitado ou entra em revisão.',
                                'val' => $user['notif_releases'] ?? 1
                            ],
                            [
                                'id' => 'notif_payments',
                                'icon' => 'bi-currency-dollar',
                                'label' => 'Pagamentos & Royalties',
                                'desc' => 'Avisos de pagamentos recebidos, levantamentos processados e royalties creditados.',
                                'val' => $user['notif_payments'] ?? 1
                            ],
                        ];
                        foreach ($notifs as $n): ?>
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-3 pb-3 border-bottom">
                                <div class="d-flex align-items-start gap-3">
                                    <div
                                        style="width:36px;height:36px;border-radius:10px;background:rgba(255,0,137,.08);display:flex;align-items:center;justify-content:center;color:var(--wasom);flex-shrink:0">
                                        <i class="bi <?php echo $n['icon']; ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small"><?php echo $n['label']; ?></div>
                                        <div class="text-muted" style="font-size:.75rem"><?php echo $n['desc']; ?></div>
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-0 flex-shrink-0">
                                    <input class="form-check-input" type="checkbox" id="<?php echo $n['id']; ?>"
                                        <?php echo $n['val'] ? 'checked' : ''; ?> onchange="saveNotifications()" />
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-muted small mt-2" id="notif-save-hint" style="display:none">
                            <i class="bi bi-check-circle text-success me-1"></i>Preferências guardadas automaticamente.
                        </div>
                    </div>
                </div><!-- /sec-notificacoes -->


                <!-- ████ SECÇÃO 4 — SESSÕES ████ -->
                <div class="profile-section" id="sec-sessoes">
                    <div class="section-card">
                        <div class="section-title"><i class="bi bi-display"></i>Sessões Activas
                            (<?php echo count($sessions); ?>)</div>
                        <p class="text-muted small mb-3">Aqui estão todos os dispositivos com sessão activa na tua
                            conta.</p>

                        <?php if (empty($sessions)): ?>
                            <p class="text-muted small">Nenhuma sessão encontrada.</p>
                        <?php else: ?>
                            <?php foreach ($sessions as $s):
                                $ua      = $s['user_agent'] ?? '';
                                $is_cur  = (strpos($ua, $_SERVER['HTTP_USER_AGENT'] ?? '') !== false);
                                $device  = 'Computador';
                                $icon    = 'bi-laptop';
                                if (preg_match('/Mobile|Android|iPhone/i', $ua)) {
                                    $device = 'Telemóvel';
                                    $icon = 'bi-phone';
                                } elseif (preg_match('/iPad|Tablet/i', $ua)) {
                                    $device = 'Tablet';
                                    $icon = 'bi-tablet';
                                }
                                $browser = 'Desconhecido';
                                if (preg_match('/Chrome\/(\d+)/i', $ua, $m))  $browser = 'Chrome ' . $m[1];
                                elseif (preg_match('/Firefox\/(\d+)/i', $ua, $m)) $browser = 'Firefox ' . $m[1];
                                elseif (preg_match('/Safari\/(\d+)/i', $ua, $m))  $browser = 'Safari';
                                elseif (preg_match('/Edg\/(\d+)/i', $ua, $m))     $browser = 'Edge ' . $m[1];
                                $location = implode(', ', array_filter([$s['city'] ?? '', $s['country'] ?? ''])) ?: 'Localização desconhecida';
                                $last_act = $s['last_activity'] ? date('d/m/Y H:i', strtotime($s['last_activity'])) : '—';
                            ?>
                                <div class="session-item <?php echo $is_cur ? 'session-current' : ''; ?>">
                                    <div class="session-icon">
                                        <i class="bi <?php echo $icon; ?>"></i>
                                    </div>
                                    <div style="flex:1;min-width:0">
                                        <div class="fw-semibold small d-flex align-items-center gap-2">
                                            <?php echo htmlspecialchars($device); ?> · <?php echo htmlspecialchars($browser); ?>
                                            <?php if ($is_cur): ?>
                                                <span class="badge"
                                                    style="background:rgba(25,135,84,.12);color:#198754;font-size:.65rem">Sessão
                                                    actual</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted" style="font-size:.75rem">
                                            <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($location); ?>
                                            <span class="ms-2"><i class="bi bi-clock me-1"></i><?php echo $last_act; ?></span>
                                        </div>
                                        <div class="text-muted" style="font-size:.7rem">IP:
                                            <?php echo htmlspecialchars($s['ip_address'] ?? '—'); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (count($sessions) > 1): ?>
                            <div class="mt-4 pt-2 border-top">
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                    data-bs-target="#logoutAllModal">
                                    <i class="bi bi-box-arrow-right me-1"></i>Sair de todos os dispositivos
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div><!-- /sec-sessoes -->


                <!-- ████ SECÇÃO 5 — ZONA DE PERIGO ████ -->
                <div class="profile-section" id="sec-perigo">

                    <!-- Download data -->
                    <div class="section-card">
                        <div class="section-title" style="color:#198754"><i class="bi bi-download"></i>Download dos
                            Dados</div>
                        <p class="text-muted small mb-3">
                            Exporta todos os teus dados (perfil, artistas, álbuns, faixas, transacções, sessões) em
                            formato JSON.
                            Os ficheiros de áudio e imagens não são incluídos.
                        </p>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                            data-bs-target="#downloadDataModal">
                            <i class="bi bi-cloud-download me-1"></i>Exportar os Meus Dados
                        </button>
                    </div>

                    <!-- Deactivate -->
                    <div class="danger-card">
                        <h5 class="text-warning fw-bold mb-2"><i class="bi bi-pause-circle me-2"></i>Desactivar Conta
                        </h5>
                        <p class="text-muted small mb-3">
                            A tua conta ficará invisível na plataforma mas os dados são mantidos.
                            Tens <strong>29 dias</strong> para recuperar, basta iniciares sessão novamente.
                            Após 29 dias, a conta é eliminada automaticamente.
                        </p>
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#deactivateModal">
                            <i class="bi bi-pause-circle me-1"></i>Desactivar a minha conta
                        </button>
                    </div>

                    <!-- Delete permanently -->
                    <div class="danger-card" style="border-color:rgba(220,53,69,.5)">
                        <h5 class="text-danger fw-bold mb-2"><i class="bi bi-trash me-2"></i>Eliminar Conta
                            Permanentemente</h5>
                        <p class="text-muted small mb-2">
                            Esta acção é <strong>irreversível</strong>. Todos os teus dados, músicas, artistas,
                            estatísticas e histórico financeiro serão permanentemente removidos.
                        </p>
                        <p class="text-muted small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Faz primeiro o download dos teus dados antes de eliminar.
                        </p>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                            data-bs-target="#deleteAccountModal">
                            <i class="bi bi-trash me-1"></i>Eliminar conta permanentemente
                        </button>
                    </div>
                </div><!-- /sec-perigo -->

            </div><!-- /content -->
        </div><!-- /profile-layout -->
    </div><!-- /container -->

    <!-- Bottom nav mobile -->
    <nav class="bottom-nav d-lg-none">
        <ul class="nav justify-content-around">
            <li class="nav-item"><a class="nav-link" href="../painel"><i
                        class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../launch/releases"><i
                        class="bi bi-disc"></i><span>Lançamentos</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../analytics/statistics"><i
                        class="bi bi-bar-chart"></i><span>Estatísticas</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
                        class="bi bi-currency-dollar"></i><span>Finanças</span></a></li>
            <li class="nav-item"><a class="nav-link active" href="profile"><i
                        class="bi bi-person"></i><span>Perfil</span></a></li>
        </ul>
    </nav>

    <!-- ════ MODAL — Editar Perfil ════ -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#FF0089,#FF4D4D);color:#fff">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square fs-4"></i>
                        <h5 class="modal-title mb-0">Editar Perfil</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Photo -->
                    <div class="text-center mb-4">
                        <div style="position:relative;display:inline-block">
                            <?php if ($photo_url): ?>
                                <img id="edit-avatar-preview" src="<?php echo htmlspecialchars($photo_url); ?>"
                                    class="avatar-ring" style="width:90px;height:90px" alt="" />
                            <?php else: ?>
                                <div id="edit-avatar-ph" class="avatar-ring-ph"
                                    style="width:90px;height:90px;font-size:2rem"
                                    onclick="document.getElementById('edit-photo-input').click()"><i
                                        class="bi bi-person"></i></div>
                                <img id="edit-avatar-preview" src="" class="avatar-ring d-none"
                                    style="width:90px;height:90px" alt="" />
                            <?php endif; ?>
                            <div class="avatar-upload-btn"
                                onclick="document.getElementById('edit-photo-input').click()">
                                <i class="bi bi-camera-fill" style="font-size:.65rem"></i>
                            </div>
                        </div>
                        <input type="file" id="edit-photo-input" accept="image/jpeg,image/png,image/webp"
                            class="d-none" />
                        <div class="text-muted mt-1" style="font-size:.72rem">JPG/PNG/WebP · Máx. 5MB</div>
                    </div>

                    <form id="edit-profile-form">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Primeiro Nome <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" maxlength="50"
                                    value="<?php echo htmlspecialchars($user['first_name']); ?>" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Segundo Nome</label>
                                <input type="text" class="form-control" name="second_name" maxlength="80"
                                    value="<?php echo htmlspecialchars($user['second_name'] ?? ''); ?>" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Nome de Utilizador <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">@</span>
                                    <input type="text" class="form-control" name="user_name" id="edit-username"
                                        maxlength="40" value="<?php echo htmlspecialchars($user['user_name'] ?? ''); ?>"
                                        oninput="checkUsername(this.value)" required />
                                    <span class="input-group-text" id="username-icon"><i
                                            class="bi bi-dash text-muted"></i></span>
                                </div>
                                <div id="username-feedback" class="form-text"></div>
                                <div id="username-suggestions" class="mt-1 d-flex gap-1 flex-wrap"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Nome Artístico / Banda</label>
                                <input type="text" class="form-control" name="name_artist_band" maxlength="100"
                                    value="<?php echo htmlspecialchars($user['name_artist_band'] ?? ''); ?>"
                                    placeholder="Como aparece nas plataformas" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Telefone</label>
                                <input type="tel" class="form-control" name="tel_user" maxlength="20"
                                    value="<?php echo htmlspecialchars($user['tel_user'] ?? ''); ?>"
                                    placeholder="+244 9XX XXX XXX" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">País</label>
                                <input type="text" class="form-control" name="country_user" maxlength="60"
                                    value="<?php echo htmlspecialchars($user['country_user'] ?? ''); ?>"
                                    placeholder="ex: Angola" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Cidade</label>
                                <input type="text" class="form-control" name="city_user" maxlength="60"
                                    value="<?php echo htmlspecialchars($user['city_user'] ?? ''); ?>"
                                    placeholder="ex: Luanda" />
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Bio</label>
                                <textarea class="form-control" name="about_user" rows="3" maxlength="500"
                                    placeholder="Fala um pouco sobre ti..."><?php echo htmlspecialchars($user['about_user'] ?? ''); ?></textarea>
                                <div class="form-text text-end" id="bio-count">0 / 500</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Website / Redes</label>
                                <input type="url" class="form-control" name="url_user" maxlength="255"
                                    value="<?php echo htmlspecialchars($user['url_user'] ?? ''); ?>"
                                    placeholder="https://..." />
                            </div>
                        </div>
                    </form>
                    <div id="edit-profile-feedback" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm px-4" style="background:var(--wasom);color:#fff"
                        id="btn-save-profile" onclick="saveProfile()">
                        <span id="save-profile-text"><i class="bi bi-check me-1"></i>Guardar</span>
                        <span id="save-profile-load" class="d-none"><span
                                class="spinner-border spinner-border-sm me-1"></span>A guardar...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════ MODAL — Sair de todos dispositivos ════ -->
    <div class="modal fade" id="logoutAllModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-right me-2 text-danger"></i>Sair de todos os
                        dispositivos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    <p class="text-muted small mb-3">Todas as outras sessões activas serão encerradas. A tua sessão
                        actual permanece activa.</p>
                    <label class="form-label fw-semibold small">A tua senha <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="logout-all-pwd"
                            placeholder="Confirma com a tua senha" />
                        <button class="btn btn-outline-secondary" onclick="togglePwd('logout-all-pwd',this)"><i
                                class="bi bi-eye"></i></button>
                    </div>
                    <div id="logout-all-feedback" class="mt-2 d-none"></div>
                </div>
                <div class="modal-footer border-0 gap-2">
                    <button class="btn btn-outline-secondary btn-sm flex-fill" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-danger py-2 btn-sm flex-fill" onclick="logoutAllDevices()">
                        <span id="logout-all-text"><i class="bi bi-box-arrow-right me-1"></i>Confirmar</span>
                        <span id="logout-all-load" class="d-none"><span
                                class="spinner-border spinner-border-sm"></span></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════ MODAL — Download Dados ════ -->
    <div class="modal fade" id="downloadDataModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="bi bi-cloud-download me-2" style="color:var(--wasom)"></i>Exportar
                        Dados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    <p class="text-muted small mb-3">
                        Exporta todos os teus dados em formato <strong>JSON</strong>.
                        O ficheiro incluirá: perfil, artistas, álbuns, faixas, transacções, levantamentos, pagamentos e
                        sessões.
                    </p>
                    <div class="alert alert-info small d-flex gap-2">
                        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                        <div>Os ficheiros de áudio e imagens não são exportados por razões de tamanho. Apenas os
                            metadados são incluídos.</div>
                    </div>
                    <label class="form-label fw-semibold small">A tua senha <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="download-data-pwd"
                            placeholder="Confirma com a tua senha" />
                        <button class="btn btn-outline-secondary" onclick="togglePwd('download-data-pwd',this)"><i
                                class="bi bi-eye"></i></button>
                    </div>
                    <div id="download-data-feedback" class="mt-2 d-none"></div>
                </div>
                <div class="modal-footer border-0 gap-2">
                    <button class="btn btn-outline-secondary btn-sm flex-fill" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-sm py-2 flex-fill" style="background:var(--wasom);color:#fff"
                        onclick="downloadData()">
                        <span id="dl-data-text"><i class="bi bi-download me-1"></i>Exportar</span>
                        <span id="dl-data-load" class="d-none"><span
                                class="spinner-border spinner-border-sm"></span></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════ MODAL — Desactivar Conta ════ -->
    <div class="modal fade" id="deactivateModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="bi bi-pause-circle me-2 text-warning"></i>Desactivar Conta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="alert alert-warning small d-flex gap-2">
                        <i class="bi bi-clock-history flex-shrink-0 mt-1"></i>
                        <div>A tua conta será desactivada mas os dados mantidos por <strong>29 dias</strong>. Para
                            recuperar, basta iniciares sessão novamente. Após 29 dias, a conta é eliminada
                            automaticamente.</div>
                    </div>
                    <label class="form-label fw-semibold small">A tua senha <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="deactivate-pwd"
                            placeholder="Confirma com a tua senha" />
                        <button class="btn btn-outline-secondary" onclick="togglePwd('deactivate-pwd',this)"><i
                                class="bi bi-eye"></i></button>
                    </div>
                    <div id="deactivate-feedback" class="mt-2 d-none"></div>
                </div>
                <div class="modal-footer border-0 gap-2">
                    <button class="btn btn-outline-secondary btn-sm flex-fill" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-warning btn-sm py-2 flex-fill fw-bold" onclick="deactivateAccount()">
                        <span id="deact-text"><i class="bi bi-pause-circle me-1"></i>Desactivar</span>
                        <span id="deact-load" class="d-none"><span
                                class="spinner-border spinner-border-sm"></span></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════ MODAL — Eliminar Conta ════ -->
    <div class="modal fade" id="deleteAccountModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Eliminar Conta Permanentemente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="alert alert-danger small d-flex gap-2">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                        <div>Esta acção é <strong>irreversível</strong>. Todos os dados serão eliminados
                            permanentemente. Não há recuperação possível.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Para confirmar, copia e cola o texto abaixo:</label>
                        <div class="p-2 rounded mb-2"
                            style="background:rgba(220,53,69,.08);border:1px dashed rgba(220,53,69,.3);font-family:monospace;font-size:.85rem;user-select:all">
                            eliminar a minha conta permanentemente
                        </div>
                        <input type="text" class="form-control form-control-sm" id="delete-confirm-text"
                            placeholder="Cola o texto aqui..." oninput="checkDeleteText()" />
                        <div id="delete-text-check" class="mt-1" style="font-size:.75rem"></div>
                    </div>
                    <label class="form-label fw-semibold small">A tua senha <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="delete-pwd"
                            placeholder="Confirma com a tua senha" />
                        <button class="btn btn-outline-secondary" onclick="togglePwd('delete-pwd',this)"><i
                                class="bi bi-eye"></i></button>
                    </div>
                    <div id="delete-feedback" class="mt-2 d-none"></div>
                </div>
                <div class="modal-footer border-0 gap-2">
                    <button class="btn btn-outline-secondary btn-sm flex-fill" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-danger btn-sm py-2 flex-fill" id="btn-confirm-delete" disabled
                        onclick="deleteAccount()">
                        <span id="del-text"><i class="bi bi-trash me-1"></i>Eliminar para sempre</span>
                        <span id="del-load" class="d-none"><span class="spinner-border spinner-border-sm"></span></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="../../js/theme.wp.js"></script>
    <script src="../../js/wp.tools.js"></script>
    <script>
        const CSRF = '<?php echo $csrf; ?>';
        const PROCESS = '<?php echo rtrim(APP_URL, "/"); ?>/dashboard/user/profile_process';

        toastr.options = {
            progressBar: true,
            closeButton: true,
            positionClass: 'toast-top-right',
            timeOut: 4000
        };

        // ── Section nav ────────────────────────────
        function showSection(id) {
            document.querySelectorAll('.profile-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.mobile-tab').forEach(b => b.classList.remove('btn-primary'));

            const sec = document.getElementById('sec-' + id);
            if (sec) sec.classList.add('active');

            const navLink = document.querySelector(`.sidebar-nav [data-section="${id}"]`);
            if (navLink) navLink.classList.add('active');

            const mobileBtn = document.querySelector(`.mobile-tab[data-section="${id}"]`);
            if (mobileBtn) mobileBtn.classList.add('btn-primary');

            history.replaceState(null, '', '#' + id);
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Init nav from URL hash
        const hash = location.hash.replace('#', '');
        if (['perfil', 'seguranca', 'notificacoes', 'sessoes', 'perigo'].includes(hash)) showSection(hash);

        document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => {
            l.addEventListener('click', e => {
                e.preventDefault();
                showSection(l.dataset.section);
            });
        });
        document.querySelectorAll('.mobile-tab').forEach(b => {
            b.addEventListener('click', () => showSection(b.dataset.section));
        });

        // ── Shared helpers ──────────────────────
        function togglePwd(id, btn) {
            const inp = document.getElementById(id);
            const show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        }

        async function postJSON(payload) {
            const fd = new FormData();
            fd.append('csrf_token', CSRF);
            for (const [k, v] of Object.entries(payload)) fd.append(k, v);
            const r = await fetch(PROCESS, {
                method: 'POST',
                body: fd
            });
            return r.json();
        }

        function setLoad(textId, loadId, btnEl, loading) {
            document.getElementById(textId)?.classList.toggle('d-none', loading);
            document.getElementById(loadId)?.classList.toggle('d-none', !loading);
            if (btnEl) btnEl.disabled = loading;
        }

        function showFeedback(id, ok, msg) {
            const el = document.getElementById(id);
            if (!el) return;
            el.innerHTML =
                `<div class="alert alert-${ok?'success':'danger'} small py-2 d-flex gap-2"><i class="bi bi-${ok?'check-circle':'exclamation-circle'}-fill flex-shrink-0"></i><div>${msg}</div></div>`;
            el.classList.remove('d-none');
        }

        // ── Verify email ─────────────────────────
        async function resendVerifyEmail() {
            const r = await postJSON({
                action: 'resend_verify_email'
            });
            if (r.ok) {
                new bootstrap.Modal(document.createElement('div'));
                await Swal.fire({
                    icon: 'success',
                    iconColor: '#FF0089',
                    title: 'Email enviado!',
                    text: r.message,
                    confirmButtonColor: '#FF0089',
                    timer: 4000,
                    timerProgressBar: true
                });
            } else toastr.error(r.message);
        }

        // ── Photo preview (edit modal) ────────────
        document.getElementById('edit-photo-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                toastr.error('Imagem demasiado grande (máx. 5MB).');
                return;
            }
            const reader = new FileReader();
            reader.onload = ev => {
                const ph = document.getElementById('edit-avatar-ph');
                if (ph) ph.classList.add('d-none');
                const img = document.getElementById('edit-avatar-preview');
                img.src = ev.target.result;
                img.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });

        // Bio counter
        const bioTA = document.querySelector('#edit-profile-form [name="about_user"]');
        const bioCount = document.getElementById('bio-count');
        if (bioTA && bioCount) {
            bioCount.textContent = bioTA.value.length + ' / 500';
            bioTA.addEventListener('input', () => bioCount.textContent = bioTA.value.length + ' / 500');
        }

        // ── Username check ────────────────────────
        let usernameTimer;

        function checkUsername(val) {
            clearTimeout(usernameTimer);
            val = val.toLowerCase().replace(/[^a-z0-9_.]/g, '');
            document.getElementById('edit-username').value = val;
            if (val.length < 3) {
                document.getElementById('username-feedback').textContent = '';
                document.getElementById('username-icon').innerHTML = '<i class="bi bi-dash text-muted"></i>';
                return;
            }
            document.getElementById('username-icon').innerHTML =
                '<span class="spinner-border spinner-border-sm text-muted"></span>';
            usernameTimer = setTimeout(async () => {
                const r = await postJSON({
                    action: 'check_username',
                    username: val
                });
                const icon = document.getElementById('username-icon');
                const fb = document.getElementById('username-feedback');
                const sug = document.getElementById('username-suggestions');
                if (r.available) {
                    icon.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
                    fb.innerHTML = `<span class="text-success small">${r.message}</span>`;
                    sug.innerHTML = '';
                } else {
                    icon.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
                    fb.innerHTML = `<span class="text-danger small">${r.message}</span>`;
                    if (r.suggestions?.length) {
                        sug.innerHTML = '<small class="text-muted me-1">Sugestões:</small>' +
                            r.suggestions.map(s =>
                                `<button type="button" class="btn btn-outline-secondary btn-sm py-0" style="font-size:.75rem" onclick="document.getElementById('edit-username').value='${s}';checkUsername('${s}')">${s}</button>`
                            ).join('');
                    }
                }
            }, 600);
        }

        // ── Save profile ──────────────────────────
        async function saveProfile() {
            const btn = document.getElementById('btn-save-profile');
            setLoad('save-profile-text', 'save-profile-load', btn, true);

            const fd = new FormData(document.getElementById('edit-profile-form'));
            fd.append('action', 'update_profile');
            fd.append('csrf_token', CSRF);
            const photo = document.getElementById('edit-photo-input').files[0];
            if (photo) fd.set('photo_user', photo);

            try {
                const r = await fetch(PROCESS, {
                    method: 'POST',
                    body: fd
                });
                const data = await r.json();
                if (data.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('editProfileModal')).hide();
                    // Update UI
                    if (data.photo_url) {
                        document.querySelectorAll('#sidebar-avatar,#hero-avatar,#edit-avatar-preview').forEach(el => {
                            el.src = data.photo_url;
                            el.classList.remove('d-none');
                        });
                        document.querySelectorAll('#sidebar-avatar-ph,#hero-avatar-ph,#edit-avatar-ph').forEach(el => el
                            ?.classList.add('d-none'));
                    }
                    toastr.success(data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showFeedback('edit-profile-feedback', false, data.message);
                }
            } catch {
                toastr.error('Erro de ligação.');
            } finally {
                setLoad('save-profile-text', 'save-profile-load', btn, false);
            }
        }

        // ── Password strength ─────────────────────
        function checkStrength(pwd) {
            const bar = document.getElementById('strength-bar');
            const label = document.getElementById('strength-label');
            let score = 0;
            if (pwd.length >= 8) score++;
            if (pwd.length >= 12) score++;
            if (/[A-Z]/.test(pwd)) score++;
            if (/[0-9]/.test(pwd)) score++;
            if (/[^A-Za-z0-9]/.test(pwd)) score++;
            const map = [
                [20, '#dc3545', 'Muito fraca'],
                [40, '#fd7e14', 'Fraca'],
                [60, '#ffc107', 'Razoável'],
                [80, '#20c997', 'Boa'],
                [100, '#198754', 'Excelente']
            ];
            const [w, c, t] = map[score - 1] || [10, '#dc3545', 'Muito fraca'];
            bar.style.width = w + '%';
            bar.style.background = c;
            label.textContent = t;
            label.style.color = c;
        }

        // ── Change password ───────────────────────
        async function changePassword() {
            const old = document.getElementById('old-password').value;
            const nw = document.getElementById('new-password').value;
            const conf = document.getElementById('confirm-password').value;
            if (!old || !nw || !conf) {
                toastr.error('Preenche todos os campos.');
                return;
            }
            if (nw !== conf) {
                toastr.error('As senhas não coincidem.');
                return;
            }
            if (nw.length < 8) {
                toastr.error('A senha deve ter pelo menos 8 caracteres.');
                return;
            }

            const r = await postJSON({
                action: 'change_password',
                old_password: old,
                new_password: nw,
                confirm_password: conf
            });
            if (r.ok) {
                toastr.success(r.message);
                ['old-password', 'new-password', 'confirm-password'].forEach(id => document.getElementById(id).value =
                    '');
                document.getElementById('strength-bar').style.width = '0';
                document.getElementById('strength-label').textContent = '';
                // Unlock recovery key
                document.getElementById('btn-gen-recovery')?.removeAttribute('disabled');
                Swal.fire({
                    icon: 'success',
                    iconColor: '#FF0089',
                    title: 'Senha alterada!',
                    text: r.message,
                    confirmButtonColor: '#FF0089',
                    timer: 3000,
                    timerProgressBar: true
                });
            } else toastr.error(r.message);
        }

        // ── 2FA ───────────────────────────────────
        let totpSecretGlobal = '';
        async function toggle2FA(checkbox) {
            if (checkbox.checked) {
                // Activar — gerar QR
                const r = await postJSON({
                    action: 'toggle_2fa',
                    enable: 1
                });
                if (r.ok) {
                    totpSecretGlobal = r.secret;
                    document.getElementById('2fa-qr').src = r.qr_url;
                    document.getElementById('2fa-secret-display').textContent = r.secret;
                    document.getElementById('2fa-setup').classList.remove('d-none');
                    document.getElementById('2fa-disable').classList.add('d-none');
                } else {
                    toastr.error(r.message);
                    checkbox.checked = false;
                }
            } else {
                // Desactivar — re-marcar imediatamente (só desmarca após senha confirmada)
                checkbox.checked = true;
                document.getElementById('2fa-disable').classList.remove('d-none');
                document.getElementById('2fa-setup').classList.add('d-none');
                document.getElementById('2fa-disable-pwd').value = '';
                document.getElementById('2fa-disable-pwd').focus();
            }
        }

        async function confirm2FA() {
            const code = document.getElementById('totp-code').value.trim();
            if (code.length !== 6) {
                toastr.error('Insere os 6 dígitos.');
                return;
            }
            const r = await postJSON({
                action: 'confirm_2fa',
                totp_code: code,
                totp_secret: totpSecretGlobal
            });
            if (r.ok) {
                document.getElementById('2fa-setup').classList.add('d-none');
                await Swal.fire({
                    icon: 'success',
                    iconColor: '#FF0089',
                    title: '2FA Activado!',
                    text: r.message,
                    confirmButtonColor: '#FF0089',
                    timer: 3000,
                    timerProgressBar: true
                });
                location.reload();
            } else toastr.error(r.message);
        }

        async function disable2FA() {
            const pwd = document.getElementById('2fa-disable-pwd').value;
            if (!pwd) {
                toastr.error('Introduz a tua senha.');
                return;
            }
            const r = await postJSON({
                action: 'toggle_2fa',
                enable: 0,
                password_confirm: pwd
            });
            if (r.ok) {
                await Swal.fire({
                    icon: 'success',
                    iconColor: '#198754',
                    title: '2FA Desactivado',
                    text: r.message,
                    confirmButtonColor: '#198754',
                    timer: 2500,
                    timerProgressBar: true
                });
                location.reload();
            } else {
                toastr.error(r.message);
            }
        }

        // ── Recovery key ──────────────────────────
        async function generateRecovery() {
            const r = await postJSON({
                action: 'generate_recovery_key'
            });
            if (r.ok) {
                const segments = r.key.split('-');
                const display = segments.map(s => `<span class="recovery-segment">${s}</span>`).join(' - ');
                document.getElementById('recovery-key-text').innerHTML = display;
                document.getElementById('recovery-key-display').classList.remove('d-none');
                document.getElementById('btn-download-recovery').classList.remove('d-none');
                document.getElementById('btn-copy-recovery').classList.remove('d-none');
                document.getElementById('btn-gen-recovery').setAttribute('disabled', '');
                Swal.fire({
                    icon: 'warning',
                    iconColor: '#FF0089',
                    title: 'Guarda a tua chave!',
                    html: '<p>Esta chave é mostrada <strong>uma única vez</strong>. Copia ou faz download agora e guarda num local seguro offline.</p>',
                    confirmButtonColor: '#FF0089',
                    confirmButtonText: 'Entendido, guardei'
                });
            } else toastr.error(r.message);
        }

        async function downloadRecovery() {
            const r = await postJSON({
                action: 'download_recovery_key'
            });
            if (r.ok) {
                const filename_txt = (r.filename || 'wasom_recovery.txt').replace(/\.json$/, '') + (r.filename
                    ?.endsWith('.txt') ? '' : '.txt');
                const blob = new Blob([
                    '============================\n' +
                    'WASOM UPFY — Chave de Recuperação\n' +
                    'Gerada em: ' + new Date().toLocaleDateString('pt-PT') + '\n' +
                    '============================\n\n' +
                    r.key + '\n\n' +
                    'ATENÇÃO: Guarda esta chave offline num local seguro.\n' +
                    'Não a partilhes com ninguém.\n'
                ], {
                    type: 'text/plain'
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = r.filename || filename_txt;
                a.click();
                URL.revokeObjectURL(url);
                toastr.success('Chave descarregada!');
            } else toastr.error(r.message);
        }

        function copyRecovery() {
            const txt = document.getElementById('recovery-key-text').textContent.replace(/\s+/g, ' ').trim();
            navigator.clipboard.writeText(txt).then(() => toastr.success('Chave copiada!'));
        }

        // ── Notifications (auto-save) ─────────────
        let notifTimer;

        function saveNotifications() {
            clearTimeout(notifTimer);
            notifTimer = setTimeout(async () => {
                const payload = {
                    action: 'update_notifications'
                };
                ['notif_email', 'notif_push', 'notif_weekly', 'notif_releases', 'notif_payments'].forEach(
                    id => {
                        payload[id] = document.getElementById(id)?.checked ? 1 : 0;
                    });
                const r = await postJSON(payload);
                const hint = document.getElementById('notif-save-hint');
                if (r.ok) {
                    if (hint) hint.style.display = 'block';
                    setTimeout(() => hint && (hint.style.display = 'none'), 3000);
                } else toastr.error(r.message);
            }, 800);
        }

        // ── Logout all devices ────────────────────
        async function logoutAllDevices() {
            const pwd = document.getElementById('logout-all-pwd').value;
            if (!pwd) {
                showFeedback('logout-all-feedback', false, 'Introduz a tua senha.');
                return;
            }
            setLoad('logout-all-text', 'logout-all-load', null, true);
            const r = await postJSON({
                action: 'logout_all_sessions',
                password_confirm: pwd
            });
            setLoad('logout-all-text', 'logout-all-load', null, false);
            if (r.ok) {
                bootstrap.Modal.getInstance(document.getElementById('logoutAllModal')).hide();
                await Swal.fire({
                    icon: 'success',
                    iconColor: '#FF0089',
                    title: 'Sessões encerradas!',
                    text: r.message,
                    confirmButtonColor: '#FF0089',
                    timer: 2500
                });
                location.reload();
            } else showFeedback('logout-all-feedback', false, r.message);
        }

        // ── Download data ─────────────────────────
        async function downloadData() {
            const pwd = document.getElementById('download-data-pwd').value;
            if (!pwd) {
                showFeedback('download-data-feedback', false, 'Introduz a tua senha.');
                return;
            }
            setLoad('dl-data-text', 'dl-data-load', null, true);
            const r = await postJSON({
                action: 'download_data',
                password_confirm: pwd
            });
            setLoad('dl-data-text', 'dl-data-load', null, false);
            if (r.ok) {
                const blob = new Blob([r.data], {
                    type: 'application/json'
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = r.filename;
                a.click();
                URL.revokeObjectURL(url);
                bootstrap.Modal.getInstance(document.getElementById('downloadDataModal')).hide();
                toastr.success('Download iniciado!');
            } else showFeedback('download-data-feedback', false, r.message);
        }

        // ── Deactivate account ────────────────────
        async function deactivateAccount() {
            const pwd = document.getElementById('deactivate-pwd').value;
            if (!pwd) {
                showFeedback('deactivate-feedback', false, 'Introduz a tua senha.');
                return;
            }
            setLoad('deact-text', 'deact-load', null, true);
            const r = await postJSON({
                action: 'deactivate_account',
                password_confirm: pwd
            });
            setLoad('deact-text', 'deact-load', null, false);
            if (r.ok) {
                await Swal.fire({
                    icon: 'info',
                    title: 'Conta desactivada',
                    text: r.message,
                    confirmButtonColor: '#FF0089'
                });
                window.location.href = r.redirect || '/';
            } else showFeedback('deactivate-feedback', false, r.message);
        }

        // ── Delete confirm text ───────────────────
        function checkDeleteText() {
            const val = document.getElementById('delete-confirm-text').value.trim().toLowerCase();
            const expected = 'eliminar a minha conta permanentemente';
            const check = document.getElementById('delete-text-check');
            const btn = document.getElementById('btn-confirm-delete');
            if (val === expected) {
                check.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Texto correcto</span>';
                btn.disabled = false;
            } else {
                check.innerHTML = val.length > 0 ?
                    '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Texto incorrecto</span>' : '';
                btn.disabled = true;
            }
        }

        // ── Delete account ────────────────────────
        async function deleteAccount() {
            const pwd = document.getElementById('delete-pwd').value;
            const text = document.getElementById('delete-confirm-text').value.trim();
            if (!pwd) {
                showFeedback('delete-feedback', false, 'Introduz a tua senha.');
                return;
            }
            setLoad('del-text', 'del-load', null, true);
            const r = await postJSON({
                action: 'delete_account',
                password_confirm: pwd,
                confirm_text: text
            });
            setLoad('del-text', 'del-load', null, false);
            if (r.ok) {
                await Swal.fire({
                    icon: 'info',
                    title: 'Conta eliminada',
                    text: 'A tua conta foi eliminada.',
                    confirmButtonColor: '#FF0089'
                });
                window.location.href = r.redirect || '/';
            } else showFeedback('delete-feedback', false, r.message);
        }

        // ── Verify success (URL param) ────────────
        <?php if ($verify_status === 'success'): ?>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'success',
                    iconColor: '#FF0089',
                    title: 'Email verificado!',
                    text: 'O teu email foi verificado com sucesso.',
                    confirmButtonColor: '#FF0089',
                    timer: 4000,
                    timerProgressBar: true
                });
            });
        <?php elseif ($verify_status === 'error'): ?>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro na verificação',
                    text: 'O link é inválido ou expirou. Solicita um novo.',
                    confirmButtonColor: '#FF0089'
                });
            });
        <?php endif; ?>
    </script>
</body>

</html>