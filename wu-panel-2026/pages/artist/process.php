<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Acções de Artistas
// Arquivo: wu-panel-2026/pages/artist/process.php
// Rota:    wu-panel-2026/artist/process (POST only)
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';

function jsonOut(bool $ok, string $msg, array $extra = []): never {
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, 'Método não permitido.');
}

$csrf_post = $_POST['csrf_token'] ?? '';
$csrf_session = $_SESSION['admin_csrf_token'] ?? '';
if (!$csrf_session || !hash_equals($csrf_session, $csrf_post)) {
    jsonOut(false, 'Sessão expirada. Recarrega a página.');
}

requirePermission($admin_id, 'users.view');

$action = trim($_POST['action'] ?? '');
$id_artist = (int)($_POST['id_artist'] ?? 0);
if ($id_artist <= 0) jsonOut(false, 'ID do artista inválido.');

// Buscar artista
$stmt = $db->prepare("SELECT a.*, u.id_users AS owner_id, u.email_user AS owner_email FROM _artist a LEFT JOIN _users u ON u.id_users = a.id_users WHERE a.id_artist = ?");
$stmt->execute([$id_artist]);
$artist = $stmt->fetch();
if (!$artist) jsonOut(false, 'Artista não encontrado.');

// ── Toggle status ──
if ($action === 'toggle_artist_status') {
    requirePermission($admin_id, 'users.edit');

    $new_status = trim($_POST['new_status'] ?? '');
    if (!in_array($new_status, ['active', 'inactive', 'blocked'], true)) {
        jsonOut(false, 'Estado inválido.');
    }
    if ($artist['status_artist'] === $new_status) {
        jsonOut(false, 'O artista já está ' . ($new_status === 'active' ? 'activo' : ($new_status === 'blocked' ? 'bloqueado' : 'inactivo')) . '.');
    }

    try {
        $db->beginTransaction();
        $db->prepare("UPDATE _artist SET status_artist = ? WHERE id_artist = ?")->execute([$new_status, $id_artist]);
        $db->prepare("
            INSERT INTO _user_activity_log (id_users, activity_type, description, entity, entity_id, ip_address)
            VALUES (?, 'artist_status_changed', ?, 'artist', ?, ?)
        ")->execute([
            $artist['owner_id'],
            $new_status === 'active' ? 'Artista desbloqueado pelo administrador' : ($new_status === 'blocked' ? 'Artista bloqueado pelo administrador' : 'Artista desactivado pelo administrador'),
            $id_artist,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        $db->commit();

        $old = json_encode(['status_artist' => $artist['status_artist']]);
        $new = json_encode(['status_artist' => $new_status]);
        logAudit($admin_id, $artist['owner_id'], 'artist.status_changed', '_artist', $id_artist, $old, $new);

        $msg = $new_status === 'active' ? 'Artista desbloqueado com sucesso!' : ($new_status === 'blocked' ? 'Artista bloqueado com sucesso!' : 'Artista desactivado com sucesso!');
        jsonOut(true, $msg);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[ARTIST STATUS] ' . $e->getMessage());
        jsonOut(false, 'Erro ao alterar estado.');
    }
}

// ── Delete ──
if ($action === 'delete_artist') {
    requirePermission($admin_id, 'users.edit');

    $admin_row = $db->prepare("SELECT password_employees FROM _employees WHERE id_employees = ?");
    $admin_row->execute([$admin_id]);
    $admin_data = $admin_row->fetch();
    if (!$admin_data) jsonOut(false, 'Erro de sessão.');

    $password_confirm = $_POST['password_confirm'] ?? '';
    if (empty($password_confirm) || !password_verify($password_confirm, $admin_data['password_employees'])) {
        jsonOut(false, 'Senha incorrecta.');
    }

    // Notificar dono
    $subject = 'Artista removido — ' . APP_NAME;
    $body = "<div style='font-family:Arial;max-width:540px;margin:auto'><div style='background:#555;padding:24px 32px'><h1 style='color:#fff'>" . APP_NAME . "</h1></div><div style='padding:28px 32px'><p>O artista <strong>" . htmlspecialchars($artist['stage_name']) . "</strong> foi removido da plataforma por um administrador.</p><p>Se tiveres dúvidas, contacta o suporte.</p><hr><small>" . APP_NAME . "</small></div></div>";
    $mailer_path = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';
    if (file_exists($mailer_path)) {
        if (!class_exists('\Wasom\Mailer')) require_once $mailer_path;
        try {
            $wm = new \Wasom\Mailer();
            $wm->host = MAIL_HOST; $wm->port = MAIL_PORT; $wm->secure = defined('MAIL_SECURE') ? MAIL_SECURE : 'tls';
            $wm->username = MAIL_USER; $wm->password = MAIL_PASS; $wm->debug = 0;
            $wm->setFrom(MAIL_FROM, MAIL_FROM_NAME)->addAddress($artist['owner_email'])->setSubject($subject)->setBody($body, strip_tags($body));
            $wm->send();
        } catch (\Wasom\MailerException $e) { error_log('[ARTIST DELETE MAIL] ' . $e->getMessage()); }
    }

    $audit_old = json_encode(['stage_name' => $artist['stage_name'], 'real_name' => $artist['real_name'], 'owner_id' => $artist['owner_id']]);
    try {
        $db->beginTransaction();
        $db->prepare("DELETE FROM _artist WHERE id_artist = ?")->execute([$id_artist]);
        $db->commit();
        logAudit($admin_id, $artist['owner_id'], 'artist.deleted', '_artist', $id_artist, $audit_old, null);
        jsonOut(true, 'Artista eliminado com sucesso!');
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[ARTIST DELETE] ' . $e->getMessage());
        jsonOut(false, 'Erro ao eliminar artista.');
    }
}

jsonOut(false, 'Acção desconhecida.');