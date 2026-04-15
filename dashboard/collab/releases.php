<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Lançamentos (Colaboradores)
// Arquivo: dashboard/collab/releases.php
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

// ── Actualizar last_seen ──────────────────────
$db->prepare("UPDATE _collaborators SET last_seen_at = NOW() WHERE id_collab = ?")
    ->execute([$id_collab]);

// ── Dados do proprietário da conta ───────────
$owner = getUserById($id_users);
if (!$owner) {
    session_destroy();
    header('Location: ' . rtrim(APP_URL, '/') . '/dashboard/account/collab-login');
    exit;
}

$owner_name        = htmlspecialchars(trim($owner['first_name'] . ' ' . ($owner['second_name'] ?? '')));
$owner_artist_name = htmlspecialchars($owner['name_artist_band'] ?? $owner['first_name']);

// ── Plano ─────────────────────────────────────
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
$can_view_finances = in_array($role, ['admin', 'analyst']);
$can_view_stats    = in_array($role, ['admin', 'analyst', 'editor']);

// Bloquear acesso se não tem permissão
if (!$can_view_releases) {
    header('Location: ' . rtrim(APP_URL, '/') . '/dashboard/collab/overview?error=noaccess');
    exit;
}

// ── Lançamentos ───────────────────────────────
$filter_status = $_GET['status'] ?? 'all';
$filter_type   = $_GET['type']   ?? 'all';
$search        = trim($_GET['q'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 12;

$where   = ["a.id_users = ?"];
$params  = [$id_users];

if ($filter_status !== 'all') {
    $where[] = "a.status_album = ?";
    $params[] = $filter_status;
}
if ($filter_type !== 'all') {
    $where[] = "a.type_album = ?";
    $params[] = $filter_type;
}
if ($search !== '') {
    $where[] = "(a.title_album LIKE ? OR ar.stage_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = implode(' AND ', $where);

// Total
$cnt = $db->prepare("
    SELECT COUNT(*) FROM _album a
    LEFT JOIN _artist ar ON ar.id_artist = a.id_artist
    WHERE $where_sql
");
$cnt->execute($params);
$total    = (int)$cnt->fetchColumn();
$pages    = max(1, ceil($total / $per_page));
$offset   = ($page - 1) * $per_page;

// Albums
$stmt = $db->prepare("
    SELECT a.id_album, a.title_album, a.type_album, a.status_album,
           a.img_cover, a.release_date, a.creat_album, a.upc,
           a.genre_main, a.smartlink,
           ar.stage_name, ar.id_artist
    FROM _album a
    LEFT JOIN _artist ar ON ar.id_artist = a.id_artist
    WHERE $where_sql
    ORDER BY a.creat_album DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$albums = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats rápidas (topo)
$stats_q = $db->prepare("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status_album='approved'    THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status_album='pending'     THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status_album='under_review'THEN 1 ELSE 0 END) as review,
        SUM(CASE WHEN status_album='rejected'    THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status_album='draft'       THEN 1 ELSE 0 END) as draft
    FROM _album WHERE id_users = ?
");
$stats_q->execute([$id_users]);
$stats = $stats_q->fetch();

// ── Helpers ───────────────────────────────────
$role_meta = [
    'admin'   => ['label' => 'Administrador', 'color' => '#dc3545', 'bg' => 'rgba(220,53,69,.1)',   'icon' => 'bi-shield-fill'],
    'editor'  => ['label' => 'Editor',        'color' => '#FF0089', 'bg' => 'rgba(255,0,137,.1)',  'icon' => 'bi-pencil-fill'],
    'analyst' => ['label' => 'Analista',      'color' => '#0d6efd', 'bg' => 'rgba(13,110,253,.1)', 'icon' => 'bi-bar-chart-fill'],
    'support' => ['label' => 'Suporte',       'color' => '#198754', 'bg' => 'rgba(25,135,84,.1)',  'icon' => 'bi-headset'],
];
$rm         = $role_meta[$role] ?? $role_meta['support'];
$role_label = $rm['label'];

$status_meta = [
    'approved'    => ['label' => 'Aprovado',    'color' => '#198754', 'bg' => 'rgba(25,135,84,.1)'],
    'pending'     => ['label' => 'Pendente',    'color' => '#856404', 'bg' => 'rgba(255,193,7,.12)'],
    'under_review' => ['label' => 'Em revisão',  'color' => '#0d6efd', 'bg' => 'rgba(13,110,253,.1)'],
    'rejected'    => ['label' => 'Recusado',    'color' => '#dc3545', 'bg' => 'rgba(220,53,69,.1)'],
    'draft'       => ['label' => 'Rascunho',    'color' => '#6c757d', 'bg' => 'rgba(108,117,125,.1)'],
];
$type_labels = ['single' => 'Single', 'EP' => 'EP', 'album' => 'Álbum', 'mixtape' => 'Mixtape'];

$logout_url = rtrim(APP_URL, '/') . '/dashboard/collab/logout';
$cover_base = rtrim(APP_URL, '/') . '/assets/comprovantes/uploads/covers/';
$base_url   = rtrim(APP_URL, '/');

// Log activity
try {
    $db->prepare("INSERT INTO _collab_activity (id_collab,id_users,activity_type,description,ip_address) VALUES (?,?,?,?,?)")
        ->execute([$id_collab, $id_users, 'releases_view', 'Visualizou página de lançamentos', $_SERVER['REMOTE_ADDR'] ?? null]);
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF2D66" />
    <title>Lançamentos — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <link rel="stylesheet" href="css/collab.css" />
    <style>
    /* ── Album cards ── */
    .album-card {
        background: var(--card);
        border-radius: 16px;
        border: 1.5px solid var(--border);
        overflow: hidden;
        transition: border-color .2s, box-shadow .2s;
    }

    .album-card:hover {
        border-color: rgba(255, 0, 137, .2);
        box-shadow: 0 4px 20px rgba(255, 0, 137, .08);
    }

    .album-cover {
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
        background: rgba(255, 0, 137, .06);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
    }

    .album-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .album-body {
        padding: 14px;
    }

    /* ── Filtros ── */
    .filter-bar {
        background: var(--card);
        border-radius: 14px;
        border: 1.5px solid var(--border);
        padding: 16px;
        margin-bottom: 20px;
    }
    </style>
</head>

<body>

    <!-- ═══ NAVBAR ═══ -->>
    <?php require_once __DIR__ . '/include/navbar-top.php'; ?>
    <!-- ═══ SIDEBAR OVERLAY (mobile) ═══ -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- ═══ SIDEBAR ═══ -->
    <?php require_once __DIR__ . '/include/sidebar.php'; ?>


    <!-- ═══ MAIN CONTENT ═══ -->
    <main class="main-content">

        <!-- Cabeçalho -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h4 fw-bold mb-1"><i class="bi bi-disc me-2" style="color:var(--wasom)"></i>Lançamentos</h1>
                <p class="text-muted small mb-0">Conta de <?php echo $owner_artist_name; ?></p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($can_edit_releases): ?>
                <button class="btn btn-sm fw-semibold px-3"
                    style="background:var(--wasom);color:#fff;border-radius:20px" data-bs-toggle="modal"
                    data-bs-target="#collabCreateModal">
                    <i class="bi bi-plus me-1"></i>Novo lançamento
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stat cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(255,0,137,.1)"><i class="bi bi-collection"
                            style="color:var(--wasom)"></i></div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['total']; ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(25,135,84,.1)"><i class="bi bi-check-circle"
                            style="color:#198754"></i></div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['approved']; ?></div>
                        <div class="stat-label">Aprovados</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(255,193,7,.1)"><i class="bi bi-hourglass-split"
                            style="color:#856404"></i></div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['pending']; ?></div>
                        <div class="stat-label">Pendentes</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(13,110,253,.1)"><i class="bi bi-search"
                            style="color:#0d6efd"></i></div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['review']; ?></div>
                        <div class="stat-label">Em revisão</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(220,53,69,.1)"><i class="bi bi-x-circle"
                            style="color:#dc3545"></i></div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['rejected']; ?></div>
                        <div class="stat-label">Recusados</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(108,117,125,.1)"><i class="bi bi-pencil"
                            style="color:#6c757d"></i></div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['draft']; ?></div>
                        <div class="stat-label">Rascunhos</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filter-bar mb-4">
            <form method="GET" class="d-flex flex-wrap align-items-center gap-2">
                <!-- Pesquisa -->
                <div class="input-group" style="max-width:240px">
                    <span class="input-group-text" style="border-color:var(--border)"><i class="bi bi-search text-muted"
                            style="font-size:.8rem"></i></span>
                    <input type="text" class="form-control form-control-sm" name="q" placeholder="Pesquisar..."
                        value="<?php echo htmlspecialchars($search); ?>" style="border-color:var(--border)" />
                </div>

                <!-- Status pills -->
                <?php
                $status_filters = [
                    'all'          => ['Todos', (int)$stats['total']],
                    'approved'     => ['Aprovados', (int)$stats['approved']],
                    'pending'      => ['Pendentes', (int)$stats['pending']],
                    'under_review' => ['Em revisão', (int)$stats['review']],
                    'rejected'     => ['Recusados', (int)$stats['rejected']],
                    'draft'        => ['Rascunhos', (int)$stats['draft']],
                ];
                foreach ($status_filters as $val => [$lbl, $cnt_v]):
                    $is_active = $filter_status === $val;
                    $url = '?' . http_build_query(array_merge($_GET, ['status' => $val, 'page' => 1]));
                ?>
                <a href="<?php echo htmlspecialchars($url); ?>"
                    class="filter-pill <?php echo $is_active ? 'active' : ''; ?>">
                    <?php echo $lbl; ?>
                    <span class="count"><?php echo $cnt_v; ?></span>
                </a>
                <?php endforeach; ?>

                <!-- Tipo -->
                <select class="form-select form-select-sm" name="type"
                    style="max-width:130px;border-color:var(--border)" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter_type === 'all'     ? 'selected' : ''; ?>>Todos os tipos
                    </option>
                    <option value="single" <?php echo $filter_type === 'single'  ? 'selected' : ''; ?>>Single</option>
                    <option value="EP" <?php echo $filter_type === 'EP'      ? 'selected' : ''; ?>>EP</option>
                    <option value="album" <?php echo $filter_type === 'album'   ? 'selected' : ''; ?>>Álbum</option>
                    <option value="mixtape" <?php echo $filter_type === 'mixtape' ? 'selected' : ''; ?>>Mixtape</option>
                </select>

                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-funnel"></i>
                </button>
                <?php if ($search || $filter_status !== 'all' || $filter_type !== 'all'): ?>
                <a href="?" class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i> Limpar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Grid de álbuns -->
        <?php if (empty($albums)): ?>
        <div class="text-center py-5" style="color:var(--muted)">
            <div style="font-size:3.5rem;opacity:.2;margin-bottom:12px">🎵</div>
            <div class="fw-semibold">Nenhum lançamento encontrado</div>
            <div class="small mt-1">Tenta outros filtros ou pesquisa</div>
            <?php if ($can_edit_releases): ?>
            <button class="btn btn-sm mt-3 px-4" style="background:var(--wasom);color:#fff;border-radius:20px"
                data-bs-toggle="modal" data-bs-target="#collabCreateModal">
                <i class="bi bi-plus me-1"></i>Criar lançamento
            </button>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="row g-3 mb-4">
            <?php foreach ($albums as $alb):
                    $sm = $status_meta[$alb['status_album']] ?? $status_meta['draft'];
                    $tl = $type_labels[$alb['type_album']] ?? $alb['type_album'];
                ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="album-card h-100">
                    <!-- Capa -->
                    <div class="album-cover" style="height:160px">
                        <?php if ($alb['img_cover']): ?>
                        <img src="<?php echo htmlspecialchars($cover_base . $alb['img_cover']); ?>" alt=""
                            onerror="this.style.display='none';this.parentElement.innerHTML='<span style=\'font-size:3rem\'>🎵</span>'" />
                        <?php else: ?>
                        <span style="font-size:3rem">🎵</span>
                        <?php endif; ?>
                    </div>
                    <div class="album-body">
                        <!-- Status + tipo -->
                        <div class="d-flex gap-1 mb-2 flex-wrap">
                            <span class="chip"
                                style="background:<?php echo $sm['bg']; ?>;color:<?php echo $sm['color']; ?>">
                                <?php echo $sm['label']; ?>
                            </span>
                            <span class="chip" style="background:rgba(255,0,137,.07);color:var(--wasom)">
                                <?php echo $tl; ?>
                            </span>
                        </div>
                        <!-- Título -->
                        <div class="fw-bold text-truncate" style="font-size:.88rem">
                            <?php echo htmlspecialchars($alb['title_album']); ?>
                        </div>
                        <!-- Artista -->
                        <div class="text-muted text-truncate" style="font-size:.73rem;margin-top:2px">
                            <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($alb['stage_name'] ?? '—'); ?>
                        </div>
                        <!-- Data -->
                        <div class="text-muted" style="font-size:.7rem;margin-top:4px">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?php echo $alb['release_date'] ? date('d/m/Y', strtotime($alb['release_date'])) : date('d/m/Y', strtotime($alb['creat_album'])); ?>
                        </div>
                        <!-- UPC -->
                        <?php if ($alb['upc']): ?>
                        <div class="text-muted mt-1" style="font-size:.68rem;font-family:monospace">
                            UPC: <?php echo htmlspecialchars($alb['upc']); ?>
                        </div>
                        <?php endif; ?>
                        <!-- Acções -->
                        <div class="d-flex gap-1 mt-3 flex-wrap">
                            <button class="btn btn-sm flex-fill"
                                style="background:rgba(255,0,137,.07);color:var(--wasom);border:1px solid rgba(255,0,137,.15);font-size:.72rem;border-radius:8px"
                                onclick="viewAlbum(<?php echo $alb['id_album']; ?>)">
                                <i class="bi bi-eye me-1"></i>Ver detalhes
                            </button>
                            <?php if ($can_edit_releases && in_array($alb['status_album'], ['draft', 'rejected'])): ?>
                            <button class="btn btn-sm"
                                style="background:rgba(255,0,137,.1);color:var(--wasom);border:1px solid rgba(255,0,137,.2);font-size:.72rem;border-radius:8px"
                                data-bs-toggle="modal" data-bs-target="#collabCreateModal">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginação -->
        <?php if ($pages > 1): ?>
        <nav class="d-flex justify-content-center gap-1 flex-wrap">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>"
                class="btn btn-sm <?php echo $p === $page ? 'btn-wasom' : 'btn-outline-secondary'; ?>"
                style="<?php echo $p === $page ? 'background:var(--wasom);color:#fff;border-color:var(--wasom)' : ''; ?>; min-width:36px">
                <?php echo $p; ?>
            </a>
            <?php endfor; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>

    </main><!-- /main-content -->


    <!-- Bottom nav -->
    <?php require_once __DIR__ . '/include/navbar-bottom.php'; ?>

    <?php require_once __DIR__ . '/include/modallogoutmyprofile.php'; ?>


    <!-- Modal aviso: criar/editar lançamento -->
    <div class="modal fade" id="collabCreateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="bi bi-disc me-2" style="color:var(--wasom)"></i>Criar / Editar
                        lançamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="text-center mb-3" style="font-size:2.5rem">🚧</div>
                    <p class="text-muted small mb-0 text-center">
                        A criação e edição de lançamentos no painel de colaboradores está a ser construída.<br /><br />
                        Pede ao proprietário da conta para criar ou editar o lançamento, ou aguarda a próxima
                        actualização.
                    </p>
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-outline-secondary btn-sm w-100" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal detalhe do álbum -->
    <div class="modal fade" id="albumDetailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-disc me-2" style="color:var(--wasom)"></i><span
                            id="modal-album-title">Detalhes</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0" id="modal-album-body">
                    <div class="text-center py-4">
                        <div class="spinner-border" style="color:var(--wasom)"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
    const BASE_URL = '<?php echo $base_url; ?>';

    // ── Sidebar toggle ────────────────────────────
    function closeSidebar() {
        document.getElementById('collabSidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
    }
    document.getElementById('btn-sidebar-toggle')?.addEventListener('click', () => {
        const sb = document.getElementById('collabSidebar');
        const ov = document.getElementById('sidebarOverlay');
        const open = sb.classList.toggle('open');
        ov.classList.toggle('show', open);
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

    // ── Ver detalhes do álbum (AJAX) ──────────────
    const albumModal = new bootstrap.Modal(document.getElementById('albumDetailModal'));

    async function viewAlbum(id) {
        document.getElementById('modal-album-title').textContent = 'A carregar...';
        document.getElementById('modal-album-body').innerHTML =
            '<div class="text-center py-4"><div class="spinner-border" style="color:#FF0089"></div></div>';
        albumModal.show();

        try {
            const r = await fetch(`${BASE_URL}/dashboard/collab/releases_ajax.php?id=${id}`);
            const data = await r.json();

            if (!data.ok) throw new Error(data.message || 'Erro');

            const a = data.album;
            const sm = {
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
            } [a.status_album] || {
                label: a.status_album,
                color: '#6c757d',
                bg: 'rgba(108,117,125,.1)'
            };

            document.getElementById('modal-album-title').textContent = a.title_album;

            let tracksHtml = '';
            if (data.tracks && data.tracks.length) {
                tracksHtml = `<div class="mt-3">
                <div class="fw-semibold small mb-2"><i class="bi bi-music-note-list me-1" style="color:#FF0089"></i>Faixas (${data.tracks.length})</div>
                ${data.tracks.map((t,i) => `
                <div class="d-flex align-items-center gap-2 py-2 border-bottom" style="font-size:.82rem">
                    <span style="width:22px;height:22px;border-radius:50%;background:rgba(255,0,137,.08);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#FF0089;flex-shrink:0">${t.track_number}</span>
                    <div style="flex:1;min-width:0">
                        <div class="fw-semibold text-truncate">${t.title_track}</div>
                        <div class="text-muted" style="font-size:.7rem">${t.name_author || '—'}${t.name_author_feat ? ' ft. '+t.name_author_feat : ''}</div>
                    </div>
                    ${t.explicit==='YES' ? '<span style="font-size:.6rem;background:#dc3545;color:#fff;padding:1px 5px;border-radius:4px;font-weight:700">E</span>' : ''}
                    ${t.duration_seconds ? '<span class="text-muted" style="font-size:.7rem;white-space:nowrap">'+Math.floor(t.duration_seconds/60)+':'+(t.duration_seconds%60).toString().padStart(2,'0')+'</span>' : ''}
                </div>`).join('')}
            </div>`;
            }

            let rejectionHtml = '';
            if (a.status_album === 'rejected' && a.rejection_reason) {
                rejectionHtml = `<div class="mt-3 p-3" style="background:rgba(220,53,69,.06);border-radius:10px;border:1px solid rgba(220,53,69,.15)">
                <div style="font-size:.7rem;color:#dc3545;font-weight:700;margin-bottom:4px">MOTIVO DA RECUSA</div>
                <div style="font-size:.83rem">${a.rejection_reason}</div>
            </div>`;
            }

            document.getElementById('modal-album-body').innerHTML = `
        <div class="row g-3">
            <div class="col-md-4">
                <div style="width:100%;aspect-ratio:1;border-radius:12px;overflow:hidden;background:rgba(255,0,137,.06);display:flex;align-items:center;justify-content:center;font-size:4rem">
                    ${a.img_cover
                        ? `<img src="${BASE_URL}/assets/comprovantes/uploads/covers/${a.img_cover}" style="width:100%;height:100%;object-fit:cover" onerror="this.parentElement.textContent='🎵'"/>`
                        : '🎵'}
                </div>
            </div>
            <div class="col-md-8">
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <span style="display:inline-flex;align-items:center;gap:4px;background:${sm.bg};color:${sm.color};padding:4px 12px;border-radius:20px;font-size:.73rem;font-weight:700">${sm.label}</span>
                    <span style="display:inline-flex;align-items:center;gap:4px;background:rgba(255,0,137,.07);color:#FF0089;padding:4px 12px;border-radius:20px;font-size:.73rem;font-weight:700">${a.type_album}</span>
                </div>
                <div style="font-size:.8rem">
                    ${[
                        ['Artista',    a.stage_name   || '—',          'bi-person'],
                        ['Género',     a.genre_main   || '—',          'bi-music-note'],
                        ['Lançamento', a.release_date ? a.release_date : '—', 'bi-calendar3'],
                        ['Território', a.territory    || 'Worldwide',  'bi-globe'],
                        ['UPC',        a.upc          || 'Ainda não atribuído', 'bi-upc'],
                    ].map(([l,v,i]) => `
                    <div class="d-flex gap-2 py-2 border-bottom align-items-center">
                        <i class="bi ${i} text-muted" style="width:16px;font-size:.8rem"></i>
                        <span class="text-muted" style="width:90px;flex-shrink:0;font-size:.75rem">${l}</span>
                        <span class="fw-semibold text-truncate">${v}</span>
                    </div>`).join('')}
                </div>
                ${a.smartlink ? `<a href="${a.smartlink}" target="_blank" class="btn btn-sm mt-3 px-3" style="background:var(--wasom);color:#fff;border-radius:20px;font-size:.75rem"><i class="bi bi-link-45deg me-1"></i>Smart link</a>` : ''}
            </div>
        </div>
        ${rejectionHtml}
        ${tracksHtml}`;
        } catch (e) {
            document.getElementById('modal-album-body').innerHTML =
                `<div class="alert alert-danger small py-2"><i class="bi bi-exclamation-circle me-1"></i>${e.message}</div>`;
        }
    }

    // ── Ping last_seen ────────────────────────────
    setInterval(() => {
        fetch('<?php echo $base_url; ?>/dashboard/collab/ping', {
            method: 'POST'
        }).catch(() => {});
    }, 120000);
    </script>
</body>

</html>