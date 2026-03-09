<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY — Endpoint AJAX: Formulário de Contacto
// Arquivo: ajax/contact.php
// ══════════════════════════════════════════════════════════════

require_once __DIR__ . '/../include/site.php';

header('Content-Type: application/json; charset=utf-8');

// ── Apenas POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// ── Lê JSON ───────────────────────────────────────────────────
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

// ── Validação CSRF ────────────────────────────────────────────
if (!validateSiteCsrf(trim($data['csrf'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido. Recarrega a página e tenta novamente.']);
    exit;
}

// ── Sanitização ───────────────────────────────────────────────
$name    = trim(strip_tags($data['name']    ?? ''));
$email   = trim(strip_tags($data['email']   ?? ''));
$phone   = trim(strip_tags($data['phone']   ?? ''));
$subject = trim(strip_tags($data['subject'] ?? ''));
$message = trim(strip_tags($data['message'] ?? ''));

// ── Validação ─────────────────────────────────────────────────
$errors = [];

if (mb_strlen($name) < 3)   $errors[] = 'O nome deve ter pelo menos 3 caracteres.';
if (mb_strlen($name) > 120) $errors[] = 'O nome é demasiado longo.';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Insere um e-mail válido.';
if (mb_strlen($email) > 120)                    $errors[] = 'O e-mail é demasiado longo.';

if ($phone && mb_strlen($phone) > 30) $errors[] = 'O número de telefone é inválido.';

if (mb_strlen($subject) < 5)   $errors[] = 'O assunto deve ter pelo menos 5 caracteres.';
if (mb_strlen($subject) > 120) $errors[] = 'O assunto é demasiado longo.';

if (mb_strlen($message) < 10)  $errors[] = 'A mensagem deve ter pelo menos 10 caracteres.';
if (mb_strlen($message) > 2000) $errors[] = 'A mensagem não pode ultrapassar 2000 caracteres.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── Rate limit: máx. 3 mensagens por IP por hora ──────────────
$ip = getVisitorIp();
try {
    $db   = getSiteDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM `_contact_message`
        WHERE `ip_address` = ?
          AND `created_at` >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$ip]);
    if ((int)$stmt->fetchColumn() >= 3) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Enviaste demasiadas mensagens recentemente. Tenta novamente mais tarde.']);
        exit;
    }
} catch (Exception $e) { /* deixa passar */ }

// ── Inserção na BD ────────────────────────────────────────────
try {
    $db   = getSiteDB();
    $stmt = $db->prepare("
        INSERT INTO `_contact_message`
            (`name_msg`, `email_msg`, `phone_msg`, `subject_msg`, `message_msg`, `ip_address`, `user_agent`)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $name,
        $email,
        $phone ?: null,
        $subject,
        $message,
        $ip,
        mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512),
    ]);

    $new_csrf = getSiteCsrf(true);

    echo json_encode([
        'success'  => true,
        'message'  => 'Mensagem enviada com sucesso! Respondemos em menos de 30 minutos.',
        'new_csrf' => $new_csrf,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao guardar a mensagem. Tenta novamente.']);
}