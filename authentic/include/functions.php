<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Funções Globais
// Arquivo: authentic/include/functions.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/connection.php';

// ════════════════════════════════════════════════
// SESSÃO & CSRF
// ════════════════════════════════════════════════

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'domain'   => $_SERVER['HTTP_HOST'],
            'secure'   => (APP_ENV === 'production'),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function validateCsrf(string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function isLoggedIn(): bool
{
    return isset($_SESSION['id_users']) && !empty($_SESSION['id_users']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('/login', ['session' => 'expired']);
    }
}

// ════════════════════════════════════════════════
// UTILIZADORES
// ════════════════════════════════════════════════

function getUserByEmail(string $email): ?array
{
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.*, s.login_attempts, s.block_until, s.block_level,
               s.is_fraud_blocked, s.recovery_key, s.two_factor_enabled
        FROM _users u
        LEFT JOIN _users_security s ON s.id_users = u.id_users
        WHERE u.email_user = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

function getUserById(int $id): ?array
{
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.*, s.login_attempts, s.block_until, s.block_level,
               s.is_fraud_blocked, s.two_factor_enabled
        FROM _users u
        LEFT JOIN _users_security s ON s.id_users = u.id_users
        WHERE u.id_users = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function emailExists(string $email): bool
{
    $db = getDB();
    $stmt = $db->prepare("SELECT id_users FROM _users WHERE email_user = ? LIMIT 1");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

function usernameExists(string $username): bool
{
    $db = getDB();
    $stmt = $db->prepare("SELECT id_users FROM _users WHERE user_name = ? LIMIT 1");
    $stmt->execute([$username]);
    return $stmt->rowCount() > 0;
}

// ════════════════════════════════════════════════
// USERNAME
// ════════════════════════════════════════════════

/**
 * Gera username único: primeironome + segundonome + 3 dígitos
 */
function generateUniqueUsername(string $first, string $second): string
{
    // Normalizar: remover acentos, lowercase, só letras
    $base = strtolower(removeAccents($first) . removeAccents($second));
    $base = preg_replace('/[^a-z0-9]/', '', $base);
    $base = substr($base, 0, 15); // Máximo 15 chars base

    // Tentar até encontrar um único
    $attempts = 0;
    do {
        $suffix   = str_pad((string)rand(0, 999), 3, '0', STR_PAD_LEFT);
        $username = $base . $suffix;
        $attempts++;
        if ($attempts > 20) {
            // Fallback com timestamp se demorar muito
            $username = $base . substr(time(), -5);
            break;
        }
    } while (usernameExists($username));

    return $username;
}

function removeAccents(string $str): string
{
    $from = [
        'á',
        'à',
        'ã',
        'â',
        'ä',
        'é',
        'è',
        'ê',
        'ë',
        'í',
        'ì',
        'î',
        'ï',
        'ó',
        'ò',
        'õ',
        'ô',
        'ö',
        'ú',
        'ù',
        'û',
        'ü',
        'ç',
        'ñ',
        'Á',
        'À',
        'Ã',
        'Â',
        'Ä',
        'É',
        'È',
        'Ê',
        'Ë',
        'Í',
        'Ì',
        'Î',
        'Ï',
        'Ó',
        'Ò',
        'Õ',
        'Ô',
        'Ö',
        'Ú',
        'Ù',
        'Û',
        'Ü',
        'Ç',
        'Ñ'
    ];
    $to   = [
        'a',
        'a',
        'a',
        'a',
        'a',
        'e',
        'e',
        'e',
        'e',
        'i',
        'i',
        'i',
        'i',
        'o',
        'o',
        'o',
        'o',
        'o',
        'u',
        'u',
        'u',
        'u',
        'c',
        'n',
        'a',
        'a',
        'a',
        'a',
        'a',
        'e',
        'e',
        'e',
        'e',
        'i',
        'i',
        'i',
        'i',
        'o',
        'o',
        'o',
        'o',
        'o',
        'u',
        'u',
        'u',
        'u',
        'c',
        'n'
    ];
    return str_replace($from, $to, $str);
}

// ════════════════════════════════════════════════
// PLANOS
// ════════════════════════════════════════════════

function getPlanIdBySlug(string $slug): ?int
{
    $db = getDB();
    $stmt = $db->prepare("SELECT id_plan FROM _plans WHERE slug_plan = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([strtolower($slug)]);
    $row = $stmt->fetch();
    return $row ? (int)$row['id_plan'] : null;
}

function getPlanBySlug(string $slug): ?array
{
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM _plans WHERE slug_plan = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([strtolower($slug)]);
    return $stmt->fetch() ?: null;
}

function getAllPlans(): array
{
    $db = getDB();
    $stmt = $db->query("SELECT * FROM _plans WHERE is_active = 1 ORDER BY display_order ASC");
    return $stmt->fetchAll();
}

// ════════════════════════════════════════════════
// BLOQUEIO DE LOGIN
// ════════════════════════════════════════════════

function checkLoginBlock(int $id): array
{
    $db = getDB();
    $stmt = $db->prepare("
        SELECT login_attempts, block_until, block_level, is_fraud_blocked
        FROM _users_security WHERE id_users = ?
    ");
    $stmt->execute([$id]);
    $s = $stmt->fetch();
    if (!$s) return ['blocked' => false];

    if ($s['is_fraud_blocked']) {
        return [
            'blocked' => true,
            'reason'  => 'fraud',
            'message' => 'Conta bloqueada por atividade suspeita. Contacte o suporte.',
        ];
    }

    if ($s['block_until'] && strtotime($s['block_until']) > time()) {
        $remaining = ceil((strtotime($s['block_until']) - time()) / 60);
        return [
            'blocked'   => true,
            'reason'    => 'temp',
            'message'   => "Muitas tentativas falhadas. Tente novamente em {$remaining} minuto(s).",
            'remaining' => $remaining,
        ];
    }

    return ['blocked' => false, 'attempts' => (int)$s['login_attempts']];
}

function registerFailedLogin(int $id): void
{
    $db = getDB();
    $stmt = $db->prepare("SELECT login_attempts FROM _users_security WHERE id_users = ?");
    $stmt->execute([$id]);
    $s = $stmt->fetch();
    $attempts = (int)($s['login_attempts'] ?? 0) + 1;

    $level = 0;
    $until = null;
    if ($attempts >= 10) {
        // Bloqueio permanente por fraude
        $db->prepare("UPDATE _users_security SET is_fraud_blocked = 1 WHERE id_users = ?")
            ->execute([$id]);
        return;
    } elseif ($attempts >= 7) {
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

function resetLoginAttempts(int $id): void
{
    getDB()->prepare("
        UPDATE _users_security
        SET login_attempts = 0, block_level = 0, block_until = NULL, last_login_at = NOW()
        WHERE id_users = ?
    ")->execute([$id]);
}

// ════════════════════════════════════════════════
// REGISTO — createUser
// ════════════════════════════════════════════════

/**
 * Cria utilizador e todos os registos relacionados numa única transacção.
 *
 * Insere em:
 *   _users, _users_security, _users_tokens (email_verify),
 *   _wallet, _user_activity_log
 *
 * @param array $data {
 *   first_name, second_name, user_name, email, password,
 *   gender, birth_date, country, city, phone, ip, plan_id
 * }
 * @return int id_users criado
 */
function createUser(array $data): int
{
    $db = getDB();
    $db->beginTransaction();

    try {
        // ── 1. Inserir utilizador principal ──────────
        $stmt = $db->prepare("
            INSERT INTO _users
            (ip_register, first_name, second_name, user_name,
             gender, birth_date, tel_user, country_user, city_user,
             email_user, password_user, plan_selected,
             status_user, email_verified, creat_user)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_plan', 0, NOW())
        ");
        $stmt->execute([
            $data['ip']          ?? null,
            $data['first_name'],
            $data['second_name'] ?? null,
            $data['user_name'],
            $data['gender'],
            $data['birth_date'],
            $data['phone']       ?? null,
            $data['country'],
            $data['city'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['plan_id']     ?? null,
        ]);
        $id = (int)$db->lastInsertId();

        // ── 2. _users_security (recovery_key obrigatório) ──
        $recovery_key = strtoupper(bin2hex(random_bytes(10))); // 20 chars hex
        $db->prepare("
            INSERT INTO _users_security
            (id_users, recovery_key, login_attempts, block_level)
            VALUES (?, ?, 0, 0)
        ")->execute([$id, $recovery_key]);

        // ── 3. _users_tokens — código de verificação de email ──
        // Sem expiração real: 10 anos. Só é apagado quando verificado.
        $verify_code    = (string)rand(100000, 999999);
        $verify_expires = date('Y-m-d H:i:s', strtotime('+48 hours'));
        $db->prepare("
            INSERT INTO _users_tokens (id_users, token, type, extra_data, is_used, expires_at)
            VALUES (?, ?, 'email_verify', ?, 0, ?)
        ")->execute([
            $id,
            bin2hex(random_bytes(32)),
            json_encode(['code' => $verify_code, 'plan_slug' => $data['plan_slug'] ?? null]),
            $verify_expires,
        ]);

        // ── 4. _wallet ────────────────────────────────
        $db->prepare("
            INSERT INTO _wallet (id_users, balance_aoa, balance_usd)
            VALUES (?, 0.00, 0.00)
        ")->execute([$id]);

        // ── 5. _user_activity_log ─────────────────────
        $db->prepare("
            INSERT INTO _user_activity_log
            (id_users, activity_type, description, ip_address)
            VALUES (?, 'register', 'Conta criada com sucesso', ?)
        ")->execute([$id, $data['ip'] ?? null]);

        $db->commit();
        return $id;
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[CREATE USER ERROR] ' . $e->getMessage());
        throw $e;
    }
}

// ════════════════════════════════════════════════
// SESSÃO DE LOGIN
// ════════════════════════════════════════════════

/**
 * Regista uma sessão activa em _users_sessions
 */
function createUserSession(int $id_users): void
{
    $token = bin2hex(random_bytes(32));
    getDB()->prepare("
        INSERT INTO _users_sessions
        (id_users, session_token, ip_address, user_agent, is_active, last_activity)
        VALUES (?, ?, ?, ?, 1, NOW())
    ")->execute([
        $id_users,
        $token,
        $_SERVER['REMOTE_ADDR']  ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
    $_SESSION['session_token'] = $token;
}

/**
 * Desactiva todas as sessões activas do utilizador (logout)
 */
function destroyUserSession(int $id_users): void
{
    $token = $_SESSION['session_token'] ?? null;
    if ($token) {
        getDB()->prepare("
            UPDATE _users_sessions SET is_active = 0 WHERE id_users = ? AND session_token = ?
        ")->execute([$id_users, $token]);
    }
}

function verifyUserPassword(int $id_users, string $password): bool
{
    $db = getDB();
    $stmt = $db->prepare("SELECT password_user FROM _users WHERE id_users = ?");
    $stmt->execute([$id_users]);
    $user = $stmt->fetch();

    if (!$user) return false;

    return password_verify($password, $user['password_user']);
}
// ════════════════════════════════════════════════
// TOKENS (verificação de email, reset de senha)
// ════════════════════════════════════════════════

function createToken(int $id_users, string $type, int $expires_hours = 24): string
{
    $db  = getDB();
    $token = bin2hex(random_bytes(32));
    $code  = (string)rand(100000, 999999); // Código de 6 dígitos para email
    $expires = date('Y-m-d H:i:s', time() + $expires_hours * 3600);

    // Invalidar tokens anteriores do mesmo tipo
    $db->prepare("
        UPDATE _users_tokens SET is_used = 1
        WHERE id_users = ? AND type = ? AND is_used = 0
    ")->execute([$id_users, $type]);

    // Criar novo token - CORRIGIDO: usando extra_data em vez de code
    $db->prepare("
        INSERT INTO _users_tokens (id_users, type, token, extra_data, expires_at)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([
        $id_users,
        $type,
        $token,
        json_encode(['code' => $code]), // Armazena o código como JSON em extra_data
        $expires
    ]);

    return $code; // Retorna o código de 6 dígitos para enviar por email
}

function validateToken(string $token_or_code, string $type, bool $is_code = false): ?array
{
    $db = getDB();

    if ($is_code) {
        // O código de 6 dígitos está guardado em extra_data como JSON {"code":"123456"}
        // Usamos JSON_EXTRACT para pesquisar directamente no MySQL
        $stmt = $db->prepare("
            SELECT t.*, u.email_user, u.first_name
            FROM _users_tokens t
            JOIN _users u ON u.id_users = t.id_users
            WHERE JSON_UNQUOTE(JSON_EXTRACT(t.extra_data, '$.code')) = ?
              AND t.type = ?
              AND t.is_used = 0
              AND t.expires_at > NOW()
            LIMIT 1
        ");
    } else {
        $stmt = $db->prepare("
            SELECT t.*, u.email_user, u.first_name
            FROM _users_tokens t
            JOIN _users u ON u.id_users = t.id_users
            WHERE t.token = ? AND t.type = ?
              AND t.is_used = 0
              AND t.expires_at > NOW()
            LIMIT 1
        ");
    }

    $stmt->execute([$token_or_code, $type]);
    return $stmt->fetch() ?: null;
}

function consumeToken(int $id_token): void
{
    getDB()->prepare("UPDATE _users_tokens SET is_used = 1 WHERE id_token = ?")
        ->execute([$id_token]);
}

// ════════════════════════════════════════════════
// EMAIL
// ════════════════════════════════════════════════

/**
 * Enviar email via PHPMailer (com fallback para mail() nativo)
 *
 * PHPMailer pode ser instalado de duas formas:
 *  1. Composer: composer require phpmailer/phpmailer
 *     → autoload em vendor/autoload.php (já incluído abaixo)
 *  2. Manual: descarregar phpmailer/phpmailer do GitHub
 *     → copiar src/ para authentic/vendor/phpmailer/src/
 *
 * Em desenvolvimento (APP_ENV='development') o email não é enviado —
 * é gravado em PHP error_log e num ficheiro de debug local.
 */
function sendEmail(string $to, string $subject, string $body, string $altBody = ''): bool
{

    // ── Modo desenvolvimento: gravar ficheiro de debug ──────────────
    if (APP_ENV === 'development') {
        $log_dir  = __DIR__ . '/../../assets/logs/emails';
        if (!is_dir($log_dir)) mkdir($log_dir, 0750, true);
        $filename = $log_dir . date('Y-m-d_H-i-s') . '_' . md5($to) . '.html';
        $debug_content = "<!DOCTYPE html><html><head><meta charset='UTF-8'>"
            . "<title>DEBUG EMAIL</title></head><body>"
            . "<table style='font-family:monospace;font-size:13px;border-collapse:collapse'>"
            . "<tr><td style='padding:4px 12px 4px 0;color:#888'>Para:</td><td><strong>$to</strong></td></tr>"
            . "<tr><td style='padding:4px 12px 4px 0;color:#888'>Assunto:</td><td>$subject</td></tr>"
            . "<tr><td style='padding:4px 12px 4px 0;color:#888'>Data:</td><td>" . date('d/m/Y H:i:s') . "</td></tr>"
            . "</table><hr>$body</body></html>";
        file_put_contents($filename, $debug_content);
        error_log("[WASOM EMAIL DEV] Para: $to | Assunto: $subject | Ficheiro: $filename");
        return true;
    }

    // ── Produção: tentar PHPMailer, fallback para mail() nativo ─────
    $vendor_composer = __DIR__ . '/../../vendor/autoload.php';
    $vendor_manual   = __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
    $has_phpmailer   = false;

    if (file_exists($vendor_composer)) {
        require_once $vendor_composer;
        $has_phpmailer = class_exists('PHPMailer\PHPMailer\PHPMailer');
    } elseif (file_exists($vendor_manual)) {
        require_once $vendor_manual;
        require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
        $has_phpmailer = true;
    }

    if ($has_phpmailer && MAIL_USER !== '') {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = MAIL_PASS;
            $mail->SMTPSecure = defined('MAIL_SECURE') ? MAIL_SECURE : 'tls';
            $mail->Port       = MAIL_PORT;
            $mail->CharSet    = 'UTF-8';
            $mail->SMTPDebug  = defined('MAIL_DEBUG') ? MAIL_DEBUG : 0;

            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody ?: strip_tags($body);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('[WASOM EMAIL ERROR] PHPMailer: ' . $e->getMessage());
            return false;
        }
    }

    // Fallback nativo (sem PHPMailer configurado)
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    $result = mail($to, $subject, $body, $headers);
    if (!$result) error_log("[WASOM EMAIL ERROR] mail() falhou para $to");
    return $result;
}

function sendVerificationEmail(string $email, string $name, string $token): bool
{
    $link    = APP_URL . '/verify-email?token=' . urlencode($token);
    $subject = 'Confirma o teu e-mail — ' . APP_NAME;
    $body    = "
    <div style='font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#222'>
      <div style='background:#FF0089;padding:24px 32px;border-radius:8px 8px 0 0'>
        <h1 style='color:#fff;margin:0;font-size:1.4rem'>" . APP_NAME . "</h1>
      </div>
      <div style='background:#fff;padding:32px;border:1px solid #eee;border-top:none;border-radius:0 0 8px 8px'>
        <h2 style='color:#FF0089;margin-top:0'>Confirma o teu e-mail</h2>
        <p>Olá <strong>{$name}</strong>,</p>
        <p>Obrigado por te registares. Clica no botão abaixo para activares a tua conta:</p>
        <div style='text-align:center;margin:32px 0'>
          <a href='{$link}'
             style='background:#FF0089;color:#fff;text-decoration:none;padding:14px 32px;
                    border-radius:8px;font-size:1rem;font-weight:600;display:inline-block'>
            Confirmar e-mail
          </a>
        </div>
        <p style='color:#666;font-size:.9rem'>
          Se o botão não funcionar, copia e cola este link no browser:<br>
          <a href='{$link}' style='color:#FF0089;word-break:break-all'>{$link}</a>
        </p>
        <p style='color:#999;font-size:.85rem'>
          Este link expira em <strong>48 horas</strong>.<br>
          Se não criaste esta conta, ignora este e-mail — nenhuma acção é necessária.
        </p>
        <hr style='border:none;border-top:1px solid #eee;margin:24px 0'>
        <small style='color:#bbb'>" . APP_NAME . " &mdash; Não respondas a este e-mail.</small>
      </div>
    </div>";

    return sendEmail($email, $subject, $body);
}


function sendPasswordResetEmail(string $email, string $name, string $code): bool
{
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
// ACTIVITY LOG
// ════════════════════════════════════════════════

function logActivity(int $id_users, string $type, string $desc, ?string $entity = null, ?int $entity_id = null): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    try {
        getDB()->prepare("
            INSERT INTO _user_activity_log
            (id_users, activity_type, description, entity, entity_id, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$id_users, $type, $desc, $entity, $entity_id, $ip]);
    } catch (Exception $e) {
        error_log('[LOG ERROR] ' . $e->getMessage());
    }
}

// ════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════

function sanitize(string $value): string
{
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function redirect(string $path, array $params = []): void
{
    $path = ltrim($path, '/');
    $url  = APP_URL . '/' . $path;
    if ($params) $url .= '?' . http_build_query($params);
    header("Location: $url");
    exit;
}

function checkHoneypot(string $field = 'honeypot'): void
{
    if (!empty($_POST[$field])) {
        header('Location: ' . APP_URL);
        exit;
    }
}

function parseName(string $full_name): array
{
    $parts = explode(' ', trim($full_name), 2);
    return ['first' => $parts[0] ?? '', 'last' => $parts[1] ?? ''];
}

function formatPrice(float $amount, string $currency = 'AOA'): string
{
    return number_format($amount, 2, ',', '.') . ' ' . $currency;
}

// ════════════════════════════════════════════════
// REMEMBER ME — Validação automática de cookie
// ════════════════════════════════════════════════

/**
 * Verificar se existe cookie "wuf_remember" válido.
 * Chamar no topo de cada pagina protegida antes de requireLogin().
 * Se válido, restaura a sessao do utilizador automaticamente.
 */
function checkRememberMe(): void
{
    if (isLoggedIn()) return; // Ja esta logado

    $cookie = $_COOKIE['wuf_remember'] ?? null;
    if (!$cookie) return;

    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.id_users, u.first_name, u.user_name, u.email_user,
               u.status_user, u.email_verified, u.plan_selected, u.onboarding_done
        FROM _users_security s
        JOIN _users u ON u.id_users = s.id_users
        WHERE s.remember_token = ?
        AND u.status_user NOT IN ('suspended','fraud','inactive','blocked')
        LIMIT 1
    ");
    $stmt->execute([$cookie]);
    $user = $stmt->fetch();

    if (!$user) {
        // Token inválido ou conta bloqueada — apagar cookie
        setcookie('wuf_remember', '', ['expires' => 1, 'path' => '/']);
        return;
    }

    // Restaurar sessao
    session_regenerate_id(true);
    $_SESSION['id_users']       = (int)$user['id_users'];
    $_SESSION['first_name']     = $user['first_name'];
    $_SESSION['user_name']      = $user['user_name'];
    $_SESSION['email']          = $user['email_user'];
    $_SESSION['status']         = $user['status_user'];
    $_SESSION['email_verified'] = (bool)$user['email_verified'];
    $_SESSION['plan_selected']  = $user['plan_selected'];
    $_SESSION['onboarding_done'] = (bool)($user['onboarding_done'] ?? false);

    // Renovar token (rolling token — novo cookie a cada acesso)
    $new_token = bin2hex(random_bytes(32));
    $expires   = time() + (30 * 24 * 3600);

    setcookie('wuf_remember', $new_token, [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => (APP_ENV === 'production'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    $db->prepare("UPDATE _users_security SET remember_token = ? WHERE id_users = ?")
        ->execute([$new_token, $user['id_users']]);

    // Registar actividade
    logActivity((int)$user['id_users'], 'auto_login', 'Login automatico via cookie remember-me');
}