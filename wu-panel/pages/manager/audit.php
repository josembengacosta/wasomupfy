<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Log de Auditoria
// Arquivo: admin/pages/manager/audit.php
// Rota:    admin/audit
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'audit.view');

// ── Filtros ──
$per_page       = 25;
$page           = max(1, (int)($_GET['page']   ?? 1));
$f_action       = trim($_GET['action']  ?? '');
$f_entity       = trim($_GET['entity']  ?? '');
$f_emp_id       = (int)($_GET['emp']    ?? 0);
$f_user_id      = (int)($_GET['user']   ?? 0);
$f_ip           = trim($_GET['ip']      ?? '');
$f_date_from    = trim($_GET['from']    ?? '');
$f_date_to      = trim($_GET['to']      ?? '');

$where  = [];
$params = [];

if ($f_action !== '') {
    $where[]  = 'a.action LIKE ?';
    $params[] = '%' . $f_action . '%';
}
if ($f_entity !== '') {
    $where[]  = 'a.entity = ?';
    $params[] = $f_entity;
}
if ($f_emp_id > 0) {
    $where[]  = 'a.id_employees = ?';
    $params[] = $f_emp_id;
}
if ($f_user_id > 0) {
    $where[]  = 'a.id_users = ?';
    $params[] = $f_user_id;
}
if ($f_ip !== '') {
    $where[]  = 'a.ip_address LIKE ?';
    $params[] = '%' . $f_ip . '%';
}
if ($f_date_from !== '') {
    $where[]  = 'a.creat_log >= ?';
    $params[] = $f_date_from . ' 00:00:00';
}
if ($f_date_to !== '') {
    $where[]  = 'a.creat_log <= ?';
    $params[] = $f_date_to . ' 23:59:59';
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── Contagem ──
$count = $db->prepare("SELECT COUNT(*) FROM _audit_log a $sql_where");
$count->execute($params);
$total_filtered = (int)$count->fetchColumn();
$total_pages    = max(1, (int)ceil($total_filtered / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;

// ── Registos ──
$stmt = $db->prepare("
    SELECT
        a.id_log, a.action, a.entity, a.entity_id,
        a.old_value, a.new_value, a.ip_address, a.user_agent,
        a.creat_log,
        a.id_employees, a.id_users,
        CONCAT(e.first_name, ' ', COALESCE(e.second_name,'')) AS emp_name,
        e.role AS emp_role, e.photo_employees,
        u.email_user AS user_email
    FROM _audit_log a
    LEFT JOIN _employees e ON e.id_employees = a.id_employees
    LEFT JOIN _users u     ON u.id_users      = a.id_users
    $sql_where
    ORDER BY a.creat_log DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// ── Totais rápidos (sem filtro) ──
$totals = $db->query("
    SELECT
        COUNT(*)                                          AS total,
        SUM(creat_log >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) AS today,
        SUM(creat_log >= DATE_SUB(NOW(), INTERVAL 7 DAY))   AS week,
        COUNT(DISTINCT ip_address)                        AS ips,
        COUNT(DISTINCT id_employees)                      AS actors,
        SUM(action LIKE 'auth.%')                         AS auth_events,
        SUM(action LIKE 'employees.%')                    AS emp_events,
        SUM(action LIKE 'profile.%')                      AS profile_events
    FROM _audit_log
")->fetch();

// ── Entidades disponíveis para filtro ──
$entities_raw = $db->query("
    SELECT DISTINCT entity FROM _audit_log WHERE entity IS NOT NULL ORDER BY entity
")->fetchAll(PDO::FETCH_COLUMN);

// ── Funcionários para filtro ──
$emp_list = $db->query("
    SELECT id_employees, first_name, second_name
    FROM _employees ORDER BY first_name
")->fetchAll();

// ── Helpers ──
function audit_action_icon(string $a): string
{
    return match (true) {
        str_starts_with($a, 'auth.login')     => 'bi-box-arrow-in-right text-success',
        str_starts_with($a, 'auth.logout')    => 'bi-box-arrow-right text-secondary',
        str_starts_with($a, 'auth.failed')    => 'bi-x-circle text-danger',
        str_starts_with($a, 'auth.password')  => 'bi-key text-warning',
        str_starts_with($a, 'auth.')          => 'bi-shield text-info',
        str_starts_with($a, 'employees.delet') => 'bi-person-x text-danger',
        str_starts_with($a, 'employees.block') => 'bi-lock text-warning',
        str_starts_with($a, 'employees.')     => 'bi-person-gear text-primary',
        str_starts_with($a, 'users.block')    => 'bi-person-slash text-warning',
        str_starts_with($a, 'users.delet')    => 'bi-person-dash text-danger',
        str_starts_with($a, 'users.')         => 'bi-people text-info',
        str_starts_with($a, 'music.')         => 'bi-music-note text-primary',
        str_starts_with($a, 'finances.')      => 'bi-currency-dollar text-success',
        str_starts_with($a, 'profile.')       => 'bi-person-circle text-secondary',
        str_starts_with($a, 'security.')      => 'bi-shield-lock text-danger',
        str_starts_with($a, 'settings.')      => 'bi-sliders text-secondary',
        default                               => 'bi-activity text-muted',
    };
}

function audit_action_color(string $a): string
{
    return match (true) {
        str_contains($a, 'delet') || str_contains($a, 'block') || str_contains($a, 'failed') => 'danger',
        str_contains($a, 'unblock') || str_contains($a, 'login') || str_contains($a, 'ok')  => 'success',
        str_contains($a, 'update') || str_contains($a, 'edit')  => 'warning',
        str_contains($a, 'create') || str_contains($a, 'add')   => 'primary',
        default => 'secondary',
    };
}

function audit_action_label(string $a): string
{
    $map = [
        'auth.login'               => 'Login efectuado',
        'auth.logout'              => 'Logout',
        'auth.failed_login'        => 'Login falhado',
        'auth.password_changed'    => 'Senha alterada',
        'auth.reset_requested'     => 'Reset de senha solicitado',
        'auth.lockscreen_unlocked' => 'Lockscreen desbloqueado',
        'auth.auto_login'          => 'Login automático (cookie)',
        'employees.updated'        => 'Funcionário actualizado',
        'employees.blocked'        => 'Funcionário bloqueado',
        'employees.unblocked'      => 'Funcionário desbloqueado',
        'employees.deleted'        => 'Funcionário eliminado',
        'employees.password_reset' => 'Senha de funcionário redefinida',
        'employees.attempts_cleared' => 'Tentativas de login limpas',
        'employees.sessions_revoked' => 'Sessões de funcionário revogadas',
        'employees.permissions_updated' => 'Permissões actualizadas',
        'profile.update'           => 'Perfil actualizado',
        'profile.photo_update'     => 'Foto de perfil alterada',
        'profile.photo_removed'    => 'Foto de perfil removida',
        'profile.lockscreen_enabled'  => 'Lockscreen activado',
        'profile.lockscreen_disabled' => 'Lockscreen desactivado',
        'security.path_changed'    => 'Caminho do painel alterado',
        'security.htaccess_regen'  => '.htaccess regenerado',
        'security.ip_added'        => 'IP adicionado à whitelist',
        'security.ip_removed'      => 'IP removido da whitelist',
        'security.whitelist_toggled' => 'Whitelist de IPs alterada',
    ];
    return $map[$a] ?? str_replace(['.', '_'], [' → ', ' '], $a);
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <title>Log de Auditoria — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <style>
        /* ── Stat cards ── */
        .aud-stat {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .aud-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .aud-stat-num {
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1;
        }

        .aud-stat-lbl {
            font-size: .74rem;
            opacity: .6;
            margin-top: 2px;
        }

        /* ── Filtros ── */
        .filter-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 18px;
        }

        .filter-card .form-label {
            font-size: .76rem;
            font-weight: 600;
            margin-bottom: 3px;
        }

        /* ── Tabela ── */
        #audit-table th {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 700;
            white-space: nowrap;
        }

        #audit-table td {
            font-size: .82rem;
            vertical-align: middle;
        }

        /* ── Actor avatar ── */
        .aud-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            border: 1.5px solid var(--border-color, #e8e8f0);
            flex-shrink: 0;
        }

        .aud-avatar-ini {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .6rem;
            color: #fff;
            flex-shrink: 0;
        }

        /* ── Action badge ── */
        .aud-action-badge {
            font-size: .7rem;
            padding: 3px 8px;
            border-radius: 20px;
            font-weight: 600;
            white-space: nowrap;
        }

        /* ── Entity pill ── */
        .aud-entity {
            font-size: .72rem;
            font-family: monospace;
            background: var(--border-color, #f0f0f8);
            padding: 2px 7px;
            border-radius: 6px;
            color: var(--text-muted, #888);
        }

        /* ── IP ── */
        .aud-ip {
            font-family: monospace;
            font-size: .75rem;
            color: var(--text-muted, #888);
            white-space: nowrap;
        }

        /* ── Diff modal ── */
        .diff-key {
            font-weight: 700;
            font-size: .8rem;
        }

        .diff-old {
            color: #ef4444;
            text-decoration: line-through;
            font-size: .8rem;
        }

        .diff-new {
            color: #22c55e;
            font-size: .8rem;
        }

        .diff-row {
            display: flex;
            gap: 12px;
            padding: 6px 0;
            border-bottom: 1px solid var(--border-color, #f0f0f8);
        }

        .diff-row:last-child {
            border-bottom: none;
        }

        /* ── Paginação ── */
        .aud-pagination .page-link {
            border-radius: 8px !important;
            margin: 0 2px;
            font-size: .8rem;
        }

        /* ── Empty ── */
        .aud-empty {
            text-align: center;
            padding: 48px 24px;
            opacity: .4;
        }

        .aud-empty i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 10px;
        }

        /* ── Dark mode ── */
        .dark-mode .filter-card,
        .dark-mode .aud-stat {
            background: var(--dark-card, #1a1a27);
            border-color: var(--dark-border, #2e2e42);
        }

        .dark-mode .aud-entity {
            background: rgba(255, 255, 255, .07);
            color: var(--text-muted-dark, #7b7b9a);
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

                <!-- ── Cabeçalho ── -->
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1">
                            <i class="bi bi-journal-text me-2"></i>Log de Auditoria
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>" class="text-secondary">Home</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable">Auditoria</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto">
                        <span style="font-size:.8rem;opacity:.6">
                            <i class="bi bi-database me-1"></i>
                            <?php echo number_format($totals['total']); ?> registos no total
                        </span>
                    </div>
                </div>

                <!-- ── Stats cards ── -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="aud-stat">
                            <div class="aud-stat-icon" style="background:rgba(59,130,246,.12)">
                                <i class="bi bi-clock-history text-primary"></i>
                            </div>
                            <div>
                                <div class="aud-stat-num"><?php echo number_format($totals['today']); ?></div>
                                <div class="aud-stat-lbl">Últimas 24h</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="aud-stat">
                            <div class="aud-stat-icon" style="background:rgba(34,197,94,.12)">
                                <i class="bi bi-calendar-week text-success"></i>
                            </div>
                            <div>
                                <div class="aud-stat-num"><?php echo number_format($totals['week']); ?></div>
                                <div class="aud-stat-lbl">Últimos 7 dias</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="aud-stat">
                            <div class="aud-stat-icon" style="background:rgba(255,0,137,.12)">
                                <i class="bi bi-people" style="color:#FF0089"></i>
                            </div>
                            <div>
                                <div class="aud-stat-num"><?php echo number_format($totals['actors']); ?></div>
                                <div class="aud-stat-lbl">Actores distintos</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="aud-stat">
                            <div class="aud-stat-icon" style="background:rgba(234,179,8,.12)">
                                <i class="bi bi-geo-alt text-warning"></i>
                            </div>
                            <div>
                                <div class="aud-stat-num"><?php echo number_format($totals['ips']); ?></div>
                                <div class="aud-stat-lbl">IPs distintos</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Filtros ── -->
                <div class="filter-card">
                    <form method="GET" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/audit" id="filter-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Acção</label>
                                <input type="text" class="form-control form-control-sm" name="action"
                                    value="<?php echo htmlspecialchars($f_action); ?>"
                                    placeholder="Ex: auth.login, employees…" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Entidade</label>
                                <select class="form-select form-select-sm" name="entity">
                                    <option value="">Todas</option>
                                    <?php foreach ($entities_raw as $ent): ?>
                                        <option value="<?php echo htmlspecialchars($ent); ?>"
                                            <?php echo $f_entity === $ent ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ent); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Funcionário</label>
                                <select class="form-select form-select-sm" name="emp">
                                    <option value="">Todos</option>
                                    <?php foreach ($emp_list as $e): ?>
                                        <option value="<?php echo $e['id_employees']; ?>"
                                            <?php echo $f_emp_id === (int)$e['id_employees'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(trim($e['first_name'] . ' ' . ($e['second_name'] ?? ''))); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">IP</label>
                                <input type="text" class="form-control form-control-sm" name="ip"
                                    value="<?php echo htmlspecialchars($f_ip); ?>" placeholder="192.168…" />
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">De</label>
                                <input type="date" class="form-control form-control-sm" name="from"
                                    value="<?php echo htmlspecialchars($f_date_from); ?>" />
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Até</label>
                                <input type="date" class="form-control form-control-sm" name="to"
                                    value="<?php echo htmlspecialchars($f_date_to); ?>" />
                            </div>
                            <div class="col-md-1 d-flex gap-1">
                                <button type="submit" class="btn btn-sm text-white w-100"
                                    style="background:#FF0089;border-color:#FF0089">
                                    <i class="bi bi-search"></i>
                                </button>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/audit"
                                    class="btn btn-sm btn-outline-secondary" title="Limpar filtros">
                                    <i class="bi bi-x"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ── Resultado + tabela ── -->
                <div class="card p-0" style="border-radius:14px;overflow:hidden">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                        style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                        <span style="font-size:.82rem;font-weight:600">
                            <?php if ($total_filtered !== (int)$totals['total']): ?>
                                <span style="color:#FF0089"><?php echo number_format($total_filtered); ?></span>
                                de <?php echo number_format($totals['total']); ?> registos
                            <?php else: ?>
                                <?php echo number_format($total_filtered); ?> registos
                            <?php endif; ?>
                        </span>
                        <span style="font-size:.76rem;opacity:.5">
                            Página <?php echo $page; ?> de <?php echo $total_pages; ?>
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="audit-table">
                            <thead>
                                <tr>
                                    <th style="width:44px">#</th>
                                    <th>Acção</th>
                                    <th>Actor</th>
                                    <th>Entidade</th>
                                    <th>Alterações</th>
                                    <th>IP</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="aud-empty">
                                                <i class="bi bi-journal-x"></i>
                                                Nenhum registo encontrado para os filtros aplicados.
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log):
                                        $color   = audit_action_color($log['action']);
                                        $icon    = audit_action_icon($log['action']);
                                        $label   = audit_action_label($log['action']);
                                        $has_diff = $log['old_value'] || $log['new_value'];

                                        // Actor name
                                        $actor_name = $log['id_employees']
                                            ? trim($log['emp_name'])
                                            : ($log['id_users'] ? ($log['user_email'] ?? 'Utilizador #' . $log['id_users']) : 'Sistema');

                                        $ini_color = adm_avatar_color($actor_name);
                                        $actor_ini = $log['id_employees']
                                            ? adm_initials(
                                                explode(' ', trim($log['emp_name']))[0] ?? '',
                                                explode(' ', trim($log['emp_name']))[1] ?? ''
                                            )
                                            : '?';
                                    ?>
                                        <tr>
                                            <!-- ID -->
                                            <td>
                                                <span style="font-family:monospace;font-size:.72rem;opacity:.5">
                                                    <?php echo $log['id_log']; ?>
                                                </span>
                                            </td>

                                            <!-- Acção -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi <?php echo $icon; ?>"
                                                        style="font-size:.95rem;width:18px;text-align:center"></i>
                                                    <div>
                                                        <div style="font-weight:600;font-size:.81rem">
                                                            <?php echo htmlspecialchars($label); ?>
                                                        </div>
                                                        <div style="font-size:.7rem;opacity:.55;font-family:monospace">
                                                            <?php echo htmlspecialchars($log['action']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Actor -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if ($log['id_employees']): ?>
                                                        <div class="aud-avatar-ini" style="background:<?php echo $ini_color; ?>">
                                                            <?php echo $actor_ini; ?>
                                                        </div>
                                                        <div>
                                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/view?id=<?php echo $log['id_employees']; ?>"
                                                                style="font-weight:600;font-size:.81rem;color:inherit;text-decoration:none">
                                                                <?php echo htmlspecialchars($actor_name); ?>
                                                            </a>
                                                            <div style="font-size:.7rem;opacity:.5">Admin
                                                                #<?php echo $log['id_employees']; ?></div>
                                                        </div>
                                                    <?php elseif ($log['id_users']): ?>
                                                        <div class="aud-avatar-ini" style="background:#6c63ff">
                                                            <i class="bi bi-person" style="font-size:.7rem"></i>
                                                        </div>
                                                        <div>
                                                            <span style="font-weight:600;font-size:.81rem">
                                                                <?php echo htmlspecialchars($actor_name); ?>
                                                            </span>
                                                            <div style="font-size:.7rem;opacity:.5">User
                                                                #<?php echo $log['id_users']; ?></div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="aud-avatar-ini" style="background:#6b7280">
                                                            <i class="bi bi-gear" style="font-size:.7rem"></i>
                                                        </div>
                                                        <span style="font-size:.81rem;opacity:.6">Sistema</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>

                                            <!-- Entidade -->
                                            <td>
                                                <?php if ($log['entity']): ?>
                                                    <span class="aud-entity"><?php echo htmlspecialchars($log['entity']); ?></span>
                                                    <?php if ($log['entity_id']): ?>
                                                        <span
                                                            style="font-size:.72rem;opacity:.5;margin-left:4px">#<?php echo $log['entity_id']; ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span style="opacity:.3">—</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Alterações -->
                                            <td>
                                                <?php if ($has_diff): ?>
                                                    <button type="button" class="btn btn-sm"
                                                        style="background:rgba(255,0,137,.08);color:#FF0089;border:1px solid rgba(255,0,137,.2);font-size:.72rem;padding:3px 10px;border-radius:20px"
                                                        data-old="<?php echo htmlspecialchars($log['old_value'] ?? ''); ?>"
                                                        data-new="<?php echo htmlspecialchars($log['new_value'] ?? ''); ?>"
                                                        data-action="<?php echo htmlspecialchars($label); ?>"
                                                        onclick="openDiff(this)">
                                                        <i class="bi bi-code-slash me-1"></i>Ver diff
                                                    </button>
                                                <?php else: ?>
                                                    <span style="opacity:.3;font-size:.75rem">—</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- IP -->
                                            <td>
                                                <?php if ($log['ip_address']): ?>
                                                    <button type="button" class="btn btn-link p-0 aud-ip"
                                                        onclick="document.querySelector('[name=ip]').value='<?php echo htmlspecialchars($log['ip_address']); ?>';document.getElementById('filter-form').submit()"
                                                        title="Filtrar por este IP">
                                                        <?php echo htmlspecialchars($log['ip_address']); ?>
                                                    </button>
                                                <?php else: ?>
                                                    <span style="opacity:.3">—</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Data -->
                                            <td style="white-space:nowrap">
                                                <div style="font-size:.8rem;font-weight:600">
                                                    <?php echo adm_fmt_date($log['creat_log']); ?>
                                                </div>
                                                <div style="font-size:.7rem;opacity:.45;font-family:monospace">
                                                    <?php echo date('d/m/Y H:i:s', strtotime($log['creat_log'])); ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- ── Paginação ── -->
                    <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-center py-3">
                            <nav>
                                <ul class="pagination pagination-sm aud-pagination mb-0">
                                    <!-- Anterior -->
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>

                                    <?php
                                    $range = 2;
                                    $start = max(1, $page - $range);
                                    $end   = min($total_pages, $page + $range);
                                    if ($start > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                                        </li>
                                        <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span>
                                            </li><?php endif; ?>
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
                                        <?php if ($end < $total_pages - 1): ?><li class="page-item disabled"><span
                                                    class="page-link">…</span></li><?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?php echo $total_pages; ?></a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Próximo -->
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

    <!-- ══ MODAL — Diff de alterações ══ -->
    <div class="modal fade" id="modalDiff" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title mb-0" style="font-size:.95rem;font-weight:700">
                            <i class="bi bi-code-slash me-2 text-muted"></i>
                            <span id="diff-action-title">Alterações registadas</span>
                        </h5>
                        <p class="mb-0 mt-1" style="font-size:.76rem;opacity:.5">
                            Comparação entre o estado anterior e o novo estado
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-6" id="diff-old-col">
                            <div
                                style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#ef4444;margin-bottom:8px">
                                <i class="bi bi-dash-circle me-1"></i>Estado anterior
                            </div>
                            <div id="diff-old-content"
                                style="background:rgba(239,68,68,.05);border:1px solid rgba(239,68,68,.15);border-radius:10px;padding:14px;font-size:.82rem;min-height:60px">
                            </div>
                        </div>
                        <div class="col-md-6" id="diff-new-col">
                            <div
                                style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#22c55e;margin-bottom:8px">
                                <i class="bi bi-plus-circle me-1"></i>Novo estado
                            </div>
                            <div id="diff-new-content"
                                style="background:rgba(34,197,94,.05);border:1px solid rgba(34,197,94,.15);border-radius:10px;padding:14px;font-size:.82rem;min-height:60px">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-2">© <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos os direitos reservados.</p>
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
    <script>
        window.__BASE_URL__ = '<?php echo APP_URL; ?>';
        window.__ADMIN_PATH__ = '<?php echo ADMIN_PATH; ?>';

        // ── Diff modal ──
        function openDiff(btn) {
            var oldRaw = btn.dataset.old || '';
            var newRaw = btn.dataset.new || '';
            var action = btn.dataset.action || 'Alterações';

            document.getElementById('diff-action-title').textContent = action;

            function renderJSON(raw, el) {
                el.innerHTML = '';
                if (!raw) {
                    el.innerHTML = '<span style="opacity:.4">Sem dados</span>';
                    return;
                }
                try {
                    var obj = JSON.parse(raw);
                    if (typeof obj === 'object' && obj !== null) {
                        Object.entries(obj).forEach(function([k, v]) {
                            var row = document.createElement('div');
                            row.className = 'diff-row';
                            row.innerHTML =
                                '<span class="diff-key">' + escHtml(k) + '</span>' +
                                '<span>' + escHtml(String(v ?? '—')) + '</span>';
                            el.appendChild(row);
                        });
                    } else {
                        el.textContent = raw;
                    }
                } catch (e) {
                    el.innerHTML = '<pre style="font-size:.75rem;margin:0;white-space:pre-wrap">' + escHtml(raw) + '</pre>';
                }
            }

            function escHtml(t) {
                return t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            renderJSON(oldRaw, document.getElementById('diff-old-content'));
            renderJSON(newRaw, document.getElementById('diff-new-content'));

            // Esconder coluna se vazia
            document.getElementById('diff-old-col').style.display = oldRaw ? '' : 'none';
            document.getElementById('diff-new-col').className = oldRaw ? 'col-md-6' : 'col-md-12';

            new bootstrap.Modal(document.getElementById('modalDiff')).show();
        }

        // ── Debounce no filtro de acção (500ms) ──
        (function() {
            var inp = document.querySelector('[name="action"]');
            if (!inp) return;
            var timer;
            inp.addEventListener('input', function() {
                clearTimeout(timer);
                timer = setTimeout(function() {
                    document.getElementById('filter-form').submit();
                }, 500);
            });
        })();
    </script>
</body>

</html>