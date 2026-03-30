<?php
// ═══════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Process (AJAX) para relatórios
// Arquivo: admin/pages/analytics/process-reports.php
// Rota:    admin/analytics/process-reports  (POST only)
// ═══════════════════════════════════════════════════════════════════════════
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.view');

$project_root = dirname(__DIR__, 3); // raiz do projecto wasomupfy/
$user_download_base = APP_URL . '/' . APP_URL_PANEL . '/ajax/report_download.php';

function jsonOut(bool $ok, string $msg, array $extra = []): never {
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
    exit;
}

function deleteReportFilesAndRows(PDO $db, string $project_root, array $rows): int
{
    $deleted = 0;

    foreach ($rows as $row) {
        $id = (int)($row['id_history'] ?? 0);
        if ($id <= 0) {
            continue;
        }

        $filePath = (string)($row['file_path'] ?? '');
        if ($filePath !== '') {
            $abs = $project_root . $filePath;
            if (is_file($abs)) {
                @unlink($abs);
            }
        }

        $db->prepare("DELETE FROM _report_history WHERE id_history = ?")->execute([$id]);
        $deleted++;
    }

    return $deleted;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(false, 'Método não permitido.');
if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    jsonOut(false, 'Sessão expirada. Recarrega a página.');
}

$action = trim($_POST['action'] ?? '');

// ══════════════════════════════════════════════════════════════════════════
// GUARDAR RELATÓRIO NO SERVIDOR + BD
// O JS envia o conteúdo do ficheiro em base64 após gerá-lo client-side
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'save_report') {
    requirePermission($admin_id, 'analytics.view');

    $user_id      = (int)($_POST['user_id']     ?? 0);
    $format       = trim($_POST['format']        ?? 'xlsx');
    $name_report  = trim($_POST['name_report']   ?? '');
    $parameters   = trim($_POST['parameters']    ?? '{}');
    $file_b64     = $_POST['file_b64']            ?? '';   // conteúdo em base64
    $file_ext     = trim($_POST['file_ext']       ?? $format); // xlsx, csv, pdf
    $rows_count   = max(0, (int)($_POST['rows_count'] ?? 0));

    if (!$user_id) jsonOut(false, 'Utilizador inválido.');
    if (empty($name_report)) $name_report = 'Relatório ' . date('d/m/Y H:i');

    $allowed_formats = ['pdf', 'excel'];
    $format = in_array(strtolower($format), $allowed_formats, true) ? strtolower($format) : 'excel';

    // Validar utilizador
    $uchk = $db->prepare("SELECT id_users FROM _users WHERE id_users = ? LIMIT 1");
    $uchk->execute([$user_id]);
    if (!$uchk->fetch()) jsonOut(false, 'Utilizador não encontrado.');

    // Validar JSON dos parâmetros
    json_decode($parameters, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $parameters = '{}';
    }

    $file_path_db   = null;
    $file_size_kb   = null;
    $status         = 'success';
    $error_msg      = null;

    // Guardar ficheiro no servidor
    if (empty($file_b64)) {
        $status    = 'error';
        $error_msg = 'O ficheiro gerado não foi recebido pelo servidor.';
    } else {
        $allowed_exts = ['xlsx', 'pdf'];
        $file_ext = in_array(strtolower($file_ext), $allowed_exts) ? strtolower($file_ext) : 'xlsx';

        $decoded = base64_decode($file_b64, true);
        if ($decoded === false || strlen($decoded) < 10) {
            $status    = 'error';
            $error_msg = 'Conteúdo do ficheiro inválido.';
        } else {
            // Directório de destino
            $rel_dir   = '/assets/comprovantes/reports/' . date('Y/m');
            $abs_dir   = $project_root . $rel_dir;
            if (!is_dir($abs_dir)) mkdir($abs_dir, 0755, true);

            $safe_name = preg_replace('/[^a-z0-9_\-]/i', '_', substr($name_report, 0, 50));
            $filename  = 'rel_u' . $user_id . '_' . date('Ymd_His') . '_' . $safe_name . '.' . $file_ext;
            $abs_path  = $abs_dir . '/' . $filename;
            $rel_path  = $rel_dir . '/' . $filename;

            if (file_put_contents($abs_path, $decoded) === false) {
                $status    = 'error';
                $error_msg = 'Não foi possível guardar o ficheiro no servidor.';
            } else {
                $file_path_db = $rel_path;
                $file_size_kb = (int)ceil(strlen($decoded) / 1024);
            }
        }
    }

    // Inserir no histórico
    $stmt = $db->prepare("
        INSERT INTO _report_history
            (id_employees, id_users, name_report, report_type, parameters,
             format, visualization, file_path, file_size_kb, rows_count,
             status, error_message, generated_at, save_dashboard)
        VALUES (?, ?, ?, 'performance', ?, ?, 'table', ?, ?, ?, ?, ?, NOW(), 1)
    ");
    $stmt->execute([
        $admin_id, $user_id, $name_report, $parameters,
        $format, $file_path_db, $file_size_kb, $rows_count ?: null,
        $status, $error_msg,
    ]);
    $new_id = (int)$db->lastInsertId();

    logAudit($admin_id, $user_id, 'report.generated', '_report_history', $new_id,
        null, ['format' => $format, 'name' => $name_report]);

    if ($status === 'error') {
        jsonOut(false, $error_msg ?? 'Erro ao guardar relatório.', ['id' => $new_id]);
    }

    try {
        $title = 'Relatório disponível para download';
        $body  = 'O relatório "' . $name_report . '" foi gerado em formato ' . strtoupper($file_ext)
            . ' e já está disponível no teu painel.';
        $action_url = $user_download_base . '?id=' . $new_id;

        $db->prepare("
            INSERT INTO _notification
                (id_users, id_employees, type, title, body, action_url, is_read, is_broadcast)
            VALUES
                (?, ?, 'system', ?, ?, ?, 0, 0)
        ")->execute([
            $user_id,
            $admin_id,
            $title,
            $body,
            $action_url,
        ]);
    } catch (Throwable $e) {
        error_log('[report notification] ' . $e->getMessage());
    }

    jsonOut(true, 'Relatório guardado com sucesso.', [
        'id'        => $new_id,
        'file_url'  => $file_path_db ? APP_URL . $file_path_db : null,
        'file_name' => $filename ?? null,
    ]);
}

// ══════════════════════════════════════════════════════════════════════════
// OBTER URL DO RELATÓRIO (para visualização em nova janela)
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'get_report_file') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("
        SELECT file_path, format, name_report
        FROM _report_history
        WHERE id_history = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) jsonOut(false, 'Relatório não disponível.');
    if (!$row['file_path']) jsonOut(false, 'Este relatório não tem ficheiro associado.');

    $abs = $project_root . $row['file_path'];
    if (!file_exists($abs)) jsonOut(false, 'Ficheiro não encontrado no servidor.');

    jsonOut(true, '', [
        'url'    => APP_URL . $row['file_path'],
        'format' => $row['format'],
        'name'   => $row['name_report'],
    ]);
}

// ══════════════════════════════════════════════════════════════════════════
// DOWNLOAD (forçar download directo)
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'download_report') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("
        SELECT file_path, format, name_report
        FROM _report_history
        WHERE id_history = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row || !$row['file_path']) jsonOut(false, 'Ficheiro não disponível.');

    $abs = $project_root . $row['file_path'];
    if (!file_exists($abs)) jsonOut(false, 'Ficheiro não existe no servidor.');

    // Marcar como downloaded
    $db->prepare("UPDATE _report_history SET downloaded=1, downloaded_at=NOW() WHERE id_history=?")
       ->execute([$id]);

    jsonOut(true, '', ['url' => APP_URL . $row['file_path']]);
}

// ══════════════════════════════════════════════════════════════════════════
// EXCLUIR RELATÓRIO
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'delete_report') {
    requirePermission($admin_id, 'analytics.view');

    $id = (int)($_POST['id'] ?? 0);
    $is_super = ($admin_role === 'super_admin') ? 1 : 0;

    $stmt = $db->prepare("
        SELECT id_history, file_path, id_employees
        FROM _report_history
        WHERE id_history = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) jsonOut(false, 'Relatório não encontrado.');

    // Só o criador ou super_admin pode excluir
    if ((int)$row['id_employees'] !== (int)$admin_id && !$is_super) {
        jsonOut(false, 'Sem permissão para excluir este relatório.');
    }

    // Apagar ficheiro físico se existir
    if ($row['file_path']) {
        $abs = $project_root . $row['file_path'];
        if (file_exists($abs)) @unlink($abs);
    }

    $db->prepare("DELETE FROM _report_history WHERE id_history=?")->execute([$id]);
    logAudit($admin_id, null, 'report.deleted', '_report_history', $id, null, null);

    jsonOut(true, 'Relatório eliminado com sucesso.');
}

if ($action === 'delete_selected_reports') {
    requirePermission($admin_id, 'analytics.view');

    $idsJson = $_POST['ids_json'] ?? '[]';
    $ids = json_decode((string)$idsJson, true);
    if (!is_array($ids) || empty($ids)) {
        jsonOut(false, 'Nenhum relatório seleccionado.');
    }

    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));
    if (empty($ids)) {
        jsonOut(false, 'Nenhum relatório válido foi seleccionado.');
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = $ids;

    $sql = "
        SELECT id_history, file_path, id_employees
        FROM _report_history
        WHERE id_history IN ($placeholders)
    ";

    if ($admin_role !== 'super_admin') {
        $sql .= " AND id_employees = ?";
        $params[] = $admin_id;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        jsonOut(false, 'Nenhum relatório elegível para exclusão.');
    }

    $deleted = deleteReportFilesAndRows($db, $project_root, $rows);
    logAudit($admin_id, null, 'report.bulk_deleted', '_report_history', null, null, [
        'count' => $deleted,
        'ids'   => array_column($rows, 'id_history'),
    ]);

    jsonOut(true, $deleted . ' relatório(s) eliminado(s) com sucesso.', ['deleted' => $deleted]);
}

if ($action === 'delete_all_reports') {
    requirePermission($admin_id, 'analytics.view');

    $sql = "
        SELECT id_history, file_path, id_employees
        FROM _report_history
        WHERE id_users IS NOT NULL
          AND save_dashboard = 1
    ";
    $params = [];

    if ($admin_role !== 'super_admin') {
        $sql .= " AND id_employees = ?";
        $params[] = $admin_id;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        jsonOut(false, 'Nenhum relatório disponível para limpeza.');
    }

    $deleted = deleteReportFilesAndRows($db, $project_root, $rows);
    logAudit($admin_id, null, 'report.all_deleted', '_report_history', null, null, [
        'count' => $deleted,
    ]);

    jsonOut(true, $deleted . ' relatório(s) removido(s) do histórico.', ['deleted' => $deleted]);
}

// ══════════════════════════════════════════════════════════════════════════
// REENVIAR (reload da página — reload limpa o CSRF)
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'refresh_csrf') {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    jsonOut(true, '', ['csrf' => $_SESSION['admin_csrf_token']]);
}

jsonOut(false, 'Acção desconhecida.');
