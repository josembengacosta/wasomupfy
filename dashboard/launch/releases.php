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
// Buscar todos os álbuns do utilizador com info do artista e contagem de faixas
// ══════════════════════════════════════════════
// LANÇAMENTOS DA BASE DE DADOS
// ══════════════════════════════════════════════
// Buscar todos os álbuns do utilizador (incluindo 'deleting')
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

// Debug - ver todos os status
error_log('STATUS ENCONTRADOS: ' . implode(', ', array_column($albums_raw, 'status_album')));

// Para cada álbum, buscar as faixas
$albums_data = [];
foreach ($albums_raw as $alb) {
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
    $albums_data[] = $alb;
}

// Codificar para JS de forma segura
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
            <div class="mt-2 d-flex justify-content-between align-items-center">
                <small class="text-muted" id="result-count"></small>
                <!-- Tabs de status rápidos -->
                <div class="d-flex gap-1" id="status-tabs">
                    <button class="btn btn-sm btn-outline-secondary" data-tab="">Todos</button>

                    <button class="btn btn-sm btn-outline-success" data-tab="approved">Aprovados</button>
                    <button class="btn btn-sm btn-outline-warning" data-tab="pending">Pendentes</button>
                    <button class="btn btn-sm btn-outline-warning" data-tab="under_review">Em revisão</button>
                    <button class="btn btn-sm btn-outline-danger" data-tab="rejected">Reprovados</button>
                    <button class="btn btn-sm btn-outline-secondary" data-tab="draft">Rascunho</button>
                    <button class="btn btn-sm btn-outline-secondary" data-tab="deleting">A eliminar...</button>

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

                            <!-- LISTAGEM DE FAIXAS MELHORADA -->
                            <div id="m-tracks-wrap">
                                <p class="text-reset small mb-2">Faixas:</p>
                                <div id="m-tracks-list" class="mb-3"></div>
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
                    <div class="alert alert-info d-flex gap-3 align-items-center">
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

                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Se mudaste de ideia, podes cancelar este pedido. Após <strong>72 horas</strong>, o lançamento
                        será eliminado permanentemente.
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
    // Ativar tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    // ════════════════════════════════════════════════
    // DADOS DA BD (PHP → JS)
    // ════════════════════════════════════════════════
    const CSRF = '<?php echo $csrf; ?>';
    const BASE_URL = '<?php echo APP_URL; ?>';
    const ALBUMS_DB = <?php echo $albums_json; ?>;
    const COVER_BASE = BASE_URL + '/assets/comprovantes/uploads/covers/';

    // ════════════════════════════════════════════════
    // RASCUNHOS — localStorage
    // Chave: wasom_drafts_{id_users}
    // Estrutura: [{id, title, artist, type, genre, created_at, ...}]
    // ════════════════════════════════════════════════
    const DRAFT_KEY = 'wasom_drafts_<?php echo $id_users; ?>';

    function getDrafts() {
        try {
            return JSON.parse(localStorage.getItem(DRAFT_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    function saveDraft(draft) {
        const drafts = getDrafts();
        const idx = drafts.findIndex(d => d.id === draft.id);
        if (idx >= 0) drafts[idx] = draft;
        else drafts.push(draft);
        localStorage.setItem(DRAFT_KEY, JSON.stringify(drafts));
        updateDraftBadge();
    }

    function deleteDraft(draftId) {
        const drafts = getDrafts().filter(d => d.id !== draftId);
        localStorage.setItem(DRAFT_KEY, JSON.stringify(drafts));
        updateDraftBadge();
    }

    function updateDraftBadge() {
        const n = getDrafts().length;
        document.getElementById('draft-count-badge').textContent = n;
        document.getElementById('draft-count-badge').style.display = n ? '' : 'none';
    }

    // ════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════
    const STATUS_LABEL = {
        approved: 'Aprovado',
        pending: 'Pendente',
        under_review: 'Em revisão',
        rejected: 'Reprovado',
        draft: 'Rascunho',
        deleting: 'A eliminar...'
    };
    const STATUS_CLASS = {
        approved: 'approved',
        pending: 'pending',
        under_review: 'warning',
        rejected: 'rejected',
        draft: 'draft',
        deleting: 'warning'
    };
    const TYPE_LABEL = {
        single: 'Single',
        ep: 'EP',
        EP: 'EP',
        album: 'Álbum',
        mixtape: 'Mixtape'
    };

    function fmt_date(str) {
        if (!str) return '—';
        const d = new Date(str);
        if (isNaN(d)) return str;
        return d.toLocaleDateString('pt-PT', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    function cover_url(path) {
        if (!path) return null;
        if (path.startsWith('http')) return path;
        return COVER_BASE + path;
    }

    const PLATFORM_ICONS = {
        spotify: {
            icon: 'bi-spotify',
            color: '#1db954',
            label: 'Spotify'
        },
        apple: {
            icon: 'bi-apple',
            color: '#fc3c44',
            label: 'Apple Music'
        },
        youtube: {
            icon: 'bi-youtube',
            color: '#ff0000',
            label: 'YouTube'
        },
        deezer: {
            icon: 'bi-music-note',
            color: '#ef5466',
            label: 'Deezer'
        },
        amazon: {
            icon: 'bi-amazon',
            color: '#ff9900',
            label: 'Amazon'
        },
        tidal: {
            icon: 'bi-water',
            color: '#00ffff',
            label: 'Tidal'
        },
    };

    // ════════════════════════════════════════════════
    // ESTADO
    // ════════════════════════════════════════════════
    const PER_PAGE = 12;
    let currentPage = 1;
    let filtered = [];

    // ════════════════════════════════════════════════
    // RENDER CARD 
    // ════════════════════════════════════════════════
    function renderCard(alb) {
        const coverUrl = cover_url(alb.img_cover);
        const coverHtml = coverUrl ?
            `<img src="${coverUrl}" class="release-cover" alt="${alb.title_album}" onclick="openModal(${alb.id_album})" loading="lazy"/>` :
            `<div class="release-cover-placeholder" onclick="openModal(${alb.id_album})"><i class="bi bi-disc text-white-50" style="font-size:3rem"></i></div>`;

        const artist = alb.stage_name || alb.real_name || '—';
        const statusClass = STATUS_CLASS[alb.status_album] || 'draft';
        const statusLabel = STATUS_LABEL[alb.status_album] || alb.status_album;
        const trackCount = alb.track_count || (alb.tracks ? alb.tracks.length : 0);

        // Botão Detalhes (sempre presente)
        let actionBtns =
            `<button class="btn btn-apply btn-sm flex-fill" onclick="openModal(${alb.id_album})"><i class="bi bi-eye me-1"></i>Detalhes</button>`;

        // Botões específicos por status
        if (alb.status_album === 'rejected') {
            actionBtns += `
            <button class="btn btn-sm flex-fill" style="background:#FF0089;color:#fff" onclick="openReview(${alb.id_album})">
                <i class="bi bi-arrow-repeat me-1"></i>Revisão
            </button>`;
        }

        // Botão Editar (para todos exceto 'deleting')

        if (['draft', 'pending', 'rejected', 'under_review'].includes(alb.status_album)) {
            actionBtns += `
            <a href="${BASE_URL}/dashboard/edit-release?id=${alb.id_album}" 
               class="btn btn-outline-secondary btn-sm" title="Editar">
                <i class="bi bi-pencil"></i>
            </a>`;
        }


        // BOTÃO ELIMINAR - APENAS approved, draft, rejected
        if (['approved', 'pending', 'rejected', 'draft'].includes(alb.status_album)) {
            // Determinar o tipo para o modal
            let deleteType = 'published';
            if (alb.status_album === 'draft') {
                deleteType = 'draft';
            }

            actionBtns += `
            <button class="btn btn-outline-danger btn-sm" 
                    onclick='openDeleteModal(${alb.id_album}, "${deleteType}", {
                        title: "${alb.title_album.replace(/'/g, "\\'")}",
                        artist: "${(alb.stage_name || alb.real_name || '—').replace(/'/g, "\\'")}",
                        meta: "${trackCount} faixas",
                        cover: "${cover_url(alb.img_cover) || ''}"
                    })'
                    title="Eliminar lançamento">
                <i class="bi bi-trash"></i>
            </button>`;
        } else if (alb.status_album === 'deleting') {
            // Status 'deleting' - botão especial
            actionBtns = `
            <button class="btn btn-warning btn-sm flex-fill" onclick="openDeleteStatusModal(${alb.id_album})">
                <i class="bi bi-hourglass-split me-1"></i>Pedido pendente
            </button>`;
        }


        return `
    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
      <div class="release-card">
        <span class="status-badge status-${statusClass}">${statusLabel}</span>
        ${coverHtml}
        <div class="release-body">
          <p class="release-title" title="${alb.title_album}">${alb.title_album}</p>
          <p class="release-artist">${artist}</p>
          <p class="release-meta"><i class="bi bi-music-note me-1"></i>${trackCount} faixa${trackCount !== 1 ? 's' : ''} &nbsp;·&nbsp; ${TYPE_LABEL[alb.type_album] || alb.type_album || '—'}</p>
        </div>
        <div class="release-actions">${actionBtns}</div>
      </div>
    </div>`;
    }

    // ════════════════════════════════════════════════
    // RENDER GRID + PAGINAÇÃO
    // ════════════════════════════════════════════════
    function renderGrid() {
        const grid = document.getElementById('releases-grid');
        const start = (currentPage - 1) * PER_PAGE;
        const page = filtered.slice(start, start + PER_PAGE);

        if (filtered.length === 0) {
            grid.innerHTML = `
        <div class="col-12">
          <div class="empty-state">
            <i class="bi bi-disc"></i>
            <h5 class="text-muted">Nenhum lançamento encontrado</h5>
            <p class="text-reset small">Altera os filtros ou cria um novo lançamento.</p>
            <a href="${BASE_URL}/dashboard/creat-release" class="btn btn-sm mt-2" style="background:#FF0089;color:#fff">
              <i class="bi bi-plus me-1"></i>Novo lançamento
            </a>
          </div>
        </div>`;
            document.getElementById('pagination').innerHTML = '';
            document.getElementById('result-count').textContent = '0 lançamentos';
            return;
        }

        grid.innerHTML = page.map(renderCard).join('');
        document.getElementById('result-count').textContent =
            `${filtered.length} lançamento${filtered.length !== 1 ? 's' : ''}`;

        // Paginação
        const totalPages = Math.ceil(filtered.length / PER_PAGE);
        const pag = document.getElementById('pagination');
        if (totalPages <= 1) {
            pag.innerHTML = '';
            return;
        }

        let html =
            `<li class="page-item ${currentPage===1?'disabled':''}"><a class="page-link" href="#" data-p="${currentPage-1}">‹</a></li>`;
        for (let i = 1; i <= totalPages; i++) {
            html +=
                `<li class="page-item ${i===currentPage?'active':''}"><a class="page-link" href="#" data-p="${i}">${i}</a></li>`;
        }
        html +=
            `<li class="page-item ${currentPage===totalPages?'disabled':''}"><a class="page-link" href="#" data-p="${currentPage+1}">›</a></li>`;
        pag.innerHTML = html;
        pag.querySelectorAll('.page-link').forEach(a => a.addEventListener('click', e => {
            e.preventDefault();
            const p = parseInt(a.dataset.p);
            if (p && p !== currentPage) {
                currentPage = p;
                renderGrid();
                window.scrollTo(0, 0);
            }
        }));
    }

    // ════════════════════════════════════════════════
    // FILTROS
    // ════════════════════════════════════════════════
    function applyFilters() {
        const t = document.getElementById('f-title').value.toLowerCase();
        const ar = document.getElementById('f-artist').value.toLowerCase();
        const u = document.getElementById('f-upc').value.toLowerCase();
        const tp = document.getElementById('f-type').value;
        const st = document.getElementById('f-status').value;

        filtered = ALBUMS_DB.filter(a =>
            (!t || a.title_album.toLowerCase().includes(t)) &&
            (!ar || (a.stage_name || a.real_name || '').toLowerCase().includes(ar)) &&
            (!u || (a.upc || '').toLowerCase().includes(u)) &&
            (!tp || a.type_album.toLowerCase() === tp.toLowerCase()) &&
            (!st || a.status_album === st)
        );
        currentPage = 1;
        renderGrid();
    }

    ['f-title', 'f-artist', 'f-upc'].forEach(id =>
        document.getElementById(id).addEventListener('input', applyFilters)
    );
    ['f-type', 'f-status'].forEach(id =>
        document.getElementById(id).addEventListener('change', applyFilters)
    );
    document.getElementById('btn-clear-filters').addEventListener('click', () => {
        ['f-title', 'f-artist', 'f-upc'].forEach(id => document.getElementById(id).value = '');
        ['f-type', 'f-status'].forEach(id => document.getElementById(id).value = '');
        document.querySelectorAll('#status-tabs button').forEach(b => b.classList.remove('active'));
        document.querySelector('#status-tabs button[data-tab=""]').classList.add('active');
        applyFilters();
    });

    // Tabs de status rápidos
    document.querySelectorAll('#status-tabs button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#status-tabs button').forEach(b => b.classList.remove(
                'active'));
            btn.classList.add('active');
            document.getElementById('f-status').value = btn.dataset.tab;
            applyFilters();
        });
    });

    // ════════════════════════════════════════════════
    // MODAL DETALHES
    // ════════════════════════════════════════════════
    function openModal(albumId) {
        const alb = ALBUMS_DB.find(a => a.id_album == albumId);
        if (!alb) return;

        const coverUrl = cover_url(alb.img_cover);
        document.getElementById('m-cover').src = coverUrl || '../assets/img/placeholder-album.png';
        document.getElementById('m-title').textContent = alb.title_album;
        document.getElementById('m-artist').textContent = alb.stage_name || alb.real_name || '—';
        document.getElementById('m-type').textContent = TYPE_LABEL[alb.type_album] || alb.type_album || '—';
        document.getElementById('m-genre').textContent = alb.genre_main || '—';
        document.getElementById('m-subgenre').textContent = alb.genre_secondary || '—';
        document.getElementById('m-language').textContent = alb.language || '—';
        document.getElementById('m-date').textContent = fmt_date(alb.release_date);
        document.getElementById('m-label').textContent = alb.label_name || '—';
        document.getElementById('m-copyright-p').textContent = alb.copyright_p || '—';
        document.getElementById('m-copyright-c').textContent = alb.copyright_c || '—';
        document.getElementById('m-upc').textContent = alb.upc || '—';
        document.getElementById('m-id').textContent = alb.id_album || '—';
        document.getElementById('m-created').textContent = alb.creat_album ? new Date(alb.creat_album)
            .toLocaleString(
                'pt-PT') : '—';
        document.getElementById('m-updated').textContent = alb.modif_album ? new Date(alb.modif_album)
            .toLocaleString(
                'pt-PT') : '—';

        // Status badge
        const statusClass = STATUS_CLASS[alb.status_album] || 'draft';
        const statusLabel = STATUS_LABEL[alb.status_album] || alb.status_album;
        document.getElementById('m-status-badge').innerHTML =
            `<span class="status-badge status-${statusClass}" style="position:static">${statusLabel}</span>`;

        // Motivo de rejeição
        const rejWrap = document.getElementById('m-reject-wrap');
        if (alb.status_album === 'rejected' && alb.rejection_reason) {
            document.getElementById('m-reject-reason').textContent = alb.rejection_reason;
            rejWrap.classList.remove('d-none');
        } else {
            rejWrap.classList.add('d-none');
        }

        // Faixas e estatísticas
        const tracks = alb.tracks || [];
        document.getElementById('m-track-count').textContent = tracks.length;

        // Calcular duração total e faixas explícitas
        let totalSeconds = 0;
        let explicitCount = 0;

        const tracksList = document.getElementById('m-tracks-list');
        tracksList.innerHTML = tracks.length ?
            tracks.map((t, i) => {
                // Extrair segundos da duração formatada (MM:SS)
                if (t.duration_track) {
                    const parts = t.duration_track.split(':');
                    if (parts.length === 2) {
                        totalSeconds += parseInt(parts[0]) * 60 + parseInt(parts[1]);
                    }
                }

                return `
            <div class="track-row">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                    <span class="track-num">${t.position_track || i+1}</span>
                    <div class="overflow-hidden">
                        <div class="fw-semibold text-truncate">${t.title_track}</div>
                        <div class="d-flex gap-2 small">
                            ${t.isrc ? `<span class="track-isrc">ISRC: ${t.isrc}</span>` : ''}
                        </div>
                        ${t.featuring_track ? `<div class="track-isrc text-reset small">com ${t.featuring_track}</div>` : ''}
                        ${t.audio_file ? `<div class="track-isrc text-reset small"><i class="bi bi-file-music"></i> Áudio: ${t.audio_file}</div>` : ''}
                    </div>
                </div>
                <span class="text-reset small flex-shrink-0">${t.duration_track || ''}</span>
            </div>`;
            }).join('') :
            '<p class="text-reset small">Nenhuma faixa registada.</p>';

        // Atualizar estatísticas
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        let durationStr = '';
        if (hours > 0) durationStr += `${hours}h `;
        if (minutes > 0 || hours > 0) durationStr += `${minutes}min `;
        durationStr += `${seconds}s`;

        document.getElementById('m-total-duration').textContent = durationStr;
        document.getElementById('m-explicit-count').textContent = explicitCount;

        // Streaming links (só approved)
        const streamWrap = document.getElementById('m-streaming-wrap');
        if (alb.status_album === 'approved') {
            streamWrap.classList.remove('d-none');
            document.getElementById('m-streaming-links').innerHTML =
                Object.entries(PLATFORM_ICONS).map(([slug, p]) =>
                    `<a href="#" title="${p.label}" style="font-size:1.5rem;color:${p.color}"><i class="bi ${p.icon}"></i></a>`
                ).join('');
        } else {
            streamWrap.classList.add('d-none');
        }

        // Plataformas de distribuição
        const platformsList = document.getElementById('m-platforms-list');
        if (alb.status_album === 'approved') {
            platformsList.innerHTML = Object.entries(PLATFORM_ICONS).map(([slug, p]) =>
                `<a href="#" title="${p.label}" style="font-size:1.2rem;color:${p.color}" class="me-2">
                <i class="bi ${p.icon}"></i>
            </a>`
            ).join('');
        } else {
            platformsList.innerHTML = '<span class="text-muted">Disponível após aprovação</span>';
        }

        // Botões do footer
        const btnEdit = document.getElementById('m-btn-edit');
        const btnReview = document.getElementById('m-btn-review');
        btnEdit.classList.add('d-none');
        btnReview.classList.add('d-none');

        if (alb.status_album === 'rejected') {
            btnEdit.href = `${BASE_URL}/dashboard/edit-release?id=${alb.id_album}`;
            btnEdit.innerHTML = '<i class="bi bi-pencil me-1"></i>Editar';
            btnEdit.classList.remove('d-none');
            btnReview.classList.remove('d-none');
            btnReview.onclick = () => {
                bootstrap.Modal.getInstance(document.getElementById('albumModal')).hide();
                openReview(albumId);
            };
        } else if (alb.status_album === 'draft') {
            btnEdit.href = `${BASE_URL}/dashboard/creat-release?draft=${alb.id_album}`;
            btnEdit.innerHTML = '<i class="bi bi-play-fill me-1"></i>Continuar rascunho';
            btnEdit.classList.remove('d-none');
        } else if (alb.status_album !== 'pending') {
            btnEdit.href = `${BASE_URL}/dashboard/edit-release?id=${alb.id_album}`;
            btnEdit.innerHTML = '<i class="bi bi-pencil me-1"></i>Editar';
            btnEdit.classList.remove('d-none');
        }

        new bootstrap.Modal(document.getElementById('albumModal')).show();
    }

    // ════════════════════════════════════════════════
    // MODAL REVISÃO
    // ════════════════════════════════════════════════
    function openReview(albumId) {
        const alb = ALBUMS_DB.find(a => a.id_album == albumId);
        if (!alb) return;

        document.getElementById('rev-album-id').value = albumId;
        document.getElementById('rev-album-title').textContent = alb.title_album + ' — ' + (alb.stage_name ||
            alb
            .real_name || '');
        document.getElementById('rev-reason').value = '';
        document.getElementById('rev-char-count').textContent = '0';
        document.getElementById('rev-feedback').classList.add('d-none');

        const rejDisplay = document.getElementById('rev-reject-display');
        if (alb.rejection_reason) {
            document.getElementById('rev-reject-text').textContent = alb.rejection_reason;
            rejDisplay.classList.remove('d-none');
        } else {
            rejDisplay.classList.add('d-none');
        }

        new bootstrap.Modal(document.getElementById('reviewModal')).show();
    }

    // Contador do textarea
    document.getElementById('rev-reason').addEventListener('input', function() {
        document.getElementById('rev-char-count').textContent = this.value.length;
    });

    // Enviar solicitação de revisão
    document.getElementById('rev-submit-btn').addEventListener('click', function() {
        const albumId = document.getElementById('rev-album-id').value;
        const reason = document.getElementById('rev-reason').value.trim();
        const fb = document.getElementById('rev-feedback');

        if (reason.length < 20) {
            fb.innerHTML =
                '<div class="alert alert-danger small py-2">A justificação deve ter pelo menos 20 caracteres.</div>';
            fb.classList.remove('d-none');
            return;
        }

        document.getElementById('rev-btn-text').classList.add('d-none');
        document.getElementById('rev-btn-load').classList.remove('d-none');
        this.disabled = true;

        fetch(BASE_URL + '/dashboard/release_process', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=request_review&id_album=${encodeURIComponent(albumId)}&reason=${encodeURIComponent(reason)}&csrf_token=${encodeURIComponent(CSRF)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    fb.innerHTML =
                        '<div class="alert alert-success small py-2"><i class="bi bi-check-circle me-1"></i>' +
                        data.message + '</div>';
                    fb.classList.remove('d-none');
                    document.getElementById('rev-submit-btn').style.display = 'none';
                    // Toastr
                    toastr.success('Solicitação de revisão enviada!');
                    setTimeout(() => bootstrap.Modal.getInstance(document.getElementById(
                            'reviewModal'))
                        .hide(), 2000);
                } else {
                    fb.innerHTML = '<div class="alert alert-danger small py-2">' + (data.message ||
                        'Erro. Tenta novamente.') + '</div>';
                    fb.classList.remove('d-none');
                }
            })
            .catch(() => {
                fb.innerHTML =
                    '<div class="alert alert-danger small py-2">Erro de ligação. Verifica a tua internet.</div>';
                fb.classList.remove('d-none');
            })
            .finally(() => {
                document.getElementById('rev-btn-text').classList.remove('d-none');
                document.getElementById('rev-btn-load').classList.add('d-none');
                document.getElementById('rev-submit-btn').disabled = false;
            });
    });



    // ════════════════════════════════════════════════
    // MODAL RASCUNHOS (LOCAIS + BD)
    // ════════════════════════════════════════════════
    document.getElementById('btn-drafts').addEventListener('click', async () => {
        // Abrir modal
        const modal = new bootstrap.Modal(document.getElementById('draftsModal'));
        modal.show();

        // Carregar rascunhos locais (rápido)
        carregarRascunhosLocais();

        // Carregar rascunhos da BD (via AJAX)
        await carregarRascunhosBD();
    });

    function carregarRascunhosLocais() {
        const drafts = getDrafts();
        const container = document.getElementById('local-drafts-list');

        if (drafts.length === 0) {
            container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="bi bi-pencil fs-1 d-block mb-3 opacity-25"></i>
                <p>Não tens rascunhos guardados neste dispositivo.</p>
                <p class="small">Os rascunhos locais são guardados quando começas a preencher um novo lançamento sem o terminar.</p>
            </div>`;
            return;
        }

        container.innerHTML = drafts.map(d => `
        <div class="draft-item d-flex align-items-center gap-3 p-3 border rounded mb-2">
            <i class="bi bi-file-earmark-music fs-3 text-muted flex-shrink-0"></i>
            <div class="flex-grow-1 overflow-hidden">
                <div class="fw-semibold text-truncate">${d.title || 'Sem título'}</div>
                <div class="text-reset small">${d.artist_names || '—'} &nbsp;·&nbsp; ${d.type || '—'}</div>
                <div class="text-reset small">Guardado: ${d.saved_at ? new Date(d.saved_at).toLocaleString('pt-PT') : '—'}</div>
                <span class="badge bg-secondary">Local</span>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <a href="${BASE_URL}/dashboard/creat-release?local_draft=${d.id}" 
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-play-fill"></i> Continuar
                </a>
                <button class="btn btn-sm btn-outline-danger" onclick="removerRascunhoLocal('${d.id}')">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
    }

    async function carregarRascunhosBD() {
        const container = document.getElementById('bd-drafts-list');

        try {
            const res = await fetch(BASE_URL + '/dashboard/get_drafts', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `csrf_token=${encodeURIComponent(CSRF)}`
            });

            const data = await res.json();

            if (data.drafts && data.drafts.length > 0) {
                container.innerHTML = data.drafts.map(d => {
                    // Escapar dados para o modal
                    const title = (d.title_album || 'Sem título').replace(/'/g, "\\'");
                    const artist = (d.name_author_band || '—').replace(/'/g, "\\'");
                    const cover = d.cover_url ? d.cover_url : '';

                    return `
                <div class="draft-item d-flex align-items-center gap-3 p-3 border rounded mb-2">
                    <i class="bi bi-cloud fs-3 text-wasom flex-shrink-0"></i>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold text-truncate">${d.title_album || 'Sem título'}</div>
                        <div class="text-reset small">${d.name_author_band || '—'} &nbsp;·&nbsp; ${d.type_album || '—'}</div>
                        <div class="text-reset small">Criado: ${d.creat_album ? new Date(d.creat_album).toLocaleString('pt-PT') : '—'}</div>
                        <span class="badge bg-wasom">Na Nuvem</span>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <a href="${BASE_URL}/dashboard/creat-release?draft=${d.id_album}" 
                           class="btn btn-sm" style="background:#FF0089;color:#fff">
                            <i class="bi bi-play-fill me-1"></i>Continuar
                        </a>
                        <button class="btn btn-sm btn-outline-danger" onclick='openDeleteModal(${d.id_album}, "draft", {
                            title: "${title}",
                            artist: "${artist}",
                            meta: "${d.track_count || 0} faixas",
                            cover: "${cover}"
                        })'>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `
                }).join('');
            } else {
                container.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-cloud fs-1 d-block mb-3 opacity-25"></i>
                    <p>Não tens rascunhos guardados na nuvem.</p>
                    <p class="small">Os rascunhos na nuvem são lançamentos que começaste mas não finalizaste.</p>
                </div>`;
            }
        } catch (err) {
            container.innerHTML = `
            <div class="alert alert-danger small">
                Erro ao carregar rascunhos da nuvem.
            </div>`;
        }
    }

    // ════════════════════════════════════════════════
    // MODAL DE ELIMINAÇÃO 
    // ════════════════════════════════════════════════
    function openDeleteModal(itemId, itemType, itemData = {}) {
        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));

        // Guardar dados
        document.getElementById('deleteItemId').value = itemId;
        document.getElementById('deleteItemType').value = itemType;

        // Preencher informações do álbum
        document.getElementById('deleteAlbumTitle').textContent = itemData.title || 'Sem título';
        document.getElementById('deleteAlbumArtist').textContent = itemData.artist || '—';
        document.getElementById('deleteAlbumMeta').textContent = itemData.meta || '';

        if (itemData.cover) {
            document.getElementById('deleteAlbumCover').src = itemData.cover;
            document.getElementById('deleteAlbumCover').style.display = 'block';
        } else {
            document.getElementById('deleteAlbumCover').src = '../assets/img/placeholder-album.png';
        }

        // Reset do modal
        document.getElementById('deletePassword').value = '';
        document.getElementById('deleteConfirmCheck').checked = false;
        document.getElementById('deleteConfirmBtn').disabled = true;
        document.getElementById('passwordField').classList.add('d-none');
        document.getElementById('deleteFeedback').classList.add('d-none');

        // Configurar baseado no tipo
        let warningHtml = '';
        let subtitle = '';

        switch (itemType) {
            case 'local':
                subtitle = 'Rascunho local (neste dispositivo)';
                warningHtml = `
                <div class="d-flex gap-2">
                    <i class="bi bi-pc-display fs-4 flex-shrink-0"></i>
                    <div>
                        <strong>Rascunho local</strong>
                        <p class="mb-0 small">Este rascunho está apenas neste dispositivo. 
                        Ao eliminar, não poderás recuperá-lo.</p>
                    </div>
                </div>`;
                document.getElementById('deleteConfirmLabel').innerHTML =
                    'Compreendo que este rascunho local será eliminado permanentemente.';
                break;


            case 'draft': // Para rascunhos na nuvem
                subtitle = 'Rascunho na nuvem';
                warningHtml = `
        <div class="d-flex gap-2">
            <i class="bi bi-cloud-arrow-up fs-4 flex-shrink-0 text-warning"></i>
            <div>
                <strong>Rascunho na nuvem</strong>
                <p class="mb-0 small">Ao solicitares a eliminação, este rascunho será movido para a lixeira 
                e eliminado permanentemente após <strong>72 horas</strong>.</p>
            </div>
        </div>`;
                document.getElementById('passwordField').classList.remove('d-none');
                document.getElementById('passwordHelp').textContent =
                    'Confirma a tua senha para solicitar a eliminação.';
                document.getElementById('deleteConfirmLabel').innerHTML =
                    'Compreendo que após 72 horas o rascunho será eliminado.';
                break;

            case 'published': // Para approved, pending, rejected
                subtitle = 'Lançamento publicado';
                warningHtml = `
        <div class="d-flex gap-2">
            <i class="bi bi-globe2 fs-4 flex-shrink-0 text-danger"></i>
            <div>
                <strong>Lançamento publicado</strong>
                <p class="mb-0 small">Este lançamento está ativo nas plataformas. 
                Ao solicitares a remoção, será removido após <strong>72 horas</strong>.</p>
            </div>
        </div>`;
                document.getElementById('passwordField').classList.remove('d-none');
                document.getElementById('passwordHelp').textContent = 'Confirma a tua senha para solicitar a remoção.';
                document.getElementById('deleteConfirmLabel').innerHTML = 'Compreendo que após 72 horas será removido.';
                break;
        }

        document.getElementById('deleteModalSubtitle').textContent = subtitle;
        document.getElementById('deleteWarning').innerHTML = warningHtml;
        document.getElementById('deleteWarning').className =
            itemType === 'published' ? 'alert alert-danger' : 'alert alert-warning';

        modal.show();
    }

    // Ativar checkbox para habilitar botão
    document.getElementById('deleteConfirmCheck').addEventListener('change', function() {
        const btn = document.getElementById('deleteConfirmBtn');
        btn.disabled = !this.checked;
    });


    // Ação de eliminar - VERSÃO CORRIGIDA
    document.getElementById('deleteConfirmBtn').addEventListener('click', async function() {
        const itemId = document.getElementById('deleteItemId').value;
        const itemType = document.getElementById('deleteItemType').value;
        const password = document.getElementById('deletePassword').value;
        const feedback = document.getElementById('deleteFeedback');

        // Loading state
        document.getElementById('deleteBtnText').classList.add('d-none');
        document.getElementById('deleteBtnLoad').classList.remove('d-none');
        this.disabled = true;

        try {
            let response;

            if (itemType === 'local') {
                // Eliminar rascunho local
                deleteDraft(itemId);
                toastr.success('Rascunho local eliminado!');
                bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();
                carregarRascunhosLocais();

                // Voltar ao estado normal
                document.getElementById('deleteBtnText').classList.remove('d-none');
                document.getElementById('deleteBtnLoad').classList.add('d-none');
                this.disabled = false;
                return;
            }

            // Para rascunhos BD ou publicados, precisa de senha
            if (!password) {
                feedback.innerHTML =
                    '<div class="alert alert-danger small py-2">A senha é obrigatória.</div>';
                feedback.classList.remove('d-none');

                // Voltar ao estado normal
                document.getElementById('deleteBtnText').classList.remove('d-none');
                document.getElementById('deleteBtnLoad').classList.add('d-none');
                this.disabled = false;
                return;
            }

            // Verificar senha e criar pedido
            const formData = new FormData();
            formData.append('action', itemType === 'draft' ? 'delete_draft' :
                (itemType === 'published' ? 'delete_release_request' : 'delete_draft_request'));
            formData.append('id_album', itemId);
            if (itemType !== 'draft') {
                formData.append('password', password);
            }
            formData.append('csrf_token', CSRF);

            console.log('A enviar pedido para:', BASE_URL + '/dashboard/launch/release_process.php');
            console.log('Action:', itemType === 'draft' ? 'delete_draft_request' :
                'delete_release_request');

            response = await fetch(BASE_URL + '/dashboard/release_process', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            console.log('Resposta do servidor:', data);


            if (data.ok) {
                // Fechar modal primeiro
                bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();

                // SweetAlert de sucesso
                await Swal.fire({
                    icon: 'success',
                    title: 'Solicitação enviada!',
                    html: `
            <p class="mb-2">${data.message}</p>
            <p class="mb-0 text-reset small">O álbum será eliminado em 72 horas, a menos que canceles o pedido.</p>
        `,
                    confirmButtonColor: '#FF0089'
                });

                // Recarregar listas
                if (itemType === 'draft') {
                    carregarRascunhosBD();
                } else {
                    setTimeout(() => window.location.reload(), 1500);
                }
            } else {
                // Mostrar erro no feedback do modal (já tens)
                feedback.innerHTML = `<div class="alert alert-danger small py-2">${data.message}</div>`;
                feedback.classList.remove('d-none');

                // Voltar ao estado normal
                document.getElementById('deleteBtnText').classList.remove('d-none');
                document.getElementById('deleteBtnLoad').classList.add('d-none');
                this.disabled = false;
            }

        } catch (err) {
            console.error('Erro detalhado:', err);
            feedback.innerHTML =
                '<div class="alert alert-danger small py-2">Erro de ligação. Tenta novamente.</div>';
            feedback.classList.remove('d-none');

            // Voltar ao estado normal
            document.getElementById('deleteBtnText').classList.remove('d-none');
            document.getElementById('deleteBtnLoad').classList.add('d-none');
            this.disabled = false;
        }
    });

    // ════════════════════════════════════════════════
    // ELIMINAR RASCUNHO LOCAL
    // ════════════════════════════════════════════════
    function removerRascunhoLocal(draftId) {
        // Buscar dados do rascunho
        const drafts = getDrafts();
        const draft = drafts.find(d => d.id === draftId);

        if (!draft) {
            toastr.error('Rascunho não encontrado');
            return;
        }

        // Abrir o modal de eliminação com tipo 'local'
        openDeleteModal(draftId, 'local', {
            title: draft.title || 'Sem título',
            artist: draft.artist_names || '—',
            meta: 'Rascunho local',
            cover: '' // Rascunhos locais não têm capa guardada
        });
    }

    // ════════════════════════════════════════════════
    // MODAL DE STATUS DE ELIMINAÇÃO
    // ════════════════════════════════════════════════
    function openDeleteStatusModal(albumId) {
        const alb = ALBUMS_DB.find(a => a.id_album == albumId);
        if (!alb) return;

        // Preencher informações básicas
        document.getElementById('statusAlbumTitle').textContent = alb.title_album || 'Sem título';
        document.getElementById('statusAlbumArtist').textContent = alb.stage_name || alb.real_name || '—';
        document.getElementById('statusAlbumMeta').textContent = `${alb.track_count || 0} faixas`;

        const coverUrl = cover_url(alb.img_cover);
        if (coverUrl) {
            document.getElementById('statusAlbumCover').src = coverUrl;
        } else {
            document.getElementById('statusAlbumCover').src = '../assets/img/placeholder-album.png';
        }

        // Guardar ID
        document.getElementById('deleteStatusAlbumId').value = albumId;

        // Calcular tempo restante (se tiver as datas)
        if (alb.delete_requested_at && alb.delete_expires_at) {
            const requested = new Date(alb.delete_requested_at);
            const expires = new Date(alb.delete_expires_at);
            const now = new Date();

            // Formatar datas
            document.getElementById('deleteRequestedAt').textContent =
                requested.toLocaleString('pt-PT');
            document.getElementById('deleteExpiresAt').textContent =
                expires.toLocaleString('pt-PT');

            // Calcular progresso (quanto % já passou)
            const totalTime = expires - requested;
            const elapsedTime = now - requested;
            const progress = Math.min(100, Math.max(0, (elapsedTime / totalTime) * 100));

            document.getElementById('deleteProgressBar').style.width = progress + '%';

            // Calcular tempo restante
            const timeLeft = expires - now;
            if (timeLeft > 0) {
                const hoursLeft = Math.floor(timeLeft / (1000 * 60 * 60));
                const minutesLeft = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));

                document.getElementById('deleteTimeRemaining').textContent =
                    `${hoursLeft}h ${minutesLeft}min restantes`;
                document.getElementById('deleteTimeDetail').textContent =
                    `A eliminação automática ocorrerá em ${hoursLeft}h ${minutesLeft}min`;
            } else {
                document.getElementById('deleteTimeRemaining').textContent = 'A processar...';
                document.getElementById('deleteTimeDetail').textContent =
                    'O prazo expirou. A eliminação será processada em breve.';
            }
        } else {
            document.getElementById('deleteRequestedAt').textContent = '—';
            document.getElementById('deleteExpiresAt').textContent = '—';
            document.getElementById('deleteTimeRemaining').textContent = 'Em processamento';
            document.getElementById('deleteTimeDetail').textContent =
                'O pedido está a ser processado.';
            document.getElementById('deleteProgressBar').style.width = '50%';
        }

        new bootstrap.Modal(document.getElementById('deleteStatusModal')).show();
    }

    // ════════════════════════════════════════════════
    // CANCELAR PEDIDO DE ELIMINAÇÃO - COM SWEETALERT
    // ════════════════════════════════════════════════
    document.getElementById('cancelDeleteRequestBtn').addEventListener('click', async function() {
        const albumId = document.getElementById('deleteStatusAlbumId').value;
        const feedback = document.getElementById('deleteStatusFeedback');

        // SweetAlert de confirmação (substitui o confirm)
        const confirmResult = await Swal.fire({
            title: 'Cancelar pedido?',
            text: 'Tens a certeza que queres cancelar o pedido de eliminação? O álbum voltará ao estado anterior.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, cancelar pedido',
            cancelButtonText: 'Não, manter'
        });

        if (!confirmResult.isConfirmed) {
            return; // Usuário cancelou
        }

        // Mostrar loading
        Swal.fire({
            title: 'A processar...',
            html: 'A cancelar pedido de eliminação',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const formData = new FormData();
            formData.append('action', 'cancel_delete_request');
            formData.append('id_album', albumId);
            formData.append('csrf_token', CSRF);

            const response = await fetch(BASE_URL + '/dashboard/release_process', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            // Fechar loading
            Swal.close();

            if (data.ok) {
                // Sucesso com SweetAlert
                await Swal.fire({
                    icon: 'success',
                    title: 'Pedido cancelado!',
                    html: `
                    <p class="mb-2">${data.message}</p>
                    <p class="mb-0 text-reset small">O álbum voltou ao estado anterior e não será eliminado.</p>
                `,
                    confirmButtonColor: '#FF0089'
                });

                // Fechar modal e recarregar
                bootstrap.Modal.getInstance(document.getElementById('deleteStatusModal')).hide();
                setTimeout(() => window.location.reload(), 1500);

            } else {
                // Erro com SweetAlert
                await Swal.fire({
                    icon: 'error',
                    title: 'Erro ao cancelar',
                    text: data.message || 'Ocorreu um erro ao cancelar o pedido.',
                    confirmButtonColor: '#FF0089'
                });
            }

        } catch (err) {
            console.error('Erro:', err);
            Swal.close();

            // Erro de ligação com SweetAlert
            await Swal.fire({
                icon: 'error',
                title: 'Erro de ligação',
                text: 'Verifica a tua internet e tenta novamente.',
                confirmButtonColor: '#FF0089'
            });
        }
    });

    // ════════════════════════════════════════════════
    // INIT - carregar dados, configurar filtros, badges, etc  
    // ════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', function() {
        updateDraftBadge();

        // 1️⃣ Garantir que a tab "Todos" está activa visualmente
        const tabs = document.querySelectorAll('#status-tabs button');
        if (tabs.length > 0) {
            tabs.forEach(b => b.classList.remove('active'));
            document.querySelector('#status-tabs button[data-tab=""]')?.classList.add('active');
        }

        // 2️⃣ Garantir que o filtro de status está vazio (todos)
        const statusFilter = document.getElementById('f-status');
        if (statusFilter) statusFilter.value = '';

        // 3️⃣ Atribuir todos os álbuns ao array filtrado
        filtered = [...ALBUMS_DB]; // Isto pega TODOS os lançamentos

        // 4️⃣ Renderizar na primeira página
        currentPage = 1;
        renderGrid(); // ← Esta função desenha os cards na tela

        // Também adicionar listeners para as tabs garantirem que mostram todos
        document.querySelectorAll('#status-tabs button').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('#status-tabs button').forEach(b => b
                    .classList
                    .remove('active'));
                btn.classList.add('active');
                document.getElementById('f-status').value = btn.dataset.tab;

                // Se clicou em "Todos", limpar também outros filtros de texto?
                if (btn.dataset.tab === '') {
                    // Opcional: limpar filtros de texto também
                    // document.getElementById('f-title').value = '';
                    // document.getElementById('f-artist').value = '';
                    // document.getElementById('f-upc').value = '';
                }

                applyFilters();
            });
        });
    });

    // Toastr config
    toastr.options = {
        positionClass: 'toast-top-right',
        timeOut: 4000,
        progressBar: true,
        closeButton: true
    };

    // ── Badge de notificações — polling 60s ──────────────────
    (function() {
        function refreshBadge() {
            fetch('./ajax/notifications_api.php?action=count', {
                    credentials: 'same-origin'
                })
                .then(r => r.json())
                .then(data => {
                    var b = document.getElementById('navNotifBadge');
                    if (!b) return;
                    var n = parseInt(data.unread || 0);
                    b.textContent = n > 99 ? '99+' : n;
                    b.style.display = n > 0 ? '' : 'none';
                }).catch(function() {});
        }
        setTimeout(function() {
            refreshBadge();
            setInterval(refreshBadge, 60000);
        }, 30000);
    })();
    </script>
</body>

</html>