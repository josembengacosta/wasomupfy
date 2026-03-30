<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Ping de Presença do Utilizador
// Arquivo: dashboard/ajax/presence_ping.php
// Rota:    dashboard/ajax/presence_ping (POST)
//
// Chamado pelo JS do dashboard a cada 2 minutos.
// Actualiza _user_presence com a actividade actual.
// Responde JSON mínimo — é um fire-and-forget do lado do cliente.
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
require_once __DIR__ . '/../include/platform.php';
startSecureSession();

header('Content-Type: application/json; charset=utf-8');
if (ob_get_level()) ob_clean();

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]); exit;
}

// Verificar se está logado
if (empty($_SESSION['id_users'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'expired' => true]); exit;
}

$id_users     = (int)$_SESSION['id_users'];
$page         = substr(trim($_POST['page']          ?? ''), 0, 255);
$activity     = substr(trim($_POST['activity_type'] ?? ''), 0, 100);
$online_status= in_array($_POST['status'] ?? '', ['online','away','busy','invisible','offline'], true)
                ? $_POST['status'] : 'online';

if (!wuf_validate_dashboard_session($id_users, false)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'expired' => true]); exit;
}

// Actualizar presença
updateUserPresence($id_users, $page, $activity, $online_status);

echo json_encode(['ok' => true, 'ts' => time()]);
exit;
