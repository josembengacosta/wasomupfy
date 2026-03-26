<?php
// ═══════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Analytics: Desempenho por Artista
// Arquivo: wu-panel-2026/pages/analytics/artists.php
// Rota:    wu-panel-2026/analytics/artists
// ═══════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// ── Feedback ──────────────────────────────────────────────────────────────
$msg      = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'artist_blocked'   => ['warning', 'bi-lock', 'Artista bloqueado com sucesso.'],
    'artist_unblocked' => ['success', 'bi-unlock', 'Artista desbloqueado com sucesso.'],
    'export_success'   => ['success', 'bi-download', 'Exportação concluída com sucesso.'],
    'error'            => ['danger',  'bi-x-circle', 'Ocorreu um erro. Tenta novamente.'],
    default            => null,
};

// ── Helpers (mesmos de home.php) ─────────────────────────────────────────
function an_fmt_num(int|float $v): string
{
    if ($v >= 1_000_000) return number_format($v / 1_000_000, 1, ',', '.') . 'M';
    if ($v >= 1_000)     return number_format($v / 1_000, 1, ',', '.') . 'K';
    return number_format($v, 0, ',', '.');
}
function an_fmt_aoa(float $v): string
{
    if ($v >= 1_000_000) return 'Kz ' . number_format($v / 1_000_000, 1, ',', '.') . 'M';
    if ($v >= 1_000)     return 'Kz ' . number_format($v / 1_000, 1, ',', '.') . 'mil';
    return 'Kz ' . number_format($v, 2, ',', '.');
}
function an_status_badge(string $s): string
{
    return match ($s) {
        'active'    => '<span class="badge an-s-active">Activo</span>',
        'inactive'  => '<span class="badge an-s-inactive">Inactivo</span>',
        'blocked'   => '<span class="badge an-s-blocked">Bloqueado</span>',
        'processing' => '<span class="badge an-s-processing">A processar</span>',
        default     => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}
function an_avatar(string $name, ?string $photo, int $size = 32): string
{
    $parts = explode(' ', trim($name), 2);
    $ini   = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1, 'UTF-8'), 'UTF-8')
        . mb_strtoupper(mb_substr($parts[1] ?? '', 0, 1, 'UTF-8'), 'UTF-8');
    $clrs  = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308', '#3b82f6', '#ef4444'];
    $color = $clrs[abs(crc32($name)) % count($clrs)];
    $s     = $size;
    $fs    = round($s * 0.3);
    if ($photo) {
        return '<img src="' . APP_URL . '/assets/comprovantes/uploads/artists/' . htmlspecialchars($photo) . '"
                     width="' . $s . '" height="' . $s . '"
                     style="border-radius:50%;object-fit:cover;border:2px solid rgba(255,0,137,.2);flex-shrink:0"
                     onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'"
                     alt="">
                <div style="width:' . $s . 'px;height:' . $s . 'px;border-radius:50%;background:' . $color . ';
                            display:none;align-items:center;justify-content:center;
                            font-weight:700;font-size:' . $fs . 'px;color:#fff;flex-shrink:0">' . $ini . '</div>';
    }
    return '<div style="width:' . $s . 'px;height:' . $s . 'px;border-radius:50%;background:' . $color . ';
                         display:flex;align-items:center;justify-content:center;
                         font-weight:700;font-size:' . $fs . 'px;color:#fff;flex-shrink:0">' . $ini . '</div>';
}

// ── Dados globais para os cards ──────────────────────────────────────────
$total_artists = (int)$db->query("SELECT COUNT(*) FROM _artist")->fetchColumn();
$active_artists = (int)$db->query("SELECT COUNT(*) FROM _artist WHERE status_artist = 'active'")->fetchColumn();
$total_streams_all = (int)$db->query("SELECT COALESCE(SUM(streams),0) FROM _stream")->fetchColumn();
$total_revenue_usd = (float)$db->query("SELECT COALESCE(SUM(revenue),0) FROM _stream")->fetchColumn();
$usd_rate = (float)($db->query("SELECT usd_to_aoa_rate FROM _platform LIMIT 1")->fetchColumn() ?: 900);
$total_revenue_aoa = $total_revenue_usd * $usd_rate;
$tracks_with_streams = (int)$db->query("SELECT COUNT(DISTINCT id_track) FROM _stream")->fetchColumn();
$avg_streams_per_artist = ($total_artists > 0) ? ($total_streams_all / $total_artists) : 0;
$unique_territories = (int)$db->query("SELECT COUNT(DISTINCT country_code) FROM _stream_country WHERE country_code IS NOT NULL")->fetchColumn();

// ── Filtros ──────────────────────────────────────────────────────────────
$per_page  = 15;
$page      = max(1, (int)($_GET['page'] ?? 1));
$f_name    = trim($_GET['name'] ?? '');
$f_country = trim($_GET['country'] ?? '');
$f_status  = trim($_GET['status'] ?? '');
$f_min_str = (int)($_GET['min_str'] ?? 0);
$f_max_str = (int)($_GET['max_str'] ?? 0);
$f_date_from = trim($_GET['date_from'] ?? '');
$f_date_to   = trim($_GET['date_to'] ?? '');
$sort_col  = in_array($_GET['sort'] ?? '', ['a.id_artist', 'a.stage_name', 'total_streams', 'total_revenue_aoa', 'tracks_count', 'a.status_artist'])
    ? $_GET['sort'] : 'total_streams';
$sort_dir  = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// ── Query com agregação e filtros ───────────────────────────────────────
$where  = [];
$params = [];

if ($f_name !== '') {
    $where[]  = "a.stage_name LIKE ?";
    $params[] = '%' . $f_name . '%';
}
if ($f_country !== '') {
    $where[]  = "a.country = ?";
    $params[] = $f_country;
}
if ($f_status !== '') {
    $where[]  = "a.status_artist = ?";
    $params[] = $f_status;
}
if ($f_date_from !== '') {
    $where[]  = "s.year_stream >= YEAR(?) OR (s.year_stream = YEAR(?) AND s.month_stream >= MONTH(?))";
    $params[] = $f_date_from;
    $params[] = $f_date_from;
    $params[] = $f_date_from;
}
if ($f_date_to !== '') {
    $where[]  = "s.year_stream <= YEAR(?) OR (s.year_stream = YEAR(?) AND s.month_stream <= MONTH(?))";
    $params[] = $f_date_to;
    $params[] = $f_date_to;
    $params[] = $f_date_to;
}
if ($f_min_str > 0) {
    $where[]  = "COALESCE(SUM(s.streams),0) >= ?";
    $params[] = $f_min_str;
}
if ($f_max_str > 0) {
    $where[]  = "COALESCE(SUM(s.streams),0) <= ?";
    $params[] = $f_max_str;
}

$base_joins = "
    FROM _artist a
    LEFT JOIN _album al ON al.id_artist = a.id_artist
    LEFT JOIN _track t ON t.id_album = al.id_album
    LEFT JOIN _stream s ON s.id_track = t.id_track
";
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Contagem total de artistas
$count_stmt = $db->prepare("
    SELECT COUNT(DISTINCT a.id_artist) $base_joins $sql_where
");
$count_stmt->execute($params);
$total_filtered = (int)$count_stmt->fetchColumn();
$total_pages    = max(1, (int)ceil($total_filtered / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;

// Dados dos artistas
$data_stmt = $db->prepare("
    SELECT
        a.id_artist,
        a.stage_name,
        a.real_name,
        a.country,
        a.city,
        a.status_artist,
        a.photo_artist,
        a.creat_artist,
        u.id_users,
        u.first_name,
        u.second_name,
        u.email_user,
        COALESCE(SUM(s.streams),0) AS total_streams,
        COALESCE(SUM(s.revenue),0) AS total_revenue_usd,
        COUNT(DISTINCT t.id_track) AS tracks_count,
        MAX(al.release_date) AS last_release_date
    $base_joins
    LEFT JOIN _users u ON u.id_users = a.id_users
    $sql_where
    GROUP BY a.id_artist
    ORDER BY $sort_col $sort_dir
    LIMIT $per_page OFFSET $offset
");
$data_stmt->execute($params);
$artists = $data_stmt->fetchAll();

// Converter receita para AOA
foreach ($artists as &$a) {
    $a['total_revenue_aoa'] = (float)$a['total_revenue_usd'] * $usd_rate;
}
unset($a);

// Listas para filtros
$countries = $db->query("SELECT DISTINCT country FROM _artist WHERE country IS NOT NULL ORDER BY country")->fetchAll(PDO::FETCH_COLUMN);
$csrf = $_SESSION['admin_csrf_token'];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title>Analytics — Desempenho por Artista — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
    /* Mesmos estilos de home.php */
    .an-s-active {
        background: rgba(34, 197, 94, .15);
        color: #166534;
    }

    .an-s-inactive {
        background: rgba(107, 114, 128, .15);
        color: #374151;
    }

    .an-s-blocked {
        background: rgba(239, 68, 68, .15);
        color: #991b1b;
    }

    .an-s-processing {
        background: rgba(59, 130, 246, .15);
        color: #1e40af;
    }

    .dark-mode .an-s-active {
        background: rgba(34, 197, 94, .18);
        color: #4ade80;
    }

    .dark-mode .an-s-inactive {
        background: rgba(107, 114, 128, .18);
        color: #9ca3af;
    }

    .dark-mode .an-s-blocked {
        background: rgba(239, 68, 68, .18);
        color: #f87171;
    }

    .dark-mode .an-s-processing {
        background: rgba(59, 130, 246, .18);
        color: #60a5fa;
    }

    .an-stat {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: transform .2s, box-shadow .2s;
    }

    .an-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, .06);
    }

    .an-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
    }

    .an-stat-val {
        font-size: 1.25rem;
        font-weight: 800;
        line-height: 1;
    }

    .an-stat-lbl {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .5px;
        opacity: .6;
        margin-top: 2px;
    }

    .an-stat-sub {
        font-size: .72rem;
        margin-top: 3px;
    }

    .an-filter {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 16px;
    }

    .an-filter .form-label {
        font-size: .75rem;
        font-weight: 600;
        margin-bottom: 3px;
    }

    .an-table th {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
        white-space: nowrap;
        cursor: pointer;
        user-select: none;
    }

    .an-table th:hover {
        opacity: .75;
    }

    .an-table td {
        font-size: .8rem;
        vertical-align: middle;
    }

    .an-table tbody tr:has(.dropdown.show) {
        background: var(--table-hover-bg, rgba(0, 0, 0, .02)) !important;
    }

    .actions-dropdown .dropdown-menu {
        position: fixed !important;
        z-index: 9999;
        min-width: 190px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .18);
        padding: 6px;
    }

    .actions-dropdown .dropdown-item {
        font-size: .82rem;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 14px;
        border-radius: 8px;
    }

    .actions-dropdown .dropdown-item i {
        width: 16px;
    }

    .an-pag .page-link {
        border-radius: 8px !important;
        margin: 0 2px;
        font-size: .8rem;
    }

    .an-empty {
        text-align: center;
        padding: 48px 24px;
        opacity: .4;
    }

    .an-empty i {
        font-size: 2.5rem;
        display: block;
        margin-bottom: 12px;
    }

    .view-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-color, #e8e8f0);
    }

    .view-info-row:last-child {
        border-bottom: none;
    }

    .view-info-lbl {
        font-size: .78rem;
        font-weight: 600;
        opacity: .6;
        min-width: 130px;
    }

    .view-info-val {
        font-size: .82rem;
        text-align: right;
    }

    /* Toast container para notificações de exportação */
    .toast-container {
        z-index: 9999;
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php require_once __DIR__ . '/../../include/sidebar.php'; ?>

        <div class="content w-100" id="mainContent">
            <?php require_once __DIR__ . '/../../include/navbar.php'; ?>

            <div class="container-fluid p-0">
                <!-- Cabeçalho -->
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1"><i class="bi bi-person-lines-fill me-2"></i>Analytics — Desempenho por
                            Artista</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item active text-white-stable">Artistas</li>
                            </ol>
                        </nav>
                    </div>
                    <?php if (hasPermission($admin_id, 'analytics.edit')): ?>
                    <div class="col-auto ms-auto">
                        <button class="btn btn-sm text-white" style="background:#FF0089;border-color:#FF0089"
                            id="exportDataBtn">
                            <i class="bi bi-download me-1"></i> Exportar (CSV/PDF)
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Feedback -->
                <?php if ($feedback): ?>
                <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3" role="alert">
                    <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                    <?php echo htmlspecialchars($feedback[2]); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Cards de estatísticas -->
                <div class="row g-3 mb-4">
                    <?php
                    $cards = [
                        ['icon' => 'bi-people', 'color' => '#FF0089', 'val' => number_format($total_artists), 'lbl' => 'Total Artistas', 'sub' => number_format($active_artists) . ' activos'],
                        ['icon' => 'bi-headphones', 'color' => '#3b82f6', 'val' => an_fmt_num($total_streams_all), 'lbl' => 'Total Streams', 'sub' => null],
                        ['icon' => 'bi-music-note-list', 'color' => '#8b5cf6', 'val' => number_format($tracks_with_streams), 'lbl' => 'Faixas com Dados', 'sub' => null],
                        ['icon' => 'bi-cash-coin', 'color' => '#f97316', 'val' => an_fmt_aoa($total_revenue_aoa), 'lbl' => 'Receita Total (AOA)', 'sub' => 'USD ' . number_format($total_revenue_usd, 2)],
                        ['icon' => 'bi-globe2', 'color' => '#22c55e', 'val' => number_format($unique_territories), 'lbl' => 'Países', 'sub' => null],
                        ['icon' => 'bi-bar-chart', 'color' => '#06b6d4', 'val' => an_fmt_num($avg_streams_per_artist), 'lbl' => 'Média/Artista', 'sub' => null],
                    ];
                    foreach ($cards as $c):
                    ?>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="an-stat">
                            <div class="an-stat-icon" style="background:<?php echo $c['color']; ?>22">
                                <i class="bi <?php echo $c['icon']; ?>" style="color:<?php echo $c['color']; ?>"></i>
                            </div>
                            <div>
                                <div class="an-stat-val"><?php echo $c['val']; ?></div>
                                <div class="an-stat-lbl"><?php echo $c['lbl']; ?></div>
                                <?php if ($c['sub']): ?>
                                <div class="an-stat-sub" style="color:<?php echo $c['color']; ?>">
                                    <?php echo $c['sub']; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filtros -->
                <div class="an-filter">
                    <form method="GET" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/artists"
                        id="filter-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">Artista</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="name"
                                    value="<?php echo htmlspecialchars($f_name); ?>" placeholder="Nome artístico" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">País</label>
                                <select class="form-select form-select-sm filter-instant" name="country">
                                    <option value="">Todos</option>
                                    <?php foreach ($countries as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c); ?>"
                                        <?php echo $f_country === $c ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <select class="form-select form-select-sm filter-instant" name="status">
                                    <option value="">Todos</option>
                                    <?php foreach (['active' => 'Activo', 'inactive' => 'Inactivo', 'blocked' => 'Bloqueado', 'processing' => 'A processar'] as $v => $l): ?>
                                    <option value="<?php echo $v; ?>" <?php echo $f_status === $v ? 'selected' : ''; ?>>
                                        <?php echo $l; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Streams (min)</label>
                                <input type="number" class="form-control form-control-sm filter-debounce" name="min_str"
                                    value="<?php echo $f_min_str > 0 ? $f_min_str : ''; ?>" placeholder="Mín" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Streams (max)</label>
                                <input type="number" class="form-control form-control-sm filter-debounce" name="max_str"
                                    value="<?php echo $f_max_str > 0 ? $f_max_str : ''; ?>" placeholder="Máx" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Período (de)</label>
                                <input type="date" class="form-control form-control-sm filter-debounce" name="date_from"
                                    value="<?php echo htmlspecialchars($f_date_from); ?>" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Período (até)</label>
                                <input type="date" class="form-control form-control-sm filter-debounce" name="date_to"
                                    value="<?php echo htmlspecialchars($f_date_to); ?>" />
                            </div>
                            <div class="col-md-2 d-flex gap-1">
                                <button type="submit" class="btn btn-sm text-white flex-fill"
                                    style="background:#FF0089;border-color:#FF0089">
                                    <i class="bi bi-search"></i>
                                </button>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/artists"
                                    class="btn btn-sm btn-outline-secondary" title="Limpar">
                                    <i class="bi bi-x"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tabela de artistas -->
                <div class="card p-0" style="border-radius:14px;overflow:hidden">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                        style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                        <span style="font-size:.82rem;font-weight:600">
                            <?php if ($total_filtered < $total_artists): ?>
                            <span style="color:#FF0089"><?php echo number_format($total_filtered); ?></span> de
                            <?php echo number_format($total_artists); ?> artistas
                            <?php else: ?>
                            <?php echo number_format($total_filtered); ?> artistas
                            <?php endif; ?>
                        </span>
                        <span style="font-size:.75rem;opacity:.5">
                            Pág. <?php echo $page; ?> / <?php echo $total_pages; ?>
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover an-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width:50px">
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'a.id_artist', 'dir' => $sort_col === 'a.id_artist' && $sort_dir === 'ASC' ? 'desc' : 'asc', 'page' => 1])); ?>"
                                            class="text-inherit text-decoration-none">
                                            ID<?php echo $sort_col === 'a.id_artist' ? ($sort_dir === 'ASC' ? ' ▲' : ' ▼') : ''; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'a.stage_name', 'dir' => $sort_col === 'a.stage_name' && $sort_dir === 'ASC' ? 'desc' : 'asc', 'page' => 1])); ?>"
                                            class="text-inherit text-decoration-none">
                                            Artista<?php echo $sort_col === 'a.stage_name' ? ($sort_dir === 'ASC' ? ' ▲' : ' ▼') : ''; ?>
                                        </a>
                                    </th>
                                    <th>País</th>
                                    <th>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'total_streams', 'dir' => $sort_col === 'total_streams' && $sort_dir === 'ASC' ? 'desc' : 'asc', 'page' => 1])); ?>"
                                            class="text-inherit text-decoration-none">
                                            Streams<?php echo $sort_col === 'total_streams' ? ($sort_dir === 'ASC' ? ' ▲' : ' ▼') : ''; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'total_revenue_aoa', 'dir' => $sort_col === 'total_revenue_aoa' && $sort_dir === 'ASC' ? 'desc' : 'asc', 'page' => 1])); ?>"
                                            class="text-inherit text-decoration-none">
                                            Receita
                                            (AOA)<?php echo $sort_col === 'total_revenue_aoa' ? ($sort_dir === 'ASC' ? ' ▲' : ' ▼') : ''; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'tracks_count', 'dir' => $sort_col === 'tracks_count' && $sort_dir === 'ASC' ? 'desc' : 'asc', 'page' => 1])); ?>"
                                            class="text-inherit text-decoration-none">
                                            Faixas<?php echo $sort_col === 'tracks_count' ? ($sort_dir === 'ASC' ? ' ▲' : ' ▼') : ''; ?>
                                        </a>
                                    </th>
                                    <th>Último Lançamento</th>
                                    <th>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'a.status_artist', 'dir' => $sort_col === 'a.status_artist' && $sort_dir === 'ASC' ? 'desc' : 'asc', 'page' => 1])); ?>"
                                            class="text-inherit text-decoration-none">
                                            Estado<?php echo $sort_col === 'a.status_artist' ? ($sort_dir === 'ASC' ? ' ▲' : ' ▼') : ''; ?>
                                        </a>
                                    </th>
                                    <th style="text-align:center;width:60px">Acções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($artists)): ?>
                                <tr>
                                    <td colspan="9">
                                        <div class="an-empty">
                                            <i class="bi bi-person-x"></i>
                                            <p class="mb-0 mt-2">Nenhum artista encontrado.</p>
                                            <?php if (hasPermission($admin_id, 'analytics.edit')): ?>
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/add"
                                                class="btn btn-sm mt-3 text-white" style="background:#FF0089">
                                                <i class="bi bi-plus-lg me-1"></i> Adicionar primeiro artista
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($artists as $i => $a):
                                        $user_name = trim($a['first_name'] . ' ' . ($a['second_name'] ?? ''));
                                        $is_even = $i % 2 === 1;
                                    ?>
                                <tr
                                    <?php echo $is_even ? 'style="background:var(--table-stripe,rgba(0,0,0,.015))"' : ''; ?>>
                                    <td><span
                                            style="font-family:monospace;font-size:.73rem;opacity:.55">#<?php echo $a['id_artist']; ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php echo an_avatar($a['stage_name'], $a['photo_artist'], 30); ?>
                                            <div>
                                                <div style="font-size:.8rem;font-weight:600">
                                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/view?id=<?php echo (int)$a['id_artist']; ?>"
                                                        class="text-inherit text-decoration-none">
                                                        <?php echo htmlspecialchars($a['stage_name']); ?>
                                                    </a>
                                                </div>
                                                <?php if ($a['real_name']): ?>
                                                <div style="font-size:.7rem;opacity:.5">
                                                    <?php echo htmlspecialchars($a['real_name']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($a['country'] ?? '—'); ?></td>
                                    <td style="font-size:.85rem;font-weight:700;color:#FF0089">
                                        <?php echo an_fmt_num((int)$a['total_streams']); ?></td>
                                    <td><?php echo an_fmt_aoa($a['total_revenue_aoa']); ?></td>
                                    <td><?php echo (int)$a['tracks_count']; ?></td>
                                    <td style="font-size:.78rem">
                                        <?php echo $a['last_release_date'] ? date('d/m/Y', strtotime($a['last_release_date'])) : '—'; ?>
                                    </td>
                                    <td><?php echo an_status_badge($a['status_artist']); ?></td>
                                    <td class="text-center">
                                        <div class="actions-dropdown dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button"
                                                data-bs-toggle="dropdown" data-bs-reference="toggle"
                                                aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="#"
                                                        onclick="viewArtist(<?php echo htmlspecialchars(json_encode([
                                                                                                                        'id'          => $a['id_artist'],
                                                                                                                        'stage_name'  => $a['stage_name'],
                                                                                                                        'real_name'   => $a['real_name'],
                                                                                                                        'country'     => $a['country'],
                                                                                                                        'city'        => $a['city'],
                                                                                                                        'status'      => $a['status_artist'],
                                                                                                                        'photo'       => $a['photo_artist'] ? APP_URL . '/assets/comprovantes/uploads/artists/' . $a['photo_artist'] : null,
                                                                                                                        'total_streams' => (int)$a['total_streams'],
                                                                                                                        'total_revenue_aoa' => $a['total_revenue_aoa'],
                                                                                                                        'tracks_count' => (int)$a['tracks_count'],
                                                                                                                        'last_release' => $a['last_release_date'],
                                                                                                                        'user_name'   => $user_name,
                                                                                                                        'user_email'  => $a['email_user'],
                                                                                                                        'creat_artist' => $a['creat_artist'],
                                                                                                                    ])); ?>, event); return false">
                                                        <i class="bi bi-eye text-info"></i> Visualizar
                                                    </a>
                                                </li>
                                                <?php if (hasPermission($admin_id, 'analytics.edit')): ?>
                                                <li>
                                                    <a class="dropdown-item"
                                                        href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/edit?id=<?php echo (int)$a['id_artist']; ?>">
                                                        <i class="bi bi-pencil text-warning"></i> Editar
                                                    </a>
                                                </li>
                                                <?php if ($a['status_artist'] === 'blocked'): ?>
                                                <li>
                                                    <a class="dropdown-item" href="#"
                                                        onclick="toggleBlockArtist(<?php echo (int)$a['id_artist']; ?>,'unblock'); return false">
                                                        <i class="bi bi-unlock text-success"></i> Desbloquear
                                                    </a>
                                                </li>
                                                <?php else: ?>
                                                <li>
                                                    <a class="dropdown-item" href="#"
                                                        onclick="toggleBlockArtist(<?php echo (int)$a['id_artist']; ?>,'block'); return false">
                                                        <i class="bi bi-lock text-warning"></i> Bloquear
                                                    </a>
                                                </li>
                                                <?php endif; ?>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-center py-3">
                        <nav>
                            <ul class="pagination pagination-sm an-pag mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link"
                                        href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"><i
                                            class="bi bi-chevron-left"></i></a>
                                </li>
                                <?php
                                    $ps = max(1, $page - 2);
                                    $pe = min($total_pages, $page + 2);
                                    if ($ps > 1): ?>
                                <li class="page-item"><a class="page-link"
                                        href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                                </li>
                                <?php if ($ps > 2): ?><li class="page-item disabled"><span class="page-link">…</span>
                                </li><?php endif; ?>
                                <?php endif; ?>
                                <?php for ($pi = $ps; $pi <= $pe; $pi++): ?>
                                <li class="page-item <?php echo $pi === $page ? 'active' : ''; ?>">
                                    <a class="page-link"
                                        href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pi])); ?>"><?php echo $pi; ?></a>
                                </li>
                                <?php endfor; ?>
                                <?php if ($pe < $total_pages): ?>
                                <?php if ($pe < $total_pages - 1): ?><li class="page-item disabled"><span
                                        class="page-link">…</span></li><?php endif; ?>
                                <li class="page-item"><a class="page-link"
                                        href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?php echo $total_pages; ?></a>
                                </li>
                                <?php endif; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link"
                                        href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"><i
                                            class="bi bi-chevron-right"></i></a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>

            </div><!-- /container-fluid -->
        </div><!-- /content -->
    </div><!-- /wrapper -->

    <!-- Modal Visualizar Artista -->
    <div class="modal fade" id="modalViewArtist" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:#FF0089">
                    <h5 class="modal-title text-white fw-bold"><i class="bi bi-person-lines-fill me-2"></i>Detalhes do
                        Artista</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="viewArtistBody">
                    <!-- Preenchido via JS -->
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-sm text-white" style="background:#FF0089" id="printArtistBtn">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para bloquear/desbloquear -->
    <div class="modal fade" id="modalToggleArtist" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalToggleTitle">Confirmar acção</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="modalToggleMsg" class="text-muted small mb-3"></p>
                    <input type="hidden" id="toggle_artist_id">
                    <input type="hidden" id="toggle_action">
                    <div id="toggle_password_wrap">
                        <label class="form-label fw-semibold small">Confirma a tua senha <span
                                class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="toggle_password" placeholder="Senha do admin">
                    </div>
                    <div class="alert alert-danger d-none mt-2 mb-0" id="toggle_error" style="font-size:.78rem"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-danger" id="btn_confirm_toggle">
                        <span class="normal-label">Confirmar</span>
                        <span class="loading-label d-none"><span class="spinner-border spinner-border-sm me-1"></span>A
                            processar…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de exportação -->
    <div class="modal fade" id="modalExport" tabindex="-1">
        <div class="modal-dialog modal-md">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:#22c55e">
                    <h5 class="modal-title text-white fw-bold"><i class="bi bi-download me-2"></i>Exportar Desempenho de
                        Artistas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-4">Os dados exportados respeitam os filtros aplicados na tabela.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Formato de exportação</label>
                        <select class="form-select" id="export_format">
                            <option value="csv">CSV (Excel)</option>
                            <option value="pdf">PDF (Relatório)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Incluir colunas</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="form-check"><input class="form-check-input" type="checkbox" id="col_id"
                                        checked> <label class="form-check-label">ID</label></div>
                            </div>
                            <div class="col-6">
                                <div class="form-check"><input class="form-check-input" type="checkbox" id="col_artist"
                                        checked> <label class="form-check-label">Artista</label></div>
                            </div>
                            <div class="col-6">
                                <div class="form-check"><input class="form-check-input" type="checkbox" id="col_country"
                                        checked> <label class="form-check-label">País</label></div>
                            </div>
                            <div class="col-6">
                                <div class="form-check"><input class="form-check-input" type="checkbox" id="col_streams"
                                        checked> <label class="form-check-label">Streams</label></div>
                            </div>
                            <div class="col-6">
                                <div class="form-check"><input class="form-check-input" type="checkbox" id="col_revenue"
                                        checked> <label class="form-check-label">Receita (AOA)</label></div>
                            </div>
                            <div class="col-6">
                                <div class="form-check"><input class="form-check-input" type="checkbox" id="col_tracks"
                                        checked> <label class="form-check-label">Faixas</label></div>
                            </div>
                            <div class="col-6">
                                <div class="form-check"><input class="form-check-input" type="checkbox"
                                        id="col_last_release" checked> <label class="form-check-label">Último
                                        Lançamento</label></div>
                            </div>
                            <div class="col-6">
                                <div class="form-check"><input class="form-check-input" type="checkbox" id="col_status"
                                        checked> <label class="form-check-label">Estado</label></div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mb-0 small">
                        <i class="bi bi-info-circle me-1"></i> Serão exportados <strong
                            id="exportCount"><?php echo number_format($total_filtered); ?></strong> registos.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm text-white" style="background:#22c55e" id="btn_do_export">
                        <i class="bi bi-download me-1"></i> Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="col-12 text-center py-2" style="font-size:.8rem">
                <p class="mb-0">© <?php echo date('Y'); ?> Wasom Upfy. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="">
            <div class="loader-progress"></div>
        </div>
    </div>

    <!-- Toast container para notificações -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
    (function() {
        'use strict';

        const BASE_URL = '<?php echo APP_URL; ?>';
        const ADMIN_PATH = '<?php echo ADMIN_PATH; ?>';
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const PROCESS = BASE_URL + '/' + ADMIN_PATH + '/analytics/process-artists';

        // ── Filtros com debounce ─────────────────────────────────────────────
        let dbt;
        document.querySelectorAll('.filter-debounce').forEach(el =>
            el.addEventListener('input', () => {
                clearTimeout(dbt);
                dbt = setTimeout(() => document.getElementById('filter-form').submit(), 500);
            })
        );
        document.querySelectorAll('.filter-instant').forEach(el =>
            el.addEventListener('change', () => document.getElementById('filter-form').submit())
        );

        // ── AJAX helper ──────────────────────────────────────────────────────
        async function postAction(payload) {
            const fd = new FormData();
            Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
            fd.append('csrf_token', CSRF);
            const r = await fetch(PROCESS, {
                method: 'POST',
                body: fd
            });
            return r.json();
        }

        // ── Helper para escapar HTML (aceita qualquer tipo) ───────────────────
        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            const s = String(str);
            return s.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        // ── Visualizar artista (modal) ──────────────────────────────────────
        let currentArtistData = null;
        window.viewArtist = function(data, event) {
            if (event) event.preventDefault();
            currentArtistData = data;
            const fmtNum = v => parseInt(v || 0).toLocaleString('pt-AO');
            const fmtAOA = v => 'Kz ' + parseFloat(v || 0).toLocaleString('pt-AO', {
                minimumFractionDigits: 2
            });
            const statusMap = {
                active: 'Activo',
                inactive: 'Inactivo',
                blocked: 'Bloqueado',
                processing: 'A processar'
            };

            document.getElementById('viewArtistBody').innerHTML = `
        <div class="row g-4">
            <div class="col-md-4 text-center">
                ${data.photo
                    ? `<img src="${data.photo}" class="img-fluid rounded-3 shadow mb-3" style="max-height:200px;object-fit:cover" onerror="this.src=''" alt="Foto">`
                    : `<div class="rounded-3 bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center mb-3" style="height:180px"><i class="bi bi-person-circle" style="font-size:3rem;opacity:.3"></i></div>`}
                <div class="fw-bold">${escapeHtml(data.stage_name)}</div>
                <div class="text-muted small">${escapeHtml(data.real_name || '—')}</div>
                <div class="mt-2">${data.status === 'active' ? '<span class="badge an-s-active">Activo</span>' : (data.status === 'blocked' ? '<span class="badge an-s-blocked">Bloqueado</span>' : '<span class="badge an-s-inactive">Inactivo</span>')}</div>
            </div>
            <div class="col-md-8">
                <div class="view-info-row"><span class="view-info-lbl">ID</span><span class="view-info-val">#${data.id}</span></div>
                <div class="view-info-row"><span class="view-info-lbl">País / Cidade</span><span class="view-info-val">${escapeHtml(data.country || '—')} ${data.city ? '/ ' + escapeHtml(data.city) : ''}</span></div>
                <div class="view-info-row"><span class="view-info-lbl">Proprietário</span><span class="view-info-val">${escapeHtml(data.user_name)} · ${escapeHtml(data.user_email)}</span></div>
                <div class="view-info-row"><span class="view-info-lbl">Total de Streams</span><span class="view-info-val fw-bold" style="color:#FF0089">${fmtNum(data.total_streams)}</span></div>
                <div class="view-info-row"><span class="view-info-lbl">Receita Total (AOA)</span><span class="view-info-val">${fmtAOA(data.total_revenue_aoa)}</span></div>
                <div class="view-info-row"><span class="view-info-lbl">Nº de Faixas</span><span class="view-info-val">${data.tracks_count}</span></div>
                <div class="view-info-row"><span class="view-info-lbl">Último Lançamento</span><span class="view-info-val">${data.last_release ? new Date(data.last_release).toLocaleDateString('pt-BR') : '—'}</span></div>
                <div class="view-info-row"><span class="view-info-lbl">Cadastrado em</span><span class="view-info-val">${new Date(data.creat_artist).toLocaleDateString('pt-BR')}</span></div>
            </div>
        </div>`;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalViewArtist')).show();
        };


        // ── Download PDF do artista (com html2pdf) ──────────────────────────────
        document.getElementById('printArtistBtn')?.addEventListener('click', async function() {
            if (!currentArtistData) return;
            const d = currentArtistData;
            const fmtNum = v => parseInt(v || 0).toLocaleString('pt-AO');
            const fmtAOA = v => 'Kz ' + parseFloat(v || 0).toLocaleString('pt-AO', {
                minimumFractionDigits: 2
            });

            // Função para garantir valores seguros
            const safe = (val) => {
                if (val === null || val === undefined) return '—';
                return escapeHtml(String(val));
            };

            // Construir HTML sem barras invertidas desnecessárias
            const pdfHtml = `
    <div style="font-family:Arial,sans-serif;max-width:680px;margin:auto;padding:24px">
        <div style="text-align:center;margin-bottom:24px;border-bottom:3px solid #FF0089;padding-bottom:16px">
            <h1 style="color:#FF0089;margin:0;font-size:1.4rem">WASOM UPFY</h1>
            <h2 style="color:#333;margin:4px 0 0;font-size:1rem;font-weight:400">Relatório de Artista</h2>
            <p style="color:#999;font-size:.8rem;margin:4px 0 0">Gerado em ${new Date().toLocaleString('pt-AO')}</p>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:.88rem">
            ${[
                ['Nome Artístico', safe(d.stage_name)],
                ['Nome Real', safe(d.real_name)],
                ['País / Cidade', `${safe(d.country)} ${d.city ? '/ ' + safe(d.city) : ''}`],
                ['Proprietário', safe(d.user_name)],
                ['E-mail', safe(d.user_email)],
                ['Total de Streams', fmtNum(d.total_streams)],
                ['Receita Total (AOA)', fmtAOA(d.total_revenue_aoa)],
                ['Nº de Faixas', d.tracks_count],
                ['Último Lançamento', d.last_release ? new Date(d.last_release).toLocaleDateString('pt-BR') : '—'],
                ['Estado', d.status === 'active' ? 'Activo' : (d.status === 'blocked' ? 'Bloqueado' : 'Inactivo')],
            ].map((r,i) => `<tr style="background:${i%2?'#f9f9f9':'#fff'}">
                <td style="padding:8px 12px;border:1px solid #eee;font-weight:600;color:#555;width:40%">${r[0]}</td>
                <td style="padding:8px 12px;border:1px solid #eee">${r[1]}</td>
            </tr>`).join('')}
        </table>
        <p style="color:#bbb;font-size:.72rem;margin-top:20px;text-align:center">Wasom Upfy v2.0 — Documento gerado automaticamente. Ref. #${d.id}</p>
    </div>`;

            const opt = {
                margin: 10,
                filename: `artista_${safe(d.stage_name).replace(/[^a-z0-9]/gi, '_')}.pdf`,
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2,
                    logging: false
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };
            try {
                if (typeof html2pdf === 'undefined') throw new Error('Biblioteca não carregada');
                await html2pdf().set(opt).from(pdfHtml).save();
                showToast('success', 'PDF Gerado', 'Relatório descarregado.');
            } catch (e) {
                console.error(e);
                showToast('error', 'Erro', 'Não foi possível gerar o PDF.');
            }
        });

        // ── Bloquear / Desbloquear artista ───────────────────────────────────
        window.toggleBlockArtist = async function(id, action) {
            const label = action === 'block' ? 'bloquear' : 'desbloquear';
            const msg = action === 'block' ?
                'O artista ficará bloqueado e não poderá lançar novas músicas.' :
                'O artista voltará a ter acesso completo.';
            const result = await Swal.fire({
                title: label.charAt(0).toUpperCase() + label.slice(1) + ' artista?',
                text: msg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: action === 'block' ? '#ef4444' : '#22c55e',
                confirmButtonText: 'Sim, ' + label,
                cancelButtonText: 'Cancelar'
            });
            if (!result.isConfirmed) return;

            document.getElementById('toggle_artist_id').value = id;
            document.getElementById('toggle_action').value = action;
            document.getElementById('toggle_password').value = '';
            document.getElementById('toggle_error').classList.add('d-none');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalToggleArtist')).show();
        };

        document.getElementById('btn_confirm_toggle')?.addEventListener('click', async function() {
            const id = document.getElementById('toggle_artist_id').value;
            const action = document.getElementById('toggle_action').value;
            const password = document.getElementById('toggle_password').value;
            const errEl = document.getElementById('toggle_error');
            if (!password) {
                errEl.textContent = 'A senha é obrigatória.';
                errEl.classList.remove('d-none');
                return;
            }
            errEl.classList.add('d-none');
            setLoading(this, true);
            try {
                const data = await postAction({
                    action: 'toggle_block_artist',
                    id_artist: id,
                    block_action: action,
                    password_confirm: password
                });
                if (data.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('modalToggleArtist')).hide();
                    location.href = location.pathname + '?msg=' + (action === 'block' ?
                        'artist_blocked' : 'artist_unblocked');
                } else {
                    errEl.textContent = data.message;
                    errEl.classList.remove('d-none');
                }
            } catch {
                errEl.textContent = 'Erro de ligação.';
                errEl.classList.remove('d-none');
            }
            setLoading(this, false);
        });

        // ── Exportação (CSV / PDF) com dados do servidor ─────────────────────
        document.getElementById('exportDataBtn')?.addEventListener('click', function() {
            const count = <?php echo $total_filtered; ?>;
            document.getElementById('exportCount').innerText = count.toLocaleString('pt-BR');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalExport')).show();
        });

        // Função para exportar CSV
        function exportToCSV(data, columns, filename) {
            const headers = columns.map(col => col.label);
            const rows = data.map(row => columns.map(col => row[col.key] ?? ''));
            const csv = [headers.join(';'), ...rows.map(row => row.map(cell =>
                `"${String(cell).replace(/"/g, '""')}"`).join(';'))].join('\n');
            const blob = new Blob(["\uFEFF" + csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        // Função para exportar PDF (usando html2pdf)
        async function exportToPDF(data, columns, filename) {
            const headers = columns.map(col => col.label);
            const rows = data.map(row => columns.map(col => row[col.key] ?? ''));
            const html = `
            <div style="font-family:Arial,sans-serif;padding:20px">
                <div style="text-align:center;margin-bottom:30px">
                    <h1 style="color:#FF0089">WASOM UPFY</h1>
                    <h2>Relatório de Desempenho por Artista</h2>
                    <p>Gerado em ${new Date().toLocaleString('pt-AO')}</p>
                </div>
                <table border="1" cellpadding="8" cellspacing="0" style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="background:#FF0089;color:white">
                            ${headers.map(h => `<th>${escapeHtml(h)}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map(row => `<tr>${row.map(cell => `<td>${escapeHtml(cell)}</td>`).join('')}</tr>`).join('')}
                    </tbody>
                </table>
                <p style="margin-top:20px;font-size:12px;color:#666">© Wasom Upfy – Documento gerado automaticamente</p>
            </div>`;
            const opt = {
                margin: 10,
                filename: filename,
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'landscape'
                }
            };
            await html2pdf().set(opt).from(html).save();
        }

        document.getElementById('btn_do_export')?.addEventListener('click', async function() {
            const format = document.getElementById('export_format').value;
            const columns = [];
            if (document.getElementById('col_id').checked) columns.push({
                key: 'id',
                label: 'ID'
            });
            if (document.getElementById('col_artist').checked) columns.push({
                key: 'stage_name',
                label: 'Artista'
            });
            if (document.getElementById('col_country').checked) columns.push({
                key: 'country',
                label: 'País'
            });
            if (document.getElementById('col_streams').checked) columns.push({
                key: 'total_streams',
                label: 'Streams'
            });
            if (document.getElementById('col_revenue').checked) columns.push({
                key: 'total_revenue_aoa',
                label: 'Receita (AOA)'
            });
            if (document.getElementById('col_tracks').checked) columns.push({
                key: 'tracks_count',
                label: 'Faixas'
            });
            if (document.getElementById('col_last_release').checked) columns.push({
                key: 'last_release',
                label: 'Último Lançamento'
            });
            if (document.getElementById('col_status').checked) columns.push({
                key: 'status_artist',
                label: 'Estado'
            });

            if (columns.length === 0) {
                showToast('warning', 'Nenhuma coluna selecionada',
                    'Seleccione pelo menos uma coluna para exportar.');
                return;
            }

            setLoading(this, true);
            try {
                // Obter dados filtrados do servidor
                const response = await postAction({
                    action: 'export_data',
                    filters: <?php echo json_encode($_GET); ?>
                });
                if (response.ok && response.data) {
                    const exportData = response.data;
                    if (format === 'csv') {
                        exportToCSV(exportData, columns, 'desempenho_artistas.csv');
                    } else if (format === 'pdf') {
                        await exportToPDF(exportData, columns, 'desempenho_artistas.pdf');
                    }
                    showToast('success', 'Exportação concluída', 'Ficheiro descarregado.');
                    bootstrap.Modal.getInstance(document.getElementById('modalExport')).hide();
                } else {
                    showToast('error', 'Erro', response.message || 'Falha ao obter dados.');
                }
            } catch (e) {
                console.error(e);
                showToast('error', 'Erro', 'Não foi possível comunicar com o servidor.');
            } finally {
                setLoading(this, false);
            }
        });

        // ── Helper de loading ────────────────────────────────────────────────
        function setLoading(btn, state) {
            const normal = btn.querySelector('.normal-label');
            const loading = btn.querySelector('.loading-label');
            if (normal) normal.classList.toggle('d-none', state);
            if (loading) loading.classList.toggle('d-none', !state);
            btn.disabled = state;
        }

        // ── Toast personalizado ──────────────────────────────────────────────
        function showToast(type, title, message) {
            const toastContainer = document.querySelector('.toast-container');
            const toastId = 'toast-' + Date.now();
            const bgClass = type === 'success' ? 'bg-success' : (type === 'warning' ? 'bg-warning' : 'bg-danger');
            const html = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${escapeHtml(title)}</strong><br>${escapeHtml(message)}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
            toastContainer.insertAdjacentHTML('beforeend', html);
            const toastEl = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        }
    })();
    </script>
</body>

</html>