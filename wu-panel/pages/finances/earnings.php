<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Finanças e Rendimentos
// Arquivo: wu-panel/pages/finances/index.php
// Rota:    wu-panel/finances
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// ── Exportar CSV (antes de qualquer output HTML) ──────────────────────────────
$export = $_GET['export'] ?? '';

if (in_array($export, ['payments', 'royalties', 'withdrawals', 'transactions'], true)) {

    if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_GET['csrf'] ?? '')) {
        http_response_code(403);
        exit('Forbidden');
    }

    $filename = 'wasom_' . $export . '_' . date('Y-m-d_H-i') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store');
    echo "\xEF\xBB\xBF"; // BOM UTF-8 para Excel

    $out = fopen('php://output', 'w');

    if ($export === 'payments') {
        $headers = ['ID', 'Referência', 'Utilizador', 'E-mail', 'Plano', 'Valor', 'Moeda', 'Método', 'Estado', 'Data'];
        fputcsv($out, $headers, ';');
        $rows = $db->query("
            SELECT p.id_payment, p.payment_ref,
                   CONCAT(u.first_name,' ',COALESCE(u.second_name,'')) AS user_name,
                   u.email_user, pl.name_plan,
                   p.amount, p.currency, p.payment_method, p.status_payment,
                   DATE_FORMAT(p.creat_payment,'%d/%m/%Y %H:%i') AS creat
            FROM _payment p
            LEFT JOIN _users u ON u.id_users = p.id_users
            LEFT JOIN _plans pl ON pl.id_plan = p.id_plan
            ORDER BY p.creat_payment DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id_payment'],
                $r['payment_ref'],
                $r['user_name'],
                $r['email_user'],
                $r['name_plan'],
                $r['amount'],
                $r['currency'],
                $r['payment_method'],
                $r['status_payment'],
                $r['creat']
            ], ';');
        }
    } elseif ($export === 'royalties') {
        $headers = ['ID', 'Utilizador', 'Artista', 'Faixa', 'Mês/Ano', 'Receita Bruta', 'Royalty Líq. AOA', 'Estado', 'Pago em'];
        fputcsv($out, $headers, ';');
        $rows = $db->query("
            SELECT r.id_royalty,
                   CONCAT(u.first_name,' ',COALESCE(u.second_name,'')) AS user_name,
                   a.stage_name, t.title_track,
                   CONCAT(LPAD(r.month_royalty,2,'0'),'/',r.year_royalty) AS period,
                   r.gross_revenue, r.net_royalty_aoa,
                   r.status_royalty,
                   COALESCE(DATE_FORMAT(r.paid_at,'%d/%m/%Y'),'—') AS paid_at
            FROM _royalty r
            LEFT JOIN _users u ON u.id_users = r.id_users
            LEFT JOIN _track t ON t.id_track = r.id_track
            LEFT JOIN _album al ON al.id_album = t.id_album
            LEFT JOIN _artist a ON a.id_artist = al.id_artist
            ORDER BY r.creat_royalty DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id_royalty'],
                $r['user_name'],
                $r['stage_name'],
                $r['title_track'],
                $r['period'],
                $r['gross_revenue'],
                $r['net_royalty_aoa'],
                $r['status_royalty'],
                $r['paid_at']
            ], ';');
        }
    } elseif ($export === 'withdrawals') {
        $headers = ['ID', 'Utilizador', 'Titular Conta', 'IBAN', 'Valor Req.', 'Valor Líq.', 'Moeda', 'Estado', 'Processado em', 'Data Pedido'];
        fputcsv($out, $headers, ';');
        $rows = $db->query("
            SELECT w.id_withdrawal,
                   CONCAT(u.first_name,' ',COALESCE(u.second_name,'')) AS user_name,
                   a.full_name_account, a.iban,
                   w.amount_requested, w.amount_net, w.currency,
                   w.status_withdrawal,
                   COALESCE(DATE_FORMAT(w.reviewed_at,'%d/%m/%Y'),'—') AS reviewed_at,
                   DATE_FORMAT(w.creat_withdrawal,'%d/%m/%Y %H:%i') AS creat
            FROM _withdrawal w
            LEFT JOIN _users u ON u.id_users = w.id_users
            LEFT JOIN _account a ON a.id_account = w.id_account
            ORDER BY w.creat_withdrawal DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id_withdrawal'],
                $r['user_name'],
                $r['full_name_account'],
                $r['iban'],
                $r['amount_requested'],
                $r['amount_net'],
                $r['currency'],
                $r['status_withdrawal'],
                $r['reviewed_at'],
                $r['creat']
            ], ';');
        }
    } elseif ($export === 'transactions') {
        $headers = ['ID', 'Utilizador', 'Tipo', 'Valor', 'Moeda', 'Saldo Antes', 'Saldo Depois', 'Descrição', 'Data'];
        fputcsv($out, $headers, ';');
        $rows = $db->query("
            SELECT tx.id_transaction,
                   COALESCE(CONCAT(u.first_name,' ',COALESCE(u.second_name,'')),'Sistema') AS user_name,
                   tx.type_transaction, tx.amount, tx.currency,
                   tx.balance_before, tx.balance_after, tx.description,
                   DATE_FORMAT(tx.creat_transaction,'%d/%m/%Y %H:%i') AS creat
            FROM _transaction tx
            LEFT JOIN _users u ON u.id_users = tx.id_users
            ORDER BY tx.creat_transaction DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id_transaction'],
                $r['user_name'],
                $r['type_transaction'],
                $r['amount'],
                $r['currency'],
                $r['balance_before'],
                $r['balance_after'],
                $r['description'],
                $r['creat']
            ], ';');
        }
    }

    fclose($out);
    exit;
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function fin_fmt(float $v, string $currency = 'AOA'): string
{
    if ($v >= 1_000_000) return 'Kz ' . number_format($v / 1_000_000, 1, ',', '.') . 'M';
    if ($v >= 1_000)     return 'Kz ' . number_format($v / 1_000, 1, ',', '.') . 'mil';
    return 'Kz ' . number_format($v, 2, ',', '.');
}

function fin_fmt_full(float $v): string
{
    return 'Kz ' . number_format($v, 2, ',', '.');
}

function fin_status_badge(string $s): string
{
    return match ($s) {
        'approved', 'paid'       => '<span class="badge fin-s-approved">Aprovado</span>',
        'pending'                => '<span class="badge fin-s-pending">Pendente</span>',
        'processing'             => '<span class="badge fin-s-processing">A processar</span>',
        'rejected', 'cancelled'  => '<span class="badge fin-s-rejected">Rejeitado</span>',
        'refunded'               => '<span class="badge fin-s-refunded">Reembolsado</span>',
        default                  => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}

function fin_relative(string $date): string
{
    $ts   = strtotime($date);
    $diff = time() - $ts;
    if ($diff < 60)     return 'agora';
    if ($diff < 3600)   return floor($diff / 60) . 'min';
    if ($diff < 86400)  return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'd';
    return date('d/m/Y', $ts);
}

// ── Estatísticas — queries separadas (mais seguras) ─────────────────────────
$total_revenue = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM _payment WHERE status_payment='approved'")->fetchColumn();
$total_pending_payments = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM _payment WHERE status_payment='pending'")->fetchColumn();
$royalties_paid = (float)$db->query("SELECT COALESCE(SUM(net_royalty_aoa),0) FROM _royalty WHERE status_royalty='paid'")->fetchColumn();
$royalties_pending = (float)$db->query("SELECT COALESCE(SUM(net_royalty_aoa),0) FROM _royalty WHERE status_royalty NOT IN ('paid','cancelled')")->fetchColumn();
$withdrawals_pending_count = (int)$db->query("SELECT COUNT(*) FROM _withdrawal WHERE status_withdrawal='pending'")->fetchColumn();
$withdrawals_pending_amount = (float)$db->query("SELECT COALESCE(SUM(amount_requested),0) FROM _withdrawal WHERE status_withdrawal='pending'")->fetchColumn();
$total_wallets_balance = (float)$db->query("SELECT COALESCE(SUM(balance_aoa),0) FROM _wallet")->fetchColumn();
$total_withdrawn = (float)$db->query("SELECT COALESCE(SUM(amount_net),0) FROM _withdrawal WHERE status_withdrawal='approved'")->fetchColumn();
$intents_under_review = (int)$db->query("SELECT COUNT(*) FROM _payment_intent WHERE status='under_review'")->fetchColumn();
$intents_revenue = (float)$db->query("SELECT COALESCE(SUM(amount_expected),0) FROM _payment_intent WHERE status='under_review'")->fetchColumn();
$withdrawals_month = (float)$db->query("SELECT COALESCE(SUM(amount_net),0) FROM _withdrawal WHERE status_withdrawal='approved' AND MONTH(reviewed_at)=MONTH(NOW()) AND YEAR(reviewed_at)=YEAR(NOW())")->fetchColumn();
$active_users = (int)$db->query("SELECT COUNT(*) FROM _users WHERE status_user='active'")->fetchColumn();
$total_artists = (int)$db->query("SELECT COUNT(*) FROM _artist")->fetchColumn();
$total_transactions = (int)$db->query("SELECT COUNT(*) FROM _transaction")->fetchColumn();

// ── Gráfico — uma query GROUP BY em vez de 12 loops ─────────────────────────
$chart_revenue_raw = $db->query("
    SELECT DATE_FORMAT(creat_payment,'%Y-%m') AS ym,
           COALESCE(SUM(amount),0) AS total
    FROM _payment
    WHERE status_payment='approved'
      AND creat_payment >= DATE_SUB(NOW(), INTERVAL 11 MONTH)
    GROUP BY ym
")->fetchAll(PDO::FETCH_KEY_PAIR);

$chart_royalty_raw = $db->query("
    SELECT DATE_FORMAT(paid_at,'%Y-%m') AS ym,
           COALESCE(SUM(net_royalty_aoa),0) AS total
    FROM _royalty
    WHERE status_royalty='paid'
      AND paid_at >= DATE_SUB(NOW(), INTERVAL 11 MONTH)
    GROUP BY ym
")->fetchAll(PDO::FETCH_KEY_PAIR);

$chart_withdrawals_raw = $db->query("
    SELECT DATE_FORMAT(reviewed_at,'%Y-%m') AS ym,
           COALESCE(SUM(amount_net),0) AS total
    FROM _withdrawal
    WHERE status_withdrawal='approved'
      AND reviewed_at >= DATE_SUB(NOW(), INTERVAL 11 MONTH)
    GROUP BY ym
")->fetchAll(PDO::FETCH_KEY_PAIR);

$chart_labels = [];
$chart_revenue = [];
$chart_royalties = [];
$chart_withdrawals_chart = [];
for ($i = 11; $i >= 0; $i--) {
    $ym    = date('Y-m', strtotime("-$i months"));
    $label = date('M/y', strtotime($ym . '-01'));
    $chart_labels[]           = $label;
    $chart_revenue[]          = round($chart_revenue_raw[$ym]   ?? 0, 2);
    $chart_royalties[]        = round($chart_royalty_raw[$ym]   ?? 0, 2);
    $chart_withdrawals_chart[] = round($chart_withdrawals_raw[$ym] ?? 0, 2);
}

// ── Tabelas recentes ─────────────────────────────────────────────────────────
$recent_payments = $db->query("
    SELECT p.id_payment, p.payment_ref, p.amount, p.currency, p.status_payment, p.creat_payment,
           p.payment_method,
           u.id_users, CONCAT(u.first_name,' ',COALESCE(u.second_name,'')) AS user_name,
           u.email_user, u.photo_user,
           pl.name_plan
    FROM _payment p
    LEFT JOIN _users u ON u.id_users = p.id_users
    LEFT JOIN _plans pl ON pl.id_plan = p.id_plan
    ORDER BY p.creat_payment DESC
    LIMIT 8
")->fetchAll();

$recent_royalties = $db->query("
    SELECT r.id_royalty, r.net_royalty_aoa, r.status_royalty, r.paid_at,
           r.year_royalty, r.month_royalty,
           t.title_track,
           a.stage_name, a.id_artist,
           u.id_users, CONCAT(u.first_name,' ',COALESCE(u.second_name,'')) AS user_name,
           u.photo_user
    FROM _royalty r
    LEFT JOIN _track t ON t.id_track = r.id_track
    LEFT JOIN _album al ON al.id_album = t.id_album
    LEFT JOIN _artist a ON a.id_artist = al.id_artist
    LEFT JOIN _users u ON u.id_users = r.id_users
    ORDER BY r.creat_royalty DESC
    LIMIT 8
")->fetchAll();

$recent_withdrawals = $db->query("
    SELECT w.id_withdrawal, w.amount_requested, w.amount_net, w.status_withdrawal,
           w.creat_withdrawal, w.reviewed_at,
           u.id_users, CONCAT(u.first_name,' ',COALESCE(u.second_name,'')) AS user_name,
           u.photo_user,
           a.iban, a.full_name_account, a.type_account
    FROM _withdrawal w
    LEFT JOIN _users u ON u.id_users = w.id_users
    LEFT JOIN _account a ON a.id_account = w.id_account
    ORDER BY w.creat_withdrawal DESC
    LIMIT 8
")->fetchAll();

$recent_transactions = $db->query("
    SELECT tx.id_transaction, tx.type_transaction, tx.amount, tx.currency,
           tx.balance_before, tx.balance_after, tx.description, tx.creat_transaction,
           COALESCE(CONCAT(u.first_name,' ',COALESCE(u.second_name,'')),'Sistema') AS user_name,
           u.id_users, u.photo_user
    FROM _transaction tx
    LEFT JOIN _users u ON u.id_users = tx.id_users
    ORDER BY tx.creat_transaction DESC
    LIMIT 8
")->fetchAll();

// ── Helpers visuais ──────────────────────────────────────────────────────────
function fin_avatar(string $name, ?string $photo, string $size = '32'): string
{
    $ini = '';
    $parts = explode(' ', trim($name), 2);
    $ini .= mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1, 'UTF-8'), 'UTF-8');
    $ini .= mb_strtoupper(mb_substr($parts[1] ?? '', 0, 1, 'UTF-8'), 'UTF-8');
    $colors = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308', '#3b82f6', '#ef4444'];
    $color  = $colors[abs(crc32($name)) % count($colors)];
    $s = (int)$size;

    if ($photo) {
        return '<img src="' . APP_URL . '/assets/comprovantes/uploads/users/' . htmlspecialchars($photo) . '"
                     width="' . $s . '" height="' . $s . '"
                     style="border-radius:50%;object-fit:cover;border:2px solid rgba(255,0,137,.2)"
                     onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'"
                     alt="" />
                <div style="width:' . $s . 'px;height:' . $s . 'px;border-radius:50%;background:' . $color . ';
                            display:none;align-items:center;justify-content:center;
                            font-weight:700;font-size:' . ($s * 0.3) . 'px;color:#fff;flex-shrink:0">' . $ini . '</div>';
    }
    return '<div style="width:' . $s . 'px;height:' . $s . 'px;border-radius:50%;background:' . $color . ';
                         display:flex;align-items:center;justify-content:center;
                         font-weight:700;font-size:' . ($s * 0.3) . 'px;color:#fff;flex-shrink:0">' . $ini . '</div>';
}

function fin_tx_icon(string $type): string
{
    return match ($type) {
        'royalty_credit' => '<i class="bi bi-music-note-beamed text-success"></i>',
        'withdrawal'     => '<i class="bi bi-arrow-up-circle text-danger"></i>',
        'plan_payment'   => '<i class="bi bi-credit-card text-primary"></i>',
        'refund'         => '<i class="bi bi-arrow-counterclockwise text-warning"></i>',
        'adjustment'     => '<i class="bi bi-sliders text-info"></i>',
        'fee'            => '<i class="bi bi-percent text-secondary"></i>',
        default          => '<i class="bi bi-arrow-left-right text-muted"></i>',
    };
}
$csrf_export = $_SESSION['admin_csrf_token'];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title>Finanças e Rendimentos — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        /* ── Status badges ── */
        .fin-s-approved {
            background: rgba(34, 197, 94, .15);
            color: #166534;
        }

        .fin-s-pending {
            background: rgba(234, 179, 8, .15);
            color: #92400e;
        }

        .fin-s-processing {
            background: rgba(59, 130, 246, .15);
            color: #1e40af;
        }

        .fin-s-rejected {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
        }

        .fin-s-refunded {
            background: rgba(107, 114, 128, .15);
            color: #374151;
        }

        .dark-mode .fin-s-approved {
            background: rgba(34, 197, 94, .18);
            color: #4ade80;
        }

        .dark-mode .fin-s-pending {
            background: rgba(234, 179, 8, .18);
            color: #facc15;
        }

        .dark-mode .fin-s-processing {
            background: rgba(59, 130, 246, .18);
            color: #60a5fa;
        }

        .dark-mode .fin-s-rejected {
            background: rgba(239, 68, 68, .18);
            color: #f87171;
        }

        .dark-mode .fin-s-refunded {
            background: rgba(107, 114, 128, .18);
            color: #9ca3af;
        }

        /* ── Stat cards ── */
        .fin-stat {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: transform .2s, box-shadow .2s;
        }

        .fin-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, .06);
        }

        .fin-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 11px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .fin-stat-val {
            font-size: 1.2rem;
            font-weight: 800;
            line-height: 1;
        }

        .fin-stat-lbl {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            opacity: .6;
            margin-top: 3px;
        }

        .fin-stat-sub {
            font-size: .72rem;
            margin-top: 4px;
        }

        /* ── Secção tabs ── */
        .fin-tabs .nav-link {
            font-size: .82rem;
            font-weight: 600;
            border-radius: 8px;
            padding: 6px 14px;
            color: var(--bs-body-color);
        }

        .fin-tabs .nav-link.active {
            background: #FF0089;
            color: #fff;
            border: none;
        }

        .fin-tabs .nav-link:not(.active):hover {
            background: rgba(255, 0, 137, .08);
        }

        /* ── Tabelas ── */
        .fin-table th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 700;
            white-space: nowrap;
        }

        .fin-table td {
            font-size: .8rem;
            vertical-align: middle;
        }

        /* ── Gráfico wrapper ── */
        .chart-wrapper {
            position: relative;
            height: 260px;
        }

        /* ── Botões export ── */
        .btn-export {
            font-size: .76rem;
            padding: 4px 12px;
        }

        /* ── TX type label ── */
        .tx-type-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            opacity: .65;
        }

        /* ── Trend badge ── */
        .trend-up {
            color: #22c55e;
            font-size: .72rem;
            font-weight: 700;
        }

        .trend-down {
            color: #ef4444;
            font-size: .72rem;
            font-weight: 700;
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
                        <h2 class="h4 mb-1"><i class="bi bi-cash-stack me-2"></i>Finanças e Rendimentos</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>" class="text-secondary">Home</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable">Finanças</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2 flex-wrap">
                        <?php if (hasPermission($admin_id, 'finances.view')): ?>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="bi bi-download me-1"></i> Exportar CSV
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item"
                                            href="?export=payments&csrf=<?php echo urlencode($csrf_export); ?>">
                                            <i class="bi bi-credit-card me-2"></i>Pagamentos
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item"
                                            href="?export=royalties&csrf=<?php echo urlencode($csrf_export); ?>">
                                            <i class="bi bi-music-note-beamed me-2"></i>Royalties
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item"
                                            href="?export=withdrawals&csrf=<?php echo urlencode($csrf_export); ?>">
                                            <i class="bi bi-wallet2 me-2"></i>Saques
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item"
                                            href="?export=transactions&csrf=<?php echo urlencode($csrf_export); ?>">
                                            <i class="bi bi-arrow-left-right me-2"></i>Transacções
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/withdrawals" target="_blank"
                            class="btn btn-sm text-white" style="background:#FF0089;border-color:#FF0089">
                            <i class="bi bi-check2-circle me-1"></i> Aprovar Pagamentos
                            <?php if ($intents_under_review > 0): ?>
                                <span class="badge bg-white text-danger ms-1"><?php echo $intents_under_review; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>

                <!-- ── Fila 1 de cards ── -->
                <div class="row g-3 mb-3">
                    <!-- Receita Total -->
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="fin-stat">
                            <div class="fin-stat-icon" style="background:rgba(255,0,137,.1)">
                                <i class="bi bi-cash-stack" style="color:#FF0089"></i>
                            </div>
                            <div>
                                <div class="fin-stat-val" title="<?php echo fin_fmt_full($total_revenue); ?>">
                                    <?php echo fin_fmt($total_revenue); ?>
                                </div>
                                <div class="fin-stat-lbl">Receita Total</div>
                            </div>
                        </div>
                    </div>
                    <!-- Royalties Pagos -->
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="fin-stat">
                            <div class="fin-stat-icon" style="background:rgba(34,197,94,.1)">
                                <i class="bi bi-check-circle-fill text-success"></i>
                            </div>
                            <div>
                                <div class="fin-stat-val" title="<?php echo fin_fmt_full($royalties_paid); ?>">
                                    <?php echo fin_fmt($royalties_paid); ?>
                                </div>
                                <div class="fin-stat-lbl">Royalties Pagos</div>
                            </div>
                        </div>
                    </div>
                    <!-- Royalties Pendentes -->
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="fin-stat">
                            <div class="fin-stat-icon" style="background:rgba(234,179,8,.1)">
                                <i class="bi bi-hourglass-split text-warning"></i>
                            </div>
                            <div>
                                <div class="fin-stat-val" title="<?php echo fin_fmt_full($royalties_pending); ?>">
                                    <?php echo fin_fmt($royalties_pending); ?>
                                </div>
                                <div class="fin-stat-lbl">Royalties Pend.</div>
                            </div>
                        </div>
                    </div>
                    <!-- Saques Pendentes -->
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="fin-stat">
                            <div class="fin-stat-icon" style="background:rgba(239,68,68,.1)">
                                <i class="bi bi-arrow-up-circle text-danger"></i>
                            </div>
                            <div>
                                <div class="fin-stat-val"><?php echo $withdrawals_pending_count; ?></div>
                                <div class="fin-stat-lbl">Saques Pend.</div>
                                <div class="fin-stat-sub text-danger">
                                    <?php echo fin_fmt($withdrawals_pending_amount); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Pagamentos em Revisão -->
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="fin-stat">
                            <div class="fin-stat-icon" style="background:rgba(59,130,246,.1)">
                                <i class="bi bi-search text-primary"></i>
                            </div>
                            <div>
                                <div class="fin-stat-val"><?php echo $intents_under_review; ?></div>
                                <div class="fin-stat-lbl">Em Revisão</div>
                                <div class="fin-stat-sub" style="color:#3b82f6">
                                    <?php echo fin_fmt($intents_revenue); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Saldo Total Wallets -->
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="fin-stat">
                            <div class="fin-stat-icon" style="background:rgba(139,92,246,.1)">
                                <i class="bi bi-wallet2" style="color:#8b5cf6"></i>
                            </div>
                            <div>
                                <div class="fin-stat-val" title="<?php echo fin_fmt_full($total_wallets_balance); ?>">
                                    <?php echo fin_fmt($total_wallets_balance); ?>
                                </div>
                                <div class="fin-stat-lbl">Saldo em Wallets</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Fila 2 de cards ── -->
                <div class="row g-3 mb-4">
                    <!-- Saques este mês -->
                    <div class="col-6 col-md-3">
                        <div class="fin-stat">
                            <div class="fin-stat-icon" style="background:rgba(20,184,166,.1)">
                                <i class="bi bi-calendar-check" style="color:#14b8a6"></i>
                            </div>
                            <div>
                                <div class="fin-stat-val" title="<?php echo fin_fmt_full($withdrawals_month); ?>">
                                    <?php echo fin_fmt($withdrawals_month); ?>
                                </div>
                                <div class="fin-stat-lbl">Saques este Mês</div>
                            </div>
                        </div>
                    </div>
                    <!-- Total sacado histórico -->
                    <div class="col-6 col-md-3">
                        <div class="fin-stat">
                            <div class="fin-stat-icon" style="background:rgba(107,114,128,.1)">
                                <i class="bi bi-arrow-down-circle text-secondary"></i>
                            </div>
                            <div>
                                <div class="fin-stat-val" title="<?php echo fin_fmt_full($total_withdrawn); ?>">
                                    <?php echo fin_fmt($total_withdrawn); ?>
                                </div>
                                <div class="fin-stat-lbl">Total Sacado</div>
                            </div>
                        </div>
                    </div>
                    <!-- Utilizadores activos -->
                    <div class="col-6 col-md-3">
                        <div class="fin-stat">
                            <div class="fin-stat-icon" style="background:rgba(6,182,212,.1)">
                                <i class="bi bi-people text-info"></i>
                            </div>
                            <div>
                                <div class="fin-stat-val"><?php echo number_format($active_users); ?></div>
                                <div class="fin-stat-lbl">Utilizadores Activos</div>
                            </div>
                        </div>
                    </div>
                    <!-- Total de transacções -->
                    <div class="col-6 col-md-3">
                        <div class="fin-stat">
                            <div class="fin-stat-icon" style="background:rgba(234,179,8,.1)">
                                <i class="bi bi-arrow-left-right text-warning"></i>
                            </div>
                            <div>
                                <div class="fin-stat-val"><?php echo number_format($total_transactions); ?></div>
                                <div class="fin-stat-lbl">Total Transacções</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Gráfico ── -->
                <div class="card mb-4">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                        style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                        <span style="font-weight:600;font-size:.88rem">
                            <i class="bi bi-graph-up me-1"></i> Evolução Financeira — Últimos 12 meses
                        </span>
                        <div class="d-flex gap-3 align-items-center" style="font-size:.73rem">
                            <span><span
                                    style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#FF0089;margin-right:4px"></span>Receita</span>
                            <span><span
                                    style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#22c55e;margin-right:4px"></span>Royalties
                                Pagos</span>
                            <span><span
                                    style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#f97316;margin-right:4px"></span>Saques</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrapper">
                            <canvas id="financeChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- ── Tabs das tabelas ── -->
                <div class="card">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                        style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                        <ul class="nav fin-tabs gap-1 mb-0" id="finTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPayments">
                                    <i class="bi bi-credit-card me-1"></i>Pagamentos
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRoyalties">
                                    <i class="bi bi-music-note-beamed me-1"></i>Royalties
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabWithdrawals">
                                    <i class="bi bi-wallet2 me-1"></i>Saques
                                    <?php if ($withdrawals_pending_count > 0): ?>
                                        <span class="badge" style="background:#FF0089;font-size:.6rem">
                                            <?php echo $withdrawals_pending_count; ?>
                                        </span>
                                    <?php endif; ?>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabTransactions">
                                    <i class="bi bi-arrow-left-right me-1"></i>Transacções
                                </button>
                            </li>
                        </ul>
                        <div id="tab-export-btns" class="d-flex gap-2"></div>
                    </div>

                    <div class="tab-content" id="finTabContent">

                        <!-- Tab Pagamentos -->
                        <div class="tab-pane fade show active" id="tabPayments">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2"
                                style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                                <span style="font-size:.78rem;opacity:.6">Últimos 8 pagamentos</span>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/payments"
                                    class="btn btn-sm btn-outline-secondary btn-export">
                                    Ver todos <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover fin-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Utilizador</th>
                                            <th>Plano</th>
                                            <th>Valor</th>
                                            <th>Método</th>
                                            <th>Estado</th>
                                            <th>Data</th>
                                            <?php if (hasPermission($admin_id, 'finances.edit')): ?>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_payments)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">Nenhum pagamento
                                                    encontrado</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recent_payments as $pay): ?>
                                                <tr>
                                                    <td><span
                                                            style="font-family:monospace;font-size:.73rem;opacity:.5">#<?php echo $pay['id_payment']; ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <?php echo fin_avatar($pay['user_name'] ?: $pay['email_user'], $pay['photo_user'], '30'); ?>
                                                            <div>
                                                                <div style="font-size:.8rem;font-weight:600">
                                                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo (int)$pay['id_users']; ?>"
                                                                        class="text-inherit text-decoration-none">
                                                                        <?php echo htmlspecialchars(trim($pay['user_name']) ?: $pay['email_user']); ?>
                                                                    </a>
                                                                </div>
                                                                <div style="font-size:.7rem;opacity:.5">
                                                                    <?php echo htmlspecialchars($pay['payment_ref']); ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($pay['name_plan'] ?? '—'); ?></td>
                                                    <td style="font-weight:700;white-space:nowrap">
                                                        <?php echo fin_fmt((float)$pay['amount'], $pay['currency']); ?>
                                                    </td>
                                                    <td>
                                                        <span style="font-size:.75rem;text-transform:capitalize">
                                                            <?php echo str_replace('_', ' ', $pay['payment_method'] ?? '—'); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo fin_status_badge($pay['status_payment']); ?></td>
                                                    <td style="white-space:nowrap;font-size:.75rem">
                                                        <?php echo date('d/m/Y', strtotime($pay['creat_payment'])); ?>
                                                        <div style="opacity:.5">
                                                            <?php echo fin_relative($pay['creat_payment']); ?></div>
                                                    </td>
                                                    <?php if (hasPermission($admin_id, 'finances.edit')): ?>
                                                        <td>
                                                            <?php if ($pay['status_payment'] === 'pending'): ?>
                                                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/finances/payments?review=<?php echo (int)$pay['id_payment']; ?>"
                                                                    class="btn btn-sm"
                                                                    style="background:#FF0089;color:#fff;font-size:.72rem;padding:3px 10px">
                                                                    Rever
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab Royalties -->
                        <div class="tab-pane fade" id="tabRoyalties">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2"
                                style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                                <span style="font-size:.78rem;opacity:.6">Últimos 8 royalties</span>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/royalties-splits"
                                    class="btn btn-sm btn-outline-secondary btn-export">
                                    Ver todos <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover fin-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Artista</th>
                                            <th>Faixa</th>
                                            <th>Período</th>
                                            <th>Royalty (AOA)</th>
                                            <th>Estado</th>
                                            <th>Pago em</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_royalties)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">Nenhum royalty
                                                    encontrado</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recent_royalties as $roy): ?>
                                                <tr>
                                                    <td><span
                                                            style="font-family:monospace;font-size:.73rem;opacity:.5">#<?php echo $roy['id_royalty']; ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <?php echo fin_avatar($roy['stage_name'] ?: $roy['user_name'], $roy['photo_user'], '30'); ?>
                                                            <div>
                                                                <div style="font-size:.8rem;font-weight:600">
                                                                    <?php echo htmlspecialchars($roy['stage_name'] ?: $roy['user_name'] ?: '—'); ?>
                                                                </div>
                                                                <div style="font-size:.7rem;opacity:.5">
                                                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo (int)$roy['id_users']; ?>"
                                                                        class="text-inherit text-decoration-none">
                                                                        <?php echo htmlspecialchars($roy['user_name']); ?>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td style="font-size:.78rem">
                                                        <?php echo htmlspecialchars($roy['title_track'] ?? '—'); ?>
                                                    </td>
                                                    <td style="font-size:.76rem;white-space:nowrap">
                                                        <?php echo str_pad((int)$roy['month_royalty'], 2, '0', STR_PAD_LEFT) . '/' . $roy['year_royalty']; ?>
                                                    </td>
                                                    <td style="font-weight:700;white-space:nowrap">
                                                        <?php echo fin_fmt((float)$roy['net_royalty_aoa']); ?>
                                                    </td>
                                                    <td><?php echo fin_status_badge($roy['status_royalty']); ?></td>
                                                    <td style="font-size:.75rem;white-space:nowrap">
                                                        <?php echo $roy['paid_at'] ? date('d/m/Y', timestamp: strtotime($roy['paid_at'])) : '—'; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab Saques -->
                        <div class="tab-pane fade" id="tabWithdrawals">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2"
                                style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                                <span style="font-size:.78rem;opacity:.6">Últimos 8 saques</span>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/withdrawals"
                                    class="btn btn-sm btn-outline-secondary btn-export">
                                    Ver todos <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover fin-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Utilizador</th>
                                            <th>Conta Destino</th>
                                            <th>Valor Req.</th>
                                            <th>Valor Líq.</th>
                                            <th>Estado</th>
                                            <th>Data Pedido</th>
                                            <th>Acções</th>
                                            <?php if (hasPermission($admin_id, 'finances.edit')): ?>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_withdrawals)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">Nenhum saque encontrado
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recent_withdrawals as $wd): ?>
                                                <tr>
                                                    <td><span
                                                            style="font-family:monospace;font-size:.73rem;opacity:.5">#<?php echo $wd['id_withdrawal']; ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <?php echo fin_avatar($wd['user_name'], $wd['photo_user'], '30'); ?>
                                                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo (int)$wd['id_users']; ?>"
                                                                class="text-inherit text-decoration-none"
                                                                style="font-size:.8rem;font-weight:600">
                                                                <?php echo htmlspecialchars($wd['user_name']); ?>
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div style="font-size:.78rem">
                                                            <?php echo htmlspecialchars($wd['full_name_account'] ?? '—'); ?>
                                                        </div>
                                                        <div style="font-size:.7rem;opacity:.5;font-family:monospace">
                                                            <?php
                                                            $iban = $wd['iban'] ?? '';
                                                            echo $iban ? '···' . substr($iban, -6) : ($wd['type_account'] ?? '');
                                                            ?>
                                                        </div>
                                                    </td>
                                                    <td style="font-weight:700;white-space:nowrap">
                                                        <?php echo fin_fmt((float)$wd['amount_requested']); ?>
                                                    </td>
                                                    <td style="white-space:nowrap;color:#22c55e;font-weight:600">
                                                        <?php echo $wd['amount_net'] ? fin_fmt((float)$wd['amount_net']) : '—'; ?>
                                                    </td>
                                                    <td><?php echo fin_status_badge($wd['status_withdrawal']); ?></td>
                                                    <td style="white-space:nowrap;font-size:.75rem">
                                                        <?php echo date('d/m/Y', strtotime($wd['creat_withdrawal'])); ?>
                                                        <div style="opacity:.5">
                                                            <?php echo fin_relative($wd['creat_withdrawal']); ?></div>
                                                    </td>
                                                    <?php if (hasPermission($admin_id, 'finances.edit')): ?>
                                                        <td>
                                                            <?php if (in_array($wd['status_withdrawal'], ['pending', 'processing'])): ?>
                                                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/withdrawals?review=<?php echo (int)$wd['id_withdrawal']; ?>"
                                                                    class="btn btn-sm"
                                                                    style="background:#FF0089;color:#fff;font-size:.72rem;padding:3px 10px">
                                                                    Processar
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab Transacções -->
                        <div class="tab-pane fade" id="tabTransactions">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2"
                                style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                                <span style="font-size:.78rem;opacity:.6">Últimas 8 transacções</span>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/manager/transactions"
                                    class="btn btn-sm btn-outline-secondary btn-export">
                                    Ver todas <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover fin-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Tipo</th>
                                            <th>Utilizador</th>
                                            <th>Valor</th>
                                            <th>Saldo Antes → Depois</th>
                                            <th>Descrição</th>
                                            <th>Data</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_transactions)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">Nenhuma transacção
                                                    encontrada</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recent_transactions as $tx): ?>
                                                <tr>
                                                    <td><span
                                                            style="font-family:monospace;font-size:.73rem;opacity:.5">#<?php echo $tx['id_transaction']; ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <?php echo fin_tx_icon($tx['type_transaction']); ?>
                                                            <span class="tx-type-label">
                                                                <?php echo str_replace('_', ' ', $tx['type_transaction']); ?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($tx['id_users']): ?>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <?php echo fin_avatar($tx['user_name'], $tx['photo_user'], '26'); ?>
                                                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo (int)$tx['id_users']; ?>"
                                                                    class="text-inherit text-decoration-none"
                                                                    style="font-size:.78rem">
                                                                    <?php echo htmlspecialchars($tx['user_name']); ?>
                                                                </a>
                                                            </div>
                                                        <?php else: ?>
                                                            <span style="font-size:.76rem;opacity:.5">Sistema</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td
                                                        style="font-weight:700;white-space:nowrap;
                                            color:<?php echo in_array($tx['type_transaction'], ['withdrawal', 'fee']) ? '#ef4444' : '#22c55e'; ?>">
                                                        <?php echo ($tx['type_transaction'] === 'withdrawal' ? '−' : '+'); ?>
                                                        <?php echo fin_fmt((float)$tx['amount'], $tx['currency']); ?>
                                                    </td>
                                                    <td style="font-size:.75rem;white-space:nowrap;font-family:monospace">
                                                        <?php
                                                        $before = $tx['balance_before'] !== null ? fin_fmt((float)$tx['balance_before']) : '—';
                                                        $after  = $tx['balance_after']  !== null ? fin_fmt((float)$tx['balance_after'])  : '—';
                                                        echo htmlspecialchars($before . ' → ' . $after);
                                                        ?>
                                                    </td>
                                                    <td style="font-size:.76rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                                        title="<?php echo htmlspecialchars($tx['description'] ?? ''); ?>">
                                                        <?php echo htmlspecialchars($tx['description'] ?? '—'); ?>
                                                    </td>
                                                    <td style="white-space:nowrap;font-size:.75rem">
                                                        <?php echo date('d/m/Y', strtotime($tx['creat_transaction'])); ?>
                                                        <div style="opacity:.5">
                                                            <?php echo fin_relative($tx['creat_transaction']); ?></div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div><!-- /tab-content -->
                </div><!-- /card tabs -->

                <!-- ── Links rápidos de gestão ── -->
                <div class="row mt-4 g-3 mb-4">
                    <?php
                    $quick_links = [
                        [
                            'icon'  => 'bi-bank2',
                            'color' => '#3b82f6',
                            'title' => 'Contas Bancárias',
                            'desc'  => 'Verificar e gerir contas bancárias dos utilizadores',
                            'url'   => APP_URL . '/' . ADMIN_PATH . '/account',
                            'perm'  => 'finances.view',
                        ],
                        [
                            'icon'  => 'bi-check2-circle',
                            'color' => '#FF0089',
                            'title' => 'Aprovar Pagamentos',
                            'desc'  => 'Rever comprovativos e aprovar ou rejeitar pagamentos',
                            'url'   => APP_URL . '/' . ADMIN_PATH . '/manager/withdrawals?filter=pending',
                            'perm'  => 'finances.edit',
                        ],
                        [
                            'icon'  => 'bi-arrow-up-circle',
                            'color' => '#ef4444',
                            'title' => 'Processar Saques',
                            'desc'  => 'Aprovar ou rejeitar pedidos de levantamento',
                            'url'   => APP_URL . '/' . ADMIN_PATH . '/manager/withdrawals',
                            'perm'  => 'finances.edit',
                        ],
                        [
                            'icon'  => 'bi-calculator',
                            'color' => '#22c55e',
                            'title' => 'Gestão de Royalties',
                            'desc'  => 'Ver e processar royalties por artista e período',
                            'url'   => APP_URL . '/' . ADMIN_PATH . '/manager/royalty-splits',
                            'perm'  => 'finances.view',
                        ],
                    ];
                    foreach ($quick_links as $ql):
                        if (!hasPermission($admin_id, $ql['perm'])) continue;
                    ?>
                        <div class="col-md-6 col-xl-3">
                            <a href="<?php echo $ql['url']; ?>" class="text-decoration-none" target="_blank">
                                <div class="fin-stat" style="cursor:pointer">
                                    <div class="fin-stat-icon" style="background:<?php echo $ql['color']; ?>22">
                                        <i class="bi <?php echo $ql['icon']; ?>"
                                            style="color:<?php echo $ql['color']; ?>"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:.85rem">
                                            <?php echo htmlspecialchars($ql['title']); ?>
                                        </div>
                                        <div style="font-size:.73rem;opacity:.6;margin-top:2px">
                                            <?php echo htmlspecialchars($ql['desc']); ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div><!-- /container-fluid -->
        </div><!-- /content -->
    </div><!-- /wrapper -->

    <footer>
        <div class="container">
            <div class="col-12 text-center py-2" style="font-size:.8rem">
                <p class="mb-0">© <?php echo date('Y'); ?> Wasom Upfy. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="" />
            <div class="loader-progress"></div>
        </div>
    </div>

    <!-- Chart.js — só o JS, sem CSS (não existe em v4) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
        (function() {
            'use strict';

            // ── Dados do gráfico injectados pelo PHP ──
            const chartLabels = <?php echo json_encode($chart_labels,      JSON_UNESCAPED_UNICODE); ?>;
            const chartRevenue = <?php echo json_encode($chart_revenue,      JSON_UNESCAPED_UNICODE); ?>;
            const chartRoyalties = <?php echo json_encode($chart_royalties,    JSON_UNESCAPED_UNICODE); ?>;
            const chartWithdrawals = <?php echo json_encode($chart_withdrawals_chart, JSON_UNESCAPED_UNICODE); ?>;

            // ── Detectar dark mode ──
            function isDark() {
                return document.body.classList.contains('dark-mode');
            }

            const gridColor = () => isDark() ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)';
            const labelColor = () => isDark() ? 'rgba(255,255,255,.55)' : 'rgba(0,0,0,.55)';

            // ── Tooltip formatter ──
            function fmtAOA(v) {
                if (v >= 1000000) {
                    return 'Kz ' + (v / 1000000).toFixed(1).replace('.', ',') + 'M';
                }
                if (v >= 1000) {
                    return 'Kz ' + (v / 1000).toFixed(1).replace('.', ',') + 'mil';
                }
                return 'Kz ' + v.toLocaleString('pt-AO', {
                    minimumFractionDigits: 2
                });
            }

            // ── Construir gráfico ──
            const canvas = document.getElementById('financeChart');
            if (!canvas) return;

            const finChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                            label: 'Receita',
                            data: chartRevenue,
                            borderColor: '#FF0089',
                            backgroundColor: 'rgba(255,0,137,.07)',
                            pointBackgroundColor: '#FF0089',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            tension: 0.35,
                            fill: true,
                            borderWidth: 2,
                        },
                        {
                            label: 'Royalties Pagos',
                            data: chartRoyalties,
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34,197,94,.06)',
                            pointBackgroundColor: '#22c55e',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            tension: 0.35,
                            fill: true,
                            borderWidth: 2,
                        },
                        {
                            label: 'Saques',
                            data: chartWithdrawals,
                            borderColor: '#f97316',
                            backgroundColor: 'rgba(249,115,22,.05)',
                            pointBackgroundColor: '#f97316',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            tension: 0.35,
                            fill: false,
                            borderWidth: 2,
                            borderDash: [5, 3],
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: isDark() ? '#1a1a2e' : '#fff',
                            borderColor: isDark() ? '#2e2e42' : '#e8e8f0',
                            borderWidth: 1,
                            titleColor: isDark() ? '#e8e8f0' : '#1a1a2e',
                            bodyColor: isDark() ? 'rgba(255,255,255,.7)' : 'rgba(0,0,0,.6)',
                            padding: 12,
                            callbacks: {
                                label: function(ctx) {
                                    return ' ' + ctx.dataset.label + ': ' + fmtAOA(ctx.raw);
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: {
                                color: gridColor()
                            },
                            ticks: {
                                color: labelColor(),
                                font: {
                                    size: 11
                                },
                                maxRotation: 0,
                            },
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor()
                            },
                            ticks: {
                                color: labelColor(),
                                font: {
                                    size: 11
                                },
                                callback: (v) => fmtAOA(v),
                            },
                        },
                    },
                },
            });

            // ── Actualizar cores do gráfico ao mudar dark mode ──
            const observer = new MutationObserver(function() {
                finChart.options.scales.x.grid.color = gridColor();
                finChart.options.scales.y.grid.color = gridColor();
                finChart.options.scales.x.ticks.color = labelColor();
                finChart.options.scales.y.ticks.color = labelColor();
                finChart.options.plugins.tooltip.backgroundColor = isDark() ? '#1a1a2e' : '#fff';
                finChart.options.plugins.tooltip.titleColor = isDark() ? '#e8e8f0' : '#1a1a2e';
                finChart.options.plugins.tooltip.borderColor = isDark() ? '#2e2e42' : '#e8e8f0';
                finChart.update('none');
            });
            observer.observe(document.body, {
                attributes: true,
                attributeFilter: ['class']
            });

        })();
    </script>
</body>

</html>