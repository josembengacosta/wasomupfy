<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY for Business — Treasury Desk (Dashboard)
// Arquivo: wu-panel-2026/pages/manager/gestion.php
// Rota:    wu-panel-2026/manager/gestion
// ══════════════════════════════════════════════════════════════

require_once __DIR__ . '/../../include/platform_admin.php';
require_once __DIR__ . '/include/payment-guard.php';

requirePermission($admin_id, 'finances.view');

// Garante CSRF, verifica expiração por inatividade e redireciona para /login se não autenticado
paymentPanelRequireAccess();

// ── Funções auxiliares ────────────────────────────────────────────────────────
function biz_money(float $value): string
{
    return 'Kz ' . number_format($value, 2, ',', '.');
}

function biz_person_name(array $row): string
{
    return trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['second_name'] ?? ''));
}

function biz_status_badge(string $status): string
{
    return match ($status) {
        'pending'                        => '<span class="biz-s-pending">Pendente</span>',
        'processing'                     => '<span class="biz-s-processing">A processar</span>',
        'approved', 'paid', 'validated'  => '<span class="biz-s-approved">Concluído</span>',
        'rejected', 'cancelled'          => '<span class="biz-s-rejected">Fechado</span>',
        default                          => '<span class="biz-s-pending">' . htmlspecialchars(ucfirst($status)) . '</span>',
    };
}

function biz_relative_time(?string $value): string
{
    if (!$value) return '--';
    $ts = strtotime($value);
    if (!$ts) return '--';
    $diff = time() - $ts;
    if ($diff < 60)    return 'agora';
    if ($diff < 3600)  return floor($diff / 60) . ' min';
    if ($diff < 86400) return floor($diff / 3600) . ' h';
    return floor($diff / 86400) . ' dias';
}

function biz_account_ref(array $row): string
{
    if (!empty($row['iban']))           return 'IBAN ...' . substr((string)$row['iban'], -6);
    if (!empty($row['express_number'])) return (string)$row['express_number'];
    return '--';
}

// ── Setup de variáveis ────────────────────────────────────────────────────────
$can_edit              = hasPermission($admin_id, 'finances.edit');
$payment_base          = paymentPanelBaseUrl();
$csrf                  = $_SESSION['admin_csrf_token'];
$payment_sidebar_active = 'dashboard';

require_once __DIR__ . '/include/payment-sidebar.php';

$requested_withdrawal_id = max(0, (int)($_GET['withdrawal'] ?? 0));

// ── Queries ───────────────────────────────────────────────────────────────────
$stats = $db->query("
    SELECT
        SUM(status_withdrawal = 'pending')    AS pending_count,
        SUM(status_withdrawal = 'processing') AS processing_count,
        COALESCE(SUM(CASE WHEN status_withdrawal IN ('pending','processing') THEN amount_requested ELSE 0 END), 0) AS pipeline_amount,
        COALESCE(SUM(CASE WHEN status_withdrawal = 'approved' AND DATE(paid_at) = CURDATE() THEN amount_net ELSE 0 END), 0) AS paid_today,
        (SELECT COUNT(*) FROM _payment_proof WHERE status = 'pending')       AS pending_proofs,
        (SELECT COUNT(*) FROM _royalty WHERE status_royalty = 'pending')     AS pending_royalties
    FROM _withdrawal
")->fetch(PDO::FETCH_ASSOC) ?: [];

$withdrawal_sql = "
    SELECT w.*,
           u.first_name, u.second_name, u.email_user, u.tel_user,
           a.full_name_account, a.type_account, a.status_account, a.iban, a.express_number,
           wl.balance_aoa, wl.total_withdrawn
    FROM _withdrawal w
    JOIN _users u ON u.id_users = w.id_users
    LEFT JOIN _account a  ON a.id_account  = w.id_account
    LEFT JOIN _wallet  wl ON wl.id_users   = w.id_users
";

$active_withdrawal = null;
if ($requested_withdrawal_id > 0) {
    $stmt = $db->prepare($withdrawal_sql . " WHERE w.id_withdrawal = ? LIMIT 1");
    $stmt->execute([$requested_withdrawal_id]);
    $active_withdrawal = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
if (!$active_withdrawal) {
    $active_withdrawal = $db->query($withdrawal_sql . "
        WHERE w.status_withdrawal IN ('pending','processing')
        ORDER BY CASE w.status_withdrawal WHEN 'pending' THEN 0 WHEN 'processing' THEN 1 ELSE 2 END, w.creat_withdrawal ASC
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC) ?: null;
}

$withdrawal_queue = $db->query($withdrawal_sql . "
    WHERE w.status_withdrawal IN ('pending','processing')
    ORDER BY CASE w.status_withdrawal WHEN 'pending' THEN 0 WHEN 'processing' THEN 1 ELSE 2 END, w.creat_withdrawal ASC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

$pending_proofs = $db->query("
    SELECT pp.id_proof, pp.file_path, pp.uploaded_at, pi.reference_code, pl.name_plan, u.first_name, u.second_name
    FROM _payment_proof pp
    JOIN _payment_intent pi ON pi.id_intent = pp.id_intent
    JOIN _users u ON u.id_users = pi.id_users
    LEFT JOIN _plans pl ON pl.id_plan = pi.id_plan
    WHERE pp.status = 'pending'
    ORDER BY pp.uploaded_at ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$pending_royalties = $db->query("
    SELECT r.id_royalty, r.net_royalty_aoa, r.month_royalty, r.year_royalty, t.title_track,
           COALESCE(ar.stage_name, u.name_artist_band, u.first_name) AS artist_name
    FROM _royalty r
    JOIN _users u ON u.id_users = r.id_users
    LEFT JOIN _track t  ON t.id_track  = r.id_track
    LEFT JOIN _album al ON al.id_album = t.id_album
    LEFT JOIN _artist ar ON ar.id_artist = al.id_artist
    WHERE r.status_royalty = 'pending'
    ORDER BY r.creat_royalty ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$recent_transactions = $db->query("
    SELECT t.type_transaction, t.amount, t.reference, t.creat_transaction,
           u.first_name, u.second_name,
           e.first_name AS emp_first_name, e.second_name AS emp_second_name
    FROM _transaction t
    LEFT JOIN _users     u ON u.id_users     = t.id_users
    LEFT JOIN _employees e ON e.id_employees = t.id_employees
    WHERE t.type_transaction IN ('withdrawal', 'royalty_credit')
    ORDER BY t.creat_transaction DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
    <title>Treasury Desk — Wasom Upfy for Business</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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

    .pag-link {
        border-radius: 8px !important;
        margin: 0 2px;
        font-size: .8rem
    }

    .desk-hero {
        background: linear-gradient(135deg, #0f172a, #111827 48%, #1f2937);
        color: #fff;
        border-radius: 28px;
        padding: 26px;
    }

    .desk-hero h1 {
        font-size: 1.95rem;
        font-weight: 800;
        margin: 14px 0 8px;
    }

    .desk-hero p {
        margin: 0;
        max-width: 760px;
        color: #d1d5db;
        line-height: 1.7;
    }

    .desk-tag {
        display: inline-flex;
        gap: 8px;
        align-items: center;
        border-radius: 999px;
        padding: 8px 12px;
        font-size: .76rem;
        background: rgba(255, 255, 255, .08);
    }

    .desk-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-top: 20px;
    }

    .desk-mini {
        background: rgba(255, 255, 255, .08);
        border: 1px solid rgba(255, 255, 255, .08);
        border-radius: 18px;
        padding: 16px;
    }

    .desk-mini strong {
        display: block;
        font-size: 1.18rem;
        margin-bottom: 6px;
    }

    .desk-mini span {
        font-size: .78rem;
        color: #d1d5db;
    }

    .desk-card,
    .focus-card {
        background: #fff;
        border-radius: 24px;
        border: 1px solid rgba(0, 0, 0, .04);
        box-shadow: 0 4px 16px rgba(15, 23, 42, .04);
    }

    .desk-card .head,
    .focus-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 18px 20px;
        border-bottom: 1px solid #f3f4f6;
    }

    .desk-card .head h5 {
        margin: 0;
        font-size: .95rem;
        font-weight: 800;
        color: #111827;
    }

    .focus-body,
    .desk-card .body {
        padding: 20px;
    }

    .focus-kpi {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .focus-kpi .item {
        background: #f8fafc;
        border: 1px solid #eef2f7;
        border-radius: 18px;
        padding: 16px;
    }

    .focus-kpi .label {
        font-size: .72rem;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: 6px;
    }

    .focus-kpi .value {
        font-size: 1.05rem;
        font-weight: 800;
        color: #111827;
    }

    .meta-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #f3f4f6;
    }

    .meta-row:last-child {
        border-bottom: 0;
    }

    .meta-row span {
        font-size: .8rem;
        color: #6b7280;
        font-weight: 600;
    }

    .meta-row strong {
        font-size: .84rem;
        color: #111827;
        text-align: right;
    }

    .queue-item,
    .proof-item,
    .royalty-item {
        padding: 14px 0;
        border-bottom: 1px solid #f3f4f6;
    }

    .queue-item:last-child,
    .proof-item:last-child,
    .royalty-item:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .line-name {
        font-weight: 700;
        color: #111827;
        font-size: .85rem;
    }

    .line-sub {
        font-size: .76rem;
        color: #6b7280;
        margin-top: 3px;
    }

    .desk-empty {
        text-align: center;
        color: #6b7280;
        padding: 24px 12px;
        font-size: .82rem;
    }

    .btn-soft {
        border-radius: 14px;
        font-weight: 700;
    }

    .tx-table th,
    .tx-table td {
        font-size: .8rem;
        vertical-align: middle;
    }

    .tx-table th {
        font-size: .68rem;
        text-transform: uppercase;
        color: #6b7280;
        background: #f8fafc;
    }

    /* Status badges herdados do payment-sidebar.css — fallback inline */
    .biz-s-pending {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        background: #fef3c7;
        color: #92400e;
    }

    .biz-s-processing {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        background: #dbeafe;
        color: #1d4ed8;
    }

    .biz-s-approved {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        background: #dcfce7;
        color: #15803d;
    }

    .biz-s-rejected {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        background: #fee2e2;
        color: #b91c1c;
    }

    @media (max-width:1199px) {

        .desk-grid,
        .focus-kpi {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width:767px) {

        .desk-grid,
        .focus-kpi {
            grid-template-columns: 1fr;
        }

        .desk-hero h1 {
            font-size: 1.5rem;
        }

        .meta-row {
            flex-direction: column;
        }

        .meta-row strong {
            text-align: left;
        }
    }
    </style>
</head>

<body>
    <div class="biz-content">

        <!-- ── Topbar ── -->
        <div class="biz-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="biz-hamburger" onclick="openSidebar()"><i class="bi bi-list fs-5"></i></button>
                <div>
                    <div class="biz-topbar-title">Treasury Desk</div>
                    <div class="biz-topbar-sub">Wasom Upfy for Business / Gestão central de pagamentos</div>
                </div>
            </div>
            <a href="<?php echo $payment_base; ?>/withdrawals" class="btn btn-sm btn-outline-secondary btn-soft">
                Fila de saques
            </a>
        </div>

        <div class="biz-inner">

            <!-- ── Hero com KPIs ── -->
            <section class="desk-hero mb-4">
                <span class="desk-tag"><i class="bi bi-stars"></i> Cockpit financeiro operacional</span>
                <h1>Liquidação de saques, comprovativos e royalties num painel só.</h1>
                <p>Assume um saque, valida provas de pagamento e credita royalties sem sair do shell financeiro.</p>
                <div class="desk-grid">
                    <div class="desk-mini">
                        <strong><?php echo (int)($stats['pending_count'] ?? 0); ?></strong>
                        <span>saques pendentes</span>
                    </div>
                    <div class="desk-mini">
                        <strong><?php echo (int)($stats['processing_count'] ?? 0); ?></strong>
                        <span>em processamento</span>
                    </div>
                    <div class="desk-mini">
                        <strong><?php echo biz_money((float)($stats['pipeline_amount'] ?? 0)); ?></strong>
                        <span>pipeline financeira</span>
                    </div>
                    <div class="desk-mini">
                        <strong><?php echo biz_money((float)($stats['paid_today'] ?? 0)); ?></strong>
                        <span>liquidado hoje</span>
                    </div>
                </div>
            </section>

            <div class="row g-4">

                <!-- ── Coluna principal ── -->
                <div class="col-xl-8">

                    <!-- Saque em foco -->
                    <section class="focus-card mb-4">
                        <div class="focus-head">
                            <div>
                                <div class="text-uppercase text-muted small fw-bold">Saque em foco</div>
                                <div class="h4 mb-0 fw-bold text-dark">
                                    <?php if ($active_withdrawal): ?>
                                    #<?php echo (int)$active_withdrawal['id_withdrawal']; ?> —
                                    <?php echo htmlspecialchars(biz_person_name($active_withdrawal)); ?>
                                    <?php else: ?>
                                    Sem saque activo
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($active_withdrawal): ?>
                            <?php echo biz_status_badge((string)$active_withdrawal['status_withdrawal']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="focus-body">
                            <?php if ($active_withdrawal): ?>
                            <div class="focus-kpi">
                                <div class="item">
                                    <div class="label">Valor pedido</div>
                                    <div class="value">
                                        <?php echo biz_money((float)$active_withdrawal['amount_requested']); ?></div>
                                </div>
                                <div class="item">
                                    <div class="label">Valor líquido</div>
                                    <div class="value" style="color:#16a34a">
                                        <?php echo biz_money((float)$active_withdrawal['amount_net']); ?></div>
                                </div>
                                <div class="item">
                                    <div class="label">Wallet actual</div>
                                    <div class="value">
                                        <?php echo biz_money((float)($active_withdrawal['balance_aoa'] ?? 0)); ?></div>
                                </div>
                            </div>
                            <div class="meta-row">
                                <span>Conta de destino</span>
                                <strong><?php echo htmlspecialchars((string)($active_withdrawal['full_name_account'] ?: '--')); ?></strong>
                            </div>
                            <div class="meta-row">
                                <span>Referência</span>
                                <strong><?php echo htmlspecialchars(biz_account_ref($active_withdrawal)); ?></strong>
                            </div>
                            <div class="meta-row">
                                <span>Tipo e estado</span>
                                <strong>
                                    <?php echo htmlspecialchars((string)($active_withdrawal['type_account'] ?: '--')); ?>
                                    /
                                    <?php echo htmlspecialchars((string)($active_withdrawal['status_account'] ?: '--')); ?>
                                </strong>
                            </div>
                            <div class="meta-row">
                                <span>Pedido criado</span>
                                <strong>
                                    <?php echo date('d/m/Y H:i', strtotime((string)$active_withdrawal['creat_withdrawal'])); ?>
                                    (<?php echo biz_relative_time((string)$active_withdrawal['creat_withdrawal']); ?>)
                                </strong>
                            </div>
                            <div class="meta-row">
                                <span>Contacto</span>
                                <strong>
                                    <?php echo htmlspecialchars((string)($active_withdrawal['email_user'] ?: '--')); ?>
                                    / <?php echo htmlspecialchars((string)($active_withdrawal['tel_user'] ?: '--')); ?>
                                </strong>
                            </div>
                            <?php else: ?>
                            <div class="desk-empty">Não existe nenhum pedido pendente ou em processamento.</div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Fila operacional de saques -->
                    <section class="desk-card mb-4">
                        <div class="head">
                            <h5>Fila operacional de saques</h5>
                            <a href="<?php echo $payment_base; ?>/withdrawals"
                                class="btn btn-sm btn-outline-secondary btn-soft">Lista completa</a>
                        </div>
                        <div class="body">
                            <?php if (empty($withdrawal_queue)): ?>
                            <div class="desk-empty">Sem saques na fila.</div>
                            <?php else: ?>
                            <?php foreach ($withdrawal_queue as $item): ?>
                            <div class="queue-item">
                                <div class="d-flex justify-content-between gap-3 flex-wrap">
                                    <div>
                                        <div class="line-name"><?php echo htmlspecialchars(biz_person_name($item)); ?>
                                        </div>
                                        <div class="line-sub">
                                            #<?php echo (int)$item['id_withdrawal']; ?> ·
                                            <?php echo htmlspecialchars((string)$item['email_user']); ?> ·
                                            <?php echo biz_relative_time((string)$item['creat_withdrawal']); ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="line-name" style="color:#ff0089">
                                            <?php echo biz_money((float)$item['amount_requested']); ?>
                                        </div>
                                        <div class="line-sub">
                                            <?php echo htmlspecialchars((string)($item['type_account'] ?: 'Conta')); ?>
                                            · <?php echo biz_account_ref($item); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap mt-3">
                                    <?php echo biz_status_badge((string)$item['status_withdrawal']); ?>
                                    <button class="btn btn-sm btn-outline-info btn-soft" type="button"
                                        onclick="viewWithdrawal(<?php echo (int)$item['id_withdrawal']; ?>)">Visualizar</button>
                                    <a class="btn btn-sm btn-dark btn-soft"
                                        href="<?php echo $payment_base; ?>/gestion?withdrawal=<?php echo (int)$item['id_withdrawal']; ?>">Assumir</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Histórico recente -->
                    <section class="desk-card">
                        <div class="head">
                            <h5>Histórico recente</h5>
                            <a href="<?php echo $payment_base; ?>/transactions"
                                class="btn btn-sm btn-outline-secondary btn-soft">Transacções</a>
                        </div>
                        <div class="body p-0">
                            <div class="table-responsive">
                                <table class="table tx-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Utilizador</th>
                                            <th>Referência</th>
                                            <th>Valor</th>
                                            <th>Executado por</th>
                                            <th>Data</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_transactions)): ?>
                                        <tr>
                                            <td colspan="6" class="desk-empty">Sem transacções recentes.</td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($recent_transactions as $tx): ?>
                                        <tr>
                                            <td><?php echo $tx['type_transaction'] === 'withdrawal' ? 'Saque' : 'Royalty'; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars(trim((string)($tx['first_name'] ?? '') . ' ' . (string)($tx['second_name'] ?? '')) ?: '--'); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars((string)($tx['reference'] ?: '--')); ?></td>
                                            <td class="fw-bold"><?php echo biz_money((float)$tx['amount']); ?></td>
                                            <td><?php echo htmlspecialchars(trim((string)($tx['emp_first_name'] ?? '') . ' ' . (string)($tx['emp_second_name'] ?? '')) ?: '--'); ?>
                                            </td>
                                            <td><?php echo date('d/m H:i', strtotime((string)$tx['creat_transaction'])); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- ── Coluna lateral ── -->
                <div class="col-xl-4">

                    <!-- Sala de liquidação -->
                    <section class="desk-card mb-4">
                        <div class="head">
                            <h5>Sala de liquidação</h5>
                            <span class="text-muted small">
                                <?php echo $can_edit ? 'Modo operativo' : 'Somente leitura'; ?>
                            </span>
                        </div>
                        <div class="body">
                            <?php if (!$active_withdrawal): ?>
                            <div class="desk-empty">Assume um saque da fila para abrir o console de pagamento.</div>
                            <?php elseif (!$can_edit): ?>
                            <div class="desk-empty">Esta conta pode visualizar, mas não aprovar ou rejeitar pagamentos.
                            </div>
                            <?php else: ?>
                            <input type="hidden" id="activeWithdrawalId"
                                value="<?php echo (int)$active_withdrawal['id_withdrawal']; ?>">
                            <?php if ((string)$active_withdrawal['status_withdrawal'] === 'pending'): ?>
                            <button type="button" class="btn btn-primary btn-soft w-100 mb-2"
                                onclick="setProcessing()">Marcar a processar</button>
                            <?php endif; ?>
                            <label class="form-label small fw-bold" for="wdNotes">Notas internas</label>
                            <textarea class="form-control mb-3" id="wdNotes" rows="3"
                                placeholder="Ex: Transferido via IBAN às 14:30, confirmado com o titular."></textarea>
                            <label class="form-label small fw-bold" for="wdProof">Comprovativo</label>
                            <input class="form-control mb-3" type="file" id="wdProof" accept="image/*,application/pdf">
                            <button type="button" class="btn btn-success btn-soft w-100 mb-2"
                                onclick="approveWithdrawal()">Confirmar pagamento</button>
                            <button type="button" class="btn btn-outline-danger btn-soft w-100"
                                onclick="rejectWithdrawal()">Rejeitar pedido</button>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Comprovativos pendentes -->
                    <section class="desk-card mb-4">
                        <div class="head">
                            <h5>Comprovativos pendentes</h5>
                            <a href="<?php echo $payment_base; ?>/proofs"
                                class="btn btn-sm btn-outline-secondary btn-soft">Abrir área</a>
                        </div>
                        <div class="body">
                            <?php if (empty($pending_proofs)): ?>
                            <div class="desk-empty">Não há comprovativos pendentes.</div>
                            <?php else: ?>
                            <?php foreach ($pending_proofs as $proof): ?>
                            <div class="proof-item">
                                <div class="line-name"><?php echo htmlspecialchars(biz_person_name($proof)); ?></div>
                                <div class="line-sub">
                                    <?php echo htmlspecialchars((string)($proof['name_plan'] ?: 'Plano')); ?> ·
                                    <?php echo htmlspecialchars((string)($proof['reference_code'] ?: '--')); ?> ·
                                    <?php echo biz_relative_time((string)$proof['uploaded_at']); ?>
                                </div>
                                <div class="d-flex gap-2 flex-wrap mt-2">
                                    <?php if (!empty($proof['file_path'])): ?>
                                    <a class="btn btn-sm btn-outline-info btn-soft"
                                        href="<?php echo APP_URL . '/' . ltrim((string)$proof['file_path'], '/'); ?>"
                                        target="_blank" rel="noopener">Abrir ficheiro</a>
                                    <?php endif; ?>
                                    <?php if ($can_edit): ?>
                                    <button class="btn btn-sm btn-success btn-soft" type="button"
                                        onclick="validateProof(<?php echo (int)$proof['id_proof']; ?>)">Validar</button>
                                    <button class="btn btn-sm btn-outline-danger btn-soft" type="button"
                                        onclick="rejectProof(<?php echo (int)$proof['id_proof']; ?>)">Rejeitar</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Royalties para crédito -->
                    <section class="desk-card">
                        <div class="head">
                            <h5>Royalties para crédito</h5>
                            <a href="<?php echo $payment_base; ?>/royalty-splits"
                                class="btn btn-sm btn-outline-secondary btn-soft">Ver mapa</a>
                        </div>
                        <div class="body">
                            <?php if (empty($pending_royalties)): ?>
                            <div class="desk-empty">Sem royalties pendentes.</div>
                            <?php else: ?>
                            <?php foreach ($pending_royalties as $royalty): ?>
                            <div class="royalty-item">
                                <div class="line-name">
                                    <?php echo htmlspecialchars((string)($royalty['artist_name'] ?: '--')); ?>
                                </div>
                                <div class="line-sub">
                                    <?php echo htmlspecialchars((string)($royalty['title_track'] ?: 'Faixa sem título')); ?>
                                    ·
                                    <?php echo str_pad((string)$royalty['month_royalty'], 2, '0', STR_PAD_LEFT) . '/' . (string)$royalty['year_royalty']; ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mt-2">
                                    <strong style="color:#ff0089">
                                        <?php echo biz_money((float)$royalty['net_royalty_aoa']); ?>
                                    </strong>
                                    <?php if ($can_edit): ?>
                                    <button class="btn btn-sm btn-dark btn-soft" type="button"
                                        onclick="payRoyalty(<?php echo (int)$royalty['id_royalty']; ?>)">Creditar</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>

                </div><!-- /col-xl-4 -->
            </div><!-- /row -->
        </div><!-- /biz-inner -->
    </div><!-- /biz-content -->

    <!-- Modal de detalhe do saque -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:#111827">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="bi bi-eye me-2"></i>Detalhe do saque
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    (function() {
        'use strict';
        const PROCESS_URL = '<?php echo $payment_base; ?>/process';
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const canEdit = <?php echo $can_edit ? 'true' : 'false'; ?>;

        async function postForm(payload) {
            const fd = new FormData();
            Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
            fd.append('csrf_token', CSRF);
            const r = await fetch(PROCESS_URL, {
                method: 'POST',
                body: fd
            });
            return r.json();
        }

        async function postWithFile(actionName) {
            const fd = new FormData();
            fd.append('action', actionName);
            fd.append('id_withdrawal', document.getElementById('activeWithdrawalId')?.value || '');
            fd.append('notes', document.getElementById('wdNotes')?.value || '');
            fd.append('csrf_token', CSRF);
            const file = document.getElementById('wdProof')?.files[0];
            if (file) fd.append('comprovante', file);
            const r = await fetch(PROCESS_URL, {
                method: 'POST',
                body: fd
            });
            return r.json();
        }

        async function runAction(fn, redirectUrl = '') {
            Swal.fire({
                title: 'A processar...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            try {
                const data = await fn();
                if (!data.ok) throw new Error(data.message);
                await Swal.fire({
                    icon: 'success',
                    title: 'Concluído',
                    text: data.message,
                    confirmButtonColor: '#ff0089'
                });
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                    return;
                }
                window.location.reload();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Falha',
                    text: error.message || 'Operação não concluída.',
                    confirmButtonColor: '#ff0089'
                });
            }
        }

        window.viewWithdrawal = async function(id) {
            document.getElementById('viewModalBody').innerHTML =
                '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
            document.getElementById('viewModalFooter').innerHTML = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('viewModal')).show();
            try {
                const data = await postForm({
                    action: 'get_withdrawal_details',
                    id_withdrawal: id
                });
                if (!data.ok) throw new Error(data.message);
                document.getElementById('viewModalBody').innerHTML = data.html ||
                    '<div class="alert alert-warning">Sem detalhes.</div>';
                document.getElementById('viewModalFooter').innerHTML = data.footer_html || '';
            } catch (error) {
                document.getElementById('viewModalBody').innerHTML =
                    '<div class="alert alert-danger">' + (error.message || 'Falha ao carregar detalhes.') +
                    '</div>';
            }
        };

        window.setProcessing = async function() {
            if (!canEdit) return;
            const ok = await Swal.fire({
                title: 'Marcar como em processamento?',
                text: 'O utilizador será notificado.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, avançar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2563eb'
            });
            if (ok.isConfirmed) runAction(() => postForm({
                action: 'set_processing_withdrawal',
                id_withdrawal: document.getElementById('activeWithdrawalId')?.value || ''
            }));
        };

        window.approveWithdrawal = async function() {
            if (!canEdit) return;
            const file = document.getElementById('wdProof')?.files[0];
            if (file && file.size > 5 * 1024 * 1024) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Ficheiro grande',
                    text: 'O comprovativo excede 5MB.',
                    confirmButtonColor: '#ff0089'
                });
                return;
            }
            const ok = await Swal.fire({
                title: 'Confirmar pagamento?',
                text: 'Wallet e transacções serão actualizadas.',
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#16a34a'
            });
            if (ok.isConfirmed) runAction(() => postWithFile('approve_withdrawal'),
                '<?php echo $payment_base; ?>/gestion');
        };

        window.rejectWithdrawal = async function() {
            if (!canEdit) return;
            const res = await Swal.fire({
                title: 'Rejeitar pedido de saque',
                input: 'textarea',
                inputLabel: 'Motivo visível para o utilizador',
                inputValidator: value => value.trim() ? undefined : 'O motivo é obrigatório.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Rejeitar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626'
            });
            if (res.isConfirmed) runAction(() => postForm({
                action: 'reject_withdrawal',
                id_withdrawal: document.getElementById('activeWithdrawalId')?.value || '',
                reject_reason: res.value.trim()
            }), '<?php echo $payment_base; ?>/gestion');
        };

        window.validateProof = async function(id) {
            if (!canEdit) return;
            const ok = await Swal.fire({
                title: 'Validar comprovativo?',
                text: 'O utilizador será notificado da aprovação.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Validar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#16a34a'
            });
            if (ok.isConfirmed) runAction(() => postForm({
                action: 'validate_proof',
                id_proof: id,
                new_status: 'validated'
            }));
        };

        window.rejectProof = async function(id) {
            if (!canEdit) return;
            const res = await Swal.fire({
                title: 'Rejeitar comprovativo',
                input: 'textarea',
                inputLabel: 'Motivo da rejeição',
                inputValidator: value => value.trim() ? undefined : 'O motivo é obrigatório.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Rejeitar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626'
            });
            if (res.isConfirmed) runAction(() => postForm({
                action: 'validate_proof',
                id_proof: id,
                new_status: 'rejected',
                reject_reason: res.value.trim()
            }));
        };

        window.payRoyalty = async function(id) {
            if (!canEdit) return;
            const ok = await Swal.fire({
                title: 'Creditar royalty ao utilizador?',
                text: 'A carteira será actualizada.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Creditar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#111827'
            });
            if (ok.isConfirmed) runAction(() => postForm({
                action: 'pay_royalty',
                id_royalty: id
            }));
        };
    })();
    </script>
</body>

</html>