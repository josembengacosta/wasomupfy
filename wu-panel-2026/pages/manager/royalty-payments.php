<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY for Business — Pagamentos de Royalties
// Arquivo: wu-panel-2026/pages/manager/royalty-payments.php
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
require_once __DIR__ . '/include/payment-guard.php';
requirePermission($admin_id, 'finances.view');
paymentPanelRequireAccess();

$per_page = 15;
$page     = max(1, (int)($_GET['page'] ?? 1));
$f_user   = trim($_GET['user'] ?? '');
$f_status = trim($_GET['status'] ?? '');

$where = [];
$params = [];
if ($f_user !== '') {
    $where[] = "(u.first_name LIKE ? OR u.second_name LIKE ? OR u.email_user LIKE ? OR t.title_track LIKE ? OR al.title_album LIKE ? OR ar.stage_name LIKE ? )";
    $like = "%$f_user%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($f_status !== '') {
    $where[] = 'r.status_royalty = ?';
    $params[] = $f_status;
}
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cnt = $db->prepare("SELECT COUNT(*) FROM _royalty r JOIN _users u ON u.id_users=r.id_users LEFT JOIN _track t ON t.id_track=r.id_track LEFT JOIN _album al ON al.id_album=t.id_album LEFT JOIN _artist ar ON ar.id_artist=al.id_artist $sql_where");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$stmt = $db->prepare("SELECT r.*, u.first_name, u.second_name, u.email_user, t.title_track, al.title_album, ar.stage_name AS artist_name, a.type_account, a.iban, a.express_number, a.full_name_account FROM _royalty r JOIN _users u ON u.id_users=r.id_users LEFT JOIN _track t ON t.id_track=r.id_track LEFT JOIN _album al ON al.id_album=t.id_album LEFT JOIN _artist ar ON ar.id_artist=al.id_artist LEFT JOIN _account a ON a.id_users=r.id_users AND a.is_default=1 $sql_where ORDER BY CASE r.status_royalty WHEN 'pending' THEN 0 WHEN 'processing' THEN 1 WHEN 'paid' THEN 2 ELSE 3 END, r.creat_royalty ASC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$royalties = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = $db->query("SELECT SUM(status_royalty='pending') AS pending, SUM(status_royalty='processing') AS processing, SUM(status_royalty='paid') AS paid, SUM(status_royalty='cancelled') AS cancelled, COALESCE(SUM(net_royalty_aoa),0) AS total_amount FROM _royalty")->fetch(PDO::FETCH_ASSOC);

$payment_sidebar_active = 'royalty-payments';
require_once __DIR__ . '/include/payment-sidebar.php';
$csrf = $_SESSION['admin_csrf_token'];

function royalty_status_badge(string $status): string
{
    return match ($status) {
        'pending' => '<span class="biz-s-pending">Pendente</span>',
        'processing' => '<span class="biz-s-processing">A processar</span>',
        'paid' => '<span class="biz-s-approved">Pago</span>',
        'cancelled' => '<span class="biz-s-rejected">Cancelado</span>',
        default => '<span class="biz-s-pending">' . htmlspecialchars(ucfirst($status)) . '</span>',
    };
}

function biz_fmt_royalty(float $v): string
{
    return 'Kz ' . number_format($v, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
    <title>Pagamentos de Royalties — Wasom Upfy for Business</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>
    <div class="biz-content">
        <div class="biz-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="biz-hamburger" onclick="openSidebar()"><i class="bi bi-list fs-5"></i></button>
                <div>
                    <div class="biz-topbar-title">Pagamentos de Royalties</div>
                    <div class="biz-topbar-sub"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/gestion"
                            style="color:#888;text-decoration:none">Treasury Desk</a> → Royalties</div>
                </div>
            </div>
            <span class="text-muted small"><?php echo date('d/m/Y H:i'); ?></span>
        </div>
        <div class="biz-inner">
            <div class="row g-3 mb-4">
                <?php $cards = [
                    ['val' => (int)$stats['pending'], 'lbl' => 'Pendentes', 'color' => '#f97316', 'icon' => 'bi-hourglass-split'],
                    ['val' => (int)$stats['processing'], 'lbl' => 'A processar', 'color' => '#3b82f6', 'icon' => 'bi-arrow-repeat'],
                    ['val' => (int)$stats['paid'], 'lbl' => 'Pagos', 'color' => '#22c55e', 'icon' => 'bi-check-circle'],
                    ['val' => (int)$stats['cancelled'], 'lbl' => 'Cancelados', 'color' => '#ef4444', 'icon' => 'bi-x-circle'],
                    ['val' => biz_fmt_royalty((float)$stats['total_amount']), 'lbl' => 'Total (AOA)', 'color' => '#FF0089', 'icon' => 'bi-cash-coin'],
                ];
                foreach ($cards as $m): ?>
                <div class="col-6 col-md-4 col-xl">
                    <div class="biz-stat" style="padding:14px 16px">
                        <div class="d-flex align-items-center gap-3">
                            <div class="biz-stat-icon"
                                style="width:40px;height:40px;background:<?php echo $m['color']; ?>18"><i
                                    class="bi <?php echo $m['icon']; ?>" style="color:<?php echo $m['color']; ?>"></i>
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

            <div class="filter-card">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Pesquisar</label>
                        <input type="text" name="user" class="form-control form-control-sm"
                            value="<?php echo htmlspecialchars($f_user); ?>" placeholder="Nome, e-mail ou faixa">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach (['pending' => 'Pendente', 'processing' => 'A processar', 'paid' => 'Pago', 'cancelled' => 'Cancelado'] as $v => $l): ?>
                            <option value="<?php echo $v; ?>" <?php echo $f_status === $v ? 'selected' : ''; ?>>
                                <?php echo $l; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-sm text-white flex-fill" style="background:#FF0089"><i
                                class="bi bi-search"></i></button>
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/royalty-payments"
                            class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a>
                    </div>
                </form>
            </div>

            <div class="biz-card">
                <div class="biz-card-header">
                    <span class="biz-card-title"><?php echo number_format($total); ?> royalties</span>
                    <span style="font-size:.75rem;color:#aaa">Pág.
                        <?php echo $page; ?>/<?php echo $total_pages; ?></span>
                    <button type="button" class="btn btn-primary btn-sm ms-auto" onclick="openManualDepositModal()">Novo
                        Depósito Manual</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover biz-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Artista / Utilizador</th>
                                <th>Faixa / Álbum</th>
                                <th>Valor (AOA)</th>
                                <th>Estado</th>
                                <th>Conta</th>
                                <th>Relatório</th>
                                <th>Accões</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($royalties)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">Sem royalties para processar.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($royalties as $r): ?>
                            <tr>
                                <td><?php echo (int)$r['id_royalty']; ?></td>
                                <td>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars(trim($r['first_name'] . ' ' . $r['second_name'])); ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars($r['email_user'] ?? ''); ?></div>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars($r['artist_name'] ?? '--'); ?></div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($r['title_track'] ?? '--'); ?></div>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars($r['title_album'] ?? '--'); ?></div>
                                </td>
                                <td class="fw-bold"><?php echo biz_fmt_royalty((float)$r['net_royalty_aoa']); ?></td>
                                <td><?php echo royalty_status_badge($r['status_royalty']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($r['type_account'] ?? ''); ?>
                                    <?php if (!empty($r['iban'])): ?>IBAN
                                    ...<?php echo htmlspecialchars(substr($r['iban'], -6)); ?><?php elseif (!empty($r['express_number'])): ?>Express
                                    <?php echo htmlspecialchars($r['express_number']); ?><?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['report_file'])): ?>
                                    <a href="<?php echo APP_URL . '/' . $r['report_file']; ?>" target="_blank"
                                        class="btn btn-sm btn-outline-secondary">Ver</a>
                                    <?php else: ?>
                                    <span class="text-muted small">Nenhum</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($r['status_royalty'] === 'pending' || $r['status_royalty'] === 'processing'): ?>
                                    <button type="button" class="btn btn-sm btn-success"
                                        onclick="openRoyaltyPaymentModal(<?php echo (int)$r['id_royalty']; ?>)">Pagar</button>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <nav class="mt-3 text-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a class="btn btn-sm btn-outline-secondary pag-link <?php echo $i === $page ? 'active' : ''; ?>"
                    href="?page=<?php echo $i; ?>&user=<?php echo urlencode($f_user); ?>&status=<?php echo urlencode($f_status); ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </nav>

        </div>
    </div>

    <div class="modal fade" id="royaltyPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="royaltyPaymentForm" enctype="multipart/form-data"
                    onsubmit="return submitRoyaltyPayment(event)">
                    <div class="modal-header">
                        <h5 class="modal-title">Pagar Royalty</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_royalty" id="royalty_id" value="">
                        <input type="hidden" name="action" value="pay_royalty">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Usuário</label><input type="text"
                                    class="form-control" id="royalty_user" readonly></div>
                            <div class="col-md-6"><label class="form-label">E-mail</label><input type="text"
                                    class="form-control" id="royalty_email" readonly></div>
                            <div class="col-md-6"><label class="form-label">Conta</label><input type="text"
                                    class="form-control" id="royalty_account" readonly></div>
                            <div class="col-md-6"><label class="form-label">Valor (AOA)</label><input type="text"
                                    class="form-control" id="royalty_amount" readonly></div>
                            <div class="col-md-12"><label class="form-label">Relatório (PDF / imagem)</label><input
                                    type="file" class="form-control" name="report_file" accept=".pdf,image/*"></div>
                            <div class="col-md-12"><label class="form-label">Notas internas</label><textarea
                                    class="form-control" name="admin_note" id="royalty_note" rows="2"
                                    placeholder="Ex: Transferido via IBAN em dd/mm, confirmado."></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Confirmar pagamento</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="manualDepositModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="manualDepositForm" enctype="multipart/form-data" onsubmit="return submitManualDeposit(event)">
                    <div class="modal-header">
                        <h5 class="modal-title">Novo Depósito Manual de Royalty</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="manual_deposit">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Selecionar Usuário</label>
                                <select name="id_users" class="form-select" onchange="loadUserAccount(this.value)"
                                    required>
                                    <option value="">Selecionar usuário...</option>
                                    <?php
                                    $users_stmt = $db->query("SELECT id_users, first_name, second_name, email_user FROM _users ORDER BY first_name, second_name");
                                    while ($u = $users_stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . (int)$u['id_users'] . '">' . htmlspecialchars(trim($u['first_name'] . ' ' . $u['second_name'])) . ' (' . htmlspecialchars($u['email_user']) . ')</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Informações da Conta</label>
                                <div id="user_account_info" class="p-3 border rounded bg-light">Selecione um usuário
                                    para carregar a conta.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Álbum Responsável</label>
                                <select name="id_album" id="deposit_album_select" class="form-select"
                                    onchange="loadAlbumTracks(this.value)" required>
                                    <option value="">Selecionar álbum...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Faixa Responsável</label>
                                <select name="id_track" id="deposit_track_select" class="form-select" required>
                                    <option value="">Selecionar faixa...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ano</label>
                                <input type="number" name="year_royalty" class="form-control" min="2020" max="2030"
                                    value="<?php echo date('Y'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mês</label>
                                <select name="month_royalty" class="form-select" required>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $m == date('n') ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Receita Bruta (USD)</label>
                                <input type="number" name="gross_revenue" class="form-control" step="0.01" min="0"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Taxa Plataforma (USD)</label>
                                <input type="number" name="platform_fee" class="form-control" step="0.01" min="0"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Valor Líquido (AOA)</label>
                                <input type="number" name="net_royalty_aoa" class="form-control" step="0.01" min="0"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Taxa de Câmbio</label>
                                <input type="number" name="exchange_rate" class="form-control" step="0.01" min="0"
                                    value="800" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Relatório (PDF / imagem)</label>
                                <input type="file" class="form-control" name="report_file" accept=".pdf,image/*">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Notas Internas</label>
                                <textarea class="form-control" name="admin_note" rows="2"
                                    placeholder="Ex: Depósito manual para royalties de streaming."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Confirmar Depósito</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
    const royaltyRows =
        <?php echo json_encode($royalties, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const royaltyModal = new bootstrap.Modal(document.getElementById('royaltyPaymentModal'));

    function openRoyaltyPaymentModal(id) {
        const row = royaltyRows.find(r => parseInt(r.id_royalty, 10) === parseInt(id, 10));
        if (!row) return;
        document.getElementById('royalty_id').value = row.id_royalty;
        document.getElementById('royalty_user').value = (row.first_name || '') + ' ' + (row.second_name || '');
        document.getElementById('royalty_email').value = row.email_user || '';
        const accType = row.type_account || 'N/A';
        let accRef = 'Sem conta';
        if (row.iban) accRef = 'IBAN ' + row.iban;
        else if (row.express_number) accRef = 'Express ' + row.express_number;
        document.getElementById('royalty_account').value = accType + ' — ' + accRef;
        document.getElementById('royalty_amount').value = 'Kz ' + Number(row.net_royalty_aoa || 0).toLocaleString(
            'pt-PT', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        document.getElementById('royalty_note').value = '';
        royaltyModal.show();
    }

    async function submitRoyaltyPayment(e) {
        e.preventDefault();
        const form = e.target;
        const data = new FormData(form);
        data.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Processando...';

        try {
            const resp = await fetch('<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/process', {
                method: 'POST',
                body: data
            });
            const json = await resp.json();
            if (json.ok) {
                alert(json.message || 'Royalty marcado como pago.');
                window.location.reload();
            } else {
                alert(json.message || 'Erro ao processar pagamento.');
            }
        } catch (err) {
            console.error(err);
            alert('Erro de rede ou servidor.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Confirmar pagamento';
        }
        return false;
    }
    </script>
</body>

</html>