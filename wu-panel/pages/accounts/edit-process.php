<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Edição de Conta Bancária
// Arquivo: wu-panel/pages/accounts/edit-process.php
// Rota:    wu-panel/accounts/edit-process (POST only)
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/' . ADMIN_PATH . '/accounts');
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'])) {
    adminRedirect('/' . ADMIN_PATH . '/accounts');
}

$action = trim($_POST['action'] ?? '');
if ($action !== 'update_account') adminRedirect('/' . ADMIN_PATH . '/accounts');

$id_account = (int)($_POST['id_account'] ?? 0);
if (!$id_account) adminRedirect('/' . ADMIN_PATH . '/accounts');

// Buscar dados antigos
$stmt = $db->prepare("SELECT * FROM _account WHERE id_account = ?");
$stmt->execute([$id_account]);
$old = $stmt->fetch();
if (!$old) adminRedirect('/' . ADMIN_PATH . '/accounts?msg=not_found');

// Campos do formulário
$full_name_account = trim($_POST['full_name_account'] ?? '');
$tel_account       = trim($_POST['tel_account'] ?? '');
$email_account     = trim($_POST['email_account'] ?? '');
$type_account      = trim($_POST['type_account'] ?? 'IBAN');
$iban              = trim($_POST['iban'] ?? '');
$express_number    = trim($_POST['express_number'] ?? '');
$is_default        = (int)($_POST['is_default'] ?? 0);
$status_account    = trim($_POST['status_account'] ?? 'pending');
$reject_reason     = trim($_POST['reject_reason'] ?? '');

// Validações
if (empty($full_name_account)) adminRedirect('/' . ADMIN_PATH . '/accounts/edit?id=' . $id_account . '&msg=error');
if (!in_array($type_account, ['IBAN', 'Express', 'PayPal'], true)) $type_account = 'IBAN';
if (!in_array($status_account, ['pending', 'verified', 'rejected'], true)) $status_account = 'pending';

// Para IBAN, exigir preenchimento? Opcional, mas validamos
if ($type_account === 'IBAN' && empty($iban)) {
    // pode permitir vazio, mas avisamos
}
if ($type_account === 'Express' && empty($express_number)) {
    // idem
}

// Se status mudou para verified ou rejected, registar quem e quando
$verified_by = null;
$verified_at = null;
if ($status_account === 'verified' && $old['status_account'] !== 'verified') {
    $verified_by = $admin_id;
    $verified_at = date('Y-m-d H:i:s');
} elseif ($status_account !== 'verified' && $old['status_account'] === 'verified') {
    // Se saiu de verified, limpar campos de verificação
    $verified_by = null;
    $verified_at = null;
}

// Montar valores a atualizar
$updates = [
    'full_name_account' => $full_name_account,
    'tel_account'       => $tel_account ?: null,
    'email_account'     => $email_account ?: null,
    'type_account'      => $type_account,
    'is_default'        => $is_default,
    'status_account'    => $status_account,
    'reject_reason'     => $reject_reason ?: null,
    'verified_by'       => $verified_by,
    'verified_at'       => $verified_at,
];

// Campos específicos conforme tipo
if ($type_account === 'IBAN') {
    $updates['iban'] = $iban ?: null;
    $updates['express_number'] = null;
} elseif ($type_account === 'Express') {
    $updates['express_number'] = $express_number ?: null;
    $updates['iban'] = null;
} else {
    $updates['iban'] = null;
    $updates['express_number'] = null;
}

try {
    $db->beginTransaction();

    $sql = "UPDATE _account SET
        full_name_account = :full_name_account,
        tel_account = :tel_account,
        email_account = :email_account,
        type_account = :type_account,
        iban = :iban,
        express_number = :express_number,
        is_default = :is_default,
        status_account = :status_account,
        reject_reason = :reject_reason,
        verified_by = :verified_by,
        verified_at = :verified_at
        WHERE id_account = :id_account";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($updates, ['id_account' => $id_account]));

    // Registrar na auditoria
    $old_val = json_encode([
        'full_name' => $old['full_name_account'],
        'type' => $old['type_account'],
        'status' => $old['status_account'],
        'is_default' => $old['is_default'],
        'iban' => $old['iban'],
        'express_number' => $old['express_number']
    ]);
    $new_val = json_encode([
        'full_name' => $full_name_account,
        'type' => $type_account,
        'status' => $status_account,
        'is_default' => $is_default,
        'iban' => $updates['iban'],
        'express_number' => $updates['express_number']
    ]);
    logAudit($admin_id, $old['id_users'], 'account.updated', '_account', $id_account, $old_val, $new_val);

    // Criar notificação para o utilizador se o estado mudou
    if ($old['status_account'] !== $status_account) {
        $title = '';
        $body = '';
        $type = 'info';
        $action_url = APP_URL . '/' . APP_URL_PANEL . '/withdraw'; // Página de saques ou contas

        if ($status_account === 'verified') {
            $title = 'Conta bancária verificada';
            $body = 'A sua conta bancária foi verificada com sucesso. Já pode realizar saques.';
            $type = 'success';
        } elseif ($status_account === 'rejected') {
            $title = 'Conta bancária rejeitada';
            $body = 'A sua conta bancária foi rejeitada. Motivo: ' . ($reject_reason ?: 'Não especificado') . ' Por favor, actualize os dados e submeta novamente.';
            $type = 'warning';
        } elseif ($status_account === 'pending' && $old['status_account'] !== 'pending') {
            $title = 'Conta bancária em análise';
            $body = 'A sua conta bancária foi submetida para análise. Aguarde a verificação.';
            $type = 'info';
        }

        if ($title) {
            $notif_stmt = $db->prepare("
                INSERT INTO _notification (id_users, type, title, body, action_url, is_read, creat_notification)
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            $notif_stmt->execute([$old['id_users'], $type, $title, $body, $action_url]);
        }
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    error_log('[ACCOUNT EDIT] ' . $e->getMessage());
    adminRedirect('/' . ADMIN_PATH . '/accounts/edit?id=' . $id_account . '&msg=error');
}

adminRedirect('/' . ADMIN_PATH . '/accounts/view?id=' . $id_account . '&msg=updated');
