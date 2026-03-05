<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Reenviar Link de Verificação
// Arquivo: authentic/resend-verification.php
// Chamado via fetch() do verify-email.php (estado expired)
// ══════════════════════════════════════════════
require_once __DIR__ . '/include/functions.php';
startSecureSession();

function jsonOut(bool $ok, string $msg): never {
    header('Content-Type: application/json');
    echo json_encode(['ok' => $ok, 'message' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, 'Método não permitido.');
}

$email = strtolower(trim($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonOut(false, 'E-mail inválido.');
}

$db   = getDB();
$user = getUserByEmail($email);

// Resposta sempre igual (não revelar se o email existe ou não)
$msg_ok = 'Novo link de verificação enviado! Verifica a tua caixa de entrada (e spam).';

if (!$user) {
    // Não revelar que o email não existe
    jsonOut(true, $msg_ok);
}

$id_users = (int)$user['id_users'];

// Já verificado?
if ($user['email_verified'] == 1) {
    jsonOut(false, 'Este e-mail já está verificado. Faz login directamente.');
}

// Rate limit: máx. 3 reenvios por hora por utilizador
$recent = $db->prepare("
    SELECT COUNT(*) FROM _users_tokens
    WHERE id_users = ? AND type = 'email_verify'
      AND creat_token > DATE_SUB(NOW(), INTERVAL 1 HOUR)
");
$recent->execute([$id_users]);
if ((int)$recent->fetchColumn() >= 3) {
    jsonOut(false, 'Demasiados reenvios. Aguarda uma hora antes de pedir outro.');
}

// Invalidar tokens anteriores não usados
$db->prepare("
    UPDATE _users_tokens SET is_used = 1
    WHERE id_users = ? AND type = 'email_verify' AND is_used = 0
")->execute([$id_users]);

// Criar novo token (48 horas)
$new_token = bin2hex(random_bytes(32));
$expires   = date('Y-m-d H:i:s', strtotime('+48 hours'));

// Manter plan_slug se existia no token anterior
$old_extra = $db->prepare("
    SELECT extra_data FROM _users_tokens
    WHERE id_users = ? AND type = 'email_verify'
    ORDER BY id_token DESC LIMIT 1
");
$old_extra->execute([$id_users]);
$prev = $old_extra->fetch();
$extra = $prev ? $prev['extra_data'] : json_encode(['plan_slug' => null]);

$db->prepare("
    INSERT INTO _users_tokens
        (id_users, token, type, extra_data, is_used, expires_at)
    VALUES (?, ?, 'email_verify', ?, 0, ?)
")->execute([$id_users, $new_token, $extra, $expires]);

// Enviar email
$sent = sendVerificationEmail($email, $user['first_name'], $new_token);

if (!$sent) {
    error_log("[RESEND VERIFY] Falha ao enviar para $email");
    // Mesmo com falha de SMTP, retornar OK (segurança — não revelar estado)
}

jsonOut(true, $msg_ok);