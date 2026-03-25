<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Activar / Suspender Conta
// Arquivo: admin/pages/users/available-account.php
//          admin/pages/users/unavailable-account.php
//
// Ambos os ficheiros são servidos por este script.
// O .htaccess mapeia:
//   admin/users/available-account   → este ficheiro com ?_action=activate
//   admin/users/unavailable-account → este ficheiro com ?_action=suspend
//
// Em alternativa, cria dois ficheiros PHP iguais e muda só o $action_mode.
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'users.edit');

// Detectar qual acção pelo nome do ficheiro actual
$current_file = basename(__FILE__, '.php');
$action_mode  = $current_file === 'available-account' ? 'activate' : 'suspend';

$id = (int)($_GET['id'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/users');

// Verificar que utilizador existe
$row = $db->prepare("SELECT id_users, status_user FROM _users WHERE id_users=? LIMIT 1");
$row->execute([$id]);
$target = $row->fetch();
if (!$target) adminRedirect('/' . ADMIN_PATH . '/users');

$back_view = '/' . ADMIN_PATH . '/users/view?id=' . $id;

if ($action_mode === 'activate') {
    $db->prepare("UPDATE _users SET status_user='active' WHERE id_users=?")
        ->execute([$id]);
    logAudit(
        $admin_id,
        $id,
        'users.activated',
        '_users',
        $id,
        ['status' => $target['status_user']],
        ['status' => 'active']
    );
    adminRedirect($back_view . '&msg=unblocked');
} else {
    $db->prepare("UPDATE _users SET status_user='suspended' WHERE id_users=?")
        ->execute([$id]);
    logAudit(
        $admin_id,
        $id,
        'users.suspended',
        '_users',
        $id,
        ['status' => $target['status_user']],
        ['status' => 'suspended']
    );
    adminRedirect($back_view . '&msg=blocked');
}
