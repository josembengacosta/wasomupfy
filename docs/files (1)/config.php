<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Configurações Globais
// Arquivo: authentic/include/config.php
// ══════════════════════════════════════════════

// ─── Banco de Dados ───────────────────────────
define('DB_HOST',    'sql200.infinityfree.com');
define('DB_NAME',    'wasomupfy');
define('DB_USER',    'root');
define('DB_PASS',    'Amoreterno@123...1');
define('DB_CHARSET', 'utf8mb4');

// ─── Aplicação ────────────────────────────────
define('APP_NAME',    'Wasom Upfy');
define('APP_VERSION', '2.0');
define('APP_URL',     'https://wasomupfy.rf.gd'); // Mudar em produção
define('APP_ENV',     'development');                // 'production' no servidor

// ─── Segurança de Login ───────────────────────
define('MAX_LOGIN_ATTEMPTS', 5);  // Tentativas antes do bloqueio nível 2
define('BLOCK_LEVEL_1_MIN', 5);   // Minutos para bloqueio nível 1 (3 tentativas)
define('BLOCK_LEVEL_2_MIN', 15);  // Minutos para bloqueio nível 2 (5 tentativas)
define('BLOCK_LEVEL_3_MIN', 30);  // Minutos para bloqueio nível 3 (7 tentativas)

// ─── Sessão ───────────────────────────────────
define('SESSION_NAME',     'wasomupfy_session');
define('SESSION_LIFETIME', 3600);  // 1 hora

// ─── E-mail (configurar quando tiver SMTP) ────
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USER',     '');  // Preencher
define('MAIL_PASS',     '');  // Preencher
define('MAIL_FROM',     'noreply@wasomupfy.com');
define('MAIL_FROM_NAME', APP_NAME);

// ─── Uploads ──────────────────────────────────
define('UPLOAD_PATH', __DIR__ . '/../../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// ─── Caminhos ─────────────────────────────────
define('AUTHENTIC_URL', APP_URL . '/authentic');
define('DASHBOARD_URL', APP_URL . '/dashboard');
