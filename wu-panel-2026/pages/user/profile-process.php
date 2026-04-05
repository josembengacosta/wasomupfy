<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Perfil Admin
// Arquivo: admin/auth/profile-process.php
// .htaccess: ^admin/profile-process/?$ → este ficheiro
// Método: POST único
// ══════════════════════════════════════════════

require_once __DIR__ . '/../../auth/include/functions_admin.php';
startAdminSession();
requireAdminLogin();
requireNoLockscreen();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/' . ADMIN_PATH . '/profile');
}

// ── CSRF ──
$csrf = $_POST['csrf_token'] ?? '';
if (!validateAdminCsrf($csrf)) {
    adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'error']);
}
$_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

$db       = getDB();
$admin_id = (int)$_SESSION['admin_id'];
$action   = $_POST['action'] ?? '';

switch ($action) {

    // ══════════════════════════════════════════
    // ACTUALIZAR PERFIL
    // ══════════════════════════════════════════
    case 'update_profile':
        $first_name  = trim($_POST['first_name']  ?? '');
        $second_name = trim($_POST['second_name'] ?? '');
        $email       = strtolower(trim($_POST['email_employees'] ?? ''));
        $email_other = strtolower(trim($_POST['email_employees_other'] ?? '')) ?: null;
        $tel         = trim($_POST['tel_employees']    ?? '') ?: null;
        $url         = trim($_POST['url_employees']    ?? '') ?: null;
        $about       = trim($_POST['about_employees']  ?? '') ?: null;
        $country     = trim($_POST['country_employees'] ?? '') ?: null;
        $city        = trim($_POST['city_employees']    ?? '') ?: null;
        $gender      = in_array($_POST['gender'] ?? '', ['M', 'F']) ? $_POST['gender'] : 'M';

        if (empty($first_name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'error', 'tab' => 'settings']);
        }

        // Verificar se o e-mail já existe noutro admin
        $check = $db->prepare("SELECT id_employees FROM _employees WHERE email_employees=? AND id_employees!=? LIMIT 1");
        $check->execute([$email, $admin_id]);
        if ($check->fetch()) {
            adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'error', 'tab' => 'settings']);
        }

        $db->prepare("
            UPDATE _employees SET
                first_name            = ?,
                second_name           = ?,
                email_employees       = ?,
                email_employees_other = ?,
                tel_employees         = ?,
                url_employees         = ?,
                about_employees       = ?,
                country_employees     = ?,
                city_employees        = ?,
                gender                = ?
            WHERE id_employees = ?
        ")->execute([
            $first_name,
            $second_name ?: null,
            $email,
            $email_other,
            $tel,
            $url,
            $about,
            $country,
            $city,
            $gender,
            $admin_id,
        ]);

        // Actualizar sessão
        $_SESSION['admin_name']      = $first_name;
        $_SESSION['admin_full_name'] = trim($first_name . ' ' . $second_name);
        $_SESSION['admin_email']     = $email;

        logAudit($admin_id, null, 'profile.update', 'employees', $admin_id, null, [
            'first_name' => $first_name,
            'email' => $email,
        ]);

        adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'profile_ok', 'tab' => 'settings']);
        break;

    // ══════════════════════════════════════════
    // ALTERAR FOTO DE PERFIL
    // ══════════════════════════════════════════
    case 'update_photo':
        if (empty($_FILES['photo']['tmp_name'])) {
            adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'error', 'tab' => 'settings']);
        }

        $file     = $_FILES['photo'];
        $maxSize  = 2 * 1024 * 1024; // 2MB
        $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
        $ext_map  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        // Verificar tamanho
        if ($file['size'] > $maxSize) {
            adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'error', 'tab' => 'settings']);
        }

        // Verificar MIME real (não confiar no $_FILES['type'])
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mime     = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowed)) {
            adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'error', 'tab' => 'settings']);
        }

        $ext      = $ext_map[$mime];
        $filename = 'emp_' . $admin_id . '_' . time() . '.' . $ext;
        $dest     = __DIR__ . '/../../../assets/comprovantes/uploads/employees/' . $filename;

        // Criar directório se não existir
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'error', 'tab' => 'settings']);
        }

        // Apagar foto anterior
        $old = $db->prepare("SELECT photo_employees FROM _employees WHERE id_employees=? LIMIT 1");
        $old->execute([$admin_id]);
        $old_photo = $old->fetchColumn();
        if ($old_photo) {
            $old_path = __DIR__ . '/../../../assets/comprovantes/uploads/employees/' . $old_photo;
            if (file_exists($old_path)) @unlink($old_path);
        }

        $db->prepare("UPDATE _employees SET photo_employees=? WHERE id_employees=?")
            ->execute([$filename, $admin_id]);

        $_SESSION['admin_photo'] = $filename;

        logAudit($admin_id, null, 'profile.photo_update', 'employees', $admin_id, null, null);

        adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'photo_ok', 'tab' => 'settings']);
        break;

    // ══════════════════════════════════════════
    // ALTERAR SENHA
    // ══════════════════════════════════════════
    case 'change_password':
        $current = $_POST['current_password'] ?? '';
        $new_pw  = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($current) || empty($new_pw) || empty($confirm)) {
            adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'error', 'tab' => 'security']);
        }

        // Verificar senha actual
        $admin = $db->prepare("SELECT password_employees FROM _employees WHERE id_employees=? LIMIT 1");
        $admin->execute([$admin_id]);
        $hash = $admin->fetchColumn();

        if (!$hash || !password_verify($current, $hash)) {
            adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'password_err', 'tab' => 'security']);
        }

        // Verificar requisitos da nova senha
        if (
            strlen($new_pw) < 8 ||
            strlen($new_pw) > 128 ||
            !preg_match('/[A-Z]/', $new_pw) ||
            !preg_match('/[a-z]/', $new_pw) ||
            !preg_match('/[0-9]/', $new_pw) ||
            !preg_match('/[!@#$%^&*\-_+=?]/', $new_pw)
        ) {
            adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'password_w', 'tab' => 'security']);
        }

        // Verificar coincidência
        if (!hash_equals($new_pw, $confirm)) {
            adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'password_w', 'tab' => 'security']);
        }

        // Actualizar hash
        $new_hash = password_hash($new_pw, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare("UPDATE _employees SET password_employees=? WHERE id_employees=?")
            ->execute([$new_hash, $admin_id]);

        // Invalidar remember token (forçar novo login noutros dispositivos)
        $db->prepare("UPDATE _employees_security SET remember_token=NULL WHERE id_employees=?")
            ->execute([$admin_id]);

        logAudit($admin_id, null, 'auth.password_changed', 'employees', $admin_id, null, null);

        adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'password_ok', 'tab' => 'security']);
        break;

    // ══════════════════════════════════════════
    // ACTIVAR LOCKSCREEN
    // ══════════════════════════════════════════
    case 'enable_lockscreen':
        activateLockscreen($admin_id);
        logAudit($admin_id, null, 'profile.lockscreen_enabled', 'employees', $admin_id, null, null);
        adminRedirect('/' . ADMIN_PATH . '/lockscreen');
        break;

    // ══════════════════════════════════════════
    // DESACTIVAR LOCKSCREEN
    // ══════════════════════════════════════════
    case 'disable_lockscreen':
    $db->prepare("UPDATE _employees_security SET lockscreen = 0 WHERE id_employees = ?")
        ->execute([$admin_id]);
    $_SESSION['admin_lockscreen'] = false;
    logAudit($admin_id, null, 'profile.lockscreen_disabled', 'employees', $admin_id, null, null);
    adminRedirect('/' . ADMIN_PATH . '/profile', ['tab' => 'security']);
    break;

    // ══════════════════════════════════════════
    // REMOVER FOTO DE PERFIL
    // ══════════════════════════════════════════
    case 'remove_photo':
        $old = $db->prepare("SELECT photo_employees FROM _employees WHERE id_employees=? LIMIT 1");
        $old->execute([$admin_id]);
        $old_photo = $old->fetchColumn();
        if ($old_photo) {
            $old_path = __DIR__ . '/../../../assets/comprovantes/uploads/employees/' . $old_photo;
            if (file_exists($old_path)) @unlink($old_path);
        }
        $db->prepare("UPDATE _employees SET photo_employees=NULL WHERE id_employees=?")
            ->execute([$admin_id]);
        $_SESSION['admin_photo'] = null;
        logAudit($admin_id, null, 'profile.photo_removed', 'employees', $admin_id, null, null);
        adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'photo_ok', 'tab' => 'settings']);
        break;

    // ══════════════════════════════════════════
    // REGENERAR ACCESS CODE (Manager/Lockscreen)
    // ══════════════════════════════════════════
    case 'regenerate_access_code':
        $current_password = $_POST['current_password'] ?? '';
        if (empty($current_password)) {
            adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'error', 'tab' => 'security']);
        }

        // Verificar senha actual
        $admin = $db->prepare("SELECT password_employees FROM _employees WHERE id_employees=? LIMIT 1");
        $admin->execute([$admin_id]);
        $hash = $admin->fetchColumn();

        if (!$hash || !password_verify($current_password, $hash)) {
            adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'password_err', 'tab' => 'security']);
        }

        // Gerar novo código de 6 dígitos
        $new_code = sprintf("%06d", random_int(0, 999999));
        $db->prepare("UPDATE _employees_security SET access_code = ? WHERE id_employees = ?")
            ->execute([$new_code, $admin_id]);

        logAudit($admin_id, null, 'security.access_code_regenerated', 'employees', $admin_id, null, null);

        adminRedirect('/' . ADMIN_PATH . '/profile', ['msg' => 'access_code_ok', 'tab' => 'security']);
        break;

    default:
        adminRedirect('/' . ADMIN_PATH . '/profile');
}