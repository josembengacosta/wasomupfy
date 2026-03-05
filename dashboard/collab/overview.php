<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Painel de Colaboradores
// Arquivo: dashboard/collab/overview.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();

// ── Verificar sessão de colaborador ──────────
if (empty($_SESSION['collab_id']) || empty($_SESSION['collab_id_users'])) {
    header('Location: ' . rtrim(APP_URL, '/') . '/dashboard/account/collab-login');
    exit;
}

// Requer mudança de senha pendente?
if (!empty($_SESSION['collab_must_change'])) {
    header('Location: ' . rtrim(APP_URL, '/') . '/dashboard/account/collab-login');
    exit;
}

$db         = getDB();
$id_collab  = (int)$_SESSION['collab_id'];
$id_users   = (int)$_SESSION['collab_id_users']; // proprietário da conta
$role       = $_SESSION['collab_role'] ?? 'support';

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

// ── Plano do proprietário ─────────────────────
$plan = null;
if ($owner['plan_selected']) {
    $ps = $db->prepare("SELECT * FROM _plans WHERE id_plan = ?");
    $ps->execute([$owner['plan_selected']]);
    $plan = $ps->fetch();
}
$plan_name = $plan ? htmlspecialchars($plan['name_plan']) : 'Sem plano';

// ── Permissões por role ───────────────────────
// admin    → tudo visível, sem acesso à Zona de Perigo e equipa
// editor   → lançamentos + artistas (sem finanças)
// analyst  → estatísticas + finanças (só leitura)
// support  → lançamentos só leitura
$can_view_releases  = in_array($role, ['admin', 'editor', 'support']);
$can_edit_releases  = in_array($role, ['admin', 'editor']);
$can_view_artists   = in_array($role, ['admin', 'editor']);
$can_view_finances  = in_array($role, ['admin', 'analyst']);
$can_view_stats     = in_array($role, ['admin', 'analyst', 'editor']);

// ── Stats para o dashboard ────────────────────

// Lançamentos
$alb = $db->prepare("SELECT COUNT(*) as total,
    SUM(CASE WHEN status_album='approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status_album='pending'  THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status_album='rejected' THEN 1 ELSE 0 END) as rejected
    FROM _album WHERE id_users = ?");
$alb->execute([$id_users]);
$album_stats = $alb->fetch();

// Artistas
$art = $db->prepare("SELECT COUNT(*) as total FROM _artist WHERE id_users = ?");
$art->execute([$id_users]);
$artist_count = (int)($art->fetchColumn());

// Finanças (só para admin/analyst)
$wallet = null;
if ($can_view_finances) {
    $ws = $db->prepare("SELECT balance_aoa, balance_usd, total_earned, total_withdrawn FROM _wallet WHERE id_users = ?");
    $ws->execute([$id_users]);
    $wallet = $ws->fetch();
}

// Últimos lançamentos (5)
$recent_albums = [];
if ($can_view_releases) {
    $ra = $db->prepare("
        SELECT a.id_album, a.title_album, a.type_album, a.status_album,
               a.img_cover, a.creat_album, ar.stage_name
        FROM _album a
        LEFT JOIN _artist ar ON ar.id_artist = a.id_artist
        WHERE a.id_users = ?
        ORDER BY a.creat_album DESC LIMIT 5
    ");
    $ra->execute([$id_users]);
    $recent_albums = $ra->fetchAll(PDO::FETCH_ASSOC);
}

// Actividades do próprio colaborador (últimas 8)
$acts = $db->prepare("
    SELECT activity_type, description, creat_activity
    FROM _collab_activity WHERE id_collab = ?
    ORDER BY creat_activity DESC LIMIT 8
");
$acts->execute([$id_collab]);
$my_activities = $acts->fetchAll(PDO::FETCH_ASSOC);

// ── Helpers ───────────────────────────────────
$role_meta = [
    'admin'   => ['label' => 'Administrador', 'color' => '#dc3545', 'bg' => 'rgba(220,53,69,.1)',  'icon' => 'bi-shield-fill'],
    'editor'  => ['label' => 'Editor',        'color' => '#FF0089', 'bg' => 'rgba(255,0,137,.1)', 'icon' => 'bi-pencil-fill'],
    'analyst' => ['label' => 'Analista',      'color' => '#0d6efd', 'bg' => 'rgba(13,110,253,.1)', 'icon' => 'bi-bar-chart-fill'],
    'support' => ['label' => 'Suporte',       'color' => '#198754', 'bg' => 'rgba(25,135,84,.1)', 'icon' => 'bi-headset'],
];
$rm         = $role_meta[$role] ?? $role_meta['support'];
$role_label = $rm['label'];

$album_status_meta = [
    'approved' => ['label' => 'Aprovado',    'color' => '#198754', 'bg' => 'rgba(25,135,84,.1)'],
    'pending'  => ['label' => 'Pendente',    'color' => '#856404', 'bg' => 'rgba(255,193,7,.12)'],
    'rejected' => ['label' => 'Recusado',    'color' => '#dc3545', 'bg' => 'rgba(220,53,69,.1)'],
    'draft'    => ['label' => 'Rascunho',    'color' => '#6c757d', 'bg' => 'rgba(108,117,125,.1)'],
    'review'   => ['label' => 'Em revisão',  'color' => '#0d6efd', 'bg' => 'rgba(13,110,253,.1)'],
];

$logout_url  = rtrim(APP_URL, '/') . '/dashboard/collab/logout';
$cover_base  = rtrim(APP_URL, '/') . '/assets/comprovantes/uploads/covers/';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <link rel="manifest" href="../../dashboard/manifest.json" />
    <title>Painel — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <style>
    :root {
        --wasom: #FF0089;
        --wasom-dark: #cc006d;
        --sidebar-w: 220px;
        --nav-h: 60px;
        --bg: #f4f6fb;
        --card: #fff;
        --border: rgba(0, 0, 0, .07);
        --text: #1a1a2e;
        --muted: #6c757d;
    }

    [data-theme="dark"] {
        --bg: #0f0f1a;
        --card: #151521;
        --border: rgba(255, 255, 255, .08);
        --text: #e8e8f0;
        --muted: #9999bb;

    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        background: var(--bg);
        color: var(--text);
        font-family: 'Segoe UI', Arial, sans-serif;
        min-height: 100vh;
    }

    /* ── Top navbar ── */
    .collab-nav {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: var(--nav-h);
        z-index: 1000;
        background: linear-gradient(135deg, #FF0089, #FF4D4D);
        display: flex;
        align-items: center;
        padding: 0 20px;
        gap: 12px;
        box-shadow: 0 2px 16px rgba(255, 0, 137, .3);
    }

    .nav-brand {
        color: #fff;
        font-weight: 900;
        font-size: 1.25rem;
        text-decoration: none;
        letter-spacing: -.3px;
    }

    .nav-brand span {
        opacity: .7;
        font-weight: 400;
        font-size: .8rem;
        margin-left: 6px;
    }

    .nav-spacer {
        flex: 1;
    }

    .nav-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(255, 255, 255, .18);
        color: #fff;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: .72rem;
        font-weight: 700;
        border: 1px solid rgba(255, 255, 255, .25);
    }

    .nav-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, .4);
        object-fit: cover;
        background: rgba(255, 255, 255, .2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
        color: #fff;
        overflow: hidden;
        flex-shrink: 0;
    }

    .nav-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* ── Sidebar ── */
    .collab-sidebar {
        position: fixed;
        top: var(--nav-h);
        left: 0;
        bottom: 0;
        width: var(--sidebar-w);
        background: var(--card);
        border-right: 1.5px solid var(--border);
        overflow-y: auto;
        z-index: 900;
        padding: 20px 12px;
        transition: transform .3s;
    }

    @media(max-width:768px) {
        .collab-sidebar {
            transform: translateX(-100%);
        }

        .collab-sidebar.open {
            transform: translateX(0);
            box-shadow: 4px 0 24px rgba(0, 0, 0, .15);
        }

        .main-content {
            margin-left: 0 !important;
        }
    }

    .sidebar-section {
        font-size: .65rem;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .8px;
        font-weight: 700;
        padding: 12px 8px 4px;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 10px;
        border-radius: 10px;
        font-size: .84rem;
        font-weight: 500;
        color: var(--text);
        text-decoration: none;
        transition: all .15s;
        margin-bottom: 2px;
    }

    .sidebar-link:hover {
        background: rgba(255, 0, 137, .06);
        color: var(--wasom);
    }

    .sidebar-link.active {
        background: rgba(255, 0, 137, .1);
        color: var(--wasom);
        font-weight: 700;
    }

    .sidebar-link i {
        width: 18px;
        text-align: center;
        font-size: .9rem;
    }

    .sidebar-link .badge-count {
        margin-left: auto;
        font-size: .6rem;
        font-weight: 800;
        background: var(--wasom);
        color: #fff;
        padding: 1px 6px;
        border-radius: 10px;
    }

    /* ── Main content ── */
    .main-content {
        margin-left: var(--sidebar-w);
        padding: calc(var(--nav-h) + 24px) 24px 80px;
        min-height: 100vh;
    }

    /* ── Cards ── */
    .dash-card {
        background: var(--card);
        border-radius: 16px;
        border: 1.5px solid var(--border);
        padding: 20px;
        transition: border-color .2s, box-shadow .2s;
    }

    .dash-card:hover {
        border-color: rgba(255, 0, 137, .15);
        box-shadow: 0 4px 20px rgba(255, 0, 137, .07);
    }

    /* ── Stat cards ── */
    .stat-card {
        background: var(--card);
        border-radius: 16px;
        border: 1.5px solid var(--border);
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .stat-value {
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1;
    }

    .stat-label {
        font-size: .72rem;
        color: var(--muted);
        margin-top: 3px;
    }

    /* ── Access badge ── */
    .access-card {
        background: linear-gradient(135deg, rgba(255, 0, 137, .08), rgba(255, 77, 77, .06));
        border: 1.5px solid rgba(255, 0, 137, .2);
        border-radius: 16px;
        padding: 20px;
    }

    /* ── Album row ── */
    .album-row {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 0;
        border-bottom: 1px solid var(--border);
    }

    .album-row:last-child {
        border-bottom: none;
    }

    .album-cover {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        object-fit: cover;
        background: rgba(255, 0, 137, .07);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        overflow: hidden;
    }

    .album-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* ── Chip ── */
    .chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: .68rem;
        font-weight: 700;
    }

    /* ── Activity items ── */
    .act-item {
        display: flex;
        gap: 10px;
        padding: 9px 0;
        border-bottom: 1px solid var(--border);
        font-size: .82rem;
    }

    .act-item:last-child {
        border-bottom: none;
    }

    .act-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--wasom);
        flex-shrink: 0;
        margin-top: 5px;
    }

    /* ── Owner info card ── */
    .owner-card {
        background: linear-gradient(135deg, #FF0089, #FF4D4D);
        border-radius: 16px;
        padding: 20px;
        color: #fff;
        position: relative;
        overflow: hidden;
    }

    .owner-card::before {
        content: '🎵';
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 4rem;
        opacity: .15;
    }

    /* ── Locked section ── */
    .locked-section {
        border-radius: 16px;
        border: 1.5px dashed var(--border);
        padding: 32px;
        text-align: center;
        opacity: .6;
    }

    /* ── Bottom nav (mobile) ── */
    .bottom-nav-collab {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: var(--card);
        border-top: 1.5px solid var(--border);
        padding: 8px 0;
        z-index: 900;
    }

    @media(max-width:768px) {
        .bottom-nav-collab {
            display: flex;
        }

        .bottom-nav-collab a {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            font-size: .58rem;
            color: var(--muted);
            text-decoration: none;
            padding: 4px 0;
        }

        .bottom-nav-collab a.active,
        .bottom-nav-collab a:hover {
            color: var(--wasom);
        }

        .bottom-nav-collab i {
            font-size: 1.2rem;
        }
    }

    /* ── Overlay (mobile sidebar) ── */
    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .4);
        z-index: 850;
    }

    .sidebar-overlay.show {
        display: block;
    }

    /* ── Theme ── */
    .theme-btn {
        background: none;
        border: none;
        color: rgba(255, 255, 255, .8);
        font-size: 1.1rem;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 8px;
        transition: background .2s;
    }

    .theme-btn:hover {
        background: rgba(255, 255, 255, .15);
    }
    </style>
</head>

<body>

    <!-- ═══ NAVBAR ═══ -->
    <nav class="collab-nav">
        <button class="theme-btn d-md-none" id="btn-sidebar-toggle">
            <i class="bi bi-list"></i>
        </button>
        <a class="nav-brand" href="overview">
            <?php echo APP_NAME; ?>
            <span>For Collaborator</span>
        </a>
        <div class="nav-spacer"></div>

        <!-- Role chip -->
        <div class="nav-chip d-none d-md-inline-flex"
            style="background:<?php echo $rm['bg']; ?>;color:<?php echo $rm['color']; ?>;border-color:<?php echo $rm['color']; ?>20">
            <i class="bi <?php echo $rm['icon']; ?>"></i>
            <?php echo $role_label; ?>
        </div>

        <!-- Theme toggle -->
        <button class="theme-btn" id="themeToggle" title="Alternar tema">
            <i class="bi bi-sun" id="themeIcon"></i>
        </button>

        <!-- Avatar + dropdown -->
        <div class="dropdown">
            <button class="nav-avatar dropdown-toggle" style="background:none;border:none;cursor:pointer"
                data-bs-toggle="dropdown">
                <?php if ($collab['photo_collab']): ?>
                <img src="<?php echo htmlspecialchars($collab['photo_collab']); ?>" alt=""
                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                <span style="display:none"><i class="bi bi-person"></i></span>
                <?php else: ?>
                <span><i class="bi bi-person"></i></span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="font-size:.84rem;min-width:200px">
                <li class="px-3 py-2">
                    <div class="fw-bold">
                        <?php echo htmlspecialchars($collab['first_name'] . ' ' . ($collab['second_name'] ?? '')); ?>
                    </div>
                    <div class="text-reset" style="font-size:.72rem">
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
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#myProfileModal">
                        <i class="bi bi-person me-2"></i>O meu perfil
                    </a></li>
                <li>
                    <hr class="dropdown-divider" />
                </li>
                <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                        <i class="bi bi-box-arrow-right me-2"></i>Terminar sessão
                    </a></li>
            </ul>
        </div>
    </nav>

    <!-- ═══ SIDEBAR OVERLAY (mobile) ═══ -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="collab-sidebar" id="collabSidebar">

        <!-- Owner info -->
        <div class="owner-card mb-3">
            <div
                style="font-size:.65rem;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">
                Conta</div>
            <div class="fw-bold" style="font-size:.95rem"><?php echo $owner_artist_name; ?></div>
            <div style="font-size:.72rem;color:rgba(255,255,255,.75);margin-top:2px"><?php echo $plan_name; ?></div>
        </div>

        <div class="sidebar-section">Menu</div>

        <a href="" class="sidebar-link active">
            <i class="bi bi-speedometer2"></i>Dashboard
        </a>

        <?php if ($can_view_releases): ?>
        <a href="<?php echo rtrim(APP_URL, '/'); ?>/dashboard/releases" class="sidebar-link">
            <i class="bi bi-disc"></i>Lançamentos
            <?php if ((int)($album_stats['pending'] ?? 0) > 0): ?>
            <span class="badge-count"><?php echo $album_stats['pending']; ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <?php if ($can_view_artists): ?>
        <a href="<?php echo rtrim(APP_URL, '/'); ?>/dashboard/artists-list" class="sidebar-link">
            <i class="bi bi-people"></i>Artistas
        </a>
        <?php endif; ?>

        <?php if ($can_view_finances): ?>
        <div class="sidebar-section">Finanças</div>
        <a href="<?php echo rtrim(APP_URL, '/'); ?>/dashboard/overview" class="sidebar-link">
            <i class="bi bi-currency-dollar"></i>Visão geral
        </a>
        <?php endif; ?>

        <?php if ($can_view_stats): ?>
        <div class="sidebar-section">Análise</div>
        <a href="<?php echo rtrim(APP_URL, '/'); ?>/dashboard/statistics" class="sidebar-link">
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

        <!-- Page title -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h4 fw-bold mb-1">
                    Olá, <?php echo htmlspecialchars($collab['first_name']); ?>! <i class="bi bi-emoji-smile"></i>
                </h1>
                <p class="small mb-0">
                    Painel de colaboradores · <?php echo $owner_artist_name; ?>
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="chip" style="background:<?php echo $rm['bg']; ?>;color:<?php echo $rm['color']; ?>">
                    <i class="bi <?php echo $rm['icon']; ?>"></i><?php echo $role_label; ?>
                </span>
                <span class="chip" style="background:rgba(25,135,84,.1);color:#198754">
                    <span style="width:7px;height:7px;border-radius:50%;background:#198754;display:inline-block"></span>
                    Online
                </span>
            </div>
        </div>


        <!-- ── Access summary card ── -->
        <div class="access-card mb-4">
            <div class="d-flex align-items-start gap-3 flex-wrap">
                <div>
                    <div class="fw-bold small mb-1">
                        <i class="bi bi-shield-check me-1" style="color:var(--wasom)"></i>
                        As tuas permissões de acesso
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <?php
                        $perms = [
                            [$can_view_releases,  'Lançamentos',   'bi-disc'],
                            [$can_edit_releases,  'Editar releases', 'bi-pencil'],
                            [$can_view_artists,   'Artistas',      'bi-people'],
                            [$can_view_finances,  'Finanças',      'bi-currency-dollar'],
                            [$can_view_stats,     'Estatísticas',  'bi-bar-chart'],
                        ];
                        foreach ($perms as [$has, $label, $icon]):
                        ?>
                        <span class="chip" style="background:<?php echo $has ? 'rgba(25,135,84,.12)' : 'rgba(108,117,125,.1)'; ?>;
                                              color:<?php echo $has ? '#198754' : '#aaa'; ?>">
                            <i class="bi <?php echo $icon; ?>"></i>
                            <?php echo $label; ?>
                            <i class="bi <?php echo $has ? 'bi-check2' : 'bi-x'; ?>"></i>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>


        <!-- ── Stat cards ── -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(255,0,137,.1)">
                        <i class="bi bi-disc" style="color:var(--wasom)"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo (int)($album_stats['total'] ?? 0); ?></div>
                        <div class="stat-label">Lançamentos</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(25,135,84,.1)">
                        <i class="bi bi-check-circle" style="color:#198754"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo (int)($album_stats['approved'] ?? 0); ?></div>
                        <div class="stat-label">Aprovados</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(255,193,7,.1)">
                        <i class="bi bi-hourglass-split" style="color:#856404"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo (int)($album_stats['pending'] ?? 0); ?></div>
                        <div class="stat-label">Pendentes</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(13,110,253,.1)">
                        <i class="bi bi-people" style="color:#0d6efd"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $artist_count; ?></div>
                        <div class="stat-label">Artistas</div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row g-3">

            <!-- ── Coluna esquerda ── -->
            <div class="col-lg-8">

                <!-- Últimos lançamentos -->
                <?php if ($can_view_releases): ?>
                <div class="dash-card mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="fw-bold small d-flex align-items-center gap-2">
                            <i class="bi bi-disc" style="color:var(--wasom)"></i>
                            Últimos Lançamentos
                        </div>
                        <?php if ($can_edit_releases): ?>
                        <a href="<?php echo rtrim(APP_URL, '/'); ?>/dashboard/releases"
                            class="btn btn-sm px-3 fw-semibold"
                            style="background:var(--wasom);color:#fff;border-radius:20px;font-size:.75rem">
                            Ver todos
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($recent_albums)): ?>
                    <div class="text-center py-4">
                        <div style="font-size:2rem;opacity:.2;margin-bottom:8px">🎵</div>
                        <div class="text-muted small">Sem lançamentos ainda.</div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($recent_albums as $alb_row):
                                $as_m = $album_status_meta[$alb_row['status_album']] ?? $album_status_meta['draft'];
                            ?>
                    <div class="album-row">
                        <div class="album-cover">
                            <?php if ($alb_row['img_cover']): ?>
                            <img src="<?php echo htmlspecialchars($cover_base . $alb_row['img_cover']); ?>" alt=""
                                onerror="this.style.display='none';this.parentElement.textContent='🎵'" />
                            <?php else: ?>🎵
                            <?php endif; ?>
                        </div>
                        <div style="flex:1;min-width:0">
                            <div class="fw-semibold small text-truncate">
                                <?php echo htmlspecialchars($alb_row['title_album']); ?>
                            </div>
                            <div class="text-reset" style="font-size:.7rem">
                                <?php echo htmlspecialchars($alb_row['stage_name'] ?? '—'); ?>
                                · <?php echo ucfirst($alb_row['type_album']); ?>
                                · <?php echo date('d/m/Y', strtotime($alb_row['creat_album'])); ?>
                            </div>
                        </div>
                        <span class="chip"
                            style="background:<?php echo $as_m['bg']; ?>;color:<?php echo $as_m['color']; ?>">
                            <?php echo $as_m['label']; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="locked-section mb-3">
                    <div style="font-size:2rem;margin-bottom:8px"><i class="bi bi-lock"></i></div>
                    <div class="fw-semibold small">Sem acesso a Lançamentos</div>
                    <div class="text-reset" style="font-size:.75rem;margin-top:4px">
                        A tua função (<?php echo $role_label; ?>) não tem permissão para ver lançamentos.
                    </div>
                </div>
                <?php endif; ?>


                <!-- Finanças (só admin/analyst) -->
                <?php if ($can_view_finances && $wallet): ?>
                <div class="dash-card mb-3">
                    <div class="fw-bold small d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-currency-dollar" style="color:var(--wasom)"></i>
                        Resumo Financeiro
                        <span class="text-muted fw-normal" style="font-size:.7rem">(só leitura)</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="text-reset" style="font-size:.7rem">Saldo AOA</div>
                            <div class="fw-bold">
                                <?php echo number_format((float)$wallet['balance_aoa'], 2, ',', '.'); ?> Kz
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-reset" style="font-size:.7rem">Saldo USD</div>
                            <div class="fw-bold">
                                $<?php echo number_format((float)$wallet['balance_usd'], 2, ',', '.'); ?>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-reset" style="font-size:.7rem">Total ganho</div>
                            <div class="fw-bold">
                                Kz<?php echo number_format((float)$wallet['total_earned'], 2, ',', '.'); ?>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-reset" style="font-size:.7rem">Total sacado</div>
                            <div class="fw-bold">
                                <?php echo number_format((float)$wallet['total_withdrawn'], 2, ',', '.'); ?> Kz
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif (!$can_view_finances): ?>
                <div class="locked-section mb-3">
                    <div style="font-size:2rem;margin-bottom:8px">🔒</div>
                    <div class="fw-semibold small">Sem acesso a Finanças</div>
                    <div class="text-reset" style="font-size:.75rem;margin-top:4px">
                        Só Administradores e Analistas têm acesso aos dados financeiros.
                    </div>
                </div>
                <?php endif; ?>

            </div><!-- /col-lg-8 -->


            <!-- ── Coluna direita ── -->
            <div class="col-lg-4">

                <!-- Info da conta proprietária -->
                <div class="dash-card mb-3">
                    <div class="fw-bold small d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-building" style="color:var(--wasom)"></i>
                        Conta que geres
                    </div>
                    <div style="font-size:.83rem">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-reset">Artista / Banda</span>
                            <span class="fw-semibold"><?php echo $owner_artist_name; ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-reset">Proprietário</span>
                            <span class="fw-semibold"><?php echo $owner_name; ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-reset">Plano</span>
                            <span class="fw-semibold"><?php echo $plan_name; ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-reset">Lançamentos</span>
                            <span class="fw-semibold"><?php echo (int)($album_stats['total'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>

                <!-- As minhas actividades -->
                <div class="dash-card">
                    <div class="fw-bold small d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-clock-history" style="color:var(--wasom)"></i>
                        As minhas actividades
                    </div>
                    <?php if (empty($my_activities)): ?>
                    <div class="text-center py-3">
                        <div class="text-muted small">Sem actividades registadas.</div>
                    </div>
                    <?php else: ?>
                    <?php
                        $act_icons = [
                            'login'            => 'bi-box-arrow-in-right',
                            'logout'           => 'bi-box-arrow-right',
                            'login_failed'     => 'bi-exclamation-triangle',
                            'password_changed' => 'bi-key',
                            'account_activated' => 'bi-check-circle',
                        ];
                        foreach ($my_activities as $act):
                            $ico = $act_icons[$act['activity_type']] ?? 'bi-activity';
                            $dt  = date('d/m H:i', strtotime($act['creat_activity']));
                        ?>
                    <div class="act-item">
                        <div class="act-dot"></div>
                        <div style="min-width:0">
                            <div class="text-truncate">
                                <?php echo htmlspecialchars($act['description'] ?: $act['activity_type']); ?></div>
                            <div class="text-reset" style="font-size:.7rem"><?php echo $dt; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div><!-- /col-lg-4 -->
        </div><!-- /row -->

    </main><!-- /main-content -->


    <!-- ── Bottom nav (mobile) ── -->
    <nav class="bottom-nav-collab">
        <a href="" class="active"><i class="bi bi-speedometer2"></i>Dashboard</a>
        <?php if ($can_view_releases): ?>
        <a href="<?php echo rtrim(APP_URL, '/'); ?>/dashboard/releases">
            <i class="bi bi-disc"></i>Releases
        </a>
        <?php endif; ?>
        <?php if ($can_view_artists): ?>
        <a href="<?php echo rtrim(APP_URL, '/'); ?>/dashboard/artists-list">
            <i class="bi bi-people"></i>Artistas
        </a>
        <?php endif; ?>
        <?php if ($can_view_stats): ?>
        <a href="<?php echo rtrim(APP_URL, '/'); ?>/dashboard/statistics">
            <i class="bi bi-bar-chart"></i>Stats
        </a>
        <?php endif; ?>
        <a href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
            <i class="bi bi-box-arrow-right"></i>Sair
        </a>
    </nav>


    <!-- ═══ MODAL — O meu perfil ═══ -->
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
                            ['Email',       $collab['email_collab'],      'bi-envelope'],
                            ['Telefone',    $collab['tel_collab'] ?: '—', 'bi-telephone'],
                            ['Função',      $role_label,                   'bi-person-badge'],
                            ['Membro desde', date('d/m/Y', strtotime($collab['creat_collab'])), 'bi-calendar3'],
                            ['Último login', $collab['last_login_at'] ? date('d/m/Y H:i', strtotime($collab['last_login_at'])) : '—', 'bi-clock'],
                        ];
                        foreach ($info_rows as [$label, $val, $ico]):
                        ?>
                        <div class="d-flex gap-2 py-2 border-bottom align-items-center">
                            <i class="bi <?php echo $ico; ?> text-muted" style="width:16px"></i>
                            <span class="text-reset" style="width:100px;flex-shrink:0"><?php echo $label; ?></span>
                            <span class="fw-semibold text-truncate"><?php echo htmlspecialchars($val); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($collab['notes']): ?>
                    <div class="mt-3 p-3"
                        style="background:rgba(255,0,137,.04);border-radius:10px;border:1px solid rgba(255,0,137,.1)">
                        <div class="text-reset" style="font-size:.7rem;margin-bottom:4px">NOTAS DO ADMINISTRADOR</div>
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


    <!-- ═══ MODAL — Logout ═══ -->
    <div class="modal fade" id="logoutModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-1">
                    <h5 class="modal-title">Terminar sessão?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    <p class="small mb-0">
                        Vais sair do painel de colaboradores. Podes entrar novamente através do link que recebeste por
                        email.
                    </p>
                </div>
                <div class="modal-footer border-0 gap-2 pt-1">
                    <button class="btn btn-outline-secondary btn-sm flex-fill"
                        data-bs-dismiss="modal">Continuar</button>
                    <a href="<?php echo htmlspecialchars($logout_url); ?>" class="btn btn-danger btn-sm flex-fill">
                        <i class="bi bi-box-arrow-right me-1"></i>Terminar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
    // ── Sidebar toggle (mobile) ────────────────────
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

    // ── Theme toggle ───────────────────────────────
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

    // ── Ping last_seen every 2 min ─────────────────
    setInterval(() => {
        fetch('<?php echo rtrim(APP_URL, "/") . "/dashboard/collab/ping"; ?>', {
            method: 'POST',
            body: (() => {
                const f = new FormData();
                return f;
            })()
        }).catch(() => {});
    }, 120000);
    </script>
</body>

</html>