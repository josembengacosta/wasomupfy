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
function getPlatform(): array {
    static $p = null;
    if ($p === null) {
        $p = getSiteDB()
            ->query("SELECT * FROM _platform ORDER BY id_platform ASC LIMIT 1")
            ->fetch();
        if (!$p) $p = [
            'status'          => 'active',
            'allow_register'  => 1,
            'allow_login'     => 1,
            'royalty_percentage' => 90,
            'stores_count'    => 157,
            'usd_to_aoa_rate' => 900,
            'currency_default'=> 'AOA',
        ];
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
function trackVisitor(string $page_url, ?string $page_title = null): void {
    try {
        $db         = getSiteDB();
        $ip         = getVisitorIp();
        $session_id = session_id() ?: bin2hex(random_bytes(16));
        $ua         = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer   = $_SERVER['HTTP_REFERER'] ?? null;

        // Detectar bot
        $is_bot  = detectBot($ua);
        $bot_name = $is_bot ? extractBotName($ua) : null;

        // Geolocalização via ip-api.com (gratuito, sem chave, 45 req/min)
        $geo = getGeoData($ip);

        // Parse browser e OS
        $browser_info = parseBrowser($ua);

        // Verificar se já existe sessão activa (últimas 30 min)
        $existing = $db->prepare("
            SELECT id_visitor FROM _visitor
            WHERE session_id = ? AND ip_address = ?
            AND creat_visitor > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
            LIMIT 1
        ");
        $existing->execute([$session_id, $ip]);
        $visitor_row = $existing->fetch();

        if ($visitor_row) {
            // Actualizar sessão existente
            $id_visitor = $visitor_row['id_visitor'];
            $db->prepare("
                UPDATE _visitor
                SET pages_viewed = pages_viewed + 1,
                    page_exit = ?,
                    modif_visitor = NOW()
                WHERE id_visitor = ?
            ")->execute([$page_url, $id_visitor]);
        } else {
            // Nova visita
            $db->prepare("
                INSERT INTO _visitor (
                    ip_address, ip_version, country_code, country_name,
                    city, region, latitude, longitude, timezone, isp,
                    user_agent, browser, browser_version, os, os_version,
                    device_type, is_bot, bot_name,
                    page_entry, page_exit, pages_viewed,
                    referrer, utm_source, utm_medium, utm_campaign,
                    session_id, creat_visitor, modif_visitor
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, 1,
                    ?, ?, ?, ?,
                    ?, NOW(), NOW()
                )
            ")->execute([
                $ip,
                strpos($ip, ':') !== false ? 'v6' : 'v4',
                $geo['countryCode'] ?? null,
                $geo['country']     ?? null,
                $geo['city']        ?? null,
                $geo['regionName']  ?? null,
                $geo['lat']         ?? null,
                $geo['lon']         ?? null,
                $geo['timezone']    ?? null,
                $geo['isp']         ?? null,
                $ua,
                $browser_info['browser']         ?? null,
                $browser_info['browser_version'] ?? null,
                $browser_info['os']              ?? null,
                $browser_info['os_version']      ?? null,
                $browser_info['device_type']     ?? 'unknown',
                $is_bot ? 1 : 0,
                $bot_name,
                $page_url, $page_url,
                $referrer,
                $_GET['utm_source']   ?? null,
                $_GET['utm_medium']   ?? null,
                $_GET['utm_campaign'] ?? null,
                $session_id,
            ]);
            $id_visitor = (int)$db->lastInsertId();
        }

        // Registar pageview
        $db->prepare("
            INSERT INTO _visitor_pageview (id_visitor, page_url, page_title)
            VALUES (?, ?, ?)
        ")->execute([$id_visitor, $page_url, $page_title]);

        // Também gravar na _visit (tabela simples legada)
        $db->prepare("
            INSERT INTO _visit (ip_visit, browser_visit, country_visit, city_visit, page_visit, views_visit, session_visit)
            VALUES (?, ?, ?, ?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE views_visit = views_visit + 1
        ")->execute([
            $ip, $ua,
            $geo['country'] ?? null,
            $geo['city']    ?? null,
            $page_url,
            $session_id,
        ]);

    } catch (Throwable $e) {
        // Tracking nunca deve quebrar a página
        if (APP_ENV === 'development') {
            error_log('[trackVisitor] ' . $e->getMessage());
        }
    }
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

// ── 11. Verificar estado da plataforma ────────
// Chamar no topo de cada página pública.
// Se a plataforma estiver em manutenção redireciona.
function checkPlatformStatus(string $current_page = ''): void {
    $p = getPlatform();
    if ($p['status'] === 'maintenance' && $current_page !== 'maintenance') {
        $msg = urlencode($p['maintenance_msg'] ?? 'Estamos em manutenção. Voltamos em breve.');
        header('Location: ' . APP_URL . '/maintenance.php?msg=' . $msg);
        exit;
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