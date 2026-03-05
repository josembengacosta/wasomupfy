<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Lista de Artistas
// Arquivo: dashboard/artists/artists-list.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
$required_permission = 'artists'; // admin, editor only
require_once __DIR__ . '/../../dashboard/collab/middleware.php';

$id_users   = (int)$user['id_users'];
$first_name = htmlspecialchars($user['first_name']);
$user_name  = htmlspecialchars($user['user_name'] ?? '');
$db         = getDB();

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

// ── Artistas com contagem de álbuns ──────────
$art_stmt = $db->prepare("
    SELECT
        a.*,
        COUNT(DISTINCT al.id_album)                                        AS total_albums,
        COUNT(DISTINCT CASE WHEN al.status_album = 'approved'   THEN al.id_album END) AS albums_approved,
        COUNT(DISTINCT CASE WHEN al.status_album = 'pending'    THEN al.id_album END) AS albums_pending,
        COUNT(DISTINCT CASE WHEN al.status_album = 'rejected'   THEN al.id_album END) AS albums_rejected,
        COUNT(DISTINCT CASE WHEN al.status_album = 'draft'      THEN al.id_album END) AS albums_draft,
        COUNT(DISTINCT CASE WHEN al.status_album = 'under_review' THEN al.id_album END) AS albums_review
    FROM _artist a
    LEFT JOIN _album al ON al.id_artist = a.id_artist AND al.id_users = a.id_users
    WHERE a.id_users = ?
    GROUP BY a.id_artist
    ORDER BY a.creat_artist DESC
");
$art_stmt->execute([$id_users]);
$artists = $art_stmt->fetchAll(PDO::FETCH_ASSOC);
$artist_count = count($artists);
$can_add = $artist_count < $max_artists;

// ── Álbuns de cada artista (para o painel lateral) ─
$albums_by_artist = [];
if ($artist_count > 0) {
    $ids = implode(',', array_column($artists, 'id_artist'));
    $al_stmt = $db->query("
        SELECT id_album, id_artist, title_album, type_album, status_album,
               img_cover, release_date, genre_main, creat_album
        FROM _album
        WHERE id_artist IN ($ids) AND id_users = $id_users
        ORDER BY creat_album DESC
    ");
    foreach ($al_stmt->fetchAll(PDO::FETCH_ASSOC) as $alb) {
        $albums_by_artist[$alb['id_artist']][] = $alb;
    }
}

// ── Session info ──────────────────────────────
$ls = $db->prepare('SELECT last_login_at FROM _users_security WHERE id_users = ?');
$ls->execute([$id_users]);
$sec = $ls->fetch();
$ss = $db->prepare("SELECT creat_session FROM _users_sessions WHERE id_users=? AND is_active=1 ORDER BY last_activity DESC LIMIT 1");
$ss->execute([$id_users]);
$cur_sess = $ss->fetch();
$dur = '—';
if ($cur_sess) {
    $secs = time() - strtotime($cur_sess['creat_session']);
    $dur = $secs < 60 ? "{$secs}s" : ($secs < 3600 ? floor($secs/60).'min' : floor($secs/3600).'h '.floor(($secs%3600)/60).'min');
}
$last_login_str = ($sec && $sec['last_login_at']) ? date('d/m/Y H:i', strtotime($sec['last_login_at'])) : '—';

$csrf      = htmlspecialchars($_SESSION['csrf_token']);
$photo_base = rtrim(APP_URL, '/') . '/assets/comprovantes/uploads/artists/';
$cover_base = rtrim(APP_URL, '/') . '/assets/comprovantes/uploads/covers/';

// ── JSON para JS ──────────────────────────────
$artists_json       = json_encode($artists,         JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
$albums_json        = json_encode($albums_by_artist, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <title>Artistas — Wasom Upfy</title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <link rel="stylesheet" href="../../css/dashboard-style.css" />
    <style>
    :root {
        --wasom: #FF0089;
        --wasom-dark: #cc006d;
    }

    /* ── Search & filters ── */
    .search-bar {
        border-radius: 12px !important;
    }

    .filter-pill {
        border-radius: 20px;
        font-size: .78rem;
        padding: 4px 14px;
        cursor: pointer;
        border: 1.5px solid transparent;
        transition: all .2s;
    }

    .filter-pill.active {
        background: var(--wasom);
        color: #fff;
        border-color: var(--wasom);
    }

    .filter-pill:not(.active) {
        border-color: rgba(0, 0, 0, .15);
    }

    /* ── View toggle ── */
    .view-btn {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 1.5px solid rgba(0, 0, 0, .12);
        transition: all .2s;
    }

    .view-btn.active {
        background: var(--wasom);
        color: #fff;
        border-color: var(--wasom);
    }

    /* ── Artist card (GRID) ── */
    .artist-card {
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        border: 1.5px solid rgba(0, 0, 0, .07);
        box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
        transition: transform .25s, box-shadow .25s;
        cursor: pointer;
        background: var(--card-bg, #fff);
    }

    .artist-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(255, 0, 137, .15);
        border-color: rgba(255, 0, 137, .3);
    }

    .artist-card-cover {
        height: 90px;
        background: linear-gradient(135deg, #FF0089 0%, #FF4D4D 100%);
        position: relative;
    }

    .artist-card-cover.has-cover {
        background-size: cover;
        background-position: center;
        filter: brightness(.7);
    }

    .artist-card-avatar {
        width: 76px;
        height: 76px;
        border-radius: 50%;
        border: 3px solid #fff;
        object-fit: cover;
        position: absolute;
        bottom: -38px;
        left: 50%;
        transform: translateX(-50%);
        box-shadow: 0 4px 14px rgba(0, 0, 0, .18);
        background: #eee;
    }

    .artist-card-avatar-placeholder {
        width: 76px;
        height: 76px;
        border-radius: 50%;
        border: 3px solid #fff;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        position: absolute;
        bottom: -38px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: #ccc;
        box-shadow: 0 4px 14px rgba(0, 0, 0, .12);
    }

    .artist-card-body {
        padding: 50px 16px 18px;
        text-align: center;
    }

    .artist-card-name {
        font-weight: 800;
        font-size: .92rem;
        margin-bottom: 2px;
        letter-spacing: -.2px;
    }

    .artist-card-real {
        font-size: .75rem;
        color: #999;
        margin-bottom: 8px;
    }

    .artist-card-location {
        font-size: .72rem;
        color: #aaa;
    }

    .artist-card-stats {
        display: flex;
        justify-content: center;
        gap: 16px;
        margin: 10px 0;
    }

    .artist-card-stat {
        text-align: center;
    }

    .artist-card-stat .num {
        font-size: 1rem;
        font-weight: 800;
        color: var(--wasom);
    }

    .artist-card-stat .lbl {
        font-size: .65rem;
        color: #999;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .artist-card-socials {
        display: flex;
        justify-content: center;
        gap: 6px;
        margin-top: 10px;
        flex-wrap: wrap;
    }

    .social-pill {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .85rem;
        color: #fff;
        transition: transform .2s;
        text-decoration: none;
    }

    .social-pill:hover {
        transform: scale(1.18);
        color: #fff;
    }

    .artist-status-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: .65rem;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 20px;
        border: 1.5px solid rgba(255, 255, 255, .4);
    }

    .status-active {
        background: rgba(25, 135, 84, .85);
        color: #fff;
    }

    .status-processing {
        background: rgba(255, 193, 7, .85);
        color: #333;
    }

    .status-inactive {
        background: rgba(108, 117, 125, .85);
        color: #fff;
    }

    .status-blocked {
        background: rgba(220, 53, 69, .85);
        color: #fff;
    }

    /* ── Artist card (LIST) ── */
    .artist-list-row {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
        border-radius: 14px;
        border: 1.5px solid rgba(0, 0, 0, .07);
        background: var(--card-bg, #fff);
        transition: all .2s;
        cursor: pointer;
        box-shadow: 0 1px 6px rgba(0, 0, 0, .04);
    }

    .artist-list-row:hover {
        border-color: rgba(255, 0, 137, .3);
        box-shadow: 0 4px 16px rgba(255, 0, 137, .1);
    }

    .artist-list-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        background: #eee;
    }

    .artist-list-avatar-ph {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #f1f3f5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: #ccc;
        flex-shrink: 0;
    }

    .artist-list-info {
        flex: 1;
        min-width: 0;
    }

    .artist-list-name {
        font-weight: 700;
        font-size: .9rem;
    }

    .artist-list-meta {
        font-size: .75rem;
        color: #999;
    }

    .artist-list-socials {
        display: flex;
        gap: 6px;
        flex-shrink: 0;
    }

    .artist-list-albums {
        display: flex;
        gap: 4px;
        flex-shrink: 0;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    /* ── Detail offcanvas ── */
    .offcanvas-detail {
        width: min(480px, 100vw) !important;
    }

    .detail-cover {
        height: 160px;
        background: linear-gradient(135deg, #FF0089, #FF4D4D);
        position: relative;
        flex-shrink: 0;
    }

    .detail-avatar {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        border: 4px solid #fff;
        object-fit: cover;
        position: absolute;
        bottom: -45px;
        left: 28px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .2);
        background: #eee;
    }

    .detail-avatar-ph {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        border: 4px solid #fff;
        background: #f1f3f5;
        position: absolute;
        bottom: -45px;
        left: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
        color: #ccc;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .15);
    }

    .detail-body {
        padding: 56px 24px 24px;
        overflow-y: auto;
    }

    .detail-stat-card {
        border-radius: 12px;
        padding: 12px 16px;
        text-align: center;
        background: rgba(255, 0, 137, .06);
        border: 1px solid rgba(255, 0, 137, .12);
    }

    .detail-stat-card .num {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--wasom);
    }

    .detail-stat-card .lbl {
        font-size: .7rem;
        color: #999;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .album-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 0;
        border-bottom: 1px solid rgba(0, 0, 0, .05);
    }

    .album-row:last-child {
        border-bottom: none;
    }

    .album-cover-sm {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        object-fit: cover;
        background: #eee;
        flex-shrink: 0;
    }

    .album-cover-sm-ph {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        background: #f1f3f5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
        color: #ccc;
        flex-shrink: 0;
    }

    .status-badge {
        font-size: .65rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 12px;
    }

    .sb-approved {
        background: #d4edda;
        color: #155724;
    }

    .sb-pending {
        background: #fff3cd;
        color: #856404;
    }

    .sb-rejected {
        background: #f8d7da;
        color: #721c24;
    }

    .sb-draft {
        background: #e2e3e5;
        color: #383d41;
    }

    .sb-under_review {
        background: #cce5ff;
        color: #004085;
    }

    .sb-processing {
        background: #fff3cd;
        color: #856404;
    }

    /* ── Empty state ── */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }

    .empty-icon {
        font-size: 4rem;
        opacity: .15;
        display: block;
        margin-bottom: 1rem;
    }

    /* ── No results ── */
    #no-results {
        display: none;
        text-align: center;
        padding: 3rem;
        color: #aaa;
    }

    /* ── Plan bar ── */
    .plan-bar {
        background: rgba(255, 0, 137, .06);
        border: 1.5px solid rgba(255, 0, 137, .15);
        border-radius: 14px;
        padding: 12px 18px;
    }
    </style>
</head>

<body>

    <!-- Navbar -->
    <?php require_once __DIR__ . '/../../dashboard/collab/navbar.php'; ?>

    <div class="container py-4" style="max-width:1100px">

        <!-- Page header -->
        <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">
                    <i class="bi bi-people-fill me-2" style="color:var(--wasom)"></i>Artistas
                </h1>
                <p class="text-muted small mb-0">Gere os perfis artísticos da tua conta. Clica num artista para ver
                    detalhes.</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <?php if ($can_add): ?>
                <a href="add-artist" class="btn btn-sm px-3" style="background:var(--wasom);color:#fff">
                    <i class="bi bi-person-plus me-1"></i>Novo Artista
                </a>
                <?php else: ?>
                <button class="btn btn-sm btn-outline-secondary" disabled title="Limite do plano atingido">
                    <i class="bi bi-lock me-1"></i>Novo Artista
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Plan bar -->
        <div class="plan-bar mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <span class="fw-semibold small">Plano <span
                            style="color:var(--wasom)"><?php echo $plan_name; ?></span></span>
                    <span class="text-muted small ms-2">·</span>
                    <span class="text-muted small ms-2">
                        <?php echo $artist_count; ?> de <?php echo $max_artists; ?>
                        artista<?php echo $max_artists > 1 ? 's' : ''; ?> usados
                    </span>
                </div>
                <div class="progress" style="width:100px;height:7px;border-radius:10px">
                    <?php $pct = $max_artists > 0 ? min(100, round($artist_count/$max_artists*100)) : 0; ?>
                    <div class="progress-bar"
                        style="width:<?php echo $pct; ?>%;background:var(--wasom);border-radius:10px"></div>
                </div>
            </div>
            <?php if (!$can_add): ?>
            <a href="../all-plans" class="btn btn-sm btn-outline-secondary" style="font-size:.78rem">
                <i class="bi bi-arrow-up-circle me-1"></i>Fazer upgrade
            </a>
            <?php endif; ?>
        </div>

        <?php if ($artist_count === 0): ?>
        <!-- Empty state -->
        <div class="empty-state card">
            <i class="bi bi-person-x empty-icon"></i>
            <h4 class="fw-bold mb-2">Nenhum artista ainda</h4>
            <p class="text-muted small mb-4">Cria o primeiro perfil artístico para poderes lançar música nas
                plataformas.</p>
            <?php if ($can_add): ?>
            <a href="add-artist" class="btn px-5" style="background:var(--wasom);color:#fff">
                <i class="bi bi-person-plus me-2"></i>Criar primeiro artista
            </a>
            <?php endif; ?>
        </div>

        <?php else: ?>

        <!-- Search + Filter + View toggle -->
        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
            <div class="flex-fill" style="min-width:200px;max-width:340px">
                <div class="input-group">
                    <span class="input-group-text" style="border-radius:12px 0 0 12px"><i
                            class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control search-bar" id="search-input"
                        placeholder="Pesquisar artista..." oninput="applyFilters()" />
                </div>
            </div>
            <!-- Status filter pills -->
            <div class="d-flex gap-2 flex-wrap">
                <span class="filter-pill active" data-status="" onclick="setPill(this)">Todos <span
                        class="badge bg-secondary ms-1" id="pill-count-all"><?php echo $artist_count; ?></span></span>
                <span class="filter-pill" data-status="active" onclick="setPill(this)">Activos</span>
                <span class="filter-pill" data-status="processing" onclick="setPill(this)">Em análise</span>
                <span class="filter-pill" data-status="inactive" onclick="setPill(this)">Inactivos</span>
            </div>
            <!-- View toggle -->
            <div class="d-flex gap-1 ms-auto">
                <div class="view-btn active" id="btn-grid" onclick="setView('grid')" title="Grelha">
                    <i class="bi bi-grid-3x3-gap" style="font-size:.85rem"></i>
                </div>
                <div class="view-btn" id="btn-list" onclick="setView('list')" title="Lista">
                    <i class="bi bi-list-ul" style="font-size:.85rem"></i>
                </div>
            </div>
        </div>

        <!-- GRID VIEW -->
        <div id="view-grid">
            <div class="row g-3" id="grid-container">
                <?php foreach ($artists as $a): ?>
                <?php
            $photo = $a['photo_artist'] ? $photo_base . htmlspecialchars($a['photo_artist']) : null;
            $status_labels = ['active'=>'Activo','processing'=>'Em análise','inactive'=>'Inactivo','blocked'=>'Bloqueado'];
            $s_label  = $status_labels[$a['status_artist']] ?? 'Desconhecido';
            $s_class  = 'status-' . $a['status_artist'];
            $socials = [];
            if ($a['spotify_url'])   $socials[] = ['url'=>$a['spotify_url'],   'icon'=>'bi-spotify',   'bg'=>'#1db954'];
            if ($a['youtube_url'])   $socials[] = ['url'=>$a['youtube_url'],   'icon'=>'bi-youtube',   'bg'=>'#ff0000'];
            if ($a['instagram_url']) $socials[] = ['url'=>$a['instagram_url'], 'icon'=>'bi-instagram', 'bg'=>'#e1306c'];
            if ($a['tiktok_url'])    $socials[] = ['url'=>$a['tiktok_url'],    'icon'=>'bi-tiktok',    'bg'=>'#010101'];
            if ($a['facebook_url'])  $socials[] = ['url'=>$a['facebook_url'],  'icon'=>'bi-facebook',  'bg'=>'#1877f2'];
            if ($a['website_url'])   $socials[] = ['url'=>$a['website_url'],   'icon'=>'bi-globe',     'bg'=>'#6c757d'];
            ?>
                <div class="col-xl-3 col-lg-4 col-md-6 col-6 artist-item"
                    data-name="<?php echo strtolower(htmlspecialchars($a['stage_name'])); ?>"
                    data-real="<?php echo strtolower(htmlspecialchars($a['real_name'] ?? '')); ?>"
                    data-status="<?php echo htmlspecialchars($a['status_artist']); ?>"
                    data-genre="<?php echo strtolower(htmlspecialchars($a['genre_main'] ?? '')); ?>">
                    <div class="artist-card" onclick="openDetail(<?php echo $a['id_artist']; ?>)">
                        <!-- Cover / gradient -->
                        <div class="artist-card-cover"></div>
                        <!-- Status badge -->
                        <span class="artist-status-badge <?php echo $s_class; ?>"><?php echo $s_label; ?></span>
                        <!-- Avatar -->
                        <?php if ($photo): ?>
                        <img src="<?php echo $photo; ?>" class="artist-card-avatar"
                            alt="<?php echo htmlspecialchars($a['stage_name']); ?>" />
                        <?php else: ?>
                        <div class="artist-card-avatar-placeholder"><i class="bi bi-person"></i></div>
                        <?php endif; ?>

                        <div class="artist-card-body">
                            <div class="artist-card-name"><?php echo htmlspecialchars($a['stage_name']); ?></div>
                            <?php if ($a['real_name']): ?>
                            <div class="artist-card-real"><?php echo htmlspecialchars($a['real_name']); ?></div>
                            <?php endif; ?>
                            <?php if ($a['city'] || $a['country']): ?>
                            <div class="artist-card-location">
                                <i class="bi bi-geo-alt-fill me-1" style="font-size:.6rem"></i>
                                <?php echo htmlspecialchars(implode(', ', array_filter([$a['city'], $a['country']]))); ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($a['genre_main']): ?>
                            <div class="mt-1">
                                <span class="badge"
                                    style="background:rgba(255,0,137,.12);color:var(--wasom);font-size:.65rem">
                                    <?php echo htmlspecialchars($a['genre_main']); ?>
                                </span>
                            </div>
                            <?php endif; ?>

                            <!-- Stats -->
                            <div class="artist-card-stats">
                                <div class="artist-card-stat">
                                    <div class="num"><?php echo $a['total_albums']; ?></div>
                                    <div class="lbl">Álbuns</div>
                                </div>
                                <div class="artist-card-stat">
                                    <div class="num"><?php echo $a['albums_approved']; ?></div>
                                    <div class="lbl">Activos</div>
                                </div>
                            </div>

                            <!-- Socials (stop propagation — abre link sem abrir modal) -->
                            <?php if (!empty($socials)): ?>
                            <div class="artist-card-socials">
                                <?php foreach (array_slice($socials,0,5) as $s): ?>
                                <a href="<?php echo htmlspecialchars($s['url']); ?>" target="_blank" rel="noopener"
                                    class="social-pill" style="background:<?php echo $s['bg']; ?>"
                                    onclick="event.stopPropagation()"
                                    title="<?php echo htmlspecialchars($s['url']); ?>">
                                    <i class="bi <?php echo $s['icon']; ?>"></i>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Actions -->
                            <div class="d-flex gap-2 justify-content-center mt-3">
                                <a href="add-artist?edit=<?php echo $a['id_artist']; ?>"
                                    class="btn btn-outline-secondary btn-sm" style="font-size:.72rem"
                                    onclick="event.stopPropagation()">
                                    <i class="bi bi-pencil me-1"></i>Editar
                                </a>
                                <button class="btn btn-outline-secondary btn-sm" style="font-size:.72rem"
                                    onclick="event.stopPropagation();openDetail(<?php echo $a['id_artist']; ?>)">
                                    <i class="bi bi-eye me-1"></i>Ver
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="no-results" class="text-center py-5 text-muted">
                <i class="bi bi-search d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                Nenhum artista encontrado para essa pesquisa.
            </div>
        </div>

        <!-- LIST VIEW -->
        <div id="view-list" class="d-none">
            <div class="d-flex flex-column gap-2" id="list-container">
                <?php foreach ($artists as $a): ?>
                <?php
            $photo   = $a['photo_artist'] ? $photo_base . htmlspecialchars($a['photo_artist']) : null;
            $s_class = 'status-' . $a['status_artist'];
            ?>
                <div class="artist-list-row artist-item"
                    data-name="<?php echo strtolower(htmlspecialchars($a['stage_name'])); ?>"
                    data-real="<?php echo strtolower(htmlspecialchars($a['real_name'] ?? '')); ?>"
                    data-status="<?php echo htmlspecialchars($a['status_artist']); ?>"
                    data-genre="<?php echo strtolower(htmlspecialchars($a['genre_main'] ?? '')); ?>"
                    onclick="openDetail(<?php echo $a['id_artist']; ?>)">
                    <!-- Avatar -->
                    <?php if ($photo): ?>
                    <img src="<?php echo $photo; ?>" class="artist-list-avatar" alt="" />
                    <?php else: ?>
                    <div class="artist-list-avatar-ph"><i class="bi bi-person"></i></div>
                    <?php endif; ?>

                    <!-- Info -->
                    <div class="artist-list-info">
                        <div class="artist-list-name">
                            <?php echo htmlspecialchars($a['stage_name']); ?>
                            <span class="badge ms-1 <?php echo $s_class; ?>"
                                style="font-size:.6rem;vertical-align:middle">
                                <?php echo $status_labels[$a['status_artist']] ?? '—'; ?>
                            </span>
                        </div>
                        <div class="artist-list-meta">
                            <?php if ($a['real_name']): ?>
                            <span><?php echo htmlspecialchars($a['real_name']); ?></span>
                            <?php endif; ?>
                            <?php if ($a['genre_main']): ?>
                            <span class="ms-1">· <?php echo htmlspecialchars($a['genre_main']); ?></span>
                            <?php endif; ?>
                            <?php if ($a['city'] || $a['country']): ?>
                            <span class="ms-1">· <i class="bi bi-geo-alt-fill" style="font-size:.6rem"></i>
                                <?php echo htmlspecialchars(implode(', ', array_filter([$a['city'], $a['country']]))); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Albums count -->
                    <div class="artist-list-albums d-none d-md-flex">
                        <?php if ($a['albums_approved']): ?>
                        <span class="status-badge sb-approved"><?php echo $a['albums_approved']; ?>
                            aprovado<?php echo $a['albums_approved']>1?'s':''; ?></span>
                        <?php endif; ?>
                        <?php if ($a['albums_pending']): ?>
                        <span class="status-badge sb-pending"><?php echo $a['albums_pending']; ?>
                            pendente<?php echo $a['albums_pending']>1?'s':''; ?></span>
                        <?php endif; ?>
                        <?php if ($a['albums_draft']): ?>
                        <span class="status-badge sb-draft"><?php echo $a['albums_draft']; ?>
                            rascunho<?php echo $a['albums_draft']>1?'s':''; ?></span>
                        <?php endif; ?>
                        <?php if (!$a['total_albums']): ?>
                        <span class="text-muted" style="font-size:.75rem">Sem lançamentos</span>
                        <?php endif; ?>
                    </div>

                    <!-- Social quick links -->
                    <div class="artist-list-socials d-none d-lg-flex" onclick="event.stopPropagation()">
                        <?php if ($a['spotify_url']): ?>
                        <a href="<?php echo htmlspecialchars($a['spotify_url']); ?>" target="_blank" rel="noopener"
                            class="social-pill" style="background:#1db954" title="Spotify"><i
                                class="bi bi-spotify"></i></a>
                        <?php endif; ?>
                        <?php if ($a['youtube_url']): ?>
                        <a href="<?php echo htmlspecialchars($a['youtube_url']); ?>" target="_blank" rel="noopener"
                            class="social-pill" style="background:#ff0000" title="YouTube"><i
                                class="bi bi-youtube"></i></a>
                        <?php endif; ?>
                        <?php if ($a['instagram_url']): ?>
                        <a href="<?php echo htmlspecialchars($a['instagram_url']); ?>" target="_blank" rel="noopener"
                            class="social-pill" style="background:#e1306c" title="Instagram"><i
                                class="bi bi-instagram"></i></a>
                        <?php endif; ?>
                    </div>

                    <!-- Action button -->
                    <div onclick="event.stopPropagation()">
                        <a href="add-artist?edit=<?php echo $a['id_artist']; ?>"
                            class="btn btn-outline-secondary btn-sm" style="font-size:.75rem">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="no-results-list" class="text-center py-5 text-muted d-none">
                <i class="bi bi-search d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                Nenhum artista encontrado.
            </div>
        </div>

        <?php endif; ?>
    </div><!-- /container -->

    <!-- Bottom nav mobile -->
    <nav class="bottom-nav d-lg-none">
        <ul class="nav justify-content-around">
            <?php if ($is_collab): ?>
            <li class="nav-item"><a class="nav-link"
                    href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/collab/overview"><i
                        class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
            <?php if (collabCan('releases')): ?><li class="nav-item"><a class="nav-link"
                    href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/launch/releases"><i
                        class="bi bi-disc"></i><span>Lançamentos</span></a></li><?php endif; ?>
            <li class="nav-item"><a class="nav-link active"
                    href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/artists/artists-list"><i
                        class="bi bi-people"></i><span>Artistas</span></a></li>
            <?php if (collabCan('stats')): ?><li class="nav-item"><a class="nav-link"
                    href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/analytics/statistics"><i
                        class="bi bi-bar-chart"></i><span>Stats</span></a></li><?php endif; ?>
            <?php if (collabCan('finances')): ?><li class="nav-item"><a class="nav-link"
                    href="<?php echo rtrim(APP_URL,'/'); ?>/dashboard/finances/overview"><i
                        class="bi bi-currency-dollar"></i><span>Finanças</span></a></li><?php endif; ?>
            <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="../painel"><i
                        class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../launch/releases"><i
                        class="bi bi-disc"></i><span>Lançamentos</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../analytics/statistics"><i
                        class="bi bi-bar-chart"></i><span>Estatísticas</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
                        class="bi bi-currency-dollar"></i><span>Finanças</span></a></li>
            <li class="nav-item"><a class="nav-link active" href="artists-list"><i
                        class="bi bi-person"></i><span>Artistas</span></a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- ════════════════════════════════════
     OFFCANVAS — Detalhe do Artista
════════════════════════════════════ -->
    <div class="offcanvas offcanvas-end offcanvas-detail" tabindex="-1" id="artistDetail">
        <div class="d-flex flex-column h-100">
            <!-- Cover + close button -->
            <div class="detail-cover" id="det-cover">
                <button type="button" class="btn btn-sm position-absolute top-0 end-0 m-2"
                    style="background:rgba(0,0,0,.4);color:#fff;border:none;border-radius:8px"
                    data-bs-dismiss="offcanvas">
                    <i class="bi bi-x-lg"></i>
                </button>
                <div id="det-avatar-wrap"></div>
            </div>

            <!-- Scrollable body -->
            <div class="detail-body flex-grow-1" id="det-body">
                <!-- Name + genre -->
                <div class="mb-1">
                    <h4 class="fw-bold mb-0" id="det-name"></h4>
                    <span class="text-muted small" id="det-real"></span>
                    <div class="mt-1" id="det-badges"></div>
                </div>

                <!-- Location + joined -->
                <div class="d-flex gap-3 text-muted small mb-3 flex-wrap" id="det-meta"></div>

                <!-- Bio -->
                <div id="det-bio-wrap" class="mb-3 d-none">
                    <p class="small mb-0" id="det-bio" style="line-height:1.7"></p>
                </div>

                <!-- Stats -->
                <div class="row g-2 mb-4" id="det-stats"></div>

                <!-- Social Links -->
                <div id="det-socials-wrap" class="mb-4 d-none">
                    <div class="fw-semibold small mb-2" style="color:var(--wasom)"><i class="bi bi-share me-1"></i>Links
                        & Redes</div>
                    <div class="d-flex gap-2 flex-wrap" id="det-socials"></div>
                </div>

                <!-- Albums -->
                <div id="det-albums-wrap">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold small" style="color:var(--wasom)"><i
                                class="bi bi-disc me-1"></i>Lançamentos</div>
                        <a id="det-see-all" href="#" class="small" style="color:var(--wasom);text-decoration:none">Ver
                            todos →</a>
                    </div>
                    <div id="det-albums"></div>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-2 mt-4">
                    <a id="det-edit-btn" href="#" class="btn btn-sm flex-fill fw-semibold"
                        style="background:var(--wasom);color:#fff">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </a>
                    <a href="../launch/creat-release" class="btn btn-sm btn-outline-secondary flex-fill fw-semibold">
                        <i class="bi bi-plus-circle me-1"></i>Lançamento
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Logout -->
    <div class="modal fade" id="logoutwasomupfy" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                            style="width:44px;height:44px;background:rgba(220,53,69,.1)">
                            <i class="bi bi-box-arrow-right fs-5 text-danger"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0">Terminar sessão</h5>
                            <small class="text-muted">@<?php echo $user_name; ?></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-2">
                    <div class="rounded-3 p-3" style="background:rgba(0,0,0,.04);font-size:.82rem">
                        <div class="row g-2">
                            <div class="col-6 d-flex gap-2"><i class="bi bi-clock text-muted mt-1"></i>
                                <div>
                                    <div class="text-muted">Duração</div>
                                    <div class="fw-semibold"><?php echo $dur; ?></div>
                                </div>
                            </div>
                            <div class="col-6 d-flex gap-2"><i class="bi bi-calendar3 text-muted mt-1"></i>
                                <div>
                                    <div class="text-muted">Último acesso</div>
                                    <div class="fw-semibold"><?php echo $last_login_str; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-center mt-3 mb-0 small">Tens a certeza?</p>
                </div>
                <div class="modal-footer border-0 gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-fill"
                        data-bs-dismiss="modal">Continuar</button>
                    <button class="btn btn-danger flex-fill" onclick="window.location='/wasomupfy/logout'"><i
                            class="bi bi-box-arrow-right me-1"></i>Terminar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="../../js/theme.wp.js"></script>
    <script src="../../js/wp.tools.js"></script>

    <script>
    // ── PHP data ──────────────────────────────────
    const ARTISTS = <?php echo $artists_json; ?>;
    const ALBUMS_MAP = <?php echo $albums_json; ?>;
    const PHOTO_BASE = '<?php echo $photo_base; ?>';
    const COVER_BASE = '<?php echo $cover_base; ?>';
    const BASE_URL = '<?php echo rtrim(APP_URL, '/'); ?>';

    toastr.options = {
        progressBar: true,
        closeButton: true,
        positionClass: 'toast-top-right',
        timeOut: 3000
    };

    // ── View toggle ───────────────────────────────
    let currentView = 'grid';

    function setView(v) {
        currentView = v;
        document.getElementById('view-grid').classList.toggle('d-none', v !== 'grid');
        document.getElementById('view-list').classList.toggle('d-none', v !== 'list');
        document.getElementById('btn-grid').classList.toggle('active', v === 'grid');
        document.getElementById('btn-list').classList.toggle('active', v === 'list');
        localStorage.setItem('wasom_artists_view', v);
    }
    // Restore saved view
    const savedView = localStorage.getItem('wasom_artists_view');
    if (savedView === 'list') setView('list');

    // ── Search + Filter ───────────────────────────
    let activeStatus = '';

    function setPill(el) {
        document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
        el.classList.add('active');
        activeStatus = el.dataset.status;
        applyFilters();
    }

    function applyFilters() {
        const q = (document.getElementById('search-input')?.value || '').toLowerCase().trim();
        const items = document.querySelectorAll('.artist-item');
        let visible = 0;

        items.forEach(item => {
            const name = item.dataset.name || '';
            const real = item.dataset.real || '';
            const status = item.dataset.status || '';
            const genre = item.dataset.genre || '';

            const matchQ = !q || name.includes(q) || real.includes(q) || genre.includes(q);
            const matchS = !activeStatus || status === activeStatus;
            const show = matchQ && matchS;

            item.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        // No results
        const noG = document.getElementById('no-results');
        const noL = document.getElementById('no-results-list');
        if (noG) noG.style.display = visible === 0 ? 'block' : 'none';
        if (noL) noL.classList.toggle('d-none', visible > 0);

        document.getElementById('pill-count-all').textContent = visible;
    }

    // ── Detail offcanvas ──────────────────────────
    const detailOffcanvas = new bootstrap.Offcanvas(document.getElementById('artistDetail'), {
        scroll: true
    });

    const STATUS_MAP = {
        active: 'Activo',
        processing: 'Em análise',
        inactive: 'Inactivo',
        blocked: 'Bloqueado'
    };
    const STATUS_BADGE = {
        active: 'sb-approved',
        processing: 'sb-processing',
        inactive: 'sb-draft',
        blocked: 'sb-rejected'
    };
    const TYPE_MAP = {
        single: 'Single',
        EP: 'EP',
        album: 'Álbum',
        mixtape: 'Mixtape'
    };
    const ALBUM_STATUS_BADGE = {
        approved: 'sb-approved',
        pending: 'sb-pending',
        rejected: 'sb-rejected',
        draft: 'sb-draft',
        under_review: 'sb-under_review'
    };
    const ALBUM_STATUS_LABEL = {
        approved: 'Aprovado',
        pending: 'Pendente',
        rejected: 'Rejeitado',
        draft: 'Rascunho',
        under_review: 'Em revisão'
    };

    const SOCIAL_META = {
        spotify_url: {
            icon: 'bi-spotify',
            label: 'Spotify',
            bg: '#1db954'
        },
        youtube_url: {
            icon: 'bi-youtube',
            label: 'YouTube',
            bg: '#ff0000'
        },
        instagram_url: {
            icon: 'bi-instagram',
            label: 'Instagram',
            bg: '#e1306c'
        },
        tiktok_url: {
            icon: 'bi-tiktok',
            label: 'TikTok',
            bg: '#010101'
        },
        facebook_url: {
            icon: 'bi-facebook',
            label: 'Facebook',
            bg: '#1877f2'
        },
        website_url: {
            icon: 'bi-globe',
            label: 'Site / Apple',
            bg: '#6c757d'
        },
    };

    function openDetail(id) {
        const artist = ARTISTS.find(a => a.id_artist == id);
        if (!artist) return;
        const albums = ALBUMS_MAP[id] || [];

        // Cover
        const coverEl = document.getElementById('det-cover');
        coverEl.style.background = 'linear-gradient(135deg,#FF0089,#FF4D4D)';

        // Avatar
        const avatarWrap = document.getElementById('det-avatar-wrap');
        if (artist.photo_artist) {
            avatarWrap.innerHTML =
                `<img src="${PHOTO_BASE}${artist.photo_artist}" class="detail-avatar" alt="${artist.stage_name}"/>`;
        } else {
            avatarWrap.innerHTML = `<div class="detail-avatar-ph"><i class="bi bi-person"></i></div>`;
        }

        // Name + real + badges
        document.getElementById('det-name').textContent = artist.stage_name;
        document.getElementById('det-real').textContent = artist.real_name || '';
        const badgesEl = document.getElementById('det-badges');
        let badges = '';
        if (artist.genre_main) badges +=
            `<span class="badge me-1" style="background:rgba(255,0,137,.12);color:var(--wasom)">${artist.genre_main}</span>`;
        if (artist.genre_secondary) badges +=
            `<span class="badge me-1 bg-light text-muted">${artist.genre_secondary}</span>`;
        const sc = STATUS_BADGE[artist.status_artist] || 'sb-draft';
        badges += `<span class="status-badge ${sc}">${STATUS_MAP[artist.status_artist] || '—'}</span>`;
        badgesEl.innerHTML = badges;

        // Meta: location + joined date
        const metaEl = document.getElementById('det-meta');
        let meta = '';
        if (artist.city || artist.country) {
            meta +=
                `<span><i class="bi bi-geo-alt-fill me-1" style="font-size:.65rem"></i>${[artist.city, artist.country].filter(Boolean).join(', ')}</span>`;
        }
        if (artist.creat_artist) {
            const d = new Date(artist.creat_artist);
            meta +=
                `<span><i class="bi bi-calendar3 me-1" style="font-size:.65rem"></i>Desde ${d.toLocaleDateString('pt-PT', {month:'long',year:'numeric'})}</span>`;
        }
        metaEl.innerHTML = meta;

        // Bio
        const biowrap = document.getElementById('det-bio-wrap');
        const bioEl = document.getElementById('det-bio');
        if (artist.bio) {
            bioEl.textContent = artist.bio;
            biowrap.classList.remove('d-none');
        } else {
            biowrap.classList.add('d-none');
        }

        // Stats
        const statsEl = document.getElementById('det-stats');
        statsEl.innerHTML = `
        <div class="col-4"><div class="detail-stat-card"><div class="num">${artist.total_albums}</div><div class="lbl">Total</div></div></div>
        <div class="col-4"><div class="detail-stat-card"><div class="num">${artist.albums_approved}</div><div class="lbl">Activos</div></div></div>
        <div class="col-4"><div class="detail-stat-card"><div class="num">${artist.albums_pending}</div><div class="lbl">Pendentes</div></div></div>
    `;

        // Social links
        const socialsWrap = document.getElementById('det-socials-wrap');
        const socialsEl = document.getElementById('det-socials');
        let socialsHTML = '';
        for (const [key, meta2] of Object.entries(SOCIAL_META)) {
            if (artist[key]) {
                socialsHTML += `
                <a href="${artist[key]}" target="_blank" rel="noopener"
                   class="d-flex align-items-center gap-2 text-decoration-none rounded-3 px-3 py-2"
                   style="background:${meta2.bg}15;border:1px solid ${meta2.bg}40;color:inherit;font-size:.8rem;flex:1;min-width:130px">
                    <span class="social-pill" style="background:${meta2.bg};width:28px;height:28px;font-size:.75rem;flex-shrink:0">
                        <i class="bi ${meta2.icon}"></i>
                    </span>
                    <div>
                        <div style="font-weight:700;font-size:.75rem">${meta2.label}</div>
                        <div class="text-muted text-truncate" style="font-size:.65rem;max-width:120px">${artist[key].replace('https://','')}</div>
                    </div>
                    <i class="bi bi-box-arrow-up-right ms-auto text-muted" style="font-size:.65rem"></i>
                </a>`;
            }
        }
        if (socialsHTML) {
            socialsEl.innerHTML = `<div class="d-flex flex-wrap gap-2">${socialsHTML}</div>`;
            socialsWrap.classList.remove('d-none');
        } else {
            socialsWrap.classList.add('d-none');
        }

        // Albums list (max 5 in panel)
        const albumsEl = document.getElementById('det-albums');
        const seeAllEl = document.getElementById('det-see-all');
        seeAllEl.href = `${BASE_URL}/dashboard/launch/releases?artist=${id}`;

        if (albums.length === 0) {
            albumsEl.innerHTML = `<p class="text-muted small">Nenhum lançamento ainda.</p>`;
        } else {
            let html = '';
            albums.slice(0, 6).forEach(alb => {
                const coverSrc = alb.img_cover ? COVER_BASE + alb.img_cover : null;
                const imgEl = coverSrc ?
                    `<img src="${coverSrc}" class="album-cover-sm" alt="${alb.title_album}"/>` :
                    `<div class="album-cover-sm-ph"><i class="bi bi-disc"></i></div>`;
                const sbClass = ALBUM_STATUS_BADGE[alb.status_album] || 'sb-draft';
                const sbLabel = ALBUM_STATUS_LABEL[alb.status_album] || alb.status_album;
                const typeLabel = TYPE_MAP[alb.type_album] || alb.type_album;
                const releaseDate = alb.release_date ? new Date(alb.release_date).toLocaleDateString('pt-PT') :
                    '—';
                html += `
                <div class="album-row">
                    ${imgEl}
                    <div style="flex:1;min-width:0">
                        <div class="fw-semibold text-truncate" style="font-size:.83rem">${alb.title_album}</div>
                        <div class="text-muted" style="font-size:.72rem">${typeLabel} · ${releaseDate}</div>
                    </div>
                    <span class="status-badge ${sbClass} ms-2">${sbLabel}</span>
                </div>`;
            });
            if (albums.length > 6) {
                html +=
                    `<div class="text-center mt-2"><a href="${seeAllEl.href}" style="font-size:.78rem;color:var(--wasom)">Ver mais ${albums.length - 6} lançamento${albums.length-6>1?'s':''} →</a></div>`;
            }
            albumsEl.innerHTML = html;
        }

        // Edit button
        document.getElementById('det-edit-btn').href = `add-artist?edit=${id}`;

        detailOffcanvas.show();
    }
    </script>
</body>

</html>