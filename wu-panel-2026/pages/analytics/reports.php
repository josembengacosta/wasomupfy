<?php
// ═══════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Analytics: Relatórios para Clientes
// Arquivo: admin/pages/analytics/reports.php
// Rota:    admin/analytics/reports
// ═══════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// ── Paginação + filtros ──
$per_page     = 20;
$page         = max(1, (int)($_GET['page'] ?? 1));
$f_status     = trim($_GET['status'] ?? '');
$f_format     = trim($_GET['format'] ?? '');
$f_user       = (int)($_GET['user']   ?? 0);

$where  = ["h.id_users IS NOT NULL", "h.save_dashboard = 1"];
$params = [];

if ($f_status !== '') { $where[] = 'h.status = ?';       $params[] = $f_status; }
if ($f_format !== '') { $where[] = 'h.format = ?';       $params[] = $f_format; }
if ($f_user   > 0)   { $where[] = 'h.id_users = ?';     $params[] = $f_user; }

$sql_where = 'WHERE ' . implode(' AND ', $where);

$count = $db->prepare("SELECT COUNT(*) FROM _report_history h $sql_where");
$count->execute($params);
$total_filtered = (int)$count->fetchColumn();
$total_pages    = max(1, (int)ceil($total_filtered / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;

$stmt = $db->prepare("
    SELECT h.id_history, h.name_report, h.report_type, h.format,
           h.status, h.file_path, h.file_size_kb,
           COALESCE(h.generated_at, h.creat_history) AS generated_at,
           h.downloaded, h.rows_count, h.id_employees,
           CONCAT(u.first_name,' ',COALESCE(u.second_name,'')) AS user_name,
           u.email_user,
           CONCAT(e.first_name,' ',COALESCE(e.second_name,'')) AS emp_name
    FROM _report_history h
    LEFT JOIN _users u     ON u.id_users        = h.id_users
    LEFT JOIN _employees e ON e.id_employees    = h.id_employees
    $sql_where
    ORDER BY COALESCE(h.generated_at, h.creat_history) DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$reports = $stmt->fetchAll();

// ── Estatísticas globais ──
$stats = $db->query("
    SELECT
        COUNT(*)                              AS total,
        SUM(status = 'processing')            AS processing,
        SUM(status = 'success')               AS success,
        SUM(status = 'error')                 AS errors,
        SUM(downloaded = 1)                   AS downloaded,
        SUM(format = 'pdf')                   AS pdfs,
        SUM(format = 'excel' OR format = 'xlsx') AS excels,
        SUM(format = 'csv')                   AS csvs
    FROM _report_history
    WHERE id_users IS NOT NULL
      AND save_dashboard = 1
")->fetch();

// ── Lista de utilizadores para o select ──
$users_all = $db->query("
    SELECT id_users, CONCAT(first_name,' ',COALESCE(second_name,'')) AS name, email_user
    FROM _users
    ORDER BY first_name, second_name
")->fetchAll();

$csrf = htmlspecialchars($_SESSION['admin_csrf_token']);

function rpt_status_badge(string $s): string {
    return match($s) {
        'success'    => '<span class="badge rpt-ok">Gerado</span>',
        'processing' => '<span class="badge rpt-processing">Processando</span>',
        'error'      => '<span class="badge rpt-err">Erro</span>',
        default      => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}
function rpt_format_icon(string $f): string {
    return match(strtolower($f)) {
        'pdf'   => '<i class="bi bi-file-earmark-pdf text-danger"></i>',
        'excel','xlsx' => '<i class="bi bi-file-earmark-excel text-success"></i>',
        'csv'   => '<i class="bi bi-filetype-csv text-primary"></i>',
        default => '<i class="bi bi-file-earmark"></i>',
    };
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo $csrf; ?>" />
    <meta name="theme-color" content="#FF0089" />
    <title>Relatórios — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <!-- SheetJS — gerar XLSX/CSV client-side -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>
    <!-- html2pdf — gerar PDF client-side -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
    /* Stat cards */
    .rpt-stat {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .rpt-stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .rpt-stat-num {
        font-size: 1.3rem;
        font-weight: 800;
        line-height: 1;
    }

    .rpt-stat-lbl {
        font-size: .74rem;
        opacity: .6;
        margin-top: 2px;
    }

    /* Status badges */
    .rpt-ok {
        background: rgba(34, 197, 94, .15);
        color: #166534;
    }

    .rpt-processing {
        background: rgba(234, 179, 8, .15);
        color: #92400e;
    }

    .rpt-err {
        background: rgba(239, 68, 68, .15);
        color: #991b1b;
    }

    /* Filter card */
    .filter-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 12px;
        padding: 16px 18px;
        margin-bottom: 18px;
    }

    .filter-card .form-label {
        font-size: .76rem;
        font-weight: 600;
        margin-bottom: 3px;
    }

    /* Table */
    #rpt-table th {
        font-size: .74rem;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
    }

    #rpt-table td {
        font-size: .82rem;
        vertical-align: middle;
    }

    /* Dropdown acções */
    .actions-dropdown .dropdown-menu {
        position: fixed !important;
        z-index: 1055;
        min-width: 180px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .12);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 10px;
        padding: 4px;
    }

    .actions-dropdown .dropdown-item {
        font-size: .82rem;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 7px;
    }

    .actions-dropdown .dropdown-item i {
        width: 16px;
        flex-shrink: 0;
    }

    #rpt-table tbody tr:has(.dropdown.show) {
        background: var(--card-bg, #fff) !important;
    }

    /* Paginação */
    .rpt-pagination .page-link {
        border-radius: 8px !important;
        margin: 0 2px;
        font-size: .8rem;
    }

    /* Progresso no modal */
    .gen-step {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 0;
        border-bottom: 1px solid var(--border-color, #f0f0f8);
        font-size: .84rem;
    }

    .gen-step:last-child {
        border-bottom: none;
    }

    .gen-step-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .8rem;
    }

    .gen-step.pending .gen-step-icon {
        background: var(--border-color, #f0f0f8);
        color: #aaa;
    }

    .gen-step.running .gen-step-icon {
        background: rgba(234, 179, 8, .15);
        color: #92400e;
    }

    .gen-step.done .gen-step-icon {
        background: rgba(34, 197, 94, .15);
        color: #166534;
    }

    .gen-step.error .gen-step-icon {
        background: rgba(239, 68, 68, .1);
        color: #991b1b;
    }

    /* Toast */
    .toast-container {
        z-index: 9999;
    }

    /* Dark mode */
    .dark-mode .filter-card,
    .dark-mode .rpt-stat {
        background: var(--dark-card, #1a1a27);
        border-color: var(--dark-border, #2e2e42);
    }

    .dark-mode .history-item {
        background: var(--dark-card, #1a1a27);
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
                        <h2 class="h4 mb-1">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i>Relatórios para Clientes
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL.'/'.ADMIN_PATH; ?>" class="text-secondary">Home</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable">Relatórios</li>
                            </ol>
                        </nav>
                    </div>
                    <?php if (hasPermission($admin_id, 'analytics.view')): ?>
                    <div class="col-auto ms-auto">
                        <button class="btn btn-sm text-white" style="background:#22c55e;border-color:#22c55e"
                            data-bs-toggle="modal" data-bs-target="#modalNewReport">
                            <i class="bi bi-plus-lg me-1"></i>Gerar Relatório
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-2">
                        <div class="rpt-stat">
                            <div class="rpt-stat-icon" style="background:rgba(255,0,137,.1)">
                                <i class="bi bi-file-earmark-bar-graph" style="color:#FF0089"></i>
                            </div>
                            <div>
                                <div class="rpt-stat-num"><?php echo number_format($stats['total']); ?></div>
                                <div class="rpt-stat-lbl">Total</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="rpt-stat">
                            <div class="rpt-stat-icon" style="background:rgba(34,197,94,.1)">
                                <i class="bi bi-check-circle text-success"></i>
                            </div>
                            <div>
                                <div class="rpt-stat-num"><?php echo number_format($stats['success']); ?></div>
                                <div class="rpt-stat-lbl">Gerados</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="rpt-stat">
                            <div class="rpt-stat-icon" style="background:rgba(234,179,8,.1)">
                                <i class="bi bi-hourglass-split text-warning"></i>
                            </div>
                            <div>
                                <div class="rpt-stat-num"><?php echo number_format($stats['processing']); ?></div>
                                <div class="rpt-stat-lbl">Processando</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="rpt-stat">
                            <div class="rpt-stat-icon" style="background:rgba(59,130,246,.1)">
                                <i class="bi bi-download text-primary"></i>
                            </div>
                            <div>
                                <div class="rpt-stat-num"><?php echo number_format($stats['downloaded']); ?></div>
                                <div class="rpt-stat-lbl">Descarregados</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="rpt-stat">
                            <div class="rpt-stat-icon" style="background:rgba(239,68,68,.1)">
                                <i class="bi bi-file-earmark-pdf text-danger"></i>
                            </div>
                            <div>
                                <div class="rpt-stat-num"><?php echo number_format($stats['pdfs']); ?></div>
                                <div class="rpt-stat-lbl">PDFs</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="rpt-stat">
                            <div class="rpt-stat-icon" style="background:rgba(34,197,94,.1)">
                                <i class="bi bi-file-earmark-excel text-success"></i>
                            </div>
                            <div>
                                <div class="rpt-stat-num">
                                    <?php echo number_format($stats['excels'] + $stats['csvs']); ?></div>
                                <div class="rpt-stat-lbl">Excel / CSV</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="filter-card">
                    <form method="GET" action="<?php echo APP_URL.'/'.ADMIN_PATH; ?>/analytics/reports"
                        id="filter-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Utilizador</label>
                                <select class="form-select form-select-sm" name="user">
                                    <option value="">Todos os utilizadores</option>
                                    <?php foreach ($users_all as $u): ?>
                                    <option value="<?php echo $u['id_users']; ?>"
                                        <?php echo $f_user === (int)$u['id_users'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(trim($u['name'])); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <select class="form-select form-select-sm" name="status">
                                    <option value="">Todos</option>
                                    <option value="success" <?php echo $f_status==='success'?'selected':''; ?>>Gerado
                                    </option>
                                    <option value="processing" <?php echo $f_status==='processing'?'selected':''; ?>>
                                        Processando</option>
                                    <option value="error" <?php echo $f_status==='error'?'selected':''; ?>>Erro</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Formato</label>
                                <select class="form-select form-select-sm" name="format">
                                    <option value="">Todos</option>
                                    <option value="pdf" <?php echo $f_format==='pdf'?'selected':''; ?>>PDF</option>
                                    <option value="excel" <?php echo $f_format==='excel'?'selected':''; ?>>Excel
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex gap-1">
                                <button type="submit" class="btn btn-sm text-white w-100"
                                    style="background:#FF0089;border-color:#FF0089">
                                    <i class="bi bi-search"></i>
                                </button>
                                <a href="<?php echo APP_URL.'/'.ADMIN_PATH; ?>/analytics/reports"
                                    class="btn btn-sm btn-outline-secondary" title="Limpar">
                                    <i class="bi bi-x"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tabela de relatórios -->
                <div class="card p-0" style="border-radius:14px;overflow:hidden">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                        style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                        <span style="font-size:.82rem;font-weight:600">
                            <?php if ($total_filtered !== (int)$stats['total']): ?>
                            <span style="color:#FF0089"><?php echo number_format($total_filtered); ?></span>
                            de <?php echo number_format($stats['total']); ?> relatórios
                            <?php else: ?>
                            <?php echo number_format($total_filtered); ?> relatórios
                            <?php endif; ?>
                        </span>
                        <span style="font-size:.76rem;opacity:.5">
                            Página <?php echo $page; ?> de <?php echo $total_pages; ?>
                        </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between gap-2 px-3 py-2 flex-wrap"
                        style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                        <div style="font-size:.78rem;opacity:.6">
                            Seleciona relatórios para excluir do servidor e do banco de dados.
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-danger" id="btn-delete-selected" disabled>
                                <i class="bi bi-trash3 me-1"></i>Excluir selecionados
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" id="btn-delete-all">
                                <i class="bi bi-x-circle me-1"></i>Limpar tudo
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="rpt-table">
                            <thead>
                                <tr>
                                    <th style="width:42px">
                                        <input type="checkbox" class="form-check-input" id="check-all-reports" />
                                    </th>
                                    <th style="width:50px">#</th>
                                    <th>Utilizador</th>
                                    <th>Nome do relatório</th>
                                    <th>Formato</th>
                                    <th>Estado</th>
                                    <th>Tamanho</th>
                                    <th>Gerado por</th>
                                    <th>Data</th>
                                    <th style="width:50px">Acções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reports)): ?>
                                <tr>
                                    <td colspan="10">
                                        <div style="text-align:center;padding:48px 24px;opacity:.4">
                                            <i class="bi bi-file-earmark-x d-block mb-2" style="font-size:2.5rem"></i>
                                            Nenhum relatório encontrado.
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($reports as $r): ?>
                                <?php $canDelete = ((int)($r['id_employees'] ?? 0) === (int)$admin_id || $admin_role === 'super_admin'); ?>
                                <tr>
                                    <td>
                                        <?php if ($canDelete): ?>
                                        <input type="checkbox" class="form-check-input report-check"
                                            value="<?php echo (int)$r['id_history']; ?>" />
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="font-family:monospace;font-size:.72rem;opacity:.5">
                                            #<?php echo $r['id_history']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;font-size:.82rem">
                                            <?php echo htmlspecialchars(trim($r['user_name'])); ?>
                                        </div>
                                        <div style="font-size:.72rem;opacity:.5">
                                            <?php echo htmlspecialchars($r['email_user'] ?? ''); ?>
                                        </div>
                                    </td>
                                    <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                                        title="<?php echo htmlspecialchars($r['name_report']); ?>">
                                        <?php echo htmlspecialchars($r['name_report']); ?>
                                    </td>
                                    <td>
                                        <?php echo rpt_format_icon($r['format']); ?>
                                        <span style="font-size:.78rem;margin-left:4px;text-transform:uppercase">
                                            <?php echo htmlspecialchars($r['format']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo rpt_status_badge($r['status']); ?></td>
                                    <td style="font-size:.78rem;opacity:.7">
                                        <?php echo $r['file_size_kb'] ? $r['file_size_kb'] . ' KB' : '—'; ?>
                                    </td>
                                    <td style="font-size:.78rem;opacity:.7">
                                        <?php echo htmlspecialchars(trim($r['emp_name'] ?? 'Sistema')); ?>
                                    </td>
                                    <td style="white-space:nowrap">
                                        <div style="font-size:.8rem;font-weight:600">
                                            <?php echo $r['generated_at'] ? adm_fmt_date($r['generated_at']) : '—'; ?>
                                        </div>
                                        <div style="font-size:.7rem;opacity:.45;font-family:monospace">
                                            <?php echo $r['generated_at'] ? date('d/m/Y H:i', strtotime($r['generated_at'])) : ''; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="dropdown actions-dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button"
                                                data-bs-toggle="dropdown" data-bs-reference="toggle">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <?php if ($r['status'] === 'success' && $r['file_path']): ?>
                                                <li>
                                                    <a class="dropdown-item btn-view-report" href="#"
                                                        data-id="<?php echo $r['id_history']; ?>">
                                                        <i class="bi bi-eye text-info"></i>Visualizar
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item btn-download-report" href="#"
                                                        data-id="<?php echo $r['id_history']; ?>">
                                                        <i class="bi bi-download text-primary"></i>Descarregar
                                                    </a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider my-1">
                                                </li>
                                                <?php endif; ?>
                                                <?php if ($canDelete): ?>
                                                <li>
                                                    <a class="dropdown-item text-danger btn-delete-report" href="#"
                                                        data-id="<?php echo $r['id_history']; ?>"
                                                        data-name="<?php echo htmlspecialchars($r['name_report']); ?>">
                                                        <i class="bi bi-trash text-danger"></i>Excluir
                                                    </a>
                                                </li>
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
                        <nav>
                            <ul class="pagination pagination-sm rpt-pagination mb-0">
                                <li class="page-item <?php echo $page<=1?'disabled':''; ?>">
                                    <a class="page-link"
                                        href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page-1])); ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php $s=max(1,$page-2); $e=min($total_pages,$page+2);
                            if ($s>1): ?><li class="page-item"><a class="page-link"
                                        href="?<?php echo http_build_query(array_merge($_GET,['page'=>1])); ?>">1</a>
                                </li>
                                <?php if($s>2): ?><li class="page-item disabled"><span class="page-link">…</span></li>
                                <?php endif; endif; ?>
                                <?php for($i=$s;$i<=$e;$i++): ?>
                                <li class="page-item <?php echo $i===$page?'active':''; ?>">
                                    <a class="page-link"
                                        href="?<?php echo http_build_query(array_merge($_GET,['page'=>$i])); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                <?php if($e<$total_pages): ?>
                                <?php if($e<$total_pages-1): ?><li class="page-item disabled"><span
                                        class="page-link">…</span></li><?php endif; ?>
                                <li class="page-item"><a class="page-link"
                                        href="?<?php echo http_build_query(array_merge($_GET,['page'=>$total_pages])); ?>"><?php echo $total_pages; ?></a>
                                </li>
                                <?php endif; ?>
                                <li class="page-item <?php echo $page>=$total_pages?'disabled':''; ?>">
                                    <a class="page-link"
                                        href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page+1])); ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>

            </div><!-- /container-fluid -->
        </div><!-- /content -->
    </div><!-- /wrapper -->

    <!-- ══ MODAL — Gerar Relatório ══ -->
    <div class="modal fade" id="modalNewReport" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:44px;height:44px;border-radius:10px;background:rgba(34,197,94,.12);
                                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="bi bi-file-earmark-bar-graph" style="color:#22c55e;font-size:1.2rem"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0" style="font-size:.95rem;font-weight:700">Gerar Relatório para
                                Cliente</h5>
                            <p class="mb-0" style="font-size:.76rem;opacity:.55">O ficheiro é gerado no browser e
                                guardado no servidor</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <!-- Passo 1: Configuração -->
                    <div id="step-config">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.82rem;font-weight:600">
                                    Utilizador <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="rpt-user-id" required>
                                    <option value="">— Selecione um utilizador —</option>
                                    <?php foreach ($users_all as $u): ?>
                                    <option value="<?php echo $u['id_users']; ?>">
                                        <?php echo htmlspecialchars(trim($u['name'])); ?>
                                        — <?php echo htmlspecialchars($u['email_user']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.82rem;font-weight:600">
                                    Formato <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="rpt-format">
                                    <option value="pdf">PDF — relatório formatado</option>
                                    <option value="excel" selected>Excel (XLSX) — dados em tabela</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.82rem;font-weight:600">Data início</label>
                                <input type="date" class="form-control" id="rpt-from"
                                    value="<?php echo date('Y-m-01'); ?>" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.82rem;font-weight:600">Data fim</label>
                                <input type="date" class="form-control" id="rpt-to"
                                    value="<?php echo date('Y-m-d'); ?>" />
                            </div>
                            <div class="col-12">
                                <label class="form-label" style="font-size:.82rem;font-weight:600">Incluir no
                                    relatório</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="rpt-inc-streams" checked />
                                        <label class="form-check-label" for="rpt-inc-streams" style="font-size:.82rem">
                                            Streams e downloads
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="rpt-inc-revenue" checked />
                                        <label class="form-check-label" for="rpt-inc-revenue" style="font-size:.82rem">
                                            Receita e royalties
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="rpt-inc-catalog" checked />
                                        <label class="form-check-label" for="rpt-inc-catalog" style="font-size:.82rem">
                                            Catálogo (álbuns e faixas)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" style="font-size:.82rem;font-weight:600">Nome do
                                    relatório</label>
                                <input type="text" class="form-control" id="rpt-name"
                                    placeholder="Ex: Relatório mensal — João Silva (deixa vazio para auto)" />
                            </div>
                        </div>
                        <div class="alert alert-danger d-none mt-3" id="rpt-error" style="font-size:.83rem"></div>
                    </div>

                    <!-- Passo 2: Progresso -->
                    <div id="step-progress" style="display:none">
                        <div id="gen-steps">
                            <div class="gen-step pending" id="gstep-fetch">
                                <div class="gen-step-icon"><i class="bi bi-database"></i></div>
                                <div>
                                    <div style="font-weight:600">Obter dados da BD</div>
                                    <div style="font-size:.74rem;opacity:.6">Streams, catálogo e royalties</div>
                                </div>
                            </div>
                            <div class="gen-step pending" id="gstep-file">
                                <div class="gen-step-icon"><i class="bi bi-file-earmark"></i></div>
                                <div>
                                    <div style="font-weight:600">Gerar ficheiro</div>
                                    <div style="font-size:.74rem;opacity:.6" id="gstep-file-lbl">—</div>
                                </div>
                            </div>
                            <div class="gen-step pending" id="gstep-save">
                                <div class="gen-step-icon"><i class="bi bi-cloud-upload"></i></div>
                                <div>
                                    <div style="font-weight:600">Guardar no servidor</div>
                                    <div style="font-size:.74rem;opacity:.6">Ficheiro físico + registo na BD</div>
                                </div>
                            </div>
                            <div class="gen-step pending" id="gstep-done">
                                <div class="gen-step-icon"><i class="bi bi-check-lg"></i></div>
                                <div>
                                    <div style="font-weight:600">Concluído</div>
                                    <div style="font-size:.74rem;opacity:.6" id="gstep-done-lbl">—</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"
                        id="btn-modal-cancel">Cancelar</button>
                    <button type="button" class="btn btn-sm text-white" id="btn-generate"
                        style="background:#22c55e;border-color:#22c55e">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="spin-gen"></span>
                        <i class="bi bi-gear me-1" id="icon-gen"></i>Gerar e Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <footer>
        <div class="container">
            <div class="col-12 text-center py-2" style="font-size:.8rem">
                <p class="mb-0">© 2026 Wasom Upfy. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="" />
            <div class="loader-progress"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.min.js"></script>
    <script>
    window.__BASE_URL__ = '<?php echo APP_URL; ?>';
    window.__ADMIN_PATH__ = '<?php echo ADMIN_PATH; ?>';

    (function() {
        'use strict';

        const BASE = '<?php echo APP_URL; ?>';
        const APATH = '<?php echo ADMIN_PATH; ?>';
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const DATA_EP = BASE + '/' + APATH + '/analytics/get_report_data';
        const PROC_EP = BASE + '/' + APATH + '/analytics/process-reports';

        // ── Utilitários ──
        function escHtml(s) {
            if (s == null) return '';
            return String(s).replace(/[&<>"']/g, function(c) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [c];
            });
        }

        function showToast(type, title, msg) {
            var cont = document.querySelector('.toast-container');
            var id = 'toast-' + Date.now();
            var bg = type === 'success' ? 'bg-success' : (type === 'warning' ? 'bg-warning text-dark' :
                'bg-danger');
            cont.insertAdjacentHTML('beforeend',
                '<div id="' + id + '" class="toast align-items-center text-white ' + bg +
                ' border-0" data-bs-autohide="true" data-bs-delay="5000">' +
                '<div class="d-flex"><div class="toast-body"><strong>' + escHtml(title) + '</strong><br>' +
                escHtml(msg) + '</div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>'
            );
            var el = document.getElementById(id);
            new bootstrap.Toast(el).show();
            el.addEventListener('hidden.bs.toast', function() {
                el.remove();
            });
        }

        function mimeByExt(fileExt) {
            switch ((fileExt || '').toLowerCase()) {
                case 'pdf':
                    return 'application/pdf';
                case 'csv':
                    return 'text/csv;charset=utf-8';
                case 'xlsx':
                default:
                    return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            }
        }

        function downloadBase64File(fileB64, fileExt, filename) {
            if (!fileB64) return;

            var binStr = atob(fileB64);
            var bytes = new Uint8Array(binStr.length);
            for (var i = 0; i < binStr.length; i++) bytes[i] = binStr.charCodeAt(i);

            var blob = new Blob([bytes], {
                type: mimeByExt(fileExt)
            });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        }

        function formatMoneyKz(value) {
            var num = Number(value || 0);
            return 'Kz ' + num.toLocaleString('pt-AO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        async function confirmDanger(title, htmlText, confirmText) {
            if (typeof Swal === 'undefined') {
                return Promise.resolve(window.confirm(title + '\n' + htmlText.replace(/<[^>]+>/g, ' ')));
            }

            var result = await Swal.fire({
                title: title,
                html: htmlText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                focusCancel: true
            });
            return !!result.isConfirmed;
        }

        async function showAlert(icon, title, text) {
            if (typeof Swal === 'undefined') {
                showToast(icon === 'success' ? 'success' : 'error', title, text);
                return;
            }
            await Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonColor: icon === 'success' ? '#22c55e' : '#FF0089'
            });
        }

        function stepSet(id, state) {
            var el = document.getElementById(id);
            if (!el) return;
            el.className = 'gen-step ' + state;
            var icon = el.querySelector('.gen-step-icon i');
            if (!icon) return;
            var icons = {
                pending: 'bi-circle',
                running: 'bi-arrow-repeat',
                done: 'bi-check-circle-fill',
                error: 'bi-x-circle-fill'
            };
            icon.className = 'bi ' + (icons[state] || 'bi-circle');
        }

        // ── Filtros — submit automático ──
        document.querySelectorAll('#filter-form select').forEach(function(sel) {
            sel.addEventListener('change', function() {
                document.getElementById('filter-form').submit();
            });
        });

        // ── Gerar relatório ──
        var btnGen = document.getElementById('btn-generate');
        if (btnGen) {
            btnGen.addEventListener('click', async function() {
                var userId = document.getElementById('rpt-user-id').value;
                var format = document.getElementById('rpt-format').value;
                var dateFrom = document.getElementById('rpt-from').value;
                var dateTo = document.getElementById('rpt-to').value;
                var incStreams = document.getElementById('rpt-inc-streams').checked;
                var incRevenue = document.getElementById('rpt-inc-revenue').checked;
                var incCatalog = document.getElementById('rpt-inc-catalog').checked;
                var rptName = document.getElementById('rpt-name').value.trim();
                var errDiv = document.getElementById('rpt-error');

                // Validar
                errDiv.classList.add('d-none');
                if (!userId) {
                    errDiv.textContent = 'Selecione um utilizador.';
                    errDiv.classList.remove('d-none');
                    return;
                }
                if (!dateFrom || !dateTo) {
                    errDiv.textContent = 'Defina o período.';
                    errDiv.classList.remove('d-none');
                    return;
                }
                if (new Date(dateFrom) > new Date(dateTo)) {
                    errDiv.textContent = 'Data início deve ser anterior à data fim.';
                    errDiv.classList.remove('d-none');
                    return;
                }

                // Mostrar progresso
                document.getElementById('step-config').style.display = 'none';
                document.getElementById('step-progress').style.display = 'block';
                btnGen.disabled = true;
                document.getElementById('spin-gen').classList.remove('d-none');
                document.getElementById('icon-gen').classList.add('d-none');
                document.getElementById('btn-modal-cancel').disabled = true;

                // Reiniciar steps
                ['gstep-fetch', 'gstep-file', 'gstep-save', 'gstep-done'].forEach(function(s) {
                    stepSet(s, 'pending');
                });

                try {
                    // ── PASSO 1: buscar dados ──
                    stepSet('gstep-fetch', 'running');
                    var fd = new FormData();
                    fd.append('user_id', userId);
                    fd.append('date_from', dateFrom);
                    fd.append('date_to', dateTo);
                    fd.append('inc_streams', incStreams ? 1 : 0);
                    fd.append('inc_revenue', incRevenue ? 1 : 0);
                    fd.append('inc_catalog', incCatalog ? 1 : 0);
                    fd.append('csrf_token', CSRF);

                    var res = await fetch(DATA_EP, {
                        method: 'POST',
                        body: fd
                    });
                    var data = await res.json();
                    if (!res.ok || data.error) throw new Error(data.error || 'Erro ao obter dados.');
                    stepSet('gstep-fetch', 'done');

                    var userName = data.user ? data.user.name : 'Utilizador';
                    var period = dateFrom.replace(/-/g, '') + '_' + dateTo.replace(/-/g, '');
                    if (!rptName) rptName = 'Relatório ' + userName + ' ' + dateFrom + ' a ' +
                        dateTo;

                    // ── PASSO 2: gerar ficheiro ──
                    stepSet('gstep-file', 'running');
                    document.getElementById('gstep-file-lbl').textContent = 'A gerar ' + format
                        .toUpperCase() + '...';

                    var fileB64 = '';
                    var fileExt = format === 'excel' ? 'xlsx' : format;
                    var fileName = ('relatorio_' + userName + '_' + period).replace(/[^a-z0-9_\-]/gi,
                        '_');

                    if (format === 'excel') {
                        fileB64 = await genExcelB64(data, userName, dateFrom, dateTo);
                        fileExt = 'xlsx';
                    } else {
                        fileB64 = await genPdfB64(data, userName, dateFrom, dateTo);
                        fileExt = 'pdf';
                    }

                    if (!fileB64) {
                        throw new Error('Não foi possível gerar o ficheiro do relatório.');
                    }

                    downloadBase64File(fileB64, fileExt, fileName + '.' + fileExt);

                    stepSet('gstep-file', 'done');

                    // ── PASSO 3: guardar no servidor ──
                    stepSet('gstep-save', 'running');

                    var params = JSON.stringify({
                        date_from: dateFrom,
                        date_to: dateTo,
                        inc_streams: incStreams,
                        inc_revenue: incRevenue,
                        inc_catalog: incCatalog
                    });
                    var rowCount = (Array.isArray(data.streams) ? data.streams.length : 0) +
                        (Array.isArray(data.royalties) ? data.royalties.length : 0) +
                        (Array.isArray(data.catalog) ? data.catalog.length : 0);

                    var fd2 = new FormData();
                    fd2.append('action', 'save_report');
                    fd2.append('user_id', userId);
                    fd2.append('format', format === 'excel' ? 'excel' : format);
                    fd2.append('file_ext', fileExt);
                    fd2.append('name_report', rptName);
                    fd2.append('parameters', params);
                    fd2.append('rows_count', String(rowCount));
                    fd2.append('file_b64', fileB64);
                    fd2.append('csrf_token', CSRF);

                    var res2 = await fetch(PROC_EP, {
                        method: 'POST',
                        body: fd2
                    });
                    var data2 = await res2.json();

                    if (!data2.ok) {
                        stepSet('gstep-save', 'error');
                        throw new Error(data2.message || 'Erro ao guardar no servidor.');
                    }
                    stepSet('gstep-save', 'done');

                    // ── PASSO 4: concluído ──
                    stepSet('gstep-done', 'done');
                    document.getElementById('gstep-done-lbl').textContent =
                        'Relatório #' + data2.id + ' guardado com sucesso.';

                    // Fechar modal após 1.5s e recarregar tabela
                    setTimeout(function() {
                        bootstrap.Modal.getInstance(document.getElementById('modalNewReport'))
                            .hide();
                        showToast('success', 'Relatório gerado',
                            'O ficheiro foi guardado, o download iniciou e o utilizador foi notificado.'
                            );
                        setTimeout(function() {
                            location.reload();
                        }, 800);
                    }, 1500);

                } catch (err) {
                    // Mostrar erro no step actual + no form
                    showToast('error', 'Erro', err.message);
                    document.getElementById('step-config').style.display = 'block';
                    document.getElementById('step-progress').style.display = 'none';
                    errDiv.textContent = err.message;
                    errDiv.classList.remove('d-none');
                    btnGen.disabled = false;
                    document.getElementById('spin-gen').classList.add('d-none');
                    document.getElementById('icon-gen').classList.remove('d-none');
                    document.getElementById('btn-modal-cancel').disabled = false;
                }
            });
        }

        // Repor modal ao fechar
        var modalEl = document.getElementById('modalNewReport');
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function() {
                document.getElementById('step-config').style.display = 'block';
                document.getElementById('step-progress').style.display = 'none';
                document.getElementById('rpt-error').classList.add('d-none');
                document.getElementById('btn-generate').disabled = false;
                document.getElementById('spin-gen').classList.add('d-none');
                document.getElementById('icon-gen').classList.remove('d-none');
                document.getElementById('btn-modal-cancel').disabled = false;
            });
        }

        // ── Geradores de ficheiro ──
        function genExcelB64(data, userName, dateFrom, dateTo) {
            return new Promise(function(resolve) {
                var wb = XLSX.utils.book_new();
                var currency = data.currency || {
                    code: 'AOA',
                    symbol: 'Kz',
                    fx_usd_aoa: 900
                };
                var plan = data.plan || null;
                var planFeatures = plan && Array.isArray(plan.features) ? plan.features
                    .filter(function(f) {
                        return Number(f.is_included) === 1;
                    })
                    .map(function(f) {
                        return f.feature_text;
                    }).join(' | ') : '—';

                // Folha de streams
                if (data.streams && data.streams.length) {
                    var rows = data.streams.map(function(s) {
                        return {
                            'Ano': s.year_stream,
                            'Mês': s.month_stream,
                            'Faixa': s.title_track || '',
                            'Artista': s.stage_name || '',
                            'Plataforma': s.name_store || '',
                            'Streams': parseInt(s.streams) || 0,
                            'Downloads': parseInt(s.downloads) || 0,
                            'Receita (Kz)': parseFloat(s.revenue_aoa) || 0
                        };
                    });
                    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(rows), 'Streams');
                }

                // Folha de royalties
                if (data.royalties && data.royalties.length) {
                    var royRows = data.royalties.map(function(r) {
                        return {
                            'Ano': r.year_royalty,
                            'Mês': r.month_royalty,
                            'Faixa': r.title_track || '',
                            'Artista': r.stage_name || '',
                            'Receita Bruta (Kz)': parseFloat(r.gross_revenue_aoa) || 0,
                            'Royalty Líq. (Kz)': parseFloat(r.net_royalty_aoa) || 0,
                            'Taxa de Câmbio': parseFloat(r.exchange_rate) || 0,
                            'Estado': r.status_royalty || ''
                        };
                    });
                    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(royRows), 'Royalties');
                }

                // Folha de catálogo
                if (data.catalog && data.catalog.length) {
                    var catRows = data.catalog.map(function(c) {
                        return {
                            'Álbum': c.title_album || '',
                            'Tipo': c.type_album || '',
                            'Faixa': c.title_track || '',
                            'Nº Faixa': c.track_number || '',
                            'Artista': c.stage_name || '',
                            'UPC': c.upc || '',
                            'ISRC': c.isrc || '',
                            'Lançamento': c.release_date || '',
                            'Estado': c.status_album || ''
                        };
                    });
                    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(catRows), 'Catálogo');
                }

                // Folha de totais
                var totSheet = XLSX.utils.json_to_sheet([{
                    'Utilizador': userName,
                    'Período início': dateFrom,
                    'Período fim': dateTo,
                    'Total Streams': data.totals ? data.totals.streams : 0,
                    'Total Downloads': data.totals ? data.totals.downloads : 0,
                    'Receita Total (Kz)': data.totals ? data.totals.revenue_aoa : 0,
                    'Moeda': currency.symbol + ' (' + currency.code + ')',
                    'Taxa USD→AOA': currency.fx_usd_aoa || 0,
                    'Plano': plan ? plan.name_plan : '—',
                    'Tipo de Plano': plan ? plan.type_plan : '—',
                    'Estado do Plano': plan ? plan.status_plan : '—',
                    'Royalty (%)': plan ? plan.royalty_rate : '—',
                    'Preço do Plano (Kz)': plan ? plan.price_plan : '—',
                    'Plano iniciado em': plan ? (plan.started_at || '—') : '—',
                    'Plano expira em': plan ? (plan.expires_at || '—') : '—',
                    'Features do Plano': planFeatures,
                }]);
                XLSX.utils.book_append_sheet(wb, totSheet, 'Resumo');

                if (plan) {
                    var planSheet = XLSX.utils.json_to_sheet([{
                        'Plano': plan.name_plan || '—',
                        'Slug': plan.slug_plan || '—',
                        'Tipo': plan.type_plan || '—',
                        'Royalty (%)': plan.royalty_rate || '—',
                        'Preço (Kz)': plan.price_plan || '—',
                        'Preço anual (Kz)': plan.price_annual || '—',
                        'Qtd. anual': plan.annual_qty || '—',
                        'Máx. artistas': plan.max_artists || '—',
                        'Máx. lançamentos': plan.max_releases || '—',
                        'Máx. faixas': plan.max_tracks_per_release || '—',
                        'Status': plan.status_plan || '—',
                        'Auto renovar': Number(plan.auto_renew || 0) === 1 ? 'Sim' : 'Não'
                    }]);
                    XLSX.utils.book_append_sheet(wb, planSheet, 'Plano');

                    if (Array.isArray(plan.features) && plan.features.length) {
                        var featRows = plan.features.map(function(f) {
                            return {
                                'Feature': f.feature_text || '',
                                'Incluída': Number(f.is_included) === 1 ? 'Sim' : 'Não'
                            };
                        });
                        XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(featRows), 'Plano Features');
                    }
                }

                var wbout = XLSX.write(wb, {
                    bookType: 'xlsx',
                    type: 'base64'
                });
                resolve(wbout);
            });
        }

        function genPdfB64(data, userName, dateFrom, dateTo) {
            return new Promise(function(resolve) {
                var totalStr = data.totals ? data.totals.streams : 0;
                var totalRev = data.totals ? formatMoneyKz(data.totals.revenue_aoa) : 'Kz 0,00';
                var planName = data.plan ? data.plan.name_plan : '—';
                var planType = data.plan ? data.plan.type_plan : '—';
                var planPrice = data.plan ? formatMoneyKz(data.plan.price_plan) : '—';
                var royRate = data.plan ? data.plan.royalty_rate : '—';
                var planStatus = data.plan ? data.plan.status_plan : '—';
                var planFeatures = data.plan && Array.isArray(data.plan.features) ? data.plan.features
                    .filter(function(f) {
                        return Number(f.is_included) === 1;
                    })
                    .map(function(f) {
                        return '<li>' + escHtml(f.feature_text) + '</li>';
                    }).join('') : '';

                var streamRows = (data.streams || []).map(function(s) {
                    return '<tr><td>' + escHtml(s.year_stream) + '-' + String(s.month_stream)
                        .padStart(2, '0') + '</td>' +
                        '<td>' + escHtml(s.title_track) + '</td>' +
                        '<td>' + escHtml(s.stage_name) + '</td>' +
                        '<td>' + escHtml(s.name_store) + '</td>' +
                        '<td style="text-align:right">' + parseInt(s.streams) + '</td>' +
                        '<td style="text-align:right">' + parseInt(s.downloads) + '</td>' +
                        '<td style="text-align:right">' + formatMoneyKz(s.revenue_aoa) +
                        '</td></tr>';
                }).join('');

                var royaltyRows = (data.royalties || []).map(function(r) {
                    return '<tr><td>' + escHtml(r.year_royalty) + '-' + String(r.month_royalty)
                        .padStart(2, '0') + '</td>' +
                        '<td>' + escHtml(r.title_track) + '</td>' +
                        '<td>' + escHtml(r.stage_name) + '</td>' +
                        '<td style="text-align:right">' + formatMoneyKz(r.gross_revenue_aoa) + '</td>' +
                        '<td style="text-align:right">' + formatMoneyKz(r.net_royalty_aoa) + '</td>' +
                        '<td>' + escHtml(r.status_royalty) + '</td></tr>';
                }).join('');

                var catRows = (data.catalog || []).map(function(c) {
                    return '<tr><td>' + escHtml(c.title_album) + '</td><td>' + escHtml(c
                            .title_track) + '</td>' +
                        '<td>' + escHtml(c.release_date) + '</td><td>' + escHtml(c.status_album) +
                        '</td></tr>';
                }).join('');

                var html = '<div style="font-family:Arial,sans-serif;padding:24px;color:#111">' +
                    '<div style="background:linear-gradient(135deg,#FF0089,#6c63ff);color:#fff;padding:20px;border-radius:10px;margin-bottom:20px">' +
                    '<h1 style="margin:0;font-size:1.4rem">Relatório de Desempenho</h1>' +
                    '<p style="margin:6px 0 0;opacity:.8">Wasom Upfy · Gerado em ' + new Date()
                    .toLocaleDateString('pt-AO') + '</p></div>' +
                    '<table style="width:100%;margin-bottom:16px;font-size:.9rem"><tr>' +
                    '<td><strong>Utilizador:</strong> ' + escHtml(userName) + '</td>' +
                    '<td><strong>Período:</strong> ' + escHtml(dateFrom) + ' a ' + escHtml(dateTo) +
                    '</td>' +
                    '<td><strong>Plano:</strong> ' + escHtml(planName) + ' · ' + escHtml(planType) + '</td></tr>' +
                    '<tr><td><strong>Royalty:</strong> ' + escHtml(String(royRate)) + '%</td>' +
                    '<td><strong>Status do Plano:</strong> ' + escHtml(planStatus) + '</td>' +
                    '<td><strong>Preço do Plano:</strong> ' + escHtml(planPrice) + '</td></tr>' +
                    '<tr><td><strong>Total Streams:</strong> ' + parseInt(totalStr).toLocaleString(
                    'pt-AO') + '</td>' +
                    '<td><strong>Receita Total:</strong> ' + totalRev + '</td><td></td></tr></table>' +
                    (planFeatures ?
                        '<h2 style="font-size:1rem;border-bottom:2px solid #FF0089;padding-bottom:6px">Features do Plano</h2>' +
                        '<ul style="margin:0 0 16px 18px;padding:0;font-size:.82rem">' + planFeatures + '</ul>' : '') +
                    (streamRows ?
                        '<h2 style="font-size:1rem;border-bottom:2px solid #FF0089;padding-bottom:6px">Streams e Receita</h2>' +
                        '<table border="1" cellpadding="5" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:.75rem">' +
                        '<thead style="background:#f4f4f8"><tr><th>Período</th><th>Faixa</th><th>Artista</th><th>Plataforma</th><th>Streams</th><th>Downloads</th><th>Receita (Kz)</th></tr></thead>' +
                        '<tbody>' + streamRows + '</tbody></table>' : '') +
                    (royaltyRows ?
                        '<h2 style="font-size:1rem;border-bottom:2px solid #FF0089;padding-bottom:6px;margin-top:16px">Royalties</h2>' +
                        '<table border="1" cellpadding="5" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:.75rem">' +
                        '<thead style="background:#f4f4f8"><tr><th>Período</th><th>Faixa</th><th>Artista</th><th>Receita Bruta (Kz)</th><th>Royalty Líq. (Kz)</th><th>Estado</th></tr></thead>' +
                        '<tbody>' + royaltyRows + '</tbody></table>' : '') +
                    (catRows ?
                        '<h2 style="font-size:1rem;border-bottom:2px solid #FF0089;padding-bottom:6px;margin-top:16px">Catálogo</h2>' +
                        '<table border="1" cellpadding="5" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:.75rem">' +
                        '<thead style="background:#f4f4f8"><tr><th>Álbum</th><th>Faixa</th><th>Lançamento</th><th>Estado</th></tr></thead>' +
                        '<tbody>' + catRows + '</tbody></table>' : '') +
                    '</div>';

                var el = document.createElement('div');
                el.innerHTML = html;
                document.body.appendChild(el);

                html2pdf().from(el).set({
                    margin: 0.4,
                    image: {
                        type: 'jpeg',
                        quality: 0.98
                    },
                    html2canvas: {
                        scale: 2
                    },
                    jsPDF: {
                        unit: 'in',
                        format: 'a4',
                        orientation: 'landscape'
                    }
                }).toPdf().get('pdf').then(function(pdf) {
                    var dataUri = pdf.output('datauristring');
                    el.remove();
                    resolve(String(dataUri).split(',')[1] || '');
                }).catch(function() {
                    el.remove();
                    resolve('');
                });
            });
        }

        // ── Visualizar ──
        document.querySelectorAll('.btn-view-report').forEach(function(btn) {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                var id = this.dataset.id;
                var fd = new FormData();
                fd.append('action', 'get_report_file');
                fd.append('id', id);
                fd.append('csrf_token', CSRF);
                try {
                    var r = await fetch(PROC_EP, {
                        method: 'POST',
                        body: fd
                    });
                    var d = await r.json();
                    if (d.ok && d.url) window.open(d.url, '_blank');
                    else showToast('error', 'Erro', d.message || 'Ficheiro não disponível.');
                } catch {
                    showToast('error', 'Erro', 'Erro de ligação.');
                }
            });
        });

        // ── Descarregar ──
        document.querySelectorAll('.btn-download-report').forEach(function(btn) {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                var id = this.dataset.id;
                var fd = new FormData();
                fd.append('action', 'download_report');
                fd.append('id', id);
                fd.append('csrf_token', CSRF);
                try {
                    var r = await fetch(PROC_EP, {
                        method: 'POST',
                        body: fd
                    });
                    var d = await r.json();
                    if (d.ok && d.url) window.location.href = d.url;
                    else showToast('error', 'Erro', d.message || 'Ficheiro não disponível.');
                } catch {
                    showToast('error', 'Erro', 'Erro de ligação.');
                }
            });
        });

        function getSelectedIds() {
            return Array.from(document.querySelectorAll('.report-check:checked')).map(function(el) {
                return el.value;
            });
        }

        function syncSelectionUi() {
            var checks = Array.from(document.querySelectorAll('.report-check'));
            var checked = checks.filter(function(el) {
                return el.checked;
            });
            var btnDeleteSelected = document.getElementById('btn-delete-selected');
            var checkAll = document.getElementById('check-all-reports');

            if (btnDeleteSelected) {
                btnDeleteSelected.disabled = checked.length === 0;
                btnDeleteSelected.innerHTML = '<i class="bi bi-trash3 me-1"></i>Excluir selecionados' +
                    (checked.length ? ' (' + checked.length + ')' : '');
            }
            if (checkAll) {
                checkAll.checked = checks.length > 0 && checked.length === checks.length;
                checkAll.indeterminate = checked.length > 0 && checked.length < checks.length;
            }
        }

        async function requestDelete(action, payload, successText) {
            var fd = new FormData();
            fd.append('action', action);
            Object.keys(payload).forEach(function(key) {
                fd.append(key, payload[key]);
            });
            fd.append('csrf_token', CSRF);

            var r = await fetch(PROC_EP, {
                method: 'POST',
                body: fd
            });
            return r.json();
        }

        var checkAllReports = document.getElementById('check-all-reports');
        if (checkAllReports) {
            checkAllReports.addEventListener('change', function() {
                document.querySelectorAll('.report-check').forEach(function(el) {
                    el.checked = checkAllReports.checked;
                });
                syncSelectionUi();
            });
        }

        document.querySelectorAll('.report-check').forEach(function(el) {
            el.addEventListener('change', syncSelectionUi);
        });
        syncSelectionUi();

        // ── Excluir individual ──
        document.querySelectorAll('.btn-delete-report').forEach(function(btn) {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                var id = this.dataset.id;
                var name = this.dataset.name;
                var confirmed = await confirmDanger(
                    'Excluir relatório?',
                    'O relatório <strong>' + escHtml(name) +
                    '</strong> será removido do servidor e do banco de dados.',
                    'Sim, excluir'
                );
                if (!confirmed) return;

                try {
                    var d = await requestDelete('delete_report', {
                        id: id
                    });
                    if (d.ok) {
                        // Remover linha da tabela
                        btn.closest('tr').remove();
                        showToast('success', 'Eliminado', 'Relatório removido com sucesso.');
                        syncSelectionUi();
                    } else {
                        await showAlert('error', 'Erro', d.message || 'Não foi possível excluir.');
                    }
                } catch {
                    await showAlert('error', 'Erro', 'Erro de ligação.');
                }
            });
        });

        var btnDeleteSelected = document.getElementById('btn-delete-selected');
        if (btnDeleteSelected) {
            btnDeleteSelected.addEventListener('click', async function() {
                var ids = getSelectedIds();
                if (!ids.length) return;

                var confirmed = await confirmDanger(
                    'Excluir selecionados?',
                    'Serão excluídos <strong>' + ids.length +
                    '</strong> relatórios do servidor e do banco de dados.',
                    'Sim, excluir selecionados'
                );
                if (!confirmed) return;

                try {
                    var d = await requestDelete('delete_selected_reports', {
                        ids_json: JSON.stringify(ids)
                    });
                    if (!d.ok) {
                        await showAlert('error', 'Erro', d.message || 'Não foi possível excluir os relatórios.');
                        return;
                    }

                    ids.forEach(function(id) {
                        var checkbox = document.querySelector('.report-check[value="' + id + '"]');
                        if (checkbox) {
                            var row = checkbox.closest('tr');
                            if (row) row.remove();
                        }
                    });
                    syncSelectionUi();
                    await showAlert('success', 'Relatórios excluídos', d.message || 'Relatórios removidos com sucesso.');
                } catch {
                    await showAlert('error', 'Erro', 'Erro de ligação.');
                }
            });
        }

        var btnDeleteAll = document.getElementById('btn-delete-all');
        if (btnDeleteAll) {
            btnDeleteAll.addEventListener('click', async function() {
                var confirmed = await confirmDanger(
                    'Limpar histórico?',
                    'Todos os relatórios que podes gerir serão removidos do servidor e do banco de dados.',
                    'Sim, limpar tudo'
                );
                if (!confirmed) return;

                try {
                    var d = await requestDelete('delete_all_reports', {});
                    if (!d.ok) {
                        await showAlert('error', 'Erro', d.message || 'Não foi possível limpar o histórico.');
                        return;
                    }

                    document.querySelectorAll('#rpt-table tbody tr').forEach(function(row) {
                        if (row.querySelector('.report-check') || row.querySelector('.btn-delete-report')) {
                            row.remove();
                        }
                    });
                    syncSelectionUi();
                    await showAlert('success', 'Histórico limpo', d.message || 'Relatórios removidos com sucesso.');
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } catch {
                    await showAlert('error', 'Erro', 'Erro de ligação.');
                }
            });
        }

    })();
    </script>
</body>

</html>
