<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Editar Funcionário
// Arquivo: admin/pages/employees/edit.php
// Rota:    admin/employees/edit?id=X
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'employees.edit');

$id = (int)($_GET['id'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/employees');

// Não pode editar a si próprio aqui (existe o profile.php para isso)
if ($id === $admin_id) {
    adminRedirect('/' . ADMIN_PATH . '/profile');
}

// ── Carregar funcionário ──
$stmt = $db->prepare("
    SELECT e.id_employees, e.first_name, e.second_name, e.user_employees,
           e.email_employees, e.email_employees_other, e.tel_employees,
           e.photo_employees, e.gender, e.role, e.status_employees,
           e.about_employees, e.url_employees,
           e.country_employees, e.city_employees,
           e.creat_employees, e.modif_employees
    FROM _employees e
    WHERE e.id_employees = ?
    LIMIT 1
");
$stmt->execute([$id]);
$emp = $stmt->fetch();

if (!$emp) adminRedirect('/' . ADMIN_PATH . '/employees');

// Não pode editar super_admin se não for super_admin
if ($emp['role'] === 'super_admin' && $admin_role !== 'super_admin') {
    adminRedirect('/' . ADMIN_PATH . '/employees');
}

// ── Permissões explícitas do funcionário ──
$perms_stmt = $db->prepare("
    SELECT permission, granted FROM _employees_permissions
    WHERE id_employees = ?
");
$perms_stmt->execute([$id]);
$perms_rows = $perms_stmt->fetchAll();
$perm_map = [];
foreach ($perms_rows as $p) {
    $perm_map[$p['permission']] = (int)$p['granted'];
}

// ── Actividade recente ──
$audit_stmt = $db->prepare("
    SELECT action, entity, creat_log
    FROM _audit_log
    WHERE id_employees = ?
    ORDER BY creat_log DESC
    LIMIT 8
");
$audit_stmt->execute([$id]);
$audit_list = $audit_stmt->fetchAll();

// ── Feedback ──
$msg      = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'updated' => ['success', 'bi-check-circle', 'Dados actualizados com sucesso.'],
    'pw_reset' => ['success', 'bi-key',           'Senha redefinida. O funcionário será notificado por e-mail.'],
    'perms'   => ['success', 'bi-shield-check',  'Permissões guardadas com sucesso.'],
    'error'   => ['danger',  'bi-x-circle',       'Ocorreu um erro. Tenta novamente.'],
    'email_exists' => ['danger', 'bi-x-circle',   'Este e-mail já está em uso por outro funcionário.'],
    'user_exists'  => ['danger', 'bi-x-circle',   'Este username já está em uso.'],
    default   => null,
};

// Tab activo (vinda do process em caso de erro)
$active_tab = $_GET['tab'] ?? 'profile';

$fullname = trim($emp['first_name'] . ' ' . ($emp['second_name'] ?? ''));
$ini      = adm_initials($emp['first_name'], $emp['second_name'] ?? '');
$color    = adm_avatar_color($fullname);

// ═══════════════════════════════════════════════════════════════════════════════
// Mapeamento de permissões do sistema (actualizado com todos os módulos)
// Utilizado na página de edição de funcionários e nas verificações de acesso.
// ═══════════════════════════════════════════════════════════════════════════════
$all_permissions = [
    // ─── Administração do sistema ──────────────────────────────────────────
    'employees' => [
        'label' => 'Gestão de Admins',
        'icon'  => 'bi-person-gear',
        'perms' => [
            'employees.view' => 'Ver lista de administradores',
            'employees.edit' => 'Criar/editar/remover administradores',
        ],
        'desc'  => 'Permite gerir outros funcionários do painel.',
    ],
    'audit' => [
        'label' => 'Auditoria',
        'icon'  => 'bi-journal-text',
        'perms' => [
            'audit.view' => 'Visualizar logs de auditoria',
        ],
        'desc'  => 'Acesso ao histórico de ações dos administradores e utilizadores.',
    ],
    'settings' => [
        'label' => 'Configurações',
        'icon'  => 'bi-sliders',
        'perms' => [
            'settings.view' => 'Visualizar configurações',
            'settings.edit' => 'Editar configurações globais (plataforma, e-mail, etc.)',
        ],
        'desc'  => 'Permite alterar parâmetros do sistema.',
    ],

    // ─── Utilizadores e perfis ────────────────────────────────────────────
    'users' => [
        'label' => 'Gestão de Utilizadores',
        'icon'  => 'bi-people',
        'perms' => [
            'users.view' => 'Ver lista de utilizadores e detalhes',
            'users.edit' => 'Editar, suspender, activar ou eliminar utilizadores',
        ],
        'desc'  => 'Controla o acesso aos dados dos artistas (clientes finais).',
    ],
    'artists' => [
        'label' => 'Artistas',
        'icon'  => 'bi-mic',
        'perms' => [
            'artists.view' => 'Ver artistas cadastrados',
            'artists.edit' => 'Editar ou eliminar artistas',
        ],
        'desc'  => 'Artistas associados aos utilizadores (perfis musicais).',
    ],
    'collaborators' => [
        'label' => 'Colaboradores',
        'icon'  => 'bi-person-plus',
        'perms' => [
            'collaborators.view' => 'Ver colaboradores',
            'collaborators.edit' => 'Gerir colaboradores (convidar, editar, remover)',
        ],
        'desc'  => 'Colaboradores convidados pelos utilizadores para ajudar na gestão.',
    ],

    // ─── Música e distribuição ───────────────────────────────────────────
    'music' => [
        'label' => 'Músicas e Lançamentos',
        'icon'  => 'bi-music-note-list',
        'perms' => [
            'music.view'    => 'Ver lançamentos e faixas',
            'music.approve' => 'Aprovar / Rejeitar envios de música',
        ],
        'desc'  => 'Gerencia o catálogo musical e o fluxo de aprovação.',
    ],
    'distribution' => [
        'label' => 'Distribuição',
        'icon'  => 'bi-globe',
        'perms' => [
            'distribution.view'   => 'Ver lojas e canais de distribuição',
            'distribution.edit'   => 'Adicionar/editar lojas e parâmetros de distribuição',
        ],
        'desc'  => 'Controla para onde as músicas são enviadas (Spotify, Apple, etc.).',
    ],

    // ─── Financeiro ──────────────────────────────────────────────────────
    'payments' => [
        'label' => 'Pagamentos',
        'icon'  => 'bi-wallet2',
        'perms' => [
            'payments.view' => 'Ver lista de pagamentos e detalhes',
            'payments.edit' => 'Aprovar, rejeitar ou reembolsar pagamentos',
        ],
        'desc'  => 'Gestão de comprovativos e ativação de planos.',
    ],
    'finances' => [
        'label' => 'Finanças e Rendimentos',
        'icon'  => 'bi-currency-dollar',
        'perms' => [
            'finances.view' => 'Ver relatórios financeiros e royalties',
            'finances.edit' => 'Processar pagamentos a artistas (saques)',
        ],
        'desc'  => 'Controla a visibilidade e processamento de royalties e saques.',
    ],

    // ─── Estatísticas e análises ─────────────────────────────────────────
    'analytics' => [
        'label' => 'Estatísticas',
        'icon'  => 'bi-graph-up',
        'perms' => [
            'analytics.view' => 'Visualizar gráficos, relatórios e dados agregados',
        ],
        'desc'  => 'Acesso a métricas de streaming, desempenho de artistas, etc.',
    ],

    // ─── Suporte ─────────────────────────────────────────────────────────
    'support' => [
        'label' => 'Suporte',
        'icon'  => 'bi-headset',
        'perms' => [
            'support.view' => 'Ver tickets de suporte',
            'support.edit' => 'Responder tickets e alterar status',
        ],
        'desc'  => 'Gerencia o atendimento aos utilizadores.',
    ],
];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <title>Editar — <?php echo htmlspecialchars($fullname); ?> — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/scrollue.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <style>
        /* ── Sidebar card do funcionário ── */
        .emp-side-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .emp-side-header {
            background: linear-gradient(135deg, #0f0f17 0%, #1a1a2e 100%);
            padding: 24px 20px;
            text-align: center;
            position: relative;
        }

        .emp-side-header::after {
            content: '';
            position: absolute;
            top: -30px;
            right: -30px;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(255, 0, 137, .2) 0%, transparent 70%);
            pointer-events: none;
        }

        .emp-avatar-wrap {
            position: relative;
            display: inline-block;
        }

        .emp-avatar-edit {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 0, 137, .5);
            box-shadow: 0 0 0 5px rgba(255, 0, 137, .1);
            cursor: pointer;
            transition: filter .2s;
        }

        .emp-avatar-ini {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.6rem;
            color: #fff;
            border: 3px solid rgba(255, 0, 137, .5);
            box-shadow: 0 0 0 5px rgba(255, 0, 137, .1);
            cursor: pointer;
        }

        .emp-avatar-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, .55);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity .2s;
            cursor: pointer;
        }

        .emp-avatar-wrap:hover .emp-avatar-overlay {
            opacity: 1;
        }

        .emp-avatar-wrap:hover .emp-avatar-edit {
            filter: brightness(.7);
        }

        .emp-side-name {
            font-weight: 700;
            color: #fff;
            font-size: 1rem;
            margin: 10px 0 2px;
        }

        .emp-side-user {
            font-size: .8rem;
            color: rgba(255, 255, 255, .5);
        }

        .emp-side-body {
            padding: 16px 18px;
        }

        .emp-info-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color, #f0f0f8);
            font-size: .83rem;
        }

        .emp-info-row:last-child {
            border-bottom: none;
        }

        .emp-info-row i {
            color: #FF0089;
            width: 16px;
            flex-shrink: 0;
        }

        .emp-info-row span {
            opacity: .75;
        }

        /* ── Tabs ── */
        .edit-tabs .nav-link {
            color: var(--text-muted, #888);
            border: none;
            border-bottom: 2px solid transparent;
            padding: 10px 18px;
            font-size: .86rem;
            font-weight: 500;
            transition: all .2s;
            border-radius: 0;
        }

        .edit-tabs .nav-link:hover {
            color: #FF0089;
        }

        .edit-tabs .nav-link.active {
            color: #FF0089;
            border-bottom-color: #FF0089;
            background: transparent;
        }

        .edit-tabs .nav-link i {
            margin-right: 6px;
        }

        /* ── Secções do form ── */
        .form-section {
            background: var(--border-color, #f8f7fc);
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 18px;
            border: 1px solid var(--border-color, #e8e8f0);
        }

        .form-section-title {
            font-size: .82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #FF0089;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dark-mode .form-section {
            background: rgba(255, 255, 255, .03);
            border-color: var(--dark-border, #2e2e42);
        }

        /* ── Força da senha ── */
        .pw-bar {
            height: 4px;
            border-radius: 2px;
            background: var(--border-color, #e8e8f0);
            overflow: hidden;
            margin: 8px 0 4px;
        }

        .pw-fill {
            height: 100%;
            border-radius: 2px;
            width: 0;
            transition: width .3s, background .3s;
        }

        .pw-lbl {
            font-size: .73rem;
            color: var(--text-muted, #aaa);
        }

        /* ── Permissões ── */
        .perm-group-card {
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .perm-group-header {
            background: var(--border-color, #f4f4f8);
            padding: 9px 14px;
            font-size: .76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .perm-group-header i {
            color: #FF0089;
        }

        .dark-mode .perm-group-card {
            border-color: var(--dark-border, #2e2e42);
        }

        .dark-mode .perm-group-header {
            background: rgba(255, 255, 255, .04);
            color: var(--text-light, #e8e8f5);
        }

        .perm-body {
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        /* Toggle switch de permissão */
        .perm-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 0;
        }

        .perm-label {
            font-size: .84rem;
        }

        /* ── Actividade ── */
        .activity-item {
            display: flex;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color, #f0f0f8);
            font-size: .8rem;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #FF0089;
            margin-top: 5px;
            flex-shrink: 0;
        }

        /* ── required asterisk ── */
        .req::after {
            content: ' *';
            color: #ef4444;
        }

        /* ── Toggle visibilidade senha ── */
        .btn-pw-eye {
            background: none;
            border: 1.5px solid var(--border-color, #e8e8f0);
            border-left: none;
            padding: 0 12px;
            cursor: pointer;
            color: var(--text-muted, #888);
            transition: color .2s;
            border-radius: 0 var(--radius, 10px) var(--radius, 10px) 0;
        }

        .btn-pw-eye:hover {
            color: #FF0089;
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

                <!-- Cabeçalho -->
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1">
                            <i class="bi bi-person-gear me-2"></i>Editar Funcionário
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>" class="text-secondary">Home</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees"
                                        class="text-secondary">Funcionários</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable">
                                    <?php echo htmlspecialchars($emp['first_name']); ?>
                                </li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/view?id=<?php echo $id; ?>"
                            class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-eye me-1"></i>Visualizar
                        </a>
                        <?php if ($admin_role === 'super_admin' && $emp['role'] !== 'super_admin'): ?>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/delete?id=<?php echo $id; ?>"
                                class="btn btn-danger btn-sm">
                                <i class="bi bi-trash me-1"></i>Excluir
                            </a>
                        <?php endif; ?>
                    </div>
                </div>



                <div class="row g-4">

                    <!-- ══ Coluna esquerda — perfil + actividade ══ -->
                    <div class="col-xl-3 col-lg-4">

                        <!-- Card de perfil -->
                        <div class="emp-side-card">
                            <div class="emp-side-header">
                                <div class="emp-avatar-wrap" onclick="document.getElementById('inp-photo').click()">
                                    <?php if (!empty($emp['photo_employees'])): ?>
                                        <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/employees/<?php echo htmlspecialchars($emp['photo_employees']); ?>"
                                            alt="" class="emp-avatar-edit" id="avatar-preview"
                                            onerror="this.style.display='none';document.getElementById('avatar-ini').style.display='flex'" />
                                        <div class="emp-avatar-ini" id="avatar-ini"
                                            style="background:<?php echo $color; ?>;display:none">
                                            <?php echo $ini; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="emp-avatar-ini" id="avatar-ini"
                                            style="background:<?php echo $color; ?>">
                                            <?php echo $ini; ?>
                                        </div>
                                        <img id="avatar-preview" style="display:none" class="emp-avatar-edit" alt="" />
                                    <?php endif; ?>
                                    <div class="emp-avatar-overlay">
                                        <i class="bi bi-camera-fill text-white fs-4"></i>
                                    </div>
                                </div>
                                <div class="emp-side-name" id="side-name"><?php echo htmlspecialchars($fullname); ?>
                                </div>
                                <div class="emp-side-user" id="side-user">
                                    @<?php echo htmlspecialchars($emp['user_employees'] ?? '—'); ?></div>
                            </div>
                            <div class="emp-side-body">
                                <div class="emp-info-row">
                                    <i class="bi bi-envelope"></i>
                                    <span
                                        id="side-email"><?php echo htmlspecialchars($emp['email_employees']); ?></span>
                                </div>
                                <?php if ($emp['tel_employees']): ?>
                                    <div class="emp-info-row">
                                        <i class="bi bi-telephone"></i>
                                        <span><?php echo htmlspecialchars($emp['tel_employees']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="emp-info-row">
                                    <i class="bi bi-shield-lock"></i>
                                    <span><?php
                                            echo match ($emp['role']) {
                                                'super_admin' => 'Super Admin',
                                                'admin'       => 'Admin',
                                                'editor'      => 'Editor',
                                                'support'     => 'Suporte',
                                                default       => ucfirst($emp['role'])
                                            };
                                            ?></span>
                                </div>
                                <div class="emp-info-row">
                                    <i class="bi bi-calendar3"></i>
                                    <span>Desde <?php echo date('d/m/Y', strtotime($emp['creat_employees'])); ?></span>
                                </div>
                                <?php if ($emp['modif_employees']): ?>
                                    <div class="emp-info-row">
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Editado <?php echo adm_fmt_date($emp['modif_employees']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Actividade recente -->
                        <div class="emp-side-card">
                            <div class="emp-side-body" style="padding-top:14px">
                                <div
                                    style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#FF0089;margin-bottom:10px">
                                    <i class="bi bi-clock-history me-2"></i>Actividade Recente
                                </div>
                                <?php if (empty($audit_list)): ?>
                                    <p style="font-size:.8rem;opacity:.45;text-align:center;padding:12px 0">
                                        Sem registos de actividade.
                                    </p>
                                <?php else: ?>
                                    <?php foreach ($audit_list as $log): ?>
                                        <div class="activity-item">
                                            <div class="activity-dot"></div>
                                            <div>
                                                <div style="font-weight:600;font-size:.8rem">
                                                    <?php echo htmlspecialchars($log['action']); ?>
                                                </div>
                                                <div style="font-size:.73rem;opacity:.6">
                                                    <?php echo adm_fmt_date($log['creat_log']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div><!-- /col-xl-3 -->

                    <!-- ══ Coluna direita — tabs de edição ══ -->
                    <div class="col-xl-9 col-lg-8">
                        <div class="card p-0" style="border-radius:14px;overflow:hidden">

                            <!-- Tabs header -->
                            <div style="border-bottom:1px solid var(--border-color,#e8e8f0);padding:0 20px">
                                <ul class="nav edit-tabs" id="editTabs" role="tablist">
                                    <li class="nav-item">
                                        <button class="nav-link <?php echo $active_tab === 'profile' ? 'active' : ''; ?>"
                                            data-bs-toggle="tab" data-bs-target="#tab-profile" type="button">
                                            <i class="bi bi-person"></i>Perfil
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link <?php echo $active_tab === 'security' ? 'active' : ''; ?>"
                                            data-bs-toggle="tab" data-bs-target="#tab-security" type="button">
                                            <i class="bi bi-shield-lock"></i>Segurança
                                        </button>
                                    </li>
                                    <?php if ($admin_role === 'super_admin' || hasPermission($admin_id, 'employees.edit')): ?>
                                        <li class="nav-item">
                                            <button class="nav-link <?php echo $active_tab === 'permissions' ? 'active' : ''; ?>"
                                                data-bs-toggle="tab" data-bs-target="#tab-permissions" type="button">
                                                <i class="bi bi-key"></i>Permissões
                                            </button>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>

                            <div class="tab-content p-4" id="editTabsContent">

                                <!-- ══ TAB: PERFIL ══ -->
                                <div class="tab-pane fade <?php echo $active_tab === 'profile' ? 'show active' : ''; ?>"
                                    id="tab-profile" role="tabpanel">

                                    <?php if ($feedback && ($active_tab === 'profile' || !in_array($active_tab, ['security', 'permissions']))): ?>
                                        <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3"
                                            role="alert">
                                            <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                                            <?php echo htmlspecialchars($feedback[2]); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST"
                                        action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/edit-process"
                                        enctype="multipart/form-data" id="form-profile">
                                        <input type="hidden" name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                        <input type="hidden" name="action" value="update_profile" />
                                        <input type="hidden" name="id" value="<?php echo $id; ?>" />
                                        <!-- Upload de foto (oculto) -->
                                        <input type="file" id="inp-photo" name="photo"
                                            accept="image/jpeg,image/png,image/webp" style="display:none" />

                                        <!-- Informações básicas -->
                                        <div class="form-section">
                                            <div class="form-section-title">
                                                <i class="bi bi-person-badge"></i>Informações Básicas
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-2">
                                                    <label class="form-label req">Género</label>
                                                    <select class="form-select" name="gender" required>
                                                        <option value="M"
                                                            <?php echo $emp['gender'] === 'M' ? 'selected' : ''; ?>>Masculino
                                                        </option>
                                                        <option value="F"
                                                            <?php echo $emp['gender'] === 'F' ? 'selected' : ''; ?>>Feminino
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label req">Primeiro Nome</label>
                                                    <input type="text" class="form-control" name="first_name"
                                                        value="<?php echo htmlspecialchars($emp['first_name']); ?>"
                                                        maxlength="50" required id="inp-fname" />
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label">Apelido</label>
                                                    <input type="text" class="form-control" name="second_name"
                                                        value="<?php echo htmlspecialchars($emp['second_name'] ?? ''); ?>"
                                                        maxlength="80" id="inp-sname" />
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label req">Username</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">@</span>
                                                        <input type="text" class="form-control" name="username"
                                                            value="<?php echo htmlspecialchars($emp['user_employees'] ?? ''); ?>"
                                                            maxlength="60" required id="inp-user"
                                                            pattern="[a-zA-Z0-9._]{3,60}" />
                                                    </div>
                                                    <div class="form-text">Só letras, números, ponto e _</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Telefone</label>
                                                    <input type="tel" class="form-control" name="tel"
                                                        value="<?php echo htmlspecialchars($emp['tel_employees'] ?? ''); ?>"
                                                        maxlength="20" placeholder="+244 9xx xxx xxx" />
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="form-label req">E-mail principal</label>
                                                    <input type="email" class="form-control" name="email"
                                                        value="<?php echo htmlspecialchars($emp['email_employees']); ?>"
                                                        maxlength="255" required id="inp-email" />
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="form-label">E-mail alternativo</label>
                                                    <input type="email" class="form-control" name="email_other"
                                                        value="<?php echo htmlspecialchars($emp['email_employees_other'] ?? ''); ?>"
                                                        maxlength="255" placeholder="Opcional" />
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">País</label>
                                                    <select class="form-select" name="country">
                                                        <option value="">Selecione um país</option>
                                                        <?php
                                                        $countries = [
                                                            'AO' => 'Angola',
                                                            'PT' => 'Portugal',
                                                            'BR' => 'Brasil',
                                                            'MZ' => 'Moçambique',
                                                            'AF' => 'Afeganistão',
                                                            'AR' => 'Argentina',
                                                            'AM' => 'Arménia',
                                                            'AU' => 'Austrália',
                                                            'AT' => 'Áustria',
                                                            'BE' => 'Bélgica',
                                                            'BO' => 'Bolívia',
                                                            'BA' => 'Bósnia',
                                                            'BW' => 'Botsuana',
                                                            'CV' => 'Cabo Verde',
                                                            'CM' => 'Camarões',
                                                            'CA' => 'Canadá',
                                                            'CL' => 'Chile',
                                                            'CO' => 'Colômbia',
                                                            'CG' => 'Congo-Brazzaville',
                                                            'CD' => 'Congo-Kinshasa',
                                                            'HR' => 'Croácia',
                                                            'CU' => 'Cuba',
                                                            'DK' => 'Dinamarca',
                                                            'EG' => 'Egipto',
                                                            'AE' => 'Emirados Árabes',
                                                            'ES' => 'Espanha',
                                                            'US' => 'Estados Unidos',
                                                            'ET' => 'Etiópia',
                                                            'FR' => 'França',
                                                            'GH' => 'Gana',
                                                            'DE' => 'Alemanha',
                                                            'GN' => 'Guiné',
                                                            'GW' => 'Guiné-Bissau',
                                                            'IN' => 'Índia',
                                                            'ID' => 'Indonésia',
                                                            'IE' => 'Irlanda',
                                                            'IL' => 'Israel',
                                                            'IT' => 'Itália',
                                                            'JP' => 'Japão',
                                                            'KE' => 'Quénia',
                                                            'LU' => 'Luxemburgo',
                                                            'MY' => 'Malásia',
                                                            'ML' => 'Mali',
                                                            'MA' => 'Marrocos',
                                                            'MX' => 'México',
                                                            'MN' => 'Mongólia',
                                                            'NA' => 'Namíbia',
                                                            'NG' => 'Nigéria',
                                                            'NO' => 'Noruega',
                                                            'NZ' => 'Nova Zelândia',
                                                            'PK' => 'Paquistão',
                                                            'PY' => 'Paraguai',
                                                            'PE' => 'Peru',
                                                            'PL' => 'Polónia',
                                                            'RO' => 'Roménia',
                                                            'RW' => 'Ruanda',
                                                            'SN' => 'Senegal',
                                                            'SL' => 'Serra Leoa',
                                                            'ZA' => 'África do Sul',
                                                            'SS' => 'Sudão do Sul',
                                                            'SE' => 'Suécia',
                                                            'CH' => 'Suíça',
                                                            'TZ' => 'Tanzânia',
                                                            'TH' => 'Tailândia',
                                                            'TN' => 'Tunísia',
                                                            'TR' => 'Turquia',
                                                            'UG' => 'Uganda',
                                                            'UA' => 'Ucrânia',
                                                            'UY' => 'Uruguai',
                                                            'VE' => 'Venezuela',
                                                            'VN' => 'Vietname',
                                                            'ZM' => 'Zâmbia',
                                                            'ZW' => 'Zimbábue',
                                                        ];
                                                        asort($countries);
                                                        foreach ($countries as $code => $name):
                                                            $sel = (($emp['country_employees'] ?? '') === $code) ? 'selected' : '';
                                                        ?>
                                                            <option value="<?php echo $code; ?>" <?php echo $sel; ?>>
                                                                <?php echo htmlspecialchars($name); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Cidade</label>
                                                    <input type="text" class="form-control" name="city"
                                                        value="<?php echo htmlspecialchars($emp['city_employees'] ?? ''); ?>"
                                                        maxlength="80" placeholder="Ex: Luanda" />
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="form-label">Website / URL</label>
                                                    <input type="url" class="form-control" name="url"
                                                        value="<?php echo htmlspecialchars($emp['url_employees'] ?? ''); ?>"
                                                        maxlength="255" placeholder="https://..." />
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="form-label">Sobre</label>
                                                    <textarea class="form-control" name="about" rows="3"
                                                        maxlength="1000"><?php echo htmlspecialchars($emp['about_employees'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Cargo e estado -->
                                        <div class="form-section">
                                            <div class="form-section-title">
                                                <i class="bi bi-shield-lock"></i>Cargo e Estado
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label req">Role / Cargo</label>
                                                    <select class="form-select" name="role" id="inp-role" required>
                                                        <?php if ($admin_role === 'super_admin'): ?>
                                                            <option value="super_admin"
                                                                <?php echo $emp['role'] === 'super_admin' ? 'selected' : ''; ?>>
                                                                Super Admin — acesso total
                                                            </option>
                                                            <option value="admin"
                                                                <?php echo $emp['role'] === 'admin' ? 'selected' : ''; ?>>
                                                                Admin — gestão geral
                                                            </option>
                                                        <?php else: ?>
                                                            <option value="admin"
                                                                <?php echo $emp['role'] === 'admin' ? 'selected' : ''; ?>
                                                                disabled>
                                                                Admin (requer Super Admin)
                                                            </option>
                                                        <?php endif; ?>
                                                        <option value="editor"
                                                            <?php echo $emp['role'] === 'editor' ? 'selected' : ''; ?>>Editor
                                                            — músicas e lançamentos</option>
                                                        <option value="support"
                                                            <?php echo $emp['role'] === 'support' ? 'selected' : ''; ?>>
                                                            Suporte — tickets e atendimento</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label req">Estado da Conta</label>
                                                    <select class="form-select" name="status" required>
                                                        <option value="active"
                                                            <?php echo $emp['status_employees'] === 'active' ? 'selected' : ''; ?>>
                                                            Activo</option>
                                                        <option value="processing"
                                                            <?php echo $emp['status_employees'] === 'processing' ? 'selected' : ''; ?>>
                                                            Em processo</option>
                                                        <option value="blocked"
                                                            <?php echo $emp['status_employees'] === 'blocked' ? 'selected' : ''; ?>>
                                                            Bloqueado</option>
                                                        <option value="suspended"
                                                            <?php echo $emp['status_employees'] === 'suspended' ? 'selected' : ''; ?>>
                                                            Suspenso</option>
                                                        <option value="inactive"
                                                            <?php echo $emp['status_employees'] === 'inactive' ? 'selected' : ''; ?>>
                                                            Inactivo</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Foto de perfil -->
                                        <div class="form-section">
                                            <div class="form-section-title">
                                                <i class="bi bi-image"></i>Foto de Perfil
                                            </div>
                                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                                    onclick="document.getElementById('inp-photo').click()">
                                                    <i class="bi bi-upload me-1"></i>Escolher foto
                                                </button>
                                                <span style="font-size:.78rem;opacity:.6" id="photo-filename">
                                                    Nenhum ficheiro seleccionado
                                                </span>
                                                <?php if (!empty($emp['photo_employees'])): ?>
                                                    <button type="submit" name="action" value="remove_photo"
                                                        class="btn btn-outline-danger btn-sm"
                                                        onclick="return confirm('Remover foto de perfil?')">
                                                        <i class="bi bi-trash me-1"></i>Remover foto actual
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <div class="form-text mt-1">JPG, PNG ou WebP · Máximo 2MB.</div>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                            <button type="reset" class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Repor
                                            </button>
                                            <button type="submit" class="btn btn-sm text-white"
                                                style="background:#FF0089;border-color:#FF0089" id="btn-save-profile">
                                                <span class="spinner-border spinner-border-sm d-none me-1"
                                                    id="spin-profile"></span>
                                                <i class="bi bi-save me-1"></i>Guardar Alterações
                                            </button>
                                        </div>
                                    </form>
                                </div><!-- /tab-profile -->

                                <!-- ══ TAB: SEGURANÇA ══ -->
                                <div class="tab-pane fade <?php echo $active_tab === 'security' ? 'show active' : ''; ?>"
                                    id="tab-security" role="tabpanel">

                                    <?php if ($feedback && $active_tab === 'security'): ?>
                                        <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3"
                                            role="alert">
                                            <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                                            <?php echo htmlspecialchars($feedback[2]); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Redefinir senha -->
                                    <div class="form-section mb-4">
                                        <div class="form-section-title">
                                            <i class="bi bi-key"></i>Redefinir Senha
                                        </div>
                                        <div class="alert alert-warning mb-3" style="font-size:.83rem">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            <strong>Atenção:</strong> Ao redefinir a senha, o funcionário será
                                            notificado por e-mail
                                            e todas as sessões activas serão encerradas.
                                        </div>
                                        <form method="POST"
                                            action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/edit-process"
                                            id="form-pw-reset">
                                            <input type="hidden" name="csrf_token"
                                                value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                            <input type="hidden" name="action" value="reset_password" />
                                            <input type="hidden" name="id" value="<?php echo $id; ?>" />
                                            <div class="row g-3">
                                                <div class="col-md-8">
                                                    <label class="form-label req">Nova Senha</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control font-monospace"
                                                            name="new_password" id="inp-new-pw"
                                                            placeholder="Clica em Gerar →" readonly required />
                                                        <button type="button" class="btn btn-outline-secondary"
                                                            id="btn-gen-pw">
                                                            <i class="bi bi-arrow-repeat me-1"></i>Gerar
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary"
                                                            id="btn-copy-pw" title="Copiar senha">
                                                            <i class="bi bi-clipboard"></i>
                                                        </button>
                                                    </div>
                                                    <div class="pw-bar">
                                                        <div class="pw-fill" id="pw-fill"></div>
                                                    </div>
                                                    <div class="pw-lbl" id="pw-lbl">Gera uma senha para continuar</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Força</label>
                                                    <div class="alert py-2 text-center mb-0" id="pw-str-txt"
                                                        style="font-size:.82rem">—</div>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <button type="submit" class="btn btn-warning btn-sm text-dark"
                                                    id="btn-reset-pw" disabled>
                                                    <i class="bi bi-send me-1"></i>Enviar nova senha por e-mail
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Limpar tentativas de login -->
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="bi bi-shield-x"></i>Acções de Segurança
                                        </div>
                                        <form method="POST"
                                            action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/edit-process"
                                            class="d-inline">
                                            <input type="hidden" name="csrf_token"
                                                value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                            <input type="hidden" name="action" value="clear_attempts" />
                                            <input type="hidden" name="id" value="<?php echo $id; ?>" />
                                            <button type="submit" class="btn btn-outline-secondary btn-sm me-2">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>
                                                Limpar tentativas de login falhadas
                                            </button>
                                        </form>
                                        <form method="POST"
                                            action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/edit-process"
                                            class="d-inline">
                                            <input type="hidden" name="csrf_token"
                                                value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                            <input type="hidden" name="action" value="revoke_sessions" />
                                            <input type="hidden" name="id" value="<?php echo $id; ?>" />
                                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                                onclick="return confirm('Terminar todas as sessões activas deste funcionário?')">
                                                <i class="bi bi-box-arrow-right me-1"></i>
                                                Terminar todas as sessões
                                            </button>
                                        </form>
                                    </div>

                                </div><!-- /tab-security -->

                                <!-- ══ TAB: PERMISSÕES ══ -->
                                <!-- ══ TAB: PERMISSÕES ══ -->
                                <?php if ($admin_role === 'super_admin' || hasPermission($admin_id, 'employees.edit')): ?>
                                    <div class="tab-pane fade <?php echo $active_tab === 'permissions' ? 'show active' : ''; ?>"
                                        id="tab-permissions" role="tabpanel">

                                        <?php if ($feedback && $active_tab === 'permissions'): ?>
                                            <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3"
                                                role="alert">
                                                <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                                                <?php echo htmlspecialchars($feedback[2]); ?>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($emp['role'] === 'super_admin'): ?>
                                            <div class="alert alert-info" style="font-size:.84rem">
                                                <i class="bi bi-info-circle me-2"></i>
                                                O <strong>Super Admin</strong> tem acesso total por definição.
                                                As permissões explícitas não se aplicam a este role.
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-light mb-3"
                                                style="font-size:.82rem;border-left:3px solid #FF0089">
                                                <i class="bi bi-info-circle me-1"></i>
                                                As permissões abaixo <strong>sobrepõem</strong> os padrões do role
                                                <code><?php echo $emp['role']; ?></code>.
                                                Deixa um toggle sem definir para usar os padrões.
                                            </div>

                                            <form method="POST"
                                                action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/edit-process"
                                                id="form-perms">
                                                <input type="hidden" name="csrf_token"
                                                    value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                                                <input type="hidden" name="action" value="update_permissions" />
                                                <input type="hidden" name="id" value="<?php echo $id; ?>" />

                                                <?php foreach ($all_permissions as $group_key => $group): ?>
                                                    <div class="perm-group-card mb-3">
                                                        <div class="perm-group-header d-flex align-items-center gap-2 mb-2">
                                                            <i class="bi <?php echo $group['icon']; ?>"
                                                                style="font-size:1.2rem;color:#FF0089"></i>
                                                            <span class="fw-semibold"><?php echo $group['label']; ?></span>
                                                            <span class="text-muted small ms-2" data-bs-toggle="tooltip"
                                                                title="<?php echo htmlspecialchars($group['desc'] ?? ''); ?>">
                                                                <i class="bi bi-question-circle"></i>
                                                            </span>
                                                        </div>
                                                        <div class="perm-body ps-4">
                                                            <?php foreach ($group['perms'] as $perm_key => $perm_label):
                                                                $current = $perm_map[$perm_key] ?? null; // 1 = concedido, 0 = negado, null = padrão
                                                            ?>
                                                                <div
                                                                    class="perm-toggle d-flex justify-content-between align-items-center py-2 border-bottom">
                                                                    <span
                                                                        class="perm-label"><?php echo htmlspecialchars($perm_label); ?></span>
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <select class="form-select form-select-sm"
                                                                            name="perm[<?php echo htmlspecialchars($perm_key); ?>]"
                                                                            style="width:130px;font-size:.78rem">
                                                                            <option value=""
                                                                                <?php echo $current === null ? 'selected' : ''; ?>>
                                                                                ◉ Padrão (<?php echo $emp['role']; ?>)
                                                                            </option>
                                                                            <option value="1"
                                                                                <?php echo $current === 1 ? 'selected' : ''; ?>>
                                                                                ✓ Conceder
                                                                            </option>
                                                                            <option value="0"
                                                                                <?php echo $current === 0 ? 'selected' : ''; ?>>
                                                                                ✗ Negar
                                                                            </option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>

                                                <div class="d-flex justify-content-between mt-4">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                                        onclick="resetPermissions()">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i>
                                                        Repor tudo para Padrão
                                                    </button>
                                                    <button type="submit" class="btn btn-sm text-white"
                                                        style="background:#FF0089;border-color:#FF0089">
                                                        <i class="bi bi-save me-1"></i>Guardar Permissões
                                                    </button>
                                                </div>
                                            </form>

                                            <script>
                                                function resetPermissions() {
                                                    document.querySelectorAll('#form-perms select').forEach(select => select.value =
                                                        '');
                                                }
                                                // Activar tooltips
                                                document.addEventListener('DOMContentLoaded', function() {
                                                    var tooltipTriggerList = [].slice.call(document.querySelectorAll(
                                                        '[data-bs-toggle="tooltip"]'));
                                                    tooltipTriggerList.map(function(tooltipTriggerEl) {
                                                        return new bootstrap.Tooltip(tooltipTriggerEl);
                                                    });
                                                });
                                            </script>

                                        <?php endif; ?>
                                    </div><!-- /tab-permissions -->
                                <?php endif; ?>

                            </div><!-- /tab-content -->
                        </div>
                    </div><!-- /col-xl-9 -->

                </div><!-- /row -->
            </div>
        </div>
    </div>

  <footer>
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-2">© <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos os direitos reservados.
                    </p>
                </div>
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
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.min.js"></script>
    <script>
        window.__BASE_URL__ = '<?php echo APP_URL; ?>';
        window.__ADMIN_PATH__ = '<?php echo ADMIN_PATH; ?>';

        document.addEventListener('DOMContentLoaded', function() {

            // ── Preview de foto ──
            var inpPhoto = document.getElementById('inp-photo');
            if (inpPhoto) {
                inpPhoto.addEventListener('change', function() {
                    var file = this.files[0];
                    if (!file) return;
                    document.getElementById('photo-filename').textContent = file.name;
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var prev = document.getElementById('avatar-preview');
                        var ini = document.getElementById('avatar-ini');
                        if (prev) {
                            prev.src = e.target.result;
                            prev.style.display = 'block';
                        }
                        if (ini) ini.style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                });
            }

            // ── Preview de nome no side card ──
            var inpFname = document.getElementById('inp-fname');
            var inpSname = document.getElementById('inp-sname');
            var inpUser = document.getElementById('inp-user');
            var inpEmail = document.getElementById('inp-email');
            var sideN = document.getElementById('side-name');
            var sideU = document.getElementById('side-user');
            var sideE = document.getElementById('side-email');

            function updateSide() {
                var n = (inpFname ? inpFname.value.trim() : '') + ' ' + (inpSname ? inpSname.value.trim() : '');
                if (sideN) sideN.textContent = n.trim() || '—';
                if (sideU && inpUser) sideU.textContent = '@' + (inpUser.value || '—');
                if (sideE && inpEmail) sideE.textContent = inpEmail.value || '—';
            }

            [inpFname, inpSname, inpUser, inpEmail].forEach(function(el) {
                if (el) el.addEventListener('input', updateSide);
            });

            // ── Gerar senha ──
            function generatePassword() {
                var u = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                var l = 'abcdefghijklmnopqrstuvwxyz';
                var d = '0123456789';
                var s = '!@#$%^&*-_+=?';
                var a = u + l + d + s;
                var pw = u[~~(Math.random() * u.length)] + u[~~(Math.random() * u.length)] +
                    l[~~(Math.random() * l.length)] + l[~~(Math.random() * l.length)] +
                    d[~~(Math.random() * d.length)] + d[~~(Math.random() * d.length)] +
                    s[~~(Math.random() * s.length)] + s[~~(Math.random() * s.length)];
                for (var i = pw.length; i < 16; i++) pw += a[~~(Math.random() * a.length)];
                return pw.split('').sort(function() {
                    return .5 - Math.random();
                }).join('');
            }

            function evalStrength(pw) {
                var sc = 0;
                if (pw.length >= 12) sc++;
                if (pw.length >= 16) sc++;
                if (/[A-Z]/.test(pw)) sc++;
                if (/[a-z]/.test(pw)) sc++;
                if (/[0-9]/.test(pw)) sc++;
                if (/[!@#$%^&*\-_+=?]/.test(pw)) sc += 2;
                return Math.min(sc, 5);
            }

            function updateStrength(pw) {
                var colors = ['#e8e8f0', '#ef4444', '#f97316', '#eab308', '#22c55e', '#16a34a'];
                var labels = ['—', 'Muito fraca', 'Fraca', 'Razoável', 'Forte', 'Muito forte'];
                var cls = ['light', 'danger', 'warning', 'warning', 'success', 'success'];
                var s = evalStrength(pw);
                var fill = document.getElementById('pw-fill');
                var lbl = document.getElementById('pw-lbl');
                var str = document.getElementById('pw-str-txt');
                var btn = document.getElementById('btn-reset-pw');
                if (fill) {
                    fill.style.width = (s * 20) + '%';
                    fill.style.background = colors[s];
                }
                if (lbl) {
                    lbl.textContent = labels[s];
                    lbl.style.color = colors[s];
                }
                if (str) {
                    str.textContent = labels[s];
                    str.className = 'alert py-2 text-center mb-0 alert-' + cls[s];
                    str.style.fontSize = '.82rem';
                }
                if (btn) btn.disabled = s < 3;
            }

            var btnGen = document.getElementById('btn-gen-pw');
            var inpPw = document.getElementById('inp-new-pw');
            if (btnGen && inpPw) {
                btnGen.addEventListener('click', function() {
                    var pw = generatePassword();
                    inpPw.value = pw;
                    updateStrength(pw);
                });
            }

            var btnCopy = document.getElementById('btn-copy-pw');
            if (btnCopy && inpPw) {
                btnCopy.addEventListener('click', function() {
                    if (!inpPw.value) return;
                    navigator.clipboard.writeText(inpPw.value).then(function() {
                        btnCopy.innerHTML = '<i class="bi bi-check"></i>';
                        setTimeout(function() {
                            btnCopy.innerHTML = '<i class="bi bi-clipboard"></i>';
                        }, 2000);
                    });
                });
            }

            // ── Spinner no submit do perfil ──
            var formProfile = document.getElementById('form-profile');
            if (formProfile) {
                formProfile.addEventListener('submit', function() {
                    var spin = document.getElementById('spin-profile');
                    var btn = document.getElementById('btn-save-profile');
                    if (spin) spin.classList.remove('d-none');
                    if (btn) btn.disabled = true;
                });
            }

        });
    </script>
</body>

</html>