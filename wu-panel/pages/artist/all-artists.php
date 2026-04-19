<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Todos os Artistas
// Arquivo: wu-panel/pages/artist/all-artists.php
// Rota:    wu-panel/artist
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'users.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// Feedback
$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'updated'     => ['success', 'bi-check-circle',    'Artista actualizado com sucesso.'],
    'deleted'     => ['success', 'bi-trash',            'Artista removido com sucesso.'],
    'blocked'     => ['warning', 'bi-lock',             'Artista bloqueado.'],
    'unblocked'   => ['success', 'bi-unlock',           'Artista desbloqueado.'],
    'error'       => ['danger',  'bi-x-circle',         'Ocorreu um erro. Tenta novamente.'],
    default       => null,
};

// Stats
$stats = $db->query("
    SELECT
        COUNT(*)                         AS total,
        SUM(status_artist = 'active')    AS active,
        SUM(status_artist = 'inactive')  AS inactive,
        SUM(status_artist = 'blocked')   AS blocked
    FROM _artist
")->fetch();

// Filtros
$per_page  = 15;
$page      = max(1, (int)($_GET['page'] ?? 1));
$f_id      = trim($_GET['id']   ?? '');
$f_stage   = trim($_GET['stage'] ?? '');
$f_real    = trim($_GET['real']  ?? '');
$f_genre   = trim($_GET['genre'] ?? '');
$f_country = trim($_GET['country'] ?? '');
$f_status  = trim($_GET['status'] ?? '');
$f_owner   = trim($_GET['owner']  ?? '');
$sort_col  = in_array($_GET['sort'] ?? '', ['id_artist', 'stage_name', 'real_name', 'creat_artist', 'status_artist']) ? $_GET['sort'] : 'creat_artist';
$sort_dir  = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$where  = [];
$params = [];

if ($f_id !== '') {
    $where[]  = 'a.id_artist = ?';
    $params[] = (int)$f_id;
}
if ($f_stage !== '') {
    $where[]  = 'a.stage_name LIKE ?';
    $params[] = '%' . $f_stage . '%';
}
if ($f_real !== '') {
    $where[]  = 'a.real_name LIKE ?';
    $params[] = '%' . $f_real . '%';
}
if ($f_genre !== '') {
    $where[]  = 'a.genre_main LIKE ? OR a.genre_secondary LIKE ?';
    $params[] = '%' . $f_genre . '%';
    $params[] = '%' . $f_genre . '%';
}
if ($f_country !== '') {
    $where[]  = 'a.country LIKE ?';
    $params[] = '%' . $f_country . '%';
}
if ($f_status !== '') {
    $where[]  = 'a.status_artist = ?';
    $params[] = $f_status;
}
if ($f_owner !== '') {
    $concat_owner = "CONCAT(u.first_name,' ',COALESCE(u.second_name,''))";
    $where[]      = "$concat_owner LIKE ?";
    $params[]     = '%' . $f_owner . '%';
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Contagem
$count_stmt = $db->prepare("
    SELECT COUNT(DISTINCT a.id_artist)
    FROM _artist a
    LEFT JOIN _users u ON u.id_users = a.id_users
    $sql_where
");
$count_stmt->execute($params);
$total_filtered = (int)$count_stmt->fetchColumn();
$total_pages    = max(1, (int)ceil($total_filtered / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;

// Dados
$stmt = $db->prepare("
    SELECT
        a.id_artist,
        a.stage_name,
        a.real_name,
        a.genre_main,
        a.genre_secondary,
        a.country,
        a.city,
        a.photo_artist,
        a.cover_artist,
        a.status_artist,
        a.creat_artist,
        a.modif_artist,
        u.id_users          AS owner_id,
        u.first_name        AS owner_first,
        u.second_name       AS owner_second,
        u.photo_user        AS owner_photo,
        u.email_user        AS owner_email,
        (SELECT COUNT(*) FROM _album WHERE id_artist = a.id_artist) AS album_count
    FROM _artist a
    LEFT JOIN _users u ON u.id_users = a.id_users
    $sql_where
    ORDER BY a.$sort_col $sort_dir
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$artists = $stmt->fetchAll();

// Helpers
function artist_status_badge(string $s): string
{
    return match ($s) {
        'active'   => '<span class="badge artist-s-active">Activo</span>',
        'inactive' => '<span class="badge artist-s-inactive">Inactivo</span>',
        'blocked'  => '<span class="badge artist-s-blocked">Bloqueado</span>',
        'processing' => '<span class="badge artist-s-processing">Processando</span>',
        default    => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}

function artist_initials(string $name): string
{
    $parts = explode(' ', trim($name));
    $init = '';
    foreach ($parts as $part) {
        if (mb_strlen($init) < 2 && !empty($part)) {
            $init .= mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8');
        }
    }
    return $init ?: 'A';
}

function artist_avatar_color(string $name): string
{
    $colors = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308', '#ec4899', '#14b8a6', '#3b82f6', '#ef4444'];
    return $colors[abs(crc32($name)) % count($colors)];
}

function artist_fmt_date($date): string
{
    if (!$date) return '—';
    $ts = strtotime($date);
    if (!$ts) return '—';
    $diff = time() - $ts;
    if ($diff < 60)     return 'agora';
    if ($diff < 3600)   return floor($diff / 60)    . 'min atrás';
    if ($diff < 86400)  return floor($diff / 3600)  . 'h atrás';
    if ($diff < 604800) return floor($diff / 86400) . 'd atrás';
    return date('d/m/Y', $ts);
}

function artist_sort_url(string $col, string $cur_col, string $cur_dir, array $get): string
{
    $dir = ($col === $cur_col && $cur_dir === 'ASC') ? 'desc' : 'asc';
    return '?' . http_build_query(array_merge($get, ['sort' => $col, 'dir' => $dir, 'page' => 1]));
}

function artist_sort_icon(string $col, string $cur_col, string $cur_dir): string
{
    if ($col !== $cur_col) return '';
    return $cur_dir === 'ASC' ? ' ▲' : ' ▼';
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title>Artistas — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        .artist-s-active {
            background: rgba(34, 197, 94, .15);
            color: #166534;
        }

        .artist-s-inactive {
            background: rgba(107, 114, 128, .15);
            color: #374151;
        }

        .artist-s-blocked {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
        }

        .artist-s-processing {
            background: rgba(234, 179, 8, .15);
            color: #92400e;
        }

        .dark-mode .artist-s-active {
            background: rgba(34, 197, 94, .18);
            color: #4ade80;
        }

        .dark-mode .artist-s-inactive {
            background: rgba(107, 114, 128, .18);
            color: #9ca3af;
        }

        .dark-mode .artist-s-blocked {
            background: rgba(239, 68, 68, .18);
            color: #f87171;
        }

        .dark-mode .artist-s-processing {
            background: rgba(234, 179, 8, .18);
            color: #facc15;
        }

        .artist-stat {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .artist-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .artist-stat-num {
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1;
        }

        .artist-stat-lbl {
            font-size: .74rem;
            opacity: .6;
            margin-top: 2px;
        }

        .filter-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 18px;
        }

        .artist-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 0, 137, .2);
        }

        .artist-avatar-ini {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .7rem;
            color: #fff;
            flex-shrink: 0;
        }

        .owner-avatar-sm {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 4px;
        }

        .owner-ini-sm {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .65rem;
            font-weight: 700;
            color: #fff;
        }

        .actions-dropdown {
            position: relative;
            display: inline-block;
        }

        .btn-actions {
            background: transparent;
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 8px;
            padding: 4px 10px;
            color: var(--text-muted, #888);
            transition: all .2s;
            cursor: pointer;
        }

        .btn-actions:hover {
            background: rgba(255, 0, 137, .1);
            border-color: #FF0089;
            color: #FF0089;
        }

        .actions-dropdown .dropdown-menu {
            position: absolute;
            z-index: 9999;
            min-width: 180px;
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 6px;
            margin: 0;
        }

        .actions-dropdown .dropdown-item {
            font-size: .82rem;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 8px;
            transition: all .2s;
            cursor: pointer;
        }

        .actions-dropdown .dropdown-item:hover {
            background: rgba(255, 0, 137, .08);
        }

        .dark-mode .actions-dropdown .dropdown-menu {
            background: var(--dark-card, #1a1a27);
            border-color: var(--dark-border, #2e2e42);
        }

        .dark-mode .actions-dropdown .dropdown-item:hover {
            background: rgba(255, 0, 137, .15);
        }

        #artists-table th {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
        }

        #artists-table td {
            font-size: .82rem;
            vertical-align: middle;
        }

        .artist-empty {
            text-align: center;
            padding: 48px 24px;
            opacity: .4;
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
                        <h2 class="h4 mb-1"><i class="bi bi-mic-fill me-2"></i>Artistas</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item active text-white-stable">Artistas</li>
                            </ol>
                        </nav>
                    </div>
                    <?php if (hasPermission($admin_id, 'users.edit')): ?>
                        <div class="col-auto ms-auto">
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/add" class="btn btn-sm text-white"
                                style="background:#FF0089;border-color:#FF0089">
                                <i class="bi bi-person-plus me-1"></i>Adicionar Artista
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($feedback): ?>
                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3" role="alert">
                        <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                        <?php echo htmlspecialchars($feedback[2]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <?php
                    $stat_cards = [
                        ['total',   '#FF0089', 'bi-mic',        'Total'],
                        ['active',  '#22c55e', 'bi-check-circle', 'Activos'],
                        ['inactive', '#6b7280', 'bi-pause-circle', 'Inactivos'],
                        ['blocked', '#ef4444', 'bi-lock',       'Bloqueados'],
                    ];
                    foreach ($stat_cards as [$val, $color, $icon, $lbl]):
                        $num = is_int($val) ? $val : (int)$stats[$val];
                    ?>
                        <div class="col-6 col-md-3">
                            <div class="artist-stat">
                                <div class="artist-stat-icon" style="background:<?php echo $color; ?>22">
                                    <i class="bi <?php echo $icon; ?>" style="color:<?php echo $color; ?>"></i>
                                </div>
                                <div>
                                    <div class="artist-stat-num"><?php echo number_format($num); ?></div>
                                    <div class="artist-stat-lbl"><?php echo $lbl; ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filtros -->
                <div class="filter-card">
                    <form method="GET" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist" id="filter-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-1">
                                <label class="form-label">ID</label>
                                <input type="number" class="form-control form-control-sm" name="id"
                                    value="<?php echo htmlspecialchars($f_id); ?>" placeholder="#" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Nome artístico</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="stage"
                                    value="<?php echo htmlspecialchars($f_stage); ?>" placeholder="Stage name" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Nome real</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="real"
                                    value="<?php echo htmlspecialchars($f_real); ?>" placeholder="Real name" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Género</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="genre"
                                    value="<?php echo htmlspecialchars($f_genre); ?>"
                                    placeholder="Hip Hop, Kizomba..." />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">País</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="country"
                                    value="<?php echo htmlspecialchars($f_country); ?>"
                                    placeholder="Angola, Brasil..." />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <select class="form-select form-select-sm filter-instant" name="status">
                                    <option value="">Todos</option>
                                    <?php foreach (['active' => 'Activo', 'inactive' => 'Inactivo', 'blocked' => 'Bloqueado', 'processing' => 'Processando'] as $v => $l): ?>
                                        <option value="<?php echo $v; ?>" <?php echo $f_status === $v ? 'selected' : ''; ?>>
                                            <?php echo $l; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Proprietário</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="owner"
                                    value="<?php echo htmlspecialchars($f_owner); ?>" placeholder="Nome do dono" />
                            </div>
                            <div class="col-md-1 d-flex gap-1">
                                <button type="submit" class="btn btn-sm text-white w-100"
                                    style="background:#FF0089;border-color:#FF0089"><i
                                        class="bi bi-search"></i></button>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist"
                                    class="btn btn-sm btn-outline-secondary" title="Limpar filtros"><i
                                        class="bi bi-x"></i></a>
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
                                <span style="color:#FF0089"><?php echo number_format($total_filtered); ?></span> de
                                <?php echo number_format((int)$stats['total']); ?> artistas
                            <?php else: ?>
                                <?php echo number_format($total_filtered); ?> artistas
                            <?php endif; ?>
                        </span>
                        <span style="font-size:.76rem;opacity:.5">Página <?php echo $page; ?> de
                            <?php echo $total_pages; ?></span>
                    </div>
                    <div class="table-responsive" style="overflow-x: auto; overflow-y: visible !important;">
                        <table class="table table-hover mb-0" id="artists-table">
                            <thead>
                                <tr>
                                    <th style="width:50px"><a
                                            href="<?php echo artist_sort_url('id_artist', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">ID<?php echo artist_sort_icon('id_artist', $sort_col, $sort_dir); ?></a>
                                    </th>
                                    <th style="width:45px">Foto</th>
                                    <th><a href="<?php echo artist_sort_url('stage_name', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">Nome
                                            artístico<?php echo artist_sort_icon('stage_name', $sort_col, $sort_dir); ?></a>
                                    </th>
                                    <th>Nome real</th>
                                    <th>Género</th>
                                    <th>País</th>
                                    <th>Proprietário</th>
                                    <th><a href="<?php echo artist_sort_url('status_artist', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">Estado<?php echo artist_sort_icon('status_artist', $sort_col, $sort_dir); ?></a>
                                    </th>
                                    <th><a href="<?php echo artist_sort_url('creat_artist', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">Criado<?php echo artist_sort_icon('creat_artist', $sort_col, $sort_dir); ?></a>
                                    </th>
                                    <th style="width:60px;text-align:center">Acções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($artists)): ?>
                                    <tr>
                                        <td colspan="10">
                                            <div class="artist-empty"><i class="bi bi-mic-mute"></i>
                                                <p class="mb-0 mt-2">Nenhum artista encontrado para os filtros aplicados.
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($artists as $i => $artist):
                                        $fullname = $artist['stage_name'];
                                        $ini = artist_initials($fullname);
                                        $color = artist_avatar_color($fullname);
                                        $owner_name = trim(($artist['owner_first'] ?? '') . ' ' . ($artist['owner_second'] ?? ''));
                                        $owner_ini = artist_initials($owner_name);
                                        $owner_color = artist_avatar_color($owner_name);
                                        $is_even = $i % 2 === 1;
                                    ?>
                                        <tr
                                            <?php echo $is_even ? 'style="background:var(--table-stripe,rgba(0,0,0,.015))"' : ''; ?>>
                                            <td><span
                                                    style="font-family:monospace;font-size:.74rem;opacity:.55">#<?php echo $artist['id_artist']; ?></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($artist['photo_artist'])): ?>
                                                    <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/artists/<?php echo htmlspecialchars($artist['photo_artist']); ?>"
                                                        class="artist-avatar" alt=""
                                                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                                                    <div class="artist-avatar-ini"
                                                        style="background:<?php echo $color; ?>;display:none"><?php echo $ini; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="artist-avatar-ini" style="background:<?php echo $color; ?>">
                                                        <?php echo $ini; ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="font-weight:600;font-size:.83rem">
                                                    <?php echo htmlspecialchars($artist['stage_name']); ?></div>
                                                <?php if ($artist['album_count']): ?>
                                                    <div style="font-size:.72rem;opacity:.5"><?php echo $artist['album_count']; ?>
                                                        álbum(ens)</div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($artist['real_name'] ?? '—'); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($artist['genre_main'] ?? ''); ?>
                                                <?php if (!empty($artist['genre_secondary'])): ?><br><small><?php echo htmlspecialchars($artist['genre_secondary']); ?></small><?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($artist['country'] ?? '—'); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if (!empty($artist['owner_photo'])): ?>
                                                        <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($artist['owner_photo']); ?>"
                                                            class="owner-avatar-sm" alt=""
                                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                                        <div class="owner-ini-sm"
                                                            style="background:<?php echo $owner_color; ?>;display:none">
                                                            <?php echo $owner_ini; ?></div>
                                                    <?php else: ?>
                                                        <div class="owner-ini-sm" style="background:<?php echo $owner_color; ?>">
                                                            <?php echo $owner_ini; ?></div>
                                                    <?php endif; ?>
                                                    <span
                                                        style="font-size:.78rem"><?php echo htmlspecialchars($owner_name ?: '—'); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo artist_status_badge($artist['status_artist']); ?></td>
                                            <td style="font-size:.78rem;white-space:nowrap">
                                                <?php echo artist_fmt_date($artist['creat_artist']); ?></td>
                                            <td class="text-center">
                                                <div class="actions-dropdown dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button"
                                                        data-bs-toggle="dropdown" data-bs-reference="toggle"
                                                        aria-expanded="false" title="Acções"><i
                                                            class="bi bi-three-dots-vertical"></i></button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item"
                                                                href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/view?id=<?php echo (int)$artist['id_artist']; ?>"><i
                                                                    class="bi bi-eye text-info"></i> Visualizar</a></li>
                                                        <?php if (hasPermission($admin_id, 'users.edit')): ?>
                                                            <li><a class="dropdown-item"
                                                                    href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/edit?id=<?php echo (int)$artist['id_artist']; ?>"><i
                                                                        class="bi bi-pencil text-warning"></i> Editar</a></li>
                                                            <?php if ($artist['status_artist'] === 'active'): ?>
                                                                <li><a class="dropdown-item" href="#"
                                                                        onclick="toggleStatus(<?php echo (int)$artist['id_artist']; ?>,'blocked');return false"><i
                                                                            class="bi bi-lock text-warning"></i> Bloquear</a></li>
                                                            <?php elseif ($artist['status_artist'] === 'blocked'): ?>
                                                                <li><a class="dropdown-item" href="#"
                                                                        onclick="toggleStatus(<?php echo (int)$artist['id_artist']; ?>,'active');return false"><i
                                                                            class="bi bi-unlock text-success"></i> Desbloquear</a></li>
                                                            <?php endif; ?>
                                                            <li>
                                                                <hr class="dropdown-divider my-1">
                                                            </li>
                                                            <li><a class="dropdown-item text-danger" href="#"
                                                                    onclick="deleteArtist(<?php echo (int)$artist['id_artist']; ?>);return false"><i
                                                                        class="bi bi-trash"></i> Excluir</a></li>
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
                            <nav aria-label="Paginação de artistas">
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link"
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"><i
                                                class="bi bi-chevron-left"></i></a></li>
                                    <?php
                                    $start = max(1, $page - 2);
                                    $end = min($total_pages, $page + 2);
                                    if ($start > 1): ?>
                                        <li class="page-item"><a class="page-link"
                                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                                        </li>
                                        <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span>
                                            </li><?php endif; ?>
                                    <?php endif; ?>
                                    <?php for ($i = $start; $i <= $end; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link"
                                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <?php if ($end < $total_pages): ?>
                                        <?php if ($end < $total_pages - 1): ?><li class="page-item disabled"><span
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
                            </nav>
                        </div>
                    <?php endif; ?>
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
        <div class="loader-content"><img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png"
                class="loader-image" alt="" />
            <div class="loader-progress"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.min.js"></script>
    <script>
        (function() {
            'use strict';
            const BASE_URL = '<?php echo APP_URL; ?>';
            const ADMIN_PATH = '<?php echo ADMIN_PATH; ?>';
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const PROCESS = BASE_URL + '/' + ADMIN_PATH + '/artist/process';

            let debounceTimer;
            document.querySelectorAll('.filter-debounce').forEach(el => {
                el.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => document.getElementById('filter-form').submit(),
                        500);
                });
            });
            document.querySelectorAll('.filter-instant').forEach(el => {
                el.addEventListener('change', () => document.getElementById('filter-form').submit());
            });

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

            window.toggleStatus = async function(id, newStatus) {
                const action = newStatus === 'blocked' ? 'bloquear' : 'desbloquear';
                const result = await Swal.fire({
                    title: action.charAt(0).toUpperCase() + action.slice(1) + ' artista?',
                    text: 'Tens a certeza que queres ' + action + ' este artista?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#FF0089',
                    confirmButtonText: 'Sim, ' + action,
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
                        action: 'toggle_artist_status',
                        id_artist: id,
                        new_status: newStatus
                    });
                    if (data.ok) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Actualizado!',
                            text: data.message,
                            confirmButtonColor: '#FF0089'
                        });
                        location.reload();
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
                        text: 'Verifica a tua internet.',
                        confirmButtonColor: '#FF0089'
                    });
                }
            };

            window.deleteArtist = async function(id) {
                const {
                    value: password
                } = await Swal.fire({
                    title: 'Excluir artista',
                    html: '<p class="mb-1">Esta acção é <strong>irreversível</strong>.</p>' +
                        '<p class="text-muted small mb-3">Confirma a tua senha de administrador para continuar.</p>' +
                        '<input type="password" id="swal-pwd" class="swal2-input" placeholder="Senha do admin">',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Excluir',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => {
                        const pwd = document.getElementById('swal-pwd').value;
                        if (!pwd) {
                            Swal.showValidationMessage('A senha é obrigatória.');
                            return false;
                        }
                        return pwd;
                    }
                });
                if (!password) return;

                Swal.fire({
                    title: 'A processar...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                try {
                    const data = await postAction({
                        action: 'delete_artist',
                        id_artist: id,
                        password_confirm: password
                    });
                    if (data.ok) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Eliminado!',
                            text: data.message,
                            confirmButtonColor: '#FF0089'
                        });
                        window.location.href = BASE_URL + '/' + ADMIN_PATH + '/artist';
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
                        text: 'Verifica a tua internet.',
                        confirmButtonColor: '#FF0089'
                    });
                }
            };
        })();
    </script>
</body>

</html>