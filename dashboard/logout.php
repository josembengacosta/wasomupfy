<?php
session_start();
include "include/connection.php";

//  Limpar todas as variáveis da sessão
$_SESSION = array();

if (ini_get(option: "session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        name: session_name(),
        value: '',
        expires_or_options: time() - 42000,
        path: $params["path"],
        domain: $params["domain"],
        secure: $params["secure"],
        httponly: $params["httponly"]
    );
}
session_destroy();
header(header: 'Location:authentic/sign-in?logout_success=1');
exit();
?>