<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Suporte
// Arquivo: authentic/support_process.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/include/functions.php';
startSecureSession();

function jsonOk(string $msg): never {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'message' => $msg]);
    exit;
}
function jsonErr(string $msg, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'message' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonErr('Método não permitido.', 405);
}

// ─── Recolher dados ───────────────────────────
$name    = sanitize($_POST['name_contact']  ?? '');
$email   = strtolower(trim($_POST['email_user'] ?? $_POST['email_contact'] ?? ''));
$subject = sanitize($_POST['subject']  ?? '');
$body    = sanitize($_POST['messenger'] ?? $_POST['body'] ?? '');

// ─── Validação ────────────────────────────────
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonErr('Endereço de e-mail inválido. Verifica e tenta novamente.');
}
if (empty($subject)) {
    jsonErr('Selecciona o assunto.');
}
if (strlen($body) < 10) {
    jsonErr('A mensagem deve ter pelo menos 10 caracteres.');
}

// ─── Rate limiting: máx. 3 tickets por IP/hora ─
$db = getDB();
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$stmt = $db->prepare("
    SELECT COUNT(*) FROM _support_ticket
    WHERE ip_ticket = ? AND creat_ticket > DATE_SUB(NOW(), INTERVAL 1 HOUR)
");
$stmt->execute([$ip]);
if ((int)$stmt->fetchColumn() >= 3) {
    jsonErr('Demasiados pedidos. Aguarda uma hora antes de enviar outro.');
}

// ─── Utilizador registado? ────────────────────
$user     = getUserByEmail($email);
$id_users = $user ? (int)$user['id_users'] : null;
// Se não passou nome e é utilizador registado, usar o nome do registo
if (empty($name) && $user) {
    $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
}

// ─── Inserir ticket ───────────────────────────
// Colunas reais: id_users, name_contact, email_contact, ip_ticket,
//                subject, body, priority, status_ticket
try {
    $db->prepare("
        INSERT INTO _support_ticket
            (id_users, name_contact, email_contact, ip_ticket, subject, body, priority, status_ticket)
        VALUES
            (?, ?, ?, ?, ?, ?, 'normal', 'open')
    ")->execute([$id_users, $name, $email, $ip, $subject, $body]);

    $id_ticket = (int)$db->lastInsertId();

    // Log de actividade para utilizadores registados
    if ($id_users) {
        logActivity($id_users, 'support_ticket_created',
            "Ticket #$id_ticket criado: $subject", 'support_ticket', $id_ticket);
    }

    // ─── Enviar e-mail de confirmação ao utilizador ─
    $first = $user ? ($user['first_name'] ?? 'utilizador') : ($name ?: 'utilizador');
    $emailBody = "
    <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto'>
      <h2 style='color:#FF0089'>Mensagem recebida ✓</h2>
      <p>Olá <strong>" . htmlspecialchars($first) . "</strong>,</p>
      <p>Recebemos a tua mensagem referente a <strong>" . htmlspecialchars($subject) . "</strong>.</p>
      <div style='background:#f9f9f9;border-left:4px solid #FF0089;padding:12px 16px;border-radius:4px;margin:16px 0'>
        <small style='color:#888'>Ticket #$id_ticket · Prioridade: Normal</small>
        <p style='margin:8px 0 0'>" . nl2br(htmlspecialchars($body)) . "</p>
      </div>
      <p>A nossa equipa irá responder em até <strong>48 horas</strong>.</p>
      <p style='color:#999;font-size:13px'>Se não enviaste este pedido, ignora este e-mail.</p>
      <hr>
      <small style='color:#999'>Wasom Upfy &mdash; Não respondas a este e-mail.</small>
    </div>";

    sendEmail(
        $email,
        "Ticket #$id_ticket recebido — " . APP_NAME,
        $emailBody
    );

    // ─── Notificar admin (quando tiver email admin definido) ─
    // if (defined('MAIL_ADMIN') && MAIL_ADMIN) {
    //     sendEmail(MAIL_ADMIN, "[Suporte #$id_ticket] $subject",
    //         "<p>De: $name &lt;$email&gt;</p><p>$body</p>");
    // }

    jsonOk("Mensagem enviada! O teu ticket é #$id_ticket. Respondemos em até 48 horas.");

} catch (\Exception $e) {
    error_log('[SUPPORT ERROR] ' . $e->getMessage());
    jsonErr('Erro interno ao guardar. Tenta novamente.');
}