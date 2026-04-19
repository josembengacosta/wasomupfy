<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Todos os Colaboradores
// Arquivo: wu-panel/pages/collab/all-collaborators.php
// Rota:    wu-panel/collab
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'users.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// ── Feedback ──
$msg      = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'updated'     => ['success', 'bi-check-circle',    'Colaborador actualizado com sucesso.'],
    'deleted'     => ['success', 'bi-trash',            'Colaborador removido com sucesso.'],
    'invite_sent' => ['success', 'bi-envelope-paper',   'Convite reenviado com sucesso.'],
    'blocked'     => ['warning', 'bi-lock',             'Colaborador bloqueado.'],
    'unblocked'   => ['success', 'bi-unlock',           'Colaborador desbloqueado.'],
    'error'       => ['danger',  'bi-x-circle',         'Ocorreu um erro. Tenta novamente.'],
    default       => null,
};

// ── Stats globais ──
$stats = $db->query("
    SELECT
        COUNT(*)                              AS total,
        SUM(status_collab = 'active')         AS active,
        SUM(status_collab = 'pending')        AS pending,
        SUM(status_collab = 'blocked')        AS blocked,
        SUM(status_collab = 'inactive')       AS inactive,
        COUNT(DISTINCT id_users)              AS unique_owners
    FROM _collaborators
")->fetch();

// ── Filtros ──
$per_page  = 15;
$page      = max(1, (int)($_GET['page'] ?? 1));
$f_id      = trim($_GET['id']     ?? '');
$f_name    = trim($_GET['name']   ?? '');
$f_email   = trim($_GET['email']  ?? '');
$f_role    = trim($_GET['role']   ?? '');
$f_status  = trim($_GET['status'] ?? '');
$f_owner   = trim($_GET['owner']  ?? '');
$sort_col  = in_array($_GET['sort'] ?? '', ['id_collab', 'first_name', 'email_collab', 'creat_collab', 'status_collab', 'role_collab'])
    ? $_GET['sort'] : 'creat_collab';
$sort_dir  = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$where  = [];
$params = [];

if ($f_id !== '') {
    $where[]  = 'c.id_collab = ?';
    $params[] = (int)$f_id;
}
if ($f_name !== '') {
    $concat   = "CONCAT(c.first_name,' ',COALESCE(c.second_name,''))";
    $where[]  = "$concat LIKE ?";
    $params[] = '%' . $f_name . '%';
}
if ($f_email !== '') {
    $where[]  = 'c.email_collab LIKE ?';
    $params[] = '%' . $f_email . '%';
}
if ($f_role !== '') {
    $where[]  = 'c.role_collab = ?';
    $params[] = $f_role;
}
if ($f_status !== '') {
    $where[]  = 'c.status_collab = ?';
    $params[] = $f_status;
}
if ($f_owner !== '') {
    $concat_owner = "CONCAT(u.first_name,' ',COALESCE(u.second_name,''))";
    $where[]      = "$concat_owner LIKE ?";
    $params[]     = '%' . $f_owner . '%';
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── Contagem ──
$count_stmt = $db->prepare("
    SELECT COUNT(DISTINCT c.id_collab)
    FROM _collaborators c
    LEFT JOIN _users u ON u.id_users = c.id_users
    $sql_where
");
$count_stmt->execute($params);
$total_filtered = (int)$count_stmt->fetchColumn();
$total_pages    = max(1, (int)ceil($total_filtered / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;

// ── Dados ──
$stmt = $db->prepare("
    SELECT
        c.id_collab,
        c.first_name,
        c.second_name,
        c.user_collab,
        c.email_collab,
        c.tel_collab,
        c.photo_collab,
        c.role_collab,
        c.status_collab,
        c.creat_collab,
        c.modif_collab,
        c.notes,
        c.invite_token_used,
        c.must_change_password,
        u.id_users          AS owner_id,
        u.first_name        AS owner_first_name,
        u.second_name       AS owner_second_name,
        u.photo_user        AS owner_photo,
        (SELECT COUNT(*)     FROM _collab_activity WHERE id_collab = c.id_collab) AS activity_count,
        (SELECT MAX(creat_activity) FROM _collab_activity WHERE id_collab = c.id_collab) AS last_activity
    FROM _collaborators c
    LEFT JOIN _users u ON u.id_users = c.id_users
    $sql_where
    ORDER BY c.$sort_col $sort_dir
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$collaborators = $stmt->fetchAll();

// ── Helpers ──
function collab_status_badge(string $s): string
{
    return match ($s) {
        'active'   => '<span class="badge collab-s-active">Activo</span>',
        'pending'  => '<span class="badge collab-s-pending">Pendente</span>',
        'blocked'  => '<span class="badge collab-s-blocked">Bloqueado</span>',
        'inactive' => '<span class="badge collab-s-inactive">Inactivo</span>',
        default    => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}

function collab_role_badge(string $role): string
{
    $map = [
        'admin'   => ['Administrador', 'bg-danger'],
        'editor'  => ['Editor',        'bg-info text-dark'],
        'analyst' => ['Analista',      'bg-success'],
        'support' => ['Suporte',       'bg-secondary'],
    ];
    [$label, $cls] = $map[$role] ?? [ucfirst($role), 'bg-secondary'];
    return '<span class="badge ' . $cls . '">' . $label . '</span>';
}

function collab_initials(string $first, string $second = ''): string
{
    return mb_strtoupper(mb_substr(trim($first),  0, 1, 'UTF-8'), 'UTF-8')
        . mb_strtoupper(mb_substr(trim($second), 0, 1, 'UTF-8'), 'UTF-8');
}

function collab_avatar_color(string $name): string
{
    $colors = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308', '#ec4899', '#14b8a6', '#3b82f6', '#ef4444'];
    return $colors[abs(crc32($name)) % count($colors)];
}

function collab_fmt_date($date): string
{
    if (!$date) return '—';
    $ts   = strtotime($date);
    if (!$ts) return '—';
    $diff = time() - $ts;
    if ($diff < 60)     return 'agora';
    if ($diff < 3600)   return floor($diff / 60)    . 'min atrás';
    if ($diff < 86400)  return floor($diff / 3600)  . 'h atrás';
    if ($diff < 604800) return floor($diff / 86400) . 'd atrás';
    return date('d/m/Y', $ts);
}

function collab_sort_url(string $col, string $cur_col, string $cur_dir, array $get): string
{
    $dir = ($col === $cur_col && $cur_dir === 'ASC') ? 'desc' : 'asc';
    return '?' . http_build_query(array_merge($get, ['sort' => $col, 'dir' => $dir, 'page' => 1]));
}

function collab_sort_icon(string $col, string $cur_col, string $cur_dir): string
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
    <title>Colaboradores — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        /* Status badges */
        .collab-s-active {
            background: rgba(34, 197, 94, .15);
            color: #166534;
        }

        .collab-s-pending {
            background: rgba(234, 179, 8, .15);
            color: #92400e;
        }

        .collab-s-blocked {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
        }

        .collab-s-inactive {
            background: rgba(107, 114, 128, .15);
            color: #374151;
        }

        .dark-mode .collab-s-active {
            background: rgba(34, 197, 94, .18);
            color: #4ade80;
        }

        .dark-mode .collab-s-pending {
            background: rgba(234, 179, 8, .18);
            color: #facc15;
        }

        .dark-mode .collab-s-blocked {
            background: rgba(239, 68, 68, .18);
            color: #f87171;
        }

        .dark-mode .collab-s-inactive {
            background: rgba(107, 114, 128, .18);
            color: #9ca3af;
        }

        /* Stat cards */
        .collab-stat {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .collab-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .collab-stat-num {
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1;
        }

        .collab-stat-lbl {
            font-size: .74rem;
            opacity: .6;
            margin-top: 2px;
        }

        /* Filter card */
        .filter-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 18px;
        }

        /* Avatar */
        .collab-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 0, 137, .2);
        }

        .collab-avatar-ini {
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

        /* Owner avatar */
        .owner-avatar-sm {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 4px;
        }

        /* Dropdown acções */
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

        .actions-dropdown .dropdown-item i {
            width: 18px;
            font-size: .9rem;
        }

        .dark-mode .actions-dropdown .dropdown-menu {
            background: var(--dark-card, #1a1a27);
            border-color: var(--dark-border, #2e2e42);
        }

        .dark-mode .actions-dropdown .dropdown-item:hover {
            background: rgba(255, 0, 137, .15);
        }

        .dark-mode .btn-actions {
            border-color: var(--dark-border, #2e2e42);
            color: #aaa;
        }

        .dark-mode .btn-actions:hover {
            background: rgba(255, 0, 137, .2);
            border-color: #FF0089;
            color: #FF0089;
        }

        /* Table */
        #collabs-table th {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
        }

        #collabs-table td {
            font-size: .82rem;
            vertical-align: middle;
        }

        /* Pagination */
        .collab-pagination .page-link {
            border-radius: 8px !important;
            margin: 0 2px;
            font-size: .8rem;
        }

        /* Empty state */
        .collab-empty {
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
                        <h2 class="h4 mb-1">
                            <i class="bi bi-people-fill me-2"></i>Colaboradores
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>" class="text-secondary">Home</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable">Colaboradores</li>
                            </ol>
                        </nav>
                    </div>
                    <?php if (hasPermission($admin_id, 'users.edit')): ?>
                        <div class="col-auto ms-auto">
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/add" class="btn btn-sm text-white"
                                style="background:#FF0089;border-color:#FF0089">
                                <i class="bi bi-person-plus me-1"></i>Adicionar Colaborador
                            </a>
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

                <!-- Stat cards -->
                <div class="row g-3 mb-4">
                    <?php
                    $stat_cards = [
                        ['total',                         '#FF0089', 'bi-people',         'Total'],
                        ['active',                        '#22c55e', 'bi-person-check',   'Activos'],
                        ['pending',                       '#eab308', 'bi-hourglass-split', 'Pendentes'],
                        [(int)$stats['blocked'] + (int)$stats['inactive'], '#ef4444', 'bi-person-x', 'Inactivos'],
                    ];
                    foreach ($stat_cards as [$val, $color, $icon, $lbl]):
                        $num = is_int($val) ? $val : (int)$stats[$val];
                    ?>
                        <div class="col-6 col-md-3">
                            <div class="collab-stat">
                                <div class="collab-stat-icon" style="background:<?php echo $color; ?>22">
                                    <i class="bi <?php echo $icon; ?>" style="color:<?php echo $color; ?>"></i>
                                </div>
                                <div>
                                    <div class="collab-stat-num"><?php echo number_format($num); ?></div>
                                    <div class="collab-stat-lbl"><?php echo $lbl; ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filtros -->
                <div class="filter-card">
                    <form method="GET" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab" id="filter-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-1">
                                <label class="form-label">ID</label>
                                <input type="number" class="form-control form-control-sm" name="id"
                                    value="<?php echo htmlspecialchars($f_id); ?>" placeholder="#" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Nome</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="name"
                                    value="<?php echo htmlspecialchars($f_name); ?>"
                                    placeholder="Nome do colaborador" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">E-mail</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="email"
                                    value="<?php echo htmlspecialchars($f_email); ?>" placeholder="email@..." />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Função</label>
                                <select class="form-select form-select-sm filter-instant" name="role">
                                    <option value="">Todas</option>
                                    <?php foreach (['admin' => 'Administrador', 'editor' => 'Editor', 'analyst' => 'Analista', 'support' => 'Suporte'] as $v => $l): ?>
                                        <option value="<?php echo $v; ?>" <?php echo $f_role === $v ? 'selected' : ''; ?>>
                                            <?php echo $l; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <select class="form-select form-select-sm filter-instant" name="status">
                                    <option value="">Todos</option>
                                    <?php foreach (['active' => 'Activo', 'pending' => 'Pendente', 'blocked' => 'Bloqueado', 'inactive' => 'Inactivo'] as $v => $l): ?>
                                        <option value="<?php echo $v; ?>" <?php echo $f_status === $v ? 'selected' : ''; ?>>
                                            <?php echo $l; ?>
                                        </option>
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
                                    style="background:#FF0089;border-color:#FF0089">
                                    <i class="bi bi-search"></i>
                                </button>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab"
                                    class="btn btn-sm btn-outline-secondary" title="Limpar filtros">
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
                                de <?php echo number_format((int)$stats['total']); ?> colaboradores
                            <?php else: ?>
                                <?php echo number_format($total_filtered); ?> colaboradores
                            <?php endif; ?>
                        </span>
                        <span style="font-size:.76rem;opacity:.5">
                            Página <?php echo $page; ?> de <?php echo $total_pages; ?>
                        </span>
                    </div>

                    <div class="table-responsive" style="overflow-x: auto; overflow-y: visible !important;">
                        <table class="table table-hover mb-0" id="collabs-table" style="overflow: visible !important;">
                            <thead>
                                <tr>
                                    <th style="width:50px">
                                        <a href="<?php echo collab_sort_url('id_collab', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">
                                            ID<?php echo collab_sort_icon('id_collab', $sort_col, $sort_dir); ?>
                                        </a>
                                    </th>
                                    <th style="width:45px">Foto</th>
                                    <th>
                                        <a href="<?php echo collab_sort_url('first_name', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">
                                            Nome<?php echo collab_sort_icon('first_name', $sort_col, $sort_dir); ?>
                                        </a>
                                    </th>
                                    <th>E-mail</th>
                                    <th>Função</th>
                                    <th>Proprietário</th>
                                    <th>
                                        <a href="<?php echo collab_sort_url('status_collab', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">
                                            Estado<?php echo collab_sort_icon('status_collab', $sort_col, $sort_dir); ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="<?php echo collab_sort_url('creat_collab', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">
                                            Convite<?php echo collab_sort_icon('creat_collab', $sort_col, $sort_dir); ?>
                                        </a>
                                    </th>
                                    <th>Últ. Actividade</th>
                                    <th style="width:60px;text-align:center">Acções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($collaborators)): ?>
                                    <tr>
                                        <td colspan="10">
                                            <div class="collab-empty">
                                                <i class="bi bi-people"></i>
                                                <p class="mb-0 mt-2">Nenhum colaborador encontrado para os filtros
                                                    aplicados.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($collaborators as $i => $collab):
                                        $fullname   = trim($collab['first_name'] . ' ' . ($collab['second_name'] ?? ''));
                                        $ini        = collab_initials($collab['first_name'], $collab['second_name'] ?? '');
                                        $color      = collab_avatar_color($fullname);
                                        $owner_name = trim($collab['owner_first_name'] . ' ' . ($collab['owner_second_name'] ?? ''));
                                        $owner_ini  = collab_initials($collab['owner_first_name'] ?? '', $collab['owner_second_name'] ?? '');
                                        $owner_color = collab_avatar_color($owner_name);
                                        $is_even    = $i % 2 === 1;
                                    ?>
                                        <tr
                                            <?php echo $is_even ? 'style="background:var(--table-stripe,rgba(0,0,0,.015))"' : ''; ?>>
                                            <!-- ID -->
                                            <td>
                                                <span style="font-family:monospace;font-size:.74rem;opacity:.55">
                                                    #<?php echo $collab['id_collab']; ?>
                                                </span>
                                            </td>
                                            <!-- Avatar -->
                                            <td>
                                                <?php if (!empty($collab['photo_collab'])): ?>
                                                    <img src="<?php echo htmlspecialchars($collab['photo_collab']); ?>"
                                                        class="collab-avatar" alt=""
                                                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                                                    <div class="collab-avatar-ini"
                                                        style="background:<?php echo $color; ?>;display:none">
                                                        <?php echo $ini; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="collab-avatar-ini" style="background:<?php echo $color; ?>">
                                                        <?php echo $ini; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <!-- Nome -->
                                            <td>
                                                <div style="font-weight:600;font-size:.83rem">
                                                    <?php echo htmlspecialchars($fullname); ?>
                                                </div>
                                                <div style="font-size:.72rem;opacity:.5">
                                                    @<?php echo htmlspecialchars($collab['user_collab']); ?>
                                                </div>
                                            </td>
                                            <!-- E-mail -->
                                            <td>
                                                <a href="mailto:<?php echo htmlspecialchars($collab['email_collab']); ?>"
                                                    style="font-size:.8rem;color:inherit;text-decoration:none">
                                                    <?php echo htmlspecialchars($collab['email_collab']); ?>
                                                </a>
                                                <?php if (!empty($collab['tel_collab'])): ?>
                                                    <div style="font-size:.72rem;opacity:.5">
                                                        <?php echo htmlspecialchars($collab['tel_collab']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <!-- Função -->
                                            <td><?php echo collab_role_badge($collab['role_collab']); ?></td>
                                            <!-- Proprietário -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if (!empty($collab['owner_photo'])): ?>
                                                        <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($collab['owner_photo']); ?>"
                                                            class="owner-avatar-sm" alt=""
                                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                                        <div class="owner-ini-sm"
                                                            style="background:<?php echo $owner_color; ?>;display:none">
                                                            <?php echo $owner_ini; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="owner-ini-sm" style="background:<?php echo $owner_color; ?>">
                                                            <?php echo $owner_ini; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span style="font-size:.78rem">
                                                        <?php echo htmlspecialchars($owner_name ?: '—'); ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <!-- Estado -->
                                            <td><?php echo collab_status_badge($collab['status_collab']); ?></td>
                                            <!-- Convite -->
                                            <td style="font-size:.78rem;white-space:nowrap">
                                                <?php echo collab_fmt_date($collab['creat_collab']); ?>
                                                <?php if (!$collab['invite_token_used'] && $collab['status_collab'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark ms-1"
                                                        style="font-size:.6rem">Pendente</span>
                                                <?php endif; ?>
                                            </td>
                                            <!-- Última actividade -->
                                            <td style="font-size:.78rem;white-space:nowrap">
                                                <?php echo collab_fmt_date($collab['last_activity']); ?>
                                                <span
                                                    class="text-muted ms-1">(<?php echo (int)$collab['activity_count']; ?>)</span>
                                            </td>
                                            <!-- Acções -->
                                            <td class="text-center">
                                                <div class="actions-dropdown dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button"
                                                        data-bs-toggle="dropdown" data-bs-reference="toggle"
                                                        aria-expanded="false" title="Acções">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/view?id=<?php echo (int)$collab['id_collab']; ?>">
                                                                <i class="bi bi-eye text-info"></i> Visualizar
                                                            </a>
                                                        </li>
                                                        <?php if (hasPermission($admin_id, 'users.edit')): ?>
                                                            <li>
                                                                <a class="dropdown-item"
                                                                    href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/edit?id=<?php echo (int)$collab['id_collab']; ?>">
                                                                    <i class="bi bi-pencil text-warning"></i> Editar
                                                                </a>
                                                            </li>
                                                            <?php if (!$collab['invite_token_used'] && $collab['status_collab'] === 'pending'): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="#"
                                                                        onclick="resendInvite(<?php echo (int)$collab['id_collab']; ?>);return false">
                                                                        <i class="bi bi-envelope-paper text-primary"></i> Reenviar
                                                                        Convite
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            <?php if ($collab['status_collab'] === 'active'): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="#"
                                                                        onclick="toggleStatus(<?php echo (int)$collab['id_collab']; ?>,'blocked');return false">
                                                                        <i class="bi bi-lock text-warning"></i> Bloquear
                                                                    </a>
                                                                </li>
                                                            <?php elseif ($collab['status_collab'] === 'blocked'): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="#"
                                                                        onclick="toggleStatus(<?php echo (int)$collab['id_collab']; ?>,'active');return false">
                                                                        <i class="bi bi-unlock text-success"></i> Desbloquear
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            <li>
                                                                <hr class="dropdown-divider my-1">
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="#"
                                                                    onclick="deleteCollab(<?php echo (int)$collab['id_collab']; ?>);return false">
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
                            <nav aria-label="Paginação de colaboradores">
                                <ul class="pagination pagination-sm collab-pagination mb-0">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php
                                    $start = max(1, $page - 2);
                                    $end   = min($total_pages, $page + 2);
                                    if ($start > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                                        </li>
                                        <?php if ($start > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php for ($i = $start; $i <= $end; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link"
                                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    <?php if ($end < $total_pages): ?>
                                        <?php if ($end < $total_pages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">
                                                <?php echo $total_pages; ?>
                                            </a>
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
                </div><!-- /card -->

            </div><!-- /container-fluid -->
        </div><!-- /content -->
    </div><!-- /wrapper -->

    <footer>
        <div class="container">
            <div class="col-12 text-center py-2" style="font-size:.8rem">
                <p class="mb-0">© <?php echo date('Y'); ?> Wasom Upfy. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="" />
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
            const PROCESS = BASE_URL + '/' + ADMIN_PATH + '/collab/process';

            // ── Filtro com debounce (campos de texto) ──
            let debounceTimer;
            document.querySelectorAll('.filter-debounce').forEach(function(el) {
                el.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        document.getElementById('filter-form').submit();
                    }, 500);
                });
            });

            // ── Filtro imediato (selects) ──
            document.querySelectorAll('.filter-instant').forEach(function(el) {
                el.addEventListener('change', function() {
                    document.getElementById('filter-form').submit();
                });
            });

            // ── Helper AJAX ──
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

            // ── Reenviar convite ──
            window.resendInvite = async function(id) {
                const result = await Swal.fire({
                    title: 'Reenviar convite?',
                    text: 'Um novo email com as credenciais será enviado para o colaborador.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#FF0089',
                    confirmButtonText: 'Sim, reenviar',
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
                        action: 'resend_invite',
                        id_collab: id
                    });
                    if (data.ok) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Convite reenviado!',
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
                        text: 'Verifica a tua internet e tenta novamente.',
                        confirmButtonColor: '#FF0089'
                    });
                }
            };

            // ── Bloquear / Desbloquear ──
            window.toggleStatus = async function(id, newStatus) {
                const action = newStatus === 'blocked' ? 'bloquear' : 'desbloquear';
                const result = await Swal.fire({
                    title: action.charAt(0).toUpperCase() + action.slice(1) + ' colaborador?',
                    text: 'Tens a certeza que queres ' + action + ' este colaborador?',
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
                        action: 'toggle_collab_status',
                        id_collab: id,
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
                        text: 'Verifica a tua internet e tenta novamente.',
                        confirmButtonColor: '#FF0089'
                    });
                }
            };

            // ── Excluir ──
            window.deleteCollab = async function(id) {
                const {
                    value: password
                } = await Swal.fire({
                    title: 'Excluir colaborador',
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
                        action: 'delete_collaborator',
                        id_collab: id,
                        password_confirm: password
                    });
                    if (data.ok) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Eliminado!',
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
                        text: 'Verifica a tua internet e tenta novamente.',
                        confirmButtonColor: '#FF0089'
                    });
                }
            };

        })();
    </script>
</body>

</html>