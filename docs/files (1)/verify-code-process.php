<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Verificação de Código
// Arquivo: authentic/verify-code-process.php
// Usado para: verificação de email E reset de senha
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions.php';
startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/authentic/confirm-email-code.php');
}

checkHoneypot();

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect('/authentic/confirm-email-code.php', ['error' => 'csrf']);
}

$code  = trim($_POST['code']  ?? '');
$email = trim($_POST['email'] ?? '');
$mode  = $_GET['mode'] ?? 'verify'; // 'verify' = verificação de email | 'reset' = reset de senha

// Validação do código — deve ter exactamente 6 dígitos
if (!preg_match('/^\d{6}$/', $code)) {
    redirect('/authentic/confirm-email-code.php', [
        'email' => urlencode($email),
        'mode'  => $mode,
        'error' => 'invalid_code',
    ]);
}

// Determinar o tipo de token
$token_type = ($mode === 'reset') ? 'password_reset' : 'email_verify';

// Validar o código na base de dados
$token = validateToken($code, $token_type, true); // true = é código, não token

if (!$token) {
    redirect('/authentic/confirm-email-code.php', [
        'email' => urlencode($email),
        'mode'  => $mode,
        'error' => 'code_expired', // Pode estar expirado ou inválido
    ]);
}

// ─── Código válido ────────────────────────────
consumeToken((int)$token['id_token']);

if ($mode === 'reset') {
    // Reset de senha — guardar o token temporariamente na sessão
    // para usar no reset-password-process.php
    $_SESSION['reset_user_id']    = (int)$token['id_users'];
    $_SESSION['reset_email']      = $token['email_user'];
    $_SESSION['reset_verified_at']= time();

    redirect('/authentic/reset-password.php', ['notice' => 'code_ok']);

} else {
    // Verificação de email — activar a conta
    $db = getDB();
    $db->prepare("
        UPDATE _users
        SET email_verified = 1, status_user = 'pending_plan'
        WHERE id_users = ?
    ")->execute([$token['id_users']]);

    logActivity((int)$token['id_users'], 'email_verified', 'E-mail verificado com sucesso');

    // Iniciar sessão e redirecionar para escolha de plano
    session_regenerate_id(true);
    $_SESSION['id_users']   = (int)$token['id_users'];
    $_SESSION['first_name'] = $token['first_name'];
    $_SESSION['email']      = $token['email_user'];
    $_SESSION['status']     = 'pending_plan';

    redirect('/plan/all-plans.php', ['welcome' => 1]);
}
