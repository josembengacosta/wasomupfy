<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Edição de Pagamento
// Arquivo: wu-panel-2026/pages/payments/edit-process.php
// Rota:    wu-panel-2026/payments/edit-process (POST only)
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
require_once dirname(__DIR__, 3) . '/authentic/include/payment_workflow.php';
requirePermission($admin_id, 'finances.edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/' . ADMIN_PATH . '/payments');
}

// CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'])) {
    adminRedirect('/' . ADMIN_PATH . '/payments');
}

$id = (int)($_POST['id_payment'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/payments');

function redirectBack(string $base, array $params = []): never {
    $sep = str_contains($base, '?') ? '&' : '?';
    $qs = $params ? $sep . http_build_query($params) : '';
    header('Location: ' . APP_URL . $base . $qs);
    exit;
}

// Buscar pagamento actual
$stmt = $db->prepare("SELECT * FROM _payment WHERE id_payment = ?");
$stmt->execute([$id]);
$old = $stmt->fetch();
if (!$old) adminRedirect('/' . ADMIN_PATH . '/payments');

$back = '/' . ADMIN_PATH . '/payments/edit?id=' . $id;
$action = trim($_POST['action'] ?? '');

if ($action === 'update_payment') {
    $new_status = trim($_POST['status_payment'] ?? 'pending');
    $new_method = trim($_POST['payment_method'] ?? 'bank_transfer');
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Validar valores
    $allowed_status = ['pending', 'approved', 'rejected', 'refunded'];
    if (!in_array($new_status, $allowed_status, true)) $new_status = 'pending';
    $allowed_method = ['bank_transfer', 'multicaixa', 'paypal', 'card'];
    if (!in_array($new_method, $allowed_method, true)) $new_method = 'bank_transfer';

    // Se o status mudar para 'approved' e antes não era, activar plano
    $needs_activation = ($new_status === 'approved' && $old['status_payment'] !== 'approved');

    try {
        $db->beginTransaction();

        // Actualizar _payment
        $db->prepare("
            UPDATE _payment
            SET status_payment = ?,
                payment_method = ?,
                rejection_reason = ?,
                notes = ?,
                reviewed_by = ?,
                reviewed_at = NOW()
            WHERE id_payment = ?
        ")->execute([
            $new_status,
            $new_method,
            $rejection_reason ?: null,
            $notes ?: null,
            $admin_id,
            $id
        ]);

        // Se necessário, activar plano (chamar função similar ao payment_process)
        if ($needs_activation) {
            // Recuperar intent associado
            $intent_stmt = $db->prepare("
                SELECT * FROM _payment_intent WHERE reference_code = ? LIMIT 1
            ");
            $intent_stmt->execute([$old['payment_ref']]);
            $intent = $intent_stmt->fetch();

            if ($intent) {
                // Incluir a função activatePlan do payment_process (deve estar disponível)
                // Ou reimplementar aqui a lógica
                // Para evitar duplicação, incluímos o arquivo que contém a função
                paymentWorkflowActivatePlan($db, $intent, null, $admin_id);
            }
        }

        // Registar actividade na _collab_activity? Não, usar _audit_log
        $old_val = json_encode(['status' => $old['status_payment'], 'method' => $old['payment_method'], 'notes' => $old['notes']]);
        $new_val = json_encode(['status' => $new_status, 'method' => $new_method, 'notes' => $notes]);
        logAudit($admin_id, $old['id_users'], 'payment.updated', '_payment', $id, $old_val, $new_val);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[PAYMENT EDIT] ' . $e->getMessage());
        redirectBack($back, ['msg' => 'error']);
    }

    redirectBack('/' . ADMIN_PATH . '/payments/view?id=' . $id, ['msg' => 'updated']);
}

adminRedirect('/' . ADMIN_PATH . '/payments');
