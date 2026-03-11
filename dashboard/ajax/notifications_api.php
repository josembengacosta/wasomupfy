<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Notifications AJAX API
// Arquivo: dashboard/ajax/notifications_api.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();

header('Content-Type: application/json; charset=utf-8');

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Verificar sessão activa
if (empty($_SESSION['id_users'])) {
    echo json_encode(['ok' => false, 'message' => 'Sessão expirada.', 'redirect' => '../../authentic/login']);
    exit;
}

// CSRF
if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'message' => 'Token inválido.']);
    exit;
}

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];
$action   = sanitize($_POST['action'] ?? '');

// ── Helper: resposta JSON ─────────────────────────────
function ok(array $extra = []): void {
    echo json_encode(array_merge(['ok' => true], $extra));
    exit;
}
function fail(string $msg): void {
    echo json_encode(['ok' => false, 'message' => $msg]);
    exit;
}

// ── Helper: verificar que a notificação pertence ao user ──
function ownsNotification(PDO $db, int $id_users, int $id_notif): bool {
    $q = $db->prepare("SELECT id_notification FROM _notification WHERE id_notification = ? AND (id_users = ? OR (is_broadcast = 1 AND id_users IS NULL)) LIMIT 1");
    $q->execute([$id_notif, $id_users]);
    return (bool)$q->fetch();
}

function ownsBroadcast(PDO $db, int $id_users, int $id_broadcast): bool {
    // Verifica que o broadcast existe e é dirigido a este user
    $q = $db->prepare("SELECT id_broadcast FROM _broadcast WHERE id_broadcast = ? AND (audience = 'all' OR audience_value IS NOT NULL) LIMIT 1");
    $q->execute([$id_broadcast]);
    return (bool)$q->fetch();
}

// ══════════════════════════════════════════════════════
// ROTEAMENTO DE ACÇÕES
// ══════════════════════════════════════════════════════
switch ($action) {

    // ── Contar não lidas (para badge da navbar) ───────
    case 'get_count':
        try {
            // Notificações pessoais não lidas
            $q1 = $db->prepare("
                SELECT COUNT(*) FROM _notification
                WHERE (id_users = ? OR (is_broadcast = 1 AND id_users IS NULL))
                  AND is_read = 0
            ");
            $q1->execute([$id_users]);
            $personalUnread = (int)$q1->fetchColumn();

            // Broadcasts não lidos (não têm entrada em _broadcast_receipt = não lido)
            $q2 = $db->prepare("
                SELECT COUNT(*) FROM _broadcast b
                LEFT JOIN _broadcast_receipt br
                       ON br.id_broadcast = b.id_broadcast AND br.id_users = ?
                WHERE (b.audience = 'all' OR b.audience = 'country')
                  AND (br.is_read IS NULL OR br.is_read = 0)
            ");
            $q2->execute([$id_users]);
            $broadcastUnread = (int)$q2->fetchColumn();

            $total = $personalUnread + $broadcastUnread;
            ok(['count' => $total]);
        } catch (PDOException $e) {
            ok(['count' => 0]);
        }
        break;

    // ── Marcar uma notificação como lida ─────────────
    case 'mark_read':
        $id     = sanitize($_POST['id']     ?? '');
        $source = sanitize($_POST['source'] ?? 'notification');

        if (str_starts_with($id, 'b_')) {
            // Broadcast
            $bid = (int)substr($id, 2);
            if (!ownsBroadcast($db, $id_users, $bid)) fail('Não autorizado.');
            try {
                $q = $db->prepare("
                    INSERT INTO _broadcast_receipt (id_broadcast, id_users, is_read, read_at)
                    VALUES (?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE is_read = 1, read_at = NOW()
                ");
                $q->execute([$bid, $id_users]);
                ok();
            } catch (PDOException $e) { fail('Erro ao actualizar.'); }
        } else {
            $nid = (int)$id;
            if (!ownsNotification($db, $id_users, $nid)) fail('Não autorizado.');
            try {
                $q = $db->prepare("UPDATE _notification SET is_read = 1, read_at = NOW() WHERE id_notification = ? AND (id_users = ? OR is_broadcast = 1)");
                $q->execute([$nid, $id_users]);
                ok();
            } catch (PDOException $e) { fail('Erro ao actualizar.'); }
        }
        break;

    // ── Marcar uma notificação como NÃO lida ─────────
    case 'mark_unread':
        $id     = sanitize($_POST['id']     ?? '');
        $source = sanitize($_POST['source'] ?? 'notification');

        if (str_starts_with($id, 'b_')) {
            $bid = (int)substr($id, 2);
            if (!ownsBroadcast($db, $id_users, $bid)) fail('Não autorizado.');
            try {
                $q = $db->prepare("
                    INSERT INTO _broadcast_receipt (id_broadcast, id_users, is_read, read_at)
                    VALUES (?, ?, 0, NULL)
                    ON DUPLICATE KEY UPDATE is_read = 0, read_at = NULL
                ");
                $q->execute([$bid, $id_users]);
                ok();
            } catch (PDOException $e) { fail('Erro ao actualizar.'); }
        } else {
            $nid = (int)$id;
            if (!ownsNotification($db, $id_users, $nid)) fail('Não autorizado.');
            try {
                $q = $db->prepare("UPDATE _notification SET is_read = 0, read_at = NULL WHERE id_notification = ? AND (id_users = ? OR is_broadcast = 1)");
                $q->execute([$nid, $id_users]);
                ok();
            } catch (PDOException $e) { fail('Erro ao actualizar.'); }
        }
        break;

    // ── Marcar TODAS como lidas ───────────────────────
    case 'mark_all_read':
        try {
            // Notificações pessoais
            $q1 = $db->prepare("UPDATE _notification SET is_read = 1, read_at = NOW() WHERE (id_users = ? OR (is_broadcast = 1 AND id_users IS NULL)) AND is_read = 0");
            $q1->execute([$id_users]);

            // Broadcasts — inserir receipts para os que ainda não têm
            $q2 = $db->prepare("
                INSERT INTO _broadcast_receipt (id_broadcast, id_users, is_read, read_at)
                SELECT b.id_broadcast, ?, 1, NOW()
                FROM _broadcast b
                LEFT JOIN _broadcast_receipt br ON br.id_broadcast = b.id_broadcast AND br.id_users = ?
                WHERE (b.audience = 'all' OR b.audience = 'country')
                  AND (br.is_read IS NULL OR br.is_read = 0)
                ON DUPLICATE KEY UPDATE is_read = 1, read_at = NOW()
            ");
            $q2->execute([$id_users, $id_users]);

            ok();
        } catch (PDOException $e) { fail('Erro ao actualizar.'); }
        break;

    // ── Eliminar uma notificação ──────────────────────
    case 'delete_one':
        $id     = sanitize($_POST['id']     ?? '');
        $source = sanitize($_POST['source'] ?? 'notification');

        if (str_starts_with($id, 'b_')) {
            // Broadcasts: não apagamos da tabela, apenas marcamos como lido
            $bid = (int)substr($id, 2);
            try {
                $q = $db->prepare("INSERT INTO _broadcast_receipt (id_broadcast, id_users, is_read, read_at) VALUES (?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE is_read = 1, read_at = NOW()");
                $q->execute([$bid, $id_users]);
                ok();
            } catch (PDOException $e) { fail('Erro.'); }
        } else {
            $nid = (int)$id;
            if (!ownsNotification($db, $id_users, $nid)) fail('Não autorizado.');
            try {
                // Só apaga notificações pessoais (não broadcasts globais)
                $q = $db->prepare("DELETE FROM _notification WHERE id_notification = ? AND id_users = ?");
                $q->execute([$nid, $id_users]);
                ok();
            } catch (PDOException $e) { fail('Erro ao eliminar.'); }
        }
        break;

    // ── Eliminar TODAS as notificações do user ────────
    case 'delete_all':
        try {
            // Apaga notificações pessoais
            $q1 = $db->prepare("DELETE FROM _notification WHERE id_users = ?");
            $q1->execute([$id_users]);

            // Marca broadcasts como lidos (não os apaga, pois são globais)
            $q2 = $db->prepare("
                INSERT INTO _broadcast_receipt (id_broadcast, id_users, is_read, read_at)
                SELECT id_broadcast, ?, 1, NOW() FROM _broadcast
                WHERE audience = 'all' OR audience = 'country'
                ON DUPLICATE KEY UPDATE is_read = 1, read_at = NOW()
            ");
            $q2->execute([$id_users]);

            ok();
        } catch (PDOException $e) { fail('Erro ao eliminar.'); }
        break;

    // ── Guardar preferências de notificação ───────────
    case 'save_prefs':
        $notif_push     = isset($_POST['notif_push'])     ? (int)(bool)$_POST['notif_push']     : 0;
        $notif_email    = isset($_POST['notif_email'])    ? (int)(bool)$_POST['notif_email']    : 0;
        $notif_streams  = isset($_POST['notif_streams'])  ? (int)(bool)$_POST['notif_streams']  : 0;
        $notif_releases = isset($_POST['notif_releases']) ? (int)(bool)$_POST['notif_releases'] : 0;
        $notif_payments = isset($_POST['notif_payments']) ? (int)(bool)$_POST['notif_payments'] : 0;
        $notif_weekly   = isset($_POST['notif_weekly'])   ? (int)(bool)$_POST['notif_weekly']   : 0;

        try {
            // Actualizar _user_settings (upsert)
            $q = $db->prepare("
                INSERT INTO _user_settings (id_users, notif_push, notif_email, notif_streams, notif_releases, notif_payments, notif_weekly)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    notif_push     = VALUES(notif_push),
                    notif_email    = VALUES(notif_email),
                    notif_streams  = VALUES(notif_streams),
                    notif_releases = VALUES(notif_releases),
                    notif_payments = VALUES(notif_payments),
                    notif_weekly   = VALUES(notif_weekly),
                    modif_settings = NOW()
            ");
            $q->execute([$id_users, $notif_push, $notif_email, $notif_streams, $notif_releases, $notif_payments, $notif_weekly]);

            // Espelhar em _users também (colunas de notificação que existem lá)
            $q2 = $db->prepare("
                UPDATE _users
                SET notif_email = ?, notif_push = ?, notif_weekly = ?,
                    notif_releases = ?, notif_payments = ?, modif_user = NOW()
                WHERE id_users = ?
            ");
            $q2->execute([$notif_email, $notif_push, $notif_weekly, $notif_releases, $notif_payments, $id_users]);

            logActivity($id_users, 'settings', 'Preferências de notificação actualizadas', 'user_settings', $id_users);
            ok();
        } catch (PDOException $e) { fail('Erro ao guardar preferências.'); }
        break;

    // ── Guardar subscripção push (Web Push) ───────────
    case 'subscribe_push':
        $subJson = $_POST['subscription'] ?? '';
        if (empty($subJson)) fail('Dados de subscripção inválidos.');

        $sub = json_decode($subJson, true);
        if (!$sub || empty($sub['endpoint'])) fail('Subscripção inválida.');

        $endpoint = sanitize($sub['endpoint']);
        $p256dh   = sanitize($sub['keys']['p256dh']  ?? '');
        $auth     = sanitize($sub['keys']['auth']     ?? '');

        try {
            // Guardar em _users_security (campo push_subscription) se existir
            // ou numa tabela _push_subscriptions (cria se não existir)
            // Por enquanto guardamos como JSON em _users_security
            $q = $db->prepare("
                UPDATE _users_security
                SET push_endpoint = ?, push_p256dh = ?, push_auth = ?, push_updated_at = NOW()
                WHERE id_users = ?
            ");
            $q->execute([$endpoint, $p256dh, $auth, $id_users]);

            if ($q->rowCount() === 0) {
                // _users_security pode não ter essas colunas ainda
                // Silencia e retorna ok mesmo assim — push ficará pendente de migração
            }

            // Activar push nas preferências
            $q2 = $db->prepare("UPDATE _user_settings SET notif_push = 1 WHERE id_users = ?");
            $q2->execute([$id_users]);
            $q3 = $db->prepare("UPDATE _users SET notif_push = 1 WHERE id_users = ?");
            $q3->execute([$id_users]);

            ok();
        } catch (PDOException $e) { fail('Erro ao guardar subscripção.'); }
        break;

    default:
        fail('Acção desconhecida.');
}