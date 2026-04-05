<?php
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'settings.view');

header('Content-Type: application/json; charset=utf-8');

function jOut(bool $ok, string $message, array $extra = [], int $status = 200): never
{
    http_response_code($status);
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jOut(false, 'Metodo nao permitido.', [], 405);
}

if (!validateAdminCsrf($_POST['csrf_token'] ?? '')) {
    jOut(false, 'Sessao expirada. Recarrega a pagina.', [], 403);
}

$db = getDB();
$action = trim((string)($_POST['action'] ?? ''));
$canEdit = hasPermission($admin_id, 'settings.edit');

function postText(string $key, ?string $default = ''): ?string
{
    if (!array_key_exists($key, $_POST)) {
        return $default;
    }
    return trim((string)$_POST[$key]);
}

function postInt(string $key, int $default = 0): int
{
    return array_key_exists($key, $_POST) ? (int)$_POST[$key] : $default;
}

function postFloat(string $key, float $default = 0.0): float
{
    return array_key_exists($key, $_POST) ? (float)$_POST[$key] : $default;
}

function requireSettingsEdit(bool $canEdit): void
{
    if (!$canEdit) {
        jOut(false, 'Nao tens permissao para editar configuracoes.', [], 403);
    }
}

function upsertAdminConfig(PDO $db, string $key, string|int|float $value, int $adminId): void
{
    $db->prepare("
        INSERT INTO _admin_config (config_key, config_value, updated_by, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            config_value = VALUES(config_value),
            updated_by = VALUES(updated_by),
            updated_at = NOW()
    ")->execute([$key, (string)$value, $adminId]);
}

function getAdminConfigValue(PDO $db, string $key, ?string $default = null): ?string
{
    $stmt = $db->prepare("SELECT config_value FROM _admin_config WHERE config_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string)$value;
}

function upsertSiteConfig(PDO $db, string $key, ?string $value): void
{
    $db->prepare("
        INSERT INTO _site_config (config_key, config_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
    ")->execute([$key, $value]);
}

function updatePlatform(PDO $db, array $fields, int $adminId): void
{
    if (empty($fields)) {
        return;
    }

    $fields['id_employees'] = $adminId;
    $sets = implode(', ', array_map(static fn(string $column) => "`{$column}` = ?", array_keys($fields)));
    $values = array_values($fields);
    $values[] = 1;

    $db->prepare("UPDATE _platform SET {$sets} WHERE id_platform = ?")->execute($values);
}

function cfgAudit(int $adminId, string $action, array $changed = []): void
{
    logAudit($adminId, null, $action, '_config', 0, null, $changed);
}

function parseDateTimeInput(?string $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    $ts = strtotime($value);
    if ($ts === false) {
        jOut(false, 'Data invalida.');
    }

    return date('Y-m-d H:i:s', $ts);
}

function validateEmailField(?string $value, string $label): ?string
{
    if ($value === null || $value === '') {
        return $value;
    }
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        jOut(false, $label . ' invalido.');
    }
    return $value;
}

function validateUrlField(?string $value, string $label): ?string
{
    if ($value === null || $value === '') {
        return $value;
    }
    if (!filter_var($value, FILTER_VALIDATE_URL)) {
        jOut(false, $label . ' invalido.');
    }
    return $value;
}

function ensureAtLeastOneWhitelistIp(PDO $db, int $adminId): void
{
    $activeCount = (int)$db->query("SELECT COUNT(*) FROM _admin_ip_whitelist WHERE active = 1")->fetchColumn();
    if ($activeCount > 0) {
        return;
    }

    $myIp = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($myIp === '' || !filter_var($myIp, FILTER_VALIDATE_IP)) {
        jOut(false, 'Nao foi possivel activar a whitelist sem um IP valido.');
    }

    $db->prepare("
        INSERT INTO _admin_ip_whitelist (ip_address, label, added_by, active)
        VALUES (?, 'Auto-adicionado ao activar', ?, 1)
        ON DUPLICATE KEY UPDATE
            active = 1,
            label = VALUES(label),
            added_by = VALUES(added_by)
    ")->execute([$myIp, $adminId]);
}

$editActions = ['save_section', 'add_ip', 'remove_ip', 'clear_old_logs', 'test_email'];
if (in_array($action, $editActions, true)) {
    requireSettingsEdit($canEdit);
}

if ($action === 'save_section') {
    $section = trim((string)($_POST['section'] ?? ''));

    try {
        $db->beginTransaction();

        switch ($section) {
            case 'geral':
                $siteName = postText('site_name', '');
                if ($siteName === '') {
                    jOut(false, 'O nome do site e obrigatorio.');
                }

                $siteMap = [
                    'site_name' => $siteName,
                    'site_url' => validateUrlField(postText('site_url', ''), 'URL do site'),
                    'support_email' => validateEmailField(postText('support_email', ''), 'Email de suporte'),
                    'info_email' => validateEmailField(postText('info_email', ''), 'Email informativo'),
                    'company_country' => postText('company_country', ''),
                    'company_city' => postText('company_city', ''),
                    'company_address' => postText('company_address', ''),
                    'company_phone' => postText('company_phone', ''),
                ];

                foreach ($siteMap as $key => $value) {
                    upsertSiteConfig($db, $key, $value);
                }

                $platformFields = [];
                $contactEmail = validateEmailField(postText('contact_email', ''), 'Email de contacto');
                if (array_key_exists('contact_email', $_POST)) {
                    $platformFields['contact_email'] = $contactEmail;
                }
                if (array_key_exists('stores_count', $_POST)) {
                    $platformFields['stores_count'] = max(1, postInt('stores_count', 150));
                }
                if (array_key_exists('platform_version', $_POST)) {
                    $platformFields['version'] = postText('platform_version', '') ?: '2.0';
                }

                updatePlatform($db, $platformFields, $admin_id);
                $db->commit();
                cfgAudit($admin_id, 'config.save.geral', array_merge($siteMap, $platformFields));
                jOut(true, 'Configuracoes gerais guardadas.');

            case 'dashboard':
                $allowedStatus = ['active', 'maintenance', 'blocked', 'unauthorized'];
                $status = postText('dashboard_status', '');
                if (!in_array($status, $allowedStatus, true)) {
                    jOut(false, 'Estado do dashboard invalido.');
                }

                $platformFields = [
                    'status' => $status,
                    'maintenance_msg' => postText('maintenance_msg', ''),
                    'maintenance_start' => parseDateTimeInput(postText('maintenance_start', '')),
                    'maintenance_end' => parseDateTimeInput(postText('maintenance_end', '')),
                    'allow_register' => array_key_exists('allow_register', $_POST) ? max(0, min(1, postInt('allow_register'))) : 1,
                    'allow_login' => array_key_exists('allow_login', $_POST) ? max(0, min(1, postInt('allow_login'))) : 1,
                ];

                updatePlatform($db, $platformFields, $admin_id);
                if (array_key_exists('session_timeout', $_POST)) {
                    upsertAdminConfig($db, 'session_timeout', max(5, postInt('session_timeout', 60)), $admin_id);
                }

                $db->commit();
                cfgAudit($admin_id, 'config.save.dashboard', $platformFields);
                jOut(true, 'Configuracoes do dashboard guardadas.');

            case 'site':
                $allowedStatus = ['active', 'maintenance', 'blocked', 'unauthorized'];
                $siteStatus = postText('site_status', '');
                if (!in_array($siteStatus, $allowedStatus, true)) {
                    jOut(false, 'Estado do site invalido.');
                }

                $platformFields = [
                    'site_status' => $siteStatus,
                    'site_maintenance_msg' => postText('site_maintenance_msg', ''),
                    'site_maintenance_start' => parseDateTimeInput(postText('site_maintenance_start', '')),
                    'site_maintenance_end' => parseDateTimeInput(postText('site_maintenance_end', '')),
                ];

                // ── Enviar notificações de fim de manutenção ─────────────────
                $wasMaintenance = ($siteStatus !== 'active');
                if ($wasMaintenance) {
                    $notifyEmails = $db->query("
                        SELECT DISTINCT email_notify 
                        FROM _maintenance_notify 
                        WHERE sent = 0
                    ")->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($notifyEmails)) {
                        $maintenanceUrl = APP_URL . '/status/maintenance.php';
                        $siteUrl = APP_URL . '/home';

                        foreach ($notifyEmails as $email) {
                            try {
                                $mailerPath = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';
                                if (file_exists($mailerPath)) {
                                    require_once $mailerPath;

                                    $mailer = new \Wasom\Mailer();
                                    $mailer->setFrom(cfg('support_email', 'wasomupfy@gmail.com'), scfg('site_name', 'Wasom Upfy'))
                                        ->addAddress($email)
                                        ->setSubject('✅ ' . scfg('site_name', 'Wasom Upfy') . ' está de volta!')
                                        ->setBody(
                                            '<div style="font-family:Arial,sans-serif;max-width:500px;margin:auto;padding:20px;background:#f8f9fa;border-radius:12px">'
                                                . '<h2 style="color:#22c55e">🎉 Site de volta ao activo!</h2>'
                                                . '<p>Obrigado por esperares! A manutenção está concluída e o site está 100% operacional.</p>'
                                                . '<p style="text-align:center;margin:24px 0">'
                                                . '<a href="' . $siteUrl . '" style="background:#FF0089;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700">👉 Aceder ao site</a>'
                                                . '</p>'
                                                . '<hr>'
                                                . '<p style="font-size:.85rem;color:#666">Esta mensagem foi enviada automaticamente. <a href="' . $maintenanceUrl . '">Não quero mais notificações</a></p>'
                                                . '</div>',
                                            'Site Wasom Upfy está de volta ao activo após manutenção.'
                                        );
                                    $mailer->send();

                                    // Marcar como enviado
                                    $db->prepare("UPDATE _maintenance_notify SET sent = 1 WHERE email_notify = ?")
                                        ->execute([$email]);
                                }
                            } catch (Throwable $e) {
                                error_log('[MAINTENANCE NOTIFY] Falha ao enviar para ' . $email . ': ' . $e->getMessage());
                            }
                        }

                        cfgAudit($admin_id, 'config.site_status.active_notified', ['count' => count($notifyEmails)]);
                    }
                }

                updatePlatform($db, $platformFields, $admin_id);

                $siteFields = [
                    'whatsapp_channel_url' => validateUrlField(postText('whatsapp_channel_url', ''), 'URL do canal WhatsApp'),
                    'instagram_url' => validateUrlField(postText('instagram_url', ''), 'URL do Instagram'),
                    'facebook_url' => validateUrlField(postText('facebook_url', ''), 'URL do Facebook'),
                    'youtube_url' => validateUrlField(postText('youtube_url', ''), 'URL do YouTube'),
                    'linkedin_url' => validateUrlField(postText('linkedin_url', ''), 'URL do LinkedIn'),
                    'threads_url' => validateUrlField(postText('threads_url', ''), 'URL do Threads'),
                    'twitter_url' => validateUrlField(postText('twitter_url', ''), 'URL do X/Twitter'),
                    'tiktok_url' => validateUrlField(postText('tiktok_url', ''), 'URL do TikTok'),
                    'youtube_tutorial_id' => postText('youtube_tutorial_id', ''),
                    'cookie_consent_enabled' => array_key_exists('cookie_consent_enabled', $_POST) ? (string)max(0, min(1, postInt('cookie_consent_enabled'))) : '0',
                    'maintenance_banner' => postText('maintenance_banner', ''),
                ];

                if (array_key_exists('whatsapp_number', $_POST)) {
                    $number = preg_replace('/\D+/', '', (string)postText('whatsapp_number', ''));
                    if ($number !== '' && !str_starts_with($number, '244')) {
                        $number = '244' . $number;
                    }
                    $siteFields['whatsapp_number'] = $number === '' ? '' : '+' . $number;
                }

                foreach ($siteFields as $key => $value) {
                    upsertSiteConfig($db, $key, $value);
                }

                $db->commit();
                cfgAudit($admin_id, 'config.save.site', array_merge($platformFields, $siteFields));
                jOut(true, 'Configuracoes do site publico guardadas.');

            case 'financeiro':
                $royalty = postFloat('royalty_percentage', -1);
                $fee = postFloat('platform_fee', -1);
                if ($royalty >= 0 && $fee >= 0 && abs(($royalty + $fee) - 100) > 0.01) {
                    jOut(false, 'Royalty e taxa da plataforma devem somar 100%.');
                }

                $currency = postText('currency_default', 'AOA');
                $allowedCurrencies = ['AOA', 'USD', 'EUR'];
                if (!in_array($currency, $allowedCurrencies, true)) {
                    $currency = 'AOA';
                }

                $platformFields = [];
                if (array_key_exists('royalty_percentage', $_POST)) {
                    $platformFields['royalty_percentage'] = max(0, min(100, $royalty));
                }
                if (array_key_exists('platform_fee', $_POST)) {
                    $platformFields['platform_fee'] = max(0, min(100, $fee));
                }
                if (array_key_exists('currency_default', $_POST)) {
                    $platformFields['currency_default'] = $currency;
                }
                if (array_key_exists('usd_to_aoa_rate', $_POST)) {
                    $platformFields['usd_to_aoa_rate'] = max(1, postFloat('usd_to_aoa_rate', 900));
                }

                updatePlatform($db, $platformFields, $admin_id);

                $adminFields = [
                    'payment_auto_approve_minutes' => max(5, postInt('payment_auto_approve_minutes', 30)),
                    'payment_intent_expiry_minutes' => max(15, postInt('payment_intent_expiry_minutes', 60)),
                    'payment_max_attempts' => max(1, postInt('payment_max_attempts', 3)),
                ];

                foreach ($adminFields as $key => $value) {
                    upsertAdminConfig($db, $key, $value, $admin_id);
                }

                $db->commit();
                cfgAudit($admin_id, 'config.save.financeiro', array_merge($platformFields, $adminFields));
                jOut(true, 'Configuracoes financeiras guardadas.');

            case 'seguranca':
                $adminFields = [
                    'max_login_attempts' => max(1, postInt('max_login_attempts', 5)),
                    'block_level_1_min' => max(1, postInt('block_level_1_min', 5)),
                    'block_level_2_min' => max(1, postInt('block_level_2_min', 15)),
                    'block_level_3_min' => max(1, postInt('block_level_3_min', 30)),
                    'session_timeout' => max(5, postInt('session_timeout', 60)),
                ];

                foreach ($adminFields as $key => $value) {
                    upsertAdminConfig($db, $key, $value, $admin_id);
                }

                $db->commit();
                cfgAudit($admin_id, 'config.save.seguranca', $adminFields);
                jOut(true, 'Configuracoes de seguranca guardadas.');

            case 'email':
                $fromAddress = validateEmailField(postText('mail_from_address', ''), 'Email remetente');
                $smtpPort = postInt('smtp_port', 587);
                if ($smtpPort < 1 || $smtpPort > 65535) {
                    jOut(false, 'Porta SMTP invalida.');
                }

                $encryption = postText('smtp_encryption', 'tls');
                if (!in_array($encryption, ['', 'tls', 'ssl'], true)) {
                    jOut(false, 'Tipo de encriptacao SMTP invalido.');
                }

                $adminFields = [
                    'smtp_host' => postText('smtp_host', ''),
                    'smtp_port' => $smtpPort,
                    'smtp_encryption' => $encryption,
                    'smtp_user' => postText('smtp_user', ''),
                    'mail_from_address' => $fromAddress,
                    'mail_from_name' => postText('mail_from_name', ''),
                    'mail_debug' => max(0, min(3, postInt('mail_debug', 0))),
                ];

                if (($password = postText('smtp_pass', '')) !== '') {
                    $adminFields['smtp_pass'] = $password;
                }

                foreach ($adminFields as $key => $value) {
                    upsertAdminConfig($db, $key, $value, $admin_id);
                }

                $db->commit();
                $auditFields = $adminFields;
                unset($auditFields['smtp_pass']);
                cfgAudit($admin_id, 'config.save.email', $auditFields);
                jOut(true, 'Configuracoes SMTP guardadas.');

            case 'integracoes':
                $adminFields = [
                    'vapid_public_key' => postText('vapid_public_key', ''),
                    'vapid_private_key' => postText('vapid_private_key', ''),
                    'vapid_subject' => postText('vapid_subject', ''),
                ];

                foreach ($adminFields as $key => $value) {
                    upsertAdminConfig($db, $key, $value, $admin_id);
                }

                $db->commit();
                cfgAudit($admin_id, 'config.save.integracoes', ['vapid_public_key' => $adminFields['vapid_public_key'], 'vapid_subject' => $adminFields['vapid_subject']]);
                jOut(true, 'Integracoes guardadas.');

            case 'whitelist_toggle':
                $enabled = array_key_exists('ip_whitelist_on', $_POST) ? '1' : '0';
                if ($enabled === '1') {
                    ensureAtLeastOneWhitelistIp($db, $admin_id);
                }
                upsertAdminConfig($db, 'ip_whitelist_on', $enabled, $admin_id);

                $db->commit();
                cfgAudit($admin_id, 'config.whitelist.' . ($enabled === '1' ? 'enabled' : 'disabled'), ['ip_whitelist_on' => $enabled]);
                jOut(true, 'Whitelist actualizada.');

            case 'logs':
                $logLevel = postText('log_level', 'warning');
                $allowedLevels = ['debug', 'info', 'warning', 'error'];
                if (!in_array($logLevel, $allowedLevels, true)) {
                    jOut(false, 'Nivel de log invalido.');
                }

                $adminFields = [
                    'log_retention_days' => max(7, postInt('log_retention_days', 90)),
                    'log_level' => $logLevel,
                ];
                foreach ($adminFields as $key => $value) {
                    upsertAdminConfig($db, $key, $value, $admin_id);
                }

                $db->commit();
                cfgAudit($admin_id, 'config.save.logs', $adminFields);
                jOut(true, 'Configuracoes de logs guardadas.');

            default:
                $db->rollBack();
                jOut(false, 'Seccao desconhecida.');
        }
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('[CONFIG SAVE] ' . $e->getMessage());
        jOut(false, 'Erro interno ao guardar configuracoes.');
    }
}

if ($action === 'test_email') {
    $mailerPath = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';
    if (!file_exists($mailerPath)) {
        error_log('[CONFIG TEST EMAIL] WasomMailer.php nao encontrado em: ' . $mailerPath);
        jOut(false, 'Nao foi possivel iniciar o envio de email.');
    }

    if (!class_exists('\Wasom\Mailer')) {
        require_once $mailerPath;
    }

    $host = postText('smtp_host', '') ?: getAdminConfigValue($db, 'smtp_host', MAIL_HOST) ?: MAIL_HOST;
    $port = postInt('smtp_port', 0);
    if ($port <= 0) {
        $port = (int)(getAdminConfigValue($db, 'smtp_port', (string)MAIL_PORT) ?: MAIL_PORT);
    }
    $secure = postText('smtp_encryption', '') ?: getAdminConfigValue($db, 'smtp_encryption', MAIL_SECURE) ?: MAIL_SECURE;
    $user = postText('smtp_user', '') ?: getAdminConfigValue($db, 'smtp_user', MAIL_USER) ?: MAIL_USER;
    $pass = postText('smtp_pass', '') ?: getAdminConfigValue($db, 'smtp_pass', MAIL_PASS) ?: MAIL_PASS;
    $from = validateEmailField(postText('mail_from_address', ''), 'Email remetente')
        ?: getAdminConfigValue($db, 'mail_from_address', MAIL_FROM)
        ?: MAIL_FROM;
    $fromName = postText('mail_from_name', '') ?: getAdminConfigValue($db, 'mail_from_name', MAIL_FROM_NAME) ?: MAIL_FROM_NAME;

    $stmt = $db->prepare("SELECT email_employees FROM _employees WHERE id_employees = ?");
    $stmt->execute([$admin_id]);
    $to = $stmt->fetchColumn() ?: $from;

    try {
        $mailer = new \Wasom\Mailer();
        $mailer->host = $host;
        $mailer->port = $port;
        $mailer->secure = $secure;
        $mailer->username = $user;
        $mailer->password = $pass;
        $mailer->debug = 0;
        $mailer->setFrom($from, $fromName)
            ->addAddress($to)
            ->setSubject('Teste de email - ' . APP_NAME . ' Admin')
            ->setBody(
                '<div style="font-family:Arial,sans-serif;max-width:500px;margin:auto">'
                    . '<div style="background:#FF0089;padding:20px;border-radius:10px 10px 0 0;text-align:center">'
                    . '<h2 style="color:#fff;margin:0">Email de teste</h2></div>'
                    . '<div style="background:#fff;padding:24px;border:1px solid #eee;border-radius:0 0 10px 10px">'
                    . '<p>O envio SMTP foi testado com sucesso.</p>'
                    . '<small style="color:#999">Enviado em ' . date('d/m/Y H:i') . '</small>'
                    . '</div></div>',
                'Teste de email do painel admin.'
            );
        $mailer->send();
        cfgAudit($admin_id, 'config.test_email.ok', ['to' => $to]);
        jOut(true, 'Email de teste enviado para ' . $to . '.');
    } catch (\Wasom\MailerException $e) {
        error_log('[CONFIG TEST EMAIL] ' . $e->getMessage());
        cfgAudit($admin_id, 'config.test_email.fail', ['to' => $to, 'error' => $e->getMessage()]);
        jOut(false, 'Falha ao enviar email de teste. Verifica as configuracoes SMTP.');
    }
}

if ($action === 'add_ip') {
    $ipAddress = postText('ip_address', '');
    $label = postText('label', '');

    if ($ipAddress === '' || !filter_var($ipAddress, FILTER_VALIDATE_IP)) {
        jOut(false, 'Endereco IP invalido.');
    }

    try {
        $db->prepare("
            INSERT INTO _admin_ip_whitelist (ip_address, label, added_by, active)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                label = VALUES(label),
                added_by = VALUES(added_by),
                active = 1
        ")->execute([$ipAddress, $label !== '' ? $label : null, $admin_id]);

        $stmt = $db->prepare("SELECT id_ip FROM _admin_ip_whitelist WHERE ip_address = ? LIMIT 1");
        $stmt->execute([$ipAddress]);
        $idIp = (int)$stmt->fetchColumn();

        cfgAudit($admin_id, 'config.whitelist.add_ip', ['ip' => $ipAddress, 'label' => $label]);
        jOut(true, 'IP adicionado.', ['id' => $idIp]);
    } catch (Throwable $e) {
        error_log('[WHITELIST ADD] ' . $e->getMessage());
        jOut(false, 'Erro ao adicionar IP.');
    }
}

if ($action === 'remove_ip') {
    $idIp = (int)($_POST['id_ip'] ?? $_POST['id_whitelist'] ?? 0);
    if ($idIp <= 0) {
        jOut(false, 'ID invalido.');
    }

    $stmt = $db->prepare("SELECT ip_address, active FROM _admin_ip_whitelist WHERE id_ip = ? LIMIT 1");
    $stmt->execute([$idIp]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$entry) {
        jOut(false, 'Entrada nao encontrada.');
    }

    $whitelistOn = getAdminConfigValue($db, 'ip_whitelist_on', '0') === '1';
    $myIp = $_SERVER['REMOTE_ADDR'] ?? '';

    if ($whitelistOn && $entry['ip_address'] === $myIp) {
        jOut(false, 'Nao podes remover o teu proprio IP enquanto a whitelist esta activa.');
    }

    if ($whitelistOn && (int)$entry['active'] === 1) {
        $activeCount = (int)$db->query("SELECT COUNT(*) FROM _admin_ip_whitelist WHERE active = 1")->fetchColumn();
        if ($activeCount <= 1) {
            jOut(false, 'A whitelist precisa de pelo menos um IP activo.');
        }
    }

    try {
        $db->prepare("DELETE FROM _admin_ip_whitelist WHERE id_ip = ?")->execute([$idIp]);
        cfgAudit($admin_id, 'config.whitelist.remove_ip', ['ip' => $entry['ip_address']]);
        jOut(true, 'IP removido da whitelist.');
    } catch (Throwable $e) {
        error_log('[WHITELIST REMOVE] ' . $e->getMessage());
        jOut(false, 'Erro ao remover IP.');
    }
}

if ($action === 'clear_old_logs') {
    $days = max(7, postInt('retention_days', 90));

    try {
        $stmt = $db->prepare("DELETE FROM _audit_log WHERE creat_log < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        $deleted = $stmt->rowCount();
        cfgAudit($admin_id, 'config.logs.cleared', ['days' => $days, 'deleted' => $deleted]);
        jOut(true, $deleted . ' registos eliminados.');
    } catch (Throwable $e) {
        error_log('[CLEAR LOGS] ' . $e->getMessage());
        jOut(false, 'Erro ao eliminar logs antigos.');
    }
}

jOut(false, 'Accao desconhecida.', [], 400);
