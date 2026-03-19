<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Edição de Funcionário
// Arquivo: admin/pages/employees/edit-process.php
// Rota:    admin/employees/edit-process (POST only)
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'employees.edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/' . ADMIN_PATH . '/employees');
}

if (!validateAdminCsrf($_POST['csrf_token'] ?? '')) {
    adminRedirect('/' . ADMIN_PATH . '/employees', ['msg' => 'error']);
}
$_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);

if (!$id) adminRedirect('/' . ADMIN_PATH . '/employees');

// Verificar que o funcionário existe
$chk = $db->prepare("SELECT id_employees, role, photo_employees, email_employees FROM _employees WHERE id_employees=? LIMIT 1");
$chk->execute([$id]);
$target = $chk->fetch();
if (!$target) adminRedirect('/' . ADMIN_PATH . '/employees');

// Protecção: não pode editar super_admin se não for super_admin
if ($target['role'] === 'super_admin' && $admin_role !== 'super_admin') {
    adminRedirect('/' . ADMIN_PATH . '/employees');
}

// Não pode editar a si próprio por aqui
if ($id === $admin_id) {
    adminRedirect('/' . ADMIN_PATH . '/profile');
}

$back     = '/' . ADMIN_PATH . '/employees/edit?id=' . $id;

// Helper local: redirecionar para a página de edição mantendo o ?id=
// (adminRedirect sobrescreveria o ? existente com os novos params)
function redirectBack(string $back, array $params = []): never {
    $url = APP_URL . '/' . ltrim($back, '/');
    if ($params) $url .= '&' . http_build_query($params);
    header('Location: ' . $url);
    exit;
}

// ════════════════════════════════════════════
switch ($action) {

    // ── Actualizar perfil ────────────────────
    case 'update_profile':
        $first_name  = trim($_POST['first_name']  ?? '');
        $second_name = trim($_POST['second_name'] ?? '') ?: null;
        $username    = trim($_POST['username']    ?? '');
        $email       = strtolower(trim($_POST['email'] ?? ''));
        $email_other = strtolower(trim($_POST['email_other'] ?? '')) ?: null;
        $tel         = trim($_POST['tel']         ?? '') ?: null;
        $url         = trim($_POST['url']         ?? '') ?: null;
        $about       = trim($_POST['about']       ?? '') ?: null;
        $country     = trim($_POST['country']    ?? '') ?: null;
        $city        = trim($_POST['city']       ?? '') ?: null;
        $gender      = in_array($_POST['gender'] ?? '', ['M','F']) ? $_POST['gender'] : 'M';
        $role        = $_POST['role']   ?? $target['role'];
        $status      = $_POST['status'] ?? 'active';

        // Validar role permitido
        $allowed_roles = ['editor', 'support'];
        if ($admin_role === 'super_admin') $allowed_roles = ['super_admin','admin','editor','support'];
        if (!in_array($role, $allowed_roles)) $role = $target['role'];

        // Validar status
        $allowed_statuses = ['active','processing','blocked','suspended','inactive'];
        if (!in_array($status, $allowed_statuses)) $status = 'active';

        // Validações obrigatórias
        if (empty($first_name) || empty($username) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectBack($back, ['msg' => 'error', 'tab' => 'profile']);
        }

        // Verificar duplicados de email (excluindo o próprio)
        $chk_email = $db->prepare("SELECT id_employees FROM _employees WHERE email_employees=? AND id_employees!=? LIMIT 1");
        $chk_email->execute([$email, $id]);
        if ($chk_email->fetch()) {
            redirectBack($back, ['msg' => 'email_exists', 'tab' => 'profile']);
        }

        $chk_user = $db->prepare("SELECT id_employees FROM _employees WHERE user_employees=? AND id_employees!=? LIMIT 1");
        $chk_user->execute([$username, $id]);
        if ($chk_user->fetch()) {
            redirectBack($back, ['msg' => 'user_exists', 'tab' => 'profile']);
        }

        // Upload de foto
        $photo_filename = $target['photo_employees'];
        if (!empty($_FILES['photo']['tmp_name'])) {
            $file = $_FILES['photo'];
            $allowed_mime = ['image/jpeg','image/png','image/webp'];
            $ext_map      = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);

            if (in_array($mime, $allowed_mime) && $file['size'] <= 2*1024*1024) {
                $dest_dir = __DIR__ . '/../../../assets/comprovantes/uploads/employees/';
                if (!is_dir($dest_dir)) mkdir($dest_dir, 0755, true);
                $new_fn = 'emp_' . $id . '_' . time() . '.' . $ext_map[$mime];
                if (move_uploaded_file($file['tmp_name'], $dest_dir . $new_fn)) {
                    // Apagar foto antiga
                    if ($photo_filename && file_exists($dest_dir . $photo_filename)) {
                        @unlink($dest_dir . $photo_filename);
                    }
                    $photo_filename = $new_fn;
                }
            }
        }

        $db->prepare("
            UPDATE _employees SET
                first_name=?, second_name=?, user_employees=?, gender=?,
                tel_employees=?, email_employees=?, email_employees_other=?,
                url_employees=?, about_employees=?, photo_employees=?,
                country_employees=?, city_employees=?,
                role=?, status_employees=?
            WHERE id_employees=?
        ")->execute([
            $first_name, $second_name, $username, $gender,
            $tel, $email, $email_other,
            $url, $about, $photo_filename,
            $country, $city,
            $role, $status,
            $id
        ]);

        logAudit($admin_id, null, 'employees.updated', 'employees', $id,
            ['role' => $target['role'], 'status' => $target['role']],
            ['role' => $role, 'status' => $status]);

        redirectBack($back, ['msg' => 'updated', 'tab' => 'profile']);

    // ── Remover foto ────────────────────────
    case 'remove_photo':
        if ($target['photo_employees']) {
            $dest_dir = __DIR__ . '/../../../assets/comprovantes/uploads/employees/';
            if (file_exists($dest_dir . $target['photo_employees'])) {
                @unlink($dest_dir . $target['photo_employees']);
            }
            $db->prepare("UPDATE _employees SET photo_employees=NULL WHERE id_employees=?")
               ->execute([$id]);
        }
        redirectBack($back, ['msg' => 'updated', 'tab' => 'profile']);

    // ── Redefinir senha ──────────────────────
    case 'reset_password':
        $new_pw = $_POST['new_password'] ?? '';

        $pw_ok = strlen($new_pw) >= 8
              && preg_match('/[A-Z]/', $new_pw)
              && preg_match('/[a-z]/', $new_pw)
              && preg_match('/[0-9]/', $new_pw);

        if (!$pw_ok) {
            redirectBack($back, ['msg' => 'error', 'tab' => 'security']);
        }

        $new_hash = password_hash($new_pw, PASSWORD_BCRYPT, ['cost' => 12]);

        $db->prepare("UPDATE _employees SET password_employees=? WHERE id_employees=?")
           ->execute([$new_hash, $id]);

        // Invalidar remember tokens
        $db->prepare("UPDATE _employees_security SET remember_token=NULL WHERE id_employees=?")
           ->execute([$id]);

        // Enviar email com nova senha
        $emp_data = $db->prepare("SELECT first_name, second_name, email_employees, user_employees FROM _employees WHERE id_employees=? LIMIT 1");
        $emp_data->execute([$id]);
        $emp_row = $emp_data->fetch();

        if ($emp_row) {
            $fullname_emp = trim($emp_row['first_name'] . ' ' . ($emp_row['second_name'] ?? ''));
            $mailer_path  = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';
            if (file_exists($mailer_path)) {
                if (!class_exists('\Wasom\Mailer')) require_once $mailer_path;
                try {
                    $subj = 'A tua senha foi redefinida — Wasom Upfy';
                    $body = '<!DOCTYPE html><html lang="pt-ao"><head><meta charset="utf-8"/></head>
<body style="margin:0;padding:0;background:#f4f4f8;font-family:Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f8;padding:32px 16px">
  <tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px">
      <tr>
        <td style="background:linear-gradient(135deg,#FF0089,#6c63ff);border-radius:16px 16px 0 0;padding:28px;text-align:center">
          <div style="display:inline-block;background:rgba(255,255,255,.15);border:3px solid rgba(255,255,255,.3);border-radius:50%;width:52px;height:52px;line-height:52px;font-size:1rem;font-weight:800;color:#fff;margin-bottom:10px">WU</div>
          <h2 style="color:#fff;margin:0;font-size:1.1rem">Senha Redefinida</h2>
        </td>
      </tr>
      <tr>
        <td style="background:#fff;padding:28px;border:1px solid #eee;border-top:none;border-radius:0 0 16px 16px">
          <p style="color:#111;margin:0 0 12px;font-size:.9rem">Olá, <strong>' . htmlspecialchars($fullname_emp) . '</strong>!</p>
          <p style="color:#555;font-size:.84rem;line-height:1.6;margin:0 0 20px">
            A tua senha de acesso ao painel foi redefinida por um administrador.
            Usa as credenciais abaixo para iniciar sessão.
          </p>
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f7fc;border-radius:10px;border:1px solid #e8e8f0;margin-bottom:20px">
            <tr><td style="padding:16px 18px">
              <div style="font-size:.75rem;color:#aaa;margin-bottom:4px">USERNAME</div>
              <div style="font-size:.92rem;font-weight:700;color:#111;margin-bottom:12px;border-bottom:1px solid #eee;padding-bottom:12px">@' . htmlspecialchars($emp_row['user_employees'] ?? '') . '</div>
              <div style="font-size:.75rem;color:#aaa;margin-bottom:4px">NOVA SENHA TEMPORÁRIA</div>
              <div style="font-family:monospace;font-size:1.05rem;font-weight:700;color:#FF0089;letter-spacing:1px">' . htmlspecialchars($new_pw) . '</div>
            </td></tr>
          </table>
          <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:12px;font-size:.78rem;color:#7c4800">
            ⚠ Altera esta senha imediatamente após o login. Não partilhes com ninguém.
          </div>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body></html>';
                    $wm = new \Wasom\Mailer();
                    $wm->host=$_SERVER['MAIL_HOST']??MAIL_HOST;
                    $wm->port=MAIL_PORT; $wm->secure=defined('MAIL_SECURE')?MAIL_SECURE:'tls';
                    $wm->username=MAIL_USER; $wm->password=MAIL_PASS;
                    $wm->debug=defined('MAIL_DEBUG')?MAIL_DEBUG:0;
                    $wm->setFrom(MAIL_FROM, MAIL_FROM_NAME)
                       ->addAddress($emp_row['email_employees'], $fullname_emp)
                       ->setSubject($subj)->setBody($body, strip_tags($body));
                    $wm->send();
                } catch (Exception $e) {
                    error_log('[EDIT PW RESET] ' . $e->getMessage());
                }
            }
        }

        logAudit($admin_id, null, 'employees.password_reset', 'employees', $id, null, null);
        redirectBack($back, ['msg' => 'pw_reset', 'tab' => 'security']);

    // ── Limpar tentativas de login ───────────
    case 'clear_attempts':
        $db->prepare("UPDATE _employees_security SET login_attempts=0, block_until=NULL WHERE id_employees=?")
           ->execute([$id]);
        logAudit($admin_id, null, 'employees.attempts_cleared', 'employees', $id, null, null);
        redirectBack($back, ['msg' => 'updated', 'tab' => 'security']);

    // ── Revogar sessões ─────────────────────
    case 'revoke_sessions':
        $db->prepare("UPDATE _employees_security SET remember_token=NULL WHERE id_employees=?")
           ->execute([$id]);
        logAudit($admin_id, null, 'employees.sessions_revoked', 'employees', $id, null, null);
        redirectBack($back, ['msg' => 'updated', 'tab' => 'security']);

    // ── Actualizar permissões ────────────────
    case 'update_permissions':
        $perm_input = $_POST['perm'] ?? [];

        // Apagar todas as permissões explícitas actuais
        $db->prepare("DELETE FROM _employees_permissions WHERE id_employees=?")
           ->execute([$id]);

        // Inserir novas (só as que não são "padrão")
        $ins = $db->prepare("
            INSERT INTO _employees_permissions (id_employees, permission, granted)
            VALUES (?, ?, ?)
        ");

        foreach ($perm_input as $perm => $val) {
            if ($val === '') continue; // padrão — não inserir
            $granted = $val === '1' ? 1 : 0;
            // Sanitizar o nome da permissão
            if (!preg_match('/^[a-z_]+\.[a-z_]+$/', $perm)) continue;
            $ins->execute([$id, $perm, $granted]);
        }

        logAudit($admin_id, null, 'employees.permissions_updated', 'employees', $id,
            null, ['count' => count($perm_input)]);

        redirectBack($back, ['msg' => 'perms', 'tab' => 'permissions']);

    default:
        redirectBack($back);
}