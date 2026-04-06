<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Editar Utilizador
// Arquivo: admin/pages/users/edit.php
// Rota:    admin/users/edit?id=X
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'users.edit');

$id = (int)($_GET['id'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/users');

// ── Buscar dados do utilizador ──
$stmt = $db->prepare("
    SELECT
        u.id_users,
        u.first_name,
        u.second_name,
        u.user_name,
        u.email_user,
        u.tel_user,
        u.country_user,
        u.city_user,
        u.status_user,
        u.plan_selected,
        u.photo_user,
        u.about_user,
        u.creat_user,
        u.modif_user,
        u.notif_email,
        u.notif_push,
        u.notif_weekly,
        u.notif_releases,
        u.notif_payments,
        u.trust_score,
        u.onboarding_done,
        us.last_login_at,
        us.last_login_ip,
        us.two_factor_enabled,
        pl.name_plan,
        pl.slug_plan
    FROM _users u
    LEFT JOIN _users_security us ON us.id_users = u.id_users
    LEFT JOIN _plans pl ON pl.id_plan = u.plan_selected
    WHERE u.id_users = ?
");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    adminRedirect('/' . ADMIN_PATH . '/users?msg=not_found');
}

// ── Buscar planos disponíveis ──
$plans = $db->query("SELECT id_plan, name_plan, slug_plan, price_plan FROM _plans WHERE is_active = 1 ORDER BY price_plan")->fetchAll();

// ── Buscar artistas do utilizador ──
$artists = $db->prepare("
    SELECT id_artist, stage_name, photo_artist, status_artist
    FROM _artist WHERE id_users = ? ORDER BY creat_artist DESC LIMIT 5
");
$artists->execute([$id]);
$artist_list = $artists->fetchAll();

// ── Buscar lançamentos recentes ──
$releases = $db->prepare("
    SELECT a.id_album, a.title_album, a.status_album, a.img_cover, a.creat_album
    FROM _album a
    INNER JOIN _artist ar ON ar.id_artist = a.id_artist
    WHERE ar.id_users = ?
    ORDER BY a.creat_album DESC LIMIT 3
");
$releases->execute([$id]);
$release_list = $releases->fetchAll();

// ── Lista de países (completa) ──
$paises = [
    "AF" => "Afeganistão",
    "ZA" => "África do Sul",
    "AL" => "Albânia",
    "DE" => "Alemanha",
    "AD" => "Andorra",
    "AO" => "Angola",
    "AI" => "Anguilla",
    "AQ" => "Antártida",
    "AG" => "Antígua e Barbuda",
    "SA" => "Arábia Saudita",
    "DZ" => "Argélia",
    "AR" => "Argentina",
    "AM" => "Arménia",
    "AW" => "Aruba",
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
    "BM" => "Bermudas",
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
    "CW" => "Curaçau",
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
    "GS" => "Geórgia do Sul e Ilhas Sandwich do Sul",
    "GI" => "Gibraltar",
    "GD" => "Granada",
    "GR" => "Grécia",
    "GL" => "Gronelândia",
    "GP" => "Guadalupe",
    "GU" => "Guam",
    "GT" => "Guatemala",
    "GG" => "Guernsey",
    "GY" => "Guiana",
    "GF" => "Guiana Francesa",
    "GN" => "Guiné",
    "GQ" => "Guiné Equatorial",
    "GW" => "Guiné-Bissau",
    "HT" => "Haiti",
    "HN" => "Honduras",
    "HK" => "Hong Kong",
    "HU" => "Hungria",
    "YE" => "Iémen",
    "IM" => "Ilha de Man",
    "NF" => "Ilha Norfolk",
    "CX" => "Ilha Natal",
    "BV" => "Ilha Bouvet",
    "HM" => "Ilha Heard e Ilhas McDonald",
    "KY" => "Ilhas Caimão",
    "CC" => "Ilhas Cocos (Keeling)",
    "CK" => "Ilhas Cook",
    "FO" => "Ilhas Faroé",
    "FK" => "Ilhas Malvinas",
    "MP" => "Ilhas Marianas do Norte",
    "MH" => "Ilhas Marshall",
    "UM" => "Ilhas Menores Distantes dos Estados Unidos",
    "PN" => "Ilhas Pitcairn",
    "SB" => "Ilhas Salomão",
    "TC" => "Ilhas Turcas e Caicos",
    "VG" => "Ilhas Virgens Britânicas",
    "VI" => "Ilhas Virgens dos EUA",
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
    "BQ" => "Países Baixos Caribenhos",
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
    "PM" => "Saint Pierre e Miquelon",
    "WS" => "Samoa",
    "AS" => "Samoa Americana",
    "BL" => "São Bartolomeu",
    "KN" => "São Cristóvão e Neves",
    "SM" => "São Marinho",
    "MF" => "São Martinho",
    "SX" => "São Martinho (Países Baixos)",
    "ST" => "São Tomé e Príncipe",
    "VC" => "São Vicente e Granadinas",
    "SH" => "Santa Helena",
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
    "SJ" => "Svalbard e Jan Mayen",
    "TH" => "Tailândia",
    "TW" => "Taiwan",
    "TJ" => "Tajiquistão",
    "TZ" => "Tanzânia",
    "IO" => "Território Britânico do Oceano Índico",
    "TF" => "Territórios Franceses do Sul",
    "TL" => "Timor-Leste",
    "TG" => "Togo",
    "TK" => "Toquelau",
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

// ── Feedback ──
$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'updated'   => ['success', 'bi-check-circle-fill', 'Utilizador actualizado com sucesso!'],
    'error'     => ['danger',  'bi-x-circle-fill',     'Ocorreu um erro. Tenta novamente.'],
    default     => null,
};

$fullname = trim($user['first_name'] . ' ' . ($user['second_name'] ?? ''));
$ini      = adm_initials($user['first_name'], $user['second_name'] ?? '');
$color    = adm_avatar_color($fullname);
$member_since = date('d/m/Y', strtotime($user['creat_user']));

// ── Status options ──
$status_options = [
    'approved' => ['label' => 'Aprovado', 'icon' => 'bi-check-circle-fill', 'color' => '#22c55e'],
    'suspended' => ['label' => 'Suspenso', 'icon' => 'bi-exclamation-triangle-fill', 'color' => '#ef4444'],
    'rejected' => ['label' => 'Rejeitado', 'icon' => 'bi-exclamation-triangle-fill', 'color' => '#ef4444'],
    'blocked' => ['label' => 'Bloqueado', 'icon' => 'bi-lock-fill', 'color' => '#6b7280'],
    'inactive' => ['label' => 'Inactivo', 'icon' => 'bi-person-slash', 'color' => '#3b82f6'],
    'pending_plan' => ['label' => 'Plano Pendente', 'icon' => 'bi-hourglass-split', 'color' => '#eab308'],
];

// ── Trust score classes ──
$trust_class = match (true) {
    $user['trust_score'] >= 80 => 'success',
    $user['trust_score'] >= 50 => 'warning',
    default => 'danger'
};
?>

<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <title>Editar Utilizador — <?php echo APP_NAME; ?> Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <style>
        /* Hero Card */
        .hero-card {
            background: linear-gradient(135deg, #0f0f17 0%, #1a1a2e 50%, #16213e 100%);
            border-radius: 24px;
            padding: 28px 32px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .05);
        }

        .hero-card::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 280px;
            height: 280px;
            background: radial-gradient(circle, rgba(255, 0, 137, .2) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero-card::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: 20%;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(108, 99, 255, .12) 0%, transparent 70%);
            pointer-events: none;
        }

        .avatar-wrapper {
            position: relative;
            display: inline-block;
        }

        .avatar-img {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 0, 137, .4);
            box-shadow: 0 0 0 6px rgba(255, 0, 137, .1), 0 8px 24px rgba(0, 0, 0, .3);
            cursor: pointer;
            transition: all .3s;
        }

        .avatar-img:hover {
            transform: scale(1.02);
            border-color: #FF0089;
        }

        .avatar-ini {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.8rem;
            color: #fff;
            cursor: pointer;
            transition: all .3s;
            box-shadow: 0 0 0 6px rgba(255, 0, 137, .1), 0 8px 24px rgba(0, 0, 0, .3);
        }

        .avatar-ini:hover {
            transform: scale(1.02);
            filter: brightness(1.05);
        }

        .avatar-edit-badge {
            position: absolute;
            bottom: 4px;
            right: 4px;
            background: #FF0089;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid #1a1a2e;
            transition: all .2s;
        }

        .avatar-edit-badge:hover {
            transform: scale(1.1);
            background: #ff338f;
        }

        /* Status Badge Hero */
        .hero-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 100px;
            font-size: .75rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .hero-status-active {
            background: rgba(34, 197, 94, .2);
            color: #4ade80;
        }

        .hero-status-suspended {
            background: rgba(239, 68, 68, .2);
            color: #f87171;
        }

        .hero-status-blocked {
            background: rgba(107, 114, 128, .2);
            color: #9ca3af;
        }

        .hero-status-inactive {
            background: rgba(59, 130, 246, .2);
            color: #93c5fd;
        }

        .hero-status-pending_plan {
            background: rgba(234, 179, 8, .2);
            color: #facc15;
        }

        /* Form Card */
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
            transition: all .2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #FF0089;
            box-shadow: 0 0 0 3px rgba(255, 0, 137, .1);
        }

        /* Info Stats */
        .info-stat {
            background: rgba(255, 0, 137, .04);
            border-radius: 14px;
            padding: 12px 16px;
            margin-bottom: 12px;
        }

        .info-stat-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #888;
            margin-bottom: 4px;
        }

        .info-stat-value {
            font-size: .9rem;
            font-weight: 600;
        }

        /* Trust Score */
        .trust-score {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 100px;
            font-size: .75rem;
            font-weight: 600;
        }

        .trust-high {
            background: rgba(34, 197, 94, .1);
            color: #22c55e;
        }

        .trust-medium {
            background: rgba(234, 179, 8, .1);
            color: #eab308;
        }

        .trust-low {
            background: rgba(239, 68, 68, .1);
            color: #ef4444;
        }

        /* Artist Mini Card */
        .artist-mini {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(255, 0, 137, .03);
            border-radius: 14px;
            margin-bottom: 8px;
            transition: all .2s;
        }

        .artist-mini:hover {
            background: rgba(255, 0, 137, .08);
        }

        .artist-mini-img {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            object-fit: cover;
        }

        .artist-mini-ini {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: .8rem;
            color: #fff;
        }

        /* Release Mini */
        .release-mini {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
        }

        .release-mini:last-child {
            border-bottom: none;
        }

        .release-mini-cover {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            object-fit: cover;
        }

        /* Switch */
        .form-switch .form-check-input {
            width: 40px;
            height: 20px;
            cursor: pointer;
        }

        .form-switch .form-check-input:checked {
            background-color: #FF0089;
            border-color: #FF0089;
        }

        /* Action Buttons */
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

        .action-btn-secondary:hover {
            background: rgba(255, 0, 137, .05);
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
                        <h2 class="h4 mb-1"><i class="bi bi-person-lines-fill me-2"></i>Perfil do Utilizador</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                                        class="text-secondary">Utilizadores</a></li>
                                <li class="breadcrumb-item active text-white-stable">
                                    <?php echo htmlspecialchars($user['first_name']); ?></li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <!-- Hero Card -->
                <div class="hero-card">
                    <div class="d-flex flex-wrap align-items-center gap-4 position-relative" style="z-index: 1">
                        <!-- Avatar -->
                        <div class="avatar-wrapper">
                            <?php if (!empty($user['photo_user'])): ?>
                                <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($user['photo_user']); ?>"
                                    class="avatar-img" id="avatar-preview" alt="Avatar"
                                    onclick="document.getElementById('avatar-input').click()">
                            <?php else: ?>
                                <div class="avatar-ini" id="avatar-placeholder" style="background:<?php echo $color; ?>"
                                    onclick="document.getElementById('avatar-input').click()">
                                    <?php echo $ini; ?>
                                </div>
                            <?php endif; ?>
                            <div class="avatar-edit-badge" onclick="document.getElementById('avatar-input').click()">
                                <i class="bi bi-camera-fill" style="font-size: .75rem;"></i>
                            </div>
                            <input type="file" id="avatar-input" name="photo_user" accept="image/*" class="d-none">
                        </div>

                        <!-- Info -->
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                <h3 class="mb-0 fw-bold" style="color: #fff;"><?php echo htmlspecialchars($fullname); ?>
                                </h3>
                                <span class="hero-status hero-status-<?php echo $user['status_user']; ?>">
                                    <i
                                        class="bi <?php echo $status_options[$user['status_user']]['icon'] ?? 'bi-person'; ?>"></i>
                                    <?php echo $status_options[$user['status_user']]['label'] ?? ucfirst($user['status_user']); ?>
                                </span>
                                <span class="trust-score trust-<?php echo $trust_class; ?>">
                                    <i class="bi bi-shield-check"></i>
                                    Score: <?php echo $user['trust_score']; ?>/100
                                </span>
                            </div>
                            <div class="d-flex flex-wrap gap-3" style="font-size: .8rem; color: rgba(255,255,255,.6);">
                                <span><i
                                        class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($user['email_user']); ?></span>
                                <?php if ($user['tel_user']): ?>
                                    <span><i
                                            class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($user['tel_user']); ?></span>
                                <?php endif; ?>
                                <span><i class="bi bi-calendar3 me-1"></i>Membro desde
                                    <?php echo $member_since; ?></span>
                                <?php if ($user['last_login_at']): ?>
                                    <span><i class="bi bi-clock-history me-1"></i>Último acesso:
                                        <?php echo date('d/m/Y H:i', strtotime($user['last_login_at'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="d-flex gap-2">
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $id; ?>"
                                class="action-btn action-btn-secondary"
                                style="background: rgba(255,255,255,.1); color:#fff;">
                                <i class="bi bi-eye me-1"></i>Ver perfil
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Feedback -->
                <?php if ($feedback): ?>
                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3">
                        <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                        <?php echo htmlspecialchars($feedback[2]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Formulário Principal -->
                    <div class="col-xl-8">
                        <div class="form-card">
                            <form id="edit-user-form" method="POST"
                                action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/edit-process"
                                enctype="multipart/form-data">
                                <input type="hidden" name="id_users" value="<?php echo $id; ?>">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                                <!-- Informações Pessoais -->
                                <div class="section-title">
                                    <i class="bi bi-person-badge"></i>Informações Pessoais
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Nome Completo <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i
                                                    class="bi bi-person"></i></span>
                                            <input type="text" class="form-control" name="first_name"
                                                value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Apelido</label>
                                        <input type="text" class="form-control" name="second_name"
                                            value="<?php echo htmlspecialchars($user['second_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Nome de Utilizador</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent">@</span>
                                            <input type="text" class="form-control" name="user_name"
                                                value="<?php echo htmlspecialchars($user['user_name'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">E-mail <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i
                                                    class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" name="email_user"
                                                value="<?php echo htmlspecialchars($user['email_user']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Telefone</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i
                                                    class="bi bi-whatsapp"></i></span>
                                            <input type="tel" class="form-control" name="tel_user"
                                                value="<?php echo htmlspecialchars($user['tel_user'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Sobre</label>
                                        <textarea class="form-control" name="about_user"
                                            rows="2"><?php echo htmlspecialchars($user['about_user'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <!-- Localização & Plano -->
                                <div class="section-title">
                                    <i class="bi bi-geo-alt"></i>Localização & Plano
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">País</label>
                                        <select class="form-select" name="country_user">
                                            <option value="">Selecionar país</option>
                                            <?php foreach ($paises as $code => $name): ?>
                                                <option value="<?php echo $code; ?>"
                                                    <?php echo ($user['country_user'] ?? '') === $code ? 'selected' : ''; ?>>
                                                    <?php echo $name; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Cidade</label>
                                        <input type="text" class="form-control" name="city_user"
                                            value="<?php echo htmlspecialchars($user['city_user'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Plano</label>
                                        <select class="form-select" name="plan_selected">
                                            <option value="">Sem plano</option>
                                            <?php foreach ($plans as $plan): ?>
                                                <option value="<?php echo $plan['id_plan']; ?>"
                                                    <?php echo ($user['plan_selected'] ?? 0) == $plan['id_plan'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($plan['name_plan']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Estado da Conta</label>
                                        <select class="form-select" name="status_user">
                                            <?php foreach ($status_options as $key => $opt): ?>
                                                <option value="<?php echo $key; ?>"
                                                    <?php echo $user['status_user'] === $key ? 'selected' : ''; ?>>
                                                    <?php echo $opt['label']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Notificações -->
                                <div class="section-title">
                                    <i class="bi bi-bell"></i>Notificações
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="notif_email" value="1"
                                                <?php echo $user['notif_email'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">E-mail</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="notif_push" value="1"
                                                <?php echo $user['notif_push'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Push</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="notif_weekly"
                                                value="1" <?php echo $user['notif_weekly'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Resumo semanal</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="notif_releases"
                                                value="1" <?php echo $user['notif_releases'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Lançamentos</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ações -->
                                <div class="d-flex justify-content-end gap-3 pt-3 border-top">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                                        class="action-btn action-btn-secondary">
                                        <i class="bi bi-arrow-left me-1"></i>Cancelar
                                    </a>
                                    <button type="submit" class="action-btn action-btn-primary">
                                        <i class="bi bi-check-lg me-1"></i>Guardar Alterações
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sidebar Direita - Informações adicionais -->
                    <div class="col-xl-4">
                        <!-- Estatísticas -->
                        <div class="form-card mb-4">
                            <div class="section-title">
                                <i class="bi bi-graph-up"></i>Estatísticas
                            </div>
                            <div class="info-stat">
                                <div class="info-stat-label">ID do Utilizador</div>
                                <div class="info-stat-value">
                                    <code>#<?php echo str_pad($id, 6, '0', STR_PAD_LEFT); ?></code>
                                </div>
                            </div>
                            <div class="info-stat">
                                <div class="info-stat-label">Membro desde</div>
                                <div class="info-stat-value">
                                    <?php echo date('d/m/Y \à\s H:i', strtotime($user['creat_user'])); ?></div>
                            </div>
                            <?php if ($user['modif_user']): ?>
                                <div class="info-stat">
                                    <div class="info-stat-label">Última atualização</div>
                                    <div class="info-stat-value">
                                        <?php echo date('d/m/Y \à\s H:i', strtotime($user['modif_user'])); ?></div>
                                </div>
                            <?php endif; ?>
                            <div class="info-stat">
                                <div class="info-stat-label">Onboarding concluído</div>
                                <div class="info-stat-value">
                                    <?php if ($user['onboarding_done']): ?>
                                        <span class="badge bg-success"><i class="bi bi-check"></i> Sim</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="bi bi-clock"></i> Não</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-stat">
                                <div class="info-stat-label">2FA Activado</div>
                                <div class="info-stat-value">
                                    <?php if ($user['two_factor_enabled']): ?>
                                        <span class="badge bg-success"><i class="bi bi-check"></i> Activado</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="bi bi-x"></i> Desactivado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Artistas -->
                        <div class="form-card mb-4">
                            <div class="section-title">
                                <i class="bi bi-mic"></i>Artistas
                                <span class="ms-auto badge bg-secondary"><?php echo count($artist_list); ?></span>
                            </div>
                            <?php if (empty($artist_list)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-person-x fs-1 opacity-25"></i>
                                    <p class="small mb-0 mt-2">Nenhum artista registado</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($artist_list as $art): ?>
                                    <div class="artist-mini">
                                        <?php if (!empty($art['photo_artist'])): ?>
                                            <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/artists/<?php echo htmlspecialchars($art['photo_artist']); ?>"
                                                class="artist-mini-img" alt="">
                                        <?php else: ?>
                                            <div class="artist-mini-ini"
                                                style="background: <?php echo adm_avatar_color($art['stage_name']); ?>">
                                                <?php echo mb_strtoupper(mb_substr($art['stage_name'], 0, 2)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?php echo htmlspecialchars($art['stage_name']); ?></div>
                                            <span
                                                class="badge bg-<?php echo $art['status_artist'] === 'active' ? 'success' : 'secondary'; ?>"
                                                style="font-size: .65rem;">
                                                <?php echo ucfirst($art['status_artist']); ?>
                                            </span>
                                        </div>
                                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artists/edit?id=<?php echo $art['id_artist']; ?>"
                                            class="text-muted">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="mt-2 text-end">
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/accounts-users?id=<?php echo $id; ?>"
                                    class="small text-wasom">
                                    <i class="bi bi-arrow-right"></i> Ver todos os artistas
                                </a>
                            </div>
                        </div>

                        <!-- Lançamentos Recentes -->
                        <?php if (!empty($release_list)): ?>
                            <div class="form-card">
                                <div class="section-title">
                                    <i class="bi bi-vinyl"></i>Lançamentos Recentes
                                    <span class="ms-auto badge bg-secondary"><?php echo count($release_list); ?></span>
                                </div>
                                <?php foreach ($release_list as $rel): ?>
                                    <div class="release-mini">
                                        <?php if (!empty($rel['img_cover'])): ?>
                                            <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/covers/<?php echo htmlspecialchars($rel['img_cover']); ?>"
                                                class="release-mini-cover" alt="">
                                        <?php else: ?>
                                            <div
                                                class="release-mini-cover d-flex align-items-center justify-content-center bg-light">
                                                <i class="bi bi-music-note text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold small"><?php echo htmlspecialchars($rel['title_album']); ?>
                                            </div>
                                            <span class="badge bg-<?php echo match ($rel['status_album']) {
                                                                        'approved' => 'success',
                                                                        'pending' => 'warning',
                                                                        'rejected' => 'danger',
                                                                        default => 'secondary'
                                                                    }; ?>">
                                                <?php echo ucfirst($rel['status_album']); ?>
                                            </span>
                                        </div>
                                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/music/view?id=<?php echo $rel['id_album']; ?>"
                                            class="text-muted">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
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
        window.__BASE_URL__ = '<?php echo APP_URL; ?>';
        window.__ADMIN_PATH__ = '<?php echo ADMIN_PATH; ?>';

        // Preview de avatar
        document.getElementById('avatar-input')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('avatar-preview');
                    const placeholder = document.getElementById('avatar-placeholder');
                    if (preview) {
                        preview.src = event.target.result;
                        preview.classList.remove('d-none');
                        if (placeholder) placeholder.style.display = 'none';
                    } else if (placeholder) {
                        placeholder.style.backgroundImage = `url(${event.target.result})`;
                        placeholder.style.backgroundSize = 'cover';
                        placeholder.innerHTML = '';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>

</html>