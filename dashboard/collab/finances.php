<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Finanças (Colaboradores)
// Arquivo: dashboard/collab/finances.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();

if (empty($_SESSION['collab_id']) || empty($_SESSION['collab_id_users'])) {
    header('Location: ' . rtrim(APP_URL, '/') . '/' . APP_URL_PANEL . '/account/collab-login');
    exit;
}
if (!empty($_SESSION['collab_must_change'])) {
    header('Location: ' . rtrim(APP_URL, '/') . '/' . APP_URL_PANEL . '/account/collab-login');
    exit;
}

$db = getDB();
$id_collab = (int)$_SESSION['collab_id'];
$id_users = (int)$_SESSION['collab_id_users'];
$role = $_SESSION['collab_role'] ?? 'support';

$cs = $db->prepare("SELECT * FROM _collaborators WHERE id_collab = ? AND id_users = ? AND status_collab = 'active' LIMIT
1");
$cs->execute([$id_collab, $id_users]);
$collab = $cs->fetch();
if (!$collab) {
    session_destroy();
    header('Location: ' . rtrim(APP_URL, '/') . '/' . APP_URL_PANEL . '/account/collab-login?error=access');
    exit;
}

$db->prepare("UPDATE _collaborators SET last_seen_at = NOW() WHERE id_collab = ?")->execute([$id_collab]);

$owner = getUserById($id_users);
if (!$owner) {
    session_destroy();
    header('Location: ' . rtrim(APP_URL, '/') . '/' . APP_URL_PANEL . '/account/collab-login');
    exit;
}

$owner_artist_name = htmlspecialchars($owner['name_artist_band'] ?? $owner['first_name']);
$owner_name = htmlspecialchars(trim($owner['first_name'] . ' ' . ($owner['second_name'] ?? '')));
$plan = null;
if ($owner['plan_selected']) {
    $ps = $db->prepare("SELECT * FROM _plans WHERE id_plan = ?");
    $ps->execute([$owner['plan_selected']]);
    $plan = $ps->fetch();
}
$plan_name = $plan ? htmlspecialchars($plan['name_plan']) : 'Sem plano';

// ── Permissões ────────────────────────────────
$can_view_releases = in_array($role, ['admin', 'editor', 'support']);
$can_view_artists = in_array($role, ['admin', 'editor']);
$can_view_finances = in_array($role, ['admin', 'analyst']);
$can_view_stats = in_array($role, ['admin', 'analyst', 'editor']);

if (!$can_view_finances) {
    header('Location: ' . rtrim(APP_URL, '/') . '/' . APP_URL_PANEL . '/collab/overview?error=noaccess');
    exit;
}

// ── Wallet ────────────────────────────────────
$wq = $db->prepare("SELECT * FROM _wallet WHERE id_users = ? LIMIT 1");
$wq->execute([$id_users]);
$wallet = $wq->fetch() ?: ['balance_aoa' => 0, 'balance_usd' => 0, 'total_earned' => 0, 'total_withdrawn' => 0];

// ── Filtro período ────────────────────────────
$filter_year = (int)($_GET['year'] ?? date('Y'));
$filter_month = (int)($_GET['month'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

// ── Royalties ─────────────────────────────────
$roy_where = ['r.id_users = ?'];
$roy_params = [$id_users];
if ($filter_year) {
    $roy_where[] = 'r.year_royalty = ?';
    $roy_params[] = $filter_year;
}
if ($filter_month) {
    $roy_where[] = 'r.month_royalty = ?';
    $roy_params[] = $filter_month;
}
$roy_sql = implode(' AND ', $roy_where);

$roy_stats_q = $db->prepare("
SELECT
COUNT(*) AS total,
SUM(CASE WHEN status_royalty='paid' THEN 1 ELSE 0 END) AS paid,
SUM(CASE WHEN status_royalty='pending' THEN 1 ELSE 0 END) AS pending,
SUM(CASE WHEN status_royalty='processing' THEN 1 ELSE 0 END) AS processing,
SUM(net_royalty) AS total_net_usd,
SUM(net_royalty_aoa) AS total_net_aoa
FROM _royalty r WHERE $roy_sql
");
$roy_stats_q->execute($roy_params);
$roy_stats = $roy_stats_q->fetch();

$roy_cnt_q = $db->prepare("SELECT COUNT(*) FROM _royalty r WHERE $roy_sql");
$roy_cnt_q->execute($roy_params);
$roy_total = (int)$roy_cnt_q->fetchColumn();
$roy_pages = max(1, ceil($roy_total / $per_page));
$roy_offset = ($page - 1) * $per_page;

$royalties_q = $db->prepare("
SELECT r.*, t.title_track, t.name_author, a.title_album
FROM _royalty r
JOIN _track t ON t.id_track = r.id_track
JOIN _album a ON a.id_album = t.id_album
WHERE $roy_sql
ORDER BY r.year_royalty DESC, r.month_royalty DESC, r.creat_royalty DESC
LIMIT $per_page OFFSET $roy_offset
");
$royalties_q->execute($roy_params);
$royalties = $royalties_q->fetchAll(PDO::FETCH_ASSOC);

// ── Transações recentes ────────────────────────
$trans_q = $db->prepare("
SELECT id_transaction, type_transaction, amount, currency,
balance_before, balance_after, description, creat_transaction
FROM _transaction
WHERE id_users = ?
ORDER BY creat_transaction DESC
LIMIT 10
");
$trans_q->execute([$id_users]);
$transactions = $trans_q->fetchAll(PDO::FETCH_ASSOC);

// ── Levantamentos recentes ─────────────────────
$with_q = $db->prepare("
SELECT w.*, ac.type_account, ac.full_name_account
FROM _withdrawal w
LEFT JOIN _account ac ON ac.id_account = w.id_account
WHERE w.id_users = ?
ORDER BY w.creat_withdrawal DESC
LIMIT 8
");
$with_q->execute([$id_users]);
$withdrawals = $with_q->fetchAll(PDO::FETCH_ASSOC);

// ── Anos disponíveis ──────────────────────────
$years_q = $db->prepare("SELECT DISTINCT year_royalty FROM _royalty WHERE id_users = ? ORDER BY year_royalty DESC");
$years_q->execute([$id_users]);
$available_years = $years_q->fetchAll(PDO::FETCH_COLUMN);
if (empty($available_years)) $available_years = [date('Y')];

// ── Helpers ───────────────────────────────────
$role_meta = [
    'admin' => ['label' => 'Administrador', 'color' => '#dc3545', 'bg' => 'rgba(220,53,69,.1)', 'icon' => 'bi-shield-fill'],
    'editor' => ['label' => 'Editor', 'color' => '#FF0089', 'bg' => 'rgba(255,0,137,.1)', 'icon' => 'bi-pencil-fill'],
    'analyst' => ['label' => 'Analista', 'color' => '#0d6efd', 'bg' => 'rgba(13,110,253,.1)', 'icon' =>
    'bi-bar-chart-fill'],
    'support' => ['label' => 'Suporte', 'color' => '#198754', 'bg' => 'rgba(25,135,84,.1)', 'icon' => 'bi-headset'],
];
$rm = $role_meta[$role] ?? $role_meta['support'];
$role_label = $rm['label'];

$royalty_status_meta = [
    'pending' => ['label' => 'Pendente', 'color' => '#856404', 'bg' => 'rgba(255,193,7,.12)'],
    'processing' => ['label' => 'A processar', 'color' => '#0d6efd', 'bg' => 'rgba(13,110,253,.1)'],
    'paid' => ['label' => 'Pago', 'color' => '#198754', 'bg' => 'rgba(25,135,84,.1)'],
    'cancelled' => ['label' => 'Cancelado', 'color' => '#dc3545', 'bg' => 'rgba(220,53,69,.1)'],
];
$withdrawal_status_meta = [
    'pending' => ['label' => 'Pendente', 'color' => '#856404', 'bg' => 'rgba(255,193,7,.12)'],
    'processing' => ['label' => 'A processar', 'color' => '#0d6efd', 'bg' => 'rgba(13,110,253,.1)'],
    'approved' => ['label' => 'Aprovado', 'color' => '#198754', 'bg' => 'rgba(25,135,84,.1)'],
    'rejected' => ['label' => 'Recusado', 'color' => '#dc3545', 'bg' => 'rgba(220,53,69,.1)'],
    'cancelled' => ['label' => 'Cancelado', 'color' => '#6c757d', 'bg' => 'rgba(108,117,125,.1)'],
];
$trans_type_meta = [
    'royalty_credit' => ['label' => 'Royalty', 'icon' => 'bi-arrow-down-circle', 'color' => '#198754'],
    'withdrawal' => ['label' => 'Levantamento', 'icon' => 'bi-arrow-up-circle', 'color' => '#dc3545'],
    'plan_payment' => ['label' => 'Pagamento plano', 'icon' => 'bi-credit-card', 'color' => '#0d6efd'],
    'adjustment' => ['label' => 'Ajuste', 'icon' => 'bi-sliders', 'color' => '#6c757d'],
    'fee' => ['label' => 'Taxa', 'icon' => 'bi-dash-circle', 'color' => '#dc3545'],
];
$months_pt = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

$logout_url = rtrim(APP_URL, '/') . '/' . APP_URL_PANEL . '/collab/logout';
$base_url = rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF2D66" />
    <title>Finanças — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="css/collab.css" />
    <style>
    /* ── Finances: Wallet hero ── */
    .wallet-hero {
        background: linear-gradient(135deg, #1a1a2e, #16213e);
        border-radius: 20px;
        padding: 28px;
        color: #fff;
        position: relative;
        overflow: hidden;
        margin-bottom: 24px;
    }

    .wallet-hero::after {
        content: '';
        position: absolute;
        right: -40px;
        top: -40px;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: rgba(255, 0, 137, .12);
    }

    .wallet-hero::before {
        content: '';
        position: absolute;
        right: 60px;
        bottom: -60px;
        width: 160px;
        height: 160px;
        border-radius: 50%;
        background: rgba(255, 77, 77, .08);
    }

    .wallet-balance {
        font-size: 2.2rem;
        font-weight: 900;
        letter-spacing: -1px;
        position: relative;
        z-index: 1;
    }

    .wallet-label {
        font-size: .72rem;
        color: rgba(255, 255, 255, .6);
        text-transform: uppercase;
        letter-spacing: .5px;
        position: relative;
        z-index: 1;
    }

    .wallet-secondary {
        font-size: 1.1rem;
        font-weight: 700;
        color: rgba(255, 255, 255, .85);
        position: relative;
        z-index: 1;
    }

    .wallet-stat {
        background: rgba(255, 255, 255, .07);
        border-radius: 12px;
        padding: 14px 16px;
        position: relative;
        z-index: 1;
        border: 1px solid rgba(255, 255, 255, .08);
    }

    .readonly-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(255, 255, 255, .12);
        color: rgba(255, 255, 255, .7);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: .7rem;
        font-weight: 600;
        border: 1px solid rgba(255, 255, 255, .15);
    }

    /* ── Finances: Table ── */
    .fin-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .82rem;
    }

    .fin-table th {
        font-size: .68rem;
        font-weight: 700;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 8px 10px;
        border-bottom: 2px solid var(--border);
        white-space: nowrap;
    }

    .fin-table td {
        padding: 10px;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
    }

    .fin-table tr:last-child td {
        border-bottom: none;
    }

    .fin-table tr:hover td {
        background: rgba(255, 0, 137, .02);
    }

    /* ── Finances: Rows ── */
    .trans-row,
    .with-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
    }

    .trans-row:last-child,
    .with-row:last-child {
        border-bottom: none;
    }

    .trans-icon {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    /* ── Finances: Pagination ── */
    .pag-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        font-size: .78rem;
        font-weight: 600;
        text-decoration: none;
        border: 1.5px solid var(--border);
        color: var(--muted);
        transition: all .15s;
    }

    .pag-btn.active,
    .pag-btn:hover {
        background: var(--wasom);
        color: #fff;
        border-color: var(--wasom);
    }
    </style>
</head>

<body>

    <!-- ═══ NAVBAR ═══ -->>
    <?php require_once __DIR__ . '/include/navbar-top.php'; ?>
    <!-- ═══ SIDEBAR OVERLAY (mobile) ═══ -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- ═══ SIDEBAR ═══ -->
    <?php require_once __DIR__ . '/include/sidebar.php'; ?>


    <!-- MAIN -->
    <main class="main-content">

        <!-- Cabeçalho -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h4 fw-bold mb-1"><i class="bi bi-currency-dollar me-2"
                        style="color:var(--wasom)"></i>Finanças</h1>
                <p class="text-muted small mb-0">Conta de <?php echo $owner_artist_name; ?></p>
            </div>
            <span class="readonly-badge"
                style="background:rgba(255,193,7,.1);color:#856404;border-color:rgba(255,193,7,.3)">
                <i class="bi bi-eye"></i> Só leitura
            </span>
        </div>

        <!-- Wallet hero -->
        <div class="wallet-hero">
            <div class="d-flex flex-wrap gap-3 align-items-start">
                <!-- Saldo principal -->
                <div style="flex:1;min-width:200px">
                    <div class="wallet-label mb-1">Saldo disponível</div>
                    <div class="wallet-balance"><?php echo number_format((float)$wallet['balance_aoa'], 2, ',', '.'); ?>
                        <span style="font-size:1.1rem;opacity:.7">Kz</span>
                    </div>
                    <div class="wallet-secondary mt-1">≈
                        $<?php echo number_format((float)$wallet['balance_usd'], 2, ',', '.'); ?> USD</div>
                </div>
                <!-- Stats secundárias -->
                <div class="d-flex gap-2 flex-wrap" style="position:relative;z-index:1">
                    <div class="wallet-stat text-center" style="min-width:110px">
                        <div style="font-size:.65rem;color:rgba(255,255,255,.55);margin-bottom:4px">Total ganho</div>
                        <div style="font-size:1rem;font-weight:800">
                            $<?php echo number_format((float)$wallet['total_earned'], 2); ?></div>
                    </div>
                    <div class="wallet-stat text-center" style="min-width:110px">
                        <div style="font-size:.65rem;color:rgba(255,255,255,.55);margin-bottom:4px">Total sacado</div>
                        <div style="font-size:1rem;font-weight:800">
                            <?php echo number_format((float)$wallet['total_withdrawn'], 2, ',', '.'); ?> Kz</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Royalties stats rápidas -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div style="background:var(--card);border-radius:14px;border:1.5px solid var(--border);padding:16px">
                    <div class="text-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.4px">
                        Royalties totais</div>
                    <div style="font-size:1.4rem;font-weight:800;margin-top:4px">
                        <?php echo (int)($roy_stats['total'] ?? 0); ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div style="background:var(--card);border-radius:14px;border:1.5px solid var(--border);padding:16px">
                    <div class="text-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.4px">Pagos
                    </div>
                    <div style="font-size:1.4rem;font-weight:800;color:#198754;margin-top:4px">
                        <?php echo (int)($roy_stats['paid'] ?? 0); ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div style="background:var(--card);border-radius:14px;border:1.5px solid var(--border);padding:16px">
                    <div class="text-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.4px">
                        Pendentes</div>
                    <div style="font-size:1.4rem;font-weight:800;color:#856404;margin-top:4px">
                        <?php echo (int)($roy_stats['pending'] ?? 0); ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div style="background:var(--card);border-radius:14px;border:1.5px solid var(--border);padding:16px">
                    <div class="text-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.4px">
                        Líquido USD</div>
                    <div style="font-size:1.4rem;font-weight:800;color:var(--wasom);margin-top:4px">
                        $<?php echo number_format((float)($roy_stats['total_net_usd'] ?? 0), 2); ?></div>
                </div>
            </div>
        </div>

        <div class="row g-4">

            <!-- Coluna esquerda: royalties -->
            <div class="col-lg-8">

                <!-- Filtros -->
                <div class="filter-bar">
                    <i class="bi bi-funnel" style="color:var(--wasom)"></i>
                    <span class="text-muted small fw-semibold">Período:</span>
                    <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                        <select name="year" class="form-select form-select-sm"
                            style="width:auto;border-color:var(--border)" onchange="this.form.submit()">
                            <?php foreach ($available_years as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $filter_year ? 'selected' : ''; ?>>
                                <?php echo $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="month" class="form-select form-select-sm"
                            style="width:auto;border-color:var(--border)" onchange="this.form.submit()">
                            <option value="0" <?php echo $filter_month === 0 ? 'selected' : ''; ?>>Todos os meses
                            </option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $m === $filter_month ? 'selected' : ''; ?>>
                                <?php echo $months_pt[$m]; ?></option>
                            <?php endfor; ?>
                        </select>
                        <?php if ($filter_month > 0): ?>
                        <a href="?year=<?php echo $filter_year; ?>" class="btn btn-sm btn-outline-danger"><i
                                class="bi bi-x"></i></a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Tabela royalties -->
                <div class="dash-card">
                    <div class="card-title"><i class="bi bi-music-note-beamed"></i>Royalties por faixa</div>
                    <?php if (empty($royalties)): ?>
                    <div class="empty-state">
                        <div class="icon">💰</div>
                        <div class="small">Nenhum royalty neste período</div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="fin-table">
                            <thead>
                                <tr>
                                    <th>Faixa / Álbum</th>
                                    <th>Período</th>
                                    <th>Receita bruta</th>
                                    <th>Líquido USD</th>
                                    <th>Líquido AOA</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($royalties as $r):
                                        $sm = $royalty_status_meta[$r['status_royalty']] ?? $royalty_status_meta['pending'];
                                    ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width:160px">
                                            <?php echo htmlspecialchars($r['title_track']); ?></div>
                                        <div class="text-muted" style="font-size:.7rem">
                                            <?php echo htmlspecialchars($r['title_album']); ?></div>
                                    </td>
                                    <td class="text-muted" style="white-space:nowrap">
                                        <?php echo $months_pt[(int)$r['month_royalty']]; ?>/<?php echo $r['year_royalty']; ?>
                                    </td>
                                    <td class="fw-semibold" style="white-space:nowrap">
                                        $<?php echo number_format((float)$r['gross_revenue'], 4); ?></td>
                                    <td class="fw-semibold" style="white-space:nowrap;color:#198754">
                                        $<?php echo number_format((float)$r['net_royalty'], 4); ?></td>
                                    <td class="fw-semibold" style="white-space:nowrap">
                                        <?php echo $r['net_royalty_aoa'] ? number_format((float)$r['net_royalty_aoa'], 2, ',', '.') . ' Kz' : '—'; ?>
                                    </td>
                                    <td><span class="chip"
                                            style="background:<?php echo $sm['bg']; ?>;color:<?php echo $sm['color']; ?>"><?php echo $sm['label']; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Paginação -->
                    <?php if ($roy_pages > 1): ?>
                    <div class="d-flex gap-1 flex-wrap mt-3">
                        <?php for ($p = 1; $p <= $roy_pages; $p++): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>"
                            class="pag-btn <?php echo $p === $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Coluna direita -->
            <div class="col-lg-4 d-flex flex-column gap-4">

                <!-- Transações recentes -->
                <div class="dash-card">
                    <div class="card-title"><i class="bi bi-arrow-left-right"></i>Transações recentes</div>
                    <?php if (empty($transactions)): ?>
                    <div class="empty-state">
                        <div class="icon">📋</div>
                        <div class="small">Sem transações</div>
                    </div>
                    <?php else: foreach ($transactions as $tr):
                            $tm = $trans_type_meta[$tr['type_transaction']] ?? ['label' => $tr['type_transaction'], 'icon' => 'bi-circle', 'color' => '#6c757d'];
                            $is_credit = in_array($tr['type_transaction'], ['royalty_credit', 'refund']);
                        ?>
                    <div class="trans-row">
                        <div class="trans-icon"
                            style="background:<?php echo $is_credit ? 'rgba(25,135,84,.1)' : 'rgba(220,53,69,.1)'; ?>">
                            <i class="bi <?php echo $tm['icon']; ?>" style="color:<?php echo $tm['color']; ?>"></i>
                        </div>
                        <div style="flex:1;min-width:0">
                            <div class="fw-semibold text-truncate" style="font-size:.8rem"><?php echo $tm['label']; ?>
                            </div>
                            <div class="text-muted" style="font-size:.68rem">
                                <?php echo date('d/m/Y H:i', strtotime($tr['creat_transaction'])); ?></div>
                        </div>
                        <div style="text-align:right;flex-shrink:0">
                            <div class="fw-bold"
                                style="font-size:.85rem;color:<?php echo $is_credit ? '#198754' : '#dc3545'; ?>">
                                <?php echo $is_credit ? '+' : '-'; ?><?php echo number_format((float)$tr['amount'], 2); ?>
                                <?php echo $tr['currency']; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach;
                    endif; ?>
                </div>

                <!-- Levantamentos recentes -->
                <div class="dash-card">
                    <div class="card-title"><i class="bi bi-cash-stack"></i>Levantamentos</div>
                    <?php if (empty($withdrawals)): ?>
                    <div class="empty-state">
                        <div class="icon">🏦</div>
                        <div class="small">Sem levantamentos</div>
                    </div>
                    <?php else: foreach ($withdrawals as $w):
                            $ws = $withdrawal_status_meta[$w['status_withdrawal']] ?? $withdrawal_status_meta['pending'];
                        ?>
                    <div class="with-row">
                        <div
                            style="width:36px;height:36px;border-radius:10px;background:rgba(255,0,137,.07);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="bi bi-bank" style="color:var(--wasom)"></i>
                        </div>
                        <div style="flex:1;min-width:0">
                            <div class="fw-semibold text-truncate" style="font-size:.8rem">
                                <?php echo htmlspecialchars($w['full_name_account'] ?? '—'); ?>
                            </div>
                            <div class="text-muted" style="font-size:.68rem">
                                <?php echo $w['type_account']; ?> ·
                                <?php echo date('d/m/Y', strtotime($w['creat_withdrawal'])); ?>
                            </div>
                        </div>
                        <div style="text-align:right;flex-shrink:0">
                            <div class="fw-bold" style="font-size:.82rem">
                                <?php echo number_format((float)$w['amount_net'], 2, ',', '.'); ?>
                                <?php echo $w['currency']; ?>
                            </div>
                            <span class="chip"
                                style="background:<?php echo $ws['bg']; ?>;color:<?php echo $ws['color']; ?>;font-size:.6rem">
                                <?php echo $ws['label']; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach;
                    endif; ?>
                </div>

            </div>
        </div>

    </main>

    <!-- Bottom nav -->
    <?php require_once __DIR__ . '/include/navbar-bottom.php'; ?>

    <?php require_once __DIR__ . '/include/modallogoutmyprofile.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
    function closeSidebar() {
        document.getElementById('collabSidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
    }
    document.getElementById('btn-sidebar-toggle')?.addEventListener('click', () => {
        const sb = document.getElementById('collabSidebar');
        const ov = document.getElementById('sidebarOverlay');
        sb.classList.toggle('open');
        ov.classList.toggle('show', sb.classList.contains('open'));
    });
    const html = document.documentElement;
    const saved = localStorage.getItem('wu_theme') || 'light';
    html.setAttribute('data-theme', saved);
    document.getElementById('themeIcon').className = saved === 'dark' ? 'bi bi-moon' : 'bi bi-sun';
    document.getElementById('themeToggle').addEventListener('click', () => {
        const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        localStorage.setItem('wu_theme', next);
        document.getElementById('themeIcon').className = next === 'dark' ? 'bi bi-moon' : 'bi bi-sun';
    });
    setInterval(() => fetch('<?php echo $base_url; ?>/dashboard/collab/ping', {
        method: 'POST'
    }).catch(() => {}), 120000);
    </script>
</body>

</html>