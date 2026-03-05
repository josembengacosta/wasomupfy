<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Pagamento
// Arquivo: dashboard/payment_process.php
// Aceita: POST (JSON para 'seen') + POST (multipart para upload)
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
requireLogin();

header('Content-Type: application/json');
$id_users = (int)$_SESSION['id_users'];

// ─── Detectar tipo de requisição ──────────────────────
$is_json = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

if ($is_json) {
    // ══════════════════════════════════════════════════
    // ACÇÃO: 'seen' — utilizador viu as instruções
    // Actualiza status para 'waiting_payment'
    // ══════════════════════════════════════════════════
    $body = json_decode(file_get_contents('php://input'), true);

    if (!validateCsrf($body['csrf'] ?? '')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'CSRF inválido.']);
        exit;
    }

    if (($body['action'] ?? '') === 'seen') {
        $intent_id = (int)($body['intent_id'] ?? 0);

        // Confirmar que o intent pertence ao utilizador e está válido
        $stmt = getDB()->prepare("
            SELECT id_intent FROM _payment_intent
            WHERE id_intent = ? AND id_users = ?
            AND status = 'created' AND expires_at > NOW()
        ");
        $stmt->execute([$intent_id, $id_users]);

        if ($stmt->fetch()) {
            getDB()->prepare("
                UPDATE _payment_intent SET status = 'waiting_payment' WHERE id_intent = ?
            ")->execute([$intent_id]);
        }

        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Acção desconhecida.']);
    exit;
}

// ══════════════════════════════════════════════════════
// ACÇÃO: Upload de comprovativo
// ══════════════════════════════════════════════════════

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'message' => 'Sessão expirada. Recarrega a página.']);
    exit;
}

$intent_id = (int)($_POST['intent_id'] ?? 0);
$full_name = sanitize($_POST['full_name'] ?? '');
$phone     = sanitize($_POST['phone']     ?? '');
$method    = sanitize($_POST['method']    ?? '');

// ─── Validações básicas ───────────────────────────────
if (!in_array($method, ['express', 'iban'])) {
    echo json_encode(['ok' => false, 'message' => 'Método de pagamento inválido.']);
    exit;
}
if (strlen($full_name) < 4) {
    echo json_encode(['ok' => false, 'message' => 'Indica o nome completo do titular.']);
    exit;
}

// ─── Verificar Payment Intent ─────────────────────────
$stmt = getDB()->prepare("
    SELECT * FROM _payment_intent
    WHERE id_intent = ? AND id_users = ?
    AND status IN ('created','waiting_payment')
    AND expires_at > NOW()
");
$stmt->execute([$intent_id, $id_users]);
$intent = $stmt->fetch();

if (!$intent) {
    echo json_encode(['ok' => false, 'message' => 'A tua referência de pagamento expirou ou é inválida. Volta atrás e gera uma nova.']);
    exit;
}

// ─── Verificar limite de tentativas ──────────────────
if ((int)$intent['attempts'] >= 3) {
    echo json_encode(['ok' => false, 'message' => 'Excedeste o número máximo de tentativas para este pagamento. Contacta o suporte.']);
    exit;
}

// ─── Validar ficheiro ─────────────────────────────────
if (empty($_FILES['comprovativo']) || $_FILES['comprovativo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'message' => 'Erro ao receber o ficheiro. Tenta novamente.']);
    exit;
}

$file     = $_FILES['comprovativo'];
$allowed  = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
$max_size = 5 * 1024 * 1024; // 5 MB

// Verificar MIME real (não só extensão)
$finfo     = new finfo(FILEINFO_MIME_TYPE);
$real_mime = $finfo->file($file['tmp_name']);

if (!in_array($real_mime, $allowed)) {
    echo json_encode(['ok' => false, 'message' => 'Tipo de ficheiro não permitido. Usa JPG, PNG, WebP ou PDF.']);
    exit;
}
if ($file['size'] > $max_size) {
    echo json_encode(['ok' => false, 'message' => 'O ficheiro é muito grande. Máximo 5 MB.']);
    exit;
}

// ─── Hash SHA256 do ficheiro ──────────────────────────
$file_hash = hash_file('sha256', $file['tmp_name']);

// Verificar se este comprovativo já foi usado antes (anti-fraude)
$hash_check = getDB()->prepare("SELECT id_proof FROM _payment_proof WHERE file_hash = ?");
$hash_check->execute([$file_hash]);
if ($hash_check->fetch()) {
    // Penalizar trust score
    getDB()->prepare("UPDATE _users SET trust_score = GREATEST(0, trust_score - 20) WHERE id_users = ?")
           ->execute([$id_users]);
    logActivity($id_users, 'payment_fraud_attempt', 'Comprovativo duplicado detectado', 'payment_intent', $intent_id);
    echo json_encode(['ok' => false, 'message' => 'Este comprovativo já foi utilizado. Envia o comprovativo original do teu pagamento.']);
    exit;
}

// ─── Guardar ficheiro ─────────────────────────────────
$upload_dir = __DIR__ . '/../../assets/payment/uploads/proofs';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0750, true);
}

$ext       = match ($real_mime) {
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
    'image/webp'      => 'webp',
    'application/pdf' => 'pdf',
    default           => 'bin',
};
$filename  = 'proof_' . $id_users . '_' . $intent_id . '_' . time() . '.' . $ext;
$file_path = 'assets/payment/uploads/proofs/' . $filename;
$dest      = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['ok' => false, 'message' => 'Erro ao guardar o ficheiro. Tenta novamente.']);
    exit;
}

// ─── Inserir comprovativo na BD ───────────────────────
$db = getDB();
$db->beginTransaction();

try {
    $db->prepare("
        INSERT INTO _payment_proof
        (id_intent, id_users, full_name, phone, method, file_path, file_hash, file_size, file_type, status, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
    ")->execute([
        $intent_id,
        $id_users,
        $full_name,
        $phone ?: null,
        $method,
        $file_path,
        $file_hash,
        $file['size'],
        $real_mime,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    // Actualizar status do intent
    $db->prepare("
        UPDATE _payment_intent
        SET status = 'under_review', attempts = attempts + 1
        WHERE id_intent = ?
    ")->execute([$intent_id]);

    // Aprovação automática para todos os pagamentos (fase sem API)
    // Quando a API EMIS estiver integrada, esta lógica será substituída por webhook
    $db->prepare("UPDATE _payment_intent SET status = 'approved', approved_at = NOW() WHERE id_intent = ?")->execute([$intent_id]);
    $db->prepare("UPDATE _payment_proof SET status = 'validated' WHERE id_intent = ?")->execute([$intent_id]);
    activatePlan($id_users, (int)$intent['id_plan'], $intent_id, $db);
    logActivity($id_users, 'payment_auto_approved', 'Plano activado automaticamente apos upload de comprovativo', 'payment_intent', $intent_id);

    $db->commit();
    echo json_encode(['ok' => true, 'auto_approved' => true]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('[PAYMENT ERROR] ' . $e->getMessage());
    // Remover ficheiro guardado se a BD falhou
    if (file_exists($dest)) unlink($dest);
    echo json_encode(['ok' => false, 'message' => 'Erro interno. Tenta novamente.']);
}

// ══════════════════════════════════════════════════════
// ACTIVAR PLANO
// ══════════════════════════════════════════════════════
function activatePlan(int $id_users, int $id_plan, int $intent_id, PDO $db): void {
    $plan_stmt = $db->prepare("SELECT type_plan, validity_days FROM _plans WHERE id_plan = ?");
    $plan_stmt->execute([$id_plan]);
    $plan = $plan_stmt->fetch();

    $activated_at = date('Y-m-d H:i:s');
    $expires_at   = null;

    if ($plan['type_plan'] === 'subscription' && $plan['validity_days']) {
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . $plan['validity_days'] . ' days'));
    }

    $db->prepare("
        UPDATE _users
        SET status_user       = 'active',
            plan_selected     = ?,
            plan_activated_at = ?,
            plan_expires_at   = ?,
            trust_score       = LEAST(100, trust_score + 10)
        WHERE id_users = ?
    ")->execute([$id_plan, $activated_at, $expires_at, $id_users]);

    // Actualizar sessão
    $_SESSION['status'] = 'active';
}