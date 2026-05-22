<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Top Tracks Manager
// Arquivo: wu-panel/pages/manager/top-tracks.php
// Rota:    wu-panel/manager/top-tracks
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// ── Stats globais ────────────────────────────────────────────
$summaryStmt = $db->prepare("SELECT
    COUNT(DISTINCT t.id_track) AS total_tracks,
    COUNT(DISTINCT al.id_artist) AS total_artists,
    COALESCE(SUM(s.streams), 0) AS total_streams
FROM _track t
LEFT JOIN _album al ON al.id_album = t.id_album
LEFT JOIN _stream s ON s.id_track = t.id_track");
$summaryStmt->execute();
$summary = $summaryStmt->fetch();

// ── Filtros + paginação ──────────────────────────────────────
$per_page = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$f_search = trim($_GET['search'] ?? '');

$allowed_sort = [
    'total_streams' => 'total_streams',
    't.creat_track' => 't.creat_track',
    't.title_track' => 't.title_track',
];
$sort_col = $_GET['sort'] ?? 'total_streams';
$sort_col = isset($allowed_sort[$sort_col]) ? $allowed_sort[$sort_col] : 'total_streams';
$sort_dir = (($_GET['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';

$where = [];
$params = [];

// Utiliza LIKE em colunas do track + artista + álbum
if ($f_search !== '') {
    $like = '%' . $f_search . '%';
    $where[] = "(
        t.title_track LIKE ? OR
        al.title_album LIKE ? OR
        ar.stage_name LIKE ? OR
        u.name_artist_band LIKE ? OR
        u.first_name LIKE ?
    )";
    $params = [$like, $like, $like, $like, $like];
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── Contagem filtrada ───────────────────────────────────────
$cnt_stmt = $db->prepare("SELECT COUNT(DISTINCT t.id_track) AS total_filtered
FROM _track t
LEFT JOIN _album al ON al.id_album = t.id_album
LEFT JOIN _artist ar ON ar.id_artist = al.id_artist
LEFT JOIN _users u ON u.id_users = al.id_users
LEFT JOIN _stream s ON s.id_track = t.id_track
$sql_where");
$cnt_stmt->execute($params);
$total_filtered = (int)$cnt_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_filtered / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// ── Dados ──────────────────────────────────────────────────
// Observação: GROUP BY para agregar streams por track.
$topTracksStmt = $db->prepare("SELECT
    t.id_track,
    t.title_track,
    COALESCE(ar.stage_name, u.name_artist_band, u.first_name, 'Independente') AS artist_name,
    al.title_album,
    COALESCE(SUM(s.streams), 0) AS total_streams,
    t.creat_track
FROM _track t
LEFT JOIN _album al ON al.id_album = t.id_album
LEFT JOIN _artist ar ON ar.id_artist = al.id_artist
LEFT JOIN _users u ON u.id_users = al.id_users
LEFT JOIN _stream s ON s.id_track = t.id_track
$sql_where
GROUP BY t.id_track
ORDER BY {$sort_col} {$sort_dir}
LIMIT {$per_page} OFFSET {$offset}");
$topTracksStmt->execute($params);
$topTracks = $topTracksStmt->fetchAll();

// ── Helpers ─────────────────────────────────────────────────
function fmt_number(int $value): string
{
    return number_format($value, 0, ',', '.');
}

function fmt_date(?string $date): string
{
    return $date ? date('d/m/Y', strtotime($date)) : '—';
}

function rel_sort_url(string $col, string $cur_col, string $cur_dir, array $get): string
{
    $dir = ($col === $cur_col && $cur_dir === 'ASC') ? 'desc' : 'asc';
    return '?' . http_build_query(array_merge($get, ['sort' => $col, 'dir' => $dir, 'page' => 1]));
}

function rel_sort_icon(string $col, string $cur_col, string $cur_dir): string
{
    if ($col !== $cur_col) return '';
    return $cur_dir === 'ASC' ? ' ▲' : ' ▼';
}

$base_url = APP_URL . '/' . ADMIN_PATH;
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
    <title>Top Tracks — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
    .rel-stat {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: transform .2s, box-shadow .2s;
        text-decoration: none;
        color: inherit;
    }

    .rel-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, .07);
        color: inherit;
    }

    .rel-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .rel-stat-val {
        font-size: 1.3rem;
        font-weight: 800;
        line-height: 1;
    }

    .rel-stat-lbl {
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .5px;
        opacity: .6;
        margin-top: 2px;
    }

    .rel-filter {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 16px;
    }

    .rel-filter .form-label {
        font-size: .74rem;
        font-weight: 600;
        margin-bottom: 3px;
    }

    #rel-table th {
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
        white-space: nowrap;
        cursor: pointer;
        user-select: none;
    }

    #rel-table td {
        font-size: .83rem;
        vertical-align: middle;
    }

    .track-title {
        font-weight: 700;
        font-size: .86rem;
    }

    .track-artist {
        opacity: .95;
    }

    .rel-empty {
        text-align: center;
        padding: 48px 24px;
        opacity: .4;
    }

    .rel-empty i {
        font-size: 2.5rem;
        display: block;
        margin-bottom: 12px;
    }

    .rel-pag .page-link {
        border-radius: 8px !important;
        margin: 0 2px;
        font-size: .8rem;
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
                        <h2 class="h4 mb-1"><i class="bi bi-music-note-list me-2"></i>Top Tracks</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo $base_url; ?>" class="text-secondary">Home</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="<?php echo $base_url; ?>/manager" class="text-secondary">Manager</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable">Top Tracks</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                <!-- Stat cards clicáveis (ordenam por streams / criacao e reset) -->
                <?php
                $cards = [
                    ['total_streams', '#FF0089', 'bi-music-note-list', 'Streams', (int)$summary['total_streams'], 'total_streams'],
                    ['t.creat_track', '#3b82f6', 'bi-clock-history', 'Mais recentes', 0, 'new'],
                    ['t.title_track', '#22c55e', 'bi-fonts', 'Faixas', (int)$summary['total_tracks'], 't.title_track'],
                ];

                // Cards: mesmo comportamento do releases (clicável via querystring)
                foreach ($cards as $card):
                    $target_sort = $card[0];
                    $color = $card[1];
                    $icon = $card[2];
                    $label = $card[3];
                    $val = $card[4];
                    $mode = $card[5];

                    $is_active = false;
                    if ($mode === 'new') {
                        $is_active = $sort_col === 't.creat_track';
                    } elseif ($target_sort === 'total_streams') {
                        $is_active = $sort_col === 'total_streams';
                    } elseif ($target_sort === 't.title_track') {
                        $is_active = $sort_col === 't.title_track';
                    }

                    if ($mode === 'new') {
                        $link = '?' . http_build_query(array_merge($_GET, ['sort' => 't.creat_track', 'dir' => 'desc', 'page' => 1]));
                    } elseif ($target_sort === 'total_streams') {
                        $link = '?' . http_build_query(array_merge($_GET, ['sort' => 'total_streams', 'dir' => 'desc', 'page' => 1]));
                    } else {
                        $link = '?' . http_build_query(array_merge($_GET, ['sort' => 't.title_track', 'dir' => 'asc', 'page' => 1]));
                    }
                ?>
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <a href="<?php echo $link; ?>" class="rel-stat <?php echo $is_active ? 'border-2' : ''; ?>"
                            style="<?php echo $is_active ? "border-color:$color!important;box-shadow:0 0 0 3px {$color}22" : ''; ?>">
                            <div class="rel-stat-icon" style="background:<?php echo $color; ?>1a">
                                <i class="bi <?php echo $icon; ?>" style="color:<?php echo $color; ?>"></i>
                            </div>
                            <div>
                                <div class="rel-stat-val">
                                    <?php echo $mode === 'new' ? number_format(count($topTracks), 0, ',', '.') : number_format((int)$val, 0, ',', '.'); ?>
                                </div>
                                <div class="rel-stat-lbl"><?php echo htmlspecialchars($label); ?></div>
                            </div>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Filtros -->
                <div class="rel-filter">
                    <form method="GET" action="<?php echo $base_url; ?>/manager/top-tracks" id="filter-form">
                        <input type="hidden" name="csrf"
                            value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">

                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Pesquisar</label>
                                <input type="text" name="search" class="form-control form-control-sm"
                                    value="<?php echo htmlspecialchars($f_search); ?>"
                                    placeholder="Música, artista, álbum...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ordenação</label>
                                <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="total_streams"
                                        <?php echo $sort_col === 'total_streams' ? 'selected' : ''; ?>>Streams</option>
                                    <option value="t.creat_track"
                                        <?php echo $sort_col === 't.creat_track' ? 'selected' : ''; ?>>Lançamento
                                    </option>
                                    <option value="t.title_track"
                                        <?php echo $sort_col === 't.title_track' ? 'selected' : ''; ?>>Título</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Direção</label>
                                <select name="dir" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="desc" <?php echo $sort_dir === 'DESC' ? 'selected' : ''; ?>>Desc
                                    </option>
                                    <option value="asc" <?php echo $sort_dir === 'ASC' ? 'selected' : ''; ?>>Asc
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex gap-1">
                                <button type="submit" class="btn btn-sm text-white flex-fill"
                                    style="background:#FF0089;border-color:#FF0089">
                                    <i class="bi bi-search me-1"></i> Pesquisar
                                </button>
                                <a href="<?php echo $base_url; ?>/manager/top-tracks"
                                    class="btn btn-sm btn-outline-secondary" title="Limpar">
                                    <i class="bi bi-x"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tabela -->
                <div class="card p-0" style="border-radius:14px;overflow:hidden">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                        style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                        <span style="font-size:.82rem;font-weight:600">
                            <?php if ($total_filtered !== (int)$summary['total_tracks']): ?>
                            <span style="color:#FF0089"><?php echo number_format($total_filtered); ?></span>
                            de <?php echo number_format((int)$summary['total_tracks']); ?> faixas
                            <?php else: ?>
                            <?php echo number_format($total_filtered); ?> faixas
                            <?php endif; ?>
                        </span>
                        <span style="font-size:.75rem;opacity:.5">Pág.
                            <?php echo (int)$page; ?>/<?php echo (int)$total_pages; ?></span>
                    </div>

                    <div class="table-responsive" style="overflow-x: auto; overflow-y: visible !important;">
                        <table class="table table-hover mb-0" id="rel-table" style="overflow: visible !important;">
                            <thead>
                                <tr>
                                    <th style="width:50px">#</th>
                                    <th style="min-width:240px">
                                        <a href="<?php echo rel_sort_url('t.title_track', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">
                                            Música<?php echo rel_sort_icon('t.title_track', $sort_col, $sort_dir); ?>
                                        </a>
                                    </th>
                                    <th>Artista</th>
                                    <th style="min-width:220px">Álbum</th>
                                    <th style="min-width:150px; text-align:left;">
                                        <a href="<?php echo rel_sort_url('total_streams', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">
                                            Streams<?php echo rel_sort_icon('total_streams', $sort_col, $sort_dir); ?>
                                        </a>
                                    </th>
                                    <th style="min-width:140px">
                                        <a href="<?php echo rel_sort_url('t.creat_track', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">
                                            Lançamento<?php echo rel_sort_icon('t.creat_track', $sort_col, $sort_dir); ?>
                                        </a>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topTracks)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="rel-empty">
                                            <i class="bi bi-music-note"></i>
                                            <p class="mb-0">Nenhuma faixa encontrada para os filtros aplicados.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($topTracks as $i => $track):
                                        $row_no = $offset + $i + 1;
                                    ?>
                                <tr
                                    <?php echo ($i % 2 === 1) ? 'style="background:var(--table-stripe,rgba(0,0,0,.013))"' : ''; ?>>
                                    <td>
                                        <span
                                            style="font-family:monospace;font-size:.72rem;opacity:.5">#<?php echo (int)$row_no; ?></span>
                                    </td>
                                    <td>
                                        <div class="track-title"><?php echo htmlspecialchars($track['title_track']); ?>
                                        </div>
                                    </td>
                                    <td class="track-artist"><?php echo htmlspecialchars($track['artist_name']); ?></td>
                                    <td><?php echo htmlspecialchars($track['title_album'] ?? '—'); ?></td>
                                    <td style="font-weight:700;color:#FF0089">
                                        <?php echo fmt_number((int)$track['total_streams']); ?></td>
                                    <td style="white-space:nowrap;opacity:.7">
                                        <?php echo fmt_date($track['creat_track']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-center py-3">
                            <nav>
                                <ul class="pagination pagination-sm rel-pag mb-0">
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
                                    <?php if ($ps > 2): ?><li class="page-item disabled"><span
                                            class="page-link">…</span></li><?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($pi = $ps; $pi <= $pe; $pi++): ?>
                                    <li class="page-item <?php echo $pi === $page ? 'active' : ''; ?>">
                                        <a class="page-link"
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pi])); ?>"><?php echo (int)$pi; ?></a>
                                    </li>
                                    <?php endfor; ?>

                                    <?php if ($pe < $total_pages): ?>
                                    <?php if ($pe < $total_pages - 1): ?><li class="page-item disabled"><span
                                            class="page-link">…</span></li><?php endif; ?>
                                    <li class="page-item"><a class="page-link"
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?php echo (int)$total_pages; ?></a>
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
                </div>

            </div>
        </div>

        <div class="page-loader" id="pageLoader">
            <div class="loader-content">
                <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="" />
                <div class="loader-progress"></div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
        <script>
        window.__BASE_URL__ = '<?php echo APP_URL; ?>';
        window.__ADMIN_PATH__ = '<?php echo ADMIN_PATH; ?>';

        document.addEventListener('DOMContentLoaded', function() {
            let dbt;
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(dbt);
                    dbt = setTimeout(function() {
                        document.getElementById('filter-form').submit();
                    }, 500);
                });
            }
        });
        </script>
</body>

</html>