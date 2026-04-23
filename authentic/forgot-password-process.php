<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Recuperação de Senha
// Arquivo: authentic/forgot-password-process.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions.php';
startSecureSession();

// Activar logs para diagnóstico
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/forgot-password');
}

checkHoneypot();

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect('/forgot-password', ['error' => 'csrf']);
}

$email = strtolower(trim($_POST['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/forgot-password', ['error' => 'invalid_email', 'email' => $email]);
}

$user = getUserByEmail($email);

// Se o utilizador não existir, informa imediatamente
if (!$user) {
    redirect('/forgot-password', ['error' => 'email_not_found', 'email' => $email]);
}

// ═══════════════════════════════════════════════
// Utilizador encontrado – enviar código
// ═══════════════════════════════════════════════
try {
    // 1. Gerar código de 6 dígitos
    $code = createToken((int)$user['id_users'], 'password_reset', 1);

    // 2. Enviar e‑mail com o MESMO método que o suporte (função sendEmail)
    $subject = "Redefinir senha — " . APP_NAME;
    $body = "
    <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto'>
      <h2 style='color:#FF0089'>Redefinir senha</h2>
      <p>Olá <strong>" . htmlspecialchars($user['first_name'] ?? 'Utilizador') . "</strong>,</p>
      <p>Recebemos um pedido de redefinição de senha. O teu código é:</p>
      <div style='font-size:36px;font-weight:bold;letter-spacing:8px;text-align:center;
                  background:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0'>
        {$code}
      </div>
      <p>Este código expira em <strong>1 hora</strong>.</p>
      <p>Se não pediste a redefinição, ignora este e‑mail. A tua senha permanece inalterada.</p>
      <hr>
      <small style='color:#999'>" . APP_NAME . " — Não respondas a este e‑mail.</small>
    </div>";

    $emailEnviado = sendEmail($email, $subject, $body);

    if (!$emailEnviado) {
        // Falha no envio — regista e informa o utilizador
        error_log("[PASSWORD RESET] ERRO CRÍTICO: sendEmail retornou false para $email");
        redirect('/forgot-password', ['error' => 'send_failed', 'email' => $email]);
    }

    // Sucesso!
    redirect('/confirm-email-code', [
        'email'  => urlencode($email),
        'mode'   => 'reset',
        'notice' => 'code_sent'
    ]);

} catch (Exception $e) {
    error_log("[PASSWORD RESET] Excepção: " . $e->getMessage());
    redirect('/forgot-password', ['error' => 'generic', 'email' => $email]);
}