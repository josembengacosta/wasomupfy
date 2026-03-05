<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Nova Senha
// Arquivo: authentic/reset-password-process.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions.php';
startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/authentic/reset-password.php');
}

checkHoneypot();

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect('/authentic/reset-password.php', ['error' => 'csrf']);
}

// Verificar se a sessão de reset foi iniciada pelo verify-code-process.php
if (empty($_SESSION['reset_user_id']) || empty($_SESSION['reset_verified_at'])) {
    redirect('/authentic/forgot-password.php', ['error' => 'session_expired']);
}

// A verificação do código não pode ter mais de 15 minutos
if (time() - $_SESSION['reset_verified_at'] > 15 * 60) {
    unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_verified_at']);
    redirect('/authentic/forgot-password.php', ['error' => 'session_expired']);
}

$id_users = (int)$_SESSION['reset_user_id'];
$password         = $_POST['password']         ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validar senha
if (strlen($password) < 8) {
    redirect('/authentic/reset-password.php', ['error' => 'weak_password']);
}
if ($password !== $confirm_password) {
    redirect('/authentic/reset-password.php', ['error' => 'password_mismatch']);
}

// Actualizar a senha
$db = getDB();
$db->prepare("
    UPDATE _users SET password_user = ? WHERE id_users = ?
")->execute([password_hash($password, PASSWORD_DEFAULT), $id_users]);

// Resetar tentativas de login (boa prática após reset)
resetLoginAttempts($id_users);

// Registar actividade
logActivity($id_users, 'password_reset', 'Senha redefinida com sucesso');

// Limpar dados da sessão de reset
unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_verified_at']);

// Redirecionar para o login com mensagem de sucesso
redirect('/authentic/login.php', ['notice' => 'password_updated']);
