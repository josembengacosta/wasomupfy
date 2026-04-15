<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Gestão de Conta
// Arquivo: dashboard/account/manage_account_process.php
// ══════════════════════════════════════════════
ob_start();
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
requireLogin();

function jsonOut(bool $ok, string $msg, array $extra = []): never
{
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(false, 'Método não permitido.');
if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    jsonOut(false, 'Sessão expirada. Recarrega a página.', ['reload' => true]);
}

$id_users = (int)$_SESSION['id_users'];
$action   = $_POST['action'] ?? '';
$db       = getDB();
$user     = getUserById($id_users);
if (!$user) jsonOut(false, 'Utilizador não encontrado.');

// ── Plan name (para emails) ───────────────────
$plan = null;
if ($user['plan_selected']) {
    $ps = $db->prepare('SELECT name_plan FROM _plans WHERE id_plan = ?');
    $ps->execute([$user['plan_selected']]);
    $plan = $ps->fetch();
}

// ── Password generator ─────────────────────────
function generateStrongPassword(int $len = 16): string
{
    $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lower   = 'abcdefghjkmnpqrstuvwxyz';
    $digits  = '23456789';
    $special = '@#$%&*!?';
    $all     = $upper . $lower . $digits . $special;
    // Garantir pelo menos 1 de cada
    $pwd  = $upper[random_int(0, strlen($upper) - 1)];
    $pwd .= $lower[random_int(0, strlen($lower) - 1)];
    $pwd .= $digits[random_int(0, strlen($digits) - 1)];
    $pwd .= $special[random_int(0, strlen($special) - 1)];
    for ($i = 4; $i < $len; $i++) {
        $pwd .= $all[random_int(0, strlen($all) - 1)];
    }
    return str_shuffle($pwd);
}

// ── Collab username unique ─────────────────────
function collabUsernameExists(PDO $db, string $username, int $exclude_id = 0): bool
{
    $s = $db->prepare("SELECT id_collab FROM _collaborators WHERE user_collab = ? AND id_collab != ?");
    $s->execute([$username, $exclude_id]);
    return (bool)$s->fetch();
}

function generateCollabUsername(PDO $db, string $first, string $second = ''): string
{
    $base = strtolower(preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $first . $second)));
    $base = substr($base, 0, 14);
    $tries = 0;
    do {
        $u = $base . str_pad((string)rand(0, 999), 3, '0', STR_PAD_LEFT);
        $tries++;
        if ($tries > 20) {
            $u = $base . substr(time(), -5);
            break;
        }
    } while (collabUsernameExists($db, $u));
    return $u;
}

// ── Email invite ───────────────────────────────
function sendCollabInviteEmail(
    string $email,
    string $first_name,
    string $user_collab,
    string $plain_password,
    string $role_label,
    string $owner_name,
    string $owner_plan,
    string $activate_url
): void {
    $subject = "Convite para a equipa — " . APP_NAME;
    $body = "
    <div style='font-family:Arial,sans-serif;max-width:620px;margin:0 auto'>
        <div style='background:linear-gradient(135deg,#FF0089,#FF4D4D);padding:28px 36px;border-radius:16px 16px 0 0'>
            <h1 style='color:#fff;margin:0;font-size:1.5rem;font-weight:800'>" . APP_NAME . "</h1>
            <p style='color:rgba(255,255,255,.8);margin:6px 0 0;font-size:.9rem'>Convite de equipa</p>
        </div>
        <div style='background:#fff;padding:32px 36px;border:1px solid #f0f0f0;border-top:none;border-radius:0 0 16px 16px'>
            <h2 style='color:#222;font-size:1.15rem;margin-bottom:4px'>Olá, <strong>" . htmlspecialchars($first_name) . "</strong>! 👋</h2>
            <p style='color:#555;line-height:1.7;margin-top:0'>
                <strong>" . htmlspecialchars($owner_name) . "</strong> convidou-te para fazer parte da equipa
                <strong>" . APP_NAME . "</strong> como <strong>" . htmlspecialchars($role_label) . "</strong>.
            </p>

            <div style='background:#f9f0f5;border-left:4px solid #FF0089;border-radius:8px;padding:18px 20px;margin:20px 0'>
                <p style='margin:0 0 10px;font-weight:700;color:#222;font-size:.9rem'>📋 Os teus dados de acesso:</p>
                <table style='width:100%;border-collapse:collapse;font-size:.88rem'>
                    <tr><td style='color:#888;padding:4px 0;width:140px'>Utilizador:</td><td style='font-weight:700;color:#222'>@" . htmlspecialchars($user_collab) . "</td></tr>
                    <tr><td style='color:#888;padding:4px 0'>Email:</td><td style='font-weight:700;color:#222'>" . htmlspecialchars($email) . "</td></tr>
                    <tr><td style='color:#888;padding:4px 0'>Senha temporária:</td><td style='font-family:monospace;font-weight:700;color:#FF0089;font-size:1rem;letter-spacing:1px'>" . htmlspecialchars($plain_password) . "</td></tr>
                    <tr><td style='color:#888;padding:4px 0'>Função:</td><td style='font-weight:700;color:#222'>" . htmlspecialchars($role_label) . "</td></tr>
                    <tr><td style='color:#888;padding:4px 0'>Plano da conta:</td><td style='font-weight:700;color:#222'>" . htmlspecialchars($owner_plan) . "</td></tr>
                </table>
            </div>

            <div style='background:#fff8e6;border:1px solid #ffc107;border-radius:8px;padding:12px 16px;margin-bottom:24px;font-size:.83rem;color:#856404'>
                <strong> ⚠️ Atenção:</strong> Este link de activação é de uso único e expira em 72 horas.
                Após o primeiro login, serás solicitado a alterar a tua senha.
            </div>

            <div style='text-align:center;margin:28px 0'>
                <a href='" . htmlspecialchars($activate_url) . "'
                   style='background:linear-gradient(135deg,#FF0089,#FF4D4D);color:#fff;padding:14px 36px;border-radius:50px;text-decoration:none;font-weight:800;font-size:.95rem;display:inline-block;letter-spacing:.3px'>
                    ✅ Activar a minha conta
                </a>
            </div>

            <p style='color:#aaa;font-size:.78rem;text-align:center;margin-top:24px'>
                Se não reconheces este convite, ignora este email.<br/>
                " . APP_NAME . " &mdash; Não respondas a este email.
            </p>
        </div>
    </div>";
    sendEmail($email, $subject, $body);
}

// ── logCollab activity ──────────────────────────
function logCollabActivity(PDO $db, int $id_collab, int $id_users, string $type, string $desc): void
{
    try {
        $db->prepare("
            INSERT INTO _collab_activity (id_collab, id_users, activity_type, description, ip_address)
            VALUES (?,?,?,?,?)
        ")->execute([$id_collab, $id_users, $type, $desc, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

// ════════════════════════════════════════════
switch ($action) {

    // ──────────────────────────────────────────
    // UPDATE ACCOUNT PROFILE (artist/band info)
    // ──────────────────────────────────────────
    case 'update_account_profile':
        $name_artist_band = trim($_POST['name_artist_band'] ?? '');
        $about_user       = trim($_POST['about_user']       ?? '');
        $url_user         = trim($_POST['url_user']         ?? '');
        $tel_user         = trim($_POST['tel_user']         ?? '');
        $country_user     = trim($_POST['country_user']     ?? '');
        $city_user        = trim($_POST['city_user']        ?? '');

        if (strlen($name_artist_band) > 100) jsonOut(false, 'Nome artístico demasiado longo (máx. 100).');
        if (strlen($about_user) > 1000) jsonOut(false, 'Bio demasiado longa (máx. 1000).');

        $db->prepare("
        UPDATE _users SET
            name_artist_band = ?, about_user = ?, url_user = ?,
            tel_user = ?, country_user = ?, city_user = ?
        WHERE id_users = ?
    ")->execute([
            $name_artist_band ?: null,
            $about_user ?: null,
            $url_user ?: null,
            $tel_user ?: null,
            $country_user ?: null,
            $city_user ?: null,
            $id_users
        ]);

        logActivity($id_users, 'account_profile_updated', 'Perfil de conta actualizado');
        jsonOut(true, 'Perfil actualizado com sucesso!');

        // ──────────────────────────────────────────
        // GENERATE STRONG PASSWORD
        // ──────────────────────────────────────────
    case 'generate_password':
        $pwd = generateStrongPassword(18);
        jsonOut(true, 'ok', ['password' => $pwd]);

        // ──────────────────────────────────────────
        // ADD COLLABORATOR
        // ──────────────────────────────────────────
    case 'add_collaborator':
        $first_name  = trim($_POST['first_name']   ?? '');
        $second_name = trim($_POST['second_name']  ?? '');
        $email       = trim(strtolower($_POST['email_collab'] ?? ''));
        $tel         = trim($_POST['tel_collab']   ?? '');
        $photo_url   = trim($_POST['photo_url']    ?? '');
        $role        = $_POST['role_collab']       ?? 'editor';
        $plain_pwd   = $_POST['plain_password']    ?? '';
        $notes       = trim($_POST['notes']        ?? '');

        // Validate
        if (empty($first_name)) jsonOut(false, 'O nome é obrigatório.');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) jsonOut(false, 'Email inválido.');
        if (!in_array($role, ['admin', 'editor', 'analyst', 'support'])) jsonOut(false, 'Função inválida.');
        if (strlen($plain_pwd) < 8) jsonOut(false, 'A senha deve ter pelo menos 8 caracteres.');


        // ─── Verificar limite de colaboradores do plano ─────────────────
        $plan_stmt = $db->prepare("
        SELECT p.max_collaborators
        FROM _users u
        JOIN _plans p ON p.id_plan = u.plan_selected
        WHERE u.id_users = ? AND u.status_user = 'active' AND u.plan_activated_at IS NOT NULL
    ");
        $plan_stmt->execute([$id_users]);
        $max_collab = $plan_stmt->fetchColumn();

        if ($max_collab === false) {
            // Fallback: plano selecionado mesmo se não estiver ativo
            $plan_stmt = $db->prepare("SELECT max_collaborators FROM _plans WHERE id_plan = ?");
            $plan_stmt->execute([$user['plan_selected']]);
            $max_collab = $plan_stmt->fetchColumn();
        }
        $max_collab = (int)($max_collab ?: 1); // padrão 1

        // Contar colaboradores actuais do utilizador
        $count_stmt = $db->prepare("SELECT COUNT(*) FROM _collaborators WHERE id_users = ?");
        $count_stmt->execute([$id_users]);
        $current_count = (int)$count_stmt->fetchColumn();

        if ($current_count >= $max_collab) {
            jsonOut(false, "Atingiste o limite de {$max_collab} colaborador(es) do teu plano. Faz upgrade para adicionar mais.");
        }

        // Check email not already a collaborator of this account
        $dup = $db->prepare("SELECT id_collab FROM _collaborators WHERE email_collab = ? AND id_users = ?");

        $dup->execute([$email, $id_users]);
        if ($dup->fetch()) jsonOut(false, "Já existe um colaborador com o email «{$email}» nesta conta.");

        // Check email not a platform user
        if (getUserByEmail($email)) {
            jsonOut(false, 'Este email já pertence a um utilizador registado na plataforma. Usa outro email.');
        }

        // Generate username
        $user_collab = generateCollabUsername($db, $first_name, $second_name);

        // Hash password
        $hash_pwd = password_hash($plain_pwd, PASSWORD_DEFAULT);

        // Generate one-time invite token (128 char hex)
        $invite_token   = bin2hex(random_bytes(64));
        $invite_expires = date('Y-m-d H:i:s', strtotime('+72 hours'));

        // Photo validation (must be URL or empty)
        if ($photo_url && !filter_var($photo_url, FILTER_VALIDATE_URL)) {
            $photo_url = null;
        }

        $db->prepare("
        INSERT INTO _collaborators
            (id_users, first_name, second_name, user_collab, email_collab, tel_collab,
             photo_collab, role_collab, password_collab, status_collab,
             invite_token, invite_token_used, invite_token_expires,
             must_change_password, notes)
        VALUES (?,?,?,?,?,?,?,?,?,'pending',?,0,?,1,?)
    ")->execute([
            $id_users,
            $first_name,
            $second_name ?: null,
            $user_collab,
            $email,
            $tel ?: null,
            $photo_url ?: null,
            $role,
            $hash_pwd,
            $invite_token,
            $invite_expires,
            $notes ?: null
        ]);

        $new_id = (int)$db->lastInsertId();

        // Send invite email
        $activate_url = APP_URL . '/' . APP_URL_PANEL . '/account/collab-activate?token=' . urlencode($invite_token);
        $role_labels  = ['admin' => 'Administrador', 'editor' => 'Editor', 'analyst' => 'Analista', 'support' => 'Suporte'];
        $role_label   = $role_labels[$role] ?? 'Editor';
        $owner_name   = trim($user['first_name'] . ' ' . ($user['second_name'] ?? ''));
        $plan_name    = $plan ? $plan['name_plan'] : APP_NAME;

        try {
            sendCollabInviteEmail($email, $first_name, $user_collab, $plain_pwd, $role_label, $owner_name, $plan_name, $activate_url);
        } catch (Exception $e) {
            error_log('[COLLAB EMAIL] ' . $e->getMessage());
        }

        logActivity($id_users, 'collaborator_added', "Colaborador adicionado: {$first_name} ({$role_label})", 'collaborator', $new_id);
        logCollabActivity($db, $new_id, $id_users, 'invite_sent', 'Convite enviado por email');

        jsonOut(true, "Colaborador adicionado! Um email de convite foi enviado para {$email}.", [
            'id_collab' => $new_id,
            'username'  => $user_collab,
        ]);

        // ──────────────────────────────────────────
        // EDIT COLLABORATOR
        // ──────────────────────────────────────────
    case 'edit_collaborator':
        $id_collab   = (int)($_POST['id_collab'] ?? 0);
        $first_name  = trim($_POST['first_name']  ?? '');
        $second_name = trim($_POST['second_name'] ?? '');
        $tel         = trim($_POST['tel_collab']  ?? '');
        $photo_url   = trim($_POST['photo_url']   ?? '');
        $role        = $_POST['role_collab']      ?? 'editor';
        $notes       = trim($_POST['notes']       ?? '');
        $new_pwd     = trim($_POST['new_password'] ?? '');

        if (!$id_collab) jsonOut(false, 'ID inválido.');
        if (empty($first_name)) jsonOut(false, 'O nome é obrigatório.');
        if (!in_array($role, ['admin', 'editor', 'analyst', 'support'])) jsonOut(false, 'Função inválida.');

        // Verify belongs to this account
        $ck = $db->prepare("SELECT * FROM _collaborators WHERE id_collab = ? AND id_users = ?");
        $ck->execute([$id_collab, $id_users]);
        $collab = $ck->fetch();
        if (!$collab) jsonOut(false, 'Colaborador não encontrado ou sem permissão.');

        if ($photo_url && !filter_var($photo_url, FILTER_VALIDATE_URL)) $photo_url = null;

        // Optional password change
        $hash_pwd = $collab['password_collab'];
        $resend_invite = false;
        if (!empty($new_pwd)) {
            if (strlen($new_pwd) < 8) jsonOut(false, 'A nova senha deve ter pelo menos 8 caracteres.');
            $hash_pwd     = password_hash($new_pwd, PASSWORD_DEFAULT);
            $resend_invite = true;
        }

        $db->prepare("
        UPDATE _collaborators SET
            first_name = ?, second_name = ?, tel_collab = ?,
            photo_collab = ?, role_collab = ?, password_collab = ?, notes = ?
        WHERE id_collab = ? AND id_users = ?
    ")->execute([
            $first_name,
            $second_name ?: null,
            $tel ?: null,
            $photo_url ?: $collab['photo_collab'],
            $role,
            $hash_pwd,
            $notes ?: null,
            $id_collab,
            $id_users
        ]);

        logActivity($id_users, 'collaborator_updated', "Colaborador actualizado: {$collab['first_name']} (#{$id_collab})", 'collaborator', $id_collab);
        logCollabActivity($db, $id_collab, $id_users, 'profile_edited', 'Perfil editado pelo proprietário');

        $msg = 'Colaborador actualizado com sucesso!';
        if ($resend_invite) {
            // Notify collaborator about password change
            $role_labels = ['admin' => 'Administrador', 'editor' => 'Editor', 'analyst' => 'Analista', 'support' => 'Suporte'];
            $body = "<div style='font-family:Arial,sans-serif;max-width:540px;margin:auto'>
            <div style='background:linear-gradient(135deg,#FF0089,#FF4D4D);padding:24px 32px;border-radius:8px 8px 0 0'>
                <h1 style='color:#fff;margin:0;font-size:1.3rem'>" . APP_NAME . "</h1>
            </div>
            <div style='background:#fff;padding:28px 32px;border:1px solid #eee;border-top:none;border-radius:0 0 8px 8px'>
                <h2 style='color:#222;font-size:1rem'>Senha actualizada</h2>
                <p>Olá <strong>" . htmlspecialchars($first_name) . "</strong>,<br/>
                A tua senha de acesso ao " . APP_NAME . " foi alterada pelo administrador da conta.<br/>
                <strong>Nova senha temporária:</strong> <span style='font-family:monospace;color:#FF0089;font-size:1.1rem'>" . htmlspecialchars($new_pwd) . "</span></p>
                <p><a href='" . APP_URL . '/' . APP_URL_PANEL . "/account/collab-login' style='background:#FF0089;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none'>Entrar no painel</a></p>
                <p style='color:#999;font-size:.8rem'>" . APP_NAME . " &mdash; Não respondas.</p>
            </div>
        </div>";
            try {
                sendEmail($collab['email_collab'], 'Senha actualizada — ' . APP_NAME, $body);
            } catch (Exception $e) {
            }
            $msg .= ' Novo email com senha enviado.';
        }

        jsonOut(true, $msg);

        // ──────────────────────────────────────────
        // TOGGLE STATUS (active/blocked)
        // ──────────────────────────────────────────
    case 'toggle_collab_status':
        $id_collab  = (int)($_POST['id_collab']  ?? 0);
        $new_status = $_POST['new_status'] ?? '';

        if (!$id_collab) jsonOut(false, 'ID inválido.');
        if (!in_array($new_status, ['active', 'blocked', 'inactive'])) jsonOut(false, 'Estado inválido.');

        $ck = $db->prepare("SELECT * FROM _collaborators WHERE id_collab = ? AND id_users = ?");
        $ck->execute([$id_collab, $id_users]);
        $collab = $ck->fetch();
        if (!$collab) jsonOut(false, 'Colaborador não encontrado.');

        $db->prepare("UPDATE _collaborators SET status_collab = ? WHERE id_collab = ? AND id_users = ?")
            ->execute([$new_status, $id_collab, $id_users]);

        logActivity($id_users, 'collaborator_status_changed', "Estado de colaborador alterado para {$new_status} (#{$id_collab})", 'collaborator', $id_collab);
        logCollabActivity($db, $id_collab, $id_users, 'status_changed', "Estado alterado para {$new_status} pelo proprietário");

        $labels = ['active' => 'activado', 'blocked' => 'bloqueado', 'inactive' => 'desactivado'];
        jsonOut(true, 'Colaborador ' . ($labels[$new_status] ?? $new_status) . '.');

        // ──────────────────────────────────────────
        // RESEND INVITE
        // ──────────────────────────────────────────
    case 'resend_invite':
        $id_collab = (int)($_POST['id_collab'] ?? 0);
        $ck = $db->prepare("SELECT * FROM _collaborators WHERE id_collab = ? AND id_users = ?");
        $ck->execute([$id_collab, $id_users]);
        $collab = $ck->fetch();
        if (!$collab) jsonOut(false, 'Colaborador não encontrado.');
        if ($collab['invite_token_used']) jsonOut(false, 'Este colaborador já activou a conta.');

        // Generate new token
        $new_token   = bin2hex(random_bytes(64));
        $new_expires = date('Y-m-d H:i:s', strtotime('+72 hours'));
        $db->prepare("UPDATE _collaborators SET invite_token = ?, invite_token_expires = ? WHERE id_collab = ?")
            ->execute([$new_token, $new_expires, $id_collab]);

        $activate_url = APP_URL . '/' . APP_URL_PANEL . '/account/collab-activate?token=' . urlencode($new_token);
        $role_labels  = ['admin' => 'Administrador', 'editor' => 'Editor', 'analyst' => 'Analista', 'support' => 'Suporte'];
        $owner_name   = trim($user['first_name'] . ' ' . ($user['second_name'] ?? ''));
        $plan_name    = $plan ? $plan['name_plan'] : APP_NAME;

        // Need plain password — can't un-hash, send a fresh one
        $new_pwd  = generateStrongPassword(16);
        $db->prepare("UPDATE _collaborators SET password_collab = ?, must_change_password = 1 WHERE id_collab = ?")
            ->execute([password_hash($new_pwd, PASSWORD_DEFAULT), $id_collab]);

        try {
            sendCollabInviteEmail(
                $collab['email_collab'],
                $collab['first_name'],
                $collab['user_collab'],
                $new_pwd,
                $role_labels[$collab['role_collab']] ?? 'Editor',
                $owner_name,
                $plan_name,
                $activate_url
            );
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        logCollabActivity($db, $id_collab, $id_users, 'invite_resent', 'Convite reenviado');
        jsonOut(true, 'Convite reenviado com nova senha temporária!');

        // ──────────────────────────────────────────
        // DELETE COLLABORATOR
        // ──────────────────────────────────────────
    case 'delete_collaborator':
        $id_collab = (int)($_POST['id_collab']       ?? 0);
        $pwd       = $_POST['password_confirm']       ?? '';

        if (!$id_collab) jsonOut(false, 'ID inválido.');
        if (!password_verify($pwd, $user['password_user'])) jsonOut(false, 'Senha incorrecta.');

        $ck = $db->prepare("SELECT * FROM _collaborators WHERE id_collab = ? AND id_users = ?");
        $ck->execute([$id_collab, $id_users]);
        $collab = $ck->fetch();
        if (!$collab) jsonOut(false, 'Colaborador não encontrado ou sem permissão.');

        // Delete (cascade handles sessions + activities)
        $db->prepare("DELETE FROM _collaborators WHERE id_collab = ? AND id_users = ?")->execute([$id_collab, $id_users]);

        // Notify
        try {
            $body = "<div style='font-family:Arial,sans-serif;max-width:540px;margin:auto'>
            <div style='background:#555;padding:24px 32px;border-radius:8px 8px 0 0'>
                <h1 style='color:#fff;margin:0'>" . APP_NAME . "</h1>
            </div>
            <div style='background:#fff;padding:28px 32px;border:1px solid #eee;border-top:none;border-radius:0 0 8px 8px'>
                <p>Olá <strong>" . htmlspecialchars($collab['first_name']) . "</strong>,<br/>
                O teu acesso como colaborador foi removido. Contacta o gestor da conta se tiveres dúvidas.</p>
                <p style='color:#999;font-size:.8rem'>" . APP_NAME . " &mdash; Não respondas.</p>
            </div>
        </div>";
            sendEmail($collab['email_collab'], 'Acesso removido — ' . APP_NAME, $body);
        } catch (Exception $e) {
        }

        logActivity($id_users, 'collaborator_deleted', "Colaborador eliminado: {$collab['first_name']} (#{$id_collab})", 'collaborator', $id_collab);
        jsonOut(true, 'Colaborador eliminado com sucesso.');

        // ──────────────────────────────────────────
        // GET COLLAB ACTIVITIES
        // ──────────────────────────────────────────
    case 'get_collab_activities':
        $id_collab = (int)($_POST['id_collab'] ?? 0);
        $ck = $db->prepare("SELECT id_collab FROM _collaborators WHERE id_collab = ? AND id_users = ?");
        $ck->execute([$id_collab, $id_users]);
        if (!$ck->fetch()) jsonOut(false, 'Sem permissão.');

        $acts = $db->prepare("
        SELECT activity_type, description, ip_address, creat_activity
        FROM _collab_activity WHERE id_collab = ?
        ORDER BY creat_activity DESC LIMIT 50
    ");
        $acts->execute([$id_collab]);
        jsonOut(true, 'ok', ['activities' => $acts->fetchAll(PDO::FETCH_ASSOC)]);

    default:
        jsonOut(false, 'Acção desconhecida.');
}