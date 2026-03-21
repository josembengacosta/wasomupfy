<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Lockscreen Admin
// Arquivo: admin/auth/lockscreen.php
// .htaccess: ^admin/lockscreen/?$ → este ficheiro
// ══════════════════════════════════════════════

require_once __DIR__ . '/include/functions_admin.php';
startAdminSession();

// Não está autenticado → login
if (!isAdminLoggedIn()) {
    adminRedirect('/' . ADMIN_PATH . '/login');
}

// Lockscreen não está activo → painel
if (!isLockscreenActive()) {
    adminRedirect('/' . ADMIN_PATH . '');
}

// Dados do admin para mostrar no ecrã
$admin_id   = (int)$_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name']      ?? '';
$admin_role = $_SESSION['admin_role']      ?? '';
$admin_photo = $_SESSION['admin_photo']     ?? null;
$full_name  = $_SESSION['admin_full_name'] ?? $admin_name;

$msg_type = $_GET['msg'] ?? null;
$feedback = match ($msg_type) {
    'wrong'  => [
        'type' => 'danger',
        'icon' => 'bi-x-circle',
        'text' => 'Código incorrecto. Tenta novamente.'
    ],
    'error'  => [
        'type' => 'danger',
        'icon' => 'bi-exclamation-octagon',
        'text' => 'Ocorreu um erro. Tenta novamente.'
    ],
    'limit'  => [
        'type' => 'warning',
        'icon' => 'bi-clock-history',
        'text' => 'Muitas tentativas. Aguarda antes de tentar novamente.'
    ],
    default  => null,
};
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <title>Ecrã Bloqueado — Wasom Upfy Admin</title>
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
            --dark-2: #141418;
            --dark-3: #1c1c22;
            --dark-border: #2a2a33;
            --off-white: #f8f7fc;
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
            -webkit-font-smoothing: antialiased;
        }

        /* ── Fundo escuro total ── */
        body {
            background: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        /* Grid pattern de fundo */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 0, 137, .05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 0, 137, .05) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
            z-index: 0;
        }

        /* Blob rosa no centro */
        body::after {
            content: '';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255, 0, 137, .08) 0%, transparent 65%);
            pointer-events: none;
            z-index: 0;
            animation: pulse-glow 6s ease-in-out infinite alternate;
        }

        @keyframes pulse-glow {
            from {
                transform: translate(-50%, -50%) scale(1);
                opacity: .6;
            }

            to {
                transform: translate(-50%, -50%) scale(1.15);
                opacity: 1;
            }
        }

        /* ── Card principal ── */
        .lock-card {
            position: relative;
            z-index: 2;
            background: var(--dark-2);
            border: 1px solid var(--dark-border);
            border-radius: 20px;
            padding: 48px 40px;
            width: 100%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 32px 64px rgba(0, 0, 0, .6);
        }

        @media (max-width: 480px) {
            .lock-card {
                padding: 36px 24px;
                margin: 0 16px;
            }
        }

        /* Logo topo */
        .lock-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 36px;
        }

        .lock-brand img {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            opacity: .7;
        }

        .lock-brand-name {
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 800;
            color: #444;
            letter-spacing: .5px;
        }

        /* Avatar */
        .lock-avatar-wrap {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }

        .lock-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--pink) 0%, #ff6bb5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 1.8rem;
            color: #fff;
            overflow: hidden;
            margin: 0 auto;
            border: 3px solid var(--dark-3);
            box-shadow: 0 0 0 2px rgba(255, 0, 137, .3);
        }

        .lock-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Ícone de cadeado sobre avatar */
        .lock-badge {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 26px;
            height: 26px;
            background: var(--pink);
            border-radius: 50%;
            border: 2px solid var(--dark-2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .7rem;
            color: #fff;
        }

        /* Info do admin */
        .lock-name {
            font-family: var(--font-display);
            font-size: 1.2rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 4px;
        }

        .lock-role {
            font-size: .8rem;
            color: #555;
            margin-bottom: 8px;
        }

        .lock-email {
            font-size: .8rem;
            color: #444;
            margin-bottom: 32px;
        }

        /* Separador */
        .lock-divider {
            height: 1px;
            background: var(--dark-border);
            margin: 0 -40px 28px;
        }

        @media (max-width: 480px) {
            .lock-divider {
                margin: 0 -24px 28px;
            }
        }

        /* Título */
        .lock-title {
            font-size: .78rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #444;
            margin-bottom: 20px;
        }

        /* Input do código (6 dígitos estilo OTP) */
        .otp-wrap {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 24px;
        }

        .otp-input {
            width: 46px;
            height: 54px;
            border: 1.5px solid var(--dark-border);
            border-radius: 10px;
            background: var(--dark-3);
            color: #fff;
            font-family: var(--font-display);
            font-size: 1.3rem;
            font-weight: 700;
            text-align: center;
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
            caret-color: var(--pink);
            /* Campo invisível por baixo — não usar type=tel aqui */
        }

        .otp-input:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 3px var(--pink-glow);
            background: #1a1a22;
        }

        .otp-input.filled {
            border-color: rgba(255, 0, 137, .5);
            color: var(--pink);
        }

        .otp-input.error {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, .15);
            animation: shake .4s ease;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            20% {
                transform: translateX(-6px);
            }

            40% {
                transform: translateX(6px);
            }

            60% {
                transform: translateX(-4px);
            }

            80% {
                transform: translateX(4px);
            }
        }

        /* Campo hidden real que recebe o código completo */
        #access_code_hidden {
            display: none;
        }

        /* Botão */
        .btn-unlock {
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
            margin-bottom: 20px;
        }

        .btn-unlock::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, .1) 0%, transparent 60%);
        }

        .btn-unlock:hover {
            background: var(--pink-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px var(--pink-glow);
        }

        .btn-unlock:active {
            transform: translateY(0);
        }

        .btn-unlock:disabled {
            opacity: .6;
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

        /* Link logout */
        .lock-logout {
            font-size: .82rem;
            color: #444;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color .2s;
        }

        .lock-logout:hover {
            color: #ef4444;
        }

        /* Alerta */
        .alert-lock {
            border-radius: 10px;
            border: none;
            padding: 11px 14px;
            font-size: .84rem;
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 20px;
            text-align: left;
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

        .alert-lock.alert-danger {
            background: rgba(239, 68, 68, .12);
            color: #fca5a5;
        }

        .alert-lock.alert-warning {
            background: rgba(234, 179, 8, .1);
            color: #fde68a;
        }

        /* Animações entrada */
        .fade-up {
            opacity: 0;
            transform: translateY(16px);
            transition: opacity .4s ease, transform .4s ease;
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

        /* Preloader */
        .preloader {
            position: fixed;
            inset: 0;
            background: var(--dark);
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
            border: 3px solid #222;
            border-top-color: var(--pink);
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        /* Relógio no topo direito */
        .lock-time {
            position: fixed;
            top: 24px;
            right: 32px;
            z-index: 10;
            text-align: right;
        }

        .lock-time .time-val {
            font-family: var(--font-display);
            font-size: 1.6rem;
            font-weight: 700;
            color: #222;
        }

        .lock-time .date-val {
            font-size: .75rem;
            color: #333;
            margin-top: 2px;
        }
    </style>
</head>

<body>

    <div class="preloader" id="preloader">
        <div class="preloader-ring"></div>
    </div>

    <!-- Relógio -->
    <div class="lock-time">
        <div class="time-val" id="clock-time">--:--</div>
        <div class="date-val" id="clock-date"></div>
    </div>

    <!-- Card -->
    <div class="lock-card">

        <!-- Logo -->
        <div class="lock-brand fade-up delay-1">
            <img src="../assets/img/brand/wasomupfy_brand.png" alt="Wasom Upfy" />
            <span class="lock-brand-name"><?php echo APP_NAME; ?></span>
        </div>

        <!-- Avatar + info -->
        <div class="fade-up delay-1">
            <div class="lock-avatar-wrap">
                <div class="lock-avatar">
                    <?php if ($admin_photo): ?>
                        <img src="../assets/comprovantes/uploads/employees/<?php echo htmlspecialchars($admin_photo); ?>"
                            alt="" />
                    <?php else: ?>
                        <?php
                        $parts = explode(' ', trim($full_name), 2);
                        echo strtoupper(substr($parts[0], 0, 1) . substr($parts[1] ?? '', 0, 1));
                        ?>
                    <?php endif; ?>
                </div>
                <div class="lock-badge"><i class="bi bi-lock-fill"></i></div>
            </div>

            <div class="lock-name"><?php echo htmlspecialchars($full_name); ?></div>
            <div class="lock-role"><?php echo getRoleLabel($admin_role); ?></div>
            <div class="lock-email"><?php echo htmlspecialchars($_SESSION['admin_email'] ?? ''); ?></div>
        </div>

        <div class="lock-divider"></div>

        <!-- Formulário -->
        <form method="POST" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/lockscreen-process" id="form-lock" novalidate
            onsubmit="return false">

            <input type="hidden" name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />
            <input type="hidden" name="access_code" id="access_code_hidden" />
            <input type="text" name="hp_field" style="display:none" tabindex="-1" autocomplete="off" />

            <!-- Alerta -->
            <?php if ($feedback): ?>
                <div class="alert-lock alert-<?php echo $feedback['type']; ?> fade-up delay-2" role="alert">
                    <i class="bi <?php echo $feedback['icon']; ?>" style="flex-shrink:0"></i>
                    <span><?php echo htmlspecialchars($feedback['text']); ?></span>
                </div>
            <?php endif; ?>

            <p class="lock-title fade-up delay-2">Introduz o código de acesso</p>

            <!-- Inputs OTP -->
            <div class="otp-wrap fade-up delay-3" id="otp-wrap">
                <input class="otp-input" type="password" maxlength="1" inputmode="numeric" pattern="[0-9]"
                    autocomplete="one-time-code" id="otp-0" />
                <input class="otp-input" type="password" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp-1" />
                <input class="otp-input" type="password" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp-2" />
                <input class="otp-input" type="password" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp-3" />
                <input class="otp-input" type="password" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp-4" />
                <input class="otp-input" type="password" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp-5" />
            </div>

            <!-- Botão -->
            <div class="fade-up delay-4">
                <button type="button" class="btn-unlock" id="btn-unlock" disabled>
                    <span class="spinner-btn" id="spinner"></span>
                    <i class="bi bi-unlock" id="btn-icon"></i>
                    <span id="btn-text">Desbloquear</span>
                </button>
            </div>

        </form>

        <!-- Logout -->
        <div class="fade-up delay-4">
            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/logout" class="lock-logout">
                <i class="bi bi-box-arrow-right"></i>
                Não és tu? Terminar sessão
            </a>
        </div>

    </div><!-- /lock-card -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ── Preloader ──────────────────────────────
            window.addEventListener('load', function() {
                document.getElementById('preloader').classList.add('hidden');
                document.querySelectorAll('.fade-up').forEach(el => el.classList.add('visible'));
                // Focar no primeiro campo
                document.getElementById('otp-0').focus();
            });

            // ── Relógio ────────────────────────────────
            function updateClock() {
                const now = new Date();
                const h = String(now.getHours()).padStart(2, '0');
                const m = String(now.getMinutes()).padStart(2, '0');
                const days = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

                document.getElementById('clock-time').textContent = h + ':' + m;
                document.getElementById('clock-date').textContent =
                    days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()];
            }

            updateClock();
            setInterval(updateClock, 30000);

            // ── Lógica OTP ─────────────────────────────
            const inputs = Array.from(document.querySelectorAll('.otp-input'));
            const hiddenFld = document.getElementById('access_code_hidden');
            const btnUnlock = document.getElementById('btn-unlock');
            const spinner = document.getElementById('spinner');
            const btnIcon = document.getElementById('btn-icon');
            const btnText = document.getElementById('btn-text');
            const form = document.getElementById('form-lock');

            function getCode() {
                return inputs.map(i => i.value).join('');
            }

            function updateState() {
                const code = getCode();
                const full = code.length === 6;

                hiddenFld.value = code;
                btnUnlock.disabled = !full;

                inputs.forEach((inp, idx) => {
                    inp.classList.toggle('filled', inp.value !== '');
                    inp.classList.remove('error');
                });

                // Auto-submit quando completo
                if (full) {
                    setTimeout(() => {
                        if (getCode().length === 6) doSubmit();
                    }, 200);
                }
            }

            inputs.forEach((inp, idx) => {
                inp.addEventListener('input', function(e) {
                    // Aceitar apenas dígitos
                    this.value = this.value.replace(/[^0-9]/g, '').slice(-1);

                    if (this.value && idx < 5) {
                        inputs[idx + 1].focus();
                    }

                    updateState();
                });

                inp.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace') {
                        if (!this.value && idx > 0) {
                            inputs[idx - 1].value = '';
                            inputs[idx - 1].focus();
                        } else {
                            this.value = '';
                        }
                        updateState();
                    }

                    if (e.key === 'ArrowLeft' && idx > 0) inputs[idx - 1].focus();
                    if (e.key === 'ArrowRight' && idx < 5) inputs[idx + 1].focus();

                    if (e.key === 'Enter' && getCode().length === 6) doSubmit();
                });

                // Colar código completo (ex: copiar do e-mail)
                inp.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pasted = (e.clipboardData || window.clipboardData)
                        .getData('text')
                        .replace(/[^0-9]/g, '')
                        .slice(0, 6);

                    pasted.split('').forEach((char, i) => {
                        if (inputs[i]) inputs[i].value = char;
                    });

                    const next = Math.min(pasted.length, 5);
                    inputs[next].focus();
                    updateState();
                });
            });

            // ── Submit ─────────────────────────────────
            function doSubmit() {
                const code = getCode();
                if (code.length !== 6) return;

                // Validação básica: só dígitos
                if (!/^\d{6}$/.test(code)) {
                    inputs.forEach(i => i.classList.add('error'));
                    inputs[0].focus();
                    return;
                }

                hiddenFld.value = code;

                spinner.style.display = 'block';
                btnIcon.style.display = 'none';
                btnText.textContent = 'A verificar...';
                btnUnlock.disabled = true;

                form.submit();
            }

            btnUnlock.addEventListener('click', doSubmit);
        });
    </script>
</body>

</html>