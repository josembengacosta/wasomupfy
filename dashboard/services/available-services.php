<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Conta e Serviços Disponíveis
// Arquivo: dashboard/services/available-services.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];
$user     = getUserById($id_users);
if (!$user) { redirect('authentic/logout'); }

$first_name       = htmlspecialchars($user['first_name'] ?? '');
$last_name        = htmlspecialchars($user['second_name'] ?? '');
$user_artist_name = htmlspecialchars($user['name_artist_band'] ?? $user['first_name'] ?? '');

// ── Plano activo do utilizador ────────────────
$active_plan = null;
try {
    $q = $db->prepare("
        SELECT p.id_plan, p.name_plan, p.slug_plan, p.price_plan, p.type_plan,
               p.royalty_rate, p.max_artists, p.max_releases, p.badge_text,
               up.status_plan, up.started_at, up.expires_at, up.releases_used, up.releases_limit
        FROM _user_plan up
        JOIN _plans p ON p.id_plan = up.id_plan
        WHERE up.id_users = ? AND up.status_plan = 'active'
        ORDER BY up.started_at DESC
        LIMIT 1
    ");
    $q->execute([$id_users]);
    $active_plan = $q->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $e) {}

// Fallback: plan_selected no _users
if (!$active_plan && !empty($user['plan_selected'])) {
    try {
        $q2 = $db->prepare("
            SELECT id_plan, name_plan, slug_plan, price_plan, type_plan,
                   royalty_rate, max_artists, max_releases, badge_text
            FROM _plans WHERE id_plan = ?
        ");
        $q2->execute([$user['plan_selected']]);
        $row = $q2->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $active_plan = array_merge($row, [
                'status_plan'    => 'active',
                'started_at'     => $user['plan_activated_at'] ?? null,
                'expires_at'     => $user['plan_expires_at']   ?? null,
                'releases_used'  => 0,
                'releases_limit' => $row['max_releases'],
            ]);
        }
    } catch (PDOException $e) {}
}

// ── Todos os planos ───────────────────────────
$all_plans = [];
try {
    $q3 = $db->prepare("
        SELECT id_plan, name_plan, slug_plan, price_plan, price_annual, annual_qty,
               type_plan, royalty_rate, max_artists, max_releases, max_tracks_per_release,
               validity_days, badge_text, is_featured, img_plan
        FROM _plans
        WHERE is_active = 1
        ORDER BY display_order ASC
    ");
    $q3->execute();
    $all_plans = $q3->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// ── Features por plano ────────────────────────
$features_by_plan = [];
try {
    $q4 = $db->prepare("
        SELECT id_plan, feature_text, is_included
        FROM _plan_features
        ORDER BY id_plan, display_order ASC
    ");
    $q4->execute();
    foreach ($q4->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $features_by_plan[$f['id_plan']][] = $f;
    }
} catch (PDOException $e) {}

// ── Ícone por slug ────────────────────────────
$plan_icons = [
    'single' => 'bi-music-note',
    'album'  => 'bi-disc-fill',
    'artist' => 'bi-person-badge-fill',
    'label'  => 'bi-building-fill',
];
$plan_colors = [
    'single' => '#6c757d',
    'album'  => '#0d6efd',
    'artist' => '#FF0089',
    'label'  => '#198754',
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
    <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png" />
    <link rel="manifest" href="../manifest.json" />
    <title>Conta e Serviços — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="../../css/dashboard-style.css" />
    <link rel="stylesheet" href="../../css/lastest-style.css" />
    <style>
    /* ══ Header ══ */
    .services-header {
        background: linear-gradient(135deg, #FF0089 0%, #c8006e 55%, #7b0044 100%);
        border-radius: 20px;
        padding: 2.2rem 2.5rem;
        margin-bottom: 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
    }

    .services-header::after {
        content: '\F541';
        font-family: 'bootstrap-icons';
        position: absolute;
        right: -20px;
        bottom: -24px;
        font-size: 9rem;
        opacity: .07;
        color: #fff;
        transform: rotate(15deg);
    }

    .services-header .header-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, .18);
        border: 1px solid rgba(255, 255, 255, .3);
        border-radius: 999px;
        padding: 4px 14px;
        font-size: .78rem;
        font-weight: 700;
        backdrop-filter: blur(4px);
        margin-bottom: .8rem;
    }

    /* ══ Plan cards ══ */
    .plan-card {
        border-radius: 18px;
        border: 2px solid var(--border-color, rgba(0, 0, 0, .08));
        transition: transform .2s, box-shadow .2s;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .plan-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(255, 0, 137, .15);
    }

    .plan-card.active-plan {
        border-color: #FF0089;
        box-shadow: 0 0 0 3px rgba(255, 0, 137, .15), 0 8px 24px rgba(255, 0, 137, .12);
    }

    .plan-card.featured-plan {
        border-color: #FF0089;
    }

    .plan-card-header {
        padding: 1.4rem 1.4rem .8rem;
        border-radius: 16px 16px 0 0;
        position: relative;
    }

    .plan-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        margin-bottom: .7rem;
    }

    .plan-name {
        font-size: 1.1rem;
        font-weight: 800;
        margin: 0;
    }

    .plan-badge-top {
        position: absolute;
        top: 14px;
        right: 14px;
        font-size: .68rem;
        font-weight: 700;
        border-radius: 999px;
        padding: 3px 10px;
    }

    .plan-price {
        font-size: 1.8rem;
        font-weight: 900;
        line-height: 1;
        color: #FF0089;
        margin: .5rem 0 .2rem;
    }

    .plan-price small {
        font-size: .8rem;
        font-weight: 500;
        color: var(--text-muted, #6c757d);
    }

    .plan-price-annual {
        font-size: .75rem;
        color: var(--text-muted, #6c757d);
        margin-bottom: .8rem;
    }

    .plan-features-list {
        list-style: none;
        padding: 0;
        margin: 0;
        flex: 1;
    }

    .plan-features-list li {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 5px 0;
        font-size: .82rem;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .05));
    }

    .plan-features-list li:last-child {
        border-bottom: none;
    }

    .plan-features-list li.not-included {
        opacity: .45;
        text-decoration: line-through;
    }

    .plan-features-list li .feat-icon {
        font-size: .85rem;
        flex-shrink: 0;
        margin-top: 1px;
    }

    /* ══ Current plan info card ══ */
    .current-plan-card {
        border-radius: 18px;
        padding: 1.5rem;
        border: 2px solid #FF0089;
        background: linear-gradient(135deg, rgba(255, 0, 137, .04), transparent);
    }

    .current-plan-stat {
        background: var(--metric-bg, rgba(0, 0, 0, .03));
        border-radius: 12px;
        padding: .85rem 1rem;
        text-align: center;
    }

    .current-plan-stat .stat-val {
        font-size: 1.3rem;
        font-weight: 800;
        color: #FF0089;
    }

    .current-plan-stat .stat-lbl {
        font-size: .7rem;
        color: var(--text-muted, #6c757d);
        margin-top: 2px;
    }

    /* ══ Section header ══ */
    .section-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: #FF0089;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 1.2rem;
    }

    /* ══ CTA contact ══ */
    .contact-info-bar {
        background: var(--metric-bg, rgba(0, 0, 0, .03));
        border-radius: 14px;
        padding: 1rem 1.4rem;
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }

    @media(max-width: 768px) {
        .services-header {
            padding: 1.5rem;
        }

        .plan-price {
            font-size: 1.4rem;
        }
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
                    <a class="nav-link" href="available-services"><i class="bi bi-star"></i> Conta e serviços
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

    <!-- ═══ MAIN ═══ -->
    <main class="container my-4">

        <!-- ══ HEADER ══ -->
        <div class="services-header">
            <div class="header-badge">
                <?php if ($active_plan): ?>
                <i class="bi bi-star-fill"></i>
                Plano <?php echo htmlspecialchars($active_plan['name_plan']); ?> activo
                <?php else: ?>
                <i class="bi bi-star"></i>
                Sem plano activo
                <?php endif; ?>
            </div>
            <h1 class="fw-bold mb-1"><i class="bi bi-layers-fill me-2"></i>Conta e Serviços</h1>
            <p class="lead mb-0" style="opacity:.85">
                Gere o teu plano, consulta os benefícios incluídos e compara todas as opções disponíveis.
            </p>
        </div>

        <!-- ══ PLANO ACTUAL ══ -->
        <div class="section-title"><i class="bi bi-shield-check"></i>O teu plano actual</div>

        <?php if ($active_plan): ?>
        <?php
    $slug        = $active_plan['slug_plan'];
    $plan_color  = $plan_colors[$slug]  ?? '#FF0089';
    $plan_icon   = $plan_icons[$slug]   ?? 'bi-star-fill';
    $is_sub      = $active_plan['type_plan'] === 'subscription';
    $expires_fmt = !empty($active_plan['expires_at'])
                    ? date('d/m/Y', strtotime($active_plan['expires_at']))
                    : ($is_sub ? '—' : 'Sem expiração');
    $started_fmt = !empty($active_plan['started_at'])
                    ? date('d/m/Y', strtotime($active_plan['started_at']))
                    : '—';
    $releases_used  = (int)($active_plan['releases_used'] ?? 0);
    $releases_limit = $active_plan['releases_limit'];
    ?>
        <div class="current-plan-card mb-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="plan-icon"
                    style="background:<?php echo $plan_color; ?>22; color:<?php echo $plan_color; ?>">
                    <i class="bi <?php echo $plan_icon; ?>"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($active_plan['name_plan']); ?>
                        <?php if ($active_plan['badge_text']): ?>
                        <span class="badge ms-2" style="background:<?php echo $plan_color; ?>;font-size:.65rem">
                            <?php echo htmlspecialchars($active_plan['badge_text']); ?>
                        </span>
                        <?php endif; ?>
                    </h4>
                    <span class="badge bg-success">Activo</span>
                    <span class="badge ms-1" style="background:rgba(0,0,0,.12);color:#FF0089">
                        <?php echo $is_sub ? 'Subscrição anual' : 'Por lançamento'; ?>
                    </span>
                </div>
                <div class="ms-auto text-end">
                    <div style="font-size:1.4rem;font-weight:900;color:#FF0089">
                        <?php echo number_format($active_plan['price_plan'], 0, ',', '.'); ?> AOA
                    </div>
                    <div style="font-size:.72rem;color:var(--text-muted,#6c757d)">
                        <?php echo $is_sub ? 'por ano' : 'por lançamento'; ?>
                    </div>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6 col-md-3">
                    <div class="current-plan-stat">
                        <div class="stat-val"><?php echo $active_plan['royalty_rate']; ?>%</div>
                        <div class="stat-lbl">Royalties</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="current-plan-stat">
                        <div class="stat-val"><?php echo $active_plan['max_artists'] ?? '∞'; ?></div>
                        <div class="stat-lbl">Artistas</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="current-plan-stat">
                        <div class="stat-val"><?php echo $started_fmt; ?></div>
                        <div class="stat-lbl">Activado em</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="current-plan-stat">
                        <div class="stat-val" style="font-size:1rem">
                            <?php echo $is_sub ? $expires_fmt : ($releases_limit ? "$releases_used / $releases_limit" : '∞'); ?>
                        </div>
                        <div class="stat-lbl"><?php echo $is_sub ? 'Validade' : 'Lançamentos usados'; ?></div>
                    </div>
                </div>
            </div>

            <?php if (!$is_sub && $releases_limit): ?>
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1" style="font-size:.78rem">
                    <span>Lançamentos utilizados</span>
                    <strong><?php echo $releases_used; ?> / <?php echo $releases_limit; ?></strong>
                </div>
                <div class="progress" style="height:6px;border-radius:999px">
                    <div class="progress-bar" role="progressbar"
                        style="width:<?php echo min(100, round($releases_used / $releases_limit * 100)); ?>%;background:#FF0089;border-radius:999px">
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2 mt-2">
                <a href="#all-plans" class="btn btn-sm"
                    style="background:linear-gradient(135deg,#FF0089,#c8006e);color:#fff;border:none;border-radius:9px;font-weight:600">
                    <i class="bi bi-arrow-up-circle me-1"></i>Alterar plano
                </a>
                <a href="../page/support" class="btn btn-sm btn-outline-secondary" style="border-radius:9px">
                    <i class="bi bi-headset me-1"></i>Contactar suporte
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="alert" style="border-radius:14px;border:2px dashed #FF0089;background:rgba(255,0,137,.04)"
            role="alert">
            <i class="bi bi-info-circle me-2" style="color:#FF0089"></i>
            Ainda não tens um plano activo. Escolhe um plano abaixo para começar a distribuir a tua música.
        </div>
        <?php endif; ?>

        <!-- ══ TODOS OS PLANOS ══ -->
        <div class="section-title mt-2" id="all-plans">
            <i class="bi bi-grid-3x2-gap"></i>Todos os planos disponíveis
        </div>

        <?php if (!empty($all_plans)): ?>
        <div class="row g-3 mb-4">
            <?php foreach ($all_plans as $plan):
            $is_active_p = $active_plan && $active_plan['id_plan'] == $plan['id_plan'];
            $pslug   = $plan['slug_plan'];
            $pcolor  = $plan_colors[$pslug]  ?? '#FF0089';
            $picon   = $plan_icons[$pslug]   ?? 'bi-star';
            $is_sub_p = $plan['type_plan'] === 'subscription';
            $feats   = $features_by_plan[$plan['id_plan']] ?? [];
        ?>
            <div class="col-sm-6 col-lg-3">
                <div
                    class="plan-card <?php echo $is_active_p ? 'active-plan' : ''; ?> <?php echo $plan['is_featured'] && !$is_active_p ? 'featured-plan' : ''; ?>">
                    <div class="plan-card-header">
                        <?php if ($is_active_p): ?>
                        <span class="plan-badge-top badge bg-success">Activo</span>
                        <?php elseif ($plan['is_featured']): ?>
                        <span class="plan-badge-top badge" style="background:#FF0089">Recomendado</span>
                        <?php elseif ($plan['badge_text']): ?>
                        <span
                            class="plan-badge-top badge bg-secondary"><?php echo htmlspecialchars($plan['badge_text']); ?></span>
                        <?php endif; ?>

                        <div class="plan-icon" style="background:<?php echo $pcolor; ?>18;color:<?php echo $pcolor; ?>">
                            <i class="bi <?php echo $picon; ?>"></i>
                        </div>
                        <p class="plan-name"><?php echo htmlspecialchars($plan['name_plan']); ?></p>
                        <div class="plan-price">
                            <?php echo number_format($plan['price_plan'], 0, ',', '.'); ?> <small>AOA</small>
                        </div>
                        <div class="plan-price-annual">
                            <?php if ($is_sub_p): ?>
                            por ano
                            <?php elseif ($plan['price_annual'] && $plan['annual_qty']): ?>
                            ou <?php echo number_format($plan['price_annual'], 0, ',', '.'); ?> AOA (pacote
                            <?php echo $plan['annual_qty']; ?>x)
                            <?php else: ?>
                            por lançamento
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="px-3 pb-2" style="flex:1">
                        <ul class="plan-features-list">
                            <?php foreach ($feats as $f): ?>
                            <li class="<?php echo $f['is_included'] ? '' : 'not-included'; ?>">
                                <i
                                    class="feat-icon bi <?php echo $f['is_included'] ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger'; ?>"></i>
                                <?php echo htmlspecialchars($f['feature_text']); ?>
                            </li>
                            <?php endforeach; ?>
                            <?php if (empty($feats)): ?>
                            <li><i class="feat-icon bi bi-check-circle-fill text-success"></i> Distribuição em 157+
                                lojas</li>
                            <li><i class="feat-icon bi bi-check-circle-fill text-success"></i>
                                <?php echo $plan['royalty_rate']; ?>% dos royalties</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div class="p-3 pt-2">
                        <?php if ($is_active_p): ?>
                        <button class="btn w-100 btn-sm" disabled
                            style="background:linear-gradient(135deg,#FF0089,#c8006e);color:#fff;border:none;border-radius:9px;font-weight:700;opacity:.7">
                            <i class="bi bi-check-lg me-1"></i>Plano actual
                        </button>
                        <?php else: ?>
                        <a href="../page/support?plano=<?php echo urlencode($plan['slug_plan']); ?>"
                            class="btn w-100 btn-sm btn-outline-secondary" style="border-radius:9px;font-weight:600">
                            <i class="bi bi-headset me-1"></i>Contactar para activar
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ══ NOTA / CTA ══ -->
        <div class="contact-info-bar mb-4">
            <i class="bi bi-info-circle-fill fs-4" style="color:#FF0089;flex-shrink:0"></i>
            <div style="flex:1">
                <div class="fw-semibold" style="font-size:.9rem">Pretende alterar o teu plano?</div>
                <div class="text-muted" style="font-size:.8rem">
                    Para efectuar alterações ao plano activo, entra em contacto com a nossa equipa de suporte.
                    Vamos ajudar-te a encontrar o plano certo para o teu projecto musical.
                </div>
            </div>
            <a href="../page/support" class="btn btn-sm flex-shrink-0"
                style="background:linear-gradient(135deg,#FF0089,#c8006e);color:#fff;border:none;border-radius:9px;font-weight:600;white-space:nowrap">
                <i class="bi bi-headset me-1"></i>Ir para suporte
            </a>
        </div>

    </main>

    <!-- Bottom Nav Mobile -->
    <nav class="bottom-nav d-lg-none">
        <ul class="nav justify-content-around">
            <li class="nav-item"><a class="nav-link" href="../painel"><i
                        class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../launch/releases"><i
                        class="bi bi-disc"></i><span>Lançamentos</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../analytics/statistics"><i
                        class="bi bi-bar-chart"></i><span>Stats</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
                        class="bi bi-currency-dollar"></i><span>Finanças</span></a></li>
            <li class="nav-item"><a class="nav-link active" href="available-services"><i
                        class="bi bi-star"></i><span>Serviços</span></a></li>
        </ul>
    </nav>

    <!-- Modal Logout -->
    <div class="modal fade" id="logoutwasomupfy" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">Terminar sessão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center text-dark">
                    <p>Tens a certeza de que desejas terminar sessão, <strong><?php echo $first_name; ?></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Não, continuar</button>
                    <a href="../logout" class="btn btn-danger">Sim, terminar sessão</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/theme.wp.js"></script>
    <script src="../../js/wp.tools.js"></script>
    <script>
    // Tema toggle navbar
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    (function() {
        const saved = localStorage.getItem('theme') || 'dark';
        if (saved === 'dark') {
            document.body.classList.add('dark-mode');
            themeIcon.className = 'bi bi-moon';
        } else {
            document.body.classList.add('light-mode');
            themeIcon.className = 'bi bi-sun';
        }
    })();
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const isDark = document.body.classList.contains('dark-mode');
            const next = isDark ? 'light' : 'dark';
            document.body.classList.toggle('dark-mode', next === 'dark');
            document.body.classList.toggle('light-mode', next === 'light');
            themeIcon.className = next === 'dark' ? 'bi bi-moon' : 'bi bi-sun';
            localStorage.setItem('theme', next);
        });
    }
    </script>
</body>

</html>