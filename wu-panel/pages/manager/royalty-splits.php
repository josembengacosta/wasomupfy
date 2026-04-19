<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY for Business — Pagar Royalties
// Arquivo: wu-panel/pages/manager/royalty-splits.php
// Rota:    wu-panel/manager/royalty-splits
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
require_once __DIR__ . '/include/payment-guard.php';
requirePermission($admin_id, 'finances.view');
paymentPanelRequireAccess();

// ── Taxa de câmbio actual ─────────────────────────────────────
$usd_rate = (float)($db->query("SELECT usd_to_aoa_rate FROM _platform LIMIT 1")->fetchColumn() ?: 900);

// ── Filtros + paginação ───────────────────────────────────────
$per_page = 15;
$page     = max(1, (int)($_GET['page']   ?? 1));
$f_search = trim($_GET['search'] ?? '');
$f_status = trim($_GET['status'] ?? '');
$f_year   = trim($_GET['year']   ?? '');
$f_month  = trim($_GET['month']  ?? '');

$where  = [];
$params = [];

if ($f_search !== '') {
    $like     = '%' . $f_search . '%';
    $where[]  = "(u.first_name LIKE ? OR u.second_name LIKE ? OR u.email_user LIKE ?
                  OR t.title_track LIKE ? OR al.title_album LIKE ?
                  OR COALESCE(ar.stage_name,u.name_artist_band,u.first_name) LIKE ?)";
    array_push($params, $like, $like, $like, $like, $like, $like);
}
if ($f_status !== '') {
    $where[] = 'r.status_royalty=?';
    $params[] = $f_status;
}
if ($f_year   !== '') {
    $where[] = 'r.year_royalty=?';
    $params[] = (int)$f_year;
}
if ($f_month  !== '') {
    $where[] = 'r.month_royalty=?';
    $params[] = (int)$f_month;
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$base_join = "
    FROM _royalty r
    JOIN _users u ON u.id_users = r.id_users
    LEFT JOIN _track t ON t.id_track = r.id_track
    LEFT JOIN _album al ON al.id_album = t.id_album
    LEFT JOIN _artist ar ON ar.id_artist = al.id_artist
    LEFT JOIN _account a ON a.id_users = r.id_users AND a.is_default = 1
";

$cnt = $db->prepare("SELECT COUNT(*) $base_join $sql_where");
$cnt->execute($params);
$total       = (int)$cnt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

$stmt = $db->prepare("
    SELECT r.id_royalty, r.id_users, r.id_track, r.year_royalty, r.month_royalty,
           r.gross_revenue, r.platform_fee, r.net_royalty, r.net_royalty_aoa,
           r.currency, r.exchange_rate, r.status_royalty, r.report_file,
           r.paid_at, r.creat_royalty,
           CONCAT(u.first_name,' ',COALESCE(u.second_name,'')) AS user_name,
           u.email_user, u.photo_user, u.name_artist_band,
           t.title_track, t.isrc,
           al.title_album, al.type_album, al.img_cover,
           COALESCE(ar.stage_name, u.name_artist_band, u.first_name) AS artist_name,
           a.type_account, a.full_name_account, a.iban, a.express_number,
           a.status_account, a.id_account,
           CONCAT(e.first_name,' ',COALESCE(e.second_name,'')) AS paid_by_name
    $base_join
    LEFT JOIN _employees e ON e.id_employees = r.paid_by
    $sql_where
    ORDER BY
        CASE r.status_royalty WHEN 'pending' THEN 0 WHEN 'processing' THEN 1 ELSE 2 END,
        r.creat_royalty ASC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$royalties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Stats ─────────────────────────────────────────────────────
$stats = $db->query("
    SELECT
        SUM(status_royalty='pending')    AS pending,
        SUM(status_royalty='processing') AS processing,
        SUM(status_royalty='paid')       AS paid,
        SUM(status_royalty='cancelled')  AS cancelled,
        COALESCE(SUM(net_royalty_aoa),0) AS total_amount,
        COALESCE(SUM(CASE WHEN status_royalty='paid' THEN net_royalty_aoa ELSE 0 END),0) AS total_paid,
        COALESCE(SUM(CASE WHEN status_royalty='pending' THEN net_royalty_aoa ELSE 0 END),0) AS total_pending_amount
    FROM _royalty
")->fetch(PDO::FETCH_ASSOC);

// ── Lista de utilizadores para o select (modal novo depósito) ─
$users_list = $db->query("
    SELECT u.id_users, u.first_name, u.second_name, u.email_user, u.name_artist_band
    FROM _users u
    WHERE u.status_user='active'
    ORDER BY u.first_name, u.second_name
")->fetchAll(PDO::FETCH_ASSOC);

// ── Anos disponíveis ──────────────────────────────────────────
$years_avail = $db->query("SELECT DISTINCT year_royalty FROM _royalty ORDER BY year_royalty DESC")->fetchAll(PDO::FETCH_COLUMN);

$payment_sidebar_active = 'royalty-splits';
require_once __DIR__ . '/include/payment-sidebar.php';
$csrf = $_SESSION['admin_csrf_token'];

// ── Helpers ───────────────────────────────────────────────────
function r_fmt(float $v): string
{
    return 'Kz ' . number_format($v, 2, ',', '.');
}
function r_status(string $s): string
{
    return match ($s) {
        'pending'    => '<span class="biz-s-pending">Pendente</span>',
        'processing' => '<span class="biz-s-processing">A processar</span>',
        'paid'       => '<span class="biz-s-paid">Pago</span>',
        'cancelled'  => '<span class="biz-s-cancelled">Cancelado</span>',
        default      => '<span class="biz-s-pending">' . htmlspecialchars(ucfirst($s)) . '</span>',
    };
}
function r_avatar(string $name, ?string $photo, int $s = 32): string
{
    $p   = explode(' ', trim($name), 2);
    $ini = mb_strtoupper(mb_substr($p[0] ?? '', 0, 1, 'UTF-8'), 'UTF-8')
        . mb_strtoupper(mb_substr($p[1] ?? '', 0, 1, 'UTF-8'), 'UTF-8');
    $cl  = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308', '#3b82f6', '#ef4444'];
    $c   = $cl[abs(crc32($name)) % count($cl)];
    $fs  = round($s * 0.3);
    if ($photo) {
        return '<img src="' . APP_URL . '/assets/comprovantes/uploads/users/' . htmlspecialchars($photo) . '"
                     width="' . $s . '" height="' . $s . '"
                     style="border-radius:50%;object-fit:cover;border:2px solid rgba(255,0,137,.2);flex-shrink:0"
                     onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'" alt="">
                <div style="width:' . $s . 'px;height:' . $s . 'px;border-radius:50%;background:' . $c . ';
                            display:none;align-items:center;justify-content:center;
                            font-weight:700;font-size:' . $fs . 'px;color:#fff;flex-shrink:0">' . $ini . '</div>';
    }
    return '<div style="width:' . $s . 'px;height:' . $s . 'px;border-radius:50%;background:' . $c . ';
                         display:flex;align-items:center;justify-content:center;
                         font-weight:700;font-size:' . $fs . 'px;color:#fff;flex-shrink:0">' . $ini . '</div>';
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
    <title>Pagar Royalties — Wasom Upfy for Business</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap"
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

        .pag-link {
            border-radius: 8px !important;
            margin: 0 2px;
            font-size: .8rem
        }

        /* ── Cover miniatura ── */
        .r-cover {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
            border: 2px solid rgba(255, 0, 137, .15);
        }

        .r-cover-ph {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: rgba(255, 0, 137, .06);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* ── Passo do formulário ── */
        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        /* ── Stepper visual ── */
        .stepper {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 24px;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .75rem;
            font-weight: 800;
            flex-shrink: 0;
            background: #e8eaf2;
            color: #9ca3af;
            transition: all .2s;
        }

        .step-circle.done {
            background: #22c55e;
            color: #fff;
        }

        .step-circle.active {
            background: #FF0089;
            color: #fff;
        }

        .step-label {
            font-size: .73rem;
            font-weight: 600;
            color: #9ca3af;
            white-space: nowrap;
        }

        .step-label.active {
            color: #1a1a2e;
        }

        .step-line {
            flex: 1;
            height: 2px;
            background: #e8eaf2;
            margin: 0 8px;
        }

        .step-line.done {
            background: #22c55e;
        }

        /* ── Report preview ── */
        .report-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 600;
            background: rgba(59, 130, 246, .1);
            color: #2563eb;
            text-decoration: none;
        }

        .report-pill:hover {
            background: rgba(59, 130, 246, .2);
            color: #1d4ed8;
        }

        /* ── Input calculado ── */
        .calc-input {
            background: rgba(255, 0, 137, .04) !important;
            border-color: rgba(255, 0, 137, .25) !important;
            color: #FF0089 !important;
            font-weight: 700 !important;
        }
    </style>
</head>

<body>

    <div class="biz-content">
        <!-- Topbar -->
        <div class="biz-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="biz-hamburger" onclick="openSidebar()"><i class="bi bi-list fs-5"></i></button>
                <div>
                    <div class="biz-topbar-title">Pagamento de Royalties</div>
                    <div class="biz-topbar-sub">
                        <a href="<?php echo paymentPanelBaseUrl(); ?>/gestion"
                            style="color:#9ca3af;text-decoration:none">Dashboard</a>
                        → Royalties
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">Taxa USD/AOA:
                    <strong><?php echo number_format($usd_rate, 0); ?></strong></span>
                <?php if ((int)$stats['pending'] > 0): ?>
                    <span class="biz-s-pending"><?php echo (int)$stats['pending']; ?> pendente(s)</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="biz-inner">

            <!-- Stat cards -->
            <div class="row g-3 mb-4">
                <?php
                $rcards = [
                    ['val' => (int)$stats['pending'],    'lbl' => 'Pendentes',         'color' => '#f97316', 'icon' => 'bi-hourglass-split',   'sub' => r_fmt((float)$stats['total_pending_amount'])],
                    ['val' => (int)$stats['processing'], 'lbl' => 'A Processar',       'color' => '#3b82f6', 'icon' => 'bi-arrow-repeat',      'sub' => null],
                    ['val' => (int)$stats['paid'],        'lbl' => 'Pagos',             'color' => '#22c55e', 'icon' => 'bi-check-circle-fill', 'sub' => r_fmt((float)$stats['total_paid'])],
                    ['val' => (int)$stats['cancelled'],   'lbl' => 'Cancelados',        'color' => '#ef4444', 'icon' => 'bi-x-circle',          'sub' => null],
                    ['val' => r_fmt((float)$stats['total_amount']), 'lbl' => 'Total Geral', 'color' => '#FF0089', 'icon' => 'bi-cash-coin',    'sub' => null],
                ];
                foreach ($rcards as $c):
                ?>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="biz-stat">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="biz-stat-icon" style="background:<?php echo $c['color']; ?>18">
                                    <i class="bi <?php echo $c['icon']; ?>" style="color:<?php echo $c['color']; ?>"></i>
                                </div>
                            </div>
                            <div class="biz-stat-val"><?php echo $c['val']; ?></div>
                            <div class="biz-stat-lbl"><?php echo $c['lbl']; ?></div>
                            <?php if ($c['sub']): ?>
                                <div class="biz-stat-sub" style="color:<?php echo $c['color']; ?>"><?php echo $c['sub']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (hasPermission($admin_id, 'finances.edit')): ?>
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-sm text-white align-self-end flex-shrink-0"
                        style="background:linear-gradient(135deg,#22c55e,#16a34a);border:none;border-radius:10px;padding:8px 16px;font-weight:600"
                        onclick="openNewDeposit()">
                        <i class="bi bi-plus-lg me-1"></i> Novo Depósito de Royalty
                    </button>
                </div>
            <?php endif; ?>
            <!-- Aviso se há pendentes -->
            <?php if ((int)$stats['pending'] > 0): ?>
                <div
                    style="background:#fff8e1;border:1px solid #ffe082;border-radius:14px;padding:12px 18px;margin-bottom:18px;display:flex;align-items:center;gap:12px;font-size:.82rem">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                    <div>Tens <strong><?php echo (int)$stats['pending']; ?> royaltie(s) por pagar</strong>,
                        num total de <strong><?php echo r_fmt((float)$stats['total_pending_amount']); ?></strong> em espera.
                        Processa os pagamentos para creditar as wallets dos utilizadores.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Filtros + botão novo depósito -->
            <div class="filter-card">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <form method="GET" class="d-flex align-items-end gap-2 flex-wrap flex-grow-1">
                        <div style="flex:2;min-width:160px">
                            <label class="form-label">Pesquisar</label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                value="<?php echo htmlspecialchars($f_search); ?>"
                                placeholder="Nome, e-mail, faixa, artista">
                        </div>
                        <div style="flex:1;min-width:130px">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <?php foreach (['pending' => 'Pendente', 'processing' => 'A processar', 'paid' => 'Pago', 'cancelled' => 'Cancelado'] as $v => $l): ?>
                                    <option value="<?php echo $v; ?>" <?php echo $f_status === $v ? 'selected' : ''; ?>>
                                        <?php echo $l; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex:1;min-width:100px">
                            <label class="form-label">Ano</label>
                            <select name="year" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <?php foreach ($years_avail as $y): ?>
                                    <option value="<?php echo $y; ?>"
                                        <?php echo $f_year == (string)$y ? 'selected' : ''; ?>>
                                        <?php echo $y; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex:1;min-width:100px">
                            <label class="form-label">Mês</label>
                            <select name="month" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>"
                                        <?php echo $f_month == (string)$m ? 'selected' : ''; ?>>
                                        <?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?> —
                                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="d-flex gap-1 align-self-end">
                            <button type="submit" class="btn btn-md text-white" style="background:#FF0089">
                                <i class="bi bi-search"></i>
                            </button>
                            <a href="<?php echo paymentPanelBaseUrl(); ?>/royalty-splits"
                                class="btn btn-md btn-outline-secondary">
                                <i class="bi bi-x"></i>
                            </a>
                        </div>
                    </form>

                </div>
            </div>

            <!-- Tabela principal -->
            <div class="biz-card">
                <div class="biz-card-header">
                    <span class="biz-card-title"><i class="bi bi-cash-coin me-2"></i>Royalties</span>
                    <span style="font-size:.75rem;color:#9ca3af">
                        <?php echo number_format($total); ?> registo(s) · Pág.
                        <?php echo $page; ?>/<?php echo $total_pages; ?>
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover biz-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Utilizador / Artista</th>
                                <th>Faixa / Álbum</th>
                                <th>Período</th>
                                <th>Receita Bruta</th>
                                <th>Taxa Wasom Upfy (10%)</th>
                                <th>Royalty Líq.</th>
                                <th>Conta</th>
                                <th>Estado</th>
                                <th>Relatório</th>
                                <th style="text-align:center;width:90px">Acções</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($royalties)): ?>
                                <tr>
                                    <td colspan="11" class="text-center py-5 text-muted">
                                        <i class="bi bi-music-note-beamed"
                                            style="font-size:2rem;display:block;margin-bottom:10px;opacity:.3"></i>
                                        Nenhum royalty encontrado para os filtros aplicados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($royalties as $r):
                                    $is_pending = in_array($r['status_royalty'], ['pending', 'processing']);
                                    $period     = str_pad((int)$r['month_royalty'], 2, '0', STR_PAD_LEFT) . '/' . $r['year_royalty'];
                                    $cover_url  = $r['img_cover']
                                        ? APP_URL . '/assets/comprovantes/uploads/covers/' . $r['img_cover']
                                        : null;
                                ?>
                                    <tr class="<?php echo $r['status_royalty'] === 'pending' ? 'highlight-pending' : ''; ?>">
                                        <!-- ID -->
                                        <td>
                                            <span style="font-family:monospace;font-size:.72rem;opacity:.5">
                                                #<?php echo (int)$r['id_royalty']; ?>
                                            </span>
                                        </td>
                                        <!-- Utilizador -->
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php echo r_avatar($r['user_name'], $r['photo_user'], 30); ?>
                                                <div>
                                                    <div style="font-weight:700;font-size:.8rem">
                                                        <?php echo htmlspecialchars($r['user_name']); ?></div>
                                                    <div style="font-size:.7rem;color:#9ca3af">
                                                        <?php echo htmlspecialchars($r['artist_name'] ?? '—'); ?></div>
                                                    <div style="font-size:.68rem;color:#c4c4cf">
                                                        <?php echo htmlspecialchars($r['email_user']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- Faixa -->
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($cover_url): ?>
                                                    <img src="<?php echo $cover_url; ?>" class="r-cover" alt=""
                                                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                                    <div class="r-cover-ph" style="display:none">
                                                        <i class="bi bi-music-note" style="color:#FF0089;font-size:.85rem"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="r-cover-ph">
                                                        <i class="bi bi-music-note" style="color:#FF0089;font-size:.85rem"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div style="font-size:.8rem;font-weight:600">
                                                        <?php echo htmlspecialchars($r['title_track'] ?? '—'); ?>
                                                    </div>
                                                    <div style="font-size:.7rem;color:#9ca3af">
                                                        <?php echo htmlspecialchars($r['title_album'] ?? '—'); ?>
                                                        <?php echo $r['type_album'] ? ' · ' . $r['type_album'] : ''; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- Período -->
                                        <td style="font-size:.8rem;font-weight:600;white-space:nowrap"><?php echo $period; ?>
                                        </td>
                                        <!-- Receita Bruta -->
                                        <td style="font-size:.79rem;white-space:nowrap">
                                            $ <?php echo number_format((float)$r['gross_revenue'], 4, ',', '.'); ?>
                                        </td>
                                        <!-- Taxa -->
                                        <td style="font-size:.79rem;white-space:nowrap;color:#ef4444">
                                            $ <?php echo number_format((float)$r['platform_fee'], 4, ',', '.'); ?>
                                        </td>
                                        <!-- Royalty líquido -->
                                        <td>
                                            <div style="font-size:.85rem;font-weight:800;color:#FF0089;white-space:nowrap">
                                                <?php echo r_fmt((float)$r['net_royalty_aoa']); ?>
                                            </div>
                                            <div style="font-size:.69rem;color:#9ca3af">
                                                $ <?php echo number_format((float)$r['net_royalty'], 4, ',', '.'); ?>
                                            </div>
                                        </td>
                                        <!-- Conta -->
                                        <td>
                                            <?php if ($r['full_name_account']): ?>
                                                <div style="font-size:.78rem;font-weight:600">
                                                    <?php echo htmlspecialchars($r['full_name_account']); ?></div>
                                                <div style="font-size:.7rem;color:#9ca3af">
                                                    <?php echo htmlspecialchars($r['type_account'] ?? ''); ?>
                                                    <?php if ($r['iban'])           echo ' · ···' . substr($r['iban'], -6); ?>
                                                    <?php if ($r['express_number']) echo ' · ' . $r['express_number']; ?>
                                                </div>
                                                <div style="font-size:.67rem">
                                                    <?php if ($r['status_account'] === 'verified'): ?>
                                                        <span style="color:#22c55e"><i class="bi bi-shield-check"></i> Verificada</span>
                                                    <?php else: ?>
                                                        <span style="color:#f97316"><i class="bi bi-shield-exclamation"></i> Por
                                                            verificar</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="font-size:.75rem;color:#ef4444"><i
                                                        class="bi bi-exclamation-triangle me-1"></i>Sem conta</span>
                                            <?php endif; ?>
                                        </td>
                                        <!-- Estado -->
                                        <td><?php echo r_status($r['status_royalty']); ?></td>
                                        <!-- Relatório -->
                                        <td>
                                            <?php if ($r['report_file']): ?>
                                                <a href="<?php echo APP_URL . '/' . $r['report_file']; ?>" target="_blank"
                                                    class="report-pill">
                                                    <i class="bi bi-file-earmark-check"></i> Ver
                                                </a>
                                            <?php else: ?>
                                                <span style="font-size:.72rem;color:#c4c4cf">Nenhum</span>
                                            <?php endif; ?>
                                        </td>
                                        <!-- Acções -->
                                        <td>
                                            <div class="d-flex gap-1 justify-content-center">
                                                <!-- Ver detalhes -->
                                                <button class="btn btn-sm btn-outline-secondary" title="Ver detalhes"
                                                    onclick="viewRoyalty(<?php echo (int)$r['id_royalty']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <?php if ($is_pending && hasPermission($admin_id, 'finances.edit')): ?>
                                                    <!-- Pagar -->
                                                    <button class="btn btn-sm btn-outline-success" title="Processar pagamento"
                                                        onclick="payRoyalty(<?php echo (int)$r['id_royalty']; ?>)">
                                                        <i class="bi bi-check-lg"></i>
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
                                <?php
                                $qp = $_GET;
                                unset($qp['page']);
                                $qstr = $qp ? '&' . http_build_query($qp) : '';
                                ?>
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link pag-link" href="?page=<?php echo $page - 1; ?><?php echo $qstr; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($pi = max(1, $page - 2); $pi <= min($total_pages, $page + 2); $pi++): ?>
                                    <li class="page-item <?php echo $pi === $page ? 'active' : ''; ?>">
                                        <a class="page-link pag-link" href="?page=<?php echo $pi; ?><?php echo $qstr; ?>">
                                            <?php echo $pi; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link pag-link" href="?page=<?php echo $page + 1; ?><?php echo $qstr; ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- /biz-inner -->
    </div><!-- /biz-content -->

    <!-- ════════════════════════════════════════════════════════════
     MODAL — Ver Detalhes do Royalty
════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="royaltyViewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:#1a1a2e">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="bi bi-cash-coin me-2"></i>Detalhe do Royalty
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="royaltyViewBody">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>
                <div class="modal-footer border-0" id="royaltyViewFooter">
                    <button class="btn btn-sm btn-outline-secondary ms-auto" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════
     MODAL — Pagar Royalty (royalty existente → pendente)
════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="royaltyPayModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-md">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:linear-gradient(135deg,#22c55e,#16a34a)">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="bi bi-check-circle me-2"></i>Pagar Royalty
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="pay_royalty_id">

                    <!-- Info resumida (preenchida via JS) -->
                    <div id="pay_royalty_info" class="account-info-box mb-4">
                        <div class="text-center text-muted py-3">
                            <div class="spinner-border spinner-border-sm"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">
                            Relatório / Comprovativo <span class="text-muted fw-normal">(opcional)</span>
                        </label>
                        <input type="file" class="form-control form-control-sm" id="pay_report_file"
                            accept=".pdf,image/*">
                        <div class="form-text">PDF ou imagem do relatório de streaming. Ficará guardado no royalty e
                            visível ao utilizador.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Notas internas <span
                                class="text-muted fw-normal">(opcional)</span></label>
                        <textarea class="form-control form-control-sm" id="pay_notes" rows="2"
                            placeholder="Ex: Transferido via IBAN em 15/07/2025, conf. bancário nº 123456"></textarea>
                    </div>
                    <div class="alert alert-info" style="font-size:.78rem">
                        <i class="bi bi-info-circle me-1"></i>
                        Ao confirmar: o valor será <strong>creditado na wallet</strong> do utilizador,
                        uma <strong>transacção</strong> será registada e uma
                        <strong>notificação</strong> será enviada ao utilizador.
                    </div>
                    <div class="alert alert-danger d-none" id="pay_royalty_error" style="font-size:.78rem"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm text-white" style="background:#22c55e;border:none"
                        id="btn_confirm_pay_royalty">
                        <span class="normal-lbl"><i class="bi bi-check-lg me-1"></i>Confirmar Pagamento</span>
                        <span class="loading-lbl d-none">
                            <span class="spinner-border spinner-border-sm me-1"></span>A processar…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════
     MODAL — Novo Depósito de Royalty (wizard 3 passos)
════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="newDepositModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:linear-gradient(135deg,#FF0089,#f97316)">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>Novo Depósito de Royalty
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">

                    <!-- Stepper -->
                    <div class="stepper mb-4" id="wizard-stepper">
                        <div class="step-item">
                            <div class="step-circle active" id="sc1">1</div>
                            <div class="step-label active" id="sl1">Seleccionar</div>
                        </div>
                        <div class="step-line" id="sline1"></div>
                        <div class="step-item">
                            <div class="step-circle" id="sc2">2</div>
                            <div class="step-label" id="sl2">Valores</div>
                        </div>
                        <div class="step-line" id="sline2"></div>
                        <div class="step-item">
                            <div class="step-circle" id="sc3">3</div>
                            <div class="step-label" id="sl3">Confirmar</div>
                        </div>
                    </div>

                    <!-- PASSO 1 — Utilizador + Faixa + Período -->
                    <div class="form-step active" id="step1">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold small">
                                    Utilizador <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="nd_user" required>
                                    <option value="">Selecciona um utilizador...</option>
                                    <?php foreach ($users_list as $u): ?>
                                        <option value="<?php echo (int)$u['id_users']; ?>"
                                            data-name="<?php echo htmlspecialchars(trim($u['first_name'] . ' ' . ($u['second_name'] ?? ''))); ?>"
                                            data-email="<?php echo htmlspecialchars($u['email_user']); ?>"
                                            data-artist="<?php echo htmlspecialchars($u['name_artist_band'] ?? ''); ?>">
                                            #<?php echo (int)$u['id_users']; ?> —
                                            <?php echo htmlspecialchars(trim($u['first_name'] . ' ' . ($u['second_name'] ?? ''))); ?>
                                            (<?php echo htmlspecialchars($u['email_user']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Info da conta (carregado via AJAX) -->
                            <div class="col-12" id="nd_account_wrap" style="display:none">
                                <label class="form-label fw-semibold small">Conta Bancária Principal</label>
                                <div class="account-info-box" id="nd_account_box">
                                    <div class="text-center text-muted py-2">
                                        <div class="spinner-border spinner-border-sm me-2"></div>A carregar conta...
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">
                                    Álbum <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="nd_album" disabled required>
                                    <option value="">Primeiro selecciona o utilizador...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">
                                    Faixa <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="nd_track" disabled required>
                                    <option value="">Primeiro selecciona o álbum...</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">
                                    Ano de Referência <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="nd_year" required>
                                    <?php for ($y = date('Y'); $y >= 2022; $y--): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $y == date('Y') ? 'selected' : ''; ?>>
                                            <?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">
                                    Mês de Referência <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="nd_month" required>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?php echo $m; ?>"
                                            <?php echo $m == (int)date('n') ? 'selected' : ''; ?>>
                                            <?php echo str_pad($m, 2, '0', STR_PAD_LEFT) . ' — ' . date('F', mktime(0, 0, 0, $m, 1)); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="alert alert-danger d-none mt-3" id="step1_error" style="font-size:.78rem"></div>
                    </div>

                    <!-- PASSO 2 — Valores financeiros -->
                    <div class="form-step" id="step2">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="account-info-box" id="step2_track_info">
                                    <!-- Preenchido via JS -->
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">
                                    Receita Bruta (USD) <span class="text-danger">*</span>
                                    <span class="text-muted fw-normal ms-1">— paga pelas plataformas</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="nd_gross" min="0" step="0.0001"
                                        placeholder="0.0000" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">
                                    Taxa Plataforma (%) <span class="text-danger">*</span>
                                    <span class="text-muted fw-normal ms-1">— Wasom Upfy retém</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="nd_fee_pct" min="0" max="100"
                                        step="0.01" value="10" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Taxa em USD (calculada)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control calc-input" id="nd_fee_usd" readonly
                                        tabindex="-1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Royalty Líq. USD (calculado)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control calc-input" id="nd_net_usd" readonly
                                        tabindex="-1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">
                                    Taxa de Câmbio (USD → AOA) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">1$=</span>
                                    <input type="number" class="form-control" id="nd_rate" min="1" step="1"
                                        value="<?php echo (int)$usd_rate; ?>" required>
                                    <span class="input-group-text">Kz</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Royalty Líq. AOA (calculado)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Kz</span>
                                    <input type="number" class="form-control calc-input" id="nd_net_aoa" readonly
                                        tabindex="-1">
                                </div>
                            </div>

                            <!-- Preview do valor final -->
                            <div class="col-12">
                                <div class="amount-preview" id="amount_preview_box">
                                    <div class="ap-amount" id="ap_amount">Kz 0,00</div>
                                    <div class="ap-label">Royalty a creditar na wallet</div>
                                    <div class="ap-breakdown" id="ap_breakdown">Preenche os valores acima</div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-danger d-none mt-3" id="step2_error" style="font-size:.78rem"></div>
                    </div>

                    <!-- PASSO 3 — Documentos + Confirmação -->
                    <div class="form-step" id="step3">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="account-info-box" id="step3_summary">
                                    <!-- preenchido via JS -->
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small">
                                    Relatório de Streaming / Comprovativo <span
                                        class="text-muted fw-normal">(opcional)</span>
                                </label>
                                <input type="file" class="form-control form-control-sm" id="nd_report"
                                    accept=".pdf,image/*">
                                <div class="form-text">PDF ou imagem (ex: relatório Spotify, Apple Music, etc.).</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Notas internas</label>
                                <textarea class="form-control form-control-sm" id="nd_notes" rows="2"
                                    placeholder="Ex: Depósito mensal referente a royalties de streaming — Janeiro 2025"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-success" style="font-size:.8rem">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Ao confirmar, o royalty será <strong>registado como pago</strong>,
                                    <strong>Kz <?php ?></strong> creditados na wallet do utilizador,
                                    uma <strong>transacção</strong> registada no histórico e uma
                                    <strong>notificação</strong> enviada automaticamente.
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-danger d-none mt-2" id="step3_error" style="font-size:.78rem"></div>
                    </div>

                </div><!-- /modal-body -->
                <div class="modal-footer border-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_prev_step"
                        style="display:none!important">
                        <i class="bi bi-chevron-left me-1"></i> Anterior
                    </button>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-sm text-white" id="btn_next_step"
                            style="background:linear-gradient(135deg,#FF0089,#f97316);border:none;min-width:120px">
                            <span class="normal-lbl">Continuar <i class="bi bi-chevron-right ms-1"></i></span>
                            <span class="loading-lbl d-none">
                                <span class="spinner-border spinner-border-sm me-1"></span>A guardar…
                            </span>
                        </button>
                    </div>
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
            const PROCESS_manager = '<?php echo paymentPanelBaseUrl(); ?>/process';

            // ── AJAX helper ──────────────────────────────────────────
            async function post(payload) {
                const fd = new FormData();
                Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
                fd.append('csrf_token', CSRF);
                const r = await fetch(PROCESS_manager, {
                    method: 'POST',
                    body: fd
                });
                return r.json();
            }

            function setLoad(btn, state) {
                btn.querySelector('.normal-lbl').classList.toggle('d-none', state);
                btn.querySelector('.loading-lbl').classList.toggle('d-none', !state);
                btn.disabled = state;
            }

            // ════════════════════════════════════════════════════════
            // VER DETALHES
            // ════════════════════════════════════════════════════════
            window.viewRoyalty = async function(id) {
                document.getElementById('royaltyViewBody').innerHTML =
                    '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
                document.getElementById('royaltyViewFooter').innerHTML =
                    '<button class="btn btn-sm btn-outline-secondary ms-auto" data-bs-dismiss="modal">Fechar</button>';
                bootstrap.Modal.getOrCreateInstance(document.getElementById('royaltyViewModal')).show();
                try {
                    const data = await post({
                        action: 'get_royalty_details',
                        id_royalty: id
                    });
                    if (data.ok) {
                        document.getElementById('royaltyViewBody').innerHTML = data.html;
                        if (data.footer_html) document.getElementById('royaltyViewFooter').innerHTML = data
                            .footer_html;
                    } else {
                        document.getElementById('royaltyViewBody').innerHTML =
                            '<div class="alert alert-danger">' + data.message + '</div>';
                    }
                } catch {
                    document.getElementById('royaltyViewBody').innerHTML =
                        '<div class="alert alert-danger">Erro de ligação.</div>';
                }
            };

            // ════════════════════════════════════════════════════════
            // PAGAR ROYALTY EXISTENTE (pendente)
            // ════════════════════════════════════════════════════════
            window.payRoyalty = async function(id) {
                document.getElementById('pay_royalty_id').value = id;
                document.getElementById('pay_royalty_error').classList.add('d-none');
                document.getElementById('pay_report_file').value = '';
                document.getElementById('pay_notes').value = '';

                // Carregar info do royalty para mostrar no modal
                const infoBox = document.getElementById('pay_royalty_info');
                infoBox.innerHTML =
                    '<div class="text-center text-muted py-2"><div class="spinner-border spinner-border-sm me-2"></div>A carregar...</div>';

                bootstrap.Modal.getOrCreateInstance(document.getElementById('royaltyPayModal')).show();

                try {
                    const data = await post({
                        action: 'get_royalty_details',
                        id_royalty: id
                    });
                    if (data.ok) {
                        infoBox.innerHTML = data.html;
                    } else {
                        infoBox.innerHTML = '<div class="text-danger small">' + data.message + '</div>';
                    }
                } catch {
                    infoBox.innerHTML = '<div class="text-danger small">Erro ao carregar.</div>';
                }
            };

            document.getElementById('btn_confirm_pay_royalty').addEventListener('click', async function() {
                const id = document.getElementById('pay_royalty_id').value;
                const errEl = document.getElementById('pay_royalty_error');
                errEl.classList.add('d-none');
                if (!id) return;

                const file = document.getElementById('pay_report_file').files[0];
                const notes = document.getElementById('pay_notes').value;

                if (file && file.size > 5 * 1024 * 1024) {
                    errEl.textContent = 'Ficheiro excede 5MB.';
                    errEl.classList.remove('d-none');
                    return;
                }

                setLoad(this, true);
                const fd = new FormData();
                fd.append('action', 'pay_royalty');
                fd.append('id_royalty', id);
                fd.append('admin_note', notes);
                fd.append('csrf_token', CSRF);
                if (file) fd.append('report_file', file);

                try {
                    const r = await fetch(PROCESS_manager, {
                        method: 'POST',
                        body: fd
                    });
                    const data = await r.json();
                    if (data.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('royaltyPayModal')).hide();
                        await Swal.fire({
                            icon: 'success',
                            title: 'Royalty Pago!',
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
                setLoad(this, false);
            });

            // ════════════════════════════════════════════════════════
            // NOVO DEPÓSITO — Wizard
            // ════════════════════════════════════════════════════════
            let currentStep = 1;
            const STEPS = 3;
            const ndModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('newDepositModal'));

            window.openNewDeposit = function() {
                goToStep(1);
                // Reset
                document.getElementById('nd_user').value = '';
                document.getElementById('nd_album').value = '';
                document.getElementById('nd_album').disabled = true;
                document.getElementById('nd_track').value = '';
                document.getElementById('nd_track').disabled = true;
                document.getElementById('nd_account_wrap').style.display = 'none';
                document.getElementById('nd_gross').value = '';
                document.getElementById('nd_fee_pct').value = '10';
                document.getElementById('nd_rate').value = '<?php echo (int)$usd_rate; ?>';
                ['nd_fee_usd', 'nd_net_usd', 'nd_net_aoa'].forEach(id => document.getElementById(id).value = '');
                document.getElementById('ap_amount').textContent = 'Kz 0,00';
                document.getElementById('ap_breakdown').textContent = 'Preenche os valores acima';
                ['step1_error', 'step2_error', 'step3_error'].forEach(id => document.getElementById(id).classList
                    .add('d-none'));
                ndModal.show();
            };

            function goToStep(n) {
                currentStep = n;
                for (let i = 1; i <= STEPS; i++) {
                    const s = document.getElementById('step' + i);
                    const sc = document.getElementById('sc' + i);
                    const sl = document.getElementById('sl' + i);
                    if (s) s.classList.toggle('active', i === n);
                    if (sc) {
                        sc.classList.toggle('active', i === n);
                        sc.classList.toggle('done', i < n);
                    }
                    if (sl) sl.classList.toggle('active', i === n);
                    const line = document.getElementById('sline' + i);
                    if (line) line.classList.toggle('done', i < n);
                }
                const prevBtn = document.getElementById('btn_prev_step');
                const nextBtn = document.getElementById('btn_next_step');
                prevBtn.style.display = n > 1 ? 'block' : 'none';
                if (n < STEPS) {
                    nextBtn.querySelector('.normal-lbl').innerHTML =
                        'Continuar <i class="bi bi-chevron-right ms-1"></i>';
                } else {
                    nextBtn.querySelector('.normal-lbl').innerHTML =
                        '<i class="bi bi-check-lg me-1"></i> Confirmar Depósito';
                }
            }

            document.getElementById('btn_prev_step').addEventListener('click', () => {
                if (currentStep > 1) goToStep(currentStep - 1);
            });

            document.getElementById('btn_next_step').addEventListener('click', async function() {
                if (currentStep === 1) {
                    // Validar passo 1
                    const userId = document.getElementById('nd_user').value;
                    const albumId = document.getElementById('nd_album').value;
                    const trackId = document.getElementById('nd_track').value;
                    const errEl = document.getElementById('step1_error');
                    errEl.classList.add('d-none');
                    if (!userId || !albumId || !trackId) {
                        errEl.textContent = 'Selecciona utilizador, álbum e faixa.';
                        errEl.classList.remove('d-none');
                        return;
                    }
                    // Preencher info do passo 2
                    const opt = document.getElementById('nd_track').options[document.getElementById(
                        'nd_track').selectedIndex];
                    const t2 = document.getElementById('step2_track_info');
                    const uOpt = document.getElementById('nd_user').options[document.getElementById(
                        'nd_user').selectedIndex];
                    t2.innerHTML = `
                <div class="ai-row"><span class="ai-lbl">Utilizador</span><span class="ai-val">${uOpt.dataset.name} (${uOpt.dataset.email})</span></div>
                <div class="ai-row"><span class="ai-lbl">Faixa</span><span class="ai-val">${opt.text}</span></div>
                <div class="ai-row"><span class="ai-lbl">Período</span><span class="ai-val">${String(document.getElementById('nd_month').value).padStart(2,'0')}/${document.getElementById('nd_year').value}</span></div>
            `;
                    goToStep(2);

                } else if (currentStep === 2) {
                    // Validar passo 2
                    const gross = parseFloat(document.getElementById('nd_gross').value || 0);
                    const netAoa = parseFloat(document.getElementById('nd_net_aoa').value || 0);
                    const errEl = document.getElementById('step2_error');
                    errEl.classList.add('d-none');
                    if (!gross || gross <= 0) {
                        errEl.textContent = 'Receita bruta inválida.';
                        errEl.classList.remove('d-none');
                        return;
                    }
                    if (!netAoa || netAoa <= 0) {
                        errEl.textContent = 'Valor em AOA inválido.';
                        errEl.classList.remove('d-none');
                        return;
                    }

                    // Preencher resumo passo 3
                    const feeUsd = parseFloat(document.getElementById('nd_fee_usd').value || 0);
                    const netUsd = parseFloat(document.getElementById('nd_net_usd').value || 0);
                    const rate = parseFloat(document.getElementById('nd_rate').value || 0);
                    const uOpt = document.getElementById('nd_user').options[document.getElementById(
                        'nd_user').selectedIndex];
                    const tOpt = document.getElementById('nd_track').options[document.getElementById(
                        'nd_track').selectedIndex];
                    document.getElementById('step3_summary').innerHTML = `
                <div class="ai-row"><span class="ai-lbl">Utilizador</span><span class="ai-val">${uOpt.dataset.name}</span></div>
                <div class="ai-row"><span class="ai-lbl">Faixa</span><span class="ai-val">${tOpt.text}</span></div>
                <div class="ai-row"><span class="ai-lbl">Período</span><span class="ai-val">${String(document.getElementById('nd_month').value).padStart(2,'0')}/${document.getElementById('nd_year').value}</span></div>
                <div class="ai-row"><span class="ai-lbl">Receita Bruta</span><span class="ai-val">$ ${gross.toFixed(4)}</span></div>
                <div class="ai-row"><span class="ai-lbl">Taxa Wasom Upfy (10%)</span><span class="ai-val" style="color:#ef4444">$ ${feeUsd.toFixed(4)}</span></div>
                <div class="ai-row"><span class="ai-lbl">Royalty Líq. USD</span><span class="ai-val">$ ${netUsd.toFixed(4)}</span></div>
                <div class="ai-row"><span class="ai-lbl">Taxa Câmbio</span><span class="ai-val">1$ = Kz ${rate.toLocaleString('pt-AO')}</span></div>
                <div class="ai-row"><span class="ai-lbl">Royalty Líq. AOA</span><span class="ai-val" style="color:#FF0089;font-weight:800;font-size:.95rem">Kz ${netAoa.toLocaleString('pt-AO',{minimumFractionDigits:2})}</span></div>
            `;
                    goToStep(3);

                } else if (currentStep === 3) {
                    // Submeter
                    const errEl = document.getElementById('step3_error');
                    errEl.classList.add('d-none');
                    setLoad(this, true);

                    const fd = new FormData();
                    fd.append('action', 'manual_deposit');
                    fd.append('csrf_token', CSRF);
                    fd.append('id_users', document.getElementById('nd_user').value);
                    fd.append('id_album', document.getElementById('nd_album').value);
                    fd.append('id_track', document.getElementById('nd_track').value);
                    fd.append('year_royalty', document.getElementById('nd_year').value);
                    fd.append('month_royalty', document.getElementById('nd_month').value);
                    fd.append('gross_revenue', document.getElementById('nd_gross').value);
                    fd.append('platform_fee', document.getElementById('nd_fee_usd').value);
                    fd.append('net_royalty_aoa', document.getElementById('nd_net_aoa').value);
                    fd.append('exchange_rate', document.getElementById('nd_rate').value);
                    fd.append('admin_note', document.getElementById('nd_notes').value);
                    const reportFile = document.getElementById('nd_report').files[0];
                    if (reportFile) fd.append('report_file', reportFile);

                    try {
                        const r = await fetch(PROCESS_manager, {
                            method: 'POST',
                            body: fd
                        });
                        const data = await r.json();
                        if (data.ok) {
                            ndModal.hide();
                            await Swal.fire({
                                icon: 'success',
                                title: 'Depósito Registado!',
                                text: data.message,
                                confirmButtonColor: '#FF0089'
                            });
                            location.reload();
                        } else {
                            errEl.textContent = 'Erro: ' + (data.message || 'Falha desconhecida');
                            errEl.classList.remove('d-none');
                            console.error('Servidor respondeu com erro:', data);
                        }
                    } catch (err) {
                        errEl.textContent = 'Erro de ligação: ' + (err.message || 'Sem conexão');
                        errEl.classList.remove('d-none');
                        console.error('Erro de rede:', err);
                    }
                    setLoad(this, false);
                }
            });

            // ── Seleccionar utilizador → carregar conta + álbuns ──
            document.getElementById('nd_user').addEventListener('change', async function() {
                const id = this.value;
                const wrap = document.getElementById('nd_account_wrap');
                const box = document.getElementById('nd_account_box');
                const albSel = document.getElementById('nd_album');
                const trkSel = document.getElementById('nd_track');

                albSel.innerHTML = '<option value="">A carregar...</option>';
                albSel.disabled = true;
                trkSel.innerHTML = '<option value="">Primeiro selecciona o álbum...</option>';
                trkSel.disabled = true;

                if (!id) {
                    wrap.style.display = 'none';
                    return;
                }
                wrap.style.display = 'block';
                box.className = 'account-info-box';
                box.innerHTML =
                    '<div class="text-center text-muted py-2"><div class="spinner-border spinner-border-sm me-2"></div>A carregar conta...</div>';

                try {
                    // Carregar conta
                    const [accData, albData] = await Promise.all([
                        post({
                            action: 'get_user_account',
                            id_users: id
                        }),
                        post({
                            action: 'get_user_albums',
                            id_users: id
                        }),
                    ]);

                    // Conta
                    if (accData.ok) {
                        box.innerHTML = accData.account_html;
                        box.className = 'account-info-box verified';
                    } else {
                        box.innerHTML =
                            '<div class="d-flex align-items-center gap-2"><i class="bi bi-exclamation-triangle text-danger"></i><span class="text-danger small">' +
                            accData.message + '</span></div>';
                        box.className = 'account-info-box empty';
                    }

                    // Álbuns
                    if (albData.ok && albData.albums_html) {
                        albSel.innerHTML = '<option value="">Selecciona um álbum...</option>' + albData
                            .albums_html;
                        albSel.disabled = false;
                    } else {
                        albSel.innerHTML = '<option value="">Sem álbuns disponíveis</option>';
                    }
                } catch {
                    box.innerHTML = '<div class="text-danger small">Erro de ligação.</div>';
                }
            });

            // ── Seleccionar álbum → carregar faixas ──
            document.getElementById('nd_album').addEventListener('change', async function() {
                const id = this.value;
                const trkSel = document.getElementById('nd_track');
                trkSel.innerHTML = '<option value="">A carregar...</option>';
                trkSel.disabled = true;
                if (!id) return;
                try {
                    const data = await post({
                        action: 'get_album_tracks',
                        id_album: id
                    });
                    if (data.ok && data.tracks_html) {
                        trkSel.innerHTML = '<option value="">Selecciona uma faixa...</option>' + data
                            .tracks_html;
                        trkSel.disabled = false;
                    } else {
                        trkSel.innerHTML = '<option value="">Sem faixas disponíveis</option>';
                    }
                } catch {
                    trkSel.innerHTML = '<option value="">Erro ao carregar</option>';
                }
            });

            // ── Cálculo automático de valores ──────────────────────
            function recalc() {
                const gross = parseFloat(document.getElementById('nd_gross').value || 0);
                const feePct = parseFloat(document.getElementById('nd_fee_pct').value || 0);
                const rate = parseFloat(document.getElementById('nd_rate').value || 0);

                const feeUsd = +(gross * feePct / 100).toFixed(4);
                const netUsd = +(gross - feeUsd).toFixed(4);
                const netAoa = +(netUsd * rate).toFixed(2);

                document.getElementById('nd_fee_usd').value = feeUsd;
                document.getElementById('nd_net_usd').value = netUsd;
                document.getElementById('nd_net_aoa').value = netAoa;

                // Preview
                document.getElementById('ap_amount').textContent = 'Kz ' + netAoa.toLocaleString('pt-AO', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                document.getElementById('ap_breakdown').innerHTML =
                    `Receita bruta: <strong>$ ${gross.toFixed(4)}</strong> &nbsp;·&nbsp;` +
                    `Taxa Wasom Upfy (${feePct}%): <strong style="color:#ef4444">$ ${feeUsd.toFixed(4)}</strong><br>` +
                    `Royalty líq. USD: <strong>$ ${netUsd.toFixed(4)}</strong> &nbsp;·&nbsp;` +
                    `Câmbio: <strong>1$ = Kz ${rate.toLocaleString('pt-AO')}</strong>`;
            }

            ['nd_gross', 'nd_fee_pct', 'nd_rate'].forEach(id => {
                document.getElementById(id).addEventListener('input', recalc);
            });

        })();
    </script>
</body>

</html>