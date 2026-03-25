<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Gestão de Artistas
// Arquivo: dashboard/artists/add-artist.php
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

// ── Plano activo ──────────────────────────────
$plan_paid = ($user['status_user'] === 'active' && !empty($user['plan_activated_at']));
$plan_id   = (int)$user['plan_selected'];
$plan      = null;
$max_artists = 1;
if ($plan_id) {
    $ps = $db->prepare('SELECT * FROM _plans WHERE id_plan = ?');
    $ps->execute([$plan_id]);
    $plan = $ps->fetch();
    if ($plan) $max_artists = (int)($plan['max_artists'] ?? 1);
}

// ── Artistas actuais ──────────────────────────
$art_stmt = $db->prepare("
    SELECT id_artist, stage_name, real_name, photo_artist, genre_main,
           country, city, status_artist, creat_artist,
           spotify_url, youtube_url, instagram_url, tiktok_url, facebook_url, website_url, bio
    FROM _artist WHERE id_users = ?
    ORDER BY creat_artist DESC
");
$art_stmt->execute([$id_users]);
$artists = $art_stmt->fetchAll(PDO::FETCH_ASSOC);
$artist_count = count($artists);
$can_add = $artist_count < $max_artists;

// ── Editar artista (GET ?edit=id) ─────────────
$edit_artist = null;
if (!empty($_GET['edit'])) {
    $ea = $db->prepare("SELECT * FROM _artist WHERE id_artist = ? AND id_users = ?");
    $ea->execute([(int)$_GET['edit'], $id_users]);
    $edit_artist = $ea->fetch();
}

// Colocar no topo, junto com $socials (linha ~56)
$genres = [
    'Pop',
    'Rock',
    'Hip-Hop / Rap',
    'R&B / Soul',
    'Afrobeats',
    'Semba',
    'Kizomba',
    'Kuduro',
    'Funaná',
    'Electrónica',
    'Jazz',
    'Gospel',
    'Reggae',
    'Funk',
    'Folk',
    'Metal',
    'Alternativo',
    'Country',
    'Blues',
    'Latin',
    'Amapiano',
    'Dancehall',
    'Instrumental',
    'Outros'
];

$socials = [
    ['name' => 'spotify_url',   'label' => 'Spotify',      'icon' => 'bi-spotify',      'color' => '#1db954', 'ph' => 'https://open.spotify.com/artist/...'],
    ['name' => 'youtube_url',   'label' => 'YouTube',      'icon' => 'bi-youtube',      'color' => '#ff0000', 'ph' => 'https://youtube.com/@...'],
    ['name' => 'instagram_url', 'label' => 'Instagram',    'icon' => 'bi-instagram',    'color' => '#e1306c', 'ph' => 'https://instagram.com/...'],
    ['name' => 'tiktok_url',    'label' => 'TikTok',       'icon' => 'bi-tiktok',       'color' => '#010101', 'ph' => 'https://tiktok.com/@...'],
    ['name' => 'facebook_url',  'label' => 'Facebook',     'icon' => 'bi-facebook',     'color' => '#1877f2', 'ph' => 'https://facebook.com/...'],
    ['name' => 'website_url',   'label' => 'Apple Music / Site', 'icon' => 'bi-apple', 'color' => '#fc3c44', 'ph' => 'https://music.apple.com/...'],
];

// ── Mensagens de retorno ──────────────────────
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';

// Dados de sessão e segurança
$ls = getDB()->prepare('SELECT last_login_at, last_login_ip FROM _users_security WHERE id_users = ?');
$ls->execute([$id_users]);
$sec = $ls->fetch();
$days_inactive = 0;
if ($sec && $sec['last_login_at']) {
    $days_inactive = (int)floor((time() - strtotime($sec['last_login_at'])) / 86400);
}

// Sessão activa actual
$sess_stmt = getDB()->prepare("
    SELECT ip_address, user_agent, country, city, creat_session, last_activity
    FROM _users_sessions
    WHERE id_users = ? AND is_active = 1
    ORDER BY last_activity DESC LIMIT 1
");
$sess_stmt->execute([$id_users]);
$current_session = $sess_stmt->fetch();

// Calcular tempo de sessão activa
$session_duration_str = '—';
if ($current_session && $current_session['creat_session']) {
    $secs = time() - strtotime($current_session['creat_session']);
    if ($secs < 60) $session_duration_str = $secs . 's';
    elseif ($secs < 3600) $session_duration_str = floor($secs / 60) . 'min';
    elseif ($secs < 86400) $session_duration_str = floor($secs / 3600) . 'h ' . floor(($secs % 3600) / 60) . 'min';
    else $session_duration_str = floor($secs / 86400) . 'd ' . floor(($secs % 86400) / 3600) . 'h';
}

// Conta desde quando
$member_since = $user['creat_user'] ? date('d/m/Y', strtotime($user['creat_user'])) : '—';
$last_login_str = ($sec && $sec['last_login_at'])
    ? date('d/m/Y H:i', strtotime($sec['last_login_at']))
    : '—';

// Browser simplificado a partir do user_agent
$ua_raw    = $current_session['user_agent'] ?? '';
$browser   = 'Navegador desconhecido';
if (str_contains($ua_raw, 'Edg'))     $browser = 'Microsoft Edge';
elseif (str_contains($ua_raw, 'Chrome'))  $browser = 'Google Chrome';
elseif (str_contains($ua_raw, 'Firefox')) $browser = 'Mozilla Firefox';
elseif (str_contains($ua_raw, 'Safari'))  $browser = 'Safari';
elseif (str_contains($ua_raw, 'Opera'))   $browser = 'Opera';

$sess_location = trim(($current_session['city'] ?? '') . ', ' . ($current_session['country'] ?? ''), ', ');
if (!$sess_location) $sess_location = 'Localização desconhecida';
$sess_ip = $current_session['ip_address'] ?? ($sec['last_login_ip'] ?? '—');

$csrf = htmlspecialchars($_SESSION['csrf_token']);
$photo_base = rtrim(APP_URL, '/') . '/assets/comprovantes/uploads/artists/';
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title><?php echo $edit_artist ? 'Editar Artista' : 'Criar Artista'; ?> — <?php echo APP_NAME; ?></title>
    <style>
        :root {
            --wasom: #FF0089;
            --wasom-dark: #cc006d;
        }

        /* ── Photo upload ── */
        .artist-photo-wrap {
            position: relative;
            width: 130px;
            height: 130px;
            margin: 0 auto 12px;
        }

        .artist-photo-circle {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--wasom);
            box-shadow: 0 4px 16px rgba(255, 0, 137, .25);
            display: block;
            background: #f1f3f5;
            transition: filter .2s;
        }

        .artist-photo-circle.placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #ccc;
        }

        .artist-photo-overlay {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--wasom);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .3);
            transition: transform .2s, background .2s;
            border: 2px solid #fff;
        }

        .artist-photo-overlay:hover {
            transform: scale(1.1);
            background: var(--wasom-dark);
        }

        /* ── Artist card in list ── */
        .artist-card {
            border-radius: 16px;
            overflow: visible;
            position: relative;
            border: 1px solid rgba(0, 0, 0, .08);
            box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
            transition: transform .2s, box-shadow .2s;
        }

        .artist-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
        }

        .artist-card-cover {
            height: 60px;
            background: linear-gradient(135deg, #FF0089, #FF4D4D);
            position: relative;
            border-radius: 16px 16px 0 0;
            overflow: hidden;
        }

        .artist-card-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 3px solid #fff;
            object-fit: cover;
            position: absolute;
            bottom: -35px;
            left: 50%;
            transform: translateX(-50%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, .2);
            background: #eee;
        }

        .artist-card-body {
            padding: 44px 16px 16px;
            text-align: center;
        }

        .artist-card-name {
            font-weight: 700;
            font-size: .95rem;
            margin-bottom: 2px;
            color: #888;
        }

        .artist-card-real {
            font-size: .78rem;
            color: #888;
            margin-bottom: 8px;
        }

        .artist-card-genre {
            font-size: .72rem;
        }

        .artist-card-actions {
            display: flex;
            gap: 6px;
            justify-content: center;
            margin-top: 12px;
        }

        .artist-status-dot {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .dot-active {
            background: #28a745;
        }

        .dot-processing {
            background: #ffc107;
        }

        .dot-inactive {
            background: #6c757d;
        }

        .dot-blocked {
            background: #dc3545;
        }

        /* ── Social links ── */
        .social-input-group .input-group-text {
            width: 42px;
            justify-content: center;
        }

        /* ── Plan limit banner ── */
        .plan-limit-bar {
            background: rgba(255, 0, 137, .08);
            border: 1px solid rgba(255, 0, 137, .2);
            border-radius: 12px;
            padding: 12px 16px;
        }

        /* ── Section divider ── */
        .section-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0 16px;
        }

        .section-divider span {
            font-weight: 700;
            font-size: .85rem;
            color: var(--wasom);
            white-space: nowrap;
        }

        .section-divider::before,
        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(0, 0, 0, .08);
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
                ['label' => 'Verificar agora', 'url' => 'user/profile#perfil'],
                true,
                'banner-email'
            ); ?>
        <?php endif; ?>

        <?php if ($plan && !$plan_paid): ?>
            <?php wuAlert(
                'warning',
                'bi-clock-history',
                '<strong>Pagamento pendente — ' . htmlspecialchars($plan['name_plan']) . '.</strong> O plano foi seleccionado mas o pagamento ainda não foi confirmado. Os teus lançamentos estão pausados até confirmação.',
                ['label' => 'Finalizar pagamento', 'url' => 'payment/pay'],
                true,
                'banner-plan-pending'
            ); ?>
        <?php elseif (!$plan): ?>
            <?php wuAlert(
                'danger',
                'bi-credit-card-fill',
                '<strong>Sem plano activo.</strong> Escolhe um plano para começar a distribuir a tua música para +150 plataformas.',
                ['label' => 'Ver planos', 'url' => 'all-plans'],
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
                ['label' => 'Criar agora', 'url' => 'artists/add-artist'],
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
                ['label' => 'Registar agora', 'url' => 'finances/withdraw'],
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
                ['label' => 'Corrigir agora', 'url' => 'finances/withdraw'],
                true,
                'banner-account-rejected'
            );
            ?>
        <?php endif; ?>
        <!-- Header de criação de artista -->
        <div class="page-header">
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <div class="page-header-compact">
                        <h1>
                            <i class="bi bi-person-plus-fill me-2"></i>
                            <?php echo $edit_artist ? 'Editar Artista' : 'Gestão de Artistas'; ?>
                        </h1>
                        <p class="lead">
                            <?php if ($edit_artist): ?>
                                Actualiza as informações do perfil artístico.
                            <?php else: ?>
                                Cria e gere os perfis artísticos da tua conta.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-back shadow-sm" onclick="window.location='artists-list'">
                        <i class="bi bi-arrow-left me-1"></i> Lista de Artistas
                    </button>
                    <?php if (!$edit_artist && $can_add): ?>
                        <button class="btn btn-sm" style="background:var(--wasom);color:#fff" data-bs-toggle="modal"
                            data-bs-target="#createArtistModal">
                            <i class="bi bi-plus me-1"></i>Novo Artista
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <style>
                .page-header::before {
                    content: '\F4E6';
                    /* bi-person-plus-fill */
                }
            </style>
        </div>

        <!-- Plan limit bar -->
        <div class="plan-limit-bar mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-person-badge" style="color:var(--wasom);font-size:1.2rem"></i>
                <div>
                    <span class="fw-semibold small">Artistas no teu plano</span>
                    <span class="text-muted small ms-2">
                        <?php echo $plan ? htmlspecialchars($plan['name_plan']) : 'Sem plano'; ?>
                    </span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="progress" style="width:120px;height:8px;border-radius:8px">
                    <?php $pct = $max_artists > 0 ? min(100, round($artist_count / $max_artists * 100)) : 0; ?>
                    <div class="progress-bar" style="width:<?php echo $pct; ?>%;background:var(--wasom)"></div>
                </div>
                <span class="fw-bold small" style="color:var(--wasom)"><?php echo $artist_count; ?> /
                    <?php echo $max_artists; ?></span>
                <?php if (!$can_add): ?>
                    <span class="badge bg-warning text-dark"><i class="bi bi-lock-fill me-1"></i>Limite
                        atingido</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($success === 'created'): ?>
            <div class="alert alert-success alert-dismissible d-flex gap-2 mb-4">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <div><strong>Artista criado com sucesso!</strong> Um email de boas-vindas foi enviado.</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($success === 'updated'): ?>
            <div class="alert alert-success alert-dismissible d-flex gap-2 mb-4">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <div><strong>Artista actualizado com sucesso!</strong></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($success === 'deleted'): ?>
            <div class="alert alert-info alert-dismissible d-flex gap-2 mb-4">
                <i class="bi bi-trash-fill flex-shrink-0"></i>
                <div><strong>Artista eliminado.</strong></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible d-flex gap-2 mb-4">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ══ EDIT FORM (only when ?edit=) ══ -->
        <?php if ($edit_artist): ?>
            <div class="card p-4 mb-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-pencil-square me-2" style="color:var(--wasom)"></i>Editar:
                    <?php echo htmlspecialchars($edit_artist['stage_name']); ?></h5>
                <form id="edit-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_artist" />
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>" />
                    <input type="hidden" name="id_artist" value="<?php echo $edit_artist['id_artist']; ?>" />

                    <!-- Photo -->
                    <div class="text-center mb-4">
                        <div class="artist-photo-wrap d-inline-block" style="width:130px;height:130px;position:relative">
                            <?php $ep = $edit_artist['photo_artist'] ? $photo_base . $edit_artist['photo_artist'] : null; ?>
                            <?php if ($ep): ?>
                                <img id="edit-photo-preview" src="<?php echo htmlspecialchars($ep); ?>"
                                    class="artist-photo-circle" alt="Foto" />
                            <?php else: ?>
                                <div id="edit-photo-placeholder" class="artist-photo-circle placeholder"><i
                                        class="bi bi-person"></i></div>
                                <img id="edit-photo-preview" src="" class="artist-photo-circle d-none" alt="Foto" />
                            <?php endif; ?>
                            <div class="artist-photo-overlay" onclick="document.getElementById('edit-photo-input').click()">
                                <i class="bi bi-camera-fill" style="font-size:.9rem"></i>
                            </div>
                        </div>
                        <input type="file" id="edit-photo-input" name="photo" accept="image/jpeg,image/png,image/webp"
                            class="d-none" />
                        <div class="text-muted" style="font-size:.72rem;margin-top:6px">JPG/PNG/WebP · Máx. 5MB
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nome Artístico <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="stage_name" maxlength="100"
                                value="<?php echo htmlspecialchars($edit_artist['stage_name']); ?>" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nome Real</label>
                            <input type="text" class="form-control" name="real_name" maxlength="150"
                                value="<?php echo htmlspecialchars($edit_artist['real_name'] ?? ''); ?>" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Email do Artista <span
                                    class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="artist_email" maxlength="255"
                                value="<?php echo htmlspecialchars($edit_artist['artist_email'] ?? ''); ?>"
                                placeholder="email@artista.com" required />
                            <div class="form-text">Usado para notificações enviadas ao artista.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Género Musical</label>
                            <select class="form-select" name="genre_main">
                                <option value="">Selecionar...</option>
                                <?php
                                foreach ($genres as $g):
                                    $val = strtolower(str_replace([' ', '/', '-'], ['_', '', ''], $g));
                                    $sel = ($edit_artist['genre_main'] === $val) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $val; ?>" <?php echo $sel; ?>><?php echo $g; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">País</label>
                            <input type="text" class="form-control" name="country" maxlength="60"
                                value="<?php echo htmlspecialchars($edit_artist['country'] ?? ''); ?>"
                                placeholder="ex: Angola" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Cidade</label>
                            <input type="text" class="form-control" name="city" maxlength="60"
                                value="<?php echo htmlspecialchars($edit_artist['city'] ?? ''); ?>"
                                placeholder="ex: Luanda" />
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Bio</label>
                            <textarea class="form-control" name="bio" rows="3" maxlength="1000"
                                placeholder="Breve descrição do artista..."><?php echo htmlspecialchars($edit_artist['bio'] ?? ''); ?></textarea>
                        </div>

                        <!-- Social links -->
                        <div class="col-12">
                            <div class="section-divider"><span><i class="bi bi-share me-1"></i>Redes Sociais &
                                    Links</span></div>
                        </div>
                        <?php
                        foreach ($socials as $s):
                        ?>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small"><?php echo $s['label']; ?></label>
                                <div class="input-group social-input-group">
                                    <span class="input-group-text"
                                        style="background:<?php echo $s['color']; ?>;border-color:<?php echo $s['color']; ?>">
                                        <i class="bi <?php echo $s['icon']; ?> text-white"></i>
                                    </span>
                                    <input type="url" class="form-control" name="<?php echo $s['name']; ?>"
                                        value="<?php echo htmlspecialchars($edit_artist[$s['name']] ?? ''); ?>"
                                        placeholder="<?php echo $s['ph']; ?>" />
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Password confirm -->
                        <div class="col-12">
                            <div class="section-divider"><span><i class="bi bi-shield-lock me-1"></i>Confirmação de
                                    Segurança</span></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">A tua senha <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password_confirm" id="edit-password"
                                    placeholder="Confirma com a tua senha actual" required />
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePwd('edit-password',this)"><i class="bi bi-eye"></i></button>
                            </div>
                            <div class="form-text">Por segurança, confirma a tua senha para guardar alterações.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn btn-sm px-4" style="background:var(--wasom);color:#fff"
                            onclick="submitEdit()">
                            <i class="bi bi-check-lg me-1"></i>Guardar Alterações
                        </button>
                        <a href="add-artist" class="btn btn-outline-secondary btn-sm px-4">Cancelar</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- ══ ARTISTS GRID ══ -->
        <?php if (!empty($artists) && !$edit_artist): ?>
            <div class="section-divider mb-3"><span><i class="bi bi-people me-1"></i>Os Teus Artistas
                    (<?php echo $artist_count; ?>)</span></div>
            <div class="row g-3 mb-4">
                <?php foreach ($artists as $a): ?>
                    <?php
                    $photo_url = $a['photo_artist'] ? $photo_base . htmlspecialchars($a['photo_artist']) : null;
                    $dot_class = 'dot-' . ($a['status_artist'] ?? 'inactive');
                    $status_labels = ['active' => 'Activo', 'processing' => 'Em análise', 'inactive' => 'Inactivo', 'blocked' => 'Bloqueado'];
                    $status_label = $status_labels[$a['status_artist']] ?? 'Desconhecido';
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-6">
                        <div class="artist-card">
                            <div class="artist-card-cover">
                                <span class="artist-status-dot <?php echo $dot_class; ?>"
                                    title="<?php echo $status_label; ?>"></span>
                            </div>
                            <?php if ($photo_url): ?>
                                <img src="<?php echo $photo_url; ?>" class="artist-card-avatar"
                                    alt="<?php echo htmlspecialchars($a['stage_name']); ?>" />
                            <?php else: ?>
                                <div class="artist-card-avatar d-flex align-items-center justify-content-center"
                                    style="background:#f1f3f5">
                                    <i class="bi bi-person" style="font-size:1.5rem;color:#ccc"></i>
                                </div>
                            <?php endif; ?>
                            <div class="artist-card-body">
                                <div class="artist-card-name text-truncate">
                                    <?php echo htmlspecialchars($a['stage_name']); ?></div>
                                <?php if ($a['real_name']): ?>
                                    <div class="artist-card-real text-truncate">
                                        <?php echo htmlspecialchars($a['real_name']); ?></div>
                                <?php endif; ?>
                                <?php if ($a['genre_main']): ?>
                                    <span
                                        class="badge bg-secondary artist-card-genre"><?php echo htmlspecialchars($a['genre_main']); ?></span>
                                <?php endif; ?>
                                <div class="artist-card-actions">
                                    <a href="add-artist?edit=<?php echo $a['id_artist']; ?>"
                                        class="btn btn-outline-secondary btn-sm" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-outline-danger btn-sm" title="Eliminar"
                                        onclick="confirmDelete(<?php echo $a['id_artist']; ?>, '<?php echo htmlspecialchars(addslashes($a['stage_name'])); ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <a href="artists-list?id=<?php echo $a['id_artist']; ?>"
                                        class="btn btn-outline-secondary btn-sm" title="Ver perfil">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (!$edit_artist): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-person-plus" style="font-size:3.5rem;opacity:.3;display:block;margin-bottom:12px"></i>
                <h5>Ainda não tens artistas</h5>
                <p class="small mb-3">Cria o primeiro perfil artístico para começar a distribuir música.</p>
                <?php if ($can_add): ?>
                    <button class="btn btn-sm px-4" style="background:var(--wasom);color:#fff" data-bs-toggle="modal"
                        data-bs-target="#createArtistModal">
                        <i class="bi bi-plus me-1"></i>Criar primeiro artista
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div><!-- /container -->


    <!-- ════════════════════════════════════
     MODAL — Criar Artista
════════════════════════════════════ -->
    <div class="modal fade" id="createArtistModal" data-bs-backdrop="<?php echo $can_add ? 'true' : 'static'; ?>"
        tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header pb-2" style="background:linear-gradient(135deg,#FF0089,#FF4D4D);color:#fff">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-plus fs-4"></i>
                        <div>
                            <h5 class="modal-title mb-0">Criar Perfil de Artista</h5>
                            <small style="opacity:.8">Preenche os dados do artista — podes completar
                                depois.</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <?php if (!$can_add): ?>
                        <div class="alert alert-warning text-center">
                            <i class="bi bi-lock-fill me-2"></i>
                            <strong>Limite atingido.</strong> O teu plano
                            <?php echo $plan ? htmlspecialchars($plan['name_plan']) : ''; ?>
                            permite <?php echo $max_artists; ?> artista(s).
                            <a href="../all-plans" class="alert-link ms-1">Fazer upgrade →</a>
                        </div>
                    <?php else: ?>

                        <!-- Photo upload -->
                        <div class="text-center mb-4">
                            <div style="position:relative;display:inline-block">
                                <div id="create-photo-placeholder" class="artist-photo-circle placeholder"
                                    style="cursor:pointer" onclick="document.getElementById('create-photo-input').click()">
                                    <i class="bi bi-person"></i>
                                </div>
                                <img id="create-photo-preview" src="" alt="Foto" class="artist-photo-circle d-none"
                                    style="cursor:pointer"
                                    onclick="document.getElementById('create-photo-input').click()" />
                                <div class="artist-photo-overlay"
                                    onclick="document.getElementById('create-photo-input').click()">
                                    <i class="bi bi-camera-fill" style="font-size:.9rem"></i>
                                </div>
                            </div>
                            <input type="file" id="create-photo-input" accept="image/jpeg,image/png,image/webp"
                                class="d-none" />
                            <div class="text-muted mt-1" style="font-size:.72rem">Foto de perfil do artista
                                (opcional)</div>
                        </div>

                        <form id="create-form">
                            <input type="hidden" name="action" value="create_artist" />
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>" />

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Nome Artístico <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="c-stage-name" name="stage_name"
                                        maxlength="100" placeholder="ex: Ghostface, DJ KP, Ana Lima" required />
                                    <div class="form-text">Como aparecerá em todas as plataformas.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Nome Real</label>
                                    <input type="text" class="form-control" name="real_name" maxlength="150"
                                        placeholder="Nome legal (opcional)" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Email do Artista <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="artist_email"
                                        placeholder="email@artista.com" required />
                                    <div class="form-text">Um email de boas-vindas será enviado para este endereço.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Género Musical</label>
                                    <select class="form-select" name="genre_main">
                                        <option value="">Selecionar...</option>
                                        <?php foreach ($genres as $g): $val = strtolower(str_replace([' ', '/', '-'], ['_', '', ''], $g)); ?>
                                            <option value="<?php echo $val; ?>"><?php echo $g; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">País</label>
                                    <input type="text" class="form-control" name="country" maxlength="60"
                                        placeholder="ex: Angola" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Cidade</label>
                                    <input type="text" class="form-control" name="city" maxlength="60"
                                        placeholder="ex: Luanda" />
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Bio</label>
                                    <textarea class="form-control" name="bio" rows="2" maxlength="1000"
                                        placeholder="Breve descrição do artista (opcional)..."></textarea>
                                </div>

                                <!-- Social links -->
                                <div class="col-12">
                                    <div class="section-divider"><span><i class="bi bi-share me-1"></i>Links &
                                            Redes</span></div>
                                </div>
                                <?php foreach ($socials as $s): ?>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small"><?php echo $s['label']; ?></label>
                                        <div class="input-group social-input-group">
                                            <span class="input-group-text"
                                                style="background:<?php echo $s['color']; ?>;border-color:<?php echo $s['color']; ?>">
                                                <i class="bi <?php echo $s['icon']; ?> text-white"></i>
                                            </span>
                                            <input type="url" class="form-control" name="<?php echo $s['name']; ?>"
                                                placeholder="<?php echo $s['ph']; ?>" />
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </form>
                    <?php endif; ?>
                    <div id="create-feedback" class="mt-3 d-none"></div>
                </div>
                <?php if ($can_add): ?>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-wasomupfy" style="background:var(--wasom);color:#fff"
                            id="btn-create-artist" onclick="submitCreate()">
                            <span id="create-btn-text"><i class="bi bi-check me-1"></i>Criar Artista</span>
                            <span id="create-btn-load" class="d-none"><span
                                    class="spinner-border spinner-border-sm me-1"></span>A criar...</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════
     MODAL — Eliminar Artista
════════════════════════════════════ -->
    <div class="modal fade" id="deleteModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:40px;height:40px;background:rgba(220,53,69,.1)">
                            <i class="bi bi-trash text-danger"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0">Eliminar Artista</h5>
                            <small class="text-muted" id="del-artist-name"></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="alert alert-danger small d-flex gap-2">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                        <div>Esta acção é <strong>irreversível</strong>. O perfil do artista será
                            permanentemente eliminado.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Notificar o artista por email?</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="del-notify" id="del-notify-yes"
                                    value="1" />
                                <label class="form-check-label small" for="del-notify-yes">
                                    <i class="bi bi-bell me-1"></i>Sim, enviar email
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="del-notify" id="del-notify-no"
                                    value="0" checked />
                                <label class="form-check-label small" for="del-notify-no">
                                    <i class="bi bi-bell-slash me-1"></i>Não, silencioso
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">A tua senha <span
                                class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="del-password"
                                placeholder="Confirma com a tua senha" />
                            <button class="btn btn-outline-secondary" type="button"
                                onclick="togglePwd('del-password',this)"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <input type="hidden" id="del-artist-id" />
                    <div id="del-feedback" class="d-none"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger btn-sm py-2" id="btn-confirm-delete"
                        onclick="submitDelete()">
                        <span id="del-btn-text"><i class="bi bi-trash me-1"></i>Eliminar</span>
                        <span id="del-btn-load" class="d-none"><span
                                class="spinner-border spinner-border-sm me-1"></span>A eliminar...</span>
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
        const CSRF = '<?php echo $csrf; ?>';
        const BASE = '<?php echo (APP_URL. '/' . APP_URL_PANEL); ?>';
        const PROCESS = BASE + '/artists/add_artist_process';

        toastr.options = {
            progressBar: true,
            closeButton: true,
            positionClass: 'toast-top-right',
            timeOut: 4000
        };

        // ── Shared helpers ──────────────────────────
        function togglePwd(id, btn) {
            const inp = document.getElementById(id);
            const isPass = inp.type === 'password';
            inp.type = isPass ? 'text' : 'password';
            btn.querySelector('i').className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
        }

        function setLoading(btnTextId, btnLoadId, btnEl, loading) {
            document.getElementById(btnTextId).classList.toggle('d-none', loading);
            document.getElementById(btnLoadId).classList.toggle('d-none', !loading);
            if (btnEl) btnEl.disabled = loading;
        }

        // ── Photo preview for CREATE modal ─────────
        document.getElementById('create-photo-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                toastr.error('Imagem demasiado grande (máx. 5MB).');
                return;
            }
            const reader = new FileReader();
            reader.onload = ev => {
                document.getElementById('create-photo-placeholder').classList.add('d-none');
                const img = document.getElementById('create-photo-preview');
                img.src = ev.target.result;
                img.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });

        // ── Photo preview for EDIT form ─────────────
        document.getElementById('edit-photo-input')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                toastr.error('Imagem demasiado grande (máx. 5MB).');
                return;
            }
            const reader = new FileReader();
            reader.onload = ev => {
                const ph = document.getElementById('edit-photo-placeholder');
                if (ph) ph.classList.add('d-none');
                const img = document.getElementById('edit-photo-preview');
                img.src = ev.target.result;
                img.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });

        // ── CREATE ARTIST ───────────────────────────
        async function submitCreate() {
            const stageName = document.getElementById('c-stage-name').value.trim();
            if (!stageName) {
                toastr.error('O nome artístico é obrigatório.');
                return;
            }

            const emailField = document.querySelector('#create-form [name="artist_email"]');
            if (!emailField.value.trim()) {
                toastr.error('O email do artista é obrigatório.');
                return;
            }

            const btn = document.getElementById('btn-create-artist');
            setLoading('create-btn-text', 'create-btn-load', btn, true);

            const fd = new FormData(document.getElementById('create-form'));
            const photoFile = document.getElementById('create-photo-input').files[0];
            if (photoFile) fd.set('photo', photoFile);
            fd.set('csrf_token', CSRF);

            try {
                const res = await fetch(PROCESS, {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('createArtistModal')).hide();
                    await Swal.fire({
                        icon: 'success',
                        iconColor: '#FF0089',
                        title: 'Artista criado!',
                        html: `<p>O perfil de <strong>${stageName}</strong> foi criado com sucesso.</p>
                       <p class="text-muted small">Um email de boas-vindas foi enviado para o artista.</p>`,
                        confirmButtonText: 'Ok',
                        confirmButtonColor: '#FF0089'
                    });
                    window.location.reload();
                } else {
                    const fb = document.getElementById('create-feedback');
                    fb.innerHTML =
                        `<div class="alert alert-danger small py-2">${data.message || 'Erro ao criar artista.'}</div>`;
                    fb.classList.remove('d-none');
                }
            } catch {
                toastr.error('Erro de ligação. Tenta novamente.');
            } finally {
                setLoading('create-btn-text', 'create-btn-load', btn, false);
            }
        }

        // ── EDIT ARTIST ─────────────────────────────
        async function submitEdit() {
            const pwd = document.getElementById('edit-password').value.trim();
            if (!pwd) {
                toastr.error('Confirma a tua senha para guardar.');
                return;
            }

            const fd = new FormData(document.getElementById('edit-form'));
            const photoFile = document.getElementById('edit-photo-input').files[0];
            if (photoFile) fd.set('photo', photoFile);
            fd.set('csrf_token', CSRF);

            const btn = document.querySelector('#edit-form button[onclick="submitEdit()"]');
            const origHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>A guardar...';
            btn.disabled = true;

            try {
                const res = await fetch(PROCESS, {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.ok) {
                    toastr.success('Artista actualizado com sucesso!');
                    setTimeout(() => window.location.href = 'add-artist?success=updated', 1000);
                } else {
                    toastr.error(data.message || 'Erro ao actualizar.');
                    btn.innerHTML = origHtml;
                    btn.disabled = false;
                }
            } catch {
                toastr.error('Erro de ligação. Tenta novamente.');
                btn.innerHTML = origHtml;
                btn.disabled = false;
            }
        }

        // ── DELETE ARTIST ────────────────────────────
        function confirmDelete(id, name) {
            document.getElementById('del-artist-id').value = id;
            document.getElementById('del-artist-name').textContent = name;
            document.getElementById('del-password').value = '';
            document.getElementById('del-feedback').classList.add('d-none');
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        async function submitDelete() {
            const id = document.getElementById('del-artist-id').value;
            const pwd = document.getElementById('del-password').value.trim();
            const notify = document.querySelector('input[name="del-notify"]:checked').value;
            const fb = document.getElementById('del-feedback');

            if (!pwd) {
                fb.innerHTML = '<div class="alert alert-danger small py-2">A senha é obrigatória.</div>';
                fb.classList.remove('d-none');
                return;
            }

            const btn = document.getElementById('btn-confirm-delete');
            setLoading('del-btn-text', 'del-btn-load', btn, true);

            const fd = new FormData();
            fd.append('action', 'delete_artist');
            fd.append('csrf_token', CSRF);
            fd.append('id_artist', id);
            fd.append('password_confirm', pwd);
            fd.append('notify_artist', notify);

            try {
                const res = await fetch(PROCESS, {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                    await Swal.fire({
                        icon: 'success',
                        iconColor: '#FF0089',
                        title: 'Artista eliminado',
                        text: notify === '1' ? 'O artista foi notificado por email.' : 'Eliminado silenciosamente.',
                        confirmButtonColor: '#FF0089',
                        timer: 2500,
                        showConfirmButton: false
                    });
                    window.location.href = 'add-artist?success=deleted';
                } else {
                    fb.innerHTML =
                        `<div class="alert alert-danger small py-2">${data.message || 'Erro ao eliminar.'}</div>`;
                    fb.classList.remove('d-none');
                }
            } catch {
                fb.innerHTML = '<div class="alert alert-danger small py-2">Erro de ligação.</div>';
                fb.classList.remove('d-none');
            } finally {
                setLoading('del-btn-text', 'del-btn-load', btn, false);
            }
        }

        // Auto-open create modal if just loaded and no artists and can_add
        <?php if ($can_add && $artist_count === 0 && !$edit_artist && !$success && !$error): ?>
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => new bootstrap.Modal(document.getElementById('createArtistModal')).show(), 400);
            });
        <?php endif; ?>
    </script>
</body>

</html>