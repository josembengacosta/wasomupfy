<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Unificação de Canal YouTube
// Arquivo: dashboard/artists/youtube/ucy.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];
$user     = getUserById($id_users);
if (!$user) { redirect('authentic/logout'); }

$first_name       = htmlspecialchars($user['first_name'] ?? '');
$user_artist_name = htmlspecialchars($user['name_artist_band'] ?? $user['first_name'] ?? '');

// ── Canais YouTube do utilizador ─────────────
try {
    $ch_q = $db->prepare("
        SELECT yc.id_youtube, yc.channel_id, yc.channel_name, yc.channel_url,
               yc.verified_code, yc.status_youtube, yc.verified_at, yc.creat_youtube,
               a.stage_name AS artist_name, a.photo_artist
        FROM _youtube_channel yc
        LEFT JOIN _artist a ON a.id_artist = yc.id_artist
        WHERE yc.id_users = ?
        ORDER BY yc.creat_youtube DESC
    ");
    $ch_q->execute([$id_users]);
    $channels = $ch_q->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $channels = [];
}

// ── Artistas do utilizador (para associar ao canal) ─
try {
    $art_q = $db->prepare("
        SELECT id_artist, stage_name
        FROM _artist
        WHERE id_users = ? AND status_artist = 'active'
        ORDER BY stage_name ASC
    ");
    $art_q->execute([$id_users]);
    $artists = $art_q->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $artists = [];
}

// ── Contadores ────────────────────────────────
$total_channels  = count($channels);
$verified_count  = count(array_filter($channels, fn($c) => $c['status_youtube'] === 'verified'));
$pending_count   = count(array_filter($channels, fn($c) => $c['status_youtube'] === 'pending'));

// ── Flash ─────────────────────────────────────
$flash = $_SESSION['ucy_flash'] ?? null;
unset($_SESSION['ucy_flash']);

// ── Status helpers ────────────────────────────
$status_cfg = [
    'verified' => ['label'=>'Verificado',  'class'=>'bg-success',            'icon'=>'bi-patch-check-fill'],
    'pending'  => ['label'=>'Pendente',    'class'=>'bg-warning text-dark',   'icon'=>'bi-clock-history'],
    'rejected' => ['label'=>'Rejeitado',   'class'=>'bg-danger',              'icon'=>'bi-x-circle-fill'],
    'removed'  => ['label'=>'Removido',    'class'=>'bg-secondary',           'icon'=>'bi-trash3'],
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
    <link rel="apple-touch-icon" href="../../../assets/img/icones/wasomupfy_fiv_512.png" />
    <link rel="manifest" href="../../manifest.json" />
    <title>YouTube — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../../assets/img/icones/wasomupfy_fiv.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="../../../css/dashboard-style.css" />
    <link rel="stylesheet" href="../../../css/lastest-style.css" />
    <style>
    /* ══ Header ══ */
    .yt-header {
        background: linear-gradient(135deg, #c8000a 0%, #ff0000 50%, #ff4444 100%);
        border-radius: 20px;
        padding: 2.2rem 2.5rem;
        margin-bottom: 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
    }

    .yt-header::after {
        content: '\F62B';
        font-family: 'bootstrap-icons';
        position: absolute;
        right: -20px;
        bottom: -28px;
        font-size: 9rem;
        opacity: .08;
        color: #fff;
    }

    .yt-header .header-badge {
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

    /* ══ Metric cards ══ */
    .yt-metric {
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        border-radius: 14px;
        padding: 1.1rem 1.3rem;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .yt-metric-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    .yt-metric-val {
        font-size: 1.5rem;
        font-weight: 900;
        line-height: 1;
    }

    .yt-metric-lbl {
        font-size: .72rem;
        color: var(--text-muted, #6c757d);
        margin-top: 2px;
    }

    /* ══ Channel card ══ */
    .channel-card {
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        border-radius: 16px;
        padding: 1.4rem;
        margin-bottom: 1rem;
        transition: box-shadow .2s;
    }

    .channel-card:hover {
        box-shadow: 0 6px 20px rgba(255, 0, 0, .1);
    }

    .channel-card.verified {
        border-left: 4px solid #198754;
    }

    .channel-card.pending {
        border-left: 4px solid #ffc107;
    }

    .channel-card.rejected {
        border-left: 4px solid #dc3545;
    }

    .channel-avatar {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ff0000, #c8000a);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.4rem;
        flex-shrink: 0;
    }

    .channel-avatar img {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        object-fit: cover;
    }

    /* ══ Verification code box ══ */
    .verify-code-box {
        background: rgba(255, 193, 7, .08);
        border: 1.5px dashed #ffc107;
        border-radius: 10px;
        padding: .7rem 1rem;
        font-family: monospace;
        font-size: 1.1rem;
        font-weight: 800;
        letter-spacing: 2px;
        color: #856404;
        text-align: center;
    }

    /* ══ Empty state ══ */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        border: 2px dashed var(--border-color, rgba(0, 0, 0, .12));
        border-radius: 18px;
    }

    .empty-state i {
        font-size: 3.5rem;
        color: #ff0000;
        opacity: .5;
    }

    /* ══ Btn YouTube ══ */
    .btn-yt {
        background: linear-gradient(135deg, #ff0000, #c8000a);
        border: none;
        color: #fff;
        border-radius: 10px;
        font-weight: 600;
        padding: .45rem 1.2rem;
        transition: all .2s;
    }

    .btn-yt:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(255, 0, 0, .35);
        color: #fff;
    }

    .btn-yt-outline {
        background: transparent;
        border: 1.5px solid #ff0000;
        color: #ff0000;
        border-radius: 10px;
        font-weight: 600;
        padding: .45rem 1.2rem;
        transition: all .2s;
    }

    .btn-yt-outline:hover {
        background: #ff0000;
        color: #fff;
    }

    /* ══ Section title ══ */
    .section-title {
        font-size: 1.05rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 1.2rem;
        color: #ff0000;
    }

    /* ══ Steps ══ */
    .step-list {
        counter-reset: step;
        list-style: none;
        padding: 0;
    }

    .step-list li {
        counter-increment: step;
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: .7rem 0;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .06));
        font-size: .86rem;
    }

    .step-list li:last-child {
        border-bottom: none;
    }

    .step-list li::before {
        content: counter(step);
        background: #ff0000;
        color: #fff;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
        font-weight: 800;
        flex-shrink: 0;
        margin-top: 1px;
    }

    @media(max-width:768px) {
        .yt-header {
            padding: 1.5rem;
        }
    }
    </style>
</head>

<body>

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
            <a class="navbar-brand" href="../../painel">
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
                        <a class="nav-link" href="ucy"><i class="bi bi-youtube"></i> Unificação de canal YouTube</a>
                    </li>
                </ul>
            </div>

            <!-- User Icon (Right) -->
            <div class="user-menu d-flex align-items-center">
                <!-- Theme Toggle Button -->
                <a class="theme-toggle text-white me-2" id="themeToggle">
                    <i class="bi bi-sun" id="themeIcon"></i>
                </a>
                <a href="../../page/notifications" class="text-white me-2" aria-label="Notificações">
                    <i class="bi bi-bell fs-4"></i>
                    <span class="badge bg-danger">9</span>
                </a>
                <a href="#" class="text-white" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-4"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="../../profile/profile"><i class="bi bi-person me-2"></i>
                            <strong><?php echo $first_name; ?></strong></a>
                        <div class="text-white-50">
                            &nbsp; &nbsp; &nbsp; &nbsp; (Conta <?php echo str_pad($id_users, 6, "0", STR_PAD_LEFT); ?>)
                        </div>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <a class="dropdown-item" href="../../profile/profile"><i class="bi bi-person me-2"></i> Meu
                            Perfil</a>
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
                        <a class="dropdown-item" href="../../page/settings"><i class="bi bi-gear me-2"></i>
                            Configurações</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../../page/notifications"><i class="bi bi-bell me-2"></i>
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
                        <a class="dropdown-item" href="../../page/about"><i class="bi bi-info-circle me-2"></i>
                            Sobre</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../../page/support"><i class="bi bi-headset me-2"></i> Enviar
                            pedido
                            de
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
                    <a class="nav-link" href="ucy"><i class="bi bi-youtube"></i> Unificação de canal YouTube</a>
                </li>
                <!-- Links secundários exibidos apenas em mobile -->
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../../profile/profile"><i class="bi bi-person-circle"></i> Meu Perfil</a>
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
                    <a class="nav-link" href="../../services/available-services"><i class="bi bi-star"></i> Conta e
                        serviços
                        disponíveis</a>
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
    <main class="container my-4">

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show mb-3"
            role="alert" style="border-radius:12px">
            <i
                class="bi bi-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo $flash['msg']; /* Permitido: pode conter <strong> e <code> do processo */ ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- ══ HEADER ══ -->
        <div class="yt-header">
            <div class="header-badge">
                <i class="bi bi-youtube"></i>
                <?php echo $verified_count; ?> canal<?php echo $verified_count !== 1 ? 'is' : ''; ?>
                verificado<?php echo $verified_count !== 1 ? 's' : ''; ?>
                <?php if ($pending_count > 0): ?>
                &nbsp;·&nbsp; <?php echo $pending_count; ?> pendente<?php echo $pending_count !== 1 ? 's' : ''; ?>
                <?php endif; ?>
            </div>
            <h1 class="fw-bold mb-1"><i class="bi bi-youtube me-2"></i>Unificação de Canal YouTube</h1>
            <p class="lead mb-0" style="opacity:.85">
                Regista e gere os teus canais do YouTube. Após verificação, a equipa Wasom Upfy activa a monetização e
                sincronização de royalties.
            </p>
        </div>

        <!-- ══ MÉTRICAS ══ -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="yt-metric">
                    <div class="yt-metric-icon" style="background:rgba(255,0,0,.1);color:#ff0000">
                        <i class="bi bi-youtube"></i>
                    </div>
                    <div>
                        <div class="yt-metric-val"><?php echo $total_channels; ?></div>
                        <div class="yt-metric-lbl">Canais registados</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="yt-metric">
                    <div class="yt-metric-icon" style="background:rgba(25,135,84,.1);color:#198754">
                        <i class="bi bi-patch-check-fill"></i>
                    </div>
                    <div>
                        <div class="yt-metric-val"><?php echo $verified_count; ?></div>
                        <div class="yt-metric-lbl">Verificados</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="yt-metric">
                    <div class="yt-metric-icon" style="background:rgba(255,193,7,.1);color:#856404">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div class="yt-metric-val"><?php echo $pending_count; ?></div>
                        <div class="yt-metric-lbl">A aguardar verificação</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">

            <!-- ══ COLUNA PRINCIPAL ══ -->
            <div class="col-lg-8">

                <!-- ── Canais registados ── -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0"><i class="bi bi-collection-play"></i>Os teus canais</div>
                    <button class="btn-yt btn btn-sm" data-bs-toggle="modal" data-bs-target="#modalConnectChannel">
                        <i class="bi bi-plus-circle me-1"></i>Registar canal
                    </button>
                </div>

                <?php if (empty($channels)): ?>
                <div class="empty-state mb-4">
                    <i class="bi bi-youtube d-block mb-3"></i>
                    <h5 class="fw-bold">Nenhum canal registado</h5>
                    <p class="text-muted small mb-3">Regista o teu canal do YouTube para começar a monetizar através do
                        Wasom Upfy.</p>
                    <button class="btn-yt btn" data-bs-toggle="modal" data-bs-target="#modalConnectChannel">
                        <i class="bi bi-plus-circle me-2"></i>Registar o primeiro canal
                    </button>
                </div>

                <?php else: ?>
                <?php foreach ($channels as $ch):
                $st   = $ch['status_youtube'];
                $cfg  = $status_cfg[$st] ?? ['label'=>ucfirst($st),'class'=>'bg-secondary','icon'=>'bi-question'];
                $yt_url = $ch['channel_url'] ?: ('https://youtube.com/channel/' . $ch['channel_id']);
            ?>
                <div class="channel-card <?php echo $st; ?>">
                    <div class="d-flex align-items-start gap-3">
                        <!-- Avatar -->
                        <div class="channel-avatar">
                            <i class="bi bi-youtube"></i>
                        </div>

                        <!-- Info -->
                        <div style="flex:1;min-width:0">
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                <h5 class="fw-bold mb-0 text-truncate">
                                    <?php echo htmlspecialchars($ch['channel_name']); ?></h5>
                                <span class="badge <?php echo $cfg['class']; ?>" style="font-size:.7rem">
                                    <i class="bi <?php echo $cfg['icon']; ?> me-1"></i><?php echo $cfg['label']; ?>
                                </span>
                            </div>

                            <div class="text-muted" style="font-size:.78rem;margin-bottom:.5rem">
                                <i class="bi bi-hash"></i> <?php echo htmlspecialchars($ch['channel_id']); ?>
                                <?php if ($ch['artist_name']): ?>
                                &nbsp;·&nbsp; <i class="bi bi-person"></i>
                                <?php echo htmlspecialchars($ch['artist_name']); ?>
                                <?php endif; ?>
                                &nbsp;·&nbsp; <i class="bi bi-calendar3"></i>
                                Registado em <?php echo date('d/m/Y', strtotime($ch['creat_youtube'])); ?>
                            </div>

                            <?php if ($st === 'verified' && $ch['verified_at']): ?>
                            <div style="font-size:.76rem;color:#198754">
                                <i class="bi bi-patch-check-fill me-1"></i>Verificado em
                                <?php echo date('d/m/Y', strtotime($ch['verified_at'])); ?>
                            </div>
                            <?php endif; ?>

                            <?php if ($st === 'pending' && $ch['verified_code']): ?>
                            <div class="mt-2">
                                <p class="small text-muted mb-1">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Adiciona este código à descrição do teu canal no YouTube e aguarda a verificação da
                                    equipa:
                                </p>
                                <div class="verify-code-box">
                                    WASOM-<?php echo htmlspecialchars($ch['verified_code']); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($st === 'rejected'): ?>
                            <div class="mt-2 alert alert-danger py-2 mb-0" style="border-radius:9px;font-size:.78rem">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Canal rejeitado. Contacta o <a href="../../page/support" class="alert-link">suporte</a>
                                para mais informações.
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Acções -->
                        <div class="d-flex flex-column gap-2 flex-shrink-0">
                            <?php if ($ch['channel_url'] || $ch['channel_id']): ?>
                            <a href="<?php echo htmlspecialchars($yt_url); ?>" target="_blank"
                                class="btn btn-sm btn-outline-secondary"
                                style="border-radius:8px;font-size:.75rem;white-space:nowrap">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Abrir
                            </a>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-danger" style="border-radius:8px;font-size:.75rem"
                                onclick="confirmRemove(<?php echo $ch['id_youtube']; ?>, '<?php echo addslashes($ch['channel_name']); ?>')">
                                <i class="bi bi-trash3 me-1"></i>Remover
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

            </div><!-- /col-lg-8 -->

            <!-- ══ SIDEBAR ══ -->
            <div class="col-lg-4">

                <!-- Como funciona -->
                <div class="card mb-3"
                    style="border-radius:16px;border:1.5px solid var(--border-color,rgba(0,0,0,.08))">
                    <div class="card-header"
                        style="background:transparent;border-bottom:1px solid var(--border-color,rgba(0,0,0,.07));padding:1rem 1.3rem;border-radius:16px 16px 0 0">
                        <h5 class="mb-0 fw-700" style="font-size:.95rem"><i class="bi bi-info-circle me-2"
                                style="color:#ff0000"></i>Como funciona</h5>
                    </div>
                    <div class="card-body" style="padding:1.2rem">
                        <ol class="step-list">
                            <li>Clica em <strong>Registar canal</strong> e introduz o ID e o nome do teu canal do
                                YouTube.</li>
                            <li>Receberás um <strong>código de verificação</strong> único.</li>
                            <li>Adiciona o código à <strong>descrição do canal</strong> no YouTube.</li>
                            <li>A equipa Wasom Upfy <strong>verifica</strong> e activa a monetização em até 48h.</li>
                            <li>Após verificação, os royalties do YouTube são <strong>sincronizados
                                    automaticamente</strong>.</li>
                        </ol>
                    </div>
                </div>

                <!-- Artistas com YouTube -->
                <?php if (!empty($artists)): ?>
                <div class="card mb-3"
                    style="border-radius:16px;border:1.5px solid var(--border-color,rgba(0,0,0,.08))">
                    <div class="card-header"
                        style="background:transparent;border-bottom:1px solid var(--border-color,rgba(0,0,0,.07));padding:1rem 1.3rem;border-radius:16px 16px 0 0">
                        <h5 class="mb-0 fw-700" style="font-size:.95rem"><i class="bi bi-person me-2"
                                style="color:#ff0000"></i>Os teus artistas</h5>
                    </div>
                    <div class="card-body p-2">
                        <?php foreach ($artists as $art): ?>
                        <div class="d-flex align-items-center gap-2 px-2 py-2"
                            style="border-bottom:1px solid var(--border-color,rgba(0,0,0,.06))">
                            <i class="bi bi-person-circle" style="color:#ff0000;font-size:1.1rem"></i>
                            <span
                                style="font-size:.85rem;font-weight:600"><?php echo htmlspecialchars($art['stage_name']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Suporte -->
                <div class="card" style="border-radius:16px;border:1.5px solid var(--border-color,rgba(0,0,0,.08))">
                    <div class="card-body text-center" style="padding:1.4rem">
                        <i class="bi bi-headset" style="font-size:2rem;color:#ff0000"></i>
                        <h6 class="fw-bold mt-2 mb-1">Precisas de ajuda?</h6>
                        <p class="text-muted small mb-3">A equipa Wasom Upfy está disponível para ajudar com a
                            verificação do canal.</p>
                        <a href="../../page/support" class="btn-yt btn w-100 btn-sm">
                            <i class="bi bi-headset me-1"></i>Contactar suporte
                        </a>
                    </div>
                </div>

            </div><!-- /sidebar -->

        </div><!-- /row -->
    </main>

    <!-- Bottom Nav Mobile -->
    <nav class="bottom-nav d-lg-none">
        <ul class="nav justify-content-around">
            <li class="nav-item"><a class="nav-link" href="../../painel"><i
                        class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../../launch/releases"><i
                        class="bi bi-disc"></i><span>Lançamentos</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../../analytics/statistics"><i
                        class="bi bi-bar-chart"></i><span>Stats</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../../finances/overview"><i
                        class="bi bi-currency-dollar"></i><span>Finanças</span></a></li>
            <li class="nav-item"><a class="nav-link active" href="ucy"><i
                        class="bi bi-youtube"></i><span>YouTube</span></a></li>
        </ul>
    </nav>

    <!-- Modal Registar Canal -->
    <div class="modal fade" id="modalConnectChannel" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="border-bottom:1px solid var(--border-color,rgba(0,0,0,.1))">
                    <h5 class="modal-title fw-bold"><i class="bi bi-youtube text-danger me-2"></i>Registar Canal YouTube
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="ucy_process" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                        <input type="hidden" name="action" value="connect_channel" />

                        <div class="mb-3">
                            <label for="channel_id" class="form-label fw-semibold">ID do Canal <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="channel_id" name="channel_id"
                                placeholder="Ex: UCxxxxxxxxxxxxxxxxxxxx" required
                                style="border-radius:10px;font-family:monospace" />
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Encontras o ID em: <strong>YouTube Studio → Definições → Informações do canal → ID do
                                    canal</strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="channel_name" class="form-label fw-semibold">Nome do Canal <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="channel_name" name="channel_name"
                                placeholder="Ex: The Last Official" required style="border-radius:10px" />
                        </div>

                        <div class="mb-3">
                            <label for="channel_url" class="form-label fw-semibold">URL do Canal</label>
                            <input type="url" class="form-control" id="channel_url" name="channel_url"
                                placeholder="https://youtube.com/@seucanal" style="border-radius:10px" />
                        </div>

                        <?php if (!empty($artists)): ?>
                        <div class="mb-3">
                            <label for="id_artist" class="form-label fw-semibold">Associar a um artista</label>
                            <select class="form-select" id="id_artist" name="id_artist" style="border-radius:10px">
                                <option value="">— Selecciona (opcional) —</option>
                                <?php foreach ($artists as $art): ?>
                                <option value="<?php echo $art['id_artist']; ?>">
                                    <?php echo htmlspecialchars($art['stage_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="alert alert-warning py-2 mb-0" style="border-radius:10px;font-size:.8rem">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Após o registo, receberás um <strong>código de verificação</strong> que deverás adicionar à
                            descrição do canal no YouTube.
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--border-color,rgba(0,0,0,.1))">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-yt btn">
                            <i class="bi bi-plus-circle me-2"></i>Registar Canal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Remoção -->
    <div class="modal fade" id="modalRemoveChannel" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-danger">
                    <h5 class="modal-title text-danger fw-bold"><i class="bi bi-trash3 me-2"></i>Remover Canal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tens a certeza de que desejas remover o canal <strong id="removeChannelName"></strong>?</p>
                    <div class="alert alert-danger py-2 mb-0" style="border-radius:10px;font-size:.8rem">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Esta acção remove o registo do canal. Para voltar a activar, terás de o registar novamente.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="ucy_process" method="POST" id="removeChannelForm">
                        <input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                        <input type="hidden" name="action" value="remove_channel" />
                        <input type="hidden" name="id_youtube" id="removeChannelId" />
                        <button type="submit" class="btn btn-danger" style="border-radius:9px">Sim, remover</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
                    <a href="../../logout" class="btn btn-danger">Sim, terminar sessão</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../js/theme.wp.js"></script>
    <script src="../../../js/wp.tools.js"></script>
    <script>
    // Tema
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    (function() {
        const saved = localStorage.getItem('theme') || 'dark';
        document.body.classList.toggle('dark-mode', saved === 'dark');
        document.body.classList.toggle('light-mode', saved !== 'dark');
        themeIcon.className = saved === 'dark' ? 'bi bi-moon' : 'bi bi-sun';
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

    // Confirmar remoção de canal
    function confirmRemove(id, name) {
        document.getElementById('removeChannelId').value = id;
        document.getElementById('removeChannelName').textContent = name;
        new bootstrap.Modal(document.getElementById('modalRemoveChannel')).show();
    }

    // Auto-dismiss alert após 6s
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.querySelector('.alert-dismissible');
        if (alert) setTimeout(() => bootstrap.Alert.getOrCreateInstance(alert)?.close(), 6000);
    });
    </script>
</body>

</html>