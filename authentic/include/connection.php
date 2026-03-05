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
function getDB(): PDO
{
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
            die("<!DOCTYPE html>
<html lang='pt-BR'>
<head>
  <meta charset='UTF-8'>
  <title>Erro - Wasom Upfy</title>
  <style>
    :root {
      --wasom-primary: #ff0089;
      --wasom-secondary: #e04385;
      --wasom-light: #fff0f7;
      --wasom-dark: #cc0070;
    }

    body {
      margin: 0;
      font-family:  'Segoe UI', Arial, sans-serif;
      background: var(--wasom-light);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .error-container {
      background: #fff;
      border: 2px solid var(--wasom-secondary);
      border-radius: 12px;
      padding: 2rem 3rem;
      text-align: center;
      box-shadow: 0 6px 20px rgba(0,0,0,0.1);
      max-width: 500px;
    }

    .error-title {
      font-size: 1.8rem;
      font-weight: bold;
      color: var(--wasom-primary);
      margin-bottom: 1rem;
    }

    .error-message {
      font-size: 1rem;
      color: var(--wasom-dark);
      margin-bottom: 2rem;
    }

    .error-footer {
      font-size: 0.9rem;
      color: #666;
    }

    .btn-retry {
      display: inline-block;
      background: var(--wasom-primary);
      color: white;
      padding: 0.7rem 1.5rem;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      transition: background 0.3s ease;
    }

    .btn-retry:hover {
      background: var(--wasom-dark);
    }
  </style>
</head>
<body>
  <div class='error-container'>
    <div class='error-title'> Ops! Algo deu errado</div>
    <div class='error-message'>
      Não foi possível conectar ao servidor.<br>
      Por favor, tente novamente mais tarde.
    </div>
    <a href='/home.php' class='btn-retry'>Voltar para Wasom Upfy</a>
    <div class='error-footer'>Plataforma Wasom Upfy</div>
  </div>
</body>
</html>");
        }
    }

    return $conn;
}