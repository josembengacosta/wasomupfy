<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Detalhes de álbum (AJAX)
// Arquivo: dashboard/collab/releases_ajax.php
// ══════════════════════════════════════════════
ob_start(); // captura warnings/notices antes do header

require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();

ob_end_clean(); // limpa qualquer output acidental
header('Content-Type: application/json; charset=utf-8');

function jsonErr(string $msg): void {
    echo json_encode(['ok' => false, 'message' => $msg]);
    exit;
}

// Verificar sessão
if (empty($_SESSION['collab_id']) || empty($_SESSION['collab_id_users'])) {
    jsonErr('Sessão expirada. Faz login novamente.');
}

$db        = getDB();
$id_collab = (int)$_SESSION['collab_id'];
$id_users  = (int)$_SESSION['collab_id_users'];
$role      = $_SESSION['collab_role'] ?? 'support';

// Verificar colaborador activo
$cs = $db->prepare("SELECT id_collab FROM _collaborators WHERE id_collab = ? AND id_users = ? AND status_collab = 'active' LIMIT 1");
$cs->execute([$id_collab, $id_users]);
if (!$cs->fetch()) {
    jsonErr('Acesso negado. A tua conta de colaborador não está activa.');
}

// Verificar permissão
if (!in_array($role, ['admin', 'editor', 'support'])) {
    jsonErr('A tua função não tem permissão para ver lançamentos.');
}

// Validar ID
$id_album = (int)($_GET['id'] ?? 0);
if ($id_album <= 0) { jsonErr('ID de álbum inválido.'); }

// Buscar álbum (deve pertencer ao proprietário correcto)
$as = $db->prepare("
    SELECT a.*, ar.stage_name
    FROM _album a
    LEFT JOIN _artist ar ON ar.id_artist = a.id_artist
    WHERE a.id_album = ? AND a.id_users = ?
    LIMIT 1
");
$as->execute([$id_album, $id_users]);
$album = $as->fetch(PDO::FETCH_ASSOC);
if (!$album) { jsonErr('Lançamento não encontrado ou sem permissão de acesso.'); }

// Buscar faixas
$ts = $db->prepare("
    SELECT track_number, title_track, name_author, name_author_feat,
           name_composer, name_producer, explicit, duration_seconds, isrc, status_track
    FROM _track WHERE id_album = ? ORDER BY track_number ASC
");
$ts->execute([$id_album]);
$tracks = $ts->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['ok' => true, 'album' => $album, 'tracks' => $tracks], JSON_UNESCAPED_UNICODE);