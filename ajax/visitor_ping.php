<?php
require_once __DIR__ . '/../include/site.php';

header('Content-Type: application/json; charset=utf-8');
if (ob_get_level()) {
    ob_clean();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método inválido.']);
    exit;
}

if (!validateSiteCsrf($_POST['csrf_token'] ?? '')) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Sessão expirada.']);
    exit;
}

$status = ($_POST['status'] ?? '') === 'offline' ? 'offline' : 'online';
$page = substr(trim($_POST['page'] ?? '/'), 0, 500);
$title = substr(trim($_POST['title'] ?? ''), 0, 255);
$time_on_page = isset($_POST['time_on_page']) ? (int)$_POST['time_on_page'] : null;
$time_on_page = $time_on_page !== null ? max(0, min(86400, $time_on_page)) : null;

$visitor_id = updateVisitorPresence($page, $title !== '' ? $title : null, false, $time_on_page, $status);

echo json_encode([
    'ok' => true,
    'visitor_id' => $visitor_id,
    'status' => $status,
    'ts' => time(),
]);
exit;
