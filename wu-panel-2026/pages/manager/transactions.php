<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY for Business — Histórico de Transacções
// Arquivo: wu-panel-2026/pages/manager/transactions.php
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.view');
if (empty($_SESSION['payment_control_auth'])) { header('Location: '.APP_URL.'/'.ADMIN_PATH.'/manager/gestion'); exit; }
$_SESSION['biz_auth_time'] = time();
if (!isset($_SESSION['admin_csrf_token'])) $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

$per_page = 20;
$page     = max(1,(int)($_GET['page']??1));
$f_user   = trim($_GET['user']  ??'');
$f_type   = trim($_GET['type']  ??'');
$f_from   = trim($_GET['from']  ??'');
$f_to     = trim($_GET['to']    ??'');

$where=[]; $params=[];
if ($f_user) { $where[]="(u.first_name LIKE ? OR u.second_name LIKE ? OR u.email_user LIKE ?)"; $params[]="%$f_user%";$params[]="%$f_user%";$params[]="%$f_user%"; }
if ($f_type) { $where[]='tx.type_transaction=?'; $params[]=$f_type; }
if ($f_from) { $where[]='tx.creat_transaction>=?'; $params[]=$f_from.' 00:00:00'; }
if ($f_to)   { $where[]='tx.creat_transaction<=?'; $params[]=$f_to.' 23:59:59'; }
$sw = $where ? 'WHERE '.implode(' AND ',$where) : '';

$cnt=$db->prepare("SELECT COUNT(*) FROM _transaction tx LEFT JOIN _users u ON u.id_users=tx.id_users $sw");
$cnt->execute($params); $total=(int)$cnt->fetchColumn();
$total_pages=max(1,(int)ceil($total/$per_page)); $page=min($page,$total_pages); $offset=($page-1)*$per_page;

$stmt=$db->prepare("
    SELECT tx.*,
           COALESCE(CONCAT(u.first_name,' ',COALESCE(u.second_name,'')),'Sistema') AS user_name,
           u.email_user, u.photo_user, u.id_users
    FROM _transaction tx
    LEFT JOIN _users u ON u.id_users=tx.id_users
    $sw ORDER BY tx.creat_transaction DESC LIMIT $per_page OFFSET $offset
");
$stmt->execute($params); $transactions=$stmt->fetchAll();

$types=['royalty_credit'=>'Crédito Royalty','withdrawal'=>'Saque','plan_payment'=>'Pagamento Plano','refund'=>'Reembolso','adjustment'=>'Ajuste','fee'=>'Taxa'];
$payment_sidebar_active='transactions';
require_once __DIR__.'/include/payment-sidebar.php';
function tx_icon2(string $t): string { return match($t){'royalty_credit'=>'<i class="bi bi-music-note-beamed text-success"></i>','withdrawal'=>'<i class="bi bi-arrow-up-circle text-danger"></i>','plan_payment'=>'<i class="bi bi-credit-card text-primary"></i>','refund'=>'<i class="bi bi-arrow-counterclockwise text-warning"></i>','adjustment'=>'<i class="bi bi-sliders text-info"></i>','fee'=>'<i class="bi bi-percent text-secondary"></i>',default=>'<i class="bi bi-arrow-left-right text-muted"></i>'}; }
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
    <title>Transacções — Wasom Upfy for Business</title>
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
                    <div class="biz-topbar-title">Histórico de Transacções</div>
                    <div class="biz-topbar-sub"><a href="<?php echo APP_URL.'/'.ADMIN_PATH; ?>/manager/gestion"
                            style="color:#888;text-decoration:none">Dashboard</a> → Transacções</div>
                </div>
            </div>
            <span class="text-muted small"><?php echo date('d/m/Y H:i'); ?></span>
        </div>
        <div class="biz-inner">

            <!-- Filtros -->
            <div class="filter-card">
                <form method="GET">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3"><label class="form-label">Utilizador</label>
                            <input type="text" name="user" class="form-control form-control-sm"
                                value="<?php echo htmlspecialchars($f_user); ?>" placeholder="Nome ou e-mail">
                        </div>
                        <div class="col-md-2"><label class="form-label">Tipo</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <?php foreach ($types as $v=>$l): ?>
                                <option value="<?php echo $v; ?>" <?php echo $f_type===$v?'selected':''; ?>>
                                    <?php echo $l; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2"><label class="form-label">De</label>
                            <input type="date" name="from" class="form-control form-control-sm"
                                value="<?php echo htmlspecialchars($f_from); ?>">
                        </div>
                        <div class="col-md-2"><label class="form-label">Até</label>
                            <input type="date" name="to" class="form-control form-control-sm"
                                value="<?php echo htmlspecialchars($f_to); ?>">
                        </div>
                        <div class="col-md-2 d-flex gap-1">
                            <button type="submit" class="btn btn-sm text-white flex-fill" style="background:#FF0089"><i
                                    class="bi bi-search"></i></button>
                            <a href="<?php echo APP_URL.'/'.ADMIN_PATH; ?>/manager/transactions"
                                class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabela -->
            <div class="biz-card">
                <div class="biz-card-header">
                    <span class="biz-card-title"><?php echo number_format($total); ?> transacção(ões)</span>
                    <span style="font-size:.75rem;color:#aaa">Pág.
                        <?php echo $page; ?>/<?php echo $total_pages; ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover biz-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tipo</th>
                                <th>Utilizador</th>
                                <th>Valor</th>
                                <th>Saldo Antes</th>
                                <th>Saldo Depois</th>
                                <th>Referência</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($transactions)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted"><i
                                        class="bi bi-inbox fs-1 d-block mb-2"></i>Nenhuma transacção encontrada</td>
                            </tr>
                            <?php else: foreach($transactions as $tx): $is_out=in_array($tx['type_transaction'],['withdrawal','fee']); ?>
                            <tr>
                                <td><span
                                        style="font-family:monospace;font-size:.73rem;opacity:.55">#<?php echo $tx['id_transaction']; ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php echo tx_icon2($tx['type_transaction']); ?><span
                                            style="font-size:.76rem;text-transform:capitalize"><?php echo $types[$tx['type_transaction']]??ucfirst($tx['type_transaction']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:.8rem;font-weight:600">
                                        <?php echo htmlspecialchars($tx['user_name']); ?></div>
                                    <?php if($tx['email_user']): ?><div style="font-size:.7rem;color:#aaa">
                                        <?php echo htmlspecialchars($tx['email_user']); ?></div><?php endif; ?>
                                </td>
                                <td
                                    style="font-weight:700;white-space:nowrap;color:<?php echo $is_out?'#ef4444':'#22c55e'; ?>">
                                    <?php echo ($is_out?'−':'+').'Kz '.number_format((float)$tx['amount'],2,',','.'); ?>
                                </td>
                                <td style="font-size:.76rem;font-family:monospace;color:#888">
                                    <?php echo $tx['balance_before']!==null?'Kz '.number_format((float)$tx['balance_before'],2,',','.'):' —'; ?>
                                </td>
                                <td style="font-size:.76rem;font-family:monospace">
                                    <?php echo $tx['balance_after']!==null?'Kz '.number_format((float)$tx['balance_after'],2,',','.'):' —'; ?>
                                </td>
                                <td style="font-size:.75rem;font-family:monospace;color:#888">
                                    <?php echo htmlspecialchars($tx['reference']??'—'); ?></td>
                                <td style="font-size:.76rem;white-space:nowrap">
                                    <?php echo date('d/m/Y',strtotime($tx['creat_transaction'])); ?>
                                    <div style="color:#aaa">
                                        <?php echo date('H:i',strtotime($tx['creat_transaction'])); ?></div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if($total_pages>1): ?>
                <div class="d-flex justify-content-center py-3">
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?php echo $page<=1?'disabled':''; ?>"><a class="page-link pag-link"
                                    href="?page=<?php echo $page-1; ?>&user=<?php echo urlencode($f_user); ?>&type=<?php echo urlencode($f_type); ?>&from=<?php echo urlencode($f_from); ?>&to=<?php echo urlencode($f_to); ?>"><i
                                        class="bi bi-chevron-left"></i></a></li>
                            <?php for($pi=max(1,$page-2);$pi<=min($total_pages,$page+2);$pi++): ?>
                            <li class="page-item <?php echo $pi===$page?'active':''; ?>"><a class="page-link pag-link"
                                    href="?page=<?php echo $pi; ?>&user=<?php echo urlencode($f_user); ?>&type=<?php echo urlencode($f_type); ?>&from=<?php echo urlencode($f_from); ?>&to=<?php echo urlencode($f_to); ?>"><?php echo $pi; ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page>=$total_pages?'disabled':''; ?>"><a
                                    class="page-link pag-link"
                                    href="?page=<?php echo $page+1; ?>&user=<?php echo urlencode($f_user); ?>&type=<?php echo urlencode($f_type); ?>&from=<?php echo urlencode($f_from); ?>&to=<?php echo urlencode($f_to); ?>"><i
                                        class="bi bi-chevron-right"></i></a></li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>