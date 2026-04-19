<?php
require_once __DIR__ . '/../../include/platform_admin.php';

if ($admin_role !== 'super_admin') {
    adminRedirect('/' . ADMIN_PATH);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/' . ADMIN_PATH . '/settings/security');
}

if (!validateAdminCsrf($_POST['csrf_token'] ?? '')) {
    adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'error']);
}

$_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

$db = getDB();
$action = trim((string)($_POST['action'] ?? ''));

function adminConfigUpsert(PDO $db, string $key, string|int $value, int $adminId): void
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

function getAdminConfig(PDO $db, string $key, ?string $default = null): ?string
{
    $stmt = $db->prepare("SELECT config_value FROM _admin_config WHERE config_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string)$value;
}

function getAdminSection(string $htaccess): ?string
{
    if (preg_match('/# ##WASOM_ADMIN_START##.*?# ##WASOM_ADMIN_END##[^\r\n]*/s', $htaccess, $matches)) {
        return $matches[0];
    }
    return null;
}

function buildAdminSection(string $currentSection, string $fromPath, string $toPath): string
{
    $updated = $fromPath === $toPath ? $currentSection : str_replace($fromPath, $toPath, $currentSection);
    $header = '# Ultima actualizacao: ' . date('Y-m-d H:i:s') . ' | Caminho: ' . $toPath;
    $updated = preg_replace('/^# .*Caminho: .*$/m', $header, $updated, 1);
    return $updated ?? $currentSection;
}

function applyAdminSection(string $existing, string $newSection): string
{
    if (preg_match('/# ##WASOM_ADMIN_START##.*?# ##WASOM_ADMIN_END##[^\r\n]*/s', $existing)) {
        return (string)preg_replace('/# ##WASOM_ADMIN_START##.*?# ##WASOM_ADMIN_END##[^\r\n]*/s', trim($newSection), $existing, 1);
    }
    return rtrim($existing) . PHP_EOL . PHP_EOL . trim($newSection) . PHP_EOL;
}

function updateAdminPathInConfigContents(string $configContents, string $newPath): ?string
{
    $updated = preg_replace(
        "/define\('ADMIN_PATH',\s*'[^']*'\);/",
        "define('ADMIN_PATH', '" . addslashes($newPath) . "');",
        $configContents,
        1
    );

    if (!is_string($updated) || $updated === $configContents) {
        return null;
    }

    return $updated;
}

function ensureWhitelistHasActiveIp(PDO $db, int $adminId): void
{
    $activeCount = (int)$db->query("SELECT COUNT(*) FROM _admin_ip_whitelist WHERE active = 1")->fetchColumn();
    if ($activeCount > 0) {
        return;
    }

    $myIp = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($myIp === '' || !filter_var($myIp, FILTER_VALIDATE_IP)) {
        adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'wl_no_ip']);
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

switch ($action) {
    case 'change_path':
        $newPath = trim((string)($_POST['new_path'] ?? ''));
        if (!preg_match('/^[a-z0-9\-]{3,40}$/', $newPath)) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'path_invalid']);
        }

        $currentPath = ADMIN_PATH;
        if ($newPath === $currentPath) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security');
        }

        $baseDir = dirname(__DIR__, 3);
        $currentDir = $baseDir . DIRECTORY_SEPARATOR . $currentPath;
        $newDir = $baseDir . DIRECTORY_SEPARATOR . $newPath;
        $htaccessPath = $baseDir . DIRECTORY_SEPARATOR . '.htaccess';
        $configPath = $baseDir . DIRECTORY_SEPARATOR . 'authentic' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'config.php';

        if (file_exists($newDir)) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'path_exists']);
        }
        if (!is_dir($currentDir) || !is_writable($baseDir) || !is_writable($htaccessPath) || !is_writable($configPath)) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'no_write']);
        }

        $originalHtaccess = @file_get_contents($htaccessPath);
        $originalConfig = @file_get_contents($configPath);
        if ($originalHtaccess === false || $originalConfig === false) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'error']);
        }

        $currentSection = getAdminSection($originalHtaccess);
        if ($currentSection === null) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'error']);
        }

        $newSection = buildAdminSection($currentSection, $currentPath, $newPath);
        $newHtaccess = applyAdminSection($originalHtaccess, $newSection);
        $newConfig = updateAdminPathInConfigContents($originalConfig, $newPath);
        if ($newConfig === null) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'error']);
        }

        if (!@rename($currentDir, $newDir)) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'rename_failed']);
        }

        try {
            if (@file_put_contents($configPath, $newConfig) === false) {
                throw new RuntimeException('config_write_failed');
            }
            if (@file_put_contents($htaccessPath, $newHtaccess) === false) {
                throw new RuntimeException('htaccess_write_failed');
            }

            $db->beginTransaction();
            adminConfigUpsert($db, 'admin_path', $newPath, $admin_id);
            adminConfigUpsert($db, 'admin_path_prev', $currentPath, $admin_id);
            adminConfigUpsert($db, 'path_last_changed', date('Y-m-d H:i:s'), $admin_id);
            $db->commit();

            logAudit(
                $admin_id,
                null,
                'security.path_changed',
                '_admin_config',
                null,
                ['path' => $currentPath],
                ['path' => $newPath]
            );

            header('Location: ' . APP_URL . '/' . $newPath . '/settings/security?msg=path_changed');
            exit;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            @file_put_contents($configPath, $originalConfig);
            @file_put_contents($htaccessPath, $originalHtaccess);
            if (is_dir($newDir) && !is_dir($currentDir)) {
                @rename($newDir, $currentDir);
            }

            error_log('[SECURITY PATH CHANGE] ' . $e->getMessage());
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'error']);
        }

    case 'regen_htaccess':
        $baseDir = dirname(__DIR__, 3);
        $htaccessPath = $baseDir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_writable($htaccessPath)) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'no_write']);
        }

        $originalHtaccess = @file_get_contents($htaccessPath);
        if ($originalHtaccess === false) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'error']);
        }

        $currentSection = getAdminSection($originalHtaccess);
        if ($currentSection === null) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'error']);
        }

        $newSection = buildAdminSection($currentSection, ADMIN_PATH, ADMIN_PATH);
        $newHtaccess = applyAdminSection($originalHtaccess, $newSection);

        if (@file_put_contents($htaccessPath, $newHtaccess) === false) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'no_write']);
        }

        logAudit($admin_id, null, 'security.htaccess_regenerated', '_admin_config', null, null, null);
        adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'htaccess_ok']);

    case 'toggle_whitelist':
        $currentValue = getAdminConfig($db, 'ip_whitelist_on', '0') === '1' ? '1' : '0';
        $newValue = $currentValue === '1' ? '0' : '1';

        if ($newValue === '1') {
            ensureWhitelistHasActiveIp($db, $admin_id);
        }

        adminConfigUpsert($db, 'ip_whitelist_on', $newValue, $admin_id);
        logAudit(
            $admin_id,
            null,
            'security.whitelist_toggled',
            '_admin_config',
            null,
            ['ip_whitelist_on' => $currentValue],
            ['ip_whitelist_on' => $newValue]
        );

        adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'wl_saved']);

    case 'add_ip':
        $ipAddress = trim((string)($_POST['ip_address'] ?? ''));
        $label = trim((string)($_POST['label'] ?? ''));

        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'error']);
        }

        $db->prepare("
            INSERT INTO _admin_ip_whitelist (ip_address, label, added_by, active)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                label = VALUES(label),
                added_by = VALUES(added_by),
                active = 1
        ")->execute([$ipAddress, $label !== '' ? $label : null, $admin_id]);

        logAudit(
            $admin_id,
            null,
            'security.ip_added',
            '_admin_ip_whitelist',
            null,
            null,
            ['ip' => $ipAddress, 'label' => $label]
        );

        adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'ip_added']);

    case 'remove_ip':
        $ipId = (int)($_POST['ip_id'] ?? 0);
        if ($ipId <= 0) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'error']);
        }

        $stmt = $db->prepare("SELECT ip_address, active FROM _admin_ip_whitelist WHERE id_ip = ? LIMIT 1");
        $stmt->execute([$ipId]);
        $ipRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ipRow) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'error']);
        }

        $whitelistOn = getAdminConfig($db, 'ip_whitelist_on', '0') === '1';
        $myIp = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($whitelistOn && $ipRow['ip_address'] === $myIp) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'self_ip_blocked']);
        }

        if ($whitelistOn && (int)$ipRow['active'] === 1) {
            $activeCount = (int)$db->query("SELECT COUNT(*) FROM _admin_ip_whitelist WHERE active = 1")->fetchColumn();
            if ($activeCount <= 1) {
                adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'last_ip_blocked']);
            }
        }

        $db->prepare("DELETE FROM _admin_ip_whitelist WHERE id_ip = ?")->execute([$ipId]);
        logAudit($admin_id, null, 'security.ip_removed', '_admin_ip_whitelist', $ipId, ['ip' => $ipRow['ip_address']], null);
        adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'ip_removed']);

    case 'toggle_ip':
        $ipId = (int)($_POST['ip_id'] ?? 0);
        if ($ipId <= 0) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'error']);
        }

        $stmt = $db->prepare("SELECT ip_address, active FROM _admin_ip_whitelist WHERE id_ip = ? LIMIT 1");
        $stmt->execute([$ipId]);
        $ipRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ipRow) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'error']);
        }

        $currentActive = (int)$ipRow['active'];
        $nextActive = $currentActive === 1 ? 0 : 1;
        $whitelistOn = getAdminConfig($db, 'ip_whitelist_on', '0') === '1';
        $myIp = $_SERVER['REMOTE_ADDR'] ?? '';

        if ($whitelistOn && $nextActive === 0 && $ipRow['ip_address'] === $myIp) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'self_ip_blocked']);
        }

        if ($whitelistOn && $nextActive === 0) {
            $activeCount = (int)$db->query("SELECT COUNT(*) FROM _admin_ip_whitelist WHERE active = 1")->fetchColumn();
            if ($activeCount <= 1) {
                adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'last_ip_blocked']);
            }
        }

        $db->prepare("UPDATE _admin_ip_whitelist SET active = ? WHERE id_ip = ?")->execute([$nextActive, $ipId]);
        logAudit(
            $admin_id,
            null,
            'security.ip_toggled',
            '_admin_ip_whitelist',
            $ipId,
            ['active' => $currentActive],
            ['active' => $nextActive]
        );

        adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'ip_toggled']);

    default:
        adminRedirect('/' . ADMIN_PATH . '/settings/security');
}
