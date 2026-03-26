<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Acções de Contas Bancárias
// Arquivo: wu-panel-2026/pages/accounts/process.php
// Rota:    wu-panel-2026/accounts/process (POST only)
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.view');

function jsonOut(bool $ok, string $msg, array $extra = []): never
{
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
    exit;
}

// ── Enviar notificação para utilizador ───────────────────────────────────────
function sendNotification(int $id_users, string $type, string $title, string $body, ?string $action_url = null): void
{
    global $db, $admin_id;
    $stmt = $db->prepare("
        INSERT INTO _notification (id_users, id_employees, type, title, body, action_url, is_read, creat_notification)
        VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
    ");
    $stmt->execute([$id_users, $admin_id, $type, $title, $body, $action_url]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, 'Método não permitido.');
}

$csrf_post = $_POST['csrf_token'] ?? '';
$csrf_session = $_SESSION['admin_csrf_token'] ?? '';
if (!$csrf_session || !hash_equals($csrf_session, $csrf_post)) {
    jsonOut(false, 'Sessão expirada. Recarrega a página.');
}

$action = trim($_POST['action'] ?? '');
$id_account = (int)($_POST['id_account'] ?? 0);
if (!$id_account) jsonOut(false, 'ID da conta inválido.');

// Buscar dados da conta
$stmt = $db->prepare("
    SELECT a.*, u.id_users, u.first_name, u.second_name, u.email_user
    FROM _account a
    LEFT JOIN _users u ON u.id_users = a.id_users
    WHERE a.id_account = ?
");
$stmt->execute([$id_account]);
$account = $stmt->fetch();
if (!$account) jsonOut(false, 'Conta não encontrada.');

$user_name = trim($account['first_name'] . ' ' . $account['second_name']) ?: $account['email_user'];

// ──────────────────────────────────────────────────────────────────────────────
// ACÇÃO: toggle_status (verificar/rejeitar)
// ──────────────────────────────────────────────────────────────────────────────
if ($action === 'toggle_status') {
    requirePermission($admin_id, 'finances.edit');

    $new_status = trim($_POST['new_status'] ?? '');
    $reject_reason = trim($_POST['reject_reason'] ?? '');

    if (!in_array($new_status, ['verified', 'rejected'], true)) {
        jsonOut(false, 'Estado inválido.');
    }
    if ($account['status_account'] === $new_status) {
        jsonOut(false, 'A conta já está com este estado.');
    }
    if ($new_status === 'rejected' && empty($reject_reason)) {
        jsonOut(false, 'É necessário um motivo para rejeitar a conta.');
    }

    try {
        $db->beginTransaction();

        $db->prepare("
            UPDATE _account
            SET status_account = ?,
                reject_reason = ?,
                verified_by = ?,
                verified_at = ?
            WHERE id_account = ?
        ")->execute([
            $new_status,
            $reject_reason ?: null,
            ($new_status === 'verified') ? $admin_id : null,
            ($new_status === 'verified') ? date('Y-m-d H:i:s') : null,
            $id_account
        ]);

        // Registrar auditoria
        $old_val = json_encode(['status' => $account['status_account']]);
        $new_val = json_encode(['status' => $new_status, 'reject_reason' => $reject_reason]);
        logAudit($admin_id, $account['id_users'], 'account.status_changed', '_account', $id_account, $old_val, $new_val);

        $db->commit();

        // Enviar notificação
        if ($new_status === 'verified') {
            $title = 'Conta bancária verificada';
            $body = "Olá {$user_name}, a sua conta bancária ({$account['type_account']}) foi verificada com sucesso. Agora pode solicitar saques.";
            $type = 'success';
            $action_url = APP_URL . '/' . APP_URL_PANEL . '/withdraw';
        } else {
            $title = 'Conta bancária rejeitada';
            $body = "Olá {$user_name}, a sua conta bancária ({$account['type_account']}) foi rejeitada. Motivo: " . ($reject_reason ?: 'documento inválido');
            $type = 'error';
            $action_url = APP_URL . '/' . APP_URL_PANEL . '/withdrawal'; // Pode ser a página de contas para tentar novamente
        }
        sendNotification($account['id_users'], $type, $title, $body, $action_url);

        $msg = $new_status === 'verified' ? 'Conta verificada com sucesso!' : 'Conta rejeitada.';
        jsonOut(true, $msg);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[ACCOUNT STATUS] ' . $e->getMessage());
        jsonOut(false, 'Erro ao alterar estado.');
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// ACÇÃO: delete_account
// ──────────────────────────────────────────────────────────────────────────────
if ($action === 'delete_account') {
    requirePermission($admin_id, 'finances.edit');

    $admin_row = $db->prepare("SELECT password_employees FROM _employees WHERE id_employees = ?");
    $admin_row->execute([$admin_id]);
    $admin_data = $admin_row->fetch();
    if (!$admin_data) jsonOut(false, 'Erro de sessão.');

    $password_confirm = $_POST['password_confirm'] ?? '';
    if (empty($password_confirm) || !password_verify($password_confirm, $admin_data['password_employees'])) {
        jsonOut(false, 'Senha incorrecta.');
    }

    // Antes de excluir, guardar dados para auditoria
    $audit_old = json_encode([
        'id_users' => $account['id_users'],
        'full_name' => $account['full_name_account'],
        'type' => $account['type_account'],
        'iban' => $account['iban'],
        'express_number' => $account['express_number'],
        'status' => $account['status_account']
    ]);

    try {
        $db->beginTransaction();

        // Desassociar saques existentes (ON DELETE SET NULL na chave estrangeira)
        $db->prepare("UPDATE _withdrawal SET id_account = NULL WHERE id_account = ?")->execute([$id_account]);

        $db->prepare("DELETE FROM _account WHERE id_account = ?")->execute([$id_account]);

        $db->commit();

        logAudit($admin_id, $account['id_users'], 'account.deleted', '_account', $id_account, $audit_old, null);

        // Notificar utilizador sobre a exclusão
        $title = 'Conta bancária removida';
        $body = "Olá {$user_name}, a sua conta bancária ({$account['type_account']}) foi removida pelo administrador. Caso tenha dúvidas, contacte o suporte.";
        sendNotification($account['id_users'], 'warning', $title, $body, APP_URL . '/' . APP_URL_PANEL . '/account/accounts');

        jsonOut(true, 'Conta eliminada com sucesso!');
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[ACCOUNT DELETE] ' . $e->getMessage());
        jsonOut(false, 'Erro ao eliminar conta.');
    }
}

jsonOut(false, 'Acção desconhecida.');