<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Criar Lançamento
// Arquivo: dashboard/launch/creat-release.php
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

// Verificar se é um rascunho da base de dados
$draft_id = isset($_GET['draft']) ? (int)$_GET['draft'] : 0;
$draft_from_db = null;

if ($draft_id) {
    // Buscar dados do álbum em draft
    $draft_stmt = $db->prepare("
        SELECT * FROM _album 
        WHERE id_album = ? AND id_users = ? AND status_album = 'draft'
    ");
    $draft_stmt->execute([$draft_id, $id_users]);
    $draft_from_db = $draft_stmt->fetch(PDO::FETCH_ASSOC);

    if ($draft_from_db) {
        // Buscar faixas do draft
        $tracks_stmt = $db->prepare("
            SELECT * FROM _track WHERE id_album = ? ORDER BY track_number
        ");
        $tracks_stmt->execute([$draft_id]);
        $draft_tracks = $tracks_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar stores selecionadas
        $stores_stmt = $db->prepare("
            SELECT id_store FROM _album_store WHERE id_album = ?
        ");
        $stores_stmt->execute([$draft_id]);
        $draft_stores = $stores_stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        // Se não encontrar o draft, redirecionar
        redirect('/dashboard/releases', ['error' => 'draft_not_found']);
    }
}

// ── Verificar plano activo ────────────────────
$plan_paid = ($user['status_user'] === 'active' && !empty($user['plan_activated_at']));
if (!$plan_paid) {
    redirect('/dashboard/all-plans', ['error' => 'no_plan']);
}

$plan_id = (int)$user['plan_selected'];
$ps = $db->prepare('SELECT * FROM _plans WHERE id_plan = ?');
$ps->execute([$plan_id]);
$plan = $ps->fetch();
if (!$plan) {
    redirect('/dashboard/all-plans', ['error' => 'no_plan']);
}

$plan_slug      = $plan['slug_plan'];  // single | album | artist | label
$max_tracks     = $plan['max_tracks_per_release'];  // 1, 15, NULL
$can_label      = ($plan_slug !== 'single');        // single can't customise label
$can_multi_artist = ($plan_slug === 'label');       // label plan allows multiple artist profiles
$ui_max_tracks  = $max_tracks ?? ($plan_slug === 'label' ? 50 : 30);  // UI limit when NULL

// ── Artistas do utilizador ────────────────────
$art_stmt = $db->prepare("SELECT id_artist, stage_name, real_name, photo_artist FROM _artist WHERE id_users = ? AND status_artist = 'active' ORDER BY stage_name");
$art_stmt->execute([$id_users]);
$user_artists = $art_stmt->fetchAll(PDO::FETCH_ASSOC);
$artists_json = json_encode($user_artists, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);


// ── Lojas digitais agrupadas por região ─────
$stores_stmt = $db->query("
    SELECT id_store, name_store, slug_store, region_store
    FROM _store
    WHERE is_active = 1
    ORDER BY FIELD(region_store, 'África','Europa','América do Norte','América do Sul','Ásia','Oceania','Global'),
             region_store, display_order
");
$stores_raw = $stores_stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar manualmente
$stores_by_region = [];
foreach ($stores_raw as $store) {
    $region = $store['region_store'] ?: 'Global';
    $stores_by_region[$region][] = $store;
}
$stores_json = json_encode($stores_raw); // mantém todas as lojas para o JS

// ── Session data (logout modal) ───────────────
$ls = $db->prepare('SELECT last_login_at FROM _users_security WHERE id_users = ?');
$ls->execute([$id_users]);
$sec = $ls->fetch();
$last_login_str = ($sec && $sec['last_login_at']) ? date('d/m/Y H:i', strtotime($sec['last_login_at'])) : '—';

$csrf = htmlspecialchars($_SESSION['csrf_token']);

// ── Store icons map ───────────────────────────
$store_icons = [

    // ── Streaming Global ──────────────────────────────────────────────────
    'spotify'              => ['icon' => 'bi-spotify',               'color' => '#1db954'],
    'apple-music'          => ['icon' => 'bi-apple',                 'color' => '#fc3c44'],
    'amazon-music'         => ['icon' => 'bi-amazon',                'color' => '#ff9900'],
    'deezer'               => ['icon' => 'bi-music-note-beamed',     'color' => '#ef5466'],
    'tidal'                => ['icon' => 'bi-water',                 'color' => '#00ffff'],
    'boomplay'             => ['icon' => 'bi-play-circle-fill',      'color' => '#f85d2f'],
    'youtube-music'        => ['icon' => 'bi-youtube',               'color' => '#ff0000'],
    'soundcloud'           => ['icon' => 'bi-soundwave',             'color' => '#ff5500'],
    'napster'              => ['icon' => 'bi-music-note-list',       'color' => '#009bd9'],
    'iheart-radio'         => ['icon' => 'bi-broadcast',             'color' => '#c6002b'],
    'audiomack'            => ['icon' => 'bi-soundwave',             'color' => '#ffa500'],
    'qobuz'                => ['icon' => 'bi-vinyl-fill',            'color' => '#003d7a'],

    // ── Streaming Ásia ────────────────────────────────────────────────────
    'jiosaavn'             => ['icon' => 'bi-music-note-list',       'color' => '#2bc5b4'],
    'gaana'                => ['icon' => 'bi-music-note-beamed',     'color' => '#e72a2a'],
    'wynk-music'           => ['icon' => 'bi-headphones',            'color' => '#5a50f0'],
    'hungama'              => ['icon' => 'bi-collection-play-fill',  'color' => '#e31837'],
    'netease-cloud-music'  => ['icon' => 'bi-cloud-fill',            'color' => '#e60026'],
    'qq-music'             => ['icon' => 'bi-music-player-fill',     'color' => '#fcb900'],
    'kugou'                => ['icon' => 'bi-disc-fill',             'color' => '#1677ff'],
    'kuwo-music'           => ['icon' => 'bi-music-note-beamed',     'color' => '#e60012'],
    'melon'                => ['icon' => 'bi-circle-fill',           'color' => '#00cd3c'],
    'genie'                => ['icon' => 'bi-stars',                 'color' => '#005bac'],
    'bugs'                 => ['icon' => 'bi-music-note',            'color' => '#ff4f00'],
    'flo'                  => ['icon' => 'bi-play-btn-fill',         'color' => '#7b2fff'],
    'kkbox'                => ['icon' => 'bi-grid-fill',             'color' => '#009fee'],
    'joox'                 => ['icon' => 'bi-play-circle-fill',      'color' => '#00c040'],
    'line-music'           => ['icon' => 'bi-chat-fill',             'color' => '#00b900'],
    'awa'                  => ['icon' => 'bi-soundwave',             'color' => '#111111'],
    'recochoku'            => ['icon' => 'bi-headset',               'color' => '#e60020'],
    'anghami'              => ['icon' => 'bi-music-note-beamed',     'color' => '#5b35d5'],
    'yandex-music'         => ['icon' => 'bi-music-note-list',       'color' => '#fc3f1d'],
    'vk-music'             => ['icon' => 'bi-person-fill',           'color' => '#0077ff'],
    'fizy'                 => ['icon' => 'bi-music-note-beamed',     'color' => '#6b00d7'],

    // ── Streaming LATAM / Brasil ──────────────────────────────────────────
    'imusica'              => ['icon' => 'bi-music-note-beamed',     'color' => '#e4002b'],
    'tim-music'            => ['icon' => 'bi-phone-fill',            'color' => '#0033a0'],
    'triller'              => ['icon' => 'bi-camera-video-fill',     'color' => '#ff3b30'],
    'claro-music'          => ['icon' => 'bi-reception-4',           'color' => '#e30613'],

    // ── Streaming Rússia / Outros ─────────────────────────────────────────
    'zvuk'                 => ['icon' => 'bi-vinyl',                 'color' => '#7b2fff'],
    'pandora'              => ['icon' => 'bi-broadcast',             'color' => '#3668ff'],
    'resso'                => ['icon' => 'bi-music-player',          'color' => '#ff4040'],

    // ── Download ──────────────────────────────────────────────────────────
    'itunes'               => ['icon' => 'bi-bag-music-fill',        'color' => '#ea4cc0'],
    'beatport'             => ['icon' => 'bi-headphones-fill',       'color' => '#02e75c'],
    'traxsource'           => ['icon' => 'bi-vinyl-fill',            'color' => '#e4002b'],
    'bandcamp'             => ['icon' => 'bi-bandcamp',              'color' => '#1da0c3'],
    '7digital'             => ['icon' => 'bi-7-circle-fill',         'color' => '#e4002b'],
    'hdtracks'             => ['icon' => 'bi-soundwave',             'color' => '#333333'],
    'juno-download'        => ['icon' => 'bi-cloud-arrow-down-fill', 'color' => '#e4002b'],
    'emusic'               => ['icon' => 'bi-download',              'color' => '#2c7be5'],

    // ── Social ────────────────────────────────────────────────────────────
    'tiktok'               => ['icon' => 'bi-tiktok',               'color' => '#010101'],
    'facebook'             => ['icon' => 'bi-facebook',             'color' => '#1877f2'],
    'snapchat'             => ['icon' => 'bi-snapchat',             'color' => '#f7c300'],
    'instagram'            => ['icon' => 'bi-instagram',            'color' => '#e1306c'],
    'x-twitter'            => ['icon' => 'bi-twitter-x',           'color' => '#000000'],
    'twitch'               => ['icon' => 'bi-twitch',               'color' => '#9146ff'],
    'kwai'                 => ['icon' => 'bi-camera-reels-fill',    'color' => '#ff5c00'],
    'vk'                   => ['icon' => 'bi-person-video3',        'color' => '#0077ff'],
    'likee'                => ['icon' => 'bi-heart-fill',           'color' => '#ff2d55'],

    // ── Vídeo ─────────────────────────────────────────────────────────────
    'youtube'              => ['icon' => 'bi-youtube',              'color' => '#ff0000'],
    'vevo'                 => ['icon' => 'bi-play-btn-fill',        'color' => '#e4002b'],
    'dailymotion'          => ['icon' => 'bi-play-circle-fill',     'color' => '#003f8a'],
    'vimeo'                => ['icon' => 'bi-vimeo',                'color' => '#1ab7ea'],

    // ── Fallback ──────────────────────────────────────────────────────────
    'default'              => ['icon' => 'bi-shop',                 'color' => '#6c757d'],
];

ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title>Novo Lançamento — <?php echo APP_NAME; ?></title>
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <!-- Cropper.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" />
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <style>
    :root {
        --wasom: #FF0089;
        --wasom-dark: #cc006d;
    }

    /* ── Step wizard ── */
    .step-wizard {
        display: flex;
        align-items: center;
        gap: 0;
        margin-bottom: 2rem;
        overflow-x: auto;
    }

    .step-item {
        display: flex;
        align-items: center;
        flex: 1;
        min-width: 80px;
    }

    .step-item:last-child {
        flex: 0;
    }

    .step-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 2px solid #dee2e6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .8rem;
        font-weight: 700;
        flex-shrink: 0;
        background: #fff;
        color: #aaa;
        transition: all .3s;
    }

    .step-item.active .step-circle {
        border-color: var(--wasom);
        background: var(--wasom);
        color: #fff;
    }

    .step-item.done .step-circle {
        border-color: #198754;
        background: #198754;
        color: #fff;
    }

    .step-label {
        font-size: .72rem;
        color: #888;
        margin-left: 8px;
        white-space: nowrap;
    }

    .step-item.active .step-label {
        color: var(--wasom);
        font-weight: 600;
    }

    .step-item.done .step-label {
        color: #198754;
    }

    .step-line {
        flex: 1;
        height: 2px;
        background: #dee2e6;
        margin: 0 4px;
    }

    .step-item.done+.step-item .step-line,
    .step-item.done .step-line {
        background: #198754;
    }

    /* ── Cover upload area ── */
    .cover-drop {
        border: 2px dashed #dee2e6;
        border-radius: 12px;
        aspect-ratio: 1/1;
        max-width: 280px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: border-color .3s, background .3s;
        overflow: hidden;
        background: #f8f9fa;
        position: relative;
    }

    .cover-drop:hover {
        border-color: var(--wasom);
        background: rgba(255, 0, 137, .04);
    }

    .cover-drop.has-image {
        border-style: solid;
        border-color: var(--wasom);
    }

    #cover-preview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: none;
        border-radius: 10px;
    }

    .cover-drop.has-image #cover-preview {
        display: block;
    }

    .cover-drop.has-image .cover-placeholder {
        display: none;
    }

    .cover-placeholder {
        text-align: center;
        padding: 20px;
        pointer-events: none;
    }

    .cover-requirements {
        font-size: .72rem;
        color: #999;
        margin-top: 8px;
        text-align: center;
    }

    /* ── Cropper modal ── */
    #cropper-img {
        max-width: 100%;
    }

    .cropper-container {
        max-height: 380px;
    }

    /* ── Track card ── */
    .track-card {
        border: 1px solid rgba(0, 0, 0, .1);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        background: var(--card-bg, #fff);
        position: relative;
    }

    .track-number-badge {
        position: absolute;
        top: -12px;
        left: 16px;
        background: var(--wasom);
        color: #fff;
        font-size: .75rem;
        font-weight: 700;
        padding: 2px 12px;
        border-radius: 20px;
    }

    .btn-remove-track {
        position: absolute;
        top: 12px;
        right: 12px;
        background: none;
        border: none;
        color: #dc3545;
        font-size: 1.1rem;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 6px;
        transition: background .2s;
    }

    .btn-remove-track:hover {
        background: rgba(220, 53, 69, .1);
    }

    /* ── Store grid ── */
    .store-card {
        border: 2px solid transparent;
        border-radius: 12px;
        padding: 16px 12px;
        text-align: center;
        cursor: pointer;
        transition: all .2s;
        background: var(--card-bg, #fff);
    }

    .store-card:hover {
        border-color: var(--wasom);
        transform: translateY(-2px);
    }

    .store-card.selected {
        border-color: var(--wasom);
        background: rgba(255, 0, 137, .06);
    }

    .store-card .store-icon {
        font-size: 2rem;
        margin-bottom: 6px;
    }

    .store-card .store-name {
        font-size: .75rem;
        font-weight: 600;
    }

    .store-card .store-check {
        display: none;
        position: absolute;
        top: 6px;
        right: 8px;
        color: var(--wasom);
        font-size: .9rem;
    }

    .store-card {
        position: relative;
    }

    .store-card.selected .store-check {
        display: block;
    }

    /* Adicionar ao <style> existente */
    .track-audio:valid {
        border-color: #198754;
    }

    .audio-filename {
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: inline-block;
    }

    .audio-progress {
        color: var(--wasom);
    }

    .track-card.has-audio {
        border-left: 4px solid #198754;
    }

    /* ── Step panels ── */
    .step-panel {
        display: none;
        animation: fadeInStep .3s ease;
    }

    .step-panel.active {
        display: block;
    }

    @keyframes fadeInStep {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ── Navigation buttons ── */
    .step-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 24px;
        padding-top: 20px;
        border-top: 1px solid rgba(0, 0, 0, .06);
    }

    /* ── Review summary ── */
    .review-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid rgba(0, 0, 0, .06);
        font-size: .9rem;
    }

    .review-row:last-child {
        border-bottom: none;
    }

    .review-label {
        color: #888;
    }

    .review-value {
        font-weight: 600;
        text-align: right;
        max-width: 60%;
    }

    /* ── Copyright field ── */
    .copyright-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .copyright-prefix {
        font-size: 1.2rem;
    }

    .copyright-fixed {
        background: #f1f3f5;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: .9rem;
        color: #555;
        flex: 1;
    }

    /* ── Select2 tag ── */
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered {
        padding: 4px;
    }
    </style>
</head>

<body>

    <!-- Navbar (compact) -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/painel">
                <span class="text-light" style="
              font-weight: bold;
              box-sizing: border-box;
              text-transform: uppercase;
              font-family: Arial, sans-serif;
            "><?php echo APP_NAME; ?></span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white-50 small d-none d-md-block">
                    <i class="bi bi-disc me-1"></i>Novo Lançamento
                    <span class="badge ms-2"
                        style="background:rgba(255,0,137,.2);color:#ff0089"><?php echo htmlspecialchars($plan['name_plan']); ?></span>
                </span>
                <a href="releases" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Cancelar
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4" style="max-width:820px">

        <!-- Step Wizard -->
        <div class="step-wizard mb-4" id="step-wizard">
            <div class="step-item active" data-step="1">
                <div class="step-circle" id="sc-1">1</div>
                <span class="step-label">Capa & Info</span>
            </div>
            <div class="step-line"></div>
            <div class="step-item" data-step="2">
                <div class="step-circle" id="sc-2">2</div>
                <span class="step-label">Créditos</span>
            </div>
            <div class="step-line"></div>
            <div class="step-item" data-step="3">
                <div class="step-circle" id="sc-3">3</div>
                <span class="step-label">Faixas</span>
            </div>
            <div class="step-line"></div>
            <div class="step-item" data-step="4">
                <div class="step-circle" id="sc-4">4</div>
                <span class="step-label">Distribuição</span>
            </div>
            <div class="step-line"></div>
            <div class="step-item" data-step="5">
                <div class="step-circle" id="sc-5">5</div>
                <span class="step-label">Publicar</span>
            </div>
        </div>

        <div class="card p-4">

            <!-- ════════════════════════════════════
         STEP 1 — Capa & Identidade
    ════════════════════════════════════ -->
            <div class="step-panel active" id="panel-1">
                <h5 class="fw-bold mb-1"><i class="bi bi-image me-2" style="color:var(--wasom)"></i>Capa & Identidade
                </h5>
                <p class="text-muted small mb-4">A imagem de capa é a identidade visual do teu lançamento em todas as
                    plataformas.</p>

                <!-- Cover upload -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Imagem de Capa <span class="text-danger">*</span></label>
                    <div class="cover-drop" id="cover-drop" onclick="document.getElementById('cover-input').click()">
                        <img id="cover-preview" src="" alt="Capa" />
                        <div class="cover-placeholder">
                            <i class="bi bi-image" style="font-size:2.5rem;color:#ccc"></i>
                            <p class="text-muted small mt-2 mb-0">Clica para escolher a imagem</p>
                            <p class="text-muted" style="font-size:.7rem">ou arrasta aqui</p>
                        </div>
                    </div>
                    <input type="file" id="cover-input" accept="image/jpeg,image/png,image/webp" class="d-none" />
                    <div class="cover-requirements text-muted" style="max-width:280px;margin:8px auto 0">
                        <i class="bi bi-info-circle me-1"></i>
                        JPG ou PNG &nbsp;·&nbsp; Mínimo 1400×1400 px &nbsp;·&nbsp; Quadrada &nbsp;·&nbsp; Máx. 10 MB
                    </div>
                    <div id="cover-error" class="text-danger small mt-1 text-center d-none"></div>
                    <div class="text-center mt-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="btn-crop">
                            <i class="bi bi-crop me-1"></i>Recortar imagem
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm d-none" id="btn-remove-cover">
                            <i class="bi bi-x me-1"></i>Remover
                        </button>
                    </div>
                </div>

                <!-- Title -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Título do Lançamento <span
                            class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" maxlength="150" placeholder="ex: Luz da Manhã" />
                    <div class="form-text">Nome exacto como aparecerá nas plataformas. Sem adicionar "Single", "EP",
                        etc.</div>
                </div>

                <!-- Version -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Versão</label>
                    <select class="form-select" id="version">
                        <option value="">Original</option>
                        <option value="Remasterizado">Remasterizado</option>
                        <option value="Ao Vivo">Ao Vivo</option>
                        <option value="Acústico">Acústico</option>
                        <option value="Remix">Remix</option>
                        <option value="Extended">Extended</option>
                        <option value="Deluxe Edition">Deluxe Edition</option>
                        <option value="Edição Especial">Edição Especial</option>
                        <option value="Instrumental">Instrumental</option>
                        <option value="Karaoke">Karaoke</option>
                        <option value="Cover">Cover</option>
                        <option value="Demo">Demo</option>
                        <option value="Mixtape">Mixtape</option>
                        <option value="Banda Sonora">Banda Sonora</option>
                    </select>
                </div>

                <!-- Type -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tipo de Lançamento <span class="text-danger">*</span></label>
                    <select class="form-select" id="type_album">
                        <?php if ($plan_slug === 'single'): ?>
                        <option value="single" selected>Single</option>
                        <?php else: ?>
                        <option value="single">Single</option>
                        <option value="EP">EP</option>
                        <option value="album">Álbum</option>
                        <option value="mixtape">Mixtape</option>
                        <?php endif; ?>
                    </select>
                    <?php if ($plan_slug === 'single'): ?>
                    <div class="form-text"><i class="bi bi-lock me-1"></i>O plano Single distribui apenas singles.</div>
                    <?php endif; ?>
                </div>

                <!-- Language -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Língua do Lançamento <span
                            class="text-danger">*</span></label>
                    <select class="form-select" id="language">
                        <option value="">Seleciona a língua</option>
                        <optgroup label="Línguas Africanas">
                            <option value="pt">🇦🇴 Português (Angola)</option>
                            <option value="pt-br">🇧🇷 Português (Brasil)</option>
                            <option value="pt-pt">🇵🇹 Português (Portugal)</option>
                            <option value="ki">🇦🇴 Kimbundo</option>
                            <option value="kg">🇦🇴 Kikongo</option>
                            <option value="umb">🇦🇴 Umbundu</option>
                            <option value="cjk">🇦🇴 Cokwe</option>
                            <option value="mbunda">🇦🇴 Mbunda</option>
                            <option value="nganguela">🇦🇴 Nganguela</option>
                            <option value="kwanyama">🇦🇴 Kwanyama</option>
                            <option value="xhosa">🇿🇦 Xhosa</option>
                            <option value="zulu">🇿🇦 Zulu</option>
                            <option value="swahili">🇹🇿 Swahili</option>
                            <option value="yoruba">🇳🇬 Yoruba</option>
                            <option value="igbo">🇳🇬 Igbo</option>
                            <option value="hausa">🇳🇬 Hausa</option>
                            <option value="amharic">🇪🇹 Amharic</option>
                            <option value="somalia">🇸🇴 Somali</option>
                        </optgroup>
                        <optgroup label="Línguas Europeias">
                            <option value="en">🇬🇧 Inglês</option>
                            <option value="es">🇪🇸 Espanhol</option>
                            <option value="fr">🇫🇷 Francês</option>
                            <option value="it">🇮🇹 Italiano</option>
                            <option value="de">🇩🇪 Alemão</option>
                            <option value="nl">🇳🇱 Holandês</option>
                            <option value="ru">🇷🇺 Russo</option>
                            <option value="uk">🇺🇦 Ucraniano</option>
                            <option value="pl">🇵🇱 Polaco</option>
                            <option value="cs">🇨🇿 Checo</option>
                            <option value="el">🇬🇷 Grego</option>
                            <option value="sv">🇸🇪 Sueco</option>
                            <option value="da">🇩🇰 Dinamarquês</option>
                            <option value="no">🇳🇴 Norueguês</option>
                            <option value="fi">🇫🇮 Finlandês</option>
                        </optgroup>
                        <optgroup label="Línguas Asiáticas">
                            <option value="zh">🇨🇳 Chinês</option>
                            <option value="ja">🇯🇵 Japonês</option>
                            <option value="ko">🇰🇷 Coreano</option>
                            <option value="hi">🇮🇳 Hindi</option>
                            <option value="th">🇹🇭 Tailandês</option>
                            <option value="vi">🇻🇳 Vietnamita</option>
                            <option value="id">🇮🇩 Indonésio</option>
                            <option value="tl">🇵🇭 Tagalog</option>
                            <option value="ar">🇸🇦 Árabe</option>
                            <option value="he">🇮🇱 Hebraico</option>
                            <option value="tr">🇹🇷 Turco</option>
                            <option value="fa">🇮🇷 Persa</option>
                        </optgroup>
                        <optgroup label="Línguas das Américas">
                            <option value="qu">🇵🇪 Quechua</option>
                            <option value="gn">🇵🇾 Guarani</option>
                            <option value="ay">🇧🇴 Aymara</option>
                            <option value="nah">🇲🇽 Nahuatl</option>
                            <option value="cr">🇨🇦 Cree</option>
                            <option value="iu">🇨🇦 Inuktitut</option>
                        </optgroup>
                        <optgroup label="Outras">
                            <option value="la">Latim</option>
                            <option value="epo">Esperanto</option>
                            <option value="other">Outro</option>
                        </optgroup>
                    </select>
                </div>

                <div class="step-nav">
                    <div></div>
                    <button class="btn btn-sm px-4" style="background:var(--wasom);color:#fff" onclick="goStep(2)">
                        Próximo <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div><!-- /panel-1 -->

            <!-- ════════════════════════════════════
         STEP 2 — Créditos & Metadados
    ════════════════════════════════════ -->
            <div class="step-panel" id="panel-2">
                <h5 class="fw-bold mb-1"><i class="bi bi-people me-2" style="color:var(--wasom)"></i>Créditos &
                    Metadados</h5>
                <p class="text-muted small mb-4">Artistas, género, direitos e informações de selo.</p>

                <!-- Main artists -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Artistas Principais <span class="text-danger">*</span></label>
                    <select class="form-select" id="artists" multiple="multiple">
                        <?php foreach ($user_artists as $a): ?>
                        <option value="<?php echo $a['id_artist']; ?>"
                            data-name="<?php echo htmlspecialchars($a['stage_name']); ?>">
                            <?php echo htmlspecialchars($a['stage_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Escreve o nome do artista. Se não existir, clica em "Criar artista".</div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="btn-create-artist">
                        <i class="bi bi-person-plus me-1"></i>Criar novo artista
                    </button>
                </div>

                <!-- Genre + Subgenre -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Género Principal <span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="genre">
                            <option value="">Seleciona um género</option>
                            <option value="pop">Pop</option>
                            <option value="rock">Rock</option>
                            <option value="hip_hop">Hip-Hop / Rap</option>
                            <option value="r_and_b">R&B / Soul</option>
                            <option value="afrobeats">Afrobeats</option>
                            <option value="semba">Semba</option>
                            <option value="kizomba">Kizomba</option>
                            <option value="kuduro">Kuduro</option>
                            <option value="funaná">Funaná</option>
                            <option value="eletronica">Electrónica</option>
                            <option value="jazz">Jazz</option>
                            <option value="classica">Clássica</option>
                            <option value="gospel">Gospel / Religioso</option>
                            <option value="reggae">Reggae</option>
                            <option value="samba">Samba</option>
                            <option value="funk">Funk</option>
                            <option value="pagode">Pagode</option>
                            <option value="forro">Forró</option>
                            <option value="folk">Folk / Acústico</option>
                            <option value="metal">Metal</option>
                            <option value="alternativo">Alternativo / Indie</option>
                            <option value="country">Country</option>
                            <option value="blues">Blues</option>
                            <option value="latin">Latin</option>
                            <option value="amapiano">Amapiano</option>
                            <option value="dancehall">Dancehall</option>
                            <option value="instrumental">Instrumental</option>
                            <option value="spoken">Palavra Falada / Poesia</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Subgénero</label>
                        <select class="form-select" id="subgenre" disabled>
                            <option value="">Seleciona primeiro o género</option>
                        </select>
                    </div>
                </div>

                <!-- Label name -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Nome do Rótulo (Selo)
                        <?php if (!$can_label): ?>
                        <i class="bi bi-lock-fill text-muted ms-1" title="Não disponível no plano Single"></i>
                        <?php endif; ?>
                    </label>
                    <input type="text" class="form-control" id="label_name" value="102022 WU Records" maxlength="100"
                        <?php echo !$can_label ? 'disabled readonly' : ''; ?> />
                    <?php if (!$can_label): ?>
                    <div class="form-text text-muted">
                        <i class="bi bi-info-circle me-1"></i>O plano Single usa o selo padrão <strong>WU
                            Records</strong>.
                        Faz upgrade para personalizar.
                    </div>
                    <?php else: ?>
                    <div class="form-text">Nome da gravadora ou nome artístico de produção.</div>
                    <?php endif; ?>
                </div>

                <!-- Copyright © -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Direitos Autorais (©)</label>
                    <div class="d-flex align-items-center gap-2">
                        <span class="copyright-prefix">©</span>
                        <select class="form-select" id="copyright-year" style="max-width:110px">
                            <!-- populated by JS -->
                        </select>
                        <div class="copyright-fixed flex-fill"> 102022 WU Records</div>
                    </div>
                    <div class="form-text">Seleciona apenas o ano. O titular é fixo: © [ano] - 102022 WU Records</div>
                </div>

                <!-- Copyright ℗ -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Direitos de Gravação (℗)</label>
                    <div class="d-flex align-items-center gap-2">
                        <span class="copyright-prefix">℗</span>
                        <select class="form-select" id="phonogram-year" style="max-width:110px">
                            <!-- populated by JS -->
                        </select>
                        <div class="copyright-fixed flex-fill"> 102022 WU Records</div>
                    </div>
                </div>

                <div class="step-nav">
                    <button class="btn btn-outline-secondary btn-sm px-4" onclick="goStep(1)">
                        <i class="bi bi-arrow-left me-1"></i>Anterior
                    </button>
                    <button class="btn btn-sm px-4" style="background:var(--wasom);color:#fff" onclick="goStep(3)">
                        Próximo <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div><!-- /panel-2 -->

            <!-- ════════════════════════════════════
         STEP 3 — Faixas
    ════════════════════════════════════ -->
            <div class="step-panel" id="panel-3">
                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>Requisitos dos áudios:</strong>
                    <ul class="mb-0 mt-1">
                        <li>Formato: <strong>WAV ou FLAC</strong></li>
                        <li>Taxa de amostragem: <strong>44.1 kHz</strong></li>
                        <li>Resolução: <strong>16 ou 24 bit</strong></li>
                        <li>Tamanho máximo: <strong>200 MB por faixa</strong></li>
                    </ul>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <h5 class="fw-bold mb-0"><i class="bi bi-music-note-list me-2" style="color:var(--wasom)"></i>Faixas
                    </h5>
                    <span class="badge bg-secondary" id="track-counter">1 / <?php echo $ui_max_tracks; ?></span>
                </div>
                <p class="text-muted small mb-4">
                    Adiciona as faixas do teu lançamento.
                    <?php if ($max_tracks === 1): ?>
                    <strong>O teu plano permite 1 faixa (Single).</strong>
                    <?php elseif ($max_tracks): ?>
                    Máximo de <strong><?php echo $max_tracks; ?> faixas</strong> no teu plano.
                    <?php else: ?>
                    O teu plano <?php echo htmlspecialchars($plan['name_plan']); ?> não tem limite de faixas.
                    <?php endif; ?>
                </p>

                <!-- Track list container -->
                <div id="tracks-container"></div>

                <!-- Add track button -->
                <?php if ($max_tracks !== 1): ?>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-add-track"
                        onclick="addTrack()">
                        <i class="bi bi-plus-circle me-1"></i>Adicionar Faixa
                    </button>
                </div>
                <?php endif; ?>

                <div class="step-nav">
                    <button class="btn btn-outline-secondary btn-sm px-4" onclick="goStep(2)">
                        <i class="bi bi-arrow-left me-1"></i>Anterior
                    </button>
                    <button class="btn btn-sm px-4" style="background:var(--wasom);color:#fff" onclick="goStep(4)">
                        Próximo <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div><!-- /panel-3 -->

            <!-- ════════════════════════════════════
     STEP 4 — Distribuição
════════════════════════════════════ -->
            <div class="step-panel" id="panel-4">
                <h5 class="fw-bold mb-1"><i class="bi bi-calendar-event me-2"
                        style="color:var(--wasom)"></i>Distribuição</h5>
                <p class="text-muted small mb-4">Define quando e onde o teu lançamento estará disponível.</p>

                <!-- Release date -->
                <div class="alert alert-info small d-flex gap-2 mb-4">
                    <i class="bi bi-lightbulb-fill flex-shrink-0 mt-1"></i>
                    <div>Recomendamos uma data de lançamento com <strong>pelo menos 2 a 3 dias de antecedência</strong>.
                        Isso garante a entrega a tempo e melhora as tuas hipóteses de marketing e listagem em playlists.
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Data de Lançamento <span
                                class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="release-date" min="" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Hora de Lançamento</label>
                        <input type="time" class="form-control" id="release-time" value="00:00" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Fuso Horário</label>
                        <select class="form-select" id="release-timezone">
                            <option value="Africa/Luanda" selected>WAT (Angola)</option>
                            <option value="UTC">UTC</option>
                            <option value="Europe/Lisbon">WET (Lisboa)</option>
                            <option value="America/Sao_Paulo">BRT (Brasil)</option>
                        </select>
                    </div>
                </div>

                <!-- Territory -->
                <div class="mb-4 p-3 rounded"
                    style="background:rgba(25,135,84,.06);border:1px solid rgba(25,135,84,.2)">
                    <div class="d-flex gap-2">
                        <i class="bi bi-globe text-success flex-shrink-0 mt-1 fs-5"></i>
                        <div>
                            <div class="fw-semibold small">Direitos Territoriais — Worldwide</div>
                            <div class="text-muted" style="font-size:.8rem">
                                Este lançamento será distribuído para todos os territórios actuais e futuros do mundo,
                                sem restrições geográficas. Caso necessite de restrições específicas, contacta o
                                suporte.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Store selection -->
                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <label class="form-label fw-semibold mb-0">Selecção de Parceiros <span
                                class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllStores()">
                                <i class="bi bi-check-all me-1"></i>Seleccionar todos
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="deselectAllStores()">
                                <i class="bi bi-x me-1"></i>Nenhum
                            </button>
                        </div>
                    </div>

                    <!-- Navegação por regiões -->
                    <ul class="nav nav-tabs mb-3" id="storeRegionsTab" role="tablist">
                        <?php $active = 'active'; ?>
                        <?php foreach ($stores_by_region as $region => $stores_list): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $active; ?>" id="tab-<?php echo md5($region); ?>"
                                data-bs-toggle="tab" data-bs-target="#region-<?php echo md5($region); ?>" type="button"
                                role="tab" aria-controls="region-<?php echo md5($region); ?>"
                                aria-selected="<?php echo $active === 'active' ? 'true' : 'false'; ?>">
                                <?php echo htmlspecialchars($region); ?>
                            </button>
                        </li>
                        <?php $active = ''; ?>
                        <?php endforeach; ?>
                    </ul>

                    <!-- Conteúdo das abas -->
                    <div class="tab-content" id="storeRegionsTabContent">
                        <?php $active = 'show active'; ?>
                        <?php foreach ($stores_by_region as $region => $stores_list): ?>
                        <div class="tab-pane fade <?php echo $active; ?>" id="region-<?php echo md5($region); ?>"
                            role="tabpanel" aria-labelledby="tab-<?php echo md5($region); ?>">
                            <div class="row g-2" id="stores-grid-<?php echo md5($region); ?>">
                                <?php foreach ($stores_list as $store): ?>
                                <?php
$slug  = $store['slug_store'];
$si    = $store_icons[$slug] ?? $store_icons['default'];
?>
                                <div class="col-4 col-md-3 col-lg-2">
                                    <div class="store-card selected" data-store-id="<?php echo $store['id_store']; ?>"
                                        onclick="toggleStore(this)">
                                        <i class="store-check bi bi-check-circle-fill"></i>
                                        <div class="store-icon">
                                            <i class="bi <?php echo $si['icon']; ?>"
                                                style="color:<?php echo $si['color']; ?>"></i>
                                        </div>
                                        <div class="store-name"><?php echo htmlspecialchars($store['name_store']); ?>
                                        </div>
                                        <input type="checkbox" class="d-none store-checkbox" checked
                                            value="<?php echo $store['id_store']; ?>" />
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php $active = ''; ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-text mt-2">
                        <span id="stores-selected-count"><?php echo count($stores_raw); ?></span> plataforma(s)
                        seleccionada(s)
                    </div>
                </div>

                <div class="step-nav">
                    <button class="btn btn-outline-secondary btn-sm px-4" onclick="goStep(3)">
                        <i class="bi bi-arrow-left me-1"></i>Anterior
                    </button>
                    <button class="btn btn-sm px-4" style="background:var(--wasom);color:#fff" onclick="goStep(5)">
                        Próximo <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div><!-- /panel-4 -->

            <!-- ════════════════════════════════════
         STEP 5 — Revisão & Publicar
    ════════════════════════════════════ -->
            <div class="step-panel" id="panel-5">
                <h5 class="fw-bold mb-1"><i class="bi bi-send me-2" style="color:var(--wasom)"></i>Revisão & Publicar
                </h5>
                <p class="text-muted small mb-4">Confirma os detalhes antes de distribuir.</p>

                <!-- Summary -->
                <div class="card mb-4" id="review-summary">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3 text-center">
                                <img id="rev-cover" src="" alt="Capa" class="rounded"
                                    style="width:100%;max-width:120px;aspect-ratio:1/1;object-fit:cover" />
                            </div>
                            <div class="col-md-9">
                                <div class="review-row"><span class="review-label">Título</span><span
                                        class="review-value" id="rev-title">—</span></div>
                                <div class="review-row"><span class="review-label">Tipo / Versão</span><span
                                        class="review-value" id="rev-type">—</span></div>
                                <div class="review-row"><span class="review-label">Artistas</span><span
                                        class="review-value" id="rev-artists">—</span></div>
                                <div class="review-row"><span class="review-label">Género</span><span
                                        class="review-value" id="rev-genre">—</span></div>
                                <div class="review-row"><span class="review-label">Faixas</span><span
                                        class="review-value" id="rev-tracks">—</span></div>
                                <div class="review-row"><span class="review-label">Data de Lançamento</span><span
                                        class="review-value" id="rev-date">—</span></div>
                                <div class="review-row"><span class="review-label">Plataformas</span><span
                                        class="review-value" id="rev-stores">—</span></div>
                                <div class="review-row"><span class="review-label">Plano</span><span
                                        class="review-value"><?php echo htmlspecialchars($plan['name_plan']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms -->
                <div class="alert alert-secondary d-flex gap-2 mb-4">
                    <i class="bi bi-shield-check flex-shrink-0 mt-1 fs-5"></i>
                    <div style="font-size:.85rem">
                        <strong>Ao distribuir este lançamento, concordas em cumprir todos os nossos
                            <a href="<?php  echo APP_URL .'/'. APP_URL_PANEL ?>/page/politicies/terms"
                                style="color:var(--wasom)">Termos
                                e
                                Condições</a> bem como as nossas
                            <a href="#" style="color:var(--wasom)">Políticas de Privacidade</a>.</strong>
                        <p class="mb-0 mt-1 text-muted">Confirmas que és titular dos direitos sobre todo o conteúdo
                            deste lançamento
                            e que não infringe direitos de terceiros.</p>
                    </div>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="terms-check" />
                    <label class="form-check-label fw-semibold" for="terms-check">
                        Confirmo que li e aceito os Termos e Políticas de Privacidade
                    </label>
                </div>

                <div class="step-nav">
                    <button class="btn btn-outline-secondary btn-sm px-4" onclick="goStep(4)">
                        <i class="bi bi-arrow-left me-1"></i>Anterior
                    </button>
                    <button type="button" class="btn px-5 fw-bold" style="background:var(--wasom);color:#fff"
                        id="btn-distribute" onclick="submitRelease()">
                        <i class="bi bi-send-fill me-2"></i>Distribuir
                    </button>
                </div>
            </div><!-- /panel-5 -->

        </div><!-- /card -->
    </div><!-- /container -->

    <!-- ════════════════════════════════════
     MODAL — Criar Artista
════════════════════════════════════ -->
    <div class="modal fade" id="createArtistModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#FF0089,#FF4D4D);color:#fff">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-plus fs-4"></i>
                        <div>
                            <h5 class="modal-title mb-0">Criar Perfil de Artista</h5>
                            <small style="opacity:.8">Este artista ficará disponível para futuros lançamentos</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Artist photo -->
                    <div class="text-center mb-4">
                        <div class="d-inline-block position-relative">
                            <div id="artist-photo-preview"
                                class="rounded-circle d-flex align-items-center justify-content-center"
                                style="width:100px;height:100px;background:#f1f3f5;border:2px dashed #dee2e6;cursor:pointer"
                                onclick="document.getElementById('artist-photo-input').click()">
                                <i class="bi bi-camera fs-3 text-muted" id="artist-photo-icon"></i>
                                <img id="artist-photo-img" src="" class="d-none rounded-circle"
                                    style="width:100px;height:100px;object-fit:cover" />
                            </div>
                        </div>
                        <div class="mt-2 text-muted small">Foto do artista (opcional)</div>
                        <input type="file" id="artist-photo-input" accept="image/*" class="d-none" />
                    </div>

                    <div class="row g-3">
                        <!-- Nome Artístico -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Nome Artístico <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="artist-name"
                                placeholder="Nome como aparece nas plataformas" maxlength="100" />
                        </div>
                        <!-- Nome Real -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nome Real (opcional)</label>
                            <input type="text" class="form-control" id="artist-real-name"
                                placeholder="Nome real do artista" />
                        </div>
                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Email do Artista <span
                                    class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="artist-email" placeholder="email@artista.com"
                                maxlength="255" />
                            <div class="form-text">Usado para notificações enviadas ao artista.</div>
                        </div>
                        <!-- Função Habitual -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Função Habitual</label>
                            <select class="form-select" id="artist-role">
                                <option value="">Seleciona a função principal</option>
                                <option value="main_artist">Artista Principal</option>
                                <option value="featured">Artista Convidado (Feat.)</option>
                                <option value="composer">Compositor</option>
                                <option value="producer">Produtor</option>
                                <option value="lyricist">Letrista</option>
                                <option value="arranger">Arranjador</option>
                                <option value="executive_producer">Produtor Executivo</option>
                                <option value="co_producer">Co-Produtor</option>
                                <option value="beatmaker">Beatmaker</option>
                                <option value="recording_engineer">Engenheiro de Gravação</option>
                                <option value="mixing_engineer">Engenheiro de Mistura</option>
                                <option value="mastering_engineer">Engenheiro de Masterização</option>
                                <option value="sound_designer">Designer de Som</option>
                                <option value="publisher">Editora</option>
                                <option value="copyright_holder">Detentor dos Direitos</option>
                                <option value="label">Selo/Gravadora</option>
                                <option value="cover_designer">Designer da Capa</option>
                                <option value="photographer">Fotógrafo</option>
                                <option value="guitarist">Guitarrista</option>
                                <option value="bassist">Baixista</option>
                                <option value="drummer">Baterista</option>
                                <option value="keyboardist">Tecladista</option>
                                <option value="percussionist">Percussionista</option>
                                <option value="strings">Cordas</option>
                                <option value="brass">Metais</option>
                                <option value="other">Outro</option>
                            </select>
                        </div>
                        <!-- Género Principal -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Género Principal</label>
                            <select class="form-select" id="artist-genre">
                                <option value="">Selecionar género...</option>
                                <option value="Afrobeats">Afrobeats</option>
                                <option value="Afropop">Afropop</option>
                                <option value="Kizomba">Kizomba</option>
                                <option value="Semba">Semba</option>
                                <option value="Kuduro">Kuduro</option>
                                <option value="Pop">Pop</option>
                                <option value="Hip-Hop">Hip-Hop</option>
                                <option value="R&B">R&B</option>
                                <option value="Rock">Rock</option>
                                <option value="Electrónica">Electrónica</option>
                                <option value="Reggaeton">Reggaeton</option>
                                <option value="Gospel">Gospel</option>
                                <option value="Jazz">Jazz</option>
                                <option value="Soul">Soul</option>
                                <option value="Outro">Outro</option>
                            </select>
                        </div>
                        <!-- Género Secundário -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Género Secundário</label>
                            <select class="form-select" id="artist-genre-secondary">
                                <option value="">Selecionar género...</option>
                                <option value="Afrobeats">Afrobeats</option>
                                <option value="Afropop">Afropop</option>
                                <option value="Kizomba">Kizomba</option>
                                <option value="Semba">Semba</option>
                                <option value="Kuduro">Kuduro</option>
                                <option value="Pop">Pop</option>
                                <option value="Hip-Hop">Hip-Hop</option>
                                <option value="R&B">R&B</option>
                                <option value="Rock">Rock</option>
                                <option value="Electrónica">Electrónica</option>
                                <option value="Reggaeton">Reggaeton</option>
                                <option value="Gospel">Gospel</option>
                                <option value="Jazz">Jazz</option>
                                <option value="Soul">Soul</option>
                                <option value="Outro">Outro</option>
                            </select>
                        </div>
                        <!-- País e Cidade -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">País</label>
                            <input type="text" class="form-control" id="artist-country" placeholder="ex: Angola"
                                maxlength="60" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Cidade</label>
                            <input type="text" class="form-control" id="artist-city" placeholder="ex: Luanda"
                                maxlength="60" />
                        </div>
                        <!-- Biografia -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Biografia (opcional)</label>
                            <textarea class="form-control" id="artist-bio" rows="3" maxlength="500"
                                placeholder="Breve descrição do artista..."></textarea>
                        </div>
                        <!-- Spotify URL -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Spotify URL</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#1db954;border-color:#1db954">
                                    <i class="bi bi-spotify text-white"></i>
                                </span>
                                <input type="url" class="form-control" id="artist-spotify"
                                    placeholder="https://open.spotify.com/artist/..." />
                            </div>
                        </div>
                        <!-- Apple Music URL -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Apple Music URL</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#fc3c44;border-color:#fc3c44">
                                    <i class="bi bi-apple text-white"></i>
                                </span>
                                <input type="url" class="form-control" id="artist-apple"
                                    placeholder="https://music.apple.com/..." />
                            </div>
                        </div>
                        <!-- YouTube URL -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">YouTube URL</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#ff0000;border-color:#ff0000">
                                    <i class="bi bi-youtube text-white"></i>
                                </span>
                                <input type="url" class="form-control" id="artist-youtube"
                                    placeholder="https://youtube.com/@..." />
                            </div>
                        </div>
                    </div>
                    <div id="artist-create-feedback" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn" style="background:var(--wasom);color:#fff" id="btn-save-artist"
                        onclick="saveArtist()">
                        <span id="save-artist-text"><i class="bi bi-check me-1"></i>Criar Artista</span>
                        <span id="save-artist-load" class="d-none"><span
                                class="spinner-border spinner-border-sm me-1"></span>A criar...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════
     MODAL — Cropper
════════════════════════════════════ -->
    <div class="modal fade" id="cropperModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-crop me-2"></i>Recortar Imagem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-2" style="background:#1a1a1a">
                    <img id="cropper-img" src="" style="max-width:100%" />
                </div>
                <div class="modal-footer">
                    <div class="text-muted small me-auto"><i class="bi bi-info-circle me-1"></i>Arrasta e redimensiona
                        para enquadrar a capa</div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn" style="background:var(--wasom);color:#fff" id="btn-crop-confirm">
                        <i class="bi bi-check me-1"></i>Aplicar Recorte
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════
     TRACK CARD TEMPLATE (hidden)
════════════════════════════════════ -->
    <template id="track-template">
        <div class="track-card" data-track-index="">
            <div class="track-number-badge">Faixa <span class="track-num-label">1</span></div>
            <button type="button" class="btn-remove-track d-none" onclick="removeTrack(this)" title="Eliminar faixa">
                <i class="bi bi-trash"></i>
            </button>
            <div class="row g-3 mt-1">
                <!-- Title -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Título da Faixa <span
                            class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm track-title" maxlength="150"
                        placeholder="Título exacto" />
                </div>
                <!-- Mix Version -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Versão do Mix <span
                            class="text-muted">(opcional)</span></label>
                    <select class="form-select form-select-sm track-mix-version">
                        <optgroup label="Versões Comuns">
                            <option value="Remix">Remix</option>
                            <option value="Extended Mix">Extended Mix</option>
                            <option value="Radio Edit">Radio Edit</option>
                            <option value="Instrumental">Instrumental</option>
                            <option value="Acapella">Acapella</option>
                            <option value="Dub Mix">Dub Mix</option>
                            <option value="Club Mix">Club Mix</option>
                            <option value="VIP Mix">VIP Mix</option>
                        </optgroup>
                        <optgroup label="Versões ao Vivo">
                            <option value="Ao Vivo">Ao Vivo</option>
                            <option value="Live Session">Live Session</option>
                            <option value="Piano Acústico">Piano Acústico</option>
                            <option value="Unplugged">Unplugged</option>
                        </optgroup>
                        <optgroup label="Versões Especiais">
                            <option value="Acústico">Acústico</option>
                            <option value="Sped Up">Sped Up</option>
                            <option value="Slowed">Slowed</option>
                            <option value="Rework">Rework</option>
                            <option value="Orquestral">Orquestral</option>
                            <option value="Karaoke">Karaoke</option>
                            <option value="DJ Tool">DJ Tool</option>
                            <option value="Demo">Demo</option>
                        </optgroup>
                    </select>
                </div>
                <!-- Main artists (Select2) -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Artistas Principais <span
                            class="text-danger">*</span></label>
                    <select class="form-select form-select-sm artist-select track-main-artists" multiple="multiple"
                        data-placeholder="Seleciona artistas..." style="width:100%">
                    </select>
                </div>
                <!-- Featuring (Select2) -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Artistas Participantes (Feat.)</label>
                    <select class="form-select form-select-sm artist-select track-feat" multiple="multiple"
                        data-placeholder="Seleciona feat..." style="width:100%">
                    </select>
                </div>
                <!-- Composers (Select2) -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Compositores <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm artist-select track-composers" multiple="multiple"
                        data-placeholder="Seleciona compositores..." style="width:100%">
                    </select>
                </div>
                <!-- Producers (Select2) -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Produtores <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm artist-select track-producers" multiple="multiple"
                        data-placeholder="Seleciona produtores..." style="width:100%">
                    </select>
                </div>
                <!-- Language -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Língua da Faixa</label>
                    <select class="form-select form-select-sm track-language">
                        <optgroup label="Línguas Africanas">
                            <option value="pt">🇦🇴 Português (Angola)</option>
                            <option value="pt-br">🇧🇷 Português (Brasil)</option>
                            <option value="pt-pt">🇵🇹 Português (Portugal)</option>
                            <option value="ki">🇦🇴 Kimbundo</option>
                            <option value="kg">🇦🇴 Kikongo</option>
                            <option value="umb">🇦🇴 Umbundu</option>
                            <option value="cjk">🇦🇴 Cokwe</option>
                            <option value="mbunda">🇦🇴 Mbunda</option>
                            <option value="nganguela">🇦🇴 Nganguela</option>
                            <option value="kwanyama">🇦🇴 Kwanyama</option>
                            <option value="xhosa">🇿🇦 Xhosa</option>
                            <option value="zulu">🇿🇦 Zulu</option>
                            <option value="swahili">🇹🇿 Swahili</option>
                            <option value="yoruba">🇳🇬 Yoruba</option>
                            <option value="igbo">🇳🇬 Igbo</option>
                            <option value="hausa">🇳🇬 Hausa</option>
                            <option value="amharic">🇪🇹 Amharic</option>
                            <option value="somalia">🇸🇴 Somali</option>
                        </optgroup>
                        <optgroup label="Línguas Europeias">
                            <option value="en">🇬🇧 Inglês</option>
                            <option value="es">🇪🇸 Espanhol</option>
                            <option value="fr">🇫🇷 Francês</option>
                            <option value="it">🇮🇹 Italiano</option>
                            <option value="de">🇩🇪 Alemão</option>
                            <option value="nl">🇳🇱 Holandês</option>
                            <option value="ru">🇷🇺 Russo</option>
                            <option value="uk">🇺🇦 Ucraniano</option>
                            <option value="pl">🇵🇱 Polaco</option>
                            <option value="cs">🇨🇿 Checo</option>
                            <option value="el">🇬🇷 Grego</option>
                            <option value="sv">🇸🇪 Sueco</option>
                            <option value="da">🇩🇰 Dinamarquês</option>
                            <option value="no">🇳🇴 Norueguês</option>
                            <option value="fi">🇫🇮 Finlandês</option>
                        </optgroup>
                        <optgroup label="Línguas Asiáticas">
                            <option value="zh">🇨🇳 Chinês</option>
                            <option value="ja">🇯🇵 Japonês</option>
                            <option value="ko">🇰🇷 Coreano</option>
                            <option value="hi">🇮🇳 Hindi</option>
                            <option value="th">🇹🇭 Tailandês</option>
                            <option value="vi">🇻🇳 Vietnamita</option>
                            <option value="id">🇮🇩 Indonésio</option>
                            <option value="tl">🇵🇭 Tagalog</option>
                            <option value="ar">🇸🇦 Árabe</option>
                            <option value="he">🇮🇱 Hebraico</option>
                            <option value="tr">🇹🇷 Turco</option>
                            <option value="fa">🇮🇷 Persa</option>
                        </optgroup>
                        <optgroup label="Línguas das Américas">
                            <option value="qu">🇵🇪 Quechua</option>
                            <option value="gn">🇵🇾 Guarani</option>
                            <option value="ay">🇧🇴 Aymara</option>
                            <option value="nah">🇲🇽 Nahuatl</option>
                            <option value="cr">🇨🇦 Cree</option>
                            <option value="iu">🇨🇦 Inuktitut</option>
                        </optgroup>
                        <optgroup label="Outras">
                            <option value="la">Latim</option>
                            <option value="epo">Esperanto</option>
                            <option value="other">Outro</option>
                        </optgroup>
                    </select>
                </div>
                <!-- Recording date -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Data de Gravação</label>
                    <input type="date" class="form-control form-control-sm track-recording-date" />
                </div>
                <!-- Explicit -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Conteúdo Explícito?</label>
                    <select class="form-select form-select-sm track-explicit">
                        <option value="NO">Não</option>
                        <option value="YES">Sim</option>
                    </select>
                </div>
                <!-- ISRC -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">ISRC <span class="text-muted">(opcional)</span></label>
                    <input type="text" class="form-control form-control-sm track-isrc" maxlength="15"
                        placeholder="ex: AOXX12345678" style="letter-spacing:.05em;text-transform:uppercase" />
                    <div class="form-text" style="font-size:.7rem">Deixa em branco — iremos atribuir automaticamente.
                    </div>
                </div>
                <!-- Dentro do template, após o ISRC -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">
                        Arquivo de Áudio <span class="text-danger">* (WAV/FLAC)</span>
                        <i class="bi bi-info-circle ms-1" data-bs-toggle="tooltip"
                            title="Formato WAV ou FLAC, 16/24 bit, 44.1kHz"></i>
                    </label>
                    <div class="input-group">
                        <input type="file" class="form-control form-control-sm track-audio"
                            accept=".wav,.flac,audio/wav,audio/flac,audio/x-flac" required />
                        <button class="btn btn-outline-secondary btn-sm track-audio-clear" type="button"
                            style="display:none" onclick="clearAudioFile(this)">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div class="form-text progress-track" style="font-size:.7rem">
                        <span class="audio-filename"></span>
                        <span class="audio-size text-muted"></span>
                        <span class="audio-progress d-none">
                            <span class="spinner-border spinner-border-sm me-1"></span>A enviar...
                        </span>
                    </div>
                    <div class="audio-error text-danger small d-none"></div>
                </div>
            </div>
        </div>
    </template>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <script>
    // ── Constantes injectadas pelo PHP ──────────────
    const CSRF = '<?php echo $csrf; ?>';
    const BASE_URL = '<?php echo APP_URL . '/' . APP_URL_PANEL ?>';
    const PLAN_SLUG = '<?php echo $plan_slug; ?>';
    const MAX_TRACKS = <?php echo $max_tracks ?? 'null'; ?>;
    const UI_MAX_TRACKS = <?php echo $ui_max_tracks; ?>;
    const CAN_LABEL = <?php echo $can_label ? 'true' : 'false'; ?>;
    const USER_ARTISTS = <?php echo $artists_json; ?>;
    const STORES_DATA = <?php echo $stores_json; ?>;
    const DRAFT_KEY = 'wasom_drafts_<?php echo $id_users; ?>';
    const DRAFT_FROM_DB = <?php echo $draft_from_db ? json_encode([
        'album'  => $draft_from_db,
        'tracks' => $draft_tracks ?? [],
        'stores' => $draft_stores ?? []
    ]) : 'null'; ?>;
    </script>
    <script src="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/launch/js/creat-release.js"></script>

</body>

</html>