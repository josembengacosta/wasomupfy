<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Login
// Arquivo: authentic/login_process.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions.php';
require_once __DIR__ . '/../dashboard/include/platform.php';
startSecureSession();

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/login');
}

// Anti-bot
checkHoneypot('honeypot');

// CSRF
if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect('/login', ['error' => 'csrf']);
}

// ══════════════════════════════════════════════
// CONFIRMAR REACTIVAÇÃO (vem do modal)
// ══════════════════════════════════════════════
if (isset($_POST['action']) && $_POST['action'] === 'confirm_reactivate') {
    $pending = $_SESSION['pending_reactivation'] ?? null;

    if (!$pending || time() > $pending['expires']) {
        unset($_SESSION['pending_reactivation']);
        redirect('/login', ['error' => 'session']);
    }

    $uid      = (int)$pending['id_users'];
    $remember = (bool)$pending['remember'];

    getDB()->prepare("
        UPDATE _users SET status_user = 'active', deactivation_user = NULL, modif_user = NOW()
        WHERE id_users = ?
    ")->execute([$uid]);

    unset($_SESSION['pending_reactivation']);

    $user = getUserById($uid);
    session_regenerate_id(true);
    $_SESSION['id_users']        = $uid;
    $_SESSION['first_name']      = $user['first_name'];
    $_SESSION['user_name']       = $user['user_name'];
    $_SESSION['email']           = $user['email_user'];
    $_SESSION['status']          = 'active';
    $_SESSION['email_verified']  = (bool)$user['email_verified'];
    $_SESSION['plan_selected']   = $user['plan_selected'];
    $_SESSION['onboarding_done'] = (bool)($user['onboarding_done'] ?? false);

    $session_token = bin2hex(random_bytes(32));
    getDB()->prepare("
        INSERT INTO _users_sessions (id_users, session_token, ip_address, user_agent, is_active, last_activity)
        VALUES (?, ?, ?, ?, 1, NOW())
    ")->execute([$uid, $session_token, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
    $_SESSION['session_token'] = $session_token;

    updateUserPresence(
        $uid,
        '/' . trim((string)APP_URL_PANEL, '/') . '/painel',
        'login',
        'online',
        $session_token
    );

    getDB()->prepare("
        UPDATE _users_security SET last_login_at = NOW(), last_login_ip = ? WHERE id_users = ?
    ")->execute([$_SERVER['REMOTE_ADDR'] ?? null, $uid]);

    if ($remember) {
        $remember_token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 3600);
        setcookie('wuf_remember', $remember_token, [
            'expires' => $expires, 'path' => '/', 'secure' => (APP_ENV === 'production'),
            'httponly' => true, 'samesite' => 'Strict',
        ]);
        getDB()->prepare("UPDATE _users_security SET remember_token = ? WHERE id_users = ?")
               ->execute([$remember_token, $uid]);
    }

    resetLoginAttempts($uid);
    logActivity($uid, 'account_reactivated', 'Conta reactivada via confirmação no modal de login');
    logActivity($uid, 'login', 'Sessão iniciada após reactivação');

    redirect('/dashboard/painel');
}

// ══════════════════════════════════════════════
// FLUXO NORMAL DE LOGIN
// ══════════════════════════════════════════════

$email    = strtolower(trim($_POST['email_user']    ?? ''));
$password = $_POST['password_user'] ?? '';
$remember = isset($_POST['remember_token']);

if (empty($email) || empty($password)) {
    redirect('/login', ['error' => 'empty']);
}

$user = getUserByEmail($email);
if (!$user) {
    redirect('/login', ['error' => 'invalid']);
}

$uid = (int)$user['id_users'];

// ─── Verificar bloqueio ───────────────────────
$block = checkLoginBlock($uid);
if ($block['blocked']) {
    redirect('/login', [
        'error' => 'blocked',
        'msg'   => urlencode($block['message']),
    ]);
}

// ─── Verificar conta suspensa/fraude ─────────
if (in_array($user['status_user'], ['suspended', 'fraud'])) {
    redirect('/login', ['error' => $user['status_user']]);
}

// ─── Conta desactivada — verificar janela de 29 dias ─────────────────────
if ($user['status_user'] === 'inactive') {
    $deact_until = $user['deactivation_user'] ?? null;

    if ($deact_until && strtotime($deact_until) > time()) {
        // Verificar senha antes de mostrar modal de confirmação
        if (!password_verify($password, $user['password_user'])) {
            registerFailedLogin($uid);
            $attempts_now = (int)($user['login_attempts'] ?? 0) + 1;
            $remaining    = max(0, MAX_LOGIN_ATTEMPTS - $attempts_now);
            redirect('/login', ['error' => 'invalid', 'remaining' => $remaining]);
        }
        // Senha correcta — guardar estado pendente e mostrar modal
        $_SESSION['pending_reactivation'] = [
            'id_users'    => $uid,
            'email'       => $user['email_user'],
            'first_name'  => $user['first_name'],
            'deact_until' => $deact_until,
            'remember'    => $remember,
            'expires'     => time() + 300,
        ];
        redirect('/login', ['confirm_reactivate' => '1']);
    } else {
        redirect('/login', ['error' => 'inactive_expired']);
    }
}

// ─── Verificar senha ──────────────────────────
if (!password_verify($password, $user['password_user'])) {
    registerFailedLogin($uid);
    $attempts_now = (int)($user['login_attempts'] ?? 0) + 1;
    $remaining    = max(0, MAX_LOGIN_ATTEMPTS - $attempts_now);
    redirect('/login', [
        'error'     => 'invalid',
        'remaining' => $remaining,
    ]);
}

// ══════════════════════════════════════════════
// LOGIN BEM-SUCEDIDO
// ══════════════════════════════════════════════

// ─── Verificar 2FA antes de criar sessão ─────
if (!empty($user['two_factor_enabled'])) {
    $_SESSION['pending_2fa'] = [
        'id_users' => $uid,
        'remember' => $remember,
        'expires'  => time() + 300,
    ];
    redirect('/2fa-verify');
}

// Regenerar sessão (previne session fixation)
session_regenerate_id(true);

// Guardar dados na sessão
$_SESSION['id_users']        = $uid;
$_SESSION['first_name']      = $user['first_name'];
$_SESSION['user_name']       = $user['user_name'];
$_SESSION['email']           = $user['email_user'];
$_SESSION['status']          = $user['status_user'];
$_SESSION['email_verified']  = (bool)$user['email_verified'];
$_SESSION['plan_selected']   = $user['plan_selected'];
$_SESSION['onboarding_done'] = (bool)($user['onboarding_done'] ?? false);

// ─── Registar sessão em _users_sessions ──────
$session_token = bin2hex(random_bytes(32));
getDB()->prepare("
    INSERT INTO _users_sessions
    (id_users, session_token, ip_address, user_agent, is_active, last_activity)
    VALUES (?, ?, ?, ?, 1, NOW())
")->execute([
    $uid,
    $session_token,
    $_SERVER['REMOTE_ADDR']     ?? null,
    $_SERVER['HTTP_USER_AGENT'] ?? null,
]);
$_SESSION['session_token'] = $session_token;

updateUserPresence(
    $uid,
    '/' . trim((string)APP_URL_PANEL, '/') . '/painel',
    'login',
    'online',
    $session_token
);

// ─── Atualizar último login em _users_security ─
getDB()->prepare("
    UPDATE _users_security
    SET last_login_at = NOW(), last_login_ip = ?
    WHERE id_users = ?
")->execute([$_SERVER['REMOTE_ADDR'] ?? null, $uid]);

// ─── "Lembrar-me" — cookie 30 dias ───────────
if ($remember) {
    $remember_token = bin2hex(random_bytes(32));
    $expires        = time() + (30 * 24 * 3600);

    setcookie('wuf_remember', $remember_token, [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => (APP_ENV === 'production'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    getDB()->prepare("
        UPDATE _users_security SET remember_token = ? WHERE id_users = ?
    ")->execute([$remember_token, $uid]);

    $_SESSION['remember_expires'] = $expires;
}

// ─── Resetar tentativas de login ──────────────
resetLoginAttempts($uid);

// ─── Registar actividade ──────────────────────
logActivity($uid, 'login', 'Sessao iniciada com sucesso');

// ══════════════════════════════════════════════
// REDIRECIONAR CONFORME ESTADO DA CONTA
// ══════════════════════════════════════════════

if ($user['status_user'] === 'processing') {
    getDB()->prepare("
        UPDATE _users SET status_user = 'pending_plan' WHERE id_users = ? AND status_user = 'processing'
    ")->execute([$uid]);
    $_SESSION['status'] = 'pending_plan';
}

if ($user['status_user'] === 'pending_plan' || $_SESSION['status'] === 'pending_plan') {
    redirect($user['plan_selected'] ? '/dashboard/painel' : '/dashboard/all-plans');
}

redirect('/dashboard/painel');
