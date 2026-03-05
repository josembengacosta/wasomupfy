<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Login
// Arquivo: authentic/login_process.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions.php';
startSecureSession();

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/authentic/login.php');
}

// Anti-bot
checkHoneypot();

// Validar CSRF
if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect('/authentic/login.php', ['error' => 'csrf']);
}

// Recolher dados
$email    = strtolower(trim($_POST['email_user']    ?? ''));
$password = $_POST['password_user'] ?? '';
$remember = isset($_POST['remember_token']);

// Validação básica
if (empty($email) || empty($password)) {
    redirect('/authentic/login.php', ['error' => 'empty']);
}

// Buscar utilizador
$user = getUserByEmail($email);

if (!$user) {
    // Não revelar se o email existe ou não (segurança)
    redirect('/authentic/login.php', ['error' => 'invalid']);
}

// Verificar bloqueio
$block = checkLoginBlock((int)$user['id_users']);
if ($block['blocked']) {
    $msg = urlencode($block['message']);
    redirect('/authentic/login.php', ['error' => 'blocked', 'msg' => $msg]);
}

// Verificar senha
if (!password_verify($password, $user['password_user'])) {
    registerFailedLogin((int)$user['id_users']);

    // Informar quantas tentativas restam
    $new_attempts = (int)($user['login_attempts'] ?? 0) + 1;
    $remaining    = max(0, MAX_LOGIN_ATTEMPTS - $new_attempts);

    redirect('/authentic/login.php', [
        'error'     => 'invalid',
        'remaining' => $remaining,
    ]);
}

// ──── Login bem-sucedido ────

// Verificar se a conta está activa
switch ($user['status_user']) {
    case 'processing':
        redirect('/authentic/confirm-email-code.php', [
            'email'  => urlencode($email),
            'notice' => 'verify_email',
        ]);
    case 'suspended':
        redirect('/authentic/login.php', ['error' => 'suspended']);
    case 'fraud':
        redirect('/authentic/login.php', ['error' => 'fraud']);
}

// Regenerar sessão (previne session fixation)
session_regenerate_id(true);

// Guardar na sessão
$_SESSION['id_users']    = (int)$user['id_users'];
$_SESSION['first_name']  = $user['first_name'];
$_SESSION['email']       = $user['email_user'];
$_SESSION['status']      = $user['status_user'];

// "Lembrar-me" — cookie de 30 dias
if ($remember) {
    $token = bin2hex(random_bytes(32));
    setcookie('remember_token', $token, [
        'expires'  => time() + 30 * 24 * 3600,
        'path'     => '/',
        'secure'   => (APP_ENV === 'production'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    // Guardar token no banco (coluna remember_token na _users_security)
    getDB()->prepare("UPDATE _users_security SET remember_token = ? WHERE id_users = ?")
           ->execute([$token, $user['id_users']]);
}

// Resetar tentativas
resetLoginAttempts((int)$user['id_users']);

// Registar actividade
logActivity((int)$user['id_users'], 'login', 'Utilizador iniciou sessão');

// Redirecionar conforme status
if ($user['status_user'] === 'pending_plan') {
    redirect('/plan/all-plans.php', ['choose' => 1]);
} else {
    redirect('/dashboard/painel.php');
}
