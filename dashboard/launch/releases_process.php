<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Acções de Lançamentos
// Arquivo: dashboard/launch/releases_process.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
requireLogin();

function jsonOut(bool $ok, string $msg, array $extra = []): never
{
    header('Content-Type: application/json');
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

switch ($action) {

    // ══════════════════════════════════════════
    // SOLICITAR REVISÃO de lançamento reprovado
    // ══════════════════════════════════════════
    case 'request_review':
        $id_album = (int)($_POST['id_album'] ?? 0);
        $reason   = trim($_POST['reason'] ?? '');

        if (!$id_album) {
            jsonOut(false, 'Lançamento inválido.');
        }
        if (strlen($reason) < 20) {
            jsonOut(false, 'A justificação deve ter pelo menos 20 caracteres.');
        }

        // Verificar que o álbum pertence ao utilizador e está reprovado
        $alb = $db->prepare("
            SELECT id_album, title_album, status_album
            FROM _album
            WHERE id_album = ? AND id_users = ?
        ");
        $alb->execute([$id_album, $id_users]);
        $album = $alb->fetch();

        if (!$album) {
            jsonOut(false, 'Lançamento não encontrado.');
        }
        if ($album['status_album'] !== 'rejected') {
            jsonOut(false, 'Só é possível solicitar revisão de lançamentos reprovados.');
        }

        // Verificar se já existe pedido pendente para este álbum
        $existing = $db->prepare("
            SELECT id_review FROM _album_review_request
            WHERE id_album = ? AND status_request = 'pending'
            LIMIT 1
        ");
        $existing->execute([$id_album]);
        if ($existing->fetch()) {
            jsonOut(false, 'Já existe uma solicitação de revisão pendente para este lançamento. Aguarda a resposta da equipa.');
        }

        // Inserir pedido de revisão
        $db->prepare("
            INSERT INTO _album_review_request
                (id_album, id_users, reason_request, status_request, creat_request)
            VALUES (?, ?, ?, 'pending', NOW())
        ")->execute([$id_album, $id_users, $reason]);

        // Actualizar o álbum para 'under_review' (estado intermediário)
        $db->prepare("
            UPDATE _album
            SET status_album = 'under_review', modif_album = NOW()
            WHERE id_album = ? AND id_users = ?
        ")->execute([$id_album, $id_users]);

        logActivity(
            $id_users,
            'review_requested',
            "Revisão solicitada para o lançamento #{$id_album}: {$album['title_album']}",
            'album',
            $id_album
        );

        jsonOut(true, 'Solicitação enviada! A nossa equipa irá rever o teu lançamento em breve.');

        // ══════════════════════════════════════════
        // ELIMINAR rascunho da BD (se foi guardado)
        // ══════════════════════════════════════════
    case 'delete_draft':
        $id_album = (int)($_POST['id_album'] ?? 0);
        if (!$id_album) jsonOut(false, 'ID inválido.');

        $del = $db->prepare("
            DELETE FROM _album
            WHERE id_album = ? AND id_users = ? AND status_album = 'draft'
        ");
        $del->execute([$id_album, $id_users]);

        if ($del->rowCount() === 0) {
            jsonOut(false, 'Rascunho não encontrado ou já não é rascunho.');
        }

        // Eliminar faixas associadas
        $db->prepare("DELETE FROM _track WHERE id_album = ?")->execute([$id_album]);

        logActivity($id_users, 'draft_deleted', "Rascunho #{$id_album} eliminado.", 'album', $id_album);
        jsonOut(true, 'Rascunho eliminado com sucesso.');

    default:
        jsonOut(false, 'Acção desconhecida.');
}
