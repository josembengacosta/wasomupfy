<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Visualizar Conta Bancária
// Arquivo: wu-panel/pages/accounts/view-account.php
// Rota:    wu-panel/accounts/view?id=X
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/accounts');

$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'updated'   => ['success', 'bi-check-circle', 'Conta actualizada com sucesso.'],
    'verified'  => ['success', 'bi-check-circle', 'Conta verificada com sucesso.'],
    'rejected'  => ['warning', 'bi-x-circle',     'Conta rejeitada.'],
    'error'     => ['danger',  'bi-x-circle',     'Ocorreu um erro. Tenta novamente.'],
    default     => null,
};

// Buscar dados da conta
$stmt = $db->prepare("
    SELECT a.*, u.id_users, u.first_name, u.second_name, u.email_user, u.photo_user, u.tel_user
    FROM _account a
    LEFT JOIN _users u ON u.id_users = a.id_users
    WHERE a.id_account = ?
");
$stmt->execute([$id]);
$account = $stmt->fetch();
if (!$account) adminRedirect('/' . ADMIN_PATH . '/accounts?msg=not_found');

// Histórico de saques com esta conta
$withdrawals = $db->prepare("
    SELECT id_withdrawal, amount_requested, amount_net, status_withdrawal, creat_withdrawal, reviewed_at
    FROM _withdrawal
    WHERE id_account = ?
    ORDER BY creat_withdrawal DESC
    LIMIT 10
");
$withdrawals->execute([$id]);
$withdrawal_list = $withdrawals->fetchAll();

// Dados do verificador
$verifier_name = null;
if ($account['verified_by']) {
    $v = $db->prepare("SELECT first_name, second_name FROM _employees WHERE id_employees = ?");
    $v->execute([$account['verified_by']]);
    $ver = $v->fetch();
    if ($ver) $verifier_name = trim($ver['first_name'] . ' ' . $ver['second_name']);
}

$user_name = trim($account['first_name'] . ' ' . $account['second_name']) ?: $account['email_user'];
$account_type_label = match ($account['type_account']) {
    'IBAN'      => 'IBAN (Transferência Bancária)',
    'Express'   => 'Multicaixa Express',
    'PayPal'    => 'PayPal',
    'Multicaixa' => 'Multicaixa',
    'TPA'       => 'TPA',
    default     => ucfirst($account['type_account']),
};

function av_fmt_date($date): string
{
    return $date ? date('d/m/Y H:i', strtotime($date)) : '—';
}

function av_status_badge(string $s): string
{
    return match ($s) {
        'verified' => '<span class="badge bg-success">Verificada</span>',
        'pending'  => '<span class="badge bg-warning text-dark">Pendente</span>',
        'rejected' => '<span class="badge bg-danger">Rejeitada</span>',
        default    => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title>Conta #<?php echo $id; ?> — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        .av-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 14px;
            padding: 20px 22px;
            margin-bottom: 20px;
        }

        .av-card-title {
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

        .av-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 9px 0;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
            font-size: .83rem;
            gap: 12px;
        }

        .av-detail-label {
            opacity: .5;
            flex-shrink: 0;
            min-width: 110px;
        }

        .av-detail-value {
            font-weight: 500;
            text-align: right;
            word-break: break-word;
        }

        .av-user-mini {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            text-decoration: none;
            color: inherit;
        }

        .av-user-mini:hover {
            border-color: #FF0089;
            background: rgba(255, 0, 137, .04);
        }

        .av-user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
        }

        .av-doc-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .av-action-btn {
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
                        <h2 class="h4 mb-1"><i class="bi bi-bank me-2"></i>Conta Bancária #<?php echo $id; ?></h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/accounts"
                                        class="text-secondary">Contas Bancárias</a></li>
                                <li class="breadcrumb-item active text-white-stable">#<?php echo $id; ?></li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/accounts"
                            class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
                        <?php if (hasPermission($admin_id, 'finances.edit')): ?>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/accounts/edit?id=<?php echo $id; ?>"
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
                    <div class="col-lg-8">
                        <!-- Dados da Conta -->
                        <div class="av-card">
                            <div class="av-card-title"><i class="bi bi-bank"></i> Dados da Conta</div>
                            <div class="av-detail-row"><span class="av-detail-label">Tipo:</span><span
                                    class="av-detail-value"><?php echo $account_type_label; ?></span></div>
                            <div class="av-detail-row"><span class="av-detail-label">Titular:</span><span
                                    class="av-detail-value"><?php echo htmlspecialchars($account['full_name_account']); ?></span>
                            </div>
                            <?php if ($account['tel_account']): ?>
                                <div class="av-detail-row"><span class="av-detail-label">Telefone:</span><span
                                        class="av-detail-value"><?php echo htmlspecialchars($account['tel_account']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($account['email_account']): ?>
                                <div class="av-detail-row"><span class="av-detail-label">E-mail (conta):</span><span
                                        class="av-detail-value"><?php echo htmlspecialchars($account['email_account']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($account['type_account'] === 'IBAN' && $account['iban']): ?>
                                <div class="av-detail-row"><span class="av-detail-label">IBAN:</span><span
                                        class="av-detail-value"><code><?php echo htmlspecialchars($account['iban']); ?></code></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($account['type_account'] === 'Express' && $account['express_number']): ?>
                                <div class="av-detail-row"><span class="av-detail-label">Nº Express:</span><span
                                        class="av-detail-value"><code><?php echo htmlspecialchars($account['express_number']); ?></code></span>
                                </div>
                            <?php endif; ?>
                            <div class="av-detail-row"><span class="av-detail-label">Estado:</span><span
                                    class="av-detail-value"><?php echo av_status_badge($account['status_account']); ?></span>
                            </div>
                            <?php if ($account['reject_reason']): ?>
                                <div class="av-detail-row"><span class="av-detail-label">Motivo rejeição:</span><span
                                        class="av-detail-value text-danger"><?php echo htmlspecialchars($account['reject_reason']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="av-detail-row"><span class="av-detail-label">Conta principal:</span><span
                                    class="av-detail-value"><?php echo $account['is_default'] ? 'Sim' : 'Não'; ?></span>
                            </div>
                            <div class="av-detail-row"><span class="av-detail-label">Criada em:</span><span
                                    class="av-detail-value"><?php echo av_fmt_date($account['creat_account']); ?></span>
                            </div>
                            <div class="av-detail-row"><span class="av-detail-label">Última actualização:</span><span
                                    class="av-detail-value"><?php echo av_fmt_date($account['modif_account']); ?></span>
                            </div>
                            <?php if ($account['verified_at']): ?>
                                <div class="av-detail-row"><span class="av-detail-label">Verificada em:</span><span
                                        class="av-detail-value"><?php echo av_fmt_date($account['verified_at']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($verifier_name): ?>
                                <div class="av-detail-row"><span class="av-detail-label">Verificada por:</span><span
                                        class="av-detail-value"><?php echo htmlspecialchars($verifier_name); ?></span></div>
                            <?php endif; ?>
                        </div>

                        <!-- Documentos (BI) -->
                        <div class="av-card">
                            <div class="av-card-title"><i class="bi bi-file-earmark-image"></i> Documentos</div>
                            <div class="row g-3">
                                <?php if ($account['bi_front_path']): ?>
                                    <div class="col-md-6">
                                        <div class="text-muted small">Frente do BI</div>
                                        <a href="<?php echo APP_URL . '/' . $account['bi_front_path']; ?>" target="_blank">
                                            <img src="<?php echo APP_URL . '/' . $account['bi_front_path']; ?>"
                                                class="av-doc-image" alt="Frente BI">
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <?php if ($account['bi_back_path']): ?>
                                    <div class="col-md-6">
                                        <div class="text-muted small">Verso do BI</div>
                                        <a href="<?php echo APP_URL . '/' . $account['bi_back_path']; ?>" target="_blank">
                                            <img src="<?php echo APP_URL . '/' . $account['bi_back_path']; ?>"
                                                class="av-doc-image" alt="Verso BI">
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <?php if (!$account['bi_front_path'] && !$account['bi_back_path']): ?>
                                    <div class="col-12 text-muted">Nenhum documento anexado.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Histórico de Saques -->
                        <div class="av-card">
                            <div class="av-card-title"><i class="bi bi-arrow-up-circle"></i> Saques associados</div>
                            <?php if (empty($withdrawal_list)): ?>
                                <div class="text-muted text-center py-3">Nenhum saque registado com esta conta.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Valor Pedido</th>
                                                <th>Valor Líquido</th>
                                                <th>Estado</th>
                                                <th>Data Pedido</th>
                                                <th>Processado em</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($withdrawal_list as $w): ?>
                                                <tr>
                                                    <td>#<?php echo $w['id_withdrawal']; ?></td>
                                                    <td><?php echo number_format($w['amount_requested'], 2); ?> AOA</td>
                                                    <td><?php echo number_format($w['amount_net'], 2); ?> AOA</td>
                                                    <td><?php echo match ($w['status_withdrawal']) {
                                                            'approved' => 'Aprovado',
                                                            'pending' => 'Pendente',
                                                            'processing' => 'Processando',
                                                            'rejected' => 'Rejeitado',
                                                            'cancelled' => 'Cancelado',
                                                            default => ucfirst($w['status_withdrawal'])
                                                        }; ?>
                                                    </td>
                                                    <td><?php echo av_fmt_date($w['creat_withdrawal']); ?></td>
                                                    <td><?php echo $w['reviewed_at'] ? av_fmt_date($w['reviewed_at']) : '—'; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end mt-2">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/withdrawals?account=<?php echo $id; ?>"
                                        class="small text-decoration-none">Ver todos os saques desta conta</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Proprietário da Conta -->
                        <div class="av-card">
                            <div class="av-card-title"><i class="bi bi-person-circle"></i> Utilizador</div>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $account['id_users']; ?>"
                                class="av-user-mini">
                                <img src="<?php echo APP_URL . '/assets/comprovantes/uploads/users/' . $account['photo_user']; ?>"
                                    class="av-user-avatar"
                                    onerror="this.src='<?php echo APP_URL; ?>/assets/img/avatar-default.png'">
                                <div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($user_name); ?></div>
                                    <div class="small text-muted">
                                        <?php echo htmlspecialchars($account['email_user']); ?></div>
                                    <?php if ($account['tel_user']): ?><div class="small text-muted"><i
                                                class="bi bi-telephone"></i>
                                            <?php echo htmlspecialchars($account['tel_user']); ?></div><?php endif; ?>
                                </div>
                                <i class="bi bi-arrow-right ms-auto text-muted"></i>
                            </a>
                        </div>

                        <!-- Acções Rápidas -->
                        <?php if (hasPermission($admin_id, 'finances.edit')): ?>
                            <div class="av-card">
                                <div class="av-card-title"><i class="bi bi-lightning"></i> Acções</div>
                                <div class="d-grid gap-2">
                                    <?php if ($account['status_account'] === 'pending'): ?>
                                        <button onclick="updateStatus('verified')" class="av-action-btn justify-content-center"
                                            style="background:#22c55e;color:#fff;border:none"><i class="bi bi-check-circle"></i>
                                            Verificar</button>
                                        <button onclick="updateStatus('rejected')" class="av-action-btn justify-content-center"
                                            style="background:#ef4444;color:#fff;border:none"><i class="bi bi-x-circle"></i>
                                            Rejeitar</button>
                                    <?php elseif ($account['status_account'] === 'verified'): ?>
                                        <button onclick="updateStatus('rejected')" class="av-action-btn justify-content-center"
                                            style="background:#ef4444;color:#fff;border:none"><i class="bi bi-x-circle"></i>
                                            Marcar como Rejeitada</button>
                                    <?php elseif ($account['status_account'] === 'rejected'): ?>
                                        <button onclick="updateStatus('verified')" class="av-action-btn justify-content-center"
                                            style="background:#22c55e;color:#fff;border:none"><i class="bi bi-check-circle"></i>
                                            Reverter para Verificada</button>
                                    <?php endif; ?>
                                    <button onclick="deleteAccount()" class="av-action-btn justify-content-center"
                                        style="border-color:rgba(239,68,68,.3);color:#ef4444"><i class="bi bi-trash"></i>
                                        Excluir conta</button>
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
                <p class="mb-0">© <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos os direitos reservados.</p>
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
        const PROCESS = BASE_URL + '/' + ADMIN_PATH + '/accounts/process';
        const ACCOUNT_ID = <?php echo $id; ?>;

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
            if (newStatus === 'verified') {
                title = 'Verificar conta?';
                text = 'A conta será marcada como verificada. O utilizador será notificado.';
            } else if (newStatus === 'rejected') {
                title = 'Rejeitar conta?';
                text = 'Podes adicionar um motivo.';
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
                    inputPlaceholder: 'Ex: Documento ilegível, conta não confirmada...',
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
                    action: 'toggle_status',
                    id_account: ACCOUNT_ID,
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

        window.deleteAccount = async function() {
            const {
                value: password
            } = await Swal.fire({
                title: 'Excluir conta bancária',
                html: '<p class="mb-1">Esta acção é <strong>irreversível</strong>.</p>' +
                    '<p class="text-muted small mb-3">Confirma a tua senha de administrador para continuar.</p>' +
                    '<input type="password" id="swal-pwd" class="swal2-input" placeholder="Senha do admin">',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Excluir',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const pwd = document.getElementById('swal-pwd').value;
                    if (!pwd) {
                        Swal.showValidationMessage('A senha é obrigatória.');
                        return false;
                    }
                    return pwd;
                }
            });
            if (!password) return;

            Swal.fire({
                title: 'A processar...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            try {
                const data = await postAction({
                    action: 'delete_account',
                    id_account: ACCOUNT_ID,
                    password_confirm: password
                });
                if (data.ok) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Eliminada!',
                        text: data.message,
                        confirmButtonColor: '#FF0089'
                    });
                    window.location.href = BASE_URL + '/' + ADMIN_PATH + '/accounts';
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