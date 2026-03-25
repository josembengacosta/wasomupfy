<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Todos os Funcionários
// Arquivo: admin/pages/employees/all-employees.php
// Rota: admin/employees
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'employees.view');


// ── Feedback ──
$msg      = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'added'     => ['success', 'bi-check-circle', 'Funcionário adicionado com sucesso.'],
    'updated'   => ['success', 'bi-check-circle', 'Funcionário actualizado com sucesso.'],
    'deleted'   => ['success', 'bi-trash',         'Funcionário removido com sucesso.'],
    'blocked'   => ['warning', 'bi-lock',           'Funcionário bloqueado.'],
    'unblocked' => ['success', 'bi-unlock',         'Funcionário desbloqueado.'],
    'error'     => ['danger',  'bi-x-circle',       'Ocorreu um erro. Tenta novamente.'],
    default     => null,
};

// ── Stats ──
$stats = $db->query("
    SELECT
        COUNT(*)                              AS total,
        SUM(status_employees = 'active')      AS active,
        SUM(status_employees = 'blocked')     AS blocked,
        SUM(status_employees = 'suspended')   AS suspended,
        SUM(status_employees = 'processing')  AS processing,
        SUM(role = 'super_admin')             AS super_admins,
        SUM(role = 'admin')                   AS admins,
        SUM(role = 'editor')                  AS editors,
        SUM(role = 'support')                 AS supports
    FROM _employees
")->fetch();

// ── Filtros + paginação ──
$per_page      = 15;
$page          = max(1, (int)($_GET['page']   ?? 1));
$filter_id     = trim($_GET['id']     ?? '');
$filter_name   = trim($_GET['name']   ?? '');
$filter_email  = trim($_GET['email']  ?? '');
$filter_role   = $_GET['role']    ?? '';
$filter_status = $_GET['status']  ?? '';

$where  = [];
$params = [];

if ($filter_id !== '') {
    $where[]  = 'e.id_employees = ?';
    $params[] = (int)$filter_id;
}
if ($filter_name !== '') {
    $concat   = "CONCAT(e.first_name,' ',COALESCE(e.second_name,''))";
    $where[]  = "$concat LIKE ?";
    $params[] = '%' . $filter_name . '%';
}
if ($filter_email !== '') {
    $where[]  = 'e.email_employees LIKE ?';
    $params[] = '%' . $filter_email . '%';
}
if ($filter_role !== '') {
    $where[]  = 'e.role = ?';
    $params[] = $filter_role;
}
if ($filter_status !== '') {
    $where[]  = 'e.status_employees = ?';
    $params[] = $filter_status;
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count_stmt = $db->prepare("SELECT COUNT(*) FROM _employees e $sql_where");
$count_stmt->execute($params);
$total_filtered = (int)$count_stmt->fetchColumn();
$total_pages    = max(1, (int)ceil($total_filtered / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;

$stmt = $db->prepare("
    SELECT e.id_employees, e.first_name, e.second_name, e.user_employees,
           e.email_employees, e.tel_employees, e.photo_employees,
           e.role, e.status_employees, e.gender, e.creat_employees,
           s.last_login_at, s.login_attempts
    FROM _employees e
    LEFT JOIN _employees_security s ON s.id_employees = e.id_employees
    $sql_where
    ORDER BY e.creat_employees DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$employees_list = $stmt->fetchAll();

// ── Helpers ──
function emp_role_badge(string $r): string
{
    return match ($r) {
        'super_admin' => '<span class="badge bg-danger">Super Admin</span>',
        'admin'       => '<span class="badge bg-primary">Admin</span>',
        'editor'      => '<span class="badge bg-info text-dark">Editor</span>',
        'support'     => '<span class="badge bg-secondary">Suporte</span>',
        default       => '<span class="badge bg-dark">' . htmlspecialchars($r) . '</span>',
    };
}

function emp_status_badge(string $s): string
{
    return match ($s) {
        'active'     => '<span class="badge emp-s-active">Activo</span>',
        'inactive'   => '<span class="badge emp-s-inactive">Inactivo</span>',
        'blocked'    => '<span class="badge emp-s-blocked">Bloqueado</span>',
        'suspended'  => '<span class="badge emp-s-suspended">Suspenso</span>',
        'processing' => '<span class="badge emp-s-processing">Em processo</span>',
        default      => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}

function emp_gender(string $g): string
{
    return match ($g) {
        'M' => 'Masculino',
        'F' => 'Feminino',
        default => '—'
    };
}

$emp_json = json_encode(array_map(function ($e) {
    return [
        'id'      => (int)$e['id_employees'],
        'nome'    => trim($e['first_name'] . ' ' . ($e['second_name'] ?? '')),
        'user'    => $e['user_employees'] ?? '',
        'email'   => $e['email_employees'],
        'tel'     => $e['tel_employees'] ?? '',
        'role'    => $e['role'],
        'status'  => $e['status_employees'],
        'gender'  => $e['gender'],
        'created' => $e['creat_employees'],
        'login'   => $e['last_login_at'],
        'attempts' => (int)($e['login_attempts'] ?? 0),
    ];
}, $employees_list), JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <title>Todos Funcionários — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/scrollue.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <style>
        .emp-s-active {
            background: rgba(34, 197, 94, .15);
            color: #166534;
        }

        .emp-s-inactive {
            background: rgba(107, 114, 128, .15);
            color: #374151;
        }

        .emp-s-blocked {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
        }

        .emp-s-suspended {
            background: rgba(234, 179, 8, .15);
            color: #92400e;
        }

        .emp-s-processing {
            background: rgba(59, 130, 246, .15);
            color: #1e40af;
        }

        .emp-stat-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: transform .2s;
        }

        .emp-stat-card:hover {
            transform: translateY(-2px);
        }

        .emp-stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .emp-stat-num {
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1;
        }

        .emp-stat-label {
            font-size: .76rem;
            opacity: .6;
            margin-top: 2px;
        }

        .filter-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 18px;
        }

        .filter-card .form-label {
            font-size: .78rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .emp-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 0, 137, .2);
        }

        .emp-avatar-ini {
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

        #employees-table th {
            font-size: .76rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
            user-select: none;
        }

        #employees-table th:hover {
            opacity: .75;
        }

        #employees-table th.sort-asc::after {
            content: " ▲";
            font-size: .6rem;
        }

        #employees-table th.sort-desc::after {
            content: " ▼";
            font-size: .6rem;
        }

        #employees-table td {
            font-size: .83rem;
            vertical-align: middle;
        }

        /* ── Dropdown acções (CORRIGIDO) ── */
        .actions-dropdown {
            position: relative;
            display: inline-block;
        }

        /* Botão de ações */
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

        /* Menu dropdown — posicionado automaticamente pelo Bootstrap */
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

        /* Itens do dropdown */
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

        /* Dark mode */
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

        /* Congela o hover da linha quando o dropdown está aberto — evita reflow */
        #employees-table tbody tr:has(.dropdown.show) {
            background: var(--card-bg, #fff) !important;
        }

        .emp-pagination .page-link {
            border-radius: 8px !important;
            margin: 0 2px;
            font-size: .82rem;
        }

        .emp-empty {
            text-align: center;
            padding: 48px 24px;
            opacity: .5;
        }

        .emp-empty i {
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

                <div class="row mb-3 mt-2 align-items-start">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1">
                            <i class="bi bi-people-fill me-2"></i>Todos Funcionários
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>" class="text-secondary">Home</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable">Funcionários</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2 flex-wrap mt-2">
                        <button class="btn btn-outline-secondary btn-sm" id="btn-export-csv">
                            <i class="bi bi-download me-1"></i>Exportar CSV
                        </button>
                        <?php if (hasPermission($admin_id, 'employees.edit')): ?>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/add" class="btn btn-sm text-white"
                                style="background:#FF0089;border-color:#FF0089">
                                <i class="bi bi-plus me-1"></i>Adicionar Funcionário
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($feedback): ?>
                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3">
                        <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                        <?php echo htmlspecialchars($feedback[2]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <?php
                    $scards = [
                        ['bi-people-fill',   '#FF0089', (int)$stats['total'],                              'Total'],
                        ['bi-person-check',  '#22c55e', (int)$stats['active'],                             'Activos'],
                        ['bi-lock',          '#ef4444', (int)($stats['blocked'] + $stats['suspended']),      'Bloqueados'],
                        ['bi-shield-fill',   '#ef4444', (int)($stats['super_admins'] + $stats['admins']),    'Admins'],
                        ['bi-pencil-square', '#6366f1', (int)$stats['editors'],                            'Editores'],
                        ['bi-headset',       '#6b7280', (int)$stats['supports'],                           'Suporte'],
                    ];
                    foreach ($scards as [$ic, $col, $num, $lbl]):
                    ?>
                        <div class="col-6 col-sm-4 col-lg-2">
                            <div class="emp-stat-card">
                                <div class="emp-stat-icon"
                                    style="background:<?php echo $col; ?>1a;color:<?php echo $col; ?>">
                                    <i class="bi <?php echo $ic; ?>"></i>
                                </div>
                                <div>
                                    <div class="emp-stat-num"><?php echo $num; ?></div>
                                    <div class="emp-stat-label"><?php echo $lbl; ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filtros -->
                <form method="GET" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees" id="filter-form">
                    <div class="filter-card">
                        <div class="row g-3">
                            <div class="col-6 col-md-1">
                                <label class="form-label">ID</label>
                                <input type="number" class="form-control form-control-sm auto-filter" name="id"
                                    placeholder="Ex: 1" min="1" value="<?php echo htmlspecialchars($filter_id); ?>" />
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Nome</label>
                                <input type="text" class="form-control form-control-sm auto-filter" name="name"
                                    placeholder="Nome ou apelido"
                                    value="<?php echo htmlspecialchars($filter_name); ?>" />
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">E-mail</label>
                                <input type="text" class="form-control form-control-sm auto-filter" name="email"
                                    placeholder="email@..." value="<?php echo htmlspecialchars($filter_email); ?>" />
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">Role</label>
                                <select class="form-select form-select-sm instant-filter" name="role">
                                    <option value="">Todos</option>
                                    <option value="super_admin"
                                        <?php echo $filter_role === 'super_admin' ? 'selected' : ''; ?>>Super Admin
                                    </option>
                                    <option value="admin" <?php echo $filter_role === 'admin' ? 'selected' : ''; ?>>
                                        Admin
                                    </option>
                                    <option value="editor" <?php echo $filter_role === 'editor' ? 'selected' : ''; ?>>
                                        Editor
                                    </option>
                                    <option value="support" <?php echo $filter_role === 'support' ? 'selected' : ''; ?>>
                                        Suporte</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">Estado</label>
                                <select class="form-select form-select-sm instant-filter" name="status">
                                    <option value="">Todos</option>
                                    <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>
                                        Activo
                                    </option>
                                    <option value="processing"
                                        <?php echo $filter_status === 'processing' ? 'selected' : ''; ?>>Em processo
                                    </option>
                                    <option value="blocked"
                                        <?php echo $filter_status === 'blocked' ? 'selected' : ''; ?>>
                                        Bloqueado</option>
                                    <option value="suspended"
                                        <?php echo $filter_status === 'suspended' ? 'selected' : ''; ?>>
                                        Suspenso</option>
                                    <option value="inactive"
                                        <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>
                                        Inactivo</option>
                                </select>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees"
                                    class="btn btn-sm btn-outline-secondary w-100" title="Limpar filtros">
                                    <i class="bi bi-eraser"></i>
                                </a>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-muted" style="font-size:.75rem">
                                <i class="bi bi-info-circle me-1"></i>
                                Texto: filtra com 500ms de espera · Selectores: filtro imediato
                            </small>
                            <span style="font-size:.82rem;font-weight:600;color:#FF0089">
                                <?php echo number_format($total_filtered, 0, ',', '.'); ?> resultado(s)
                            </span>
                        </div>
                    </div>
                </form>

                <!-- Tabela -->
                <div class="card stats-card-primary">
                    <div class="table-responsive" style="overflow-x: auto; overflow-y: visible !important;">
                        <table class="table table-hover mb-0" id="employees-table"
                            style="overflow: visible !important;">
                            <thead>
                                <tr>
                                    <th data-col="id">#</th>
                                    <th>Foto</th>
                                    <th data-col="nome">Nome</th>
                                    <th data-col="user">Username</th>
                                    <th data-col="role">Role</th>
                                    <th data-col="email">E-mail</th>
                                    <th data-col="tel">Telefone</th>
                                    <th data-col="status">Estado</th>
                                    <th data-col="created">Membro desde</th>
                                    <th data-col="login">Último login</th>
                                    <th style="width:52px">Acções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employees_list)): ?>
                                    <tr>
                                        <td colspan="11">
                                            <div class="emp-empty">
                                                <i class="bi bi-people"></i>
                                                <p class="mb-0">
                                                    Nenhum funcionário encontrado
                                                    <?php if ($filter_name || $filter_email || $filter_role || $filter_status || $filter_id): ?>
                                                        com esses filtros.
                                                    <?php else: ?>
                                                        .
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($employees_list as $emp):
                                        $fullname = trim($emp['first_name'] . ' ' . ($emp['second_name'] ?? ''));
                                        $ini      = adm_initials($emp['first_name'], $emp['second_name'] ?? '');
                                        $color    = adm_avatar_color($fullname);
                                        $is_me    = (int)$emp['id_employees'] === $admin_id;
                                    ?>
                                        <tr>
                                            <td style="font-family:monospace;font-size:.78rem;opacity:.6">
                                                #<?php echo (int)$emp['id_employees']; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($emp['photo_employees'])): ?>
                                                    <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/employees/<?php echo htmlspecialchars($emp['photo_employees']); ?>"
                                                        alt="" class="emp-avatar"
                                                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                                                    <div class="emp-avatar-ini"
                                                        style="background:<?php echo $color; ?>;display:none">
                                                        <?php echo $ini; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="emp-avatar-ini" style="background:<?php echo $color; ?>">
                                                        <?php echo $ini; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="font-weight:600">
                                                    <?php echo htmlspecialchars($fullname); ?>
                                                    <?php if ($is_me): ?>
                                                        <span class="badge bg-primary ms-1" style="font-size:.6rem">Você</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="font-size:.74rem;opacity:.5">
                                                    <?php echo emp_gender($emp['gender']); ?></div>
                                            </td>
                                            <td style="font-size:.78rem;opacity:.6">
                                                @<?php echo htmlspecialchars($emp['user_employees'] ?? '—'); ?>
                                            </td>
                                            <td><?php echo emp_role_badge($emp['role']); ?></td>
                                            <td style="font-size:.8rem">
                                                <a href="mailto:<?php echo htmlspecialchars($emp['email_employees']); ?>"
                                                    class="text-decoration-none" style="color:inherit">
                                                    <?php echo htmlspecialchars($emp['email_employees']); ?>
                                                </a>
                                            </td>
                                            <td style="font-size:.8rem;opacity:.7">
                                                <?php echo htmlspecialchars($emp['tel_employees'] ?? '—'); ?>
                                            </td>
                                            <td><?php echo emp_status_badge($emp['status_employees']); ?></td>
                                            <td style="font-size:.78rem;opacity:.6;white-space:nowrap">
                                                <?php echo date('d/m/Y', strtotime($emp['creat_employees'])); ?>
                                            </td>
                                            <td style="font-size:.78rem;opacity:.6;white-space:nowrap">
                                                <?php echo $emp['last_login_at'] ? adm_fmt_date($emp['last_login_at']) : '—'; ?>
                                            </td>
                                            <td>
                                                <div class="dropdown actions-dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button"
                                                        data-bs-toggle="dropdown" title="Acções">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/view?id=<?php echo (int)$emp['id_employees']; ?>">
                                                                <i class="bi bi-eye text-info"></i>Visualizar
                                                            </a>
                                                        </li>
                                                        <?php if (hasPermission($admin_id, 'employees.edit') && !$is_me): ?>
                                                            <li>
                                                                <a class="dropdown-item"
                                                                    href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/edit?id=<?php echo (int)$emp['id_employees']; ?>">
                                                                    <i class="bi bi-pencil text-warning"></i>Editar
                                                                </a>
                                                            </li>
                                                            <?php if ($admin_role === 'super_admin' && $emp['role'] !== 'super_admin'): ?>
                                                                <li>
                                                                    <hr class="dropdown-divider my-1">
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item text-danger"
                                                                        href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/delete?id=<?php echo (int)$emp['id_employees']; ?>">
                                                                        <i class="bi bi-trash text-danger"></i>Excluir
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

                    <?php if ($total_pages > 1): ?>
                        <div class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span style="font-size:.8rem;opacity:.6">
                                Página <?php echo $page; ?> de <?php echo $total_pages; ?>
                                &nbsp;·&nbsp;
                                <?php echo number_format($total_filtered, 0, ',', '.'); ?> resultado(s)
                            </span>
                            <nav>
                                <ul class="pagination pagination-sm emp-pagination mb-0">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php
                                    $rs = max(1, $page - 2);
                                    $re = min($total_pages, $page + 2);
                                    if ($rs > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                                        if ($rs > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                    }
                                    for ($p = $rs; $p <= $re; $p++) {
                                        $active = $p === $page ? 'active' : '';
                                        echo '<li class="page-item ' . $active . '"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $p])) . '">' . $p . '</a></li>';
                                    }
                                    if ($re < $total_pages) {
                                        if ($re < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                        echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '">' . $total_pages . '</a></li>';
                                    }
                                    ?>
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

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-2">© 2026 Wasom Upfy. Todos os direitos reservados.</p>
                    <a href="<?php echo APP_URL; ?>/page/politicies/terms" class="me-2">Termos de Uso</a>
                    <a href="<?php echo APP_URL; ?>/page/politicies/privacy" class="me-2">Privacidade</a>
                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/messages/inbox">Suporte</a>
                </div>
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
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.min.js"></script>
    <script>
        window.__EMP_DATA__ = <?php echo $emp_json; ?>;

        document.addEventListener('DOMContentLoaded', function() {

            // ── Auto-filtro com debounce 500ms ──
            var debounceTimer;
            document.querySelectorAll('.auto-filter').forEach(function(el) {
                el.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        document.getElementById('filter-form').submit();
                    }, 500);
                });
            });

            // Selectores: filtro imediato
            document.querySelectorAll('.instant-filter').forEach(function(el) {
                el.addEventListener('change', function() {
                    document.getElementById('filter-form').submit();
                });
            });

            // ── Ordenação client-side ──
            var sortCol = null,
                sortDir = 'asc';
            document.querySelectorAll('#employees-table thead th[data-col]').forEach(function(th) {
                th.addEventListener('click', function() {
                    var col = this.dataset.col;
                    sortDir = (sortCol === col && sortDir === 'asc') ? 'desc' : 'asc';
                    sortCol = col;
                    document.querySelectorAll('#employees-table thead th').forEach(function(h) {
                        h.classList.remove('sort-asc', 'sort-desc');
                    });
                    this.classList.add(sortDir === 'asc' ? 'sort-asc' : 'sort-desc');

                    var tbody = document.querySelector('#employees-table tbody');
                    var rows = Array.from(tbody.querySelectorAll('tr'));
                    var data = window.__EMP_DATA__ || [];

                    rows.sort(function(a, b) {
                        var idA = parseInt((a.querySelector('td:first-child').textContent ||
                            '').replace('#', '')) || 0;
                        var idB = parseInt((b.querySelector('td:first-child').textContent ||
                            '').replace('#', '')) || 0;
                        var dA = data.find(function(e) {
                            return e.id === idA;
                        });
                        var dB = data.find(function(e) {
                            return e.id === idB;
                        });
                        if (!dA || !dB) return 0;
                        var va = dA[col] || '',
                            vb = dB[col] || '';
                        if (col === 'id') {
                            va = dA.id;
                            vb = dB.id;
                        }
                        if (typeof va === 'number') return sortDir === 'asc' ? va - vb :
                            vb - va;
                        va = String(va).toLowerCase();
                        vb = String(vb).toLowerCase();
                        if (va < vb) return sortDir === 'asc' ? -1 : 1;
                        if (va > vb) return sortDir === 'asc' ? 1 : -1;
                        return 0;
                    });
                    rows.forEach(function(r) {
                        tbody.appendChild(r);
                    });
                });
            });

            // ── Exportar CSV ──
            var btnCsv = document.getElementById('btn-export-csv');
            if (btnCsv) {
                btnCsv.addEventListener('click', function() {
                    var data = window.__EMP_DATA__;
                    if (!data || data.length === 0) {
                        alert('Nenhum dado para exportar.');
                        return;
                    }

                    var headers = ['ID', 'Nome', 'Username', 'E-mail', 'Telefone', 'Role', 'Estado',
                        'Género', 'Membro desde', 'Último login'
                    ];
                    var rows = data.map(function(e) {
                        return [e.id, e.nome, e.user, e.email, e.tel, e.role, e.status,
                            e.gender === 'M' ? 'Masculino' : e.gender === 'F' ? 'Feminino' : '',
                            e.created ? e.created.slice(0, 10) : '',
                            e.login ? e.login.slice(0, 10) : ''
                        ];
                    });

                    var csv = headers.join(';') + '\n';
                    rows.forEach(function(r) {
                        csv += r.map(function(v) {
                            return '"' + String(v || '').replace(/"/g, '""') + '"';
                        }).join(';') + '\n';
                    });

                    var blob = new Blob(['\uFEFF' + csv], {
                        type: 'text/csv;charset=utf-8;'
                    });
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'funcionarios_' + new Date().toISOString().slice(0, 10) + '.csv';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                });
            }
        });
    </script>
</body>

</html>