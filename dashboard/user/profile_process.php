<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Processar Perfil
// Arquivo: dashboard/user/profile_process.php
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

// ════════════════════════════════════════════
switch ($action) {

    // ──────────────────────────────────────────
    // CHECK USERNAME — sugestão em tempo real
    // ──────────────────────────────────────────
    case 'check_username':
        $uname = trim(strtolower($_POST['username'] ?? ''));
        $uname = preg_replace('/[^a-z0-9_.]/', '', $uname);

        if (strlen($uname) < 3)  jsonOut(false, 'Mínimo 3 caracteres.');
        if (strlen($uname) > 40) jsonOut(false, 'Máximo 40 caracteres.');

        // É o próprio?
        if ($uname === $user['user_name']) jsonOut(true, 'Este é o teu nome de utilizador actual.', ['available' => true]);

        $st = $db->prepare("SELECT id_users FROM _users WHERE user_name = ? AND id_users != ?");
        $st->execute([$uname, $id_users]);
        if ($st->fetch()) {
            // Gerar sugestões
            $base = preg_replace('/[^a-z0-9]/', '', strtolower(removeAccents($user['first_name'] . ($user['second_name'] ?? ''))));
            $sugs = [];
            for ($i = 0; $i < 3; $i++) {
                $s = $base . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
                if (!usernameExists($s)) $sugs[] = $s;
            }
            jsonOut(false, "«{$uname}» já está em uso.", ['available' => false, 'suggestions' => $sugs]);
        }
        jsonOut(true, 'Disponível!', ['available' => true]);

        // ──────────────────────────────────────────
        // UPDATE PROFILE
        // ──────────────────────────────────────────
    case 'update_profile':
        $first_name      = trim($_POST['first_name']      ?? '');
        $second_name     = trim($_POST['second_name']     ?? '');
        $user_name_new   = trim(strtolower($_POST['user_name'] ?? ''));
        $user_name_new   = preg_replace('/[^a-z0-9_.]/', '', $user_name_new);
        $tel_user        = trim($_POST['tel_user']        ?? '');
        $country_user    = trim($_POST['country_user']    ?? '');
        $city_user       = trim($_POST['city_user']       ?? '');
        $about_user      = trim($_POST['about_user']      ?? '');
        $url_user        = trim($_POST['url_user']        ?? '');
        $name_artist_band = trim($_POST['name_artist_band'] ?? '');

        if (empty($first_name)) jsonOut(false, 'O primeiro nome é obrigatório.');
        if (strlen($user_name_new) < 3) jsonOut(false, 'O nome de utilizador deve ter pelo menos 3 caracteres.');

        // Username único
        if ($user_name_new !== $user['user_name']) {
            $ck = $db->prepare("SELECT id_users FROM _users WHERE user_name = ? AND id_users != ?");
            $ck->execute([$user_name_new, $id_users]);
            if ($ck->fetch()) jsonOut(false, "O nome de utilizador «{$user_name_new}» já está em uso.");
        }

        // Photo upload
        $photo_path = $user['photo_user'];
        if (!empty($_FILES['photo_user']['name']) && $_FILES['photo_user']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo_user'];
            $mime = mime_content_type($file['tmp_name']);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
                jsonOut(false, 'Formato inválido. Usa JPG, PNG ou WebP.');
            }
            if ($file['size'] > 5 * 1024 * 1024) jsonOut(false, 'Imagem demasiado grande (máx. 5 MB).');

            $ext  = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
            $dir  = __DIR__ . '/../../assets/comprovantes/uploads/users/';
            if (!is_dir($dir)) mkdir($dir, 0750, true);

            // Apagar foto anterior
            if ($photo_path) {
                $old = $dir . $photo_path;
                if (file_exists($old)) @unlink($old);
            }
            $filename   = 'user_' . $id_users . '_' . time() . '.' . $ext;
            $photo_path = $filename;
            move_uploaded_file($file['tmp_name'], $dir . $filename);
        }

        $db->prepare("
        UPDATE _users SET
            first_name = ?, second_name = ?, user_name = ?,
            tel_user = ?, country_user = ?, city_user = ?,
            about_user = ?, url_user = ?,
            name_artist_band = ?, photo_user = ?
        WHERE id_users = ?
    ")->execute([
            $first_name,
            $second_name ?: null,
            $user_name_new,
            $tel_user ?: null,
            $country_user ?: null,
            $city_user ?: null,
            $about_user ?: null,
            $url_user ?: null,
            $name_artist_band ?: null,
            $photo_path,
            $id_users
        ]);

        logActivity($id_users, 'profile_updated', 'Perfil actualizado');
        jsonOut(true, 'Perfil actualizado com sucesso!', [
            'photo_url' => $photo_path ? rtrim(APP_URL, '/') . '/assets/comprovantes/uploads/users/' . $photo_path : null,
            'name'      => $first_name . ($second_name ? ' ' . $second_name : ''),
            'username'  => $user_name_new,
        ]);

        // ──────────────────────────────────────────
        // RESEND VERIFICATION EMAIL
        // ──────────────────────────────────────────
    case 'resend_verify_email':
        if ($user['email_verified']) jsonOut(false, 'O teu email já está verificado.');

        // Verificar cooldown (máx 1 por 5 min)
        $ck = $db->prepare("
        SELECT creat_token FROM _users_tokens
        WHERE id_users = ? AND type = 'email_verify' AND is_used = 0
        ORDER BY creat_token DESC LIMIT 1
    ");
        $ck->execute([$id_users]);
        $last = $ck->fetch();
        if ($last && (time() - strtotime($last['creat_token'])) < 300) {
            jsonOut(false, 'Aguarda 5 minutos antes de solicitar outro email.');
        }

        // Invalidar tokens antigos
        $db->prepare("UPDATE _users_tokens SET is_used=1 WHERE id_users=? AND type='email_verify' AND is_used=0")
            ->execute([$id_users]);

        createToken($id_users, 'email_verify', 48);

        // Buscar o token real (64 chars) na BD — igual ao register_process.php
        $tk_stmt = $db->prepare("
        SELECT token FROM _users_tokens
        WHERE id_users = ? AND type = 'email_verify' AND is_used = 0
        ORDER BY id_token DESC LIMIT 1
    ");
        $tk_stmt->execute([$id_users]);
        $tk_row = $tk_stmt->fetch();

        if (!$tk_row) jsonOut(false, 'Erro ao gerar o token. Tenta novamente.');

        $name = $user['first_name'] . ($user['second_name'] ? ' ' . $user['second_name'] : '');
        sendVerificationEmail($user['email_user'], $name, $tk_row['token']);

        logActivity($id_users, 'verify_email_resent', 'Email de verificação reenviado');
        jsonOut(true, 'Email de verificação enviado! Verifica a tua caixa de entrada (e o spam).');

        // ──────────────────────────────────────────
        // CHANGE PASSWORD
        // ──────────────────────────────────────────
    case 'change_password':
        $old_pass  = $_POST['old_password']     ?? '';
        $new_pass  = $_POST['new_password']     ?? '';
        $conf_pass = $_POST['confirm_password'] ?? '';

        if (!password_verify($old_pass, $user['password_user'])) {
            jsonOut(false, 'A senha actual está incorrecta.');
        }
        if (strlen($new_pass) < 8) jsonOut(false, 'A nova senha deve ter pelo menos 8 caracteres.');
        if ($new_pass !== $conf_pass) jsonOut(false, 'As senhas não coincidem.');
        if (password_verify($new_pass, $user['password_user'])) {
            jsonOut(false, 'A nova senha não pode ser igual à actual.');
        }

        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $db->prepare("UPDATE _users SET password_user = ? WHERE id_users = ?")->execute([$hash, $id_users]);

        // Marcar que pode agora gerar recovery key
        $_SESSION['can_generate_recovery'] = true;

        logActivity($id_users, 'password_changed', 'Senha alterada com sucesso');

        // Email de aviso
        $name = $user['first_name'];
        $body = "<div style='font-family:Arial,sans-serif;max-width:540px;margin:auto'>
        <div style='background:#FF0089;padding:24px 32px;border-radius:8px 8px 0 0'>
            <h1 style='color:#fff;margin:0;font-size:1.3rem'>" . APP_NAME . "</h1>
        </div>
        <div style='background:#fff;padding:28px 32px;border:1px solid #eee;border-top:none;border-radius:0 0 8px 8px'>
            <h2 style='color:#222;font-size:1rem'>Senha alterada</h2>
            <p>Olá <strong>{$name}</strong>, a tua senha foi alterada com sucesso em " . date('d/m/Y H:i') . ".</p>
            <p>Se não foste tu, contacta o suporte imediatamente.</p>
            <p style='color:#999;font-size:.8rem'>" . APP_NAME . " &mdash; Não respondas a este email.</p>
        </div>
    </div>";
        sendEmail($user['email_user'], 'Senha alterada — ' . APP_NAME, $body);

        jsonOut(true, 'Senha alterada com sucesso! Receberás um email de confirmação.');

        // ──────────────────────────────────────────
        // GENERATE RECOVERY KEY (24 segmentos)
        // ──────────────────────────────────────────
    case 'generate_recovery_key':
        if (empty($_SESSION['can_generate_recovery'])) {
            jsonOut(false, 'Deves alterar a tua senha antes de gerar uma nova chave de recuperação.');
        }

        // Gerar 24 segmentos de 4 chars cada (estilo Mega)
        $segments = [];
        $chars    = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Sem ambíguos: 0,O,1,I
        $len      = strlen($chars);
        for ($i = 0; $i < 24; $i++) {
            $seg = '';
            for ($j = 0; $j < 4; $j++) {
                $seg .= $chars[random_int(0, $len - 1)];
            }
            $segments[] = $seg;
        }
        $key = implode('-', $segments); // 24 grupos de 4 = 96 chars + 23 hífens = 119 chars

        $db->prepare("UPDATE _users_security SET recovery_key = ? WHERE id_users = ?")
            ->execute([password_hash($key, PASSWORD_DEFAULT), $id_users]);

        // Guardar temporariamente na sessão para download
        $_SESSION['recovery_key_plain'] = $key;
        unset($_SESSION['can_generate_recovery']);

        logActivity($id_users, 'recovery_key_generated', 'Chave de recuperação regenerada');
        jsonOut(true, 'Chave gerada!', ['key' => $key]);

        // ──────────────────────────────────────────
        // DOWNLOAD RECOVERY KEY (TXT)
        // ──────────────────────────────────────────
    case 'download_recovery_key':
        if (empty($_SESSION['recovery_key_plain'])) {
            jsonOut(false, 'Nenhuma chave disponível para download. Gera primeiro uma nova chave.');
        }
        // Retorna a key — o JS faz o download
        $key = $_SESSION['recovery_key_plain'];
        unset($_SESSION['recovery_key_plain']);
        jsonOut(true, 'ok', ['key' => $key, 'filename' => APP_NAME . '_recovery_key_' . date('Ymd') . '.txt']);

        // ──────────────────────────────────────────
        // TOGGLE 2FA
        // ──────────────────────────────────────────
    case 'toggle_2fa':
        $enable = (int)($_POST['enable'] ?? 0);

        if ($enable) {
            // Gerar secret TOTP em base32 puro (A-Z + 2-7)
            // base64_encode gera chars inválidos para base32 — geração correcta:
            $bytes    = random_bytes(20);
            $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
            $secret   = '';
            $v = 0;
            $vbits = 0;
            foreach (str_split($bytes) as $byte) {
                $v = ($v << 8) | ord($byte);
                $vbits += 8;
                while ($vbits >= 5) {
                    $vbits -= 5;
                    $secret .= $alphabet[($v >> $vbits) & 0x1F];
                }
            }
            // Resultado: sempre 32 chars base32 válidos (Google Authenticator aceita)

            $db->prepare("UPDATE _users_security SET two_factor_enabled=0, two_factor_secret=? WHERE id_users=?")
                ->execute([$secret, $id_users]);

            $label   = APP_NAME . ':' . $user['email_user'];
            $otpauth = 'otpauth://totp/' . rawurlencode($label) . '?secret=' . $secret . '&issuer=' . rawurlencode(APP_NAME);
            $qr_url  = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($otpauth);

            jsonOut(true, 'Secret gerado.', ['secret' => $secret, 'qr_url' => $qr_url]);
        } else {
            // Desactivar — requer senha
            $pwd = $_POST['password_confirm'] ?? '';
            if (!password_verify($pwd, $user['password_user'])) {
                jsonOut(false, 'Senha incorrecta para desactivar o 2FA.');
            }
            $db->prepare("UPDATE _users_security SET two_factor_enabled=0, two_factor_secret=NULL WHERE id_users=?")
                ->execute([$id_users]);
            logActivity($id_users, '2fa_disabled', '2FA desactivado');
            jsonOut(true, '2FA desactivado com sucesso.');
        }

        // ──────────────────────────────────────────
        // CONFIRM 2FA (verificar código TOTP)
        // ──────────────────────────────────────────
    case 'confirm_2fa':
        $code   = trim($_POST['totp_code'] ?? '');
        $secret = trim($_POST['totp_secret'] ?? '');

        if (!$code || !$secret || strlen($code) !== 6) {
            jsonOut(false, 'Código inválido. Insere os 6 dígitos do teu autenticador.');
        }

        // Validação TOTP manual (sem biblioteca externa)
        function verifyTotp(string $secret, string $code, int $window = 1): bool
        {
            $decoded = base32Decode($secret);
            $time    = floor(time() / 30);
            for ($i = -$window; $i <= $window; $i++) {
                $t     = $time + $i;
                $msg   = pack('N*', 0) . pack('N*', $t);
                $hash  = hash_hmac('sha1', $msg, $decoded, true);
                $offset = ord($hash[19]) & 0x0F;
                $otp   = ((ord($hash[$offset]) & 0x7F) << 24)
                    | ((ord($hash[$offset + 1]) & 0xFF) << 16)
                    | ((ord($hash[$offset + 2]) & 0xFF) << 8)
                    |  (ord($hash[$offset + 3]) & 0xFF);
                $otp   = str_pad((string)($otp % 1000000), 6, '0', STR_PAD_LEFT);
                if (hash_equals($otp, $code)) return true;
            }
            return false;
        }

        function base32Decode(string $base32): string
        {
            $base32  = strtoupper($base32);
            $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
            $output  = '';
            $v       = 0;
            $vbits   = 0;
            for ($i = 0; $i < strlen($base32); $i++) {
                $v = ($v << 5) | strpos($alphabet, $base32[$i]);
                $vbits += 5;
                if ($vbits >= 8) {
                    $vbits -= 8;
                    $output .= chr($v >> $vbits);
                    $v &= (1 << $vbits) - 1;
                }
            }
            return $output;
        }

        if (!verifyTotp($secret, $code)) {
            jsonOut(false, 'Código incorrecto. Certifica-te que o teu relógio está sincronizado.');
        }

        $db->prepare("UPDATE _users_security SET two_factor_enabled=1, two_factor_secret=? WHERE id_users=?")
            ->execute([$secret, $id_users]);
        logActivity($id_users, '2fa_enabled', '2FA activado com sucesso');
        jsonOut(true, '2FA activado com sucesso! A tua conta está mais segura.');

        // ──────────────────────────────────────────
        // UPDATE NOTIFICATIONS
        // ──────────────────────────────────────────
    case 'update_notifications':
        $db->prepare("
        UPDATE _users SET
            notif_email    = ?,
            notif_push     = ?,
            notif_weekly   = ?,
            notif_releases = ?,
            notif_payments = ?
        WHERE id_users = ?
    ")->execute([
            (int)($_POST['notif_email']    ?? 0),
            (int)($_POST['notif_push']     ?? 0),
            (int)($_POST['notif_weekly']   ?? 0),
            (int)($_POST['notif_releases'] ?? 0),
            (int)($_POST['notif_payments'] ?? 0),
            $id_users
        ]);
        logActivity($id_users, 'notifications_updated', 'Preferências de notificação actualizadas');
        jsonOut(true, 'Preferências guardadas!');

        // ──────────────────────────────────────────
        // LOGOUT ALL SESSIONS
        // ──────────────────────────────────────────
    case 'logout_all_sessions':
        $pwd = $_POST['password_confirm'] ?? '';
        if (!password_verify($pwd, $user['password_user'])) {
            jsonOut(false, 'Senha incorrecta.');
        }

        // Manter a sessão actual, terminar todas as outras
        $current_token = $_SESSION['session_token'] ?? '';
        $db->prepare("
        UPDATE _users_sessions SET is_active = 0
        WHERE id_users = ? AND session_token != ?
    ")->execute([$id_users, $current_token]);

        logActivity($id_users, 'logout_all_sessions', 'Sessão encerrada em todos os dispositivos');
        jsonOut(true, 'Sessão encerrada em todos os dispositivos!');

        // ──────────────────────────────────────────
        // DEACTIVATE ACCOUNT (29 dias)
        // ──────────────────────────────────────────
    case 'deactivate_account':
        $pwd = $_POST['password_confirm'] ?? '';
        if (!password_verify($pwd, $user['password_user'])) {
            jsonOut(false, 'Senha incorrecta.');
        }
        if ($user['status_user'] === 'inactive') {
            jsonOut(false, 'A tua conta já está desactivada.');
        }

        $deact_until = date('Y-m-d H:i:s', strtotime('+29 days'));
        $db->prepare("
        UPDATE _users SET status_user='inactive', deactivation_user=? WHERE id_users=?
    ")->execute([$deact_until, $id_users]);

        // Destruir sessão completamente (BD + PHP)
        destroyUserSession($id_users);
        session_unset();
        session_destroy();

        logActivity($id_users, 'account_deactivated', 'Conta desactivada — prazo de recuperação: ' . $deact_until);

        // Email
        $body = "<div style='font-family:Arial,sans-serif;max-width:540px;margin:auto'>
        <div style='background:#555;padding:24px 32px;border-radius:8px 8px 0 0'>
            <h1 style='color:#fff;margin:0;font-size:1.3rem'>" . APP_NAME . "</h1>
        </div>
        <div style='background:#fff;padding:28px 32px;border:1px solid #eee;border-top:none;border-radius:0 0 8px 8px'>
            <h2 style='color:#222;font-size:1rem'>Conta desactivada</h2>
            <p>A tua conta foi desactivada em " . date('d/m/Y H:i') . ".
            Tens até <strong>" . date('d/m/Y', strtotime($deact_until)) . "</strong> para a recuperar.</p>
            <p>Para recuperar, basta iniciares sessão novamente.</p>
            <p style='color:#999;font-size:.8rem'>" . APP_NAME . " &mdash; Não respondas a este email.</p>
        </div>
    </div>";
        sendEmail($user['email_user'], 'Conta desactivada — ' . APP_NAME, $body);

        jsonOut(true, 'Conta desactivada.', ['redirect' => APP_URL . '/login?notice=account_deactivated']);

        // ──────────────────────────────────────────
        // DELETE ACCOUNT PERMANENTLY
        // ──────────────────────────────────────────
    case 'delete_account':
        $pwd           = $_POST['password_confirm']  ?? '';
        $confirm_text  = trim($_POST['confirm_text'] ?? '');
        $expected_text = 'eliminar a minha conta permanentemente';

        if (!password_verify($pwd, $user['password_user'])) {
            jsonOut(false, 'Senha incorrecta.');
        }
        if (strtolower($confirm_text) !== $expected_text) {
            jsonOut(false, 'Texto de confirmação incorrecto. Copia exactamente o texto indicado.');
        }

        // Anonimizar em vez de apagar (manter integridade)
        $anon_email = 'deleted_' . $id_users . '_' . time() . '@deleted.wasom';
        $db->prepare("
        UPDATE _users SET
            first_name     = 'Conta',
            second_name    = 'Eliminada',
            user_name      = ?,
            email_user     = ?,
            tel_user       = NULL,
            about_user     = NULL,
            photo_user     = NULL,
            password_user  = ?,
            status_user    = 'inactive',
            deactivation_user = NOW()
        WHERE id_users = ?
    ")->execute([
            'deleted_' . $id_users,
            $anon_email,
            password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
            $id_users
        ]);

        destroyUserSession($id_users);
        session_unset();
        session_destroy();

        logActivity($id_users, 'account_deleted', 'Conta eliminada permanentemente pelo utilizador');
        jsonOut(true, 'Conta eliminada.', ['redirect' => APP_URL . '/login?notice=account_deleted']);

        // ──────────────────────────────────────────
        // DOWNLOAD USER DATA (JSON)
        // ──────────────────────────────────────────
    case 'download_data':
        $pwd = $_POST['password_confirm'] ?? '';
        if (!password_verify($pwd, $user['password_user'])) {
            jsonOut(false, 'Senha incorrecta.');
        }

        // Recolher todos os dados
        $data = [];

        // Perfil (sem password)
        unset($user['password_user']);
        $data['perfil'] = $user;

        // Artistas
        $st = $db->prepare("SELECT * FROM _artist WHERE id_users = ?");
        $st->execute([$id_users]);
        $data['artistas'] = $st->fetchAll(PDO::FETCH_ASSOC);

        // Álbuns
        $st = $db->prepare("SELECT * FROM _album WHERE id_users = ?");
        $st->execute([$id_users]);
        $data['albuns'] = $st->fetchAll(PDO::FETCH_ASSOC);

        // Faixas
        $st = $db->prepare("SELECT t.* FROM _track t JOIN _album a ON a.id_album=t.id_album WHERE t.id_users=?");
        $st->execute([$id_users]);
        $data['faixas'] = $st->fetchAll(PDO::FETCH_ASSOC);

        // Transacções
        $st = $db->prepare("SELECT * FROM _transaction WHERE id_users = ? ORDER BY creat_transaction DESC");
        $st->execute([$id_users]);
        $data['transaccoes'] = $st->fetchAll(PDO::FETCH_ASSOC);

        // Levantamentos
        $st = $db->prepare("SELECT * FROM _withdrawal WHERE id_users = ? ORDER BY creat_withdrawal DESC");
        $st->execute([$id_users]);
        $data['levantamentos'] = $st->fetchAll(PDO::FETCH_ASSOC);

        // Pagamentos
        $st = $db->prepare("SELECT id_payment,id_plan,amount,currency,payment_method,status_payment,creat_payment FROM _payment WHERE id_users = ?");
        $st->execute([$id_users]);
        $data['pagamentos'] = $st->fetchAll(PDO::FETCH_ASSOC);

        // Sessões (últimas 50)
        $st = $db->prepare("SELECT ip_address,user_agent,country,city,creat_session,last_activity FROM _users_sessions WHERE id_users=? ORDER BY creat_session DESC LIMIT 50");
        $st->execute([$id_users]);
        $data['sessoes'] = $st->fetchAll(PDO::FETCH_ASSOC);

        $data['exportado_em'] = date('Y-m-d H:i:s');
        $data['plataforma']   = APP_NAME;

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        logActivity($id_users, 'data_downloaded', 'Download de todos os dados do utilizador');

        jsonOut(true, 'ok', [
            'data'     => $json,
            'filename' => APP_NAME . '_dados_' . $id_users . '_' . date('Ymd') . '.json',
        ]);

    default:
        jsonOut(false, 'Acção desconhecida.');
}