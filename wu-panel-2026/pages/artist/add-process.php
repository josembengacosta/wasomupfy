<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Adição de Artista
// Arquivo: wu-panel-2026/pages/artist/add-process.php
// Rota:    wu-panel-2026/artist/add-process (POST only)
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'artists.edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/' . ADMIN_PATH . '/artist');
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'])) {
    adminRedirect('/' . ADMIN_PATH . '/artist');
}

$action = trim($_POST['action'] ?? '');
if ($action !== 'add_artist') adminRedirect('/' . ADMIN_PATH . '/artist');

// ──────────────────────────────────────────────────────────────────────────────
// Campos obrigatórios
// ──────────────────────────────────────────────────────────────────────────────
$id_users   = (int)($_POST['id_users'] ?? 0);
$stage_name = trim($_POST['stage_name'] ?? '');
if ($id_users <= 0 || empty($stage_name)) {
    adminRedirect('/' . ADMIN_PATH . '/artist/add?msg=error');
}

// Campos opcionais
$real_name       = trim($_POST['real_name'] ?? '');
$genre_main      = trim($_POST['genre_main'] ?? '');
$genre_secondary = trim($_POST['genre_secondary'] ?? '');
$bio             = trim($_POST['bio'] ?? '');
$country         = trim($_POST['country'] ?? '');
$city            = trim($_POST['city'] ?? '');
$facebook_url    = trim($_POST['facebook_url'] ?? '');
$instagram_url   = trim($_POST['instagram_url'] ?? '');
$youtube_url     = trim($_POST['youtube_url'] ?? '');
$spotify_url     = trim($_POST['spotify_url'] ?? '');
$apple_music_url = trim($_POST['apple_music_url'] ?? '');
$tiktok_url      = trim($_POST['tiktok_url'] ?? '');
$website_url     = trim($_POST['website_url'] ?? '');
$status_artist   = trim($_POST['status_artist'] ?? 'active');

if (!in_array($status_artist, ['active', 'inactive', 'blocked', 'processing'], true)) {
    $status_artist = 'active';
}

// Verificar unicidade de stage_name para o mesmo dono
$dup = $db->prepare("SELECT id_artist FROM _artist WHERE stage_name = ? AND id_users = ?");
$dup->execute([$stage_name, $id_users]);
if ($dup->fetchColumn()) {
    adminRedirect('/' . ADMIN_PATH . '/artist/add?msg=dupe_stage');
}

// ──────────────────────────────────────────────────────────────────────────────
// Processar upload da foto
// ──────────────────────────────────────────────────────────────────────────────
$photo_filename = null;

if (isset($_FILES['photo_artist']) && $_FILES['photo_artist']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['photo_artist'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5 MB

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed_mimes)) {
        adminRedirect('/' . ADMIN_PATH . '/artist/add?msg=invalid_image');
    }
    if ($file['size'] > $max_size) {
        adminRedirect('/' . ADMIN_PATH . '/artist/add?msg=image_too_large');
    }

    // Extensão baseada no MIME
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'bin',
    };
}

// ──────────────────────────────────────────────────────────────────────────────
// Inserir na BD (primeiro sem a foto)
// ──────────────────────────────────────────────────────────────────────────────
try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO _artist (
            id_users, stage_name, real_name, genre_main, genre_secondary,
            bio, country, city,
            facebook_url, instagram_url, youtube_url, spotify_url,
            apple_music_url, tiktok_url, website_url,
            photo_artist, status_artist
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?
        )
    ");
    $stmt->execute([
        $id_users,
        $stage_name,
        $real_name ?: null,
        $genre_main ?: null,
        $genre_secondary ?: null,
        $bio ?: null,
        $country ?: null,
        $city ?: null,
        $facebook_url ?: null,
        $instagram_url ?: null,
        $youtube_url ?: null,
        $spotify_url ?: null,
        $apple_music_url ?: null,
        $tiktok_url ?: null,
        $website_url ?: null,
        null, // photo_artist (será atualizado após obter o ID)
        $status_artist
    ]);

    $id_artist = (int)$db->lastInsertId();

    // Se houver foto, gerar nome e mover
    if (isset($ext)) {
        $random = bin2hex(random_bytes(5)); // 10 caracteres hex
        $timestamp = time();
        $filename = "artist_{$id_artist}_{$timestamp}_{$random}.{$ext}";
        $upload_dir = dirname(__DIR__, 3) . '/assets/comprovantes/uploads/artists/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0750, true);
        }
        $dest = $upload_dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $photo_filename = $filename;
            // Atualizar o registo com o nome do ficheiro
            $db->prepare("UPDATE _artist SET photo_artist = ? WHERE id_artist = ?")
                ->execute([$photo_filename, $id_artist]);
        } else {
            throw new Exception('Falha ao mover ficheiro');
        }
    }

    // Registar actividade
    $db->prepare("
        INSERT INTO _user_activity_log
            (id_users, activity_type, description, entity, entity_id, ip_address)
        VALUES (?, 'artist_created', 'Artista criado pelo administrador', 'artist', ?, ?)
    ")->execute([$id_users, $id_artist, $_SERVER['REMOTE_ADDR'] ?? null]);

    $db->commit();

    // Registrar na auditoria
    logAudit($admin_id, $id_users, 'artist.created', '_artist', $id_artist);

    adminRedirect('/' . ADMIN_PATH . '/artist/view?id=' . $id_artist . '&msg=created');
} catch (Exception $e) {
    $db->rollBack();
    error_log('[ARTIST ADD] ' . $e->getMessage());
    adminRedirect('/' . ADMIN_PATH . '/artist/add?msg=error');
}