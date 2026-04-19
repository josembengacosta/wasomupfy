<?php
// ═══════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Admin: Importação de Streams (Country Only)
// Arquivo: wu-panel/pages/analytics/import-streams.php
// ═══════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.edit');

if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$db   = getDB();
$csrf = $_SESSION['admin_csrf_token'];

// ── Feedback de redirect ──────────────────────────────────────────────────
$swal_on_load = null;
switch ($_GET['msg'] ?? '') {
    case 'imported':
        $swal_on_load = ['success', 'Importação concluída', 'Os dados foram importados com sucesso.'];
        break;
    case 'error':
        $swal_on_load = ['error',   'Erro na importação',   'Verifique o formato do ficheiro CSV.'];
        break;
}

// ── Estatísticas de resumo ────────────────────────────────────────────────
$stat_country  = (int)$db->query("SELECT COUNT(*) FROM _stream_country")->fetchColumn();
$stat_tracks   = (int)$db->query("SELECT COUNT(DISTINCT id_track) FROM _stream_country")->fetchColumn();

// ── Lojas activas ─────────────────────────────────────────────────────────
$stores = $db->query("
    SELECT id_store, name_store FROM _store WHERE is_active = 1 ORDER BY name_store
")->fetchAll();

// ── Faixas para selects ───────────────────────────────────────────────────
$tracks = $db->query("
    SELECT t.id_track, t.title_track, u.first_name
    FROM _track t
    JOIN _users u ON t.id_users = u.id_users
    ORDER BY t.title_track
    LIMIT 500
")->fetchAll();

// ── Lista de países (ISO) ─────────────────────────────────────────────────
$country_meta = [

    // ── África ───────────────────────────────────────────────────────────────
    'África do Sul'                  => 'za',
    'South Africa'                   => 'za',
    'Argélia'                        => 'dz',
    'Algeria'                        => 'dz',
    'Angola'                         => 'ao',
    'Benim'                          => 'bj',
    'Benin'                          => 'bj',
    'Botsuana'                       => 'bw',
    'Botswana'                       => 'bw',
    'Burkina Faso'                   => 'bf',
    'Burundi'                        => 'bi',
    'Cabo Verde'                     => 'cv',
    'Cape Verde'                     => 'cv',
    'Camarões'                       => 'cm',
    'Cameroon'                       => 'cm',
    'Chade'                          => 'td',
    'Chad'                           => 'td',
    'Comores'                        => 'km',
    'Comoros'                        => 'km',
    'Congo'                          => 'cd',
    'República Democrática do Congo' => 'cd',
    'Democratic Republic of Congo'   => 'cd',
    'República do Congo'             => 'cg',
    'Republic of Congo'              => 'cg',
    'Costa do Marfim'                => 'ci',
    'Ivory Coast'                    => 'ci',
    "Côte d'Ivoire" => 'ci',
    'Djibuti'                        => 'dj',
    'Djibouti'                       => 'dj',
    'Egito'                          => 'eg',
    'Egypt'                          => 'eg',
    'Eritreia'                       => 'er',
    'Eritrea'                        => 'er',
    'Eswatini'                       => 'sz',
    'Suazilândia'                    => 'sz',
    'Etiópia'                        => 'et',
    'Ethiopia'                       => 'et',
    'Gabão'                          => 'ga',
    'Gabon'                          => 'ga',
    'Gâmbia'                         => 'gm',
    'Gambia'                         => 'gm',
    'Gana'                           => 'gh',
    'Ghana'                          => 'gh',
    'Guiné'                          => 'gn',
    'Guinea'                         => 'gn',
    'Guiné Equatorial'               => 'gq',
    'Equatorial Guinea'              => 'gq',
    'Guiné-Bissau'                   => 'gw',
    'Guinea-Bissau'                  => 'gw',
    'Quénia'                         => 'ke',
    'Kenya'                          => 'ke',
    'Lesoto'                         => 'ls',
    'Lesotho'                        => 'ls',
    'Libéria'                        => 'lr',
    'Liberia'                        => 'lr',
    'Líbia'                          => 'ly',
    'Libya'                          => 'ly',
    'Madagáscar'                     => 'mg',
    'Madagascar'                     => 'mg',
    'Malawi'                         => 'mw',
    'Mali'                           => 'ml',
    'Mauritânia'                     => 'mr',
    'Mauritania'                     => 'mr',
    'Maurícia'                       => 'mu',
    'Mauritius'                      => 'mu',
    'Marrocos'                       => 'ma',
    'Morocco'                        => 'ma',
    'Moçambique'                     => 'mz',
    'Mozambique'                     => 'mz',
    'Namíbia'                        => 'na',
    'Namibia'                        => 'na',
    'Níger'                          => 'ne',
    'Niger'                          => 'ne',
    'Nigéria'                        => 'ng',
    'Nigeria'                        => 'ng',
    'Ruanda'                         => 'rw',
    'Rwanda'                         => 'rw',
    'São Tomé e Príncipe'            => 'st',
    'Sao Tome and Principe'          => 'st',
    'Senegal'                        => 'sn',
    'Serra Leoa'                     => 'sl',
    'Sierra Leone'                   => 'sl',
    'Seychelles'                     => 'sc',
    'Somália'                        => 'so',
    'Somalia'                        => 'so',
    'Sudão'                          => 'sd',
    'Sudan'                          => 'sd',
    'Sudão do Sul'                   => 'ss',
    'South Sudan'                    => 'ss',
    'Tanzânia'                       => 'tz',
    'Tanzania'                       => 'tz',
    'Togo'                           => 'tg',
    'Tunísia'                        => 'tn',
    'Tunisia'                        => 'tn',
    'Uganda'                         => 'ug',
    'Zâmbia'                         => 'zm',
    'Zambia'                         => 'zm',
    'Zimbabué'                       => 'zw',
    'Zimbabwe'                       => 'zw',

    // ── Américas ──────────────────────────────────────────────────────────────
    'Antígua e Barbuda'              => 'ag',
    'Antigua and Barbuda'            => 'ag',
    'Argentina'                      => 'ar',
    'Bahamas'                        => 'bs',
    'Barbados'                       => 'bb',
    'Belize'                         => 'bz',
    'Bolívia'                        => 'bo',
    'Bolivia'                        => 'bo',
    'Brasil'                         => 'br',
    'Brazil'                         => 'br',
    'Canadá'                         => 'ca',
    'Canada'                         => 'ca',
    'Chile'                          => 'cl',
    'Colômbia'                       => 'co',
    'Colombia'                       => 'co',
    'Costa Rica'                     => 'cr',
    'Cuba'                           => 'cu',
    'Dominica'                       => 'dm',
    'República Dominicana'           => 'do',
    'Dominican Republic'             => 'do',
    'Equador'                        => 'ec',
    'Ecuador'                        => 'ec',
    'El Salvador'                    => 'sv',
    'Granada'                        => 'gd',
    'Grenada'                        => 'gd',
    'Guatemala'                      => 'gt',
    'Guiana'                         => 'gy',
    'Guyana'                         => 'gy',
    'Haiti'                          => 'ht',
    'Honduras'                       => 'hn',
    'Jamaica'                        => 'jm',
    'México'                         => 'mx',
    'Mexico'                         => 'mx',
    'Nicarágua'                      => 'ni',
    'Nicaragua'                      => 'ni',
    'Panamá'                         => 'pa',
    'Panama'                         => 'pa',
    'Paraguai'                       => 'py',
    'Paraguay'                       => 'py',
    'Peru'                           => 'pe',
    'São Cristóvão e Névis'          => 'kn',
    'Saint Kitts and Nevis'          => 'kn',
    'Santa Lúcia'                    => 'lc',
    'Saint Lucia'                    => 'lc',
    'São Vicente e Granadinas'       => 'vc',
    'Saint Vincent and the Grenadines' => 'vc',
    'Suriname'                       => 'sr',
    'Trinidad e Tobago'              => 'tt',
    'Trinidad and Tobago'            => 'tt',
    'Estados Unidos'                 => 'us',
    'United States'                  => 'us',
    'USA' => 'us',
    'Uruguai'                        => 'uy',
    'Uruguay'                        => 'uy',
    'Venezuela'                      => 've',

    // ── Europa ────────────────────────────────────────────────────────────────
    'Albânia'                        => 'al',
    'Albania'                        => 'al',
    'Alemanha'                       => 'de',
    'Germany'                        => 'de',
    'Andorra'                        => 'ad',
    'Áustria'                        => 'at',
    'Austria'                        => 'at',
    'Bielorrússia'                   => 'by',
    'Belarus'                        => 'by',
    'Bélgica'                        => 'be',
    'Belgium'                        => 'be',
    'Bósnia e Herzegovina'           => 'ba',
    'Bosnia and Herzegovina'         => 'ba',
    'Bulgária'                       => 'bg',
    'Bulgaria'                       => 'bg',
    'Croácia'                        => 'hr',
    'Croatia'                        => 'hr',
    'Chipre'                         => 'cy',
    'Cyprus'                         => 'cy',
    'República Checa'                => 'cz',
    'Czech Republic'                 => 'cz',
    'Czechia' => 'cz',
    'Dinamarca'                      => 'dk',
    'Denmark'                        => 'dk',
    'Eslováquia'                     => 'sk',
    'Slovakia'                       => 'sk',
    'Eslovénia'                      => 'si',
    'Slovenia'                       => 'si',
    'Espanha'                        => 'es',
    'Spain'                          => 'es',
    'Estónia'                        => 'ee',
    'Estonia'                        => 'ee',
    'Finlândia'                      => 'fi',
    'Finland'                        => 'fi',
    'França'                         => 'fr',
    'France'                         => 'fr',
    'Grécia'                         => 'gr',
    'Greece'                         => 'gr',
    'Hungria'                        => 'hu',
    'Hungary'                        => 'hu',
    'Irlanda'                        => 'ie',
    'Ireland'                        => 'ie',
    'Islândia'                       => 'is',
    'Iceland'                        => 'is',
    'Itália'                         => 'it',
    'Italy'                          => 'it',
    'Kosovo'                         => 'xk',
    'Letónia'                        => 'lv',
    'Latvia'                         => 'lv',
    'Liechtenstein'                  => 'li',
    'Lituânia'                       => 'lt',
    'Lithuania'                      => 'lt',
    'Luxemburgo'                     => 'lu',
    'Luxembourg'                     => 'lu',
    'Malta'                          => 'mt',
    'Moldávia'                       => 'md',
    'Moldova'                        => 'md',
    'Mónaco'                         => 'mc',
    'Monaco'                         => 'mc',
    'Montenegro'                     => 'me',
    'Macedónia do Norte'             => 'mk',
    'North Macedonia'                => 'mk',
    'Noruega'                        => 'no',
    'Norway'                         => 'no',
    'Países Baixos'                  => 'nl',
    'Netherlands'                    => 'nl',
    'Holland' => 'nl',
    'Polónia'                        => 'pl',
    'Poland'                         => 'pl',
    'Portugal'                       => 'pt',
    'Reino Unido'                    => 'gb',
    'United Kingdom'                 => 'gb',
    'UK' => 'gb',
    'Roménia'                        => 'ro',
    'Romania'                        => 'ro',
    'Rússia'                         => 'ru',
    'Russia'                         => 'ru',
    'San Marino'                     => 'sm',
    'Sérvia'                         => 'rs',
    'Serbia'                         => 'rs',
    'Suécia'                         => 'se',
    'Sweden'                         => 'se',
    'Suíça'                          => 'ch',
    'Switzerland'                    => 'ch',
    'Ucrânia'                        => 'ua',
    'Ukraine'                        => 'ua',
    'Vaticano'                       => 'va',
    'Vatican'                        => 'va',

    // ── Ásia ─────────────────────────────────────────────────────────────────
    'Afeganistão'                    => 'af',
    'Afghanistan'                    => 'af',
    'Arábia Saudita'                 => 'sa',
    'Saudi Arabia'                   => 'sa',
    'Arménia'                        => 'am',
    'Armenia'                        => 'am',
    'Azerbaijão'                     => 'az',
    'Azerbaijan'                     => 'az',
    'Bahrein'                        => 'bh',
    'Bahrain'                        => 'bh',
    'Bangladesh'                     => 'bd',
    'Butão'                          => 'bt',
    'Bhutan'                         => 'bt',
    'Brunei'                         => 'bn',
    'Camboja'                        => 'kh',
    'Cambodia'                       => 'kh',
    'China'                          => 'cn',
    'Coreia do Norte'                => 'kp',
    'North Korea'                    => 'kp',
    'Coreia do Sul'                  => 'kr',
    'South Korea'                    => 'kr',
    'Emirados Árabes Unidos'         => 'ae',
    'United Arab Emirates'           => 'ae',
    'UAE' => 'ae',
    'Filipinas'                      => 'ph',
    'Philippines'                    => 'ph',
    'Geórgia'                        => 'ge',
    'Georgia'                        => 'ge',
    'Índia'                          => 'in',
    'India'                          => 'in',
    'Indonésia'                      => 'id',
    'Indonesia'                      => 'id',
    'Iraque'                         => 'iq',
    'Iraq'                           => 'iq',
    'Irão'                           => 'ir',
    'Iran'                           => 'ir',
    'Israel'                         => 'il',
    'Japão'                          => 'jp',
    'Japan'                          => 'jp',
    'Jordânia'                       => 'jo',
    'Jordan'                         => 'jo',
    'Cazaquistão'                    => 'kz',
    'Kazakhstan'                     => 'kz',
    'Kuwait'                         => 'kw',
    'Quirguistão'                    => 'kg',
    'Kyrgyzstan'                     => 'kg',
    'Laos'                           => 'la',
    'Líbano'                         => 'lb',
    'Lebanon'                        => 'lb',
    'Malásia'                        => 'my',
    'Malaysia'                       => 'my',
    'Maldivas'                       => 'mv',
    'Maldives'                       => 'mv',
    'Mongólia'                       => 'mn',
    'Mongolia'                       => 'mn',
    'Myanmar'                        => 'mm',
    'Birmânia'                       => 'mm',
    'Nepal'                          => 'np',
    'Omã'                            => 'om',
    'Oman'                           => 'om',
    'Paquistão'                      => 'pk',
    'Pakistan'                       => 'pk',
    'Palestina'                      => 'ps',
    'Palestine'                      => 'ps',
    'Qatar'                          => 'qa',
    'Catar'                          => 'qa',
    'Singapura'                      => 'sg',
    'Singapore'                      => 'sg',
    'Síria'                          => 'sy',
    'Syria'                          => 'sy',
    'Sri Lanka'                      => 'lk',
    'Tajiquistão'                    => 'tj',
    'Tajikistan'                     => 'tj',
    'Tailândia'                      => 'th',
    'Thailand'                       => 'th',
    'Timor-Leste'                    => 'tl',
    'East Timor'                     => 'tl',
    'Turquemenistão'                 => 'tm',
    'Turkmenistan'                   => 'tm',
    'Turquia'                        => 'tr',
    'Turkey'                         => 'tr',
    'Uzbequistão'                    => 'uz',
    'Uzbekistan'                     => 'uz',
    'Vietname'                       => 'vn',
    'Vietnam'                        => 'vn',
    'Iémen'                          => 'ye',
    'Yemen'                          => 'ye',

    // ── Oceânia ───────────────────────────────────────────────────────────────
    'Austrália'                      => 'au',
    'Australia'                      => 'au',
    'Fiji'                           => 'fj',
    'Kiribati'                       => 'ki',
    'Ilhas Marshall'                 => 'mh',
    'Marshall Islands'               => 'mh',
    'Micronésia'                     => 'fm',
    'Micronesia'                     => 'fm',
    'Nauru'                          => 'nr',
    'Nova Zelândia'                  => 'nz',
    'New Zealand'                    => 'nz',
    'Palau'                          => 'pw',
    'Papua Nova Guiné'               => 'pg',
    'Papua New Guinea'               => 'pg',
    'Samoa'                          => 'ws',
    'Ilhas Salomão'                  => 'sb',
    'Solomon Islands'                => 'sb',
    'Tonga'                          => 'to',
    'Tuvalu'                         => 'tv',
    'Vanuatu'                        => 'vu',

    // ── Especial ──────────────────────────────────────────────────────────────
    'Worldwide'                      => '',
    'Unknown'                        => '',
    'Desconhecido'                   => '',
];
$countries = [];
foreach ($country_meta as $name => $iso) {
    $countries[$iso . '|' . $name] = ['country_code' => $iso, 'country_name' => $name];
}
ksort($countries);
$countries = array_values($countries);

// ── Registos de _stream_country (últimos 1000) ────────────────────────────
$country_records = $db->query("
    SELECT
        sc.id_stream_country,
        t.id_track,
        t.title_track,
        sc.country_code,
        sc.country_name,
        sc.year_stream,
        sc.month_stream,
        sc.streams,
        sc.revenue,
        COALESCE(SUM(str.downloads), 0) AS total_downloads,
        GROUP_CONCAT(DISTINCT str.id_store) AS store_ids
    FROM _stream_country sc
    JOIN _track t ON t.id_track = sc.id_track
    LEFT JOIN _stream str ON str.id_track = sc.id_track 
        AND str.year_stream = sc.year_stream 
        AND str.month_stream = sc.month_stream
    GROUP BY sc.id_stream_country, t.id_track, t.title_track, sc.country_code, sc.country_name, sc.year_stream, sc.month_stream, sc.streams, sc.revenue
    ORDER BY sc.year_stream DESC, sc.month_stream DESC, t.title_track
    LIMIT 1000
")->fetchAll();

$month_names = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
    <meta name="theme-color" content="#FF0089">
    <title>Importar Streams — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css">
    <style>
        /* ── Stat cards ── */
        .wu-stat {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .wu-stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .wu-stat-val {
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1;
        }

        .wu-stat-lbl {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            opacity: .55;
            margin-top: 3px;
        }

        /* ── Card ── */
        .wu-card {
            border-radius: 14px;
            border: 1px solid var(--border-color, #e8e8f0);
            background: var(--card-bg, #fff);
        }

        /* ── Upload area ── */
        .upload-area {
            border: 2px dashed var(--border-color, #d0d0e0);
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
        }

        .upload-area.dragover,
        .upload-area:hover {
            border-color: #FF0089;
            background: rgba(255, 0, 137, .03);
        }

        /* ── Mapping table ── */
        .mapping-table th {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .mapping-table td {
            font-size: .8rem;
            vertical-align: middle;
        }

        /* ── Preview ── */
        .csv-preview {
            max-height: 260px;
            overflow: auto;
            font-size: .72rem;
        }

        /* ── Records table ── */
        .records-table td {
            font-size: .8rem;
            vertical-align: middle;
        }

        .records-table th {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .flag-sm {
            font-size: .85rem;
        }

        /* ── DB browser ── */
        .tbl-card {
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 10px;
            padding: 14px;
            cursor: pointer;
            transition: border-color .2s, transform .15s;
            background: var(--card-bg, #fff);
        }

        .tbl-card:hover {
            border-color: #FF0089;
            transform: translateY(-2px);
        }

        .tbl-card.selected {
            border-color: #FF0089;
            box-shadow: 0 0 0 3px rgba(255, 0, 137, .15);
        }

        .tbl-badge {
            font-size: .65rem;
            padding: 3px 8px;
            border-radius: 20px;
        }

        .db-viewer {
            max-height: 440px;
            overflow: auto;
            font-size: .75rem;
        }

        .db-viewer th {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .3px;
            white-space: nowrap;
        }

        .db-viewer td {
            white-space: nowrap;
            max-width: 160px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── Misc ── */
        .btn-pink {
            background: #FF0089;
            color: #fff;
        }

        .btn-pink:hover {
            background: #e0007a;
            color: #fff;
        }

        .search-bar input {
            border-radius: 8px;
            font-size: .82rem;
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

                <!-- ── Header ─────────────────────────────────────────────── -->
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1"><i class="bi bi-cloud-upload me-2"></i>Importação de Streams</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>">Home</a>
                                </li>
                                <li class="breadcrumb-item"><a href="stores">Lojas</a></li>
                                <li class="breadcrumb-item active">Importar Streams</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        <a href="stores" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-shop"></i> Ver Lojas
                        </a>
                        <button class="btn btn-sm btn-pink" id="btnManualAdd">
                            <i class="bi bi-plus-lg"></i> Adicionar Manual
                        </button>
                    </div>
                </div>

                <!-- ── Stats ──────────────────────────────────────────────── -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="wu-stat">
                            <div class="wu-stat-icon" style="background:#3b82f622">
                                <i class="bi bi-globe2" style="color:#3b82f6"></i>
                            </div>
                            <div>
                                <div class="wu-stat-val"><?php echo number_format($stat_country); ?></div>
                                <div class="wu-stat-lbl">Registos por País</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="wu-stat">
                            <div class="wu-stat-icon" style="background:#FF008922">
                                <i class="bi bi-music-note-beamed" style="color:#FF0089"></i>
                            </div>
                            <div>
                                <div class="wu-stat-val"><?php echo number_format($stat_tracks); ?></div>
                                <div class="wu-stat-lbl">Faixas com Streams</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Tabs ───────────────────────────────────────────────── -->
                <ul class="nav nav-tabs mb-3" id="mainTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-csv" data-bs-toggle="tab" data-bs-target="#pane-csv"
                            type="button" role="tab">
                            <i class="bi bi-filetype-csv me-1"></i>Importação CSV
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-manual" data-bs-toggle="tab" data-bs-target="#pane-manual"
                            type="button" role="tab">
                            <i class="bi bi-pencil-square me-1"></i>Adição Manual
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-records" data-bs-toggle="tab" data-bs-target="#pane-records"
                            type="button" role="tab">
                            <i class="bi bi-table me-1"></i>Registos
                            <span class="badge bg-secondary ms-1" style="font-size:.65rem">
                                <?php echo number_format($stat_country); ?>
                            </span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-db" data-bs-toggle="tab" data-bs-target="#pane-db"
                            type="button" role="tab" data-loaded="0">
                            <i class="bi bi-server me-1"></i>Tabelas BD
                        </button>
                    </li>
                </ul>

                <div class="tab-content">

                    <!-- ════════════════════════════════════ ABA: CSV ─── -->
                    <div class="tab-pane fade show active" id="pane-csv" role="tabpanel">
                        <div class="wu-card p-4">
                            <h5 class="mb-1"><i class="bi bi-upload me-2"></i>Carregar ficheiro CSV</h5>
                            <p class="text-muted small mb-4">Relatórios de streaming por país (Spotify, Apple Music,
                                etc.).</p>

                            <div class="upload-area" id="dropArea">
                                <i class="bi bi-cloud-arrow-up fs-1 text-muted"></i>
                                <p class="mt-2 mb-1 fw-semibold">Arraste um ficheiro CSV ou clique para selecionar</p>
                                <p class="small text-muted mb-0">Tamanho máximo: 20 MB</p>
                                <input type="file" id="csvFileInput" accept=".csv,text/csv" style="display:none">
                            </div>
                            <div id="fileInfo" class="mt-2 d-none">
                                <span class="badge bg-light text-dark me-2" id="fileName"></span>
                                <span class="text-muted small" id="fileSize"></span>
                            </div>

                            <div id="mappingSection" class="mt-4 d-none">
                                <h6 class="mb-3">Mapeamento de colunas</h6>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Ano do relatório</label>
                                        <select class="form-select form-select-sm" id="yearSelect">
                                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                                <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm mapping-table">
                                        <thead>
                                            <tr>
                                                <th>Coluna CSV</th>
                                                <th>Campo destino</th>
                                                <th>Exemplo</th>
                                            </tr>
                                        </thead>
                                        <tbody id="mappingRows"></tbody>
                                    </table>
                                </div>

                                <div class="csv-preview border rounded p-2 bg-light mb-3">
                                    <strong class="small d-block mb-1">Pré-visualização (5 primeiras linhas):</strong>
                                    <div id="csvPreview"></div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        id="cancelMappingBtn">Cancelar</button>
                                    <button type="button" class="btn btn-sm btn-pink" id="importBtn" disabled>
                                        <i class="bi bi-database me-1"></i>Importar Dados
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div><!-- /pane-csv -->

                    <!-- ════════════════════════════════════ ABA: MANUAL ─── -->
                    <div class="tab-pane fade" id="pane-manual" role="tabpanel">
                        <div class="wu-card p-4">
                            <h5 class="mb-1"><i class="bi bi-plus-circle me-2"></i>Adicionar registo de stream por país
                            </h5>
                            <p class="text-muted small mb-4">Insira manualmente dados de streaming para uma faixa e
                                país.</p>

                            <form id="manualStreamForm" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <div class="row g-3">
                                    <!-- Faixa -->
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Faixa <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" name="id_track" id="manualTrack">
                                            <option value="">Selecione uma faixa</option>
                                            <?php foreach ($tracks as $t): ?>
                                                <option value="<?php echo $t['id_track']; ?>">
                                                    <?php echo htmlspecialchars($t['title_track'] . ' (' . $t['first_name'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <!-- País -->
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">País <span
                                                class="text-danger">*</span></label>
                                        <input list="countriesDatalist" name="country_name" id="manualCountryName"
                                            class="form-control form-control-sm"
                                            placeholder="Digite ou selecione o país" autocomplete="off">
                                        <datalist id="countriesDatalist">
                                            <?php foreach ($countries as $c): ?>
                                                <option value="<?php echo htmlspecialchars($c['country_name']); ?>"
                                                    data-code="<?php echo $c['country_code']; ?>">
                                                <?php endforeach; ?>
                                        </datalist>
                                        <input type="hidden" name="country_code" id="manualCountryCode">
                                        <small class="text-muted">Comece a digitar ou escolha da lista.</small>
                                    </div>
                                    <!-- Ano -->
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Ano</label>
                                        <select class="form-select form-select-sm" name="year_stream">
                                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                                <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <!-- Mês -->
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Mês</label>
                                        <select class="form-select form-select-sm" name="month_stream">
                                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                                <option value="<?php echo $m; ?>"><?php echo $month_names[$m]; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <!-- Streams -->
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Streams</label>
                                        <input type="number" class="form-control form-control-sm" name="streams"
                                            value="0" min="0">
                                    </div>
                                    <!-- Downloads -->
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Downloads</label>
                                        <input type="number" class="form-control form-control-sm" name="downloads"
                                            value="0" min="0">
                                    </div>
                                    <!-- Receita -->
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Receita (USD)</label>
                                        <input type="number" step="0.0001" class="form-control form-control-sm"
                                            name="revenue" value="0.0000">
                                    </div>
                                    <!-- Separador: sincronização _stream -->
                                    <div class="col-12">
                                        <hr class="my-1">
                                        <p class="text-muted small mb-2">
                                            <i class="bi bi-arrow-left-right me-1"></i>
                                            <strong>Sincronizar com _stream</strong> (opcional) — selecione uma loja
                                            para gravar também os dados de streaming por loja.
                                        </p>
                                    </div>
                                    <!-- Loja -->
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Loja <span
                                                class="text-muted fw-normal">(opcional)</span></label>
                                        <select class="form-select form-select-sm" name="id_store">
                                            <option value="">— Não sincronizar —</option>
                                            <?php foreach ($stores as $s): ?>
                                                <option value="<?php echo $s['id_store']; ?>">
                                                    <?php echo htmlspecialchars($s['name_store']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <!-- Senha -->
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Senha Admin <span
                                                class="text-danger">*</span></label>
                                        <input type="password" class="form-control form-control-sm"
                                            name="password_confirm" autocomplete="current-password">
                                    </div>
                                </div>
                                <div class="mt-3 text-end">
                                    <button type="submit" class="btn btn-pink" id="manualSubmitBtn">
                                        <i class="bi bi-save me-1"></i>Adicionar Registo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div><!-- /pane-manual -->

                    <!-- ════════════════════════════════════ ABA: REGISTOS ─── -->
                    <div class="tab-pane fade" id="pane-records" role="tabpanel">
                        <div class="wu-card p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0"><i class="bi bi-globe2 me-2"></i>Streams por País</h5>
                                <div class="search-bar" style="width:260px">
                                    <input type="text" class="form-control form-control-sm" id="recordsSearch"
                                        placeholder="&#xF52A; Filtrar por faixa ou país...">
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover records-table" id="countryTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Faixa</th>
                                            <th>País</th>
                                            <th>Código</th>
                                            <th>Ano</th>
                                            <th>Mês</th>
                                            <th class="text-end">Streams</th>
                                            <th class="text-end">Downloads</th>
                                            <th class="text-end">Receita (USD)</th>
                                            <th style="width:90px">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($country_records as $rec): ?>
                                            <tr>
                                                <td class="text-muted"><?php echo $rec['id_stream_country']; ?></td>
                                                <td><?php echo htmlspecialchars($rec['title_track']); ?></td>
                                                <td><?php echo htmlspecialchars($rec['country_name'] ?? '—'); ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">
                                                        <?php echo strtoupper($rec['country_code']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $rec['year_stream']; ?></td>
                                                <td><?php echo $month_names[(int)$rec['month_stream']] ?? $rec['month_stream']; ?>
                                                </td>
                                                <td class="text-end fw-semibold">
                                                    <?php echo number_format($rec['streams']); ?></td>
                                                <td class="text-end fw-semibold">
                                                    <?php echo number_format($rec['total_downloads']); ?></td>
                                                <td class="text-end">$<?php echo number_format($rec['revenue'], 4); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-secondary btn-edit-record"
                                                        title="Editar" data-id="<?php echo $rec['id_stream_country']; ?>"
                                                        data-track="<?php echo $rec['id_track']; ?>"
                                                        data-track-name="<?php echo htmlspecialchars($rec['title_track']); ?>"
                                                        data-country-code="<?php echo htmlspecialchars($rec['country_code']); ?>"
                                                        data-country-name="<?php echo htmlspecialchars($rec['country_name'] ?? ''); ?>"
                                                        data-year="<?php echo $rec['year_stream']; ?>"
                                                        data-month="<?php echo $rec['month_stream']; ?>"
                                                        data-streams="<?php echo $rec['streams']; ?>"
                                                        data-downloads="<?php echo $rec['total_downloads']; ?>"
                                                        data-store-id="<?php echo $rec['store_ids'] ? explode(',', $rec['store_ids'])[0] : ''; ?>"
                                                        data-revenue="<?php echo $rec['revenue']; ?>">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger ms-1 btn-delete-record"
                                                        title="Eliminar" data-id="<?php echo $rec['id_stream_country']; ?>"
                                                        data-label="<?php echo htmlspecialchars($rec['title_track'] . ' — ' . ($rec['country_name'] ?? $rec['country_code']) . ' (' . $rec['year_stream'] . '/' . $rec['month_stream'] . ')'); ?>">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($country_records) >= 1000): ?>
                                <p class="text-muted small mt-2 mb-0">
                                    <i class="bi bi-info-circle me-1"></i>Mostrando os primeiros 1.000 registos.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div><!-- /pane-records -->

                    <!-- ════════════════════════════════════ ABA: TABELAS BD ─── -->
                    <div class="tab-pane fade" id="pane-db" role="tabpanel">
                        <div class="wu-card p-4">
                            <h5 class="mb-1"><i class="bi bi-server me-2"></i>Browser de Tabelas</h5>
                            <p class="text-muted small mb-4">Visualize os registos das principais tabelas da base de
                                dados.</p>

                            <!-- Lista de tabelas -->
                            <div class="row g-2 mb-4" id="dbTablesList">
                                <div class="col-12 text-center py-4 text-muted" id="dbTablesLoading">
                                    <span class="spinner-border spinner-border-sm me-2"></span>A carregar tabelas...
                                </div>
                            </div>

                            <!-- Visualizador de registos -->
                            <div id="dbRecordsSection" class="d-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0" id="dbTableTitle">—</h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted small" id="dbPaginationInfo"></span>
                                        <button class="btn btn-sm btn-outline-secondary" id="dbPrevBtn">
                                            <i class="bi bi-chevron-left"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" id="dbNextBtn">
                                            <i class="bi bi-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="db-viewer table-responsive border rounded" id="dbTableViewer">
                                    <!-- Preenchido via JS -->
                                </div>
                            </div>
                        </div>
                    </div><!-- /pane-db -->

                </div><!-- /.tab-content -->
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
     MODAL: Editar Registo (todos os campos editáveis)
═══════════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background:#FF0089">
                    <h5 class="modal-title text-white" id="editModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>Editar Registo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Info do registo -->
                    <div class="alert alert-info d-flex gap-2 align-items-start small py-2 mb-3" id="editRecordInfo">
                        <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
                        <span id="editRecordInfoText">—</span>
                    </div>

                    <form id="editRecordForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="action" value="update_record">
                        <input type="hidden" name="id" id="editId">

                        <div class="row g-3">
                            <!-- País -->
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">País <span class="text-danger">*</span></label>
                                <input list="editCountriesDatalist" name="country_name" id="editCountryName"
                                    class="form-control form-control-sm" placeholder="Nome do país" autocomplete="off">
                                <datalist id="editCountriesDatalist">
                                    <?php foreach ($countries as $c): ?>
                                        <option value="<?php echo htmlspecialchars($c['country_name']); ?>"
                                            data-code="<?php echo $c['country_code']; ?>">
                                        <?php endforeach; ?>
                                </datalist>
                            </div>
                            <!-- Código ISO -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Código ISO <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm text-uppercase"
                                    name="country_code" id="editCountryCode" maxlength="2" placeholder="AO">
                            </div>
                            <!-- Ano -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Ano <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="year_stream" id="editYear">
                                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                        <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <!-- Mês -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Mês <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="month_stream" id="editMonth">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?php echo $m; ?>"><?php echo $month_names[$m]; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <!-- Streams -->
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Streams <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" name="streams"
                                    id="editStreams" min="0">
                            </div>
                            <!-- Downloads -->
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Downloads</label>
                                <input type="number" class="form-control form-control-sm" name="downloads"
                                    id="editDownloads" min="0" value="0">
                            </div>
                            <!-- Receita -->
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Receita (USD) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.0001" class="form-control" name="revenue"
                                        id="editRevenue">
                                </div>
                            </div>
                            <!-- Separador sinc _stream -->
                            <div class="col-12">
                                <hr class="my-1">
                                <p class="text-muted small mb-1">
                                    <i class="bi bi-arrow-left-right me-1"></i>
                                    <strong>Sincronizar _stream</strong> (opcional)
                                </p>
                            </div>
                            <!-- Loja -->
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Loja <span
                                        class="text-muted fw-normal">(opcional)</span></label>
                                <select class="form-select form-select-sm" name="id_store" id="editStoreId">
                                    <option value="">— Não sincronizar —</option>
                                    <?php foreach ($stores as $s): ?>
                                        <option value="<?php echo $s['id_store']; ?>">
                                            <?php echo htmlspecialchars($s['name_store']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Senha Admin -->
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Senha Admin <span
                                        class="text-danger">*</span></label>
                                <input type="password" class="form-control form-control-sm" name="password_confirm"
                                    id="editPassword" autocomplete="current-password" placeholder="Confirmar com senha">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-sm btn-pink" id="editSaveBtn">
                                <i class="bi bi-save me-1"></i>Guardar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de progresso CSV -->
    <div class="modal fade" id="importProgressModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="spinner-border mb-3" style="color:#FF0089"></div>
                    <h6>Importando dados...</h6>
                    <p class="small text-muted mb-0" id="progressText">A processar registos</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Scripts ──────────────────────────────────────────────────────────── -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.min.js"></script>
    <?php if (!empty($_SESSION['admin_js_file'])): ?>
        <script src="<?php echo APP_URL; ?>/<?php echo $_SESSION['admin_js_file']; ?>"></script>
    <?php endif; ?>

    <script>
        (function() {
            'use strict';

            // ── Constantes ─────────────────────────────────────────────────────────
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const PROCESS_URL = 'process-import';
            const MONTHS = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

            // ── Helpers ─────────────────────────────────────────────────────────────
            function esc(str) {
                const d = document.createElement('div');
                d.appendChild(document.createTextNode(str ?? ''));
                return d.innerHTML;
            }
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
            });

            function swToast(icon, title) {
                Toast.fire({
                    icon,
                    title
                });
            }

            function swAlert(icon, title, text = '') {
                return Swal.fire({
                    icon,
                    title,
                    text,
                    confirmButtonColor: '#FF0089',
                    customClass: {
                        confirmButton: 'btn btn-pink btn-sm'
                    }
                });
            }

            // ── Feedback de redirect ────────────────────────────────────────────────
            <?php if ($swal_on_load): ?>
                window.addEventListener('DOMContentLoaded', () => {
                    swAlert(
                        '<?php echo $swal_on_load[0]; ?>',
                        '<?php echo addslashes($swal_on_load[1]); ?>',
                        '<?php echo addslashes($swal_on_load[2]); ?>'
                    );
                });
            <?php endif; ?>

            // ── HTTP helper ─────────────────────────────────────────────────────────
            async function postJson(body) {
                const resp = await fetch(PROCESS_URL, {
                    method: 'POST',
                    body
                });
                if (!resp.ok && resp.status !== 200) throw new Error(`HTTP ${resp.status}`);
                return resp.json();
            }

            function setBusy(btn, busy) {
                if (busy) {
                    btn.dataset.orig = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
                    btn.disabled = true;
                } else {
                    btn.innerHTML = btn.dataset.orig || btn.innerHTML;
                    btn.disabled = false;
                }
            }

            // ── Botão "Adicionar Manual" ────────────────────────────────────────────
            document.getElementById('btnManualAdd')?.addEventListener('click', () => {
                document.getElementById('tab-manual')?.click();
            });

            // ── Filtro da tabela de registos ────────────────────────────────────────
            const recordsSearch = document.getElementById('recordsSearch');
            if (recordsSearch) {
                recordsSearch.addEventListener('input', function() {
                    const q = this.value.toLowerCase();
                    document.querySelectorAll('#countryTable tbody tr').forEach(tr => {
                        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
                    });
                });
            }

            // ═══════════════════════════════════════════════════════════════════════
            // CSV UPLOAD & MAPPING
            // ═══════════════════════════════════════════════════════════════════════
            const dropArea = document.getElementById('dropArea');
            const csvFileInput = document.getElementById('csvFileInput');
            const fileInfo = document.getElementById('fileInfo');
            const mappingSection = document.getElementById('mappingSection');
            const mappingRows = document.getElementById('mappingRows');
            const csvPreview = document.getElementById('csvPreview');
            const importBtn = document.getElementById('importBtn');
            const yearSelect = document.getElementById('yearSelect');
            let csvData = null;

            // Campos requeridos para importação de países
            const CSV_FIELDS = [{
                    field: 'id_track',
                    label: 'Faixa / ISRC',
                    required: true
                },
                {
                    field: 'country_code',
                    label: 'Código de País',
                    required: true
                },
                {
                    field: 'country_name',
                    label: 'Nome do País',
                    required: false
                },
                {
                    field: 'streams',
                    label: 'Streams',
                    required: false
                },
                {
                    field: 'revenue',
                    label: 'Receita',
                    required: false
                },
                {
                    field: 'month_stream',
                    label: 'Mês',
                    required: false
                },
            ];

            const FIELD_ALIASES = {
                id_track: ['track', 'isrc', 'id_track', 'song id', 'track id'],
                country_code: ['country code', 'country_code', 'iso', 'codigo', 'code'],
                country_name: ['country', 'pais', 'country name', 'country_name', 'territorio'],
                streams: ['streams', 'plays', 'total streams', 'stream count'],
                revenue: ['revenue', 'receita', 'earnings', 'amount', 'royalty'],
                month_stream: ['month', 'mes', 'report month', 'month_stream'],
            };

            function normalizeHdr(h) {
                return String(h || '').toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, ' ').trim();
            }

            function autoDetect(headers) {
                const norm = headers.map(normalizeHdr);
                return Object.fromEntries(
                    CSV_FIELDS.map(({
                        field
                    }) => {
                        const idx = norm.findIndex(h => (FIELD_ALIASES[field] || []).some(a => h.includes(a)));
                        return [field, idx >= 0 ? idx : ''];
                    })
                );
            }

            function parseCsv(text) {
                const lines = text.trim().split(/\r?\n/);
                return lines.map(line => {
                    const cols = [];
                    let cur = '',
                        inQ = false;
                    for (let i = 0; i < line.length; i++) {
                        const c = line[i];
                        if (c === '"') {
                            inQ = !inQ;
                        } else if (c === ',' && !inQ) {
                            cols.push(cur);
                            cur = '';
                        } else cur += c;
                    }
                    cols.push(cur);
                    return cols;
                });
            }

            function renderMapping(headers, detected) {
                const opts = headers.map((h, i) => `<option value="${i}">${h}</option>`).join('');
                mappingRows.innerHTML = CSV_FIELDS.map(({
                    field,
                    label,
                    required
                }) => {
                    const v = detected[field];
                    return `<tr>
                <td><span class="badge bg-light text-dark border">${label}${required ? ' <span class="text-danger">*</span>' : ''}</span></td>
                <td>
                    <select class="form-select form-select-sm map-select" data-field="${field}">
                        <option value="">— não mapear —</option>
                        ${opts}
                    </select>
                </td>
                <td><span class="text-muted fst-italic" style="font-size:.72rem" id="ex-${field}">—</span></td>
            </tr>`;
                }).join('');

                mappingRows.querySelectorAll('.map-select').forEach(sel => {
                    const v = detected[sel.dataset.field];
                    if (v !== '' && v !== undefined) sel.value = String(v);
                    sel.addEventListener('change', checkRequired);
                });

                checkRequired();
            }

            function checkRequired() {
                const required = ['id_track', 'country_code'];
                const mapped = [...mappingRows.querySelectorAll('.map-select')]
                    .filter(s => s.value !== '').map(s => s.dataset.field);
                importBtn.disabled = !required.every(f => mapped.includes(f));
            }

            function renderPreview(rows) {
                const preview = rows.slice(0, 5);
                const table = `<table class="table table-sm table-bordered mb-0">
            <thead><tr>${rows[0].map(h => `<th>${h}</th>`).join('')}</tr></thead>
            <tbody>${preview.slice(1).map(r =>
                `<tr>${r.map(c => `<td>${c}</td>`).join('')}</tr>`
            ).join('')}</tbody>
        </table>`;
                csvPreview.innerHTML = table;
            }

            function handleFile(file) {
                if (!file) return;
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
                fileInfo.classList.remove('d-none');

                const reader = new FileReader();
                reader.onload = e => {
                    const rows = parseCsv(e.target.result);
                    if (rows.length < 2) {
                        swAlert('warning', 'CSV inválido', 'O ficheiro parece estar vazio.');
                        return;
                    }
                    csvData = rows.slice(1); // dados sem cabeçalho
                    renderMapping(rows[0], autoDetect(rows[0]));
                    renderPreview(rows);
                    mappingSection.classList.remove('d-none');
                };
                reader.readAsText(file);
            }

            dropArea?.addEventListener('click', () => csvFileInput.click());
            csvFileInput?.addEventListener('change', e => handleFile(e.target.files[0]));
            dropArea?.addEventListener('dragover', e => {
                e.preventDefault();
                dropArea.classList.add('dragover');
            });
            dropArea?.addEventListener('dragleave', () => dropArea.classList.remove('dragover'));
            dropArea?.addEventListener('drop', e => {
                e.preventDefault();
                dropArea.classList.remove('dragover');
                handleFile(e.dataTransfer.files[0]);
            });

            document.getElementById('cancelMappingBtn')?.addEventListener('click', () => {
                mappingSection.classList.add('d-none');
                fileInfo.classList.add('d-none');
                csvData = null;
                csvFileInput.value = '';
            });

            importBtn?.addEventListener('click', async () => {
                if (!csvData?.length) {
                    swToast('warning', 'Carregue um ficheiro CSV primeiro.');
                    return;
                }

                const mappings = {};
                mappingRows.querySelectorAll('.map-select').forEach(s => {
                    if (s.value !== '') mappings[s.dataset.field] = parseInt(s.value, 10);
                });
                if (!mappings.id_track) {
                    swAlert('warning', 'Campo obrigatório', 'Mapeie a coluna da faixa (id_track).');
                    return;
                }
                if (!mappings.country_code) {
                    swAlert('warning', 'Campo obrigatório', 'Mapeie a coluna do código de país.');
                    return;
                }

                const progressModal = new bootstrap.Modal(document.getElementById('importProgressModal'));
                progressModal.show();
                setBusy(importBtn, true);

                const fd = new FormData();
                fd.set('action', 'import_csv');
                fd.set('csrf_token', CSRF);
                fd.set('year', yearSelect.value);
                fd.set('mappings', JSON.stringify(mappings));
                fd.set('csv_data', JSON.stringify(csvData));

                try {
                    const json = await postJson(fd);
                    progressModal.hide();
                    if (!json.ok) {
                        swAlert('error', 'Erro na importação', json.message);
                        return;
                    }

                    const summary = json.skipped > 0 ?
                        `${json.imported} importados, ${json.skipped} ignorados.` :
                        `${json.imported} registos importados.`;

                    await swAlert('success', 'Importação concluída!', summary);
                    location.href = location.pathname + '?msg=imported';
                } catch (err) {
                    progressModal.hide();
                    swAlert('error', 'Erro de comunicação', err.message || 'Tente novamente.');
                } finally {
                    setBusy(importBtn, false);
                }
            });

            // ═══════════════════════════════════════════════════════════════════════
            // ADIÇÃO MANUAL
            // ═══════════════════════════════════════════════════════════════════════
            const manualForm = document.getElementById('manualStreamForm');
            const manualCountryName = document.getElementById('manualCountryName');
            const manualCountryCode = document.getElementById('manualCountryCode');
            const manualSubmitBtn = document.getElementById('manualSubmitBtn');

            // Sincronizar país → código via datalist
            manualCountryName?.addEventListener('input', function() {
                const val = this.value;
                let code = '';
                document.querySelectorAll('#countriesDatalist option').forEach(opt => {
                    if (opt.value === val) code = opt.dataset.code;
                });
                manualCountryCode.value = code;
            });

            manualForm?.addEventListener('submit', async e => {
                e.preventDefault();
                const fd = new FormData(manualForm);
                fd.set('action', 'manual_add');
                fd.set('csrf_token', CSRF);

                if (!fd.get('id_track')) {
                    swAlert('warning', 'Campo obrigatório', 'Selecione uma faixa.');
                    return;
                }
                if (!fd.get('country_code')) {
                    swAlert('warning', 'Campo obrigatório', 'Selecione um país válido da lista.');
                    return;
                }
                if (!fd.get('password_confirm').trim()) {
                    swAlert('warning', 'Campo obrigatório', 'Informe a senha do admin.');
                    return;
                }

                setBusy(manualSubmitBtn, true);
                try {
                    const json = await postJson(fd);
                    if (!json.ok) {
                        swAlert('error', 'Erro', json.message);
                        return;
                    }
                    swToast('success', json.message || 'Registo adicionado!');
                    location.reload();
                    if (rec && rec.id_stream_country) {
                        const tbody = document.querySelector('#countryTable tbody');
                        const monthName = MONTHS[+rec.month_stream] || rec.month_stream;
                        const newRow = document.createElement('tr');
                        newRow.innerHTML = `
                    <td class="text-muted">${rec.id_stream_country}</td>
                    <td>${esc(rec.title_track)}</td>
                    <td>${esc(rec.country_name || '—')}</td>
                    <td><span class="badge bg-light text-dark border">${(rec.country_code || '').toUpperCase()}</span></td>
                    <td>${rec.year_stream}</td>
                    <td>${monthName}</td>
                    <td class="text-end fw-semibold">${Number(rec.streams).toLocaleString()}</td>
                    <td class="text-end fw-semibold">${Number(rec.downloads || 0).toLocaleString()}</td>
                    <td class="text-end">$${Number(rec.revenue).toFixed(4)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary btn-edit-record" title="Editar"
                            data-id="${rec.id_stream_country}"
                            data-track="${rec.id_track}"
                            data-track-name="${esc(rec.title_track)}"
                            data-country-code="${esc(rec.country_code)}"
                            data-country-name="${esc(rec.country_name || '')}"
                            data-year="${rec.year_stream}"
                            data-month="${rec.month_stream}"
                            data-streams="${rec.streams}"
                            data-revenue="${rec.revenue}">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger ms-1 btn-delete-record" title="Eliminar"
                            data-id="${rec.id_stream_country}"
                            data-label="${esc(rec.title_track + ' — ' + (rec.country_name || rec.country_code) + ' (' + rec.year_stream + '/' + rec.month_stream + ')')}">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </td>`;
                        // Highlight temporário
                        newRow.style.background = 'rgba(255,0,137,.08)';
                        tbody.insertBefore(newRow, tbody.firstChild);
                        setTimeout(() => {
                            newRow.style.transition = 'background 1s';
                            newRow.style.background = '';
                        }, 50);

                        // Actualizar badge do contador na tab
                        const badge = document.querySelector('#tab-records .badge');
                        if (badge) badge.textContent = (parseInt(badge.textContent.replace(/\D/g, '') ||
                            '0') + 1).toLocaleString();
                    }

                    manualForm.reset();
                    // Limpar código oculto do país
                    if (manualCountryCode) manualCountryCode.value = '';
                } catch (err) {
                    swAlert('error', 'Erro de comunicação', err.message || 'Tente novamente.');
                } finally {
                    setBusy(manualSubmitBtn, false);
                }
            });

            // ═══════════════════════════════════════════════════════════════════════
            // EDITAR REGISTO (modal)
            // ═══════════════════════════════════════════════════════════════════════
            const editModalEl = document.getElementById('editModal');
            const editModal = new bootstrap.Modal(editModalEl);
            const editCountryName = document.getElementById('editCountryName');
            const editCountryCode = document.getElementById('editCountryCode');
            const editRecordForm = document.getElementById('editRecordForm');
            const editSaveBtn = document.getElementById('editSaveBtn');

            // Sincronizar país → código no modal de edição
            editCountryName?.addEventListener('input', function() {
                const val = this.value;
                let code = '';
                document.querySelectorAll('#editCountriesDatalist option').forEach(opt => {
                    if (opt.value === val) code = opt.dataset.code;
                });
                if (code) editCountryCode.value = code.toUpperCase();
            });

            editCountryCode?.addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z]/g, '').slice(0, 2);
            });

            document.addEventListener('click', e => {
                const btn = e.target.closest('.btn-edit-record');
                if (!btn) return;

                const d = btn.dataset;
                document.getElementById('editId').value = d.id;
                document.getElementById('editCountryName').value = d.countryName;
                document.getElementById('editCountryCode').value = (d.countryCode || '').toUpperCase();
                document.getElementById('editYear').value = d.year;
                document.getElementById('editMonth').value = d.month;
                document.getElementById('editStreams').value = d.streams;
                document.getElementById('editDownloads').value = parseInt(d.downloads) || 0;
                document.getElementById('editStoreId').value = d.storeId || '';
                document.getElementById('editRevenue').value = d.revenue;
                document.getElementById('editPassword').value = '';

                document.getElementById('editRecordInfoText').textContent =
                    `Registo #${d.id} — ${d.trackName} | ${d.countryName || d.countryCode} | ${d.year}/${MONTHS[+d.month] || d.month}`;

                editModal.show();
            });

            editRecordForm?.addEventListener('submit', async e => {
                e.preventDefault();
                const fd = new FormData(editRecordForm);

                if (!fd.get('country_code').trim()) {
                    swAlert('warning', 'Campo obrigatório', 'Preencha o código ISO do país.');
                    return;
                }
                if (!fd.get('streams') && fd.get('streams') !== '0') {
                    swAlert('warning', 'Campo obrigatório', 'Preencha os streams.');
                    return;
                }
                if (!fd.get('password_confirm').trim()) {
                    swAlert('warning', 'Senha obrigatória', 'Confirme a operação com a sua senha.');
                    return;
                }

                setBusy(editSaveBtn, true);
                try {
                    const json = await postJson(fd);
                    if (!json.ok) {
                        swAlert('error', 'Erro', json.message);
                        return;
                    }

                    editModal.hide();
                    swToast('success', json.message || 'Registo actualizado!');
                    location.reload();
                    const row = document.querySelector(`.btn-edit-record[data-id="${fd.get('id')}"]`)
                        ?.closest('tr');
                    if (row) {
                        const cells = row.querySelectorAll('td');
                        cells[2].textContent = fd.get('country_name') || cells[2].textContent;
                        cells[3].innerHTML =
                            `<span class="badge bg-light text-dark border">${(fd.get('country_code') || '').toUpperCase()}</span>`;
                        cells[4].textContent = fd.get('year_stream');
                        cells[5].textContent = MONTHS[+fd.get('month_stream')] || fd.get('month_stream');
                        cells[6].textContent = Number(fd.get('streams')).toLocaleString();
                        cells[7].textContent = '$' + Number(fd.get('revenue')).toFixed(4);

                        // Atualizar data-attributes do botão editar
                        const editBtn = row.querySelector('.btn-edit-record');
                        if (editBtn) {
                            editBtn.dataset.countryCode = fd.get('country_code');
                            editBtn.dataset.countryName = fd.get('country_name');
                            editBtn.dataset.year = fd.get('year_stream');
                            editBtn.dataset.month = fd.get('month_stream');
                            editBtn.dataset.streams = fd.get('streams');
                            editBtn.dataset.downloads = fd.get('downloads') || 0;
                            editBtn.dataset.revenue = fd.get('revenue');
                        }
                    }
                } catch (err) {
                    swAlert('error', 'Erro de comunicação', err.message || 'Tente novamente.');
                } finally {
                    setBusy(editSaveBtn, false);
                }
            });

            // ═══════════════════════════════════════════════════════════════════════
            // ELIMINAR REGISTO (via SweetAlert2 com campo de senha)
            // ═══════════════════════════════════════════════════════════════════════
            document.addEventListener('click', async e => {
                const btn = e.target.closest('.btn-delete-record');
                if (!btn) return;

                const id = btn.dataset.id;
                const label = btn.dataset.label;

                const result = await Swal.fire({
                    title: 'Confirmar Eliminação',
                    html: `<p class="mb-2 text-muted small">${label}</p><p>Esta operação é <strong>irreversível</strong>. Insira a sua senha para confirmar.</p>`,
                    icon: 'warning',
                    input: 'password',
                    inputPlaceholder: 'Senha do admin',
                    inputAttributes: {
                        autocomplete: 'current-password'
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-trash3 me-1"></i>Eliminar',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        confirmButton: 'btn btn-sm btn-danger',
                        cancelButton: 'btn btn-sm btn-outline-secondary'
                    },
                    preConfirm: (password) => {
                        if (!password) {
                            Swal.showValidationMessage('Informe a senha do admin.');
                            return false;
                        }
                        return password;
                    }
                });

                if (!result.isConfirmed) return;

                const fd = new FormData();
                fd.set('action', 'delete_record');
                fd.set('csrf_token', CSRF);
                fd.set('id', id);
                fd.set('password_confirm', result.value);

                try {
                    const json = await postJson(fd);
                    if (!json.ok) {
                        swAlert('error', 'Erro', json.message);
                        return;
                    }
                    swToast('success', 'Registo eliminado.');
                    btn.closest('tr')?.remove();
                } catch (err) {
                    swAlert('error', 'Erro de comunicação', err.message || 'Tente novamente.');
                }
            });

            // ═══════════════════════════════════════════════════════════════════════
            // DB BROWSER (Tabelas BD)
            // ═══════════════════════════════════════════════════════════════════════
            const tabDb = document.getElementById('tab-db');
            const dbTablesList = document.getElementById('dbTablesList');
            const dbRecordsSection = document.getElementById('dbRecordsSection');
            const dbTableTitle = document.getElementById('dbTableTitle');
            const dbTableViewer = document.getElementById('dbTableViewer');
            const dbPaginationInfo = document.getElementById('dbPaginationInfo');
            const dbPrevBtn = document.getElementById('dbPrevBtn');
            const dbNextBtn = document.getElementById('dbNextBtn');

            let dbCurrentTable = null;
            let dbCurrentPage = 1;
            let dbTotal = 0;
            let dbPerPage = 50;

            tabDb?.addEventListener('shown.bs.tab', () => {
                if (tabDb.dataset.loaded === '1') return;
                tabDb.dataset.loaded = '1';
                loadTablesList();
            });

            async function loadTablesList() {
                const fd = new FormData();
                fd.set('action', 'fetch_tables_list');
                fd.set('csrf_token', CSRF);

                try {
                    const json = await postJson(fd);
                    if (!json.ok) {
                        dbTablesList.innerHTML = `<div class="col text-danger small">${json.message}</div>`;
                        return;
                    }

                    dbTablesList.innerHTML = json.tables.map(t => {
                        const countBadge = t.count < 0 ?
                            `<span class="tbl-badge bg-light text-muted">—</span>` :
                            `<span class="tbl-badge bg-light text-dark border">${t.count.toLocaleString()} reg.</span>`;
                        return `
                <div class="col-6 col-md-3">
                    <div class="tbl-card d-flex justify-content-between align-items-start"
                         data-table="${t.table}" style="pointer-events:${t.count < 0 ? 'none;opacity:.5' : 'auto'}">
                        <div>
                            <div class="fw-semibold" style="font-size:.82rem">
                                <i class="bi ${t.icon} me-1 text-muted"></i>${t.table}
                            </div>
                            <div class="text-muted" style="font-size:.7rem">${t.label}</div>
                        </div>
                        ${countBadge}
                    </div>
                </div>`;
                    }).join('');

                    dbTablesList.querySelectorAll('.tbl-card').forEach(card => {
                        card.addEventListener('click', () => {
                            dbTablesList.querySelectorAll('.tbl-card').forEach(c => c.classList
                                .remove('selected'));
                            card.classList.add('selected');
                            dbCurrentTable = card.dataset.table;
                            dbCurrentPage = 1;
                            loadTableRecords();
                        });
                    });
                } catch (err) {
                    dbTablesList.innerHTML = `<div class="col text-danger small">Erro: ${err.message}</div>`;
                }
            }

            async function loadTableRecords() {
                if (!dbCurrentTable) return;
                dbRecordsSection.classList.remove('d-none');
                dbTableViewer.innerHTML =
                    '<div class="text-center py-3 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>A carregar...</div>';

                const fd = new FormData();
                fd.set('action', 'fetch_table');
                fd.set('csrf_token', CSRF);
                fd.set('table', dbCurrentTable);
                fd.set('page', dbCurrentPage);

                try {
                    const json = await postJson(fd);
                    if (!json.ok) {
                        dbTableViewer.innerHTML = `<p class="text-danger p-3">${json.message}</p>`;
                        return;
                    }

                    dbTotal = json.total;
                    dbPerPage = json.per_page;

                    dbTableTitle.textContent = `${json.table} (${json.total.toLocaleString()} registos)`;
                    const totalPages = Math.ceil(dbTotal / dbPerPage);
                    dbPaginationInfo.textContent = `Página ${json.page} de ${totalPages || 1}`;
                    dbPrevBtn.disabled = json.page <= 1;
                    dbNextBtn.disabled = json.page >= totalPages;

                    if (!json.rows.length) {
                        dbTableViewer.innerHTML = '<p class="text-muted p-3 mb-0">Sem registos.</p>';
                        return;
                    }

                    const thead =
                        `<thead><tr>${json.columns.map(c => `<th title="${c}">${c}</th>`).join('')}</tr></thead>`;
                    const tbody = `<tbody>${json.rows.map(row =>
                `<tr>${json.columns.map(col => {
                    const v = row[col];
                    const display = v === null ? '<span class="text-muted fst-italic">NULL</span>' : String(v);
                    return `<td title="${v ?? ''}">${display}</td>`;
                }).join('')}</tr>`
            ).join('')}</tbody>`;

                    dbTableViewer.innerHTML =
                        `<table class="table table-sm table-hover mb-0">${thead}${tbody}</table>`;
                } catch (err) {
                    dbTableViewer.innerHTML = `<p class="text-danger p-3">Erro: ${err.message}</p>`;
                }
            }

            dbPrevBtn?.addEventListener('click', () => {
                if (dbCurrentPage > 1) {
                    dbCurrentPage--;
                    loadTableRecords();
                }
            });
            dbNextBtn?.addEventListener('click', () => {
                if (dbCurrentPage < Math.ceil(dbTotal / dbPerPage)) {
                    dbCurrentPage++;
                    loadTableRecords();
                }
            });

        })();
    </script>
</body>

</html>