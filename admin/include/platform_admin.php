<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Platform Admin
// Arquivo: admin/platform_admin.php
// Incluir no topo de TODAS as páginas do painel
//
// Uso:
//   require_once __DIR__ . '/../../platform_admin.php';
//   // ou, da raiz do admin/:
//   require_once __DIR__ . '/platform_admin.php';
// ══════════════════════════════════════════════

// ── Dependências base ──
require_once __DIR__ . '/../auth/include/functions_admin.php';

startAdminSession();
checkAdminRememberMe();
requireAdminLogin();
requireNoLockscreen();

$db = getDB();

// ── Dados da sessão ──
$admin_id       = (int)($_SESSION['admin_id']       ?? 0);
$admin_name     = $_SESSION['admin_name']            ?? '';
$admin_fullname = $_SESSION['admin_full_name']       ?? $admin_name;
$admin_role     = $_SESSION['admin_role']            ?? '';
$admin_photo    = $_SESSION['admin_photo']           ?? null;
$admin_email    = $_SESSION['admin_email']           ?? '';

// ── Helpers globais ──
if (!function_exists('adm_initials')) {
    function adm_initials(string $f, string $s = ''): string
    {
        return mb_strtoupper(mb_substr(trim($f), 0, 1, 'UTF-8'), 'UTF-8')
            . mb_strtoupper(mb_substr(trim($s), 0, 1, 'UTF-8'), 'UTF-8');
    }
}

if (!function_exists('adm_avatar_color')) {
    function adm_avatar_color(string $n): string
    {
        $c = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308', '#ec4899', '#14b8a6', '#3b82f6', '#ef4444'];
        return $c[abs(crc32($n)) % count($c)];
    }
}

if (!function_exists('adm_fmt_aoa')) {
    function adm_fmt_aoa(float $v): string
    {
        if ($v >= 1_000_000) return 'Kz ' . number_format($v / 1_000_000, 1, ',', '.') . 'M';
        if ($v >= 1_000)     return 'Kz ' . number_format($v / 1_000, 1, ',', '.') . 'mil';
        return 'Kz ' . number_format($v, 2, ',', '.');
    }
}

if (!function_exists('adm_fmt_date')) {
    function adm_fmt_date(string $dt): string
    {
        $ts = strtotime($dt);
        if (!$ts) return '—';
        $d = time() - $ts;
        if ($d < 60)     return 'agora';
        if ($d < 3600)   return floor($d / 60) . 'min atrás';
        if ($d < 86400)  return floor($d / 3600) . 'h atrás';
        if ($d < 604800) return floor($d / 86400) . 'd atrás';
        $months = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        return date('d', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    }
}

// ── Badges de notificação globais ──
// Carregados uma vez e partilhados por sidebar + navbar
if (!isset($adm_pending_releases)) {
    $adm_pending_releases = (int)$db->query("SELECT COUNT(*) FROM _album WHERE status_album IN ('pending','under_review')")->fetchColumn();
    $adm_pending_payments = (int)$db->query("SELECT COUNT(*) FROM _payment WHERE status_payment='pending'")->fetchColumn();
    $adm_open_tickets     = (int)$db->query("SELECT COUNT(*) FROM _support_ticket WHERE status_ticket NOT IN ('closed','resolved')")->fetchColumn();
    $adm_total_notifs     = $adm_pending_releases + $adm_pending_payments + $adm_open_tickets;
}

// ── Info da sessão (logout modal) ──
if (!isset($_SESSION['_start_time'])) $_SESSION['_start_time'] = time();
$adm_session_mins = max(0, (int)floor((time() - $_SESSION['_start_time']) / 60));
$adm_client_ip    = $_SERVER['REMOTE_ADDR'] ?? '—';
$adm_ua           = $_SERVER['HTTP_USER_AGENT'] ?? '';

$adm_browser = 'Desconhecido';
if (str_contains($adm_ua, 'Edg'))         $adm_browser = 'Microsoft Edge';
elseif (str_contains($adm_ua, 'Chrome'))  $adm_browser = 'Google Chrome';
elseif (str_contains($adm_ua, 'Firefox')) $adm_browser = 'Firefox';
elseif (str_contains($adm_ua, 'Safari'))  $adm_browser = 'Safari';
elseif (str_contains($adm_ua, 'Opera'))   $adm_browser = 'Opera';

$adm_os = 'Desconhecido';
if (str_contains($adm_ua, 'Windows NT 10')) $adm_os = 'Windows 10/11';
elseif (str_contains($adm_ua, 'Windows'))   $adm_os = 'Windows';
elseif (str_contains($adm_ua, 'Mac OS'))    $adm_os = 'macOS';
elseif (str_contains($adm_ua, 'Android'))   $adm_os = 'Android';
elseif (str_contains($adm_ua, 'iPhone'))    $adm_os = 'iOS';
elseif (str_contains($adm_ua, 'Linux'))     $adm_os = 'Linux';

// ── Página activa (para highlight no sidebar) ──
// Detecta o URL actual e extrai o segmento após /admin/
$adm_current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$adm_current_path = preg_replace('#^/wasomupfy/admin/?#', '', $adm_current_path);
$adm_current_path = trim($adm_current_path, '/');
// Ex: "profile" | "users" | "releases/pending" | ""

function adm_is_active(string $segment): string
{
    global $adm_current_path;
    return str_starts_with($adm_current_path, $segment) ? ' active' : '';
}