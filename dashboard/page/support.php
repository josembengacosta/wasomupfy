<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Suporte
// Arquivo: dashboard/page/support.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];
$user     = getUserById($id_users);
if (!$user) {
    session_destroy();
    redirect(APP_URL  . '/' . 'login', ['error' => 'csrf']);
}

$first_name = htmlspecialchars($user['first_name'] ?? '');
$full_name  = htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['second_name'] ?? '')));
$email_user = htmlspecialchars($user['email_user'] ?? '');

// ── Tickets anteriores do utilizador ─────────────────────────
try {
    $tq = $db->prepare("
        SELECT t.id_ticket, t.subject, t.body, t.priority, t.status_ticket, t.creat_ticket,
               (SELECT COUNT(*) FROM _support_reply r WHERE r.id_ticket = t.id_ticket) AS reply_count
        FROM _support_ticket t
        WHERE t.id_users = ?
        ORDER BY t.creat_ticket DESC
        LIMIT 10
    ");
    $tq->execute([$id_users]);
    $tickets = $tq->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tickets = [];
}

$open_count     = count(array_filter($tickets, fn($t) => $t['status_ticket'] === 'open'));
$progress_count = count(array_filter($tickets, fn($t) => $t['status_ticket'] === 'in_progress'));

$status_cfg = [
    'open'        => ['label' => 'Aberto',     'class' => 'bg-warning text-dark'],
    'in_progress' => ['label' => 'Em análise', 'class' => 'bg-primary'],
    'resolved'    => ['label' => 'Resolvido',  'class' => 'bg-success'],
    'closed'      => ['label' => 'Fechado',    'class' => 'bg-secondary'],
];
$priority_cfg = [
    'low'    => ['label' => 'Baixa',   'class' => 'text-success'],
    'medium' => ['label' => 'Média',   'class' => 'text-warning'],
    'high'   => ['label' => 'Alta',    'class' => 'text-danger'],
    'urgent' => ['label' => 'Urgente', 'class' => 'text-danger fw-bold'],
];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png" />
    <link rel="manifest" href="../manifest.json" />
    <title>Suporte — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/dashboard-style.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/lastest-style.css" />
    <style>
        .support-hero {
            background: linear-gradient(135deg, #FF0089 0%, #c8006e 55%, #7b0044 100%);
            border-radius: 20px;
            padding: 2.2rem 2.5rem;
            margin-bottom: 2rem;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .support-hero::after {
            content: '\F348';
            font-family: 'bootstrap-icons';
            position: absolute;
            right: -20px;
            bottom: -28px;
            font-size: 9rem;
            opacity: .07;
        }

        .support-hero .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: 999px;
            padding: 4px 14px;
            font-size: .78rem;
            font-weight: 700;
            backdrop-filter: blur(4px);
            margin-bottom: .8rem;
        }

        .form-card {
            background: var(--card-bg, #fff);
            border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
            border-radius: 18px;
            padding: 1.8rem;
        }

        .form-card .form-control,
        .form-card .form-select {
            border-radius: 10px;
            border: 1.5px solid var(--border-color, rgba(0, 0, 0, .12));
            background: var(--input-bg, #f8f9fa);
            padding: .6rem .95rem;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-card .form-control:focus,
        .form-card .form-select:focus {
            border-color: #FF0089;
            box-shadow: 0 0 0 .2rem rgba(255, 0, 137, .15);
            background: var(--card-bg, #fff);
        }

        .form-card textarea.form-control {
            min-height: 130px;
            resize: vertical;
        }

        .form-card .form-label {
            font-weight: 600;
            font-size: .88rem;
            margin-bottom: .4rem;
        }

        .form-progress-bar {
            height: 5px;
            background: var(--border-color, rgba(0, 0, 0, .08));
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .form-progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #FF0089, #c8006e);
            border-radius: 999px;
            transition: width .3s ease;
        }

        .urgency-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .urgency-option {
            border: 2px solid var(--border-color, rgba(0, 0, 0, .1));
            border-radius: 10px;
            padding: 10px 6px;
            text-align: center;
            cursor: pointer;
            transition: all .15s;
            user-select: none;
            font-size: .78rem;
            font-weight: 600;
        }

        .urgency-option:hover {
            border-color: #FF0089;
        }

        .urgency-option input[type=radio] {
            display: none;
        }

        .urgency-option.sel-low {
            border-color: #198754;
            background: rgba(25, 135, 84, .07);
            color: #198754;
        }

        .urgency-option.sel-medium {
            border-color: #ffc107;
            background: rgba(255, 193, 7, .08);
            color: #856404;
        }

        .urgency-option.sel-high {
            border-color: #dc3545;
            background: rgba(220, 53, 69, .07);
            color: #dc3545;
        }

        .btn-support {
            background: linear-gradient(135deg, #FF0089, #c8006e);
            border: none;
            color: #fff;
            border-radius: 12px;
            font-weight: 700;
            padding: .75rem 2rem;
            width: 100%;
            transition: all .2s;
            font-size: .95rem;
        }

        .btn-support:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(255, 0, 137, .4);
            color: #fff;
        }

        .btn-support:disabled {
            opacity: .65;
        }

        .file-drop {
            border: 2px dashed var(--border-color, rgba(0, 0, 0, .15));
            border-radius: 12px;
            padding: 1.2rem;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
        }

        .file-drop:hover,
        .file-drop.drag-over {
            border-color: #FF0089;
            background: rgba(255, 0, 137, .03);
        }

        .file-drop input[type=file] {
            display: none;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--metric-bg, rgba(0, 0, 0, .03));
            border-radius: 8px;
            padding: 5px 10px;
            font-size: .78rem;
            margin-top: 5px;
        }

        .result-banner {
            border-radius: 12px;
            padding: .85rem 1.1rem;
            font-size: .86rem;
            display: none;
            align-items: center;
            gap: 10px;
        }

        .result-banner.show {
            display: flex;
        }

        .result-banner.ok {
            background: rgba(25, 135, 84, .1);
            border: 1.5px solid #198754;
            color: #0a5c36;
        }

        .result-banner.err {
            background: rgba(220, 53, 69, .08);
            border: 1.5px solid #dc3545;
            color: #842029;
        }

        .ticket-row {
            padding: 11px 0;
            border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .07));
            font-size: .83rem;
        }

        .ticket-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .sec-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #FF0089;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1.2rem;
        }

        @media(max-width:768px) {
            .support-hero {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>

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
                <span class="text-light"
                    style="font-weight: bold; box-sizing: border-box; text-transform: uppercase; font-family:Arial, sans-serif">WASOM
                    UPFY</span>
            </a>


            <!-- Desktop Menu -->
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav m-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i>
                            Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i>
                            Lançamentos</a></li>
                    <li class="nav-item"><a class="nav-link" href="../analytics/statistics"><i
                                class="bi bi-bar-chart"></i>
                            Estatísticas</a></li>
                    <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
                                class="bi bi-currency-dollar"></i>
                            Finanças</a></li>
                    <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i>
                            Artistas</a></li>
                    <li class="nav-item"><a class="nav-link" href="../artists/youtube/ucy"><i class="bi bi-youtube"></i>
                            Unificação de canal YouTube</a></li>
                </ul>
            </div>

            <!-- User Icon (Right) -->
            <div class="user-menu d-flex align-items-center">
                <!-- Theme Toggle Button -->
                <a class="theme-toggle text-white me-2" id="themeToggle">
                    <i class="bi bi-sun" id="themeIcon"></i>
                </a>
                <a href="../notifications" class="text-white me-2" aria-label="Notificações">
                    <i class="bi bi-bell fs-4"></i>
                    <span class="badge bg-danger">9</span>
                </a>
                <a href="#" class="text-white" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-4"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="../user/profile"><i class="bi bi-person me-2"></i>
                            <strong>Eleven
                                Records</strong></a>
                        <div class="text-white-50"> &nbsp; &nbsp; &nbsp; &nbsp;
                            (Conta <?php echo str_pad($id_users, 6, "0", STR_PAD_LEFT); ?>)</div>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../user/profile"><i class="bi bi-person me-2"></i> Meu
                            Perfil</a>
                    </li>
                    <li><a class="dropdown-item" href="../account/manage-account"><i class="bi bi-tools me-2"></i>
                            Gestão de Conta</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../page/settings"><i class="bi bi-gear me-2"></i>
                            Configurações</a></li>
                    <li><a class="dropdown-item" href="../page/notifications"><i class="bi bi-bell me-2"></i>
                            Notificações</a></li>
                    <li><a class="dropdown-item" href="../services/available-services"><i class="bi bi-star me-2"></i>
                            Conta e serviços disponíveis</a></li>
                    <li><a class="dropdown-item" href="#?logout-wasomupfy" data-bs-toggle="modal"
                            data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right me-2"></i>
                            Desconectar-se</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../page/about"><i class="bi bi-info-circle me-2"></i>
                            Sobre</a>
                    </li>
                    <li><a class="dropdown-item" href="../page/support"><i class="bi bi-headset me-2"></i> Enviar
                            pedido de suporte</a></li>
                    <li><a class="dropdown-item" href="../page/faq"><i class="bi bi-chat-left-text me-2"></i>
                            Perguntas frequentes</a></li>
                    <li><a class="dropdown-item" href="../page/help"><i class="bi bi-question-circle me-2"></i>
                            Ajuda</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><span class="dropdown-item-text" id="versionDropdown"></span></li>
                </ul>
            </div>
        </div>
    </nav>


    <!-- Offcanvas Menu par Mobile e Desktop -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasMenuLabel">
                <!-- <svg width="120" height="40" viewBox="0 0 120 40" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="116" height="36" rx="5" fill="none" stroke="#ff0089" stroke-width="2" />
                    <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="20" font-weight="bold"
                        fill="#ff0089" text-anchor="middle" dominant-baseline="middle">WASOM UPFY</text>
                </svg> -->
                <span class="text-light"
                    style="font-weight: bold; box-sizing: border-box; text-transform: uppercase; font-family:Arial, sans-serif">WASOM
                    UPFY</span>
            </h5>
            <button type="button" class="btn-close text-white" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i>
                        Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i>
                        Lançamentos</a></li>
                <li class="nav-item"><a class="nav-link" href="../analytics/statistics"><i class="bi bi-bar-chart"></i>
                        Estatísticas</a></li>
                <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
                            class="bi bi-currency-dollar"></i> Finanças</a></li>
                <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i>
                        Artistas</a></li>
                <li class="nav-item"><a class="nav-link" href="../artists/youtube/ucy"><i class="bi bi-youtube"></i>
                        Unificação
                        de
                        canal YouTube</a></li>
                <!-- Links secundários exibidos apenas em mobile -->
                <li class="nav-item d-lg-none"><a class="nav-link" href="../user/profile"><i
                            class="bi bi-person-circle"></i> Meu Perfil</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link active" href="../page/settings"><i
                            class="bi bi-gear"></i> Configurações</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link" href="../page/notifications"><i
                            class="bi bi-bell"></i> Notificações</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link" href="../page/about"><i
                            class="bi bi-info-circle"></i> Sobre</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link" href="../services/available-services"><i
                            class="bi bi-star"></i>
                        Conta e serviços disponíveis</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link" href="../page/help"><i
                            class="bi bi-question-circle"></i> Ajuda</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link" href="#?logout-wasomupfy" data-bs-toggle="modal"
                        data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right"></i> Desconectar-se</a></li>
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
                    <button class="btn btn-pink btn-sm" onclick="tryReconnect()">Tentar Reconectar</button>
                </div>
            </div>
        </div>
    </div>


    <!-- MAIN -->
    <main class="container my-4">

        <!-- HERO -->
        <div class="support-hero">
            <div class="hero-badge">
                <i class="bi bi-headset"></i>
                <?php if ($open_count + $progress_count > 0): ?>
                    <?php echo $open_count + $progress_count; ?>
                    ticket<?php echo ($open_count + $progress_count) > 1 ? 's' : ''; ?>
                    activo<?php echo ($open_count + $progress_count) > 1 ? 's' : ''; ?>
                <?php else: ?>
                    Nenhum ticket em aberto
                <?php endif; ?>
            </div>
            <h1 class="fw-bold mb-1"><i class="bi bi-headset-vr me-2"></i>Suporte Wasom Upfy</h1>
            <p class="lead mb-0" style="opacity:.85">
                Descreve o teu problema e a nossa equipa responde em até 48 horas.
                Antes, consulta o <a href="faq" class="text-white fw-semibold">FAQ</a> — talvez já tenhamos a resposta.
            </p>
        </div>

        <div class="row g-4">

            <!-- FORMULÁRIO -->
            <div class="col-lg-8">
                <div class="sec-title"><i class="bi bi-send"></i>Novo pedido</div>

                <div id="resultBanner" class="result-banner mb-3" role="alert"></div>

                <div class="form-card">
                    <!-- Progresso -->
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Progresso do formulário</small>
                        <small class="text-muted" id="progressPct">0%</small>
                    </div>
                    <div class="form-progress-bar">
                        <div class="form-progress-fill" id="progressFill"></div>
                    </div>

                    <!-- NOTA: sem method/action — o submit é 100% via fetch().
                     O onsubmit="return false" é um segundo travão caso o JS falhe. -->
                    <form id="supportForm" novalidate onsubmit="return false;">
                        <input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />

                        <!-- Tipo de problema -->
                        <div class="mb-3">
                            <label for="issueType" class="form-label text-muted">Tipo de Problema <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="issueType" name="issueType" required>
                                <option value="" disabled selected>Selecciona uma opção</option>
                                <option value="login">Problema com login ou senha</option>
                                <option value="plan">Alterar ou questão sobre o plano</option>
                                <option value="payment">Problema com pagamento</option>
                                <option value="stats">Erro nas estatísticas</option>
                                <option value="upload">Falha ao enviar ficheiros</option>
                                <option value="royalty">Questão sobre royalties</option>
                                <option value="refund">Pedido de reembolso</option>
                                <option value="account">Conta suspensa ou bloqueada</option>
                                <option value="other">Outro assunto</option>
                            </select>
                            <div class="invalid-feedback">Selecciona o tipo de problema.</div>
                        </div>

                        <!-- Urgência -->
                        <div class="mb-3">
                            <label class="form-label text-muted">Urgência <span class="text-danger">*</span></label>
                            <div class="urgency-grid" id="urgencyGrid">
                                <label class="urgency-option" id="urgLow">
                                    <input type="radio" name="urgency" value="low" />
                                    <i class="bi bi-arrow-down-circle d-block mb-1"
                                        style="font-size:1.2rem;color:#198754"></i>
                                    Baixa
                                </label>
                                <label class="urgency-option" id="urgMedium">
                                    <input type="radio" name="urgency" value="medium" />
                                    <i class="bi bi-dash-circle d-block mb-1"
                                        style="font-size:1.2rem;color:#ffc107"></i>
                                    Média
                                </label>
                                <label class="urgency-option" id="urgHigh">
                                    <input type="radio" name="urgency" value="high" />
                                    <i class="bi bi-exclamation-circle d-block mb-1"
                                        style="font-size:1.2rem;color:#dc3545"></i>
                                    Alta
                                </label>
                            </div>
                            <div id="urgencyError" class="text-danger mt-1" style="font-size:.78rem;display:none">
                                <i class="bi bi-exclamation-triangle me-1"></i>Selecciona o nível de urgência.
                            </div>
                        </div>

                        <!-- Descrição -->
                        <div class="mb-3">
                            <label for="description" class="form-label text-muted">Descrição do Problema <span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description"
                                placeholder="Descreve o problema com o máximo de detalhe..." required></textarea>
                            <div class="d-flex justify-content-end mt-1">
                                <small class="text-muted"><span id="charCount">0</span>/3000</small>
                            </div>
                            <div class="invalid-feedback">A descrição deve ter pelo menos 10 caracteres.</div>
                        </div>

                        <!-- Anexos -->
                        <div class="mb-4">
                            <label class="form-label text-muted">
                                Anexos <small class="fw-normal">(opcional — máx. 5 ficheiros de 10 MB)</small>
                            </label>
                            <div class="file-drop" id="dropArea">
                                <i class="bi bi-cloud-upload" style="font-size:1.7rem;color:#FF0089;opacity:.6"></i>
                                <div class="mt-1" style="font-size:.82rem;color:var(--text-muted,#6c757d)">
                                    Clica ou arrasta ficheiros aqui<br>
                                    <small>JPG, PNG, PDF, ZIP, MP4 — máx. 10 MB cada</small>
                                </div>
                                <input type="file" id="fileInput" multiple
                                    accept=".jpg,.jpeg,.png,.gif,.pdf,.txt,.zip,.mp4,.mov" />
                            </div>
                            <div id="fileList"></div>
                        </div>

                        <!-- Identidade -->
                        <div class="d-flex align-items-center gap-2 mb-4 p-3"
                            style="background:var(--metric-bg,rgba(0,0,0,.03));border-radius:10px;font-size:.82rem;color:var(--text-muted,#6c757d)">
                            <i class="bi bi-person-check-fill" style="color:#FF0089;font-size:1.1rem"></i>
                            <div>
                                A enviar como <strong><?php echo $full_name; ?></strong>
                                &nbsp;·&nbsp; <?php echo $email_user; ?>
                            </div>
                        </div>

                        <button type="button" class="btn-support btn" id="submitBtn">
                            <i class="bi bi-send me-2"></i>Enviar Pedido de Suporte
                        </button>
                    </form>
                </div>
            </div>

            <!-- SIDEBAR -->
            <div class="col-lg-4">

                <!-- Tickets -->
                <div class="card mb-3"
                    style="border-radius:16px;border:1.5px solid var(--border-color,rgba(0,0,0,.08))">
                    <div class="card-header"
                        style="background:transparent;border-bottom:1px solid var(--border-color,rgba(0,0,0,.07));padding:1rem 1.3rem;border-radius:16px 16px 0 0">
                        <h5 class="mb-0 fw-bold" style="font-size:.95rem">
                            <i class="bi bi-clock-history me-2" style="color:#FF0089"></i>Os teus tickets
                            <?php if (!empty($tickets)): ?>
                                <span class="badge ms-1"
                                    style="background:#FF0089;font-size:.65rem"><?php echo count($tickets); ?></span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body py-2 px-3">
                        <?php if (empty($tickets)): ?>
                            <p class="text-muted small py-2 mb-0">Ainda não enviaste nenhum pedido.</p>
                        <?php else: ?>
                            <?php foreach ($tickets as $t):
                                $st = $status_cfg[$t['status_ticket']] ?? ['label' => ucfirst($t['status_ticket']), 'class' => 'bg-secondary'];
                                $pr = $priority_cfg[$t['priority']]   ?? ['label' => ucfirst($t['priority']), 'class' => 'text-muted'];
                                $preview = preg_replace('/^\[.*?\]\n\n/', '', $t['body'] ?? '');
                                $preview = mb_strimwidth($preview, 0, 65, '…');
                            ?>
                                <div class="ticket-row">
                                    <div class="d-flex align-items-center flex-wrap gap-1 mb-1">
                                        <span style="font-size:.68rem;font-family:monospace;color:var(--text-muted,#6c757d)">
                                            #<?php echo str_pad($t['id_ticket'], 5, '0', STR_PAD_LEFT); ?>
                                        </span>
                                        <span class="badge <?php echo $st['class']; ?>"
                                            style="font-size:.65rem"><?php echo $st['label']; ?></span>
                                        <span class="<?php echo $pr['class']; ?>" style="font-size:.7rem">·
                                            <?php echo $pr['label']; ?></span>
                                        <?php if ($t['reply_count'] > 0): ?>
                                            <span class="text-muted" style="font-size:.68rem">
                                                <i class="bi bi-chat-left me-1"></i><?php echo $t['reply_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="fw-semibold"
                                        style="font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        <?php echo htmlspecialchars($t['subject']); ?>
                                    </div>
                                    <div class="text-muted" style="font-size:.72rem"><?php echo htmlspecialchars($preview); ?>
                                    </div>
                                    <div style="font-size:.68rem;color:var(--text-muted,#6c757d);margin-top:2px">
                                        <i
                                            class="bi bi-calendar3 me-1"></i><?php echo date('d/m/Y H:i', strtotime($t['creat_ticket'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info -->
                <div class="card mb-3"
                    style="border-radius:16px;border:1.5px solid var(--border-color,rgba(0,0,0,.08))">
                    <div class="card-header"
                        style="background:transparent;border-bottom:1px solid var(--border-color,rgba(0,0,0,.07));padding:1rem 1.3rem;border-radius:16px 16px 0 0">
                        <h5 class="mb-0 fw-bold" style="font-size:.95rem"><i class="bi bi-info-circle me-2"
                                style="color:#FF0089"></i>Informações</h5>
                    </div>
                    <div class="card-body" style="padding:1.2rem">
                        <div class="d-flex gap-3 mb-3 align-items-start">
                            <i class="bi bi-clock" style="color:#FF0089;font-size:1.1rem;margin-top:2px"></i>
                            <div style="font-size:.82rem"><strong>Tempo de resposta</strong><br><span
                                    class="text-muted">Até 48 horas úteis</span></div>
                        </div>
                        <div class="d-flex gap-3 mb-3 align-items-start">
                            <i class="bi bi-envelope" style="color:#FF0089;font-size:1.1rem;margin-top:2px"></i>
                            <div style="font-size:.82rem"><strong>Resposta por e-mail</strong><br><span
                                    class="text-muted"><?php echo $email_user; ?></span></div>
                        </div>
                        <div class="d-flex gap-3 align-items-start">
                            <i class="bi bi-shield-check" style="color:#FF0089;font-size:1.1rem;margin-top:2px"></i>
                            <div style="font-size:.82rem"><strong>Máx. 5 pedidos / hora</strong><br><span
                                    class="text-muted">Para garantir qualidade de resposta</span></div>
                        </div>
                    </div>
                </div>

                <!-- FAQ -->
                <div class="card" style="border-radius:16px;border:1.5px solid var(--border-color,rgba(0,0,0,.08))">
                    <div class="card-body text-center" style="padding:1.4rem">
                        <i class="bi bi-chat-left-text-fill" style="font-size:2rem;color:#FF0089"></i>
                        <h6 class="fw-bold mt-2 mb-1">Consulta o FAQ primeiro</h6>
                        <p class="text-muted small mb-3">Muitas questões frequentes já têm resposta na nossa base de
                            conhecimento.</p>
                        <a href="faq" class="btn btn-sm btn-outline-secondary w-100"
                            style="border-radius:9px;font-weight:600">
                            <i class="bi bi-chat-left-text me-1"></i>Ver perguntas frequentes
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Bottom Nav Mobile -->
    <nav class="bottom-nav d-lg-none">
        <ul class="nav justify-content-around">
            <li class="nav-item"><a class="nav-link" href="../painel"><i
                        class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../launch/releases"><i
                        class="bi bi-disc"></i><span>Lançamentos</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../analytics/statistics"><i
                        class="bi bi-bar-chart"></i><span>Stats</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
                        class="bi bi-currency-dollar"></i><span>Finanças</span></a></li>
            <li class="nav-item"><a class="nav-link active" href="support"><i
                        class="bi bi-headset"></i><span>Suporte</span></a></li>
        </ul>
    </nav>

    <!-- Modal Logout -->
    <div class="modal fade" id="logoutwasomupfy" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">Terminar sessão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center text-dark">
                    <p>Tens a certeza de que desejas terminar sessão, <strong><?php echo $first_name; ?></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Não, continuar</button>
                    <a href="../logout" class="btn btn-danger">Sim, terminar sessão</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- theme.wp.js já trata o tema — não declarar themeToggle/themeIcon aqui -->
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <script>
        // ══════════════════════════════════════════════════════════════
        // TODO o código da página fica DENTRO do DOMContentLoaded.
        // Isto garante:
        //   1. DOM já existe quando tentamos aceder aos elementos
        //   2. Não há conflito com variáveis de theme.wp.js / wp.tools.js
        //      porque usamos um scope isolado (função anónima)
        // ══════════════════════════════════════════════════════════════
        document.addEventListener('DOMContentLoaded', function() {

            // ── Urgência ───────────────────────────────────────────
            let selectedUrgency = '';

            function setUrgency(val) {
                selectedUrgency = val;
                ['Low', 'Medium', 'High'].forEach(function(u) {
                    var el = document.getElementById('urg' + u);
                    if (el) el.className = 'urgency-option';
                });
                var active = document.getElementById('urg' + val.charAt(0).toUpperCase() + val.slice(1));
                if (active) active.classList.add('sel-' + val);
                var radio = document.querySelector('input[name="urgency"][value="' + val + '"]');
                if (radio) radio.checked = true;
                var err = document.getElementById('urgencyError');
                if (err) err.style.display = 'none';
                updateProgress();
            }

            // Clique nos cards de urgência
            ['Low', 'Medium', 'High'].forEach(function(u) {
                var card = document.getElementById('urg' + u);
                if (card) {
                    card.addEventListener('click', function() {
                        setUrgency(u.toLowerCase());
                    });
                }
            });

            // ── Contador de caracteres ──────────────────────────────
            var descEl = document.getElementById('description');
            var charCount = document.getElementById('charCount');
            if (descEl && charCount) {
                descEl.addEventListener('input', function() {
                    charCount.textContent = this.value.length;
                    updateProgress();
                });
            }

            // ── Progresso ───────────────────────────────────────────
            function updateProgress() {
                var issueVal = document.getElementById('issueType') ? document.getElementById('issueType').value :
                    '';
                var descVal = descEl ? descEl.value.trim() : '';
                var fields = [
                    issueVal !== '',
                    selectedUrgency !== '',
                    descVal.length >= 10
                ];
                var pct = Math.round(fields.filter(Boolean).length / fields.length * 100);
                var fill = document.getElementById('progressFill');
                var pctEl = document.getElementById('progressPct');
                if (fill) fill.style.width = pct + '%';
                if (pctEl) pctEl.textContent = pct + '%';
            }

            var issueTypeEl = document.getElementById('issueType');
            if (issueTypeEl) {
                issueTypeEl.addEventListener('change', updateProgress);
            }

            // ── Upload de ficheiros ─────────────────────────────────
            var selectedFiles = [];
            var dropArea = document.getElementById('dropArea');
            var fileInput = document.getElementById('fileInput');
            var fileList = document.getElementById('fileList');
            var MAX_FILES = 5;
            var MAX_SIZE = 10 * 1024 * 1024;
            var ALLOWED = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'zip', 'mp4', 'mov'];

            // Clique na área de drop abre o file picker
            if (dropArea && fileInput) {
                dropArea.addEventListener('click', function(e) {
                    if (e.target !== fileInput) fileInput.click();
                });

                dropArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    dropArea.classList.add('drag-over');
                });
                dropArea.addEventListener('dragleave', function() {
                    dropArea.classList.remove('drag-over');
                });
                dropArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    dropArea.classList.remove('drag-over');
                    var dt = new DataTransfer();
                    Array.from(e.dataTransfer.files || []).forEach(function(f) {
                        dt.items.add(f);
                    });
                    fileInput.files = dt.files;
                    processFiles();
                });

                fileInput.addEventListener('change', processFiles);
            }

            function processFiles() {
                Array.from(fileInput.files).forEach(function(f) {
                    if (selectedFiles.length >= MAX_FILES) return;
                    var ext = f.name.split('.').pop().toLowerCase();
                    if (!ALLOWED.includes(ext) || f.size > MAX_SIZE) return;
                    if (!selectedFiles.find(function(x) {
                            return x.name === f.name && x.size === f.size;
                        })) {
                        selectedFiles.push(f);
                    }
                });
                renderFiles();
            }

            function renderFiles() {
                if (!fileList) return;
                fileList.innerHTML = '';
                selectedFiles.forEach(function(f, i) {
                    var d = document.createElement('div');
                    d.className = 'file-item';
                    d.innerHTML = '<span><i class="bi bi-paperclip me-1"></i>' + f.name +
                        ' <small class="text-muted">(' + (f.size / 1024).toFixed(0) +
                        ' KB)</small></span>' +
                        '<button type="button" class="btn btn-sm text-danger p-0 ms-2" data-idx="' + i +
                        '">' +
                        '<i class="bi bi-x-lg"></i></button>';
                    d.querySelector('button').addEventListener('click', function() {
                        selectedFiles.splice(parseInt(this.dataset.idx), 1);
                        renderFiles();
                    });
                    fileList.appendChild(d);
                });
            }

            // ── Submit via fetch ────────────────────────────────────
            var submitBtn = document.getElementById('submitBtn');
            var banner = document.getElementById('resultBanner');

            if (submitBtn) {
                submitBtn.addEventListener('click', async function() {

                    var issueType = issueTypeEl ? issueTypeEl.value : '';
                    var description = descEl ? descEl.value.trim() : '';
                    var csrfToken = document.querySelector('[name="csrf_token"]');
                    csrfToken = csrfToken ? csrfToken.value : '';

                    // Validação
                    var valid = true;

                    if (!issueType) {
                        if (issueTypeEl) issueTypeEl.classList.add('is-invalid');
                        valid = false;
                    } else {
                        if (issueTypeEl) issueTypeEl.classList.remove('is-invalid');
                    }

                    if (!selectedUrgency) {
                        var urgErr = document.getElementById('urgencyError');
                        if (urgErr) urgErr.style.display = 'block';
                        valid = false;
                    }

                    if (description.length < 10) {
                        if (descEl) descEl.classList.add('is-invalid');
                        valid = false;
                    } else {
                        if (descEl) descEl.classList.remove('is-invalid');
                    }

                    if (!valid) return;

                    // FormData
                    var fd = new FormData();
                    fd.append('csrf_token', csrfToken);
                    fd.append('issueType', issueType);
                    fd.append('urgency', selectedUrgency);
                    fd.append('description', description);
                    selectedFiles.forEach(function(f) {
                        fd.append('attachment[]', f);
                    });

                    // UI loading
                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2"></span>A enviar…';
                    if (banner) banner.className = 'result-banner mb-3';

                    try {
                        var res = await fetch('../ajax/support_dashboard', {
                            method: 'POST',
                            body: fd,
                            credentials: 'same-origin'
                        });
                        var data = await res.json();

                        if (banner) {
                            banner.className = 'result-banner mb-3 show ' + (data.ok ? 'ok' : 'err');
                            banner.innerHTML = '<i class="bi bi-' + (data.ok ? 'check-circle-fill' :
                                    'exclamation-triangle-fill') +
                                ' fs-5 flex-shrink-0"></i><span>' + data.message + '</span>';
                        }

                        if (data.ok) {
                            // Limpar form
                            if (issueTypeEl) {
                                issueTypeEl.value = '';
                                issueTypeEl.classList.remove('is-invalid');
                            }
                            if (descEl) {
                                descEl.value = '';
                                descEl.classList.remove('is-invalid');
                            }
                            if (charCount) charCount.textContent = '0';
                            selectedUrgency = '';
                            ['Low', 'Medium', 'High'].forEach(function(u) {
                                var el = document.getElementById('urg' + u);
                                if (el) el.className = 'urgency-option';
                            });
                            selectedFiles = [];
                            renderFiles();
                            updateProgress();
                            if (banner) banner.scrollIntoView({
                                behavior: 'smooth',
                                block: 'nearest'
                            });
                            // Actualiza lista após 8s
                            setTimeout(function() {
                                location.reload();
                            }, 8000);
                        }
                    } catch (err) {
                        if (banner) {
                            banner.className = 'result-banner mb-3 show err';
                            banner.innerHTML =
                                '<i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>' +
                                '<span>Erro de rede. Verifica a ligação e tenta novamente.</span>';
                        }
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-send me-2"></i>Enviar Pedido de Suporte';
                    }
                });
            }

        }); // fim DOMContentLoaded
    </script>
</body>

</html>