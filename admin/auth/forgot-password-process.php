<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Recuperação de Senha Admin
// Arquivo: admin/auth/forgot-password-process.php
// .htaccess: ^admin/forgot-password-process/?$ → este ficheiro
// Método: POST único — bloqueado via GET pelo .htaccess
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions_admin.php';
// NOTA: NÃO incluir functions.php aqui — causaria
// redeclaração de sanitize() e outras funções.
// O sendAdminResetEmail() usa WasomMailer directamente.

startAdminSession();

// ─── Apenas POST ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/admin/forgot-password');
}

// ─── Rate limit por IP ────────────────────────
// Máximo 5 pedidos de reset por sessão por IP.
$ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rk      = 'wuf_admin_reset_' . md5($ip);
$r_count = (int)($_SESSION[$rk] ?? 0);

if ($r_count >= 5) {
    logAudit(null, null, 'auth.reset_rate_limit', 'admin_forgot', null,
        ['ip' => $ip], null);
    adminRedirect('/admin/forgot-password', ['msg' => 'limit']);
}

// ─── CSRF ─────────────────────────────────────
$csrf = $_POST['csrf_token'] ?? '';
if (!validateAdminCsrf($csrf)) {
    adminRedirect('/admin/forgot-password', ['msg' => 'error']);
}

// Regenerar CSRF após uso
$_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

// ─── Honeypot ─────────────────────────────────
if (!empty($_POST['hp_field'])) {
    sleep(2);
    adminRedirect('/admin/forgot-password', ['msg' => 'sent']);
}

// ─── Recolha dos campos ───────────────────────
$method      = $_POST['method'] ?? 'email';
$method      = in_array($method, ['email', 'user']) ? $method : 'email';
$email_input = strtolower(trim($_POST['email_employees'] ?? ''));
$user_input  = strtolower(trim(ltrim($_POST['user_employees'] ?? '', '@')));

// ─── Validação ────────────────────────────────
if ($method === 'email') {
    if (empty($email_input) || !filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        adminRedirect('/admin/forgot-password', ['msg' => 'error']);
    }
} else {
    if (empty($user_input) || strlen($user_input) < 3) {
        adminRedirect('/admin/forgot-password', ['msg' => 'error']);
    }
}

// ─── Incrementar contador de rate limit ───────
$_SESSION[$rk] = $r_count + 1;

// ─── Buscar admin na BD ───────────────────────
$admin = null;

if ($method === 'email') {
    $admin = getAdminByEmail($email_input);
} else {
    $stmt = getDB()->prepare("
        SELECT e.*,
               s.login_attempts, s.block_until, s.block_level,
               s.is_fraud_blocked, s.lockscreen
        FROM _employees e
        LEFT JOIN _employees_security s ON s.id_employees = e.id_employees
        WHERE e.user_employees = ?
        LIMIT 1
    ");
    $stmt->execute([$user_input]);
    $admin = $stmt->fetch() ?: null;
}

// ─── Resposta sempre genérica ─────────────────
// Mesmo sem conta, a resposta é idêntica —
// impede enumeração de contas/usernames.
if (!$admin) {
    usleep(random_int(300000, 700000));
    logAudit(null, null, 'auth.reset_not_found', 'admin_forgot', null,
        ['method' => $method, 'ip' => $ip], null);
    adminRedirect('/admin/forgot-password', ['msg' => 'sent']);
}

$id   = (int)$admin['id_employees'];
$name = $admin['first_name'];
$dest = $admin['email_employees'];

// ─── Conta inactiva → resposta genérica ───────
$inactive = ['inactive', 'blocked', 'suspended'];
if (in_array($admin['status_employees'], $inactive)) {
    usleep(random_int(300000, 700000));
    logAudit(null, null, 'auth.reset_inactive_account', 'employees', $id,
        ['status' => $admin['status_employees']], null);
    adminRedirect('/admin/forgot-password', ['msg' => 'sent']);
}

// ─── Anti-spam: token recente (< 10 min) ──────
$stmt = getDB()->prepare("
    SELECT reset_password_expires
    FROM _employees_security
    WHERE id_employees = ?
    AND reset_password_token   IS NOT NULL
    AND reset_password_expires > DATE_ADD(NOW(), INTERVAL 50 MINUTE)
    LIMIT 1
");
$stmt->execute([$id]);
if ($stmt->fetch()) {
    usleep(random_int(200000, 500000));
    adminRedirect('/admin/forgot-password', ['msg' => 'sent']);
}

// ─── Criar token e enviar e-mail ──────────────
$token = createAdminResetToken($id);
$sent  = sendAdminResetEmail($dest, $name, $token);

// ─── Log de auditoria ─────────────────────────
logAudit($id, null, 'auth.reset_requested', 'employees', $id, null, [
    'method' => $method,
    'sent'   => $sent,
    'ip'     => $ip,
]);

if (!$sent) {
    error_log("[ADMIN RESET] Falha ao enviar e-mail de reset para ID {$id} ({$dest})");
}

// ─── Resposta sempre igual ────────────────────
adminRedirect('/admin/forgot-password', ['msg' => 'sent']);