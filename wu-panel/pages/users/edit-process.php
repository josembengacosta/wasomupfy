<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Edição de Utilizador
// Arquivo: admin/pages/users/edit-process.php
// Rota:    admin/users/edit-process
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'users.edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/' . ADMIN_PATH . '/users');
}

// Validar CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    adminRedirect('/' . ADMIN_PATH . '/users/edit?id=' . ($_POST['id_users'] ?? 0) . '&msg=error');
}

$id = (int)($_POST['id_users'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/users');

// =============================================
// UPLOAD DE IMAGEM - CORRIGIDO
// =============================================
$photo_path = null;

if (isset($_FILES['photo_user']) && $_FILES['photo_user']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['photo_user'];

    // Validar tipo pelo nome do arquivo (mais confiável)
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_extensions)) {
        adminRedirect('/' . ADMIN_PATH . '/users/edit?id=' . $id . '&msg=error');
    }

    // Validar tamanho (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        adminRedirect('/' . ADMIN_PATH . '/users/edit?id=' . $id . '&msg=error');
    }

    // Caminho absoluto para a pasta de uploads
    $upload_dir = __DIR__ . '/../../../assets/comprovantes/uploads/users/';

    // Criar pasta se não existir
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Gerar nome único
    $filename = 'user_' . $id . '_' . time() . '.' . $ext;
    $target_path = $upload_dir . $filename;

    // Mover arquivo
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $photo_path = $filename;

        // Apagar foto antiga
        $old = $db->prepare("SELECT photo_user FROM _users WHERE id_users = ?");
        $old->execute([$id]);
        $old_photo = $old->fetchColumn();

        if ($old_photo && $old_photo !== $photo_path) {
            $old_path = $upload_dir . $old_photo;
            if (file_exists($old_path)) {
                @unlink($old_path);
            }
        }
    }
}

// =============================================
// DADOS DO FORMULÁRIO
// =============================================
$first_name = trim($_POST['first_name'] ?? '');
$second_name = trim($_POST['second_name'] ?? '');
$user_name = trim($_POST['user_name'] ?? '');
$email_user = trim($_POST['email_user'] ?? '');
$tel_user = trim($_POST['tel_user'] ?? '');
$country_user = trim($_POST['country_user'] ?? '');
$city_user = trim($_POST['city_user'] ?? '');
$about_user = trim($_POST['about_user'] ?? '');
$plan_selected = (int)($_POST['plan_selected'] ?? 0);
$status_user = $_POST['status_user'] ?? 'active';

$notif_email = isset($_POST['notif_email']) ? 1 : 0;
$notif_push = isset($_POST['notif_push']) ? 1 : 0;
$notif_weekly = isset($_POST['notif_weekly']) ? 1 : 0;
$notif_releases = isset($_POST['notif_releases']) ? 1 : 0;
$notif_payments = isset($_POST['notif_payments']) ? 1 : 0;

// Validar campos obrigatórios
if (empty($first_name) || empty($email_user)) {
    adminRedirect('/' . ADMIN_PATH . '/users/edit?id=' . $id . '&msg=error');
}

// Validar email
if (!filter_var($email_user, FILTER_VALIDATE_EMAIL)) {
    adminRedirect('/' . ADMIN_PATH . '/users/edit?id=' . $id . '&msg=error');
}

// =============================================
// ATUALIZAR NO BANCO
// =============================================
try {
    $db->beginTransaction();

    $sql = "UPDATE _users SET 
        first_name = ?,
        second_name = ?,
        user_name = ?,
        email_user = ?,
        tel_user = ?,
        country_user = ?,
        city_user = ?,
        about_user = ?,
        plan_selected = ?,
        status_user = ?,
        notif_email = ?,
        notif_push = ?,
        notif_weekly = ?,
        notif_releases = ?,
        notif_payments = ?
    ";
    $params = [
        $first_name,
        $second_name,
        $user_name,
        $email_user,
        $tel_user,
        $country_user,
        $city_user,
        $about_user,
        $plan_selected ?: null,
        $status_user,
        $notif_email,
        $notif_push,
        $notif_weekly,
        $notif_releases,
        $notif_payments
    ];

    if ($photo_path) {
        $sql .= ", photo_user = ?";
        $params[] = $photo_path;
    }

    $sql .= " WHERE id_users = ?";
    $params[] = $id;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $db->commit();

    adminRedirect('/' . ADMIN_PATH . '/users/view?id=' . $id . '&msg=updated');
} catch (Exception $e) {
    $db->rollBack();
    error_log('[EDIT USER ERROR] ' . $e->getMessage());
    adminRedirect('/' . ADMIN_PATH . '/users/edit?id=' . $id . '&msg=error');
}
