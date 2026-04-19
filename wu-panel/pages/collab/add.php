<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Adicionar Colaborador
// Arquivo: wu-panel/pages/collab/add.php
// Rota:    wu-panel/collab/add
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'collaborators.edit');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'dupe_email' => ['danger', 'bi-envelope', 'Já existe um colaborador com este e-mail para este utilizador.'],
    'dupe_user'  => ['danger', 'bi-person', 'Este utilizador já é um colaborador.'],
    'error'      => ['danger', 'bi-x-circle', 'Ocorreu um erro. Tenta novamente.'],
    default      => null,
};

// Lista de utilizadores (para select)
$users = $db->query("
    SELECT id_users, CONCAT(first_name, ' ', COALESCE(second_name, '')) AS name, email_user
    FROM _users
    WHERE status_user = 'active'
    ORDER BY first_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title>Adicionar Colaborador — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        .ac-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 14px;
            padding: 22px 24px;
            margin-bottom: 20px;
        }

        .ac-card-title {
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

        .ac-form-label {
            font-size: .78rem;
            font-weight: 600;
            margin-bottom: 5px;
            opacity: .7;
        }

        .ac-hint {
            font-size: .72rem;
            opacity: .45;
            margin-top: 3px;
        }

        .select2-container--default .select2-selection--single {
            background-color: var(--input-bg, #fff);
            border-color: var(--border-color, #ced4da);
            border-radius: 0.375rem;
            height: calc(2.25rem + 2px);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 2.25rem;
            color: var(--text-color, #212529);
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 2.25rem;
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
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1"><i class="bi bi-person-plus me-2"></i>Adicionar Colaborador</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab"
                                        class="text-secondary">Colaboradores</a></li>
                                <li class="breadcrumb-item active text-white-stable">Adicionar</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Voltar
                        </a>
                    </div>
                </div>

                <?php if ($feedback): ?>
                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3">
                        <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                        <?php echo htmlspecialchars($feedback[2]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab/add-process"
                    id="form-collab">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
                    <input type="hidden" name="action" value="add_collaborator">

                    <div class="row g-4">
                        <div class="col-lg-8">
                            <!-- Dados Pessoais -->
                            <div class="ac-card">
                                <div class="ac-card-title"><i class="bi bi-person"></i> Dados Pessoais</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="ac-form-label">Primeiro Nome *</label>
                                        <input type="text" class="form-control" name="first_name" required />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="ac-form-label">Apelido</label>
                                        <input type="text" class="form-control" name="second_name" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="ac-form-label">E-mail *</label>
                                        <input type="email" class="form-control" name="email_collab" required />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="ac-form-label">Telefone</label>
                                        <input type="text" class="form-control" name="tel_collab" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="ac-form-label">Função</label>
                                        <select class="form-select" name="role_collab">
                                            <option value="admin">Administrador</option>
                                            <option value="editor" selected>Editor</option>
                                            <option value="analyst">Analista</option>
                                            <option value="support">Suporte</option>
                                        </select>
                                        <div class="ac-hint">
                                            Administrador: acesso total à conta.<br>
                                            Editor: pode criar/editar conteúdos.<br>
                                            Analista: apenas visualização de estatísticas.<br>
                                            Suporte: pode responder a tickets.
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="ac-form-label">Notas internas</label>
                                        <textarea class="form-control" name="notes" rows="3"
                                            placeholder="Informações internas sobre este colaborador..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Foto de Perfil -->
                            <div class="ac-card">
                                <div class="ac-card-title"><i class="bi bi-image"></i> Foto de Perfil</div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="ac-form-label">URL da foto</label>
                                        <input type="url" class="form-control" name="photo_url"
                                            placeholder="https://exemplo.com/foto.jpg" />
                                        <div class="ac-hint">Cole o URL de uma imagem pública. Deixa em branco para não
                                            definir.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <!-- Proprietário -->
                            <div class="ac-card">
                                <div class="ac-card-title"><i class="bi bi-person-circle"></i> Proprietário</div>
                                <select class="form-select select2" name="id_users" required>
                                    <option value="">Selecionar utilizador</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?php echo $u['id_users']; ?>">
                                            <?php echo htmlspecialchars($u['name'] ?: $u['email_user']); ?>
                                            (<?php echo htmlspecialchars($u['email_user']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="ac-hint mt-2">O utilizador dono da conta (que irá gerir este colaborador).
                                </div>
                            </div>

                            <!-- Ações -->
                            <div class="ac-card">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn text-white"
                                        style="background:#FF0089;border-color:#FF0089">
                                        <i class="bi bi-envelope-paper"></i> Criar e Enviar Convite
                                    </button>
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/collab"
                                        class="btn btn-outline-secondary">Cancelar</a>
                                </div>
                                <div class="ac-hint mt-2">
                                    Será gerada uma senha temporária e enviado um e-mail de convite para o colaborador.
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="col-12 text-center py-2">
                <p class="mb-0">© <?php echo date('Y'); ?> Wasom Upfy. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    <div class="page-loader" id="pageLoader">
        <div class="loader-content"><img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png"
                class="loader-image" alt="" />
            <div class="loader-progress"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Selecionar utilizador',
                allowClear: true
            });
        });
    </script>
</body>

</html>