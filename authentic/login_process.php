<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Login
// Arquivo: authentic/login_process.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions.php';
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

// Recolher dados
$email    = strtolower(trim($_POST['email_user']    ?? ''));
$password = $_POST['password_user'] ?? '';
$remember = isset($_POST['remember_token']);

// Validação básica
if (empty($email) || empty($password)) {
    redirect('/login', ['error' => 'empty']);
}

// Buscar utilizador
$user = getUserByEmail($email);

if (!$user) {
    // Não revelar se email existe ou não
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
if (in_array($user['status_user'], ['suspended', 'fraud', 'inactive'])) {
    redirect('/login', ['error' => $user['status_user']]);
}

// ─── Verificar senha ──────────────────────────
if (!password_verify($password, $user['password_user'])) {
    registerFailedLogin($uid);

    // Calcular tentativas restantes para mostrar ao utilizador
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

// ─── Atualizar último login em _users_security ─
getDB()->prepare("
    UPDATE _users_security
    SET last_login_at = NOW(), last_login_ip = ?
    WHERE id_users = ?
")->execute([$_SERVER['REMOTE_ADDR'] ?? null, $uid]);

// ─── "Lembrar-me" — cookie 30 dias ───────────
// 30 dias: bom equilíbrio entre conveniência e segurança.
// Menor (7d) seria muito inconveniente; maior (90d) seria risco se o
// dispositivo for partilhado. O utilizador pode sempre fazer logout.
if ($remember) {
    $remember_token = bin2hex(random_bytes(32));
    $expires        = time() + (30 * 24 * 3600); // 30 dias

    setcookie('wuf_remember', $remember_token, [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => (APP_ENV === 'production'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    // Guardar token e expiração na BD
    getDB()->prepare("
        UPDATE _users_security
        SET remember_token = ?
        WHERE id_users = ?
    ")->execute([$remember_token, $uid]);

    // Também guardar expiração na sessão para validação futura
    $_SESSION['remember_expires'] = $expires;
}

// ─── Resetar tentativas de login ──────────────
resetLoginAttempts($uid);

// ─── Registar actividade ──────────────────────
logActivity($uid, 'login', 'Sessao iniciada com sucesso');

// ══════════════════════════════════════════════
// REDIRECIONAR CONFORME ESTADO DA CONTA
// ══════════════════════════════════════════════

// Conta em processing: email ainda não verificado mas login permitido
// O aviso aparece no painel
if ($user['status_user'] === 'processing') {
    // Atualizar status para pending_plan para poder aceder ao painel
    // (o email pode ser verificado mais tarde no painel)
    getDB()->prepare("
        UPDATE _users SET status_user = 'pending_plan' WHERE id_users = ? AND status_user = 'processing'
    ")->execute([$uid]);
    $_SESSION['status'] = 'pending_plan';
}

// Se tem plano pré-selecionado mas ainda não pagou → painel com onboarding
// Se não tem plano → página de escolha de planos
if ($user['status_user'] === 'pending_plan' || $_SESSION['status'] === 'pending_plan') {
    if ($user['plan_selected']) {
        // Tem plano escolhido → vai ao dashboard e mostramos o onboarding
        redirect('/dashboard/painel');
    } else {
        // Sem plano → escolher plano
        redirect('/dashboard/all-plans');
    }
}

// Conta activa → dashboard normal
redirect('/dashboard/painel');