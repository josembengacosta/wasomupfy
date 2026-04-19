<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Acções de Colaboradores
// Arquivo: wu-panel/pages/collab/process.php
// Rota:    wu-panel/collab/process (POST only)
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';

// ── Resposta JSON ──────────────────────────────
function jsonOut(bool $ok, string $msg, array $extra = []): never
{
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
    exit;
}

// ── Verificações de base ──────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, 'Método não permitido.');
}

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'xmlhttprequest') {
    // Aceitar também FormData sem o header (fetch sem header customizado)
    // — apenas verificamos CSRF abaixo
}

// ── CSRF ──────────────────────────────────────
$csrf_post    = $_POST['csrf_token'] ?? '';
$csrf_session = $_SESSION['admin_csrf_token'] ?? '';

if (!$csrf_session || !hash_equals($csrf_session, $csrf_post)) {
    jsonOut(false, 'Sessão expirada. Recarrega a página e tenta novamente.');
}

// ── Permissão mínima para todas as acções ──────
requirePermission($admin_id, 'users.view');

// ── Ler acção e ID ─────────────────────────────
$action    = trim($_POST['action'] ?? '');
$id_collab = (int)($_POST['id_collab'] ?? 0);

if ($id_collab <= 0) {
    jsonOut(false, 'ID do colaborador inválido.');
}

// ── Buscar colaborador ─────────────────────────
$stmt = $db->prepare("
    SELECT
        c.*,
        u.first_name       AS owner_first,
        u.second_name      AS owner_second,
        u.email_user       AS owner_email
    FROM _collaborators c
    LEFT JOIN _users u ON u.id_users = c.id_users
    WHERE c.id_collab = ?
");
$stmt->execute([$id_collab]);
$col = $stmt->fetch();

if (!$col) {
    jsonOut(false, 'Colaborador não encontrado.');
}

// ── Helper: gerar senha forte ─────────────────
function collab_strong_password(int $len = 16): string
{
    $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lower   = 'abcdefghjkmnpqrstuvwxyz';
    $digits  = '23456789';
    $special = '@#$%&*!?';
    $all     = $upper . $lower . $digits . $special;

    $pwd  = $upper[random_int(0, strlen($upper) - 1)];
    $pwd .= $lower[random_int(0, strlen($lower) - 1)];
    $pwd .= $digits[random_int(0, strlen($digits) - 1)];
    $pwd .= $special[random_int(0, strlen($special) - 1)];
    for ($i = 4; $i < $len; $i++) {
        $pwd .= $all[random_int(0, strlen($all) - 1)];
    }
    return str_shuffle($pwd);
}

// ── Helper: enviar email via WasomMailer ──────
function collab_send_email(string $to, string $subject, string $body): bool
{
    $mailer_path = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';
    if (!file_exists($mailer_path)) {
        error_log('[COLLAB MAIL] WasomMailer não encontrado: ' . $mailer_path);
        return false;
    }
    if (!class_exists('\Wasom\Mailer')) {
        require_once $mailer_path;
    }
    try {
        $wm = new \Wasom\Mailer();
        $wm->host     = MAIL_HOST;
        $wm->port     = MAIL_PORT;
        $wm->secure   = defined('MAIL_SECURE') ? MAIL_SECURE : 'tls';
        $wm->username = MAIL_USER;
        $wm->password = MAIL_PASS;
        $wm->debug    = 0;
        $wm->setFrom(MAIL_FROM, MAIL_FROM_NAME)
            ->addAddress($to)
            ->setSubject($subject)
            ->setBody($body, strip_tags($body));
        $wm->send();
        return true;
    } catch (\Wasom\MailerException $e) {
        error_log('[COLLAB MAIL] ' . $e->getMessage());
        return false;
    }
}

// ══════════════════════════════════════════════
// ACÇÃO: REENVIAR CONVITE
// ══════════════════════════════════════════════
if ($action === 'resend_invite') {

    requirePermission($admin_id, 'users.edit');

    if ($col['invite_token_used']) {
        jsonOut(false, 'Este colaborador já activou a conta. Não é possível reenviar o convite.');
    }
    if ($col['status_collab'] !== 'pending') {
        jsonOut(false, 'Só é possível reenviar convites a colaboradores com estado "Pendente".');
    }

    $new_token    = bin2hex(random_bytes(64));
    $new_expires  = date('Y-m-d H:i:s', strtotime('+72 hours'));
    $new_password = collab_strong_password(16);
    $new_hash     = password_hash($new_password, PASSWORD_DEFAULT, ['cost' => 12]);

    try {
        $db->beginTransaction();

        $db->prepare("
            UPDATE _collaborators
            SET invite_token         = ?,
                invite_token_expires = ?,
                password_collab      = ?,
                must_change_password = 1
            WHERE id_collab = ?
        ")->execute([$new_token, $new_expires, $new_hash, $id_collab]);

        $db->prepare("
            INSERT INTO _collab_activity
                (id_collab, id_users, activity_type, description, ip_address)
            VALUES (?, ?, 'invite_resent', 'Convite reenviado pelo administrador', ?)
        ")->execute([$id_collab, $col['id_users'], $_SERVER['REMOTE_ADDR'] ?? null]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[COLLAB RESEND DB] ' . $e->getMessage());
        jsonOut(false, 'Erro ao gerar novo convite. Tenta novamente.');
    }

    // Buscar plano do proprietário
    $plan_name = APP_NAME;
    if ($col['id_users']) {
        $plan_row = $db->prepare("
            SELECT p.name_plan
            FROM _plans p
            JOIN _users u ON u.plan_selected = p.id_plan
            WHERE u.id_users = ?
        ");
        $plan_row->execute([$col['id_users']]);
        $prow = $plan_row->fetch();
        if ($prow) $plan_name = $prow['name_plan'];
    }

    $role_labels = [
        'admin'   => 'Administrador',
        'editor'  => 'Editor',
        'analyst' => 'Analista',
        'support' => 'Suporte',
    ];
    $role_label  = $role_labels[$col['role_collab']] ?? ucfirst($col['role_collab']);
    $owner_name  = trim(($col['owner_first'] ?? '') . ' ' . ($col['owner_second'] ?? ''));
    $activate_url = rtrim(APP_URL, '/') . '/dashboard/account/collab-activate?token=' . urlencode($new_token);

    $subject = 'Novo convite para a equipa — ' . APP_NAME;
    $body    = "
    <div style='font-family:\"Segoe UI\",Arial,sans-serif;max-width:540px;margin:auto'>
      <div style='background:linear-gradient(135deg,#FF0089,#6c63ff);padding:28px 32px;border-radius:10px 10px 0 0;text-align:center'>
        <div style='display:inline-block;background:rgba(255,255,255,.15);border:3px solid rgba(255,255,255,.3);
                    border-radius:50%;width:52px;height:52px;line-height:52px;text-align:center;
                    font-size:1.1rem;font-weight:800;color:#fff;letter-spacing:-1px;margin-bottom:10px'>
          WU
        </div>
        <h1 style='color:#fff;font-size:1.2rem;margin:0;font-weight:700'>Convite actualizado!</h1>
        <p style='color:rgba(255,255,255,.8);margin:6px 0 0;font-size:.85rem'>
          Foi gerado um novo convite para ti
        </p>
      </div>
      <div style='background:#fff;padding:32px;border:1px solid #eee;border-top:none;border-radius:0 0 10px 10px'>
        <p>Olá <strong>" . htmlspecialchars($col['first_name']) . "</strong>,</p>
        <p>Um novo convite foi gerado para acederes à plataforma " . APP_NAME . " como colaborador de <strong>" . htmlspecialchars($owner_name) . "</strong>.</p>
        <table style='width:100%;border-collapse:collapse;margin:20px 0;background:#f9f9f9;border-radius:8px'>
          <tr><td style='padding:10px 14px;color:#888;font-size:.83rem'>E-mail</td>
              <td style='padding:10px 14px;font-size:.83rem;font-weight:600'>" . htmlspecialchars($col['email_collab']) . "</td></tr>
          <tr style='background:#fff'><td style='padding:10px 14px;color:#888;font-size:.83rem'>Senha temporária</td>
              <td style='padding:10px 14px;font-size:.83rem;font-family:monospace;letter-spacing:2px;color:#FF0089;font-weight:700'>" . $new_password . "</td></tr>
          <tr><td style='padding:10px 14px;color:#888;font-size:.83rem'>Função</td>
              <td style='padding:10px 14px;font-size:.83rem'>" . $role_label . "</td></tr>
          <tr style='background:#fff'><td style='padding:10px 14px;color:#888;font-size:.83rem'>Plano</td>
              <td style='padding:10px 14px;font-size:.83rem'>" . htmlspecialchars($plan_name) . "</td></tr>
        </table>
        <div style='text-align:center;margin:28px 0'>
          <a href='" . $activate_url . "'
             style='background:#FF0089;color:#fff;text-decoration:none;
                    padding:14px 36px;border-radius:8px;font-size:.95rem;
                    font-weight:700;display:inline-block;letter-spacing:.3px'>
            Activar a Minha Conta
          </a>
        </div>
        <div style='background:#fff8fb;border-left:3px solid #FF0089;padding:12px 16px;border-radius:0 6px 6px 0;margin:20px 0'>
          <p style='margin:0;font-size:.82rem;color:#555'>
            ⚠️ Este convite expira em <strong>72 horas</strong>.<br>
            A senha temporária deverá ser alterada no primeiro acesso.
          </p>
        </div>
        <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0'>
        <small style='color:#bbb'>" . APP_NAME . " &mdash; Não reencaminhes este e-mail.</small>
      </div>
    </div>";

    $email_ok = collab_send_email($col['email_collab'], $subject, $body);

    logAudit($admin_id, null, 'collaborator.invite_resent', '_collaborators', $id_collab);

    if ($email_ok) {
        jsonOut(true, 'Convite reenviado com sucesso! Um novo email foi enviado para ' . $col['email_collab'] . '.');
    } else {
        jsonOut(true, 'Convite gerado mas houve um problema com o envio do email. Verifica as configurações de SMTP.', ['warning' => true]);
    }
}

// ══════════════════════════════════════════════
// ACÇÃO: ALTERAR ESTADO
// ══════════════════════════════════════════════
if ($action === 'toggle_collab_status') {

    requirePermission($admin_id, 'users.edit');

    $new_status = trim($_POST['new_status'] ?? '');

    if (!in_array($new_status, ['active', 'blocked', 'inactive'], true)) {
        jsonOut(false, 'Estado inválido.');
    }

    if ($col['status_collab'] === $new_status) {
        $labels = ['active' => 'activo', 'blocked' => 'bloqueado', 'inactive' => 'inactivo'];
        jsonOut(false, 'O colaborador já está ' . ($labels[$new_status] ?? $new_status) . '.');
    }

    $desc_map = [
        'active'   => 'Conta activada/desbloqueada pelo administrador',
        'blocked'  => 'Conta bloqueada pelo administrador',
        'inactive' => 'Conta desactivada pelo administrador',
    ];

    try {
        $db->beginTransaction();

        $db->prepare("UPDATE _collaborators SET status_collab = ? WHERE id_collab = ?")
            ->execute([$new_status, $id_collab]);

        $db->prepare("
            INSERT INTO _collab_activity
                (id_collab, id_users, activity_type, description, ip_address)
            VALUES (?, ?, 'status_changed', ?, ?)
        ")->execute([
            $id_collab,
            $col['id_users'],
            $desc_map[$new_status] ?? 'Estado alterado',
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $db->commit();

        $old_val = json_encode(['status_collab' => $col['status_collab']]);
        $new_val = json_encode(['status_collab' => $new_status]);
        logAudit($admin_id, $col['id_users'], 'collaborator.status_changed', '_collaborators', $id_collab, $old_val, $new_val);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[COLLAB STATUS] ' . $e->getMessage());
        jsonOut(false, 'Erro ao alterar estado. Tenta novamente.');
    }

    $msg_map = [
        'active'   => 'Colaborador desbloqueado com sucesso!',
        'blocked'  => 'Colaborador bloqueado com sucesso!',
        'inactive' => 'Colaborador desactivado com sucesso!',
    ];
    jsonOut(true, $msg_map[$new_status] ?? 'Estado actualizado com sucesso!');
}

// ══════════════════════════════════════════════
// ACÇÃO: ELIMINAR COLABORADOR
// ══════════════════════════════════════════════
if ($action === 'delete_collaborator') {

    requirePermission($admin_id, 'users.edit');

    // Verificar senha do admin logado — buscar da BD
    $admin_row = $db->prepare("SELECT password_employees FROM _employees WHERE id_employees = ?");
    $admin_row->execute([$admin_id]);
    $admin_data = $admin_row->fetch();

    if (!$admin_data) {
        jsonOut(false, 'Erro de sessão. Faz login novamente.');
    }

    $password_confirm = $_POST['password_confirm'] ?? '';
    if (empty($password_confirm) || !password_verify($password_confirm, $admin_data['password_employees'])) {
        jsonOut(false, 'Senha incorrecta. A verificação falhou.');
    }

    // Email de notificação (antes de apagar)
    $notify_subject = 'Acesso removido — ' . APP_NAME;
    $notify_body    = "
    <div style='font-family:\"Segoe UI\",Arial,sans-serif;max-width:540px;margin:auto'>
      <div style='background:#555;padding:24px 32px;border-radius:8px 8px 0 0'>
        <h1 style='color:#fff;margin:0;font-size:1.2rem'>" . APP_NAME . "</h1>
      </div>
      <div style='background:#fff;padding:28px 32px;border:1px solid #eee;border-top:none;border-radius:0 0 8px 8px'>
        <p>Olá <strong>" . htmlspecialchars($col['first_name']) . "</strong>,</p>
        <p>O teu acesso como colaborador na plataforma " . APP_NAME . " foi <strong>removido</strong> por um administrador.</p>
        <p>Se tiveres dúvidas, contacta directamente o gestor da conta.</p>
        <hr style='border:none;border-top:1px solid #f0f0f0;margin:20px 0'>
        <small style='color:#bbb'>" . APP_NAME . " &mdash; Não respondas a este e-mail.</small>
      </div>
    </div>";

    collab_send_email($col['email_collab'], $notify_subject, $notify_body);

    // Guardar dados para audit antes de apagar (ON DELETE CASCADE apaga _collab_activity)
    $audit_old = json_encode([
        'name'         => trim($col['first_name'] . ' ' . ($col['second_name'] ?? '')),
        'email'        => $col['email_collab'],
        'role'         => $col['role_collab'],
        'status'       => $col['status_collab'],
        'owner_id'     => $col['id_users'],
    ]);

    try {
        $db->beginTransaction();

        $db->prepare("DELETE FROM _collaborators WHERE id_collab = ?")
            ->execute([$id_collab]);

        $db->commit();

        logAudit($admin_id, $col['id_users'], 'collaborator.deleted', '_collaborators', $id_collab, $audit_old, null);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[COLLAB DELETE] ' . $e->getMessage());
        jsonOut(false, 'Erro ao eliminar colaborador. Tenta novamente.');
    }

    jsonOut(true, 'Colaborador eliminado com sucesso!');
}

// ── Acção desconhecida ──
jsonOut(false, 'Acção desconhecida.');
