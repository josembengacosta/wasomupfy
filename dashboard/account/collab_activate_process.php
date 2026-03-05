<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Activação Colaborador
// Arquivo: dashboard/account/collab_activate_process.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';

function jsonOut(bool $ok, string $msg, array $extra = []): never {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(false, 'Método não permitido.');

$db          = getDB();
$token       = trim($_POST['token']            ?? '');
$new_password= trim($_POST['new_password']     ?? '');
$conf_pass   = trim($_POST['confirm_password'] ?? '');

if (empty($token))        jsonOut(false, 'Token em falta.');
if (strlen($new_password) < 8) jsonOut(false, 'A senha deve ter pelo menos 8 caracteres.');
if ($new_password !== $conf_pass) jsonOut(false, 'As senhas não coincidem.');

// Find collaborator by token
$st = $db->prepare("SELECT * FROM _collaborators WHERE invite_token = ? AND invite_token_used = 0 LIMIT 1");
$st->execute([$token]);
$collab = $st->fetch();

if (!$collab) jsonOut(false, 'Link inválido ou já utilizado.');
if (strtotime($collab['invite_token_expires']) < time()) jsonOut(false, 'Link expirado. Pede ao administrador que reenvie o convite.');

// Activate
$hash = password_hash($new_password, PASSWORD_DEFAULT);
$db->prepare("
    UPDATE _collaborators SET
        password_collab     = ?,
        status_collab       = 'active',
        invite_token_used   = 1,
        first_login_at      = NOW(),
        must_change_password = 0
    WHERE id_collab = ?
")->execute([$hash, $collab['id_collab']]);

// Log
try {
    $db->prepare("
        INSERT INTO _collab_activity (id_collab, id_users, activity_type, description, ip_address)
        VALUES (?,?,?,?,?)
    ")->execute([
        $collab['id_collab'], $collab['id_users'],
        'account_activated', 'Conta activada e senha definida', $_SERVER['REMOTE_ADDR'] ?? null
    ]);
} catch (Exception $e) {}

// Notify owner
try {
    $owner_st = $db->prepare("SELECT email_user, first_name FROM _users WHERE id_users = ?");
    $owner_st->execute([$collab['id_users']]);
    $owner = $owner_st->fetch();
    if ($owner) {
        $body = "<div style='font-family:Arial,sans-serif;max-width:540px;margin:auto'>
            <div style='background:linear-gradient(135deg,#FF0089,#FF4D4D);padding:24px 32px;border-radius:8px 8px 0 0'>
                <h1 style='color:#fff;margin:0;font-size:1.2rem'>".APP_NAME."</h1>
            </div>
            <div style='background:#fff;padding:24px 32px;border:1px solid #eee;border-top:none;border-radius:0 0 8px 8px'>
                <p>Olá <strong>".htmlspecialchars($owner['first_name'])."</strong>,<br/>
                <strong>".htmlspecialchars($collab['first_name'])."</strong> (@".htmlspecialchars($collab['user_collab']).")
                acabou de activar a conta de colaborador na tua equipa ".APP_NAME.".</p>
                <p style='color:#999;font-size:.8rem'>".APP_NAME." &mdash; Não respondas.</p>
            </div>
        </div>";
        sendEmail($owner['email_user'], 'Colaborador activou a conta — '.APP_NAME, $body);
    }
} catch (Exception $e) {}

// Send welcome email to collaborator with login link
try {
    $login_url = rtrim(APP_URL,'/') . '/dashboard/account/collab-login';
    $body = "<div style='font-family:Arial,sans-serif;max-width:540px;margin:auto'>
        <div style='background:linear-gradient(135deg,#FF0089,#FF4D4D);padding:28px 32px;border-radius:8px 8px 0 0'>
            <h1 style='color:#fff;margin:0;font-size:1.2rem'>".APP_NAME."</h1>
        </div>
        <div style='background:#fff;padding:28px 32px;border:1px solid #eee;border-top:none;border-radius:0 0 8px 8px'>
            <h2 style='color:#222;font-size:1rem'>Bem-vindo à equipa! 🎉</h2>
            <p>Olá <strong>".htmlspecialchars($collab['first_name'])."</strong>,<br/>
            A tua conta foi activada com sucesso. Podes aceder ao painel de colaboradores sempre que quiseres através do link abaixo:</p>
            <div style='text-align:center;margin:24px 0'>
                <a href='".htmlspecialchars($login_url)."'
                   style='background:linear-gradient(135deg,#FF0089,#FF4D4D);color:#fff;padding:12px 32px;border-radius:50px;text-decoration:none;font-weight:800'>
                    🔐 Entrar no Painel
                </a>
            </div>
            <div style='background:#f0f4ff;border-radius:8px;padding:14px 16px;font-size:.83rem'>
                <strong>Guarda este email</strong> — este link é a tua entrada para o painel de colaboradores.<br/>
                <strong>Username:</strong> @".htmlspecialchars($collab['user_collab'])."
            </div>
            <p style='color:#999;font-size:.8rem;margin-top:20px'>".APP_NAME." &mdash; Não respondas.</p>
        </div>
    </div>";
    sendEmail($collab['email_collab'], 'Conta activada — '.APP_NAME, $body);
} catch (Exception $e) {}

jsonOut(true, 'Conta activada com sucesso!', ['login_url' => rtrim(APP_URL,'/') . '/dashboard/account/collab-login']);