<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Adicionar Funcionário
// Arquivo: admin/pages/employees/add.php
// Rota: admin/employees/add
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'employees.edit');

// Só super_admin pode criar super_admin ou admin
// admin pode criar editor e support
$can_create_admin = in_array($admin_role, ['super_admin']);

// Feedback de erro de validação (volta do process)
$err = $_GET['err'] ?? null;
$errors = match ($err) {
    'email_exists'    => ['danger', 'Este e-mail já está registado em outro funcionário.'],
    'user_exists'     => ['danger', 'Este username já está em uso.'],
    'invalid'         => ['danger', 'Dados inválidos. Verifica os campos obrigatórios.'],
    'email_fail'      => ['warning', 'Conta criada, mas o e-mail de convite falhou. Partilha as credenciais manualmente.'],
    'error'           => ['danger', 'Ocorreu um erro ao criar a conta. Tenta novamente.'],
    default           => null,
};

// Repopular campos após erro
$old = [
    'first_name'  => htmlspecialchars($_GET['first_name']  ?? ''),
    'second_name' => htmlspecialchars($_GET['second_name'] ?? ''),
    'username'    => htmlspecialchars($_GET['username']    ?? ''),
    'email'       => htmlspecialchars($_GET['email']       ?? ''),
    'tel'         => htmlspecialchars($_GET['tel']         ?? ''),
    'gender'      => $_GET['gender']   ?? 'M',
    'role'        => $_GET['role']     ?? 'editor',
    'status'      => $_GET['status']   ?? 'processing',
    'country'     => htmlspecialchars($_GET['country'] ?? ''),
    'city'        => htmlspecialchars($_GET['city']    ?? ''),
];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <title>Adicionar Funcionário — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/scrollue.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <style>
    /* ── Secções do formulário ── */
    .form-section {
        padding: 22px;
        border-radius: 12px;
        border: 1px solid var(--border-color, #e8e8f0);
        background: var(--card-bg, #fff);
        margin-bottom: 20px;
    }

    .form-section h4 {
        font-size: .95rem;
        font-weight: 700;
        margin-bottom: 0;
        padding-bottom: 14px;
        border-bottom: 1px solid var(--border-color, #f0f0f8);
    }

    .form-section h4 i {
        color: #FF0089;
    }

    /* ── Preview card ── */
    .preview-card {
        position: sticky;
        top: 20px;
        border-radius: 16px;
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

    .preview-role {
        font-size: .78rem;
        opacity: .8;
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

    /* ── Força da senha ── */
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

    .pw-strength-label {
        font-size: .74rem;
        color: #aaa;
    }

    /* ── Checkbox de convite ── */
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

    .invite-box .form-check-label {
        font-size: .88rem;
        font-weight: 600;
        cursor: pointer;
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

    .invite-detail li {
        font-size: .82rem;
        margin-bottom: 4px;
        opacity: .8;
    }

    /* ── Status radio cards ── */
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
        width: 100%;
        font-size: .82rem;
    }

    .status-option label i {
        font-size: 1.3rem;
        margin-bottom: 6px;
    }

    .status-option input:checked+label {
        border-color: #FF0089;
        background: rgba(255, 0, 137, .07);
        color: #FF0089;
    }

    .status-option.s-active input:checked+label {
        border-color: #22c55e;
        background: rgba(34, 197, 94, .07);
        color: #166534;
    }

    .status-option.s-process input:checked+label {
        border-color: #3b82f6;
        background: rgba(59, 130, 246, .07);
        color: #1e40af;
    }

    /* ── Role select visual ── */
    .role-select-wrap select {
        font-size: .88rem;
    }

    .role-hint {
        font-size: .76rem;
        opacity: .6;
        margin-top: 4px;
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
                            <i class="bi bi-person-plus-fill me-2"></i>Adicionar Funcionário
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>" class="text-secondary">Home</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees"
                                        class="text-secondary">Funcionários</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable">Adicionar</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees"
                            class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>Voltar à lista
                        </a>
                    </div>
                </div>

                <!-- Feedback de erro -->
                <?php if ($errors): ?>
                <div class="alert alert-<?php echo $errors[0]; ?> alert-dismissible fade show mb-3">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($errors[1]); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees/add-process"
                    enctype="multipart/form-data" id="form-add-emp" novalidate>
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />

                    <div class="row g-4">

                        <!-- ══ Coluna esquerda — formulário ══ -->
                        <div class="col-lg-8">

                            <!-- 1. Informações básicas -->
                            <div class="form-section">
                                <h4><i class="bi bi-person-badge"></i> Informações Básicas</h4>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-3">
                                        <label class="form-label">Género <span class="text-danger">*</span></label>
                                        <select class="form-select" name="gender" id="inp-gender" required>
                                            <option value="M" <?php echo $old['gender'] === 'M' ? 'selected' : ''; ?>>
                                                Masculino</option>
                                            <option value="F" <?php echo $old['gender'] === 'F' ? 'selected' : ''; ?>>
                                                Feminino
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Primeiro Nome <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="first_name" id="inp-fname"
                                            placeholder="Ex: José" maxlength="50" required
                                            value="<?php echo $old['first_name']; ?>" />
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Apelido</label>
                                        <input type="text" class="form-control" name="second_name" id="inp-sname"
                                            placeholder="Ex: Mbenga da Costa" maxlength="80"
                                            value="<?php echo $old['second_name']; ?>" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Username <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">@</span>
                                            <input type="text" class="form-control" name="username" id="inp-user"
                                                placeholder="josembengadacosta" maxlength="60" required
                                                value="<?php echo $old['username']; ?>" autocomplete="off" />
                                        </div>
                                        <div class="form-text">Usado para login · só letras, números e _</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Telefone</label>
                                        <input type="tel" class="form-control" name="tel" id="inp-tel"
                                            placeholder="+244 9xx xxx xxx" maxlength="20"
                                            value="<?php echo $old['tel']; ?>" />
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">E-mail <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" name="email" id="inp-email"
                                            placeholder="funcionario@wasomupfy.com" maxlength="255" required
                                            value="<?php echo $old['email']; ?>" autocomplete="off" />
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>
                                            As credenciais de acesso serão enviadas para este endereço.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Cargo e permissões -->
                            <div class="form-section">
                                <h4><i class="bi bi-shield-lock"></i> Cargo e Permissões</h4>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-12 role-select-wrap">
                                        <label class="form-label">Role / Cargo <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select" name="role" id="inp-role" required>
                                            <?php if ($can_create_admin): ?>
                                            <option value="super_admin"
                                                <?php echo $old['role'] === 'super_admin' ? 'selected' : ''; ?>>
                                                Super Administrador — acesso total à plataforma
                                            </option>
                                            <option value="admin"
                                                <?php echo $old['role'] === 'admin' ? 'selected' : ''; ?>>
                                                Administrador — gestão geral (sem acesso total)
                                            </option>
                                            <?php else: ?>
                                            <option value="admin" disabled title="Requer Super Admin">
                                                Administrador (requer Super Admin)
                                            </option>
                                            <?php endif; ?>
                                            <option value="editor"
                                                <?php echo $old['role'] === 'editor' ? 'selected' : ''; ?>>
                                                Editor — gestão de músicas e lançamentos
                                            </option>
                                            <option value="support"
                                                <?php echo $old['role'] === 'support' ? 'selected' : ''; ?>>
                                                Suporte — gestão de tickets e suporte ao cliente
                                            </option>
                                        </select>

                                        <!-- Descrição dinâmica do role -->
                                        <div class="mt-2 p-3 rounded" id="role-desc"
                                            style="font-size:.82rem;border:1px solid var(--border-color,#e8e8f0)">
                                            <strong>Editor:</strong>
                                            Pode rever, aprovar e rejeitar músicas e lançamentos.
                                            Sem acesso a finanças, utilizadores ou configurações.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Segurança — senha -->
                            <div class="form-section">
                                <h4><i class="bi bi-key"></i> Senha Temporária</h4>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-8">
                                        <label class="form-label">Senha <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control font-monospace" name="password"
                                                id="inp-pw" placeholder="Clica em Gerar Senha →"
                                                autocomplete="new-password" readonly required />
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
                                        <div class="pw-strength-label" id="pw-label">
                                            Gera uma senha para continuar
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Força</label>
                                        <div class="alert py-2 text-center" id="pw-strength-text"
                                            style="font-size:.82rem;margin-bottom:0">—</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>
                                            A senha é temporária. O funcionário será convidado a alterá-la no primeiro
                                            acesso.
                                            Mínimo 12 caracteres, com maiúsculas, números e símbolos.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Localização (opcional — depende do ALTER TABLE) -->
                            <div class="form-section">
                                <h4><i class="bi bi-geo-alt"></i> Localização <small class="text-muted"
                                        style="font-size:.75rem;font-weight:400">(opcional)</small></h4>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label class="form-label">País</label>
                                        <select class="form-select" name="country" id="inp-country">
                                            <option value="">Selecione um país</option>
                                            <?php
                                            $countries = [
                                                'AO' => 'Angola',
                                                'PT' => 'Portugal',
                                                'BR' => 'Brasil',
                                                'MZ' => 'Moçambique',
                                                'AF' => 'Afeganistão',
                                                'AL' => 'Albânia',
                                                'DZ' => 'Argélia',
                                                'AR' => 'Argentina',
                                                'AM' => 'Arménia',
                                                'AU' => 'Austrália',
                                                'AT' => 'Áustria',
                                                'AZ' => 'Azerbaijão',
                                                'BE' => 'Bélgica',
                                                'BZ' => 'Belize',
                                                'BJ' => 'Benim',
                                                'BO' => 'Bolívia',
                                                'BA' => 'Bósnia',
                                                'BW' => 'Botsuana',
                                                'CV' => 'Cabo Verde',
                                                'CM' => 'Camarões',
                                                'CA' => 'Canadá',
                                                'CL' => 'Chile',
                                                'CO' => 'Colômbia',
                                                'CG' => 'Congo-Brazzaville',
                                                'CD' => 'Congo-Kinshasa',
                                                'KP' => 'Coreia do Norte',
                                                'KR' => 'Coreia do Sul',
                                                'CI' => 'Costa do Marfim',
                                                'HR' => 'Croácia',
                                                'CU' => 'Cuba',
                                                'DK' => 'Dinamarca',
                                                'EG' => 'Egipto',
                                                'AE' => 'Emirados Árabes',
                                                'SK' => 'Eslováquia',
                                                'SI' => 'Eslovénia',
                                                'ES' => 'Espanha',
                                                'US' => 'Estados Unidos',
                                                'ET' => 'Etiópia',
                                                'FR' => 'França',
                                                'GA' => 'Gabão',
                                                'GH' => 'Gana',
                                                'DE' => 'Alemanha',
                                                'GR' => 'Grécia',
                                                'GW' => 'Guiné-Bissau',
                                                'GN' => 'Guiné',
                                                'HT' => 'Haiti',
                                                'HN' => 'Honduras',
                                                'HU' => 'Hungria',
                                                'IN' => 'Índia',
                                                'ID' => 'Indonésia',
                                                'IE' => 'Irlanda',
                                                'IL' => 'Israel',
                                                'IT' => 'Itália',
                                                'JM' => 'Jamaica',
                                                'JP' => 'Japão',
                                                'KE' => 'Quénia',
                                                'LY' => 'Líbia',
                                                'LU' => 'Luxemburgo',
                                                'MG' => 'Madagáscar',
                                                'MY' => 'Malásia',
                                                'MW' => 'Maláui',
                                                'MV' => 'Maldivas',
                                                'ML' => 'Mali',
                                                'MT' => 'Malta',
                                                'MA' => 'Marrocos',
                                                'MX' => 'México',
                                                'MN' => 'Mongólia',
                                                'NA' => 'Namíbia',
                                                'NP' => 'Nepal',
                                                'NI' => 'Nicarágua',
                                                'NE' => 'Níger',
                                                'NG' => 'Nigéria',
                                                'NO' => 'Noruega',
                                                'NZ' => 'Nova Zelândia',
                                                'PK' => 'Paquistão',
                                                'PY' => 'Paraguai',
                                                'PE' => 'Peru',
                                                'PL' => 'Polónia',
                                                'RO' => 'Roménia',
                                                'RW' => 'Ruanda',
                                                'SN' => 'Senegal',
                                                'SL' => 'Serra Leoa',
                                                'SO' => 'Somália',
                                                'ZA' => 'África do Sul',
                                                'SS' => 'Sudão do Sul',
                                                'SE' => 'Suécia',
                                                'CH' => 'Suíça',
                                                'TZ' => 'Tanzânia',
                                                'TH' => 'Tailândia',
                                                'TT' => 'Trindade e Tobago',
                                                'TN' => 'Tunísia',
                                                'TR' => 'Turquia',
                                                'UG' => 'Uganda',
                                                'UA' => 'Ucrânia',
                                                'UY' => 'Uruguai',
                                                'VE' => 'Venezuela',
                                                'VN' => 'Vietname',
                                                'ZM' => 'Zâmbia',
                                                'ZW' => 'Zimbábue',
                                            ];
                                            asort($countries);
                                            foreach ($countries as $code => $name):
                                                $sel = ($old['country'] === $code) ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo $code; ?>" <?php echo $sel; ?>>
                                                <?php echo htmlspecialchars($name); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Cidade</label>
                                        <input type="text" class="form-control" name="city" id="inp-city"
                                            placeholder="Ex: Luanda" maxlength="80"
                                            value="<?php echo $old['city']; ?>" />
                                    </div>
                                </div>
                            </div>

                            <!-- 5. Estado da conta -->
                            <div class="form-section">
                                <h4><i class="bi bi-toggle-on"></i> Estado da Conta</h4>
                                <p style="font-size:.84rem;opacity:.7;margin-top:8px">
                                    Defines se o funcionário pode aceder imediatamente ao painel ou
                                    se a conta fica em espera até tu activares manualmente.
                                </p>
                                <div class="status-options">
                                    <div class="status-option s-active">
                                        <input type="radio" name="status" id="s-active" value="active"
                                            <?php echo $old['status'] === 'active' ? 'checked' : ''; ?>>
                                        <label for="s-active">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <strong>Activar agora</strong>
                                            <span style="font-size:.74rem;opacity:.7;margin-top:2px">
                                                Acesso imediato ao painel
                                            </span>
                                        </label>
                                    </div>
                                    <div class="status-option s-process">
                                        <input type="radio" name="status" id="s-process" value="processing"
                                            <?php echo ($old['status'] === 'processing' || $old['status'] === '') ? 'checked' : ''; ?>>
                                        <label for="s-process">
                                            <i class="bi bi-hourglass-split"></i>
                                            <strong>Aguardar activação</strong>
                                            <span style="font-size:.74rem;opacity:.7;margin-top:2px">
                                                Só acede após tu activares
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- 6. Foto de perfil -->
                            <div class="form-section">
                                <h4><i class="bi bi-image"></i> Foto de Perfil <small class="text-muted"
                                        style="font-size:.75rem;font-weight:400">(opcional)</small></h4>
                                <div class="mt-3">
                                    <input class="form-control" type="file" name="photo" id="inp-photo"
                                        accept="image/jpeg,image/png,image/webp" />
                                    <div class="form-text">JPG, PNG ou WebP · Máximo 2MB.</div>
                                </div>
                            </div>

                            <!-- 7. Convite por e-mail -->
                            <div class="invite-box" id="invite-box">
                                <div class="form-check form-switch d-flex align-items-center gap-3">
                                    <input class="form-check-input" type="checkbox" role="switch" name="send_invite"
                                        id="chk-invite" value="1" style="width:2.5em;height:1.3em;cursor:pointer"
                                        checked />
                                    <label class="form-check-label" for="chk-invite">
                                        <i class="bi bi-envelope-paper me-2" style="color:#FF0089"></i>
                                        Enviar e-mail de convite com as credenciais de acesso
                                    </label>
                                </div>
                                <div class="invite-detail show" id="invite-detail">
                                    <p style="font-size:.82rem;margin-bottom:8px;opacity:.8">
                                        O e-mail de convite incluirá:
                                    </p>
                                    <ul style="padding-left:18px;margin:0">
                                        <li>Nome completo e username atribuído</li>
                                        <li>Endereço de e-mail</li>
                                        <li>Senha temporária gerada</li>
                                        <li>Um <strong>botão de activação</strong> com link único e seguro</li>
                                        <li>Instruções para alterar a senha no primeiro acesso</li>
                                    </ul>
                                    <div class="alert alert-warning mt-2 mb-0 py-2" style="font-size:.78rem">
                                        <i class="bi bi-shield-lock me-1"></i>
                                        O link de activação expira em <strong>72 horas</strong> e só pode ser usado
                                        <strong>uma vez</strong>. Após utilizado, o botão deixa de funcionar.
                                    </div>
                                </div>
                            </div>

                            <!-- Botões -->
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/employees"
                                    class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-1"></i>Cancelar
                                </a>
                                <button type="submit" class="btn text-white" id="btn-submit"
                                    style="background:#FF0089;border-color:#FF0089" disabled>
                                    <span class="spinner-border spinner-border-sm d-none me-1" id="spin-submit"></span>
                                    <i class="bi bi-person-check me-1"></i>Criar Conta
                                </button>
                            </div>

                        </div><!-- /col-lg-8 -->

                        <!-- ══ Coluna direita — preview ══ -->
                        <div class="col-lg-4">
                            <div class="preview-card">
                                <div class="preview-header">
                                    <div class="preview-avatar" id="prev-avatar">
                                        <i class="bi bi-person" style="color:rgba(255,255,255,.6)"></i>
                                    </div>
                                    <div class="preview-name" id="prev-name">Nome do Funcionário</div>
                                    <div class="preview-role" id="prev-role-label">
                                        <span class="badge bg-light text-dark">Editor</span>
                                    </div>
                                </div>
                                <div class="preview-body">
                                    <div class="preview-row">
                                        <span class="preview-label">Username</span>
                                        <span class="preview-val" id="prev-user">—</span>
                                    </div>
                                    <div class="preview-row">
                                        <span class="preview-label">E-mail</span>
                                        <span class="preview-val" id="prev-email" style="font-size:.78rem">—</span>
                                    </div>
                                    <div class="preview-row">
                                        <span class="preview-label">Telefone</span>
                                        <span class="preview-val" id="prev-tel">—</span>
                                    </div>
                                    <div class="preview-row">
                                        <span class="preview-label">Localização</span>
                                        <span class="preview-val" id="prev-loc">—</span>
                                    </div>
                                    <div class="preview-row">
                                        <span class="preview-label">Estado</span>
                                        <span class="preview-val" id="prev-status">
                                            <span class="badge bg-primary">Em processo</span>
                                        </span>
                                    </div>
                                    <div class="preview-row">
                                        <span class="preview-label">Senha</span>
                                        <span class="preview-val" id="prev-pw"
                                            style="font-family:monospace;font-size:.75rem;color:#FF0089">—</span>
                                    </div>
                                </div>

                                <!-- Info sobre o email de convite -->
                                <div class="px-3 pb-3">
                                    <div class="alert alert-info py-2 mb-0" id="prev-invite-info"
                                        style="font-size:.78rem">
                                        <i class="bi bi-envelope-paper me-1"></i>
                                        E-mail de convite será enviado após criação.
                                    </div>
                                </div>
                            </div>
                        </div><!-- /col-lg-4 -->

                    </div><!-- /row -->
                </form>

            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-2">© <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos os direitos reservados.
                    </p>
                </div>
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
    <script src="<?php echo APP_URL; ?>/js/lastest.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {

        // ── Referências ──
        var fName = document.getElementById('inp-fname');
        var sName = document.getElementById('inp-sname');
        var userInp = document.getElementById('inp-user');
        var emailInp = document.getElementById('inp-email');
        var telInp = document.getElementById('inp-tel');
        var genderSel = document.getElementById('inp-gender');
        var roleSel = document.getElementById('inp-role');
        var countryS = document.getElementById('inp-country');
        var cityInp = document.getElementById('inp-city');
        var pwInp = document.getElementById('inp-pw');
        var photoInp = document.getElementById('inp-photo');
        var btnGenPw = document.getElementById('btn-gen-pw');
        var btnCpPw = document.getElementById('btn-copy-pw');
        var btnSubmit = document.getElementById('btn-submit');
        var spinSub = document.getElementById('spin-submit');
        var form = document.getElementById('form-add-emp');

        // Preview refs
        var prevAvatar = document.getElementById('prev-avatar');
        var prevName = document.getElementById('prev-name');
        var prevRoleLbl = document.getElementById('prev-role-label');
        var prevUser = document.getElementById('prev-user');
        var prevEmail = document.getElementById('prev-email');
        var prevTel = document.getElementById('prev-tel');
        var prevLoc = document.getElementById('prev-loc');
        var prevStatus = document.getElementById('prev-status');
        var prevPw = document.getElementById('prev-pw');
        var prevInvInfo = document.getElementById('prev-invite-info');
        var pwFill = document.getElementById('pw-fill');
        var pwLabel = document.getElementById('pw-strength-label') || document.getElementById('pw-label');
        var pwStrTxt = document.getElementById('pw-strength-text');
        var roleDesc = document.getElementById('role-desc');
        var chkInvite = document.getElementById('chk-invite');
        var inviteBox = document.getElementById('invite-box');
        var inviteDetail = document.getElementById('invite-detail');

        // ── Dados de roles ──
        var roleData = {
            'super_admin': {
                label: 'Super Admin',
                cls: 'bg-danger',
                desc: '<strong>Super Administrador:</strong> Acesso total à plataforma sem qualquer restrição. Pode gerir funcionários, finanças, configurações e todos os dados.'
            },
            'admin': {
                label: 'Admin',
                cls: 'bg-primary',
                desc: '<strong>Administrador:</strong> Gestão geral da plataforma. Pode gerir utilizadores, músicas, finanças e suporte. Sem acesso à gestão de Super Admins.'
            },
            'editor': {
                label: 'Editor',
                cls: 'bg-info text-dark',
                desc: '<strong>Editor:</strong> Pode rever, aprovar e rejeitar músicas e lançamentos. Sem acesso a finanças, utilizadores ou configurações.'
            },
            'support': {
                label: 'Suporte',
                cls: 'bg-secondary',
                desc: '<strong>Suporte:</strong> Gestão de tickets e suporte ao cliente. Acesso apenas a visualização de dados relevantes para o atendimento.'
            }
        };

        // ── Gerar username automático ──
        function genUsername(first, second) {
            var f = (first || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(
                /[^a-z0-9]/g, '');
            var s = (second || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(
                /[^a-z0-9]/g, '');
            return f + (s ? s.charAt(0) : '');
        }

        fName.addEventListener('input', function() {
            if (!userInp.dataset.manual) {
                userInp.value = genUsername(this.value, sName.value);
            }
            updatePreview();
        });
        sName.addEventListener('input', function() {
            if (!userInp.dataset.manual) {
                userInp.value = genUsername(fName.value, this.value);
            }
            updatePreview();
        });
        userInp.addEventListener('input', function() {
            this.dataset.manual = '1';
            updatePreview();
        });

        // ── Gerar senha forte ──
        function generatePassword() {
            var upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            var lower = 'abcdefghijklmnopqrstuvwxyz';
            var digits = '0123456789';
            var syms = '!@#$%^&*-_+=?';
            var all = upper + lower + digits + syms;
            var pw = '';
            // Garantir pelo menos 2 de cada
            pw += upper[Math.floor(Math.random() * upper.length)];
            pw += upper[Math.floor(Math.random() * upper.length)];
            pw += lower[Math.floor(Math.random() * lower.length)];
            pw += lower[Math.floor(Math.random() * lower.length)];
            pw += digits[Math.floor(Math.random() * digits.length)];
            pw += digits[Math.floor(Math.random() * digits.length)];
            pw += syms[Math.floor(Math.random() * syms.length)];
            pw += syms[Math.floor(Math.random() * syms.length)];
            // Completar até 16
            for (var i = pw.length; i < 16; i++) {
                pw += all[Math.floor(Math.random() * all.length)];
            }
            // Embaralhar
            pw = pw.split('').sort(function() {
                return 0.5 - Math.random();
            }).join('');
            return pw;
        }

        function evalStrength(pw) {
            var score = 0;
            if (pw.length >= 12) score++;
            if (pw.length >= 16) score++;
            if (/[A-Z]/.test(pw)) score++;
            if (/[a-z]/.test(pw)) score++;
            if (/[0-9]/.test(pw)) score++;
            if (/[^a-zA-Z0-9]/.test(pw)) score += 2;
            return Math.min(score, 5);
        }

        function updateStrength(pw) {
            var s = evalStrength(pw);
            var colors = ['#e8e8f0', '#ef4444', '#f97316', '#eab308', '#22c55e', '#16a34a'];
            var labels = ['—', 'Muito fraca', 'Fraca', 'Razoável', 'Forte', 'Muito forte'];
            var classes = ['light', 'danger', 'warning', 'warning', 'success', 'success'];
            pwFill.style.width = (s * 20) + '%';
            pwFill.style.background = colors[s];
            if (pwLabel) {
                pwLabel.textContent = labels[s];
                pwLabel.style.color = colors[s];
            }
            if (pwStrTxt) {
                pwStrTxt.textContent = labels[s];
                pwStrTxt.className = 'alert alert-' + classes[s] + ' py-2 text-center';
                pwStrTxt.style.fontSize = '.82rem';
                pwStrTxt.style.marginBottom = '0';
            }
        }

        btnGenPw.addEventListener('click', function() {
            var pw = generatePassword();
            pwInp.value = pw;
            updateStrength(pw);
            prevPw.textContent = pw;
            checkCanSubmit();
        });

        btnCpPw.addEventListener('click', function() {
            if (!pwInp.value) return;
            navigator.clipboard.writeText(pwInp.value).then(function() {
                btnCpPw.innerHTML = '<i class="bi bi-check"></i>';
                setTimeout(function() {
                    btnCpPw.innerHTML = '<i class="bi bi-clipboard"></i>';
                }, 2000);
            });
        });

        // ── Descrição do role ──
        roleSel.addEventListener('change', function() {
            var r = roleData[this.value];
            if (r) {
                roleDesc.innerHTML = r.desc;
            }
            updatePreview();
        });
        // Trigger inicial
        roleSel.dispatchEvent(new Event('change'));

        // ── Toggle convite ──
        chkInvite.addEventListener('change', function() {
            inviteDetail.classList.toggle('show', this.checked);
            inviteBox.classList.toggle('active', this.checked);
            prevInvInfo.style.display = this.checked ? '' : 'none';
        });
        // Estado inicial
        inviteBox.classList.toggle('active', chkInvite.checked);

        // ── Preview em tempo real ──
        function updatePreview() {
            var full = [fName.value.trim(), sName.value.trim()].filter(Boolean).join(' ');
            prevName.textContent = full || 'Nome do Funcionário';

            var r = roleData[roleSel.value] || roleData['editor'];
            prevRoleLbl.innerHTML = '<span class="badge ' + r.cls + '">' + r.label + '</span>';

            prevUser.textContent = userInp.value ? '@' + userInp.value : '—';
            prevEmail.textContent = emailInp.value || '—';
            prevTel.textContent = telInp.value || '—';

            var country = countryS.options[countryS.selectedIndex]?.text || '';
            var city = cityInp.value.trim();
            prevLoc.textContent = (country && country !== 'Selecione um país') ?
                country + (city ? ', ' + city : '') :
                (city || '—');

            // Status
            var statusVal = document.querySelector('input[name="status"]:checked')?.value || 'processing';
            var statusMap = {
                'active': '<span class="badge" style="background:rgba(34,197,94,.15);color:#166534">Activo</span>',
                'processing': '<span class="badge" style="background:rgba(59,130,246,.15);color:#1e40af">Em processo</span>',
            };
            prevStatus.innerHTML = statusMap[statusVal] || statusMap['processing'];

            // Avatar com iniciais
            if (!prevAvatar.querySelector('img')) {
                var ini = (fName.value[0] || '').toUpperCase() + (sName.value[0] || '').toUpperCase();
                prevAvatar.innerHTML = ini ?
                    '<span style="font-size:1.8rem;font-weight:800;color:#fff">' + ini + '</span>' :
                    '<i class="bi bi-person" style="color:rgba(255,255,255,.6)"></i>';
            }
        }

        // Listeners de preview
        [fName, sName, userInp, emailInp, telInp, cityInp].forEach(function(el) {
            el.addEventListener('input', updatePreview);
        });
        [genderSel, roleSel, countryS].forEach(function(el) {
            el.addEventListener('change', updatePreview);
        });
        document.querySelectorAll('input[name="status"]').forEach(function(el) {
            el.addEventListener('change', updatePreview);
        });

        // ── Validação antes de submeter ──
        function checkCanSubmit() {
            var ok = fName.value.trim() !== '' &&
                emailInp.value.trim() !== '' &&
                userInp.value.trim() !== '' &&
                pwInp.value.trim() !== '';
            btnSubmit.disabled = !ok;
        }
        [fName, emailInp, userInp].forEach(function(el) {
            el.addEventListener('input', checkCanSubmit);
        });

        // ── Submit ──
        form.addEventListener('submit', function(e) {
            if (!pwInp.value.trim()) {
                e.preventDefault();
                alert('Gera uma senha antes de criar a conta.');
                return;
            }
            spinSub.classList.remove('d-none');
            btnSubmit.disabled = true;
            btnSubmit.querySelector('i').className = '';
        });

        // ── Upload de foto — preview ──
        photoInp.addEventListener('change', function() {
            var file = this.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                prevAvatar.innerHTML = '<img src="' + e.target.result +
                    '" style="width:100%;height:100%;object-fit:cover;border-radius:50%"/>';
            };
            reader.readAsDataURL(file);
        });

        // Trigger inicial
        updatePreview();
    });
    </script>
</body>

</html>