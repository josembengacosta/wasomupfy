<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Perfil do Admin
// Arquivo: admin/pages/user/profile.php
// .htaccess: ^admin/profile/?$ → este ficheiro
// ══════════════════════════════════════════════

require_once __DIR__ . '/../../auth/include/functions_admin.php';
require_once __DIR__ . '/../../include/platform_admin.php';
startAdminSession();
checkAdminRememberMe();
requireAdminLogin();
requireNoLockscreen();

$db = getDB();

$admin_id       = (int)$_SESSION['admin_id'];
$admin_name     = $_SESSION['admin_name']      ?? '';
$admin_fullname = $_SESSION['admin_full_name'] ?? $admin_name;
$admin_role     = $_SESSION['admin_role']      ?? '';
$admin_photo    = $_SESSION['admin_photo']     ?? null;
$admin_email    = $_SESSION['admin_email']     ?? '';

// ── Dados completos do admin ──
$admin = $db->prepare("
    SELECT e.*,
           s.recovery_key, s.login_attempts, s.block_level,
           s.last_login_at, s.last_login_ip, s.lockscreen,
           s.remember_token, s.reset_password_token
    FROM _employees e
    LEFT JOIN _employees_security s ON s.id_employees = e.id_employees
    WHERE e.id_employees = ?
    LIMIT 1
");
$admin->execute([$admin_id]);
$admin = $admin->fetch();

if (!$admin) {
    adminRedirect('/' . ADMIN_PATH . '/login');
}

// ── Permissões do admin ──
$perms = getAdminPermissions($admin_id);

// ── Atividade recente (últimas 10 acções) ──
$activity = $db->prepare("
    SELECT action, entity, entity_id, creat_log, ip_address
    FROM _audit_log
    WHERE id_employees = ?
    ORDER BY creat_log DESC
    LIMIT 10
");
$activity->execute([$admin_id]);
$activity_list = $activity->fetchAll();

// ── Feedback de operações ──
$msg  = $_GET['msg']  ?? null;
$type = $_GET['type'] ?? null;

$feedback = match ($msg) {
    'profile_ok'  => ['success', 'bi-check-circle', 'Perfil actualizado com sucesso.'],
    'password_ok' => ['success', 'bi-check-circle', 'Senha alterada com sucesso.'],
    'password_err' => ['danger',  'bi-x-circle',     'Senha actual incorrecta.'],
    'password_w'  => ['warning', 'bi-exclamation-triangle', 'As senhas não coincidem ou não cumprem os requisitos.'],
    'access_code_ok' => ['success', 'bi-shield-lock', 'Código de acesso regenerado com sucesso.'],
    'photo_ok'    => ['success', 'bi-check-circle', 'Foto de perfil actualizada.'],
    'error'       => ['danger',  'bi-exclamation-octagon', 'Ocorreu um erro. Tenta novamente.'],
    default       => null,
};

// ── Helpers ──
function adm_initials_p(string $f, string $s = ''): string
{
    return mb_strtoupper(mb_substr(trim($f), 0, 1, 'UTF-8'), 'UTF-8')
        . mb_strtoupper(mb_substr(trim($s), 0, 1, 'UTF-8'), 'UTF-8');
}

function adm_fmt_dt(string $dt): string
{
    $ts = strtotime($dt);
    if (!$ts) return '—';
    $d = time() - $ts;
    if ($d < 60)    return 'agora';
    if ($d < 3600)  return floor($d / 60) . 'min atrás';
    if ($d < 86400) return floor($d / 3600) . 'h atrás';
    $months = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    return date('d', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts) . ', ' . date('H:i', $ts);
}

function action_icon(string $a): string
{
    $map = [
        'auth.login'               => ['bi-box-arrow-in-right', 'text-success'],
        'auth.logout'              => ['bi-box-arrow-right',   'text-secondary'],
        'auth.failed_login'        => ['bi-x-circle',          'text-danger'],
        'auth.password_changed'    => ['bi-shield-lock',       'text-warning'],
        'auth.reset_requested'     => ['bi-key',               'text-info'],
        'auth.lockscreen_unlocked' => ['bi-unlock',            'text-success'],
        'auth.auto_login'          => ['bi-lightning-charge',  'text-info'],
    ];
    $r = $map[$a] ?? ['bi-activity', 'text-muted'];
    return "<i class=\"bi {$r[0]} {$r[1]}\"></i>";
}

function action_label(string $a): string
{
    $map = [
        'auth.login'               => 'Início de sessão',
        'auth.logout'              => 'Fim de sessão',
        'auth.failed_login'        => 'Tentativa de login falhada',
        'auth.password_changed'    => 'Senha alterada',
        'auth.reset_requested'     => 'Reset de senha solicitado',
        'auth.lockscreen_unlocked' => 'Ecrã desbloqueado',
        'auth.auto_login'          => 'Login automático (cookie)',
    ];
    return $map[$a] ?? str_replace(['.', '_'], [' → ', ' '], $a);
}

// ── Info da sessão para modal logout ──
$session_start = $_SESSION['_start_time'] ?? time();
if (!isset($_SESSION['_start_time'])) $_SESSION['_start_time'] = time();
$session_mins  = max(0, (int)floor((time() - $session_start) / 60));
$client_ip     = $_SERVER['REMOTE_ADDR'] ?? '—';
$ua            = $_SERVER['HTTP_USER_AGENT'] ?? '';
$browser = 'Desconhecido';
if (str_contains($ua, 'Edg'))        $browser = 'Microsoft Edge';
elseif (str_contains($ua, 'Chrome')) $browser = 'Google Chrome';
elseif (str_contains($ua, 'Firefox')) $browser = 'Firefox';
elseif (str_contains($ua, 'Safari')) $browser = 'Safari';
$os = 'Desconhecido';
if (str_contains($ua, 'Windows NT 10')) $os = 'Windows 10/11';
elseif (str_contains($ua, 'Windows'))   $os = 'Windows';
elseif (str_contains($ua, 'Mac OS'))    $os = 'macOS';
elseif (str_contains($ua, 'Android'))   $os = 'Android';
elseif (str_contains($ua, 'iPhone'))    $os = 'iOS';
elseif (str_contains($ua, 'Linux'))     $os = 'Linux';

// ── Pending notifications ──
$pending_releases = (int)$db->query("SELECT COUNT(*) FROM _album WHERE status_album IN ('pending','under_review')")->fetchColumn();
$pending_payments = (int)$db->query("SELECT COUNT(*) FROM _payment WHERE status_payment='pending'")->fetchColumn();
$open_tickets     = (int)$db->query("SELECT COUNT(*) FROM _support_ticket WHERE status_ticket NOT IN ('closed','resolved')")->fetchColumn();
$total_notifs     = $pending_releases + $pending_payments + $open_tickets;

// ── Role label ──
function role_badge(string $r): string
{
    return match ($r) {
        'super_admin' => '<span class="badge bg-danger">Super Admin</span>',
        'admin'       => '<span class="badge bg-primary">Administrador</span>',
        'editor'      => '<span class="badge bg-info">Editor</span>',
        'support'     => '<span class="badge bg-secondary">Suporte</span>',
        default       => '<span class="badge bg-dark">' . ucfirst($r) . '</span>',
    };
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
    <title>Perfil — <?php echo htmlspecialchars($admin_fullname); ?> — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/scrollue.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />

    <style>
    /* ── Profile Header ── */
    .profile-header {
        background: linear-gradient(135deg, #FF0089 0%, #6c63ff 100%);
        color: white;
        padding: 2rem 0 0;
        margin-bottom: 0;
        border-radius: 0;
        position: relative;
        overflow: hidden;
    }

    .profile-header::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255, 255, 255, .05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, .05) 1px, transparent 1px);
        background-size: 32px 32px;
        pointer-events: none;
    }

    .profile-avatar-wrap {
        position: relative;
        display: inline-block;
    }

    .profile-avatar {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, .3);
        box-shadow: 0 4px 20px rgba(0, 0, 0, .3);
    }

    .profile-avatar-initials {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .2);
        border: 4px solid rgba(255, 255, 255, .3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 800;
        color: #fff;
    }

    .avatar-upload-btn {
        position: absolute;
        bottom: 4px;
        right: 4px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #fff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: .75rem;
        color: #FF0089;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .2);
        transition: transform .2s;
    }

    .avatar-upload-btn:hover {
        transform: scale(1.1);
    }

    /* ── Nav pills ── */
    .profile-tabs {
        border-bottom: 2px solid rgba(255, 255, 255, .2);
        margin-top: 16px;
    }

    .profile-tabs .nav-link {
        color: rgba(255, 255, 255, .7);
        border-radius: 0;
        padding: 10px 20px;
        font-size: .88rem;
        font-weight: 500;
        border: none;
        background: none;
        transition: color .2s, border-bottom .2s;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
    }

    .profile-tabs .nav-link:hover {
        color: #fff;
    }

    .profile-tabs .nav-link.active {
        color: #fff;
        border-bottom-color: #fff;
    }

    /* ── Info card lateral ── */
    .info-sidebar-card {
        background: var(--card-bg, #fff);
        border-radius: 12px;
        border: 1px solid var(--border-color, #e8e8f0);
        padding: 18px;
        margin-bottom: 16px;
    }

    .info-sidebar-card .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-color, #f0f0f8);
        font-size: .83rem;
    }

    .info-sidebar-card .info-row:last-child {
        border-bottom: none;
    }

    .info-sidebar-card .info-label {
        opacity: .6;
    }

    .info-sidebar-card .info-value {
        font-weight: 600;
        text-align: right;
        max-width: 60%;
        word-break: break-all;
    }

    /* ── Security items ── */
    .security-item {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 0;
        border-bottom: 1px solid var(--border-color, #f0f0f8);
    }

    .security-item:last-child {
        border-bottom: none;
    }

    .security-item-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .security-item-body {
        flex: 1;
    }

    .security-item-title {
        font-size: .9rem;
        font-weight: 600;
        margin-bottom: 3px;
    }

    .security-item-desc {
        font-size: .8rem;
        opacity: .6;
        line-height: 1.5;
    }

    /* ── Recovery key ── */
    .recovery-key-box {
        background: rgba(255, 0, 137, .06);
        border: 1px solid rgba(255, 0, 137, .2);
        border-radius: 10px;
        padding: 14px 18px;
        font-family: 'Courier New', monospace;
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: 2px;
        color: #FF0089;
        text-align: center;
        cursor: pointer;
        user-select: all;
        transition: background .2s;
        position: relative;
    }

    .recovery-key-box:hover {
        background: rgba(255, 0, 137, .1);
    }

    .recovery-key-box .copy-hint {
        font-size: .7rem;
        font-weight: 400;
        opacity: .6;
        display: block;
        margin-top: 4px;
        letter-spacing: 0;
        font-family: inherit;
    }

    /* ── Activity timeline ── */
    .activity-item-adm {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-color, #f0f0f8);
    }

    .activity-item-adm:last-child {
        border-bottom: none;
    }

    .activity-icon-wrap {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--bs-light, #f8f9fa);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1rem;
    }

    .activity-info-adm {
        flex: 1;
    }

    .activity-title-adm {
        font-size: .85rem;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .activity-meta-adm {
        font-size: .75rem;
        opacity: .55;
    }

    /* ── Permission badges ── */
    .perm-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 8px;
    }

    .perm-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: .8rem;
        border: 1px solid;
    }

    .perm-item.granted {
        background: rgba(34, 197, 94, .08);
        border-color: rgba(34, 197, 94, .25);
        color: #166534;
    }

    .perm-item.denied {
        background: rgba(239, 68, 68, .06);
        border-color: rgba(239, 68, 68, .2);
        color: #991b1b;
        opacity: .6;
    }

    .perm-item.default_ {
        background: rgba(59, 130, 246, .08);
        border-color: rgba(59, 130, 246, .2);
        color: #1e40af;
    }

    /* ── Logout modal ── */
    .logout-admin-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
        background: #f8f7fc;
        border-radius: 12px;
        margin-bottom: 14px;
    }

    .logout-admin-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FF0089, #ff6bb5);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: .95rem;
        color: #fff;
        flex-shrink: 0;
        overflow: hidden;
    }

    .logout-admin-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .logout-session-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 0;
        font-size: .84rem;
        color: #555;
        border-bottom: 1px solid rgba(0, 0, 0, .06);
    }

    .logout-session-row:last-child {
        border-bottom: none;
    }

    .logout-session-row i {
        color: #FF0089;
        width: 16px;
        flex-shrink: 0;
    }

    .logout-session-row strong {
        color: #222;
        margin-left: auto;
        text-align: right;
        font-size: .82rem;
    }

    .logout-modal-session {
        background: rgba(0, 0, 0, .04);
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 16px;
    }

    /* ── Password strength ── */
    .strength-bar {
        height: 4px;
        border-radius: 2px;
        background: #e8e8f0;
        overflow: hidden;
        margin: 8px 0 4px;
    }

    .strength-fill {
        height: 100%;
        border-radius: 2px;
        width: 0;
        transition: width .3s, background .3s;
    }

    .strength-label {
        font-size: .74rem;
        color: #aaa;
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php require_once __DIR__ . '/../../include/sidebar.php'; ?>
        <div class="content w-100" id="mainContent">
            <?php require_once __DIR__ . '/../../include/navbar.php'; ?>

            <!-- ══ PROFILE HEADER ══ -->
            <div class="profile-header">
                <div class="container-fluid px-4">
                    <!-- Feedback -->
                    <?php if ($feedback): ?>
                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3" role="alert">
                        <i class="bi <?php echo $feedback[1]; ?> me-2"></i><?php echo htmlspecialchars($feedback[2]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <div class="row align-items-end">
                        <div class="col-auto">
                            <div class="profile-avatar-wrap">
                                <?php if ($admin_photo): ?>
                                <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/employees/<?php echo htmlspecialchars($admin_photo); ?>"
                                    alt="<?php echo htmlspecialchars($admin_fullname); ?>" class="profile-avatar" />
                                <?php else: ?>
                                <div class="profile-avatar-initials">
                                    <?php echo adm_initials_p($admin['first_name'], $admin['second_name'] ?? ''); ?>
                                </div>
                                <?php endif; ?>
                                <button class="avatar-upload-btn" type="button"
                                    onclick="document.getElementById('photo-input').click()" title="Alterar foto">
                                    <i class="bi bi-camera-fill"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col">
                            <h2 class="mb-1"><?php echo htmlspecialchars($admin_fullname); ?></h2>
                            <p class="mb-1 opacity-75" style="font-size:.9rem">
                                <i
                                    class="bi bi-at me-1"></i><?php echo htmlspecialchars($admin['user_employees'] ?? '—'); ?>
                                &nbsp;·&nbsp;
                                <i
                                    class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($admin['email_employees']); ?>
                            </p>
                            <p class="mb-0 opacity-75" style="font-size:.85rem">
                                <i
                                    class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($admin['tel_employees'] ?? '—'); ?>
                                &nbsp;·&nbsp;
                                <?php echo role_badge($admin_role); ?>
                                &nbsp;
                                <?php if ($admin['status_employees'] === 'active'): ?>
                                <span class="badge bg-success">Activo</span>
                                <?php elseif ($admin['status_employees'] === 'suspended'): ?>
                                <span class="badge bg-warning text-dark">Suspenso</span>
                                <?php else: ?>
                                <span class="badge bg-danger"><?php echo ucfirst($admin['status_employees']); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-auto text-end pb-1">
                            <small class="opacity-60" style="font-size:.78rem">
                                <i class="bi bi-clock me-1"></i>
                                Último login:
                                <?php echo $admin['last_login_at'] ? adm_fmt_dt($admin['last_login_at']) : '—'; ?>
                            </small>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav profile-tabs mt-3" id="profileTabs">
                        <li class="nav-item"><a class="nav-link active" href="#overview" data-bs-toggle="pill"><i
                                    class="bi bi-person-lines-fill me-1"></i>Visão Geral</a></li>
                        <li class="nav-item"><a class="nav-link" href="#activity" data-bs-toggle="pill"><i
                                    class="bi bi-activity me-1"></i>Actividade</a></li>
                        <li class="nav-item"><a class="nav-link" href="#settings" data-bs-toggle="pill"><i
                                    class="bi bi-gear me-1"></i>Editar Perfil</a></li>
                        <li class="nav-item"><a class="nav-link" href="#security" data-bs-toggle="pill"><i
                                    class="bi bi-shield-lock me-1"></i>Segurança</a></li>
                        <li class="nav-item"><a class="nav-link" href="#permissions" data-bs-toggle="pill"><i
                                    class="bi bi-key me-1"></i>Permissões</a></li>
                    </ul>
                </div>
            </div>

            <!-- ══ CONTEÚDO DAS TABS ══ -->
            <div class="container-fluid px-4 py-4">
                <div class="row">

                    <!-- Coluna lateral -->
                    <div class="col-md-3 mb-4">
                        <div class="info-sidebar-card">
                            <h6 class="mb-3"
                                style="font-size:.85rem;font-weight:700;opacity:.7;text-transform:uppercase;letter-spacing:.5px">
                                Informações</h6>
                            <div class="info-row">
                                <span class="info-label">ID</span>
                                <span class="info-value">#<?php echo $admin_id; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Membro desde</span>
                                <span
                                    class="info-value"><?php echo date('d/m/Y', strtotime($admin['creat_employees'])); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Último login</span>
                                <span
                                    class="info-value"><?php echo $admin['last_login_at'] ? adm_fmt_dt($admin['last_login_at']) : '—'; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">IP do login</span>
                                <span class="info-value"
                                    style="font-family:monospace;font-size:.78rem"><?php echo htmlspecialchars($admin['last_login_ip'] ?? '—'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Tentativas login</span>
                                <span class="info-value">
                                    <?php
                                    $att = (int)($admin['login_attempts'] ?? 0);
                                    $cls = $att === 0 ? 'success' : ($att < 3 ? 'warning' : 'danger');
                                    echo "<span class='badge bg-{$cls}'>{$att}</span>";
                                    ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Lockscreen</span>
                                <span class="info-value">
                                    <?php echo $admin['lockscreen'] ? '<span class="badge bg-warning text-dark">Activo</span>' : '<span class="badge bg-success">Inactivo</span>'; ?>
                                </span>
                            </div>
                        </div>

                        <!-- Links rápidos -->
                        <div class="info-sidebar-card">
                            <h6 class="mb-3"
                                style="font-size:.85rem;font-weight:700;opacity:.7;text-transform:uppercase;letter-spacing:.5px">
                                Acções rápidas</h6>
                            <a href="#security"
                                class="tab-link d-flex align-items-center gap-2 text-decoration-none py-2"
                                data-tab="security" style="font-size:.84rem;cursor:pointer">
                                <i class="bi bi-key-fill" style="color:#FF0089"></i> Alterar senha
                            </a>
                            <a href="#security"
                                class="tab-link d-flex align-items-center gap-2 text-decoration-none py-2"
                                data-tab="security" style="font-size:.84rem;cursor:pointer">
                                <i class="bi bi-shield-lock-fill" style="color:#FF0089"></i> Chave de recuperação
                            </a>
                            <a href="#settings"
                                class="tab-link d-flex align-items-center gap-2 text-decoration-none py-2"
                                data-tab="settings" style="font-size:.84rem;cursor:pointer">
                                <i class="bi bi-camera-fill" style="color:#FF0089"></i> Alterar foto
                            </a>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/audit"
                                class="d-flex align-items-center gap-2 text-decoration-none py-2"
                                style="font-size:.84rem">
                                <i class="bi bi-journal-text" style="color:#FF0089"></i> Log de auditoria
                            </a>
                        </div>
                    </div>

                    <!-- Conteúdo das tabs -->
                    <div class="col-md-9">
                        <div class="tab-content">

                            <!-- ══ ABA — Visão Geral ══ -->
                            <div class="tab-pane fade show active" id="overview">
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4"><i class="bi bi-person-lines-fill me-2"
                                                style="color:#FF0089"></i>Informações Pessoais</h5>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Nome completo:</strong>
                                                    <?php echo htmlspecialchars($admin_fullname); ?></p>
                                                <p><strong>Username:</strong>
                                                    <?php echo htmlspecialchars($admin['user_employees'] ?? '—'); ?></p>
                                                <p><strong>Género:</strong>
                                                    <?php echo $admin['gender'] === 'M' ? 'Masculino' : ($admin['gender'] === 'F' ? 'Feminino' : '—'); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>E-mail:</strong>
                                                    <?php echo htmlspecialchars($admin['email_employees']); ?></p>
                                                <p><strong>E-mail alternativo:</strong>
                                                    <?php echo htmlspecialchars($admin['email_employees_other'] ?? '—'); ?>
                                                </p>
                                                <p><strong>Telefone:</strong>
                                                    <?php echo htmlspecialchars($admin['tel_employees'] ?? '—'); ?></p>
                                                <p><strong>País:</strong>
                                                    <?php echo htmlspecialchars($admin['country_employees'] ?? '—'); ?>
                                                </p>
                                                <p><strong>Cidade:</strong>
                                                    <?php echo htmlspecialchars($admin['city_employees'] ?? '—'); ?></p>
                                            </div>
                                        </div>
                                        <?php if (!empty($admin['about_employees'])): ?>
                                        <div class="mt-2">
                                            <p class="mb-1"><strong>Sobre mim:</strong></p>
                                            <p class="text-muted">
                                                <?php echo nl2br(htmlspecialchars($admin['about_employees'])); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4"><i class="bi bi-briefcase me-2"
                                                style="color:#FF0089"></i>Informações Profissionais</h5>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Cargo / Role:</strong>
                                                    <?php echo getRoleLabel($admin_role); ?></p>
                                                <p><strong>Estado:</strong>
                                                    <?php if ($admin['status_employees'] === 'active'): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                    <?php else: ?>
                                                    <span
                                                        class="badge bg-danger"><?php echo ucfirst($admin['status_employees']); ?></span>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Membro desde:</strong>
                                                    <?php echo date('d/m/Y', strtotime($admin['creat_employees'])); ?>
                                                </p>
                                                <p><strong>Última actualização:</strong>
                                                    <?php echo date('d/m/Y H:i', strtotime($admin['modif_employees'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ══ ABA — Actividade ══ -->
                            <div class="tab-pane fade" id="activity">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h5 class="card-title mb-0"><i class="bi bi-activity me-2"
                                                    style="color:#FF0089"></i>Histórico de Actividades</h5>
                                            <?php if (hasPermission($admin_id, 'audit.view')): ?>
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/audit"
                                                class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-journal-text me-1"></i>Log completo
                                            </a>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (empty($activity_list)): ?>
                                        <div class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox"
                                                style="font-size:2rem;opacity:.3;display:block;margin-bottom:8px"></i>
                                            Nenhuma actividade registada ainda.
                                        </div>
                                        <?php else: ?>
                                        <?php foreach ($activity_list as $act): ?>
                                        <div class="activity-item-adm">
                                            <div class="activity-icon-wrap">
                                                <?php echo action_icon($act['action']); ?>
                                            </div>
                                            <div class="activity-info-adm">
                                                <div class="activity-title-adm">
                                                    <?php echo htmlspecialchars(action_label($act['action'])); ?></div>
                                                <div class="activity-meta-adm">
                                                    <?php if ($act['entity']): ?><?php echo htmlspecialchars($act['entity']); ?>
                                                    · <?php endif; ?>
                                                    <?php if ($act['ip_address']): ?><code
                                                        style="font-size:.72rem"><?php echo htmlspecialchars($act['ip_address']); ?></code>
                                                    · <?php endif; ?>
                                                    <?php echo adm_fmt_dt($act['creat_log']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- ══ ABA — Editar Perfil ══ -->
                            <div class="tab-pane fade" id="settings">
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4"><i class="bi bi-person-gear me-2"
                                                style="color:#FF0089"></i>Editar Perfil</h5>
                                        <form method="POST"
                                            action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/profile-process"
                                            id="form-profile" novalidate onsubmit="return false">
                                            <input type="hidden" name="csrf_token"
                                                value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                            <input type="hidden" name="action" value="update_profile" />

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Primeiro Nome <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="first_name" required
                                                        value="<?php echo htmlspecialchars($admin['first_name']); ?>"
                                                        maxlength="50" />
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Apelido</label>
                                                    <input type="text" class="form-control" name="second_name"
                                                        value="<?php echo htmlspecialchars($admin['second_name'] ?? ''); ?>"
                                                        maxlength="80" />
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">E-mail <span
                                                            class="text-danger">*</span></label>
                                                    <input type="email" class="form-control" name="email_employees"
                                                        required
                                                        value="<?php echo htmlspecialchars($admin['email_employees']); ?>"
                                                        maxlength="255" />
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">E-mail alternativo</label>
                                                    <input type="email" class="form-control"
                                                        name="email_employees_other"
                                                        value="<?php echo htmlspecialchars($admin['email_employees_other'] ?? ''); ?>"
                                                        maxlength="255" />
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Telefone</label>
                                                    <input type="tel" class="form-control" name="tel_employees"
                                                        value="<?php echo htmlspecialchars($admin['tel_employees'] ?? ''); ?>"
                                                        maxlength="20" />
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Género</label>
                                                    <select class="form-select" name="gender">
                                                        <option value="M"
                                                            <?php echo $admin['gender'] === 'M' ? 'selected' : ''; ?>>
                                                            Masculino</option>
                                                        <option value="F"
                                                            <?php echo $admin['gender'] === 'F' ? 'selected' : ''; ?>>
                                                            Feminino</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">País</label>
                                                    <select class="form-select" name="country_employees">
                                                        <option value="">Selecione um país</option>
                                                        <?php
                                                        $countries = [
                                                            'AO' => 'Angola',
                                                            'PT' => 'Portugal',
                                                            'BR' => 'Brasil',
                                                            'MZ' => 'Moçambique',
                                                            'CV' => 'Cabo Verde',
                                                            'GW' => 'Guiné-Bissau',
                                                            'GN' => 'Guiné',
                                                            'ST' => 'São Tomé e Príncipe',
                                                            'AF' => 'Afeganistão',
                                                            'AR' => 'Argentina',
                                                            'AU' => 'Austrália',
                                                            'AT' => 'Áustria',
                                                            'BE' => 'Bélgica',
                                                            'BO' => 'Bolívia',
                                                            'BA' => 'Bósnia',
                                                            'BW' => 'Botsuana',
                                                            'CM' => 'Camarões',
                                                            'CA' => 'Canadá',
                                                            'CL' => 'Chile',
                                                            'CO' => 'Colômbia',
                                                            'CG' => 'Congo-Brazzaville',
                                                            'CD' => 'Congo-Kinshasa',
                                                            'HR' => 'Croácia',
                                                            'DK' => 'Dinamarca',
                                                            'EG' => 'Egipto',
                                                            'AE' => 'Emirados Árabes',
                                                            'ES' => 'Espanha',
                                                            'US' => 'Estados Unidos',
                                                            'ET' => 'Etiópia',
                                                            'FR' => 'França',
                                                            'GH' => 'Gana',
                                                            'DE' => 'Alemanha',
                                                            'IN' => 'Índia',
                                                            'ID' => 'Indonésia',
                                                            'IE' => 'Irlanda',
                                                            'IT' => 'Itália',
                                                            'JP' => 'Japão',
                                                            'KE' => 'Quénia',
                                                            'MA' => 'Marrocos',
                                                            'MX' => 'México',
                                                            'NG' => 'Nigéria',
                                                            'NO' => 'Noruega',
                                                            'NZ' => 'Nova Zelândia',
                                                            'PK' => 'Paquistão',
                                                            'PE' => 'Peru',
                                                            'PL' => 'Polónia',
                                                            'RO' => 'Roménia',
                                                            'RW' => 'Ruanda',
                                                            'SN' => 'Senegal',
                                                            'ZA' => 'África do Sul',
                                                            'SE' => 'Suécia',
                                                            'CH' => 'Suíça',
                                                            'TZ' => 'Tanzânia',
                                                            'TN' => 'Tunísia',
                                                            'TR' => 'Turquia',
                                                            'UG' => 'Uganda',
                                                            'UY' => 'Uruguai',
                                                            'VE' => 'Venezuela',
                                                            'ZM' => 'Zâmbia',
                                                            'ZW' => 'Zimbábue',
                                                        ];
                                                        asort($countries);
                                                        foreach ($countries as $code => $name):
                                                            $sel = (($admin['country_employees'] ?? '') === $code) ? 'selected' : '';
                                                        ?>
                                                        <option value="<?php echo $code; ?>" <?php echo $sel; ?>>
                                                            <?php echo htmlspecialchars($name); ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Cidade</label>
                                                    <input type="text" class="form-control" name="city_employees"
                                                        value="<?php echo htmlspecialchars($admin['city_employees'] ?? ''); ?>"
                                                        maxlength="80" placeholder="Ex: Luanda" />
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">URL / Website</label>
                                                <input type="url" class="form-control" name="url_employees"
                                                    value="<?php echo htmlspecialchars($admin['url_employees'] ?? ''); ?>"
                                                    maxlength="255" placeholder="https://..." />
                                            </div>

                                            <div class="mb-4">
                                                <label class="form-label">Sobre mim</label>
                                                <textarea class="form-control" name="about_employees" rows="3"
                                                    maxlength="1000"
                                                    placeholder="Uma breve descrição sobre ti..."><?php echo htmlspecialchars($admin['about_employees'] ?? ''); ?></textarea>
                                            </div>

                                            <button type="button" class="btn btn-primary" id="btn-save-profile">
                                                <span class="spinner-border spinner-border-sm d-none me-1"
                                                    id="spin-profile"></span>
                                                <i class="bi bi-check-circle me-1"></i>Guardar Alterações
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Foto de perfil -->
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4"><i class="bi bi-camera me-2"
                                                style="color:#FF0089"></i>Foto de Perfil</h5>
                                        <form method="POST"
                                            action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/profile-process"
                                            enctype="multipart/form-data" id="form-photo">
                                            <input type="hidden" name="csrf_token"
                                                value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                            <input type="hidden" name="action" value="update_photo" />
                                            <!-- Input oculto — activado pelo botão e pela drop zone -->
                                            <input type="file" name="photo" id="photo-input"
                                                accept="image/jpeg,image/png,image/webp" style="display:none" />

                                            <div class="row align-items-center g-4">
                                                <!-- Preview actual -->
                                                <div class="col-auto">
                                                    <div style="position:relative;display:inline-block">
                                                        <div id="photo-preview-wrap" style="width:96px;height:96px;border-radius:50%;overflow:hidden;
                                      border:3px solid rgba(255,0,137,.3);
                                      box-shadow:0 0 0 4px rgba(255,0,137,.08)">
                                                            <?php if ($admin_photo): ?>
                                                            <img id="photo-preview"
                                                                src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/employees/<?php echo htmlspecialchars($admin_photo); ?>"
                                                                alt=""
                                                                style="width:100%;height:100%;object-fit:cover" />
                                                            <?php else: ?>
                                                            <div id="photo-preview-initials" style="width:100%;height:100%;background:#FF0089;display:flex;
                                          align-items:center;justify-content:center;
                                          font-weight:800;font-size:1.5rem;color:#fff">
                                                                <?php echo adm_initials_p($admin['first_name'], $admin['second_name'] ?? ''); ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <!-- Badge de câmara sobre a foto -->
                                                        <button type="button"
                                                            onclick="document.getElementById('photo-input').click()"
                                                            style="position:absolute;bottom:2px;right:2px;width:28px;height:28px;
                                         border-radius:50%;background:#FF0089;border:2px solid #fff;
                                         display:flex;align-items:center;justify-content:center;
                                         cursor:pointer;font-size:.75rem;color:#fff;box-shadow:0 2px 6px rgba(0,0,0,.25)"
                                                            title="Seleccionar foto">
                                                            <i class="bi bi-camera-fill"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Zona de upload / drop -->
                                                <div class="col">
                                                    <div id="photo-drop-zone"
                                                        onclick="document.getElementById('photo-input').click()" style="border:2px dashed rgba(255,0,137,.3);border-radius:12px;
                                    padding:24px 20px;text-align:center;cursor:pointer;
                                    transition:background .2s,border-color .2s;
                                    background:rgba(255,0,137,.03)">
                                                        <i class="bi bi-cloud-arrow-up"
                                                            style="font-size:2rem;color:#FF0089;opacity:.6;display:block;margin-bottom:8px"></i>
                                                        <div style="font-size:.88rem;font-weight:600;margin-bottom:4px">
                                                            Clica ou arrasta a foto aqui
                                                        </div>
                                                        <div style="font-size:.76rem;opacity:.55">
                                                            JPG, PNG ou WebP · Máximo 2MB
                                                        </div>
                                                        <div id="photo-filename"
                                                            style="font-size:.78rem;color:#FF0089;margin-top:8px;display:none;font-weight:600">
                                                        </div>
                                                    </div>

                                                    <div class="d-flex gap-2 mt-3 flex-wrap">
                                                        <button type="submit" id="btn-upload-photo"
                                                            class="btn btn-primary btn-sm" disabled>
                                                            <span class="spinner-border spinner-border-sm d-none me-1"
                                                                id="spin-photo"></span>
                                                            <i class="bi bi-upload me-1"></i>Guardar foto
                                                        </button>
                                                        <?php if ($admin_photo): ?>
                                                        <form method="POST"
                                                            action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/profile-process"
                                                            style="display:inline">
                                                            <input type="hidden" name="csrf_token"
                                                                value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                                            <input type="hidden" name="action" value="remove_photo" />
                                                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                                                onclick="return confirm('Remover a foto de perfil?')">
                                                                <i class="bi bi-trash me-1"></i>Remover foto
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- ══ ABA — Segurança ══ -->
                            <div class="tab-pane fade" id="security">

                                <!-- Alterar senha -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4"><i class="bi bi-lock me-2"
                                                style="color:#FF0089"></i>Alterar Senha</h5>
                                        <form method="POST"
                                            action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/profile-process"
                                            id="form-password" novalidate onsubmit="return false">
                                            <input type="hidden" name="csrf_token"
                                                value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                            <input type="hidden" name="action" value="change_password" />

                                            <div class="mb-3">
                                                <label class="form-label">Senha Actual <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" name="current_password"
                                                        id="current_password" required maxlength="128"
                                                        placeholder="A tua senha actual"
                                                        autocomplete="current-password" />
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        onclick="togglePw('current_password','eye-cur')">
                                                        <i class="bi bi-eye" id="eye-cur"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Nova Senha <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" name="new_password"
                                                        id="new_password" required maxlength="128"
                                                        placeholder="Mínimo 8 caracteres" autocomplete="new-password" />
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        onclick="togglePw('new_password','eye-new')">
                                                        <i class="bi bi-eye" id="eye-new"></i>
                                                    </button>
                                                </div>
                                                <div class="strength-bar">
                                                    <div class="strength-fill" id="pw-strength-fill"></div>
                                                </div>
                                                <div class="strength-label" id="pw-strength-label">Escreve a nova senha
                                                </div>
                                            </div>

                                            <div class="mb-4">
                                                <label class="form-label">Confirmar Nova Senha <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" name="confirm_password"
                                                        id="confirm_password" required maxlength="128"
                                                        placeholder="Repete a nova senha" autocomplete="new-password" />
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        onclick="togglePw('confirm_password','eye-conf')">
                                                        <i class="bi bi-eye" id="eye-conf"></i>
                                                    </button>
                                                </div>
                                                <div id="pw-match-err"
                                                    style="font-size:.78rem;color:#ef4444;margin-top:4px;display:none">
                                                    As senhas não coincidem.</div>
                                            </div>

                                            <!-- Requisitos -->
                                            <div class="mb-4 p-3 rounded"
                                                style="background:rgba(255,0,137,.05);border:1px solid rgba(255,0,137,.15)">
                                                <p
                                                    style="font-size:.78rem;font-weight:600;margin-bottom:8px;opacity:.7">
                                                    Requisitos da senha:</p>
                                                <div class="row g-1" style="font-size:.78rem">
                                                    <div class="col-6"><span id="req-len" class="text-muted"><i
                                                                class="bi bi-circle me-1"></i>Mínimo 8 caracteres</span>
                                                    </div>
                                                    <div class="col-6"><span id="req-up" class="text-muted"><i
                                                                class="bi bi-circle me-1"></i>Uma maiúscula</span></div>
                                                    <div class="col-6"><span id="req-low" class="text-muted"><i
                                                                class="bi bi-circle me-1"></i>Uma minúscula</span></div>
                                                    <div class="col-6"><span id="req-num" class="text-muted"><i
                                                                class="bi bi-circle me-1"></i>Um número</span></div>
                                                    <div class="col-6"><span id="req-sym" class="text-muted"><i
                                                                class="bi bi-circle me-1"></i>Um símbolo</span></div>
                                                    <div class="col-6"><span id="req-match" class="text-muted"><i
                                                                class="bi bi-circle me-1"></i>Senhas coincidem</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <button type="button" class="btn btn-danger" id="btn-change-pw">
                                                <span class="spinner-border spinner-border-sm d-none me-1"
                                                    id="spin-pw"></span>
                                                <i class="bi bi-lock me-1"></i>Alterar Senha
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Chave de recuperação -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="security-item">
                                            <div class="security-item-icon"
                                                style="background:rgba(234,179,8,.1);color:#eab308">
                                                <i class="bi bi-key-fill"></i>
                                            </div>
                                            <div class="security-item-body">
                                                <div class="security-item-title">Chave de Recuperação</div>
                                                <div class="security-item-desc mb-3">
                                                    Usa esta chave para recuperar o acesso à tua conta caso percas a
                                                    senha.
                                                    Guarda-a num local seguro — <strong>nunca a partilhes</strong>.
                                                </div>
                                                <div class="recovery-key-box" id="recovery-key-box"
                                                    onclick="copyRecoveryKey()">
                                                    <?php echo htmlspecialchars($admin['recovery_key']); ?>
                                                    <span class="copy-hint"><i class="bi bi-clipboard me-1"></i>Clica
                                                        para copiar</span>
                                                </div>
                                                <div id="copy-feedback"
                                                    style="font-size:.75rem;color:#22c55e;margin-top:6px;display:none">
                                                    <i class="bi bi-check-circle me-1"></i>Copiado para a área de
                                                    transferência!
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Access Code -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="security-item">
                                            <div class="security-item-icon"
                                                style="background:rgba(255,0,137,.1);color:#FF0089">
                                                <i class="bi bi-shield-lock-fill"></i>
                                            </div>
                                            <div class="security-item-body">
                                                <div class="security-item-title">Código de Acesso (Manager / Lockscreen)
                                                </div>
                                                <div class="security-item-desc mb-3">
                                                    Este código de 6 dígitos é necessário para aceder ao <strong>Painel
                                                        de Pagamentos (Treasury Desk)</strong>
                                                    e também pode ser usado para desbloquear o ecrã caso o Lockscreen
                                                    esteja activo.
                                                    <strong>Guarda-o num local seguro</strong> – não o partilhes.
                                                </div>
                                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                                    <div class="recovery-key-box" id="access-code-box"
                                                        style="font-family:monospace;letter-spacing:2px">
                                                        <?php $stmt = $db->prepare("SELECT access_code FROM _employees_security WHERE id_employees = ?");
                                                        $stmt->execute([$admin_id]);
                                                        $access_code = $stmt->fetchColumn();
                                                        echo htmlspecialchars($access_code ?: '———');
                                                        ?>
                                                        <span class="copy-hint"><i
                                                                class="bi bi-clipboard me-1"></i>Clicar para
                                                            copiar</span>
                                                    </div>
                                                    <form method="POST"
                                                        action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/profile-process"
                                                        id="form-regen-access" style="display:inline">
                                                        <input type="hidden" name="csrf_token"
                                                            value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
                                                        <input type="hidden" name="action"
                                                            value="regenerate_access_code">
                                                        <input type="hidden" name="current_password"
                                                            id="regen_password_hidden">
                                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                            id="regenerateAccessCodeBtn">
                                                            <i class="bi bi-arrow-repeat me-1"></i>Regenerar código
                                                        </button>
                                                    </form>
                                                </div>
                                                <div id="access-copy-feedback"
                                                    style="font-size:.75rem;color:#22c55e;margin-top:6px;display:none">
                                                    <i class="bi bi-check-circle me-1"></i>Código copiado!
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Lockscreen -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="security-item">
                                            <div class="security-item-icon"
                                                style="background:rgba(99,102,241,.1);color:#6366f1">
                                                <i class="bi bi-display"></i>
                                            </div>
                                            <div class="security-item-body">
                                                <div class="security-item-title">Ecrã de Bloqueio (Lockscreen)</div>
                                                <div class="security-item-desc">
                                                    Quando o lockscreen está activo, é necessário introduzir um código
                                                    de 6 dígitos
                                                    para aceder ao painel após a sessão ficar inactiva.
                                                    Estado actual:
                                                    <?php echo $admin['lockscreen'] ? '<span class="badge bg-warning text-dark">Activo</span>' : '<span class="badge bg-success">Inactivo</span>'; ?>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <?php if ($admin['lockscreen']): ?>
                                                <form method="POST"
                                                    action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/profile-process"
                                                    style="display:inline">
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                                    <input type="hidden" name="action" value="disable_lockscreen" />
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-unlock me-1"></i>Desactivar
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <form method="POST"
                                                    action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/profile-process"
                                                    style="display:inline">
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                                    <input type="hidden" name="action" value="enable_lockscreen" />
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-lock me-1"></i>Activar
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2FA -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="security-item">
                                            <div class="security-item-icon"
                                                style="background:rgba(255,0,137,.1);color:#FF0089">
                                                <i class="bi bi-shield-check"></i>
                                            </div>
                                            <div class="security-item-body">
                                                <div class="security-item-title">Autenticação de Dois Factores (2FA)
                                                </div>
                                                <div class="security-item-desc">
                                                    Adiciona uma camada extra de segurança ao teu login com um código
                                                    temporário gerado por uma aplicação TOTP (ex: Google Authenticator,
                                                    Authy).
                                                    <br><span class="badge bg-secondary mt-1">Em desenvolvimento</span>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <button class="btn btn-sm btn-outline-secondary" disabled
                                                    title="Em desenvolvimento">
                                                    <i class="bi bi-phone me-1"></i>Activar 2FA
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sessões activas -->
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3"><i class="bi bi-laptop me-2"
                                                style="color:#FF0089"></i>Sessão Actual</h5>
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <div style="font-size:.85rem">
                                                <div><strong><i
                                                            class="bi bi-browser-chrome me-1"></i><?php echo htmlspecialchars($browser); ?></strong>
                                                    — <?php echo htmlspecialchars($os); ?></div>
                                                <div class="text-muted" style="font-size:.78rem">
                                                    IP: <code><?php echo htmlspecialchars($client_ip); ?></code>
                                                    · Sessão iniciada há <?php echo $session_mins; ?> min
                                                    · Último login:
                                                    <?php echo $admin['last_login_at'] ? adm_fmt_dt($admin['last_login_at']) : '—'; ?>
                                                </div>
                                            </div>
                                            <div class="ms-auto">
                                                <span class="badge bg-success"><i class="bi bi-circle-fill me-1"
                                                        style="font-size:.5rem"></i>Sessão activa</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- ══ ABA — Permissões ══ -->
                            <div class="tab-pane fade" id="permissions">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h5 class="card-title mb-0"><i class="bi bi-key me-2"
                                                    style="color:#FF0089"></i>Permissões do Perfil</h5>
                                            <span class="badge"
                                                style="background:rgba(255,0,137,.1);color:#FF0089;font-size:.78rem">
                                                Role: <?php echo getRoleLabel($admin_role); ?>
                                            </span>
                                        </div>

                                        <?php if ($admin_role === 'super_admin'): ?>
                                        <div class="alert alert-success mb-4">
                                            <i class="bi bi-shield-fill-check me-2"></i>
                                            <strong>Super Administrador</strong> — Acesso total a todas as
                                            funcionalidades da plataforma, sem restrições.
                                        </div>
                                        <?php else: ?>
                                        <p style="font-size:.84rem;opacity:.7;margin-bottom:20px">
                                            As permissões assinaladas com <span class="badge"
                                                style="background:rgba(59,130,246,.1);color:#1e40af">Padrão</span>
                                            são as predefinidas para o teu role. As marcadas com
                                            <span class="badge"
                                                style="background:rgba(34,197,94,.1);color:#166534">Concedida</span>
                                            foram atribuídas explicitamente. As
                                            <span class="badge"
                                                style="background:rgba(239,68,68,.08);color:#991b1b">Negada</span>
                                            foram explicitamente revogadas.
                                        </p>

                                        <?php
                                            // Permissões padrão por role (espelha o functions_admin.php)
                                            $role_defaults = [
                                                'admin'   => ['employees.view', 'employees.edit', 'users.view', 'users.edit', 'music.view', 'music.approve', 'finances.view', 'finances.edit', 'analytics.view', 'support.view', 'support.edit', 'audit.view', 'settings.view', 'settings.edit'],
                                                'editor'  => ['music.view', 'music.approve', 'analytics.view'],
                                                'support' => ['support.view', 'analytics.view'],
                                            ];

                                            $all_permissions = [
                                                'Utilizadores'   => ['users.view', 'users.edit'],
                                                'Músicas'        => ['music.view', 'music.approve'],
                                                'Finanças'       => ['finances.view', 'finances.edit'],
                                                'Analytics'      => ['analytics.view'],
                                                'Suporte'        => ['support.view', 'support.edit'],
                                                'Funcionários'   => ['employees.view', 'employees.edit'],
                                                'Auditoria'      => ['audit.view'],
                                                'Configurações'  => ['settings.view', 'settings.edit'],
                                            ];

                                            $defaults_for_role = $role_defaults[$admin_role] ?? [];
                                            ?>

                                        <?php foreach ($all_permissions as $group => $perm_list): ?>
                                        <div class="mb-4">
                                            <h6 class="text-muted mb-2"
                                                style="font-size:.78rem;text-transform:uppercase;letter-spacing:.5px">
                                                <?php echo $group; ?></h6>
                                            <div class="perm-grid">
                                                <?php foreach ($perm_list as $perm):
                                                            $explicit = $perms[$perm] ?? null; // null = sem regra na BD
                                                            if ($explicit === true) {
                                                                $cls = 'granted';
                                                                $ico = 'bi-check-circle-fill';
                                                                $lbl = 'Concedida';
                                                            } elseif ($explicit === false) {
                                                                $cls = 'denied';
                                                                $ico = 'bi-x-circle-fill';
                                                                $lbl = 'Negada';
                                                            } elseif (in_array($perm, $defaults_for_role)) {
                                                                $cls = 'default_';
                                                                $ico = 'bi-circle-fill';
                                                                $lbl = 'Padrão';
                                                            } else {
                                                                $cls = 'denied';
                                                                $ico = 'bi-x-circle';
                                                                $lbl = 'Sem acesso';
                                                            }
                                                            $perm_name = str_replace(['.view', '.edit', '.approve'], [' — ver', ' — editar', ' — aprovar'], $perm);
                                                        ?>
                                                <div class="perm-item <?php echo $cls; ?>">
                                                    <i class="bi <?php echo $ico; ?>"></i>
                                                    <span><?php echo htmlspecialchars($perm_name); ?></span>
                                                    <small class="ms-auto opacity-75"><?php echo $lbl; ?></small>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>

                                        <?php if (hasPermission($admin_id, 'employees.edit')): ?>
                                        <div class="alert alert-info mt-3" style="font-size:.82rem">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Para alterar as permissões de um funcionário, vai a
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees"
                                                class="alert-link">Gestão
                                                de Admins</a>.
                                        </div>
                                        <?php endif; ?>
                                        <?php endif; ?>

                                    </div>
                                </div>
                            </div>

                        </div><!-- /tab-content -->
                    </div><!-- /col-md-9 -->
                </div><!-- /row -->
            </div><!-- /container-fluid -->

        </div><!-- /content -->
    </div><!-- /wrapper -->

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-2">© <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos os direitos reservados.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="<?php echo APP_URL  ?>/assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="Carregando" />
            <div class="loader-progress"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo APP_URL  ?>/js/lastest.js"></script>
    <script src="<?php echo APP_URL  ?>/js/lastest.min.js"></script>
    <script>
    // Neutralizar o código legado do lastest.js que tenta aceder a
    // modais/elementos que não existem nesta página (devicesModal, twoFAModal, etc.)
    window.__profilePage = true;
    </script>
    <script src="<?php echo APP_URL  ?>/js/lastest.js"></script>
    <script src="<?php echo APP_URL  ?>/js/lastest.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {

        // ── Toggle visibilidade de senha ──
        window.togglePw = function(inputId, iconId) {
            const inp = document.getElementById(inputId);
            const ico = document.getElementById(iconId);
            if (!inp || !ico) return;
            inp.type = inp.type === 'password' ? 'text' : 'password';
            ico.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
            inp.focus();
        };

        // ── Validação e força da senha ──
        const newPwInp = document.getElementById('new_password');
        const confPwInp = document.getElementById('confirm_password');
        const strengthFl = document.getElementById('pw-strength-fill');
        const strengthLb = document.getElementById('pw-strength-label');
        const matchErr = document.getElementById('pw-match-err');

        const reqs = {
            len: {
                el: document.getElementById('req-len'),
                fn: v => v.length >= 8
            },
            up: {
                el: document.getElementById('req-up'),
                fn: v => /[A-Z]/.test(v)
            },
            low: {
                el: document.getElementById('req-low'),
                fn: v => /[a-z]/.test(v)
            },
            num: {
                el: document.getElementById('req-num'),
                fn: v => /[0-9]/.test(v)
            },
            sym: {
                el: document.getElementById('req-sym'),
                fn: v => /[!@#$%^&*\-_+=?]/.test(v)
            },
            match: {
                el: document.getElementById('req-match'),
                fn: (v, c) => v.length > 0 && v === c
            },
        };

        const levels = [{
                w: '0%',
                c: '#e8e8f0',
                l: 'Escreve a nova senha'
            },
            {
                w: '20%',
                c: '#ef4444',
                l: 'Muito fraca'
            },
            {
                w: '40%',
                c: '#f97316',
                l: 'Fraca'
            },
            {
                w: '60%',
                c: '#eab308',
                l: 'Razoável'
            },
            {
                w: '80%',
                c: '#22c55e',
                l: 'Forte'
            },
            {
                w: '100%',
                c: '#16a34a',
                l: 'Muito forte'
            },
        ];

        function updateReqs() {
            const v = newPwInp ? newPwInp.value : '';
            const c = confPwInp ? confPwInp.value : '';
            let met = 0;

            Object.entries(reqs).forEach(([k, r]) => {
                const ok = k === 'match' ? r.fn(v, c) : r.fn(v);
                if (r.el) {
                    r.el.className = ok ? 'text-success' : 'text-muted';
                    r.el.querySelector('i').className = ok ? 'bi bi-check-circle-fill me-1' :
                        'bi bi-circle me-1';
                }
                if (ok && k !== 'match') met++;
            });

            const lvl = levels[met];
            if (strengthFl) {
                strengthFl.style.width = lvl.w;
                strengthFl.style.background = lvl.c;
            }
            if (strengthLb) {
                strengthLb.textContent = lvl.l;
                strengthLb.style.color = met === 0 ? '#aaa' : lvl.c;
            }
            if (matchErr) matchErr.style.display = (c.length > 0 && v !== c) ? 'block' : 'none';
        }

        if (newPwInp) newPwInp.addEventListener('input', updateReqs);
        if (confPwInp) confPwInp.addEventListener('input', updateReqs);

        // ── Submeter alterar senha ──
        const btnPw = document.getElementById('btn-change-pw');
        const formPw = document.getElementById('form-password');
        const spinPw = document.getElementById('spin-pw');

        if (btnPw && formPw) {
            btnPw.addEventListener('click', function() {
                const v = newPwInp ? newPwInp.value : '';
                const c = confPwInp ? confPwInp.value : '';
                const cur = document.getElementById('current_password');

                if (!cur || !cur.value) {
                    cur && cur.classList.add('is-invalid');
                    return;
                }
                if (!reqs.len.fn(v) || !reqs.up.fn(v) || !reqs.low.fn(v) || !reqs.num.fn(v) || !reqs.sym
                    .fn(v)) {
                    newPwInp && newPwInp.classList.add('is-invalid');
                    return;
                }
                if (v !== c) {
                    confPwInp && confPwInp.classList.add('is-invalid');
                    if (matchErr) {
                        matchErr.style.display = 'block';
                    }
                    return;
                }

                if (spinPw) spinPw.classList.remove('d-none');
                btnPw.disabled = true;
                formPw.submit();
            });
        }

        // ── Submeter perfil ──
        const btnPrf = document.getElementById('btn-save-profile');
        const formPrf = document.getElementById('form-profile');
        const spinPrf = document.getElementById('spin-profile');

        if (btnPrf && formPrf) {
            btnPrf.addEventListener('click', function() {
                if (spinPrf) spinPrf.classList.remove('d-none');
                btnPrf.disabled = true;
                formPrf.submit();
            });
        }

        // ── Copiar chave de recuperação ──
        window.copyRecoveryKey = function() {
            const box = document.getElementById('recovery-key-box');
            const txt = box ? box.childNodes[0].textContent.trim() : '';
            const fb = document.getElementById('copy-feedback');

            if (navigator.clipboard && txt) {
                navigator.clipboard.writeText(txt).then(() => {
                    if (fb) {
                        fb.style.display = 'block';
                        setTimeout(() => {
                            fb.style.display = 'none';
                        }, 3000);
                    }
                });
            } else {
                // Fallback
                const ta = document.createElement('textarea');
                ta.value = txt;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                if (fb) {
                    fb.style.display = 'block';
                    setTimeout(() => {
                        fb.style.display = 'none';
                    }, 3000);
                }
            }
        };

        // Copiar Access Code
        const accessBox = document.getElementById('access-code-box');
        const accessFeedback = document.getElementById('access-copy-feedback');
        if (accessBox) {
            accessBox.addEventListener('click', function() {
                let code = this.childNodes[0]?.textContent.trim();
                if (!code || code === '———') return;
                navigator.clipboard.writeText(code).then(() => {
                    if (accessFeedback) {
                        accessFeedback.style.display = 'block';
                        setTimeout(() => {
                            accessFeedback.style.display = 'none';
                        }, 3000);
                    }
                });
            });
        }

        // Regenerar Access Code (SweetAlert + formulário)
        const regenBtn = document.getElementById('regenerateAccessCodeBtn');
        if (regenBtn) {
            regenBtn.addEventListener('click', async () => {
                const {
                    value: password
                } = await Swal.fire({
                    title: 'Regenerar código de acesso',
                    text: 'Confirma a tua senha actual para gerar um novo código de 6 dígitos.',
                    input: 'password',
                    inputPlaceholder: 'Senha do administrador',
                    inputAttributes: {
                        autocomplete: 'current-password'
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Regenerar',
                    cancelButtonText: 'Cancelar'
                });
                if (password) {
                    document.getElementById('regen_password_hidden').value = password;
                    document.getElementById('form-regen-access').submit();
                }
            });
        }

        // ── Upload de foto — drop zone + preview ──
        const photoInput = document.getElementById('photo-input');
        const dropZone = document.getElementById('photo-drop-zone');
        const previewWrap = document.getElementById('photo-preview-wrap');
        const fileNameEl = document.getElementById('photo-filename');
        const btnUpload = document.getElementById('btn-upload-photo');
        const spinPhoto = document.getElementById('spin-photo');
        const formPhoto = document.getElementById('form-photo');

        function handlePhotoFile(file) {
            if (!file) return;

            // Validar tipo e tamanho no cliente
            const allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowed.includes(file.type)) {
                alert('Formato inválido. Usa JPG, PNG ou WebP.');
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                alert('Ficheiro demasiado grande. Máximo 2MB.');
                return;
            }

            // Preview
            const reader = new FileReader();
            reader.onload = function(e) {
                previewWrap.innerHTML =
                    `<img src="${e.target.result}" alt="" style="width:100%;height:100%;object-fit:cover" />`;
            };
            reader.readAsDataURL(file);

            // Mostrar nome do ficheiro
            if (fileNameEl) {
                fileNameEl.textContent = '✓ ' + file.name;
                fileNameEl.style.display = 'block';
            }

            // Activar botão
            if (btnUpload) btnUpload.disabled = false;
        }

        if (photoInput) {
            photoInput.addEventListener('change', function() {
                handlePhotoFile(this.files[0]);
            });
        }

        // Drag & Drop
        if (dropZone) {
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.background = 'rgba(255,0,137,.08)';
                this.style.borderColor = '#FF0089';
            });
            dropZone.addEventListener('dragleave', function() {
                this.style.background = 'rgba(255,0,137,.03)';
                this.style.borderColor = 'rgba(255,0,137,.3)';
            });
            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.background = 'rgba(255,0,137,.03)';
                this.style.borderColor = 'rgba(255,0,137,.3)';
                const file = e.dataTransfer.files[0];
                if (file && photoInput) {
                    // Transferir para o input real
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    photoInput.files = dt.files;
                    handlePhotoFile(file);
                }
            });
        }

        // Submit do form de foto com loading
        if (formPhoto && btnUpload) {
            btnUpload.addEventListener('click', function() {
                if (spinPhoto) spinPhoto.classList.remove('d-none');
                btnUpload.disabled = true;
                formPhoto.submit();
            });
        }

        // ── Links rápidos da coluna lateral → activar tab correcto ──
        document.querySelectorAll('.tab-link[data-tab]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const tabEl = document.querySelector('a[href="#' + this.dataset.tab + '"]');
                if (tabEl) bootstrap.Tab.getOrCreateInstance(tabEl).show();
                // Scroll suave para o conteúdo
                const pane = document.getElementById(this.dataset.tab);
                if (pane) setTimeout(() => pane.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                }), 150);
            });
        });

        // ── Restaurar tab activa via URL param ──
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab) {
            const tabEl = document.querySelector(`a[href="#${tab}"]`);
            if (tabEl) {
                bootstrap.Tab.getOrCreateInstance(tabEl).show();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Copiar Access Code
            const accessBox = document.getElementById('access-code-box');
            const accessFeedback = document.getElementById('access-copy-feedback');
            if (accessBox) {
                accessBox.addEventListener('click', function() {
                    let code = this.childNodes[0]?.textContent.trim();
                    if (!code || code === '———') return;
                    navigator.clipboard.writeText(code).then(() => {
                        if (accessFeedback) {
                            accessFeedback.style.display = 'block';
                            setTimeout(() => {
                                accessFeedback.style.display = 'none';
                            }, 3000);
                        }
                    });
                });
            }

            // Regenerar Access Code (com SweetAlert)
            const regenBtn = document.getElementById('regenerateAccessCodeBtn');
            if (regenBtn) {
                regenBtn.addEventListener('click', async function() {
                    // Verifica se o SweetAlert está disponível
                    if (typeof Swal === 'undefined') {
                        console.error('SweetAlert não carregado');
                        alert('Erro: SweetAlert não carregado. Recarrega a página.');
                        return;
                    }
                    const {
                        value: password
                    } = await Swal.fire({
                        title: 'Regenerar código de acesso',
                        text: 'Confirma a tua senha actual para gerar um novo código de 6 dígitos.',
                        input: 'password',
                        inputPlaceholder: 'Senha do administrador',
                        inputAttributes: {
                            autocomplete: 'current-password'
                        },
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        confirmButtonText: 'Regenerar',
                        cancelButtonText: 'Cancelar'
                    });
                    if (password) {
                        document.getElementById('regen_password_hidden').value = password;
                        document.getElementById('form-regen-access').submit();
                    }
                });
            }
        });
    });
    </script>
</body>

</html>