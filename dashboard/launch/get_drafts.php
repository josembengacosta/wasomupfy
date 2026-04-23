<?php
// dashboard/launch/get_drafts.php
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
requireLogin();

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    jsonOut(false, 'CSRF inválido');
}

$id_users = (int)$_SESSION['id_users'];
$db = getDB();

$stmt = $db->prepare("
    SELECT 
        a.id_album,
        a.title_album,
        a.type_album,
        a.name_author_band,
        a.img_cover,  -- ← CAMPO DA CAPA ADICIONADO
        a.creat_album,
        a.modif_album,
        COUNT(t.id_track) as track_count
    FROM _album a
    LEFT JOIN _track t ON t.id_album = a.id_album
    WHERE a.id_users = ? AND a.status_album = 'draft'
    GROUP BY a.id_album
    ORDER BY a.modif_album DESC
");
$stmt->execute([$id_users]);
$drafts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Construir URL da capa
foreach ($drafts as &$draft) {
    if ($draft['img_cover']) {
        $draft['cover_url'] = 'https://wasomupfy.rf.gd/assets/comprovantes/uploads/covers/' . $draft['img_cover'];
    } else {
        $draft['cover_url'] = null;
    }
}

header('Content-Type: application/json');
echo json_encode(['ok' => true, 'drafts' => $drafts]);
exit;