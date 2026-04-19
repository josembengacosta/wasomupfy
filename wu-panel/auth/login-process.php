<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processamento do Login Admin
// Arquivo: admin/auth/login-process.php
// Método: POST único — nunca acessível via GET
//         (bloqueado pelo .htaccess da pasta auth)
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions_admin.php';
startAdminSession();

// ─── Apenas POST ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/' . ADMIN_PATH . '/login');
}

// ─── Rate limit básico por IP ──────────────────
// Camada extra antes de qualquer consulta à BD.
// Impede ataques de força bruta distribuídos
// mesmo sem conta válida.
$ip          = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ip_key      = 'wuf_admin_ip_' . md5($ip);
$ip_attempts = (int)($_SESSION[$ip_key] ?? 0);

if ($ip_attempts >= 20) {
    // Mais de 20 tentativas deste IP nesta sessão → bloquear
    logAudit(
        null,
        null,
        'auth.ip_rate_limit',
        'admin_login',
        null,
        ['ip' => $ip],
        null
    );
    adminRedirect('/' . ADMIN_PATH . '/login', ['msg' => 'blocked']);
}

// ─── CSRF ─────────────────────────────────────
$csrf = $_POST['csrf_token'] ?? '';
if (!validateAdminCsrf($csrf)) {
    logAudit(
        null,
        null,
        'auth.csrf_fail',
        'admin_login',
        null,
        ['ip' => $ip],
        null
    );
    adminRedirect('/' . ADMIN_PATH . '/login', ['msg' => 'error']);
}

// Regenerar CSRF após validação (single-use token)
$_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

// ─── Honeypot anti-bot ────────────────────────
if (!empty($_POST['hp_field'])) {
    // Bot detectado — falha silenciosa (não revelar que existe honeypot)
    sleep(2);
    adminRedirect('/' . ADMIN_PATH . '/login', ['msg' => 'error']);
}

// ─── Recolha e sanitização dos campos ─────────
$email    = strtolower(trim($_POST['email_employees'] ?? ''));
$password = $_POST['password_employees'] ?? '';
$remember = !empty($_POST['remember_me']);

// ─── Validação básica dos campos ──────────────
if (empty($email) || empty($password)) {
    adminRedirect('/' . ADMIN_PATH . '/login', ['msg' => 'error']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    adminRedirect('/' . ADMIN_PATH . '/login', ['msg' => 'error']);
}

if (strlen($password) < 6 || strlen($password) > 128) {
    adminRedirect('/' . ADMIN_PATH . '/login', ['msg' => 'error']);
}

// ─── Buscar employee na BD ────────────────────
$admin = getAdminByEmail($email);

if (!$admin) {
    // E-mail não existe — registar tentativa por IP
    // e responder com atraso para dificultar enumeração
    $_SESSION[$ip_key] = $ip_attempts + 1;
    usleep(500000); // 0.5s de atraso proposital
    adminRedirect('/' . ADMIN_PATH . '/login', ['msg' => 'error']);
}

$id = (int)$admin['id_employees'];

// ─── Verificar bloqueio ────────────────────────
$block = isAdminBlocked($admin);

if ($block['blocked']) {
    $msg = match (true) {
        $block['reason'] === 'fraud'                      => 'blocked',
        $block['reason'] === 'attempts'                   => 'blocked',
        str_starts_with($block['reason'], 'status_')      => 'inactive',
        default                                           => 'blocked',
    };
    adminRedirect('/' . ADMIN_PATH . '/login', ['msg' => $msg]);
}

// ─── Verificar senha ──────────────────────────
if (!password_verify($password, $admin['password_employees'])) {

    // Registar tentativa falhada (bloqueio progressivo)
    recordFailedAdminLogin($id);
    $_SESSION[$ip_key] = $ip_attempts + 1;

    // Re-ler dados de segurança para saber se acabou de ser bloqueado
    $updated = getAdminById($id);
    $attempts_now = (int)($updated['login_attempts'] ?? 0);

    if ($attempts_now >= 3 && $attempts_now < 5) {
        adminRedirect('/' . ADMIN_PATH . '/login', [
            'msg' => 'blocked',
            'wait' => ADMIN_BLOCK_1_MIN
        ]);
    } elseif ($attempts_now >= 5) {
        adminRedirect('/' . ADMIN_PATH . '/login', ['msg' => 'blocked']);
    }

    adminRedirect('/' . ADMIN_PATH . '/login', ['msg' => 'error']);
}

// ══════════════════════════════════════════════
// CREDENCIAIS VÁLIDAS — processar login
// ══════════════════════════════════════════════

// ─── Renovar hash bcrypt se necessário ────────
// Se o hash foi criado com cost < 12, actualiza
// automaticamente na próxima autenticação.
if (password_needs_rehash($admin['password_employees'], PASSWORD_BCRYPT, ['cost' => 12])) {
    $new_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    getDB()->prepare("
        UPDATE _employees
        SET password_employees = ?
        WHERE id_employees = ?
    ")->execute([$new_hash, $id]);
}

// ─── Regenerar sessão (anti-fixation) ─────────
session_regenerate_id(true);

// ─── Popular sessão ───────────────────────────
_populateAdminSession($admin);

// ─── Registar login bem-sucedido ──────────────
recordAdminLoginSuccess($id);

// ─── Limpar contador de IP ────────────────────
unset($_SESSION[$ip_key]);

// ─── Log de auditoria ─────────────────────────
logAudit($id, null, 'auth.login', 'employees', $id, null, [
    'role' => $admin['role'],
    'ip'   => $ip,
]);

// ─── Remember Me ──────────────────────────────
if ($remember) {
    setAdminRememberCookie($id);
}

// ─── Lockscreen activo? ───────────────────────
// Se o admin tinha o lockscreen activo (ex: saiu
// sem fazer logout), redirecionar para o desbloquear
// antes de aceder ao painel.
if (!empty($admin['lockscreen'])) {
    $_SESSION['admin_lockscreen'] = true;
    adminRedirect('/' . ADMIN_PATH . '/lockscreen');
}

// ─── URL de retorno (prioridade: campo POST > sessão) ───
$return = $_POST['return_url'] ?? $_SESSION['admin_return_url'] ?? null;
unset($_SESSION['admin_return_url']);

if ($return) {
    $return = filter_var(urldecode($return), FILTER_SANITIZE_URL);
    // Garantir que a URL é interna (começa com o caminho do admin)
    $base_admin = APP_URL . '/' . ADMIN_PATH;
    if (str_starts_with($return, $base_admin) || str_starts_with($return, '/' . ADMIN_PATH)) {
        // Se a URL for relativa, prefixar com APP_URL
        if (!str_starts_with($return, 'http')) {
            $return = APP_URL . $return;
        }
        header('Location: ' . $return);
        exit;
    }
}

// ─── Redirecionar para o painel (fallback) ───────────────
adminRedirect('/' . ADMIN_PATH . '');