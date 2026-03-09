<?php
// WASOM UPFY v2.0 - Painel Principal
// Arquivo: dashboard/painel.php
require_once __DIR__ . '/../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$user = getUserById((int)$_SESSION['id_users']);
if (!$user) {
    session_destroy();
    redirect('/login', ['error' => 'csrf']);
}

$first_name     = htmlspecialchars($user['first_name']);
$user_name      = htmlspecialchars($user['user_name'] ?? '');
$email_verified = (bool)$user['email_verified'];
$plan_selected  = $user['plan_selected'];
$onboard_done   = (bool)($user['onboarding_done'] ?? false);
$id_users       = (int)$user['id_users'];

// Saldo
$w = getDB()->prepare('SELECT balance_aoa, balance_usd FROM _wallet WHERE id_users = ?');
$w->execute([$id_users]);
$balance = $w->fetch() ?: ['balance_aoa' => 0, 'balance_usd' => 0];

// Plano
$plan = null;
$plan_paid = false; // true = plano activo e pago
if ($plan_selected) {
    $ps = getDB()->prepare('SELECT * FROM _plans WHERE id_plan = ?');
    $ps->execute([$plan_selected]);
    $plan = $ps->fetch();
}
// Plano considerado pago se status_user = 'active' E plan_activated_at preenchido
$plan_paid = ($user['status_user'] === 'active' && !empty($user['plan_activated_at']));

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

// Conta bancaria para saque (tabela _account existente)
$bank_stmt = getDB()->prepare("
    SELECT * FROM _account
    WHERE id_users = ? AND status_account = 'verified' AND is_default = 1
    LIMIT 1
");
$bank_stmt->execute([$id_users]);
$bank_account = $bank_stmt->fetch() ?: null;

// Saldo em AOA (float)
$balance_aoa = (float)($balance['balance_aoa'] ?? 0);
$min_withdrawal = 10000.00; // Minimo de saque: 10.000 Kz
$can_withdraw = $plan_paid && $bank_account && ($balance_aoa >= $min_withdrawal);

// Streams por plataforma (tabelas reais: _stream + _store + _track)
$streams_stmt = getDB()->prepare("
    SELECT
        st.id_store,
        st.name_store,
        st.slug_store,
        COALESCE(SUM(s.streams), 0) as total
    FROM _store st
    LEFT JOIN _stream s ON s.id_store = st.id_store
    LEFT JOIN _track  t ON t.id_track  = s.id_track AND t.id_users = ?
    WHERE st.is_active = 1 AND st.type_store = 'streaming'
    GROUP BY st.id_store, st.name_store, st.slug_store
    HAVING total > 0
    ORDER BY total DESC
    LIMIT 6
");
$streams_stmt->execute([$id_users]);
$streams = $streams_stmt->fetchAll();
$has_streams = !empty($streams);

// Streams por mês para o gráfico (últimos 6 meses, por plataforma com dados)
$chart_stmt = getDB()->prepare("
    SELECT
        st.name_store,
        st.slug_store,
        s.year_stream,
        s.month_stream,
        SUM(s.streams) as total
    FROM _stream s
    JOIN _track  t  ON t.id_track  = s.id_track AND t.id_users = ?
    JOIN _store  st ON st.id_store = s.id_store AND st.type_store = 'streaming'
    WHERE (s.year_stream * 100 + s.month_stream) >= (YEAR(NOW()) * 100 + MONTH(NOW()) - 6)
    GROUP BY st.id_store, st.name_store, st.slug_store, s.year_stream, s.month_stream
    ORDER BY s.year_stream, s.month_stream
");
$chart_stmt->execute([$id_users]);
$chart_rows = $chart_stmt->fetchAll();

// Total de lancamentos
$rel_stmt = getDB()->prepare("SELECT COUNT(*) as total FROM _album WHERE id_users = ?");
$rel_stmt->execute([$id_users]);
$total_releases = (int)($rel_stmt->fetch()['total'] ?? 0);

// Preparar dados do gráfico para o JS
$platform_colors = [
    'spotify'       => ['border' => '#1db954', 'bg' => 'rgba(29,185,84,0.45)'],
    'apple-music'   => ['border' => '#fa586a', 'bg' => 'rgba(250,88,106,0.45)'],
    'deezer'        => ['border' => '#ff0089', 'bg' => 'rgba(255,0,137,0.45)'],
    'amazon-music'  => ['border' => '#00a8e0', 'bg' => 'rgba(0,168,224,0.45)'],
    'youtube-music' => ['border' => '#ff0000', 'bg' => 'rgba(255,0,0,0.45)'],
    'tidal'         => ['border' => '#00ffff', 'bg' => 'rgba(0,255,255,0.35)'],
    'boomplay'      => ['border' => '#ff6600', 'bg' => 'rgba(255,102,0,0.45)'],
    'soundcloud'    => ['border' => '#ff5500', 'bg' => 'rgba(255,85,0,0.45)'],
];

$chart_labels   = [];
$chart_datasets = [];

if ($has_streams && !empty($chart_rows)) {
    // Construir labels de meses únicos ordenados
    $months_seen = [];
    foreach ($chart_rows as $r) {
        $key = $r['year_stream'] . '-' . str_pad($r['month_stream'], 2, '0', STR_PAD_LEFT);
        if (!isset($months_seen[$key])) {
            $months_seen[$key] = date('M Y', mktime(0, 0, 0, $r['month_stream'], 1, $r['year_stream']));
        }
    }
    ksort($months_seen);
    $chart_labels = array_values($months_seen);
    $month_keys   = array_keys($months_seen);

    // Agrupar por plataforma
    $by_platform = [];
    foreach ($chart_rows as $r) {
        $slug = $r['slug_store'];
        $key  = $r['year_stream'] . '-' . str_pad($r['month_stream'], 2, '0', STR_PAD_LEFT);
        $by_platform[$slug]['name'] = $r['name_store'];
        $by_platform[$slug]['data'][$key] = (int)$r['total'];
    }

    foreach ($by_platform as $slug => $info) {
        $color = $platform_colors[$slug] ?? ['border' => '#aaaaaa', 'bg' => 'rgba(170,170,170,0.4)'];
        $data  = [];
        foreach ($month_keys as $mk) {
            $data[] = $info['data'][$mk] ?? 0;
        }
        $chart_datasets[] = [
            'label'           => $info['name'],
            'data'            => $data,
            'borderColor'     => $color['border'],
            'backgroundColor' => $color['bg'],
            'fill'            => true,
            'stack'           => 'combined',
            'tension'         => 0.4,
        ];
    }
}

$chart_json_labels   = json_encode($chart_labels);
$chart_json_datasets = json_encode($chart_datasets);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="#FF0089" />
    <link rel="apple-touch-icon" href="../assets/img/icones/wasomupfy_fiv_512.png" />
    <link rel="apple-touch-startup-image" href="../assets/img/screenshots/splash.png" />
    <link rel="manifest" href="manifest.json" />
    <title>Dashboard — Wasom Upfy</title>
    <link rel="shortcut icon" href="../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="../css/dashboard-style.css" />
    <link rel="stylesheet" href="../css/lastest-style.css" />
    <!-- <style>
        @font-face {
            font-family: 'FonteLogo';
            src: url("../css/fonts/bubblegum-sans-regular.otf.ttf");
        }
        .brand_wp {
            font-weight: bold;
            box-sizing: border-box;
            text-transform: capitalize;
            font-family: 'FonteLogo', sans-serif;
        }
    </style> -->

    <style>
    /* ─── Onboarding ─────────────────────── */
    .ob-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #dee2e6;
        display: inline-block;
        transition: background .3s;
    }

    .ob-dot.active {
        background: #FF0089;
        transform: scale(1.3);
    }

    /* ─── Verification badge ─────────────── */
    .verification-badge {
        background: linear-gradient(135deg, #FF0089, #FF4D4D);
        color: white;
        padding: .25rem 1rem;
        border-radius: 20px;
        font-size: .875rem;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
    }

    .unverified-badge {
        background: #fff3cd;
        color: #856404;
        padding: .25rem 1rem;
        border-radius: 20px;
        font-size: .875rem;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        border: 1px solid #ffc107;
    }
    </style>
</head>

<body>
    <?php include __DIR__ . '/finances/_modal_withdrawal.php'; ?>
    <!-- Tela de Carregamento -->
    <!-- <div class="loading-screen" id="loadingScreen">
        <img src="../assets/img/brand/wasomupfy_loaading.png" class="img-fluid loading-logo" width="90" height="90"
            alt="Loading-wasomupfy">
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
            <a class="navbar-brand" href="painel">
                <!-- SVG Logo Wasom Upfy -->
                <!-- <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="120" height="32" rx="5" fill="none" stroke="#ff0089" stroke-width="2" />
                    <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold"
                        fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
                </svg> -->
                <!-- <img src="../assets/img/brand/wasomupfy_brand.png" width="70"  class="img-fluid" alt=""> -->
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
                        <a class="nav-link" href="painel"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="launch/releases"><i class="bi bi-disc"></i>
                            Lançamentos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="analytics/statistics"><i class="bi bi-bar-chart"></i> Estatísticas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="finances/overview"><i class="bi bi-currency-dollar"></i> Finanças</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="artists/artists-list"><i class="bi bi-person"></i> Artistas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="artists/youtube/ucy"><i class="bi bi-youtube"></i> Unificação de canal
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
                        <a class="dropdown-item" href="user/profile"><i class="bi bi-person me-2"></i>
                            <strong><?php echo $first_name; ?></strong></a>
                        <div class="text-white-50">
                            &nbsp; &nbsp; &nbsp; &nbsp; (Conta <?php echo str_pad($id_users, 6, "0", STR_PAD_LEFT); ?>)
                        </div>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <a class="dropdown-item" href="user/profile"><i class="bi bi-person me-2"></i> Meu Perfil</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="account/manage-account"><i class="bi bi-tools me-2"></i> Gestão
                            de
                            Conta</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <a class="dropdown-item" href="page/settings"><i class="bi bi-gear me-2"></i> Configurações</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="page/notifications"><i class="bi bi-bell me-2"></i>
                            Notificações</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="services/available-services"><i class="bi bi-star me-2"></i>
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
                        <a class="dropdown-item" href="page/about"><i class="bi bi-info-circle me-2"></i> Sobre</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="page/support"><i class="bi bi-headset me-2"></i> Enviar pedido de
                            suporte</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="page/faq"><i class="bi bi-chat-left-text me-2"></i> Perguntas
                            frequentes</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="page/help"><i class="bi bi-question-circle me-2"></i> Ajuda</a>
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

    <!-- Offcanvas Menu par Mobile e Desktop -->
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
            ">WASOM UPFY
                </span>
            </h5>
            <button type="button" class="btn-close text-white" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="painel"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/dashboard/releases"><i class="bi bi-disc"></i> Lançamentos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="analytics/statistics"><i class="bi bi-bar-chart"></i> Estatísticas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="finances/overview"><i class="bi bi-currency-dollar"></i> Finanças</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="artists/artists-list"><i class="bi bi-person"></i> Artistas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="artists/youtube/ucy"><i class="bi bi-youtube"></i> Unificação de canal
                        YouTube</a>
                </li>
                <!-- Links secundários exibidos apenas em mobile -->
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="user/profile"><i class="bi bi-person-circle"></i> Meu Perfil</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="page/settings"><i class="bi bi-gear"></i> Configurações</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="page/notifications"><i class="bi bi-bell"></i> Notificações</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="page/about"><i class="bi bi-info-circle"></i> Sobre</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="services/available-services"><i class="bi bi-star"></i> Conta e serviços
                        disponíveis</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="page/help"><i class="bi bi-question-circle"></i> Ajuda</a>
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
    Regra: Bootstrap alert com X para acções pendentes
    do utilizador. Toastr para eventos em background.
    Página de notificações para histórico completo.
    ============================================ */ ?>

        <?php /* ── NÍVEL 1: Crítico — bloqueia distribuição ── */ ?>

        <?php if (!$email_verified): ?>
        <div class="alert alert-warning alert-dismissible d-flex align-items-center gap-3 mb-3" role="alert"
            id="banner-email">
            <i class="bi bi-envelope-exclamation-fill fs-4 flex-shrink-0"></i>
            <div class="flex-grow-1">
                <strong>Email não verificado.</strong>
                Verifica o teu e-mail para garantir o acesso à conta e receber notificações de pagamentos.
                <a href="account/manage-account" class="alert-link ms-1">Verificar agora &rarr;</a>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php endif; ?>

        <?php if ($plan && !$plan_paid): ?>
        <div class="alert alert-warning alert-dismissible d-flex align-items-center gap-3 mb-3" role="alert"
            id="banner-plan-pending">
            <i class="bi bi-clock-history fs-4 flex-shrink-0"></i>
            <div class="flex-grow-1">
                <strong>Pagamento pendente — <?php echo htmlspecialchars($plan['name_plan']); ?>.</strong>
                O plano foi seleccionado mas o pagamento ainda não foi confirmado. Os teus lançamentos estão pausados
                até confirmação.
                <a href="payment/pay" class="alert-link ms-1 fw-bold">Finalizar pagamento &rarr;</a>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php elseif (!$plan): ?>
        <div class="alert alert-danger d-flex align-items-center gap-3 mb-3" role="alert" id="banner-plan">
            <i class="bi bi-credit-card-fill fs-4 flex-shrink-0"></i>
            <div class="flex-grow-1">
                <strong>Sem plano activo.</strong>
                Escolhe um plano para começar a distribuir a tua música para +150 plataformas.
                <a href="all-plans" class="alert-link ms-1 fw-bold">Ver planos &rarr;</a>
            </div>
        </div>
        <?php endif; ?>

        <?php /* ── NÍVEL 2: Importante — perfil incompleto ── */ ?>

        <?php if ($plan_paid && !$has_artist): ?>
        <div class="alert alert-info alert-dismissible d-flex align-items-center gap-3 mb-3" role="alert"
            id="banner-artist">
            <i class="bi bi-person-plus-fill fs-4 flex-shrink-0"></i>
            <div class="flex-grow-1">
                <strong>Cria o teu perfil de artista.</strong>
                Tens plano activo mas ainda não criaste um perfil. Precisas de um para poder lançar música.
                <a href="artists/add-artist" class="alert-link ms-1">Criar agora &rarr;</a>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php endif; ?>

        <?php /* ── NÍVEL 3: Informativo — conta bancária ── */ ?>

        <?php if ($plan_paid && $has_artist && !$bank_account): ?>
        <div class="alert alert-secondary alert-dismissible d-flex align-items-center gap-3 mb-3" role="alert"
            id="banner-bank">
            <i class="bi bi-bank fs-4 flex-shrink-0"></i>
            <div class="flex-grow-1">
                <strong>Conta bancária não registada.</strong>
                Para poder sacar os teus royalties, regista uma conta IBAN ou Multicaixa Express.
                <a href="finances/create-account" class="alert-link ms-1">Registar agora &rarr;</a>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php endif; ?>

        <?php /* ── NÍVEL 3: Conta bancária rejeitada ── */ ?>

        <?php
        $rejected_account = null;
        if ($plan_paid) {
            $rej_stmt = getDB()->prepare("SELECT type_account, reject_reason FROM _account WHERE id_users = ? AND status_account = 'rejected' LIMIT 1");
            $rej_stmt->execute([$id_users]);
            $rejected_account = $rej_stmt->fetch();
        }
        ?>
        <?php if ($rejected_account): ?>
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-3 mb-3" role="alert"
            id="banner-account-rejected">
            <i class="bi bi-x-circle-fill fs-4 flex-shrink-0"></i>
            <div class="flex-grow-1">
                <strong>Conta <?php echo htmlspecialchars($rejected_account['type_account']); ?> rejeitada.</strong>
                <?php if ($rejected_account['reject_reason']): ?>
                Motivo: <em><?php echo htmlspecialchars($rejected_account['reject_reason']); ?></em>.
                <?php endif; ?>
                Actualiza os dados e submete novamente.
                <a href="finances/create-account" class="alert-link ms-1">Corrigir agora &rarr;</a>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php endif; ?>

        <!-- Header da Pagina Inicial -->
        <div class="page-header">
            <h1>
                <i class="bi bi-house-door-fill me-3"></i> Olá, seja
                bem-vindo(a) de volta <br> <span class="text-warning"><?php echo $first_name; ?>!</span>
            </h1>
            <p class="lead">
                Aqui está um resumo do desempenho recente dos seus lançamentos e o que
                está acontecendo com sua conta. Continue acompanhando para não perder
                nenhuma novidade!
            </p>
            <!-- Ícone decorativo: casa -->
            <style>
            .page-header::before {
                content: '\F1D0';
                /* bi-house-door-fill */
            }
            </style>
        </div>
        <!-- Onboarding Modal -->
        <?php if (!$onboard_done): ?>
        <div class="modal fade" id="onboardingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background:linear-gradient(135deg,#FF0089,#FF4D4D);color:#fff">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-stars me-2"></i>Bem-vindo(a) ao Wasom Upfy, <?php echo $first_name; ?>!
                        </h5>
                    </div>
                    <div class="modal-body p-4">

                        <!-- Progress dots -->
                        <div class="d-flex justify-content-center mb-4 gap-3" id="ob-dots">
                            <span class="ob-dot active" data-step="1"></span>
                            <span class="ob-dot" data-step="2"></span>
                            <span class="ob-dot" data-step="3"></span>
                            <span class="ob-dot" data-step="4"></span>
                        </div>

                        <!-- Step 1: Boas vindas -->
                        <div class="ob-step" id="ob-1">
                            <div class="text-center mb-3"><i class="bi bi-emoji-smile-fill"
                                    style="font-size:3rem;color:#FF0089"></i></div>
                            <h5 class="text-center fw-bold">A tua conta foi criada com sucesso!</h5>
                            <p class="text-muted text-center">O Wasom Upfy distribui a tua música para mais de 150 lojas
                                digitais mundiais.</p>
                            <hr>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Distribui para
                                    +150 plataformas incluindo Spotify, Apple Music e Deezer</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Recebe
                                    royalties directamente na tua carteira</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Acompanha
                                    streams e estatisticas em tempo real</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Suporte
                                    dedicado em portugues</li>
                            </ul>
                            <div class="alert alert-info small">
                                <i class="bi bi-clock me-1"></i>
                                <strong>Aviso de sessão:</strong> Por segurança, se não iniciares sessão por <strong>30
                                    dias</strong> consecutivos, a tua sessão expira automaticamente e precisas de fazer
                                login novamente.
                            </div>
                        </div>

                        <!-- Step 2: Email -->
                        <div class="ob-step d-none" id="ob-2">
                            <div class="text-center mb-3"><i class="bi bi-envelope-check-fill"
                                    style="font-size:3rem;color:#FF0089"></i></div>
                            <h5 class="text-center fw-bold">Verificação de Email</h5>
                            <?php if (!$email_verified): ?>
                            <p class="text-muted text-center">
                                Enviamos um codigo de 6 digitos para
                                <strong><?php echo htmlspecialchars($user['email_user']); ?></strong>.
                            </p>
                            <div class="alert alert-warning">
                                <i class="bi bi-clock-history me-2"></i>
                                O teu email ainda não foi verificado. Podes continuar e verificar mais tarde em
                                <strong>Conta > Gerir Conta</strong>. O codigo não expira.
                            </div>
                            <?php else: ?>
                            <p class="text-muted text-center">
                                O seu email foi verificado:
                                <strong><?php echo htmlspecialchars($user['email_user']); ?></strong>.
                            </p>
                            <div class="alert alert-success text-center">
                                <i class="bi bi-patch-check-fill me-2"></i> Email verificado!
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Step 3: Plano -->
                        <div class="ob-step d-none" id="ob-3">
                            <div class="text-center mb-3"><i class="bi bi-star-fill"
                                    style="font-size:3rem;color:#FF0089"></i></div>
                            <h5 class="text-center fw-bold">O teu Plano</h5>
                            <?php if ($plan): ?>
                            <div class="card border-0 shadow-sm p-3 mb-3"
                                style="border-left:4px solid #FF0089!important">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($plan['name_plan']); ?>
                                        </h6>
                                        <p class="text-muted small mb-1">
                                            <?php echo htmlspecialchars($plan['description_plan'] ?? ''); ?></p>
                                        <small><i class="bi bi-percent me-1"></i><?php echo $plan['royalty_rate']; ?>%
                                            royalties para ti</small>
                                    </div>
                                    <div class="text-end">
                                        <strong
                                            style="color:#FF0089"><?php echo number_format($plan['price_plan'], 2, ',', '.'); ?>
                                            AOA</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-warning">
                                <i class="bi bi-credit-card me-2"></i>
                                Pagamento pendente. Procede ao pagamento para activar a distribuicao.
                                <a href="payment/pay" class="alert-link d-block mt-1 fw-bold">Finalizar
                                    pagamento &rarr;</a>
                            </div>
                            <?php else: ?>
                            <p class="text-center text-muted mb-3">Ainda não escolheste um plano.</p>
                            <div class="d-grid gap-2">
                                <a href="all-plans#single" class="btn btn-outline-pink btn-sm text-start"><i
                                        class="bi bi-music-note me-2"></i><strong>Single</strong> — Lançamentos
                                    avulsos</a>
                                <a href="all-plans#album" class="btn btn-outline-pink btn-sm text-start"><i
                                        class="bi bi-disc me-2"></i><strong>Album</strong> — Pacote de lançamentos</a>
                                <a href="all-plans#artist" class="btn btn-outline-pink btn-sm text-start"><i
                                        class="bi bi-person-badge me-2"></i><strong>Artist</strong> — Para artistas
                                    activos</a>
                                <a href="all-plans#label" class="btn btn-outline-pink btn-sm text-start"><i
                                        class="bi bi-building me-2"></i><strong>Label</strong> — Para editoras</a>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Step 4: Artista -->
                        <div class="ob-step d-none" id="ob-4">
                            <div class="text-center mb-3"><i class="bi bi-person-badge-fill"
                                    style="font-size:3rem;color:#FF0089"></i></div>
                            <h5 class="text-center fw-bold">Perfil de Artista</h5>
                            <p class="text-muted text-center">Para distribuir música precisas de um perfil de artista.
                            </p>
                            <?php if ($has_artist): ?>
                            <div class="alert alert-success text-center">
                                <i class="bi bi-check-circle-fill me-2"></i> Ja tens um perfil de artista. Tudo pronto!
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info small">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Nome artistico:</strong> No plano Single, o nome de selo é atribuido por nós.
                                Nos planos
                                Album, Artist e Label podes personalizar ao criar um lançamento.
                            </div>
                            <div class="d-grid gap-2 mt-3">
                                <a href="../dashboard/artists/add-artist" class="btn btn-wasomupfy">
                                    <i class="bi bi-person-plus me-2"></i>Criar Perfil de Artista
                                </a>
                                <button type="button" class="btn btn-link text-muted" id="ob-skip-artist">Criar mais
                                    tarde</button>
                            </div>
                            <?php endif; ?>
                        </div>

                    </div>
                    <div class="modal-footer d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-outline-secondary d-none" id="ob-prev">
                            <i class="bi bi-arrow-left me-1"></i> Anterior
                        </button>
                        <div></div>
                        <button type="button" class="btn btn-wasomupfy" id="ob-next">
                            Continuar <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                        <button type="button" class="btn btn-success d-none" id="ob-finish"
                            onclick="finishOnboarding()">
                            <i class="bi bi-check-lg me-1"></i> Entrar no Painel
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>


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
                    <a href="finances/withdraw" class="btn btn-pink">
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

        <!-- Launch Card -->
        <div class="launch-card mb-4">
            <div class="card">
                <div class="d-flex align-items-lg-center">
                    <i class="bi bi-vinyl-fill gt text-7xl me-4"></i>
                    <div class="m-auto w-100 text-center welcome-text">
                        <?php if (!$plan_paid): ?>
                        <h5>Activa o teu plano para lançar música</h5>
                        <p>Tens de completar o pagamento do plano antes de poder distribuir música nas plataformas.</p>
                        <a href="payment" class="btn btn-pink w-100">
                            <i class="bi bi-credit-card me-2"></i> Finalizar Pagamento
                        </a>
                        <?php elseif (!$has_artist): ?>
                        <h5>Cria o teu perfil de artista</h5>
                        <p>Antes de lançar música, precisa de um perfil de artista associado à tua conta.</p>
                        <a href="artists/add-artist" class="btn btn-pink w-100">
                            <i class="bi bi-person-plus me-2"></i> Criar Perfil de Artista
                        </a>
                        <?php else: ?>
                        <h5>Pronto para lançar a tua próxima música?</h5>
                        <p>Cria um novo lançamento com código UPC exclusivo e distribui para +150 plataformas em até 72
                            horas.</p>
                        <a href="launch/creat-release" class="btn btn-pink w-100">
                            <i class="bi bi-plus me-2"></i> Novo Lançamento
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Card -->
        <div class="chart-card mb-4 platforms-card data-simplebar">
            <div class="text-center">
                <h5>Desempenho de streams por plataforma</h5>
                <p>Acompanhe a evolução dos teus streams e identifique onde a tua música tem mais impacto.</p>
            </div>

            <?php if (!$plan_paid): ?>
            <!-- Estado: sem plano activo -->
            <div class="card text-center py-5">
                <i class="bi bi-lock fs-1 text-muted mb-3"></i>
                <h6 class="text-muted">Estatísticas bloqueadas</h6>
                <p class="text-muted small">Activa o teu plano para começar a distribuir e ver os teus streams.</p>
                <a href="payment" class="btn btn-pink btn-sm mt-2">
                    <i class="bi bi-credit-card me-1"></i>Activar Plano
                </a>
            </div>

            <?php elseif ($total_releases === 0): ?>
            <!-- Estado: tem plano mas sem lançamentos -->
            <div class="card text-center py-5">
                <i class="bi bi-vinyl fs-1 text-muted mb-3" style="opacity:.4"></i>
                <h6 class="text-muted">Ainda sem lançamentos</h6>
                <p class="text-muted small">
                    Cria o teu primeiro lançamento para começar a receber streams nas plataformas.
                </p>
                <?php if ($has_artist): ?>
                <a href="launch/creat-release" class="btn btn-pink btn-sm mt-2">
                    <i class="bi bi-plus me-1"></i>Criar Lançamento
                </a>
                <?php else: ?>
                <a href="artists/add-artist" class="btn btn-pink btn-sm mt-2">
                    <i class="bi bi-person-plus me-1"></i>Criar Perfil de Artista
                </a>
                <?php endif; ?>
            </div>

            <?php elseif (!$has_streams): ?>
            <!-- Estado: tem lançamentos mas streams ainda a chegar (aguarda 24-72h) -->
            <div class="card text-center py-5">
                <div class="mb-3">
                    <span class="spinner-border spinner-border-sm text-muted me-2"></span>
                    <i class="bi bi-hourglass-split fs-1 text-muted" style="opacity:.5"></i>
                </div>
                <h6 class="text-muted">A aguardar primeiros streams</h6>
                <p class="text-muted small">
                    Os teus lançamentos estão em distribuição. Os streams começam a aparecer em 24–72 horas após
                    aprovação.
                </p>
                <a href="analytics/statistics" class="btn btn-outline-secondary btn-sm mt-2">
                    <i class="bi bi-bar-chart me-1"></i>Ver Estatísticas
                </a>
            </div>

            <?php else: ?>
            <!-- Estado: tem streams reais -->
            <div class="card">
                <canvas id="streamChart"></canvas>
                <hr />
                <ul class="mt-2">
                    <?php
                        // Ícones CDN por plataforma
                        $platform_icons = [
                            'spotify'       => '<img src="https://upload.wikimedia.org/wikipedia/commons/1/19/Spotify_logo_without_text.svg" width="22" alt="Spotify">',
                            'apple_music'   => '<img src="https://upload.wikimedia.org/wikipedia/commons/5/5f/Apple_Music_icon.svg" width="22" alt="Apple Music">',
                            'deezer'        => '<img src="https://e-cdns-files.dzcdn.net/img/common/logos/deezer-logo.svg" width="22" alt="Deezer">',
                            'youtube_music' => '<img src="https://upload.wikimedia.org/wikipedia/commons/6/6a/Youtube_Music_icon.svg" width="22" alt="YouTube Music">',
                            'amazon_music'  => '<img src="https://upload.wikimedia.org/wikipedia/commons/d/d5/Amazon_Music_logo.svg" width="22" alt="Amazon Music">',
                            'tidal'         => '<i class="fa-brands fa-tidal fs-5"></i>',
                            'soundcloud'    => '<i class="fa-brands fa-soundcloud fs-5 text-warning"></i>',
                            'tiktok'        => '<i class="fa-brands fa-tiktok fs-5"></i>',
                        ];
                        foreach ($streams as $s):
                            $slug = $s['platform_slug'];
                            $icon = $platform_icons[$slug] ?? '<i class="bi bi-music-note-beamed fs-5 text-muted"></i>';
                        ?>
                    <li>
                        <div class="platform-info">
                            <?php echo $icon; ?>
                            <span><?php echo htmlspecialchars($s['platform_name']); ?></span>
                        </div>
                        <span class="stream-count"><?php echo number_format($s['total'], 0, ',', '.'); ?> streams</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <div class="launch-card mb-4">
            <div class="card">
                <a href="analytics/statistics" class="d-block text-center mt-3 btn btn-pink">
                    <i class="bi bi-bar-chart me-2"></i> Ver todas as estatísticas
                </a>
            </div>
        </div>

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
                        <form method="post" action="finances/withdrawal_process" class="needs-validation row g-3"
                            novalidate id="withdrawal-form">
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
                            <a href="finances/withdraw" class="btn btn-pink btn-sm">
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
    </div>

    <nav class="bottom-nav d-lg-none">
        <ul class="nav justify-content-around">
            <li class="nav-item">
                <a class="nav-link" href="painel" aria-label="Ir para Dashboard"><i
                        class="bi bi-speedometer2"></i><span>Dashboard</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="launch/releases" aria-label="Ir para Lançamentos"><i
                        class="bi bi-disc"></i><span>Lançamentos</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="analytics/statistics" aria-label="Ir para Estatísticas"><i
                        class="bi bi-bar-chart"></i><span>Estatísticas</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="finances/overview" aria-label="Ir para Finanças"><i
                        class="bi bi-currency-dollar"></i><span>Finanças</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="artists/artists-list" aria-label="Ir para Artistas"><i
                        class="bi bi-person"></i><span>Artistas</span></a>
            </li>
        </ul>
    </nav>

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
        window.location = 'logout';
    }
    </script>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/theme.wp.js"></script>
    <script src="../js/wp.tools.js"></script>
    <script>
    const tooltipTriggerList = document.querySelectorAll(
        '[data-bs-toggle="tooltip"]'
    );
    const tooltipList = [...tooltipTriggerList].map(
        (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
    );
    </script>
    <script>
    // ── Gráfico de streams — dados reais da BD ──────────────
    <?php if ($has_streams && !empty($chart_json_datasets)): ?>
        (function() {
            const canvas = document.getElementById('streamChart');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo $chart_json_labels; ?>,
                    datasets: <?php echo $chart_json_datasets; ?>
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            stacked: true,
                            ticks: {
                                callback: v => v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v
                            },
                            title: {
                                display: true,
                                text: 'Streams'
                            }
                        },
                        x: {
                            stacked: true,
                            title: {
                                display: true,
                                text: 'Período'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: ctx =>
                                    ` ${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString('pt-AO')} streams`
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        })();
    <?php endif; ?>
    </script>

    <script>
    // ══════════════════════════════════════
    // ONBOARDING
    // ══════════════════════════════════════
    (function() {
        const TOTAL = 4;
        let current = 1;

        const modal = document.getElementById('onboardingModal');
        if (!modal) return; // onboarding_done = true, modal nao existe

        const btnNext = document.getElementById('ob-next');
        const btnPrev = document.getElementById('ob-prev');
        const btnFinish = document.getElementById('ob-finish');
        const btnSkip = document.getElementById('ob-skip-artist');
        const dots = document.querySelectorAll('.ob-dot');

        // Abrir modal automaticamente
        const bsModal = new bootstrap.Modal(modal, {
            backdrop: 'static'
        });
        bsModal.show();

        function goTo(n) {
            document.getElementById('ob-' + current).classList.add('d-none');
            current = n;
            document.getElementById('ob-' + current).classList.remove('d-none');

            // Dots
            dots.forEach((d, i) => d.classList.toggle('active', i + 1 === current));

            // Botoes
            btnPrev.classList.toggle('d-none', current === 1);
            btnNext.classList.toggle('d-none', current === TOTAL);
            btnFinish.classList.toggle('d-none', current !== TOTAL);
        }

        btnNext.addEventListener('click', () => {
            if (current < TOTAL) goTo(current + 1);
        });
        btnPrev.addEventListener('click',
            () => {
                if (current > 1) goTo(current - 1);
            });
        if (btnSkip) btnSkip.addEventListener('click', () => goTo(TOTAL)); // ja e o step 4, avanca para finish

        window.finishOnboarding = function() {
            // Marcar onboarding como feito via fetch
            fetch('/wasomupfy/dashboard/onboarding_done', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    csrf: '<?php echo $_SESSION["csrf_token"]; ?>'
                })
            }).finally(() => bsModal.hide());
        };
    })();
    </script>
</body>

</html>