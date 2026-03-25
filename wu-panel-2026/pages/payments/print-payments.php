<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Página de Impressão de Pagamentos
// Arquivo: wu-panel-2026/pages/payments/print-payments.php
// Rota:    wu-panel-2026/payments/print?filtros...
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.view');

// Replicar os mesmos filtros de all-payments.php
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

// Buscar todos os registos (sem paginação, para impressão)
$stmt = $db->prepare("
    SELECT
        p.id_payment,
        p.payment_ref,
        p.amount,
        p.currency,
        p.payment_method,
        p.status_payment,
        p.creat_payment,
        CONCAT(u.first_name, ' ', COALESCE(u.second_name, '')) AS user_name,
        u.email_user,
        pl.name_plan
    FROM _payment p
    LEFT JOIN _users u ON u.id_users = p.id_users
    LEFT JOIN _plans pl ON pl.id_plan = p.id_plan
    $sql_where
    ORDER BY p.$sort_col $sort_dir
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Cabeçalho HTML com estilo de impressão
$title = 'Pagamentos - ' . APP_NAME;
$date = date('d/m/Y H:i:s');
$company = APP_NAME ?? 'Wasom Upfy';

echo <<<HTML
<!DOCTYPE html>
<html lang="pt-ao">
<head>
    <meta charset="UTF-8">
    <title>$title</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            color: #FF0089;
            font-size: 20px;
            margin-bottom: 5px;
        }
        .filters {
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            text-align: center;
            color: #aaa;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        .no-print {
            text-align: center;
            margin-top: 20px;
        }
        button {
            padding: 8px 16px;
            background: #FF0089;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>$company - Lista de Pagamentos</h1>
    <div class="filters">
        Gerado em: $date<br>
        Filtros aplicados: 
HTML;

// Exibir filtros aplicados
$filter_text = [];
if ($f_id) $filter_text[] = "ID: $f_id";
if ($f_user) $filter_text[] = "Utilizador: $f_user";
if ($f_plan) $filter_text[] = "Plano: $f_plan";
if ($f_status) $filter_text[] = "Estado: $f_status";
if ($f_method) $filter_text[] = "Método: $f_method";
if ($f_date_from) $filter_text[] = "Data de: $f_date_from";
if ($f_date_to) $filter_text[] = "Data até: $f_date_to";
if (empty($filter_text)) $filter_text[] = "Todos os registos";
echo implode(' | ', $filter_text);

echo <<<HTML
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Referência</th>
                <th>Utilizador</th>
                <th>E-mail</th>
                <th>Plano</th>
                <th>Valor (AOA)</th>
                <th>Método</th>
                <th>Estado</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
HTML;

foreach ($rows as $row) {
    $method_label = match ($row['payment_method']) {
        'bank_transfer' => 'Transferência',
        'multicaixa'    => 'Multicaixa',
        'paypal'        => 'PayPal',
        'card'          => 'Cartão',
        default         => ucfirst($row['payment_method']),
    };
    $status_label = match ($row['status_payment']) {
        'approved' => 'Aprovado',
        'pending'  => 'Pendente',
        'rejected' => 'Rejeitado',
        'refunded' => 'Reembolsado',
        default    => ucfirst($row['status_payment']),
    };
    echo '<tr>';
    echo '<td>' . $row['id_payment'] . '</td>';
    echo '<td>' . htmlspecialchars($row['payment_ref']) . '</td>';
    echo '<td>' . htmlspecialchars($row['user_name'] ?: $row['email_user']) . '</td>';
    echo '<td>' . htmlspecialchars($row['email_user']) . '</td>';
    echo '<td>' . htmlspecialchars($row['name_plan']) . '</td>';
    echo '<td>' . number_format((float)$row['amount'], 2) . '</td>';
    echo '<td>' . $method_label . '</td>';
    echo '<td>' . $status_label . '</td>';
    echo '<td>' . date('d/m/Y', strtotime($row['creat_payment'])) . '</td>';
    echo '</tr>';
}

echo <<<HTML
        </tbody>
    </table>
    <div class="footer">
        Documento gerado automaticamente em $date. Total de registos: {$stmt->rowCount()}
    </div>
    <div class="no-print">
        <button onclick="window.print();">Imprimir / Salvar como PDF</button>
        <button onclick="window.close();">Fechar</button>
    </div>
</body>
</html>
HTML;