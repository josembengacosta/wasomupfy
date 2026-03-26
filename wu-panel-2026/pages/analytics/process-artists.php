<?php
// ═══════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Analytics: Process (AJAX) para Artistas
// Arquivo: wu-panel-2026/pages/analytics/process-artists.php
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

$action = trim($_POST['action'] ?? '');

// ══════════════════════════════════════════════════════════════════════════
// EXPORTAR DADOS (retorna JSON com todos os artistas filtrados)
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'export_data') {
    requirePermission($admin_id, 'analytics.view');

    $filters = json_decode($_POST['filters'] ?? '[]', true);
    if (!is_array($filters)) $filters = [];

    // Replicar os filtros da página
    $where  = [];
    $params = [];

    if (!empty($filters['name'])) {
        $where[]  = "a.stage_name LIKE ?";
        $params[] = '%' . $filters['name'] . '%';
    }
    if (!empty($filters['country'])) {
        $where[]  = "a.country = ?";
        $params[] = $filters['country'];
    }
    if (!empty($filters['status'])) {
        $where[]  = "a.status_artist = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['min_str'])) {
        $where[]  = "COALESCE(SUM(s.streams),0) >= ?";
        $params[] = (int)$filters['min_str'];
    }
    if (!empty($filters['max_str'])) {
        $where[]  = "COALESCE(SUM(s.streams),0) <= ?";
        $params[] = (int)$filters['max_str'];
    }
    if (!empty($filters['date_from'])) {
        $where[]  = "s.year_stream >= YEAR(?) OR (s.year_stream = YEAR(?) AND s.month_stream >= MONTH(?))";
        $params[] = $filters['date_from'];
        $params[] = $filters['date_from'];
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where[]  = "s.year_stream <= YEAR(?) OR (s.year_stream = YEAR(?) AND s.month_stream <= MONTH(?))";
        $params[] = $filters['date_to'];
        $params[] = $filters['date_to'];
        $params[] = $filters['date_to'];
    }

    $base_joins = "
        FROM _artist a
        LEFT JOIN _album al ON al.id_artist = a.id_artist
        LEFT JOIN _track t ON t.id_album = al.id_album
        LEFT JOIN _stream s ON s.id_track = t.id_track
    ";
    $sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("
        SELECT
            a.id_artist AS id,
            a.stage_name,
            a.real_name,
            a.country,
            a.city,
            a.status_artist,
            COALESCE(SUM(s.streams),0) AS total_streams,
            COALESCE(SUM(s.revenue),0) AS total_revenue_usd,
            COUNT(DISTINCT t.id_track) AS tracks_count,
            MAX(al.release_date) AS last_release,
            a.creat_artist
        $base_joins
        $sql_where
        GROUP BY a.id_artist
        ORDER BY total_streams DESC
    ");
    $stmt->execute($params);
    $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $usd_rate = (float)($db->query("SELECT usd_to_aoa_rate FROM _platform LIMIT 1")->fetchColumn() ?: 900);
    foreach ($artists as &$a) {
        $a['total_revenue_aoa'] = (float)$a['total_revenue_usd'] * $usd_rate;
        $a['last_release'] = $a['last_release'] ? date('d/m/Y', strtotime($a['last_release'])) : '—';
        // Mapear status para texto legível
        $a['status_artist'] = match ($a['status_artist']) {
            'active' => 'Activo',
            'inactive' => 'Inactivo',
            'blocked' => 'Bloqueado',
            'processing' => 'A processar',
            default => ucfirst($a['status_artist'])
        };
    }

    jsonOut(true, 'Dados obtidos', ['data' => $artists]);
}

// ══════════════════════════════════════════════════════════════════════════
// BLOQUEAR / DESBLOQUEAR ARTISTA
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'toggle_block_artist') {
    requirePermission($admin_id, 'analytics.edit');

    $id_artist = (int)($_POST['id_artist'] ?? 0);
    $block_action = trim($_POST['block_action'] ?? '');
    $password = trim($_POST['password_confirm'] ?? '');

    if ($id_artist <= 0) jsonOut(false, 'ID de artista inválido.');
    if (!in_array($block_action, ['block', 'unblock'], true)) jsonOut(false, 'Acção inválida.');

    // Verificar senha do admin
    $admin_row = $db->prepare("SELECT password_employees FROM _employees WHERE id_employees=?");
    $admin_row->execute([$admin_id]);
    $admin_data = $admin_row->fetch();
    if (!$admin_data || !password_verify($password, $admin_data['password_employees'])) {
        jsonOut(false, 'Senha incorrecta.');
    }

    $new_status = $block_action === 'block' ? 'blocked' : 'active';
    $old_row = $db->prepare("SELECT status_artist, id_users FROM _artist WHERE id_artist=?");
    $old_row->execute([$id_artist]);
    $old = $old_row->fetch();
    if (!$old) jsonOut(false, 'Artista não encontrado.');
    if ($old['status_artist'] === $new_status) {
        jsonOut(false, 'O artista já está ' . ($new_status === 'blocked' ? 'bloqueado' : 'activo') . '.');
    }

    try {
        $db->prepare("UPDATE _artist SET status_artist=? WHERE id_artist=?")
            ->execute([$new_status, $id_artist]);

        logAudit(
            $admin_id,
            (int)$old['id_users'],
            'artist.' . ($block_action === 'block' ? 'blocked' : 'unblocked'),
            '_artist',
            $id_artist,
            json_encode(['status_artist' => $old['status_artist']]),
            json_encode(['status_artist' => $new_status])
        );

        jsonOut(true, $block_action === 'block' ? 'Artista bloqueado.' : 'Artista desbloqueado.');
    } catch (Exception $e) {
        error_log('[ARTIST BLOCK] ' . $e->getMessage());
        jsonOut(false, 'Erro ao actualizar estado do artista.');
    }
}

jsonOut(false, 'Acção desconhecida.');