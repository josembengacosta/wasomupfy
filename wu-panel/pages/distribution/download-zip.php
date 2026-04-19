<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Download ZIP de Álbum
// Arquivo: wu-panel/pages/distribution/download-zip.php
// Rota:    wu-panel/releases/download-zip?id=X
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'music.view');

// ── Validar ID ────────────────────────────────────────────────
$id_album = (int)($_GET['id'] ?? 0);
if ($id_album <= 0) {
    http_response_code(400);
    exit('ID de álbum inválido.');
}

// ── Verificar ZipArchive ──────────────────────────────────────
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('A extensão ZipArchive não está disponível neste servidor. Contacta o administrador do sistema.');
}

// ── Buscar álbum ──────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT al.*,
           COALESCE(ar.stage_name, u.name_artist_band, u.first_name) AS artist_name,
           CONCAT(u.first_name,' ',COALESCE(u.second_name,'')) AS user_fullname
    FROM _album al
    LEFT JOIN _artist ar ON ar.id_artist = al.id_artist
    LEFT JOIN _users u ON u.id_users = al.id_users
    WHERE al.id_album = ?
");
$stmt->execute([$id_album]);
$album = $stmt->fetch();

if (!$album) {
    http_response_code(404);
    exit('Álbum não encontrado.');
}

// ── Buscar faixas com áudio ───────────────────────────────────
$tracks = $db->prepare("
    SELECT id_track, title_track, track_number, audio_file
    FROM _track
    WHERE id_album = ? AND audio_file IS NOT NULL AND audio_file != ''
    ORDER BY track_number ASC, creat_track ASC
");
$tracks->execute([$id_album]);
$tracks = $tracks->fetchAll();

// ── Caminhos base ─────────────────────────────────────────────
$root      = dirname(__DIR__, 3); // raiz do projecto
$audio_dir = $root . '/assets/uploads/audio/';
$cover_dir = $root . '/assets/comprovantes/uploads/covers/';

// ── Nome do ZIP ───────────────────────────────────────────────
function sanitize_filename(string $name): string
{
    $name = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $name);
    $name = trim(preg_replace('/\s+/', '_', $name), '_');
    return mb_substr($name, 0, 80, 'UTF-8');
}

$artist_slug = sanitize_filename($album['artist_name'] ?? 'Artista');
$date_str    = $album['release_date']
    ? date('Y-m-d', strtotime($album['release_date']))
    : date('Y-m-d', strtotime($album['creat_album']));
$zip_name    = $artist_slug . '_' . $date_str . '.zip';

// ── Criar ZIP em memória (tmp) ────────────────────────────────
$tmp_file = tempnam(sys_get_temp_dir(), 'wasom_zip_');
if (!$tmp_file) {
    http_response_code(500);
    exit('Erro ao criar ficheiro temporário.');
}

$zip = new ZipArchive();
$result = $zip->open($tmp_file, ZipArchive::OVERWRITE | ZipArchive::CREATE);

if ($result !== true) {
    http_response_code(500);
    error_log('[ZIP] ZipArchive::open falhou com código: ' . $result);
    exit('Erro ao criar o arquivo ZIP (código: ' . $result . ').');
}

$files_added = 0;

// ── Adicionar capa do álbum ───────────────────────────────────
if ($album['img_cover']) {
    $cover_path = $cover_dir . $album['img_cover'];
    if (file_exists($cover_path) && is_readable($cover_path)) {
        $cover_ext = strtolower(pathinfo($album['img_cover'], PATHINFO_EXTENSION));
        $zip->addFile($cover_path, 'cover.' . ($cover_ext ?: 'jpg'));
        $files_added++;
    }
}

// ── Adicionar faixas ──────────────────────────────────────────
foreach ($tracks as $track_idx => $t) {
    $audio_path = $audio_dir . $t['audio_file'];
    if (!file_exists($audio_path) || !is_readable($audio_path)) {
        continue; // ficheiro não existe, pular
    }

    $audio_ext  = strtolower(pathinfo($t['audio_file'], PATHINFO_EXTENSION));
    $track_num  = str_pad((int)($t['track_number'] ?: $track_idx + 1), 2, '0', STR_PAD_LEFT);
    $track_name = sanitize_filename($t['title_track']);
    $entry_name = $track_num . '_' . $track_name . '.' . ($audio_ext ?: 'mp3');

    $zip->addFile($audio_path, $entry_name);
    $files_added++;
}

// ── Adicionar ficheiro de informações ─────────────────────────
$info_lines = [
    'WASOM UPFY — Informações do Álbum',
    str_repeat('=', 50),
    '',
    'ARTISTA:   ' . ($album['artist_name'] ?? 'Independente'),
    'TÍTULO:    ' . $album['title_album'],
    'TIPO:      ' . ucfirst($album['type_album']),
    'GÉNERO:    ' . ($album['genre_main'] ?? '—'),
    'IDIOMA:    ' . ($album['language'] ?? '—'),
    'TERRITÓRIO:' . ($album['territory'] ?? 'Worldwide'),
    'UPC:       ' . ($album['upc'] ?? 'Não atribuído'),
    'LANÇAMENTO:' . ($album['release_date'] ? date('d/m/Y', strtotime($album['release_date'])) : '—'),
    'ESTADO:    ' . ucfirst($album['status_album']),
    '',
    'GRAVADORA: ' . ($album['label_name'] ?? 'Independente'),
    'COPYRIGHT ©:' . ($album['copyright_c'] ?? '—'),
    'FONOGRAMA ℗:' . ($album['copyright_p'] ?? '—'),
    '',
    'FAIXAS NO ZIP:',
    str_repeat('-', 50),
];

foreach ($tracks as $track_idx => $t) {
    $track_num = str_pad((int)($t['track_number'] ?: $track_idx + 1), 2, '0', STR_PAD_LEFT);
    $info_lines[] = sprintf('%s. %s', $track_num, $t['title_track']);
}

$info_lines[] = '';
$info_lines[] = str_repeat('=', 50);
$info_lines[] = 'Gerado em: ' . date('d/m/Y H:i:s');
$info_lines[] = 'Por: Wasom Upfy Admin Panel';

$zip->addFromString('info.txt', implode(PHP_EOL, $info_lines));

$zip->close();

// ── Registar auditoria ────────────────────────────────────────
if (function_exists('logAudit')) {
    logAudit($admin_id, null, 'album.download_zip', '_album', $id_album);
} else {
    try {
        $db->prepare("
            INSERT INTO _audit_log (id_employees, action, entity, entity_id, ip_address)
            VALUES (?, 'album.download_zip', '_album', ?, ?)
        ")->execute([$admin_id, $id_album, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {
        error_log('[ZIP AUDIT] ' . $e->getMessage());
    }
}

// ── Enviar o ficheiro ─────────────────────────────────────────
if (!file_exists($tmp_file) || filesize($tmp_file) === 0) {
    http_response_code(500);
    @unlink($tmp_file);
    exit($files_added === 0
        ? 'Nenhuma faixa com áudio disponível para gerar o ZIP.'
        : 'Erro ao gerar o arquivo ZIP.');
}

$file_size = filesize($tmp_file);

// Limpar qualquer output anterior
if (ob_get_level()) ob_end_clean();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . rawurlencode($zip_name) . '"');
header('Content-Length: ' . $file_size);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

// Enviar em chunks para evitar timeout em ficheiros grandes
$handle = fopen($tmp_file, 'rb');
if ($handle) {
    while (!feof($handle)) {
        echo fread($handle, 8192);
        if (ob_get_level()) ob_flush();
        flush();
    }
    fclose($handle);
}

// Limpar ficheiro temporário
@unlink($tmp_file);
exit;
