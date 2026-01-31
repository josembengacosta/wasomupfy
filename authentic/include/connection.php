<?php
//carregar config do arquivo
$config = include ('config.php');
// Conexão com o banco de dados
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_errno) {
    error_log("FConexão falhou:" . $conn->connect_error);
    die("Ocorreu um erro ao tentar conectar com servidor. Por favor, tente novamente mais tarde.");
}

$conn->close();