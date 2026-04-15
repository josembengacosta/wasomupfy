<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Verificação 2FA
// Arquivo: authentic/2fa-process.php
// ══════════════════════════════════════════════
ob_start();
require_once __DIR__ . '/include/functions.php';
require_once __DIR__ . '/../dashboard/include/platform.php';
startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/login');
}

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect('/2fa-verify', ['error' => 'csrf']);
}

// Verificar sessão pendente
$pending = $_SESSION['pending_2fa'] ?? null;
if (!$pending || time() > $pending['expires']) {
    unset($_SESSION['pending_2fa']);
    redirect('/login', ['error' => 'session']);
}

$uid      = (int)$pending['id_users'];
$remember = (bool)$pending['remember'];
$mode     = in_array($_POST['mode'] ?? '', ['totp', 'recovery']) ? $_POST['mode'] : 'totp';
$db       = getDB();

// ══════════════════════════════════════════════
// FUNÇÕES TOTP
// ══════════════════════════════════════════════
function base32Decode2fa(string $base32): string {
    $base32   = strtoupper($base32);
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output   = '';
    $v = 0; $vbits = 0;
    for ($i = 0; $i < strlen($base32); $i++) {
        $pos = strpos($alphabet, $base32[$i]);
        if ($pos === false) continue;
        $v = ($v << 5) | $pos;
        $vbits += 5;
        if ($vbits >= 8) {
            $vbits -= 8;
            $output .= chr($v >> $vbits);
            $v &= (1 << $vbits) - 1;
        }
    }
    return $output;
}

function verifyTotp2fa(string $secret, string $code, int $window = 1): bool {
    $decoded = base32Decode2fa($secret);
    $time    = floor(time() / 30);
    for ($i = -$window; $i <= $window; $i++) {
        $t    = $time + $i;
        $msg  = pack('N*', 0) . pack('N*', $t);
        $hash = hash_hmac('sha1', $msg, $decoded, true);
        $offset = ord($hash[19]) & 0x0F;
        $otp  = ((ord($hash[$offset])   & 0x7F) << 24)
              | ((ord($hash[$offset+1]) & 0xFF) << 16)
              | ((ord($hash[$offset+2]) & 0xFF) << 8)
              |  (ord($hash[$offset+3]) & 0xFF);
        $otp  = str_pad((string)($otp % 1000000), 6, '0', STR_PAD_LEFT);
        if (hash_equals($otp, $code)) return true;
    }
    return false;
}

// ══════════════════════════════════════════════
// MODO: TOTP
// ══════════════════════════════════════════════
if ($mode === 'totp') {
    $code = trim($_POST['code'] ?? '');

    if (!preg_match('/^\d{6}$/', $code)) {
        redirect('/2fa-verify', ['error' => 'invalid_code']);
    }

    // Buscar secret (não incluído no getUserByEmail por segurança)
    $sec = $db->prepare("SELECT two_factor_secret FROM _users_security WHERE id_users = ?");
    $sec->execute([$uid]);
    $secret = $sec->fetchColumn();

    if (!$secret || !verifyTotp2fa($secret, $code)) {
        logActivity($uid, '2fa_failed', 'Código TOTP incorrecto no login');
        redirect('/2fa-verify', ['error' => 'invalid_code']);
    }
}

// ══════════════════════════════════════════════
// MODO: RECOVERY KEY
// ══════════════════════════════════════════════
elseif ($mode === 'recovery') {
    $key_input = strtoupper(trim($_POST['recovery_key'] ?? ''));

    if (empty($key_input)) {
        redirect('/2fa-verify', ['mode' => 'recovery', 'error' => 'invalid_recovery']);
    }

    $rec = $db->prepare("SELECT recovery_key FROM _users_security WHERE id_users = ?");
    $rec->execute([$uid]);
    $stored_key = $rec->fetchColumn();

    if (!$stored_key || !password_verify($key_input, $stored_key)) {
        logActivity($uid, '2fa_recovery_failed', 'Chave de recuperação inválida no login');
        redirect('/2fa-verify', ['mode' => 'recovery', 'error' => 'invalid_recovery']);
    }

    // Invalidar recovery key após uso + desactivar 2FA (perdeu o telemóvel)
    $new_key = strtoupper(bin2hex(random_bytes(10)));
    $db->prepare("
        UPDATE _users_security
        SET recovery_key = ?, two_factor_enabled = 0, two_factor_secret = NULL
        WHERE id_users = ?
    ")->execute([$new_key, $uid]);

    logActivity($uid, '2fa_recovery_used', '2FA desactivado via chave de recuperação — nova chave gerada');
    // Nota: não enviamos a nova chave aqui — o utilizador deve ir ao perfil gerar uma nova
}

// ══════════════════════════════════════════════
// AUTENTICAÇÃO COMPLETA — CRIAR SESSÃO
// ══════════════════════════════════════════════
unset($_SESSION['pending_2fa']);

$user = getUserById($uid);
if (!$user) {
    redirect('/login', ['error' => 'session']);
}

session_regenerate_id(true);
$_SESSION['id_users']        = $uid;
$_SESSION['first_name']      = $user['first_name'];
$_SESSION['user_name']       = $user['user_name'];
$_SESSION['email']           = $user['email_user'];
$_SESSION['status']          = $user['status_user'];
$_SESSION['email_verified']  = (bool)$user['email_verified'];
$_SESSION['plan_selected']   = $user['plan_selected'];
$_SESSION['onboarding_done'] = (bool)($user['onboarding_done'] ?? false);

$session_token = bin2hex(random_bytes(32));
$db->prepare("
    INSERT INTO _users_sessions
        (id_users, session_token, ip_address, user_agent, is_active, last_activity)
    VALUES (?, ?, ?, ?, 1, NOW())
")->execute([
    $uid, $session_token,
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

$db->prepare("
    UPDATE _users_security SET last_login_at = NOW(), last_login_ip = ? WHERE id_users = ?
")->execute([$_SERVER['REMOTE_ADDR'] ?? null, $uid]);

if ($remember) {
    $remember_token = bin2hex(random_bytes(32));
    $expires = time() + (30 * 24 * 3600);
    setcookie('wuf_remember', $remember_token, [
        'expires'  => $expires, 'path' => '/',
        'secure'   => (APP_ENV === 'production'),
        'httponly' => true, 'samesite' => 'Strict',
    ]);
    $db->prepare("UPDATE _users_security SET remember_token = ? WHERE id_users = ?")
       ->execute([$remember_token, $uid]);
}

resetLoginAttempts($uid);
logActivity($uid, 'login', 'Sessão iniciada com sucesso (2FA verificado)');

// ══════════════════════════════════════════════
// REDIRECIONAMENTO APÓS 2FA BEM-SUCEDIDO
// ══════════════════════════════════════════════

// Recupera a URL salva (se houver)
$redirect_url = $pending['redirect_after'] ?? null;
unset($_SESSION['pending_2fa'], $_SESSION['redirect_after_login']);

// Se existe URL armazenada e é interna (relativa), redireciona para ela
if ($redirect_url && parse_url($redirect_url, PHP_URL_HOST) === null) {
    redirect($redirect_url);
}

// Fallback: comportamento padrão
if ($user['status_user'] === 'processing') {
    $db->prepare("UPDATE _users SET status_user='pending_plan' WHERE id_users=? AND status_user='processing'")
        ->execute([$uid]);
    $_SESSION['status'] = 'pending_plan';
}
if (($user['status_user'] ?? $_SESSION['status'] ?? '') === 'pending_plan') {
    redirect($user['plan_selected'] ? APP_URL_PANEL .'/painel' : APP_URL_PANEL . '/all-plans');
}

redirect(APP_URL_PANEL . '/painel');