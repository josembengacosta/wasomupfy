<?php
require_once __DIR__ . '/../../authentic/include/functions.php';
require_once __DIR__ . '/../include/platform.php';

startSecureSession();
checkRememberMe();
requireLogin();
checkDashboardStatus();

$user = checkUserAccess((int)($_SESSION['id_users'] ?? 0));
$id_users = (int)($user['id_users'] ?? 0);
$id_history = (int)($_GET['id'] ?? 0);

if ($id_users <= 0 || $id_history <= 0) {
    http_response_code(400);
    exit('Relatorio invalido.');
}

$db = getDB();
$stmt = $db->prepare("
    SELECT id_history, name_report, format, file_path
    FROM _report_history
    WHERE id_history = ?
      AND id_users = ?
      AND status = 'success'
      AND save_dashboard = 1
    LIMIT 1
");
$stmt->execute([$id_history, $id_users]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report || empty($report['file_path'])) {
    http_response_code(404);
    exit('Relatorio nao encontrado.');
}

$project_root = dirname(__DIR__, 2);
$absolute_path = $project_root . $report['file_path'];
if (!is_file($absolute_path)) {
    http_response_code(404);
    exit('Ficheiro nao encontrado.');
}

$extension = strtolower(pathinfo($absolute_path, PATHINFO_EXTENSION));
$mime_type = match ($extension) {
    'pdf'  => 'application/pdf',
    'csv'  => 'text/csv; charset=utf-8',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    default => 'application/octet-stream',
};

$safe_name = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)($report['name_report'] ?? 'relatorio'));
$safe_name = trim((string)$safe_name, '_');
if ($safe_name === '') {
    $safe_name = 'relatorio_' . $id_history;
}
$download_name = $safe_name . '.' . ($extension ?: 'bin');

$db->prepare("
    UPDATE _report_history
    SET downloaded = 1,
        downloaded_at = NOW()
    WHERE id_history = ?
")->execute([$id_history]);

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . (string)filesize($absolute_path));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

readfile($absolute_path);
exit;