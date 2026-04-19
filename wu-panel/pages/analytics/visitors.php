<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Visitantes do Site
// Arquivo: wu-panel/pages/analytics/visites.php
// Rota:    wu-panel/analytics/visitors
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// ══════════════════════════════════════════════
// HANDLER AJAX — INLINE (POST com ajax_action)
// ══════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    if (ob_get_level()) ob_clean();

    // CSRF
    if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        echo json_encode(['ok' => false, 'message' => 'Sessão expirada.']);
        exit;
    }
    requirePermission($admin_id, 'analytics.view');

    $action     = trim($_POST['ajax_action']);
    $visitor_id = (int)($_POST['visitor_id'] ?? 0);

    // ── Bloquear visitante ──
    if ($action === 'block' && $visitor_id) {
        $block_type   = in_array($_POST['block_type']   ?? '', ['temporary', 'permanent', 'custom']) ? $_POST['block_type'] : 'temporary';
        $block_reason = in_array($_POST['block_reason'] ?? '', ['spam', 'security', 'bot', 'multiple_failures', 'suspicious', 'other']) ? $_POST['block_reason'] : 'suspicious';
        $block_notes  = substr(trim($_POST['block_notes'] ?? ''), 0, 500);
        $block_until  = null;
        if ($block_type === 'temporary') {
            $hours = max(1, min(8760, (int)($_POST['block_hours'] ?? 24)));
            $block_until = date('Y-m-d H:i:s', strtotime('+' . $hours . ' hours'));
        }
        try {
            $db->prepare("
                UPDATE _visitor SET
                    status_visitor = 'blocked',
                    block_type     = ?,
                    block_reason   = ?,
                    block_notes    = ?,
                    block_until    = ?,
                    blocked_by     = ?,
                    blocked_at     = NOW()
                WHERE id_visitor = ?
            ")->execute([$block_type, $block_reason, $block_notes ?: null, $block_until, $admin_id, $visitor_id]);
            logAudit($admin_id, null, 'visitor.blocked', '_visitor', $visitor_id);
            echo json_encode(['ok' => true, 'message' => 'Visitante bloqueado com sucesso.']);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => 'Erro ao bloquear.']);
        }
        exit;
    }

    // ── Desbloquear visitante ──
    if ($action === 'unblock' && $visitor_id) {
        try {
            $db->prepare("
                UPDATE _visitor SET
                    status_visitor = 'active',
                    block_type     = NULL,
                    block_reason   = NULL,
                    block_notes    = NULL,
                    block_until    = NULL,
                    blocked_by     = NULL,
                    blocked_at     = NULL
                WHERE id_visitor = ?
            ")->execute([$visitor_id]);
            logAudit($admin_id, null, 'visitor.unblocked', '_visitor', $visitor_id);
            echo json_encode(['ok' => true, 'message' => 'Visitante desbloqueado.']);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => 'Erro ao desbloquear.']);
        }
        exit;
    }

    // ── Marcar como suspeito ──
    if ($action === 'mark_suspicious' && $visitor_id) {
        try {
            $db->prepare("UPDATE _visitor SET status_visitor='suspicious' WHERE id_visitor=?")
                ->execute([$visitor_id]);
            logAudit($admin_id, null, 'visitor.marked_suspicious', '_visitor', $visitor_id);
            echo json_encode(['ok' => true, 'message' => 'Visitante marcado como suspeito.']);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => 'Erro ao marcar.']);
        }
        exit;
    }

    // ── Eliminar visitante ──
    if ($action === 'delete' && $visitor_id) {
        requirePermission($admin_id, 'analytics.view');
        $pw = $_POST['admin_password'] ?? '';
        $adm = $db->prepare("SELECT password_employees FROM _employees WHERE id_employees=?");
        $adm->execute([$admin_id]);
        $adm_row = $adm->fetch();
        if (!$adm_row || !password_verify($pw, $adm_row['password_employees'])) {
            echo json_encode(['ok' => false, 'message' => 'Senha incorrecta.']);
            exit;
        }
        try {
            $db->prepare("DELETE FROM _visitor WHERE id_visitor=?")->execute([$visitor_id]);
            logAudit($admin_id, null, 'visitor.deleted', '_visitor', $visitor_id);
            echo json_encode(['ok' => true, 'message' => 'Visitante eliminado.']);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => 'Erro ao eliminar.']);
        }
        exit;
    }

    // ── Limpeza em massa ──
    if ($action === 'bulk_delete') {
        $pw     = $_POST['admin_password'] ?? '';
        $option = $_POST['bulk_option'] ?? '';
        $adm = $db->prepare("SELECT password_employees FROM _employees WHERE id_employees=?");
        $adm->execute([$admin_id]);
        $adm_row = $adm->fetch();
        if (!$adm_row || !password_verify($pw, $adm_row['password_employees'])) {
            echo json_encode(['ok' => false, 'message' => 'Senha incorrecta.']);
            exit;
        }
        try {
            $sql = match ($option) {
                'all'       => "DELETE FROM _visitor WHERE 1",
                'older_30'  => "DELETE FROM _visitor WHERE last_seen < DATE_SUB(NOW(), INTERVAL 30 DAY)",
                'older_90'  => "DELETE FROM _visitor WHERE last_seen < DATE_SUB(NOW(), INTERVAL 90 DAY)",
                'blocked'   => "DELETE FROM _visitor WHERE status_visitor='blocked'",
                'bots'      => "DELETE FROM _visitor WHERE is_bot=1",
                default     => null,
            };
            if (!$sql) {
                echo json_encode(['ok' => false, 'message' => 'Opção inválida.']);
                exit;
            }
            $count = $db->exec($sql);
            logAudit($admin_id, null, 'visitor.bulk_delete', '_visitor', null, json_encode(['option' => $option, 'count' => $count]));
            echo json_encode(['ok' => true, 'message' => "$count registo(s) eliminado(s).", 'count' => $count]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => 'Erro ao limpar.']);
        }
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Acção desconhecida.']);
    exit;
}

// ══════════════════════════════════════════════
// EXPORT CSV — SERVER-SIDE
// ══════════════════════════════════════════════
if (($_GET['export'] ?? '') === 'csv') {
    requirePermission($admin_id, 'analytics.view');

    // Filtros idênticos aos da listagem (exemplo)
    $where = [];
    $params = [];
    if (!empty($_GET['ip'])) {
        $where[] = 'v.ip_address LIKE ?';
        $params[] = '%' . $_GET['ip'] . '%';
    }
    if (!empty($_GET['country'])) {
        $where[] = 'v.country_code = ?';
        $params[] = strtoupper($_GET['country']);
    }
    if (!empty($_GET['status'])) {
        $where[] = 'v.status_visitor = ?';
        $params[] = $_GET['status'];
    }
    if (!empty($_GET['device'])) {
        $where[] = 'v.device_type = ?';
        $params[] = $_GET['device'];
    }
    if (!empty($_GET['browser'])) {
        $where[] = 'v.browser = ?';
        $params[] = $_GET['browser'];
    }
    if (isset($_GET['bot']) && $_GET['bot'] !== '') {
        $where[] = 'v.is_bot = ?';
        $params[] = (int)$_GET['bot'];
    }
    if (!empty($_GET['date_from'])) {
        $where[] = 'DATE(v.creat_visitor) >= ?';
        $params[] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[] = 'DATE(v.creat_visitor) <= ?';
        $params[] = $_GET['date_to'];
    }

    $sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $db->prepare("
        SELECT v.id_visitor, v.ip_address, v.ip_version, v.country_code, v.country_name,
               v.city, v.region, v.isp, v.browser, v.browser_version, v.os, v.os_version,
               v.device_type, v.device_brand, v.screen_resolution, v.is_bot, v.bot_name,
               v.page_entry, v.page_exit, v.pages_viewed, v.session_duration,
               v.referrer, v.utm_source, v.utm_medium, v.utm_campaign,
               v.is_online, v.last_seen, v.visit_count,
               v.status_visitor, v.block_type, v.block_reason,
               v.creat_visitor, v.modif_visitor
        FROM _visitor v $sql_where
        ORDER BY v.last_seen DESC
        LIMIT 50000
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $csvExcelEncode = static function (string $value): string {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($value, 'UTF-16LE', 'UTF-8');
        }

        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'UTF-16LE//IGNORE', $value);
            if ($converted !== false) {
                return $converted;
            }
        }

        return $value;
    };
    $csvExcelLine = static function (array $fields) use ($csvExcelEncode): string {
        $escaped = array_map(static function ($value): string {
            $value = (string)($value ?? '');
            $value = str_replace('"', '""', $value);
            return '"' . $value . '"';
        }, $fields);

        return $csvExcelEncode(implode(';', $escaped) . "\r\n");
    };

    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: text/csv; charset=UTF-16LE');
    header('Content-Disposition: attachment; filename="visitantes_' . date('Y-m-d') . '.csv"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xFF\xFE");
    fwrite($out, $csvExcelEncode("sep=;\r\n"));
    fwrite($out, $csvExcelLine([
        'ID',
        'IP',
        'Vers' . "\u{00E3}" . 'o IP',
        'Pa' . "\u{00ED}" . 's (c' . "\u{00F3}" . 'digo)',
        'Pa' . "\u{00ED}" . 's',
        'Cidade',
        'Regi' . "\u{00E3}" . 'o',
        'ISP',
        'Browser',
        'Vers' . "\u{00E3}" . 'o Browser',
        'SO',
        'Vers' . "\u{00E3}" . 'o SO',
        'Dispositivo',
        'Marca',
        'Resolu' . "\u{00E7}" . "\u{00E3}" . 'o',
        "\u{00C9}" . ' Bot',
        'Nome Bot',
        'P' . "\u{00E1}" . 'gina Entrada',
        'P' . "\u{00E1}" . 'gina Sa' . "\u{00ED}" . 'da',
        'P' . "\u{00E1}" . 'ginas Vistas',
        'Dura' . "\u{00E7}" . "\u{00E3}" . 'o Sess' . "\u{00E3}" . 'o (s)',
        'Referrer',
        'UTM Source',
        'UTM Medium',
        'UTM Campaign',
        'Online Agora',
        "\u{00DA}" . 'ltima Vista',
        'Total Visitas',
        'Estado',
        'Tipo Bloqueio',
        'Raz' . "\u{00E3}" . 'o Bloqueio',
        'Criado em',
        'Modificado em',
    ]));

    foreach ($rows as $r) {
        fwrite($out, $csvExcelLine([
            $r['id_visitor'],
            $r['ip_address'],
            $r['ip_version'],
            $r['country_code'],
            $r['country_name'],
            $r['city'],
            $r['region'],
            $r['isp'],
            $r['browser'],
            $r['browser_version'],
            $r['os'],
            $r['os_version'],
            $r['device_type'],
            $r['device_brand'],
            $r['screen_resolution'],
            $r['is_bot'] ? 'Sim' : 'Nao',
            $r['bot_name'],
            $r['page_entry'],
            $r['page_exit'],
            $r['pages_viewed'],
            $r['session_duration'],
            $r['referrer'],
            $r['utm_source'],
            $r['utm_medium'],
            $r['utm_campaign'],
            $r['is_online'] ? 'Sim' : 'Nao',
            $r['last_seen'],
            $r['visit_count'],
            $r['status_visitor'],
            $r['block_type'],
            $r['block_reason'],
            $r['creat_visitor'],
            $r['modif_visitor'],
        ]));
    }
    fclose($out);

    // Configuração dos cabeçalhos
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="visitantes_' . date('Y-m-d') . '.csv"');

    // Abre o output como stream
    $out = fopen('php://output', 'w');
    // BOM UTF-8 para Excel reconhecer acentos
    fputs($out, "\xEF\xBB\xBF");

    // Cabeçalhos – use o delimitador que preferir (; para Excel PT)
    $headers = [
        'ID',
        'IP',
        'Versão IP',
        'País (código)',
        'País',
        'Cidade',
        'Região',
        'ISP',
        'Browser',
        'Versão Browser',
        'SO',
        'Versão SO',
        'Dispositivo',
        'Marca',
        'Resolução',
        'É Bot',
        'Nome Bot',
        'Página Entrada',
        'Página Saída',
        'Páginas Vistas',
        'Duração Sessão (s)',
        'Referrer',
        'UTM Source',
        'UTM Medium',
        'UTM Campaign',
        'Online Agora',
        'Última Vista',
        'Total Visitas',
        'Estado',
        'Tipo Bloqueio',
        'Razão Bloqueio',
        'Criado em',
        'Modificado em'
    ];
    fputcsv($out, $headers, ';', '"', '\\');

    // Dados
    foreach ($rows as $r) {
        $row = [
            $r['id_visitor'],
            $r['ip_address'],
            $r['ip_version'],
            $r['country_code'],
            $r['country_name'],
            $r['city'],
            $r['region'],
            $r['isp'],
            $r['browser'],
            $r['browser_version'],
            $r['os'],
            $r['os_version'],
            $r['device_type'],
            $r['device_brand'],
            $r['screen_resolution'],
            $r['is_bot'] ? 'Sim' : 'Não',
            $r['bot_name'],
            $r['page_entry'],
            $r['page_exit'],
            $r['pages_viewed'],
            $r['session_duration'],
            $r['referrer'],
            $r['utm_source'],
            $r['utm_medium'],
            $r['utm_campaign'],
            $r['is_online'] ? 'Sim' : 'Não',
            $r['last_seen'],
            $r['visit_count'],
            $r['status_visitor'],
            $r['block_type'],
            $r['block_reason'],
            $r['creat_visitor'],
            $r['modif_visitor'],
        ];
        fputcsv($out, $row, ';', '"', '\\');
    }
    fclose($out);
    exit;
}

// ══════════════════════════════════════════════
// STATS GLOBAIS
// ══════════════════════════════════════════════
$stats = $db->query("
    SELECT
        COUNT(*)                                                      AS total,
        SUM(is_online = 1 AND last_seen >= DATE_SUB(NOW(),INTERVAL 5 MINUTE)) AS online_now,
        SUM(status_visitor = 'blocked')                               AS blocked,
        SUM(status_visitor = 'suspicious')                            AS suspicious,
        SUM(is_bot = 1)                                               AS bots,
        COUNT(DISTINCT country_code)                                  AS countries,
        SUM(DATE(creat_visitor) = CURDATE())                          AS today_new,
        SUM(DATE(last_seen) = CURDATE())                              AS today_active
    FROM _visitor
")->fetch();

// Top 5 países
$top_countries = $db->query("
    SELECT country_code, country_name, COUNT(*) AS cnt
    FROM _visitor
    WHERE country_code IS NOT NULL
    GROUP BY country_code, country_name
    ORDER BY cnt DESC LIMIT 5
")->fetchAll();

// Distribuição de devices
$device_dist = $db->query("
    SELECT device_type, COUNT(*) AS cnt
    FROM _visitor
    GROUP BY device_type
    ORDER BY cnt DESC
")->fetchAll();

// Actividade últimos 7 dias (para sparkline)
$activity_7d = $db->query("
    SELECT DATE(creat_visitor) AS day, COUNT(*) AS cnt
    FROM _visitor
    WHERE creat_visitor >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(creat_visitor)
    ORDER BY day
")->fetchAll(PDO::FETCH_KEY_PAIR);
$sparkline = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $sparkline[] = (int)($activity_7d[$day] ?? 0);
}

// ══════════════════════════════════════════════
// FILTROS + PAGINAÇÃO
// ══════════════════════════════════════════════
$per_page  = 20;
$page      = max(1, (int)($_GET['page']    ?? 1));
$f_ip      = trim($_GET['ip']      ?? '');
$f_country = trim($_GET['country'] ?? '');
$f_status  = trim($_GET['status']  ?? '');
$f_device  = trim($_GET['device']  ?? '');
$f_browser = trim($_GET['browser'] ?? '');
$f_bot     = $_GET['bot'] ?? '';
$f_from    = trim($_GET['date_from'] ?? '');
$f_to      = trim($_GET['date_to']   ?? '');
$sort_col  = in_array($_GET['sort'] ?? '', ['id_visitor', 'ip_address', 'country_name', 'last_seen', 'visit_count', 'pages_viewed', 'status_visitor'])
    ? $_GET['sort'] : 'last_seen';
$sort_dir  = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$where = [];
$params = [];
if ($f_ip) {
    $where[] = 'v.ip_address LIKE ?';
    $params[] = '%' . $f_ip . '%';
}
if ($f_country) {
    $where[] = 'v.country_code = ?';
    $params[] = strtoupper($f_country);
}
if ($f_status) {
    $where[] = 'v.status_visitor = ?';
    $params[] = $f_status;
}
if ($f_device) {
    $where[] = 'v.device_type = ?';
    $params[] = $f_device;
}
if ($f_browser) {
    $where[] = 'v.browser = ?';
    $params[] = $f_browser;
}
if ($f_bot !== '') {
    $where[] = 'v.is_bot = ?';
    $params[] = (int)$f_bot;
}
if ($f_from) {
    $where[] = 'DATE(v.creat_visitor) >= ?';
    $params[] = $f_from;
}
if ($f_to) {
    $where[] = 'DATE(v.creat_visitor) <= ?';
    $params[] = $f_to;
}
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cnt_stmt = $db->prepare("SELECT COUNT(*) FROM _visitor v $sql_where");
$cnt_stmt->execute($params);
$total_filtered = (int)$cnt_stmt->fetchColumn();
$total_pages    = max(1, (int)ceil($total_filtered / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;

$stmt = $db->prepare("
    SELECT v.id_visitor, v.ip_address, v.ip_version,
           v.country_code, v.country_name, v.city, v.region, v.isp,
           v.browser, v.browser_version, v.os, v.os_version,
           v.device_type, v.device_brand, v.is_bot, v.bot_name,
           v.page_entry, v.page_exit, v.pages_viewed, v.session_duration,
           v.referrer, v.is_online, v.last_seen, v.visit_count,
           v.status_visitor, v.block_type, v.block_reason, v.block_until,
           v.block_notes, v.creat_visitor,
           (SELECT COUNT(*) FROM _visitor_pageview vp WHERE vp.id_visitor=v.id_visitor) AS total_pageviews
    FROM _visitor v
    $sql_where
    ORDER BY v.$sort_col $sort_dir
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$visitors = $stmt->fetchAll();

// Filtros disponíveis (para os selects)
$avail_browsers = $db->query("SELECT DISTINCT browser FROM _visitor WHERE browser IS NOT NULL ORDER BY browser")->fetchAll(PDO::FETCH_COLUMN);
$avail_devices  = $db->query("SELECT DISTINCT device_type FROM _visitor WHERE device_type IS NOT NULL ORDER BY device_type")->fetchAll(PDO::FETCH_COLUMN);

// ── Helpers ──
function vis_status_badge(string $s, ?string $bt = null): string
{
    return match ($s) {
        'active'     => '<span class="badge vis-s-active">Activo</span>',
        'blocked'    => '<span class="badge vis-s-blocked">Bloqueado' . ($bt ? ' (' . ucfirst($bt) . ')' : '') . '</span>',
        'suspicious' => '<span class="badge vis-s-suspicious">Suspeito</span>',
        default      => '<span class="badge bg-secondary">' . htmlspecialchars($s) . '</span>',
    };
}
function vis_device_icon(string $d): string
{
    return match (strtolower($d)) {
        'desktop' => 'bi-pc-display',
        'mobile' => 'bi-phone',
        'tablet' => 'bi-tablet',
        'bot'     => 'bi-robot',
        default  => 'bi-question-circle',
    };
}
function vis_browser_icon(string $b): string
{
    return match (strtolower($b)) {
        'chrome' => 'bi-browser-chrome',
        'firefox' => 'bi-browser-firefox',
        'safari' => 'bi-browser-safari',
        'edge' => 'bi-browser-edge',
        default  => 'bi-globe2',
    };
}
function vis_fmt_dur(int $s): string
{
    if ($s < 60)   return $s . 's';
    if ($s < 3600) return floor($s / 60) . 'm';
    return floor($s / 3600) . 'h ' . floor(($s % 3600) / 60) . 'm';
}
function vis_time_ago(?string $dt): string
{
    if (!$dt) return '—';
    $diff = time() - strtotime($dt);
    if ($diff < 60)     return 'agora';
    if ($diff < 3600)   return floor($diff / 60) . ' min';
    if ($diff < 86400)  return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'd';
    return date('d/m/Y', strtotime($dt));
}
function vis_sort_url(string $col, string $cur, string $dir, array $get): string
{
    $d = ($col === $cur && $dir === 'ASC') ? 'desc' : 'asc';
    return '?' . http_build_query(array_merge($get, ['sort' => $col, 'dir' => $d, 'page' => 1]));
}
function vis_sort_icon(string $col, string $cur, string $dir): string
{
    if ($col !== $cur) return '';
    return $dir === 'ASC' ? ' ▲' : ' ▼';
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title>Visitantes — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        /* ── Status badges ── */
        .vis-s-active {
            background: rgba(34, 197, 94, .15);
            color: #166534;
        }

        .vis-s-blocked {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
        }

        .vis-s-suspicious {
            background: rgba(234, 179, 8, .15);
            color: #92400e;
        }

        .dark-mode .vis-s-active {
            background: rgba(34, 197, 94, .2);
            color: #4ade80;
        }

        .dark-mode .vis-s-blocked {
            background: rgba(239, 68, 68, .2);
            color: #f87171;
        }

        .dark-mode .vis-s-suspicious {
            background: rgba(234, 179, 8, .2);
            color: #facc15;
        }

        /* ── Stat cards ── */
        .vis-stat {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: transform .2s;
        }

        .vis-stat:hover {
            transform: translateY(-2px);
        }

        .vis-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .vis-stat-num {
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1;
        }

        .vis-stat-lbl {
            font-size: .72rem;
            opacity: .55;
            margin-top: 2px;
        }

        /* ── Filter card ── */
        .filter-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 18px;
        }

        .filter-card .form-label {
            font-size: .76rem;
            font-weight: 600;
            margin-bottom: 3px;
        }

        /* ── Table ── */
        #vis-table th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
        }

        #vis-table td {
            font-size: .8rem;
            vertical-align: middle;
        }

        #vis-table tbody tr:has(.dropdown.show) {
            background: var(--hover-bg, rgba(0, 0, 0, .03));
        }

        /* ── IP badge ── */
        .ip-badge {
            font-family: monospace;
            font-size: .76rem;
            background: var(--code-bg, rgba(0, 0, 0, .04));
            border: 1px solid var(--border-color, #e8e8f0);
            padding: 2px 7px;
            border-radius: 6px;
            cursor: pointer;
            transition: all .15s;
        }

        .ip-badge:hover {
            border-color: #FF0089;
            color: #FF0089;
        }

        .dark-mode .ip-badge {
            background: rgba(255, 255, 255, .06);
        }

        /* ── Online dot ── */
        .online-dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, .25);
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                box-shadow: 0 0 0 2px rgba(34, 197, 94, .25);
            }

            50% {
                box-shadow: 0 0 0 5px rgba(34, 197, 94, .05);
            }
        }

        /* ── Sparkline ── */
        .sparkline-wrap {
            height: 40px;
        }

        /* ── Top countries ── */
        .ctr-bar-wrap {
            background: var(--border-color, #e8e8f0);
            border-radius: 4px;
            height: 6px;
            overflow: hidden;
        }

        .ctr-bar {
            height: 100%;
            border-radius: 4px;
            background: #FF0089;
            transition: width .4s;
        }

        /* ── Pagination ── */
        .vis-pagination .page-link {
            border-radius: 8px !important;
            margin: 0 2px;
            font-size: .8rem;
        }

        /* ── Dropdown actions ── */
        .actions-dropdown .dropdown-menu {
            position: fixed !important;
            z-index: 9999;
            min-width: 170px;
        }

        #vis-table tbody tr:has(.dropdown.show)>td {
            position: relative;
            z-index: 1;
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

                <!-- Header -->
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1"><i class="bi bi-globe2 me-2"></i>Visitantes</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item active text-white-stable">Visitantes</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        <!-- Badge realtime -->
                        <span class="badge"
                            style="background:rgba(34,197,94,.15);color:#22c55e;padding:7px 12px;border-radius:10px;font-size:.77rem">
                            <span class="online-dot me-1"></span>
                            <?php echo number_format((int)$stats['online_now']); ?> online agora
                        </span>
                        <?php if (hasPermission($admin_id, 'analytics.view')): ?>
                            <!-- Export CSV -->
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>"
                                class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-download me-1"></i> CSV
                            </a>
                            <!-- Limpar dados -->
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="openBulkDelete()">
                                <i class="bi bi-trash3 me-1"></i> Limpar
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <?php
                    $sc = [
                        ['total',       'Total',          '#FF0089', 'bi-globe2'],
                        ['today_active', 'Activos Hoje',   '#22c55e', 'bi-calendar-check'],
                        ['today_new',   'Novos Hoje',     '#3b82f6', 'bi-person-plus'],
                        ['blocked',     'Bloqueados',     '#ef4444', 'bi-unlock'],
                        ['suspicious',  'Suspeitos',      '#eab308', 'bi-exclamation-triangle'],
                        ['bots',        'Bots',           '#8b5cf6', 'bi-robot'],
                        ['countries',   'Países',         '#06b6d4', 'bi-flag'],
                        ['online_now',  'Online Agora',   '#10b981', 'bi-wifi'],
                    ];
                    foreach ($sc as [$key, $lbl, $color, $icon]):
                    ?>
                        <div class="col-6 col-md-3 col-xl-<?php echo count($sc) <= 4 ? '3' : '3'; ?>">
                            <div class="vis-stat">
                                <div class="vis-stat-icon" style="background:<?php echo $color; ?>18">
                                    <i class="bi <?php echo $icon; ?>" style="color:<?php echo $color; ?>"></i>
                                </div>
                                <div>
                                    <div class="vis-stat-num counter" data-valor="<?php echo (int)$stats[$key]; ?>"
                                        data-tipo="contagem">0</div>
                                    <div class="vis-stat-lbl"><?php echo $lbl; ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Charts row -->
                <div class="row g-3 mb-4">
                    <!-- Actividade 7 dias -->
                    <div class="col-md-6">
                        <div class="card p-3" style="border-radius:14px">
                            <div
                                style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;opacity:.5;font-weight:700;margin-bottom:10px">
                                Actividade — Últimos 7 Dias
                            </div>
                            <canvas id="spark-chart" height="80"></canvas>
                        </div>
                    </div>
                    <!-- Top países -->
                    <div class="col-md-3">
                        <div class="card p-3" style="border-radius:14px;height:100%">
                            <div
                                style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;opacity:.5;font-weight:700;margin-bottom:10px">
                                Top Países
                            </div>
                            <?php
                            $max_c = $top_countries ? max(array_column($top_countries, 'cnt')) : 1;
                            foreach ($top_countries as $tc): ?>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1" style="font-size:.78rem">
                                        <span><?php echo htmlspecialchars($tc['country_name'] ?: $tc['country_code'] ?: '—'); ?></span>
                                        <span style="opacity:.5"><?php echo number_format($tc['cnt']); ?></span>
                                    </div>
                                    <div class="ctr-bar-wrap">
                                        <div class="ctr-bar" style="width:<?php echo round($tc['cnt'] / $max_c * 100); ?>%">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($top_countries)): ?>
                                <div style="opacity:.35;font-size:.8rem;text-align:center;padding:16px 0">Sem dados</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Dispositivos -->
                    <div class="col-md-3">
                        <div class="card p-3" style="border-radius:14px;height:100%">
                            <div
                                style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;opacity:.5;font-weight:700;margin-bottom:10px">
                                Dispositivos
                            </div>
                            <canvas id="device-chart" height="120"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="filter-card">
                    <form method="GET" id="filter-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">IP / Endereço</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="ip"
                                    value="<?php echo htmlspecialchars($f_ip); ?>" placeholder="192.168.1.1" />
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">País</label>
                                <input type="text" class="form-control form-control-sm filter-debounce" name="country"
                                    value="<?php echo htmlspecialchars($f_country); ?>" placeholder="AO"
                                    maxlength="2" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <select class="form-select form-select-sm filter-instant" name="status">
                                    <option value="">Todos</option>
                                    <?php foreach (['active' => 'Activo', 'blocked' => 'Bloqueado', 'suspicious' => 'Suspeito'] as $v => $l): ?>
                                        <option value="<?php echo $v; ?>" <?php echo $f_status === $v ? 'selected' : ''; ?>>
                                            <?php echo $l; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Dispositivo</label>
                                <select class="form-select form-select-sm filter-instant" name="device">
                                    <option value="">Todos</option>
                                    <?php foreach ($avail_devices as $d): ?>
                                        <option value="<?php echo $d; ?>" <?php echo $f_device === $d ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($d); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Browser</label>
                                <select class="form-select form-select-sm filter-instant" name="browser">
                                    <option value="">Todos</option>
                                    <?php foreach ($avail_browsers as $b): ?>
                                        <option value="<?php echo $b; ?>" <?php echo $f_browser === $b ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($b); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Bot</label>
                                <select class="form-select form-select-sm filter-instant" name="bot">
                                    <option value="">Todos</option>
                                    <option value="0" <?php echo $f_bot === '0' ? 'selected' : ''; ?>>Não</option>
                                    <option value="1" <?php echo $f_bot === '1' ? 'selected' : ''; ?>>Sim</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">De</label>
                                <input type="date" class="form-control form-control-sm" name="date_from"
                                    value="<?php echo htmlspecialchars($f_from); ?>" />
                            </div>
                            <div class="col-md-1 d-flex gap-1 align-items-end">
                                <input type="date" class="form-control form-control-sm" name="date_to"
                                    value="<?php echo htmlspecialchars($f_to); ?>" />
                            </div>
                            <div class="col-auto d-flex gap-1">
                                <button type="submit" class="btn btn-sm text-white"
                                    style="background:#FF0089;border-color:#FF0089">
                                    <i class="bi bi-search"></i>
                                </button>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/visitors"
                                    class="btn btn-sm btn-outline-secondary" title="Limpar">
                                    <i class="bi bi-x"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tabela -->
                <div class="card p-0" style="border-radius:14px;overflow:hidden">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2"
                        style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                        <span style="font-size:.82rem;font-weight:600">
                            <?php if ($total_filtered !== (int)$stats['total']): ?>
                                <span style="color:#FF0089"><?php echo number_format($total_filtered); ?></span> de
                                <?php echo number_format((int)$stats['total']); ?> visitantes
                            <?php else: ?>
                                <?php echo number_format($total_filtered); ?> visitantes
                            <?php endif; ?>
                        </span>
                        <span style="font-size:.74rem;opacity:.45">Pág.
                            <?php echo $page; ?>/<?php echo $total_pages; ?></span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="vis-table">
                            <thead>
                                <tr>
                                    <th style="width:50px"><a
                                            href="<?php echo vis_sort_url('id_visitor', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">ID<?php echo vis_sort_icon('id_visitor', $sort_col, $sort_dir); ?></a>
                                    </th>
                                    <th><a href="<?php echo vis_sort_url('ip_address', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">IP<?php echo vis_sort_icon('ip_address', $sort_col, $sort_dir); ?></a>
                                    </th>
                                    <th><a href="<?php echo vis_sort_url('country_name', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">Localização<?php echo vis_sort_icon('country_name', $sort_col, $sort_dir); ?></a>
                                    </th>
                                    <th>Browser / SO</th>
                                    <th>Dispositivo</th>
                                    <th><a href="<?php echo vis_sort_url('pages_viewed', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">Páginas<?php echo vis_sort_icon('pages_viewed', $sort_col, $sort_dir); ?></a>
                                    </th>
                                    <th><a href="<?php echo vis_sort_url('visit_count', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">Visitas<?php echo vis_sort_icon('visit_count', $sort_col, $sort_dir); ?></a>
                                    </th>
                                    <th><a href="<?php echo vis_sort_url('status_visitor', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">Estado<?php echo vis_sort_icon('status_visitor', $sort_col, $sort_dir); ?></a>
                                    </th>
                                    <th><a href="<?php echo vis_sort_url('last_seen', $sort_col, $sort_dir, $_GET); ?>"
                                            class="text-inherit text-decoration-none">Última
                                            Vista<?php echo vis_sort_icon('last_seen', $sort_col, $sort_dir); ?></a></th>
                                    <th style="width:60px;text-align:center">Acções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($visitors)): ?>
                                    <tr>
                                        <td colspan="10">
                                            <div class="text-center py-5" style="opacity:.35">
                                                <i class="bi bi-globe2"
                                                    style="font-size:2.5rem;display:block;margin-bottom:10px"></i>
                                                Nenhum visitante encontrado para os filtros aplicados.
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($visitors as $v):
                                        $is_online_now = $v['is_online'] && strtotime($v['last_seen']) >= strtotime('-5 minutes');
                                    ?>
                                        <tr>
                                            <td><span
                                                    style="font-family:monospace;font-size:.73rem;opacity:.5">#<?php echo $v['id_visitor']; ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if ($is_online_now): ?>
                                                        <span class="online-dot flex-shrink-0"></span>
                                                    <?php endif; ?>
                                                    <span class="ip-badge"
                                                        onclick="filterByIP('<?php echo htmlspecialchars($v['ip_address']); ?>')"
                                                        title="Clica para filtrar por este IP">
                                                        <?php echo htmlspecialchars($v['ip_address']); ?>
                                                    </span>
                                                    <?php if ($v['is_bot']): ?>
                                                        <span class="badge"
                                                            style="background:rgba(139,92,246,.15);color:#8b5cf6;font-size:.62rem">BOT</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($v['isp'])): ?>
                                                    <div style="font-size:.7rem;opacity:.4;margin-top:2px">
                                                        <?php echo htmlspecialchars($v['isp']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="font-size:.8rem;font-weight:600">
                                                    <?php if (!empty($v['country_code'])): ?>
                                                        <span
                                                            style="font-size:.85rem;margin-right:3px"><?php echo htmlspecialchars($v['country_code']); ?></span>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($v['country_name'] ?: '—'); ?>
                                                </div>
                                                <?php if (!empty($v['city'])): ?>
                                                    <div style="font-size:.71rem;opacity:.45">
                                                        <?php echo htmlspecialchars($v['city']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-1" style="font-size:.8rem">
                                                    <?php if (!empty($v['browser'])): ?>
                                                        <i class="bi <?php echo vis_browser_icon($v['browser']); ?>"
                                                            style="font-size:.9rem"></i>
                                                        <?php echo htmlspecialchars(ucfirst($v['browser'])); ?>
                                                        <?php if (!empty($v['browser_version'])): ?><span
                                                                style="opacity:.4;font-size:.7rem">
                                                                <?php echo htmlspecialchars($v['browser_version']); ?></span><?php endif; ?>
                                                    <?php else: ?><span style="opacity:.4">—</span><?php endif; ?>
                                                </div>
                                                <?php if (!empty($v['os'])): ?>
                                                    <div style="font-size:.71rem;opacity:.45">
                                                        <?php echo htmlspecialchars($v['os']); ?>
                                                        <?php echo htmlspecialchars($v['os_version'] ?? ''); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php $di = vis_device_icon($v['device_type'] ?? ''); ?>
                                                <i class="bi <?php echo $di; ?>" style="font-size:1rem;opacity:.7"
                                                    title="<?php echo htmlspecialchars(ucfirst($v['device_type'] ?? '—')); ?>"></i>
                                                <span
                                                    style="font-size:.78rem;margin-left:4px"><?php echo htmlspecialchars(ucfirst($v['device_type'] ?? '—')); ?></span>
                                                <?php if (!empty($v['device_brand'])): ?>
                                                    <div style="font-size:.7rem;opacity:.4">
                                                        <?php echo htmlspecialchars($v['device_brand']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align:center">
                                                <div style="font-weight:700;font-size:.9rem">
                                                    <?php echo (int)$v['total_pageviews']; ?></div>
                                                <?php if ($v['session_duration'] > 0): ?>
                                                    <div style="font-size:.7rem;opacity:.4">
                                                        <?php echo vis_fmt_dur((int)$v['session_duration']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align:center">
                                                <div style="font-weight:700;font-size:.9rem">
                                                    <?php echo (int)$v['visit_count']; ?></div>
                                            </td>
                                            <td><?php echo vis_status_badge($v['status_visitor'], $v['block_type']); ?></td>
                                            <td style="white-space:nowrap;font-size:.78rem">
                                                <?php echo vis_time_ago($v['last_seen']); ?>
                                                <div style="font-size:.7rem;opacity:.4">
                                                    <?php echo $v['last_seen'] ? date('d/m H:i', strtotime($v['last_seen'])) : ''; ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="actions-dropdown dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button"
                                                        data-bs-toggle="dropdown" data-bs-reference="toggle"
                                                        aria-expanded="false">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="#"
                                                                onclick="viewPageviews(<?php echo (int)$v['id_visitor']; ?>, '<?php echo htmlspecialchars($v['ip_address']); ?>');return false">
                                                                <i class="bi bi-list-ul text-info"></i> Ver Pageviews
                                                            </a>
                                                        </li>
                                                        <?php if (hasPermission($admin_id, 'analytics.view')): ?>
                                                            <?php if ($v['status_visitor'] !== 'blocked'): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="#"
                                                                        onclick="openBlock(<?php echo (int)$v['id_visitor']; ?>, '<?php echo htmlspecialchars($v['ip_address']); ?>');return false">
                                                                        <i class="bi bi-unlock text-warning"></i> Bloquear
                                                                    </a>
                                                                </li>
                                                            <?php else: ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="#"
                                                                        onclick="doAction('unblock',<?php echo (int)$v['id_visitor']; ?>,null,'<?php echo htmlspecialchars($v['ip_address']); ?>');return false">
                                                                        <i class="bi bi-unlock text-success"></i> Desbloquear
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            <?php if ($v['status_visitor'] !== 'suspicious' && !$v['is_bot']): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="#"
                                                                        onclick="doAction('mark_suspicious',<?php echo (int)$v['id_visitor']; ?>,null,'<?php echo htmlspecialchars($v['ip_address']); ?>');return false">
                                                                        <i class="bi bi-exclamation-triangle text-warning"></i> Marcar
                                                                        Suspeito
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            <li>
                                                                <hr class="dropdown-divider my-1">
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="#"
                                                                    onclick="openDelete(<?php echo (int)$v['id_visitor']; ?>, '<?php echo htmlspecialchars($v['ip_address']); ?>');return false">
                                                                    <i class="bi bi-trash text-danger"></i> Eliminar
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-center py-3">
                            <nav>
                                <ul class="pagination pagination-sm vis-pagination mb-0">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"><i
                                                class="bi bi-chevron-left"></i></a>
                                    </li>
                                    <?php
                                    $s = max(1, $page - 2);
                                    $e = min($total_pages, $page + 2);
                                    if ($s > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                                        if ($s > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                    }
                                    for ($i = $s; $i <= $e; $i++) {
                                        echo '<li class="page-item ' . ($i === $page ? 'active' : '') . '"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '">' . $i . '</a></li>';
                                    }
                                    if ($e < $total_pages) {
                                        if ($e < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                        echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '">' . $total_pages . '</a></li>';
                                    }
                                    ?>
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                            href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"><i
                                                class="bi bi-chevron-right"></i></a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div><!-- /card -->

            </div><!-- /container-fluid -->
        </div><!-- /content -->
    </div><!-- /wrapper -->

    <!-- ════ MODAIS ════ -->

    <!-- Modal: Bloquear -->
    <div class="modal fade" id="blockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-ban me-2"></i>Bloquear Visitante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">IP: <code id="block-ip"></code></p>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:.82rem">Tipo de Bloqueio</label>
                        <select class="form-select" id="block-type">
                            <option value="temporary">Temporário</option>
                            <option value="permanent">Permanente</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </div>
                    <div class="mb-3" id="block-hours-wrap">
                        <label class="form-label fw-600" style="font-size:.82rem">Duração (horas)</label>
                        <input type="number" class="form-control" id="block-hours" value="24" min="1" max="8760" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:.82rem">Razão</label>
                        <select class="form-select" id="block-reason">
                            <option value="suspicious">Actividade suspeita</option>
                            <option value="spam">Spam</option>
                            <option value="security">Segurança</option>
                            <option value="bot">Bot malicioso</option>
                            <option value="multiple_failures">Múltiplas falhas de autenticação</option>
                            <option value="other">Outra</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:.82rem">Notas (opcional)</label>
                        <textarea class="form-control" id="block-notes" rows="2"
                            placeholder="Observações internas..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning btn-sm" id="btn-confirm-block">
                        <i class="bi bi-ban me-1"></i> Confirmar Bloqueio
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Eliminar -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Eliminar Visitante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Vais eliminar permanentemente o registo do IP <code id="delete-ip"></code>. Esta acção não pode
                        ser desfeita.</p>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:.82rem">Senha de Administrador *</label>
                        <input type="password" class="form-control" id="delete-password" placeholder="Senha do admin" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger btn-sm" id="btn-confirm-delete">
                        <i class="bi bi-trash me-1"></i> Eliminar Definitivamente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Limpar em massa -->
    <div class="modal fade" id="bulkDeleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Limpeza de Dados</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger mb-3">
                        <strong>Atenção:</strong> Esta acção é irreversível e elimina registos permanentemente.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:.82rem">Opção de Limpeza *</label>
                        <select class="form-select" id="bulk-option">
                            <option value="">Selecciona...</option>
                            <option value="older_30">Registos com mais de 30 dias</option>
                            <option value="older_90">Registos com mais de 90 dias</option>
                            <option value="blocked">Apenas bloqueados</option>
                            <option value="bots">Apenas bots</option>
                            <option value="all">TUDO (todos os visitantes)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:.82rem">Escreve <strong>EXCLUIR TUDO</strong>
                            para confirmar *</label>
                        <input type="text" class="form-control" id="bulk-confirm-text" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:.82rem">Senha de Administrador *</label>
                        <input type="password" class="form-control" id="bulk-password" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger btn-sm" id="btn-confirm-bulk">
                        <i class="bi bi-trash3 me-1"></i> Confirmar Limpeza
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Pageviews -->
    <div class="modal fade" id="pageviewsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-list-ul me-2"></i>Pageviews — <span id="pv-ip"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="pv-body">
                    <div class="text-center py-4">
                        <div class="spinner-border" style="color:#FF0089"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="col-12 text-center py-2" style="font-size:.8rem">
                <p class="mb-0">© <?php echo date('Y'); ?> Wasom Upfy. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="" />
            <div class="loader-progress"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.min.js"></script>
    <script>
        (function() {
            'use strict';

            const BASE_URL = '<?php echo APP_URL; ?>';
            const ADMIN_PATH = '<?php echo ADMIN_PATH; ?>';
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const SELF_URL = BASE_URL + '/' + ADMIN_PATH + '/analytics/visitors';

            // ── Sparkline — Actividade 7 dias ──
            const sparkCtx = document.getElementById('spark-chart');
            if (sparkCtx) {
                const isDark = document.body.classList.contains('dark-mode');
                const gridClr = isDark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)';
                const textClr = isDark ? 'rgba(255,255,255,.4)' : 'rgba(0,0,0,.4)';
                const labels = [];
                for (let i = 6; i >= 0; i--) {
                    const d = new Date();
                    d.setDate(d.getDate() - i);
                    labels.push(d.toLocaleDateString('pt-AO', {
                        weekday: 'short'
                    }));
                }
                new Chart(sparkCtx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            data: <?php echo json_encode($sparkline); ?>,
                            borderColor: '#FF0089',
                            backgroundColor: 'rgba(255,0,137,.08)',
                            fill: true,
                            tension: .4,
                            pointRadius: 4,
                            pointBackgroundColor: '#FF0089',
                        }]
                    },
                    options: {
                        responsive: false,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: gridClr
                                },
                                ticks: {
                                    color: textClr,
                                    font: {
                                        size: 10
                                    }
                                }
                            },
                            y: {
                                grid: {
                                    color: gridClr
                                },
                                ticks: {
                                    color: textClr,
                                    font: {
                                        size: 10
                                    }
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // ── Donut — Dispositivos ──
            const devCtx = document.getElementById('device-chart');
            if (devCtx) {
                const devData = <?php echo json_encode(array_column($device_dist, 'cnt')); ?>;
                const devLabels =
                    <?php echo json_encode(array_map(fn($d) => ucfirst($d['device_type'] ?? '—'), $device_dist)); ?>;
                new Chart(devCtx, {
                    type: 'doughnut',
                    data: {
                        labels: devLabels,
                        datasets: [{
                            data: devData,
                            backgroundColor: ['#FF0089', '#3b82f6', '#22c55e', '#eab308', '#8b5cf6',
                                '#6b7280'
                            ],
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: false,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: {
                                        size: 10
                                    },
                                    boxWidth: 10
                                }
                            }
                        }
                    }
                });
            }

            // ── Filtros com debounce ──
            let dbt;
            document.querySelectorAll('.filter-debounce').forEach(el => {
                el.addEventListener('input', () => {
                    clearTimeout(dbt);
                    dbt = setTimeout(() => document.getElementById('filter-form').submit(), 600);
                });
            });
            document.querySelectorAll('.filter-instant').forEach(el => {
                el.addEventListener('change', () => document.getElementById('filter-form').submit());
            });

            // ── Filtrar por IP ao clicar ──
            window.filterByIP = function(ip) {
                const url = new URL(window.location.href);
                url.searchParams.set('ip', ip);
                url.searchParams.set('page', 1);
                window.location.href = url.toString();
            };

            // ── Helper AJAX ──
            async function postAction(payload) {
                const fd = new FormData();
                Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
                fd.append('csrf_token', CSRF);
                const r = await fetch(window.location.href, {
                    method: 'POST',
                    body: fd
                });
                return r.json();
            }

            function toast(type, msg) {
                const id = 't' + Date.now();
                const cls = type === 'success' ? 'bg-success' : type === 'warning' ? 'bg-warning text-dark' :
                    'bg-danger';
                let tc = document.querySelector('.toast-container.position-fixed');
                if (!tc) {
                    tc = document.createElement('div');
                    tc.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                    tc.style.zIndex = '9999';
                    document.body.appendChild(tc);
                }
                tc.insertAdjacentHTML('beforeend', `
            <div id="${id}" class="toast align-items-center ${cls} border-0">
                <div class="d-flex">
                    <div class="toast-body fw-600">${msg}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>`);
                const el = document.getElementById(id);
                new bootstrap.Toast(el, {
                    delay: 4000
                }).show();
                el.addEventListener('hidden.bs.toast', () => el.remove());
            }

            // ── Acções genéricas (unblock, mark_suspicious) ──
            window.doAction = async function(action, id, extra, ip) {
                const labels = {
                    unblock: 'desbloquear',
                    mark_suspicious: 'marcar como suspeito'
                };
                if (!confirm(`Tens a certeza que queres ${labels[action] || action} o visitante ${ip}?`))
                    return;
                const payload = {
                    ajax_action: action,
                    visitor_id: id
                };
                if (extra) Object.assign(payload, extra);
                try {
                    const d = await postAction(payload);
                    toast(d.ok ? 'success' : 'danger', d.message);
                    if (d.ok) setTimeout(() => location.reload(), 800);
                } catch {
                    toast('danger', 'Erro de ligação.');
                }
            };

            // ── Modal Bloquear ──
            let _blockId = null;
            window.openBlock = function(id, ip) {
                _blockId = id;
                document.getElementById('block-ip').textContent = ip;
                document.getElementById('block-hours').value = 24;
                document.getElementById('block-notes').value = '';
                new bootstrap.Modal(document.getElementById('blockModal')).show();
            };
            document.getElementById('block-type')?.addEventListener('change', function() {
                document.getElementById('block-hours-wrap').style.display = this.value === 'temporary' ? '' :
                    'none';
            });
            document.getElementById('btn-confirm-block')?.addEventListener('click', async function() {
                this.disabled = true;
                const payload = {
                    ajax_action: 'block',
                    visitor_id: _blockId,
                    block_type: document.getElementById('block-type').value,
                    block_reason: document.getElementById('block-reason').value,
                    block_notes: document.getElementById('block-notes').value,
                    block_hours: document.getElementById('block-hours').value,
                };
                try {
                    const d = await postAction(payload);
                    toast(d.ok ? 'warning' : 'danger', d.message);
                    if (d.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('blockModal')).hide();
                        setTimeout(() => location.reload(), 600);
                    }
                } catch {
                    toast('danger', 'Erro.');
                }
                this.disabled = false;
            });

            // ── Modal Eliminar ──
            let _deleteId = null;
            window.openDelete = function(id, ip) {
                _deleteId = id;
                document.getElementById('delete-ip').textContent = ip;
                document.getElementById('delete-password').value = '';
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            };
            document.getElementById('btn-confirm-delete')?.addEventListener('click', async function() {
                const pw = document.getElementById('delete-password').value;
                if (!pw) {
                    toast('danger', 'Senha obrigatória.');
                    return;
                }
                this.disabled = true;
                try {
                    const d = await postAction({
                        ajax_action: 'delete',
                        visitor_id: _deleteId,
                        admin_password: pw
                    });
                    toast(d.ok ? 'success' : 'danger', d.message);
                    if (d.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                        setTimeout(() => location.reload(), 600);
                    }
                } catch {
                    toast('danger', 'Erro.');
                }
                this.disabled = false;
            });

            // ── Modal Limpeza em massa ──
            window.openBulkDelete = function() {
                document.getElementById('bulk-confirm-text').value = '';
                document.getElementById('bulk-password').value = '';
                document.getElementById('bulk-option').value = '';
                new bootstrap.Modal(document.getElementById('bulkDeleteModal')).show();
            };
            document.getElementById('btn-confirm-bulk')?.addEventListener('click', async function() {
                const opt = document.getElementById('bulk-option').value;
                const conf = document.getElementById('bulk-confirm-text').value;
                const pw = document.getElementById('bulk-password').value;
                if (!opt) {
                    toast('danger', 'Selecciona uma opção.');
                    return;
                }
                if (conf !== 'EXCLUIR TUDO') {
                    toast('danger', 'Texto de confirmação incorrecto.');
                    return;
                }
                if (!pw) {
                    toast('danger', 'Senha obrigatória.');
                    return;
                }
                this.disabled = true;
                try {
                    const d = await postAction({
                        ajax_action: 'bulk_delete',
                        bulk_option: opt,
                        admin_password: pw
                    });
                    toast(d.ok ? 'success' : 'danger', d.message);
                    if (d.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('bulkDeleteModal')).hide();
                        setTimeout(() => location.reload(), 1000);
                    }
                } catch {
                    toast('danger', 'Erro.');
                }
                this.disabled = false;
            });

            // ── Modal Pageviews ──
            window.viewPageviews = async function(id, ip) {
                document.getElementById('pv-ip').textContent = ip;
                document.getElementById('pv-body').innerHTML =
                    '<div class="text-center py-4"><div class="spinner-border" style="color:#FF0089"></div></div>';
                new bootstrap.Modal(document.getElementById('pageviewsModal')).show();
                try {
                    const fd = new FormData();
                    fd.append('visitor_id', id);
                    fd.append('csrf_token', CSRF);
                    const r = await fetch(BASE_URL + '/' + ADMIN_PATH + '/analytics/visitors-api', {
                        method: 'POST',
                        body: fd,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const d = await r.json();
                    if (d.ok && d.pageviews.length > 0) {
                        let html =
                            '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>#</th><th>URL</th><th>Título</th><th>Tempo</th><th>Data</th></tr></thead><tbody>';
                        d.pageviews.forEach((pv, i) => {
                            html += `<tr>
                        <td style="opacity:.4;font-size:.75rem">${i+1}</td>
                        <td style="font-size:.8rem;font-family:monospace;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${pv.page_url}">${pv.page_url}</td>
                        <td style="font-size:.78rem">${pv.page_title||'—'}</td>
                        <td style="font-size:.78rem">${pv.time_on_page?pv.time_on_page+'s':'—'}</td>
                        <td style="font-size:.74rem;opacity:.5;white-space:nowrap">${pv.creat_pageview}</td>
                    </tr>`;
                        });
                        html += '</tbody></table></div>';
                        document.getElementById('pv-body').innerHTML = html;
                    } else {
                        document.getElementById('pv-body').innerHTML =
                            '<div class="text-center py-4" style="opacity:.4"><i class="bi bi-eye-slash fs-2 d-block mb-2"></i>Nenhum pageview registado.</div>';
                    }
                } catch {
                    document.getElementById('pv-body').innerHTML =
                        '<div class="text-center py-4 text-danger">Erro ao carregar pageviews.</div>';
                }
            };

        })();
    </script>
</body>

</html>