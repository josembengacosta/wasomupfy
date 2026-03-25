<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Ver Colaborador
// Arquivo: wu-panel-2026/pages/collab/view.php
// Rota:    wu-panel-2026/collab/view?id=X
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'users.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/collab');

// ── Feedback ──
$msg      = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'blocked'   => ['warning', 'bi-lock',        'Colaborador bloqueado com sucesso.'],
    'unblocked' => ['success', 'bi-unlock',       'Colaborador desbloqueado com sucesso.'],
    'updated'   => ['success', 'bi-check-circle', 'Dados actualizados com sucesso.'],
    'error'     => ['danger',  'bi-x-circle',     'Ocorreu um erro. Tenta novamente.'],
    default     => null,
};

// ── Buscar colaborador ──
$stmt = $db->prepare("
    SELECT
        c.*,
        u.id_users          AS owner_id,
        u.first_name        AS owner_first,
        u.second_name       AS owner_second,
        u.email_user        AS owner_email,
        u.photo_user        AS owner_photo,
        u.status_user       AS owner_status,
        p.name_plan         AS owner_plan
    FROM _collaborators c
    LEFT JOIN _users u       ON u.id_users   = c.id_users
    LEFT JOIN _plans p       ON p.id_plan    = u.plan_selected
    WHERE c.id_collab = ?
");
$stmt->execute([$id]);
$collab = $stmt->fetch();

if (!$collab) {
    adminRedirect('/' . ADMIN_PATH . '/collab?msg=not_found');
}

// ── Actividade recente ──
$activity = $db->prepare("
    SELECT activity_type, description, ip_address, creat_activity
    FROM _collab_activity
    WHERE id_collab = ?
    ORDER BY creat_activity DESC
    LIMIT 20
");
$activity->execute([$id]);
$activity_list = $activity->fetchAll();

// ── Sessões activas ──
$sessions = $db->prepare("
    SELECT session_token, ip_address, user_agent, last_activity, creat_session, is_active
    FROM _collab_sessions
    WHERE id_collab = ? AND is_active = 1
    ORDER BY last_activity DESC
    LIMIT 5
");
$sessions->execute([$id]);
$session_list = $sessions->fetchAll();

// ── Helpers ──
function cv_fmt_date($date, bool $relative = true): string
{
    if (!$date) return '—';
    $ts   = strtotime($date);
    if (!$ts) return '—';
    if (!$relative) return date('d/m/Y H:i', $ts);
    $diff = time() - $ts;
    if ($diff < 60)     return 'agora';
    if ($diff < 3600)   return floor($diff / 60)    . ' min atrás';
    if ($diff < 86400)  return floor($diff / 3600)  . 'h atrás';
    if ($diff < 604800) return floor($diff / 86400) . 'd atrás';
    return date('d/m/Y', $ts);
}

function cv_initials(string $a, string $b = ''): string
{
    return mb_strtoupper(mb_substr(trim($a), 0, 1, 'UTF-8'))
        . mb_strtoupper(mb_substr(trim($b), 0, 1, 'UTF-8'));
}

function cv_avatar_color(string $name): string
{
    $colors = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308', '#ec4899', '#14b8a6', '#3b82f6', '#ef4444'];
    return $colors[abs(crc32($name)) % count($colors)];
}

function cv_status_badge(string $s): string
{
    return match ($s) {
        'active'   => '<span class="badge cv-s-active">Activo</span>',
        'pending'  => '<span class="badge cv-s-pending">Pendente</span>',
        'blocked'  => '<span class="badge cv-s-blocked">Bloqueado</span>',
        'inactive' => '<span class="badge cv-s-inactive">Inactivo</span>',
        default    => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}

function cv_role_label(string $role): string
{
    return match ($role) {
        'admin'   => 'Administrador',
        'editor'  => 'Editor',
        'analyst' => 'Analista',
        'support' => 'Suporte',
        default   => ucfirst($role),
    };
}

function cv_role_color(string $role): string
{
    return match ($role) {
        'admin'   => '#ef4444',
        'editor'  => '#06b6d4',
        'analyst' => '#22c55e',
        'support' => '#6b7280',
        default   => '#8b5cf6',
    };
}

function cv_activity_icon(string $type): array
{
    return match (true) {
        str_contains($type, 'login')   => ['bi-box-arrow-in-right', '#22c55e'],
        str_contains($type, 'logout')  => ['bi-box-arrow-right',    '#6b7280'],
        str_contains($type, 'blocked') => ['bi-lock',               '#ef4444'],
        str_contains($type, 'invite')  => ['bi-envelope-paper',     '#3b82f6'],
        str_contains($type, 'status')  => ['bi-arrow-repeat',       '#f97316'],
        str_contains($type, 'edit')    => ['bi-pencil',             '#eab308'],
        str_contains($type, 'delete')  => ['bi-trash',              '#ef4444'],
        default                        => ['bi-activity',            '#8b5cf6'],
    };
}

function cv_browser(string $ua): string
{
    if (str_contains($ua, 'Firefox'))  return 'Firefox';
    if (str_contains($ua, 'Edg'))      return 'Edge';
    if (str_contains($ua, 'Chrome'))   return 'Chrome';
    if (str_contains($ua, 'Safari'))   return 'Safari';
    if (str_contains($ua, 'Opera'))    return 'Opera';
    return 'Outro';
}

$fullname    = trim($collab['first_name'] . ' ' . ($collab['second_name'] ?? ''));
$ini         = cv_initials($collab['first_name'], $collab['second_name'] ?? '');
$color       = cv_avatar_color($fullname);
$role_label  = cv_role_label($collab['role_collab']);
$role_color  = cv_role_color($collab['role_collab']);
$owner_name  = trim(($collab['owner_first'] ?? '') . ' ' . ($collab['owner_second'] ?? ''));
$owner_ini   = cv_initials($collab['owner_first'] ?? '', $collab['owner_second'] ?? '');
$owner_color = cv_avatar_color($owner_name);
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title><?php echo htmlspecialchars($fullname); ?> — Colaborador · Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        /* ── Status ── */
        .cv-s-active {
            background: rgba(34, 197, 94, .15);
            color: #166534;
        }

        .cv-s-pending {
            background: rgba(234, 179, 8, .15);
            color: #92400e;
        }

        .cv-s-blocked {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
        }

        .cv-s-inactive {
            background: rgba(107, 114, 128, .15);
            color: #374151;
        }

        .dark-mode .cv-s-active {
            background: rgba(34, 197, 94, .2);
            color: #4ade80;
        }

        .dark-mode .cv-s-pending {
            background: rgba(234, 179, 8, .2);
            color: #facc15;
        }

        .dark-mode .cv-s-blocked {
            background: rgba(239, 68, 68, .2);
            color: #f87171;
        }

        .dark-mode .cv-s-inactive {
            background: rgba(107, 114, 128, .2);
            color: #9ca3af;
        }

        /* ── Hero ── */
        .cv-hero {
            background: linear-gradient(135deg, #0f0f1a 0%, #1a0a12 60%, #0f0f1a 100%);
            border-radius: 16px;
            padding: 32px;
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .cv-hero::before {
            content: '';
            position: absolute;
            top: -60px;
            left: -60px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 0, 137, .18) 0%, transparent 70%);
            pointer-events: none;
        }

        .cv-hero::after {
            content: '';
            position: absolute;
            bottom: -40px;
            right: -40px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(108, 99, 255, .14) 0%, transparent 70%);
            pointer-events: none;
        }

        .cv-avatar-wrap {
            position: relative;
            display: inline-block;
        }

        .cv-avatar-lg {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 0, 137, .4);
        }

        .cv-avatar-ini-lg {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.6rem;
            color: #fff;
            border: 3px solid rgba(255, 255, 255, .15);
            flex-shrink: 0;
        }

        .cv-status-dot {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid #0f0f1a;
        }

        .cv-role-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .4px;
            border: 1px solid;
        }

        /* ── Cards ── */
        .cv-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 14px;
            padding: 20px 22px;
            margin-bottom: 20px;
        }

        .cv-card-title {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            opacity: .5;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .cv-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 9px 0;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
            font-size: .83rem;
            gap: 12px;
        }

        .cv-detail-row:last-child {
            border-bottom: none;
        }

        .cv-detail-label {
            opacity: .5;
            flex-shrink: 0;
            min-width: 110px;
        }

        .cv-detail-value {
            font-weight: 500;
            text-align: right;
            word-break: break-word;
        }

        /* ── Activity ── */
        .cv-activity-item {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
            align-items: flex-start;
        }

        .cv-activity-item:last-child {
            border-bottom: none;
        }

        .cv-activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
            flex-shrink: 0;
        }

        .cv-activity-type {
            font-size: .8rem;
            font-weight: 600;
        }

        .cv-activity-meta {
            font-size: .73rem;
            opacity: .5;
        }

        /* ── Session ── */
        .cv-session-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
        }

        .cv-session-item:last-child {
            border-bottom: none;
        }

        /* ── Owner card ── */
        .cv-owner-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px;
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            text-decoration: none;
            color: inherit;
            transition: all .2s;
        }

        .cv-owner-card:hover {
            border-color: #FF0089;
            background: rgba(255, 0, 137, .04);
            color: inherit;
        }

        .cv-owner-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 0, 137, .2);
            flex-shrink: 0;
        }

        .cv-owner-ini {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .75rem;
            color: #fff;
            flex-shrink: 0;
        }

        /* ── Action buttons ── */
        .cv-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 10px;
            font-size: .82rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all .2s;
            cursor: pointer;
        }

        /* ── Security indicator ── */
        .cv-sec-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 0;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
            font-size: .82rem;
        }

        .cv-sec-item:last-child {
            border-bottom: none;
        }

        .cv-sec-label {
            opacity: .55;
            font-size: .78rem;
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

                <!-- Breadcrumb -->
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1"><i class="bi bi-person-badge me-2"></i>Colaborador</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>" class="text-secondary">Home</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab"
                                        class="text-secondary">Colaboradores</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable">
                                    <?php echo htmlspecialchars($fullname); ?></li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        <?php if (hasPermission($admin_id, 'users.edit')): ?>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/edit?id=<?php echo $id; ?>"
                                class="cv-action-btn text-white" style="background:#FF0089;border-color:#FF0089">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab" class="cv-action-btn"
                            style="border-color:var(--border-color,#e8e8f0)">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                    </div>
                </div>

                <!-- Feedback -->
                <?php if ($feedback): ?>
                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3" role="alert">
                        <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                        <?php echo htmlspecialchars($feedback[2]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4">

                    <!-- ═══ Coluna Principal (8/12) ═══ -->
                    <div class="col-lg-8">

                        <!-- Hero -->
                        <div class="cv-hero">
                            <div class="d-flex align-items-center gap-4 position-relative" style="z-index:1">
                                <!-- Avatar -->
                                <div class="cv-avatar-wrap flex-shrink-0">
                                    <?php if (!empty($collab['photo_collab'])): ?>
                                        <img src="<?php echo htmlspecialchars($collab['photo_collab']); ?>"
                                            class="cv-avatar-lg" alt=""
                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                                        <div class="cv-avatar-ini-lg" style="background:<?php echo $color; ?>;display:none">
                                            <?php echo $ini; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="cv-avatar-ini-lg" style="background:<?php echo $color; ?>">
                                            <?php echo $ini; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php
                                    $dot_color = match ($collab['status_collab']) {
                                        'active'   => '#22c55e',
                                        'pending'  => '#eab308',
                                        'blocked'  => '#ef4444',
                                        default    => '#6b7280',
                                    };
                                    ?>
                                    <div class="cv-status-dot" style="background:<?php echo $dot_color; ?>"></div>
                                </div>

                                <!-- Info -->
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <h3 class="text-white mb-0" style="font-size:1.35rem;font-weight:800">
                                            <?php echo htmlspecialchars($fullname); ?>
                                        </h3>
                                        <?php echo cv_status_badge($collab['status_collab']); ?>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        <span class="cv-role-pill"
                                            style="color:<?php echo $role_color; ?>;border-color:<?php echo $role_color; ?>33;background:<?php echo $role_color; ?>15">
                                            <i class="bi bi-shield-fill"></i>
                                            <?php echo $role_label; ?>
                                        </span>
                                        <span style="color:rgba(255,255,255,.5);font-size:.82rem">
                                            @<?php echo htmlspecialchars($collab['user_collab']); ?>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-3"
                                        style="font-size:.8rem;color:rgba(255,255,255,.6)">
                                        <span><i
                                                class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($collab['email_collab']); ?></span>
                                        <?php if (!empty($collab['tel_collab'])): ?>
                                            <span><i
                                                    class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($collab['tel_collab']); ?></span>
                                        <?php endif; ?>
                                        <span><i class="bi bi-calendar3 me-1"></i>Desde
                                            <?php echo date('d/m/Y', strtotime($collab['creat_collab'])); ?></span>
                                    </div>
                                </div>

                                <!-- Acções rápidas (só para users.edit) -->
                                <?php if (hasPermission($admin_id, 'users.edit')): ?>
                                    <div class="flex-shrink-0 d-none d-md-flex flex-column gap-2" style="min-width:130px">
                                        <?php if ($collab['status_collab'] === 'active'): ?>
                                            <button onclick="toggleStatus(<?php echo $id; ?>,'blocked')"
                                                class="cv-action-btn text-white"
                                                style="background:rgba(239,68,68,.2);border-color:rgba(239,68,68,.4);color:#f87171!important;justify-content:center">
                                                <i class="bi bi-lock"></i> Bloquear
                                            </button>
                                        <?php elseif ($collab['status_collab'] === 'blocked'): ?>
                                            <button onclick="toggleStatus(<?php echo $id; ?>,'active')" class="cv-action-btn"
                                                style="background:rgba(34,197,94,.2);border-color:rgba(34,197,94,.4);color:#4ade80;justify-content:center">
                                                <i class="bi bi-unlock"></i> Desbloquear
                                            </button>
                                        <?php endif; ?>
                                        <?php if (!$collab['invite_token_used'] && $collab['status_collab'] === 'pending'): ?>
                                            <button onclick="resendInvite(<?php echo $id; ?>)" class="cv-action-btn"
                                                style="background:rgba(59,130,246,.2);border-color:rgba(59,130,246,.4);color:#60a5fa;justify-content:center">
                                                <i class="bi bi-envelope-paper"></i> Reenviar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Informações Pessoais -->
                        <div class="cv-card">
                            <div class="cv-card-title"><i class="bi bi-person"></i> Informações Pessoais</div>
                            <div class="cv-detail-row">
                                <span class="cv-detail-label">Nome completo</span>
                                <span class="cv-detail-value"><?php echo htmlspecialchars($fullname); ?></span>
                            </div>
                            <div class="cv-detail-row">
                                <span class="cv-detail-label">Username</span>
                                <span
                                    class="cv-detail-value"><code>@<?php echo htmlspecialchars($collab['user_collab']); ?></code></span>
                            </div>
                            <div class="cv-detail-row">
                                <span class="cv-detail-label">E-mail</span>
                                <span class="cv-detail-value">
                                    <a href="mailto:<?php echo htmlspecialchars($collab['email_collab']); ?>"
                                        style="color:inherit"><?php echo htmlspecialchars($collab['email_collab']); ?></a>
                                </span>
                            </div>
                            <?php if (!empty($collab['tel_collab'])): ?>
                                <div class="cv-detail-row">
                                    <span class="cv-detail-label">Telefone</span>
                                    <span
                                        class="cv-detail-value"><?php echo htmlspecialchars($collab['tel_collab']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="cv-detail-row">
                                <span class="cv-detail-label">Função</span>
                                <span class="cv-detail-value">
                                    <span class="badge"
                                        style="background:<?php echo $role_color; ?>22;color:<?php echo $role_color; ?>">
                                        <?php echo $role_label; ?>
                                    </span>
                                </span>
                            </div>
                            <div class="cv-detail-row">
                                <span class="cv-detail-label">Estado</span>
                                <span
                                    class="cv-detail-value"><?php echo cv_status_badge($collab['status_collab']); ?></span>
                            </div>
                            <?php if (!empty($collab['notes'])): ?>
                                <div class="cv-detail-row">
                                    <span class="cv-detail-label">Notas</span>
                                    <span class="cv-detail-value" style="font-style:italic;opacity:.7">
                                        <?php echo nl2br(htmlspecialchars($collab['notes'])); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="cv-detail-row">
                                <span class="cv-detail-label">ID</span>
                                <span class="cv-detail-value">
                                    <code
                                        style="font-size:.77rem">#<?php echo str_pad($id, 6, '0', STR_PAD_LEFT); ?></code>
                                </span>
                            </div>
                            <div class="cv-detail-row">
                                <span class="cv-detail-label">Desde</span>
                                <span
                                    class="cv-detail-value"><?php echo date('d/m/Y \à\s H:i', strtotime($collab['creat_collab'])); ?></span>
                            </div>
                            <?php if ($collab['modif_collab'] && $collab['modif_collab'] !== $collab['creat_collab']): ?>
                                <div class="cv-detail-row">
                                    <span class="cv-detail-label">Última alteração</span>
                                    <span
                                        class="cv-detail-value"><?php echo date('d/m/Y \à\s H:i', strtotime($collab['modif_collab'])); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Actividade Recente -->
                        <div class="cv-card">
                            <div class="cv-card-title">
                                <i class="bi bi-activity"></i> Actividade Recente
                                <span class="ms-auto badge bg-secondary" style="font-size:.65rem;font-weight:600">
                                    <?php echo count($activity_list); ?>
                                </span>
                            </div>
                            <?php if (empty($activity_list)): ?>
                                <div class="text-center py-4" style="opacity:.35">
                                    <i class="bi bi-clock-history"
                                        style="font-size:2rem;display:block;margin-bottom:8px"></i>
                                    <span style="font-size:.83rem">Nenhuma actividade registada</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($activity_list as $act):
                                    [$icon, $icon_color] = cv_activity_icon($act['activity_type']);
                                    $desc = $act['description'] ?: str_replace('_', ' ', $act['activity_type']);
                                ?>
                                    <div class="cv-activity-item">
                                        <div class="cv-activity-icon" style="background:<?php echo $icon_color; ?>18">
                                            <i class="bi <?php echo $icon; ?>" style="color:<?php echo $icon_color; ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="cv-activity-type"><?php echo htmlspecialchars($desc); ?></div>
                                            <div class="cv-activity-meta">
                                                <?php if (!empty($act['ip_address'])): ?>
                                                    <i class="bi bi-geo-alt"></i>
                                                    <?php echo htmlspecialchars($act['ip_address']); ?> ·
                                                <?php endif; ?>
                                                <?php echo cv_fmt_date($act['creat_activity']); ?>
                                            </div>
                                        </div>
                                        <span style="font-size:.72rem;opacity:.4;white-space:nowrap">
                                            <?php echo date('d/m H:i', strtotime($act['creat_activity'])); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                    </div><!-- /col-lg-8 -->

                    <!-- ═══ Coluna Lateral (4/12) ═══ -->
                    <div class="col-lg-4">

                        <!-- Proprietário da conta -->
                        <div class="cv-card">
                            <div class="cv-card-title"><i class="bi bi-person-circle"></i> Proprietário da Conta</div>
                            <?php if ($collab['owner_id']): ?>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $collab['owner_id']; ?>"
                                    class="cv-owner-card">
                                    <?php if (!empty($collab['owner_photo'])): ?>
                                        <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($collab['owner_photo']); ?>"
                                            class="cv-owner-avatar" alt=""
                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                                        <div class="cv-owner-ini" style="background:<?php echo $owner_color; ?>;display:none">
                                            <?php echo $owner_ini; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="cv-owner-ini" style="background:<?php echo $owner_color; ?>">
                                            <?php echo $owner_ini; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1 min-w-0">
                                        <div style="font-weight:700;font-size:.88rem">
                                            <?php echo htmlspecialchars($owner_name ?: '—'); ?>
                                        </div>
                                        <div
                                            style="font-size:.74rem;opacity:.5;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                            <?php echo htmlspecialchars($collab['owner_email'] ?? ''); ?>
                                        </div>
                                        <?php if (!empty($collab['owner_plan'])): ?>
                                            <span class="badge"
                                                style="background:#FF008915;color:#FF0089;font-size:.62rem;margin-top:3px">
                                                <?php echo htmlspecialchars($collab['owner_plan']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <i class="bi bi-arrow-right" style="opacity:.3;font-size:.9rem"></i>
                                </a>
                            <?php else: ?>
                                <div class="text-center py-3" style="opacity:.4;font-size:.83rem">
                                    <i class="bi bi-person-x fs-4 d-block mb-1"></i>
                                    Proprietário não encontrado
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Estado do Convite -->
                        <div class="cv-card">
                            <div class="cv-card-title"><i class="bi bi-envelope-paper"></i> Convite</div>
                            <div class="cv-sec-item">
                                <span class="cv-sec-label">Estado do token</span>
                                <?php if ($collab['invite_token_used']): ?>
                                    <span class="badge bg-success"><i class="bi bi-check"></i> Utilizado</span>
                                <?php else: ?>
                                    <span class="badge" style="background:rgba(234,179,8,.15);color:#92400e">
                                        <i class="bi bi-hourglass-split"></i> Pendente
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="cv-sec-item">
                                <span class="cv-sec-label">Alterar senha no 1.º acesso</span>
                                <?php if ($collab['must_change_password']): ?>
                                    <span class="badge" style="background:rgba(234,179,8,.15);color:#92400e">Sim</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Não</span>
                                <?php endif; ?>
                            </div>
                            <div class="cv-sec-item">
                                <span class="cv-sec-label">Convite criado</span>
                                <span
                                    style="font-size:.78rem"><?php echo cv_fmt_date($collab['creat_collab'], false); ?></span>
                            </div>
                            <?php if (!empty($collab['invite_token_expires'])): ?>
                                <div class="cv-sec-item">
                                    <span class="cv-sec-label">Expirava em</span>
                                    <span
                                        style="font-size:.78rem"><?php echo cv_fmt_date($collab['invite_token_expires'], false); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($collab['first_login_at'])): ?>
                                <div class="cv-sec-item">
                                    <span class="cv-sec-label">Primeiro acesso</span>
                                    <span
                                        style="font-size:.78rem"><?php echo cv_fmt_date($collab['first_login_at'], false); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Sessões Activas -->
                        <div class="cv-card">
                            <div class="cv-card-title">
                                <i class="bi bi-laptop"></i> Sessões Activas
                                <span class="ms-auto badge bg-secondary" style="font-size:.65rem">
                                    <?php echo count($session_list); ?>
                                </span>
                            </div>
                            <?php if (empty($session_list)): ?>
                                <div class="text-center py-3" style="opacity:.35;font-size:.8rem">
                                    <i class="bi bi-moon-stars"
                                        style="font-size:1.6rem;display:block;margin-bottom:6px"></i>
                                    Nenhuma sessão activa
                                </div>
                            <?php else: ?>
                                <?php foreach ($session_list as $sess): ?>
                                    <div class="cv-session-item">
                                        <div style="width:30px;height:30px;border-radius:8px;background:rgba(255,0,137,.1);
                                                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                            <i class="bi bi-laptop" style="color:#FF0089;font-size:.85rem"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div style="font-size:.78rem;font-weight:600">
                                                <?php echo htmlspecialchars(cv_browser($sess['user_agent'] ?? '')); ?>
                                            </div>
                                            <div style="font-size:.72rem;opacity:.5">
                                                <?php echo htmlspecialchars($sess['ip_address'] ?? '—'); ?>
                                                · <?php echo cv_fmt_date($sess['last_activity']); ?>
                                            </div>
                                        </div>
                                        <span style="font-size:.65rem;padding:3px 8px;border-radius:999px;
                                                 background:rgba(34,197,94,.15);color:#22c55e;white-space:nowrap">
                                            Online
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Acções -->
                        <?php if (hasPermission($admin_id, 'users.edit')): ?>
                            <div class="cv-card">
                                <div class="cv-card-title"><i class="bi bi-lightning"></i> Acções Rápidas</div>
                                <div class="d-grid gap-2">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/edit?id=<?php echo $id; ?>"
                                        class="cv-action-btn text-white justify-content-center"
                                        style="background:#FF0089;border-color:#FF0089">
                                        <i class="bi bi-pencil"></i> Editar dados
                                    </a>
                                    <?php if ($collab['status_collab'] === 'active'): ?>
                                        <button onclick="toggleStatus(<?php echo $id; ?>,'blocked')"
                                            class="cv-action-btn justify-content-center"
                                            style="border-color:rgba(239,68,68,.4);color:#ef4444">
                                            <i class="bi bi-lock"></i> Bloquear acesso
                                        </button>
                                    <?php elseif ($collab['status_collab'] === 'blocked'): ?>
                                        <button onclick="toggleStatus(<?php echo $id; ?>,'active')"
                                            class="cv-action-btn justify-content-center"
                                            style="border-color:rgba(34,197,94,.4);color:#22c55e">
                                            <i class="bi bi-unlock"></i> Desbloquear acesso
                                        </button>
                                    <?php endif; ?>
                                    <?php if (!$collab['invite_token_used'] && $collab['status_collab'] === 'pending'): ?>
                                        <button onclick="resendInvite(<?php echo $id; ?>)"
                                            class="cv-action-btn justify-content-center"
                                            style="border-color:rgba(59,130,246,.4);color:#3b82f6">
                                            <i class="bi bi-envelope-paper"></i> Reenviar convite
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="deleteCollab(<?php echo $id; ?>)"
                                        class="cv-action-btn justify-content-center"
                                        style="border-color:rgba(239,68,68,.3);color:#ef4444">
                                        <i class="bi bi-trash"></i> Excluir colaborador
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div><!-- /col-lg-4 -->

                </div><!-- /row -->
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
    <script>
        (function() {
            'use strict';

            const BASE_URL = '<?php echo APP_URL; ?>';
            const ADMIN_PATH = '<?php echo ADMIN_PATH; ?>';
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const PROCESS = BASE_URL + '/' + ADMIN_PATH + '/collab/process';

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
                        window.location.href = BASE_URL + '/' + ADMIN_PATH + '/collab';
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