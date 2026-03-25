<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Editar Colaborador
// Arquivo: wu-panel-2026/pages/collab/edit.php
// Rota:    wu-panel-2026/collab/edit?id=X
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'users.edit');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/collab');

// ── Feedback ──
$msg      = $_GET['msg'] ?? null;
$tab_open = $_GET['tab'] ?? 'profile';
$feedback = match ($msg) {
    'updated'      => ['success', 'bi-check-circle', 'Dados actualizados com sucesso.'],
    'pw_reset'     => ['success', 'bi-key',          'Senha temporária enviada com sucesso.'],
    'invite_sent'  => ['success', 'bi-envelope',     'Convite reenviado com sucesso.'],
    'error'        => ['danger',  'bi-x-circle',     'Ocorreu um erro. Tenta novamente.'],
    'dupe_email'   => ['danger',  'bi-x-circle',     'Este e-mail já está em uso por outro colaborador.'],
    'dupe_user'    => ['danger',  'bi-x-circle',     'Este username já está em uso por outro colaborador.'],
    default        => null,
};

// ── Buscar colaborador ──
$stmt = $db->prepare("
    SELECT
        c.*,
        u.id_users       AS owner_id,
        u.first_name     AS owner_first,
        u.second_name    AS owner_second,
        u.email_user     AS owner_email,
        u.photo_user     AS owner_photo
    FROM _collaborators c
    LEFT JOIN _users u ON u.id_users = c.id_users
    WHERE c.id_collab = ?
");
$stmt->execute([$id]);
$collab = $stmt->fetch();

if (!$collab) {
    adminRedirect('/' . ADMIN_PATH . '/collab?msg=not_found');
}

// ── Actividade recente (sidebar) ──
$activity = $db->prepare("
    SELECT activity_type, description, creat_activity
    FROM _collab_activity
    WHERE id_collab = ?
    ORDER BY creat_activity DESC
    LIMIT 5
");
$activity->execute([$id]);
$activity_list = $activity->fetchAll();

$fullname   = trim($collab['first_name'] . ' ' . ($collab['second_name'] ?? ''));
$ini        = mb_strtoupper(mb_substr(trim($collab['first_name']), 0, 1, 'UTF-8'))
    . mb_strtoupper(mb_substr(trim($collab['second_name'] ?? ''), 0, 1, 'UTF-8'));
$colors     = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308'];
$color      = $colors[abs(crc32($fullname)) % count($colors)];

$owner_name  = trim(($collab['owner_first'] ?? '') . ' ' . ($collab['owner_second'] ?? ''));
$owner_ini   = mb_strtoupper(mb_substr(trim($collab['owner_first'] ?? ''), 0, 1, 'UTF-8'))
    . mb_strtoupper(mb_substr(trim($collab['owner_second'] ?? ''), 0, 1, 'UTF-8'));
$owner_colors = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308'];
$owner_color  = $owner_colors[abs(crc32($owner_name)) % count($owner_colors)];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title>Editar <?php echo htmlspecialchars($collab['first_name']); ?> — Colaborador · Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        /* ── Layout cards ── */
        .ce-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 14px;
            padding: 22px 24px;
            margin-bottom: 20px;
        }

        .ce-card-title {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            opacity: .5;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ── Profile card (coluna esquerda) ── */
        .ce-profile-card {
            background: linear-gradient(160deg, #0f0f1a 0%, #1a0a12 100%);
            border-radius: 16px;
            padding: 28px 22px;
            text-align: center;
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .ce-profile-card::before {
            content: '';
            position: absolute;
            top: -50px;
            left: -50px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255, 0, 137, .2) 0%, transparent 70%);
            pointer-events: none;
        }

        .ce-avatar-wrap {
            position: relative;
            display: inline-block;
        }

        .ce-avatar-lg {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 0, 137, .35);
        }

        .ce-avatar-ini-lg {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.5rem;
            color: #fff;
            border: 3px solid rgba(255, 255, 255, .15);
            margin: 0 auto;
        }

        /* ── Tabs ── */
        .ce-nav .nav-link {
            font-size: .82rem;
            font-weight: 600;
            color: var(--text-muted, #888);
            border-radius: 10px;
            padding: 8px 16px;
            margin-right: 4px;
            border: none !important;
            transition: all .2s;
        }

        .ce-nav .nav-link.active {
            background: #FF008918;
            color: #FF0089 !important;
        }

        .ce-nav .nav-link:hover:not(.active) {
            background: var(--hover-bg, rgba(0, 0, 0, .04));
        }

        /* ── Form elements ── */
        .ce-form-label {
            font-size: .78rem;
            font-weight: 600;
            margin-bottom: 5px;
            opacity: .7;
        }

        .ce-hint {
            font-size: .72rem;
            opacity: .45;
            margin-top: 3px;
        }

        /* ── Password strength ── */
        .pw-strength-bar {
            height: 4px;
            border-radius: 4px;
            transition: width .3s, background .3s;
            background: #e8e8f0;
        }

        .pw-req {
            font-size: .75rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color .2s;
        }

        .pw-req.met {
            color: #22c55e;
        }

        .pw-req.unmet {
            opacity: .4;
        }

        /* ── Activity mini ── */
        .ce-act-item {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
            font-size: .78rem;
        }

        .ce-act-item:last-child {
            border-bottom: none;
        }

        .ce-act-dot {
            width: 26px;
            height: 26px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .78rem;
            flex-shrink: 0;
        }

        /* ── Owner mini ── */
        .ce-owner-mini {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 10px;
            text-decoration: none;
            color: inherit;
        }

        .ce-owner-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .ce-owner-ini {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .68rem;
            color: #fff;
            flex-shrink: 0;
        }

        /* ── Role colours ── */
        .role-admin {
            background: rgba(239, 68, 68, .12);
            color: #ef4444;
            border-color: rgba(239, 68, 68, .3);
        }

        .role-editor {
            background: rgba(6, 182, 212, .12);
            color: #06b6d4;
            border-color: rgba(6, 182, 212, .3);
        }

        .role-analyst {
            background: rgba(34, 197, 94, .12);
            color: #22c55e;
            border-color: rgba(34, 197, 94, .3);
        }

        .role-support {
            background: rgba(107, 114, 128, .12);
            color: #6b7280;
            border-color: rgba(107, 114, 128, .3);
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

                <!-- Breadcrumb -->
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1"><i class="bi bi-pencil-square me-2"></i>Editar Colaborador</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>" class="text-secondary">Home</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab"
                                        class="text-secondary">Colaboradores</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/view?id=<?php echo $id; ?>"
                                        class="text-secondary">
                                        <?php echo htmlspecialchars($fullname); ?>
                                    </a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable">Editar</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/view?id=<?php echo $id; ?>"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye me-1"></i> Visualizar
                        </a>
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Voltar
                        </a>
                    </div>
                </div>

                <div class="row g-4">

                    <!-- ═══ Coluna Esquerda (3/12) ═══ -->
                    <div class="col-lg-3">

                        <!-- Profile card -->
                        <div class="ce-profile-card">
                            <div class="ce-avatar-wrap mb-3" style="position:relative;display:inline-block">
                                <?php if (!empty($collab['photo_collab'])): ?>
                                    <img src="<?php echo htmlspecialchars($collab['photo_collab']); ?>" class="ce-avatar-lg"
                                        id="avatar-preview" alt=""
                                        onerror="this.style.display='none';document.getElementById('avatar-placeholder').style.display='flex'" />
                                    <div class="ce-avatar-ini-lg" id="avatar-placeholder"
                                        style="background:<?php echo $color; ?>;display:none">
                                        <?php echo $ini; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="ce-avatar-ini-lg" id="avatar-placeholder"
                                        style="background:<?php echo $color; ?>">
                                        <?php echo $ini; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="text-white fw-700 mb-1" id="preview-name"
                                style="font-size:.95rem;font-weight:700">
                                <?php echo htmlspecialchars($fullname); ?>
                            </div>
                            <div class="mb-2">
                                <span class="badge role-<?php echo $collab['role_collab']; ?>" id="preview-role"
                                    style="border:1px solid">
                                    <?php echo match ($collab['role_collab']) {
                                        'admin' => 'Administrador',
                                        'editor' => 'Editor',
                                        'analyst' => 'Analista',
                                        'support' => 'Suporte',
                                        default => ucfirst($collab['role_collab'])
                                    }; ?>
                                </span>
                            </div>
                            <div style="color:rgba(255,255,255,.45);font-size:.77rem" id="preview-user">
                                @<?php echo htmlspecialchars($collab['user_collab']); ?>
                            </div>
                            <div style="color:rgba(255,255,255,.35);font-size:.74rem;margin-top:4px" id="preview-email">
                                <?php echo htmlspecialchars($collab['email_collab']); ?>
                            </div>

                            <!-- Atalhos para tabs -->
                            <div class="mt-3 pt-3" style="border-top:1px solid rgba(255,255,255,.08);text-align:left">
                                <div
                                    style="font-size:.7rem;text-transform:uppercase;letter-spacing:.6px;opacity:.35;margin-bottom:8px">
                                    Secções
                                </div>
                                <a class="tab-link d-flex align-items-center gap-2 py-1" data-tab="profile" href="#"
                                    style="color:rgba(255,255,255,.6);text-decoration:none;font-size:.78rem">
                                    <i class="bi bi-person-fill" style="color:#FF0089"></i> Perfil
                                </a>
                                <a class="tab-link d-flex align-items-center gap-2 py-1 mt-1" data-tab="security"
                                    href="#" style="color:rgba(255,255,255,.6);text-decoration:none;font-size:.78rem">
                                    <i class="bi bi-key-fill" style="color:#FF0089"></i> Segurança
                                </a>
                            </div>
                        </div>

                        <!-- Actividade recente -->
                        <div class="ce-card">
                            <div class="ce-card-title"><i class="bi bi-activity"></i> Actividade</div>
                            <?php if (empty($activity_list)): ?>
                                <div style="text-align:center;opacity:.35;font-size:.78rem;padding:12px 0">
                                    Sem actividade registada
                                </div>
                            <?php else: ?>
                                <?php foreach ($activity_list as $act):
                                    $desc = $act['description'] ?: str_replace('_', ' ', $act['activity_type']);
                                ?>
                                    <div class="ce-act-item">
                                        <div class="ce-act-dot" style="background:rgba(255,0,137,.1)">
                                            <i class="bi bi-lightning-charge-fill" style="color:#FF0089"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:600"><?php echo htmlspecialchars($desc); ?></div>
                                            <div style="opacity:.4;font-size:.7rem">
                                                <?php
                                                $ts   = strtotime($act['creat_activity']);
                                                $diff = time() - $ts;
                                                if ($diff < 3600)       echo floor($diff / 60) . ' min atrás';
                                                elseif ($diff < 86400)  echo floor($diff / 3600) . 'h atrás';
                                                else                     echo date('d/m/Y', $ts);
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Proprietário -->
                        <?php if ($collab['owner_id']): ?>
                            <div class="ce-card">
                                <div class="ce-card-title"><i class="bi bi-person-circle"></i> Proprietário</div>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $collab['owner_id']; ?>"
                                    class="ce-owner-mini">
                                    <?php if (!empty($collab['owner_photo'])): ?>
                                        <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($collab['owner_photo']); ?>"
                                            class="ce-owner-avatar" alt=""
                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                                        <div class="ce-owner-ini" style="background:<?php echo $owner_color; ?>;display:none">
                                            <?php echo $owner_ini; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="ce-owner-ini" style="background:<?php echo $owner_color; ?>">
                                            <?php echo $owner_ini; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-size:.82rem;font-weight:700">
                                            <?php echo htmlspecialchars($owner_name ?: '—'); ?></div>
                                        <div
                                            style="font-size:.72rem;opacity:.45;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:140px">
                                            <?php echo htmlspecialchars($collab['owner_email'] ?? ''); ?>
                                        </div>
                                    </div>
                                    <i class="bi bi-arrow-right ms-auto" style="opacity:.3;font-size:.85rem"></i>
                                </a>
                            </div>
                        <?php endif; ?>

                    </div><!-- /col-lg-3 -->

                    <!-- ═══ Coluna Principal (9/12) ═══ -->
                    <div class="col-lg-9">

                        <!-- Tabs nav -->
                        <ul class="nav ce-nav mb-3" id="collab-tabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link <?php echo $tab_open === 'profile' ? 'active' : ''; ?>"
                                    data-bs-toggle="tab" data-bs-target="#tab-profile" type="button">
                                    <i class="bi bi-person me-1"></i> Perfil
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link <?php echo $tab_open === 'security' ? 'active' : ''; ?>"
                                    data-bs-toggle="tab" data-bs-target="#tab-security" type="button">
                                    <i class="bi bi-key me-1"></i> Segurança
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">

                            <!-- ── TAB PERFIL ── -->
                            <div class="tab-pane fade <?php echo $tab_open === 'profile' ? 'show active' : ''; ?>"
                                id="tab-profile">

                                <?php if ($feedback && in_array($tab_open, ['profile', ''])): ?>
                                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3">
                                        <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                                        <?php echo htmlspecialchars($feedback[2]); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <form method="POST"
                                    action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/edit-process"
                                    enctype="multipart/form-data" id="form-profile">
                                    <input type="hidden" name="csrf_token"
                                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
                                    <input type="hidden" name="id_collab" value="<?php echo $id; ?>">
                                    <input type="hidden" name="action" value="update_profile">

                                    <!-- Dados pessoais -->
                                    <div class="ce-card">
                                        <div class="ce-card-title"><i class="bi bi-person"></i> Dados Pessoais</div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="ce-form-label">Primeiro Nome *</label>
                                                <input type="text" class="form-control" name="first_name"
                                                    value="<?php echo htmlspecialchars($collab['first_name']); ?>"
                                                    required id="inp-first" />
                                            </div>
                                            <div class="col-md-6">
                                                <label class="ce-form-label">Apelido</label>
                                                <input type="text" class="form-control" name="second_name"
                                                    value="<?php echo htmlspecialchars($collab['second_name'] ?? ''); ?>"
                                                    id="inp-second" />
                                            </div>
                                            <div class="col-md-6">
                                                <label class="ce-form-label">Username *</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">@</span>
                                                    <input type="text" class="form-control" name="user_collab"
                                                        value="<?php echo htmlspecialchars($collab['user_collab']); ?>"
                                                        required pattern="[a-zA-Z0-9_]+" id="inp-user" />
                                                </div>
                                                <div class="ce-hint">Apenas letras, números e underscore</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="ce-form-label">E-mail *</label>
                                                <input type="email" class="form-control" name="email_collab"
                                                    value="<?php echo htmlspecialchars($collab['email_collab']); ?>"
                                                    required id="inp-email" />
                                            </div>
                                            <div class="col-md-6">
                                                <label class="ce-form-label">Telefone</label>
                                                <input type="text" class="form-control" name="tel_collab"
                                                    value="<?php echo htmlspecialchars($collab['tel_collab'] ?? ''); ?>" />
                                            </div>
                                            <div class="col-md-6">
                                                <label class="ce-form-label">Função</label>
                                                <select class="form-select" name="role_collab" id="inp-role">
                                                    <?php foreach (
                                                        [
                                                            'admin'   => 'Administrador',
                                                            'editor'  => 'Editor',
                                                            'analyst' => 'Analista',
                                                            'support' => 'Suporte',
                                                        ] as $val => $lbl
                                                    ): ?>
                                                        <option value="<?php echo $val; ?>"
                                                            <?php echo $collab['role_collab'] === $val ? 'selected' : ''; ?>>
                                                            <?php echo $lbl; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="ce-hint" id="role-desc"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="ce-form-label">Estado</label>
                                                <select class="form-select" name="status_collab">
                                                    <?php foreach (
                                                        [
                                                            'active'   => 'Activo',
                                                            'pending'  => 'Pendente',
                                                            'blocked'  => 'Bloqueado',
                                                            'inactive' => 'Inactivo',
                                                        ] as $val => $lbl
                                                    ): ?>
                                                        <option value="<?php echo $val; ?>"
                                                            <?php echo $collab['status_collab'] === $val ? 'selected' : ''; ?>>
                                                            <?php echo $lbl; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="ce-form-label">Notas internas</label>
                                                <textarea class="form-control" name="notes" rows="3"
                                                    placeholder="Informações internas sobre este colaborador..."><?php echo htmlspecialchars($collab['notes'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Foto de perfil -->
                                    <div class="ce-card">
                                        <div class="ce-card-title"><i class="bi bi-image"></i> Foto de Perfil</div>
                                        <div class="row g-3 align-items-center">
                                            <div class="col-auto">
                                                <?php if (!empty($collab['photo_collab'])): ?>
                                                    <img src="<?php echo htmlspecialchars($collab['photo_collab']); ?>"
                                                        id="photo-preview"
                                                        style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,0,137,.3)"
                                                        alt=""
                                                        onerror="this.style.display='none';document.getElementById('photo-ini').style.display='flex'" />
                                                    <div id="photo-ini"
                                                        style="background:<?php echo $color; ?>;width:64px;height:64px;border-radius:50%;display:none;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:1.2rem">
                                                        <?php echo $ini; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div id="photo-ini"
                                                        style="background:<?php echo $color; ?>;width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:1.2rem">
                                                        <?php echo $ini; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col">
                                                <div class="mb-2">
                                                    <label class="ce-form-label">URL externa da foto</label>
                                                    <input type="url" class="form-control" name="photo_collab"
                                                        value="<?php echo htmlspecialchars($collab['photo_collab'] ?? ''); ?>"
                                                        placeholder="https://exemplo.com/foto.jpg"
                                                        id="photo-url-input" />
                                                    <div class="ce-hint">Cole o URL de uma imagem pública. Deixa em
                                                        branco para remover.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/view?id=<?php echo $id; ?>"
                                            class="btn btn-outline-secondary">
                                            Cancelar
                                        </a>
                                        <button type="submit" class="btn text-white" id="btn-save-profile"
                                            style="background:#FF0089;border-color:#FF0089;min-width:130px">
                                            <span id="btn-save-text">
                                                <i class="bi bi-check-lg me-1"></i> Guardar Alterações
                                            </span>
                                            <span id="btn-save-spin" class="d-none">
                                                <span class="spinner-border spinner-border-sm me-1"></span> A guardar...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- ── TAB SEGURANÇA ── -->
                            <div class="tab-pane fade <?php echo $tab_open === 'security' ? 'show active' : ''; ?>"
                                id="tab-security">

                                <?php if ($feedback && $tab_open === 'security'): ?>
                                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3">
                                        <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                                        <?php echo htmlspecialchars($feedback[2]); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <!-- Gerar nova senha -->
                                <div class="ce-card">
                                    <div class="ce-card-title"><i class="bi bi-key"></i> Gerar Nova Senha Temporária
                                    </div>
                                    <p style="font-size:.83rem;opacity:.6;margin-bottom:16px">
                                        Gera uma senha forte e envia por email ao colaborador. A senha é temporária — o
                                        colaborador será obrigado a alterá-la no próximo acesso.
                                    </p>

                                    <form method="POST"
                                        action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/edit-process"
                                        id="form-pw">
                                        <input type="hidden" name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
                                        <input type="hidden" name="id_collab" value="<?php echo $id; ?>">
                                        <input type="hidden" name="action" value="reset_password">

                                        <div class="mb-3">
                                            <label class="ce-form-label">Nova senha temporária</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control font-monospace" id="new-pw"
                                                    name="new_password" readonly
                                                    placeholder="Clica em Gerar para criar..." />
                                                <button type="button" class="btn btn-outline-secondary" id="btn-gen-pw"
                                                    title="Gerar senha forte">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" id="btn-copy-pw"
                                                    title="Copiar">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                            <!-- Barra de força -->
                                            <div class="mt-2"
                                                style="height:4px;background:var(--border-color,#e8e8f0);border-radius:4px">
                                                <div id="pw-bar" class="pw-strength-bar" style="width:0"></div>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                <span id="pr-len" class="pw-req unmet"><i class="bi bi-dot"></i> 12+
                                                    chars</span>
                                                <span id="pr-upper" class="pw-req unmet"><i class="bi bi-dot"></i>
                                                    Maiúscula</span>
                                                <span id="pr-lower" class="pw-req unmet"><i class="bi bi-dot"></i>
                                                    Minúscula</span>
                                                <span id="pr-num" class="pw-req unmet"><i class="bi bi-dot"></i>
                                                    Número</span>
                                                <span id="pr-spec" class="pw-req unmet"><i class="bi bi-dot"></i>
                                                    Símbolo</span>
                                            </div>
                                        </div>

                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="send_email"
                                                id="chk-send-email" value="1" checked />
                                            <label class="form-check-label" for="chk-send-email"
                                                style="font-size:.82rem">
                                                Enviar nova senha por email para
                                                <strong><?php echo htmlspecialchars($collab['email_collab']); ?></strong>
                                            </label>
                                        </div>

                                        <button type="submit" class="btn text-white" id="btn-reset-pw" disabled
                                            style="background:#FF0089;border-color:#FF0089">
                                            <i class="bi bi-key me-1"></i> Definir Senha Temporária
                                        </button>
                                    </form>
                                </div>

                                <!-- Reenviar convite -->
                                <?php if (!$collab['invite_token_used'] && $collab['status_collab'] === 'pending'): ?>
                                    <div class="ce-card">
                                        <div class="ce-card-title"><i class="bi bi-envelope-paper"></i> Reenviar Convite
                                        </div>
                                        <p style="font-size:.83rem;opacity:.6;margin-bottom:16px">
                                            O colaborador ainda não activou a conta. Podes gerar um novo convite com
                                            credenciais actualizadas.
                                        </p>
                                        <button onclick="resendInvite(<?php echo $id; ?>)" class="btn btn-outline-primary"
                                            type="button">
                                            <i class="bi bi-envelope-paper me-1"></i> Reenviar Convite
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <!-- Terminar sessões -->
                                <div class="ce-card">
                                    <div class="ce-card-title"><i class="bi bi-laptop"></i> Sessões</div>
                                    <p style="font-size:.83rem;opacity:.6;margin-bottom:12px">
                                        Termina todas as sessões activas deste colaborador. O próximo acesso exigirá
                                        novo login.
                                    </p>
                                    <form method="POST"
                                        action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/edit-process">
                                        <input type="hidden" name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
                                        <input type="hidden" name="id_collab" value="<?php echo $id; ?>">
                                        <input type="hidden" name="action" value="revoke_sessions">
                                        <button type="submit" class="btn btn-outline-warning"
                                            onclick="return confirm('Terminar todas as sessões activas deste colaborador?')">
                                            <i class="bi bi-power me-1"></i> Terminar Todas as Sessões
                                        </button>
                                    </form>
                                </div>

                            </div><!-- /tab-security -->

                        </div><!-- /tab-content -->
                    </div><!-- /col-lg-9 -->

                </div><!-- /row -->
            </div><!-- /container-fluid -->
        </div><!-- /content -->
    </div><!-- /wrapper -->

    <footer>
        <div class="container">
            <div class="col-12 text-center py-2" style="font-size:.8rem">
                <p class="mb-0">© <?php echo date('Y'); ?> Wasom Upfy. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="" />
            <div class="loader-progress"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
        window.__profilePage = true;
        window.__BASE_URL__ = '<?php echo APP_URL; ?>';
        window.__ADMIN_PATH__ = '<?php echo ADMIN_PATH; ?>';

        (function() {
            'use strict';

            const BASE_URL = window.__BASE_URL__;
            const ADMIN_PATH = window.__ADMIN_PATH__;
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const PROCESS = BASE_URL + '/' + ADMIN_PATH + '/collab/process';

            // ── Atalhos para tabs (coluna esquerda) ──
            document.querySelectorAll('.tab-link[data-tab]').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabEl = document.querySelector('button[data-bs-target="#tab-' + this.dataset
                        .tab + '"]');
                    if (tabEl) bootstrap.Tab.getOrCreateInstance(tabEl).show();
                });
            });

            // ── Preview ao vivo do profile card ──
            document.getElementById('inp-first')?.addEventListener('input', updatePreview);
            document.getElementById('inp-second')?.addEventListener('input', updatePreview);
            document.getElementById('inp-user')?.addEventListener('input', function() {
                const el = document.getElementById('preview-user');
                if (el) el.textContent = '@' + (this.value || '');
            });
            document.getElementById('inp-email')?.addEventListener('input', function() {
                const el = document.getElementById('preview-email');
                if (el) el.textContent = this.value || '';
            });

            const roleLabels = {
                admin: 'Administrador',
                editor: 'Editor',
                analyst: 'Analista',
                support: 'Suporte'
            };
            const roleDescs = {
                admin: 'Acesso total à conta do proprietário.',
                editor: 'Pode criar e editar conteúdos.',
                analyst: 'Acesso de leitura a estatísticas.',
                support: 'Pode responder a pedidos de suporte.'
            };
            document.getElementById('inp-role')?.addEventListener('change', function() {
                const el = document.getElementById('preview-role');
                if (el) el.textContent = roleLabels[this.value] || this.value;
                const desc = document.getElementById('role-desc');
                if (desc) desc.textContent = roleDescs[this.value] || '';
            });
            // Inicializar descrição do role
            const roleEl = document.getElementById('inp-role');
            if (roleEl) {
                const desc = document.getElementById('role-desc');
                if (desc) desc.textContent = roleDescs[roleEl.value] || '';
            }

            function updatePreview() {
                const first = document.getElementById('inp-first')?.value || '';
                const second = document.getElementById('inp-second')?.value || '';
                const el = document.getElementById('preview-name');
                if (el) el.textContent = (first + ' ' + second).trim();
            }

            // ── Spinner no submit do profile ──
            document.getElementById('form-profile')?.addEventListener('submit', function() {
                document.getElementById('btn-save-text').classList.add('d-none');
                document.getElementById('btn-save-spin').classList.remove('d-none');
                document.getElementById('btn-save-profile').disabled = true;
            });

            // ── Preview URL da foto ──
            document.getElementById('photo-url-input')?.addEventListener('input', function() {
                const url = this.value.trim();
                const preview = document.getElementById('photo-preview');
                const ini = document.getElementById('photo-ini');
                const avLg = document.getElementById('avatar-placeholder');
                if (url) {
                    if (preview) {
                        preview.src = url;
                        preview.style.display = '';
                    }
                    if (ini) ini.style.display = 'none';
                    if (avLg && !preview) avLg.style.backgroundImage = 'url(' + url + ')';
                } else {
                    if (preview) preview.style.display = 'none';
                    if (ini) ini.style.display = 'flex';
                }
            });

            // ── Gerador de senha ──
            const chars = {
                upper: 'ABCDEFGHJKLMNPQRSTUVWXYZ',
                lower: 'abcdefghjkmnpqrstuvwxyz',
                digits: '23456789',
                special: '@#$%&*!?'
            };

            function genPassword(len = 16) {
                const all = chars.upper + chars.lower + chars.digits + chars.special;
                let pwd = chars.upper[Math.floor(Math.random() * chars.upper.length)] +
                    chars.lower[Math.floor(Math.random() * chars.lower.length)] +
                    chars.digits[Math.floor(Math.random() * chars.digits.length)] +
                    chars.special[Math.floor(Math.random() * chars.special.length)];
                for (let i = 4; i < len; i++) pwd += all[Math.floor(Math.random() * all.length)];
                return pwd.split('').sort(() => Math.random() - .5).join('');
            }

            function evalPassword(pw) {
                const checks = {
                    len: pw.length >= 12,
                    upper: /[A-Z]/.test(pw),
                    lower: /[a-z]/.test(pw),
                    num: /[0-9]/.test(pw),
                    spec: /[@#$%&*!?^()_\-+=]/.test(pw)
                };
                const ids = {
                    len: 'pr-len',
                    upper: 'pr-upper',
                    lower: 'pr-lower',
                    num: 'pr-num',
                    spec: 'pr-spec'
                };
                let score = 0;
                Object.entries(checks).forEach(([k, ok]) => {
                    const el = document.getElementById(ids[k]);
                    if (el) {
                        el.classList.toggle('met', ok);
                        el.classList.toggle('unmet', !ok);
                    }
                    if (ok) score++;
                });
                const bar = document.getElementById('pw-bar');
                if (bar) {
                    const pct = (score / 5) * 100;
                    const color = score < 2 ? '#ef4444' : score < 4 ? '#eab308' : '#22c55e';
                    bar.style.width = pct + '%';
                    bar.style.background = color;
                }
                const btn = document.getElementById('btn-reset-pw');
                if (btn) btn.disabled = score < 5;
                return score;
            }

            document.getElementById('btn-gen-pw')?.addEventListener('click', function() {
                const pw = genPassword(16);
                const el = document.getElementById('new-pw');
                if (el) {
                    el.value = pw;
                    evalPassword(pw);
                }
            });
            document.getElementById('new-pw')?.addEventListener('input', function() {
                evalPassword(this.value);
            });
            document.getElementById('btn-copy-pw')?.addEventListener('click', function() {
                const pw = document.getElementById('new-pw')?.value;
                if (!pw) return;
                navigator.clipboard.writeText(pw).then(() => {
                    this.innerHTML = '<i class="bi bi-check2"></i>';
                    setTimeout(() => {
                        this.innerHTML = '<i class="bi bi-clipboard"></i>';
                    }, 1500);
                });
            });

            // ── Reenviar convite via AJAX ──
            async function postAction(payload) {
                const fd = new FormData();
                Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
                fd.append('csrf_token', CSRF);
                const r = await fetch(PROCESS, {
                    method: 'POST',
                    body: fd
                });
                return r.json();
            }

            window.resendInvite = async function(id) {
                const result = await Swal.fire({
                    title: 'Reenviar convite?',
                    text: 'Um novo email com as credenciais será enviado para o colaborador.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#FF0089',
                    confirmButtonText: 'Sim, reenviar',
                    cancelButtonText: 'Cancelar'
                });
                if (!result.isConfirmed) return;
                Swal.fire({
                    title: 'A processar...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                try {
                    const data = await postAction({
                        action: 'resend_invite',
                        id_collab: id
                    });
                    if (data.ok) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Convite reenviado!',
                            text: data.message,
                            confirmButtonColor: '#FF0089'
                        });
                        location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: data.message,
                            confirmButtonColor: '#FF0089'
                        });
                    }
                } catch {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro de ligação',
                        text: 'Verifica a tua internet.',
                        confirmButtonColor: '#FF0089'
                    });
                }
            };

        })();
    </script>
</body>

</html>