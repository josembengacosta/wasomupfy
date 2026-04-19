<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Editar Pagamento
// Arquivo: wu-panel/pages/payments/edit.php
// Rota:    wu-panel/payments/edit?id=X
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.edit');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/payments');

$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'updated' => ['success', 'bi-check-circle', 'Pagamento actualizado com sucesso.'],
    'error'   => ['danger',  'bi-x-circle',     'Ocorreu um erro.'],
    default   => null,
};

// Buscar dados do pagamento
$stmt = $db->prepare("
    SELECT p.*, u.first_name, u.second_name, u.email_user, pl.name_plan
    FROM _payment p
    LEFT JOIN _users u ON u.id_users = p.id_users
    LEFT JOIN _plans pl ON pl.id_plan = p.id_plan
    WHERE p.id_payment = ?
");
$stmt->execute([$id]);
$pay = $stmt->fetch();
if (!$pay) adminRedirect('/' . ADMIN_PATH . '/payments?msg=not_found');

$fullname = trim(($pay['first_name'] ?? '') . ' ' . ($pay['second_name'] ?? ''));
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title>Editar Pagamento #<?php echo $id; ?> — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        .pe-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 14px;
            padding: 22px 24px;
            margin-bottom: 20px;
        }

        .pe-card-title {
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

        .pe-form-label {
            font-size: .78rem;
            font-weight: 600;
            margin-bottom: 5px;
            opacity: .7;
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
                        <h2 class="h4 mb-1"><i class="bi bi-pencil-square me-2"></i>Editar Pagamento #<?php echo $id; ?>
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments"
                                        class="text-secondary">Pagamentos</a></li>
                                <li class="breadcrumb-item"><a
                                        href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments/view?id=<?php echo $id; ?>"
                                        class="text-secondary">#<?php echo $id; ?></a></li>
                                <li class="breadcrumb-item active text-white-stable">Editar</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments/view?id=<?php echo $id; ?>"
                            class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Visualizar</a>
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments"
                            class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
                    </div>
                </div>

                <?php if ($feedback): ?>
                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3"><i
                            class="bi <?php echo $feedback[1]; ?> me-2"></i><?php echo htmlspecialchars($feedback[2]); ?><button
                            type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <form method="POST" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments/edit-process">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
                    <input type="hidden" name="id_payment" value="<?php echo $id; ?>">
                    <input type="hidden" name="action" value="update_payment">

                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="pe-card">
                                <div class="pe-card-title"><i class="bi bi-info-circle"></i> Dados do Pagamento</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="pe-form-label">Estado</label>
                                        <select class="form-select" name="status_payment">
                                            <?php foreach (['pending' => 'Pendente', 'approved' => 'Aprovado', 'rejected' => 'Rejeitado', 'refunded' => 'Reembolsado'] as $v => $l): ?>
                                                <option value="<?php echo $v; ?>"
                                                    <?php echo $pay['status_payment'] === $v ? 'selected' : ''; ?>>
                                                    <?php echo $l; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="pe-form-label">Método de Pagamento</label>
                                        <select class="form-select" name="payment_method">
                                            <?php foreach (['bank_transfer' => 'Transferência Bancária', 'multicaixa' => 'Multicaixa Express', 'paypal' => 'PayPal', 'card' => 'Cartão'] as $v => $l): ?>
                                                <option value="<?php echo $v; ?>"
                                                    <?php echo $pay['payment_method'] === $v ? 'selected' : ''; ?>>
                                                    <?php echo $l; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="pe-form-label">Motivo da Rejeição (se aplicável)</label>
                                        <textarea class="form-control" name="rejection_reason"
                                            rows="3"><?php echo htmlspecialchars($pay['rejection_reason'] ?? ''); ?></textarea>
                                        <div class="pe-hint">Este texto é visível para o utilizador quando o pagamento é
                                            rejeitado.</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="pe-form-label">Notas Internas</label>
                                        <textarea class="form-control" name="notes"
                                            rows="3"><?php echo htmlspecialchars($pay['notes'] ?? ''); ?></textarea>
                                        <div class="pe-hint">Apenas visível para administradores.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="pe-card">
                                <div class="pe-card-title"><i class="bi bi-person"></i> Informação do Utilizador</div>
                                <div><strong><?php echo htmlspecialchars($fullname ?: $pay['email_user']); ?></strong>
                                </div>
                                <div class="small text-muted"><?php echo htmlspecialchars($pay['email_user']); ?></div>
                                <div class="mt-2 small">Plano:
                                    <strong><?php echo htmlspecialchars($pay['name_plan']); ?></strong>
                                </div>
                                <div class="mt-2 small">Valor: <?php echo number_format((float)$pay['amount'], 2); ?>
                                    AOA</div>
                                <div class="mt-2 small">Referência:
                                    <code><?php echo htmlspecialchars($pay['payment_ref']); ?></code>
                                </div>
                            </div>
                            <div class="pe-card">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn text-white"
                                        style="background:#FF0089;border-color:#FF0089"><i class="bi bi-save"></i>
                                        Guardar Alterações</button>
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments/view?id=<?php echo $id; ?>"
                                        class="btn btn-outline-secondary">Cancelar</a>
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