<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Edição de Artista
// Arquivo: wu-panel/pages/artist/edit-process.php
// Rota:    wu-panel/artist/edit-process (POST only)
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'users.edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/' . ADMIN_PATH . '/artist');
}

// CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'])) {
    adminRedirect('/' . ADMIN_PATH . '/artist');
}

$id = (int)($_POST['id_artist'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/artist');

function redirectBack(string $base, array $params = []): never
{
    $sep = str_contains($base, '?') ? '&' : '?';
    $qs = $params ? $sep . http_build_query($params) : '';
    header('Location: ' . APP_URL . $base . $qs);
    exit;
}

// Buscar artista atual
$stmt = $db->prepare("SELECT * FROM _artist WHERE id_artist = ?");
$stmt->execute([$id]);
$artist = $stmt->fetch();
if (!$artist) adminRedirect('/' . ADMIN_PATH . '/artist');

$back = '/' . ADMIN_PATH . '/artist/edit?id=' . $id;
$action = trim($_POST['action'] ?? '');

if ($action === 'update_profile') {
    $stage_name   = trim($_POST['stage_name'] ?? '');
    $real_name    = trim($_POST['real_name'] ?? '');
    $genre_main   = trim($_POST['genre_main'] ?? '');
    $genre_secondary = trim($_POST['genre_secondary'] ?? '');
    $bio          = trim($_POST['bio'] ?? '');
    $country      = trim($_POST['country'] ?? '');
    $city         = trim($_POST['city'] ?? '');
    $facebook_url = trim($_POST['facebook_url'] ?? '');
    $instagram_url = trim($_POST['instagram_url'] ?? '');
    $youtube_url  = trim($_POST['youtube_url'] ?? '');
    $spotify_url  = trim($_POST['spotify_url'] ?? '');
    $apple_music_url = trim($_POST['apple_music_url'] ?? '');
    $tiktok_url   = trim($_POST['tiktok_url'] ?? '');
    $website_url  = trim($_POST['website_url'] ?? '');
    $photo_artist = trim($_POST['photo_artist'] ?? '');
    $status_artist = trim($_POST['status_artist'] ?? 'active');

    if (empty($stage_name)) {
        redirectBack($back, ['msg' => 'error']);
    }
    if (!in_array($status_artist, ['active', 'inactive', 'blocked', 'processing'], true)) {
        $status_artist = 'active';
    }

    // Verificar unicidade de stage_name por dono
    $dup = $db->prepare("SELECT id_artist FROM _artist WHERE stage_name = ? AND id_users = ? AND id_artist != ?");
    $dup->execute([$stage_name, $artist['id_users'], $id]);
    if ($dup->fetchColumn()) {
        redirectBack($back, ['msg' => 'dupe_stage']);
    }

    $old_val = json_encode([
        'stage_name'   => $artist['stage_name'],
        'real_name'    => $artist['real_name'],
        'genre_main'   => $artist['genre_main'],
        'status_artist' => $artist['status_artist'],
    ]);

    try {
        $db->beginTransaction();

        $artist_email = trim($_POST['artist_email'] ?? '');
$default_role = trim($_POST['default_role'] ?? '');

$db->prepare("
    UPDATE _artist SET
        stage_name   = ?,
        real_name    = ?,
        artist_email = ?,
        default_role = ?,
        genre_main   = ?,
        genre_secondary = ?,
        bio          = ?,
        country      = ?,
        city         = ?,
        facebook_url = ?,
        instagram_url= ?,
        youtube_url  = ?,
        spotify_url  = ?,
        apple_music_url = ?,
        tiktok_url   = ?,
        website_url  = ?,
        photo_artist = ?,
        status_artist= ?
    WHERE id_artist = ?
")->execute([
    $stage_name,
    $real_name ?: null,
    $artist_email ?: null,
    $default_role ?: null,
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
    $photo_artist ?: null,
    $status_artist,
    $id
]);

        // Registar activity
        $db->prepare("
            INSERT INTO _user_activity_log (id_users, activity_type, description, entity, entity_id, ip_address)
            VALUES (?, 'artist_updated', 'Perfil do artista actualizado pelo administrador', 'artist', ?, ?)
        ")->execute([$artist['id_users'], $id, $_SERVER['REMOTE_ADDR'] ?? null]);

        $db->commit();

        $new_val = json_encode([
            'stage_name'   => $stage_name,
            'real_name'    => $real_name,
            'genre_main'   => $genre_main,
            'status_artist' => $status_artist,
        ]);
        logAudit($admin_id, $artist['id_users'], 'artist.updated', '_artist', $id, $old_val, $new_val);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[ARTIST EDIT] ' . $e->getMessage());
        redirectBack($back, ['msg' => 'error']);
    }

    redirectBack('/' . ADMIN_PATH . '/artist/edit?id=' . $id, ['msg' => 'updated']);
}

adminRedirect('/' . ADMIN_PATH . '/artist');