<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Recuperação de Senha
// Arquivo: authentic/forgot-password-process.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions.php';
startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/forgot-password');
}

checkHoneypot();

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect('/forgot-password', ['error' => 'csrf']);
}

$email = strtolower(trim($_POST['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/forgot-password', ['error' => 'invalid_email']);
}

// SEMPRE mostrar a mesma mensagem, independente de o email existir ou não
// (evita que um atacante descubra quais emails estão cadastrados)
$user = getUserByEmail($email);

if ($user) {
    // Utilizador existe — criar token e enviar email
    $code = createToken((int)$user['id_users'], 'password_reset', 1); // expira em 1 hora
    sendPasswordResetEmail($email, $user['first_name'], $code);
}

// Redirecionar para confirmar o código (sempre, exista ou não o utilizador)
redirect('/confirm-email-code', [
    'email'  => urlencode($email),
    'mode'   => 'reset',       // Indica que é para reset (não verificação de conta)
    'notice' => 'check_email',
]);