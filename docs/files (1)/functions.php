<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Funções Globais
// Arquivo: authentic/include/functions.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/connection.php';

// ════════════════════════════════════════════════
// SESSÃO & CSRF
// ════════════════════════════════════════════════

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => (APP_ENV === 'production'),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }

    // Gerar CSRF token se ainda não existir
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function validateCsrf(string $token): bool {
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function isLoggedIn(): bool {
    return isset($_SESSION['id_users']) && !empty($_SESSION['id_users']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('/authentic/login.php', ['session' => 'expired']);
    }
}

// ════════════════════════════════════════════════
// UTILIZADORES
// ════════════════════════════════════════════════

function getUserByEmail(string $email): ?array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.*, s.login_attempts, s.block_until, s.block_level, s.is_fraud_blocked
        FROM _users u
        LEFT JOIN _users_security s ON s.id_users = u.id_users
        WHERE u.email_user = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

function getUserById(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.*, s.login_attempts, s.block_until, s.block_level, s.is_fraud_blocked
        FROM _users u
        LEFT JOIN _users_security s ON s.id_users = u.id_users
        WHERE u.id_users = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function emailExists(string $email): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT id_users FROM _users WHERE email_user = ? LIMIT 1");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

// ════════════════════════════════════════════════
// BLOQUEIO DE LOGIN
// ════════════════════════════════════════════════

function checkLoginBlock(int $id): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT login_attempts, block_until, block_level, is_fraud_blocked
        FROM _users_security WHERE id_users = ?
    ");
    $stmt->execute([$id]);
    $s = $stmt->fetch();

    if (!$s) return ['blocked' => false];

    // Bloqueio permanente por fraude
    if ($s['is_fraud_blocked']) {
        return [
            'blocked' => true,
            'reason'  => 'fraud',
            'message' => 'Conta bloqueada por atividade suspeita. Contacte o suporte.',
        ];
    }

    // Bloqueio temporário ainda activo
    if ($s['block_until'] && strtotime($s['block_until']) > time()) {
        $remaining = ceil((strtotime($s['block_until']) - time()) / 60);
        return [
            'blocked'  => true,
            'reason'   => 'temp',
            'message'  => "Muitas tentativas falhadas. Tente novamente em {$remaining} minuto(s).",
            'remaining'=> $remaining,
        ];
    }

    return ['blocked' => false, 'attempts' => (int)$s['login_attempts']];
}

function registerFailedLogin(int $id): void {
    $db = getDB();
    $stmt = $db->prepare("SELECT login_attempts FROM _users_security WHERE id_users = ?");
    $stmt->execute([$id]);
    $s = $stmt->fetch();
    $attempts = (int)($s['login_attempts'] ?? 0) + 1;

    $level = 0;
    $until = null;

    if ($attempts >= 7) {
        $level = 3;
        $until = date('Y-m-d H:i:s', time() + BLOCK_LEVEL_3_MIN * 60);
    } elseif ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $level = 2;
        $until = date('Y-m-d H:i:s', time() + BLOCK_LEVEL_2_MIN * 60);
    } elseif ($attempts >= 3) {
        $level = 1;
        $until = date('Y-m-d H:i:s', time() + BLOCK_LEVEL_1_MIN * 60);
    }

    $db->prepare("
        UPDATE _users_security
        SET login_attempts = ?, block_level = ?, block_until = ?, last_failed_at = NOW()
        WHERE id_users = ?
    ")->execute([$attempts, $level, $until, $id]);
}

function resetLoginAttempts(int $id): void {
    $db->prepare = getDB()->prepare("
        UPDATE _users_security
        SET login_attempts = 0, block_level = 0, block_until = NULL, last_login_at = NOW()
        WHERE id_users = ?
    ");
    getDB()->prepare("
        UPDATE _users_security
        SET login_attempts = 0, block_level = 0, block_until = NULL, last_login_at = NOW()
        WHERE id_users = ?
    ")->execute([$id]);
}

// ════════════════════════════════════════════════
// REGISTO
// ════════════════════════════════════════════════

function createUser(array $data): int {
    $db = getDB();
    $db->beginTransaction();

    try {
        // Inserir utilizador principal
        $stmt = $db->prepare("
            INSERT INTO _users
            (email_user, password_user, first_name, last_name, full_name, gender_user,
             birth_date, country_user, city_user, tel_user, ip_user,
             status_user, email_verified, creat_user)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'processing', 0, NOW())
        ");
        $stmt->execute([
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['first_name'],
            $data['last_name'],
            $data['full_name'],
            $data['gender'],
            $data['birth_date'],
            $data['country'],
            $data['city'],
            $data['phone'] ?? null,
            $data['ip'],
        ]);
        $id = (int)$db->lastInsertId();

        // Criar registo de segurança
        $db->prepare("
            INSERT INTO _users_security (id_users, login_attempts, block_level)
            VALUES (?, 0, 0)
        ")->execute([$id]);

        // Criar carteira
        $db->prepare("
            INSERT INTO _wallet (id_users, balance_aoa, balance_usd)
            VALUES (?, 0, 0)
        ")->execute([$id]);

        $db->commit();
        return $id;

    } catch (Exception $e) {
        $db->rollBack();
        error_log("[CREATE USER ERROR] " . $e->getMessage());
        throw $e;
    }
}

// ════════════════════════════════════════════════
// TOKENS (verificação de email, reset de senha)
// ════════════════════════════════════════════════

function createToken(int $id_users, string $type, int $expires_hours = 24): string {
    $db  = getDB();
    $token = bin2hex(random_bytes(32));
    $code  = (string)rand(100000, 999999); // Código de 6 dígitos para email
    $expires = date('Y-m-d H:i:s', time() + $expires_hours * 3600);

    // Invalidar tokens anteriores do mesmo tipo
    $db->prepare("
        UPDATE _users_tokens SET is_used = 1
        WHERE id_users = ? AND type = ? AND is_used = 0
    ")->execute([$id_users, $type]);

    // Criar novo token
    $db->prepare("
        INSERT INTO _users_tokens (id_users, type, token, code, expires_at)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$id_users, $type, $token, $code, $expires]);

    return $code; // Retorna o código de 6 dígitos para enviar por email
}

function validateToken(string $token_or_code, string $type, bool $is_code = false): ?array {
    $db = getDB();
    $field = $is_code ? 'code' : 'token';

    $stmt = $db->prepare("
        SELECT t.*, u.email_user, u.first_name
        FROM _users_tokens t
        JOIN _users u ON u.id_users = t.id_users
        WHERE t.{$field} = ? AND t.type = ?
        AND t.is_used = 0 AND t.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token_or_code, $type]);
    return $stmt->fetch() ?: null;
}

function consumeToken(int $id_token): void {
    getDB()->prepare("UPDATE _users_tokens SET is_used = 1 WHERE id_token = ?")
           ->execute([$id_token]);
}

// ════════════════════════════════════════════════
// EMAIL
// ════════════════════════════════════════════════

/**
 * Função de email simples (substituir por PHPMailer quando tiver SMTP)
 * Por agora usa mail() nativo do PHP
 */
function sendEmail(string $to, string $subject, string $body): bool {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";

    // Em desenvolvimento, registar no log em vez de enviar
    if (APP_ENV === 'development') {
        error_log("[EMAIL TO: $to] SUBJECT: $subject | BODY: " . strip_tags($body));
        return true; // Simular sucesso em desenvolvimento
    }

    return mail($to, $subject, $body, $headers);
}

function sendVerificationEmail(string $email, string $name, string $code): bool {
    $subject = "Código de verificação - " . APP_NAME;
    $body = "
    <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto'>
      <h2 style='color:#FF0089'>Verificar a tua conta</h2>
      <p>Olá <strong>{$name}</strong>,</p>
      <p>O teu código de verificação é:</p>
      <div style='font-size:36px;font-weight:bold;letter-spacing:8px;text-align:center;
                  background:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0'>
        {$code}
      </div>
      <p>Este código expira em <strong>24 horas</strong>.</p>
      <p>Se não criaste esta conta, ignora este e-mail.</p>
      <hr>
      <small style='color:#999'>" . APP_NAME . " &mdash; Não respondas a este e-mail.</small>
    </div>";

    return sendEmail($email, $subject, $body);
}

function sendPasswordResetEmail(string $email, string $name, string $code): bool {
    $subject = "Redefinir senha - " . APP_NAME;
    $body = "
    <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto'>
      <h2 style='color:#FF0089'>Redefinir senha</h2>
      <p>Olá <strong>{$name}</strong>,</p>
      <p>Recebemos um pedido de redefinição de senha. O teu código é:</p>
      <div style='font-size:36px;font-weight:bold;letter-spacing:8px;text-align:center;
                  background:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0'>
        {$code}
      </div>
      <p>Este código expira em <strong>1 hora</strong>.</p>
      <p>Se não pediste a redefinição, ignora este e-mail. A tua senha permanece inalterada.</p>
      <hr>
      <small style='color:#999'>" . APP_NAME . " &mdash; Não respondas a este e-mail.</small>
    </div>";

    return sendEmail($email, $subject, $body);
}

// ════════════════════════════════════════════════
// ACTIVITY LOG & AUDIT
// ════════════════════════════════════════════════

function logActivity(int $id_users, string $type, string $desc, string $entity = null, int $entity_id = null): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    try {
        getDB()->prepare("
            INSERT INTO _user_activity_log
            (id_users, activity_type, description, entity, entity_id, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$id_users, $type, $desc, $entity, $entity_id, $ip]);
    } catch (Exception $e) {
        error_log("[LOG ERROR] " . $e->getMessage());
    }
}

// ════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════

function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function redirect(string $path, array $params = []): void {
    $url = APP_URL . $path;
    if ($params) $url .= '?' . http_build_query($params);
    header("Location: $url");
    exit;
}

function redirectBack(string $fallback = '/authentic/login.php'): void {
    $ref = $_SERVER['HTTP_REFERER'] ?? APP_URL . $fallback;
    header("Location: $ref");
    exit;
}

function checkHoneypot(string $field_name = 'honeypot'): void {
    if (!empty($_POST[$field_name])) {
        // Bot detectado — não revelar o motivo, apenas redirecionar
        header("Location: " . APP_URL);
        exit;
    }
}

function parseName(string $full_name): array {
    $parts = explode(' ', trim($full_name), 2);
    return [
        'first' => $parts[0] ?? '',
        'last'  => $parts[1] ?? '',
    ];
}
