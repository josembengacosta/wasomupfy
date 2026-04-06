<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Todos os Utilizadores
// Arquivo: admin/pages/users/all-users.php
// Rota:    admin/users
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'users.view');

// ── Feedback ──
$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'updated'   => ['success', 'bi-check-circle',  'Utilizador actualizado com sucesso.'],
    'deleted'   => ['success', 'bi-trash',          'Utilizador removido com sucesso.'],
    'blocked'   => ['warning', 'bi-lock',           'Utilizador bloqueado.'],
    'unblocked' => ['success', 'bi-unlock',         'Utilizador desbloqueado.'],
    'error'     => ['danger',  'bi-x-circle',       'Ocorreu um erro. Tenta novamente.'],
    default     => null,
};

// ── Stats globais ──
$stats = $db->query("
    SELECT
        COUNT(*)                            AS total,
        SUM(status_user = 'active')         AS active,
        SUM(status_user = 'inactive')       AS inactive,
        SUM(status_user = 'blocked')        AS blocked,
        SUM(status_user = 'processing')     AS processing,
        SUM(status_user = 'suspended')      AS suspended,
        SUM(status_user = 'fraud')          AS fraud,
        SUM(status_user = 'pending_plan')   AS pending_plan,
        SUM(creat_user >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS new_this_month
    FROM _users
")->fetch();

// ── Filtros ──
$per_page      = 20;
$page          = max(1, (int)($_GET['page']    ?? 1));
$f_id          = trim($_GET['id']      ?? '');
$f_name        = trim($_GET['name']    ?? '');
$f_email       = trim($_GET['email']   ?? '');
$f_country     = trim($_GET['country'] ?? '');
$f_status      = trim($_GET['status']  ?? '');
$f_plan        = trim($_GET['plan']    ?? '');
$sort_col      = in_array($_GET['sort'] ?? '', ['id_users', 'first_name', 'email_user', 'creat_user', 'status_user']) ? $_GET['sort'] : 'creat_user';
$sort_dir      = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$where  = [];
$params = [];

if ($f_id !== '') {
    $where[]  = 'u.id_users = ?';
    $params[] = (int)$f_id;
}
if ($f_name !== '') {
    $where[]  = "CONCAT(u.first_name,' ',COALESCE(u.second_name,'')) LIKE ?";
    $params[] = '%' . $f_name . '%';
}
if ($f_email !== '') {
    $where[]  = 'u.email_user LIKE ?';
    $params[] = '%' . $f_email . '%';
}
if ($f_country !== '') {
    $where[]  = 'u.country_user = ?';
    $params[] = $f_country;
}
if ($f_status !== '') {
    $where[]  = 'u.status_user = ?';
    $params[] = $f_status;
}
if ($f_plan !== '') {
    $where[]  = 'pl.id_plan = ?';
    $params[] = (int)$f_plan;
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── Planos disponíveis para filtro ──
$plans_list = $db->query("SELECT id_plan, name_plan FROM _plans ORDER BY name_plan")->fetchAll();

// ── Contagem ──
$count_sql = "
    SELECT COUNT(DISTINCT u.id_users)
    FROM _users u
    LEFT JOIN _user_plan up ON up.id_users = u.id_users
    LEFT JOIN _plans pl     ON pl.id_plan = up.id_plan
    $sql_where
";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_filtered = (int)$count_stmt->fetchColumn();
$total_pages    = max(1, (int)ceil($total_filtered / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;

// ── Dados ──
$stmt = $db->prepare("
    SELECT
        u.id_users, u.first_name, u.second_name, u.user_name,
        u.email_user, u.tel_user, u.photo_user,
        u.country_user, u.city_user, u.status_user,
        u.creat_user, u.modif_user,
        pl.name_plan,
        us.last_login_at, us.login_attempts
    FROM _users u
    LEFT JOIN (
        SELECT id_users, id_plan
        FROM _user_plan
        WHERE (id_users, started_at) IN (
            SELECT id_users, MAX(started_at) FROM _user_plan GROUP BY id_users
        )
    ) up ON up.id_users = u.id_users
    LEFT JOIN _plans pl     ON pl.id_plan = up.id_plan
    LEFT JOIN _users_security us ON us.id_users = u.id_users
    $sql_where
    ORDER BY u.$sort_col $sort_dir
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$users_list = $stmt->fetchAll();

// ── Helpers ──
function usr_status_badge(string $s): string
{
    return match ($s) {
        'active'    => '<span class="badge usr-s-active">Activo</span>',
        'inactive'  => '<span class="badge usr-s-inactive">Inactivo</span>',
        'suspended' => '<span class="badge usr-s-suspended">Suspenso</span>',
        'processing'    => '<span class="badge usr-s-processing">Em revisão</span>',
        'blocked'   => '<span class="badge usr-s-blocked">Bloqueado</span>',
        'fraud'  => '<span class="badge usr-s-fraud">Fraude</span>',
        'pending_plan'  => '<span class="badge usr-s-pending_plan">Plano Pendente</span>',
        default     => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}

function usr_next_sort(string $col, string $current_col, string $current_dir): string
{
    if ($col !== $current_col) return 'asc';
    return $current_dir === 'asc' ? 'desc' : 'asc';
}
function usr_sort_icon(string $col, string $current_col, string $current_dir): string
{
    if ($col !== $current_col) return '';
    return $current_dir === 'asc' ? ' ▲' : ' ▼';
}

function usr_sort_url(string $col, string $current_col, string $current_dir, array $get): string
{
    $dir = ($col === $current_col && $current_dir === 'asc') ? 'desc' : 'asc';
    return '?' . http_build_query(array_merge($get, ['sort' => $col, 'dir' => $dir, 'page' => 1]));
}
$paises = [
    "AF" => "Afeganistão",
    "ZA" => "África do Sul",
    "AL" => "Albânia",
    "DE" => "Alemanha",
    "AD" => "Andorra",
    "AO" => "Angola",
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
    "KR" => "Coreia do Sul",
    "KP" => "Coreia do Norte",
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
    "GT" => "Guatemala",
    "GW" => "Guiné-Bissau",
    "GQ" => "Guiné Equatorial",
    "GN" => "Guiné",
    "GY" => "Guiana",
    "HT" => "Haiti",
    "HN" => "Honduras",
    "HU" => "Hungria",
    "YE" => "Iémen",
    "IN" => "Índia",
    "ID" => "Indonésia",
    "IQ" => "Iraque",
    "IE" => "Irlanda",
    "IR" => "Irão",
    "IS" => "Islândia",
    "IL" => "Israel",
    "IT" => "Itália",
    "JM" => "Jamaica",
    "JP" => "Japão",
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
    "MK" => "Macedónia do Norte",
    "MG" => "Madagáscar",
    "MY" => "Malásia",
    "MW" => "Malawi",
    "MV" => "Maldivas",
    "ML" => "Mali",
    "MT" => "Malta",
    "MA" => "Marrocos",
    "MH" => "Ilhas Marshall",
    "MU" => "Maurícia",
    "MR" => "Mauritânia",
    "MX" => "México",
    "FM" => "Micronésia",
    "MZ" => "Moçambique",
    "MD" => "Moldávia",
    "MC" => "Mónaco",
    "MN" => "Mongólia",
    "ME" => "Montenegro",
    "MM" => "Myanmar",
    "NA" => "Namíbia",
    "NR" => "Nauru",
    "NP" => "Nepal",
    "NI" => "Nicarágua",
    "NE" => "Níger",
    "NG" => "Nigéria",
    "NO" => "Noruega",
    "NZ" => "Nova Zelândia",
    "OM" => "Omã",
    "NL" => "Países Baixos",
    "PW" => "Palau",
    "PK" => "Paquistão",
    "PA" => "Panamá",
    "PG" => "Papua-Nova Guiné",
    "PY" => "Paraguai",
    "PE" => "Peru",
    "PL" => "Polónia",
    "PT" => "Portugal",
    "KE" => "Quénia",
    "KG" => "Quirguistão",
    "KI" => "Quiribati",
    "GB" => "Reino Unido",
    "CF" => "República Centro-Africana",
    "CZ" => "República Checa",
    "DO" => "República Dominicana",
    "RO" => "Roménia",
    "RW" => "Ruanda",
    "RU" => "Rússia",
    "SB" => "Ilhas Salomão",
    "WS" => "Samoa",
    "SM" => "São Marinho",
    "LC" => "Santa Lúcia",
    "KN" => "São Cristóvão e Nevis",
    "VC" => "São Vicente e Granadinas",
    "ST" => "São Tomé e Príncipe",
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
    "TL" => "Timor-Leste",
    "TG" => "Togo",
    "TO" => "Tonga",
    "TT" => "Trinidad e Tobago",
    "TN" => "Tunísia",
    "TM" => "Turquemenistão",
    "TR" => "Turquia",
    "TV" => "Tuvalu",
    "UA" => "Ucrânia",
    "UG" => "Uganda",
    "UY" => "Uruguai",
    "UZ" => "Uzbequistão",
    "VU" => "Vanuatu",
    "VA" => "Vaticano",
    "VE" => "Venezuela",
    "VN" => "Vietname",
    "ZM" => "Zâmbia",
    "ZW" => "Zimbabué"
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
    <title>Utilizadores — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <style>
        /* Status badges */
        .usr-s-active {
            background: rgba(34, 197, 94, .15);
            color: #166534;
        }

        .usr-s-suspended {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
        }

        .usr-s-processing {
            background: rgba(234, 179, 8, .15);
            color: #92400e;
        }

        .usr-s-blocked {
            background: rgba(107, 114, 128, .15);
            color: #374151;
        }

        .usr-s-inactive {
            background: rgba(59, 130, 246, .15);
            color: #1e40af;
        }

        .usr-s-fraud {
            background: rgba(115, 27, 27, 0.15);
            color: #2b2c2d;
        }

        .dark-mode .usr-s-active {
            background: rgba(34, 197, 94, .18);
            color: #4ade80;
        }

        .dark-mode .usr-s-suspended {
            background: rgba(239, 68, 68, .18);
            color: #f87171;
        }

        .dark-mode .usr-s-processing {
            background: rgba(234, 179, 8, .18);
            color: #facc15;
        }

        .dark-mode .usr-s-blocked {
            background: rgba(107, 114, 128, .18);
            color: #9ca3af;
        }

        .dark-mode .usr-s-inactive {
            background: rgba(59, 130, 246, .18);
            color: #93c5fd;
        }

        .dark-mode .usr-s-fraud {
            background: rgba(115, 27, 27, 0.15);
            color: #fff;
        }

        /* Stat cards */
        .usr-stat {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .usr-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .usr-stat-num {
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1;
        }

        .usr-stat-lbl {
            font-size: .74rem;
            opacity: .6;
            margin-top: 2px;
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
        #users-table th {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
            user-select: none;
        }

        #users-table th:hover {
            opacity: .75;
        }

        #users-table td {
            font-size: .82rem;
            vertical-align: middle;
        }

        /* Avatar */
        .usr-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 0, 137, .2);
        }

        .usr-avatar-ini {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .65rem;
            color: #fff;
            flex-shrink: 0;
        }

        /* Dropdown acções — fix hover tremer */
        .actions-dropdown .dropdown-menu {
            position: fixed !important;
            z-index: 1055;
            min-width: 170px;
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

        #users-table tbody tr:has(.dropdown.show) {
            background: var(--card-bg, #fff) !important;
        }

        /* Paginação */
        .usr-pagination .page-link {
            border-radius: 8px !important;
            margin: 0 2px;
            font-size: .8rem;
        }

        /* Empty */
        .usr-empty {
            text-align: center;
            padding: 48px 24px;
            opacity: .4;
        }

        .usr-empty i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 12px;
        }

        /* Dark mode */
        .dark-mode .filter-card,
        .dark-mode .usr-stat {
            background: var(--dark-card, #1a1a27);
            border-color: var(--dark-border, #2e2e42);
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
                            <i class="bi bi-people-fill me-2"></i>Utilizadores
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>" class="text-secondary">Home</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable">Utilizadores</li>
                            </ol>
                        </nav>
                    </div>
                    <?php if (hasPermission($admin_id, 'users.edit')): ?>
                        <div class="col-auto ms-auto">
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/add" class="btn btn-sm text-white"
                                style="background:#FF0089;border-color:#FF0089">
                                <i class="bi bi-person-plus me-1"></i>Adicionar Utilizador
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Feedback -->
                <?php if ($feedback): ?>
                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3">
                        <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                        <?php echo htmlspecialchars($feedback[2]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stat cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-2">
                        <div class="usr-stat">
                            <div class="usr-stat-icon" style="background:rgba(255,0,137,.1)">
                                <i class="bi bi-people" style="color:#FF0089"></i>
                            </div>
                            <div>
                                <div class="usr-stat-num"><?php echo number_format($stats['total']); ?></div>
                                <div class="usr-stat-lbl">Total</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="usr-stat">
                            <div class="usr-stat-icon" style="background:rgba(34,197,94,.1)">
                                <i class="bi bi-person-check text-success"></i>
                            </div>
                            <div>
                                <div class="usr-stat-num"><?php echo number_format($stats['active']); ?></div>
                                <div class="usr-stat-lbl">Activos</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="usr-stat">
                            <div class="usr-stat-icon" style="background:rgba(239,68,68,.1)">
                                <i class="bi bi-person-slash text-danger"></i>
                            </div>
                            <div>
                                <div class="usr-stat-num">
                                    <?php echo number_format($stats['suspended'] or $stats['inactive']); ?></div>
                                <div class="usr-stat-lbl">Suspensos/Inativos</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="usr-stat">
                            <div class="usr-stat-icon" style="background:rgba(234,179,8,.1)">
                                <i class="bi bi-hourglass-split text-warning"></i>
                            </div>
                            <div>
                                <div class="usr-stat-num"><?php echo number_format($stats['processing']); ?></div>
                                <div class="usr-stat-lbl">Em revisão</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="usr-stat">
                            <div class="usr-stat-icon" style="background:rgba(107,114,128,.1)">
                                <i class="bi bi-lock text-secondary"></i>
                            </div>
                            <div>
                                <div class="usr-stat-num">
                                    <?php echo number_format($stats['blocked'] or $stats['fraud']); ?></div>
                                <div class="usr-stat-lbl">Bloqueados</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="usr-stat">
                            <div class="usr-stat-icon" style="background:rgba(59,130,246,.1)">
                                <i class="bi bi-person-add text-primary"></i>
                            </div>
                            <div>
                                <div class="usr-stat-num"><?php echo number_format($stats['new_this_month']); ?></div>
                                <div class="usr-stat-lbl">Novos (30d)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="filter-card">
                    <form method="GET" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users" id="filter-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-1">
                                <label class="form-label">ID</label>
                                <input type="number" class="form-control form-control-sm" name="id"
                                    value="<?php echo htmlspecialchars($f_id); ?>" placeholder="#" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nome</label>
                                <input type="text" class="form-control form-control-sm" name="name"
                                    value="<?php echo htmlspecialchars($f_name); ?>" placeholder="Primeiro ou apelido…"
                                    id="inp-name" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">E-mail</label>
                                <input type="text" class="form-control form-control-sm" name="email"
                                    value="<?php echo htmlspecialchars($f_email); ?>" placeholder="email@…"
                                    id="inp-email" />
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">País</label>
                                <select class="form-select form-select-sm" name="country">
                                    <option value="">Todos</option>
                                    <?php foreach ($paises as $code => $name): ?>
                                        <option value="<?php echo $code; ?>"
                                            <?php echo $f_country === $code ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Estado</label>
                                <select class="form-select form-select-sm" name="status">
                                    <option value="">Todos</option>
                                    <option value="active" <?php echo $f_status === 'active' ? 'selected' : ''; ?>>
                                        Activo
                                    </option>
                                    <option value="suspended"
                                        <?php echo $f_status === 'suspended' ? 'selected' : ''; ?>>
                                        Suspenso</option>
                                    <option value="review" <?php echo $f_status === 'review' ? 'selected' : ''; ?>>
                                        Revisão
                                    </option>
                                    <option value="blocked" <?php echo $f_status === 'blocked' ? 'selected' : ''; ?>>
                                        Bloqueado
                                    </option>
                                    <option value="inactive" <?php echo $f_status === 'inactive' ? 'selected' : ''; ?>>
                                        Inactivo</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Plano</label>
                                <select class="form-select form-select-sm" name="plan">
                                    <option value="">Todos</option>
                                    <?php foreach ($plans_list as $plan): ?>
                                        <option value="<?php echo $plan['id_plan']; ?>"
                                            <?php echo $f_plan == (string)$plan['id_plan'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($plan['name_plan']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1 d-flex gap-1">
                                <button type="submit" class="btn btn-sm text-white w-100"
                                    style="background:#FF0089;border-color:#FF0089">
                                    <i class="bi bi-search"></i>
                                </button>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                                    class="btn btn-sm btn-outline-secondary" title="Limpar">
                                    <i class="bi bi-x"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tabela -->
                <div class="card p-0" style="border-radius:14px;overflow:hidden">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                        style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                        <span style="font-size:.82rem;font-weight:600">
                            <?php if ($total_filtered !== (int)$stats['total']): ?>
                                <span style="color:#FF0089"><?php echo number_format($total_filtered); ?></span>
                                de <?php echo number_format($stats['total']); ?> utilizadores
                            <?php else: ?>
                                <?php echo number_format($total_filtered); ?> utilizadores
                            <?php endif; ?>
                        </span>
                        <span style="font-size:.76rem;opacity:.5">
                            Página <?php echo $page; ?> de <?php echo $total_pages; ?>
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="users-table">
                            <thead>
                                <tr>
                                    <th style="width:50px">
                                        <a href="<?php echo usr_sort_url('id_users', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">
                                            ID<?php echo usr_sort_icon('id_users', $sort_col, $sort_dir); ?>
                                        </a>
                                    </th>
                                    <th style="width:40px">Foto</th>
                                    <th>
                                        <a href="<?php echo usr_sort_url('first_name', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">
                                            Nome<?php echo usr_sort_icon('first_name', $sort_col, $sort_dir); ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="<?php echo usr_sort_url('email_user', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">
                                            E-mail<?php echo usr_sort_icon('email_user', $sort_col, $sort_dir); ?>
                                        </a>
                                    </th>
                                    <th>Tel</th>
                                    <th>País</th>
                                    <th>Plano</th>
                                    <th>
                                        <a href="<?php echo usr_sort_url('status_user', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">
                                            Estado<?php echo usr_sort_icon('status_user', $sort_col, $sort_dir); ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="<?php echo usr_sort_url('creat_user', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">
                                            Criado<?php echo usr_sort_icon('creat_user', $sort_col, $sort_dir); ?>
                                        </a>
                                    </th>
                                    <th style="width:50px">Acções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users_list)): ?>
                                    <tr>
                                        <td colspan="9">
                                            <div class="usr-empty">
                                                <i class="bi bi-people"></i>
                                                Nenhum utilizador encontrado para os filtros aplicados.
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users_list as $usr):
                                        $fullname = trim($usr['first_name'] . ' ' . ($usr['second_name'] ?? ''));
                                        $ini      = adm_initials($usr['first_name'], $usr['second_name'] ?? '');
                                        $color    = adm_avatar_color($fullname);
                                    ?>
                                        <tr>
                                            <!-- ID -->
                                            <td>
                                                <span style="font-family:monospace;font-size:.75rem;opacity:.6">
                                                    #<?php echo $usr['id_users']; ?>
                                                </span>
                                            </td>

                                            <!-- Avatar -->
                                            <td>
                                                <?php if (!empty($usr['photo_user'])): ?>
                                                    <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($usr['photo_user']); ?>"
                                                        class="usr-avatar" alt=""
                                                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                                                    <div class="usr-avatar-ini"
                                                        style="background:<?php echo $color; ?>;display:none">
                                                        <?php echo $ini; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="usr-avatar-ini" style="background:<?php echo $color; ?>">
                                                        <?php echo $ini; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Nome + username -->
                                            <td>
                                                <div style="font-weight:600;font-size:.83rem">
                                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $usr['id_users']; ?>"
                                                        style="color:inherit;text-decoration:none">
                                                        <?php echo htmlspecialchars($fullname); ?>
                                                    </a>
                                                </div>
                                                <?php if ($usr['user_name']): ?>
                                                    <div style="font-size:.73rem;opacity:.5">
                                                        @<?php echo htmlspecialchars($usr['user_name']); ?></div>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Email -->
                                            <td>
                                                <a href="mailto:<?php echo htmlspecialchars($usr['email_user']); ?>"
                                                    style="font-size:.8rem;color:inherit">
                                                    <?php echo htmlspecialchars($usr['email_user']); ?>
                                                </a>
                                            </td>

                                            <!-- Telefone -->
                                            <td>
                                                <a href="https://wa.me/<?php echo htmlspecialchars($usr['tel_user']); ?>"
                                                    target="_blank" title="Ir para WhatsApp"
                                                    style="font-size:.8rem;color:inherit">
                                                    <?php echo htmlspecialchars($usr['tel_user']); ?>
                                                </a>
                                            </td>

                                            <!-- País -->
                                            <td style="font-size:.8rem">
                                                <?php echo htmlspecialchars($usr['country_user'] ?? '—'); ?>
                                                <?php if ($usr['city_user']): ?>
                                                    <span style="opacity:.5"> /
                                                        <?php echo htmlspecialchars($usr['city_user']); ?></span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Plano -->
                                            <td>
                                                <?php if ($usr['name_plan']): ?>
                                                    <span style="font-size:.76rem;padding:3px 8px;border-radius:20px;
                                             background:rgba(255,0,137,.08);color:#FF0089;font-weight:600">
                                                        <?php echo htmlspecialchars($usr['name_plan']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="opacity:.35;font-size:.76rem">—</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Estado -->
                                            <td><?php echo usr_status_badge($usr['status_user']); ?></td>

                                            <!-- Criado -->
                                            <td style="font-size:.78rem;white-space:nowrap">
                                                <?php echo adm_fmt_date($usr['creat_user']); ?>
                                            </td>

                                            <!-- Acções -->
                                            <td>
                                                <div class="dropdown actions-dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button"
                                                        data-bs-toggle="dropdown" data-bs-reference="toggle" title="Acções">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $usr['id_users']; ?>">
                                                                <i class="bi bi-eye text-info"></i>Visualizar
                                                            </a>
                                                        </li>
                                                        <?php if (hasPermission($admin_id, 'users.edit')): ?>
                                                            <li>
                                                                <a class="dropdown-item"
                                                                    href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/edit?id=<?php echo $usr['id_users']; ?>">
                                                                    <i class="bi bi-pencil text-warning"></i>Editar
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <hr class="dropdown-divider my-1">
                                                            </li>
                                                            <?php if ($usr['status_user'] === 'active'): ?>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/unavailable-account?id=<?php echo $usr['id_users']; ?>">
                                                                        <i class="bi bi-lock text-warning"></i>Suspender
                                                                    </a>
                                                                </li>
                                                            <?php else: ?>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/available-account?id=<?php echo $usr['id_users']; ?>">
                                                                        <i class="bi bi-unlock text-success"></i>Activar
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            <?php if ($admin_role === 'super_admin'): ?>
                                                                <li>
                                                                    <a class="dropdown-item text-danger"
                                                                        href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/delete?id=<?php echo $usr['id_users']; ?>">
                                                                        <i class="bi bi-trash text-danger"></i>Excluir
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
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
                                <ul class="pagination pagination-sm usr-pagination mb-0">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
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
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link"
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
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
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
    <script src="<?php echo APP_URL; ?>/js/lastest.min.js"></script>
    <script>
        window.__BASE_URL__ = '<?php echo APP_URL; ?>';
        window.__ADMIN_PATH__ = '<?php echo ADMIN_PATH; ?>';

        // Debounce nos campos de texto
        (function() {
            var timer;
            ['inp-name', 'inp-email'].forEach(function(id) {
                var el = document.getElementById(id);
                if (!el) return;
                el.addEventListener('input', function() {
                    clearTimeout(timer);
                    timer = setTimeout(function() {
                        document.getElementById('filter-form').submit();
                    }, 500);
                });
            });
            // Selects — submit imediato
            document.querySelectorAll('#filter-form select').forEach(function(sel) {
                sel.addEventListener('change', function() {
                    document.getElementById('filter-form').submit();
                });
            });
        })();
    </script>
</body>

</html>