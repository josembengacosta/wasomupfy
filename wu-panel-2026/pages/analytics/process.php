<?php
// ═══════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Analytics: Process (AJAX)
// Arquivo: wu-panel-2026/pages/analytics/process.php
// Rota:    wu-panel-2026/analytics/process (POST only)
// ═══════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.view');

function jsonOut(bool $ok, string $msg, array $extra = []): never
{
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, 'Método não permitido.');
}

// ── CSRF ──────────────────────────────────────────────────────────────────
if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    jsonOut(false, 'Sessão expirada. Recarrega a página.');
}

$action    = trim($_POST['action'] ?? '');
$id_stream = (int)($_POST['id_stream'] ?? 0);

// ══════════════════════════════════════════════════════════════════════════
// ADICIONAR STREAM
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'add_stream') {
    requirePermission($admin_id, 'analytics.edit');

    $id_track  = (int)($_POST['id_track']  ?? 0);
    $id_store  = (int)($_POST['id_store']  ?? 0);
    $year      = (int)($_POST['year']      ?? 0);
    $month     = (int)($_POST['month']     ?? 0);
    $streams   = (int)($_POST['streams']   ?? 0);
    $downloads = (int)($_POST['downloads'] ?? 0);
    $revenue   = (float)($_POST['revenue'] ?? 0);

    if (!$id_track || !$id_store || $year < 2020 || $year > 2099 || $month < 1 || $month > 12) {
        jsonOut(false, 'Dados inválidos. Verifica faixa, plataforma, ano e mês.');
    }
    if ($streams < 0) {
        jsonOut(false, 'O número de streams não pode ser negativo.');
    }

    // Verificar se a faixa existe e pertence à plataforma
    $track = $db->prepare("SELECT t.id_track, t.id_album, al.id_users FROM _track t JOIN _album al ON al.id_album=t.id_album WHERE t.id_track=? AND t.status_track IN ('active','approved')");
    $track->execute([$id_track]);
    $track_row = $track->fetch();
    if (!$track_row) {
        jsonOut(false, 'Faixa não encontrada ou não está activa.');
    }

    // Verificar se a loja existe
    $store = $db->prepare("SELECT id_store FROM _store WHERE id_store=? AND is_active=1");
    $store->execute([$id_store]);
    if (!$store->fetch()) {
        jsonOut(false, 'Plataforma não encontrada.');
    }

    try {
        // INSERT ON DUPLICATE KEY UPDATE — soma streams/downloads/revenue se já existir
        $stmt = $db->prepare("
            INSERT INTO _stream (id_track, id_store, year_stream, month_stream, streams, downloads, revenue)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                streams   = streams   + VALUES(streams),
                downloads = downloads + VALUES(downloads),
                revenue   = revenue   + VALUES(revenue),
                modif_stream = NOW()
        ");
        $stmt->execute([$id_track, $id_store, $year, $month, $streams, $downloads, $revenue]);

        $new_id = $db->lastInsertId() ?: 0;

        logAudit(
            $admin_id,
            (int)$track_row['id_users'],
            'analytics.stream_added',
            '_stream',
            $new_id ?: null,
            null,
            json_encode(['id_track' => $id_track, 'id_store' => $id_store, 'year' => $year, 'month' => $month, 'streams' => $streams])
        );

        jsonOut(true, 'Stream adicionado com sucesso! O TOP 5 foi actualizado.');
    } catch (Exception $e) {
        error_log('[ANALYTICS ADD] ' . $e->getMessage());
        jsonOut(false, 'Erro ao guardar. Tenta novamente.');
    }
}

// ══════════════════════════════════════════════════════════════════════════
// ACTUALIZAR STREAM
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'update_stream') {
    requirePermission($admin_id, 'analytics.edit');

    if ($id_stream <= 0) jsonOut(false, 'ID de stream inválido.');

    $year      = (int)($_POST['year']      ?? 0);
    $month     = (int)($_POST['month']     ?? 0);
    $streams   = (int)($_POST['streams']   ?? 0);
    $downloads = (int)($_POST['downloads'] ?? 0);
    $revenue   = (float)($_POST['revenue'] ?? 0);

    if ($year < 2020 || $year > 2099 || $month < 1 || $month > 12 || $streams < 0) {
        jsonOut(false, 'Dados inválidos. Verifica os campos.');
    }

    // Buscar registo actual
    $old = $db->prepare("SELECT s.*, al.id_users FROM _stream s JOIN _track t ON t.id_track=s.id_track JOIN _album al ON al.id_album=t.id_album WHERE s.id_stream=?");
    $old->execute([$id_stream]);
    $old_row = $old->fetch();
    if (!$old_row) jsonOut(false, 'Registo não encontrado.');

    try {
        $db->prepare("
            UPDATE _stream
            SET year_stream=?, month_stream=?, streams=?, downloads=?, revenue=?, modif_stream=NOW()
            WHERE id_stream=?
        ")->execute([$year, $month, $streams, $downloads, $revenue, $id_stream]);

        $old_val = json_encode(['year' => $old_row['year_stream'], 'month' => $old_row['month_stream'], 'streams' => $old_row['streams'], 'downloads' => $old_row['downloads'], 'revenue' => $old_row['revenue']]);
        $new_val = json_encode(['year' => $year, 'month' => $month, 'streams' => $streams, 'downloads' => $downloads, 'revenue' => $revenue]);

        logAudit($admin_id, (int)$old_row['id_users'], 'analytics.stream_updated', '_stream', $id_stream, $old_val, $new_val);

        jsonOut(true, 'Dados actualizados com sucesso!');
    } catch (Exception $e) {
        error_log('[ANALYTICS UPDATE] ' . $e->getMessage());
        jsonOut(false, 'Erro ao actualizar. Tenta novamente.');
    }
}

// ══════════════════════════════════════════════════════════════════════════
// BLOQUEAR / DESBLOQUEAR TRACK
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'toggle_block') {
    requirePermission($admin_id, 'analytics.edit');

    if ($id_stream <= 0) jsonOut(false, 'ID de stream inválido.');

    $block_action = trim($_POST['block_action'] ?? '');
    if (!in_array($block_action, ['block', 'unblock'], true)) {
        jsonOut(false, 'Acção inválida.');
    }

    // Buscar o id_track a partir do stream
    $stream_row = $db->prepare("SELECT s.id_track, al.id_users, t.status_track FROM _stream s JOIN _track t ON t.id_track=s.id_track JOIN _album al ON al.id_album=t.id_album WHERE s.id_stream=?");
    $stream_row->execute([$id_stream]);
    $sr = $stream_row->fetch();
    if (!$sr) jsonOut(false, 'Stream não encontrado.');

    $new_status = $block_action === 'block' ? 'blocked' : 'active';
    $old_status = $sr['status_track'];

    if ($old_status === $new_status) {
        jsonOut(false, 'A track já está ' . ($new_status === 'blocked' ? 'bloqueada' : 'activa') . '.');
    }

    try {
        $db->prepare("UPDATE _track SET status_track=? WHERE id_track=?")
            ->execute([$new_status, $sr['id_track']]);

        logAudit(
            $admin_id,
            (int)$sr['id_users'],
            'analytics.track_' . $block_action . 'ed',
            '_track',
            $sr['id_track'],
            json_encode(['status_track' => $old_status]),
            json_encode(['status_track' => $new_status])
        );

        $msg = $block_action === 'block'
            ? 'Track bloqueada com sucesso. Não aparecerá no TOP 5.'
            : 'Track desbloqueada com sucesso. Voltará ao TOP 5.';

        jsonOut(true, $msg);
    } catch (Exception $e) {
        error_log('[ANALYTICS BLOCK] ' . $e->getMessage());
        jsonOut(false, 'Erro ao actualizar estado da track.');
    }
}

// ══════════════════════════════════════════════════════════════════════════
// EXCLUIR STREAM
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'delete_stream') {
    requirePermission($admin_id, 'analytics.edit');

    if ($id_stream <= 0) jsonOut(false, 'ID de stream inválido.');

    // Verificar senha do admin
    $admin_row = $db->prepare("SELECT password_employees FROM _employees WHERE id_employees=?");
    $admin_row->execute([$admin_id]);
    $admin_data = $admin_row->fetch();
    if (!$admin_data) jsonOut(false, 'Erro de sessão. Faz login novamente.');

    $password = $_POST['password_confirm'] ?? '';
    if (empty($password) || !password_verify($password, $admin_data['password_employees'])) {
        jsonOut(false, 'Senha incorrecta. Verifica e tenta novamente.');
    }

    // Buscar dados para audit
    $stream_data = $db->prepare("
        SELECT s.*, t.title_track, al.id_users,
               COALESCE(ar.stage_name, u.name_artist_band, u.first_name) AS artist_name
        FROM _stream s
        JOIN _track t ON t.id_track=s.id_track
        JOIN _album al ON al.id_album=t.id_album
        JOIN _users u ON u.id_users=al.id_users
        LEFT JOIN _artist ar ON ar.id_artist=al.id_artist
        WHERE s.id_stream=?
    ");
    $stream_data->execute([$id_stream]);
    $sd = $stream_data->fetch();
    if (!$sd) jsonOut(false, 'Registo não encontrado.');

    $audit_old = json_encode([
        'track'   => $sd['title_track'],
        'artist'  => $sd['artist_name'],
        'streams' => $sd['streams'],
        'year'    => $sd['year_stream'],
        'month'   => $sd['month_stream'],
    ]);

    try {
        $db->prepare("DELETE FROM _stream WHERE id_stream=?")->execute([$id_stream]);

        logAudit($admin_id, (int)$sd['id_users'], 'analytics.stream_deleted', '_stream', $id_stream, $audit_old, null);

        jsonOut(true, 'Registo de stream eliminado com sucesso.');
    } catch (Exception $e) {
        error_log('[ANALYTICS DELETE] ' . $e->getMessage());
        jsonOut(false, 'Erro ao eliminar. Tenta novamente.');
    }
}

jsonOut(false, 'Acção desconhecida.');