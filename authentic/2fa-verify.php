<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Verificação 2FA
// Arquivo: authentic/2fa-verify.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/include/functions.php';
startSecureSession();

if (isLoggedIn()) {
    redirect('/dashboard/painel');
}

// Verificar se há sessão 2FA pendente válida
$pending = $_SESSION['pending_2fa'] ?? null;
if (!$pending || time() > $pending['expires']) {
    unset($_SESSION['pending_2fa']);
    redirect('/login', ['error' => 'session']);
}

$errors  = [
    'invalid_code'    => 'Código incorrecto. Certifica-te que o teu relógio está sincronizado.',
    'expired_session' => 'A sessão de verificação expirou. Inicia sessão novamente.',
    'invalid_recovery' => 'Chave de recuperação inválida ou já utilizada.',
    'csrf'            => 'Sessão expirada. Tenta novamente.',
];
$error   = isset($_GET['error']) ? ($errors[$_GET['error']] ?? '') : '';
$mode    = in_array($_GET['mode'] ?? '', ['totp', 'recovery']) ? $_GET['mode'] : 'totp';
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <title>Verificação em dois passos — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/josembengacosta/wasomupfy@main/css/light.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/login.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/loanding.css" />
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

        .code-inputs input.filled {
            border-color: #ff0089;
            background: rgba(255, 0, 137, .04);
        }

        .code-inputs input.is-invalid {
            border-color: #dc3545;
        }

        @media (max-width:400px) {
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

        .shield-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255, 0, 137, .1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 1.6rem;
        }

        .tab-mode {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .tab-mode a {
            font-size: .82rem;
            padding: 5px 14px;
            border-radius: 20px;
            border: 1px solid #dee2e6;
            text-decoration: none;
            color: #666;
            transition: all .2s;
        }

        .tab-mode a.active {
            background: rgba(255, 0, 137, .08);
            border-color: rgba(255, 0, 137, .3);
            color: #ff0089;
            font-weight: 600;
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
                            height="90" alt="<?php echo APP_NAME; ?>" />
                        <h1 class="h3 mt-3 text-wasom">Verificação em dois passos</h1>
                        <p class="text-muted small">
                            <?php if ($mode === 'recovery'): ?>
                                Insere a tua chave de recuperação para aceder à conta.
                            <?php else: ?>
                                Insere o código de 6 dígitos do teu autenticador.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </section>

            <section>
                <div class="row justify-content-center">
                    <div class="col-xl-4 col-lg-6 col-md-8 col-12">
                        <div class="card">
                            <div class="card-body p-4">
                                <div class="shield-icon"><i class="bi bi-shield-lock-fill text-wasom"></i></div>

                                <!-- Tabs TOTP / Recovery -->
                                <div class="tab-mode">
                                    <a href="2fa-verify" class="<?php echo $mode === 'totp' ? 'active' : ''; ?>">
                                        <i class="bi bi-phone me-1"></i>Autenticador
                                    </a>
                                    <a href="2fa-verify?mode=recovery"
                                        class="<?php echo $mode === 'recovery' ? 'active' : ''; ?>">
                                        <i class="bi bi-key me-1"></i>Chave de recuperação
                                    </a>
                                </div>

                                <?php if ($error): ?>
                                    <div style="padding: 1rem;"
                                        class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3"
                                        role="alert">
                                        <i class="bi bi-exclamation-circle-fill me-2 flex-shrink-0"></i>
                                        <div><?php echo htmlspecialchars($error); ?></div>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if ($mode === 'totp'): ?>
                                    <!-- ── Modo TOTP ── -->
                                    <form method="POST" action="2fa-process" id="totp-form">
                                        <input type="hidden" name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
                                        <input type="hidden" name="mode" value="totp" />
                                        <input type="hidden" name="code" id="code" />

                                        <div class="mb-4">
                                            <label class="form-label fw-semibold text-center d-block mb-3">
                                                Código do autenticador <span class="text-danger">*</span>
                                            </label>
                                            <div class="code-inputs" id="code-inputs-wrap">
                                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                                    <input type="text" class="code-digit" id="code-<?php echo $i; ?>"
                                                        inputmode="numeric" maxlength="1" pattern="[0-9]"
                                                        autocomplete="<?php echo $i === 1 ? 'one-time-code' : 'off'; ?>"
                                                        aria-label="Dígito <?php echo $i; ?>" />
                                                <?php endfor; ?>
                                            </div>
                                            <div id="code-error" class="text-danger text-center small mt-2 d-none">
                                                <i class="bi bi-exclamation-circle me-1"></i>Insere um código de 6 dígitos
                                                válido.
                                            </div>
                                        </div>

                                        <div class="d-grid mb-3">
                                            <button class="btn btn-wasomupfy" type="submit" id="btn-submit">
                                                <span id="btn-text"><i class="bi bi-check-circle me-1"></i>Verificar
                                                    código</span>
                                                <span id="btn-loading" class="d-none">
                                                    <span class="spinner-border spinner-border-sm me-2"></span>A
                                                    verificar...
                                                </span>
                                            </button>
                                        </div>
                                    </form>

                                <?php else: ?>
                                    <!-- ── Modo Recovery Key ── -->
                                    <form method="POST" action="2fa-process" id="recovery-form">
                                        <input type="hidden" name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
                                        <input type="hidden" name="mode" value="recovery" />

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small">
                                                Chave de recuperação <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="recovery_key" class="form-control font-monospace"
                                                placeholder="XXXX-XXXX-XXXX-XXXX-XXXX" autocomplete="off" spellcheck="false"
                                                maxlength="119" style="letter-spacing:.1em;text-transform:uppercase"
                                                oninput="this.value=this.value.toUpperCase()" required />
                                            <div class="form-text">
                                                <i class="bi bi-info-circle me-1"></i>
                                                A chave de recuperação foi gerada nas definições de segurança do teu perfil.
                                                <strong>Após uso, será invalidada.</strong>
                                            </div>
                                        </div>

                                        <div class="d-grid mb-3">
                                            <button class="btn btn-wasomupfy" type="submit">
                                                <i class="bi bi-key me-1"></i>Usar chave de recuperação
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>

                                <div class="text-center">
                                    <a href="login" class="text-decoration-none text-wasom small">
                                        <i class="bi bi-arrow-left me-1"></i>Voltar ao login
                                    </a>
                                </div>

                            </div>
                            <div class="card-footer text-center py-2 small">
                                <a href="#support" data-bs-toggle="modal" data-bs-target="#support"
                                    class="text-wasom me-1">Suporte</a> |
                                <a href="home" class="text-muted ms-1">Voltar home</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php include __DIR__ . '/_modal_support.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('load', () => requestAnimationFrame(() => document.body.classList.add('loaded')));

        // ── Inputs do código TOTP (igual ao confirm-email-code) ──
        const digits = document.querySelectorAll('.code-digit');
        const codeField = document.getElementById('code');

        digits.forEach((input, idx) => {
            input.addEventListener('input', e => {
                const val = e.target.value.replace(/\D/g, '');
                e.target.value = val;
                if (val) {
                    e.target.classList.add('filled');
                    if (idx < digits.length - 1) digits[idx + 1].focus();
                } else {
                    e.target.classList.remove('filled');
                }
                syncCode();
            });
            input.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !input.value && idx > 0) {
                    digits[idx - 1].focus();
                    digits[idx - 1].value = '';
                    digits[idx - 1].classList.remove('filled');
                    syncCode();
                }
            });
            input.addEventListener('paste', e => {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '')
                    .slice(0, 6);
                paste.split('').forEach((ch, i) => {
                    if (digits[i]) {
                        digits[i].value = ch;
                        digits[i].classList.add('filled');
                    }
                });
                syncCode();
                if (paste.length === 6) document.getElementById('btn-submit')?.focus();
            });
        });

        function syncCode() {
            if (!codeField) return;
            codeField.value = Array.from(digits).map(d => d.value).join('');
        }

        const form = document.getElementById('totp-form');
        if (form) {
            form.addEventListener('submit', e => {
                syncCode();
                const val = codeField?.value ?? '';
                const errEl = document.getElementById('code-error');
                if (!/^\d{6}$/.test(val)) {
                    e.preventDefault();
                    digits.forEach(d => d.classList.add('is-invalid'));
                    errEl?.classList.remove('d-none');
                    return;
                }
                errEl?.classList.add('d-none');
                document.getElementById('btn-text')?.classList.add('d-none');
                document.getElementById('btn-loading')?.classList.remove('d-none');
                document.getElementById('btn-submit').disabled = true;
            });
        }
    </script>
</body>

</html>