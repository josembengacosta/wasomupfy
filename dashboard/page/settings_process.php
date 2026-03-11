<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Processador de Configurações
// Arquivo: dashboard/page/settings_process.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];
$user     = getUserById($id_users);
if (!$user) { redirect('authentic/logout'); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard/page/settings');
}

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['settings_flash'] = ['type'=>'error','msg'=>'Token inválido. Tenta novamente.'];
    redirect('dashboard/page/settings');
}

$action = $_POST['action'] ?? '';

// ══════════════════════════════════════════════════════
// upsertSettings — CORRIGIDO
// Usa VALUES(col) no ON DUPLICATE KEY UPDATE para evitar
// SQLSTATE[HY093]: named params duplicados no PDO.
// ══════════════════════════════════════════════════════
function upsertSettings(PDO $db, int $id_users, array $fields): void {
    $cols = implode(', ', array_keys($fields));
    $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($fields)));
    $upd  = implode(', ', array_map(fn($k) => "$k = VALUES($k)", array_keys($fields)));
    $params = $fields;
    $params['id_users'] = $id_users;
    $db->prepare("
        INSERT INTO _user_settings (id_users, $cols)
        VALUES (:id_users, $vals)
        ON DUPLICATE KEY UPDATE $upd
    ")->execute($params);
}

function flashRedirect(string $type, string $msg, string $anchor = ''): void {
    $_SESSION['settings_flash'] = ['type' => $type, 'msg' => $msg];
    redirect('dashboard/page/settings' . ($anchor ? '#' . $anchor : ''));
}

switch ($action) {

case 'save_notifications':
    upsertSettings($db, $id_users, [
        'notif_email'   => isset($_POST['notif_email'])   ? 1 : 0,
        'notif_push'    => isset($_POST['notif_push'])    ? 1 : 0,
        'notif_streams' => isset($_POST['notif_streams']) ? 1 : 0,
        'notif_weekly'  => isset($_POST['notif_weekly'])  ? 1 : 0,
    ]);
    logActivity($id_users, 'settings', 'Preferências de notificação actualizadas', 'settings', null);
    flashRedirect('success', 'Preferências de notificação guardadas.', 'notifications');
    break;

case 'save_appearance':
    $theme      = in_array($_POST['theme'] ?? '', ['dark','light','system']) ? $_POST['theme'] : 'dark';
    $ui_density = in_array($_POST['ui_density'] ?? '', ['comfortable','compact','cozy']) ? $_POST['ui_density'] : 'compact';
    upsertSettings($db, $id_users, ['theme' => $theme, 'ui_density' => $ui_density]);
    logActivity($id_users, 'settings', 'Aparência actualizada: tema ' . $theme, 'settings', null);
    flashRedirect('success', 'Aparência guardada com sucesso.', 'appearance');
    break;

case 'save_widgets':
    upsertSettings($db, $id_users, [
        'widget_streams'   => isset($_POST['widget_streams'])   ? 1 : 0,
        'widget_financial' => isset($_POST['widget_financial']) ? 1 : 0,
        'widget_releases'  => isset($_POST['widget_releases'])  ? 1 : 0,
        'widget_artists'   => isset($_POST['widget_artists'])   ? 1 : 0,
        'widget_activity'  => isset($_POST['widget_activity'])  ? 1 : 0,
    ]);
    logActivity($id_users, 'settings', 'Widgets do dashboard actualizados', 'settings', null);
    flashRedirect('success', 'Widgets do dashboard guardados.', 'dashboard');
    break;

case 'save_privacy':
    upsertSettings($db, $id_users, [
        'private_stats'  => isset($_POST['private_stats'])  ? 1 : 0,
        'accept_cookies' => isset($_POST['accept_cookies']) ? 1 : 0,
        'share_data'     => isset($_POST['share_data'])     ? 1 : 0,
        'two_factor'     => isset($_POST['two_factor'])     ? 1 : 0,
    ]);
    logActivity($id_users, 'settings', 'Preferências de privacidade actualizadas', 'settings', null);
    flashRedirect('success', 'Privacidade guardada.', 'privacy');
    break;

case 'save_language':
    $language    = in_array($_POST['language']    ?? '', ['pt-ao','pt-br','pt-pt','en','fr'])        ? $_POST['language']    : 'pt-ao';
    $currency    = in_array($_POST['currency']    ?? '', ['AOA','USD','EUR','BRL'])                   ? $_POST['currency']    : 'AOA';
    $date_format = in_array($_POST['date_format'] ?? '', ['dd/mm/yyyy','mm/dd/yyyy','yyyy-mm-dd'])    ? $_POST['date_format'] : 'dd/mm/yyyy';
    upsertSettings($db, $id_users, compact('language', 'currency', 'date_format'));
    logActivity($id_users, 'settings', "Idioma/região actualizado: $language / $currency", 'settings', null);
    flashRedirect('success', 'Preferências regionais guardadas.', 'language');
    break;

case 'revoke_sessions':
    // remember_token está em _users_security (não numa tabela separada)
    try {
        $db->prepare("UPDATE _users_security SET remember_token = NULL WHERE id_users = ?")
           ->execute([$id_users]);
    } catch (PDOException $e) { /* seguro */ }
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
    logActivity($id_users, 'security', 'Todas as sessões externas encerradas', 'session', null);
    flashRedirect('success', 'Todas as sessões activas foram encerradas.', 'danger');
    break;

case 'revoke_analytics':
    upsertSettings($db, $id_users, ['share_data' => 0, 'accept_cookies' => 0]);
    logActivity($id_users, 'settings', 'Acesso analítico revogado', 'settings', null);
    flashRedirect('success', 'Acesso a dados analíticos revogado.', 'danger');
    break;

// Desactivar e Eliminar são acções sensíveis — redireccionam
// para profile onde há confirmações de segurança adicionais
case 'deactivate_account':
    redirect('dashboard/user/profile?action=deactivate');
    break;

case 'delete_account':
    redirect('dashboard/user/profile?action=delete');
    break;

default:
    flashRedirect('error', 'Acção desconhecida.');
    break;
}