<?php
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'music.edit');

function jOut(bool $ok, string $msg): never {
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => $ok, 'message' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jOut(false, 'Método não permitido.');
if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) jOut(false, 'Sessão expirada.');

$id = (int)($_POST['id_youtube'] ?? 0);
$channel_name = trim($_POST['channel_name'] ?? '');
$channel_id = trim($_POST['channel_id'] ?? '');
$channel_url = trim($_POST['channel_url'] ?? '');
$verified_code = trim($_POST['verified_code'] ?? '');
$status = trim($_POST['status_youtube'] ?? '');

if (!$id || !$channel_name || !$channel_id) jOut(false, 'Nome e ID do canal são obrigatórios.');
if (!in_array($status, ['pending','verified','rejected','removed'])) jOut(false, 'Estado inválido.');

$db = getDB();
// Buscar dados antigos para auditoria
$stmt = $db->prepare("SELECT * FROM _youtube_channel WHERE id_youtube = ?");
$stmt->execute([$id]);
$old = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$old) jOut(false, 'Canal não encontrado.');

try {
    $db->prepare("UPDATE _youtube_channel SET channel_name=?, channel_id=?, channel_url=?, verified_code=?, status_youtube=? WHERE id_youtube=?")
       ->execute([$channel_name, $channel_id, $channel_url, $verified_code, $status, $id]);
    // Notificar utilizador apenas se o estado mudou
    if ($old['status_youtube'] !== $status) {
        $msg = "O estado do teu canal YouTube foi alterado para " . ucfirst($status) . ".";
        $db->prepare("INSERT INTO _notification (id_users, id_employees, type, title, body) VALUES (?,?,'info','Estado do canal alterado',?)")
           ->execute([$old['id_users'], $admin_id, $msg]);
    }
    logAudit($admin_id, $old['id_users'], 'youtube.edited', '_youtube_channel', $id, $old, [
        'channel_name' => $channel_name,
        'channel_id' => $channel_id,
        'channel_url' => $channel_url,
        'verified_code' => $verified_code,
        'status_youtube' => $status
    ]);
    jOut(true, 'Canal actualizado com sucesso.');
} catch (Exception $e) {
    jOut(false, 'Erro ao actualizar: ' . $e->getMessage());
}