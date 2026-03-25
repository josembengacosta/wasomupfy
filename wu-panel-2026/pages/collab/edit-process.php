<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Edição de Colaborador
// Arquivo: wu-panel-2026/pages/collab/edit-process.php
// Rota:    wu-panel-2026/collab/edit-process (POST only)
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'users.edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/' . ADMIN_PATH . '/collab');
}

// ── CSRF ──
if (!isset($_POST['csrf_token']) || !hash_equals(
    ($_SESSION['admin_csrf_token'] ?? ''),
    $_POST['csrf_token']
)) {
    adminRedirect('/' . ADMIN_PATH . '/collab');
}

$id = (int)($_POST['id_collab'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/collab');

// ── Helper: redirect de volta com parâmetros ──
function redirectBack(string $base, array $params = []): never
{
    $sep = str_contains($base, '?') ? '&' : '?';
    $qs  = $params ? $sep . http_build_query($params) : '';
    header('Location: ' . APP_URL . $base . $qs);
    exit;
}

// ── Buscar colaborador ──
$stmt = $db->prepare("SELECT * FROM _collaborators WHERE id_collab = ?");
$stmt->execute([$id]);
$collab = $stmt->fetch();

if (!$collab) {
    adminRedirect('/' . ADMIN_PATH . '/collab?msg=not_found');
}

$back = '/' . ADMIN_PATH . '/collab/edit?id=' . $id;
$action = trim($_POST['action'] ?? '');

// ══════════════════════════════════════════════
// ACÇÃO: ACTUALIZAR PERFIL
// ══════════════════════════════════════════════
if ($action === 'update_profile') {

    $first_name   = trim($_POST['first_name']   ?? '');
    $second_name  = trim($_POST['second_name']  ?? '');
    $user_collab  = trim($_POST['user_collab']  ?? '');
    $email_collab = trim($_POST['email_collab'] ?? '');
    $tel_collab   = trim($_POST['tel_collab']   ?? '');
    $role_collab  = trim($_POST['role_collab']  ?? 'editor');
    $status_collab = trim($_POST['status_collab'] ?? 'active');
    $notes        = trim($_POST['notes']        ?? '');
    $photo_collab = trim($_POST['photo_collab'] ?? '');

    // Validação básica
    if (empty($first_name) || empty($email_collab) || empty($user_collab)) {
        redirectBack($back, ['msg' => 'error', 'tab' => 'profile']);
    }
    if (!filter_var($email_collab, FILTER_VALIDATE_EMAIL)) {
        redirectBack($back, ['msg' => 'error', 'tab' => 'profile']);
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $user_collab)) {
        redirectBack($back, ['msg' => 'error', 'tab' => 'profile']);
    }
    if (!in_array($role_collab,   ['admin', 'editor', 'analyst', 'support'], true)) $role_collab = 'editor';
    if (!in_array($status_collab, ['active', 'pending', 'blocked', 'inactive'], true)) $status_collab = 'active';

    // Verificar unicidade de email
    $dup_email = $db->prepare("SELECT id_collab FROM _collaborators WHERE email_collab = ? AND id_collab != ?");
    $dup_email->execute([$email_collab, $id]);
    if ($dup_email->fetchColumn()) {
        redirectBack($back, ['msg' => 'dupe_email', 'tab' => 'profile']);
    }

    // Verificar unicidade de username
    $dup_user = $db->prepare("SELECT id_collab FROM _collaborators WHERE user_collab = ? AND id_collab != ?");
    $dup_user->execute([$user_collab, $id]);
    if ($dup_user->fetchColumn()) {
        redirectBack($back, ['msg' => 'dupe_user', 'tab' => 'profile']);
    }

    // Construir old_value para audit
    $old_val = json_encode([
        'first_name'   => $collab['first_name'],
        'second_name'  => $collab['second_name'],
        'user_collab'  => $collab['user_collab'],
        'email_collab' => $collab['email_collab'],
        'role_collab'  => $collab['role_collab'],
        'status_collab' => $collab['status_collab'],
    ]);

    try {
        $db->beginTransaction();

        $db->prepare("
            UPDATE _collaborators SET
                first_name    = ?,
                second_name   = ?,
                user_collab   = ?,
                email_collab  = ?,
                tel_collab    = ?,
                role_collab   = ?,
                status_collab = ?,
                notes         = ?,
                photo_collab  = ?
            WHERE id_collab = ?
        ")->execute([
            $first_name,
            $second_name ?: null,
            $user_collab,
            $email_collab,
            $tel_collab  ?: null,
            $role_collab,
            $status_collab,
            $notes       ?: null,
            $photo_collab ?: null,
            $id,
        ]);

        $db->prepare("
            INSERT INTO _collab_activity
                (id_collab, id_users, activity_type, description, ip_address)
            VALUES (?, ?, 'profile_updated', 'Perfil actualizado pelo administrador', ?)
        ")->execute([$id, $collab['id_users'], $_SERVER['REMOTE_ADDR'] ?? null]);

        $db->commit();

        $new_val = json_encode([
            'first_name'   => $first_name,
            'second_name'  => $second_name,
            'user_collab'  => $user_collab,
            'email_collab' => $email_collab,
            'role_collab'  => $role_collab,
            'status_collab' => $status_collab,
        ]);
        logAudit($admin_id, $collab['id_users'], 'collaborator.updated', '_collaborators', $id, $old_val, $new_val);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[COLLAB EDIT] ' . $e->getMessage());
        redirectBack($back, ['msg' => 'error', 'tab' => 'profile']);
    }

    redirectBack('/' . ADMIN_PATH . '/collab/edit?id=' . $id, ['msg' => 'updated', 'tab' => 'profile']);
}

// ══════════════════════════════════════════════
// ACÇÃO: GERAR SENHA TEMPORÁRIA
// ══════════════════════════════════════════════
if ($action === 'reset_password') {

    $new_password = trim($_POST['new_password'] ?? '');
    $send_email   = !empty($_POST['send_email']);

    // Validar requisitos mínimos
    if (
        strlen($new_password) < 12 ||
        !preg_match('/[A-Z]/', $new_password) ||
        !preg_match('/[a-z]/', $new_password) ||
        !preg_match('/[0-9]/', $new_password) ||
        !preg_match('/[@#$%&*!?^()_\-+=]/', $new_password)
    ) {
        redirectBack($back, ['msg' => 'error', 'tab' => 'security']);
    }

    $new_hash = password_hash($new_password, PASSWORD_DEFAULT, ['cost' => 12]);

    try {
        $db->beginTransaction();

        $db->prepare("
            UPDATE _collaborators
            SET password_collab      = ?,
                must_change_password = 1
            WHERE id_collab = ?
        ")->execute([$new_hash, $id]);

        // Invalidar sessões activas
        $db->prepare("UPDATE _collab_sessions SET is_active = 0 WHERE id_collab = ?")
            ->execute([$id]);

        $db->prepare("
            INSERT INTO _collab_activity
                (id_collab, id_users, activity_type, description, ip_address)
            VALUES (?, ?, 'password_reset', 'Senha temporária definida pelo administrador', ?)
        ")->execute([$id, $collab['id_users'], $_SERVER['REMOTE_ADDR'] ?? null]);

        $db->commit();

        logAudit($admin_id, $collab['id_users'], 'collaborator.password_reset', '_collaborators', $id);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[COLLAB PW RESET] ' . $e->getMessage());
        redirectBack($back, ['msg' => 'error', 'tab' => 'security']);
    }

    // Enviar email se solicitado
    if ($send_email) {
        $mailer_path = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';
        if (file_exists($mailer_path)) {
            if (!class_exists('\Wasom\Mailer')) require_once $mailer_path;
            try {
                $fullname = trim($collab['first_name'] . ' ' . ($collab['second_name'] ?? ''));
                $subject  = 'Nova senha temporária — ' . APP_NAME;
                $body     = "
                <div style='font-family:\"Segoe UI\",Arial,sans-serif;max-width:540px;margin:auto'>
                  <div style='background:linear-gradient(135deg,#FF0089,#6c63ff);padding:28px 32px;border-radius:10px 10px 0 0;text-align:center'>
                    <div style='display:inline-block;background:rgba(255,255,255,.15);border:3px solid rgba(255,255,255,.3);
                                border-radius:50%;width:52px;height:52px;line-height:52px;text-align:center;
                                font-size:1.1rem;font-weight:800;color:#fff;margin-bottom:10px'>WU</div>
                    <h1 style='color:#fff;font-size:1.2rem;margin:0;font-weight:700'>Nova senha temporária</h1>
                  </div>
                  <div style='background:#fff;padding:32px;border:1px solid #eee;border-top:none;border-radius:0 0 10px 10px'>
                    <p>Olá <strong>" . htmlspecialchars($fullname) . "</strong>,</p>
                    <p>Um administrador definiu uma nova senha temporária para a tua conta de colaborador.</p>
                    <table style='width:100%;border-collapse:collapse;margin:20px 0;background:#f9f9f9;border-radius:8px'>
                      <tr>
                        <td style='padding:10px 14px;color:#888;font-size:.83rem'>E-mail</td>
                        <td style='padding:10px 14px;font-size:.83rem;font-weight:600'>" . htmlspecialchars($collab['email_collab']) . "</td>
                      </tr>
                      <tr style='background:#fff'>
                        <td style='padding:10px 14px;color:#888;font-size:.83rem'>Senha temporária</td>
                        <td style='padding:10px 14px;font-size:.83rem;font-family:monospace;letter-spacing:2px;color:#FF0089;font-weight:700'>" . htmlspecialchars($new_password) . "</td>
                      </tr>
                    </table>
                    <div style='background:#fff8fb;border-left:3px solid #FF0089;padding:12px 16px;border-radius:0 6px 6px 0;margin:20px 0'>
                      <p style='margin:0;font-size:.82rem;color:#555'>
                        ⚠️ Deverás alterar esta senha no próximo acesso.<br>
                        Não partilhes esta mensagem com ninguém.
                      </p>
                    </div>
                    <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0'>
                    <small style='color:#bbb'>" . APP_NAME . " &mdash; Não respondas a este e-mail.</small>
                  </div>
                </div>";

                $wm = new \Wasom\Mailer();
                $wm->host     = MAIL_HOST;
                $wm->port     = MAIL_PORT;
                $wm->secure   = defined('MAIL_SECURE') ? MAIL_SECURE : 'tls';
                $wm->username = MAIL_USER;
                $wm->password = MAIL_PASS;
                $wm->debug    = 0;
                $wm->setFrom(MAIL_FROM, MAIL_FROM_NAME)
                    ->addAddress($collab['email_collab'], $fullname)
                    ->setSubject($subject)
                    ->setBody($body, strip_tags($body));
                $wm->send();
            } catch (\Wasom\MailerException $e) {
                error_log('[COLLAB MAIL PW] ' . $e->getMessage());
            }
        }
    }

    redirectBack($back, ['msg' => 'pw_reset', 'tab' => 'security']);
}

// ══════════════════════════════════════════════
// ACÇÃO: REVOGAR SESSÕES
// ══════════════════════════════════════════════
if ($action === 'revoke_sessions') {

    try {
        $db->prepare("UPDATE _collab_sessions SET is_active = 0 WHERE id_collab = ?")
            ->execute([$id]);

        $db->prepare("
            INSERT INTO _collab_activity
                (id_collab, id_users, activity_type, description, ip_address)
            VALUES (?, ?, 'sessions_revoked', 'Todas as sessões terminadas pelo administrador', ?)
        ")->execute([$id, $collab['id_users'], $_SERVER['REMOTE_ADDR'] ?? null]);

        logAudit($admin_id, $collab['id_users'], 'collaborator.sessions_revoked', '_collaborators', $id);
    } catch (Exception $e) {
        error_log('[COLLAB REVOKE] ' . $e->getMessage());
        redirectBack($back, ['msg' => 'error', 'tab' => 'security']);
    }

    redirectBack($back, ['msg' => 'updated', 'tab' => 'security']);
}

// ── Acção desconhecida ──
adminRedirect('/' . ADMIN_PATH . '/collab');
