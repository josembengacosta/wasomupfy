<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Editar Conta Bancária
// Arquivo: wu-panel-2026/pages/accounts/edit.php
// Rota:    wu-panel-2026/accounts/edit?id=X
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.edit');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/accounts');

$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'updated' => ['success', 'bi-check-circle', 'Conta actualizada com sucesso.'],
    'error'   => ['danger',  'bi-x-circle',     'Ocorreu um erro. Tenta novamente.'],
    default   => null,
};

// Buscar dados da conta
$stmt = $db->prepare("
    SELECT a.*, u.id_users, u.first_name, u.second_name, u.email_user
    FROM _account a
    LEFT JOIN _users u ON u.id_users = a.id_users
    WHERE a.id_account = ?
");
$stmt->execute([$id]);
$account = $stmt->fetch();
if (!$account) adminRedirect('/' . ADMIN_PATH . '/accounts?msg=not_found');

$user_name = trim(($account['first_name'] ?? '') . ' ' . ($account['second_name'] ?? ''));
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title>Editar Conta #<?php echo $id; ?> — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
    .ae-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 14px;
        padding: 22px 24px;
        margin-bottom: 20px;
    }

    .ae-card-title {
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

    .ae-form-label {
        font-size: .78rem;
        font-weight: 600;
        margin-bottom: 5px;
        opacity: .7;
    }

    .ae-hint {
        font-size: .72rem;
        opacity: .45;
        margin-top: 3px;
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
                        <h2 class="h4 mb-1"><i class="bi bi-pencil-square me-2"></i>Editar Conta #<?php echo $id; ?>
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/accounts"
                                        class="text-secondary">Contas</a></li>
                                <li class="breadcrumb-item"><a
                                        href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/accounts/view?id=<?php echo $id; ?>"
                                        class="text-secondary">#<?php echo $id; ?></a></li>
                                <li class="breadcrumb-item active text-white-stable">Editar</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/accounts/view?id=<?php echo $id; ?>"
                            class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Visualizar</a>
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/accounts"
                            class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
                    </div>
                </div>

                <?php if ($feedback): ?>
                <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3">
                    <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                    <?php echo htmlspecialchars($feedback[2]); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/accounts/edit-process"
                    id="form-account">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
                    <input type="hidden" name="id_account" value="<?php echo $id; ?>">
                    <input type="hidden" name="action" value="update_account">

                    <div class="row g-4">
                        <div class="col-lg-8">
                            <!-- Dados da Conta -->
                            <div class="ae-card">
                                <div class="ae-card-title"><i class="bi bi-bank"></i> Dados da Conta</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="ae-form-label">Titular da Conta *</label>
                                        <input type="text" class="form-control" name="full_name_account"
                                            value="<?php echo htmlspecialchars($account['full_name_account']); ?>"
                                            required />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="ae-form-label">Telefone (conta)</label>
                                        <input type="text" class="form-control" name="tel_account"
                                            value="<?php echo htmlspecialchars($account['tel_account'] ?? ''); ?>" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="ae-form-label">E-mail (conta)</label>
                                        <input type="email" class="form-control" name="email_account"
                                            value="<?php echo htmlspecialchars($account['email_account'] ?? ''); ?>" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="ae-form-label">Tipo de Conta</label>
                                        <select class="form-select" name="type_account">
                                            <?php foreach (['IBAN' => 'IBAN (Bancária)', 'Express' => 'Express', 'PayPal' => 'PayPal'] as $v => $l): ?>
                                            <option value="<?php echo $v; ?>"
                                                <?php echo $account['type_account'] === $v ? 'selected' : ''; ?>>
                                                <?php echo $l; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php if ($account['type_account'] === 'IBAN'): ?>
                                    <div class="col-12">
                                        <label class="ae-form-label">IBAN</label>
                                        <input type="text" class="form-control" name="iban"
                                            value="<?php echo htmlspecialchars($account['iban'] ?? ''); ?>" />
                                    </div>
                                    <?php elseif ($account['type_account'] === 'Express'): ?>
                                    <div class="col-12">
                                        <label class="ae-form-label">Número Multicaixa Express</label>
                                        <input type="text" class="form-control" name="express_number"
                                            value="<?php echo htmlspecialchars($account['express_number'] ?? ''); ?>" />
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-md-6">
                                        <label class="ae-form-label">Conta Principal</label>
                                        <select class="form-select" name="is_default">
                                            <option value="1" <?php echo $account['is_default'] ? 'selected' : ''; ?>>
                                                Sim</option>
                                            <option value="0" <?php echo !$account['is_default'] ? 'selected' : ''; ?>>
                                                Não</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Estado e Verificação -->
                            <div class="ae-card">
                                <div class="ae-card-title"><i class="bi bi-check2-circle"></i> Estado e Verificação
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="ae-form-label">Estado da Conta</label>
                                        <select class="form-select" name="status_account">
                                            <?php foreach (['pending' => 'Pendente', 'verified' => 'Verificada', 'rejected' => 'Rejeitada'] as $v => $l): ?>
                                            <option value="<?php echo $v; ?>"
                                                <?php echo $account['status_account'] === $v ? 'selected' : ''; ?>>
                                                <?php echo $l; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="ae-form-label">Motivo da Rejeição (se aplicável)</label>
                                        <textarea class="form-control" name="reject_reason"
                                            rows="3"><?php echo htmlspecialchars($account['reject_reason'] ?? ''); ?></textarea>
                                        <div class="ae-hint">Este texto será visível para o utilizador.</div>
                                    </div>
                                    <?php if ($account['verified_by']): ?>
                                    <div class="col-12">
                                        <div class="small text-muted">
                                            <i class="bi bi-info-circle"></i> Verificada por: <?php
                                                                                                    $v_stmt = $db->prepare("SELECT first_name, second_name FROM _employees WHERE id_employees = ?");
                                                                                                    $v_stmt->execute([$account['verified_by']]);
                                                                                                    $ver = $v_stmt->fetch();
                                                                                                    echo htmlspecialchars(trim(($ver['first_name'] ?? '') . ' ' . ($ver['second_name'] ?? '')) ?: 'Desconhecido');
                                                                                                    ?><br>
                                            <i class="bi bi-calendar"></i> Verificada em:
                                            <?php echo date('d/m/Y H:i', strtotime($account['verified_at'])); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <!-- Utilizador -->
                            <div class="ae-card">
                                <div class="ae-card-title"><i class="bi bi-person-circle"></i> Utilizador</div>
                                <div class="mb-2">
                                    <div class="fw-semibold">
                                        <?php echo htmlspecialchars($user_name ?: $account['email_user']); ?></div>
                                    <div class="small text-muted">
                                        <?php echo htmlspecialchars($account['email_user']); ?></div>
                                </div>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $account['id_users']; ?>"
                                    class="btn btn-sm btn-outline-secondary w-100">Ver perfil</a>
                            </div>

                            <!-- Acções -->
                            <div class="ae-card">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn text-white"
                                        style="background:#FF0089;border-color:#FF0089">
                                        <i class="bi bi-save"></i> Guardar Alterações
                                    </button>
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/accounts/view?id=<?php echo $id; ?>"
                                        class="btn btn-outline-secondary">Cancelar</a>
                                </div>
                                <div class="ae-hint mt-2">Ao alterar o estado, o utilizador receberá uma notificação.
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
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
</body>

</html>