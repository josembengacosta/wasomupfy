<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — API Pageviews de Visitante
// Arquivo: wu-panel-2026/pages/analytics/visitors-api.php
// Rota:    wu-panel-2026/analytics/visitors-api
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.view');

header('Content-Type: application/json; charset=utf-8');
if (ob_get_level()) ob_clean();

$visitor_id = (int)($_GET['visitor_id'] ?? 0);
if (!$visitor_id) {
    echo json_encode(['ok' => false, 'message' => 'ID inválido.']);
    exit;
}

$stmt = $db->prepare("
    SELECT id_pageview, page_url, page_title, time_on_page, creat_pageview
    FROM _visitor_pageview
    WHERE id_visitor = ?
    ORDER BY creat_pageview DESC
    LIMIT 100
");
$stmt->execute([$visitor_id]);
$rows = $stmt->fetchAll();

echo json_encode(['ok' => true, 'pageviews' => $rows]);
exit;