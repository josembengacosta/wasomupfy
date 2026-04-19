<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Utilizadores Online
// Arquivo: wu-panel/pages/analytics/online-users.php
// Rota:    wu-panel/analytics/online-users
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// ══════════════════════════════════════════════
// HANDLER AJAX
// ══════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    if (ob_get_level()) ob_clean();

    if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        echo json_encode(['ok' => false, 'message' => 'Sessão expirada.']);
        exit;
    }

    $action = trim($_POST['ajax_action']);

    // ── Enviar broadcast ──
    if ($action === 'broadcast') {
        requirePermission($admin_id, 'users.edit');
        $type     = in_array($_POST['type'] ?? '', ['info', 'warning', 'success', 'event']) ? $_POST['type'] : 'info';
        $audience = in_array($_POST['audience'] ?? '', ['all', 'selected', 'role', 'country']) ? $_POST['audience'] : 'all';
        $aud_val  = trim($_POST['audience_value'] ?? '');
        $message  = trim($_POST['message'] ?? '');

        if (empty($message)) {
            echo json_encode(['ok' => false, 'message' => 'A mensagem é obrigatória.']);
            exit;
        }

        // Determinar destinatários
        $where_b = '1';
        $params_b = [];
        if ($audience === 'role') {
            // sem campo de role em _users — filtrar por plano ou status
            $where_b = 'u.status_user = ?';
            $params_b[] = 'active';
        } elseif ($audience === 'country') {
            $where_b = 'u.country_user = ?';
            $params_b[] = $aud_val;
        } elseif ($audience === 'selected') {
            $ids = array_map('intval', explode(',', $_POST['selected_ids'] ?? ''));
            $ids = array_filter($ids);
            if (empty($ids)) {
                echo json_encode(['ok' => false, 'message' => 'Nenhum utilizador seleccionado.']);
                exit;
            }
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $where_b = "u.id_users IN ($ph)";
            $params_b = $ids;
        }

        $recip_stmt = $db->prepare("SELECT id_users FROM _users u WHERE $where_b");
        $recip_stmt->execute($params_b);
        $recipients = $recip_stmt->fetchAll(PDO::FETCH_COLUMN);
        $count = count($recipients);

        if ($count === 0) {
            echo json_encode(['ok' => false, 'message' => 'Nenhum destinatário encontrado.']);
            exit;
        }

        try {
            $db->beginTransaction();

            // INSERT _broadcast
            $db->prepare("
                INSERT INTO _broadcast
                    (id_employees, type, audience, audience_value, message, recipients_count)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$admin_id, $type, $audience, $aud_val ?: null, $message, $count]);
            $bc_id = (int)$db->lastInsertId();

            // INSERT _notification para cada destinatário
            $ins = $db->prepare("
                INSERT INTO _notification
                    (id_users, id_employees, type, title, body, is_broadcast)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            foreach ($recipients as $uid) {
                $ins->execute([$uid, $admin_id, $type, 'Anúncio de Administrador', $message]);
            }

            // INSERT _broadcast_receipt
            $ins_r = $db->prepare("INSERT IGNORE INTO _broadcast_receipt (id_broadcast, id_users) VALUES (?, ?)");
            foreach ($recipients as $uid) {
                $ins_r->execute([$bc_id, $uid]);
            }

            $db->commit();
            logAudit($admin_id, null, 'broadcast.sent', '_broadcast', $bc_id, null, json_encode(['count' => $count, 'type' => $type]));
            echo json_encode(['ok' => true, 'message' => "Anúncio enviado para $count utilizador(es).", 'count' => $count]);
        } catch (Exception $e) {
            $db->rollBack();
            error_log('[BROADCAST] ' . $e->getMessage());
            echo json_encode(['ok' => false, 'message' => 'Erro ao enviar anúncio.']);
        }
        exit;
    }

    // ── Enviar mensagem directa ──
    if ($action === 'send_message') {
        requirePermission($admin_id, 'users.edit');
        $to_user = (int)($_POST['to_user'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $body    = trim($_POST['body'] ?? '');
        $link_notification =  APP_URL . '/' .  APP_URL_PANEL . '/' . 'page/notifications';

        if (!$to_user || empty($body)) {
            echo json_encode(['ok' => false, 'message' => 'Destinatário e mensagem são obrigatórios.']);
            exit;
        }
        try {
            $db->prepare("
INSERT INTO _message (from_employee, to_user, subject, body)
VALUES (?, ?, ?, ?)
")->execute([$admin_id, $to_user, $subject ?: 'Mensagem da Administração', $body]);

            // Notificação
            $db->prepare("
INSERT INTO _notification (id_users, id_employees, type, title, body, action_url)
VALUES (?, ?, 'info', 'Nova mensagem do Admin', ?, '$link_notification')
")->execute([$to_user, $admin_id, substr($body, 0, 120)]);

            echo json_encode(['ok' => true, 'message' => 'Mensagem enviada com sucesso.']);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => 'Erro ao enviar mensagem.']);
        }
        exit;
    }

    // ── Forçar logout de utilizador ──
    if ($action === 'force_logout') {
        requirePermission($admin_id, 'users.edit');
        $uid = (int)($_POST['user_id'] ?? 0);
        if (!$uid) {
            echo json_encode(['ok' => false, 'message' => 'ID inválido.']);
            exit;
        }
        try {
            $db->beginTransaction();
            $db->prepare("
UPDATE _users_sessions
SET is_active = 0,
last_activity = NOW()
WHERE id_users = ?
")->execute([$uid]);
            $db->prepare("
UPDATE _users_security
SET remember_token = NULL,
modif_security = NOW()
WHERE id_users = ?
")->execute([$uid]);
            $db->prepare("
UPDATE _user_presence
SET online_status = 'offline',
last_activity = NOW(),
last_activity_type = 'forced_logout',
last_page = '/wu-panel/analytics/online-users',
session_duration = session_duration + GREATEST(0, TIMESTAMPDIFF(SECOND, last_activity, NOW())),
modif_presence = NOW()
WHERE id_users = ?
")->execute([$uid]);
            $db->commit();
            logAudit($admin_id, $uid, 'user.force_logout', '_users', $uid);
            echo json_encode(['ok' => true, 'message' => 'Utilizador desligado com sucesso.']);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['ok' => false, 'message' => 'Erro ao desligar.']);
        }
        exit;
    }

    // ── API de refresh (retorna JSON com lista actualizada) ──
    if ($action === 'refresh') {
        $f_status = trim($_POST['status'] ?? '');
        $f_plan = (int)($_POST['plan'] ?? 0);
        $where = ["up.online_status != 'offline'", "up.last_activity >= DATE_SUB(NOW(), INTERVAL 60 MINUTE)"];
        $params = [];
        if ($f_status && in_array($f_status, ['online', 'away', 'busy', 'invisible'])) {
            $where[] = 'up.online_status = ?';
            $params[] = $f_status;
        }
        if ($f_plan) {
            $where[] = 'u.plan_selected = ?';
            $params[] = $f_plan;
        }
        $sql_where = 'WHERE ' . implode(' AND ', $where);
        $stmt = $db->prepare("
SELECT up.id_users, up.online_status, up.last_activity,
up.last_activity_type, up.last_page, up.device_type,
up.browser, up.country_code, up.country_name, up.city,
up.session_duration, up.ip_address, up.session_start,
u.first_name, u.second_name, u.photo_user, u.email_user,
u.status_user, u.plan_selected,
p.name_plan, p.slug_plan
FROM _user_presence up
JOIN _users u ON u.id_users = up.id_users
LEFT JOIN _plans p ON p.id_plan = u.plan_selected
$sql_where
ORDER BY up.last_activity DESC
LIMIT 200
");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        // Contar por status
        $counts = $db->query("
SELECT online_status, COUNT(*) AS cnt
FROM _user_presence
WHERE online_status != 'offline' AND last_activity >= DATE_SUB(NOW(),INTERVAL 60 MINUTE)
GROUP BY online_status
")->fetchAll(PDO::FETCH_KEY_PAIR);
        echo json_encode(['ok' => true, 'users' => $rows, 'counts' => $counts, 'ts' => time()]);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Acção desconhecida.']);
    exit;
}

// ══════════════════════════════════════════════
// EXPORT CSV
// ══════════════════════════════════════════════
if (($_GET['export'] ?? '') === 'csv') {
    requirePermission($admin_id, 'analytics.view');
    $stmt = $db->query("
SELECT up.id_users, u.first_name, u.second_name, u.email_user,
up.online_status, up.last_activity, up.last_activity_type,
up.last_page, up.device_type, up.browser, up.ip_address,
up.country_name, up.city, up.session_duration, up.session_start,
p.name_plan
FROM _user_presence up
JOIN _users u ON u.id_users = up.id_users
LEFT JOIN _plans p ON p.id_plan = u.plan_selected
WHERE up.online_status != 'offline' AND up.last_activity >= DATE_SUB(NOW(),INTERVAL 60 MINUTE)
ORDER BY up.last_activity DESC
");
    $rows = $stmt->fetchAll();
    $csvExcelEncode = static function (string $value): string {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($value, 'UTF-16LE', 'UTF-8');
        }

        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'UTF-16LE//IGNORE', $value);
            if ($converted !== false) {
                return $converted;
            }
        }

        return $value;
    };
    $csvExcelLine = static function (array $fields) use ($csvExcelEncode): string {
        $escaped = array_map(static function ($value): string {
            $value = (string)($value ?? '');
            $value = str_replace('"', '""', $value);
            return '"' . $value . '"';
        }, $fields);

        return $csvExcelEncode(implode(';', $escaped) . "\r\n");
    };

    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: text/csv; charset=UTF-16LE');
    header('Content-Disposition: attachment; filename="utilizadores_online_' . date('Y-m-d_His') . '.csv"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xFF\xFE");
    fwrite($out, $csvExcelEncode("sep=;\r\n"));
    fwrite($out, $csvExcelLine([
        'ID',
        'Nome',
        'E-mail',
        'Estado',
        "\u{00DA}ltima Actividade",
        'Tipo Actividade',
        "\u{00DA}ltima P\u{00E1}gina",
        'Dispositivo',
        'Browser',
        'IP',
        'Pa' . "\u{00ED}" . 's',
        'Cidade',
        'Dura' . "\u{00E7}" . "\u{00E3}" . 'o Sess' . "\u{00E3}" . 'o (s)',
        'In' . "\u{00ED}" . 'cio Sess' . "\u{00E3}" . 'o',
        'Plano',
    ]));
    foreach ($rows as $r) {
        fwrite($out, $csvExcelLine([
            $r['id_users'],
            trim($r['first_name'] . ' ' . ($r['second_name'] ?? '')),
            $r['email_user'],
            $r['online_status'],
            $r['last_activity'],
            $r['last_activity_type'],
            $r['last_page'],
            $r['device_type'],
            $r['browser'],
            $r['ip_address'],
            $r['country_name'],
            $r['city'],
            $r['session_duration'],
            $r['session_start'],
            $r['name_plan'],
        ]));
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="utilizadores_online_' . date('Y-m-d_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputs($out, "sep=;\r\n");
    fputcsv($out, [
        'ID',
        'Nome',
        'E-mail',
        'Estado',
        'Última Actividade',
        'Tipo Actividade',
        'Última Página',
        'Dispositivo',
        'Browser',
        'IP',
        'País',
        'Cidade',
        'Duração Sessão (s)',
        'Início Sessão',
        'Plano'
    ], ';');
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id_users'],
            trim($r['first_name'] . ' ' . ($r['second_name'] ?? '')),
            $r['email_user'],
            $r['online_status'],
            $r['last_activity'],
            $r['last_activity_type'],
            $r['last_page'],
            $r['device_type'],
            $r['browser'],
            $r['ip_address'],
            $r['country_name'],
            $r['city'],
            $r['session_duration'],
            $r['session_start'],
            $r['name_plan'],
        ], ';');
    }
    fclose($out);
    exit;
}

// ══════════════════════════════════════════════
// DADOS DA PÁGINA
// ══════════════════════════════════════════════

// Stats
$stats = $db->query("
SELECT
COUNT(*) AS total_online,
SUM(online_status='online') AS cnt_online,
SUM(online_status='away') AS cnt_away,
SUM(online_status='busy') AS cnt_busy,
SUM(online_status='invisible') AS cnt_invisible
FROM _user_presence
WHERE online_status != 'offline'
AND last_activity >= DATE_SUB(NOW(), INTERVAL 60 MINUTE)
")->fetch();

// Filtros
$f_status = trim($_GET['status'] ?? '');
$f_plan = (int)($_GET['plan'] ?? 0);

$where = ["up.online_status != 'offline'", "up.last_activity >= DATE_SUB(NOW(), INTERVAL 60 MINUTE)"];
$params = [];
if ($f_status && in_array($f_status, ['online', 'away', 'busy', 'invisible'])) {
    $where[] = 'up.online_status = ?';
    $params[] = $f_status;
}
if ($f_plan) {
    $where[] = 'u.plan_selected = ?';
    $params[] = $f_plan;
}
$sql_where = 'WHERE ' . implode(' AND ', $where);

$stmt = $db->prepare("
SELECT up.id_users, up.online_status, up.last_activity,
up.last_activity_type, up.last_page, up.device_type,
up.browser, up.country_code, up.country_name, up.city,
up.session_duration, up.ip_address, up.session_start,
u.first_name, u.second_name, u.photo_user, u.email_user,
u.status_user, u.plan_selected,
p.name_plan, p.slug_plan
FROM _user_presence up
JOIN _users u ON u.id_users = up.id_users
LEFT JOIN _plans p ON p.id_plan = u.plan_selected
$sql_where
ORDER BY up.last_activity DESC
LIMIT 200
");
$stmt->execute($params);
$users_online = $stmt->fetchAll();

// Planos para filtro
$plans_list = $db->query("SELECT id_plan, name_plan FROM _plans WHERE is_active=1 ORDER BY display_order")->fetchAll();

// Top actividades
$top_activities = $db->query("
SELECT last_activity_type, COUNT(*) AS cnt
FROM _user_presence
WHERE online_status != 'offline' AND last_activity >= DATE_SUB(NOW(),INTERVAL 60 MINUTE)
AND last_activity_type IS NOT NULL
GROUP BY last_activity_type ORDER BY cnt DESC LIMIT 5
")->fetchAll();

// Distribuição por país
$top_countries = $db->query("
SELECT country_name, COUNT(*) AS cnt
FROM _user_presence
WHERE online_status != 'offline' AND last_activity >= DATE_SUB(NOW(),INTERVAL 60 MINUTE)
AND country_name IS NOT NULL
GROUP BY country_name ORDER BY cnt DESC LIMIT 5
")->fetchAll();

// ── Helpers ──
function ou_status_dot(string $s): string
{
    return match ($s) {
        'online' => '#22c55e',
        'away' => '#f59e0b',
        'busy' => '#ef4444',
        'invisible' => '#9ca3af',
        default => '#6b7280',
    };
}
function ou_status_label(string $s): string
{
    return match ($s) {
        'online' => 'Online',
        'away' => 'Ausente',
        'busy' => 'Ocupado',
        'invisible' => 'Invisível',
        default => ucfirst($s),
    };
}
function ou_device_icon(string $d): string
{
    return match (strtolower($d)) {
        'desktop' => 'bi-pc-display',
        'mobile' => 'bi-phone',
        'tablet' => 'bi-tablet',
        default => 'bi-display',
    };
}
function ou_time_ago(?string $dt): string
{
    if (!$dt) return '—';
    $diff = time() - strtotime($dt);
    if ($diff < 60) return 'agora mesmo';
    if ($diff < 3600) return floor($diff / 60) . ' min atrás';
    if ($diff < 86400)
        return floor($diff / 3600) . 'h atrás';
    return date('d/m H:i', strtotime($dt));
}
function ou_fmt_dur(int $s): string
{
    if ($s < 60) return $s . 's';
    if ($s < 3600) return floor($s / 60) . 'm ' . ($s % 60) . 's';
    return
        floor($s / 3600) . 'h ' . floor(($s % 3600) / 60) . 'm';
}
function ou_initials(string $a, string $b = ''): string
{
    return mb_strtoupper(mb_substr(trim($a), 0, 1, 'UTF-8')) . mb_strtoupper(mb_substr(trim($b), 0, 1, 'UTF-8'));
}
function ou_color(string $name): string
{
    $colors = [
        '#FF0089',
        '#f97316',
        '#8b5cf6',
        '#06b6d4',
        '#22c55e',
        '#eab308',
        '#ec4899',
        '#3b82f6',
        '#14b8a6'
    ];
    return $colors[abs(crc32($name)) % count($colors)];
}
function
ou_activity_label(?string $t): string
{
    if (!$t) return '—';
    return match ($t) {
        'listening' => '🎵 Ouvindo',
        'uploading' => '📤 Uploading',
        'dashboard' => '📊 Dashboard',
        'releases' => '🎵 Lançamentos',
        'finances' => '💰 Finanças',
        'profile' => '👤 Perfil',
        'artists' => '🎤 Artistas',
        'settings' => '⚙️ Definições',
        default => htmlspecialchars($t),
    };
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
    <title>Utilizadores Online — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        /* ── Stats ── */
        .ou-stat {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .ou-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .ou-stat-num {
            font-size: 1.4rem;
            font-weight: 800;
            line-height: 1;
        }

        .ou-stat-lbl {
            font-size: .72rem;
            opacity: .55;
            margin-top: 2px;
        }

        /* ── User card ── */
        .ou-user-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 14px 16px;
            position: relative;
            overflow: hidden;
            transition: all .2s;
            cursor: default;
        }

        .ou-user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, .08);
        }

        .ou-user-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 3px;
            height: 100%;
        }

        .ou-user-card.status-online::before {
            background: #22c55e;
        }

        .ou-user-card.status-away::before {
            background: #f59e0b;
        }

        .ou-user-card.status-busy::before {
            background: #ef4444;
        }

        .ou-user-card.status-invisible::before {
            background: #9ca3af;
        }

        .ou-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 0, 137, .2);
            flex-shrink: 0;
        }

        .ou-avatar-ini {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: .78rem;
            color: #fff;
            flex-shrink: 0;
        }

        .ou-status-dot {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 11px;
            height: 11px;
            border-radius: 50%;
            border: 2px solid var(--card-bg, #fff);
        }

        .ou-activity-tag {
            font-size: .68rem;
            padding: 2px 7px;
            border-radius: 6px;
            background: rgba(255, 0, 137, .1);
            color: #FF0089;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
        }

        /* ── Table view ── */
        #ou-table th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 700;
            white-space: nowrap;
        }

        #ou-table td {
            font-size: .8rem;
            vertical-align: middle;
        }

        /* ── Auto-refresh badge ── */
        .refresh-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: .74rem;
            padding: 5px 10px;
            border-radius: 8px;
            background: rgba(34, 197, 94, .1);
            color: #22c55e;
        }

        .refresh-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #22c55e;
            animation: rdot 2s infinite;
        }

        @keyframes rdot {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: .3
            }
        }

        /* ── View toggle ── */
        .view-btn {
            padding: 5px 10px;
            border-radius: 8px;
            font-size: .78rem;
            cursor: pointer;
            transition: all .2s;
        }

        .view-btn.active {
            background: #FF0089;
            color: #fff;
            border-color: #FF0089;
        }

        /* ── Activities sidebar ── */
        .activity-feed-item {
            display: flex;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
            font-size: .78rem;
        }

        .activity-feed-item:last-child {
            border-bottom: none;
        }

        /* ── Empty ── */
        .ou-empty {
            text-align: center;
            padding: 60px 24px;
            opacity: .35;
        }

        .ou-empty i {
            font-size: 3rem;
            display: block;
            margin-bottom: 12px;
        }

        /* ── Responsive grid ── */
        #users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 12px;
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

                <!-- Header -->
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1"><i class="bi bi-wifi me-2"></i>Utilizadores Online</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item active text-white-stable">Online Agora</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex align-items-center gap-2 flex-wrap">
                        <span class="refresh-badge" id="refresh-badge">
                            <span class="refresh-dot"></span>
                            Actualiza em <span id="countdown">60</span>s
                        </span>
                        <?php if (hasPermission($admin_id, 'analytics.view')): ?>
                            <a href="?export=csv" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-download me-1"></i>CSV
                            </a>
                        <?php endif; ?>
                        <?php if (hasPermission($admin_id, 'users.edit')): ?>
                            <button type="button" class="btn btn-sm text-white"
                                style="background:#FF0089;border-color:#FF0089" onclick="openBroadcast()">
                                <i class="bi bi-broadcast me-1"></i> Anúncio
                            </button>
                        <?php endif; ?>
                        <!-- Toggle de vista -->
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-secondary view-btn active" id="btn-grid"
                                onclick="setView('grid')">
                                <i class="bi bi-grid-3x3-gap"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary view-btn" id="btn-table"
                                onclick="setView('table')">
                                <i class="bi bi-table"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <?php
                    $ou_cards = [
                        [(int)$stats['total_online'], 'Total Online',  '#FF0089', 'bi-people-fill'],
                        [(int)$stats['cnt_online'],   'Activos',       '#22c55e', 'bi-circle-fill'],
                        [(int)$stats['cnt_away'],     'Ausentes',      '#f59e0b', 'bi-moon-fill'],
                        [(int)$stats['cnt_busy'],     'Ocupados',      '#ef4444', 'bi-slash-circle-fill'],
                        [(int)$stats['cnt_invisible'], 'Invisíveis',    '#9ca3af', 'bi-eye-slash-fill'],
                    ];
                    foreach ($ou_cards as [$val, $lbl, $color, $icon]): ?>
                        <div class="col-6 col-md-<?php echo count($ou_cards) <= 4 ? '3' : '2'; ?>">
                            <div class="ou-stat">
                                <div class="ou-stat-icon" style="background:<?php echo $color; ?>18">
                                    <i class="bi <?php echo $icon; ?>" style="color:<?php echo $color; ?>"></i>
                                </div>
                                <div>
                                    <div class="ou-stat-num" id="stat-<?php echo strtolower($lbl); ?>">
                                        <?php echo $val; ?>
                                    </div>
                                    <div class="ou-stat-lbl"><?php echo $lbl; ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filtros de status -->
                <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                    <span style="font-size:.78rem;opacity:.5;font-weight:600">Filtrar:</span>
                    <?php
                    $status_opts = [''  => 'Todos', 'online' => 'Online', 'away' => 'Ausentes', 'busy' => 'Ocupados', 'invisible' => 'Invisíveis'];
                    foreach ($status_opts as $v => $l): ?>
                        <a href="?status=<?php echo $v; ?>&plan=<?php echo $f_plan; ?>"
                            class="badge <?php echo $f_status === $v ? 'text-white' : ''; ?>"
                            style="font-size:.74rem;padding:6px 12px;border-radius:8px;text-decoration:none;
                              <?php echo $f_status === $v ? 'background:#FF0089;' : 'background:var(--border-color,#e8e8f0);color:inherit;'; ?>">
                            <?php echo $l; ?>
                        </a>
                    <?php endforeach; ?>
                    <span style="flex:1"></span>
                    <!-- Filtro por plano -->
                    <select class="form-select form-select-sm" style="max-width:160px"
                        onchange="window.location='?status=<?php echo $f_status; ?>&plan='+this.value">
                        <option value="0">Todos os planos</option>
                        <?php foreach ($plans_list as $pl): ?>
                            <option value="<?php echo $pl['id_plan']; ?>"
                                <?php echo $f_plan === $pl['id_plan'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pl['name_plan']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row g-3">
                    <!-- ── Lista principal ── -->
                    <div class="col-lg-9">

                        <!-- Grid View -->
                        <div id="view-grid">
                            <?php if (empty($users_online)): ?>
                                <div class="ou-empty">
                                    <i class="bi bi-moon-stars"></i>
                                    <p class="mb-0">Nenhum utilizador online neste momento.</p>
                                </div>
                            <?php else: ?>
                                <div id="users-grid">
                                    <?php foreach ($users_online as $u):
                                        $fn  = trim($u['first_name'] . ' ' . ($u['second_name'] ?? ''));
                                        $ini = ou_initials($u['first_name'], $u['second_name'] ?? '');
                                        $clr = ou_color($fn);
                                        $dot = ou_status_dot($u['online_status']);
                                    ?>
                                        <div class="ou-user-card status-<?php echo htmlspecialchars($u['online_status']); ?>"
                                            data-user-id="<?php echo $u['id_users']; ?>">
                                            <div class="d-flex align-items-start gap-3">
                                                <!-- Avatar -->
                                                <div style="position:relative;flex-shrink:0">
                                                    <?php if (!empty($u['photo_user'])): ?>
                                                        <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($u['photo_user']); ?>"
                                                            class="ou-avatar" alt=""
                                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                                                        <div class="ou-avatar-ini"
                                                            style="background:<?php echo $clr; ?>;display:none">
                                                            <?php echo $ini; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="ou-avatar-ini" style="background:<?php echo $clr; ?>">
                                                            <?php echo $ini; ?></div>
                                                    <?php endif; ?>
                                                    <div class="ou-status-dot" style="background:<?php echo $dot; ?>"></div>
                                                </div>
                                                <!-- Info -->
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="d-flex align-items-center gap-1 mb-1">
                                                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $u['id_users']; ?>"
                                                            style="font-weight:700;font-size:.88rem;text-decoration:none;color:inherit;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                                            <?php echo htmlspecialchars($fn); ?>
                                                        </a>
                                                    </div>
                                                    <div
                                                        style="font-size:.73rem;opacity:.45;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                                        <?php echo htmlspecialchars($u['email_user']); ?>
                                                    </div>
                                                    <?php if (!empty($u['name_plan'])): ?>
                                                        <span class="badge"
                                                            style="background:rgba(255,0,137,.1);color:#FF0089;font-size:.62rem;margin-top:2px">
                                                            <?php echo htmlspecialchars($u['name_plan']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <!-- Acções -->
                                                <div class="dropdown flex-shrink-0">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button"
                                                        data-bs-toggle="dropdown" data-bs-reference="toggle">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item"
                                                                href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $u['id_users']; ?>">
                                                                <i class="bi bi-eye text-info"></i> Ver Perfil
                                                            </a></li>
                                                        <?php if (hasPermission($admin_id, 'users.edit')): ?>
                                                            <li><a class="dropdown-item" href="#"
                                                                    onclick="openMessage(<?php echo $u['id_users']; ?>,'<?php echo htmlspecialchars($fn); ?>');return false">
                                                                    <i class="bi bi-chat-dots text-primary"></i> Enviar Mensagem
                                                                </a></li>
                                                            <li>
                                                                <hr class="dropdown-divider my-1">
                                                            </li>
                                                            <li><a class="dropdown-item text-warning" href="#"
                                                                    onclick="forceLogout(<?php echo $u['id_users']; ?>,'<?php echo htmlspecialchars($fn); ?>');return false">
                                                                    <i class="bi bi-power text-warning"></i> Forçar Logout
                                                                </a></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                            <!-- Linha de actividade -->
                                            <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                                                <?php if (!empty($u['last_activity_type'])): ?>
                                                    <span
                                                        class="ou-activity-tag"><?php echo ou_activity_label($u['last_activity_type']); ?></span>
                                                <?php endif; ?>
                                                <span style="font-size:.7rem;opacity:.4;margin-left:auto">
                                                    <?php echo ou_time_ago($u['last_activity']); ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($u['last_page'])): ?>
                                                <div style="font-size:.7rem;opacity:.35;margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                                    title="<?php echo htmlspecialchars($u['last_page']); ?>">
                                                    <i class="bi bi-link-45deg"></i>
                                                    <?php echo htmlspecialchars($u['last_page']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <!-- Session info -->
                                            <div class="d-flex gap-3 mt-2" style="font-size:.7rem;opacity:.4">
                                                <?php if (!empty($u['device_type'])): ?>
                                                    <span><i
                                                            class="bi <?php echo ou_device_icon($u['device_type']); ?> me-1"></i><?php echo ucfirst($u['device_type']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($u['country_code'])): ?>
                                                    <span><i
                                                            class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($u['country_code']); ?><?php echo $u['city'] ? ' — ' . htmlspecialchars($u['city']) : ''; ?></span>
                                                <?php endif; ?>
                                                <?php if ($u['session_duration'] > 0): ?>
                                                    <span><i
                                                            class="bi bi-clock me-1"></i><?php echo ou_fmt_dur((int)$u['session_duration']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Table View (oculto por padrão) -->
                        <div id="view-table" style="display:none">
                            <div class="card p-0" style="border-radius:14px;overflow:hidden">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="ou-table">
                                        <thead>
                                            <tr>
                                                <th style="width:42px">Foto</th>
                                                <th>Nome</th>
                                                <th>Estado</th>
                                                <th>Actividade</th>
                                                <th>Dispositivo</th>
                                                <th>Localização</th>
                                                <th>Sessão</th>
                                                <th>Plano</th>
                                                <th style="width:50px;text-align:center">Acções</th>
                                            </tr>
                                        </thead>
                                        <tbody id="ou-tbody">
                                            <?php if (empty($users_online)): ?>
                                                <tr>
                                                    <td colspan="9">
                                                        <div class="ou-empty" style="padding:32px">
                                                            <i class="bi bi-moon-stars"
                                                                style="font-size:2rem;display:block;margin-bottom:8px"></i>
                                                            Nenhum utilizador online.
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($users_online as $u):
                                                    $fn  = trim($u['first_name'] . ' ' . ($u['second_name'] ?? ''));
                                                    $ini = ou_initials($u['first_name'], $u['second_name'] ?? '');
                                                    $clr = ou_color($fn);
                                                    $dot = ou_status_dot($u['online_status']);
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <?php if (!empty($u['photo_user'])): ?>
                                                                <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($u['photo_user']); ?>"
                                                                    style="width:34px;height:34px;border-radius:50%;object-fit:cover"
                                                                    alt=""
                                                                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                                                                <div
                                                                    style="width:34px;height:34px;border-radius:50%;background:<?php echo $clr; ?>;display:none;align-items:center;justify-content:center;font-weight:700;font-size:.65rem;color:#fff">
                                                                    <?php echo $ini; ?></div>
                                                            <?php else: ?>
                                                                <div
                                                                    style="width:34px;height:34px;border-radius:50%;background:<?php echo $clr; ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.65rem;color:#fff">
                                                                    <?php echo $ini; ?></div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $u['id_users']; ?>"
                                                                style="font-weight:600;font-size:.83rem;text-decoration:none;color:inherit">
                                                                <?php echo htmlspecialchars($fn); ?>
                                                            </a>
                                                            <div style="font-size:.71rem;opacity:.4">
                                                                <?php echo htmlspecialchars($u['email_user']); ?></div>
                                                        </td>
                                                        <td>
                                                            <span
                                                                style="display:inline-flex;align-items:center;gap:5px;font-size:.78rem">
                                                                <span
                                                                    style="width:8px;height:8px;border-radius:50%;background:<?php echo $dot; ?>;flex-shrink:0"></span>
                                                                <?php echo ou_status_label($u['online_status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($u['last_activity_type'])): ?>
                                                                <span
                                                                    class="ou-activity-tag"><?php echo ou_activity_label($u['last_activity_type']); ?></span>
                                                                <?php else: ?>—<?php endif; ?>
                                                                <div style="font-size:.7rem;opacity:.4;margin-top:2px">
                                                                    <?php echo ou_time_ago($u['last_activity']); ?></div>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($u['device_type'])): ?>
                                                                <i class="bi <?php echo ou_device_icon($u['device_type']); ?> me-1"
                                                                    style="opacity:.6"></i>
                                                                <span
                                                                    style="font-size:.78rem"><?php echo ucfirst($u['device_type']); ?></span>
                                                                <?php else: ?>—<?php endif; ?>
                                                                <?php if (!empty($u['browser'])): ?>
                                                                    <div style="font-size:.7rem;opacity:.4">
                                                                        <?php echo htmlspecialchars(ucfirst($u['browser'])); ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                        </td>
                                                        <td style="font-size:.78rem">
                                                            <?php echo htmlspecialchars($u['country_code'] ?? '—'); ?>
                                                            <?php if (!empty($u['city'])): ?>
                                                                <div style="font-size:.7rem;opacity:.4">
                                                                    <?php echo htmlspecialchars($u['city']); ?></div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td style="font-size:.78rem">
                                                            <?php echo $u['session_duration'] > 0 ? ou_fmt_dur((int)$u['session_duration']) : '—'; ?>
                                                            <?php if (!empty($u['session_start'])): ?>
                                                                <div style="font-size:.7rem;opacity:.4">
                                                                    <?php echo date('H:i', strtotime($u['session_start'])); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($u['name_plan'])): ?>
                                                                <span class="badge"
                                                                    style="background:rgba(255,0,137,.1);color:#FF0089;font-size:.63rem">
                                                                    <?php echo htmlspecialchars($u['name_plan']); ?>
                                                                </span>
                                                                <?php else: ?>—<?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="dropdown">
                                                                <button class="btn btn-sm btn-outline-secondary" type="button"
                                                                    data-bs-toggle="dropdown" data-bs-reference="toggle">
                                                                    <i class="bi bi-three-dots-vertical"></i>
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li><a class="dropdown-item"
                                                                            href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $u['id_users']; ?>">
                                                                            <i class="bi bi-eye text-info"></i> Ver Perfil
                                                                        </a></li>
                                                                    <?php if (hasPermission($admin_id, 'users.edit')): ?>
                                                                        <li><a class="dropdown-item" href="#"
                                                                                onclick="openMessage(<?php echo $u['id_users']; ?>,'<?php echo htmlspecialchars($fn); ?>');return false">
                                                                                <i class="bi bi-chat-dots text-primary"></i>
                                                                                Mensagem
                                                                            </a></li>
                                                                        <li><a class="dropdown-item text-warning" href="#"
                                                                                onclick="forceLogout(<?php echo $u['id_users']; ?>,'<?php echo htmlspecialchars($fn); ?>');return false">
                                                                                <i class="bi bi-power text-warning"></i> Forçar
                                                                                Logout
                                                                            </a></li>
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
                            </div>
                        </div>

                    </div><!-- /col-lg-9 -->

                    <!-- ── Coluna lateral ── -->
                    <div class="col-lg-3">
                        <!-- Actividades mais frequentes -->
                        <div class="card p-3 mb-3" style="border-radius:14px">
                            <div
                                style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;opacity:.5;font-weight:700;margin-bottom:12px">
                                Top Actividades
                            </div>
                            <?php if (empty($top_activities)): ?>
                                <div style="opacity:.35;font-size:.78rem;text-align:center;padding:12px 0">Sem dados
                                </div>
                            <?php else: ?>
                                <?php foreach ($top_activities as $ta): ?>
                                    <div class="activity-feed-item">
                                        <div
                                            style="width:26px;height:26px;border-radius:7px;background:rgba(255,0,137,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                            <i class="bi bi-lightning-charge-fill" style="color:#FF0089;font-size:.75rem"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div style="font-weight:600">
                                                <?php echo ou_activity_label($ta['last_activity_type']); ?></div>
                                        </div>
                                        <span
                                            style="font-size:.72rem;font-weight:700;opacity:.6"><?php echo $ta['cnt']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Top países -->
                        <div class="card p-3 mb-3" style="border-radius:14px">
                            <div
                                style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;opacity:.5;font-weight:700;margin-bottom:12px">
                                Por País
                            </div>
                            <?php if (empty($top_countries)): ?>
                                <div style="opacity:.35;font-size:.78rem;text-align:center;padding:12px 0">Sem dados
                                </div>
                            <?php else: ?>
                                <?php $max_c = max(array_column($top_countries, 'cnt')); ?>
                                <?php foreach ($top_countries as $tc): ?>
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between mb-1" style="font-size:.78rem">
                                            <span><?php echo htmlspecialchars($tc['country_name'] ?? '—'); ?></span>
                                            <span style="opacity:.5"><?php echo $tc['cnt']; ?></span>
                                        </div>
                                        <div style="background:var(--border-color,#e8e8f0);border-radius:4px;height:5px">
                                            <div
                                                style="width:<?php echo round($tc['cnt'] / $max_c * 100); ?>%;height:100%;border-radius:4px;background:#FF0089;transition:width .4s">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Última actualização -->
                        <div class="card p-3" style="border-radius:14px;text-align:center">
                            <div style="font-size:.72rem;opacity:.4;margin-bottom:6px">Última actualização</div>
                            <div style="font-size:.9rem;font-weight:700" id="last-update">
                                <?php echo date('H:i:s'); ?>
                            </div>
                            <div style="font-size:.72rem;opacity:.4;margin-top:4px"><?php echo date('d/m/Y'); ?>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary mt-3 w-100" onclick="refreshNow()">
                                <i class="bi bi-arrow-clockwise me-1"></i> Actualizar Agora
                            </button>
                        </div>
                    </div>

                </div><!-- /row -->
            </div><!-- /container-fluid -->
        </div><!-- /content -->
    </div><!-- /wrapper -->

    <!-- Modal: Broadcast -->
    <div class="modal fade" id="broadcastModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-broadcast me-2"></i>Enviar Anúncio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.82rem;font-weight:600">Tipo *</label>
                        <select class="form-select" id="bc-type">
                            <option value="info">ℹ️ Informativo</option>
                            <option value="warning">⚠️ Aviso</option>
                            <option value="success">✅ Sucesso</option>
                            <option value="event">🎉 Evento</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.82rem;font-weight:600">Público-alvo *</label>
                        <select class="form-select" id="bc-audience" onchange="toggleAudienceFields()">
                            <option value="all">Todos os utilizadores activos</option>
                            <option value="country">Por país</option>
                        </select>
                    </div>
                    <div class="mb-3" id="bc-country-wrap" style="display:none">
                        <label class="form-label" style="font-size:.82rem;font-weight:600">País (código ISO)</label>
                        <input type="text" class="form-control" id="bc-country-val" placeholder="AO" maxlength="60" />
                        <div style="font-size:.72rem;opacity:.45;margin-top:3px">Ex: AO, PT, BR, MZ</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.82rem;font-weight:600">Mensagem *</label>
                        <textarea class="form-control" id="bc-message" rows="4"
                            placeholder="Escreve o teu anúncio..."></textarea>
                        <div style="font-size:.72rem;opacity:.45;margin-top:3px">A mensagem será inserida em
                            <em>_notification</em> e exibida no dashboard de cada utilizador.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm text-white" id="btn-send-broadcast"
                        style="background:#FF0089;border-color:#FF0089">
                        <i class="bi bi-send me-1"></i> Enviar Anúncio
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Mensagem directa -->
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-chat-dots me-2"></i>Enviar Mensagem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="msg-to-id" />
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.82rem;font-weight:600">Destinatário</label>
                        <input type="text" class="form-control" id="msg-to-name" readonly />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.82rem;font-weight:600">Assunto</label>
                        <input type="text" class="form-control" id="msg-subject" placeholder="Assunto da mensagem" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.82rem;font-weight:600">Mensagem *</label>
                        <textarea class="form-control" id="msg-body" rows="5"
                            placeholder="Escreve a tua mensagem..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-send-message">
                        <i class="bi bi-send me-1"></i> Enviar
                    </button>
                </div>
            </div>
        </div>
    </div>

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
            const SELF_URL = window.location.href.split('?')[0];

            // ── Auto-refresh countdown ──
            let countdown = 60;
            let refreshInt = setInterval(tick, 1000);

            function tick() {
                countdown--;
                const el = document.getElementById('countdown');
                if (el) el.textContent = countdown;
                if (countdown <= 0) {
                    countdown = 60;
                    refreshNow();
                }
            }

            window.refreshNow = async function() {
                countdown = 60;
                const el = document.getElementById('last-update');
                if (el) el.textContent = new Date().toLocaleTimeString('pt-AO');
                // Só faz reload simples — mantém os filtros da URL
                const url = new URL(window.location.href);
                window.location.href = url.toString();
            };

            // ── Toggle de vista ──
            window.setView = function(v) {
                document.getElementById('view-grid').style.display = v === 'grid' ? '' : 'none';
                document.getElementById('view-table').style.display = v === 'table' ? '' : 'none';
                document.getElementById('btn-grid').classList.toggle('active', v === 'grid');
                document.getElementById('btn-table').classList.toggle('active', v === 'table');
                localStorage.setItem('ou-view', v);
            };
            // Restaurar preferência
            const savedView = localStorage.getItem('ou-view') || 'grid';
            setView(savedView);

            // ── Helper AJAX ──
            async function postAction(payload) {
                const fd = new FormData();
                Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
                fd.append('csrf_token', CSRF);
                const r = await fetch(SELF_URL, {
                    method: 'POST',
                    body: fd
                });
                return r.json();
            }

            function toast(type, msg) {
                const id = 't' + Date.now();
                const cls = type === 'success' ? 'bg-success' : type === 'warning' ? 'bg-warning text-dark' :
                    'bg-danger';
                let tc = document.querySelector('.toast-container.position-fixed');
                if (!tc) {
                    tc = document.createElement('div');
                    tc.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                    tc.style.zIndex = '9999';
                    document.body.appendChild(tc);
                }
                tc.insertAdjacentHTML('beforeend', `
            <div id="${id}" class="toast align-items-center ${cls} border-0">
                <div class="d-flex">
                    <div class="toast-body fw-600">${msg}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>`);
                const el = document.getElementById(id);
                new bootstrap.Toast(el, {
                    delay: 4000
                }).show();
                el.addEventListener('hidden.bs.toast', () => el.remove());
            }

            // ── Broadcast ──
            window.openBroadcast = function() {
                document.getElementById('bc-message').value = '';
                document.getElementById('bc-type').value = 'info';
                document.getElementById('bc-audience').value = 'all';
                toggleAudienceFields();
                new bootstrap.Modal(document.getElementById('broadcastModal')).show();
            };
            window.toggleAudienceFields = function() {
                const aud = document.getElementById('bc-audience').value;
                document.getElementById('bc-country-wrap').style.display = aud === 'country' ? '' : 'none';
            };
            document.getElementById('btn-send-broadcast')?.addEventListener('click', async function() {
                const msg = document.getElementById('bc-message').value.trim();
                if (!msg) {
                    toast('danger', 'A mensagem é obrigatória.');
                    return;
                }
                this.disabled = true;
                try {
                    const d = await postAction({
                        ajax_action: 'broadcast',
                        type: document.getElementById('bc-type').value,
                        audience: document.getElementById('bc-audience').value,
                        audience_value: document.getElementById('bc-country-val')?.value || '',
                        message: msg,
                    });
                    toast(d.ok ? 'success' : 'danger', d.message);
                    if (d.ok) bootstrap.Modal.getInstance(document.getElementById('broadcastModal'))
                        .hide();
                } catch {
                    toast('danger', 'Erro de ligação.');
                }
                this.disabled = false;
            });

            // ── Mensagem directa ──
            window.openMessage = function(uid, name) {
                document.getElementById('msg-to-id').value = uid;
                document.getElementById('msg-to-name').value = name;
                document.getElementById('msg-subject').value = '';
                document.getElementById('msg-body').value = '';
                new bootstrap.Modal(document.getElementById('messageModal')).show();
            };
            document.getElementById('btn-send-message')?.addEventListener('click', async function() {
                const body = document.getElementById('msg-body').value.trim();
                if (!body) {
                    toast('danger', 'A mensagem é obrigatória.');
                    return;
                }
                this.disabled = true;
                try {
                    const d = await postAction({
                        ajax_action: 'send_message',
                        to_user: document.getElementById('msg-to-id').value,
                        subject: document.getElementById('msg-subject').value,
                        body: body,
                    });
                    toast(d.ok ? 'success' : 'danger', d.message);
                    if (d.ok) bootstrap.Modal.getInstance(document.getElementById('messageModal'))
                        .hide();
                } catch {
                    toast('danger', 'Erro de ligação.');
                }
                this.disabled = false;
            });

            // ── Forçar logout ──
            window.forceLogout = async function(uid, name) {
                const result = await Swal.fire({
                    icon: 'warning',
                    title: 'Forçar logout?',
                    text: `O utilizador "${name}" será desligado imediatamente em todas as sessões activas.`,
                    showCancelButton: true,
                    confirmButtonText: 'Sim, desligar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#FF0089',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true
                });
                if (!result.isConfirmed) return;
                try {
                    const d = await postAction({
                        ajax_action: 'force_logout',
                        user_id: uid
                    });
                    await Swal.fire({
                        icon: d.ok ? 'success' : 'error',
                        title: d.ok ? 'Logout forçado' : 'Falha ao desligar',
                        text: d.message,
                        confirmButtonColor: '#FF0089'
                    });
                    if (d.ok) location.reload();
                } catch {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Erro de ligação',
                        text: 'Não foi possível comunicar com o servidor.',
                        confirmButtonColor: '#FF0089'
                    });
                }
            };

        })();
    </script>
</body>

</html>