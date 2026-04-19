<?php
// ═══════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Analytics: Visão Geral de Streams
// Arquivo: wu-panel/pages/analytics/home.php
// Rota:    wu-panel/analytics
// ═══════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// ── Feedback ──────────────────────────────────────────────────────────────
$msg      = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'added'     => ['success', 'bi-check-circle',    'Dados de stream adicionados com sucesso.'],
    'updated'   => ['success', 'bi-check-circle',    'Stream actualizado com sucesso.'],
    'deleted'   => ['success', 'bi-trash',            'Stream removido com sucesso.'],
    'blocked'   => ['warning', 'bi-lock',             'Track bloqueada. Não aparece no TOP 5.'],
    'unblocked' => ['success', 'bi-unlock',           'Track desbloqueada.'],
    'error'     => ['danger',  'bi-x-circle',         'Ocorreu um erro. Tenta novamente.'],
    default     => null,
};

// ── Helpers ───────────────────────────────────────────────────────────────
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
        'active', 'approved' => '<span class="badge an-s-active">Activa</span>',
        'inactive'          => '<span class="badge an-s-inactive">Inactiva</span>',
        'blocked'           => '<span class="badge an-s-blocked">Bloqueada</span>',
        'processing'        => '<span class="badge an-s-processing">A processar</span>',
        default             => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
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
        return '<img src="' . APP_URL . '/assets/comprovantes/uploads/users/' . htmlspecialchars($photo) . '"
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

// ── Stats cards ───────────────────────────────────────────────────────────
$total_streams   = (int)$db->query("SELECT COALESCE(SUM(streams),0) FROM _stream")->fetchColumn();
$total_downloads = (int)$db->query("SELECT COALESCE(SUM(downloads),0) FROM _stream")->fetchColumn();
$unique_tracks   = (int)$db->query("SELECT COUNT(DISTINCT id_track) FROM _stream")->fetchColumn();
$unique_platforms = (int)$db->query("SELECT COUNT(DISTINCT id_store) FROM _stream")->fetchColumn();
$unique_territories = (int)$db->query("SELECT COUNT(DISTINCT country_code) FROM _stream_country WHERE country_code IS NOT NULL")->fetchColumn();
$total_revenue_usd = (float)$db->query("SELECT COALESCE(SUM(revenue),0) FROM _stream")->fetchColumn();
$usd_rate        = (float)($db->query("SELECT usd_to_aoa_rate FROM _platform LIMIT 1")->fetchColumn() ?: 900);
$total_revenue_aoa = $total_revenue_usd * $usd_rate;
$streams_this_month = (int)$db->query("SELECT COALESCE(SUM(streams),0) FROM _stream WHERE year_stream=YEAR(NOW()) AND month_stream=MONTH(NOW())")->fetchColumn();
$blocked_tracks  = (int)$db->query("SELECT COUNT(*) FROM _track WHERE status_track='blocked'")->fetchColumn();

// ── Filtros ───────────────────────────────────────────────────────────────
$per_page  = 15;
$page      = max(1, (int)($_GET['page'] ?? 1));
$f_id      = trim($_GET['id']       ?? '');
$f_account = trim($_GET['account']  ?? '');
$f_track   = trim($_GET['track']    ?? '');
$f_artist  = trim($_GET['artist']   ?? '');
$f_store   = trim($_GET['store']    ?? '');
$f_status  = trim($_GET['status']   ?? '');
$f_year    = trim($_GET['year']     ?? '');
$sort_col  = in_array($_GET['sort'] ?? '', ['s.id_stream', 't.title_track', 's.streams', 's.year_stream', 's.revenue'])
    ? $_GET['sort'] : 's.id_stream';
$sort_dir  = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$where  = [];
$params = [];

if ($f_id !== '') {
    $where[]  = 's.id_stream = ?';
    $params[] = (int)$f_id;
}
if ($f_account !== '') {
    $where[]  = "CONCAT(u.first_name,' ',COALESCE(u.second_name,'')) LIKE ?";
    $params[] = '%' . $f_account . '%';
}
if ($f_track !== '') {
    $where[]  = 't.title_track LIKE ?';
    $params[] = '%' . $f_track . '%';
}
if ($f_artist !== '') {
    $where[]  = "(COALESCE(ar.stage_name, u.name_artist_band, u.first_name)) LIKE ?";
    $params[] = '%' . $f_artist . '%';
}
if ($f_store !== '') {
    $where[]  = 'st.id_store = ?';
    $params[] = (int)$f_store;
}
if ($f_status !== '') {
    $where[]  = 't.status_track = ?';
    $params[] = $f_status;
}
if ($f_year !== '') {
    $where[]  = 's.year_stream = ?';
    $params[] = (int)$f_year;
}

$base_joins = "
    FROM _stream s
    JOIN _track t ON t.id_track = s.id_track
    JOIN _album al ON al.id_album = t.id_album
    JOIN _users u ON u.id_users = al.id_users
    LEFT JOIN _artist ar ON ar.id_artist = al.id_artist
    JOIN _store st ON st.id_store = s.id_store
    LEFT JOIN _user_plan up ON up.id_users = u.id_users AND up.status_plan = 'active'
    LEFT JOIN _plans pl ON pl.id_plan = up.id_plan
";
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// contagem
$count_stmt = $db->prepare("SELECT COUNT(DISTINCT s.id_stream) $base_joins $sql_where");
$count_stmt->execute($params);
$total_filtered = (int)$count_stmt->fetchColumn();
$total_pages    = max(1, (int)ceil($total_filtered / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;

// dados
$data_stmt = $db->prepare("
    SELECT
        s.id_stream, s.streams, s.downloads, s.revenue,
        s.year_stream, s.month_stream,
        t.id_track, t.title_track, t.status_track, t.isrc,
        al.id_album, al.title_album, al.img_cover, al.type_album,
        u.id_users, u.first_name, u.second_name, u.email_user,
        u.photo_user, u.name_artist_band,
        COALESCE(ar.stage_name, u.name_artist_band, u.first_name) AS artist_name,
        st.id_store, st.name_store, st.slug_store, st.logo_store,
        pl.name_plan,
        (SELECT GROUP_CONCAT(DISTINCT sc.country_name ORDER BY sc.streams DESC SEPARATOR ', ')
         FROM _stream_country sc WHERE sc.id_track = t.id_track
         AND sc.year_stream = s.year_stream AND sc.month_stream = s.month_stream
         LIMIT 3) AS territories,
        (SELECT COUNT(DISTINCT sc.country_code) FROM _stream_country sc WHERE sc.id_track = t.id_track) AS territory_count
    $base_joins
    $sql_where
    ORDER BY $sort_col $sort_dir
    LIMIT $per_page OFFSET $offset
");
$data_stmt->execute($params);
$streams = $data_stmt->fetchAll();

// ── Dados para modais ─────────────────────────────────────────────────────
// Lojas para select
$stores = $db->query("SELECT id_store, name_store, slug_store FROM _store WHERE is_active=1 ORDER BY name_store")->fetchAll();
// Faixas para select add (com artista e álbum)
$tracks_for_select = $db->query("
    SELECT t.id_track,
           CONCAT(t.title_track,' — ',COALESCE(ar.stage_name,u.name_artist_band,u.first_name),' (',al.type_album,')') AS label
    FROM _track t
    JOIN _album al ON al.id_album = t.id_album
    JOIN _users u ON u.id_users = al.id_users
    LEFT JOIN _artist ar ON ar.id_artist = al.id_artist
    WHERE t.status_track IN ('active','approved')
    ORDER BY t.creat_track DESC
    LIMIT 200
")->fetchAll();
// Anos para filtro
$years = $db->query("SELECT DISTINCT year_stream FROM _stream ORDER BY year_stream DESC")->fetchAll(PDO::FETCH_COLUMN);

$csrf = $_SESSION['admin_csrf_token'];

// ── Gráfico — streams por mês (últimos 12) ────────────────────────────────
$chart_raw = $db->query("
    SELECT CONCAT(year_stream,'-',LPAD(month_stream,2,'0')) AS ym,
           SUM(streams) AS total_streams, SUM(revenue) AS total_revenue
    FROM _stream
    WHERE (year_stream > YEAR(DATE_SUB(NOW(),INTERVAL 12 MONTH)))
       OR (year_stream = YEAR(DATE_SUB(NOW(),INTERVAL 12 MONTH)) AND month_stream >= MONTH(DATE_SUB(NOW(),INTERVAL 12 MONTH)))
    GROUP BY ym ORDER BY ym ASC
")->fetchAll(PDO::FETCH_UNIQUE);

$chart_labels = $chart_streams = $chart_revenue_chart = [];
for ($i = 11; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $chart_labels[]         = date('M/y', strtotime($ym . '-01'));
    $chart_streams[]        = (int)($chart_raw[$ym]['total_streams'] ?? 0);
    $chart_revenue_chart[]  = round(((float)($chart_raw[$ym]['total_revenue'] ?? 0)) * $usd_rate, 2);
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title>Analytics — Visão Geral — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        /* ── Status badges ── */
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

        /* ── Stat cards ── */
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

        /* ── Filtros ── */
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

        /* ── Tabela ── */
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

        /* ── Acções dropdown ── */
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

        /* ── Gráfico ── */
        .chart-wrapper {
            position: relative;
            height: 220px;
        }

        /* ── Paginação ── */
        .an-pag .page-link {
            border-radius: 8px !important;
            margin: 0 2px;
            font-size: .8rem;
        }

        /* ── Empty ── */
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

        /* ── Capa miniatura ── */
        .an-cover {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .an-cover-ph {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            background: rgba(255, 0, 137, .1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* ── Store badge ── */
        .store-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: .73rem;
            font-weight: 600;
            background: rgba(255, 0, 137, .08);
            color: #FF0089;
        }

        /* ── Modal view ── */
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

        /* ── Print styles (PDF) ── */
        @media print {
            body>*:not(#printArea) {
                display: none !important;
            }

            #printArea {
                display: block !important;
                position: static;
                font-family: Arial, sans-serif;
            }

            .no-print {
                display: none !important;
            }

            .modal-backdrop {
                display: none !important;
            }
        }

        #printArea {
            display: none;
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
                        <h2 class="h4 mb-1"><i class="bi bi-bar-chart-line me-2"></i>Analytics — Visão Geral</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>" class="text-secondary">Home</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable">Analytics</li>
                            </ol>
                        </nav>
                    </div>
                    <?php if (hasPermission($admin_id, 'analytics.edit')): ?>
                        <div class="col-auto ms-auto">
                            <button class="btn btn-sm text-white" style="background:#FF0089;border-color:#FF0089"
                                data-bs-toggle="modal" data-bs-target="#modalAddStream">
                                <i class="bi bi-plus-lg me-1"></i> Adicionar Dados
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

                <!-- ── Stat cards ── -->
                <div class="row g-3 mb-4">
                    <?php
                    $cards = [
                        ['icon' => 'bi-headphones', 'color' => '#FF0089', 'val' => an_fmt_num($total_streams), 'lbl' => 'Total de Streams', 'sub' => an_fmt_num($streams_this_month) . ' este mês'],
                        ['icon' => 'bi-download', 'color' => '#3b82f6', 'val' => an_fmt_num($total_downloads), 'lbl' => 'Total Downloads', 'sub' => null],
                        ['icon' => 'bi-music-note-list', 'color' => '#8b5cf6', 'val' => number_format($unique_tracks), 'lbl' => 'Faixas com Dados', 'sub' => null],
                        ['icon' => 'bi-shop', 'color' => '#06b6d4', 'val' => number_format($unique_platforms), 'lbl' => 'Plataformas', 'sub' => null],
                        ['icon' => 'bi-globe2', 'color' => '#22c55e', 'val' => number_format($unique_territories), 'lbl' => 'Territórios', 'sub' => null],
                        ['icon' => 'bi-cash-coin', 'color' => '#f97316', 'val' => an_fmt_aoa($total_revenue_aoa), 'lbl' => 'Receita (AOA)', 'sub' => 'USD ' . number_format($total_revenue_usd, 2)],
                        ['icon' => 'bi-lock', 'color' => '#ef4444', 'val' => number_format($blocked_tracks), 'lbl' => 'Tracks Bloqueadas', 'sub' => 'Fora do TOP 5'],
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

                <!-- ── Gráfico ── -->
                <div class="card mb-4">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                        style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                        <span style="font-weight:600;font-size:.88rem">
                            <i class="bi bi-graph-up me-1"></i> Streams e Receita — Últimos 12 meses
                        </span>
                        <div class="d-flex gap-3" style="font-size:.73rem">
                            <span><span
                                    style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#FF0089;margin-right:4px"></span>Streams</span>
                            <span><span
                                    style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#f97316;margin-right:4px"></span>Receita
                                (AOA)</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrapper"><canvas id="analyticsChart"></canvas></div>
                    </div>
                </div>

                <!-- ── Filtros ── -->
                <div class="an-filter">
                    <form method="GET" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics" id="filter-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-1">
                                <label class="form-label">ID</label>
                                <input type="number" class="form-control form-control-sm" name="id"
                                    value="<?php echo htmlspecialchars($f_id); ?>" placeholder="#" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Conta</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="account"
                                    value="<?php echo htmlspecialchars($f_account); ?>"
                                    placeholder="Nome do utilizador" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Faixa</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="track"
                                    value="<?php echo htmlspecialchars($f_track); ?>" placeholder="Título da faixa" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Artista</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="artist"
                                    value="<?php echo htmlspecialchars($f_artist); ?>" placeholder="Nome do artista" />
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Plataforma</label>
                                <select class="form-select form-select-sm filter-instant" name="store">
                                    <option value="">Todas</option>
                                    <?php foreach ($stores as $st): ?>
                                        <option value="<?php echo $st['id_store']; ?>"
                                            <?php echo $f_store == $st['id_store'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($st['name_store']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Estado</label>
                                <select class="form-select form-select-sm filter-instant" name="status">
                                    <option value="">Todos</option>
                                    <?php foreach (['active' => 'Activa', 'inactive' => 'Inactiva', 'blocked' => 'Bloqueada', 'processing' => 'A processar'] as $v => $l): ?>
                                        <option value="<?php echo $v; ?>" <?php echo $f_status === $v ? 'selected' : ''; ?>>
                                            <?php echo $l; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Ano</label>
                                <select class="form-select form-select-sm filter-instant" name="year">
                                    <option value="">Todos</option>
                                    <?php foreach ($years as $y): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $f_year == $y ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex gap-1">
                                <button type="submit" class="btn btn-sm text-white flex-fill"
                                    style="background:#FF0089;border-color:#FF0089">
                                    <i class="bi bi-search"></i>
                                </button>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics"
                                    class="btn btn-sm btn-outline-secondary" title="Limpar">
                                    <i class="bi bi-x"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ── Tabela ── -->
                <div class="card p-0" style="border-radius:14px;overflow:hidden">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                        style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                        <span style="font-size:.82rem;font-weight:600">
                            <?php if ($total_filtered < $total_streams): ?>
                                <span style="color:#FF0089"><?php echo number_format($total_filtered); ?></span>
                                de <?php echo number_format($total_streams); ?> registos
                            <?php else: ?>
                                <?php echo number_format($total_filtered); ?> registos
                            <?php endif; ?>
                        </span>
                        <span style="font-size:.75rem;opacity:.5">
                            Pág. <?php echo $page; ?> / <?php echo $total_pages; ?>
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover an-table mb-0" id="an-table">
                            <thead>
                                <tr>
                                    <th style="width:50px">
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 's.id_stream', 'dir' => $sort_col === 's.id_stream' && $sort_dir === 'ASC' ? 'desc' : 'asc', 'page' => 1])); ?>"
                                            class="text-inherit text-decoration-none">
                                            ID<?php echo $sort_col === 's.id_stream' ? ($sort_dir === 'ASC' ? ' ▲' : ' ▼') : ''; ?>
                                        </a>
                                    </th>
                                    <th>Conta</th>
                                    <th>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 't.title_track', 'dir' => $sort_col === 't.title_track' && $sort_dir === 'ASC' ? 'desc' : 'asc', 'page' => 1])); ?>"
                                            class="text-inherit text-decoration-none">
                                            Faixa<?php echo $sort_col === 't.title_track' ? ($sort_dir === 'ASC' ? ' ▲' : ' ▼') : ''; ?>
                                        </a>
                                    </th>
                                    <th>Artista</th>
                                    <th>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 's.streams', 'dir' => $sort_col === 's.streams' && $sort_dir === 'ASC' ? 'desc' : 'asc', 'page' => 1])); ?>"
                                            class="text-inherit text-decoration-none">
                                            Streams<?php echo $sort_col === 's.streams' ? ($sort_dir === 'ASC' ? ' ▲' : ' ▼') : ''; ?>
                                        </a>
                                    </th>
                                    <th>Plataforma</th>
                                    <th>Territórios</th>
                                    <th>Período</th>
                                    <th>Estado</th>
                                    <th style="text-align:center;width:60px">Acções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($streams)): ?>
                                    <tr>
                                        <td colspan="10">
                                            <div class="an-empty">
                                                <i class="bi bi-bar-chart"></i>
                                                <p class="mb-0 mt-2">Nenhum dado de stream encontrado.</p>
                                                <?php if (hasPermission($admin_id, 'analytics.edit')): ?>
                                                    <button class="btn btn-sm mt-3 text-white" style="background:#FF0089"
                                                        data-bs-toggle="modal" data-bs-target="#modalAddStream">
                                                        <i class="bi bi-plus-lg me-1"></i> Adicionar primeiro registo
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($streams as $i => $s):
                                        $user_name  = trim($s['first_name'] . ' ' . ($s['second_name'] ?? ''));
                                        $is_even    = $i % 2 === 1;
                                        $cover_url  = $s['img_cover']
                                            ? APP_URL . '/assets/comprovantes/uploads/artists/' . $s['img_cover']
                                            : null;
                                    ?>
                                        <tr
                                            <?php echo $is_even ? 'style="background:var(--table-stripe,rgba(0,0,0,.015))"' : ''; ?>>
                                            <!-- ID -->
                                            <td><span
                                                    style="font-family:monospace;font-size:.73rem;opacity:.55">#<?php echo $s['id_stream']; ?></span>
                                            </td>
                                            <!-- Conta -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php echo an_avatar($user_name, $s['photo_user'], 30); ?>
                                                    <div>
                                                        <div style="font-size:.8rem;font-weight:600">
                                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo (int)$s['id_users']; ?>"
                                                                class="text-inherit text-decoration-none">
                                                                <?php echo htmlspecialchars($user_name); ?>
                                                            </a>
                                                        </div>
                                                        <div style="font-size:.7rem;opacity:.5">
                                                            <?php echo htmlspecialchars($s['name_plan'] ?? '—'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <!-- Faixa -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if ($cover_url): ?>
                                                        <img src="<?php echo $cover_url; ?>" class="an-cover" alt=""
                                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                                        <div class="an-cover-ph" style="display:none">
                                                            <i class="bi bi-music-note" style="color:#FF0089;font-size:.9rem"></i>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="an-cover-ph">
                                                            <i class="bi bi-music-note" style="color:#FF0089;font-size:.9rem"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div style="font-size:.8rem;font-weight:600">
                                                            <?php echo htmlspecialchars($s['title_track']); ?>
                                                        </div>
                                                        <div style="font-size:.7rem;opacity:.5">
                                                            <?php echo htmlspecialchars($s['type_album']); ?>
                                                            <?php echo $s['isrc'] ? ' · ' . $s['isrc'] : ''; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <!-- Artista -->
                                            <td style="font-size:.8rem;font-weight:500">
                                                <?php echo htmlspecialchars($s['artist_name']); ?>
                                            </td>
                                            <!-- Streams -->
                                            <td>
                                                <div style="font-size:.85rem;font-weight:700;color:#FF0089">
                                                    <?php echo an_fmt_num((int)$s['streams']); ?>
                                                </div>
                                                <?php if ((int)$s['downloads'] > 0): ?>
                                                    <div style="font-size:.7rem;opacity:.5">
                                                        <i class="bi bi-download"></i>
                                                        <?php echo an_fmt_num((int)$s['downloads']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <!-- Plataforma -->
                                            <td>
                                                <span class="store-pill">
                                                    <i class="bi bi-shop"></i>
                                                    <?php echo htmlspecialchars($s['name_store']); ?>
                                                </span>
                                            </td>
                                            <!-- Territórios -->
                                            <td style="font-size:.76rem;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                                title="<?php echo htmlspecialchars($s['territories'] ?? '—'); ?>">
                                                <?php
                                                if ((int)$s['territory_count'] > 0) {
                                                    echo '<span class="badge" style="background:rgba(34,197,94,.12);color:#166534;font-size:.7rem">';
                                                    echo (int)$s['territory_count'];
                                                    echo ' país' . ((int)$s['territory_count'] !== 1 ? 'es' : '');
                                                    echo '</span>';
                                                } else {
                                                    echo '<span style="opacity:.4">—</span>';
                                                }
                                                ?>
                                            </td>
                                            <!-- Período -->
                                            <td style="font-size:.78rem;white-space:nowrap">
                                                <?php echo str_pad((int)$s['month_stream'], 2, '0', STR_PAD_LEFT) . '/' . $s['year_stream']; ?>
                                            </td>
                                            <!-- Estado -->
                                            <td><?php echo an_status_badge($s['status_track']); ?></td>
                                            <!-- Acções -->
                                            <td class="text-center">
                                                <div class="actions-dropdown dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button"
                                                        data-bs-toggle="dropdown" data-bs-reference="toggle"
                                                        aria-expanded="false">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <!-- Visualizar -->
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="viewStream(<?php echo htmlspecialchars(json_encode([
                                                                                                                        'id'         => $s['id_stream'],
                                                                                                                        'track'      => $s['title_track'],
                                                                                                                        'artist'     => $s['artist_name'],
                                                                                                                        'album'      => $s['title_album'],
                                                                                                                        'type'       => $s['type_album'],
                                                                                                                        'isrc'       => $s['isrc'] ?? '',
                                                                                                                        'cover'      => $cover_url ?? '',
                                                                                                                        'store'      => $s['name_store'],
                                                                                                                        'streams'    => $s['streams'],
                                                                                                                        'downloads'  => $s['downloads'],
                                                                                                                        'revenue'    => $s['revenue'],
                                                                                                                        'revenue_aoa' => round((float)$s['revenue'] * $usd_rate, 2),
                                                                                                                        'territories' => $s['territories'] ?? '—',
                                                                                                                        'terr_count' => $s['territory_count'],
                                                                                                                        'period'     => str_pad((int)$s['month_stream'], 2, '0', STR_PAD_LEFT) . '/' . $s['year_stream'],
                                                                                                                        'user'       => $user_name,
                                                                                                                        'email'      => $s['email_user'],
                                                                                                                        'plan'       => $s['name_plan'] ?? '—',
                                                                                                                        'status'     => $s['status_track'],
                                                                                                                    ])); ?>, event);return false">
                                                                <i class="bi bi-eye text-info"></i> Visualizar
                                                            </a>
                                                        </li>
                                                        <?php if (hasPermission($admin_id, 'analytics.edit')): ?>
                                                            <!-- Editar -->
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="editStream(<?php echo htmlspecialchars(json_encode([
                                                                                                                            'id'       => $s['id_stream'],
                                                                                                                            'track'    => $s['title_track'],
                                                                                                                            'store'    => $s['name_store'],
                                                                                                                            'streams'  => $s['streams'],
                                                                                                                            'downloads' => $s['downloads'],
                                                                                                                            'revenue'  => $s['revenue'],
                                                                                                                            'year'     => $s['year_stream'],
                                                                                                                            'month'    => $s['month_stream'],
                                                                                                                        ])); ?>, event);return false">
                                                                    <i class="bi bi-pencil text-warning"></i> Editar
                                                                </a>
                                                            </li>
                                                            <!-- Bloquear / Desbloquear -->
                                                            <?php if ($s['status_track'] === 'blocked'): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="#"
                                                                        onclick="toggleBlock(<?php echo (int)$s['id_stream']; ?>,'unblock');return false">
                                                                        <i class="bi bi-unlock text-success"></i> Desbloquear Track
                                                                    </a>
                                                                </li>
                                                            <?php else: ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="#"
                                                                        onclick="toggleBlock(<?php echo (int)$s['id_stream']; ?>,'block');return false">
                                                                        <i class="bi bi-lock text-warning"></i> Bloquear Track
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            <li>
                                                                <hr class="dropdown-divider my-1">
                                                            </li>
                                                            <!-- Excluir -->
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="#"
                                                                    onclick="deleteStream(<?php echo (int)$s['id_stream']; ?>, '<?php echo htmlspecialchars(addslashes($s['title_track'])); ?>');return false">
                                                                    <i class="bi bi-trash"></i> Excluir
                                                                </a>
                                                            </li>
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
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
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
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>

            </div><!-- /container-fluid -->
        </div><!-- /content -->
    </div><!-- /wrapper -->

    <!-- ════════════════════════════════════════════════════════════════════════
     MODAL — Visualizar
════════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalViewStream" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:#FF0089">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="bi bi-bar-chart-line me-2"></i>Detalhes do Stream
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="viewStreamBody">
                    <!-- preenchido pelo JS -->
                </div>
                <div class="modal-footer border-0 no-print">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-sm text-white" style="background:#FF0089"
                        onclick="printStreamPDF()">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════
     MODAL — Editar Stream
════════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalEditStream" tabindex="-1">
        <div class="modal-dialog modal-md">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:#f97316">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="bi bi-pencil me-2"></i>Editar Dados de Stream
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="edit_stream_id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Faixa</label>
                        <input type="text" class="form-control" id="edit_stream_track" readonly
                            style="background:transparent;opacity:.7">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Plataforma</label>
                        <input type="text" class="form-control" id="edit_stream_store_name" readonly
                            style="background:transparent;opacity:.7">
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Ano <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_stream_year" min="2020" max="2099"
                                required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Mês <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_stream_month" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>">
                                        <?php echo str_pad($m, 2, '0', STR_PAD_LEFT) . ' — ' . date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold small">Streams <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_stream_streams" min="0" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold small">Downloads</label>
                            <input type="number" class="form-control" id="edit_stream_downloads" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold small">Receita (USD)</label>
                            <input type="number" class="form-control" id="edit_stream_revenue" min="0" step="0.0001">
                        </div>
                    </div>
                    <div class="alert alert-danger d-none mt-3" id="edit_stream_error"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm text-white" style="background:#f97316"
                        id="btn_save_edit_stream">
                        <span class="normal-label">Guardar Alterações</span>
                        <span class="loading-label d-none">
                            <span class="spinner-border spinner-border-sm me-1"></span>A guardar…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════
     MODAL — Adicionar Stream
════════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalAddStream" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:#22c55e">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>Adicionar Dados de Stream
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-4">
                        <i class="bi bi-info-circle me-1"></i>
                        Os dados adicionados aqui actualizam o <strong>TOP 5 de músicas</strong> na página inicial do
                        painel.
                    </p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Faixa <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_track_id" required>
                                <option value="">Selecciona uma faixa...</option>
                                <?php foreach ($tracks_for_select as $tr): ?>
                                    <option value="<?php echo $tr['id_track']; ?>">
                                        <?php echo htmlspecialchars($tr['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Só faixas com estado active ou approved aparecem na lista.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Plataforma <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="add_store_id" required>
                                <option value="">Selecciona uma plataforma...</option>
                                <?php foreach ($stores as $st): ?>
                                    <option value="<?php echo $st['id_store']; ?>">
                                        <?php echo htmlspecialchars($st['name_store']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Ano <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="add_year" value="<?php echo date('Y'); ?>"
                                min="2020" max="2099" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Mês <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_month" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $m == (int)date('n') ? 'selected' : ''; ?>>
                                        <?php echo str_pad($m, 2, '0', STR_PAD_LEFT) . ' — ' . date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold small">Streams <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="add_streams" min="0" placeholder="ex: 10000"
                                required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold small">Downloads</label>
                            <input type="number" class="form-control" id="add_downloads" min="0" placeholder="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold small">Receita (USD)</label>
                            <input type="number" class="form-control" id="add_revenue" min="0" step="0.0001"
                                placeholder="0.0000">
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0" style="font-size:.78rem">
                        <i class="bi bi-lightbulb me-1"></i>
                        Se já existir um registo para esta faixa + plataforma + período, os valores serão
                        <strong>somados</strong> ao existente.
                    </div>
                    <div class="alert alert-danger d-none mt-3" id="add_stream_error"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm text-white" style="background:#22c55e"
                        id="btn_save_add_stream">
                        <span class="normal-label">Adicionar Stream</span>
                        <span class="loading-label d-none">
                            <span class="spinner-border spinner-border-sm me-1"></span>A guardar…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════
     MODAL — Excluir / Bloquear
════════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalDeleteStream" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalDeleteTitle">Confirmar acção</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="modalDeleteMsg" class="text-muted small mb-3"></p>
                    <input type="hidden" id="del_stream_id">
                    <input type="hidden" id="del_stream_action">
                    <div id="del_password_wrap">
                        <label class="form-label fw-semibold small">
                            Confirma a tua senha <span class="text-danger">*</span>
                        </label>
                        <input type="password" class="form-control" id="del_password" placeholder="Senha do admin">
                    </div>
                    <div class="alert alert-danger d-none mt-2 mb-0" id="del_error" style="font-size:.78rem"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-danger" id="btn_confirm_del">
                        <span class="normal-label">Confirmar</span>
                        <span class="loading-label d-none">
                            <span class="spinner-border spinner-border-sm me-1"></span>A processar…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Área de impressão (PDF) — oculta no ecrã, visível no print -->
    <div id="printArea"></div>

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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
        (function() {
            'use strict';

            const BASE_URL = '<?php echo APP_URL; ?>';
            const ADMIN_PATH = '<?php echo ADMIN_PATH; ?>';
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const PROCESS = BASE_URL + '/' + ADMIN_PATH + '/analytics/process';

            // ── Filtros ──────────────────────────────────────────────────────
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

            // ── AJAX helper ──────────────────────────────────────────────────
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

            // ── Gráfico ──────────────────────────────────────────────────────
            const chartEl = document.getElementById('analyticsChart');
            if (chartEl) {
                const labels = <?php echo json_encode($chart_labels, JSON_UNESCAPED_UNICODE); ?>;
                const streams = <?php echo json_encode($chart_streams); ?>;
                const revenues = <?php echo json_encode($chart_revenue_chart); ?>;
                const isDark = () => document.body.classList.contains('dark-mode');
                const gc = () => isDark() ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)';
                const tc = () => isDark() ? 'rgba(255,255,255,.55)' : 'rgba(0,0,0,.55)';

                const chart = new Chart(chartEl, {
                    data: {
                        labels,
                        datasets: [{
                                type: 'bar',
                                label: 'Streams',
                                data: streams,
                                backgroundColor: 'rgba(255,0,137,.2)',
                                borderColor: '#FF0089',
                                borderWidth: 1.5,
                                yAxisID: 'yStreams',
                                borderRadius: 4
                            },
                            {
                                type: 'line',
                                label: 'Receita (AOA)',
                                data: revenues,
                                borderColor: '#f97316',
                                backgroundColor: 'rgba(249,115,22,.05)',
                                tension: 0.35,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                yAxisID: 'yRevenue',
                                fill: true,
                                borderWidth: 2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: isDark() ? '#1a1a2e' : '#fff',
                                borderColor: isDark() ? '#2e2e42' : '#e8e8f0',
                                borderWidth: 1,
                                titleColor: isDark() ? '#e8e8f0' : '#1a1a2e',
                                bodyColor: isDark() ? 'rgba(255,255,255,.7)' : 'rgba(0,0,0,.6)',
                                padding: 12,
                                callbacks: {
                                    label: ctx => ctx.datasetIndex === 0 ? ' Streams: ' + ctx.raw
                                        .toLocaleString('pt-AO') : ' Receita: Kz ' + ctx.raw.toLocaleString(
                                            'pt-AO')
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: gc()
                                },
                                ticks: {
                                    color: tc(),
                                    font: {
                                        size: 11
                                    },
                                    maxRotation: 0
                                }
                            },
                            yStreams: {
                                position: 'left',
                                grid: {
                                    color: gc()
                                },
                                ticks: {
                                    color: tc(),
                                    font: {
                                        size: 11
                                    },
                                    callback: v => v >= 1e6 ? v / 1e6 + 'M' : v >= 1e3 ? v / 1e3 + 'K' : v
                                }
                            },
                            yRevenue: {
                                position: 'right',
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: tc(),
                                    font: {
                                        size: 11
                                    },
                                    callback: v => 'Kz ' + v.toLocaleString()
                                }
                            }
                        }
                    }
                });

                new MutationObserver(() => {
                    ['x', 'yStreams', 'yRevenue'].forEach(ax => {
                        chart.options.scales[ax].grid.color = gc();
                        chart.options.scales[ax].ticks.color = tc();
                    });
                    chart.update('none');
                }).observe(document.body, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }

            // ── Modal Visualizar ─────────────────────────────────────────────
            let currentViewData = null;

            window.viewStream = function(data, event) {
                if (event) event.preventDefault();
                currentViewData = data;
                const fmtNum = v => parseInt(v || 0).toLocaleString('pt-AO');
                const fmtAOA = v => 'Kz ' + parseFloat(v || 0).toLocaleString('pt-AO', {
                    minimumFractionDigits: 2
                });

                const statusMap = {
                    active: 'Activa',
                    approved: 'Activa',
                    inactive: 'Inactiva',
                    blocked: 'Bloqueada',
                    processing: 'A processar'
                };

                document.getElementById('viewStreamBody').innerHTML = `
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    ${data.cover
                        ? `<img src="${data.cover}" class="img-fluid rounded-3 shadow mb-3" style="max-height:200px;object-fit:cover" onerror="this.src=''" alt="Capa">`
                        : `<div class="rounded-3 bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center mb-3" style="height:180px"><i class="bi bi-music-note-beamed" style="font-size:3rem;opacity:.3"></i></div>`}
                    <div class="fw-bold">${data.track}</div>
                    <div class="text-muted small">${data.artist}</div>
                    <div class="badge mt-2" style="background:rgba(255,0,137,.1);color:#FF0089">${data.store}</div>
                </div>
                <div class="col-md-8">
                    <div class="view-info-row"><span class="view-info-lbl">Álbum / Tipo</span><span class="view-info-val">${data.album} <span class="badge bg-secondary ms-1">${data.type}</span></span></div>
                    <div class="view-info-row"><span class="view-info-lbl">ISRC</span><span class="view-info-val" style="font-family:monospace">${data.isrc||'—'}</span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Conta</span><span class="view-info-val">${data.user} · ${data.email}</span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Plano</span><span class="view-info-val">${data.plan}</span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Período</span><span class="view-info-val">${data.period}</span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Total de Streams</span><span class="view-info-val fw-bold" style="color:#FF0089">${fmtNum(data.streams)}</span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Downloads</span><span class="view-info-val">${fmtNum(data.downloads)}</span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Receita (USD)</span><span class="view-info-val">$ ${parseFloat(data.revenue||0).toFixed(4)}</span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Receita (AOA)</span><span class="view-info-val fw-bold" style="color:#22c55e">${fmtAOA(data.revenue_aoa)}</span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Territórios</span><span class="view-info-val">${data.territories||'—'} <span class="badge bg-secondary ms-1">${data.terr_count} países</span></span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Estado da Track</span><span class="view-info-val">${statusMap[data.status]||data.status}</span></div>
                </div>
            </div>`;

                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalViewStream')).show();
            };

            window.printStreamPDF = function() {
                if (!currentViewData) return;
                const d = currentViewData;
                const fmtNum = v => parseInt(v || 0).toLocaleString('pt-AO');
                const printContent = `
        <div style="font-family:Arial,sans-serif;max-width:680px;margin:auto;padding:24px">
            <div style="text-align:center;margin-bottom:24px;border-bottom:3px solid #FF0089;padding-bottom:16px">
                <h1 style="color:#FF0089;margin:0;font-size:1.4rem">WASOM UPFY</h1>
                <h2 style="color:#333;margin:4px 0 0;font-size:1rem;font-weight:400">Relatório de Stream — Analytics</h2>
                <p style="color:#999;font-size:.8rem;margin:4px 0 0">Gerado em ${new Date().toLocaleString('pt-AO')}</p>
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:.88rem">
                ${[
                    ['Faixa', d.track],
                    ['Artista', d.artist],
                    ['Álbum / Tipo', `${d.album} (${d.type})`],
                    ['ISRC', d.isrc || '—'],
                    ['Conta', d.user],
                    ['E-mail', d.email],
                    ['Plano', d.plan],
                    ['Plataforma', d.store],
                    ['Período', d.period],
                    ['Total Streams', fmtNum(d.streams)],
                    ['Downloads', fmtNum(d.downloads)],
                    ['Receita USD', `$ ${parseFloat(d.revenue||0).toFixed(4)}`],
                    ['Receita AOA', `Kz ${parseFloat(d.revenue_aoa||0).toLocaleString('pt-AO', {minimumFractionDigits:2})}`],
                    ['Territórios', d.territories || '—'],
                    ['Estado da Track', d.status],
                ].map((r,i) => `<tr style="background:${i%2?'#f9f9f9':'#fff'}">
                    <td style="padding:8px 12px;border:1px solid #eee;font-weight:600;color:#555;width:40%">${r[0]}</td>
                    <td style="padding:8px 12px;border:1px solid #eee">${r[1]}</td>
                </tr>`).join('')}
            </table>
            <p style="color:#bbb;font-size:.72rem;margin-top:20px;text-align:center">Wasom Upfy v2.0 — Documento gerado automaticamente. Ref. #${d.id}</p>
        </div>`;
                document.getElementById('printArea').innerHTML = printContent;
                document.getElementById('printArea').style.display = 'block';
                window.print();
                setTimeout(() => {
                    document.getElementById('printArea').style.display = 'none';
                }, 1000);
            };

            // ── Modal Editar ─────────────────────────────────────────────────
            window.editStream = function(data, event) {
                if (event) event.preventDefault();
                document.getElementById('edit_stream_id').value = data.id;
                document.getElementById('edit_stream_track').value = data.track;
                document.getElementById('edit_stream_store_name').value = data.store;
                document.getElementById('edit_stream_year').value = data.year;
                document.getElementById('edit_stream_month').value = data.month;
                document.getElementById('edit_stream_streams').value = data.streams;
                document.getElementById('edit_stream_downloads').value = data.downloads;
                document.getElementById('edit_stream_revenue').value = data.revenue;
                document.getElementById('edit_stream_error').classList.add('d-none');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditStream')).show();
            };

            document.getElementById('btn_save_edit_stream').addEventListener('click', async function() {
                const id = document.getElementById('edit_stream_id').value;
                const streams = parseInt(document.getElementById('edit_stream_streams').value);
                if (!id || isNaN(streams) || streams < 0) {
                    const el = document.getElementById('edit_stream_error');
                    el.textContent = 'Streams inválido.';
                    el.classList.remove('d-none');
                    return;
                }
                setLoading(this, true);
                try {
                    const data = await postAction({
                        action: 'update_stream',
                        id_stream: id,
                        year: document.getElementById('edit_stream_year').value,
                        month: document.getElementById('edit_stream_month').value,
                        streams,
                        downloads: parseInt(document.getElementById('edit_stream_downloads')
                            .value) || 0,
                        revenue: parseFloat(document.getElementById('edit_stream_revenue').value) ||
                            0,
                    });
                    if (data.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('modalEditStream')).hide();
                        location.href = location.pathname + '?msg=updated';
                    } else {
                        const el = document.getElementById('edit_stream_error');
                        el.textContent = data.message;
                        el.classList.remove('d-none');
                    }
                } catch {
                    alert('Erro de ligação.');
                }
                setLoading(this, false);
            });

            // ── Modal Adicionar ──────────────────────────────────────────────
            document.getElementById('btn_save_add_stream').addEventListener('click', async function() {
                const trackId = document.getElementById('add_track_id').value;
                const storeId = document.getElementById('add_store_id').value;
                const streams = parseInt(document.getElementById('add_streams').value);
                const errEl = document.getElementById('add_stream_error');
                if (!trackId || !storeId || isNaN(streams) || streams < 0) {
                    errEl.textContent = 'Preenche faixa, plataforma e streams.';
                    errEl.classList.remove('d-none');
                    return;
                }
                errEl.classList.add('d-none');
                setLoading(this, true);
                try {
                    const data = await postAction({
                        action: 'add_stream',
                        id_track: trackId,
                        id_store: storeId,
                        year: document.getElementById('add_year').value,
                        month: document.getElementById('add_month').value,
                        streams,
                        downloads: parseInt(document.getElementById('add_downloads').value) || 0,
                        revenue: parseFloat(document.getElementById('add_revenue').value) || 0,
                    });
                    if (data.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('modalAddStream')).hide();
                        location.href = location.pathname + '?msg=added';
                    } else {
                        errEl.textContent = data.message;
                        errEl.classList.remove('d-none');
                    }
                } catch {
                    alert('Erro de ligação.');
                }
                setLoading(this, false);
            });

            // ── Bloquear / Desbloquear ────────────────────────────────────────
            window.toggleBlock = async function(id, action) {
                const label = action === 'block' ? 'bloquear' : 'desbloquear';
                const result = await Swal.fire({
                    title: label.charAt(0).toUpperCase() + label.slice(1) + ' track?',
                    text: action === 'block' ?
                        'A track ficará bloqueada e não aparecerá no TOP 5 da plataforma.' : 'A track voltará a aparecer no TOP 5 da plataforma.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: action === 'block' ? '#ef4444' : '#22c55e',
                    confirmButtonText: 'Sim, ' + label,
                    cancelButtonText: 'Cancelar'
                });
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'A processar...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                try {
                    const data = await postAction({
                        action: 'toggle_block',
                        id_stream: id,
                        block_action: action
                    });
                    if (data.ok) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Feito!',
                            text: data.message,
                            confirmButtonColor: '#FF0089'
                        });
                        location.href = location.pathname + '?msg=' + (action === 'block' ? 'blocked' :
                            'unblocked');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: data.message,
                            confirmButtonColor: '#FF0089'
                        });
                    }
                } catch {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro de ligação',
                        confirmButtonColor: '#FF0089'
                    });
                }
            };

            // ── Excluir ──────────────────────────────────────────────────────
            window.deleteStream = function(id, trackName) {
                document.getElementById('del_stream_id').value = id;
                document.getElementById('del_stream_action').value = 'delete_stream';
                document.getElementById('modalDeleteTitle').textContent = 'Excluir registo de stream';
                document.getElementById('modalDeleteMsg').textContent =
                    `Vais excluir o registo de "${trackName}". Esta acção é irreversível.`;
                document.getElementById('del_password').value = '';
                document.getElementById('del_error').classList.add('d-none');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDeleteStream')).show();
            };

            document.getElementById('btn_confirm_del').addEventListener('click', async function() {
                const id = document.getElementById('del_stream_id').value;
                const action = document.getElementById('del_stream_action').value;
                const password = document.getElementById('del_password').value;
                const errEl = document.getElementById('del_error');
                if (!password) {
                    errEl.textContent = 'A senha é obrigatória.';
                    errEl.classList.remove('d-none');
                    return;
                }
                errEl.classList.add('d-none');
                setLoading(this, true);
                try {
                    const data = await postAction({
                        action,
                        id_stream: id,
                        password_confirm: password
                    });
                    if (data.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('modalDeleteStream')).hide();
                        location.href = location.pathname + '?msg=deleted';
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

            // ── Utilitário: loading button ────────────────────────────────────
            function setLoading(btn, state) {
                btn.querySelector('.normal-label').classList.toggle('d-none', state);
                btn.querySelector('.loading-label').classList.toggle('d-none', !state);
                btn.disabled = state;
            }

        })();
    </script>
</body>

</html>