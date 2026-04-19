<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Caixa de Entrada de Suporte
// Arquivo: wu-panel/pages/messages/inbox.php
// Rota:    wu-panel/messages/inbox
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'support.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

function inbox_has_column(PDO $db, string $table, string $column): bool
{
    static $cache = [];

    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);

    return $cache[$key] = ((int)$stmt->fetchColumn() > 0);
}

$ticket_star_enabled   = inbox_has_column($db, '_support_ticket', 'is_starred');
$contact_star_enabled  = inbox_has_column($db, '_contact_message', 'is_starred');
$feedback_star_enabled = inbox_has_column($db, '_feedback', 'is_starred');
$ticket_star_sql       = $ticket_star_enabled ? 'st.is_starred' : '0';
$contact_star_sql      = $contact_star_enabled ? 'cm.is_starred' : '0';
$feedback_star_sql     = $feedback_star_enabled ? 'fb.is_starred' : '0';
$ticket_can_star_sql   = $ticket_star_enabled ? '1' : '0';
$contact_can_star_sql  = $contact_star_enabled ? '1' : '0';
$feedback_can_star_sql = $feedback_star_enabled ? '1' : '0';

// ── Filtros / aba activa ──────────────────────────────────────
$tab      = in_array($_GET['tab'] ?? '', ['tickets', 'contact', 'feedback', 'starred', 'archived']) ? $_GET['tab'] : 'all';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 25;
$f_search = trim($_GET['q'] ?? '');
$selected = (int)($_GET['open'] ?? 0);  // ID da mensagem aberta
$sel_src  = trim($_GET['src'] ?? '');   // fonte da mensagem aberta

// ── Funções de normalização ───────────────────────────────────
// Cada fonte → estrutura unificada para a lista
function inbox_source_label(string $src): array // [label, color, icon]
{
    return match ($src) {
        'ticket_auth'    => ['Suporte (Auth)',    '#8b5cf6', 'bi-shield-lock'],
        'ticket_public'  => ['Suporte (Site)',    '#3b82f6', 'bi-globe2'],
        'ticket_dash'    => ['Suporte (Painel)',  '#FF0089', 'bi-person-fill'],
        'contact'        => ['Contacto Site',     '#f97316', 'bi-envelope'],
        'feedback'       => ['Feedback',          '#22c55e', 'bi-chat-square-text'],
        default          => ['Mensagem',          '#6b7280', 'bi-chat'],
    };
}

function inbox_priority_label(string $p): string
{
    return match ($p) {
        'high'   => '<span class="badge inbox-p-high">Alta</span>',
        'low'    => '<span class="badge inbox-p-low">Baixa</span>',
        default  => '',
    };
}

function inbox_status_badge(string $s): string
{
    return match ($s) {
        'new', 'open'       => '<span class="inbox-s-new"></span>',
        'in_progress'      => '<span class="inbox-s-progress"></span>',
        'read'             => '',
        'replied', 'resolved' => '<span class="inbox-s-replied">✓</span>',
        'archived', 'closed' => '<span class="inbox-s-archived">—</span>',
        default            => '',
    };
}

function inbox_relative(string $dt): string
{
    $ts   = strtotime($dt);
    $diff = time() - $ts;
    if ($diff < 60)       return 'agora';
    if ($diff < 3600)     return floor($diff / 60) . 'min';
    if ($diff < 86400)    return floor($diff / 3600) . 'h';
    if ($diff < 604800)   return date('d/m', $ts);
    return date('d/m/Y', $ts);
}

// ── Construir query UNION normalizada ─────────────────────────
// tickets de suporte
$ticket_where  = [];
$ticket_params = [];
$contact_where  = [];
$contact_params = [];
$feedback_where  = [];
$feedback_params = [];

$search_like = $f_search !== '' ? '%' . $f_search . '%' : null;

// -- tickets
$tw = ["st.status_ticket != 'deleted'"];
if ($tab === 'tickets') {
} // sem filtro extra
if ($tab === 'starred')  $tw[] = $ticket_star_enabled ? "st.is_starred = 1" : "1=0";
if ($tab === 'archived') $tw[] = "st.status_ticket IN ('closed','archived')";
if ($tab === 'contact' || $tab === 'feedback') $tw[] = "1=0"; // esconder
if ($search_like) {
    $tw[] = "(st.subject LIKE ? OR st.body LIKE ? OR st.name_contact LIKE ? OR st.email_contact LIKE ?)";
    array_push($ticket_params, $search_like, $search_like, $search_like, $search_like);
}
if ($tab === 'all' || $tab === 'tickets' || $tab === 'starred' || $tab === 'archived' || $f_search !== '') {
    // ok
}
$tw_str = 'WHERE ' . implode(' AND ', $tw);

// -- contact_message
$cw = ["cm.status_msg != 'deleted'"];
if ($tab === 'contact') {
} // ok
if ($tab === 'starred')  $cw[] = $contact_star_enabled ? "cm.is_starred = 1" : "1=0";
if ($tab === 'archived') $cw[] = "cm.status_msg = 'archived'";
if ($tab === 'tickets' || $tab === 'feedback') $cw[] = "1=0";
if ($search_like) {
    $cw[] = "(cm.subject_msg LIKE ? OR cm.message_msg LIKE ? OR cm.name_msg LIKE ? OR cm.email_msg LIKE ?)";
    array_push($contact_params, $search_like, $search_like, $search_like, $search_like);
}
$cw_str = 'WHERE ' . implode(' AND ', $cw);

// -- feedback
$fw = ["fb.status_fb != 'deleted'"];
if ($tab === 'feedback') {
}
if ($tab === 'starred')  $fw[] = $feedback_star_enabled ? "fb.is_starred = 1" : "1=0";
if ($tab === 'archived') $fw[] = "fb.status_fb = 'archived'";
if ($tab === 'tickets' || $tab === 'contact') $fw[] = "1=0";
if ($search_like) {
    $fw[] = "(fb.subject_fb LIKE ? OR fb.message_fb LIKE ? OR fb.name_fb LIKE ?)";
    array_push($feedback_params, $search_like, $search_like, $search_like);
}
$fw_str = 'WHERE ' . implode(' AND ', $fw);

// UNION query para contagem e listagem
$union_sql = "
    (SELECT
        st.id_ticket        AS msg_id,
        CASE st.source_ticket
            WHEN 'auth_modal'    THEN 'ticket_auth'
            WHEN 'public_form'   THEN 'ticket_public'
            WHEN 'dashboard_form' THEN 'ticket_dash'
            ELSE 'ticket_auth' END   AS source,
        st.name_contact     AS sender_name,
        st.email_contact    AS sender_email,
        st.id_users         AS sender_user_id,
        u.photo_user        AS sender_photo,
        st.subject          AS subject,
        LEFT(st.body, 120)  AS body_preview,
        st.status_ticket    AS msg_status,
        st.priority         AS priority,
        $ticket_star_sql    AS is_starred,
        $ticket_can_star_sql AS can_star,
        st.assigned_to      AS assigned_to,
        st.creat_ticket     AS created_at
    FROM _support_ticket st
    LEFT JOIN _users u ON u.id_users = st.id_users
    $tw_str)
    UNION ALL
    (SELECT
        cm.id               AS msg_id,
        'contact'           AS source,
        cm.name_msg         AS sender_name,
        cm.email_msg        AS sender_email,
        NULL                AS sender_user_id,
        NULL                AS sender_photo,
        cm.subject_msg      AS subject,
        LEFT(cm.message_msg, 120) AS body_preview,
        cm.status_msg       AS msg_status,
        'normal'            AS priority,
        $contact_star_sql   AS is_starred,
        $contact_can_star_sql AS can_star,
        NULL                AS assigned_to,
        cm.created_at       AS created_at
    FROM _contact_message cm
    $cw_str)
    UNION ALL
    (SELECT
        fb.id               AS msg_id,
        'feedback'          AS source,
        fb.name_fb          AS sender_name,
        ''                  AS sender_email,
        NULL                AS sender_user_id,
        NULL                AS sender_photo,
        fb.subject_fb       AS subject,
        LEFT(fb.message_fb, 120) AS body_preview,
        fb.status_fb        AS msg_status,
        'low'               AS priority,
        $feedback_star_sql  AS is_starred,
        $feedback_can_star_sql AS can_star,
        NULL                AS assigned_to,
        fb.created_at       AS created_at
    FROM _feedback fb
    $fw_str)
";

$all_params = array_merge($ticket_params, $contact_params, $feedback_params);

// Contagem
$count_stmt = $db->prepare("SELECT COUNT(*) FROM ($union_sql) AS t");
$count_stmt->execute($all_params);
$total       = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

// Listagem ordenada
$list_stmt = $db->prepare(
    "SELECT * FROM ($union_sql) AS t ORDER BY is_starred DESC, created_at DESC LIMIT $per_page OFFSET $offset"
);
$list_stmt->execute($all_params);
$messages = $list_stmt->fetchAll();

// ── Badge counts para as abas ─────────────────────────────────
$unread_tickets  = (int)$db->query("SELECT COUNT(*) FROM _support_ticket WHERE status_ticket='open'")->fetchColumn();
$unread_contact  = (int)$db->query("SELECT COUNT(*) FROM _contact_message WHERE status_msg='new'")->fetchColumn();
$unread_feedback = (int)$db->query("SELECT COUNT(*) FROM _feedback WHERE status_fb='new'")->fetchColumn();
$total_unread    = $unread_tickets + $unread_contact + $unread_feedback;
$starred_count_sql = [];
if ($ticket_star_enabled) {
    $starred_count_sql[] = "(SELECT COUNT(*) FROM _support_ticket WHERE is_starred=1)";
}
if ($contact_star_enabled) {
    $starred_count_sql[] = "(SELECT COUNT(*) FROM _contact_message WHERE is_starred=1)";
}
if ($feedback_star_enabled) {
    $starred_count_sql[] = "(SELECT COUNT(*) FROM _feedback WHERE is_starred=1)";
}
$total_starred = $starred_count_sql
    ? (int)$db->query("SELECT " . implode(' + ', $starred_count_sql))->fetchColumn()
    : 0;

// ── Admins para atribuição ────────────────────────────────────
$admins = $db->query("SELECT id_employees, CONCAT(first_name,' ',COALESCE(second_name,'')) AS name FROM _employees WHERE status_employees='active' ORDER BY first_name")->fetchAll();

$base_url = APP_URL . '/' . ADMIN_PATH;
$csrf     = $_SESSION['admin_csrf_token'];
$proc_url = $base_url . '/messages/inbox-process';
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
    <title>Caixa de Entrada — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        /* ══════════════════════════════════════════════
       Inbox layout — 3 colunas tipo Gmail
       ══════════════════════════════════════════════ */
        .inbox-wrap {
            display: flex;
            height: calc(100vh - 64px);
            /* 64px = navbar */
            overflow: hidden;
            background: var(--card-bg, #fff);
            border-radius: 14px;
            border: 1px solid var(--border-color, #e8e8f0);
        }

        /* ── Sidebar ── */
        .inbox-sidebar {
            width: 220px;
            flex-shrink: 0;
            border-right: 1px solid var(--border-color, #e8e8f0);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .inbox-sidebar-head {
            padding: 14px 16px 10px;
            font-size: .88rem;
            font-weight: 800;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .inbox-compose-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 10px 12px;
            padding: 9px 14px;
            background: linear-gradient(135deg, #FF0089, #f97316);
            color: #fff;
            border-radius: 10px;
            font-size: .8rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all .2s;
            box-shadow: 0 4px 12px rgba(255, 0, 137, .25);
        }

        .inbox-compose-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(255, 0, 137, .35);
            color: #fff;
        }

        .inbox-nav {
            flex: 1;
            overflow-y: auto;
            padding: 6px 0;
        }

        .inbox-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            font-size: .8rem;
            font-weight: 500;
            cursor: pointer;
            border-radius: 0;
            text-decoration: none;
            color: var(--bs-body-color);
            transition: all .15s;
            position: relative;
        }

        .inbox-nav-item:hover {
            background: rgba(255, 0, 137, .07);
            color: #FF0089;
        }

        .inbox-nav-item.active {
            background: rgba(255, 0, 137, .12);
            color: #FF0089;
            font-weight: 700;
            border-right: 3px solid #FF0089;
        }

        .inbox-nav-item i {
            font-size: .95rem;
            width: 18px;
            text-align: center;
            flex-shrink: 0;
        }

        .inbox-nav-badge {
            margin-left: auto;
            background: #FF0089;
            color: #fff;
            font-size: .58rem;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 20px;
            min-width: 18px;
            text-align: center;
        }

        .inbox-nav-sep {
            padding: 6px 16px 3px;
            font-size: .62rem;
            text-transform: uppercase;
            letter-spacing: .8px;
            opacity: .45;
            font-weight: 700;
        }

        /* ── Lista de mensagens ── */
        .inbox-list-col {
            width: 340px;
            flex-shrink: 0;
            border-right: 1px solid var(--border-color, #e8e8f0);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .inbox-list-head {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .inbox-search {
            flex: 1;
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 8px;
            padding: 6px 12px 6px 32px;
            font-size: .8rem;
            background: transparent;
            color: inherit;
            outline: none;
            transition: border-color .2s;
        }

        .inbox-search:focus {
            border-color: #FF0089;
        }

        .inbox-search-wrap {
            position: relative;
            flex: 1;
        }

        .inbox-search-wrap i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            opacity: .4;
            font-size: .8rem;
        }

        .inbox-list-scroll {
            flex: 1;
            overflow-y: auto;
        }

        .inbox-msg-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
            cursor: pointer;
            transition: background .12s;
            position: relative;
            text-decoration: none;
            color: inherit;
        }

        .inbox-msg-item:hover {
            background: rgba(255, 0, 137, .04);
        }

        .inbox-msg-item.active {
            background: rgba(255, 0, 137, .09);
            border-right: 3px solid #FF0089;
        }

        .inbox-msg-item.unread .inbox-sender {
            font-weight: 800;
        }

        .inbox-msg-item.unread .inbox-subject {
            font-weight: 700;
        }

        .inbox-msg-item.starred .inbox-star-btn {
            color: #f59e0b !important;
        }

        .inbox-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 2px solid transparent;
        }

        .inbox-avatar-ini {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: .68rem;
            color: #fff;
            flex-shrink: 0;
        }

        .inbox-msg-body {
            flex: 1;
            min-width: 0;
        }

        .inbox-msg-top {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 2px;
        }

        .inbox-sender {
            font-size: .8rem;
            font-weight: 500;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .inbox-time {
            font-size: .68rem;
            opacity: .5;
            flex-shrink: 0;
        }

        .inbox-subject {
            font-size: .77rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-bottom: 2px;
        }

        .inbox-preview {
            font-size: .72rem;
            opacity: .5;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .inbox-source-pill {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: .6rem;
            font-weight: 700;
        }

        .inbox-star-btn {
            background: none;
            border: none;
            padding: 2px;
            color: rgba(0, 0, 0, .25);
            cursor: pointer;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .inbox-star-btn:hover {
            color: #f59e0b;
        }

        .dark-mode .inbox-star-btn {
            color: rgba(255, 255, 255, .25);
        }

        /* Status dots */
        .inbox-s-new {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #3b82f6;
            flex-shrink: 0;
            margin-right: 2px;
        }

        .inbox-s-progress {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #f97316;
            flex-shrink: 0;
            margin-right: 2px;
        }

        .inbox-s-replied {
            font-size: .65rem;
            color: #22c55e;
            font-weight: 700;
            flex-shrink: 0;
            margin-right: 2px;
        }

        .inbox-s-archived {
            font-size: .65rem;
            color: #9ca3af;
            flex-shrink: 0;
            margin-right: 2px;
        }

        /* Priority badges */
        .inbox-p-high {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
            font-size: .6rem;
            padding: 1px 5px;
        }

        .inbox-p-low {
            background: rgba(107, 114, 128, .15);
            color: #374151;
            font-size: .6rem;
            padding: 1px 5px;
        }

        /* List empty */
        .inbox-empty {
            text-align: center;
            padding: 40px 20px;
            opacity: .4;
        }

        .inbox-empty i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 10px;
        }

        /* ── Painel de leitura ── */
        .inbox-read-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-width: 0;
        }

        .inbox-read-toolbar {
            padding: 10px 20px;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .inbox-read-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .inbox-msg-header {
            margin-bottom: 20px;
        }

        .inbox-msg-title {
            font-size: 1.1rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .inbox-msg-from {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            background: var(--table-stripe, rgba(0, 0, 0, .02));
            border-radius: 10px;
            margin-bottom: 16px;
        }

        .inbox-msg-meta {
            font-size: .76rem;
            opacity: .6;
            margin-top: 2px;
        }

        .inbox-msg-content {
            font-size: .88rem;
            line-height: 1.7;
            padding: 16px 0;
            border-top: 1px solid var(--border-color, #e8e8f0);
            border-bottom: 1px solid var(--border-color, #e8e8f0);
            margin-bottom: 20px;
            word-break: break-word;
            white-space: pre-wrap;
        }

        /* Thread de respostas */
        .inbox-replies {
            margin-bottom: 20px;
        }

        .inbox-reply-item {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 10px;
            border: 1px solid var(--border-color, #e8e8f0);
        }

        .inbox-reply-item.from-admin {
            border-left: 3px solid #FF0089;
            background: rgba(255, 0, 137, .03);
        }

        .inbox-reply-item.from-user {
            border-left: 3px solid #3b82f6;
            background: rgba(59, 130, 246, .03);
        }

        .inbox-reply-meta {
            font-size: .72rem;
            opacity: .55;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
        }

        .inbox-reply-body {
            font-size: .84rem;
            line-height: 1.65;
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* Caixa de resposta */
        .inbox-reply-box {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 14px;
            margin-top: 10px;
        }

        .inbox-reply-box textarea {
            width: 100%;
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 8px;
            padding: 10px;
            font-size: .84rem;
            resize: vertical;
            min-height: 100px;
            background: transparent;
            color: inherit;
            outline: none;
            font-family: inherit;
            transition: border-color .2s;
        }

        .inbox-reply-box textarea:focus {
            border-color: #FF0089;
        }

        /* Welcome/empty state */
        .inbox-welcome {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: .35;
            padding: 40px;
            text-align: center;
        }

        /* Paginação */
        .inbox-pag {
            padding: 8px 14px;
            border-top: 1px solid var(--border-color, #e8e8f0);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .inbox-pag .page-link {
            border-radius: 6px !important;
            font-size: .75rem;
            margin: 0 1px;
        }

        /* Status badge na toolbar */
        .status-select-sm {
            font-size: .75rem;
            padding: 3px 8px;
            border-radius: 7px;
            border: 1px solid var(--border-color, #e8e8f0);
            background: transparent;
            color: inherit;
        }

        /* ── Responsivo ── */
        @media (max-width: 991px) {
            .inbox-sidebar {
                display: none;
            }

            .inbox-list-col {
                width: 100%;
                border-right: none;
            }

            .inbox-list-col.reading-mode {
                display: none;
            }

            .inbox-read-col {
                display: none;
            }

            .inbox-read-col.reading-mode {
                display: flex;
            }

            .inbox-back-btn {
                display: flex !important;
            }
        }

        .inbox-back-btn {
            display: none;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php require_once __DIR__ . '/../../include/sidebar.php'; ?>

        <div class="content w-100" id="mainContent" style="padding:0">
            <?php require_once __DIR__ . '/../../include/navbar.php'; ?>

            <!-- Título e breadcrumb acima do inbox -->
            <div class="d-flex align-items-center gap-3 px-3 py-2"
                style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                <div>
                    <span style="font-size:.95rem;font-weight:800"><i class="bi bi-inbox me-2"></i>Caixa de
                        Entrada</span>
                    <span style="font-size:.75rem;opacity:.5;margin-left:8px"><?php echo number_format($total); ?>
                        mensage<?php echo $total !== 1 ? 'ns' : 'm'; ?></span>
                </div>
                <?php if ($total_unread > 0): ?>
                    <span class="badge" style="background:#FF0089;font-size:.7rem"><?php echo $total_unread; ?> não
                        lida<?php echo $total_unread !== 1 ? 's' : ''; ?></span>
                <?php endif; ?>
            </div>

            <!-- Inbox layout -->
            <div class="inbox-wrap">

                <!-- ── SIDEBAR ── -->
                <div class="inbox-sidebar">
                    <button class="inbox-compose-btn" data-bs-toggle="modal" data-bs-target="#modalCompose">
                        <i class="bi bi-pencil-square"></i> Nova Mensagem
                    </button>

                    <nav class="inbox-nav">
                        <div class="inbox-nav-sep">Caixa</div>
                        <?php
                        $tabs = [
                            ['all',      'bi-inbox',           'Todas',        $total_unread],
                            ['tickets',  'bi-headset',         'Suporte',      $unread_tickets],
                            ['contact',  'bi-envelope-open',   'Contacto Site', $unread_contact],
                            ['feedback', 'bi-chat-square-text', 'Feedbacks',    $unread_feedback],
                        ];
                        foreach ($tabs as [$t, $icon, $label, $badge]):
                            $is_active = $tab === $t;
                            $q = array_merge($_GET, ['tab' => $t, 'page' => 1, 'open' => '', 'src' => '']);
                            unset($q['open'], $q['src']);
                        ?>
                            <a href="?<?php echo http_build_query($q); ?>"
                                class="inbox-nav-item <?php echo $is_active ? 'active' : ''; ?>">
                                <i class="bi <?php echo $icon; ?>"></i>
                                <?php echo $label; ?>
                                <?php if ($badge > 0): ?>
                                    <span class="inbox-nav-badge"><?php echo $badge > 99 ? '99+' : $badge; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>

                        <div class="inbox-nav-sep" style="margin-top:10px">Organizar</div>
                        <?php
                        $tabs2 = [
                            ['starred',  'bi-star-fill',  'Importantes',  $total_starred],
                            ['archived', 'bi-archive',    'Arquivadas',   0],
                        ];
                        foreach ($tabs2 as [$t, $icon, $label, $badge]):
                            $is_active = $tab === $t;
                            $q = array_merge($_GET, ['tab' => $t, 'page' => 1]);
                            unset($q['open'], $q['src']);
                        ?>
                            <a href="?<?php echo http_build_query($q); ?>"
                                class="inbox-nav-item <?php echo $is_active ? 'active' : ''; ?>">
                                <i class="bi <?php echo $icon; ?>"
                                    style="<?php echo $t === 'starred' ? 'color:#f59e0b' : ''; ?>"></i>
                                <?php echo $label; ?>
                                <?php if ($badge > 0): ?>
                                    <span class="inbox-nav-badge" style="background:#f59e0b"><?php echo $badge; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <!-- ── LISTA DE MENSAGENS ── -->
                <div class="inbox-list-col" id="listCol"
                    <?php echo ($selected && $sel_src) ? 'class="inbox-list-col"' : ''; ?>>
                    <!-- Search bar -->
                    <div class="inbox-list-head">
                        <form method="GET" style="display:contents" id="search-form">
                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                            <div class="inbox-search-wrap">
                                <i class="bi bi-search"></i>
                                <input type="text" name="q" class="inbox-search" placeholder="Pesquisar mensagens..."
                                    value="<?php echo htmlspecialchars($f_search); ?>" id="inbox-search-input">
                            </div>
                        </form>
                    </div>

                    <!-- Lista -->
                    <div class="inbox-list-scroll" id="msgList">
                        <?php if (empty($messages)): ?>
                            <div class="inbox-empty">
                                <i class="bi bi-inbox"></i>
                                <p class="mb-0">Nenhuma mensagem encontrada.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $msg):
                                [$src_label, $src_color, $src_icon] = inbox_source_label($msg['source']);
                                $is_unread  = in_array($msg['msg_status'], ['new', 'open']);
                                $is_starred = (bool)$msg['is_starred'];
                                $can_star   = (bool)($msg['can_star'] ?? 0);
                                $is_active  = ($selected == $msg['msg_id'] && $sel_src === $msg['source']);

                                // Avatar
                                $name  = $msg['sender_name'] ?: 'Visitante';
                                $parts = explode(' ', trim($name), 2);
                                $ini   = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1, 'UTF-8'), 'UTF-8')
                                    . mb_strtoupper(mb_substr($parts[1] ?? '', 0, 1, 'UTF-8'), 'UTF-8');
                                $colors = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308', '#3b82f6', '#ef4444'];
                                $avc   = $colors[abs(crc32($name)) % count($colors)];

                                $open_url = '?' . http_build_query(array_merge($_GET, ['open' => $msg['msg_id'], 'src' => $msg['source'], 'page' => $page]));
                            ?>
                                <a href="<?php echo $open_url; ?>"
                                    class="inbox-msg-item <?php echo $is_unread ? 'unread' : ''; ?> <?php echo $is_starred ? 'starred' : ''; ?> <?php echo $is_active ? 'active' : ''; ?>"
                                    data-id="<?php echo (int)$msg['msg_id']; ?>"
                                    data-source="<?php echo htmlspecialchars($msg['source']); ?>"
                                    onclick="loadMessage(<?php echo (int)$msg['msg_id']; ?>,'<?php echo htmlspecialchars($msg['source']); ?>',this);return false">

                                    <!-- Unread dot -->
                                    <div style="width:6px;flex-shrink:0;padding-top:16px">
                                        <?php if ($is_unread): ?>
                                            <div style="width:6px;height:6px;border-radius:50%;background:#FF0089"></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Avatar -->
                                    <?php if ($msg['sender_photo']): ?>
                                        <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($msg['sender_photo']); ?>"
                                            class="inbox-avatar" alt=""
                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                        <div class="inbox-avatar-ini" style="background:<?php echo $avc; ?>;display:none">
                                            <?php echo $ini; ?></div>
                                    <?php else: ?>
                                        <div class="inbox-avatar-ini" style="background:<?php echo $avc; ?>"><?php echo $ini; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Corpo -->
                                    <div class="inbox-msg-body">
                                        <div class="inbox-msg-top">
                                            <span class="inbox-sender"><?php echo htmlspecialchars($name); ?></span>
                                            <span class="inbox-time"><?php echo inbox_relative($msg['created_at']); ?></span>
                                        </div>
                                        <div class="inbox-subject">
                                            <?php echo inbox_status_badge($msg['msg_status']); ?>
                                            <?php echo htmlspecialchars($msg['subject']); ?>
                                        </div>
                                        <div class="inbox-preview"><?php echo htmlspecialchars($msg['body_preview']); ?></div>
                                        <div class="mt-1 d-flex align-items-center gap-1">
                                            <span class="inbox-source-pill"
                                                style="background:<?php echo $src_color; ?>1a;color:<?php echo $src_color; ?>">
                                                <i class="bi <?php echo $src_icon; ?>" style="font-size:.55rem"></i>
                                                <?php echo $src_label; ?>
                                            </span>
                                            <?php echo inbox_priority_label($msg['priority']); ?>
                                        </div>
                                    </div>

                                    <!-- Estrela -->
                                    <button class="inbox-star-btn" data-can-star="<?php echo $can_star ? '1' : '0'; ?>"
                                        onclick="toggleStar(event, <?php echo (int)$msg['msg_id']; ?>,'<?php echo htmlspecialchars($msg['source']); ?>',this)"
                                        title="<?php echo $can_star ? ($is_starred ? 'Remover importância' : 'Marcar como importante') : 'Favoritos indisponíveis nesta instalação'; ?>"
                                        style="<?php echo $can_star ? '' : 'opacity:.45;cursor:not-allowed'; ?>">
                                        <i class="bi <?php echo $is_starred ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                    </button>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Paginação da lista -->
                    <?php if ($total_pages > 1): ?>
                        <div class="inbox-pag">
                            <span style="font-size:.72rem;opacity:.5"><?php echo number_format($total); ?> total</span>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item disabled"><span class="page-link"
                                            style="font-size:.72rem"><?php echo $page; ?>/<?php echo $total_pages; ?></span>
                                    </li>
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

                <!-- ── PAINEL DE LEITURA ── -->
                <div class="inbox-read-col" id="readCol">

                    <!-- Estado vazio / welcome -->
                    <div class="inbox-welcome" id="readWelcome"
                        <?php echo ($selected && $sel_src) ? 'style="display:none"' : ''; ?>>
                        <i class="bi bi-inbox" style="font-size:3.5rem;margin-bottom:14px"></i>
                        <p style="font-weight:700;font-size:.95rem">Selecciona uma mensagem</p>
                        <p style="font-size:.8rem">Clica numa mensagem na lista para a ler aqui.</p>
                    </div>

                    <!-- Loading spinner -->
                    <div id="readLoading" style="display:none;flex:1;align-items:center;justify-content:center">
                        <div class="spinner-border" style="color:#FF0089"></div>
                    </div>

                    <!-- Conteúdo da mensagem (carregado via AJAX) -->
                    <div id="readContent"
                        style="display:<?php echo ($selected && $sel_src) ? 'flex' : 'none'; ?>;flex-direction:column;height:100%;overflow:hidden">

                        <!-- Toolbar acções -->
                        <div class="inbox-read-toolbar" id="readToolbar">
                            <button class="btn btn-sm btn-outline-secondary inbox-back-btn" id="btnBack"
                                onclick="closeRead()">
                                <i class="bi bi-arrow-left"></i>
                            </button>
                            <div id="toolbarActions" class="d-flex gap-2 align-items-center flex-wrap flex-fill">
                                <!-- Preenchido via JS -->
                            </div>
                        </div>

                        <!-- Scroll do conteúdo -->
                        <div class="inbox-read-scroll" id="readBody">
                            <!-- Preenchido via AJAX -->
                            <?php if ($selected && $sel_src): ?>
                                <div class="text-center py-5">
                                    <div class="spinner-border" style="color:#FF0089"></div>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

            </div><!-- /inbox-wrap -->
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════
     MODAL — Nova Mensagem (Compose)
════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalCompose" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:#FF0089">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="bi bi-pencil-square me-2"></i>Nova Mensagem
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Para <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="compose_to" placeholder="email@destinatario.com">
                        <div class="form-text">Podes escrever directamente para qualquer email.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Assunto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="compose_subject" placeholder="Assunto da mensagem">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Mensagem <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="compose_body" rows="6"
                            placeholder="Escreve a tua mensagem..."></textarea>
                    </div>
                    <div class="alert alert-danger d-none" id="compose_error" style="font-size:.78rem"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm text-white" style="background:#FF0089"
                        id="btn_send_compose">
                        <span class="normal-lbl"><i class="bi bi-send me-1"></i>Enviar Email</span>
                        <span class="loading-lbl d-none"><span class="spinner-border spinner-border-sm me-1"></span>A
                            enviar…</span>
                    </button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
        (function() {
            'use strict';

            window.__BASE_URL__ = '<?php echo APP_URL; ?>';
            window.__ADMIN_PATH__ = '<?php echo ADMIN_PATH; ?>';

            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const PROCESS = '<?php echo $proc_url; ?>';

            // ── AJAX helper ──────────────────────────────────────
            async function post(payload) {
                const fd = new FormData();
                Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
                fd.append('csrf_token', CSRF);
                try {
                    const r = await fetch(PROCESS, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: fd
                    });
                    if (!r.ok) throw new Error('Erro de servidor: ' + r.status);
                    const text = await r.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error("Erro ao processar JSON. Resposta do servidor:", text);
                        throw new Error('Resposta inválida do servidor');
                    }
                } catch (e) {
                    console.error("Erro na requisição AJAX:", e);
                    return {
                        ok: false,
                        message: 'Erro de ligação ao servidor.'
                    };
                }
            }

            function setLoad(btn, state) {
                btn.querySelector('.normal-lbl').classList.toggle('d-none', state);
                btn.querySelector('.loading-lbl').classList.toggle('d-none', !state);
                btn.disabled = state;
            }

            // ── Carregar mensagem no painel direito ──────────────
            let currentId = <?php echo $selected ?: 0; ?>;
            let currentSrc = '<?php echo htmlspecialchars($sel_src); ?>';

            window.loadMessage = async function(id, src, el) {
                // Actualizar URL sem reload
                const url = new URL(window.location);
                url.searchParams.set('open', id);
                url.searchParams.set('src', src);
                history.pushState({}, '', url);

                // Highlight na lista
                document.querySelectorAll('.inbox-msg-item').forEach(i => i.classList.remove('active'));
                if (el) el.classList.add('active');

                // Mostrar loading
                document.getElementById('readWelcome').style.display = 'none';
                document.getElementById('readLoading').style.display = 'flex';
                document.getElementById('readContent').style.display = 'none';

                // Mobile: esconder lista
                if (window.innerWidth <= 991) {
                    document.getElementById('listCol').classList.add('reading-mode');
                    document.getElementById('readCol').classList.add('reading-mode');
                }

                currentId = id;
                currentSrc = src;

                try {
                    const data = await post({
                        action: 'load_message',
                        msg_id: id,
                        source: src
                    });
                    if (data.ok) {
                        document.getElementById('readBody').innerHTML = data.html;
                        document.getElementById('toolbarActions').innerHTML = data.toolbar_html;
                        document.getElementById('readLoading').style.display = 'none';
                        document.getElementById('readContent').style.display = 'flex';

                        // Marcar não-lida como lida automaticamente
                        if (data.was_unread) {
                            if (el) el.classList.remove('unread');
                            const dot = el?.querySelector('[style*="border-radius:50%"]');
                            if (dot) dot.remove();
                        }

                        // Focar reply se existir
                        setTimeout(() => {
                            const ta = document.getElementById('reply_textarea');
                            // não focar automaticamente
                        }, 200);
                    } else {
                        document.getElementById('readBody').innerHTML = '<div class="alert alert-danger m-3">' +
                            data.message + '</div>';
                        document.getElementById('readLoading').style.display = 'none';
                        document.getElementById('readContent').style.display = 'flex';
                    }
                } catch {
                    document.getElementById('readBody').innerHTML =
                        '<div class="alert alert-danger m-3">Erro de ligação.</div>';
                    document.getElementById('readLoading').style.display = 'none';
                    document.getElementById('readContent').style.display = 'flex';
                }
            };

            window.closeRead = function() {
                document.getElementById('listCol').classList.remove('reading-mode');
                document.getElementById('readCol').classList.remove('reading-mode');
                document.getElementById('readWelcome').style.display = 'flex';
                document.getElementById('readContent').style.display = 'none';
            };

            // ── Estrelar/Desestrelar ──────────────────────────────
            window.toggleStar = async function(e, id, src, btn) {
                e.preventDefault();
                e.stopPropagation();
                if (btn?.dataset?.canStar !== '1') {
                    return;
                }

                const icon = btn.querySelector('i');
                const item = btn.closest('.inbox-msg-item');
                btn.disabled = true;

                const data = await post({
                    action: 'toggle_star',
                    msg_id: id,
                    source: src
                });

                btn.disabled = false;
                if (!data.ok) {
                    await Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: data.message || 'Não foi possível actualizar o favorito.',
                        showConfirmButton: false,
                        timer: 2600,
                        timerProgressBar: true
                    });
                    return;
                }

                const starred = !!data.starred;
                icon.className = starred ? 'bi bi-star-fill' : 'bi bi-star';
                btn.style.color = starred ? '#f59e0b' : '';
                btn.title = starred ? 'Remover importância' : 'Marcar como importante';
                item?.classList.toggle('starred', starred);
            };

            // ── Responder (delegado ao body — o HTML do painel tem os handlers) ──
            document.addEventListener('click', async function(e) {
                // Botão de enviar resposta
                if (e.target.closest('#btn_send_reply')) {
                    const btn = e.target.closest('#btn_send_reply');
                    const ta = document.getElementById('reply_textarea');
                    const errEl = document.getElementById('reply_error');
                    if (errEl) errEl.classList.add('d-none');

                    const body = ta?.value.trim();
                    if (!body || body.length < 3) {
                        if (errEl) {
                            errEl.textContent = 'Escreve uma resposta.';
                            errEl.classList.remove('d-none');
                        }
                        return;
                    }

                    setLoad(btn, true);
                    try {
                        const data = await post({
                            action: 'reply',
                            msg_id: currentId,
                            source: currentSrc,
                            body: body,
                        });
                        if (data.ok) {
                            ta.value = '';
                            await loadMessage(currentId, currentSrc, null);
                        } else {
                            if (errEl) {
                                errEl.textContent = data.message;
                                errEl.classList.remove('d-none');
                            }
                        }
                    } catch {
                        if (errEl) {
                            errEl.textContent = 'Erro de ligação.';
                            errEl.classList.remove('d-none');
                        }
                    }
                    setLoad(btn, false);
                }

                // Botão arquivar
                if (e.target.closest('#btn_archive')) {
                    const btn = e.target.closest('#btn_archive');
                    const {
                        isConfirmed
                    } = await Swal.fire({
                        title: 'Arquivar mensagem?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#FF0089',
                        confirmButtonText: 'Sim',
                        cancelButtonText: 'Cancelar'
                    });
                    if (!isConfirmed) return;
                    btn.disabled = true;
                    await post({
                        action: 'archive',
                        msg_id: currentId,
                        source: currentSrc
                    });
                    window.location.reload();
                }

                // Botão eliminar
                if (e.target.closest('#btn_delete')) {
                    const {
                        isConfirmed
                    } = await Swal.fire({
                        title: 'Eliminar mensagem?',
                        text: 'Esta acção é irreversível.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        confirmButtonText: 'Eliminar',
                        cancelButtonText: 'Cancelar'
                    });
                    if (!isConfirmed) return;
                    await post({
                        action: 'delete_msg',
                        msg_id: currentId,
                        source: currentSrc
                    });
                    window.location.reload();
                }

                // Mudar status (select)
                if (e.target.id === 'status_select') {
                    await post({
                        action: 'change_status',
                        msg_id: currentId,
                        source: currentSrc,
                        new_status: e.target.value
                    });
                }

                // Atribuir a admin
                if (e.target.id === 'assign_select') {
                    await post({
                        action: 'assign',
                        msg_id: currentId,
                        source: currentSrc,
                        assigned_to: e.target.value
                    });
                }
            });

            // ── Compose ──────────────────────────────────────────
            document.getElementById('btn_send_compose')?.addEventListener('click', async function() {
                const to = document.getElementById('compose_to').value.trim();
                const subject = document.getElementById('compose_subject').value.trim();
                const body = document.getElementById('compose_body').value.trim();
                const errEl = document.getElementById('compose_error');
                errEl.classList.add('d-none');

                if (!to || !subject || !body) {
                    errEl.textContent = 'Preenche todos os campos.';
                    errEl.classList.remove('d-none');
                    return;
                }
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(to)) {
                    errEl.textContent = 'Email inválido.';
                    errEl.classList.remove('d-none');
                    return;
                }

                setLoad(this, true);
                try {
                    const data = await post({
                        action: 'compose_email',
                        to,
                        subject,
                        body
                    });
                    if (data.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('modalCompose')).hide();
                        document.getElementById('compose_to').value = '';
                        document.getElementById('compose_subject').value = '';
                        document.getElementById('compose_body').value = '';
                        await Swal.fire({
                            icon: 'success',
                            title: 'Enviado!',
                            text: 'Email enviado com sucesso.',
                            confirmButtonColor: '#FF0089'
                        });
                    } else {
                        errEl.textContent = data.message;
                        errEl.classList.remove('d-none');
                    }
                } catch {
                    errEl.textContent = 'Erro de ligação.';
                    errEl.classList.remove('d-none');
                }
                setLoad(this, false);
            });

            // ── Pesquisa com debounce ─────────────────────────────
            let dbt;
            document.getElementById('inbox-search-input')?.addEventListener('input', function() {
                clearTimeout(dbt);
                dbt = setTimeout(() => document.getElementById('search-form').submit(), 500);
            });

            // ── Auto-carregar mensagem se vier via URL ────────────
            document.addEventListener('DOMContentLoaded', function() {
                if (<?php echo $selected ? 'true' : 'false'; ?>) {
                    loadMessage(<?php echo $selected ?: 0; ?>, '<?php echo htmlspecialchars($sel_src); ?>',
                        null);
                }
            });

        })();
    </script>
</body>

</html>