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
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF2D66" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <link rel="manifest" href="../../dashboard/manifest.json" />
    <title>Painel — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="css/collab.css" />
    <style>
    /* ── Overview: Access badge ── */
    .access-card {
        background: linear-gradient(135deg, rgba(255, 0, 137, .08), rgba(255, 77, 77, .06));
        border: 1.5px solid rgba(255, 0, 137, .2);
        border-radius: 16px;
        padding: 20px;
    }

    /* ── Overview: Album row ── */
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

    /* ── Overview: Activity ── */
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

    /* ── Overview: Locked ── */
    .locked-section {
        border-radius: 16px;
        border: 1.5px dashed var(--border);
        padding: 32px;
        text-align: center;
        opacity: .6;
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

        <!-- Page title -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h4 fw-bold mb-1"><i class="bi bi-emoji-smile" style="color:var(--wasom)"></i>
                    Olá, <?php echo htmlspecialchars($collab['first_name']); ?>!
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
                        <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/collab/releases"
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
                    <div style="font-size:2rem;margin-bottom:8px"> <i class="bi bi-"> </i> </div>
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

    <!-- Bottom nav -->
    <?php require_once __DIR__ . '/include/navbar-bottom.php'; ?>

    <?php require_once __DIR__ . '/include/modallogoutmyprofile.php'; ?>

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