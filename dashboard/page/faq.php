<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Perguntas Frequentes (FAQ)
// Arquivo: dashboard/page/faq.php
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

?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title data-i18n="faq_title">Perguntas Frequentes — <?php echo APP_NAME; ?></title>
    <style>
    /* ══ Progress bar de leitura ══ */
    .read-progress {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        z-index: 9999;
        background: var(--border-color, rgba(0, 0, 0, .08));
    }

    .read-progress-fill {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #FF0089, #FF4D4D);
        transition: width .1s linear;
    }

    /* ══ Hero ══ */
    .faq-hero {
        background: linear-gradient(135deg, #FF0089 0%, #FF4D4D 100%);
        border-radius: 22px;
        padding: 2.8rem 2rem 2.2rem;
        margin-bottom: 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        text-align: center;
    }

    .faq-hero::before {
        content: '\F44F';
        font-family: 'bootstrap-icons';
        position: absolute;
        right: -20px;
        bottom: -28px;
        font-size: 11rem;
        opacity: .07;
    }

    .faq-hero h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: .6rem;
        position: relative;
        z-index: 2;
    }

    .faq-hero p {
        font-size: 1.05rem;
        max-width: 660px;
        margin: 0 auto .5rem;
        opacity: .9;
        position: relative;
        z-index: 2;
    }

    .faq-hero .update-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, .18);
        border: 1px solid rgba(255, 255, 255, .3);
        border-radius: 999px;
        padding: 3px 14px;
        font-size: .75rem;
        font-weight: 600;
        position: relative;
        z-index: 2;
        margin-bottom: .5rem;
    }

    /* ══ Search ══ */
    .faq-search-wrap {
        max-width: 580px;
        margin: 1.5rem auto 0;
        position: relative;
        z-index: 2;
    }

    .faq-search-wrap .input-group {
        background: #fff;
        border-radius: 50px;
        overflow: hidden;
        box-shadow: 0 8px 22px rgba(0, 0, 0, .18);
    }

    .faq-search-wrap input {
        border: none;
        padding: .85rem 1.4rem;
        font-size: .93rem;
    }

    .faq-search-wrap input:focus {
        box-shadow: none;
    }

    .faq-search-wrap .search-icon-btn {
        background: #fff;
        border: none;
        padding: 0 1.6rem;
        color: #FF0089;
        font-size: 1.1rem;
    }

    /* ══ Action buttons ══ */
    .action-btns {
        display: flex;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }

    .action-btns a,
    .action-btns button {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: .4rem 1.1rem;
        border-radius: 999px;
        font-size: .8rem;
        font-weight: 600;
        border: 1.5px solid rgba(255, 0, 137, .35);
        color: #FF0089;
        background: transparent;
        text-decoration: none;
        transition: all .2s;
        cursor: pointer;
    }

    .action-btns a:hover,
    .action-btns button:hover {
        background: #FF0089;
        color: #fff;
        border-color: #FF0089;
    }

    /* ══ Category filter tabs ══ */
    .cat-filter {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: center;
        margin-bottom: 1.8rem;
    }

    .cat-btn {
        padding: .38rem 1.1rem;
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

    .cat-btn:hover {
        border-color: #FF0089;
        color: #FF0089;
    }

    .cat-btn.active {
        background: #FF0089;
        border-color: #FF0089;
        color: #fff;
    }

    /* ══ Index nav (sidebar) ══ */
    .nav-index {
        background: var(--card-bg, #fff);
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        border-radius: 16px;
        padding: 1.4rem;
        margin-bottom: 1.5rem;
    }

    .nav-index h3 {
        font-size: .9rem;
        font-weight: 800;
        color: #FF0089;
        margin-bottom: .9rem;
    }

    .nav-index ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .nav-index .index-item {
        margin-bottom: .4rem;
    }

    .nav-index .index-item a {
        font-size: .8rem;
        color: var(--text-muted, #6c757d);
        text-decoration: none;
        display: flex;
        align-items: flex-start;
        gap: 6px;
        line-height: 1.4;
        transition: color .15s;
    }

    .nav-index .index-item a::before {
        content: '›';
        color: #FF0089;
        flex-shrink: 0;
    }

    .nav-index .index-item a:hover {
        color: #FF0089;
    }

    .nav-index .index-item.hidden {
        display: none;
    }

    /* ══ FAQ items (custom accordion — preserva faq.js) ══ */
    .faq-content {}

    .faq-item {
        background: var(--card-bg, #fff);
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        border-left: 4px solid transparent;
        border-radius: 14px;
        margin-bottom: .8rem;
        overflow: hidden;
        transition: border-color .2s, box-shadow .2s;
        display: none;
        /* controlado por JS / filtro */
    }

    .faq-item.visible {
        display: block;
    }

    .faq-item:hover {
        border-left-color: #FF0089;
    }

    .faq-item.active {
        border-left-color: #FF0089;
        box-shadow: 0 4px 16px rgba(255, 0, 137, .1);
    }

    .faq-item .question {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 1.1rem 1.3rem;
        cursor: pointer;
        user-select: none;
        font-weight: 600;
        font-size: .92rem;
    }

    .faq-item .question>i:first-child {
        color: #FF0089;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .faq-item .question span {
        flex: 1;
    }

    .faq-item .question .toggle-icon {
        color: var(--text-muted, #6c757d);
        transition: transform .3s;
        flex-shrink: 0;
    }

    .faq-item.active .question .toggle-icon {
        transform: rotate(180deg);
    }

    .faq-item .answer {
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        padding: 0 1.3rem;
        font-size: .87rem;
        color: var(--text-muted, #6c757d);
        line-height: 1.7;
        transition: max-height .35s ease, opacity .3s ease, padding .3s ease;
    }

    .faq-item.active .answer {
        padding: 0 1.3rem 1.2rem;
    }

    .faq-item .answer mark {
        background: rgba(255, 0, 137, .18);
        color: inherit;
        border-radius: 3px;
        padding: 0 2px;
    }

    /* ══ Category badge on item ══ */
    .faq-cat-tag {
        font-size: .65rem;
        font-weight: 700;
        padding: .2rem .6rem;
        border-radius: 999px;
        background: rgba(255, 0, 137, .1);
        color: #FF0089;
        flex-shrink: 0;
    }

    /* ══ No results ══ */
    #noResults {
        text-align: center;
        padding: 2.5rem 1rem;
        display: none;
        color: var(--text-muted, #6c757d);
    }

    #noResults i {
        font-size: 2.5rem;
        color: #FF0089;
        opacity: .4;
        display: block;
        margin-bottom: .8rem;
    }

    /* ══ Tips ══ */
    .tips-section {
        background: var(--card-bg, #fff);
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        border-radius: 16px;
        padding: 1.5rem;
        margin-top: 1.5rem;
    }

    .tips-section h2 {
        font-size: 1.05rem;
        font-weight: 800;
        color: #FF0089;
        margin-bottom: 1rem;
    }

    .tip-card {
        background: var(--metric-bg, rgba(0, 0, 0, .03));
        border-radius: 10px;
        padding: .75rem 1rem;
        font-size: .85rem;
        margin-bottom: .6rem;
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }

    .tip-card i {
        color: #FF0089;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .tip-card:last-child {
        margin-bottom: 0;
    }

    /* ══ Tutorial section ══ */
    .tutorial-section {
        background: linear-gradient(135deg, rgba(255, 0, 137, .07), rgba(255, 77, 77, .05));
        border: 1.5px solid rgba(255, 0, 137, .2);
        border-radius: 16px;
        padding: 1.8rem;
        text-align: center;
        margin-top: .8rem;
    }

    .tutorial-section h2 {
        font-size: 1.05rem;
        font-weight: 800;
        color: #FF0089;
        margin-bottom: 1rem;
    }

    .tutorial-btn {
        background: #FF0089;
        border: none;
        color: #fff;
        padding: .55rem 2rem;
        border-radius: 999px;
        font-weight: 700;
        transition: all .2s;
    }

    .tutorial-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(255, 0, 137, .35);
    }

    /* ══ Back to top ══ */
    #backToTop {
        position: fixed;
        bottom: 80px;
        right: 20px;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #FF0089;
        color: #fff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        box-shadow: 0 4px 14px rgba(255, 0, 137, .4);
        opacity: 0;
        pointer-events: none;
        transition: opacity .25s;
        z-index: 1000;
        cursor: pointer;
    }

    #backToTop.visible {
        opacity: 1;
        pointer-events: auto;
    }

    /* ══ Support float btn ══ */
    .support-btn {
        position: fixed;
        bottom: 80px;
        left: 20px;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #FF0089;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        box-shadow: 0 4px 14px rgba(255, 0, 137, .4);
        text-decoration: none;
        z-index: 1000;
        transition: transform .2s;
    }

    .support-btn:hover {
        transform: scale(1.1);
        color: #fff;
    }

    @media(max-width:768px) {
        .faq-hero h1 {
            font-size: 1.9rem;
        }

        .faq-hero {
            padding: 2rem 1rem 1.8rem;
        }
    }
    </style>
</head>

<body>

    <!-- Barra de progresso de leitura -->
    <div class="read-progress">
        <div class="read-progress-fill" id="progressBar"></div>
    </div>

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
        <div class="faq-hero">
            <div class="update-badge"><i class="bi bi-clock"></i> <span data-i18n="faq_update_date">Última actualização:
                    11 de Março de 2026</span></div>
            <h1 data-i18n="faq_title">Perguntas Frequentes</h1>
            <p data-i18n="faq_description">
                Encontra respostas para as perguntas mais comuns sobre a plataforma <?php echo APP_NAME ?>.<br />
                Não encontraste o que procuravas? <a href="support" class="text-white fw-bold">Entra em contacto com o
                    suporte!</a>
            </p>
            <!-- Search -->
            <div class="faq-search-wrap">
                <div class="input-group">
                    <input type="text" id="faqSearch" class="form-control" placeholder="Pesquisar perguntas..."
                        data-i18n-placeholder="search_placeholder" oninput="searchFAQ()" />
                    <button class="search-icon-btn" type="button">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="action-btns">
            <a href="faq.pdf" download><i class="bi bi-file-earmark-pdf"></i> <span data-i18n="download_pdf">Descarregar
                    em PDF</span></a>
            <button onclick="printFAQ()"><i class="bi bi-printer"></i> <span data-i18n="print">Imprimir</span></button>
            <!-- Language selector (opcional — habilita em faq.js: enableI18n = true) -->
            <div id="languageSelector" style="display:none">
                <select id="languageSelect" onchange="changeLanguage(this.value)" class="form-select form-select-sm"
                    style="border-radius:999px;font-size:.78rem;width:auto">
                    <option value="pt-AO">Português (AO)</option>
                    <option value="en-US">English</option>
                </select>
            </div>
        </div>

        <!-- CATEGORY FILTER -->
        <div class="cat-filter" id="catFilter">
            <button class="cat-btn active" data-cat="all" onclick="filterCategory('all')"><i
                    class="bi bi-grid me-1"></i><span data-i18n="cat_all">Todas</span></button>
            <button class="cat-btn" data-cat="conta" onclick="filterCategory('conta')"><i
                    class="bi bi-person-circle me-1"></i><span data-i18n="cat_conta">Conta</span></button>
            <button class="cat-btn" data-cat="lancamentos" onclick="filterCategory('lancamentos')"><i
                    class="bi bi-disc me-1"></i><span data-i18n="cat_lancamentos">Lançamentos</span></button>
            <button class="cat-btn" data-cat="financeiro" onclick="filterCategory('financeiro')"><i
                    class="bi bi-currency-dollar me-1"></i><span data-i18n="cat_financeiro">Financeiro</span></button>
            <button class="cat-btn" data-cat="artistas" onclick="filterCategory('artistas')"><i
                    class="bi bi-music-note-list me-1"></i><span data-i18n="cat_artistas">Artistas</span></button>
            <button class="cat-btn" data-cat="estatisticas" onclick="filterCategory('estatisticas')"><i
                    class="bi bi-bar-chart me-1"></i><span data-i18n="cat_estatisticas">Estatísticas</span></button>
            <button class="cat-btn" data-cat="youtube" onclick="filterCategory('youtube')"><i
                    class="bi bi-youtube me-1"></i><span data-i18n="cat_youtube">YouTube</span></button>
            <button class="cat-btn" data-cat="planos" onclick="filterCategory('planos')"><i
                    class="bi bi-star me-1"></i><span data-i18n="cat_planos">Planos</span></button>
            <button class="cat-btn" data-cat="suporte" onclick="filterCategory('suporte')"><i
                    class="bi bi-headset me-1"></i><span data-i18n="cat_suporte">Suporte</span></button>
        </div>

        <div class="row g-4">

            <!-- FAQ + Conteúdo -->
            <div class="col-lg-8">

                <!-- Navigation Index (mobile — accordion) -->
                <div class="nav-index d-lg-none mb-3 fade-in-custom" id="navIndexMobile">
                    <h3 data-i18n="index_title"><i class="bi bi-list-ol me-2"></i>Índice</h3>
                    <ul id="indexListMobile">
                        <!-- preenchido dinamicamente por updateIndex() -->
                    </ul>
                </div>

                <section class="faq-content fade-in-custom" id="faqContent">

                    <!-- ════════════════════════════════════════
                     CONTA E PERFIL
                ════════════════════════════════════════ -->

                    <div class="faq-item visible" id="faq1" data-category="conta">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-person-plus"></i>
                            <span data-i18n="faq1_question">Como cadastrar um novo artista?</span>
                            <span class="faq-cat-tag" data-i18n="cat_conta">Conta</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq1-answer" data-i18n="faq1_answer">
                            Acede à secção <strong>Artistas</strong> no menu, clica em <strong>Adicionar Novo</strong> e
                            preenche os dados solicitados — nome artístico, nome real, foto, bio e informações de
                            contacto. Após rever, guarda as alterações. O processo leva poucos minutos. Certifica-te de
                            que os dados estão correctos para evitar problemas futuros.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq2" data-category="conta">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-person-gear"></i>
                            <span data-i18n="faq2_question">Como actualizar os meus dados de perfil?</span>
                            <span class="faq-cat-tag" data-i18n="cat_conta">Conta</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq2-answer" data-i18n="faq2_answer">
                            Vai a <strong>Meu Perfil</strong> no menu do utilizador (ícone de pessoa no canto superior
                            direito). Podes actualizar o teu nome, foto de perfil, e-mail e palavra-passe. Após editar,
                            clica em <strong>Guardar alterações</strong>. Algumas alterações como o e-mail podem
                            requerer confirmação adicional.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq3" data-category="conta">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-lock"></i>
                            <span data-i18n="faq3_question">O que fazer se esquecer a palavra-passe?</span>
                            <span class="faq-cat-tag" data-i18n="cat_conta">Conta</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq3-answer" data-i18n="faq3_answer">
                            Vai à página de <strong>login</strong>, clica em <em>"Esqueceu a palavra-passe?"</em> e
                            segue as instruções. Receberás um e-mail com um link para criar uma nova palavra-passe.
                            Verifica a pasta de spam se o e-mail não aparecer na caixa de entrada. O link expira em 30
                            minutos.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq4" data-category="conta">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-shield-lock"></i>
                            <span data-i18n="faq4_question">Como activar a autenticação de dois factores (2FA)?</span>
                            <span class="faq-cat-tag" data-i18n="cat_conta">Conta</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq4-answer" data-i18n="faq4_answer">
                            Acede a <strong>Configurações → Segurança</strong> e activa a opção <em>Autenticação de dois
                                factores</em>. Será gerado um código QR para ligar ao teu autenticador (Google
                            Authenticator, Authy, etc.). A partir daí, cada login pedirá o código do autenticador além
                            da palavra-passe.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq5" data-category="conta">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span data-i18n="faq5_question">A minha conta foi suspensa. O que devo fazer?</span>
                            <span class="faq-cat-tag" data-i18n="cat_conta">Conta</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq5-answer" data-i18n="faq5_answer">
                            Contas são suspensas por violação dos <a href="terms">Termos de Uso</a> ou actividade
                            suspeita. Se acreditas que foi um erro, envia um <a href="support">pedido de suporte</a>
                            explicando a situação. A equipa irá rever e responder em até 48 horas. Não cries uma nova
                            conta enquanto o processo está em análise.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq6" data-category="conta">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-moon-stars"></i>
                            <span data-i18n="faq6_question">Como funciona o modo escuro?</span>
                            <span class="faq-cat-tag" data-i18n="cat_conta">Conta</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq6-answer" data-i18n="faq6_answer">
                            Clica no ícone de sol/lua na barra de navegação para alternar entre modo claro e escuro. A
                            preferência é guardada automaticamente. Podes também definir o tema nas <a
                                href="settings">Configurações</a>. O modo escuro é ideal para ambientes com pouca luz,
                            reduzindo o cansaço visual.
                        </div>
                    </div>

                    <!-- ════════════════════════════════════════
                     LANÇAMENTOS
                ════════════════════════════════════════ -->

                    <div class="faq-item visible" id="faq7" data-category="lancamentos">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-disc"></i>
                            <span data-i18n="faq7_question">Como criar um novo lançamento?</span>
                            <span class="faq-cat-tag" data-i18n="cat_lancamentos">Lançamentos</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq7-answer" data-i18n="faq7_answer">
                            Vai a <strong>Lançamentos → Novo Lançamento</strong>. Preenche o título, artista, género e
                            data de lançamento. Faz upload do ficheiro de áudio (WAV ou FLAC recomendado) e da capa
                            (mínimo 3000×3000 px, formato JPG/PNG). Revê todas as informações e confirma o envio. O
                            lançamento será processado em até 72 horas.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq8" data-category="lancamentos">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-file-music"></i>
                            <span data-i18n="faq8_question">Quais formatos de áudio são aceites?</span>
                            <span class="faq-cat-tag" data-i18n="cat_lancamentos">Lançamentos</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq8-answer" data-i18n="faq8_answer">
                            Formatos aceites: <strong>WAV</strong> (recomendado — 16 ou 24 bits, 44,1 kHz),
                            <strong>FLAC</strong> (sem perdas), <strong>AIFF</strong> (compatível Apple) e
                            <strong>MP3</strong> a 320 kbps (menos recomendado). Tamanho máximo por ficheiro: <strong>1
                                GB</strong>. Não aceitamos ficheiros comprimidos (ZIP, RAR) nem formatos de vídeo como
                            MP4.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq9" data-category="lancamentos">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-image"></i>
                            <span data-i18n="faq9_question">Qual é o requisito mínimo para a capa?</span>
                            <span class="faq-cat-tag" data-i18n="cat_lancamentos">Lançamentos</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq9-answer" data-i18n="faq9_answer">
                            A capa deve ter no mínimo <strong>3000×3000 pixels</strong>, formato quadrado (1:1), em JPG
                            ou PNG com qualidade máxima. Não deve conter logótipos de lojas (Spotify, Apple Music,
                            etc.), URLs, informações de contacto ou conteúdo explícito sem a marcação correcta. Uma capa
                            de baixa qualidade pode resultar na rejeição do lançamento.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq10" data-category="lancamentos">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-clock-history"></i>
                            <span data-i18n="faq10_question">Quanto tempo demora a distribuição?</span>
                            <span class="faq-cat-tag" data-i18n="cat_lancamentos">Lançamentos</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq10-answer" data-i18n="faq10_answer">
                            Após aprovação interna (até 72 horas), o lançamento é enviado às plataformas. O tempo de
                            disponibilização varia por plataforma: <strong>Spotify</strong> e <strong>Apple
                                Music</strong> geralmente em 3–7 dias, outras plataformas em até 14 dias. Recomendamos
                            submeter com pelo menos <strong>2 semanas</strong> de antecedência.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq11" data-category="lancamentos">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-pencil-square"></i>
                            <span data-i18n="faq11_question">Posso editar um lançamento após o envio?</span>
                            <span class="faq-cat-tag" data-i18n="cat_lancamentos">Lançamentos</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq11-answer" data-i18n="faq11_answer">
                            Enquanto o lançamento está em estado <strong>rascunho</strong> ou <strong>em
                                revisão</strong>, podes editar livremente. Após ser <strong>distribuído</strong>,
                            algumas informações como título e artista <em>não podem ser alteradas</em> pois já estão nas
                            plataformas. Para alterações urgentes, contacta o <a href="support">suporte</a>.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq12" data-category="lancamentos">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-calendar-event"></i>
                            <span data-i18n="faq12_question">Como agendar uma data de lançamento?</span>
                            <span class="faq-cat-tag" data-i18n="cat_lancamentos">Lançamentos</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq12-answer" data-i18n="faq12_answer">
                            Durante a criação do lançamento, no campo <strong>Data de lançamento</strong>, selecciona
                            uma data futura. O sistema enviará automaticamente às plataformas na data indicada. Para que
                            a distribuição esteja completa na data desejada, submete com pelo menos 2 semanas de
                            antecedência.
                        </div>
                    </div>

                    <!-- ════════════════════════════════════════
                     FINANCEIRO
                ════════════════════════════════════════ -->

                    <div class="faq-item visible" id="faq13" data-category="financeiro">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-wallet2"></i>
                            <span data-i18n="faq13_question">Como ver o meu saldo disponível?</span>
                            <span class="faq-cat-tag" data-i18n="cat_financeiro">Financeiro</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq13-answer" data-i18n="faq13_answer">
                            O teu saldo aparece no topo da página <strong>Finanças → Visão Geral</strong>. É dividido em
                            <em>saldo disponível</em> (pronto para levantamento) e <em>saldo pendente</em> (em
                            processamento). Podes também ver o histórico de transacções em <strong>Finanças →
                                Transacções</strong>.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq14" data-category="financeiro">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-bank"></i>
                            <span data-i18n="faq14_question">Como efectuar um levantamento?</span>
                            <span class="faq-cat-tag" data-i18n="cat_financeiro">Financeiro</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq14-answer" data-i18n="faq14_answer">
                            Vai a <strong>Finanças → Levantamentos</strong>, escolhe o método (IBAN, Express, PayPal),
                            introduz o valor desejado e confirma a tua palavra-passe. Receberás um e-mail de
                            confirmação. O prazo de processamento é de <strong>3 a 5 dias úteis</strong> dependendo do
                            método escolhido.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq15" data-category="financeiro">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-cash-coin"></i>
                            <span data-i18n="faq15_question">Qual é o valor mínimo para levantamento?</span>
                            <span class="faq-cat-tag" data-i18n="cat_financeiro">Financeiro</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq15-answer" data-i18n="faq15_answer">
                            O valor mínimo para levantamento é de <strong>1.000 AOA</strong>. Não há valor máximo por
                            pedido, mas existem limites mensais dependendo do teu plano. Consulta a página <a
                                href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/services/available-services">Conta e
                                serviços disponíveis</a> para ver os
                            limites do teu plano.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq16" data-category="financeiro">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-percent"></i>
                            <span data-i18n="faq16_question">Como funcionam os royalties?</span>
                            <span class="faq-cat-tag" data-i18n="cat_financeiro">Financeiro</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq16-answer" data-i18n="faq16_answer">
                            A <?php echo APP_NAME ?> distribui <strong>90% dos royalties</strong> directamente ao
                            artista. Os
                            restantes 10% cobrem custos de distribuição e operação da plataforma. Os royalties são
                            calculados com base nos streams e downloads em cada plataforma, e actualizados mensalmente
                            nos relatórios de estatísticas.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq17" data-category="financeiro">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-calendar-check"></i>
                            <span data-i18n="faq17_question">Quando recebo os pagamentos de royalties?</span>
                            <span class="faq-cat-tag" data-i18n="cat_financeiro">Financeiro</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq17-answer" data-i18n="faq17_answer">
                            Os royalties são processados e creditados na tua carteira até ao dia <strong>15 de cada
                                mês</strong>, referentes ao mês anterior. Algumas plataformas como o Spotify têm um
                            atraso de 2–3 meses nos relatórios, o que pode influenciar o valor visível. Receberás uma
                            notificação quando o saldo for actualizado.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq18" data-category="financeiro">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-diagram-3"></i>
                            <span data-i18n="faq18_question">Como funciona a divisão de royalties entre
                                colaboradores?</span>
                            <span class="faq-cat-tag" data-i18n="cat_financeiro">Financeiro</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq18-answer" data-i18n="faq18_answer">
                            Vai a <strong>Finanças → Visão Geral → Divisão de Royalties</strong>. Podes configurar
                            percentagens para cada colaborador por lançamento ou álbum. O sistema divide automaticamente
                            os royalties conforme definido e cada colaborador pode ver a sua parte no próprio painel. A
                            soma das percentagens deve ser sempre 100%.
                        </div>
                    </div>

                    <!-- ════════════════════════════════════════
                     ARTISTAS
                ════════════════════════════════════════ -->

                    <div class="faq-item visible" id="faq19" data-category="artistas">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-people"></i>
                            <span data-i18n="faq19_question">Posso ter vários artistas na mesma conta?</span>
                            <span class="faq-cat-tag" data-i18n="cat_artistas">Artistas</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq19-answer" data-i18n="faq19_answer">
                            Sim. Dependendo do teu plano, podes criar múltiplos artistas na mesma conta. O plano
                            <strong>Label</strong> tem número ilimitado de artistas, enquanto os planos
                            <strong>Artist</strong> e <strong>Album</strong> têm limites. Consulta os detalhes do teu
                            plano em <a
                                href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/services/available-services">Conta e
                                serviços</a>.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq20" data-category="artistas">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-person-check"></i>
                            <span data-i18n="faq20_question">Como adicionar um colaborador à minha conta?</span>
                            <span class="faq-cat-tag" data-i18n="cat_artistas">Artistas</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq20-answer" data-i18n="faq20_answer">
                            Vai a <strong>Gestão de Conta → Colaboradores</strong> e clica em <strong>Convidar
                                Colaborador</strong>. Introduz o e-mail da pessoa e define as permissões (visualização,
                            edição, finanças). A pessoa receberá um convite por e-mail. Colaboradores têm acesso
                            limitado conforme as permissões que atribuíres.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq21" data-category="artistas">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-share"></i>
                            <span data-i18n="faq21_question">Como vincular redes sociais ao perfil do artista?</span>
                            <span class="faq-cat-tag" data-i18n="cat_artistas">Artistas</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq21-answer" data-i18n="faq21_answer">
                            Vai a <strong>Artistas → [nome do artista] → Editar Perfil</strong>. Na secção de redes
                            sociais, podes adicionar links para Instagram, Facebook, YouTube, Spotify, Apple Music,
                            TikTok e website pessoal. Estes links aparecem no perfil público do artista e nas lojas que
                            os suportam.
                        </div>
                    </div>

                    <!-- ════════════════════════════════════════
                     ESTATÍSTICAS
                ════════════════════════════════════════ -->

                    <div class="faq-item visible" id="faq22" data-category="estatisticas">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-bar-chart"></i>
                            <span data-i18n="faq22_question">Como ver as estatísticas das minhas músicas?</span>
                            <span class="faq-cat-tag" data-i18n="cat_estatisticas">Estatísticas</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq22-answer" data-i18n="faq22_answer">
                            Acede a <strong>Estatísticas</strong> no menu principal. Podes filtrar por artista, álbum,
                            faixa, período de tempo e plataforma. Os dados são apresentados em gráficos interactivos e
                            tabelas. Clica em qualquer artista para ver os detalhes completos incluindo países,
                            playlists e evolução temporal.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq23" data-category="estatisticas">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-shop"></i>
                            <span data-i18n="faq23_question">Que plataformas aparecem nas estatísticas?</span>
                            <span class="faq-cat-tag" data-i18n="cat_estatisticas">Estatísticas</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq23-answer" data-i18n="faq23_answer">
                            As estatísticas incluem: <strong>Spotify, Apple Music, YouTube Music, Deezer, Tidal, Amazon
                                Music, Boomplay, TikTok, iTunes</strong> e outras lojas onde os teus lançamentos estejam
                            distribuídos. Nem todas as plataformas reportam em tempo real — algumas têm atraso de 24–72
                            horas.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq24" data-category="estatisticas">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-download"></i>
                            <span data-i18n="faq24_question">Como exportar os dados de estatísticas?</span>
                            <span class="faq-cat-tag" data-i18n="cat_estatisticas">Estatísticas</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq24-answer" data-i18n="faq24_answer">
                            Em <strong>Estatísticas → Exportar</strong>, selecciona o período, artistas e plataformas
                            que queres incluir. Podes exportar em <strong>CSV</strong> para análise em Excel, ou em
                            <strong>PDF</strong> como relatório formatado. Para relatórios completos pré-gerados, vai a
                            <strong>Estatísticas → Relatórios</strong>.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq25" data-category="estatisticas">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-arrow-repeat"></i>
                            <span data-i18n="faq25_question">Com que frequência as estatísticas são actualizadas?</span>
                            <span class="faq-cat-tag" data-i18n="cat_estatisticas">Estatísticas</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq25-answer" data-i18n="faq25_answer">
                            As estatísticas são actualizadas <strong>diariamente</strong> para a maioria das
                            plataformas. O Spotify e o Apple Music fornecem dados com até 1 dia de atraso. Plataformas
                            como Boomplay e Amazon Music podem ter atraso de 3–5 dias. Os totais mensais são definitivos
                            apenas após o fecho do mês.
                        </div>
                    </div>

                    <!-- ════════════════════════════════════════
                     YOUTUBE
                ════════════════════════════════════════ -->

                    <div class="faq-item visible" id="faq26" data-category="youtube">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-youtube"></i>
                            <span data-i18n="faq26_question">O que é a unificação de canal YouTube?</span>
                            <span class="faq-cat-tag" data-i18n="cat_youtube">YouTube</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq26-answer" data-i18n="faq26_answer">
                            A unificação de canal permite ligar o teu canal YouTube à plataforma para sincronizar Art
                            Tracks automaticamente, acompanhar streams e receitas em tempo real, gerir vídeos musicais e
                            detectar conteúdo gerado por fãs. Disponível para todos os planos, sem custo adicional.
                            Acede em <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/youtube/ucy">Artistas →
                                YouTube</a>.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq27" data-category="youtube">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-patch-check"></i>
                            <span data-i18n="faq27_question">Como verificar o meu canal YouTube?</span>
                            <span class="faq-cat-tag" data-i18n="cat_youtube">YouTube</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq27-answer" data-i18n="faq27_answer">
                            Vai a <strong>Artistas → Unificação YouTube → Registar Canal</strong>. Após submeter o URL
                            do canal, receberás um código de verificação no formato <code>WASOM-XXXXXXXX</code>.
                            Adiciona este código à descrição do teu canal YouTube (ou num vídeo específico) e aguarda a
                            confirmação, que demora até 48 horas.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq28" data-category="youtube">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-music-note-beamed"></i>
                            <span data-i18n="faq28_question">O que é um Art Track?</span>
                            <span class="faq-cat-tag" data-i18n="cat_youtube">YouTube</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq28-answer" data-i18n="faq28_answer">
                            Um <strong>Art Track</strong> é um vídeo automático criado pelo YouTube Music com a capa do
                            teu lançamento como imagem estática e a música como áudio. É criado automaticamente quando
                            distribuis música pelo YouTube Music. Podes monetizá-lo através da unificação do canal,
                            gerando receita adicional.
                        </div>
                    </div>

                    <!-- ════════════════════════════════════════
                     PLANOS
                ════════════════════════════════════════ -->

                    <div class="faq-item visible" id="faq29" data-category="planos">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-star"></i>
                            <span data-i18n="faq29_question">Quais planos estão disponíveis?</span>
                            <span class="faq-cat-tag" data-i18n="cat_planos">Planos</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq29-answer" data-i18n="faq29_answer">
                            A <?php echo APP_NAME ?> oferece quatro planos: <strong>Single</strong> (2.000 AOA por
                            lançamento — 1
                            faixa), <strong>Album</strong> (5.000 AOA por lançamento — até 20 faixas),
                            <strong>Artist</strong> (11.400 AOA/mês — lançamentos ilimitados, 1 artista) e
                            <strong>Label</strong> (70.000 AOA/mês — lançamentos ilimitados, artistas ilimitados).
                            Consulta <a
                                href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/services/available-services">Conta e
                                serviços</a> para detalhes.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq30" data-category="planos">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-arrow-up-circle"></i>
                            <span data-i18n="faq30_question">Posso mudar de plano?</span>
                            <span class="faq-cat-tag" data-i18n="cat_planos">Planos</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq30-answer" data-i18n="faq30_answer">
                            Sim. <strong>Upgrade</strong> (plano superior): disponível imediatamente após pagamento.
                            <strong>Downgrade</strong> (plano inferior): entra em vigor no final do ciclo actual, para
                            não perder benefícios já pagos. Contacta o <a href="support">suporte</a> para iniciar a
                            mudança ou acede a <a
                                href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/services/available-services">Conta e
                                serviços</a>.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq31" data-category="planos">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-credit-card"></i>
                            <span data-i18n="faq31_question">Como activar o meu plano após o pagamento?</span>
                            <span class="faq-cat-tag" data-i18n="cat_planos">Planos</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq31-answer" data-i18n="faq31_answer">
                            Após efectuar o pagamento (transferência bancária ou outro método disponível), faz upload do
                            comprovante em <strong>Conta → Activar Plano</strong>. A equipa irá verificar e activar o
                            plano em até 24 horas úteis. Receberás uma notificação por e-mail quando o plano for
                            activado.
                        </div>
                    </div>

                    <!-- ════════════════════════════════════════
                     SUPORTE
                ════════════════════════════════════════ -->

                    <div class="faq-item visible" id="faq32" data-category="suporte">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-headset"></i>
                            <span data-i18n="faq32_question">Como enviar um pedido de suporte?</span>
                            <span class="faq-cat-tag" data-i18n="cat_suporte">Suporte</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq32-answer" data-i18n="faq32_answer">
                            Vai a <a href="support"><strong>Suporte</strong></a>, selecciona o tipo de problema, o nível
                            de urgência e descreve o problema com o máximo de detalhe. Podes anexar ficheiros (capturas
                            de ecrã, documentos) até 10 MB cada. O limite é de 5 pedidos por hora. A equipa responde em
                            até 48 horas úteis.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq33" data-category="suporte">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-clock-fill"></i>
                            <span data-i18n="faq33_question">Qual é o prazo de resposta do suporte?</span>
                            <span class="faq-cat-tag" data-i18n="cat_suporte">Suporte</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq33-answer" data-i18n="faq33_answer">
                            O suporte funciona de Segunda a Sexta das 9h às 18h (WAT) e Sábado das 9h às 13h. Tickets
                            são respondidos em até <strong>48 horas úteis</strong>. Tickets marcados como
                            <em>urgentes</em> têm prioridade de resposta em até 24 horas. Fora do horário, os pedidos
                            ficam em fila e são atendidos no próximo dia útil.
                        </div>
                    </div>

                    <div class="faq-item visible" id="faq34" data-category="suporte">
                        <div class="question" onclick="toggleFAQ(this)" role="button" aria-expanded="false">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span data-i18n="faq34_question">Como solicitar um reembolso?</span>
                            <span class="faq-cat-tag" data-i18n="cat_suporte">Suporte</span>
                            <i class="bi bi-chevron-down toggle-icon"></i>
                        </div>
                        <div class="answer" id="faq34-answer" data-i18n="faq34_answer">
                            Envia um <a href="support">pedido de suporte</a> com o tipo <em>"Pedido de reembolso"</em>,
                            incluindo o número da transacção, data do pagamento e o motivo do pedido. Reembolsos são
                            processados em até 10 dias úteis, mediante análise. Pagamentos por transferência bancária
                            são devolvidos para a mesma conta de origem.
                        </div>
                    </div>

                    <!-- ════ Sem resultados ════ -->
                    <div id="noResults">
                        <i class="bi bi-search"></i>
                        <p class="fw-semibold mb-1">Nenhum resultado encontrado</p>
                        <small>Tenta outros termos ou <a href="support">contacta o suporte</a>.</small>
                    </div>

                    <!-- ════ Tips ════ -->
                    <div class="tips-section" id="tips">
                        <h2><i class="bi bi-lightning-charge me-2"></i><span data-i18n="tips_title">Dicas Rápidas</span>
                        </h2>
                        <div class="tip-card" data-i18n="tip1"><i class="bi bi-calendar3"></i> Usa os filtros de data
                            para comparar estatísticas entre períodos rapidamente.</div>
                        <div class="tip-card" data-i18n="tip2"><i class="bi bi-bell"></i> Activa notificações para novos
                            streams nas Configurações de notificações.</div>
                        <div class="tip-card" data-i18n="tip3"><i class="bi bi-file-earmark-spreadsheet"></i> Exporta os
                            dados em CSV na secção de estatísticas para análise detalhada.</div>
                        <div class="tip-card"><i class="bi bi-calendar-plus"></i> Submete lançamentos com pelo menos 2
                            semanas de antecedência para garantir disponibilidade na data desejada.</div>
                        <div class="tip-card"><i class="bi bi-shield-check"></i> Activa o 2FA em <a
                                href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/user/profile#seguranca">Segurança</a>
                            para proteger a tua conta.</div>
                    </div>

                    <!-- ════ Tutorial ════ -->
                    <div class="tutorial-section" id="tutorial">
                        <h2 data-i18n="tutorial_title"><i class="bi bi-play-circle me-2"></i>Assiste ao Nosso Tutorial
                        </h2>
                        <p class="text-muted small mb-3">Aprende a usar a plataforma passo a passo em vídeo</p>
                        <button class="tutorial-btn" data-bs-toggle="modal" data-bs-target="#tutorialModal"
                            data-i18n="watch_video">
                            <i class="bi bi-play-fill me-2"></i>Ver Vídeo
                        </button>
                    </div>

                </section>
            </div>

            <!-- SIDEBAR — Index + Ajuda -->
            <div class="col-lg-4 d-none d-lg-block">
                <div class="nav-index sticky-top" style="top:80px" id="navIndex">
                    <h3 data-i18n="index_title"><i class="bi bi-list-ol me-2"></i>Índice</h3>
                    <ul id="indexList">
                        <!-- preenchido por buildIndex() -->
                    </ul>
                </div>

                <div class="card mt-3"
                    style="border-radius:16px;border:1.5px solid var(--border-color,rgba(0,0,0,.08))">
                    <div class="card-body" style="padding:1.3rem">
                        <h6 class="fw-bold mb-3" style="color:#FF0089"><i class="bi bi-headset me-2"></i>Não encontraste
                            resposta?</h6>
                        <p class="text-muted small mb-3">A nossa equipa está disponível para te ajudar com qualquer
                            questão.</p>
                        <a href="support" class="btn btn-sm w-100 mb-2"
                            style="background:#FF0089;color:#fff;border-radius:8px;font-weight:700">
                            <i class="bi bi-send me-1"></i> Enviar pedido de suporte
                        </a>
                        <a href="help" class="btn btn-sm btn-outline-secondary w-100"
                            style="border-radius:8px;font-weight:600">
                            <i class="bi bi-question-circle me-1"></i> Central de Ajuda
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>


    <!-- Back to top -->
    <button id="backToTop" onclick="scrollToTop()" title="Voltar ao topo"><i class="bi bi-chevron-up"></i></button>

    <!-- Support float -->
    <a href="support" class="support-btn" title="Suporte"><i class="bi bi-headset"></i></a>

    <!-- Modal Tutorial -->
    <div class="modal fade" id="tutorialModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" data-i18n="tutorial_modal_title">Tutorial <?php echo APP_NAME ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.youtube.com/embed/your-video-id"
                            title="Tutorial <?php echo APP_NAME ?>" frameborder="0" allowfullscreen></iframe>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        data-i18n="close">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- theme.wp.js gere o tema — sem const themeToggle/themeIcon inline nesta página -->
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <!-- faq.js carregado DEPOIS do Bootstrap e dos outros scripts -->
    <script src="js/faq.js"></script>
</body>

</html>