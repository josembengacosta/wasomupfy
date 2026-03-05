<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Verificação de Código
// Arquivo: authentic/verify-code-process.php
// Usado para: verificação de e-mail E reset de senha
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions.php';
startSecureSession();

// ─── Só aceita POST ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/confirm-email-code');
}

checkHoneypot();

// ─── CSRF ─────────────────────────────────────
if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect('/confirm-email-code', ['error' => 'csrf']);
}

// ─── Parâmetros ───────────────────────────────
$code  = trim($_POST['code']  ?? '');
$email = trim($_POST['email'] ?? '');

// mode pode vir do POST (campo hidden) ou GET (query string)
$mode = $_POST['mode'] ?? ($_GET['mode'] ?? 'verify');
$mode = in_array($mode, ['verify', 'reset']) ? $mode : 'verify';

// ─── Validar formato do código ────────────────
if (!preg_match('/^\d{6}$/', $code)) {
    redirect('/confirm-email-code', [
        'email' => urlencode($email),
        'mode'  => $mode,
        'error' => 'invalid_code',
    ]);
}

// ─── Tipo de token conforme o modo ───────────
$token_type = ($mode === 'reset') ? 'password_reset' : 'email_verify';

// ─── Validar código na base de dados ─────────
// validateToken com is_code=true pesquisa via JSON_EXTRACT em extra_data
$token = validateToken($code, $token_type, true);

if (!$token) {
    redirect('/confirm-email-code', [
        'email' => urlencode($email),
        'mode'  => $mode,
        'error' => 'code_expired',
    ]);
}

// ─── Código válido — consumir (marcar como usado) ─
consumeToken((int)$token['id_token']);

// ══════════════════════════════════════════════
// MODO: RESET DE SENHA
// ══════════════════════════════════════════════
if ($mode === 'reset') {
    // Guardar na sessão para usar no reset-password-process
    $_SESSION['reset_user_id']     = (int)$token['id_users'];
    $_SESSION['reset_email']       = $token['email_user'];
    $_SESSION['reset_verified_at'] = time();

    logActivity(
        (int)$token['id_users'],
        'password_reset_code_verified',
        'Código de reset de senha verificado com sucesso'
    );

    redirect('/reset-password', ['notice' => 'code_ok']);
}

// ══════════════════════════════════════════════
// MODO: VERIFICAÇÃO DE E-MAIL
// ══════════════════════════════════════════════
$db = getDB();

// Activar e-mail e actualizar estado da conta
$db->prepare("
    UPDATE _users
    SET email_verified    = 1,
        email_verified_at = NOW(),
        status_user       = 'pending_plan',
        modif_user        = NOW()
    WHERE id_users = ? AND email_verified = 0
")->execute([$token['id_users']]);

logActivity((int)$token['id_users'], 'email_verified', 'E-mail verificado com sucesso');

// Iniciar sessão autenticada
session_regenerate_id(true);
$_SESSION['id_users']   = (int)$token['id_users'];
$_SESSION['first_name'] = $token['first_name'];
$_SESSION['email']      = $token['email_user'];
$_SESSION['status']     = 'pending_plan';

// Criar sessão na tabela _users_sessions
$db->prepare("
    INSERT INTO _users_sessions
        (id_users, session_token, ip_address, user_agent, is_active)
    VALUES (?, ?, ?, ?, 1)
")->execute([
    (int)$token['id_users'],
    session_id(),
    $_SERVER['REMOTE_ADDR'] ?? null,
    $_SERVER['HTTP_USER_AGENT'] ?? null,
]);

// Redirecionar para escolha de plano com mensagem de boas-vindas
redirect('/dashboard/all-plans', ['welcome' => 1]);