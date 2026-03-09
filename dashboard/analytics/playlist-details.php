<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Detalhes de Playlist (Estatísticas)
// Arquivo: dashboard/analytics/playlist-details.php
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

// ── Parâmetros ────────────────────────────────
// ?playlist= vem como string do nome (vindo de statistics.php)
// Não existe tabela de playlists na DB ainda — a página é future-ready
$playlist_raw  = isset($_GET['playlist']) ? trim($_GET['playlist']) : '';
$filter_year   = isset($_GET['year'])     ? (int)$_GET['year']     : (int)date('Y');
$filter_store  = isset($_GET['store'])    ? (int)$_GET['store']    : 0;

// Sanitizar nome da playlist
$playlist_name = preg_replace('/[^\p{L}0-9 \-\_\.\(\)]/u', '', $playlist_raw);
$playlist_name = mb_substr(trim($playlist_name), 0, 100);

if (!$playlist_name) {
  redirect('dashboard/analytics/statistics#playlist');
}

// ── Lojas activas ─────────────────────────────
$stores_q = $db->prepare("SELECT id_store, name_store, slug_store FROM _store WHERE is_active = 1 ORDER BY display_order ASC");
$stores_q->execute();
$stores    = $stores_q->fetchAll(PDO::FETCH_ASSOC);
$store_map = array_column($stores, null, 'id_store');

// ── Anos disponíveis ──────────────────────────
$years_q = $db->prepare("
    SELECT DISTINCT s.year_stream
    FROM _stream s
    JOIN _track t ON t.id_track = s.id_track
    WHERE t.id_users = ?
    ORDER BY s.year_stream DESC
");
$years_q->execute([$id_users]);
$available_years = $years_q->fetchAll(PDO::FETCH_COLUMN);
if (empty($available_years)) $available_years = [(int)date('Y')];

// ── TOP FAIXAS do utilizador no ano (substituto útil enquanto não há dados de playlist) ──
// Quando existir tabela _playlist_stream, a query muda para filtrar por playlist
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
        a.stage_name,
        a.photo_artist,
        COALESCE(SUM(s.streams), 0)   AS total_streams,
        COALESCE(SUM(s.downloads), 0) AS total_downloads,
        COALESCE(SUM(s.revenue), 0)   AS total_revenue
    FROM _track t
    JOIN _album al ON al.id_album = t.id_album
    LEFT JOIN _artist a  ON a.id_artist  = al.id_artist
    LEFT JOIN _stream s  ON s.id_track   = t.id_track
                         AND s.year_stream = ?
                         " . ($filter_store ? "AND s.id_store = ?" : "") . "
    WHERE t.id_users = ?
      AND t.status_track IN ('active','approved')
      AND al.status_album IN ('approved')
    GROUP BY t.id_track, t.title_track, t.name_author, t.name_author_feat,
             t.explicit, t.duration_seconds,
             al.title_album, al.type_album, al.img_cover, al.release_date,
             a.stage_name, a.photo_artist
    ORDER BY total_streams DESC
    LIMIT 50
");
$pt = [$filter_year];
if ($filter_store) $pt[] = $filter_store;
$pt[] = $id_users;
$tracks_q->execute($pt);
$tracks = $tracks_q->fetchAll(PDO::FETCH_ASSOC);

// ── Totais ────────────────────────────────────
$total_streams_all  = array_sum(array_column($tracks, 'total_streams'));
$total_revenue_all  = array_sum(array_column($tracks, 'total_revenue'));
$total_tracks       = count($tracks);

// ── Streams por plataforma (para barras) ──────
$plat_q = $db->prepare("
    SELECT
        st.name_store, st.slug_store,
        COALESCE(SUM(s.streams), 0) AS total_streams
    FROM _stream s
    JOIN _track t  ON t.id_track  = s.id_track
    JOIN _store st ON st.id_store = s.id_store
    WHERE t.id_users = ? AND s.year_stream = ?
      " . ($filter_store ? "AND s.id_store = ?" : "") . "
    GROUP BY st.id_store, st.name_store, st.slug_store
    ORDER BY total_streams DESC
    LIMIT 8
");
$pp = [$id_users, $filter_year];
if ($filter_store) $pp[] = $filter_store;
$plat_q->execute($pp);
$platforms = $plat_q->fetchAll(PDO::FETCH_ASSOC);

$store_colors = [
  'spotify'       => ['border' => '#1db954', 'bg' => 'rgba(29,185,84,0.4)'],
  'apple-music'   => ['border' => '#fc3c44', 'bg' => 'rgba(252,60,68,0.4)'],
  'amazon-music'  => ['border' => '#00a8e0', 'bg' => 'rgba(0,168,224,0.4)'],
  'deezer'        => ['border' => '#ff0089', 'bg' => 'rgba(255,0,137,0.4)'],
  'tidal'         => ['border' => '#00ffff', 'bg' => 'rgba(0,255,255,0.3)'],
  'youtube-music' => ['border' => '#ff0000', 'bg' => 'rgba(255,0,0,0.4)'],
  'boomplay'      => ['border' => '#f5a623', 'bg' => 'rgba(245,166,35,0.4)'],
  'tiktok'        => ['border' => '#69c9d0', 'bg' => 'rgba(105,201,208,0.4)'],
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
  'default'       => 'bi-music-note-beamed',
];

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
  <title><?php echo htmlspecialchars($playlist_name); ?> — Estatísticas — <?php echo APP_NAME; ?></title>
  <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
  <link rel="stylesheet" href="../../css/dashboard-style.css" />
  <link rel="stylesheet" href="../../css/lastest-style.css" />
  <link rel="stylesheet" href="../../css/playlist-details.css" />
  <style>
    /* ══ Hero playlist ══ */
    .playlist-hero {
      border-radius: 20px;
      overflow: hidden;
      margin-bottom: 28px;
      min-height: 150px;
      background: linear-gradient(135deg, #1a003e 0%, #2d0060 50%, #1a1a2e 100%);
      position: relative;
    }

    .playlist-hero .deco {
      position: absolute;
      inset: 0;
      opacity: .06;
      background: repeating-linear-gradient(45deg, #FF0089 0, #FF0089 1px, transparent 0, transparent 50%);
      background-size: 20px 20px;
    }

    .playlist-hero .hero-body {
      position: relative;
      z-index: 1;
      padding: 28px 28px 24px;
      display: flex;
      align-items: center;
      gap: 20px;
      flex-wrap: wrap;
    }

    .playlist-icon-lg {
      width: 80px;
      height: 80px;
      border-radius: 16px;
      background: linear-gradient(135deg, #FF0089, #a000c8);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.4rem;
      flex-shrink: 0;
      box-shadow: 0 4px 20px rgba(255, 0, 137, .4);
    }

    .playlist-hero-info h2 {
      color: #fff;
      font-weight: 800;
      margin: 0 0 4px;
    }

    .playlist-hero-info .meta {
      color: rgba(255, 255, 255, .6);
      font-size: .82rem;
    }

    .playlist-hero-info .meta span {
      margin-right: 14px;
    }

    /* ══ Cards ══ */
    .stat-hero-card {
      border-radius: 16px;
      padding: 18px 20px;
      border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
      background: var(--card-bg, #fff);
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
      background: var(--card-bg, #fff);
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

    /* ══ Faixas ══ */
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

    .rank-num {
      font-size: .8rem;
      font-weight: 900;
      color: var(--text-muted, #6c757d);
      min-width: 24px;
      text-align: center;
    }

    .rank-num.top3 {
      color: #FF0089;
    }

    /* ══ Aviso ══ */
    .data-notice {
      background: rgba(255, 0, 137, .04);
      border: 1px solid rgba(255, 0, 137, .14);
      border-radius: 14px;
      padding: 14px 18px;
      margin-bottom: 22px;
      display: flex;
      gap: 10px;
      align-items: flex-start;
      font-size: .8rem;
      color: var(--text-muted, #6c757d);
    }

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

  <!-- ═══ NAVBAR ═══ -->
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu">
        <span class="navbar-toggler-icon"><i class="bi bi-list text-white fs-1"></i></span>
      </button>
      <a class="navbar-brand" href="../painel">
        <span class="text-light" style="font-weight:bold;font-family:Arial,sans-serif">WASOM UPFY</span>
      </a>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav m-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i>
              Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i>
              Lançamentos</a></li>
          <li class="nav-item"><a class="nav-link active" href="statistics"><i class="bi bi-bar-chart"></i>
              Estatísticas</a></li>
          <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
                class="bi bi-currency-dollar"></i> Finanças</a></li>
          <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i>
              Artistas</a></li>
          <li class="nav-item"><a class="nav-link" href="../artists/youtube/ucy"><i class="bi bi-youtube"></i>
              YouTube</a></li>
        </ul>
      </div>
      <div class="user-menu d-flex align-items-center">
        <a class="theme-toggle text-white me-2" id="themeToggle"><i class="bi bi-sun" id="themeIcon"></i></a>
        <a href="../page/notifications" class="text-white me-2"><i class="bi bi-bell fs-4"></i></a>
        <a href="#" class="text-white" data-bs-toggle="dropdown"><i class="bi bi-person-circle fs-4"></i></a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="../user/profile">
              <i class="bi bi-person me-2"></i><strong><?php echo $user_artist_name; ?></strong></a>
            <div class="px-3 pb-1 text-muted" style="font-size:.72rem">Conta
              <?php echo str_pad($id_users, 6, '0', STR_PAD_LEFT); ?></div>
          </li>
          <li>
            <hr class="dropdown-divider" />
          </li>
          <li><a class="dropdown-item" href="../user/profile"><i class="bi bi-person me-2"></i> Meu Perfil</a>
          </li>
          <li><a class="dropdown-item" href="../account/manage-account"><i class="bi bi-tools me-2"></i>
              Gestão de Conta</a></li>
          <li>
            <hr class="dropdown-divider" />
          </li>
          <li><a class="dropdown-item" href="../page/settings"><i class="bi bi-gear me-2"></i>
              Configurações</a></li>
          <li><a class="dropdown-item" href="../page/notifications"><i class="bi bi-bell me-2"></i>
              Notificações</a></li>
          <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
              data-bs-target="#logoutwasomupfy">
              <i class="bi bi-box-arrow-right me-2"></i> Desconectar-se</a></li>
          <li>
            <hr class="dropdown-divider" />
          </li>
          <li><a class="dropdown-item" href="../page/support"><i class="bi bi-headset me-2"></i> Suporte</a>
          </li>
          <li><a class="dropdown-item" href="../page/faq"><i class="bi bi-chat-left-text me-2"></i> FAQ</a>
          </li>
          <li><span class="dropdown-item-text" id="versionDropdown"></span></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Offcanvas Mobile -->
  <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title text-light" style="font-weight:bold;font-family:Arial,sans-serif">WASOM UPFY</h5>
      <button type="button" class="btn-close text-white" data-bs-dismiss="offcanvas"><i
          class="bi bi-x-lg"></i></button>
    </div>
    <div class="offcanvas-body">
      <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i>
            Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i>
            Lançamentos</a></li>
        <li class="nav-item"><a class="nav-link active" href="statistics"><i class="bi bi-bar-chart"></i>
            Estatísticas</a></li>
        <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
              class="bi bi-currency-dollar"></i> Finanças</a></li>
        <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i>
            Artistas</a></li>
        <li class="nav-item d-lg-none"><a class="nav-link" href="../user/profile"><i
              class="bi bi-person-circle"></i> Meu Perfil</a></li>
        <li class="nav-item d-lg-none"><a class="nav-link text-danger" href="#" data-bs-toggle="modal"
            data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right"></i> Desconectar-se</a></li>
      </ul>
    </div>
  </div>

  <!-- ═══ MAIN ═══ -->
  <div class="container my-4">

    <!-- ── Hero da playlist ── -->
    <div class="playlist-hero">
      <div class="deco"></div>
      <div class="hero-body">
        <div class="playlist-icon-lg">
          <i class="bi bi-collection-play-fill" style="color:#fff"></i>
        </div>
        <div class="playlist-hero-info">
          <h2><?php echo htmlspecialchars($playlist_name); ?></h2>
          <div class="meta">
            <span><i class="bi bi-music-note me-1"></i><?php echo $total_tracks; ?>
              faixa<?php echo $total_tracks != 1 ? 's' : ''; ?></span>
            <span><i class="bi bi-headphones me-1"></i><?php echo number_format((int)$total_streams_all); ?>
              streams em <?php echo $filter_year; ?></span>
          </div>
        </div>
        <div class="ms-auto d-flex gap-2 flex-wrap align-items-start">
          <a href="statistics#playlist" class="btn btn-sm"
            style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:10px">
            <i class="bi bi-arrow-left me-1"></i>Voltar
          </a>
        </div>
      </div>
    </div>

    <!-- ── Filtros ── -->
    <form method="GET" action="playlist-details">
      <input type="hidden" name="playlist" value="<?php echo htmlspecialchars($playlist_name); ?>" />
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
            <option value="0" <?php echo !$filter_store ? 'selected' : ''; ?>>Todas as plataformas</option>
            <?php foreach ($stores as $st): ?>
              <option value="<?php echo $st['id_store']; ?>"
                <?php echo $st['id_store'] == $filter_store ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($st['name_store']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="ms-auto d-flex align-items-end" style="font-size:.78rem;color:var(--text-muted,#6c757d)">
          <i class="bi bi-info-circle me-1"></i><?php echo $filter_year; ?>
          <?php echo $filter_store && isset($store_map[$filter_store]) ? '— ' . htmlspecialchars($store_map[$filter_store]['name_store']) : ''; ?>
        </div>
      </div>
    </form>

    <!-- ── Aviso datos de playlist ── -->
    <div class="data-notice">
      <i class="bi bi-info-circle-fill mt-1" style="color:#FF0089;flex-shrink:0"></i>
      <div>
        <strong>Dados de playlists por plataforma em breve.</strong> Os streams específicos desta playlist serão
        disponibilizados quando as plataformas enviarem relatórios detalhados de curadoria. Os dados abaixo
        representam o desempenho geral das tuas faixas no catálogo distribuído.
      </div>
    </div>

    <!-- ── Cards de totais ── -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-hero-card">
          <div class="stat-label">Faixas no catálogo</div>
          <div class="stat-value" style="color:#FF0089"><?php echo $total_tracks; ?></div>
          <i class="bi bi-music-note stat-icon"></i>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-hero-card">
          <div class="stat-label">Streams totais</div>
          <div class="stat-value" style="color:#6f42c1"><?php echo number_format((int)$total_streams_all); ?>
          </div>
          <i class="bi bi-headphones stat-icon"></i>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-hero-card">
          <div class="stat-label">Receita (USD)</div>
          <div class="stat-value" style="color:#198754;font-size:1.3rem">
            $<?php echo number_format((float)$total_revenue_all, 2); ?></div>
          <i class="bi bi-currency-dollar stat-icon"></i>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-hero-card">
          <div class="stat-label">Plataformas activas</div>
          <div class="stat-value" style="color:#0d6efd"><?php echo count($platforms); ?></div>
          <i class="bi bi-collection stat-icon"></i>
        </div>
      </div>
    </div>

    <!-- ── Streams por plataforma ── -->
    <?php if (!empty($platforms)): ?>
      <div class="card mb-4" style="border-radius:16px">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-collection me-2 text-pink"></i>Streams por plataforma</h6>
        </div>
        <div class="card-body pt-2">
          <?php
          $max_plat = max(array_column($platforms, 'total_streams') ?: [1]);
          foreach ($platforms as $pd):
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
              <div style="font-size:.78rem;font-weight:700;min-width:90px;text-align:right">
                <?php echo number_format((int)$pd['total_streams']); ?> <span
                  style="font-size:.65rem;font-weight:400;color:var(--text-muted,#6c757d)">streams</span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- ── Top faixas ── -->
    <div class="table-card mb-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">
            <i class="bi bi-music-note-list me-2 text-pink"></i>
            Top faixas — <?php echo $filter_year; ?>
          </h6>
          <span class="badge bg-secondary"><?php echo $total_tracks; ?></span>
        </div>
        <?php if (empty($tracks)): ?>
          <div class="empty-section">
            <div class="icon"><i class="bi bi-music-note"></i></div>
            <div class="small fw-semibold mb-1">Nenhuma faixa activa encontrada.</div>
            <div class="small">As faixas aparecem aqui após aprovação pela equipa Wasom Upfy.</div>
            <a href="../launch/releases" class="btn btn-sm btn-pink mt-3">Ver lançamentos</a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table id="tracksTable" class="table table-striped table-hover mb-0">
              <thead>
                <tr>
                  <th style="width:36px">#</th>
                  <th style="width:52px">Capa</th>
                  <th>Faixa</th>
                  <th>Artista</th>
                  <th>Álbum</th>
                  <th>Duração</th>
                  <th>Streams</th>
                  <th>Receita (USD)</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($tracks as $i => $track):
                  $rank = $i + 1;
                ?>
                  <tr>
                    <td>
                      <span class="rank-num <?php echo $rank <= 3 ? 'top3' : ''; ?>">
                        <?php if ($rank === 1): ?>🥇
                        <?php elseif ($rank === 2): ?>🥈
                        <?php elseif ($rank === 3): ?>🥉
                        <?php else: ?><?php echo $rank; ?>
                      <?php endif; ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($track['img_cover']): ?>
                        <img class="track-cover"
                          src="<?php echo htmlspecialchars($cover_url . $track['img_cover']); ?>"
                          onerror="this.outerHTML='<div class=\'track-cover-placeholder\'>🎵</div>'"
                          alt="" />
                      <?php else: ?><div class="track-cover-placeholder">🎵</div><?php endif; ?>
                    </td>
                    <td>
                      <div class="fw-semibold" style="font-size:.87rem">
                        <?php echo htmlspecialchars($track['title_track']); ?>
                        <?php if ($track['explicit'] === 'YES'): ?>
                          <span class="explicit-badge">E</span>
                        <?php endif; ?>
                      </div>
                      <?php if ($track['name_author_feat']): ?>
                        <div style="font-size:.7rem;color:var(--text-muted,#6c757d)">feat.
                          <?php echo htmlspecialchars($track['name_author_feat']); ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="small">
                      <?php echo htmlspecialchars($track['stage_name'] ?? $track['name_author'] ?? '—'); ?>
                    </td>
                    <td style="font-size:.82rem">
                      <?php echo htmlspecialchars($track['title_album']); ?>
                      <span class="badge bg-light text-muted ms-1"
                        style="font-size:.6rem"><?php echo strtoupper($track['type_album']); ?></span>
                    </td>
                    <td class="small text-muted"><?php echo formatDuration($track['duration_seconds']); ?>
                    </td>
                    <td class="fw-bold" style="color:#FF0089">
                      <?php echo number_format((int)$track['total_streams']); ?></td>
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
          <p>Tens a certeza de que desejas terminar sessão, <strong><?php echo $first_name; ?></strong>?</p>
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
            [6, 'desc']
          ], // streams DESC
          columnDefs: [{
              orderable: false,
              targets: [0, 1]
            },
            {
              type: 'num-fmt',
              targets: [6, 7]
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
  </script>
</body>

</html>

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
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
  <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png" />
  <link rel="apple-touch-startup-image" href="../../assets/img/screenshots/splash.png" />
  <link rel="manifest" href="../manifest.json" />
  <title>Playlist / detalhes — Wasom Upfy</title>
  <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
  <link href="
https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css
" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="../../css/dashboard-style.css" />
  <link rel="stylesheet" href="../../css/lastest-style.css" />
  <link rel="stylesheet" href="../../css/playlist-details.css" />
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
    <div class="playlist-header">
      <h2 id="playlistName">Carregando...</h2>
      <div class="col-auto ms-auto text-end mt-n1">
        <button class="btn btn-back shadow-sm" onclick="window.location.reload()">
          <i class="bi bi-repeat"></i> Actualizar
        </button>
        <button class="btn btn-pink" onclick="window.location='statistics#playlist'">
          <i class="bi bi-arrow-left"></i> Voltar
        </button>
      </div>
    </div>

    <!-- Description -->
    <p class="stats-description">
      Aqui podes ver as estatísticas das músicas reproduzidas nesta playlist
      de acordo com a disponibilidade de registo.
    </p>

    <div class="date-range">
      <div>
        <label>Intervalo de datas</label>
        <div>
          <input type="date" id="startDate" value="2024-10-09" />
          <input type="date" id="endDate" value="2024-12-30" />
        </div>
      </div>
      <button class="btn-apply" onclick="applyDateRange()">Aplicar</button>
      <div class="total-streams">
        Total de streams: <span id="totalStreams">0</span>
      </div>
    </div>

    <div class="filter-group">
      <div class="input-group">
        <i class="bi bi-search"></i>
        <input type="text" id="songSearch" placeholder="Pesquisar música" onkeyup="filterSongs()" />
      </div>
      <div class="input-group">
        <i class="bi bi-filter"></i>
        <input type="number" id="minStreams" placeholder="Streams mínimos" oninput="validateStreamsFilter()" />
      </div>
      <div class="input-group">
        <i class="bi bi-filter"></i>
        <input type="number" id="maxStreams" placeholder="Streams máximos" oninput="validateStreamsFilter()" />
      </div>
      <div class="input-group">
        <i class="bi bi-award"></i>
        <select id="awardsFilter" onchange="filterSongs()">
          <option value="all">Todos</option>
          <option value="with">Com prêmios</option>
          <option value="without">Sem prêmios</option>
        </select>
      </div>
      <div class="input-group">
        <i class="bi bi-sort-down"></i>
        <select id="sortOrder" onchange="filterSongs()">
          <option value="desc">Streams (Decrescente)</option>
          <option value="asc">Streams (Crescente)</option>
        </select>
      </div>
      <button class="btn-apply" id="applyFilters" onclick="filterSongs()" disabled>
        Aplicar
      </button>
    </div>

    <ul class="songs-list" id="songsList"></ul>
  </div>

  <nav class="bottom-nav d-lg-none">
    <ul class="nav justify-content-around">
      <li class="nav-item">
        <a class="nav-link" href="../painel" aria-label="Ir para Dashboard"><i
            class="bi bi-speedometer2"></i><span>Dashboard</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="../launch/releases" aria-label="Ir para Lançamentos"><i
            class="bi bi-disc"></i><span>Lançamentos</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="../analytics/statistics" aria-label="Ir para Estatísticas"><i
            class="bi bi-bar-chart"></i><span>Estatísticas</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="../finances/overview" aria-label="Ir para Finanças"><i
            class="bi bi-currency-dollar"></i><span>Finanças</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="../artists/artists-list" aria-label="Ir para Artistas"><i
            class="bi bi-person"></i><span>Artistas</span></a>
      </li>
    </ul>
  </nav>

  <!-- ════ MODAL — Logout ════ -->
  <div class="modal fade" id="logoutwasomupfy" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="logoutwasomupfyLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5 text-dark" id="logoutwasomupfyLabel">
            Terminar sessão
          </h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="container">
            <div class="row justify-content-center text-center">
              <div class="col-md-12 content-center justify-center text-center">
                <p class="text-center text-dark">
                  @josembengadacosta você tem certeza de que desejas terminar
                  sessão?
                </p>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div>
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
              Não, continuar
            </button>
          </div>
          <div>
            <button class="btn btn-danger" type="button" name="logout_wasomupfy"
              onclick="logout_wasomupfy()">
              Sim, terminar
            </button>
          </div>
          <script type="text/javascript">
            function logout_wasomupfy() {
              window.location = "logout";
            }
          </script>
        </div>
      </div>
    </div>
  </div>
  <!-- ════ MODAL — Logout  FIM ════ -->

  <!-- Bootstrap JS and Popper.js -->
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../js/theme.wp.js"></script>
  <script src="../../js/wp.tools.js"></script>
  <script>
    const tooltipTriggerList = document.querySelectorAll(
      '[data-bs-toggle="tooltip"]'
    );
    const tooltipList = [...tooltipTriggerList].map(
      (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
    );
  </script>
  <script>
    // Dados simulados para cada playlist
    const playlistData = {
      "Mix Spotify": {
        totalStreams: 15000,
        songs: [{
            title: "Song A",
            awards: "Prêmio 27-02-2025",
            streams: 8000,
            cover: "https://via.placeholder.com/40",
          },
          {
            title: "Song B",
            awards: "",
            streams: 5000,
            cover: "https://via.placeholder.com/40",
          },
          {
            title: "Song C",
            awards: "Prêmio 30-01-2025",
            streams: 2000,
            cover: "https://via.placeholder.com/40",
          },
        ],
      },
      "On Repeat": {
        totalStreams: 5000,
        songs: [{
            title: "Song D",
            awards: "Prêmio 15-03-2025",
            streams: 3000,
            cover: "https://via.placeholder.com/40",
          },
          {
            title: "Song E",
            awards: "",
            streams: 1500,
            cover: "https://via.placeholder.com/40",
          },
          {
            title: "Song F",
            awards: "",
            streams: 500,
            cover: "https://via.placeholder.com/40",
          },
        ],
      },
      "Playlist 3": {
        totalStreams: 2000,
        songs: [{
            title: "Song G",
            awards: "",
            streams: 1000,
            cover: "https://via.placeholder.com/40",
          },
          {
            title: "Song H",
            awards: "Prêmio 20-04-2025",
            streams: 800,
            cover: "https://via.placeholder.com/40",
          },
          {
            title: "Song I",
            awards: "",
            streams: 200,
            cover: "https://via.placeholder.com/40",
          },
        ],
      },
    };

    // Carregar dados da playlist com base no parâmetro da URL
    const urlParams = new URLSearchParams(window.location.search);
    const playlist = decodeURIComponent(
      urlParams.get("playlist") || "Mix Spotify"
    ); // Fallback para Mix Spotify
    console.log("Parâmetro playlist:", playlist);

    const currentData = playlistData[playlist] || playlistData["Mix Spotify"];
    console.log("Dados carregados:", currentData);

    const playlistName = document.getElementById("playlistName");
    const totalStreams = document.getElementById("totalStreams");
    const songsList = document.getElementById("songsList");

    playlistName.textContent = playlist;
    totalStreams.textContent = currentData.totalStreams.toLocaleString();

    // Função para carregar músicas
    function loadSongs(songs) {
      songsList.innerHTML = "";
      songs.forEach((song) => {
        const li = document.createElement("li");
        li.innerHTML = `
                    <img src="${song.cover}" alt="${song.title}">
                    <div class="song-info">
                        <div class="song-title">${song.title}</div>
                        <div class="awards">${song.awards || "-"}</div>
                    </div>
                    <div class="song-streams">${song.streams.toLocaleString()} Streams</div>
                `;
        songsList.appendChild(li);
      });
    }

    // Carregar músicas iniciais
    loadSongs(currentData.songs);

    // Aplicar intervalo de datas (placeholder para lógica futura)
    function applyDateRange() {
      const startDate = document.getElementById("startDate").value;
      const endDate = document.getElementById("endDate").value;
      console.log(`Intervalo aplicado: ${startDate} a ${endDate}`);
      filterSongs();
    }

    // Validar filtros de streams
    function validateStreamsFilter() {
      const minStreams = document.getElementById("minStreams").value;
      const maxStreams = document.getElementById("maxStreams").value;
      const applyButton = document.getElementById("applyFilters");
      const isMinValid =
        minStreams === "" || (!isNaN(minStreams) && minStreams >= 0);
      const isMaxValid =
        maxStreams === "" || (!isNaN(maxStreams) && maxStreams >= 0);
      const areBothFilled = minStreams !== "" && maxStreams !== "";
      const isRangeValid = areBothFilled ?
        parseInt(minStreams) <= parseInt(maxStreams) :
        true;
      applyButton.disabled = !(isMinValid && isMaxValid && isRangeValid);
    }

    // Filtrar músicas
    function filterSongs() {
      const searchTerm = document
        .getElementById("songSearch")
        .value.toLowerCase();
      const minStreams =
        parseInt(document.getElementById("minStreams").value) || 0;
      const maxStreams =
        parseInt(document.getElementById("maxStreams").value) ||
        Number.MAX_SAFE_INTEGER;
      const awardsFilter = document.getElementById("awardsFilter").value;
      const sortOrder = document.getElementById("sortOrder").value;

      let filteredSongs = currentData.songs.filter((song) => {
        const matchesSearch = song.title.toLowerCase().includes(searchTerm);
        const matchesStreams =
          song.streams >= minStreams && song.streams <= maxStreams;
        const matchesAwards =
          awardsFilter === "all" ?
          true :
          awardsFilter === "with" ?
          song.awards !== "" :
          song.awards === "";

        return matchesSearch && matchesStreams && matchesAwards;
      });

      filteredSongs.sort((a, b) => {
        return sortOrder === "asc" ?
          a.streams - b.streams :
          b.streams - a.streams;
      });

      const totalFilteredStreams = filteredSongs.reduce(
        (sum, song) => sum + song.streams,
        0
      );
      totalStreams.textContent = totalFilteredStreams.toLocaleString();

      loadSongs(filteredSongs);
    }
  </script>
</body>

</html>