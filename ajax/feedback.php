<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY — Endpoint AJAX: Submeter Feedback
// Arquivo: ajax/feedback.php
// Chamado via fetch() POST pelo modal #formFeedback
// ══════════════════════════════════════════════════════════════

require_once __DIR__ . '/../include/site.php';

header('Content-Type: application/json; charset=utf-8');

// ── Apenas POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// ── Lê JSON ou form-data ──────────────────────────────────────
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    // fallback para form-data normal
    $data = $_POST;
}

// ── Validação CSRF ────────────────────────────────────────────
$csrf = trim($data['csrf'] ?? '');
if (!validateSiteCsrf($csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido. Recarrega a página e tenta novamente.']);
    exit;
}

// ── Sanitização e validação dos campos ───────────────────────
$name    = trim(strip_tags($data['name']    ?? ''));
$subject = trim(strip_tags($data['subject'] ?? ''));
$message = trim(strip_tags($data['message'] ?? ''));

$errors = [];

if (mb_strlen($name) < 2) {
    $errors[] = 'O nome deve ter pelo menos 2 caracteres.';
}
if (mb_strlen($name) > 120) {
    $errors[] = 'O nome não pode ultrapassar 120 caracteres.';
}

$allowed_subjects = [
    'Sugestão de melhoria',
    'Elogio',
    'Relatar um problema',
    'Outros',
];
if (!in_array($subject, $allowed_subjects)) {
    $subject = 'Sugestão de melhoria'; // fallback seguro
}

if (mb_strlen($message) < 10) {
    $errors[] = 'A mensagem deve ter pelo menos 10 caracteres.';
}
if (mb_strlen($message) > 2000) {
    $errors[] = 'A mensagem não pode ultrapassar 2000 caracteres.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── Rate limit simples por IP (máx. 3 feedbacks por hora) ────
$ip = getVisitorIp();
try {
    $db = getSiteDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM `_feedback`
        WHERE `ip_address` = ?
          AND `created_at` >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$ip]);
    $count = (int)$stmt->fetchColumn();

    if ($count >= 3) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Enviaste demasiados feedbacks recentemente. Tenta novamente mais tarde.']);
        exit;
    }
} catch (Exception $e) {
    // se falhar a verificação, deixa passar (não bloquear por erro de DB)
}

// ── Página de origem (enviada pelo JS) ───────────────────────
$page_origin = trim(strip_tags($data['page'] ?? $_SERVER['HTTP_REFERER'] ?? ''));
$page_origin = mb_substr($page_origin, 0, 255);

// ── Inserção na BD ────────────────────────────────────────────
try {
    $db   = getSiteDB();
    $stmt = $db->prepare("
        INSERT INTO `_feedback`
            (`name_fb`, `subject_fb`, `message_fb`, `page_origin`, `ip_address`, `user_agent`)
        VALUES
            (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $name,
        $subject,
        $message,
        $page_origin,
        $ip,
        mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512),
    ]);

    // Regenera CSRF após submissão bem-sucedida
    $new_csrf = getSiteCsrf(true); // passa true = forçar novo token

    echo json_encode([
        'success'  => true,
        'message'  => 'Obrigado pelo teu feedback! A tua opinião é muito importante para nós. 🙏',
        'new_csrf' => $new_csrf,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ocorreu um erro ao guardar o feedback. Tenta novamente.']);
}