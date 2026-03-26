<?php
// ═══════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Analytics: Desempenho por Loja Digital
// Arquivo: wu-panel-2026/pages/analytics/stores.php
// Rota:    wu-panel-2026/analytics/stores
// ═══════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.view');

if (!isset($_SESSION['admin_csrf_token'])) {
  $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// ── Feedback ──────────────────────────────────────────────────────────────
$msg      = $_GET['msg'] ?? null;
$feedback = match ($msg) {
  'store_blocked'   => ['warning', 'bi-lock', 'Loja desactivada com sucesso.'],
  'store_unblocked' => ['success', 'bi-unlock', 'Loja reactivada com sucesso.'],
  'updated'         => ['success', 'bi-check', 'Dados da loja actualizados.'],
  'error'           => ['danger',  'bi-x-circle', 'Ocorreu um erro. Tenta novamente.'],
  default           => null,
};

// ── Helpers (reutilizamos as mesmas funções) ─────────────────────────────
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
    'active'    => '<span class="badge an-s-active">Activa</span>',
    'inactive'  => '<span class="badge an-s-inactive">Inactiva</span>',
    default     => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
  };
}
function an_avatar(string $name, ?string $photo, int $size = 32): string
{
  // Mesma implementação de antes
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

// ── Estatísticas gerais das lojas ────────────────────────────────────────
$total_stores = (int)$db->query("SELECT COUNT(*) FROM _store WHERE is_active=1")->fetchColumn();
$total_streams_all = (int)$db->query("SELECT COALESCE(SUM(streams),0) FROM _stream")->fetchColumn();
$total_revenue_usd = (float)$db->query("SELECT COALESCE(SUM(revenue),0) FROM _stream")->fetchColumn();
$usd_rate = (float)($db->query("SELECT usd_to_aoa_rate FROM _platform LIMIT 1")->fetchColumn() ?: 900);
$total_revenue_aoa = $total_revenue_usd * $usd_rate;
$stores_with_data = (int)$db->query("SELECT COUNT(DISTINCT id_store) FROM _stream")->fetchColumn();
$unique_territories = (int)$db->query("SELECT COUNT(DISTINCT country_code) FROM _stream_country WHERE country_code IS NOT NULL")->fetchColumn();

// Média de streams por loja
$avg_streams_per_store = ($stores_with_data > 0) ? ($total_streams_all / $stores_with_data) : 0;

// ── Filtros e ordenação ───────────────────────────────────────────────────
$per_page  = 15;
$page      = max(1, (int)($_GET['page'] ?? 1));
$f_name    = trim($_GET['name']   ?? '');
$f_type    = trim($_GET['type']   ?? '');
$f_status  = trim($_GET['status'] ?? '');
$f_min_str = (int)($_GET['min_str'] ?? 0);
$f_max_str = (int)($_GET['max_str'] ?? 0);
$sort_col  = in_array($_GET['sort'] ?? '', ['s.id_store', 's.name_store', 'total_streams', 'total_revenue', 'artist_count', 'track_count', 's.is_active'])
  ? $_GET['sort'] : 'total_streams';
$sort_dir  = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$where  = [];
$params = [];

if ($f_name !== '') {
  $where[]  = "s.name_store LIKE ?";
  $params[] = '%' . $f_name . '%';
}
if ($f_type !== '') {
  $where[]  = "s.type_store = ?";
  $params[] = $f_type;
}
if ($f_status !== '') {
  $where[]  = "s.is_active = ?";
  $params[] = ($f_status === 'active') ? 1 : 0;
}
if ($f_min_str > 0) {
  $where[]  = "COALESCE(SUM(str.streams),0) >= ?";
  $params[] = $f_min_str;
}
if ($f_max_str > 0) {
  $where[]  = "COALESCE(SUM(str.streams),0) <= ?";
  $params[] = $f_max_str;
}

$base_joins = "
    FROM _store s
    LEFT JOIN _stream str ON str.id_store = s.id_store
    LEFT JOIN _track t ON t.id_track = str.id_track
    LEFT JOIN _album al ON al.id_album = t.id_album
    LEFT JOIN _artist a ON a.id_artist = al.id_artist
";
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Contagem total de lojas (distintas)
$count_stmt = $db->prepare("
    SELECT COUNT(DISTINCT s.id_store) $base_joins $sql_where
");
$count_stmt->execute($params);
$total_filtered = (int)$count_stmt->fetchColumn();
$total_pages    = max(1, (int)ceil($total_filtered / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;

// Dados das lojas com agregados
$data_stmt = $db->prepare("
    SELECT
        s.id_store,
        s.name_store,
        s.slug_store,
        s.logo_store,
        s.type_store,
        s.is_active,
        s.url_store,
        s.display_order,
        COALESCE(SUM(str.streams),0) AS total_streams,
        COALESCE(SUM(str.revenue),0) AS total_revenue,
        COUNT(DISTINCT a.id_artist) AS artist_count,
        COUNT(DISTINCT t.id_track) AS track_count
    $base_joins
    $sql_where
    GROUP BY s.id_store
    ORDER BY $sort_col $sort_dir
    LIMIT $per_page OFFSET $offset
");
$data_stmt->execute($params);
$stores = $data_stmt->fetchAll();

// Lista de tipos de loja para filtro
$store_types = $db->query("SELECT DISTINCT type_store FROM _store ORDER BY type_store")->fetchAll(PDO::FETCH_COLUMN);

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
    <title>Analytics — Desempenho por Loja Digital — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
    /* Reutilizamos os estilos de home.php e artists.php */
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

    .dark-mode .an-s-active {
        background: rgba(34, 197, 94, .18);
        color: #4ade80;
    }

    .dark-mode .an-s-inactive {
        background: rgba(107, 114, 128, .18);
        color: #9ca3af;
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
    }

    .an-table td {
        font-size: .8rem;
        vertical-align: middle;
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

    .store-logo {
        width: 30px;
        height: 30px;
        object-fit: contain;
        border-radius: 6px;
        background: #fff;
        padding: 4px;
    }

    .an-empty {
        text-align: center;
        padding: 48px 24px;
        opacity: .4;
    }

    .view-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-color, #e8e8f0);
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
                        <h2 class="h4 mb-1"><i class="bi bi-shop me-2"></i>Analytics — Desempenho por Loja Digital</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item active text-white-stable">Lojas Digitais</li>
                            </ol>
                        </nav>
                    </div>
                    <?php if (hasPermission($admin_id, 'analytics.edit')): ?>
                    <div class="col-auto ms-auto">
                        <button class="btn btn-sm text-white" style="background:#FF0089;border-color:#FF0089"
                            data-bs-toggle="modal" data-bs-target="#modalAddStore">
                            <i class="bi bi-plus-lg me-1"></i> Nova Loja
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($feedback): ?>
                <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3">
                    <i class="bi <?php echo $feedback[1]; ?> me-2"></i><?php echo htmlspecialchars($feedback[2]); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Stat cards -->
                <div class="row g-3 mb-4">
                    <?php
          $cards = [
            ['icon' => 'bi-shop', 'color' => '#FF0089', 'val' => number_format($total_stores), 'lbl' => 'Lojas Activas', 'sub' => null],
            ['icon' => 'bi-headphones', 'color' => '#3b82f6', 'val' => an_fmt_num($total_streams_all), 'lbl' => 'Total Streams', 'sub' => null],
            ['icon' => 'bi-cash-coin', 'color' => '#f97316', 'val' => an_fmt_aoa($total_revenue_aoa), 'lbl' => 'Receita (AOA)', 'sub' => 'USD ' . number_format($total_revenue_usd, 2)],
            ['icon' => 'bi-bar-chart', 'color' => '#06b6d4', 'val' => an_fmt_num($avg_streams_per_store), 'lbl' => 'Média / Loja', 'sub' => null],
            ['icon' => 'bi-globe2', 'color' => '#22c55e', 'val' => number_format($unique_territories), 'lbl' => 'Países', 'sub' => null],
          ];
          foreach ($cards as $c): ?>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="an-stat">
                            <div class="an-stat-icon" style="background:<?php echo $c['color']; ?>22">
                                <i class="bi <?php echo $c['icon']; ?>" style="color:<?php echo $c['color']; ?>"></i>
                            </div>
                            <div>
                                <div class="an-stat-val"><?php echo $c['val']; ?></div>
                                <div class="an-stat-lbl"><?php echo $c['lbl']; ?></div>
                                <?php if ($c['sub']): ?><div class="an-stat-sub"
                                    style="color:<?php echo $c['color']; ?>"><?php echo $c['sub']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filtros -->
                <div class="an-filter">
                    <form method="GET" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/stores"
                        id="filter-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">Loja</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="name"
                                    value="<?php echo htmlspecialchars($f_name); ?>" placeholder="Nome da loja" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tipo</label>
                                <select class="form-select form-select-sm filter-instant" name="type">
                                    <option value="">Todos</option>
                                    <?php foreach ($store_types as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo $f_type === $t ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($t); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <select class="form-select form-select-sm filter-instant" name="status">
                                    <option value="">Todos</option>
                                    <option value="active" <?php echo $f_status === 'active' ? 'selected' : ''; ?>>
                                        Activa</option>
                                    <option value="inactive" <?php echo $f_status === 'inactive' ? 'selected' : ''; ?>>
                                        Inactiva</option>
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
                            <div class="col-md-2 d-flex gap-1">
                                <button type="submit" class="btn btn-sm text-white flex-fill"
                                    style="background:#FF0089;border-color:#FF0089"><i
                                        class="bi bi-search"></i></button>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/stores"
                                    class="btn btn-sm btn-outline-secondary" title="Limpar"><i class="bi bi-x"></i></a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tabela -->
                <div class="card p-0" style="border-radius:14px;overflow:hidden">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                        style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                        <span style="font-size:.82rem;font-weight:600">
                            <?php if ($total_filtered < $total_stores): ?>
                            <span style="color:#FF0089"><?php echo number_format($total_filtered); ?></span> de
                            <?php echo number_format($total_stores); ?> lojas
                            <?php else: echo number_format($total_filtered); ?> lojas<?php endif; ?>
                        </span>
                        <span style="font-size:.75rem;opacity:.5">Pág. <?php echo $page; ?> /
                            <?php echo $total_pages; ?></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover an-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width:50px"><a
                                            href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 's.id_store', 'dir' => $sort_col === 's.id_store' && $sort_dir === 'ASC' ? 'desc' : 'asc', 'page' => 1])); ?>"
                                            class="text-inherit text-decoration-none">ID<?php echo $sort_col === 's.id_store' ? ($sort_dir === 'ASC' ? ' ▲' : ' ▼') : ''; ?></a>
                                    </th>
                                    <th>Loja</th>
                                    <th>Tipo</th>
                                    <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'total_streams', 'dir' => $sort_col === 'total_streams' && $sort_dir === 'ASC' ? 'desc' : 'asc', 'page' => 1])); ?>"
                                            class="text-inherit text-decoration-none">Streams<?php echo $sort_col === 'total_streams' ? ($sort_dir === 'ASC' ? ' ▲' : ' ▼') : ''; ?></a>
                                    </th>
                                    <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'total_revenue', 'dir' => $sort_col === 'total_revenue' && $sort_dir === 'ASC' ? 'desc' : 'asc', 'page' => 1])); ?>"
                                            class="text-inherit text-decoration-none">Receita
                                            (USD)<?php echo $sort_col === 'total_revenue' ? ($sort_dir === 'ASC' ? ' ▲' : ' ▼') : ''; ?></a>
                                    </th>
                                    <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'artist_count', 'dir' => $sort_col === 'artist_count' && $sort_dir === 'ASC' ? 'desc' : 'asc', 'page' => 1])); ?>"
                                            class="text-inherit text-decoration-none">Artistas</a></th>
                                    <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'track_count', 'dir' => $sort_col === 'track_count' && $sort_dir === 'ASC' ? 'desc' : 'asc', 'page' => 1])); ?>"
                                            class="text-inherit text-decoration-none">Faixas</a></th>
                                    <th><a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 's.is_active', 'dir' => $sort_col === 's.is_active' && $sort_dir === 'ASC' ? 'desc' : 'asc', 'page' => 1])); ?>"
                                            class="text-inherit text-decoration-none">Estado<?php echo $sort_col === 's.is_active' ? ($sort_dir === 'ASC' ? ' ▲' : ' ▼') : ''; ?></a>
                                    </th>
                                    <th style="text-align:center;width:60px">Acções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($stores)): ?>
                                <tr>
                                    <td colspan="9">
                                        <div class="an-empty"><i class="bi bi-shop"></i>
                                            <p class="mb-0 mt-2">Nenhuma loja encontrada.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: foreach ($stores as $i => $s): $is_even = $i % 2 === 1; ?>
                                <tr
                                    <?php echo $is_even ? 'style="background:var(--table-stripe,rgba(0,0,0,.015))"' : ''; ?>>
                                    <td><span
                                            style="font-family:monospace;font-size:.73rem;opacity:.55">#<?php echo $s['id_store']; ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if ($s['logo_store']): ?><img
                                                src="<?php echo APP_URL . '/' . $s['logo_store']; ?>" class="store-logo"
                                                alt=""><?php endif; ?>
                                            <div>
                                                <div style="font-size:.8rem;font-weight:600">
                                                    <?php echo htmlspecialchars($s['name_store']); ?></div>
                                                <div style="font-size:.7rem;opacity:.5">
                                                    <?php echo htmlspecialchars($s['slug_store']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo ucfirst($s['type_store']); ?></td>
                                    <td style="font-size:.85rem;font-weight:700;color:#FF0089">
                                        <?php echo an_fmt_num((int)$s['total_streams']); ?></td>
                                    <td><?php echo '$ ' . number_format((float)$s['total_revenue'], 2); ?></td>
                                    <td><?php echo (int)$s['artist_count']; ?></td>
                                    <td><?php echo (int)$s['track_count']; ?></td>
                                    <td><?php echo an_status_badge($s['is_active'] ? 'active' : 'inactive'); ?></td>
                                    <td class="text-center">
                                        <div class="actions-dropdown dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button"
                                                data-bs-toggle="dropdown"><i
                                                    class="bi bi-three-dots-vertical"></i></button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="#"
                                                        onclick="viewStore(<?php echo htmlspecialchars(json_encode($s)); ?>, event); return false"><i
                                                            class="bi bi-eye text-info"></i> Visualizar</a></li>
                                                <?php if (hasPermission($admin_id, 'analytics.edit')): ?>
                                                <li><a class="dropdown-item" href="#"
                                                        onclick="editStore(<?php echo (int)$s['id_store']; ?>, '<?php echo htmlspecialchars(addslashes($s['name_store'])); ?>', '<?php echo $s['type_store']; ?>', <?php echo $s['is_active']; ?>, event); return false"><i
                                                            class="bi bi-pencil text-warning"></i> Editar</a></li>
                                                <?php if ($s['is_active']): ?>
                                                <li><a class="dropdown-item" href="#"
                                                        onclick="toggleStore(<?php echo (int)$s['id_store']; ?>,'block'); return false"><i
                                                            class="bi bi-lock text-warning"></i> Desactivar</a></li>
                                                <?php else: ?>
                                                <li><a class="dropdown-item" href="#"
                                                        onclick="toggleStore(<?php echo (int)$s['id_store']; ?>,'unblock'); return false"><i
                                                            class="bi bi-unlock text-success"></i> Reactivar</a></li>
                                                <?php endif; ?>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach;
                endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-center py-3">
                        <ul class="pagination pagination-sm an-pag mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link"
                                    href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"><i
                                        class="bi bi-chevron-left"></i></a></li>
                            <?php
                $ps = max(1, $page - 2);
                $pe = min($total_pages, $page + 2);
                if ($ps > 1): ?>
                            <li class="page-item"><a class="page-link"
                                    href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                            </li>
                            <?php if ($ps > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?php for ($pi = $ps; $pi <= $pe; $pi++): ?>
                            <li class="page-item <?php echo $pi === $page ? 'active' : ''; ?>"><a class="page-link"
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
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"><a
                                    class="page-link"
                                    href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"><i
                                        class="bi bi-chevron-right"></i></a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Visualizar Loja -->
    <div class="modal fade" id="modalViewStore" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:#FF0089">
                    <h5 class="modal-title text-white fw-bold"><i class="bi bi-shop me-2"></i>Detalhes da Loja</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="viewStoreBody"></div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Fechar</button><button type="button" class="btn btn-sm text-white"
                        style="background:#FF0089" id="printStoreBtn"><i class="bi bi-file-earmark-pdf me-1"></i>
                        Download PDF</button></div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Loja (simplificado, apenas ativar/desativar) -->
    <div class="modal fade" id="modalEditStore" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="editStoreTitle">Editar Loja</h5><button type="button"
                        class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="editStoreMsg"></p>
                    <input type="hidden" id="edit_store_id">
                    <div id="edit_store_status_wrap">
                        <label class="form-label fw-semibold small">Estado</label>
                        <select class="form-select" id="edit_store_status">
                            <option value="1">Activa</option>
                            <option value="0">Inactiva</option>
                        </select>
                    </div>
                    <div id="edit_store_password_wrap" class="mt-3">
                        <label class="form-label fw-semibold small">Confirmar senha <span
                                class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="edit_store_password"
                            placeholder="Senha do admin">
                    </div>
                    <div class="alert alert-danger d-none mt-2 mb-0" id="edit_store_error" style="font-size:.78rem">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-danger" id="btn_save_edit_store"><span
                            class="normal-label">Guardar</span><span class="loading-label d-none"><span
                                class="spinner-border spinner-border-sm me-1"></span>A guardar…</span></button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="col-12 text-center py-2">
                <p class="mb-0">© <?php echo date('Y'); ?> Wasom Upfy. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    <div class="page-loader" id="pageLoader">
        <div class="loader-content"><img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" alt="">
            <div class="loader-progress"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
    (function() {
        const BASE_URL = '<?php echo APP_URL; ?>';
        const ADMIN_PATH = '<?php echo ADMIN_PATH; ?>';
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const PROCESS = BASE_URL + '/' + ADMIN_PATH + '/analytics/process-stores';

        let dbt;
        document.querySelectorAll('.filter-debounce').forEach(el => el.addEventListener('input', () => {
            clearTimeout(dbt);
            dbt = setTimeout(() => document.getElementById('filter-form').submit(), 500);
        }));
        document.querySelectorAll('.filter-instant').forEach(el => el.addEventListener('change', () => document
            .getElementById('filter-form').submit()));

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

        let currentStoreData = null;
        window.viewStore = function(data, event) {
            if (event) event.preventDefault();
            currentStoreData = data;
            const fmtNum = v => parseInt(v || 0).toLocaleString('pt-AO');
            const fmtMoney = v => '$ ' + parseFloat(v || 0).toLocaleString('pt-AO', {
                minimumFractionDigits: 2
            });
            document.getElementById('viewStoreBody').innerHTML = `
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    ${data.logo_store ? `<img src="${BASE_URL}/${data.logo_store}" class="img-fluid rounded-3 shadow mb-3" style="max-height:150px;object-fit:contain">` : `<div class="rounded-3 bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center mb-3" style="height:150px"><i class="bi bi-shop" style="font-size:3rem;opacity:.3"></i></div>`}
                    <div class="fw-bold">${data.name_store}</div>
                    <div class="text-muted small">${data.slug_store}</div>
                </div>
                <div class="col-md-8">
                    <div class="view-info-row"><span class="view-info-lbl">ID</span><span class="view-info-val">#${data.id_store}</span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Tipo</span><span class="view-info-val">${data.type_store}</span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Estado</span><span class="view-info-val">${data.is_active ? 'Activa' : 'Inactiva'}</span></div>
                    <div class="view-info-row"><span class="view-info-lbl">URL</span><span class="view-info-val"><a href="${data.url_store}" target="_blank">${data.url_store || '—'}</a></span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Total Streams</span><span class="view-info-val fw-bold" style="color:#FF0089">${fmtNum(data.total_streams)}</span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Receita (USD)</span><span class="view-info-val">${fmtMoney(data.total_revenue)}</span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Artistas</span><span class="view-info-val">${data.artist_count}</span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Faixas</span><span class="view-info-val">${data.track_count}</span></div>
                    <div class="view-info-row"><span class="view-info-lbl">Ordem de exibição</span><span class="view-info-val">${data.display_order}</span></div>
                </div>
            </div>`;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalViewStore')).show();
        };
        document.getElementById('printStoreBtn')?.addEventListener('click', () => {
            if (!currentStoreData) return;
            const d = currentStoreData;
            const fmtNum = v => parseInt(v || 0).toLocaleString('pt-AO');
            const fmtMoney = v => '$ ' + parseFloat(v || 0).toLocaleString('pt-AO', {
                minimumFractionDigits: 2
            });
            const printContent =
                `<div style="font-family:Arial,sans-serif;max-width:680px;margin:auto;padding:24px"><div style="text-align:center;border-bottom:3px solid #FF0089;padding-bottom:16px"><h1 style="color:#FF0089;margin:0">WASOM UPFY</h1><h2 style="color:#333;margin:4px 0 0;font-weight:400">Relatório de Loja</h2><p>${new Date().toLocaleString('pt-AO')}</p></div><table style="width:100%;border-collapse:collapse">${[
                ['Loja', d.name_store],
                ['Slug', d.slug_store],
                ['Tipo', d.type_store],
                ['Estado', d.is_active ? 'Activa' : 'Inactiva'],
                ['URL', d.url_store || '—'],
                ['Total Streams', fmtNum(d.total_streams)],
                ['Receita (USD)', fmtMoney(d.total_revenue)],
                ['Artistas', d.artist_count],
                ['Faixas', d.track_count],
            ].map((r,i) => `<tr style="background:${i%2?'#f9f9f9':'#fff'}"><td style="padding:8px 12px;border:1px solid #eee;width:40%">${r[0]}</td><td style="padding:8px 12px;border:1px solid #eee">${r[1]}</td></tr>`).join('')}</table><p style="color:#bbb;font-size:.72rem;margin-top:20px;text-align:center">Wasom Upfy v2.0 — Documento gerado automaticamente. Ref. #${d.id_store}</p></div>`;
            const printArea = document.createElement('div');
            printArea.id = 'printArea';
            printArea.style.display = 'block';
            printArea.innerHTML = printContent;
            document.body.appendChild(printArea);
            window.print();
            setTimeout(() => printArea.remove(), 1000);
        });

        window.editStore = function(id, name, type, isActive, event) {
            if (event) event.preventDefault();
            document.getElementById('edit_store_id').value = id;
            document.getElementById('edit_store_status').value = isActive ? 1 : 0;
            document.getElementById('editStoreMsg').innerHTML =
                `A editar loja <strong>${name}</strong> (${type})`;
            document.getElementById('edit_store_password').value = '';
            document.getElementById('edit_store_error').classList.add('d-none');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditStore')).show();
        };
        document.getElementById('btn_save_edit_store')?.addEventListener('click', async function() {
            const id = document.getElementById('edit_store_id').value;
            const newStatus = document.getElementById('edit_store_status').value;
            const password = document.getElementById('edit_store_password').value;
            const errEl = document.getElementById('edit_store_error');
            if (!password) {
                errEl.textContent = 'A senha é obrigatória.';
                errEl.classList.remove('d-none');
                return;
            }
            errEl.classList.add('d-none');
            setLoading(this, true);
            try {
                const data = await postAction({
                    action: 'update_store',
                    id_store: id,
                    is_active: newStatus,
                    password_confirm: password
                });
                if (data.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditStore')).hide();
                    location.href = location.pathname + '?msg=' + (newStatus == 1 ? 'store_unblocked' :
                        'store_blocked');
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

        window.toggleStore = async function(id, action) {
            const result = await Swal.fire({
                title: action === 'block' ? 'Desactivar loja?' : 'Reactivar loja?',
                text: action === 'block' ? 'A loja deixará de aparecer nas estatísticas.' :
                    'A loja voltará a aparecer nas estatísticas.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: action === 'block' ? '#ef4444' : '#22c55e',
                confirmButtonText: 'Sim, ' + (action === 'block' ? 'desactivar' : 'reactivar'),
                cancelButtonText: 'Cancelar'
            });
            if (!result.isConfirmed) return;
            document.getElementById('edit_store_id').value = id;
            document.getElementById('edit_store_status').value = action === 'block' ? 0 : 1;
            document.getElementById('editStoreMsg').innerHTML = action === 'block' ? 'Desactivar loja' :
                'Reactivar loja';
            document.getElementById('edit_store_password').value = '';
            document.getElementById('edit_store_error').classList.add('d-none');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditStore')).show();
        };

        function setLoading(btn, state) {
            btn.querySelector('.normal-label').classList.toggle('d-none', state);
            btn.querySelector('.loading-label').classList.toggle('d-none', !state);
            btn.disabled = state;
        }
    })();
    </script>
</body>

</html>