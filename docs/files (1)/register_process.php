<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Cadastro
// Arquivo: authentic/register_process.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions.php';
startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/authentic/register.php');
}

// Anti-bot
checkHoneypot('hairypot'); // Nome do honeypot no register é 'hairypot'

// Validar CSRF
if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect('/authentic/register.php', ['error' => 'csrf']);
}

// ─── Recolher e sanitizar dados ───────────────
$email        = strtolower(trim($_POST['email_user']    ?? ''));
$full_name    = sanitize($_POST['fullname_user'] ?? '');
$gender       = sanitize($_POST['gender']        ?? '');
$country      = sanitize($_POST['country_user']  ?? '');
$city         = sanitize($_POST['city']          ?? '');
$phone        = sanitize($_POST['tel_user']      ?? '');
$password     = $_POST['password_user']    ?? '';
$confirm_pass = $_POST['confirm_password'] ?? '';
$terms        = isset($_POST['terms_agree']);

$birth_day   = (int)($_POST['birth_day']   ?? 0);
$birth_month = (int)($_POST['birth_month'] ?? 0);
$birth_year  = (int)($_POST['birth_year']  ?? 0);

// ─── Validações ───────────────────────────────

// Email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/authentic/register.php', ['error' => 'invalid_email']);
}

// Email duplicado
if (emailExists($email)) {
    redirect('/authentic/register.php', ['error' => 'email_taken']);
}

// Nome completo
if (strlen($full_name) < 6 || strlen($full_name) > 100) {
    redirect('/authentic/register.php', ['error' => 'invalid_name']);
}

// Data de nascimento
if (
    !$birth_day || !$birth_month || !$birth_year
    || !checkdate($birth_month, $birth_day, $birth_year)
) {
    redirect('/authentic/register.php', ['error' => 'invalid_date']);
}

// Verificar idade mínima (16 anos)
$birth = new DateTime("$birth_year-$birth_month-$birth_day");
$today = new DateTime();
$age   = $today->diff($birth)->y;
if ($age < 16) {
    redirect('/authentic/register.php', ['error' => 'underage']);
}
$birth_date = "$birth_year-" . str_pad($birth_month, 2, '0', STR_PAD_LEFT)
    . "-" . str_pad($birth_day,   2, '0', STR_PAD_LEFT);

// Género
if (!in_array($gender, ['M', 'F', 'O'])) {
    redirect('/authentic/register.php', ['error' => 'invalid_gender']);
}

// País e cidade
if (empty($country) || strlen($country) > 2) {
    redirect('/authentic/register.php', ['error' => 'invalid_country']);
}
if (empty($city)) {
    redirect('/authentic/register.php', ['error' => 'invalid_city']);
}

// Senha
if (strlen($password) < 10) {
    redirect('/authentic/register.php', ['error' => 'weak_password']);
}
if ($password !== $confirm_pass) {
    redirect('/authentic/register.php', ['error' => 'password_mismatch']);
}

// Termos
if (!$terms) {
    redirect('/authentic/register.php', ['error' => 'terms']);
}

// ─── Criar utilizador ─────────────────────────
$name = parseName($full_name);

try {
    $id_users = createUser([
        'email'      => $email,
        'password'   => $password,
        'first_name' => $name['first'],
        'last_name'  => $name['last'],
        'full_name'  => $full_name,
        'gender'     => $gender,
        'birth_date' => $birth_date,
        'country'    => $country,
        'city'       => $city,
        'phone'      => $phone ?: null,
        'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
} catch (Exception $e) {
    redirect('/authentic/register.php', ['error' => 'server']);
}

// ─── Enviar código de verificação ─────────────
$code = createToken($id_users, 'email_verify', 24);
sendVerificationEmail($email, $name['first'], $code);

// Registar actividade
logActivity($id_users, 'register', 'Conta criada com sucesso');

// ─── Redirecionar para verificação ─────────────
redirect('/authentic/confirm-email-code.php', [
    'email'  => urlencode($email),
    'notice' => 'check_email',
]);
