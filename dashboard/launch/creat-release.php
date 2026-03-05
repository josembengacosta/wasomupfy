<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Criar Lançamento
// Arquivo: dashboard/launch/creat-release.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$user = getUserById((int)$_SESSION['id_users']);
if (!$user) {
    session_destroy();
    redirect('/login', ['error' => 'csrf']);
}

$id_users   = (int)$user['id_users'];
$first_name = htmlspecialchars($user['first_name']);
$user_name  = htmlspecialchars($user['user_name'] ?? '');
$db         = getDB();

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
        redirect('/dashboard/launch/releases', ['error' => 'draft_not_found']);
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

// ── Lojas digitais ────────────────────────────
$stores_stmt = $db->query("SELECT id_store, name_store, slug_store FROM _store WHERE is_active = 1 ORDER BY display_order");
$stores = $stores_stmt->fetchAll(PDO::FETCH_ASSOC);
$stores_json = json_encode($stores);

// ── Session data (logout modal) ───────────────
$ls = $db->prepare('SELECT last_login_at FROM _users_security WHERE id_users = ?');
$ls->execute([$id_users]);
$sec = $ls->fetch();
$last_login_str = ($sec && $sec['last_login_at']) ? date('d/m/Y H:i', strtotime($sec['last_login_at'])) : '—';

$csrf = htmlspecialchars($_SESSION['csrf_token']);

// ── Store icons map ───────────────────────────
$store_icons = [
    'spotify'       => ['icon' => 'bi-spotify',      'color' => '#1db954'],
    'apple-music'   => ['icon' => 'bi-apple',         'color' => '#fc3c44'],
    'amazon-music'  => ['icon' => 'bi-amazon',        'color' => '#ff9900'],
    'deezer'        => ['icon' => 'bi-music-note',    'color' => '#ef5466'],
    'tidal'         => ['icon' => 'bi-water',         'color' => '#00ffff'],
    'boomplay'      => ['icon' => 'bi-headphones',    'color' => '#f85d2f'],
    'youtube-music' => ['icon' => 'bi-youtube',       'color' => '#ff0000'],
    'itunes'        => ['icon' => 'bi-bag-music',     'color' => '#ea4cc0'],
    'pandora'       => ['icon' => 'bi-broadcast',     'color' => '#3668ff'],
    'resso'         => ['icon' => 'bi-music-player',  'color' => '#ff4040'],
    'claro-music'   => ['icon' => 'bi-music-note-beamed', 'color' => '#e30613'],
    'tiktok'        => ['icon' => 'bi-tiktok',        'color' => '#000000'],
    'facebook'      => ['icon' => 'bi-facebook',      'color' => '#1877f2'],
    'snapchat'      => ['icon' => 'bi-snapchat',      'color' => '#fffc00'],
    'youtube'       => ['icon' => 'bi-youtube',       'color' => '#ff0000'],
];

ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <title>Novo Lançamento — Wasom Upfy</title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <!-- Cropper.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" />
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <!-- Dashboard CSS -->
    <link rel="stylesheet" href="../../css/dashboard-style.css" />
    <link rel="stylesheet" href="../../css/lastest-style.css" />
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
            <a class="navbar-brand" href="../painel">
                <span class="text-light fw-bold" style="font-family:Arial,sans-serif">WASOM UPFY</span>
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
                    <div class="row g-2" id="stores-grid">
                        <?php foreach ($stores as $store): ?>
                        <?php
                            $slug = $store['slug_store'];
                            $icon = $store_icons[$slug] ?? ['icon' => 'bi-music-note', 'color' => '#888'];
                            ?>
                        <div class="col-4 col-md-3 col-lg-2">
                            <div class="store-card selected" data-store-id="<?php echo $store['id_store']; ?>"
                                onclick="toggleStore(this)">
                                <i class="store-check bi bi-check-circle-fill"></i>
                                <div class="store-icon"><i class="bi <?php echo $icon['icon']; ?>"
                                        style="color:<?php echo $icon['color']; ?>"></i></div>
                                <div class="store-name"><?php echo htmlspecialchars($store['name_store']); ?></div>
                                <input type="checkbox" class="d-none store-checkbox" checked
                                    value="<?php echo $store['id_store']; ?>" />
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-text mt-2"><span id="stores-selected-count"><?php echo count($stores); ?></span>
                        plataforma(s) seleccionada(s)</div>
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
                            <a href="#" style="color:var(--wasom)">Termos e Condições</a> bem como as nossas
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
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Nome Artístico <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="artist-name"
                                placeholder="Nome como aparece nas plataformas" maxlength="100" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nome Real (opcional)</label>
                            <input type="text" class="form-control" id="artist-real-name"
                                placeholder="Nome real do artista" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Email do Artista <span
                                    class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="artist-email" placeholder="email@artista.com"
                                maxlength="255" />
                            <div class="form-text">Usado para notificações enviadas ao artista.</div>
                        </div>
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
                <!-- Main artists -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Artistas Principais <span
                            class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm track-main-artists"
                        placeholder="Artista A, Artista B (separados por vírgula)" />
                </div>
                <!-- Featuring -->
                <div class=" col-md-6">
                    <label class="form-label fw-semibold small">Artistas Participantes (Feat.)</label>
                    <input type="text" class="form-control form-control-sm track-feat"
                        placeholder="ex: DJ Kwame, Bruna" />
                </div>
                <!-- Composers -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Compositores <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm track-composers"
                        placeholder="ex: José Mbenga, Ana Costa" />
                </div>
                <!-- Producers -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Produtores <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm track-producers"
                        placeholder="ex: DJ KP Beats" />
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
    <script src="../../js/theme.wp.js"></script>
    <script src="../../js/wp.tools.js"></script>

    <script>
    // ═══════════════════════════════════════════════
    // CONFIG — PHP data injected
    // ═══════════════════════════════════════════════
    const CSRF = '<?php echo $csrf; ?>';
    const BASE_URL = '<?php echo rtrim(APP_URL, '/'); ?>';
    const PLAN_SLUG = '<?php echo $plan_slug; ?>';
    const MAX_TRACKS = <?php echo $max_tracks ?? 'null'; ?>;
    const UI_MAX_TRACKS = <?php echo $ui_max_tracks; ?>;
    const CAN_LABEL = <?php echo $can_label ? 'true' : 'false'; ?>;
    const USER_ARTISTS = <?php echo $artists_json; ?>;
    const STORES_DATA = <?php echo $stores_json; ?>;

    toastr.options = {
        progressBar: true,
        closeButton: true,
        positionClass: 'toast-top-right',
        timeOut: 3000
    };


    // ═══════════════════════════════════════════════
    // CARREGAR RASCUNHO (localStorage OU Banco de Dados)
    // ═══════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);

        // Verificar se é rascunho da BD
        if (urlParams.has('draft') && DRAFT_FROM_DB) {
            console.log('A carregar rascunho da base de dados:', DRAFT_FROM_DB);
            preencherDraftFromDB(DRAFT_FROM_DB);
            toastr.success('Rascunho carregado da base de dados!');
        }
        // Verificar se é rascunho do localStorage
        else if (urlParams.has('local_draft')) {
            const draftId = urlParams.get('local_draft');
            const drafts = JSON.parse(localStorage.getItem(DRAFT_KEY) || '[]');
            const draft = drafts.find(d => d.id === draftId);

            if (draft) {
                console.log('A carregar rascunho do localStorage:', draft);
                setTimeout(() => preencherRascunho(draft), 500);
                toastr.success('Rascunho local carregado!');
            }
        }
    });

    // Função para preencher rascunho da BD
    function preencherDraftFromDB(draft) {
        const album = draft.album;
        const tracks = draft.tracks || [];
        const stores = draft.stores || [];

        // STEP 1 - Informações básicas
        if (album.title_album) {
            // Extrair título e versão (se houver)
            const titleMatch = album.title_album.match(/^(.*?)(?:\s*\((.*?)\))?$/);
            document.getElementById('title').value = titleMatch[1].trim();
            if (titleMatch[2]) {
                // Encontrar a opção da versão
                const versionSelect = document.getElementById('version');
                const option = Array.from(versionSelect.options).find(opt => opt.value === titleMatch[2]);
                if (option) versionSelect.value = titleMatch[2];
            }
        }

        if (album.type_album) document.getElementById('type_album').value = album.type_album;
        if (album.language) document.getElementById('language').value = album.language;

        // STEP 2 - Créditos
        if (album.id_artist) {
            // Selecionar artista no Select2
            $('#artists').val([album.id_artist]).trigger('change');
        }

        if (album.genre_main) {
            document.getElementById('genre').value = album.genre_main;
            document.getElementById('genre').dispatchEvent(new Event('change'));

            setTimeout(() => {
                if (album.genre_secondary) {
                    document.getElementById('subgenre').value = album.genre_secondary;
                }
            }, 200);
        }

        if (album.label_name) document.getElementById('label_name').value = album.label_name;

        // Copyright - extrair anos
        if (album.copyright_c) {
            const yearMatch = album.copyright_c.match(/\d{4}/);
            if (yearMatch) document.getElementById('copyright-year').value = yearMatch[0];
        }
        if (album.copyright_p) {
            const yearMatch = album.copyright_p.match(/\d{4}/);
            if (yearMatch) document.getElementById('phonogram-year').value = yearMatch[0];
        }

        // STEP 3 - Faixas
        if (tracks.length > 0) {
            // Remover faixas existentes
            document.getElementById('tracks-container').innerHTML = '';
            trackCount = 0;

            // Adicionar faixas do rascunho
            function adicionarFaixaComDelay(index) {
                if (index >= tracks.length) return;

                if (index > 0) addTrack();

                setTimeout(() => {
                    const cards = document.querySelectorAll('.track-card');
                    const card = cards[index];
                    if (!card) return;

                    const track = tracks[index];

                    if (track.title_track) card.querySelector('.track-title').value = track.title_track;
                    if (track.name_author) card.querySelector('.track-main-artists').value = track.name_author;
                    if (track.name_author_feat) card.querySelector('.track-feat').value = track
                        .name_author_feat;
                    if (track.name_composer) card.querySelector('.track-composers').value = track.name_composer;
                    if (track.name_producer) card.querySelector('.track-producers').value = track.name_producer;
                    if (track.language) card.querySelector('.track-language').value = track.language;
                    if (track.recording_date) card.querySelector('.track-recording-date').value = track
                        .recording_date;
                    if (track.explicit) card.querySelector('.track-explicit').value = track.explicit;
                    if (track.isrc) card.querySelector('.track-isrc').value = track.isrc;

                    // Se houver mix_version no título, tentar extrair
                    if (track.title_track) {
                        const versionMatch = track.title_track.match(/\((.*?)\)$/);
                        if (versionMatch) {
                            const versionSelect = card.querySelector('.track-mix-version');
                            const option = Array.from(versionSelect.options).find(opt => opt.value ===
                                versionMatch[1]);
                            if (option) versionSelect.value = versionMatch[1];
                        }
                    }

                    adicionarFaixaComDelay(index + 1);
                }, 300);
            }

            adicionarFaixaComDelay(0);
        }

        // STEP 4 - Distribuição
        if (album.release_date) document.getElementById('release-date').value = album.release_date;

        // Stores
        if (stores.length > 0) {
            // Desmarcar todas
            document.querySelectorAll('.store-card').forEach(c => {
                c.classList.remove('selected');
                c.querySelector('.store-checkbox').checked = false;
            });

            // Marcar as do draft
            stores.forEach(storeId => {
                const card = document.querySelector(`.store-card[data-store-id="${storeId}"]`);
                if (card) {
                    card.classList.add('selected');
                    card.querySelector('.store-checkbox').checked = true;
                }
            });
            updateStoreCount();
        }

        // Se houver capa, mostrar mensagem
        if (album.img_cover) {
            toastr.info('A capa será mantida. Podes substituir se desejares.');
        }

        updateTrackUI();
    }

    // ═══════════════════════════════════════════════
    // STEP NAVIGATION
    // ═══════════════════════════════════════════════
    let currentStep = 1;
    const TOTAL_STEPS = 5;

    function goStep(n) {
        if (n < 1 || n > TOTAL_STEPS) return;
        if (n > currentStep && !validateStep(currentStep)) return;

        document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('panel-' + n).classList.add('active');

        document.querySelectorAll('.step-item').forEach((item, i) => {
            item.classList.remove('active', 'done');
            const step = i + 1;
            if (step < n) item.classList.add('done');
            else if (step === n) item.classList.add('active');
        });

        // Update done circle icons
        document.querySelectorAll('.step-circle').forEach((c, i) => {
            if (i + 1 < n) c.innerHTML = '<i class="bi bi-check"></i>';
            else c.textContent = i + 1;
        });

        // Populate review on step 5
        if (n === 5) buildReview();

        currentStep = n;
        window.scrollTo(0, 0);
    }

    function validateStep(step) {
        if (step === 1) {
            if (!coverBlob) {
                toastr.error('Adiciona uma imagem de capa.');
                return false;
            }
            if (!document.getElementById('title').value.trim()) {
                toastr.error('Preenche o título do lançamento.');
                return false;
            }
        }
        if (step === 2) {
            const artists = $('#artists').val();
            if (!artists || artists.length === 0) {
                toastr.error('Seleciona pelo menos um artista.');
                return false;
            }
            if (!document.getElementById('genre').value) {
                toastr.error('Seleciona o género principal.');
                return false;
            }
        }
        if (step === 3) {
            const cards = document.querySelectorAll('.track-card');
            let ok = true;
            cards.forEach((card, i) => {
                const title = card.querySelector('.track-title').value.trim();
                const main = card.querySelector('.track-main-artists').value.trim();
                const comp = card.querySelector('.track-composers').value.trim();
                const prod = card.querySelector('.track-producers').value.trim();
                if (!title) {
                    toastr.error(`Faixa ${i+1}: preenche o título.`);
                    ok = false;
                }
                if (!main) {
                    toastr.error(`Faixa ${i+1}: preenche os artistas.`);
                    ok = false;
                }
                if (!comp) {
                    toastr.error(`Faixa ${i+1}: preenche os compositores.`);
                    ok = false;
                }
                if (!prod) {
                    toastr.error(`Faixa ${i+1}: preenche os produtores.`);
                    ok = false;
                }
                const audioFile = card.querySelector('.track-audio').files[0];
                if (!audioFile) {
                    toastr.error(`Faixa ${i+1}: seleciona o arquivo de áudio.`);
                    ok = false;
                } else if (!audioFile.type.includes('wav') && !audioFile.type.includes('flac')) {
                    toastr.error(`Faixa ${i+1}: formato inválido. Use WAV ou FLAC.`);
                    ok = false;
                } else if (audioFile.size > 200 * 1024 * 1024) {
                    toastr.error(`Faixa ${i+1}: arquivo muito grande (máx. 200MB).`);
                    ok = false;
                }
            });

            return ok;
        }
        if (step === 4) {
            if (!document.getElementById('release-date').value) {
                toastr.error('Define a data de lançamento.');
                return false;
            }
            const sel = document.querySelectorAll('.store-card.selected').length;
            if (sel === 0) {
                toastr.error('Seleciona pelo menos uma plataforma.');
                return false;
            }
        }
        return true;
    }

    // ═══════════════════════════════════════════════
    // COVER IMAGE + CROPPER
    // ═══════════════════════════════════════════════
    let coverBlob = null;
    let cropper = null;

    document.getElementById('cover-input').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const errEl = document.getElementById('cover-error');
        errEl.classList.add('d-none');

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            errEl.textContent = 'Formato inválido. Usa JPG, PNG ou WebP.';
            errEl.classList.remove('d-none');
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            errEl.textContent = 'Imagem demasiado grande (máx. 10 MB).';
            errEl.classList.remove('d-none');
            return;
        }

        const reader = new FileReader();
        reader.onload = (ev) => {
            const img = new Image();
            img.onload = () => {
                if (img.width < 1400 || img.height < 1400) {
                    errEl.textContent =
                        `Imagem muito pequena (${img.width}×${img.height}px). Mínimo 1400×1400 px.`;
                    errEl.classList.remove('d-none');
                    return;
                }
                // If not square, offer crop
                if (img.width !== img.height) {
                    openCropper(ev.target.result);
                } else {
                    setCover(ev.target.result, file);
                }
            };
            img.src = ev.target.result;
        };
        reader.readAsDataURL(file);
    });

    function setCover(dataUrl, file = null) {
        document.getElementById('cover-preview').src = dataUrl;
        document.getElementById('cover-drop').classList.add('has-image');
        document.getElementById('btn-crop').classList.remove('d-none');
        document.getElementById('btn-remove-cover').classList.remove('d-none');

        // Convert to Blob for FormData
        if (file) {
            coverBlob = file;
        } else {
            fetch(dataUrl).then(r => r.blob()).then(b => {
                coverBlob = b;
            });
        }
    }

    function openCropper(src) {
        document.getElementById('cropper-img').src = src;
        const modal = new bootstrap.Modal(document.getElementById('cropperModal'));
        modal.show();

        document.getElementById('cropperModal').addEventListener('shown.bs.modal', () => {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            cropper = new Cropper(document.getElementById('cropper-img'), {
                aspectRatio: 1 / 1,
                viewMode: 2,
                background: false,
                minCropBoxWidth: 1400,
                minCropBoxHeight: 1400
            });
        }, {
            once: true
        });
    }

    document.getElementById('btn-crop-confirm').addEventListener('click', () => {
        if (!cropper) return;
        cropper.getCroppedCanvas({
            width: 3000,
            height: 3000
        }).toBlob((blob) => {
            const url = URL.createObjectURL(blob);
            coverBlob = blob;
            setCover(url);
            bootstrap.Modal.getInstance(document.getElementById('cropperModal')).hide();
        }, 'image/jpeg', 0.95);
    });

    document.getElementById('btn-crop').addEventListener('click', () => {
        const src = document.getElementById('cover-preview').src;
        if (src) openCropper(src);
    });

    document.getElementById('btn-remove-cover').addEventListener('click', () => {
        coverBlob = null;
        document.getElementById('cover-preview').src = '';
        document.getElementById('cover-drop').classList.remove('has-image');
        document.getElementById('btn-crop').classList.add('d-none');
        document.getElementById('btn-remove-cover').classList.add('d-none');
        document.getElementById('cover-input').value = '';
    });

    // Drag & drop
    const coverDrop = document.getElementById('cover-drop');
    coverDrop.addEventListener('dragover', e => {
        e.preventDefault();
        coverDrop.style.borderColor = 'var(--wasom)';
    });
    coverDrop.addEventListener('dragleave', () => {
        coverDrop.style.borderColor = '';
    });
    coverDrop.addEventListener('drop', e => {
        e.preventDefault();
        coverDrop.style.borderColor = '';
        const file = e.dataTransfer.files[0];
        if (file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            document.getElementById('cover-input').files = dt.files;
            document.getElementById('cover-input').dispatchEvent(new Event('change'));
        }
    });

    // ═══════════════════════════════════════════════
    // SELECT2 — ARTISTS
    // ═══════════════════════════════════════════════
    let artistNewName = '';

    $(document).ready(function() {
        $('#artists').select2({
            theme: 'bootstrap-5',
            placeholder: 'Escreve ou seleciona artistas...',
            allowClear: true,
            tags: false,
            width: '100%',
            escapeMarkup: function(markup) {
                return markup; // Permite HTML não escapar
            },
            language: {
                noResults: function() {
                    return '<div style="padding:8px">Artista não encontrado. <a href="#" id="s2-create-link" class="fw-bold" style="color:var(--wasom)">Criar artista</a></div>';
                }
            }
        });

        // Click on "Criar artista" inside Select2 dropdown
        $(document).on('click', '#s2-create-link', function(e) {
            e.preventDefault();
            artistNewName = $('.select2-search__field').val() || '';
            document.getElementById('artist-name').value = artistNewName;
            $('#artists').select2('close');
            new bootstrap.Modal(document.getElementById('createArtistModal')).show();
        });
    });

    document.getElementById('btn-create-artist').addEventListener('click', () => {
        artistNewName = '';
        document.getElementById('artist-name').value = '';
        document.getElementById('artist-real-name').value = '';
        document.getElementById('artist-email').value = '';
        document.getElementById('artist-genre').value = '';
        document.getElementById('artist-spotify').value = '';
        document.getElementById('artist-apple').value = '';
        document.getElementById('artist-youtube').value = '';
        document.getElementById('artist-create-feedback').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('createArtistModal')).show();
    });

    // Artist photo preview
    document.getElementById('artist-photo-input').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = ev => {
            document.getElementById('artist-photo-icon').classList.add('d-none');
            const img = document.getElementById('artist-photo-img');
            img.src = ev.target.result;
            img.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });

    async function saveArtist() {
        const name = document.getElementById('artist-name').value.trim();
        const email = document.getElementById('artist-email').value.trim();
        const fb = document.getElementById('artist-create-feedback');

        if (!name) {
            fb.innerHTML = '<div class="alert alert-danger py-2 small">O nome artístico é obrigatório.</div>';
            fb.classList.remove('d-none');
            return;
        }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            fb.innerHTML =
                '<div class="alert alert-danger py-2 small">O email do artista é obrigatório e deve ser válido.</div>';
            fb.classList.remove('d-none');
            return;
        }

        document.getElementById('save-artist-text').classList.add('d-none');
        document.getElementById('save-artist-load').classList.remove('d-none');
        document.getElementById('btn-save-artist').disabled = true;

        const fd = new FormData();
        fd.append('action', 'create_artist');
        fd.append('csrf_token', CSRF);
        fd.append('stage_name', name);
        fd.append('real_name', document.getElementById('artist-real-name').value.trim());
        fd.append('artist_email', email);
        fd.append('genre_main', document.getElementById('artist-genre').value.trim());
        fd.append('spotify_url', document.getElementById('artist-spotify').value.trim());
        fd.append('website_url', document.getElementById('artist-apple').value.trim());
        fd.append('youtube_url', document.getElementById('artist-youtube').value.trim());
        const photo = document.getElementById('artist-photo-input').files[0];
        if (photo) fd.append('photo', photo);

        try {
            const res = await fetch(BASE_URL + '/dashboard/launch/creat_release_process', {
                method: 'POST',
                body: fd
            });
            const data = await res.json();
            if (data.ok) {
                // Add to Select2
                const opt = new Option(name, data.id_artist, true, true);
                $('#artists').append(opt).trigger('change');
                toastr.success(`Artista "${name}" criado com sucesso!`);
                bootstrap.Modal.getInstance(document.getElementById('createArtistModal')).hide();
                // Add to USER_ARTISTS
                USER_ARTISTS.push({
                    id_artist: data.id_artist,
                    stage_name: name
                });
            } else {
                fb.innerHTML =
                    `<div class="alert alert-danger py-2 small">${data.message || 'Erro ao criar artista.'}</div>`;
                fb.classList.remove('d-none');
            }
        } catch {
            fb.innerHTML = '<div class="alert alert-danger py-2 small">Erro de ligação. Tenta novamente.</div>';
            fb.classList.remove('d-none');
        } finally {
            document.getElementById('save-artist-text').classList.remove('d-none');
            document.getElementById('save-artist-load').classList.add('d-none');
            document.getElementById('btn-save-artist').disabled = false;
        }
    }

    // ═══════════════════════════════════════════════
    // GENRE / SUBGENRE
    // ═══════════════════════════════════════════════
    const SUBGENRES = {
        pop: ['Pop Rock', 'Synth-Pop', 'Indie Pop', 'Electro Pop', 'Dance Pop', 'K-Pop', 'Teen Pop'],
        rock: ['Rock Clássico', 'Rock Alternativo', 'Indie Rock', 'Grunge', 'Punk Rock', 'Hard Rock', 'Post-Rock'],
        hip_hop: ['Trap', 'Boom Bap', 'Gangsta Rap', 'Rap Consciente', 'Drill', 'Phonk', 'Lo-fi Hip-Hop',
            'Afrorap'
        ],
        r_and_b: ['Soul', 'Neo-Soul', 'Contemporary R&B', 'Funk R&B', 'Quiet Storm'],
        afrobeats: ['Afropop', 'Afrofusion', 'Highlife', 'Afrohouse', 'Afrobeats Drill'],
        semba: ['Semba Tradicional', 'Semba Moderno', 'Semba Jazz'],
        kizomba: ['Kizomba Clássica', 'Kizomba Fusion', 'Tarraxo', 'Ghetto Zouk'],
        kuduro: ['Kuduro Tradicional', 'Kuduro Moderno', 'Afro Kuduro'],
        funaná: ['Funaná Tradicional', 'Funaná Moderno'],
        eletronica: ['House', 'Tech House', 'Deep House', 'Techno', 'Drum & Bass', 'Dubstep', 'Trance', 'Ambient',
            'EDM', 'Afrotech'
        ],
        jazz: ['Smooth Jazz', 'Bebop', 'Fusion', 'Free Jazz', 'Jazz Vocal', 'Latin Jazz'],
        classica: ['Barroco', 'Romântico', 'Moderno', 'Minimalismo', 'Ópera', 'Câmara', 'Sinfónico'],
        gospel: ['Gospel Contemporâneo', 'Gospel Tradicional', 'CCM', 'Praise & Worship', 'Gospel Afro'],
        reggae: ['Roots Reggae', 'Dancehall', 'Ska', 'Reggaeton', 'Lovers Rock'],
        samba: ['Samba Tradicional', 'Pagode', 'Samba Rock', 'Samba Enredo'],
        funk: ['Funk Carioca', 'Funk Proibidão', 'Funk Melody', 'Miami Bass'],
        pagode: ['Pagode Romântico', 'Pagode Baiano'],
        forro: ['Forró Pé-de-Serra', 'Forró Universitário', 'Xote', 'Baião'],
        folk: ['Folk Tradicional', 'Singer-Songwriter', 'Indie Folk', 'Bluegrass', 'Celtic'],
        metal: ['Heavy Metal', 'Death Metal', 'Black Metal', 'Power Metal', 'Metalcore', 'Nu-Metal'],
        alternativo: ['Indie Rock', 'Post-Punk', 'Shoegaze', 'Dream Pop', 'Emo', 'Grunge'],
        country: ['Country Clássico', 'Country Pop', 'Country Rock', 'Bluegrass'],
        blues: ['Delta Blues', 'Chicago Blues', 'Electric Blues', 'Blues Rock'],
        latin: ['Salsa', 'Merengue', 'Bachata', 'Cumbia', 'Bossa Nova', 'Flamenco', 'Tango'],
        amapiano: ['Amapiano Log Drum', 'Amapiano Vocals', 'Street Amapiano'],
        dancehall: ['Dancehall Roots', 'Modern Dancehall', 'Ragga'],
        instrumental: ['Jazz Instrumental', 'Clássico Instrumental', 'Lo-Fi', 'Ambient', 'Chillout'],
        spoken: ['Spoken Word', 'Poesia', 'Slam Poetry', 'Audiolivro'],
        outros: ['World Music', 'Fusion', 'Experimental'],
    };

    document.getElementById('genre').addEventListener('change', function() {
        const genre = this.value;
        const sub = document.getElementById('subgenre');
        sub.innerHTML = '<option value="">Seleciona um subgénero</option>';
        sub.disabled = !genre;
        if (genre && SUBGENRES[genre]) {
            SUBGENRES[genre].forEach(sg => {
                const opt = document.createElement('option');
                opt.value = sg.toLowerCase().replace(/[^a-z0-9]/g, '_');
                opt.textContent = sg;
                sub.appendChild(opt);
            });
        }
    });

    // ═══════════════════════════════════════════════
    // COPYRIGHT YEAR SELECT
    // ═══════════════════════════════════════════════
    function populateYears() {
        const cur = new Date().getFullYear();
        ['copyright-year', 'phonogram-year'].forEach(id => {
            const sel = document.getElementById(id);
            for (let y = cur + 1; y >= 1950; y--) {
                const opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                if (y === cur) opt.selected = true;
                sel.appendChild(opt);
            }
        });
    }
    populateYears();

    // ═══════════════════════════════════════════════
    // TRACKS
    // ═══════════════════════════════════════════════
    let trackCount = 0;

    function addTrack() {
        if (UI_MAX_TRACKS && trackCount >= UI_MAX_TRACKS) {
            toastr.warning(`O teu plano permite no máximo ${UI_MAX_TRACKS} faixas.`);
            return;
        }
        trackCount++;
        const template = document.getElementById('track-template');
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.track-card');

        card.dataset.trackIndex = trackCount;
        card.querySelector('.track-num-label').textContent = trackCount;

        // Show remove button if more than 1 track
        if (trackCount > 1) card.querySelector('.btn-remove-track').classList.remove('d-none');

        document.getElementById('tracks-container').appendChild(card);
        renumberTracks();
        updateTrackUI();
    }

    function removeTrack(btn) {
        if (trackCount <= 1) return;
        btn.closest('.track-card').remove();
        trackCount--;
        renumberTracks();
        updateTrackUI();
    }

    function renumberTracks() {
        document.querySelectorAll('.track-card').forEach((card, i) => {
            card.dataset.trackIndex = i + 1;
            card.querySelector('.track-num-label').textContent = i + 1;
            // Only first track can't be removed if only 1
            const rm = card.querySelector('.btn-remove-track');
            rm.classList.toggle('d-none', trackCount <= 1);
        });
    }

    function updateTrackUI() {
        document.getElementById('track-counter').textContent = `${trackCount} / ${UI_MAX_TRACKS}`;
        const btn = document.getElementById('btn-add-track');
        if (btn) btn.disabled = (UI_MAX_TRACKS && trackCount >= UI_MAX_TRACKS);
    }

    // Init first track
    addTrack();

    // ═══════════════════════════════════════════════
    // STORES
    // ═══════════════════════════════════════════════
    function toggleStore(card) {
        card.classList.toggle('selected');
        card.querySelector('.store-checkbox').checked = card.classList.contains('selected');
        updateStoreCount();
    }

    function selectAllStores() {
        document.querySelectorAll('.store-card').forEach(c => {
            c.classList.add('selected');
            c.querySelector('.store-checkbox').checked = true;
        });
        updateStoreCount();
    }

    function deselectAllStores() {
        document.querySelectorAll('.store-card').forEach(c => {
            c.classList.remove('selected');
            c.querySelector('.store-checkbox').checked = false;
        });
        updateStoreCount();
    }

    function updateStoreCount() {
        const n = document.querySelectorAll('.store-card.selected').length;
        document.getElementById('stores-selected-count').textContent = n;
    }

    // ═══════════════════════════════════════════════
    // MIN DATE (today + 2 days)
    // ═══════════════════════════════════════════════
    (function() {
        const d = new Date();
        d.setDate(d.getDate() + 2);
        const min = d.toISOString().split('T')[0];
        document.getElementById('release-date').min = min;
        document.getElementById('release-date').value = min;
    })();

    // ═══════════════════════════════════════════════
    // REVIEW — Build summary on step 5
    // ═══════════════════════════════════════════════
    function buildReview() {
        const title = document.getElementById('title').value;
        const version = document.getElementById('version').value;
        const type_alb = document.getElementById('type_album').value;
        const genreEl = document.getElementById('genre');
        const genreText = genreEl.options[genreEl.selectedIndex]?.text || '—';
        const subEl = document.getElementById('subgenre');
        const subText = subEl.value ? subEl.options[subEl.selectedIndex]?.text : '';
        const dateVal = document.getElementById('release-date').value;
        const storeCount = document.querySelectorAll('.store-card.selected').length;
        const artistNames = $('#artists').select2('data').map(o => o.text).join(', ');
        const tracks = document.querySelectorAll('.track-card');

        document.getElementById('rev-title').textContent = title || '—';
        document.getElementById('rev-type').textContent = `${type_alb}${version ? ' — ' + version : ''}`;
        document.getElementById('rev-artists').textContent = artistNames || '—';
        document.getElementById('rev-genre').textContent = genreText + (subText ? ' › ' + subText : '');
        document.getElementById('rev-tracks').textContent = `${tracks.length} faixa${tracks.length !== 1 ? 's' : ''}`;
        document.getElementById('rev-date').textContent = dateVal ? new Date(dateVal + 'T00:00').toLocaleDateString(
            'pt-PT') : '—';
        document.getElementById('rev-stores').textContent = `${storeCount} plataforma${storeCount !== 1 ? 's' : ''}`;

        // Cover
        const prev = document.getElementById('cover-preview').src;
        const revCover = document.getElementById('rev-cover');
        if (prev && prev !== window.location.href) revCover.src = prev;
        else revCover.style.display = 'none';
    }

    // ═══════════════════════════════════════════════
    // SUBMIT
    // ═══════════════════════════════════════════════
    async function submitRelease() {
        if (!validateStep(4)) return;
        if (!document.getElementById('terms-check').checked) {
            toastr.error('Aceita os Termos e Políticas de Privacidade para continuar.');
            return;
        }

        const btn = document.getElementById('btn-distribute');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A enviar...';
        btn.disabled = true;

        // Collect tracks com arquivos de áudio
        const tracks = [];
        const trackCards = document.querySelectorAll('.track-card');

        // Validar e coletar dados
        for (let i = 0; i < trackCards.length; i++) {
            const card = trackCards[i];
            const audioFile = card.querySelector('.track-audio').files[0];

            if (!audioFile) {
                toastr.error(`Faixa ${i+1}: seleciona o arquivo de áudio.`);
                btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Distribuir';
                btn.disabled = false;
                return;
            }

            // Validar formato
            const validTypes = ['audio/wav', 'audio/x-wav', 'audio/flac', 'audio/x-flac'];
            const fileName = audioFile.name.toLowerCase();
            if (!validTypes.includes(audioFile.type) && !fileName.endsWith('.wav') && !fileName.endsWith('.flac')) {
                toastr.error(`Faixa ${i+1}: formato inválido. Use WAV ou FLAC.`);
                btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Distribuir';
                btn.disabled = false;
                return;
            }

            // Validar tamanho
            if (audioFile.size > 200 * 1024 * 1024) {
                toastr.error(`Faixa ${i+1}: arquivo muito grande (máx. 200MB).`);
                btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Distribuir';
                btn.disabled = false;
                return;
            }

            tracks.push({
                track_number: i + 1,
                title_track: card.querySelector('.track-title').value.trim(),
                mix_version: card.querySelector('.track-mix-version').value,
                name_author: card.querySelector('.track-main-artists').value.trim(),
                name_author_feat: card.querySelector('.track-feat').value.trim(),
                name_composer: card.querySelector('.track-composers').value.trim(),
                name_producer: card.querySelector('.track-producers').value.trim(),
                language: card.querySelector('.track-language').value,
                recording_date: card.querySelector('.track-recording-date').value,
                explicit: card.querySelector('.track-explicit').value,
                isrc: card.querySelector('.track-isrc').value.trim().toUpperCase()
            });
        }

        // Collect stores
        const stores = [];
        document.querySelectorAll('.store-card.selected').forEach(c => stores.push(c.dataset.storeId));

        const copyrightYear = document.getElementById('copyright-year').value;
        const phonogramYear = document.getElementById('phonogram-year').value;

        // CRIAR FormData AQUI (única declaração)
        const fd = new FormData();

        // Adicionar todos os campos
        fd.append('action', 'create_release');
        fd.append('csrf_token', CSRF);
        fd.append('title_album', document.getElementById('title').value.trim());
        fd.append('version_album', document.getElementById('version').value);
        fd.append('type_album', document.getElementById('type_album').value);
        fd.append('language', document.getElementById('language').value);
        fd.append('artists', JSON.stringify($('#artists').val()));
        fd.append('genre_main', document.getElementById('genre').value);
        fd.append('genre_secondary', document.getElementById('subgenre').value);
        fd.append('label_name', CAN_LABEL ? document.getElementById('label_name').value.trim() :
            '102022 WU Records');
        fd.append('copyright_c', `© ${copyrightYear}  - 102022 WU Records`);
        fd.append('copyright_p', `℗ ${phonogramYear}  - 102022 WU Records`);
        fd.append('release_date', document.getElementById('release-date').value);
        fd.append('tracks', JSON.stringify(tracks));
        fd.append('stores', JSON.stringify(stores));
        fd.append('audio_count', tracks.length);

        if (coverBlob) fd.append('cover', coverBlob, 'cover.jpg');

        // Adicionar arquivos de áudio
        trackCards.forEach((card, index) => {
            const audioFile = card.querySelector('.track-audio').files[0];
            if (audioFile) {
                const safeFileName = `track_${index + 1}_${audioFile.name.replace(/[^a-zA-Z0-9.]/g, '_')}`;
                fd.append(`audio_${index + 1}`, audioFile, safeFileName);
            }
        });

        try {
            const res = await fetch(BASE_URL + '/dashboard/launch/creat_release_process', {
                method: 'POST',
                body: fd
            });

            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }

            const data = await res.json();

            if (data.ok) {
                await Swal.fire({
                    icon: 'success',
                    iconColor: '#FF0089',
                    title: '<i class="bi bi-music-note-beamed me-2"></i>Lançamento enviado!',
                    html: `<p class="mb-2">O teu lançamento <strong>${document.getElementById('title').value}</strong> foi submetido com sucesso!</p>
                    <p class="mb-0 text-muted small">
                        <i class="bi bi-clock me-1"></i>
                        Estado: <strong>Pendente de revisão</strong><br>
                        A nossa equipa irá rever o teu lançamento em até 48h.
                        Receberás uma notificação quando for aprovado.
                    </p>`,
                    confirmButtonText: 'Ver Lançamentos',
                    confirmButtonColor: '#FF0089',
                    showCancelButton: false,
                    allowOutsideClick: false
                });
                window.location.href = BASE_URL + '/dashboard/launch/releases';
            } else {
                btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Distribuir';
                btn.disabled = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: data.message || 'Erro ao submeter. Tenta novamente.',
                    confirmButtonColor: '#FF0089'
                });
            }
        } catch (err) {
            console.error('Erro detalhado:', err);
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Distribuir';
            btn.disabled = false;
            Swal.fire({
                icon: 'error',
                title: 'Erro de Ligação',
                text: 'Verifica a tua internet e tenta novamente. Detalhes: ' + err.message,
                confirmButtonColor: '#FF0089'
            });
        }
    }

    // Limpar arquivo de áudio selecionado
    function clearAudioFile(btn) {
        const card = btn.closest('.track-card');
        const audioInput = card.querySelector('.track-audio');
        audioInput.value = '';
        btn.style.display = 'none';
        card.querySelector('.audio-filename').textContent = '';
        card.querySelector('.audio-size').textContent = '';
        card.classList.remove('has-audio');
    }

    // Atualizar preview do arquivo selecionado
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('track-audio')) {
            const card = e.target.closest('.track-card');
            const clearBtn = card.querySelector('.track-audio-clear');
            const filenameSpan = card.querySelector('.audio-filename');
            const sizeSpan = card.querySelector('.audio-size');

            if (e.target.files[0]) {
                const file = e.target.files[0];
                filenameSpan.textContent = file.name;
                const sizeMB = (file.size / (1024 * 1024)).toFixed(1);
                sizeSpan.textContent = `(${sizeMB} MB)`;
                clearBtn.style.display = 'block';
                card.classList.add('has-audio');

                // Validar formato
                if (!file.type.includes('wav') && !file.type.includes('flac')) {
                    showAudioError(card, 'Formato inválido. Use WAV ou FLAC.');
                } else {
                    hideAudioError(card);
                }
            } else {
                filenameSpan.textContent = '';
                sizeSpan.textContent = '';
                clearBtn.style.display = 'none';
                card.classList.remove('has-audio');
            }
        }
    });

    function showAudioError(card, message) {
        const errorDiv = card.querySelector('.audio-error');
        errorDiv.textContent = message;
        errorDiv.classList.remove('d-none');
    }

    function hideAudioError(card) {
        card.querySelector('.audio-error').classList.add('d-none');
    }

    // ═══════════════════════════════════════════════
    // AUTO-DRAFT COMPLETO
    // ═══════════════════════════════════════════════
    const DRAFT_KEY = 'wasom_drafts_<?php echo $id_users; ?>';

    function saveCompleteDraft() {
        const draft = {
            id: 'local_' + Date.now(),
            saved_at: new Date().toISOString(),

            // STEP 1
            title: document.getElementById('title').value,
            version: document.getElementById('version').value,
            type_album: document.getElementById('type_album').value,
            language: document.getElementById('language').value,
            coverBlob: coverBlob ? true : false,

            // STEP 2
            artists: $('#artists').val(),
            artists_names: $('#artists').select2('data').map(o => o.text).join(', '),
            genre_main: document.getElementById('genre').value,
            genre_secondary: document.getElementById('subgenre').value,
            label_name: document.getElementById('label_name').value,
            copyright_year: document.getElementById('copyright-year').value,
            phonogram_year: document.getElementById('phonogram-year').value,

            // STEP 3
            tracks: [],

            // STEP 4
            release_date: document.getElementById('release-date').value,
            release_time: document.getElementById('release-time').value,
            release_timezone: document.getElementById('release-timezone').value,
            stores: Array.from(document.querySelectorAll('.store-card.selected')).map(c => c.dataset.storeId)
        };

        // Recolher dados das faixas
        document.querySelectorAll('.track-card').forEach((card, index) => {
            draft.tracks.push({
                track_number: index + 1,
                title_track: card.querySelector('.track-title').value,
                mix_version: card.querySelector('.track-mix-version').value,
                name_author: card.querySelector('.track-main-artists').value,
                name_author_feat: card.querySelector('.track-feat').value,
                name_composer: card.querySelector('.track-composers').value,
                name_producer: card.querySelector('.track-producers').value,
                language: card.querySelector('.track-language').value,
                recording_date: card.querySelector('.track-recording-date').value,
                explicit: card.querySelector('.track-explicit').value,
                isrc: card.querySelector('.track-isrc').value
            });
        });

        // Guardar se houver dados
        if (draft.title || draft.tracks.length > 0) {
            let drafts = [];
            try {
                drafts = JSON.parse(localStorage.getItem(DRAFT_KEY) || '[]');
            } catch {}

            const idx = drafts.findIndex(d => d.id && d.id.startsWith('local_'));
            if (idx >= 0) drafts[idx] = draft;
            else drafts.push(draft);

            localStorage.setItem(DRAFT_KEY, JSON.stringify(drafts));
        }
    }

    // Guardar a cada 30 segundos
    setInterval(saveCompleteDraft, 30000);

    // Guardar ao sair da página
    window.addEventListener('beforeunload', function() {
        saveCompleteDraft();
    });

    // Warn before leaving with unsaved data
    window.addEventListener('beforeunload', e => {
        if (document.getElementById('title').value.trim()) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
    </script>

    <script>
    // Dados do rascunho BD (se existir)
    const DRAFT_FROM_DB = <?php echo $draft_from_db ? json_encode([
    'album' => $draft_from_db,
    'tracks' => $draft_tracks,
    'stores' => $draft_stores
]) : 'null'; ?>;
    </script>
</body>

</html>