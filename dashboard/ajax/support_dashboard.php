<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Endpoint AJAX: Suporte do Painel
// Arquivo: dashboard/ajax/support_dashboard.php
// Origem:  dashboard/page/support.php (utilizador autenticado)
// Tabela:  _support_ticket (schema real)
// ══════════════════════════════════════════════════════════════

require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();

header('Content-Type: application/json; charset=utf-8');

function jsonOk(string $msg, array $extra = []): never
{
    echo json_encode(array_merge(['ok' => true, 'message' => $msg], $extra));
    exit;
}
function jsonErr(string $msg, int $code = 200): never
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $msg]);
    exit;
}

// ── Apenas POST ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonErr('Método não permitido.', 405);
}

// ── Sessão activa ──────────────────────────────────────────────
$id_users = isset($_SESSION['id_users']) ? (int)$_SESSION['id_users'] : null;
if (!$id_users) {
    jsonErr('Sessão expirada. Faz login novamente.', 401);
}

// ── CSRF ───────────────────────────────────────────────────────
$csrf_post    = trim($_POST['csrf_token'] ?? '');
$csrf_session = $_SESSION['csrf_token']   ?? '';
if (empty($csrf_post) || !hash_equals($csrf_session, $csrf_post)) {
    jsonErr('Token de segurança inválido. Recarrega a página.', 403);
}

$db   = getDB();
$user = getUserById($id_users);
if (!$user) {
    jsonErr('Utilizador não encontrado.', 401);
}

// ── Sanitização ────────────────────────────────────────────────
$urgency     = sanitize($_POST['urgency']     ?? '');
$issue_type  = sanitize($_POST['issueType']   ?? '');
$description = sanitize($_POST['description'] ?? '');

// Nome e e-mail sempre da conta autenticada (segurança)
$name  = trim(($user['first_name'] ?? '') . ' ' . ($user['second_name'] ?? ''));
$email = strtolower(trim($user['email_user'] ?? ''));

// ── Validação ──────────────────────────────────────────────────
$allowed_urgency = ['low', 'medium', 'high'];
if (!in_array($urgency, $allowed_urgency, true)) {
    jsonErr('Selecciona um nível de urgência válido.');
}

$issue_labels = [
    'login'   => 'Problema com login ou senha',
    'plan'    => 'Alterar ou questão sobre o plano',
    'payment' => 'Problema com pagamento',
    'stats'   => 'Erro nas estatísticas',
    'upload'  => 'Falha ao enviar ficheiros',
    'royalty' => 'Questão sobre royalties',
    'refund'  => 'Pedido de reembolso',
    'account' => 'Conta suspensa ou bloqueada',
    'other'   => 'Outro assunto',
];
if (!array_key_exists($issue_type, $issue_labels)) {
    jsonErr('Selecciona um tipo de problema válido.');
}

if (mb_strlen($description) < 10)   jsonErr('A descrição deve ter pelo menos 10 caracteres.');
if (mb_strlen($description) > 3000) jsonErr('A descrição não pode ultrapassar 3000 caracteres.');

// ── Rate limit: máx. 5 tickets / hora por utilizador ──────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
try {
    $rl = $db->prepare("
        SELECT COUNT(*) FROM _support_ticket
        WHERE id_users = ?
          AND creat_ticket >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $rl->execute([$id_users]);
    if ((int)$rl->fetchColumn() >= 5) {
        jsonErr('Demasiados pedidos. Aguarda um pouco antes de tentar novamente.');
    }
} catch (PDOException $e) { /* continua */
}

// ── Processamento de ficheiros ─────────────────────────────────
$saved_files = [];

if (!empty($_FILES['attachment']) && is_array($_FILES['attachment']['name'])) {
    $upload_dir  = __DIR__ . '/../../assets/support_attachments/';
    $max_size    = 10 * 1024 * 1024;
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'zip', 'mp4', 'mov'];

    if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);

    $count = count($_FILES['attachment']['name']);
    for ($i = 0; $i < min($count, 5); $i++) {
        $tmp  = $_FILES['attachment']['tmp_name'][$i] ?? '';
        $orig = $_FILES['attachment']['name'][$i]     ?? '';
        $size = $_FILES['attachment']['size'][$i]     ?? 0;
        $err  = $_FILES['attachment']['error'][$i]    ?? UPLOAD_ERR_NO_FILE;

        if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($tmp) || $size > $max_size) continue;
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext, true)) continue;

        $safe = time() . '_' . $i . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (move_uploaded_file($tmp, $upload_dir . $safe)) $saved_files[] = $safe;
    }
}

// ── Montar subject + body (schema real: sem colunas extras) ───
// _support_ticket não tem urgency_ticket/issue_type_ticket —
// encodamos tudo no body com prefixo estruturado e legível.
$urgency_label = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta'][$urgency] ?? ucfirst($urgency);
$subject       = $issue_labels[$issue_type];
$body          = "[Urgência: $urgency_label | Tipo: $issue_type]\n\n" . $description;

if (!empty($saved_files)) {
    $body .= "\n\n[Anexos: " . implode(', ', $saved_files) . "]";
}

// priority: schema aceita low / medium / high / urgent
$priority = $urgency; // mapeamento directo

// ── Inserção (_support_ticket — colunas reais) ─────────────────
try {
    $stmt = $db->prepare("
        INSERT INTO _support_ticket
            (id_users, name_contact, email_contact, ip_ticket,
             subject, body, priority, status_ticket)
        VALUES
            (?, ?, ?, ?,
             ?, ?, ?, 'open')
    ");
    $stmt->execute([
        $id_users,
        $name,
        $email,
        $ip,
        $subject,
        $body,
        $priority,
    ]);

    $id_ticket     = (int)$db->lastInsertId();
    $id_ticket_fmt = str_pad($id_ticket, 5, '0', STR_PAD_LEFT);

    logActivity(
        $id_users,
        'support_ticket_created',
        "Ticket #$id_ticket_fmt criado: $subject",
        'support_ticket',
        $id_ticket
    );

    // ── E-mail de confirmação ──────────────────────────────────
    if (!empty($email)) {
        $firstName = $user['first_name'] ?? $name;
        $emailHtml = "
        <div style='font-family:Arial,sans-serif;max-width:520px;margin:auto'>
          <h2 style='color:#FF0089'>Mensagem recebida ✓</h2>
          <p>Olá <strong>" . htmlspecialchars($firstName) . "</strong>,</p>
          <p>Recebemos o teu pedido referente a <strong>" . htmlspecialchars($subject) . "</strong>.</p>
          <div style='background:#f9f9f9;border-left:4px solid #FF0089;padding:12px 16px;border-radius:4px;margin:16px 0'>
            <small style='color:#888'>Ticket #$id_ticket_fmt &middot; Urgência: $urgency_label</small>
            <p style='margin:8px 0 0'>" . nl2br(htmlspecialchars($description)) . "</p>
          </div>
          <p>A nossa equipa irá responder em até <strong>48 horas</strong>.</p>
          <hr>
          <small style='color:#aaa'>Wasom Upfy &mdash; Não respondas a este e-mail.</small>
        </div>";

        try {
            sendEmail($email, "Ticket #$id_ticket_fmt recebido — " . APP_NAME, $emailHtml);
        } catch (Throwable $e) {
            error_log('[SUPPORT EMAIL ERROR] ' . $e->getMessage());
        }
    }

    jsonOk(
        "Pedido enviado com sucesso! O teu ticket é <strong>#$id_ticket_fmt</strong>. Respondemos em até 48 horas.",
        ['ticket_id' => $id_ticket]
    );
} catch (PDOException $e) {
    error_log('[SUPPORT DASHBOARD ERROR] ' . $e->getMessage());
    jsonErr('Erro interno ao guardar. Tenta novamente.');
}