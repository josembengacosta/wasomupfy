<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Verificar Canais (atalho para pendentes)
// Arquivo: wu-panel-2026/pages/integration/verify-channel.php
// Rota:    wu-panel-2026/integration/verify-channel
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'music.view');

header('Location: ' . APP_URL . '/' . ADMIN_PATH . '/integration/verify?status=pending');
exit;