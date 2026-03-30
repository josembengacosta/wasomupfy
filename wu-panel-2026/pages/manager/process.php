<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY for Business — Process (AJAX)
// Arquivo: wu-panel-2026/pages/manager/process.php
// Rota:    wu-panel-2026/manager/process (POST only)
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.view');

function jOut(bool $ok, string $msg, array $extra = []): never
{
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jOut(false, 'Método não permitido.');

if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    jOut(false, 'Sessão expirada. Recarrega a página.');
}

$action = trim($_POST['action'] ?? '');

// ── Logout ────────────────────────────────────────────────────────────────
if ($action === 'logout_payment_panel') {
    unset($_SESSION['payment_control_auth'], $_SESSION['biz_auth_time'], $_SESSION['biz_attempts']);
    jOut(true, 'Sessão encerrada.');
}

if (empty($_SESSION['payment_control_auth'])) jOut(false, 'Acesso não autorizado.');
$_SESSION['biz_auth_time'] = time();

// ── Helpers ───────────────────────────────────────────────────────────────
function notifyUser(PDO $db, int $id_users, int $id_emp, string $type, string $title, string $body, string $url = ''): void
{
    try {
        $db->prepare("INSERT INTO _notification (id_users,id_employees,type,title,body,action_url) VALUES (?,?,?,?,?,?)")
            ->execute([$id_users, $id_emp, $type, $title, $body, $url]);
    } catch (Exception $e) {
        error_log('[NOTIFY] ' . $e->getMessage());
    }
}

function biz_mail(string $to, string $subject, string $body): bool
{
    $p = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';
    if (!file_exists($p)) return false;
    if (!class_exists('\Wasom\Mailer')) require_once $p;
    try {
        $wm = new \Wasom\Mailer();
        $wm->host = MAIL_HOST;
        $wm->port = MAIL_PORT;
        $wm->secure = defined('MAIL_SECURE') ? MAIL_SECURE : 'tls';
        $wm->username = MAIL_USER;
        $wm->password = MAIL_PASS;
        $wm->debug = 0;
        $wm->setFrom(MAIL_FROM, MAIL_FROM_NAME)->addAddress($to)->setSubject($subject)->setBody($body, strip_tags($body));
        $wm->send();
        return true;
    } catch (\Wasom\MailerException $e) {
        error_log('[BIZ_MAIL] ' . $e->getMessage());
        return false;
    }
}

function wd_email(array $w, string $type, string $reason = '', string $proof_url = ''): string
{
    $name   = trim(($w['first_name'] ?? '') . (' ' . ($w['second_name'] ?? '')));
    $amount = 'Kz ' . number_format((float)($w['amount_net'] ?? $w['amount_requested']), 2, ',', '.');
    $color  = ['approved' => '#22c55e', 'rejected' => '#ef4444', 'processing' => '#3b82f6'][$type] ?? '#FF0089';
    $title  = ['approved' => 'Pagamento Efectuado ✅', 'rejected' => 'Pedido de Saque Rejeitado ❌', 'processing' => 'Saque em Processamento 🔄'][$type] ?? 'Actualização';
    $cnt    = match ($type) {
        'approved'  => "O teu saque de <strong>$amount</strong> foi efectuado para a conta indicada." . ($proof_url ? "<br><a href='$proof_url' style='color:$color'>Ver comprovativo →</a>" : ''),
        'rejected'  => "O teu saque foi rejeitado. Motivo: <strong>" . htmlspecialchars($reason) . "</strong>",
        'processing' => "O teu saque de <strong>$amount</strong> está em processamento.",
        default     => 'O estado do teu pedido foi actualizado.',
    };
    return "<div style='font-family:\"Segoe UI\",Arial,sans-serif;max-width:560px;margin:auto'>
      <div style='background:linear-gradient(135deg,#0f0f1a,#1a1a2e);padding:28px 32px;border-radius:12px 12px 0 0;text-align:center'>
        <div style='font-size:1rem;font-weight:800;color:#fff'>Wasom Upfy</div>
        <div style='font-size:.7rem;color:$color;text-transform:uppercase;letter-spacing:1px;font-weight:700'>for Business</div>
      </div>
      <div style='background:#fff;padding:32px;border:1px solid #eee;border-top:none;border-radius:0 0 12px 12px'>
        <h2 style='color:#1a1a2e;font-size:1rem;margin:0 0 16px'>$title</h2>
        <p>Olá <strong>" . htmlspecialchars($name) . "</strong>,</p>
        <p style='color:#444;line-height:1.6'>$cnt</p>
        <div style='background:#f8f9fc;border-radius:8px;padding:14px 18px;margin:16px 0'>
          <div style='display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #eee'>
            <span style='font-size:.8rem;color:#888'>Referência</span>
            <span style='font-size:.8rem;font-weight:600;font-family:monospace'>#" . ($w['id_withdrawal'] ?? '—') . "</span>
          </div>
          <div style='display:flex;justify-content:space-between;padding:5px 0'>
            <span style='font-size:.8rem;color:#888'>Valor</span>
            <span style='font-size:.8rem;font-weight:700;color:$color'>$amount</span>
          </div>
        </div>
        <hr style='border:none;border-top:1px solid #f0f0f0;margin:16px 0'>
        <small style='color:#bbb'>" . APP_NAME . " — Não respondas.</small>
      </div>
    </div>";
}

function biz_fmt_d(float $v): string
{
    return 'Kz ' . number_format($v, 2, ',', '.');
}

// ════════════════════════════════════════════════════════════════════════════
// VER DETALHES DO SAQUE
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'get_withdrawal_details') {
    $id = (int)($_POST['id_withdrawal'] ?? 0);
    if (!$id) jOut(false, 'ID inválido.');

    $stmt = $db->prepare("
        SELECT w.*, a.full_name_account, a.tel_account, a.email_account,
               a.iban, a.express_number, a.type_account, a.status_account,
               a.bi_front_path, a.bi_back_path,
               u.first_name, u.second_name, u.email_user, u.tel_user, u.photo_user,
               CONCAT(e.first_name,' ',COALESCE(e.second_name,'')) AS reviewed_by_name
        FROM _withdrawal w
        LEFT JOIN _account a ON a.id_account=w.id_account
        LEFT JOIN _users u ON u.id_users=w.id_users
        LEFT JOIN _employees e ON e.id_employees=w.reviewed_by
        WHERE w.id_withdrawal=?
    ");
    $stmt->execute([$id]);
    $wd = $stmt->fetch();
    if (!$wd) jOut(false, 'Pedido não encontrado.');

    $uname  = trim($wd['first_name'] . ' ' . ($wd['second_name'] ?? ''));
    $smap   = ['pending' => 'Pendente', 'processing' => 'A processar', 'approved' => 'Aprovado', 'rejected' => 'Rejeitado', 'cancelled' => 'Cancelado'];
    $ab     = APP_URL . '/assets/comprovantes/uploads/';
    $is_act = in_array($wd['status_withdrawal'], ['pending', 'processing']);

    $h  = '<div class="row g-4">';
    // Col esquerda
    $h .= '<div class="col-md-5">';
    $h .= '<h6 class="fw-bold mb-3" style="color:#1a1a2e"><i class="bi bi-person-circle me-2"></i>Utilizador</h6>';
    foreach ([['Nome', $uname], ['E-mail', $wd['email_user']], ['Telefone', $wd['tel_user'] ?? '—']] as [$l, $v])
        $h .= '<div class="det-row"><span class="det-lbl">' . $l . '</span><span class="det-val">' . htmlspecialchars($v) . '</span></div>';
    $h .= '<h6 class="fw-bold mb-3 mt-4" style="color:#1a1a2e"><i class="bi bi-bank me-2"></i>Conta Destino</h6>';
    $h .= '<div class="det-row"><span class="det-lbl">Tipo</span><span class="det-val fw-bold">' . htmlspecialchars($wd['type_account'] ?? '—') . '</span></div>';
    $h .= '<div class="det-row"><span class="det-lbl">Titular</span><span class="det-val">' . htmlspecialchars($wd['full_name_account'] ?? '—') . '</span></div>';
    if ($wd['iban']) $h .= '<div class="det-row"><span class="det-lbl">IBAN</span><span class="det-val" style="font-family:monospace;font-size:.78rem">' . htmlspecialchars($wd['iban']) . '</span></div>';
    if ($wd['express_number']) $h .= '<div class="det-row"><span class="det-lbl">Nº Express</span><span class="det-val" style="font-family:monospace">' . htmlspecialchars($wd['express_number']) . '</span></div>';
    if ($wd['tel_account']) $h .= '<div class="det-row"><span class="det-lbl">Tel. Conta</span><span class="det-val">' . htmlspecialchars($wd['tel_account']) . '</span></div>';
    $acc_s = $wd['status_account'] === 'verified' ? 'approved' : ($wd['status_account'] ?? 'pending');
    $h .= '<div class="det-row"><span class="det-lbl">Estado Conta</span><span class="det-val"><span class="biz-s-' . $acc_s . '">' . ucfirst($wd['status_account'] ?? '—') . '</span></span></div>';
    // BI
    if ($wd['bi_front_path'] || $wd['bi_back_path']) {
        $h .= '<h6 class="fw-bold mb-2 mt-4" style="color:#1a1a2e"><i class="bi bi-person-vcard me-2"></i>Documento BI</h6>';
        $h .= '<div class="row g-2">';
        if ($wd['bi_front_path']) {
            $u = $ab . 'bi/' . $wd['bi_front_path'];
            $h .= '<div class="col-6"><small class="text-muted d-block mb-1 text-center">Frente</small><a href="' . $u . '" target="_blank"><img src="' . $u . '" class="bi-doc-img" alt="BI Frente"></a></div>';
        }
        if ($wd['bi_back_path']) {
            $u = $ab . 'bi/' . $wd['bi_back_path'];
            $h .= '<div class="col-6"><small class="text-muted d-block mb-1 text-center">Verso</small><a href="' . $u . '" target="_blank"><img src="' . $u . '" class="bi-doc-img" alt="BI Verso"></a></div>';
        }
        $h .= '</div>';
    }
    $h .= '</div>'; // col esquerda

    // Col direita
    $h .= '<div class="col-md-7">';
    $h .= '<h6 class="fw-bold mb-3" style="color:#1a1a2e"><i class="bi bi-arrow-up-circle me-2"></i>Pedido #' . (int)$wd['id_withdrawal'] . '</h6>';
    $h .= '<div class="det-row"><span class="det-lbl">Valor Pedido</span><span class="det-val fw-bold" style="color:#FF0089;font-size:1rem">' . biz_fmt_d((float)$wd['amount_requested']) . '</span></div>';
    $h .= '<div class="det-row"><span class="det-lbl">Taxa</span><span class="det-val text-danger">' . biz_fmt_d((float)($wd['amount_fee'] ?? 0)) . '</span></div>';
    $h .= '<div class="det-row"><span class="det-lbl">Valor Líquido</span><span class="det-val fw-bold" style="color:#22c55e;font-size:.95rem">' . biz_fmt_d((float)$wd['amount_net']) . '</span></div>';
    $h .= '<div class="det-row"><span class="det-lbl">Moeda</span><span class="det-val">' . htmlspecialchars($wd['currency'] ?? 'AOA') . '</span></div>';
    $sts = $wd['status_withdrawal'] === 'approved' ? 'approved' : $wd['status_withdrawal'];
    $h .= '<div class="det-row"><span class="det-lbl">Estado</span><span class="det-val"><span class="biz-s-' . $sts . '">' . ($smap[$wd['status_withdrawal']] ?? ucfirst($wd['status_withdrawal'])) . '</span></span></div>';
    $h .= '<div class="det-row"><span class="det-lbl">Data do Pedido</span><span class="det-val">' . date('d/m/Y H:i', strtotime($wd['creat_withdrawal'])) . '</span></div>';
    if ($wd['reviewed_at']) $h .= '<div class="det-row"><span class="det-lbl">Revisto em</span><span class="det-val">' . date('d/m/Y H:i', strtotime($wd['reviewed_at'])) . '</span></div>';
    if ($wd['reviewed_by_name']) $h .= '<div class="det-row"><span class="det-lbl">Revisto por</span><span class="det-val">' . htmlspecialchars($wd['reviewed_by_name']) . '</span></div>';
    if ($wd['paid_at']) $h .= '<div class="det-row"><span class="det-lbl">Pago em</span><span class="det-val text-success fw-bold">' . date('d/m/Y H:i', strtotime($wd['paid_at'])) . '</span></div>';
    if ($wd['rejection_reason']) $h .= '<div class="det-row"><span class="det-lbl">Motivo Rejeição</span><span class="det-val text-danger small">' . htmlspecialchars($wd['rejection_reason']) . '</span></div>';
    if ($wd['notes']) $h .= '<div class="det-row"><span class="det-lbl">Notas</span><span class="det-val text-muted small">' . htmlspecialchars($wd['notes']) . '</span></div>';
    // Comprovativo
    if ($wd['comprovante']) {
        $pu  = APP_URL . '/assets/payment/uploads/withdrawals/' . $wd['comprovante'];
        $img = preg_match('/\.(jpg|jpeg|png|webp)$/i', $wd['comprovante']);
        $h .= '<h6 class="fw-bold mb-2 mt-4" style="color:#1a1a2e"><i class="bi bi-file-earmark-check me-2"></i>Comprovativo</h6>';
        $h .= $img ? '<a href="' . $pu . '" target="_blank"><img src="' . $pu . '" class="bi-doc-img w-100" style="max-height:180px" alt="Comprovativo"></a>'
            : '<a href="' . $pu . '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Ver PDF</a>';
    }
    $h .= '</div></div>'; // col + row

    $footer = '';
    if ($is_act) {
        if ($wd['status_withdrawal'] === 'pending')
            $footer .= '<button class="btn btn-sm btn-outline-primary" onclick="setProcessing(' . (int)$wd['id_withdrawal'] . ');bootstrap.Modal.getInstance(document.getElementById(\'viewModal\')).hide()"><i class="bi bi-arrow-repeat me-1"></i>A processar</button>';
        $footer .= '<button class="btn btn-sm btn-success" onclick="approveWithdrawal(' . (int)$wd['id_withdrawal'] . ');bootstrap.Modal.getInstance(document.getElementById(\'viewModal\')).hide()"><i class="bi bi-check-lg me-1"></i>Aprovar</button>';
        $footer .= '<button class="btn btn-sm btn-danger" onclick="rejectWithdrawal(' . (int)$wd['id_withdrawal'] . ');bootstrap.Modal.getInstance(document.getElementById(\'viewModal\')).hide()"><i class="bi bi-x-lg me-1"></i>Rejeitar</button>';
    }
    $footer .= '<button class="btn btn-sm btn-outline-secondary ms-auto" data-bs-dismiss="modal">Fechar</button>';
    jOut(true, '', ['html' => $h, 'footer_html' => $footer]);
}

// ════════════════════════════════════════════════════════════════════════════
// MARCAR A PROCESSAR
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'set_processing_withdrawal') {
    requirePermission($admin_id, 'finances.edit');
    $id = (int)($_POST['id_withdrawal'] ?? 0);
    if (!$id) jOut(false, 'ID inválido.');
    $w = $db->prepare("SELECT w.*, u.email_user, u.first_name, u.second_name FROM _withdrawal w JOIN _users u ON u.id_users=w.id_users WHERE w.id_withdrawal=?");
    $w->execute([$id]);
    $wd = $w->fetch();
    if (!$wd) jOut(false, 'Pedido não encontrado.');
    if ($wd['status_withdrawal'] !== 'pending') jOut(false, 'Pedido não está pendente.');
    try {
        $db->prepare("UPDATE _withdrawal SET status_withdrawal='processing',reviewed_by=?,reviewed_at=NOW() WHERE id_withdrawal=?")->execute([$admin_id, $id]);
        notifyUser(
            $db,
            $wd['id_users'],
            $admin_id,
            'payment',
            'Saque em Processamento 🔄',
            'O teu saque de ' . biz_fmt_d((float)$wd['amount_net']) . ' está a ser processado. Serás notificado quando concluído.',
            APP_URL . '/dashboard/withdraw'
        );
        biz_mail($wd['email_user'], 'Saque em processamento — ' . APP_NAME, wd_email($wd, 'processing'));
        logAudit(
            $admin_id,
            $wd['id_users'],
            'withdrawal.set_processing',
            '_withdrawal',
            $id,
            json_encode(['status_withdrawal' => 'pending']),
            json_encode(['status_withdrawal' => 'processing'])
        );
        jOut(true, 'Marcado como "a processar". Utilizador notificado.');
    } catch (Exception $e) {
        error_log('[WD PROC] ' . $e->getMessage());
        jOut(false, 'Erro ao actualizar.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// APROVAR SAQUE
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'approve_withdrawal') {
    requirePermission($admin_id, 'finances.edit');
    $id    = (int)($_POST['id_withdrawal'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    if (!$id) jOut(false, 'ID inválido.');

    $stmt = $db->prepare("SELECT w.*,u.email_user,u.first_name,u.second_name,wl.id_wallet,wl.balance_aoa,wl.total_withdrawn FROM _withdrawal w JOIN _users u ON u.id_users=w.id_users LEFT JOIN _wallet wl ON wl.id_users=w.id_users WHERE w.id_withdrawal=?");
    $stmt->execute([$id]);
    $wd = $stmt->fetch();
    if (!$wd) jOut(false, 'Pedido não encontrado.');
    if (!in_array($wd['status_withdrawal'], ['pending', 'processing'])) jOut(false, 'Pedido já foi processado.');

    // Upload comprovativo
    $comp = null;
    if (!empty($_FILES['comprovante']['name'])) {
        $f = $_FILES['comprovante'];
        if (!in_array($f['type'], ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])) jOut(false, 'Tipo de ficheiro não permitido.');
        if ($f['size'] > 5 * 1024 * 1024) jOut(false, 'Ficheiro excede 5MB.');
        $dir = dirname(__DIR__, 4) . '/assets/payment/uploads/withdrawals/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $ext  = pathinfo($f['name'], PATHINFO_EXTENSION);
        $comp = 'wd_' . $id . '_' . time() . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dir . $comp)) jOut(false, 'Erro ao guardar ficheiro.');
    }

    try {
        $db->beginTransaction();
        $db->prepare("UPDATE _withdrawal SET status_withdrawal='approved',reviewed_by=?,reviewed_at=NOW(),paid_at=NOW(),comprovante=COALESCE(?,comprovante),notes=COALESCE(NULLIF(?,''),notes) WHERE id_withdrawal=?")
            ->execute([$admin_id, $comp, $notes ?: null, $id]);

        $bb = (float)($wd['balance_aoa'] ?? 0);
        $ba = max(0, $bb - (float)$wd['amount_net']);
        $tw = (float)($wd['total_withdrawn'] ?? 0) + (float)$wd['amount_net'];
        if ($wd['id_wallet'])
            $db->prepare("UPDATE _wallet SET balance_aoa=?,total_withdrawn=?,modif_wallet=NOW() WHERE id_wallet=?")->execute([$ba, $tw, $wd['id_wallet']]);

        $tx = $db->prepare("INSERT INTO _transaction (id_users,id_employees,type_transaction,amount,currency,balance_before,balance_after,reference,description) VALUES (?,?,'withdrawal',?,'AOA',?,?,?,?)");
        $tx->execute([$wd['id_users'], $admin_id, (float)$wd['amount_net'], $bb, $ba, 'WD-' . str_pad($id, 6, '0', STR_PAD_LEFT), 'Saque aprovado']);
        $tx_id = $db->lastInsertId();
        $db->prepare("UPDATE _withdrawal SET id_transaction=? WHERE id_withdrawal=?")->execute([$tx_id, $id]);
        $db->commit();

        $pu = $comp ? APP_URL . '/assets/payment/uploads/withdrawals/' . $comp : '';
        notifyUser(
            $db,
            $wd['id_users'],
            $admin_id,
            'payment',
            'Saque Aprovado ✅',
            'O teu saque de ' . biz_fmt_d((float)$wd['amount_net']) . ' foi aprovado e o pagamento efectuado.',
            APP_URL . '/dashboard/withdraw'
        );
        biz_mail($wd['email_user'], 'Saque aprovado — ' . APP_NAME, wd_email($wd, 'approved', '', $pu));
        logAudit(
            $admin_id,
            $wd['id_users'],
            'withdrawal.approved',
            '_withdrawal',
            $id,
            json_encode(['status_withdrawal' => $wd['status_withdrawal']]),
            json_encode(['status_withdrawal' => 'approved', 'amount_net' => $wd['amount_net']])
        );
        jOut(true, 'Saque aprovado! Wallet actualizada e utilizador notificado.');
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[WD APPROVE] ' . $e->getMessage());
        jOut(false, 'Erro ao aprovar.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// REJEITAR SAQUE
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'reject_withdrawal') {
    requirePermission($admin_id, 'finances.edit');
    $id     = (int)($_POST['id_withdrawal'] ?? 0);
    $reason = trim($_POST['reject_reason']  ?? '');
    if (!$id) jOut(false, 'ID inválido.');
    if (!$reason) jOut(false, 'O motivo é obrigatório.');

    $w = $db->prepare("SELECT w.*,u.email_user,u.first_name,u.second_name FROM _withdrawal w JOIN _users u ON u.id_users=w.id_users WHERE w.id_withdrawal=?");
    $w->execute([$id]);
    $wd = $w->fetch();
    if (!$wd) jOut(false, 'Pedido não encontrado.');
    if (!in_array($wd['status_withdrawal'], ['pending', 'processing'])) jOut(false, 'Pedido já foi processado.');

    try {
        $db->prepare("UPDATE _withdrawal SET status_withdrawal='rejected',reviewed_by=?,reviewed_at=NOW(),rejection_reason=? WHERE id_withdrawal=?")
            ->execute([$admin_id, $reason, $id]);
        notifyUser(
            $db,
            $wd['id_users'],
            $admin_id,
            'warning',
            'Saque Rejeitado ❌',
            'O teu saque foi rejeitado. Motivo: ' . $reason,
            APP_URL . '/dashboard/withdraw'
        );
        biz_mail($wd['email_user'], 'Saque rejeitado — ' . APP_NAME, wd_email($wd, 'rejected', $reason));
        logAudit(
            $admin_id,
            $wd['id_users'],
            'withdrawal.rejected',
            '_withdrawal',
            $id,
            json_encode(['status_withdrawal' => $wd['status_withdrawal']]),
            json_encode(['status_withdrawal' => 'rejected', 'reason' => $reason])
        );
        jOut(true, 'Pedido rejeitado. Utilizador notificado com o motivo.');
    } catch (Exception $e) {
        error_log('[WD REJECT] ' . $e->getMessage());
        jOut(false, 'Erro ao rejeitar.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// VER DETALHES ROYALTY
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'get_royalty_details') {
    $id = (int)($_POST['id_royalty'] ?? 0);
    if (!$id) jOut(false, 'ID inválido.');
    $r = $db->prepare("
        SELECT r.*,u.first_name,u.second_name,u.email_user,
               t.title_track,t.isrc,al.title_album,al.type_album,
               COALESCE(ar.stage_name,u.name_artist_band,u.first_name) AS artist_name,
               CONCAT(e.first_name,' ',COALESCE(e.second_name,'')) AS paid_by_name
        FROM _royalty r
        JOIN _users u ON u.id_users=r.id_users
        LEFT JOIN _track t ON t.id_track=r.id_track
        LEFT JOIN _album al ON al.id_album=t.id_album
        LEFT JOIN _artist ar ON ar.id_artist=al.id_artist
        LEFT JOIN _employees e ON e.id_employees=r.paid_by
        WHERE r.id_royalty=?
    ");
    $r->execute([$id]);
    $roy = $r->fetch();
    if (!$roy) jOut(false, 'Royalty não encontrado.');

    $smap = ['pending' => 'Pendente', 'processing' => 'A processar', 'paid' => 'Pago', 'cancelled' => 'Cancelado'];
    $uname = trim($roy['first_name'] . ' ' . ($roy['second_name'] ?? ''));

    $h  = '<div class="row g-3">';
    $h .= '<div class="col-md-6">';
    $h .= '<h6 class="fw-bold mb-3" style="color:#1a1a2e"><i class="bi bi-music-note-beamed me-2"></i>Faixa</h6>';
    foreach ([['Título', $roy['title_track'] ?? '—'], ['Álbum', $roy['title_album'] ?? '—'], ['Artista', $roy['artist_name'] ?? '—'], ['ISRC', $roy['isrc'] ?? '—'], ['Período', str_pad($roy['month_royalty'], 2, '0', STR_PAD_LEFT) . '/' . $roy['year_royalty']]] as [$l, $v])
        $h .= '<div class="det-row"><span class="det-lbl">' . $l . '</span><span class="det-val">' . htmlspecialchars($v) . '</span></div>';
    $h .= '</div><div class="col-md-6">';
    $h .= '<h6 class="fw-bold mb-3" style="color:#1a1a2e"><i class="bi bi-cash-coin me-2"></i>Valores</h6>';
    $h .= '<div class="det-row"><span class="det-lbl">Receita Bruta</span><span class="det-val">$ ' . number_format((float)$roy['gross_revenue'], 4, ',', '.') . '</span></div>';
    $h .= '<div class="det-row"><span class="det-lbl">Taxa Plataforma</span><span class="det-val text-danger">$ ' . number_format((float)$roy['platform_fee'], 4, ',', '.') . '</span></div>';
    $h .= '<div class="det-row"><span class="det-lbl">Royalty Líq. (AOA)</span><span class="det-val fw-bold" style="color:#FF0089;font-size:1rem">' . biz_fmt_d((float)$roy['net_royalty_aoa']) . '</span></div>';
    $h .= '<div class="det-row"><span class="det-lbl">Estado</span><span class="det-val"><span class="biz-s-' . ($roy['status_royalty'] === 'paid' ? 'approved' : $roy['status_royalty']) . '">' . ($smap[$roy['status_royalty']] ?? ucfirst($roy['status_royalty'])) . '</span></span></div>';
    $h .= '<div class="det-row"><span class="det-lbl">Utilizador</span><span class="det-val">' . htmlspecialchars($uname) . '</span></div>';
    if ($roy['paid_at']) $h .= '<div class="det-row"><span class="det-lbl">Pago em</span><span class="det-val text-success">' . date('d/m/Y H:i', strtotime($roy['paid_at'])) . '</span></div>';
    if ($roy['paid_by_name']) $h .= '<div class="det-row"><span class="det-lbl">Pago por</span><span class="det-val">' . htmlspecialchars($roy['paid_by_name']) . '</span></div>';
    $h .= '</div></div>';

    $footer = '';
    if ($roy['status_royalty'] === 'pending')
        $footer .= '<button class="btn btn-success btn-sm" onclick="payRoyalty(' . (int)$roy['id_royalty'] . ');bootstrap.Modal.getInstance(document.getElementById(\'royaltyModal\')).hide()"><i class="bi bi-check-lg me-1"></i>Pagar Royalty</button>';
    $footer .= '<button class="btn btn-sm btn-outline-secondary ms-auto" data-bs-dismiss="modal">Fechar</button>';
    jOut(true, '', ['html' => $h, 'footer_html' => $footer]);
}

// ════════════════════════════════════════════════════════════════════════════
// PAGAR ROYALTY (com suporte a ficheiro e notas)
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'pay_royalty') {
    requirePermission($admin_id, 'finances.edit');
    $id = (int)($_POST['id_royalty'] ?? 0);
    if (!$id) jOut(false, 'ID inválido.');

    $notes = trim($_POST['admin_note'] ?? '');

    // Upload do relatório (opcional)
    $report_file = null;
    if (!empty($_FILES['report_file']['name'])) {
        $file = $_FILES['report_file'];
        if (!in_array($file['type'], ['image/jpeg', 'image/png', 'application/pdf'])) {
            jOut(false, 'Tipo de ficheiro não permitido (apenas JPEG, PNG, PDF).');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            jOut(false, 'Ficheiro excede 5MB.');
        }
        $dir = dirname(__DIR__, 4) . '/assets/payment/uploads/royalties/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $report_file = 'royalty_' . $id . '_' . time() . '.' . $ext;
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                jOut(false, 'Não foi possível criar o diretório de uploads. Contacte o suporte.');
            }
        }
        if (!move_uploaded_file($file['tmp_name'], $dir . $report_file)) {
            jOut(false, 'Erro ao guardar ficheiro.');
        }
        $report_file = 'assets/payment/uploads/royalties/' . $report_file;
    }

    $r = $db->prepare("SELECT r.*,u.email_user,u.first_name,u.second_name,t.title_track,wl.id_wallet,wl.balance_aoa,wl.total_earned FROM _royalty r JOIN _users u ON u.id_users=r.id_users LEFT JOIN _track t ON t.id_track=r.id_track LEFT JOIN _wallet wl ON wl.id_users=r.id_users WHERE r.id_royalty=?");
    $r->execute([$id]);
    $roy = $r->fetch();
    if (!$roy) jOut(false, 'Royalty não encontrado.');
    if ($roy['status_royalty'] !== 'pending') jOut(false, 'Este royalty não está pendente.');

    try {
        $db->beginTransaction();

        // Actualizar royalty
        $sql = "UPDATE _royalty SET status_royalty='paid', paid_by=?, paid_at=NOW()";
        $params = [$admin_id];
        if ($report_file) {
            $sql .= ", report_file = ?";
            $params[] = $report_file;
        }
        $sql .= " WHERE id_royalty=?";
        $params[] = $id;
        $db->prepare($sql)->execute($params);

        // Atualizar wallet
        $bb = (float)($roy['balance_aoa'] ?? 0);
        $ba = $bb + (float)$roy['net_royalty_aoa'];
        $te = (float)($roy['total_earned'] ?? 0) + (float)$roy['net_royalty_aoa'];
        if ($roy['id_wallet'])
            $db->prepare("UPDATE _wallet SET balance_aoa=?,total_earned=?,modif_wallet=NOW() WHERE id_wallet=?")->execute([$ba, $te, $roy['id_wallet']]);

        // Inserir transacção
        $tx = $db->prepare("INSERT INTO _transaction (id_users,id_employees,type_transaction,amount,currency,balance_before,balance_after,reference,description) VALUES (?,?,'royalty_credit',?,'AOA',?,?,?,?)");
        $ref = 'ROY-' . str_pad($id, 6, '0', STR_PAD_LEFT);
        $desc = 'Royalty creditado — ' . ($roy['title_track'] ?? 'Faixa');
        $tx->execute([$roy['id_users'], $admin_id, (float)$roy['net_royalty_aoa'], $bb, $ba, $ref, $desc]);
        $tx_id = $db->lastInsertId();
        $db->prepare("UPDATE _royalty SET id_transaction=? WHERE id_royalty=?")->execute([$tx_id, $id]);

        $db->commit();

        // Guardar notas em log de auditoria
        if ($notes) {
            logAudit(
                $admin_id,
                $roy['id_users'],
                'royalty.paid_notes',
                '_royalty',
                $id,
                null,
                json_encode(['notes' => $notes])
            );
        }

        notifyUser(
            $db,
            $roy['id_users'],
            $admin_id,
            'payment',
            'Royalty Creditado 🎵',
            'O royalty de ' . biz_fmt_d((float)$roy['net_royalty_aoa']) . ' foi creditado na tua carteira.',
            APP_URL . '/dashboard/transactions'
        );
        logAudit(
            $admin_id,
            $roy['id_users'],
            'royalty.paid',
            '_royalty',
            $id,
            json_encode(['status_royalty' => 'pending']),
            json_encode(['status_royalty' => 'paid', 'aoa' => $roy['net_royalty_aoa']])
        );
        jOut(true, 'Royalty pago! Wallet creditada e utilizador notificado.');
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[ROY PAY] ' . $e->getMessage());
        jOut(false, 'Erro ao processar.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// VALIDAR COMPROVATIVO (payment_proof)
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'validate_proof') {
    requirePermission($admin_id, 'finances.edit');
    $id    = (int)($_POST['id_proof'] ?? 0);
    $ns    = trim($_POST['new_status'] ?? '');
    $reason = trim($_POST['reject_reason'] ?? '');
    if (!$id || !in_array($ns, ['validated', 'rejected'], true)) jOut(false, 'Dados inválidos.');
    if ($ns === 'rejected' && !$reason) jOut(false, 'Motivo obrigatório na rejeição.');

    $p = $db->prepare("SELECT pp.*,pi.id_users,u.email_user,u.first_name,u.second_name FROM _payment_proof pp JOIN _payment_intent pi ON pi.id_intent=pp.id_intent JOIN _users u ON u.id_users=pi.id_users WHERE pp.id_proof=?");
    $p->execute([$id]);
    $proof = $p->fetch();
    if (!$proof) jOut(false, 'Comprovativo não encontrado.');
    if ($proof['status'] !== 'pending') jOut(false, 'Já foi processado.');

    try {
        $db->prepare("UPDATE _payment_proof SET status=?,reject_reason=NULLIF(?,NULL),reviewer_id=?,reviewed_at=NOW() WHERE id_proof=?")
            ->execute([$ns, $reason ?: null, $admin_id, $id]);
        if ($ns === 'validated') {
            $db->prepare("UPDATE _payment_intent SET status='approved',approved_by=?,approved_at=NOW() WHERE id_intent=?")->execute([$admin_id, $proof['id_intent']]);
            notifyUser($db, $proof['id_users'], $admin_id, 'payment', 'Comprovativo Validado ✅', 'O teu comprovativo foi validado. O teu plano será activado em breve.', APP_URL . '/dashboard');
        } else {
            notifyUser($db, $proof['id_users'], $admin_id, 'warning', 'Comprovativo Rejeitado ❌', 'O comprovativo foi rejeitado. Motivo: ' . $reason, APP_URL . '/dashboard/payment/pay');
        }
        logAudit($admin_id, $proof['id_users'], 'proof.' . $ns, '_payment_proof', $id, json_encode(['status' => 'pending']), json_encode(['status' => $ns]));
        jOut(true, 'Comprovativo ' . ($ns === 'validated' ? 'validado' : 'rejeitado') . '!');
    } catch (Exception $e) {
        error_log('[PROOF] ' . $e->getMessage());
        jOut(false, 'Erro ao processar.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// TOGGLE STORE
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'toggle_store') {
    requirePermission($admin_id, 'analytics.edit');
    $id = $db_id = (int)($_POST['id_store'] ?? 0);
    $na = (int)($_POST['is_active'] ?? 0);
    if (!$id) jOut(false, 'ID de loja inválido.');
    $st = $db->prepare("SELECT id_store,name_store,is_active FROM _store WHERE id_store=?");
    $st->execute([$id]);
    $s = $st->fetch();
    if (!$s) jOut(false, 'Loja não encontrada.');
    $db->prepare("UPDATE _store SET is_active=? WHERE id_store=?")->execute([$na ? 1 : 0, $id]);
    logAudit(
        $admin_id,
        null,
        'store.' . ($na ? 'activated' : 'deactivated'),
        '_store',
        $id,
        json_encode(['is_active' => $s['is_active']]),
        json_encode(['is_active' => $na ? 1 : 0])
    );
    jOut(true, 'Loja ' . ($na ? 'activada' : 'desactivada') . ' com sucesso.');
}

// ════════════════════════════════════════════════════════════════════════════
// NOVAS AÇÕES PARA O MODAL NOVO DEPÓSITO
// ════════════════════════════════════════════════════════════════════════════

// 1. Obter conta bancária do utilizador (para exibir no modal)
if ($action === 'get_user_account') {
    $id = (int)($_POST['id_users'] ?? 0);
    if (!$id) jOut(false, 'ID inválido.');

    $stmt = $db->prepare("
        SELECT full_name_account, tel_account, email_account, iban, express_number,
               type_account, status_account
        FROM _account
        WHERE id_users = ? AND is_default = 1
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $acc = $stmt->fetch();

    if (!$acc) {
        jOut(false, 'Utilizador não possui conta bancária cadastrada.');
    }

    $status_class = match ($acc['status_account']) {
        'verified' => 'verified',
        'pending'  => 'pending',
        default    => 'empty'
    };

    $iban_display = $acc['iban'] ? '...' . substr($acc['iban'], -6) : '';
    $express_display = $acc['express_number'] ?: '';

    $html = "
        <div class='account-info-box {$status_class}'>
            <div class='ai-row'><span class='ai-lbl'>Titular</span><span class='ai-val'>" . htmlspecialchars($acc['full_name_account']) . "</span></div>
            <div class='ai-row'><span class='ai-lbl'>Tipo</span><span class='ai-val'>" . htmlspecialchars($acc['type_account']) . "</span></div>
            <div class='ai-row'><span class='ai-lbl'>IBAN</span><span class='ai-val' style='font-family:monospace'>" . htmlspecialchars($iban_display ?: '—') . "</span></div>
            <div class='ai-row'><span class='ai-lbl'>Express</span><span class='ai-val'>" . htmlspecialchars($express_display ?: '—') . "</span></div>
            <div class='ai-row'><span class='ai-lbl'>Estado</span><span class='ai-val'>" . ucfirst($acc['status_account']) . "</span></div>
        </div>";

    jOut(true, '', ['account_html' => $html]);
}

// 2. Obter álbuns do utilizador (apenas aprovados)
if ($action === 'get_user_albums') {
    $id = (int)($_POST['id_users'] ?? 0);
    if (!$id) jOut(false, 'ID inválido.');

    $stmt = $db->prepare("
        SELECT id_album, title_album, type_album
        FROM _album
        WHERE id_users = ? AND status_album = 'approved'
        ORDER BY creat_album DESC
    ");
    $stmt->execute([$id]);
    $albums = $stmt->fetchAll();

    $html = '';
    foreach ($albums as $album) {
        $html .= '<option value="' . (int)$album['id_album'] . '">'
            . htmlspecialchars($album['title_album']) . ' (' . $album['type_album'] . ')'
            . '</option>';
    }

    jOut(true, '', ['albums_html' => $html]);
}

// 3. Obter faixas de um álbum (apenas activas)
if ($action === 'get_album_tracks') {
    $id = (int)($_POST['id_album'] ?? 0);
    if (!$id) jOut(false, 'ID inválido.');

    $stmt = $db->prepare("
        SELECT id_track, title_track, track_number
        FROM _track
        WHERE id_album = ? AND status_track = 'active'
        ORDER BY track_number ASC
    ");
    $stmt->execute([$id]);
    $tracks = $stmt->fetchAll();

    $html = '';
    foreach ($tracks as $track) {
        $html .= '<option value="' . (int)$track['id_track'] . '">'
            . htmlspecialchars($track['title_track'])
            . ($track['track_number'] ? ' (#' . $track['track_number'] . ')' : '')
            . '</option>';
    }

    jOut(true, '', ['tracks_html' => $html]);
}

// 4. Depósito manual de royalty (cria registo e credita wallet)
if ($action === 'manual_deposit') {
    requirePermission($admin_id, 'finances.edit');

    $id_users   = (int)($_POST['id_users'] ?? 0);
    $id_track   = (int)($_POST['id_track'] ?? 0);
    $year       = (int)($_POST['year_royalty'] ?? 0);
    $month      = (int)($_POST['month_royalty'] ?? 0);
    $gross      = (float)($_POST['gross_revenue'] ?? 0);
    $fee_usd    = (float)($_POST['platform_fee'] ?? 0);
    $net_aoa    = (float)($_POST['net_royalty_aoa'] ?? 0);
    $rate       = (float)($_POST['exchange_rate'] ?? 0);
    $notes      = trim($_POST['admin_note'] ?? '');

    if (!$id_users || !$id_track || !$year || !$month || $gross <= 0 || $net_aoa <= 0) {
        jOut(false, 'Dados incompletos ou inválidos.');
    }

    // Verificar se já existe royalty para este período
    $check = $db->prepare("
        SELECT id_royalty FROM _royalty
        WHERE id_users = ? AND id_track = ? AND year_royalty = ? AND month_royalty = ?
    ");
    $check->execute([$id_users, $id_track, $year, $month]);
    if ($check->rowCount()) {
        jOut(false, 'Já existe um royalty registado para este utilizador, faixa e período.');
    }

    // Upload do relatório (opcional)
    $report_file = null;
    if (!empty($_FILES['report_file']['name'])) {
        $file = $_FILES['report_file'];
        if (!in_array($file['type'], ['image/jpeg', 'image/png', 'application/pdf'])) {
            jOut(false, 'Tipo de ficheiro não permitido (apenas JPEG, PNG, PDF).');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            jOut(false, 'Ficheiro excede 5MB.');
        }
        $dir = dirname(__DIR__, 4) . '/assets/payment/uploads/royalties/';
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                jOut(false, 'Não foi possível criar o diretório de uploads.');
            }
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $report_file = 'royalty_' . $id_users . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!@move_uploaded_file($file['tmp_name'], $dir . $report_file)) {
            jOut(false, 'Erro ao guardar ficheiro.');
        }
        $report_file = 'assets/payment/uploads/royalties/' . $report_file;
    }

    try {
        $db->beginTransaction();

        // 1. Inserir royalty (já pago)
        $stmt = $db->prepare("
            INSERT INTO _royalty
            (id_users, id_track, year_royalty, month_royalty,
             gross_revenue, platform_fee, net_royalty, currency, exchange_rate,
             net_royalty_aoa, status_royalty, report_file, paid_by, paid_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'USD', ?, ?, 'paid', ?, ?, NOW())
        ");
        $net_usd = $gross - $fee_usd;
        $stmt->execute([
            $id_users,
            $id_track,
            $year,
            $month,
            $gross,
            $fee_usd,
            $net_usd,
            $rate,
            $net_aoa,
            $report_file,
            $admin_id
        ]);
        $royalty_id = $db->lastInsertId();

        // 2. Atualizar wallet
        $wallet = $db->prepare("
            SELECT id_wallet, balance_aoa, total_earned
            FROM _wallet WHERE id_users = ?
        ");
        $wallet->execute([$id_users]);
        $w = $wallet->fetch();
        if (!$w) {
            $db->prepare("INSERT INTO _wallet (id_users) VALUES (?)")->execute([$id_users]);
            $w = ['id_wallet' => $db->lastInsertId(), 'balance_aoa' => 0, 'total_earned' => 0];
        }
        $new_balance = (float)$w['balance_aoa'] + $net_aoa;
        $new_total   = (float)$w['total_earned'] + $net_aoa;
        $db->prepare("UPDATE _wallet SET balance_aoa = ?, total_earned = ? WHERE id_wallet = ?")
            ->execute([$new_balance, $new_total, $w['id_wallet']]);

        // 3. Inserir transacção
        $tx = $db->prepare("
            INSERT INTO _transaction
            (id_users, id_employees, type_transaction, amount, currency,
             balance_before, balance_after, reference, description)
            VALUES (?, ?, 'royalty_credit', ?, 'AOA', ?, ?, ?, ?)
        ");
        $ref = 'ROY-MAN-' . str_pad($royalty_id, 6, '0', STR_PAD_LEFT);
        $desc = "Royalty manual — mês " . str_pad($month, 2, '0', STR_PAD_LEFT) . "/$year";
        $tx->execute([$id_users, $admin_id, $net_aoa, $w['balance_aoa'], $new_balance, $ref, $desc]);
        $tx_id = $db->lastInsertId();

        // Ligar transacção ao royalty
        $db->prepare("UPDATE _royalty SET id_transaction = ? WHERE id_royalty = ?")
            ->execute([$tx_id, $royalty_id]);

        // 4. Notificar utilizador
        $u_stmt = $db->prepare("SELECT email_user, first_name, second_name FROM _users WHERE id_users = ?");
        $u_stmt->execute([$id_users]);
        $user = $u_stmt->fetch();
        if ($user) {
            notifyUser(
                $db,
                $id_users,
                $admin_id,
                'payment',
                'Royalty Creditado 🎵',
                "Foi creditado Kz " . number_format($net_aoa, 2, ',', '.') . " na tua wallet.",
                APP_URL . '/dashboard/analytics/report'
            );
        }

        $db->commit();

        // Guardar notas em log de auditoria
        if ($notes) {
            logAudit(
                $admin_id,
                $id_users,
                'royalty.manual_notes',
                '_royalty',
                $royalty_id,
                null,
                json_encode(['notes' => $notes])
            );
        }

        logAudit(
            $admin_id,
            $id_users,
            'royalty.manual_deposit',
            '_royalty',
            $royalty_id,
            null,
            json_encode(['gross' => $gross, 'net_aoa' => $net_aoa])
        );

        jOut(true, 'Royalty depositado com sucesso! Wallet actualizada.');
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[MANUAL DEPOSIT] ' . $e->getMessage());
        jOut(false, 'Erro ao processar: ' . $e->getMessage());
    }
}

jOut(false, 'Acção desconhecida.');
