<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Comparar Períodos
// Arquivo: dashboard/analytics/compare.php
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
if (empty($available_years)) {
    $available_years = [(int)date('Y'), (int)date('Y') - 1];
}
$current_year = (int)date('Y');

// ── Lojas activas ─────────────────────────────
$stores_q = $db->prepare("SELECT id_store, name_store, slug_store FROM _store WHERE is_active = 1 ORDER BY display_order ASC");
$stores_q->execute();
$stores = $stores_q->fetchAll(PDO::FETCH_ASSOC);

// ── Parâmetros GET ────────────────────────────
// Período A e B em ano + mês
$ya_start = isset($_GET['ya_start']) ? (int)$_GET['ya_start'] : $current_year - 1;
$ma_start = isset($_GET['ma_start']) ? max(1, min(12, (int)$_GET['ma_start'])) : 1;
$ya_end   = isset($_GET['ya_end'])   ? (int)$_GET['ya_end']   : $current_year - 1;
$ma_end   = isset($_GET['ma_end'])   ? max(1, min(12, (int)$_GET['ma_end']))   : 12;

$yb_start = isset($_GET['yb_start']) ? (int)$_GET['yb_start'] : $current_year;
$mb_start = isset($_GET['mb_start']) ? max(1, min(12, (int)$_GET['mb_start'])) : 1;
$yb_end   = isset($_GET['yb_end'])   ? (int)$_GET['yb_end']   : $current_year;
$mb_end   = isset($_GET['mb_end'])   ? max(1, min(12, (int)$_GET['mb_end']))   : 12;

$filter_store = isset($_GET['store']) ? (int)$_GET['store'] : 0;

$has_data = !empty($_GET['ya_start']) || !empty($_GET['yb_start']);

// ── Helper: query de streams para um período ──
function queryPeriodStreams(PDO $db, int $id_users, int $y_start, int $m_start, int $y_end, int $m_end, int $store = 0): array {
    $store_clause = $store ? "AND s.id_store = :store" : "";
    $sql = "
        SELECT
            s.year_stream,
            s.month_stream,
            s.id_store,
            st.name_store,
            st.slug_store,
            COALESCE(SUM(s.streams), 0)   AS streams,
            COALESCE(SUM(s.downloads), 0) AS downloads,
            COALESCE(SUM(s.revenue), 0)   AS revenue
        FROM _stream s
        JOIN _track t  ON t.id_track  = s.id_track
        JOIN _store st ON st.id_store = s.id_store
        WHERE t.id_users = :id_users
          AND (s.year_stream > :y_start OR (s.year_stream = :y_start2 AND s.month_stream >= :m_start))
          AND (s.year_stream < :y_end   OR (s.year_stream = :y_end2   AND s.month_stream <= :m_end))
          $store_clause
        GROUP BY s.year_stream, s.month_stream, s.id_store, st.name_store, st.slug_store
        ORDER BY s.year_stream ASC, s.month_stream ASC, st.display_order ASC
    ";
    $stmt = $db->prepare($sql);
    $params = [
        ':id_users' => $id_users,
        ':y_start'  => $y_start,  ':y_start2' => $y_start, ':m_start' => $m_start,
        ':y_end'    => $y_end,    ':y_end2'   => $y_end,   ':m_end'   => $m_end,
    ];
    if ($store) $params[':store'] = $store;
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Helper: query top artistas por período ────
function queryPeriodArtists(PDO $db, int $id_users, int $y_start, int $m_start, int $y_end, int $m_end, int $store = 0): array {
    $store_clause = $store ? "AND s.id_store = :store" : "";
    $sql = "
        SELECT
            a.id_artist,
            a.stage_name,
            a.photo_artist,
            COALESCE(SUM(s.streams), 0) AS streams
        FROM _stream s
        JOIN _track t  ON t.id_track  = s.id_track
        JOIN _album al ON al.id_album = t.id_album
        JOIN _artist a ON a.id_artist = al.id_artist
        WHERE t.id_users = :id_users AND a.id_users = :id_users2
          AND (s.year_stream > :y_start OR (s.year_stream = :y_start2 AND s.month_stream >= :m_start))
          AND (s.year_stream < :y_end   OR (s.year_stream = :y_end2   AND s.month_stream <= :m_end))
          $store_clause
        GROUP BY a.id_artist, a.stage_name, a.photo_artist
        ORDER BY streams DESC
        LIMIT 5
    ";
    $stmt = $db->prepare($sql);
    $params = [
        ':id_users'  => $id_users,
        ':id_users2' => $id_users,
        ':y_start'   => $y_start, ':y_start2' => $y_start, ':m_start' => $m_start,
        ':y_end'     => $y_end,   ':y_end2'   => $y_end,   ':m_end'   => $m_end,
    ];
    if ($store) $params[':store'] = $store;
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Executar queries quando há parâmetros ─────
$rows_a = $rows_b = $artists_a = $artists_b = [];

if ($has_data) {
    $rows_a    = queryPeriodStreams($db, $id_users, $ya_start, $ma_start, $ya_end,   $ma_end,   $filter_store);
    $rows_b    = queryPeriodStreams($db, $id_users, $yb_start, $mb_start, $yb_end,   $mb_end,   $filter_store);
    $artists_a = queryPeriodArtists($db, $id_users, $ya_start, $ma_start, $ya_end,  $ma_end,   $filter_store);
    $artists_b = queryPeriodArtists($db, $id_users, $yb_start, $mb_start, $yb_end,  $mb_end,   $filter_store);
}

// ── Totais por período ────────────────────────
$total_a_streams  = array_sum(array_column($rows_a, 'streams'));
$total_b_streams  = array_sum(array_column($rows_b, 'streams'));
$total_a_revenue  = array_sum(array_column($rows_a, 'revenue'));
$total_b_revenue  = array_sum(array_column($rows_b, 'revenue'));
$total_a_downloads = array_sum(array_column($rows_a, 'downloads'));
$total_b_downloads = array_sum(array_column($rows_b, 'downloads'));

// ── Variação percentual ───────────────────────
function pct_change(float $old, float $new): ?float {
    if ($old == 0) return null;
    return round(($new - $old) / $old * 100, 1);
}
$pct_streams  = pct_change($total_a_streams,   $total_b_streams);
$pct_revenue  = pct_change($total_a_revenue,   $total_b_revenue);
$pct_downloads = pct_change($total_a_downloads, $total_b_downloads);

// ── Agregados por plataforma (A vs B) ─────────
$plat_a = [];
foreach ($rows_a as $r) {
    $k = $r['slug_store'];
    if (!isset($plat_a[$k])) $plat_a[$k] = ['name'=>$r['name_store'],'slug'=>$k,'streams'=>0,'revenue'=>0];
    $plat_a[$k]['streams'] += $r['streams'];
    $plat_a[$k]['revenue'] += $r['revenue'];
}
$plat_b = [];
foreach ($rows_b as $r) {
    $k = $r['slug_store'];
    if (!isset($plat_b[$k])) $plat_b[$k] = ['name'=>$r['name_store'],'slug'=>$k,'streams'=>0,'revenue'=>0];
    $plat_b[$k]['streams'] += $r['streams'];
    $plat_b[$k]['revenue'] += $r['revenue'];
}
$all_slugs = array_unique(array_merge(array_keys($plat_a), array_keys($plat_b)));

// ── Dados para gráfico mensal ─────────────────
// Construir lista de labels por mês para cada período
function build_month_labels(int $y_start, int $m_start, int $y_end, int $m_end): array {
    $labels = [];
    $pt_months = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    $y = $y_start; $m = $m_start;
    while ($y < $y_end || ($y === $y_end && $m <= $m_end)) {
        $labels[] = $pt_months[$m - 1] . '/' . substr($y, 2);
        $m++;
        if ($m > 12) { $m = 1; $y++; }
        if (count($labels) > 36) break; // segurança
    }
    return $labels;
}

function build_monthly_totals(array $rows, int $y_start, int $m_start, int $y_end, int $m_end): array {
    // índice por year+month
    $map = [];
    foreach ($rows as $r) {
        $key = $r['year_stream'] . '-' . str_pad($r['month_stream'], 2, '0', STR_PAD_LEFT);
        if (!isset($map[$key])) $map[$key] = 0;
        $map[$key] += $r['streams'];
    }
    $result = [];
    $y = $y_start; $m = $m_start;
    while ($y < $y_end || ($y === $y_end && $m <= $m_end)) {
        $key = $y . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
        $result[] = (int)($map[$key] ?? 0);
        $m++;
        if ($m > 12) { $m = 1; $y++; }
        if (count($result) > 36) break;
    }
    return $result;
}

$labels_a  = build_month_labels($ya_start, $ma_start, $ya_end, $ma_end);
$labels_b  = build_month_labels($yb_start, $mb_start, $yb_end, $mb_end);
$totals_a  = build_monthly_totals($rows_a, $ya_start, $ma_start, $ya_end, $ma_end);
$totals_b  = build_monthly_totals($rows_b, $yb_start, $mb_start, $yb_end, $mb_end);

// Labels unificados para o gráfico (máximo entre os dois períodos)
$max_len    = max(count($labels_a), count($labels_b));
$chart_labels_a = $labels_a + array_fill(0, $max_len, '');
$chart_data_a   = $totals_a + array_fill(0, $max_len, null);
$chart_data_b   = $totals_b + array_fill(0, $max_len, null);

$months_pt = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

$store_colors = [
    'spotify'       => ['border'=>'#1db954','bg'=>'rgba(29,185,84,0.4)'],
    'apple-music'   => ['border'=>'#fc3c44','bg'=>'rgba(252,60,68,0.4)'],
    'amazon-music'  => ['border'=>'#00a8e0','bg'=>'rgba(0,168,224,0.4)'],
    'deezer'        => ['border'=>'#ff0089','bg'=>'rgba(255,0,137,0.4)'],
    'tidal'         => ['border'=>'#00ffff','bg'=>'rgba(0,255,255,0.3)'],
    'youtube-music' => ['border'=>'#ff0000','bg'=>'rgba(255,0,0,0.4)'],
    'boomplay'      => ['border'=>'#f5a623','bg'=>'rgba(245,166,35,0.4)'],
    'tiktok'        => ['border'=>'#69c9d0','bg'=>'rgba(105,201,208,0.4)'],
    'default'       => ['border'=>'#888',   'bg'=>'rgba(136,136,136,0.3)'],
];
$store_icons = [
    'spotify'=>'bi-spotify','apple-music'=>'bi-apple','amazon-music'=>'bi-music-note-beamed',
    'deezer'=>'bi-music-player','tidal'=>'bi-water','youtube-music'=>'bi-youtube',
    'boomplay'=>'bi-soundwave','tiktok'=>'bi-tiktok','default'=>'bi-music-note-beamed',
];

$base_url  = rtrim(APP_URL, '/');
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
    <title>Comparar Períodos — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="../../css/dashboard-style.css" />
    <link rel="stylesheet" href="../../css/lastest-style.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
    /* ══ Layout ══ */
    .comparison-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 18px;
        padding: 2rem;
        margin-bottom: 2rem;
        color: #fff;
    }

    /* ══ Período cards ══ */
    .period-card {
        border-radius: 14px;
        padding: 1.5rem;
        height: 100%;
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        transition: box-shadow .2s;
    }

    .period-card.period-a {
        border-left: 4px solid #ff0089;
    }

    .period-card.period-b {
        border-left: 4px solid #00d084;
    }

    .period-card:hover {
        box-shadow: 0 6px 24px rgba(255, 0, 137, .1);
    }

    /* ══ Métricas ══ */
    .metric-box {
        background: var(--metric-bg, rgba(0, 0, 0, .03));
        border-radius: 12px;
        padding: 1.2rem;
        text-align: center;
        margin-bottom: .8rem;
    }

    .metric-value {
        font-size: 1.8rem;
        font-weight: 900;
    }

    .metric-label {
        font-size: .72rem;
        color: var(--text-muted, #6c757d);
        text-transform: uppercase;
        letter-spacing: .6px;
        margin-top: 4px;
    }

    .total-streams-a {
        color: #ff0089;
        font-size: 2.4rem;
        font-weight: 900;
    }

    .total-streams-b {
        color: #00d084;
        font-size: 2.4rem;
        font-weight: 900;
    }

    .vs-divider {
        font-size: 1.5rem;
        font-weight: 900;
        color: var(--text-muted, #aaa);
    }

    /* ══ Badges variação ══ */
    .comparison-badge {
        display: inline-block;
        padding: .4rem 1rem;
        border-radius: 20px;
        font-weight: 700;
        font-size: .88rem;
    }

    .badge-positive {
        background: rgba(0, 208, 132, .12);
        color: #00a862;
        border: 1px solid #00d084;
    }

    .badge-negative {
        background: rgba(255, 68, 68, .12);
        color: #cc3333;
        border: 1px solid #ff4444;
    }

    .badge-neutral {
        background: rgba(150, 150, 150, .1);
        color: #888;
        border: 1px solid #aaa;
    }

    .percentage-change {
        font-weight: 700;
        padding: .25rem .7rem;
        border-radius: 20px;
        font-size: .82rem;
    }

    /* ══ Botões ══ */
    .btn-compare {
        background: #ff0089;
        color: #fff;
        border: none;
        padding: .75rem 2rem;
        border-radius: 25px;
        font-weight: 700;
        transition: background .2s, transform .15s;
    }

    .btn-compare:hover {
        background: #cc006e;
        transform: scale(1.04);
        color: #fff;
    }

    .btn-swap {
        background: transparent;
        border: 2px solid #ff0089;
        color: #ff0089;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all .25s;
    }

    .btn-swap:hover {
        background: #ff0089;
        color: #fff;
        transform: rotate(180deg);
    }

    /* ══ Quick select ══ */
    .quick-select {
        display: inline-block;
        padding: .4rem 1rem;
        border-radius: 20px;
        margin: .2rem;
        cursor: pointer;
        border: 1px solid var(--border-color, rgba(0, 0, 0, .12));
        font-size: .82rem;
        transition: all .2s;
    }

    .quick-select:hover,
    .quick-select.active {
        background: #ff0089;
        border-color: #ff0089;
        color: #fff;
    }

    /* ══ Plataformas ══ */
    .platform-icon-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
        color: #fff;
    }

    .bar-compare {
        height: 12px;
        border-radius: 6px;
    }

    .bar-a {
        background: linear-gradient(90deg, #ff0089, #ff4da6);
    }

    .bar-b {
        background: linear-gradient(90deg, #00d084, #4dffc4);
    }

    /* ══ Artistas ══ */
    .artist-rank-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .06));
    }

    .artist-rank-row:last-child {
        border-bottom: none;
    }

    .artist-photo-sm {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }

    .artist-photo-sm-placeholder {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255, 0, 137, .08);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    /* ══ Empty state ══ */
    .compare-cta {
        text-align: center;
        padding: 48px 24px;
        color: var(--text-muted, #6c757d);
    }

    .compare-cta .icon {
        font-size: 3rem;
        opacity: .12;
        margin-bottom: 14px;
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
                <li class="nav-item d-lg-none"><a class="nav-link text-danger" href="#" data-bs-toggle="modal"
                        data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right"></i> Desconectar-se</a></li>
            </ul>
        </div>
    </div>

    <!-- ═══ MAIN ═══ -->
    <div class="container my-4">

        <!-- Cabeçalho -->
        <div class="comparison-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="bi bi-calendar-range me-3"></i>Comparar Períodos</h1>
                    <p class="lead mb-0">Compare o desempenho das tuas músicas entre dois períodos distintos e visualiza
                        o crescimento mês a mês.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="statistics" class="btn btn-pink me-2">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                    <?php if ($has_data && (!empty($rows_a) || !empty($rows_b))): ?>
                    <a href="export?context=compare&ya_start=<?php echo $ya_start; ?>&ma_start=<?php echo $ma_start; ?>&ya_end=<?php echo $ya_end; ?>&ma_end=<?php echo $ma_end; ?>&yb_start=<?php echo $yb_start; ?>&mb_start=<?php echo $mb_start; ?>&yb_end=<?php echo $yb_end; ?>&mb_end=<?php echo $mb_end; ?>"
                        class="btn btn-secondary">
                        <i class="bi bi-download"></i> Exportar
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Formulário de comparação ── -->
        <form method="GET" action="compare" id="compareForm">
            <!-- Seleção rápida -->
            <div class="mb-3">
                <div class="small text-muted fw-semibold mb-2 text-uppercase" style="letter-spacing:.5px">Seleção rápida
                </div>
                <span class="quick-select" onclick="setQuick('month')">Este mês vs Anterior</span>
                <span class="quick-select" onclick="setQuick('quarter')">Trimestre vs Anterior</span>
                <span class="quick-select" onclick="setQuick('year')">Este ano vs Anterior</span>
            </div>

            <div class="row mb-3 g-3 align-items-stretch">
                <!-- Período A -->
                <div class="col-md-5">
                    <div class="period-card period-a h-100">
                        <h5 class="mb-3"><i class="bi bi-calendar-check me-2" style="color:#ff0089"></i>Período A</h5>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-muted">Ano início</label>
                                <select name="ya_start" id="ya_start" class="form-select form-select-sm">
                                    <?php foreach ($available_years as $y): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == $ya_start ? 'selected' : ''; ?>>
                                        <?php echo $y; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Mês início</label>
                                <select name="ma_start" id="ma_start" class="form-select form-select-sm">
                                    <?php foreach ($months_pt as $mi => $mn): ?>
                                    <option value="<?php echo $mi+1; ?>"
                                        <?php echo ($mi+1) == $ma_start ? 'selected' : ''; ?>><?php echo $mn; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Ano fim</label>
                                <select name="ya_end" id="ya_end" class="form-select form-select-sm">
                                    <?php foreach ($available_years as $y): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == $ya_end ? 'selected' : ''; ?>>
                                        <?php echo $y; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Mês fim</label>
                                <select name="ma_end" id="ma_end" class="form-select form-select-sm">
                                    <?php foreach ($months_pt as $mi => $mn): ?>
                                    <option value="<?php echo $mi+1; ?>"
                                        <?php echo ($mi+1) == $ma_end ? 'selected' : ''; ?>><?php echo $mn; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Swap -->
                <div class="col-md-2 d-flex align-items-center justify-content-center">
                    <button type="button" class="btn-swap" onclick="swapPeriods()" title="Trocar períodos">
                        <i class="bi bi-arrow-left-right"></i>
                    </button>
                </div>

                <!-- Período B -->
                <div class="col-md-5">
                    <div class="period-card period-b h-100">
                        <h5 class="mb-3"><i class="bi bi-calendar-check me-2" style="color:#00d084"></i>Período B</h5>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-muted">Ano início</label>
                                <select name="yb_start" id="yb_start" class="form-select form-select-sm">
                                    <?php foreach ($available_years as $y): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == $yb_start ? 'selected' : ''; ?>>
                                        <?php echo $y; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Mês início</label>
                                <select name="mb_start" id="mb_start" class="form-select form-select-sm">
                                    <?php foreach ($months_pt as $mi => $mn): ?>
                                    <option value="<?php echo $mi+1; ?>"
                                        <?php echo ($mi+1) == $mb_start ? 'selected' : ''; ?>><?php echo $mn; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Ano fim</label>
                                <select name="yb_end" id="yb_end" class="form-select form-select-sm">
                                    <?php foreach ($available_years as $y): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == $yb_end ? 'selected' : ''; ?>>
                                        <?php echo $y; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Mês fim</label>
                                <select name="mb_end" id="mb_end" class="form-select form-select-sm">
                                    <?php foreach ($months_pt as $mi => $mn): ?>
                                    <option value="<?php echo $mi+1; ?>"
                                        <?php echo ($mi+1) == $mb_end ? 'selected' : ''; ?>><?php echo $mn; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plataforma -->
            <div class="d-flex gap-3 align-items-end mb-4 flex-wrap">
                <div>
                    <label class="form-label small text-muted fw-semibold">Plataforma</label>
                    <select name="store" class="form-select form-select-sm" style="min-width:160px">
                        <option value="0" <?php echo !$filter_store ? 'selected' : ''; ?>>Todas</option>
                        <?php foreach ($stores as $st): ?>
                        <option value="<?php echo $st['id_store']; ?>"
                            <?php echo $st['id_store'] == $filter_store ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($st['name_store']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-compare">
                    <i class="bi bi-graph-up-arrow me-2"></i>Comparar Períodos
                </button>
            </div>
        </form>

        <?php if (!$has_data): ?>
        <!-- CTA inicial -->
        <div class="compare-cta">
            <div class="icon"><i class="bi bi-calendar-range"></i></div>
            <div class="fw-semibold mb-1">Selecciona dois períodos e clica em <strong>Comparar</strong></div>
            <div class="small">Podes usar a seleção rápida acima para comparar meses, trimestres ou anos.</div>
        </div>

        <?php elseif (empty($rows_a) && empty($rows_b)): ?>
        <div class="compare-cta">
            <div class="icon"><i class="bi bi-bar-chart"></i></div>
            <div class="fw-semibold mb-1">Sem dados para os períodos seleccionados.</div>
            <div class="small">Tenta outro intervalo ou aguarda a importação dos dados das plataformas.</div>
        </div>

        <?php else: ?>
        <!-- ════ RESULTADOS ════ -->

        <!-- Total streams A vs B -->
        <div class="period-card mb-4">
            <div class="row align-items-center text-center g-3">
                <div class="col-md-5">
                    <div class="small text-muted text-uppercase fw-bold" style="letter-spacing:.5px">Período A</div>
                    <div class="total-streams-a"><?php echo number_format((int)$total_a_streams); ?></div>
                    <div class="small text-muted"><?php echo $months_pt[$ma_start-1].'/'.$ya_start; ?> →
                        <?php echo $months_pt[$ma_end-1].'/'.$ya_end; ?></div>
                </div>
                <div class="col-md-2">
                    <div class="vs-divider">VS</div>
                    <?php if ($pct_streams !== null): ?>
                    <div class="mt-2">
                        <span
                            class="comparison-badge <?php echo $pct_streams > 0 ? 'badge-positive' : ($pct_streams < 0 ? 'badge-negative' : 'badge-neutral'); ?>">
                            <i
                                class="bi bi-arrow-<?php echo $pct_streams > 0 ? 'up' : ($pct_streams < 0 ? 'down' : 'dash'); ?> me-1"></i>
                            <?php echo abs($pct_streams); ?>%
                            <?php echo $pct_streams > 0 ? 'crescimento' : ($pct_streams < 0 ? 'queda' : 'estável'); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-5">
                    <div class="small text-muted text-uppercase fw-bold" style="letter-spacing:.5px">Período B</div>
                    <div class="total-streams-b"><?php echo number_format((int)$total_b_streams); ?></div>
                    <div class="small text-muted"><?php echo $months_pt[$mb_start-1].'/'.$yb_start; ?> →
                        <?php echo $months_pt[$mb_end-1].'/'.$yb_end; ?></div>
                </div>
            </div>
        </div>

        <!-- Métricas 3 cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="period-card text-center">
                    <div class="metric-label">Downloads</div>
                    <div class="d-flex justify-content-center align-items-baseline gap-3 mt-2">
                        <div>
                            <div style="font-size:.7rem;color:#ff0089">A</div>
                            <div class="metric-value" style="color:#ff0089">
                                <?php echo number_format((int)$total_a_downloads); ?></div>
                        </div>
                        <div class="vs-divider" style="font-size:1rem">vs</div>
                        <div>
                            <div style="font-size:.7rem;color:#00d084">B</div>
                            <div class="metric-value" style="color:#00d084">
                                <?php echo number_format((int)$total_b_downloads); ?></div>
                        </div>
                    </div>
                    <?php if ($pct_downloads !== null): ?>
                    <div class="mt-2"><span
                            class="comparison-badge <?php echo $pct_downloads >= 0 ? 'badge-positive' : 'badge-negative'; ?>"
                            style="font-size:.72rem">
                            <i
                                class="bi bi-arrow-<?php echo $pct_downloads >= 0 ? 'up' : 'down'; ?> me-1"></i><?php echo abs($pct_downloads); ?>%
                        </span></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="period-card text-center">
                    <div class="metric-label">Receita bruta (USD)</div>
                    <div class="d-flex justify-content-center align-items-baseline gap-3 mt-2">
                        <div>
                            <div style="font-size:.7rem;color:#ff0089">A</div>
                            <div class="metric-value" style="color:#ff0089;font-size:1.4rem">
                                $<?php echo number_format((float)$total_a_revenue, 2); ?></div>
                        </div>
                        <div class="vs-divider" style="font-size:1rem">vs</div>
                        <div>
                            <div style="font-size:.7rem;color:#00d084">B</div>
                            <div class="metric-value" style="color:#00d084;font-size:1.4rem">
                                $<?php echo number_format((float)$total_b_revenue, 2); ?></div>
                        </div>
                    </div>
                    <?php if ($pct_revenue !== null): ?>
                    <div class="mt-2"><span
                            class="comparison-badge <?php echo $pct_revenue >= 0 ? 'badge-positive' : 'badge-negative'; ?>"
                            style="font-size:.72rem">
                            <i
                                class="bi bi-arrow-<?php echo $pct_revenue >= 0 ? 'up' : 'down'; ?> me-1"></i><?php echo abs($pct_revenue); ?>%
                        </span></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="period-card text-center">
                    <div class="metric-label">Média mensal de streams</div>
                    <?php
                $months_a_count = count($totals_a) ?: 1;
                $months_b_count = count($totals_b) ?: 1;
                $avg_a = round($total_a_streams / $months_a_count);
                $avg_b = round($total_b_streams / $months_b_count);
                $pct_avg = pct_change($avg_a, $avg_b);
                ?>
                    <div class="d-flex justify-content-center align-items-baseline gap-3 mt-2">
                        <div>
                            <div style="font-size:.7rem;color:#ff0089">A</div>
                            <div class="metric-value" style="color:#ff0089"><?php echo number_format($avg_a); ?></div>
                        </div>
                        <div class="vs-divider" style="font-size:1rem">vs</div>
                        <div>
                            <div style="font-size:.7rem;color:#00d084">B</div>
                            <div class="metric-value" style="color:#00d084"><?php echo number_format($avg_b); ?></div>
                        </div>
                    </div>
                    <?php if ($pct_avg !== null): ?>
                    <div class="mt-2"><span
                            class="comparison-badge <?php echo $pct_avg >= 0 ? 'badge-positive' : 'badge-negative'; ?>"
                            style="font-size:.72rem">
                            <i
                                class="bi bi-arrow-<?php echo $pct_avg >= 0 ? 'up' : 'down'; ?> me-1"></i><?php echo abs($pct_avg); ?>%
                        </span></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Gráfico mensal -->
        <div class="period-card mb-4">
            <h6 class="mb-3"><i class="bi bi-graph-up me-2 text-pink"></i>Evolução mensal dos streams</h6>
            <canvas id="comparisonChart" style="max-height:300px"></canvas>
        </div>

        <!-- Comparação por plataforma -->
        <?php if (!empty($all_slugs)): ?>
        <div class="period-card mb-4">
            <h6 class="mb-3"><i class="bi bi-grid-3x3-gap-fill me-2 text-pink"></i>Comparação por plataforma</h6>
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:.85rem">
                    <thead>
                        <tr>
                            <th>Plataforma</th>
                            <th class="text-end" style="color:#ff0089">Streams A</th>
                            <th class="text-end" style="color:#00d084">Streams B</th>
                            <th class="text-center">Variação</th>
                            <th style="min-width:160px">Tendência</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_slugs as $slug):
                    $pa = $plat_a[$slug] ?? ['name'=>$slug,'streams'=>0];
                    $pb = $plat_b[$slug] ?? ['name'=>$slug,'streams'=>0];
                    $name = $pa['name'] !== $slug ? $pa['name'] : ($pb['name'] !== $slug ? $pb['name'] : $slug);
                    $pct  = pct_change($pa['streams'], $pb['streams']);
                    $colors = $store_colors[$slug] ?? $store_colors['default'];
                    $icon   = $store_icons[$slug]  ?? $store_icons['default'];
                    $max_bar = max($pa['streams'], $pb['streams'], 1);
                    $pct_bar_a = round($pa['streams'] / $max_bar * 100);
                    $pct_bar_b = round($pb['streams'] / $max_bar * 100);
                ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="platform-icon-circle"
                                        style="background:<?php echo $colors['border']; ?>">
                                        <i class="bi <?php echo $icon; ?>"></i>
                                    </div>
                                    <?php echo htmlspecialchars($name); ?>
                                </div>
                            </td>
                            <td class="text-end fw-semibold" style="color:#ff0089">
                                <?php echo number_format((int)$pa['streams']); ?></td>
                            <td class="text-end fw-semibold" style="color:#00d084">
                                <?php echo number_format((int)$pb['streams']); ?></td>
                            <td class="text-center">
                                <?php if ($pct !== null): ?>
                                <span
                                    class="percentage-change <?php echo $pct >= 0 ? 'badge-positive' : 'badge-negative'; ?>">
                                    <i
                                        class="bi bi-arrow-<?php echo $pct >= 0 ? 'up' : 'down'; ?> me-1"></i><?php echo abs($pct); ?>%
                                </span>
                                <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
                            </td>
                            <td>
                                <div class="mb-1">
                                    <div class="bar-compare bar-a"
                                        style="width:<?php echo $pct_bar_a; ?>%;max-width:100%"></div>
                                </div>
                                <div>
                                    <div class="bar-compare bar-b"
                                        style="width:<?php echo $pct_bar_b; ?>%;max-width:100%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top artistas A vs B -->
        <?php if (!empty($artists_a) || !empty($artists_b)): ?>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="period-card period-a h-100">
                    <h6 class="mb-3"><i class="bi bi-person-fill me-2" style="color:#ff0089"></i>Top Artistas — Período
                        A</h6>
                    <?php if (empty($artists_a)): ?>
                    <div class="small text-muted">Sem dados.</div>
                    <?php else: ?>
                    <?php foreach ($artists_a as $i => $art): ?>
                    <div class="artist-rank-row">
                        <span class="fw-bold" style="min-width:20px;color:#ff0089"><?php echo $i+1; ?></span>
                        <?php if ($art['photo_artist']): ?>
                        <img class="artist-photo-sm"
                            src="<?php echo htmlspecialchars($photo_url . $art['photo_artist']); ?>"
                            onerror="this.outerHTML='<div class=\'artist-photo-sm-placeholder\'>🎤</div>'" alt="" />
                        <?php else: ?><div class="artist-photo-sm-placeholder">🎤</div><?php endif; ?>
                        <div class="flex-grow-1 fw-semibold small"><?php echo htmlspecialchars($art['stage_name']); ?>
                        </div>
                        <div class="small fw-bold" style="color:#ff0089">
                            <?php echo number_format((int)$art['streams']); ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="period-card period-b h-100">
                    <h6 class="mb-3"><i class="bi bi-person-fill me-2" style="color:#00d084"></i>Top Artistas — Período
                        B</h6>
                    <?php if (empty($artists_b)): ?>
                    <div class="small text-muted">Sem dados.</div>
                    <?php else: ?>
                    <?php foreach ($artists_b as $i => $art): ?>
                    <div class="artist-rank-row">
                        <span class="fw-bold" style="min-width:20px;color:#00d084"><?php echo $i+1; ?></span>
                        <?php if ($art['photo_artist']): ?>
                        <img class="artist-photo-sm"
                            src="<?php echo htmlspecialchars($photo_url . $art['photo_artist']); ?>"
                            onerror="this.outerHTML='<div class=\'artist-photo-sm-placeholder\'>🎤</div>'" alt="" />
                        <?php else: ?><div class="artist-photo-sm-placeholder">🎤</div><?php endif; ?>
                        <div class="flex-grow-1 fw-semibold small"><?php echo htmlspecialchars($art['stage_name']); ?>
                        </div>
                        <div class="small fw-bold" style="color:#00d084">
                            <?php echo number_format((int)$art['streams']); ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; // fim $has_data ?>
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
    <script src="../../js/theme.wp.js"></script>
    <script src="../../js/wp.tools.js"></script>
    <script>
    // ── Seleção rápida ────────────────────────────
    function setQuick(type) {
        const now = new Date();
        const y = now.getFullYear();
        const m = now.getMonth() + 1; // 1-12

        let yaS, maS, yaE, maE, ybS, mbS, ybE, mbE;

        if (type === 'month') {
            // Mês anterior vs este mês
            const prevM = m === 1 ? 12 : m - 1;
            const prevY = m === 1 ? y - 1 : y;
            yaS = yaE = prevY;
            maS = maE = prevM;
            ybS = ybE = y;
            mbS = mbE = m;
        } else if (type === 'quarter') {
            const q = Math.ceil(m / 3);
            const prevQ = q === 1 ? 4 : q - 1;
            const prevQY = q === 1 ? y - 1 : y;
            yaS = prevQY;
            maS = (prevQ - 1) * 3 + 1;
            yaE = prevQY;
            maE = prevQ * 3;
            ybS = y;
            mbS = (q - 1) * 3 + 1;
            ybE = y;
            mbE = Math.min(q * 3, m);
        } else { // year
            yaS = y - 1;
            maS = 1;
            yaE = y - 1;
            maE = 12;
            ybS = y;
            mbS = 1;
            ybE = y;
            mbE = m;
        }

        setSelects(yaS, maS, yaE, maE, 'ya_start', 'ma_start', 'ya_end', 'ma_end');
        setSelects(ybS, mbS, ybE, mbE, 'yb_start', 'mb_start', 'yb_end', 'mb_end');
        document.getElementById('compareForm').submit();
    }

    function setSelects(yS, mS, yE, mE, idYS, idMS, idYE, idME) {
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) {
                for (let opt of el.options) {
                    if (parseInt(opt.value) === val) {
                        opt.selected = true;
                        break;
                    }
                }
            }
        };
        setVal(idYS, yS);
        setVal(idMS, mS);
        setVal(idYE, yE);
        setVal(idME, mE);
    }

    // ── Swap períodos ─────────────────────────────
    function swapPeriods() {
        const getV = id => document.getElementById(id).value;
        const setV = (id, v) => {
            document.getElementById(id).value = v;
        };
        const [yaS, maS, yaE, maE] = [getV('ya_start'), getV('ma_start'), getV('ya_end'), getV('ma_end')];
        const [ybS, mbS, ybE, mbE] = [getV('yb_start'), getV('mb_start'), getV('yb_end'), getV('mb_end')];
        setV('ya_start', ybS);
        setV('ma_start', mbS);
        setV('ya_end', ybE);
        setV('ma_end', mbE);
        setV('yb_start', yaS);
        setV('mb_start', maS);
        setV('yb_end', yaE);
        setV('mb_end', maE);
    }

    <?php if ($has_data && (!empty($rows_a) || !empty($rows_b))): ?>
    // ── Gráfico ───────────────────────────────────
    const labelsA = <?php echo json_encode(array_values($labels_a)); ?>;
    const labelsB = <?php echo json_encode(array_values($labels_b)); ?>;
    const dataA = <?php echo json_encode(array_values($chart_data_a)); ?>;
    const dataB = <?php echo json_encode(array_values($chart_data_b)); ?>;
    const maxLen = Math.max(labelsA.length, labelsB.length);
    const chartLabels = labelsA.length >= labelsB.length ? labelsA : labelsB;

    new Chart(document.getElementById('comparisonChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                    label: 'Período A (<?php echo $months_pt[$ma_start-1].'/'.$ya_start; ?> → <?php echo $months_pt[$ma_end-1].'/'.$ya_end; ?>)',
                    data: dataA.slice(0, maxLen),
                    borderColor: '#ff0089',
                    backgroundColor: 'rgba(255,0,137,0.08)',
                    tension: 0.4,
                    fill: true,
                    spanGaps: true
                },
                {
                    label: 'Período B (<?php echo $months_pt[$mb_start-1].'/'.$yb_start; ?> → <?php echo $months_pt[$mb_end-1].'/'.$yb_end; ?>)',
                    data: dataB.slice(0, maxLen),
                    borderColor: '#00d084',
                    backgroundColor: 'rgba(0,208,132,0.08)',
                    tension: 0.4,
                    fill: true,
                    spanGaps: true
                }
            ]
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
                    title: {
                        display: true,
                        text: 'Streams'
                    }
                },
                x: {
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