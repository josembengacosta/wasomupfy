<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Ajuda / Central de Ajuda
// Arquivo: dashboard/page/help.php
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


$first_name = htmlspecialchars($user['first_name'] ?? '');
$full_name  = htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['second_name'] ?? '')));
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title>Ajuda — <?php echo APP_NAME; ?></title>
    <style>
    /* ══ Help header ══ */
    .help-header {
        background: linear-gradient(135deg, #FF0089 0%, #FF4D4D 100%);
        border-radius: 24px;
        padding: 3rem 2rem;
        margin-bottom: 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        text-align: center;
    }

    .help-header::before {
        content: '\F431';
        font-family: 'bootstrap-icons';
        position: absolute;
        left: -20px;
        bottom: -20px;
        font-size: 12rem;
        opacity: .08;
        transform: rotate(-15deg);
    }

    .help-header::after {
        content: '\F44F';
        font-family: 'bootstrap-icons';
        position: absolute;
        right: -20px;
        top: -20px;
        font-size: 10rem;
        opacity: .08;
        transform: rotate(15deg);
    }

    .help-header h1 {
        font-size: 2.8rem;
        font-weight: 800;
        margin-bottom: 1rem;
        position: relative;
        z-index: 2;
    }

    .help-header p {
        font-size: 1.1rem;
        max-width: 680px;
        margin: 0 auto;
        opacity: .92;
        position: relative;
        z-index: 2;
    }

    /* ══ Search box ══ */
    .search-box {
        max-width: 580px;
        margin: 1.8rem auto 0;
        position: relative;
        z-index: 2;
    }

    .search-box .input-group {
        background: #fff;
        border-radius: 50px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .18);
    }

    .search-box input {
        border: none;
        padding: .9rem 1.4rem;
        font-size: .95rem;
    }

    .search-box input:focus {
        box-shadow: none;
        outline: none;
    }

    .search-box .search-btn {
        background: #fff;
        border: none;
        padding: 0 1.8rem;
        color: #FF0089;
        font-weight: 700;
        transition: all .2s;
    }

    .search-box .search-btn:hover {
        background: #FF0089;
        color: #fff;
    }

    /* ══ Category cards ══ */
    .help-category-card {
        background: var(--card-bg, #fff);
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .07));
        border-radius: 18px;
        padding: 2rem 1.4rem;
        text-align: center;
        height: 100%;
        transition: all .25s;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .help-category-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 16px 36px rgba(255, 0, 137, .14);
        border-color: #FF0089;
        color: inherit;
    }

    .help-category-icon {
        width: 72px;
        height: 72px;
        background: linear-gradient(135deg, rgba(255, 0, 137, .1), rgba(255, 77, 77, .1));
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.3rem;
    }

    .help-category-icon i {
        font-size: 2.5rem;
        color: #FF0089;
    }

    .help-category-card h3 {
        font-size: 1.15rem;
        font-weight: 700;
        margin-bottom: .4rem;
    }

    .help-category-card p {
        color: var(--text-muted, #6c757d);
        font-size: .88rem;
        margin-bottom: .8rem;
    }

    .help-category-card .badge {
        background: #FF0089;
        font-weight: 500;
        padding: .35rem .85rem;
    }

    /* ══ FAQ items ══ */
    .faq-item {
        background: var(--card-bg, #fff);
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .07));
        border-radius: 14px;
        padding: 1.4rem;
        margin-bottom: .8rem;
        border-left: 4px solid transparent;
        transition: all .2s;
    }

    .faq-item:hover {
        box-shadow: 0 6px 20px rgba(255, 0, 137, .1);
        border-left-color: #FF0089;
    }

    .faq-question {
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        user-select: none;
    }

    .faq-question h5 {
        margin: 0;
        font-weight: 600;
        font-size: .95rem;
    }

    .faq-question .faq-icon {
        font-size: 1.1rem;
        color: #FF0089;
        transition: transform .3s;
        flex-shrink: 0;
    }

    .faq-question[aria-expanded="true"] .faq-icon {
        transform: rotate(180deg);
    }

    .faq-answer {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color, rgba(0, 0, 0, .08));
        color: var(--text-muted, #6c757d);
        font-size: .88rem;
    }

    /* ══ Tutorial cards ══ */
    .tutorial-card {
        background: var(--card-bg, #fff);
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .07));
        border-radius: 14px;
        overflow: hidden;
        height: 100%;
        transition: all .25s;
    }

    .tutorial-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(255, 0, 137, .13);
    }

    .tutorial-thumb {
        position: relative;
        padding-top: 56.25%;
        background: linear-gradient(135deg, #FF0089, #FF4D4D);
    }

    .tutorial-thumb-inner {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .play-btn {
        width: 52px;
        height: 52px;
        background: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FF0089;
        font-size: 1.5rem;
        box-shadow: 0 4px 14px rgba(0, 0, 0, .25);
        transition: all .2s;
    }

    .tutorial-card:hover .play-btn {
        background: #FF0089;
        color: #fff;
        transform: scale(1.1);
    }

    .tutorial-icon-bg {
        font-size: 5rem;
        opacity: .12;
        color: #fff;
        position: absolute;
        right: 10px;
        bottom: -10px;
    }

    .tutorial-body {
        padding: 1.3rem;
    }

    .tutorial-body h5 {
        font-weight: 700;
        font-size: .95rem;
        margin-bottom: .35rem;
    }

    .tutorial-body p {
        color: var(--text-muted, #6c757d);
        font-size: .82rem;
        margin-bottom: .8rem;
    }

    .tutorial-meta {
        display: flex;
        gap: 1rem;
        font-size: .76rem;
        color: var(--text-muted, #6c757d);
    }

    .tutorial-meta i {
        color: #FF0089;
        margin-right: 3px;
    }

    /* ══ Support option cards ══ */
    .support-option {
        background: var(--card-bg, #fff);
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .07));
        border-radius: 18px;
        padding: 2rem 1.5rem;
        text-align: center;
        height: 100%;
        transition: all .25s;
    }

    .support-option:hover {
        border-color: #FF0089;
        transform: translateY(-4px);
        box-shadow: 0 10px 28px rgba(255, 0, 137, .1);
    }

    .support-option>i {
        font-size: 2.8rem;
        color: #FF0089;
        display: block;
        margin-bottom: .9rem;
    }

    .support-option h4 {
        font-weight: 700;
        font-size: 1.05rem;
        margin-bottom: .4rem;
    }

    .support-option p {
        color: var(--text-muted, #6c757d);
        font-size: .86rem;
        margin-bottom: 1.2rem;
    }

    /* ══ Buttons ══ */
    .btn-help {
        background: linear-gradient(135deg, #FF0089, #FF4D4D);
        border: none;
        color: #fff;
        padding: .5rem 1.8rem;
        border-radius: 50px;
        font-weight: 600;
        transition: all .2s;
    }

    .btn-help:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(255, 0, 137, .3);
        color: #fff;
    }

    .btn-help-outline {
        background: transparent;
        border: 2px solid #FF0089;
        color: #FF0089;
        padding: .5rem 1.8rem;
        border-radius: 50px;
        font-weight: 600;
        transition: all .2s;
    }

    .btn-help-outline:hover {
        background: #FF0089;
        color: #fff;
    }

    /* ══ Contact info ══ */
    .contact-info {
        background: var(--metric-bg, rgba(0, 0, 0, .03));
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .07));
        border-radius: 16px;
        padding: 1.5rem;
    }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: .9rem;
    }

    .contact-item:last-child {
        margin-bottom: 0;
    }

    .contact-item-icon {
        width: 38px;
        height: 38px;
        background: var(--card-bg, #fff);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FF0089;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .07);
    }

    .contact-item strong {
        display: block;
        font-size: .85rem;
    }

    .contact-item span {
        color: var(--text-muted, #6c757d);
        font-size: .8rem;
    }

    /* ══ Quick links ══ */
    .quick-link {
        background: var(--card-bg, #fff);
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .07));
        border-radius: 11px;
        padding: .9rem 1.1rem;
        display: flex;
        align-items: center;
        gap: 14px;
        text-decoration: none;
        color: inherit;
        transition: all .2s;
        margin-bottom: .6rem;
    }

    .quick-link:last-child {
        margin-bottom: 0;
    }

    .quick-link i {
        font-size: 1.8rem;
        color: #FF0089;
        transition: color .2s;
        flex-shrink: 0;
    }

    .quick-link:hover {
        background: #FF0089;
        color: #fff;
        transform: translateX(4px);
        border-color: #FF0089;
    }

    .quick-link:hover i {
        color: #fff;
    }

    .quick-link:hover small {
        color: rgba(255, 255, 255, .8);
    }

    .quick-link h6 {
        margin: 0;
        font-weight: 600;
        font-size: .87rem;
    }

    .quick-link small {
        color: var(--text-muted, #6c757d);
        font-size: .76rem;
    }

    /* ══ Section title ══ */
    .sec-title {
        font-size: 1.25rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 1.3rem;
    }

    .sec-title i {
        color: #FF0089;
    }

    @media(max-width:768px) {
        .help-header {
            padding: 2rem 1rem;
        }

        .help-header h1 {
            font-size: 2rem;
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
                '<strong>Cria o teu perfil artístico.</strong> Tens plano activo mas ainda não criaste um perfil artístico. Precisas de um para poder lançar música.',
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

        <!-- HEADER -->
        <div class="help-header">
            <h1><i class="bi bi-question-circle-fill me-2"></i>Central de Ajuda</h1>
            <p>
                Encontra respostas para as tuas dúvidas, tutoriais passo a passo e suporte especializado
                para aproveitares ao máximo a plataforma <?php echo APP_NAME ?>.
            </p>
            <div class="search-box">
                <div class="input-group">
                    <input type="text" class="form-control" id="helpSearch"
                        placeholder="Pesquisar… ex: lançar música, saques, estatísticas">
                    <button class="search-btn" type="button" id="searchBtn">
                        <i class="bi bi-search me-1"></i> Pesquisar
                    </button>
                </div>
            </div>
        </div>

        <!-- CATEGORIAS -->
        <div class="row g-3 mb-5">
            <div class="col-md-3 col-6">
                <a href="faq?categoria=lancamentos" class="help-category-card">
                    <div class="help-category-icon"><i class="bi bi-disc"></i></div>
                    <h3>Lançamentos</h3>
                    <p>Como publicar e gerir as tuas músicas</p>
                    <span class="badge">12 artigos</span>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="faq?categoria=financeiro" class="help-category-card">
                    <div class="help-category-icon"><i class="bi bi-currency-dollar"></i></div>
                    <h3>Financeiro</h3>
                    <p>Levantamentos, royalties e pagamentos</p>
                    <span class="badge">8 artigos</span>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="faq?categoria=conta" class="help-category-card">
                    <div class="help-category-icon"><i class="bi bi-person-circle"></i></div>
                    <h3>Conta</h3>
                    <p>Gestão de perfil e planos</p>
                    <span class="badge">10 artigos</span>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="faq?categoria=youtube" class="help-category-card">
                    <div class="help-category-icon"><i class="bi bi-youtube"></i></div>
                    <h3>YouTube</h3>
                    <p>Unificação de canais e Art Tracks</p>
                    <span class="badge">6 artigos</span>
                </a>
            </div>
        </div>

        <!-- FAQ DESTAQUE -->
        <div class="row mb-5">
            <div class="col-12 d-flex justify-content-between align-items-center mb-3">
                <div class="sec-title mb-0"><i class="bi bi-patch-question"></i>Perguntas Frequentes</div>
                <a href="faq" class="btn-help-outline btn btn-sm">Ver todas <i class="bi bi-arrow-right ms-1"></i></a>
            </div>

            <div class="col-lg-6">
                <!-- FAQ 1 -->
                <div class="faq-item">
                    <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="false">
                        <h5>Como criar um novo lançamento?</h5>
                        <i class="bi bi-chevron-down faq-icon"></i>
                    </div>
                    <div id="faq1" class="collapse">
                        <div class="faq-answer">
                            <p>Para criar um novo lançamento:</p>
                            <ol class="mb-2">
                                <li>Acede à secção <strong>Lançamentos</strong> no menu principal</li>
                                <li>Clica em <strong>Novo Lançamento</strong></li>
                                <li>Preenche as informações da música (título, artista, género)</li>
                                <li>Faz upload do ficheiro de áudio (WAV ou FLAC)</li>
                                <li>Adiciona a capa (mínimo 3000×3000 px)</li>
                                <li>Escolhe a data de lançamento e confirma</li>
                            </ol>
                            <p class="mb-0">O lançamento é processado em até 72 horas.</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="faq-item">
                    <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false">
                        <h5>Como efectuar um levantamento?</h5>
                        <i class="bi bi-chevron-down faq-icon"></i>
                    </div>
                    <div id="faq2" class="collapse">
                        <div class="faq-answer">
                            <p>Para efectuar um levantamento:</p>
                            <ol class="mb-2">
                                <li>Vai a <strong>Finanças → Visão Geral</strong></li>
                                <li>Clica em <strong>Levantar</strong> no card de saldo</li>
                                <li>Escolhe o método (IBAN, Express, PayPal)</li>
                                <li>Introduz o valor e confirma a senha</li>
                                <li>Aguarda confirmação por e-mail</li>
                            </ol>
                            <p class="mb-0">Prazo: 3 a 5 dias úteis.</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="faq-item">
                    <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false">
                        <h5>O que é a unificação de canal YouTube?</h5>
                        <i class="bi bi-chevron-down faq-icon"></i>
                    </div>
                    <div id="faq3" class="collapse">
                        <div class="faq-answer">
                            <p>A unificação permite:</p>
                            <ul class="mb-2">
                                <li>Ligar os teus canais do YouTube à plataforma</li>
                                <li>Sincronizar Art Tracks automaticamente</li>
                                <li>Acompanhar streams e receitas em tempo real</li>
                                <li>Detectar conteúdo gerado por fãs</li>
                            </ul>
                            <p class="mb-0">Disponível para todos os planos, sem custo adicional.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <!-- FAQ 4 -->
                <div class="faq-item">
                    <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false">
                        <h5>Como funcionam os royalties?</h5>
                        <i class="bi bi-chevron-down faq-icon"></i>
                    </div>
                    <div id="faq4" class="collapse">
                        <div class="faq-answer">
                            <p>Política de royalties <?php echo APP_NAME ?>:</p>
                            <ul class="mb-2">
                                <li><strong>90% de royalties</strong> para todos os planos</li>
                                <li>Pagamentos mensais até ao dia 15</li>
                                <li>Relatórios detalhados por plataforma</li>
                                <li>Valor mínimo para levantamento: 1 000 AOA</li>
                                <li>Sem taxas ocultas nem anuais</li>
                            </ul>
                            <p class="mb-0">Os 10% restantes cobrem custos de distribuição.</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ 5 -->
                <div class="faq-item">
                    <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq5" aria-expanded="false">
                        <h5>Quais formatos de áudio são aceites?</h5>
                        <i class="bi bi-chevron-down faq-icon"></i>
                    </div>
                    <div id="faq5" class="collapse">
                        <div class="faq-answer">
                            <ul class="mb-2">
                                <li><strong>WAV</strong> (recomendado) — 16 ou 24 bits, 44,1 kHz</li>
                                <li><strong>FLAC</strong> — Qualidade sem perdas</li>
                                <li><strong>AIFF</strong> — Compatível com Apple</li>
                                <li><strong>MP3</strong> — 320 kbps (menos recomendado)</li>
                            </ul>
                            <p class="mb-0">Tamanho máximo por ficheiro: 1 GB.</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ 6 -->
                <div class="faq-item">
                    <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq6" aria-expanded="false">
                        <h5>Posso mudar de plano?</h5>
                        <i class="bi bi-chevron-down faq-icon"></i>
                    </div>
                    <div id="faq6" class="collapse">
                        <div class="faq-answer">
                            <ul class="mb-2">
                                <li><strong>Upgrade:</strong> disponível imediatamente</li>
                                <li><strong>Downgrade:</strong> disponível no final do ciclo actual</li>
                                <li>Contacta o <a href="support">suporte</a> para alterações</li>
                                <li>Consulta <a
                                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/services/available-services">Conta
                                        e serviços</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TUTORIAIS -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="sec-title"><i class="bi bi-play-circle-fill"></i>Tutoriais em Vídeo</div>
            </div>

            <?php
            $tutorials = [
                [
                    'icon' => 'bi-person-video3',
                    'color' => '#FF0089,#c8006e',
                    'title' => 'Guia Completo para Iniciantes',
                    'desc' => 'Aprende os primeiros passos na plataforma',
                    'dur' => '15 min',
                    'views' => '2,5k',
                    'slug' => 'iniciante'
                ],
                [
                    'icon' => 'bi-disc-fill',
                    'color' => '#FF4D4D,#c8006e',
                    'title' => 'Como Lançar a Tua Primeira Música',
                    'desc' => 'Passo a passo do processo de lançamento',
                    'dur' => '8 min',
                    'views' => '3,2k',
                    'slug' => 'lancamento'
                ],
                [
                    'icon' => 'bi-bar-chart-fill',
                    'color' => '#FF0089,#7b0044',
                    'title' => 'Analisando Estatísticas e Métricas',
                    'desc' => 'Entende os teus dados e aumenta os streams',
                    'dur' => '12 min',
                    'views' => '1,8k',
                    'slug' => 'estatisticas'
                ],
            ];
            foreach ($tutorials as $t): ?>
            <div class="col-md-4 mb-3">
                <div class="tutorial-card">
                    <div class="tutorial-thumb">
                        <div class="tutorial-thumb-inner">
                            <i class="bi <?php echo $t['icon']; ?> tutorial-icon-bg"></i>
                            <div class="play-btn"><i class="bi bi-play-fill"></i></div>
                        </div>
                    </div>
                    <div class="tutorial-body">
                        <h5><?php echo $t['title']; ?></h5>
                        <p><?php echo $t['desc']; ?></p>
                        <div class="tutorial-meta">
                            <span><i class="bi bi-clock"></i><?php echo $t['dur']; ?></span>
                            <span><i class="bi bi-eye"></i><?php echo $t['views']; ?> visualizações</span>
                        </div>
                        <button class="btn btn-help-outline btn-sm w-100 mt-2 tutorial-btn"
                            data-slug="<?php echo $t['slug']; ?>">
                            Assistir Tutorial
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- SUPORTE PERSONALIZADO -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="sec-title"><i class="bi bi-headset"></i>Precisas de Ajuda Personalizada?</div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="support-option">
                    <i class="bi bi-headset"></i>
                    <h4>Ticket de Suporte</h4>
                    <p>Descreve o teu problema e a equipa responde em até 48 horas</p>
                    <a href="support" class="btn-help btn">
                        <i class="bi bi-send me-2"></i>Enviar Pedido
                    </a>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="support-option">
                    <i class="bi bi-envelope"></i>
                    <h4>E-mail</h4>
                    <p>Resposta em até 48 horas úteis</p>
                    <p class="text-muted small">suporte@wasomupfy.com</p>
                    <a href="mailto:suporte@wasomupfy.com" class="btn-help-outline btn">
                        <i class="bi bi-send me-2"></i>Enviar E-mail
                    </a>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="support-option">
                    <i class="bi bi-whatsapp"></i>
                    <h4>WhatsApp</h4>
                    <p>Atendimento rápido via mensagem</p>
                    <p class="text-muted small">+244 975 818 046</p>
                    <a href="https://wa.me/244975818046" target="_blank" rel="noopener" class="btn-help-outline btn">
                        <i class="bi bi-whatsapp me-2"></i>Chamar no WhatsApp
                    </a>
                </div>
            </div>
        </div>

        <!-- HORÁRIO E LINKS -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="contact-info">
                    <h5 class="mb-3 fw-bold" style="font-size:.95rem">
                        <i class="bi bi-clock me-2" style="color:#FF0089"></i>Horário de Atendimento
                    </h5>
                    <div class="contact-item">
                        <div class="contact-item-icon"><i class="bi bi-calendar-check"></i></div>
                        <div><strong>Segunda a Sexta</strong><span>9h às 18h (WAT)</span></div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-item-icon"><i class="bi bi-calendar"></i></div>
                        <div><strong>Sábado</strong><span>9h às 13h (WAT)</span></div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-item-icon"><i class="bi bi-calendar-x"></i></div>
                        <div><strong>Domingo e Feriados</strong><span>Encerrado</span></div>
                    </div>
                    <hr class="my-3" />
                    <div class="contact-item">
                        <div class="contact-item-icon"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <strong>Tempo médio de resposta</strong>
                            <span>Ticket: 48h &nbsp;·&nbsp; E-mail: 24h &nbsp;·&nbsp; WhatsApp: 1h</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="contact-info">
                    <h5 class="mb-3 fw-bold" style="font-size:.95rem">
                        <i class="bi bi-link-45deg me-2" style="color:#FF0089"></i>Links Úteis
                    </h5>
                    <a href="faq" class="quick-link">
                        <i class="bi bi-question-circle"></i>
                        <div>
                            <h6>Perguntas Frequentes (FAQ)</h6><small>Respostas para as dúvidas mais comuns</small>
                        </div>
                    </a>
                    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/services/available-services"
                        class="quick-link">
                        <i class="bi bi-star"></i>
                        <div>
                            <h6>Planos e Serviços</h6><small>Compara todos os planos disponíveis</small>
                        </div>
                    </a>
                    <a href="support" class="quick-link">
                        <i class="bi bi-headset"></i>
                        <div>
                            <h6>Enviar Ticket de Suporte</h6><small>Abre um pedido directamente à nossa equipa</small>
                        </div>
                    </a>
                    <a href="about" class="quick-link">
                        <i class="bi bi-info-circle"></i>
                        <div>
                            <h6>Sobre o <?php echo APP_NAME ?></h6><small>Conhece a nossa história e missão</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mb-4">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Central de Ajuda V<?php echo APP_VERSION ?> — <?php echo APP_NAME; ?>
            </small>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- theme.wp.js gere o tema globalmente — zero código de tema inline nesta página -->
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {

        // ── Pesquisa ────────────────────────────────────────
        function doSearch() {
            var term = document.getElementById('helpSearch').value.trim();
            if (term) window.location.href = 'faq?search=' + encodeURIComponent(term);
        }

        var searchBtn = document.getElementById('searchBtn');
        var searchInput = document.getElementById('helpSearch');

        if (searchBtn) searchBtn.addEventListener('click', doSearch);
        if (searchInput) searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') doSearch();
        });

        // ── Tutoriais ───────────────────────────────────────
        document.querySelectorAll('.tutorial-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var slug = this.dataset.slug;
                // Redireccionamento futuro: window.location.href = '../tutorials/' + slug;
                // Por agora abre um modal ou alerta temporário
                alert('Tutorial "' + slug + '" em breve disponível!');
            });
        });

        // ── FAQ arrow sync ──────────────────────────────────
        // O Bootstrap gere o collapse, mas o chevron precisa de ser rotacionado
        // via CSS (.faq-question[aria-expanded="true"] .faq-icon) — já está no CSS.
        // Forçar o aria-expanded no parent quando o collapse abre/fecha:
        document.querySelectorAll('.faq-item .collapse').forEach(function(collapseEl) {
            collapseEl.addEventListener('show.bs.collapse', function() {
                var btn = this.closest('.faq-item').querySelector('.faq-question');
                if (btn) btn.setAttribute('aria-expanded', 'true');
            });
            collapseEl.addEventListener('hide.bs.collapse', function() {
                var btn = this.closest('.faq-item').querySelector('.faq-question');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            });
        });

    }); // fim DOMContentLoaded
    </script>
</body>

</html>