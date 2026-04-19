<?php
// ═══════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Endpoint JSON para dados de relatório
// Arquivo: admin/pages/analytics/get_report_data.php
// Rota:    admin/analytics/get_report_data  (POST only)
// ═══════════════════════════════════════════════════════════════════════════
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.view');

function jsonError(string $msg, int $code = 400): never {
    http_response_code($code);
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Método não permitido', 405);
if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) jsonError('Sessão expirada', 403);

$user_id     = (int)($_POST['user_id']      ?? 0);
$date_from   = $_POST['date_from']  ?? date('Y-m-01');
$date_to     = $_POST['date_to']    ?? date('Y-m-d');
$inc_streams = (int)($_POST['inc_streams'] ?? 1);
$inc_revenue = (int)($_POST['inc_revenue'] ?? 1);
$inc_catalog = (int)($_POST['inc_catalog'] ?? 1);

if (!$user_id) jsonError('Utilizador não seleccionado', 400);

try {
    $platformCfg = $db->query("
        SELECT currency_default, usd_to_aoa_rate
        FROM _platform
        ORDER BY id_platform DESC
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC) ?: [];
    $usdToAoa = max(1, (float)($platformCfg['usd_to_aoa_rate'] ?? 900));

    // ── Utilizador ──
    $stmt = $db->prepare("
        SELECT id_users, first_name, second_name, email_user
        FROM _users WHERE id_users = ? LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (!$user) jsonError('Utilizador não encontrado', 404);

    $response = [
        'user' => [
            'id'    => $user['id_users'],
            'name'  => trim($user['first_name'] . ' ' . ($user['second_name'] ?? '')),
            'email' => $user['email_user'],
        ],
        'currency' => [
            'code'        => 'AOA',
            'symbol'      => 'Kz',
            'fx_usd_aoa'  => $usdToAoa,
            'platform'    => $platformCfg['currency_default'] ?? 'AOA',
        ],
    ];

    // ── Streams + Receita ──
    if ($inc_streams || $inc_revenue) {
        // Converter datas para filtrar por ano/mês
        $from_year  = (int)date('Y', strtotime($date_from));
        $from_month = (int)date('n', strtotime($date_from));
        $to_year    = (int)date('Y', strtotime($date_to));
        $to_month   = (int)date('n', strtotime($date_to));

        $stmt = $db->prepare("
            SELECT
                s.year_stream, s.month_stream,
                s.streams, s.downloads,
                s.revenue,
                ROUND(s.revenue * ?, 2) AS revenue_aoa,
                t.title_track,
                COALESCE(ar.stage_name, CONCAT(u.first_name,' ',COALESCE(u.second_name,''))) AS stage_name,
                st.name_store
            FROM _stream s
            JOIN _track t   ON t.id_track  = s.id_track
            JOIN _album al  ON al.id_album  = t.id_album
            JOIN _users u   ON u.id_users   = al.id_users
            LEFT JOIN _artist ar ON ar.id_artist = al.id_artist
            JOIN _store st  ON st.id_store  = s.id_store
            WHERE al.id_users = ?
              AND (s.year_stream * 12 + s.month_stream) >= (? * 12 + ?)
              AND (s.year_stream * 12 + s.month_stream) <= (? * 12 + ?)
            ORDER BY s.year_stream DESC, s.month_stream DESC, t.title_track
        ");
        $stmt->execute([$usdToAoa, $user_id, $from_year, $from_month, $to_year, $to_month]);
        $response['streams'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Totais
        $total_streams  = array_sum(array_column($response['streams'], 'streams'));
        $total_downloads= array_sum(array_column($response['streams'], 'downloads'));
        $total_revenue  = array_sum(array_column($response['streams'], 'revenue'));
        $total_revenue_aoa = array_sum(array_column($response['streams'], 'revenue_aoa'));

        $response['totals'] = [
            'streams'      => $total_streams,
            'downloads'    => $total_downloads,
            'revenue'      => round($total_revenue, 4),
            'revenue_aoa'  => round($total_revenue_aoa, 2),
        ];
    } else {
        $response['streams'] = [];
        $response['totals']  = ['streams'=>0,'downloads'=>0,'revenue'=>0];
    }

    // ── Royalties ──
    if ($inc_revenue) {
        $stmt = $db->prepare("
            SELECT
                r.year_royalty, r.month_royalty,
                r.gross_revenue,
                ROUND(r.gross_revenue * COALESCE(NULLIF(r.exchange_rate, 0), ?), 2) AS gross_revenue_aoa,
                r.net_royalty,
                COALESCE(r.net_royalty_aoa, ROUND(r.net_royalty * COALESCE(NULLIF(r.exchange_rate, 0), ?), 2)) AS net_royalty_aoa,
                r.status_royalty, r.currency,
                COALESCE(r.exchange_rate, ?) AS exchange_rate,
                t.title_track,
                COALESCE(ar.stage_name, CONCAT(u.first_name,' ',COALESCE(u.second_name,''))) AS stage_name
            FROM _royalty r
            JOIN _track t   ON t.id_track   = r.id_track
            JOIN _users u   ON u.id_users   = r.id_users
            LEFT JOIN _album al  ON al.id_album = t.id_album
            LEFT JOIN _artist ar ON ar.id_artist = al.id_artist
            WHERE r.id_users = ?
            ORDER BY r.year_royalty DESC, r.month_royalty DESC
        ");
        $stmt->execute([$usdToAoa, $usdToAoa, $usdToAoa, $user_id]);
        $response['royalties'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $response['royalties'] = [];
    }

    // ── Catálogo ──
    if ($inc_catalog) {
        $stmt = $db->prepare("
            SELECT
                al.title_album, al.type_album, al.release_date,
                al.status_album, al.upc,
                t.title_track, t.track_number, t.isrc, t.duration_seconds,
                COALESCE(ar.stage_name, CONCAT(u.first_name,' ',COALESCE(u.second_name,''))) AS stage_name
            FROM _album al
            JOIN _users u ON u.id_users = al.id_users
            LEFT JOIN _artist ar ON ar.id_artist = al.id_artist
            LEFT JOIN _track t   ON t.id_album   = al.id_album
            WHERE al.id_users = ?
            ORDER BY al.release_date DESC, t.track_number ASC
        ");
        $stmt->execute([$user_id]);
        $response['catalog'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $response['catalog'] = [];
    }

    // ── Plano activo ──
    $stmt = $db->prepare("
        SELECT
            p.id_plan,
            p.slug_plan,
            p.name_plan,
            p.type_plan,
            p.description_plan,
            p.price_plan,
            p.price_usd,
            p.price_annual,
            p.annual_qty,
            p.validity_days,
            p.max_artists,
            p.max_releases,
            p.max_tracks_per_release,
            p.royalty_rate,
            p.badge_text,
            up.started_at,
            up.expires_at,
            up.status_plan,
            up.releases_used,
            up.releases_limit,
            up.auto_renew
        FROM _user_plan up
        JOIN _plans p ON p.id_plan = up.id_plan
        WHERE up.id_users = ?
        ORDER BY
            CASE up.status_plan
                WHEN 'active' THEN 0
                WHEN 'pending_payment' THEN 1
                ELSE 2
            END,
            COALESCE(up.started_at, up.creat_user_plan) DESC,
            up.id_user_plan DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($plan) {
        $feat = $db->prepare("
            SELECT feature_text, is_included
            FROM _plan_features
            WHERE id_plan = ?
            ORDER BY display_order ASC, id_feature ASC
        ");
        $feat->execute([(int)$plan['id_plan']]);
        $plan['features'] = $feat->fetchAll(PDO::FETCH_ASSOC);
    }
    $response['plan'] = $plan;

    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);

} catch (Throwable $e) {
    error_log('[GET_REPORT_DATA] ' . $e->getMessage());
    jsonError('Erro interno do servidor', 500);
}