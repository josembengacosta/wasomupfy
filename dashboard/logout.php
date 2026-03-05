<?php
// dashboard/logout.php
require_once __DIR__ . '/../authentic/include/functions.php';
startSecureSession();

if (isLoggedIn()) {
    $id_users = (int)$_SESSION['id_users'];

    // Desactivar sessao activa na BD
    destroyUserSession($id_users);

    // Registar actividade
    logActivity($id_users, 'logout', 'Sessao terminada');

    // Invalidar cookie remember-me
    if (!empty($_COOKIE['wuf_remember'])) {
        getDB()->prepare("UPDATE _users_security SET remember_token = NULL WHERE id_users = ?")
               ->execute([$id_users]);
        setcookie('wuf_remember', '', ['expires' => 1, 'path' => '/']);
    }
}

// Destruir sessao PHP
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

redirect('/login', ['notice' => 'logout']);