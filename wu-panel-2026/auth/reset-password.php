<?php
require_once __DIR__ . '/include/functions_admin.php';
startAdminSession();

if (isAdminLoggedIn() && !isLockscreenActive()) adminRedirect('/' . ADMIN_PATH . '');

$token = trim($_GET['token'] ?? '');
if (empty($token) || strlen($token) !== 64) adminRedirect('/' . ADMIN_PATH . '/forgot-password', ['msg' => 'error']);

$admin_id = validateAdminResetToken($token);
if (!$admin_id) adminRedirect('/' . ADMIN_PATH . '/forgot-password', ['msg' => 'expired']);

$_SESSION['admin_reset_token'] = $token;
$_SESSION['admin_reset_id']    = $admin_id;

$msg_type = $_GET['msg'] ?? null;
$feedback = match ($msg_type) {
    'mismatch' => ['type' => 'danger',  'icon' => 'bi-x-circle',         'text' => 'As senhas não coincidem. Tenta novamente.'],
    'weak'     => ['type' => 'warning', 'icon' => 'bi-shield-exclamation', 'text' => 'A senha é demasiado fraca. Segue os requisitos abaixo.'],
    'error'    => ['type' => 'danger',  'icon' => 'bi-exclamation-octagon', 'text' => 'Ocorreu um erro. Tenta novamente.'],
    default    => null,
};
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <title>Nova Senha — Wasom Upfy Admin</title>
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
            --pink-glow: rgba(255, 0, 137, .22);
            --dark: #0c0c0f;
            --off-white: #f8f7fc;
            --muted: #888;
            --green: #22c55e;
            --red: #ef4444;
            --amber: #f59e0b;
            --font-d: 'Syne', sans-serif;
            --font-b: 'DM Sans', sans-serif
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        html,
        body {
            height: 100%;
            font-family: var(--font-b);
            font-size: 15px;
            background: var(--off-white);
            color: #1a1a1a;
            -webkit-font-smoothing: antialiased
        }

        .auth-shell {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh
        }

        @media(max-width:900px) {
            .auth-shell {
                grid-template-columns: 1fr
            }

            .auth-left {
                display: none
            }

            .auth-right {
                padding: 40px 24px
            }
        }

        .auth-left {
            background: var(--dark);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px
        }

        .auth-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(255, 0, 137, .06) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 0, 137, .06) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none
        }

        .auth-left::after {
            content: '';
            position: absolute;
            top: 10%;
            right: -5%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 0, 137, .18) 0%, transparent 70%);
            pointer-events: none;
            animation: pg 5s ease-in-out infinite alternate
        }

        @keyframes pg {
            from {
                transform: scale(1);
                opacity: .6
            }

            to {
                transform: scale(1.2);
                opacity: 1
            }
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 2
        }

        .brand-logo img {
            width: 44px;
            height: 44px;
            border-radius: 10px
        }

        .brand-name {
            font-family: var(--font-d);
            font-size: 1.2rem;
            font-weight: 800;
            color: #fff
        }

        .brand-badge {
            font-size: .65rem;
            font-weight: 600;
            background: var(--pink);
            color: #fff;
            padding: 2px 8px;
            border-radius: 20px;
            letter-spacing: .5px;
            text-transform: uppercase
        }

        .left-illus {
            position: relative;
            z-index: 2;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center
        }

        .shield-wrap {
            position: relative;
            width: 160px;
            height: 160px
        }

        .shield-ring {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 1px dashed rgba(255, 0, 137, .35);
            animation: rr 12s linear infinite
        }

        @keyframes rr {
            to {
                transform: rotate(360deg)
            }
        }

        .shield-ring::before,
        .shield-ring::after {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            background: var(--pink);
            border-radius: 50%;
            top: -4px;
            left: calc(50% - 4px);
            box-shadow: 0 0 8px var(--pink)
        }

        .shield-ring::after {
            top: auto;
            bottom: -4px
        }

        .shield-icon {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: var(--pink);
            filter: drop-shadow(0 0 20px var(--pink-glow))
        }

        .left-steps {
            position: relative;
            z-index: 2
        }

        .steps-title {
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 16px
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px
        }

        .step-dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .75rem;
            font-weight: 700;
            flex-shrink: 0
        }

        .step-dot.done {
            background: rgba(34, 197, 94, .15);
            color: var(--green)
        }

        .step-dot.active {
            background: var(--pink);
            color: #fff
        }

        .step-dot.pending {
            background: #222;
            color: #555;
            border: 1px solid #333
        }

        .step-label {
            font-size: .85rem;
            color: #666
        }

        .step-label.active {
            color: #ccc;
            font-weight: 500
        }

        .step-label.done {
            color: #555;
            text-decoration: line-through
        }

        .auth-right {
            background: var(--off-white);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 40px
        }

        .auth-form-wrap {
            width: 100%;
            max-width: 400px
        }

        .form-header {
            margin-bottom: 32px
        }

        .access-label {
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
            margin-bottom: 16px
        }

        .form-header h1 {
            font-family: var(--font-d);
            font-size: 1.75rem;
            font-weight: 800;
            color: #111;
            margin-bottom: 8px
        }

        .form-header p {
            font-size: .88rem;
            color: var(--muted)
        }

        .form-label {
            font-size: .82rem;
            font-weight: 600;
            color: #444;
            margin-bottom: 6px;
            display: block
        }

        .iw {
            position: relative
        }

        .iw .form-control {
            padding-right: 48px
        }

        .form-control {
            border: 1.5px solid #e0e0e8;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: .9rem;
            font-family: var(--font-b);
            background: #fff;
            color: #111;
            width: 100%;
            transition: border-color .2s, box-shadow .2s
        }

        .form-control:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 3px var(--pink-glow);
            outline: none
        }

        .form-control.is-invalid {
            border-color: var(--red);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, .1)
        }

        .form-control.is-valid {
            border-color: var(--green);
            box-shadow: none
        }

        .btn-eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #bbb;
            cursor: pointer;
            font-size: .95rem;
            transition: color .2s;
            padding: 2px 4px;
            line-height: 1
        }

        .btn-eye:hover {
            color: var(--pink)
        }

        .str-wrap {
            margin-top: 8px
        }

        .str-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4px;
            margin-bottom: 6px
        }

        .str-seg {
            height: 4px;
            border-radius: 2px;
            background: #e0e0e8;
            transition: background .3s
        }

        .s1 .str-seg:nth-child(1) {
            background: var(--red)
        }

        .s2 .str-seg:nth-child(-n+2) {
            background: var(--amber)
        }

        .s3 .str-seg:nth-child(-n+3) {
            background: #84cc16
        }

        .s4 .str-seg {
            background: var(--green)
        }

        .str-lbl {
            font-size: .78rem;
            font-weight: 500;
            color: var(--muted);
            transition: color .3s
        }

        .s1 .str-lbl {
            color: var(--red)
        }

        .s2 .str-lbl {
            color: var(--amber)
        }

        .s3 .str-lbl {
            color: #84cc16
        }

        .s4 .str-lbl {
            color: var(--green)
        }

        .req-list {
            background: #fff;
            border: 1px solid #e8e8f0;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 20px
        }

        .req-title {
            font-size: .78rem;
            font-weight: 600;
            color: #999;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 10px
        }

        .req-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .82rem;
            color: #aaa;
            margin-bottom: 6px;
            transition: color .25s
        }

        .req-item:last-child {
            margin-bottom: 0
        }

        .req-item i {
            font-size: .75rem;
            width: 14px;
            transition: color .25s;
            color: #ddd
        }

        .req-item.ok {
            color: var(--green)
        }

        .req-item.ok i {
            color: var(--green)
        }

        .btn-submit {
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
            font-family: var(--font-b);
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s, transform .15s, box-shadow .2s;
            position: relative;
            overflow: hidden
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, .12) 0%, transparent 60%)
        }

        .btn-submit:hover:not(:disabled) {
            background: var(--pink-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px var(--pink-glow)
        }

        .btn-submit:disabled {
            opacity: .5;
            cursor: not-allowed
        }

        .spin {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, .4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: sp .6s linear infinite
        }

        @keyframes sp {
            to {
                transform: rotate(360deg)
            }
        }

        .alert-a {
            border-radius: 10px;
            border: none;
            padding: 12px 16px;
            font-size: .87rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 24px;
            animation: sd .3s ease
        }

        @keyframes sd {
            from {
                opacity: 0;
                transform: translateY(-8px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .alert-a.danger {
            background: #fff0f0;
            color: #b91c1c
        }

        .alert-a.warning {
            background: #fffbeb;
            color: #92400e
        }

        .fu {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity .45s ease, transform .45s ease
        }

        .fu.v {
            opacity: 1;
            transform: translateY(0)
        }

        .d1 {
            transition-delay: .05s
        }

        .d2 {
            transition-delay: .12s
        }

        .d3 {
            transition-delay: .19s
        }

        .d4 {
            transition-delay: .26s
        }

        .d5 {
            transition-delay: .33s
        }

        .d6 {
            transition-delay: .40s
        }

        .preloader {
            position: fixed;
            inset: 0;
            background: var(--off-white);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity .4s, visibility .4s
        }

        .preloader.h {
            opacity: 0;
            visibility: hidden
        }

        .pr {
            width: 36px;
            height: 36px;
            border: 3px solid #eee;
            border-top-color: var(--pink);
            border-radius: 50%;
            animation: sp .7s linear infinite
        }
    </style>
</head>

<body>
    <div class="preloader" id="pre">
        <div class="pr"></div>
    </div>
    <div class="auth-shell">

        <div class="auth-left">
            <div class="brand-logo">
                <img src="../assets/img/brand/wasomupfy.png" alt="Wasom Upfy" />
                <span class="brand-name">Wasom Upfy</span>
                <span class="brand-badge">Admin</span>
            </div>
            <div class="left-illus">
                <div class="shield-wrap">
                    <div class="shield-ring"></div>
                    <div class="shield-icon"><i class="bi bi-shield-lock"></i></div>
                </div>
            </div>
            <div class="left-steps">
                <p class="steps-title">Progresso</p>
                <div class="step-item">
                    <div class="step-dot done"><i class="bi bi-check"></i></div><span class="step-label done">Pedido
                        enviado</span>
                </div>
                <div class="step-item">
                    <div class="step-dot done"><i class="bi bi-check"></i></div><span class="step-label done">E-mail
                        recebido</span>
                </div>
                <div class="step-item">
                    <div class="step-dot active">3</div><span class="step-label active">Definir nova senha</span>
                </div>
                <div class="step-item">
                    <div class="step-dot pending">4</div><span class="step-label">Aceder ao painel</span>
                </div>
            </div>
        </div>

        <div class="auth-right">
            <div class="auth-form-wrap">

                <div class="form-header fu d1">
                    <div class="access-label"><i class="bi bi-shield-lock-fill"></i> Passo 3 de 4</div>
                    <h1>Define a tua<br>nova senha</h1>
                    <p>Escolhe uma senha forte para proteger a tua conta de administrador.</p>
                </div>

                <?php if ($feedback): ?>
                    <div class="alert-a <?php echo $feedback['type']; ?> fu d1">
                        <i class="bi <?php echo $feedback['icon']; ?>" style="flex-shrink:0;margin-top:1px"></i>
                        <span><?php echo htmlspecialchars($feedback['text']); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/reset-password-process" id="frm" novalidate
                    onsubmit="return false">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>" />

                    <div class="mb-2 fu d2">
                        <label class="form-label" for="np">Nova senha <span class="text-danger">*</span></label>
                        <div class="iw">
                            <input type="password" class="form-control" id="np" name="new_password"
                                placeholder="Mínimo 8 caracteres" maxlength="128" autocomplete="new-password" />
                            <button type="button" class="btn-eye" id="e1"><i class="bi bi-eye" id="ei1"></i></button>
                        </div>
                        <div class="str-wrap" id="sw" style="display:none">
                            <div class="str-bar" id="sb">
                                <div class="str-seg"></div>
                                <div class="str-seg"></div>
                                <div class="str-seg"></div>
                                <div class="str-seg"></div>
                            </div>
                            <span class="str-lbl" id="sl"></span>
                        </div>
                    </div>

                    <div class="req-list fu d3">
                        <p class="req-title">A senha deve ter:</p>
                        <div class="req-item" id="rl"><i class="bi bi-circle"></i> Pelo menos 8 caracteres</div>
                        <div class="req-item" id="ru"><i class="bi bi-circle"></i> Uma letra maiúscula (A–Z)</div>
                        <div class="req-item" id="rlo"><i class="bi bi-circle"></i> Uma letra minúscula (a–z)</div>
                        <div class="req-item" id="rn"><i class="bi bi-circle"></i> Um número (0–9)</div>
                        <div class="req-item" id="rs"><i class="bi bi-circle"></i> Um caractere especial (!@#$%...)
                        </div>
                    </div>

                    <div class="mb-4 fu d4">
                        <label class="form-label" for="cp">Confirmar senha <span class="text-danger">*</span></label>
                        <div class="iw">
                            <input type="password" class="form-control" id="cp" name="confirm_password"
                                placeholder="Repete a senha" maxlength="128" autocomplete="new-password" />
                            <button type="button" class="btn-eye" id="e2"><i class="bi bi-eye" id="ei2"></i></button>
                        </div>
                        <div id="mm" style="font-size:.8rem;margin-top:6px;min-height:18px"></div>
                    </div>

                    <div class="fu d5">
                        <button type="button" class="btn-submit" id="bs" disabled>
                            <span class="spin" id="sp"></span>
                            <i class="bi bi-check-circle" id="bi"></i>
                            <span id="bt">Guardar nova senha</span>
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4 fu d6">
                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/login"
                        style="font-size:.84rem;color:#aaa;text-decoration:none"
                        onmouseover="this.style.color='#FF0089'" onmouseout="this.style.color='#aaa'">
                        <i class="bi bi-arrow-left me-1"></i> Voltar ao login
                    </a>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.addEventListener('load', function() {
                document.getElementById('pre').classList.add('h');
                document.querySelectorAll('.fu').forEach(el => el.classList.add('v'));
            });

            const np = document.getElementById('np'),
                cp = document.getElementById('cp'),
                bs = document.getElementById('bs'),
                mm = document.getElementById('mm'),
                sw = document.getElementById('sw'),
                sb = document.getElementById('sb'),
                sl = document.getElementById('sl');

            // Toggles
            document.getElementById('e1').addEventListener('click', function() {
                np.type = np.type === 'password' ? 'text' : 'password';
                document.getElementById('ei1').className = 'bi bi-eye' + (np.type === 'text' ? '-slash' :
                    '');
                np.focus();
            });
            document.getElementById('e2').addEventListener('click', function() {
                cp.type = cp.type === 'password' ? 'text' : 'password';
                document.getElementById('ei2').className = 'bi bi-eye' + (cp.type === 'text' ? '-slash' :
                    '');
                cp.focus();
            });

            const reqs = {
                rl: v => v.length >= 8,
                ru: v => /[A-Z]/.test(v),
                rlo: v => /[a-z]/.test(v),
                rn: v => /[0-9]/.test(v),
                rs: v => /[^A-Za-z0-9]/.test(v)
            };
            const labels = ['', 'Fraca', 'Razoável', 'Boa', 'Forte'];

            function strength(v) {
                if (!v) return 0;
                let s = 0;
                if (v.length >= 8) s++;
                if (/[A-Z]/.test(v)) s++;
                if (/[a-z]/.test(v)) s++;
                if (/[0-9]/.test(v)) s++;
                if (/[^A-Za-z0-9]/.test(v)) s++;
                return s <= 1 ? 1 : s <= 3 ? 2 : s === 4 ? 3 : 4;
            }

            np.addEventListener('input', function() {
                const v = this.value;
                Object.entries(reqs).forEach(([id, fn]) => {
                    const el = document.getElementById(id),
                        ico = el.querySelector('i');
                    el.classList.toggle('ok', fn(v));
                    ico.className = fn(v) ? 'bi bi-check-circle-fill' : 'bi bi-circle';
                });
                if (v.length > 0) {
                    sw.style.display = 'block';
                    const s = strength(v);
                    sb.className = 'str-bar s' + s;
                    sl.textContent = labels[s];
                } else {
                    sw.style.display = 'none';
                }
                checkMatch();
                updateBtn();
            });

            function checkMatch() {
                const v = np.value,
                    c = cp.value;
                if (!c) {
                    mm.textContent = '';
                    cp.classList.remove('is-valid', 'is-invalid');
                    return;
                }
                if (v === c) {
                    mm.innerHTML =
                        '<span style="color:var(--green)"><i class="bi bi-check-circle"></i> As senhas coincidem</span>';
                    cp.classList.add('is-valid');
                    cp.classList.remove('is-invalid');
                } else {
                    mm.innerHTML =
                        '<span style="color:var(--red)"><i class="bi bi-x-circle"></i> As senhas não coincidem</span>';
                    cp.classList.add('is-invalid');
                    cp.classList.remove('is-valid');
                }
            }

            cp.addEventListener('input', function() {
                checkMatch();
                updateBtn();
            });

            function updateBtn() {
                const v = np.value,
                    c = cp.value;
                bs.disabled = !(Object.values(reqs).every(fn => fn(v)) && v === c && c.length > 0);
            }

            bs.addEventListener('click', function() {
                document.getElementById('sp').style.display = 'block';
                document.getElementById('bi').style.display = 'none';
                document.getElementById('bt').textContent = 'A guardar...';
                bs.disabled = true;
                document.getElementById('frm').submit();
            });

            [np, cp].forEach(i => i.addEventListener('keydown', e => {
                if (e.key === 'Enter' && !bs.disabled) bs.click();
            }));
        });
    </script>
</body>

</html>