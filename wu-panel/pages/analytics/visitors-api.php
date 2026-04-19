<?php
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.view');

header('Content-Type: application/json; charset=utf-8');
if (ob_get_level()) {
    ob_clean();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método inválido.']);
    exit;
}

if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Sessão expirada.']);
    exit;
}

$visitor_id = (int)($_POST['visitor_id'] ?? 0);
if ($visitor_id <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'ID inválido.']);
    exit;
}

$exists = $db->prepare("SELECT 1 FROM _visitor WHERE id_visitor = ? LIMIT 1");
$exists->execute([$visitor_id]);
if (!$exists->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Visitante não encontrado.']);
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
