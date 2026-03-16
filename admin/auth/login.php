<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Login do Painel Admin
// Arquivo: admin/auth/login.php
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions_admin.php';
startAdminSession();
checkAdminRememberMe();

// Se já está autenticado e sem lockscreen → painel
if (isAdminLoggedIn() && !isLockscreenActive()) {
    adminRedirect('/admin');
}

// Mensagens de feedback vindas do login-process
$msg_type = $_GET['msg'] ?? null;
$feedback = match ($msg_type) {
    'expired'   => [
        'type' => 'warning',
        'icon' => 'bi-clock-history',
        'text' => 'A tua sessão expirou. Introduz as credenciais novamente.'
    ],
    'blocked'   => [
        'type' => 'danger',
        'icon' => 'bi-shield-lock',
        'text' => 'Conta temporariamente bloqueada por excesso de tentativas.'
    ],
    'inactive'  => [
        'type' => 'danger',
        'icon' => 'bi-person-x',
        'text' => 'Esta conta está inactiva. Contacta o super administrador.'
    ],
    'error'     => [
        'type' => 'danger',
        'icon' => 'bi-exclamation-octagon',
        'text' => 'Credenciais inválidas. Verifica o e-mail e a senha.'
    ],
    'logout'    => [
        'type' => 'success',
        'icon' => 'bi-check-circle',
        'text' => 'Sessão terminada com sucesso.'
    ],
    'reset_ok'  => [
        'type' => 'success',
        'icon' => 'bi-check-circle',
        'text' => 'Senha redefinida com sucesso. Podes iniciar sessão.'
    ],
    default     => null,
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
    <meta name="admin_csrf_token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
    <title>Acesso Restrito — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />

    <!-- Bootstrap 5.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <!-- Google Fonts: Syne (display) + DM Sans (body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet" />

    <style>
    /* ─── Variables ─────────────────────────────── */
    :root {
        --pink: #FF0089;
        --pink-dark: #cc006e;
        --pink-glow: rgba(255, 0, 137, 0.25);
        --dark: #0c0c0f;
        --dark-2: #141418;
        --dark-3: #1c1c22;
        --dark-border: #2a2a33;
        --off-white: #f8f7fc;
        --text-muted: #888;
        --radius: 14px;
        --font-display: 'Syne', sans-serif;
        --font-body: 'DM Sans', sans-serif;
    }

    /* ─── Reset & Base ──────────────────────────── */
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

    /* ─── Layout Principal ──────────────────────── */
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
    }

    /* ─── Painel Esquerdo (Dark/Brand) ──────────── */
    .auth-left {
        background: var(--dark);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 48px;
    }

    /* Grid pattern decorativo */
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

    /* Blob de luz rosa no centro */
    .auth-left::after {
        content: '';
        position: absolute;
        top: 30%;
        left: 20%;
        width: 380px;
        height: 380px;
        background: radial-gradient(circle, rgba(255, 0, 137, .18) 0%, transparent 70%);
        pointer-events: none;
        animation: pulse-glow 5s ease-in-out infinite alternate;
    }

    @keyframes pulse-glow {
        from {
            transform: scale(1);
            opacity: .7;
        }

        to {
            transform: scale(1.15);
            opacity: 1;
        }
    }

    /* Linha diagonal decorativa */
    .auth-left .deco-line {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 60%;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--pink), transparent);
        opacity: .4;
    }

    .brand-area {
        position: relative;
        z-index: 2;
    }

    .brand-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 60px;
    }

    .brand-logo img {
        width: 44px;
        height: 44px;
        border-radius: 10px;
    }

    .brand-logo .brand-name {
        font-family: var(--font-display);
        font-size: 1.2rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: .5px;
    }

    .brand-logo .brand-badge {
        font-size: .65rem;
        font-weight: 600;
        background: var(--pink);
        color: #fff;
        padding: 2px 8px;
        border-radius: 20px;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .brand-headline {
        font-family: var(--font-display);
        font-size: 2.6rem;
        font-weight: 800;
        color: #fff;
        line-height: 1.15;
        margin-bottom: 20px;
    }

    .brand-headline span {
        color: var(--pink);
    }

    .brand-sub {
        font-size: .95rem;
        color: #888;
        line-height: 1.65;
        max-width: 340px;
    }

    /* Stats inferiores */
    .brand-stats {
        position: relative;
        z-index: 2;
        display: flex;
        gap: 32px;
    }

    .stat-item {
        border-left: 2px solid var(--pink);
        padding-left: 14px;
    }

    .stat-value {
        font-family: var(--font-display);
        font-size: 1.5rem;
        font-weight: 800;
        color: #fff;
    }

    .stat-label {
        font-size: .78rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    /* ─── Painel Direito (Form) ─────────────────── */
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

    /* Header do form */
    .form-header {
        margin-bottom: 36px;
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
        font-size: 1.9rem;
        font-weight: 800;
        color: #111;
        margin-bottom: 6px;
    }

    .form-header p {
        font-size: .88rem;
        color: var(--text-muted);
        font-weight: 400;
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
        box-shadow: 0 0 0 3px rgba(239, 68, 68, .15);
    }

    .form-control.is-valid {
        border-color: #22c55e;
        box-shadow: none;
    }

    .input-group .form-control {
        border-right: none;
        border-radius: 10px 0 0 10px;
    }

    .input-group .btn-eye {
        border: 1.5px solid #e0e0e8;
        border-left: none;
        border-radius: 0 10px 10px 0;
        background: #fff;
        color: #888;
        padding: 0 16px;
        transition: color .2s;
        cursor: pointer;
    }

    .input-group .btn-eye:hover {
        color: var(--pink);
    }

    .input-group:focus-within .btn-eye {
        border-color: var(--pink);
    }

    .input-group:focus-within .form-control {
        border-color: var(--pink);
        box-shadow: 0 0 0 3px var(--pink-glow);
    }

    /* Checkbox */
    .form-check-input:checked {
        background-color: var(--pink);
        border-color: var(--pink);
    }

    .form-check-input:focus {
        box-shadow: 0 0 0 3px var(--pink-glow);
    }

    .form-check-label {
        font-size: .85rem;
        color: #555;
    }

    /* Link forgot password */
    .link-forgot {
        font-size: .85rem;
        color: #555;
        text-decoration: none;
        transition: color .2s;
    }

    .link-forgot:hover {
        color: var(--pink);
    }

    /* Botão submit */
    .btn-admin-login {
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
        letter-spacing: .2px;
        cursor: pointer;
        transition: background .2s, transform .15s, box-shadow .2s;
        position: relative;
        overflow: hidden;
    }

    .btn-admin-login::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, .12) 0%, transparent 60%);
    }

    .btn-admin-login:hover {
        background: var(--pink-dark);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px var(--pink-glow);
    }

    .btn-admin-login:active {
        transform: translateY(0);
    }

    .btn-admin-login .spinner-btn {
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

    /* Divider segurança */
    .security-note {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 24px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        font-size: .78rem;
        color: #aaa;
    }

    .security-note i {
        color: #ccc;
        font-size: .85rem;
    }

    /* Alertas de feedback */
    .alert-admin {
        border-radius: 10px;
        border: none;
        padding: 12px 16px;
        font-size: .87rem;
        display: flex;
        align-items: center;
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

    /* ─── Preloader ─────────────────────────────── */
    .preloader {
        position: fixed;
        inset: 0;
        background: var(--off-white);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        transition: opacity .4s ease, visibility .4s;
    }

    .preloader.hidden {
        opacity: 0;
        visibility: hidden;
    }

    .preloader-inner {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 16px;
    }

    .preloader-ring {
        width: 36px;
        height: 36px;
        border: 3px solid #eee;
        border-top-color: var(--pink);
        border-radius: 50%;
        animation: spin .7s linear infinite;
    }

    /* ─── Animações de entrada ──────────────────── */
    .fade-up {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity .5s ease, transform .5s ease;
    }

    .fade-up.visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* Delays escalonados */
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

    .delay-6 {
        transition-delay: .40s;
    }

    /* ─── Mobile logo ───────────────────────────── */
    .mobile-brand {
        display: none;
        align-items: center;
        gap: 10px;
        margin-bottom: 32px;
    }

    @media (max-width: 900px) {
        .mobile-brand {
            display: flex;
        }

        .auth-right {
            padding: 40px 24px;
        }
    }

    /* ─── Scrollbar ─────────────────────────────── */
    ::-webkit-scrollbar {
        width: 6px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: #ddd;
        border-radius: 3px;
    }
    </style>
</head>

<body>

    <!-- Preloader -->
    <div class="preloader" id="preloader">
        <div class="preloader-inner">
            <div class="preloader-ring"></div>
        </div>
    </div>

    <div class="auth-shell">

        <!-- ══ Painel Esquerdo — Brand ══ -->
        <div class="auth-left">
            <div class="brand-area">
                <div class="brand-logo">
                    <img src="../assets/img/brand/wasomupfy.png" alt="Wasom Upfy" />
                    <span class="brand-name">Wasom Upfy</span>
                    <span class="brand-badge">Admin</span>
                </div>

                <h2 class="brand-headline">
                    Centro de<br />
                    <span>Controlo</span><br />
                    da Plataforma
                </h2>
                <p class="brand-sub">
                    Painel exclusivo para a equipa interna.
                    Gere utilizadores, aprova lançamentos e
                    supervisiona todas as operações da plataforma.
                </p>
            </div>

            <div class="brand-stats">
                <div class="stat-item">
                    <div class="stat-value">v2.0</div>
                    <div class="stat-label">Versão</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">AO</div>
                    <div class="stat-label">Origem</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">100%</div>
                    <div class="stat-label">Seguro</div>
                </div>
            </div>

            <div class="deco-line"></div>
        </div>

        <!-- ══ Painel Direito — Formulário ══ -->
        <div class="auth-right">
            <div class="auth-form-wrap">

                <!-- Logo mobile -->
                <div class="mobile-brand">
                    <img src="../assets/img/brand/wasomupfy_authentic.png" alt="Wasom Upfy" width="36" height="36"
                        style="border-radius:8px" />
                    <span style="font-family:var(--font-display);font-weight:800;font-size:1.1rem">
                        Wasom Upfy
                    </span>
                    <span class="brand-badge" style="font-size:.62rem;background:var(--pink);
                          color:#fff;padding:2px 8px;border-radius:20px;
                          font-weight:600;letter-spacing:.5px;text-transform:uppercase">
                        Admin
                    </span>
                </div>

                <!-- Cabeçalho -->
                <div class="form-header fade-up delay-1">
                    <div class="access-label">
                        <i class="bi bi-shield-lock-fill"></i>
                        Acesso Restrito
                    </div>
                    <h1>Bem-vindo de volta</h1>
                    <p>Introduz as tuas credenciais de administrador</p>
                </div>

                <!-- Alerta de feedback -->
                <?php if ($feedback): ?>
                <div class="alert-admin alert-<?php echo $feedback['type']; ?> fade-up delay-2" role="alert">
                    <i class="bi <?php echo $feedback['icon']; ?>"></i>
                    <span><?php echo htmlspecialchars($feedback['text']); ?></span>
                </div>
                <?php endif; ?>

                <!-- Formulário -->
                <form method="POST" action="<?php echo APP_URL; ?>/admin/login-process" id="form-login" novalidate
                    onsubmit="return false">

                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />

                    <!-- Honeypot anti-bot (oculto) -->
                    <input type="text" name="hp_field" style="display:none" tabindex="-1" autocomplete="off" />

                    <!-- E-mail -->
                    <div class="mb-3 fade-up delay-2">
                        <label class="form-label" for="email_admin">
                            E-mail <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="email" class="form-control" id="email_admin" name="email_employees"
                                placeholder="email@wasomupfy.com" maxlength="255" autocomplete="email" required />
                            <span class="btn-eye" style="pointer-events:none">
                                <i class="bi bi-envelope"></i>
                            </span>
                        </div>
                        <div class="invalid-feedback" id="email-feedback">
                            Introduz um e-mail válido.
                        </div>
                    </div>

                    <!-- Senha -->
                    <div class="mb-3 fade-up delay-3">
                        <label class="form-label" for="password_admin">
                            Senha <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password_admin" name="password_employees"
                                placeholder="A tua senha de acesso" maxlength="128" autocomplete="current-password"
                                required />
                            <button type="button" class="btn-eye" id="btn-toggle-pass"
                                aria-label="Mostrar/esconder senha">
                                <i class="bi bi-eye" id="eye-icon"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">
                            Introduz a tua senha.
                        </div>
                    </div>

                    <!-- Lembrar + Esqueceu -->
                    <div class="mb-4 d-flex align-items-center justify-content-between fade-up delay-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember_admin" name="remember_me" />
                            <label class="form-check-label" for="remember_admin">
                                Lembrar-me
                            </label>
                        </div>
                        <a href="forgot-password" class="link-forgot">
                            Esqueceste a senha?
                        </a>
                    </div>

                    <!-- Botão submit -->
                    <div class="fade-up delay-5">
                        <button type="button" class="btn-admin-login" id="btn-submit">
                            <span class="spinner-btn" id="spinner"></span>
                            <i class="bi bi-box-arrow-in-right" id="btn-icon"></i>
                            <span id="btn-text">Entrar no Painel</span>
                        </button>
                    </div>

                </form>

                <!-- Nota de segurança -->
                <div class="security-note fade-up delay-6">
                    <i class="bi bi-lock-fill"></i>
                    <span>
                        Esta área é de <strong style="color:#555">acesso exclusivo</strong>
                        à equipa Wasom Upfy. Todas as acções são registadas.
                    </span>
                </div>

            </div><!-- /auth-form-wrap -->
        </div><!-- /auth-right -->

    </div><!-- /auth-shell -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {

        // ── Preloader ──────────────────────────────────
        window.addEventListener('load', function() {
            document.getElementById('preloader').classList.add('hidden');
            // Activar animações de entrada
            document.querySelectorAll('.fade-up').forEach(function(el) {
                el.classList.add('visible');
            });
        });

        // ── Toggle visibilidade da senha ───────────────
        const passInput = document.getElementById('password_admin');
        const eyeIcon = document.getElementById('eye-icon');

        document.getElementById('btn-toggle-pass').addEventListener('click', function() {
            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeIcon.className = 'bi bi-eye-slash';
            } else {
                passInput.type = 'password';
                eyeIcon.className = 'bi bi-eye';
            }
            passInput.focus();
        });

        // ── Validação em tempo real do e-mail ──────────
        const emailInput = document.getElementById('email_admin');
        emailInput.addEventListener('input', function() {
            const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value);
            this.classList.toggle('is-invalid', !valid && this.value.length > 4);
            this.classList.toggle('is-valid', valid);
        });

        // ── Submit com loading ─────────────────────────
        const btnSubmit = document.getElementById('btn-submit');
        const spinner = document.getElementById('spinner');
        const btnIcon = document.getElementById('btn-icon');
        const btnText = document.getElementById('btn-text');
        const form = document.getElementById('form-login');

        btnSubmit.addEventListener('click', function() {
            const email = emailInput.value.trim();
            const password = passInput.value.trim();

            // Validação básica antes do submit
            let valid = true;

            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                emailInput.classList.add('is-invalid');
                valid = false;
            }

            if (!password) {
                passInput.classList.add('is-invalid');
                valid = false;
            }

            if (!valid) return;

            // Mostrar estado de loading
            spinner.style.display = 'block';
            btnIcon.style.display = 'none';
            btnText.textContent = 'A verificar...';
            btnSubmit.disabled = true;

            // Submeter o form
            form.submit();
        });

        // Limpar is-invalid ao digitar
        passInput.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });

        // ── Enter no campo de senha → submit ───────────
        passInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') btnSubmit.click();
        });
        emailInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') btnSubmit.click();
        });

    });
    </script>
</body>

</html>