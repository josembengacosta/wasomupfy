<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Processamento de Planos
// Arquivo: wu-panel-2026/pages/finances/plans-process.php
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'finances.edit');

function jsonOut(bool $ok, string $message, array $extra = []): never {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, 'Método não permitido.');
}

if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    jsonOut(false, 'Sessão expirada. Recarregue a página.');
}

$db     = getDB();
$action = $_POST['action'] ?? '';

// ════════════════════════════════════════════════════════════════
// OBTER UM PLANO (para edição)
// ════════════════════════════════════════════════════════════════
if ($action === 'get_plan') {
    $id = (int)($_POST['id_plan'] ?? 0);
    if (!$id) jsonOut(false, 'ID inválido.');

    $stmt = $db->prepare("SELECT * FROM _plans WHERE id_plan = ?");
    $stmt->execute([$id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) jsonOut(false, 'Plano não encontrado.');
    jsonOut(true, '', ['plan' => $plan]);
}

// ════════════════════════════════════════════════════════════════
// SALVAR (novo ou editar)
// ════════════════════════════════════════════════════════════════
if ($action === 'save_plan') {

    // ── Campos obrigatórios ───────────────────────────────────
    $id   = (int)($_POST['id_plan'] ?? 0);
    $name = trim($_POST['name_plan'] ?? '');
    $slug = trim($_POST['slug_plan'] ?? '');
    $desc = trim($_POST['description_plan'] ?? '');
    $type = trim($_POST['type_plan'] ?? 'per_release');

    if (empty($name) || empty($slug)) {
        jsonOut(false, 'Nome e slug são obrigatórios.');
    }
    if (!in_array($type, ['per_release', 'subscription'], true)) {
        jsonOut(false, 'Tipo de plano inválido.');
    }

    // ── Preços ────────────────────────────────────────────────
    $price       = (float)($_POST['price_plan']    ?? 0);
    $price_usd   = $_POST['price_usd']   !== '' ? (float)$_POST['price_usd']   : null;
    $price_ann   = $_POST['price_annual'] !== '' ? (float)$_POST['price_annual'] : null;
    $annual_qty  = $_POST['annual_qty']   !== '' ? (int)$_POST['annual_qty']   : null;
    $royalty     = (float)($_POST['royalty_rate'] ?? 90.00);

    // ── Validade (só subscription) ────────────────────────────
    $validity    = $_POST['validity_days'] !== '' ? (int)$_POST['validity_days'] : null;

    // ── Limites ───────────────────────────────────────────────
    $max_artists = $_POST['max_artists']          !== '' ? (int)$_POST['max_artists']          : null;
    $max_rel     = $_POST['max_releases']         !== '' ? (int)$_POST['max_releases']         : null;
    $max_tracks  = $_POST['max_tracks_per_release'] !== '' ? (int)$_POST['max_tracks_per_release'] : null;

    // ── Visual ────────────────────────────────────────────────
    $img         = trim($_POST['img_plan']  ?? '') ?: null;
    $badge       = trim($_POST['badge_text'] ?? '') ?: null;
    $is_featured = (int)($_POST['is_featured'] ?? 0);
    $order       = (int)($_POST['display_order'] ?? 0);
    $is_active   = (int)($_POST['is_active'] ?? 1);

    try {
        if ($id > 0) {
            // ── Atualizar ─────────────────────────────────────
            $stmt = $db->prepare("
                UPDATE _plans SET
                    name_plan              = ?,
                    slug_plan              = ?,
                    description_plan       = ?,
                    type_plan              = ?,
                    price_plan             = ?,
                    price_usd              = ?,
                    price_annual           = ?,
                    annual_qty             = ?,
                    validity_days          = ?,
                    max_artists            = ?,
                    max_releases           = ?,
                    max_tracks_per_release = ?,
                    royalty_rate           = ?,
                    img_plan               = ?,
                    badge_text             = ?,
                    is_featured            = ?,
                    display_order          = ?,
                    is_active              = ?,
                    modif_plan             = NOW()
                WHERE id_plan = ?
            ");
            $stmt->execute([
                $name, $slug, $desc ?: null, $type,
                $price, $price_usd, $price_ann, $annual_qty, $validity,
                $max_artists, $max_rel, $max_tracks,
                $royalty, $img, $badge, $is_featured,
                $order, $is_active,
                $id,
            ]);
            jsonOut(true, 'Plano atualizado com sucesso.');
        } else {
            // ── Inserir ───────────────────────────────────────
            $stmt = $db->prepare("
                INSERT INTO _plans (
                    name_plan, slug_plan, description_plan, type_plan,
                    price_plan, price_usd, price_annual, annual_qty, validity_days,
                    max_artists, max_releases, max_tracks_per_release,
                    royalty_rate, img_plan, badge_text, is_featured,
                    display_order, is_active
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?
                )
            ");
            $stmt->execute([
                $name, $slug, $desc ?: null, $type,
                $price, $price_usd, $price_ann, $annual_qty, $validity,
                $max_artists, $max_rel, $max_tracks,
                $royalty, $img, $badge, $is_featured,
                $order, $is_active,
            ]);
            jsonOut(true, 'Plano criado com sucesso.');
        }
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            jsonOut(false, 'Já existe um plano com este slug.');
        }
        jsonOut(false, 'Erro ao salvar: ' . $e->getMessage());
    }
}

// ════════════════════════════════════════════════════════════════
// ALTERNAR STATUS
// ════════════════════════════════════════════════════════════════
if ($action === 'toggle_plan') {
    $id     = (int)($_POST['id_plan']   ?? 0);
    $active = (int)($_POST['is_active'] ?? 1);
    if (!$id) jsonOut(false, 'ID inválido.');

    $stmt = $db->prepare("UPDATE _plans SET is_active = ?, modif_plan = NOW() WHERE id_plan = ?");
    $stmt->execute([$active, $id]);
    jsonOut(true, 'Status atualizado.');
}

// ════════════════════════════════════════════════════════════════
// ELIMINAR
// ════════════════════════════════════════════════════════════════
if ($action === 'delete_plan') {
    $id = (int)($_POST['id_plan'] ?? 0);
    if (!$id) jsonOut(false, 'ID inválido.');

    // Verificar se há utilizadores com este plano
    $check = $db->prepare("SELECT COUNT(*) FROM _users WHERE plan_selected = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        jsonOut(false, 'Não é possível eliminar: existem utilizadores com este plano selecionado.');
    }

    $stmt = $db->prepare("DELETE FROM _plans WHERE id_plan = ?");
    $stmt->execute([$id]);
    jsonOut(true, 'Plano eliminado permanentemente.');
}

jsonOut(false, 'Ação desconhecida.');