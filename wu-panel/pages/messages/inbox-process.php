<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Inbox Process (AJAX)
// Arquivo: wu-panel/pages/messages/inbox-process.php
// Rota:    wu-panel/messages/inbox-process (POST only)
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'support.view');

function jOut(bool $ok, string $msg, array $extra = []): never
{
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jOut(false, 'Método não permitido.');

if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    jOut(false, 'Sessão expirada. Recarrega a página.');
}

$action = trim($_POST['action'] ?? '');
$msg_id = (int)($_POST['msg_id'] ?? 0);
$source = trim($_POST['source'] ?? '');

// ── Helpers ───────────────────────────────────────────────────
function ib_fmt_date(string $dt): string
{
    return date('d/m/Y H:i', strtotime($dt));
}
function ib_relative(string $dt): string
{
    $ts   = strtotime($dt);
    $diff = time() - $ts;
    if ($diff < 60)     return 'agora';
    if ($diff < 3600)   return floor($diff / 60) . 'min atrás';
    if ($diff < 86400)  return floor($diff / 3600) . 'h atrás';
    if ($diff < 604800) return date('d/m', $ts) . ' às ' . date('H:i', $ts);
    return date('d/m/Y H:i', $ts);
}
function ib_avatar(string $name, ?string $photo, int $s = 40): string
{
    $p   = explode(' ', trim($name), 2);
    $ini = mb_strtoupper(mb_substr($p[0] ?? '', 0, 1, 'UTF-8'), 'UTF-8')
        . mb_strtoupper(mb_substr($p[1] ?? '', 0, 1, 'UTF-8'), 'UTF-8');
    $cl  = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308', '#3b82f6', '#ef4444'];
    $c   = $cl[abs(crc32($name)) % count($cl)];
    $fs  = round($s * 0.3);
    if ($photo) {
        return "<img src=\"" . APP_URL . "/assets/comprovantes/uploads/users/" . htmlspecialchars($photo) . "\"
                     width=\"$s\" height=\"$s\"
                     style=\"border-radius:50%;object-fit:cover;flex-shrink:0\"
                     onerror=\"this.style.display='none';this.nextElementSibling.style.display='flex'\" alt=\"\">
                <div style=\"width:{$s}px;height:{$s}px;border-radius:50%;background:{$c};
                            display:none;align-items:center;justify-content:center;
                            font-weight:700;font-size:{$fs}px;color:#fff;flex-shrink:0\">{$ini}</div>";
    }
    return "<div style=\"width:{$s}px;height:{$s}px;border-radius:50%;background:{$c};
                         display:flex;align-items:center;justify-content:center;
                         font-weight:700;font-size:{$fs}px;color:#fff;flex-shrink:0\">{$ini}</div>";
}

function ib_send_email(string $to, string $subject, string $body): bool
{
    $p = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';
    if (!file_exists($p)) return false;
    if (!class_exists('\Wasom\Mailer')) require_once $p;
    try {
        $wm = new \Wasom\Mailer();
        $wm->host = MAIL_HOST;
        $wm->port = MAIL_PORT;
        $wm->secure = defined('MAIL_SECURE') ? MAIL_SECURE : 'tls';
        $wm->username = MAIL_USER;
        $wm->password = MAIL_PASS;
        $wm->debug = 0;
        $wm->setFrom(MAIL_FROM, MAIL_FROM_NAME)->addAddress($to)->setSubject($subject)->setBody($body, strip_tags($body));
        $wm->send();
        return true;
    } catch (\Wasom\MailerException $e) {
        error_log('[INBOX_MAIL] ' . $e->getMessage());
        return false;
    }
}

function ib_email_template(string $subject, string $content, string $color = '#FF0089'): string
{
    return "
    <div style='font-family:\"Segoe UI\",Arial,sans-serif;max-width:560px;margin:auto'>
      <div style='background:linear-gradient(135deg,#0f0f1a,#1a1a2e);padding:22px 28px;border-radius:12px 12px 0 0;display:flex;align-items:center;gap:12px'>
        <div style='font-size:.85rem;font-weight:800;color:#fff'>Wasom Upfy</div>
        <div style='font-size:.6rem;color:$color;text-transform:uppercase;letter-spacing:1px;font-weight:700'>Suporte</div>
      </div>
      <div style='background:#fff;padding:28px;border:1px solid #eee;border-top:none;border-radius:0 0 12px 12px'>
        <h2 style='color:#1a1a2e;font-size:.95rem;font-weight:800;margin:0 0 14px'>$subject</h2>
        <div style='font-size:.87rem;line-height:1.7;color:#374151'>$content</div>
        <hr style='border:none;border-top:1px solid #f0f0f0;margin:20px 0'>
        <small style='color:#bbb'>Wasom Upfy — Responde a este email para continuar a conversa.</small>
      </div>
    </div>";
}

function ib_notifyUser(PDO $db, int $id_users, int $id_emp, string $title, string $body, string $url = ''): void
{
    try {
        $db->prepare("INSERT INTO _notification (id_users,id_employees,type,title,body,action_url) VALUES (?,?,'info',?,?,?)")
            ->execute([$id_users, $id_emp, $title, $body, $url]);
    } catch (Exception $e) {
        error_log('[INBOX_NOTIFY] ' . $e->getMessage());
    }
}

function ib_has_column(PDO $db, string $table, string $column): bool
{
    static $cache = [];

    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);

    return $cache[$key] = ((int)$stmt->fetchColumn() > 0);
}

function ib_can_star(PDO $db, string $source): bool
{
    if (str_starts_with($source, 'ticket_')) {
        return ib_has_column($db, '_support_ticket', 'is_starred');
    }
    if ($source === 'contact') {
        return ib_has_column($db, '_contact_message', 'is_starred');
    }
    if ($source === 'feedback') {
        return ib_has_column($db, '_feedback', 'is_starred');
    }

    return false;
}

// ── Construir o HTML completo de uma mensagem ─────────────────
function buildMessageHTML(array $msg, array $replies, ?array $user, ?array $assigned_emp, array $all_admins, PDO $db, string $source): array
{
    $src_map = [
        'ticket_auth'   => ['Suporte (Auth)',   '#8b5cf6', 'bi-shield-lock'],
        'ticket_public' => ['Suporte (Site)',   '#3b82f6', 'bi-globe2'],
        'ticket_dash'   => ['Suporte (Painel)', '#FF0089', 'bi-person-fill'],
        'contact'       => ['Contacto Site',    '#f97316', 'bi-envelope'],
        'feedback'      => ['Feedback',         '#22c55e', 'bi-chat-square-text'],
    ];
    [$src_label, $src_color, $src_icon] = $src_map[$msg['source']] ?? ['Mensagem', '#6b7280', 'bi-chat'];

    $can_approve = hasPermission($GLOBALS['admin_id'], 'support.edit') || hasPermission($GLOBALS['admin_id'], 'support.view');
    $has_user    = !empty($msg['id_users']);
    $can_star    = !empty($msg['can_star']);

    // Status options por tipo de fonte
    if (str_starts_with($msg['source'], 'ticket_')) {
        $status_options = ['open' => 'Aberto', 'in_progress' => 'Em Progresso', 'resolved' => 'Resolvido', 'closed' => 'Fechado'];
        $cur_status     = $msg['status_ticket'];
    } elseif ($msg['source'] === 'contact') {
        $status_options = ['new' => 'Novo', 'read' => 'Lido', 'replied' => 'Respondido', 'archived' => 'Arquivado'];
        $cur_status     = $msg['status_msg'];
    } else {
        $status_options = ['new' => 'Novo', 'read' => 'Lido', 'archived' => 'Arquivado'];
        $cur_status     = $msg['status_fb'];
    }

    $priority_label = match ($msg['priority'] ?? 'normal') {
        'high' => '<span class="badge" style="background:rgba(239,68,68,.15);color:#991b1b;font-size:.68rem">Prioridade Alta</span>',
        'low'  => '<span class="badge" style="background:rgba(107,114,128,.12);color:#374151;font-size:.68rem">Prioridade Baixa</span>',
        default => '',
    };

    // ── Header do email
    $html = '<div class="inbox-msg-header">';
    $html .= '<div class="d-flex align-items-start justify-content-between gap-3 mb-3">';
    $html .= '<h4 class="inbox-msg-title mb-0">' . htmlspecialchars($msg['subject']) . '</h4>';
    $html .= '<div class="d-flex gap-2 align-items-center flex-shrink-0">';
    $html .= '<span style="background:' . $src_color . '1a;color:' . $src_color . ';padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700"><i class="bi ' . $src_icon . ' me-1"></i>' . $src_label . '</span>';
    if ($priority_label) $html .= $priority_label;
    $html .= '</div></div>';

    // ── Remetente
    $sender_name = $msg['sender_name'] ?: 'Visitante';
    $html .= '<div class="inbox-msg-from">';
    $html .= ib_avatar($sender_name, $msg['sender_photo'] ?? null, 42);
    $html .= '<div><div style="font-weight:700;font-size:.88rem">' . htmlspecialchars($sender_name) . '</div>';
    $html .= '<div class="inbox-msg-meta">';
    $html .= htmlspecialchars($msg['sender_email'] ?? '');
    if ($has_user && $user) {
        $html .= ' · <a href="' . APP_URL . '/' . ADMIN_PATH . '/users/view?id=' . (int)$msg['id_users'] . '" style="color:#FF0089;font-size:.72rem" target="_blank">Ver perfil</a>';
        if (!empty($user['name_plan'])) $html .= ' · <span style="font-size:.72rem;color:#8b5cf6">' . $user['name_plan'] . '</span>';
    }
    $html .= '<br><span style="font-size:.7rem">' . ib_fmt_date($msg['created_at']) . '</span>';
    if ($msg['ip_address'] ?? '') $html .= ' · <span style="font-size:.68rem;font-family:monospace;opacity:.5">' . htmlspecialchars($msg['ip_address']) . '</span>';
    $html .= '</div></div>';
    $html .= '</div>'; // from

    // Campos extras (tickets)
    if (str_starts_with($msg['source'], 'ticket_') && (!empty($msg['urgency_ticket']) || !empty($msg['issue_type_ticket']))) {
        $html .= '<div class="d-flex gap-2 mb-3 flex-wrap">';
        if (!empty($msg['urgency_ticket']))    $html .= '<span style="font-size:.72rem;background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:6px">Urgência: ' . ucfirst($msg['urgency_ticket']) . '</span>';
        if (!empty($msg['issue_type_ticket'])) $html .= '<span style="font-size:.72rem;background:#e0f2fe;color:#1e40af;padding:2px 8px;border-radius:6px">Tipo: ' . ucfirst($msg['issue_type_ticket']) . '</span>';
        if (!empty($msg['phone_msg'] ?? $msg['phone_contact'] ?? '')) $html .= '<span style="font-size:.72rem;background:#f0fdf4;color:#166534;padding:2px 8px;border-radius:6px"><i class="bi bi-telephone me-1"></i>' . htmlspecialchars($msg['phone_msg'] ?? $msg['phone_contact']) . '</span>';
        $html .= '</div>';
    }

    $html .= '</div>'; // inbox-msg-header

    // ── Corpo da mensagem
    $body_text = $msg['body'] ?? $msg['message_msg'] ?? $msg['message_fb'] ?? $msg['message_contact'] ?? '';
    $html .= '<div class="inbox-msg-content">' . nl2br(htmlspecialchars($body_text)) . '</div>';

    // ── Thread de respostas
    if (!empty($replies)) {
        $html .= '<div class="inbox-replies">';
        $html .= '<h6 style="font-size:.8rem;font-weight:700;margin-bottom:12px;opacity:.7"><i class="bi bi-chat-left-dots me-2"></i>' . count($replies) . ' resposta' . ((count($replies) > 1) ? 's' : '') . '</h6>';
        foreach ($replies as $rep) {
            $is_admin = !empty($rep['from_employee']);
            $rep_name = $is_admin
                ? (trim(($rep['emp_first'] ?? '') . (' ' . ($rep['emp_second'] ?? ''))) ?: 'Admin')
                : (trim(($rep['usr_first'] ?? '') . (' ' . ($rep['usr_second'] ?? ''))) ?: 'Utilizador');
            $html .= '<div class="inbox-reply-item ' . ($is_admin ? 'from-admin' : 'from-user') . '">';
            $html .= '<div class="inbox-reply-meta">';
            $html .= '<span><strong>' . htmlspecialchars($rep_name) . '</strong>' . ($is_admin ? ' (Admin)' : '') . '</span>';
            $html .= '<span>' . ib_relative($rep['creat_reply']) . '</span>';
            $html .= '</div>';
            $html .= '<div class="inbox-reply-body">' . nl2br(htmlspecialchars($rep['body'])) . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
    }

    // ── Caixa de resposta
    if ($can_approve) {
        $btn_label = $has_user ? 'Enviar Resposta (email + painel)' : 'Responder por Email';
        $html .= '
        <div class="inbox-reply-box">
            <div style="font-size:.78rem;font-weight:700;margin-bottom:8px;opacity:.7">
                <i class="bi bi-reply me-1"></i>' . $btn_label . '
            </div>
            <textarea id="reply_textarea" rows="4" placeholder="Escreve a tua resposta..."></textarea>
            <div class="d-flex gap-2 mt-2 align-items-center">
                <button class="btn btn-sm text-white" style="background:#FF0089;border-color:#FF0089" id="btn_send_reply">
                    <span class="normal-lbl"><i class="bi bi-send me-1"></i>Enviar</span>
                    <span class="loading-lbl d-none"><span class="spinner-border spinner-border-sm me-1"></span>A enviar…</span>
                </button>';
        if (!$has_user) {
            $html .= '<span style="font-size:.72rem;opacity:.5"><i class="bi bi-info-circle me-1"></i>Sem conta — resposta apenas por email</span>';
        }
        $html .= '<div class="alert alert-danger d-none ms-2 mb-0 py-1 px-2 flex-fill" id="reply_error" style="font-size:.75rem"></div>';
        $html .= '</div></div>';
    }

    // ── Toolbar HTML
    $status_select = '<select id="status_select" class="status-select-sm" title="Mudar estado">';
    foreach ($status_options as $v => $l) {
        $sel = $cur_status === $v ? ' selected' : '';
        $status_select .= "<option value=\"$v\"$sel>$l</option>";
    }
    $status_select .= '</select>';

    $assign_select = '<select id="assign_select" class="status-select-sm" title="Atribuir a admin">';
    $assign_select .= '<option value="">Sem atribuição</option>';
    foreach ($all_admins as $a) {
        $sel = ($assigned_emp && $assigned_emp['id_employees'] == $a['id_employees']) ? ' selected' : '';
        $assign_select .= '<option value="' . (int)$a['id_employees'] . '"' . $sel . '>' . htmlspecialchars($a['name']) . '</option>';
    }
    $assign_select .= '</select>';

    $star_title = $can_star
        ? ($msg['is_starred'] ? 'Remover importância' : 'Marcar como importante')
        : 'Favoritos indisponíveis nesta instalação';
    $star_state = $msg['is_starred'] ? 'bi-star-fill text-warning' : 'bi-star';

    $toolbar = '
        <button class="btn btn-sm btn-outline-secondary"
            data-can-star="' . ($can_star ? '1' : '0') . '"
            onclick="toggleStar(event,' . ($msg['msg_id']) . ',\'' . htmlspecialchars($source) . '\',this)"
            title="' . htmlspecialchars($star_title, ENT_QUOTES, 'UTF-8') . '"'
        . ($can_star ? '' : ' style="opacity:.45;cursor:not-allowed" aria-disabled="true"') . '>
            <i class="bi ' . $star_state . '"></i>
        </button>
        ' . $status_select . '
        ' . $assign_select . '
        <button class="btn btn-sm btn-outline-secondary" id="btn_archive" title="Arquivar">
            <i class="bi bi-archive"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger ms-auto" id="btn_delete" title="Eliminar">
            <i class="bi bi-trash"></i>
        </button>';

    return ['html' => $html, 'toolbar_html' => $toolbar];
}

// ════════════════════════════════════════════════════════════════════════════
// ACÇÃO: CARREGAR MENSAGEM
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'load_message') {
    if ($msg_id <= 0 || !$source) jOut(false, 'Dados inválidos.');

    $msg      = null;
    $replies  = [];
    $user     = null;
    $was_unread = false;

    $admins = $db->query("SELECT id_employees, CONCAT(first_name,' ',COALESCE(second_name,'')) AS name FROM _employees WHERE status_employees='active' ORDER BY first_name")->fetchAll();

    if (str_starts_with($source, 'ticket_')) {
        $stmt = $db->prepare("
            SELECT st.*, st.id_users, st.email_contact AS sender_email, st.name_contact AS sender_name,
                   u.photo_user AS sender_photo, u.name_artist_band,
                   pl.name_plan
            FROM _support_ticket st
            LEFT JOIN _users u ON u.id_users=st.id_users
            LEFT JOIN _user_plan up ON up.id_users=st.id_users AND up.status_plan='active'
            LEFT JOIN _plans pl ON pl.id_plan=up.id_plan
            WHERE st.id_ticket=?
        ");
        $stmt->execute([$msg_id]);
        $msg = $stmt->fetch();
        if (!$msg) jOut(false, 'Mensagem não encontrada.');

        // Normalizar source
        $msg['source']     = match ($msg['source_ticket']) {
            'auth_modal' => 'ticket_auth',
            'public_form' => 'ticket_public',
            'dashboard_form' => 'ticket_dash',
            default => 'ticket_auth'
        };
        $msg['created_at'] = $msg['creat_ticket'];
        $msg['can_star']   = ib_can_star($db, $msg['source']);
        $msg['is_starred'] = $msg['is_starred'] ?? 0;
        $msg['priority']   = $msg['priority'] ?? 'normal';
        $msg['msg_id']     = $msg['id_ticket'];
        $msg['ip_address'] = $msg['ip_ticket'];

        $was_unread = $msg['status_ticket'] === 'open';
        if ($was_unread) {
            $db->prepare("UPDATE _support_ticket SET status_ticket='in_progress' WHERE id_ticket=? AND status_ticket='open'")->execute([$msg_id]);
        }

        // Respostas
        $rep_stmt = $db->prepare("
            SELECT sr.*, 
                   eu.first_name AS emp_first, eu.second_name AS emp_second,
                   uu.first_name AS usr_first, uu.second_name AS usr_second
            FROM _support_reply sr
            LEFT JOIN _employees eu ON eu.id_employees=sr.from_employee
            LEFT JOIN _users uu ON uu.id_users=sr.from_user
            WHERE sr.id_ticket=? ORDER BY sr.creat_reply ASC
        ");
        $rep_stmt->execute([$msg_id]);
        $replies = $rep_stmt->fetchAll();

        // Utilizador
        if (!empty($msg['id_users'])) {
            $user_stmt = $db->prepare("
                SELECT u.*, pl.name_plan 
                FROM _users u 
                LEFT JOIN _user_plan up ON up.id_users=u.id_users AND up.status_plan='active' 
                LEFT JOIN _plans pl ON pl.id_plan=up.id_plan 
                WHERE u.id_users=?
            ");
            $user_stmt->execute([$msg['id_users']]);
            $user = $user_stmt->fetch() ?: null;
        }
        $assigned_emp = null;
        if (!empty($msg['assigned_to'])) {
            $ae = $db->prepare("SELECT * FROM _employees WHERE id_employees=?");
            $ae->execute([$msg['assigned_to']]);
            $assigned_emp = $ae->fetch() ?: null;
        }
    } elseif ($source === 'contact') {
        $stmt = $db->prepare("SELECT *, email_msg AS sender_email, name_msg AS sender_name FROM _contact_message WHERE id=?");
        $stmt->execute([$msg_id]);
        $msg = $stmt->fetch();
        if (!$msg) jOut(false, 'Mensagem não encontrada.');
        $msg['source']     = 'contact';
        $msg['can_star']   = ib_can_star($db, $msg['source']);
        $msg['is_starred'] = $msg['is_starred'] ?? 0;
        $msg['priority']   = 'normal';
        $msg['body']       = $msg['message_msg'];
        $msg['subject']    = $msg['subject_msg'];
        $msg['msg_id']     = $msg['id'];
        $msg['id_users']   = null;
        $msg['sender_photo'] = null;
        $was_unread = $msg['status_msg'] === 'new';
        if ($was_unread) {
            $db->prepare("UPDATE _contact_message SET status_msg='read' WHERE id=? AND status_msg='new'")->execute([$msg_id]);
        }
        $assigned_emp = null;
    } elseif ($source === 'feedback') {
        $stmt = $db->prepare("SELECT *, '' AS sender_email, name_fb AS sender_name FROM _feedback WHERE id=?");
        $stmt->execute([$msg_id]);
        $msg = $stmt->fetch();
        if (!$msg) jOut(false, 'Mensagem não encontrada.');
        $msg['source']     = 'feedback';
        $msg['can_star']   = ib_can_star($db, $msg['source']);
        $msg['is_starred'] = $msg['is_starred'] ?? 0;
        $msg['priority']   = 'low';
        $msg['body']       = $msg['message_fb'];
        $msg['subject']    = $msg['subject_fb'];
        $msg['msg_id']     = $msg['id'];
        $msg['id_users']   = null;
        $msg['sender_photo'] = null;
        $was_unread = $msg['status_fb'] === 'new';
        if ($was_unread) {
            $db->prepare("UPDATE _feedback SET status_fb='read' WHERE id=? AND status_fb='new'")->execute([$msg_id]);
        }
        $assigned_emp = null;
    } else {
        jOut(false, 'Fonte desconhecida.');
    }

    $rendered = buildMessageHTML($msg, $replies, $user, $assigned_emp, $admins, $db, $source);
    jOut(true, '', ['html' => $rendered['html'], 'toolbar_html' => $rendered['toolbar_html'], 'was_unread' => $was_unread]);
}

// ════════════════════════════════════════════════════════════════════════════
// ACÇÃO: TOGGLE STAR
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'toggle_star') {
    if ($msg_id <= 0 || !$source) jOut(false, 'Dados inválidos.');

    if (!ib_can_star($db, $source)) {
        jOut(false, 'Favoritos indisponíveis nesta instalação.', ['can_star' => false, 'starred' => 0]);
    }

    try {
        if (str_starts_with($source, 'ticket_')) {
            $cur = $db->prepare("SELECT is_starred FROM _support_ticket WHERE id_ticket=?");
            $cur->execute([$msg_id]);
            $row = $cur->fetch();
            $new = $row ? ($row['is_starred'] ? 0 : 1) : 1;
            $db->prepare("UPDATE _support_ticket SET is_starred=? WHERE id_ticket=?")->execute([$new, $msg_id]);
        } elseif ($source === 'contact') {
            $cur = $db->prepare("SELECT is_starred FROM _contact_message WHERE id=?");
            $cur->execute([$msg_id]);
            $row = $cur->fetch();
            $new = $row ? ($row['is_starred'] ? 0 : 1) : 1;
            $db->prepare("UPDATE _contact_message SET is_starred=? WHERE id=?")->execute([$new, $msg_id]);
        } elseif ($source === 'feedback') {
            $cur = $db->prepare("SELECT is_starred FROM _feedback WHERE id=?");
            $cur->execute([$msg_id]);
            $row = $cur->fetch();
            $new = $row ? ($row['is_starred'] ? 0 : 1) : 1;
            $db->prepare("UPDATE _feedback SET is_starred=? WHERE id=?")->execute([$new, $msg_id]);
        }
        jOut(true, 'OK', ['starred' => $new ?? 0]);
    } catch (Exception $e) {
        error_log('[INBOX STAR] ' . $e->getMessage());
        jOut(false, 'Erro.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// ACÇÃO: RESPONDER
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'reply') {
    requirePermission($admin_id, 'support.view');
    if ($msg_id <= 0 || !$source) jOut(false, 'Dados inválidos.');

    $body = trim($_POST['body'] ?? '');
    if (mb_strlen($body, 'UTF-8') < 3)   jOut(false, 'A resposta é muito curta.');
    if (mb_strlen($body, 'UTF-8') > 5000) jOut(false, 'A resposta não pode exceder 5000 caracteres.');

    // Buscar dados do ticket/mensagem original
    $orig_email = '';
    $orig_user  = null;
    $orig_subject = '';

    if (str_starts_with($source, 'ticket_')) {
        $t = $db->prepare("SELECT id_ticket, id_users, email_contact, name_contact, subject FROM _support_ticket WHERE id_ticket=?");
        $t->execute([$msg_id]);
        $ticket = $t->fetch();
        if (!$ticket) jOut(false, 'Ticket não encontrado.');

        $orig_email   = $ticket['email_contact'];
        $orig_subject = $ticket['subject'];

        if ($ticket['id_users']) {
            $us = $db->prepare("SELECT * FROM _users WHERE id_users=?");
            $us->execute([$ticket['id_users']]);
            $orig_user = $us->fetch() ?: null;
        }

        try {
            $db->prepare("
                INSERT INTO _support_reply (id_ticket, from_employee, body)
                VALUES (?, ?, ?)
            ")->execute([$msg_id, $admin_id, $body]);

            $db->prepare("UPDATE _support_ticket SET status_ticket='in_progress', modif_ticket=NOW() WHERE id_ticket=?")->execute([$msg_id]);

            // Notificação no painel (se tem conta)
            if ($orig_user) {
                ib_notifyUser(
                    $db,
                    (int)$orig_user['id_users'],
                    $admin_id,
                    'Resposta ao teu ticket de suporte',
                    'A equipa da Wasom Upfy respondeu ao teu pedido: "' . mb_substr($orig_subject, 0, 60, 'UTF-8') . '"',
                    APP_URL . '/dashboard/page/support'
                );
            }
        } catch (Exception $e) {
            error_log('[INBOX REPLY DB] ' . $e->getMessage());
            jOut(false, 'Erro ao guardar resposta.');
        }
    } elseif ($source === 'contact') {
        $cm = $db->prepare("SELECT * FROM _contact_message WHERE id=?");
        $cm->execute([$msg_id]);
        $contact = $cm->fetch();
        if (!$contact) jOut(false, 'Mensagem não encontrada.');
        $orig_email   = $contact['email_msg'];
        $orig_subject = $contact['subject_msg'];
        $db->prepare("UPDATE _contact_message SET status_msg='replied', replied_at=NOW() WHERE id=?")->execute([$msg_id]);
    } elseif ($source === 'feedback') {
        $fb = $db->prepare("SELECT * FROM _feedback WHERE id=?");
        $fb->execute([$msg_id]);
        $feed = $fb->fetch();
        if (!$feed) jOut(false, 'Feedback não encontrado.');
        // Feedbacks geralmente não têm email — mas se tiver, enviar
        $orig_email   = $feed['email'] ?? '';
        $orig_subject = $feed['subject_fb'];
        $db->prepare("UPDATE _feedback SET status_fb='read' WHERE id=?")->execute([$msg_id]);
    }

    // Enviar email de resposta
    if ($orig_email && filter_var($orig_email, FILTER_VALIDATE_EMAIL)) {
        $re_subject = 'Re: ' . $orig_subject;
        $email_html = ib_email_template(
            $re_subject,
            nl2br(htmlspecialchars($body)) .
                '<br><br><span style="font-size:.78rem;color:#888">Esta mensagem foi enviada pela equipa da Wasom Upfy em resposta ao teu contacto.</span>'
        );
        $sent = ib_send_email($orig_email, $re_subject . ' — ' . APP_NAME, $email_html);
    } else {
        $sent = false;
    }

    logAudit($admin_id, $orig_user['id_users'] ?? null, 'support.replied', '_support_ticket', $msg_id);

    jOut(true, 'Resposta enviada com sucesso.' . ($sent ? '' : ' (Email não pôde ser enviado — verifique as configurações SMTP.)'));
}

// ════════════════════════════════════════════════════════════════════════════
// ACÇÃO: MUDAR STATUS
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'change_status') {
    if ($msg_id <= 0 || !$source) jOut(false, 'Dados inválidos.');
    $new_status = trim($_POST['new_status'] ?? '');

    try {
        if (str_starts_with($source, 'ticket_')) {
            $allowed = ['open', 'in_progress', 'resolved', 'closed'];
            if (!in_array($new_status, $allowed, true)) jOut(false, 'Estado inválido.');
            $resolved = in_array($new_status, ['resolved', 'closed']) ? ', resolved_at=NOW()' : '';
            $db->prepare("UPDATE _support_ticket SET status_ticket=? $resolved WHERE id_ticket=?")->execute([$new_status, $msg_id]);
        } elseif ($source === 'contact') {
            $allowed = ['new', 'read', 'replied', 'archived'];
            if (!in_array($new_status, $allowed, true)) jOut(false, 'Estado inválido.');
            $db->prepare("UPDATE _contact_message SET status_msg=? WHERE id=?")->execute([$new_status, $msg_id]);
        } elseif ($source === 'feedback') {
            $allowed = ['new', 'read', 'archived'];
            if (!in_array($new_status, $allowed, true)) jOut(false, 'Estado inválido.');
            $db->prepare("UPDATE _feedback SET status_fb=? WHERE id=?")->execute([$new_status, $msg_id]);
        }
        jOut(true, 'Estado actualizado.');
    } catch (Exception $e) {
        error_log('[INBOX STATUS] ' . $e->getMessage());
        jOut(false, 'Erro ao actualizar estado.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// ACÇÃO: ATRIBUIR
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'assign') {
    requirePermission($admin_id, 'support.view');
    if ($msg_id <= 0 || !str_starts_with($source, 'ticket_')) jOut(false, 'Apenas tickets podem ser atribuídos.');
    $to = (int)($_POST['assigned_to'] ?? 0) ?: null;
    try {
        $db->prepare("UPDATE _support_ticket SET assigned_to=? WHERE id_ticket=?")->execute([$to, $msg_id]);
        jOut(true, $to ? 'Ticket atribuído.' : 'Atribuição removida.');
    } catch (Exception $e) {
        jOut(false, 'Erro ao atribuir.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// ACÇÃO: ARQUIVAR
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'archive') {
    if ($msg_id <= 0 || !$source) jOut(false, 'Dados inválidos.');
    try {
        if (str_starts_with($source, 'ticket_')) {
            $db->prepare("UPDATE _support_ticket SET status_ticket='closed' WHERE id_ticket=?")->execute([$msg_id]);
        } elseif ($source === 'contact') {
            $db->prepare("UPDATE _contact_message SET status_msg='archived' WHERE id=?")->execute([$msg_id]);
        } elseif ($source === 'feedback') {
            $db->prepare("UPDATE _feedback SET status_fb='archived' WHERE id=?")->execute([$msg_id]);
        }
        jOut(true, 'Mensagem arquivada.');
    } catch (Exception $e) {
        jOut(false, 'Erro ao arquivar.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// ACÇÃO: ELIMINAR (soft delete via status)
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'delete_msg') {
    requirePermission($admin_id, 'support.view');
    if ($msg_id <= 0 || !$source) jOut(false, 'Dados inválidos.');
    try {
        if (str_starts_with($source, 'ticket_')) {
            // Soft delete — usar status especial ou apagar. Aqui apagamos se super_admin, senão arquivamos.
            if ($GLOBALS['admin_role'] === 'super_admin') {
                $db->prepare("DELETE FROM _support_ticket WHERE id_ticket=?")->execute([$msg_id]);
            } else {
                $db->prepare("UPDATE _support_ticket SET status_ticket='closed' WHERE id_ticket=?")->execute([$msg_id]);
            }
        } elseif ($source === 'contact') {
            $db->prepare("DELETE FROM _contact_message WHERE id=?")->execute([$msg_id]);
        } elseif ($source === 'feedback') {
            $db->prepare("DELETE FROM _feedback WHERE id=?")->execute([$msg_id]);
        }
        logAudit($admin_id, null, 'support.message_deleted', '_support', $msg_id);
        jOut(true, 'Mensagem eliminada.');
    } catch (Exception $e) {
        error_log('[INBOX DELETE] ' . $e->getMessage());
        jOut(false, 'Erro ao eliminar.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// ACÇÃO: COMPOSE (nova mensagem via email)
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'compose_email') {
    requirePermission($admin_id, 'support.view');

    $to      = trim($_POST['to']      ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body    = trim($_POST['body']    ?? '');

    if (!$to || !$subject || !$body) jOut(false, 'Preenche todos os campos.');
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) jOut(false, 'Email inválido.');
    if (mb_strlen($body, 'UTF-8') > 10000) jOut(false, 'Mensagem demasiado longa.');

    $html = ib_email_template(
        htmlspecialchars($subject),
        nl2br(htmlspecialchars($body)) .
            '<br><br><small style="color:#9ca3af">Esta mensagem foi enviada pela equipa da Wasom Upfy.</small>'
    );

    $sent = ib_send_email($to, $subject . ' — ' . APP_NAME, $html);

    logAudit($admin_id, null, 'support.email_sent', 'outbox', 0, null, json_encode(['to' => $to, 'subject' => $subject]));

    if ($sent) {
        jOut(true, 'Email enviado com sucesso para ' . $to . '.');
    } else {
        jOut(false, 'Erro ao enviar email. Verifica as configurações SMTP.');
    }
}

jOut(false, 'Acção desconhecida.');
