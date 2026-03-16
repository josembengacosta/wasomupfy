<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Recuperar Senha Admin
// Arquivo: admin/auth/forgot-password.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions_admin.php';
startAdminSession();

// Admin já autenticado → painel
if (isAdminLoggedIn() && !isLockscreenActive()) {
    adminRedirect('/admin');
}

$msg_type = $_GET['msg'] ?? null;
$feedback = match($msg_type) {
    'sent'       => ['type' => 'success', 'icon' => 'bi-envelope-check',
                     'text' => 'Se os dados existirem na nossa base, receberás um e-mail com as instruções em breve.'],
    'not_found'  => ['type' => 'danger',  'icon' => 'bi-person-x',
                     'text' => 'Nenhuma conta encontrada com esses dados. Verifica e tenta novamente.'],
    'error'      => ['type' => 'danger',  'icon' => 'bi-exclamation-octagon',
                     'text' => 'Ocorreu um erro ao processar o pedido. Tenta novamente.'],
    'limit'      => ['type' => 'warning', 'icon' => 'bi-clock-history',
                     'text' => 'Muitos pedidos de recuperação. Aguarda alguns minutos antes de tentar novamente.'],
    default      => null,
  };
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <title>Recuperar Senha — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet" />

    <style>
    :root {
        --pink: #FF0089;
        --pink-dark: #cc006e;
        --pink-glow: rgba(255, 0, 137, 0.22);
        --dark: #0c0c0f;
        --off-white: #f8f7fc;
        --text-muted: #888;
        --radius: 14px;
        --font-display: 'Syne', sans-serif;
        --font-body: 'DM Sans', sans-serif;
    }

    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    html,
    body {
        height: 100%;
        font-family: var(--font-body);
        font-size: 15px;
        background: var(--off-white);
        color: #1a1a1a;
        -webkit-font-smoothing: antialiased;
    }

    /* ── Layout split igual ao login ── */
    .auth-shell {
        display: grid;
        grid-template-columns: 1fr 1fr;
        min-height: 100vh;
    }

    @media (max-width: 900px) {
        .auth-shell {
            grid-template-columns: 1fr;
        }

        .auth-left {
            display: none;
        }

        .auth-right {
            padding: 40px 24px;
        }
    }

    /* ── Painel esquerdo ── */
    .auth-left {
        background: var(--dark);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 48px;
    }

    .auth-left::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255, 0, 137, .06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 0, 137, .06) 1px, transparent 1px);
        background-size: 48px 48px;
        pointer-events: none;
    }

    /* Blob rosa posicionado diferente do login */
    .auth-left::after {
        content: '';
        position: absolute;
        bottom: 20%;
        right: 10%;
        width: 320px;
        height: 320px;
        background: radial-gradient(circle, rgba(255, 0, 137, .2) 0%, transparent 70%);
        pointer-events: none;
        animation: pulse-glow 6s ease-in-out infinite alternate;
    }

    @keyframes pulse-glow {
        from {
            transform: scale(1);
            opacity: .6;
        }

        to {
            transform: scale(1.2);
            opacity: 1;
        }
    }

    .brand-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 72px;
        position: relative;
        z-index: 2;
    }

    .brand-logo img {
        width: 44px;
        height: 44px;
        border-radius: 10px;
    }

    .brand-name {
        font-family: var(--font-display);
        font-size: 1.2rem;
        font-weight: 800;
        color: #fff;
    }

    .brand-badge {
        font-size: .65rem;
        font-weight: 600;
        background: var(--pink);
        color: #fff;
        padding: 2px 8px;
        border-radius: 20px;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    /* Ilustração central: ícone grande com anel animado */
    .left-illustration {
        position: relative;
        z-index: 2;
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .illus-ring {
        position: relative;
        width: 180px;
        height: 180px;
    }

    .illus-ring::before,
    .illus-ring::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 50%;
        border: 1.5px solid rgba(255, 0, 137, .3);
        animation: ring-expand 3s ease-out infinite;
    }

    .illus-ring::after {
        animation-delay: 1.5s;
    }

    @keyframes ring-expand {
        0% {
            transform: scale(.8);
            opacity: .8;
        }

        100% {
            transform: scale(1.6);
            opacity: 0;
        }
    }

    .illus-icon {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        color: var(--pink);
        filter: drop-shadow(0 0 24px var(--pink-glow));
    }

    /* Texto inferior esquerdo */
    .left-footer {
        position: relative;
        z-index: 2;
    }

    .left-footer p {
        font-size: .88rem;
        color: #555;
        line-height: 1.7;
        max-width: 300px;
    }

    .left-footer strong {
        color: #888;
    }

    /* ── Painel direito ── */
    .auth-right {
        background: var(--off-white);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 48px 40px;
    }

    .auth-form-wrap {
        width: 100%;
        max-width: 400px;
    }

    /* Botão voltar */
    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: .83rem;
        font-weight: 500;
        color: #888;
        text-decoration: none;
        margin-bottom: 36px;
        transition: color .2s;
    }

    .btn-back:hover {
        color: var(--pink);
    }

    /* Header */
    .form-header {
        margin-bottom: 32px;
    }

    .form-header .access-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        color: var(--pink);
        background: rgba(255, 0, 137, .08);
        border: 1px solid rgba(255, 0, 137, .2);
        padding: 4px 12px;
        border-radius: 20px;
        margin-bottom: 16px;
    }

    .form-header h1 {
        font-family: var(--font-display);
        font-size: 1.75rem;
        font-weight: 800;
        color: #111;
        margin-bottom: 8px;
    }

    .form-header p {
        font-size: .88rem;
        color: var(--text-muted);
        line-height: 1.6;
    }

    /* Tabs de método (e-mail / username) */
    .method-tabs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 28px;
        background: #ededf5;
        padding: 4px;
        border-radius: 10px;
    }

    .method-tab {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 9px 12px;
        border-radius: 8px;
        font-size: .83rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        background: transparent;
        color: #888;
        transition: background .2s, color .2s, box-shadow .2s;
    }

    .method-tab.active {
        background: #fff;
        color: var(--pink);
        box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
    }

    .method-tab i {
        font-size: .9rem;
    }

    /* Inputs */
    .form-label {
        font-size: .82rem;
        font-weight: 600;
        color: #444;
        margin-bottom: 6px;
    }

    .form-control {
        border: 1.5px solid #e0e0e8;
        border-radius: 10px;
        padding: 12px 16px;
        font-size: .9rem;
        font-family: var(--font-body);
        background: #fff;
        color: #111;
        transition: border-color .2s, box-shadow .2s;
    }

    .form-control:focus {
        border-color: var(--pink);
        box-shadow: 0 0 0 3px var(--pink-glow);
        outline: none;
    }

    .form-control.is-invalid {
        border-color: #ef4444;
    }

    .input-icon-wrap {
        position: relative;
    }

    .input-icon-wrap .form-control {
        padding-right: 44px;
    }

    .input-icon-wrap .field-icon {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #bbb;
        font-size: .95rem;
        pointer-events: none;
        transition: color .2s;
    }

    .input-icon-wrap:focus-within .field-icon {
        color: var(--pink);
    }

    /* Botão submit */
    .btn-admin-submit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 13px 24px;
        background: var(--pink);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-family: var(--font-body);
        font-size: .95rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .2s, transform .15s, box-shadow .2s;
        position: relative;
        overflow: hidden;
    }

    .btn-admin-submit::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, .12) 0%, transparent 60%);
    }

    .btn-admin-submit:hover {
        background: var(--pink-dark);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px var(--pink-glow);
    }

    .btn-admin-submit:active {
        transform: translateY(0);
    }

    .btn-admin-submit:disabled {
        opacity: .7;
        cursor: not-allowed;
        transform: none;
    }

    .spinner-btn {
        display: none;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, .4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin .6s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Info box */
    .info-box {
        background: #fff;
        border: 1px solid #e8e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        margin-top: 20px;
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }

    .info-box i {
        color: var(--pink);
        font-size: 1rem;
        margin-top: 1px;
        flex-shrink: 0;
    }

    .info-box p {
        font-size: .82rem;
        color: #666;
        line-height: 1.6;
        margin: 0;
    }

    .info-box strong {
        color: #444;
    }

    /* Alertas */
    .alert-admin {
        border-radius: 10px;
        border: none;
        padding: 12px 16px;
        font-size: .87rem;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 24px;
        animation: slideDown .3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-admin.alert-danger {
        background: #fff0f0;
        color: #b91c1c;
    }

    .alert-admin.alert-warning {
        background: #fffbeb;
        color: #92400e;
    }

    .alert-admin.alert-success {
        background: #f0fdf4;
        color: #166534;
    }

    /* Animações entrada */
    .fade-up {
        opacity: 0;
        transform: translateY(18px);
        transition: opacity .45s ease, transform .45s ease;
    }

    .fade-up.visible {
        opacity: 1;
        transform: translateY(0);
    }

    .delay-1 {
        transition-delay: .05s;
    }

    .delay-2 {
        transition-delay: .12s;
    }

    .delay-3 {
        transition-delay: .19s;
    }

    .delay-4 {
        transition-delay: .26s;
    }

    .delay-5 {
        transition-delay: .33s;
    }

    /* Preloader */
    .preloader {
        position: fixed;
        inset: 0;
        background: var(--off-white);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        transition: opacity .4s, visibility .4s;
    }

    .preloader.hidden {
        opacity: 0;
        visibility: hidden;
    }

    .preloader-ring {
        width: 36px;
        height: 36px;
        border: 3px solid #eee;
        border-top-color: var(--pink);
        border-radius: 50%;
        animation: spin .7s linear infinite;
    }

    /* Link de registo */
    .link-secondary {
        font-size: .84rem;
        color: #aaa;
        text-decoration: none;
        transition: color .2s;
    }

    .link-secondary:hover {
        color: var(--pink);
    }
    </style>
</head>

<body>

    <!-- Preloader -->
    <div class="preloader" id="preloader">
        <div class="preloader-ring"></div>
    </div>

    <div class="auth-shell">

        <!-- ══ Painel Esquerdo ══ -->
        <div class="auth-left">
            <div class="brand-logo">
                <img src="../assets/img/brand/wasomupfy.png" alt="Wasom Upfy" />
                <span class="brand-name">Wasom Upfy</span>
                <span class="brand-badge">Admin</span>
            </div>

            <div class="left-illustration">
                <div class="illus-ring">
                    <div class="illus-icon">
                        <i class="bi bi-key"></i>
                    </div>
                </div>
            </div>

            <div class="left-footer">
                <p>
                    Introduz o teu <strong>e-mail</strong> ou
                    <strong>nome de utilizador</strong> para receberes
                    as instruções de recuperação de senha.<br><br>
                    O link expira em <strong>1 hora</strong> por razões
                    de segurança.
                </p>
            </div>
        </div>

        <!-- ══ Painel Direito — Formulário ══ -->
        <div class="auth-right">
            <div class="auth-form-wrap">

                <!-- Voltar ao login -->
                <a href="login" class="btn-back fade-up delay-1">
                    <i class="bi bi-arrow-left"></i>
                    Voltar ao login
                </a>

                <!-- Cabeçalho -->
                <div class="form-header fade-up delay-1">
                    <div class="access-label">
                        <i class="bi bi-key-fill"></i>
                        Recuperação de Senha
                    </div>
                    <h1>Esqueceste<br>a senha?</h1>
                    <p>
                        Sem problema. Identifica-te abaixo e
                        enviamos as instruções para o teu e-mail.
                    </p>
                </div>

                <!-- Feedback -->
                <?php if ($feedback): ?>
                <div class="alert-admin alert-<?php echo $feedback['type']; ?> fade-up delay-2" role="alert">
                    <i class="bi <?php echo $feedback['icon']; ?>" style="margin-top:1px;flex-shrink:0"></i>
                    <span><?php echo htmlspecialchars($feedback['text']); ?></span>
                </div>
                <?php endif; ?>

                <!-- Tabs de método -->
                <div class="method-tabs fade-up delay-2">
                    <button class="method-tab active" id="tab-email" type="button">
                        <i class="bi bi-envelope"></i>
                        E-mail
                    </button>
                    <button class="method-tab" id="tab-user" type="button">
                        <i class="bi bi-at"></i>
                        Utilizador
                    </button>
                </div>

                <!-- Formulário -->
                <form method="POST" action="forgot-password-process" id="form-forgot" novalidate
                    onsubmit="return false">

                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
                    <input type="hidden" name="method" id="input-method" value="email" />

                    <!-- Honeypot -->
                    <input type="text" name="hp_field" style="display:none" tabindex="-1" autocomplete="off" />

                    <!-- Campo E-mail -->
                    <div class="mb-4 fade-up delay-3" id="field-email">
                        <label class="form-label" for="email_employees">
                            Endereço de e-mail <span class="text-danger">*</span>
                        </label>
                        <div class="input-icon-wrap">
                            <input type="email" class="form-control" id="email_employees" name="email_employees"
                                placeholder="email@wasomupfy.com" maxlength="255" autocomplete="email" />
                            <i class="bi bi-envelope field-icon"></i>
                        </div>
                        <div class="invalid-feedback d-block" id="email-err" style="display:none!important"></div>
                    </div>

                    <!-- Campo Username (oculto por defeito) -->
                    <div class="mb-4 fade-up delay-3" id="field-user" style="display:none">
                        <label class="form-label" for="user_employees">
                            Nome de utilizador <span class="text-danger">*</span>
                        </label>
                        <div class="input-icon-wrap">
                            <input type="text" class="form-control" id="user_employees" name="user_employees"
                                placeholder="@utilizador" maxlength="60" autocomplete="username" />
                            <i class="bi bi-at field-icon"></i>
                        </div>
                        <div class="invalid-feedback d-block" id="user-err" style="display:none!important"></div>
                    </div>

                    <!-- Botão submit -->
                    <div class="fade-up delay-4">
                        <button type="button" class="btn-admin-submit" id="btn-submit">
                            <span class="spinner-btn" id="spinner"></span>
                            <i class="bi bi-send" id="btn-icon"></i>
                            <span id="btn-text">Enviar instruções</span>
                        </button>
                    </div>

                </form>

                <!-- Info box -->
                <div class="info-box fade-up delay-5">
                    <i class="bi bi-info-circle-fill"></i>
                    <p>
                        Por segurança, a resposta é sempre a mesma
                        independentemente de os dados existirem ou não.
                        Verifica a tua caixa de entrada e a pasta de
                        <strong>spam</strong>.
                    </p>
                </div>

                <!-- Link voltar -->
                <div class="text-center mt-4 fade-up delay-5">
                    <a href="login" class="link-secondary">
                        <i class="bi bi-arrow-left me-1"></i>
                        Voltar ao login
                    </a>
                </div>

            </div><!-- /auth-form-wrap -->
        </div><!-- /auth-right -->

    </div><!-- /auth-shell -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {

        // ── Preloader ──────────────────────────────
        window.addEventListener('load', function() {
            document.getElementById('preloader').classList.add('hidden');
            document.querySelectorAll('.fade-up').forEach(el => el.classList.add('visible'));
        });

        // ── Tabs de método ─────────────────────────
        const tabEmail = document.getElementById('tab-email');
        const tabUser = document.getElementById('tab-user');
        const fieldEmail = document.getElementById('field-email');
        const fieldUser = document.getElementById('field-user');
        const inputEmail = document.getElementById('email_employees');
        const inputUser = document.getElementById('user_employees');
        const methodHid = document.getElementById('input-method');

        let activeMethod = 'email';

        tabEmail.addEventListener('click', function() {
            activeMethod = 'email';
            methodHid.value = 'email';

            tabEmail.classList.add('active');
            tabUser.classList.remove('active');

            fieldEmail.style.display = 'block';
            fieldUser.style.display = 'none';

            // Limpar campo anterior
            inputUser.value = '';
            inputUser.classList.remove('is-invalid');
            inputEmail.focus();
        });

        tabUser.addEventListener('click', function() {
            activeMethod = 'user';
            methodHid.value = 'user';

            tabUser.classList.add('active');
            tabEmail.classList.remove('active');

            fieldUser.style.display = 'block';
            fieldEmail.style.display = 'none';

            // Limpar campo anterior
            inputEmail.value = '';
            inputEmail.classList.remove('is-invalid');
            inputUser.focus();
        });

        // ── Validação em tempo real ─────────────────
        inputEmail.addEventListener('input', function() {
            const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value);
            this.classList.toggle('is-invalid', !valid && this.value.length > 4);
            this.classList.toggle('is-valid', valid);
        });

        inputUser.addEventListener('input', function() {
            const clean = this.value.replace(/^@/, '');
            this.classList.toggle('is-invalid', clean.length > 0 && clean.length < 3);
            this.classList.toggle('is-valid', clean.length >= 3);
        });

        // ── Submit ─────────────────────────────────
        const btnSubmit = document.getElementById('btn-submit');
        const spinner = document.getElementById('spinner');
        const btnIcon = document.getElementById('btn-icon');
        const btnText = document.getElementById('btn-text');
        const form = document.getElementById('form-forgot');

        function doSubmit() {
            let valid = true;

            if (activeMethod === 'email') {
                const email = inputEmail.value.trim();
                if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    inputEmail.classList.add('is-invalid');
                    inputEmail.focus();
                    valid = false;
                }
            } else {
                const user = inputUser.value.replace(/^@/, '').trim();
                if (!user || user.length < 3) {
                    inputUser.classList.add('is-invalid');
                    inputUser.focus();
                    valid = false;
                }
            }

            if (!valid) return;

            // Loading
            spinner.style.display = 'block';
            btnIcon.style.display = 'none';
            btnText.textContent = 'A enviar...';
            btnSubmit.disabled = true;

            form.submit();
        }

        btnSubmit.addEventListener('click', doSubmit);

        // Enter nos campos → submit
        [inputEmail, inputUser].forEach(function(inp) {
            inp.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') doSubmit();
            });
        });

        // Remover is-invalid ao digitar
        [inputEmail, inputUser].forEach(function(inp) {
            inp.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });

    });
    </script>
</body>

</html>