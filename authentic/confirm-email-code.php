<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Verificar Código
// Arquivo: authentic/confirm-email-code.php
// Usado para: verificação de e-mail E reset de senha
// ══════════════════════════════════════════════
require_once __DIR__ . '/include/functions.php';
startSecureSession();

if (isLoggedIn()) {
    redirect('/dashboard/painel');
}

// ─── Parâmetros da URL ────────────────────────
$email = htmlspecialchars(urldecode($_GET['email'] ?? ''));
$mode  = in_array($_GET['mode'] ?? '', ['verify', 'reset']) ? $_GET['mode'] : 'verify';

// ─── Mensagens de erro e notice ──────────────
$notices = [
    'check_email' => 'Código enviado! 
  Verifica a tua caixa de entrada (e a pasta de spam).',
    'code_sent'   => 'Código reenviado com sucesso.',
    'code_ok'     => 'Código verificado! Define agora a tua nova senha.',
];
$errors = [
    'csrf'         => 'Sessão expirada. Tenta novamente.',
    'invalid_code' => 'O código introduzido não é válido. Deve ter 6 dígitos numéricos.',
    'code_expired' => 'O código expirou ou já foi utilizado. Solicita um novo.',
    'generic'      => 'Ocorreu um erro. Tenta novamente.',
];

$notice = isset($_GET['notice']) ? ($notices[$_GET['notice']] ?? '') : '';
$error  = isset($_GET['error'])  ? ($errors[$_GET['error']]  ?? 'Erro desconhecido.') : '';

// ─── Título e textos por modo ─────────────────
$is_reset = ($mode === 'reset');
$title_main = $is_reset ? 'Recuperar senha'     : 'Verificar e-mail';
$title_sub  = $is_reset ? 'Código de recuperação' : 'Verificar código';
$desc       = $is_reset
    ? 'Insere o código de 6 dígitos enviado para o teu e-mail para redefinires a senha.'
    : 'Insere o código de 6 dígitos enviado para o teu e-mail para activares a conta.';
$back_link  = $is_reset ? 'forgot-password' : 'register';
$back_text  = $is_reset ? 'Alterar e-mail'  : 'Voltar ao registo';
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <title><?php echo htmlspecialchars($title_main); ?> — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/josembengacosta/wasomupfy@main/css/light.css" />
    <link rel="stylesheet" href="css/login.css" />
    <link rel="stylesheet" href="css/loanding.css" />
    <style>
        :root {
            --wasom-primary: #ff0089;
        }

        .card {
            border-radius: 12px;
            border: 1px solid rgba(255, 0, 137, .15);
            background: #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .1);
        }

        .btn-wasomupfy {
            background: linear-gradient(45deg, #ff0089, #ff0089);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 7px;
            font-size: 1.05rem;
            transition: all .3s ease;
        }

        .btn-wasomupfy:hover {
            background: linear-gradient(45deg, #e04385, #cc0070);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(172, 19, 19, .2);
            color: white;
        }

        .preloader {
            position: fixed;
            inset: 0;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity .5s ease;
        }

        .loaded .preloader {
            opacity: 0;
            pointer-events: none;
        }

        /* ── Inputs do código ── */
        .code-inputs {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .code-inputs input {
            width: 44px;
            height: 52px;
            text-align: center;
            font-size: 1.4rem;
            font-weight: 600;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            transition: all .2s;
        }

        .code-inputs input:focus {
            border-color: #ff0089;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 0, 137, .15);
        }

        .code-inputs input.is-valid {
            border-color: #198754;
        }

        .code-inputs input.is-invalid {
            border-color: #dc3545;
        }

        .code-inputs input.filled {
            border-color: #ff0089;
            background: rgba(255, 0, 137, .04);
        }

        @media (max-width: 400px) {
            .code-inputs input {
                width: 38px;
                height: 44px;
                font-size: 1.1rem;
            }

            .code-inputs {
                gap: 5px;
            }
        }

        .spinner-border {
            border-bottom-color: #ff0089;
            border-top-color: #ff0089;
            border-left-color: #ff0089;
        }

        .text-wasom {
            color: var(--wasom-primary) !important;
        }

        /* ── Resend countdown ── */
        #resend-btn:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .email-chip {
            display: inline-block;
            background: rgba(255, 0, 137, .08);
            border: 1px solid rgba(255, 0, 137, .2);
            border-radius: 20px;
            padding: 2px 12px;
            font-size: .85rem;
            color: #ff0089;
            font-weight: 500;
        }
    </style>
</head>

<body data-theme="default">

    <div class="preloader">
        <div class="spinner-border" role="status"><span class="visually-hidden">Carregando...</span></div>
    </div>

    <main class="main">
        <div class="container">

            <section class="py-3 py-lg-4">
                <div class="row justify-content-center">
                    <div class="col-xl-4 col-lg-6 col-md-8 col-12 text-center m-auto mt-4">
                        <img src="assets/img/brand/wasomupfy_authentic.png" class="img-fluid fade-in" width="90"
                            height="90" alt="Wasom Upfy" />
                        <h1 class="h3 mt-3 text-wasom"><?php echo htmlspecialchars($title_sub); ?></h1>
                        <p class="text-muted small"><?php echo htmlspecialchars($desc); ?></p>
                        <?php if ($email): ?>
                            <div class="mt-1">
                                <span class="email-chip"><i class="bi bi-envelope me-1"></i><?php echo $email; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section id="verify-code">
                <div class="row justify-content-center">
                    <div class="col-xl-4 col-lg-6 col-md-8 col-11 m-auto">
                        <div class="card shadow-sm">
                            <div class="card-body p-4">

                                <!-- ── Alert notice ── -->
                                <?php if ($notice): ?>
                                    <div style="padding: 1rem;"
                                        class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3"
                                        role="alert">
                                        <i class="bi bi-check-circle-fill me-2 flex-shrink-0"></i>
                                        <div><?php echo htmlspecialchars($notice); ?></div>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <!-- ── Alert erro ── -->
                                <?php if ($error): ?>
                                    <div style="padding: 1rem;"
                                        class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3"
                                        role="alert">
                                        <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0"></i>
                                        <div><?php echo htmlspecialchars($error); ?></div>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="verify-code-process?mode=<?php echo $mode; ?>"
                                    class="needs-validation" id="verify-form" novalidate>
                                    <input type="hidden" name="csrf_token"
                                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
                                    <input type="hidden" name="email"
                                        value="<?php echo htmlspecialchars(urldecode($_GET['email'] ?? '')); ?>" />
                                    <input type="hidden" name="mode" value="<?php echo $mode; ?>" />
                                    <input type="text" name="honeypot" style="display:none" value=""
                                        autocomplete="off" />
                                    <input type="hidden" name="code" id="code" />

                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">
                                            Código de confirmação <span class="text-danger">*</span>
                                        </label>
                                        <div class="code-inputs" id="code-inputs-wrap">
                                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                                <input type="text" class="code-digit" id="code-<?php echo $i; ?>"
                                                    inputmode="numeric" maxlength="1" pattern="[0-9]"
                                                    autocomplete="<?php echo $i === 1 ? 'one-time-code' : 'off'; ?>"
                                                    aria-label="Dígito <?php echo $i; ?> do código" />
                                            <?php endfor; ?>
                                        </div>
                                        <div id="code-error" class="text-danger text-center small mt-2 d-none">
                                            <i class="bi bi-exclamation-circle me-1"></i>Insere um código de 6 dígitos
                                            válido.
                                        </div>
                                    </div>

                                    <div class="d-grid mb-3">
                                        <button class="btn btn-wasomupfy" type="submit" id="btn-submit">
                                            <span id="btn-text">
                                                <i class="bi bi-check-circle me-1"></i>Confirmar código
                                            </span>
                                            <span id="btn-loading" class="d-none">
                                                <span class="spinner-border spinner-border-sm me-2"></span>A
                                                verificar...
                                            </span>
                                        </button>
                                    </div>

                                    <!-- Reenviar código -->
                                    <div class="text-center mb-2">
                                        <span class="text-muted small">Não recebeste o código? </span>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-wasom" id="resend-btn"
                                            style="text-decoration:none;font-size:.85rem" disabled
                                            onclick="resendCode()">
                                            Reenviar
                                        </button>
                                        <span id="resend-countdown" class="text-muted small">
                                            (aguarda <span id="countdown-secs">60</span>s)
                                        </span>
                                    </div>

                                    <div class="text-center">
                                        <a href="<?php echo $back_link; ?>"
                                            class="text-decoration-none text-wasom small">
                                            <i class="bi bi-arrow-left me-1"></i><?php echo $back_text; ?>
                                        </a>
                                    </div>
                                </form>

                            </div>
                            <div class="card-footer text-center py-2 small">
                                <a href="#support" data-bs-toggle="modal" data-bs-target="#support"
                                    class="text-wasom me-1">Suporte</a> |
                                <a href="page/politicies/terms" target="_blank" class="text-wasom mx-1">Termos</a> |
                                <a href="page/politicies/privacy" target="_blank"
                                    class="text-wasom mx-1">Privacidade</a> |
                                <a onclick="window.location.href='home'" class="text-muted text-decoration-none">Voltar
                                    home</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <?php include __DIR__ . '/_modal_support.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/josembengacosta/wasomupfy@main/js/app.js"></script>
    <script>
        // ── Preloader ─────────────────────────────────────────
        window.addEventListener('load', () => requestAnimationFrame(() => document.body.classList.add('loaded')));

        // ── Referências ──────────────────────────────────────
        const inputs = document.querySelectorAll('.code-digit');
        const hiddenCode = document.getElementById('code');
        const codeError = document.getElementById('code-error');
        const EMAIL = '<?php echo addslashes(urldecode($_GET['email'] ?? '')); ?>';
        const MODE = '<?php echo $mode; ?>';

        // ── Preenchimento automático (OTP autocomplete) ───────
        // iOS/Android preenchem automaticamente o campo com autocomplete="one-time-code"
        // mas o input é só 1 dígito — ao receber o código completo distribui pelos 6
        inputs[0].addEventListener('input', function() {
            const val = this.value.replace(/\D/g, '');
            if (val.length === 6) {
                // Utilizador colou ou o sistema preencheu automaticamente o código completo
                val.split('').forEach((d, i) => {
                    inputs[i].value = d;
                    inputs[i].classList.add('filled');
                });
                updateCode();
                inputs[5].focus();
            }
        });

        // ── Navegação entre inputs ────────────────────────────
        inputs.forEach((inp, idx) => {
            inp.addEventListener('input', function() {
                const v = this.value.replace(/\D/g, '');
                this.value = v ? v[0] : '';
                if (this.value) {
                    this.classList.add('filled');
                    if (idx < 5) inputs[idx + 1].focus();
                } else {
                    this.classList.remove('filled');
                }
                updateCode();
            });

            inp.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && idx > 0) {
                    inputs[idx - 1].focus();
                    inputs[idx - 1].value = '';
                    inputs[idx - 1].classList.remove('filled');
                    updateCode();
                }
                // Permitir Ctrl+V no primeiro campo
                if ((e.ctrlKey || e.metaKey) && e.key === 'v' && idx !== 0) {
                    inputs[0].focus();
                }
            });

            // Seleccionar todo o conteúdo ao focar (facilita correcção)
            inp.addEventListener('focus', function() {
                this.select();
            });
        });

        // ── Colar código (suporte a Ctrl+V em qualquer campo) ─
        document.addEventListener('paste', function(e) {
            const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            if (paste.length === 6) {
                paste.split('').forEach((d, i) => {
                    inputs[i].value = d;
                    inputs[i].classList.add('filled');
                });
                updateCode();
                inputs[5].focus();
                e.preventDefault();
            }
        });

        // ── Actualizar campo oculto + feedback visual ─────────
        function updateCode() {
            const code = Array.from(inputs).map(i => i.value).join('');
            hiddenCode.value = code;
            const valid = /^\d{6}$/.test(code);
            codeError.classList.toggle('d-none', valid || code.length === 0);
            inputs.forEach(i => {
                i.classList.toggle('is-valid', valid);
                i.classList.toggle('is-invalid', !valid && i.value === '' && code.length > 0);
            });
            // Auto-submit quando os 6 dígitos estão preenchidos
            if (valid) {
                setTimeout(() => {
                    document.getElementById('btn-text').classList.add('d-none');
                    document.getElementById('btn-loading').classList.remove('d-none');
                    document.getElementById('btn-submit').disabled = true;
                    document.getElementById('verify-form').submit();
                }, 300); // Pequeno delay para feedback visual
            }
        }

        // ── Submit manual ─────────────────────────────────────
        document.getElementById('verify-form').addEventListener('submit', function(e) {
            const code = hiddenCode.value;
            if (!/^\d{6}$/.test(code)) {
                e.preventDefault();
                codeError.classList.remove('d-none');
                inputs.forEach(i => i.classList.add('is-invalid'));
                return;
            }
            document.getElementById('btn-text').classList.add('d-none');
            document.getElementById('btn-loading').classList.remove('d-none');
            document.getElementById('btn-submit').disabled = true;
        });

        // ── Reenviar código (com countdown de 60s) ───────────
        let countdownTimer = null;
        let secondsLeft = 60;

        function startCountdown() {
            const btn = document.getElementById('resend-btn');
            const cntSpan = document.getElementById('countdown-secs');
            const cntWrap = document.getElementById('resend-countdown');
            btn.disabled = true;
            secondsLeft = 60;
            cntWrap.classList.remove('d-none');
            cntSpan.textContent = secondsLeft;

            countdownTimer = setInterval(() => {
                secondsLeft--;
                cntSpan.textContent = secondsLeft;
                if (secondsLeft <= 0) {
                    clearInterval(countdownTimer);
                    btn.disabled = false;
                    cntWrap.classList.add('d-none');
                }
            }, 1000);
        }

        function resendCode() {
            if (!EMAIL) return;
            fetch('forgot-password-process', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'csrf_token=<?php echo urlencode($_SESSION["csrf_token"]); ?>&email=' + encodeURIComponent(
                    EMAIL) + '&resend=1'
            }).then(() => {
                startCountdown();
                // Toastr se disponível, senão alert Bootstrap
                if (typeof toastr !== 'undefined') {
                    toastr.success('Código reenviado! Verifica a tua caixa de entrada.');
                }
            }).catch(() => {});
        }

        // Iniciar o countdown assim que a página carrega
        startCountdown();
    </script>
</body>

</html>