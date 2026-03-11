<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY — Endpoint AJAX: Formulário Público de Suporte
// Arquivo: ajax/support.php
// Tabela: _support_ticket (unificada com authentic/support_process.php)
// Recebe multipart/form-data — tem upload de ficheiro
// ══════════════════════════════════════════════════════════════

require_once __DIR__ . '/../include/site.php';

header('Content-Type: application/json; charset=utf-8');

// ── Apenas POST ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// ── Validação CSRF ─────────────────────────────────────────────
// FormData (multipart) → token em $_POST
$csrf = trim($_POST['csrf_token'] ?? '');
if (!validateSiteCsrf($csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido. Recarrega a página e tenta novamente.']);
    exit;
}

// ── Sanitização ────────────────────────────────────────────────
$name        = trim(strip_tags($_POST['name']        ?? ''));
$email       = strtolower(trim($_POST['email']       ?? ''));
$urgency     = trim(strip_tags($_POST['urgency']     ?? ''));
$issue_type  = trim(strip_tags($_POST['issueType']   ?? ''));
$description = trim(strip_tags($_POST['description'] ?? ''));
// Este endpoint serve exclusivamente o formulário público (anónimo).
// O painel do utilizador autenticado usa ajax/support_dashboard.php.
$source_ticket = 'public_form';


// ── Validação ──────────────────────────────────────────────────
$errors = [];

if (mb_strlen($name) < 2)   $errors[] = 'O nome deve ter pelo menos 2 caracteres.';
if (mb_strlen($name) > 120) $errors[] = 'O nome não pode ultrapassar 120 caracteres.';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Insira um e-mail válido.';
if (mb_strlen($email) > 150) $errors[] = 'E-mail demasiado longo.';

$allowed_urgency = ['low', 'medium', 'high'];
if (!in_array($urgency, $allowed_urgency, true)) $errors[] = 'Selecione um nível de urgência válido.';

$allowed_issues = ['login', 'plan', 'stats', 'upload', 'other'];
if (!in_array($issue_type, $allowed_issues, true)) $errors[] = 'Selecione um tipo de problema válido.';

if (mb_strlen($description) < 10)   $errors[] = 'A descrição deve ter pelo menos 10 caracteres.';
if (mb_strlen($description) > 3000) $errors[] = 'A descrição não pode ultrapassar 3000 caracteres.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── Rate limit: máx. 5 pedidos / hora / IP ────────────────────
// (o modal usa 3/hora; o formulário público usa 5/hora — filtramos por source_ticket)
$ip = getVisitorIp();
try {
    $db   = getSiteDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM `_support_ticket`
        WHERE `ip_ticket`     = ?
          AND `source_ticket` = 'public_form'
          AND `creat_ticket`  >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$ip]);
    if ((int)$stmt->fetchColumn() >= 5) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Demasiados pedidos. Aguarda um pouco antes de tentar novamente.']);
        exit;
    }
} catch (Exception $e) {
    // Continua sem rate limit se a coluna source_ticket ainda não existir
    // (executa o ALTER TABLE antes de publicar)
}

// ── Processamento de ficheiros ─────────────────────────────────
$attachments_json = null;

if (!empty($_FILES['attachment']) && is_array($_FILES['attachment']['name'])) {
    $upload_dir  = __DIR__ . '/../assets/support_attachments/';
    $max_size    = 10 * 1024 * 1024; // 10 MB por ficheiro
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'zip', 'mp4', 'mov'];
    $saved_files = [];

    if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);

    $count = count($_FILES['attachment']['name']);
    for ($i = 0; $i < min($count, 5); $i++) {
        $tmp  = $_FILES['attachment']['tmp_name'][$i] ?? '';
        $orig = $_FILES['attachment']['name'][$i]     ?? '';
        $size = $_FILES['attachment']['size'][$i]     ?? 0;
        $err  = $_FILES['attachment']['error'][$i]    ?? UPLOAD_ERR_NO_FILE;

        if ($err === UPLOAD_ERR_NO_FILE || empty($tmp) || !is_uploaded_file($tmp)) continue;
        if ($err !== UPLOAD_ERR_OK || $size > $max_size) continue;

        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext, true)) continue;

        $safe = time() . '_' . $i . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (move_uploaded_file($tmp, $upload_dir . $safe)) $saved_files[] = $safe;
    }

    if (!empty($saved_files)) $attachments_json = json_encode($saved_files);
}

// ── Subject legível a partir do issue_type ────────────────────
$issue_labels = [
    'login'  => 'Problemas de Login',
    'plan'   => 'Alterar o plano',
    'stats'  => 'Erro nas Estatísticas',
    'upload' => 'Falha ao Enviar Arquivos',
    'other'  => 'Outro',
];
$subject = $issue_labels[$issue_type] ?? 'Suporte técnico';

// ── Inserção na tabela _support_ticket ────────────────────────
//
// NOTAS DE COMPATIBILIDADE com support_process.php:
//   • priority = 'normal'  — mesmo valor hardcoded do modal; não tocamos
//                            no ENUM existente. A urgência real fica em
//                            urgency_ticket (coluna nova via ALTER TABLE).
//   • creat_ticket / modif_ticket — omitidos; têm DEFAULT CURRENT_TIMESTAMP
//   • id_users = NULL       — utilizador não autenticado
//   • source_ticket         — coluna nova via ALTER TABLE (DEFAULT 'auth_modal'
//                            garante que os tickets do modal ficam correctos
//                            sem alterar support_process.php)
//
try {
    $db = getSiteDB();
    $stmt = $db->prepare("
        INSERT INTO `_support_ticket`
            (`id_users`, `name_contact`, `email_contact`, `source_ticket`,
             `ip_ticket`, `user_agent`,
             `subject`, `body`, `priority`,
             `urgency_ticket`, `issue_type_ticket`, `attachments_ticket`,
             `status_ticket`)
        VALUES
            (NULL, ?, ?, ?,
             ?, ?,
             ?, ?, 'normal',
             ?, ?, ?,
             'open')
    ");
    $stmt->execute([
        $name,
        $email,
        $source_ticket,   // sempre 'public_form' neste endpoint
        $ip,
        mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        $subject,
        $description,
        $urgency,         // urgency_ticket: low / medium / high
        $issue_type,      // issue_type_ticket: login / plan / stats / upload / other
        $attachments_json,
    ]);

    $id_ticket = (int)$db->lastInsertId();
    $new_csrf  = getSiteCsrf(true);

    echo json_encode([
        'success'  => true,
        'message'  => "O seu pedido de suporte foi enviado com sucesso! O seu ticket é #$id_ticket. A nossa equipa responderá em breve.",
        'new_csrf' => $new_csrf,
    ]);

} catch (Exception $e) {
    error_log('[SUPPORT PUBLIC ERROR] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno ao guardar o pedido. Tenta novamente ou contacta-nos directamente.',
    ]);
}