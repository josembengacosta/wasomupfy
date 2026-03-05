<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Criação de Lançamento
// Arquivo: dashboard/launch/creat_release_process.php
// ══════════════════════════════════════════════
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
requireLogin();

function jsonOut(bool $ok, string $msg, array $extra = []): never
{
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, 'Método não permitido.');
}

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    jsonOut(false, 'Sessão expirada. Recarrega a página.', ['reload' => true]);
}

$id_users = (int)$_SESSION['id_users'];
$action   = $_POST['action'] ?? '';
$db       = getDB();

// ══════════════════════════════════════════════
// VERIFICAR PLANO ACTIVO
// ══════════════════════════════════════════════
$user = getUserById($id_users);
if (!$user || $user['status_user'] !== 'active' || empty($user['plan_activated_at'])) {
    jsonOut(false, 'Sem plano activo. Activa um plano para lançar música.');
}

$plan_id  = (int)$user['plan_selected'];
$ps = $db->prepare('SELECT * FROM _plans WHERE id_plan = ?');
$ps->execute([$plan_id]);
$plan = $ps->fetch();
if (!$plan) {
    jsonOut(false, 'Plano não encontrado.');
}

$plan_slug  = $plan['slug_plan'];
$max_tracks = $plan['max_tracks_per_release'];
$can_label  = ($plan_slug !== 'single');

// ══════════════════════════════════════════════
switch ($action) {

    // ──────────────────────────────────────────
    // CREATE ARTIST
    // ──────────────────────────────────────────
    case 'create_artist':
        $stage_name   = trim($_POST['stage_name']   ?? '');
        $real_name    = trim($_POST['real_name']     ?? '');
        $artist_email = trim($_POST['artist_email']  ?? '');
        $genre_main   = trim($_POST['genre_main']    ?? '');
        $spotify      = trim($_POST['spotify_url']   ?? '');
        $website      = trim($_POST['website_url']   ?? '');
        $youtube      = trim($_POST['youtube_url']   ?? '');

        if (empty($stage_name) || strlen($stage_name) > 100) {
            jsonOut(false, 'Nome artístico obrigatório (máx. 100 caracteres).');
        }
        if (empty($artist_email) || !filter_var($artist_email, FILTER_VALIDATE_EMAIL)) {
            jsonOut(false, 'Email do artista inválido ou em falta.');
        }

        // Verificar limite do plano
        $max_artists = (int)($plan['max_artists'] ?? 1);
        $cnt = $db->prepare("SELECT COUNT(*) FROM _artist WHERE id_users = ?");
        $cnt->execute([$id_users]);
        if ((int)$cnt->fetchColumn() >= $max_artists) {
            jsonOut(false, "Limite de {$max_artists} artista(s) atingido. Faz upgrade do teu plano.");
        }

        // Verificar duplicado
        $dup = $db->prepare("SELECT id_artist FROM _artist WHERE id_users = ? AND stage_name = ?");
        $dup->execute([$id_users, $stage_name]);
        if ($dup->fetch()) {
            jsonOut(false, "Já tens um artista com o nome «{$stage_name}».");
        }

        // Upload foto
        $photo_path = null;
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file    = $_FILES['photo'];
            $mime    = mime_content_type($file['tmp_name']);
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mime, $allowed)) {
                jsonOut(false, 'Formato de imagem inválido. Usa JPG, PNG ou WebP.');
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                jsonOut(false, 'Imagem muito grande (máx. 5 MB).');
            }
            $ext        = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
            $dir        = __DIR__ . '/../../assets/comprovantes/uploads/artists/';
            if (!is_dir($dir)) mkdir($dir, 0750, true);
            $filename   = 'artist_' . $id_users . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $photo_path = $filename;
            move_uploaded_file($file['tmp_name'], $dir . $filename);
        }

        // INSERT
        $db->prepare("
            INSERT INTO _artist
                (id_users, stage_name, real_name, artist_email, genre_main,
                 spotify_url, website_url, youtube_url, photo_artist, status_artist)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ")->execute([
            $id_users,
            $stage_name,
            $real_name    ?: null,
            $artist_email,
            $genre_main   ?: null,
            $spotify      ?: null,
            $website      ?: null,
            $youtube      ?: null,
            $photo_path,
        ]);

        $new_id     = (int)$db->lastInsertId();
        $user_name  = trim($user['first_name'] . ' ' . ($user['second_name'] ?? ''));
        $user_email = $user['email_user'];
        $label_name = $plan['name_plan'] ?? APP_NAME;

        logActivity($id_users, 'artist_created', "Artista criado: {$stage_name} (#{$new_id})", 'artist', $new_id);

        // Emails ao artista e ao proprietário
        $date = date('d/m/Y \à\s H:i');
        try {
            // Email ao artista
            $subjectArtist = "Bem-vindo à " . APP_NAME . " — O teu perfil foi criado";
            $bodyArtist = "
            <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
                <div style='background:linear-gradient(135deg,#FF0089,#FF4D4D);padding:28px 32px;border-radius:16px 16px 0 0;text-align:center'>
                    <h1 style='color:#fff;margin:0;font-size:1.5rem;font-weight:800'>" . APP_NAME . "</h1>
                </div>
                <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;border:1px solid #f0f0f0;border-top:none'>
                    <h2 style='color:#222;font-size:1.15rem;margin-bottom:6px'>Olá, <strong>" . htmlspecialchars($stage_name) . "</strong>! 🎵</h2>
                    <p style='color:#555;line-height:1.6'>O teu perfil artístico foi criado na plataforma <strong>" . APP_NAME . "</strong> por <strong>" . htmlspecialchars($user_name) . "</strong> (<em>" . htmlspecialchars($label_name) . "</em>) em <strong>{$date}</strong>.</p>
                    <div style='background:#f9f0f5;border-left:4px solid #FF0089;border-radius:8px;padding:16px;margin:20px 0'>
                        <p style='margin:0;color:#333;font-size:.92rem;line-height:1.8'>
                            <strong>Nome artístico:</strong> " . htmlspecialchars($stage_name) . "<br/>
                            <strong>Género:</strong> " . (htmlspecialchars($genre_main) ?: '—') . "<br/>
                            <strong>Estado:</strong> Activo<br/>
                            <strong>Criado em:</strong> {$date}
                        </p>
                    </div>
                    <p style='color:#555;line-height:1.6'>A tua música será distribuída para mais de 150 lojas digitais assim que o teu lançamento for aprovado.</p>
                    <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0'/>
                    <p style='color:#999;font-size:.78rem;text-align:center'>" . APP_NAME . " &mdash; Não respondas a este email.</p>
                </div>
            </div>";
            sendEmail($artist_email, $subjectArtist, $bodyArtist);
        } catch (Exception $e) {
            error_log('[RELEASE ARTIST EMAIL] ' . $e->getMessage());
        }

        try {
            // Email ao proprietário
            $subjectOwner = "Artista adicionado — " . APP_NAME;
            $bodyOwner = "
            <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
                <div style='background:linear-gradient(135deg,#FF0089,#FF4D4D);padding:28px 32px;border-radius:16px 16px 0 0;text-align:center'>
                    <h1 style='color:#fff;margin:0;font-size:1.5rem;font-weight:800'>" . APP_NAME . "</h1>
                </div>
                <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;border:1px solid #f0f0f0;border-top:none'>
                    <h2 style='color:#222;font-size:1.15rem;margin-bottom:6px'>Novo artista adicionado à tua conta</h2>
                    <p style='color:#555;line-height:1.6'>Olá <strong>" . htmlspecialchars($user_name) . "</strong>, adicionaste um novo artista durante a criação de um lançamento em <strong>{$date}</strong>.</p>
                    <div style='background:#f9f0f5;border-left:4px solid #FF0089;border-radius:8px;padding:16px;margin:20px 0'>
                        <p style='margin:0;color:#333;font-size:.92rem;line-height:1.8'>
                            <strong>Nome artístico:</strong> " . htmlspecialchars($stage_name) . "<br/>
                            <strong>Género:</strong> " . (htmlspecialchars($genre_main) ?: '—') . "<br/>
                            <strong>Email do artista:</strong> " . htmlspecialchars($artist_email) . "<br/>
                            <strong>Estado:</strong> Activo<br/>
                            <strong>Data de criação:</strong> {$date}
                        </p>
                    </div>
                    <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0'/>
                    <p style='color:#999;font-size:.78rem;text-align:center'>" . APP_NAME . " &mdash; Não respondas a este email.</p>
                </div>
            </div>";
            sendEmail($user_email, $subjectOwner, $bodyOwner);
        } catch (Exception $e) {
            error_log('[RELEASE OWNER EMAIL] ' . $e->getMessage());
        }

        jsonOut(true, 'Artista criado com sucesso!', ['id_artist' => $new_id]);

    // ──────────────────────────────────────────
    // CREATE RELEASE
    // ──────────────────────────────────────────
    case 'create_release':
        $title          = trim($_POST['title_album']     ?? '');
        $version        = trim($_POST['version_album']   ?? '');
        $type_album     = trim($_POST['type_album']      ?? 'single');
        $language       = trim($_POST['language']        ?? 'pt');
        $artists_raw    = trim($_POST['artists']         ?? '[]');
        $genre_main     = trim($_POST['genre_main']      ?? '');
        $genre_sec      = trim($_POST['genre_secondary'] ?? '');
        $label_raw      = trim($_POST['label_name']      ?? '');
        $copyright_c    = trim($_POST['copyright_c']     ?? '');
        $copyright_p    = trim($_POST['copyright_p']     ?? '');
        $release_date   = trim($_POST['release_date']    ?? '');
        $tracks_raw     = trim($_POST['tracks']          ?? '[]');
        $stores_raw     = trim($_POST['stores']          ?? '[]');

        // ── Validate ──────────────────────────
        if (empty($title) || strlen($title) > 150) {
            jsonOut(false, 'Título inválido ou muito longo (máx. 150 caracteres).');
        }
        if (!in_array($type_album, ['single', 'EP', 'album', 'mixtape'])) {
            jsonOut(false, 'Tipo de lançamento inválido.');
        }
        if (empty($genre_main)) {
            jsonOut(false, 'Seleciona o género principal.');
        }

        // Artists
        $artist_ids = json_decode($artists_raw, true);
        if (!is_array($artist_ids) || empty($artist_ids)) {
            jsonOut(false, 'Seleciona pelo menos um artista.');
        }
        // Validate artists belong to user
        foreach ($artist_ids as $aid) {
            $av = $db->prepare("SELECT id_artist, stage_name FROM _artist WHERE id_artist = ? AND id_users = ?");
            $av->execute([(int)$aid, $id_users]);
            if (!$av->fetch()) jsonOut(false, 'Artista inválido ou não pertence à tua conta.');
        }
        $primary_artist_id = (int)$artist_ids[0];

        // Get artist names for name_author_band
        $placeholders = implode(',', array_fill(0, count($artist_ids), '?'));
        $names_stmt = $db->prepare("
            SELECT stage_name FROM _artist 
            WHERE id_artist IN ($placeholders) 
            ORDER BY FIELD(id_artist, $placeholders)
        ");
        $names_stmt->execute(array_merge($artist_ids, $artist_ids));
        $artist_names = array_column($names_stmt->fetchAll(PDO::FETCH_ASSOC), 'stage_name');
        $name_author_band = implode(', ', $artist_names);

        // Tracks
        $tracks = json_decode($tracks_raw, true);
        if (!is_array($tracks) || empty($tracks)) {
            jsonOut(false, 'Adiciona pelo menos uma faixa.');
        }
        if ($max_tracks && count($tracks) > $max_tracks) {
            jsonOut(false, "O teu plano permite máximo {$max_tracks} faixa(s).");
        }
        // Single plan must be type single
        if ($plan_slug === 'single' && $type_album !== 'single') {
            jsonOut(false, 'O plano Single distribui apenas singles.');
        }

        // Release date validation (at least today)
        if (empty($release_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $release_date)) {
            jsonOut(false, 'Data de lançamento inválida.');
        }
        if (strtotime($release_date) < strtotime('today')) {
            jsonOut(false, 'A data de lançamento deve ser hoje ou no futuro.');
        }

        // Label
        $label_name = $can_label && !empty($label_raw) ? $label_raw : 'WU Records';
        if (strlen($label_name) > 100) $label_name = substr($label_name, 0, 100);

        // Stores
        $store_ids = json_decode($stores_raw, true);
        if (!is_array($store_ids) || empty($store_ids)) {
            jsonOut(false, 'Seleciona pelo menos uma plataforma de distribuição.');
        }

        // ── Cover upload ──────────────────────
        $cover_path = null;
        if (!empty($_FILES['cover']['name']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $file    = $_FILES['cover'];
            $mime    = mime_content_type($file['tmp_name']);
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($mime, $allowed)) {
                jsonOut(false, 'Formato de capa inválido. Usa JPG, PNG ou WebP.');
            }
            if ($file['size'] > 15 * 1024 * 1024) {
                jsonOut(false, 'Imagem de capa demasiado grande (máx. 15 MB).');
            }

            // Validate dimensions
            $img_info = getimagesize($file['tmp_name']);
            if (!$img_info || $img_info[0] < 1400 || $img_info[1] < 1400) {
                jsonOut(false, 'A capa deve ter pelo menos 1400×1400 px.');
            }

            $ext      = $mime === 'image/png' ? 'png' : 'jpg';
            $dir      = __DIR__ . '/../../assets/comprovantes/uploads/covers/';
            if (!is_dir($dir)) mkdir($dir, 0750, true);
            $filename   = 'cover_' . $id_users . '_' . time() . '.' . $ext;
            $cover_path = $filename;
            move_uploaded_file($file['tmp_name'], $dir . $filename);
        }

        // ── DATABASE TRANSACTION ──────────────
        try {
            $db->beginTransaction();

            // 1. Insert _album
            $full_title = $title . ($version ? ' (' . $version . ')' : '');

            $stmt = $db->prepare("
                INSERT INTO _album
                    (id_users, id_artist, id_plan, upc, title_album, type_album, name_author_band,
                     genre_main, genre_secondary, language, label_name, smartlink,
                     release_date, recording_date, territory, copyright_c, copyright_p,
                     img_cover, status_album)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");

            $stmt->execute([
                $id_users,
                $primary_artist_id,
                $plan_id,
                null,
                $full_title,
                $type_album,
                $name_author_band,
                $genre_main,
                $genre_sec ?: null,
                $language,
                $label_name,
                null,
                $release_date,
                null,
                'Worldwide',
                $copyright_c,
                $copyright_p,
                $cover_path
            ]);

            $id_album = (int)$db->lastInsertId();

            // ── Audio files upload (agora com $id_album disponível) ──
            $audio_count = (int)($_POST['audio_count'] ?? 0);
            $audio_paths = [];

            for ($i = 1; $i <= $audio_count; $i++) {
                $audio_field = "audio_{$i}";

                if (!empty($_FILES[$audio_field]['name']) && $_FILES[$audio_field]['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES[$audio_field];

                    // Validar por extensão (mais seguro)
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['wav', 'flac'])) {
                        throw new Exception("Formato de áudio inválido na faixa {$i}. Use WAV ou FLAC.");
                    }

                    if ($file['size'] > 200 * 1024 * 1024) {
                        throw new Exception("Arquivo de áudio muito grande na faixa {$i} (máx. 200MB).");
                    }

                    $dir = __DIR__ . '/../../assets/uploads/audio/';
                    if (!is_dir($dir)) mkdir($dir, 0750, true);

                    $filename = 'track_' . $id_users . '_' . $id_album . '_' . $i . '_' . time() . '.' . $ext;
                    $audio_paths[$i] = $filename;

                    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                        throw new Exception("Falha ao salvar arquivo de áudio da faixa {$i}.");
                    }
                } else {
                    throw new Exception("Arquivo de áudio obrigatório para a faixa {$i}.");
                }
            }

            // 2. Insert tracks
            $track_stmt = $db->prepare("
                INSERT INTO _track
                    (id_album, id_users, title_track, track_number,
                     name_author, name_author_feat, name_composer, name_producer,
                     language, duration_seconds, explicit, isrc, audio_file, status_track)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'processing')
            ");

            foreach ($tracks as $i => $trk) {
                $trk_title   = trim($trk['title_track'] ?? '');
                $trk_author  = trim($trk['name_author'] ?? $name_author_band);
                $trk_feat    = trim($trk['name_author_feat'] ?? '');
                $trk_comp    = trim($trk['name_composer'] ?? '');
                $trk_prod    = trim($trk['name_producer'] ?? '');
                $trk_lang    = trim($trk['language'] ?? $language);
                $trk_explicit = in_array($trk['explicit'] ?? 'NO', ['YES', 'NO']) ? $trk['explicit'] : 'NO';
                $trk_isrc    = !empty($trk['isrc']) ? strtoupper(preg_replace('/[^A-Z0-9]/', '', $trk['isrc'])) : null;
                $audio_file = $audio_paths[$i + 1] ?? null;
                $trk_duration = !empty($trk['duration_seconds']) ? (int)$trk['duration_seconds'] : null;
                $trk_num     = (int)($trk['track_number'] ?? $i + 1);

                if (empty($trk_title)) continue;
                if (empty($trk_prod)) $trk_prod = 'WU Records';

                $track_stmt->execute([
                    $id_album,
                    $id_users,
                    $trk_title,
                    $trk_num,
                    $trk_author ?: $name_author_band,
                    $trk_feat ?: null,
                    $trk_comp ?: null,
                    $trk_prod,
                    $trk_lang,
                    $trk_duration,
                    $trk_explicit,
                    $trk_isrc,
                    $audio_file
                ]);
            }

            // 3. Insert _album_store entries
            $ins_store = $db->prepare("
                INSERT INTO _album_store (id_album, id_store, status)
                VALUES (?, ?, 'pending')
            ");

            foreach ($store_ids as $sid) {
                $sv = $db->prepare("SELECT id_store FROM _store WHERE id_store = ? AND is_active = 1");
                $sv->execute([(int)$sid]);
                if ($sv->fetch()) {
                    $ins_store->execute([$id_album, (int)$sid]);
                }
            }

            $db->commit();

            logActivity(
                $id_users,
                'release_submitted',
                "Lançamento submetido: {$full_title} (#{$id_album})",
                'album',
                $id_album
            );

            jsonOut(true, 'Lançamento submetido com sucesso!', [
                'id_album' => $id_album,
                'title'    => $full_title,
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            error_log('[RELEASE ERROR] ' . $e->getMessage());
            error_log('[RELEASE ERROR] Trace: ' . $e->getTraceAsString());
            jsonOut(false, 'Erro interno ao guardar o lançamento. Tenta novamente. Detalhes: ' . $e->getMessage());
        }
        break;

    // ──────────────────────────────────────────
    // EDIT RELEASE
    // ──────────────────────────────────────────
    case 'edit_release':
        $id_album = (int)($_POST['id_album'] ?? 0);
        $title = trim($_POST['title_album'] ?? '');
        $type_album = trim($_POST['type_album'] ?? '');
        $language = trim($_POST['language'] ?? '');
        $genre_main = trim($_POST['genre_main'] ?? '');
        $genre_sec = trim($_POST['genre_secondary'] ?? '');
        $label_name = trim($_POST['label_name'] ?? '');
        $release_date = trim($_POST['release_date'] ?? '');
        $copyright_c = trim($_POST['copyright_c'] ?? '');
        $copyright_p = trim($_POST['copyright_p'] ?? '');
        $name_author_band = trim($_POST['name_author_band'] ?? '');
        $id_artist = (int)($_POST['id_artist'] ?? 0);
        
        // Stores selecionadas
        $store_ids = $_POST['stores'] ?? [];
        if (!is_array($store_ids)) $store_ids = [];

        // Tracks (vem como array do formulário de edição)
        $tracks = $_POST['tracks'] ?? [];
        
        // Validar dados básicos
        if (empty($title) || strlen($title) > 150) {
            jsonOut(false, 'Título inválido ou muito longo (máx. 150 caracteres).');
        }
        if (!in_array($type_album, ['single', 'EP', 'album', 'mixtape'])) {
            jsonOut(false, 'Tipo de lançamento inválido.');
        }
        if (empty($genre_main)) {
            jsonOut(false, 'Seleciona o género principal.');
        }
        if (!$id_artist) {
            jsonOut(false, 'Seleciona um artista principal.');
        }
        
        // Verificar se o álbum pertence ao utilizador
        $check = $db->prepare("SELECT id_album FROM _album WHERE id_album = ? AND id_users = ?");
        $check->execute([$id_album, $id_users]);
        if (!$check->fetch()) {
            jsonOut(false, 'Lançamento não encontrado ou não pertence à tua conta.');
        }
        
        // Verificar se o artista pertence ao utilizador
        $check_artist = $db->prepare("SELECT id_artist FROM _artist WHERE id_artist = ? AND id_users = ?");
        $check_artist->execute([$id_artist, $id_users]);
        if (!$check_artist->fetch()) {
            jsonOut(false, 'Artista inválido ou não pertence à tua conta.');
        }

        try {
            $db->beginTransaction();
            
            // 1. Atualizar _album
            $update_album = $db->prepare("
                UPDATE _album SET
                    title_album = ?,
                    type_album = ?,
                    language = ?,
                    genre_main = ?,
                    genre_secondary = ?,
                    label_name = ?,
                    release_date = ?,
                    copyright_c = ?,
                    copyright_p = ?,
                    name_author_band = ?,
                    id_artist = ?
                WHERE id_album = ? AND id_users = ?
            ");
            
            $update_album->execute([
                $title,
                $type_album,
                $language,
                $genre_main,
                $genre_sec ?: null,
                $label_name,
                $release_date,
                $copyright_c,
                $copyright_p,
                $name_author_band,
                $id_artist,
                $id_album,
                $id_users
            ]);
            
            // 2. Atualizar tracks (se vierem do formulário)
            if (!empty($tracks) && is_array($tracks)) {
                foreach ($tracks as $track) {
                    if (empty($track['id_track'])) continue;
                    
                    $id_track = (int)$track['id_track'];
                    $track_title = trim($track['title_track'] ?? '');
                    $track_author = trim($track['name_author'] ?? $name_author_band);
                    $track_feat = trim($track['name_author_feat'] ?? '');
                    $track_comp = trim($track['name_composer'] ?? '');
                    $track_prod = trim($track['name_producer'] ?? '');
                    $track_lang = trim($track['language'] ?? $language);
                    $track_explicit = in_array($track['explicit'] ?? 'NO', ['YES', 'NO']) ? $track['explicit'] : 'NO';
                    $track_isrc = !empty($track['isrc']) ? strtoupper(preg_replace('/[^A-Z0-9]/', '', $track['isrc'])) : null;
                    $track_duration = !empty($track['duration_seconds']) ? (int)$track['duration_seconds'] : null;
                    $mix_version = trim($track['mix_version'] ?? '');
                    
                    // Combinar título com versão se existir
                    $full_track_title = $track_title . ($mix_version ? ' (' . $mix_version . ')' : '');
                    
                    $update_track = $db->prepare("
                        UPDATE _track SET
                            title_track = ?,
                            name_author = ?,
                            name_author_feat = ?,
                            name_composer = ?,
                            name_producer = ?,
                            language = ?,
                            explicit = ?,
                            isrc = ?,
                            duration_seconds = ?
                        WHERE id_track = ? AND id_album = ? AND id_users = ?
                    ");
                    
                    $update_track->execute([
                        $full_track_title,
                        $track_author ?: $name_author_band,
                        $track_feat ?: null,
                        $track_comp ?: null,
                        $track_prod,
                        $track_lang,
                        $track_explicit,
                        $track_isrc,
                        $track_duration,
                        $id_track,
                        $id_album,
                        $id_users
                    ]);
                }
            }
            
            // 3. Atualizar stores (remover todas e reinserir)
            $db->prepare("DELETE FROM _album_store WHERE id_album = ?")->execute([$id_album]);
            
            if (!empty($store_ids)) {
                $ins_store = $db->prepare("INSERT INTO _album_store (id_album, id_store, status) VALUES (?, ?, 'pending')");
                foreach ($store_ids as $sid) {
                    $sid = (int)$sid;
                    if ($sid > 0) {
                        $ins_store->execute([$id_album, $sid]);
                    }
                }
            }
            
            $db->commit();
            
            logActivity(
                $id_users,
                'release_updated',
                "Lançamento atualizado: {$title} (#{$id_album})",
                'album',
                $id_album
            );
            
            jsonOut(true, 'Lançamento atualizado com sucesso!', [
                'id_album' => $id_album
            ]);
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log('[EDIT ERROR] ' . $e->getMessage());
            error_log('[EDIT ERROR] Trace: ' . $e->getTraceAsString());
            jsonOut(false, 'Erro ao atualizar o lançamento. Detalhes: ' . $e->getMessage());
        }
        break;

    // ──────────────────────────────────────────
    // DELETE DRAFT (eliminação imediata)
    // ──────────────────────────────────────────
    case 'delete_draft':
        $id_album = (int)($_POST['id_album'] ?? 0);
        
        // Verificar se pertence ao user e é draft
        $check = $db->prepare("
            SELECT id_album FROM _album 
            WHERE id_album = ? AND id_users = ? AND status_album = 'draft'
        ");
        $check->execute([$id_album, $id_users]);
        
        if (!$check->fetch()) {
            jsonOut(false, 'Rascunho não encontrado.');
        }
        
        try {
            // As tracks são apagadas por CASCADE (foreign key)
            $db->prepare("DELETE FROM _album WHERE id_album = ?")->execute([$id_album]);
            jsonOut(true, 'Rascunho eliminado com sucesso!');
        } catch (Exception $e) {
            error_log('[DELETE DRAFT] ' . $e->getMessage());
            jsonOut(false, 'Erro ao eliminar rascunho.');
        }
        break;
        
    // ──────────────────────────────────────────
    // SOLICITAR ELIMINAÇÃO DE RASCUNHO (draft)
    // ──────────────────────────────────────────
    case 'delete_draft_request':
        $id_album = (int)($_POST['id_album'] ?? 0);
        $password = $_POST['password'] ?? '';
        
        // Verificar senha
        if (!verifyUserPassword($id_users, $password)) {
            jsonOut(false, 'Senha incorreta.');
        }
        
        // Verificar se o álbum existe, pertence ao user e é draft
        $check = $db->prepare("
            SELECT id_album, title_album, status_album 
            FROM _album 
            WHERE id_album = ? AND id_users = ? AND status_album = 'draft'
        ");
        $check->execute([$id_album, $id_users]);
        $album = $check->fetch();
        
        if (!$album) {
            jsonOut(false, 'Rascunho não encontrado.');
        }
        
        try {
            $db->beginTransaction();
            
            // Gerar código único para o pedido
            $request_code = 'DEL-' . strtoupper(uniqid());
            $expires_at = date('Y-m-d H:i:s', strtotime('+72 hours'));
            
            // Inserir pedido na tabela _delete_requests
            $stmt = $db->prepare("
                INSERT INTO _delete_requests 
                    (id_album, id_users, request_code, reason, expires_at, status)
                VALUES (?, ?, ?, 'user_request', ?, 'pending')
            ");
            $stmt->execute([$id_album, $id_users, $request_code, $expires_at]);
            
            // Guardar status anterior e atualizar para 'deleting'
            $db->prepare("
                UPDATE _album SET 
                    previous_status = status_album,
                    status_album = 'deleting',
                    delete_requested_at = NOW(),
                    delete_expires_at = ?
                WHERE id_album = ?
            ")->execute([$expires_at, $id_album]);
            
            logActivity($id_users, 'delete_requested', 
                "Solicitação de eliminação para rascunho: {$album['title_album']} (#{$id_album})", 
                'album', $id_album);
            
            $db->commit();
            
            jsonOut(true, "Solicitação recebida! O rascunho será eliminado permanentemente em 72 horas. Podes recuperá-lo na lixeira até lá.");
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log('[DELETE DRAFT ERROR] ' . $e->getMessage());
            jsonOut(false, 'Erro ao processar solicitação.');
        }
        break;
        
    // ──────────────────────────────────────────
    // SOLICITAR ELIMINAÇÃO DE LANÇAMENTO PUBLICADO
    // ──────────────────────────────────────────
    case 'delete_release_request':
        $id_album = (int)($_POST['id_album'] ?? 0);
        $password = $_POST['password'] ?? '';
        
        // Verificar senha
        if (!verifyUserPassword($id_users, $password)) {
            jsonOut(false, 'Senha incorreta.');
        }
        
        // Verificar se o álbum existe, pertence ao user e está publicado
        $check = $db->prepare("
            SELECT id_album, title_album, status_album 
            FROM _album 
            WHERE id_album = ? AND id_users = ? AND status_album IN ('approved', 'pending')
        ");
        $check->execute([$id_album, $id_users]);
        $album = $check->fetch();
        
        if (!$album) {
            jsonOut(false, 'Lançamento não encontrado ou não pode ser eliminado.');
        }
        
        try {
            $db->beginTransaction();
            
            $expires_at = date('Y-m-d H:i:s', strtotime('+72 hours'));
            
            // Inserir pedido na tabela _takedown_request
            $stmt = $db->prepare("
                INSERT INTO _takedown_request 
                    (id_users, id_album, reason, status_takedown)
                VALUES (?, ?, 'user_request', 'pending')
            ");
            $stmt->execute([$id_users, $id_album]);
            
            // Guardar status anterior e atualizar para 'deleting'
            $db->prepare("
                UPDATE _album SET 
                    previous_status = status_album,
                    status_album = 'deleting',
                    delete_requested_at = NOW(),
                    delete_expires_at = ?
                WHERE id_album = ?
            ")->execute([$expires_at, $id_album]);
            
            logActivity($id_users, 'takedown_requested', 
                "Solicitação de remoção para: {$album['title_album']} (#{$id_album})", 
                'album', $id_album);
            
            $db->commit();
            
            jsonOut(true, "Solicitação recebida! O lançamento será removido das plataformas em 72 horas. Podes recuperá-lo na lixeira até lá.");
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log('[TAKEDOWN ERROR] ' . $e->getMessage());
            jsonOut(false, 'Erro ao processar solicitação.');
        }
        break;
        
    // ──────────────────────────────────────────
    // CANCELAR PEDIDO DE ELIMINAÇÃO
    // ──────────────────────────────────────────
    case 'cancel_delete_request':
        $id_album = (int)($_POST['id_album'] ?? 0);
        
        // Verificar se o álbum existe e está em deleting
        $check = $db->prepare("
            SELECT id_album, previous_status 
            FROM _album 
            WHERE id_album = ? AND id_users = ? AND status_album = 'deleting'
        ");
        $check->execute([$id_album, $id_users]);
        $album = $check->fetch();
        
        if (!$album) {
            jsonOut(false, 'Não há pedido de eliminação ativo para este lançamento.');
        }
        
        try {
            $db->beginTransaction();
            
            // Reverter para o status anterior
            $db->prepare("
                UPDATE _album SET 
                    status_album = previous_status,
                    previous_status = NULL,
                    delete_requested_at = NULL,
                    delete_expires_at = NULL
                WHERE id_album = ? AND id_users = ?
            ")->execute([$id_album, $id_users]);
            
            // Cancelar pedidos pendentes
            $db->prepare("
                UPDATE _delete_requests SET status = 'cancelled' 
                WHERE id_album = ? AND status = 'pending'
            ")->execute([$id_album]);
            
            $db->prepare("
                UPDATE _takedown_request SET status_takedown = 'rejected' 
                WHERE id_album = ? AND status_takedown = 'pending'
            ")->execute([$id_album]);
            
            logActivity($id_users, 'delete_cancelled', 
                "Pedido de eliminação cancelado para o álbum #{$id_album}", 
                'album', $id_album);
            
            $db->commit();
            
            jsonOut(true, 'Pedido de eliminação cancelado com sucesso!');
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log('[CANCEL DELETE ERROR] ' . $e->getMessage());
            jsonOut(false, 'Erro ao cancelar pedido.');
        }
        break;

    // ──────────────────────────────────────────
    // REQUEST REVIEW (solicitar revisão)
    // ──────────────────────────────────────────
    case 'request_review':
        $id_album = (int)($_POST['id_album'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        
        if (!$id_album) {
            jsonOut(false, 'ID do lançamento inválido.');
        }
        if (strlen($reason) < 20) {
            jsonOut(false, 'A justificação deve ter pelo menos 20 caracteres.');
        }
        
        // Verificar se o álbum pertence ao utilizador e está rejected
        $check = $db->prepare("
            SELECT id_album, title_album, rejection_reason 
            FROM _album 
            WHERE id_album = ? AND id_users = ? AND status_album = 'rejected'
        ");
        $check->execute([$id_album, $id_users]);
        $album = $check->fetch();
        
        if (!$album) {
            jsonOut(false, 'Lançamento não encontrado ou não está em estado reprovado.');
        }
        
        try {
            $db->beginTransaction();
            
            // Inserir pedido de revisão
            $stmt = $db->prepare("
                INSERT INTO _album_review_request 
                    (id_album, id_users, reason_request, status_request)
                VALUES (?, ?, ?, 'pending')
            ");
            $stmt->execute([$id_album, $id_users, $reason]);
            
            // Atualizar status do álbum para 'pending_review' (se existir esse status)
            // ou deixar como está e a equipa vê os pedidos
            $db->prepare("
                UPDATE _album SET 
                    status_album = 'pending'
                WHERE id_album = ?
            ")->execute([$id_album]);
            
            logActivity($id_users, 'review_requested', 
                "Solicitação de revisão para: {$album['title_album']}", 'album', $id_album);
            
            $db->commit();
            
            jsonOut(true, 'Solicitação de revisão enviada com sucesso!');
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log('[REVIEW ERROR] ' . $e->getMessage());
            jsonOut(false, 'Erro ao enviar solicitação. Tenta novamente.');
        }
        break;

    default:
        jsonOut(false, 'Acção desconhecida.');
}