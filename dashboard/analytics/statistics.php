<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Estatísticas
// Arquivo: dashboard/analytics/statistics.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];
$user     = getUserById($id_users);
if (!$user) { redirect('authentic/logout'); }

$first_name       = htmlspecialchars($user['first_name']);
$user_artist_name = htmlspecialchars($user['name_artist_band'] ?? $user['first_name']);

// ── Filtros da query string ────────────────────
$filter_year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$filter_store = isset($_GET['store']) ? (int)$_GET['store'] : 0; // 0 = todos

// ── Lojas activas ──────────────────────────────
$stores_q = $db->prepare("SELECT id_store, name_store, slug_store FROM _store WHERE is_active = 1 ORDER BY display_order ASC");
$stores_q->execute();
$stores = $stores_q->fetchAll(PDO::FETCH_ASSOC);
$store_map = array_column($stores, null, 'id_store');

// ── Anos disponíveis (para o selector) ────────
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

// ── TOTAIS GLOBAIS do ano ─────────────────────
$totals_q = $db->prepare("
    SELECT
        COALESCE(SUM(s.streams), 0)   AS total_streams,
        COALESCE(SUM(s.downloads), 0) AS total_downloads,
        COALESCE(SUM(s.revenue), 0)   AS total_revenue
    FROM _stream s
    JOIN _track t ON t.id_track = s.id_track
    WHERE t.id_users = ?
      AND s.year_stream = ?
      " . ($filter_store ? "AND s.id_store = ?" : "") . "
");
$params_totals = [$id_users, $filter_year];
if ($filter_store) $params_totals[] = $filter_store;
$totals_q->execute($params_totals);
$totals = $totals_q->fetch();

// ── STREAMS POR PLATAFORMA (tabela de plataformas) ──
$platforms_q = $db->prepare("
    SELECT
        s.id_store,
        st.name_store,
        st.slug_store,
        COALESCE(SUM(s.streams), 0)   AS total_streams,
        COALESCE(SUM(s.downloads), 0) AS total_downloads,
        COALESCE(SUM(s.revenue), 0)   AS total_revenue
    FROM _stream s
    JOIN _track t  ON t.id_track   = s.id_track
    JOIN _store st ON st.id_store  = s.id_store
    WHERE t.id_users = ?
      AND s.year_stream = ?
    GROUP BY s.id_store, st.name_store, st.slug_store
    ORDER BY total_streams DESC
");
$platforms_q->execute([$id_users, $filter_year]);
$platforms_data = $platforms_q->fetchAll(PDO::FETCH_ASSOC);

// ── STREAMS POR MÊS + PLATAFORMA (dados para o gráfico) ──
$chart_q = $db->prepare("
    SELECT
        s.month_stream,
        s.id_store,
        st.name_store,
        st.slug_store,
        COALESCE(SUM(s.streams), 0) AS streams
    FROM _stream s
    JOIN _track t  ON t.id_track  = s.id_track
    JOIN _store st ON st.id_store = s.id_store
    WHERE t.id_users = ?
      AND s.year_stream = ?
      " . ($filter_store ? "AND s.id_store = ?" : "") . "
    GROUP BY s.month_stream, s.id_store, st.name_store, st.slug_store
    ORDER BY s.month_stream ASC, st.display_order ASC
");
$params_chart = [$id_users, $filter_year];
if ($filter_store) $params_chart[] = $filter_store;
$chart_q->execute($params_chart);
$chart_raw = $chart_q->fetchAll(PDO::FETCH_ASSOC);

// Organizar dados do gráfico por plataforma e mês
$chart_stores = [];
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

// Paleta de cores por slug
$store_colors = [
    'spotify'       => ['border'=>'#1db954','bg'=>'rgba(29,185,84,0.45)'],
    'apple-music'   => ['border'=>'#fc3c44','bg'=>'rgba(252,60,68,0.45)'],
    'amazon-music'  => ['border'=>'#00a8e0','bg'=>'rgba(0,168,224,0.45)'],
    'deezer'        => ['border'=>'#ff0089','bg'=>'rgba(255,0,137,0.45)'],
    'tidal'         => ['border'=>'#00ffff','bg'=>'rgba(0,255,255,0.3)'],
    'youtube-music' => ['border'=>'#ff0000','bg'=>'rgba(255,0,0,0.4)'],
    'boomplay'      => ['border'=>'#f5a623','bg'=>'rgba(245,166,35,0.4)'],
    'tiktok'        => ['border'=>'#69c9d0','bg'=>'rgba(105,201,208,0.4)'],
    'default'       => ['border'=>'#aaa','bg'=>'rgba(170,170,170,0.3)'],
];

// Construir datasets JSON para Chart.js
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

// Labels dos 12 meses
$months_pt_short = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

// ── TOP ARTISTAS por streams ───────────────────
$artists_q = $db->prepare("
    SELECT
        a.id_artist,
        a.stage_name,
        a.photo_artist,
        COALESCE(SUM(s.streams), 0) AS total_streams
    FROM _stream s
    JOIN _track t  ON t.id_track   = s.id_track
    JOIN _artist a ON a.id_artist  = t.id_users  -- NOTA: id_artist não está em _track
    WHERE t.id_users = ?
      AND s.year_stream = ?
    GROUP BY a.id_artist, a.stage_name, a.photo_artist
    ORDER BY total_streams DESC
    LIMIT 20
");
// CORRECÇÃO: _track não tem id_artist, streams são por track/user, artistas são os do utilizador
// Query correcta: artistas do utilizador + somar streams das suas faixas
$artists_q = $db->prepare("
    SELECT
        a.id_artist,
        a.stage_name,
        a.photo_artist,
        COALESCE(SUM(s.streams), 0) AS total_streams
    FROM _artist a
    JOIN _track t  ON t.id_users   = a.id_users AND a.id_users = ?
    JOIN _stream s ON s.id_track   = t.id_track AND s.year_stream = ?
    WHERE a.id_users = ?
    GROUP BY a.id_artist, a.stage_name, a.photo_artist
    ORDER BY total_streams DESC
    LIMIT 20
");
$artists_q->execute([$id_users, $filter_year, $id_users]);
$artists_data = $artists_q->fetchAll(PDO::FETCH_ASSOC);

// Fallback: artistas sem streams também aparecem
if (empty($artists_data)) {
    $art_fallback = $db->prepare("SELECT id_artist, stage_name, photo_artist FROM _artist WHERE id_users = ? AND status_artist != 'blocked' LIMIT 10");
    $art_fallback->execute([$id_users]);
    $artists_data = $art_fallback->fetchAll(PDO::FETCH_ASSOC);
    foreach ($artists_data as &$a) $a['total_streams'] = 0;
    unset($a);
}

$base_url      = rtrim(APP_URL, '/');
$cover_artists = $base_url . '/assets/comprovantes/uploads/artists/';
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
    <title>Estatísticas — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../../css/dashboard-style.css" />
    <link rel="stylesheet" href="../../css/lastest-style.css" />
    <link rel="stylesheet" href="../../css/statistics.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
    /* ── Stat cards ── */
    .stat-hero-card {
        border-radius: 18px;
        padding: 20px 24px;
        position: relative;
        overflow: hidden;
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        transition: box-shadow .2s;
    }

    .stat-hero-card:hover {
        box-shadow: 0 4px 24px rgba(255, 0, 137, .09);
    }

    .stat-hero-card .stat-label {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-muted, #6c757d);
        margin-bottom: 6px;
    }

    .stat-hero-card .stat-value {
        font-size: 1.8rem;
        font-weight: 900;
        line-height: 1;
    }

    .stat-hero-card .stat-sub {
        font-size: .75rem;
        color: var(--text-muted, #6c757d);
        margin-top: 4px;
    }

    .stat-hero-card .stat-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 3rem;
        opacity: .07;
    }

    /* ── Filtros ── */
    .filter-bar {
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        border-radius: 16px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: flex-end;
    }

    .filter-bar label {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-muted, #6c757d);
        display: block;
        margin-bottom: 4px;
    }

    .filter-bar select,
    .filter-bar input {
        font-size: .85rem;
        border-radius: 10px;
    }

    /* ── Plataformas list ── */
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

    .platform-streams {
        font-size: .78rem;
        font-weight: 700;
        min-width: 80px;
        text-align: right;
    }

    /* ── Artists table ── */
    .artist-row-img {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        object-fit: cover;
        background: rgba(255, 0, 137, .08);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .artist-row-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* ── Empty state ── */
    .empty-section {
        text-align: center;
        padding: 40px 20px;
        color: var(--text-muted, #6c757d);
    }

    .empty-section .icon {
        font-size: 2.5rem;
        opacity: .15;
        margin-bottom: 10px;
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
                        <div class="px-3 pb-1 text-muted" style="font-size:.72rem">
                            Conta <?php echo str_pad($id_users, 6, '0', STR_PAD_LEFT); ?>
                        </div>
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
                <li class="nav-item d-lg-none"><a class="nav-link" href="../page/settings"><i class="bi bi-gear"></i>
                        Configurações</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link text-danger" href="#" data-bs-toggle="modal"
                        data-bs-target="#logoutwasomupfy">
                        <i class="bi bi-box-arrow-right"></i> Desconectar-se</a></li>
            </ul>
        </div>
    </div>

    <!-- ═══ MAIN ═══ -->
    <div class="container my-4">

        <!-- Cabeçalho -->
        <div class="page-header">
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <div class="page-header-compact">
                        <h1><i class="bi bi-bar-chart-fill me-3"></i>Estatísticas</h1>
                        <p class="lead">
                            Stream Analytics — streams totais no Spotify, Apple Music, Deezer, Amazon, YouTube e muito
                            mais.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button type="button" class="btn btn-secondary me-2" data-bs-toggle="modal"
                        data-bs-target="#modalExport">
                        <i class="bi bi-download"></i> Exportar
                    </button>
                    <a href="compare" class="btn btn-pink">
                        <i class="bi bi-calendar-range"></i> Comparar
                    </a>
                </div>
            </div>
        </div>

        <!-- ── Barra de filtros ── -->
        <form method="GET" action="statistics">
            <div class="filter-bar">
                <div>
                    <label>Ano</label>
                    <select name="year" class="form-select form-select-sm" style="min-width:100px"
                        onchange="this.form.submit()">
                        <?php foreach ($available_years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $filter_year ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
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
                            <?php echo htmlspecialchars($st['name_store']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ms-auto d-flex align-items-end gap-2"
                    style="font-size:.78rem;color:var(--text-muted,#6c757d)">
                    <i class="bi bi-info-circle"></i>
                    A mostrar dados de <strong><?php echo $filter_year; ?></strong>
                    <?php if ($filter_store && isset($store_map[$filter_store])): ?>
                    — <?php echo htmlspecialchars($store_map[$filter_store]['name_store']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- ── Cards de totais ── -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-hero-card">
                    <div class="stat-label">Total de Streams</div>
                    <div class="stat-value" style="color:#FF0089">
                        <?php echo number_format((int)$totals['total_streams']); ?></div>
                    <div class="stat-sub">em <?php echo $filter_year; ?></div>
                    <i class="bi bi-headphones stat-icon"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-hero-card">
                    <div class="stat-label">Downloads</div>
                    <div class="stat-value" style="color:#0d6efd">
                        <?php echo number_format((int)$totals['total_downloads']); ?></div>
                    <div class="stat-sub">em <?php echo $filter_year; ?></div>
                    <i class="bi bi-download stat-icon"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-hero-card">
                    <div class="stat-label">Receita Bruta</div>
                    <div class="stat-value" style="color:#198754">
                        Kz<?php echo number_format((float)$totals['total_revenue'], 2); ?></div>
                    <div class="stat-sub">AO em <?php echo $filter_year; ?></div>
                    <i class="bi bi-currency-dollar stat-icon"></i>
                </div>
            </div>
        </div>

        <!-- ── Gráfico de streams ── -->
        <div class="chart-card platforms-card mb-4">
            <div class="card">
                <?php if (empty($chart_datasets)): ?>
                <div class="empty-section">
                    <div class="icon"><i class="bi bi-bar-chart"></i></div>
                    <div class="small fw-semibold mb-1">Sem dados de streams para <?php echo $filter_year; ?>.</div>
                    <div class="small">Os streams aparecem aqui após a importação mensal de dados pelas plataformas.
                    </div>
                </div>
                <?php else: ?>
                <canvas id="streamChart" style="max-height:320px"></canvas>
                <hr style="opacity:.07;margin:16px 0" />
                <!-- Lista de plataformas com barras -->
                <?php
            $max_streams = max(array_column($platforms_data, 'total_streams') ?: [1]);
            foreach ($platforms_data as $pd):
                $slug   = $pd['slug_store'];
                $colors = $store_colors[$slug] ?? $store_colors['default'];
                $pct    = $max_streams > 0 ? round(($pd['total_streams'] / $max_streams) * 100) : 0;
            ?>
                <div class="platform-row">
                    <div class="platform-dot" style="background:<?php echo $colors['border']; ?>"></div>
                    <div style="min-width:120px;font-size:.82rem;font-weight:600">
                        <?php echo htmlspecialchars($pd['name_store']); ?></div>
                    <div class="platform-bar-bg">
                        <div class="platform-bar-fill"
                            style="width:<?php echo $pct; ?>%;background:<?php echo $colors['border']; ?>"></div>
                    </div>
                    <div class="platform-streams"><?php echo number_format((int)$pd['total_streams']); ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Artistas ── -->
        <div class="table-card mb-4">
            <div class="card" id="artist">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Artistas</h6>
                    <span class="badge bg-secondary"><?php echo count($artists_data); ?></span>
                </div>
                <?php if (empty($artists_data)): ?>
                <div class="empty-section">
                    <div class="icon"><i class="bi bi-person"></i></div>
                    <div class="small">Nenhum artista encontrado.</div>
                    <a href="../artists/add-artist" class="btn btn-sm btn-pink mt-3">Adicionar artista</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" id="artistsTable">
                        <thead>
                            <tr>
                                <th style="width:52px">Foto</th>
                                <th>Artista</th>
                                <th>Streams <?php echo $filter_year; ?></th>
                                <th class="text-center" style="width:60px">Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($artists_data as $art): ?>
                            <tr>
                                <td>
                                    <div class="artist-row-img">
                                        <?php if ($art['photo_artist']): ?>
                                        <img src="<?php echo htmlspecialchars($cover_artists . $art['photo_artist']); ?>"
                                            onerror="this.parentElement.innerHTML='🎤'" alt="" />
                                        <?php else: ?>🎤<?php endif; ?>
                                    </div>
                                </td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($art['stage_name']); ?></td>
                                <td class="fw-bold" style="color:#FF0089">
                                    <?php echo number_format((int)$art['total_streams']); ?></td>
                                <td class="text-center">
                                    <a href="artist-details?artist=<?php echo (int)$art['id_artist']; ?>"
                                        class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Playlists ── -->
        <div class="table-card mb-4">
            <div class="card" id="playlist">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Playlists</h6>
                    <span class="badge bg-secondary text-muted" style="font-size:.7rem">Em breve</span>
                </div>
                <div class="empty-section">
                    <div class="icon"><i class="bi bi-collection-play"></i></div>
                    <div class="small fw-semibold mb-1">Dados de playlists não disponíveis ainda.</div>
                    <div class="small">Esta funcionalidade será activada assim que as integrações com as plataformas
                        enviarem dados de playlists.</div>
                </div>
            </div>
        </div>

        <!-- ── Territórios ── -->
        <div class="territory-card mb-4">
            <div class="card" id="country">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Territórios</h6>
                    <span class="badge bg-secondary text-muted" style="font-size:.7rem">Em breve</span>
                </div>
                <div class="empty-section">
                    <div class="icon"><i class="bi bi-globe2"></i></div>
                    <div class="small fw-semibold mb-1">Dados por território não disponíveis ainda.</div>
                    <div class="small">Os dados geográficos aparecem aqui após a integração das plataformas.</div>
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

    <?php
// CSRF para links de download no modal
$csrf_export = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(16));
$_SESSION['csrf_token'] = $csrf_export;
?>

    <!-- ════ MODAL — Exportar ════ -->
    <div class="modal fade" id="modalExport" tabindex="-1" aria-labelledby="modalExportLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalExportLabel">
                        <i class="bi bi-download me-2 text-success"></i>Exportar Dados
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Info do filtro activo -->
                    <div class="alert alert-secondary py-2 px-3 mb-4" style="font-size:.82rem;border-radius:10px">
                        <i class="bi bi-funnel me-2"></i>
                        A exportar dados de <strong><?php echo $filter_year; ?></strong>
                        <?php if ($filter_store && isset($store_map[$filter_store])): ?>
                        — <strong><?php echo htmlspecialchars($store_map[$filter_store]['name_store']); ?></strong>
                        <?php else: ?> — todas as plataformas<?php endif; ?>
                    </div>

                    <!-- Aviso CSV -->
                    <div class="alert alert-warning py-2 px-3 mb-4" style="font-size:.78rem;border-radius:10px">
                        <i class="bi bi-info-circle me-1"></i>
                        Ficheiros CSV com separador <strong>;</strong> e codificação UTF-8. No Excel abre directamente.
                        No Google Sheets usa <em>Ficheiro → Importar</em> e selecciona "Ponto e vírgula".
                    </div>

                    <!-- 3 opções de download -->
                    <div class="row g-3">
                        <!-- Streams -->
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 d-flex flex-column"
                                style="border-color:rgba(255,0,137,.2) !important">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div
                                        style="width:36px;height:36px;border-radius:10px;background:rgba(255,0,137,.1);display:flex;align-items:center;justify-content:center;color:#FF0089;font-size:1.2rem;flex-shrink:0">
                                        <i class="bi bi-headphones"></i>
                                    </div>
                                    <div class="fw-bold small">Streams por Faixa</div>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;flex:1">
                                    Detalhe mensal por plataforma — Ano, Mês, Plataforma, Artista, Álbum, Faixa, ISRC,
                                    Streams, Downloads, Receita USD
                                </div>
                                <a href="export?do_export=streams_csv&year=<?php echo $filter_year; ?>&store=<?php echo $filter_store; ?>&csrf=<?php echo urlencode($csrf_export); ?>"
                                    class="btn btn-sm mt-3 w-100 fw-bold"
                                    style="background:rgba(255,0,137,.1);color:#FF0089;border:1.5px solid rgba(255,0,137,.3);border-radius:8px"
                                    data-bs-dismiss="modal">
                                    <i class="bi bi-filetype-csv me-1"></i>Download CSV
                                </a>
                            </div>
                        </div>

                        <!-- Royalties -->
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 d-flex flex-column"
                                style="border-color:rgba(25,135,84,.2) !important">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div
                                        style="width:36px;height:36px;border-radius:10px;background:rgba(25,135,84,.1);display:flex;align-items:center;justify-content:center;color:#198754;font-size:1.2rem;flex-shrink:0">
                                        <i class="bi bi-cash-coin"></i>
                                    </div>
                                    <div class="fw-bold small">Royalties</div>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;flex:1">
                                    Receitas mensais — Faixa, ISRC, Álbum, Artista, Receita Bruta, Taxa, Royalty Líquido
                                    USD e AOA, Estado
                                </div>
                                <a href="export?do_export=royalties_csv&year=<?php echo $filter_year; ?>&csrf=<?php echo urlencode($csrf_export); ?>"
                                    class="btn btn-sm mt-3 w-100 fw-bold"
                                    style="background:rgba(25,135,84,.1);color:#198754;border:1.5px solid rgba(25,135,84,.3);border-radius:8px"
                                    data-bs-dismiss="modal">
                                    <i class="bi bi-filetype-csv me-1"></i>Download CSV
                                </a>
                            </div>
                        </div>

                        <!-- Catálogo -->
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 d-flex flex-column"
                                style="border-color:rgba(13,110,253,.2) !important">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div
                                        style="width:36px;height:36px;border-radius:10px;background:rgba(13,110,253,.1);display:flex;align-items:center;justify-content:center;color:#0d6efd;font-size:1.2rem;flex-shrink:0">
                                        <i class="bi bi-music-note-list"></i>
                                    </div>
                                    <div class="fw-bold small">Catálogo de Faixas</div>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;flex:1">
                                    Todas as faixas activas com totais de <?php echo $filter_year; ?> — ISRC, Autor,
                                    Álbum, Território, Editora, Streams, Receita
                                </div>
                                <a href="export?do_export=tracks_csv&year=<?php echo $filter_year; ?>&store=<?php echo $filter_store; ?>&csrf=<?php echo urlencode($csrf_export); ?>"
                                    class="btn btn-sm mt-3 w-100 fw-bold"
                                    style="background:rgba(13,110,253,.1);color:#0d6efd;border:1.5px solid rgba(13,110,253,.3);border-radius:8px"
                                    data-bs-dismiss="modal">
                                    <i class="bi bi-filetype-csv me-1"></i>Download CSV
                                </a>
                            </div>
                        </div>
                    </div><!-- /row -->

                    <!-- PDF future -->
                    <div class="mt-4 pt-3 border-top d-flex align-items-center gap-2" style="opacity:.5">
                        <i class="bi bi-filetype-pdf text-danger fs-5"></i>
                        <span class="small text-muted">Relatórios PDF (em breve) — Streams, Financeiro e por
                            Artista</span>
                        <span class="badge bg-secondary ms-1" style="font-size:.6rem">Em breve</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- ════ MODAL — Exportar FIM ════ -->

    <!-- ═══ JS ═══ -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/theme.wp.js"></script>
    <script src="../../js/wp.tools.js"></script>
    <script>
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

    <?php if (!empty($chart_datasets)): ?>
    // ── Chart.js ──────────────────────────────────
    const ctx = document.getElementById('streamChart').getContext('2d');
    const streamChart = new Chart(ctx, {
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