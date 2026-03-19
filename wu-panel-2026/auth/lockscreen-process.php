<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Lockscreen Admin
// Arquivo: admin/auth/lockscreen-process.php
// .htaccess: ^admin/lockscreen-process/?$ → este ficheiro
// Método: POST único
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions_admin.php';
startAdminSession();

// ─── Apenas POST ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/' . ADMIN_PATH . '/lockscreen');
}

// ─── Não autenticado → login ───────────────────
if (!isAdminLoggedIn()) {
    adminRedirect('/' . ADMIN_PATH . '/login');
}

// ─── Lockscreen não activo → painel ───────────
if (!isLockscreenActive()) {
    adminRedirect('/' . ADMIN_PATH . '');
}

// ─── Rate limit por sessão ────────────────────
// Máximo 10 tentativas de código por sessão.
// Passado esse limite, faz logout forçado.
$rk       = 'wuf_lock_attempts';
$attempts = (int)($_SESSION[$rk] ?? 0);

if ($attempts >= 10) {
    logAudit(
        (int)$_SESSION['admin_id'],
        null,
        'auth.lockscreen_force_logout',
        'employees',
        (int)$_SESSION['admin_id'],
        ['attempts' => $attempts],
        null
    );
    // Logout forçado por excesso de tentativas
    logoutAdmin();
    adminRedirect('/' . ADMIN_PATH . '/login', ['msg' => 'blocked']);
}

// ─── CSRF ─────────────────────────────────────
$csrf = $_POST['csrf_token'] ?? '';
if (!validateAdminCsrf($csrf)) {
    adminRedirect('/' . ADMIN_PATH . '/lockscreen', ['msg' => 'error']);
}

$_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

// ─── Honeypot ─────────────────────────────────
if (!empty($_POST['hp_field'])) {
    sleep(2);
    adminRedirect('/' . ADMIN_PATH . '/lockscreen', ['msg' => 'error']);
}

// ─── Recolha e validação básica do código ─────
$code = trim($_POST['access_code'] ?? '');

if (!preg_match('/^\d{6}$/', $code)) {
    $_SESSION[$rk] = $attempts + 1;
    adminRedirect('/' . ADMIN_PATH . '/lockscreen', ['msg' => 'wrong']);
}

// ─── Verificar código na BD ───────────────────
$admin_id = (int)$_SESSION['admin_id'];
$valid    = validateAndUnlock($admin_id, $code);

if (!$valid) {
    $_SESSION[$rk] = $attempts + 1;
    $remaining = 10 - ($attempts + 1);

    logAudit(
        $admin_id,
        null,
        'auth.lockscreen_wrong_code',
        'employees',
        $admin_id,
        ['attempts' => $attempts + 1],
        null
    );

    // Se restam poucas tentativas, avisa
    if ($remaining <= 3 && $remaining > 0) {
        adminRedirect('/' . ADMIN_PATH . '/lockscreen', [
            'msg'  => 'wrong',
            'left' => $remaining,
        ]);
    }

    adminRedirect('/' . ADMIN_PATH . '/lockscreen', ['msg' => 'wrong']);
}

// ─── Código correcto ──────────────────────────

// Limpar contador de tentativas
unset($_SESSION[$rk]);

// Log de auditoria
logAudit(
    $admin_id,
    null,
    'auth.lockscreen_unlocked',
    'employees',
    $admin_id,
    null,
    null
);

// Redirecionar para o painel
adminRedirect('/' . ADMIN_PATH . '');
