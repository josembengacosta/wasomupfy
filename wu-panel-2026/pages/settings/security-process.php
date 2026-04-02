<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Segurança Avançada
// Arquivo: admin/pages/settings/security-process.php
// Rota: admin/settings/security-process (POST only)
// Só super_admin
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';

if ($admin_role !== 'super_admin') {
    adminRedirect('/' . ADMIN_PATH . '');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/' . ADMIN_PATH . '/settings/security');
}

if (!validateAdminCsrf($_POST['csrf_token'] ?? '')) {
    adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'error']);
}
$_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

$action = $_POST['action'] ?? '';

// ════════════════════════════════════════════
// Helper — Gerar secção admin do .htaccess
// NÃO sobrescreve o ficheiro inteiro.
// Usa marcadores para identificar a secção admin
// e substitui APENAS esse bloco, preservando
// todas as outras regras do site.
// ════════════════════════════════════════════
function generate_htaccess(string $admin_path): string
{
    $base = rtrim(parse_url(APP_URL, PHP_URL_PATH), '/');
    $date = date('Y-m-d H:i:s');

    // Secção admin entre marcadores — TUDO entre eles é substituído
    return "
# ##WASOM_ADMIN_START## — NÃO remover esta linha
# Secção do painel admin — gerada automaticamente
# Última actualização: {$date} | Caminho: {$admin_path}

# ── Auth ──
RewriteRule ^{$admin_path}/login/?$                         {$admin_path}/auth/login.php [L]
RewriteRule ^{$admin_path}/login-process/?$                 {$admin_path}/auth/login-process.php [L]
RewriteRule ^{$admin_path}/logout/?$                        {$admin_path}/auth/logout.php [L]
RewriteRule ^{$admin_path}/forgot-password/?$               {$admin_path}/auth/forgot-password.php [L]
RewriteRule ^{$admin_path}/forgot-password-process/?$       {$admin_path}/auth/forgot-password-process.php [L]
RewriteRule ^{$admin_path}/reset-password/?$                {$admin_path}/auth/reset-password.php [L,QSA]
RewriteRule ^{$admin_path}/reset-password-process/?$        {$admin_path}/auth/reset-password-process.php [L,QSA]
RewriteRule ^{$admin_path}/confirm-code/?$                  {$admin_path}/auth/confirm-email-code.php [L,QSA]
RewriteRule ^{$admin_path}/lockscreen/?$                    {$admin_path}/auth/lockscreen.php [L]
RewriteRule ^{$admin_path}/lockscreen-process/?$            {$admin_path}/auth/lockscreen-process.php [L]
RewriteRule ^{$admin_path}/invite/accept/?$                 {$admin_path}/auth/invite-accept.php [L,QSA]

# ── Home ──
RewriteRule ^{$admin_path}/?$                               {$admin_path}/home.php [L]

# ── Perfil ──
RewriteRule ^{$admin_path}/profile/?$                       {$admin_path}/pages/user/profile.php [L]
RewriteRule ^{$admin_path}/profile-process/?$               {$admin_path}/pages/user/profile-process.php [L]

# ── Analytics ──
RewriteRule ^{$admin_path}/analytics/?$                     {$admin_path}/pages/analytics/home.php [L]
RewriteRule ^{$admin_path}/analytics/artists/?$             {$admin_path}/pages/analytics/artists.php [L]
RewriteRule ^{$admin_path}/analytics/stores/?$              {$admin_path}/pages/analytics/stores.php [L]
RewriteRule ^{$admin_path}/analytics/online-users/?$        {$admin_path}/pages/analytics/online-users.php [L]
RewriteRule ^{$admin_path}/analytics/reports/?$             {$admin_path}/pages/analytics/reports.php [L]
RewriteRule ^{$admin_path}/analytics/visitors/?$            {$admin_path}/pages/analytics/visites.php [L]

# ── Funcionários ──
RewriteRule ^{$admin_path}/employees/?$                     {$admin_path}/pages/employees/all-employees.php [L]
RewriteRule ^{$admin_path}/employees/add/?$                 {$admin_path}/pages/employees/add.php [L]
RewriteRule ^{$admin_path}/employees/add-process/?$         {$admin_path}/pages/employees/add-process.php [L]
RewriteRule ^{$admin_path}/employees/edit/?$                {$admin_path}/pages/employees/edit.php [L,QSA]
RewriteRule ^{$admin_path}/employees/delete/?$              {$admin_path}/pages/employees/delete.php [L,QSA]
RewriteRule ^{$admin_path}/employees/view/?$                {$admin_path}/pages/employees/view.php [L,QSA]

# ── Utilizadores ──
RewriteRule ^{$admin_path}/users/?$                         {$admin_path}/pages/users/all-users.php [L]
RewriteRule ^{$admin_path}/users/add/?$                     {$admin_path}/pages/users/add.php [L]
RewriteRule ^{$admin_path}/users/edit/?$                    {$admin_path}/pages/users/edit.php [L,QSA]
RewriteRule ^{$admin_path}/users/delete/?$                  {$admin_path}/pages/users/delete.php [L,QSA]
RewriteRule ^{$admin_path}/users/available-account/?$       {$admin_path}/pages/users/available-account.php [L]
RewriteRule ^{$admin_path}/users/unavailable-account/?$     {$admin_path}/pages/users/unavailable-account.php [L]

# ── Artistas ──
RewriteRule ^{$admin_path}/accounts-users/?$                {$admin_path}/pages/artist/accounts-users.php [L]
RewriteRule ^{$admin_path}/collaborators/?$                 {$admin_path}/pages/artist/collaborators-artist.php [L]

# ── Músicas ──
RewriteRule ^{$admin_path}/music/revise/?$                  {$admin_path}/pages/distribution/releases.php?filter=pending [L,QSA]
RewriteRule ^{$admin_path}/music/approve/?$                 {$admin_path}/pages/distribution/releases.php?filter=pending [L,QSA]
RewriteRule ^{$admin_path}/music/reject/?$                  {$admin_path}/pages/distribution/releases.php?filter=rejected [L,QSA]

# ── Distribuição ──
RewriteRule ^{$admin_path}/releases/?$                      {$admin_path}/pages/distribution/releases.php [L]
RewriteRule ^{$admin_path}/monetization/?$                  {$admin_path}/pages/distribution/monetization.php [L]

# ── Manager ──
RewriteRule ^{$admin_path}/manager/gestion/?$               {$admin_path}/pages/manager/gestion.php [L]
RewriteRule ^{$admin_path}/manager/activity/?$              {$admin_path}/pages/manager/activity.php [L]
RewriteRule ^{$admin_path}/manager/timeline/?$              {$admin_path}/pages/manager/timeline.php [L]
RewriteRule ^{$admin_path}/manager/top-music/?$             {$admin_path}/pages/manager/top-music.php [L]

# ── Finanças ──
RewriteRule ^{$admin_path}/finances/?$                      {$admin_path}/pages/finances/earnings.php [L]
RewriteRule ^{$admin_path}/finances/payments/?$             {$admin_path}/pages/finances/payments.php [L]

# ── Integração ──
RewriteRule ^{$admin_path}/integration/verify/?$            {$admin_path}/pages/integration/verify.php [L]
RewriteRule ^{$admin_path}/integration/verify-channel/?$    {$admin_path}/pages/integration/verify-channel.php [L]

# ── Mensagens ──
RewriteRule ^{$admin_path}/messages/inbox/?$                {$admin_path}/pages/messages/inbox.php [L]
RewriteRule ^{$admin_path}/messages/compose/?$              {$admin_path}/pages/messages/compose.php [L]

# ── Ajuda ──
RewriteRule ^{$admin_path}/help/contact/?$                  {$admin_path}/pages/help/contact.php [L]

# ── Configurações + Segurança ──
RewriteRule ^{$admin_path}/settings/?$                      {$admin_path}/pages/settings/index.php [L]
RewriteRule ^{$admin_path}/settings/security/?$             {$admin_path}/pages/settings/security.php [L]
RewriteRule ^{$admin_path}/settings/security-process/?$     {$admin_path}/pages/settings/security-process.php [L]

# ##WASOM_ADMIN_END## — NÃO remover esta linha
";
}

/**
 * Aplica a secção admin no .htaccess existente.
 * Se os marcadores já existem → substitui apenas essa secção.
 * Se não existem → acrescenta no final do ficheiro.
 * O resto do .htaccess (rotas do site, dashboard, etc.) é preservado intacto.
 */
function apply_htaccess_section(string $htaccess_path, string $admin_section): bool
{
    $marker_start = '# ##WASOM_ADMIN_START##';
    $marker_end   = '# ##WASOM_ADMIN_END##';

    if (!file_exists($htaccess_path)) {
        // Ficheiro não existe — criar com a secção admin
        return file_put_contents($htaccess_path, $admin_section) !== false;
    }

    $existing = file_get_contents($htaccess_path);
    if ($existing === false) return false;

    if (str_contains($existing, $marker_start) && str_contains($existing, $marker_end)) {
        // Substituir APENAS a secção entre marcadores
        $pattern = '/' . preg_quote($marker_start, '/') . '.*?' . preg_quote($marker_end, '/') . '[^\n]*/s';
        $new_content = preg_replace($pattern, trim($admin_section), $existing);
    } else {
        // Marcadores não existem — acrescentar no final, separado por linha em branco
        $new_content = rtrim($existing) . "

" . trim($admin_section) . "
";
    }

    // Backup de segurança antes de escrever
    file_put_contents($htaccess_path . '.bak', $existing);

    return file_put_contents($htaccess_path, $new_content) !== false;
}
// ════════════════════════════════════════════
// Helper — Actualizar config.php
// ════════════════════════════════════════════
function update_admin_path_in_config(string $new_path): bool
{
    $config_path = dirname(__DIR__, 3) . '/authentic/include/config.php';
    if (!file_exists($config_path) || !is_writable($config_path)) return false;

    $content = file_get_contents($config_path);
    // Substituir a linha define('ADMIN_PATH', ...)
    $new_content = preg_replace(
        "/define\('ADMIN_PATH',\s*'[^']*'\);/",
        "define('ADMIN_PATH', '" . addslashes($new_path) . "');",
        $content
    );

    if ($new_content === $content) return false; // Nada mudou
    return file_put_contents($config_path, $new_content) !== false;
}

switch ($action) {

    // ══════════════════════════════════════════
    // RODAR CAMINHO DO PAINEL
    // ══════════════════════════════════════════
    case 'change_path':
        $new_path = trim($_POST['new_path'] ?? '');

        // Validar formato
        if (!preg_match('/^[a-z0-9\-]{3,40}$/', $new_path)) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'path_invalid']);
        }

        $current_path = ADMIN_PATH;

        if ($new_path === $current_path) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security');
        }

        // Caminhos físicos no servidor
        $base_dir    = dirname(__DIR__, 3); // raiz do projecto (ex: htdocs/wasomupfy)
        $current_dir = $base_dir . '/' . $current_path;
        $new_dir     = $base_dir . '/' . $new_path;

        // Verificar se o novo caminho já existe
        if (file_exists($new_dir)) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'path_exists']);
        }

        // Verificar se temos permissão de escrita
        if (!is_writable($base_dir)) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'no_write']);
        }

        // 1. Actualizar APENAS a secção admin no .htaccess (preserva o resto do site)
        $htaccess_path   = $base_dir . '/.htaccess';
        $admin_section   = generate_htaccess($new_path);
        if (!apply_htaccess_section($htaccess_path, $admin_section)) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'no_write']);
        }

        // 2. Actualizar config.php
        update_admin_path_in_config($new_path);

        // 3. Actualizar BD
        $db->prepare("
            UPDATE _admin_config SET config_value=?, updated_by=?, updated_at=NOW()
            WHERE config_key='admin_path'
        ")->execute([$new_path, $admin_id]);

        $db->prepare("
            UPDATE _admin_config SET config_value=?, updated_by=?, updated_at=NOW()
            WHERE config_key='path_prev'
        ")->execute([$current_path, $admin_id]);

        $db->prepare("
            UPDATE _admin_config SET config_value=NOW(), updated_by=?, updated_at=NOW()
            WHERE config_key='path_last_changed'
        ")->execute([$admin_id]);

        // 4. Tentar renomear a pasta (pode falhar se não tiver permissão)
        $rename_ok = false;
        if (is_dir($current_dir)) {
            $rename_ok = rename($current_dir, $new_dir);
        }

        // Log de auditoria
        logAudit(
            $admin_id,
            null,
            'security.path_changed',
            'admin_config',
            null,
            ['path' => $current_path],
            ['path' => $new_path, 'rename_ok' => $rename_ok]
        );

        // Redirecionar para o novo URL
        $new_url = APP_URL . '/' . $new_path . '/settings/security?msg=path_changed';
        if (!$rename_ok) {
            $new_url .= '&warn=rename_failed';
        }
        header('Location: ' . $new_url);
        exit;

        // ══════════════════════════════════════════
        // REGENERAR .htaccess SEM MUDAR CAMINHO
        // ══════════════════════════════════════════
    case 'regen_htaccess':
        $base_dir     = dirname(__DIR__, 3);
        $htaccess_path = $base_dir . '/.htaccess';
        $current_path = ADMIN_PATH;

        if (!is_writable($htaccess_path) && !is_writable($base_dir)) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'no_write']);
        }

        $admin_section = generate_htaccess($current_path);
        if (apply_htaccess_section($htaccess_path, $admin_section)) {
            logAudit($admin_id, null, 'security.htaccess_regenerated', 'admin_config', null, null, null);
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'htaccess_ok']);
        } else {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'no_write']);
        }

        // ══════════════════════════════════════════
        // TOGGLE WHITELIST ON/OFF
        // ══════════════════════════════════════════
    case 'toggle_whitelist':
        $current = $db->query(
            "SELECT config_value FROM _admin_config WHERE config_key='ip_whitelist_on' LIMIT 1"
        )->fetchColumn();
        $new_val = ($current === '1') ? '0' : '1';

        // Segurança: não activar se não há IPs na lista
        if ($new_val === '1') {
            $count = (int)$db->query("SELECT COUNT(*) FROM _admin_ip_whitelist WHERE active=1")->fetchColumn();
            if ($count === 0) {
                // Adicionar o IP actual automaticamente
                $my_ip = $_SERVER['REMOTE_ADDR'] ?? '';
                if ($my_ip) {
                    $db->prepare("
                        INSERT IGNORE INTO _admin_ip_whitelist (ip_address, label, added_by)
                        VALUES (?, 'Auto-adicionado ao activar', ?)
                    ")->execute([$my_ip, $admin_id]);
                }
            }
        }

        $db->prepare("
            UPDATE _admin_config SET config_value=?, updated_by=?, updated_at=NOW()
            WHERE config_key='ip_whitelist_on'
        ")->execute([$new_val, $admin_id]);

        logAudit(
            $admin_id,
            null,
            'security.whitelist_toggled',
            'admin_config',
            null,
            ['wl_on' => $current],
            ['wl_on' => $new_val]
        );

        adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'wl_saved']);

        // ══════════════════════════════════════════
        // ADICIONAR IP
        // ══════════════════════════════════════════
    case 'add_ip':
        $ip_address = trim($_POST['ip_address'] ?? '');
        $label      = trim($_POST['label']      ?? '') ?: null;

        // Validar IP (IPv4 ou IPv6)
        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'error']);
        }

        $db->prepare("
            INSERT IGNORE INTO _admin_ip_whitelist (ip_address, label, added_by)
            VALUES (?, ?, ?)
        ")->execute([$ip_address, $label, $admin_id]);

        logAudit(
            $admin_id,
            null,
            'security.ip_added',
            'admin_ip_whitelist',
            null,
            null,
            ['ip' => $ip_address, 'label' => $label]
        );

        adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'ip_added']);

        // ══════════════════════════════════════════
        // REMOVER IP
        // ══════════════════════════════════════════
    case 'remove_ip':
        $ip_id = (int)($_POST['ip_id'] ?? 0);
        if (!$ip_id) adminRedirect('/' . ADMIN_PATH . '/settings/security');

        // Não remover o próprio IP se a whitelist está activa
        $wl_on = $db->query(
            "SELECT config_value FROM _admin_config WHERE config_key='ip_whitelist_on' LIMIT 1"
        )->fetchColumn();

        if ($wl_on === '1') {
            $my_ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $this_ip = $db->prepare("SELECT ip_address FROM _admin_ip_whitelist WHERE id_ip=? LIMIT 1");
            $this_ip->execute([$ip_id]);
            $ip_row = $this_ip->fetchColumn();
            if ($ip_row === $my_ip) {
                // Não pode remover o próprio IP enquanto a whitelist está activa
                adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'error']);
            }
        }

        $db->prepare("DELETE FROM _admin_ip_whitelist WHERE id_ip=?")->execute([$ip_id]);

        logAudit($admin_id, null, 'security.ip_removed', 'admin_ip_whitelist', $ip_id, null, null);

        adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'ip_removed']);

        // ══════════════════════════════════════════
        // TOGGLE IP ACTIVO/INACTIVO
        // ══════════════════════════════════════════
    case 'toggle_ip':
        $ip_id = (int)($_POST['ip_id'] ?? 0);
        if (!$ip_id) adminRedirect('/' . ADMIN_PATH . '/settings/security');

        $db->prepare("
            UPDATE _admin_ip_whitelist SET active = 1 - active WHERE id_ip=?
        ")->execute([$ip_id]);

        adminRedirect('/' . ADMIN_PATH . '/settings/security', ['msg' => 'ip_toggled']);

    default:
        adminRedirect('/' . ADMIN_PATH . '/settings/security');
}