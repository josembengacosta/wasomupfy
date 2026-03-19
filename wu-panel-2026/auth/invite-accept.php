<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Activação de Conta por Convite
// Arquivo: admin/auth/invite-accept.php
// Rota:    admin/invite/accept?t=TOKEN
//
// Fluxo (4 passos):
//   1. Validar token → Introduzir email+senha temp
//   2. Introduzir OTP de 6 dígitos (enviado por email)
//   3. Definir nova senha
//   4. Token marcado como usado → redirecionar para login
// ══════════════════════════════════════════════

// Carregar apenas o necessário — sem requireAdminLogin
require_once __DIR__ . '/../../authentic/include/config.php';
require_once __DIR__ . '/../../authentic/include/connection.php';
require_once __DIR__ . '/include/functions_admin.php';

startAdminSession();

// Se já está logado como admin, redirecionar para home
if (isAdminLoggedIn()) {
    adminRedirect('/' . ADMIN_PATH . '');
}

$db = getDB();

// ── Constantes de sessão para este fluxo ──
define('INV_SESS', 'wuf_invite_');

// ── Helper: limpar sessão do convite ──
function inv_clear(): void
{
    foreach (['step', 'emp_id', 'token', 'otp', 'otp_exp', 'email'] as $k) {
        unset($_SESSION[INV_SESS . $k]);
    }
}

// ── Helper: mostrar página de erro tipo browser ──
// Não revela nenhuma informação sobre o sistema
function show_browser_error(): never
{
    // Limpar sessão do convite
    inv_clear();
    // Headers que simulam erro de conexão
    header('HTTP/1.1 410 Gone');
    echo '<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="utf-8"/>
<title>Esta página não está disponível</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
         background:#fff; color:#202124; padding: 80px 24px; }
  .err-code { font-size:.85rem; color:#70757a; margin-bottom:16px; }
  h1 { font-size:1.4rem; font-weight:400; margin-bottom:12px; }
  p  { font-size:.88rem; color:#5f6368; line-height:1.6; max-width:480px; }
  .btn { display:inline-block; margin-top:24px; padding:8px 20px;
         background:#1a73e8; color:#fff; border-radius:4px;
         font-size:.85rem; text-decoration:none; cursor:pointer;
         border:none; font-family:inherit; }
  .btn:hover { background:#1557b0; }
  .details { margin-top:32px; font-size:.78rem; color:#9aa0a6; }
</style>
</head>
<body>
<div class="err-code">ERR_RESOURCE_NOT_AVAILABLE</div>
<h1>Esta página não está disponível</h1>
<p>
  O servidor onde esta página está hospedada pode estar temporariamente
  indisponível ou ter sido movido para um endereço diferente.
</p>
<a href="javascript:history.back()" class="btn">Tentar novamente</a>
<div class="details">
  Código de diagnóstico: 0x80004005 &nbsp;·&nbsp; ' . date('Y-m-d H:i:s') . '
</div>
</body>
</html>';
    exit;
}

// ════════════════════════════════════════════
// VALIDAÇÃO DO TOKEN (primeira chegada)
// ════════════════════════════════════════════
$token_raw = $_GET['t'] ?? ($_SESSION[INV_SESS . 'token'] ?? null);

// Se não há token e não há sessão de convite activa, erro
if (!$token_raw && !isset($_SESSION[INV_SESS . 'step'])) {
    show_browser_error();
}

// Se há token na URL, validar
if ($token_raw && !isset($_SESSION[INV_SESS . 'step'])) {
    // Sanitizar — deve ser hex 64 chars
    if (!preg_match('/^[a-f0-9]{64}$/i', $token_raw)) {
        show_browser_error();
    }

    // Consultar na BD
    $stmt = $db->prepare("
        SELECT s.id_employees, s.invite_token, s.invite_token_expires, s.invite_used,
               e.email_employees, e.first_name, e.second_name, e.status_employees
        FROM _employees_security s
        JOIN _employees e ON e.id_employees = s.id_employees
        WHERE s.invite_token = ?
        LIMIT 1
    ");
    $stmt->execute([$token_raw]);
    $inv = $stmt->fetch();

    // Validações silenciosas — qualquer falha → erro de browser
    if (!$inv)                                         show_browser_error();
    if ((int)$inv['invite_used'] !== 0)                show_browser_error();
    if (!$inv['invite_token_expires'])                 show_browser_error();
    if (strtotime($inv['invite_token_expires']) < time()) show_browser_error();

    // Token válido — guardar em sessão e avançar para passo 1
    $_SESSION[INV_SESS . 'step']   = 1;
    $_SESSION[INV_SESS . 'emp_id'] = (int)$inv['id_employees'];
    $_SESSION[INV_SESS . 'token']  = $token_raw;
    $_SESSION[INV_SESS . 'email']  = $inv['email_employees'];

    // Regenerar ID de sessão por segurança
    session_regenerate_id(true);
}

// ── Variáveis do fluxo ──
$current_step = (int)($_SESSION[INV_SESS . 'step'] ?? 1);
$emp_id       = (int)($_SESSION[INV_SESS . 'emp_id'] ?? 0);
$inv_email    = $_SESSION[INV_SESS . 'email'] ?? '';

// Protecção: se não há emp_id válido na sessão, erro
if (!$emp_id) show_browser_error();

// Carregar dados do employee
$emp_stmt = $db->prepare("
    SELECT e.first_name, e.second_name, e.email_employees, e.user_employees, e.password_employees
    FROM _employees e
    WHERE e.id_employees = ?
    LIMIT 1
");
$emp_stmt->execute([$emp_id]);
$emp = $emp_stmt->fetch();
if (!$emp) show_browser_error();

$emp_fullname = trim($emp['first_name'] . ' ' . ($emp['second_name'] ?? ''));

// ── Mensagens de erro/feedback ──
$form_error = null;
$form_info  = null;

// ════════════════════════════════════════════
// PROCESSAMENTO POST
// ════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_step = (int)($_POST['step'] ?? 0);

    // ── PASSO 1: validar email/username + senha temporária ──
    if ($posted_step === 1 && $current_step === 1) {
        $input_login = trim($_POST['login']    ?? '');
        $input_pw    = trim($_POST['password'] ?? '');

        if (empty($input_login) || empty($input_pw)) {
            $form_error = 'Preenche todos os campos.';
        } else {
            // Verificar se é email ou username
            $match = (strtolower($input_login) === strtolower($emp['email_employees']))
                || (strtolower($input_login) === strtolower($emp['user_employees'] ?? ''));

            if (!$match) {
                // Delay anti-enumeração
                usleep(random_int(400000, 700000));
                $form_error = 'Credenciais incorrectas. Verifica o e-mail/username e a senha.';
            } elseif (!password_verify($input_pw, $emp['password_employees'])) {
                usleep(random_int(400000, 700000));
                $form_error = 'Credenciais incorrectas. Verifica o e-mail/username e a senha.';
            } else {
                // Credenciais correctas — gerar OTP e enviar por email
                $otp     = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp_exp = time() + 600; // 10 minutos

                $_SESSION[INV_SESS . 'otp']     = password_hash($otp, PASSWORD_BCRYPT, ['cost' => 10]);
                $_SESSION[INV_SESS . 'otp_exp'] = $otp_exp;
                $_SESSION[INV_SESS . 'step']    = 2;
                $current_step = 2;

                // Enviar OTP por email
                $otp_sent = false;
                $mailer_path = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';
                if (file_exists($mailer_path)) {
                    if (!class_exists('\Wasom\Mailer')) require_once $mailer_path;
                    try {
                        $otp_subject = 'Código de verificação — Wasom Upfy';
                        $otp_body = '<!DOCTYPE html>
<html lang="pt-ao"><head><meta charset="utf-8"/><title>Código de Verificação</title></head>
<body style="margin:0;padding:0;background:#f4f4f8;font-family:Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f8;padding:32px 16px">
  <tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px">
      <tr>
        <td style="background:linear-gradient(135deg,#FF0089,#6c63ff);border-radius:16px 16px 0 0;padding:28px 24px;text-align:center">
          <div style="display:inline-block;background:rgba(255,255,255,.15);border:3px solid rgba(255,255,255,.3);border-radius:50%;width:52px;height:52px;line-height:52px;text-align:center;font-size:1rem;font-weight:800;color:#fff;margin-bottom:12px">WU</div>
          <h2 style="color:#fff;margin:0;font-size:1.1rem">Código de Verificação</h2>
        </td>
      </tr>
      <tr>
        <td style="background:#fff;padding:28px;border:1px solid #eee;border-top:none;border-radius:0 0 16px 16px">
          <p style="color:#111;margin:0 0 16px;font-size:.9rem">
            Olá <strong>' . htmlspecialchars($emp_fullname) . '</strong>,
          </p>
          <p style="color:#555;font-size:.84rem;margin:0 0 20px;line-height:1.6">
            Usas o código abaixo para confirmar a tua identidade durante a activação da conta.
            Válido por <strong>10 minutos</strong>.
          </p>
          <div style="background:#f8f7fc;border:2px dashed rgba(255,0,137,.3);border-radius:12px;padding:20px;text-align:center;margin-bottom:20px">
            <div style="font-size:2.2rem;font-weight:800;letter-spacing:8px;color:#FF0089;font-family:monospace">
              ' . $otp . '
            </div>
            <div style="font-size:.75rem;color:#aaa;margin-top:6px">Código de verificação único</div>
          </div>
          <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:12px;font-size:.78rem;color:#7c4800">
            ⚠ Se não estás a activar a tua conta na Wasom Upfy, ignora este e-mail.
            Nunca partilhes este código.
          </div>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body></html>';

                        $wm = new \Wasom\Mailer();
                        $wm->host     = MAIL_HOST;
                        $wm->port     = MAIL_PORT;
                        $wm->secure   = defined('MAIL_SECURE') ? MAIL_SECURE : 'tls';
                        $wm->username = MAIL_USER;
                        $wm->password = MAIL_PASS;
                        $wm->debug    = defined('MAIL_DEBUG') ? MAIL_DEBUG : 0;
                        $wm->setFrom(MAIL_FROM, MAIL_FROM_NAME)
                            ->addAddress($emp['email_employees'], $emp_fullname)
                            ->setSubject($otp_subject)
                            ->setBody($otp_body, 'Código de verificação: ' . $otp);
                        $wm->send();
                        $otp_sent = true;
                    } catch (Exception $e) {
                        error_log('[INVITE OTP] Falha: ' . $e->getMessage());
                    }
                }

                if ($otp_sent) {
                    // Mascarar email para exibição
                    $parts  = explode('@', $emp['email_employees']);
                    $masked = mb_substr($parts[0], 0, 2) . '***@' . ($parts[1] ?? '');
                    $form_info = 'Código enviado para <strong>' . htmlspecialchars($masked) . '</strong>. Verifica a caixa de entrada.';
                } else {
                    // Email falhou — por segurança, não bloqueamos o fluxo
                    // mas informamos o utilizador
                    $form_info = 'Não foi possível enviar o código. Contacta o administrador.';
                    // Voltar ao passo 1
                    $_SESSION[INV_SESS . 'step'] = 1;
                    $current_step = 1;
                    $form_error = 'Falha no envio do código de verificação. Tenta novamente.';
                }
            }
        }
    }

    // ── PASSO 2: verificar OTP ──
    elseif ($posted_step === 2 && $current_step === 2) {
        $input_otp = trim($_POST['otp'] ?? '');

        if (!preg_match('/^\d{6}$/', $input_otp)) {
            $form_error = 'O código deve ter exactamente 6 dígitos.';
        } elseif (!isset($_SESSION[INV_SESS . 'otp_exp']) || time() > $_SESSION[INV_SESS . 'otp_exp']) {
            $form_error = 'O código expirou. <a href="' . APP_URL . '/' . ADMIN_PATH . '/invite/accept?t=' . urlencode($_SESSION[INV_SESS . 'token']) . '" style="color:#FF0089">Recomeçar</a>.';
            inv_clear();
        } elseif (!password_verify($input_otp, $_SESSION[INV_SESS . 'otp'] ?? '')) {
            $form_error = 'Código incorrecto. Verifica o e-mail e tenta novamente.';
        } else {
            // OTP correcto — avançar para passo 3
            $_SESSION[INV_SESS . 'step'] = 3;
            $current_step = 3;
            // Limpar OTP da sessão (já não necessário)
            unset($_SESSION[INV_SESS . 'otp'], $_SESSION[INV_SESS . 'otp_exp']);
        }
    }

    // ── PASSO 3: definir nova senha ──
    elseif ($posted_step === 3 && $current_step === 3) {
        $new_pw      = $_POST['new_password']     ?? '';
        $confirm_pw  = $_POST['confirm_password'] ?? '';

        $pw_ok = strlen($new_pw) >= 8
            && preg_match('/[A-Z]/', $new_pw)
            && preg_match('/[a-z]/', $new_pw)
            && preg_match('/[0-9]/', $new_pw)
            && preg_match('/[!@#$%^&*\-_+=?]/', $new_pw);

        if (!$pw_ok) {
            $form_error = 'A senha não cumpre os requisitos mínimos.';
        } elseif (!hash_equals($new_pw, $confirm_pw)) {
            $form_error = 'As senhas não coincidem.';
        } else {
            $new_hash = password_hash($new_pw, PASSWORD_BCRYPT, ['cost' => 12]);

            try {
                $db->beginTransaction();

                // Actualizar senha
                $db->prepare("UPDATE _employees SET password_employees = ? WHERE id_employees = ?")
                    ->execute([$new_hash, $emp_id]);

                // Marcar invite como usado + limpar token
                $db->prepare("
                    UPDATE _employees_security
                    SET invite_used = 1, invite_token = NULL, invite_token_expires = NULL
                    WHERE id_employees = ?
                ")->execute([$emp_id]);

                // Activar conta se ainda estiver em processing
                // (respeitar o estado que o admin definiu — se já está active, manter)
                // Se o admin escolheu "active" já foi definido na criação
                // Se escolheu "processing", activamos agora que o utilizador completou o fluxo
                $db->prepare("
                    UPDATE _employees
                    SET status_employees = 'active'
                    WHERE id_employees = ? AND status_employees = 'processing'
                ")->execute([$emp_id]);

                $db->commit();

                // Log
                logAudit(null, null, 'auth.invite_accepted', 'employees', $emp_id, null, null);

                // ── Notificar o admin que criou o convite ──
                // Buscar quem criou este funcionário pelo audit_log
                $creator = $db->prepare("
                    SELECT e.email_employees, e.first_name, e.second_name
                    FROM _audit_log al
                    JOIN _employees e ON e.id_employees = al.id_employees
                    WHERE al.action = 'employees.created'
                      AND al.entity = 'employees'
                      AND al.entity_id = ?
                    ORDER BY al.creat_log ASC
                    LIMIT 1
                ");
                $creator->execute([$emp_id]);
                $creator_admin = $creator->fetch();

                if ($creator_admin) {
                    $creator_name  = trim($creator_admin['first_name'] . ' ' . ($creator_admin['second_name'] ?? ''));
                    $notif_subject = 'Conta activada — ' . $emp_fullname . ' já tem acesso ao painel';
                    $notif_body    = '<!DOCTYPE html>
<html lang="pt-ao"><head><meta charset="utf-8"/></head>
<body style="margin:0;padding:0;background:#f4f4f8;font-family:Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f8;padding:32px 16px">
  <tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px">
      <tr>
        <td style="background:linear-gradient(135deg,#FF0089,#6c63ff);border-radius:16px 16px 0 0;padding:28px 24px;text-align:center">
          <div style="display:inline-block;background:rgba(255,255,255,.15);border:3px solid rgba(255,255,255,.3);border-radius:50%;width:52px;height:52px;line-height:52px;text-align:center;font-size:1rem;font-weight:800;color:#fff;margin-bottom:12px">WU</div>
          <h2 style="color:#fff;margin:0;font-size:1.1rem">Conta Activada</h2>
          <p style="color:rgba(255,255,255,.8);margin:6px 0 0;font-size:.84rem">Notificação automática do painel</p>
        </td>
      </tr>
      <tr>
        <td style="background:#fff;padding:28px;border:1px solid #eee;border-top:none;border-radius:0 0 16px 16px">
          <p style="color:#111;margin:0 0 16px;font-size:.9rem">
            Olá, <strong>' . htmlspecialchars($creator_name) . '</strong>!
          </p>
          <p style="color:#555;font-size:.84rem;line-height:1.6;margin:0 0 20px">
            O funcionário que convidaste completou a activação da conta com sucesso
            e já tem acesso ao painel.
          </p>
          <table width="100%" cellpadding="0" cellspacing="0"
                 style="background:#f8f7fc;border-radius:10px;border:1px solid #e8e8f0;margin-bottom:20px">
            <tr>
              <td style="padding:16px 18px">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td style="font-size:.75rem;color:#aaa;padding:4px 0 2px">NOME</td>
                    <td style="font-size:.75rem;color:#aaa;padding:4px 0 2px;text-align:right">USERNAME</td>
                  </tr>
                  <tr>
                    <td style="font-size:.9rem;font-weight:700;color:#111;padding:2px 0 10px;border-bottom:1px solid #eee">
                      ' . htmlspecialchars($emp_fullname) . '
                    </td>
                    <td style="font-size:.9rem;font-weight:700;color:#FF0089;padding:2px 0 10px;border-bottom:1px solid #eee;text-align:right">
                      @' . htmlspecialchars($emp['user_employees'] ?? '') . '
                    </td>
                  </tr>
                  <tr>
                    <td style="font-size:.75rem;color:#aaa;padding:10px 0 2px">E-MAIL</td>
                    <td style="font-size:.75rem;color:#aaa;padding:10px 0 2px;text-align:right">ACTIVADO EM</td>
                  </tr>
                  <tr>
                    <td style="font-size:.84rem;color:#555;padding:2px 0">
                      ' . htmlspecialchars($emp['email_employees']) . '
                    </td>
                    <td style="font-size:.84rem;color:#555;padding:2px 0;text-align:right">
                      ' . date('d/m/Y \à\s H:i') . '
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
          <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;font-size:.78rem;color:#166534">
            ✓ A conta está activa e o funcionário já pode fazer login no painel de administração.
          </div>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body></html>';

                    $mailer_path = dirname(__DIR__, 3) . '/authentic/include/WasomMailer.php';
                    if (file_exists($mailer_path)) {
                        if (!class_exists('\Wasom\Mailer')) require_once $mailer_path;
                        try {
                            $wm = new \Wasom\Mailer();
                            $wm->host     = MAIL_HOST;
                            $wm->port     = MAIL_PORT;
                            $wm->secure   = defined('MAIL_SECURE') ? MAIL_SECURE : 'tls';
                            $wm->username = MAIL_USER;
                            $wm->password = MAIL_PASS;
                            $wm->debug    = defined('MAIL_DEBUG') ? MAIL_DEBUG : 0;
                            $wm->setFrom(MAIL_FROM, MAIL_FROM_NAME)
                                ->addAddress($creator_admin['email_employees'], $creator_name)
                                ->setSubject($notif_subject)
                                ->setBody($notif_body, strip_tags($notif_body));
                            $wm->send();
                        } catch (Exception $e) {
                            error_log('[INVITE NOTIF] Falha ao notificar admin: ' . $e->getMessage());
                            // Falha silenciosa — não bloquear o redirect
                        }
                    }
                }

                // Limpar sessão do convite
                inv_clear();

                // Redirecionar para login com mensagem de sucesso
                adminRedirect('/' . ADMIN_PATH . '/login', ['invite' => 'done']);
            } catch (Exception $e) {
                $db->rollBack();
                $form_error = 'Ocorreu um erro ao guardar a senha. Tenta novamente.';
            }
        }
    }
}

// ── Recarregar passo actual da sessão ──
$current_step = (int)($_SESSION[INV_SESS . 'step'] ?? 1);

// Email mascarado para exibição no passo 2
$email_masked = '';
if ($inv_email) {
    $parts        = explode('@', $inv_email);
    $email_masked = mb_substr($parts[0], 0, 2) . '***@' . ($parts[1] ?? '');
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <title>Activar Conta — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet" />
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f8f7fc;
            font-family: 'DM Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .inv-wrap {
            width: 100%;
            max-width: 440px;
        }

        /* ── Logo / marca ── */
        .inv-brand {
            text-align: center;
            margin-bottom: 24px;
        }

        .inv-brand-logo {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FF0089, #6c63ff);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: .9rem;
            color: #fff;
            letter-spacing: -1px;
            margin-bottom: 12px;
        }

        .inv-brand-name {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.15rem;
            color: #111;
            display: block;
        }

        /* ── Card principal ── */
        .inv-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e8e8f0;
            padding: 32px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .07);
        }

        /* ── Steps indicator ── */
        .steps-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: 28px;
        }

        .step-dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .72rem;
            font-weight: 700;
            border: 2px solid #e8e8f0;
            color: #aaa;
            background: #fff;
            position: relative;
            z-index: 1;
            transition: all .3s;
            flex-shrink: 0;
        }

        .step-dot.done {
            background: #22c55e;
            border-color: #22c55e;
            color: #fff;
        }

        .step-dot.active {
            background: #FF0089;
            border-color: #FF0089;
            color: #fff;
            box-shadow: 0 0 0 4px rgba(255, 0, 137, .15);
        }

        .step-line {
            flex: 1;
            height: 2px;
            background: #e8e8f0;
            margin: 0 4px;
            transition: background .3s;
        }

        .step-line.done {
            background: #22c55e;
        }

        /* ── Títulos ── */
        .inv-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            color: #111;
            margin-bottom: 6px;
        }

        .inv-sub {
            font-size: .84rem;
            color: #888;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        /* ── Inputs ── */
        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1.5px solid #e8e8f0;
            padding: 11px 14px;
            font-size: .88rem;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #FF0089;
            box-shadow: 0 0 0 3px rgba(255, 0, 137, .12);
            outline: none;
        }

        .form-label {
            font-size: .82rem;
            font-weight: 600;
            color: #444;
            margin-bottom: 6px;
        }

        /* ── OTP input ── */
        .otp-input {
            font-family: monospace;
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 8px;
            text-align: center;
            color: #FF0089;
        }

        /* ── Botão principal ── */
        .btn-primary-inv {
            width: 100%;
            padding: 13px;
            border-radius: 12px;
            background: linear-gradient(135deg, #FF0089, #cc006e);
            border: none;
            color: #fff;
            font-weight: 700;
            font-size: .9rem;
            cursor: pointer;
            transition: opacity .2s, transform .2s;
            margin-top: 8px;
        }

        .btn-primary-inv:hover {
            opacity: .9;
            transform: translateY(-1px);
        }

        .btn-primary-inv:active {
            transform: translateY(0);
        }

        .btn-primary-inv:disabled {
            opacity: .6;
            cursor: not-allowed;
            transform: none;
        }

        /* ── Força da senha ── */
        .pw-bar {
            height: 4px;
            border-radius: 2px;
            background: #e8e8f0;
            overflow: hidden;
            margin: 8px 0 4px;
        }

        .pw-fill {
            height: 100%;
            border-radius: 2px;
            width: 0;
            transition: width .3s, background .3s;
        }

        .pw-lbl {
            font-size: .73rem;
            color: #aaa;
        }

        /* ── Requisitos da senha ── */
        .pw-req {
            font-size: .76rem;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
            color: #aaa;
        }

        .pw-req i {
            font-size: .7rem;
        }

        .pw-req.ok {
            color: #22c55e;
        }

        /* ── Toggle senha ── */
        .pw-toggle {
            background: none;
            border: none;
            cursor: pointer;
            color: #aaa;
            padding: 0 12px;
        }

        .pw-toggle:hover {
            color: #FF0089;
        }

        /* ── Erro / info ── */
        .inv-alert {
            border-radius: 10px;
            padding: 12px 14px;
            font-size: .82rem;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .inv-alert.error {
            background: rgba(239, 68, 68, .08);
            border: 1px solid rgba(239, 68, 68, .2);
            color: #991b1b;
        }

        .inv-alert.info {
            background: rgba(59, 130, 246, .08);
            border: 1px solid rgba(59, 130, 246, .2);
            color: #1e40af;
        }

        /* ── Info do utilizador ── */
        .emp-chip {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f8f7fc;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 20px;
        }

        .emp-chip-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FF0089, #6c63ff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: .75rem;
            color: #fff;
            flex-shrink: 0;
        }

        .emp-chip-name {
            font-weight: 600;
            font-size: .88rem;
            color: #111;
        }

        .emp-chip-email {
            font-size: .76rem;
            color: #888;
        }
    </style>
</head>

<body>
    <div class="inv-wrap">

        <!-- Marca -->
        <div class="inv-brand">
            <div class="inv-brand-logo">WU</div>
            <span class="inv-brand-name">Wasom Upfy</span>
        </div>

        <!-- Card -->
        <div class="inv-card">

            <!-- Steps -->
            <div class="steps-wrap">
                <div class="step-dot <?php echo $current_step >= 1 ? ($current_step > 1 ? 'done' : 'active') : ''; ?>">
                    <?php echo $current_step > 1 ? '<i class="bi bi-check-lg"></i>' : '1'; ?>
                </div>
                <div class="step-line <?php echo $current_step > 1 ? 'done' : ''; ?>"></div>
                <div class="step-dot <?php echo $current_step >= 2 ? ($current_step > 2 ? 'done' : 'active') : ''; ?>">
                    <?php echo $current_step > 2 ? '<i class="bi bi-check-lg"></i>' : '2'; ?>
                </div>
                <div class="step-line <?php echo $current_step > 2 ? 'done' : ''; ?>"></div>
                <div class="step-dot <?php echo $current_step >= 3 ? 'active' : ''; ?>">3</div>
            </div>

            <!-- Chip com info do utilizador -->
            <div class="emp-chip">
                <div class="emp-chip-avatar">
                    <?php
                    $ini = mb_strtoupper(mb_substr($emp['first_name'], 0, 1, 'UTF-8'), 'UTF-8')
                        . mb_strtoupper(mb_substr($emp['second_name'] ?? '', 0, 1, 'UTF-8'), 'UTF-8');
                    echo $ini;
                    ?>
                </div>
                <div>
                    <div class="emp-chip-name"><?php echo htmlspecialchars($emp_fullname); ?></div>
                    <div class="emp-chip-email"><?php echo htmlspecialchars($email_masked ?: $inv_email); ?></div>
                </div>
            </div>

            <!-- Erros / Infos -->
            <?php if ($form_error): ?>
                <div class="inv-alert error">
                    <i class="bi bi-x-circle me-1"></i><?php echo $form_error; ?>
                </div>
            <?php endif; ?>

            <?php if ($form_info): ?>
                <div class="inv-alert info">
                    <i class="bi bi-info-circle me-1"></i><?php echo $form_info; ?>
                </div>
            <?php endif; ?>


            <?php if ($current_step === 1): ?>
                <!-- ══════════════════════
             PASSO 1 — Credenciais
             ══════════════════════ -->
                <div class="inv-title">Confirma a tua identidade</div>
                <div class="inv-sub">
                    Introduz o teu e-mail ou username e a senha temporária
                    que recebeste no e-mail de convite.
                </div>

                <form method="POST" id="form-step1">
                    <input type="hidden" name="step" value="1" />

                    <div class="mb-3">
                        <label class="form-label">E-mail ou Username</label>
                        <div class="input-group">
                            <span class="input-group-text"
                                style="border-radius:10px 0 0 10px;border:1.5px solid #e8e8f0;border-right:none;background:#f8f7fc">
                                <i class="bi bi-person text-muted"></i>
                            </span>
                            <input type="text" class="form-control" name="login" placeholder="email@... ou @username"
                                autocomplete="username" required style="border-radius:0 10px 10px 0" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Senha Temporária</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="password" id="pw-temp"
                                placeholder="A senha do e-mail de convite" autocomplete="current-password" required
                                style="border-radius:10px 0 0 10px;font-family:monospace" />
                            <button type="button" class="pw-toggle input-group-text"
                                style="border:1.5px solid #e8e8f0;border-left:none;border-radius:0 10px 10px 0;background:#f8f7fc"
                                onclick="togglePw('pw-temp','eye-temp')">
                                <i class="bi bi-eye" id="eye-temp"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-inv" id="btn-s1">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="spin-s1"></span>
                        <i class="bi bi-arrow-right me-1"></i>Continuar
                    </button>
                </form>


            <?php elseif ($current_step === 2): ?>
                <!-- ══════════════════════
             PASSO 2 — OTP
             ══════════════════════ -->
                <div class="inv-title">Código de verificação</div>
                <div class="inv-sub">
                    Enviámos um código de 6 dígitos para
                    <strong><?php echo htmlspecialchars($email_masked); ?></strong>.
                    Introduz o código abaixo. Válido por <strong>10 minutos</strong>.
                </div>

                <form method="POST" id="form-step2">
                    <input type="hidden" name="step" value="2" />

                    <div class="mb-4">
                        <label class="form-label text-center d-block">Código de 6 dígitos</label>
                        <input type="text" class="form-control otp-input" name="otp" id="otp-inp" maxlength="6"
                            pattern="\d{6}" placeholder="_ _ _ _ _ _" autocomplete="one-time-code" required
                            inputmode="numeric" />
                    </div>

                    <button type="submit" class="btn-primary-inv" id="btn-s2">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="spin-s2"></span>
                        <i class="bi bi-shield-check me-1"></i>Verificar Código
                    </button>
                </form>

                <!-- Reenviar -->
                <div class="text-center mt-3">
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="step" value="resend" />
                        <button type="submit" style="background:none;border:none;color:#FF0089;
                    font-size:.8rem;cursor:pointer;text-decoration:underline;font-family:inherit">
                            Não recebi o código — reenviar
                        </button>
                    </form>
                </div>


            <?php elseif ($current_step === 3): ?>
                <!-- ══════════════════════
             PASSO 3 — Nova senha
             ══════════════════════ -->
                <div class="inv-title">Define a tua senha</div>
                <div class="inv-sub">
                    Escolhe uma senha forte para a tua conta.
                    Após este passo, a senha temporária deixará de funcionar.
                </div>

                <form method="POST" id="form-step3" novalidate onsubmit="return false">
                    <input type="hidden" name="step" value="3" />

                    <div class="mb-3">
                        <label class="form-label">Nova Senha</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="new_password" id="pw-new"
                                placeholder="Mínimo 8 caracteres" autocomplete="new-password" required
                                style="border-radius:10px 0 0 10px" />
                            <button type="button" class="pw-toggle input-group-text"
                                style="border:1.5px solid #e8e8f0;border-left:none;border-radius:0 10px 10px 0;background:#f8f7fc"
                                onclick="togglePw('pw-new','eye-new')">
                                <i class="bi bi-eye" id="eye-new"></i>
                            </button>
                        </div>
                        <div class="pw-bar">
                            <div class="pw-fill" id="pw-fill3"></div>
                        </div>
                        <div class="pw-lbl" id="pw-lbl3">Escreve a tua nova senha</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirmar Nova Senha</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="confirm_password" id="pw-conf"
                                placeholder="Repete a senha" autocomplete="new-password" required
                                style="border-radius:10px 0 0 10px" />
                            <button type="button" class="pw-toggle input-group-text"
                                style="border:1.5px solid #e8e8f0;border-left:none;border-radius:0 10px 10px 0;background:#f8f7fc"
                                onclick="togglePw('pw-conf','eye-conf')">
                                <i class="bi bi-eye" id="eye-conf"></i>
                            </button>
                        </div>
                        <div id="match-err" style="font-size:.75rem;color:#ef4444;margin-top:4px;display:none">
                            <i class="bi bi-x-circle me-1"></i>As senhas não coincidem.
                        </div>
                    </div>

                    <!-- Requisitos -->
                    <div class="mb-3 p-3 rounded" style="background:#f8f7fc;border:1px solid #e8e8f0">
                        <div class="pw-req" id="req-len"> <i class="bi bi-circle"></i> Mínimo 8 caracteres</div>
                        <div class="pw-req" id="req-up"> <i class="bi bi-circle"></i> Uma letra maiúscula</div>
                        <div class="pw-req" id="req-low"> <i class="bi bi-circle"></i> Uma letra minúscula</div>
                        <div class="pw-req" id="req-num"> <i class="bi bi-circle"></i> Um número</div>
                        <div class="pw-req" id="req-sym"> <i class="bi bi-circle"></i> Um símbolo (!@#$%...)</div>
                        <div class="pw-req" id="req-mat"> <i class="bi bi-circle"></i> Senhas coincidem</div>
                    </div>

                    <button type="button" class="btn-primary-inv" id="btn-s3" disabled onclick="submitStep3()">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="spin-s3"></span>
                        <i class="bi bi-check-circle me-1"></i>Activar Conta
                    </button>
                </form>

            <?php endif; ?>

        </div><!-- /inv-card -->

        <div class="text-center mt-3" style="font-size:.75rem;color:#bbb">
            © 2026 Wasom Upfy · Todos os direitos reservados.
        </div>

    </div><!-- /inv-wrap -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ── Toggle visibilidade de senha ──
        function togglePw(inputId, iconId) {
            var inp = document.getElementById(inputId);
            var ico = document.getElementById(iconId);
            if (!inp || !ico) return;
            inp.type = inp.type === 'password' ? 'text' : 'password';
            ico.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        }

        // ── Spinner nos forms ──
        function addSpinner(formId, btnId, spinId) {
            var form = document.getElementById(formId);
            var btn = document.getElementById(btnId);
            var spin = document.getElementById(spinId);
            if (!form || !btn) return;
            form.addEventListener('submit', function() {
                if (spin) spin.classList.remove('d-none');
                btn.disabled = true;
            });
        }

        addSpinner('form-step1', 'btn-s1', 'spin-s1');
        addSpinner('form-step2', 'btn-s2', 'spin-s2');

        // ── OTP — auto-submit ao completar 6 dígitos ──
        var otpInp = document.getElementById('otp-inp');
        if (otpInp) {
            otpInp.addEventListener('input', function() {
                // Remover caracteres não numéricos
                this.value = this.value.replace(/\D/g, '');
                if (this.value.length === 6) {
                    var btn = document.getElementById('btn-s2');
                    var spin = document.getElementById('spin-s2');
                    if (spin) spin.classList.remove('d-none');
                    if (btn) btn.disabled = true;
                    document.getElementById('form-step2').submit();
                }
            });
        }

        // ── Validação de senha (passo 3) ──
        <?php if ($current_step === 3): ?>
            var pwNew = document.getElementById('pw-new');
            var pwConf = document.getElementById('pw-conf');
            var pwFill = document.getElementById('pw-fill3');
            var pwLbl = document.getElementById('pw-lbl3');
            var btn3 = document.getElementById('btn-s3');
            var matchE = document.getElementById('match-err');

            var reqs = {
                len: {
                    el: document.getElementById('req-len'),
                    fn: function(v) {
                        return v.length >= 8;
                    }
                },
                up: {
                    el: document.getElementById('req-up'),
                    fn: function(v) {
                        return /[A-Z]/.test(v);
                    }
                },
                low: {
                    el: document.getElementById('req-low'),
                    fn: function(v) {
                        return /[a-z]/.test(v);
                    }
                },
                num: {
                    el: document.getElementById('req-num'),
                    fn: function(v) {
                        return /[0-9]/.test(v);
                    }
                },
                sym: {
                    el: document.getElementById('req-sym'),
                    fn: function(v) {
                        return /[!@#$%^&*\-_+=?]/.test(v);
                    }
                },
                mat: {
                    el: document.getElementById('req-mat'),
                    fn: function(v, c) {
                        return v.length > 0 && v === c;
                    }
                },
            };

            var levels = ['#e8e8f0', '#ef4444', '#f97316', '#eab308', '#22c55e', '#16a34a'];
            var labels = ['', 'Muito fraca', 'Fraca', 'Razoável', 'Forte', 'Muito forte'];

            function updateReqs() {
                var v = pwNew ? pwNew.value : '';
                var c = pwConf ? pwConf.value : '';
                var met = 0;
                var allOk = true;

                Object.keys(reqs).forEach(function(k) {
                    var r = reqs[k];
                    var ok = (k === 'mat') ? r.fn(v, c) : r.fn(v);
                    if (r.el) {
                        r.el.className = 'pw-req' + (ok ? ' ok' : '');
                        r.el.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
                    }
                    if (ok && k !== 'mat') met++;
                    if (!ok) allOk = false;
                });

                if (pwFill) {
                    pwFill.style.width = (met * 20) + '%';
                    pwFill.style.background = levels[met];
                }
                if (pwLbl) {
                    pwLbl.textContent = labels[met] || '';
                    pwLbl.style.color = levels[met];
                }
                if (matchE) matchE.style.display = (c.length > 0 && v !== c) ? 'block' : 'none';
                if (btn3) btn3.disabled = !allOk;
            }

            if (pwNew) pwNew.addEventListener('input', updateReqs);
            if (pwConf) pwConf.addEventListener('input', updateReqs);

            function submitStep3() {
                var v = pwNew ? pwNew.value : '';
                var c = pwConf ? pwConf.value : '';
                if (v !== c || v.length < 8) return;
                var spin = document.getElementById('spin-s3');
                if (spin) spin.classList.remove('d-none');
                if (btn3) btn3.disabled = true;
                document.getElementById('form-step3').submit();
            }
        <?php endif; ?>
    </script>
</body>

</html>