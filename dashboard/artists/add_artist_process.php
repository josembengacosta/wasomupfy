<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Artistas
// Arquivo: dashboard/artists/add_artist_process.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
requireLogin();

function jsonOut(bool $ok, string $msg, array $extra = []): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(false, 'Método não permitido.');
if (!validateCsrf($_POST['csrf_token'] ?? '')) jsonOut(false, 'Sessão expirada. Recarrega a página.');

$id_users = (int)$_SESSION['id_users'];
$action   = $_POST['action'] ?? '';
$db       = getDB();

// ── Dados do utilizador ───────────────────────
$user = getUserById($id_users);
if (!$user) jsonOut(false, 'Utilizador não encontrado.');

$user_email = $user['email_user'];
$user_name  = trim($user['first_name'] . ' ' . ($user['second_name'] ?? ''));

// ── Verificar plano ───────────────────────────
$plan_id = (int)$user['plan_selected'];
$plan    = null;
$max_artists = 1;
if ($plan_id) {
    $ps = $db->prepare('SELECT * FROM _plans WHERE id_plan = ?');
    $ps->execute([$plan_id]);
    $plan = $ps->fetch();
    if ($plan) $max_artists = (int)($plan['max_artists'] ?? 1);
}

// ── Helper: contar artistas actuais ──────────
function countArtists(PDO $db, int $id_users): int
{
    $s = $db->prepare("SELECT COUNT(*) FROM _artist WHERE id_users = ?");
    $s->execute([$id_users]);
    return (int)$s->fetchColumn();
}

// ── Helper: upload foto ───────────────────────
function uploadArtistPhoto(array $file, int $id_users): ?string
{
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return null;

    $mime    = mime_content_type($file['tmp_name']);
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowed)) throw new InvalidArgumentException('Formato inválido. Usa JPG, PNG ou WebP.');
    if ($file['size'] > 5 * 1024 * 1024) throw new InvalidArgumentException('Imagem demasiado grande (máx. 5 MB).');

    $ext      = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
    $dir      = __DIR__ . '/../../assets/comprovantes/uploads/artists/';
    if (!is_dir($dir)) mkdir($dir, 0750, true);
    $filename = 'artist_' . $id_users . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        throw new RuntimeException('Falha ao guardar a imagem.');
    }
    return $filename;
}


// ── Email: artista criado → ARTISTA ──────────────────────────────────────
function sendArtistCreatedEmailToArtist(string $artist_email, string $stage_name, string $owner_name, string $label_name, string $genre, string $country, string $city): void
{
    $date = date('d/m/Y \à\s H:i');
    $loc  = implode(', ', array_filter([$city, $country])) ?: '—';
    $subject = "Bem-vindo à " . APP_NAME . " — O teu perfil foi criado";
    $body = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
        <div style='background:linear-gradient(135deg,#FF0089,#FF4D4D);padding:28px 32px;border-radius:16px 16px 0 0;text-align:center'>
            <h1 style='color:#fff;margin:0;font-size:1.5rem;font-weight:800'>" . APP_NAME . "</h1>
        </div>
        <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;border:1px solid #f0f0f0;border-top:none'>
            <h2 style='color:#222;font-size:1.15rem;margin-bottom:6px'>Olá, <strong>" . htmlspecialchars($stage_name) . "</strong>! <i class='bi bi-music-note-beamed'></i></h2>
            <p style='color:#555;line-height:1.6'>O teu perfil artístico foi criado na plataforma <strong>" . APP_NAME . "</strong> por <strong>" . htmlspecialchars($owner_name) . "</strong> (<em>" . htmlspecialchars($label_name) . "</em>) em <strong>" . $date . "</strong>.</p>
            <div style='background:#f9f0f5;border-left:4px solid #FF0089;border-radius:8px;padding:16px;margin:20px 0'>
                <p style='margin:0;color:#333;font-size:.92rem;line-height:1.8'>
                    <strong>Nome artístico:</strong> " . htmlspecialchars($stage_name) . "<br/>
                    <strong>Género:</strong> " . (htmlspecialchars($genre) ?: '—') . "<br/>
                    <strong>Localização:</strong> " . htmlspecialchars($loc) . "<br/>
                    <strong>Estado:</strong> Em análise<br/>
                    <strong>Criado em:</strong> " . $date . "
                </p>
            </div>
            <p style='color:#555;line-height:1.6'>A tua música será distribuída para mais de 150 lojas digitais assim que o teu lançamento for aprovado. Para dúvidas, contacta o responsável pela tua conta.</p>
            <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0'/>
            <p style='color:#999;font-size:.78rem;text-align:center'>" . APP_NAME . " &mdash; Não respondas a este email.</p>
        </div>
    </div>";
    sendEmail($artist_email, $subject, $body);
}

// ── Email: artista criado → PROPRIETÁRIO ─────────────────────────────────
function sendArtistCreatedEmailToOwner(string $owner_email, string $owner_name, string $stage_name, string $artist_email, string $genre): void
{
    $date    = date('d/m/Y \à\s H:i');
    $subject = "Artista adicionado — " . APP_NAME;
    $body = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
        <div style='background:linear-gradient(135deg,#FF0089,#FF4D4D);padding:28px 32px;border-radius:16px 16px 0 0;text-align:center'>
            <h1 style='color:#fff;margin:0;font-size:1.5rem;font-weight:800'>" . APP_NAME . "</h1>
        </div>
        <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;border:1px solid #f0f0f0;border-top:none'>
            <h2 style='color:#222;font-size:1.15rem;margin-bottom:6px'>Novo artista adicionado à tua conta</h2>
            <p style='color:#555;line-height:1.6'>Olá <strong>" . htmlspecialchars($owner_name) . "</strong>, adicionaste um novo artista à tua conta em <strong>" . $date . "</strong>.</p>
            <div style='background:#f9f0f5;border-left:4px solid #FF0089;border-radius:8px;padding:16px;margin:20px 0'>
                <p style='margin:0;color:#333;font-size:.92rem;line-height:1.8'>
                    <strong>Nome artístico:</strong> " . htmlspecialchars($stage_name) . "<br/>
                    <strong>Género:</strong> " . (htmlspecialchars($genre) ?: '—') . "<br/>
                    <strong>Email do artista:</strong> " . htmlspecialchars($artist_email) . "<br/>
                    <strong>Estado inicial:</strong> Em análise<br/>
                    <strong>Data de criação:</strong> " . $date . "
                </p>
            </div>
            <p style='color:#555;font-size:.85rem;line-height:1.6'>Um email de boas-vindas foi enviado para <strong>" . htmlspecialchars($artist_email) . "</strong>.</p>
            <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0'/>
            <p style='color:#999;font-size:.78rem;text-align:center'>" . APP_NAME . " &mdash; Não respondas a este email.</p>
        </div>
    </div>";
    sendEmail($owner_email, $subject, $body);
}

// ── Email: artista actualizado → ARTISTA ─────────────────────────────────
function sendArtistUpdatedEmailToArtist(string $artist_email, string $stage_name, string $owner_name, string $genre, string $country, string $city): void
{
    $date = date('d/m/Y \à\s H:i');
    $loc  = implode(', ', array_filter([$city, $country])) ?: '—';
    $subject = "O teu perfil foi actualizado — " . APP_NAME;
    $body = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
        <div style='background:linear-gradient(135deg,#FF0089,#FF4D4D);padding:28px 32px;border-radius:16px 16px 0 0;text-align:center'>
            <h1 style='color:#fff;margin:0;font-size:1.5rem;font-weight:800'>" . APP_NAME . "</h1>
        </div>
        <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;border:1px solid #f0f0f0;border-top:none'>
            <h2 style='color:#222;font-size:1.15rem;margin-bottom:6px'>O teu perfil artístico foi actualizado</h2>
            <p style='color:#555;line-height:1.6'>Olá <strong>" . htmlspecialchars($stage_name) . "</strong>, o teu perfil na plataforma <strong>" . APP_NAME . "</strong> foi actualizado por <strong>" . htmlspecialchars($owner_name) . "</strong> em <strong>" . $date . "</strong>.</p>
            <div style='background:#f0f7ff;border-left:4px solid #1877f2;border-radius:8px;padding:16px;margin:20px 0'>
                <p style='margin:0;color:#333;font-size:.92rem;line-height:1.8'>
                    <strong>Nome artístico:</strong> " . htmlspecialchars($stage_name) . "<br/>
                    <strong>Género:</strong> " . (htmlspecialchars($genre) ?: '—') . "<br/>
                    <strong>Localização:</strong> " . htmlspecialchars($loc) . "<br/>
                    <strong>Actualizado em:</strong> " . $date . "
                </p>
            </div>
            <p style='color:#555;line-height:1.6;font-size:.85rem'>Se não reconheces esta alteração, contacta o responsável pela tua conta.</p>
            <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0'/>
            <p style='color:#999;font-size:.78rem;text-align:center'>" . APP_NAME . " &mdash; Não respondas a este email.</p>
        </div>
    </div>";
    sendEmail($artist_email, $subject, $body);
}

// ── Email: artista actualizado → PROPRIETÁRIO ────────────────────────────
function sendArtistUpdatedEmailToOwner(string $owner_email, string $owner_name, string $stage_name, string $artist_email, string $genre): void
{
    $date    = date('d/m/Y \à\s H:i');
    $subject = "Perfil de artista actualizado — " . APP_NAME;
    $body = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
        <div style='background:linear-gradient(135deg,#FF0089,#FF4D4D);padding:28px 32px;border-radius:16px 16px 0 0;text-align:center'>
            <h1 style='color:#fff;margin:0;font-size:1.5rem;font-weight:800'>" . APP_NAME . "</h1>
        </div>
        <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;border:1px solid #f0f0f0;border-top:none'>
            <h2 style='color:#222;font-size:1.15rem;margin-bottom:6px'>Perfil de artista actualizado</h2>
            <p style='color:#555;line-height:1.6'>Olá <strong>" . htmlspecialchars($owner_name) . "</strong>, actualizaste o perfil do artista <strong>" . htmlspecialchars($stage_name) . "</strong> em <strong>" . $date . "</strong>.</p>
            <div style='background:#f0f7ff;border-left:4px solid #1877f2;border-radius:8px;padding:16px;margin:20px 0'>
                <p style='margin:0;color:#333;font-size:.92rem;line-height:1.8'>
                    <strong>Nome artístico:</strong> " . htmlspecialchars($stage_name) . "<br/>
                    <strong>Género:</strong> " . (htmlspecialchars($genre) ?: '—') . "<br/>
                    <strong>Email do artista:</strong> " . htmlspecialchars($artist_email) . "<br/>
                    <strong>Data da alteração:</strong> " . $date . "
                </p>
            </div>
            <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0'/>
            <p style='color:#999;font-size:.78rem;text-align:center'>" . APP_NAME . " &mdash; Não respondas a este email.</p>
        </div>
    </div>";
    sendEmail($owner_email, $subject, $body);
}

// ── Email: artista eliminado → ARTISTA ───────────────────────────────────
function sendArtistDeletedEmailToArtist(string $artist_email, string $stage_name, string $owner_name): void
{
    $date    = date('d/m/Y \à\s H:i');
    $subject = "O teu perfil artístico foi removido — " . APP_NAME;
    $body = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
        <div style='background:linear-gradient(135deg,#555,#333);padding:28px 32px;border-radius:16px 16px 0 0;text-align:center'>
            <h1 style='color:#fff;margin:0;font-size:1.5rem;font-weight:800'>" . APP_NAME . "</h1>
        </div>
        <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;border:1px solid #f0f0f0;border-top:none'>
            <h2 style='color:#222;font-size:1.15rem;margin-bottom:6px'>Perfil removido da plataforma</h2>
            <p style='color:#555;line-height:1.6'>Olá <strong>" . htmlspecialchars($stage_name) . "</strong>, o teu perfil artístico foi <strong>removido</strong> da plataforma <strong>" . APP_NAME . "</strong> por <strong>" . htmlspecialchars($owner_name) . "</strong> em <strong>" . $date . "</strong>.</p>
            <div style='background:#fff3cd;border-left:4px solid #ffc107;border-radius:8px;padding:16px;margin:20px 0'>
                <p style='margin:0;color:#333;font-size:.92rem;line-height:1.8'>
                    <strong>Nome artístico:</strong> " . htmlspecialchars($stage_name) . "<br/>
                    <strong>Data de remoção:</strong> " . $date . "
                </p>
            </div>
            <p style='color:#555;line-height:1.6;font-size:.85rem'>Se achares que isto foi um erro, contacta directamente o responsável pela tua conta.</p>
            <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0'/>
            <p style='color:#999;font-size:.78rem;text-align:center'>" . APP_NAME . " &mdash; Não respondas a este email.</p>
        </div>
    </div>";
    sendEmail($artist_email, $subject, $body);
}

// ── Email: artista eliminado → PROPRIETÁRIO ──────────────────────────────
function sendArtistDeletedEmailToOwner(string $owner_email, string $owner_name, string $stage_name, string $artist_email, bool $notified): void
{
    $date       = date('d/m/Y \à\s H:i');
    $notif_text = $notified
        ? 'O artista foi notificado por email (<strong>' . htmlspecialchars($artist_email) . '</strong>).'
        : 'Eliminação silenciosa — o artista <strong>não</strong> foi notificado.';
    $subject = "Artista eliminado da tua conta — " . APP_NAME;
    $body = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
        <div style='background:linear-gradient(135deg,#FF0089,#FF4D4D);padding:28px 32px;border-radius:16px 16px 0 0;text-align:center'>
            <h1 style='color:#fff;margin:0;font-size:1.5rem;font-weight:800'>" . APP_NAME . "</h1>
        </div>
        <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;border:1px solid #f0f0f0;border-top:none'>
            <h2 style='color:#222;font-size:1.15rem;margin-bottom:6px'>Artista eliminado da tua conta</h2>
            <p style='color:#555;line-height:1.6'>Olá <strong>" . htmlspecialchars($owner_name) . "</strong>, eliminaste o perfil do artista <strong>" . htmlspecialchars($stage_name) . "</strong> em <strong>" . $date . "</strong>.</p>
            <div style='background:#f8d7da;border-left:4px solid #dc3545;border-radius:8px;padding:16px;margin:20px 0'>
                <p style='margin:0;color:#333;font-size:.92rem;line-height:1.8'>
                    <strong>Nome artístico:</strong> " . htmlspecialchars($stage_name) . "<br/>
                    <strong>Email do artista:</strong> " . htmlspecialchars($artist_email) . "<br/>
                    <strong>Data de eliminação:</strong> " . $date . "
                </p>
            </div>
            <p style='color:#888;font-size:.85rem'>" . $notif_text . "</p>
            <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0'/>
            <p style='color:#999;font-size:.78rem;text-align:center'>" . APP_NAME . " &mdash; Não respondas a este email.</p>
        </div>
    </div>";
    sendEmail($owner_email, $subject, $body);
}

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
        $country      = trim($_POST['country']       ?? '');
        $city         = trim($_POST['city']          ?? '');
        $bio          = trim($_POST['bio']           ?? '');
        $spotify_url  = trim($_POST['spotify_url']   ?? '');
        $youtube_url  = trim($_POST['youtube_url']   ?? '');
        $instagram_url = trim($_POST['instagram_url'] ?? '');
        $tiktok_url   = trim($_POST['tiktok_url']    ?? '');
        $facebook_url = trim($_POST['facebook_url']  ?? '');
        $website_url  = trim($_POST['website_url']   ?? '');
        $default_role    = trim($_POST['default_role']     ?? '');
        $genre_secondary = trim($_POST['genre_secondary']  ?? '');

        // Validações
        if (empty($stage_name) || strlen($stage_name) > 100)
            jsonOut(false, 'Nome artístico obrigatório (máx. 100 caracteres).');
        if (empty($artist_email) || !filter_var($artist_email, FILTER_VALIDATE_EMAIL))
            jsonOut(false, 'Email do artista inválido ou em falta.');

        // Limite do plano
        if (countArtists($db, $id_users) >= $max_artists)
            jsonOut(false, "Limite de {$max_artists} artista(s) atingido. Faz upgrade do teu plano.");

        // Duplicado
        $dup = $db->prepare("SELECT id_artist FROM _artist WHERE id_users = ? AND stage_name = ?");
        $dup->execute([$id_users, $stage_name]);
        if ($dup->fetch()) jsonOut(false, "Já tens um artista com o nome «{$stage_name}».");

        // Upload foto
        $photo_path = null;
        try {
            if (!empty($_FILES['photo']['name'])) {
                $photo_path = uploadArtistPhoto($_FILES['photo'], $id_users);
            }
        } catch (Exception $e) {
            jsonOut(false, $e->getMessage());
        }

        // INSERT
        $db->prepare("
    INSERT INTO _artist
        (id_users, stage_name, real_name, artist_email, default_role,
         genre_main, genre_secondary, country, city, bio,
         photo_artist, spotify_url, youtube_url, instagram_url,
         tiktok_url, facebook_url, website_url, status_artist)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'processing')
")->execute([
    $id_users,
    $stage_name,
    $real_name ?: null,
    $artist_email,
    $default_role ?: null,
    $genre_main ?: null,
    $genre_secondary ?: null,
    $country ?: null,
    $city ?: null,
    $bio ?: null,
    $photo_path,
    $spotify_url ?: null,
    $youtube_url ?: null,
    $instagram_url ?: null,
    $tiktok_url ?: null,
    $facebook_url ?: null,
    $website_url ?: null,
]);
        $new_id = (int)$db->lastInsertId();
        logActivity($id_users, 'artist_created', "Artista criado: {$stage_name} (#{$new_id})", 'artist', $new_id);

        // Emails ao artista e ao proprietário
        try {
            $label_name = $plan ? $plan['name_plan'] : APP_NAME;
            sendArtistCreatedEmailToArtist($artist_email, $stage_name, $user_name, $label_name, $genre_main, $country, $city);
            sendArtistCreatedEmailToOwner($user_email, $user_name, $stage_name, $artist_email, $genre_main);
        } catch (Exception $e) {
            error_log('[ARTIST EMAIL] ' . $e->getMessage());
        }

        jsonOut(true, 'Artista criado com sucesso!', ['id_artist' => $new_id]);

        // ──────────────────────────────────────────
        // UPDATE ARTIST
        // ──────────────────────────────────────────
    case 'update_artist':
        $id_artist     = (int)($_POST['id_artist']      ?? 0);
        $stage_name    = trim($_POST['stage_name']      ?? '');
        $real_name     = trim($_POST['real_name']       ?? '');
        $artist_email  = trim($_POST['artist_email']    ?? '');
        $genre_main    = trim($_POST['genre_main']      ?? '');
        $country       = trim($_POST['country']         ?? '');
        $city          = trim($_POST['city']            ?? '');
        $bio           = trim($_POST['bio']             ?? '');
        $spotify_url   = trim($_POST['spotify_url']     ?? '');
        $youtube_url   = trim($_POST['youtube_url']     ?? '');
        $instagram_url = trim($_POST['instagram_url']   ?? '');
        $tiktok_url    = trim($_POST['tiktok_url']      ?? '');
        $facebook_url  = trim($_POST['facebook_url']    ?? '');
        $website_url   = trim($_POST['website_url']     ?? '');
        $default_role    = trim($_POST['default_role']     ?? '');
        $genre_secondary = trim($_POST['genre_secondary']  ?? '');
        $pwd           = $_POST['password_confirm']     ?? '';

        if (!$id_artist) jsonOut(false, 'ID de artista inválido.');
        if (empty($stage_name) || strlen($stage_name) > 100)
            jsonOut(false, 'Nome artístico obrigatório (máx. 100 caracteres).');
        if (empty($artist_email) || !filter_var($artist_email, FILTER_VALIDATE_EMAIL))
            jsonOut(false, 'Email do artista inválido ou em falta.');
        if (empty($pwd)) jsonOut(false, 'A confirmação de senha é obrigatória.');
        if (!verifyUserPassword($id_users, $pwd)) jsonOut(false, 'Senha incorrecta.');

        // Verificar que o artista pertence ao utilizador
        $av = $db->prepare("SELECT * FROM _artist WHERE id_artist = ? AND id_users = ?");
        $av->execute([$id_artist, $id_users]);
        $existing = $av->fetch();
        if (!$existing) jsonOut(false, 'Artista não encontrado ou sem permissão.');

        // Duplicado (excluindo o próprio)
        $dup = $db->prepare("SELECT id_artist FROM _artist WHERE id_users = ? AND stage_name = ? AND id_artist != ?");
        $dup->execute([$id_users, $stage_name, $id_artist]);
        if ($dup->fetch()) jsonOut(false, "Já tens outro artista com o nome «{$stage_name}».");

        // Upload foto
        $photo_path = $existing['photo_artist'];
        try {
            if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                // Apagar foto anterior
                if ($photo_path) {
                    $old = __DIR__ . '/../../assets/comprovantes/uploads/artists/' . $photo_path;
                    if (file_exists($old)) @unlink($old);
                }
                $photo_path = uploadArtistPhoto($_FILES['photo'], $id_users);
            }
        } catch (Exception $e) {
            jsonOut(false, $e->getMessage());
        }

        // UPDATE
        $db->prepare("
    UPDATE _artist SET
        stage_name = ?, real_name = ?, artist_email = ?, default_role = ?,
        genre_main = ?, genre_secondary = ?,
        country = ?, city = ?, bio = ?,
        photo_artist = ?, spotify_url = ?, youtube_url = ?, instagram_url = ?,
        tiktok_url = ?, facebook_url = ?, website_url = ?,
        modif_artist = NOW()
    WHERE id_artist = ? AND id_users = ?
")->execute([
    $stage_name,
    $real_name ?: null,
    $artist_email,
    $default_role ?: null,
    $genre_main ?: null,
    $genre_secondary ?: null,
    $country ?: null,
    $city ?: null,
    $bio ?: null,
    $photo_path,
    $spotify_url ?: null,
    $youtube_url ?: null,
    $instagram_url ?: null,
    $tiktok_url ?: null,
    $facebook_url ?: null,
    $website_url ?: null,
    $id_artist,
    $id_users
]);

        logActivity($id_users, 'artist_updated', "Artista actualizado: {$stage_name} (#{$id_artist})", 'artist', $id_artist);

        // Emails ao proprietário e ao artista
        try {
            sendArtistUpdatedEmailToOwner($user_email, $user_name, $stage_name, $artist_email, $genre_main);
            sendArtistUpdatedEmailToArtist($artist_email, $stage_name, $user_name, $genre_main, $country, $city);
        } catch (Exception $e) {
            error_log('[ARTIST EMAIL] ' . $e->getMessage());
        }

        jsonOut(true, 'Artista actualizado com sucesso!');

        // ──────────────────────────────────────────
        // DELETE ARTIST
        // ──────────────────────────────────────────
    case 'delete_artist':
        $id_artist     = (int)($_POST['id_artist']       ?? 0);
        $pwd           = $_POST['password_confirm']       ?? '';
        $notify_artist = (int)($_POST['notify_artist']   ?? 0);

        if (!$id_artist) jsonOut(false, 'ID de artista inválido.');
        if (empty($pwd)) jsonOut(false, 'A senha é obrigatória para eliminar.');
        if (!verifyUserPassword($id_users, $pwd)) jsonOut(false, 'Senha incorrecta. Tenta novamente.');

        // Verificar que pertence ao utilizador
        $av = $db->prepare("SELECT * FROM _artist WHERE id_artist = ? AND id_users = ?");
        $av->execute([$id_artist, $id_users]);
        $artist = $av->fetch();
        if (!$artist) jsonOut(false, 'Artista não encontrado ou sem permissão.');

        // Verificar se tem álbuns activos (approved/pending/under_review)
        $alb = $db->prepare("
            SELECT COUNT(*) FROM _album
            WHERE id_artist = ? AND status_album IN ('approved','pending','under_review')
        ");
        $alb->execute([$id_artist]);
        if ((int)$alb->fetchColumn() > 0) {
            jsonOut(false, 'Este artista tem lançamentos activos ou em análise. Remove ou arquiva os lançamentos primeiro.');
        }

        $stage_name = $artist['stage_name'];

        // Apagar foto
        if ($artist['photo_artist']) {
            $old = __DIR__ . '/../../assets/comprovantes/uploads/artists/' . $artist['photo_artist'];
            if (file_exists($old)) @unlink($old);
        }

        // DELETE (cascata vai cuidar de álbuns em draft)
        $db->prepare("DELETE FROM _artist WHERE id_artist = ? AND id_users = ?")->execute([$id_artist, $id_users]);

        logActivity($id_users, 'artist_deleted', "Artista eliminado: {$stage_name} (#{$id_artist})", 'artist', $id_artist);

        // Email ao proprietário
        try {
            sendArtistDeletedEmailToOwner($user_email, $user_name, $stage_name, (string)($artist['artist_email'] ?? ''), (bool)$notify_artist);
        } catch (Exception $e) {
            error_log('[ARTIST EMAIL] ' . $e->getMessage());
        }

        // Email ao artista (só se o proprietário optou por notificar)
        if ($notify_artist && !empty($artist['artist_email'])) {
            try {
                sendArtistDeletedEmailToArtist($artist['artist_email'], $stage_name, $user_name);
            } catch (Exception $e) {
                error_log('[ARTIST EMAIL] ' . $e->getMessage());
            }
        }

        jsonOut(true, 'Artista eliminado com sucesso.');

    default:
        jsonOut(false, 'Acção desconhecida.');
}