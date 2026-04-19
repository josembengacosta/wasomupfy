<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Ver Pagamento
// Arquivo: wu-panel/pages/payments/view-payment.php
// Rota:    wu-panel/payments/view?id=X
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/payments');

$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'updated'   => ['success', 'bi-check-circle', 'Pagamento actualizado com sucesso.'],
    'approved'  => ['success', 'bi-check-circle', 'Pagamento aprovado.'],
    'rejected'  => ['warning', 'bi-x-circle',     'Pagamento rejeitado.'],
    'refunded'  => ['info',    'bi-arrow-return-left', 'Pagamento reembolsado.'],
    'error'     => ['danger',  'bi-x-circle',     'Ocorreu um erro.'],
    default     => null,
};

// Buscar dados do pagamento
$stmt = $db->prepare("
    SELECT
        p.*,
        u.id_users,
        u.first_name,
        u.second_name,
        u.email_user,
        u.photo_user,
        u.tel_user,
        pl.id_plan,
        pl.name_plan,
        pl.type_plan,
        pl.price_plan,
        pl.validity_days,
        pi.id_intent,
        pi.reference_code AS intent_ref,
        pi.amount_expected,
        pi.expires_at AS intent_expires,
        pi.creat_intent AS intent_created,
        pr.id_proof,
        pr.full_name AS proof_name,
        pr.phone AS proof_phone,
        pr.method AS proof_method,
        pr.file_path,
        pr.file_hash,
        pr.file_size,
        pr.file_type,
        pr.status AS proof_status,
        pr.reject_reason AS proof_reject_reason,
        pr.uploaded_at,
        tr.id_transaction,
        tr.amount AS tx_amount,
        tr.currency AS tx_currency,
        tr.description AS tx_description,
        tr.creat_transaction
    FROM _payment p
    LEFT JOIN _users u ON u.id_users = p.id_users
    LEFT JOIN _plans pl ON pl.id_plan = p.id_plan
    LEFT JOIN _payment_intent pi ON pi.reference_code = p.payment_ref
    LEFT JOIN _payment_proof pr ON pr.id_intent = pi.id_intent
    LEFT JOIN _transaction tr ON tr.reference = p.payment_ref AND tr.type_transaction = 'plan_payment'
    WHERE p.id_payment = ?
");
$stmt->execute([$id]);
$pay = $stmt->fetch();
if (!$pay) adminRedirect('/' . ADMIN_PATH . '/payments?msg=not_found');

// Histórico de alterações (audit)
$audit = $db->prepare("
    SELECT action, old_value, new_value, creat_log, id_employees
    FROM _audit_log
    WHERE entity = '_payment' AND entity_id = ?
    ORDER BY creat_log DESC
    LIMIT 10
");
$audit->execute([$id]);
$audit_list = $audit->fetchAll();

// Helper para formatação
function pv_fmt_date($date): string
{
    if (!$date) return '—';
    $ts = strtotime($date);
    return $ts ? date('d/m/Y H:i', $ts) : '—';
}

function pv_status_badge(string $status): string
{
    return match ($status) {
        'approved' => '<span class="badge bg-success">Aprovado</span>',
        'pending'  => '<span class="badge bg-warning text-dark">Pendente</span>',
        'rejected' => '<span class="badge bg-danger">Rejeitado</span>',
        'refunded' => '<span class="badge bg-secondary">Reembolsado</span>',
        default    => '<span class="badge bg-secondary">' . ucfirst($status) . '</span>',
    };
}

function pv_method_icon(string $method): string
{
    return match ($method) {
        'bank_transfer' => '<i class="bi bi-building"></i> Transferência Bancária',
        'multicaixa'    => '<i class="bi bi-credit-card"></i> Multicaixa Express',
        'paypal'        => '<i class="bi bi-paypal"></i> PayPal',
        'card'          => '<i class="bi bi-credit-card-2-front"></i> Cartão',
        default         => '<i class="bi bi-cash"></i> ' . ucfirst($method),
    };
}

$fullname = trim(($pay['first_name'] ?? '') . ' ' . ($pay['second_name'] ?? ''));
$user_avatar = $pay['photo_user'] ? APP_URL . '/assets/comprovantes/uploads/users/' . $pay['photo_user'] : null;
$proof_path = $pay['file_path'] ? APP_URL . '/' . $pay['file_path'] : null;
$is_image = in_array($pay['file_type'], ['image/jpeg', 'image/png', 'image/webp']);
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title>Pagamento #<?php echo $id; ?> — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        .pv-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 14px;
            padding: 20px 22px;
            margin-bottom: 20px;
        }

        .pv-card-title {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            opacity: .5;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pv-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 9px 0;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
            font-size: .83rem;
            gap: 12px;
        }

        .pv-detail-label {
            opacity: .5;
            flex-shrink: 0;
            min-width: 110px;
        }

        .pv-detail-value {
            font-weight: 500;
            text-align: right;
            word-break: break-word;
        }

        .pv-proof-image {
            max-width: 100%;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-top: 8px;
        }

        .pv-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 10px;
            font-size: .82rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all .2s;
            cursor: pointer;
        }

        .user-mini {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            text-decoration: none;
            color: inherit;
        }

        .user-mini:hover {
            border-color: #FF0089;
            background: rgba(255, 0, 137, .04);
        }

        .user-avatar-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
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
                        <h2 class="h4 mb-1"><i class="bi bi-cash-stack me-2"></i>Pagamento #<?php echo $id; ?></h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments"
                                        class="text-secondary">Pagamentos</a></li>
                                <li class="breadcrumb-item active text-white-stable">#<?php echo $id; ?></li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments"
                            class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
                        <?php if (hasPermission($admin_id, 'finances.edit')): ?>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments/edit?id=<?php echo $id; ?>"
                                class="btn btn-sm text-white" style="background:#FF0089;border-color:#FF0089"><i
                                    class="bi bi-pencil"></i> Editar</a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($feedback): ?>
                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3"><i
                            class="bi <?php echo $feedback[1]; ?> me-2"></i><?php echo htmlspecialchars($feedback[2]); ?><button
                            type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Coluna Principal (8/12) -->
                    <div class="col-lg-8">
                        <!-- Resumo do Pagamento -->
                        <div class="pv-card">
                            <div class="pv-card-title"><i class="bi bi-receipt"></i> Resumo do Pagamento</div>
                            <div class="pv-detail-row"><span class="pv-detail-label">Referência</span><span
                                    class="pv-detail-value"><code><?php echo htmlspecialchars($pay['payment_ref']); ?></code></span>
                            </div>
                            <div class="pv-detail-row"><span class="pv-detail-label">Plano</span><span
                                    class="pv-detail-value"><?php echo htmlspecialchars($pay['name_plan']); ?>
                                    (<?php echo $pay['type_plan'] === 'subscription' ? 'Subscrição' : 'Por lançamento'; ?>)</span>
                            </div>
                            <div class="pv-detail-row"><span class="pv-detail-label">Valor</span><span
                                    class="pv-detail-value"><strong><?php echo number_format((float)$pay['amount'], 2); ?>
                                        AOA</strong></span></div>
                            <div class="pv-detail-row"><span class="pv-detail-label">Método</span><span
                                    class="pv-detail-value"><?php echo pv_method_icon($pay['payment_method']); ?></span>
                            </div>
                            <div class="pv-detail-row"><span class="pv-detail-label">Estado</span><span
                                    class="pv-detail-value"><?php echo pv_status_badge($pay['status_payment']); ?></span>
                            </div>
                            <div class="pv-detail-row"><span class="pv-detail-label">Data do pagamento</span><span
                                    class="pv-detail-value"><?php echo pv_fmt_date($pay['creat_payment']); ?></span>
                            </div>
                            <?php if ($pay['reviewed_at']): ?>
                                <div class="pv-detail-row"><span class="pv-detail-label">Aprovado/Rejeitado em</span><span
                                        class="pv-detail-value"><?php echo pv_fmt_date($pay['reviewed_at']); ?></span></div>
                            <?php endif; ?>
                            <?php if ($pay['rejection_reason']): ?>
                                <div class="pv-detail-row"><span class="pv-detail-label">Motivo da rejeição</span><span
                                        class="pv-detail-value text-danger"><?php echo htmlspecialchars($pay['rejection_reason']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Comprovativo de Pagamento -->
                        <div class="pv-card">
                            <div class="pv-card-title"><i class="bi bi-file-earmark-image"></i> Comprovativo</div>
                            <?php if ($proof_path): ?>
                                <div class="mb-2">
                                    <span
                                        class="badge bg-secondary"><?php echo strtoupper(pathinfo($proof_path, PATHINFO_EXTENSION)); ?></span>
                                    <span class="text-muted ms-2"><?php echo round($pay['file_size'] / 1024, 2); ?>
                                        KB</span>
                                </div>
                                <?php if ($is_image): ?>
                                    <a href="<?php echo $proof_path; ?>" target="_blank">
                                        <img src="<?php echo $proof_path; ?>" class="pv-proof-image" alt="Comprovativo"
                                            style="max-height:300px;width:auto">
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo $proof_path; ?>" target="_blank" class="btn btn-outline-secondary">
                                        <i class="bi bi-file-earmark-pdf"></i> Abrir PDF
                                    </a>
                                <?php endif; ?>
                                <div class="mt-3">
                                    <a href="<?php echo $proof_path; ?>" download
                                        class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i>
                                        Descarregar</a>
                                </div>
                            <?php else: ?>
                                <div class="text-muted">Nenhum comprovativo anexado.</div>
                            <?php endif; ?>
                            <?php if ($pay['proof_name']): ?>
                                <div class="mt-3 small text-muted">
                                    <i class="bi bi-person"></i> Titular:
                                    <?php echo htmlspecialchars($pay['proof_name']); ?><br>
                                    <?php if ($pay['proof_phone']): ?><i class="bi bi-telephone"></i>
                                        <?php echo htmlspecialchars($pay['proof_phone']); ?><br><?php endif; ?>
                                    <i class="bi bi-credit-card"></i> Método declarado:
                                    <?php echo $pay['proof_method'] === 'express' ? 'Multicaixa Express' : 'Transferência IBAN'; ?><br>
                                    <i class="bi bi-clock-history"></i> Enviado em:
                                    <?php echo pv_fmt_date($pay['uploaded_at']); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Detalhes do Plano e Ativação -->
                        <div class="pv-card">
                            <div class="pv-card-title"><i class="bi bi-calendar-check"></i> Activação do Plano</div>
                            <?php if ($pay['status_payment'] === 'approved'): ?>
                                <div class="pv-detail-row"><span class="pv-detail-label">Início</span><span
                                        class="pv-detail-value"><?php echo pv_fmt_date($pay['creat_payment']); ?></span>
                                </div>
                                <?php if ($pay['validity_days']): ?>
                                    <div class="pv-detail-row"><span class="pv-detail-label">Válido até</span><span
                                            class="pv-detail-value"><?php echo pv_fmt_date(date('Y-m-d H:i:s', strtotime($pay['creat_payment'] . ' + ' . $pay['validity_days'] . ' days'))); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($pay['id_transaction']): ?>
                                    <div class="pv-detail-row"><span class="pv-detail-label">Transacção associada</span><span
                                            class="pv-detail-value"><code>#<?php echo $pay['id_transaction']; ?></code> -
                                            <?php echo number_format((float)$pay['tx_amount'], 2); ?>
                                            <?php echo $pay['tx_currency']; ?></span></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-muted">O plano ainda não foi activado.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Histórico de Alterações (Audit) -->
                        <?php if (!empty($audit_list)): ?>
                            <div class="pv-card">
                                <div class="pv-card-title"><i class="bi bi-clock-history"></i> Histórico de Alterações</div>
                                <?php foreach ($audit_list as $log): ?>
                                    <div class="small mb-2 pb-2 border-bottom">
                                        <i class="bi bi-pencil-square"></i>
                                        <strong><?php echo htmlspecialchars($log['action']); ?></strong><br>
                                        <span class="text-muted"><?php echo pv_fmt_date($log['creat_log']); ?></span>
                                        <?php if ($log['old_value'] || $log['new_value']): ?>
                                            <div class="text-muted" style="font-size:.7rem">
                                                <span class="text-muted">Antes:</span>
                                                <?php echo htmlspecialchars($log['old_value']); ?><br>
                                                <span class="text-muted">Depois:</span>
                                                <?php echo htmlspecialchars($log['new_value']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Coluna Lateral (4/12) -->
                    <div class="col-lg-4">
                        <!-- Utilizador -->
                        <div class="pv-card">
                            <div class="pv-card-title"><i class="bi bi-person-circle"></i> Utilizador</div>
                            <?php if ($pay['id_users']): ?>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $pay['id_users']; ?>"
                                    class="user-mini">
                                    <?php if ($user_avatar): ?>
                                        <img src="<?php echo $user_avatar; ?>" class="user-avatar-sm" alt=""
                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                        <div class="user-avatar-sm bg-secondary" style="display:none">
                                            <?php echo substr($fullname, 0, 1); ?></div>
                                    <?php else: ?>
                                        <div
                                            class="user-avatar-sm bg-secondary d-flex align-items-center justify-content-center text-white">
                                            <?php echo substr($fullname, 0, 1); ?></div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <div style="font-weight:600"><?php echo htmlspecialchars($fullname); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($pay['email_user']); ?>
                                        </div>
                                        <?php if ($pay['tel_user']): ?><div class="small text-muted"><i
                                                    class="bi bi-whatsapp"></i>
                                                <?php echo htmlspecialchars($pay['tel_user']); ?></div><?php endif; ?>
                                    </div>
                                    <i class="bi bi-arrow-right text-muted"></i>
                                </a>
                            <?php else: ?>
                                <div class="text-muted">Utilizador removido</div>
                            <?php endif; ?>
                            <div class="mt-3 d-flex gap-2">
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $pay['tel_user'] ?? ''); ?>"
                                    target="_blank" class="btn btn-sm btn-outline-success w-100"
                                    <?php if (empty($pay['tel_user'])) echo 'style="pointer-events:none;opacity:0.5"'; ?>>
                                    <i class="bi bi-whatsapp"></i> WhatsApp
                                </a>
                                <a href="mailto:<?php echo htmlspecialchars($pay['email_user']); ?>"
                                    class="btn btn-sm btn-outline-primary w-100">
                                    <i class="bi bi-envelope"></i> E-mail
                                </a>
                            </div>
                        </div>

                        <!-- Acções Rápidas (Admin) -->
                        <?php if (hasPermission($admin_id, 'finances.edit')): ?>
                            <div class="pv-card">
                                <div class="pv-card-title"><i class="bi bi-lightning"></i> Acções Rápidas</div>
                                <div class="d-grid gap-2">
                                    <?php if ($pay['status_payment'] === 'pending'): ?>
                                        <button onclick="updateStatus('approved')" class="pv-action-btn justify-content-center"
                                            style="background:#22c55e;color:#fff;border:none"><i class="bi bi-check-circle"></i>
                                            Aprovar</button>
                                        <button onclick="updateStatus('rejected')" class="pv-action-btn justify-content-center"
                                            style="background:#ef4444;color:#fff;border:none"><i class="bi bi-x-circle"></i>
                                            Rejeitar</button>
                                    <?php elseif ($pay['status_payment'] === 'approved'): ?>
                                        <button onclick="updateStatus('refunded')" class="pv-action-btn justify-content-center"
                                            style="background:#6b7280;color:#fff;border:none"><i
                                                class="bi bi-arrow-return-left"></i> Reembolsar</button>
                                    <?php endif; ?>
                                    <button onclick="generateReceipt()" class="pv-action-btn justify-content-center"
                                        style="background:#FF0089;color:#fff;border:none"><i
                                            class="bi bi-file-earmark-pdf"></i> Gerar Recibo PDF</button>
                                    <button onclick="sendEmail()" class="pv-action-btn justify-content-center"
                                        style="background:#3b82f6;color:#fff;border:none"><i
                                            class="bi bi-envelope-paper"></i> Enviar Recibo por E-mail</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
        const BASE_URL = '<?php echo APP_URL; ?>';
        const ADMIN_PATH = '<?php echo ADMIN_PATH; ?>';
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const PROCESS = BASE_URL + '/' + ADMIN_PATH + '/payments/process';
        const PAYMENT_ID = <?php echo $id; ?>;

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

        window.updateStatus = async function(newStatus) {
            let title = '',
                text = '';
            if (newStatus === 'approved') {
                title = 'Aprovar pagamento?';
                text = 'O utilizador será notificado e o plano activado.';
            } else if (newStatus === 'rejected') {
                title = 'Rejeitar pagamento?';
                text = 'Podes adicionar um motivo.';
            } else if (newStatus === 'refunded') {
                title = 'Marcar como reembolsado?';
                text = 'Isto não reverte automaticamente o pagamento, apenas regista.';
            }
            const result = await Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FF0089',
                confirmButtonText: 'Sim, continuar',
                cancelButtonText: 'Cancelar'
            });
            if (!result.isConfirmed) return;

            let reason = '';
            if (newStatus === 'rejected') {
                const {
                    value
                } = await Swal.fire({
                    title: 'Motivo da rejeição',
                    input: 'textarea',
                    inputLabel: 'O motivo será visível para o utilizador',
                    inputPlaceholder: 'Ex: Comprovativo ilegível, valor incorrecto...',
                    inputAttributes: {
                        rows: 3
                    },
                    confirmButtonColor: '#FF0089'
                });
                if (value === undefined) return;
                reason = value;
            }

            Swal.fire({
                title: 'A processar...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            try {
                const data = await postAction({
                    action: 'update_status',
                    id_payment: PAYMENT_ID,
                    new_status: newStatus,
                    reject_reason: reason
                });
                if (data.ok) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Actualizado!',
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

        window.generateReceipt = async function() {
            Swal.fire({
                title: 'A gerar recibo...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            try {
                const data = await postAction({
                    action: 'generate_receipt',
                    id_payment: PAYMENT_ID
                });
                if (data.ok) {
                    window.open(data.pdf_url, '_blank');
                    Swal.fire({
                        icon: 'success',
                        title: 'Recibo gerado!',
                        text: 'O PDF foi aberto numa nova aba.',
                        confirmButtonColor: '#FF0089'
                    });
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

        window.sendEmail = async function() {
            const result = await Swal.fire({
                title: 'Enviar recibo por e-mail?',
                text: 'O recibo será enviado para ' + '<?php echo addslashes($pay['email_user']); ?>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#FF0089',
                confirmButtonText: 'Sim, enviar',
                cancelButtonText: 'Cancelar'
            });
            if (!result.isConfirmed) return;
            Swal.fire({
                title: 'A enviar...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            try {
                const data = await postAction({
                    action: 'email_receipt',
                    id_payment: PAYMENT_ID
                });
                if (data.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Enviado!',
                        text: data.message,
                        confirmButtonColor: '#FF0089'
                    });
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
    </script>
</body>

</html>