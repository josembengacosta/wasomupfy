<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Configurações de Exemplo
// Arquivo: authentic/include/config.example.php
// ══════════════════════════════════════════════

// ─── Banco de Dados ───────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'nome_do_banco');
define('DB_USER',    'usuario_do_banco');
define('DB_PASS',    'senha_do_banco');
define('DB_CHARSET', 'utf8mb4');

// ─── Aplicação ────────────────────────────────
define('APP_NAME',    'Wasom Upfy');
define('APP_VERSION', '2.0');
define('APP_URL',     'https://seusite.infinityfreeapp.com'); // URL de produção
define('APP_URL_PANEL',     'dashboard');
define('APP_ENV',     'production');
define('VAPID_PUBLIC_KEY',  'COLOQUE_SUA_CHAVE_PUBLICA_AQUI');
define('VAPID_PRIVATE_KEY', 'COLOQUE_SUA_CHAVE_PRIVADA_AQUI');
define('VAPID_SUBJECT',     'mailto:suporte@seusite.com');

// ─── Segurança de Login ───────────────────────
define('MAX_LOGIN_ATTEMPTS', 5);
define('BLOCK_LEVEL_1_MIN', 5);
define('BLOCK_LEVEL_2_MIN', 15);
define('BLOCK_LEVEL_3_MIN', 30);

// ─── Sessão ───────────────────────────────────
define('SESSION_NAME',     'wasomupfy_session');
define('SESSION_LIFETIME', 3600);

// ─── E-mail — PHPMailer + SMTP ───────────────
define('MAIL_DRIVER',   'phpmailer');
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_SECURE',   'tls');
define('MAIL_USER',     'seuemail@gmail.com');
define('MAIL_PASS',     'sua_senha_de_app');
define('MAIL_FROM',     'seuemail@gmail.com');
define('MAIL_FROM_NAME', APP_NAME);
define('MAIL_DEBUG', 0);

// ─── Uploads ──────────────────────────────────
define('UPLOAD_PATH', __DIR__ . '/../../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024);

// ─── Caminhos ─────────────────────────────────
define('AUTHENTIC_URL', APP_URL . '/authentic');
define('DASHBOARD_URL', APP_URL . '/dashboard');
define('PRIVACY_VERSION', '2.0');
define('PRIVACY_DATE',    '11 de Março de 2026');
define('TERMS_VERSION', '2.0');
define('TERMS_DATE',    '11 de Março de 2026');

// ─── Painel Admin ────────────────────────────
define('ADMIN_PATH', 'wu-panel');
