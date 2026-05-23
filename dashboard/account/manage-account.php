<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0.1.1 — Gestão de Conta
// Arquivo: dashboard/account/manage-account.php
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

// ── Security record ───────────────────────────
$sec = $db->prepare("SELECT * FROM _users_security WHERE id_users = ?");
$sec->execute([$id_users]);
$security = $sec->fetch() ?: [];

// ── Plan ──────────────────────────────────────
$plan = null;
if ($user['plan_selected']) {
    $ps = $db->prepare("SELECT * FROM _plans WHERE id_plan = ?");
    $ps->execute([$user['plan_selected']]);
    $plan = $ps->fetch();
}
$plan_name = $plan ? htmlspecialchars($plan['name_plan']) : 'Sem plano';
$plan_slug = $plan ? $plan['slug_plan'] : '';

// ── Limite de colaboradores do plano ──────────
$max_collaborators = 1; // padrão
if ($plan) {
    $max_collaborators = (int)($plan['max_collaborators'] ?? 1);
}
// Se o plano não tiver max_collaborators definido, mas soubermos pelo slug
if (!$plan || !isset($plan['max_collaborators'])) {
    if ($plan_slug === 'label') $max_collaborators = 5;
    else $max_collaborators = 1;
}

// ── Collaborators (sem limite) ────────────────
$collab_st = $db->prepare("
    SELECT *, TIMESTAMPDIFF(MINUTE, last_seen_at, NOW()) AS mins_since_seen
    FROM _collaborators WHERE id_users = ? ORDER BY creat_collab DESC
");
$collab_st->execute([$id_users]);
$collaborators = $collab_st->fetchAll(PDO::FETCH_ASSOC);
$collab_count  = count($collaborators);

// ── Artist / Band name info ───────────────────
$has_artist_profile = !empty($user['name_artist_band']);

$csrf = htmlspecialchars($_SESSION['csrf_token']);
$process_url = rtrim(APP_URL, '/') . '/dashboard/account/manage_account_process';

// Role labels / colours
$role_meta = [
    'admin'   => ['label' => 'Administrador', 'bg' => 'rgba(220,53,69,.12)',  'color' => '#dc3545', 'icon' => 'bi-shield-fill'],
    'editor'  => ['label' => 'Editor',        'bg' => 'rgba(255,0,137,.1)',   'color' => '#FF0089', 'icon' => 'bi-pencil-fill'],
    'analyst' => ['label' => 'Analista',      'bg' => 'rgba(13,110,253,.1)', 'color' => '#0d6efd', 'icon' => 'bi-bar-chart-fill'],
    'support' => ['label' => 'Suporte',       'bg' => 'rgba(25,135,84,.1)',  'color' => '#198754', 'icon' => 'bi-headset'],
];
$status_meta = [
    'active'   => ['label' => 'Activo',     'bg' => 'rgba(25,135,84,.12)',   'color' => '#198754'],
    'pending'  => ['label' => 'Pendente',   'bg' => 'rgba(255,193,7,.15)',   'color' => '#856404'],
    'blocked'  => ['label' => 'Bloqueado',  'bg' => 'rgba(220,53,69,.12)',   'color' => '#dc3545'],
    'inactive' => ['label' => 'Inactivo',   'bg' => 'rgba(108,117,125,.12)', 'color' => '#6c757d'],
];

$collabs_json = json_encode($collaborators, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
$photo_base   = rtrim(APP_URL, '/') . '/assets/comprovantes/uploads/user/';
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title>Gestão de Conta — <?php echo APP_NAME; ?></title>

    <style>
        :root {
            --wasom: #FF0089;
            --wasom-dark: #cc006d;
        }

        /* ── Sidebar layout ── */
        .manage-layout {
            display: grid;
            grid-template-columns: 230px 1fr;
            gap: 24px;
            align-items: start;
        }

        @media(max-width:768px) {
            .manage-layout {
                grid-template-columns: 1fr;
            }

            .manage-sidebar {
                display: none;
            }
        }

        .manage-sidebar {
            position: sticky;
            top: 80px;
        }

        .sidebar-nav .nav-link {
            border-radius: 12px;
            padding: 10px 14px;
            font-size: .875rem;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all .2s;
            margin-bottom: 2px;
            font-weight: 500;
        }

        .sidebar-nav .nav-link:hover {
            background: rgba(255, 0, 137, .08);
            color: var(--wasom);
        }

        .sidebar-nav .nav-link.active {
            background: rgba(255, 0, 137, .12);
            color: var(--wasom);
            font-weight: 700;
        }

        .sidebar-nav .nav-link i {
            width: 20px;
            text-align: center;
        }

        /* ── Section ── */
        .manage-section {
            display: none;
        }

        .manage-section.active {
            display: block;
        }

        /* ── Cards ── */
        .section-card {
            border-radius: 18px;
            border: 1.5px solid rgba(0, 0, 0, .07);
            background: var(--card-bg, #fff);
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .04);
        }

        .section-title {
            font-size: 1rem;
            font-weight: 800;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--wasom);
        }

        /* ── Artist profile card ── */
        .artist-profile-hero {
            border-radius: 16px;
            overflow: hidden;
            background: linear-gradient(135deg, #FF0089 0%, #FF4D4D 100%);
            padding: 28px 24px;
            margin-bottom: 20px;
            position: relative;
        }

        .artist-profile-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .artist-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, .2);
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, .3);
            margin-bottom: 12px;
        }

        .no-profile-ph {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 12px;
            border: 2px solid rgba(255, 255, 255, .3);
        }

        .profile-field-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 0, 0, .05);
        }

        .profile-field-row:last-child {
            border-bottom: none;
        }

        .profile-field-label {
            font-size: .72rem;
            color: #999;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .profile-field-value {
            font-weight: 600;
            font-size: .9rem;
        }

        /* ── Plan limit bar ── */
        .plan-limit-bar {
            background: rgba(255, 0, 137, .06);
            border: 1.5px solid rgba(255, 0, 137, .15);
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 20px;
        }

        /* ── Collaborator table ── */
        .collab-row {
            display: grid;
            grid-template-columns: 44px 1fr auto auto auto;
            gap: 12px;
            align-items: center;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1.5px solid rgba(0, 0, 0, .06);
            background: var(--card-bg, #fff);
            transition: border-color .2s, box-shadow .2s;
            margin-bottom: 10px;
        }

        .collab-row:hover {
            border-color: rgba(255, 0, 137, .2);
            box-shadow: 0 2px 12px rgba(255, 0, 137, .08);
        }

        .collab-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            background: #f1f3f5;
            flex-shrink: 0;
        }

        .collab-avatar-ph {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 0, 137, .08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: var(--wasom);
        }

        .online-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 2px solid #fff;
            display: inline-block;
            flex-shrink: 0;
        }

        .dot-online {
            background: #198754;
        }

        .dot-offline {
            background: #adb5bd;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .68rem;
            font-weight: 700;
        }

        /* ── Mobile tabs ── */
        .mobile-tab.active {
            background: var(--wasom) !important;
            color: #fff !important;
            border-color: var(--wasom) !important;
        }

        /* ── Upgrade banner ── */
        .upgrade-banner {
            border-radius: 16px;
            padding: 24px;
            background: linear-gradient(135deg, rgba(255, 0, 137, .08), rgba(255, 77, 77, .06));
            border: 1.5px dashed rgba(255, 0, 137, .3);
            text-align: center;
        }

        /* ── Form focus ── */
        .form-control:focus,
        .form-select:focus {
            border-color: var(--wasom);
            box-shadow: 0 0 0 .2rem rgba(255, 0, 137, .18);
        }

        /* ── Password display ── */
        .pwd-display {
            font-family: monospace;
            font-size: .95rem;
            letter-spacing: 1.5px;
            background: rgba(255, 0, 137, .06);
            border: 1.5px dashed var(--wasom);
            border-radius: 10px;
            padding: 10px 14px;
            word-break: break-all;
            user-select: all;
        }

        /* ── Activity modal items ── */
        .activity-item {
            display: flex;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 0, 0, .05);
            font-size: .83rem;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(255, 0, 137, .08);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--wasom);
            flex-shrink: 0;
            font-size: .8rem;
        }
    </style>
</head>

<body>

    <!-- ═══ NAVBAR ═══ -->
    <?php require_once __DIR__ . '/../include/sidebar.php'; ?>
    <!-- Main Content -->
    <div class="container my-4">

        <!-- Page header -->
        <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1"><i class="bi bi-tools me-2" style="color:var(--wasom)"></i>Gestão de
                    Conta
                </h1>
                <p class="text-muted small mb-0">Gere o teu perfil artístico e a tua equipa de colaboradores.</p>
            </div>
        </div>

        <!-- Mobile tabs -->
        <div class="d-flex gap-2 mb-3 overflow-auto d-md-none pb-1" style="scrollbar-width:none">
            <button class="btn btn-sm btn-outline-secondary flex-shrink-0 mobile-tab active" data-section="artista">
                <i class="bi bi-mic me-1"></i>Perfil Artístico
            </button>
            <button class="btn btn-sm btn-outline-secondary flex-shrink-0 mobile-tab" data-section="equipa">
                <i class="bi bi-people me-1"></i>Equipa
            </button>
        </div>

        <div class="manage-layout">

            <!-- ══ SIDEBAR ══ -->
            <aside class="manage-sidebar d-none d-md-block">
                <div class="section-card p-3 mb-3 text-center">
                    <?php
                    $photo_user = $user['photo_user'] ?? null;
                    $photo_user_url = $photo_user
                        ? rtrim(APP_URL, '/') . '/assets/comprovantes/uploads/users/' . htmlspecialchars($photo_user)
                        : null;
                    $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['second_name'] ?? '', 0, 1));
                    ?>
                    <div
                        style="width:56px;height:56px;border-radius:50%;margin:0 auto 10px;overflow:hidden;border:2px solid rgba(255,0,137,.2)">
                        <?php if ($photo_user_url): ?>
                            <img src="<?php echo $photo_user_url; ?>" alt="<?php echo $first_name; ?>"
                                style="width:100%;height:100%;object-fit:cover" />
                        <?php else: ?>
                            <div
                                style="width:100%;height:100%;background:linear-gradient(135deg,#FF0089,#FF4D4D);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.1rem">
                                <?php echo $initials ?: '🎵'; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="fw-bold small"><?php echo $first_name; ?></div>
                    <div class="text-muted" style="font-size:.72rem">@<?php echo $user_name; ?></div>
                    <div class="mt-2">
                        <span class="chip" style="background:rgba(255,0,137,.1);color:var(--wasom)">
                            <i class="bi bi-star-fill" style="font-size:.55rem"></i><?php echo $plan_name; ?>
                        </span>
                    </div>
                </div>
                <nav class="sidebar-nav">
                    <a href="#" class="nav-link active" data-section="artista">
                        <i class="bi bi-mic"></i>Perfil Artístico
                    </a>
                    <a href="#" class="nav-link" data-section="equipa">
                        <i class="bi bi-people"></i>Equipa
                        <?php if ($collab_count > 0): ?>
                            <span class="badge ms-auto"
                                style="background:var(--wasom);font-size:.6rem"><?php echo $collab_count; ?></span>
                        <?php endif; ?>
                    </a>
                </nav>
            </aside>

            <!-- ══ CONTENT ══ -->
            <div>

                <!-- ████ SECÇÃO 1 — PERFIL ARTÍSTICO ████ -->
                <div class="manage-section active" id="sec-artista">

                    <!-- Hero -->
                    <div class="artist-profile-hero">
                        <div style="position:relative;z-index:1">
                            <?php if ($has_artist_profile): ?>
                                <div class="artist-tag"><i class="bi bi-patch-check-fill"></i>Perfil Artístico
                                    Configurado
                                </div>
                            <?php else: ?>
                                <div class="artist-tag"
                                    style="background:rgba(255,193,7,.25);border-color:rgba(255,193,7,.4)">
                                    <i class="bi bi-exclamation-circle"></i>Perfil não configurado
                                </div>
                            <?php endif; ?>

                            <?php if (!$has_artist_profile): ?>
                                <div class="no-profile-ph">🎤</div>
                                <h3 style="color:#fff;margin:0;font-size:1.1rem;font-weight:800">Cria o teu perfil
                                    artístico
                                </h3>
                                <p style="color:rgba(255,255,255,.8);font-size:.83rem;margin:6px 0 0">
                                    O nome artístico é usado nos teus lançamentos e plataformas de streaming.
                                </p>
                            <?php else: ?>
                                <h3 style="color:#fff;margin:0;font-size:1.3rem;font-weight:800">
                                    <?php echo htmlspecialchars($user['name_artist_band']); ?>
                                </h3>
                                <p style="color:rgba(255,255,255,.75);font-size:.82rem;margin:4px 0 0">
                                    <?php if ($user['city_user'] || $user['country_user']): ?>
                                        <i class="bi bi-geo-alt-fill me-1" style="font-size:.65rem"></i>
                                        <?php echo htmlspecialchars(implode(', ', array_filter([$user['city_user'] ?? '', $user['country_user'] ?? '']))); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Profile form card -->
                    <div class="section-card">
                        <div class="section-title">
                            <i class="bi bi-person-badge"></i>
                            <?php echo $has_artist_profile ? 'Editar Perfil Artístico' : 'Criar Perfil Artístico'; ?>
                        </div>

                        <?php if (!$has_artist_profile): ?>
                            <div class="alert alert-info small d-flex gap-2 mb-3" style="border-radius:10px">
                                <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                                <div>
                                    O <strong>nome artístico ou de banda</strong> é o nome que vai aparecer nas
                                    plataformas
                                    de streaming (Spotify, Apple Music, etc.) nos teus lançamentos.
                                    Podes actualizar sempre que quiseres — excepto após um lançamento aprovado, onde a
                                    alteração requer análise.
                                </div>
                            </div>
                        <?php endif; ?>

                        <form id="artist-profile-form">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold small">
                                        Nome Artístico / Banda
                                        <span class="text-danger">*</span>
                                        <span class="text-muted fw-normal ms-1" style="font-size:.7rem">(aparece nas
                                            plataformas)</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-mic text-muted"></i></span>
                                        <input type="text" class="form-control" name="name_artist_band" maxlength="100"
                                            placeholder="ex: Dj Calú, Bad Gyal, The Weeknd..."
                                            value="<?php echo htmlspecialchars($user['name_artist_band'] ?? ''); ?>"
                                            required />
                                    </div>
                                    <div class="form-text">Este nome será validado para evitar conflitos com outros
                                        artistas na plataforma.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Telefone de Contacto</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-telephone text-muted"></i></span>
                                        <input type="tel" class="form-control" name="tel_user" maxlength="20"
                                            placeholder="+244 9XX XXX XXX"
                                            value="<?php echo htmlspecialchars($user['tel_user'] ?? ''); ?>" />
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small">País</label>
                                    <input type="text" class="form-control" name="country_user" maxlength="60"
                                        placeholder="ex: Angola"
                                        value="<?php echo htmlspecialchars($user['country_user'] ?? ''); ?>" />
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small">Cidade</label>
                                    <input type="text" class="form-control" name="city_user" maxlength="60"
                                        placeholder="ex: Luanda"
                                        value="<?php echo htmlspecialchars($user['city_user'] ?? ''); ?>" />
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold small">
                                        Bio Artística
                                        <span class="text-muted fw-normal ms-1" style="font-size:.7rem">máx. 1000
                                            chars</span>
                                    </label>
                                    <textarea class="form-control" name="about_user" rows="4" maxlength="1000"
                                        placeholder="Fala sobre o teu percurso artístico, estilo musical, inspirações..."
                                        id="bio-textarea"><?php echo htmlspecialchars($user['about_user'] ?? ''); ?></textarea>
                                    <div class="d-flex justify-content-end mt-1">
                                        <small class="text-muted" id="bio-counter">
                                            <?php echo strlen($user['about_user'] ?? ''); ?> / 1000
                                        </small>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Website / Link de Streaming</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-globe text-muted"></i></span>
                                        <input type="url" class="form-control" name="url_user" maxlength="255"
                                            placeholder="https://..."
                                            value="<?php echo htmlspecialchars($user['url_user'] ?? ''); ?>" />
                                    </div>
                                </div>
                            </div>

                            <div id="artist-profile-feedback" class="mt-3 d-none"></div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="button" class="btn px-4 fw-semibold"
                                    style="background:var(--wasom);color:#fff;border-radius:10px" id="btn-save-artist"
                                    onclick="saveArtistProfile()">
                                    <span id="save-artist-text">
                                        <i class="bi bi-check-lg me-1"></i>
                                        <?php echo $has_artist_profile ? 'Guardar Alterações' : 'Criar Perfil Artístico'; ?>
                                    </span>
                                    <span id="save-artist-load" class="d-none">
                                        <span class="spinner-border spinner-border-sm me-1"></span>A guardar...
                                    </span>
                                </button>
                                <?php if ($has_artist_profile): ?>
                                    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/artists-list"
                                        class="btn btn-outline-secondary px-3" style="border-radius:10px">
                                        <i class="bi bi-people me-1"></i>Ver Artistas
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <?php if ($has_artist_profile): ?>
                            <!-- Info: cannot delete, just update -->
                            <div class="mt-4 pt-3 border-top d-flex gap-2 align-items-start"
                                style="font-size:.78rem;color:#aaa">
                                <i class="bi bi-info-circle flex-shrink-0 mt-1"></i>
                                <div>
                                    O perfil artístico <strong>não pode ser eliminado</strong> enquanto tiveres
                                    lançamentos
                                    associados.
                                    Para criar um novo perfil artístico separado, vai a
                                    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/add-artist"
                                        style="color:var(--wasom)">Artistas</a>.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div><!-- /sec-artista -->


                <!-- ████ SECÇÃO 2 — EQUIPA / COLABORADORES ████ -->
                <div class="manage-section" id="sec-equipa">

                    <!-- Cabeçalho da equipa com limite -->
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div>
                            <span class="fw-semibold small">Plano <span
                                    style="color:var(--wasom)"><?php echo $plan_name; ?></span></span>
                            <span class="text-muted small ms-2">·</span>
                            <span class="text-muted small ms-2"><?php echo $collab_count; ?> /
                                <?php echo $max_collaborators; ?> colaboradores</span>
                        </div>
                        <?php if ($collab_count < $max_collaborators): ?>
                            <button class="btn btn-sm px-3 fw-semibold"
                                style="background:var(--wasom);color:#fff;border-radius:20px" data-bs-toggle="modal"
                                data-bs-target="#addCollabModal">
                                <i class="bi bi-person-plus me-1"></i>Adicionar colaborador
                            </button>
                        <?php else: ?>
                            <button class="btn btn-sm px-3 fw-semibold"
                                style="background:#6c757d;color:#fff;border-radius:20px;cursor:not-allowed" disabled
                                title="Limite de colaboradores atingido. Faz upgrade para adicionar mais.">
                                <i class="bi bi-person-plus me-1"></i>Limite atingido
                            </button>
                            <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/all-plans"
                                class="btn btn-sm btn-outline-warning px-3" style="border-radius:20px">
                                <i class="bi bi-arrow-up-circle me-1"></i>Fazer upgrade
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Barra de progresso -->
                    <div class="plan-limit-bar mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><i class="bi bi-people me-1"></i>Colaboradores utilizados</span>
                            <strong><?php echo $collab_count; ?> / <?php echo $max_collaborators; ?></strong>
                        </div>
                        <div class="progress" style="height:6px;border-radius:999px">
                            <div class="progress-bar" role="progressbar"
                                style="width:<?php echo min(100, ($collab_count / $max_collaborators) * 100); ?>%;background:var(--wasom);border-radius:999px">
                            </div>
                        </div>
                        <?php if ($collab_count >= $max_collaborators): ?>
                            <div class="mt-2 text-warning small d-flex gap-2">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span>Limite de colaboradores atingido. <a
                                        href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/all-plans"
                                        style="color:var(--wasom);font-weight:700">Faz upgrade <i
                                            class="bi bi-arrow-right"></i></a></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Collaborators list -->
                    <div class="section-card">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="section-title mb-0"><i class="bi bi-people"></i>Membros da Equipa</div>
                            <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()"
                                title="Actualizar">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>

                        <?php if ($collab_count === 0): ?>
                            <div class="text-center py-5">
                                <div style="font-size:3rem;opacity:.2;margin-bottom:12px">👤</div>
                                <p class="text-muted small mb-3">Ainda não tens colaboradores.<br />Adiciona o primeiro
                                    membro da tua equipa.</p>
                                <button class="btn btn-sm px-4"
                                    style="background:var(--wasom);color:#fff;border-radius:20px" data-bs-toggle="modal"
                                    data-bs-target="#addCollabModal">
                                    <i class="bi bi-person-plus me-1"></i>Adicionar colaborador
                                </button>
                            </div>

                        <?php else: ?>

                            <!-- Table header (desktop) -->
                            <div class="d-none d-lg-grid mb-2 px-3"
                                style="grid-template-columns:44px 1fr auto auto auto;gap:12px">
                                <div></div>
                                <div style="font-size:.7rem;color:#999;text-transform:uppercase;letter-spacing:.5px">
                                    Colaborador</div>
                                <div style="font-size:.7rem;color:#999;text-transform:uppercase;letter-spacing:.5px">
                                    Estado
                                </div>
                                <div style="font-size:.7rem;color:#999;text-transform:uppercase;letter-spacing:.5px">
                                    Função
                                </div>
                                <div style="font-size:.7rem;color:#999;text-transform:uppercase;letter-spacing:.5px">
                                    Acções
                                </div>
                            </div>

                            <?php foreach ($collaborators as $c):
                                $rm = $role_meta[$c['role_collab']] ?? $role_meta['editor'];
                                $sm = $status_meta[$c['status_collab']] ?? $status_meta['inactive'];
                                $is_online = $c['last_seen_at'] && $c['mins_since_seen'] < 5;
                                $photo = $c['photo_collab'];
                                $joined_str = date('d/m/Y', strtotime($c['creat_collab']));
                                $last_str   = $c['last_login_at'] ? date('d/m/Y H:i', strtotime($c['last_login_at'])) : 'Nunca';
                            ?>
                                <div class="collab-row" id="collab-row-<?php echo $c['id_collab']; ?>">
                                    <!-- Avatar + online dot -->
                                    <div style="position:relative">
                                        <?php if ($photo): ?>
                                            <img src="<?php echo htmlspecialchars($photo); ?>" class="collab-avatar"
                                                onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
                                                alt="" />
                                            <div class="collab-avatar-ph" style="display:none">
                                                <i class="bi bi-person"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="collab-avatar-ph"><i class="bi bi-person"></i></div>
                                        <?php endif; ?>
                                        <span class="online-dot <?php echo $is_online ? 'dot-online' : 'dot-offline'; ?>"
                                            style="position:absolute;bottom:2px;right:2px"
                                            title="<?php echo $is_online ? 'Online' : 'Offline'; ?>"></span>
                                    </div>

                                    <!-- Info -->
                                    <div style="min-width:0">
                                        <div class="fw-semibold small text-truncate">
                                            <?php echo htmlspecialchars($c['first_name'] . ' ' . ($c['second_name'] ?? '')); ?>
                                        </div>
                                        <div class="text-muted" style="font-size:.72rem">
                                            @<?php echo htmlspecialchars($c['user_collab']); ?>
                                            <span class="ms-1">· <?php echo htmlspecialchars($c['email_collab']); ?></span>
                                        </div>
                                        <div class="text-muted d-flex gap-2 flex-wrap mt-1" style="font-size:.68rem">
                                            <span><i class="bi bi-calendar3 me-1"></i><?php echo $joined_str; ?></span>
                                            <span><i class="bi bi-clock me-1"></i><?php echo $last_str; ?></span>
                                            <?php if ($c['tel_collab']): ?>
                                                <span><i
                                                        class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($c['tel_collab']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div>
                                        <span class="chip d-none d-md-inline-flex"
                                            style="background:<?php echo $sm['bg']; ?>;color:<?php echo $sm['color']; ?>">
                                            <?php echo $sm['label']; ?>
                                        </span>
                                    </div>

                                    <!-- Role -->
                                    <div>
                                        <span class="chip d-none d-md-inline-flex"
                                            style="background:<?php echo $rm['bg']; ?>;color:<?php echo $rm['color']; ?>">
                                            <i class="bi <?php echo $rm['icon']; ?>"></i>
                                            <?php echo $rm['label']; ?>
                                        </span>
                                    </div>

                                    <!-- Actions -->
                                    <div class="d-flex gap-1">
                                        <!-- Activities -->
                                        <button class="btn btn-sm btn-outline-secondary p-1 px-2" title="Ver actividades"
                                            onclick="viewActivities(<?php echo $c['id_collab']; ?>,'<?php echo htmlspecialchars(addslashes($c['first_name'])); ?>')">
                                            <i class="bi bi-clock-history" style="font-size:.8rem"></i>
                                        </button>
                                        <!-- Edit -->
                                        <button class="btn btn-sm btn-outline-secondary p-1 px-2" title="Editar"
                                            onclick="openEditCollab(<?php echo $c['id_collab']; ?>)">
                                            <i class="bi bi-pencil" style="font-size:.8rem"></i>
                                        </button>
                                        <!-- More (dropdown) -->
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-sm btn-outline-secondary p-1 px-2 dropdown-toggle dropdown-toggle-split"
                                                data-bs-toggle="dropdown" title="Mais acções">
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" style="font-size:.82rem">
                                                <?php if ($c['status_collab'] === 'active'): ?>
                                                    <li><a class="dropdown-item" href="#"
                                                            onclick="toggleStatus(<?php echo $c['id_collab']; ?>,'blocked');return false">
                                                            <i class="bi bi-slash-circle me-2 text-danger"></i>Bloquear
                                                        </a></li>
                                                <?php elseif ($c['status_collab'] === 'blocked'): ?>
                                                    <li><a class="dropdown-item" href="#"
                                                            onclick="toggleStatus(<?php echo $c['id_collab']; ?>,'active');return false">
                                                            <i class="bi bi-check-circle me-2 text-success"></i>Desbloquear
                                                        </a></li>
                                                <?php endif; ?>
                                                <?php if ($c['status_collab'] === 'pending' && !$c['invite_token_used']): ?>
                                                    <li><a class="dropdown-item" href="#"
                                                            onclick="resendInvite(<?php echo $c['id_collab']; ?>);return false">
                                                            <i class="bi bi-send me-2" style="color:var(--wasom)"></i>Reenviar
                                                            convite
                                                        </a></li>
                                                <?php endif; ?>
                                                <li>
                                                    <hr class="dropdown-divider" />
                                                </li>
                                                <li><a class="dropdown-item text-danger" href="#"
                                                        onclick="openDeleteCollab(<?php echo $c['id_collab']; ?>,'<?php echo htmlspecialchars(addslashes($c['first_name'])); ?>');return false">
                                                        <i class="bi bi-trash me-2"></i>Eliminar
                                                    </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; // collab_count 
                        ?>

                    </div><!-- /section-card -->

                    <!-- Legend -->
                    <div class="d-flex flex-wrap gap-3 mt-1 px-1" style="font-size:.72rem;color:#aaa">
                        <span><span class="online-dot dot-online me-1 d-inline-block"></span>Online (activo < 5
                                min)</span>
                                <span><span class="online-dot dot-offline me-1 d-inline-block"></span>Offline</span>
                                <span><i class="bi bi-envelope-check me-1"></i>Convite enviado por email</span>
                    </div>


                </div><!-- /sec-equipa -->

            </div><!-- /content -->
        </div><!-- /manage-layout -->
    </div><!-- /container -->


    <!-- ════ MODAL — Adicionar Colaborador ════ -->
    <div class="modal fade" id="addCollabModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#FF0089,#FF4D4D);color:#fff">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-plus-fill fs-4"></i>
                        <h5 class="modal-title mb-0">Adicionar Colaborador</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">

                    <div class="row g-3">
                        <!-- Photo URL -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Foto de Perfil <span
                                    class="text-muted fw-normal">(URL externo)</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-image text-muted"></i></span>
                                <input type="url" class="form-control" id="add-photo-url"
                                    placeholder="https://exemplo.com/foto.jpg" oninput="previewAddPhoto(this.value)" />
                            </div>
                            <div class="mt-2 d-flex align-items-center gap-2">
                                <img id="add-photo-preview" src="" alt=""
                                    style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,0,137,.3);display:none" />
                                <small class="text-muted" style="font-size:.72rem">Cola o URL de qualquer foto de
                                    perfil
                                    (LinkedIn, Google, etc.)</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Primeiro Nome <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add-first-name" maxlength="50"
                                placeholder="ex: Ana" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Apelido</label>
                            <input type="text" class="form-control" id="add-second-name" maxlength="80"
                                placeholder="ex: Silva" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" class="form-control" id="add-email"
                                    placeholder="colaborador@email.com" />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Telefone</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone text-muted"></i></span>
                                <input type="tel" class="form-control" id="add-tel" placeholder="+244 9XX XXX XXX" />
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Função <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="add-role">
                                <option value="admin">Administrador — Acesso total</option>
                                <option value="editor" selected>Editor — Lançamentos e artistas</option>
                                <option value="analyst">Analista — Estatísticas e relatórios</option>
                                <option value="support">Suporte — Visualização apenas</option>
                            </select>
                            <div class="form-text" id="role-desc-text">Pode criar e editar lançamentos e artistas.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Senha de Acesso <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="add-password" readonly
                                    placeholder="Clica para gerar →" style="font-family:monospace;font-size:.85rem" />
                                <button class="btn btn-outline-secondary" type="button" onclick="generatePassword()"
                                    title="Gerar senha forte">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyPassword()"
                                    title="Copiar senha">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                            <div class="form-text">Clica <i class="bi bi-arrow-repeat"></i> para gerar uma senha
                                forte.
                                Guarda antes de enviar.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Notas internas <span
                                    class="text-muted fw-normal">(opcional)</span></label>
                            <textarea class="form-control" id="add-notes" rows="2" maxlength="500"
                                placeholder="Ex: responsável pelas redes sociais, visualização financeira apenas..."></textarea>
                        </div>
                    </div>

                    <!-- Password preview -->
                    <div id="add-pwd-preview" class="mt-3 d-none">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <small class="fw-semibold" style="color:var(--wasom)"><i class="bi bi-key me-1"></i>Senha
                                gerada:</small>
                            <small class="text-muted">(guarda antes de enviar o convite)</small>
                        </div>
                        <div class="pwd-display" id="pwd-display-text"></div>
                    </div>

                    <div id="add-collab-feedback" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-md px-4 fw-semibold"
                        style="background:var(--wasom);color:#fff;border-radius:10px" id="btn-add-collab"
                        onclick="addCollaborator()">
                        <span id="add-collab-text"><i class="bi bi-send me-1"></i>Enviar convite</span>
                        <span id="add-collab-load" class="d-none"><span
                                class="spinner-border spinner-border-sm me-1"></span>A enviar...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- ════ MODAL — Editar Colaborador ════ -->
    <div class="modal fade" id="editCollabModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2" style="color:var(--wasom)"></i>Editar
                        Colaborador</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="edit-collab-id" />
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Foto de Perfil (URL)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-image text-muted"></i></span>
                                <input type="url" class="form-control" id="edit-photo-url"
                                    oninput="previewEditPhoto(this.value)" />
                            </div>
                            <div class="mt-2"><img id="edit-photo-preview" src="" alt=""
                                    style="width:44px;height:44px;border-radius:50%;object-fit:cover;display:none;border:2px solid rgba(255,0,137,.3)" />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Primeiro Nome <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit-first-name" maxlength="50" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Apelido</label>
                            <input type="text" class="form-control" id="edit-second-name" maxlength="80" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Telefone</label>
                            <input type="tel" class="form-control" id="edit-tel" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Função</label>
                            <select class="form-select" id="edit-role">
                                <option value="admin">Administrador</option>
                                <option value="editor">Editor</option>
                                <option value="analyst">Analista</option>
                                <option value="support">Suporte</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Notas internas</label>
                            <textarea class="form-control" id="edit-notes" rows="2" maxlength="500"></textarea>
                        </div>
                        <!-- Optional new password -->
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label fw-semibold small mb-0">Redefinir Senha <span
                                        class="text-muted fw-normal">(opcional)</span></label>
                                <button class="btn btn-outline-secondary btn-sm" type="button"
                                    onclick="generateEditPassword()">
                                    <i class="bi bi-arrow-repeat me-1"></i>Gerar nova
                                </button>
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control" id="edit-password"
                                    placeholder="Deixa em branco para manter a actual"
                                    style="font-family:monospace;font-size:.85rem" />
                                <button class="btn btn-outline-secondary" type="button" onclick="copyEditPassword()"><i
                                        class="bi bi-clipboard"></i></button>
                            </div>
                            <div class="form-text">Se definires nova senha, será enviado email ao colaborador.</div>
                        </div>
                    </div>
                    <div id="edit-collab-feedback" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm py-1 fw-semibold"
                        style="background:var(--wasom);color:#fff;border-radius:10px" id="btn-edit-collab"
                        onclick="saveEditCollab()">
                        <span id="edit-collab-text"><i class="bi bi-check-lg me-1"></i>Guardar</span>
                        <span id="edit-collab-load" class="d-none"><span
                                class="spinner-border spinner-border-sm me-1"></span></span>
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- ════ MODAL — Eliminar Colaborador ════ -->
    <div class="modal fade" id="deleteCollabModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Eliminar Colaborador</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    <p class="text-muted small mb-3">
                        Vais remover <strong id="del-collab-name"></strong> da tua equipa.
                        O colaborador perderá acesso imediatamente e receberá um email de notificação.
                    </p>
                    <input type="hidden" id="del-collab-id" />
                    <label class="form-label fw-semibold small">A tua senha <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="del-collab-pwd"
                            placeholder="Confirma com a tua senha" />
                        <button class="btn btn-outline-secondary" type="button"
                            onclick="const i=document.getElementById('del-collab-pwd');i.type=i.type==='password'?'text':'password'">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div id="del-collab-feedback" class="mt-2 d-none"></div>
                </div>
                <div class="modal-footer border-0 gap-2">
                    <button class="btn btn-outline-secondary btn-sm flex-fill" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-danger btn-sm flex-fill" id="btn-del-collab" onclick="deleteCollaborator()">
                        <span id="del-collab-text"><i class="bi bi-trash me-1"></i>Eliminar</span>
                        <span id="del-collab-load" class="d-none"><span
                                class="spinner-border spinner-border-sm"></span></span>
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- ════ MODAL — Actividades do Colaborador ════ -->
    <div class="modal fade" id="activitiesModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="bi bi-clock-history me-2"
                            style="color:var(--wasom)"></i>Actividades de <span id="act-collab-name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="activities-body">
                    <div class="text-center py-4"><span class="spinner-border text-secondary"></span></div>
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
        const PROCESS = '<?php echo $process_url; ?>';
        const COLLABS = <?php echo $collabs_json; ?>;

        toastr.options = {
            progressBar: true,
            closeButton: true,
            positionClass: 'toast-top-right',
            timeOut: 4000
        };

        // ── Section nav ────────────────────────────
        function showSection(id) {
            document.querySelectorAll('.manage-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.mobile-tab').forEach(b => b.classList.remove('active'));
            document.getElementById('sec-' + id)?.classList.add('active');
            document.querySelector(`.sidebar-nav [data-section="${id}"]`)?.classList.add('active');
            document.querySelector(`.mobile-tab[data-section="${id}"]`)?.classList.add('active');
            history.replaceState(null, '', '#' + id);
        }
        document.querySelectorAll('.sidebar-nav .nav-link, .mobile-tab').forEach(el => {
            el.addEventListener('click', e => {
                e.preventDefault();
                showSection(el.dataset.section);
            });
        });
        const hash = location.hash.replace('#', '');
        if (['artista', 'equipa'].includes(hash)) showSection(hash);

        // ── Helpers ───────────────────────────────
        async function postJSON(payload) {
            const fd = new FormData();
            fd.append('csrf_token', CSRF);
            for (const [k, v] of Object.entries(payload)) fd.append(k, v ?? '');
            const r = await fetch(PROCESS, {
                method: 'POST',
                body: fd
            });
            return r.json();
        }

        function setLoad(textId, loadId, btnEl, on) {
            document.getElementById(textId)?.classList.toggle('d-none', on);
            document.getElementById(loadId)?.classList.toggle('d-none', !on);
            if (btnEl) btnEl.disabled = on;
        }

        function showFb(id, ok, msg) {
            const el = document.getElementById(id);
            if (!el) return;
            el.innerHTML =
                `<div class="alert alert-${ok?'success':'danger'} small py-2 d-flex gap-2"><i class="bi bi-${ok?'check-circle':'exclamation-circle'}-fill flex-shrink-0 mt-1"></i><div>${msg}</div></div>`;
            el.classList.remove('d-none');
        }

        // ── Bio counter ───────────────────────────
        const bioTA = document.getElementById('bio-textarea');
        const bioCT = document.getElementById('bio-counter');
        if (bioTA && bioCT) {
            bioTA.addEventListener('input', () => bioCT.textContent = bioTA.value.length + ' / 1000');
        }

        // ── Role descriptions ─────────────────────
        const roleDescs = {
            admin: 'Acesso total à conta, lançamentos, finanças e equipa.',
            editor: 'Pode criar e editar lançamentos e artistas.',
            analyst: 'Acesso a estatísticas, streaming e relatórios financeiros.',
            support: 'Visualização apenas — sem edição de dados.',
        };
        document.getElementById('add-role')?.addEventListener('change', function() {
            document.getElementById('role-desc-text').textContent = roleDescs[this.value] || '';
        });

        // ── Photo previews ─────────────────────────
        function previewAddPhoto(url) {
            const img = document.getElementById('add-photo-preview');
            if (!url) {
                img.style.display = 'none';
                return;
            }
            img.src = url;
            img.style.display = 'block';
            img.onerror = () => img.style.display = 'none';
        }

        function previewEditPhoto(url) {
            const img = document.getElementById('edit-photo-preview');
            if (!url) {
                img.style.display = 'none';
                return;
            }
            img.src = url;
            img.style.display = 'block';
            img.onerror = () => img.style.display = 'none';
        }

        // ── Generate password ─────────────────────
        async function generatePassword() {
            const r = await postJSON({
                action: 'generate_password'
            });
            if (r.ok) {
                document.getElementById('add-password').value = r.password;
                document.getElementById('pwd-display-text').textContent = r.password;
                document.getElementById('add-pwd-preview').classList.remove('d-none');
            }
        }

        function copyPassword() {
            const val = document.getElementById('add-password').value;
            if (!val) {
                toastr.warning('Gera primeiro uma senha.');
                return;
            }
            navigator.clipboard.writeText(val).then(() => toastr.success('Senha copiada!'));
        }
        async function generateEditPassword() {
            const r = await postJSON({
                action: 'generate_password'
            });
            if (r.ok) document.getElementById('edit-password').value = r.password;
        }

        function copyEditPassword() {
            const val = document.getElementById('edit-password').value;
            if (!val) {
                toastr.warning('Gera primeiro uma senha.');
                return;
            }
            navigator.clipboard.writeText(val).then(() => toastr.success('Senha copiada!'));
        }

        // ── Save artist profile ───────────────────
        async function saveArtistProfile() {
            const btn = document.getElementById('btn-save-artist');
            const form = document.getElementById('artist-profile-form');
            const name = form.querySelector('[name="name_artist_band"]').value.trim();
            if (!name) {
                showFb('artist-profile-feedback', false, 'O nome artístico é obrigatório.');
                return;
            }

            setLoad('save-artist-text', 'save-artist-load', btn, true);
            document.getElementById('artist-profile-feedback').classList.add('d-none');

            const data = new FormData(form);
            data.append('action', 'update_account_profile');
            data.append('csrf_token', CSRF);
            try {
                const r = await fetch(PROCESS, {
                    method: 'POST',
                    body: data
                });
                const res = await r.json();
                if (res.ok) {
                    toastr.success(res.message);
                    setTimeout(() => location.reload(), 800);
                } else showFb('artist-profile-feedback', false, res.message);
            } catch {
                toastr.error('Erro de ligação.');
            } finally {
                setLoad('save-artist-text', 'save-artist-load', btn, false);
            }
        }

        // ── Add collaborator ──────────────────────
        async function addCollaborator() {
            const firstName = document.getElementById('add-first-name').value.trim();
            const email = document.getElementById('add-email').value.trim();
            const pwd = document.getElementById('add-password').value.trim();

            if (!firstName) {
                showFb('add-collab-feedback', false, 'O nome é obrigatório.');
                return;
            }
            if (!email) {
                showFb('add-collab-feedback', false, 'O email é obrigatório.');
                return;
            }
            if (!pwd) {
                showFb('add-collab-feedback', false, 'Gera e copia a senha antes de enviar.');
                return;
            }

            const btn = document.getElementById('btn-add-collab');
            setLoad('add-collab-text', 'add-collab-load', btn, true);
            document.getElementById('add-collab-feedback').classList.add('d-none');

            const r = await postJSON({
                action: 'add_collaborator',
                first_name: firstName,
                second_name: document.getElementById('add-second-name').value,
                email_collab: email,
                tel_collab: document.getElementById('add-tel').value,
                photo_url: document.getElementById('add-photo-url').value,
                role_collab: document.getElementById('add-role').value,
                plain_password: pwd,
                notes: document.getElementById('add-notes').value,
            });

            setLoad('add-collab-text', 'add-collab-load', btn, false);

            if (r.ok) {
                bootstrap.Modal.getInstance(document.getElementById('addCollabModal')).hide();
                await Swal.fire({
                    icon: 'success',
                    iconColor: '#FF0089',
                    title: 'Convite enviado!',
                    html: `<p>${r.message}</p><div class="mt-2 small text-muted">Username atribuído: <strong>@${r.username}</strong></div>`,
                    confirmButtonColor: '#FF0089',
                    timer: 4000,
                    timerProgressBar: true
                });
                location.reload();
            } else showFb('add-collab-feedback', false, r.message);
        }

        // ── Edit collaborator ─────────────────────
        function openEditCollab(id) {
            const c = COLLABS.find(x => x.id_collab == id);
            if (!c) return;
            document.getElementById('edit-collab-id').value = id;
            document.getElementById('edit-first-name').value = c.first_name || '';
            document.getElementById('edit-second-name').value = c.second_name || '';
            document.getElementById('edit-tel').value = c.tel_collab || '';
            document.getElementById('edit-photo-url').value = c.photo_collab || '';
            document.getElementById('edit-role').value = c.role_collab || 'editor';
            document.getElementById('edit-notes').value = c.notes || '';
            document.getElementById('edit-password').value = '';
            previewEditPhoto(c.photo_collab || '');
            document.getElementById('edit-collab-feedback').classList.add('d-none');
            new bootstrap.Modal(document.getElementById('editCollabModal')).show();
        }

        async function saveEditCollab() {
            const id = document.getElementById('edit-collab-id').value;
            const firstName = document.getElementById('edit-first-name').value.trim();
            if (!firstName) {
                showFb('edit-collab-feedback', false, 'O nome é obrigatório.');
                return;
            }

            const btn = document.getElementById('btn-edit-collab');
            setLoad('edit-collab-text', 'edit-collab-load', btn, true);
            document.getElementById('edit-collab-feedback').classList.add('d-none');

            const r = await postJSON({
                action: 'edit_collaborator',
                id_collab: id,
                first_name: firstName,
                second_name: document.getElementById('edit-second-name').value,
                tel_collab: document.getElementById('edit-tel').value,
                photo_url: document.getElementById('edit-photo-url').value,
                role_collab: document.getElementById('edit-role').value,
                notes: document.getElementById('edit-notes').value,
                new_password: document.getElementById('edit-password').value,
            });

            setLoad('edit-collab-text', 'edit-collab-load', btn, false);

            if (r.ok) {
                bootstrap.Modal.getInstance(document.getElementById('editCollabModal')).hide();
                toastr.success(r.message);
                setTimeout(() => location.reload(), 800);
            } else showFb('edit-collab-feedback', false, r.message);
        }

        // ── Toggle status ─────────────────────────
        async function toggleStatus(id, newStatus) {
            const labels = {
                active: 'Desbloquear',
                blocked: 'Bloquear',
                inactive: 'Desactivar'
            };
            const result = await Swal.fire({
                icon: newStatus === 'blocked' ? 'warning' : 'question',
                title: (newStatus === 'blocked' ? 'Bloquear' : 'Desbloquear') + ' colaborador?',
                text: newStatus === 'blocked' ? 'O colaborador perderá acesso imediatamente.' : 'O colaborador voltará a ter acesso.',
                showCancelButton: true,
                confirmButtonColor: newStatus === 'blocked' ? '#dc3545' : '#198754',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Confirmar',
            });
            if (!result.isConfirmed) return;
            const r = await postJSON({
                action: 'toggle_collab_status',
                id_collab: id,
                new_status: newStatus
            });
            if (r.ok) {
                toastr.success(r.message);
                setTimeout(() => location.reload(), 700);
            } else toastr.error(r.message);
        }

        // ── Resend invite ─────────────────────────
        async function resendInvite(id) {
            const result = await Swal.fire({
                icon: 'question',
                title: 'Reenviar convite?',
                text: 'Será gerada nova senha e reenviado email de convite.',
                showCancelButton: true,
                confirmButtonColor: '#FF0089',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sim, reenviar'
            });
            if (!result.isConfirmed) return;
            const r = await postJSON({
                action: 'resend_invite',
                id_collab: id
            });
            if (r.ok) toastr.success(r.message);
            else toastr.error(r.message);
        }

        // ── Delete collaborator ───────────────────
        function openDeleteCollab(id, name) {
            document.getElementById('del-collab-id').value = id;
            document.getElementById('del-collab-name').textContent = name;
            document.getElementById('del-collab-pwd').value = '';
            document.getElementById('del-collab-feedback').classList.add('d-none');
            new bootstrap.Modal(document.getElementById('deleteCollabModal')).show();
        }
        async function deleteCollaborator() {
            const id = document.getElementById('del-collab-id').value;
            const pwd = document.getElementById('del-collab-pwd').value;
            if (!pwd) {
                showFb('del-collab-feedback', false, 'Introduz a tua senha.');
                return;
            }

            const btn = document.getElementById('btn-del-collab');
            setLoad('del-collab-text', 'del-collab-load', btn, true);
            const r = await postJSON({
                action: 'delete_collaborator',
                id_collab: id,
                password_confirm: pwd
            });
            setLoad('del-collab-text', 'del-collab-load', btn, false);

            if (r.ok) {
                bootstrap.Modal.getInstance(document.getElementById('deleteCollabModal')).hide();
                toastr.success(r.message);
                setTimeout(() => location.reload(), 700);
            } else showFb('del-collab-feedback', false, r.message);
        }

        // ── View activities ────────────────────────
        async function viewActivities(id, name) {
            document.getElementById('act-collab-name').textContent = name;
            document.getElementById('activities-body').innerHTML =
                '<div class="text-center py-4"><span class="spinner-border text-secondary"></span></div>';
            const modal = new bootstrap.Modal(document.getElementById('activitiesModal'));
            modal.show();

            const r = await postJSON({
                action: 'get_collab_activities',
                id_collab: id
            });
            if (!r.ok) {
                document.getElementById('activities-body').innerHTML =
                    `<p class="text-muted small text-center py-3">${r.message}</p>`;
                return;
            }

            const acts = r.activities;
            if (!acts || acts.length === 0) {
                document.getElementById('activities-body').innerHTML =
                    '<p class="text-muted small text-center py-3">Sem actividades registadas.</p>';
                return;
            }

            const iconMap = {
                login: 'bi-box-arrow-in-right',
                logout: 'bi-box-arrow-right',
                login_failed: 'bi-exclamation-triangle',
                account_activated: 'bi-check-circle',
                invite_sent: 'bi-envelope',
                status_changed: 'bi-toggle-on',
                profile_edited: 'bi-pencil',
                invite_resent: 'bi-send',
            };

            const html = acts.map(a => {
                const d = new Date(a.creat_activity).toLocaleDateString('pt-PT', {
                    day: '2-digit',
                    month: 'short',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                const ico = iconMap[a.activity_type] || 'bi-activity';
                return `<div class="activity-item">
            <div class="activity-icon"><i class="bi ${ico}"></i></div>
            <div>
                <div style="font-weight:600;font-size:.83rem">${a.description || a.activity_type}</div>
                <div style="font-size:.72rem;color:#aaa">${d}${a.ip_address ? ' · IP: '+a.ip_address : ''}</div>
            </div>
        </div>`;
            }).join('');

            document.getElementById('activities-body').innerHTML = html;
        }
    </script>
</body>

</html>