<?php
// ════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Admin: Processamento de FAQs
// Arquivo: wu-panel/pages/faq/faq_process.php
// ════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'content.edit');

function jsonOut(bool $ok, string $message, array $extra = []): never
{
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

$db = getDB();
$action = $_POST['action'] ?? '';

// ════════════════════════════════════════════════════════════════
// LISTAR FAQs (para DataTable)
// ════════════════════════════════════════════════════════════════
if ($action === 'list_faqs') {
    $faqs = $db->query("
        SELECT id_faq, category_faq, question, answer, status_faq, display_order
        FROM _faq
        ORDER BY display_order ASC, id_faq DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    jsonOut(true, '', ['data' => $faqs]);
}

// ════════════════════════════════════════════════════════════════
// OBTER UMA FAQ (para edição)
// ════════════════════════════════════════════════════════════════
if ($action === 'get_faq') {
    $id = (int)($_POST['id_faq'] ?? 0);
    if (!$id) jsonOut(false, 'ID inválido.');

    $stmt = $db->prepare("SELECT * FROM _faq WHERE id_faq = ?");
    $stmt->execute([$id]);
    $faq = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$faq) jsonOut(false, 'FAQ não encontrada.');
    jsonOut(true, '', ['faq' => $faq]);
}

// ════════════════════════════════════════════════════════════════
// SALVAR (nova ou editar)
// ════════════════════════════════════════════════════════════════
if ($action === 'save_faq') {
    $id = (int)($_POST['id_faq'] ?? 0);
    $category = trim($_POST['category_faq'] ?? 'Geral');
    $question = trim($_POST['question'] ?? '');
    $answer   = trim($_POST['answer'] ?? '');
    $order    = (int)($_POST['display_order'] ?? 0);
    $status   = $_POST['status_faq'] === 'hidden' ? 'hidden' : 'visible';

    if (empty($question) || empty($answer)) {
        jsonOut(false, 'Pergunta e resposta são obrigatórias.');
    }
    if (strlen($question) > 500) {
        jsonOut(false, 'A pergunta excede 500 caracteres.');
    }

    if ($id > 0) {
        // Atualizar
        $stmt = $db->prepare("
            UPDATE _faq SET
                category_faq = ?,
                question = ?,
                answer = ?,
                status_faq = ?,
                display_order = ?,
                modif_faq = NOW()
            WHERE id_faq = ?
        ");
        $stmt->execute([$category, $question, $answer, $status, $order, $id]);
        jsonOut(true, 'FAQ atualizada com sucesso.');
    } else {
        // Inserir
        $stmt = $db->prepare("
            INSERT INTO _faq (category_faq, question, answer, status_faq, display_order)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$category, $question, $answer, $status, $order]);
        jsonOut(true, 'FAQ criada com sucesso.');
    }
}

// ════════════════════════════════════════════════════════════════
// ALTERNAR VISIBILIDADE (toggle)
// ════════════════════════════════════════════════════════════════
if ($action === 'toggle_faq') {
    $id = (int)($_POST['id_faq'] ?? 0);
    $status = $_POST['status'] === 'visible' ? 'visible' : 'hidden';
    if (!$id) jsonOut(false, 'ID inválido.');

    $stmt = $db->prepare("UPDATE _faq SET status_faq = ? WHERE id_faq = ?");
    $stmt->execute([$status, $id]);
    jsonOut(true, 'Status atualizado.');
}

// ════════════════════════════════════════════════════════════════
// ELIMINAR
// ════════════════════════════════════════════════════════════════
if ($action === 'delete_faq') {
    $id = (int)($_POST['id_faq'] ?? 0);
    if (!$id) jsonOut(false, 'ID inválido.');

    $stmt = $db->prepare("DELETE FROM _faq WHERE id_faq = ?");
    $stmt->execute([$id]);
    jsonOut(true, 'FAQ eliminada permanentemente.');
}

jsonOut(false, 'Ação desconhecida.');
