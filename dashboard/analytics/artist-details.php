<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Detalhes do Artista (Estatísticas)
// Arquivo: dashboard/analytics/artist-details.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];
$user     = getUserById($id_users);
if (!$user) {
  redirect('../logout');
}

$first_name       = htmlspecialchars($user['first_name']);
$user_artist_name = htmlspecialchars($user['name_artist_band'] ?? $user['first_name']);

// ── Parâmetros da URL ─────────────────────────
$id_artist   = isset($_GET['artist']) ? (int)$_GET['artist'] : 0;
$filter_year = isset($_GET['year'])   ? (int)$_GET['year']   : (int)date('Y');
$filter_store = isset($_GET['store']) ? (int)$_GET['store']  : 0; // 0 = todos

if (!$id_artist) {
  redirect('dashboard/analytics/statistics#artist');
}

// ── Validar que o artista pertence ao utilizador ──
$artist_q = $db->prepare("
    SELECT id_artist, stage_name, real_name, photo_artist, cover_artist,
           bio, genre_main, genre_secondary, country, city,
           instagram_url, spotify_url, youtube_url
    FROM _artist
    WHERE id_artist = ? AND id_users = ? AND status_artist != 'blocked'
");
$artist_q->execute([$id_artist, $id_users]);
$artist = $artist_q->fetch();

if (!$artist) {
  // Artista não encontrado ou não pertence ao utilizador
  redirect('dashboard/analytics/statistics#artist');
}

// ── Anos disponíveis ──────────────────────────
$years_q = $db->prepare("
    SELECT DISTINCT s.year_stream
    FROM _stream s
    JOIN _track t  ON t.id_track  = s.id_track
    JOIN _album al ON al.id_album = t.id_album
    WHERE al.id_artist = ? AND al.id_users = ?
    ORDER BY s.year_stream DESC
");
$years_q->execute([$id_artist, $id_users]);
$available_years = $years_q->fetchAll(PDO::FETCH_COLUMN);
if (empty($available_years)) $available_years = [(int)date('Y')];

// ── Lojas activas ─────────────────────────────
$stores_q = $db->prepare("SELECT id_store, name_store, slug_store FROM _store WHERE is_active = 1 ORDER BY display_order ASC");
$stores_q->execute();
$stores    = $stores_q->fetchAll(PDO::FETCH_ASSOC);
$store_map = array_column($stores, null, 'id_store');

// ── TOTAIS do artista no ano ──────────────────
$totals_q = $db->prepare("
    SELECT
        COALESCE(SUM(s.streams), 0)   AS total_streams,
        COALESCE(SUM(s.downloads), 0) AS total_downloads,
        COALESCE(SUM(s.revenue), 0)   AS total_revenue,
        COUNT(DISTINCT t.id_track)    AS total_tracks
    FROM _stream s
    JOIN _track t  ON t.id_track  = s.id_track
    JOIN _album al ON al.id_album = t.id_album
    WHERE al.id_artist = ? AND al.id_users = ?
      AND s.year_stream = ?
      " . ($filter_store ? "AND s.id_store = ?" : "") . "
");
$p = [$id_artist, $id_users, $filter_year];
if ($filter_store) $p[] = $filter_store;
$totals_q->execute($p);
$totals = $totals_q->fetch();

// ── STREAMS POR PLATAFORMA ────────────────────
$platforms_q = $db->prepare("
    SELECT
        st.id_store,
        st.name_store,
        st.slug_store,
        COALESCE(SUM(s.streams), 0)  AS total_streams,
        COALESCE(SUM(s.revenue), 0)  AS total_revenue
    FROM _stream s
    JOIN _track t  ON t.id_track  = s.id_track
    JOIN _album al ON al.id_album = t.id_album
    JOIN _store st ON st.id_store = s.id_store
    WHERE al.id_artist = ? AND al.id_users = ? AND s.year_stream = ?
    GROUP BY st.id_store, st.name_store, st.slug_store
    ORDER BY total_streams DESC
");
$platforms_q->execute([$id_artist, $id_users, $filter_year]);
$platforms_data = $platforms_q->fetchAll(PDO::FETCH_ASSOC);

// ── STREAMS POR MÊS + PLATAFORMA (gráfico) ───
$chart_q = $db->prepare("
    SELECT
        s.month_stream,
        s.id_store,
        st.name_store,
        st.slug_store,
        COALESCE(SUM(s.streams), 0) AS streams
    FROM _stream s
    JOIN _track t  ON t.id_track  = s.id_track
    JOIN _album al ON al.id_album = t.id_album
    JOIN _store st ON st.id_store = s.id_store
    WHERE al.id_artist = ? AND al.id_users = ? AND s.year_stream = ?
      " . ($filter_store ? "AND s.id_store = ?" : "") . "
    GROUP BY s.month_stream, s.id_store, st.name_store, st.slug_store
    ORDER BY s.month_stream ASC, st.display_order ASC
");
$pc = [$id_artist, $id_users, $filter_year];
if ($filter_store) $pc[] = $filter_store;
$chart_q->execute($pc);
$chart_raw = $chart_q->fetchAll(PDO::FETCH_ASSOC);

// Organizar para Chart.js
$store_colors = [
  'spotify'       => ['border' => '#1db954', 'bg' => 'rgba(29,185,84,0.4)'],
  'apple-music'   => ['border' => '#fc3c44', 'bg' => 'rgba(252,60,68,0.4)'],
  'amazon-music'  => ['border' => '#00a8e0', 'bg' => 'rgba(0,168,224,0.4)'],
  'deezer'        => ['border' => '#ff0089', 'bg' => 'rgba(255,0,137,0.4)'],
  'tidal'         => ['border' => '#00ffff', 'bg' => 'rgba(0,255,255,0.3)'],
  'youtube-music' => ['border' => '#ff0000', 'bg' => 'rgba(255,0,0,0.4)'],
  'boomplay'      => ['border' => '#f5a623', 'bg' => 'rgba(245,166,35,0.4)'],
  'tiktok'        => ['border' => '#69c9d0', 'bg' => 'rgba(105,201,208,0.4)'],
  'itunes'        => ['border' => '#c864c8', 'bg' => 'rgba(200,100,200,0.4)'],
  'default'       => ['border' => '#aaa',   'bg' => 'rgba(170,170,170,0.3)'],
];

$store_icons = [
  'spotify'       => 'bi-spotify',
  'apple-music'   => 'bi-apple',
  'amazon-music'  => 'bi-music-note-beamed',
  'deezer'        => 'bi-music-player',
  'tidal'         => 'bi-water',
  'youtube-music' => 'bi-youtube',
  'boomplay'      => 'bi-soundwave',
  'tiktok'        => 'bi-tiktok',
  'itunes'        => 'bi-music-note',
  'default'       => 'bi-music-note-beamed',
];

$chart_stores   = [];
$chart_by_store = [];
foreach ($chart_raw as $row) {
  $mid = (int)$row['month_stream'];
  $sid = (int)$row['id_store'];
  if (!isset($chart_stores[$sid])) {
    $chart_stores[$sid] = ['name' => $row['name_store'], 'slug' => $row['slug_store']];
    $chart_by_store[$sid] = array_fill(1, 12, 0);
  }
  $chart_by_store[$sid][$mid] = (int)$row['streams'];
}

$chart_datasets = [];
foreach ($chart_stores as $sid => $sinfo) {
  $slug   = $sinfo['slug'];
  $colors = $store_colors[$slug] ?? $store_colors['default'];
  $chart_datasets[] = [
    'label'           => $sinfo['name'],
    'data'            => array_values($chart_by_store[$sid]),
    'borderColor'     => $colors['border'],
    'backgroundColor' => $colors['bg'],
    'fill'            => true,
    'stack'           => 'combined',
    'tension'         => 0.3,
  ];
}

$months_pt_short = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

// ── FAIXAS do artista com totais de streams ───
$tracks_q = $db->prepare("
    SELECT
        t.id_track,
        t.title_track,
        t.name_author,
        t.name_author_feat,
        t.explicit,
        t.duration_seconds,
        al.title_album,
        al.type_album,
        al.img_cover,
        al.release_date,
        COALESCE(SUM(s.streams), 0)   AS total_streams,
        COALESCE(SUM(s.downloads), 0) AS total_downloads,
        COALESCE(SUM(s.revenue), 0)   AS total_revenue
    FROM _track t
    JOIN _album al ON al.id_album = t.id_album
    LEFT JOIN _stream s ON s.id_track = t.id_track
        AND s.year_stream = ?
        " . ($filter_store ? "AND s.id_store = ?" : "") . "
    WHERE al.id_artist = ? AND al.id_users = ?
      AND t.status_track IN ('active','approved')
    GROUP BY t.id_track, t.title_track, t.name_author, t.name_author_feat,
             t.explicit, t.duration_seconds,
             al.title_album, al.type_album, al.img_cover, al.release_date
    ORDER BY total_streams DESC
");
$pt = [$filter_year];
if ($filter_store) $pt[] = $filter_store;
$pt[] = $id_artist;
$pt[] = $id_users;
$tracks_q->execute($pt);
$tracks = $tracks_q->fetchAll(PDO::FETCH_ASSOC);

// Helper: formatar duração
function formatDuration(?int $sec): string
{
  if (!$sec) return '—';
  return gmdate($sec >= 3600 ? 'H:i:s' : 'i:s', $sec);
}

$base_url  = rtrim(APP_URL, '/');
$cover_url = $base_url . '/assets/comprovantes/uploads/covers/';
$photo_url = $base_url . '/assets/comprovantes/uploads/artists/';
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
    <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png" />
    <link rel="manifest" href="../manifest.json" />
    <title><?php echo htmlspecialchars($artist['stage_name']); ?> — Estatísticas — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="../../css/dashboard-style.css" />
    <link rel="stylesheet" href="../../css/lastest-style.css" />
    <link rel="stylesheet" href="../../css/artist-list.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
    /* ══ Hero do artista ══ */
    .artist-hero {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 28px;
        min-height: 180px;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
    }

    .artist-hero .hero-cover {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: center;
        opacity: .22;
        filter: blur(2px);
    }

    .artist-hero .hero-body {
        position: relative;
        z-index: 1;
        padding: 28px 28px 24px;
        display: flex;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
    }

    .artist-photo {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(255, 0, 137, .6);
        background: rgba(255, 0, 137, .1);
        flex-shrink: 0;
    }

    .artist-photo-placeholder {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 3px solid rgba(255, 0, 137, .4);
        background: rgba(255, 0, 137, .08);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.8rem;
        flex-shrink: 0;
    }

    .artist-hero-info h2 {
        color: #fff;
        font-weight: 800;
        margin: 0 0 4px;
    }

    .artist-hero-info .meta {
        color: rgba(255, 255, 255, .6);
        font-size: .82rem;
    }

    .artist-hero-info .meta span {
        margin-right: 14px;
    }

    /* ══ Stat cards ══ */
    .stat-hero-card {
        border-radius: 16px;
        padding: 18px 20px;
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        position: relative;
        overflow: hidden;
    }

    .stat-hero-card .stat-label {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-muted, #6c757d);
        margin-bottom: 5px;
    }

    .stat-hero-card .stat-value {
        font-size: 1.65rem;
        font-weight: 900;
        line-height: 1;
    }

    .stat-hero-card .stat-icon {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 2.6rem;
        opacity: .07;
    }

    /* ══ Filtros ══ */
    .filter-bar {
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        border-radius: 14px;
        padding: 14px 18px;
        margin-bottom: 22px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
    }

    .filter-bar label {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-muted, #6c757d);
        display: block;
        margin-bottom: 3px;
    }

    /* ══ Plataformas ══ */
    .platform-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .06));
    }

    .platform-row:last-child {
        border-bottom: none;
    }

    .platform-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .platform-bar-bg {
        flex: 1;
        height: 6px;
        border-radius: 6px;
        background: var(--border-color, rgba(0, 0, 0, .07));
        overflow: hidden;
    }

    .platform-bar-fill {
        height: 100%;
        border-radius: 6px;
        transition: width .5s ease;
    }

    /* ══ Tabela de faixas ══ */
    .track-cover {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
    }

    .track-cover-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: rgba(255, 0, 137, .08);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .explicit-badge {
        font-size: .6rem;
        background: #333;
        color: #fff;
        border-radius: 3px;
        padding: 1px 4px;
        vertical-align: middle;
        margin-left: 4px;
    }

    .feat-text {
        font-size: .72rem;
        color: var(--text-muted, #6c757d);
    }

    /* ══ Social links ══ */
    .social-links a {
        font-size: 1.3rem;
        color: var(--text-muted, #6c757d);
        margin-right: 10px;
        transition: color .2s;
    }

    .social-links a:hover {
        color: #FF0089;
    }

    /* ══ Empty ══ */
    .empty-section {
        text-align: center;
        padding: 36px 20px;
        color: var(--text-muted, #6c757d);
    }

    .empty-section .icon {
        font-size: 2.2rem;
        opacity: .15;
        margin-bottom: 8px;
    }
    </style>
</head>

<body>

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

    <!-- Offcanvas Menu for Mobile -->
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
            </ul>
            <div class="version-info">Versão 2.1 (2026)</div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-4">

        <!-- ═══ MAIN ═══ -->
        <div class="container my-4">

            <!-- ── Hero do artista ── -->
            <div class="artist-hero">
                <?php if ($artist['cover_artist']): ?>
                <div class="hero-cover"
                    style="background-image:url('<?php echo htmlspecialchars($photo_url . $artist['cover_artist']); ?>')">
                </div>
                <?php endif; ?>
                <div class="hero-body">
                    <?php if ($artist['photo_artist']): ?>
                    <img class="artist-photo"
                        src="<?php echo htmlspecialchars($photo_url . $artist['photo_artist']); ?>"
                        onerror="this.outerHTML='<div class=\'artist-photo-placeholder\'>🎤</div>'"
                        alt="<?php echo htmlspecialchars($artist['stage_name']); ?>" />
                    <?php else: ?>
                    <div class="artist-photo-placeholder">🎤</div>
                    <?php endif; ?>
                    <div class="artist-hero-info">
                        <h2><?php echo htmlspecialchars($artist['stage_name']); ?></h2>
                        <div class="meta">
                            <?php if ($artist['genre_main']): ?>
                            <span><i
                                    class="bi bi-music-note me-1"></i><?php echo htmlspecialchars($artist['genre_main']); ?><?php if ($artist['genre_secondary']): ?>
                                /
                                <?php echo htmlspecialchars($artist['genre_secondary']);
                                                                                                            endif; ?></span>
                            <?php endif; ?>
                            <?php if ($artist['country']): ?>
                            <span><i
                                    class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($artist['country']); ?><?php if ($artist['city']): ?>,
                                <?php echo htmlspecialchars($artist['city']);
                                                                                                      endif; ?></span>
                            <?php endif; ?>
                            <span><i class="bi bi-disc me-1"></i><?php echo (int)$totals['total_tracks']; ?>
                                faixa<?php echo $totals['total_tracks'] != 1 ? 's' : ''; ?></span>
                        </div>
                        <?php if ($artist['instagram_url'] || $artist['spotify_url'] || $artist['youtube_url']): ?>
                        <div class="social-links mt-2">
                            <?php if ($artist['instagram_url']): ?>
                            <a href="<?php echo htmlspecialchars($artist['instagram_url']); ?>" target="_blank"
                                rel="noopener" data-bs-toggle="tooltip" title="Instagram"><i
                                    class="bi bi-instagram"></i></a>
                            <?php endif; ?>
                            <?php if ($artist['spotify_url']): ?>
                            <a href="<?php echo htmlspecialchars($artist['spotify_url']); ?>" target="_blank"
                                rel="noopener" data-bs-toggle="tooltip" title="Spotify"><i
                                    class="bi bi-spotify"></i></a>
                            <?php endif; ?>
                            <?php if ($artist['youtube_url']): ?>
                            <a href="<?php echo htmlspecialchars($artist['youtube_url']); ?>" target="_blank"
                                rel="noopener" data-bs-toggle="tooltip" title="YouTube"><i
                                    class="bi bi-youtube"></i></a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="ms-auto d-flex gap-2 flex-wrap align-items-start">
                        <a href="statistics#artist" class="btn btn-sm"
                            style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:10px">
                            <i class="bi bi-arrow-left me-1"></i>Voltar
                        </a>
                        <a href="artist-details?artist=<?php echo $id_artist; ?>&year=<?php echo $filter_year; ?><?php echo $filter_store ? '&store=' . $filter_store : ''; ?>"
                            class="btn btn-sm"
                            style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:10px">
                            <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
                        </a>
                    </div>
                </div>
            </div>

            <!-- ── Barra de filtros ── -->
            <form method="GET" action="artist-details">
                <input type="hidden" name="artist" value="<?php echo $id_artist; ?>" />
                <div class="filter-bar">
                    <div>
                        <label>Ano</label>
                        <select name="year" class="form-select form-select-sm" style="min-width:100px"
                            onchange="this.form.submit()">
                            <?php foreach ($available_years as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $filter_year ? 'selected' : ''; ?>>
                                <?php echo $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Plataforma</label>
                        <select name="store" class="form-select form-select-sm" style="min-width:160px"
                            onchange="this.form.submit()">
                            <option value="0" <?php echo !$filter_store ? 'selected' : ''; ?>>Todas as plataformas
                            </option>
                            <?php foreach ($stores as $st): ?>
                            <option value="<?php echo $st['id_store']; ?>"
                                <?php echo $st['id_store'] == $filter_store ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($st['name_store']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ms-auto d-flex align-items-end"
                        style="font-size:.78rem;color:var(--text-muted,#6c757d)">
                        <i class="bi bi-info-circle me-1"></i>
                        <?php echo $filter_year; ?>
                        <?php echo $filter_store && isset($store_map[$filter_store]) ? '— ' . htmlspecialchars($store_map[$filter_store]['name_store']) : '— Todas as plataformas'; ?>
                    </div>
                </div>
            </form>

            <!-- ── Cards de totais ── -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-hero-card">
                        <div class="stat-label">Streams</div>
                        <div class="stat-value" style="color:#FF0089">
                            <?php echo number_format((int)$totals['total_streams']); ?></div>
                        <i class="bi bi-headphones stat-icon"></i>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-hero-card">
                        <div class="stat-label">Downloads</div>
                        <div class="stat-value" style="color:#0d6efd">
                            <?php echo number_format((int)$totals['total_downloads']); ?></div>
                        <i class="bi bi-download stat-icon"></i>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-hero-card">
                        <div class="stat-label">Receita (Kz)</div>
                        <div class="stat-value" style="color:#198754;font-size:1.3rem">
                            Kz<?php echo number_format((float)$totals['total_revenue'], 2); ?></div>
                        <i class="bi bi-currency-dollar stat-icon"></i>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-hero-card">
                        <div class="stat-label">Faixas activas</div>
                        <div class="stat-value" style="color:#6c757d"><?php echo (int)$totals['total_tracks']; ?></div>
                        <i class="bi bi-disc stat-icon"></i>
                    </div>
                </div>
            </div>

            <!-- ── Gráfico streams por mês ── -->
            <div class="chart-card mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-graph-up me-2 text-pink"></i>Streams por mês —
                            <?php echo $filter_year; ?></h6>
                    </div>
                    <?php if (empty($chart_datasets)): ?>
                    <div class="empty-section">
                        <div class="icon"><i class="bi bi-bar-chart"></i></div>
                        <div class="small fw-semibold mb-1">Sem dados de streams para <?php echo $filter_year; ?>.</div>
                        <div class="small">Os streams são importados mensalmente após entrega dos relatórios pelas
                            plataformas.</div>
                    </div>
                    <?php else: ?>
                    <div class="p-3">
                        <canvas id="streamChart" style="max-height:300px"></canvas>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Plataformas ── -->
            <?php if (!empty($platforms_data)): ?>
            <div class="card mb-4" style="border-radius:16px">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-collection me-2 text-pink"></i>Streams por plataforma</h6>
                </div>
                <div class="card-body pt-2">
                    <?php
            $max_plat = max(array_column($platforms_data, 'total_streams') ?: [1]);
            foreach ($platforms_data as $pd):
              $slug   = $pd['slug_store'];
              $colors = $store_colors[$slug] ?? $store_colors['default'];
              $icon   = $store_icons[$slug]  ?? $store_icons['default'];
              $pct    = $max_plat > 0 ? round(($pd['total_streams'] / $max_plat) * 100) : 0;
            ?>
                    <div class="platform-row">
                        <div class="platform-dot" style="background:<?php echo $colors['border']; ?>"></div>
                        <i class="<?php echo $icon; ?>"
                            style="font-size:1rem;color:<?php echo $colors['border']; ?>;min-width:20px"></i>
                        <div style="min-width:130px;font-size:.82rem;font-weight:600">
                            <?php echo htmlspecialchars($pd['name_store']); ?></div>
                        <div class="platform-bar-bg">
                            <div class="platform-bar-fill"
                                style="width:<?php echo $pct; ?>%;background:<?php echo $colors['border']; ?>"></div>
                        </div>
                        <div style="font-size:.78rem;font-weight:700;min-width:80px;text-align:right">
                            <?php echo number_format((int)$pd['total_streams']); ?>
                            <span style="font-size:.65rem;font-weight:400;color:var(--text-muted,#6c757d)">
                                streams</span>
                        </div>
                        <div style="font-size:.72rem;color:var(--text-muted,#6c757d);min-width:70px;text-align:right">
                            $<?php echo number_format((float)$pd['total_revenue'], 2); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Tabela de faixas ── -->
            <div class="table-card mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-music-note-list me-2 text-pink"></i>Faixas</h6>
                        <span class="badge bg-secondary"><?php echo count($tracks); ?></span>
                    </div>
                    <?php if (empty($tracks)): ?>
                    <div class="empty-section">
                        <div class="icon"><i class="bi bi-music-note"></i></div>
                        <div class="small fw-semibold mb-1">Nenhuma faixa activa encontrada.</div>
                        <div class="small">As faixas aparecem aqui após aprovação pela equipa Wasom Upfy.</div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table id="tracksTable" class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width:52px">Capa</th>
                                    <th>Faixa</th>
                                    <th>Álbum</th>
                                    <th>Duração</th>
                                    <th>Streams <?php echo $filter_year; ?></th>
                                    <th>Downloads</th>
                                    <th>Receita (Kz)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tracks as $track): ?>
                                <tr>
                                    <td>
                                        <?php if ($track['img_cover']): ?>
                                        <img class="track-cover"
                                            src="<?php echo htmlspecialchars($cover_url . $track['img_cover']); ?>"
                                            onerror="this.outerHTML='<div class=\'track-cover-placeholder\'>🎵</div>'"
                                            alt="" />
                                        <?php else: ?>
                                        <div class="track-cover-placeholder">🎵</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold" style="font-size:.87rem">
                                            <?php echo htmlspecialchars($track['title_track']); ?>
                                            <?php if ($track['explicit'] === 'YES'): ?>
                                            <span class="explicit-badge">E</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($track['name_author']): ?>
                                        <div class="feat-text">
                                            <?php echo htmlspecialchars($track['name_author']); ?><?php if ($track['name_author_feat']): ?>
                                            feat. <?php echo htmlspecialchars($track['name_author_feat']);
                                                                                  endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:.82rem">
                                        <?php echo htmlspecialchars($track['title_album']); ?>
                                        <span class="badge bg-light text-muted ms-1"
                                            style="font-size:.6rem"><?php echo strtoupper($track['type_album']); ?></span>
                                    </td>
                                    <td class="small text-muted">
                                        <?php echo formatDuration($track['duration_seconds']); ?>
                                    </td>
                                    <td class="fw-bold" style="color:#FF0089">
                                        <?php echo number_format((int)$track['total_streams']); ?></td>
                                    <td class="small"><?php echo number_format((int)$track['total_downloads']); ?></td>
                                    <td class="small fw-semibold" style="color:#198754">
                                        $<?php echo number_format((float)$track['total_revenue'], 4); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Bio (se existir) ── -->
            <?php if ($artist['bio']): ?>
            <div class="card mb-4" style="border-radius:16px">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-person-lines-fill me-2 text-pink"></i>Biografia</h6>
                </div>
                <div class="card-body" style="font-size:.87rem;line-height:1.7;white-space:pre-line">
                    <?php echo nl2br(htmlspecialchars($artist['bio'])); ?>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /container -->

        <!-- Bottom Nav Mobile -->
        <nav class="bottom-nav d-lg-none">
            <ul class="nav justify-content-around">
                <li class="nav-item"><a class="nav-link" href="../painel"><i
                            class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
                <li class="nav-item"><a class="nav-link" href="../launch/releases"><i
                            class="bi bi-disc"></i><span>Lançamentos</span></a></li>
                <li class="nav-item"><a class="nav-link active" href="statistics"><i
                            class="bi bi-bar-chart"></i><span>Estatísticas</span></a></li>
                <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
                            class="bi bi-currency-dollar"></i><span>Finanças</span></a></li>
                <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i
                            class="bi bi-person"></i><span>Artistas</span></a></li>
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
                        <p>Tens a certeza de que desejas terminar sessão, <strong><?php echo $first_name; ?></strong>?
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Não, continuar</button>
                        <a href="../logout" class="btn btn-danger">Sim, terminar sessão</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ JS ═══ -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        <script src="../../js/theme.wp.js"></script>
        <script src="../../js/wp.tools.js"></script>
        <script>
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

        <?php if (!empty($tracks)): ?>
        $(document).ready(function() {
            $('#tracksTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                lengthChange: false,
                pageLength: 10,
                order: [
                    [4, 'desc']
                ], // ordenar por streams DESC
                columnDefs: [{
                        orderable: false,
                        targets: [0]
                    },
                    {
                        type: 'num-fmt',
                        targets: [4, 5, 6]
                    }
                ],
                language: {
                    search: 'Pesquisar faixa:',
                    info: 'A mostrar _START_ a _END_ de _TOTAL_ faixas',
                    paginate: {
                        next: 'Próximo',
                        previous: 'Anterior'
                    },
                    emptyTable: 'Nenhuma faixa encontrada.'
                }
            });
        });
        <?php endif; ?>

        <?php if (!empty($chart_datasets)): ?>
        const ctx = document.getElementById('streamChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months_pt_short); ?>,
                datasets: <?php echo json_encode($chart_datasets); ?>
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Streams'
                        }
                    },
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Mês'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
        <?php endif; ?>
        </script>
</body>

</html>