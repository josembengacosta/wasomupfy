<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Logout de Colaborador
// Arquivo: dashboard/collab/logout.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();

$db = getDB();

// Invalidar sessão na BD
if (!empty($_SESSION['collab_id']) && !empty($_SESSION['collab_session_token'])) {
    try {
        $db->prepare("
            UPDATE _collab_sessions SET is_active = 0
            WHERE id_collab = ? AND session_token = ?
        ")->execute([$_SESSION['collab_id'], $_SESSION['collab_session_token']]);

        $db->prepare("
            INSERT INTO _collab_activity (id_collab, id_users, activity_type, description, ip_address)
            VALUES (?,?,?,?,?)
        ")->execute([
            $_SESSION['collab_id'],
            $_SESSION['collab_id_users'] ?? 0,
            'logout',
            'Sessão terminada',
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Exception $e) {}
}

// Limpar apenas variáveis de colaborador
$keys_to_remove = ['collab_id','collab_id_users','collab_role','collab_session_token','collab_must_change','collab_first_login'];
foreach ($keys_to_remove as $k) unset($_SESSION[$k]);

header('Location: ' . rtrim(APP_URL,'/') . '/dashboard/account/collab-login?msg=logout');
exit;