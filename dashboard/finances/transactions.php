<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Divisão de Royalties
// Arquivo: dashboard/finances/transactions.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];
$user     = getUserById($id_users);
if (!$user) {
    redirect('authentic/logout');
}

// ── Plano ──────────────────────────────────────
$plan = null;
if ($user['plan_selected']) {
    $ps = $db->prepare("SELECT * FROM _plans WHERE id_plan = ?");
    $ps->execute([$user['plan_selected']]);
    $plan = $ps->fetch();
}
$plan_name = $plan ? htmlspecialchars($plan['name_plan']) : 'Sem plano';

// ── Artistas do utilizador ─────────────────────
$artists_q = $db->prepare("
    SELECT id_artist, stage_name, photo_artist, status_artist
    FROM _artist WHERE id_users = ? AND status_artist != 'blocked'
    ORDER BY stage_name ASC
");
$artists_q->execute([$id_users]);
$artists = $artists_q->fetchAll(PDO::FETCH_ASSOC);

// ── Splits agrupados por artista ───────────────
$splits_q = $db->prepare("
    SELECT ac.*, a.stage_name AS artist_name, a.photo_artist
    FROM _artist_collaborator ac
    JOIN _artist a ON a.id_artist = ac.id_artist
    WHERE a.id_users = ?
    ORDER BY ac.id_artist ASC, ac.royalty_share DESC
");
$splits_q->execute([$id_users]);
$all_splits = $splits_q->fetchAll(PDO::FETCH_ASSOC);

$splits_by_artist = [];
foreach ($all_splits as $s) {
    $splits_by_artist[$s['id_artist']][] = $s;
}
$total_pct_by_artist = [];
foreach ($splits_by_artist as $aid => $splits) {
    $total_pct_by_artist[$aid] = array_sum(array_column($splits, 'royalty_share'));
}

// ── Feedback ───────────────────────────────────
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
$errs = [
    'noartist'  => 'Artista não encontrado ou sem permissão.',
    'over100'   => 'A soma das percentagens excede 100%. Verifique os valores.',
    'sameemail' => 'Não podes dividir royalties com a tua própria conta.',
    'dupli'     => 'Já existe uma divisão com este colaborador para este artista.',
    'notfound'  => 'Divisão não encontrada.',
    'invalid'   => 'Dados inválidos. Verifica o formulário e tenta novamente.',
];

// ── Helpers ────────────────────────────────────
$user_artist_name = htmlspecialchars($user['name_artist_band'] ?? $user['first_name']);
$base_url         = rtrim(APP_URL, '/');
$cover_url        = $base_url . '/assets/comprovantes/uploads/artists/';

$role_labels = [
    'feat'      => 'Featuring',
    'producer'  => 'Produtor',
    'composer'  => 'Compositor',
    'lyricist'  => 'Letrista',
    'manager'   => 'Manager',
    'label'     => 'Editora',
    'other'     => 'Outro',
];
$role_colors = [
    'feat'      => ['bg' => 'rgba(255,0,137,.1)',  'color' => '#FF0089'],
    'producer'  => ['bg' => 'rgba(13,110,253,.1)', 'color' => '#0d6efd'],
    'composer'  => ['bg' => 'rgba(25,135,84,.1)',  'color' => '#198754'],
    'lyricist'  => ['bg' => 'rgba(255,193,7,.12)', 'color' => '#856404'],
    'manager'   => ['bg' => 'rgba(108,117,125,.1)', 'color' => '#6c757d'],
    'label'     => ['bg' => 'rgba(111,66,193,.1)', 'color' => '#6f42c1'],
    'other'     => ['bg' => 'rgba(108,117,125,.1)', 'color' => '#6c757d'],
];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png" />
    <link rel="manifest" href="../manifest.json" />
    <title>Divisão de Royalties — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <link rel="stylesheet" href="../../css/dashboard-style.css" />
    <link rel="stylesheet" href="../../css/lastest-style.css" />
    <style>
        .split-artist-card {
            background: var(--card-bg, #fff);
            border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 24px;
            transition: box-shadow .2s;
        }

        .split-artist-card:hover {
            box-shadow: 0 6px 32px rgba(255, 0, 137, .08);
        }

        .artist-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px 24px;
            border-bottom: 1.5px solid var(--border-color, rgba(0, 0, 0, .07));
            background: linear-gradient(135deg, rgba(255, 0, 137, .03), transparent);
        }

        .artist-avatar {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            object-fit: cover;
            flex-shrink: 0;
            background: rgba(255, 0, 137, .08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            overflow: hidden;
        }

        .artist-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .royalty-bar-wrap {
            padding: 16px 24px;
        }

        .royalty-bar-label {
            display: flex;
            justify-content: space-between;
            font-size: .75rem;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .royalty-bar {
            height: 8px;
            border-radius: 10px;
            background: var(--border-color, rgba(0, 0, 0, .07));
            overflow: hidden;
        }

        .royalty-bar-fill {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, #FF0089, #FF4D4D);
            transition: width .5s ease;
        }

        .royalty-bar-fill.over {
            background: linear-gradient(90deg, #dc3545, #ff6b6b);
        }

        .beneficiary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .84rem;
        }

        .beneficiary-table th {
            font-size: .68rem;
            font-weight: 700;
            color: var(--text-muted, #6c757d);
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 8px 24px;
            border-bottom: 1.5px solid var(--border-color, rgba(0, 0, 0, .07));
            white-space: nowrap;
        }

        .beneficiary-table td {
            padding: 12px 24px;
            border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .05));
            vertical-align: middle;
        }

        .beneficiary-table tr:last-child td {
            border-bottom: none;
        }

        .beneficiary-table tr:hover td {
            background: rgba(255, 0, 137, .02);
        }

        .role-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .67rem;
            font-weight: 700;
        }

        .pct-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255, 0, 137, .08);
            font-size: .85rem;
            font-weight: 800;
            color: #FF0089;
            border: 2px solid rgba(255, 0, 137, .15);
        }

        .btn-split-del {
            background: none;
            border: none;
            color: #dc3545;
            font-size: .85rem;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 8px;
            transition: background .15s;
        }

        .btn-split-del:hover {
            background: rgba(220, 53, 69, .08);
        }

        .empty-splits {
            text-align: center;
            padding: 32px 24px;
            color: var(--text-muted, #6c757d);
        }

        .empty-splits .icon {
            font-size: 2.5rem;
            opacity: .18;
            margin-bottom: 8px;
        }

        .pct-remaining-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 700;
            background: rgba(25, 135, 84, .1);
            color: #198754;
            border: 1.5px solid rgba(25, 135, 84, .2);
        }

        .pct-remaining-badge.warn {
            background: rgba(255, 193, 7, .1);
            color: #856404;
            border-color: rgba(255, 193, 7, .3);
        }

        .pct-remaining-badge.danger {
            background: rgba(220, 53, 69, .08);
            color: #dc3545;
            border-color: rgba(220, 53, 69, .2);
        }

        .finances-hero {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 20px;
            padding: 28px 32px;
            color: #fff;
            position: relative;
            overflow: hidden;
            margin-bottom: 28px;
        }

        .finances-hero::after {
            content: '';
            position: absolute;
            right: -60px;
            top: -60px;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 0, 137, .18), transparent 70%);
        }

        .finances-hero::before {
            content: '';
            position: absolute;
            left: -40px;
            bottom: -40px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 77, 77, .1), transparent 70%);
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu">
                <span class="navbar-toggler-icon"><i class="bi bi-list text-white fs-1"></i></span>
            </button>
            <a class="navbar-brand" href="../painel">
                <span class="text-light" style="font-weight:bold;font-family:Arial,sans-serif">WASOM UPFY</span>
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav m-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i>
                            Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i>
                            Lançamentos</a></li>
                    <li class="nav-item"><a class="nav-link" href="../analytics/statistics"><i
                                class="bi bi-bar-chart"></i> Estatísticas</a></li>
                    <li class="nav-item"><a class="nav-link active" href="../finances/overview"><i
                                class="bi bi-currency-dollar"></i> Finanças</a></li>
                    <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i>
                            Artistas</a></li>
                </ul>
            </div>
            <div class="user-menu d-flex align-items-center">
                <a class="theme-toggle text-white me-2" id="themeToggle"><i class="bi bi-sun" id="themeIcon"></i></a>
                <a href="../notifications" class="text-white me-2"><i class="bi bi-bell fs-4"></i></a>
                <a href="#" class="text-white" data-bs-toggle="dropdown"><i class="bi bi-person-circle fs-4"></i></a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="../user/profile"><i
                                class="bi bi-person me-2"></i><strong><?php echo $user_artist_name; ?></strong></a></li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li><a class="dropdown-item" href="../user/profile"><i class="bi bi-person me-2"></i> Meu Perfil</a>
                    </li>
                    <li><a class="dropdown-item" href="../account/manage-account"><i class="bi bi-tools me-2"></i>
                            Gestão de Conta</a></li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li><a class="dropdown-item" href="../page/settings"><i class="bi bi-gear me-2"></i>
                            Configurações</a></li>
                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                            data-bs-target="#logoutwasomupfy">
                            <i class="bi bi-box-arrow-right me-2"></i> Desconectar-se</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Offcanvas Mobile -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title text-light" style="font-weight:bold;font-family:Arial,sans-serif">WASOM UPFY</h5>
            <button type="button" class="btn-close text-white" data-bs-dismiss="offcanvas"><i
                    class="bi bi-x-lg"></i></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i>
                        Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i>
                        Lançamentos</a></li>
                <li class="nav-item"><a class="nav-link" href="../analytics/statistics"><i class="bi bi-bar-chart"></i>
                        Estatísticas</a></li>
                <li class="nav-item"><a class="nav-link active" href="../finances/overview"><i
                            class="bi bi-currency-dollar"></i> Finanças</a></li>
                <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i>
                        Artistas</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link" href="../user/profile"><i
                            class="bi bi-person-circle"></i> Meu Perfil</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link" href="../page/settings"><i class="bi bi-gear"></i>
                        Configurações</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link text-danger" href="#" data-bs-toggle="modal"
                        data-bs-target="#logoutwasomupfy">
                        <i class="bi bi-box-arrow-right"></i> Desconectar-se</a></li>
            </ul>
        </div>
    </div>

    <!-- Main -->
    <div class="container my-4">

        <!-- Hero -->
        <div class="finances-hero">
            <div class="row align-items-center" style="position:relative;z-index:1">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb" style="margin-bottom:8px">
                        <ol class="breadcrumb mb-0" style="font-size:.75rem;opacity:.6">
                            <li class="breadcrumb-item"><a href="../painel"
                                    class="text-white text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="overview"
                                    class="text-white text-decoration-none">Finanças</a></li>
                            <li class="breadcrumb-item active text-white">Divisão de Royalties</li>
                        </ol>
                    </nav>
                    <h1 class="fw-bold mb-1" style="font-size:1.6rem">
                        <i class="bi bi-pie-chart-fill me-2" style="color:#FF0089"></i>Divisão de Royalties
                    </h1>
                    <p class="mb-0" style="font-size:.88rem;opacity:.7">
                        Gere a partilha de royalties com produtores, compositores, featurings e colaboradores.
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <?php if (!empty($artists)): ?>
                        <button class="btn btn-sm fw-bold"
                            style="background:#FF0089;color:#fff;border:none;border-radius:20px;padding:10px 24px"
                            data-bs-toggle="modal" data-bs-target="#modalNewSplit">
                            <i class="bi bi-plus me-1"></i>Nova divisão
                        </button>
                    <?php endif; ?>
                    <a href="overview" class="btn btn-sm ms-2"
                        style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:20px">
                        <i class="bi bi-arrow-left me-1"></i>Voltar
                    </a>
                </div>
            </div>
        </div>

        <!-- Alertas -->
        <?php if ($success === 'created'): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="border-radius:14px">
                <i class="bi bi-check-circle-fill"></i> Divisão criada com sucesso.
                <button class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($success === 'deleted'): ?>
            <div class="alert alert-info d-flex align-items-center gap-2 mb-4" style="border-radius:14px">
                <i class="bi bi-info-circle-fill"></i> Divisão removida.
                <button class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif (!empty($error) && isset($errs[$error])): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius:14px">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($errs[$error]); ?>
                <button class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Sem artistas -->
        <?php if (empty($artists)): ?>
            <div class="split-artist-card text-center p-5">
                <div style="font-size:3rem;opacity:.2;margin-bottom:12px">🎤</div>
                <h5 class="fw-bold">Nenhum artista encontrado</h5>
                <p class="text-muted small mb-4">Precisas de ter pelo menos um artista registado para criar divisões de
                    royalties.</p>
                <a href="../artists/add-artist" class="btn btn-sm fw-bold px-4"
                    style="background:#FF0089;color:#fff;border:none;border-radius:20px">
                    <i class="bi bi-plus me-1"></i>Adicionar artista
                </a>
            </div>

        <?php else: ?>

            <!-- Cards por artista -->
            <?php foreach ($artists as $art):
                $aid     = $art['id_artist'];
                $splits  = $splits_by_artist[$aid] ?? [];
                $used    = (float)($total_pct_by_artist[$aid] ?? 0);
                $free    = max(0.0, 100.0 - $used);
                $bar_pct = min(100, $used);
            ?>
                <div class="split-artist-card" id="artist-card-<?php echo $aid; ?>">

                    <!-- Header artista -->
                    <div class="artist-header">
                        <div class="artist-avatar">
                            <?php if ($art['photo_artist']): ?>
                                <img src="<?php echo htmlspecialchars($cover_url . $art['photo_artist']); ?>"
                                    onerror="this.parentElement.innerHTML='🎤'" alt="" />
                                <?php else: ?>🎤<?php endif; ?>
                        </div>
                        <div style="flex:1;min-width:0">
                            <div class="fw-bold" style="font-size:.97rem"><?php echo htmlspecialchars($art['stage_name']); ?>
                            </div>
                            <div class="text-muted" style="font-size:.75rem">
                                <?php echo count($splits); ?> colaborador<?php echo count($splits) !== 1 ? 'es' : ''; ?>
                                &nbsp;·&nbsp;
                                <?php if ($used > 100): ?>
                                    <span style="color:#dc3545;font-weight:600">Excede 100% — revê os valores</span>
                                <?php elseif ($used >= 100): ?>
                                    <span style="color:#856404;font-weight:600">100% distribuído</span>
                                <?php else: ?>
                                    <span style="color:#198754;font-weight:600"><?php echo number_format($free, 1); ?>%
                                        disponível</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button class="btn btn-sm"
                            style="background:rgba(255,0,137,.08);color:#FF0089;border:1px solid rgba(255,0,137,.2);border-radius:10px;font-size:.75rem;font-weight:700;flex-shrink:0"
                            onclick="openSplitModal(<?php echo $aid; ?>, '<?php echo addslashes(htmlspecialchars($art['stage_name'])); ?>', <?php echo number_format($free, 2, '.', ''); ?>)">
                            <i class="bi bi-plus me-1"></i>Adicionar
                        </button>
                    </div>

                    <!-- Barra progresso -->
                    <div class="royalty-bar-wrap">
                        <div class="royalty-bar-label">
                            <span class="text-muted" style="font-size:.72rem">Royalties distribuídos</span>
                            <span
                                style="font-weight:800;font-size:.82rem;color:<?php echo $used > 100 ? '#dc3545' : ($used >= 100 ? '#856404' : '#FF0089'); ?>">
                                <?php echo number_format($used, 1); ?>%
                            </span>
                        </div>
                        <div class="royalty-bar">
                            <div class="royalty-bar-fill <?php echo $used > 100 ? 'over' : ''; ?>"
                                style="width:<?php echo $bar_pct; ?>%"></div>
                        </div>
                    </div>

                    <!-- Tabela beneficiários -->
                    <?php if (empty($splits)): ?>
                        <div class="empty-splits">
                            <div class="icon">🤝</div>
                            <div class="small">Nenhuma divisão criada para este artista.</div>
                            <div class="text-muted" style="font-size:.72rem;margin-top:4px">Clica em "Adicionar" para criar a
                                primeira divisão.</div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="beneficiary-table">
                                <thead>
                                    <tr>
                                        <th>Colaborador</th>
                                        <th>Função</th>
                                        <th>Conta Wasom</th>
                                        <th class="text-center">%</th>
                                        <th class="text-center">Acções</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($splits as $sp):
                                        $rc = $role_colors[$sp['role_collab']] ?? $role_colors['other'];
                                        $rl = $role_labels[$sp['role_collab']] ?? 'Outro';
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($sp['name_collab']); ?></div>
                                                <?php if ($sp['email_collab']): ?>
                                                    <div class="text-muted" style="font-size:.72rem">
                                                        <?php echo htmlspecialchars($sp['email_collab']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="role-chip"
                                                    style="background:<?php echo $rc['bg']; ?>;color:<?php echo $rc['color']; ?>">
                                                    <?php echo $rl; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($sp['id_users']): ?>
                                                    <span class="role-chip" style="background:rgba(25,135,84,.1);color:#198754">
                                                        <i class="bi bi-check-circle-fill" style="font-size:.7rem"></i> Verificado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="role-chip" style="background:rgba(108,117,125,.08);color:#6c757d">
                                                        <i class="bi bi-dash-circle" style="font-size:.7rem"></i> Externo
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="pct-badge"><?php echo number_format((float)$sp['royalty_share'], 1); ?>%
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn-split-del" title="Remover"
                                                    onclick="confirmDelete(<?php echo (int)$sp['id_collab']; ?>, <?php echo $aid; ?>, '<?php echo addslashes(htmlspecialchars($sp['name_collab'])); ?>')">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div><!-- /container -->

    <!-- Bottom Nav Mobile -->
    <nav class="bottom-nav d-lg-none">
        <ul class="nav justify-content-around">
            <li class="nav-item"><a class="nav-link" href="../painel"><i
                        class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../launch/releases"><i
                        class="bi bi-disc"></i><span>Lançamentos</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../analytics/statistics"><i
                        class="bi bi-bar-chart"></i><span>Stats</span></a></li>
            <li class="nav-item"><a class="nav-link active" href="../finances/overview"><i
                        class="bi bi-currency-dollar"></i><span>Finanças</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i
                        class="bi bi-person"></i><span>Artistas</span></a></li>
        </ul>
    </nav>


    <!-- ═══ MODAL — Nova divisão ═══ -->
    <div class="modal fade" id="modalNewSplit" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius:20px;border:none">
                <div class="modal-header border-0 pb-0" style="padding:24px 28px 0">
                    <div>
                        <h5 class="fw-bold mb-0"><i class="bi bi-pie-chart me-2" style="color:#FF0089"></i>Nova divisão
                            de royalties</h5>
                        <p class="text-muted small mb-0 mt-1">Define como os royalties serão partilhados com um
                            colaborador deste artista.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:24px 28px">
                    <form method="POST" action="split_process.php" id="formNewSplit" novalidate>
                        <input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                        <input type="hidden" name="action" value="create" />
                        <input type="hidden" name="honeypot" value="" />

                        <!-- Artista -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Artista <span
                                    class="text-danger">*</span></label>
                            <select name="id_artist" id="selectArtist" class="form-select" required
                                onchange="updateRemainingPct(this.value)">
                                <option value="">— Selecciona um artista —</option>
                                <?php foreach ($artists as $a): ?>
                                    <option value="<?php echo $a['id_artist']; ?>"
                                        data-free="<?php echo number_format(max(0, 100 - ($total_pct_by_artist[$a['id_artist']] ?? 0)), 2, '.', ''); ?>">
                                        <?php echo htmlspecialchars($a['stage_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Selecciona um artista.</div>
                            <div class="mt-2" id="pctRemainingWrap" style="display:none">
                                <span class="pct-remaining-badge" id="pctRemainingBadge">
                                    <i class="bi bi-pie-chart-fill"></i>
                                    <span id="pctRemainingVal">100</span>% disponível para distribuir
                                </span>
                            </div>
                        </div>

                        <hr style="border-color:rgba(0,0,0,.07);margin:0 -4px 20px" />

                        <!-- Dados do colaborador -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Nome do colaborador <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name_collab" class="form-control" maxlength="150"
                                    placeholder="Ex: DJ Calvo, Studio X, …" required />
                                <div class="invalid-feedback">Insere o nome do colaborador.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Função <span
                                        class="text-danger">*</span></label>
                                <select name="role_collab" class="form-select" required>
                                    <option value="">— Selecciona —</option>
                                    <?php foreach ($role_labels as $val => $lbl): ?>
                                        <option value="<?php echo $val; ?>"><?php echo $lbl; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Selecciona uma função.</div>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold small">E-mail da conta Wasom Upfy <span
                                        class="text-muted">(opcional)</span></label>
                                <input type="email" name="email_collab" class="form-control" maxlength="255"
                                    placeholder="conta@exemplo.com" />
                                <div class="form-text"><i class="bi bi-info-circle me-1"></i>
                                    Se o colaborador tiver conta na plataforma, introduz o e-mail para que receba os
                                    royalties directamente.
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Percentagem (%) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="royalty_share" id="inputPct" class="form-control"
                                        min="0.1" max="100" step="0.1" placeholder="0.0" required />
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="invalid-feedback">Insere uma percentagem válida (0.1–100).</div>
                            </div>
                        </div>

                        <!-- Aviso -->
                        <div class="p-3 mt-2"
                            style="background:rgba(255,193,7,.07);border-radius:12px;border:1px solid rgba(255,193,7,.25)">
                            <div class="d-flex gap-2 align-items-start">
                                <i class="bi bi-exclamation-triangle-fill text-warning mt-1" style="flex-shrink:0"></i>
                                <div style="font-size:.78rem;color:#856404">
                                    <strong>Atenção:</strong> Ao criar esta divisão confirmas que concordas em partilhar
                                    os royalties gerados por este artista com o colaborador indicado, na percentagem
                                    definida. Esta acção reflecte-se nos relatórios financeiros mensais.
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0" style="padding:0 28px 24px;gap:10px">
                    <button class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formNewSplit" class="btn fw-bold flex-fill"
                        style="background:#FF0089;color:#fff;border:none;border-radius:10px">
                        <i class="bi bi-check-lg me-1"></i>Criar divisão
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- ═══ MODAL — Confirmar delete ═══ -->
    <div class="modal fade" id="modalDeleteSplit" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:380px">
            <div class="modal-content" style="border-radius:18px;border:none">
                <div class="modal-header border-0 pb-0" style="padding:22px 24px 0">
                    <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-trash3 me-2"></i>Remover divisão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:16px 24px">
                    <p class="text-muted small mb-0">
                        Tens a certeza que queres remover a divisão de royalties de
                        <strong id="deleteCollabName">—</strong>?
                        Esta acção não pode ser desfeita.
                    </p>
                </div>
                <div class="modal-footer border-0" style="padding:0 24px 22px;gap:10px">
                    <button class="btn btn-outline-secondary flex-fill btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="split_process.php" style="flex:1">
                        <input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                        <input type="hidden" name="action" value="delete" />
                        <input type="hidden" name="id_collab" id="deleteCollabId" value="" />
                        <input type="hidden" name="id_artist" id="deleteArtistId" value="" />
                        <button type="submit" class="btn btn-danger w-100 btn-sm fw-bold">Sim, remover</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- ═══ MODAL — Logout ═══ -->
    <div class="modal fade" id="logoutwasomupfy" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">Terminar sessão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center text-dark">
                    <p>Tens a certeza de que desejas terminar sessão?</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" data-bs-dismiss="modal">Não, continuar</button>
                    <a href="../logout" class="btn btn-danger">Sim, terminar sessão</a>
                </div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="../../js/theme.wp.js"></script>
    <script>
        // Actualizar % disponível ao mudar artista
        function updateRemainingPct(artistId) {
            const sel = document.getElementById('selectArtist');
            const opt = sel.options[sel.selectedIndex];
            const free = parseFloat(opt?.dataset?.free ?? 100);
            const wrap = document.getElementById('pctRemainingWrap');
            const badge = document.getElementById('pctRemainingBadge');
            const val = document.getElementById('pctRemainingVal');
            const inp = document.getElementById('inputPct');

            if (!artistId) {
                wrap.style.display = 'none';
                return;
            }

            wrap.style.display = 'block';
            val.textContent = free.toFixed(1);
            badge.className = 'pct-remaining-badge' + (free <= 0 ? ' danger' : free < 20 ? ' warn' : '');
            inp.max = free > 0 ? free : 0;
            inp.disabled = free <= 0;
            inp.placeholder = free <= 0 ? 'Sem % disponível' : '0.0';
        }

        // Abrir modal pré-seleccionado para artista específico
        function openSplitModal(artistId, artistName, free) {
            const sel = document.getElementById('selectArtist');
            sel.value = artistId;
            updateRemainingPct(artistId);
            new bootstrap.Modal(document.getElementById('modalNewSplit')).show();
        }

        // Confirmar delete
        function confirmDelete(collabId, artistId, name) {
            document.getElementById('deleteCollabId').value = collabId;
            document.getElementById('deleteArtistId').value = artistId;
            document.getElementById('deleteCollabName').textContent = name;
            new bootstrap.Modal(document.getElementById('modalDeleteSplit')).show();
        }

        // Validação do form
        document.getElementById('formNewSplit').addEventListener('submit', function(e) {
            const sel = document.getElementById('selectArtist');
            const pct = document.getElementById('inputPct');
            const pctVal = parseFloat(pct.value);
            let ok = true;

            sel.classList.toggle('is-invalid', !sel.value);
            if (!sel.value) ok = false;

            const pctOk = pct.value && !isNaN(pctVal) && pctVal >= 0.1 && pctVal <= parseFloat(pct.max || 100);
            pct.classList.toggle('is-invalid', !pctOk);
            if (!pctOk) ok = false;

            if (!this.checkValidity()) ok = false;
            this.classList.add('was-validated');
            if (!ok) e.preventDefault();
        });

        // Toastr feedback
        <?php if ($success === 'created'): ?>
            toastr.success('Divisão criada com sucesso!', '', {
                timeOut: 4000,
                positionClass: 'toast-top-right'
            });
        <?php elseif ($success === 'deleted'): ?>
            toastr.info('Divisão removida.', '', {
                timeOut: 3000,
                positionClass: 'toast-top-right'
            });
        <?php elseif (!empty($error) && isset($errs[$error])): ?>
            toastr.error('<?php echo addslashes($errs[$error]); ?>', 'Erro', {
                timeOut: 5000,
                positionClass: 'toast-top-right'
            });
        <?php endif; ?>
    </script>
</body>

</html>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png">
    <link rel="apple-touch-startup-image" href="../../assets/img/screenshots/splash.png">
    <link rel="manifest" href="../manifest.json">
    <title>Conta de saque — Wasom Upfy</title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon">
    <link href="
https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css
" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Select2 Bootstrap 5 Theme (opcional, para melhor integração) -->
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    <link rel="stylesheet" href="../../css/dashboard-style.css">
    <link rel="stylesheet" href="../../css/lastest-style.css">
</head>
<!-- javacript para habilitar o form de ID-->
<script type="text/javascript">
    function other_link1() {
        if (link2.style.display = 'none');
        link1.style.display = 'block';

    }
</script>
<script type="text/javascript">
    function other_link() {
        if (link1.style.display = 'none');
        link2.style.display = 'block';
    }
</script>

<style>
    #link2 {
        display: none;
    }
</style>

<body>

    <!-- Tela de Carregamento -->
    <!-- <div class="loading-screen" id="loadingScreen">
        <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg" class="loading-logo">
            <rect x="2" y="2" width="116" height="36" rx="5" fill="none" stroke="#ff0089" stroke-width="2"/>
            <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold" fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
        </svg>
        <div class="spinner"></div>
    </div> -->

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <!-- Menu Button (Left) -->
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu"
                aria-controls="offcanvasMenu">
                <span class="navbar-toggler-icon"><i class="bi bi-list text-white fs-1"></i></span>
            </button>

            <!-- Logo (Center on Mobile, Left on Desktop) -->
            <a class="navbar-brand" href="../painel">
                <!-- SVG Logo Wasom Upfy -->
                <!-- <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="116" height="36" rx="5" fill="none" stroke="#ff0089" stroke-width="2" />
                    <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold"
                        fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
                </svg> -->
                <span class="text-light"
                    style="font-weight: bold; box-sizing: border-box; text-transform: capitalize; font-family:Arial, sans-serif">WASOM
                    UPFY</span>
            </a>

            <!-- Desktop Menu -->
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav m-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i>
                            Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i>
                            Lançamentos</a></li>
                    <li class="nav-item"><a class="nav-link" href="../analytics/statistics"><i
                                class="bi bi-bar-chart"></i>
                            Estatísticas</a></li>
                    <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
                                class="bi bi-currency-dollar"></i>
                            Finanças</a></li>
                    <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i>
                            Artistas</a></li>
                    <li class="nav-item"><a class="nav-link" href="../artists/youtube/ucy"><i class="bi bi-youtube"></i>
                            Unificação de canal YouTube</a></li>
                </ul>
            </div>

            <!-- User Icon (Right) -->
            <div class="user-menu d-flex align-items-center">
                <!-- Theme Toggle Button -->
                <a class="theme-toggle text-white me-2" id="themeToggle">
                    <i class="bi bi-sun" id="themeIcon"></i>
                </a>
                <a href="../notifications" class="text-white me-2" aria-label="Notificações">
                    <i class="bi bi-bell fs-4"></i>
                    <span class="badge bg-danger">9</span>
                </a>
                <a href="#" class="text-white" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-4"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="../user/profile"><i class="bi bi-person me-2"></i>
                            <strong>Eleven
                                Records</strong></a>
                        <div class="text-white-50"> &nbsp; &nbsp; &nbsp;
                            &nbsp;
                            (Conta <?php echo str_pad($id_users, 6, "0", STR_PAD_LEFT); ?>)</div>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../user/profile"><i class="bi bi-person me-2"></i> Meu
                            Perfil</a>
                    </li>
                    <li><a class="dropdown-item" href="../account/manage-account"><i class="bi bi-tools me-2"></i>
                            Gestão de Conta</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../page/settings"><i class="bi bi-gear me-2"></i>
                            Configurações</a></li>
                    <li><a class="dropdown-item" href="../page/notifications"><i class="bi bi-bell me-2"></i>
                            Notificações</a></li>
                    <li><a class="dropdown-item" href="../services/available-services"><i class="bi bi-star me-2"></i>
                            Conta e
                            serviços disponíveis</a></li>
                    <li><a class="dropdown-item" href="#?logout-wasomupfy" data-bs-toggle="modal"
                            data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right me-2"></i>
                            Desconectar-se</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../page/about"><i class="bi bi-info-circle me-2"></i>
                            Sobre</a>
                    </li>
                    <li><a class="dropdown-item" href="../page/support"><i class="bi bi-headset me-2"></i> Enviar
                            pedido de suporte</a></li>
                    <li><a class="dropdown-item" href="../page/faq"><i class="bi bi-chat-left-text me-2"></i>
                            Perguntas frequentes</a></li>
                    <li><a class="dropdown-item" href="../page/help"><i class="bi bi-question-circle me-2"></i>
                            Ajuda</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><span class="dropdown-item-text" id="versionDropdown"></span></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Offcanvas Menu para Mobile e Desktop -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasMenuLabel">
                <!-- <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="116" height="36" rx="5" fill="none" stroke="#ff0089" stroke-width="2" />
                    <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold"
                        fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
                </svg> -->
                <span class="text-light"
                    style="font-weight: bold; box-sizing: border-box; text-transform: capitalize; font-family:Arial, sans-serif">WASOM
                    UPFY</span>
            </h5>
            <button type="button" class="btn-close text-white" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i>
                        Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i>
                        Lançamentos</a></li>
                <li class="nav-item"><a class="nav-link" href="../analytics/statistics"><i class="bi bi-bar-chart"></i>
                        Estatísticas</a></li>
                <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
                            class="bi bi-currency-dollar"></i>
                        Finanças</a></li>
                <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i>
                        Artistas</a></li>
                <li class="nav-item"><a class="nav-link" href="../artists/youtube/ucy"><i class="bi bi-youtube"></i>
                        Unificação
                        de
                        canal YouTube</a></li>
                <!-- Links secundários exibidos apenas em mobile -->
                <li class="nav-item d-lg-none"><a class="nav-link" href="../user/profile"><i
                            class="bi bi-person-circle"></i> Meu
                        Perfil</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link active" href="../page/settings"><i
                            class="bi bi-gear"></i> Configurações</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link" href="../page/notifications"><i
                            class="bi bi-bell"></i> Notificações</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link" href="../page/about"><i
                            class="bi bi-info-circle"></i> Sobre</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link" href="../services/available-services"><i
                            class="bi bi-star"></i>
                        Conta e serviços disponíveis</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link" href="../page/help"><i
                            class="bi bi-question-circle"></i>
                        Ajuda</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link" href="#?logout-wasomupfy" data-bs-toggle="modal"
                        data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right"></i>
                        Desconectar-se</a></li>
            </ul>
        </div>
    </div>

    <!-- Toast para Notificações de Status -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="connectionToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">Conexão</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
            <div class="toast-body">
                Você está offline. Alguns dados podem estar desatualizados.
                <div class="mt-2">
                    <button class="btn btn-pink btn-sm" onclick="tryReconnect()">Tentar Reconectar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-4">
        <!-- Welcome Section -->
        <div class="page-header">
            <div class="row align-items-center mb-4">
                <div class="col-auto">
                    <div class="page-header-compact">
                        <h1>
                            <i class="bi bi-pie-chart-fill me-3"></i>
                            Divisão de Royalties
                        </h1>
                        <p class="lead">
                            Todas as divisões feitas em cada faixa distribuída nesta conta encontram-se
                            registradas aqui, bem como podes fazer novas divisões para colaboradores.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-pink" data-bs-toggle="modal" data-bs-target="#creatnewRoyal"><i
                            class="bi bi-plus"></i> Nova divisão</button>
                    <button class="btn btn-light" onclick="window.location='overview'"><i class="bi bi-arrow-left"></i>
                        Voltar</button>
                </div>
            </div>

            <style>
                .page-header::before {
                    content: '\F4F5';
                    /* bi-pie-chart-fill */
                }
            </style>
        </div>

        <!-- Launch Card para actualizar a conta express -->
        <div class="launch-card mb-4 mt-4">
            <div class="card">
                <div class="align-items-lg-center">
                    <div class="text-center">
                        <div class="welcome-text d-flex justify-content-between align-items-center">
                            <h5 style="font-weight: unset;" class="text-secondary">Título da faixa
                                Artista</h5>

                            <a href="#other_link1" data-bs-toggle="collapse" id='link1'
                                data-bs-target="#collapseOneAccount" onclick="other_link()" aria-expanded="false"
                                aria-controls="collapseOneAccount" class="text-secondary"> Ver detalhes</a>

                            <a href="#other_link2" data-bs-toggle="collapse" id='link2'
                                data-bs-target="#collapseOneAccount" onclick="other_link1()" aria-expanded="false"
                                aria-controls="collapseOneAccount" class="text-secondary"> Ocultar detalhes</a>

                        </div>
                        <div id="collapseOneAccount" class="accordion-collapse collapse w-100"
                            data-bs-parent="#accordionExample">
                            <div class="mt-3">
                                <div class="welcome-text d-flex justify-content-between align-items-center">
                                    <h5>Beneficíarios</h5>
                                </div>
                                <div class="welcome-text d-flex justify-content-between align-items-center">
                                    <h6 style="font-weight: unset;" class="text-secondary">N.ª</h6>
                                    <span class="text-secondary">1</span>
                                </div>
                                <div class="welcome-text d-flex justify-content-between align-items-center">
                                    <h6 style="font-weight: unset;" class="text-secondary">Artista</h6>
                                    <span class="text-secondary">Mbenga</span>
                                </div>
                                <div class="welcome-text d-flex justify-content-between align-items-center">
                                    <h6 style="font-weight: unset;" class="text-secondary">Percetagem</h6>
                                    <span class="text-secondary">10%</span>
                                </div>
                                <div class="welcome-text d-flex justify-content-between align-items-center">
                                    <h6 style="font-weight: unset;" class="text-secondary">E-mail:</h6>
                                    <span class="text-secondary">josembengadacosta@wasomupfy.com</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Launch Card para actualizar a conta IBAN -->

        <!-- Modal para criar contas -->
        <div class="modal fade" id="creatnewRoyal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="creatnewRoyalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5 text-dark" id="creatnewRoyalLabel">Dividir royalties</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="card row justify-content-center text-center">
                            <div class="card-header row">
                                <p class="stats-description text-start">
                                    Faça a divisão dos seus royalties com os colaboradores das faixas disponíveis.
                                    Disponível 100% para distribuição.
                                </p>
                            </div>
                            <div class="card-body">
                                <!-- Launch Card para actualizar a conta express -->
                                <div class="launch-card">
                                    <div class="align-items-lg-center">
                                        <div class="text-start">
                                            <h6 class="font-bold">Disponível
                                                <span class="text-success">100%</sapn>
                                            </h6>
                                            <div class="mt-4">
                                                <form method="POST" action="/split-royalties.php"
                                                    class="needs-validation mb-2 row text-start" id="validation-form"
                                                    novalidate>
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                                    <input type="text" name="honeypot" style="display: none;" value>
                                                    <!-- Container para campos dinâmicos -->
                                                    <div id="dynamic-fields">
                                                        <div class="field-group mb-3" data-index="0">
                                                            <div class="row">
                                                                <div class="mb-2 col-md-12">
                                                                    <label for="name_share_0" class="form-label">Faixa
                                                                        a
                                                                        partilhar
                                                                        <span
                                                                            class="text-danger text-xs">*</span></label>
                                                                    <input type="text" autocomplete="off"
                                                                        id="name_share_0" name="name_share[]"
                                                                        placeholder="Insira o nome da faixa" required
                                                                        class="form-control" value>
                                                                    <div class="invalid-feedback">Por
                                                                        favor,
                                                                        insira
                                                                        o
                                                                        nome
                                                                        da
                                                                        faixa.</div>
                                                                </div>
                                                                <div class="mb-2 col-md-8">
                                                                    <label for="name_author_band_0"
                                                                        class="form-label">Artista
                                                                        <span
                                                                            class="text-danger text-xs">*</span></label>
                                                                    <input type="text" maxlength="60"
                                                                        placeholder="Insira o nome do artista"
                                                                        class="form-control" autocomplete="off"
                                                                        id="name_author_band_0"
                                                                        name="name_author_band[]" list="list_author_0"
                                                                        required>
                                                                    <datalist id="list_author_0">
                                                                        <option value="Artista 1">
                                                                        <option value="Artista 2">
                                                                            <!-- Preencha com dados dinâmicos do back-end, se disponível -->
                                                                    </datalist>
                                                                    <div class="invalid-feedback">Por
                                                                        favor,
                                                                        insira
                                                                        o
                                                                        nome
                                                                        do
                                                                        artista.</div>
                                                                </div>
                                                                <div class="mb-2 col-md-4">
                                                                    <label for="por_0" class="form-label">Percentagem
                                                                        <span
                                                                            class="text-danger text-xs">*</span></label>
                                                                    <input type="number" autocomplete="off" id="por_0"
                                                                        name="por[]" step="0.1" min="1" max="100"
                                                                        class="form-control" placeholder="%" required
                                                                        value>
                                                                    <div class="invalid-feedback">Insira
                                                                        uma
                                                                        percentagem
                                                                        válida
                                                                        (1-100).</div>
                                                                </div>
                                                                <div class="mb-2 col-md-12">
                                                                    <label for="email_share_0" class="form-label">E-mail
                                                                        da
                                                                        conta
                                                                        <span
                                                                            class="text-danger text-xs">*</span></label>
                                                                    <br>
                                                                    <small>O
                                                                        e-mail
                                                                        da
                                                                        conta
                                                                        a
                                                                        receber
                                                                        deve
                                                                        ser
                                                                        uma
                                                                        conta
                                                                        Wasom
                                                                        Upfy,
                                                                        neste
                                                                        caso
                                                                        o
                                                                        artista
                                                                        que
                                                                        vai
                                                                        receber
                                                                        os
                                                                        royalties
                                                                        desta
                                                                        faixa
                                                                        deve
                                                                        ter
                                                                        uma
                                                                        conta
                                                                        na
                                                                        plataforma
                                                                        para
                                                                        poder
                                                                        ter
                                                                        acesso
                                                                        aos
                                                                        royalties.</small>
                                                                    <input type="email" autocomplete="off"
                                                                        id="email_share_0" name="email_share[]"
                                                                        placeholder="Insira o email da conta Wasom Upfy"
                                                                        required class="form-control mt-2" value>
                                                                    <div class="invalid-feedback">Insira
                                                                        um
                                                                        e-mail
                                                                        válido
                                                                        da
                                                                        conta
                                                                        Wasom
                                                                        Upfy.</div>
                                                                </div>
                                                                <div class="col-md-12 mb-2">
                                                                    <button type="button"
                                                                        class="btn btn-outline-danger btn-sm delete-field"
                                                                        style="display: none;"
                                                                        aria-label="Remover faixa">Remover
                                                                        campo</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2">
                                                        <button type="button" class="btn btn-pink form-control"
                                                            id="add-field">+
                                                            Adicionar
                                                            novo</button>
                                                    </div>
                                                    <div class="mt-3">
                                                        <small>Ao
                                                            prosseguir
                                                            com esta
                                                            ação,
                                                            concorda
                                                            em fazer
                                                            a
                                                            divisão
                                                            de
                                                            royalties
                                                            da faixa
                                                            selecionada
                                                            para o
                                                            artista
                                                            assinar,
                                                            bem como
                                                            também
                                                            demonstra
                                                            que está
                                                            de
                                                            acordo
                                                            com a
                                                            percentagem
                                                            dada.</small>
                                                    </div>
                                                    <div class="mt-3">
                                                        <button type="submit"
                                                            class="btn btn-outline-pink  form-control">
                                                            Dividir</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer row">
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal para criar contas fim -->
    </div>

    <nav class="bottom-nav d-lg-none">
        <ul class="nav justify-content-around">
            <li class="nav-item"><a class="nav-link" href="../painel" aria-label="Ir para Dashboard"><i
                        class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../launch/releases" aria-label="Ir para Lançamentos"><i
                        class="bi bi-disc"></i><span>Lançamentos</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../analytics/statistics" aria-label="Ir para Estatísticas"><i
                        class="bi bi-bar-chart"></i><span>Estatísticas</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../finances/overview" aria-label="Ir para Finanças"><i
                        class="bi bi-currency-dollar"></i><span>Finanças</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../artists/artists-list" aria-label="Ir para Artistas"><i
                        class="bi bi-person"></i><span>Artistas</span></a></li>
        </ul>
    </nav>

    <!-- ════ MODAL — Logout ════ -->
    <div class="modal fade" id="logoutwasomupfy" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="logoutwasomupfyLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 text-dark" id="logoutwasomupfyLabel">Terminar
                        sessão</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <div class="row justify-content-center text-center">
                            <div class="col-md-12 content-center justify-center text-center">
                                <p class="text-center text-dark">@josembengadacosta
                                    você tem
                                    certeza
                                    de que desejas terminar
                                    sessão?</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Não,
                            continuar</button>
                    </div>
                    <div>
                        <button class="btn btn-danger" type="button" name="logout_wasomupfy"
                            onclick="logout_wasomupfy()">Sim,
                            terminar
                            sessão</button>
                    </div>
                    <script type="text/javascript">
                        function logout_wasomupfy() {
                            window.location = '../logout';
                        }
                    </script>
                </div>
            </div>
        </div>
    </div>
    <!-- ════ MODAL — Logout  FIM ════ -->

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/validacao.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.min.js"></script>
    <!-- Toastr -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="../../js/theme.wp.js"></script>
    <script src="../../js/wp.tools.js"></script>
    <script>
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    </script>
    <script>
        $(document).ready(() => {
            // Contador para índices únicos
            let fieldIndex = 1;

            // Adicionar novo grupo de campos
            $("#add-field").click(() => {
                const newField = `
                <div class="field-group mb-3" data-index="${fieldIndex}">
                    <hr>
                    <div class="row">
                        <div class="mb-2 col-md-12">
                            <label for="name_share_${fieldIndex}" class="form-label">Faixa a partilhar <span class="text-danger text-xs">*</span></label>
                            <input type="text" autocomplete="off" id="name_share_${fieldIndex}" name="name_share[]" placeholder="Insira o nome da faixa" required class="form-control" value="">
                            <div class="invalid-feedback">Por favor, insira o nome da faixa.</div>
                        </div>
                        <div class="mb-2 col-md-8">
                            <label for="name_author_band_${fieldIndex}" class="form-label">Artista <span class="text-danger text-xs">*</span></label>
                            <input type="text" maxlength="60" placeholder="Insira o nome do artista" class="form-control" autocomplete="off" id="name_author_band_${fieldIndex}" name="name_author_band[]" list="list_author_${fieldIndex}" required>
                            <datalist id="list_author_${fieldIndex}">
                                <option value="Artista 1">
                                <option value="Artista 2">
                            </datalist>
                            <div class="invalid-feedback">Por favor, insira o nome do artista.</div>
                        </div>
                        <div class="mb-2 col-md-4">
                            <label for="por_${fieldIndex}" class="form-label">Percentagem <span class="text-danger text-xs">*</span></label>
                            <input type="number" autocomplete="off" id="por_${fieldIndex}" name="por[]" step="0.1" min="1" max="100" class="form-control" placeholder="%" required value="">
                            <div class="invalid-feedback">Insira uma percentagem válida (1-100).</div>
                        </div>
                        <div class="mb-2 col-md-12">
                            <label for="email_share_${fieldIndex}" class="form-label">E-mail da conta <span class="text-danger text-xs">*</span></label>
                            <br>
                            <small>O e-mail da conta a receber deve ser uma conta Wasom Upfy, neste caso o artista que vai receber os royalties desta faixa deve ter uma conta na plataforma para poder ter acesso aos royalties.</small>
                            <input type="email" autocomplete="off" id="email_share_${fieldIndex}" name="email_share[]" placeholder="Insira o email da conta Wasom Upfy" required class="form-control mt-2" value="">
                            <div class="invalid-feedback">Insira um e-mail válido da conta Wasom Upfy.</div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <button type="button" class="btn btn-outline-danger btn-sm delete-field" aria-label="Remover faixa">Remover campo</button>
                        </div>
                    </div>
                </div>`;
                $("#dynamic-fields").append(newField);
                fieldIndex++;
            });

            // Deletar grupo de campos
            $(document).on("click", ".delete-field", function() {
                $(this).closest(".field-group").remove();
            });

            // Validação do formulário
            $("#validation-form").validate({
                rules: {
                    "name_share[]": {
                        required: true,
                        maxlength: 100
                    },
                    "name_author_band[]": {
                        required: true,
                        maxlength: 60
                    },
                    "por[]": {
                        required: true,
                        number: true,
                        min: 1,
                        max: 100
                    },
                    "email_share[]": {
                        required: true,
                        email: true,
                        maxlength: 60
                    }
                },
                messages: {
                    "name_share[]": {
                        required: "Insira o nome da faixa.",
                        maxlength: "Máximo 100 caracteres."
                    },
                    "name_author_band[]": {
                        required: "Insira o nome do artista.",
                        maxlength: "Máximo 60 caracteres."
                    },
                    "por[]": {
                        required: "Insira a percentagem.",
                        number: "Insira um número válido.",
                        min: "A percentagem deve ser no mínimo 1.",
                        max: "A percentagem não pode exceder 100."
                    },
                    "email_share[]": {
                        required: "Insira um e-mail.",
                        email: "Insira um e-mail válido."
                    }
                },
                errorPlacement: (error, element) => {
                    error.insertAfter(element);
                },
                highlight: (element) => {
                    $(element).addClass("is-invalid").removeClass("is-valid");
                },
                unhighlight: (element) => {
                    $(element).removeClass("is-invalid").addClass("is-valid");
                }
            });
        });
    </script>
    <script>
        $(function() {
            var Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000
            });

            $('.toastrDefaultInfo').click(function() {
                toastr.info(
                    'Para visualizar os dados da suas carteiras financeiras basta clicar <q>Express</q> ou <q>IBAN</q>'
                )
            });

        });
    </script>
</body>

</html>