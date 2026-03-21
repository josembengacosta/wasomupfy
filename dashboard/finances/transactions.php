<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Divisão de Royalties
// Arquivo: dashboard/finances/transactions.php
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

// ── Artistas do utilizador ─────────────────────
$artists_q = $db->prepare("
    SELECT id_artist, stage_name, photo_artist, status_artist
    FROM _artist WHERE id_users = ? AND status_artist != 'blocked'
    ORDER BY stage_name ASC
");
$artists_q->execute([$id_users]);
$artists = $artists_q->fetchAll(PDO::FETCH_ASSOC);

// ── Splits agrupados por artista ───────────────
$splits_q = $db->prepare("
    SELECT ac.*, a.stage_name AS artist_name, a.photo_artist
    FROM _artist_collaborator ac
    JOIN _artist a ON a.id_artist = ac.id_artist
    WHERE a.id_users = ?
    ORDER BY ac.id_artist ASC, ac.royalty_share DESC
");
$splits_q->execute([$id_users]);
$all_splits = $splits_q->fetchAll(PDO::FETCH_ASSOC);

$splits_by_artist = [];
foreach ($all_splits as $s) {
    $splits_by_artist[$s['id_artist']][] = $s;
}
$total_pct_by_artist = [];
foreach ($splits_by_artist as $aid => $splits) {
    $total_pct_by_artist[$aid] = array_sum(array_column($splits, 'royalty_share'));
}

// ── Feedback ───────────────────────────────────
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
$errs = [
    'noartist'  => 'Artista não encontrado ou sem permissão.',
    'over100'   => 'A soma das percentagens excede 100%. Verifique os valores.',
    'sameemail' => 'Não podes dividir royalties com a tua própria conta.',
    'dupli'     => 'Já existe uma divisão com este colaborador para este artista.',
    'notfound'  => 'Divisão não encontrada.',
    'invalid'   => 'Dados inválidos. Verifica o formulário e tenta novamente.',
];

// ── Helpers ────────────────────────────────────
$user_artist_name = htmlspecialchars($user['name_artist_band'] ?? $user['first_name']);
$base_url         = rtrim(APP_URL, '/');
$cover_url        = $base_url . '/assets/comprovantes/uploads/artists/';

$role_labels = [
    'feat'      => 'Featuring',
    'producer'  => 'Produtor',
    'composer'  => 'Compositor',
    'lyricist'  => 'Letrista',
    'manager'   => 'Manager',
    'label'     => 'Editora',
    'other'     => 'Outro',
];
$role_colors = [
    'feat'      => ['bg' => 'rgba(255,0,137,.1)',  'color' => '#FF0089'],
    'producer'  => ['bg' => 'rgba(13,110,253,.1)', 'color' => '#0d6efd'],
    'composer'  => ['bg' => 'rgba(25,135,84,.1)',  'color' => '#198754'],
    'lyricist'  => ['bg' => 'rgba(255,193,7,.12)', 'color' => '#856404'],
    'manager'   => ['bg' => 'rgba(108,117,125,.1)', 'color' => '#6c757d'],
    'label'     => ['bg' => 'rgba(111,66,193,.1)', 'color' => '#6f42c1'],
    'other'     => ['bg' => 'rgba(108,117,125,.1)', 'color' => '#6c757d'],
];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title>Divisão de Royalties — <?php echo APP_NAME; ?></title>
    <style>
    .split-artist-card {
        background: var(--card-bg, #fff);
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 24px;
        transition: box-shadow .2s;
    }

    .split-artist-card:hover {
        box-shadow: 0 6px 32px rgba(255, 0, 137, .08);
    }

    .artist-header {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 20px 24px;
        border-bottom: 1.5px solid var(--border-color, rgba(0, 0, 0, .07));
        background: linear-gradient(135deg, rgba(255, 0, 137, .03), transparent);
    }

    .artist-avatar {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        object-fit: cover;
        flex-shrink: 0;
        background: rgba(255, 0, 137, .08);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        overflow: hidden;
    }

    .artist-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .royalty-bar-wrap {
        padding: 16px 24px;
    }

    .royalty-bar-label {
        display: flex;
        justify-content: space-between;
        font-size: .75rem;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .royalty-bar {
        height: 8px;
        border-radius: 10px;
        background: var(--border-color, rgba(0, 0, 0, .07));
        overflow: hidden;
    }

    .royalty-bar-fill {
        height: 100%;
        border-radius: 10px;
        background: linear-gradient(90deg, #FF0089, #FF4D4D);
        transition: width .5s ease;
    }

    .royalty-bar-fill.over {
        background: linear-gradient(90deg, #dc3545, #ff6b6b);
    }

    .beneficiary-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .84rem;
    }

    .beneficiary-table th {
        font-size: .68rem;
        font-weight: 700;
        color: var(--text-muted, #6c757d);
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 8px 24px;
        border-bottom: 1.5px solid var(--border-color, rgba(0, 0, 0, .07));
        white-space: nowrap;
    }

    .beneficiary-table td {
        padding: 12px 24px;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .05));
        vertical-align: middle;
    }

    .beneficiary-table tr:last-child td {
        border-bottom: none;
    }

    .beneficiary-table tr:hover td {
        background: rgba(255, 0, 137, .02);
    }

    .role-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: .67rem;
        font-weight: 700;
    }

    .pct-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: rgba(255, 0, 137, .08);
        font-size: .85rem;
        font-weight: 800;
        color: #FF0089;
        border: 2px solid rgba(255, 0, 137, .15);
    }

    .btn-split-del {
        background: none;
        border: none;
        color: #dc3545;
        font-size: .85rem;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 8px;
        transition: background .15s;
    }

    .btn-split-del:hover {
        background: rgba(220, 53, 69, .08);
    }

    .empty-splits {
        text-align: center;
        padding: 32px 24px;
        color: var(--text-muted, #6c757d);
    }

    .empty-splits .icon {
        font-size: 2.5rem;
        opacity: .18;
        margin-bottom: 8px;
    }

    .pct-remaining-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: .78rem;
        font-weight: 700;
        background: rgba(25, 135, 84, .1);
        color: #198754;
        border: 1.5px solid rgba(25, 135, 84, .2);
    }

    .pct-remaining-badge.warn {
        background: rgba(255, 193, 7, .1);
        color: #856404;
        border-color: rgba(255, 193, 7, .3);
    }

    .pct-remaining-badge.danger {
        background: rgba(220, 53, 69, .08);
        color: #dc3545;
        border-color: rgba(220, 53, 69, .2);
    }

    .finances-hero {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 20px;
        padding: 28px 32px;
        color: #fff;
        position: relative;
        overflow: hidden;
        margin-bottom: 28px;
    }

    .finances-hero::after {
        content: '';
        position: absolute;
        right: -60px;
        top: -60px;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 0, 137, .18), transparent 70%);
    }

    .finances-hero::before {
        content: '';
        position: absolute;
        left: -40px;
        bottom: -40px;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 77, 77, .1), transparent 70%);
    }
    </style>
</head>

<body>

    <!-- ═══ NAVBAR ═══ -->
    <?php require_once __DIR__ . '/../include/sidebar.php'; ?>
    <!-- Main -->
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
        <!-- Hero -->
        <div class="finances-hero">
            <div class="row align-items-center" style="position:relative;z-index:1">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb" style="margin-bottom:8px">
                        <ol class="breadcrumb mb-0" style="font-size:.75rem;opacity:.6">
                            <li class="breadcrumb-item"><a href="../painel"
                                    class="text-white text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="overview"
                                    class="text-white text-decoration-none">Finanças</a></li>
                            <li class="breadcrumb-item active text-white">Divisão de Royalties</li>
                        </ol>
                    </nav>
                    <h1 class="fw-bold mb-1" style="font-size:1.6rem">
                        <i class="bi bi-pie-chart-fill me-2" style="color:#FF0089"></i>Divisão de Royalties
                    </h1>
                    <p class="mb-0" style="font-size:.88rem;opacity:.7">
                        Gere a partilha de royalties com produtores, compositores, featurings e colaboradores.
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <?php if (!empty($artists)): ?>
                    <button class="btn btn-sm fw-bold"
                        style="background:#FF0089;color:#fff;border:none;border-radius:20px;padding:10px 24px"
                        data-bs-toggle="modal" data-bs-target="#modalNewSplit">
                        <i class="bi bi-plus me-1"></i>Nova divisão
                    </button>
                    <?php endif; ?>
                    <a href="overview" class="btn btn-sm ms-2"
                        style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:20px">
                        <i class="bi bi-arrow-left me-1"></i>Voltar
                    </a>
                </div>
            </div>
        </div>

        <!-- Alertas -->
        <?php if ($success === 'created'): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="border-radius:14px">
            <i class="bi bi-check-circle-fill"></i> Divisão criada com sucesso.
            <button class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php elseif ($success === 'deleted'): ?>
        <div class="alert alert-info d-flex align-items-center gap-2 mb-4" style="border-radius:14px">
            <i class="bi bi-info-circle-fill"></i> Divisão removida.
            <button class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php elseif (!empty($error) && isset($errs[$error])): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius:14px">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($errs[$error]); ?>
            <button class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Sem artistas -->
        <?php if (empty($artists)): ?>
        <div class="split-artist-card text-center p-5">
            <div style="font-size:3rem;opacity:.2;margin-bottom:12px">🎤</div>
            <h5 class="fw-bold">Nenhum artista encontrado</h5>
            <p class="text-muted small mb-4">Precisas de ter pelo menos um artista registado para criar divisões de
                royalties.</p>
            <a href="../artists/add-artist" class="btn btn-sm fw-bold px-4"
                style="background:#FF0089;color:#fff;border:none;border-radius:20px">
                <i class="bi bi-plus me-1"></i>Adicionar artista
            </a>
        </div>

        <?php else: ?>

        <!-- Cards por artista -->
        <?php foreach ($artists as $art):
                $aid     = $art['id_artist'];
                $splits  = $splits_by_artist[$aid] ?? [];
                $used    = (float)($total_pct_by_artist[$aid] ?? 0);
                $free    = max(0.0, 100.0 - $used);
                $bar_pct = min(100, $used);
            ?>
        <div class="split-artist-card" id="artist-card-<?php echo $aid; ?>">

            <!-- Header artista -->
            <div class="artist-header">
                <div class="artist-avatar">
                    <?php if ($art['photo_artist']): ?>
                    <img src="<?php echo htmlspecialchars($cover_url . $art['photo_artist']); ?>"
                        onerror="this.parentElement.innerHTML='🎤'" alt="" />
                    <?php else: ?>🎤<?php endif; ?>
                </div>
                <div style="flex:1;min-width:0">
                    <div class="fw-bold" style="font-size:.97rem"><?php echo htmlspecialchars($art['stage_name']); ?>
                    </div>
                    <div class="text-muted" style="font-size:.75rem">
                        <?php echo count($splits); ?> colaborador<?php echo count($splits) !== 1 ? 'es' : ''; ?>
                        &nbsp;·&nbsp;
                        <?php if ($used > 100): ?>
                        <span style="color:#dc3545;font-weight:600">Excede 100% — revê os valores</span>
                        <?php elseif ($used >= 100): ?>
                        <span style="color:#856404;font-weight:600">100% distribuído</span>
                        <?php else: ?>
                        <span style="color:#198754;font-weight:600"><?php echo number_format($free, 1); ?>%
                            disponível</span>
                        <?php endif; ?>
                    </div>
                </div>
                <button class="btn btn-sm"
                    style="background:rgba(255,0,137,.08);color:#FF0089;border:1px solid rgba(255,0,137,.2);border-radius:10px;font-size:.75rem;font-weight:700;flex-shrink:0"
                    onclick="openSplitModal(<?php echo $aid; ?>, '<?php echo addslashes(htmlspecialchars($art['stage_name'])); ?>', <?php echo number_format($free, 2, '.', ''); ?>)">
                    <i class="bi bi-plus me-1"></i>Adicionar
                </button>
            </div>

            <!-- Barra progresso -->
            <div class="royalty-bar-wrap">
                <div class="royalty-bar-label">
                    <span class="text-muted" style="font-size:.72rem">Royalties distribuídos</span>
                    <span
                        style="font-weight:800;font-size:.82rem;color:<?php echo $used > 100 ? '#dc3545' : ($used >= 100 ? '#856404' : '#FF0089'); ?>">
                        <?php echo number_format($used, 1); ?>%
                    </span>
                </div>
                <div class="royalty-bar">
                    <div class="royalty-bar-fill <?php echo $used > 100 ? 'over' : ''; ?>"
                        style="width:<?php echo $bar_pct; ?>%"></div>
                </div>
            </div>

            <!-- Tabela beneficiários -->
            <?php if (empty($splits)): ?>
            <div class="empty-splits">
                <div class="icon">🤝</div>
                <div class="small">Nenhuma divisão criada para este artista.</div>
                <div class="text-muted" style="font-size:.72rem;margin-top:4px">Clica em "Adicionar" para criar a
                    primeira divisão.</div>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="beneficiary-table">
                    <thead>
                        <tr>
                            <th>Colaborador</th>
                            <th>Função</th>
                            <th>Conta Wasom</th>
                            <th class="text-center">%</th>
                            <th class="text-center">Acções</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($splits as $sp):
                                        $rc = $role_colors[$sp['role_collab']] ?? $role_colors['other'];
                                        $rl = $role_labels[$sp['role_collab']] ?? 'Outro';
                                    ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($sp['name_collab']); ?></div>
                                <?php if ($sp['email_collab']): ?>
                                <div class="text-muted" style="font-size:.72rem">
                                    <?php echo htmlspecialchars($sp['email_collab']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="role-chip"
                                    style="background:<?php echo $rc['bg']; ?>;color:<?php echo $rc['color']; ?>">
                                    <?php echo $rl; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($sp['id_users']): ?>
                                <span class="role-chip" style="background:rgba(25,135,84,.1);color:#198754">
                                    <i class="bi bi-check-circle-fill" style="font-size:.7rem"></i> Verificado
                                </span>
                                <?php else: ?>
                                <span class="role-chip" style="background:rgba(108,117,125,.08);color:#6c757d">
                                    <i class="bi bi-dash-circle" style="font-size:.7rem"></i> Externo
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="pct-badge"><?php echo number_format((float)$sp['royalty_share'], 1); ?>%
                                </div>
                            </td>
                            <td class="text-center">
                                <button class="btn-split-del" title="Remover"
                                    onclick="confirmDelete(<?php echo (int)$sp['id_collab']; ?>, <?php echo $aid; ?>, '<?php echo addslashes(htmlspecialchars($sp['name_collab'])); ?>')">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    </div><!-- /container -->

    <!-- ═══ MODAL — Nova divisão ═══ -->
    <div class="modal fade" id="modalNewSplit" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius:20px;border:none">
                <div class="modal-header border-0 pb-0" style="padding:24px 28px 0">
                    <div>
                        <h5 class="fw-bold mb-0"><i class="bi bi-pie-chart me-2" style="color:#FF0089"></i>Nova divisão
                            de royalties</h5>
                        <p class="text-muted small mb-0 mt-1">Define como os royalties serão partilhados com um
                            colaborador deste artista.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:24px 28px">
                    <form method="POST" action="split_process.php" id="formNewSplit" novalidate>
                        <input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                        <input type="hidden" name="action" value="create" />
                        <input type="hidden" name="honeypot" value="" />

                        <!-- Artista -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Artista <span
                                    class="text-danger">*</span></label>
                            <select name="id_artist" id="selectArtist" class="form-select" required
                                onchange="updateRemainingPct(this.value)">
                                <option value="">— Selecciona um artista —</option>
                                <?php foreach ($artists as $a): ?>
                                <option value="<?php echo $a['id_artist']; ?>"
                                    data-free="<?php echo number_format(max(0, 100 - ($total_pct_by_artist[$a['id_artist']] ?? 0)), 2, '.', ''); ?>">
                                    <?php echo htmlspecialchars($a['stage_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Selecciona um artista.</div>
                            <div class="mt-2" id="pctRemainingWrap" style="display:none">
                                <span class="pct-remaining-badge" id="pctRemainingBadge">
                                    <i class="bi bi-pie-chart-fill"></i>
                                    <span id="pctRemainingVal">100</span>% disponível para distribuir
                                </span>
                            </div>
                        </div>

                        <hr style="border-color:rgba(0,0,0,.07);margin:0 -4px 20px" />

                        <!-- Dados do colaborador -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Nome do colaborador <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name_collab" class="form-control" maxlength="150"
                                    placeholder="Ex: DJ Calvo, Studio X, …" required />
                                <div class="invalid-feedback">Insere o nome do colaborador.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Função <span
                                        class="text-danger">*</span></label>
                                <select name="role_collab" class="form-select" required>
                                    <option value="">— Selecciona —</option>
                                    <?php foreach ($role_labels as $val => $lbl): ?>
                                    <option value="<?php echo $val; ?>"><?php echo $lbl; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Selecciona uma função.</div>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold small">E-mail da conta Wasom Upfy <span
                                        class="text-muted">(opcional)</span></label>
                                <input type="email" name="email_collab" class="form-control" maxlength="255"
                                    placeholder="conta@exemplo.com" />
                                <div class="form-text"><i class="bi bi-info-circle me-1"></i>
                                    Se o colaborador tiver conta na plataforma, introduz o e-mail para que receba os
                                    royalties directamente.
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Percentagem (%) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="royalty_share" id="inputPct" class="form-control"
                                        min="0.1" max="100" step="0.1" placeholder="0.0" required />
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="invalid-feedback">Insere uma percentagem válida (0.1–100).</div>
                            </div>
                        </div>

                        <!-- Aviso -->
                        <div class="p-3 mt-2"
                            style="background:rgba(255,193,7,.07);border-radius:12px;border:1px solid rgba(255,193,7,.25)">
                            <div class="d-flex gap-2 align-items-start">
                                <i class="bi bi-exclamation-triangle-fill text-warning mt-1" style="flex-shrink:0"></i>
                                <div style="font-size:.78rem;color:#856404">
                                    <strong>Atenção:</strong> Ao criar esta divisão confirmas que concordas em partilhar
                                    os royalties gerados por este artista com o colaborador indicado, na percentagem
                                    definida. Esta acção reflecte-se nos relatórios financeiros mensais.
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0" style="padding:0 28px 24px;gap:10px">
                    <button class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formNewSplit" class="btn fw-bold flex-fill"
                        style="background:#FF0089;color:#fff;border:none;border-radius:10px">
                        <i class="bi bi-check-lg me-1"></i>Criar divisão
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- ═══ MODAL — Confirmar delete ═══ -->
    <div class="modal fade" id="modalDeleteSplit" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:380px">
            <div class="modal-content" style="border-radius:18px;border:none">
                <div class="modal-header border-0 pb-0" style="padding:22px 24px 0">
                    <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-trash3 me-2"></i>Remover divisão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:16px 24px">
                    <p class="text-muted small mb-0">
                        Tens a certeza que queres remover a divisão de royalties de
                        <strong id="deleteCollabName">—</strong>?
                        Esta acção não pode ser desfeita.
                    </p>
                </div>
                <div class="modal-footer border-0" style="padding:0 24px 22px;gap:10px">
                    <button class="btn btn-outline-secondary flex-fill btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="split_process.php" style="flex:1">
                        <input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                        <input type="hidden" name="action" value="delete" />
                        <input type="hidden" name="id_collab" id="deleteCollabId" value="" />
                        <input type="hidden" name="id_artist" id="deleteArtistId" value="" />
                        <button type="submit" class="btn btn-danger w-100 btn-sm fw-bold">Sim, remover</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script>
    // Actualizar % disponível ao mudar artista
    function updateRemainingPct(artistId) {
        const sel = document.getElementById('selectArtist');
        const opt = sel.options[sel.selectedIndex];
        const free = parseFloat(opt?.dataset?.free ?? 100);
        const wrap = document.getElementById('pctRemainingWrap');
        const badge = document.getElementById('pctRemainingBadge');
        const val = document.getElementById('pctRemainingVal');
        const inp = document.getElementById('inputPct');

        if (!artistId) {
            wrap.style.display = 'none';
            return;
        }

        wrap.style.display = 'block';
        val.textContent = free.toFixed(1);
        badge.className = 'pct-remaining-badge' + (free <= 0 ? ' danger' : free < 20 ? ' warn' : '');
        inp.max = free > 0 ? free : 0;
        inp.disabled = free <= 0;
        inp.placeholder = free <= 0 ? 'Sem % disponível' : '0.0';
    }

    // Abrir modal pré-seleccionado para artista específico
    function openSplitModal(artistId, artistName, free) {
        const sel = document.getElementById('selectArtist');
        sel.value = artistId;
        updateRemainingPct(artistId);
        new bootstrap.Modal(document.getElementById('modalNewSplit')).show();
    }

    // Confirmar delete
    function confirmDelete(collabId, artistId, name) {
        document.getElementById('deleteCollabId').value = collabId;
        document.getElementById('deleteArtistId').value = artistId;
        document.getElementById('deleteCollabName').textContent = name;
        new bootstrap.Modal(document.getElementById('modalDeleteSplit')).show();
    }

    // Validação do form
    document.getElementById('formNewSplit').addEventListener('submit', function(e) {
        const sel = document.getElementById('selectArtist');
        const pct = document.getElementById('inputPct');
        const pctVal = parseFloat(pct.value);
        let ok = true;

        sel.classList.toggle('is-invalid', !sel.value);
        if (!sel.value) ok = false;

        const pctOk = pct.value && !isNaN(pctVal) && pctVal >= 0.1 && pctVal <= parseFloat(pct.max || 100);
        pct.classList.toggle('is-invalid', !pctOk);
        if (!pctOk) ok = false;

        if (!this.checkValidity()) ok = false;
        this.classList.add('was-validated');
        if (!ok) e.preventDefault();
    });

    // Toastr feedback
    <?php if ($success === 'created'): ?>
    toastr.success('Divisão criada com sucesso!', '', {
        timeOut: 4000,
        positionClass: 'toast-top-right'
    });
    <?php elseif ($success === 'deleted'): ?>
    toastr.info('Divisão removida.', '', {
        timeOut: 3000,
        positionClass: 'toast-top-right'
    });
    <?php elseif (!empty($error) && isset($errs[$error])): ?>
    toastr.error('<?php echo addslashes($errs[$error]); ?>', 'Erro', {
        timeOut: 5000,
        positionClass: 'toast-top-right'
    });
    <?php endif; ?>
    </script>
</body>

</html>