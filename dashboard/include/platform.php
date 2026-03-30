<?php
// ══════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Dashboard Runtime Layer
// Arquivo: dashboard/include/platform.php
//
// ARQUITECTURA:
//   authentic/include/functions.php  → auth / segurança / DB
//   dashboard/include/platform.php   → lógica de runtime do dashboard
//   include/site.php                 → lógica do site público
//
// DEPENDÊNCIAS:
//   Requer functions.php já incluído (usa getDB()).
//
// INCLUIR logo após functions.php em cada página do dashboard:
//   require_once __DIR__ . '//dashboard/authentic/include/functions.php';
//   require_once __DIR__ . '/http://localhost/wasomupfy/dashboard/include/platform.php';
//   startSecureSession();
//   checkRememberMe();
//   requireLogin();
//   checkDashboardStatus();
//   $user = checkUserAccess($_SESSION['id_users']);
// ══════════════════════════════════════════════════════════════════


// ══════════════════════════════════════════════════════════════════
// 1. _platform — estado e configuração do DASHBOARD
//    Lê a coluna `status` (não site_status — essa é do site público).
//    Auto-expiry: se maintenance/blocked e maintenance_end já passou,
//    restaura automaticamente para 'active'.
// ══════════════════════════════════════════════════════════════════
function getDashboardPlatform(): array
{
    static $p = null;
    if ($p !== null) return $p;

    try {
        $db = getDB();
        $p  = $db->query("SELECT * FROM _platform ORDER BY id_platform ASC LIMIT 1")->fetch();
    } catch (Throwable $e) {
        error_log('[getDashboardPlatform] BD indisponível: ' . $e->getMessage());
    }

    // Defaults seguros se BD estiver em baixo
    if (!$p) {
        $p = [
            'status'                  => 'active',
            'site_status'             => 'active',
            'allow_register'          => 1,
            'allow_login'             => 1,
            'royalty_percentage'      => 90.00,
            'platform_fee'            => 10.00,
            'currency_default'        => 'AOA',
            'usd_to_aoa_rate'         => 900.00,
            'contact_email'           => 'suporte@wasomupfy.com',
            'stores_count'            => 150,
            'version'                 => '2.0',
            'maintenance_msg'         => null,
            'maintenance_start'       => null,
            'maintenance_end'         => null,
            'maintenance_services'    => null,
        ];
        return $p;
    }

    // ── Auto-expiry do DASHBOARD (coluna `status`) ────────────────
    // Não toca em site_status — são independentes.
    $expirable = ['maintenance', 'blocked'];
    if (
        in_array($p['status'], $expirable, true) &&
        !empty($p['maintenance_end']) &&
        strtotime($p['maintenance_end']) <= time()
    ) {
        try {
            $db->prepare("
                UPDATE _platform SET
                    status                = 'active',
                    maintenance_msg       = NULL,
                    maintenance_start     = NULL,
                    maintenance_end       = NULL,
                    maintenance_services  = NULL,
                    modif_platform        = NOW()
                WHERE id_platform = ?
            ")->execute([$p['id_platform']]);

            $p['status']               = 'active';
            $p['maintenance_msg']      = null;
            $p['maintenance_start']    = null;
            $p['maintenance_end']      = null;
            $p['maintenance_services'] = null;

            error_log('[getDashboardPlatform] Auto-expiry: status restaurado para active.');
        } catch (Throwable $e) {
            error_log('[getDashboardPlatform] Auto-expiry falhou: ' . $e->getMessage());
        }
    }

    return $p;
}


// ══════════════════════════════════════════════════════════════════
// 2. Verificar estado do dashboard + redirecionar se necessário
//    Chamar logo após requireLogin() em todas as páginas.
//
//    $current_page — identificador da página actual para evitar
//    loops em páginas de estado (ex: 'maintenance', '403', etc.).
// ══════════════════════════════════════════════════════════════════
function checkDashboardStatus(string $current_page = ''): array
{
    // Páginas de estado nunca redireccionam (evita loop infinito)
    $status_pages = ['maintenance', '403', '404', '500', '503', 'unauthorized'];
    if (in_array($current_page, $status_pages, true)) {
        return getDashboardPlatform();
    }

    $p  = getDashboardPlatform();
    $st = $p['status'] ?? 'active';

    if ($st === 'maintenance') {
        header('Location: ' . APP_URL . '/' . APP_URL_PANEL . '/status/maintenance');
        exit;
    }

    if ($st === 'blocked') {
        header('Location: ' . APP_URL . '/' . APP_URL_PANEL . '/status/503');
        exit;
    }

    if ($st === 'unauthorized') {
        header('Location: ' . APP_URL . '/' . APP_URL_PANEL . '/status/403');
        exit;
    }

    return $p;
}


// ══════════════════════════════════════════════════════════════════
// 3. Estado do utilizador
//    Lê _users + _user_plan e devolve um array enriquecido com
//    campos calculados: plan_active, plan_days_left, etc.
//    Verifica também expiração de plano e actualiza status_user.
// ══════════════════════════════════════════════════════════════════
function getUserStatus(int $id_users): ?array
{
    try {
        $db   = getDB();
        // NOTA: _user_plan não tem coluna `status`.
        // Usamos subquery para apanhar apenas o registo mais recente da _user_plan.
        $stmt = $db->prepare("
            SELECT
                u.*,
                p.name_plan,
                p.type_plan,
                p.price_plan,
                p.billing_plan,
                up.releases_used,
                up.releases_limit,
                up.started_at  AS plan_started_at,
                up.expires_at  AS plan_expires_at_up
            FROM _users u
            LEFT JOIN _plans p
                ON p.id_plan = u.plan_selected
            LEFT JOIN _user_plan up
                ON up.id_users = u.id_users
               AND up.started_at = (
                       SELECT MAX(started_at) FROM _user_plan
                       WHERE id_users = u.id_users
                   )
            WHERE u.id_users = ?
            LIMIT 1
        ");
        $stmt->execute([$id_users]);
        $user = $stmt->fetch();

        // Fallback: se o JOIN falhou (ex: utilizador sem registo em _user_plan),
        // buscar apenas os dados base do utilizador e devolver com defaults seguros.
        if (!$user) {
            $user = getUserById($id_users);
            if (!$user) return null;
            $user['plan_active']    = false;
            $user['plan_days_left'] = null;
            $user['plan_expired']   = false;
            $user['name_plan']      = null;
            $user['type_plan']      = null;
            $user['price_plan']     = null;
            $user['billing_plan']   = null;
            $user['releases_used']  = 0;
            $user['releases_limit'] = 0;
            return $user;
        }

        // ── Campos calculados ──────────────────────────────────────
        $now = time();

        // Data de expiração: preferir plan_expires_at da _users
        $expires_ts = !empty($user['plan_expires_at'])
            ? strtotime($user['plan_expires_at'])
            : (!empty($user['plan_expires_at_up']) ? strtotime($user['plan_expires_at_up']) : null);

        $user['plan_active']    = false;
        $user['plan_days_left'] = null;
        $user['plan_expired']   = false;

        if ($expires_ts !== null) {
            $user['plan_days_left'] = (int)ceil(($expires_ts - $now) / 86400);
            $user['plan_active']    = ($expires_ts > $now) && ($user['status_user'] === 'active');
            $user['plan_expired']   = $expires_ts <= $now;
        } elseif (!empty($user['plan_selected'])) {
            // Plano por lançamento (per_release) — sem data de expiração
            $user['plan_active']  = ($user['status_user'] === 'active');
            $user['plan_expired'] = false;
        }

        // ── Auto-expiração do plano ───────────────────────────────
        // Se o plano expirou e o status_user ainda está 'active',
        // actualiza para 'pending_plan' automaticamente.
        if (
            $user['plan_expired'] &&
            $user['status_user'] === 'active' &&
            !empty($user['plan_selected'])
        ) {
            try {
                $db->prepare("
                    UPDATE _users
                    SET status_user = 'pending_plan', modif_user = NOW()
                    WHERE id_users = ?
                ")->execute([$id_users]);
                $user['status_user'] = 'pending_plan';
                $user['plan_active'] = false;
            } catch (Throwable $e) {
                error_log('[getUserStatus] Auto-expiração plano falhou: ' . $e->getMessage());
            }
        }

        return $user;
    } catch (Throwable $e) {
        error_log('[getUserStatus] ' . $e->getMessage());
        return null;
    }
}

// ══════════════════════════════════════════════════════════════════
// 4. Presença do utilizador no dashboard
//    Mantém _user_presence actualizado com último page load/ping.
//    O backend faz o update inicial e o JS mantém a sessão viva.
// ══════════════════════════════════════════════════════════════════
function wuf_detect_ua(string $ua): array
{
    if ($ua === '') {
        return [
            'device_type' => 'unknown',
            'browser'     => null,
        ];
    }

    $ua_lower = strtolower($ua);
    $is_bot   = str_contains($ua_lower, 'bot')
        || str_contains($ua_lower, 'crawler')
        || str_contains($ua_lower, 'spider');

    $device = 'desktop';
    if ($is_bot) {
        // O schema actual não aceita "bot" em _user_presence.device_type.
        $device = 'unknown';
    } elseif (preg_match('/tablet|ipad/i', $ua)) {
        $device = 'tablet';
    } elseif (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $ua)) {
        $device = 'mobile';
    }

    $browser = null;
    if (preg_match('/Edg(?:e|A|iOS)?\/([0-9.]+)/i', $ua)) {
        $browser = 'edge';
    } elseif (preg_match('/Firefox\/([0-9.]+)/i', $ua)) {
        $browser = 'firefox';
    } elseif (preg_match('/OPR\/([0-9.]+)/i', $ua)) {
        $browser = 'opera';
    } elseif (preg_match('/Chrome\/([0-9.]+)/i', $ua)) {
        $browser = 'chrome';
    } elseif (preg_match('/Version\/([0-9.]+).*Safari/i', $ua)) {
        $browser = 'safari';
    } elseif ($is_bot) {
        $browser = 'bot';
    }

    return [
        'device_type' => $device,
        'browser'     => $browser,
    ];
}

function wuf_infer_presence_activity(string $path): string
{
    $path = strtolower($path);

    return match (true) {
        str_contains($path, 'releases')     => 'releases',
        str_contains($path, 'finances')     => 'finances',
        str_contains($path, 'withdraw')     => 'finances',
        str_contains($path, 'artists')      => 'artists',
        str_contains($path, 'analytics')    => 'analytics',
        str_contains($path, 'statistics')   => 'analytics',
        str_contains($path, 'profile')      => 'profile',
        str_contains($path, 'settings')     => 'settings',
        str_contains($path, 'support')      => 'support',
        str_contains($path, 'notification') => 'notifications',
        default                             => 'dashboard',
    };
}

function updateUserPresence(
    int $id_users,
    string $last_page = '',
    string $activity_type = '',
    string $online_status = 'online',
    ?string $session_token = null
): void {
    try {
        $db = getDB();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        $allowed_statuses = ['online', 'away', 'busy', 'invisible', 'offline'];
        if (!in_array($online_status, $allowed_statuses, true)) {
            $online_status = 'online';
        }

        if ($last_page === '') {
            $last_page = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
        }

        if ($activity_type === '') {
            $activity_type = wuf_infer_presence_activity($last_page);
        }

        $session_token = $session_token ?: ($_SESSION['session_token'] ?? null);
        $ua_data       = wuf_detect_ua($ua);
        $session_start = $online_status === 'offline' ? null : date('Y-m-d H:i:s');

        $db->prepare("
            INSERT INTO _user_presence
                (id_users, online_status, last_activity, last_activity_type,
                 last_page, session_token, ip_address, user_agent,
                 device_type, browser, session_start, session_duration)
            VALUES
                (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ON DUPLICATE KEY UPDATE
                online_status      = VALUES(online_status),
                last_activity      = NOW(),
                last_activity_type = COALESCE(VALUES(last_activity_type), last_activity_type),
                last_page          = COALESCE(VALUES(last_page), last_page),
                session_token      = COALESCE(VALUES(session_token), session_token),
                ip_address         = VALUES(ip_address),
                user_agent         = VALUES(user_agent),
                device_type        = VALUES(device_type),
                browser            = VALUES(browser),
                session_start      = CASE
                    WHEN VALUES(online_status) = 'offline' THEN session_start
                    WHEN session_token IS NULL AND VALUES(session_token) IS NOT NULL THEN NOW()
                    WHEN session_token IS NOT NULL AND VALUES(session_token) IS NOT NULL
                         AND session_token <> VALUES(session_token) THEN NOW()
                    WHEN online_status = 'offline' THEN NOW()
                    ELSE COALESCE(session_start, NOW())
                END,
                session_duration   = CASE
                    WHEN last_activity IS NULL THEN session_duration
                    ELSE session_duration + GREATEST(0, TIMESTAMPDIFF(SECOND, last_activity, NOW()))
                END,
                modif_presence     = NOW()
        ")->execute([
            $id_users,
            $online_status,
            $activity_type ?: null,
            $last_page ?: null,
            $session_token,
            $ip,
            $ua ?: null,
            $ua_data['device_type'],
            $ua_data['browser'],
            $session_start,
        ]);
    } catch (Throwable $e) {
        error_log('[updateUserPresence] ' . $e->getMessage());
    }
}

function markUserOffline(
    int $id_users,
    string $activity_type = 'logout',
    string $last_page = '/dashboard/logout'
): void
{
    try {
        getDB()->prepare("
            UPDATE _user_presence
            SET online_status      = 'offline',
                last_activity      = NOW(),
                last_activity_type = ?,
                last_page          = ?,
                session_duration   = session_duration + GREATEST(0, TIMESTAMPDIFF(SECOND, last_activity, NOW())),
                modif_presence     = NOW()
            WHERE id_users = ?
        ")->execute([$activity_type, $last_page, $id_users]);
    } catch (Throwable $e) {
        error_log('[markUserOffline] ' . $e->getMessage());
    }
}

function wuf_register_dashboard_session(int $id_users): ?string
{
    $session_token = $_SESSION['session_token'] ?? null;
    if (is_string($session_token) && $session_token !== '') {
        return $session_token;
    }

    try {
        $session_token = bin2hex(random_bytes(32));
        $db = getDB();
        $db->prepare("
            INSERT INTO _users_sessions
                (id_users, session_token, ip_address, user_agent, is_active, last_activity)
            VALUES
                (?, ?, ?, ?, 1, NOW())
        ")->execute([
            $id_users,
            $session_token,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        $_SESSION['session_token'] = $session_token;
        return $session_token;
    } catch (Throwable $e) {
        error_log('[wuf_register_dashboard_session] ' . $e->getMessage());
        return null;
    }
}

function wuf_touch_dashboard_session(?string $session_token = null): void
{
    $session_token = $session_token ?: ($_SESSION['session_token'] ?? null);
    if (!is_string($session_token) || $session_token === '') {
        return;
    }

    try {
        getDB()->prepare("
            UPDATE _users_sessions
            SET last_activity = NOW()
            WHERE session_token = ? AND is_active = 1
        ")->execute([$session_token]);
    } catch (Throwable $e) {
        error_log('[wuf_touch_dashboard_session] ' . $e->getMessage());
    }
}

function wuf_is_dashboard_session_active(int $id_users, ?string $session_token = null): bool
{
    $session_token = $session_token ?: ($_SESSION['session_token'] ?? null);
    if (!is_string($session_token) || $session_token === '') {
        return false;
    }

    try {
        $stmt = getDB()->prepare("
            SELECT is_active
            FROM _users_sessions
            WHERE id_users = ? AND session_token = ?
            LIMIT 1
        ");
        $stmt->execute([$id_users, $session_token]);
        $row = $stmt->fetch();

        return $row && (int)$row['is_active'] === 1;
    } catch (Throwable $e) {
        error_log('[wuf_is_dashboard_session_active] ' . $e->getMessage());
        return true;
    }
}

function wuf_destroy_dashboard_session_locally(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => 1,
            'path'     => $params['path'] ?: '/',
            'domain'   => $params['domain'] ?? '',
            'secure'   => (bool)($params['secure'] ?? (APP_ENV === 'production')),
            'httponly' => (bool)($params['httponly'] ?? true),
            'samesite' => $params['samesite'] ?? 'Strict',
        ]);
    }

    if (isset($_COOKIE['wuf_remember'])) {
        setcookie('wuf_remember', '', [
            'expires'  => 1,
            'path'     => '/',
            'secure'   => (APP_ENV === 'production'),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function wuf_force_dashboard_logout(int $id_users, string $notice = 'session'): void
{
    markUserOffline($id_users, 'forced_logout', '/dashboard/session-expired');
    wuf_destroy_dashboard_session_locally();
    redirect('/login', ['notice' => $notice]);
}

function wuf_validate_dashboard_session(int $id_users, bool $redirect_on_fail = true): bool
{
    $session_token = wuf_register_dashboard_session($id_users);
    if ($session_token === null) {
        return true;
    }

    if (!wuf_is_dashboard_session_active($id_users, $session_token)) {
        if ($redirect_on_fail) {
            wuf_force_dashboard_logout($id_users);
        }

        markUserOffline($id_users, 'forced_logout', '/dashboard/session-expired');
        wuf_destroy_dashboard_session_locally();
        return false;
    }

    wuf_touch_dashboard_session($session_token);
    return true;
}


// ══════════════════════════════════════════════════════════════════
// 5. Verificar acesso do utilizador ao dashboard
//    Chama getUserStatus() e redireciona conforme o estado.
//    Devolve o array do utilizador para uso imediato na página.
// ══════════════════════════════════════════════════════════════════
function checkUserAccess(int $id_users): array
{
    if (!wuf_validate_dashboard_session($id_users, true)) {
        exit;
    }

    $user = getUserStatus($id_users);

    // Se getUserStatus() falhou (ex: query com JOIN a lançar excepção),
    // tentar getUserById() como fallback antes de destruir a sessão.
    if (!$user) {
        $user = getUserById($id_users);
    }

    if (!$user) {
        // Utilizador genuinamente não encontrado — sessão inválida
        session_destroy();
        header('Location: ' . APP_URL . '/' . APP_URL_PANEL . '/status/unauthorized');
        exit;
    }

    $st = $user['status_user'] ?? 'active';

    if ($st === 'suspended' || $st === 'banned') {
        header('Location: ' . APP_URL . '/' . APP_URL_PANEL . '/status/403');
        exit;
    }

    if ($st === 'inactive') {
        session_destroy();
        header('Location: ' . APP_URL . '/' . APP_URL_PANEL . '/status/unauthorized');
        exit;
    }

    // Garantir que os campos calculados existem mesmo vindo do fallback
    $user += [
        'plan_active'    => false,
        'plan_days_left' => null,
        'plan_expired'   => false,
        'name_plan'      => null,
        'releases_used'  => 0,
        'releases_limit' => 0,
    ];

    // pending_plan e pending_verification → deixar aceder mas
    // getDashboardAlerts() vai mostrar avisos na UI
    updateUserPresence((int)$user['id_users']);

    return $user;
}


// ══════════════════════════════════════════════════════════════════
// 6. Alertas do dashboard
//    Gera lista de avisos contextuais para mostrar no topo das
//    páginas do dashboard. Cada alerta tem:
//      type    → 'warning' | 'danger' | 'info' | 'success'
//      icon    → classe Bootstrap Icons
//      message → texto do aviso
//      action  → ['label' => '...', 'url' => '...'] ou null
//      dismiss → true/false (pode ser fechado pelo utilizador)
// ══════════════════════════════════════════════════════════════════
function getDashboardAlerts(array $user, array $platform): array
{
    $alerts = [];

    // ── Plano expirado ─────────────────────────────────────────────
    if (!empty($user['plan_expired']) && !empty($user['plan_selected'])) {
        $alerts[] = [
            'type'    => 'danger',
            'icon'    => 'bi-exclamation-triangle-fill',
            'message' => 'O teu plano expirou. Renova agora para continuar a distribuir música.',
            'action'  => ['label' => 'Renovar Plano', 'url' => APP_URL . '/' . APP_URL_PANEL . '/overview'],
            'dismiss' => false,
        ];
    }

    // ── Plano a expirar em breve (≤ 7 dias) ───────────────────────
    elseif (
        isset($user['plan_days_left']) &&
        $user['plan_days_left'] !== null &&
        $user['plan_days_left'] > 0 &&
        $user['plan_days_left'] <= 7
    ) {
        $dias = $user['plan_days_left'];
        $alerts[] = [
            'type'    => 'warning',
            'icon'    => 'bi-clock-fill',
            'message' => "O teu plano expira em {$dias} " . ($dias === 1 ? 'dia' : 'dias') . '. Renova para não interromper os teus lançamentos.',
            'action'  => ['label' => 'Renovar', 'url' => APP_URL . '/' . APP_URL_PANEL . '/overview'],
            'dismiss' => true,
        ];
    }

    // ── Sem plano activo ───────────────────────────────────────────
    if (
        ($user['status_user'] === 'pending_plan' || empty($user['plan_selected'])) &&
        empty($user['plan_expired']) // não duplicar com o alerta de expirado
    ) {
        $alerts[] = [
            'type'    => 'info',
            'icon'    => 'bi-star-fill',
            'message' => 'Ainda não tens um plano activo. Escolhe um plano para começar a distribuir.',
            'action'  => ['label' => 'Ver Planos', 'url' => APP_URL . '/' . APP_URL_PANEL . '/overview'],
            'dismiss' => false,
        ];
    }

    // ── Conta bancária não verificada (para quem já tem saldo) ────
    // (só mostrar se o utilizador já tem histórico de ganhos)
    try {
        $db    = getDB();
        $wallet = $db->prepare("SELECT balance_aoa FROM _wallet WHERE id_users = ? LIMIT 1");
        $wallet->execute([$user['id_users']]);
        $w = $wallet->fetch();

        if ($w && (float)$w['balance_aoa'] > 0) {
            $acc = $db->prepare("
                SELECT id_account FROM _account
                WHERE id_users = ? AND status_account = 'verified'
                LIMIT 1
            ");
            $acc->execute([$user['id_users']]);
            if (!$acc->fetch()) {
                $alerts[] = [
                    'type'    => 'warning',
                    'icon'    => 'bi-bank',
                    'message' => 'Tens saldo disponível mas não tens uma conta bancária verificada para levantamentos.',
                    'action'  => ['label' => 'Adicionar Conta', 'url' => APP_URL . '/' . APP_URL_PANEL . '/account/manage-account'],
                    'dismiss' => true,
                ];
            }
        }
    } catch (Throwable $e) {
        error_log('[getDashboardAlerts] wallet check falhou: ' . $e->getMessage());
    }

    // ── Onboarding não concluído ───────────────────────────────────
    if (empty($user['onboarding_done'])) {
        $alerts[] = [
            'type'    => 'info',
            'icon'    => 'bi-person-check-fill',
            'message' => 'Completa o teu perfil para desbloquear todas as funcionalidades.',
            'action'  => ['label' => 'Completar Perfil', 'url' => APP_URL . '/' . APP_URL_PANEL . '/user/profile'],
            'dismiss' => true,
        ];
    }

    // ── Trust score baixo ──────────────────────────────────────────
    if (isset($user['trust_score']) && (int)$user['trust_score'] < 30) {
        $alerts[] = [
            'type'    => 'danger',
            'icon'    => 'bi-shield-exclamation',
            'message' => 'A tua conta tem um índice de confiança baixo. Contacta o suporte para mais informações.',
            'action'  => ['label' => 'Contactar Suporte', 'url' => APP_URL . '/' . APP_URL_PANEL . '/page/support'],
            'dismiss' => false,
        ];
    }

    // ── Plataforma em manutenção agendada (aviso antecipado) ───────
    // Mostrar aviso se manutenção está agendada para as próximas 2h
    $st = $platform['status'] ?? 'active';
    if (
        $st === 'active' &&
        !empty($platform['maintenance_start']) &&
        strtotime($platform['maintenance_start']) > time() &&
        strtotime($platform['maintenance_start']) - time() <= 7200 // 2 horas
    ) {
        $inicio = date('H:i', strtotime($platform['maintenance_start']));
        $alerts[] = [
            'type'    => 'warning',
            'icon'    => 'bi-tools',
            'message' => "Manutenção programada para as {$inicio}. O dashboard ficará temporariamente indisponível.",
            'action'  => null,
            'dismiss' => true,
        ];
    }

    return $alerts;
}

// ══════════════════════════════════════════════════════════════════
// 7. Renderizar alertas no HTML
//    Chama getDashboardAlerts() e imprime o HTML dos avisos.
//    Incluir logo após o header/navbar em cada página:
//       renderDashboardAlerts($user, $platform);
// ══════════════════════════════════════════════════════════════════

function renderDashboardAlerts(array $user, array $platform): void
{
    $alerts = getDashboardAlerts($user, $platform);
    if (empty($alerts)) return;

    $colorMap = [
        'danger'  => ['bg' => 'rgba(239,68,68,.08)',  'border' => 'rgba(239,68,68,.25)',  'text' => '#ef4444'],
        'warning' => ['bg' => 'rgba(234,179,8,.08)',  'border' => 'rgba(234,179,8,.25)',  'text' => '#eab308'],
        'info'    => ['bg' => 'rgba(99,102,241,.08)', 'border' => 'rgba(99,102,241,.25)', 'text' => '#6366f1'],
        'success' => ['bg' => 'rgba(34,197,94,.08)',  'border' => 'rgba(34,197,94,.25)',  'text' => '#22c55e'],
    ];

    // Injectar CSS de tema uma única vez por página
    static $css_injected = false;
    if (!$css_injected) {
        echo '<style>
.wu-alert-msg {
    flex: 1;
    line-height: 1.6;
    /* Light: texto escuro legível */
    color: #1e1e2e;
}
.wu-alert-dismiss {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    line-height: 1;
    padding: 0;
    flex-shrink: 0;
    /* Light: cinza visível */
    color: #6b7280;
    transition: color .2s;
}
.wu-alert-dismiss:hover { color: #374151; }

/* Dark mode — texto claro */
body.dark-mode .wu-alert-msg,
[data-theme="dark"] .wu-alert-msg,
.dark .wu-alert-msg {
    color: rgba(232,232,240,.85);
}
body.dark-mode .wu-alert-dismiss,
[data-theme="dark"] .wu-alert-dismiss,
.dark .wu-alert-dismiss {
    color: rgba(255,255,255,.35);
}
body.dark-mode .wu-alert-dismiss:hover,
[data-theme="dark"] .wu-alert-dismiss:hover {
    color: rgba(255,255,255,.7);
}
</style>';
        $css_injected = true;
    }

    echo '<div class="wu-alerts-wrap" id="wuAlertsWrap" style="display:flex;flex-direction:column;gap:8px;margin-bottom:1.2rem;">';

    foreach ($alerts as $i => $alert) {
        $c   = $colorMap[$alert['type']] ?? $colorMap['info'];
        $id  = 'wuAlert' . $i;

        echo "<div id=\"{$id}\" style=\""
            . "display:flex;align-items:flex-start;gap:10px;"
            . "background:{$c['bg']};"
            . "border:1px solid {$c['border']};"
            . "border-radius:12px;padding:.75rem 1rem;"
            . "font-size:.83rem;"
            . "transition:opacity .3s;\">";

        // Ícone — sempre na cor do tipo de alerta
        echo "<i class=\"bi {$alert['icon']}\" style=\"font-size:1rem;flex-shrink:0;margin-top:2px;color:{$c['text']};\"></i>";

        // Texto — usa classe para adaptar ao tema
        echo '<span class="wu-alert-msg">' . $alert['message'];
        if (!empty($alert['action'])) {
            $label = htmlspecialchars($alert['action']['label']);
            $url   = htmlspecialchars($alert['action']['url']);
            echo " <a href=\"{$url}\" style=\"color:{$c['text']};font-weight:700;text-decoration:underline;white-space:nowrap\">{$label} &rarr;</a>";
        }
        echo '</span>';

        // Botão dismiss — usa classe para adaptar ao tema
        if ($alert['dismiss']) {
            echo "<button type=\"button\" class=\"wu-alert-dismiss\" aria-label=\"Fechar\""
                . " onclick=\"(function(el){el.style.opacity='0';setTimeout(function(){el.style.display='none'},300)})"
                . "(document.getElementById('{$id}'))\">&times;</button>";
        }

        echo '</div>';
    }

    echo '</div>';
}

// ══════════════════════════════════════════════════════════════════
// 8. Notificações — helpers para o dashboard
//    (A lógica completa está em ajax/notifications_api.php;
//     estas funções são para acesso síncrono no PHP da página.)
// ══════════════════════════════════════════════════════════════════
function getUnreadNotifCount(int $id_users): int
{
    try {
        $db = getDB();

        // Notificações directas não lidas
        $s1 = $db->prepare("
            SELECT COUNT(*) FROM _notification
            WHERE id_users = ? AND is_read = 0
        ");
        $s1->execute([$id_users]);
        $direct = (int)$s1->fetchColumn();

        // Broadcasts não lidos (via _broadcast_receipt)
        $s2 = $db->prepare("
            SELECT COUNT(*) FROM _broadcast_receipt
            WHERE id_users = ? AND is_read = 0
        ");
        $s2->execute([$id_users]);
        $broadcast = (int)$s2->fetchColumn();

        return $direct + $broadcast;
    } catch (Throwable $e) {
        error_log('[getUnreadNotifCount] ' . $e->getMessage());
        return 0;
    }
}

function getRecentNotifs(int $id_users, int $limit = 5): array
{
    try {
        $db   = getDB();
        $stmt = $db->prepare("
            SELECT id_notification, type, title, body, action_url, is_read, creat_notification
            FROM _notification
            WHERE id_users = ?
            ORDER BY creat_notification DESC
            LIMIT ?
        ");
        $stmt->execute([$id_users, $limit]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('[getRecentNotifs] ' . $e->getMessage());
        return [];
    }
}

// ══════════════════════════════════════════════════════════════════
// 9. Configuração da plataforma — helpers de acesso rápido
// ══════════════════════════════════════════════════════════════════
function getPlatformConfig(): array
{
    $p = getDashboardPlatform();
    return [
        'royalty_percentage' => (float)($p['royalty_percentage'] ?? 90.00),
        'platform_fee'       => (float)($p['platform_fee']       ?? 10.00),
        'currency_default'   => $p['currency_default']            ?? 'AOA',
        'usd_to_aoa_rate'    => (float)($p['usd_to_aoa_rate']    ?? 900.00),
        'contact_email'      => $p['contact_email']               ?? 'suporte@wasomupfy.com',
        'stores_count'       => (int)($p['stores_count']          ?? 150),
        'allow_register'     => (bool)($p['allow_register']       ?? true),
        'allow_login'        => (bool)($p['allow_login']          ?? true),
        'version'            => $p['version']                     ?? '2.0',
    ];
}

// Converte USD → AOA usando a taxa da plataforma
function usdToAoa(float $usd): float
{
    $rate = (float)(getDashboardPlatform()['usd_to_aoa_rate'] ?? 900.00);
    return round($usd * $rate, 2);
}

// Formata valor em AOA: 2000 → "2.000 Kz"
function formatAOA(float $value): string
{
    return number_format($value, 0, ',', '.') . ' Kz';
}

// Formata valor em USD: 1.5 → "1,50 USD"
function formatUSD(float $value): string
{
    return number_format($value, 2, ',', '.') . ' USD';
}

// Formata royalty: calcula parte do utilizador com base na taxa da plataforma
function calcRoyalty(float $total_usd): array
{
    $cfg     = getPlatformConfig();
    $pct     = $cfg['royalty_percentage'] / 100;
    $user    = round($total_usd * $pct, 4);
    $platform = round($total_usd * ($cfg['platform_fee'] / 100), 4);
    return [
        'total'    => $total_usd,
        'user_usd' => $user,
        'plat_usd' => $platform,
        'user_aoa' => usdToAoa($user),
        'plat_aoa' => usdToAoa($platform),
        'pct'      => $cfg['royalty_percentage'],
    ];
}


// ══════════════════════════════════════════════════════════════════
// 10. Estado do plano — helper para a UI
//    Devolve array com informação de apresentação do plano activo.
// ══════════════════════════════════════════════════════════════════
function getPlanBadge(array $user): array
{
    if (empty($user['plan_selected'])) {
        return [
            'label'  => 'Sem Plano',
            'color'  => '#6b7280',
            'icon'   => 'bi-dash-circle',
            'active' => false,
        ];
    }

    $name = $user['name_plan'] ?? 'Plano';

    if (!empty($user['plan_expired'])) {
        return [
            'label'  => $name . ' (Expirado)',
            'color'  => '#ef4444',
            'icon'   => 'bi-x-circle-fill',
            'active' => false,
        ];
    }

    $days  = $user['plan_days_left'] ?? null;
    $color = '#22c55e'; // verde
    if ($days !== null && $days <= 7)  $color = '#eab308'; // âmbar
    if ($days !== null && $days <= 3)  $color = '#ef4444'; // vermelho

    $suffix = '';
    if ($days !== null) {
        $suffix = ' · ' . $days . ' ' . ($days === 1 ? 'dia' : 'dias');
    }

    return [
        'label'  => $name . $suffix,
        'color'  => $color,
        'icon'   => 'bi-check-circle-fill',
        'active' => (bool)$user['plan_active'],
        'days'   => $days,
    ];
}


// ══════════════════════════════════════════════════════════════════
// 11. Utilitários gerais do dashboard
// ══════════════════════════════════════════════════════════════════

// Formata data/hora em pt-AO: "13 mar. 2026 às 14:30"
function formatDatePT(string $datetime, bool $time = true): string
{
    if (empty($datetime)) return '—';
    $ts     = strtotime($datetime);
    $months = [
        '',
        'jan.',
        'fev.',
        'mar.',
        'abr.',
        'mai.',
        'jun.',
        'jul.',
        'ago.',
        'set.',
        'out.',
        'nov.',
        'dez.'
    ];
    $d = date('j', $ts);
    $m = $months[(int)date('n', $ts)];
    $y = date('Y', $ts);
    $t = $time ? ' às ' . date('H:i', $ts) : '';
    return "{$d} {$m} {$y}{$t}";
}

// "há 5 minutos", "há 2 horas", "há 3 dias", etc.
function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'agora mesmo';
    if ($diff < 3600)   return 'há ' . floor($diff / 60)   . ' min';
    if ($diff < 86400)  return 'há ' . floor($diff / 3600) . 'h';
    if ($diff < 604800) return 'há ' . floor($diff / 86400) . ' ' .
        (floor($diff / 86400) === 1 ? 'dia' : 'dias');
    return formatDatePT($datetime, false);
}

// Trunca texto com reticências: truncate('texto longo', 40)
function truncate(string $text, int $length = 60, string $suffix = '…'): string
{
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return rtrim(mb_substr($text, 0, $length)) . $suffix;
}

// Gera URL de avatar via initials (fallback quando não há foto)
// Devolve string CSS inline para usar no style de um div.
function avatarInitials(string $name, int $size = 40): string
{
    $parts    = array_filter(explode(' ', trim($name)));
    $initials = mb_strtoupper(
        mb_substr($parts[0] ?? '?', 0, 1) .
            mb_substr(end($parts) ?? '', 0, 1)
    );
    $colors = ['#FF0089', '#6366f1', '#22c55e', '#eab308', '#f97316', '#06b6d4'];
    $color  = $colors[array_sum(array_map('ord', str_split($name))) % count($colors)];
    return "width:{$size}px;height:{$size}px;border-radius:50%;"
        . "background:{$color};display:inline-flex;align-items:center;"
        . "justify-content:center;font-family:'Syne',sans-serif;"
        . "font-weight:900;font-size:" . round($size * 0.38) . "px;color:#fff;"
        . "flex-shrink:0;\" data-initials=\"{$initials}";
}
