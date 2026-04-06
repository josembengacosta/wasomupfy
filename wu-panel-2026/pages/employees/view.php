<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Visualizar Funcionário
// Arquivo: admin/pages/employees/view.php
// Rota:    admin/employees/view?id=X
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'employees.view');

$id = (int)($_GET['id'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/employees');

// ── Carregar funcionário ──
$stmt = $db->prepare("
    SELECT
        e.id_employees, e.first_name, e.second_name, e.user_employees,
        e.email_employees, e.email_employees_other, e.tel_employees,
        e.photo_employees, e.gender, e.role, e.status_employees,
        e.about_employees, e.url_employees,
        e.country_employees, e.city_employees,
        e.creat_employees, e.modif_employees, e.deactivation_at,
        s.last_login_at, s.last_login_ip, s.login_attempts,
        s.block_until, s.block_level, s.is_fraud_blocked,
        s.lockscreen, s.invite_used
    FROM _employees e
    LEFT JOIN _employees_security s ON s.id_employees = e.id_employees
    WHERE e.id_employees = ?
    LIMIT 1
");
$stmt->execute([$id]);
$emp = $stmt->fetch();

if (!$emp) adminRedirect('/' . ADMIN_PATH . '/employees');

// ── Feedback de acções ──
$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'blocked'   => ['warning', 'bi-lock',         'Funcionário bloqueado. As sessões activas foram encerradas.'],
    'unblocked' => ['success', 'bi-unlock',        'Funcionário desbloqueado com sucesso.'],
    'updated'   => ['success', 'bi-check-circle',  'Dados actualizados com sucesso.'],
    'error'     => ['danger',  'bi-x-circle',      'Ocorreu um erro. Tenta novamente.'],
    default     => null,
};

$fullname  = trim($emp['first_name'] . ' ' . ($emp['second_name'] ?? ''));
$ini       = adm_initials($emp['first_name'], $emp['second_name'] ?? '');
$color     = adm_avatar_color($fullname);
$is_me     = $emp['id_employees'] === $admin_id;

// ── Permissões explícitas ──
$perms_stmt = $db->prepare("
    SELECT permission, granted
    FROM _employees_permissions
    WHERE id_employees = ?
    ORDER BY permission ASC
");
$perms_stmt->execute([$id]);
$perms_rows = $perms_stmt->fetchAll();

// ── Actividade recente (audit log) ──
$audit_stmt = $db->prepare("
    SELECT action, entity, entity_id, creat_log, ip_address
    FROM _audit_log
    WHERE id_employees = ?
    ORDER BY creat_log DESC
    LIMIT 20
");
$audit_stmt->execute([$id]);
$audit_list = $audit_stmt->fetchAll();

// ── Helpers locais ──
function view_role_badge(string $r): string
{
    return match ($r) {
        'super_admin' => '<span class="badge bg-danger">Super Admin</span>',
        'admin'       => '<span class="badge bg-primary">Admin</span>',
        'editor'      => '<span class="badge bg-info text-dark">Editor</span>',
        'support'     => '<span class="badge bg-secondary">Suporte</span>',
        default       => '<span class="badge bg-dark">' . htmlspecialchars($r) . '</span>',
    };
}

function view_status_badge(string $s): string
{
    return match ($s) {
        'active'     => '<span class="badge vw-s-active">Activo</span>',
        'inactive'   => '<span class="badge vw-s-inactive">Inactivo</span>',
        'blocked'    => '<span class="badge vw-s-blocked">Bloqueado</span>',
        'suspended'  => '<span class="badge vw-s-suspended">Suspenso</span>',
        'processing' => '<span class="badge vw-s-processing">Em processo</span>',
        default      => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}

function view_gender(string $g): string
{
    return match ($g) {
        'M' => 'Masculino',
        'F' => 'Feminino',
        default => '—'
    };
}

// Agrupar permissões por módulo
$perm_groups = [
    'employees' => ['label' => 'Admins',       'icon' => 'bi-person-gear',      'perms' => []],
    'users'     => ['label' => 'Utilizadores', 'icon' => 'bi-people',           'perms' => []],
    'music'     => ['label' => 'Músicas',      'icon' => 'bi-music-note-list',  'perms' => []],
    'finances'  => ['label' => 'Finanças',     'icon' => 'bi-currency-dollar',  'perms' => []],
    'analytics' => ['label' => 'Estatísticas', 'icon' => 'bi-graph-up',         'perms' => []],
    'support'   => ['label' => 'Suporte',      'icon' => 'bi-headset',          'perms' => []],
    'audit'     => ['label' => 'Auditoria',    'icon' => 'bi-journal-text',     'perms' => []],
    'settings'  => ['label' => 'Config',       'icon' => 'bi-sliders',          'perms' => []],
];

$perm_map = [];
foreach ($perms_rows as $p) {
    $perm_map[$p['permission']] = (int)$p['granted'];
}

foreach ($perm_map as $perm => $granted) {
    $parts = explode('.', $perm, 2);
    $group = $parts[0] ?? 'other';
    $action = $parts[1] ?? $perm;
    if (isset($perm_groups[$group])) {
        $perm_groups[$group]['perms'][$action] = $granted;
    }
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
    <title><?php echo htmlspecialchars($fullname); ?> — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/scrollue.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <style>
        /* ═══════════════════════════════════════════════════════
   WASOM UPFY v2.0 — view.php styles
   Funcionário · Página de visualização
   ═══════════════════════════════════════════════════════ */

        /* ── Status badges ── */
        .vw-s-active {
            background: rgba(34, 197, 94, .15);
            color: #166534;
        }

        .vw-s-inactive {
            background: rgba(107, 114, 128, .15);
            color: #374151;
        }

        .vw-s-blocked {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
        }

        .vw-s-suspended {
            background: rgba(234, 179, 8, .15);
            color: #92400e;
        }

        .vw-s-processing {
            background: rgba(59, 130, 246, .15);
            color: #1e40af;
        }

        /* Dark mode — status badges */
        .dark-mode .vw-s-active {
            background: rgba(34, 197, 94, .18);
            color: #4ade80;
        }

        .dark-mode .vw-s-inactive {
            background: rgba(107, 114, 128, .18);
            color: #9ca3af;
        }

        .dark-mode .vw-s-blocked {
            background: rgba(239, 68, 68, .18);
            color: #f87171;
        }

        .dark-mode .vw-s-suspended {
            background: rgba(234, 179, 8, .18);
            color: #facc15;
        }

        .dark-mode .vw-s-processing {
            background: rgba(59, 130, 246, .18);
            color: #93c5fd;
        }

        /* ── Hero header ── */
        .emp-hero {
            background: linear-gradient(135deg, #0f0f17 0%, #1a1a2e 60%, #16213e 100%);
            border-radius: 16px;
            padding: 28px 32px;
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, .06);
        }

        /* Blob rosa */
        .emp-hero::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 260px;
            height: 260px;
            background: radial-gradient(circle, rgba(255, 0, 137, .18) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Blob roxo */
        .emp-hero::after {
            content: '';
            position: absolute;
            bottom: -40px;
            left: 30%;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(108, 99, 255, .14) 0%, transparent 70%);
            pointer-events: none;
        }

        /* ── Avatar ── */
        .emp-avatar-wrap {
            position: relative;
            flex-shrink: 0;
        }

        .emp-avatar-lg {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 0, 137, .45);
            box-shadow: 0 0 0 6px rgba(255, 0, 137, .1), 0 8px 24px rgba(0, 0, 0, .3);
            display: block;
        }

        .emp-avatar-ini-lg {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.65rem;
            color: #fff;
            border: 3px solid rgba(255, 0, 137, .45);
            box-shadow: 0 0 0 6px rgba(255, 0, 137, .1), 0 8px 24px rgba(0, 0, 0, .3);
            flex-shrink: 0;
        }

        /* Dot de estado */
        .emp-status-dot {
            position: absolute;
            bottom: 3px;
            right: 3px;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            border: 2.5px solid #1a1a2e;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .4);
        }

        .emp-status-dot.active {
            background: #22c55e;
        }

        .emp-status-dot.blocked {
            background: #ef4444;
        }

        .emp-status-dot.suspended {
            background: #eab308;
        }

        .emp-status-dot.processing {
            background: #3b82f6;
        }

        .emp-status-dot.inactive {
            background: #6b7280;
        }

        /* ── Hero texto ── */
        .emp-hero-name {
            font-size: 1.35rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 6px;
            line-height: 1.25;
        }

        .emp-hero-meta {
            font-size: .81rem;
            color: rgba(255, 255, 255, .5);
            display: flex;
            flex-wrap: wrap;
            gap: 4px 0;
            margin-top: 8px;
        }

        .emp-hero-meta span {
            margin-right: 16px;
            white-space: nowrap;
        }

        .emp-hero-meta i {
            margin-right: 4px;
            color: rgba(255, 0, 137, .75);
        }

        /* ── Info cards ── */
        .info-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 14px;
            padding: 20px 22px;
            transition: box-shadow .2s;
        }

        .dark-mode .info-card {
            background: var(--dark-card, #1a1a27);
            border-color: var(--dark-border, #2e2e42);
            color: var(--text-light, #e8e8f5);
        }

        .info-card-title {
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .55px;
            color: #FF0089;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
        }

        .dark-mode .info-card-title {
            border-bottom-color: var(--dark-border, #2e2e42);
        }

        /* ── Detail rows ── */
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 9px 0;
            border-bottom: 1px solid var(--border-color, #f0f0f8);
            font-size: .84rem;
            gap: 14px;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: var(--text-muted, #888);
            flex-shrink: 0;
            min-width: 115px;
            line-height: 1.5;
        }

        .detail-value {
            font-weight: 600;
            text-align: right;
            word-break: break-all;
            line-height: 1.5;
        }

        .dark-mode .detail-row {
            border-bottom-color: var(--dark-border, #2e2e42);
        }

        .dark-mode .detail-label {
            color: var(--text-muted-dark, #7b7b9a);
        }

        .dark-mode .detail-value {
            color: var(--text-light, #e8e8f5);
        }

        /* ── Bloco "Sobre" ── */
        .about-block {
            margin-top: 14px;
            padding: 14px 16px;
            border-radius: 10px;
            background: var(--border-color, #f8f7fc);
            border: 1px solid var(--border-color, #e8e8f0);
            font-size: .84rem;
            line-height: 1.7;
            color: var(--text, #1a1a2e);
        }

        .dark-mode .about-block {
            background: rgba(255, 255, 255, .04);
            border-color: var(--dark-border, #2e2e42);
            color: var(--text-light, #e8e8f5);
        }

        .about-block-label {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #FF0089;
            margin-bottom: 6px;
        }

        /* ── Permissões ── */
        .perm-group {
            margin-bottom: 12px;
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 10px;
            overflow: hidden;
        }

        .dark-mode .perm-group {
            border-color: var(--dark-border, #2e2e42);
        }

        .perm-group-header {
            background: var(--border-color, #f4f4f8);
            padding: 8px 14px;
            font-size: .75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: .45px;
            color: var(--text, #1a1a2e);
        }

        .dark-mode .perm-group-header {
            background: rgba(255, 255, 255, .04);
            color: var(--text-light, #e8e8f5);
        }

        .perm-group-header i {
            color: #FF0089;
            font-size: .9rem;
        }

        .perm-tags {
            padding: 10px 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .perm-tag {
            font-size: .73rem;
            padding: 3px 10px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
            white-space: nowrap;
        }

        .perm-tag.granted {
            background: rgba(34, 197, 94, .12);
            color: #166534;
            border: 1px solid rgba(34, 197, 94, .25);
        }

        .perm-tag.denied {
            background: rgba(239, 68, 68, .1);
            color: #991b1b;
            border: 1px solid rgba(239, 68, 68, .2);
        }

        .perm-tag.default {
            background: rgba(107, 114, 128, .1);
            color: #374151;
            border: 1px solid rgba(107, 114, 128, .2);
        }

        .dark-mode .perm-tag.granted {
            background: rgba(34, 197, 94, .15);
            color: #4ade80;
            border-color: rgba(34, 197, 94, .25);
        }

        .dark-mode .perm-tag.denied {
            background: rgba(239, 68, 68, .15);
            color: #f87171;
            border-color: rgba(239, 68, 68, .25);
        }

        .dark-mode .perm-tag.default {
            background: rgba(107, 114, 128, .12);
            color: #9ca3af;
            border-color: rgba(107, 114, 128, .2);
        }

        /* ── Audit log ── */
        .audit-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color, #f0f0f8);
            font-size: .82rem;
        }

        .audit-row:last-child {
            border-bottom: none;
        }

        .dark-mode .audit-row {
            border-bottom-color: var(--dark-border, #2e2e42);
        }

        .audit-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
            flex-shrink: 0;
            background: rgba(255, 0, 137, .1);
            color: #FF0089;
        }

        .audit-action {
            font-weight: 600;
            font-size: .81rem;
            color: var(--text, #1a1a2e);
        }

        .audit-meta {
            font-size: .74rem;
            color: var(--text-muted, #888);
            margin-top: 2px;
        }

        .dark-mode .audit-action {
            color: var(--text-light, #e8e8f5);
        }

        .dark-mode .audit-meta {
            color: var(--text-muted-dark, #7b7b9a);
        }

        /* ── Botões de acção no hero ── */
        .status-action-btn {
            padding: 7px 16px;
            border-radius: 8px;
            font-size: .81rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all .2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            white-space: nowrap;
        }

        .status-action-btn:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
        }

        /* ── Indicadores de segurança ── */
        .sec-indicator {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: 10px;
            font-size: .82rem;
            margin-bottom: 8px;
            border: 1px solid;
            transition: box-shadow .2s;
        }

        .sec-indicator:last-child {
            margin-bottom: 0;
        }

        .sec-indicator i {
            font-size: 1rem;
            flex-shrink: 0;
        }

        .sec-indicator>div>div:first-child {
            font-weight: 600;
        }

        .sec-indicator>div>div:last-child {
            font-size: .74rem;
            opacity: .75;
            margin-top: 1px;
        }

        .sec-indicator.ok {
            background: rgba(34, 197, 94, .07);
            border-color: rgba(34, 197, 94, .2);
            color: #166534;
        }

        .sec-indicator.warn {
            background: rgba(234, 179, 8, .07);
            border-color: rgba(234, 179, 8, .25);
            color: #92400e;
        }

        .sec-indicator.danger {
            background: rgba(239, 68, 68, .07);
            border-color: rgba(239, 68, 68, .2);
            color: #991b1b;
        }

        .sec-indicator.neutral {
            background: rgba(107, 114, 128, .07);
            border-color: rgba(107, 114, 128, .2);
            color: #374151;
        }

        .dark-mode .sec-indicator.ok {
            background: rgba(34, 197, 94, .1);
            border-color: rgba(34, 197, 94, .25);
            color: #4ade80;
        }

        .dark-mode .sec-indicator.warn {
            background: rgba(234, 179, 8, .1);
            border-color: rgba(234, 179, 8, .25);
            color: #facc15;
        }

        .dark-mode .sec-indicator.danger {
            background: rgba(239, 68, 68, .1);
            border-color: rgba(239, 68, 68, .25);
            color: #f87171;
        }

        .dark-mode .sec-indicator.neutral {
            background: rgba(107, 114, 128, .1);
            border-color: rgba(107, 114, 128, .2);
            color: #9ca3af;
        }

        /* ── Aviso de sem permissões explícitas ── */
        .perms-default-notice {
            padding: 14px 16px;
            border-radius: 10px;
            font-size: .82rem;
            background: rgba(59, 130, 246, .07);
            border: 1px solid rgba(59, 130, 246, .18);
            color: #1e40af;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.5;
        }

        .dark-mode .perms-default-notice {
            background: rgba(59, 130, 246, .1);
            border-color: rgba(59, 130, 246, .25);
            color: #93c5fd;
        }

        /* ── Empty audit state ── */
        .audit-empty {
            text-align: center;
            padding: 36px 20px;
            opacity: .45;
            font-size: .84rem;
        }

        .audit-empty i {
            font-size: 2rem;
            display: block;
            margin-bottom: 8px;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .emp-hero {
                padding: 20px;
            }

            .emp-hero-name {
                font-size: 1.1rem;
            }

            .emp-avatar-lg,
            .emp-avatar-ini-lg {
                width: 70px;
                height: 70px;
                font-size: 1.3rem;
            }

            .detail-label {
                min-width: 90px;
            }

            .status-action-btn {
                padding: 6px 12px;
                font-size: .78rem;
            }
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
                            <i class="bi bi-person-lines-fill me-2"></i>Perfil do Funcionário
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>" class="text-secondary">Home</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees"
                                        class="text-secondary">Funcionários</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable">
                                    <?php echo htmlspecialchars($emp['first_name']); ?>
                                </li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2 flex-wrap mt-2">
                        <?php if (hasPermission($admin_id, 'employees.edit') && !$is_me): ?>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/edit?id=<?php echo $id; ?>"
                                class="btn btn-sm btn-warning text-dark">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Voltar
                        </a>
                    </div>
                </div>

                <?php if ($feedback): ?>
                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3" role="alert"
                        style="border-radius:12px">
                        <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                        <?php echo htmlspecialchars($feedback[2]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- ── Hero ── -->
                <div class="emp-hero">
                    <div class="d-flex align-items-center gap-4 flex-wrap position-relative" style="z-index:1">
                        <!-- Avatar -->
                        <div class="emp-avatar-wrap">
                            <?php if (!empty($emp['photo_employees'])): ?>
                                <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/employees/<?php echo htmlspecialchars($emp['photo_employees']); ?>"
                                    alt="" class="emp-avatar-lg"
                                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                                <div class="emp-avatar-ini-lg" style="background:<?php echo $color; ?>;display:none">
                                    <?php echo $ini; ?>
                                </div>
                            <?php else: ?>
                                <div class="emp-avatar-ini-lg" style="background:<?php echo $color; ?>">
                                    <?php echo $ini; ?>
                                </div>
                            <?php endif; ?>
                            <div class="emp-status-dot <?php echo $emp['status_employees']; ?>"></div>
                        </div>

                        <!-- Info principal -->
                        <div class="flex-grow-1">
                            <div class="emp-hero-name">
                                <?php echo htmlspecialchars($fullname); ?>
                                <?php if ($is_me): ?>
                                    <span class="badge bg-primary ms-2"
                                        style="font-size:.6rem;vertical-align:middle">Você</span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <?php echo view_role_badge($emp['role']); ?>
                                <?php echo view_status_badge($emp['status_employees']); ?>
                                <?php if ($emp['is_fraud_blocked']): ?>
                                    <span class="badge bg-danger">
                                        <i class="bi bi-shield-exclamation me-1"></i>Bloqueio anti-fraude
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="emp-hero-meta">
                                <span><i
                                        class="bi bi-at"></i><?php echo htmlspecialchars($emp['user_employees'] ?? '—'); ?></span>
                                <span><i
                                        class="bi bi-envelope"></i><?php echo htmlspecialchars($emp['email_employees']); ?></span>
                                <?php if ($emp['tel_employees']): ?>
                                    <span><i
                                            class="bi bi-telephone"></i><?php echo htmlspecialchars($emp['tel_employees']); ?></span>
                                <?php endif; ?>
                                <span><i class="bi bi-calendar3"></i>Desde
                                    <?php echo date('d/m/Y', strtotime($emp['creat_employees'])); ?></span>
                            </div>
                        </div>

                        <!-- Acções rápidas de estado (só super_admin, não em si próprio) -->
                        <?php if ($admin_role === 'super_admin' && !$is_me && $emp['role'] !== 'super_admin'): ?>
                            <div class="d-flex flex-column gap-2">
                                <?php if ($emp['status_employees'] === 'active'): ?>
                                    <button type="button" class="status-action-btn"
                                        style="background:rgba(234,179,8,.15);color:#92400e" data-bs-toggle="modal"
                                        data-bs-target="#modalBlock">
                                        <i class="bi bi-lock-fill"></i>Bloquear
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="status-action-btn"
                                        style="background:rgba(34,197,94,.15);color:#166534" data-bs-toggle="modal"
                                        data-bs-target="#modalUnblock">
                                        <i class="bi bi-unlock-fill"></i>Desbloquear
                                    </button>
                                <?php endif; ?>
                                <!-- Excluir — GET directo para delete.php que mostra confirmação -->
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/delete?id=<?php echo $id; ?>"
                                    class="status-action-btn" style="background:rgba(239,68,68,.12);color:#991b1b">
                                    <i class="bi bi-trash-fill"></i>Excluir
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row g-4">

                    <!-- ══ Coluna esquerda ══ -->
                    <div class="col-xl-8">

                        <!-- Informações pessoais -->
                        <div class="info-card mb-4">
                            <div class="info-card-title">
                                <i class="bi bi-person-badge"></i>Informações Pessoais
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <span class="detail-label">Primeiro Nome</span>
                                        <span
                                            class="detail-value"><?php echo htmlspecialchars($emp['first_name']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Apelido</span>
                                        <span
                                            class="detail-value"><?php echo htmlspecialchars($emp['second_name'] ?? '—'); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Username</span>
                                        <span
                                            class="detail-value">@<?php echo htmlspecialchars($emp['user_employees'] ?? '—'); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Género</span>
                                        <span class="detail-value"><?php echo view_gender($emp['gender']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Telefone</span>
                                        <span
                                            class="detail-value"><?php echo htmlspecialchars($emp['tel_employees'] ?? '—'); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <span class="detail-label">E-mail principal</span>
                                        <span class="detail-value" style="font-size:.8rem">
                                            <a href="mailto:<?php echo htmlspecialchars($emp['email_employees']); ?>"
                                                style="color:inherit"><?php echo htmlspecialchars($emp['email_employees']); ?></a>
                                        </span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">E-mail alternativo</span>
                                        <span class="detail-value" style="font-size:.8rem">
                                            <?php echo $emp['email_employees_other']
                                                ? htmlspecialchars($emp['email_employees_other'])
                                                : '—'; ?>
                                        </span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Website</span>
                                        <span class="detail-value" style="font-size:.8rem">
                                            <?php if ($emp['url_employees']): ?>
                                                <a href="<?php echo htmlspecialchars($emp['url_employees']); ?>"
                                                    target="_blank" style="color:#FF0089">
                                                    <?php echo htmlspecialchars($emp['url_employees']); ?>
                                                </a>
                                                <?php else: ?>—<?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Role</span>
                                        <span class="detail-value"><?php echo view_role_badge($emp['role']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Estado</span>
                                        <span
                                            class="detail-value"><?php echo view_status_badge($emp['status_employees']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">País</span>
                                        <span
                                            class="detail-value"><?php echo htmlspecialchars($emp['country_employees'] ?? '—'); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Cidade</span>
                                        <span
                                            class="detail-value"><?php echo htmlspecialchars($emp['city_employees'] ?? '—'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <?php if ($emp['about_employees']): ?>
                                <div class="mt-3 p-3 rounded"
                                    style="background:var(--border-color,#f8f7fc);font-size:.84rem;line-height:1.7">
                                    <div
                                        style="font-size:.76rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#FF0089;margin-bottom:6px">
                                        Sobre
                                    </div>
                                    <?php echo nl2br(htmlspecialchars($emp['about_employees'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Permissões -->
                        <div class="info-card mb-4">
                            <div class="info-card-title">
                                <i class="bi bi-shield-lock"></i>Permissões
                                <span style="font-size:.72rem;font-weight:400;opacity:.6;margin-left:auto">
                                    <?php echo count($perms_rows) > 0 ? count($perms_rows) . ' regras explícitas' : 'Usando padrões do role'; ?>
                                </span>
                            </div>

                            <?php if (count($perms_rows) === 0): ?>
                                <div class="p-3 rounded text-center"
                                    style="background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.15);font-size:.83rem;color:#1e40af">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Sem regras explícitas — a usar os padrões do role
                                    <strong><?php echo ucfirst($emp['role']); ?></strong>.
                                </div>
                            <?php else: ?>
                                <?php foreach ($perm_groups as $group_key => $group):
                                    if (empty($group['perms'])) continue;
                                ?>
                                    <div class="perm-group">
                                        <div class="perm-group-header">
                                            <i class="bi <?php echo $group['icon']; ?>"></i>
                                            <?php echo $group['label']; ?>
                                        </div>
                                        <div class="perm-tags">
                                            <?php foreach ($group['perms'] as $action => $granted): ?>
                                                <span class="perm-tag <?php echo $granted ? 'granted' : 'denied'; ?>">
                                                    <i
                                                        class="bi bi-<?php echo $granted ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
                                                    <?php echo htmlspecialchars($action); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Actividade recente -->
                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="bi bi-clock-history"></i>Actividade Recente
                                <span style="font-size:.72rem;font-weight:400;opacity:.6;margin-left:auto">
                                    Últimas <?php echo count($audit_list); ?> acções
                                </span>
                            </div>
                            <?php if (empty($audit_list)): ?>
                                <div class="text-center py-4" style="opacity:.4;font-size:.84rem">
                                    <i class="bi bi-journal-x d-block mb-2" style="font-size:1.8rem"></i>
                                    Nenhuma actividade registada ainda.
                                </div>
                            <?php else: ?>
                                <?php foreach ($audit_list as $log):
                                    // Ícone baseado na acção
                                    $aicon = match (true) {
                                        str_starts_with($log['action'], 'auth.')       => 'bi-shield-check',
                                        str_starts_with($log['action'], 'employees.')  => 'bi-person-gear',
                                        str_starts_with($log['action'], 'users.')      => 'bi-people',
                                        str_starts_with($log['action'], 'music.')      => 'bi-music-note',
                                        str_starts_with($log['action'], 'finances.')   => 'bi-currency-dollar',
                                        str_starts_with($log['action'], 'security.')   => 'bi-lock',
                                        default                                         => 'bi-activity',
                                    };
                                ?>
                                    <div class="audit-row">
                                        <div class="audit-icon">
                                            <i class="bi <?php echo $aicon; ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="audit-action"><?php echo htmlspecialchars($log['action']); ?></div>
                                            <div class="audit-meta">
                                                <?php if ($log['entity']): ?>
                                                    <span class="badge bg-secondary me-1" style="font-size:.65rem">
                                                        <?php echo htmlspecialchars($log['entity']); ?>
                                                        <?php echo $log['entity_id'] ? ' #' . $log['entity_id'] : ''; ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($log['ip_address']): ?>
                                                    <span class="me-2"><i
                                                            class="bi bi-geo-alt"></i><?php echo htmlspecialchars($log['ip_address']); ?></span>
                                                <?php endif; ?>
                                                <span><?php echo adm_fmt_date($log['creat_log']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                    </div><!-- /col-xl-8 -->

                    <!-- ══ Coluna direita ══ -->
                    <div class="col-xl-4">

                        <!-- Conta e datas -->
                        <div class="info-card mb-4">
                            <div class="info-card-title">
                                <i class="bi bi-calendar3"></i>Conta
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">ID</span>
                                <span class="detail-value" style="font-family:monospace;font-size:.82rem">
                                    #<?php echo $emp['id_employees']; ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Criada em</span>
                                <span class="detail-value" style="font-size:.82rem">
                                    <?php echo date('d/m/Y H:i', strtotime($emp['creat_employees'])); ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Modificada</span>
                                <span class="detail-value" style="font-size:.82rem">
                                    <?php echo date('d/m/Y H:i', strtotime($emp['modif_employees'])); ?>
                                </span>
                            </div>
                            <?php if ($emp['deactivation_at']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Desactivada</span>
                                    <span class="detail-value text-danger" style="font-size:.82rem">
                                        <?php echo date('d/m/Y H:i', strtotime($emp['deactivation_at'])); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <span class="detail-label">Convite</span>
                                <span class="detail-value">
                                    <?php if ($emp['invite_used'] === null): ?>
                                        <span class="badge vw-s-processing">Sem convite</span>
                                    <?php elseif ($emp['invite_used']): ?>
                                        <span class="badge vw-s-active">Activado</span>
                                    <?php else: ?>
                                        <span class="badge vw-s-suspended">Pendente</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <!-- Segurança e sessão -->
                        <div class="info-card mb-4">
                            <div class="info-card-title">
                                <i class="bi bi-shield-lock"></i>Segurança
                            </div>

                            <!-- Último login -->
                            <div class="detail-row">
                                <span class="detail-label">Último login</span>
                                <span class="detail-value" style="font-size:.8rem">
                                    <?php echo $emp['last_login_at'] ? adm_fmt_date($emp['last_login_at']) : '—'; ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">IP do login</span>
                                <span class="detail-value" style="font-family:monospace;font-size:.8rem">
                                    <?php echo htmlspecialchars($emp['last_login_ip'] ?? '—'); ?>
                                </span>
                            </div>

                            <div class="mt-3 d-flex flex-column gap-2">
                                <!-- Tentativas de login -->
                                <?php $attempts = (int)($emp['login_attempts'] ?? 0); ?>
                                <div
                                    class="sec-indicator <?php echo $attempts === 0 ? 'ok' : ($attempts >= 3 ? 'danger' : 'warn'); ?>">
                                    <i class="bi bi-key"></i>
                                    <div>
                                        <div style="font-weight:600">Tentativas de login</div>
                                        <div style="font-size:.76rem;opacity:.8">
                                            <?php echo $attempts; ?> tentativa<?php echo $attempts !== 1 ? 's' : ''; ?>
                                            falhada<?php echo $attempts !== 1 ? 's' : ''; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bloqueio temporário -->
                                <?php if ($emp['block_until'] && strtotime($emp['block_until']) > time()): ?>
                                    <div class="sec-indicator danger">
                                        <i class="bi bi-clock-fill"></i>
                                        <div>
                                            <div style="font-weight:600">Bloqueado temporariamente</div>
                                            <div style="font-size:.76rem;opacity:.8">
                                                Até <?php echo date('H:i d/m', strtotime($emp['block_until'])); ?>
                                                · Nível <?php echo $emp['block_level']; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Anti-fraude -->
                                <div class="sec-indicator <?php echo $emp['is_fraud_blocked'] ? 'danger' : 'ok'; ?>">
                                    <i
                                        class="bi bi-<?php echo $emp['is_fraud_blocked'] ? 'shield-fill-exclamation' : 'shield-check'; ?>"></i>
                                    <div>
                                        <div style="font-weight:600">Bloqueio anti-fraude</div>
                                        <div style="font-size:.76rem;opacity:.8">
                                            <?php echo $emp['is_fraud_blocked'] ? 'Activo — conta bloqueada por suspeita' : 'Sem alertas de fraude'; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Lockscreen -->
                                <div class="sec-indicator <?php echo $emp['lockscreen'] ? 'warn' : 'neutral'; ?>">
                                    <i class="bi bi-phone<?php echo $emp['lockscreen'] ? '-fill' : ''; ?>"></i>
                                    <div>
                                        <div style="font-weight:600">Lockscreen</div>
                                        <div style="font-size:.76rem;opacity:.8">
                                            <?php echo $emp['lockscreen'] ? 'Activo — requer código para entrar' : 'Inactivo'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Links rápidos -->
                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="bi bi-lightning-charge"></i>Acções Rápidas
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <?php if (hasPermission($admin_id, 'employees.edit') && !$is_me): ?>
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/edit?id=<?php echo $id; ?>"
                                        class="btn btn-sm btn-warning text-dark w-100 text-start">
                                        <i class="bi bi-pencil me-2"></i>Editar este funcionário
                                    </a>
                                <?php endif; ?>
                                <a href="mailto:<?php echo htmlspecialchars($emp['email_employees']); ?>"
                                    class="btn btn-sm btn-outline-secondary w-100 text-start">
                                    <i class="bi bi-envelope me-2"></i>Enviar e-mail
                                </a>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees?id=<?php echo $id; ?>"
                                    class="btn btn-sm btn-outline-secondary w-100 text-start">
                                    <i class="bi bi-arrow-left me-2"></i>Ver na lista
                                </a>
                            </div>
                        </div>

                    </div><!-- /col-xl-4 -->

                </div><!-- /row -->

            </div>
        </div>
    </div>

    <!-- ══ MODAL — Bloquear Funcionário ══ -->
    <div class="modal fade" id="modalBlock" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:42px;height:42px;border-radius:10px;background:rgba(234,179,8,.12);
                                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="bi bi-lock-fill" style="color:#eab308;font-size:1.1rem"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0" style="font-size:.95rem;font-weight:700">Bloquear funcionário
                            </h5>
                            <p class="mb-0" style="font-size:.78rem;opacity:.6">
                                <?php echo htmlspecialchars($fullname); ?>
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <p
                        style="font-size:.85rem;line-height:1.6;margin-bottom:<?php echo $admin_role === 'super_admin' ? '0' : '16px'; ?>">
                        Ao bloquear, este funcionário <strong>não conseguirá aceder ao painel</strong>
                        até ser desbloqueado manualmente.
                    </p>
                    <?php if ($admin_role !== 'super_admin'): ?>
                        <!-- Admins que não são super_admin precisam de confirmar com senha -->
                        <div class="mb-0">
                            <label class="form-label" style="font-size:.8rem;font-weight:600">
                                Confirma a tua senha para continuar
                            </label>
                            <input type="password" class="form-control" id="block-password-inp"
                                placeholder="A tua senha de acesso" autocomplete="current-password" />
                            <div id="block-pw-err" style="font-size:.76rem;color:#ef4444;margin-top:4px;display:none">
                                <i class="bi bi-x-circle me-1"></i>Senha incorrecta.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <?php if ($admin_role === 'super_admin'): ?>
                        <!-- Super admin — submete directamente sem senha -->
                        <form method="POST" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/delete"
                            style="display:inline">
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                            <input type="hidden" name="action" value="block" />
                            <input type="hidden" name="id" value="<?php echo $id; ?>" />
                            <button type="submit" class="btn btn-sm btn-warning text-dark">
                                <i class="bi bi-lock me-1"></i>Confirmar bloqueio
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Outros roles — verificação de senha via JS antes de submeter -->
                        <form method="POST" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/delete"
                            id="form-block" style="display:inline">
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                            <input type="hidden" name="action" value="block" />
                            <input type="hidden" name="id" value="<?php echo $id; ?>" />
                            <input type="hidden" name="confirm_password" id="block-pw-hidden" />
                            <button type="button" class="btn btn-sm btn-warning text-dark" id="btn-confirm-block">
                                <i class="bi bi-lock me-1"></i>Confirmar bloqueio
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ MODAL — Desbloquear Funcionário ══ -->
    <div class="modal fade" id="modalUnblock" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:42px;height:42px;border-radius:10px;background:rgba(34,197,94,.12);
                                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="bi bi-unlock-fill" style="color:#22c55e;font-size:1.1rem"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0" style="font-size:.95rem;font-weight:700">Desbloquear
                                funcionário</h5>
                            <p class="mb-0" style="font-size:.78rem;opacity:.6">
                                <?php echo htmlspecialchars($fullname); ?>
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <p
                        style="font-size:.85rem;line-height:1.6;margin-bottom:<?php echo $admin_role === 'super_admin' ? '0' : '16px'; ?>">
                        O funcionário voltará a ter acesso ao painel com o seu role e permissões anteriores.
                    </p>
                    <?php if ($admin_role !== 'super_admin'): ?>
                        <div class="mb-0">
                            <label class="form-label" style="font-size:.8rem;font-weight:600">
                                Confirma a tua senha para continuar
                            </label>
                            <input type="password" class="form-control" id="unblock-password-inp"
                                placeholder="A tua senha de acesso" autocomplete="current-password" />
                            <div id="unblock-pw-err" style="font-size:.76rem;color:#ef4444;margin-top:4px;display:none">
                                <i class="bi bi-x-circle me-1"></i>Senha incorrecta.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <?php if ($admin_role === 'super_admin'): ?>
                        <form method="POST" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/delete"
                            style="display:inline">
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                            <input type="hidden" name="action" value="unblock" />
                            <input type="hidden" name="id" value="<?php echo $id; ?>" />
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="bi bi-unlock me-1"></i>Confirmar desbloqueio
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/delete"
                            id="form-unblock" style="display:inline">
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                            <input type="hidden" name="action" value="unblock" />
                            <input type="hidden" name="id" value="<?php echo $id; ?>" />
                            <input type="hidden" name="confirm_password" id="unblock-pw-hidden" />
                            <button type="button" class="btn btn-sm btn-success" id="btn-confirm-unblock">
                                <i class="bi bi-unlock me-1"></i>Confirmar desbloqueio
                            </button>
                        </form>
                    <?php endif; ?>
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
    <script src="<?php echo APP_URL; ?>/js/lastest.min.js"></script>
    <script>
        window.__BASE_URL__ = '<?php echo APP_URL; ?>';
        window.__ADMIN_PATH__ = '<?php echo ADMIN_PATH; ?>';
    </script>

    <script>
        // ── Lógica dos modais de bloquear/desbloquear (só para não-super_admin) ──
        // Super admin: form submete directamente, sem JS necessário.
        // Outros: copiar senha para hidden input antes de submeter.

        (function() {
            // ── Bloquear ──
            var btnBlock = document.getElementById('btn-confirm-block');
            if (btnBlock) {
                btnBlock.addEventListener('click', function() {
                    var pw = (document.getElementById('block-password-inp') || {}).value || '';
                    var err = document.getElementById('block-pw-err');
                    if (!pw.trim()) {
                        if (err) {
                            err.style.display = 'block';
                            err.textContent = 'A senha é obrigatória.';
                        }
                        document.getElementById('block-password-inp').focus();
                        return;
                    }
                    document.getElementById('block-pw-hidden').value = pw;
                    document.getElementById('form-block').submit();
                });
                // Esconder erro ao escrever
                var inp = document.getElementById('block-password-inp');
                if (inp) inp.addEventListener('input', function() {
                    var e = document.getElementById('block-pw-err');
                    if (e) e.style.display = 'none';
                });
                // Enter no campo de senha = confirmar
                if (inp) inp.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') btnBlock.click();
                });
            }

            // ── Desbloquear ──
            var btnUnblock = document.getElementById('btn-confirm-unblock');
            if (btnUnblock) {
                btnUnblock.addEventListener('click', function() {
                    var pw = (document.getElementById('unblock-password-inp') || {}).value || '';
                    var err = document.getElementById('unblock-pw-err');
                    if (!pw.trim()) {
                        if (err) {
                            err.style.display = 'block';
                            err.textContent = 'A senha é obrigatória.';
                        }
                        document.getElementById('unblock-password-inp').focus();
                        return;
                    }
                    document.getElementById('unblock-pw-hidden').value = pw;
                    document.getElementById('form-unblock').submit();
                });
                var inp2 = document.getElementById('unblock-password-inp');
                if (inp2) inp2.addEventListener('input', function() {
                    var e = document.getElementById('unblock-pw-err');
                    if (e) e.style.display = 'none';
                });
                if (inp2) inp2.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') btnUnblock.click();
                });
            }

            // Limpar campos de senha ao fechar os modais
            ['modalBlock', 'modalUnblock'].forEach(function(id) {
                var el = document.getElementById(id);
                if (!el) return;
                el.addEventListener('hidden.bs.modal', function() {
                    el.querySelectorAll('input[type="password"]').forEach(function(i) {
                        i.value = '';
                    });
                    el.querySelectorAll('[id$="-pw-err"]').forEach(function(e) {
                        e.style.display = 'none';
                    });
                });
            });
        })();
    </script>
</body>

</html>