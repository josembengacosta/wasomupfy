<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Contas Bancárias (Listagem)
// Arquivo: wu-panel-2026/pages/accounts/all-accounts.php
// Rota:    wu-panel-2026/accounts
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.view'); // ou accounts.view (criar nova permissão)

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// ── Feedback ──
$msg = $_GET['msg'] ?? null;
$feedback = null; // inicializa para evitar aviso

$feedback = match ($msg) {
    'deleted'     => ['success', 'bi-trash',    'Conta eliminada com sucesso.'],
    'updated'     => ['success', 'bi-check',    'Conta actualizada com sucesso.'],
    'verified'    => ['success', 'bi-check',    'Conta verificada com sucesso.'],
    'rejected'    => ['warning', 'bi-x',        'Conta rejeitada.'],
    'error'       => ['danger',  'bi-x-circle', 'Ocorreu um erro.'],
    default       => null,
};

// ── Estatísticas com subqueries separadas (evita problemas com colunas) ──────────
$total_accounts = (int)$db->query("SELECT COUNT(*) FROM _account")->fetchColumn();
$iban_count = (int)$db->query("SELECT COUNT(*) FROM _account WHERE type_account = 'IBAN'")->fetchColumn();
$express_count = (int)$db->query("SELECT COUNT(*) FROM _account WHERE type_account = 'Express'")->fetchColumn();
$pending = (int)$db->query("SELECT COUNT(*) FROM _account WHERE status_account = 'pending'")->fetchColumn();
$verified = (int)$db->query("SELECT COUNT(*) FROM _account WHERE status_account = 'verified'")->fetchColumn();
$rejected = (int)$db->query("SELECT COUNT(*) FROM _account WHERE status_account = 'rejected'")->fetchColumn();
$total_users = (int)$db->query("SELECT COUNT(*) FROM _users")->fetchColumn();
$without_account = $total_users - $total_accounts;

$stats = [
    'total_accounts' => $total_accounts,
    'iban_count' => $iban_count,
    'express_count' => $express_count,
    'pending' => $pending,
    'verified' => $verified,
    'rejected' => $rejected,
    'total_users' => $total_users,
    'without_account' => $without_account,
];

// ── Filtros e ordenação ───────────────────────────────────────────────────────
$per_page  = 15;
$page      = max(1, (int)($_GET['page'] ?? 1));
$f_user    = trim($_GET['user']   ?? '');
$f_type    = trim($_GET['type']   ?? '');
$f_status  = trim($_GET['status'] ?? '');
$f_is_default = null;
if (isset($_GET['default']) && $_GET['default'] !== '') {
    $f_is_default = (int)$_GET['default'];
}
$sort_col  = in_array($_GET['sort'] ?? '', ['id_account', 'full_name_account', 'type_account', 'status_account', 'creat_account']) ? $_GET['sort'] : 'creat_account';
$sort_dir  = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$where  = [];
$params = [];

if ($f_user !== '') {
    $where[]  = "(u.first_name LIKE ? OR u.second_name LIKE ? OR u.email_user LIKE ?)";
    $params[] = '%' . $f_user . '%';
    $params[] = '%' . $f_user . '%';
    $params[] = '%' . $f_user . '%';
}
if ($f_type !== '') {
    $where[]  = 'a.type_account = ?';
    $params[] = $f_type;
}
if ($f_status !== '') {
    $where[]  = 'a.status_account = ?';
    $params[] = $f_status;
}
if ($f_is_default !== null) {
    $where[]  = 'a.is_default = ?';
    $params[] = $f_is_default;
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── Contagem ─────────────────────────────────────────────────────────────────
$count_stmt = $db->prepare("
    SELECT COUNT(DISTINCT a.id_account)
    FROM _account a
    LEFT JOIN _users u ON u.id_users = a.id_users
    $sql_where
");
$count_stmt->execute($params);
$total_filtered = (int)$count_stmt->fetchColumn();
$total_pages    = max(1, (int)ceil($total_filtered / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;

// ── Dados ─────────────────────────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT
        a.id_account,
        a.full_name_account,
        a.tel_account,
        a.email_account,
        a.iban,
        a.express_number,
        a.type_account,
        a.status_account,
        a.is_default,
        a.creat_account,
        a.modif_account,
        u.id_users,
        u.first_name,
        u.second_name,
        u.email_user,
        u.photo_user,
        (SELECT COUNT(*) FROM _withdrawal WHERE id_account = a.id_account) AS withdrawals_count
    FROM _account a
    LEFT JOIN _users u ON u.id_users = a.id_users
    $sql_where
    ORDER BY a.$sort_col $sort_dir
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$accounts = $stmt->fetchAll();

// ── Helpers (mantidos, apenas pequenos ajustes) ──────────────────────────────
function acc_status_badge(string $s): string
{
    return match ($s) {
        'verified' => '<span class="badge bg-success">Verificada</span>',
        'pending'  => '<span class="badge bg-warning text-dark">Pendente</span>',
        'rejected' => '<span class="badge bg-danger">Rejeitada</span>',
        default    => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}

function acc_type_badge(string $t): string
{
    $map = [
        'IBAN'      => ['IBAN (Bancária)', 'bg-primary'],
        'Express'   => ['Express', 'bg-info text-dark'],
        'PayPal'    => ['PayPal', 'bg-secondary'],
    ];
    [$label, $class] = $map[$t] ?? [ucfirst($t), 'bg-secondary'];
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}

function acc_fmt_date($date): string
{
    if (!$date) return '—';
    $ts = strtotime($date);
    if (!$ts) return '—';
    $diff = time() - $ts;
    if ($diff < 60)     return 'agora';
    if ($diff < 3600)   return floor($diff / 60) . 'min atrás';
    if ($diff < 86400)  return floor($diff / 3600) . 'h atrás';
    if ($diff < 604800) return floor($diff / 86400) . 'd atrás';
    return date('d/m/Y', $ts);
}

function acc_avatar(string $name, ?string $photo, int $size = 32): string
{
    $ini = '';
    $parts = explode(' ', trim($name), 2);
    $ini .= mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1, 'UTF-8'), 'UTF-8');
    $ini .= mb_strtoupper(mb_substr($parts[1] ?? '', 0, 1, 'UTF-8'), 'UTF-8');
    $colors = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308', '#3b82f6', '#ef4444'];
    $color  = $colors[abs(crc32($name)) % count($colors)];

    if ($photo) {
        return '<img src="' . APP_URL . '/assets/comprovantes/uploads/users/' . htmlspecialchars($photo) . '"
                     width="' . $size . '" height="' . $size . '"
                     style="border-radius:50%;object-fit:cover;border:2px solid rgba(255,0,137,.2)"
                     onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'"
                     alt="" />
                <div style="width:' . $size . 'px;height:' . $size . 'px;border-radius:50%;background:' . $color . ';
                            display:none;align-items:center;justify-content:center;
                            font-weight:700;font-size:' . ($size * 0.3) . 'px;color:#fff;flex-shrink:0">' . $ini . '</div>';
    }
    return '<div style="width:' . $size . 'px;height:' . $size . 'px;border-radius:50%;background:' . $color . ';
                         display:flex;align-items:center;justify-content:center;
                         font-weight:700;font-size:' . ($size * 0.3) . 'px;color:#fff;flex-shrink:0">' . $ini . '</div>';
}

function acc_sort_url(string $col, string $cur_col, string $cur_dir, array $get): string
{
    $dir = ($col === $cur_col && $cur_dir === 'ASC') ? 'desc' : 'asc';
    return '?' . http_build_query(array_merge($get, ['sort' => $col, 'dir' => $dir, 'page' => 1]));
}

function acc_sort_icon(string $col, string $cur_col, string $cur_dir): string
{
    if ($col !== $cur_col) return '';
    return $cur_dir === 'ASC' ? ' ▲' : ' ▼';
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
    <title>Contas Bancárias — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
    .acc-s-verified {
        background: rgba(34, 197, 94, .15);
        color: #166534;
    }

    .acc-s-pending {
        background: rgba(234, 179, 8, .15);
        color: #92400e;
    }

    .acc-s-rejected {
        background: rgba(239, 68, 68, .15);
        color: #991b1b;
    }

    .dark-mode .acc-s-verified {
        background: rgba(34, 197, 94, .2);
        color: #4ade80;
    }

    .dark-mode .acc-s-pending {
        background: rgba(234, 179, 8, .2);
        color: #facc15;
    }

    .dark-mode .acc-s-rejected {
        background: rgba(239, 68, 68, .2);
        color: #f87171;
    }

    .acc-stat {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .acc-stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .acc-stat-num {
        font-size: 1.3rem;
        font-weight: 800;
        line-height: 1;
    }

    .acc-stat-lbl {
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

    #accounts-table th {
        font-size: .74rem;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
        white-space: nowrap;
        cursor: pointer;
    }

    #accounts-table td {
        font-size: .82rem;
        vertical-align: middle;
    }

    .acc-empty {
        text-align: center;
        padding: 48px 24px;
        opacity: .4;
    }

    .user-avatar-sm {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        object-fit: cover;
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
                        <h2 class="h4 mb-1"><i class="bi bi-bank me-2"></i>Contas Bancárias</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item active text-white-stable">Contas</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                <?php if ($feedback): ?>
                <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3">
                    <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                    <?php echo htmlspecialchars($feedback[2]); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Cards de estatísticas -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="acc-stat">
                            <div class="acc-stat-icon" style="background:rgba(255,0,137,.1)"><i class="bi bi-bank"
                                    style="color:#FF0089"></i></div>
                            <div>
                                <div class="acc-stat-num"><?php echo number_format($stats['total_accounts']); ?></div>
                                <div class="acc-stat-lbl">Total Contas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="acc-stat">
                            <div class="acc-stat-icon" style="background:rgba(34,197,94,.1)"><i
                                    class="bi bi-check-circle" style="color:#22c55e"></i></div>
                            <div>
                                <div class="acc-stat-num"><?php echo number_format($stats['verified']); ?></div>
                                <div class="acc-stat-lbl">Verificadas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="acc-stat">
                            <div class="acc-stat-icon" style="background:rgba(234,179,8,.1)"><i
                                    class="bi bi-hourglass-split" style="color:#eab308"></i></div>
                            <div>
                                <div class="acc-stat-num"><?php echo number_format($stats['pending']); ?></div>
                                <div class="acc-stat-lbl">Pendentes</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="acc-stat">
                            <div class="acc-stat-icon" style="background:rgba(239,68,68,.1)"><i class="bi bi-x-circle"
                                    style="color:#ef4444"></i></div>
                            <div>
                                <div class="acc-stat-num"><?php echo number_format($stats['rejected']); ?></div>
                                <div class="acc-stat-lbl">Rejeitadas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="acc-stat">
                            <div class="acc-stat-icon" style="background:rgba(59,130,246,.1)"><i
                                    class="bi bi-credit-card" style="color:#3b82f6"></i></div>
                            <div>
                                <div class="acc-stat-num"><?php echo number_format($stats['iban_count']); ?></div>
                                <div class="acc-stat-lbl">IBAN</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="acc-stat">
                            <div class="acc-stat-icon" style="background:rgba(139,92,246,.1)"><i class="bi bi-phone"
                                    style="color:#8b5cf6"></i></div>
                            <div>
                                <div class="acc-stat-num"><?php echo number_format($stats['express_count']); ?></div>
                                <div class="acc-stat-lbl">Express</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="acc-stat">
                            <div class="acc-stat-icon" style="background:rgba(107,114,128,.1)"><i class="bi bi-people"
                                    style="color:#6b7280"></i></div>
                            <div>
                                <div class="acc-stat-num"><?php echo number_format($stats['total_users']); ?></div>
                                <div class="acc-stat-lbl">Utilizadores</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="acc-stat">
                            <div class="acc-stat-icon" style="background:rgba(239,68,68,.1)"><i class="bi bi-person-x"
                                    style="color:#ef4444"></i></div>
                            <div>
                                <div class="acc-stat-num"><?php echo number_format($stats['without_account']); ?></div>
                                <div class="acc-stat-lbl">Sem Conta</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="filter-card">
                    <form method="GET" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/accounts" id="filter-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Utilizador</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="user"
                                    value="<?php echo htmlspecialchars($f_user); ?>" placeholder="Nome ou e-mail" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tipo de Conta</label>
                                <select class="form-select form-select-sm filter-instant" name="type">
                                    <option value="">Todos</option>
                                    <?php foreach (['IBAN' => 'IBAN (Bancária)', 'Express' => 'Express', 'PayPal' => 'PayPal'] as $v => $l): ?>
                                    <option value="<?php echo $v; ?>" <?php echo $f_type === $v ? 'selected' : ''; ?>>
                                        <?php echo $l; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <select class="form-select form-select-sm filter-instant" name="status">
                                    <option value="">Todos</option>
                                    <?php foreach (['pending' => 'Pendente', 'verified' => 'Verificada', 'rejected' => 'Rejeitada'] as $v => $l): ?>
                                    <option value="<?php echo $v; ?>" <?php echo $f_status === $v ? 'selected' : ''; ?>>
                                        <?php echo $l; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Conta Principal</label>
                                <select class="form-select form-select-sm filter-instant" name="default">
                                    <option value="">Todas</option>
                                    <option value="1" <?php echo $f_is_default === 1 ? 'selected' : ''; ?>>Sim</option>
                                    <option value="0" <?php echo $f_is_default === 0 ? 'selected' : ''; ?>>Não</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex gap-1">
                                <button type="submit" class="btn btn-sm text-white w-100"
                                    style="background:#FF0089;border-color:#FF0089"><i class="bi bi-search"></i>
                                    Filtrar</button>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/accounts"
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
                            <?php if ($total_filtered !== $stats['total_accounts']): ?>
                            <span style="color:#FF0089"><?php echo number_format($total_filtered); ?></span> de
                            <?php echo number_format($stats['total_accounts']); ?> contas
                            <?php else: ?>
                            <?php echo number_format($total_filtered); ?> contas
                            <?php endif; ?>
                        </span>
                        <span style="font-size:.76rem;opacity:.5">Página <?php echo $page; ?> de
                            <?php echo $total_pages; ?></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="accounts-table">
                            <thead>
                                <tr>
                                    <th style="width:60px"><a
                                            href="<?php echo acc_sort_url('id_account', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">ID<?php echo acc_sort_icon('id_account', $sort_col, $sort_dir); ?></a>
                                    </th>
                                    <th>Utilizador</th>
                                    <th>Titular da Conta</th>
                                    <th>Tipo</th>
                                    <th>IBAN / Express</th>
                                    <th>Estado</th>
                                    <th>Principal</th>
                                    <th>Saques</th>
                                    <th><a href="<?php echo acc_sort_url('creat_account', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">Criada<?php echo acc_sort_icon('creat_account', $sort_col, $sort_dir); ?></a>
                                    </th>
                                    <th style="width:60px;text-align:center">Acções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($accounts)): ?>
                                <tr>
                                    <td colspan="10">
                                        <div class="acc-empty"><i class="bi bi-bank"></i>
                                            <p class="mb-0 mt-2">Nenhuma conta encontrada para os filtros aplicados.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($accounts as $acc): ?>
                                <?php
                                        $user_name = trim(($acc['first_name'] ?? '') . ' ' . ($acc['second_name'] ?? ''));
                                        $display_name = $user_name ?: $acc['email_user'];
                                        $acc_detail = $acc['type_account'] === 'IBAN' ? $acc['iban'] : $acc['express_number'];
                                        $acc_detail_display = $acc_detail ? (strlen($acc_detail) > 20 ? substr($acc_detail, 0, 18) . '…' : $acc_detail) : '—';
                                        ?>
                                <tr>
                                    <td><span
                                            style="font-family:monospace;font-size:.74rem;opacity:.55">#<?php echo $acc['id_account']; ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php echo acc_avatar($display_name, $acc['photo_user'], 30); ?>
                                            <div>
                                                <div style="font-size:.8rem;font-weight:600">
                                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $acc['id_users']; ?>"
                                                        class="text-inherit text-decoration-none">
                                                        <?php echo htmlspecialchars($display_name); ?>
                                                    </a>
                                                </div>
                                                <div style="font-size:.7rem;opacity:.5">
                                                    <?php echo htmlspecialchars($acc['email_user']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($acc['full_name_account']); ?></td>
                                    <td><?php echo acc_type_badge($acc['type_account']); ?></td>
                                    <td><code><?php echo htmlspecialchars($acc_detail_display); ?></code></td>
                                    <td><?php echo acc_status_badge($acc['status_account']); ?></td>
                                    <td><?php echo $acc['is_default'] ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-muted"></i>'; ?>
                                    </td>
                                    <td><span
                                            class="badge bg-secondary"><?php echo (int)$acc['withdrawals_count']; ?></span>
                                    </td>
                                    <td style="font-size:.78rem;white-space:nowrap">
                                        <?php echo acc_fmt_date($acc['creat_account']); ?></td>
                                    <td class="text-center">
                                        <div class="actions-dropdown dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button"
                                                data-bs-toggle="dropdown" data-bs-reference="toggle"
                                                aria-expanded="false" title="Acções"><i
                                                    class="bi bi-three-dots-vertical"></i></button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item"
                                                        href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/accounts/view?id=<?php echo $acc['id_account']; ?>"><i
                                                            class="bi bi-eye text-info"></i> Visualizar</a></li>
                                                <?php if (hasPermission($admin_id, 'finances.edit')): ?>
                                                <li><a class="dropdown-item" href="#"
                                                        onclick="deleteAccount(<?php echo $acc['id_account']; ?>);return false"><i
                                                            class="bi bi-trash text-danger"></i> Excluir</a></li>
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
                        <nav aria-label="Paginação de contas">
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
    <script>
    (function() {
        const BASE_URL = '<?php echo APP_URL; ?>';
        const ADMIN_PATH = '<?php echo ADMIN_PATH; ?>';
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const PROCESS = BASE_URL + '/' + ADMIN_PATH + '/accounts/process';

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

        window.deleteAccount = async function(id) {
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
                    id_account: id,
                    password_confirm: password
                });
                if (data.ok) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Eliminada!',
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
    </script>
</body>

</html>