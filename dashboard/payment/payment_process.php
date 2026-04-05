<?php

require_once __DIR__ . '/../../authentic/include/functions.php';
require_once __DIR__ . '/../../authentic/include/payment_workflow.php';

startSecureSession();
requireLogin();

header('Content-Type: application/json; charset=utf-8');

function paymentJsonError(string $message, int $status = 400): never
{
    http_response_code($status);
    echo json_encode([
        'ok'      => false,
        'message' => $message,
    ]);
    exit;
}

function paymentJsonOk(array $payload = []): never
{
    echo json_encode(array_merge(['ok' => true], $payload));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    paymentJsonError('Metodo nao permitido.', 405);
}

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];
$isJson   = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
$body     = [];

if ($isJson) {
    $body   = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = trim((string)($body['action'] ?? ''));
    $csrf   = (string)($body['csrf'] ?? '');
} else {
    $action = trim((string)($_POST['action'] ?? 'upload'));
    $csrf   = (string)($_POST['csrf_token'] ?? '');
}

if (!validateCsrf($csrf)) {
    paymentJsonError('Sessao expirada. Recarrega a pagina.', 403);
}

if ($action === 'seen') {
    $intentId = (int)($body['intent_id'] ?? 0);
    if (!$intentId) {
        paymentJsonError('Intent invalido.');
    }

    $stmt = $db->prepare("
        SELECT id_intent, status
        FROM _payment_intent
        WHERE id_intent = ?
          AND id_users = ?
          AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$intentId, $id_users]);
    $intent = $stmt->fetch();

    if (!$intent) {
        paymentJsonError('Referencia nao encontrada ou expirada.');
    }

    if ($intent['status'] === 'created') {
        $db->prepare("UPDATE _payment_intent SET status = 'waiting_payment' WHERE id_intent = ?")
            ->execute([$intentId]);
    }

    paymentJsonOk();
}

if (!in_array($action, ['', 'upload'], true)) {
    paymentJsonError('Accao desconhecida.');
}

$intentId = (int)($_POST['intent_id'] ?? 0);
if (!$intentId) {
    paymentJsonError('Referencia de pagamento em falta.');
}

$fullName = sanitize(trim((string)($_POST['full_name'] ?? '')));
$phone    = sanitize(trim((string)($_POST['phone'] ?? '')));
$method   = trim((string)($_POST['method'] ?? $_POST['method_used'] ?? ''));

if (!in_array($method, ['express', 'iban'], true)) {
    paymentJsonError('Metodo de pagamento invalido.');
}

if (strlen($fullName) < 4) {
    paymentJsonError('Indica o nome completo do titular.');
}

$intentStmt = $db->prepare("
    SELECT pi.*, pl.name_plan, pl.slug_plan, pl.type_plan, pl.validity_days, pl.max_releases
    FROM _payment_intent pi
    JOIN _plans pl ON pl.id_plan = pi.id_plan
    WHERE pi.id_intent = ?
      AND pi.id_users = ?
      AND pi.status IN ('created', 'waiting_payment')
      AND pi.expires_at > NOW()
    LIMIT 1
");
$intentStmt->execute([$intentId, $id_users]);
$intent = $intentStmt->fetch();

if (!$intent) {
    paymentJsonError('A tua referencia expirou, ja foi usada ou nao pertence a esta conta.');
}

if ((int)$intent['attempts'] >= 3) {
    paymentJsonError('Excedeste o numero maximo de tentativas para este pagamento. Contacta o suporte.');
}

if (empty($_FILES['comprovativo']) || ($_FILES['comprovativo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'Ficheiro demasiado grande para o servidor.',
        UPLOAD_ERR_FORM_SIZE  => 'Ficheiro demasiado grande.',
        UPLOAD_ERR_PARTIAL    => 'Upload incompleto. Tenta novamente.',
        UPLOAD_ERR_NO_FILE    => 'Selecciona um comprovativo antes de enviar.',
        UPLOAD_ERR_NO_TMP_DIR => 'Erro interno do servidor.',
        UPLOAD_ERR_CANT_WRITE => 'Erro ao guardar o ficheiro.',
    ];
    $errorCode = (int)($_FILES['comprovativo']['error'] ?? UPLOAD_ERR_NO_FILE);
    paymentJsonError($uploadErrors[$errorCode] ?? 'Erro desconhecido no upload.');
}

$file     = $_FILES['comprovativo'];
$maxSize  = 5 * 1024 * 1024;
$minSize  = 10 * 1024;
$mimeInfo = new finfo(FILEINFO_MIME_TYPE);
$mime     = (string)$mimeInfo->file($file['tmp_name']);
$allowed  = [
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
    'image/webp'      => 'webp',
    'application/pdf' => 'pdf',
];

if ((int)$file['size'] < $minSize) {
    paymentJsonError('Ficheiro muito pequeno. Envia um comprovativo valido.');
}

if ((int)$file['size'] > $maxSize) {
    paymentJsonError('O ficheiro e muito grande. Maximo 5 MB.');
}

if (!isset($allowed[$mime])) {
    paymentJsonError('Tipo de ficheiro nao permitido. Usa JPG, PNG, WebP ou PDF.');
}

$fileHash = hash_file('sha256', $file['tmp_name']);
$hashStmt = $db->prepare("SELECT id_proof FROM _payment_proof WHERE file_hash = ? LIMIT 1");
$hashStmt->execute([$fileHash]);
if ($hashStmt->fetch()) {
    $db->prepare("UPDATE _users SET trust_score = GREATEST(0, trust_score - 20) WHERE id_users = ?")
        ->execute([$id_users]);
    logActivity($id_users, 'payment_fraud_attempt', 'Comprovativo duplicado detectado', 'payment_intent', $intentId);
    paymentJsonError('Este comprovativo ja foi utilizado. Envia o comprovativo original do teu pagamento.');
}

$uploadDir = __DIR__ . '/../../assets/payment/uploads/proofs';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0750, true) && !is_dir($uploadDir)) {
    paymentJsonError('Nao foi possivel preparar o directorio de uploads.');
}

$filename = 'proof_' . $id_users . '_' . $intentId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
$filePath = 'assets/payment/uploads/proofs/' . $filename;
$destPath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    paymentJsonError('Erro ao guardar o ficheiro. Tenta novamente.');
}

$trustStmt = $db->prepare("SELECT trust_score FROM _users WHERE id_users = ? LIMIT 1");
$trustStmt->execute([$id_users]);
$trustScore        = (int)$trustStmt->fetchColumn();
$needsManualReview = ($intent['slug_plan'] === 'label') || ($trustScore < 30);
$autoApproveTs     = time() + 1800;

try {
    $db->beginTransaction();

    $db->prepare("
        INSERT INTO _payment_proof
            (id_intent, id_users, full_name, phone, method, file_path, file_hash, file_size, file_type, status, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
    ")->execute([
        $intentId,
        $id_users,
        $fullName,
        $phone ?: null,
        $method,
        $filePath,
        $fileHash,
        (int)$file['size'],
        $mime,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    $proofId = (int)$db->lastInsertId();

    $db->prepare("
        UPDATE _payment_intent
        SET status = 'under_review',
            attempts = attempts + 1
        WHERE id_intent = ?
    ")->execute([$intentId]);

    if (!$needsManualReview) {
        paymentWorkflowCreatePendingActivation($db, $intent, [
            'id_proof'   => $proofId,
            'method'     => $method,
            'file_path'  => $filePath,
        ]);
    }

    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    if (file_exists($destPath)) {
        unlink($destPath);
    }
    error_log('[PAYMENT_PROCESS] ' . $e->getMessage());
    paymentJsonError('Erro interno ao processar o pagamento. Tenta novamente.', 500);
}

logActivity($id_users, 'payment_proof_uploaded', 'Comprovativo enviado para o plano ' . $intent['slug_plan'], 'payment_intent', $intentId);

if ($needsManualReview) {
    $message = $intent['slug_plan'] === 'label'
        ? 'O plano Label requer validacao manual da nossa equipa. Entraremos em contacto assim que a analise for concluida.'
        : 'O teu comprovativo foi recebido e esta em revisao manual. Vais receber uma notificacao assim que a validacao terminar.';

    paymentJsonOk([
        'state'   => 'under_review',
        'message' => $message,
    ]);
}

paymentJsonOk([
    'state'           => 'processing',
    'message'         => 'Comprovativo recebido. O teu plano entra agora em processamento e sera activado automaticamente em cerca de 30 minutos.',
    'auto_approve_at' => date('Y-m-d H:i:s', $autoApproveTs),
    'auto_approve_ts' => $autoApproveTs,
]);
