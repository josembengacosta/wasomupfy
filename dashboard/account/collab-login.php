<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Login de Colaboradores
// Arquivo: dashboard/account/collab-login.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();

$db         = getDB();
$error      = '';
$show_setup = false;
$collab_data = null;

// ══════════════════════════════════════════════
// ACÇÃO: alterar senha no primeiro login (AJAX)
// ══════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action_change_password'])) {
    header('Content-Type: application/json; charset=utf-8');

    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['ok' => false, 'message' => 'Sessão expirada. Recarrega a página.']);
        exit;
    }
    if (empty($_SESSION['collab_id'])) {
        echo json_encode(['ok' => false, 'message' => 'Sessão não encontrada. Faz login novamente.']);
        exit;
    }

    $id_collab = (int)$_SESSION['collab_id'];
    $id_users  = (int)$_SESSION['collab_id_users'];
    $new_pwd   = trim($_POST['new_password']     ?? '');
    $conf_pwd  = trim($_POST['confirm_password'] ?? '');

    if (strlen($new_pwd) < 8) {
        echo json_encode(['ok' => false, 'message' => 'A senha deve ter pelo menos 8 caracteres.']);
        exit;
    }
    if ($new_pwd !== $conf_pwd) {
        echo json_encode(['ok' => false, 'message' => 'As senhas não coincidem.']);
        exit;
    }

    $db->prepare("
        UPDATE _collaborators
        SET password_collab = ?, must_change_password = 0, last_seen_at = NOW()
        WHERE id_collab = ?
    ")->execute([password_hash($new_pwd, PASSWORD_DEFAULT), $id_collab]);

    try {
        $db->prepare("
            INSERT INTO _collab_activity (id_collab, id_users, activity_type, description, ip_address)
            VALUES (?,?,?,?,?)
        ")->execute([$id_collab, $id_users, 'password_changed', 'Senha definida no primeiro login', $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Exception $e) {
    }

    $_SESSION['collab_must_change'] = false;
    $_SESSION['collab_first_login'] = false;

    echo json_encode([
        'ok'       => true,
        'message'  => 'Senha definida!',
        'redirect' => APP_URL.'/' . APP_URL_PANEL .'/collab/overview',
    ]);
    exit;
}

// ══════════════════════════════════════════════
// ACÇÃO: login normal (POST form)
// ══════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['login_collab'])) {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sessão expirada. Recarrega a página.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';

        $st = $db->prepare("
            SELECT * FROM _collaborators
            WHERE (email_collab = ? OR user_collab = ?)
            LIMIT 1
        ");
        $st->execute([$identifier, $identifier]);
        $collab = $st->fetch();

        if (!$collab) {
            $error = 'Utilizador não encontrado.';
        } elseif ($collab['status_collab'] === 'pending') {
            $error = 'A tua conta ainda não foi activada. Verifica o email de convite.';
        } elseif ($collab['status_collab'] === 'blocked') {
            $error = 'A tua conta está bloqueada. Contacta o administrador da conta.';
        } elseif ($collab['status_collab'] === 'inactive') {
            $error = 'A tua conta está inactiva.';
        } elseif (!password_verify($password, $collab['password_collab'])) {
            $error = 'Senha incorrecta.';
            try {
                $db->prepare("INSERT INTO _collab_activity (id_collab,id_users,activity_type,description,ip_address) VALUES (?,?,?,?,?)")
                    ->execute([$collab['id_collab'], $collab['id_users'], 'login_failed', 'Tentativa de login falhada', $_SERVER['REMOTE_ADDR'] ?? null]);
            } catch (Exception $e) {
            }
        } else {
            // ── LOGIN BEM SUCEDIDO ──
            $collab_data = $collab;
            $is_first    = !empty($collab['must_change_password']) || empty($collab['first_login_at']);

            $db->prepare("UPDATE _collaborators SET last_login_at=NOW(), last_login_ip=?, last_seen_at=NOW() WHERE id_collab=?")
                ->execute([$_SERVER['REMOTE_ADDR'] ?? null, $collab['id_collab']]);

            $session_token = bin2hex(random_bytes(32));
            $db->prepare("INSERT INTO _collab_sessions (id_collab,id_users,session_token,ip_address,user_agent) VALUES (?,?,?,?,?)")
                ->execute([$collab['id_collab'], $collab['id_users'], $session_token, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);

            $_SESSION['collab_id']            = $collab['id_collab'];
            $_SESSION['collab_id_users']      = $collab['id_users'];
            $_SESSION['collab_role']          = $collab['role_collab'];
            $_SESSION['collab_session_token'] = $session_token;
            $_SESSION['collab_must_change']   = (bool)$collab['must_change_password'];
            $_SESSION['collab_first_login']   = $is_first;

            try {
                $db->prepare("INSERT INTO _collab_activity (id_collab,id_users,activity_type,description,ip_address) VALUES (?,?,?,?,?)")
                    ->execute([$collab['id_collab'], $collab['id_users'], 'login', 'Login realizado com sucesso', $_SERVER['REMOTE_ADDR'] ?? null]);
            } catch (Exception $e) {
            }

            if ($is_first) {
                $show_setup = true;
            } else {
                header('Location: ' . rtrim(APP_URL, '/') . '/dashboard/collab/overview');
                exit;
            }
        }
    }
}

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = htmlspecialchars($_SESSION['csrf_token']);

$role_labels = ['admin' => 'Administrador', 'editor' => 'Editor', 'analyst' => 'Analista', 'support' => 'Suporte'];
$role_label  = $collab_data ? ($role_labels[$collab_data['role_collab']] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="theme-color" content="#FF0089" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <link rel="manifest" href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/manifest.json" />
    <title>Painel de Colaboradores — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="<?php echo APP_URL ?>/assets/img/icones/wasomupfy_fiv.png" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <style>
    :root {
        --wasom: #FF0089;
    }

    * {
        box-sizing: border-box;
    }

    body {
        background: linear-gradient(135deg, #1a0010 0%, #2d0020 50%, #0d0010 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', Arial, sans-serif;
        padding: 16px;
    }

    /* ── Particles ── */
    .particles {
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        overflow: hidden;
    }

    .particle {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 0, 137, .12);
        animation: float-up linear infinite;
    }

    @keyframes float-up {
        0% {
            transform: translateY(110vh) scale(0);
            opacity: 0;
        }

        10% {
            opacity: 1;
        }

        90% {
            opacity: .4;
        }

        100% {
            transform: translateY(-80px) scale(1.2);
            opacity: 0;
        }
    }

    /* ── Card ── */
    .login-card {
        max-width: 420px;
        width: 100%;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 28px 80px rgba(0, 0, 0, .65);
        position: relative;
        z-index: 1;
    }

    .login-header {
        background: linear-gradient(135deg, #FF0089, #FF4D4D);
        padding: 36px 32px 28px;
        text-align: center;
    }

    .login-body {
        background: #fff;
        padding: 32px;
    }

    /* ── Badge ── */
    .collab-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, .18);
        color: #fff;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: .75rem;
        font-weight: 700;
        border: 1px solid rgba(255, 255, 255, .25);
        margin-top: 10px;
    }

    /* ── Form ── */
    .form-control:focus {
        border-color: var(--wasom);
        box-shadow: 0 0 0 .2rem rgba(255, 0, 137, .18);
    }

    /* ── Buttons ── */
    .btn-wasom {
        background: linear-gradient(135deg, #FF0089, #FF4D4D);
        color: #fff;
        border: none;
        border-radius: 50px;
        padding: 13px;
        font-weight: 800;
        width: 100%;
        transition: transform .2s, box-shadow .2s;
    }

    .btn-wasom:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(255, 0, 137, .4);
        color: #fff;
    }

    .btn-wasom:active {
        transform: translateY(0);
    }

    .btn-wasom:disabled {
        transform: none;
        box-shadow: none;
        opacity: .65;
        color: #fff;
    }

    /* ── PWA card ── */
    .pwa-card {
        background: linear-gradient(135deg, #1e0015, #2d0020);
        border-radius: 18px;
        padding: 24px 20px;
        color: #fff;
        text-align: center;
        border: 1px solid rgba(255, 0, 137, .2);
    }

    /* ── Strength bar ── */
    .strength-track {
        height: 5px;
        border-radius: 4px;
        background: #e9ecef;
        margin-top: 6px;
    }

    .strength-fill {
        height: 100%;
        border-radius: 4px;
        transition: width .3s, background .3s;
    }
    </style>
</head>

<body>

    <div class="particles" id="particles"></div>

    <div class="login-card">

        <!-- Header -->
        <div class="login-header">
            <div style="font-size:2.6rem;line-height:1;margin-bottom:6px"><img
                    src="<?php echo APP_URL ?>/assets/img/brand/wasomupfy_authentic.png" class="img-fluid" width="90"
                    height="90" alt="Wasom Upfy">
            </div>
            <h1 style="color:#fff;margin:0;font-size:1.4rem;font-weight:900;letter-spacing:-.3px">
                <?php echo APP_NAME; ?>
            </h1>
            <div class="collab-badge">
                <i class="bi bi-people-fill"></i>Painel de Colaboradores
            </div>
            <p style="color:rgba(255,255,255,.65);margin:10px 0 0;font-size:.8rem">
                Acesso exclusivo para membros da equipa
            </p>
        </div>

        <!-- Body -->
        <div class="login-body">

            <?php if ($error): ?>
            <div class="alert alert-danger small d-flex gap-2 mb-3 py-2">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0 mt-1"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
            <?php endif; ?>


            <?php if (!$show_setup): ?>
            <!-- ══ FORMULÁRIO LOGIN ══ -->
            <form method="POST" id="login-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>" />
                <input type="hidden" name="login_collab" value="1" />

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Email ou username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-person text-muted"></i></span>
                        <input type="text" class="form-control" name="identifier" placeholder="@username ou email..."
                            value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>" autocomplete="username"
                            required autofocus />
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold small">Senha</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" class="form-control" name="password" id="pwd-input"
                            placeholder="••••••••" autocomplete="current-password" required />
                        <button class="btn btn-outline-secondary" type="button"
                            onclick="const i=document.getElementById('pwd-input');const s=i.type==='password';i.type=s?'text':'password';this.querySelector('i').className=s?'bi bi-eye-slash':'bi bi-eye'">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-wasom" id="btn-login">
                    <span id="login-text"><i class="bi bi-box-arrow-in-right me-1"></i>Entrar no painel</span>
                    <span id="login-load" class="d-none">
                        <span class="spinner-border spinner-border-sm me-1"></span>A autenticar...
                    </span>
                </button>
            </form>

            <div class="text-center mt-4" style="font-size:.77rem;color:#bbb;line-height:1.9">
                <i class="bi bi-shield-lock me-1"></i>Acesso restrito a membros convidados.<br />
                <a href="<?php echo rtrim(APP_URL, '/'); ?>/login" style="color:#aaa;text-decoration:none">← Voltar ao
                    portal principal</a>
            </div>


            <?php else: ?>
            <!-- ══ PRIMEIRO LOGIN — SETUP ══ -->
            <div class="text-center mb-4">
                <?php if ($collab_data['photo_collab']): ?>
                <img src="<?php echo htmlspecialchars($collab_data['photo_collab']); ?>"
                    style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:3px solid var(--wasom)"
                    onerror="this.style.display='none'" alt="" />
                <?php else: ?>
                <div
                    style="width:64px;height:64px;border-radius:50%;background:rgba(255,0,137,.1);display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto">
                    🎤</div>
                <?php endif; ?>
                <h4 class="fw-bold mt-2 mb-0" style="font-size:1.1rem">
                    Bem-vindo, <?php echo htmlspecialchars($collab_data['first_name']); ?>! 🎉
                </h4>
                <div class="text-muted small"><?php echo htmlspecialchars($role_label); ?></div>
            </div>

            <!-- PWA prompt -->
            <div class="pwa-card mb-4" id="pwa-section">
                <div style="font-size:2.4rem;margin-bottom:8px">📱</div>
                <h5 style="color:#fff;font-weight:800;margin-bottom:6px;font-size:.95rem">
                    Instala o App no teu dispositivo
                </h5>
                <p style="color:rgba(255,255,255,.7);font-size:.8rem;margin-bottom:16px;line-height:1.6">
                    Acesso rápido, notificações e uso offline com a PWA do <?php echo APP_NAME; ?>.
                </p>
                <button class="btn btn-sm mb-2" id="btn-install-pwa"
                    style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:20px;font-size:.8rem;width:100%">
                    <i class="bi bi-download me-1"></i>Instalar App (PWA)
                </button>
                <button class="btn btn-link btn-sm"
                    style="color:rgba(255,255,255,.45);font-size:.75rem;text-decoration:none;width:100%"
                    onclick="skipPwa()">
                    Não agora, continuar →
                </button>
            </div>

            <!-- Mudar senha (aparece depois de fechar PWA) -->
            <div id="change-pwd-section" class="d-none">
                <p class="fw-semibold small mb-3">
                    <i class="bi bi-key me-1" style="color:var(--wasom)"></i>Define a tua senha permanente
                </p>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nova Senha <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="first-new-pwd" placeholder="Mín. 8 caracteres"
                            oninput="checkStrength(this.value)" required />
                        <button class="btn btn-outline-secondary" type="button"
                            onclick="togglePwd('first-new-pwd',this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="strength-track mt-1">
                        <div class="strength-fill" id="str-bar" style="width:0;background:#dc3545"></div>
                    </div>
                    <small id="str-label" class="text-muted" style="font-size:.7rem"></small>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Confirmar Senha <span
                            class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="first-conf-pwd" placeholder="Repete a senha" />
                        <button class="btn btn-outline-secondary" type="button"
                            onclick="togglePwd('first-conf-pwd',this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div id="change-pwd-feedback" class="mb-2 d-none"></div>
                <button class="btn btn-wasom" type="button" id="btn-change-pwd" onclick="changeFirstPassword()">
                    <span id="cpwd-text"><i class="bi bi-check2-circle me-1"></i>Definir senha e entrar</span>
                    <span id="cpwd-load" class="d-none">
                        <span class="spinner-border spinner-border-sm me-1"></span>A guardar...
                    </span>
                </button>
            </div>
            <?php endif; ?>

        </div><!-- /login-body -->
    </div><!-- /login-card -->

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
    toastr.options = {
        progressBar: true,
        closeButton: true,
        positionClass: 'toast-top-right',
        timeOut: 4000
    };

    const CSRF = '<?php echo $csrf; ?>';
    const SELF_URL = '<?php echo rtrim(APP_URL, "/" . APP_URL_PANEL);
                            "/account/collab-login"; ?>';
    const DASH_URL = '<?php echo rtrim(APP_URL, "/" . APP_URL_PANEL);
                            "/collab/overview"; ?>';

    // ── Particles ─────────────────────────────────
    (function() {
        const c = document.getElementById('particles');
        for (let i = 0; i < 14; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            const sz = 16 + Math.random() * 55;
            p.style.cssText = `width:${sz}px;height:${sz}px;left:${Math.random()*100}%;` +
                `animation-duration:${9+Math.random()*14}s;animation-delay:${Math.random()*12}s`;
            c.appendChild(p);
        }
    })();

    // ── Login loading ──────────────────────────────
    document.getElementById('login-form')?.addEventListener('submit', () => {
        document.getElementById('login-text').classList.add('d-none');
        document.getElementById('login-load').classList.remove('d-none');
        document.getElementById('btn-login').disabled = true;
    });

    // ── PWA install ────────────────────────────────
    let deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', e => {
        e.preventDefault();
        deferredPrompt = e;
    });

    document.getElementById('btn-install-pwa')?.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const {
                outcome
            } = await deferredPrompt.userChoice;
            deferredPrompt = null;
            if (outcome === 'accepted') toastr.success('App instalado com sucesso!');
        } else {
            await Swal.fire({
                icon: 'info',
                title: 'Como instalar',
                html: `<div style="text-align:left;font-size:.9rem;line-height:2">
                <strong>Android Chrome:</strong> Menu ⋮ → Adicionar ao ecrã inicial<br/>
                <strong>iOS Safari:</strong> Botão de partilha → Adicionar ao ecrã inicial<br/>
                <strong>Chrome / Edge desktop:</strong> Ícone ⊕ na barra de endereço
            </div>`,
                confirmButtonColor: '#FF0089'
            });
        }
        skipPwa();
    });

    function skipPwa() {
        document.getElementById('pwa-section')?.classList.add('d-none');
        document.getElementById('change-pwd-section')?.classList.remove('d-none');
        document.getElementById('first-new-pwd')?.focus();
    }

    // ── Password helpers ───────────────────────────
    function togglePwd(id, btn) {
        const inp = document.getElementById(id);
        const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    }

    function checkStrength(pwd) {
        const bar = document.getElementById('str-bar');
        const lbl = document.getElementById('str-label');
        let s = 0;
        if (pwd.length >= 8) s++;
        if (pwd.length >= 12) s++;
        if (/[A-Z]/.test(pwd)) s++;
        if (/[0-9]/.test(pwd)) s++;
        if (/[^A-Za-z0-9]/.test(pwd)) s++;
        const map = [
            [20, '#dc3545', 'Muito fraca'],
            [40, '#fd7e14', 'Fraca'],
            [60, '#ffc107', 'Razoável'],
            [80, '#20c997', 'Boa'],
            [100, '#198754', 'Excelente']
        ];
        const [w, c, t] = map[s - 1] || [8, '#dc3545', 'Muito fraca'];
        bar.style.width = w + '%';
        bar.style.background = c;
        lbl.textContent = t;
        lbl.style.color = c;
    }

    // ── Mudar senha (primeiro login) — POST para self ──
    async function changeFirstPassword() {
        const pwd = document.getElementById('first-new-pwd').value.trim();
        const conf = document.getElementById('first-conf-pwd').value.trim();
        const fb = document.getElementById('change-pwd-feedback');
        const btn = document.getElementById('btn-change-pwd');

        fb.classList.add('d-none');

        if (pwd.length < 8) {
            fb.innerHTML = '<div class="alert alert-danger small py-2">Mínimo 8 caracteres.</div>';
            fb.classList.remove('d-none');
            return;
        }
        if (pwd !== conf) {
            fb.innerHTML = '<div class="alert alert-danger small py-2">As senhas não coincidem.</div>';
            fb.classList.remove('d-none');
            return;
        }

        document.getElementById('cpwd-text').classList.add('d-none');
        document.getElementById('cpwd-load').classList.remove('d-none');
        btn.disabled = true;

        const fd = new FormData();
        fd.append('csrf_token', CSRF);
        fd.append('action_change_password', '1');
        fd.append('new_password', pwd);
        fd.append('confirm_password', conf);

        try {
            const r = await fetch(SELF_URL, {
                method: 'POST',
                body: fd
            });
            const data = await r.json();

            if (data.ok) {
                await Swal.fire({
                    icon: 'success',
                    iconColor: '#FF0089',
                    title: 'Senha definida!',
                    text: 'A entrar no painel...',
                    timer: 1500,
                    showConfirmButton: false
                });
                window.location.href = data.redirect || DASH_URL;
            } else {
                fb.innerHTML = `<div class="alert alert-danger small py-2">${data.message}</div>`;
                fb.classList.remove('d-none');
                document.getElementById('cpwd-text').classList.remove('d-none');
                document.getElementById('cpwd-load').classList.add('d-none');
                btn.disabled = false;
            }
        } catch {
            fb.innerHTML = '<div class="alert alert-danger small py-2">Erro de ligação. Tenta novamente.</div>';
            fb.classList.remove('d-none');
            document.getElementById('cpwd-text').classList.remove('d-none');
            document.getElementById('cpwd-load').classList.add('d-none');
            btn.disabled = false;
        }
    }
    </script>
</body>

</html>