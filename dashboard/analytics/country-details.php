<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Detalhes por País (Estatísticas)
// Arquivo: dashboard/analytics/country-details.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
require_once __DIR__ . '/../include/platform.php';
startSecureSession();
checkRememberMe();
requireLogin();
$platform = checkDashboardStatus();
$user     = checkUserAccess((int)$_SESSION['id_users']);

$id_users       = (int)$user['id_users'];
$first_name     = htmlspecialchars($user['first_name']);
$user_name      = htmlspecialchars($user['user_name'] ?? '');
$email_verified = (bool)$user['email_verified'];
$plan_selected  = $user['plan_selected'];
$onboard_done   = (bool)($user['onboarding_done'] ?? false);
$user_photo     = $user['photo_user'] ?? null;
$name_artist_band = htmlspecialchars($user['name_artist_band'] ?? 'Cria Perfil Artístico');
$notif_count    = getUnreadNotifCount($id_users);
$db             = getDB();

// ── Saldo ─────────────────────────────────────
$w = $db->prepare('SELECT balance_aoa FROM _wallet WHERE id_users = ?');
$w->execute([$id_users]);
$balance = $w->fetch() ?: ['balance_aoa' => 0];

// ── Plano ─────────────────────────────────────
$plan      = null;
$plan_paid = ($user['status_user'] === 'active' && !empty($user['plan_activated_at']));
if ($plan_selected) {
    $ps = $db->prepare('SELECT * FROM _plans WHERE id_plan = ?');
    $ps->execute([$plan_selected]);
    $plan = $ps->fetch();
}

// Adicionar verificação de expiração do plano
$plan_expired = false;
if ($plan_paid && !empty($user['plan_expires_at'])) {
    $plan_expired = strtotime($user['plan_expires_at']) < time();
}

// ── Artistas ──────────────────────────────────
$as = $db->prepare('SELECT COUNT(*) AS total FROM _artist WHERE id_users = ?');
$as->execute([$id_users]);
$has_artist = (int)($as->fetch()['total'] ?? 0) > 0;

// ── Conta bancária ────────────────────────────
$ba = $db->prepare("SELECT id_account FROM _account WHERE id_users = ? AND status_account = 'verified' LIMIT 1");
$ba->execute([$id_users]);
$bank_account = $ba->fetch();

// ── Conta rejeitada ───────────────────────────
$rejected_account = null;
if ($plan_paid) {
    $rj = $db->prepare("SELECT type_account, reject_reason FROM _account WHERE id_users = ? AND status_account = 'rejected' LIMIT 1");
    $rj->execute([$id_users]);
    $rejected_account = $rj->fetch();
}

// ── Sessão info (modal logout) ────────────────
$ls = $db->prepare('SELECT last_login_at, last_login_ip FROM _users_security WHERE id_users = ?');
$ls->execute([$id_users]);
$sec = $ls->fetch();

$sess_stmt = $db->prepare("
    SELECT ip_address, user_agent, country, city, creat_session, last_activity
    FROM _users_sessions WHERE id_users = ? AND is_active = 1
    ORDER BY last_activity DESC LIMIT 1
");
$sess_stmt->execute([$id_users]);
$current_session  = $sess_stmt->fetch();
$session_duration_str = '—';
if ($current_session && $current_session['creat_session']) {
    $secs = time() - strtotime($current_session['creat_session']);
    if ($secs < 60)     $session_duration_str = $secs . 's';
    elseif ($secs < 3600)  $session_duration_str = floor($secs / 60) . 'min';
    elseif ($secs < 86400) $session_duration_str = floor($secs / 3600) . 'h ' . floor(($secs % 3600) / 60) . 'min';
    else                   $session_duration_str = floor($secs / 86400) . 'd ' . floor(($secs % 86400) / 3600) . 'h';
}
$member_since   = $user['creat_user'] ? date('d/m/Y', strtotime($user['creat_user'])) : '—';
$last_login_str = ($sec && $sec['last_login_at']) ? date('d/m/Y H:i', strtotime($sec['last_login_at'])) : '—';
$ua_raw   = $current_session['user_agent'] ?? '';
$browser  = 'Desconhecido';
if (str_contains($ua_raw, 'Edg'))     $browser = 'Microsoft Edge';
elseif (str_contains($ua_raw, 'Chrome'))  $browser = 'Google Chrome';
elseif (str_contains($ua_raw, 'Firefox')) $browser = 'Mozilla Firefox';
elseif (str_contains($ua_raw, 'Safari'))  $browser = 'Safari';
elseif (str_contains($ua_raw, 'Opera'))   $browser = 'Opera';
$sess_location = trim(($current_session['city'] ?? '') . ', ' . ($current_session['country'] ?? ''), ', ') ?: 'Desconhecida';
$sess_ip       = $current_session['ip_address'] ?? ($sec['last_login_ip'] ?? '—');

// ── Parâmetros ────────────────────────────────
$country_raw  = isset($_GET['country']) ? trim($_GET['country']) : '';
$filter_year  = isset($_GET['year'])    ? (int)$_GET['year']    : (int)date('Y');

$country_name = preg_replace('/[^a-zA-ZÀ-ÿ0-9 \-\(\)\.]/u', '', $country_raw);
$country_name = mb_substr($country_name, 0, 80);

if (!$country_name) {
    redirect(APP_URL_PANEL . '/statistics#country');
}

// ── Mapa: país → coordenadas + ISO2 ──────────
$country_meta = [
    // ── África ───────────────────────────────────────────────────────────────
    'Angola'                    => ['lat' => -11.2027, 'lng' =>  17.8739, 'iso' => 'ao'],
    'Argélia'                   => ['lat' =>  28.0339, 'lng' =>   1.6596, 'iso' => 'dz'],
    'Algeria'                   => ['lat' =>  28.0339, 'lng' =>   1.6596, 'iso' => 'dz'],
    'Benim'                     => ['lat' =>   9.3077, 'lng' =>   2.3158, 'iso' => 'bj'],
    'Benin'                     => ['lat' =>   9.3077, 'lng' =>   2.3158, 'iso' => 'bj'],
    'Botsuana'                  => ['lat' => -22.3285, 'lng' =>  24.6849, 'iso' => 'bw'],
    'Botswana'                  => ['lat' => -22.3285, 'lng' =>  24.6849, 'iso' => 'bw'],
    'Burkina Faso'               => ['lat' =>  12.3641, 'lng' =>  -1.5337, 'iso' => 'bf'],
    'Burundi'                   => ['lat' =>  -3.3731, 'lng' =>  29.9189, 'iso' => 'bi'],
    'Cabo Verde'                => ['lat' =>  16.0000, 'lng' => -24.0132, 'iso' => 'cv'],
    'Cape Verde'                => ['lat' =>  16.0000, 'lng' => -24.0132, 'iso' => 'cv'],
    'Camarões'                  => ['lat' =>   3.8480, 'lng' =>  11.5021, 'iso' => 'cm'],
    'Cameroon'                  => ['lat' =>   3.8480, 'lng' =>  11.5021, 'iso' => 'cm'],
    'Chade'                     => ['lat' =>  15.4542, 'lng' =>  18.7322, 'iso' => 'td'],
    'Chad'                      => ['lat' =>  15.4542, 'lng' =>  18.7322, 'iso' => 'td'],
    'Comores'                   => ['lat' => -11.6455, 'lng' =>  43.3333, 'iso' => 'km'],
    'Comoros'                   => ['lat' => -11.6455, 'lng' =>  43.3333, 'iso' => 'km'],
    'Congo'                     => ['lat' =>  -4.0383, 'lng' =>  21.7587, 'iso' => 'cd'],
    'República Democrática do Congo' => ['lat' => -4.0383, 'lng' => 21.7587, 'iso' => 'cd'],
    'Democratic Republic of Congo'   => ['lat' => -4.0383, 'lng' => 21.7587, 'iso' => 'cd'],
    'República do Congo'        => ['lat' =>  -0.2280, 'lng' =>  15.8277, 'iso' => 'cg'],
    'Republic of Congo'         => ['lat' =>  -0.2280, 'lng' =>  15.8277, 'iso' => 'cg'],
    'Costa do Marfim'           => ['lat' =>   7.5400, 'lng' =>  -5.5471, 'iso' => 'ci'],
    'Ivory Coast'               => ['lat' =>   7.5400, 'lng' =>  -5.5471, 'iso' => 'ci'],
    "Côte d'Ivoire"             => ['lat' =>   7.5400, 'lng' =>  -5.5471, 'iso' => 'ci'],
    'Djibuti'                   => ['lat' =>  11.8251, 'lng' =>  42.5903, 'iso' => 'dj'],
    'Djibouti'                  => ['lat' =>  11.8251, 'lng' =>  42.5903, 'iso' => 'dj'],
    'Egito'                     => ['lat' =>  26.8206, 'lng' =>  30.8025, 'iso' => 'eg'],
    'Egypt'                     => ['lat' =>  26.8206, 'lng' =>  30.8025, 'iso' => 'eg'],
    'Eritreia'                  => ['lat' =>  15.1794, 'lng' =>  39.7823, 'iso' => 'er'],
    'Eritrea'                   => ['lat' =>  15.1794, 'lng' =>  39.7823, 'iso' => 'er'],
    'Eswatini'                  => ['lat' => -26.5225, 'lng' =>  31.4659, 'iso' => 'sz'],
    'Suazilândia'               => ['lat' => -26.5225, 'lng' =>  31.4659, 'iso' => 'sz'],
    'Etiópia'                   => ['lat' =>   9.1450, 'lng' =>  40.4897, 'iso' => 'et'],
    'Ethiopia'                  => ['lat' =>   9.1450, 'lng' =>  40.4897, 'iso' => 'et'],
    'Gabão'                     => ['lat' =>  -0.8037, 'lng' =>  11.6094, 'iso' => 'ga'],
    'Gabon'                     => ['lat' =>  -0.8037, 'lng' =>  11.6094, 'iso' => 'ga'],
    'Gâmbia'                    => ['lat' =>  13.4432, 'lng' => -15.3101, 'iso' => 'gm'],
    'Gambia'                    => ['lat' =>  13.4432, 'lng' => -15.3101, 'iso' => 'gm'],
    'Gana'                      => ['lat' =>   7.9465, 'lng' =>  -1.0232, 'iso' => 'gh'],
    'Ghana'                     => ['lat' =>   7.9465, 'lng' =>  -1.0232, 'iso' => 'gh'],
    'Guiné'                     => ['lat' =>  11.7466, 'lng' => -15.6836, 'iso' => 'gn'],
    'Guinea'                    => ['lat' =>  11.7466, 'lng' => -15.6836, 'iso' => 'gn'],
    'Guiné Equatorial'          => ['lat' =>   1.6508, 'lng' =>  10.2679, 'iso' => 'gq'],
    'Equatorial Guinea'         => ['lat' =>   1.6508, 'lng' =>  10.2679, 'iso' => 'gq'],
    'Guiné-Bissau'              => ['lat' =>  11.8037, 'lng' => -15.1804, 'iso' => 'gw'],
    'Guinea-Bissau'             => ['lat' =>  11.8037, 'lng' => -15.1804, 'iso' => 'gw'],
    'Quénia'                    => ['lat' =>  -0.0236, 'lng' =>  37.9062, 'iso' => 'ke'],
    'Kenya'                     => ['lat' =>  -0.0236, 'lng' =>  37.9062, 'iso' => 'ke'],
    'Lesoto'                    => ['lat' => -29.6100, 'lng' =>  28.2336, 'iso' => 'ls'],
    'Lesotho'                   => ['lat' => -29.6100, 'lng' =>  28.2336, 'iso' => 'ls'],
    'Libéria'                   => ['lat' =>   6.4281, 'lng' =>  -9.4295, 'iso' => 'lr'],
    'Liberia'                   => ['lat' =>   6.4281, 'lng' =>  -9.4295, 'iso' => 'lr'],
    'Líbia'                     => ['lat' =>  26.3351, 'lng' =>  17.2283, 'iso' => 'ly'],
    'Libya'                     => ['lat' =>  26.3351, 'lng' =>  17.2283, 'iso' => 'ly'],
    'Madagáscar'                => ['lat' => -18.7669, 'lng' =>  46.8691, 'iso' => 'mg'],
    'Madagascar'                => ['lat' => -18.7669, 'lng' =>  46.8691, 'iso' => 'mg'],
    'Malawi'                    => ['lat' => -13.2543, 'lng' =>  34.3015, 'iso' => 'mw'],
    'Mali'                      => ['lat' =>  17.5707, 'lng' =>  -3.9962, 'iso' => 'ml'],
    'Mauritânia'                => ['lat' =>  21.0079, 'lng' => -10.9408, 'iso' => 'mr'],
    'Mauritania'                => ['lat' =>  21.0079, 'lng' => -10.9408, 'iso' => 'mr'],
    'Maurícia'                  => ['lat' => -20.3484, 'lng' =>  57.5522, 'iso' => 'mu'],
    'Mauritius'                 => ['lat' => -20.3484, 'lng' =>  57.5522, 'iso' => 'mu'],
    'Marrocos'                  => ['lat' =>  31.7917, 'lng' =>  -7.0926, 'iso' => 'ma'],
    'Morocco'                   => ['lat' =>  31.7917, 'lng' =>  -7.0926, 'iso' => 'ma'],
    'Moçambique'                => ['lat' => -18.6657, 'lng' =>  35.5296, 'iso' => 'mz'],
    'Mozambique'                => ['lat' => -18.6657, 'lng' =>  35.5296, 'iso' => 'mz'],
    'Namíbia'                   => ['lat' => -22.9576, 'lng' =>  18.4904, 'iso' => 'na'],
    'Namibia'                   => ['lat' => -22.9576, 'lng' =>  18.4904, 'iso' => 'na'],
    'Níger'                     => ['lat' =>  17.6078, 'lng' =>   8.0817, 'iso' => 'ne'],
    'Niger'                     => ['lat' =>  17.6078, 'lng' =>   8.0817, 'iso' => 'ne'],
    'Nigéria'                   => ['lat' =>   9.0820, 'lng' =>   8.6753, 'iso' => 'ng'],
    'Nigeria'                   => ['lat' =>   9.0820, 'lng' =>   8.6753, 'iso' => 'ng'],
    'Ruanda'                    => ['lat' =>  -1.9403, 'lng' =>  29.8739, 'iso' => 'rw'],
    'Rwanda'                    => ['lat' =>  -1.9403, 'lng' =>  29.8739, 'iso' => 'rw'],
    'São Tomé e Príncipe'       => ['lat' =>   0.1864, 'lng' =>   6.6131, 'iso' => 'st'],
    'Sao Tome and Principe'     => ['lat' =>   0.1864, 'lng' =>   6.6131, 'iso' => 'st'],
    'Senegal'                   => ['lat' =>  14.4974, 'lng' => -14.4524, 'iso' => 'sn'],
    'Serra Leoa'                => ['lat' =>   8.4606, 'lng' => -11.7799, 'iso' => 'sl'],
    'Sierra Leone'              => ['lat' =>   8.4606, 'lng' => -11.7799, 'iso' => 'sl'],
    'Seychelles'                => ['lat' =>  -4.6796, 'lng' =>  55.4920, 'iso' => 'sc'],
    'Somália'                   => ['lat' =>   5.1521, 'lng' =>  46.1996, 'iso' => 'so'],
    'Somalia'                   => ['lat' =>   5.1521, 'lng' =>  46.1996, 'iso' => 'so'],
    'África do Sul'             => ['lat' => -30.5595, 'lng' =>  22.9375, 'iso' => 'za'],
    'South Africa'              => ['lat' => -30.5595, 'lng' =>  22.9375, 'iso' => 'za'],
    'Sudão'                     => ['lat' =>  12.8628, 'lng' =>  30.2176, 'iso' => 'sd'],
    'Sudan'                     => ['lat' =>  12.8628, 'lng' =>  30.2176, 'iso' => 'sd'],
    'Sudão do Sul'              => ['lat' =>   6.8770, 'lng' =>  31.3070, 'iso' => 'ss'],
    'South Sudan'               => ['lat' =>   6.8770, 'lng' =>  31.3070, 'iso' => 'ss'],
    'Tanzânia'                  => ['lat' =>  -6.3690, 'lng' =>  34.8888, 'iso' => 'tz'],
    'Tanzania'                  => ['lat' =>  -6.3690, 'lng' =>  34.8888, 'iso' => 'tz'],
    'Togo'                      => ['lat' =>   8.6195, 'lng' =>   0.8248, 'iso' => 'tg'],
    'Tunísia'                   => ['lat' =>  33.8869, 'lng' =>   9.5375, 'iso' => 'tn'],
    'Tunisia'                   => ['lat' =>  33.8869, 'lng' =>   9.5375, 'iso' => 'tn'],
    'Uganda'                    => ['lat' =>   1.3733, 'lng' =>  32.2903, 'iso' => 'ug'],
    'Zâmbia'                    => ['lat' => -13.1339, 'lng' =>  27.8493, 'iso' => 'zm'],
    'Zambia'                    => ['lat' => -13.1339, 'lng' =>  27.8493, 'iso' => 'zm'],
    'Zimbabué'                  => ['lat' => -19.0154, 'lng' =>  29.1549, 'iso' => 'zw'],
    'Zimbabwe'                  => ['lat' => -19.0154, 'lng' =>  29.1549, 'iso' => 'zw'],

    // ── Américas ──────────────────────────────────────────────────────────────
    'Antígua e Barbuda'         => ['lat' =>  17.0608, 'lng' => -61.7964, 'iso' => 'ag'],
    'Antigua and Barbuda'       => ['lat' =>  17.0608, 'lng' => -61.7964, 'iso' => 'ag'],
    'Argentina'                 => ['lat' => -38.4161, 'lng' => -63.6167, 'iso' => 'ar'],
    'Bahamas'                   => ['lat' =>  25.0343, 'lng' => -77.3963, 'iso' => 'bs'],
    'Barbados'                  => ['lat' =>  13.1939, 'lng' => -59.5432, 'iso' => 'bb'],
    'Belize'                    => ['lat' =>  17.1899, 'lng' => -88.4976, 'iso' => 'bz'],
    'Bolívia'                   => ['lat' => -16.2902, 'lng' => -63.5887, 'iso' => 'bo'],
    'Bolivia'                   => ['lat' => -16.2902, 'lng' => -63.5887, 'iso' => 'bo'],
    'Brasil'                    => ['lat' => -14.2350, 'lng' => -51.9253, 'iso' => 'br'],
    'Brazil'                    => ['lat' => -14.2350, 'lng' => -51.9253, 'iso' => 'br'],
    'Canadá'                    => ['lat' =>  56.1304, 'lng' => -106.3468, 'iso' => 'ca'],
    'Canada'                    => ['lat' =>  56.1304, 'lng' => -106.3468, 'iso' => 'ca'],
    'Chile'                     => ['lat' => -35.6751, 'lng' => -71.5430, 'iso' => 'cl'],
    'Colômbia'                  => ['lat' =>   4.5709, 'lng' => -74.2973, 'iso' => 'co'],
    'Colombia'                  => ['lat' =>   4.5709, 'lng' => -74.2973, 'iso' => 'co'],
    'Costa Rica'                => ['lat' =>   9.7489, 'lng' => -83.7534, 'iso' => 'cr'],
    'Cuba'                      => ['lat' =>  21.5218, 'lng' => -77.7812, 'iso' => 'cu'],
    'Dominica'                  => ['lat' =>  15.4150, 'lng' => -61.3710, 'iso' => 'dm'],
    'República Dominicana'      => ['lat' =>  18.7357, 'lng' => -70.1627, 'iso' => 'do'],
    'Dominican Republic'        => ['lat' =>  18.7357, 'lng' => -70.1627, 'iso' => 'do'],
    'Equador'                   => ['lat' =>  -1.8312, 'lng' => -78.1834, 'iso' => 'ec'],
    'Ecuador'                   => ['lat' =>  -1.8312, 'lng' => -78.1834, 'iso' => 'ec'],
    'El Salvador'               => ['lat' =>  13.7942, 'lng' => -88.8965, 'iso' => 'sv'],
    'Granada'                   => ['lat' =>  12.1165, 'lng' => -61.6790, 'iso' => 'gd'],
    'Grenada'                   => ['lat' =>  12.1165, 'lng' => -61.6790, 'iso' => 'gd'],
    'Guatemala'                 => ['lat' =>  15.7835, 'lng' => -90.2308, 'iso' => 'gt'],
    'Guiana'                    => ['lat' =>   4.8604, 'lng' => -58.9302, 'iso' => 'gy'],
    'Guyana'                    => ['lat' =>   4.8604, 'lng' => -58.9302, 'iso' => 'gy'],
    'Haiti'                     => ['lat' =>  18.9712, 'lng' => -72.2852, 'iso' => 'ht'],
    'Honduras'                  => ['lat' =>  15.1999, 'lng' => -86.2419, 'iso' => 'hn'],
    'Jamaica'                   => ['lat' =>  18.1096, 'lng' => -77.2975, 'iso' => 'jm'],
    'México'                    => ['lat' =>  23.6345, 'lng' => -102.5528, 'iso' => 'mx'],
    'Mexico'                    => ['lat' =>  23.6345, 'lng' => -102.5528, 'iso' => 'mx'],
    'Nicarágua'                 => ['lat' =>  12.8654, 'lng' => -85.2072, 'iso' => 'ni'],
    'Nicaragua'                 => ['lat' =>  12.8654, 'lng' => -85.2072, 'iso' => 'ni'],
    'Panamá'                    => ['lat' =>   8.5380, 'lng' => -80.7821, 'iso' => 'pa'],
    'Panama'                    => ['lat' =>   8.5380, 'lng' => -80.7821, 'iso' => 'pa'],
    'Paraguai'                  => ['lat' => -23.4425, 'lng' => -58.4438, 'iso' => 'py'],
    'Paraguay'                  => ['lat' => -23.4425, 'lng' => -58.4438, 'iso' => 'py'],
    'Peru'                      => ['lat' =>  -9.1900, 'lng' => -75.0152, 'iso' => 'pe'],
    'São Cristóvão e Névis'     => ['lat' =>  17.3578, 'lng' => -62.7830, 'iso' => 'kn'],
    'Saint Kitts and Nevis'     => ['lat' =>  17.3578, 'lng' => -62.7830, 'iso' => 'kn'],
    'Santa Lúcia'               => ['lat' =>  13.9094, 'lng' => -60.9789, 'iso' => 'lc'],
    'Saint Lucia'               => ['lat' =>  13.9094, 'lng' => -60.9789, 'iso' => 'lc'],
    'São Vicente e Granadinas'  => ['lat' =>  12.9843, 'lng' => -61.2872, 'iso' => 'vc'],
    'Saint Vincent and the Grenadines' => ['lat' => 12.9843, 'lng' => -61.2872, 'iso' => 'vc'],
    'Suriname'                  => ['lat' =>   3.9193, 'lng' => -56.0278, 'iso' => 'sr'],
    'Trinidad e Tobago'         => ['lat' =>  10.6918, 'lng' => -61.2225, 'iso' => 'tt'],
    'Trinidad and Tobago'       => ['lat' =>  10.6918, 'lng' => -61.2225, 'iso' => 'tt'],
    'USA'                       => ['lat' =>  37.0902, 'lng' => -95.7129, 'iso' => 'us'],
    'United States'             => ['lat' =>  37.0902, 'lng' => -95.7129, 'iso' => 'us'],
    'Estados Unidos'            => ['lat' =>  37.0902, 'lng' => -95.7129, 'iso' => 'us'],
    'Uruguai'                   => ['lat' => -32.5228, 'lng' => -55.7658, 'iso' => 'uy'],
    'Uruguay'                   => ['lat' => -32.5228, 'lng' => -55.7658, 'iso' => 'uy'],
    'Venezuela'                 => ['lat' =>   6.4238, 'lng' => -66.5897, 'iso' => 've'],

    // ── Europa ────────────────────────────────────────────────────────────────
    'Albânia'                   => ['lat' =>  41.1533, 'lng' =>  20.1683, 'iso' => 'al'],
    'Albania'                   => ['lat' =>  41.1533, 'lng' =>  20.1683, 'iso' => 'al'],
    'Alemanha'                  => ['lat' =>  51.1657, 'lng' =>  10.4515, 'iso' => 'de'],
    'Germany'                   => ['lat' =>  51.1657, 'lng' =>  10.4515, 'iso' => 'de'],
    'Andorra'                   => ['lat' =>  42.5063, 'lng' =>   1.5218, 'iso' => 'ad'],
    'Áustria'                   => ['lat' =>  47.5162, 'lng' =>  14.5501, 'iso' => 'at'],
    'Austria'                   => ['lat' =>  47.5162, 'lng' =>  14.5501, 'iso' => 'at'],
    'Bielorrússia'              => ['lat' =>  53.7098, 'lng' =>  27.9534, 'iso' => 'by'],
    'Belarus'                   => ['lat' =>  53.7098, 'lng' =>  27.9534, 'iso' => 'by'],
    'Bélgica'                   => ['lat' =>  50.5039, 'lng' =>   4.4699, 'iso' => 'be'],
    'Belgium'                   => ['lat' =>  50.5039, 'lng' =>   4.4699, 'iso' => 'be'],
    'Bósnia e Herzegovina'      => ['lat' =>  43.9159, 'lng' =>  17.6791, 'iso' => 'ba'],
    'Bosnia and Herzegovina'    => ['lat' =>  43.9159, 'lng' =>  17.6791, 'iso' => 'ba'],
    'Bulgária'                  => ['lat' =>  42.7339, 'lng' =>  25.4858, 'iso' => 'bg'],
    'Bulgaria'                  => ['lat' =>  42.7339, 'lng' =>  25.4858, 'iso' => 'bg'],
    'Croácia'                   => ['lat' =>  45.1000, 'lng' =>  15.2000, 'iso' => 'hr'],
    'Croatia'                   => ['lat' =>  45.1000, 'lng' =>  15.2000, 'iso' => 'hr'],
    'Chipre'                    => ['lat' =>  35.1264, 'lng' =>  33.4299, 'iso' => 'cy'],
    'Cyprus'                    => ['lat' =>  35.1264, 'lng' =>  33.4299, 'iso' => 'cy'],
    'República Checa'           => ['lat' =>  49.8175, 'lng' =>  15.4730, 'iso' => 'cz'],
    'Czech Republic'            => ['lat' =>  49.8175, 'lng' =>  15.4730, 'iso' => 'cz'],
    'Czechia'                   => ['lat' =>  49.8175, 'lng' =>  15.4730, 'iso' => 'cz'],
    'Dinamarca'                 => ['lat' =>  56.2639, 'lng' =>   9.5018, 'iso' => 'dk'],
    'Denmark'                   => ['lat' =>  56.2639, 'lng' =>   9.5018, 'iso' => 'dk'],
    'Eslováquia'                => ['lat' =>  48.6690, 'lng' =>  19.6990, 'iso' => 'sk'],
    'Slovakia'                  => ['lat' =>  48.6690, 'lng' =>  19.6990, 'iso' => 'sk'],
    'Eslovénia'                 => ['lat' =>  46.1512, 'lng' =>  14.9955, 'iso' => 'si'],
    'Slovenia'                  => ['lat' =>  46.1512, 'lng' =>  14.9955, 'iso' => 'si'],
    'Espanha'                   => ['lat' =>  40.4637, 'lng' =>  -3.7492, 'iso' => 'es'],
    'Spain'                     => ['lat' =>  40.4637, 'lng' =>  -3.7492, 'iso' => 'es'],
    'Estónia'                   => ['lat' =>  58.5953, 'lng' =>  25.0136, 'iso' => 'ee'],
    'Estonia'                   => ['lat' =>  58.5953, 'lng' =>  25.0136, 'iso' => 'ee'],
    'Finlândia'                 => ['lat' =>  61.9241, 'lng' =>  25.7482, 'iso' => 'fi'],
    'Finland'                   => ['lat' =>  61.9241, 'lng' =>  25.7482, 'iso' => 'fi'],
    'França'                    => ['lat' =>  46.2276, 'lng' =>   2.2137, 'iso' => 'fr'],
    'France'                    => ['lat' =>  46.2276, 'lng' =>   2.2137, 'iso' => 'fr'],
    'Grécia'                    => ['lat' =>  39.0742, 'lng' =>  21.8243, 'iso' => 'gr'],
    'Greece'                    => ['lat' =>  39.0742, 'lng' =>  21.8243, 'iso' => 'gr'],
    'Hungria'                   => ['lat' =>  47.1625, 'lng' =>  19.5033, 'iso' => 'hu'],
    'Hungary'                   => ['lat' =>  47.1625, 'lng' =>  19.5033, 'iso' => 'hu'],
    'Irlanda'                   => ['lat' =>  53.1424, 'lng' =>  -7.6921, 'iso' => 'ie'],
    'Ireland'                   => ['lat' =>  53.1424, 'lng' =>  -7.6921, 'iso' => 'ie'],
    'Islândia'                  => ['lat' =>  64.9631, 'lng' => -19.0208, 'iso' => 'is'],
    'Iceland'                   => ['lat' =>  64.9631, 'lng' => -19.0208, 'iso' => 'is'],
    'Itália'                    => ['lat' =>  41.8719, 'lng' =>  12.5674, 'iso' => 'it'],
    'Italy'                     => ['lat' =>  41.8719, 'lng' =>  12.5674, 'iso' => 'it'],
    'Kosovo'                    => ['lat' =>  42.6026, 'lng' =>  20.9030, 'iso' => 'xk'],
    'Letónia'                   => ['lat' =>  56.8796, 'lng' =>  24.6032, 'iso' => 'lv'],
    'Latvia'                    => ['lat' =>  56.8796, 'lng' =>  24.6032, 'iso' => 'lv'],
    'Liechtenstein'             => ['lat' =>  47.1660, 'lng' =>   9.5554, 'iso' => 'li'],
    'Lituânia'                  => ['lat' =>  55.1694, 'lng' =>  23.8813, 'iso' => 'lt'],
    'Lithuania'                 => ['lat' =>  55.1694, 'lng' =>  23.8813, 'iso' => 'lt'],
    'Luxemburgo'                => ['lat' =>  49.8153, 'lng' =>   6.1296, 'iso' => 'lu'],
    'Luxembourg'                => ['lat' =>  49.8153, 'lng' =>   6.1296, 'iso' => 'lu'],
    'Malta'                     => ['lat' =>  35.9375, 'lng' =>  14.3754, 'iso' => 'mt'],
    'Moldávia'                  => ['lat' =>  47.4116, 'lng' =>  28.3699, 'iso' => 'md'],
    'Moldova'                   => ['lat' =>  47.4116, 'lng' =>  28.3699, 'iso' => 'md'],
    'Mónaco'                    => ['lat' =>  43.7384, 'lng' =>   7.4246, 'iso' => 'mc'],
    'Monaco'                    => ['lat' =>  43.7384, 'lng' =>   7.4246, 'iso' => 'mc'],
    'Montenegro'                => ['lat' =>  42.7087, 'lng' =>  19.3744, 'iso' => 'me'],
    'Macedónia do Norte'        => ['lat' =>  41.6086, 'lng' =>  21.7453, 'iso' => 'mk'],
    'North Macedonia'           => ['lat' =>  41.6086, 'lng' =>  21.7453, 'iso' => 'mk'],
    'Noruega'                   => ['lat' =>  60.4720, 'lng' =>   8.4689, 'iso' => 'no'],
    'Norway'                    => ['lat' =>  60.4720, 'lng' =>   8.4689, 'iso' => 'no'],
    'Países Baixos'             => ['lat' =>  52.1326, 'lng' =>   5.2913, 'iso' => 'nl'],
    'Netherlands'               => ['lat' =>  52.1326, 'lng' =>   5.2913, 'iso' => 'nl'],
    'Holland'                   => ['lat' =>  52.1326, 'lng' =>   5.2913, 'iso' => 'nl'],
    'Polónia'                   => ['lat' =>  51.9194, 'lng' =>  19.1451, 'iso' => 'pl'],
    'Poland'                    => ['lat' =>  51.9194, 'lng' =>  19.1451, 'iso' => 'pl'],
    'Portugal'                  => ['lat' =>  39.3999, 'lng' =>  -8.2245, 'iso' => 'pt'],
    'Reino Unido'               => ['lat' =>  55.3781, 'lng' =>  -3.4360, 'iso' => 'gb'],
    'United Kingdom'            => ['lat' =>  55.3781, 'lng' =>  -3.4360, 'iso' => 'gb'],
    'UK'                        => ['lat' =>  55.3781, 'lng' =>  -3.4360, 'iso' => 'gb'],
    'Roménia'                   => ['lat' =>  45.9432, 'lng' =>  24.9668, 'iso' => 'ro'],
    'Romania'                   => ['lat' =>  45.9432, 'lng' =>  24.9668, 'iso' => 'ro'],
    'Rússia'                    => ['lat' =>  61.5240, 'lng' => 105.3188, 'iso' => 'ru'],
    'Russia'                    => ['lat' =>  61.5240, 'lng' => 105.3188, 'iso' => 'ru'],
    'San Marino'                => ['lat' =>  43.9424, 'lng' =>  12.4578, 'iso' => 'sm'],
    'Sérvia'                    => ['lat' =>  44.0165, 'lng' =>  21.0059, 'iso' => 'rs'],
    'Serbia'                    => ['lat' =>  44.0165, 'lng' =>  21.0059, 'iso' => 'rs'],
    'Suécia'                    => ['lat' =>  60.1282, 'lng' =>  18.6435, 'iso' => 'se'],
    'Sweden'                    => ['lat' =>  60.1282, 'lng' =>  18.6435, 'iso' => 'se'],
    'Suíça'                     => ['lat' =>  46.8182, 'lng' =>   8.2275, 'iso' => 'ch'],
    'Switzerland'               => ['lat' =>  46.8182, 'lng' =>   8.2275, 'iso' => 'ch'],
    'Ucrânia'                   => ['lat' =>  48.3794, 'lng' =>  31.1656, 'iso' => 'ua'],
    'Ukraine'                   => ['lat' =>  48.3794, 'lng' =>  31.1656, 'iso' => 'ua'],
    'Vaticano'                  => ['lat' =>  41.9029, 'lng' =>  12.4534, 'iso' => 'va'],
    'Vatican'                   => ['lat' =>  41.9029, 'lng' =>  12.4534, 'iso' => 'va'],

    // ── Ásia ─────────────────────────────────────────────────────────────────
    'Afeganistão'               => ['lat' =>  33.9391, 'lng' =>  67.7100, 'iso' => 'af'],
    'Afghanistan'               => ['lat' =>  33.9391, 'lng' =>  67.7100, 'iso' => 'af'],
    'Arábia Saudita'            => ['lat' =>  23.8859, 'lng' =>  45.0792, 'iso' => 'sa'],
    'Saudi Arabia'              => ['lat' =>  23.8859, 'lng' =>  45.0792, 'iso' => 'sa'],
    'Arménia'                   => ['lat' =>  40.0691, 'lng' =>  45.0382, 'iso' => 'am'],
    'Armenia'                   => ['lat' =>  40.0691, 'lng' =>  45.0382, 'iso' => 'am'],
    'Azerbaijão'                => ['lat' =>  40.1431, 'lng' =>  47.5769, 'iso' => 'az'],
    'Azerbaijan'                => ['lat' =>  40.1431, 'lng' =>  47.5769, 'iso' => 'az'],
    'Bahrein'                   => ['lat' =>  26.0275, 'lng' =>  50.5500, 'iso' => 'bh'],
    'Bahrain'                   => ['lat' =>  26.0275, 'lng' =>  50.5500, 'iso' => 'bh'],
    'Bangladesh'                => ['lat' =>  23.6850, 'lng' =>  90.3563, 'iso' => 'bd'],
    'Butão'                     => ['lat' =>  27.5142, 'lng' =>  90.4336, 'iso' => 'bt'],
    'Bhutan'                    => ['lat' =>  27.5142, 'lng' =>  90.4336, 'iso' => 'bt'],
    'Brunei'                    => ['lat' =>   4.5353, 'lng' => 114.7277, 'iso' => 'bn'],
    'Camboja'                   => ['lat' =>  12.5657, 'lng' => 104.9910, 'iso' => 'kh'],
    'Cambodia'                  => ['lat' =>  12.5657, 'lng' => 104.9910, 'iso' => 'kh'],
    'China'                     => ['lat' =>  35.8617, 'lng' => 104.1954, 'iso' => 'cn'],
    'Coreia do Norte'           => ['lat' =>  40.3399, 'lng' => 127.5101, 'iso' => 'kp'],
    'North Korea'               => ['lat' =>  40.3399, 'lng' => 127.5101, 'iso' => 'kp'],
    'Coreia do Sul'             => ['lat' =>  35.9078, 'lng' => 127.7669, 'iso' => 'kr'],
    'South Korea'               => ['lat' =>  35.9078, 'lng' => 127.7669, 'iso' => 'kr'],
    'Emirados Árabes Unidos'    => ['lat' =>  23.4241, 'lng' =>  53.8478, 'iso' => 'ae'],
    'United Arab Emirates'      => ['lat' =>  23.4241, 'lng' =>  53.8478, 'iso' => 'ae'],
    'UAE'                       => ['lat' =>  23.4241, 'lng' =>  53.8478, 'iso' => 'ae'],
    'Filipinas'                 => ['lat' =>  12.8797, 'lng' => 121.7740, 'iso' => 'ph'],
    'Philippines'               => ['lat' =>  12.8797, 'lng' => 121.7740, 'iso' => 'ph'],
    'Geórgia'                   => ['lat' =>  42.3154, 'lng' =>  43.3569, 'iso' => 'ge'],
    'Georgia'                   => ['lat' =>  42.3154, 'lng' =>  43.3569, 'iso' => 'ge'],
    'Índia'                     => ['lat' =>  20.5937, 'lng' =>  78.9629, 'iso' => 'in'],
    'India'                     => ['lat' =>  20.5937, 'lng' =>  78.9629, 'iso' => 'in'],
    'Indonésia'                 => ['lat' =>  -0.7893, 'lng' => 113.9213, 'iso' => 'id'],
    'Indonesia'                 => ['lat' =>  -0.7893, 'lng' => 113.9213, 'iso' => 'id'],
    'Iraque'                    => ['lat' =>  33.2232, 'lng' =>  43.6793, 'iso' => 'iq'],
    'Iraq'                      => ['lat' =>  33.2232, 'lng' =>  43.6793, 'iso' => 'iq'],
    'Irão'                      => ['lat' =>  32.4279, 'lng' =>  53.6880, 'iso' => 'ir'],
    'Iran'                      => ['lat' =>  32.4279, 'lng' =>  53.6880, 'iso' => 'ir'],
    'Israel'                    => ['lat' =>  31.0461, 'lng' =>  34.8516, 'iso' => 'il'],
    'Japão'                     => ['lat' =>  36.2048, 'lng' => 138.2529, 'iso' => 'jp'],
    'Japan'                     => ['lat' =>  36.2048, 'lng' => 138.2529, 'iso' => 'jp'],
    'Jordânia'                  => ['lat' =>  30.5852, 'lng' =>  36.2384, 'iso' => 'jo'],
    'Jordan'                    => ['lat' =>  30.5852, 'lng' =>  36.2384, 'iso' => 'jo'],
    'Cazaquistão'               => ['lat' =>  48.0196, 'lng' =>  66.9237, 'iso' => 'kz'],
    'Kazakhstan'                => ['lat' =>  48.0196, 'lng' =>  66.9237, 'iso' => 'kz'],
    'Kuwait'                    => ['lat' =>  29.3117, 'lng' =>  47.4818, 'iso' => 'kw'],
    'Quirguistão'               => ['lat' =>  41.2044, 'lng' =>  74.7661, 'iso' => 'kg'],
    'Kyrgyzstan'                => ['lat' =>  41.2044, 'lng' =>  74.7661, 'iso' => 'kg'],
    'Laos'                      => ['lat' =>  19.8563, 'lng' => 102.4955, 'iso' => 'la'],
    'Líbano'                    => ['lat' =>  33.8547, 'lng' =>  35.8623, 'iso' => 'lb'],
    'Lebanon'                   => ['lat' =>  33.8547, 'lng' =>  35.8623, 'iso' => 'lb'],
    'Malásia'                   => ['lat' =>   4.2105, 'lng' => 101.9758, 'iso' => 'my'],
    'Malaysia'                  => ['lat' =>   4.2105, 'lng' => 101.9758, 'iso' => 'my'],
    'Maldivas'                  => ['lat' =>   3.2028, 'lng' =>  73.2207, 'iso' => 'mv'],
    'Maldives'                  => ['lat' =>   3.2028, 'lng' =>  73.2207, 'iso' => 'mv'],
    'Mongólia'                  => ['lat' =>  46.8625, 'lng' => 103.8467, 'iso' => 'mn'],
    'Mongolia'                  => ['lat' =>  46.8625, 'lng' => 103.8467, 'iso' => 'mn'],
    'Myanmar'                   => ['lat' =>  21.9162, 'lng' =>  95.9560, 'iso' => 'mm'],
    'Birmânia'                  => ['lat' =>  21.9162, 'lng' =>  95.9560, 'iso' => 'mm'],
    'Nepal'                     => ['lat' =>  28.3949, 'lng' =>  84.1240, 'iso' => 'np'],
    'Omã'                       => ['lat' =>  21.5126, 'lng' =>  55.9233, 'iso' => 'om'],
    'Oman'                      => ['lat' =>  21.5126, 'lng' =>  55.9233, 'iso' => 'om'],
    'Paquistão'                 => ['lat' =>  30.3753, 'lng' =>  69.3451, 'iso' => 'pk'],
    'Pakistan'                  => ['lat' =>  30.3753, 'lng' =>  69.3451, 'iso' => 'pk'],
    'Palestina'                 => ['lat' =>  31.9522, 'lng' =>  35.2332, 'iso' => 'ps'],
    'Palestine'                 => ['lat' =>  31.9522, 'lng' =>  35.2332, 'iso' => 'ps'],
    'Qatar'                     => ['lat' =>  25.3548, 'lng' =>  51.1839, 'iso' => 'qa'],
    'Catar'                     => ['lat' =>  25.3548, 'lng' =>  51.1839, 'iso' => 'qa'],
    'Singapura'                 => ['lat' =>   1.3521, 'lng' => 103.8198, 'iso' => 'sg'],
    'Singapore'                 => ['lat' =>   1.3521, 'lng' => 103.8198, 'iso' => 'sg'],
    'Síria'                     => ['lat' =>  34.8021, 'lng' =>  38.9968, 'iso' => 'sy'],
    'Syria'                     => ['lat' =>  34.8021, 'lng' =>  38.9968, 'iso' => 'sy'],
    'Sri Lanka'                 => ['lat' =>   7.8731, 'lng' =>  80.7718, 'iso' => 'lk'],
    'Tajiquistão'               => ['lat' =>  38.8610, 'lng' =>  71.2761, 'iso' => 'tj'],
    'Tajikistan'                => ['lat' =>  38.8610, 'lng' =>  71.2761, 'iso' => 'tj'],
    'Tailândia'                 => ['lat' =>  15.8700, 'lng' => 100.9925, 'iso' => 'th'],
    'Thailand'                  => ['lat' =>  15.8700, 'lng' => 100.9925, 'iso' => 'th'],
    'Timor-Leste'               => ['lat' =>  -8.8742, 'lng' => 125.7275, 'iso' => 'tl'],
    'East Timor'                => ['lat' =>  -8.8742, 'lng' => 125.7275, 'iso' => 'tl'],
    'Turquemenistão'            => ['lat' =>  38.9697, 'lng' =>  59.5563, 'iso' => 'tm'],
    'Turkmenistan'              => ['lat' =>  38.9697, 'lng' =>  59.5563, 'iso' => 'tm'],
    'Turquia'                   => ['lat' =>  38.9637, 'lng' =>  35.2433, 'iso' => 'tr'],
    'Turkey'                    => ['lat' =>  38.9637, 'lng' =>  35.2433, 'iso' => 'tr'],
    'Uzbequistão'               => ['lat' =>  41.3775, 'lng' =>  64.5853, 'iso' => 'uz'],
    'Uzbekistan'                => ['lat' =>  41.3775, 'lng' =>  64.5853, 'iso' => 'uz'],
    'Vietname'                  => ['lat' =>  14.0583, 'lng' => 108.2772, 'iso' => 'vn'],
    'Vietnam'                   => ['lat' =>  14.0583, 'lng' => 108.2772, 'iso' => 'vn'],
    'Iémen'                     => ['lat' =>  15.5527, 'lng' =>  48.5164, 'iso' => 'ye'],
    'Yemen'                     => ['lat' =>  15.5527, 'lng' =>  48.5164, 'iso' => 'ye'],

    // ── Oceânia ───────────────────────────────────────────────────────────────
    'Austrália'                 => ['lat' => -25.2744, 'lng' => 133.7751, 'iso' => 'au'],
    'Australia'                 => ['lat' => -25.2744, 'lng' => 133.7751, 'iso' => 'au'],
    'Fiji'                      => ['lat' => -17.7134, 'lng' => 178.0650, 'iso' => 'fj'],
    'Kiribati'                  => ['lat' =>   1.8709, 'lng' => -157.3630, 'iso' => 'ki'],
    'Ilhas Marshall'            => ['lat' =>   7.1315, 'lng' => 171.1845, 'iso' => 'mh'],
    'Marshall Islands'          => ['lat' =>   7.1315, 'lng' => 171.1845, 'iso' => 'mh'],
    'Micronésia'                => ['lat' =>   7.4256, 'lng' => 150.5508, 'iso' => 'fm'],
    'Micronesia'                => ['lat' =>   7.4256, 'lng' => 150.5508, 'iso' => 'fm'],
    'Nauru'                     => ['lat' =>  -0.5228, 'lng' => 166.9315, 'iso' => 'nr'],
    'Nova Zelândia'             => ['lat' => -40.9006, 'lng' => 174.8860, 'iso' => 'nz'],
    'New Zealand'               => ['lat' => -40.9006, 'lng' => 174.8860, 'iso' => 'nz'],
    'Palau'                     => ['lat' =>   7.5150, 'lng' => 134.5825, 'iso' => 'pw'],
    'Papua Nova Guiné'          => ['lat' =>  -6.3149, 'lng' => 143.9555, 'iso' => 'pg'],
    'Papua New Guinea'          => ['lat' =>  -6.3149, 'lng' => 143.9555, 'iso' => 'pg'],
    'Samoa'                     => ['lat' => -13.7590, 'lng' => -172.1046, 'iso' => 'ws'],
    'Ilhas Salomão'             => ['lat' =>  -9.6457, 'lng' => 160.1562, 'iso' => 'sb'],
    'Solomon Islands'           => ['lat' =>  -9.6457, 'lng' => 160.1562, 'iso' => 'sb'],
    'Tonga'                     => ['lat' => -21.1790, 'lng' => -175.1982, 'iso' => 'to'],
    'Tuvalu'                    => ['lat' =>  -7.1095, 'lng' => 177.6493, 'iso' => 'tv'],
    'Vanuatu'                   => ['lat' => -15.3767, 'lng' => 166.9592, 'iso' => 'vu'],

    // ── Especial ──────────────────────────────────────────────────────────────
    'Worldwide'                 => ['lat' =>   0.0000, 'lng' =>   0.0000, 'iso' => ''],
    'Unknown'                   => ['lat' =>   0.0000, 'lng' =>   0.0000, 'iso' => ''],
    'Desconhecido'              => ['lat' =>   0.0000, 'lng' =>   0.0000, 'iso' => ''],
];

$meta = null;
foreach ($country_meta as $k => $v) {
    if (mb_strtolower($k) === mb_strtolower($country_name)) {
        $meta = $v;
        break;
    }
}
if (!$meta) $meta = ['lat' => 0, 'lng' => 0, 'iso' => ''];

$flag_url = $meta['iso'] ? "https://flagcdn.com/48x36/{$meta['iso']}.png" : '';
$flag_20  = $meta['iso'] ? "https://flagcdn.com/20x15/{$meta['iso']}.png" : '';

// ── Anos disponíveis ──────────────────────────
$years_q = $db->prepare("
    SELECT DISTINCT s.year_stream
    FROM _stream s
    JOIN _track t ON t.id_track = s.id_track
    WHERE t.id_users = ?
    ORDER BY s.year_stream DESC
");
$years_q->execute([$id_users]);
$available_years = $years_q->fetchAll(PDO::FETCH_COLUMN);
if (empty($available_years)) $available_years = [(int)date('Y')];

// ── Verificações de conta (para alertas) ──────
$plan_selected  = $user['plan_selected'];
$plan_paid      = ($user['status_user'] === 'active' && !empty($user['plan_activated_at']));
$email_verified = (bool)$user['email_verified'];

$plan = null;
if ($plan_selected) {
    $ps = $db->prepare('SELECT * FROM _plans WHERE id_plan = ?');
    $ps->execute([$plan_selected]);
    $plan = $ps->fetch();
}

$as = $db->prepare('SELECT COUNT(*) AS total FROM _artist WHERE id_users = ?');
$as->execute([$id_users]);
$has_artist = (int)($as->fetch()['total'] ?? 0) > 0;

$ba = $db->prepare("SELECT id_account FROM _account WHERE id_users = ? AND status_account = 'verified' LIMIT 1");
$ba->execute([$id_users]);
$bank_account = $ba->fetch();

// ── Determinar se é Worldwide ─────────────────
$iso2 = $meta['iso'] ?? '';
$is_worldwide = ($iso2 === '' || strtolower($country_name) === 'worldwide');

// ── TOTAIS (CARDS) ────────────────────────────
if ($is_worldwide) {
    $totals_q = $db->prepare("
        SELECT
            COALESCE(SUM(s.streams), 0) AS total_streams,
            COALESCE(SUM(s.revenue), 0) AS total_revenue,
            COUNT(DISTINCT t.id_track) AS total_tracks
        FROM _stream s
        JOIN _track t ON t.id_track = s.id_track
        WHERE t.id_users = ? AND s.year_stream = ?
    ");
    $totals_q->execute([$id_users, $filter_year]);
    $country_totals = $totals_q->fetch();
} else {
    $totals_q = $db->prepare("
        SELECT
            COALESCE(SUM(sc.streams), 0) AS total_streams,
            COALESCE(SUM(sc.revenue), 0) AS total_revenue,
            COUNT(DISTINCT sc.id_track) AS total_tracks
        FROM _stream_country sc
        JOIN _track t ON t.id_track = sc.id_track
        WHERE t.id_users = ?
          AND sc.year_stream = ?
          AND LOWER(sc.country_code) = LOWER(?)
    ");
    $totals_q->execute([$id_users, $filter_year, $iso2]);
    $country_totals = $totals_q->fetch();
}
$total_streams_all = (int)($country_totals['total_streams'] ?? 0);
$total_revenue_all = (float)($country_totals['total_revenue'] ?? 0);
$total_tracks_all  = (int)($country_totals['total_tracks'] ?? 0);

// ── FAIXAS INDIVIDUAIS (TABELA DETALHADA) ─────
$tracks_in_country = [];
if ($is_worldwide) {
    $tracks_q = $db->prepare("
        SELECT
            t.id_track,
            t.title_track,
            t.isrc,
            t.duration_seconds,
            t.explicit,
            t.language,
            t.name_author,
            t.name_author_feat,
            al.title_album,
            al.type_album,
            al.img_cover,
            a.stage_name,
            COALESCE(SUM(s.streams), 0) AS streams,
            COALESCE(SUM(s.revenue), 0) AS revenue
        FROM _stream s
        INNER JOIN _track t ON t.id_track = s.id_track
        INNER JOIN _album al ON al.id_album = t.id_album
        LEFT JOIN _artist a ON a.id_artist = al.id_artist
        WHERE t.id_users = ?
          AND s.year_stream = ?
          AND t.status_track IN ('active','approved')
          AND al.status_album IN ('approved','active')
        GROUP BY t.id_track
        ORDER BY streams DESC
        LIMIT 100
    ");
    $tracks_q->execute([$id_users, $filter_year]);
    $tracks_in_country = $tracks_q->fetchAll(PDO::FETCH_ASSOC);
} else {
    if ($iso2) {
        $tracks_q = $db->prepare("
            SELECT
                t.id_track,
                t.title_track,
                t.isrc,
                t.duration_seconds,
                t.explicit,
                t.language,
                t.name_author,
                t.name_author_feat,
                al.title_album,
                al.type_album,
                al.img_cover,
                a.stage_name,
                COALESCE(SUM(sc.streams), 0) AS streams,
                COALESCE(SUM(sc.revenue), 0) AS revenue
            FROM _stream_country sc
            INNER JOIN _track t ON t.id_track = sc.id_track
            INNER JOIN _album al ON al.id_album = t.id_album
            LEFT JOIN _artist a ON a.id_artist = al.id_artist
            WHERE t.id_users = ?
              AND sc.year_stream = ?
              AND LOWER(sc.country_code) = LOWER(?)
              AND t.status_track IN ('active','approved')
              AND al.status_album IN ('approved','active')
            GROUP BY t.id_track
            ORDER BY streams DESC
            LIMIT 100
        ");
        $tracks_q->execute([$id_users, $filter_year, $iso2]);
        $tracks_in_country = $tracks_q->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ── Álbuns distribuídos neste território ─────
if ($is_worldwide) {
    // Worldwide: considerar álbuns com território "Worldwide"
    $albums_q = $db->prepare("
        SELECT
            al.id_album,
            al.title_album,
            al.type_album,
            al.img_cover,
            al.territory,
            al.release_date,
            al.genre_main,
            a.stage_name,
            COUNT(DISTINCT t.id_track) AS num_tracks,
            COALESCE(SUM(s.streams), 0) AS total_streams,
            COALESCE(SUM(s.revenue), 0) AS total_revenue
        FROM _album al
        LEFT JOIN _artist a ON a.id_artist = al.id_artist
        LEFT JOIN _track t ON t.id_album = al.id_album AND t.status_track IN ('active','approved')
        LEFT JOIN _stream s ON s.id_track = t.id_track AND s.year_stream = ?
        WHERE al.id_users = ?
          AND al.status_album IN ('approved','active')
          AND (al.territory LIKE '%Worldwide%' OR al.territory IS NULL OR al.territory = '')
        GROUP BY al.id_album
        ORDER BY total_streams DESC
    ");
    $albums_q->execute([$filter_year, $id_users]);
} else {
    // País específico: álbuns cujo território contém o código ISO ou "Worldwide"
    $albums_q = $db->prepare("
        SELECT
            al.id_album,
            al.title_album,
            al.type_album,
            al.img_cover,
            al.territory,
            al.release_date,
            al.genre_main,
            a.stage_name,
            COUNT(DISTINCT t.id_track) AS num_tracks,
            COALESCE(SUM(sc.streams), 0) AS total_streams,
            COALESCE(SUM(sc.revenue), 0) AS total_revenue
        FROM _album al
        LEFT JOIN _artist a ON a.id_artist = al.id_artist
        LEFT JOIN _track t ON t.id_album = al.id_album AND t.status_track IN ('active','approved')
        LEFT JOIN _stream_country sc ON sc.id_track = t.id_track
                                   AND sc.year_stream = ?
                                   AND LOWER(sc.country_code) = LOWER(?)
        WHERE al.id_users = ?
          AND al.status_album IN ('approved','active')
          AND (
              al.territory LIKE '%Worldwide%'
              OR FIND_IN_SET(?, REPLACE(al.territory, ' ', '')) > 0
          )
        GROUP BY al.id_album
        ORDER BY total_streams DESC
    ");
    $albums_q->execute([$filter_year, $iso2, $id_users, $iso2]);
}
$albums = $albums_q->fetchAll(PDO::FETCH_ASSOC);

$total_albums = count($albums);

// ── Dados para mapa (Worldwide) ───────────────
$map_countries = [];
if ($is_worldwide) {
    // Usar o array $country_meta para obter coordenadas (sem necessidade de tabela extra)
    $map_q = $db->prepare("
        SELECT LOWER(sc.country_code) as code, sc.country_name, SUM(sc.streams) as streams
        FROM _stream_country sc
        JOIN _track t ON t.id_track = sc.id_track
        WHERE t.id_users = ? AND sc.year_stream = ?
        GROUP BY sc.country_code, sc.country_name
        HAVING streams > 0
        ORDER BY streams DESC
        LIMIT 30
    ");
    $map_q->execute([$id_users, $filter_year]);
    $raw = $map_q->fetchAll(PDO::FETCH_ASSOC);
    foreach ($raw as $r) {
        $code = $r['code'];
        $coord = null;
        foreach ($country_meta as $k => $v) {
            if (isset($v['iso']) && strtolower($v['iso']) === $code) {
                $coord = $v;
                break;
            }
        }
        if ($coord && $coord['lat'] != 0) {
            $map_countries[] = [
                'lat' => $coord['lat'],
                'lng' => $coord['lng'],
                'name' => $r['country_name'] ?? $k,
                'streams' => (int)$r['streams']
            ];
        }
    }
}

$base_url  = rtrim(APP_URL, '/');
$cover_url = $base_url . '/assets/comprovantes/uploads/covers/';
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title><?php echo htmlspecialchars($country_name); ?> — Estatísticas — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="<?php echo APP_URL ?>/assets/img/icones/wasomupfy_fiv.png" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?php echo APP_URL ?>/css/statistics.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
    /* Estilos mantidos do original */
    .country-hero {
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 28px;
        background: linear-gradient(135deg, #0f3460 0%, #16213e 60%, #1a1a2e 100%);
        position: relative;
        min-height: 150px;
    }

    .country-hero .hero-body {
        position: relative;
        z-index: 1;
        padding: 28px 28px 24px;
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .country-flag-lg {
        width: 80px;
        height: 60px;
        border-radius: 8px;
        object-fit: cover;
        box-shadow: 0 4px 16px rgba(0, 0, 0, .4);
        flex-shrink: 0;
    }

    .country-flag-placeholder {
        width: 80px;
        height: 60px;
        border-radius: 8px;
        background: rgba(255, 255, 255, .1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
    }

    .country-hero-info h2 {
        color: #fff;
        font-weight: 800;
        margin: 0 0 4px;
    }

    .country-hero-info .meta {
        color: rgba(255, 255, 255, .6);
        font-size: .82rem;
    }

    .country-hero-info .meta span {
        margin-right: 14px;
    }

    .stat-hero-card {
        border-radius: 16px;
        padding: 18px 20px;
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        background: var(--card-bg, #fff);
        position: relative;
        overflow: hidden;
    }

    .stat-hero-card .stat-label {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-muted, #6c757d);
        margin-bottom: 5px;
    }

    .stat-hero-card .stat-value {
        font-size: 1.65rem;
        font-weight: 900;
        line-height: 1;
    }

    .stat-hero-card .stat-icon {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 2.6rem;
        opacity: .07;
    }

    .filter-bar {
        background: var(--card-bg, #fff);
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        border-radius: 14px;
        padding: 14px 18px;
        margin-bottom: 22px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
    }

    .filter-bar label {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-muted, #6c757d);
        display: block;
        margin-bottom: 3px;
    }

    #country-map {
        height: 260px;
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 24px;
    }

    .album-cover {
        width: 42px;
        height: 42px;
        border-radius: 8px;
        object-fit: cover;
    }

    .album-cover-placeholder {
        width: 42px;
        height: 42px;
        border-radius: 8px;
        background: rgba(255, 0, 137, .08);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .type-badge {
        font-size: .6rem;
        border-radius: 4px;
        padding: 1px 5px;
    }

    .data-notice {
        background: rgba(255, 0, 137, .04);
        border: 1px solid rgba(255, 0, 137, .14);
        border-radius: 14px;
        padding: 14px 18px;
        margin-bottom: 22px;
        display: flex;
        gap: 10px;
        align-items: flex-start;
        font-size: .8rem;
        color: var(--text-muted, #6c757d);
    }

    .empty-section {
        text-align: center;
        padding: 36px 20px;
        color: var(--text-muted, #6c757d);
    }

    .empty-section .icon {
        font-size: 2.2rem;
        opacity: .15;
        margin-bottom: 8px;
    }

    .explicit-badge {
        font-size: .6rem;
        background: #333;
        color: #fff;
        border-radius: 3px;
        padding: 1px 4px;
        vertical-align: middle;
        margin-left: 4px;
    }
    </style>
</head>

<body>
    <?php require_once __DIR__ . '/../include/sidebar.php'; ?>

    <div class="container my-4">

        <?php renderDashboardAlerts($user, $platform); ?>

        <?php
        $alertColors = [
            'danger'  => ['bg' => 'rgba(239,68,68,.08)',  'border' => 'rgba(239,68,68,.25)',  'text' => '#ef4444'],
            'warning' => ['bg' => 'rgba(234,179,8,.08)',  'border' => 'rgba(234,179,8,.25)',  'text' => '#eab308'],
            'info'    => ['bg' => 'rgba(99,102,241,.08)', 'border' => 'rgba(99,102,241,.25)', 'text' => '#6366f1'],
        ];
        function wuAlert(string $type, string $icon, string $message, ?array $action = null, bool $dismiss = true, string $id = ''): void
        {
            global $alertColors;
            $c   = $alertColors[$type] ?? $alertColors['info'];
            $eid = $id ?: ('wuAlert_' . md5($message));
            echo "<div id=\"{$eid}\" style=\"display:flex;align-items:flex-start;gap:10px;"
                . "background:{$c['bg']};border:1px solid {$c['border']};border-radius:12px;"
                . "padding:.75rem 1rem;font-size:.83rem;margin-bottom:.6rem;transition:opacity .3s;\">";
            echo "<i class=\"bi {$icon}\" style=\"font-size:1rem;flex-shrink:0;margin-top:2px;color:{$c['text']};\"></i>";
            echo '<span>' . $message;
            if ($action) echo " <a href=\"{$action['url']}\" style=\"color:{$c['text']};font-weight:700;text-decoration:underline;white-space:nowrap\">{$action['label']} &rarr;</a>";
            echo '</span>';
            if ($dismiss) echo "<button type=\"button\" style=\"margin-left:auto;background:none;border:none;font-size:1.1rem;cursor:pointer;opacity:.5\" onclick=\"(function(el){el.style.opacity='0';setTimeout(function(){el.style.display='none'},300)})(document.getElementById('{$eid}'))\">&times;</button>";
            echo '</div>';
        }
        ?>

        <!-- ── Hero ── -->
        <div class="country-hero">
            <div class="hero-body">
                <?php if ($is_worldwide): ?>
                <div class="country-flag-placeholder"><i class="bi bi-globe"></i></div>
                <div class="country-hero-info">
                    <h2><i class="bi bi-globe2 me-2"></i>Worldwide</h2>
                    <div class="meta">
                        <span><i class="bi bi-disc me-1"></i><?php echo $total_albums; ?> álbuns distribuídos</span>
                        <span><i class="bi bi-music-note me-1"></i><?php echo $total_tracks_all; ?> faixas com
                            streams</span>
                    </div>
                </div>
                <?php else: ?>
                <?php if ($flag_url): ?>
                <img class="country-flag-lg" src="<?php echo $flag_url; ?>"
                    alt="<?php echo htmlspecialchars($country_name); ?>" />
                <?php else: ?>
                <div class="country-flag-placeholder"><i class="bi bi-globe"></i></div>
                <?php endif; ?>
                <div class="country-hero-info">
                    <h2>
                        <?php if ($flag_20): ?>
                        <img src="<?php echo $flag_20; ?>" alt=""
                            style="vertical-align:middle;margin-right:8px;border-radius:3px;height:16px" />
                        <?php endif; ?>
                        <?php echo htmlspecialchars($country_name); ?>
                    </h2>
                    <div class="meta">
                        <span><i class="bi bi-disc me-1"></i><?php echo $total_albums; ?>
                            álbum<?php echo $total_albums != 1 ? 'ns' : ''; ?> distribuídos</span>
                        <span><i class="bi bi-music-note me-1"></i><?php echo $total_tracks_all; ?>
                            faixa<?php echo $total_tracks_all != 1 ? 's' : ''; ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <div class="ms-auto d-flex gap-2 flex-wrap align-items-start">
                    <a href="statistics#country" class="btn btn-sm"
                        style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:10px">
                        <i class="bi bi-arrow-left me-1"></i>Voltar
                    </a>
                </div>
            </div>
        </div>

        <!-- ── Filtro de ano ── -->
        <form method="GET" action="country-details">
            <input type="hidden" name="country" value="<?php echo htmlspecialchars($country_name); ?>" />
            <div class="filter-bar">
                <div>
                    <label>Ano</label>
                    <select name="year" class="form-select form-select-sm" style="min-width:100px"
                        onchange="this.form.submit()">
                        <?php foreach ($available_years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $filter_year ? 'selected' : ''; ?>>
                            <?php echo $y; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ms-auto d-flex align-items-end" style="font-size:.78rem;color:var(--text-muted,#6c757d)">
                    <i class="bi bi-info-circle me-1"></i>Dados de <?php echo $filter_year; ?>
                </div>
            </div>
        </form>

        <!-- ── Aviso ── -->
        <?php if ($is_worldwide): ?>
        <div class="data-notice">
            <i class="bi bi-globe2 mt-1" style="color:#0d6efd;flex-shrink:0"></i>
            <div>
                <strong>Visão global (Worldwide).</strong> Os dados abaixo representam o total de streams e receitas em
                todos os territórios onde a tua música está disponível.
            </div>
        </div>
        <?php else: ?>
        <div class="data-notice">
            <i class="bi bi-check-circle-fill mt-1" style="color:#198754;flex-shrink:0"></i>
            <div>
                <strong>Dados geográficos disponíveis.</strong> Streams e receitas baseados nos relatórios das
                plataformas para <strong><?php echo htmlspecialchars($country_name); ?></strong>.
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Cards de totais ── -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-hero-card">
                    <div class="stat-label">Álbuns distribuídos</div>
                    <div class="stat-value" style="color:#FF0089"><?php echo $total_albums; ?></div>
                    <i class="bi bi-disc stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-hero-card">
                    <div class="stat-label">Faixas com streams</div>
                    <div class="stat-value" style="color:#0d6efd"><?php echo $total_tracks_all; ?></div>
                    <i class="bi bi-music-note stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-hero-card">
                    <div class="stat-label">Streams totais</div>
                    <div class="stat-value" style="color:#6f42c1"><?php echo number_format((int)$total_streams_all); ?>
                    </div>
                    <i class="bi bi-headphones stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-hero-card">
                    <div class="stat-label">Receita (USD)</div>
                    <div class="stat-value" style="color:#198754;font-size:1.3rem">
                        $<?php echo number_format((float)$total_revenue_all, 2); ?></div>
                    <i class="bi bi-currency-dollar stat-icon"></i>
                </div>
            </div>
        </div>

        <!-- ── Mapa Leaflet ── -->
        <?php if (!$is_worldwide && ($meta['lat'] != 0 || $meta['lng'] != 0)): ?>
        <div class="card mb-4" style="border-radius:16px;overflow:hidden">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-globe2 me-2 text-pink"></i>Localização</h6>
            </div>
            <div id="country-map"></div>
        </div>
        <?php elseif ($is_worldwide && !empty($map_countries)): ?>
        <div class="card mb-4" style="border-radius:16px;overflow:hidden">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-pin-map-fill me-2 text-pink"></i>Distribuição de streams por país</h6>
            </div>
            <div id="country-map"></div>
        </div>
        <?php endif; ?>

        <!-- ── Tabela de álbuns distribuídos ── -->
        <div class="table-card mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-disc me-2 text-pink"></i>Álbuns distribuídos em
                        <em><?php echo htmlspecialchars($country_name); ?></em>
                    </h6>
                    <span class="badge bg-secondary"><?php echo $total_albums; ?></span>
                </div>
                <?php if (empty($albums)): ?>
                <div class="empty-section">
                    <div class="icon"><i class="bi bi-disc"></i></div>
                    <div class="small fw-semibold mb-1">Nenhum álbum distribuído neste território.</div>
                    <div class="small">Para distribuir para <?php echo htmlspecialchars($country_name); ?>, edita o
                        campo <strong>Território</strong> no teu álbum.</div>
                    <a href="<?php echo  APP_URL . '/'.  APP_URL_PANEL ?>/releases" class="btn btn-sm btn-pink mt-3">Ver
                        lançamentos</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table id="albumsTable" class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:52px">Capa</th>
                                <th>Álbum</th>
                                <th>Artista</th>
                                <th>Tipo</th>
                                <th>Faixas</th>
                                <th>Streams <?php echo $filter_year; ?></th>
                                <th>Receita (USD)</th>
                                <th>Territórios</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($albums as $al):
                                    $type_colors = ['single'=>'bg-primary','EP'=>'bg-warning text-dark','album'=>'bg-success','mixtape'=>'bg-secondary'];
                                    $tc = $type_colors[strtolower($al['type_album'] ?? '')] ?? 'bg-secondary';
                                ?>
                            <tr>
                                <td><?php if ($al['img_cover']): ?><img class="album-cover"
                                        src="<?php echo htmlspecialchars($cover_url . $al['img_cover']); ?>"
                                        onerror="this.outerHTML='<div class=\'album-cover-placeholder\'>🎵</div>'"
                                        alt="" /><?php else: ?><div class="album-cover-placeholder">🎵</div>
                                    <?php endif; ?></td>
                                <td>
                                    <div class="fw-semibold" style="font-size:.87rem">
                                        <?php echo htmlspecialchars($al['title_album']); ?></div>
                                    <?php if ($al['release_date']): ?><div
                                        style="font-size:.7rem;color:var(--text-muted,#6c757d)">
                                        <?php echo date('d/m/Y', strtotime($al['release_date'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?php echo htmlspecialchars($al['stage_name'] ?? '—'); ?></td>
                                <td><span
                                        class="badge type-badge <?php echo $tc; ?>"><?php echo strtoupper($al['type_album']); ?></span>
                                </td>
                                <td class="small text-center"><?php echo (int)$al['num_tracks']; ?></td>
                                <td class="fw-bold" style="color:#FF0089">
                                    <?php echo number_format((int)$al['total_streams']); ?></td>
                                <td class="small fw-semibold" style="color:#198754">
                                    $<?php echo number_format((float)$al['total_revenue'], 4); ?></td>
                                <td><span class="badge bg-light text-muted"
                                        style="font-size:.65rem;max-width:120px;white-space:normal;text-align:left"><?php echo htmlspecialchars(mb_substr($al['territory'], 0, 50)); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Tabela de Faixas Individuais ── -->
        <?php if (!empty($tracks_in_country)): ?>
        <div class="table-card mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-music-note-list me-2 text-pink"></i>Faixas com streams
                        <?php echo $is_worldwide ? '(global)' : 'em ' . htmlspecialchars($country_name); ?></h6>
                    <span class="badge bg-secondary"><?php echo count($tracks_in_country); ?> faixas</span>
                </div>
                <div class="table-responsive">
                    <table id="tracksTable" class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:52px">Capa</th>
                                <th>Faixa</th>
                                <th>Artista</th>
                                <th>Álbum</th>
                                <th>ISRC</th>
                                <th>Duração</th>
                                <th>Streams <?php echo $filter_year; ?></th>
                                <th>Receita (USD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tracks_in_country as $track): ?>
                            <tr>
                                <td><?php if ($track['img_cover']): ?><img class="album-cover"
                                        src="<?php echo htmlspecialchars($cover_url . $track['img_cover']); ?>"
                                        onerror="this.outerHTML='<div class=\'album-cover-placeholder\'>🎵</div>'"
                                        alt="" /><?php else: ?><div class="album-cover-placeholder">🎵</div>
                                    <?php endif; ?></td>
                                <td>
                                    <div class="fw-semibold" style="font-size:.87rem">
                                        <?php echo htmlspecialchars($track['title_track']); ?><?php if ($track['explicit'] === 'YES'): ?><span
                                            class="explicit-badge">E</span><?php endif; ?></div>
                                    <?php if ($track['name_author_feat']): ?><div
                                        style="font-size:.7rem;color:var(--text-muted,#6c757d)">feat.
                                        <?php echo htmlspecialchars($track['name_author_feat']); ?></div><?php endif; ?>
                                    <?php if ($track['language']): ?><div
                                        style="font-size:.65rem;color:var(--text-muted,#6c757d)">
                                        <?php echo htmlspecialchars($track['language']); ?></div><?php endif; ?>
                                </td>
                                <td class="small">
                                    <?php echo htmlspecialchars($track['stage_name'] ?? $track['name_author'] ?? '—'); ?>
                                </td>
                                <td><span class="badge bg-light text-muted me-1"
                                        style="font-size:.6rem"><?php echo strtoupper($track['type_album']); ?></span><?php echo htmlspecialchars($track['title_album']); ?>
                                </td>
                                <td class="small font-monospace"><?php echo htmlspecialchars($track['isrc'] ?? '—'); ?>
                                </td>
                                <td class="small text-muted">
                                    <?php $sec = (int)$track['duration_seconds']; echo $sec ? gmdate($sec >= 3600 ? 'H:i:s' : 'i:s', $sec) : '—'; ?>
                                </td>
                                <td class="fw-bold" style="color:#FF0089">
                                    <?php echo number_format((int)$track['streams']); ?></td>
                                <td class="small fw-semibold" style="color:#198754">
                                    $<?php echo number_format((float)$track['revenue'], 4); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="empty-section">
            <div class="icon"><i class="bi bi-music-note"></i></div>
            <div class="small fw-semibold mb-1">Nenhuma faixa com streams
                <?php echo $is_worldwide ? '' : 'em ' . htmlspecialchars($country_name); ?>.</div>
            <div class="small">Os streams aparecerão aqui quando as plataformas enviarem relatórios.</div>
        </div>
        <?php endif; ?>

    </div><!-- /container -->

    <!-- ═══ JS ═══ -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="<?php echo APP_URL ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL ?>/js/wp.tools.js"></script>
    <script>
    (function() {
        // Ativar tooltips do Bootstrap
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

        // DataTable para álbuns
        <?php if (!empty($albums)): ?>
        $(document).ready(function() {
            if ($('#albumsTable').length) {
                $('#albumsTable').DataTable({
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    lengthChange: false,
                    pageLength: 10,
                    order: [
                        [5, 'desc']
                    ],
                    columnDefs: [{
                        orderable: false,
                        targets: [0, 7]
                    }],
                    language: {
                        search: 'Pesquisar álbum:',
                        info: 'A mostrar _START_ a _END_ de _TOTAL_ álbuns',
                        paginate: {
                            next: 'Próximo',
                            previous: 'Anterior'
                        },
                        emptyTable: 'Nenhum álbum encontrado.'
                    }
                });
            }
        });
        <?php endif; ?>

        // DataTable para faixas
        <?php if (!empty($tracks_in_country)): ?>
        $(document).ready(function() {
            if ($('#tracksTable').length) {
                $('#tracksTable').DataTable({
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    lengthChange: false,
                    pageLength: 10,
                    order: [
                        [6, 'desc']
                    ],
                    columnDefs: [{
                        orderable: false,
                        targets: [0]
                    }],
                    language: {
                        search: 'Pesquisar faixa:',
                        info: 'A mostrar _START_ a _END_ de _TOTAL_ faixas',
                        paginate: {
                            next: 'Próximo',
                            previous: 'Anterior'
                        },
                        emptyTable: 'Nenhuma faixa encontrada.'
                    }
                });
            }
        });
        <?php endif; ?>

        // Mapa Leaflet
        var map = null;
        var mapElement = document.getElementById('country-map');
        if (mapElement) {
            <?php if (!$is_worldwide && ($meta['lat'] != 0 || $meta['lng'] != 0)): ?>
            map = L.map('country-map', {
                    zoomControl: true,
                    scrollWheelZoom: false
                })
                .setView([<?php echo (float)$meta['lat']; ?>, <?php echo (float)$meta['lng']; ?>], 4);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);
            L.circleMarker([<?php echo (float)$meta['lat']; ?>, <?php echo (float)$meta['lng']; ?>], {
                    color: '#FF0089',
                    fillColor: '#FF0089',
                    fillOpacity: 0.5,
                    radius: 14
                }).addTo(map)
                .bindPopup(
                    '<b><?php echo htmlspecialchars($country_name, ENT_QUOTES); ?></b><br><?php echo $total_albums; ?> álbum<?php echo $total_albums != 1 ? 'ns' : ''; ?> distribuídos'
                )
                .openPopup();
            <?php elseif ($is_worldwide && !empty($map_countries)): ?>
            map = L.map('country-map', {
                    zoomControl: true,
                    scrollWheelZoom: false
                })
                .setView([20, 0], 2);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);
            var countries = <?php echo json_encode($map_countries) ?: '[]'; ?>;
            if (Array.isArray(countries)) {
                countries.forEach(function(c) {
                    if (c.lat && c.lng) {
                        var size = Math.min(20, 8 + Math.log(c.streams + 1) * 2);
                        L.circleMarker([c.lat, c.lng], {
                            color: '#FF0089',
                            fillColor: '#FF0089',
                            fillOpacity: 0.5,
                            radius: size
                        }).addTo(map).bindPopup('<b>' + c.name + '</b><br>' + c.streams
                            .toLocaleString() + ' streams');
                    }
                });
            }
            <?php endif; ?>
        }
    })();
    </script>
</body>

</html>