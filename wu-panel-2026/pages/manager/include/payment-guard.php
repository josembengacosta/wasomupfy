<?php

if (!function_exists('paymentPanelBaseUrl')) {
    function paymentPanelBaseUrl(): string
    {
        return APP_URL . '/' . ADMIN_PATH . '/manager';
    }
}

if (!function_exists('paymentPanelEnsureCsrf')) {
    function paymentPanelEnsureCsrf(): void
    {
        if (!isset($_SESSION['admin_csrf_token']) || !is_string($_SESSION['admin_csrf_token'])) {
            $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        }
    }
}

if (!function_exists('paymentPanelLogout')) {
    function paymentPanelLogout(): void
    {
        unset($_SESSION['payment_control_auth'], $_SESSION['biz_auth_time'], $_SESSION['biz_attempts']);
    }
}

if (!function_exists('paymentPanelExpireIfIdle')) {
    function paymentPanelExpireIfIdle(int $ttlSeconds = 14400): void
    {
        if (!empty($_SESSION['biz_auth_time']) && (time() - (int)$_SESSION['biz_auth_time']) > $ttlSeconds) {
            paymentPanelLogout();
        }
    }
}

if (!function_exists('paymentPanelTouch')) {
    function paymentPanelTouch(): void
    {
        $_SESSION['biz_auth_time'] = time();
    }
}

if (!function_exists('paymentPanelDefaultTarget')) {
    function paymentPanelDefaultTarget(): string
    {
        return paymentPanelBaseUrl() . '/gestion';
    }
}

if (!function_exists('paymentPanelSanitizeReturnTarget')) {
    function paymentPanelSanitizeReturnTarget(?string $target): string
    {
        $target = trim((string)$target);
        if ($target === '') {
            return paymentPanelDefaultTarget();
        }

        $basePath = rtrim((string)parse_url(APP_URL, PHP_URL_PATH), '/');
        $expectedPrefix = $basePath . '/' . ADMIN_PATH . '/manager/';
        if (str_starts_with($target, $expectedPrefix)) {
            return $target;
        }

        if (str_starts_with($target, paymentPanelBaseUrl() . '/')) {
            return $target;
        }

        return paymentPanelDefaultTarget();
    }
}

if (!function_exists('paymentPanelGetDefaultAccountForUser')) {
    function paymentPanelGetDefaultAccountForUser(PDO $db, int $userId): array|null
    {
        $stmt = $db->prepare("SELECT * FROM _account WHERE id_users = ? AND is_default = 1 LIMIT 1");
        $stmt->execute([$userId]);
        $acc = $stmt->fetch(PDO::FETCH_ASSOC);
        return $acc ?: null;
    }
}

if (!function_exists('paymentPanelCurrentTarget')) {
    function paymentPanelCurrentTarget(): string
    {
        return paymentPanelSanitizeReturnTarget($_SERVER['REQUEST_URI'] ?? '');
    }
}

if (!function_exists('paymentPanelVerifyAccessCode')) {
    function paymentPanelVerifyAccessCode(PDO $db, int $adminId, string $code): bool
    {
        $stmt = $db->prepare("SELECT access_code FROM _employees_security WHERE id_employees = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return !empty($row['access_code']) && hash_equals((string)$row['access_code'], trim($code));
    }
}

if (!function_exists('paymentPanelRequireAccess')) {
    function paymentPanelRequireAccess(): void
    {
        paymentPanelEnsureCsrf();
        paymentPanelExpireIfIdle();

        if (empty($_SESSION['payment_control_auth'])) {
            header('Location: ' . paymentPanelDefaultTarget());
            exit;
        }

        paymentPanelTouch();
    }
}

if (!function_exists('paymentPanelRequireAccessJson')) {
    function paymentPanelRequireAccessJson(): bool
    {
        paymentPanelEnsureCsrf();
        paymentPanelExpireIfIdle();

        if (empty($_SESSION['payment_control_auth'])) {
            return false;
        }

        paymentPanelTouch();
        return true;
    }
}