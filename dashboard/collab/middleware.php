<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Middleware Universal de Autenticação
// Arquivo: dashboard/collab/middleware.php
//
// COMO USAR — substitui nas páginas existentes:
//   require_once __DIR__ . '/../../authentic/include/functions.php';
//   startSecureSession();
//   checkRememberMe();
//   requireLogin();
//   $user = getUserById((int)$_SESSION['id_users']);
//   if (!$user) { session_destroy(); redirect('/login'); }
//
// POR:
//   require_once __DIR__ . '/../../authentic/include/functions.php';
//   require_once __DIR__ . '/../../dashboard/collab/middleware.php';
//
// O middleware expõe as mesmas variáveis que as páginas já usam:
//   $user         → dados do proprietário da conta
//   $id_users     → ID do proprietário (para todas as queries)
//   $first_name   → nome para mostrar na navbar
//   $user_name    → username para mostrar
//   $db           → PDO connection
//   $is_collab    → bool — true se sessão de colaborador
//   $collab       → array com dados do colaborador (ou null)
//   $collab_role  → 'admin'|'editor'|'analyst'|'support' (ou null)
// ══════════════════════════════════════════════════════════════

startSecureSession();

$db        = getDB();
$is_collab = false;
$collab    = null;
$collab_role = null;

// ────────────────────────────────────────────
// CASO 1: Sessão de colaborador
// ────────────────────────────────────────────
if (!empty($_SESSION['collab_id']) && !empty($_SESSION['collab_id_users'])) {

    // Requer mudança de senha pendente?
    if (!empty($_SESSION['collab_must_change'])) {
        header('Location: ' . rtrim(APP_URL, '/') . '/ ' . APP_URL_PANEL . '/account/collab-login');
        exit;
    }

    // Verificar colaborador activo na BD
    $cs = $db->prepare("
        SELECT * FROM _collaborators
        WHERE id_collab = ? AND id_users = ? AND status_collab = 'active'
        LIMIT 1
    ");
    $cs->execute([$_SESSION['collab_id'], $_SESSION['collab_id_users']]);
    $collab = $cs->fetch();

    if (!$collab) {
        // Conta bloqueada/removida entretanto
        session_destroy();
        header('Location: ' . rtrim(APP_URL, '/') . '/ ' . APP_URL_PANEL . '/account/collab-login?error=access');
        exit;
    }

    // Actualizar last_seen
    $db->prepare("UPDATE _collaborators SET last_seen_at = NOW() WHERE id_collab = ?")
        ->execute([$collab['id_collab']]);

    // Carregar dados do proprietário (para as queries das páginas usarem)
    $user = getUserById((int)$_SESSION['collab_id_users']);
    if (!$user) {
        session_destroy();
        header('Location: ' . rtrim(APP_URL, '/') . '/ ' . APP_URL_PANEL . '/account/collab-login');
        exit;
    }

    // Expor variáveis compatíveis com as páginas existentes
    $is_collab   = true;
    $collab_role = $collab['role_collab'];
    $id_users    = (int)$user['id_users'];
    $first_name  = htmlspecialchars($collab['first_name'] . ' ' . ($collab['second_name'] ?? ''));
    $user_name   = htmlspecialchars($collab['user_collab']);

    // ── Verificar permissão para a página actual ──
    // Cada página que inclui este middleware deve definir $required_permission
    // antes do include. Ex: $required_permission = 'finances';
    // Permissões por role:
    $permissions = [
        'admin'   => ['releases', 'releases_edit', 'artists', 'finances', 'stats'],
        'editor'  => ['releases', 'releases_edit', 'artists', 'stats'],
        'analyst' => ['releases', 'finances', 'stats'],
        'support' => ['releases'],
    ];
    $allowed = $permissions[$collab_role] ?? [];

    if (!empty($required_permission) && !in_array($required_permission, $allowed)) {
        // Acesso bloqueado — mostra página de erro 403 inline
        $blocked_page_title = 'Acesso negado';
        require __DIR__ . '/blocked.php';
        exit;
    }

    // ────────────────────────────────────────────
    // CASO 2: Sessão normal de utilizador
    // ────────────────────────────────────────────
} elseif (!empty($_SESSION['id_users'])) {

    checkRememberMe();

    $user = getUserById((int)$_SESSION['id_users']);
    if (!$user) {
        session_destroy();
        redirect('/login', ['error' => 'csrf']);
    }

    $id_users   = (int)$user['id_users'];
    $first_name = htmlspecialchars($user['first_name']);
    $user_name  = htmlspecialchars($user['user_name'] ?? '');

    // ────────────────────────────────────────────
    // CASO 3: Sem sessão — redirecionar para login
    // ────────────────────────────────────────────
} else {
    $current = urlencode($_SERVER['REQUEST_URI'] ?? '');
    header('Location: ' . rtrim(APP_URL, '/') . '/login?redirect=' . $current);
    exit;
}

// ── Helper: retorna true se o colaborador tem permissão ──
function collabCan(string $permission): bool
{
    global $is_collab, $collab_role;
    if (!$is_collab) return true; // utilizador normal tem tudo
    $permissions = [
        'admin'   => ['releases', 'releases_edit', 'artists', 'finances', 'stats'],
        'editor'  => ['releases', 'releases_edit', 'artists', 'stats'],
        'analyst' => ['releases', 'finances', 'stats'],
        'support' => ['releases'],
    ];
    return in_array($permission, $permissions[$collab_role] ?? []);
}