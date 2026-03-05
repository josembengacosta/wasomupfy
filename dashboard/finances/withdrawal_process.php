<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Pedido de Saque
// Arquivo: dashboard/finances/withdrawal_process.php
// Chamado via fetch() POST de qualquer página que inclua
// o modal _modal_withdrawal.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
requireLogin();

header('Content-Type: application/json');

$id_users = (int)$_SESSION['id_users'];
$user     = getUserById($id_users);
$db       = getDB();

// ─── Helper ───────────────────────────────────────────
function respond(bool $ok, string $message = '', array $extra = []): never {
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));
    exit;
}

// ─── Só aceita POST ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(false, 'Método não permitido.');
}

// ─── CSRF ─────────────────────────────────────────────
if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    respond(false, 'Sessão expirada. Recarrega a página e tenta novamente.');
}

// ─── Verificar senha ──────────────────────────────────
$password = $_POST['password'] ?? '';
if (empty($password)) {
    respond(false, 'Insere a tua senha para confirmar o saque.');
}
if (!password_verify($password, $user['password_user'])) {
    respond(false, 'Senha incorrecta. Verifica e tenta novamente.');
}

// ─── Verificar plano activo ───────────────────────────
if (($user['status_user'] ?? '') !== 'active' || empty($user['plan_activated_at'])) {
    respond(false, 'O teu plano não está activo. Não é possível sacar.');
}

// ─── Conta bancária ───────────────────────────────────
$id_account = (int)($_POST['id_account'] ?? 0);
$acc_stmt   = $db->prepare("
    SELECT * FROM _account
    WHERE id_account = ? AND id_users = ? AND status_account = 'verified' AND is_default = 1
");
$acc_stmt->execute([$id_account, $id_users]);
$account = $acc_stmt->fetch();

if (!$account) {
    respond(false, 'Conta bancária não encontrada ou ainda não verificada.');
}

// ─── Saldo ────────────────────────────────────────────
$wallet_stmt = $db->prepare("SELECT balance_aoa FROM _wallet WHERE id_users = ?");
$wallet_stmt->execute([$id_users]);
$wallet = $wallet_stmt->fetch();

if (!$wallet) {
    respond(false, 'Carteira não encontrada. Contacta o suporte.');
}

$balance_aoa  = (float)$wallet['balance_aoa'];
$min_withdrawal = 10000.00;

if ($balance_aoa < $min_withdrawal) {
    respond(false, sprintf(
        'Saldo insuficiente. O mínimo para saque é %s Kz. O teu saldo é %s Kz.',
        number_format($min_withdrawal, 0, ',', '.'),
        number_format($balance_aoa, 2, ',', '.')
    ));
}

// ─── Verificar saque duplicado em aberto ──────────────
$dup_stmt = $db->prepare("
    SELECT id_withdrawal FROM _withdrawal
    WHERE id_users = ? AND status_withdrawal IN ('pending','processing')
    LIMIT 1
");
$dup_stmt->execute([$id_users]);
if ($dup_stmt->fetch()) {
    respond(false, 'Já tens um pedido de saque em processamento. Aguarda a conclusão antes de fazer um novo.');
}

// ─── Validar valores recebidos do form ───────────────
// Não confiamos cegamente nos valores do form — recalculamos no servidor
$withdrawal_fee_pct = 0.00; // taxa actual (0%) — alterar aqui quando necessário
$amount_requested   = $balance_aoa;
$amount_fee         = round($amount_requested * $withdrawal_fee_pct / 100, 2);
$amount_net         = round($amount_requested - $amount_fee, 2);

// Sanity check: o form deve enviar os mesmos valores
$form_net = round((float)($_POST['amount_net'] ?? 0), 2);
if (abs($form_net - $amount_net) > 0.01) {
    // Divergência — possível manipulação do formulário
    logActivity($id_users, 'withdrawal_tamper_attempt',
        "Tentativa de saque com valor manipulado: form_net=$form_net, server_net=$amount_net",
        'withdrawal', 0
    );
    respond(false, 'Erro de validação do valor. Recarrega a página e tenta novamente.');
}

// ─── Processar transacção ─────────────────────────────
$db->beginTransaction();
try {
    // 1. Inserir pedido de saque
    $ins = $db->prepare("
        INSERT INTO _withdrawal
        (id_users, id_account, amount_requested, amount_fee, amount_net, currency, status_withdrawal, notes)
        VALUES (?, ?, ?, ?, ?, 'AOA', 'pending', ?)
    ");
    $notes = sprintf(
        'Saque automático via painel. Conta: %s (%s). IP: %s',
        $account['full_name_account'],
        $account['type_account'],
        $_SERVER['REMOTE_ADDR'] ?? 'N/A'
    );
    $ins->execute([$id_users, $id_account, $amount_requested, $amount_fee, $amount_net, $notes]);
    $id_withdrawal = (int)$db->lastInsertId();

    // 2. Deduzir saldo da carteira
    $upd = $db->prepare("
        UPDATE _wallet
        SET balance_aoa      = balance_aoa - ?,
            total_withdrawn   = total_withdrawn + ?,
            modif_wallet      = NOW()
        WHERE id_users = ? AND balance_aoa >= ?
    ");
    $upd->execute([$amount_requested, $amount_requested, $id_users, $amount_requested]);

    if ($upd->rowCount() === 0) {
        // O saldo mudou entre a verificação e a actualização (race condition)
        throw new RuntimeException('Saldo alterado durante o processamento. Tenta novamente.');
    }

    // 3. Log de actividade
    logActivity(
        $id_users,
        'withdrawal_requested',
        sprintf('Saque de %s AOA solicitado. Conta: %s #%d. ID saque: %d',
            number_format($amount_requested, 2),
            $account['type_account'],
            $id_account,
            $id_withdrawal
        ),
        'withdrawal',
        $id_withdrawal
    );

    $db->commit();

    respond(true, 'Pedido de saque registado com sucesso.', [
        'id_withdrawal' => $id_withdrawal,
        'amount_net'    => $amount_net,
        'currency'      => 'AOA',
    ]);

} catch (RuntimeException $e) {
    $db->rollBack();
    respond(false, $e->getMessage());

} catch (Exception $e) {
    $db->rollBack();
    error_log('[WITHDRAWAL ERROR] user=' . $id_users . ' | ' . $e->getMessage());
    respond(false, 'Erro interno ao processar o saque. Tenta novamente ou contacta o suporte.');
}