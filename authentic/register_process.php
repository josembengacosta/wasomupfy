<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Cadastro
// Arquivo: authentic/register_process.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions.php';
startSecureSession();

/**
 * Armazena os dados do formulário na sessão para repopular em caso de erro.
 * Remove campos sensíveis (senha, CSRF, honeypot).
 */
function flashFormData(array $data): void
{
    unset($data['password_user'], $data['confirm_password'], $data['csrf_token'], $data['hairypot']);
    $_SESSION['register_form_data'] = $data;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/register');
}

checkHoneypot('hairypot');

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'csrf']);
}

if (!isLoginAllowed()) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'global_disabled']);
}

// Verificar se registos estão permitidos (tabela _platform)
$db = getDB();
$platform = $db->query("SELECT allow_register FROM _platform LIMIT 1")->fetch();
if (!$platform || !$platform['allow_register']) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'register_disabled']);
}

// Throttle simples por IP (máx 3 tentativas em 1 hora)
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$throttle_key = 'reg_throttle_' . $ip;
$attempts = $_SESSION[$throttle_key] ?? 0;
if ($attempts > 3) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'too_many_attempts']);
}
$_SESSION[$throttle_key] = $attempts + 1;

// ══════════════════════════════════════════════
// 1. RECOLHER E VALIDAR
// ══════════════════════════════════════════════

$email         = strtolower(trim($_POST['email_user']  ?? ''));
$confirm_email = strtolower(trim($_POST['confirm_email'] ?? ''));
$full_name     = trim($_POST['fullname_user'] ?? '');
$gender_raw    = trim($_POST['gender']        ?? '');
$country       = trim($_POST['country_user']  ?? '');
$city          = trim($_POST['city']          ?? '');
$phone         = trim($_POST['tel_user']      ?? '');
$password      = $_POST['password_user']      ?? '';
$confirm_pass  = $_POST['confirm_password']   ?? '';
$terms         = isset($_POST['terms_agree']);

$birth_day   = (int)($_POST['birth_day']   ?? 0);
$birth_month = (int)($_POST['birth_month'] ?? 0);
$birth_year  = (int)($_POST['birth_year']  ?? 0);

$plan_slug = $_SESSION['register_plan'] ?? null;

// ─── Validações ─────────────────────────────────

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'invalid_email']);
}
if ($email !== $confirm_email) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'email_mismatch']);
}
if (emailExists($email)) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'email_taken']);
}

$name_parts = array_filter(explode(' ', $full_name));
if (strlen($full_name) < 2 || strlen($full_name) > 100 || count($name_parts) < 1) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'invalid_name']);
}

if (!$birth_day || !$birth_month || !$birth_year || !checkdate($birth_month, $birth_day, $birth_year)) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'invalid_date']);
}
$birth_obj = new DateTime("$birth_year-$birth_month-$birth_day");
$age = (new DateTime())->diff($birth_obj)->y;
if ($age < 18) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'underage']);
}
$birth_date = sprintf('%04d-%02d-%02d', $birth_year, $birth_month, $birth_day);

$gender_map = ['M' => 'M', 'F' => 'F', 'O' => 'Outro'];
if (!isset($gender_map[$gender_raw])) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'invalid_gender']);
}
$gender = $gender_map[$gender_raw];

if (empty($country) || strlen($country) !== 2) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'invalid_country']);
}
if (empty($city) || strlen($city) < 2) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'invalid_city']);
}

if ($phone) {
    // Remove espaços, traços, parênteses e pontos
    $phone_clean = preg_replace('/[\s\-\(\)\.]/', '', $phone);
    if (!preg_match('/^\+?\d{7,15}$/', $phone_clean)) {
        flashFormData($_POST);
        redirect('/register', ['error' => 'invalid_phone']);
    }
    $phone = $phone_clean; // armazena apenas dígitos e possível '+'
}

if (strlen($password) < 10) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'weak_password']);
}
if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'weak_password']);
}
if ($password !== $confirm_pass) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'password_mismatch']);
}

if (!$terms) {
    flashFormData($_POST);
    redirect('/register', ['error' => 'terms']);
}

// ══════════════════════════════════════════════
// 2. PREPARAR DADOS
// ══════════════════════════════════════════════

$name_arr    = array_values($name_parts);
$first_name  = $name_arr[0];
$second_name = isset($name_arr[1]) ? implode(' ', array_slice($name_arr, 1)) : '';

$username = generateUniqueUsername($first_name, $second_name);
$plan_id  = $plan_slug ? getPlanIdBySlug($plan_slug) : null;

// ══════════════════════════════════════════════
// 3. CRIAR UTILIZADOR
// ══════════════════════════════════════════════

try {
    $id_users = createUser([
        'first_name'  => $first_name,
        'second_name' => $second_name,
        'user_name'   => $username,
        'email'       => $email,
        'password'    => $password,
        'gender'      => $gender,
        'birth_date'  => $birth_date,
        'country'     => $country,
        'city'        => $city,
        'phone'       => $phone ?: null,
        'ip'          => $ip,
        'plan_id'     => $plan_id,
    ]);
} catch (Exception $e) {
    error_log('[REGISTER ERROR] ' . $e->getMessage());
    flashFormData($_POST);
    redirect('/register', ['error' => 'server']);
}

unset($_SESSION['register_plan'], $_SESSION[$throttle_key]);

// ══════════════════════════════════════════════
// 4. ENVIAR EMAIL DE VERIFICAÇÃO
// ══════════════════════════════════════════════

$tk_stmt = $db->prepare("
    SELECT token FROM _users_tokens
    WHERE id_users = ? AND type = 'email_verify' AND is_used = 0
    ORDER BY id_token DESC LIMIT 1
");
$tk_stmt->execute([$id_users]);
$tk_row = $tk_stmt->fetch();

if ($tk_row) {
    sendVerificationEmail($email, $first_name, $tk_row['token']);
}

redirect('/login', ['notice' => 'account_created']);