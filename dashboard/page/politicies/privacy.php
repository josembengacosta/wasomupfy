<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Política de Privacidade
// Arquivo: dashboard/page/privacy.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$id_users   = (int)$_SESSION['id_users'];
$user       = getUserById($id_users);
if (!$user) {
    session_destroy();
    redirect(APP_URL  . '/' . 'login', ['error' => 'csrf']);
}

$first_name = htmlspecialchars($user['first_name'] ?? '');
$full_name  = htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['second_name'] ?? '')));

define('PRIVACY_VERSION', '2.0');
define('PRIVACY_DATE',    '11 de Março de 2026');
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <link rel="apple-touch-icon" href="../../../assets/img/icones/wasomupfy_fiv_512.png" />
    <link rel="manifest" href="../manifest.json" />
    <title>Política de Privacidade — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../../assets/img/icones/wasomupfy_fiv.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/dashboard-style.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/lastest-style.css" />
    <style>
        /* ══ Progress bar ══ */
        .read-progress {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            z-index: 9999;
            background: rgba(0, 0, 0, .08);
        }

        .read-progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #0d6efd, #6f42c1);
            transition: width .1s linear;
        }

        /* ══ Hero ══ */
        .privacy-hero {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 55%, #24243e 100%);
            border-radius: 22px;
            padding: 3rem 2.4rem 2.4rem;
            margin-bottom: 2rem;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .privacy-hero::before {
            content: '\F4B3';
            font-family: 'bootstrap-icons';
            position: absolute;
            right: -20px;
            bottom: -30px;
            font-size: 11rem;
            opacity: .06;
        }

        .privacy-hero .version-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(13, 110, 253, .25);
            border: 1px solid rgba(13, 110, 253, .45);
            border-radius: 999px;
            padding: 4px 14px;
            font-size: .75rem;
            font-weight: 700;
            margin-bottom: .8rem;
        }

        .privacy-hero h1 {
            font-size: 2.2rem;
            font-weight: 900;
            margin-bottom: .4rem;
        }

        .privacy-hero p {
            opacity: .8;
            font-size: .92rem;
            max-width: 640px;
            margin-bottom: 0;
        }

        .privacy-hero .hero-meta {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-top: 1.2rem;
        }

        .privacy-hero .hero-meta span {
            font-size: .78rem;
            opacity: .7;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ══ Action buttons ══ */
        .action-btns {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }

        .action-btns a,
        .action-btns button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: .42rem 1.2rem;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 700;
            border: 1.5px solid rgba(13, 110, 253, .35);
            color: #0d6efd;
            background: transparent;
            text-decoration: none;
            transition: all .2s;
            cursor: pointer;
        }

        .action-btns a:hover,
        .action-btns button:hover {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        /* ══ Layout ══ */
        .privacy-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 2rem;
            align-items: start;
        }

        @media(max-width:991px) {
            .privacy-layout {
                grid-template-columns: 1fr;
            }
        }

        /* ══ Index sidebar ══ */
        .privacy-index {
            background: var(--card-bg, #fff);
            border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
            border-radius: 18px;
            padding: 1.5rem;
            position: sticky;
            top: 80px;
        }

        .privacy-index h3 {
            font-size: .9rem;
            font-weight: 900;
            color: #0d6efd;
            margin-bottom: 1rem;
        }

        .privacy-index ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .privacy-index li {
            margin-bottom: .35rem;
        }

        .privacy-index a {
            font-size: .78rem;
            color: var(--text-muted, #6c757d);
            text-decoration: none;
            display: flex;
            align-items: flex-start;
            gap: 6px;
            line-height: 1.4;
            padding: .25rem .4rem;
            border-radius: 7px;
            transition: all .15s;
        }

        .privacy-index a .num {
            color: #0d6efd;
            font-weight: 800;
            flex-shrink: 0;
            min-width: 18px;
        }

        .privacy-index a:hover,
        .privacy-index a.active {
            color: #0d6efd;
            background: rgba(13, 110, 253, .07);
        }

        /* ══ Privacy content ══ */
        .privacy-content {
            background: var(--card-bg, #fff);
            border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
            border-radius: 18px;
            padding: 2.5rem;
        }

        @media(max-width:576px) {
            .privacy-content {
                padding: 1.4rem;
            }
        }

        .priv-section {
            margin-bottom: 2.5rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .06));
        }

        .priv-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .priv-section h2 {
            font-size: 1.1rem;
            font-weight: 800;
            color: #0d6efd;
            margin-bottom: 1rem;
            padding-bottom: .5rem;
            border-bottom: 2px solid rgba(13, 110, 253, .12);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .priv-section h2 .sec-num {
            background: rgba(13, 110, 253, .1);
            color: #0d6efd;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .82rem;
            font-weight: 900;
            flex-shrink: 0;
        }

        .priv-section h3 {
            font-size: .9rem;
            font-weight: 800;
            margin: 1.2rem 0 .6rem;
            color: var(--heading-color, #222);
        }

        .priv-section p {
            font-size: .87rem;
            line-height: 1.8;
            margin-bottom: .8rem;
            color: var(--text-body, #444);
        }

        .priv-section ul {
            padding-left: 0;
            list-style: none;
            margin-bottom: .8rem;
        }

        .priv-section ul li {
            font-size: .87rem;
            line-height: 1.7;
            padding: .3rem 0 .3rem 1.3rem;
            position: relative;
            color: var(--text-body, #444);
        }

        .priv-section ul li::before {
            content: '›';
            position: absolute;
            left: 0;
            color: #0d6efd;
            font-weight: 900;
        }

        /* ══ Highlight boxes ══ */
        .priv-box {
            border-radius: 12px;
            padding: 1rem 1.2rem;
            margin: 1rem 0;
            font-size: .84rem;
            line-height: 1.7;
        }

        .priv-box.blue {
            background: rgba(13, 110, 253, .08);
            border-left: 4px solid #0d6efd;
            color: var(--text-body, #444);
        }

        .priv-box.purple {
            background: rgba(111, 66, 193, .08);
            border-left: 4px solid #6f42c1;
            color: var(--text-body, #444);
        }

        .priv-box.green {
            background: rgba(25, 135, 84, .08);
            border-left: 4px solid #198754;
            color: var(--text-body, #444);
        }

        .priv-box.yellow {
            background: rgba(255, 193, 7, .1);
            border-left: 4px solid #ffc107;
            color: var(--text-body, #444);
        }

        .priv-box.red {
            background: rgba(220, 53, 69, .08);
            border-left: 4px solid #dc3545;
            color: var(--text-body, #444);
        }

        .priv-box strong {
            display: block;
            margin-bottom: .3rem;
        }

        /* ══ Dados table ══ */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            font-size: .82rem;
        }

        .data-table th {
            background: rgba(13, 110, 253, .08);
            color: #0d6efd;
            padding: .65rem 1rem;
            text-align: left;
            font-weight: 800;
            border-bottom: 2px solid rgba(13, 110, 253, .2);
        }

        .data-table td {
            padding: .6rem 1rem;
            border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .07));
            vertical-align: top;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover td {
            background: rgba(13, 110, 253, .03);
        }

        /* ══ Direitos do utilizador — cards ══ */
        .rights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            margin: 1rem 0;
        }

        .right-card {
            background: var(--metric-bg, rgba(0, 0, 0, .03));
            border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
            border-radius: 14px;
            padding: 1rem;
            text-align: center;
        }

        .right-card i {
            font-size: 1.6rem;
            color: #0d6efd;
            display: block;
            margin-bottom: .5rem;
        }

        .right-card strong {
            font-size: .82rem;
            display: block;
            margin-bottom: .3rem;
        }

        .right-card span {
            font-size: .75rem;
            color: var(--text-muted, #6c757d);
        }

        /* ══ Retenção de dados — timeline ══ */
        .retention-list {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }

        .retention-list li {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: .6rem 0;
            border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .06));
            font-size: .85rem;
        }

        .retention-list li:last-child {
            border-bottom: none;
        }

        .retention-badge {
            background: rgba(13, 110, 253, .1);
            color: #0d6efd;
            border-radius: 8px;
            padding: .25rem .7rem;
            font-size: .72rem;
            font-weight: 800;
            white-space: nowrap;
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* ══ Back to top ══ */
        #backToTop {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #0d6efd;
            color: #fff;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            box-shadow: 0 4px 14px rgba(13, 110, 253, .4);
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s;
            z-index: 1000;
            cursor: pointer;
        }

        #backToTop.visible {
            opacity: 1;
            pointer-events: auto;
        }

        /* ══ Print ══ */
        @media print {

            .navbar,
            .offcanvas,
            .bottom-nav,
            .action-btns,
            .privacy-index,
            #backToTop,
            .read-progress,
            nav {
                display: none !important;
            }

            .privacy-layout {
                grid-template-columns: 1fr !important;
            }

            .privacy-content {
                border: none !important;
                padding: 0 !important;
            }

            .priv-section h2 {
                color: #000 !important;
            }
        }
    </style>
</head>

<body>

    <!-- Barra de progresso de leitura -->
    <div class="read-progress">
        <div class="read-progress-fill" id="progressBar"></div>
    </div>

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
            <a class="navbar-brand" href="../../painel">
                <!-- SVG Logo Wasom Upfy -->
                <!-- <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="116" height="36" rx="5" fill="none" stroke="#ff0089" stroke-width="2" />
                    <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold"
                        fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
                </svg> -->
                <span class="text-light" style="
              font-weight: bold;
              box-sizing: border-box;
              text-transform: uppercase;
              font-family: Arial, sans-serif;
            "><?php echo APP_NAME; ?></span>
            </a>

            <!-- Desktop Menu -->
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav m-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="../../painel"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../launch/releases"><i class="bi bi-disc"></i> Lançamentos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../analytics/statistics"><i class="bi bi-bar-chart"></i>
                            Estatísticas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../finances/overview"><i class="bi bi-currency-dollar"></i>
                            Finanças</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../artists/artists-list"><i class="bi bi-person"></i> Artistas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../artists/youtube/ucy"><i class="bi bi-youtube"></i> Unificação de
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
                        <a class="dropdown-item" href="../../user/profile"><i class="bi bi-person me-2"></i>
                            <strong><?php echo $first_name; ?></strong></a>
                        <div class="text-white-50">
                            &nbsp; &nbsp; &nbsp; &nbsp; (Conta <?php echo str_pad($id_users, 6, "0", STR_PAD_LEFT); ?>)
                        </div>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <a class="dropdown-item" href="../../user/profile"><i class="bi bi-person me-2"></i> Meu
                            Perfil</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../../account/manage-account"><i class="bi bi-tools me-2"></i>
                            Gestão de
                            Conta</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <a class="dropdown-item" href="../../page/settings"><i class="bi bi-gear me-2"></i>
                            Configurações</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../../page/notifications"><i class="bi bi-bell me-2"></i>
                            Notificações</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../../page/plans"><i class="bi bi-star me-2"></i> Planos</a>
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
                        <a class="dropdown-item" href="../../page/about"><i class="bi bi-info-circle me-2"></i>
                            Sobre</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../../page/support"><i class="bi bi-headset me-2"></i> Enviar
                            pedido de
                            suporte</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../../page/faq"><i class="bi bi-chat-left-text me-2"></i>
                            Perguntas
                            frequentes</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../../page/help"><i class="bi bi-question-circle me-2"></i>
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
              text-transform: uppercase;
              font-family: Arial, sans-serif;
            "><?php echo APP_NAME; ?></span>
            </h5>
            <button type="button" class="btn-close text-white" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="../../painel"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../launch/releases"><i class="bi bi-disc"></i> Lançamentos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../analytics/statistics"><i class="bi bi-bar-chart"></i>
                        Estatísticas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../finances/overview"><i class="bi bi-currency-dollar"></i>
                        Finanças</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../artists/artists-list"><i class="bi bi-person"></i> Artistas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../youtube"><i class="bi bi-youtube"></i> Unificação de canal
                        YouTube</a>
                </li>
                <!-- Links secundários exibidos apenas em mobile -->
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../../user/profile"><i class="bi bi-person-circle"></i> Meu Perfil</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link active" href="../../page/settings"><i class="bi bi-gear"></i> Configurações</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../../page/notifications"><i class="bi bi-bell"></i> Notificações</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../../page/about"><i class="bi bi-info-circle"></i> Sobre</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../../page/plans"><i class="bi bi-star"></i> Planos</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../../page/help"><i class="bi bi-question-circle"></i> Ajuda</a>
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
    <div class="container my-4">

        <!-- HERO -->
        <div class="privacy-hero">
            <div class="version-badge">
                <i class="bi bi-shield-lock-fill"></i>
                Versão <?php echo PRIVACY_VERSION; ?> · Em vigor desde <?php echo PRIVACY_DATE; ?>
            </div>
            <h1><i class="bi bi-shield-check me-3" style="color:#0d6efd"></i>Política de Privacidade</h1>
            <p>
                A Wasom Upfy trata os teus dados pessoais com responsabilidade, transparência e segurança.
                Este documento explica exactamente quais dados recolhemos, para que os usamos, como os protegemos
                e quais são os teus direitos enquanto titular dos dados.
            </p>
            <div class="hero-meta">
                <span><i class="bi bi-geo-alt-fill" style="color:#0d6efd"></i> Luanda, Angola</span>
                <span><i class="bi bi-calendar3" style="color:#0d6efd"></i> Última actualização:
                    <?php echo PRIVACY_DATE; ?></span>
                <span><i class="bi bi-translate" style="color:#0d6efd"></i> Língua oficial: Português (Angola)</span>
                <span><i class="bi bi-shield-fill-check" style="color:#0d6efd"></i> Versão
                    <?php echo PRIVACY_VERSION; ?></span>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="action-btns">
            <a href="privacy.pdf" download><i class="bi bi-file-earmark-pdf"></i> Descarregar em PDF</a>
            <button id="btnPrint"><i class="bi bi-printer"></i> Imprimir</button>
            <a href="terms"><i class="bi bi-file-text"></i> Termos de Uso</a>
        </div>

        <!-- LAYOUT -->
        <div class="privacy-layout">

            <!-- ÍNDICE -->
            <div class="privacy-index d-none d-lg-block">
                <h3><i class="bi bi-list-ol me-2"></i>Índice</h3>
                <ul>
                    <li><a href="#p1"><span class="num">1.</span>Responsável pelo Tratamento</a></li>
                    <li><a href="#p2"><span class="num">2.</span>Dados que Recolhemos</a></li>
                    <li><a href="#p3"><span class="num">3.</span>Como Recolhemos os Dados</a></li>
                    <li><a href="#p4"><span class="num">4.</span>Finalidades do Tratamento</a></li>
                    <li><a href="#p5"><span class="num">5.</span>Base Legal do Tratamento</a></li>
                    <li><a href="#p6"><span class="num">6.</span>Partilha de Dados com Terceiros</a></li>
                    <li><a href="#p7"><span class="num">7.</span>Transferências Internacionais</a></li>
                    <li><a href="#p8"><span class="num">8.</span>Retenção e Eliminação de Dados</a></li>
                    <li><a href="#p9"><span class="num">9.</span>Segurança dos Dados</a></li>
                    <li><a href="#p10"><span class="num">10.</span>Cookies e Rastreamento</a></li>
                    <li><a href="#p11"><span class="num">11.</span>Notificações Push</a></li>
                    <li><a href="#p12"><span class="num">12.</span>Dados de Menores</a></li>
                    <li><a href="#p13"><span class="num">13.</span>Os Teus Direitos</a></li>
                    <li><a href="#p14"><span class="num">14.</span>Reclamações</a></li>
                    <li><a href="#p15"><span class="num">15.</span>Actualizações desta Política</a></li>
                    <li><a href="#p16"><span class="num">16.</span>Contacto</a></li>
                </ul>
            </div>

            <!-- CONTEÚDO -->
            <div class="privacy-content">

                <!-- ════ 1. RESPONSÁVEL ════ -->
                <div class="priv-section" id="p1">
                    <h2><span class="sec-num">1</span>Responsável pelo Tratamento dos Dados</h2>
                    <p>
                        O responsável pelo tratamento dos dados pessoais recolhidos através da plataforma
                        Wasom Upfy é:
                    </p>
                    <div class="priv-box blue">
                        <strong><i class="bi bi-building me-2"></i>Wasom Upfy</strong>
                        Plataforma digital de distribuição musical e gestão de direitos autorais<br>
                        <i class="bi bi-geo-alt me-1"></i> Luanda, República de Angola<br>
                        <i class="bi bi-envelope me-1"></i> privacidade@wasomupfy.com<br>
                        <i class="bi bi-headset me-1"></i> <a href="support">Pedido de suporte</a> — resposta em até 48h
                        úteis
                    </div>
                    <p>
                        Para quaisquer questões relacionadas com o tratamento dos teus dados pessoais, deves
                        contactar o responsável através dos meios indicados acima ou através da secção
                        <a href="#p16">Contacto</a> no final deste documento.
                    </p>
                </div>

                <!-- ════ 2. DADOS QUE RECOLHEMOS ════ -->
                <div class="priv-section" id="p2">
                    <h2><span class="sec-num">2</span>Dados Pessoais que Recolhemos</h2>
                    <p>
                        A Wasom Upfy recolhe apenas os dados estritamente necessários para a prestação dos
                        serviços contratados. Abaixo encontras uma descrição detalhada dos dados recolhidos,
                        organizados por categoria.
                    </p>

                    <h3>2.1 Dados de Identificação e Conta</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Dado</th>
                                <th>Descrição</th>
                                <th>Obrigatório</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Nome completo</strong></td>
                                <td>Primeiro nome e apelido do titular</td>
                                <td>Sim</td>
                            </tr>
                            <tr>
                                <td><strong>Nome de utilizador</strong></td>
                                <td>Username único na plataforma</td>
                                <td>Sim</td>
                            </tr>
                            <tr>
                                <td><strong>Endereço de e-mail</strong></td>
                                <td>E-mail principal de acesso e notificações</td>
                                <td>Sim</td>
                            </tr>
                            <tr>
                                <td><strong>Palavra-passe</strong></td>
                                <td>Armazenada em formato hash bcrypt — nunca em texto simples</td>
                                <td>Sim</td>
                            </tr>
                            <tr>
                                <td><strong>Género</strong></td>
                                <td>Para personalização da plataforma</td>
                                <td>Opcional</td>
                            </tr>
                            <tr>
                                <td><strong>Data de nascimento</strong></td>
                                <td>Para verificação de elegibilidade (≥18 anos)</td>
                                <td>Sim</td>
                            </tr>
                            <tr>
                                <td><strong>País e cidade</strong></td>
                                <td>Para regionalização de conteúdo e relatórios</td>
                                <td>Sim</td>
                            </tr>
                            <tr>
                                <td><strong>Número de telefone</strong></td>
                                <td>Para verificação de segurança e suporte</td>
                                <td>Opcional</td>
                            </tr>
                            <tr>
                                <td><strong>Fotografia de perfil</strong></td>
                                <td>Imagem opcional do perfil público</td>
                                <td>Opcional</td>
                            </tr>
                            <tr>
                                <td><strong>Biografia</strong></td>
                                <td>Descrição curta do utilizador</td>
                                <td>Opcional</td>
                            </tr>
                        </tbody>
                    </table>

                    <h3>2.2 Dados de Artistas e Conteúdo Musical</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Dado</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Nome artístico</strong></td>
                                <td>Nome de palco do artista ou banda</td>
                            </tr>
                            <tr>
                                <td><strong>Género musical</strong></td>
                                <td>Géneros principal e secundário do artista</td>
                            </tr>
                            <tr>
                                <td><strong>Biografia do artista</strong></td>
                                <td>Texto de apresentação para as plataformas</td>
                            </tr>
                            <tr>
                                <td><strong>Foto e capa do artista</strong></td>
                                <td>Imagens para distribuição e perfil</td>
                            </tr>
                            <tr>
                                <td><strong>Redes sociais</strong></td>
                                <td>URLs opcionais de Instagram, Facebook, Spotify, etc.</td>
                            </tr>
                            <tr>
                                <td><strong>Ficheiros de áudio</strong></td>
                                <td>As faixas musicais submetidas para distribuição</td>
                            </tr>
                            <tr>
                                <td><strong>Metadados dos lançamentos</strong></td>
                                <td>Título, artistas, colaboradores, ISRC, UPC, data de lançamento</td>
                            </tr>
                            <tr>
                                <td><strong>Canal YouTube</strong></td>
                                <td>ID e URL do canal registado para unificação</td>
                            </tr>
                        </tbody>
                    </table>

                    <h3>2.3 Dados Financeiros</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Dado</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Comprovantes de pagamento</strong></td>
                                <td>Ficheiros enviados para activação de planos</td>
                            </tr>
                            <tr>
                                <td><strong>Dados bancários de levantamento</strong></td>
                                <td>IBAN, nome do banco, titular da conta</td>
                            </tr>
                            <tr>
                                <td><strong>Histórico de transacções</strong></td>
                                <td>Pagamentos de planos, royalties recebidos, levantamentos</td>
                            </tr>
                            <tr>
                                <td><strong>Saldo da carteira</strong></td>
                                <td>Valor disponível em conta para levantamento</td>
                            </tr>
                        </tbody>
                    </table>

                    <h3>2.4 Dados Técnicos e de Navegação</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Dado</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Endereço IP</strong></td>
                                <td>Registado no momento do registo e em cada sessão</td>
                            </tr>
                            <tr>
                                <td><strong>Tokens de sessão</strong></td>
                                <td>Para manter a sessão activa e segura</td>
                            </tr>
                            <tr>
                                <td><strong>Token "lembrar-me"</strong></td>
                                <td>Cookie seguro para login automático</td>
                            </tr>
                            <tr>
                                <td><strong>Preferências da plataforma</strong></td>
                                <td>Tema, idioma, densidade de interface, widgets activos</td>
                            </tr>
                            <tr>
                                <td><strong>Preferências de notificação</strong></td>
                                <td>Tipos de notificação activados (push, e-mail, streams, etc.)</td>
                            </tr>
                            <tr>
                                <td><strong>Subscripção push</strong></td>
                                <td>Endpoint e chaves para notificações Web Push (apenas se activado)</td>
                            </tr>
                            <tr>
                                <td><strong>Logs de actividade</strong></td>
                                <td>Acções realizadas na plataforma para auditoria de segurança</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ════ 3. COMO RECOLHEMOS ════ -->
                <div class="priv-section" id="p3">
                    <h2><span class="sec-num">3</span>Como Recolhemos os Dados</h2>
                    <p>Os dados pessoais são recolhidos através dos seguintes mecanismos:</p>
                    <ul>
                        <li><strong>Formulário de registo:</strong> dados fornecidos directamente pelo utilizador no
                            momento da criação da conta;</li>
                        <li><strong>Utilização da plataforma:</strong> dados gerados automaticamente durante a navegação
                            e uso das funcionalidades;</li>
                        <li><strong>Submissão de lançamentos:</strong> metadados, ficheiros de áudio e capas fornecidos
                            ao criar lançamentos;</li>
                        <li><strong>Transacções financeiras:</strong> dados fornecidos ao activar planos, solicitar
                            levantamentos ou registar dados bancários;</li>
                        <li><strong>Comunicações de suporte:</strong> dados fornecidos ao abrir tickets ou enviar
                            mensagens à equipa;</li>
                        <li><strong>Plataformas de distribuição parceiras:</strong> dados de streams, receitas e
                            desempenho fornecidos pelo Spotify, Apple Music, YouTube e outras plataformas parceiras;
                        </li>
                        <li><strong>Cookies e tecnologias similares:</strong> dados de sessão e preferências recolhidos
                            automaticamente durante a utilização.</li>
                    </ul>
                </div>

                <!-- ════ 4. FINALIDADES ════ -->
                <div class="priv-section" id="p4">
                    <h2><span class="sec-num">4</span>Finalidades do Tratamento de Dados</h2>
                    <p>Os dados pessoais recolhidos são utilizados exclusivamente para as seguintes finalidades:</p>

                    <h3>4.1 Prestação dos Serviços Contratados</h3>
                    <ul>
                        <li>Criação, gestão e autenticação da conta do utilizador;</li>
                        <li>Distribuição de lançamentos musicais para plataformas parceiras;</li>
                        <li>Geração de relatórios analíticos de streams, receitas e desempenho;</li>
                        <li>Processamento e distribuição de royalties;</li>
                        <li>Gestão de levantamentos e transacções financeiras;</li>
                        <li>Operação do sistema de divisão de royalties entre colaboradores.</li>
                    </ul>

                    <h3>4.2 Segurança e Prevenção de Fraude</h3>
                    <ul>
                        <li>Detecção e prevenção de acessos não autorizados;</li>
                        <li>Identificação de actividades suspeitas, incluindo manipulação de streams;</li>
                        <li>Verificação de comprovantes de pagamento;</li>
                        <li>Manutenção de logs de auditoria para investigação de incidentes;</li>
                        <li>Bloqueio de IPs e contas associados a actividades fraudulentas.</li>
                    </ul>

                    <h3>4.3 Comunicação com o Utilizador</h3>
                    <ul>
                        <li>Envio de notificações sobre o estado dos lançamentos;</li>
                        <li>Alertas de pagamentos, royalties e levantamentos;</li>
                        <li>Comunicações sobre actualizações da plataforma, manutenções ou novas funcionalidades;</li>
                        <li>Resposta a pedidos de suporte;</li>
                        <li>Notificações push (apenas com consentimento explícito do utilizador).</li>
                    </ul>

                    <h3>4.4 Cumprimento de Obrigações Legais</h3>
                    <ul>
                        <li>Cumprimento de obrigações fiscais e contabilísticas;</li>
                        <li>Resposta a ordens judiciais ou requisições de autoridades competentes;</li>
                        <li>Registo de aceitação dos Termos de Uso para efeitos legais.</li>
                    </ul>

                    <h3>4.5 Melhoria da Plataforma</h3>
                    <ul>
                        <li>Análise agregada e anonimizada do comportamento de utilização para identificar melhorias;
                        </li>
                        <li>Detecção e correcção de erros técnicos.</li>
                    </ul>

                    <div class="priv-box green">
                        <strong><i class="bi bi-check-circle-fill me-2"></i>Compromisso</strong>
                        A Wasom Upfy não utiliza os teus dados para fins publicitários, não os vende a terceiros e não
                        os utiliza para criar perfis de comportamento para fins comerciais externos.
                    </div>
                </div>

                <!-- ════ 5. BASE LEGAL ════ -->
                <div class="priv-section" id="p5">
                    <h2><span class="sec-num">5</span>Base Legal do Tratamento</h2>
                    <p>
                        O tratamento dos dados pessoais pela Wasom Upfy assenta nas seguintes bases legais,
                        em conformidade com a legislação angolana aplicável:
                    </p>
                    <ul>
                        <li><strong>Execução de contrato:</strong> o tratamento é necessário para a prestação dos
                            serviços contratados ao criar uma conta e activar um plano;</li>
                        <li><strong>Consentimento:</strong> para tratamentos opcionais, como notificações push, o
                            tratamento baseia-se no consentimento explícito do utilizador, que pode ser retirado a
                            qualquer momento nas <a href="settings">Configurações</a>;</li>
                        <li><strong>Interesse legítimo:</strong> para fins de segurança, prevenção de fraude e melhoria
                            da plataforma, desde que não prevaleçam sobre os direitos do utilizador;</li>
                        <li><strong>Obrigação legal:</strong> para o cumprimento de obrigações legais, fiscais ou
                            judiciais.</li>
                    </ul>
                </div>

                <!-- ════ 6. PARTILHA COM TERCEIROS ════ -->
                <div class="priv-section" id="p6">
                    <h2><span class="sec-num">6</span>Partilha de Dados com Terceiros</h2>
                    <p>
                        A Wasom Upfy não vende, arrenda nem partilha os teus dados pessoais para fins
                        comerciais. A partilha de dados ocorre apenas nas seguintes situações:
                    </p>

                    <h3>6.1 Plataformas de Distribuição</h3>
                    <p>
                        Para efectuar a distribuição musical, partilhamos os <strong>metadados dos lançamentos</strong>
                        (nome do artista, título, géneros, data de lançamento, ISRC, UPC, capa, ficheiro de áudio)
                        com as plataformas parceiras: Spotify, Apple Music, Deezer, Tidal, YouTube Music, Amazon
                        Music, Boomplay, TikTok, iTunes e outras. Estes dados são os estritamente necessários
                        para disponibilizar a música nas plataformas. A tua morada, número de telefone, dados
                        bancários ou histórico financeiro nunca são partilhados com plataformas de distribuição.
                    </p>

                    <h3>6.2 Prestadores de Serviços Técnicos</h3>
                    <p>
                        Alguns prestadores de serviços técnicos (como serviços de alojamento web, entrega de
                        e-mail transaccional ou processamento de pagamentos) podem processar dados em nosso nome,
                        estando contratualmente obrigados a tratar os dados apenas para as finalidades
                        especificadas e a aplicar medidas de segurança adequadas.
                    </p>

                    <h3>6.3 Autoridades e Exigências Legais</h3>
                    <p>
                        Podemos divulgar dados pessoais quando tal seja exigido por lei, ordem judicial,
                        regulamentação aplicável ou requisição de autoridade competente. Nestes casos, divulgamos
                        apenas os dados estritamente necessários e notificamos o utilizador sempre que legalmente
                        permitido.
                    </p>

                    <h3>6.4 Colaboradores e Co-artistas</h3>
                    <p>
                        Quando configuras a divisão de royalties ou adicionas colaboradores a um lançamento,
                        partilhas voluntariamente determinados dados (nome de utilizador, percentagem de royalties)
                        com esses colaboradores, através da funcionalidade de divisão da plataforma. Esta partilha
                        é controlada inteiramente pelo titular da conta.
                    </p>

                    <div class="priv-box yellow">
                        <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Importante</strong>
                        A Wasom Upfy nunca partilha dados financeiros, palavras-passe, dados bancários ou
                        informações pessoais identificáveis com outras plataformas além das estritamente
                        necessárias para a distribuição musical.
                    </div>
                </div>

                <!-- ════ 7. TRANSFERÊNCIAS INTERNACIONAIS ════ -->
                <div class="priv-section" id="p7">
                    <h2><span class="sec-num">7</span>Transferências Internacionais de Dados</h2>
                    <p>
                        A distribuição musical implica a transmissão de metadados dos teus lançamentos para
                        plataformas sediadas em múltiplos países (Estados Unidos, Reino Unido, União Europeia,
                        entre outros). Estas transferências são:
                    </p>
                    <ul>
                        <li>Necessárias para a execução do contrato de distribuição;</li>
                        <li>Limitadas aos metadados musicais e dados de artista indispensáveis para a publicação;</li>
                        <li>Efectuadas com plataformas que possuem os seus próprios compromissos de protecção de dados,
                            de acordo com os seus Termos de Serviço.</li>
                    </ul>
                    <p>
                        Os teus dados pessoais identificáveis (e-mail, telefone, dados bancários, endereço IP)
                        <strong>não são transferidos para o exterior</strong> e permanecem nos servidores da
                        Wasom Upfy, localizados em Angola ou em servidores de alojamento com localização
                        contratualmente definida.
                    </p>
                </div>

                <!-- ════ 8. RETENÇÃO E ELIMINAÇÃO ════ -->
                <div class="priv-section" id="p8">
                    <h2><span class="sec-num">8</span>Retenção e Eliminação de Dados</h2>
                    <p>
                        Os dados pessoais são conservados pelo período estritamente necessário para as
                        finalidades que motivaram a sua recolha, ou pelo período exigido por lei.
                    </p>

                    <ul class="retention-list">
                        <li>
                            <span class="retention-badge">Conta activa</span>
                            <div>Dados de conta, perfil e preferências — conservados durante toda a vigência da conta.
                            </div>
                        </li>
                        <li>
                            <span class="retention-badge">29 dias</span>
                            <div>Após pedido de encerramento voluntário da conta — período de recuperação antes da
                                eliminação definitiva.</div>
                        </li>
                        <li>
                            <span class="retention-badge">5 anos</span>
                            <div>Registos financeiros (transacções, royalties, levantamentos, comprovantes de pagamento)
                                — por obrigação legal e fiscal.</div>
                        </li>
                        <li>
                            <span class="retention-badge">1 ano</span>
                            <div>Logs de actividade e registos de segurança — para investigação de incidentes e
                                prevenção de fraude.</div>
                        </li>
                        <li>
                            <span class="retention-badge">Imediato</span>
                            <div>Subscripções push — eliminadas imediatamente ao revogar a permissão nas Configurações.
                            </div>
                        </li>
                        <li>
                            <span class="retention-badge">Encerramento</span>
                            <div>Após encerramento por violação dos Termos — dados mínimos retidos para efeitos legais e
                                prevenção de criação de nova conta fraudulenta.</div>
                        </li>
                    </ul>

                    <p>
                        Após o término dos períodos de retenção, os dados são eliminados de forma segura
                        ou anonimizados de modo irreversível.
                    </p>
                </div>

                <!-- ════ 9. SEGURANÇA ════ -->
                <div class="priv-section" id="p9">
                    <h2><span class="sec-num">9</span>Segurança dos Dados</h2>
                    <p>
                        A Wasom Upfy implementa medidas técnicas e organizacionais adequadas para proteger
                        os dados pessoais contra acesso não autorizado, perda, destruição ou divulgação indevida.
                        As medidas incluem:
                    </p>
                    <ul>
                        <li><strong>Hash de palavras-passe:</strong> as palavras-passe são armazenadas exclusivamente em
                            formato hash bcrypt com salt único por utilizador — nunca em texto simples;</li>
                        <li><strong>Sessões seguras:</strong> utilização de tokens de sessão aleatórios com regeneração
                            após autenticação;</li>
                        <li><strong>Token "lembrar-me":</strong> armazenado em hash seguro na base de dados, com
                            validade limitada e revogação imediata no logout;</li>
                        <li><strong>HTTPS obrigatório:</strong> toda a comunicação entre o browser do utilizador e a
                            plataforma é cifrada via TLS;</li>
                        <li><strong>Protecção CSRF:</strong> tokens únicos por formulário para prevenir ataques de
                            falsificação de pedidos entre sites;</li>
                        <li><strong>Controlo de acesso por sessão:</strong> cada página verifica activamente a
                            autenticidade da sessão antes de carregar qualquer dado;</li>
                        <li><strong>Detecção de IPs suspeitos:</strong> monitorização de acessos de localizações não
                            reconhecidas com notificação ao utilizador;</li>
                        <li><strong>Auditoria de actividade:</strong> registo das principais acções na conta para
                            detecção de comportamentos anómalos;</li>
                        <li><strong>Isolamento de dados:</strong> cada utilizador acede exclusivamente aos seus próprios
                            dados — o isolamento é garantido a nível de base de dados e lógica de aplicação;</li>
                        <li><strong>Backups periódicos:</strong> cópias de segurança cifradas da base de dados com
                            política de retenção definida.</li>
                    </ul>
                    <div class="priv-box yellow">
                        <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Limitação</strong>
                        Apesar das medidas implementadas, nenhum sistema é absolutamente inviolável. Em caso de
                        incidente de segurança que afecte os teus dados, serás notificado no prazo de
                        <strong>72 horas</strong> após a detecção, em conformidade com as obrigações legais aplicáveis.
                    </div>
                </div>

                <!-- ════ 10. COOKIES ════ -->
                <div class="priv-section" id="p10">
                    <h2><span class="sec-num">10</span>Cookies e Tecnologias de Rastreamento</h2>
                    <p>A Wasom Upfy utiliza os seguintes tipos de cookies:</p>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Nome</th>
                                <th>Finalidade</th>
                                <th>Duração</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Essencial</strong></td>
                                <td><code>PHPSESSID</code></td>
                                <td>Manter a sessão de login activa e segura</td>
                                <td>Sessão</td>
                            </tr>
                            <tr>
                                <td><strong>Essencial</strong></td>
                                <td><code>remember_token</code></td>
                                <td>Login automático quando "lembrar-me" está activo</td>
                                <td>30 dias</td>
                            </tr>
                            <tr>
                                <td><strong>Funcional</strong></td>
                                <td><code>wasomupfy_theme</code></td>
                                <td>Guardar preferência de tema (escuro/claro)</td>
                                <td>1 ano</td>
                            </tr>
                            <tr>
                                <td><strong>Funcional</strong></td>
                                <td><code>wasomupfy_prefs</code></td>
                                <td>Guardar preferências de interface e notificações</td>
                                <td>1 ano</td>
                            </tr>
                            <tr>
                                <td><strong>Segurança</strong></td>
                                <td><code>csrf_token</code></td>
                                <td>Protecção contra ataques CSRF em formulários</td>
                                <td>Sessão</td>
                            </tr>
                        </tbody>
                    </table>

                    <p>
                        A Wasom Upfy <strong>não utiliza cookies de publicidade ou rastreamento de terceiros</strong>.
                        Não existe integração com redes de publicidade, pixels de rastreamento social ou
                        ferramentas de análise externas que recolham dados identificáveis.
                    </p>
                    <p>
                        Podes gerir as preferências de cookies nas <a href="settings">Configurações</a> da conta.
                        A desactivação dos cookies essenciais impedirá o funcionamento correcto da sessão
                        de login e de algumas funcionalidades da plataforma.
                    </p>
                </div>

                <!-- ════ 11. NOTIFICAÇÕES PUSH ════ -->
                <div class="priv-section" id="p11">
                    <h2><span class="sec-num">11</span>Notificações Push e Service Worker</h2>
                    <p>
                        A Wasom Upfy oferece notificações push — alertas enviados directamente para o teu
                        dispositivo, mesmo quando não estás activamente a usar a plataforma. Esta funcionalidade
                        é <strong>totalmente opcional</strong> e requer o teu consentimento explícito.
                    </p>
                    <h3>11.1 Dados Recolhidos para Push</h3>
                    <ul>
                        <li>Endpoint de subscrição (URL único gerado pelo browser);</li>
                        <li>Chaves criptográficas públicas da subscrição (p256dh e auth);</li>
                        <li>Estes dados não identificam a tua pessoa — identificam apenas o dispositivo/browser.</li>
                    </ul>
                    <h3>11.2 Como Revogar o Consentimento</h3>
                    <p>
                        Podes desactivar as notificações push a qualquer momento em
                        <a href="notifications"><strong>Notificações → Preferências → Notificações push</strong></a>.
                        Após a revogação, a subscrição é eliminada imediatamente dos nossos servidores.
                    </p>
                    <h3>11.3 Service Worker</h3>
                    <p>
                        A plataforma utiliza um Service Worker (<code>sw-wasomupfy.js</code>) que permite o
                        funcionamento parcial da plataforma sem ligação à internet (cache de páginas) e a
                        recepção de notificações push. O Service Worker <strong>não recolhe dados</strong>
                        — serve apenas para cache de recursos e entrega de notificações.
                    </p>
                </div>

                <!-- ════ 12. DADOS DE MENORES ════ -->
                <div class="priv-section" id="p12">
                    <h2><span class="sec-num">12</span>Dados de Menores</h2>
                    <div class="priv-box red">
                        <strong><i class="bi bi-shield-exclamation me-2"></i>Restrição de Idade</strong>
                        A Wasom Upfy é destinada exclusivamente a utilizadores com <strong>18 anos ou mais</strong>.
                        Não recolhemos intencionalmente dados pessoais de menores de idade. Se tomarmos
                        conhecimento de que recolhemos dados de um menor, esses dados serão imediatamente
                        eliminados e a conta encerrada.
                    </div>
                    <p>
                        Se és pai, mãe ou tutor legal e acreditas que o teu filho criou uma conta na Wasom Upfy,
                        contacta-nos imediatamente através de <a href="support">pedido de suporte</a> ou por
                        e-mail para privacidade@wasomupfy.com.
                    </p>
                </div>

                <!-- ════ 13. OS TEUS DIREITOS ════ -->
                <div class="priv-section" id="p13">
                    <h2><span class="sec-num">13</span>Os Teus Direitos como Titular dos Dados</h2>
                    <p>
                        Enquanto titular dos dados pessoais tratados pela Wasom Upfy, tens os seguintes direitos,
                        que podes exercer a qualquer momento:
                    </p>

                    <div class="rights-grid">
                        <div class="right-card">
                            <i class="bi bi-eye"></i>
                            <strong>Direito de Acesso</strong>
                            <span>Saber quais dados temos sobre ti e obter uma cópia.</span>
                        </div>
                        <div class="right-card">
                            <i class="bi bi-pencil-square"></i>
                            <strong>Direito de Rectificação</strong>
                            <span>Corrigir dados incorrectos ou incompletos.</span>
                        </div>
                        <div class="right-card">
                            <i class="bi bi-trash"></i>
                            <strong>Direito ao Apagamento</strong>
                            <span>Solicitar a eliminação dos teus dados pessoais.</span>
                        </div>
                        <div class="right-card">
                            <i class="bi bi-slash-circle"></i>
                            <strong>Direito de Oposição</strong>
                            <span>Opor-te ao tratamento baseado em interesse legítimo.</span>
                        </div>
                        <div class="right-card">
                            <i class="bi bi-pause-circle"></i>
                            <strong>Direito de Limitação</strong>
                            <span>Solicitar a limitação do tratamento em certas circunstâncias.</span>
                        </div>
                        <div class="right-card">
                            <i class="bi bi-download"></i>
                            <strong>Direito de Portabilidade</strong>
                            <span>Receber os teus dados num formato legível por máquina.</span>
                        </div>
                    </div>

                    <h3>Como Exercer os Teus Direitos</h3>
                    <p>
                        Para exercer qualquer um dos direitos acima, podes:
                    </p>
                    <ul>
                        <li>Aceder às <a href="settings"><strong>Configurações</strong></a> da conta para alterar ou
                            eliminar dados de perfil;</li>
                        <li>Abrir um <a href="support"><strong>pedido de suporte</strong></a> descrevendo o direito que
                            desejas exercer;</li>
                        <li>Enviar um e-mail para <strong>privacidade@wasomupfy.com</strong> com o assunto "Exercício de
                            Direitos — [tipo de direito]".</li>
                    </ul>
                    <p>
                        Responderemos ao teu pedido no prazo de <strong>30 dias úteis</strong>. Em casos
                        de complexidade elevada, este prazo pode ser prorrogado por mais 30 dias, com
                        notificação prévia.
                    </p>
                    <div class="priv-box blue">
                        <strong><i class="bi bi-info-circle-fill me-2"></i>Limitações ao Apagamento</strong>
                        O direito ao apagamento pode ser limitado quando os dados sejam necessários para o
                        cumprimento de obrigações legais (como registos fiscais de 5 anos), para a defesa de
                        direitos em litígios pendentes, ou quando o apagamento comprometa a integridade de
                        registos de auditoria de segurança.
                    </div>
                </div>

                <!-- ════ 14. RECLAMAÇÕES ════ -->
                <div class="priv-section" id="p14">
                    <h2><span class="sec-num">14</span>Reclamações e Resolução de Conflitos</h2>
                    <p>
                        Se considerares que o tratamento dos teus dados pessoais viola os teus direitos ou
                        os termos desta Política de Privacidade, tens o direito de apresentar uma reclamação.
                    </p>
                    <h3>14.1 Reclamação Interna</h3>
                    <p>
                        Antes de recorrer a qualquer instância externa, incentivamos a resolução directa
                        através do nosso <a href="support">sistema de suporte</a>. Comprometemo-nos a analisar
                        todas as reclamações de forma séria e a responder no prazo de <strong>15 dias úteis</strong>.
                    </p>
                    <h3>14.2 Autoridade Competente</h3>
                    <p>
                        Caso não fiques satisfeito com a resolução interna, podes apresentar queixa junto
                        da autoridade de supervisão competente em Angola ou, caso aplicável, no teu país
                        de residência. Em Angola, as matérias relacionadas com protecção de dados e privacidade
                        são reguladas no âmbito da legislação de comunicações electrónicas e serviços da
                        sociedade da informação.
                    </p>
                </div>

                <!-- ════ 15. ACTUALIZAÇÕES ════ -->
                <div class="priv-section" id="p15">
                    <h2><span class="sec-num">15</span>Actualizações desta Política de Privacidade</h2>
                    <p>
                        A Wasom Upfy reserva-se o direito de actualizar esta Política de Privacidade para
                        reflectir alterações nos serviços, na legislação aplicável ou nas práticas de
                        tratamento de dados.
                    </p>
                    <p>
                        Em caso de alterações significativas, notificaremos os utilizadores com pelo menos
                        <strong>15 dias de antecedência</strong> através de:
                    </p>
                    <ul>
                        <li>Notificação no painel da plataforma e no centro de notificações;</li>
                        <li>E-mail para o endereço registado na conta;</li>
                        <li>Aviso em destaque no acesso à plataforma.</li>
                    </ul>
                    <p>
                        A data da última actualização está sempre indicada no topo deste documento.
                        O uso contínuo da plataforma após a entrada em vigor da nova versão constitui
                        aceitação das alterações. O histórico de versões anteriores pode ser solicitado
                        através do suporte.
                    </p>
                </div>

                <!-- ════ 16. CONTACTO ════ -->
                <div class="priv-section" id="p16">
                    <h2><span class="sec-num">16</span>Contacto para Assuntos de Privacidade</h2>
                    <p>
                        Para qualquer questão relacionada com esta Política de Privacidade ou com o
                        tratamento dos teus dados pessoais, podes contactar-nos através de:
                    </p>
                    <ul>
                        <li><strong>E-mail dedicado:</strong> privacidade@wasomupfy.com — assunto: "Privacidade —
                            [descrição breve]";</li>
                        <li><strong>Suporte na plataforma:</strong> <a href="support">Enviar pedido de suporte</a> —
                            categoria: Privacidade e Dados;</li>
                        <li><strong>Resposta garantida:</strong> até <strong>30 dias úteis</strong> para questões de
                            privacidade e exercício de direitos.</li>
                    </ul>

                    <div class="priv-box purple" style="margin-top:1.5rem">
                        <strong><i class="bi bi-shield-lock-fill me-2"></i>O teu compromisso com a privacidade</strong>
                        Ao utilizares a plataforma Wasom Upfy, partilhas connosco a responsabilidade de proteger
                        os dados. Mantém as tuas credenciais em segurança, não partilhes a tua palavra-passe e
                        reporta qualquer actividade suspeita na tua conta o mais rapidamente possível.
                        A privacidade é uma responsabilidade partilhada.
                    </div>
                </div>

            </div><!-- /privacy-content -->
        </div><!-- /privacy-layout -->

        <!-- Footer -->
        <div class="text-center mt-4 mb-5" style="font-size:.78rem;color:var(--text-muted,#6c757d)">
            <p>
                <strong>Wasom Upfy</strong> · Política de Privacidade versão <?php echo PRIVACY_VERSION; ?> ·
                Em vigor desde <?php echo PRIVACY_DATE; ?> ·
                <a href="terms" class="text-secondary">Termos de Uso</a>
            </p>
            <p>© <?php echo date('Y'); ?> Wasom Upfy. Todos os direitos reservados.</p>
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
                        class="bi bi-bar-chart"></i><span>Stats</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
                        class="bi bi-currency-dollar"></i><span>Finanças</span></a></li>
            <li class="nav-item"><a class="nav-link active" href="privacy"><i
                        class="bi bi-shield-check"></i><span>Privacidade</span></a></li>
        </ul>
    </nav>

    <!-- Back to top -->
    <button id="backToTop" title="Voltar ao topo"><i class="bi bi-chevron-up"></i></button>

    <!-- Modal Logout -->
    <div class="modal fade" id="logoutwasomupfy" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
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
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ── Barra de progresso + back to top ─────────────
            var fill = document.getElementById('progressBar');
            var backToTop = document.getElementById('backToTop');

            function updateProgress() {
                var scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
                var scrollH = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                var pct = scrollH > 0 ? (scrollTop / scrollH) * 100 : 0;
                if (fill) fill.style.width = pct + '%';
                if (backToTop) backToTop.classList.toggle('visible', scrollTop > 300);
            }
            window.addEventListener('scroll', updateProgress);
            updateProgress();

            // ── Back to top ───────────────────────────────────
            if (backToTop) {
                backToTop.addEventListener('click', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }

            // ── Imprimir ──────────────────────────────────────
            var btnPrint = document.getElementById('btnPrint');
            if (btnPrint) {
                btnPrint.addEventListener('click', function() {
                    window.print();
                });
            }

            // ── Highlight activo do índice ao scroll ─────────
            var sections = document.querySelectorAll('.priv-section');
            var indexLinks = document.querySelectorAll('.privacy-index a');

            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var id = entry.target.getAttribute('id');
                        indexLinks.forEach(function(link) {
                            link.classList.toggle('active', link.getAttribute('href') ===
                                '#' + id);
                        });
                    }
                });
            }, {
                rootMargin: '-20% 0px -70% 0px'
            });

            sections.forEach(function(sec) {
                observer.observe(sec);
            });

        });
    </script>
</body>

</html>