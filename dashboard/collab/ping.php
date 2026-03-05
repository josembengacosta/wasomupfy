<?php
// WASOM UPFY v2.0 — Ping colaborador (manter online)
// Arquivo: dashboard/collab/ping.php
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();

if (!empty($_SESSION['collab_id'])) {
    try {
        getDB()->prepare("UPDATE _collaborators SET last_seen_at = NOW() WHERE id_collab = ?")
               ->execute([$_SESSION['collab_id']]);
    } catch (Exception $e) {}
}
http_response_code(204);
exit;