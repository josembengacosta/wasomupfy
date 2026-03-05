<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Nova Senha
// Arquivo: authentic/reset-password-process.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions.php';
startSecureSession();

// ─── Só aceita POST ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/reset-password');
}

checkHoneypot();

// ─── CSRF ─────────────────────────────────────
if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect('/reset-password', ['error' => 'csrf']);
}

// ─── Verificar sessão de reset ────────────────
if (empty($_SESSION['reset_user_id']) || empty($_SESSION['reset_verified_at'])) {
    redirect('/forgot-password', ['error' => 'session_expired']);
}

// A verificação do código não pode ter mais de 15 minutos
if (time() - $_SESSION['reset_verified_at'] > 15 * 60) {
    unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_verified_at']);
    redirect('/forgot-password', ['error' => 'session_expired']);
}

$id_users         = (int)$_SESSION['reset_user_id'];
$password         = $_POST['password']         ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// ─── Validar senha ────────────────────────────
if (strlen($password) < 8) {
    redirect('/reset-password', ['error' => 'weak_password']);
}

if ($password !== $confirm_password) {
    redirect('/reset-password', ['error' => 'password_mismatch']);
}

// ─── Actualizar a senha ───────────────────────
$db = getDB();
$db->prepare("
    UPDATE _users
    SET password_user = ?, modif_user = NOW()
    WHERE id_users = ?
")->execute([password_hash($password, PASSWORD_DEFAULT), $id_users]);

// ─── Invalidar todas as sessões activas ───────
// (segurança: forçar re-login em todos os dispositivos após reset)
$db->prepare("
    UPDATE _users_sessions SET is_active = 0 WHERE id_users = ?
")->execute([$id_users]);

// ─── Resetar tentativas de login bloqueadas ───
resetLoginAttempts($id_users);

// ─── Registar actividade ──────────────────────
logActivity($id_users, 'password_reset', 'Senha redefinida com sucesso via recuperação');

// ─── Limpar dados da sessão de reset ─────────
unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_verified_at']);

// ─── Redirecionar para login com sucesso ─────
// NOTA: login.php espera 'notice=password_reset' (não password_updated)
redirect('/login', ['notice' => 'password_reset']);