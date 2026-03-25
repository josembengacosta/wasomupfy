<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Adicionar Utilizador
// Arquivo: admin/pages/users/add-process.php
// Rota: admin/users/add-process (POST only)
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'users.edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/' . ADMIN_PATH . '/users/add');
}

// ── CSRF ──
if (!validateAdminCsrf($_POST['csrf_token'] ?? '')) {
    adminRedirect('/' . ADMIN_PATH . '/users/add', ['err' => 'invalid']);
}
$_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

// ── Sanitizar inputs ──
$first_name   = trim($_POST['first_name'] ?? '');
$second_name  = trim($_POST['second_name'] ?? '') ?: null;
$username     = trim($_POST['username'] ?? '');
$email        = strtolower(trim($_POST['email'] ?? ''));
$tel          = trim($_POST['tel'] ?? '') ?: null;
$gender       = in_array($_POST['gender'] ?? '', ['M', 'F', 'Outro']) ? $_POST['gender'] : null;
$birth_date   = trim($_POST['birth_date'] ?? '') ?: null;
$country      = trim($_POST['country'] ?? '') ?: null;
$city         = trim($_POST['city'] ?? '') ?: null;
$plan_selected = (int)($_POST['plan'] ?? 0) ?: null;
$status       = $_POST['status'] ?? 'active';
$about        = trim($_POST['about'] ?? '') ?: null;
$password_raw = $_POST['password'] ?? '';
$send_invite  = isset($_POST['send_invite']) && $_POST['send_invite'] === '1';

// ── Validar data de nascimento ──
if ($birth_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
    adminRedirect('/' . ADMIN_PATH . '/users/add', array_merge(['err' => 'invalid'], $_POST));
}
// Verificar idade mínima (13 anos)
if ($birth_date) {
    $age = date_diff(date_create($birth_date), date_create('today'))->y;
    if ($age < 13) {
        adminRedirect('/' . ADMIN_PATH . '/users/add', array_merge(['err' => 'invalid'], $_POST));
    }
}

// ── Notificações ──
$notif_email    = isset($_POST['notif_email']) ? 1 : 0;
$notif_push     = isset($_POST['notif_push']) ? 1 : 0;
$notif_weekly   = isset($_POST['notif_weekly']) ? 1 : 0;
$notif_releases = isset($_POST['notif_releases']) ? 1 : 0;
$notif_payments = 1; // Padrão activado para pagamentos

// ── Validar status ──
$allowed_statuses = ['active', 'suspended', 'blocked', 'inactive', 'pending_plan'];
if (!in_array($status, $allowed_statuses)) {
    $status = 'active';
}

// ── Validações obrigatórias ──
$errors_back = [];

if (empty($first_name))   $errors_back[] = 'first_name';
if (empty($username))     $errors_back[] = 'username';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors_back[] = 'email';
if (empty($password_raw)) $errors_back[] = 'password';

// Username: só letras, números, ponto e underscore (min 3, max 60)
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
    adminRedirect('/' . ADMIN_PATH . '/users/add', array_merge(['err' => 'invalid'], $_POST));
}

// ── Verificar duplicados (email e username) ──
$chk = $db->prepare("SELECT id_users FROM _users WHERE email_user = ? LIMIT 1");
$chk->execute([$email]);
if ($chk->fetch()) {
    adminRedirect('/' . ADMIN_PATH . '/users/add', array_merge(['err' => 'email_exists'], $_POST));
}

$chk2 = $db->prepare("SELECT id_users FROM _users WHERE user_name = ? LIMIT 1");
$chk2->execute([$username]);
if ($chk2->fetch()) {
    adminRedirect('/' . ADMIN_PATH . '/users/add', array_merge(['err' => 'user_exists'], $_POST));
}

// ── Hash da senha ──
$password_hash = password_hash($password_raw, PASSWORD_BCRYPT, ['cost' => 12]);

// ── Upload de foto ──
$photo_filename = null;
if (!empty($_FILES['photo']['tmp_name'])) {
    $file = $_FILES['photo'];
    $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
    $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (in_array($mime, $allowed_mime) && $file['size'] <= 2 * 1024 * 1024) {
        $ext = $ext_map[$mime];
        $dest_dir = __DIR__ . '/../../../assets/comprovantes/uploads/users/';
        if (!is_dir($dest_dir)) mkdir($dest_dir, 0755, true);
        $photo_filename = 'user_new_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dest_dir . $photo_filename)) {
            $photo_filename = null;
        }
    }
}

// ── Gerar chave de recuperação (formato: 20 caracteres hex) ──
$recovery_key = bin2hex(random_bytes(10)); // 20 chars

// ── Gerar token de confirmação de e-mail (opcional, para convite) ──
$email_verify_token = null;
$email_verify_expires = null;
if ($send_invite) {
    $email_verify_token = bin2hex(random_bytes(32)); // 64 chars hex
    $email_verify_expires = date('Y-m-d H:i:s', time() + 72 * 3600); // 72h
}

// ── Inserir na BD — transacção ──
try {
    $db->beginTransaction();

    // 1. _users
    $ins = $db->prepare("
        INSERT INTO _users
            (first_name, second_name, user_name, email_user, tel_user,
             gender, birth_date, country_user, city_user, about_user,
             password_user, plan_selected, status_user,
             notif_email, notif_push, notif_weekly, notif_releases, notif_payments,
             ip_register, creat_user)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $ins->execute([
        $first_name,
        $second_name,
        $username,
        $email,
        $tel,
        $gender,
        $birth_date,
        $country,
        $city,
        $about,
        $password_hash,
        $plan_selected,
        $status,
        $notif_email,
        $notif_push,
        $notif_weekly,
        $notif_releases,
        $notif_payments,
        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
    ]);
    $new_id = (int)$db->lastInsertId();

    // Renomear foto com o ID real
    if ($photo_filename) {
        $dest_dir = __DIR__ . '/../../../assets/comprovantes/uploads/users/';
        $new_filename = 'user_' . $new_id . '_' . time() . '.' . pathinfo($photo_filename, PATHINFO_EXTENSION);
        if (rename($dest_dir . $photo_filename, $dest_dir . $new_filename)) {
            $db->prepare("UPDATE _users SET photo_user=? WHERE id_users=?")->execute([$new_filename, $new_id]);
            $photo_filename = $new_filename;
        }
    }

    // 2. _users_security
    $ins_sec = $db->prepare("
        INSERT INTO _users_security
            (id_users, recovery_key, email_verify_token, email_verify_expires)
        VALUES (?, ?, ?, ?)
    ");
    $ins_sec->execute([$new_id, $recovery_key, $email_verify_token, $email_verify_expires]);

    // 3. _user_settings (configurações padrão)
    $ins_settings = $db->prepare("
        INSERT INTO _user_settings
            (id_users, notif_email, notif_push, notif_streams, notif_weekly,
             theme, ui_density, widget_streams, widget_financial, widget_artists,
             private_stats, language, currency, date_format)
        VALUES (?, ?, ?, 0, ?, 'dark', 'compact', 1, 1, 1, 1, 'pt-br', 'AOA', 'dd/mm/yyyy')
    ");
    $ins_settings->execute([$new_id, $notif_email, $notif_push, $notif_weekly]);

    // 4. _wallet (carteira inicial)
    $ins_wallet = $db->prepare("INSERT INTO _wallet (id_users) VALUES (?)");
    $ins_wallet->execute([$new_id]);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    // Apagar foto se foi criada
    if ($photo_filename) {
        $dest_dir = __DIR__ . '/../../../assets/comprovantes/uploads/users/';
        if (file_exists($dest_dir . $photo_filename)) @unlink($dest_dir . $photo_filename);
    }
    error_log('[ADD USER ERROR] ' . $e->getMessage());
    adminRedirect('/' . ADMIN_PATH . '/users/add', ['err' => 'error']);
}

// ── Log de auditoria ──
logAudit(
    $admin_id,
    $new_id,
    'users.created',
    '_users',
    $new_id,
    null,
    ['status' => $status, 'email' => $email, 'plan' => $plan_selected, 'gender' => $gender]
);

// ── Enviar e-mail de boas-vindas / convite ──
$email_sent = true;
if ($send_invite && $email_verify_token) {
    $fullname = trim($first_name . ' ' . ($second_name ?? ''));

    // URL de activação
    $verify_url = APP_URL . '/verify-email?token=' . urlencode($email_verify_token);

    // Status label
    $status_label = match ($status) {
        'active' => 'Activa — podes aceder imediatamente',
        'suspended' => 'Suspensa — aguarda activação pelo administrador',
        'blocked' => 'Bloqueada — contacta o suporte',
        'pending_plan' => 'Pendente — escolhe um plano para começar',
        default => ucfirst($status)
    };

    // Plano label
    $plan_name = '';
    if ($plan_selected) {
        $planStmt = $db->prepare("SELECT name_plan FROM _plans WHERE id_plan = ?");
        $planStmt->execute([$plan_selected]);
        $plan_name = $planStmt->fetchColumn() ?: '';
    }

    // ── Enviar e-mail via WasomMailer ──
    try {
        $mailer_path = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';

        if (!file_exists($mailer_path)) {
            error_log('[USER MAILER] WasomMailer.php não encontrado');
            $email_sent = false;
        } else {
            if (!class_exists('\Wasom\Mailer')) {
                require_once $mailer_path;
            }

            $subject = 'Bem-vindo à Wasom Upfy — As tuas credenciais de acesso';

            $html_body = '<!DOCTYPE html>
<html lang="pt-ao">
<head><meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/><title>Bem-vindo à Wasom Upfy</title></head>
<body style="margin:0;padding:0;background:#f4f4f8;font-family:\'Inter\',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f8;padding:32px 16px">
    <tr><td align="center">
      <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px">
        <tr><td align="center" style="background:linear-gradient(135deg,#FF0089,#6c63ff);border-radius:16px 16px 0 0;padding:32px 24px">
            <img src="' . APP_URL . '/assets/img/brand/wasomupfy_brand.png" alt="Wasom Upfy" width="52" height="52" style="border-radius:50%;border:3px solid rgba(255,255,255,.3);margin-bottom:12px;display:block;margin:0 auto 12px"/>
            <h1 style="color:#fff;font-size:1.3rem;margin:0;font-weight:700">Bem-vindo à Wasom Upfy!</h1>
            <p style="color:rgba(255,255,255,.8);margin:6px 0 0;font-size:.88rem">A tua conta foi criada com sucesso</p>
        </td></tr>
        <tr><td style="background:#fff;padding:28px 28px 24px">
            <p style="font-size:.95rem;color:#111;margin:0 0 6px">Olá, <strong>' . htmlspecialchars($fullname) . '</strong>!</p>
            <p style="font-size:.84rem;color:#555;line-height:1.6;margin:0 0 20px">A tua conta na plataforma <strong>Wasom Upfy</strong> foi criada com sucesso. Abaixo encontras as tuas credenciais de acesso.</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f7fc;border-radius:10px;border:1px solid #e8e8f0;margin-bottom:20px">
                <tr><td style="padding:16px 18px">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr><td style="font-size:.78rem;color:#888;padding:6px 0 2px;border-bottom:1px solid #eee">NOME</td></tr>
                        <tr><td style="font-size:.92rem;font-weight:700;color:#111;padding:4px 0 10px;border-bottom:1px solid #eee">' . htmlspecialchars($fullname) . '</td></tr>
                        <tr><td style="font-size:.78rem;color:#888;padding:6px 0 2px;border-bottom:1px solid #eee">USERNAME</td></tr>
                        <tr><td style="font-size:.92rem;font-weight:700;color:#111;padding:4px 0 10px;border-bottom:1px solid #eee">@' . htmlspecialchars($username) . '</td></tr>
                        <tr><td style="font-size:.78rem;color:#888;padding:6px 0 2px;border-bottom:1px solid #eee">E-MAIL</td></tr>
                        <tr><td style="font-size:.92rem;font-weight:600;color:#111;padding:4px 0 10px;border-bottom:1px solid #eee">' . htmlspecialchars($email) . '</td></tr>
                        <tr><td style="font-size:.78rem;color:#888;padding:6px 0 2px;border-bottom:1px solid #eee">SENHA TEMPORÁRIA</td></tr>
                        <tr><td style="font-family:monospace;font-size:1rem;font-weight:700;color:#FF0089;padding:4px 0 10px;border-bottom:1px solid #eee">' . htmlspecialchars($password_raw) . '</td></tr>
                        <tr><td style="font-size:.78rem;color:#888;padding:6px 0 2px">PLANO</td></tr>
                        <tr><td style="font-size:.84rem;color:#555;padding:2px 0 10px;border-bottom:1px solid #eee">' . ($plan_name ?: 'Sem plano selecionado') . '</td></tr>
                        <tr><td style="font-size:.78rem;color:#888;padding:6px 0 2px">ESTADO DA CONTA</td></tr>
                        <tr><td style="font-size:.84rem;color:#555;padding:2px 0">' . htmlspecialchars($status_label) . '</td></tr>
                    </table>
                </td></tr>
            </table>
            <p style="font-size:.84rem;color:#555;margin:0 0 14px;line-height:1.5">Para activar a tua conta, clica no botão abaixo. Este link expira em <strong>72 horas</strong> e só pode ser utilizado <strong>uma vez</strong>.</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px">
                <tr><td align="center"><a href="' . htmlspecialchars($verify_url) . '" style="display:inline-block;background:linear-gradient(135deg,#FF0089,#cc006e);color:#fff;font-weight:700;font-size:.9rem;padding:14px 36px;border-radius:50px;text-decoration:none">Activar a Minha Conta</a></td></tr>
            </table>
            <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:12px 14px"><p style="margin:0;font-size:.78rem;color:#7c4800"><strong>⚠ Segurança:</strong> Não partilhes esta senha. A senha acima é temporária e deverás alterá-la após o primeiro acesso.</p></div>
        </td></tr>
        <tr><td style="background:#f8f7fc;border-radius:0 0 16px 16px;padding:16px 24px;text-align:center;border-top:1px solid #e8e8f0"><p style="margin:0;font-size:.75rem;color:#aaa">© 2026 Wasom Upfy · Todos os direitos reservados.</p></td></tr>
      </table>
    </td></tr>
</table>
</body>
</html>';

            $plain_body = "Olá $fullname,\n\nA tua conta na Wasom Upfy foi criada com sucesso.\n\n"
                . "Username: $username\n"
                . "E-mail: $email\n"
                . "Senha temporária: $password_raw\n\n"
                . "Activa a tua conta: $verify_url\n\n"
                . "Este link expira em 72 horas.\n\n"
                . "Wasom Upfy";

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
                ->setBody($html_body, $plain_body);
            $wm->send();
            $email_sent = true;
        }
    } catch (\Wasom\MailerException $e) {
        error_log('[USER MAILER] Falha ao enviar boas-vindas: ' . $e->getMessage());
        $email_sent = false;
    } catch (Exception $e) {
        error_log('[USER MAILER] Erro inesperado: ' . $e->getMessage());
        $email_sent = false;
    }
}

// ── Redirecionar ──
if ($send_invite && !$email_sent) {
    adminRedirect('/' . ADMIN_PATH . '/users', ['msg' => 'added', 'warn' => 'email_fail']);
} else {
    adminRedirect('/' . ADMIN_PATH . '/users', ['msg' => 'added']);
}