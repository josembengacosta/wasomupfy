<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY for Business — Comprovativos de Pagamento
// Arquivo: wu-panel/pages/manager/proofs.php
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.edit');
if (empty($_SESSION['payment_control_auth'])) {
    header('Location: ' . APP_URL . '/' . ADMIN_PATH . '/manager/gestion');
    exit;
}
$_SESSION['biz_auth_time'] = time();
if (!isset($_SESSION['admin_csrf_token'])) $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

$per_page = 15;
$page     = max(1, (int)($_GET['page'] ?? 1));
$f_status = trim($_GET['status'] ?? '');
$f_user   = trim($_GET['user'] ?? '');

$where = [];
$params = [];
if ($f_status) {
    $where[] = 'pp.status=?';
    $params[] = $f_status;
}
if ($f_user) {
    $where[] = '(u.first_name LIKE ? OR u.second_name LIKE ? OR u.email_user LIKE ?)';
    $params[] = "%$f_user%";
    $params[] = "%$f_user%";
    $params[] = "%$f_user%";
}
$sw = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cnt = $db->prepare("SELECT COUNT(*) FROM _payment_proof pp JOIN _payment_intent pi ON pi.id_intent=pp.id_intent JOIN _users u ON u.id_users=pi.id_users $sw");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$stmt = $db->prepare("
    SELECT pp.*,pi.id_users,pi.amount_expected,
           pl.name_plan,
           CONCAT(u.first_name,' ',COALESCE(u.second_name,'')) AS user_name,
           u.email_user,u.photo_user
    FROM _payment_proof pp
    JOIN _payment_intent pi ON pi.id_intent=pp.id_intent
    JOIN _users u ON u.id_users=pi.id_users
    LEFT JOIN _plans pl ON pl.id_plan=pi.id_plan
    $sw ORDER BY CASE pp.status WHEN 'pending' THEN 0 ELSE 1 END, pp.uploaded_at DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$proofs = $stmt->fetchAll();

$stats_p = $db->query("SELECT SUM(status='pending') AS pending, SUM(status='validated') AS validated, SUM(status='rejected') AS rejected FROM _payment_proof")->fetch();

$payment_sidebar_active = 'proofs';
require_once __DIR__ . '/include/payment-sidebar.php';
$csrf = $_SESSION['admin_csrf_token'];
function proof_badge(string $s): string
{
    return match ($s) {
        'pending' => '<span class="biz-s-pending">Pendente</span>',
        'validated' => '<span class="biz-s-approved">Validado</span>',
        'rejected' => '<span class="biz-s-rejected">Rejeitado</span>',
        default => '<span class="biz-s-pending">' . ucfirst($s) . '</span>'
    };
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
    <title>Comprovativos — Wasom Upfy for Business</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap"
        rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0
        }

        .filter-card {
            background: #fff;
            border-radius: 16px;
            padding: 16px 20px;
            border: 1px solid rgba(0, 0, 0, .04);
            box-shadow: 0 2px 8px rgba(0, 0, 0, .03);
            margin-bottom: 20px
        }

        .filter-card .form-label {
            font-size: .74rem;
            font-weight: 600;
            margin-bottom: 3px;
            color: #555
        }

        .proof-thumb {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e8eaf0;
            cursor: zoom-in;
            transition: border-color .2s
        }

        .proof-thumb:hover {
            border-color: #FF0089
        }

        .pag-link {
            border-radius: 8px !important;
            margin: 0 2px;
            font-size: .8rem
        }
    </style>
</head>

<body>
    <div class="biz-content">
        <div class="biz-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="biz-hamburger" onclick="openSidebar()"><i class="bi bi-list fs-5"></i></button>
                <div>
                    <div class="biz-topbar-title">Comprovativos de Pagamento</div>
                    <div class="biz-topbar-sub"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/gestion"
                            style="color:#888;text-decoration:none">Dashboard</a> → Comprovativos</div>
                </div>
            </div>
            <span class="text-muted small"><?php echo date('d/m/Y H:i'); ?></span>
        </div>
        <div class="biz-inner">

            <!-- Mini stats -->
            <div class="row g-3 mb-4">
                <?php foreach ([['pending', 'Pendentes', '#f97316', 'bi-hourglass-split'], ['validated', 'Validados', '#22c55e', 'bi-check-circle'], ['rejected', 'Rejeitados', '#ef4444', 'bi-x-circle']] as [$k, $l, $c, $i]): ?>
                    <div class="col-md-4">
                        <div class="biz-stat" style="padding:14px 16px">
                            <div class="d-flex align-items-center gap-3">
                                <div class="biz-stat-icon" style="width:40px;height:40px;background:<?php echo $c; ?>18"><i
                                        class="bi <?php echo $i; ?>" style="color:<?php echo $c; ?>"></i></div>
                                <div>
                                    <div style="font-size:1.3rem;font-weight:800;color:#1a1a2e">
                                        <?php echo (int)($stats_p[$k] ?? 0); ?></div>
                                    <div class="biz-stat-lbl"><?php echo $l; ?></div>
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
                        <div class="col-md-4"><label class="form-label">Utilizador</label>
                            <input type="text" name="user" class="form-control form-control-sm"
                                value="<?php echo htmlspecialchars($f_user); ?>" placeholder="Nome ou e-mail">
                        </div>
                        <div class="col-md-3"><label class="form-label">Estado</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="pending" <?php echo $f_status === 'pending' ? 'selected' : ''; ?>>
                                    Pendente
                                </option>
                                <option value="validated" <?php echo $f_status === 'validated' ? 'selected' : ''; ?>>
                                    Validado
                                </option>
                                <option value="rejected" <?php echo $f_status === 'rejected' ? 'selected' : ''; ?>>
                                    Rejeitado
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-1">
                            <button type="submit" class="btn btn-md text-white flex-fill" style="background:#FF0089"><i
                                    class="bi bi-search"></i></button>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/proofs"
                                class="btn btn-md btn-outline-secondary"><i class="bi bi-x"></i></a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabela -->
            <div class="biz-card">
                <div class="biz-card-header">
                    <span class="biz-card-title"><?php echo number_format($total); ?> comprovativo(s)</span>
                    <span style="font-size:.75rem;color:#aaa">Pág.
                        <?php echo $page; ?>/<?php echo $total_pages; ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover biz-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Utilizador</th>
                                <th>Plano</th>
                                <th>Método</th>
                                <th>Valor Esperado</th>
                                <th>Ficheiro</th>
                                <th>Estado</th>
                                <th>Enviado em</th>
                                <th style="text-align:center">Acções</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($proofs)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted"><i
                                            class="bi bi-file-earmark-x fs-1 d-block mb-2"></i>Nenhum comprovativo
                                        encontrado</td>
                                </tr>
                                <?php else: foreach ($proofs as $p):
                                    $is_img = preg_match('/\.(jpg|jpeg|png|webp)$/i', $p['file_path']);
                                    $file_url = APP_URL . '/' . $p['file_path'];
                                ?>
                                    <tr class="<?php echo $p['status'] === 'pending' ? 'table-warning' : ''; ?>">
                                        <td><span
                                                style="font-family:monospace;font-size:.73rem;opacity:.55">#<?php echo $p['id_proof']; ?></span>
                                        </td>
                                        <td>
                                            <div style="font-weight:600;font-size:.82rem">
                                                <?php echo htmlspecialchars($p['user_name']); ?></div>
                                            <div style="font-size:.72rem;color:#888">
                                                <?php echo htmlspecialchars($p['email_user']); ?></div>
                                        </td>
                                        <td style="font-size:.78rem"><?php echo htmlspecialchars($p['name_plan'] ?? '—'); ?>
                                        </td>
                                        <td><span
                                                style="font-size:.76rem;text-transform:uppercase;font-weight:700;color:#888"><?php echo htmlspecialchars($p['method']); ?></span>
                                        </td>
                                        <td style="font-weight:700;color:#FF0089;white-space:nowrap">Kz
                                            <?php echo number_format((float)$p['amount_expected'], 2, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($is_img): ?>
                                                <a href="<?php echo $file_url; ?>" target="_blank"><img
                                                        src="<?php echo $file_url; ?>" class="proof-thumb" alt="Comprovativo"></a>
                                            <?php else: ?>
                                                <a href="<?php echo $file_url; ?>" target="_blank"
                                                    class="btn btn-sm btn-outline-secondary"><i
                                                        class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo proof_badge($p['status']); ?></td>
                                        <td style="font-size:.76rem;white-space:nowrap">
                                            <?php echo date('d/m/Y', strtotime($p['uploaded_at'])); ?>
                                            <div style="color:#aaa"><?php echo date('H:i', strtotime($p['uploaded_at'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 justify-content-center">
                                                <?php if ($p['status'] === 'pending'): ?>
                                                    <button class="btn btn-sm btn-outline-success" title="Validar"
                                                        onclick="validateProof(<?php echo (int)$p['id_proof']; ?>,'validated')"><i
                                                            class="bi bi-check-lg"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger" title="Rejeitar"
                                                        onclick="validateProof(<?php echo (int)$p['id_proof']; ?>,'rejected')"><i
                                                            class="bi bi-x-lg"></i></button>
                                                <?php endif; ?>
                                                <a href="<?php echo $file_url; ?>" target="_blank"
                                                    class="btn btn-sm btn-outline-secondary" title="Ver ficheiro"><i
                                                        class="bi bi-eye"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-center py-3">
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a
                                        class="page-link pag-link"
                                        href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($f_status); ?>&user=<?php echo urlencode($f_user); ?>"><i
                                            class="bi bi-chevron-left"></i></a></li>
                                <?php for ($pi = max(1, $page - 2); $pi <= min($total_pages, $page + 2); $pi++): ?>
                                    <li class="page-item <?php echo $pi === $page ? 'active' : ''; ?>"><a
                                            class="page-link pag-link"
                                            href="?page=<?php echo $pi; ?>&status=<?php echo urlencode($f_status); ?>&user=<?php echo urlencode($f_user); ?>"><?php echo $pi; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"><a
                                        class="page-link pag-link"
                                        href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($f_status); ?>&user=<?php echo urlencode($f_user); ?>"><i
                                            class="bi bi-chevron-right"></i></a></li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const CSRF2 = '<?php echo $csrf; ?>';
        const PROC2 = '<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/process';
        async function validateProof(id, status) {
            const reject = status === 'rejected';
            let reason = '';
            if (reject) {
                const {
                    value: r
                } = await Swal.fire({
                    title: 'Motivo da rejeição',
                    input: 'textarea',
                    inputLabel: 'Explica o motivo (visível ao utilizador)',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Rejeitar',
                    cancelButtonText: 'Cancelar'
                });
                if (!r) return;
                reason = r;
            } else {
                const {
                    isConfirmed
                } = await Swal.fire({
                    title: 'Validar comprovativo?',
                    text: 'O utilizador será notificado e o pagamento marcado como aprovado.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#22c55e',
                    confirmButtonText: 'Validar',
                    cancelButtonText: 'Cancelar'
                });
                if (!isConfirmed) return;
            }
            Swal.fire({
                title: 'A processar...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            const fd = new FormData();
            fd.append('action', 'validate_proof');
            fd.append('id_proof', id);
            fd.append('new_status', status);
            fd.append('csrf_token', CSRF2);
            if (reason) fd.append('reject_reason', reason);
            const r = await fetch(PROC2, {
                method: 'POST',
                body: fd
            });
            const data = await r.json();
            if (data.ok) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Feito!',
                    text: data.message,
                    confirmButtonColor: '#FF0089'
                });
                location.reload();
            } else Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: data.message,
                confirmButtonColor: '#FF0089'
            });
        }
    </script>
</body>

</html>