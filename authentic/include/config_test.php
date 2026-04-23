<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Configurações Globais
// Arquivo: authentic/include/config.php
// ══════════════════════════════════════════════

// ─── Banco de Dados ───────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'wasomupfy');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ─── Aplicação ────────────────────────────────
define('APP_NAME',    'Wasom Upfy');
define('APP_VERSION', '2.0');
define('APP_URL',     'https://wasomupfy.rf.gd'); // Mudar em produção
define('APP_URL_PANEL',     'dashboard');
define('APP_ENV',     'production');                 // 'development' = debug local | 'production' = envio real
define('VAPID_PUBLIC_KEY',  'BKGIW47bet8LzqCcTTV3B_pJLgUxA1xdJgtlYEU9LcJArBrZRmKipIlYblrVDMBX54bn-5T7hydeuXGB1NxGVl4'); // a chave Public Key
define('VAPID_PRIVATE_KEY', 'sEsX8fwLAmksOgTy0MlLrgVMaq5BnYGIDkKh7K5ok7s');   // a chave Private Key
define('VAPID_SUBJECT',     'mailto:suporte@wasomupfy.com');

// ─── Segurança de Login ───────────────────────
define('MAX_LOGIN_ATTEMPTS', 5);  // Tentativas antes do bloqueio nível 2
define('BLOCK_LEVEL_1_MIN', 5);   // Minutos para bloqueio nível 1 (3 tentativas)
define('BLOCK_LEVEL_2_MIN', 15);  // Minutos para bloqueio nível 2 (5 tentativas)
define('BLOCK_LEVEL_3_MIN', 30);  // Minutos para bloqueio nível 3 (7 tentativas)

// ─── Sessão ───────────────────────────────────
define('SESSION_NAME',     'wasomupfy_session');
define('SESSION_LIFETIME', 3600);  // 1 hora

// ─── E-mail — PHPMailer + SMTP ───────────────
// Instalar: composer require phpmailer/phpmailer
// Alternativa sem Composer: pasta vendor/phpmailer/ manual
define('MAIL_DRIVER',   'phpmailer');      // 'phpmailer' | 'native'
define('MAIL_HOST',     'smtp.gmail.com'); // Gmail, Outlook, etc.
define('MAIL_PORT',     587);              // 587 = TLS | 465 = SSL
define('MAIL_SECURE',   'tls');            // 'tls' | 'ssl'
define('MAIL_USER',     'wasomupfy@gmail.com');  // conta SMTP (ex: seuemail@gmail.com)
define('MAIL_PASS',     'prntkiazqafmoesg');
define('MAIL_FROM',     'wasomupfy@gmail.com');  // ← deve ser igual ao MAIL_USER no Gmail
define('MAIL_FROM_NAME', APP_NAME);
// ─── Modo debug de email ──────────────────────
// 0 = sem output | 1 = cliente | 2 = cliente+servidor
define('MAIL_DEBUG', 0);

// ─── Uploads ──────────────────────────────────
define('UPLOAD_PATH', __DIR__ . '/../../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// ─── Caminhos ─────────────────────────────────
define('AUTHENTIC_URL', APP_URL . '/authentic');
define('DASHBOARD_URL', APP_URL . '/dashboard');
// Data de vigência dos termos
define('PRIVACY_VERSION', '2.0');
define('PRIVACY_DATE',    '11 de Março de 2026');
define('TERMS_VERSION', '2.0');
define('TERMS_DATE',    '11 de Março de 2026');


// ─── Painel Admin ────────────────────────────
// ADMIN_PATH — caminho da pasta do painel.
// DEVE coincidir com:
//   1. O nome real da pasta no servidor (admin/)
//   2. O valor em _admin_config WHERE config_key='admin_path'
//   3. As rotas no .htaccess raiz (^ADMIN_PATH/...)
// Para rodar o caminho: alterar aqui + renomear pasta + executar
// o gerador de .htaccess em admin/pages/settings/security.php
define('ADMIN_PATH', 'wu-panel'); // Exemplo: 'admin' ou 'admin-panel' ou 'wu-panel'