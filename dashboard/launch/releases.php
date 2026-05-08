<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Lançamentos
// Arquivo: dashboard/launch/releases.php
// ══════════════════════════════════════════════
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

// ── Verificar limite de lançamentos do plano ─────────────────
$can_create_release = true;
$limit_message = '';

// Buscar plano ativo do usuário
$user_plan_stmt = $db->prepare("
    SELECT up.releases_used, up.releases_limit, up.status_plan
    FROM _user_plan up
    WHERE up.id_users = ? AND up.status_plan = 'active'
    ORDER BY up.id_user_plan DESC
    LIMIT 1
");
$user_plan_stmt->execute([$id_users]);
$user_plan = $user_plan_stmt->fetch();

if ($user_plan) {
    // Verificar limite numérico (pacotes)
    if ($user_plan['releases_limit'] !== null) {
        if ((int)$user_plan['releases_used'] >= (int)$user_plan['releases_limit']) {
            $can_create_release = false;
            $limit_message = 'Atingiste o limite de lançamentos do teu plano. Faz upgrade para continuar.';
        }
    }
}

// Verificação adicional para Single (apenas 1 lançamento ativo)
$plan_slug = $plan['slug_plan'] ?? '';
if ($plan_slug === 'single' && $can_create_release) {
    $active_count_stmt = $db->prepare("
        SELECT COUNT(*) FROM _album
        WHERE id_users = ? 
          AND status_album IN ('pending', 'under_review', 'approved')
    ");
    $active_count_stmt->execute([$id_users]);
    if ((int)$active_count_stmt->fetchColumn() >= 1) {
        $can_create_release = false;
        $limit_message = 'O plano Single permite apenas 1 lançamento ativo. Aguarda a conclusão ou faz upgrade.';
    }
}
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

// ══════════════════════════════════════════════
// LANÇAMENTOS DA BASE DE DADOS
// ══════════════════════════════════════════════
$albums_stmt = $db->prepare("
    SELECT
        a.id_album,
        a.title_album,
        a.type_album,
        a.upc,
        a.img_cover,
        a.genre_main,
        a.genre_secondary, 
        a.language,        
        a.release_date,
        a.status_album,
        a.rejection_reason,
        a.label_name,
        a.copyright_c,     
        a.copyright_p,
        a.creat_album,
        a.modif_album,
        a.delete_requested_at,
        a.delete_expires_at,
        ar.stage_name,
        ar.real_name,
        (SELECT COUNT(*) FROM _track t WHERE t.id_album = a.id_album) AS track_count
    FROM _album a
    LEFT JOIN _artist ar ON ar.id_artist = a.id_artist
    WHERE a.id_users = ?
    ORDER BY a.creat_album DESC
");
$albums_stmt->execute([$id_users]);
$albums_raw = $albums_stmt->fetchAll(PDO::FETCH_ASSOC);

$albums_data = [];
foreach ($albums_raw as $alb) {

    // ── Faixas ──────────────────────────────────
    $trk_stmt = $db->prepare("
        SELECT 
            title_track, 
            isrc, 
            audio_file,
            name_author,                     
            name_composer,                    
            name_producer,                     
            explicit,                           
            language,                            
            name_author_feat AS featuring_track,
            track_number AS position_track,
            CASE WHEN duration_seconds IS NOT NULL
                THEN CONCAT(FLOOR(duration_seconds/60), ':', LPAD(duration_seconds%60,2,'0'))
                ELSE NULL END AS duration_track
        FROM _track 
        WHERE id_album = ?
        ORDER BY track_number ASC, id_track ASC
    ");
    $trk_stmt->execute([$alb['id_album']]);
    $alb['tracks'] = $trk_stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Lojas (_album_store JOIN _store) ─────────
    $st_stmt = $db->prepare("
        SELECT
            s.id_store,
            s.name_store,
            s.slug_store,
            als.status           AS store_status,
            als.store_release_url,
            als.distributed_at
        FROM _album_store als
        INNER JOIN _store s ON s.id_store = als.id_store
        WHERE als.id_album = ?
        ORDER BY s.display_order ASC
    ");
    $st_stmt->execute([$alb['id_album']]);
    $alb['stores'] = $st_stmt->fetchAll(PDO::FETCH_ASSOC);

    $albums_data[] = $alb;
}

$albums_json = json_encode($albums_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

// CSRF token para os processos Ajax
$csrf = htmlspecialchars($_SESSION['csrf_token']);
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title>Lançamentos — <?php echo APP_NAME; ?></title>
    <!-- Select2 Bootstrap 5 Theme (opcional, para melhor integração) -->
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/11.0.2/css/bootstrap-slider.min.css"
        rel="stylesheet" />
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/release-style.css" />
    <style>
    /* ── Cards de lançamento ─── */
    .release-card {
        border-radius: 12px;
        overflow: hidden;
        position: relative;
        border: 1px solid var(--border-color, rgba(0, 0, 0, .08));
        box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
        transition: transform .2s, box-shadow .2s;
        height: 100%;
    }

    .release-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(255, 0, 137, .15);
        border-color: rgba(255, 0, 137, .3);
    }

    .release-cover {
        width: 100%;
        aspect-ratio: 1/1;
        object-fit: cover;
        cursor: pointer;
        display: block;
    }

    .release-cover-placeholder {
        width: 100%;
        aspect-ratio: 1/1;
        background: linear-gradient(135deg, #2d2d2d, #1a1a1a);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #555;
    }

    .release-body {
        padding: 12px 14px;
    }

    .release-title {
        font-weight: 700;
        font-size: .95rem;
        margin: 0 0 2px;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .release-artist {
        font-size: .8rem;
        color: #888;
        margin-bottom: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .release-meta {
        font-size: .75rem;
        color: #999;
        margin-bottom: 8px;
    }

    .release-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        padding: 0 14px 14px;
    }

    /* ── Status ribbon ─── */
    .status-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        font-size: .7rem;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: .4px;
        backdrop-filter: blur(6px);
    }

    .status-approved {
        background: rgba(25, 135, 84, .85);
        color: #fff;
    }

    .status-pending {
        background: rgba(255, 193, 7, .9);
        color: #000;
    }

    .status-rejected {
        background: rgba(220, 53, 69, .85);
        color: #fff;
    }

    .status-draft {
        background: rgba(108, 117, 125, .8);
        color: #fff;
    }

    .status-warning {
        background: rgba(255, 193, 7, .9);
        color: #000;
    }

    /* Estilos para scroll suave nos tabs */
    #status-tabs {
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
    }

    #status-tabs::-webkit-scrollbar {
        height: 4px;
    }

    #status-tabs::-webkit-scrollbar-track {
        background: transparent;
    }

    #status-tabs::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.25);
        border-radius: 4px;
    }

    /* ── Modal do álbum ─── */
    .album-modal-cover {
        width: 100%;
        max-width: 200px;
        border-radius: 10px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, .3);
    }

    .track-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        border-bottom: 1px solid rgba(255, 255, 255, .08);
        font-size: .85rem;
    }

    .track-row:last-child {
        border-bottom: none;
    }

    .track-num {
        color: #ff0089;
        font-weight: 700;
        min-width: 24px;
    }

    .track-isrc {
        font-size: .7rem;
        color: #888;
    }

    /* ── Estado vazio ─── */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-state i {
        font-size: 4rem;
        color: #dee2e6;
        margin-bottom: 16px;
    }

    /* ── Streaming links no card ─── */
    .streaming-links a {
        font-size: 1.1rem;
        color: #888;
        margin-right: 6px;
        transition: color .2s;
        text-decoration: none;
    }

    .streaming-links a:hover {
        color: #ff0089;
    }

    /* ── Modal de revisão ─── */
    #reviewModal .form-label {
        font-size: .85rem;
    }

    /* ── Acordeão de faixas no modal escuro ── */
    .accordion-button {
        background-color: #2c2c2c !important;
        color: #fff !important;
        border: 1px solid rgba(255, 255, 255, .1) !important;
    }

    .accordion-button:not(.collapsed) {
        background-color: #3a3a3a !important;
        color: #FF0089 !important;
    }

    .accordion-button::after {
        filter: brightness(0) invert(1);
    }

    .accordion-item {
        background-color: transparent;
        border-color: rgba(255, 255, 255, .08);
    }

    .accordion-body {
        background-color: #1e1e1e;
        color: #ddd;
        font-size: .82rem;
    }

    .track-detail-row {
        display: flex;
        justify-content: space-between;
        padding: 4px 0;
    }

    .track-detail-label {
        opacity: .6;
        font-size: .75rem;
    }

    .track-detail-value {
        font-weight: 500;
    }
    </style>
</head>

<body>
    <!-- ═══ NAVBAR ═══ -->
    <?php require_once __DIR__ . '/../include/sidebar.php'; ?>
    <!-- Main Content -->
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

        <!-- Header -->
        <div class="page-header mb-4">
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <div class="page-header-compact">
                        <h1>
                            <i class="bi bi-disc-fill me-3"></i>
                            Meus Lançamentos
                        </h1>
                        <p class="lead">
                            O <q>Meus Lançamentos</q> agrega todo o catálogo de lançamentos desta conta.
                            Aqui você encontra todos os singles, EPs e álbuns distribuídos para as
                            plataformas digitais. Utilize a busca para encontrar rapidamente o que procura.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-secondary" id="btn-drafts">
                        <i class="bi bi-pencil"></i> Rascunhos
                        <span class="badge bg-warning" id="draft-count-badge">0</span>
                    </button>
                    <?php if ($can_create_release): ?>
                    <button class="btn btn-pink" onclick="window.location='creat-release'">
                        <i class="bi bi-plus"></i> Novo lançamento
                    </button>
                    <?php else: ?>
                    <button class="btn btn-secondary" disabled data-bs-toggle="tooltip"
                        title="<?php echo htmlspecialchars($limit_message); ?>">
                        <i class="bi bi-plus"></i> Novo lançamento
                    </button>
                    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/all-plans" class="btn btn-pink ms-2">
                        <i class="bi bi-arrow-up-circle"></i> Fazer Upgrade
                    </a>
                    <div class="small mt-2">

                        <span class="text-dark badge bg-warning"> <i
                                class="bi bi-exclamation-circle me-1"></i><?php echo htmlspecialchars($limit_message); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Ícone decorativo: disco -->
            <style>
            .page-header::before {
                content: '\F428';
                /* bi-disc-fill */
            }
            </style>
        </div>

        <!-- Filtros -->
        <div class="card mb-4 p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3 col-6">
                    <label class="form-label small mb-1">Título</label>
                    <input type="text" class="form-control form-control-sm" id="f-title"
                        placeholder="Título do álbum" />
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small mb-1">Artista</label>
                    <input type="text" class="form-control form-control-sm" id="f-artist"
                        placeholder="Nome do artista" />
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small mb-1">UPC</label>
                    <input type="text" class="form-control form-control-sm" id="f-upc" placeholder="UPC" />
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small mb-1">Tipo</label>
                    <select class="form-select form-select-sm" id="f-type">
                        <option value="">Todos</option>
                        <option value="single">Single</option>
                        <option value="EP">EP</option>
                        <option value="album">Álbum</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small mb-1">Status</label>
                    <select class="form-select form-select-sm" id="f-status">

                        <option value="">Todos</option>
                        <option value="approved">Aprovado</option>
                        <option value="pending">Pendente</option>
                        <option value="under_review">Em revisão</option>
                        <option value="rejected">Reprovado</option>
                        <option value="draft">Rascunho</option>
                        <option value="deleting">A eliminar...</option>

                    </select>
                </div>
                <div class="col-md-1 col-6">
                    <button class="btn btn-outline-secondary btn-sm w-100" id="btn-clear-filters"
                        title="Limpar filtros">
                        <i class="bi bi-eraser"></i>
                    </button>
                </div>
            </div>
            <!-- Container principal: empilha em mobile, linha no desktop -->
            <div class="mt-2 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <small class="text-muted" id="result-count"></small>

                <!-- Wrapper com scroll horizontal para mobile -->
                <div class="overflow-auto w-100 w-md-auto">
                    <div class="d-flex gap-1 flex-nowrap pb-1" id="status-tabs">
                        <button class="btn btn-sm btn-outline-secondary flex-shrink-0" data-tab="">Todos</button>
                        <button class="btn btn-sm btn-outline-success flex-shrink-0"
                            data-tab="approved">Aprovados</button>
                        <button class="btn btn-sm btn-outline-warning flex-shrink-0"
                            data-tab="pending">Pendentes</button>
                        <button class="btn btn-sm btn-outline-warning flex-shrink-0" data-tab="under_review">Em
                            revisão</button>
                        <button class="btn btn-sm btn-outline-danger flex-shrink-0"
                            data-tab="rejected">Reprovados</button>
                        <button class="btn btn-sm btn-outline-secondary flex-shrink-0"
                            data-tab="draft">Rascunho</button>
                        <button class="btn btn-sm btn-outline-secondary flex-shrink-0" data-tab="deleting">A
                            eliminar...</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de lançamentos -->
        <div id="releases-grid" class="row g-3 mb-4">
            <!-- Preenchido por JS -->
        </div>

        <!-- Paginação -->
        <nav>
            <ul class="pagination justify-content-center" id="pagination"></ul>
        </nav>

    </div><!-- /container -->

    <!-- ══════════════════════════════════════════
     MODAL — Detalhes do Lançamento
══════════════════════════════════════════ -->
    <div class="modal fade" id="albumModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header border-0 pb-1">
                    <div class="d-flex align-items-center gap-3">
                        <div id="m-status-badge"></div>
                        <div>
                            <h5 class="modal-title mb-0" id="m-title"></h5>
                            <small class="text-reset" id="m-artist"></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Capa -->
                        <div class="col-md-4 text-center">
                            <img id="m-cover" src="" alt="Capa" class="album-modal-cover w-100 mb-3" />
                            <div class="d-grid gap-2">
                                <!-- Links de streaming (só approved) -->
                                <div id="m-streaming-wrap" class="d-none">
                                    <p class="small text-muted mb-1">Ouvir em:</p>
                                    <div class="d-flex gap-2 justify-content-center flex-wrap" id="m-streaming-links">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Info -->
                        <div class="col-md-8">
                            <!-- Motivo de rejeição -->
                            <div id="m-reject-wrap" class="alert alert-danger d-none mb-3">
                                <strong><i class="bi bi-x-circle me-1"></i>Motivo da reprovação:</strong>
                                <p class="mb-0 mt-1" id="m-reject-reason"></p>
                            </div>

                            <!-- METADATA COMPLETO -->
                            <div class="row g-2 mb-3" style="font-size:.85rem">
                                <div class="col-6"><span class="text-reset">Tipo:</span><br><span id="m-type"
                                        class="fw-semibold"></span></div>
                                <div class="col-6"><span class="text-reset">Género:</span><br><span id="m-genre"
                                        class="fw-semibold"></span></div>
                                <div class="col-6"><span class="text-reset">Subgénero:</span><br><span id="m-subgenre"
                                        class="fw-semibold"></span></div>
                                <div class="col-6"><span class="text-reset">Idioma:</span><br><span id="m-language"
                                        class="fw-semibold"></span></div>
                                <div class="col-6"><span class="text-reset">Lançamento:</span><br><span id="m-date"
                                        class="fw-semibold"></span></div>
                                <div class="col-6"><span class="text-reset">Selo:</span><br><span id="m-label"
                                        class="fw-semibold"></span></div>
                                <div class="col-6"><span class="text-reset">℗ Copyright:</span><br><span
                                        id="m-copyright-p" class="fw-semibold small"></span></div>
                                <div class="col-6"><span class="text-reset">© Copyright:</span><br><span
                                        id="m-copyright-c" class="fw-semibold small"></span></div>
                                <div class="col-12"><span class="text-reset">UPC:</span> <code id="m-upc"
                                        class="text-white"></code></div>
                                <div class="col-12"><span class="text-reset">ID do Lançamento:</span> <code id="m-id"
                                        class="text-white"></code></div>
                            </div>

                            <!-- ESTATÍSTICAS DAS FAIXAS -->
                            <div id="m-stats-wrap" class="mb-3 small">
                                <div class="d-flex gap-3">
                                    <span><span class="text-reset">Total faixas:</span> <strong
                                            id="m-track-count">0</strong></span>
                                    <span><span class="text-reset">Duração total:</span> <strong
                                            id="m-total-duration">—</strong></span>
                                    <span><span class="text-reset">Explícitas:</span> <strong
                                            id="m-explicit-count">0</strong></span>
                                </div>
                            </div>

                            <!-- LISTAGEM DE FAIXAS (ACORDEÃO) -->
                            <div id="m-tracks-wrap">
                                <p class="text-reset small mb-2">Faixas:</p>
                                <div class="accordion" id="tracksAccordion">

                                </div>
                            </div>

                            <!-- INFORMAÇÃO DE DISTRIBUIÇÃO -->
                            <div id="m-distribution-wrap" class="mt-3 pt-2 border-top border-secondary">
                                <p class="text-reset small mb-2">Distribuição:</p>
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <span class="text-reset">Data de submissão:</span><br>
                                        <span id="m-created" class="fw-semibold"></span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-reset">Última atualização:</span><br>
                                        <span id="m-updated" class="fw-semibold"></span>
                                    </div>
                                    <div class="col-12" id="m-platforms-wrap">
                                        <span class="text-reset">Plataformas:</span><br>
                                        <div id="m-platforms-list" class="d-flex flex-wrap gap-2 mt-1"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0" id="m-footer">
                    <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Fechar</button>
                    <!-- Botões dinâmicos por status -->
                    <a id="m-btn-edit" href="#" class="btn btn-outline-warning btn-sm d-none"><i
                            class="bi bi-pencil me-1"></i>Editar</a>
                    <button id="m-btn-review" class="btn btn-sm d-none" style="background:#FF0089;color:#fff">
                        <i class="bi bi-arrow-repeat me-1"></i>Solicitar Revisão
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
     MODAL — Solicitar Revisão
══════════════════════════════════════════ -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:40px;height:40px;background:rgba(255,0,137,.1)">
                            <i class="bi bi-arrow-repeat" style="color:#ff0089"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0">Solicitar Revisão</h5>
                            <small class="text-muted" id="rev-album-title"></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-1">
                    <div class="alert alert-warning small d-flex gap-2">
                        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                        <div>A equipa da <?php echo APP_NAME ?> irá rever o lançamento novamente. Indica o motivo da
                            solicitação
                            para acelerar o processo.</div>
                    </div>
                    <div id="rev-reject-display" class="alert alert-danger small d-none">
                        <strong>Motivo original da reprovação:</strong>
                        <p class="mb-0 mt-1" id="rev-reject-text"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Justificação <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" id="rev-reason" rows="4" maxlength="500"
                            placeholder="Explica por que o lançamento deve ser revisto. Por exemplo: 'Os metadados estavam incorrectos na primeira submissão. Foram corrigidos. Por favor rever.'"></textarea>
                        <div class="d-flex justify-content-end mt-1">
                            <small class="text-muted"><span id="rev-char-count">0</span>/500</small>
                        </div>
                    </div>
                    <div id="rev-feedback" class="d-none"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <input type="hidden" id="rev-album-id" />
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm" style="background:#FF0089;color:#fff" id="rev-submit-btn">
                        <span id="rev-btn-text"><i class="bi bi-send me-1"></i>Enviar solicitação</span>
                        <span id="rev-btn-load" class="d-none"><span
                                class="spinner-border spinner-border-sm me-1"></span>A enviar...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
     MODAL — Rascunhos (LOCAIS + BD)
══════════════════════════════════════════ -->
    <div class="modal fade" id="draftsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Rascunhos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Tabs para separar os tipos -->
                    <ul class="nav nav-tabs mb-3" id="draftTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="bd-drafts-tab" data-bs-toggle="tab"
                                data-bs-target="#bd-drafts" type="button" role="tab">
                                <i class="bi bi-cloud me-1"></i>Na Nuvem
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="local-drafts-tab" data-bs-toggle="tab"
                                data-bs-target="#local-drafts" type="button" role="tab">
                                <i class="bi bi-pc me-1"></i>Neste Dispositivo
                            </button>
                        </li>
                    </ul>

                    <!-- Conteúdo das tabs -->
                    <div class="tab-content" id="draftTabsContent">
                        <!-- Rascunhos da BD -->
                        <div class="tab-pane fade show active" id="bd-drafts" role="tabpanel">
                            <div id="bd-drafts-list" class="drafts-list">
                                <!-- Carregado via AJAX -->
                                <div class="text-center py-4">
                                    <div class="spinner-border text-wasom" role="status">
                                        <span class="visually-hidden">A carregar...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rascunhos Locais -->
                        <div class="tab-pane fade" id="local-drafts" role="tabpanel">
                            <div id="local-drafts-list" class="drafts-list">
                                <!-- Preenchido via JS -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
     MODAL — Confirmar Eliminação
══════════════════════════════════════════ -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:48px;height:48px;background:rgba(220,53,69,.1)">
                            <i class="bi bi-exclamation-triangle-fill fs-4 text-danger"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0" id="deleteModalTitle">Eliminar Rascunho</h5>
                            <small class="text-muted" id="deleteModalSubtitle"></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-0">
                    <!-- Informação do álbum -->
                    <div class="card bg-light border-0 mb-3" id="deleteAlbumInfo">
                        <div class="card-body p-3">
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0">
                                    <img id="deleteAlbumCover" src="" alt="Capa"
                                        style="width:60px;height:60px;object-fit:cover;border-radius:6px;">
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1" id="deleteAlbumTitle"></h6>
                                    <p class="small text-muted mb-1" id="deleteAlbumArtist"></p>
                                    <p class="small text-muted mb-0" id="deleteAlbumMeta"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aviso específico por tipo -->
                    <div class="alert" id="deleteWarning" role="alert">
                        <!-- Conteúdo dinâmico via JS -->
                    </div>

                    <!-- Input da senha (apenas para BD) -->
                    <div class="mb-3 d-none" id="passwordField">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-lock me-1"></i>Confirma a tua senha atual
                        </label>
                        <input type="password" class="form-control" id="deletePassword" placeholder="••••••••"
                            autocomplete="off">
                        <div class="form-text" id="passwordHelp"></div>
                    </div>

                    <!-- Checkbox de confirmação -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="deleteConfirmCheck">
                        <label class="form-check-label small" for="deleteConfirmCheck" id="deleteConfirmLabel">
                            Compreendo que esta ação é irreversível
                        </label>
                    </div>

                    <!-- Feedback de erro -->
                    <div id="deleteFeedback" class="d-none"></div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <input type="hidden" id="deleteItemId">
                    <input type="hidden" id="deleteItemType"> <!-- 'local', 'draft', 'published' -->
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="deleteConfirmBtn" disabled>
                        <span id="deleteBtnText"><i class="bi bi-trash me-1"></i>Eliminar</span>
                        <span id="deleteBtnLoad" class="d-none">
                            <span class="spinner-border spinner-border-sm me-1"></span>A processar...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
     MODAL — Status de Eliminação
══════════════════════════════════════════ -->
    <div class="modal fade" id="deleteStatusModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:48px;height:48px;background:rgba(23,162,184,.1)">
                            <i class="bi bi-hourglass-split fs-4" style="color:#17a2b8"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0" id="deleteStatusTitle">Pedido de Eliminação</h5>
                            <small class="text-muted" id="deleteStatusSubtitle"></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-0">
                    <!-- Informação do álbum -->
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0">
                                    <img id="statusAlbumCover" src="" alt="Capa"
                                        style="width:60px;height:60px;object-fit:cover;border-radius:6px;">
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1" id="statusAlbumTitle"></h6>
                                    <p class="small text-muted mb-1" id="statusAlbumArtist"></p>
                                    <p class="small text-muted mb-0" id="statusAlbumMeta"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timer e informações -->
                    <div class="alert alert-info d-flex gap-3 align-items-center" id="deleteStatusTimerAlert">
                        <i class="bi bi-clock-history fs-2"></i>
                        <div>
                            <strong id="deleteTimeRemaining">A calcular...</strong>
                            <p class="mb-0 small" id="deleteTimeDetail"></p>
                        </div>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Pedido realizado em:</span>
                            <span class="fw-semibold" id="deleteRequestedAt"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Eliminação automática em:</span>
                            <span class="fw-semibold" id="deleteExpiresAt"></span>
                        </div>
                        <div class="progress mt-2" style="height: 6px;">
                            <div id="deleteProgressBar" class="progress-bar bg-info" role="progressbar"
                                style="width: 0%"></div>
                        </div>
                    </div>

                    <div class="alert alert-warning small" id="deleteStatusRecoveryAlert">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <span id="deleteStatusRecoveryText">Se mudaste de ideia, podes cancelar este pedido. Após <strong>72 horas</strong>, o lançamento
                        será eliminado permanentemente.</span>
                    </div>

                    <div id="deleteStatusFeedback" class="d-none"></div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <input type="hidden" id="deleteStatusAlbumId">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-warning" id="cancelDeleteRequestBtn">
                        <i class="bi bi-x-circle me-1"></i>Cancelar pedido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <script>
    // ── Constantes injectadas pelo PHP ──────────────
    const CSRF = '<?php echo $csrf; ?>';
    const BASE_URL = '<?php echo APP_URL; ?>';
    const ALBUMS_DB = <?php echo $albums_json; ?>;
    const DRAFT_KEY = 'wasom_drafts_<?php echo $id_users; ?>';
    </script>
    <script src="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/launch/js/releases.js"></script>

</body>

</html>
