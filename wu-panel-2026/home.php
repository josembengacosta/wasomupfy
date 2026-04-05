<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Painel Admin — Home
// Arquivo: admin/home.php
// .htaccess: ^admin/?$ → admin/home.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/platform_admin.php';
if (!function_exists('adm_fmt_action')) {
    function adm_fmt_action(string $a): string
    {
        $m = [
            'auth.login'               => 'Início de sessão',
            'auth.logout'              => 'Fim de sessão',
            'auth.failed_login'        => 'Tentativa de login falhada',
            'auth.password_changed'    => 'Senha alterada',
            'auth.reset_requested'     => 'Reset de senha solicitado',
            'auth.lockscreen_unlocked' => 'Ecrã desbloqueado',
            'auth.auto_login'          => 'Login automático (cookie)',
            'auth.ip_rate_limit'       => 'Bloqueio por tentativas (IP)',
            'auth.csrf_fail'           => 'Falha CSRF detectada',
        ];
        return $m[$a] ?? str_replace(['.', '_'], [' → ', ' '], $a);
    }
}

$canUsersView     = hasPermission($admin_id, 'users.view');
$canEmployeesView = hasPermission($admin_id, 'employees.view');
$canEmployeesEdit = hasPermission($admin_id, 'employees.edit');
$canMusicView     = hasPermission($admin_id, 'music.view');
$canMusicApprove  = hasPermission($admin_id, 'music.approve');
$canFinancesView  = hasPermission($admin_id, 'finances.view');
$canAnalyticsView = hasPermission($admin_id, 'analytics.view');
$canSupportView   = hasPermission($admin_id, 'support.view');
$canAuditView     = hasPermission($admin_id, 'audit.view');
$canSettingsView  = hasPermission($admin_id, 'settings.view');

// ── Queries — Stats cards ──
$total_users        = $canUsersView ? (int)$db->query("SELECT COUNT(*) FROM _users")->fetchColumn() : 0;
$total_emp          = $canEmployeesView ? (int)$db->query("SELECT COUNT(*) FROM _employees")->fetchColumn() : 0;
$total_releases     = $canMusicView ? (int)$db->query("SELECT COUNT(*) FROM _album")->fetchColumn() : 0;
$total_visitors     = $canAnalyticsView ? (int)$db->query("SELECT COUNT(*) FROM _visitor WHERE is_bot=0")->fetchColumn() : 0;
$online_now         = $canAnalyticsView ? (int)$db->query("
    SELECT COUNT(*)
    FROM _user_presence
    WHERE online_status != 'offline'
      AND last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
")->fetchColumn() : 0;
$revenue_today      = $canFinancesView ? (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM _payment WHERE status_payment='approved' AND DATE(creat_payment)=CURDATE()")->fetchColumn() : 0.0;
$accounts_ok        = $canUsersView ? (int)$db->query("SELECT COUNT(*) FROM _users WHERE status_user='active'")->fetchColumn() : 0;
$accounts_pend      = $canUsersView ? (int)$db->query("SELECT COUNT(*) FROM _users WHERE status_user NOT IN ('active')")->fetchColumn() : 0;
$total_bank_accounts = $canUsersView ? (int)$db->query("SELECT COUNT(DISTINCT id_users) FROM _account")->fetchColumn() : 0;
$total_collabs      = $canUsersView ? (int)$db->query("SELECT COUNT(*) FROM _collaborators")->fetchColumn() : 0;
$revenue_total      = $canFinancesView ? (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM _payment WHERE status_payment='approved'")->fetchColumn() : 0.0;
$pending_releases   = $canMusicApprove ? (int)$db->query("SELECT COUNT(*) FROM _album WHERE status_album IN ('pending','under_review')")->fetchColumn() : 0;
$open_tickets       = $canSupportView ? (int)$db->query("SELECT COUNT(*) FROM _support_ticket WHERE status_ticket NOT IN ('closed','resolved')")->fetchColumn() : 0;

// ── Queries — Streams reais por período ──
$pt_days = ['Monday' => 'Seg', 'Tuesday' => 'Ter', 'Wednesday' => 'Qua', 'Thursday' => 'Qui', 'Friday' => 'Sex', 'Saturday' => 'Sáb', 'Sunday' => 'Dom'];
$streams_today_labels = array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00', range(0, 23));
$streams_today_data   = array_fill(0, 24, 0);
$streams_7d_labels    = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
$streams_7d_data      = array_fill(0, 7, 0);
$streams_30d_labels   = array_map(fn($i) => "Dia $i", range(1, 30));
$streams_30d_data     = array_fill(0, 30, 0);
$streams_month_labels = ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'];
$streams_month_data   = array_fill(0, 4, 0);
$streams_total_today  = 0;
$streams_total_7d     = 0;
$streams_total_30d    = 0;
$streams_total_month  = 0;
$new_listeners_today  = 0;
$new_listeners_7d     = 0;
$new_listeners_30d    = 0;
$new_listeners_month  = 0;
$rev_today            = 0.0;
$rev_7d               = 0.0;
$rev_30d              = 0.0;
$rev_month            = 0.0;

if ($canAnalyticsView) {
    $streams_today_rows = $db->query("
        SELECT HOUR(v.creat_visitor) as hora, COUNT(v.id_visitor) as total
        FROM _visitor v
        WHERE DATE(v.creat_visitor) = CURDATE() AND v.is_bot=0
        GROUP BY hora ORDER BY hora ASC
    ")->fetchAll();
    $streams_today_labels = array_map(fn($r) => str_pad($r['hora'], 2, '0', STR_PAD_LEFT) . ':00', $streams_today_rows);
    $streams_today_data   = array_map(fn($r) => (int)$r['total'], $streams_today_rows);
    if (empty($streams_today_labels)) {
        $streams_today_labels = array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00', range(0, 23));
        $streams_today_data   = array_fill(0, 24, 0);
    }

    $streams_7d_rows = $db->query("
        SELECT DAYNAME(creat_visitor) as dia, DATE(creat_visitor) as dt, COUNT(*) as total
        FROM _visitor WHERE creat_visitor >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_bot=0
        GROUP BY dt ORDER BY dt ASC
    ")->fetchAll();
    $streams_7d_labels = array_map(fn($r) => $pt_days[$r['dia']] ?? $r['dia'], $streams_7d_rows);
    $streams_7d_data   = array_map(fn($r) => (int)$r['total'], $streams_7d_rows);
    if (empty($streams_7d_labels)) {
        $streams_7d_labels = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
        $streams_7d_data   = array_fill(0, 7, 0);
    }

    $streams_30d_rows = $db->query("
        SELECT DAY(creat_visitor) as dia, COUNT(*) as total
        FROM _visitor WHERE creat_visitor >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND is_bot=0
        GROUP BY DATE(creat_visitor) ORDER BY creat_visitor ASC
    ")->fetchAll();
    $streams_30d_labels = array_map(fn($r) => 'Dia ' . $r['dia'], $streams_30d_rows);
    $streams_30d_data   = array_map(fn($r) => (int)$r['total'], $streams_30d_rows);
    if (empty($streams_30d_labels)) {
        $streams_30d_labels = array_map(fn($i) => "Dia $i", range(1, 30));
        $streams_30d_data   = array_fill(0, 30, 0);
    }

    $streams_month_rows = $db->query("
        SELECT WEEK(creat_visitor) as sem, COUNT(*) as total
        FROM _visitor WHERE MONTH(creat_visitor)=MONTH(NOW()) AND YEAR(creat_visitor)=YEAR(NOW()) AND is_bot=0
        GROUP BY sem ORDER BY sem ASC
    ")->fetchAll();
    $streams_month_labels = array_map(fn($i) => "Semana $i", range(1, count($streams_month_rows)));
    $streams_month_data   = array_map(fn($r) => (int)$r['total'], $streams_month_rows);
    if (empty($streams_month_labels)) {
        $streams_month_labels = ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'];
        $streams_month_data   = array_fill(0, 4, 0);
    }

    $streams_total_today  = (int)array_sum($streams_today_data);
    $streams_total_7d     = (int)array_sum($streams_7d_data);
    $streams_total_30d    = (int)array_sum($streams_30d_data);
    $streams_total_month  = (int)array_sum($streams_month_data);
    $new_listeners_today  = (int)$db->query("SELECT COUNT(*) FROM _visitor WHERE DATE(creat_visitor)=CURDATE() AND visit_count=1 AND is_bot=0")->fetchColumn();
    $new_listeners_7d     = (int)$db->query("SELECT COUNT(*) FROM _visitor WHERE creat_visitor>=DATE_SUB(NOW(),INTERVAL 7 DAY) AND visit_count=1 AND is_bot=0")->fetchColumn();
    $new_listeners_30d    = (int)$db->query("SELECT COUNT(*) FROM _visitor WHERE creat_visitor>=DATE_SUB(NOW(),INTERVAL 30 DAY) AND visit_count=1 AND is_bot=0")->fetchColumn();
    $new_listeners_month  = (int)$db->query("SELECT COUNT(*) FROM _visitor WHERE MONTH(creat_visitor)=MONTH(NOW()) AND YEAR(creat_visitor)=YEAR(NOW()) AND visit_count=1 AND is_bot=0")->fetchColumn();
    $country_stats        = $db->query("
        SELECT country_code, COUNT(*) as total
        FROM _visitor WHERE is_bot=0 AND country_code IS NOT NULL
        GROUP BY country_code ORDER BY total DESC LIMIT 3
    ")->fetchAll();
} else {
    $country_stats = [];
}

if ($canAnalyticsView || $canFinancesView) {
    $rev_today = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM _payment WHERE status_payment='approved' AND DATE(creat_payment)=CURDATE()")->fetchColumn();
    $rev_7d    = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM _payment WHERE status_payment='approved' AND creat_payment>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
    $rev_30d   = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM _payment WHERE status_payment='approved' AND creat_payment>=DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn();
    $rev_month = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM _payment WHERE status_payment='approved' AND MONTH(creat_payment)=MONTH(NOW()) AND YEAR(creat_payment)=YEAR(NOW())")->fetchColumn();
}

$top_tracks = $canMusicView ? $db->query("
    SELECT t.title_track,
           COALESCE(ar.stage_name, u.first_name) AS artist_name,
           SUM(s.streams) AS total_streams
    FROM _stream s
    JOIN _track t ON t.id_track = s.id_track
    JOIN _album a ON a.id_album = t.id_album
    JOIN _users u ON u.id_users = a.id_users
    LEFT JOIN _artist ar ON ar.id_artist = a.id_artist
    GROUP BY t.id_track
    ORDER BY total_streams DESC
    LIMIT 5
")->fetchAll() : [];

$recent_users_full = $canUsersView ? $db->query("
    SELECT u.id_users, u.first_name, u.second_name, u.email_user,
           u.photo_user, u.status_user, u.plan_activated_at,
           p.name_plan
    FROM _users u
    LEFT JOIN _plans p ON p.id_plan = u.plan_selected
    ORDER BY u.id_users DESC
    LIMIT 8
")->fetchAll() : [];

$audit_list = $canAuditView ? $db->query("
    SELECT al.action, al.creat_log, al.ip_address, al.entity,
           e.first_name, e.second_name, e.photo_employees, e.role
    FROM _audit_log al
    LEFT JOIN _employees e ON e.id_employees = al.id_employees
    ORDER BY al.creat_log DESC
    LIMIT 5
")->fetchAll() : [];

$releases_24h = $canMusicView ? $db->query("
    SELECT a.title_album, a.type_album, a.status_album, a.img_cover, a.creat_album,
           u.first_name, u.second_name, u.email_user
    FROM _album a
    JOIN _users u ON u.id_users = a.id_users
    WHERE a.creat_album >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY a.creat_album DESC
    LIMIT 8
")->fetchAll() : [];

$tickets_24h = $canSupportView ? $db->query("
    SELECT st.id_ticket, st.subject, st.body, st.status_ticket, st.name_contact, st.email_contact,
           st.priority, st.creat_ticket, u.first_name, u.second_name, u.email_user, u.photo_user
    FROM _support_ticket st
    LEFT JOIN _users u ON u.id_users = st.id_users
    WHERE st.creat_ticket >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY st.creat_ticket DESC
    LIMIT 8
")->fetchAll() : [];

$plans_dist = $canFinancesView ? $db->query("
    SELECT p.name_plan, COUNT(u.id_users) as total
    FROM _users u JOIN _plans p ON p.id_plan = u.plan_selected
    WHERE u.plan_selected IS NOT NULL
    GROUP BY u.plan_selected, p.name_plan ORDER BY total DESC
")->fetchAll() : [];
$plans_labels_json = json_encode(array_column($plans_dist, 'name_plan'), JSON_UNESCAPED_UNICODE);
$plans_data_json   = json_encode(array_column($plans_dist, 'total'));

$pending_rel = $canMusicApprove ? $db->query("
    SELECT a.id_album, a.title_album, a.type_album, a.status_album,
           a.img_cover, a.creat_album,
           u.first_name, u.second_name
    FROM _album a JOIN _users u ON u.id_users=a.id_users
    WHERE a.status_album IN ('pending','under_review')
    ORDER BY a.creat_album ASC LIMIT 5
")->fetchAll() : [];

$recent_pays = $canFinancesView ? $db->query("
    SELECT p.payment_ref, p.amount, p.status_payment, p.payment_method, p.creat_payment,
           u.first_name, u.second_name, u.photo_user, pl.name_plan
    FROM _payment p
    JOIN _users u ON u.id_users=p.id_users
    JOIN _plans pl ON pl.id_plan=p.id_plan
    ORDER BY p.creat_payment DESC LIMIT 8
")->fetchAll() : [];

// ── Helpers de status ──
function rel_status(string $s): array
{
    return match ($s) {
        'pending'      => ['warning', 'Pendente'],
        'under_review' => ['info', 'Em revisão'],
        'approved'     => ['success', 'Aprovado'],
        'rejected'     => ['danger', 'Rejeitado'],
        default        => ['secondary', ucfirst($s)],
    };
}

function pay_status_class(string $s): string
{
    return match ($s) {
        'approved' => 'success',
        'pending' => 'warning',
        'rejected' => 'danger',
        'refunded' => 'info',
        default => 'secondary',
    };
}

function pay_status_label(string $s): string
{
    return match ($s) {
        'approved' => 'Aprovado',
        'pending' => 'Pendente',
        'rejected' => 'Rejeitado',
        'refunded' => 'Reembolso',
        default => ucfirst($s),
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
    <meta name="apple-mobile-web-app-status-bar-style" content="#FF0089" />
    <link rel="apple-touch-icon" href="../assets/img/icones/wasomupfy_fiv_512.png" />
    <link rel="apple-touch-startup-image" href="../assets/img/screenshots/splash.png" />
    <link rel="manifest" href="manifest.json" />
    <title>Painel Administrador — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/libs/scrollue.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />

    <style>
    /* ── Novos Clientes — cards com foto/nome/email ── */
    .client-user-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
        margin-top: 8px;
    }

    .client-user-card {
        background: rgba(255, 255, 255, .04);
        border: 1px solid rgba(255, 255, 255, .08);
        border-radius: 12px;
        padding: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: background .2s, border-color .2s, transform .2s;
        cursor: default;
    }

    .client-user-card:hover {
        background: rgba(255, 0, 137, .08);
        border-color: rgba(255, 0, 137, .25);
        transform: translateY(-2px);
    }

    .client-user-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        flex-shrink: 0;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: .82rem;
        color: #fff;
        border: 2px solid rgba(255, 255, 255, .1);
    }

    .client-user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .client-user-info {
        flex: 1;
        min-width: 0;
    }

    .client-user-name {
        font-size: .82rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .client-user-email {
        font-size: .72rem;
        opacity: .55;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .client-user-plan {
        font-size: .65rem;
        background: rgba(255, 0, 137, .15);
        color: #FF0089;
        padding: 2px 7px;
        border-radius: 20px;
        font-weight: 600;
        white-space: nowrap;
        margin-top: 3px;
        display: inline-block;
    }

    .client-user-date {
        font-size: .68rem;
        opacity: .4;
        margin-top: 2px;
    }

    /* ── Logout modal melhorado ── */
    .logout-modal-session {
        background: rgba(0, 0, 0, .04);
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 16px;
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
        max-width: 60%;
        word-break: break-all;
    }

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

    .logout-admin-name {
        font-size: .95rem;
        font-weight: 700;
        color: #111;
    }

    .logout-admin-role {
        font-size: .78rem;
        color: #888;
        margin-top: 2px;
    }

    /* ── List items para Lançamentos por Rever e Pagamentos ── */
    .adm-list-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255, 255, 255, .07);
    }

    .adm-list-item:last-child {
        border-bottom: none;
    }

    .adm-list-info {
        flex: 1;
        min-width: 0;
    }

    .adm-list-title {
        font-size: .84rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .adm-list-sub {
        font-size: .74rem;
        opacity: .6;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .adm-list-meta {
        text-align: right;
        flex-shrink: 0;
    }

    .adm-cover-thumb {
        width: 38px;
        height: 38px;
        border-radius: 7px;
        object-fit: cover;
        flex-shrink: 0;
        border: 1px solid rgba(255, 255, 255, .1);
    }

    .adm-cover-placeholder {
        width: 38px;
        height: 38px;
        border-radius: 7px;
        background: rgba(255, 0, 137, .12);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FF0089;
        flex-shrink: 0;
    }

    .adm-avatar-sm {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: .65rem;
        color: #fff;
        flex-shrink: 0;
        overflow: hidden;
    }

    .adm-avatar-sm img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* ── Hora ao vivo ── */
    .live-clock-wrap {
        text-align: right;
        margin-bottom: 6px;
    }

    .live-clock-time {
        font-size: 1.1rem;
        font-weight: 700;
        letter-spacing: .5px;
    }

    .live-clock-date {
        font-size: .73rem;
        opacity: .6;
        margin-top: 1px;
    }

    /* ── Card section header ── */
    .section-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    </style>
</head>

<body>

    <div class="wrapper">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php require_once __DIR__ . '/include/sidebar.php'; ?>
        <div class="content w-100" id="mainContent">
            <?php require_once __DIR__ . '/include/navbar.php'; ?>

            <div class="container-fluid p-0">
                <!-- Welcome + Clock + Acções -->
                <div class="row mb-3 mt-2">
                    <div class="welcome-text col-auto d-sm-block">
                        <h2 class="h4 mb-2">
                            <i class="bi bi-speedometer2 me-2"></i>Bem-vindo,<br />
                            <span><?php echo htmlspecialchars($admin_fullname); ?></span>
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="#" class="text-secondary-emphasis">Home</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable" aria-current="page">Dashboard</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto text-end mt-n1 mt-3">
                        <!-- Hora e data ao vivo -->
                        <div class="live-clock-wrap text-white-stable">
                            <div class="live-clock-time" id="live-time">--:--</div>
                            <div class="live-clock-date" id="live-date"></div>
                        </div>
                        <div class="mt-2">
                            <div class="dropdown me-2 d-inline-block position-relative">
                                <a class="btn btn-light bg-white shadow-sm dropdown-toggle" href="#"
                                    data-bs-toggle="dropdown" data-bs-display="static">
                                    <i class="align-middle mt-n1 bi bi-calendar"></i> Acções
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <h6 class="dropdown-header">Opções rápidas</h6>
                                    <?php if ($canMusicApprove): ?>
                                    <a class="dropdown-item"
                                        href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/releases/pending">Aprovar
                                        Lançamentos</a>
                                    <?php endif; ?>
                                    <?php if ($canFinancesView): ?>
                                    <a class="dropdown-item"
                                        href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/finances/payments">Aprovar
                                        Pagamentos</a>
                                    <?php endif; ?>
                                    <?php if ($canEmployeesEdit): ?>
                                    <a class="dropdown-item"
                                        href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees">Novo
                                        funcionário</a>
                                    <?php endif; ?>
                                    <?php if ($canSettingsView): ?>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item"
                                        href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/settings">Configurações</a>
                                    <?php endif; ?>
                                    <?php if ($canAuditView): ?>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/audit">Log
                                        de
                                        Auditoria</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button class="btn btn-light bg-white shadow-sm">
                                <i class="align-middle bi bi-repeat" id="refresh"
                                    onclick="window.location.reload()">&nbsp;</i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ══ STATS CARDS — DADOS REAIS ══ -->
                <div class="row">
                    <?php if ($canUsersView): ?>
                    <div class="col-12 col-sm-4 col-xxl-3 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="text-white-stable">
                                            Total de Usuários
                                            <span class="badge badge-soft-pink counter"
                                                data-valor="<?php echo $total_users; ?>" data-tipo="contagem">0</span>
                                        </h5>
                                        <p class="mb-2">Total de usuários da <strong>Wasom Upfy</strong></p>
                                        <div class="mb-0">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                                                class="card-link">Ver usuários
                                                <i class="bi bi-arrow-right"></i></a>
                                        </div>
                                    </div>
                                    <div class="d-inline-block ms-3">
                                        <div class="stat">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                                                title="Ver usuários"><i
                                                    class="align-middle bi bi-people card-icon"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($canEmployeesView): ?>
                    <div class="col-12 col-sm-4 col-xxl-3 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="text-white-stable">
                                            Total de Funcionários
                                            <span class="badge badge-soft-pink counter"
                                                data-valor="<?php echo $total_emp; ?>" data-tipo="contagem">0</span>
                                        </h5>
                                        <p class="mb-2">Total de funcionários da <strong>Wasom Upfy</strong></p>
                                        <div class="mb-0">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees"
                                                class="card-link">Ver
                                                funcionários <i class="bi bi-arrow-right"></i></a>
                                        </div>
                                    </div>
                                    <div class="d-inline-block ms-3">
                                        <div class="stat">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees"
                                                title="Ver funcionários"><i
                                                    class="align-middle bi bi-people card-icon"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($canMusicView): ?>
                    <div class="col-12 col-sm-4 col-xxl-3 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="text-white-stable">
                                            Total de Lançamentos
                                            <span class="badge badge-soft-pink counter"
                                                data-valor="<?php echo $total_releases; ?>"
                                                data-tipo="contagem">0</span>
                                        </h5>
                                        <p class="mb-2">Todos os lançamentos dos usuários da <strong>Wasom Upfy</strong>
                                        </p>
                                        <div class="mb-0">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/releases"
                                                class="card-link">Ver
                                                lançamentos <i class="bi bi-arrow-right"></i></a>
                                        </div>
                                    </div>
                                    <div class="d-inline-block ms-3">
                                        <div class="stat">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/releases"
                                                title="Ver lançamentos"><i
                                                    class="align-middle bi-rocket-takeoff card-icon"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($canAnalyticsView): ?>
                    <div class="col-12 col-sm-4 col-xxl-3 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="text-white-stable">
                                            Visitantes
                                            <span class="badge badge-soft-pink counter"
                                                data-valor="<?php echo $total_visitors; ?>"
                                                data-tipo="contagem">0</span>
                                        </h5>
                                        <p class="mb-2">Todos os visitantes globais da <strong>Wasom Upfy</strong></p>
                                        <div class="mb-0">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/visitors"
                                                class="card-link">Ver os
                                                visitantes <i class="bi bi-arrow-right"></i></a>
                                        </div>
                                    </div>
                                    <div class="d-inline-block ms-3">
                                        <div class="stat">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/visitors"
                                                title="Ver visitantes"><i class="align-middle bi-eye card-icon"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-4 col-xxl-3 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="text-white-stable">
                                            Usuários Online
                                            <span class="badge badge-soft-pink counter"
                                                data-valor="<?php echo $online_now; ?>" data-tipo="contagem">0</span>
                                        </h5>
                                        <p class="mb-2">Todos os usuários online na <strong>Wasom Upfy</strong></p>
                                        <div class="mb-0">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/online-users"
                                                class="card-link">Ver
                                                actividade <i class="bi bi-arrow-right"></i></a>
                                        </div>
                                    </div>
                                    <div class="d-inline-block ms-3">
                                        <div class="stat">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/online-users"
                                                title="Usuários online"><i
                                                    class="align-middle bi-person-workspace card-icon"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($canFinancesView): ?>
                    <div class="col-12 col-sm-4 col-xxl-3 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="text-white-stable">
                                            Receita de Hoje
                                            <span
                                                class="badge badge-soft-pink"><?php echo adm_fmt_aoa($revenue_today); ?></span>
                                        </h5>
                                        <p class="mb-2">Vê relatórios sobre a receita de hoje</p>
                                        <div class="mb-0">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/finances/payments"
                                                class="card-link">Ver
                                                relatório <i class="bi bi-arrow-right"></i></a>
                                        </div>
                                    </div>
                                    <div class="d-inline-block ms-3">
                                        <div class="stat">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/finances/payments"
                                                title="Receita hoje"><i
                                                    class="align-middle bi-cash-coin card-icon"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($canUsersView): ?>
                    <div class="col-12 col-sm-6 col-xxl-3 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="text-white-stable">
                                            Contas Disponíveis
                                            <span class="badge badge-soft-pink counter"
                                                data-valor="<?php echo $accounts_ok; ?>" data-tipo="contagem">0</span>
                                        </h5>
                                        <p class="mb-2">Vê agora todas as Contas Disponíveis na <strong>Wasom
                                                Upfy</strong></p>
                                        <div class="mb-0">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/available-account"
                                                class="card-link">Ver
                                                as Contas Disponíveis <i class="bi bi-arrow-right"></i></a>
                                        </div>
                                    </div>
                                    <div class="d-inline-block ms-3">
                                        <div class="stat">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/available-account"
                                                title="Contas disponíveis"><i
                                                    class="align-middle bi-person-check card-icon"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xxl-3 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="text-white-stable">
                                            Contas Indisponíveis
                                            <span class="badge badge-soft-pink counter"
                                                data-valor="<?php echo $accounts_pend; ?>" data-tipo="contagem">0</span>
                                        </h5>
                                        <p class="mb-2">Vê agora todas as contas indisponível na <strong>Wasom
                                                Upfy</strong></p>
                                        <div class="mb-0">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/unavailable-account"
                                                class="card-link">Ver as Contas Indisponíveis <i
                                                    class="bi bi-arrow-right"></i></a>
                                        </div>
                                    </div>
                                    <div class="d-inline-block ms-3">
                                        <div class="stat">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/unavailable-account"
                                                title="Contas indisponíveis"><i
                                                    class="align-middle bi-person-exclamation card-icon"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($canMusicApprove): ?>
                    <!-- NOVO CARD — Lançamentos Pendentes -->
                    <div class="col-12 col-sm-4 col-xxl-3 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="text-white-stable">
                                            Lançamentos Pendentes
                                            <span class="badge badge-soft-pink counter"
                                                data-valor="<?php echo $pending_releases; ?>"
                                                data-tipo="contagem">0</span>
                                        </h5>
                                        <p class="mb-2">Lançamentos aguardando revisão e aprovação</p>
                                        <div class="mb-0">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/releases/pending"
                                                class="card-link">Rever
                                                agora <i class="bi bi-arrow-right"></i></a>
                                        </div>
                                    </div>
                                    <div class="d-inline-block ms-3">
                                        <div class="stat">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/releases/pending"
                                                title="Lançamentos pendentes"><i
                                                    class="align-middle bi-hourglass-split card-icon"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($canUsersView): ?>
                    <!-- NOVO — Total com Conta Bancária -->
                    <div class="col-12 col-sm-4 col-xxl-3 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="text-white-stable">
                                            Com Conta Bancária
                                            <span class="badge badge-soft-pink counter"
                                                data-valor="<?php echo $total_bank_accounts; ?>"
                                                data-tipo="contagem">0</span>
                                        </h5>
                                        <p class="mb-2">Utilizadores com conta bancária registada na <strong>Wasom
                                                Upfy</strong></p>
                                        <div class="mb-0">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                                                class="card-link">Ver contas <i class="bi bi-arrow-right"></i></a>
                                        </div>
                                    </div>
                                    <div class="d-inline-block ms-3">
                                        <div class="stat">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                                                title="Contas bancárias"><i
                                                    class="align-middle bi-bank card-icon"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($canUsersView): ?>
                    <!-- NOVO — Total Colaboradores -->
                    <div class="col-12 col-sm-4 col-xxl-3 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="text-white-stable">
                                            Total Colaboradores
                                            <span class="badge badge-soft-pink counter"
                                                data-valor="<?php echo $total_collabs; ?>" data-tipo="contagem">0</span>
                                        </h5>
                                        <p class="mb-2">Colaboradores registados pelos artistas da <strong>Wasom
                                                Upfy</strong></p>
                                        <div class="mb-0">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                                                class="card-link">Ver
                                                colaboradores <i class="bi bi-arrow-right"></i></a>
                                        </div>
                                    </div>
                                    <div class="d-inline-block ms-3">
                                        <div class="stat">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                                                title="Colaboradores"><i
                                                    class="align-middle bi-people-fill card-icon"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($canFinancesView): ?>
                    <!-- NOVO — Receita Total Acumulada -->
                    <div class="col-12 col-sm-4 col-xxl-3 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="text-white-stable">
                                            Receita Acumulada
                                            <span
                                                class="badge badge-soft-pink"><?php echo adm_fmt_aoa($revenue_total); ?></span>
                                        </h5>
                                        <p class="mb-2">Total histórico de pagamentos aprovados na <strong>Wasom
                                                Upfy</strong></p>
                                        <div class="mb-0">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/finances"
                                                class="card-link">Ver
                                                finanças <i class="bi bi-arrow-right"></i></a>
                                        </div>
                                    </div>
                                    <div class="d-inline-block ms-3">
                                        <div class="stat">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/finances"
                                                title="Receita acumulada"><i
                                                    class="align-middle bi-graph-up-arrow card-icon"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

                <!-- ══ SUPORTE + MAPA + STREAMS ══ -->
                <div class="row">
                    <?php if ($canSupportView || $canAnalyticsView): ?>
                    <div class="col-md-6 mb-3">
                        <?php if ($canSupportView): ?>
                        <div class="card stats-card-primary flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="text-white-stable">
                                            Pedidos de Suporte de Usuários
                                            <?php if ($open_tickets > 0): ?>
                                            <span class="badge bg-danger"><?php echo $open_tickets; ?></span>
                                            <?php endif; ?>
                                        </h5>
                                        <p class="mb-2">Todos os pedidos de suporte da <strong>Wasom Upfy</strong> estão
                                            disponíveis aqui.</p>
                                        <div class="mb-0">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/support"
                                                class="card-link">Ver pedidos
                                                de suporte <i class="bi bi-arrow-right"></i></a>
                                        </div>
                                    </div>
                                    <div class="d-inline-block ms-3">
                                        <div class="stat">
                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/support"
                                                title="Suporte"><i class="align-middle bi-headset card-icon"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($canAnalyticsView): ?>
                        <div class="card stats-card-primary flex-fill">
                            <h5 class="card-title">Países que usam a Wasom Upfy</h5>
                            <div id="clientMap"></div>
                            <div class="row text-center">
                                <?php
                                        $country_map = ['AO' => 'Angola', 'PT' => 'Portugal', 'BR' => 'Brasil'];
                                        if (!empty($country_stats)):
                                            $grand = max(1, array_sum(array_column($country_stats, 'total')));
                                            foreach (array_slice($country_stats, 0, 3) as $cs):
                                                $pct = round(($cs['total'] / $grand) * 100, 1);
                                                $lbl = $country_map[$cs['country_code']] ?? $cs['country_code'];
                                        ?>
                                <div class="col-4 border-end">
                                    <h6 class="text-white-stable"><?php echo htmlspecialchars($lbl); ?></h6>
                                    <h4 class="counter" data-valor="<?php echo $pct; ?>" data-tipo="porcentagem">0%</h4>
                                </div>
                                <?php endforeach;
                                        else: ?>
                                <div class="col-4 border-end">
                                    <h6 class="text-white-stable">Angola</h6>
                                    <h4 class="counter" data-valor="58" data-tipo="porcentagem">0%</h4>
                                </div>
                                <div class="col-4 border-end">
                                    <h6 class="text-white-stable">Portugal</h6>
                                    <h4 class="counter" data-valor="22" data-tipo="porcentagem">0%</h4>
                                </div>
                                <div class="col-4">
                                    <h6 class="text-white-stable">Outros</h6>
                                    <h4 class="counter" data-valor="20" data-tipo="porcentagem">0%</h4>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($canAnalyticsView): ?>
                    <div class="col-md-6 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Estatísticas de Streams</h5>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" aria-label="Selecionar Período">
                                        Últimos 7 dias
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" data-period="today">Hoje</a></li>
                                        <li><a class="dropdown-item" href="#" data-period="7days">Últimos 7 dias</a>
                                        </li>
                                        <li><a class="dropdown-item" href="#" data-period="30days">Últimos 30 dias</a>
                                        </li>
                                        <li><a class="dropdown-item" href="#" data-period="month">Este mês</a></li>
                                    </ul>
                                </div>
                            </div>
                            <canvas id="streamsChart" style="max-height: 400px"></canvas>
                            <div class="row mt-3 text-center">
                                <div class="col-4 border-end">
                                    <h6 class="text-white-stable">Total Streams</h6>
                                    <h4 id="totalStreams" class="counter" data-valor="173.589" data-tipo="decimal">0
                                    </h4>
                                </div>
                                <div class="col-4 border-end">
                                    <h6 class="text-white-stable">Novos Ouvintes</h6>
                                    <h4 id="newListeners" class="counter" data-valor="1.254" data-tipo="decimal">0</h4>
                                </div>
                                <div class="col-4">
                                    <h6 class="text-white-stable">Receita</h6>
                                    <h4 id="revenue" class="counter" data-valor="<?php echo ($revenue_total); ?>"
                                        data-tipo="moeda">kz0
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ══ TOP MÚSICAS (reais) + ATIVIDADE RECENTE (real) ══ -->
                <div class="row">
                    <?php if ($canMusicView): ?>
                    <div class="col-md-6 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <h5 class="card-title">Top Músicas</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Música</th>
                                            <th>Artista</th>
                                            <th>Streams</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($top_tracks)): ?>
                                        <?php foreach ($top_tracks as $i => $t): ?>
                                        <tr>
                                            <td><?php echo $i + 1; ?></td>
                                            <td><?php echo htmlspecialchars($t['title_track']); ?></td>
                                            <td><?php echo htmlspecialchars($t['artist_name']); ?></td>
                                            <td><?php echo number_format((int)$t['total_streams'], 0, ',', '.'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">Sem dados de streams
                                                ainda.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics" class="card-link">Ver todas <i
                                    class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($canAuditView): ?>
                    <!-- Atividade Recente REAL -->
                    <div class="col-12 col-md-6 mb-3">
                        <div class="card stats-card-primary flex-fill">
                            <h5 class="card-title">Atividade Recente</h5>
                            <div class="activity-list">
                                <?php if (empty($audit_list)): ?>
                                <p class="text-white-stable" style="opacity:.6;font-size:.85rem">Sem actividade
                                    registada ainda.</p>
                                <?php else: ?>
                                <?php foreach ($audit_list as $log):
                                            $who   = $log['first_name'] ? trim($log['first_name'] . ' ' . ($log['second_name'] ?? '')) : 'Sistema';
                                            $ini   = $log['first_name'] ? adm_initials($log['first_name'], $log['second_name'] ?? '') : 'WU';
                                            $color = adm_avatar_color($who);
                                        ?>
                                <div class="activity-item d-flex mb-3">
                                    <div class="flex-shrink-0">
                                        <div
                                            style="width:40px;height:40px;border-radius:50%;background:<?php echo $color; ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;color:#fff;overflow:hidden">
                                            <?php if (!empty($log['photo_employees'])): ?>
                                            <img src="../assets/comprovantes/uploads/employees/<?php echo htmlspecialchars($log['photo_employees']); ?>"
                                                alt="" style="width:100%;height:100%;object-fit:cover"
                                                onerror="this.style.display='none';this.parentElement.textContent='<?php echo $ini; ?>'" />
                                            <?php else: ?>
                                            <?php echo adm_initials($log['first_name'] ?? '', $log['second_name'] ?? ''); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0"><?php echo htmlspecialchars(adm_fmt_action($log['action'])); ?>
                                        </h6>
                                        <p class="text-white-stable mb-0">
                                            <?php echo htmlspecialchars($who); ?>
                                            <?php if ($log['ip_address']): ?> · <code
                                                style="font-size:.75rem;opacity:.7"><?php echo htmlspecialchars($log['ip_address']); ?></code><?php endif; ?>
                                        </p>
                                        <small
                                            class="text-white-stable"><?php echo adm_fmt_date($log['creat_log']); ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/audit" class="card-link mt-3">Ver toda
                                actividade <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ══ NOSSOS CLIENTES MELHORADO + PLANOS ACTIVOS ══ -->
                <div class="row">
                    <?php if ($canUsersView): ?>
                    <!-- Nossos Clientes — cards com foto/nome/email/plano/data -->
                    <div class="col-md-7 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <div class="section-card-header">
                                <h5 class="card-title mb-0">Novos Clientes</h5>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users" class="card-link"
                                    style="font-size:.8rem">
                                    Ver todos <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <p class="small mb-3">Utilizadores mais recentes a juntar-se à Wasom Upfy</p>
                            <?php if (empty($recent_users_full)): ?>
                            <p class="text-white-stable" style="opacity:.6;font-size:.85rem">Nenhum utilizador registado
                                ainda.</p>
                            <?php else: ?>
                            <div class="client-user-grid">
                                <?php foreach ($recent_users_full as $u):
                                            $ini   = adm_initials($u['first_name'], $u['second_name'] ?? '');
                                            $color = adm_avatar_color($u['first_name'] . ($u['second_name'] ?? ''));
                                            $plan  = $u['name_plan'] ?? 'Sem plano';
                                            $dt    = $u['plan_activated_at'] ? adm_fmt_date($u['plan_activated_at']) : '—';
                                        ?>
                                <div class="client-user-card">
                                    <div class="client-user-avatar" style="background:<?php echo $color; ?>">
                                        <?php if (!empty($u['photo_user'])): ?>
                                        <img src="../assets/comprovantes/uploads/users/<?php echo htmlspecialchars($u['photo_user']); ?>"
                                            alt="<?php echo htmlspecialchars($u['first_name']); ?>"
                                            onerror="this.style.display='none';this.parentElement.textContent='<?php echo $ini; ?>'" />
                                        <?php else: echo $ini;
                                                    endif; ?>
                                    </div>
                                    <div class="client-user-info">
                                        <div class="client-user-name text-white-stable">
                                            <?php echo htmlspecialchars($u['first_name'] . ' ' . ($u['second_name'] ?? '')); ?>
                                        </div>
                                        <div class="client-user-email"><?php echo htmlspecialchars($u['email_user']); ?>
                                        </div>
                                        <div class="client-user-plan"><?php echo htmlspecialchars($plan); ?></div>
                                        <div class="client-user-date"><?php echo $dt; ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($canFinancesView): ?>
                    <!-- Planos Activos — donut + lista -->
                    <div class="col-md-5 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <h5 class="card-title">Planos Activos</h5>
                            <p class="small mb-3">Distribuição de utilizadores por plano</p>
                            <?php if (empty($plans_dist) || array_sum(array_column($plans_dist, 'total')) === 0): ?>
                            <p class="text-white-stable" style="opacity:.6;font-size:.85rem">Nenhum plano activo ainda.
                            </p>
                            <?php else: ?>
                            <div style="max-width:160px;margin:0 auto 16px">
                                <canvas id="plansDonut"></canvas>
                            </div>
                            <?php
                                    $planColors = ['#FF0089', '#3b82f6', '#22c55e', '#f97316', '#8b5cf6'];
                                    foreach ($plans_dist as $i => $plan):
                                        $col = $planColors[$i % count($planColors)];
                                    ?>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span
                                        style="width:10px;height:10px;border-radius:3px;background:<?php echo $col; ?>;display:inline-block;flex-shrink:0"></span>
                                    <span class="text-white-stable"
                                        style="font-size:.82rem"><?php echo htmlspecialchars($plan['name_plan']); ?></span>
                                </div>
                                <strong style="font-size:.82rem"><?php echo (int)$plan['total']; ?></strong>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users" class="card-link mt-3">Ver
                                utilizadores <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ══ LANÇAMENTOS POR REVER + TIMELINE ══ -->
                <div class="row">
                    <?php if ($canMusicApprove): ?>
                    <div class="col-md-6 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <div class="section-card-header">
                                <h5 class="card-title mb-0">Lançamentos por Rever</h5>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/releases/pending" class="card-link"
                                    style="font-size:.8rem">Ver todos <i class="bi bi-arrow-right"></i></a>
                            </div>
                            <?php if (empty($pending_rel)): ?>
                            <div class="text-center py-3">
                                <i class="bi bi-check-circle" style="font-size:2rem;color:#22c55e;opacity:.5"></i>
                                <p class="text-white-stable mt-2" style="font-size:.85rem;opacity:.6">Sem lançamentos
                                    pendentes.</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($pending_rel as $rel):
                                        [$sc, $sl] = rel_status($rel['status_album']);
                                    ?>
                            <div class="adm-list-item">
                                <?php if (!empty($rel['img_cover'])): ?>
                                <img src="../assets/comprovantes/uploads/covers/<?php echo htmlspecialchars($rel['img_cover']); ?>"
                                    class="adm-cover-thumb" alt="" onerror="this.style.display='none'" />
                                <?php else: ?>
                                <div class="adm-cover-placeholder"><i class="bi bi-music-note"></i></div>
                                <?php endif; ?>
                                <div class="adm-list-info">
                                    <div class="adm-list-title text-white-stable">
                                        <?php echo htmlspecialchars($rel['title_album']); ?></div>
                                    <div class="adm-list-sub">
                                        <?php echo htmlspecialchars($rel['first_name'] . ' ' . ($rel['second_name'] ?? '')); ?>
                                        · <?php echo ucfirst($rel['type_album']); ?></div>
                                </div>
                                <div class="adm-list-meta">
                                    <span class="badge bg-<?php echo $sc; ?>"><?php echo $sl; ?></span>
                                    <div style="font-size:.7rem;opacity:.45;margin-top:3px">
                                        <?php echo adm_fmt_date($rel['creat_album']); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="col-md-6 mb-3 d-flex">
                        <div class="card stats-card-primary flex-fill">
                            <h5 class="card-title">Timeline de Atualizações</h5>
                            <div class="timeline">
                                <div class="timeline-item">
                                    <h6>Wasom Upfy v2.0 Lançada</h6>
                                    <p class="text-white-stable mb-1">Novo painel admin, autenticação reforçada e
                                        auditoria completa</p>
                                    <small class="timeline-date">2026</small>
                                </div>
                                <div class="timeline-item">
                                    <h6>Sistema de Lockscreen Admin</h6>
                                    <p class="text-white-stable mb-1">Código OTP de 6 dígitos para desbloquear sessões
                                        inactivas</p>
                                    <small class="timeline-date">2026</small>
                                </div>
                                <div class="timeline-item">
                                    <h6>Auditoria e Log de Acções</h6>
                                    <p class="text-white-stable mb-1">Todas as acções dos admins registadas em tempo
                                        real</p>
                                    <small class="timeline-date">2026</small>
                                </div>
                            </div>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/settings" class="card-link">Ver histórico
                                completo <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <!-- ══ PAGAMENTOS RECENTES ══ -->
                <?php if ($canFinancesView): ?>
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="card stats-card-primary flex-fill">
                            <div class="section-card-header">
                                <h5 class="card-title mb-0"> <i class="bi-cash-coin me-2" style="color:#FF0089"></i>
                                    Pagamentos Recentes
                                </h5>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/finances/payments" class="card-link"
                                    style="font-size:.8rem">Ver todos <i class="bi bi-arrow-right"></i></a>
                            </div>
                            <?php if (empty($recent_pays)): ?>
                            <p class="text-white-stable" style="opacity:.6;font-size:.85rem">Nenhum pagamento registado
                                ainda.</p>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Utilizador</th>
                                            <th>Referência</th>
                                            <th>Plano</th>
                                            <th>Valor</th>
                                            <th>Método</th>
                                            <th>Estado</th>
                                            <th>Data</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_pays as $pay):
                                                $ini   = adm_initials($pay['first_name'], $pay['second_name'] ?? '');
                                                $color = adm_avatar_color($pay['first_name']);
                                                $sc    = pay_status_class($pay['status_payment']);
                                                $sl    = pay_status_label($pay['status_payment']);
                                            ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="adm-avatar-sm" style="background:<?php echo $color; ?>">
                                                        <?php if (!empty($pay['photo_user'])): ?>
                                                        <img src="../assets/comprovantes/uploads/users/<?php echo htmlspecialchars($pay['photo_user']); ?>"
                                                            alt="" onerror="this.style.display='none'" />
                                                        <?php else: echo $ini;
                                                                endif; ?>
                                                    </div>
                                                    <span
                                                        style="font-size:.82rem"><?php echo htmlspecialchars($pay['first_name'] . ' ' . ($pay['second_name'] ?? '')); ?></span>
                                                </div>
                                            </td>
                                            <td><code
                                                    style="font-size:.78rem"><?php echo htmlspecialchars($pay['payment_ref']); ?></code>
                                            </td>
                                            <td style="font-size:.82rem">
                                                <?php echo htmlspecialchars($pay['name_plan']); ?></td>
                                            <td style="font-size:.84rem;font-weight:600">
                                                <?php echo adm_fmt_aoa((float)$pay['amount']); ?></td>
                                            <td style="font-size:.8rem">
                                                <?php echo ucfirst(str_replace('_', ' ', $pay['payment_method'])); ?>
                                            </td>
                                            <td><span class="badge bg-<?php echo $sc; ?>"><?php echo $sl; ?></span></td>
                                            <td style="font-size:.78rem;opacity:.65">
                                                <?php echo adm_fmt_date($pay['creat_payment']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ══ LANÇAMENTOS NAS ÚLTIMAS 24H ══ -->
                <?php if ($canMusicView): ?>
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="card stats-card-primary flex-fill">
                            <div class="section-card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-clock-history me-2" style="color:#FF0089"></i>
                                    Lançamentos nas Últimas 24h
                                </h5>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/releases" class="card-link"
                                    style="font-size:.8rem">
                                    Ver todos <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <?php if (empty($releases_24h)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size:2rem;opacity:.3"></i>
                                <p class="text-white-stable mt-2" style="font-size:.85rem;opacity:.6">
                                    Nenhum lançamento nas últimas 24 horas.
                                </p>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Capa</th>
                                            <th>Título</th>
                                            <th>Tipo</th>
                                            <th>Artista</th>
                                            <th>Estado</th>
                                            <th>Enviado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($releases_24h as $rel):
                                                [$sc, $sl] = rel_status($rel['status_album']);
                                            ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($rel['img_cover'])): ?>
                                                <img src="../assets/comprovantes/uploads/covers/<?php echo htmlspecialchars($rel['img_cover']); ?>"
                                                    alt=""
                                                    style="width:36px;height:36px;border-radius:6px;object-fit:cover"
                                                    onerror="this.style.display='none'" />
                                                <?php else: ?>
                                                <div
                                                    style="width:36px;height:36px;border-radius:6px;background:rgba(255,0,137,.15);display:flex;align-items:center;justify-content:center;color:#FF0089">
                                                    <i class="bi bi-music-note"></i>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-weight:600;font-size:.85rem">
                                                <?php echo htmlspecialchars($rel['title_album']); ?></td>
                                            <td style="font-size:.82rem"><?php echo ucfirst($rel['type_album']); ?></td>
                                            <td style="font-size:.82rem">
                                                <?php echo htmlspecialchars($rel['first_name'] . ' ' . ($rel['second_name'] ?? '')); ?>
                                            </td>
                                            <td><span class="badge bg-<?php echo $sc; ?>"><?php echo $sl; ?></span></td>
                                            <td style="font-size:.78rem;opacity:.65">
                                                <?php echo adm_fmt_date($rel['creat_album']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
            <?php endif; ?>

            <?php if ($canSupportView): ?>
            <!-- ══ PEDIDOS DE SUPORTE NAS ÚLTIMAS 24H ══ -->
            <div class="row">
                <div class="col-12 mb-3">
                    <div class="card stats-card-primary flex-fill">
                        <div class="section-card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-headset me-2" style="color:#FF0089"></i>
                                Pedidos de Suporte nas Últimas 24h
                            </h5>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/support" class="card-link"
                                style="font-size:.8rem">
                                Ver todos <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <?php if (empty($tickets_24h)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-emoji-smile" style="font-size:2rem;opacity:.3;color:#22c55e"></i>
                            <p class="text-white-stable mt-2" style="font-size:.85rem;opacity:.6">
                                Sem pedidos de suporte nas últimas 24 horas.
                            </p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($tickets_24h as $tk):
                                $ticket_name  = trim(($tk['first_name'] ?? '') . ' ' . ($tk['second_name'] ?? ''));
                                $ticket_name  = $ticket_name !== '' ? $ticket_name : trim((string)($tk['name_contact'] ?? ''));
                                $ticket_name  = $ticket_name !== '' ? $ticket_name : 'Visitante';
                                $ticket_email = trim((string)($tk['email_user'] ?? ''));
                                $ticket_email = $ticket_email !== '' ? $ticket_email : trim((string)($tk['email_contact'] ?? ''));
                                $parts        = preg_split('/\s+/', $ticket_name, 2);
                                $ini          = adm_initials($parts[0] ?? 'V', $parts[1] ?? '');
                                $color        = adm_avatar_color($ticket_name);
                                $preview = mb_substr(strip_tags($tk['body']), 0, 120, 'UTF-8');
                                if (mb_strlen(strip_tags($tk['body']), 'UTF-8') > 120) $preview .= '…';
                                $tk_sc = match ($tk['status_ticket']) {
                                    'open'       => 'danger',
                                    'in_progress' => 'warning',
                                    'waiting'    => 'info',
                                    'resolved'   => 'success',
                                    'closed'     => 'secondary',
                                    default      => 'secondary',
                                };
                                $tk_sl = match ($tk['status_ticket']) {
                                    'open'       => 'Aberto',
                                    'in_progress' => 'Em progresso',
                                    'waiting'    => 'Aguardando',
                                    'resolved'   => 'Resolvido',
                                    'closed'     => 'Fechado',
                                    default      => ucfirst($tk['status_ticket']),
                                };
                                $pr_sc = match ($tk['priority'] ?? '') {
                                    'high'   => 'danger',
                                    'normal' => 'warning',
                                    'medium' => 'warning',
                                    'low'    => 'secondary',
                                    default  => 'secondary',
                                };
                                $pr_sl = match ($tk['priority'] ?? '') {
                                    'high'   => 'Alta',
                                    'normal' => 'Média',
                                    'medium' => 'Média',
                                    'low'    => 'Baixa',
                                    default  => '—',
                                };
                            ?>
                        <div class="activity-item d-flex mb-3 pb-3"
                            style="border-bottom:1px solid rgba(255,255,255,.07)">
                            <!-- Avatar do utilizador -->
                            <div class="flex-shrink-0">
                                <div
                                    style="width:42px;height:42px;border-radius:50%;background:<?php echo $color; ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.78rem;color:#fff;overflow:hidden">
                                    <?php if (!empty($tk['photo_user'])): ?>
                                    <img src="../assets/comprovantes/uploads/users/<?php echo htmlspecialchars($tk['photo_user']); ?>"
                                        alt="" style="width:100%;height:100%;object-fit:cover"
                                        onerror="this.style.display='none';this.parentElement.textContent='<?php echo $ini; ?>'" />
                                    <?php else: ?>
                                    <?php echo $ini; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex align-items-start justify-content-between flex-wrap gap-1">
                                    <div>
                                        <h6 class="mb-0" style="font-size:.88rem">
                                            <?php echo htmlspecialchars($tk['subject']); ?></h6>
                                        <small class="text-white-stable" style="opacity:.6">
                                            <?php echo htmlspecialchars($ticket_name); ?>
                                            <?php if ($ticket_email !== ''): ?>
                                            · <?php echo htmlspecialchars($ticket_email); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="d-flex gap-1 flex-shrink-0">
                                        <span class="badge bg-<?php echo $tk_sc; ?>"><?php echo $tk_sl; ?></span>
                                        <?php if ($tk['priority']): ?>
                                        <span class="badge bg-<?php echo $pr_sc; ?>"><?php echo $pr_sl; ?>
                                            prioridade</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-white-stable mb-0 mt-1"
                                    style="font-size:.8rem;opacity:.7;line-height:1.5">
                                    <?php echo htmlspecialchars($preview); ?>
                                </p>
                                <div class="mt-1">
                                    <small class="text-white-stable"
                                        style="opacity:.5"><?php echo adm_fmt_date($tk['creat_ticket']); ?></small>
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/support/<?php echo (int)$tk['id_ticket']; ?>"
                                        class="card-link ms-3" style="font-size:.75rem">
                                        Ver ticket <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
    </div>

    <!-- Floating Action Button -->
    <div class="fab" onclick="showQuickAction()" aria-label="Ações Rápidas">
        <i class="bi bi-plus-lg"></i>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-2">© 2026 Wasom Upfy. Todos os direitos reservados.</p>
                    <a href="<?php echo APP_URL; ?>/page/politicies/terms" class="me-2">Termos de Uso</a>
                    <a href="<?php echo APP_URL; ?>/page/politicies/privacy" class="me-2">Privacidade</a>
                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/support">Suporte</a>
                </div>
            </div>
        </div>
    </footer>

    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="../assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="Carregando" />
            <div class="loader-progress"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="<?php echo APP_URL  ?>/js/lastest.js"></script>
    <script src="<?php echo APP_URL  ?>/js/lastest.min.js"></script>

    <script>
    // ── Mapa — centrado em Angola ──────────────
    function initClientMap() {
        const mapHost = document.getElementById("clientMap");
        if (!mapHost || typeof L === "undefined") return;
        if (mapHost._leaflet_map) return;

        const map = L.map(mapHost).setView([-8.8372, 13.2343], 3);
        const tileUrl = document.body.classList.contains("dark-mode") ?
            "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png" :
            "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png";

        L.tileLayer(tileUrl, {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        mapHost._leaflet_map = map;

        [{
                name: "Luanda, Angola",
                lat: -8.8372,
                lng: 13.2343
            },
            {
                name: "Lisboa, Portugal",
                lat: 38.7223,
                lng: -9.1393
            },
            {
                name: "São Paulo, Brasil",
                lat: -23.5505,
                lng: -46.6333
            },
            {
                name: "Porto, Portugal",
                lat: 41.1579,
                lng: -8.6291
            },
        ].forEach(c => {
            L.marker([c.lat, c.lng]).addTo(map)
                .bindPopup(`<b>${c.name}</b><br>Utilizadores Wasom Upfy`);
        });
    }

    // ── Streams Chart — dados reais da BD ──────
    const streamData = {
        today: {
            labels: <?php echo json_encode($streams_today_labels); ?>,
            streams: <?php echo json_encode($streams_today_data); ?>,
            totalStreams: <?php echo $streams_total_today; ?>,
            newListeners: <?php echo $new_listeners_today; ?>,
            revenue: <?php echo $rev_today; ?>,
        },
        "7days": {
            labels: <?php echo json_encode($streams_7d_labels); ?>,
            streams: <?php echo json_encode($streams_7d_data); ?>,
            totalStreams: <?php echo $streams_total_7d; ?>,
            newListeners: <?php echo $new_listeners_7d; ?>,
            revenue: <?php echo $rev_7d; ?>,
        },
        "30days": {
            labels: <?php echo json_encode($streams_30d_labels); ?>,
            streams: <?php echo json_encode($streams_30d_data); ?>,
            totalStreams: <?php echo $streams_total_30d; ?>,
            newListeners: <?php echo $new_listeners_30d; ?>,
            revenue: <?php echo $rev_30d; ?>,
        },
        month: {
            labels: <?php echo json_encode($streams_month_labels); ?>,
            streams: <?php echo json_encode($streams_month_data); ?>,
            totalStreams: <?php echo $streams_total_month; ?>,
            newListeners: <?php echo $new_listeners_month; ?>,
            revenue: <?php echo $rev_month; ?>,
        },
    };

    const streamsCanvas = document.getElementById("streamsChart");
    let streamsChart = null;
    const ctx = streamsCanvas ? streamsCanvas.getContext("2d") : null;

    function updateChart(period) {
        if (!ctx || !streamData[period]) return;

        const data = streamData[period];
        const dark = document.body.classList.contains("dark-mode");
        const lineColor = dark ? "#ff0088" : "#ff4d94";
        if (streamsChart) streamsChart.destroy();
        streamsChart = new Chart(ctx, {
            type: "line",
            data: {
                labels: data.labels,
                datasets: [{
                    label: "Streams",
                    data: data.streams,
                    borderColor: lineColor,
                    backgroundColor: lineColor + "33",
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: dark ? "#333" : "#fff",
                        titleColor: dark ? "#fff" : "#333",
                        bodyColor: dark ? "#fff" : "#333",
                    },
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: dark ? "#fff" : "#333"
                        }
                    },
                    y: {
                        grid: {
                            color: dark ? "#444" : "#e0e0e0"
                        },
                        ticks: {
                            color: dark ? "#fff" : "#333"
                        }
                    },
                },
            },
        });
        const totalStreamsEl = document.getElementById("totalStreams");
        const newListenersEl = document.getElementById("newListeners");
        // Formatar receita em AOA
        const rev = data.revenue;
        let revFmt;
        if (rev >= 1000000) revFmt = 'Kz ' + (rev / 1000000).toFixed(1).replace('.0', '') + 'M';
        else if (rev >= 1000) revFmt = 'Kz ' + (rev / 1000).toFixed(1).replace('.0', '') + 'mil';
        else revFmt = 'Kz ' + rev.toLocaleString("pt-AO", {
            minimumFractionDigits: 2
        });
        const revenueEl = document.getElementById("revenue");
        if (totalStreamsEl) totalStreamsEl.textContent = data.totalStreams.toLocaleString("pt-AO");
        if (newListenersEl) newListenersEl.textContent = data.newListeners.toLocaleString("pt-AO");
        if (revenueEl) revenueEl.textContent = revFmt;
    }

    if (ctx) {
        updateChart("7days");

    document.querySelectorAll(".dropdown-menu a[data-period]").forEach(item => {
        item.addEventListener("click", e => {
            e.preventDefault();
            updateChart(e.target.getAttribute("data-period"));
            e.target.closest(".dropdown").querySelector(".dropdown-toggle").textContent = e.target
                .textContent;
        });
    });
        document.querySelector('[onclick="toggleDarkMode()"]').addEventListener("click", () => {
            const cur = (document.querySelector('[aria-label="Selecionar PerÃ­odo"]')?.textContent || "").trim();
            const map = {
            "Hoje": "today",
            "Últimos 7 dias": "7days",
            "Últimos 30 dias": "30days",
            "Este mês": "month"
        };
        updateChart(map[cur] || "7days");
    });
    }

    // ── Relógio em tempo real ───────────────────
    function updateClock() {
        const now = new Date();
        const days = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        const t = document.getElementById('live-time');
        const d = document.getElementById('live-date');
        if (t) t.textContent = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2,
            '0');
        if (d) d.textContent = days[now.getDay()] + ' · ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now
            .getFullYear();
    }

    // Chamar dentro do DOMContentLoaded para garantir que os elementos existem
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            updateClock();
            initClientMap();
            setInterval(updateClock, 30000);
        });
    } else {
        updateClock();
        initClientMap();
        setInterval(updateClock, 30000);
    }

    // ── Gráfico donut de planos ─────────────────
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('plansDonut');
        if (!canvas) return;
        const labels = <?php echo $plans_labels_json; ?>;
        const data = <?php echo $plans_data_json; ?>;
        if (!data.some(v => v > 0)) return;
        new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: ['#FF0089', '#3b82f6', '#22c55e', '#f97316', '#8b5cf6']
                        .slice(0, labels.length),
                    borderWidth: 0,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' utilizadores'
                        }
                    }
                }
            }
        });
    });
    </script>
</body>

</html>
