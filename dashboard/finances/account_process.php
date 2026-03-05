<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Processamento de Contas de Saque
// Arquivo: dashboard/finances/account_process.php
// Acções: create | edit | delete
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
requireLogin();

header('Content-Type: application/json');

$id_users = (int)$_SESSION['id_users'];
$user     = getUserById($id_users);
$db       = getDB();

// ─── Helper: resposta JSON ────────────────────────────
function respond(bool $ok, string $message = '', array $extra = []): never
{
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));
    exit;
}

// ─── CSRF ─────────────────────────────────────────────
$csrf = $_POST['csrf_token'] ?? '';
if (!validateCsrf($csrf)) {
    http_response_code(403);
    respond(false, 'Sessão expirada. Recarrega a página e tenta novamente.');
}

$action = $_POST['action'] ?? '';

// ══════════════════════════════════════════════════════
// ACÇÃO: CREATE
// ══════════════════════════════════════════════════════
if ($action === 'create') {

    $account_type = $_POST['account_type'] ?? '';
    if (!in_array($account_type, ['express', 'iban'])) respond(false, 'Tipo de conta inválido.');

    // ── Verificar se já tem conta deste tipo ──────────
    $type_enum = $account_type === 'iban' ? 'IBAN' : 'Express';
    $dup = $db->prepare("SELECT id_account FROM _account WHERE id_users = ? AND type_account = ?");
    $dup->execute([$id_users, $type_enum]);
    if ($dup->fetch()) respond(false, "Já tens uma conta $type_enum registada. Elimina a existente primeiro.");

    // ── Validar senha ─────────────────────────────────
    $password = $_POST['confirm_password'] ?? '';
    if (empty($password)) respond(false, 'Insere a senha para confirmar.');
    if (!password_verify($password, $user['password_user'])) respond(false, 'Senha incorrecta.');

    // ── Campos por tipo ───────────────────────────────
    $full_name   = sanitize($_POST['full_name'] ?? '');
    $tel_account = null;
    $iban_number = null;
    $email_acc   = null;
    $express_num = null;

    if (strlen($full_name) < 4 || substr_count(trim($full_name), ' ') < 1) {
        respond(false, 'Insere o nome completo do titular (nome e apelido).');
    }

    if ($account_type === 'express') {
        $raw = preg_replace('/\D/', '', $_POST['express_number'] ?? '');
        if (!preg_match('/^9\d{8}$/', $raw)) respond(false, 'Número Express inválido (9 dígitos, começa por 9).');

        // Verificar duplicado de número
        $dup2 = $db->prepare("SELECT id_account FROM _account WHERE tel_account = ? AND id_account NOT IN (SELECT id_account FROM _account WHERE id_users = ? AND type_account = 'Express')");
        $dup2->execute(['+244' . $raw, $id_users]);
        if ($dup2->fetch()) respond(false, 'Este número Express já está registado noutra conta.');

        $tel_account = '+244' . $raw;
        $express_num = $tel_account;
    } else { // iban
        $iban_raw = strtoupper(preg_replace('/\s+/', '', $_POST['iban_number'] ?? ''));
        if (!preg_match('/^AO\d{2}[A-Z0-9]{18,}$/', $iban_raw)) respond(false, 'IBAN inválido. Deve começar com AO e ter pelo menos 20 caracteres.');

        // Verificar duplicado de IBAN
        $dup3 = $db->prepare("SELECT id_account FROM _account WHERE iban = ? AND id_users != ?");
        $dup3->execute([$iban_raw, $id_users]);
        if ($dup3->fetch()) respond(false, 'Este IBAN já está registado noutra conta da plataforma.');

        $iban_number = $iban_raw;
        $email_acc   = sanitize($_POST['email_account'] ?? '') ?: null;
    }

    // ── Upload BI ─────────────────────────────────────
    $upload_dir = __DIR__ . '/../../assets/comprovantes/uploads/bi/id_' . $id_users . '/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0750, true);

    $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size     = 8 * 1024 * 1024; // 8 MB
    $bi_paths     = [];

    foreach (['bi_front' => 'frente', 'bi_back' => 'verso'] as $field => $label) {
        if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            respond(false, "Faz o upload da $label do BI.");
        }
        $file = $_FILES[$field];
        if ($file['size'] > $max_size) respond(false, 'Ficheiro muito grande (máx. 8 MB).');

        $finfo     = new finfo(FILEINFO_MIME_TYPE);
        $real_mime = $finfo->file($file['tmp_name']);
        if (!in_array($real_mime, $allowed_mime)) respond(false, 'Tipo de ficheiro não permitido. Usa JPG, PNG ou WebP.');

        $ext      = match ($real_mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg'
        };
        $filename = 'bi_' . $id_users . '_' . $field . '_' . time() . rand(100, 999) . '.' . $ext;
        $dest     = $upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) respond(false, 'Erro ao guardar o ficheiro. Tenta novamente.');
        $bi_paths[$field] = 'assets/comprovantes/uploads/bi/id_' . $id_users . '/' . $filename;
    }

    // ── Inserir ───────────────────────────────────────
    $db->beginTransaction();
    try {
        $db->prepare("
            INSERT INTO _account
            (id_users, full_name_account, tel_account, email_account, iban, express_number,
             type_account, bi_front_path, bi_back_path, status_account, is_default, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 1, ?)
        ")->execute([
            $id_users,
            $full_name,
            $tel_account,
            $email_acc,
            $iban_number,
            $express_num,
            $type_enum,
            $bi_paths['bi_front'],
            $bi_paths['bi_back'],
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $new_id = (int)$db->lastInsertId();
        logActivity($id_users, 'payout_account_created', "Conta $type_enum criada — ID $new_id", 'account', $new_id);
        $db->commit();
        respond(true, 'Conta submetida com sucesso. Aguarda verificação em até 48 horas.');
    } catch (Exception $e) {
        $db->rollBack();
        // Limpar ficheiros
        foreach ($bi_paths as $path) {
            $full = __DIR__ . '/../../' . $path;
            if (file_exists($full)) unlink($full);
        }
        error_log('[ACCOUNT CREATE] ' . $e->getMessage());
        respond(false, 'Erro interno. Tenta novamente.');
    }
}

// ══════════════════════════════════════════════════════
// ACÇÃO: EDIT
// ══════════════════════════════════════════════════════
if ($action === 'edit') {

    $id_account  = (int)($_POST['id_account'] ?? 0);
    $account_type = $_POST['account_type'] ?? '';

    // Verificar que a conta pertence ao utilizador
    $acc = $db->prepare("SELECT * FROM _account WHERE id_account = ? AND id_users = ?");
    $acc->execute([$id_account, $id_users]);
    $account = $acc->fetch();
    if (!$account) respond(false, 'Conta não encontrada.');

    $full_name = sanitize($_POST['full_name'] ?? '');
    if (strlen($full_name) < 4 || substr_count(trim($full_name), ' ') < 1) {
        respond(false, 'Insere o nome completo do titular.');
    }

    $updates = ['full_name_account = ?', 'status_account = ?', 'modif_account = NOW()'];
    $params  = [$full_name, 'pending']; // volta a pending após edição

    if ($account_type === 'express') {
        $raw = preg_replace('/\D/', '', $_POST['express_number'] ?? '');
        if (!preg_match('/^9\d{8}$/', $raw)) respond(false, 'Número Express inválido.');

        $new_num = '+244' . $raw;

        // Verificar duplicado (excluindo a própria conta)
        $dup = $db->prepare("SELECT id_account FROM _account WHERE tel_account = ? AND id_account != ?");
        $dup->execute([$new_num, $id_account]);
        if ($dup->fetch()) respond(false, 'Este número Express já está registado noutra conta.');

        $updates[] = 'tel_account = ?';
        $updates[] = 'express_number = ?';
        $params[]  = $new_num;
        $params[]  = $new_num;
    } else { // iban
        $iban_raw = strtoupper(preg_replace('/\s+/', '', $_POST['iban_number'] ?? ''));
        if (!preg_match('/^AO\d{2}[A-Z0-9]{18,}$/', $iban_raw)) respond(false, 'IBAN inválido.');

        // Verificar duplicado (excluindo a própria conta)
        $dup = $db->prepare("SELECT id_account FROM _account WHERE iban = ? AND id_account != ? AND id_users != ?");
        $dup->execute([$iban_raw, $id_account, $id_users]);
        if ($dup->fetch()) respond(false, 'Este IBAN já está registado noutra conta da plataforma.');

        $updates[] = 'iban = ?';
        $params[]  = $iban_raw;

        $email = sanitize($_POST['email_account'] ?? '');
        if ($email) {
            $updates[] = 'email_account = ?';
            $params[] = $email;
        }

        $bank = sanitize($_POST['bank_name'] ?? '');
        if ($bank) {
            $updates[] = 'bank_name = ?';
            $params[] = $bank;
        }
    }

    $params[] = $id_account;
    $sql = 'UPDATE _account SET ' . implode(', ', $updates) . ' WHERE id_account = ?';

    try {
        $db->prepare($sql)->execute($params);
        logActivity($id_users, 'payout_account_edited', "Conta ID $id_account editada — volta a pending", 'account', $id_account);
        respond(true, 'Dados actualizados. A conta volta para verificação.');
    } catch (Exception $e) {
        error_log('[ACCOUNT EDIT] ' . $e->getMessage());
        respond(false, 'Erro interno ao guardar.');
    }
}

// ══════════════════════════════════════════════════════
// ACÇÃO: DELETE
// ══════════════════════════════════════════════════════
if ($action === 'delete') {

    $id_account = (int)($_POST['id_account'] ?? 0);
    $password   = $_POST['confirm_password'] ?? '';

    if (empty($password)) respond(false, 'Insere a tua senha para confirmar a eliminação.');
    if (!password_verify($password, $user['password_user'])) respond(false, 'Senha incorrecta.');

    // Verificar que a conta pertence ao utilizador
    $acc = $db->prepare("SELECT * FROM _account WHERE id_account = ? AND id_users = ?");
    $acc->execute([$id_account, $id_users]);
    $account = $acc->fetch();
    if (!$account) respond(false, 'Conta não encontrada.');

    // Verificar que não há saques pendentes vinculados a esta conta
    $pending = $db->prepare("SELECT id_withdrawal FROM _withdrawal_requests WHERE id_account = ? AND status IN ('pending','processing')");
    $pending->execute([$id_account]);
    if ($pending->fetch()) respond(false, 'Não é possível eliminar — há um pedido de saque pendente nesta conta. Aguarda a conclusão.');

    try {
        // Apagar apenas os ficheiros do BI (nunca o directório — pode ser reutilizado)
        foreach (['bi_front_path', 'bi_back_path'] as $col) {
            if (!empty($account[$col])) {
                $file_path = __DIR__ . '/../../' . $account[$col];
                if (file_exists($file_path) && is_file($file_path)) {
                    unlink($file_path);
                }
            }
        }

        $db->prepare("DELETE FROM _account WHERE id_account = ? AND id_users = ?")->execute([$id_account, $id_users]);
        logActivity($id_users, 'payout_account_deleted', "Conta ID $id_account eliminada ({$account['type_account']})", 'account', $id_account);
        respond(true, 'Conta eliminada com sucesso.');
    } catch (Exception $e) {
        error_log('[ACCOUNT DELETE] ' . $e->getMessage());
        respond(false, 'Erro interno ao eliminar.');
    }
}

// ─── Acção não reconhecida ────────────────────────────
respond(false, 'Acção inválida.');
