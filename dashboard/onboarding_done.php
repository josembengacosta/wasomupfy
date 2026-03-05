<?php
// dashboard/onboarding_done.php — chamado via fetch pelo JS do painel
require_once __DIR__ . '/../authentic/include/functions.php';
startSecureSession();
requireLogin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!validateCsrf($data['csrf'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

markOnboardingDone((int)$_SESSION['id_users']);
$_SESSION['onboarding_done'] = true;
echo json_encode(['ok' => true]);