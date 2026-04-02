<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Funções do Painel Admin
// Arquivo: admin/auth/include/functions_admin.php
// Tabela central: _employees + _employees_security
//                 + _employees_permissions
// ══════════════════════════════════════════════

// Reutiliza config.php e connection.php do authentic
// (mesma BD, mesmas constantes — zero duplicação)
require_once dirname(__DIR__, 3) . '/authentic/include/connection.php';

// ─── Constantes exclusivas do admin ───────────
if (!defined('ADMIN_SESSION_NAME'))  define('ADMIN_SESSION_NAME',  'wuf_admin_session');
if (!defined('ADMIN_LOGIN_URL'))     define('ADMIN_LOGIN_URL',     APP_URL . '/' . ADMIN_PATH . '/login');
if (!defined('ADMIN_PANEL_URL'))     define('ADMIN_PANEL_URL',     APP_URL . '/' . ADMIN_PATH . '');
if (!defined('ADMIN_COOKIE_NAME'))   define('ADMIN_COOKIE_NAME',   'wuf_admin_remember');
if (!defined('ADMIN_COOKIE_DAYS'))   define('ADMIN_COOKIE_DAYS',   7);   // admins: 7 dias (menor que utilizadores)
if (!defined('ADMIN_MAX_ATTEMPTS'))  define('ADMIN_MAX_ATTEMPTS',  5);
if (!defined('ADMIN_BLOCK_1_MIN'))   define('ADMIN_BLOCK_1_MIN',   10);  // 3 tentativas → 10 min
if (!defined('ADMIN_BLOCK_2_MIN'))   define('ADMIN_BLOCK_2_MIN',   30);  // 5 tentativas → 30 min
if (!defined('ADMIN_BLOCK_3_MIN'))   define('ADMIN_BLOCK_3_MIN',   60);  // 7+ tentativas → 60 min


// ════════════════════════════════════════════════
// SESSÃO & CSRF
// ════════════════════════════════════════════════

/**
 * Inicia a sessão segura do admin.
 * Sessão separada da sessão de utilizador — não conflituam.
 */
function startAdminSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(ADMIN_SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => APP_URL . '/' . ADMIN_PATH . '/',  // Cookie restrito à pasta admin
            'domain'   => $_SERVER['HTTP_HOST'],
            'secure'   => (APP_ENV === 'production'),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }

    // CSRF token para o admin
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Valida o CSRF token do admin.
 */
function validateAdminCsrf(string $token): bool
{
    return isset($_SESSION['admin_csrf_token'])
        && hash_equals($_SESSION['admin_csrf_token'], $token);
}

/**
 * Verifica se há um admin autenticado na sessão.
 */
function isAdminLoggedIn(): bool
{
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Força autenticação — redireciona para login se não autenticado.
 * Guardar a URL actual para redirecionar de volta após login.
 */
function requireAdminLogin(): void
{
    if (!isAdminLoggedIn()) {
        $return = $_SERVER['REQUEST_URI'] ?? '';
        adminRedirect('/' . ADMIN_PATH . '/login', $return ? ['return' => $return, 'msg' => 'session'] : []);
    }

    // ── Verificação de estado em tempo real na BD ──
    // Garante que admins bloqueados/inactivos são expulsos imediatamente,
    // mesmo que a sessão PHP ainda esteja válida.
    $session_admin_id = (int)($_SESSION['admin_id'] ?? 0);
    if ($session_admin_id > 0) {
        try {
            $db_chk = getDB();
            $row = $db_chk->prepare(
                "SELECT status_employees, role FROM _employees WHERE id_employees = ? LIMIT 1"
            );
            $row->execute([$session_admin_id]);
            $current = $row->fetch();

            if (!$current || !in_array($current['status_employees'], ['active'], true)) {
                // Estado mudou — forçar logout imediato
                session_unset();
                session_destroy();
                $return = $_SERVER['REQUEST_URI'] ?? '';
                adminRedirect('/' . ADMIN_PATH . '/login', [
                    'msg'    => 'inactive',
                    'return' => $return,
                ]);
            }

            // Actualizar role na sessão caso tenha mudado
            if ($current['role'] !== ($_SESSION['admin_role'] ?? '')) {
                $_SESSION['admin_role'] = $current['role'];
            }
        } catch (Exception $e) {
            // BD indisponível — fail-open (não expulsar)
            error_log('[ADMIN STATUS CHECK] BD indisponível: ' . $e->getMessage());
        }
    }
}

/**
 * Verifica se o lockscreen está activo para o admin actual.
 */
function isLockscreenActive(): bool
{
    if (!isAdminLoggedIn()) return false;
    return !empty($_SESSION['admin_lockscreen']);
}

/**
 * Exige que o lockscreen NÃO esteja activo.
 * Redireciona para o lockscreen se estiver.
 */
function requireNoLockscreen(): void
{
    if (isLockscreenActive()) {
        adminRedirect('/' . ADMIN_PATH . '/lockscreen');
    }
}


// ════════════════════════════════════════════════
// LEITURA DE EMPLOYEES
// ════════════════════════════════════════════════

/**
 * Busca um employee pelo e-mail (com dados de segurança).
 */
function getAdminByEmail(string $email): ?array
{
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT e.*,
               s.login_attempts, s.block_until, s.block_level,
               s.is_fraud_blocked, s.remember_token, s.lockscreen,
               s.access_code, s.last_login_at, s.last_login_ip
        FROM _employees e
        LEFT JOIN _employees_security s ON s.id_employees = e.id_employees
        WHERE e.email_employees = ?
        LIMIT 1
    ");
    $stmt->execute([strtolower(trim($email))]);
    return $stmt->fetch() ?: null;
}

/**
 * Busca um employee pelo ID (com dados de segurança).
 */
function getAdminById(int $id): ?array
{
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT e.*,
               s.login_attempts, s.block_until, s.block_level,
               s.is_fraud_blocked, s.lockscreen, s.access_code,
               s.last_login_at, s.last_login_ip
        FROM _employees e
        LEFT JOIN _employees_security s ON s.id_employees = e.id_employees
        WHERE e.id_employees = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}


// ════════════════════════════════════════════════
// BLOQUEIO & TENTATIVAS DE LOGIN
// ════════════════════════════════════════════════

/**
 * Verifica se o admin está bloqueado neste momento.
 * Retorna array ['blocked' => bool, 'until' => string|null, 'reason' => string]
 */
function isAdminBlocked(array $admin): array
{
    // Bloqueio por fraude (manual, permanente até ser levantado)
    if (!empty($admin['is_fraud_blocked'])) {
        return [
            'blocked' => true,
            'until'   => null,
            'reason'  => 'fraud',
        ];
    }

    // Bloqueio temporário por tentativas
    if (!empty($admin['block_until'])) {
        if (strtotime($admin['block_until']) > time()) {
            return [
                'blocked' => true,
                'until'   => $admin['block_until'],
                'reason'  => 'attempts',
            ];
        }
        // Bloqueio expirado — limpar automaticamente
        resetAdminLoginAttempts((int)$admin['id_employees']);
    }

    // Status da conta
    $inactive = ['inactive', 'blocked', 'suspended', 'processing'];
    if (in_array($admin['status_employees'], $inactive)) {
        return [
            'blocked' => true,
            'until'   => null,
            'reason'  => 'status_' . $admin['status_employees'],
        ];
    }

    return ['blocked' => false, 'until' => null, 'reason' => ''];
}

/**
 * Regista uma tentativa de login falhada.
 * Aplica bloqueio progressivo conforme número de tentativas.
 */
function recordFailedAdminLogin(int $id): void
{
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT login_attempts, block_level
        FROM _employees_security
        WHERE id_employees = ?
    ");
    $stmt->execute([$id]);
    $sec = $stmt->fetch();

    $attempts = (int)($sec['login_attempts'] ?? 0) + 1;
    $level    = (int)($sec['block_level'] ?? 0);

    $block_until = null;

    // Bloqueio progressivo
    if ($attempts === 3) {
        $block_until = date('Y-m-d H:i:s', time() + (ADMIN_BLOCK_1_MIN * 60));
        $level       = 1;
    } elseif ($attempts === 5) {
        $block_until = date('Y-m-d H:i:s', time() + (ADMIN_BLOCK_2_MIN * 60));
        $level       = 2;
    } elseif ($attempts >= 7) {
        $block_until = date('Y-m-d H:i:s', time() + (ADMIN_BLOCK_3_MIN * 60));
        $level       = 3;
    }

    $db->prepare("
        UPDATE _employees_security
        SET login_attempts = ?,
            block_level    = ?,
            block_until    = ?
        WHERE id_employees = ?
    ")->execute([$attempts, $level, $block_until, $id]);

    // Log de auditoria (sem id_employees autenticado — é tentativa falhada)
    logAudit(null, null, 'auth.failed_login', 'employees', $id, null, null);
}

/**
 * Limpa tentativas e bloqueio após login bem-sucedido ou expiração.
 */
function resetAdminLoginAttempts(int $id): void
{
    getDB()->prepare("
        UPDATE _employees_security
        SET login_attempts = 0,
            block_until    = NULL,
            block_level    = 0
        WHERE id_employees = ?
    ")->execute([$id]);
}

/**
 * Regista o login bem-sucedido (IP + timestamp).
 */
function recordAdminLoginSuccess(int $id): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    getDB()->prepare("
        UPDATE _employees_security
        SET last_login_at  = NOW(),
            last_login_ip  = ?,
            login_attempts = 0,
            block_until    = NULL,
            block_level    = 0
        WHERE id_employees = ?
    ")->execute([$ip, $id]);
}


// ════════════════════════════════════════════════
// REMEMBER ME — Cookie de sessão persistente
// ════════════════════════════════════════════════

/**
 * Grava cookie "remember me" seguro para o admin.
 * Token é rolling: renovado a cada visita.
 */
function setAdminRememberCookie(int $id): void
{
    $token   = bin2hex(random_bytes(32));
    $expires = time() + (ADMIN_COOKIE_DAYS * 24 * 3600);

    setcookie(ADMIN_COOKIE_NAME, $token, [
        'expires'  => $expires,
        'path'     => APP_URL . '/' . ADMIN_PATH . '/',
        'secure'   => (APP_ENV === 'production'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    getDB()->prepare("
        UPDATE _employees_security
        SET remember_token = ?
        WHERE id_employees = ?
    ")->execute([$token, $id]);
}

/**
 * Apaga o cookie "remember me" do admin.
 */
function clearAdminRememberCookie(int $id): void
{
    setcookie(ADMIN_COOKIE_NAME, '', [
        'expires'  => 1,
        'path'     => APP_URL . '/' . ADMIN_PATH . '/',
        'secure'   => (APP_ENV === 'production'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    getDB()->prepare("
        UPDATE _employees_security
        SET remember_token = NULL
        WHERE id_employees = ?
    ")->execute([$id]);
}

/**
 * Verifica cookie "remember me" do admin e restaura sessão.
 * Chamar no topo de cada página protegida antes de requireAdminLogin().
 */
function checkAdminRememberMe(): void
{
    if (isAdminLoggedIn()) return;

    $token = $_COOKIE[ADMIN_COOKIE_NAME] ?? null;
    if (!$token) return;

    $db   = getDB();
    $stmt = $db->prepare("
        SELECT e.id_employees, e.first_name, e.second_name,
               e.email_employees, e.role, e.status_employees,
               e.photo_employees, s.lockscreen
        FROM _employees_security s
        JOIN _employees e ON e.id_employees = s.id_employees
        WHERE s.remember_token = ?
        AND e.status_employees = 'active'
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $admin = $stmt->fetch();

    if (!$admin) {
        // Token inválido ou conta inactiva — apagar cookie
        setcookie(ADMIN_COOKIE_NAME, '', ['expires' => 1, 'path' => APP_URL . '/' . ADMIN_PATH . '/']);
        return;
    }

    // Restaurar sessão
    session_regenerate_id(true);
    _populateAdminSession($admin);

    // Rolling token — novo token a cada acesso
    setAdminRememberCookie((int)$admin['id_employees']);

    logAudit(
        (int)$admin['id_employees'],
        null,
        'auth.auto_login',
        'employees',
        (int)$admin['id_employees'],
        null,
        null
    );
}

/**
 * Popula $_SESSION com os dados do admin.
 * Uso interno — chamado após login e checkRememberMe.
 */
function _populateAdminSession(array $admin): void
{
    $_SESSION['admin_id']         = (int)$admin['id_employees'];
    $_SESSION['admin_name']       = $admin['first_name'];
    $_SESSION['admin_full_name']  = $admin['first_name'] . ' ' . ($admin['second_name'] ?? '');
    $_SESSION['admin_email']      = $admin['email_employees'];
    $_SESSION['admin_role']       = $admin['role'];
    $_SESSION['admin_photo']      = $admin['photo_employees'] ?? null;
    $_SESSION['admin_lockscreen'] = !empty($admin['lockscreen']);
}


// ════════════════════════════════════════════════
// LOCKSCREEN
// ════════════════════════════════════════════════

/**
 * Activa o lockscreen para o admin actual.
 * A sessão é mantida mas o acesso fica bloqueado até introduzir o access_code.
 */
function activateLockscreen(int $id): void
{
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    getDB()->prepare("
        UPDATE _employees_security
        SET lockscreen  = 1,
            access_code = ?
        WHERE id_employees = ?
    ")->execute([$code, $id]);

    $_SESSION['admin_lockscreen'] = true;
}

/**
 * Valida o access_code e desactiva o lockscreen.
 * Retorna true se o código for correcto.
 */
function validateAndUnlock(int $id, string $code): bool
{
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT access_code FROM _employees_security
        WHERE id_employees = ? AND lockscreen = 1
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row || !hash_equals((string)$row['access_code'], trim($code))) {
        return false;
    }

    $db->prepare("
        UPDATE _employees_security
        SET lockscreen  = 0,
            access_code = NULL
        WHERE id_employees = ?
    ")->execute([$id]);

    $_SESSION['admin_lockscreen'] = false;
    return true;
}


// ════════════════════════════════════════════════
// PERMISSÕES
// ════════════════════════════════════════════════

/**
 * Verifica se um admin tem uma permissão específica.
 *
 * Super admin tem sempre tudo.
 * Para outros roles, verifica _employees_permissions.
 *
 * Uso: hasPermission($id, 'finances.view')
 *      hasPermission($id, 'music.approve')
 */
function hasPermission(int $id, string $permission): bool
{
    $role = $_SESSION['admin_role'] ?? null;

    // ── super_admin — acesso total sempre ──
    if ($role === 'super_admin') return true;

    // ── Verificar se existe regra explícita na BD ──
    // Se existir, respeita-a (seja granted=1 ou granted=0)
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT granted FROM _employees_permissions
        WHERE id_employees = ? AND permission = ?
        LIMIT 1
    ");
    $stmt->execute([$id, $permission]);
    $row = $stmt->fetch();

    if ($row !== false) {
        // Regra explícita encontrada — respeitar
        return (bool)$row['granted'];
    }

    // ── Sem regra na BD — aplicar permissões padrão por role ──
    // Estas são as permissões que cada role tem por omissão.
    // Para personalizar individualmente, inserir em _employees_permissions.
    $defaults = [
        'admin' => [
            'employees.view',
            'employees.edit',
            'users.view',
            'users.edit',
            'music.view',
            'music.approve',
            'finances.view',
            'finances.edit',
            'analytics.view',
            'support.view',
            'support.edit',
            'audit.view',
            'settings.view',
            'settings.edit',
        ],
        'editor' => [
            'music.view',
            'music.approve',
            'analytics.view',
        ],
        'support' => [
            'support.view',
            'analytics.view',
        ],
    ];

    $role_defaults = $defaults[$role] ?? [];
    return in_array($permission, $role_defaults, true);
}

/**
 * Retorna todas as permissões de um admin como array associativo.
 * ['users.edit' => true, 'finances.view' => false, ...]
 */
function getAdminPermissions(int $id): array
{
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT permission, granted
        FROM _employees_permissions
        WHERE id_employees = ?
    ");
    $stmt->execute([$id]);
    $rows = $stmt->fetchAll();

    $perms = [];
    foreach ($rows as $row) {
        $perms[$row['permission']] = (bool)$row['granted'];
    }
    return $perms;
}

/**
 * Exige uma permissão. Redireciona para 403 se não tiver.
 */
function requirePermission(int $id, string $permission): void
{
    if (!hasPermission($id, $permission)) {
        adminRedirect('/' . ADMIN_PATH . '/error/403');
    }
}


// ════════════════════════════════════════════════
// AUDITORIA
// ════════════════════════════════════════════════

/**
 * Regista uma acção no log de auditoria (_audit_log).
 *
 * @param int|null    $id_emp    ID do employee que executou (null se não autenticado)
 * @param int|null    $id_user   ID do utilizador afectado (null se não aplicável)
 * @param string      $action    Ex: 'user.block', 'music.approve', 'plan.update'
 * @param string|null $entity    Nome da tabela afectada (ex: '_users', '_album')
 * @param int|null    $entity_id ID do registo afectado
 * @param mixed       $old       Valor anterior (será serializado para JSON)
 * @param mixed       $new       Novo valor (será serializado para JSON)
 */
function logAudit(
    ?int    $id_emp,
    ?int    $id_user,
    string  $action,
    ?string $entity    = null,
    ?int    $entity_id = null,
    mixed   $old       = null,
    mixed   $new       = null
): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        getDB()->prepare("
            INSERT INTO _audit_log
            (id_employees, id_users, action, entity, entity_id,
             old_value, new_value, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $id_emp,
            $id_user,
            $action,
            $entity,
            $entity_id,
            $old !== null ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
            $new !== null ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
            $ip,
            $ua,
        ]);
    } catch (Throwable $e) {
        error_log('[AUDIT LOG ERROR] ' . $e->getMessage());
    }
}


// ════════════════════════════════════════════════
// RESET DE PASSWORD — Admin
// ════════════════════════════════════════════════

/**
 * Cria token de reset de password para o admin.
 * Armazena em _employees_security.reset_password_token.
 * Expira em 1 hora.
 */
function createAdminResetToken(int $id): string
{
    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600);

    getDB()->prepare("
        UPDATE _employees_security
        SET reset_password_token   = ?,
            reset_password_expires = ?
        WHERE id_employees = ?
    ")->execute([$token, $expires, $id]);

    return $token;
}

/**
 * Valida o token de reset e retorna o ID do employee.
 * Retorna null se inválido ou expirado.
 */
function validateAdminResetToken(string $token): ?int
{
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT id_employees
        FROM _employees_security
        WHERE reset_password_token   = ?
        AND   reset_password_expires > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ? (int)$row['id_employees'] : null;
}

/**
 * Limpa o token de reset após uso.
 */
function consumeAdminResetToken(int $id): void
{
    getDB()->prepare("
        UPDATE _employees_security
        SET reset_password_token   = NULL,
            reset_password_expires = NULL
        WHERE id_employees = ?
    ")->execute([$id]);
}

/**
 * Actualiza a password do admin (hash bcrypt).
 */
function updateAdminPassword(int $id, string $new_password): void
{
    $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
    getDB()->prepare("
        UPDATE _employees
        SET password_employees = ?
        WHERE id_employees = ?
    ")->execute([$hash, $id]);

    consumeAdminResetToken($id);
    logAudit($id, null, 'auth.password_reset', 'employees', $id, null, null);
}

/**
 * Envia e-mail de reset de password ao admin.
 */
function sendAdminResetEmail(string $email, string $name, string $token): bool
{
    $link    = APP_URL . '/' . ADMIN_PATH . '/reset-password?token=' . urlencode($token);
    $subject = 'Redefinir senha — ' . APP_NAME . ' Admin';
    $body    = "
    <div style='font-family:\"Segoe UI\",Arial,sans-serif;max-width:540px;margin:auto;color:#1a1a1a'>
      <div style='background:#111;padding:28px 32px;border-radius:10px 10px 0 0;display:flex;align-items:center;gap:12px'>
        <span style='color:#FF0089;font-size:1.5rem;font-weight:800'>WASOM</span>
        <span style='color:#fff;font-size:0.85rem;opacity:.6'>Painel Admin</span>
      </div>
      <div style='background:#fff;padding:36px 32px;border:1px solid #eee;border-top:none;border-radius:0 0 10px 10px'>
        <h2 style='color:#111;margin-top:0;font-size:1.3rem'>Redefinição de senha</h2>
        <p>Olá <strong>{$name}</strong>,</p>
        <p>Recebemos um pedido de redefinição de senha para a tua conta de administrador.</p>
        <div style='text-align:center;margin:32px 0'>
          <a href='{$link}'
             style='background:#FF0089;color:#fff;text-decoration:none;
                    padding:14px 36px;border-radius:8px;font-size:0.95rem;
                    font-weight:700;display:inline-block;letter-spacing:.3px'>
            Redefinir senha
          </a>
        </div>
        <p style='color:#666;font-size:.88rem'>
          Se o botão não funcionar, copia e cola este link:<br>
          <a href='{$link}' style='color:#FF0089;word-break:break-all;font-size:.82rem'>{$link}</a>
        </p>
        <div style='background:#fff8fb;border-left:3px solid #FF0089;padding:12px 16px;
                    border-radius:0 6px 6px 0;margin:20px 0'>
          <p style='margin:0;font-size:.85rem;color:#555'>
            ⚠️ Este link expira em <strong>1 hora</strong>.<br>
            Se não pediste esta redefinição, ignora este e-mail e
            contacta o super administrador imediatamente.
          </p>
        </div>
        <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0'>
        <small style='color:#bbb'>" . APP_NAME . " Admin &mdash; Acesso restrito. Não reencaminhar.</small>
      </div>
    </div>";

    // Envia directamente via WasomMailer — sem depender de functions.php
    // (evita conflito de redeclaração de funções como sanitize())
    $mailer_path = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';

    if (!file_exists($mailer_path)) {
        error_log('[ADMIN MAILER] WasomMailer.php não encontrado em: ' . $mailer_path);
        return false;
    }

    if (!class_exists('\Wasom\Mailer')) {
        require_once $mailer_path;
    }

    try {
        $wm = new \Wasom\Mailer();
        $wm->host     = MAIL_HOST;
        $wm->port     = MAIL_PORT;
        $wm->secure   = defined('MAIL_SECURE') ? MAIL_SECURE : 'tls';
        $wm->username = MAIL_USER;
        $wm->password = MAIL_PASS;
        $wm->debug    = defined('MAIL_DEBUG') ? MAIL_DEBUG : 0;
        $wm->setFrom(MAIL_FROM, MAIL_FROM_NAME)
            ->addAddress($email, $name)
            ->setSubject($subject)
            ->setBody($body, strip_tags($body));
        $wm->send();
        return true;
    } catch (\Wasom\MailerException $e) {
        error_log('[ADMIN MAILER] Falha ao enviar reset email para ' . $email . ': ' . $e->getMessage());
        return false;
    }
}


// ════════════════════════════════════════════════
// LOGOUT
// ════════════════════════════════════════════════

/**
 * Faz logout completo do admin:
 * destrói sessão, apaga cookie, regista auditoria.
 */
function logoutAdmin(): void
{
    $id = $_SESSION['admin_id'] ?? null;

    if ($id) {
        clearAdminRememberCookie((int)$id);
        logAudit((int)$id, null, 'auth.logout', 'employees', (int)$id, null, null);
    }

    // Destruir sessão admin completamente
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $p['path'],
                'domain'  => $p['domain'],
                'secure' => $p['secure'],
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }
    session_destroy();
}


// ════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════

/**
 * Redireccionamento seguro dentro do admin.
 */
function adminRedirect(string $path, array $params = []): never
{
    $path = ltrim($path, '/');
    $url  = APP_URL . '/' . $path;
    if ($params) $url .= '?' . http_build_query($params);
    header("Location: $url");
    exit;
}

/**
 * Sanitiza string para output HTML.
 * (Idêntica à do functions.php, definida aqui se não estiver carregada)
 */
if (!function_exists('sanitize')) {
    function sanitize(string $value): string
    {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Retorna o nome legível do role do admin.
 */
function getRoleLabel(string $role): string
{
    return match ($role) {
        'super_admin' => 'Super Admin',
        'admin'       => 'Administrador',
        'editor'      => 'Editor',
        'support'     => 'Suporte',
        default       => ucfirst($role),
    };
}

/**
 * Retorna a classe CSS Bootstrap do role (para badges).
 */
function getRoleBadgeClass(string $role): string
{
    return match ($role) {
        'super_admin' => 'danger',
        'admin'       => 'primary',
        'editor'      => 'info',
        'support'     => 'secondary',
        default       => 'dark',
    };
}
