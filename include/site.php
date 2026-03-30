<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Backend do Site Principal
// Arquivo: include/site.php
// Incluir no topo de cada página pública:
//   require_once __DIR__ . '/../include/site.php';
// ══════════════════════════════════════════════

// ── 1. Config base ────────────────────────────
define('APP_ENV', 'development'); // trocar para 'production' no servidor
define('DB_HOST', 'localhost');
define('DB_NAME', 'wasomupfy');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('APP_URL', 'http://localhost/wasomupfy'); // sem barra no fim

// ── 2. Ligação à BD (singleton) ───────────────
function getSiteDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// ── 3. _site_config (apenas chaves públicas) ──
// Chaves disponíveis via cfg():
//   general : site_name, site_tagline
//   social  : facebook_url, instagram_url, youtube_url, tiktok_url,
//             linkedin_url, twitter_url, threads_url, whatsapp_number
//   email   : support_email, info_email
//   payment : min_withdrawal

function getSiteConfig(): array {
    static $cfg = null;
    if ($cfg === null) {
        $stmt = getSiteDB()->query("
            SELECT config_key, config_value
            FROM _site_config
            WHERE is_public = 1
        ");
        $cfg = [];
        foreach ($stmt->fetchAll() as $row) {
            $cfg[$row['config_key']] = $row['config_value'];
        }
    }
    return $cfg;
}

function cfg(string $key, string $default = ''): string {
    return getSiteConfig()[$key] ?? $default;
}

// ── 4. _platform — estado e taxas ────────────
// ARQUITECTURA DE CONTROLO INDEPENDENTE:
//   site_status          → controla o site público  (lido por site.php)
//   status               → controla o dashboard     (lido por functions.php)
//   Admin pode colocar o site em manutenção sem afectar o dashboard, e vice-versa.
//
// Auto-expiry: se site_status é 'maintenance'/'blocked' e site_maintenance_end
// já passou, restaura automaticamente para 'active' sem intervenção do admin.
function getPlatform(): array {
    static $p = null;
    if ($p === null) {
        $db = getSiteDB();
        $p  = $db->query("SELECT * FROM _platform ORDER BY id_platform ASC LIMIT 1")->fetch();

        if (!$p) {
            $p = [
                'site_status'        => 'active',
                'status'             => 'active',
                'allow_register'     => 1,
                'allow_login'        => 1,
                'royalty_percentage' => 90,
                'stores_count'       => 157,
                'usd_to_aoa_rate'    => 900,
                'currency_default'   => 'AOA',
            ];
            return $p;
        }

        // ── Auto-expiry do site público ───────────────────────────────
        // Usa site_maintenance_end e actualiza site_status.
        // NÃO toca em 'status' (dashboard) — são independentes.
        $expirable = ['maintenance', 'blocked'];
        if (
            in_array($p['site_status'], $expirable, true) &&
            !empty($p['site_maintenance_end']) &&
            strtotime($p['site_maintenance_end']) <= time()
        ) {
            try {
                $db->prepare("
                    UPDATE _platform SET
                        site_status               = 'active',
                        site_maintenance_msg      = NULL,
                        site_maintenance_start    = NULL,
                        site_maintenance_end      = NULL,
                        site_maintenance_services = NULL,
                        modif_platform            = NOW()
                    WHERE id_platform = ?
                ")->execute([$p['id_platform']]);

                $p['site_status']               = 'active';
                $p['site_maintenance_msg']      = null;
                $p['site_maintenance_start']    = null;
                $p['site_maintenance_end']      = null;
                $p['site_maintenance_services'] = null;

                error_log('[getPlatform] Auto-expiry site: site_status restaurado para active.');

            } catch (Throwable $e) {
                error_log('[getPlatform] Auto-expiry site falhou: ' . $e->getMessage());
            }
        }
    }
    return $p;
}

// ── 5. _plans + _plan_features ────────────────
function getPlans(): array {
    $db   = getSiteDB();
    $plans = $db->query("
        SELECT * FROM _plans
        WHERE is_active = 1
        ORDER BY display_order ASC
    ")->fetchAll();

    foreach ($plans as &$plan) {
        $plan['features'] = $db->prepare("
            SELECT feature_text, is_included
            FROM _plan_features
            WHERE id_plan = ?
            ORDER BY display_order ASC
        ");
        $plan['features']->execute([$plan['id_plan']]);
        $plan['features'] = $plan['features']->fetchAll();
    }
    unset($plan);
    return $plans;
}

function getPlanBySlug(string $slug): ?array {
    $stmt = getSiteDB()->prepare("
        SELECT * FROM _plans WHERE slug_plan = ? AND is_active = 1 LIMIT 1
    ");
    $stmt->execute([$slug]);
    $plan = $stmt->fetch();
    if (!$plan) return null;

    $f = getSiteDB()->prepare("
        SELECT feature_text, is_included
        FROM _plan_features
        WHERE id_plan = ?
        ORDER BY display_order ASC
    ");
    $f->execute([$plan['id_plan']]);
    $plan['features'] = $f->fetchAll();
    return $plan;
}

// Formatar preço em AOA: 2000 → "2.000 Kz"
function formatAOA(float $value): string {
    return number_format($value, 0, ',', '.') . ' Kz';
}

// ── 6. _faq ───────────────────────────────────
function getFaqs(?string $category = null): array {
    $db  = getSiteDB();
    $sql = "SELECT * FROM _faq WHERE status_faq = 'visible'";
    $params = [];
    if ($category !== null) {
        $sql .= " AND category_faq = ?";
        $params[] = $category;
    }
    $sql .= " ORDER BY display_order ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getFaqCategories(): array {
    return getSiteDB()->query("
        SELECT DISTINCT category_faq
        FROM _faq
        WHERE status_faq = 'visible'
        ORDER BY category_faq ASC
    ")->fetchAll(PDO::FETCH_COLUMN);
}

// ── 7. _store — lojas de distribuição ─────────
function getStores(int $limit = 0): array {
    $sql  = "SELECT * FROM _store WHERE is_active = 1 ORDER BY display_order ASC";
    if ($limit > 0) $sql .= " LIMIT $limit";
    return getSiteDB()->query($sql)->fetchAll();
}

// ── 8. _blog_post — últimos artigos ──────────
function getLatestPosts(int $limit = 3): array {
    $stmt = getSiteDB()->prepare("
        SELECT p.*, c.name_category
        FROM _blog_post p
        LEFT JOIN _blog_category c ON c.id_category = p.id_category
        WHERE p.status_post = 'published'
        ORDER BY p.published_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getPostBySlug(string $slug): ?array {
    $stmt = getSiteDB()->prepare("
        SELECT p.*, c.name_category
        FROM _blog_post p
        LEFT JOIN _blog_category c ON c.id_category = p.id_category
        WHERE p.slug_post = ? AND p.status_post = 'published'
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
    if ($post) {
        // Incrementar views
        getSiteDB()->prepare("UPDATE _blog_post SET views_post = views_post + 1 WHERE id_post = ?")
                   ->execute([$post['id_post']]);
    }
    return $post ?: null;
}

// ── 9. Rastreio de visitantes ─────────────────
// Guarda IP, país, cidade, browser, OS, device na _visitor
// e cada pageview em _visitor_pageview
function getVisitorSessionId(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['_visitor_session_id'])) {
        $_SESSION['_visitor_session_id'] = session_id() ?: bin2hex(random_bytes(16));
    }

    return (string)$_SESSION['_visitor_session_id'];
}

function cleanupStaleVisitors(int $idle_minutes = 5): void {
    $idle_minutes = max(1, min(120, $idle_minutes));

    try {
        getSiteDB()->exec("
            UPDATE _visitor
            SET is_online = 0,
                modif_visitor = NOW()
            WHERE is_online = 1
              AND last_seen < DATE_SUB(NOW(), INTERVAL {$idle_minutes} MINUTE)
        ");
    } catch (Throwable $e) {
        if (APP_ENV === 'development') {
            error_log('[cleanupStaleVisitors] ' . $e->getMessage());
        }
    }
}

function updateVisitorPresence(
    string $page_url,
    ?string $page_title = null,
    bool $register_pageview = false,
    ?int $time_on_page = null,
    string $status = 'online'
): ?int {
    try {
        $db           = getSiteDB();
        $ip           = getVisitorIp();
        $session_id   = getVisitorSessionId();
        $ua           = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer     = $_SERVER['HTTP_REFERER'] ?? null;
        $browser_info = parseBrowser($ua);
        $is_bot       = detectBot($ua);
        $bot_name     = $is_bot ? extractBotName($ua) : null;
        $page_url     = substr(trim($page_url), 0, 500);
        $page_title   = $page_title !== null ? substr(trim($page_title), 0, 255) : null;
        $time_on_page = $time_on_page !== null ? max(0, min(86400, $time_on_page)) : null;
        $is_online    = $status === 'offline' ? 0 : 1;

        cleanupStaleVisitors();

        $existing = $db->prepare("
            SELECT id_visitor
            FROM _visitor
            WHERE session_id = ?
            LIMIT 1
        ");
        $existing->execute([$session_id]);
        $visitor_row = $existing->fetch();

        if ($visitor_row) {
            $id_visitor = (int)$visitor_row['id_visitor'];
            $db->prepare("
                UPDATE _visitor
                SET page_exit        = ?,
                    pages_viewed     = CASE WHEN ? = 1 THEN pages_viewed + 1 ELSE pages_viewed END,
                    user_agent       = ?,
                    browser          = ?,
                    browser_version  = ?,
                    os               = ?,
                    os_version       = ?,
                    device_type      = ?,
                    is_bot           = ?,
                    bot_name         = ?,
                    referrer         = COALESCE(referrer, ?),
                    ip_address       = ?,
                    ip_version       = ?,
                    is_online        = ?,
                    last_seen        = NOW(),
                    session_duration = GREATEST(0, TIMESTAMPDIFF(SECOND, creat_visitor, NOW())),
                    modif_visitor    = NOW()
                WHERE id_visitor = ?
            ")->execute([
                $page_url,
                $register_pageview ? 1 : 0,
                $ua ?: null,
                $browser_info['browser'] ?? null,
                $browser_info['browser_version'] ?? null,
                $browser_info['os'] ?? null,
                $browser_info['os_version'] ?? null,
                $browser_info['device_type'] ?? 'unknown',
                $is_bot ? 1 : 0,
                $bot_name,
                $referrer,
                $ip,
                strpos($ip, ':') !== false ? 'v6' : 'v4',
                $is_online,
                $id_visitor,
            ]);
        } else {
            if ($status === 'offline') {
                return null;
            }

            $geo = getGeoData($ip);
            $visit_count_stmt = $db->prepare("SELECT COUNT(*) FROM _visitor WHERE ip_address = ?");
            $visit_count_stmt->execute([$ip]);
            $visit_count = (int)$visit_count_stmt->fetchColumn() + 1;

            $db->prepare("
                INSERT INTO _visitor (
                    ip_address, ip_version, country_code, country_name,
                    city, region, latitude, longitude, timezone, isp,
                    user_agent, browser, browser_version, os, os_version,
                    device_type, is_bot, bot_name,
                    page_entry, page_exit, pages_viewed, session_duration,
                    referrer, utm_source, utm_medium, utm_campaign,
                    session_id, is_online, last_seen, visit_count,
                    creat_visitor, modif_visitor
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, 0,
                    ?, ?, ?, ?,
                    ?, ?, NOW(), ?,
                    NOW(), NOW()
                )
            ")->execute([
                $ip,
                strpos($ip, ':') !== false ? 'v6' : 'v4',
                $geo['countryCode'] ?? null,
                $geo['country'] ?? null,
                $geo['city'] ?? null,
                $geo['regionName'] ?? null,
                $geo['lat'] ?? null,
                $geo['lon'] ?? null,
                $geo['timezone'] ?? null,
                $geo['isp'] ?? null,
                $ua ?: null,
                $browser_info['browser'] ?? null,
                $browser_info['browser_version'] ?? null,
                $browser_info['os'] ?? null,
                $browser_info['os_version'] ?? null,
                $browser_info['device_type'] ?? 'unknown',
                $is_bot ? 1 : 0,
                $bot_name,
                $page_url,
                $page_url,
                $register_pageview ? 1 : 0,
                $referrer,
                $_GET['utm_source'] ?? null,
                $_GET['utm_medium'] ?? null,
                $_GET['utm_campaign'] ?? null,
                $session_id,
                $is_online,
                $visit_count,
            ]);
            $id_visitor = (int)$db->lastInsertId();
        }

        if ($register_pageview && $id_visitor > 0) {
            $db->prepare("
                INSERT INTO _visitor_pageview (id_visitor, page_url, page_title, time_on_page)
                VALUES (?, ?, ?, ?)
            ")->execute([$id_visitor, $page_url, $page_title, $time_on_page]);
        } elseif ($time_on_page !== null && $id_visitor > 0) {
            $db->prepare("
                UPDATE _visitor_pageview
                SET time_on_page = ?
                WHERE id_visitor = ? AND page_url = ?
                ORDER BY id_pageview DESC
                LIMIT 1
            ")->execute([$time_on_page, $id_visitor, $page_url]);
        }

        return $id_visitor;
    } catch (Throwable $e) {
        if (APP_ENV === 'development') {
            error_log('[updateVisitorPresence] ' . $e->getMessage());
        }
        return null;
    }
}

function trackVisitor(string $page_url, ?string $page_title = null): void {
    updateVisitorPresence($page_url, $page_title, true, null, 'online');
}

function getVisitorIp(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $key) {
        $val = $_SERVER[$key] ?? null;
        if ($val) {
            // Pode ter lista separada por vírgula — pegar o primeiro
            $ip = trim(explode(',', $val)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

function getGeoData(string $ip): array {
    // Localhost/privado — sem geolocalização
    if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0']) ||
        !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return ['country' => 'Local', 'city' => 'Localhost', 'countryCode' => 'XX'];
    }

    // Cache na sessão para não repetir o pedido na mesma sessão
    if (isset($_SESSION['_geo_' . $ip])) {
        return $_SESSION['_geo_' . $ip];
    }

    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $raw = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,regionName,city,lat,lon,timezone,isp", false, $ctx);
    $geo = $raw ? (json_decode($raw, true) ?? []) : [];

    if (($geo['status'] ?? '') !== 'success') $geo = [];
    $_SESSION['_geo_' . $ip] = $geo;
    return $geo;
}

function detectBot(string $ua): bool {
    $bots = ['bot','crawl','spider','slurp','mediapartners','facebookexternalhit',
             'twitterbot','linkedinbot','whatsapp','telegram','curl','wget','python','java'];
    $ua_lower = strtolower($ua);
    foreach ($bots as $b) {
        if (strpos($ua_lower, $b) !== false) return true;
    }
    return false;
}

function extractBotName(string $ua): ?string {
    if (preg_match('/(Googlebot|Bingbot|Slurp|DuckDuckBot|Baiduspider|facebookexternalhit|Twitterbot|LinkedInBot)/i', $ua, $m)) {
        return $m[1];
    }
    return 'Unknown Bot';
}

function parseBrowser(string $ua): array {
    $result = ['browser' => null, 'browser_version' => null, 'os' => null, 'os_version' => null, 'device_type' => 'desktop'];

    // Device type
    if (preg_match('/tablet|ipad/i', $ua))         $result['device_type'] = 'tablet';
    elseif (preg_match('/mobile|android|iphone/i', $ua)) $result['device_type'] = 'mobile';
    elseif (detectBot($ua))                         $result['device_type'] = 'bot';

    // Browser
    $browsers = [
        'Edg'     => 'Edge',
        'OPR'     => 'Opera',
        'Opera'   => 'Opera',
        'Chrome'  => 'Chrome',
        'Firefox' => 'Firefox',
        'Safari'  => 'Safari',
        'MSIE'    => 'Internet Explorer',
        'Trident' => 'Internet Explorer',
    ];
    foreach ($browsers as $key => $name) {
        if (preg_match("/{$key}\/([0-9.]+)/i", $ua, $m)) {
            $result['browser']         = $name;
            $result['browser_version'] = explode('.', $m[1])[0];
            break;
        }
    }

    // OS
    if      (preg_match('/Windows NT ([0-9.]+)/i', $ua, $m)) { $result['os'] = 'Windows'; $result['os_version'] = $m[1]; }
    elseif  (preg_match('/Mac OS X ([0-9_]+)/i', $ua, $m))   { $result['os'] = 'macOS';   $result['os_version'] = str_replace('_','.',$m[1]); }
    elseif  (preg_match('/Android ([0-9.]+)/i', $ua, $m))    { $result['os'] = 'Android'; $result['os_version'] = $m[1]; }
    elseif  (preg_match('/iPhone OS ([0-9_]+)/i', $ua, $m))  { $result['os'] = 'iOS';     $result['os_version'] = str_replace('_','.',$m[1]); }
    elseif  (preg_match('/Linux/i', $ua))                     { $result['os'] = 'Linux';   }

    return $result;
}

// ── 10. Contact form → _support_ticket ────────
function submitContactForm(array $data): array {
    $name    = trim($data['name']    ?? '');
    $email   = trim($data['email']   ?? '');
    $subject = trim($data['subject'] ?? '');
    $body    = trim($data['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($body)) {
        return ['ok' => false, 'msg' => 'Preenche todos os campos obrigatórios.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'msg' => 'Email inválido.'];
    }
    if (strlen($body) < 20) {
        return ['ok' => false, 'msg' => 'A mensagem é muito curta.'];
    }

    getSiteDB()->prepare("
        INSERT INTO _support_ticket
            (name_contact, email_contact, ip_ticket, subject, body, priority, status_ticket)
        VALUES (?, ?, ?, ?, ?, 'medium', 'open')
    ")->execute([$name, $email, getVisitorIp(), $subject, $body]);

    return ['ok' => true, 'msg' => 'Mensagem enviada com sucesso! Responderemos em breve.'];
}

// ── 11. Verificar estado da plataforma + visitante ─────────────────
//
// PÁGINAS LIVRES — sempre acessíveis independentemente do estado da plataforma.
// Razão: suporte/termos/privacidade/cookies são direitos do utilizador e
// devem estar disponíveis mesmo em manutenção ou bloqueio.
//
// FUTURO: quando existir controlo por página na BD (_page_control),
// esta lista poderá ser substituída por uma query dinâmica que verifica
// se a página está activa, suspensa ou bloqueada individualmente.
// Por agora é um array estático fácil de manter.
//
// $current_page: identificador da página actual (ex: 'home', 'terms').
//   Usar sempre que se chama checkPlatformStatus() — evita loops.
function checkPlatformStatus(string $current_page = ''): void {

    // ── Páginas de status — nunca redireccionam (evita loops) ────────
    $status_pages = ['maintenance', '403', '404', '500', '503', 'offline'];

    // ── Páginas livres — acessíveis mesmo com plataforma bloqueada ───
    // Inclui suporte, políticas legais e ajuda ao utilizador.
    // Visitantes bloqueados por IP também podem aceder às páginas legais.
    $free_pages = [
        // Suporte e ajuda
        'support', 'faq', 'help', 'tutorial',
        // Políticas legais (direito do utilizador)
        'terms', 'privacy', 'cookies',
        // Contacto (pode ser necessário mesmo em manutenção)
        'contact',
    ];

    // Páginas de status e páginas livres saem imediatamente
    if (in_array($current_page, $status_pages, true)) return;
    if (in_array($current_page, $free_pages, true))   return;

    // ── 11a. Estado global da plataforma ─────────────────────────────
    // getPlatform() já correu o auto-expiry — se o tempo expirou,
    // o status já está 'active' aqui, sem necessidade de redirect.
    $p = getPlatform();

    // Ler site_status (coluna dedicada ao site público)
    // 'status' é reservado para o dashboard — não usar aqui.
    $site_st = $p['site_status'] ?? 'active';

    if ($site_st === 'maintenance') {
        header('Location: ' . APP_URL . '/status/maintenance.php');
        exit;
    }

    if ($site_st === 'blocked') {
        header('HTTP/1.1 503 Service Unavailable');
        header('Location: ' . APP_URL . '/status/503.php');
        exit;
    }

    if ($site_st === 'unauthorized') {
        header('HTTP/1.1 403 Forbidden');
        header('Location: ' . APP_URL . '/status/403.php');
        exit;
    }

    // ── 11b. Verificar se o IP do visitante está bloqueado ────────────
    // Páginas legais (terms, privacy, cookies) estão isentas mesmo aqui —
    // já saíram no bloco $free_pages acima.
    checkVisitorStatus();
}

// Verifica se o IP actual está bloqueado na tabela _visitor.
// Em caso de bloco temporário já expirado, faz o UPDATE automático.
function checkVisitorStatus(): void {
    try {
        $ip  = getVisitorIp();
        $db  = getSiteDB();

        $stmt = $db->prepare("
            SELECT status_visitor, block_type, block_until
            FROM _visitor
            WHERE ip_address = ?
            ORDER BY id_visitor DESC
            LIMIT 1
        ");
        $stmt->execute([$ip]);
        $visitor = $stmt->fetch();

        if (!$visitor) return; // IP desconhecido — deixar passar

        if ($visitor['status_visitor'] === 'blocked') {
            // Bloco temporário já expirou? → desbloquear automaticamente
            if (
                $visitor['block_type'] === 'temporary' &&
                !empty($visitor['block_until']) &&
                strtotime($visitor['block_until']) < time()
            ) {
                $db->prepare("
                    UPDATE _visitor SET
                        status_visitor = 'active',
                        block_type     = NULL,
                        block_reason   = NULL,
                        block_until    = NULL,
                        modif_visitor  = NOW()
                    WHERE ip_address = ?
                ")->execute([$ip]);
                return; // agora está activo
            }

            // Ainda bloqueado → 403
            header('HTTP/1.1 403 Forbidden');
            header('Location: ' . APP_URL . '/status/403.php');
            exit;
        }

        if ($visitor['status_visitor'] === 'suspicious') {
            // Suspeito: registar nos logs mas não bloquear
            error_log('[checkVisitorStatus] Suspicious IP: ' . $ip);
        }

    } catch (Throwable $e) {
        if (APP_ENV === 'development') {
            error_log('[checkVisitorStatus] ' . $e->getMessage());
        }
    }
}


// ── 12. Contar estatísticas públicas ──────────
function getPublicStats(): array {
    $db = getSiteDB();
    return [
        'artists'  => (int)$db->query("SELECT COUNT(*) FROM _artist WHERE status_artist = 'active'")->fetchColumn(),
        'releases' => (int)$db->query("SELECT COUNT(*) FROM _album  WHERE status_album  = 'approved'")->fetchColumn(),
        'stores'   => (int)$db->query("SELECT COUNT(*) FROM _store  WHERE is_active = 1")->fetchColumn(),
        'users'    => (int)$db->query("SELECT COUNT(*) FROM _users  WHERE status_user  IN ('active','pending_plan')")->fetchColumn(),
    ];
}

// ── 13. Sessão (para tracking e CSRF no contacto) ─
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── 14. CSRF simples para formulários públicos ──
// ── CSRF ──────────────────────────────────────────────────────
// $force = true → gera sempre um token novo (usar após POST)
function getSiteCsrf(bool $force = false): string {
    if (!isset($_SESSION['_site_csrf']) || $force) {
        $_SESSION['_site_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_site_csrf'];
}

function validateSiteCsrf(string $token): bool {
    if (empty($token) || empty($_SESSION['_site_csrf'])) return false;
    return hash_equals($_SESSION['_site_csrf'], $token);
}
