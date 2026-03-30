<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY for Business — Pedidos de Saque
// Arquivo: wu-panel-2026/pages/manager/withdrawals.php
// Rota:    wu-panel-2026/manager/withdrawals
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.edit');

if (empty($_SESSION['payment_control_auth'])) {
    header('Location: ' . APP_URL . '/' . ADMIN_PATH . '/manager/gestion'); exit;
}
$_SESSION['biz_auth_time'] = time();

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// ── Filtros + paginação ───────────────────────────────────────
$per_page = 15;
$page     = max(1, (int)($_GET['page'] ?? 1));
$f_user   = trim($_GET['user']   ?? '');
$f_status = trim($_GET['status'] ?? '');
$f_type   = trim($_GET['type']   ?? '');

$where  = [];
$params = [];
if ($f_user !== '') {
    $where[]  = "(u.first_name LIKE ? OR u.second_name LIKE ? OR u.email_user LIKE ?)";
    $params[] = "%$f_user%"; $params[] = "%$f_user%"; $params[] = "%$f_user%";
}
if ($f_status !== '') {
    $where[]  = 'w.status_withdrawal = ?';
    $params[] = $f_status;
}
if ($f_type !== '') {
    $where[]  = 'a.type_account = ?';
    $params[] = $f_type;
}
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Contagem
$cnt = $db->prepare("SELECT COUNT(*) FROM _withdrawal w LEFT JOIN _users u ON u.id_users=w.id_users LEFT JOIN _account a ON a.id_account=w.id_account $sql_where");
$cnt->execute($params);
$total       = (int)$cnt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

// Dados
$stmt = $db->prepare("
    SELECT w.*,
           u.first_name, u.second_name, u.email_user, u.photo_user, u.tel_user,
           a.full_name_account, a.tel_account, a.email_account,
           a.iban, a.express_number, a.type_account, a.status_account,
           a.bi_front_path, a.bi_back_path
    FROM _withdrawal w
    LEFT JOIN _users u ON u.id_users = w.id_users
    LEFT JOIN _account a ON a.id_account = w.id_account
    $sql_where
    ORDER BY
        CASE w.status_withdrawal WHEN 'pending' THEN 0 WHEN 'processing' THEN 1 ELSE 2 END,
        w.creat_withdrawal ASC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$withdrawals = $stmt->fetchAll();

// Stats rápidas
$stats_wd = $db->query("
    SELECT
        SUM(status_withdrawal='pending')    AS pending,
        SUM(status_withdrawal='processing') AS processing,
        SUM(status_withdrawal='approved')   AS approved,
        SUM(status_withdrawal='rejected')   AS rejected,
        COALESCE(SUM(CASE WHEN status_withdrawal='approved' THEN amount_net ELSE 0 END),0) AS total_paid
    FROM _withdrawal
")->fetch();

$payment_sidebar_active = 'withdrawals';
require_once __DIR__ . '/include/payment-sidebar.php';
$csrf = $_SESSION['admin_csrf_token'];

function wd_status_badge(string $s): string
{
    return match($s) {
        'pending'    => '<span class="biz-s-pending">Pendente</span>',
        'processing' => '<span class="biz-s-processing">A processar</span>',
        'approved'   => '<span class="biz-s-approved">Aprovado</span>',
        'rejected'   => '<span class="biz-s-rejected">Rejeitado</span>',
        'cancelled'  => '<span class="biz-s-rejected">Cancelado</span>',
        default      => '<span class="biz-s-pending">'.ucfirst($s).'</span>',
    };
}
function biz_fmt_w(float $v): string {
    return 'Kz ' . number_format($v, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
    <title>Pedidos de Saque — Wasom Upfy for Business</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap"
        rel="stylesheet">
    <style>
    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        margin: 0;
    }

    .filter-card {
        background: #fff;
        border-radius: 16px;
        padding: 16px 20px;
        border: 1px solid rgba(0, 0, 0, .04);
        box-shadow: 0 2px 8px rgba(0, 0, 0, .03);
        margin-bottom: 20px;
    }

    .filter-card .form-label {
        font-size: .75rem;
        font-weight: 600;
        margin-bottom: 3px;
        color: #555;
    }

    .pag-link {
        border-radius: 8px !important;
        margin: 0 2px;
        font-size: .8rem;
    }
    </style>
</head>

<body>

    <div class="biz-content">
        <div class="biz-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="biz-hamburger" onclick="openSidebar()"><i class="bi bi-list fs-5"></i></button>
                <div>
                    <div class="biz-topbar-title">Pedidos de Saque</div>
                    <div class="biz-topbar-sub">
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/gestion"
                            style="color:#888;text-decoration:none">Dashboard</a>
                        → Saques
                    </div>
                </div>
            </div>
            <span class="text-muted small"><?php echo date('d/m/Y H:i'); ?></span>
        </div>

        <div class="biz-inner">

            <!-- Mini stats -->
            <div class="row g-3 mb-4">
                <?php
            $mini = [
                ['val'=>(int)$stats_wd['pending'],    'lbl'=>'Pendentes',    'color'=>'#f97316','icon'=>'bi-hourglass-split'],
                ['val'=>(int)$stats_wd['processing'], 'lbl'=>'A processar', 'color'=>'#3b82f6','icon'=>'bi-arrow-repeat'],
                ['val'=>(int)$stats_wd['approved'],   'lbl'=>'Aprovados',   'color'=>'#22c55e','icon'=>'bi-check-circle'],
                ['val'=>(int)$stats_wd['rejected'],   'lbl'=>'Rejeitados',  'color'=>'#ef4444','icon'=>'bi-x-circle'],
                ['val'=>biz_fmt_w((float)$stats_wd['total_paid']),'lbl'=>'Total Pago','color'=>'#FF0089','icon'=>'bi-cash-coin'],
            ];
            foreach ($mini as $m):
            ?>
                <div class="col-6 col-md-4 col-xl">
                    <div class="biz-stat" style="padding:14px 16px">
                        <div class="d-flex align-items-center gap-3">
                            <div class="biz-stat-icon"
                                style="width:40px;height:40px;background:<?php echo $m['color']; ?>18">
                                <i class="bi <?php echo $m['icon']; ?>" style="color:<?php echo $m['color']; ?>"></i>
                            </div>
                            <div>
                                <div style="font-size:1.2rem;font-weight:800;color:#1a1a2e"><?php echo $m['val']; ?>
                                </div>
                                <div class="biz-stat-lbl"><?php echo $m['lbl']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Filtros -->
            <div class="filter-card">
                <form method="GET">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Utilizador</label>
                            <input type="text" name="user" class="form-control form-control-sm"
                                value="<?php echo htmlspecialchars($f_user); ?>" placeholder="Nome ou e-mail">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <?php foreach (['pending'=>'Pendente','processing'=>'A processar','approved'=>'Aprovado','rejected'=>'Rejeitado','cancelled'=>'Cancelado'] as $v=>$l): ?>
                                <option value="<?php echo $v; ?>" <?php echo $f_status===$v?'selected':''; ?>>
                                    <?php echo $l; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tipo de Conta</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <?php foreach (['IBAN'=>'IBAN','Express'=>'Express','PayPal'=>'PayPal','Multicaixa'=>'Multicaixa','TPA'=>'TPA'] as $v=>$l): ?>
                                <option value="<?php echo $v; ?>" <?php echo $f_type===$v?'selected':''; ?>>
                                    <?php echo $l; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-1">
                            <button type="submit" class="btn btn-sm text-white flex-fill" style="background:#FF0089">
                                <i class="bi bi-search"></i>
                            </button>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/withdrawals"
                                class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabela -->
            <div class="biz-card">
                <div class="biz-card-header">
                    <span class="biz-card-title">
                        <?php echo number_format($total); ?> pedido(s)
                    </span>
                    <span style="font-size:.75rem;color:#aaa">Pág.
                        <?php echo $page; ?>/<?php echo $total_pages; ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover biz-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Utilizador</th>
                                <th>Conta Destino</th>
                                <th>Valor Pedido</th>
                                <th>Valor Líq.</th>
                                <th>Estado</th>
                                <th>Data</th>
                                <th style="text-align:center">Acções</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($withdrawals)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>Nenhum pedido encontrado
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($withdrawals as $w):
                        $user_name = trim($w['first_name'].' '.($w['second_name']??''));
                        $is_active = in_array($w['status_withdrawal'],['pending','processing']);
                    ?>
                            <tr class="<?php echo $w['status_withdrawal']==='pending' ? 'table-warning' : ''; ?>">
                                <td><span
                                        style="font-family:monospace;font-size:.73rem;opacity:.55">#<?php echo $w['id_withdrawal']; ?></span>
                                </td>
                                <td>
                                    <div style="font-weight:600;font-size:.82rem">
                                        <?php echo htmlspecialchars($user_name); ?></div>
                                    <div style="font-size:.72rem;color:#888">
                                        <?php echo htmlspecialchars($w['email_user']); ?></div>
                                </td>
                                <td>
                                    <?php if ($w['full_name_account']): ?>
                                    <div style="font-size:.8rem;font-weight:600">
                                        <?php echo htmlspecialchars($w['full_name_account']); ?></div>
                                    <div style="font-size:.72rem;color:#888">
                                        <?php echo $w['type_account']; ?> ·
                                        <?php
                                if ($w['type_account']==='IBAN' && $w['iban']) echo '···'.substr($w['iban'],-6);
                                elseif ($w['express_number']) echo $w['express_number'];
                                ?>
                                    </div>
                                    <?php else: echo '<span style="opacity:.4">—</span>'; endif; ?>
                                </td>
                                <td style="font-weight:700;white-space:nowrap;color:#FF0089">
                                    <?php echo biz_fmt_w((float)$w['amount_requested']); ?>
                                </td>
                                <td style="font-weight:600;white-space:nowrap;color:#22c55e">
                                    <?php echo $w['amount_net'] ? biz_fmt_w((float)$w['amount_net']) : '—'; ?>
                                </td>
                                <td><?php echo wd_status_badge($w['status_withdrawal']); ?></td>
                                <td style="font-size:.76rem;white-space:nowrap">
                                    <?php echo date('d/m/Y',strtotime($w['creat_withdrawal'])); ?>
                                    <div style="color:#aaa"><?php echo date('H:i',strtotime($w['creat_withdrawal'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <!-- Detalhes -->
                                        <button class="btn btn-sm btn-outline-info" title="Ver detalhes"
                                            onclick="viewWithdrawal(<?php echo (int)$w['id_withdrawal']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if ($is_active): ?>
                                        <!-- Processar -->
                                        <?php if ($w['status_withdrawal']==='pending'): ?>
                                        <button class="btn btn-sm btn-outline-primary" title="Marcar a processar"
                                            onclick="setProcessing(<?php echo (int)$w['id_withdrawal']; ?>)">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                        <?php endif; ?>
                                        <!-- Aprovar -->
                                        <button class="btn btn-sm btn-outline-success"
                                            title="Aprovar e marcar como pago"
                                            onclick="approveWithdrawal(<?php echo (int)$w['id_withdrawal']; ?>)">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <!-- Rejeitar -->
                                        <button class="btn btn-sm btn-outline-danger" title="Rejeitar"
                                            onclick="rejectWithdrawal(<?php echo (int)$w['id_withdrawal']; ?>)">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center py-3">
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?php echo $page<=1?'disabled':''; ?>">
                                <a class="page-link pag-link"
                                    href="?page=<?php echo $page-1; ?>&user=<?php echo urlencode($f_user); ?>&status=<?php echo urlencode($f_status); ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($pi=max(1,$page-2);$pi<=min($total_pages,$page+2);$pi++): ?>
                            <li class="page-item <?php echo $pi===$page?'active':''; ?>">
                                <a class="page-link pag-link"
                                    href="?page=<?php echo $pi; ?>&user=<?php echo urlencode($f_user); ?>&status=<?php echo urlencode($f_status); ?>"><?php echo $pi; ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page>=$total_pages?'disabled':''; ?>">
                                <a class="page-link pag-link"
                                    href="?page=<?php echo $page+1; ?>&user=<?php echo urlencode($f_user); ?>&status=<?php echo urlencode($f_status); ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Modal Ver Detalhes -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:#1a1a2e">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="bi bi-arrow-up-circle me-2"></i>Detalhe do Pedido de Saque
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="viewModalBody">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>
                <div class="modal-footer border-0" id="viewModalFooter"></div>
            </div>
        </div>
    </div>

    <!-- Modal Aprovar -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:#22c55e">
                    <h5 class="modal-title text-white fw-bold"><i class="bi bi-check-circle me-2"></i>Aprovar Saque</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="approve_wd_id">
                    <p class="text-muted small mb-3">
                        Confirma que o pagamento foi realizado. Será enviada uma notificação ao utilizador e o saldo da
                        wallet será actualizado.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Comprovativo de Pagamento (opcional)</label>
                        <input type="file" class="form-control form-control-sm" id="approve_comprovante"
                            accept="image/*,application/pdf">
                        <div class="form-text">PDF ou imagem (max 5MB). Será enviado ao utilizador.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Notas internas (opcional)</label>
                        <textarea class="form-control form-control-sm" id="approve_notes" rows="2"
                            placeholder="Ex: Transferido via IBAN às 14:30"></textarea>
                    </div>
                    <div class="alert alert-danger d-none" id="approve_error" style="font-size:.78rem"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success btn-sm" id="btn_confirm_approve">
                        <span class="normal-lbl"><i class="bi bi-check-lg me-1"></i>Confirmar Pagamento</span>
                        <span class="loading-lbl d-none"><span class="spinner-border spinner-border-sm me-1"></span>A
                            processar…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    (function() {
        'use strict';
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const PROCESS = '<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/process';

        async function post(payload) {
            const fd = new FormData();
            Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
            fd.append('csrf_token', CSRF);
            const r = await fetch(PROCESS, {
                method: 'POST',
                body: fd
            });
            return r.json();
        }

        // ── Visualizar ──────────────────────────────────────────────
        window.viewWithdrawal = async function(id) {
            document.getElementById('viewModalBody').innerHTML =
                '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
            document.getElementById('viewModalFooter').innerHTML = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('viewModal')).show();
            try {
                const data = await post({
                    action: 'get_withdrawal_details',
                    id_withdrawal: id
                });
                if (data.ok) {
                    document.getElementById('viewModalBody').innerHTML = data.html;
                    document.getElementById('viewModalFooter').innerHTML = data.footer_html || '';
                } else {
                    document.getElementById('viewModalBody').innerHTML =
                        '<div class="alert alert-danger">' + data.message + '</div>';
                }
            } catch {
                document.getElementById('viewModalBody').innerHTML =
                    '<div class="alert alert-danger">Erro de ligação.</div>';
            }
        };

        // ── Marcar a processar ───────────────────────────────────────
        window.setProcessing = async function(id) {
            const {
                isConfirmed
            } = await Swal.fire({
                title: 'Marcar como "A processar"?',
                text: 'O utilizador será notificado que o pagamento está em curso.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                confirmButtonText: 'Sim, marcar',
                cancelButtonText: 'Cancelar'
            });
            if (!isConfirmed) return;
            Swal.fire({
                title: 'A processar...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            try {
                const data = await post({
                    action: 'set_processing_withdrawal',
                    id_withdrawal: id
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
                    confirmButtonColor: '#FF0089'
                });
            }
        };

        // ── Aprovar ──────────────────────────────────────────────────
        window.approveWithdrawal = function(id) {
            document.getElementById('approve_wd_id').value = id;
            document.getElementById('approve_comprovante').value = '';
            document.getElementById('approve_notes').value = '';
            document.getElementById('approve_error').classList.add('d-none');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('approveModal')).show();
        };

        document.getElementById('btn_confirm_approve').addEventListener('click', async function() {
            const id = document.getElementById('approve_wd_id').value;
            const file = document.getElementById('approve_comprovante').files[0];
            const notes = document.getElementById('approve_notes').value;
            const errEl = document.getElementById('approve_error');
            errEl.classList.add('d-none');

            if (file && file.size > 5 * 1024 * 1024) {
                errEl.textContent = 'O ficheiro excede 5MB.';
                errEl.classList.remove('d-none');
                return;
            }

            setLoading(this, true);
            const fd = new FormData();
            fd.append('action', 'approve_withdrawal');
            fd.append('id_withdrawal', id);
            fd.append('notes', notes);
            fd.append('csrf_token', CSRF);
            if (file) fd.append('comprovante', file);

            try {
                const r = await fetch(PROCESS, {
                    method: 'POST',
                    body: fd
                });
                const data = await r.json();
                if (data.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('approveModal')).hide();
                    await Swal.fire({
                        icon: 'success',
                        title: 'Aprovado!',
                        text: data.message,
                        confirmButtonColor: '#FF0089'
                    });
                    location.reload();
                } else {
                    errEl.textContent = data.message;
                    errEl.classList.remove('d-none');
                }
            } catch {
                errEl.textContent = 'Erro de ligação.';
                errEl.classList.remove('d-none');
            }
            setLoading(this, false);
        });

        // ── Rejeitar ─────────────────────────────────────────────────
        window.rejectWithdrawal = async function(id) {
            const {
                value: reason
            } = await Swal.fire({
                title: 'Rejeitar pedido de saque',
                html: '<p class="text-muted small mb-3">Explica o motivo. Será visível ao utilizador e enviada uma notificação.</p>' +
                    '<textarea id="swal-reason" class="form-control" rows="3" placeholder="Ex: IBAN inválido; documentos ilegíveis..."></textarea>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Rejeitar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const r = document.getElementById('swal-reason').value.trim();
                    if (!r) {
                        Swal.showValidationMessage('O motivo é obrigatório.');
                        return false;
                    }
                    return r;
                }
            });
            if (!reason) return;

            Swal.fire({
                title: 'A processar...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            try {
                const data = await post({
                    action: 'reject_withdrawal',
                    id_withdrawal: id,
                    reject_reason: reason
                });
                if (data.ok) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Rejeitado',
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
                    confirmButtonColor: '#FF0089'
                });
            }
        };

        function setLoading(btn, state) {
            btn.querySelector('.normal-lbl').classList.toggle('d-none', state);
            btn.querySelector('.loading-lbl').classList.toggle('d-none', !state);
            btn.disabled = state;
        }
    })();
    </script>
</body>

</html>