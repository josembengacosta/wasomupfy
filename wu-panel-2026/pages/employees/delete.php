<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Excluir / Bloquear Funcionário
// Arquivo: admin/pages/employees/delete.php
// Rota:    admin/employees/delete?id=X  (GET = confirmação)
//          admin/employees/delete       (POST = acção)
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'employees.edit');

// ── POST: executar a acção ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateAdminCsrf($_POST['csrf_token'] ?? '')) {
        adminRedirect('/' . ADMIN_PATH . '/employees', ['msg' => 'error']);
    }
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

    $action = $_POST['action'] ?? '';
    $emp_id = (int)($_POST['id'] ?? 0);

    if (!$emp_id) adminRedirect('/' . ADMIN_PATH . '/employees');

    // Carregar alvo
    $row = $db->prepare("SELECT id_employees, role, status_employees, first_name, second_name FROM _employees WHERE id_employees=? LIMIT 1");
    $row->execute([$emp_id]);
    $target = $row->fetch();

    if (!$target) adminRedirect('/' . ADMIN_PATH . '/employees');

    // Protecções
    if ($emp_id === $admin_id)                                   adminRedirect('/' . ADMIN_PATH . '/employees');
    if ($target['role'] === 'super_admin' && $admin_role !== 'super_admin') adminRedirect('/' . ADMIN_PATH . '/employees');

    $back_view = '/' . ADMIN_PATH . '/employees/view?id=' . $emp_id;

    switch ($action) {

        case 'block':
            $db->prepare("UPDATE _employees SET status_employees='blocked' WHERE id_employees=?")
               ->execute([$emp_id]);
            // Invalidar sessões activas
            $db->prepare("UPDATE _employees_security SET remember_token=NULL WHERE id_employees=?")
               ->execute([$emp_id]);
            logAudit($admin_id, null, 'employees.blocked', 'employees', $emp_id, ['status'=>$target['status_employees']], ['status'=>'blocked']);
            adminRedirect($back_view . '&msg=blocked');
            break;

        case 'unblock':
            $db->prepare("UPDATE _employees SET status_employees='active' WHERE id_employees=?")
               ->execute([$emp_id]);
            logAudit($admin_id, null, 'employees.unblocked', 'employees', $emp_id, ['status'=>$target['status_employees']], ['status'=>'active']);
            adminRedirect($back_view . '&msg=unblocked');
            break;

        case 'delete':
            if ($admin_role !== 'super_admin') adminRedirect('/' . ADMIN_PATH . '/employees');
            if ($target['role'] === 'super_admin')  adminRedirect('/' . ADMIN_PATH . '/employees');

            // Apagar foto física se existir
            $photo_row = $db->prepare("SELECT photo_employees FROM _employees WHERE id_employees=? LIMIT 1");
            $photo_row->execute([$emp_id]);
            $photo = $photo_row->fetchColumn();
            if ($photo) {
                $photo_path = __DIR__ . '/../../../assets/comprovantes/uploads/employees/' . $photo;
                if (file_exists($photo_path)) @unlink($photo_path);
            }

            $db->prepare("DELETE FROM _employees WHERE id_employees=?")->execute([$emp_id]);
            logAudit($admin_id, null, 'employees.deleted', 'employees', $emp_id, null, null);
            adminRedirect('/' . ADMIN_PATH . '/employees', ['msg' => 'deleted']);
            break;

        default:
            adminRedirect('/' . ADMIN_PATH . '/employees');
    }
}

// ── GET: página de confirmação de exclusão ──────────────
$id = (int)($_GET['id'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/employees');

// Só super_admin pode excluir
if ($admin_role !== 'super_admin') {
    adminRedirect('/' . ADMIN_PATH . '/employees/view?id=' . $id);
}

$stmt = $db->prepare("
    SELECT e.id_employees, e.first_name, e.second_name, e.user_employees,
           e.email_employees, e.role, e.status_employees, e.photo_employees,
           e.creat_employees
    FROM _employees e
    WHERE e.id_employees = ?
    LIMIT 1
");
$stmt->execute([$id]);
$emp = $stmt->fetch();

if (!$emp) adminRedirect('/' . ADMIN_PATH . '/employees');
if ($emp['role'] === 'super_admin') adminRedirect('/' . ADMIN_PATH . '/employees');
if ($id === $admin_id)             adminRedirect('/' . ADMIN_PATH . '/employees');

$fullname = trim($emp['first_name'] . ' ' . ($emp['second_name'] ?? ''));
$ini      = adm_initials($emp['first_name'], $emp['second_name'] ?? '');
$color    = adm_avatar_color($fullname);
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <title>Excluir Funcionário — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/scrollue.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <style>
    .del-avatar {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.5rem;
        color: #fff;
        flex-shrink: 0;
    }

    .del-avatar img {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        object-fit: cover;
    }

    .danger-zone {
        border: 2px solid rgba(239, 68, 68, .3);
        border-radius: 12px;
        padding: 20px;
        background: rgba(239, 68, 68, .04);
    }

    .checklist-del {
        list-style: none;
        padding: 0;
        margin: 12px 0 0;
    }

    .checklist-del li {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: .84rem;
        margin-bottom: 6px;
        color: #991b1b;
    }

    .checklist-del li i {
        margin-top: 2px;
        flex-shrink: 0;
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php require_once __DIR__ . '/../../include/sidebar.php'; ?>

        <div class="content w-100" id="mainContent">
            <?php require_once __DIR__ . '/../../include/navbar.php'; ?>

            <div class="container-fluid p-0" style="max-width:560px;margin:0 auto">

                <div class="row mb-3 mt-2">
                    <div class="col-auto">
                        <h2 class="h4 mb-1">
                            <i class="bi bi-trash3 me-2 text-danger"></i>Excluir Funcionário
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL.'/'.ADMIN_PATH; ?>/employees"
                                        class="text-secondary">Funcionários</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable">Excluir</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                <!-- Identificação do funcionário -->
                <div class="card mb-4">
                    <div class="d-flex align-items-center gap-3 p-1">
                        <?php if (!empty($emp['photo_employees'])): ?>
                        <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/employees/<?php echo htmlspecialchars($emp['photo_employees']); ?>"
                            class="del-avatar" alt="" style="border:2px solid var(--border-color)" />
                        <?php else: ?>
                        <div class="del-avatar" style="background:<?php echo $color; ?>">
                            <?php echo $ini; ?>
                        </div>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:700;font-size:1rem"><?php echo htmlspecialchars($fullname); ?></div>
                            <div style="font-size:.82rem;opacity:.6">
                                @<?php echo htmlspecialchars($emp['user_employees'] ?? '—'); ?>
                                &nbsp;·&nbsp;<?php echo htmlspecialchars($emp['email_employees']); ?>
                            </div>
                            <div class="mt-1">
                                <?php
                            $role_badge = match($emp['role']) {
                                'admin'   => '<span class="badge bg-primary">Admin</span>',
                                'editor'  => '<span class="badge bg-info text-dark">Editor</span>',
                                'support' => '<span class="badge bg-secondary">Suporte</span>',
                                default   => '<span class="badge bg-dark">' . htmlspecialchars($emp['role']) . '</span>',
                            };
                            echo $role_badge;
                            ?>
                                <span class="badge bg-secondary ms-1" style="font-size:.65rem">
                                    Membro desde <?php echo date('d/m/Y', strtotime($emp['creat_employees'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zona de perigo -->
                <div class="danger-zone mb-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-exclamation-octagon-fill text-danger fs-5"></i>
                        <strong style="color:#991b1b">Esta acção é irreversível</strong>
                    </div>
                    <p style="font-size:.84rem;color:#991b1b;margin-bottom:0">
                        Ao confirmar, os seguintes dados serão <strong>eliminados permanentemente</strong>:
                    </p>
                    <ul class="checklist-del">
                        <li><i class="bi bi-x-circle-fill"></i>Perfil completo e credenciais de acesso</li>
                        <li><i class="bi bi-x-circle-fill"></i>Registo de segurança e tokens de sessão</li>
                        <li><i class="bi bi-x-circle-fill"></i>Permissões explícitas atribuídas</li>
                        <li><i class="bi bi-x-circle-fill"></i>Foto de perfil (ficheiro físico no servidor)</li>
                    </ul>
                    <div class="alert alert-warning mt-3 mb-0" style="font-size:.8rem">
                        <i class="bi bi-info-circle me-1"></i>
                        Os registos de auditoria e actividade <strong>não são eliminados</strong>
                        — ficam no log histórico para referência futura.
                    </div>
                </div>

                <!-- Formulário de confirmação -->
                <form method="POST" action="<?php echo APP_URL.'/'.ADMIN_PATH; ?>/employees/delete" id="form-del"
                    onsubmit="return validateDel()">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                    <input type="hidden" name="action" value="delete" />
                    <input type="hidden" name="id" value="<?php echo $id; ?>" />

                    <div class="mb-3">
                        <label class="form-label" style="font-size:.84rem;font-weight:600">
                            Para confirmar, escreve o nome completo do funcionário:
                        </label>
                        <input type="text" class="form-control" id="inp-confirm-name"
                            placeholder="<?php echo htmlspecialchars($fullname); ?>" autocomplete="off" />
                        <div id="name-err" style="font-size:.76rem;color:#ef4444;margin-top:4px;display:none">
                            <i class="bi bi-x-circle me-1"></i>O nome não corresponde.
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="<?php echo APP_URL.'/'.ADMIN_PATH; ?>/employees/view?id=<?php echo $id; ?>"
                            class="btn btn-outline-secondary flex-grow-1">
                            <i class="bi bi-arrow-left me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-danger flex-grow-1" id="btn-del" disabled>
                            <span class="spinner-border spinner-border-sm d-none me-1" id="spin-del"></span>
                            <i class="bi bi-trash3 me-1"></i>Excluir definitivamente
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-2">© 2026 Wasom Upfy. Todos os direitos reservados.</p>
                    <a href="<?php echo APP_URL; ?>/page/politicies/terms" class="me-2">Termos de Uso</a>
                    <a href="<?php echo APP_URL; ?>/page/politicies/privacy" class="me-2">Privacidade</a>
                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/support">Suporte</a>
                </div>
            </div>
        </div>
    </footer>

    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="../assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="Carregando" />
            <div class="loader-progress"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
    var EXPECTED_NAME = <?php echo json_encode($fullname); ?>;

    document.getElementById('inp-confirm-name').addEventListener('input', function() {
        var ok = this.value.trim().toLowerCase() === EXPECTED_NAME.toLowerCase();
        document.getElementById('btn-del').disabled = !ok;
        document.getElementById('name-err').style.display =
            (this.value.length > 0 && !ok) ? 'block' : 'none';
    });

    function validateDel() {
        var ok = document.getElementById('inp-confirm-name').value.trim().toLowerCase() ===
            EXPECTED_NAME.toLowerCase();
        if (!ok) {
            document.getElementById('name-err').style.display = 'block';
            return false;
        }
        document.getElementById('spin-del').classList.remove('d-none');
        document.getElementById('btn-del').disabled = true;
        return true;
    }
    </script>
</body>

</html>