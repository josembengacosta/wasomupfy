<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Suporte
// Arquivo: authentic/support_process.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions.php';
startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectBack();
}

// Recolher dados (os campos têm nomes diferentes nos vários modais)
$email   = strtolower(trim($_POST['email_user']     ?? $_POST['support_email']   ?? ''));
$subject = sanitize($_POST['subject']              ?? $_POST['support_subject']  ?? '');
$message = sanitize($_POST['messenger']            ?? $_POST['support_message']  ?? '');

// Validação básica
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($subject) || empty($message)) {
    redirectBack();
}

// Inserir ticket na base de dados
$db = getDB();
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

// Verificar se é utilizador registado
$user = getUserByEmail($email);
$id_users = $user ? (int)$user['id_users'] : null;

$db->prepare("
    INSERT INTO _support_ticket
    (id_users, email_ticket, subject_ticket, first_message, ip_ticket, status_ticket)
    VALUES (?, ?, ?, ?, ?, 'open')
")->execute([$id_users, $email, $subject, $message, $ip]);

$id_ticket = (int)$db->lastInsertId();

// Notificar admin por email (opcional — activar quando tiver SMTP)
// sendEmail(MAIL_FROM, "Novo ticket #$id_ticket: $subject", $message);

// Redirecionar com mensagem de sucesso
redirectBack();
