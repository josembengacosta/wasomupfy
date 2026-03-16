<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Nova Senha Admin
// Arquivo: admin/auth/reset-password-process.php
// .htaccess: ^admin/reset-password-process/?$ → este ficheiro
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions_admin.php';
startAdminSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/admin/forgot-password');
}

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
if (!validateAdminCsrf($csrf)) {
    adminRedirect('/admin/forgot-password', ['msg' => 'error']);
}
$_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

// Token via sessão (guardado pelo reset-password.php)
$token    = $_SESSION['admin_reset_token'] ?? null;
$admin_id = (int)($_SESSION['admin_reset_id'] ?? 0);

if (!$token || !$admin_id) {
    adminRedirect('/admin/forgot-password', ['msg' => 'error']);
}

// Revalidar token na BD — dupla verificação sessão + BD
$valid_id = validateAdminResetToken($token);
if (!$valid_id || $valid_id !== $admin_id) {
    unset($_SESSION['admin_reset_token'], $_SESSION['admin_reset_id']);
    adminRedirect('/admin/forgot-password', ['msg' => 'expired']);
}

// Campos
$new_password     = $_POST['new_password']     ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($new_password) || empty($confirm_password)) {
    adminRedirect('/admin/reset-password', ['token' => $token, 'msg' => 'error']);
}

// Senhas coincidem
if (!hash_equals($new_password, $confirm_password)) {
    adminRedirect('/admin/reset-password', ['token' => $token, 'msg' => 'mismatch']);
}

// Força mínima
$weak = (
    strlen($new_password) < 8               ||
    !preg_match('/[A-Z]/', $new_password)   ||
    !preg_match('/[a-z]/', $new_password)   ||
    !preg_match('/[0-9]/', $new_password)   ||
    !preg_match('/[^A-Za-z0-9]/', $new_password)
);

if ($weak || strlen($new_password) > 128) {
    adminRedirect('/admin/reset-password', ['token' => $token, 'msg' => 'weak']);
}

// ── Actualizar senha ──────────────────────────
// updateAdminPassword(): hash bcrypt cost12 + consumeToken + logAudit
updateAdminPassword($admin_id, $new_password);

// Limpar sessão de reset
unset($_SESSION['admin_reset_token'], $_SESSION['admin_reset_id']);

// Invalidar remember me — obriga novo login com nova senha
clearAdminRememberCookie($admin_id);

// Log extra com IP
logAudit($admin_id, null, 'auth.password_changed', 'employees', $admin_id,
    null, ['ip' => $_SERVER['REMOTE_ADDR'] ?? null]);

// Redirecionar para login com mensagem de sucesso
adminRedirect('/admin/login', ['msg' => 'reset_ok']);