<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Configurações da Plataforma (Admin)
// Arquivo: admin/pages/settings/config.php
// Rota:    ADMIN_PATH/settings
// Tabelas: _admin_config, _site_config, _platform, _admin_ip_whitelist, _audit_log
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'settings.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$db       = getDB();
$base_url = APP_URL . '/' . ADMIN_PATH;
$csrf     = $_SESSION['admin_csrf_token'];
$can_edit = hasPermission($admin_id, 'settings.edit');

// ─────────────────────────────────────────────────────────────
// HELPERS DE LEITURA
// ─────────────────────────────────────────────────────────────

// Lê _admin_config com cache
function cfg(string $key, $default = ''): mixed
{
    global $db;
    static $cache = [];
    if (!isset($cache)) $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];
    $s = $db->prepare("SELECT config_value FROM _admin_config WHERE config_key = ? LIMIT 1");
    $s->execute([$key]);
    $v = $s->fetchColumn();
    $cache[$key] = ($v !== false) ? $v : $default;
    return $cache[$key];
}

// Lê _site_config com cache
function scfg(string $key, $default = ''): mixed
{
    global $db;
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];
    $s = $db->prepare("SELECT config_value FROM _site_config WHERE config_key = ? LIMIT 1");
    $s->execute([$key]);
    $v = $s->fetchColumn();
    $cache[$key] = ($v !== false) ? $v : $default;
    return $cache[$key];
}

// Lê _platform (sempre 1 linha)
function plat(string $col, $default = ''): mixed
{
    global $db;
    static $p = null;
    if ($p === null) {
        $p = $db->query("SELECT * FROM _platform LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    return $p[$col] ?? $default;
}

// ─────────────────────────────────────────────────────────────
// DADOS — _admin_config
// ─────────────────────────────────────────────────────────────
$ac = [
    // Segurança
    'max_login_attempts'    => cfg('max_login_attempts', 5),
    'block_level_1_min'     => cfg('block_level_1_min', 5),
    'block_level_2_min'     => cfg('block_level_2_min', 15),
    'block_level_3_min'     => cfg('block_level_3_min', 30),
    'session_timeout'       => cfg('session_timeout', 60),
    'ip_whitelist_on'       => cfg('ip_whitelist_on', 0),
    'admin_path'            => cfg('admin_path', ADMIN_PATH),

    // Email
    'smtp_host'             => cfg('smtp_host', MAIL_HOST),
    'smtp_port'             => cfg('smtp_port', MAIL_PORT),
    'smtp_encryption'       => cfg('smtp_encryption', MAIL_SECURE),
    'smtp_user'             => cfg('smtp_user', MAIL_USER),
    'smtp_pass'             => cfg('smtp_pass', ''),
    'mail_from_address'     => cfg('mail_from_address', MAIL_FROM),
    'mail_from_name'        => cfg('mail_from_name', MAIL_FROM_NAME),
    'mail_debug'            => cfg('mail_debug', 0),

    // Integrações
    'vapid_public_key'      => cfg('vapid_public_key', defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : ''),
    'vapid_private_key'     => cfg('vapid_private_key', ''),
    'vapid_subject'         => cfg('vapid_subject', defined('VAPID_SUBJECT') ? VAPID_SUBJECT : ''),

    // Logs
    'log_retention_days'    => cfg('log_retention_days', 90),
    'log_level'             => cfg('log_level', 'warning'),

    // Pagamento
    'payment_auto_approve_minutes' => cfg('payment_auto_approve_minutes', 30),
    'payment_intent_expiry_minutes' => cfg('payment_intent_expiry_minutes', 60),
    'payment_max_attempts'  => cfg('payment_max_attempts', 3),
];

// ─────────────────────────────────────────────────────────────
// DADOS — _site_config
// ─────────────────────────────────────────────────────────────
$sc = [
    'site_name'             => scfg('site_name', 'Wasom Upfy'),
    'site_url'              => scfg('site_url', APP_URL),
    'support_email'         => scfg('support_email', 'suporte@wasomupfy.com'),
    'info_email'            => scfg('info_email', 'info@wasomupfy.com'),
    'whatsapp_number'       => scfg('whatsapp_number', ''),
    'whatsapp_channel_url'  => scfg('whatsapp_channel_url', ''),
    'instagram_url'         => scfg('instagram_url', ''),
    'facebook_url'          => scfg('facebook_url', ''),
    'youtube_url'           => scfg('youtube_url', ''),
    'linkedin_url'          => scfg('linkedin_url', ''),
    'threads_url'           => scfg('threads_url', ''),
    'twitter_url'           => scfg('twitter_url', ''),
    'tiktok_url'            => scfg('tiktok_url', ''),
    'company_country'       => scfg('company_country', 'Angola'),
    'company_city'          => scfg('company_city', 'Luanda'),
    'company_address'       => scfg('company_address', ''),
    'company_phone'         => scfg('company_phone', ''),
    'youtube_tutorial_id'   => scfg('youtube_tutorial_id', ''),
    'cookie_consent_enabled' => scfg('cookie_consent_enabled', '1'),
    'maintenance_banner'    => scfg('maintenance_banner', ''),
];

// ─────────────────────────────────────────────────────────────
// DADOS — _platform
// ─────────────────────────────────────────────────────────────
$pl = [
    'status'                  => plat('status', 'active'),
    'maintenance_msg'         => plat('maintenance_msg', ''),
    'maintenance_start'       => plat('maintenance_start', ''),
    'maintenance_end'         => plat('maintenance_end', ''),
    'maintenance_services'    => plat('maintenance_services', '{}'),
    'site_status'             => plat('site_status', 'active'),
    'site_maintenance_msg'    => plat('site_maintenance_msg', ''),
    'site_maintenance_start'  => plat('site_maintenance_start', ''),
    'site_maintenance_end'    => plat('site_maintenance_end', ''),
    'site_maintenance_services' => plat('site_maintenance_services', '{}'),
    'allow_register'          => plat('allow_register', 1),
    'allow_login'             => plat('allow_login', 1),
    'royalty_percentage'      => plat('royalty_percentage', 90),
    'platform_fee'            => plat('platform_fee', 10),
    'currency_default'        => plat('currency_default', 'AOA'),
    'usd_to_aoa_rate'         => plat('usd_to_aoa_rate', 900),
    'stores_count'            => plat('stores_count', 150),
    'contact_email'           => plat('contact_email', 'suporte@wasomupfy.com'),
    'version'                 => plat('version', '2.0'),
];

// Descodificar JSON dos serviços de manutenção
$dash_services = [];
$site_services = [];
try {
    $dash_services = json_decode($pl['maintenance_services'] ?: '{}', true) ?? [];
    $site_services = json_decode($pl['site_maintenance_services'] ?: '{}', true) ?? [];
} catch (Exception $e) {
}

// ─────────────────────────────────────────────────────────────
// WHITELIST DE IPs
// ─────────────────────────────────────────────────────────────
$ip_whitelist = [];
try {
    $wl = $db->query("SELECT * FROM _admin_ip_whitelist ORDER BY creat_ip DESC");
    $ip_whitelist = $wl->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

$my_ip = $_SERVER['REMOTE_ADDR'] ?? '—';

// ─────────────────────────────────────────────────────────────
// LOGS RECENTES (_audit_log)
// ─────────────────────────────────────────────────────────────
$recent_logs = [];
try {
    $recent_logs = $db->query("
        SELECT al.*, CONCAT(e.first_name,' ',COALESCE(e.second_name,'')) AS emp_name
        FROM _audit_log al
        LEFT JOIN _employees e ON e.id_employees = al.id_employees
        ORDER BY al.creat_log DESC LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

// Contagem de logs por período
$log_stats = ['today' => 0, 'week' => 0, 'total' => 0];
try {
    $log_stats['today'] = (int)$db->query("SELECT COUNT(*) FROM _audit_log WHERE DATE(creat_log)=CURDATE()")->fetchColumn();
    $log_stats['week']  = (int)$db->query("SELECT COUNT(*) FROM _audit_log WHERE creat_log >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    $log_stats['total'] = (int)$db->query("SELECT COUNT(*) FROM _audit_log")->fetchColumn();
} catch (Exception $e) {
}

// ─────────────────────────────────────────────────────────────
// ESTATÍSTICAS DA PLATAFORMA (painel geral)
// ─────────────────────────────────────────────────────────────
$stats = [];
try {
    $stats['users']    = (int)$db->query("SELECT COUNT(*) FROM _users")->fetchColumn();
    $stats['active']   = (int)$db->query("SELECT COUNT(*) FROM _users WHERE status_user='active'")->fetchColumn();
    $stats['albums']   = (int)$db->query("SELECT COUNT(*) FROM _album WHERE status_album NOT IN ('draft','deleting')")->fetchColumn();
    $stats['payments'] = (int)$db->query("SELECT COUNT(*) FROM _payment WHERE status_payment='approved'")->fetchColumn();
    $stats['revenue']  = (float)($db->query("SELECT COALESCE(SUM(amount),0) FROM _payment WHERE status_payment='approved'")->fetchColumn());
    $stats['employees'] = (int)$db->query("SELECT COUNT(*) FROM _employees WHERE status_employees='active'")->fetchColumn();
} catch (Exception $e) {
}

// ─────────────────────────────────────────────────────────────
// FEEDBACK VIA GET
// ─────────────────────────────────────────────────────────────
$msg = $_GET['msg'] ?? '';
$tab_open = $_GET['tab'] ?? 'geral';
$feedback = match ($msg) {
    'saved'           => ['success', 'bi-check-circle-fill', 'Configurações guardadas com sucesso.'],
    'test_email_ok'   => ['success', 'bi-envelope-check-fill', 'E-mail de teste enviado com sucesso!'],
    'test_email_err'  => ['danger',  'bi-envelope-exclamation-fill', 'Falha ao enviar e-mail de teste. Verifica as configurações SMTP.'],
    'ip_added'        => ['success', 'bi-shield-check-fill', 'IP adicionado à whitelist.'],
    'ip_removed'      => ['warning', 'bi-shield-dash', 'IP removido da whitelist.'],
    'logs_cleared'    => ['success', 'bi-trash3-fill', 'Logs antigos eliminados com sucesso.'],
    'error'           => ['danger',  'bi-exclamation-octagon-fill', 'Erro ao guardar. Tenta novamente.'],
    'perm_denied'     => ['danger',  'bi-lock-fill', 'Não tens permissão para esta acção.'],
    default           => null,
};

// Mapa de tabs para activar via GET
$tab_map = [
    'geral'     => 'tabGeral',
    'site'      => 'tabSite',
    'dashboard' => 'tabDashboard',
    'financeiro' => 'tabFinanceiro',
    'seguranca' => 'tabSeguranca',
    'email'     => 'tabEmail',
    'integracoes' => 'tabIntegracoes',
    'whitelist' => 'tabWhitelist',
    'logs'      => 'tabLogs',
];
$active_tab = $tab_map[$tab_open] ?? 'tabGeral';

// ─────────────────────────────────────────────────────────────
// HELPER — SELECT booleano
// ─────────────────────────────────────────────────────────────
function boolSelect(string $name, $value, bool $disabled = false): string
{
    $d = $disabled ? ' disabled' : '';
    $yes = $value ? ' selected' : '';
    $no  = !$value ? ' selected' : '';
    return '<select name="' . $name . '" class="form-select"' . $d . '>'
        . '<option value="1"' . $yes . '>Sim</option>'
        . '<option value="0"' . $no . '>Não</option>'
        . '</select>';
}
function sel(string $name, array $options, $current, bool $disabled = false): string
{
    $d = $disabled ? ' disabled' : '';
    $out = '<select name="' . $name . '" class="form-select"' . $d . '>';
    foreach ($options as $val => $label) {
        $sel = $current == $val ? ' selected' : '';
        $out .= '<option value="' . htmlspecialchars((string)$val) . '"' . $sel . '>' . htmlspecialchars($label) . '</option>';
    }
    $out .= '</select>';
    return $out;
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
    <title>Configurações — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css">
    <style>
    /* ── Nav lateral de tabs ── */
    .cfg-nav {
        min-width: 220px;
        flex-shrink: 0;
    }

    .cfg-nav-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 10px;
        font-size: .85rem;
        font-weight: 500;
        color: var(--text-muted, #888);
        text-decoration: none;
        transition: all .2s;
        margin-bottom: 2px;
        border: none;
        background: transparent;
        width: 100%;
        text-align: left;
    }

    .cfg-nav-link i {
        font-size: 1rem;
        width: 20px;
        text-align: center;
        flex-shrink: 0;
    }

    .cfg-nav-link:hover {
        background: rgba(255, 0, 137, .08);
        color: #FF0089;
    }

    .cfg-nav-link.active {
        background: rgba(255, 0, 137, .12);
        color: #FF0089;
        font-weight: 700;
    }

    .cfg-nav-link .cfg-badge {
        margin-left: auto;
        font-size: .65remw;
        padding: 2px 7px;
        border-radius: 10px;
        background: #FF0089;
        color: #fff;
        font-weight: 700;
    }

    .cfg-nav-sep {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: var(--text-muted, #888);
        padding: 10px 14px 4px;
    }

    /* ── Cartões de configuração ── */
    .cfg-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 14px;
        padding: 22px;
        margin-bottom: 20px;
    }

    .cfg-card-title {
        font-size: .92rem;
        font-weight: 700;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-color, #e8e8f0);
    }

    .cfg-card-title i {
        color: #FF0089;
    }

    /* ── Campos ── */
    .cfg-label {
        font-size: .78rem;
        font-weight: 600;
        margin-bottom: 5px;
        display: block;
    }

    .cfg-hint {
        font-size: .72rem;
        color: var(--text-muted, #888);
        margin-top: 3px;
    }

    /* ── Status pill ── */
    .st-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: .75rem;
        font-weight: 700;
    }

    .st-active {
        background: rgba(34, 197, 94, .12);
        color: #16a34a;
    }

    .st-maintenance {
        background: rgba(234, 179, 8, .12);
        color: #b45309;
    }

    .st-blocked {
        background: rgba(239, 68, 68, .12);
        color: #dc2626;
    }

    /* ── Stats bar no topo ── */
    .cfg-stat {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .cfg-stat-val {
        font-size: 1.4rem;
        font-weight: 800;
        color: #FF0089;
    }

    .cfg-stat-lbl {
        font-size: .72rem;
        color: var(--text-muted, #888);
    }

    /* ── Whitelist IPs ── */
    .ip-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        border-radius: 10px;
        border: 1px solid var(--border-color, #e8e8f0);
        margin-bottom: 8px;
        background: var(--bg, #f4f4f8);
    }

    .ip-row .ip-val {
        font-family: monospace;
        font-size: .88rem;
        font-weight: 700;
    }

    .ip-row .ip-lbl {
        font-size: .75rem;
        color: var(--text-muted, #888);
    }

    .ip-mine {
        border-color: rgba(255, 0, 137, .3);
        background: rgba(255, 0, 137, .04);
    }

    /* ── Logs ── */
    .log-row {
        display: flex;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid var(--border-color, #e8e8f0);
        font-size: .8rem;
        align-items: flex-start;
    }

    .log-row:last-child {
        border-bottom: none;
    }

    .log-action {
        font-weight: 700;
        font-family: monospace;
        color: #FF0089;
        font-size: .75rem;
    }

    .log-entity {
        font-size: .72rem;
        color: var(--text-muted, #888);
    }

    .log-time {
        font-size: .7rem;
        color: var(--text-muted, #888);
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* ── Layout principal ── */
    .cfg-layout {
        display: flex;
        gap: 24px;
        align-items: flex-start;
    }

    @media (max-width: 768px) {
        .cfg-layout {
            flex-direction: column;
        }

        .cfg-nav {
            min-width: auto;
            width: 100%;
        }
    }

    /* ── Pane ── */
    .cfg-pane {
        display: none;
        flex: 1;
        min-width: 0;
    }

    .cfg-pane.active {
        display: block;
    }

    /* ── Save bar fixa ── */
    .cfg-save-bar {
        position: sticky;
        bottom: 0;
        left: 0;
        right: 0;
        background: var(--card-bg, #fff);
        border-top: 1px solid var(--border-color, #e8e8f0);
        padding: 12px 0;
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 100;
    }

    /* ── Maintenance services grid ── */
    .svc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 10px;
    }

    .svc-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid var(--border-color, #e8e8f0);
        cursor: pointer;
        transition: border-color .2s;
        font-size: .82rem;
    }

    .svc-item input[type=checkbox] {
        accent-color: #FF0089;
    }

    .svc-item.checked {
        border-color: #FF0089;
        background: rgba(255, 0, 137, .05);
    }

    /* ── Dark mode ── */
    .dark-mode .cfg-card,
    [data-theme="dark"] .cfg-card {
        background: var(--dark-card);
        border-color: var(--dark-border);
    }

    .dark-mode .ip-row,
    [data-theme="dark"] .ip-row {
        background: var(--dark-input);
        border-color: var(--dark-border);
    }

    .dark-mode .cfg-save-bar,
    [data-theme="dark"] .cfg-save-bar {
        background: var(--dark-card);
        border-color: var(--dark-border);
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php require_once __DIR__ . '/../../include/sidebar.php'; ?>
        <div class="content w-100" id="mainContent">
            <?php require_once __DIR__ . '/../../include/navbar.php'; ?>

            <div class="container-fluid p-0">

                <!-- ── Breadcrumb ── -->
                <div class="row mb-3 mt-2">
                    <div class="welcome-text col-auto d-sm-block">

                        <h2 class="h4 mb-2">
                            <i class="bi bi-gear-wide-connected me-2"></i>Configurações da
                            Plataforma
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="#" class="text-secondary-emphasis">Home</a>
                                </li>
                                <li class="breadcrumb-item active text-white-stable" aria-current="page">Configurações</li>
                            </ol>
                        </nav>
                        <div style="font-size:.75rem;color:var(--text-muted,#888)">
                            Versão <?php echo htmlspecialchars($pl['version']); ?> &middot; Admin Path:
                            /<?php echo htmlspecialchars($ac['admin_path']); ?>
                            &middot; IP actual: <code><?php echo htmlspecialchars($my_ip); ?></code>
                        </div>
                    </div>
                    <?php if (!$can_edit): ?>
                    <span class="badge ms-auto"
                        style="background:rgba(234,179,8,.15);color:#b45309;font-size:.75rem;padding:6px 12px;border-radius:8px">
                        <i class="bi bi-eye me-1"></i>Modo de visualização (sem permissão de edição)
                    </span>
                    <?php endif; ?>
                </div>

                <!-- ── Feedback ── -->
                <?php if ($feedback): ?>
                <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-4"
                    style="border-radius:12px">
                    <i class="bi <?php echo $feedback[1]; ?> me-2"></i><?php echo htmlspecialchars($feedback[2]); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- ── Stats bar ── -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-2">
                        <div class="cfg-stat">
                            <div class="cfg-stat-val"><?php echo number_format($stats['users']); ?></div>
                            <div class="cfg-stat-lbl">Utilizadores</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="cfg-stat">
                            <div class="cfg-stat-val"><?php echo number_format($stats['active']); ?></div>
                            <div class="cfg-stat-lbl">Activos</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="cfg-stat">
                            <div class="cfg-stat-val"><?php echo number_format($stats['albums']); ?></div>
                            <div class="cfg-stat-lbl">Lançamentos</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="cfg-stat">
                            <div class="cfg-stat-val"><?php echo number_format($stats['payments']); ?></div>
                            <div class="cfg-stat-lbl">Pagamentos</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="cfg-stat">
                            <div class="cfg-stat-val">Kz
                                <?php echo $stats['revenue'] >= 1000000 ? number_format($stats['revenue'] / 1000000, 1) . 'M' : number_format($stats['revenue'] / 1000, 1) . 'K'; ?>
                            </div>
                            <div class="cfg-stat-lbl">Receita Total</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="cfg-stat">
                            <div class="cfg-stat-val"><?php echo number_format($log_stats['today']); ?></div>
                            <div class="cfg-stat-lbl">Logs hoje</div>
                        </div>
                    </div>
                </div>

                <!-- ── Layout tabs ── -->
                <div class="cfg-layout">

                    <!-- ═══ NAV LATERAL ═══ -->
                    <nav class="cfg-nav">
                        <div class="cfg-nav-sep">Plataforma</div>
                        <button class="cfg-nav-link <?php echo $active_tab === 'tabGeral' ? 'active' : ''; ?>"
                            data-pane="tabGeral">
                            <i class="bi bi-sliders"></i>Geral
                        </button>
                        <button class="cfg-nav-link <?php echo $active_tab === 'tabDashboard' ? 'active' : ''; ?>"
                            data-pane="tabDashboard">
                            <i class="bi bi-grid-3x3-gap"></i>Dashboard/App
                            <?php
                            $dash_status = $pl['status'];
                            if ($dash_status !== 'active'): ?>
                            <span
                                class="cfg-badge"><?php echo $dash_status === 'maintenance' ? 'MANUTENÇÃO' : 'BLOQUEADO'; ?></span>
                            <?php endif; ?>
                        </button>
                        <button class="cfg-nav-link <?php echo $active_tab === 'tabSite' ? 'active' : ''; ?>"
                            data-pane="tabSite">
                            <i class="bi bi-globe2"></i>Site Público
                            <?php if ($pl['site_status'] !== 'active'): ?>
                            <span class="cfg-badge" style="background:#f97316">INACTIVO</span>
                            <?php endif; ?>
                        </button>
                        <button class="cfg-nav-link <?php echo $active_tab === 'tabFinanceiro' ? 'active' : ''; ?>"
                            data-pane="tabFinanceiro">
                            <i class="bi bi-currency-dollar"></i>Financeiro
                        </button>

                        <div class="cfg-nav-sep mt-2">Segurança</div>
                        <button class="cfg-nav-link <?php echo $active_tab === 'tabSeguranca' ? 'active' : ''; ?>"
                            data-pane="tabSeguranca">
                            <i class="bi bi-shield-lock"></i>Segurança
                        </button>
                        <button class="cfg-nav-link <?php echo $active_tab === 'tabWhitelist' ? 'active' : ''; ?>"
                            data-pane="tabWhitelist">
                            <i class="bi bi-list-check"></i>Whitelist IP
                            <?php if ($ac['ip_whitelist_on']): ?>
                            <span class="cfg-badge" style="background:#22c55e">ON</span>
                            <?php endif; ?>
                        </button>

                        <div class="cfg-nav-sep mt-2">Comunicação</div>
                        <button class="cfg-nav-link <?php echo $active_tab === 'tabEmail' ? 'active' : ''; ?>"
                            data-pane="tabEmail">
                            <i class="bi bi-envelope-paper"></i>Email SMTP
                        </button>
                        <button class="cfg-nav-link <?php echo $active_tab === 'tabIntegracoes' ? 'active' : ''; ?>"
                            data-pane="tabIntegracoes">
                            <i class="bi bi-plug"></i>Integrações
                        </button>

                        <div class="cfg-nav-sep mt-2">Sistema</div>
                        <button class="cfg-nav-link <?php echo $active_tab === 'tabLogs' ? 'active' : ''; ?>"
                            data-pane="tabLogs">
                            <i class="bi bi-journal-text"></i>Logs & Auditoria
                            <?php if ($log_stats['today'] > 0): ?>
                            <span class="cfg-badge" style="background:#6b7280"><?php echo $log_stats['today']; ?></span>
                            <?php endif; ?>
                        </button>
                    </nav>

                    <!-- ═══ CONTEÚDO ═══ -->
                    <div style="flex:1;min-width:0">

                        <!-- ════════════════════════════════════════ -->
                        <!-- TAB GERAL -->
                        <!-- ════════════════════════════════════════ -->
                        <div class="cfg-pane <?php echo $active_tab === 'tabGeral' ? 'active' : ''; ?>" id="tabGeral">
                            <form class="cfg-form" data-section="geral" data-label="Geral">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                <input type="hidden" name="action" value="save_section">
                                <input type="hidden" name="section" value="geral">

                                <!-- Identidade -->
                                <div class="cfg-card">
                                    <div class="cfg-card-title"><i class="bi bi-building"></i>Identidade da Plataforma
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="cfg-label">Nome da plataforma <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="site_name" class="form-control"
                                                placeholder="ex: Wasom Upfy"
                                                value="<?php echo htmlspecialchars($sc['site_name']); ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">Aparece em emails, títulos e rodapés. →
                                                <code>_site_config.site_name</code>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="cfg-label">Versão da plataforma</label>
                                            <input type="text" name="platform_version" class="form-control"
                                                value="<?php echo htmlspecialchars($pl['version']); ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_platform.version</code></div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="cfg-label">Nº de lojas exibido</label>
                                            <input type="number" name="stores_count" class="form-control" min="1"
                                                value="<?php echo (int)$pl['stores_count']; ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_platform.stores_count</code></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="cfg-label">URL do site <span
                                                    class="text-danger">*</span></label>
                                            <input type="url" name="site_url" class="form-control"
                                                value="<?php echo htmlspecialchars($sc['site_url']); ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">URL base (sem barra final) →
                                                <code>_site_config.site_url</code>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="cfg-label">E-mail de suporte</label>
                                            <input type="email" name="contact_email" class="form-control"
                                                value="<?php echo htmlspecialchars($pl['contact_email']); ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">Mostrado nos footers e emails →
                                                <code>_platform.contact_email</code>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empresa -->
                                <div class="cfg-card">
                                    <div class="cfg-card-title"><i class="bi bi-geo-alt"></i>Empresa & Localização</div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="cfg-label">País</label>
                                            <input type="text" name="company_country" class="form-control"
                                                value="<?php echo htmlspecialchars($sc['company_country']); ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_site_config.company_country</code></div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="cfg-label">Cidade</label>
                                            <input type="text" name="company_city" class="form-control"
                                                value="<?php echo htmlspecialchars($sc['company_city']); ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_site_config.company_city</code></div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="cfg-label">Telefone</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                                <input type="text" name="company_phone" class="form-control"
                                                    value="<?php echo htmlspecialchars($sc['company_phone']); ?>"
                                                    placeholder="+244 9XX XXX XXX"
                                                    <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            </div>
                                            <div class="cfg-hint">→ <code>_site_config.company_phone</code></div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="cfg-label">Morada</label>
                                            <input type="text" name="company_address" class="form-control"
                                                value="<?php echo htmlspecialchars($sc['company_address']); ?>"
                                                placeholder="Rua, Bairro, Nº"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_site_config.company_address</code></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="cfg-label">E-mail de informação</label>
                                            <input type="email" name="info_email" class="form-control"
                                                value="<?php echo htmlspecialchars($sc['info_email']); ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_site_config.info_email</code></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="cfg-label">E-mail de suporte público</label>
                                            <input type="email" name="support_email" class="form-control"
                                                value="<?php echo htmlspecialchars($sc['support_email']); ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_site_config.support_email</code></div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($can_edit): ?><div class="cfg-save-bar">
                                    <button type="submit" class="btn text-white fw-700" style="background:#FF0089">
                                        <i class="bi bi-floppy me-1"></i>Guardar Geral
                                    </button>
                                </div><?php endif; ?>
                            </form>
                        </div>

                        <!-- ════════════════════════════════════════ -->
                        <!-- TAB DASHBOARD / APP -->
                        <!-- ════════════════════════════════════════ -->
                        <div class="cfg-pane <?php echo $active_tab === 'tabDashboard' ? 'active' : ''; ?>"
                            id="tabDashboard">
                            <form class="cfg-form" data-section="dashboard" data-label="Dashboard">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                <input type="hidden" name="action" value="save_section">
                                <input type="hidden" name="section" value="dashboard">

                                <!-- Estado do dashboard -->
                                <div class="cfg-card">
                                    <div class="cfg-card-title"><i class="bi bi-toggle-on"></i>Estado do Painel de
                                        Utilizadores
                                        <span class="st-pill <?php echo match ($pl['status']) {
                                                                    'active' => 'st-active',
                                                                    'maintenance' => 'st-maintenance',
                                                                    default => 'st-blocked'
                                                                }; ?> ms-auto">
                                            <i class="bi bi-circle-fill" style="font-size:.45rem"></i>
                                            <?php echo match ($pl['status']) {
                                                'active' => 'Activo',
                                                'maintenance' => 'Manutenção',
                                                'blocked' => 'Bloqueado',
                                                'unauthorized' => 'Não autorizado',
                                                default => ucfirst($pl['status'])
                                            }; ?>
                                        </span>
                                    </div>
                                    <div class="alert alert-warning mb-3" style="font-size:.82rem;border-radius:10px">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        Este controlo afecta <strong>apenas o dashboard do utilizador</strong> — o site
                                        público tem controlo independente.
                                        Campo: <code>_platform.status</code>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="cfg-label">Estado</label>
                                            <?php echo sel('dashboard_status', [
                                                'active'       => 'Activo',
                                                'maintenance'  => 'Manutenção',
                                                'blocked'      => 'Bloqueado',
                                                'unauthorized' => 'Não autorizado',
                                            ], $pl['status'], !$can_edit); ?>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="cfg-label">Mensagem de manutenção (mostrada no ecrã)</label>
                                            <input type="text" name="maintenance_msg" class="form-control"
                                                value="<?php echo htmlspecialchars($pl['maintenance_msg']); ?>"
                                                placeholder="Estamos a melhorar o serviço. Brevemente de volta!"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="cfg-label">Início da manutenção</label>
                                            <input type="datetime-local" name="maintenance_start" class="form-control"
                                                value="<?php echo $pl['maintenance_start'] ? date('Y-m-d\TH:i', strtotime($pl['maintenance_start'])) : ''; ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="cfg-label">Fim da manutenção (auto-reactivar)</label>
                                            <input type="datetime-local" name="maintenance_end" class="form-control"
                                                value="<?php echo $pl['maintenance_end'] ? date('Y-m-d\TH:i', strtotime($pl['maintenance_end'])) : ''; ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">Quando chega este datetime → status volta a 'active'
                                                automaticamente.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Acesso -->
                                <div class="cfg-card">
                                    <div class="cfg-card-title"><i class="bi bi-door-open"></i>Controlo de Acesso</div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="cfg-label">Permitir registo de novos utilizadores</label>
                                            <?php echo boolSelect('allow_register', $pl['allow_register'], !$can_edit); ?>
                                            <div class="cfg-hint">→ <code>_platform.allow_register</code></div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="cfg-label">Permitir login</label>
                                            <?php echo boolSelect('allow_login', $pl['allow_login'], !$can_edit); ?>
                                            <div class="cfg-hint">→ <code>_platform.allow_login</code></div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="cfg-label">Timeout de sessão (minutos)</label>
                                            <input type="number" name="session_timeout" class="form-control" min="5"
                                                max="1440" value="<?php echo (int)$ac['session_timeout']; ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_admin_config.session_timeout</code></div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($can_edit): ?><div class="cfg-save-bar">
                                    <button type="submit" class="btn text-white fw-700" style="background:#FF0089">
                                        <i class="bi bi-floppy me-1"></i>Guardar Dashboard
                                    </button>
                                </div><?php endif; ?>
                            </form>
                        </div>

                        <!-- ════════════════════════════════════════ -->
                        <!-- TAB SITE PÚBLICO -->
                        <!-- ════════════════════════════════════════ -->
                        <div class="cfg-pane <?php echo $active_tab === 'tabSite' ? 'active' : ''; ?>" id="tabSite">
                            <form class="cfg-form" data-section="site" data-label="Site Público">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                <input type="hidden" name="action" value="save_section">
                                <input type="hidden" name="section" value="site">

                                <!-- Estado -->
                                <div class="cfg-card">
                                    <div class="cfg-card-title"><i class="bi bi-globe2"></i>Estado do Site Público
                                        <span class="st-pill <?php echo match ($pl['site_status']) {
                                                                    'active' => 'st-active',
                                                                    'maintenance' => 'st-maintenance',
                                                                    default => 'st-blocked'
                                                                }; ?> ms-auto">
                                            <i class="bi bi-circle-fill" style="font-size:.45rem"></i>
                                            <?php echo match ($pl['site_status']) {
                                                'active' => 'Activo',
                                                'maintenance' => 'Manutenção',
                                                'blocked' => 'Bloqueado',
                                                'unauthorized' => 'Não autorizado',
                                                default => ucfirst($pl['site_status'])
                                            }; ?>
                                        </span>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="cfg-label">Estado</label>
                                            <?php echo sel('site_status', [
                                                'active'      => 'Activo',
                                                'maintenance' => 'Manutenção',
                                                'blocked'     => 'Bloqueado',
                                                'unauthorized' => 'Não autorizado',
                                            ], $pl['site_status'], !$can_edit); ?>
                                            <div class="cfg-hint">→ <code>_platform.site_status</code></div>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="cfg-label">Mensagem de manutenção do site</label>
                                            <input type="text" name="site_maintenance_msg" class="form-control"
                                                value="<?php echo htmlspecialchars($pl['site_maintenance_msg']); ?>"
                                                placeholder="Estamos a melhorar. Brevemente de volta!"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="cfg-label">Início da manutenção</label>
                                            <input type="datetime-local" name="site_maintenance_start"
                                                class="form-control"
                                                value="<?php echo $pl['site_maintenance_start'] ? date('Y-m-d\TH:i', strtotime($pl['site_maintenance_start'])) : ''; ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="cfg-label">Fim da manutenção</label>
                                            <input type="datetime-local" name="site_maintenance_end"
                                                class="form-control"
                                                value="<?php echo $pl['site_maintenance_end'] ? date('Y-m-d\TH:i', strtotime($pl['site_maintenance_end'])) : ''; ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                        </div>
                                    </div>
                                </div>

                                <!-- Redes Sociais -->
                                <div class="cfg-card">
                                    <div class="cfg-card-title"><i class="bi bi-share"></i>Redes Sociais & Contacto
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="cfg-label"><i class="bi bi-whatsapp me-1"
                                                    style="color:#25d366"></i>Número WhatsApp</label>
                                            <div class="input-group">
                                                <span class="input-group-text">+244</span>
                                                <input type="text" name="whatsapp_number" class="form-control"
                                                    value="<?php echo htmlspecialchars(ltrim($sc['whatsapp_number'], '+244')); ?>"
                                                    placeholder="9XX XXX XXX"
                                                    <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            </div>
                                            <div class="cfg-hint">→ <code>_site_config.whatsapp_number</code></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="cfg-label"><i class="bi bi-whatsapp me-1"
                                                    style="color:#25d366"></i>URL do Canal WhatsApp</label>
                                            <input type="url" name="whatsapp_channel_url" class="form-control"
                                                value="<?php echo htmlspecialchars($sc['whatsapp_channel_url']); ?>"
                                                placeholder="https://whatsapp.com/channel/..."
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_site_config.whatsapp_channel_url</code></div>
                                        </div>
                                        <?php
                                        $socials = [
                                            ['instagram_url', 'bi-instagram', '#e1306c', 'Instagram', 'https://instagram.com/wasomupfy'],
                                            ['facebook_url',  'bi-facebook',  '#1877f2', 'Facebook',  'https://facebook.com/wasom.official'],
                                            ['youtube_url',   'bi-youtube',   '#ff0000', 'YouTube',   'https://youtube.com/@wasomupfy'],
                                            ['linkedin_url',  'bi-linkedin',  '#0a66c2', 'LinkedIn',  'https://linkedin.com/company/wasom-upfy'],
                                            ['threads_url',   'bi-threads',   '#000000', 'Threads',   'https://threads.net/wasomupfy'],
                                            ['twitter_url',   'bi-twitter-x', '#000000', 'X (Twitter)', 'https://x.com/wasomupfy'],
                                            ['tiktok_url',    'bi-tiktok',    '#010101', 'TikTok',    'https://tiktok.com/@wasomupfy'],
                                        ];
                                        foreach ($socials as [$key, $icon, $color, $label, $placeholder]):
                                        ?>
                                        <div class="col-md-6">
                                            <label class="cfg-label"><i class="bi <?php echo $icon; ?> me-1"
                                                    style="color:<?php echo $color; ?>"></i><?php echo $label; ?></label>
                                            <input type="url" name="<?php echo $key; ?>" class="form-control"
                                                value="<?php echo htmlspecialchars($sc[$key]); ?>"
                                                placeholder="<?php echo $placeholder; ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_site_config.<?php echo $key; ?></code></div>
                                        </div>
                                        <?php endforeach; ?>
                                        <div class="col-md-6">
                                            <label class="cfg-label"><i class="bi bi-youtube me-1"
                                                    style="color:#ff0000"></i>ID do vídeo tutorial (YouTube)</label>
                                            <input type="text" name="youtube_tutorial_id" class="form-control"
                                                value="<?php echo htmlspecialchars($sc['youtube_tutorial_id']); ?>"
                                                placeholder="dQw4w9WgXcQ" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">Apenas o ID do vídeo →
                                                <code>_site_config.youtube_tutorial_id</code>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Misc -->
                                <div class="cfg-card">
                                    <div class="cfg-card-title"><i class="bi bi-toggles"></i>Funcionalidades do Site
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="cfg-label">Aviso de cookies</label>
                                            <?php echo boolSelect('cookie_consent_enabled', $sc['cookie_consent_enabled'], !$can_edit); ?>
                                            <div class="cfg-hint">→ <code>_site_config.cookie_consent_enabled</code>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="cfg-label">Banner de aviso no topo do site</label>
                                            <input type="text" name="maintenance_banner" class="form-control"
                                                value="<?php echo htmlspecialchars($sc['maintenance_banner']); ?>"
                                                placeholder="Deixa vazio para não mostrar nenhum banner"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_site_config.maintenance_banner</code></div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($can_edit): ?><div class="cfg-save-bar">
                                    <button type="submit" class="btn text-white fw-700" style="background:#FF0089">
                                        <i class="bi bi-floppy me-1"></i>Guardar Site Público
                                    </button>
                                </div><?php endif; ?>
                            </form>
                        </div>

                        <!-- ════════════════════════════════════════ -->
                        <!-- TAB FINANCEIRO -->
                        <!-- ════════════════════════════════════════ -->
                        <div class="cfg-pane <?php echo $active_tab === 'tabFinanceiro' ? 'active' : ''; ?>"
                            id="tabFinanceiro">
                            <form class="cfg-form" data-section="financeiro" data-label="Financeiro">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                <input type="hidden" name="action" value="save_section">
                                <input type="hidden" name="section" value="financeiro">

                                <!-- Royalties -->
                                <div class="cfg-card">
                                    <div class="cfg-card-title"><i class="bi bi-pie-chart"></i>Royalties & Taxas</div>
                                    <div class="alert alert-info mb-3" style="font-size:.8rem;border-radius:10px">
                                        <i class="bi bi-info-circle me-1"></i>
                                        A soma de <code>royalty_percentage + platform_fee</code> deve ser 100%.
                                        Actualmente:
                                        <strong><?php echo (float)$pl['royalty_percentage'] + (float)$pl['platform_fee']; ?>%</strong>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="cfg-label">Royalty do artista (%)</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" min="0" max="100"
                                                    name="royalty_percentage" class="form-control"
                                                    value="<?php echo (float)$pl['royalty_percentage']; ?>"
                                                    <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="cfg-hint">→ <code>_platform.royalty_percentage</code></div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="cfg-label">Taxa da plataforma (%)</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" min="0" max="100" name="platform_fee"
                                                    class="form-control"
                                                    value="<?php echo (float)$pl['platform_fee']; ?>"
                                                    <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="cfg-hint">→ <code>_platform.platform_fee</code></div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="cfg-label">Moeda padrão</label>
                                            <?php echo sel('currency_default', ['AOA' => 'AOA — Kwanza', 'USD' => 'USD — Dólar', 'EUR' => 'EUR — Euro'], $pl['currency_default'], !$can_edit); ?>
                                            <div class="cfg-hint">→ <code>_platform.currency_default</code></div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="cfg-label">Taxa USD → AOA</label>
                                            <div class="input-group">
                                                <span class="input-group-text">1 USD =</span>
                                                <input type="number" step="0.01" min="1" name="usd_to_aoa_rate"
                                                    class="form-control"
                                                    value="<?php echo (float)$pl['usd_to_aoa_rate']; ?>"
                                                    <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                                <span class="input-group-text">AOA</span>
                                            </div>
                                            <div class="cfg-hint">→ <code>_platform.usd_to_aoa_rate</code></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pagamento -->
                                <div class="cfg-card">
                                    <div class="cfg-card-title"><i class="bi bi-credit-card"></i>Fluxo de Pagamento
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="cfg-label">Auto-aprovação após (minutos)</label>
                                            <input type="number" min="5" max="1440" name="payment_auto_approve_minutes"
                                                class="form-control"
                                                value="<?php echo (int)$ac['payment_auto_approve_minutes']; ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">Single/Álbum/Artista aprovados após este tempo. →
                                                <code>_admin_config</code>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="cfg-label">Expiração do intent (minutos)</label>
                                            <input type="number" min="15" max="1440"
                                                name="payment_intent_expiry_minutes" class="form-control"
                                                value="<?php echo (int)$ac['payment_intent_expiry_minutes']; ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">Tempo máximo para enviar comprovativo. →
                                                <code>_admin_config</code>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="cfg-label">Máx. tentativas de upload</label>
                                            <input type="number" min="1" max="10" name="payment_max_attempts"
                                                class="form-control"
                                                value="<?php echo (int)$ac['payment_max_attempts']; ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">Por referência de pagamento. →
                                                <code>_admin_config</code>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($can_edit): ?><div class="cfg-save-bar">
                                    <button type="submit" class="btn text-white fw-700" style="background:#FF0089">
                                        <i class="bi bi-floppy me-1"></i>Guardar Financeiro
                                    </button>
                                </div><?php endif; ?>
                            </form>
                        </div>

                        <!-- ════════════════════════════════════════ -->
                        <!-- TAB SEGURANÇA -->
                        <!-- ════════════════════════════════════════ -->
                        <div class="cfg-pane <?php echo $active_tab === 'tabSeguranca' ? 'active' : ''; ?>"
                            id="tabSeguranca">
                            <form class="cfg-form" data-section="seguranca" data-label="Segurança">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                <input type="hidden" name="action" value="save_section">
                                <input type="hidden" name="section" value="seguranca">

                                <!-- Bloqueio de login -->
                                <div class="cfg-card">
                                    <div class="cfg-card-title"><i class="bi bi-shield-exclamation"></i>Bloqueio de
                                        Login (Utilizadores)</div>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="cfg-label">Máx. tentativas antes bloqueio</label>
                                            <input type="number" min="1" max="20" name="max_login_attempts"
                                                class="form-control"
                                                value="<?php echo (int)$ac['max_login_attempts']; ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_admin_config.max_login_attempts</code></div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="cfg-label">Bloqueio Nível 1 (3 tent.) — min</label>
                                            <input type="number" min="1" name="block_level_1_min" class="form-control"
                                                value="<?php echo (int)$ac['block_level_1_min']; ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_admin_config.block_level_1_min</code></div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="cfg-label">Bloqueio Nível 2 (5 tent.) — min</label>
                                            <input type="number" min="1" name="block_level_2_min" class="form-control"
                                                value="<?php echo (int)$ac['block_level_2_min']; ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_admin_config.block_level_2_min</code></div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="cfg-label">Bloqueio Nível 3 (7+ tent.) — min</label>
                                            <input type="number" min="1" name="block_level_3_min" class="form-control"
                                                value="<?php echo (int)$ac['block_level_3_min']; ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_admin_config.block_level_3_min</code></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Caminho admin -->
                                <div class="cfg-card">
                                    <div class="cfg-card-title"><i class="bi bi-folder-lock"></i>Caminho do Painel Admin
                                    </div>
                                    <div class="alert alert-danger mb-3" style="font-size:.8rem;border-radius:10px">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        <strong>Atenção:</strong> Alterar o caminho aqui actualiza a BD e o .htaccess
                                        mas
                                        <strong>não renomeia a pasta automaticamente</strong>.
                                        Usa a página de segurança dedicada em
                                        <a href="<?php echo $base_url; ?>/settings/security"
                                            style="color:inherit">Configurações → Segurança</a>.
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="cfg-label">Caminho actual do admin</label>
                                            <div class="input-group">
                                                <span class="input-group-text">/wasomupfy/</span>
                                                <input type="text" class="form-control"
                                                    value="<?php echo htmlspecialchars(ADMIN_PATH); ?>" disabled>
                                            </div>
                                            <div class="cfg-hint">Activo agora. Para rodar usa a página de Segurança.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="cfg-label">Valor em <code>_admin_config</code></label>
                                            <input type="text" class="form-control"
                                                value="<?php echo htmlspecialchars($ac['admin_path']); ?>" disabled>
                                            <div class="cfg-hint">Se diferente do activo → rota pode estar
                                                desincronizada.</div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($can_edit): ?><div class="cfg-save-bar">
                                    <button type="submit" class="btn text-white fw-700" style="background:#FF0089">
                                        <i class="bi bi-floppy me-1"></i>Guardar Segurança
                                    </button>
                                    <a href="<?php echo $base_url; ?>/settings/security"
                                        class="btn btn-outline-secondary">
                                        <i class="bi bi-shield-shaded me-1"></i>Segurança Avançada (rotação caminho,
                                        whitelist IP)
                                    </a>
                                </div><?php endif; ?>
                            </form>
                        </div>

                        <!-- ════════════════════════════════════════ -->
                        <!-- TAB EMAIL SMTP -->
                        <!-- ════════════════════════════════════════ -->
                        <div class="cfg-pane <?php echo $active_tab === 'tabEmail' ? 'active' : ''; ?>" id="tabEmail">
                            <form class="cfg-form" data-section="email" data-label="Email SMTP">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                <input type="hidden" name="action" value="save_section">
                                <input type="hidden" name="section" value="email">

                                <div class="cfg-card">
                                    <div class="cfg-card-title"><i class="bi bi-server"></i>Servidor SMTP</div>
                                    <div class="alert alert-warning mb-3" style="font-size:.8rem;border-radius:10px">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Actualmente a usar as constantes de <code>config.php</code>. Guardar aqui
                                        sobrepõe os valores em <code>_admin_config</code>.
                                        O WasomMailer lê preferencialmente desta tabela quando existirem entradas.
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="cfg-label">Host SMTP <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="smtp_host" class="form-control"
                                                value="<?php echo htmlspecialchars($ac['smtp_host']); ?>"
                                                placeholder="smtp.gmail.com"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="cfg-label">Porta</label>
                                            <?php echo sel('smtp_port', ['587' => '587 (TLS — recomendado)', '465' => '465 (SSL)', '25' => '25 (sem encriptação)'], $ac['smtp_port'], !$can_edit); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="cfg-label">Encriptação</label>
                                            <?php echo sel('smtp_encryption', ['tls' => 'TLS (STARTTLS)', 'ssl' => 'SSL', '' => 'Nenhuma'], $ac['smtp_encryption'], !$can_edit); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="cfg-label">Utilizador SMTP (email da conta) <span
                                                    class="text-danger">*</span></label>
                                            <input type="email" name="smtp_user" class="form-control"
                                                value="<?php echo htmlspecialchars($ac['smtp_user']); ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="cfg-label">App Password (Gmail) / Senha SMTP</label>
                                            <div class="input-group">
                                                <input type="password" name="smtp_pass" class="form-control"
                                                    id="smtpPassInput"
                                                    value="<?php echo htmlspecialchars($ac['smtp_pass']); ?>"
                                                    autocomplete="new-password"
                                                    placeholder="Deixa vazio para não alterar"
                                                    <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                                <?php if ($can_edit): ?>
                                                <button type="button" class="btn btn-outline-secondary"
                                                    onclick="const i=document.getElementById('smtpPassInput');i.type=i.type==='password'?'text':'password'">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                            <div class="cfg-hint">Gmail: gera em myaccount.google.com/apppasswords</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="cfg-label">E-mail remetente (FROM) — deve = utilizador no
                                                Gmail</label>
                                            <input type="email" name="mail_from_address" class="form-control"
                                                value="<?php echo htmlspecialchars($ac['mail_from_address']); ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="cfg-label">Nome remetente</label>
                                            <input type="text" name="mail_from_name" class="form-control"
                                                value="<?php echo htmlspecialchars($ac['mail_from_name']); ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="cfg-label">Debug SMTP</label>
                                            <?php echo sel('mail_debug', ['0' => '0 (Off)', '1' => '1 (Cliente)', '2' => '2 (Servidor)', '3' => '3 (Tudo)'], $ac['mail_debug'], !$can_edit); ?>
                                            <div class="cfg-hint">0 em produção</div>
                                        </div>
                                    </div>
                                    <?php if ($can_edit): ?>
                                    <div class="mt-3 d-flex gap-2">
                                        <button type="button" class="btn btn-outline-primary" id="testEmailBtn">
                                            <i class="bi bi-envelope-check me-1"></i>Enviar e-mail de teste
                                        </button>
                                        <div style="font-size:.75rem;color:var(--text-muted,#888);align-self:center">
                                            Guarda primeiro, depois testa.
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($can_edit): ?><div class="cfg-save-bar">
                                    <button type="submit" class="btn text-white fw-700" style="background:#FF0089">
                                        <i class="bi bi-floppy me-1"></i>Guardar Email SMTP
                                    </button>
                                </div><?php endif; ?>
                            </form>
                        </div>

                        <!-- ════════════════════════════════════════ -->
                        <!-- TAB INTEGRAÇÕES -->
                        <!-- ════════════════════════════════════════ -->
                        <div class="cfg-pane <?php echo $active_tab === 'tabIntegracoes' ? 'active' : ''; ?>"
                            id="tabIntegracoes">
                            <form class="cfg-form" data-section="integracoes" data-label="Integrações">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                <input type="hidden" name="action" value="save_section">
                                <input type="hidden" name="section" value="integracoes">

                                <!-- VAPID Push Notifications -->
                                <div class="cfg-card">
                                    <div class="cfg-card-title"><i class="bi bi-bell-fill"></i>Push Notifications
                                        (VAPID)</div>
                                    <div class="alert alert-info mb-3" style="font-size:.8rem;border-radius:10px">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Chaves VAPID para Web Push Notifications. Gera novas em
                                        <a href="https://vapidkeys.com" target="_blank">vapidkeys.com</a>.
                                        <strong>Atenção:</strong> trocar as chaves invalida todas as subscrições
                                        existentes dos utilizadores.
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="cfg-label">Chave Pública VAPID</label>
                                            <input type="text" name="vapid_public_key" class="form-control"
                                                style="font-family:monospace;font-size:.78rem"
                                                value="<?php echo htmlspecialchars($ac['vapid_public_key']); ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_admin_config.vapid_public_key</code> | No js:
                                                <code>applicationServerKey</code>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="cfg-label">Chave Privada VAPID (confidencial)</label>
                                            <div class="input-group">
                                                <input type="password" name="vapid_private_key" id="vapidPriv"
                                                    class="form-control" style="font-family:monospace;font-size:.78rem"
                                                    value="<?php echo htmlspecialchars($ac['vapid_private_key']); ?>"
                                                    <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                                <?php if ($can_edit): ?>
                                                <button type="button" class="btn btn-outline-secondary"
                                                    onclick="const i=document.getElementById('vapidPriv');i.type=i.type==='password'?'text':'password'">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                            <div class="cfg-hint">Nunca expor no frontend →
                                                <code>_admin_config.vapid_private_key</code>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="cfg-label">Subject VAPID (mailto ou URL)</label>
                                            <input type="text" name="vapid_subject" class="form-control"
                                                value="<?php echo htmlspecialchars($ac['vapid_subject']); ?>"
                                                placeholder="mailto:suporte@wasomupfy.com"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">→ <code>_admin_config.vapid_subject</code></div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($can_edit): ?><div class="cfg-save-bar">
                                    <button type="submit" class="btn text-white fw-700" style="background:#FF0089">
                                        <i class="bi bi-floppy me-1"></i>Guardar Integrações
                                    </button>
                                </div><?php endif; ?>
                            </form>
                        </div>

                        <!-- ════════════════════════════════════════ -->
                        <!-- TAB WHITELIST IP -->
                        <!-- ════════════════════════════════════════ -->
                        <div class="cfg-pane <?php echo $active_tab === 'tabWhitelist' ? 'active' : ''; ?>"
                            id="tabWhitelist">
                            <div class="cfg-card">
                                <div class="cfg-card-title">
                                    <i class="bi bi-list-check"></i>Whitelist de IPs do Admin
                                    <span
                                        class="ms-auto <?php echo $ac['ip_whitelist_on'] ? 'badge bg-success' : 'badge bg-secondary'; ?>">
                                        <?php echo $ac['ip_whitelist_on'] ? 'ACTIVA' : 'INACTIVA'; ?>
                                    </span>
                                </div>

                                <?php if ($can_edit): ?>
                                <!-- Toggle whitelist -->
                                <form class="cfg-form mb-4" data-section="whitelist_toggle"
                                    data-label="Toggle Whitelist">
                                    <input type="hidden" name="csrf_token"
                                        value="<?php echo htmlspecialchars($csrf); ?>">
                                    <input type="hidden" name="action" value="save_section">
                                    <input type="hidden" name="section" value="whitelist_toggle">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="form-check form-switch mb-0" style="font-size:.9rem">
                                            <input class="form-check-input" type="checkbox" name="ip_whitelist_on"
                                                id="whitelistToggle" value="1"
                                                <?php echo $ac['ip_whitelist_on'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="whitelistToggle">
                                                Activar whitelist
                                            </label>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Actualizar</button>
                                    </div>
                                    <div style="font-size:.75rem;color:var(--text-muted,#888);margin-top:4px">
                                        Quando activa, só IPs na lista podem aceder ao painel admin.
                                        O teu IP actual (<code><?php echo htmlspecialchars($my_ip); ?></code>) é
                                        protegido automaticamente.
                                    </div>
                                </form>

                                <!-- Adicionar IP -->
                                <div class="d-flex gap-2 mb-4">
                                    <input type="text" id="newIpInput" class="form-control" style="max-width:200px"
                                        placeholder="192.168.1.1" value="<?php echo htmlspecialchars($my_ip); ?>">
                                    <input type="text" id="newIpLabel" class="form-control" style="max-width:200px"
                                        placeholder="Descrição (ex: Escritório)">
                                    <button type="button" class="btn text-white" style="background:#FF0089"
                                        id="addIpBtn">
                                        <i class="bi bi-plus-lg me-1"></i>Adicionar
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="addMyIpBtn">
                                        <i class="bi bi-geo me-1"></i>Adicionar meu IP
                                    </button>
                                </div>
                                <?php endif; ?>

                                <!-- Lista de IPs -->
                                <div id="ipList">
                                    <?php if (empty($ip_whitelist)): ?>
                                    <div
                                        style="text-align:center;padding:24px;color:var(--text-muted,#888);font-size:.85rem">
                                        <i class="bi bi-shield-slash"
                                            style="font-size:2rem;display:block;margin-bottom:8px"></i>
                                        Nenhum IP na whitelist ainda.
                                    </div>
                                    <?php else: ?>
                                    <?php foreach ($ip_whitelist as $wip):
                                            $is_mine = $wip['ip_address'] === $my_ip;
                                        ?>
                                    <div class="ip-row <?php echo $is_mine ? 'ip-mine' : ''; ?>"
                                        id="ip-row-<?php echo (int)$wip['id_ip']; ?>">
                                        <i class="bi bi-shield-check"
                                            style="color:<?php echo $is_mine ? '#FF0089' : '#22c55e'; ?>;font-size:1.1rem;flex-shrink:0"></i>
                                        <div style="flex:1;min-width:0">
                                            <div class="ip-val"><?php echo htmlspecialchars($wip['ip_address']); ?>
                                                <?php if ($is_mine): ?>
                                                <span class="badge ms-1" style="background:#FF0089;font-size:.6rem">O
                                                    meu IP</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ip-lbl">
                                                <?php echo htmlspecialchars($wip['label'] ?? 'Sem descrição'); ?>
                                                &middot; Adicionado:
                                                <?php echo date('d/m/Y H:i', strtotime($wip['creat_ip'])); ?>
                                            </div>
                                        </div>
                                        <?php if ($can_edit && !$is_mine): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="removeIp(<?php echo (int)$wip['id_ip']; ?>,this)" title="Remover">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php elseif ($can_edit && $is_mine): ?>
                                        <span style="font-size:.72rem;color:var(--text-muted,#888)">Protegido</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- ════════════════════════════════════════ -->
                        <!-- TAB LOGS & AUDITORIA -->
                        <!-- ════════════════════════════════════════ -->
                        <div class="cfg-pane <?php echo $active_tab === 'tabLogs' ? 'active' : ''; ?>" id="tabLogs">
                            <form class="cfg-form" data-section="logs" data-label="Logs">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                <input type="hidden" name="action" value="save_section">
                                <input type="hidden" name="section" value="logs">

                                <!-- Configuração -->
                                <div class="cfg-card">
                                    <div class="cfg-card-title"><i class="bi bi-sliders2"></i>Configuração de Logs</div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-4">
                                            <label class="cfg-label">Retenção de logs (dias)</label>
                                            <input type="number" min="7" max="365" name="log_retention_days"
                                                class="form-control"
                                                value="<?php echo (int)$ac['log_retention_days']; ?>"
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <div class="cfg-hint">Logs mais antigos que este valor podem ser eliminados.
                                                → <code>_admin_config</code></div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="cfg-label">Nível de log</label>
                                            <?php echo sel('log_level', [
                                                'debug'   => 'Debug (tudo)',
                                                'info'    => 'Info',
                                                'warning' => 'Warning',
                                                'error'   => 'Erro apenas',
                                            ], $ac['log_level'], !$can_edit); ?>
                                            <div class="cfg-hint">→ <code>_admin_config.log_level</code></div>
                                        </div>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-auto">
                                            <div class="cfg-stat" style="min-width:120px">
                                                <div class="cfg-stat-val">
                                                    <?php echo number_format($log_stats['today']); ?></div>
                                                <div class="cfg-stat-lbl">Hoje</div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="cfg-stat" style="min-width:120px">
                                                <div class="cfg-stat-val">
                                                    <?php echo number_format($log_stats['week']); ?></div>
                                                <div class="cfg-stat-lbl">7 dias</div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="cfg-stat" style="min-width:120px">
                                                <div class="cfg-stat-val">
                                                    <?php echo number_format($log_stats['total']); ?></div>
                                                <div class="cfg-stat-lbl">Total</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="<?php echo $base_url; ?>/audit"
                                            class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-eye me-1"></i>Ver todos os logs de auditoria
                                        </a>
                                        <?php if ($can_edit): ?>
                                        <button type="button" class="btn btn-outline-danger btn-sm" id="clearLogsBtn">
                                            <i class="bi bi-trash me-1"></i>Eliminar logs antigos (>
                                            <?php echo (int)$ac['log_retention_days']; ?> dias)
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Logs recentes -->
                                <div class="cfg-card">
                                    <div class="cfg-card-title"><i class="bi bi-clock-history"></i>Actividade Recente
                                        (últimas 20 acções)</div>
                                    <?php if (empty($recent_logs)): ?>
                                    <div
                                        style="text-align:center;padding:24px;color:var(--text-muted,#888);font-size:.85rem">
                                        Nenhum log registado.</div>
                                    <?php else: ?>
                                    <?php foreach ($recent_logs as $log):
                                            $action_color = match (true) {
                                                str_contains($log['action'], 'delete') || str_contains($log['action'], 'reject') => '#ef4444',
                                                str_contains($log['action'], 'approve') || str_contains($log['action'], 'active') => '#22c55e',
                                                str_contains($log['action'], 'login')   => '#3b82f6',
                                                default => '#FF0089',
                                            };
                                        ?>
                                    <div class="log-row">
                                        <div
                                            style="flex-shrink:0;width:8px;height:8px;border-radius:50%;background:<?php echo $action_color; ?>;margin-top:5px">
                                        </div>
                                        <div style="flex:1;min-width:0">
                                            <div class="log-action" style="color:<?php echo $action_color; ?>">
                                                <?php echo htmlspecialchars($log['action']); ?></div>
                                            <div class="log-entity">
                                                <?php echo htmlspecialchars($log['entity'] ?? ''); ?>
                                                <?php if ($log['entity_id']): ?>#<?php echo (int)$log['entity_id']; ?><?php endif; ?>
                                                &middot;
                                                <strong><?php echo htmlspecialchars($log['emp_name'] ?? 'Sistema'); ?></strong>
                                                &middot;
                                                <code><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></code>
                                            </div>
                                        </div>
                                        <div class="log-time">
                                            <?php echo date('d/m H:i', strtotime($log['creat_log'])); ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <?php if ($can_edit): ?><div class="cfg-save-bar">
                                    <button type="submit" class="btn text-white fw-700" style="background:#FF0089">
                                        <i class="bi bi-floppy me-1"></i>Guardar Logs
                                    </button>
                                </div><?php endif; ?>
                            </form>
                        </div>

                    </div><!-- /conteúdo -->
                </div><!-- /cfg-layout -->

            </div><!-- /p-4 -->
        </div><!-- /content -->
    </div><!-- /wrapper -->

    <!-- ════ PAGE LOADER ════ -->
    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="">
            <div class="loader-progress"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
    (function() {
        'use strict';

        window.__BASE_URL__ = '<?php echo APP_URL; ?>';
        window.__ADMIN_PATH__ = '<?php echo ADMIN_PATH; ?>';

        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const PROCESS = window.__BASE_URL__ + '/' + window.__ADMIN_PATH__ + '/settings/config-process';
        const canEdit = <?php echo $can_edit ? 'true' : 'false'; ?>;

        // ── Navegação de tabs ─────────────────────────────────────
        const panes = document.querySelectorAll('.cfg-pane');
        const links = document.querySelectorAll('.cfg-nav-link[data-pane]');

        links.forEach(btn => {
            btn.addEventListener('click', function() {
                const target = this.dataset.pane;
                panes.forEach(p => p.classList.remove('active'));
                links.forEach(l => l.classList.remove('active'));
                document.getElementById(target)?.classList.add('active');
                this.classList.add('active');
                // Actualizar URL
                const url = new URL(window.location);
                const tabKey = Object.entries(<?php echo json_encode($tab_map); ?>).find(([k, v]) =>
                    v === target)?. [0] || '';
                if (tabKey) url.searchParams.set('tab', tabKey);
                else url.searchParams.delete('tab');
                history.replaceState({}, '', url);
            });
        });

        // ── AJAX helper ───────────────────────────────────────────
        async function post(payload) {
            const fd = new FormData();
            Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
            fd.append('csrf_token', CSRF);
            try {
                const r = await fetch(PROCESS, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: fd
                });
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const txt = await r.text();
                try {
                    return JSON.parse(txt);
                } catch {
                    console.error('Resposta inválida:', txt);
                    throw new Error('JSON inválido');
                }
            } catch (e) {
                console.error('[CONFIG POST]', e);
                return {
                    ok: false,
                    message: 'Erro de ligação ao servidor.'
                };
            }
        }

        function swal(icon, title, text) {
            return Swal.fire({
                icon,
                title,
                text,
                confirmButtonColor: '#FF0089'
            });
        }

        // ── Guardar secção (cada form) ────────────────────────────
        document.querySelectorAll('.cfg-form').forEach(form => {
            form.addEventListener('submit', async e => {
                e.preventDefault();
                if (!canEdit) return;
                const btn = form.querySelector('[type=submit]');
                const origHtml = btn.innerHTML;
                btn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1"></span>A guardar...';
                btn.disabled = true;

                const fd = new FormData(form);
                fd.set('csrf_token', CSRF);
                try {
                    const r = await fetch(PROCESS, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: fd
                    });
                    const data = await r.json();
                    if (data.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Guardado!',
                            text: data.message || form.dataset.label + ' guardado.',
                            timer: 2500,
                            showConfirmButton: false,
                            confirmButtonColor: '#FF0089'
                        });
                    } else {
                        swal('error', 'Erro', data.message);
                    }
                } catch (err) {
                    swal('error', 'Erro', 'Não foi possível guardar. Verifica a tua ligação.');
                } finally {
                    btn.innerHTML = origHtml;
                    btn.disabled = false;
                }
            });
        });

        // ── Teste de email ────────────────────────────────────────
        document.getElementById('testEmailBtn')?.addEventListener('click', async () => {
            const form = document.querySelector('#tabEmail .cfg-form');
            const fd = new FormData(form);
            fd.set('csrf_token', CSRF);
            fd.set('action', 'test_email');
            Swal.fire({
                title: 'A enviar...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            try {
                const r = await fetch(PROCESS, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: fd
                });
                const data = await r.json();
                swal(data.ok ? 'success' : 'error', data.ok ? 'E-mail enviado!' : 'Falha', data
                    .message);
            } catch {
                swal('error', 'Erro', 'Não foi possível testar o email.');
            }
        });

        // ── Whitelist IP — adicionar ──────────────────────────────
        function addIp(ip, label) {
            if (!ip) return;
            post({
                action: 'add_ip',
                ip_address: ip,
                label: label || ''
            }).then(data => {
                if (data.ok) {
                    // Adicionar linha sem reload
                    const list = document.getElementById('ipList');
                    const empty = list.querySelector('[style*="text-align:center"]');
                    if (empty) empty.remove();
                    const div = document.createElement('div');
                    div.className = 'ip-row';
                    div.id = 'ip-row-' + (data.id || 'new');
                    div.innerHTML = `<i class="bi bi-shield-check" style="color:#22c55e;font-size:1.1rem;flex-shrink:0"></i>
                    <div style="flex:1;min-width:0">
                        <div class="ip-val">${ip}</div>
                        <div class="ip-lbl">${label || 'Sem descrição'} &middot; Agora</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeIp(${data.id},this)">
                        <i class="bi bi-trash"></i>
                    </button>`;
                    list.prepend(div);
                    Swal.fire({
                        icon: 'success',
                        title: 'IP adicionado!',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    swal('error', 'Erro', data.message);
                }
            });
        }

        document.getElementById('addIpBtn')?.addEventListener('click', () => {
            const ip = document.getElementById('newIpInput').value.trim();
            const lbl = document.getElementById('newIpLabel').value.trim();
            addIp(ip, lbl);
        });
        document.getElementById('addMyIpBtn')?.addEventListener('click', () => {
            const ip = '<?php echo htmlspecialchars($my_ip); ?>';
            document.getElementById('newIpInput').value = ip;
            addIp(ip, 'O meu IP');
        });

        // ── Whitelist IP — remover ────────────────────────────────
        window.removeIp = async function(id, btn) {
            const {
                isConfirmed
            } = await Swal.fire({
                title: 'Remover IP?',
                text: 'Será removido da whitelist imediatamente.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Remover',
                cancelButtonText: 'Cancelar'
            });
            if (!isConfirmed) return;
            const data = await post({
                action: 'remove_ip',
                id_ip: id
            });
            if (data.ok) {
                document.getElementById('ip-row-' + id)?.remove();
                Swal.fire({
                    icon: 'success',
                    title: 'Removido!',
                    timer: 1200,
                    showConfirmButton: false
                });
            } else {
                swal('error', 'Erro', data.message);
            }
        };

        // ── Limpar logs ───────────────────────────────────────────
        document.getElementById('clearLogsBtn')?.addEventListener('click', async () => {
            const days = document.querySelector('[name="log_retention_days"]')?.value || 90;
            const {
                isConfirmed
            } = await Swal.fire({
                title: 'Eliminar logs antigos?',
                text: `Remove todos os registos com mais de ${days} dias.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Eliminar',
            });
            if (!isConfirmed) return;
            Swal.fire({
                title: 'A eliminar...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            const data = await post({
                action: 'clear_old_logs',
                retention_days: days
            });
            swal(data.ok ? 'success' : 'error', data.ok ? 'Logs eliminados!' : 'Erro', data.message);
        });

    })();
    </script>
</body>

</html>