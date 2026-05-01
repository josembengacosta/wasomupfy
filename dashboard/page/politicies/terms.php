<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Termos de Uso e Condições
// Arquivo: dashboard/page/terms.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../../authentic/include/functions.php';
require_once __DIR__ . '/../../include/platform.php';
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
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../../include/head.php'; ?>
    <title>Termos de Uso e Condições — <?php echo APP_NAME; ?></title>
    <style>
    /* ══ Progress bar de leitura ══ */
    .read-progress {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        z-index: 9999;
        background: rgba(0, 0, 0, .08);
    }

    .read-progress-fill {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #FF0089, #FF4D4D);
        transition: width .1s linear;
    }

    /* ══ Hero ══ */
    .terms-hero {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
        border-radius: 22px;
        padding: 3rem 2.4rem 2.4rem;
        margin-bottom: 2rem;
        color: #fff;
        position: relative;
        overflow: hidden;
    }

    .terms-hero::before {
        content: '\F4BC';
        font-family: 'bootstrap-icons';
        position: absolute;
        right: -20px;
        bottom: -30px;
        font-size: 11rem;
        opacity: .06;
    }

    .terms-hero .version-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 0, 137, .25);
        border: 1px solid rgba(255, 0, 137, .4);
        border-radius: 999px;
        padding: 4px 14px;
        font-size: .75rem;
        font-weight: 700;
        margin-bottom: .8rem;
    }

    .terms-hero h1 {
        font-size: 2.2rem;
        font-weight: 900;
        margin-bottom: .4rem;
    }

    .terms-hero p {
        opacity: .8;
        font-size: .92rem;
        max-width: 640px;
        margin-bottom: 0;
    }

    .terms-hero .hero-meta {
        display: flex;
        gap: 1.5rem;
        flex-wrap: wrap;
        margin-top: 1.2rem;
    }

    .terms-hero .hero-meta span {
        font-size: .78rem;
        opacity: .7;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* ══ Action buttons ══ */
    .action-btns {
        display: flex;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 2rem;
    }

    .action-btns a,
    .action-btns button {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: .42rem 1.2rem;
        border-radius: 999px;
        font-size: .8rem;
        font-weight: 700;
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

    /* ══ Layout ══ */
    .terms-layout {
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: 2rem;
        align-items: start;
    }

    @media(max-width:991px) {
        .terms-layout {
            grid-template-columns: 1fr;
        }
    }

    /* ══ Index sidebar ══ */
    .terms-index {
        background: var(--card-bg, #fff);
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        border-radius: 18px;
        padding: 1.5rem;
        position: sticky;
        top: 80px;
    }

    .terms-index h3 {
        font-size: .9rem;
        font-weight: 900;
        color: #FF0089;
        margin-bottom: 1rem;
    }

    .terms-index ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .terms-index li {
        margin-bottom: .35rem;
    }

    .terms-index a {
        font-size: .78rem;
        color: var(--text-muted, #6c757d);
        text-decoration: none;
        display: flex;
        align-items: flex-start;
        gap: 6px;
        line-height: 1.4;
        padding: .25rem .4rem;
        border-radius: 7px;
        transition: all .15s;
    }

    .terms-index a .num {
        color: #FF0089;
        font-weight: 800;
        flex-shrink: 0;
        min-width: 18px;
    }

    .terms-index a:hover,
    .terms-index a.active {
        color: #FF0089;
        background: rgba(255, 0, 137, .07);
    }

    /* ══════════════════════════════════════════════════════
   CARDS DE CONTEÚDO — Termos e Privacidade
   ══════════════════════════════════════════════════════ */
    .privacy-content,
    .terms-content {
        background: var(--card-bg, #fff);
        /* usa variável, sem fallback? melhor: define variável no :root */
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        border-radius: 18px;
        padding: 2.5rem;
    }

    /* Tema claro: garante as variáveis padrão */
    body:not(.dark-mode):not([data-theme="dark"]) .privacy-content,
    body:not(.dark-mode):not([data-theme="dark"]) .terms-content {
        --card-bg: #fff;
        --border-color: rgba(0, 0, 0, .08);
    }

    /* Tema escuro: redefine as variáveis para fundo escuro e borda visível */
    .dark-mode .privacy-content,
    [data-theme="dark"] .privacy-content,
    .dark-mode .terms-content,
    [data-theme="dark"] .terms-content {
        --card-bg: #1e1e1e;
        --border-color: rgba(255, 255, 255, 0.08);
        /* Opcional: podes também forçar diretamente, como backup */
        background: #1e1e1e;
        border-color: rgba(255, 255, 255, 0.08);
    }

    /* Índice lateral (sidebar) com o mesmo tratamento */
    .privacy-index,
    .terms-index {
        background: var(--card-bg, #fff);
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        border-radius: 18px;
        padding: 1.5rem;
    }

    .dark-mode .privacy-index,
    [data-theme="dark"] .privacy-index,
    .dark-mode .terms-index,
    [data-theme="dark"] .terms-index {
        --card-bg: #1e1e1e;
        --border-color: rgba(255, 255, 255, 0.08);
        background: #1e1e1e;
        border-color: rgba(255, 255, 255, 0.08);
    }

    /* ── Responsivo para mobile ── */

    @media (max-width: 576px) {
        .terms-hero {
            padding: 1.8rem 1rem 1.5rem;
            border-radius: 16px;
        }

        .terms-hero h1 {
            font-size: 1.6rem;
        }

        .terms-content {
            padding: 1.2rem;
        }

        .action-btns {
            flex-direction: column;
        }

        .action-btns a,
        .action-btns button {
            width: 100%;
            justify-content: center;
        }
    }


    .term-section {
        margin-bottom: 2.5rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .06));
    }

    .term-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .term-section h2 {
        font-size: 1.1rem;
        font-weight: 800;
        color: #FF0089;
        margin-bottom: 1rem;
        padding-bottom: .5rem;
        border-bottom: 2px solid rgba(255, 0, 137, .12);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .term-section h2 .sec-num {
        background: rgba(255, 0, 137, .1);
        color: #FF0089;
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .82rem;
        font-weight: 900;
        flex-shrink: 0;
    }

    .term-section h3 {
        font-size: .9rem;
        font-weight: 800;
        margin: 1.2rem 0 .6rem;
        color: var(--heading-color, #222);
    }

    .term-section p {
        font-size: .87rem;
        line-height: 1.8;
        margin-bottom: .8rem;
        color: var(--text-body, #444);
    }

    .term-section ul {
        padding-left: 0;
        list-style: none;
        margin-bottom: .8rem;
    }

    .term-section ul li {
        font-size: .87rem;
        line-height: 1.7;
        padding: .3rem 0 .3rem 1.3rem;
        position: relative;
        color: var(--text-body, #444);
    }

    .term-section ul li::before {
        content: '›';
        position: absolute;
        left: 0;
        color: #FF0089;
        font-weight: 900;
    }

    /* ══ Highlight boxes ══ */
    .term-box {
        border-radius: 12px;
        padding: 1rem 1.2rem;
        margin: 1rem 0;
        font-size: .84rem;
        line-height: 1.7;
    }

    .term-box.warning {
        background: rgba(255, 193, 7, .1);
        border-left: 4px solid #ffc107;
        color: var(--text-body, #444);
    }

    .term-box.danger {
        background: rgba(220, 53, 69, .08);
        border-left: 4px solid #dc3545;
        color: var(--text-body, #444);
    }

    .term-box.info {
        background: rgba(13, 110, 253, .08);
        border-left: 4px solid #0d6efd;
        color: var(--text-body, #444);
    }

    .term-box.success {
        background: rgba(25, 135, 84, .08);
        border-left: 4px solid #198754;
        color: var(--text-body, #444);
    }

    .term-box strong {
        display: block;
        margin-bottom: .3rem;
    }

    /* ══ Planos tabela ══ */
    .plan-table {
        width: 100%;
        border-collapse: collapse;
        margin: 1rem 0;
        font-size: .82rem;
    }

    .plan-table th {
        background: rgba(255, 0, 137, .08);
        color: #FF0089;
        padding: .65rem 1rem;
        text-align: left;
        font-weight: 800;
        border-bottom: 2px solid rgba(255, 0, 137, .2);
    }

    .plan-table td {
        padding: .6rem 1rem;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .07));
    }

    .plan-table tr:last-child td {
        border-bottom: none;
    }

    .plan-table tr:hover td {
        background: rgba(255, 0, 137, .03);
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

    /* Evitar que o conteúdo ultrapasse os limites */
    .privacy-content,
    .terms-content {
        overflow-x: auto;
        /* barra de scroll horizontal só se for mesmo preciso */
        word-wrap: break-word;
        /* quebra palavras longas */
        max-width: 100%;
        /* nunca ultrapassa o container pai */
    }

    @media (max-width: 576px) {

        /* Tabelas com scroll horizontal */
        .data-table,
        .plan-table {
            display: block;
            /* transforma a tabela em bloco */
            width: 100%;
            overflow-x: auto;
            /* scroll horizontal se necessário */
            -webkit-overflow-scrolling: touch;
        }

        /* Opcional: garantir que imagens e iframes também não escapam */

        iframe {
            max-width: 100%;
            height: auto;
        }
    }


    /* ══ Print ══ */
    @media print {

        .navbar,
        .offcanvas,
        .bottom-nav,
        .action-btns,
        .terms-index,
        #backToTop,
        .read-progress,
        nav {
            display: none !important;
        }

        .terms-layout {
            grid-template-columns: 1fr !important;
        }

        .terms-content {
            border: none !important;
            padding: 0 !important;
        }

        .term-section h2 {
            color: #000 !important;
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
    <?php require_once __DIR__ . '/../../include/sidebar.php'; ?>
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

        <!-- HERO -->
        <div class="terms-hero">
            <div class="version-badge">
                <i class="bi bi-file-earmark-check-fill"></i>
                Versão <?php echo TERMS_VERSION; ?> · Em vigor desde <?php echo TERMS_DATE; ?>
            </div>
            <h1><i class="bi bi-file-text-fill me-3" style="color:#FF0089"></i>Termos de Uso e Condições</h1>
            <p>
                Ao criar uma conta e aceder à plataforma <?php echo APP_NAME ?>, confirmaste que leste, compreendeste e
                concordaste na íntegra com os presentes Termos de Uso. A utilização contínua da plataforma implica
                a aceitação de todas as condições aqui estabelecidas.
            </p>
            <div class="hero-meta">
                <span><i class="bi bi-geo-alt-fill" style="color:#FF0089"></i> Luanda, Angola</span>
                <span><i class="bi bi-calendar3" style="color:#FF0089"></i> Última actualização:
                    <?php echo TERMS_DATE; ?></span>
                <span><i class="bi bi-translate" style="color:#FF0089"></i> Língua oficial: Português (Angola)</span>
                <span><i class="bi bi-shield-lock-fill" style="color:#FF0089"></i> Versão
                    <?php echo TERMS_VERSION; ?></span>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="action-btns">
            <a href="terms.pdf" download><i class="bi bi-file-earmark-pdf"></i> Descarregar em PDF</a>
            <button id="btnPrint"><i class="bi bi-printer"></i> Imprimir</button>
            <a href="privacy"><i class="bi bi-shield-check"></i> Política de Privacidade</a>
        </div>

        <!-- LAYOUT -->
        <div class="terms-layout">

            <!-- ÍNDICE (sidebar) -->
            <div class="terms-index d-none d-lg-block">
                <h3><i class="bi bi-list-ol me-2"></i>Índice</h3>
                <ul>
                    <li><a href="#s1"><span class="num">1.</span>Identificação e Serviços</a></li>
                    <li><a href="#s2"><span class="num">2.</span>Aceitação e Elegibilidade</a></li>
                    <li><a href="#s3"><span class="num">3.</span>Registo de Conta</a></li>
                    <li><a href="#s4"><span class="num">4.</span>Planos e Condições de Pagamento</a></li>
                    <li><a href="#s5"><span class="num">5.</span>Política de Não Reembolso</a></li>
                    <li><a href="#s6"><span class="num">6.</span>Distribuição Musical</a></li>
                    <li><a href="#s7"><span class="num">7.</span>Royalties e Pagamentos</a></li>
                    <li><a href="#s8"><span class="num">8.</span>Propriedade Intelectual</a></li>
                    <li><a href="#s9"><span class="num">9.</span>Conteúdo Proibido</a></li>
                    <li><a href="#s10"><span class="num">10.</span>Uso Aceitável da Plataforma</a></li>
                    <li><a href="#s11"><span class="num">11.</span>Suspensão e Encerramento</a></li>
                    <li><a href="#s12"><span class="num">12.</span>Limitação de Responsabilidade</a></li>
                    <li><a href="#s13"><span class="num">13.</span>Privacidade e Dados</a></li>
                    <li><a href="#s14"><span class="num">14.</span>Cookies</a></li>
                    <li><a href="#s15"><span class="num">15.</span>Serviços de Terceiros</a></li>
                    <li><a href="#s16"><span class="num">16.</span>Actualizações dos Termos</a></li>
                    <li><a href="#s17"><span class="num">17.</span>Lei Aplicável</a></li>
                    <li><a href="#s18"><span class="num">18.</span>Contacto</a></li>
                </ul>
            </div>

            <!-- CONTEÚDO DOS TERMOS -->
            <div class="terms-content">

                <!-- ════ 1. IDENTIFICAÇÃO E SERVIÇOS ════ -->
                <div class="term-section" id="s1">
                    <h2><span class="sec-num">1</span>Identificação e Descrição dos Serviços</h2>
                    <p>
                        A <strong><?php echo APP_NAME ?></strong> é uma plataforma digital de distribuição musical e
                        gestão de
                        direitos
                        autorais, desenvolvida e operada em Angola. A plataforma permite a artistas, produtores
                        musicais,
                        bandas e selos discográficos distribuir as suas obras para mais de <strong>150 plataformas
                            digitais</strong>
                        em todo o mundo, incluindo, entre outras: Spotify, Apple Music, YouTube Music, Deezer, Tidal,
                        Amazon Music, Boomplay, TikTok, iTunes e outras lojas de música.
                    </p>
                    <p>Os serviços disponibilizados pela plataforma incluem, mas não se limitam a:</p>
                    <ul>
                        <li>Distribuição de singles, EPs e álbuns para plataformas de streaming e lojas digitais;</li>
                        <li>Geração automática de códigos <strong>UPC</strong> (Universal Product Code) e
                            <strong>ISRC</strong> (International Standard Recording Code);
                        </li>
                        <li>Painel analítico de streams, receitas, países e playlists em tempo real;</li>
                        <li>Gestão financeira com carteira digital, histórico de transacções e levantamentos;</li>
                        <li>Divisão automática de royalties entre colaboradores e co-artistas;</li>
                        <li>Unificação e gestão de canal YouTube (Art Tracks e monetização);</li>
                        <li>Sistema de suporte por tickets com acompanhamento de estado;</li>
                        <li>Notificações em tempo real via plataforma, e-mail e push notifications;</li>
                        <li>Relatórios mensais de receitas e estatísticas de desempenho.</li>
                    </ul>
                </div>

                <!-- ════ 2. ACEITAÇÃO E ELEGIBILIDADE ════ -->
                <div class="term-section" id="s2">
                    <h2><span class="sec-num">2</span>Aceitação dos Termos e Elegibilidade</h2>
                    <p>
                        Ao criar uma conta na plataforma <?php echo APP_NAME ?>, o utilizador declara expressamente que:
                    </p>
                    <ul>
                        <li>Leu, compreendeu e aceita na íntegra os presentes Termos de Uso;</li>
                        <li>Tem idade igual ou superior a <strong>18 anos</strong>, ou age com o consentimento expresso
                            do seu representante legal;</li>
                        <li>As informações prestadas no registo são verdadeiras, completas e actualizadas;</li>
                        <li>Tem capacidade legal para celebrar contratos vinculativos ao abrigo da legislação angolana;
                        </li>
                        <li>Aceita a <a href="privacy">Política de Privacidade</a> da plataforma.</li>
                    </ul>
                    <div class="term-box warning">
                        <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Atenção</strong>
                        Se não concordar com qualquer parte destes Termos, deverá cessar imediatamente o uso da
                        plataforma e contactar o <a
                            href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/support">suporte</a> para encerrar a
                        sua
                        conta.
                    </div>
                </div>

                <!-- ════ 3. REGISTO DE CONTA ════ -->
                <div class="term-section" id="s3">
                    <h2><span class="sec-num">3</span>Registo de Conta e Segurança</h2>
                    <p>
                        Para utilizar os serviços da <?php echo APP_NAME ?>, é obrigatório criar uma conta pessoal. Cada
                        utilizador
                        pode manter <strong>apenas uma conta activa</strong> na plataforma.
                    </p>
                    <h3>3.1 Responsabilidade do Utilizador</h3>
                    <ul>
                        <li>O utilizador é o único responsável pela confidencialidade das suas credenciais de acesso
                            (e-mail e palavra-passe);</li>
                        <li>Qualquer actividade realizada na conta é da inteira responsabilidade do titular;</li>
                        <li>Em caso de acesso não autorizado, o utilizador deve notificar imediatamente a equipa via <a
                                href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/support">pedido de suporte</a>;
                        </li>
                        <li>A partilha de credenciais de acesso com terceiros é estritamente proibida.</li>
                    </ul>
                    <h3>3.2 Dados do Perfil</h3>
                    <ul>
                        <li>O utilizador compromete-se a manter os seus dados de perfil actualizados e verdadeiros;</li>
                        <li>A utilização de identidades falsas, nomes artísticos que violem direitos de terceiros ou
                            imagens de perfil inapropriadas é proibida e pode resultar na suspensão imediata da conta.
                        </li>
                    </ul>
                </div>

                <!-- ════ 4. PLANOS E PAGAMENTOS ════ -->
                <div class="term-section" id="s4">
                    <h2><span class="sec-num">4</span>Planos de Serviço e Condições de Pagamento</h2>
                    <p>
                        A <?php echo APP_NAME ?> oferece quatro planos de serviço, cada um com características e
                        condições
                        específicas.
                        O utilizador deve escolher o plano adequado às suas necessidades antes de efectuar qualquer
                        lançamento.
                    </p>

                    <table class="plan-table">
                        <thead>
                            <tr>
                                <th>Plano</th>
                                <th>Preço</th>
                                <th>Tipo</th>
                                <th>Cobertura</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Single</strong></td>
                                <td><strong>2.000 AOA</strong></td>
                                <td>Por lançamento</td>
                                <td>1 faixa por lançamento</td>
                            </tr>
                            <tr>
                                <td><strong>Album</strong></td>
                                <td><strong>5.000 AOA</strong></td>
                                <td>Por lançamento</td>
                                <td>Até 20 faixas por lançamento</td>
                            </tr>
                            <tr>
                                <td><strong>Artist</strong></td>
                                <td><strong>11.400 AOA/mês</strong></td>
                                <td>Subscrição mensal</td>
                                <td>Lançamentos ilimitados, 1 artista</td>
                            </tr>
                            <tr>
                                <td><strong>Label</strong></td>
                                <td><strong>70.000 AOA/mês</strong></td>
                                <td>Subscrição mensal</td>
                                <td>Lançamentos ilimitados, artistas ilimitados</td>
                            </tr>
                        </tbody>
                    </table>

                    <h3>4.1 Forma de Pagamento</h3>
                    <p>
                        Os pagamentos são efectuados por transferência bancária ou outro método disponibilizado pela
                        plataforma. Após o pagamento, o utilizador deve submeter o comprovante na secção
                        <strong>Conta → Activar Plano</strong>. A activação ocorre em até <strong>24 horas
                            úteis</strong>
                        após verificação pela equipa administrativa.
                    </p>

                    <h3>4.2 Renovação de Planos por Subscrição</h3>
                    <p>
                        Os planos <strong>Artist</strong> e <strong>Label</strong> são de subscrição mensal. A renovação
                        não é automática — o utilizador deve efectuar o pagamento e submeter o comprovante antes do
                        vencimento para garantir a continuidade do serviço sem interrupção. A <?php echo APP_NAME ?>
                        enviará uma
                        notificação com <strong>7 dias de antecedência</strong> do vencimento.
                    </p>

                    <h3>4.3 Plano Inactivo</h3>
                    <p>
                        Caso o plano expire sem renovação, os lançamentos existentes nas plataformas permanecerão
                        activos, mas o utilizador não poderá submeter novos lançamentos até renovar o plano.
                    </p>
                </div>

                <!-- ════ 5. POLÍTICA DE NÃO REEMBOLSO ════ -->
                <div class="term-section" id="s5">
                    <h2><span class="sec-num">5</span>Política de Não Reembolso</h2>

                    <div class="term-box danger">
                        <strong><i class="bi bi-x-circle-fill me-2"></i>Política de Não Reembolso — Leitura
                            Obrigatória</strong>
                        Todos os pagamentos efectuados à <?php echo APP_NAME ?> são <strong>definitivos e não
                            reembolsáveis</strong>,
                        independentemente da circunstância. Ao efectuar o pagamento, o utilizador declara ter
                        compreendido
                        e aceite esta condição de forma irrevogável.
                    </div>

                    <p>A política de não reembolso aplica-se em todos os casos, incluindo:</p>
                    <ul>
                        <li>Pagamentos de activação de plano (Single, Album, Artist, Label);</li>
                        <li>Pagamentos de renovação de planos de subscrição;</li>
                        <li>Pagamentos efectuados por erro do utilizador (valor errado, plano errado);</li>
                        <li>Situações em que o utilizador decida não utilizar o serviço após pagamento;</li>
                        <li>Casos em que a conta seja suspensa ou encerrada por violação dos presentes Termos;</li>
                        <li>Pagamentos relativos a lançamentos que sejam posteriormente rejeitados por incumprimento dos
                            requisitos técnicos ou de conteúdo;</li>
                        <li>Indisponibilidade temporária da plataforma por manutenção ou causas de força maior.</li>
                    </ul>

                    <h3>5.1 Excepção Única</h3>
                    <p>
                        A única situação em que poderá ser analisado um pedido de crédito de conta (e não reembolso
                        monetário) é quando a <?php echo APP_NAME ?> cometa um erro técnico comprovável que resulte na
                        cobrança
                        duplicada pelo mesmo serviço. Nesse caso, o utilizador deve abrir um
                        <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/support">pedido de suporte</a> com o
                        comprovante das duas cobranças no prazo de
                        <strong>72 horas</strong> após a ocorrência. A análise não garante resultado favorável.
                    </p>

                    <div class="term-box info">
                        <strong><i class="bi bi-info-circle-fill me-2"></i>Recomendação</strong>
                        Antes de efectuar qualquer pagamento, certifica-te de que escolheste o plano correcto e que
                        compreendeste as condições de cada plano na secção
                        <a href="../services/available-services">Conta e serviços disponíveis</a>.
                    </div>
                </div>

                <!-- ════ 6. DISTRIBUIÇÃO MUSICAL ════ -->
                <div class="term-section" id="s6">
                    <h2><span class="sec-num">6</span>Distribuição Musical — Requisitos e Prazos</h2>
                    <h3>6.1 Requisitos Técnicos de Áudio</h3>
                    <ul>
                        <li>Formatos aceites: <strong>WAV</strong> (recomendado, 16 ou 24 bits, 44,1 kHz),
                            <strong>FLAC</strong> (sem perdas), <strong>AIFF</strong> e <strong>MP3</strong> a 320 kbps;
                        </li>
                        <li>Tamanho máximo por ficheiro de áudio: <strong>1 GB</strong>;</li>
                        <li>A qualidade de áudio deve ser adequada para distribuição profissional — ficheiros com ruído
                            excessivo, cortes abruptos ou qualidade inferior a 128 kbps podem ser rejeitados.</li>
                    </ul>
                    <h3>6.2 Requisitos da Capa</h3>
                    <ul>
                        <li>Dimensão mínima: <strong>3.000 × 3.000 pixels</strong>, formato quadrado (proporção 1:1);
                        </li>
                        <li>Formato: JPG ou PNG com qualidade máxima;</li>
                        <li>A capa <strong>não pode conter</strong>: logótipos ou marcas de lojas digitais (Spotify,
                            Apple, etc.), URLs, endereços de e-mail, informações de contacto, conteúdo explícito sem
                            marcação adequada, ou materiais que violem direitos de autor de terceiros.</li>
                    </ul>
                    <h3>6.3 Prazos de Distribuição</h3>
                    <ul>
                        <li>Revisão interna pela equipa <?php echo APP_NAME ?>: até <strong>72 horas úteis</strong> após
                            a
                            submissão;</li>
                        <li>Spotify e Apple Music: disponibilização em <strong>3 a 7 dias</strong> após aprovação;</li>
                        <li>Outras plataformas: até <strong>14 dias</strong> após aprovação;</li>
                        <li>Recomendamos submeter lançamentos com pelo menos <strong>2 semanas de antecedência</strong>
                            em relação à data desejada de disponibilização.</li>
                    </ul>
                    <h3>6.4 Rejeição de Lançamentos</h3>
                    <p>
                        A <?php echo APP_NAME ?> reserva-se o direito de rejeitar qualquer lançamento que não cumpra os
                        requisitos
                        técnicos, de conteúdo ou legais. Em caso de rejeição, o utilizador será notificado com o motivo.
                        O pagamento do plano não é reembolsável em caso de rejeição por não conformidade.
                    </p>
                    <h3>6.5 Remoção de Lançamentos</h3>
                    <p>
                        O utilizador pode solicitar a remoção de um lançamento das plataformas a qualquer momento.
                        A remoção efectiva pode demorar até <strong>30 dias</strong> dependendo de cada plataforma. A
                        <?php echo APP_NAME ?>
                        não se responsabiliza por streams ou receitas geradas durante o período de processamento da
                        remoção.
                    </p>
                </div>

                <!-- ════ 7. ROYALTIES E PAGAMENTOS ════ -->
                <div class="term-section" id="s7">
                    <h2><span class="sec-num">7</span>Royalties, Receitas e Levantamentos</h2>
                    <h3>7.1 Distribuição de Royalties</h3>
                    <p>
                        A <?php echo APP_NAME ?> distribui <strong>90% dos royalties</strong> gerados pelos lançamentos
                        directamente
                        ao artista. Os restantes <strong>10%</strong> destinam-se à cobertura dos custos operacionais da
                        plataforma, incluindo licenças de distribuição, infraestrutura técnica e suporte administrativo.
                    </p>
                    <div class="term-box success">
                        <strong><i class="bi bi-check-circle-fill me-2"></i>Taxa de royalties</strong>
                        90% para o artista · 10% para operação da plataforma — sem custos ocultos ou taxas adicionais.
                    </div>

                    <h3>7.2 Ciclo de Pagamento</h3>
                    <p>
                        Os royalties são processados e creditados na carteira digital do utilizador até ao
                        <strong>dia 15 de cada mês</strong>, referentes ao mês anterior. Algumas plataformas de
                        distribuição como o Spotify e o Apple Music têm um atraso natural de <strong>2 a 3
                            meses</strong>
                        nos seus relatórios de receitas, o que pode influenciar os valores visíveis na plataforma.
                    </p>

                    <h3>7.3 Levantamentos</h3>
                    <ul>
                        <li>Valor mínimo para levantamento: <strong>1.000 AOA</strong>;</li>
                        <li>Métodos disponíveis: transferência bancária (IBAN), Express e outros métodos listados na
                            plataforma;</li>
                        <li>Prazo de processamento: <strong>3 a 5 dias úteis</strong> após confirmação;</li>
                        <li>O utilizador é responsável pela veracidade dos dados bancários fornecidos. Pagamentos
                            efectuados para contas erradas por dados fornecidos incorrectamente pelo utilizador não são
                            da responsabilidade da <?php echo APP_NAME ?>.</li>
                    </ul>

                    <h3>7.4 Divisão de Royalties entre Colaboradores</h3>
                    <p>
                        O utilizador pode configurar a divisão de royalties entre colaboradores directamente na
                        plataforma, em <strong>Finanças → Divisão de Royalties</strong>. A soma das percentagens deve
                        ser sempre 100%. Cada colaborador convidado terá acesso à sua parte conforme as permissões
                        definidas pelo titular da conta.
                    </p>

                    <h3>7.5 Retenção por Suspeita de Fraude</h3>
                    <p>
                        A <?php echo APP_NAME ?> reserva-se o direito de reter temporariamente pagamentos de royalties
                        quando
                        existir suspeita fundada de manipulação de streams, fraude ou actividade irregular detectada
                        pelas plataformas de distribuição. O utilizador será notificado e terá direito a apresentar
                        esclarecimentos no prazo de <strong>15 dias úteis</strong>.
                    </p>
                </div>

                <!-- ════ 8. PROPRIEDADE INTELECTUAL ════ -->
                <div class="term-section" id="s8">
                    <h2><span class="sec-num">8</span>Propriedade Intelectual</h2>
                    <h3>8.1 Propriedade do Conteúdo</h3>
                    <p>
                        O utilizador mantém a totalidade dos direitos de propriedade intelectual sobre as suas obras
                        musicais. A <?php echo APP_NAME ?> não reivindica qualquer direito de propriedade sobre as
                        músicas,
                        letras, capas ou qualquer outro conteúdo submetido pelo utilizador.
                    </p>
                    <h3>8.2 Licença de Distribuição</h3>
                    <p>
                        Ao submeter um lançamento, o utilizador concede à <?php echo APP_NAME ?> uma
                        <strong>licença não exclusiva, mundial e revogável</strong> para distribuir, reproduzir,
                        disponibilizar e promover as obras nas plataformas parceiras, em seu nome, pelo período em
                        que o lançamento estiver activo na plataforma.
                    </p>
                    <h3>8.3 Garantia de Titularidade</h3>
                    <p>
                        O utilizador declara e garante que:
                    </p>
                    <ul>
                        <li>É o titular legítimo ou detentor de licença válida para todos os conteúdos submetidos;</li>
                        <li>Os conteúdos submetidos não violam direitos de autor, marcas registadas ou quaisquer outros
                            direitos de terceiros;</li>
                        <li>Não submete conteúdo que seja objecto de litígio, embargo ou decisão judicial que impeça a
                            sua distribuição.</li>
                    </ul>
                    <p>
                        O utilizador será o único responsável por qualquer reclamação, litígio ou indemnização
                        resultante
                        da violação desta garantia. A <?php echo APP_NAME ?> reserva-se o direito de remover
                        imediatamente qualquer
                        conteúdo que seja objecto de reclamação fundamentada de violação de direitos de terceiros.
                    </p>
                    <h3>8.4 Propriedade da Plataforma</h3>
                    <p>
                        Todos os elementos da plataforma <?php echo APP_NAME ?> — incluindo o design, código-fonte,
                        logótipos,
                        marcas, textos, relatórios e funcionalidades — são propriedade exclusiva da
                        <?php echo APP_NAME ?> e estão
                        protegidos pelas leis de propriedade intelectual aplicáveis. É expressamente proibida a
                        reprodução, cópia, modificação ou distribuição de qualquer elemento da plataforma sem
                        autorização
                        escrita prévia.
                    </p>
                </div>

                <!-- ════ 9. CONTEÚDO PROIBIDO ════ -->
                <div class="term-section" id="s9">
                    <h2><span class="sec-num">9</span>Conteúdo Proibido</h2>
                    <p>
                        É estritamente proibido submeter à plataforma qualquer conteúdo que:
                    </p>
                    <ul>
                        <li>Viole direitos de autor, direitos conexos ou direitos de imagem de terceiros;</li>
                        <li>Contenha ou promova discurso de ódio, racismo, xenofobia, discriminação ou incitamento à
                            violência;</li>
                        <li>Seja de natureza pornográfica, obscena ou sexualmente explícita sem as devidas marcações de
                            conteúdo adulto;</li>
                        <li>Envolva ou promova actividades ilegais, incluindo o consumo ou tráfico de substâncias
                            ilícitas;</li>
                        <li>Contenha ameaças, difamação ou calúnia dirigidas a indivíduos ou grupos;</li>
                        <li>Reproduza sem autorização gravações de terceiros, samples não licenciados ou remisturas não
                            autorizadas;</li>
                        <li>Seja gerado por inteligência artificial sem a devida declaração nos metadados do lançamento,
                            nos casos em que as plataformas de destino o exijam.</li>
                    </ul>
                    <div class="term-box danger">
                        <strong><i class="bi bi-x-octagon-fill me-2"></i>Consequências</strong>
                        A submissão de conteúdo proibido resultará na remoção imediata do lançamento, possível
                        suspensão ou encerramento permanente da conta, sem direito a reembolso, e eventual
                        responsabilização legal pelo utilizador.
                    </div>
                </div>

                <!-- ════ 10. USO ACEITÁVEL ════ -->
                <div class="term-section" id="s10">
                    <h2><span class="sec-num">10</span>Uso Aceitável da Plataforma</h2>
                    <p>O utilizador compromete-se a não:</p>
                    <ul>
                        <li>Tentar aceder a áreas restritas da plataforma sem autorização;</li>
                        <li>Utilizar ferramentas automatizadas (bots, scrapers, crawlers) para extrair dados da
                            plataforma;</li>
                        <li>Realizar ataques de negação de serviço (DoS/DDoS) ou qualquer tentativa de comprometer a
                            segurança da plataforma;</li>
                        <li>Criar ou utilizar mais de uma conta por utilizador — contas duplicadas serão encerradas sem
                            aviso prévio;</li>
                        <li>Manipular artificialmente o número de streams ou reproduções de qualquer lançamento,
                            incluindo através de serviços de "stream farming" ou bots;</li>
                        <li>Partilhar, vender ou transferir a sua conta a terceiros;</li>
                        <li>Utilizar a plataforma para fins comerciais não autorizados, incluindo a revenda de serviços
                            sem acordo escrito prévio com a <?php echo APP_NAME ?>.</li>
                    </ul>
                </div>

                <!-- ════ 11. SUSPENSÃO E ENCERRAMENTO ════ -->
                <div class="term-section" id="s11">
                    <h2><span class="sec-num">11</span>Suspensão e Encerramento de Contas</h2>
                    <h3>11.1 Suspensão Temporária</h3>
                    <p>A conta pode ser suspensa temporariamente nos seguintes casos:</p>
                    <ul>
                        <li>Detecção de actividade suspeita ou acesso de localização não reconhecida;</li>
                        <li>Submissão de comprovantes de pagamento falsos ou adulterados;</li>
                        <li>Múltiplos pedidos de levantamento em períodos anormalmente curtos;</li>
                        <li>Reclamações de violação de direitos de autor recebidas pelas plataformas de distribuição;
                        </li>
                        <li>Violação de qualquer disposição dos presentes Termos, passível de correcção;</li>
                        <li>Não pagamento de quantias devidas à plataforma.</li>
                    </ul>
                    <h3>11.2 Encerramento Permanente</h3>
                    <p>A conta pode ser encerrada definitivamente nos seguintes casos:</p>
                    <ul>
                        <li>Criação de contas duplicadas ou clonadas;</li>
                        <li>Fraude comprovada, incluindo manipulação de streams;</li>
                        <li>Violações graves e repetidas dos Termos de Uso;</li>
                        <li>Determinação judicial ou ordem de autoridade competente;</li>
                        <li>Actividade que cause dano reputacional ou financeiro à plataforma ou a terceiros.</li>
                    </ul>
                    <div class="term-box warning">
                        <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Consequências do
                            Encerramento</strong>
                        Em caso de encerramento por violação dos Termos, o utilizador perde o acesso a todos os dados,
                        lançamentos e saldo da carteira, sem direito a reembolso. Os lançamentos distribuídos poderão
                        ser removidos das plataformas.
                    </div>
                    <h3>11.3 Encerramento Voluntário</h3>
                    <p>
                        O utilizador pode solicitar o encerramento voluntário da sua conta através de um
                        <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/support">pedido de suporte</a>. Antes
                        do
                        encerramento, todos os levantamentos
                        pendentes devem ser processados e todos os lançamentos activos serão mantidos ou removidos
                        conforme instrução do utilizador. O saldo remanescente na carteira após o encerramento
                        voluntário pode ser levantado no prazo de <strong>30 dias</strong> após o pedido.
                    </p>
                </div>

                <!-- ════ 12. LIMITAÇÃO DE RESPONSABILIDADE ════ -->
                <div class="term-section" id="s12">
                    <h2><span class="sec-num">12</span>Limitação de Responsabilidade</h2>
                    <p>
                        A <?php echo APP_NAME ?> não se responsabiliza por:
                    </p>
                    <ul>
                        <li>Falhas técnicas, indisponibilidade ou atrasos causados por plataformas de distribuição
                            terceiras (Spotify, Apple Music, etc.);</li>
                        <li>Perdas de receitas resultantes de decisões unilaterais das plataformas de distribuição
                            parceiras;</li>
                        <li>Alterações nas políticas de remuneração das plataformas de streaming que afectem os valores
                            de royalties;</li>
                        <li>Danos indirectos, incidentais ou consequenciais resultantes do uso ou incapacidade de uso da
                            plataforma;</li>
                        <li>Interrupções de serviço causadas por casos de força maior, incluindo falhas de energia,
                            desastres naturais, guerras, pandemias ou ordens governamentais;</li>
                        <li>Perdas ou danos resultantes de acesso não autorizado à conta por falha do utilizador em
                            proteger as suas credenciais.</li>
                    </ul>
                    <p>
                        A responsabilidade total da <?php echo APP_NAME ?> perante o utilizador, em qualquer
                        circunstância,
                        está limitada ao valor pago pelo utilizador pelo plano activo no momento do evento gerador
                        do dano, referente ao último ciclo de facturação.
                    </p>
                </div>

                <!-- ════ 13. PRIVACIDADE E DADOS ════ -->
                <div class="term-section" id="s13">
                    <h2><span class="sec-num">13</span>Privacidade e Tratamento de Dados</h2>
                    <p>
                        O tratamento dos dados pessoais dos utilizadores é regido pela
                        <a href="privacy"><strong>Política de Privacidade</strong></a> da <?php echo APP_NAME ?>, que
                        constitui
                        parte integrante dos presentes Termos de Uso. Ao aceitar estes Termos, o utilizador aceita
                        igualmente a Política de Privacidade.
                    </p>
                    <p>
                        A <?php echo APP_NAME ?> não partilha dados pessoais dos utilizadores com terceiros para fins
                        comerciais
                        ou publicitários. Os dados são utilizados exclusivamente para a prestação dos serviços
                        contratados e para o cumprimento de obrigações legais.
                    </p>
                    <p>
                        Cada utilizador pode visualizar apenas os seus próprios dados, lançamentos e informações
                        financeiras. O acesso a dados de outros utilizadores é estritamente proibido e tecnicamente
                        impedido pela plataforma.
                    </p>
                </div>

                <!-- ════ 14. COOKIES ════ -->
                <div class="term-section" id="s14">
                    <h2><span class="sec-num">14</span>Cookies e Tecnologias de Rastreamento</h2>
                    <p>
                        A <?php echo APP_NAME ?> utiliza cookies e tecnologias similares para:
                    </p>
                    <ul>
                        <li>Manter a sessão de utilizador activa e segura;</li>
                        <li>Guardar preferências de tema, idioma e interface;</li>
                        <li>Analisar o comportamento de utilização para melhoria contínua da plataforma;</li>
                        <li>Detectar e prevenir actividades fraudulentas ou não autorizadas.</li>
                    </ul>
                    <p>
                        O utilizador pode gerir as preferências de cookies nas
                        <a href="settings">Configurações</a> da conta. A desactivação de cookies essenciais pode
                        afectar o funcionamento correcto da plataforma, incluindo a manutenção da sessão de login.
                    </p>
                </div>

                <!-- ════ 15. SERVIÇOS DE TERCEIROS ════ -->
                <div class="term-section" id="s15">
                    <h2><span class="sec-num">15</span>Serviços e Plataformas de Terceiros</h2>
                    <p>
                        A <?php echo APP_NAME ?> distribui conteúdo para plataformas de terceiros (Spotify, Apple Music,
                        YouTube
                        Music, Deezer, etc.) que possuem os seus próprios Termos de Uso e Políticas de Privacidade
                        independentes. A <?php echo APP_NAME ?> não controla nem se responsabiliza pelas políticas,
                        decisões
                        ou alterações efectuadas por essas plataformas.
                    </p>
                    <p>
                        A integração com o <strong>YouTube</strong> para unificação de canal e gestão de Art Tracks
                        está sujeita aos Termos de Serviço do YouTube e às políticas do YouTube Partner Program.
                        A <?php echo APP_NAME ?> não garante a aprovação pelo YouTube da monetização de qualquer canal.
                    </p>
                </div>

                <!-- ════ 16. ACTUALIZAÇÕES DOS TERMOS ════ -->
                <div class="term-section" id="s16">
                    <h2><span class="sec-num">16</span>Actualizações dos Termos de Uso</h2>
                    <p>
                        A <?php echo APP_NAME ?> reserva-se o direito de actualizar os presentes Termos de Uso a
                        qualquer momento,
                        mediante notificação prévia ao utilizador com pelo menos <strong>15 dias de
                            antecedência</strong>
                        através de:
                    </p>
                    <ul>
                        <li>Notificação na plataforma (painel de notificações);</li>
                        <li>Notificação por e-mail para o endereço registado na conta;</li>
                        <li>Aviso em destaque na página de login.</li>
                    </ul>
                    <p>
                        O uso contínuo da plataforma após a entrada em vigor da nova versão dos Termos constitui
                        aceitação tácita das alterações. Se o utilizador não concordar com as alterações, deve
                        cessar o uso da plataforma e solicitar o encerramento da conta antes da data de entrada
                        em vigor da nova versão.
                    </p>
                </div>

                <!-- ════ 17. LEI APLICÁVEL ════ -->
                <div class="term-section" id="s17">
                    <h2><span class="sec-num">17</span>Lei Aplicável e Resolução de Litígios</h2>
                    <p>
                        Os presentes Termos de Uso são regidos e interpretados de acordo com a legislação da
                        <strong>República de Angola</strong>, em especial a Lei n.º 22/11 de 17 de Junho (Lei das
                        Comunicações Electrónicas e dos Serviços da Sociedade da Informação) e demais legislação
                        aplicável em matéria de propriedade intelectual, protecção de dados e direito do
                        consumidor.
                    </p>
                    <p>
                        Qualquer litígio decorrente da interpretação ou execução dos presentes Termos será
                        submetido à <strong>jurisdição exclusiva dos tribunais competentes de Luanda, Angola</strong>,
                        com renúncia expressa a qualquer outro foro que possa ser competente.
                    </p>
                    <p>
                        Antes de recorrer a qualquer instância judicial, as partes comprometem-se a tentar
                        resolver o litígio de forma amigável, através do <a
                            href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/support">sistema de suporte</a>
                        da plataforma, num prazo de <strong>30 dias</strong> a contar da notificação da reclamação.
                    </p>
                </div>

                <!-- ════ 18. CONTACTO ════ -->
                <div class="term-section" id="s18">
                    <h2><span class="sec-num">18</span>Contacto</h2>
                    <p>
                        Para questões, dúvidas ou reclamações relativas aos presentes Termos de Uso, o utilizador
                        pode contactar a equipa <?php echo APP_NAME ?> através dos seguintes meios:
                    </p>
                    <ul>
                        <li><strong>Suporte na plataforma:</strong> <a
                                href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/support">Enviar pedido de
                                suporte</a>
                            —
                            resposta em até 48 horas úteis;</li>
                        <li><strong>E-mail:</strong> suporte@wasomupfy.com;</li>
                        <li><strong>FAQ:</strong> <a
                                href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/faq">Perguntas Frequentes</a> —
                            para questões comuns sobre a
                            plataforma;</li>
                        <li><strong>Horário de atendimento:</strong> Segunda a Sexta, das 09h00 às 18h00 (WAT), Sábado
                            das 09h00 às 13h00.</li>
                    </ul>

                    <div class="term-box info" style="margin-top:1.5rem">
                        <strong><i class="bi bi-check-circle-fill me-2" style="color:#198754"></i>Aceitação Confirmada
                            no Registo</strong>
                        Ao criares a tua conta na <?php echo APP_NAME ?>, confirmaste a leitura e aceitação integral
                        destes
                        Termos de Uso e da <a href="privacy">Política de Privacidade</a>. A data e IP do teu
                        registo foram registados como prova de aceitação.
                    </div>
                </div>

            </div><!-- /terms-content -->
        </div><!-- /terms-layout -->

        <!-- Footer dos termos -->
        <div class="text-center mt-4 mb-5" style="font-size:.78rem;color:var(--text-muted,#6c757d)">
            <p>
                <strong><?php echo APP_NAME ?></strong> · Termos de Uso versão <?php echo TERMS_VERSION; ?> ·
                Em vigor desde <?php echo TERMS_DATE; ?> ·
                <a href="privacy" class="text-secondary">Política de Privacidade</a>
            </p>
            <p>© <?php echo date('Y'); ?> <?php echo APP_NAME ?>. Todos os direitos reservados.</p>
        </div>

    </div><!-- /container -->


    <!-- Back to top -->
    <button id="backToTop" title="Voltar ao topo"><i class="bi bi-chevron-up"></i></button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- theme.wp.js é o dono de themeToggle/themeIcon — sem redeclaração inline -->
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {

        // ── Barra de progresso de leitura + back to top ──
        var fill = document.getElementById('progressBar');
        var backToTop = document.getElementById('backToTop');

        function updateProgress() {
            var scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
            var scrollH = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            var pct = scrollH > 0 ? (scrollTop / scrollH) * 100 : 0;
            if (fill) fill.style.width = pct + '%';
            if (backToTop) backToTop.classList.toggle('visible', scrollTop > 300);
        }

        window.addEventListener('scroll', updateProgress);
        updateProgress();

        // ── Back to top ───────────────────────────────────
        if (backToTop) {
            backToTop.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }

        // ── Imprimir ──────────────────────────────────────
        var btnPrint = document.getElementById('btnPrint');
        if (btnPrint) {
            btnPrint.addEventListener('click', function() {
                window.print();
            });
        }

        // ── Highlight activo do índice ao scroll ─────────
        var sections = document.querySelectorAll('.term-section');
        var indexLinks = document.querySelectorAll('.terms-index a');

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var id = entry.target.getAttribute('id');
                    indexLinks.forEach(function(link) {
                        link.classList.toggle('active', link.getAttribute('href') ===
                            '#' + id);
                    });
                }
            });
        }, {
            rootMargin: '-20% 0px -70% 0px'
        });

        sections.forEach(function(sec) {
            observer.observe(sec);
        });

    });
    </script>
</body>

</html>