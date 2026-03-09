<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Artistas (Colaboradores)
// Arquivo: dashboard/collab/artists.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();

// ── Verificar sessão de colaborador ──────────
if (empty($_SESSION['collab_id']) || empty($_SESSION['collab_id_users'])) {
    header('Location: ' . rtrim(APP_URL, '/') . '/dashboard/account/collab-login');
    exit;
}
if (!empty($_SESSION['collab_must_change'])) {
    header('Location: ' . rtrim(APP_URL, '/') . '/dashboard/account/collab-login');
    exit;
}

$db        = getDB();
$id_collab = (int)$_SESSION['collab_id'];
$id_users  = (int)$_SESSION['collab_id_users'];
$role      = $_SESSION['collab_role'] ?? 'support';

// ── Dados do colaborador ──────────────────────
$cs = $db->prepare("SELECT * FROM _collaborators WHERE id_collab = ? AND id_users = ? AND status_collab = 'active' LIMIT 1");
$cs->execute([$id_collab, $id_users]);
$collab = $cs->fetch();
if (!$collab) {
    session_destroy();
    header('Location: ' . rtrim(APP_URL, '/') . '/dashboard/account/collab-login?error=access');
    exit;
}

$db->prepare("UPDATE _collaborators SET last_seen_at = NOW() WHERE id_collab = ?")
    ->execute([$id_collab]);

// ── Dados do proprietário ─────────────────────
$owner = getUserById($id_users);
if (!$owner) {
    session_destroy();
    header('Location: ' . rtrim(APP_URL, '/') . '/dashboard/account/collab-login');
    exit;
}

$owner_name        = htmlspecialchars(trim($owner['first_name'] . ' ' . ($owner['second_name'] ?? '')));
$owner_artist_name = htmlspecialchars($owner['name_artist_band'] ?? $owner['first_name']);

$plan = null;
if ($owner['plan_selected']) {
    $ps = $db->prepare("SELECT * FROM _plans WHERE id_plan = ?");
    $ps->execute([$owner['plan_selected']]);
    $plan = $ps->fetch();
}
$plan_name = $plan ? htmlspecialchars($plan['name_plan']) : 'Sem plano';

// ── Permissões ────────────────────────────────
$can_view_releases = in_array($role, ['admin', 'editor', 'support']);
$can_edit_releases = in_array($role, ['admin', 'editor']);
$can_view_artists  = in_array($role, ['admin', 'editor']);
$can_edit_artists  = in_array($role, ['admin', 'editor']);
$can_view_finances = in_array($role, ['admin', 'analyst']);
$can_view_stats    = in_array($role, ['admin', 'analyst', 'editor']);

if (!$can_view_artists) {
    header('Location: ' . rtrim(APP_URL, '/') . '/dashboard/collab/overview?error=noaccess');
    exit;
}

// ── Artistas ──────────────────────────────────
$search = trim($_GET['q'] ?? '');
$filter = $_GET['status'] ?? 'all';

$where  = ["a.id_users = ?"];
$params = [$id_users];

if ($filter !== 'all') {
    $where[] = "a.status_artist = ?";
    $params[] = $filter;
}
if ($search !== '') {
    $where[] = "(a.stage_name LIKE ? OR a.real_name LIKE ? OR a.genre_main LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT a.*,
        COUNT(DISTINCT al.id_album)                                           AS total_albums,
        COUNT(DISTINCT CASE WHEN al.status_album='approved'    THEN al.id_album END) AS albums_approved,
        COUNT(DISTINCT CASE WHEN al.status_album='pending'     THEN al.id_album END) AS albums_pending,
        COUNT(DISTINCT CASE WHEN al.status_album='under_review'THEN al.id_album END) AS albums_review,
        COUNT(DISTINCT CASE WHEN al.status_album='rejected'    THEN al.id_album END) AS albums_rejected,
        COUNT(DISTINCT CASE WHEN al.status_album='draft'       THEN al.id_album END) AS albums_draft
    FROM _artist a
    LEFT JOIN _album al ON al.id_artist = a.id_artist AND al.id_users = a.id_users
    WHERE $where_sql
    GROUP BY a.id_artist
    ORDER BY a.creat_artist DESC
");
$stmt->execute($params);
$artists = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Últimos 3 álbuns por artista (para o painel de detalhe)
$albums_by_artist = [];
if (!empty($artists)) {
    $ids = implode(',', array_map('intval', array_column($artists, 'id_artist')));
    $as2 = $db->query("
        SELECT id_album, id_artist, title_album, type_album, status_album, img_cover, release_date
        FROM _album WHERE id_artist IN ($ids) AND id_users = $id_users
        ORDER BY creat_album DESC
    ");
    foreach ($as2->fetchAll(PDO::FETCH_ASSOC) as $alb) {
        if (count($albums_by_artist[$alb['id_artist']] ?? []) < 3) {
            $albums_by_artist[$alb['id_artist']][] = $alb;
        }
    }
}

// Stats totais
$tot_q = $db->prepare("SELECT COUNT(*) as total,
    SUM(CASE WHEN status_artist='active'   THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status_artist='inactive' THEN 1 ELSE 0 END) as inactive,
    SUM(CASE WHEN status_artist='blocked'  THEN 1 ELSE 0 END) as blocked,
    SUM(CASE WHEN status_artist='processing'THEN 1 ELSE 0 END) as processing
    FROM _artist WHERE id_users = ?");
$tot_q->execute([$id_users]);
$stats = $tot_q->fetch();

// ── Helpers ───────────────────────────────────
$role_meta = [
    'admin'   => ['label' => 'Administrador', 'color' => '#dc3545', 'bg' => 'rgba(220,53,69,.1)',   'icon' => 'bi-shield-fill'],
    'editor'  => ['label' => 'Editor',        'color' => '#FF0089', 'bg' => 'rgba(255,0,137,.1)',  'icon' => 'bi-pencil-fill'],
    'analyst' => ['label' => 'Analista',      'color' => '#0d6efd', 'bg' => 'rgba(13,110,253,.1)', 'icon' => 'bi-bar-chart-fill'],
    'support' => ['label' => 'Suporte',       'color' => '#198754', 'bg' => 'rgba(25,135,84,.1)',  'icon' => 'bi-headset'],
];
$rm         = $role_meta[$role] ?? $role_meta['support'];
$role_label = $rm['label'];

$artist_status_meta = [
    'active'     => ['label' => 'Activo',      'color' => '#198754', 'bg' => 'rgba(25,135,84,.1)'],
    'inactive'   => ['label' => 'Inactivo',    'color' => '#6c757d', 'bg' => 'rgba(108,117,125,.1)'],
    'blocked'    => ['label' => 'Bloqueado',   'color' => '#dc3545', 'bg' => 'rgba(220,53,69,.1)'],
    'processing' => ['label' => 'Em processo', 'color' => '#856404', 'bg' => 'rgba(255,193,7,.12)'],
];
$album_status_meta = [
    'approved'    => ['label' => 'Aprovado',   'color' => '#198754', 'bg' => 'rgba(25,135,84,.1)'],
    'pending'     => ['label' => 'Pendente',   'color' => '#856404', 'bg' => 'rgba(255,193,7,.12)'],
    'under_review' => ['label' => 'Em revisão', 'color' => '#0d6efd', 'bg' => 'rgba(13,110,253,.1)'],
    'rejected'    => ['label' => 'Recusado',   'color' => '#dc3545', 'bg' => 'rgba(220,53,69,.1)'],
    'draft'       => ['label' => 'Rascunho',   'color' => '#6c757d', 'bg' => 'rgba(108,117,125,.1)'],
];

$logout_url  = rtrim(APP_URL, '/') . '/dashboard/collab/logout';
$base_url    = rtrim(APP_URL, '/');
$cover_base  = $base_url . '/assets/comprovantes/uploads/covers/';
$photo_base  = $base_url . '/assets/comprovantes/uploads/artists/';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <title>Artistas — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <link rel="stylesheet" href="css/collab.css" />
    <style>
        /* Artist cards */
        .artist-card {
            background: var(--card);
            border-radius: 16px;
            border: 1.5px solid var(--border);
            overflow: hidden;
            cursor: pointer;
            transition: border-color .2s, box-shadow .2s, transform .15s;
        }

        .artist-card:hover {
            border-color: rgba(255, 0, 137, .25);
            box-shadow: 0 6px 24px rgba(255, 0, 137, .1);
            transform: translateY(-2px);
        }

        .artist-cover {
            width: 100%;
            height: 80px;
            object-fit: cover;
            background: linear-gradient(135deg, rgba(255, 0, 137, .08), rgba(255, 77, 77, .08));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            position: relative;
            /* SEM overflow:hidden — para a foto circular não ficar cortada */
        }

        .artist-cover img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 0;
        }

        .artist-photo-wrap {
            position: absolute;
            bottom: -22px;
            left: 16px;
            z-index: 2;
        }

        .artist-photo {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 3px solid var(--card);
            object-fit: cover;
            background: rgba(255, 0, 137, .1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            overflow: hidden;
        }

        .artist-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .artist-body {
            padding: 30px 14px 14px;
        }

        /* Detalhe offcanvas */
        .detail-panel {
            position: fixed;
            top: 0;
            right: -440px;
            width: 440px;
            max-width: 100vw;
            height: 100vh;
            background: var(--card);
            border-left: 1.5px solid var(--border);
            z-index: 1100;
            overflow-y: auto;
            transition: right .3s;
            box-shadow: -4px 0 32px rgba(0, 0, 0, .1);
        }

        .detail-panel.open {
            right: 0;
        }

        .detail-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .35);
            z-index: 1050;
        }

        .detail-overlay.show {
            display: block;
        }

        .detail-cover {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background: linear-gradient(135deg, #FF0089, #FF4D4D);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
            opacity: .3;
            position: relative;
            overflow: hidden;
        }

        .detail-cover img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 1;
        }

        .detail-photo {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            border: 4px solid var(--card);
            object-fit: cover;
            background: rgba(255, 0, 137, .1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            overflow: hidden;
            margin-top: -36px;
            margin-left: 20px;
            position: relative;
        }

        .detail-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Social links */
        .social-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 600;
            text-decoration: none;
            border: 1.5px solid var(--border);
            color: var(--text);
            transition: all .15s;
        }

        .social-btn:hover {
            border-color: var(--wasom);
            color: var(--wasom);
        }
    </style>
</head>

<body>

    <!-- ═══ NAVBAR ═══ -->
    <nav class="collab-nav">
        <button class="theme-btn d-md-none" id="btn-sidebar-toggle"><i class="bi bi-list"></i></button>
        <a class="nav-brand" href="<?php echo $base_url; ?>/dashboard/collab/overview">
            <?php echo APP_NAME; ?><span>For Colaboradores</span>
        </a>
        <div class="nav-spacer"></div>
        <div class="nav-chip d-none d-md-inline-flex"
            style="background:<?php echo $rm['bg']; ?>;color:<?php echo $rm['color']; ?>;border-color:<?php echo $rm['color']; ?>20">
            <i class="bi <?php echo $rm['icon']; ?>"></i><?php echo $role_label; ?>
        </div>
        <button class="theme-btn" id="themeToggle"><i class="bi bi-sun" id="themeIcon"></i></button>
        <div class="dropdown">
            <button class="nav-avatar dropdown-toggle" style="background:none;border:none;cursor:pointer"
                data-bs-toggle="dropdown">
                <?php if ($collab['photo_collab']): ?>
                    <img src="<?php echo htmlspecialchars($collab['photo_collab']); ?>" alt=""
                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                    <span style="display:none"> <i class="bi bi-person-circle"></i> </span>
                <?php else: ?><span> <i class="bi bi-person-circle"></i> </span><?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="font-size:.84rem;min-width:200px">
                <li class="px-3 py-2">
                    <div class="fw-bold">
                        <?php echo htmlspecialchars($collab['first_name'] . ' ' . ($collab['second_name'] ?? '')); ?>
                    </div>
                    <div class="text-muted" style="font-size:.72rem">
                        @<?php echo htmlspecialchars($collab['user_collab']); ?></div>
                    <div class="mt-1">
                        <span class="chip"
                            style="background:<?php echo $rm['bg']; ?>;color:<?php echo $rm['color']; ?>">
                            <i class="bi <?php echo $rm['icon']; ?>"></i><?php echo $role_label; ?>
                        </span>
                    </div>
                </li>
                <li>
                    <hr class="dropdown-divider" />
                </li>
                <li><a class="dropdown-item" href="<?php echo $base_url; ?>/dashboard/collab/overview"><i
                            class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                <li>
                    <hr class="dropdown-divider" />
                </li>
                <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                        data-bs-target="#logoutModal"><i class="bi bi-box-arrow-right me-2"></i>Terminar sessão</a></li>
            </ul>
        </div>
    </nav>

    <!-- Sidebar overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="collab-sidebar" id="collabSidebar">
        <div class="owner-card mb-3">
            <div
                style="font-size:.65rem;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">
                Conta</div>
            <div class="fw-bold" style="font-size:.95rem"><?php echo $owner_artist_name; ?></div>
            <div style="font-size:.72rem;color:rgba(255,255,255,.75);margin-top:2px"><?php echo $plan_name; ?></div>
        </div>

        <div class="sidebar-section">Menu</div>
        <a href="<?php echo $base_url; ?>/dashboard/collab/overview" class="sidebar-link">
            <i class="bi bi-speedometer2"></i>Dashboard
        </a>
        <?php if ($can_view_releases): ?>
            <a href="<?php echo $base_url; ?>/dashboard/collab/releases" class="sidebar-link">
                <i class="bi bi-disc"></i>Lançamentos
            </a>
        <?php endif; ?>
        <?php if ($can_view_artists): ?>
            <a href="<?php echo $base_url; ?>/dashboard/collab/artists" class="sidebar-link active">
                <i class="bi bi-people"></i>Artistas
            </a>
        <?php endif; ?>
        <?php if ($can_view_finances): ?>
            <div class="sidebar-section">Finanças</div>
            <a href="<?php echo $base_url; ?>/dashboard/collab/finances" class="sidebar-link">
                <i class="bi bi-currency-dollar"></i>Visão geral
            </a>
        <?php endif; ?>
        <?php if ($can_view_stats): ?>
            <div class="sidebar-section">Análise</div>
            <a href="<?php echo $base_url; ?>/dashboard/collab/statistics" class="sidebar-link">
                <i class="bi bi-bar-chart"></i>Estatísticas
            </a>
        <?php endif; ?>
        <div class="sidebar-section">Conta</div>
        <a href="#" class="sidebar-link" data-bs-toggle="modal" data-bs-target="#myProfileModal">
            <i class="bi bi-person-gear"></i>O meu perfil
        </a>
        <a href="#" class="sidebar-link text-danger" data-bs-toggle="modal" data-bs-target="#logoutModal">
            <i class="bi bi-box-arrow-right"></i>Terminar sessão
        </a>
    </aside>


    <!-- ═══ MAIN CONTENT ═══ -->
    <main class="main-content">

        <!-- Cabeçalho -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h4 fw-bold mb-1"><i class="bi bi-people-fill me-2" style="color:var(--wasom)"></i>Artistas
                </h1>
                <p class="text-muted small mb-0">Conta de <?php echo $owner_artist_name; ?></p>
            </div>
            <?php if ($can_edit_artists): ?>
                <a href="<?php echo $base_url; ?>/dashboard/artists/add-artist" class="btn btn-sm fw-semibold px-3"
                    style="background:var(--wasom);color:#fff;border-radius:20px">
                    <i class="bi bi-plus me-1"></i>Novo artista
                </a>
            <?php endif; ?>
        </div>

        <!-- Stat cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(255,0,137,.1)"><i class="bi bi-people"
                            style="color:var(--wasom)"></i></div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['total']; ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(25,135,84,.1)"><i class="bi bi-check-circle"
                            style="color:#198754"></i></div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['active']; ?></div>
                        <div class="stat-label">Activos</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(255,193,7,.1)"><i class="bi bi-hourglass-split"
                            style="color:#856404"></i></div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['processing']; ?></div>
                        <div class="stat-label">Em processo</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(108,117,125,.1)"><i class="bi bi-slash-circle"
                            style="color:#6c757d"></i></div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['inactive'] + (int)$stats['blocked']; ?></div>
                        <div class="stat-label">Inactivos</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filter-bar mb-4">
            <form method="GET" class="d-flex flex-wrap align-items-center gap-2">
                <div class="input-group" style="max-width:240px">
                    <span class="input-group-text" style="border-color:var(--border)"><i class="bi bi-search text-muted"
                            style="font-size:.8rem"></i></span>
                    <input type="text" class="form-control form-control-sm" name="q" placeholder="Pesquisar artista..."
                        value="<?php echo htmlspecialchars($search); ?>" style="border-color:var(--border)" />
                </div>
                <?php
                $filters = [
                    'all'        => ['Todos',       (int)$stats['total']],
                    'active'     => ['Activos',     (int)$stats['active']],
                    'processing' => ['Em processo', (int)$stats['processing']],
                    'inactive'   => ['Inactivos',   (int)$stats['inactive']],
                    'blocked'    => ['Bloqueados',  (int)$stats['blocked']],
                ];
                foreach ($filters as $val => [$lbl, $cnt]): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => $val])); ?>"
                        class="filter-pill <?php echo $filter === $val ? 'active' : ''; ?>">
                        <?php echo $lbl; ?><span class="count"><?php echo $cnt; ?></span>
                    </a>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
                <?php if ($search || $filter !== 'all'): ?>
                    <a href="?" class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i> Limpar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Grid de artistas -->
        <?php if (empty($artists)): ?>
            <div class="text-center py-5" style="color:var(--muted)">
                <div style="font-size:3.5rem;opacity:.2;margin-bottom:12px">🎤</div>
                <div class="fw-semibold">Nenhum artista encontrado</div>
                <div class="small mt-1">Tenta outros filtros</div>
                <?php if ($can_edit_artists): ?>
                    <a href="<?php echo $base_url; ?>/dashboard/artists/add-artist" class="btn btn-sm mt-3 px-4"
                        style="background:var(--wasom);color:#fff;border-radius:20px">
                        <i class="bi bi-plus me-1"></i>Adicionar artista
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($artists as $a):
                    $sm = $artist_status_meta[$a['status_artist']] ?? $artist_status_meta['inactive'];
                ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="artist-card h-100" onclick="openDetail(<?php echo $a['id_artist']; ?>)">
                            <!-- Cover + foto -->
                            <div class="artist-cover">
                                <?php if ($a['cover_artist']): ?>
                                    <img src="<?php echo htmlspecialchars($photo_base . $a['cover_artist']); ?>"
                                        onerror="this.style.display='none'" alt="" />
                                <?php else: ?><i class="bi bi-mic"></i><?php endif; ?>
                                <div class="artist-photo-wrap">
                                    <div class="artist-photo">
                                        <?php if ($a['photo_artist']): ?>
                                            <img src="<?php echo htmlspecialchars($photo_base . $a['photo_artist']); ?>"
                                                onerror="this.style.display='none'" alt="" />
                                        <?php else: ?><i class="bi bi-mic"></i><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="artist-body">
                                <!-- Status chip -->
                                <span class="chip mb-2"
                                    style="background:<?php echo $sm['bg']; ?>;color:<?php echo $sm['color']; ?>">
                                    <?php echo $sm['label']; ?>
                                </span>
                                <!-- Nome -->
                                <div class="fw-bold text-truncate" style="font-size:.9rem">
                                    <?php echo htmlspecialchars($a['stage_name']); ?>
                                </div>
                                <!-- Nome real -->
                                <?php if ($a['real_name']): ?>
                                    <div class="text-muted text-truncate" style="font-size:.72rem;margin-top:1px">
                                        <?php echo htmlspecialchars($a['real_name']); ?>
                                    </div>
                                <?php endif; ?>
                                <!-- Género + país -->
                                <div class="text-muted mt-1" style="font-size:.7rem">
                                    <?php if ($a['genre_main']): ?>
                                        <span><i
                                                class="bi bi-music-note me-1"></i><?php echo htmlspecialchars($a['genre_main']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($a['country']): ?>
                                        <span class="ms-2"><i
                                                class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($a['country']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <!-- Lançamentos -->
                                <div class="d-flex gap-2 mt-2 flex-wrap">
                                    <span class="chip"
                                        style="background:rgba(255,0,137,.07);color:var(--wasom);font-size:.65rem">
                                        <i class="bi bi-disc"></i> <?php echo (int)$a['total_albums']; ?>
                                        lançamento<?php echo $a['total_albums'] != 1 ? 's' : ''; ?>
                                    </span>
                                    <?php if ((int)$a['albums_pending'] > 0): ?>
                                        <span class="chip" style="background:rgba(255,193,7,.1);color:#856404;font-size:.65rem">
                                            <?php echo $a['albums_pending']; ?>
                                            pendente<?php echo $a['albums_pending'] != 1 ? 's' : ''; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <!-- Botão editar (só editor/admin) -->
                                <?php if ($can_edit_artists): ?>
                                    <div class="mt-3">
                                        <a href="<?php echo $base_url; ?>/dashboard/artists/add-artist?edit=<?php echo $a['id_artist']; ?>"
                                            class="btn btn-sm w-100" onclick="event.stopPropagation()"
                                            style="background:rgba(255,0,137,.07);color:var(--wasom);border:1px solid rgba(255,0,137,.15);font-size:.72rem;border-radius:8px">
                                            <i class="bi bi-pencil me-1"></i>Editar
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>


    <!-- ═══ PAINEL DE DETALHE ═══ -->
    <div class="detail-overlay" id="detailOverlay" onclick="closeDetail()"></div>
    <div class="detail-panel" id="detailPanel">
        <div id="detailContent">
            <div class="text-center py-5">
                <div class="spinner-border" style="color:#FF0089"></div>
            </div>
        </div>
    </div>

    <!-- Dados artistas para JS -->
    <script>
        const ARTISTS_DATA = <?php
                                $js_artists = [];
                                foreach ($artists as $a) {
                                    $js_artists[$a['id_artist']] = [
                                        'id_artist'     => (int)$a['id_artist'],
                                        'stage_name'    => $a['stage_name'],
                                        'real_name'     => $a['real_name'] ?? '',
                                        'genre_main'    => $a['genre_main'] ?? '',
                                        'genre_secondary' => $a['genre_secondary'] ?? '',
                                        'bio'           => $a['bio'] ?? '',
                                        'country'       => $a['country'] ?? '',
                                        'city'          => $a['city'] ?? '',
                                        'photo_artist'  => $a['photo_artist'] ? ($photo_base . $a['photo_artist']) : '',
                                        'cover_artist'  => $a['cover_artist']  ? ($photo_base . $a['cover_artist'])  : '',
                                        'status_artist' => $a['status_artist'],
                                        'facebook_url'  => $a['facebook_url']  ?? '',
                                        'instagram_url' => $a['instagram_url'] ?? '',
                                        'youtube_url'   => $a['youtube_url']   ?? '',
                                        'spotify_url'   => $a['spotify_url']   ?? '',
                                        'tiktok_url'    => $a['tiktok_url']    ?? '',
                                        'website_url'   => $a['website_url']   ?? '',
                                        'total_albums'  => (int)$a['total_albums'],
                                        'albums_approved' => (int)$a['albums_approved'],
                                        'albums_pending' => (int)$a['albums_pending'],
                                        'albums_rejected' => (int)$a['albums_rejected'],
                                        'albums_draft'   => (int)$a['albums_draft'],
                                        'creat_artist'  => $a['creat_artist'],
                                    ];
                                }
                                echo json_encode($js_artists);
                                ?>;

        const ALBUMS_BY_ARTIST = <?php
                                    $js_albums = [];
                                    foreach ($albums_by_artist as $artist_id => $albs) {
                                        foreach ($albs as $alb) {
                                            $js_albums[$artist_id][] = [
                                                'title_album'  => $alb['title_album'],
                                                'type_album'   => $alb['type_album'],
                                                'status_album' => $alb['status_album'],
                                                'img_cover'    => $alb['img_cover'] ? ($cover_base . $alb['img_cover']) : '',
                                                'release_date' => $alb['release_date'] ?? '',
                                            ];
                                        }
                                    }
                                    echo json_encode($js_albums);
                                    ?>;

        const BASE_URL = '<?php echo $base_url; ?>';
        const CAN_EDIT = <?php echo $can_edit_artists ? 'true' : 'false'; ?>;
        const PHOTO_BASE = '<?php echo $photo_base; ?>';

        const STATUS_META = {
            active: {
                label: 'Activo',
                color: '#198754',
                bg: 'rgba(25,135,84,.1)'
            },
            inactive: {
                label: 'Inactivo',
                color: '#6c757d',
                bg: 'rgba(108,117,125,.1)'
            },
            blocked: {
                label: 'Bloqueado',
                color: '#dc3545',
                bg: 'rgba(220,53,69,.1)'
            },
            processing: {
                label: 'Em processo',
                color: '#856404',
                bg: 'rgba(255,193,7,.12)'
            },
        };
        const ALBUM_STATUS_META = {
            approved: {
                label: 'Aprovado',
                color: '#198754',
                bg: 'rgba(25,135,84,.1)'
            },
            pending: {
                label: 'Pendente',
                color: '#856404',
                bg: 'rgba(255,193,7,.12)'
            },
            under_review: {
                label: 'Em revisão',
                color: '#0d6efd',
                bg: 'rgba(13,110,253,.1)'
            },
            rejected: {
                label: 'Recusado',
                color: '#dc3545',
                bg: 'rgba(220,53,69,.1)'
            },
            draft: {
                label: 'Rascunho',
                color: '#6c757d',
                bg: 'rgba(108,117,125,.1)'
            },
        };
    </script>


    <!-- Bottom nav -->
    <nav class="bottom-nav-collab">
        <a href="<?php echo $base_url; ?>/dashboard/collab/overview"><i class="bi bi-speedometer2"></i>Dashboard</a>
        <?php if ($can_view_releases): ?>
            <a href="<?php echo $base_url; ?>/dashboard/collab/releases"><i class="bi bi-disc"></i>Releases</a>
        <?php endif; ?>
        <a href="<?php echo $base_url; ?>/dashboard/collab/artists" class="active"><i
                class="bi bi-people"></i>Artistas</a>
        <?php if ($can_view_stats): ?>
            <a href="<?php echo $base_url; ?>/dashboard/collab/statistics"><i class="bi bi-bar-chart"></i>Stats</a>
        <?php endif; ?>
        <?php if ($can_view_finances): ?>
            <a href="<?php echo $base_url; ?>/dashboard/collab/finances"><i class="bi bi-currency-dollar"></i>Finanças</a>
        <?php endif; ?>
    </nav>


    <!-- Modal — O meu perfil -->
    <div class="modal fade" id="myProfileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="bi bi-person me-2" style="color:var(--wasom)"></i>O meu perfil
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="text-center mb-3">
                        <?php if ($collab['photo_collab']): ?>
                            <img src="<?php echo htmlspecialchars($collab['photo_collab']); ?>"
                                style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--wasom)"
                                onerror="this.style.display='none'" alt="" />
                        <?php else: ?>
                            <div
                                style="width:72px;height:72px;border-radius:50%;background:rgba(255,0,137,.1);display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto">
                                🎤</div>
                        <?php endif; ?>
                        <h5 class="fw-bold mt-2 mb-0">
                            <?php echo htmlspecialchars($collab['first_name'] . ' ' . ($collab['second_name'] ?? '')); ?>
                        </h5>
                        <div class="text-muted small">@<?php echo htmlspecialchars($collab['user_collab']); ?></div>
                    </div>
                    <div style="font-size:.83rem">
                        <?php
                        $info_rows = [
                            ['Email',        $collab['email_collab'],       'bi-envelope'],
                            ['Telefone',     $collab['tel_collab'] ?: '—',  'bi-telephone'],
                            ['Função',       $role_label,                    'bi-person-badge'],
                            ['Membro desde', date('d/m/Y', strtotime($collab['creat_collab'])), 'bi-calendar3'],
                            ['Último login', $collab['last_login_at'] ? date('d/m/Y H:i', strtotime($collab['last_login_at'])) : '—', 'bi-clock'],
                        ];
                        foreach ($info_rows as [$label, $val, $ico]):
                        ?>
                            <div class="d-flex gap-2 py-2 border-bottom align-items-center">
                                <i class="bi <?php echo $ico; ?> text-muted" style="width:16px"></i>
                                <span class="text-muted" style="width:100px;flex-shrink:0"><?php echo $label; ?></span>
                                <span class="fw-semibold text-truncate"><?php echo htmlspecialchars($val); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($collab['notes']): ?>
                        <div class="mt-3 p-3"
                            style="background:rgba(255,0,137,.04);border-radius:10px;border:1px solid rgba(255,0,137,.1)">
                            <div class="text-muted" style="font-size:.7rem;margin-bottom:4px">NOTAS DO ADMINISTRADOR</div>
                            <div style="font-size:.82rem"><?php echo htmlspecialchars($collab['notes']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════ MODAL — Logout ════ -->
    <div class="modal fade" id="logoutModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:360px">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Terminar sessão?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    <p class="text-muted small mb-0">Vais sair do painel de colaboradores.</p>
                </div>
                <div class="modal-footer border-0 gap-2">
                    <button class="btn btn-outline-secondary flex-fill btn-sm"
                        data-bs-dismiss="modal">Continuar</button>
                    <a href="<?php echo htmlspecialchars($logout_url); ?>" class="btn btn-danger flex-fill btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Terminar
                    </a>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        // ── Sidebar toggle ────────────────────────────
        function closeSidebar() {
            document.getElementById('collabSidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('show');
        }
        document.getElementById('btn-sidebar-toggle')?.addEventListener('click', () => {
            const sb = document.getElementById('collabSidebar');
            const ov = document.getElementById('sidebarOverlay');
            sb.classList.toggle('open');
            ov.classList.toggle('show', sb.classList.contains('open'));
        });

        // ── Theme ─────────────────────────────────────
        const html = document.documentElement;
        const saved = localStorage.getItem('wu_theme') || 'light';
        html.setAttribute('data-theme', saved);
        document.getElementById('themeIcon').className = saved === 'dark' ? 'bi bi-moon' : 'bi bi-sun';
        document.getElementById('themeToggle').addEventListener('click', () => {
            const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('wu_theme', next);
            document.getElementById('themeIcon').className = next === 'dark' ? 'bi bi-moon' : 'bi bi-sun';
        });

        // ── Painel de detalhe ─────────────────────────
        function openDetail(id) {
            const a = ARTISTS_DATA[id];
            if (!a) return;

            const sm = STATUS_META[a.status_artist] || STATUS_META.inactive;
            const albs = ALBUMS_BY_ARTIST[id] || [];

            // Socials
            const socials = [
                ['instagram_url', 'bi-instagram', 'Instagram'],
                ['spotify_url', 'bi-spotify', 'Spotify'],
                ['youtube_url', 'bi-youtube', 'YouTube'],
                ['tiktok_url', 'bi-tiktok', 'TikTok'],
                ['facebook_url', 'bi-facebook', 'Facebook'],
                ['website_url', 'bi-globe', 'Website'],
            ].filter(([k]) => a[k]).map(([k, icon, lbl]) =>
                `<a href="${a[k]}" target="_blank" class="social-btn"><i class="bi ${icon}"></i>${lbl}</a>`
            ).join('');

            // Albums
            const albsHtml = albs.length ? albs.map(alb => {
                const as = ALBUM_STATUS_META[alb.status_album] || ALBUM_STATUS_META.draft;
                return `<div class="d-flex align-items-center gap-2 py-2 border-bottom">
            <div style="width:36px;height:36px;border-radius:8px;overflow:hidden;background:rgba(255,0,137,.06);flex-shrink:0;display:flex;align-items:center;justify-content:center">
                ${alb.img_cover ? `<img src="${alb.img_cover}" style="width:100%;height:100%;object-fit:cover" onerror="this.parentElement.innerHTML='🎵'"/>` : '🎵'}
            </div>
            <div style="flex:1;min-width:0">
                <div class="fw-semibold text-truncate" style="font-size:.82rem">${alb.title_album}</div>
                <div class="text-muted" style="font-size:.7rem">${alb.type_album}${alb.release_date ? ' · '+alb.release_date : ''}</div>
            </div>
            <span style="background:${as.bg};color:${as.color};padding:2px 8px;border-radius:12px;font-size:.65rem;font-weight:700;white-space:nowrap">${as.label}</span>
        </div>`;
            }).join('') : '<div class="text-muted small text-center py-3">Sem lançamentos</div>';

            document.getElementById('detailContent').innerHTML = `
    <!-- Cover -->
    <div class="detail-cover">
        ${a.cover_artist ? `<img src="${a.cover_artist}" onerror="this.style.display='none'" alt=""/>` : ''}
        <div style="position:absolute;top:12px;right:12px">
            <button class="btn btn-sm" style="background:rgba(255,255,255,.9);border:none;border-radius:50%;width:32px;height:32px;padding:0" onclick="closeDetail()">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>

    <!-- Foto + nome -->
    <div class="detail-photo">
        ${a.photo_artist ? `<img src="${a.photo_artist}" onerror="this.innerHTML='🎤'" alt=""/>` : '🎤'}
    </div>
    <div class="px-4 pt-2 pb-1">
        <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
            <div>
                <h5 class="fw-bold mb-0">${a.stage_name}</h5>
                ${a.real_name ? `<div class="text-muted small">${a.real_name}</div>` : ''}
            </div>
            <span style="background:${sm.bg};color:${sm.color};padding:4px 12px;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap">${sm.label}</span>
        </div>
    </div>

    <!-- Info -->
    <div class="px-4 pb-3">
        <!-- Género + localização -->
        <div class="d-flex flex-wrap gap-2 mb-3">
            ${a.genre_main ? `<span style="background:rgba(255,0,137,.07);color:#FF0089;padding:4px 10px;border-radius:20px;font-size:.72rem;font-weight:600"><i class="bi bi-music-note me-1"></i>${a.genre_main}</span>` : ''}
            ${a.genre_secondary ? `<span style="background:rgba(255,0,137,.05);color:#FF0089;padding:4px 10px;border-radius:20px;font-size:.72rem;font-weight:600">${a.genre_secondary}</span>` : ''}
            ${a.country ? `<span style="background:rgba(108,117,125,.08);color:#6c757d;padding:4px 10px;border-radius:20px;font-size:.72rem;font-weight:600"><i class="bi bi-geo-alt me-1"></i>${a.city ? a.city+', ' : ''}${a.country}</span>` : ''}
        </div>

        <!-- Bio -->
        ${a.bio ? `<div class="mb-3 p-3" style="background:rgba(255,0,137,.03);border-radius:10px;border:1px solid rgba(255,0,137,.08);font-size:.82rem;line-height:1.5">${a.bio}</div>` : ''}

        <!-- Stats de lançamentos -->
        <div class="row g-2 mb-3">
            ${[
                ['Total',      a.total_albums,    '#FF0089'],
                ['Aprovados',  a.albums_approved, '#198754'],
                ['Pendentes',  a.albums_pending,  '#856404'],
                ['Rascunhos',  a.albums_draft,    '#6c757d'],
            ].map(([l,v,c]) => `
            <div class="col-3">
                <div style="background:var(--bg);border-radius:10px;padding:10px;text-align:center">
                    <div style="font-size:1.2rem;font-weight:800;color:${c}">${v}</div>
                    <div style="font-size:.65rem;color:var(--muted)">${l}</div>
                </div>
            </div>`).join('')}
        </div>

        <!-- Socials -->
        ${socials ? `<div class="d-flex flex-wrap gap-1 mb-3">${socials}</div>` : ''}

        <!-- Últimos lançamentos -->
        <div class="fw-semibold small mb-2"><i class="bi bi-disc me-1" style="color:#FF0089"></i>Últimos lançamentos</div>
        ${albsHtml}

        <!-- Botão editar -->
        ${CAN_EDIT ? `
        <a href="${BASE_URL}/dashboard/artists/add-artist?edit=${a.id_artist}"
           class="btn btn-sm w-100 mt-3 fw-semibold" style="background:#FF0089;color:#fff;border-radius:20px">
            <i class="bi bi-pencil me-1"></i>Editar artista
        </a>` : ''}
    </div>`;

            document.getElementById('detailPanel').classList.add('open');
            document.getElementById('detailOverlay').classList.add('show');
        }

        function closeDetail() {
            document.getElementById('detailPanel').classList.remove('open');
            document.getElementById('detailOverlay').classList.remove('show');
        }

        // ── Ping ──────────────────────────────────────
        setInterval(() => {
            fetch('<?php echo $base_url; ?>/dashboard/collab/ping', {
                method: 'POST'
            }).catch(() => {});
        }, 120000);
    </script>
</body>

</html>