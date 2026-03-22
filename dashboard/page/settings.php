<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Configurações
// Arquivo: dashboard/page/settings.php
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


$first_name       = htmlspecialchars($user['first_name'] ?? '');
$last_name        = htmlspecialchars($user['second_name'] ?? '');
$user_artist_name = htmlspecialchars($user['name_artist_band'] ?? $user['first_name'] ?? '');
$email_user       = htmlspecialchars($user['email_user'] ?? '');

// ── Plano activo ─────────────────────────────
// Colunas correctas: started_at / expires_at (não start_date/end_date)
// Tabela correcta: _plans (não _plan)
$plan = null;
try {
    $plan_q = $db->prepare("
        SELECT p.name_plan, p.slug_plan, up.status_plan, up.started_at, up.expires_at
        FROM _user_plan up
        JOIN _plans p ON p.id_plan = up.id_plan
        WHERE up.id_users = ? AND up.status_plan = 'active'
        ORDER BY up.started_at DESC
        LIMIT 1
    ");
    $plan_q->execute([$id_users]);
    $plan = $plan_q->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $e) { /* seguro */
}

// Fallback: plan_selected é INT (FK para _plans), busca o nome
if (!$plan && !empty($user['plan_selected'])) {
    try {
        $p_q = $db->prepare("SELECT name_plan, slug_plan FROM _plans WHERE id_plan = ?");
        $p_q->execute([$user['plan_selected']]);
        $p_row = $p_q->fetch(PDO::FETCH_ASSOC);
        if ($p_row) {
            $plan = [
                'name_plan'   => $p_row['name_plan'],
                'slug_plan'   => $p_row['slug_plan'],
                'status_plan' => 'active',
                'started_at'  => $user['plan_activated_at'] ?? null,
                'expires_at'  => $user['plan_expires_at']   ?? null,
            ];
        }
    } catch (PDOException $e) { /* seguro */
    }
}

// ── Artistas (integrações) ────────────────────
try {
    $artists_q = $db->prepare("
        SELECT id_artist, stage_name, youtube_url, spotify_url, instagram_url
        FROM _artist
        WHERE id_users = ? AND status_artist = 'active'
        ORDER BY stage_name ASC
        LIMIT 5
    ");
    $artists_q->execute([$id_users]);
    $artists = $artists_q->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $artists = [];
}

// ── Carregar ou criar _user_settings ─────────
try {
    $settings_q = $db->prepare("SELECT * FROM _user_settings WHERE id_users = ?");
    $settings_q->execute([$id_users]);
    $settings = $settings_q->fetch(PDO::FETCH_ASSOC);
    if (!$settings) {
        $db->prepare("INSERT INTO _user_settings (id_users) VALUES (?) ON DUPLICATE KEY UPDATE id_users = id_users")
            ->execute([$id_users]);
        $settings_q->execute([$id_users]);
        $settings = $settings_q->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $settings = [];
}

$s = array_merge([
    'notif_email'      => 1,
    'notif_push'       => 0,
    'notif_streams'    => 0,
    'notif_weekly'      => 0,
    'theme'            => 'dark',
    'ui_density'   => 'compact',
    'widget_streams'   => 1,
    'widget_financial'  => 1,
    'widget_releases'  => 0,
    'widget_artists'    => 1,
    'widget_activity'  => 0,
    'private_stats'    => 1,
    'accept_cookies'    => 0,
    'share_data'       => 0,
    'two_factor'        => 0,
    'language'         => 'pt-ao',
    'currency'    => 'AOA',
    'date_format'      => 'dd/mm/yyyy',
], $settings ?: []);

// ── Flash message ─────────────────────────────
$flash = $_SESSION['settings_flash'] ?? null;
unset($_SESSION['settings_flash']);

// ── Membro desde — coluna correcta: creat_user ─
$member_since = '—';
if (!empty($user['creat_user'])) {
    try {
        $d = new DateTime($user['creat_user']);
        $months_pt = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $member_since = $d->format('d') . ' ' . $months_pt[(int)$d->format('m') - 1] . ' ' . $d->format('Y');
    } catch (Exception $e) {
        $member_since = '—';
    }
}

// ── Actividade recente — tabela: _user_activity_log ─
try {
    $act_q = $db->prepare("
        SELECT description, creat_activity, activity_type
        FROM _user_activity_log
        WHERE id_users = ?
        ORDER BY creat_activity DESC
        LIMIT 5
    ");
    $act_q->execute([$id_users]);
    $recent_activity = $act_q->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_activity = [];
}

// ── Status da conta — coluna correcta: status_user ─
$status_user = $user['status_user'] ?? 'active';
$status_map  = [
    'active'       => ['label' => 'Activo',      'class' => 'bg-success'],
    'inactive'     => ['label' => 'Inactivo',     'class' => 'bg-secondary'],
    'blocked'      => ['label' => 'Bloqueado',    'class' => 'bg-danger'],
    'processing'   => ['label' => 'Em análise',   'class' => 'bg-warning text-dark'],
    'suspended'    => ['label' => 'Suspenso',     'class' => 'bg-warning text-dark'],
    'fraud'        => ['label' => 'Fraude',       'class' => 'bg-danger'],
    'pending_plan' => ['label' => 'Sem plano',    'class' => 'bg-secondary'],
];
$status_info = $status_map[$status_user] ?? ['label' => ucfirst($status_user), 'class' => 'bg-secondary'];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title>Configurações — <?php echo APP_NAME; ?></title>
    <style>
    .settings-header {
        background: linear-gradient(135deg, #FF0089 0%, #c8006e 60%, #7b0044 100%);
        border-radius: 20px;
        padding: 2.2rem 2.5rem;
        margin-bottom: 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
    }

    .settings-header::after {
        content: '\F3E5';
        font-family: 'bootstrap-icons';
        position: absolute;
        right: -20px;
        bottom: -24px;
        font-size: 9rem;
        opacity: .08;
        color: #fff;
        transform: rotate(30deg);
    }

    .settings-card {
        border-radius: 16px;
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        background: var(--card-bg, #fff);
        transition: box-shadow .2s;
        margin-bottom: 1.6rem;
    }

    .settings-card:hover {
        box-shadow: 0 6px 24px rgba(255, 0, 137, .1);
    }

    .settings-card .card-header {
        background: transparent;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .07));
        padding: 1rem 1.4rem;
        border-radius: 16px 16px 0 0;
    }

    .settings-card .card-header h5 {
        margin: 0;
        font-weight: 700;
        font-size: .95rem;
    }

    .settings-card .card-body {
        padding: 1.4rem;
    }

    .settings-section h2 {
        font-size: 1.1rem;
        font-weight: 800;
        color: #FF0089;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 1rem;
    }

    .form-check-input:checked {
        background-color: #FF0089;
        border-color: #FF0089;
    }

    .form-check-input:focus {
        border-color: #FF0089;
        box-shadow: 0 0 0 .2rem rgba(255, 0, 137, .2);
    }

    .form-select:focus,
    .form-control:focus {
        border-color: #FF0089;
        box-shadow: 0 0 0 .2rem rgba(255, 0, 137, .2);
    }

    .btn-settings {
        background: linear-gradient(135deg, #FF0089, #c8006e);
        border: none;
        color: #fff;
        padding: .45rem 1.2rem;
        border-radius: 9px;
        font-weight: 600;
        font-size: .85rem;
        transition: all .2s;
    }

    .btn-settings:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(255, 0, 137, .35);
        color: #fff;
    }

    .btn-settings-outline {
        background: transparent;
        border: 1.5px solid #FF0089;
        color: #FF0089;
        padding: .45rem 1.2rem;
        border-radius: 9px;
        font-weight: 600;
        font-size: .85rem;
        transition: all .2s;
    }

    .btn-settings-outline:hover {
        background: #FF0089;
        color: #fff;
    }

    /* ── Tema cards ── */
    .theme-option {
        border: 2px solid var(--border-color, rgba(0, 0, 0, .1));
        border-radius: 12px;
        padding: 14px 12px;
        cursor: pointer;
        text-align: center;
        transition: all .2s;
        user-select: none;
        display: block;
    }

    .theme-option:hover {
        border-color: #FF0089;
    }

    .theme-option.active {
        border-color: #FF0089;
        background: rgba(255, 0, 137, .06);
    }

    .theme-option .theme-icon {
        font-size: 1.6rem;
        margin-bottom: 6px;
    }

    .theme-option .theme-label {
        font-size: .78rem;
        font-weight: 700;
    }

    .theme-option input[type="radio"] {
        display: none;
    }

    /* ── Info grid ── */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: .75rem;
    }

    .info-item {
        background: var(--metric-bg, rgba(0, 0, 0, .025));
        padding: .85rem 1rem;
        border-radius: 10px;
    }

    .info-item strong {
        color: #FF0089;
        display: block;
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 3px;
    }

    .info-item p {
        margin: 0;
        font-size: .88rem;
        font-weight: 500;
    }

    /* ── Integrações ── */
    .integration-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-radius: 12px;
        background: var(--metric-bg, rgba(0, 0, 0, .025));
        margin-bottom: 8px;
    }

    .integration-row:last-child {
        margin-bottom: 0;
    }

    /* ── Zona de Perigo ── */
    .danger-zone {
        border: 2px solid rgba(220, 53, 69, .3);
        border-radius: 14px;
        padding: 1.4rem;
        background: rgba(220, 53, 69, .025);
    }

    .danger-zone h6 {
        color: #dc3545;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .danger-item {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 12px 0;
        border-bottom: 1px solid rgba(220, 53, 69, .1);
    }

    .danger-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .danger-item-info .title {
        font-weight: 700;
        font-size: .88rem;
    }

    .danger-item-info .desc {
        font-size: .75rem;
        color: var(--text-muted, #6c757d);
        margin-top: 2px;
    }

    /* ── Sidebar nav ── */
    .quick-nav .list-group-item {
        border: none;
        padding: .6rem 1rem;
        font-size: .85rem;
        font-weight: 500;
        color: var(--text-muted, #6c757d);
        border-radius: 8px !important;
        transition: all .15s;
    }

    .quick-nav .list-group-item:hover,
    .quick-nav .list-group-item.active-link {
        background: rgba(255, 0, 137, .07);
        color: #FF0089;
    }

    .account-status-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 7px 0;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .06));
        font-size: .84rem;
    }

    .account-status-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    /* ── Toasts ── */
    .toast-pink {
        background: linear-gradient(135deg, #FF0089, #c8006e);
        color: #fff;
    }

    .toast-green {
        background: linear-gradient(135deg, #10b981, #34d399);
        color: #fff;
    }

    .toast-red {
        background: linear-gradient(135deg, #ef4444, #f87171);
        color: #fff;
    }

    /* ── Activity ── */
    .activity-row {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .06));
        font-size: .78rem;
    }

    .activity-row:last-child {
        border-bottom: none;
    }

    .activity-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #FF0089;
        flex-shrink: 0;
        margin-top: 4px;
    }

    @media(max-width:768px) {
        .settings-header {
            padding: 1.5rem;
        }

        .info-grid {
            grid-template-columns: 1fr;
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

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show mb-3"
            role="alert" style="border-radius:12px">
            <i
                class="bi bi-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($flash['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="settings-header">
            <h1 class="fw-bold mb-2"><i class="bi bi-gear-fill me-2"></i>Configurações</h1>
            <p class="lead mb-0" style="opacity:.85">Personaliza a tua experiência no <?php echo APP_NAME ?> —
                notificações,
                aparência, privacidade e mais.</p>
        </div>

        <div class="row g-4">

            <!-- ══ COLUNA PRINCIPAL ══ -->
            <div class="col-lg-8">

                <!-- ── Perfil ── -->
                <section class="settings-section" id="profile">
                    <h2><i class="bi bi-person-circle"></i>Perfil</h2>
                    <div class="settings-card card">
                        <div class="card-header">
                            <h5>Informações da Conta</h5>
                        </div>
                        <div class="card-body">
                            <div class="info-grid mb-3">
                                <div class="info-item">
                                    <strong>Nome</strong>
                                    <p><?php echo trim($first_name . ' ' . $last_name) ?: '—'; ?></p>
                                </div>
                                <div class="info-item">
                                    <strong>Nome artístico / Editora</strong>
                                    <p><?php echo $user_artist_name ?: '—'; ?></p>
                                </div>
                                <div class="info-item">
                                    <strong>E-mail</strong>
                                    <p><?php echo $email_user ?: '—'; ?></p>
                                </div>
                                <div class="info-item">
                                    <strong>Membro desde</strong>
                                    <p><?php echo $member_since; ?></p>
                                </div>
                                <div class="info-item">
                                    <strong>ID da conta</strong>
                                    <p><?php echo str_pad($id_users, 6, '0', STR_PAD_LEFT); ?></p>
                                </div>
                                <div class="info-item">
                                    <strong>Plano</strong>
                                    <p>
                                        <?php if ($plan): ?>
                                        <span
                                            class="badge bg-success"><?php echo htmlspecialchars($plan['name_plan']); ?></span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">Sem plano activo</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/user/profile"
                                    class="btn btn-settings"><i class="bi bi-pencil me-2"></i>Editar Perfil</a>
                                <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/user/profile#seguranca"
                                    class="btn btn-settings-outline"><i class="bi bi-key me-2"></i>Alterar Senha</a>
                                <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/user/profile"
                                    class="btn btn-settings-outline"><i class="bi bi-tools me-2"></i>Gestão de Conta</a>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ── Notificações ── -->
                <section class="settings-section" id="notifications">
                    <h2><i class="bi bi-bell"></i>Notificações</h2>
                    <div class="settings-card card">
                        <div class="card-header">
                            <h5>Preferências de Notificação</h5>
                        </div>
                        <div class="card-body">
                            <form action="settings_process" method="POST">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                                <input type="hidden" name="action" value="save_notifications" />
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="notif_email" name="notif_email"
                                        value="1" <?php echo $s['notif_email'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="notif_email">
                                        <strong>Notificações por E-mail</strong>
                                        <small class="d-block text-muted">Actualizações sobre streams, saques e
                                            suporte</small>
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="notif_push" name="notif_push"
                                        value="1" <?php echo $s['notif_push'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="notif_push">
                                        <strong>Notificações Push</strong>
                                        <small class="d-block text-muted">Alertas em tempo real no navegador</small>
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="notif_streams"
                                        name="notif_streams" value="1"
                                        <?php echo $s['notif_streams'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="notif_streams">
                                        <strong>Alertas de Streams Altos</strong>
                                        <small class="d-block text-muted">Notificar quando streams ultrapassarem 1.000
                                            por faixa</small>
                                    </label>
                                </div>
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="notif_weekly"
                                        name="notif_weekly" value="1"
                                        <?php echo $s['notif_weekly'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="notif_weekly">
                                        <strong>Resumo Semanal</strong>
                                        <small class="d-block text-muted">Receber um resumo das actividades da semana
                                            por e-mail</small>
                                    </label>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-settings"><i
                                            class="bi bi-check-lg me-2"></i>Guardar</button>
                                    <button type="button" class="btn btn-settings-outline"
                                        onclick="testPushNotification()"><i class="bi bi-bell me-2"></i>Testar
                                        Push</button>
                                    <a href="notifications" class="btn btn-settings-outline"><i
                                            class="bi bi-list me-2"></i>Ver Notificações</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- ── Aparência ── -->
                <section class="settings-section" id="appearance">
                    <h2><i class="bi bi-palette"></i>Aparência</h2>
                    <div class="settings-card card">
                        <div class="card-header">
                            <h5>Tema e Interface</h5>
                        </div>
                        <div class="card-body">
                            <form action="settings_process" method="POST" id="formAppearance">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                                <input type="hidden" name="action" value="save_appearance" />
                                <input type="hidden" name="theme" id="hiddenTheme"
                                    value="<?php echo htmlspecialchars($s['theme']); ?>" />

                                <div class="mb-4">
                                    <label class="form-label fw-bold mb-3">Tema</label>
                                    <div class="row g-2">
                                        <?php
                                        $themes = [
                                            'dark'   => ['icon' => 'bi-moon-stars-fill', 'label' => 'Escuro',  'desc' => 'Fundo escuro'],
                                            'light'  => ['icon' => 'bi-sun-fill',        'label' => 'Claro',   'desc' => 'Fundo claro'],
                                            'system' => ['icon' => 'bi-laptop',          'label' => 'Sistema', 'desc' => 'Igual ao SO'],
                                        ];
                                        foreach ($themes as $val => $t): ?>
                                        <div class="col-4">
                                            <label
                                                class="theme-option <?php echo $s['theme'] === $val ? 'active' : ''; ?>"
                                                id="themeCard_<?php echo $val; ?>"
                                                onclick="selectTheme('<?php echo $val; ?>')">
                                                <div class="theme-icon"><i class="bi <?php echo $t['icon']; ?>"></i>
                                                </div>
                                                <div class="theme-label"><?php echo $t['label']; ?></div>
                                                <div style="font-size:.68rem;color:var(--text-muted,#6c757d)">
                                                    <?php echo $t['desc']; ?></div>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="ui_density" class="form-label fw-bold">Densidade da Interface</label>
                                    <select class="form-select" id="ui_density" name="ui_density">
                                        <option value="comfortable"
                                            <?php echo $s['ui_density'] === 'comfortable' ? 'selected' : ''; ?>>
                                            Confortável — mais espaço entre elementos</option>
                                        <option value="compact"
                                            <?php echo $s['ui_density'] === 'compact'     ? 'selected' : ''; ?>>Compacto
                                            —
                                            padrão</option>
                                        <option value="cozy"
                                            <?php echo $s['ui_density'] === 'cozy'        ? 'selected' : ''; ?>>
                                            Aconchegante — elementos maiores</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-settings"><i
                                        class="bi bi-check-lg me-2"></i>Guardar Aparência</button>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- ── Widgets ── -->
                <section class="settings-section" id="dashboard">
                    <h2><i class="bi bi-speedometer2"></i>Dashboard</h2>
                    <div class="settings-card card">
                        <div class="card-header">
                            <h5>Widgets e Exibição</h5>
                        </div>
                        <div class="card-body">
                            <form action="settings_process" method="POST">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                                <input type="hidden" name="action" value="save_widgets" />
                                <p class="small text-muted mb-3">Escolhe quais secções aparecem no teu painel principal.
                                </p>
                                <?php
                                $widgets = [
                                    'widget_streams'   => ['label' => 'Gráfico de Streams',    'desc' => 'Evolução mensal dos streams'],
                                    'widget_financial' => ['label' => 'Resumo Financeiro',      'desc' => 'Balanço de royalties e carteira'],
                                    'widget_releases'  => ['label' => 'Lançamentos Recentes',   'desc' => 'Últimos álbuns/singles submetidos'],
                                    'widget_artists'   => ['label' => 'Top Artistas',           'desc' => 'Artistas com mais streams'],
                                    'widget_activity'  => ['label' => 'Feed de Actividades',    'desc' => 'Últimas acções na plataforma'],
                                ];
                                foreach ($widgets as $key => $w): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="<?php echo $key; ?>"
                                        name="<?php echo $key; ?>" value="1" <?php echo $s[$key] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="<?php echo $key; ?>">
                                        <?php echo $w['label']; ?>
                                        <small class="d-block text-muted"><?php echo $w['desc']; ?></small>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-settings"><i
                                            class="bi bi-check-lg me-2"></i>Guardar Widgets</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- ── Privacidade ── -->
                <section class="settings-section" id="privacy">
                    <h2><i class="bi bi-shield-lock"></i>Privacidade</h2>
                    <div class="settings-card card">
                        <div class="card-header">
                            <h5>Controlos de Privacidade</h5>
                        </div>
                        <div class="card-body">
                            <form action="settings_process" method="POST">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                                <input type="hidden" name="action" value="save_privacy" />
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="private_stats"
                                        name="private_stats" value="1"
                                        <?php echo $s['private_stats'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="private_stats">
                                        <strong>Estatísticas privadas</strong>
                                        <small class="d-block text-muted">Visíveis apenas para mim e para a equipa Wasom
                                            Upfy</small>
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="accept_cookies"
                                        name="accept_cookies" value="1"
                                        <?php echo $s['accept_cookies'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="accept_cookies">
                                        <strong>Aceitar cookies analíticos</strong>
                                        <small class="d-block text-muted">Permite melhorar a experiência com base no
                                            comportamento de navegação</small>
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="share_data" name="share_data"
                                        value="1" <?php echo $s['share_data'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="share_data">
                                        <strong>Partilhar dados anonimizados</strong>
                                        <small class="d-block text-muted">Contribuir para a melhoria do serviço — sem
                                            identificação pessoal</small>
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-settings"><i
                                        class="bi bi-check-lg me-2"></i>Guardar Privacidade</button>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- ── Idioma e Região ── -->
                <section class="settings-section" id="language">
                    <h2><i class="bi bi-globe"></i>Idioma e Região</h2>
                    <div class="settings-card card">
                        <div class="card-header">
                            <h5>Preferências Regionais</h5>
                        </div>
                        <div class="card-body">
                            <form action="settings_process" method="POST">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                                <input type="hidden" name="action" value="save_language" />
                                <div class="mb-3">
                                    <label for="language" class="form-label fw-semibold">Idioma</label>
                                    <select class="form-select" id="language" name="language">
                                        <option value="pt-ao"
                                            <?php echo $s['language'] === 'pt-ao' ? 'selected' : ''; ?>>
                                            Português (Angola)</option>
                                        <option value="pt-br"
                                            <?php echo $s['language'] === 'pt-br' ? 'selected' : ''; ?>>
                                            Português (Brasil)</option>
                                        <option value="pt-pt"
                                            <?php echo $s['language'] === 'pt-pt' ? 'selected' : ''; ?>>
                                            Português (Portugal)</option>
                                        <option value="en" <?php echo $s['language'] === 'en'    ? 'selected' : ''; ?>>
                                            English (US)</option>
                                        <option value="fr" <?php echo $s['language'] === 'fr'    ? 'selected' : ''; ?>>
                                            Français</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="currency" class="form-label fw-semibold">Moeda Principal</label>
                                    <select class="form-select" id="currency" name="currency">
                                        <option value="AOA" <?php echo $s['currency'] === 'AOA' ? 'selected' : ''; ?>>
                                            AOA
                                            — Kwanza Angolano</option>
                                        <option value="USD" <?php echo $s['currency'] === 'USD' ? 'selected' : ''; ?>>
                                            USD
                                            — Dólar Americano</option>
                                        <option value="EUR" <?php echo $s['currency'] === 'EUR' ? 'selected' : ''; ?>>
                                            EUR
                                            — Euro</option>
                                        <option value="BRL" <?php echo $s['currency'] === 'BRL' ? 'selected' : ''; ?>>
                                            BRL
                                            — Real Brasileiro</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label for="date_format" class="form-label fw-semibold">Formato de Data</label>
                                    <select class="form-select" id="date_format" name="date_format">
                                        <option value="dd/mm/yyyy"
                                            <?php echo $s['date_format'] === 'dd/mm/yyyy'  ? 'selected' : ''; ?>>
                                            DD/MM/YYYY (padrão Angola/Portugal)</option>
                                        <option value="mm/dd/yyyy"
                                            <?php echo $s['date_format'] === 'mm/dd/yyyy'  ? 'selected' : ''; ?>>
                                            MM/DD/YYYY (EUA)</option>
                                        <option value="yyyy-mm-dd"
                                            <?php echo $s['date_format'] === 'yyyy-mm-dd'  ? 'selected' : ''; ?>>
                                            YYYY-MM-DD (ISO 8601)</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-settings"><i
                                        class="bi bi-check-lg me-2"></i>Guardar Preferências</button>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- ── Integrações ── -->
                <section class="settings-section" id="integrations">
                    <h2><i class="bi bi-link-45deg"></i>Integrações</h2>
                    <div class="settings-card card">
                        <div class="card-header">
                            <h5>Plataformas Conectadas</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($artists)):
                                foreach ($artists as $art): ?>

                            <?php if (!empty($art['youtube_url'])): ?>
                            <div class="integration-row">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-youtube text-danger fs-4"></i>
                                    <div>
                                        <div class="fw-semibold small">YouTube</div>
                                        <div class="text-muted" style="font-size:.72rem">
                                            <?php echo htmlspecialchars($art['stage_name']); ?></div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success">Conectado</span>
                                    <a href="<?php echo htmlspecialchars($art['youtube_url']); ?>" target="_blank"
                                        class="btn btn-sm btn-outline-secondary"
                                        style="border-radius:8px;font-size:.72rem">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($art['spotify_url'])): ?>
                            <div class="integration-row">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-spotify text-success fs-4"></i>
                                    <div>
                                        <div class="fw-semibold small">Spotify for Artists</div>
                                        <div class="text-muted" style="font-size:.72rem">
                                            <?php echo htmlspecialchars($art['stage_name']); ?></div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success">Conectado</span>
                                    <a href="<?php echo htmlspecialchars($art['spotify_url']); ?>" target="_blank"
                                        class="btn btn-sm btn-outline-secondary"
                                        style="border-radius:8px;font-size:.72rem">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($art['instagram_url'])): ?>
                            <div class="integration-row">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-instagram fs-4" style="color:#e1306c"></i>
                                    <div>
                                        <div class="fw-semibold small">Instagram</div>
                                        <div class="text-muted" style="font-size:.72rem">
                                            <?php echo htmlspecialchars($art['stage_name']); ?></div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success">Conectado</span>
                                    <a href="<?php echo htmlspecialchars($art['instagram_url']); ?>" target="_blank"
                                        class="btn btn-sm btn-outline-secondary"
                                        style="border-radius:8px;font-size:.72rem">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php endforeach;
                            else: ?>
                            <p class="small text-muted">Nenhum artista com integrações configuradas.</p>
                            <?php endif; ?>

                            <!-- Em breve -->
                            <?php foreach (
                                [
                                    ['bi-apple', '#555', 'Apple Music for Artists'],
                                    ['bi-tiktok', '#69c9d0', 'TikTok for Artists'],
                                ] as [$icon, $color, $name]
                            ): ?>
                            <div class="integration-row" style="opacity:.5">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi <?php echo $icon; ?> fs-4" style="color:<?php echo $color; ?>"></i>
                                    <div>
                                        <div class="fw-semibold small"><?php echo $name; ?></div>
                                        <div class="text-muted" style="font-size:.72rem">Em breve</div>
                                    </div>
                                </div>
                                <span class="badge bg-secondary">Em breve</span>
                            </div>
                            <?php endforeach; ?>
                            <div class="d-flex gap-2 mt-3">
                                <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/youtube/ucy"
                                    class="btn btn-settings-outline"><i class="bi bi-youtube me-1"></i>Gerir YouTube</a>
                                <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/artists-list"
                                    class="btn btn-settings-outline"><i class="bi bi-person me-1"></i>Ver Artistas</a>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ══ ZONA DE PERIGO ══ -->
                <section class="settings-section" id="danger">
                    <h2 style="color:#dc3545"><i class="bi bi-exclamation-triangle"></i>Zona de Perigo</h2>
                    <div class="danger-zone">
                        <h6><i class="bi bi-exclamation-circle me-2"></i>Acções sensíveis — procede com cautela</h6>

                        <!-- Encerrar sessões -->
                        <div class="danger-item">
                            <div class="danger-item-info">
                                <div class="title"><i class="bi bi-shield-x me-2 text-warning"></i>Encerrar todas as
                                    sessões activas</div>
                                <div class="desc">Termina o acesso automático (remember me) em todos os dispositivos.
                                </div>
                            </div>
                            <form action="settings_process" method="POST" style="flex-shrink:0">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                                <input type="hidden" name="action" value="revoke_sessions" />
                                <button type="submit" class="btn btn-outline-warning btn-sm"
                                    style="border-radius:9px;white-space:nowrap">
                                    <i class="bi bi-door-open me-1"></i>Encerrar sessões
                                </button>
                            </form>
                        </div>

                        <!-- Alterar senha -->
                        <div class="danger-item">
                            <div class="danger-item-info">
                                <div class="title"><i class="bi bi-key me-2 text-warning"></i>Alterar senha</div>
                                <div class="desc">Recomendamos alterar a senha regularmente para proteger a tua conta.
                                </div>
                            </div>
                            <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/user/profile#seguranca"
                                class="btn btn-outline-warning btn-sm"
                                style="border-radius:9px;flex-shrink:0;white-space:nowrap">
                                <i class="bi bi-key me-1"></i>Alterar senha
                            </a>
                        </div>

                        <!-- Revogar analytics -->
                        <div class="danger-item">
                            <div class="danger-item-info">
                                <div class="title"><i class="bi bi-slash-circle me-2 text-warning"></i>Revogar acesso a
                                    dados analíticos</div>
                                <div class="desc">Remove a autorização de partilha de dados com plataformas terceiras.
                                </div>
                            </div>
                            <form action="settings_process" method="POST" style="flex-shrink:0">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                                <input type="hidden" name="action" value="revoke_analytics" />
                                <button type="submit" class="btn btn-outline-warning btn-sm"
                                    style="border-radius:9px;white-space:nowrap">
                                    <i class="bi bi-slash-circle me-1"></i>Revogar acesso
                                </button>
                            </form>
                        </div>

                        <!-- Desactivar conta → profile -->
                        <div class="danger-item">
                            <div class="danger-item-info">
                                <div class="title"><i class="bi bi-pause-circle me-2 text-danger"></i>Desactivar conta
                                    temporariamente</div>
                                <div class="desc">A conta fica suspensa. Para confirmar, serás redirecccionado para a
                                    Gestão de Conta.</div>
                            </div>
                            <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/user/profile#perigo"
                                class="btn btn-outline-danger btn-sm"
                                style="border-radius:9px;flex-shrink:0;white-space:nowrap">
                                <i class="bi bi-pause-circle me-1"></i>Desactivar
                            </a>
                        </div>

                        <!-- Eliminar conta → profile -->
                        <div class="danger-item">
                            <div class="danger-item-info">
                                <div class="title"><i class="bi bi-trash3 me-2 text-danger"></i>Eliminar conta
                                    permanentemente</div>
                                <div class="desc">Todos os teus dados serão removidos de forma definitiva. Para
                                    confirmar, serás redirecccionado para a Gestão de Conta.</div>
                            </div>
                            <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/user/profile#perigo"
                                class="btn btn-danger btn-sm"
                                style="border-radius:9px;flex-shrink:0;white-space:nowrap">
                                <i class="bi bi-trash3 me-1"></i>Eliminar conta
                            </a>
                        </div>

                    </div>
                </section>

            </div><!-- /col-lg-8 -->

            <!-- ══ SIDEBAR ══ -->
            <div class="col-lg-4">

                <!-- Navegação rápida -->
                <div class="settings-card card mb-3">
                    <div class="card-header">
                        <h5><i class="bi bi-list-ul me-2"></i>Navegação Rápida</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="list-group quick-nav">
                            <?php
                            $nav = [
                                '#profile'       => ['bi-person-circle',        'Perfil'],
                                '#notifications' => ['bi-bell',                 'Notificações'],
                                '#appearance'    => ['bi-palette',              'Aparência'],
                                '#dashboard'     => ['bi-speedometer2',         'Dashboard'],
                                '#privacy'       => ['bi-shield-lock',          'Privacidade'],
                                '#language'      => ['bi-globe',                'Idioma e Região'],
                                '#integrations'  => ['bi-link-45deg',           'Integrações'],
                                '#danger'        => ['bi-exclamation-triangle', 'Zona de Perigo'],
                            ];
                            foreach ($nav as $href => [$icon, $label]): ?>
                            <a href="<?php echo $href; ?>" class="list-group-item list-group-item-action"
                                style="<?php echo $href === '#danger' ? 'color:#dc3545!important' : ''; ?>">
                                <i class="bi <?php echo $icon; ?> me-2" style="color:#FF0089"></i><?php echo $label; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Estado da conta -->
                <div class="settings-card card mb-3">
                    <div class="card-header">
                        <h5><i class="bi bi-shield-check me-2"></i>Estado da Conta</h5>
                    </div>
                    <div class="card-body">
                        <div class="account-status-row">
                            <span class="text-muted small">Plano</span>
                            <?php if ($plan): ?>
                            <strong class="small"
                                style="color:#FF0089"><?php echo htmlspecialchars($plan['name_plan']); ?></strong>
                            <?php else: ?>
                            <span class="badge bg-secondary">Sem plano</span>
                            <?php endif; ?>
                        </div>
                        <div class="account-status-row">
                            <span class="text-muted small">Estado</span>
                            <span
                                class="badge <?php echo $status_info['class']; ?>"><?php echo $status_info['label']; ?></span>
                        </div>
                        <?php if ($plan && !empty($plan['expires_at'])): ?>
                        <div class="account-status-row">
                            <span class="text-muted small">Validade</span>
                            <span class="small"><?php echo date('d/m/Y', strtotime($plan['expires_at'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="account-status-row">
                            <span class="text-muted small">Membro desde</span>
                            <span class="small"><?php echo $member_since; ?></span>
                        </div>
                        <div class="account-status-row">
                            <span class="text-muted small">ID</span>
                            <span class="small fw-bold"><?php echo str_pad($id_users, 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/services/available-services"
                            class="btn btn-settings w-100 mt-3">
                            <i class="bi bi-arrow-up-circle me-2"></i>Ver planos
                        </a>
                    </div>
                </div>

                <!-- Actividade recente -->
                <div class="settings-card card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Actividade Recente</h5>
                    </div>
                    <div class="card-body py-2">
                        <?php if (!empty($recent_activity)):
                            foreach ($recent_activity as $act): ?>
                        <div class="activity-row">
                            <div class="activity-dot"></div>
                            <div>
                                <div style="font-size:.78rem"><?php echo htmlspecialchars($act['description'] ?? ''); ?>
                                </div>
                                <div style="font-size:.68rem;color:var(--text-muted,#6c757d)">
                                    <?php echo !empty($act['creat_activity']) ? date('d/m/Y H:i', strtotime($act['creat_activity'])) : '—'; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach;
                        else: ?>
                        <p class="small text-muted mb-0">Sem actividade registada.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Links úteis -->
                <div class="settings-card card mb-3">
                    <div class="card-header">
                        <h5><i class="bi bi-link me-2"></i>Links Úteis</h5>
                    </div>
                    <div class="card-body p-2">
                        <ul class="nav flex-column">
                            <?php foreach (
                                [
                                    ['help',                    'bi-question-circle', 'Ajuda'],
                                    ['support',                 'bi-headset',         'Suporte'],
                                    ['faq',                     'bi-chat-left-text',  'Perguntas Frequentes'],
                                    ['politicies/terms',        'bi-file-text',       'Termos de Uso'],
                                    ['politicies/privacy',      'bi-shield',          'Política de Privacidade'],
                                ] as [$href, $icon, $label]
                            ): ?>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="<?php echo $href; ?>" style="font-size:.84rem">
                                    <i class="bi <?php echo $icon; ?> me-2"
                                        style="color:#FF0089"></i><?php echo $label; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Versão -->
                <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill text-white mb-4"
                    style="background:linear-gradient(135deg,#FF0089,#c8006e);font-size:.8rem">
                    <i class="bi bi-info-circle"></i>
                    <span><?php echo APP_NAME; ?> — Versão 2.0 (2026)</span>
                </div>

            </div><!-- /sidebar -->
        </div><!-- /row -->
        </main>


        <!-- Toast -->
        <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1200">
            <div id="mainToast" class="toast align-items-center border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body" id="mainToastBody"></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>

        <!-- ═══ JS ═══ -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
        <script>
        // ── Toast helper ──────────────────────────────
        function showToast(msg, type = 'pink') {
            const el = document.getElementById('mainToast');
            el.className = `toast align-items-center border-0 toast-${type}`;
            document.getElementById('mainToastBody').textContent = msg;
            bootstrap.Toast.getOrCreateInstance(el, {
                delay: 3500
            }).show();
        }

        // ══════════════════════════════════════════════════════
        // TEMA — alinhado com o teu sistema theme.wp.js
        // O teu sistema usa: body.classList.add('dark-mode')
        //                    body.classList.add('light-mode')
        //                    localStorage.setItem('theme', val)
        // ══════════════════════════════════════════════════════
        function selectTheme(val) {
            // 1. Actualiza o campo hidden do formulário
            document.getElementById('hiddenTheme').value = val;

            // 2. Actualiza os cards visuais
            document.querySelectorAll('.theme-option').forEach(el => el.classList.remove('active'));
            const card = document.getElementById('themeCard_' + val);
            if (card) card.classList.add('active');

            // 3. Aplica o tema no body (igual ao theme.wp.js)
            const body = document.body;
            if (val === 'dark') {
                body.classList.add('dark-mode');
                body.classList.remove('light-mode');
                document.getElementById('themeIcon').className = 'bi bi-moon';
            } else if (val === 'light') {
                body.classList.add('light-mode');
                body.classList.remove('dark-mode');
                document.getElementById('themeIcon').className = 'bi bi-sun';
            } else {
                // system: respeita a preferência do SO
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (prefersDark) {
                    body.classList.add('dark-mode');
                    body.classList.remove('light-mode');
                    document.getElementById('themeIcon').className = 'bi bi-moon';
                } else {
                    body.classList.add('light-mode');
                    body.classList.remove('dark-mode');
                    document.getElementById('themeIcon').className = 'bi bi-sun';
                }
            }

            // 4. Guarda no localStorage (para o toggle da navbar também ler)
            localStorage.setItem('theme', val);
        }

        // Ao carregar: sincroniza os cards com o tema guardado
        (function() {
            const saved = localStorage.getItem('theme') || '<?php echo htmlspecialchars($s["theme"]); ?>';
            selectTheme(saved);
        })();

        // Toggle da navbar também actualiza o card seleccionado
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                const isDark = document.body.classList.contains('dark-mode');
                selectTheme(isDark ? 'light' : 'dark');
            });
        }

        // ── Push notification test ────────────────────
        function testPushNotification() {
            if (!('Notification' in window)) {
                showToast('Notificações não suportadas neste navegador.', 'red');
                return;
            }
            Notification.requestPermission().then(p => {
                if (p === 'granted') {
                    new Notification('Wasom Upfy', {
                        body: 'Notificação de teste enviada com sucesso!',
                        icon: '<?php echo APP_URL ?>/assets/img/icones/wasomupfy_fiv.png'
                    });
                    showToast('Notificação de teste enviada!');
                } else {
                    showToast('Permissão negada pelo navegador.', 'red');
                }
            });
        }

        // ── Quick nav scroll spy ──────────────────────
        const sections = document.querySelectorAll('section[id]');
        window.addEventListener('scroll', () => {
            let cur = '';
            sections.forEach(s => {
                if (window.scrollY >= s.offsetTop - 120) cur = s.id;
            });
            document.querySelectorAll('.quick-nav a').forEach(a => {
                a.classList.toggle('active-link', a.getAttribute('href') === '#' + cur);
            });
        }, {
            passive: true
        });

        // ── Flash toast auto ──────────────────────────
        <?php if ($flash): ?>
        showToast(<?php echo json_encode($flash['msg']); ?>,
            '<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>');
        <?php endif; ?>
        </script>
</body>

</html>