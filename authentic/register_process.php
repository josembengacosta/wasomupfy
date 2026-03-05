<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Cadastro
// Arquivo: authentic/register_process.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions.php';
startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/register');
}

checkHoneypot('hairypot');

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect('/register', ['error' => 'csrf']);
}

// ══════════════════════════════════════════════
// 1. RECOLHER E SANITIZAR
// ══════════════════════════════════════════════

$email        = strtolower(trim($_POST['email_user']  ?? ''));
$full_name    = sanitize(trim($_POST['fullname_user'] ?? ''));
$gender_raw   = sanitize($_POST['gender']             ?? '');
$country      = sanitize($_POST['country_user']       ?? '');
$city         = sanitize($_POST['city']               ?? '');
$phone        = sanitize($_POST['tel_user']           ?? '');
$password     = $_POST['password_user']               ?? '';
$confirm_pass = $_POST['confirm_password']            ?? '';
$terms        = isset($_POST['terms_agree']);

$birth_day    = (int)($_POST['birth_day']   ?? 0);
$birth_month  = (int)($_POST['birth_month'] ?? 0);
$birth_year   = (int)($_POST['birth_year']  ?? 0);

// Plano pré-selecionado via URL — guardado na sessão pelo register.php
$plan_slug = $_SESSION['register_plan'] ?? null;

// ══════════════════════════════════════════════
// 2. VALIDAÇÕES
// ══════════════════════════════════════════════

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/register', ['error' => 'invalid_email']);
}

if (emailExists($email)) {
    redirect('/register', ['error' => 'email_taken']);
}

// Nome: mínimo 2 palavras (para ter first_name + second_name)
$name_parts = array_filter(explode(' ', $full_name));
if (strlen($full_name) < 6 || strlen($full_name) > 100 || count($name_parts) < 2) {
    redirect('/register', ['error' => 'invalid_name']);
}

if (!$birth_day || !$birth_month || !$birth_year || !checkdate($birth_month, $birth_day, $birth_year)) {
    redirect('/register', ['error' => 'invalid_date']);
}

$birth_obj = new DateTime("$birth_year-$birth_month-$birth_day");
$age       = (new DateTime())->diff($birth_obj)->y;
if ($age < 16) {
    redirect('/register', ['error' => 'underage']);
}
$birth_date = sprintf('%04d-%02d-%02d', $birth_year, $birth_month, $birth_day);

// Mapear género para o enum da BD ('M','F','Outro')
$gender_map = ['M' => 'M', 'F' => 'F', 'O' => 'Outro'];
if (!isset($gender_map[$gender_raw])) {
    redirect('/register', ['error' => 'invalid_gender']);
}
$gender = $gender_map[$gender_raw];

if (empty($country) || strlen($country) !== 2) {
    redirect('/register', ['error' => 'invalid_country']);
}

if (empty($city) || strlen($city) < 2) {
    redirect('/register', ['error' => 'invalid_city']);
}

if (strlen($password) < 10) {
    redirect('/register', ['error' => 'weak_password']);
}

if ($password !== $confirm_pass) {
    redirect('/register', ['error' => 'password_mismatch']);
}

if (!$terms) {
    redirect('/register', ['error' => 'terms']);
}

// ══════════════════════════════════════════════
// 3. PREPARAR DADOS
// ══════════════════════════════════════════════

$name_arr    = array_values($name_parts);
$first_name  = $name_arr[0];
$second_name = implode(' ', array_slice($name_arr, 1));

$username = generateUniqueUsername($first_name, $second_name);

// Buscar id do plano pré-seleccionado
$plan_id = $plan_slug ? getPlanIdBySlug($plan_slug) : null;

// ══════════════════════════════════════════════
// 4. CRIAR UTILIZADOR (transacção completa)
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
        'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
        'plan_id'     => $plan_id,
    ]);
} catch (Exception $e) {
    error_log('[REGISTER ERROR] ' . $e->getMessage());
    redirect('/register', ['error' => 'server']);
}

unset($_SESSION['register_plan']);

// ══════════════════════════════════════════════
// 5. ENVIAR EMAIL DE VERIFICAÇÃO
// ══════════════════════════════════════════════

// Buscar o token de verificação que foi criado pelo createUser()
$db       = getDB();
$tk_stmt  = $db->prepare("
    SELECT token FROM _users_tokens
    WHERE id_users = ? AND type = 'email_verify' AND is_used = 0
    ORDER BY id_token DESC LIMIT 1
");
$tk_stmt->execute([$id_users]);
$tk_row = $tk_stmt->fetch();

if ($tk_row) {
    // Enviar email com link de verificação (token hex de 64 chars)
    sendVerificationEmail($email, $first_name, $tk_row['token']);
}

// ══════════════════════════════════════════════
// 6. REDIRECIONAR
// ══════════════════════════════════════════════
// Redireciona para login com aviso de que deve verificar o email.
// Não iniciamos sessão aqui — só é possível após verificar o email.
redirect('/login', ['notice' => 'account_created']);