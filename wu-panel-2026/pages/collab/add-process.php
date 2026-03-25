<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Adição de Colaborador
// Arquivo: wu-panel-2026/pages/collab/add-process.php
// Rota:    wu-panel-2026/collab/add-process (POST only)
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'collaborators.edit');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adminRedirect('/' . ADMIN_PATH . '/collab');
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'])) {
    adminRedirect('/' . ADMIN_PATH . '/collab');
}

$action = trim($_POST['action'] ?? '');
if ($action !== 'add_collaborator') adminRedirect('/' . ADMIN_PATH . '/collab');

// ──────────────────────────────────────────────────────────────────────────────
// Campos obrigatórios
// ──────────────────────────────────────────────────────────────────────────────
$id_users   = (int)($_POST['id_users'] ?? 0);
$first_name = trim($_POST['first_name'] ?? '');
$email      = trim(strtolower($_POST['email_collab'] ?? ''));
$role       = $_POST['role_collab'] ?? 'editor';

if ($id_users <= 0 || empty($first_name) || empty($email)) {
    adminRedirect('/' . ADMIN_PATH . '/collab/add?msg=error');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    adminRedirect('/' . ADMIN_PATH . '/collab/add?msg=error');
}
if (!in_array($role, ['admin', 'editor', 'analyst', 'support'], true)) {
    $role = 'editor';
}

// Campos opcionais
$second_name = trim($_POST['second_name'] ?? '');
$tel         = trim($_POST['tel_collab']   ?? '');
$photo_url   = trim($_POST['photo_url']    ?? '');
$notes       = trim($_POST['notes']        ?? '');

// Validar URL da foto, se fornecida
if ($photo_url && !filter_var($photo_url, FILTER_VALIDATE_URL)) {
    $photo_url = null;
}

// ──────────────────────────────────────────────────────────────────────────────
// Verificar se o email já está em uso por outro colaborador deste utilizador
// ──────────────────────────────────────────────────────────────────────────────
$dup = $db->prepare("SELECT id_collab FROM _collaborators WHERE email_collab = ? AND id_users = ?");
$dup->execute([$email, $id_users]);
if ($dup->fetchColumn()) {
    adminRedirect('/' . ADMIN_PATH . '/collab/add?msg=dupe_email');
}

// Verificar se o utilizador já é um colaborador (opcional – pode ser útil)
// Neste caso, não impedimos, mas podemos avisar. Por enquanto, apenas verificamos email.

// ──────────────────────────────────────────────────────────────────────────────
// Buscar dados do proprietário (para o e-mail de convite)
// ──────────────────────────────────────────────────────────────────────────────
$owner = $db->prepare("SELECT first_name, second_name, email_user FROM _users WHERE id_users = ?");
$owner->execute([$id_users]);
$owner_data = $owner->fetch();
if (!$owner_data) {
    adminRedirect('/' . ADMIN_PATH . '/collab/add?msg=error');
}
$owner_name = trim($owner_data['first_name'] . ' ' . ($owner_data['second_name'] ?? ''));

// Buscar o plano do proprietário (para mostrar no e-mail)
$plan_stmt = $db->prepare("
    SELECT p.name_plan
    FROM _plans p
    JOIN _users u ON u.plan_selected = p.id_plan
    WHERE u.id_users = ?
");
$plan_stmt->execute([$id_users]);
$plan = $plan_stmt->fetch();
$plan_name = $plan ? $plan['name_plan'] : APP_NAME;

// ──────────────────────────────────────────────────────────────────────────────
// Gerar username único para o colaborador
// ──────────────────────────────────────────────────────────────────────────────
function collabUsernameExists(PDO $db, string $username, int $exclude_id = 0): bool {
    $s = $db->prepare("SELECT id_collab FROM _collaborators WHERE user_collab = ? AND id_collab != ?");
    $s->execute([$username, $exclude_id]);
    return (bool)$s->fetch();
}

function generateCollabUsername(PDO $db, string $first, string $second = ''): string {
    $base = strtolower(preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $first . $second)));
    $base = substr($base, 0, 14);
    $tries = 0;
    do {
        $u = $base . str_pad((string)rand(0, 999), 3, '0', STR_PAD_LEFT);
        $tries++;
        if ($tries > 20) { $u = $base . substr(time(), -5); break; }
    } while (collabUsernameExists($db, $u));
    return $u;
}

$user_collab = generateCollabUsername($db, $first_name, $second_name);

// ──────────────────────────────────────────────────────────────────────────────
// Gerar senha forte e hashear
// ──────────────────────────────────────────────────────────────────────────────
function generateStrongPassword(int $len = 16): string {
    $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lower   = 'abcdefghjkmnpqrstuvwxyz';
    $digits  = '23456789';
    $special = '@#$%&*!?';
    $all     = $upper . $lower . $digits . $special;
    $pwd  = $upper[random_int(0, strlen($upper)-1)];
    $pwd .= $lower[random_int(0, strlen($lower)-1)];
    $pwd .= $digits[random_int(0, strlen($digits)-1)];
    $pwd .= $special[random_int(0, strlen($special)-1)];
    for ($i = 4; $i < $len; $i++) {
        $pwd .= $all[random_int(0, strlen($all)-1)];
    }
    return str_shuffle($pwd);
}

$plain_password = generateStrongPassword(16);
$hash_password = password_hash($plain_password, PASSWORD_DEFAULT);

// ──────────────────────────────────────────────────────────────────────────────
// Gerar token de convite (128 caracteres hex)
// ──────────────────────────────────────────────────────────────────────────────
$invite_token   = bin2hex(random_bytes(64));
$invite_expires = date('Y-m-d H:i:s', strtotime('+72 hours'));

// ──────────────────────────────────────────────────────────────────────────────
// Inserir na base de dados
// ──────────────────────────────────────────────────────────────────────────────
try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO _collaborators (
            id_users, first_name, second_name, user_collab, email_collab, tel_collab,
            photo_collab, role_collab, password_collab, status_collab,
            invite_token, invite_token_used, invite_token_expires,
            must_change_password, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, 0, ?, 1, ?)
    ");
    $stmt->execute([
        $id_users,
        $first_name,
        $second_name ?: null,
        $user_collab,
        $email,
        $tel ?: null,
        $photo_url ?: null,
        $role,
        $hash_password,
        $invite_token,
        $invite_expires,
        $notes ?: null
    ]);

    $id_collab = (int)$db->lastInsertId();

    // Registar actividade no log do colaborador
    $db->prepare("
        INSERT INTO _collab_activity
            (id_collab, id_users, activity_type, description, ip_address)
        VALUES (?, ?, 'invite_sent', 'Convite enviado pelo administrador', ?)
    ")->execute([$id_collab, $id_users, $_SERVER['REMOTE_ADDR'] ?? null]);

    // Log de auditoria
    logAudit($admin_id, $id_users, 'collaborator.created', '_collaborators', $id_collab);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    error_log('[COLLAB ADD] ' . $e->getMessage());
    adminRedirect('/' . ADMIN_PATH . '/collab/add?msg=error');
}

// ──────────────────────────────────────────────────────────────────────────────
// Enviar e-mail de convite
// ──────────────────────────────────────────────────────────────────────────────
function sendCollabInviteEmail(
    string $email, string $first_name, string $user_collab, string $plain_password,
    string $role_label, string $owner_name, string $owner_plan, string $activate_url
): void {
    $subject = "Convite para a equipa — " . APP_NAME;
    $body = "
    <div style='font-family:Arial,sans-serif;max-width:620px;margin:0 auto'>
        <div style='background:linear-gradient(135deg,#FF0089,#FF4D4D);padding:28px 36px;border-radius:16px 16px 0 0'>
            <h1 style='color:#fff;margin:0;font-size:1.5rem;font-weight:800'>".APP_NAME."</h1>
            <p style='color:rgba(255,255,255,.8);margin:6px 0 0;font-size:.9rem'>Convite de equipa</p>
        </div>
        <div style='background:#fff;padding:32px 36px;border:1px solid #f0f0f0;border-top:none;border-radius:0 0 16px 16px'>
            <h2 style='color:#222;font-size:1.15rem;margin-bottom:4px'>Olá, <strong>".htmlspecialchars($first_name)."</strong>!</h2>
            <p style='color:#555;line-height:1.7;margin-top:0'>
                <strong>".htmlspecialchars($owner_name)."</strong> convidou-te para fazer parte da equipa
                <strong>".APP_NAME."</strong> como <strong>".htmlspecialchars($role_label)."</strong>.
            </p>

            <div style='background:#f9f0f5;border-left:4px solid #FF0089;border-radius:8px;padding:18px 20px;margin:20px 0'>
                <p style='margin:0 0 10px;font-weight:700;color:#222;font-size:.9rem'>📋 Os teus dados de acesso:</p>
                <table style='width:100%;border-collapse:collapse;font-size:.88rem'>
                    <tr><td style='color:#888;padding:4px 0;width:140px'>Utilizador:</td><td style='font-weight:700;color:#222'>@".htmlspecialchars($user_collab)."</td></tr>
                    <tr><td style='color:#888;padding:4px 0'>Email:</td><td style='font-weight:700;color:#222'>".htmlspecialchars($email)."</td></tr>
                    <tr><td style='color:#888;padding:4px 0'>Senha temporária:</td><td style='font-family:monospace;font-weight:700;color:#FF0089;font-size:1rem;letter-spacing:1px'>".htmlspecialchars($plain_password)."</td></tr>
                    <tr><td style='color:#888;padding:4px 0'>Função:</td><td style='font-weight:700;color:#222'>".htmlspecialchars($role_label)."</td></tr>
                    <tr><td style='color:#888;padding:4px 0'>Plano da conta:</td><td style='font-weight:700;color:#222'>".htmlspecialchars($owner_plan)."</td></tr>
                </table>
            </div>

            <div style='background:#fff8e6;border:1px solid #ffc107;border-radius:8px;padding:12px 16px;margin-bottom:24px;font-size:.83rem;color:#856404'>
                <strong> ⚠️ Atenção:</strong> Este link de activação é de uso único e expira em 72 horas.
                Após o primeiro login, serás solicitado a alterar a tua senha.
            </div>

            <div style='text-align:center;margin:28px 0'>
                <a href='".htmlspecialchars($activate_url)."'
                   style='background:linear-gradient(135deg,#FF0089,#FF4D4D);color:#fff;padding:14px 36px;border-radius:50px;text-decoration:none;font-weight:800;font-size:.95rem;display:inline-block;letter-spacing:.3px'>
                    ✅ Activar a minha conta
                </a>
            </div>

            <p style='color:#aaa;font-size:.78rem;text-align:center;margin-top:24px'>
                Se não reconheces este convite, ignora este email.<br/>
                ".APP_NAME." &mdash; Não respondas a este email.
            </p>
        </div>
    </div>";
    sendEmail($email, $subject, $body);
}

$activate_url = rtrim(APP_URL, '/') . '/dashboard/account/collab-activate?token=' . urlencode($invite_token);
$role_labels  = ['admin' => 'Administrador', 'editor' => 'Editor', 'analyst' => 'Analista', 'support' => 'Suporte'];
$role_label   = $role_labels[$role] ?? 'Editor';

try {
    sendCollabInviteEmail($email, $first_name, $user_collab, $plain_password, $role_label, $owner_name, $plan_name, $activate_url);
} catch (Exception $e) {
    error_log('[COLLAB ADD EMAIL] ' . $e->getMessage());
    // Não paramos o fluxo, apenas registamos o erro
}

// ──────────────────────────────────────────────────────────────────────────────
// Redirecionar com sucesso
// ──────────────────────────────────────────────────────────────────────────────
adminRedirect('/' . ADMIN_PATH . '/collab/view?id=' . $id_collab . '&msg=invite_sent');