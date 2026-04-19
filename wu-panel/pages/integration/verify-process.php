<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Processador de Acções (Verificar/Rejeitar/Remover/Exportar)
// Arquivo: wu-panel/pages/integration/verify-process.php
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'music.view');

function jOut(bool $ok, string $msg, array $extra = []): never
{
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jOut(false, 'Método não permitido.');
if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    jOut(false, 'Sessão expirada. Recarregue a página.');
}

$action = trim($_POST['action'] ?? '');
$id_youtube = (int)($_POST['id_youtube'] ?? 0);

$db = getDB();

// ── Buscar canal (se necessário) ───────────────────────────────
function getChannel(PDO $db, int $id): ?array
{
    $stmt = $db->prepare("
        SELECT yc.*, u.email_user, u.first_name, u.second_name, u.id_users, a.stage_name
        FROM _youtube_channel yc
        LEFT JOIN _users u ON u.id_users = yc.id_users
        LEFT JOIN _artist a ON a.id_artist = yc.id_artist
        WHERE yc.id_youtube = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ── Notificações e email (reutilizar funções existentes) ──────
function notifyUserChannel(PDO $db, int $id_users, int $admin_id, string $type, string $title, string $body): void
{
    $db->prepare("INSERT INTO _notification (id_users, id_employees, type, title, body) VALUES (?,?,?,?,?)")
        ->execute([$id_users, $admin_id, $type, $title, $body]);
}
function sendChannelEmail(string $to, string $name, string $channel_name, string $status, string $reason = ''): void
{
    // mesma implementação da versão anterior (WasomMailer)
    $subject = 'Canal YouTube - ' . ($status === 'verified' ? 'Verificado ✅' : 'Rejeitado ❌');
    $color = $status === 'verified' ? '#22c55e' : '#ef4444';
    $status_text = $status === 'verified' ? 'Verificado' : 'Rejeitado';
    $reason_html = $reason ? "<p><strong>Motivo:</strong> " . htmlspecialchars($reason) . "</p>" : '';
    $body = "<div style='font-family:Arial,sans-serif;max-width:560px;margin:auto'><div style='background:linear-gradient(135deg,#0f0f1a,#1a1a2e);padding:24px 32px;border-radius:12px 12px 0 0'><h2 style='color:#fff;margin:0'>Wasom Upfy</h2></div><div style='background:#fff;padding:32px;border:1px solid #eee;border-top:none;border-radius:0 0 12px 12px'><h3 style='color:$color'>Canal $status_text</h3><p>Olá <strong>$name</strong>,</p><p>O teu canal YouTube <strong>$channel_name</strong> foi $status_text.</p>$reason_html<p style='font-size:.85rem;color:#666'>Podes ver o estado actual no teu painel.</p><hr><small>Wasom Upfy — Não respondas a este e-mail.</small></div></div>";
    $mailer_path = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';
    if (file_exists($mailer_path)) {
        require_once $mailer_path;
        try {
            $wm = new \Wasom\Mailer();
            $wm->host = MAIL_HOST;
            $wm->port = MAIL_PORT;
            $wm->secure = defined('MAIL_SECURE') ? MAIL_SECURE : 'tls';
            $wm->username = MAIL_USER;
            $wm->password = MAIL_PASS;
            $wm->setFrom(MAIL_FROM, MAIL_FROM_NAME)
                ->addAddress($to, $name)
                ->setSubject($subject)
                ->setBody($body, strip_tags($body));
            $wm->send();
        } catch (Exception $e) {
            error_log('[CHANNEL_EMAIL] ' . $e->getMessage());
        }
    }
}

// ══════════════════════════════════════════════════════════════
// VERIFICAR (aprovar) canal
// ══════════════════════════════════════════════════════════════
if ($action === 'verify') {
    requirePermission($admin_id, 'music.approve');
    if (!$id_youtube) jOut(false, 'ID inválido.');
    $channel = getChannel($db, $id_youtube);
    if (!$channel) jOut(false, 'Canal não encontrado.');
    if ($channel['status_youtube'] !== 'pending') jOut(false, 'Apenas canais pendentes podem ser verificados.');
    try {
        $db->prepare("UPDATE _youtube_channel SET status_youtube = 'verified', verified_at = NOW() WHERE id_youtube = ?")->execute([$id_youtube]);
        notifyUserChannel($db, $channel['id_users'], $admin_id, 'success', 'Canal YouTube Verificado ✅', 'O teu canal "' . $channel['channel_name'] . '" foi verificado com sucesso.');
        sendChannelEmail($channel['email_user'], $channel['first_name'] . ' ' . $channel['second_name'], $channel['channel_name'], 'verified');
        logAudit($admin_id, $channel['id_users'], 'youtube.verified', '_youtube_channel', $id_youtube, ['status' => 'pending'], ['status' => 'verified']);
        jOut(true, 'Canal verificado com sucesso.');
    } catch (Exception $e) {
        jOut(false, 'Erro ao verificar.');
    }
}

// ══════════════════════════════════════════════════════════════
// REJEITAR canal
// ══════════════════════════════════════════════════════════════
if ($action === 'reject') {
    requirePermission($admin_id, 'music.approve');
    $reason = trim($_POST['reason'] ?? '');
    if (!$id_youtube) jOut(false, 'ID inválido.');
    $channel = getChannel($db, $id_youtube);
    if (!$channel) jOut(false, 'Canal não encontrado.');
    if ($channel['status_youtube'] !== 'pending') jOut(false, 'Apenas canais pendentes podem ser rejeitados.');
    try {
        $db->prepare("UPDATE _youtube_channel SET status_youtube = 'rejected' WHERE id_youtube = ?")->execute([$id_youtube]);
        $msg = 'O teu canal "' . $channel['channel_name'] . '" foi rejeitado.' . ($reason ? ' Motivo: ' . $reason : '');
        notifyUserChannel($db, $channel['id_users'], $admin_id, 'warning', 'Canal YouTube Rejeitado ❌', $msg);
        sendChannelEmail($channel['email_user'], $channel['first_name'] . ' ' . $channel['second_name'], $channel['channel_name'], 'rejected', $reason);
        logAudit($admin_id, $channel['id_users'], 'youtube.rejected', '_youtube_channel', $id_youtube, ['status' => 'pending'], ['status' => 'rejected', 'reason' => $reason]);
        jOut(true, 'Canal rejeitado.');
    } catch (Exception $e) {
        jOut(false, 'Erro ao rejeitar.');
    }
}

// ══════════════════════════════════════════════════════════════
// REMOVER canal (DELETE físico com validação de senha)
// ══════════════════════════════════════════════════════════════
if ($action === 'remove') {
    requirePermission($admin_id, 'music.edit');
    $password = $_POST['admin_password'] ?? '';
    if (!$password) jOut(false, 'Senha obrigatória.');
    // Verificar senha do admin
    $stmt = $db->prepare("SELECT password_employees FROM _employees WHERE id_employees = ?");
    $stmt->execute([$admin_id]);
    $hash = $stmt->fetchColumn();
    if (!$hash || !password_verify($password, $hash)) jOut(false, 'Senha incorrecta.');
    if (!$id_youtube) jOut(false, 'ID inválido.');
    $channel = getChannel($db, $id_youtube);
    if (!$channel) jOut(false, 'Canal não encontrado.');
    try {
        $db->prepare("DELETE FROM _youtube_channel WHERE id_youtube = ?")->execute([$id_youtube]);
        notifyUserChannel($db, $channel['id_users'], $admin_id, 'warning', 'Canal YouTube Removido 🗑️', 'O teu canal "' . $channel['channel_name'] . '" foi removido da plataforma.');
        logAudit($admin_id, $channel['id_users'], 'youtube.removed', '_youtube_channel', $id_youtube, null, ['reason' => 'admin_removed']);
        jOut(true, 'Canal removido permanentemente.');
    } catch (Exception $e) {
        jOut(false, 'Erro ao remover.');
    }
}

jOut(false, 'Acção desconhecida.');
