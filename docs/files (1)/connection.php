<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Conexão com o Banco de Dados
// Arquivo: authentic/include/connection.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/config.php';

/**
 * Retorna a instância PDO (singleton)
 * Uso: $db = getDB();
 */
function getDB(): PDO {
    static $conn = null;

    if ($conn === null) {
        $dsn = "mysql:host=" . DB_HOST
             . ";dbname=" . DB_NAME
             . ";charset=" . DB_CHARSET;

        try {
            $conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            if (APP_ENV === 'development') {
                die("Erro de conexão: " . $e->getMessage());
            }
            error_log("[DB ERROR] " . $e->getMessage());
            die("Erro ao conectar com o servidor. Tente novamente mais tarde.");
        }
    }

    return $conn;
}
