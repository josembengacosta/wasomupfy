<?php
// ═══════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Analytics: Process (AJAX) para Lojas Digitais
// Arquivo: wu-panel/pages/analytics/process-stores.php
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
// EXPORTAR DADOS (retorna JSON com todas as lojas filtradas)
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'export_data') {
    requirePermission($admin_id, 'analytics.view');

    $filters = json_decode($_POST['filters'] ?? '[]', true);
    if (!is_array($filters)) $filters = [];

    // Replicar os filtros da página
    $where  = [];
    $params = [];

    if (!empty($filters['name'])) {
        $where[]  = "s.name_store LIKE ?";
        $params[] = '%' . $filters['name'] . '%';
    }
    if (!empty($filters['type'])) {
        $where[]  = "s.type_store = ?";
        $params[] = $filters['type'];
    }
    if (!empty($filters['status'])) {
        $where[]  = "s.is_active = ?";
        $params[] = ($filters['status'] === 'active') ? 1 : 0;
    }
    if (!empty($filters['min_str'])) {
        $where[]  = "COALESCE(SUM(str.streams),0) >= ?";
        $params[] = (int)$filters['min_str'];
    }
    if (!empty($filters['max_str'])) {
        $where[]  = "COALESCE(SUM(str.streams),0) <= ?";
        $params[] = (int)$filters['max_str'];
    }

    $base_joins = "
        FROM _store s
        LEFT JOIN _stream str ON str.id_store = s.id_store
        LEFT JOIN _track t ON t.id_track = str.id_track
        LEFT JOIN _album al ON al.id_album = t.id_album
        LEFT JOIN _artist a ON a.id_artist = al.id_artist
    ";
    $sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("
        SELECT
            s.id_store,
            s.name_store,
            s.slug_store,
            s.type_store,
            s.is_active,
            s.url_store,
            s.display_order,
            COALESCE(SUM(str.streams),0) AS total_streams,
            COALESCE(SUM(str.revenue),0) AS total_revenue,
            COUNT(DISTINCT a.id_artist) AS artist_count,
            COUNT(DISTINCT t.id_track) AS track_count
        $base_joins
        $sql_where
        GROUP BY s.id_store
        ORDER BY total_streams DESC
    ");
    $stmt->execute($params);
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $usd_rate = (float)($db->query("SELECT usd_to_aoa_rate FROM _platform LIMIT 1")->fetchColumn() ?: 900);
    foreach ($stores as &$s) {
        $s['total_revenue_aoa'] = (float)$s['total_revenue'] * $usd_rate;
        $s['is_active'] = $s['is_active'] ? 'Activa' : 'Inactiva';
    }

    jsonOut(true, 'Dados obtidos', ['data' => $stores]);
}

if ($action === 'add_store') {
    requirePermission($admin_id, 'analytics.edit');

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $type = trim($_POST['type'] ?? 'streaming');
    $url = trim($_POST['url'] ?? '');
    $display_order = (int)($_POST['display_order'] ?? 0);
    $is_active = (int)($_POST['is_active'] ?? 1);
    $password = trim($_POST['password_confirm'] ?? '');

    if (empty($name) || empty($slug)) {
        jsonOut(false, 'Nome e slug são obrigatórios.');
    }
    if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        jsonOut(false, 'Slug inválido. Use apenas letras minúsculas, números e hífen.');
    }
    // Verificar se slug já existe
    $check = $db->prepare("SELECT id_store FROM _store WHERE slug_store = ?");
    $check->execute([$slug]);
    if ($check->fetch()) {
        jsonOut(false, 'Slug já existe. Escolha outro.');
    }

    // Verificar senha
    $admin_row = $db->prepare("SELECT password_employees FROM _employees WHERE id_employees=?");
    $admin_row->execute([$admin_id]);
    $admin_data = $admin_row->fetch();
    if (!$admin_data || !password_verify($password, $admin_data['password_employees'])) {
        jsonOut(false, 'Senha incorrecta.');
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO _store (name_store, slug_store, type_store, url_store, display_order, is_active, logo_store)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $slug, $type, $url, $display_order, $is_active, null]);

        $new_id = $db->lastInsertId();

        logAudit(
            $admin_id,
            null,
            'store.created',
            '_store',
            $new_id,
            null,
            json_encode(['name' => $name, 'slug' => $slug, 'type' => $type])
        );

        jsonOut(true, 'Loja adicionada com sucesso.');
    } catch (Exception $e) {
        error_log('[STORE ADD] ' . $e->getMessage());
        jsonOut(false, 'Erro ao adicionar loja.');
    }
}

// ══════════════════════════════════════════════════════════════════════════
// ACTUALIZAR ESTADO DA LOJA (activar/desactivar)
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'update_store') {
    requirePermission($admin_id, 'analytics.edit');

    $id_store = (int)($_POST['id_store'] ?? 0);
    $is_active = (int)($_POST['is_active'] ?? 0);
    $password = trim($_POST['password_confirm'] ?? '');

    if ($id_store <= 0) jsonOut(false, 'ID de loja inválido.');
    if (!in_array($is_active, [0, 1])) jsonOut(false, 'Estado inválido.');

    // Verificar senha do admin
    $admin_row = $db->prepare("SELECT password_employees FROM _employees WHERE id_employees=?");
    $admin_row->execute([$admin_id]);
    $admin_data = $admin_row->fetch();
    if (!$admin_data || !password_verify($password, $admin_data['password_employees'])) {
        jsonOut(false, 'Senha incorrecta.');
    }

    // Buscar dados antigos para auditoria
    $old_row = $db->prepare("SELECT name_store, is_active FROM _store WHERE id_store=?");
    $old_row->execute([$id_store]);
    $old = $old_row->fetch();
    if (!$old) jsonOut(false, 'Loja não encontrada.');
    if ($old['is_active'] == $is_active) {
        jsonOut(false, 'A loja já está ' . ($is_active ? 'activa' : 'inactiva') . '.');
    }

    try {
        $db->prepare("UPDATE _store SET is_active=? WHERE id_store=?")
            ->execute([$is_active, $id_store]);

        logAudit(
            $admin_id,
            null, // lojas não têm user_id associado
            'store.' . ($is_active ? 'activated' : 'deactivated'),
            '_store',
            $id_store,
            json_encode(['is_active' => $old['is_active']]),
            json_encode(['is_active' => $is_active])
        );

        jsonOut(true, $is_active ? 'Loja reactivada.' : 'Loja desactivada.');
    } catch (Exception $e) {
        error_log('[STORE UPDATE] ' . $e->getMessage());
        jsonOut(false, 'Erro ao actualizar estado da loja.');
    }
}

jsonOut(false, 'Acção desconhecida.');
