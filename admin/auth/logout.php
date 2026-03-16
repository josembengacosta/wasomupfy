<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Logout Admin
// Arquivo: admin/logout.php         ← raiz do admin
// .htaccess: ^admin/logout/?$ → admin/logout.php
// Aceita GET e POST — logout deve ser sempre
// iniciado por link semântico (<a href>), nunca JS.
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions_admin.php';
startAdminSession();

// Se não está autenticado, redirecionar directamente
// sem tentar fazer logout (evita erros desnecessários)
if (!isAdminLoggedIn()) {
    adminRedirect('/admin/login');
}

// ── Logout completo ──────────────────────────
// logoutAdmin() trata de:
// - apagar o cookie remember me
// - registar em _audit_log
// - destruir a sessão completamente
logoutAdmin();

// ── Redirecionar para login com mensagem ──────
adminRedirect('/admin/login', ['msg' => 'logout']);