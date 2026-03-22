<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Notificações
// Arquivo: dashboard/page/notifications.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
require_once __DIR__ . '/../include/platform.php';
startSecureSession();
checkRememberMe();
requireLogin();
$platform = checkDashboardStatus();
$user     = checkUserAccess((int)$_SESSION['id_users']);

$id_users       = (int)$user['id_users'];
$first_name     = htmlspecialchars($user['first_name']);
$user_name      = htmlspecialchars($user['user_name'] ?? '');
$email_verified = (bool)$user['email_verified'];
$plan_selected  = $user['plan_selected'];
$onboard_done   = (bool)($user['onboarding_done'] ?? false);
$user_photo     = $user['photo_user'] ?? null;
$name_artist_band = htmlspecialchars($user['name_artist_band'] ?? 'Cria Perfil Artístico');
$notif_count    = getUnreadNotifCount($id_users);
$db             = getDB();

// ── Saldo ─────────────────────────────────────
$w = $db->prepare('SELECT balance_aoa FROM _wallet WHERE id_users = ?');
$w->execute([$id_users]);
$balance = $w->fetch() ?: ['balance_aoa' => 0];

// ── Plano ─────────────────────────────────────
$plan_id     = (int)$user['plan_selected'];
$plan        = null;
$max_artists = 1;
if ($plan_id) {
    $ps = $db->prepare('SELECT * FROM _plans WHERE id_plan = ?');
    $ps->execute([$plan_id]);
    $plan = $ps->fetch();
    if ($plan) $max_artists = (int)($plan['max_artists'] ?? 1);
}
$plan_name = $plan ? htmlspecialchars($plan['name_plan']) : 'Sem plano';

// ── Plano ─────────────────────────────────────
$plan      = null;
$plan_paid = ($user['status_user'] === 'active' && !empty($user['plan_activated_at']));
if ($plan_selected) {
    $ps = $db->prepare('SELECT * FROM _plans WHERE id_plan = ?');
    $ps->execute([$plan_selected]);
    $plan = $ps->fetch();
}

// Adicionar verificação de expiração do plano
$plan_expired = false;
if ($plan_paid && !empty($user['plan_expires_at'])) {
    $plan_expired = strtotime($user['plan_expires_at']) < time();
}

// ── Artistas ──────────────────────────────────
$as = $db->prepare('SELECT COUNT(*) AS total FROM _artist WHERE id_users = ?');
$as->execute([$id_users]);
$has_artist = (int)($as->fetch()['total'] ?? 0) > 0;

// ── Conta bancária ────────────────────────────
$ba = $db->prepare("SELECT id_account FROM _account WHERE id_users = ? AND status_account = 'verified' LIMIT 1");
$ba->execute([$id_users]);
$bank_account = $ba->fetch();

// ── Conta rejeitada ───────────────────────────
$rejected_account = null;
if ($plan_paid) {
    $rj = $db->prepare("SELECT type_account, reject_reason FROM _account WHERE id_users = ? AND status_account = 'rejected' LIMIT 1");
    $rj->execute([$id_users]);
    $rejected_account = $rj->fetch();
}

// ── Sessão info (modal logout) ────────────────
$ls = $db->prepare('SELECT last_login_at, last_login_ip FROM _users_security WHERE id_users = ?');
$ls->execute([$id_users]);
$sec = $ls->fetch();

$sess_stmt = $db->prepare("
    SELECT ip_address, user_agent, country, city, creat_session, last_activity
    FROM _users_sessions WHERE id_users = ? AND is_active = 1
    ORDER BY last_activity DESC LIMIT 1
");
$sess_stmt->execute([$id_users]);
$current_session  = $sess_stmt->fetch();
$session_duration_str = '—';
if ($current_session && $current_session['creat_session']) {
    $secs = time() - strtotime($current_session['creat_session']);
    if ($secs < 60)     $session_duration_str = $secs . 's';
    elseif ($secs < 3600)  $session_duration_str = floor($secs / 60) . 'min';
    elseif ($secs < 86400) $session_duration_str = floor($secs / 3600) . 'h ' . floor(($secs % 3600) / 60) . 'min';
    else                   $session_duration_str = floor($secs / 86400) . 'd ' . floor(($secs % 86400) / 3600) . 'h';
}
$member_since   = $user['creat_user'] ? date('d/m/Y', strtotime($user['creat_user'])) : '—';
$last_login_str = ($sec && $sec['last_login_at']) ? date('d/m/Y H:i', strtotime($sec['last_login_at'])) : '—';
$ua_raw   = $current_session['user_agent'] ?? '';
$browser  = 'Desconhecido';
if (str_contains($ua_raw, 'Edg'))     $browser = 'Microsoft Edge';
elseif (str_contains($ua_raw, 'Chrome'))  $browser = 'Google Chrome';
elseif (str_contains($ua_raw, 'Firefox')) $browser = 'Mozilla Firefox';
elseif (str_contains($ua_raw, 'Safari'))  $browser = 'Safari';
elseif (str_contains($ua_raw, 'Opera'))   $browser = 'Opera';
$sess_location = trim(($current_session['city'] ?? '') . ', ' . ($current_session['country'] ?? ''), ', ') ?: 'Desconhecida';
$sess_ip       = $current_session['ip_address'] ?? ($sec['last_login_ip'] ?? '—');

$first_name       = htmlspecialchars($user['first_name']);
$user_artist_name = htmlspecialchars($user['name_artist_band'] ?? $user['first_name']);

// ── Carregar preferências de notificação ──────────────
try {
    $pq = $db->prepare("SELECT * FROM _user_settings WHERE id_users = ? LIMIT 1");
    $pq->execute([$id_users]);
    $prefs = $pq->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $prefs = [];
}

// Fallback para colunas de _users se _user_settings não existir
$pref_email    = (int)($prefs['notif_email']    ?? $user['notif_email']    ?? 1);
$pref_push     = (int)($prefs['notif_push']     ?? $user['notif_push']     ?? 0);
$pref_streams  = (int)($prefs['notif_streams']  ?? 1);
$pref_weekly   = (int)($prefs['notif_weekly']   ?? $user['notif_weekly']   ?? 1);
$pref_releases = (int)($prefs['notif_releases'] ?? $user['notif_releases'] ?? 1);
$pref_payments = (int)($prefs['notif_payments'] ?? $user['notif_payments'] ?? 1);

// ── Buscar notificações do utilizador + broadcasts ────
try {
    // Notificações pessoais + broadcasts dirigidos a este user
    $nq = $db->prepare("
        SELECT id_notification AS id, id_users, type, title, body, action_url,
               is_read, read_at, is_broadcast, creat_notification AS creat,
               'notification' AS source
        FROM _notification
        WHERE id_users = ?
           OR (is_broadcast = 1 AND id_users IS NULL)
        ORDER BY is_read ASC, creat_notification DESC
        LIMIT 80
    ");
    $nq->execute([$id_users]);
    $notifications = $nq->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $notifications = [];
}

// ── Buscar broadcasts do admin com estado de leitura ──
try {
    $bq = $db->prepare("
        SELECT b.id_broadcast AS id, b.type, b.message AS body,
               b.creat_broadcast AS creat,
               COALESCE(br.is_read, 0) AS is_read,
               br.read_at,
               1 AS is_broadcast,
               'broadcast' AS source
        FROM _broadcast b
        LEFT JOIN _broadcast_receipt br
               ON br.id_broadcast = b.id_broadcast AND br.id_users = ?
        WHERE b.audience = 'all'
           OR (b.audience = 'country' AND b.audience_value = ?)
        ORDER BY b.creat_broadcast DESC
        LIMIT 20
    ");
    $bq->execute([$id_users, $user['country_user'] ?? 'AO']);
    $broadcasts = $bq->fetchAll(PDO::FETCH_ASSOC);

    // Normalizar broadcasts para ter título
    foreach ($broadcasts as &$b) {
        $b['title']      = 'Comunicado da Equipa Wasom Upfy';
        $b['action_url'] = null;
        $b['id_users']   = $id_users;
        $b['id']         = 'b_' . $b['id'];   // prefix para distinguir no AJAX
    }
    unset($b);
} catch (PDOException $e) {
    $broadcasts = [];
}

// Merge e sort por data desc, não lidas primeiro
$all = array_merge($notifications, $broadcasts);
usort($all, function ($a, $b) {
    if ($a['is_read'] !== $b['is_read']) return $a['is_read'] - $b['is_read'];
    return strtotime($b['creat']) - strtotime($a['creat']);
});

// Contagens
$total_count  = count($all);
$unread_count = count(array_filter($all, fn($n) => !$n['is_read']));
$read_count   = $total_count - $unread_count;

// ── Helper: ícone e cor por tipo ──────────────────────
function notif_icon(string $type): array
{
    return match ($type) {
        'success'   => ['bi-check-circle-fill',      'icon-success'],
        'warning'   => ['bi-exclamation-triangle-fill', 'icon-warning'],
        'error'     => ['bi-x-circle-fill',           'icon-error'],
        'payment'   => ['bi-currency-dollar',         'icon-payment'],
        'music'     => ['bi-disc-fill',               'icon-music'],
        'system'    => ['bi-gear-fill',               'icon-system'],
        'broadcast' => ['bi-broadcast',               'icon-broadcast'],
        default     => ['bi-info-circle-fill',        'icon-info'],
    };
}

function notif_badge(string $type): string
{
    return match ($type) {
        'music'     => '<span class="notif-badge badge-music"><i class="bi bi-music-note me-1"></i>Música</span>',
        'payment'   => '<span class="notif-badge badge-payment"><i class="bi bi-cash me-1"></i>Pagamento</span>',
        'system'    => '<span class="notif-badge badge-system"><i class="bi bi-gear me-1"></i>Sistema</span>',
        'warning'   => '<span class="notif-badge badge-warning"><i class="bi bi-exclamation me-1"></i>Aviso</span>',
        'error'     => '<span class="notif-badge badge-error"><i class="bi bi-x me-1"></i>Erro</span>',
        'success'   => '<span class="notif-badge badge-success"><i class="bi bi-check me-1"></i>Sucesso</span>',
        'broadcast' => '<span class="notif-badge badge-broadcast"><i class="bi bi-broadcast me-1"></i>Comunicado</span>',
        default     => '<span class="notif-badge badge-info"><i class="bi bi-info me-1"></i>Info</span>',
    };
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Agora mesmo';
    if ($diff < 3600)   return 'Há ' . floor($diff / 60)   . ' min';
    if ($diff < 86400)  return 'Há ' . floor($diff / 3600) . 'h';
    if ($diff < 604800) return 'Há ' . floor($diff / 86400) . ' dia' . (floor($diff / 86400) > 1 ? 's' : '');
    return date('d/m/Y H:i', strtotime($datetime));
}

function date_group(string $datetime): string
{
    $ts   = strtotime($datetime);
    $now  = time();
    $diff = $now - $ts;
    if ($diff < 86400)        return 'Hoje';
    if ($diff < 86400 * 7)      return 'Esta semana';
    if ($diff < 86400 * 30)     return 'Este mês';
    if ($diff < 86400 * 365)    return date('F Y', $ts);
    return 'Anteriores';
}

// Agrupar por data
$grouped = [];
foreach ($all as $n) {
    $g = date_group($n['creat']);
    $grouped[$g][] = $n;
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title>Notificações — <?php echo APP_NAME; ?></title>
    <style>
    /* ══ Hero ══ */
    .notif-hero {
        background: linear-gradient(135deg, #FF0089 0%, #c8006e 55%, #7b0044 100%);
        border-radius: 20px;
        padding: 2rem 2.4rem;
        margin-bottom: 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
    }

    .notif-hero::after {
        content: '\F0B5';
        font-family: 'bootstrap-icons';
        position: absolute;
        right: -15px;
        bottom: -25px;
        font-size: 9rem;
        opacity: .07;
    }

    .notif-hero .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, .18);
        border: 1px solid rgba(255, 255, 255, .3);
        border-radius: 999px;
        padding: 4px 14px;
        font-size: .76rem;
        font-weight: 700;
        backdrop-filter: blur(4px);
        margin-bottom: .7rem;
    }

    .notif-hero h1 {
        font-size: 1.9rem;
        font-weight: 800;
        margin-bottom: .3rem;
    }

    .notif-hero p {
        opacity: .85;
        font-size: .92rem;
        margin: 0;
    }

    /* ══ Quick action bar ══ */
    .quick-bar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 1.4rem;
    }

    .quick-bar .btn {
        border-radius: 10px;
        font-size: .8rem;
        font-weight: 600;
        padding: .45rem 1rem;
    }

    /* ══ Filter tabs ══ */
    .filter-tabs {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }

    .btn-filter {
        padding: .38rem 1rem;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 700;
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .12));
        background: var(--card-bg, #fff);
        color: var(--text-muted, #6c757d);
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
    }

    .btn-filter:hover {
        border-color: #FF0089;
        color: #FF0089;
    }

    .btn-filter.active {
        background: #FF0089;
        border-color: #FF0089;
        color: #fff;
    }

    .btn-filter .badge {
        font-size: .65rem;
        padding: .2rem .45rem;
    }

    /* ══ Date group header ══ */
    .notif-group-date {
        font-size: .72rem;
        font-weight: 800;
        color: var(--text-muted, #6c757d);
        text-transform: uppercase;
        letter-spacing: .08em;
        padding: .5rem 0 .4rem;
        margin-top: .4rem;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .07));
        margin-bottom: .4rem;
    }

    /* ══ Notification card ══ */
    .notification-card {
        background: var(--card-bg, #fff);
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .07));
        border-left: 4px solid transparent;
        border-radius: 14px;
        padding: 1rem 1.2rem;
        margin-bottom: .6rem;
        cursor: pointer;
        transition: all .18s;
        position: relative;
    }

    .notification-card:hover {
        box-shadow: 0 4px 16px rgba(255, 0, 137, .1);
        border-left-color: #FF0089;
        transform: translateX(2px);
    }

    .notification-card.unread {
        border-left-color: #FF0089;
        background: var(--card-bg, #fff);
    }

    .notification-card.unread::before {
        content: '';
        position: absolute;
        top: 14px;
        right: 14px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #FF0089;
        box-shadow: 0 0 0 2px rgba(255, 0, 137, .2);
    }

    /* ══ Notification icon ══ */
    .notif-icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .icon-info {
        background: rgba(13, 110, 253, .12);
        color: #0d6efd;
    }

    .icon-success {
        background: rgba(25, 135, 84, .12);
        color: #198754;
    }

    .icon-warning {
        background: rgba(255, 193, 7, .15);
        color: #856404;
    }

    .icon-error {
        background: rgba(220, 53, 69, .12);
        color: #dc3545;
    }

    .icon-payment {
        background: rgba(255, 193, 7, .15);
        color: #fd7e14;
    }

    .icon-music {
        background: rgba(255, 0, 137, .1);
        color: #FF0089;
    }

    .icon-system {
        background: rgba(108, 117, 125, .12);
        color: #6c757d;
    }

    .icon-broadcast {
        background: rgba(111, 66, 193, .12);
        color: #6f42c1;
    }

    /* ══ Card content ══ */
    .notif-title {
        font-weight: 700;
        font-size: .88rem;
        margin-bottom: .2rem;
    }

    .notif-body {
        font-size: .8rem;
        color: var(--text-muted, #6c757d);
        margin-bottom: .4rem;
        display: -webkit-box;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .notif-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .notif-time {
        font-size: .72rem;
        color: var(--text-muted, #6c757d);
    }

    /* ══ Badge types ══ */
    .notif-badge {
        font-size: .65rem;
        font-weight: 700;
        padding: .2rem .6rem;
        border-radius: 999px;
    }

    .badge-music {
        background: rgba(255, 0, 137, .12);
        color: #FF0089;
    }

    .badge-payment {
        background: rgba(253, 126, 20, .12);
        color: #fd7e14;
    }

    .badge-system {
        background: rgba(108, 117, 125, .12);
        color: #6c757d;
    }

    .badge-warning {
        background: rgba(255, 193, 7, .18);
        color: #856404;
    }

    .badge-error {
        background: rgba(220, 53, 69, .12);
        color: #dc3545;
    }

    .badge-success {
        background: rgba(25, 135, 84, .12);
        color: #198754;
    }

    .badge-info {
        background: rgba(13, 110, 253, .1);
        color: #0d6efd;
    }

    .badge-broadcast {
        background: rgba(111, 66, 193, .12);
        color: #6f42c1;
    }

    /* ══ Card action buttons ══ */
    .card-actions {
        display: flex;
        gap: 4px;
        margin-left: auto;
        flex-shrink: 0;
        align-self: flex-start;
    }

    .action-btn {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        border: none;
        background: var(--metric-bg, rgba(0, 0, 0, .04));
        color: var(--text-muted, #6c757d);
        font-size: .85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all .15s;
        cursor: pointer;
    }

    .action-btn:hover {
        background: #FF0089;
        color: #fff;
    }

    .action-btn.danger:hover {
        background: #dc3545;
    }

    /* ══ Empty state ══ */
    .notif-empty {
        text-align: center;
        padding: 3rem 1rem;
        display: none;
    }

    .notif-empty i {
        font-size: 3rem;
        color: #FF0089;
        opacity: .3;
        display: block;
        margin-bottom: 1rem;
    }

    /* ══ Settings card ══ */
    .settings-card {
        background: var(--card-bg, #fff);
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        border-radius: 16px;
        padding: 1.3rem;
        margin-bottom: 1rem;
    }

    .settings-card h6 {
        font-weight: 800;
        font-size: .9rem;
        color: #FF0089;
        margin-bottom: 1rem;
    }

    /* ══ Preference row ══ */
    .pref-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .6rem 0;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .06));
    }

    .pref-row:last-of-type {
        border-bottom: none;
    }

    .pref-row span {
        font-size: .84rem;
        font-weight: 600;
    }

    .pref-row small {
        font-size: .73rem;
        color: var(--text-muted, #6c757d);
    }

    .form-check-input:checked {
        background-color: #FF0089;
        border-color: #FF0089;
    }

    /* ══ Push notification permission card ══ */
    .push-card {
        background: linear-gradient(135deg, rgba(255, 0, 137, .07), rgba(200, 0, 110, .04));
        border: 1.5px solid rgba(255, 0, 137, .2);
        border-radius: 14px;
        padding: 1.2rem;
        margin-bottom: 1rem;
        text-align: center;
    }

    .push-card i {
        font-size: 2rem;
        color: #FF0089;
        display: block;
        margin-bottom: .6rem;
    }

    .push-card h6 {
        font-weight: 800;
        font-size: .88rem;
        margin-bottom: .4rem;
    }

    .push-card p {
        font-size: .78rem;
        color: var(--text-muted, #6c757d);
        margin-bottom: .8rem;
    }

    .btn-push {
        background: #FF0089;
        border: none;
        color: #fff;
        border-radius: 10px;
        font-size: .82rem;
        font-weight: 700;
        padding: .5rem 1.2rem;
        transition: all .2s;
    }

    .btn-push:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(255, 0, 137, .35);
        color: #fff;
    }

    .btn-push:disabled {
        opacity: .6;
    }

    /* ══ Modal notification ══ */
    #notificationModal .modal-header {
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .08));
    }

    #notificationModal .modal-footer {
        border-top: 1px solid var(--border-color, rgba(0, 0, 0, .08));
    }

    .modal-notif-icon {
        width: 54px;
        height: 54px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin: 0 auto 1rem;
    }

    .modal-action-area {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .btn-action-primary {
        background: #FF0089;
        border: none;
        color: #fff;
        padding: .45rem 1.2rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: .82rem;
        transition: all .2s;
    }

    .btn-action-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(255, 0, 137, .3);
        color: #fff;
    }

    .btn-action-later {
        background: transparent;
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .12));
        color: var(--text-muted, #6c757d);
        padding: .45rem 1.2rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: .82rem;
        transition: all .15s;
    }

    .btn-action-later:hover {
        border-color: #FF0089;
        color: #FF0089;
    }

    /* ══ Progress bar read ══ */
    .read-ratio-bar {
        height: 6px;
        border-radius: 999px;
        overflow: hidden;
        background: var(--border-color, rgba(0, 0, 0, .08));
    }

    .read-ratio-fill {
        height: 100%;
        background: linear-gradient(90deg, #FF0089, #c8006e);
        border-radius: 999px;
        transition: width .4s;
    }

    @media(max-width:768px) {
        .notif-hero {
            padding: 1.4rem;
        }

        .notif-hero h1 {
            font-size: 1.5rem;
        }
    }
    </style>
</head>

<body>
    <!-- ═══ NAVBAR ═══ -->
    <?php require_once __DIR__ . '/../include/sidebar.php'; ?>
    <!-- ═══ MAIN ═══ -->
    <div class="container my-4">
        <?php /* ============================================
    BANNERS DE NOTIFICACAO DO PAINEL
    Estilo: inline CSS consistente com renderDashboardAlerts().
    Bootstrap alert nativo removido — um único sistema visual.
    Lógica de prioridade:
      Nível 1 (danger)  — bloqueia distribuição
      Nível 2 (warning) — importante, requer atenção
      Nível 3 (info)    — informativo / acção opcional
    ============================================ */ ?>

        <?php renderDashboardAlerts($user, $platform); ?>

        <?php
        // Cor map para helpers inline — idêntico ao renderDashboardAlerts()
        $alertColors = [
            'danger'  => ['bg' => 'rgba(239,68,68,.08)',  'border' => 'rgba(239,68,68,.25)',  'text' => '#ef4444'],
            'warning' => ['bg' => 'rgba(234,179,8,.08)',  'border' => 'rgba(234,179,8,.25)',  'text' => '#eab308'],
            'info'    => ['bg' => 'rgba(99,102,241,.08)', 'border' => 'rgba(99,102,241,.25)', 'text' => '#6366f1'],
        ];
        function wuAlert(string $type, string $icon, string $message, ?array $action = null, bool $dismiss = true, string $id = ''): void
        {
            global $alertColors;
            $c   = $alertColors[$type] ?? $alertColors['info'];
            $eid = $id ?: ('wuPanelAlert_' . md5($message));
            echo "<div id=\"{$eid}\" style=\"display:flex;align-items:flex-start;gap:10px;"
                . "background:{$c['bg']};border:1px solid {$c['border']};border-radius:12px;"
                . "padding:.75rem 1rem;font-size:.83rem;margin-bottom:.6rem;"
                . "transition:opacity .3s;\">";
            echo "<i class=\"bi {$icon}\" style=\"font-size:1rem;flex-shrink:0;margin-top:2px;color:{$c['text']};\"></i>";
            echo '<span class="wu-alert-msg">' . $message;
            if ($action) {
                echo " <a href=\"{$action['url']}\" style=\"color:{$c['text']};font-weight:700;"
                    . "text-decoration:underline;white-space:nowrap\">{$action['label']} &rarr;</a>";
            }
            echo '</span>';
            if ($dismiss) {
                echo "<button type=\"button\" class=\"wu-alert-dismiss\" aria-label=\"Fechar\""
                    . " onclick=\"(function(el){el.style.opacity='0';"
                    . "setTimeout(function(){el.style.display='none'},300)})(document.getElementById('{$eid}'))\">"
                    . "&times;</button>";
            }
            echo '</div>';
        }
        ?>

        <?php /* ── NÍVEL 1: Crítico — bloqueia distribuição ── */ ?>

        <?php if (!$email_verified): ?>
        <?php wuAlert(
                'danger',
                'bi-envelope-exclamation-fill',
                '<strong>Email não verificado.</strong> Verifica o teu e-mail para garantir o acesso à conta e receber notificações de pagamentos.',
                ['label' => 'Verificar agora', 'url' => APP_URL . '/' . APP_URL_PANEL . '/user/profile#perfil'],
                true,
                'banner-email'
            ); ?>
        <?php endif; ?>

        <?php if ($plan && !$plan_paid): ?>
        <?php wuAlert(
                'warning',
                'bi-clock-history',
                '<strong>Pagamento pendente — ' . htmlspecialchars($plan['name_plan']) . '.</strong> O plano foi seleccionado mas o pagamento ainda não foi confirmado. Os teus lançamentos estão pausados até confirmação.',
                ['label' => 'Finalizar pagamento', 'url' => APP_URL . '/' . APP_URL_PANEL . '/payment/pay'],
                true,
                'banner-plan-pending'
            ); ?>
        <?php elseif (!$plan): ?>
        <?php wuAlert(
                'danger',
                'bi-credit-card-fill',
                '<strong>Sem plano activo.</strong> Escolhe um plano para começar a distribuir a tua música para +150 plataformas.',
                ['label' => 'Ver planos', 'url' => APP_URL . '/' . APP_URL_PANEL . '/all-plans'],
                false,
                'banner-plan'
            ); ?>
        <?php endif; ?>

        <?php /* ── NÍVEL 2: Importante — perfil incompleto ── */ ?>

        <?php if ($plan_paid && !$has_artist): ?>
        <?php wuAlert(
                'info',
                'bi-person-plus-fill',
                '<strong>Cria o teu perfil de artista.</strong> Tens plano activo mas ainda não criaste um perfil. Precisas de um para poder lançar música.',
                ['label' => 'Criar agora', 'url' => APP_URL . '/' . APP_URL_PANEL . '/add-artist'],
                true,
                'banner-artist'
            ); ?>
        <?php endif; ?>

        <?php /* ── NÍVEL 3: Informativo — conta bancária ── */ ?>

        <?php if ($plan_paid && $has_artist && !$bank_account): ?>
        <?php wuAlert(
                'info',
                'bi-bank',
                '<strong>Conta bancária não registada.</strong> Para poder sacar os teus royalties, regista uma conta IBAN ou Multicaixa Express.',
                ['label' => 'Registar agora', 'url' => APP_URL . '/' . APP_URL_PANEL . '/withdraw'],
                true,
                'banner-bank'
            ); ?>
        <?php endif; ?>

        <?php /* ── NÍVEL 3: Conta bancária rejeitada ── */ ?>

        <?php
        $rejected_account = null;
        if ($plan_paid) {
            $rej_stmt = getDB()->prepare("SELECT type_account, reject_reason FROM _account WHERE id_users = ? AND status_account = 'rejected' LIMIT 1");
            $rej_stmt->execute([$id_users]);
            $rejected_account = $rej_stmt->fetch();
        }
        ?>
        <?php if ($rejected_account): ?>
        <?php
            $rej_msg = '<strong>Conta ' . htmlspecialchars($rejected_account['type_account']) . ' rejeitada.</strong>';
            if ($rejected_account['reject_reason']) {
                $rej_msg .= ' Motivo: <em>' . htmlspecialchars($rejected_account['reject_reason']) . '</em>.';
            }
            $rej_msg .= ' Actualiza os dados e submete novamente.';
            wuAlert(
                'danger',
                'bi-x-circle-fill',
                $rej_msg,
                ['label' => 'Corrigir agora', 'url' => APP_URL . '/' . APP_URL_PANEL . '/withdraw'],
                true,
                'banner-account-rejected'
            );
            ?>
        <?php endif; ?>
        <!-- HERO -->
        <div class="notif-hero">
            <div class="hero-badge">
                <i class="bi bi-bell-fill"></i>
                <?php if ($unread_count > 0): ?>
                <?php echo $unread_count; ?> não lida<?php echo $unread_count !== 1 ? 's' : ''; ?>
                <?php else: ?>
                Tudo em dia!
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h1 class="fw-bold mb-1"><i class="bi bi-bell-fill me-2"></i>Notificações</h1>
                    <p>Fica a par de todas as novidades, actualizações e movimentações da tua conta.</p>
                </div>
                <div class="text-center">
                    <div style="font-size:2rem;font-weight:900;line-height:1"><?php echo $total_count; ?></div>
                    <small style="opacity:.75;font-size:.72rem">total</small>
                </div>
            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="quick-bar">
            <button class="btn btn-outline-secondary btn-sm" id="btnMarkAll">
                <i class="bi bi-check2-all me-1"></i>Marcar todas como lidas
            </button>
            <button class="btn btn-outline-secondary btn-sm" id="btnDeleteAll">
                <i class="bi bi-trash me-1"></i>Limpar todas
            </button>
            <button class="btn btn-outline-secondary btn-sm" id="btnRefresh">
                <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
            </button>
        </div>

        <!-- FILTER TABS -->
        <div class="filter-tabs">
            <button class="btn-filter active" data-filter="all">
                <i class="bi bi-bell me-1"></i>Todas
                <span class="badge bg-secondary ms-1" id="countAll"><?php echo $total_count; ?></span>
            </button>
            <button class="btn-filter" data-filter="unread">
                <i class="bi bi-envelope me-1"></i>Não lidas
                <span class="badge bg-danger ms-1" id="countUnread"><?php echo $unread_count; ?></span>
            </button>
            <button class="btn-filter" data-filter="music">
                <i class="bi bi-music-note me-1"></i>Música
            </button>
            <button class="btn-filter" data-filter="payment">
                <i class="bi bi-currency-dollar me-1"></i>Pagamentos
            </button>
            <button class="btn-filter" data-filter="system">
                <i class="bi bi-gear me-1"></i>Sistema
            </button>
            <button class="btn-filter" data-filter="broadcast">
                <i class="bi bi-broadcast me-1"></i>Comunicados
            </button>
        </div>

        <div class="row g-4">

            <!-- ══ LISTA DE NOTIFICAÇÕES ══ -->
            <div class="col-lg-8">
                <div id="notifList">
                    <?php if (empty($all)): ?>
                    <div class="notif-empty" style="display:block">
                        <i class="bi bi-bell-slash"></i>
                        <p class="fw-semibold mb-1">Sem notificações</p>
                        <small class="text-muted">Quando houver novidades, aparecerão aqui.</small>
                    </div>
                    <?php else: ?>

                    <?php
                        foreach ($grouped as $groupName => $items):
                        ?>
                    <div class="notif-group-date group-label" data-group="<?php echo htmlspecialchars($groupName); ?>">
                        <i class="bi bi-calendar3 me-2"></i><?php echo htmlspecialchars($groupName); ?>
                    </div>

                    <?php foreach ($items as $n):
                                [$icon, $iconClass] = notif_icon($n['type']);
                                $isUnread  = !$n['is_read'];
                                $nid       = htmlspecialchars($n['id']);
                                $source    = $n['source'];
                                $title     = htmlspecialchars($n['title']);
                                $body      = htmlspecialchars($n['body']);
                                $bodyShort = mb_strimwidth(strip_tags($n['body']), 0, 110, '…');
                                $ago       = time_ago($n['creat']);
                                $badge     = notif_badge($n['type']);
                                $actionUrl = htmlspecialchars($n['action_url'] ?? '');
                            ?>
                    <div class="notification-card <?php echo $isUnread ? 'unread' : ''; ?>"
                        data-id="<?php echo $nid; ?>" data-source="<?php echo $source; ?>"
                        data-type="<?php echo htmlspecialchars($n['type']); ?>" data-title="<?php echo $title; ?>"
                        data-body="<?php echo $body; ?>" data-ago="<?php echo $ago; ?>"
                        data-action="<?php echo $actionUrl; ?>" data-read="<?php echo $isUnread ? '0' : '1'; ?>">

                        <div class="d-flex gap-3 align-items-start">
                            <div class="notif-icon-wrap <?php echo $iconClass; ?>">
                                <i class="bi <?php echo $icon; ?>"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="notif-title"><?php echo $title; ?></div>
                                <div class="notif-body"><?php echo htmlspecialchars($bodyShort); ?></div>
                                <div class="notif-meta">
                                    <?php echo $badge; ?>
                                    <span class="notif-time"><i class="bi bi-clock me-1"></i><?php echo $ago; ?></span>
                                </div>
                            </div>
                            <div class="card-actions">
                                <!-- Toggle lida/não lida -->
                                <button class="action-btn btn-toggle-read"
                                    title="<?php echo $isUnread ? 'Marcar como lida' : 'Marcar como não lida'; ?>">
                                    <i class="bi <?php echo $isUnread ? 'bi-check-lg' : 'bi-envelope'; ?>"></i>
                                </button>
                                <!-- Eliminar -->
                                <button class="action-btn btn-delete danger" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>

                    <div class="notif-empty" id="emptyState">
                        <i class="bi bi-search"></i>
                        <p class="fw-semibold mb-1">Sem resultados</p>
                        <small class="text-muted">Nenhuma notificação nesta categoria.</small>
                    </div>

                    <?php endif; ?>
                </div>
            </div>

            <!-- ══ SIDEBAR ══ -->
            <div class="col-lg-4">

                <!-- Push Notification Permission -->
                <div class="push-card" id="pushCard">
                    <i class="bi bi-bell-fill"></i>
                    <h6>Activar Notificações Push</h6>
                    <p>Recebe notificações em tempo real no teu dispositivo — mesmo com o browser fechado.</p>
                    <button class="btn-push btn" id="btnEnablePush">
                        <i class="bi bi-bell-fill me-2"></i>Activar Notificações
                    </button>
                    <div id="pushStatus" class="mt-2"
                        style="font-size:.76rem;color:var(--text-muted,#6c757d);display:none"></div>
                </div>

                <!-- Resumo -->
                <div class="settings-card">
                    <h6><i class="bi bi-pie-chart me-2"></i>Resumo</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span style="font-size:.83rem;color:var(--text-muted,#6c757d)">Total</span>
                        <span class="fw-bold" id="statTotal"><?php echo $total_count; ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span style="font-size:.83rem;color:var(--text-muted,#6c757d)">Não lidas</span>
                        <span class="fw-bold text-danger" id="statUnread"><?php echo $unread_count; ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span style="font-size:.83rem;color:var(--text-muted,#6c757d)">Lidas</span>
                        <span class="fw-bold" id="statRead"><?php echo $read_count; ?></span>
                    </div>
                    <?php if ($total_count > 0): ?>
                    <div class="read-ratio-bar">
                        <div class="read-ratio-fill" id="ratioFill"
                            style="width:<?php echo round($read_count / $total_count * 100); ?>%"></div>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size:.72rem">
                        <?php echo round($read_count / $total_count * 100); ?>% lidas
                    </small>
                    <?php endif; ?>
                </div>

                <!-- Preferências -->
                <div class="settings-card">
                    <h6><i class="bi bi-sliders me-2"></i>Preferências</h6>

                    <div class="pref-row">
                        <div>
                            <span>Notificações push</span>
                            <small class="d-block">No dispositivo, mesmo offline</small>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input pref-switch" type="checkbox" id="prefPush"
                                data-pref="notif_push" <?php echo $pref_push ? 'checked' : ''; ?> />
                        </div>
                    </div>
                    <div class="pref-row">
                        <div>
                            <span>Notificações por e-mail</span>
                            <small class="d-block">Resumos e alertas importantes</small>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input pref-switch" type="checkbox" id="prefEmail"
                                data-pref="notif_email" <?php echo $pref_email ? 'checked' : ''; ?> />
                        </div>
                    </div>
                    <div class="pref-row">
                        <div>
                            <span>Streams</span>
                            <small class="d-block">Novas reproduções nas tuas músicas</small>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input pref-switch" type="checkbox" id="prefStreams"
                                data-pref="notif_streams" <?php echo $pref_streams ? 'checked' : ''; ?> />
                        </div>
                    </div>
                    <div class="pref-row">
                        <div>
                            <span>Lançamentos</span>
                            <small class="d-block">Estado dos teus lançamentos</small>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input pref-switch" type="checkbox" id="prefReleases"
                                data-pref="notif_releases" <?php echo $pref_releases ? 'checked' : ''; ?> />
                        </div>
                    </div>
                    <div class="pref-row">
                        <div>
                            <span>Pagamentos</span>
                            <small class="d-block">Royalties, levantamentos, planos</small>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input pref-switch" type="checkbox" id="prefPayments"
                                data-pref="notif_payments" <?php echo $pref_payments ? 'checked' : ''; ?> />
                        </div>
                    </div>
                    <div class="pref-row">
                        <div>
                            <span>Resumo semanal</span>
                            <small class="d-block">Relatório automático às sextas</small>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input pref-switch" type="checkbox" id="prefWeekly"
                                data-pref="notif_weekly" <?php echo $pref_weekly ? 'checked' : ''; ?> />
                        </div>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-sm w-100 fw-bold" id="btnSavePrefs"
                            style="background:#FF0089;color:#fff;border-radius:10px">
                            <i class="bi bi-save me-2"></i>Guardar preferências
                        </button>
                    </div>
                </div>

                <!-- Atalhos rápidos -->
                <div class="settings-card">
                    <h6><i class="bi bi-lightning-charge me-2"></i>Atalhos Rápidos</h6>
                    <div class="d-grid gap-2">
                        <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/overview"
                            class="btn btn-sm btn-outline-secondary text-start"
                            style="border-radius:9px;font-weight:600">
                            <i class="bi bi-currency-dollar me-2"></i>Verificar receitas
                        </a>
                        <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/statistics"
                            class="btn btn-sm btn-outline-secondary text-start"
                            style="border-radius:9px;font-weight:600">
                            <i class="bi bi-bar-chart me-2"></i>Ver estatísticas
                        </a>
                        <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/creat-release"
                            class="btn btn-sm btn-outline-secondary text-start"
                            style="border-radius:9px;font-weight:600">
                            <i class="bi bi-plus-circle me-2"></i>Novo lançamento
                        </a>
                        <a href="support" class="btn btn-sm btn-outline-secondary text-start"
                            style="border-radius:9px;font-weight:600">
                            <i class="bi bi-headset me-2"></i>Enviar suporte
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ══ Modal: Detalhe da Notificação ══ -->
    <div class="modal fade" id="notificationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalNotifTitle">Detalhes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalNotifBody">
                    <!-- preenchido por JS -->
                </div>
                <div class="modal-footer justify-content-between">
                    <div id="modalToggleBtn">
                        <!-- botão marcar/desmarcar como lida -->
                    </div>
                    <div class="d-flex gap-2" id="modalActionBtns">
                        <!-- botões de acção dependendo do tipo -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ Modal: Confirmação de acção ══ -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmTitle">Confirmar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="confirmMsg">Tens a certeza?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger btn-sm" id="confirmOkBtn">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <script>
    // ════════════════════════════════════════════════════
    // CSRF token para todos os pedidos AJAX
    // ════════════════════════════════════════════════════
    const CSRF_TOKEN = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;
    const BASE_URL = <?php echo rtrim(APP_URL, '/' . APP_URL_PANEL); ?>;
    const API_URL = BASE_URL + '/ajax/notifications_api';

    // ════════════════════════════════════════════════════
    // VAPID PUBLIC KEY (configurar no servidor)
    // ════════════════════════════════════════════════════
    const VAPID_PUBLIC_KEY = <?php echo json_encode(defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : ''); ?>;

    document.addEventListener('DOMContentLoaded', function() {

        // ── Bootstrap modais ──────────────────────────────
        const notifModal = new bootstrap.Modal(document.getElementById('notificationModal'));
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        const feedToast = new bootstrap.Toast(document.getElementById('feedbackToast'), {
            delay: 3000
        });

        // ── Toast helper ──────────────────────────────────
        function toast(msg, isOk = true) {
            var toastEl = document.getElementById('feedbackToast');
            var msgEl = document.getElementById('feedbackToastMsg');
            msgEl.textContent = msg;
            toastEl.style.background = isOk ? 'rgba(25,135,84,.95)' : 'rgba(220,53,69,.95)';
            toastEl.style.color = '#fff';
            feedToast.show();
        }

        // ── AJAX helper ───────────────────────────────────
        async function api(data) {
            data.csrf_token = CSRF_TOKEN;
            try {
                var res = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams(data),
                    credentials: 'same-origin'
                });
                return await res.json();
            } catch (e) {
                return {
                    ok: false,
                    message: 'Erro de rede.'
                };
            }
        }

        // ── Filtros ───────────────────────────────────────
        document.querySelectorAll('.btn-filter').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.btn-filter').forEach(function(b) {
                    b.classList.remove('active');
                });
                this.classList.add('active');
                var filter = this.dataset.filter;
                var cards = document.querySelectorAll('.notification-card');
                var visible = 0;

                cards.forEach(function(card) {
                    var show = filter === 'all' ||
                        (filter === 'unread' && card.dataset.read === '0') ||
                        card.dataset.type === filter;
                    card.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                // Esconder/mostrar group labels
                document.querySelectorAll('.group-label').forEach(function(lbl) {
                    var next = lbl.nextElementSibling;
                    var hasVisible = false;
                    while (next && !next.classList.contains('group-label')) {
                        if (next.classList.contains('notification-card') && next.style
                            .display !== 'none') {
                            hasVisible = true;
                            break;
                        }
                        next = next.nextElementSibling;
                    }
                    lbl.style.display = hasVisible ? '' : 'none';
                });

                document.getElementById('emptyState').style.display = visible === 0 ? 'block' :
                    'none';
            });
        });

        // ── Abrir modal ao clicar no card ─────────────────
        document.querySelectorAll('.notification-card').forEach(function(card) {
            card.addEventListener('click', function(e) {
                if (e.target.closest('.card-actions')) return;
                openNotifModal(card);
            });
        });

        function openNotifModal(card) {
            var id = card.dataset.id;
            var source = card.dataset.source;
            var type = card.dataset.type;
            var title = card.dataset.title;
            var body = card.dataset.body;
            var ago = card.dataset.ago;
            var action = card.dataset.action;
            var isRead = card.dataset.read === '1';

            document.getElementById('modalNotifTitle').textContent = title;

            // Ícone no modal
            var iconMap = {
                info: ['bi-info-circle-fill', 'icon-info'],
                success: ['bi-check-circle-fill', 'icon-success'],
                warning: ['bi-exclamation-triangle-fill', 'icon-warning'],
                error: ['bi-x-circle-fill', 'icon-error'],
                payment: ['bi-currency-dollar', 'icon-payment'],
                music: ['bi-disc-fill', 'icon-music'],
                system: ['bi-gear-fill', 'icon-system'],
                broadcast: ['bi-broadcast', 'icon-broadcast'],
            };
            var [ico, icoClass] = iconMap[type] || ['bi-bell-fill', 'icon-info'];

            document.getElementById('modalNotifBody').innerHTML =
                '<div class="text-center mb-3">' +
                '  <div class="modal-notif-icon ' + icoClass + ' mx-auto"><i class="bi ' + ico +
                '"></i></div>' +
                '</div>' +
                '<p class="text-muted small text-center mb-3"><i class="bi bi-clock me-1"></i>' + ago + '</p>' +
                '<p style="font-size:.9rem;line-height:1.7">' + body.replace(/\n/g, '<br>') + '</p>';

            // Botão toggle read
            document.getElementById('modalToggleBtn').innerHTML = isRead ?
                '<button class="btn btn-sm btn-outline-secondary" id="modalBtnToggleRead"><i class="bi bi-envelope me-1"></i>Marcar como não lida</button>' :
                '<button class="btn btn-sm btn-outline-secondary" id="modalBtnToggleRead"><i class="bi bi-check2 me-1"></i>Marcar como lida</button>';

            document.getElementById('modalBtnToggleRead').addEventListener('click', function() {
                if (isRead) {
                    doMarkUnread(id, source, card);
                } else {
                    doMarkRead(id, source, card);
                }
                notifModal.hide();
            });

            // Botões de acção dependendo do tipo
            var actionsHtml = '';
            if (action) {
                actionsHtml += '<a href="' + action +
                    '" class="btn-action-primary btn"><i class="bi bi-box-arrow-up-right me-1"></i>Ver agora</a>';
            }
            if (type === 'payment' || type === 'music') {
                actionsHtml +=
                    '<button class="btn-action-later btn" data-bs-dismiss="modal" id="modalBtnLater">Ver mais tarde</button>';
            }
            actionsHtml +=
                '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>';

            document.getElementById('modalActionBtns').innerHTML = actionsHtml;

            // "Ver mais tarde" — marca como não lida (volta ao estado não lido)
            var laterBtn = document.getElementById('modalBtnLater');
            if (laterBtn) {
                laterBtn.addEventListener('click', function() {
                    doMarkUnread(id, source, card);
                });
            }

            // Ao abrir o modal, se não lida, marca como lida automaticamente
            if (!isRead) {
                doMarkRead(id, source, card);
            }

            notifModal.show();
        }

        // ── Marcar como lida ──────────────────────────────
        async function doMarkRead(id, source, card) {
            var r = await api({
                action: 'mark_read',
                id: id,
                source: source
            });
            if (r.ok) {
                card.classList.remove('unread');
                card.dataset.read = '1';
                var tb = card.querySelector('.btn-toggle-read');
                if (tb) {
                    tb.title = 'Marcar como não lida';
                    tb.querySelector('i').className = 'bi bi-envelope';
                }
                updateCounts();
            }
        }

        // ── Marcar como não lida ──────────────────────────
        async function doMarkUnread(id, source, card) {
            var r = await api({
                action: 'mark_unread',
                id: id,
                source: source
            });
            if (r.ok) {
                card.classList.add('unread');
                card.dataset.read = '0';
                var tb = card.querySelector('.btn-toggle-read');
                if (tb) {
                    tb.title = 'Marcar como lida';
                    tb.querySelector('i').className = 'bi bi-check-lg';
                }
                updateCounts();
            }
        }

        // ── Botões inline dos cards ───────────────────────
        document.querySelectorAll('.notification-card').forEach(function(card) {
            var id = card.dataset.id;
            var source = card.dataset.source;

            // Toggle read/unread
            var tb = card.querySelector('.btn-toggle-read');
            if (tb) {
                tb.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (card.dataset.read === '0') {
                        doMarkRead(id, source, card);
                    } else {
                        doMarkUnread(id, source, card);
                    }
                });
            }

            // Delete
            var db = card.querySelector('.btn-delete');
            if (db) {
                db.addEventListener('click', function(e) {
                    e.stopPropagation();
                    confirmAction('Eliminar esta notificação?', async function() {
                        var r = await api({
                            action: 'delete_one',
                            id: id,
                            source: source
                        });
                        if (r.ok) {
                            card.style.transition = 'opacity .25s';
                            card.style.opacity = '0';
                            setTimeout(function() {
                                card.remove();
                                updateCounts();
                            }, 260);
                            toast('Notificação eliminada.');
                        } else {
                            toast(r.message, false);
                        }
                    });
                });
            }
        });

        // ── Marcar todas como lidas ───────────────────────
        document.getElementById('btnMarkAll').addEventListener('click', async function() {
            var r = await api({
                action: 'mark_all_read'
            });
            if (r.ok) {
                document.querySelectorAll('.notification-card.unread').forEach(function(c) {
                    c.classList.remove('unread');
                    c.dataset.read = '1';
                    var tb = c.querySelector('.btn-toggle-read');
                    if (tb) {
                        tb.title = 'Marcar como não lida';
                        tb.querySelector('i').className = 'bi bi-envelope';
                    }
                });
                updateCounts();
                toast('Todas as notificações marcadas como lidas.');
            } else {
                toast(r.message, false);
            }
        });

        // ── Limpar todas ──────────────────────────────────
        document.getElementById('btnDeleteAll').addEventListener('click', function() {
            confirmAction('Eliminar todas as notificações? Esta acção não pode ser desfeita.',
                async function() {
                    var r = await api({
                        action: 'delete_all'
                    });
                    if (r.ok) {
                        document.querySelectorAll('.notification-card').forEach(function(c) {
                            c.remove();
                        });
                        document.querySelectorAll('.group-label').forEach(function(g) {
                            g.remove();
                        });
                        document.getElementById('emptyState').style.display = 'block';
                        updateCounts();
                        toast('Todas as notificações eliminadas.');
                    } else {
                        toast(r.message, false);
                    }
                });
        });

        // ── Actualizar ────────────────────────────────────
        document.getElementById('btnRefresh').addEventListener('click', function() {
            location.reload();
        });

        // ── Helper confirmação ────────────────────────────
        function confirmAction(msg, cb) {
            document.getElementById('confirmMsg').textContent = msg;
            var btn = document.getElementById('confirmOkBtn');
            var newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            newBtn.addEventListener('click', function() {
                confirmModal.hide();
                cb();
            });
            confirmModal.show();
        }

        // ── Actualizar contadores ─────────────────────────
        function updateCounts() {
            var cards = document.querySelectorAll('.notification-card');
            var unread = document.querySelectorAll('.notification-card.unread');
            var total = cards.length;
            var unrdCnt = unread.length;
            var rdCnt = total - unrdCnt;

            document.getElementById('statTotal').textContent = total;
            document.getElementById('statUnread').textContent = unrdCnt;
            document.getElementById('statRead').textContent = rdCnt;
            document.getElementById('countAll').textContent = total;
            document.getElementById('countUnread').textContent = unrdCnt;

            // Barra
            var pct = total > 0 ? Math.round(rdCnt / total * 100) : 0;
            var fill = document.getElementById('ratioFill');
            if (fill) fill.style.width = pct + '%';

            // Badge navbar
            var badge = document.getElementById('navBadge');
            if (badge) {
                if (unrdCnt > 0) {
                    badge.textContent = unrdCnt > 99 ? '99+' : unrdCnt;
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
                }
            }
        }

        // ── Guardar preferências ──────────────────────────
        document.getElementById('btnSavePrefs').addEventListener('click', async function() {
            var prefs = {};
            document.querySelectorAll('.pref-switch').forEach(function(sw) {
                prefs[sw.dataset.pref] = sw.checked ? 1 : 0;
            });
            var r = await api(Object.assign({
                action: 'save_prefs'
            }, prefs));
            if (r.ok) {
                toast('Preferências guardadas!');
            } else {
                toast(r.message, false);
            }
        });

        // ── Push Notifications (Web Push API) ────────────
        var pushCard = document.getElementById('pushCard');
        var btnPush = document.getElementById('btnEnablePush');
        var pushStat = document.getElementById('pushStatus');

        function urlBase64ToUint8Array(base64String) {
            var padding = '='.repeat((4 - base64String.length % 4) % 4);
            var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            var rawData = window.atob(base64);
            var output = new Uint8Array(rawData.length);
            for (var i = 0; i < rawData.length; ++i) output[i] = rawData.charCodeAt(i);
            return output;
        }

        if ('serviceWorker' in navigator && 'PushManager' in window) {
            // Registar Service Worker
            navigator.serviceWorker.register('../sw.js')
                .then(function(reg) {
                    // Verificar se já tem permissão
                    if (Notification.permission === 'granted') {
                        pushCard.style.display = 'none'; // Já activado
                    }

                    btnPush.addEventListener('click', async function() {
                        if (VAPID_PUBLIC_KEY === 'SUBSTITUI_PELA_TUA_VAPID_PUBLIC_KEY') {
                            pushStat.style.display = '';
                            pushStat.textContent = 'VAPID key não configurada no servidor.';
                            return;
                        }
                        try {
                            var permission = await Notification.requestPermission();
                            if (permission !== 'granted') {
                                pushStat.style.display = '';
                                pushStat.textContent = 'Permissão negada pelo browser.';
                                return;
                            }
                            var subscription = await reg.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: urlBase64ToUint8Array(
                                    VAPID_PUBLIC_KEY)
                            });
                            var r = await api({
                                action: 'subscribe_push',
                                subscription: JSON.stringify(subscription)
                            });
                            if (r.ok) {
                                pushCard.style.display = 'none';
                                toast('Notificações push activadas!');
                                document.getElementById('prefPush').checked = true;
                            } else {
                                pushStat.style.display = '';
                                pushStat.textContent = r.message;
                            }
                        } catch (err) {
                            pushStat.style.display = '';
                            pushStat.textContent = 'Erro: ' + err.message;
                        }
                    });
                })
                .catch(function() {
                    pushCard.style.display = 'none'; // SW não disponível — esconde o card
                });
        } else {
            pushCard.style.display = 'none'; // Browser não suporta push
        }

        // ── Polling do badge (a cada 30s) ─────────────────
        async function pollBadge() {
            try {
                var r = await api({
                    action: 'get_count'
                });
                if (r.ok) {
                    var badge = document.getElementById('navBadge');
                    if (badge) {
                        if (r.count > 0) {
                            badge.textContent = r.count > 99 ? '99+' : r.count;
                            badge.style.display = '';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                }
            } catch (e) {}
        }
        setInterval(pollBadge, 30000);

    }); // fim DOMContentLoaded
    </script>
</body>

</html>