<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Processador YouTube / UCY
// Arquivo: dashboard/artists/youtube/ucy_process.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];
$user     = getUserById($id_users);
if (!$user) { redirect('authentic/logout'); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard/artists/youtube/ucy');
}

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['ucy_flash'] = ['type'=>'error', 'msg'=>'Token inválido. Tenta novamente.'];
    redirect('dashboard/artists/youtube/ucy');
}

$action = $_POST['action'] ?? '';

function ucyFlash(string $type, string $msg): void {
    $_SESSION['ucy_flash'] = ['type' => $type, 'msg' => $msg];
    redirect('dashboard/artists/youtube/ucy');
}

switch ($action) {

// ── Conectar novo canal ───────────────────────
case 'connect_channel':
    $channel_id   = trim($_POST['channel_id']   ?? '');
    $channel_name = trim($_POST['channel_name'] ?? '');
    $channel_url  = trim($_POST['channel_url']  ?? '');
    $id_artist    = !empty($_POST['id_artist']) ? (int)$_POST['id_artist'] : null;

    if (empty($channel_id) || empty($channel_name)) {
        ucyFlash('error', 'O ID e o nome do canal são obrigatórios.');
    }

    // Sanitizar URL
    if (!empty($channel_url) && !str_starts_with($channel_url, 'http')) {
        $channel_url = 'https://' . $channel_url;
    }

    // Verificar se o canal já está registado nesta conta
    $check = $db->prepare("SELECT id_youtube FROM _youtube_channel WHERE id_users = ? AND channel_id = ?");
    $check->execute([$id_users, $channel_id]);
    if ($check->fetch()) {
        ucyFlash('error', 'Este canal já está registado na tua conta.');
    }

    // Verificar se o id_artist pertence ao utilizador
    if ($id_artist) {
        $art_check = $db->prepare("SELECT id_artist FROM _artist WHERE id_artist = ? AND id_users = ?");
        $art_check->execute([$id_artist, $id_users]);
        if (!$art_check->fetch()) { $id_artist = null; }
    }

    // Gerar código de verificação único (6 chars alfanumérico)
    $verified_code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

    $db->prepare("
        INSERT INTO _youtube_channel (id_users, id_artist, channel_id, channel_name, channel_url, verified_code, status_youtube)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ")->execute([$id_users, $id_artist, $channel_id, $channel_name, $channel_url ?: null, $verified_code]);

    logActivity($id_users, 'youtube', "Canal YouTube registado: $channel_name", 'youtube_channel', (int)$db->lastInsertId());
    ucyFlash('success', "Canal <strong>$channel_name</strong> submetido para verificação. Código: <code>$verified_code</code> — adiciona-o à descrição do canal.");

// ── Remover canal ─────────────────────────────
case 'remove_channel':
    $id_youtube = (int)($_POST['id_youtube'] ?? 0);
    if (!$id_youtube) { ucyFlash('error', 'Canal inválido.'); }

    // Confirmar que pertence ao utilizador
    $own = $db->prepare("SELECT channel_name FROM _youtube_channel WHERE id_youtube = ? AND id_users = ?");
    $own->execute([$id_youtube, $id_users]);
    $ch = $own->fetch(PDO::FETCH_ASSOC);
    if (!$ch) { ucyFlash('error', 'Canal não encontrado.'); }

    $db->prepare("DELETE FROM _youtube_channel WHERE id_youtube = ? AND id_users = ?")
       ->execute([$id_youtube, $id_users]);

    logActivity($id_users, 'youtube', "Canal YouTube removido: " . $ch['channel_name'], 'youtube_channel', $id_youtube);
    ucyFlash('success', 'Canal removido com sucesso.');

default:
    ucyFlash('error', 'Acção desconhecida.');
}