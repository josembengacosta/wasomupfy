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

    if (!$pending) {
        redirect('/login', ['error' => 'session_expired']);
    }
    if (time() > $pending['expires']) {
        unset($_SESSION['pending_reactivation']);
        redirect('/login', ['error' => 'reactivation_expired']);
    }

    $uid      = (int)$pending['id_users'];
    $remember = (bool)$pending['remember'];

    // Verificar bloqueio novamente (segurança)
    $block = checkLoginBlock($uid);
    if ($block['blocked']) {
        unset($_SESSION['pending_reactivation']);
        redirect('/login', ['error' => 'blocked', 'msg' => urlencode($block['message'])]);
    }

    $db = getDB();
    $db->prepare("UPDATE _users SET status_user = 'active', deactivation_user = NULL, modif_user = NOW() WHERE id_users = ?")
        ->execute([$uid]);

    unset($_SESSION['pending_reactivation']);

    $user = getUserById($uid);
    completeLogin($user, $remember);

    logActivity($uid, 'account_reactivated', 'Conta reactivada via modal de login');
    redirect('/' . APP_URL_PANEL . '/painel');
}

// ══════════════════════════════════════════════
// ─── Global login allowed? ─────────────────────
if (!isLoginAllowed()) {
    redirect('/login', ['error' => 'disabled']);
}

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
    redirect('/login', ['error' => 'blocked', 'msg' => urlencode($block['message'])]);
}

// ─── Verificar conta suspensa/fraude ─────────
if (in_array($user['status_user'], ['suspended', 'fraud'])) {
    redirect('/login', ['error' => $user['status_user']]);
}

// ─── Conta desactivada — verificar janela de 29 dias ─────────────────────
if ($user['status_user'] === 'inactive') {
    $deact_until = $user['deactivation_user'] ?? null;

    if ($deact_until && strtotime($deact_until) > time()) {
        // Verificar bloqueio antes de mostrar modal (redundante, mas seguro)
        $block = checkLoginBlock($uid);
        if ($block['blocked']) {
            redirect('/login', ['error' => 'blocked', 'msg' => urlencode($block['message'])]);
        }

        // Verificar senha antes de mostrar modal de confirmação
        if (!password_verify($password, $user['password_user'])) {
            registerFailedLogin($uid);
            $attempts_now = (int)($user['login_attempts'] ?? 0) + 1;
            $remaining    = max(0, MAX_LOGIN_ATTEMPTS - $attempts_now);
            $_SESSION['login_error'] = 'invalid';
            $_SESSION['remaining_attempts'] = $remaining;
            redirect('/login');
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
    $_SESSION['login_error'] = 'invalid';
    $_SESSION['remaining_attempts'] = $remaining;
    redirect('/login');
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

// Finalizar login (cria sessão, cookie, logs)
completeLogin($user, $remember);

// Redirecionar conforme estado da conta
if ($user['status_user'] === 'processing') {
    getDB()->prepare("UPDATE _users SET status_user = 'pending_plan' WHERE id_users = ? AND status_user = 'processing'")
        ->execute([$uid]);
    $_SESSION['status'] = 'pending_plan';
}

if ($user['status_user'] === 'pending_plan' || ($_SESSION['status'] ?? '') === 'pending_plan') {
    redirect('/' . APP_URL_PANEL . ($user['plan_selected'] ? '/painel' : '/all-plans'));
}

redirect('/' . APP_URL_PANEL . '/painel');