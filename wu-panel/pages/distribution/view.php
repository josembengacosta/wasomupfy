<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Detalhe de Lançamento
// Arquivo: wu-panel/pages/distribution/view.php
// Rota:    wu-panel/releases/view?id=X
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'music.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$id_album = (int)($_GET['id'] ?? 0);
if (!$id_album) {
    http_response_code(404);
    exit('Álbum não encontrado.');
}

// ── Buscar álbum completo ────────────────────────────────────
$stmt = $db->prepare("
    SELECT
        al.*,
        ar.id_artist, ar.stage_name, ar.photo_artist, ar.genre_main AS artist_genre,
        u.id_users, u.first_name, u.second_name, u.email_user, u.photo_user,
        u.name_artist_band, u.status_user,
        up.id_plan, up.status_plan, up.releases_used, up.releases_limit, up.started_at,
        pl.name_plan, pl.slug_plan, pl.royalty_rate,
        CONCAT(e.first_name,' ',COALESCE(e.second_name,'')) AS approved_by_name
    FROM _album al
    LEFT JOIN _artist ar ON ar.id_artist = al.id_artist
    LEFT JOIN _users u ON u.id_users = al.id_users
    LEFT JOIN _user_plan up ON up.id_users = al.id_users AND up.status_plan = 'active'
    LEFT JOIN _plans pl ON pl.id_plan = up.id_plan
    LEFT JOIN _employees e ON e.id_employees = al.approved_by
    WHERE al.id_album = ?
");
$stmt->execute([$id_album]);
$album = $stmt->fetch();

if (!$album) {
    http_response_code(404);
    exit('Álbum não encontrado.');
}

// ── Buscar faixas ─────────────────────────────────────────────
$tracks = $db->prepare("
    SELECT *
    FROM _track
    WHERE id_album = ?
    ORDER BY track_number ASC, creat_track ASC
");
$tracks->execute([$id_album]);
$tracks = $tracks->fetchAll();

// ── Pedido de revisão pendente ──────────────────────────────
$review_request = null;
if ($album['status_album'] === 'under_review') {
    $rev_stmt = $db->prepare("
        SELECT *
        FROM _album_review_request
        WHERE id_album = ? AND status_request = 'pending'
        ORDER BY creat_request DESC
        LIMIT 1
    ");
    $rev_stmt->execute([$id_album]);
    $review_request = $rev_stmt->fetch();
}

// ── Verificar ficheiros de áudio ──────────────────────────────
$audio_base = dirname(__DIR__, 3) . '/assets/uploads/audio/';
foreach ($tracks as &$t) {
    $t['audio_exists'] = !empty($t['audio_file']) && file_exists($audio_base . $t['audio_file']);
}
unset($t);

// ── Helpers ───────────────────────────────────────────────────
$base_url   = APP_URL . '/' . ADMIN_PATH;
$csrf       = $_SESSION['admin_csrf_token'];
$user_name  = trim($album['first_name'] . ' ' . ($album['second_name'] ?? ''));
$artist_name = $album['stage_name'] ?: ($album['name_artist_band'] ?: $user_name);
$cover_url  = $album['img_cover']
    ? APP_URL . '/assets/comprovantes/uploads/covers/' . $album['img_cover']
    : null;

function vw_duration(int $sec): string
{
    if (!$sec) return '—';
    return sprintf('%d:%02d', intdiv($sec, 60), $sec % 60);
}
function vw_status_label(string $s): array // [label, color, bg]
{
    return match ($s) {
        'pending'      => ['Pendente de Revisão', '#92400e', 'rgba(234,179,8,.15)'],
        'under_review' => ['Em Revisão',           '#1e40af', 'rgba(59,130,246,.15)'],
        'approved'     => ['Aprovado',             '#166534', 'rgba(34,197,94,.15)'],
        'rejected'     => ['Rejeitado',            '#991b1b', 'rgba(239,68,68,.15)'],
        'draft'        => ['Rascunho',             '#374151', 'rgba(107,114,128,.15)'],
        'deleting'     => ['A Eliminar',           '#92400e', 'rgba(249,115,22,.15)'],
        default        => [ucfirst($s),            '#374151', 'rgba(107,114,128,.15)'],
    };
}
function vw_avatar(string $name, ?string $photo, string $path, int $s = 36): string
{
    $p   = explode(' ', trim($name), 2);
    $ini = mb_strtoupper(mb_substr($p[0] ?? '', 0, 1, 'UTF-8'), 'UTF-8')
        . mb_strtoupper(mb_substr($p[1] ?? '', 0, 1, 'UTF-8'), 'UTF-8');
    $cl  = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308', '#3b82f6', '#ef4444'];
    $c   = $cl[abs(crc32($name)) % count($cl)];
    $fs  = round($s * 0.3);
    if ($photo) {
        return '<img src="' . APP_URL . '/' . $path . '/' . htmlspecialchars($photo) . '"
                     width="' . $s . '" height="' . $s . '"
                     style="border-radius:50%;object-fit:cover;border:2px solid rgba(255,0,137,.2)"
                     onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'" alt="">
                <div style="width:' . $s . 'px;height:' . $s . 'px;border-radius:50%;background:' . $c . ';
                            display:none;align-items:center;justify-content:center;
                            font-weight:700;font-size:' . $fs . 'px;color:#fff">' . $ini . '</div>';
    }
    return '<div style="width:' . $s . 'px;height:' . $s . 'px;border-radius:50%;background:' . $c . ';
                         display:flex;align-items:center;justify-content:center;
                         font-weight:700;font-size:' . $fs . 'px;color:#fff">' . $ini . '</div>';
}

[$status_label, $status_color, $status_bg] = vw_status_label($album['status_album']);
$can_approve = hasPermission($admin_id, 'music.approve');
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
    <title><?php echo htmlspecialchars($album['title_album']); ?> — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
    /* ── Album header ── */
    .album-hero {
        background: linear-gradient(135deg, #0f0f1a, #1a1a2e);
        border-radius: 16px;
        padding: 28px;
        color: #fff;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }

    .album-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 80% 50%, rgba(255, 0, 137, .15) 0%, transparent 60%);
    }

    .album-cover-lg {
        width: 160px;
        height: 160px;
        border-radius: 12px;
        object-fit: cover;
        box-shadow: 0 16px 40px rgba(0, 0, 0, .5);
        flex-shrink: 0;
    }

    .album-cover-lg-ph {
        width: 160px;
        height: 160px;
        border-radius: 12px;
        background: rgba(255, 0, 137, .12);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .album-title {
        font-size: 1.6rem;
        font-weight: 800;
        color: #fff;
        line-height: 1.2;
    }

    .album-meta {
        font-size: .8rem;
        color: rgba(255, 255, 255, .6);
    }

    .album-meta strong {
        color: rgba(255, 255, 255, .9);
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: 30px;
        font-size: .78rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    /* ── Info row ── */
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 9px 0;
        border-bottom: 1px solid var(--border-color, #e8e8f0);
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-lbl {
        font-size: .76rem;
        font-weight: 600;
        opacity: .55;
        min-width: 150px;
    }

    .info-val {
        font-size: .82rem;
        text-align: right;
    }

    /* ── Faixas ── */
    .track-row {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color, #e8e8f0);
        transition: background .15s;
    }

    .track-row:hover {
        background: var(--table-hover, rgba(0, 0, 0, .02));
    }

    .track-row:last-child {
        border-bottom: none;
    }

    .track-num {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: rgba(255, 0, 137, .1);
        color: #FF0089;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .7rem;
        font-weight: 800;
        flex-shrink: 0;
    }

    .track-title {
        font-size: .84rem;
        font-weight: 700;
    }

    .track-meta {
        font-size: .72rem;
        opacity: .55;
        margin-top: 2px;
    }

    audio {
        height: 28px;
        border-radius: 20px;
        width: 100%;
        max-width: 220px;
    }

    /* ── Acção buttons ── */
    .action-section {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 14px;
        padding: 20px;
    }

    .btn-approve {
        background: #22c55e;
        border: none;
        color: #fff;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 700;
    }

    .btn-approve:hover {
        background: #16a34a;
        color: #fff;
    }

    .btn-reject {
        background: #ef4444;
        border: none;
        color: #fff;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 700;
    }

    .btn-reject:hover {
        background: #dc2626;
        color: #fff;
    }

    .btn-process {
        background: #3b82f6;
        border: none;
        color: #fff;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 700;
    }

    .btn-process:hover {
        background: #2563eb;
        color: #fff;
    }

    .btn-reopen {
        background: #f97316;
        border: none;
        color: #fff;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 700;
    }

    .btn-reopen:hover {
        background: #ea580c;
        color: #fff;
    }

    /* ── Explicit badge ── */
    .badge-explicit {
        background: #ef4444;
        color: #fff;
        font-size: .6rem;
        padding: 1px 5px;
        border-radius: 3px;
        font-weight: 700;
        vertical-align: middle;
    }

    /* ── Modal inputs ── */
    .modal-input-section label {
        font-size: .8rem;
        font-weight: 600;
        margin-bottom: 4px;
    }

    /* ── Acordeão de faixas ── */
    .accordion-button {
        background-color: var(--card-bg, #fff) !important;
        color: var(--text-color, #212529) !important;
        border: 1px solid var(--border-color, #e8e8f0) !important;
    }

    .accordion-button:not(.collapsed) {
        background-color: var(--card-bg, #fff) !important;
        color: #FF0089 !important;
        box-shadow: none;
    }

    .accordion-button::after {
        filter: none;
        /* manter seta padrão */
    }

    .accordion-item {
        background-color: transparent;
        border-color: var(--border-color, #e8e8f0);
    }

    .accordion-body {
        background-color: var(--card-bg, #fff);
        color: var(--text-color, #212529);
        font-size: .82rem;
    }

    .track-ac-detail-row {
        display: flex;
        justify-content: space-between;
        padding: 4px 0;
    }

    .track-ac-detail-label {
        opacity: .6;
        font-size: .75rem;
    }

    .track-ac-detail-value {
        font-weight: 500;
    }

    /* Ajuste para o player de áudio dentro do header */
    .accordion-header .track-header-content {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
    }

    .accordion-header .track-title-col {
        flex: 1;
        min-width: 0;
    }
    </style>
</head>

<body>

    <div class="wrapper">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php require_once __DIR__ . '/../../include/sidebar.php'; ?>

        <div class="content w-100" id="mainContent">
            <?php require_once __DIR__ . '/../../include/navbar.php'; ?>

            <div class="container-fluid p-0">

                <!-- Breadcrumb + botão voltar -->
                <div class="d-flex align-items-center gap-3 mb-3 mt-2">
                    <a href="<?php echo $base_url; ?>/releases" class="btn btn-sm btn-outline-secondary"
                        style="border-radius:8px">
                        <i class="bi bi-arrow-left me-1"></i> Lançamentos
                    </a>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="<?php echo $base_url; ?>" class="text-secondary">Home</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="<?php echo $base_url; ?>/releases" class="text-secondary">Lançamentos</a>
                            </li>
                            <li class="breadcrumb-item active text-white-stable">
                                <?php echo htmlspecialchars($album['title_album']); ?>
                            </li>
                        </ol>
                    </nav>
                </div>

                <!-- ══ SECÇÃO 1 — HEADER DO ÁLBUM ══ -->
                <div class="album-hero mb-4">
                    <div class="d-flex gap-4 flex-wrap align-items-start" style="position:relative;z-index:1">
                        <!-- Capa -->
                        <?php if ($cover_url): ?>
                        <img src="<?php echo $cover_url; ?>" class="album-cover-lg" alt="Capa"
                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <div class="album-cover-lg-ph" style="display:none">
                            <i class="bi bi-vinyl" style="font-size:3rem;color:rgba(255,255,255,.3)"></i>
                        </div>
                        <?php else: ?>
                        <div class="album-cover-lg-ph">
                            <i class="bi bi-vinyl" style="font-size:3rem;color:rgba(255,255,255,.3)"></i>
                        </div>
                        <?php endif; ?>

                        <!-- Info principal -->
                        <div class="flex-grow-1" style="min-width:0">
                            <!-- Status pill -->
                            <div class="mb-2">
                                <span class="status-pill"
                                    style="background:<?php echo $status_bg; ?>;color:<?php echo $status_color; ?>">
                                    <i class="bi bi-circle-fill" style="font-size:.45rem"></i>
                                    <?php echo $status_label; ?>
                                </span>
                                <?php if ($album['upc']): ?>
                                <span
                                    style="margin-left:10px;font-size:.72rem;color:rgba(255,255,255,.5);font-family:monospace">
                                    UPC: <?php echo htmlspecialchars($album['upc']); ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <!-- Tipo + Título -->
                            <div class="album-meta mb-1">
                                <?php echo strtoupper($album['type_album']); ?>
                                <?php echo $album['language'] ? ' · ' . htmlspecialchars($album['language']) : ''; ?>
                                <?php echo $album['genre_main'] ? ' · ' . htmlspecialchars($album['genre_main']) : ''; ?>
                            </div>
                            <h1 class="album-title mb-2"><?php echo htmlspecialchars($album['title_album']); ?></h1>

                            <!-- Artista + utilizador -->
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($album['photo_artist']): ?>
                                    <?php echo vw_avatar($artist_name, $album['photo_artist'], 'assets/comprovantes/uploads/artists', 32); ?>
                                    <?php else: ?>
                                    <?php echo vw_avatar($artist_name, null, '', 32); ?>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-size:.85rem;font-weight:700;color:#fff">
                                            <?php echo htmlspecialchars($artist_name); ?>
                                        </div>
                                        <div class="album-meta">Artista</div>
                                    </div>
                                </div>
                                <div style="width:1px;height:36px;background:rgba(255,255,255,.15)"></div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php echo vw_avatar($user_name, $album['photo_user'], 'assets/comprovantes/uploads/users', 32); ?>
                                    <div>
                                        <div style="font-size:.82rem;font-weight:600;color:#fff">
                                            <?php echo htmlspecialchars($user_name); ?>
                                        </div>
                                        <div class="album-meta"><?php echo htmlspecialchars($album['email_user']); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($album['name_plan']): ?>
                                <span
                                    style="background:rgba(255,0,137,.25);color:#fff;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700">
                                    <?php echo htmlspecialchars($album['name_plan']); ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <!-- Metadados ── Data, Gravadora -->
                            <div class="album-meta mt-3 d-flex gap-4 flex-wrap">
                                <span>
                                    <i class="bi bi-calendar3 me-1"></i>
                                    Enviado:
                                    <strong><?php echo date('d/m/Y', strtotime($album['creat_album'])); ?></strong>
                                </span>
                                <?php if ($album['release_date']): ?>
                                <span>
                                    <i class="bi bi-calendar-event me-1"></i>
                                    Lançamento:
                                    <strong><?php echo date('d/m/Y', strtotime($album['release_date'])); ?></strong>
                                </span>
                                <?php endif; ?>
                                <?php if ($album['label_name']): ?>
                                <span>
                                    <i class="bi bi-building me-1"></i>
                                    Gravadora: <strong><?php echo htmlspecialchars($album['label_name']); ?></strong>
                                </span>
                                <?php endif; ?>
                                <?php if ($album['approved_at']): ?>
                                <span>
                                    <i class="bi bi-check-circle me-1"></i>
                                    Aprovado:
                                    <strong><?php echo date('d/m/Y', strtotime($album['approved_at'])); ?></strong>
                                    <?php if ($album['approved_by_name']): ?>
                                    por <strong><?php echo htmlspecialchars($album['approved_by_name']); ?></strong>
                                    <?php endif; ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($album['smartlink']): ?>
                            <div class="mt-2">
                                <a href="<?php echo htmlspecialchars($album['smartlink']); ?>" target="_blank"
                                    rel="noopener" style="color:#FF0089;font-size:.8rem">
                                    <i class="bi bi-link-45deg me-1"></i>
                                    <?php echo htmlspecialchars($album['smartlink']); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Botões de download -->
                        <div class="d-flex flex-column gap-2 flex-shrink-0">
                            <a href="<?php echo $base_url; ?>/releases/download-zip?id=<?php echo (int)$album['id_album']; ?>"
                                class="btn btn-sm"
                                style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:8px;white-space:nowrap">
                                <i class="bi bi-file-zip me-1"></i> Download ZIP
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row g-4">

                    <!-- Coluna principal -->
                    <div class="col-lg-8">

                        <!-- ══ SECÇÃO 2 — FAIXAS ══ -->
                        <div class="card mb-4" style="border-radius:14px;overflow:hidden">
                            <div class="d-flex align-items-center justify-content-between px-3 py-2"
                                style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                                <span style="font-size:.88rem;font-weight:700">
                                    <i class="bi bi-music-note-list me-2"></i>
                                    Faixas <span style="color:#FF0089">(<?php echo count($tracks); ?>)</span>
                                </span>
                            </div>

                            <?php if (empty($tracks)): ?>
                            <div style="text-align:center;padding:32px;opacity:.4">
                                <i class="bi bi-music-note" style="font-size:2rem;display:block;margin-bottom:8px"></i>
                                <p class="mb-0">Nenhuma faixa registada.</p>
                            </div>
                            <?php else: ?>
                            <div class="accordion" id="tracksAdminAccordion">
                                <?php foreach ($tracks as $idx => $t):
                $audio_url = $t['audio_exists']
                    ? APP_URL . '/assets/uploads/audio/' . $t['audio_file']
                    : null;
                $track_num  = (int)($t['track_number'] ?: $idx + 1);
                $collapse_id = 'collapseTrack' . $t['id_track'];
                $heading_id  = 'headingTrack' . $t['id_track'];
            ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="<?php echo $heading_id; ?>">
                                        <button class="accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#<?php echo $collapse_id; ?>"
                                            aria-expanded="false" aria-controls="<?php echo $collapse_id; ?>">
                                            <div class="track-header-content">
                                                <span class="track-num"><?php echo $track_num; ?></span>
                                                <div class="track-title-col">
                                                    <div class="track-title">
                                                        <?php echo htmlspecialchars($t['title_track']); ?>
                                                        <?php if ($t['explicit'] === 'YES'): ?>
                                                        <span class="badge-explicit ms-1">E</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div style="font-size:.72rem;opacity:.6;white-space:nowrap">
                                                        <?php echo vw_duration((int)($t['duration_seconds'] ?? 0)); ?>
                                                    </div>
                                                </div>
                                                <div style="flex-shrink:0;margin-left:auto">
                                                    <?php if ($audio_url): ?>
                                                    <audio controls preload="none" style="height:28px;max-width:180px"
                                                        aria-label="Ouvir <?php echo htmlspecialchars($t['title_track']); ?>">
                                                        <source src="<?php echo $audio_url; ?>" type="audio/mpeg">
                                                        O teu browser não suporta áudio HTML5.
                                                    </audio>
                                                    <?php else: ?>
                                                    <span style="font-size:.72rem;opacity:.4;font-style:italic">
                                                        <i class="bi bi-slash-circle me-1"></i>Sem áudio
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="<?php echo $collapse_id; ?>" class="accordion-collapse collapse"
                                        aria-labelledby="<?php echo $heading_id; ?>"
                                        data-bs-parent="#tracksAdminAccordion">
                                        <div class="accordion-body">
                                            <div class="track-ac-detail-row"><span
                                                    class="track-ac-detail-label">Título</span><span
                                                    class="track-ac-detail-value"><?php echo htmlspecialchars($t['title_track']); ?></span>
                                            </div>
                                            <div class="track-ac-detail-row"><span class="track-ac-detail-label">Artista
                                                    Principal</span><span
                                                    class="track-ac-detail-value"><?php echo htmlspecialchars($t['name_author'] ?? '—'); ?></span>
                                            </div>
                                            <?php if (!empty($t['name_author_feat'])): ?>
                                            <div class="track-ac-detail-row"><span
                                                    class="track-ac-detail-label">Feat.</span><span
                                                    class="track-ac-detail-value"><?php echo htmlspecialchars($t['name_author_feat']); ?></span>
                                            </div>
                                            <?php endif; ?>
                                            <div class="track-ac-detail-row"><span
                                                    class="track-ac-detail-label">Compositor</span><span
                                                    class="track-ac-detail-value"><?php echo htmlspecialchars($t['name_composer'] ?? '—'); ?></span>
                                            </div>
                                            <div class="track-ac-detail-row"><span
                                                    class="track-ac-detail-label">Produtor</span><span
                                                    class="track-ac-detail-value"><?php echo htmlspecialchars($t['name_producer'] ?? '—'); ?></span>
                                            </div>
                                            <div class="track-ac-detail-row"><span
                                                    class="track-ac-detail-label">ISRC</span><span
                                                    class="track-ac-detail-value"><?php echo htmlspecialchars($t['isrc'] ?? '—'); ?></span>
                                            </div>
                                            <div class="track-ac-detail-row"><span
                                                    class="track-ac-detail-label">Idioma</span><span
                                                    class="track-ac-detail-value"><?php echo htmlspecialchars($t['language'] ?? '—'); ?></span>
                                            </div>
                                            <div class="track-ac-detail-row"><span
                                                    class="track-ac-detail-label">Duração</span><span
                                                    class="track-ac-detail-value"><?php echo vw_duration((int)($t['duration_seconds'] ?? 0)); ?></span>
                                            </div>
                                            <div class="track-ac-detail-row"><span
                                                    class="track-ac-detail-label">Explícito</span><span
                                                    class="track-ac-detail-value"><?php echo $t['explicit'] === 'YES' ? 'Sim' : 'Não'; ?></span>
                                            </div>
                                            <?php if (!empty($t['audio_file'])): ?>
                                            <div class="track-ac-detail-row"><span
                                                    class="track-ac-detail-label">Ficheiro de áudio</span><span
                                                    class="track-ac-detail-value"><?php echo htmlspecialchars($t['audio_file']); ?></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- ══ SECÇÃO 4 — BOTÕES DE ACÇÃO ══ -->
                        <?php if ($can_approve && !in_array($album['status_album'], ['draft', 'deleting', 'approved'])): ?>
                        <div class="action-section mb-4" id="actions">
                            <h6 style="font-weight:700;margin-bottom:16px">
                                <i class="bi bi-gear me-2"></i>Acções de Revisão
                            </h6>

                            <div class="d-flex gap-2 flex-wrap">
                                <?php if ($album['status_album'] === 'pending'): ?>
                                <!-- Colocar em revisão -->
                                <button class="btn btn-process" id="btn-set-processing">
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    Colocar em Revisão
                                </button>
                                <?php endif; ?>

                                <?php if ($album['status_album'] === 'under_review'): ?>
                                <!-- Aprovar -->
                                <button class="btn btn-approve" data-bs-toggle="modal" data-bs-target="#modalApprove">
                                    <i class="bi bi-check-lg me-1"></i>
                                    Aprovar Álbum
                                </button>
                                <!-- Rejeitar -->
                                <button class="btn btn-reject" data-bs-toggle="modal" data-bs-target="#modalReject">
                                    <i class="bi bi-x-lg me-1"></i>
                                    Rejeitar
                                </button>
                                <?php endif; ?>

                                <?php if ($album['status_album'] === 'rejected'): ?>
                                <!-- Reabrir -->
                                <button class="btn btn-reopen" id="btn-reopen">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>
                                    Reabrir Pedido
                                </button>
                                <?php endif; ?>
                            </div>

                            <?php if ($album['rejection_reason']): ?>
                            <div class="alert alert-danger mt-3 mb-0" style="border-radius:10px;font-size:.82rem">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Motivo de rejeição:</strong>
                                <?php echo htmlspecialchars($album['rejection_reason']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($review_request): ?>
                        <!-- ══ SECÇÃO — PEDIDO DE REVISÃO DO UTILIZADOR ══ -->
                        <div class="action-section mb-4" id="review-request-section">
                            <h6 style="font-weight:700;margin-bottom:16px">
                                <i class="bi bi-chat-left-text me-2" style="color:#FF0089"></i>
                                Justificação do Utilizador
                            </h6>

                            <!-- Info do pedido -->
                            <div class="rounded-3 p-3 mb-3"
                                style="background:rgba(255,0,137,.06);border:1px solid rgba(255,0,137,.15)">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-warning text-dark" style="font-size:.72rem">Pendente</span>
                                    <span style="font-size:.78rem;color:#666">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        Solicitado em:
                                        <?php echo date('d/m/Y \à\s H:i', strtotime($review_request['creat_request'])); ?>
                                    </span>
                                </div>

                                <!-- Justificativo do utilizador -->
                                <div class="p-3 rounded-3"
                                    style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1)">
                                    <div style="font-size:.72rem;font-weight:700;margin-bottom:6px;opacity:.6">
                                        <i class="bi bi-person-circle me-1"></i>
                                        Justificativo do utilizador:
                                    </div>
                                    <p style="font-size:.85rem;line-height:1.7;white-space:pre-wrap;margin-bottom:0">
                                        <?php echo htmlspecialchars($review_request['reason_request']); ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Campo para o administrador responder (opcional, via modal existente ou novo) -->
                            <div class="alert alert-info small d-flex gap-2 mb-0" style="font-size:.8rem">
                                <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
                                <div>
                                    Este pedido precisa da tua atenção. Ao <strong>aprovar</strong> ou
                                    <strong>rejeitar</strong> o álbum, podes adicionar uma resposta ao utilizador
                                    que será registada neste pedido.
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($can_approve && $album['status_album'] === 'approved'): ?>
                        <!-- Editar UPC/Smartlink num álbum aprovado -->
                        <div class="action-section mb-4">
                            <h6 style="font-weight:700;margin-bottom:12px">
                                <i class="bi bi-pencil me-2"></i>Actualizar UPC / Smartlink
                            </h6>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                data-bs-target="#modalUpdateUpc">
                                <i class="bi bi-pencil me-1"></i> Editar UPC e Smartlink
                            </button>
                        </div>
                        <?php endif; ?>

                        <!-- ══ NOVO: SECÇÃO EDIT / DELETE ══ -->
                        <?php if ($can_approve && !in_array($album['status_album'], ['deleting'])): ?>
                        <div class="action-section mb-4" id="edit-actions">
                            <h6 style="font-weight:700;margin-bottom:16px">
                                <i class="bi bi-pencil-square me-2"></i>Editar / Eliminar
                            </h6>
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#modalEditAlbum">
                                    <i class="bi bi-pencil me-1"></i>Editar Álbum
                                </button>
                                <button class="btn btn-outline-danger btn-sm" id="btn-delete-album">
                                    <i class="bi bi-trash me-1"></i>Eliminar Álbum
                                </button>
                            </div>
                            <div class="mt-2" style="font-size:.78rem;color:#666">
                                <i class="bi bi-info-circle me-1"></i>
                                Eliminação é irreversível após 7 dias (soft-delete).
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($can_approve && $album['status_album'] === 'deleting'): ?>
                        <div class="action-section mb-4" id="undelete-actions">
                            <h6 style="font-weight:700;margin-bottom:12px">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Álbum a Eliminar
                            </h6>
                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <?php if (strtotime($album['delete_expires_at']) > time()): ?>
                                <button class="btn btn-success btn-sm" id="btn-undelete-album">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Recuperar
                                </button>
                                <?php endif; ?>
                                <?php if (strtotime($album['delete_expires_at']) <= time()): ?>
                                <button class="btn btn-danger btn-sm" id="btn-permanent-delete">
                                    <i class="bi bi-trash-fill me-1"></i>Eliminar Permanentemente
                                </button>
                                <?php endif; ?>
                                <div style="font-size:.78rem;color:#666">
                                    <span class="text-danger">
                                        Expira: <?php echo date('d/m/Y H:i', strtotime($album['delete_expires_at'])); ?>
                                    </span>
                                    <?php if (strtotime($album['delete_expires_at']) <= time()): ?>
                                    <br><small>O prazo expirou — podes eliminar definitivamente.</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div><!-- /col-lg-8 -->

                    <!-- Coluna lateral -->
                    <div class="col-lg-4">

                        <!-- ══ SECÇÃO 3 — UTILIZADOR E PLANO ══ -->
                        <div class="card mb-4" style="border-radius:14px;overflow:hidden">
                            <div class="px-3 py-2" style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                                <span style="font-size:.88rem;font-weight:700">
                                    <i class="bi bi-person-circle me-2"></i>Utilizador
                                </span>
                            </div>
                            <div class="px-3 py-2">
                                <div class="d-flex align-items-center gap-3 mb-3 mt-1">
                                    <?php echo vw_avatar($user_name, $album['photo_user'], 'assets/comprovantes/uploads/users', 44); ?>
                                    <div>
                                        <div style="font-weight:700"><?php echo htmlspecialchars($user_name); ?></div>
                                        <div style="font-size:.75rem;opacity:.6">
                                            <?php echo htmlspecialchars($album['email_user']); ?></div>
                                        <a href="<?php echo $base_url; ?>/users/view?id=<?php echo (int)$album['id_users']; ?>"
                                            style="font-size:.72rem;color:#FF0089">
                                            Ver perfil →
                                        </a>
                                    </div>
                                </div>

                                <div class="info-row">
                                    <span class="info-lbl">Estado da Conta</span>
                                    <span class="info-val"><?php echo ucfirst($album['status_user'] ?? '—'); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-lbl">Plano Activo</span>
                                    <span
                                        class="info-val"><?php echo htmlspecialchars($album['name_plan'] ?? '—'); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-lbl">Royalty</span>
                                    <span
                                        class="info-val"><?php echo $album['royalty_rate'] ? (float)$album['royalty_rate'] . '%' : '—'; ?></span>
                                </div>
                                <?php if ($album['started_at']): ?>
                                <div class="info-row">
                                    <span class="info-lbl">Plano desde</span>
                                    <span
                                        class="info-val"><?php echo date('d/m/Y', strtotime($album['started_at'])); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($album['releases_limit']): ?>
                                <div class="info-row">
                                    <span class="info-lbl">Releases usados</span>
                                    <span class="info-val">
                                        <strong
                                            style="color:#FF0089"><?php echo (int)$album['releases_used']; ?></strong>
                                        / <?php echo (int)$album['releases_limit']; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Detalhes técnicos do álbum -->
                        <div class="card" style="border-radius:14px;overflow:hidden">
                            <div class="px-3 py-2" style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                                <span style="font-size:.88rem;font-weight:700">
                                    <i class="bi bi-info-circle me-2"></i>Informações Técnicas
                                </span>
                            </div>
                            <div class="px-3 py-2">
                                <div class="info-row">
                                    <span class="info-lbl">Tipo</span>
                                    <span class="info-val"><?php echo ucfirst($album['type_album']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-lbl">Género</span>
                                    <span class="info-val">
                                        <?php echo htmlspecialchars($album['genre_main'] ?? '—'); ?>
                                        <?php if ($album['genre_secondary']): ?>
                                        <span style="opacity:.6;font-size:.75rem"> /
                                            <?php echo htmlspecialchars($album['genre_secondary']); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-lbl">Idioma</span>
                                    <span
                                        class="info-val"><?php echo htmlspecialchars($album['language'] ?? '—'); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-lbl">Território</span>
                                    <span
                                        class="info-val"><?php echo htmlspecialchars($album['territory'] ?? '—'); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-lbl">UPC</span>
                                    <span class="info-val" style="font-family:monospace;font-size:.78rem">
                                        <?php echo $album['upc'] ? htmlspecialchars($album['upc']) : '—'; ?>
                                    </span>
                                </div>
                                <?php if ($album['copyright_c']): ?>
                                <div class="info-row">
                                    <span class="info-lbl">Copyright ©</span>
                                    <span class="info-val"
                                        style="font-size:.76rem"><?php echo htmlspecialchars($album['copyright_c']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($album['copyright_p']): ?>
                                <div class="info-row">
                                    <span class="info-lbl">Fonograma ℗</span>
                                    <span class="info-val"
                                        style="font-size:.76rem"><?php echo htmlspecialchars($album['copyright_p']); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="info-row">
                                    <span class="info-lbl">Total de Faixas</span>
                                    <span class="info-val"
                                        style="font-weight:700;color:#FF0089"><?php echo count($tracks); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-lbl">Data de Envio</span>
                                    <span
                                        class="info-val"><?php echo date('d/m/Y H:i', strtotime($album['creat_album'])); ?></span>
                                </div>
                            </div>
                        </div>

                    </div><!-- /col-lg-4 -->

                </div><!-- /row -->

            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════
     MODAL — Aprovar
════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalApprove" tabindex="-1">
        <div class="modal-dialog modal-md">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:#22c55e">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="bi bi-check-circle me-2"></i>Aprovar Álbum
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 modal-input-section">
                    <p class="text-muted small mb-4">
                        Ao aprovar, o álbum será marcado como aprovado, o utilizador será notificado
                        e o contador de releases do plano será actualizado.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">UPC (EAN-13, 13 dígitos) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="approve_upc" maxlength="13" pattern="[0-9]{13}"
                            placeholder="ex: 0123456789012"
                            value="<?php echo htmlspecialchars($album['upc'] ?? ''); ?>">
                        <div class="form-text">Obrigatório. 13 dígitos numéricos (EAN-13).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Smartlink (opcional)</label>
                        <input type="url" class="form-control" id="approve_smartlink" placeholder="https://..."
                            value="<?php echo htmlspecialchars($album['smartlink'] ?? ''); ?>">
                        <div class="form-text">Link agregador gerado após distribuição.</div>
                    </div>
                    <div class="alert alert-danger d-none" id="approve_error" style="font-size:.78rem"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm text-white" style="background:#22c55e"
                        id="btn-confirm-approve">
                        <span class="normal-lbl"><i class="bi bi-check-lg me-1"></i>Confirmar Aprovação</span>
                        <span class="loading-lbl d-none"><span class="spinner-border spinner-border-sm me-1"></span>A
                            processar…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════
     MODAL — Rejeitar
════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalReject" tabindex="-1">
        <div class="modal-dialog modal-md">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:#ef4444">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="bi bi-x-circle me-2"></i>Rejeitar Álbum
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 modal-input-section">
                    <p class="text-muted small mb-4">
                        O utilizador receberá uma notificação com o motivo. Pode corrigir e reenviar.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Motivo da Rejeição <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_reason" rows="4"
                            placeholder="Descreve o motivo da rejeição (mínimo 10 caracteres)..."></textarea>
                        <div class="form-text">Este texto será enviado ao utilizador por email e notificação.</div>
                    </div>
                    <div class="alert alert-danger d-none" id="reject_error" style="font-size:.78rem"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-danger" id="btn-confirm-reject">
                        <span class="normal-lbl"><i class="bi bi-x-lg me-1"></i>Confirmar Rejeição</span>
                        <span class="loading-lbl d-none"><span class="spinner-border spinner-border-sm me-1"></span>A
                            processar…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════
     MODAL — Actualizar UPC/Smartlink (álbum aprovado)
════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalUpdateUpc" tabindex="-1">
        <div class="modal-dialog modal-md">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:#FF0089">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="bi bi-pencil me-2"></i>Actualizar UPC e Smartlink
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 modal-input-section">
                    <div class="mb-3">
                        <label class="form-label">UPC (EAN-13, 13 dígitos)</label>
                        <input type="text" class="form-control" id="update_upc" maxlength="13" pattern="[0-9]{13}"
                            value="<?php echo htmlspecialchars($album['upc'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Smartlink</label>
                        <input type="url" class="form-control" id="update_smartlink"
                            value="<?php echo htmlspecialchars($album['smartlink'] ?? ''); ?>">
                    </div>
                    <div class="alert alert-danger d-none" id="update_error" style="font-size:.78rem"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm text-white" style="background:#FF0089"
                        id="btn-confirm-update">
                        <span class="normal-lbl">Guardar</span>
                        <span class="loading-lbl d-none"><span class="spinner-border spinner-border-sm me-1"></span>A
                            guardar…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════ NOVO: MODAL EDITAR ÁLBUM ═══════ -->
    <div class="modal fade" id="modalEditAlbum" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i>Editar Álbum
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="form-edit-album">
                        <input type="hidden" name="id_album" value="<?php echo (int)$album['id_album']; ?>">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Título *</label>
                                <input type="text" class="form-control" name="title_album" required maxlength="150"
                                    value="<?php echo htmlspecialchars($album['title_album']); ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Género Principal *</label>
                                <input type="text" class="form-control" name="genre_main" required maxlength="50"
                                    value="<?php echo htmlspecialchars($album['genre_main']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" name="type_album">
                                    <?php foreach (['single', 'ep', 'album', 'mixtape'] as $t): ?>
                                    <option value="<?php echo $t; ?>"
                                        <?php echo $album['type_album'] === $t ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($t); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Estado <span class="text-danger">*</span></label>
                                <select class="form-select" name="status_album" required>
                                    <?php
                                    $statuses = [
                                        'draft'        => 'Rascunho',
                                        'pending'      => 'Pendente de Revisão',
                                        'under_review' => 'Em Revisão',
                                        'approved'     => 'Aprovado',
                                        'rejected'     => 'Rejeitado',
                                    ];
                                    foreach ($statuses as $val => $lbl): ?>
                                    <option value="<?php echo $val; ?>"
                                        <?php echo $album['status_album'] === $val ? 'selected' : ''; ?>>
                                        <?php echo $lbl; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text text-warning">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Alterar o estado notifica o utilizador.
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Data Lançamento</label>
                                <input type="date" class="form-control" name="release_date"
                                    value="<?php echo $album['release_date']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Território</label>
                                <input type="text" class="form-control" name="territory" maxlength="100"
                                    value="<?php echo htmlspecialchars($album['territory']); ?>"
                                    placeholder="Worldwide">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Gravadora</label>
                                <input type="text" class="form-control" name="label_name" maxlength="100"
                                    value="<?php echo htmlspecialchars($album['label_name']); ?>">
                            </div>
                        </div>
                        <div class="alert alert-info mt-3" style="font-size:.82rem">
                            <i class="bi bi-info-circle me-1"></i> Campos obrigatórios: Título, Género. Outros campos
                            não editáveis (capa/faixas geridas no upload).
                        </div>
                        <div class="alert alert-warning d-none" id="edit-error" style="font-size:.78rem"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-save-edit">
                        <span class="normal">Guardar Alterações</span>
                        <span class="loading d-none"><span
                                class="spinner-border spinner-border-sm me-1"></span>Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════ MODAL CONFIRM DELETE ═══════ -->
    <div class="modal fade" id="modalConfirmDelete" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-trash3 me-2"></i>Confirmar Eliminação
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="text-center mb-3">
                        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:3rem"></i>
                        <h6 class="mt-2 mb-0 fw-bold"><?php echo htmlspecialchars($album['title_album']); ?></h6>
                        <p class="text-muted small mt-1 mb-0">Soft-delete: marcado para eliminação em 7 dias.</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.82rem">
                            <i class="bi bi-shield-lock me-1 text-danger"></i>
                            Confirma a tua senha para continuar <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="password" class="form-control" id="delete_password"
                                placeholder="A tua senha de admin" autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button"
                                onclick="const i=document.getElementById('delete_password');i.type=i.type==='password'?'text':'password'">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text text-danger">
                            Por segurança, a senha é obrigatória para eliminar.
                        </div>
                    </div>
                    <div class="alert alert-danger d-none py-2" id="delete-error" style="font-size:.78rem"></div>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger btn-sm" id="btn-confirm-delete">
                        <span class="normal-lbl"><i class="bi bi-trash me-1"></i>Eliminar</span>
                        <span class="loading-lbl d-none"><span class="spinner-border spinner-border-sm me-1"></span>A
                            eliminar...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="" />
            <div class="loader-progress"></div>
        </div>
    </div>

    <input type="hidden" id="album-id-holder" value="<?php echo (int)$album['id_album']; ?>">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
    window.__BASE_URL__ = '<?php echo APP_URL; ?>';
    window.__ADMIN_PATH__ = '<?php echo ADMIN_PATH; ?>';

    // Helper seguro para chamar SweetAlert2 (aguarda carregamento)
    async function swalFire(options) {
        if (typeof Swal === 'undefined') {
            for (let i = 0; i < 20; i++) {
                await new Promise(r => setTimeout(r, 100));
                if (typeof Swal !== 'undefined') break;
            }
            if (typeof Swal === 'undefined') {
                alert('Biblioteca de diálogo não carregou. Recarregue a página.');
                throw new Error('SweetAlert2 missing');
            }
        }
        return Swal.fire(options);
    }

    document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const PROCESS = window.__BASE_URL__ + '/' + window.__ADMIN_PATH__ + '/releases/view-process';
        const RELEASES_URL = window.__BASE_URL__ + '/' + window.__ADMIN_PATH__ + '/releases/';

        const albumIdHolder = document.getElementById('album-id-holder');
        const ALBUM_ID = albumIdHolder ? parseInt(albumIdHolder.value, 10) :
            <?php echo (int)$album['id_album']; ?>;
        if (!ALBUM_ID) {
            console.error('ID do álbum não definido.');
        }

        async function postAction(payload) {
            const fd = new FormData();
            Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
            fd.append('csrf_token', CSRF);
            const r = await fetch(PROCESS, {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            });
            return r.json();
        }

        function setLoading(btn, state) {
            const normal = btn.querySelector('.normal-lbl');
            const loading = btn.querySelector('.loading-lbl');
            if (normal) normal.classList.toggle('d-none', state);
            if (loading) loading.classList.toggle('d-none', !state);
            btn.disabled = state;
        }

        function showError(elId, msg) {
            const el = document.getElementById(elId);
            if (el) {
                el.textContent = msg;
                el.classList.remove('d-none');
            }
        }

        function hideError(elId) {
            const el = document.getElementById(elId);
            if (el) el.classList.add('d-none');
        }

        async function handleAction(action, extra = {}) {
            try {
                const data = await postAction({
                    action,
                    id_album: ALBUM_ID,
                    ...extra
                });
                if (data.ok) {
                    await swalFire({
                        icon: 'success',
                        title: 'Feito!',
                        text: data.message || 'Operação concluída com sucesso.',
                        confirmButtonColor: '#FF0089'
                    });
                    if (action === 'permanent_delete_album') {
                        window.location.href = RELEASES_URL;
                        return {};
                    }
                    location.reload();
                } else {
                    return {
                        error: data.message || 'Erro desconhecido.'
                    };
                }
            } catch {
                return {
                    error: 'Erro de ligação. Verifica a tua internet.'
                };
            }
            return {};
        }

        // ── Colocar em revisão ────────────────────────────
        const btnProcess = document.getElementById('btn-set-processing');
        if (btnProcess) {
            btnProcess.addEventListener('click', async function() {
                const {
                    isConfirmed,
                    value: password
                } = await swalFire({
                    title: 'Colocar em revisão?',
                    text: 'O utilizador será notificado que o álbum está a ser analisado.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3b82f6',
                    confirmButtonText: 'Sim, colocar em revisão',
                    cancelButtonText: 'Cancelar'
                });
                if (!isConfirmed) return;
                swalFire({
                    title: 'A processar...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                const res = await handleAction('set_processing');
                if (res.error) {
                    swalFire({
                        icon: 'error',
                        title: 'Erro',
                        text: res.error,
                        confirmButtonColor: '#FF0089'
                    });
                }
            });
        }

        // ── Aprovar ───────────────────────────────────────
        const btnApprove = document.getElementById('btn-confirm-approve');
        if (btnApprove) {
            btnApprove.addEventListener('click', async function() {
                hideError('approve_error');
                const upc = document.getElementById('approve_upc').value.trim();
                const smartlink = document.getElementById('approve_smartlink').value.trim();

                if (!/^\d{13}$/.test(upc)) {
                    showError('approve_error',
                        'O UPC deve ter exactamente 13 dígitos numéricos (EAN-13).');
                    return;
                }
                if (smartlink && !/^https?:\/\/.+/.test(smartlink)) {
                    showError('approve_error',
                        'O smartlink deve ser uma URL válida (https://...).');
                    return;
                }

                setLoading(this, true);
                const res = await handleAction('approve', {
                    upc,
                    smartlink
                });
                if (res.error) showError('approve_error', res.error);
                setLoading(this, false);
            });
        }

        // ── Rejeitar ──────────────────────────────────────
        const btnReject = document.getElementById('btn-confirm-reject');
        if (btnReject) {
            btnReject.addEventListener('click', async function() {
                hideError('reject_error');
                const reason = document.getElementById('reject_reason').value.trim();
                if (reason.length < 10) {
                    showError('reject_error', 'O motivo deve ter pelo menos 10 caracteres.');
                    return;
                }
                setLoading(this, true);
                const res = await handleAction('reject', {
                    reject_reason: reason
                });
                if (res.error) showError('reject_error', res.error);
                setLoading(this, false);
            });
        }

        // ── Reabrir ───────────────────────────────────────
        const btnReopen = document.getElementById('btn-reopen');
        if (btnReopen) {
            btnReopen.addEventListener('click', async function() {
                const {
                    isConfirmed,
                    value: password
                } = await swalFire({
                    title: 'Reabrir pedido?',
                    text: 'O álbum voltará ao estado "Pendente" para ser corrigido e reenviado.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f97316',
                    confirmButtonText: 'Sim, reabrir',
                    cancelButtonText: 'Cancelar'
                });
                if (!isConfirmed) return;
                swalFire({
                    title: 'A processar...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                const res = await handleAction('reopen');
                if (res.error) {
                    swalFire({
                        icon: 'error',
                        title: 'Erro',
                        text: res.error,
                        confirmButtonColor: '#FF0089'
                    });
                }
            });
        }

        // ── Editar Álbum ─────────────────────────────────
        const btnSaveEdit = document.getElementById('btn-save-edit');
        if (btnSaveEdit) {
            btnSaveEdit.addEventListener('click', async function() {
                hideError('edit-error');
                const formData = new FormData(document.getElementById('form-edit-album'));
                const btn = this;
                const normal = btn.querySelector('.normal');
                const loading = btn.querySelector('.loading');
                normal.classList.add('d-none');
                loading.classList.remove('d-none');
                btn.disabled = true;

                try {
                    const data = await postAction({
                        action: 'edit_album',
                        ...Object.fromEntries(formData)
                    });
                    if (data.ok) {
                        await swalFire({
                            icon: 'success',
                            title: 'Actualizado!',
                            text: data.message,
                            confirmButtonColor: '#FF0089'
                        });
                        location.reload();
                    } else {
                        showError('edit-error', data.message);
                    }
                } catch (e) {
                    showError('edit-error', 'Erro de ligação.');
                } finally {
                    normal.classList.remove('d-none');
                    loading.classList.add('d-none');
                    btn.disabled = false;
                }
            });
        }

        // ── ELIMINAR ──────────────────────────────────
        const btnDelete = document.getElementById('btn-delete-album');
        if (btnDelete) {
            btnDelete.addEventListener('click', () => {
                const pwdInput = document.getElementById('delete_password');
                if (pwdInput) pwdInput.value = '';
                hideError('delete-error');
                new bootstrap.Modal(document.getElementById('modalConfirmDelete')).show();
            });
        }

        const btnConfirmDelete = document.getElementById('btn-confirm-delete');
        if (btnConfirmDelete) {
            btnConfirmDelete.addEventListener('click', async function() {
                hideError('delete-error');

                const idHolder = document.getElementById('album-id-holder');
                const albumId = idHolder ? parseInt(idHolder.value, 10) : 0;
                if (!albumId) {
                    showError('delete-error', 'ID do álbum não encontrado. Recarregue a página.');
                    return;
                }

                const password = document.getElementById('delete_password').value.trim();
                if (!password) {
                    showError('delete-error', 'A senha é obrigatória para confirmar a eliminação.');
                    return;
                }

                setLoading(this, true);
                try {
                    const data = await postAction({
                        action: 'delete_album',
                        id_album: albumId,
                        admin_password: password
                    });
                    if (data.ok) {
                        if (typeof Swal !== 'undefined') {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Eliminado!',
                                text: data.message,
                                confirmButtonColor: '#FF0089'
                            });
                        } else {
                            alert(data.message);
                        }
                        location.href = '<?php echo $base_url; ?>/releases';
                    } else {
                        showError('delete-error', data.message);
                    }
                } catch (e) {
                    showError('delete-error', 'Erro de ligação.');
                } finally {
                    setLoading(this, false);
                }
            });
        }

        // ── Recuperar ───────────────────────────────────
        const btnUndelete = document.getElementById('btn-undelete-album');
        if (btnUndelete) {
            btnUndelete.addEventListener('click', async function() {
                const {
                    isConfirmed
                } = await swalFire({
                    title: 'Recuperar álbum?',
                    text: 'Voltará ao estado de rascunho.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#22c55e'
                });
                if (!isConfirmed) return;
                swalFire({
                    title: 'Recuperando...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                const res = await handleAction('undelete_album');
                if (res.error) {
                    swalFire({
                        icon: 'error',
                        title: 'Erro',
                        text: res.error
                    });
                }
            });
        }

        // ── ELIMINAR PERMANENTEMENTE ──────────────────
        const btnPermDelete = document.getElementById('btn-permanent-delete');
        if (btnPermDelete) {
            btnPermDelete.addEventListener('click', async function() {
                const {
                    isConfirmed,
                    value: password
                } = await swalFire({
                    title: 'Eliminar permanentemente?',
                    html: `<p class="mb-2">Esta acção é <strong>irreversível</strong> e apagará todos os ficheiros e dados associados.</p>
                           <p class="text-danger small mb-0">Confirma a tua senha para continuar.</p>
                           <input type="password" id="swal-pwd" class="swal2-input" placeholder="Senha do admin">`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Sim, eliminar permanentemente',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => {
                        const pwd = document.getElementById('swal-pwd').value.trim();
                        if (!pwd) {
                            Swal.showValidationMessage('A senha é obrigatória.');
                            return false;
                        }
                        return pwd;
                    }
                });
                if (!isConfirmed) return;

                swalFire({
                    title: 'A eliminar...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const res = await handleAction('permanent_delete_album', {
                    admin_password: password
                });
                if (res.error) {
                    swalFire({
                        icon: 'error',
                        title: 'Erro',
                        text: res.error,
                        confirmButtonColor: '#FF0089'
                    });
                }
            });
        }

        // ── Actualizar UPC/Smartlink ────────────────────
        const btnUpdate = document.getElementById('btn-confirm-update');
        if (btnUpdate) {
            btnUpdate.addEventListener('click', async function() {
                hideError('update_error');
                const upc = document.getElementById('update_upc').value.trim();
                const smartlink = document.getElementById('update_smartlink').value.trim();
                if (upc && !/^\d{13}$/.test(upc)) {
                    showError('update_error', 'O UPC deve ter exactamente 13 dígitos numéricos.');
                    return;
                }
                setLoading(this, true);
                const res = await handleAction('update_upc', {
                    upc,
                    smartlink
                });
                if (res.error) showError('update_error', res.error);
                setLoading(this, false);
            });
        }

        // Scroll para secções
        ['actions', 'edit-actions', 'undelete-actions'].forEach(id => {
            if (window.location.hash === `#${id}`) {
                const el = document.getElementById(id);
                if (el) el.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    </script>
</body>

</html>
