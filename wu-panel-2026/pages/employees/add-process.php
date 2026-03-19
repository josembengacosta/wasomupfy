<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Adicionar Funcionário
// Arquivo: admin/pages/employees/add-process.php
// Rota: admin/employees/add-process  (POST only)
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'employees.edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  adminRedirect('/' . ADMIN_PATH . '/employees/add');
}

// ── CSRF ──
if (!validateAdminCsrf($_POST['csrf_token'] ?? '')) {
  adminRedirect('/' . ADMIN_PATH . '/employees/add', ['err' => 'invalid']);
}
$_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

// ── Sanitizar inputs ──
$first_name  = trim($_POST['first_name']  ?? '');
$second_name = trim($_POST['second_name'] ?? '') ?: null;
$username    = trim($_POST['username']    ?? '');
$email       = strtolower(trim($_POST['email'] ?? ''));
$tel         = trim($_POST['tel']         ?? '') ?: null;
$gender      = in_array($_POST['gender'] ?? '', ['M', 'F']) ? $_POST['gender'] : 'M';
$role        = $_POST['role']   ?? 'editor';
$status      = $_POST['status'] ?? 'processing';
$country     = trim($_POST['country'] ?? '') ?: null;
$city        = trim($_POST['city']    ?? '') ?: null;
$password_raw = $_POST['password'] ?? '';
$send_invite = isset($_POST['send_invite']) && $_POST['send_invite'] === '1';

// ── Validar roles permitidos ──
$allowed_roles = ['editor', 'support'];
if (in_array($admin_role, ['super_admin'])) {
  $allowed_roles = ['super_admin', 'admin', 'editor', 'support'];
}
if (!in_array($role, $allowed_roles)) {
  $role = 'editor'; // fallback seguro
}

// ── Validar status ──
$allowed_statuses = ['active', 'processing'];
if (!in_array($status, $allowed_statuses)) {
  $status = 'processing';
}

// ── Validações obrigatórias ──
$errors_back = [];
if (empty($first_name))   $errors_back[] = 'first_name';
if (empty($username))     $errors_back[] = 'username';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors_back[] = 'email';
if (empty($password_raw)) $errors_back[] = 'password';

// Username: só letras, números, ponto e underscore
if (!empty($username) && !preg_match('/^[a-zA-Z0-9._]{3,60}$/', $username)) {
  $errors_back[] = 'username_format';
}

// Validar força da senha (mínimo 8 chars, maiúscula, minúscula, número)
if (!empty($password_raw) && (
  strlen($password_raw) < 8 ||
  !preg_match('/[A-Z]/', $password_raw) ||
  !preg_match('/[a-z]/', $password_raw) ||
  !preg_match('/[0-9]/', $password_raw)
)) {
  $errors_back[] = 'password_weak';
}

if (!empty($errors_back)) {
  adminRedirect('/' . ADMIN_PATH . '/employees/add', array_merge(['err' => 'invalid'], $_POST));
}

// ── Verificar duplicados ──
$chk = $db->prepare("SELECT id_employees FROM _employees WHERE email_employees = ? LIMIT 1");
$chk->execute([$email]);
if ($chk->fetch()) {
  adminRedirect('/' . ADMIN_PATH . '/employees/add', array_merge(['err' => 'email_exists'], $_POST));
}

$chk2 = $db->prepare("SELECT id_employees FROM _employees WHERE user_employees = ? LIMIT 1");
$chk2->execute([$username]);
if ($chk2->fetch()) {
  adminRedirect('/' . ADMIN_PATH . '/employees/add', array_merge(['err' => 'user_exists'], $_POST));
}

// ── Hash da senha ──
$password_hash = password_hash($password_raw, PASSWORD_BCRYPT, ['cost' => 12]);

// ── Upload de foto ──
$photo_filename = null;
if (!empty($_FILES['photo']['tmp_name'])) {
  $file    = $_FILES['photo'];
  $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
  $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
  $finfo   = new finfo(FILEINFO_MIME_TYPE);
  $mime    = $finfo->file($file['tmp_name']);

  if (in_array($mime, $allowed_mime) && $file['size'] <= 2 * 1024 * 1024) {
    $ext  = $ext_map[$mime];
    $dest_dir = __DIR__ . '/../../../assets/comprovantes/uploads/employees/';
    if (!is_dir($dest_dir)) mkdir($dest_dir, 0755, true);
    $photo_filename = 'emp_new_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dest_dir . $photo_filename)) {
      $photo_filename = null;
    }
  }
}

// ── Gerar chave de recuperação ──
$recovery_key = bin2hex(random_bytes(11)); // 22 chars

// ── Gerar token de convite ──
$invite_token         = null;
$invite_token_expires = null;
if ($send_invite) {
  $invite_token         = bin2hex(random_bytes(32)); // 64 chars hex
  $invite_token_expires = date('Y-m-d H:i:s', time() + 72 * 3600); // 72h
}

// ── Inserir na BD — transacção ──
try {
  $db->beginTransaction();

  // 1. _employees
  $ins = $db->prepare("
        INSERT INTO _employees
            (first_name, second_name, user_employees, gender, tel_employees,
             email_employees, password_employees, role, status_employees,
             photo_employees)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
  $ins->execute([
    $first_name,
    $second_name,
    $username,
    $gender,
    $tel,
    $email,
    $password_hash,
    $role,
    $status,
    $photo_filename,
  ]);
  $new_id = (int)$db->lastInsertId();

  // Renomear foto com o ID real
  if ($photo_filename) {
    $dest_dir   = __DIR__ . '/../../../assets/comprovantes/uploads/employees/';
    $new_filename = 'emp_' . $new_id . '_' . time() . '.' . pathinfo($photo_filename, PATHINFO_EXTENSION);
    if (rename($dest_dir . $photo_filename, $dest_dir . $new_filename)) {
      $db->prepare("UPDATE _employees SET photo_employees=? WHERE id_employees=?")
        ->execute([$new_filename, $new_id]);
      $photo_filename = $new_filename;
    }
  }

  // 2. _employees_security
  /*
    */
  $ins_sec = $db->prepare("
        INSERT INTO _employees_security
            (id_employees, recovery_key, invite_token, invite_token_expires, invite_used)
        VALUES (?, ?, ?, ?, 0)
    ");
  $ins_sec->execute([$new_id, $recovery_key, $invite_token, $invite_token_expires, $admin_id]);

  $db->commit();
} catch (Exception $e) {
  $db->rollBack();
  // Apagar foto se foi criada
  if ($photo_filename) {
    $dest_dir = __DIR__ . '/../../../assets/comprovantes/uploads/employees/';
    if (file_exists($dest_dir . $photo_filename)) @unlink($dest_dir . $photo_filename);
  }
  adminRedirect('/' . ADMIN_PATH . '/employees/add', ['err' => 'error']);
}

// ── Log de auditoria ──
logAudit(
  $admin_id,
  null,
  'employees.created',
  'employees',
  $new_id,
  null,
  ['role' => $role, 'status' => $status, 'email' => $email]
);

// ── Enviar e-mail de convite ──
$email_sent = true;
if ($send_invite && $invite_token) {
  $fullname     = trim($first_name . ' ' . ($second_name ?? ''));

  // URL do botão de activação — vai para admin/auth/invite-accept?t=TOKEN
  // O utilizador só vê um botão no email, não o URL directamente
  $invite_url   = APP_URL . '/' . ADMIN_PATH . '/invite/accept?t=' . urlencode($invite_token);

  // Role label para o email
  $role_labels  = [
    'super_admin' => 'Super Administrador',
    'admin'       => 'Administrador',
    'editor'      => 'Editor',
    'support'     => 'Suporte',
  ];
  $role_label = $role_labels[$role] ?? ucfirst($role);

  // Status label
  $status_label = $status === 'active' ? 'Activa — podes aceder imediatamente' : 'Em processo — aguarda activação pelo administrador';

  // Construir email HTML
  $subject = 'Convite para a equipa Wasom Upfy — As tuas credenciais de acesso';

  $html_body = '<!DOCTYPE html>
<html lang="pt-ao">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Convite Wasom Upfy</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f8;font-family:\'Poppins\',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f8;padding:32px 16px">
  <tr>
    <td align="center">
      <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px">

        <!-- Header -->
        <tr>
          <td align="center" style="background:linear-gradient(135deg,#FF0089,#6c63ff);border-radius:16px 16px 0 0;padding:32px 24px">
            <img src="' . APP_URL . '/assets/img/brand/wasomupfy_brand.png"
                 alt="Wasom Upfy" width="52" height="52"
                 style="border-radius:50%;border:3px solid rgba(255,255,255,.3);margin-bottom:12px;display:block;margin:0 auto 12px"/>
            <h1 style="color:#fff;font-size:1.3rem;margin:0;font-weight:700">Bem-vindo à equipa!</h1>
            <p style="color:rgba(255,255,255,.8);margin:6px 0 0;font-size:.88rem">
              Foste convidado para o painel de administração
            </p>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="background:#fff;padding:28px 28px 24px">

            <p style="font-size:.95rem;color:#111;margin:0 0 6px">
              Olá, <strong>' . htmlspecialchars($fullname) . '</strong>!
            </p>
            <p style="font-size:.84rem;color:#555;line-height:1.6;margin:0 0 20px">
              <strong>' . htmlspecialchars(getRoleLabel($admin_role)) . ' ' . htmlspecialchars($admin_fullname) . '</strong>
              convidou-te para fazer parte da equipa <strong>Wasom Upfy</strong> como
              <strong>' . htmlspecialchars($role_label) . '</strong>.
              Abaixo encontras as tuas credenciais de acesso ao painel.
            </p>

            <!-- Credenciais -->
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:#f8f7fc;border-radius:10px;border:1px solid #e8e8f0;margin-bottom:20px">
              <tr>
                <td style="padding:16px 18px">
                  <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="font-size:.78rem;color:#888;padding:6px 0 2px;border-bottom:1px solid #eee">USERNAME</td>
                      <td style="font-size:.78rem;color:#888;padding:6px 0 2px;border-bottom:1px solid #eee;text-align:right">CARGO</td>
                    </tr>
                    <tr>
                      <td style="font-size:.92rem;font-weight:700;color:#111;padding:4px 0 10px;border-bottom:1px solid #eee">
                        @' . htmlspecialchars($username) . '
                      </td>
                      <td style="font-size:.92rem;font-weight:700;color:#FF0089;padding:4px 0 10px;border-bottom:1px solid #eee;text-align:right">
                        ' . htmlspecialchars($role_label) . '
                      </td>
                    </tr>
                    <tr>
                      <td style="font-size:.78rem;color:#888;padding:10px 0 2px">E-MAIL</td>
                    </tr>
                    <tr>
                      <td colspan="2" style="font-size:.88rem;font-weight:600;color:#111;padding:2px 0 10px;border-bottom:1px solid #eee">
                        ' . htmlspecialchars($email) . '
                      </td>
                    </tr>
                    <tr>
                      <td style="font-size:.78rem;color:#888;padding:10px 0 2px">SENHA TEMPORÁRIA</td>
                    </tr>
                    <tr>
                      <td colspan="2" style="font-family:monospace;font-size:1rem;font-weight:700;color:#FF0089;padding:2px 0 10px;letter-spacing:1px;border-bottom:1px solid #eee">
                        ' . htmlspecialchars($password_raw) . '
                      </td>
                    </tr>
                    <tr>
                      <td style="font-size:.78rem;color:#888;padding:10px 0 2px">ESTADO DA CONTA</td>
                    </tr>
                    <tr>
                      <td colspan="2" style="font-size:.84rem;color:#555;padding:2px 0">
                        ' . htmlspecialchars($status_label) . '
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- Botão de activação -->
            <p style="font-size:.84rem;color:#555;margin:0 0 14px;line-height:1.5">
              Para activar a tua conta e definir uma senha definitiva, clica no botão abaixo.
              Este botão expira em <strong>72 horas</strong> e só pode ser utilizado <strong>uma vez</strong>.
            </p>

            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px">
              <tr>
                <td align="center">
                  <a href="' . htmlspecialchars($invite_url) . '"
                     style="display:inline-block;background:linear-gradient(135deg,#FF0089,#cc006e);
                            color:#fff;font-weight:700;font-size:.9rem;padding:14px 36px;
                            border-radius:50px;text-decoration:none;letter-spacing:.3px">
                    Activar a Minha Conta
                  </a>
                </td>
              </tr>
            </table>

            <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:12px 14px;margin-bottom:16px">
              <p style="margin:0;font-size:.78rem;color:#7c4800;line-height:1.5">
                <strong>⚠ Segurança:</strong>
                Não partilhes esta senha com ninguém. A senha acima é temporária e deverás alterá-la
                após o primeiro acesso. Se não reconheces este convite, ignora este e-mail.
              </p>
            </div>

          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f8f7fc;border-radius:0 0 16px 16px;padding:16px 24px;text-align:center;
                     border-top:1px solid #e8e8f0">
            <p style="margin:0;font-size:.75rem;color:#aaa;line-height:1.5">
              Este e-mail foi enviado pela plataforma <strong>Wasom Upfy</strong>.<br/>
              © 2026 Wasom Upfy · Todos os direitos reservados.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>';

  // Enviar via WasomMailer
  try {
    $mailer_path = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';

    if (!file_exists($mailer_path)) {
      error_log('[ADMIN MAILER] WasomMailer.php não encontrado em: ' . $mailer_path);
      $email_sent = false;
    } else {
      if (!class_exists('\Wasom\Mailer')) {
        require_once $mailer_path;
      }

      $wm = new \Wasom\Mailer();
      $wm->host     = MAIL_HOST;
      $wm->port     = MAIL_PORT;
      $wm->secure   = defined('MAIL_SECURE') ? MAIL_SECURE : 'tls';
      $wm->username = MAIL_USER;
      $wm->password = MAIL_PASS;
      $wm->debug    = defined('MAIL_DEBUG') ? MAIL_DEBUG : 0;
      $wm->setFrom(MAIL_FROM, MAIL_FROM_NAME)
        ->addAddress($email, $fullname)
        ->setSubject($subject)
        ->setBody($html_body, strip_tags($html_body));
      $wm->send();
      $email_sent = true;
    }
  } catch (\Wasom\MailerException $e) {
    error_log('[ADMIN MAILER] Falha ao enviar convite para ' . $email . ': ' . $e->getMessage());
    $email_sent = false;
  } catch (Exception $e) {
    error_log('[ADMIN MAILER] Erro inesperado: ' . $e->getMessage());
    $email_sent = false;
  }
}

// ── Redirecionar ──
if ($send_invite && !$email_sent) {
  // Conta criada mas email falhou
  adminRedirect('/' . ADMIN_PATH . '/employees', ['msg' => 'added', 'warn' => 'email_fail']);
} else {
  adminRedirect('/' . ADMIN_PATH . '/employees', ['msg' => 'added']);
}
