<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Acções de Lançamentos (AJAX) + EDIT/DELETE
// Arquivo: wu-panel/pages/distribution/view-process.php
// Rota:    wu-panel/releases/view-process (POST only)
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

// ── Método ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jOut(false, 'Método não permitido.');
}

// ── CSRF ──────────────────────────────────────────────────────
if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    jOut(false, 'Sessão expirada. Recarrega a página.');
}

$action   = trim($_POST['action']   ?? '');
$id_album = (int)($_POST['id_album'] ?? 0);

if ($id_album <= 0) {
    jOut(false, 'ID de álbum inválido.');
}

// ── Buscar álbum ──────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT al.*,
           COALESCE(ar.stage_name, u.name_artist_band, u.first_name) AS artist_name,
           CONCAT(u.first_name,' ',COALESCE(u.second_name,'')) AS user_fullname,
           u.email_user,
           up.id_user_plan, up.releases_used, up.releases_limit
    FROM _album al
    LEFT JOIN _artist ar ON ar.id_artist = al.id_artist
    LEFT JOIN _users u ON u.id_users = al.id_users
    LEFT JOIN _user_plan up ON up.id_users = al.id_users AND up.status_plan = 'active'
    WHERE al.id_album = ?
");
$stmt->execute([$id_album]);
$album = $stmt->fetch();

if (!$album) {
    jOut(false, 'Álbum não encontrado.');
}

// ── Helpers ───────────────────────────────────────────────────
function notifyUser(PDO $db, int $id_users, int $id_emp, string $type, string $title, string $body, string $url = ''): void
{
    try {
        $db->prepare("
            INSERT INTO _notification (id_users, id_employees, type, title, body, action_url)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$id_users, $id_emp, $type, $title, $body, $url]);
    } catch (Exception $e) {
        error_log('[NOTIFY_ALBUM] ' . $e->getMessage());
    }
}

function sendAlbumEmail(string $to, string $subject, string $html_body): bool
{
    $mailer_path = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';
    if (!file_exists($mailer_path)) {
        error_log('[ALBUM_MAIL] WasomMailer não encontrado: ' . $mailer_path);
        return false;
    }
    if (!class_exists('\Wasom\Mailer')) {
        require_once $mailer_path;
    }
    try {
        $wm = new \Wasom\Mailer();
        $wm->host     = MAIL_HOST;
        $wm->port     = MAIL_PORT;
        $wm->secure   = defined('MAIL_SECURE') ? MAIL_SECURE : 'tls';
        $wm->username = MAIL_USER;
        $wm->password = MAIL_PASS;
        $wm->debug    = 0;
        $wm->setFrom(MAIL_FROM, MAIL_FROM_NAME)
            ->addAddress($to)
            ->setSubject($subject)
            ->setBody($html_body, strip_tags($html_body));
        $wm->send();
        return true;
    } catch (\Wasom\MailerException $e) {
        error_log('[ALBUM_MAIL] ' . $e->getMessage());
        return false;
    }
}

function albumEmailTemplate(string $title_section, string $color, string $icon_emoji, string $body_html): string
{
    return '
    <div style="font-family:\"Segoe UI\",Arial,sans-serif;max-width:560px;margin:auto">
      <div style="background:linear-gradient(135deg,#0f0f1a,#1a1a2e);padding:28px 32px;border-radius:12px 12px 0 0">
        <div style="display:flex;align-items:center;gap:12px">
          <div style="font-size:1.6rem">' . $icon_emoji . '</div>
          <div>
            <div style="font-size:.9rem;font-weight:800;color:#fff">Wasom Upfy</div>
            <div style="font-size:.65rem;color:' . $color . ';text-transform:uppercase;letter-spacing:1px;font-weight:700">Distribuição Musical</div>
          </div>
        </div>
      </div>
      <div style="background:#fff;padding:32px;border:1px solid #eee;border-top:none;border-radius:0 0 12px 12px">
        <h2 style="color:#1a1a2e;font-size:1.05rem;margin:0 0 6px;font-weight:800">' . $title_section . '</h2>
        ' . $body_html . '
        <hr style="border:none;border-top:1px solid #f0f0f0;margin:24px 0">
        <small style="color:#bbb">Wasom Upfy — Não respondas a este email.</small>
      </div>
    </div>';
}

function al_logAudit(PDO $db, int $admin_id, string $action, string $entity, int $entity_id, string $ip = '', $old = null, $new = null): void
{
    try {
        $db->prepare("
            INSERT INTO _audit_log (id_employees, action, entity, entity_id, ip_address, old_value, new_value)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $admin_id,
            $action,
            $entity,
            $entity_id,
            $ip,
            $old !== null ? json_encode($old) : null,
            $new !== null ? json_encode($new) : null
        ]);
    } catch (Exception $e) {
        error_log('[AUDIT_ALBUM] ' . $e->getMessage());
    }
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$dashboard_url = APP_URL . '/dashboard/releases';

// ════════════════════════════════════════════════════════════════════════════
// COLOCAR EM REVISÃO  (pending → under_review)
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'set_processing') {
    requirePermission($admin_id, 'music.approve');

    if ($album['status_album'] !== 'pending') {
        jOut(false, 'Este álbum não está no estado "Pendente".');
    }

    try {
        $db->prepare("
            UPDATE _album
            SET status_album='under_review', modif_album=NOW()
            WHERE id_album=?
        ")->execute([$id_album]);

        // Notificação
        notifyUser(
            $db,
            $album['id_users'],
            $admin_id,
            'info',
            'Álbum em análise 🎵',
            'O teu álbum "' . $album['title_album'] . '" está a ser analisado pela nossa equipa. Aguarda enquanto verificamos todos os detalhes.',
            $dashboard_url
        );

        // Email
        $html = albumEmailTemplate(
            'O teu álbum está a ser analisado',
            '#3b82f6',
            '🔍',
            '<p>Olá <strong>' . htmlspecialchars($album['user_fullname']) . '</strong>,</p>
             <p>O teu álbum <strong>"' . htmlspecialchars($album['title_album']) . '"</strong> foi recebido e está agora a ser analisado pela nossa equipa de curadoria.</p>
             <div style="background:#eff6ff;border-left:4px solid #3b82f6;padding:12px 16px;border-radius:0 8px 8px 0;margin:16px 0">
               <p style="margin:0;font-size:.85rem;color:#1e40af">⏱️ O processo de análise pode demorar até 3-5 dias úteis. Serás notificado assim que tivermos uma resposta.</p>
             </div>
             <p style="color:#555;font-size:.85rem">Enquanto isso, podes acompanhar o estado do teu lançamento no teu painel.</p>'
        );
        sendAlbumEmail($album['email_user'], 'O teu álbum está em análise — ' . APP_NAME, $html);

        al_logAudit(
            $db,
            $admin_id,
            'album.set_under_review',
            '_album',
            $id_album,
            $ip,
            ['status_album' => 'pending'],
            ['status_album' => 'under_review']
        );

        jOut(true, 'Álbum colocado em revisão. Utilizador notificado.');
    } catch (Exception $e) {
        error_log('[ALBUM SET_PROCESSING] ' . $e->getMessage());
        jOut(false, 'Erro ao actualizar estado. Tenta novamente.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// APROVAR  (under_review → approved)
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'approve') {
    requirePermission($admin_id, 'music.approve');

    if ($album['status_album'] !== 'under_review') {
        jOut(false, 'O álbum precisa de estar em revisão para ser aprovado.');
    }

    $upc       = trim($_POST['upc']       ?? '');
    $smartlink = trim($_POST['smartlink'] ?? '');

    // Validar UPC
    if (!preg_match('/^\d{13}$/', $upc)) {
        jOut(false, 'UPC inválido. Deve ter exactamente 13 dígitos numéricos (EAN-13).');
    }

    // Verificar UPC único (excluindo este álbum)
    $upc_check = $db->prepare("SELECT id_album FROM _album WHERE upc=? AND id_album!=?");
    $upc_check->execute([$upc, $id_album]);
    if ($upc_check->fetch()) {
        jOut(false, 'Este UPC já está atribuído a outro álbum.');
    }

    // Validar smartlink (se preenchido)
    if ($smartlink && !filter_var($smartlink, FILTER_VALIDATE_URL)) {
        jOut(false, 'O smartlink deve ser uma URL válida (ex: https://...).');
    }

    try {
        $db->beginTransaction();

        $db->prepare("
            UPDATE _album
            SET status_album='approved', upc=?, smartlink=NULLIF(?,''),
                approved_by=?, approved_at=NOW(), modif_album=NOW()
            WHERE id_album=?
        ")->execute([$upc, $smartlink ?: null, $admin_id, $id_album]);

        // Incrementar releases_used no user_plan
        if ($album['id_user_plan']) {
            $db->prepare("
                UPDATE _user_plan
                SET releases_used = releases_used + 1
                WHERE id_user_plan=?
            ")->execute([$album['id_user_plan']]);
        }

        $db->commit();

        // Notificação
        notifyUser(
            $db,
            $album['id_users'],
            $admin_id,
            'success',
            'Álbum aprovado! ✅',
            'O teu álbum "' . $album['title_album'] . '" foi aprovado e está a ser distribuído para mais de 150 plataformas digitais. UPC: ' . $upc,
            $dashboard_url
        );

        // Email
        $smartlink_html = $smartlink
            ? '<p><a href="' . htmlspecialchars($smartlink) . '" style="background:#FF0089;color:#fff;padding:8px 20px;border-radius:8px;text-decoration:none;font-weight:700;font-size:.85rem">Ver no Smartlink</a></p>'
            : '';
        $html = albumEmailTemplate(
            'O teu álbum foi aprovado! 🎉',
            '#22c55e',
            '✅',
            '<p>Olá <strong>' . htmlspecialchars($album['user_fullname']) . '</strong>,</p>
             <p>Excelente notícia! O teu álbum <strong>"' . htmlspecialchars($album['title_album']) . '"</strong> foi aprovado pela nossa equipa e está agora a ser distribuído para <strong>mais de 150 plataformas digitais</strong> em todo o mundo.</p>
             <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px;margin:16px 0">
               <p style="margin:0 0 8px;font-size:.85rem"><strong style="color:#16a34a">UPC do teu álbum:</strong></p>
               <p style="margin:0;font-family:monospace;font-size:1.1rem;font-weight:800;color:#166534;letter-spacing:2px">' . $upc . '</p>
             </div>
             <p style="color:#555;font-size:.85rem">A disponibilidade nas plataformas pode demorar entre 3-7 dias úteis após a aprovação.</p>
             ' . $smartlink_html
        );
        sendAlbumEmail($album['email_user'], 'O teu álbum foi aprovado — ' . APP_NAME, $html);

        al_logAudit(
            $db,
            $admin_id,
            'album.approved',
            '_album',
            $id_album,
            $ip,
            ['status_album' => 'under_review'],
            ['status_album' => 'approved', 'upc' => $upc, 'smartlink' => $smartlink]
        );

        // Se existir um pedido de revisão pendente, marcá-lo como resolvido
$rev_stmt = $db->prepare("
    UPDATE _album_review_request
    SET status_request = 'resolved',
        resolved_by = ?,
        resolved_at = NOW()
    WHERE id_album = ? AND status_request = 'pending'
");
$rev_stmt->execute([$admin_id, $id_album]);

        jOut(true, 'Álbum aprovado com sucesso! UPC: ' . $upc . '. Utilizador notificado.');
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[ALBUM APPROVE] ' . $e->getMessage());
        jOut(false, 'Erro ao aprovar o álbum. Tenta novamente.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// REJEITAR  (under_review → rejected)
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'reject') {
    requirePermission($admin_id, 'music.approve');

    if ($album['status_album'] !== 'under_review') {
        jOut(false, 'O álbum precisa de estar em revisão para ser rejeitado.');
    }

    $reason = trim($_POST['reject_reason'] ?? '');

    if (mb_strlen($reason, 'UTF-8') < 10) {
        jOut(false, 'O motivo deve ter pelo menos 10 caracteres.');
    }
    if (mb_strlen($reason, 'UTF-8') > 2000) {
        jOut(false, 'O motivo não pode exceder 2000 caracteres.');
    }

    try {
        $db->prepare("
            UPDATE _album
            SET status_album='rejected', rejection_reason=?, modif_album=NOW()
            WHERE id_album=?
        ")->execute([$reason, $id_album]);

        // Notificação
        notifyUser(
            $db,
            $album['id_users'],
            $admin_id,
            'warning',
            'Álbum não aprovado ❌',
            'O teu álbum "' . $album['title_album'] . '" não foi aprovado. Motivo: ' . mb_substr($reason, 0, 150, 'UTF-8') . (mb_strlen($reason, 'UTF-8') > 150 ? '…' : '') . ' — Podes corrigir e reenviar.',
            $dashboard_url
        );

        // Email
        $html = albumEmailTemplate(
            'O teu álbum não foi aprovado',
            '#ef4444',
            '❌',
            '<p>Olá <strong>' . htmlspecialchars($album['user_fullname']) . '</strong>,</p>
             <p>Após análise, o teu álbum <strong>"' . htmlspecialchars($album['title_album']) . '"</strong> não pode ser aprovado neste momento.</p>
             <div style="background:#fff5f5;border-left:4px solid #ef4444;padding:14px 16px;border-radius:0 8px 8px 0;margin:16px 0">
               <p style="margin:0 0 6px;font-size:.8rem;font-weight:700;color:#991b1b">Motivo da rejeição:</p>
               <p style="margin:0;font-size:.85rem;color:#374151">' . nl2br(htmlspecialchars($reason)) . '</p>
             </div>
             <p style="color:#555;font-size:.85rem">Podes <strong>corrigir os problemas apontados</strong> e reenviar o teu álbum para nova análise directamente pelo teu painel.</p>'
        );
        sendAlbumEmail($album['email_user'], 'O teu álbum não foi aprovado — ' . APP_NAME, $html);

        al_logAudit(
            $db,
            $admin_id,
            'album.rejected',
            '_album',
            $id_album,
            $ip,
            ['status_album' => 'under_review'],
            ['status_album' => 'rejected', 'reason' => $reason]
        );
        // Se existir pedido de revisão pendente, fechá-lo como resolvido (ou rejeitado, conforme o fluxo)
$rev_stmt = $db->prepare("
    UPDATE _album_review_request
    SET status_request = 'resolved',
        resolved_by = ?,
        resolved_at = NOW()
    WHERE id_album = ? AND status_request = 'pending'
");
$rev_stmt->execute([$admin_id, $id_album]);

        jOut(true, 'Álbum rejeitado. O utilizador foi notificado com o motivo.');
    } catch (Exception $e) {
        error_log('[ALBUM REJECT] ' . $e->getMessage());
        jOut(false, 'Erro ao rejeitar o álbum. Tenta novamente.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// REABRIR  (rejected → pending)
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'reopen') {
    requirePermission($admin_id, 'music.approve');

    if ($album['status_album'] !== 'rejected') {
        jOut(false, 'Só é possível reabrir álbuns no estado "Rejeitado".');
    }

    try {
        $db->prepare("
            UPDATE _album
            SET status_album='pending', rejection_reason=NULL, modif_album=NOW()
            WHERE id_album=?
        ")->execute([$id_album]);

        notifyUser(
            $db,
            $album['id_users'],
            $admin_id,
            'info',
            'Álbum reaberto para revisão 🔄',
            'O teu álbum "' . $album['title_album'] . '" foi reaberto e está novamente em análise. Verifica se fizeste as correcções necessárias.',
            $dashboard_url
        );

        al_logAudit(
            $db,
            $admin_id,
            'album.reopened',
            '_album',
            $id_album,
            $ip,
            ['status_album' => 'rejected'],
            ['status_album' => 'pending']
        );

        jOut(true, 'Álbum reaberto. Voltou ao estado "Pendente".');
    } catch (Exception $e) {
        error_log('[ALBUM REOPEN] ' . $e->getMessage());
        jOut(false, 'Erro ao reabrir o álbum.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// ACTUALIZAR UPC / SMARTLINK  (num álbum já aprovado)
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'update_upc') {
    requirePermission($admin_id, 'music.approve');

    if ($album['status_album'] !== 'approved') {
        jOut(false, 'Só é possível actualizar UPC de álbuns aprovados.');
    }

    $upc       = trim($_POST['upc']       ?? '');
    $smartlink = trim($_POST['smartlink'] ?? '');

    if ($upc && !preg_match('/^\d{13}$/', $upc)) {
        jOut(false, 'UPC inválido. Deve ter exactamente 13 dígitos numéricos (EAN-13).');
    }

    // Verificar unicidade do UPC
    if ($upc) {
        $upc_check = $db->prepare("SELECT id_album FROM _album WHERE upc=? AND id_album!=?");
        $upc_check->execute([$upc, $id_album]);
        if ($upc_check->fetch()) {
            jOut(false, 'Este UPC já está atribuído a outro álbum.');
        }
    }

    if ($smartlink && !filter_var($smartlink, FILTER_VALIDATE_URL)) {
        jOut(false, 'Smartlink deve ser uma URL válida.');
    }

    try {
        $db->prepare("
            UPDATE _album
            SET upc = NULLIF(?,  ''),
                smartlink = NULLIF(?, ''),
                modif_album = NOW()
            WHERE id_album = ?
        ")->execute([$upc ?: null, $smartlink ?: null, $id_album]);

        al_logAudit(
            $db,
            $admin_id,
            'album.upc_updated',
            '_album',
            $id_album,
            $ip,
            ['upc' => $album['upc'], 'smartlink' => $album['smartlink']],
            ['upc' => $upc, 'smartlink' => $smartlink]
        );

        jOut(true, 'UPC e smartlink actualizados com sucesso.');
    } catch (Exception $e) {
        error_log('[ALBUM UPDATE_UPC] ' . $e->getMessage());
        jOut(false, 'Erro ao actualizar. Tenta novamente.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// EDITAR ÁLBUM (edit_album)
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'edit_album') {
    requirePermission($admin_id, 'music.approve');

    $title_album  = trim($_POST['title_album']  ?? '');
    $genre_main   = trim($_POST['genre_main']   ?? '');
    $release_date = trim($_POST['release_date'] ?? '');
    $territory    = trim($_POST['territory']    ?? '');
    $label_name   = trim($_POST['label_name']   ?? '');
    $type_album   = trim($_POST['type_album']   ?? '');
    $new_status   = trim($_POST['status_album'] ?? '');

    if (mb_strlen($title_album, 'UTF-8') < 1 || mb_strlen($title_album, 'UTF-8') > 255) {
        jOut(false, 'Título inválido (1-255 caracteres).');
    }
    if (mb_strlen($genre_main, 'UTF-8') < 1 || mb_strlen($genre_main, 'UTF-8') > 100) {
        jOut(false, 'Género principal inválido (1-100 caracteres).');
    }

    $allowed_types   = ['single', 'ep', 'album', 'mixtape'];
    $allowed_statuses = ['draft', 'pending', 'under_review', 'approved', 'rejected'];
    if ($type_album && !in_array($type_album, $allowed_types, true)) {
        jOut(false, 'Tipo de álbum inválido.');
    }
    if ($new_status && !in_array($new_status, $allowed_statuses, true)) {
        jOut(false, 'Estado inválido.');
    }

    $release_date = $release_date ? date('Y-m-d', strtotime($release_date)) : null;
    $status_changed = $new_status && $new_status !== $album['status_album'];

    try {
        $old_data = $album;

        $db->prepare("
            UPDATE _album
            SET title_album  = ?,
                genre_main   = ?,
                type_album   = COALESCE(NULLIF(?, ''), type_album),
                status_album = COALESCE(NULLIF(?, ''), status_album),
                release_date = NULLIF(?, ''),
                territory    = NULLIF(?, ''),
                label_name   = NULLIF(?, ''),
                modif_album  = NOW()
            WHERE id_album = ?
        ")->execute([
            $title_album,
            $genre_main,
            $type_album,
            $new_status,
            $release_date,
            $territory,
            $label_name,
            $id_album
        ]);

        // Se o status mudou → notificar utilizador
        if ($status_changed) {
            $status_labels = [
                'draft'        => 'Rascunho',
                'pending'      => 'Pendente de Revisão',
                'under_review' => 'Em Revisão',
                'approved'     => 'Aprovado',
                'rejected'     => 'Rejeitado',
            ];
            $new_label = $status_labels[$new_status] ?? ucfirst($new_status);
            notifyUser(
                $db,
                $album['id_users'],
                $admin_id,
                'info',
                'Estado do álbum actualizado',
                'O estado do teu álbum "' . $album['title_album'] . '" foi alterado para: ' . $new_label . '.',
                $dashboard_url
            );
        }

        al_logAudit($db, $admin_id, 'album.edited', '_album', $id_album, $ip, [
            'title_album'  => $old_data['title_album'],
            'status_album' => $old_data['status_album'],
            'genre_main'   => $old_data['genre_main'],
        ], [
            'title_album'  => $title_album,
            'status_album' => $new_status ?: $old_data['status_album'],
            'genre_main'   => $genre_main,
        ]);

        $msg = 'Álbum actualizado com sucesso.';
        if ($status_changed) $msg .= ' Estado alterado para "' . $new_label . '".';
        jOut(true, $msg);
    } catch (Exception $e) {
        error_log('[ALBUM EDIT] ' . $e->getMessage());
        jOut(false, 'Erro ao actualizar. Tenta novamente.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// ELIMINAR ÁLBUM (delete_album) - Soft-delete com confirmação de senha
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'delete_album') {
    requirePermission($admin_id, 'music.approve');

    if (in_array($album['status_album'], ['approved', 'deleting'])) {
        jOut(false, 'Não é possível eliminar álbuns aprovados ou já marcados para eliminação.');
    }

    // Verificar senha do admin
    $admin_password = trim((string)($_POST['admin_password'] ?? ''));
    if (empty($admin_password)) {
        jOut(false, 'A senha é obrigatória para confirmar a eliminação.');
    }

    // Buscar hash da senha do admin actual
    $pw_stmt = $db->prepare("SELECT password_employees FROM _employees WHERE id_employees = ? LIMIT 1");
    $pw_stmt->execute([$admin_id]);
    $pw_row = $pw_stmt->fetch();

    if (!$pw_row || !password_verify($admin_password, $pw_row['password_employees'])) {
        jOut(false, 'Senha incorrecta. Tenta novamente.');
    }

    try {
        $delete_expires = date('Y-m-d H:i:s', strtotime('+7 days'));

        $db->prepare("
            UPDATE _album
            SET status_album = 'deleting', delete_expires_at = ?,
                modif_album = NOW()
            WHERE id_album = ?
        ")->execute([$delete_expires, $id_album]);

        notifyUser(
            $db,
            $album['id_users'],
            $admin_id,
            'warning',
            'Álbum marcado para eliminação 🗑️',
            'O álbum "' . $album['title_album'] . '" foi marcado para eliminação permanente em 7 dias. Contacta o suporte se foi um engano.',
            $dashboard_url
        );

        al_logAudit($db, $admin_id, 'album.deleted', '_album', $id_album, $ip, [
            'status_album' => $album['status_album']
        ], ['status_album' => 'deleting', 'delete_expires_at' => $delete_expires]);

        jOut(true, 'Álbum marcado para eliminação (recuperável em 7 dias).');
    } catch (Exception $e) {
        error_log('[ALBUM DELETE] ' . $e->getMessage());
        jOut(false, 'Erro ao marcar para eliminação.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// RECUPERAR ÁLBUM (undelete_album)
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'undelete_album') {
    requirePermission($admin_id, 'music.approve');

    if ($album['status_album'] !== 'deleting') {
        jOut(false, 'Só álbuns no estado "A eliminar" podem ser recuperados.');
    }

    try {
        $db->prepare("
            UPDATE _album 
            SET status_album = 'draft', delete_expires_at = NULL, 
                modif_album = NOW()
            WHERE id_album = ?
        ")->execute([$id_album]);

        notifyUser(
            $db,
            $album['id_users'],
            $admin_id,
            'success',
            'Álbum recuperado ✅',
            'O álbum "' . $album['title_album'] . '" foi recuperado e voltou ao estado de rascunho.',
            $dashboard_url
        );

        al_logAudit($db, $admin_id, 'album.undeleted', '_album', $id_album, $ip, [
            'status_album' => 'deleting'
        ], ['status_album' => 'draft']);

        jOut(true, 'Álbum recuperado com sucesso.');
    } catch (Exception $e) {
        error_log('[ALBUM UNDELETE] ' . $e->getMessage());
        jOut(false, 'Erro ao recuperar álbum.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// ELIMINAR PERMANENTEMENTE (hard delete) — só após expiração
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'permanent_delete_album') {
    requirePermission($admin_id, 'music.delete');

    if ($album['status_album'] !== 'deleting') {
        jOut(false, 'Só é possível eliminar permanentemente álbuns que já estão marcados para eliminação.');
    }

    // Opcional: verificar se a data de expiração já passou
    if (strtotime($album['delete_expires_at']) > time()) {
        jOut(false, 'O período de espera ainda não expirou. Eliminação manual permitida apenas após expiração.');
    }

    // Verificar senha do admin
    $admin_password = trim((string)($_POST['admin_password'] ?? ''));
    if (empty($admin_password)) {
        jOut(false, 'A senha é obrigatória para confirmar a eliminação permanente.');
    }

    $pw_stmt = $db->prepare("SELECT password_employees FROM _employees WHERE id_employees = ? LIMIT 1");
    $pw_stmt->execute([$admin_id]);
    $pw_row = $pw_stmt->fetch();

    if (!$pw_row || !password_verify($admin_password, $pw_row['password_employees'])) {
        jOut(false, 'Senha incorrecta. Tenta novamente.');
    }

    try {
        // Apagar ficheiros físicos
        $audio_base = dirname(__DIR__, 3) . '/assets/uploads/audio/';
        $cover_base = dirname(__DIR__, 3) . '/assets/comprovantes/uploads/covers/';

        $track_files = $db->prepare("SELECT audio_file FROM _track WHERE id_album = ?");
        $track_files->execute([$id_album]);
        $tracks = $track_files->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tracks as $audio_file) {
            if ($audio_file) {
                $path = $audio_base . $audio_file;
                if (file_exists($path)) @unlink($path);
            }
        }

        if (!empty($album['img_cover'])) {
            $cover_path = $cover_base . $album['img_cover'];
            if (file_exists($cover_path)) @unlink($cover_path);
        }

        // Remover registos da BD
        $db->beginTransaction();
        $db->prepare("DELETE FROM _track WHERE id_album = ?")->execute([$id_album]);
        $db->prepare("DELETE FROM _album_store WHERE id_album = ?")->execute([$id_album]);
        $db->prepare("DELETE FROM _album_review_request WHERE id_album = ?")->execute([$id_album]);
        $db->prepare("DELETE FROM _takedown_request WHERE id_album = ?")->execute([$id_album]);
        $db->prepare("DELETE FROM _album WHERE id_album = ?")->execute([$id_album]);
        $db->commit();

        al_logAudit($db, $admin_id, 'album.permanently_deleted', '_album', $id_album, $ip,
            ['status_album' => 'deleting'], null);

        jOut(true, 'Álbum eliminado permanentemente com sucesso.');
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[PERM DELETE] ' . $e->getMessage());
        jOut(false, 'Erro ao eliminar o álbum. Tenta novamente.');
    }
}
jOut(false, 'Acção desconhecida.');
