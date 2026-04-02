<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Gestão de Lançamentos (Álbuns)
// Arquivo: wu-panel-2026/pages/distribution/releases.php
// Rota:    wu-panel-2026/releases
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'music.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// ── Stats globais ────────────────────────────────────────────
$stats = $db->query("
    SELECT
        COUNT(*)                               AS total,
        SUM(status_album = 'pending')          AS pending,
        SUM(status_album = 'under_review')     AS under_review,
        SUM(status_album = 'approved')         AS approved,
        SUM(status_album = 'rejected')         AS rejected,
        SUM(status_album = 'draft')            AS draft,
        SUM(status_album = 'deleting')         AS deleting
    FROM _album
")->fetch();

// ── Filtros ──────────────────────────────────────────────────
$per_page  = 15;
$page      = max(1, (int)($_GET['page']   ?? 1));
$f_status  = trim($_GET['status']  ?? '');
$f_type    = trim($_GET['type']    ?? '');
$f_plan    = trim($_GET['plan']    ?? '');
$f_search  = trim($_GET['search']  ?? '');
$sort_col  = in_array($_GET['sort'] ?? '', ['al.creat_album', 'al.title_album', 'al.status_album', 'al.release_date'])
    ? $_GET['sort'] : 'al.creat_album';
$sort_dir  = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$where  = [];
$params = [];

if ($f_status !== '') {
    $where[]  = 'al.status_album = ?';
    $params[] = $f_status;
}
if ($f_type !== '') {
    $where[]  = 'al.type_album = ?';
    $params[] = $f_type;
}
if ($f_plan !== '') {
    $where[]  = 'up.id_plan = ?';
    $params[] = (int)$f_plan;
}
if ($f_search !== '') {
    $like     = '%' . $f_search . '%';
    $where[]  = "(al.title_album LIKE ? OR ar.stage_name LIKE ? OR CONCAT(u.first_name,' ',COALESCE(u.second_name,'')) LIKE ? OR al.upc LIKE ?)";
    array_push($params, $like, $like, $like, $like);
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$base_join = "
    FROM _album al
    LEFT JOIN _artist ar ON ar.id_artist = al.id_artist
    LEFT JOIN _users u ON u.id_users = al.id_users
    LEFT JOIN _user_plan up ON up.id_users = al.id_users AND up.status_plan = 'active'
    LEFT JOIN _plans pl ON pl.id_plan = up.id_plan
";

// Contagem
$cnt_stmt = $db->prepare("SELECT COUNT(DISTINCT al.id_album) $base_join $sql_where");
$cnt_stmt->execute($params);
$total_filtered = (int)$cnt_stmt->fetchColumn();
$total_pages    = max(1, (int)ceil($total_filtered / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;

// Dados
$stmt = $db->prepare("
    SELECT
        al.id_album, al.title_album, al.type_album, al.status_album,
        al.upc, al.img_cover, al.genre_main, al.language,
        al.release_date, al.creat_album, al.rejection_reason,
        ar.id_artist, ar.stage_name, ar.photo_artist,
        u.id_users, u.first_name, u.second_name, u.email_user, u.photo_user,
        pl.name_plan, pl.slug_plan,
        up.releases_used, up.releases_limit,
        (SELECT COUNT(*) FROM _track t WHERE t.id_album = al.id_album) AS track_count
    $base_join
    $sql_where
    GROUP BY al.id_album
    ORDER BY $sort_col $sort_dir
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$albums = $stmt->fetchAll();

// Planos para filtro
$plans = $db->query("SELECT id_plan, name_plan FROM _plans WHERE is_active=1 ORDER BY name_plan")->fetchAll();

// ── Helpers ──────────────────────────────────────────────────
function rel_status_badge(string $s): string
{
    return match ($s) {
        'pending'      => '<span class="badge rel-s-pending">Pendente</span>',
        'under_review' => '<span class="badge rel-s-review">Em Revisão</span>',
        'approved'     => '<span class="badge rel-s-approved">Aprovado</span>',
        'rejected'     => '<span class="badge rel-s-rejected">Rejeitado</span>',
        'draft'        => '<span class="badge rel-s-draft">Rascunho</span>',
        'deleting'     => '<span class="badge rel-s-deleting">A eliminar</span>',
        default        => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}
function rel_type_label(string $t): string
{
    return match ($t) {
        'single'  => '<span style="font-size:.7rem;background:rgba(255,0,137,.1);color:#FF0089;padding:2px 8px;border-radius:20px;font-weight:700">Single</span>',
        'EP'      => '<span style="font-size:.7rem;background:rgba(59,130,246,.1);color:#3b82f6;padding:2px 8px;border-radius:20px;font-weight:700">EP</span>',
        'album'   => '<span style="font-size:.7rem;background:rgba(139,92,246,.1);color:#8b5cf6;padding:2px 8px;border-radius:20px;font-weight:700">Álbum</span>',
        'mixtape' => '<span style="font-size:.7rem;background:rgba(249,115,22,.1);color:#f97316;padding:2px 8px;border-radius:20px;font-weight:700">Mixtape</span>',
        default   => '<span style="font-size:.7rem;background:#f0f0f0;color:#666;padding:2px 8px;border-radius:20px">' . htmlspecialchars($t) . '</span>',
    };
}
function rel_avatar(string $name, ?string $photo, string $path, int $s = 32): string
{
    $p   = explode(' ', trim($name), 2);
    $ini = mb_strtoupper(mb_substr($p[0] ?? '', 0, 1, 'UTF-8'), 'UTF-8')
        . mb_strtoupper(mb_substr($p[1] ?? '', 0, 1, 'UTF-8'), 'UTF-8');
    $cl  = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308', '#3b82f6', '#ef4444'];
    $c   = $cl[abs(crc32($name)) % count($cl)];
    $fs  = round($s * 0.32);
    if ($photo) {
        return '<img src="' . APP_URL . '/' . $path . '/' . htmlspecialchars($photo) . '"
                     width="' . $s . '" height="' . $s . '"
                     style="border-radius:50%;object-fit:cover;border:2px solid rgba(255,0,137,.15);flex-shrink:0"
                     onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'" alt="">
                <div style="width:' . $s . 'px;height:' . $s . 'px;border-radius:50%;background:' . $c . ';
                            display:none;align-items:center;justify-content:center;
                            font-weight:700;font-size:' . $fs . 'px;color:#fff;flex-shrink:0">' . $ini . '</div>';
    }
    return '<div style="width:' . $s . 'px;height:' . $s . 'px;border-radius:50%;background:' . $c . ';
                         display:flex;align-items:center;justify-content:center;
                         font-weight:700;font-size:' . $fs . 'px;color:#fff;flex-shrink:0">' . $ini . '</div>';
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
    <title>Lançamentos — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        /* ── Status badges ── */
        .rel-s-pending {
            background: rgba(234, 179, 8, .15);
            color: #92400e;
        }

        .rel-s-review {
            background: rgba(59, 130, 246, .15);
            color: #1e40af;
        }

        .rel-s-approved {
            background: rgba(34, 197, 94, .15);
            color: #166534;
        }

        .rel-s-rejected {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
        }

        .rel-s-draft {
            background: rgba(107, 114, 128, .15);
            color: #374151;
        }

        .rel-s-deleting {
            background: rgba(249, 115, 22, .15);
            color: #92400e;
        }

        .dark-mode .rel-s-pending {
            background: rgba(234, 179, 8, .2);
            color: #facc15;
        }

        .dark-mode .rel-s-review {
            background: rgba(59, 130, 246, .2);
            color: #60a5fa;
        }

        .dark-mode .rel-s-approved {
            background: rgba(34, 197, 94, .2);
            color: #4ade80;
        }

        .dark-mode .rel-s-rejected {
            background: rgba(239, 68, 68, .2);
            color: #f87171;
        }

        .dark-mode .rel-s-draft {
            background: rgba(107, 114, 128, .2);
            color: #9ca3af;
        }

        .dark-mode .rel-s-deleting {
            background: rgba(249, 115, 22, .2);
            color: #fb923c;
        }

        /* ── Stat cards ── */
        .rel-stat {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: transform .2s, box-shadow .2s;
            cursor: pointer;
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

        /* ── Filtros ── */
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

        /* ── Tabela ── */
        #rel-table th {
            font-size: .76rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
            user-select: none;
        }

        #rel-table th:hover {
            opacity: .75;
        }

        #rel-table td {
            font-size: .83rem;
            vertical-align: middle;
        }

        /* ── Capa ── */
        .album-cover {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid rgba(0, 0, 0, .1);
            flex-shrink: 0;
        }

        .album-cover-ph {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            background: rgba(255, 0, 137, .08);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* ── Dropdown anti-tremor ── */
        .actions-dropdown .dropdown-menu {
            position: fixed !important;
            z-index: 9999;
            min-width: 180px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .15);
            padding: 6px;
        }

        .actions-dropdown .dropdown-menu {
            position: absolute !important;
            z-index: 1060;
            min-width: 200px;
            margin-top: 8px;
        }


        .actions-dropdown .dropdown-item {
            font-size: .82rem;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 8px;
        }

        /* ── Paginação ── */
        .rel-pag .page-link {
            border-radius: 8px !important;
            margin: 0 2px;
            font-size: .8rem;
        }

        /* ── Empty ── */
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
                        <h2 class="h4 mb-1"><i class="bi bi-vinyl me-2"></i>Lançamentos</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo $base_url; ?>" class="text-secondary">Home</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable">Lançamentos</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                <!-- Stat cards (clicáveis como filtros) -->
                <div class="row g-3 mb-4">
                    <?php
                    $stat_cards = [
                        ['',             '#FF0089', 'bi-vinyl',           'Total',        $stats['total']],
                        ['pending',      '#eab308', 'bi-hourglass-split', 'Pendentes',    $stats['pending']],
                        ['under_review', '#3b82f6', 'bi-search',          'Em Revisão',   $stats['under_review']],
                        ['approved',     '#22c55e', 'bi-check-circle',    'Aprovados',    $stats['approved']],
                        ['rejected',     '#ef4444', 'bi-x-circle',        'Rejeitados',   $stats['rejected']],
                        ['draft',        '#6b7280', 'bi-file-earmark',    'Rascunhos',    $stats['draft']],
                    ];
                    foreach ($stat_cards as [$sv, $color, $icon, $lbl, $val]):
                        $is_active = $f_status === $sv;
                        $link = $sv ? '?' . http_build_query(array_merge($_GET, ['status' => $sv, 'page' => 1])) : '?' . http_build_query(array_merge($_GET, ['status' => '', 'page' => 1]));
                    ?>
                        <div class="col-6 col-md-4 col-xl-2">
                            <a href="<?php echo $link; ?>" class="rel-stat <?php echo $is_active ? 'border-2' : ''; ?>"
                                style="<?php echo $is_active ? "border-color:$color!important;box-shadow:0 0 0 3px {$color}22" : ''; ?>">
                                <div class="rel-stat-icon" style="background:<?php echo $color; ?>1a">
                                    <i class="bi <?php echo $icon; ?>" style="color:<?php echo $color; ?>"></i>
                                </div>
                                <div>
                                    <div class="rel-stat-val"><?php echo number_format((int)$val); ?></div>
                                    <div class="rel-stat-lbl"><?php echo $lbl; ?></div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filtros -->
                <div class="rel-filter">
                    <form method="GET" action="<?php echo $base_url; ?>/releases" id="filter-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Pesquisar</label>
                                <input type="text" name="search" class="form-control form-control-sm"
                                    value="<?php echo htmlspecialchars($f_search); ?>"
                                    placeholder="Título, artista, utilizador, UPC...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Todos</option>
                                    <?php foreach (['pending' => 'Pendente', 'under_review' => 'Em Revisão', 'approved' => 'Aprovado', 'rejected' => 'Rejeitado', 'draft' => 'Rascunho', 'deleting' => 'A eliminar'] as $v => $l): ?>
                                        <option value="<?php echo $v; ?>" <?php echo $f_status === $v ? 'selected' : ''; ?>>
                                            <?php echo $l; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tipo</label>
                                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Todos</option>
                                    <?php foreach (['single' => 'Single', 'EP' => 'EP', 'album' => 'Álbum', 'mixtape' => 'Mixtape'] as $v => $l): ?>
                                        <option value="<?php echo $v; ?>" <?php echo $f_type === $v ? 'selected' : ''; ?>>
                                            <?php echo $l; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Plano</label>
                                <select name="plan" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Todos</option>
                                    <?php foreach ($plans as $pl): ?>
                                        <option value="<?php echo (int)$pl['id_plan']; ?>"
                                            <?php echo $f_plan == (string)$pl['id_plan'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pl['name_plan']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex gap-1">
                                <button type="submit" class="btn btn-sm text-white flex-fill"
                                    style="background:#FF0089;border-color:#FF0089">
                                    <i class="bi bi-search me-1"></i> Pesquisar
                                </button>
                                <a href="<?php echo $base_url; ?>/releases" class="btn btn-sm btn-outline-secondary"
                                    title="Limpar">
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
                            <?php if ($total_filtered !== (int)$stats['total']): ?>
                                <span style="color:#FF0089"><?php echo number_format($total_filtered); ?></span>
                                de <?php echo number_format((int)$stats['total']); ?> lançamentos
                            <?php else: ?>
                                <?php echo number_format($total_filtered); ?> lançamentos
                            <?php endif; ?>
                        </span>
                        <span style="font-size:.75rem;opacity:.5">Pág.
                            <?php echo $page; ?>/<?php echo $total_pages; ?></span>
                    </div>

                    <div class="table-responsive" style="overflow-x: auto; overflow-y: visible !important;">
                        <table class="table table-hover mb-0" id="rel-table" style="overflow: visible !important;">
                            <thead>
                                <tr>
                                    <th style="width:50px">#</th>
                                    <th style="width:48px">Capa</th>
                                    <th>
                                        <a href="<?php echo rel_sort_url('al.title_album', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">
                                            Título<?php echo rel_sort_icon('al.title_album', $sort_col, $sort_dir); ?>
                                        </a>
                                    </th>
                                    <th>Artista</th>
                                    <th>Utilizador</th>
                                    <th>Plano</th>
                                    <th>Tipo</th>
                                    <th>Faixas</th>
                                    <th>
                                        <a href="<?php echo rel_sort_url('al.status_album', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">
                                            Estado<?php echo rel_sort_icon('al.status_album', $sort_col, $sort_dir); ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="<?php echo rel_sort_url('al.creat_album', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">
                                            Enviado<?php echo rel_sort_icon('al.creat_album', $sort_col, $sort_dir); ?>
                                        </a>
                                    </th>
                                    <th style="text-align:center;width:80px">Acções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($albums)): ?>
                                    <tr>
                                        <td colspan="11">
                                            <div class="rel-empty">
                                                <i class="bi bi-vinyl"></i>
                                                <p class="mb-0">Nenhum lançamento encontrado para os filtros aplicados.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($albums as $i => $alb):
                                        $user_name  = trim($alb['first_name'] . ' ' . ($alb['second_name'] ?? ''));
                                        $artist_name = $alb['stage_name'] ?: $user_name;
                                        $cover_url  = $alb['img_cover']
                                            ? APP_URL . '/assets/comprovantes/uploads/covers/' . $alb['img_cover']
                                            : null;
                                        $is_even = $i % 2 === 1;
                                    ?>
                                        <tr
                                            <?php echo $is_even ? 'style="background:var(--table-stripe,rgba(0,0,0,.013))"' : ''; ?>>
                                            <!-- ID -->
                                            <td>
                                                <span style="font-family:monospace;font-size:.72rem;opacity:.5">
                                                    #<?php echo (int)$alb['id_album']; ?>
                                                </span>
                                            </td>
                                            <!-- Capa -->
                                            <td>
                                                <?php if ($cover_url): ?>
                                                    <img src="<?php echo $cover_url; ?>" class="album-cover" alt=""
                                                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                                    <div class="album-cover-ph" style="display:none">
                                                        <i class="bi bi-music-note" style="color:#FF0089;font-size:.85rem"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="album-cover-ph">
                                                        <i class="bi bi-music-note" style="color:#FF0089;font-size:.85rem"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <!-- Título -->
                                            <td>
                                                <a href="<?php echo $base_url; ?>/releases/view?id=<?php echo (int)$alb['id_album']; ?>"
                                                    class="text-inherit text-decoration-none"
                                                    style="font-weight:700;font-size:.83rem">
                                                    <?php echo htmlspecialchars($alb['title_album']); ?>
                                                </a>
                                                <?php if ($alb['upc']): ?>
                                                    <div style="font-size:.68rem;font-family:monospace;opacity:.5">
                                                        UPC: <?php echo htmlspecialchars($alb['upc']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <!-- Artista -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if ($alb['photo_artist']): ?>
                                                        <?php echo rel_avatar($artist_name, $alb['photo_artist'], 'assets/comprovantes/uploads/artists', 26); ?>
                                                    <?php else: ?>
                                                        <?php echo rel_avatar($artist_name, null, '', 26); ?>
                                                    <?php endif; ?>
                                                    <span
                                                        style="font-size:.79rem"><?php echo htmlspecialchars($artist_name); ?></span>
                                                </div>
                                            </td>
                                            <!-- Utilizador -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php echo rel_avatar($user_name, $alb['photo_user'], 'assets/comprovantes/uploads/users', 26); ?>
                                                    <div>
                                                        <div style="font-size:.79rem;font-weight:600">
                                                            <?php echo htmlspecialchars($user_name); ?>
                                                        </div>
                                                        <div style="font-size:.68rem;opacity:.5">
                                                            <?php echo htmlspecialchars($alb['email_user']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <!-- Plano -->
                                            <td>
                                                <span style="font-size:.75rem">
                                                    <?php echo htmlspecialchars($alb['name_plan'] ?? '—'); ?>
                                                </span>
                                                <?php if ($alb['releases_limit']): ?>
                                                    <div style="font-size:.68rem;opacity:.5">
                                                        <?php echo (int)$alb['releases_used']; ?>/<?php echo (int)$alb['releases_limit']; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <!-- Tipo -->
                                            <td><?php echo rel_type_label($alb['type_album']); ?></td>
                                            <!-- Faixas -->
                                            <td style="text-align:center">
                                                <span style="font-size:.78rem;font-weight:700;color:#FF0089">
                                                    <?php echo (int)$alb['track_count']; ?>
                                                </span>
                                            </td>
                                            <!-- Estado -->
                                            <td><?php echo rel_status_badge($alb['status_album']); ?></td>
                                            <!-- Data -->
                                            <td style="font-size:.75rem;white-space:nowrap;opacity:.7">
                                                <?php echo date('d/m/Y', strtotime($alb['creat_album'])); ?>
                                            </td>
                                            <!-- Acções -->
                                            <td>
                                                <div class="actions-dropdown dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button"
                                                        data-bs-toggle="dropdown" data-bs-reference="toggle"
                                                        aria-expanded="false">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="<?php echo $base_url; ?>/releases/view?id=<?php echo (int)$alb['id_album']; ?>">
                                                                <i class="bi bi-eye text-info"></i> Ver Detalhes
                                                            </a>
                                                        </li>
                                                        <?php if (hasPermission($admin_id, 'music.approve') && $alb['status_album'] === 'pending'): ?>
                                                            <li>
                                                                <a class="dropdown-item text-warning"
                                                                    href="<?php echo $base_url; ?>/releases/view?id=<?php echo (int)$alb['id_album']; ?>#actions">
                                                                    <i class="bi bi-arrow-repeat text-primary"></i> Colocar em
                                                                    Revisão
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if (hasPermission($admin_id, 'music.edit') && !in_array($alb['status_album'], ['approved', 'deleting'])): ?>
                                                            <li>
                                                                <a class="dropdown-item text-primary"
                                                                    href="<?php echo $base_url; ?>/releases/view?id=<?php echo (int)$alb['id_album']; ?>#edit-actions">
                                                                    <i class="bi bi-pencil text-primary"></i> Editar Álbum
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if (hasPermission($admin_id, 'music.delete') && !in_array($alb['status_album'], ['approved', 'deleting'])): ?>
                                                            <li>
                                                                <a class="dropdown-item text-danger"
                                                                    href="<?php echo $base_url; ?>/releases/view?id=<?php echo (int)$alb['id_album']; ?>#edit-actions">
                                                                    <i class="bi bi-trash text-danger"></i> Eliminar Álbum
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if (hasPermission($admin_id, 'music.view')): ?>
                                                            <li>
                                                                <a class="dropdown-item"
                                                                    href="<?php echo $base_url; ?>/releases/download-zip?id=<?php echo (int)$alb['id_album']; ?>">
                                                                    <i class="bi bi-file-zip text-secondary"></i> Download ZIP
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
                                            <?php if ($ps > 2): ?><li class="page-item disabled">
                                                    <span class="page-link">…</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php for ($pi = $ps; $pi <= $pe; $pi++): ?>
                                            <li class="page-item <?php echo $pi === $page ? 'active' : ''; ?>">
                                                <a class="page-link"
                                                    href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pi])); ?>">
                                                    <?php echo $pi; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        <?php if ($pe < $total_pages): ?>
                                            <?php if ($pe < $total_pages - 1): ?><li class="page-item disabled">
                                                    <span class="page-link">…</span>
                                                </li><?php endif; ?>
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
                // Debounce na pesquisa de texto
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