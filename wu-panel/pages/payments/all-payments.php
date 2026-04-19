<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Todos os Pagamentos
// Arquivo: wu-panel/pages/payments/all-payments.php
// Rota:    wu-panel/payments
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// ──────────────────────────────────────────────────────────────────────────────
// Feedback
// ──────────────────────────────────────────────────────────────────────────────
$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'updated'     => ['success', 'bi-check-circle',    'Pagamento actualizado com sucesso.'],
    'approved'    => ['success', 'bi-check-circle',    'Pagamento aprovado.'],
    'rejected'    => ['warning', 'bi-x-circle',        'Pagamento rejeitado.'],
    'refunded'    => ['info',    'bi-arrow-return-left', 'Pagamento reembolsado.'],
    'error'       => ['danger',  'bi-x-circle',         'Ocorreu um erro. Tenta novamente.'],
    default       => null,
};

// ──────────────────────────────────────────────────────────────────────────────
// Estatísticas globais
// ──────────────────────────────────────────────────────────────────────────────
$stats = $db->query("
    SELECT
        COUNT(*)                 AS total,
        SUM(status_payment = 'approved') AS approved,
        SUM(status_payment = 'pending')  AS pending,
        SUM(status_payment = 'rejected') AS rejected,
        SUM(status_payment = 'refunded') AS refunded,
        COALESCE(SUM(amount), 0) AS total_amount
    FROM _payment
")->fetch();

// ──────────────────────────────────────────────────────────────────────────────
// Filtros e ordenação
// ──────────────────────────────────────────────────────────────────────────────
$per_page   = 15;
$page       = max(1, (int)($_GET['page'] ?? 1));
$f_id       = trim($_GET['id'] ?? '');
$f_user     = trim($_GET['user'] ?? '');
$f_plan     = trim($_GET['plan'] ?? '');
$f_status   = trim($_GET['status'] ?? '');
$f_method   = trim($_GET['method'] ?? '');
$f_date_from = trim($_GET['date_from'] ?? '');
$f_date_to  = trim($_GET['date_to'] ?? '');
$sort_col   = in_array($_GET['sort'] ?? '', ['id_payment', 'amount', 'creat_payment', 'status_payment']) ? $_GET['sort'] : 'creat_payment';
$sort_dir   = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$where  = [];
$params = [];

if ($f_id !== '') {
    $where[]  = 'p.id_payment = ?';
    $params[] = (int)$f_id;
}
if ($f_user !== '') {
    $where[]  = "(u.first_name LIKE ? OR u.second_name LIKE ? OR u.email_user LIKE ?)";
    $params[] = '%' . $f_user . '%';
    $params[] = '%' . $f_user . '%';
    $params[] = '%' . $f_user . '%';
}
if ($f_plan !== '') {
    $where[]  = 'pl.name_plan LIKE ?';
    $params[] = '%' . $f_plan . '%';
}
if ($f_status !== '') {
    $where[]  = 'p.status_payment = ?';
    $params[] = $f_status;
}
if ($f_method !== '') {
    $where[]  = 'p.payment_method = ?';
    $params[] = $f_method;
}
if ($f_date_from !== '') {
    $where[]  = 'DATE(p.creat_payment) >= ?';
    $params[] = $f_date_from;
}
if ($f_date_to !== '') {
    $where[]  = 'DATE(p.creat_payment) <= ?';
    $params[] = $f_date_to;
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ──────────────────────────────────────────────────────────────────────────────
// Contagem
// ──────────────────────────────────────────────────────────────────────────────
$count_stmt = $db->prepare("
    SELECT COUNT(DISTINCT p.id_payment)
    FROM _payment p
    LEFT JOIN _users u ON u.id_users = p.id_users
    LEFT JOIN _plans pl ON pl.id_plan = p.id_plan
    $sql_where
");
$count_stmt->execute($params);
$total_filtered = (int)$count_stmt->fetchColumn();
$total_pages    = max(1, (int)ceil($total_filtered / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;

// ──────────────────────────────────────────────────────────────────────────────
// Dados
// ──────────────────────────────────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT
        p.id_payment,
        p.payment_ref,
        p.amount,
        p.currency,
        p.payment_method,
        p.status_payment,
        p.rejection_reason,
        p.comprovante,
        p.creat_payment,
        u.id_users,
        u.first_name,
        u.second_name,
        u.email_user,
        pl.id_plan,
        pl.name_plan,
        pi.id_intent,
        pi.reference_code AS intent_ref
    FROM _payment p
    LEFT JOIN _users u ON u.id_users = p.id_users
    LEFT JOIN _plans pl ON pl.id_plan = p.id_plan
    LEFT JOIN _payment_intent pi ON pi.reference_code = p.payment_ref
    $sql_where
    ORDER BY p.$sort_col $sort_dir
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

// ──────────────────────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────────────────────
function payment_status_badge(string $status): string
{
    return match ($status) {
        'approved' => '<span class="badge payment-s-approved">Aprovado</span>',
        'pending'  => '<span class="badge payment-s-pending">Pendente</span>',
        'rejected' => '<span class="badge payment-s-rejected">Rejeitado</span>',
        'refunded' => '<span class="badge payment-s-refunded">Reembolsado</span>',
        default    => '<span class="badge bg-secondary">' . ucfirst($status) . '</span>',
    };
}

function payment_method_icon(string $method): string
{
    return match ($method) {
        'bank_transfer' => '<i class="bi bi-building"></i> Transferência',
        'multicaixa'    => '<i class="bi bi-credit-card"></i> Multicaixa Express',
        'paypal'        => '<i class="bi bi-paypal"></i> PayPal',
        'card'          => '<i class="bi bi-credit-card-2-front"></i> Cartão',
        default         => '<i class="bi bi-cash"></i> ' . ucfirst($method),
    };
}

function payment_fmt_date($date): string
{
    if (!$date) return '—';
    $ts = strtotime($date);
    if (!$ts) return '—';
    $diff = time() - $ts;
    if ($diff < 60)     return 'agora';
    if ($diff < 3600)   return floor($diff / 60)    . 'min atrás';
    if ($diff < 86400)  return floor($diff / 3600)  . 'h atrás';
    if ($diff < 604800) return floor($diff / 86400) . 'd atrás';
    return date('d/m/Y', $ts);
}

function payment_sort_url(string $col, string $cur_col, string $cur_dir, array $get): string
{
    $dir = ($col === $cur_col && $cur_dir === 'ASC') ? 'desc' : 'asc';
    return '?' . http_build_query(array_merge($get, ['sort' => $col, 'dir' => $dir, 'page' => 1]));
}

function payment_sort_icon(string $col, string $cur_col, string $cur_dir): string
{
    if ($col !== $cur_col) return '';
    return $cur_dir === 'ASC' ? ' ▲' : ' ▼';
}

// pagamentos, criar array para JavaScript
$js_payments = [];
foreach ($payments as $p) {
    $fullname = trim(($p['first_name'] ?? '') . ' ' . ($p['second_name'] ?? ''));
    $js_payments[] = [
        'id'      => $p['id_payment'],
        'ref'     => $p['payment_ref'],
        'user'    => $fullname ?: $p['email_user'],
        'email'   => $p['email_user'],
        'plan'    => $p['name_plan'] ?? '—',
        'amount'  => number_format((float)$p['amount'], 2),
        'method'  => match ($p['payment_method']) {
            'bank_transfer' => 'Transferência',
            'multicaixa'    => 'Multicaixa Express',
            'paypal'        => 'PayPal',
            'card'          => 'Cartão',
            default         => ucfirst($p['payment_method']),
        },
        'status'  => match ($p['status_payment']) {
            'approved' => 'Aprovado',
            'pending'  => 'Pendente',
            'rejected' => 'Rejeitado',
            'refunded' => 'Reembolsado',
            default    => ucfirst($p['status_payment']),
        },
        'date'    => date('d/m/Y', strtotime($p['creat_payment'])),
    ];
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
    <title>Pagamentos — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        .payment-s-approved {
            background: rgba(34, 197, 94, .15);
            color: #166534;
        }

        .payment-s-pending {
            background: rgba(234, 179, 8, .15);
            color: #92400e;
        }

        .payment-s-rejected {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
        }

        .payment-s-refunded {
            background: rgba(107, 114, 128, .15);
            color: #374151;
        }

        .dark-mode .payment-s-approved {
            background: rgba(34, 197, 94, .2);
            color: #4ade80;
        }

        .dark-mode .payment-s-pending {
            background: rgba(234, 179, 8, .2);
            color: #facc15;
        }

        .dark-mode .payment-s-rejected {
            background: rgba(239, 68, 68, .2);
            color: #f87171;
        }

        .dark-mode .payment-s-refunded {
            background: rgba(107, 114, 128, .2);
            color: #9ca3af;
        }

        .payment-stat {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .payment-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .payment-stat-num {
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1;
        }

        .payment-stat-lbl {
            font-size: .74rem;
            opacity: .6;
            margin-top: 2px;
        }

        .filter-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 18px;
        }

        #payments-table th {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
        }

        #payments-table td {
            font-size: .82rem;
            vertical-align: middle;
        }

        .payment-empty {
            text-align: center;
            padding: 48px 24px;
            opacity: .4;
        }

        .export-buttons {
            display: inline-flex;
            gap: 6px;
            margin-left: auto;
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
                <!-- Cabeçalho -->
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1"><i class="bi bi-cash-stack me-2"></i>Pagamentos</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item active text-white-stable">Pagamentos</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        <div class="export-buttons">
                            <button class="btn btn-sm btn-outline-secondary" id="btn-export-csv" title="Exportar CSV">
                                <i class="bi bi-filetype-csv"></i> CSV
                            </button>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments/export?format=pdf&<?php echo http_build_query($_GET); ?>"
                                target="_blank" class="btn btn-sm btn-outline-secondary" title="Exportar PDF">
                                <i class="bi bi-filetype-pdf"></i> PDF
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($feedback): ?>
                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3" role="alert">
                        <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                        <?php echo htmlspecialchars($feedback[2]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <?php
                    $stat_cards = [
                        ['total',         '#FF0089', 'bi-credit-card',       'Total'],
                        ['approved',      '#22c55e', 'bi-check-circle',      'Aprovados'],
                        ['pending',       '#eab308', 'bi-hourglass-split',   'Pendentes'],
                        ['rejected',      '#ef4444', 'bi-x-circle',          'Rejeitados'],
                        ['refunded',      '#6b7280', 'bi-arrow-return-left', 'Reembolsados'],
                        ['total_amount',  '#FF0089', 'bi-calculator',        'Valor Total (AOA)'],
                    ];
                    foreach ($stat_cards as [$val, $color, $icon, $lbl]):
                        $num = ($val === 'total_amount') ? number_format((float)$stats['total_amount'], 2) : number_format((int)$stats[$val]);
                    ?>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="payment-stat">
                                <div class="payment-stat-icon" style="background:<?php echo $color; ?>22">
                                    <i class="bi <?php echo $icon; ?>" style="color:<?php echo $color; ?>"></i>
                                </div>
                                <div>
                                    <div class="payment-stat-num"><?php echo $num; ?></div>
                                    <div class="payment-stat-lbl"><?php echo $lbl; ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filtros -->
                <div class="filter-card">
                    <form method="GET" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments" id="filter-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-1">
                                <label class="form-label">ID</label>
                                <input type="number" class="form-control form-control-sm" name="id"
                                    value="<?php echo htmlspecialchars($f_id); ?>" placeholder="#" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Utilizador</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="user"
                                    value="<?php echo htmlspecialchars($f_user); ?>" placeholder="Nome ou e-mail" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Plano</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="plan"
                                    value="<?php echo htmlspecialchars($f_plan); ?>" placeholder="Nome do plano" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <select class="form-select form-select-sm filter-instant" name="status">
                                    <option value="">Todos</option>
                                    <?php foreach (['pending' => 'Pendente', 'approved' => 'Aprovado', 'rejected' => 'Rejeitado', 'refunded' => 'Reembolsado'] as $v => $l): ?>
                                        <option value="<?php echo $v; ?>" <?php echo $f_status === $v ? 'selected' : ''; ?>>
                                            <?php echo $l; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Método</label>
                                <select class="form-select form-select-sm filter-instant" name="method">
                                    <option value="">Todos</option>
                                    <?php foreach (['bank_transfer' => 'Transferência', 'multicaixa' => 'Multicaixa', 'paypal' => 'PayPal', 'card' => 'Cartão'] as $v => $l): ?>
                                        <option value="<?php echo $v; ?>" <?php echo $f_method === $v ? 'selected' : ''; ?>>
                                            <?php echo $l; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Data (de)</label>
                                <input type="date" class="form-control form-control-sm filter-instant" name="date_from"
                                    value="<?php echo htmlspecialchars($f_date_from); ?>" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Data (até)</label>
                                <input type="date" class="form-control form-control-sm filter-instant" name="date_to"
                                    value="<?php echo htmlspecialchars($f_date_to); ?>" />
                            </div>
                            <div class="col-md-1 d-flex gap-1">
                                <button type="submit" class="btn btn-sm text-white w-100"
                                    style="background:#FF0089;border-color:#FF0089"><i
                                        class="bi bi-search"></i></button>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments"
                                    class="btn btn-sm btn-outline-secondary" title="Limpar filtros"><i
                                        class="bi bi-x"></i></a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tabela -->
                <div class="card p-0" style="border-radius:14px;overflow:hidden">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                        style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                        <span style="font-size:.82rem;font-weight:600">
                            <?php if ($total_filtered !== (int)$stats['total']): ?>
                                <span style="color:#FF0089"><?php echo number_format($total_filtered); ?></span> de
                                <?php echo number_format((int)$stats['total']); ?> pagamentos
                            <?php else: ?>
                                <?php echo number_format($total_filtered); ?> pagamentos
                            <?php endif; ?>
                        </span>
                        <span style="font-size:.76rem;opacity:.5">Página <?php echo $page; ?> de
                            <?php echo $total_pages; ?></span>
                    </div>
                    <div class="table-responsive" style="overflow-x: auto;">
                        <table class="table table-hover mb-0" id="payments-table">
                            <thead>
                                <tr>
                                    <th style="width:60px"><a
                                            href="<?php echo payment_sort_url('id_payment', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">ID<?php echo payment_sort_icon('id_payment', $sort_col, $sort_dir); ?></a>
                                    </th>
                                    <th>Utilizador</th>
                                    <th>Plano</th>
                                    <th>Valor</th>
                                    <th>Método</th>
                                    <th>Referência</th>
                                    <th><a href="<?php echo payment_sort_url('status_payment', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">Estado<?php echo payment_sort_icon('status_payment', $sort_col, $sort_dir); ?></a>
                                    </th>
                                    <th><a href="<?php echo payment_sort_url('creat_payment', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">Data<?php echo payment_sort_icon('creat_payment', $sort_col, $sort_dir); ?></a>
                                    </th>
                                    <th style="width:60px;text-align:center">Acções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr>
                                        <td colspan="9">
                                            <div class="payment-empty"><i class="bi bi-cash-stack"></i>
                                                <p class="mb-0 mt-2">Nenhum pagamento encontrado para os filtros aplicados.
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payments as $pay):
                                        $fullname = trim(($pay['first_name'] ?? '') . ' ' . ($pay['second_name'] ?? ''));
                                        $user_link = $pay['id_users'] ? APP_URL . '/' . ADMIN_PATH . '/users/view?id=' . $pay['id_users'] : '#';
                                    ?>
                                        <tr>
                                            <td><span
                                                    style="font-family:monospace;font-size:.74rem;opacity:.55">#<?php echo $pay['id_payment']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($pay['id_users']): ?>
                                                    <a href="<?php echo $user_link; ?>" class="text-decoration-none"
                                                        style="color:inherit">
                                                        <?php echo htmlspecialchars($fullname ?: $pay['email_user']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Utilizador removido</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($pay['name_plan'] ?? '—'); ?></td>
                                            <td><strong><?php echo number_format((float)$pay['amount'], 2); ?> AOA</strong></td>
                                            <td><?php echo payment_method_icon($pay['payment_method']); ?></td>
                                            <td><code><?php echo htmlspecialchars($pay['payment_ref']); ?></code></td>
                                            <td><?php echo payment_status_badge($pay['status_payment']); ?></td>
                                            <td style="font-size:.78rem;white-space:nowrap">
                                                <?php echo payment_fmt_date($pay['creat_payment']); ?></td>
                                            <td class="text-center">
                                                <div class="actions-dropdown dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button"
                                                        data-bs-toggle="dropdown" data-bs-reference="toggle"
                                                        aria-expanded="false" title="Acções"><i
                                                            class="bi bi-three-dots-vertical"></i></button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item"
                                                                href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments/view?id=<?php echo $pay['id_payment']; ?>"><i
                                                                    class="bi bi-eye text-info"></i> Visualizar</a></li>
                                                        <?php if (hasPermission($admin_id, 'finances.edit')): ?>
                                                            <li><a class="dropdown-item"
                                                                    href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments/edit?id=<?php echo $pay['id_payment']; ?>"><i
                                                                        class="bi bi-pencil text-warning"></i> Editar</a></li>
                                                            <?php if ($pay['status_payment'] === 'pending'): ?>
                                                                <li><a class="dropdown-item" href="#"
                                                                        onclick="updatePaymentStatus(<?php echo $pay['id_payment']; ?>, 'approved');return false"><i
                                                                            class="bi bi-check-circle text-success"></i> Aprovar</a>
                                                                </li>
                                                                <li><a class="dropdown-item" href="#"
                                                                        onclick="updatePaymentStatus(<?php echo $pay['id_payment']; ?>, 'rejected');return false"><i
                                                                            class="bi bi-x-circle text-danger"></i> Rejeitar</a></li>
                                                            <?php elseif ($pay['status_payment'] === 'approved'): ?>
                                                                <li><a class="dropdown-item" href="#"
                                                                        onclick="updatePaymentStatus(<?php echo $pay['id_payment']; ?>, 'refunded');return false"><i
                                                                            class="bi bi-arrow-return-left text-info"></i>
                                                                        Reembolsar</a></li>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </ul>
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
                            <nav aria-label="Paginação de pagamentos">
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link"
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"><i
                                                class="bi bi-chevron-left"></i></a></li>
                                    <?php
                                    $start = max(1, $page - 2);
                                    $end = min($total_pages, $page + 2);
                                    if ($start > 1): ?>
                                        <li class="page-item"><a class="page-link"
                                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                                        </li>
                                        <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span>
                                            </li><?php endif; ?>
                                    <?php endif; ?>
                                    <?php for ($i = $start; $i <= $end; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link"
                                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <?php if ($end < $total_pages): ?>
                                        <?php if ($end < $total_pages - 1): ?><li class="page-item disabled"><span
                                                    class="page-link">…</span></li><?php endif; ?>
                                        <li class="page-item"><a class="page-link"
                                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?php echo $total_pages; ?></a>
                                        </li>
                                    <?php endif; ?>
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"><a
                                            class="page-link"
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"><i
                                                class="bi bi-chevron-right"></i></a></li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
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
    <script src="<?php echo APP_URL; ?>/js/lastest.min.js"></script>
    <script>
        window.__PAYMENTS_DATA__ = <?php echo json_encode($js_payments, JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script>
        (function() {
            const BASE_URL = '<?php echo APP_URL; ?>';
            const ADMIN_PATH = '<?php echo ADMIN_PATH; ?>';
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const PROCESS = BASE_URL + '/' + ADMIN_PATH + '/payments/process';

            let debounceTimer;
            document.querySelectorAll('.filter-debounce').forEach(el => {
                el.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => document.getElementById('filter-form').submit(),
                        500);
                });
            });
            document.querySelectorAll('.filter-instant').forEach(el => {
                el.addEventListener('change', () => document.getElementById('filter-form').submit());
            });

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

            window.updatePaymentStatus = async function(id, newStatus) {
                let title = '',
                    text = '';
                if (newStatus === 'approved') {
                    title = 'Aprovar pagamento?';
                    text = 'O utilizador será notificado e o plano activado.';
                } else if (newStatus === 'rejected') {
                    title = 'Rejeitar pagamento?';
                    text = 'Podes adicionar um motivo no próximo passo.';
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
                        id_payment: id,
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
        })();

        (function() {
            'use strict';

            var btnCsv = document.getElementById('btn-export-csv');
            if (btnCsv) {
                btnCsv.addEventListener('click', function() {
                    var data = window.__PAYMENTS_DATA__;
                    if (!data || data.length === 0) {
                        Swal.fire('Aviso', 'Nenhum dado para exportar.', 'warning');
                        return;
                    }

                    var headers = ['ID', 'Referência', 'Utilizador', 'E-mail', 'Plano', 'Valor (AOA)', 'Método',
                        'Estado', 'Data'
                    ];
                    var rows = data.map(function(p) {
                        return [
                            p.id,
                            p.ref,
                            p.user,
                            p.email,
                            p.plan,
                            p.amount,
                            p.method,
                            p.status,
                            p.date
                        ];
                    });

                    var csv = headers.join(';') + '\n';
                    rows.forEach(function(r) {
                        csv += r.map(function(v) {
                            return '"' + String(v || '').replace(/"/g, '""') + '"';
                        }).join(';') + '\n';
                    });

                    var blob = new Blob(['\uFEFF' + csv], {
                        type: 'text/csv;charset=utf-8;'
                    });
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'pagamentos_' + new Date().toISOString().slice(0, 10) + '.csv';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                });
            }
        })();
        var btnPdf = document.getElementById('btn-export-pdf');
        if (btnPdf) {
            btnPdf.addEventListener('click', function() {
                // Coletar os filtros atuais da página
                var params = new URLSearchParams(window.location.search);
                // Abrir uma nova aba com a página de impressão
                var url = BASE_URL + '/' + ADMIN_PATH + '/payments/print?' + params.toString();
                window.open(url, '_blank');
            });
        }
    </script>
</body>

</html>