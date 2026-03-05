<?php
// WASOM UPFY v2.0 - Painel Principal
// Arquivo: dashboard/painel.php
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$user = getUserById((int)$_SESSION['id_users']);
if (!$user) {
    session_destroy();
    redirect('/login', ['error' => 'csrf']);
}

$first_name = htmlspecialchars($user['first_name']);
$gender = htmlspecialchars($user['gender']);
$user_name = htmlspecialchars($user['user_name'] ?? '');
$email_verified = (bool)$user['email_verified'];
$plan_selected = $user['plan_selected'];
$onboard_done = (bool)($user['onboarding_done'] ?? false);
$id_users = (int)$user['id_users'];

// Saldo
$w = getDB()->prepare('SELECT balance_aoa, balance_usd FROM _wallet WHERE id_users = ?');
$w->execute([$id_users]);
$balance = $w->fetch() ?: ['balance_aoa' => 0, 'balance_usd' => 0];

// Plano
$plan = null;
$plan_paid = false; // true = plano activo e pago
if ($plan_selected) {
    $ps = getDB()->prepare('SELECT * FROM _plans WHERE id_plan = ?');
    $ps->execute([$plan_selected]);
    $plan = $ps->fetch();
}
// Plano considerado pago se status_user = 'active' E plan_activated_at preenchido
$plan_paid = ($user['status_user'] === 'active' && !empty($user['plan_activated_at']));

// Tem artistas?
$as = getDB()->prepare('SELECT COUNT(*) as total FROM _artist WHERE id_users = ?');
$as->execute([$id_users]);
$has_artist = (int)($as->fetch()['total'] ?? 0) > 0;

// Dias desde ultimo login
$ls = getDB()->prepare('SELECT last_login_at FROM _users_security WHERE id_users = ?');
$ls->execute([$id_users]);
$sec = $ls->fetch();
$days_inactive = 0;
if ($sec && $sec['last_login_at']) {
    $days_inactive = (int)floor((time() - strtotime($sec['last_login_at'])) / 86400);
}

// Conta bancaria para saque (tabela _account existente)
$bank_stmt = getDB()->prepare("
SELECT * FROM _account
WHERE id_users = ? AND status_account = 'verified' AND is_default = 1
LIMIT 1
");
$bank_stmt->execute([$id_users]);
$bank_account = $bank_stmt->fetch() ?: null;

// Saldo em AOA (float)
$balance_aoa = (float)($balance['balance_aoa'] ?? 0);
$min_withdrawal = 10000.00; // Minimo de saque: 10.000 Kz
$can_withdraw = $plan_paid && $bank_account && ($balance_aoa >= $min_withdrawal);

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png" />
    <link rel="apple-touch-startup-image" href="../../assets/img/screenshots/splash.png" />
    <link rel="manifest" href="../manifest.json" />
    <title>Finança — Wasom Upfy</title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="../../css/libs/scrollue.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link href="
https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css
" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="../../css/dashboard-style.css" />
    <link rel="stylesheet" href="../../css/lastest-style.css" />
</head>

<body>
    <?php include __DIR__ . '/_modal_withdrawal.php'; ?>
    <!-- Tela de Carregamento -->
    <!-- <div class="loading-screen" id="loadingScreen">
        <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg" class="loading-logo">
            <rect x="2" y="2" width="116" height="36" rx="5" fill="none" stroke="#ff0089" stroke-width="2"/>
            <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold" fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
        </svg>
        <div class="spinner"></div>
    </div> -->

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <!-- Menu Button (Left) -->
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu"
                aria-controls="offcanvasMenu">
                <span class="navbar-toggler-icon"><i class="bi bi-list text-white fs-1"></i></span>
            </button>

            <!-- Logo (Center on Mobile, Left on Desktop) -->
            <a class="navbar-brand" href="../painel">
                <!-- SVG Logo Wasom Upfy -->
                <!-- <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="116" height="36" rx="5" fill="none" stroke="#ff0089" stroke-width="2" />
                    <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold"
                        fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
                </svg> -->
                <span class="text-light" style="
              font-weight: bold;
              box-sizing: border-box;
              text-transform: capitalize;
              font-family: Arial, sans-serif;
            ">WASOM UPFY</span>
            </a>

            <!-- Desktop Menu -->
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav m-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i> Lançamentos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../analytics/statistics"><i class="bi bi-bar-chart"></i>
                            Estatísticas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="overview"><i class="bi bi-currency-dollar"></i> Finanças</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i> Artistas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../artists/youtube/ucy"><i class="bi bi-youtube"></i> Unificação de
                            canal
                            YouTube</a>
                    </li>
                </ul>
            </div>

            <!-- User Icon (Right) -->
            <div class="user-menu d-flex align-items-center">
                <!-- Theme Toggle Button -->
                <a class="theme-toggle text-white me-2" id="themeToggle">
                    <i class="bi bi-sun" id="themeIcon"></i>
                </a>
                <a href="../page/notifications" class="text-white me-2" aria-label="Notificações">
                    <i class="bi bi-bell fs-4"></i>
                    <span class="badge bg-danger">9</span>
                </a>
                <a href="#" class="text-white" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-4"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="../user/profile"><i class="bi bi-person me-2"></i>
                            <strong><?php echo $first_name; ?></strong></a>
                        <div class="text-white-50">
                            &nbsp; &nbsp; &nbsp; &nbsp; (Conta <?php echo str_pad($id_users, 6, "0", STR_PAD_LEFT); ?>)
                        </div>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <a class="dropdown-item" href="../user/profile"><i class="bi bi-person me-2"></i> Meu Perfil</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../account/manage-account"><i class="bi bi-tools me-2"></i>
                            Gestão de
                            Conta</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <a class="dropdown-item" href="../page/settings"><i class="bi bi-gear me-2"></i>
                            Configurações</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../page/notifications"><i class="bi bi-bell me-2"></i>
                            Notificações</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../services/available-services"><i class="bi bi-star me-2"></i>
                            Conta e
                            serviços disponíveis</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#?logout-wasomupfy" data-bs-toggle="modal"
                            data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right me-2"></i>
                            Desconectar-se</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <a class="dropdown-item" href="../page/about"><i class="bi bi-info-circle me-2"></i> Sobre</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../page/support"><i class="bi bi-headset me-2"></i> Enviar pedido
                            de
                            suporte</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../page/faq"><i class="bi bi-chat-left-text me-2"></i> Perguntas
                            frequentes</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../page/help"><i class="bi bi-question-circle me-2"></i>
                            Ajuda</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <span class="dropdown-item-text" id="versionDropdown"></span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Offcanvas Menu para Mobile e Desktop -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasMenuLabel">
                <!-- <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="116" height="36" rx="5" fill="none" stroke="#ff0089" stroke-width="2" />
                    <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold"
                        fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
                </svg> -->
                <span class="text-light" style="
              font-weight: bold;
              box-sizing: border-box;
              text-transform: capitalize;
              font-family: Arial, sans-serif;
            ">WASOM UPFY</span>
            </h5>
            <button type="button" class="btn-close text-white" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i> Lançamentos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../analytics/statistics"><i class="bi bi-bar-chart"></i> Estatísticas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../finances/overview"><i class="bi bi-currency-dollar"></i> Finanças</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i> Artistas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../artists/youtube/ucy"><i class="bi bi-youtube"></i> Unificação de canal
                        YouTube</a>
                </li>
                <!-- Links secundários exibidos apenas em mobile -->
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../user/profile"><i class="bi bi-person-circle"></i> Meu Perfil</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link active" href="../page/settings"><i class="bi bi-gear"></i> Configurações</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../page/notifications"><i class="bi bi-bell"></i> Notificações</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../page/about"><i class="bi bi-info-circle"></i> Sobre</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../services/available-services"><i class="bi bi-star"></i> Conta e
                        serviços
                        disponíveis</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="../page/help"><i class="bi bi-question-circle"></i> Ajuda</a>
                </li>
                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="#?logout-wasomupfy" data-bs-toggle="modal"
                        data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right"></i> Desconectar-se</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Toast para Notificações de Status -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="connectionToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">Conexão</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
            <div class="toast-body">
                Você está offline. Alguns dados podem estar desatualizados.
                <div class="mt-2">
                    <button class="btn btn-pink btn-sm" onclick="tryReconnect()">
                        Tentar Reconectar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-4">

        <?php /* ============================================
    BANNERS DE NOTIFICACAO DO PAINEL
    ============================================ */ ?>

        <?php if (!$email_verified): ?>
        <div class="alert alert-warning alert-dismissible d-flex align-items-center mb-3" role="alert"
            id="banner-email">
            <i class="bi bi-envelope-exclamation-fill fs-5 me-2"></i>
            <div>
                <strong>Email por verificar.</strong>
                O teu email ainda não foi verificado. Acede ao teu perfil e usa o codigo que recebeste para verificar.
                <a href="../account/manage-account" class="alert-link ms-1">Verificar agora &rarr;</a>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!$has_artist): ?>
        <div class="alert alert-info alert-dismissible d-flex align-items-center mb-3" role="alert" id="banner-artist">
            <i class="bi bi-person-plus-fill fs-5 me-2"></i>
            <div>
                <strong>Cria o teu perfil de artista.</strong>
                Para comecar a distribuir música, primeiro precisas de criar um perfil de artista.
                <a href="../artists/add-artist" class="alert-link ms-1">Criar agora &rarr;</a>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($plan && !$plan_paid): ?>
        <!-- Tem plano escolhido mas pagamento pendente -->
        <div class="alert alert-warning alert-dismissible d-flex align-items-center mb-3" role="alert"
            id="banner-plan-pending">
            <i class="bi bi-clock-history fs-5 me-2 flex-shrink-0"></i>
            <div class="flex-grow-1">
                <strong>Pagamento pendente — Plano <?php echo htmlspecialchars($plan['name_plan']); ?>.</strong>
                O plano foi seleccionado mas o pagamento ainda não foi confirmado.
                <a href="../payment/pay" class="alert-link ms-1 fw-bold">Finalizar pagamento &rarr;</a>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php elseif (!$plan): ?>
        <!-- Sem plano nenhum -->
        <div class="alert alert-danger d-flex align-items-center mb-3" role="alert" id="banner-plan">
            <i class="bi bi-credit-card-fill fs-5 me-2 flex-shrink-0"></i>
            <div class="flex-grow-1">
                <strong>Sem plano activo.</strong>
                Escolhe um plano para comecar a distribuir a tua música.
                <a href="../all-plans" class="alert-link ms-1 fw-bold">Ver planos &rarr;</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Header de Finanças -->
        <div class="page-header">
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <div class="page-header-compact">
                        <h1>
                            <i class="bi bi-currency-dollar me-3"></i>
                            Finanças
                        </h1>
                        <p class="lead">
                            Acompanhe todo o histórico de receitas geradas pelos seus streams,
                            saques realizados e saldo disponível para movimentação.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-pink" onclick="window.location='../analytics/report'">
                        <i class="bi bi-file-text"></i> Relatórios
                    </button>
                </div>
            </div>

            <style>
            .page-header::before {
                content: '\F54A';
                /* bi-currency-dollar */
            }
            </style>
        </div>

        <!-- Balance Card -->
        <div class="balance-card mb-4">
            <div class="card">
                <h6 style="color: #ff0089">Saldo disponível para saque</h6>
                <h2 id="balance"><?php echo number_format($balance_aoa, 2, ",", "."); ?> AOA</h2>

                <?php if (!$plan_paid): ?>
                <p class="text-warning small mb-2">
                    <i class="bi bi-lock-fill me-1"></i>
                    Activa o teu plano para começar a receber royalties.
                </p>
                <?php elseif (!$bank_account): ?>
                <p class="text-muted small mb-2">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    Para sacar, primeiro regista uma conta bancária.
                </p>
                <?php elseif ($balance_aoa < $min_withdrawal): ?>
                <p class="text-muted small mb-2">
                    <i class="bi bi-info-circle me-1"></i>
                    Mínimo para saque: <strong>10.000 Kz</strong>
                    (tens <?php echo number_format($balance_aoa, 0, ',', '.'); ?> Kz).
                </p>
                <?php else: ?>
                <p class="small mb-2" style="color:#ccc">
                    Os teus rendimentos estão prontos. Solicita o saque agora.
                </p>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-outline-pink disabled" onclick="setMoeda('AOA')" id="btnAOA"
                        data-bs-toggle="tooltip" data-bs-placement="top" title="Ver em Kwanza">
                        <i class="bi bi-currency-exchange"></i> AOA
                    </button>

                    <?php if (!$bank_account): ?>
                    <!-- Sem conta: leva para criar conta bancária -->
                    <a href="finances/withdraw" class="btn btn-pink">
                        <i class="bi bi-bank me-1"></i> Criar Conta Bancária
                    </a>
                    <?php elseif ($can_withdraw): ?>
                    <!-- Pode sacar -->
                    <button class="btn btn-pink" data-bs-toggle="modal" data-bs-target="#sake">
                        <i class="bi bi-wallet2 me-2"></i> Sacar
                    </button>
                    <?php else: ?>
                    <!-- Saldo insuficiente ou plano inactivo -->
                    <button class="btn btn-pink" disabled title="Saldo mínimo de 10.000 Kz necessário">
                        <i class="bi bi-wallet2 me-2"></i> Sacar
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Launch Card -->
        <div class="launch-card mb-4">
            <div class="card">
                <div class="d-flex align-items-lg-center">
                    <div class="m-auto w-100 text-center">
                        <button style="color: #ff0089; font-weight: bold" class="btn btn-default w-100"
                            onclick="window.location='withdraw'">
                            <h5>
                                <i class="bi bi-credit-card-fill me-3"></i> Contas de Saque
                            </h5>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="launch-card mb-4">
            <div class="card">
                <div class="d-flex align-items-lg-center">
                    <div class="m-auto w-100 text-center">
                        <button style="color: #ff0089; font-weight: bold" class="btn btn-default w-100"
                            onclick="window.location='transactions'">
                            <h5>
                                <i class="bi bi-cash-coin me-3"></i> Divisão de Royalties
                            </h5>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contas de Saque Card -->
        <div class="table-card mb-4">
            <div class="card">
                <div class="card-header">
                    <h6>Contas de Saque</h6>
                </div>
                <!-- Divisão de Royalties Table -->
                <div class="table-responsive">
                    <table id="royaltiesTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Royalties</th>
                                <th>Moeda padrão</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>01-12-2024</td>
                                <td>AOA</td>
                                <td>kz50,000.00</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>01-01-2025</td>
                                <td>AOA</td>
                                <td>kz50,000.00</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>01-02-2025</td>
                                <td>AOA</td>
                                <td>kz100,000.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Histórico de Saques Table -->
                <div class="table-responsive mt-4">
                    <table id="withdrawalsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Saque</th>
                                <th>Conta</th>
                                <th>Moeda padrão</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>01-12-2024</td>
                                <td>Express</td>
                                <td>AOA</td>
                                <td>kz50,000.00</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>01-01-2025</td>
                                <td>IBAN</td>
                                <td>AOA</td>
                                <td>kz50,000.00</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>01-02-2025</td>
                                <td>Express</td>
                                <td>AOA</td>
                                <td>kz100,000.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal para saque contas -->
        <div class="modal fade" id="sake" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="sakeLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-dark" id="sakeLabel">
                            <i class="bi bi-wallet2 me-2 text-pink"></i>Solicitar Saque
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php if ($can_withdraw && $bank_account): ?>

                        <!-- ─ Estado: pode sacar ─ -->
                        <p class="text-muted small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            O valor é processado pela equipa em até 48 horas. Receberás uma notificação por e-mail.
                        </p>
                        <form method="post" action="/withdrawal_process" class="needs-validation row g-3" novalidate
                            id="withdrawal-form">
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <!-- Valor (preenchido automaticamente) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Valor de Saque <span
                                        class="text-muted">(AOA)</span></label>
                                <input type="text" class="form-control" readonly
                                    value="<?php echo number_format($balance_aoa, 2, ',', '.'); ?>">
                                <div class="form-text">Valor total disponível</div>
                            </div>

                            <!-- Conta destino -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Conta Destino</label>
                                <div class="form-control bg-light d-flex align-items-center gap-2"
                                    style="height:auto;padding:.6rem .9rem">
                                    <?php if (in_array($bank_account['type_account'], ['IBAN', 'Multicaixa'])): ?>
                                    <i class="bi bi-bank text-primary"></i>
                                    <div>
                                        <div class="fw-semibold small">
                                            <?php echo htmlspecialchars($bank_account['full_name_account']); ?></div>
                                        <div class="text-muted" style="font-size:.75rem">IBAN ·
                                            <?php echo $bank_account['iban'] ? substr(htmlspecialchars($bank_account['iban']), -8) : 'N/A'; ?>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <i class="bi bi-phone text-success"></i>
                                    <div>
                                        <div class="fw-semibold small">
                                            <?php echo htmlspecialchars($bank_account['full_name_account']); ?></div>
                                        <div class="text-muted" style="font-size:.75rem">Express ·
                                            <?php echo htmlspecialchars($bank_account['tel_account'] ?? 'N/A'); ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Senha de confirmação -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Confirmar com a tua senha <span
                                        class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" required
                                    placeholder="Senha da tua conta Wasom Upfy" autocomplete="current-password">
                                <div class="invalid-feedback">Insere a tua senha para confirmar o saque.</div>
                            </div>

                            <div class="col-12">
                                <div class="alert alert-warning py-2 small mb-0">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Ao confirmar, autorizes o envio de
                                    <strong><?php echo number_format($balance_aoa, 2, ',', '.'); ?> AOA</strong>
                                    para a conta registada. Esta operação não pode ser revertida.
                                </div>
                            </div>

                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-pink">
                                    <i class="bi bi-send me-2"></i>Confirmar Saque
                                </button>
                            </div>
                        </form>

                        <?php else: ?>
                        <!-- ─ Estado: não pode sacar ─ -->
                        <div class="text-center py-4">
                            <i class="bi bi-lock fs-1 text-muted mb-3 d-block"></i>
                            <?php if (!$plan_paid): ?>
                            <h6>Plano não activo</h6>
                            <p class="text-muted small">Activa o teu plano para começar a receber royalties e fazer
                                saques.</p>
                            <a href="payment" class="btn btn-pink btn-sm">Activar Plano</a>
                            <?php elseif (!$bank_account): ?>
                            <h6>Sem conta bancária registada</h6>
                            <p class="text-muted small">Para sacar os teus royalties, primeiro regista uma conta
                                bancária (IBAN ou Multicaixa Express).</p>
                            <a href="finances/withdraw" class="btn btn-pink btn-sm">
                                <i class="bi bi-bank me-1"></i>Registar Conta Bancária
                            </a>
                            <?php else: ?>
                            <h6>Saldo insuficiente</h6>
                            <p class="text-muted small">O mínimo para saque é <strong>10.000 Kz</strong>. O teu saldo
                                actual é <strong><?php echo number_format($balance_aoa, 2, ',', '.'); ?> AOA</strong>.
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
        <!-- Modal para saque contas fim -->
    </div>

    <nav class="bottom-nav d-lg-none">
        <ul class="nav justify-content-around">
            <li class="nav-item">
                <a class="nav-link" href="../painel" aria-label="Ir para Dashboard"><i
                        class="bi bi-speedometer2"></i><span>Dashboard</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../launch/releases" aria-label="Ir para Lançamentos"><i
                        class="bi bi-disc"></i><span>Lançamentos</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../analytics/statistics" aria-label="Ir para Estatísticas"><i
                        class="bi bi-bar-chart"></i><span>Estatísticas</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../finances/overview" aria-label="Ir para Finanças"><i
                        class="bi bi-currency-dollar"></i><span>Finanças</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../artists/artists-list" aria-label="Ir para Artistas"><i
                        class="bi bi-person"></i><span>Artistas</span></a>
            </li>
        </ul>
    </nav>

    <!-- Modal logout -->
    <div class="modal fade" id="logoutwasomupfy" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="logoutwasomupfyLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 text-dark" id="logoutwasomupfyLabel">
                        Terminar sessão
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <div class="row justify-content-center text-center">
                            <div class="col-md-12 content-center justify-center text-center">
                                <p class="text-center text-dark">
                                    @josembengadacosta você tem certeza de que desejas terminar
                                    sessão?
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                            Não, continuar
                        </button>
                    </div>
                    <div>
                        <button class="btn btn-danger" type="button" name="logout_wasomupfy"
                            onclick="logout_wasomupfy()">
                            Sim, terminar
                        </button>
                    </div>
                    <script type="text/javascript">
                    function logout_wasomupfy() {
                        window.location = "../logout";
                    }
                    </script>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal logout fim -->

    <!-- jQuery (necessário para DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/v/bs5/dt-1.13.6/datatables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="../../js/theme.wp.js"></script>
    <script src="../../js/wp.tools.js"></script>
    <script>
    const tooltipTriggerList = document.querySelectorAll(
        '[data-bs-toggle="tooltip"]'
    );
    const tooltipList = [...tooltipTriggerList].map(
        (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
    );
    </script>

    <script>
    // Inicializar DataTables
    $(document).ready(function() {
        $("#royaltiesTable").DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            lengthChange: false,
            pageLength: 5,
            language: {
                search: "Pesquisar royalties recebidos por data:",
                info: "Mostrando _START_ a _END_ de _TOTAL_ entradas",
                paginate: {
                    next: "Próximo",
                    previous: "Anterior",
                },
            },
        });

        $("#withdrawalsTable").DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            lengthChange: false,
            pageLength: 5,
            language: {
                search: "Pesquisar saques por data ou valores:",
                info: "Mostrando _START_ a _END_ de _TOTAL_ saques",
                paginate: {
                    next: "Próximo",
                    previous: "Anterior",
                },
            },
        });
    });
    </script>
</body>

</html>