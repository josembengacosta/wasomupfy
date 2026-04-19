<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Adicionar Utilizador
// Arquivo: admin/pages/users/add.php
// Rota: admin/users/add
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'users.edit');

// Feedback de erro de validação (volta do process)
$err = $_GET['err'] ?? null;
$errors = match ($err) {
    'email_exists'    => ['danger', 'Este e-mail já está registado em outra conta.'],
    'user_exists'     => ['danger', 'Este nome de utilizador já está em uso.'],
    'invalid'         => ['danger', 'Dados inválidos. Verifica os campos obrigatórios.'],
    'password_weak'   => ['danger', 'A senha é muito fraca. Usa pelo menos 8 caracteres, maiúsculas, minúsculas e números.'],
    'email_fail'      => ['warning', 'Conta criada, mas o e-mail de confirmação falhou.'],
    'error'           => ['danger', 'Ocorreu um erro ao criar a conta. Tenta novamente.'],
    default           => null,
};

// ── Buscar planos disponíveis ──
$plans = $db->query("SELECT id_plan, name_plan, slug_plan, price_plan FROM _plans WHERE is_active = 1 ORDER BY price_plan")->fetchAll();

// ── Lista de países (completa) ──
$paises = [
    "AF" => "Afeganistão",
    "ZA" => "África do Sul",
    "AL" => "Albânia",
    "DE" => "Alemanha",
    "AD" => "Andorra",
    "AO" => "Angola",
    "AG" => "Antígua e Barbuda",
    "SA" => "Arábia Saudita",
    "DZ" => "Argélia",
    "AR" => "Argentina",
    "AM" => "Arménia",
    "AU" => "Austrália",
    "AT" => "Áustria",
    "AZ" => "Azerbaijão",
    "BS" => "Bahamas",
    "BH" => "Bahrein",
    "BD" => "Bangladesh",
    "BB" => "Barbados",
    "BE" => "Bélgica",
    "BZ" => "Belize",
    "BJ" => "Benim",
    "BY" => "Bielorrússia",
    "BO" => "Bolívia",
    "BA" => "Bósnia e Herzegovina",
    "BW" => "Botsuana",
    "BR" => "Brasil",
    "BN" => "Brunei",
    "BG" => "Bulgária",
    "BF" => "Burkina Faso",
    "BI" => "Burundi",
    "BT" => "Butão",
    "CV" => "Cabo Verde",
    "CM" => "Camarões",
    "KH" => "Camboja",
    "CA" => "Canadá",
    "QA" => "Catar",
    "KZ" => "Cazaquistão",
    "TD" => "Chade",
    "CL" => "Chile",
    "CN" => "China",
    "CY" => "Chipre",
    "CO" => "Colômbia",
    "KM" => "Comores",
    "CG" => "Congo",
    "CD" => "Congo (República Democrática)",
    "KP" => "Coreia do Norte",
    "KR" => "Coreia do Sul",
    "CI" => "Costa do Marfim",
    "CR" => "Costa Rica",
    "HR" => "Croácia",
    "CU" => "Cuba",
    "DK" => "Dinamarca",
    "DJ" => "Djibouti",
    "DM" => "Dominica",
    "EG" => "Egito",
    "SV" => "El Salvador",
    "AE" => "Emirados Árabes Unidos",
    "EC" => "Equador",
    "ER" => "Eritreia",
    "SK" => "Eslováquia",
    "SI" => "Eslovénia",
    "ES" => "Espanha",
    "US" => "Estados Unidos",
    "EE" => "Estónia",
    "SZ" => "Eswatini",
    "ET" => "Etiópia",
    "FJ" => "Fiji",
    "PH" => "Filipinas",
    "FI" => "Finlândia",
    "FR" => "França",
    "GA" => "Gabão",
    "GM" => "Gâmbia",
    "GH" => "Gana",
    "GE" => "Geórgia",
    "GD" => "Granada",
    "GR" => "Grécia",
    "GL" => "Gronelândia",
    "GP" => "Guadalupe",
    "GU" => "Guam",
    "GT" => "Guatemala",
    "GG" => "Guernsey",
    "GY" => "Guiana",
    "GN" => "Guiné",
    "GQ" => "Guiné Equatorial",
    "GW" => "Guiné-Bissau",
    "HT" => "Haiti",
    "HN" => "Honduras",
    "HK" => "Hong Kong",
    "HU" => "Hungria",
    "YE" => "Iémen",
    "IN" => "Índia",
    "ID" => "Indonésia",
    "IR" => "Irão",
    "IQ" => "Iraque",
    "IE" => "Irlanda",
    "IS" => "Islândia",
    "IL" => "Israel",
    "IT" => "Itália",
    "JM" => "Jamaica",
    "JP" => "Japão",
    "JE" => "Jersey",
    "JO" => "Jordânia",
    "KW" => "Kuwait",
    "LA" => "Laos",
    "LS" => "Lesoto",
    "LV" => "Letónia",
    "LB" => "Líbano",
    "LR" => "Libéria",
    "LY" => "Líbia",
    "LI" => "Liechtenstein",
    "LT" => "Lituânia",
    "LU" => "Luxemburgo",
    "MO" => "Macau",
    "MK" => "Macedónia do Norte",
    "MG" => "Madagáscar",
    "MY" => "Malásia",
    "MW" => "Malawi",
    "MV" => "Maldivas",
    "ML" => "Mali",
    "MT" => "Malta",
    "MA" => "Marrocos",
    "MQ" => "Martinica",
    "MU" => "Maurícia",
    "MR" => "Mauritânia",
    "YT" => "Mayotte",
    "MX" => "México",
    "MM" => "Myanmar",
    "FM" => "Micronésia",
    "MZ" => "Moçambique",
    "MD" => "Moldávia",
    "MC" => "Mónaco",
    "MN" => "Mongólia",
    "ME" => "Montenegro",
    "MS" => "Montserrat",
    "NA" => "Namíbia",
    "NR" => "Nauru",
    "NP" => "Nepal",
    "NI" => "Nicarágua",
    "NE" => "Níger",
    "NG" => "Nigéria",
    "NU" => "Niue",
    "NO" => "Noruega",
    "NC" => "Nova Caledónia",
    "NZ" => "Nova Zelândia",
    "OM" => "Omã",
    "NL" => "Países Baixos",
    "PW" => "Palau",
    "PS" => "Palestina",
    "PA" => "Panamá",
    "PG" => "Papua-Nova Guiné",
    "PK" => "Paquistão",
    "PY" => "Paraguai",
    "PE" => "Peru",
    "PF" => "Polinésia Francesa",
    "PL" => "Polónia",
    "PR" => "Porto Rico",
    "PT" => "Portugal",
    "KE" => "Quénia",
    "KG" => "Quirguistão",
    "KI" => "Quiribati",
    "GB" => "Reino Unido",
    "CF" => "República Centro-Africana",
    "CZ" => "República Checa",
    "DO" => "República Dominicana",
    "RE" => "Reunião",
    "RO" => "Roménia",
    "RW" => "Ruanda",
    "RU" => "Rússia",
    "EH" => "Saara Ocidental",
    "WS" => "Samoa",
    "AS" => "Samoa Americana",
    "SM" => "São Marinho",
    "ST" => "São Tomé e Príncipe",
    "VC" => "São Vicente e Granadinas",
    "LC" => "Santa Lúcia",
    "SC" => "Seicheles",
    "SN" => "Senegal",
    "SL" => "Serra Leoa",
    "RS" => "Sérvia",
    "SG" => "Singapura",
    "SY" => "Síria",
    "SO" => "Somália",
    "LK" => "Sri Lanka",
    "SD" => "Sudão",
    "SS" => "Sudão do Sul",
    "SE" => "Suécia",
    "CH" => "Suíça",
    "SR" => "Suriname",
    "TH" => "Tailândia",
    "TW" => "Taiwan",
    "TJ" => "Tajiquistão",
    "TZ" => "Tanzânia",
    "TL" => "Timor-Leste",
    "TG" => "Togo",
    "TO" => "Tonga",
    "TT" => "Trindade e Tobago",
    "TN" => "Tunísia",
    "TM" => "Turquemenistão",
    "TR" => "Turquia",
    "TV" => "Tuvalu",
    "UA" => "Ucrânia",
    "UG" => "Uganda",
    "UY" => "Uruguai",
    "UZ" => "Usbequistão",
    "VU" => "Vanuatu",
    "VA" => "Vaticano",
    "VE" => "Venezuela",
    "VN" => "Vietname",
    "WF" => "Wallis e Futuna",
    "ZM" => "Zâmbia",
    "ZW" => "Zimbabué"
];

// Status options para utilizadores
$status_options = [
    'active' => ['label' => 'Activo', 'icon' => 'bi-check-circle-fill', 'color' => '#22c55e'],
    'suspended' => ['label' => 'Suspenso', 'icon' => 'bi-exclamation-triangle-fill', 'color' => '#ef4444'],
    'blocked' => ['label' => 'Bloqueado', 'icon' => 'bi-lock-fill', 'color' => '#6b7280'],
    'inactive' => ['label' => 'Inactivo', 'icon' => 'bi-person-slash', 'color' => '#3b82f6'],
    'pending_plan' => ['label' => 'Plano Pendente', 'icon' => 'bi-hourglass-split', 'color' => '#eab308'],
];

// Género options
$gender_options = [
    'M' => ['label' => 'Masculino', 'icon' => 'bi-gender-male'],
    'F' => ['label' => 'Feminino', 'icon' => 'bi-gender-female'],
    'Outro' => ['label' => 'Outro', 'icon' => 'bi-gender-ambiguous'],
];

// Repopular campos após erro
$old = [
    'first_name'  => htmlspecialchars($_GET['first_name']  ?? ''),
    'second_name' => htmlspecialchars($_GET['second_name'] ?? ''),
    'username'    => htmlspecialchars($_GET['username']    ?? ''),
    'email'       => htmlspecialchars($_GET['email']       ?? ''),
    'tel'         => htmlspecialchars($_GET['tel']         ?? ''),
    'gender'      => $_GET['gender'] ?? '',
    'birth_date'  => htmlspecialchars($_GET['birth_date'] ?? ''),
    'country'     => htmlspecialchars($_GET['country'] ?? ''),
    'city'        => htmlspecialchars($_GET['city']    ?? ''),
    'plan'        => (int)($_GET['plan'] ?? 0),
    'status'      => $_GET['status'] ?? 'active',
];
?>

<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <title>Adicionar Utilizador — <?php echo APP_NAME; ?> Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap"
        rel="stylesheet" />
    <style>
        /* Mesmo CSS anterior... (manter tudo) */
        * {
            font-family: 'Inter', sans-serif;
        }

        .hero-card {
            background: linear-gradient(135deg, #0f0f17 0%, #1a1a2e 50%, #16213e 100%);
            border-radius: 24px;
            padding: 28px 32px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .05);
        }

        .form-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 20px;
            padding: 28px;
            transition: all .3s;
        }

        .dark-mode .form-card {
            background: var(--dark-card, #1a1a27);
            border-color: var(--dark-border, #2e2e42);
        }

        .section-title {
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #FF0089;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 10px 14px;
            font-size: .85rem;
        }

        .gender-options {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .gender-option {
            flex: 1;
            min-width: 100px;
        }

        .gender-option input[type="radio"] {
            display: none;
        }

        .gender-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px;
            border-radius: 12px;
            border: 2px solid var(--border-color, #e8e8f0);
            cursor: pointer;
            transition: all .2s;
            background: var(--card-bg, #fff);
        }

        .gender-option label i {
            font-size: 1.5rem;
            margin-bottom: 6px;
        }

        .gender-option input:checked+label {
            border-color: #FF0089;
            background: rgba(255, 0, 137, .07);
            color: #FF0089;
        }

        .pw-strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #e8e8f0;
            overflow: hidden;
            margin: 8px 0 4px;
        }

        .pw-strength-fill {
            height: 100%;
            border-radius: 2px;
            width: 0;
            transition: width .3s, background .3s;
        }

        .preview-card {
            position: sticky;
            top: 20px;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--border-color, #e8e8f0);
            background: var(--card-bg, #fff);
        }

        .preview-header {
            background: linear-gradient(135deg, #FF0089, #6c63ff);
            padding: 28px 20px 20px;
            text-align: center;
            color: #fff;
        }

        .preview-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .2);
            border: 3px solid rgba(255, 255, 255, .4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 12px;
            overflow: hidden;
        }

        .preview-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-name {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .preview-body {
            padding: 18px;
        }

        .preview-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color, #f0f0f8);
            font-size: .83rem;
        }

        .preview-row:last-child {
            border-bottom: none;
        }

        .preview-label {
            opacity: .6;
        }

        .preview-val {
            font-weight: 600;
            text-align: right;
            max-width: 55%;
            word-break: break-all;
        }

        .status-options {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .status-option {
            flex: 1;
            min-width: 140px;
        }

        .status-option input[type="radio"] {
            display: none;
        }

        .status-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 14px 10px;
            border-radius: 10px;
            border: 2px solid var(--border-color, #e8e8f0);
            cursor: pointer;
            transition: all .2s;
            text-align: center;
        }

        .status-option input:checked+label {
            border-color: #FF0089;
            background: rgba(255, 0, 137, .07);
            color: #FF0089;
        }

        .invite-box {
            border: 2px dashed rgba(255, 0, 137, .3);
            border-radius: 12px;
            padding: 16px 18px;
            background: rgba(255, 0, 137, .03);
            transition: border-color .2s, background .2s;
        }

        .invite-box.active {
            border-color: rgba(255, 0, 137, .6);
            background: rgba(255, 0, 137, .06);
        }

        .invite-detail {
            display: none;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 0, 137, .15);
        }

        .invite-detail.show {
            display: block;
        }

        .action-btn {
            padding: 8px 20px;
            border-radius: 12px;
            font-weight: 500;
            font-size: .85rem;
            transition: all .2s;
        }

        .action-btn-primary {
            background: #FF0089;
            color: #fff;
            border: none;
        }

        .action-btn-primary:hover {
            background: #ff338f;
            transform: translateY(-2px);
        }

        .action-btn-secondary {
            background: transparent;
            border: 1px solid var(--border-color, #e8e8f0);
        }

        .form-switch .form-check-input {
            width: 40px;
            height: 20px;
            cursor: pointer;
        }

        .form-switch .form-check-input:checked {
            background-color: #FF0089;
            border-color: #FF0089;
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
                        <h2 class="h4 mb-1"><i class="bi bi-person-plus-fill me-2"></i>Adicionar Utilizador</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                                        class="text-secondary">Utilizadores</a></li>
                                <li class="breadcrumb-item active text-white-stable">Adicionar</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                            class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>Voltar à lista
                        </a>
                    </div>
                </div>

                <!-- Feedback -->
                <?php if ($errors): ?>
                    <div class="alert alert-<?php echo $errors[0]; ?> alert-dismissible fade show mb-3">
                        <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($errors[1]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Hero Card -->
                <div class="hero-card">
                    <div class="d-flex flex-wrap align-items-center gap-4 position-relative" style="z-index: 1">
                        <div class="flex-grow-1">
                            <h3 class="mb-2 fw-bold" style="color: #fff;">Novo Utilizador</h3>
                            <div class="d-flex flex-wrap gap-3" style="font-size: .8rem; color: rgba(255,255,255,.6);">
                                <span><i class="bi bi-info-circle me-1"></i>Preencha os dados para criar uma nova
                                    conta</span>
                                <span><i class="bi bi-envelope-paper me-1"></i>O utilizador receberá um e-mail com as
                                    credenciais</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Formulário Principal -->
                    <div class="col-xl-8">
                        <div class="form-card">
                            <form method="POST" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/add-process"
                                enctype="multipart/form-data" id="form-add-user">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />

                                <!-- Informações Pessoais -->
                                <div class="section-title">
                                    <i class="bi bi-person-badge"></i>Informações Pessoais
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Primeiro Nome <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i
                                                    class="bi bi-person"></i></span>
                                            <input type="text" class="form-control" name="first_name" id="first_name"
                                                value="<?php echo $old['first_name']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Apelido</label>
                                        <input type="text" class="form-control" name="second_name" id="second_name"
                                            value="<?php echo $old['second_name']; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Nome de Utilizador <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent">@</span>
                                            <input type="text" class="form-control" name="username" id="username"
                                                value="<?php echo $old['username']; ?>" required>
                                        </div>
                                        <div class="form-text">Usado para login · só letras, números e _ (3-60
                                            caracteres)</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">E-mail <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i
                                                    class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" name="email" id="email"
                                                value="<?php echo $old['email']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Telefone</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i
                                                    class="bi bi-whatsapp"></i></span>
                                            <input type="tel" class="form-control" name="tel" id="tel"
                                                value="<?php echo $old['tel']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Data de Nascimento</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i
                                                    class="bi bi-calendar"></i></span>
                                            <input type="date" class="form-control" name="birth_date" id="birth_date"
                                                value="<?php echo $old['birth_date']; ?>"
                                                max="<?php echo date('Y-m-d', strtotime('-13 years')); ?>">
                                        </div>
                                        <div class="form-text">Deve ter pelo menos 13 anos</div>
                                    </div>
                                </div>

                                <!-- Género -->
                                <div class="section-title">
                                    <i class="bi bi-gender-ambiguous"></i>Género
                                </div>
                                <div class="gender-options mb-4">
                                    <?php foreach ($gender_options as $key => $opt): ?>
                                        <div class="gender-option">
                                            <input type="radio" name="gender" id="gender_<?php echo $key; ?>"
                                                value="<?php echo $key; ?>"
                                                <?php echo $old['gender'] === $key ? 'checked' : ($key === 'M' && !$old['gender'] ? 'checked' : ''); ?>>
                                            <label for="gender_<?php echo $key; ?>">
                                                <i class="bi <?php echo $opt['icon']; ?>"></i>
                                                <?php echo $opt['label']; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Senha -->
                                <div class="section-title">
                                    <i class="bi bi-shield-lock"></i>Segurança
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-8">
                                        <label class="form-label fw-semibold">Senha <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control font-monospace" name="password"
                                                id="password" placeholder="Clica em Gerar Senha →" readonly required />
                                            <button class="btn btn-outline-secondary" type="button" id="btn-gen-pw">
                                                <i class="bi bi-arrow-repeat me-1"></i>Gerar
                                            </button>
                                            <button class="btn btn-outline-secondary" type="button" id="btn-copy-pw">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </div>
                                        <div class="pw-strength-bar">
                                            <div class="pw-strength-fill" id="pw-fill"></div>
                                        </div>
                                        <div class="pw-strength-label" id="pw-label">Gera uma senha para continuar</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Força</label>
                                        <div class="alert py-2 text-center" id="pw-strength-text"
                                            style="font-size:.82rem;margin-bottom:0">—</div>
                                    </div>
                                </div>

                                <!-- Plano e Status -->
                                <div class="section-title">
                                    <i class="bi bi-star"></i>Plano e Estado
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Plano</label>
                                        <select class="form-select" name="plan" id="plan">
                                            <option value="">Sem plano</option>
                                            <?php foreach ($plans as $plan): ?>
                                                <option value="<?php echo $plan['id_plan']; ?>"
                                                    <?php echo $old['plan'] == $plan['id_plan'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($plan['name_plan']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Estado da Conta</label>
                                        <div class="status-options">
                                            <?php foreach ($status_options as $key => $opt): ?>
                                                <div class="status-option">
                                                    <input type="radio" name="status" id="status_<?php echo $key; ?>"
                                                        value="<?php echo $key; ?>"
                                                        <?php echo $old['status'] === $key ? 'checked' : ($key === 'active' ? 'checked' : ''); ?>>
                                                    <label for="status_<?php echo $key; ?>">
                                                        <i class="bi <?php echo $opt['icon']; ?>"></i>
                                                        <strong><?php echo $opt['label']; ?></strong>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Localização -->
                                <div class="section-title">
                                    <i class="bi bi-geo-alt"></i>Localização
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">País</label>
                                        <select class="form-select" name="country" id="country">
                                            <option value="">Selecionar país</option>
                                            <?php foreach ($paises as $code => $name): ?>
                                                <option value="<?php echo $code; ?>"
                                                    <?php echo $old['country'] === $code ? 'selected' : ''; ?>>
                                                    <?php echo $name; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Cidade</label>
                                        <input type="text" class="form-control" name="city" id="city"
                                            value="<?php echo $old['city']; ?>">
                                    </div>
                                </div>

                                <!-- Sobre -->
                                <div class="section-title">
                                    <i class="bi bi-chat-text"></i>Sobre
                                </div>
                                <div class="mb-4">
                                    <textarea class="form-control" name="about" id="about" rows="3"></textarea>
                                    <div class="form-text">Informações adicionais sobre o utilizador (opcional)</div>
                                </div>

                                <!-- Notificações -->
                                <div class="section-title">
                                    <i class="bi bi-bell"></i>Notificações
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="notif_email"
                                                id="notif_email" value="1" checked>
                                            <label class="form-check-label">E-mail</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="notif_push"
                                                id="notif_push" value="1">
                                            <label class="form-check-label">Push</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="notif_weekly"
                                                id="notif_weekly" value="1">
                                            <label class="form-check-label">Resumo semanal</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="notif_releases"
                                                id="notif_releases" value="1">
                                            <label class="form-check-label">Lançamentos</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Foto de Perfil -->
                                <div class="section-title">
                                    <i class="bi bi-image"></i>Foto de Perfil
                                </div>
                                <div class="mb-4">
                                    <input class="form-control" type="file" name="photo" id="photo"
                                        accept="image/jpeg,image/png,image/webp">
                                    <div class="form-text">JPG, PNG ou WebP · Máximo 2MB.</div>
                                </div>

                                <!-- Convite por e-mail -->
                                <div class="invite-box" id="invite-box">
                                    <div class="form-check form-switch d-flex align-items-center gap-3">
                                        <input class="form-check-input" type="checkbox" role="switch" name="send_invite"
                                            id="chk-invite" value="1" style="width:2.5em;height:1.3em;cursor:pointer"
                                            checked />
                                        <label class="form-check-label" for="chk-invite">
                                            <i class="bi bi-envelope-paper me-2" style="color:#FF0089"></i>
                                            Enviar e-mail de boas-vindas com as credenciais de acesso
                                        </label>
                                    </div>
                                    <div class="invite-detail show" id="invite-detail">
                                        <p style="font-size:.82rem;margin-bottom:8px;opacity:.8">
                                            O e-mail de boas-vindas incluirá:
                                        </p>
                                        <ul style="padding-left:18px;margin:0">
                                            <li>Nome completo e nome de utilizador atribuído</li>
                                            <li>Endereço de e-mail</li>
                                            <li>Senha temporária gerada</li>
                                            <li>Link de activação para definir uma nova senha (expira em 72h)</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Botões -->
                                <div class="d-flex justify-content-end gap-3 pt-3 border-top">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                                        class="action-btn action-btn-secondary">
                                        <i class="bi bi-arrow-left me-1"></i>Cancelar
                                    </a>
                                    <button type="submit" class="action-btn action-btn-primary" id="btn-submit"
                                        disabled>
                                        <span class="spinner-border spinner-border-sm d-none me-1"
                                            id="spin-submit"></span>
                                        <i class="bi bi-person-check me-1"></i>Criar Conta
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sidebar Direita - Pré-visualização -->
                    <div class="col-xl-4">
                        <div class="preview-card">
                            <div class="preview-header">
                                <div class="preview-avatar" id="prev-avatar">
                                    <i class="bi bi-person" style="color:rgba(255,255,255,.6)"></i>
                                </div>
                                <div class="preview-name" id="prev-name">Nome do Utilizador</div>
                            </div>
                            <div class="preview-body">
                                <div class="preview-row">
                                    <span class="preview-label">Username</span>
                                    <span class="preview-val" id="prev-user">—</span>
                                </div>
                                <div class="preview-row">
                                    <span class="preview-label">E-mail</span>
                                    <span class="preview-val" id="prev-email">—</span>
                                </div>
                                <div class="preview-row">
                                    <span class="preview-label">Telefone</span>
                                    <span class="preview-val" id="prev-tel">—</span>
                                </div>
                                <div class="preview-row">
                                    <span class="preview-label">Género</span>
                                    <span class="preview-val" id="prev-gender">—</span>
                                </div>
                                <div class="preview-row">
                                    <span class="preview-label">Data Nasc.</span>
                                    <span class="preview-val" id="prev-birth">—</span>
                                </div>
                                <div class="preview-row">
                                    <span class="preview-label">Localização</span>
                                    <span class="preview-val" id="prev-loc">—</span>
                                </div>
                                <div class="preview-row">
                                    <span class="preview-label">Plano</span>
                                    <span class="preview-val" id="prev-plan">—</span>
                                </div>
                                <div class="preview-row">
                                    <span class="preview-label">Estado</span>
                                    <span class="preview-val" id="prev-status">—</span>
                                </div>
                                <div class="preview-row">
                                    <span class="preview-label">Senha</span>
                                    <span class="preview-val" id="prev-pw"
                                        style="font-family:monospace;font-size:.75rem;color:#FF0089">—</span>
                                </div>
                                <div class="preview-row">
                                    <span class="preview-label">Notificações</span>
                                    <span class="preview-val" id="prev-notif">E-mail</span>
                                </div>
                            </div>
                            <div class="px-3 pb-3">
                                <div class="alert alert-info py-2 mb-0" id="prev-invite-info" style="font-size:.78rem">
                                    <i class="bi bi-envelope-paper me-1"></i>
                                    E-mail de boas-vindas será enviado após criação.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="col-12 text-center py-2" style="font-size:.8rem">
                <p class="mb-0">© <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos os direitos reservados.</p>
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
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos
            const form = document.getElementById('form-add-user');
            const firstName = document.getElementById('first_name');
            const secondName = document.getElementById('second_name');
            const username = document.getElementById('username');
            const email = document.getElementById('email');
            const tel = document.getElementById('tel');
            const birthDate = document.getElementById('birth_date');
            const country = document.getElementById('country');
            const city = document.getElementById('city');
            const plan = document.getElementById('plan');
            const password = document.getElementById('password');
            const photo = document.getElementById('photo');
            const btnGenPw = document.getElementById('btn-gen-pw');
            const btnCopyPw = document.getElementById('btn-copy-pw');
            const btnSubmit = document.getElementById('btn-submit');
            const spinSub = document.getElementById('spin-submit');
            const chkInvite = document.getElementById('chk-invite');
            const inviteBox = document.getElementById('invite-box');
            const inviteDetail = document.getElementById('invite-detail');
            const prevInviteInfo = document.getElementById('prev-invite-info');

            // Preview
            const prevAvatar = document.getElementById('prev-avatar');
            const prevName = document.getElementById('prev-name');
            const prevUser = document.getElementById('prev-user');
            const prevEmail = document.getElementById('prev-email');
            const prevTel = document.getElementById('prev-tel');
            const prevGender = document.getElementById('prev-gender');
            const prevBirth = document.getElementById('prev-birth');
            const prevLoc = document.getElementById('prev-loc');
            const prevPlan = document.getElementById('prev-plan');
            const prevStatus = document.getElementById('prev-status');
            const prevPw = document.getElementById('prev-pw');
            const prevNotif = document.getElementById('prev-notif');

            // Password strength
            const pwFill = document.getElementById('pw-fill');
            const pwLabel = document.getElementById('pw-label');
            const pwStrText = document.getElementById('pw-strength-text');

            // Género labels
            const genderLabels = {
                'M': 'Masculino',
                'F': 'Feminino',
                'Outro': 'Outro'
            };

            // Planos e status
            const plansData = <?php echo json_encode(array_column($plans, 'name_plan', 'id_plan')); ?>;
            const statusOptions = <?php echo json_encode($status_options); ?>;

            // Gerar username automático
            function genUsername(first, second) {
                let f = (first || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(
                    /[^a-z0-9]/g, '');
                let s = (second || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(
                    /[^a-z0-9]/g, '');
                let username = f + (s ? s.substring(0, 3) : '');
                return username.substring(0, 60);
            }

            let manualUsername = false;
            firstName.addEventListener('input', function() {
                if (!manualUsername) username.value = genUsername(this.value, secondName.value);
                updatePreview();
            });
            secondName.addEventListener('input', function() {
                if (!manualUsername) username.value = genUsername(firstName.value, this.value);
                updatePreview();
            });
            username.addEventListener('input', function() {
                manualUsername = true;
                updatePreview();
            });

            // Gerar senha forte
            function generatePassword() {
                const upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                const lower = 'abcdefghijklmnopqrstuvwxyz';
                const digits = '0123456789';
                const syms = '!@#$%^&*';
                const all = upper + lower + digits + syms;
                let pw = '';
                pw += upper[Math.floor(Math.random() * upper.length)];
                pw += lower[Math.floor(Math.random() * lower.length)];
                pw += digits[Math.floor(Math.random() * digits.length)];
                pw += syms[Math.floor(Math.random() * syms.length)];
                for (let i = pw.length; i < 12; i++) pw += all[Math.floor(Math.random() * all.length)];
                return pw.split('').sort(() => 0.5 - Math.random()).join('');
            }

            function evalStrength(pw) {
                let score = 0;
                if (pw.length >= 12) score++;
                if (/[A-Z]/.test(pw)) score++;
                if (/[a-z]/.test(pw)) score++;
                if (/[0-9]/.test(pw)) score++;
                if (/[^a-zA-Z0-9]/.test(pw)) score += 2;
                return Math.min(score, 5);
            }

            function updateStrength(pw) {
                let s = evalStrength(pw);
                let colors = ['#e8e8f0', '#ef4444', '#f97316', '#eab308', '#22c55e', '#16a34a'];
                let labels = ['—', 'Muito fraca', 'Fraca', 'Razoável', 'Forte', 'Muito forte'];
                pwFill.style.width = (s * 20) + '%';
                pwFill.style.background = colors[s];
                pwLabel.textContent = labels[s];
                pwStrText.textContent = labels[s];
                pwStrText.className =
                    `alert alert-${s < 2 ? 'danger' : s < 4 ? 'warning' : 'success'} py-2 text-center`;
            }

            btnGenPw.addEventListener('click', function() {
                let pw = generatePassword();
                password.value = pw;
                updateStrength(pw);
                prevPw.textContent = pw;
                checkCanSubmit();
            });

            btnCopyPw.addEventListener('click', function() {
                if (!password.value) return;
                navigator.clipboard.writeText(password.value).then(() => {
                    btnCopyPw.innerHTML = '<i class="bi bi-check"></i>';
                    setTimeout(() => btnCopyPw.innerHTML = '<i class="bi bi-clipboard"></i>', 2000);
                });
            });

            // Toggle convite
            chkInvite.addEventListener('change', function() {
                inviteDetail.classList.toggle('show', this.checked);
                inviteBox.classList.toggle('active', this.checked);
                prevInviteInfo.style.display = this.checked ? '' : 'none';
            });

            // Atualizar preview
            function updatePreview() {
                // Nome
                let full = [firstName.value.trim(), secondName.value.trim()].filter(Boolean).join(' ');
                prevName.textContent = full || 'Nome do Utilizador';

                // Informações básicas
                prevUser.textContent = username.value ? '@' + username.value : '—';
                prevEmail.textContent = email.value || '—';
                prevTel.textContent = tel.value || '—';

                // Género
                let genderVal = document.querySelector('input[name="gender"]:checked')?.value || '';
                prevGender.textContent = genderLabels[genderVal] || '—';

                // Data de nascimento
                if (birthDate.value) {
                    let date = new Date(birthDate.value);
                    prevBirth.textContent = date.toLocaleDateString('pt-PT');
                } else {
                    prevBirth.textContent = '—';
                }

                // Localização
                let countryText = country.options[country.selectedIndex]?.text || '';
                let cityText = city.value.trim();
                prevLoc.textContent = (countryText && countryText !== 'Selecionar país') ?
                    countryText + (cityText ? ', ' + cityText : '') : (cityText || '—');

                // Plano
                let planId = plan.value;
                prevPlan.textContent = plansData[planId] || '—';

                // Status
                let statusVal = document.querySelector('input[name="status"]:checked')?.value || 'active';
                prevStatus.innerHTML =
                    `<span style="color:${statusOptions[statusVal]?.color || '#888'}">${statusOptions[statusVal]?.label || 'Activo'}</span>`;

                // Notificações
                let notifs = [];
                if (document.getElementById('notif_email').checked) notifs.push('E-mail');
                if (document.getElementById('notif_push').checked) notifs.push('Push');
                if (document.getElementById('notif_weekly').checked) notifs.push('Semanal');
                if (document.getElementById('notif_releases').checked) notifs.push('Lançamentos');
                prevNotif.textContent = notifs.length ? notifs.join(', ') : 'Nenhuma';
            }

            function checkCanSubmit() {
                let ok = firstName.value.trim() !== '' && email.value.trim() !== '' && username.value.trim() !==
                    '' && password.value.trim() !== '';
                btnSubmit.disabled = !ok;
            }

            // Listeners
            [firstName, secondName, username, email, tel, birthDate, country, city, plan].forEach(el => {
                if (el) el.addEventListener('input', updatePreview);
                if (el) el.addEventListener('change', updatePreview);
            });
            document.querySelectorAll('input[name="gender"]').forEach(el => el.addEventListener('change',
                updatePreview));
            document.querySelectorAll('input[name="status"]').forEach(el => el.addEventListener('change',
                updatePreview));
            document.querySelectorAll('.form-switch input').forEach(el => el.addEventListener('change',
                updatePreview));

            // Avatar preview
            photo.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        prevAvatar.innerHTML =
                            `<img src="${event.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:50%"/>`;
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Submit
            form.addEventListener('submit', function(e) {
                if (!password.value.trim()) {
                    e.preventDefault();
                    alert('Gera uma senha antes de criar a conta.');
                    return;
                }
                spinSub.classList.remove('d-none');
                btnSubmit.disabled = true;
                btnSubmit.querySelector('i').className = '';
            });

            // Gerar senha inicial
            btnGenPw.click();
            updatePreview();
        });
    </script>
</body>

</html>