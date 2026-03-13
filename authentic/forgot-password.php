<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Recuperar Senha
// Arquivo: authentic/forgot-password.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/include/functions.php';
startSecureSession();

// Já autenticado → redirecionar para o painel
if (isLoggedIn()) {
    redirect('/dashboard/painel');
}

// ─── Mensagens de notice e erro (via GET param) ───────
$notices = [
    'check_email' => 'Se o e-mail existir na nossa base de dados, receberás um código de recuperação em breve.',
    'code_sent'   => 'Código reenviado. Verifica a tua caixa de entrada.',
];
$errors = [
    'csrf'          => 'Sessão expirada. Tenta novamente.',
    'invalid_email' => 'Endereço de e-mail inválido. Verifica e tenta novamente.',
    'too_many'      => 'Demasiados pedidos. Aguarda alguns minutos antes de tentar novamente.',
    'generic'       => 'Ocorreu um erro. Tenta novamente.',
];

$notice = isset($_GET['notice']) ? ($notices[$_GET['notice']] ?? '') : '';
$error  = isset($_GET['error'])  ? ($errors[$_GET['error']]  ?? 'Erro desconhecido. Tenta novamente.') : '';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <title>Recuperar senha — Wasom Upfy</title>
    <link rel="shortcut icon" href="assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/josembengacosta/wasomupfy@main/css/light.css" />
    <link rel="stylesheet" href="css/login.css" />
    <style>
    :root {
        --wasom-primary: #ff0089;
        --wasom-secondary: #e04385;
        --wasom-dark: #cc0070;
    }

    .card {
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .1);
        background: rgba(255, 255, 255, .95);
        margin: auto;
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
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
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

    .spinner-border {
        border-bottom-color: #ff0089;
        border-top-color: #ff0089;
        border-left-color: #ff0089;
    }

    .form-control:focus {
        border-color: var(--wasom-primary);
        box-shadow: 0 0 0 0.25rem rgba(255, 0, 137, .15);
    }

    .text-wasom {
        color: var(--wasom-primary) !important;
    }
    </style>
</head>

<body data-theme="default">

    <div class="preloader">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Carregando...</span>
        </div>
    </div>

    <main class="main">
        <div class="container">

            <section class="py-3 py-lg-4">
                <div class="row justify-content-center">
                    <div class="col-xl-4 col-lg-6 col-md-8 col-12 text-center m-auto mt-5">
                        <img src="assets/img/brand/wasomupfy_authentic.png"
                            class="img-fluid animate__animated animate__fadeIn" width="90" height="90"
                            alt="Wasom Upfy" />
                        <h1 class="h3 mt-3 text-wasom">Recuperar senha</h1>
                        <p class="text-muted">Insere o e-mail associado à tua conta para receberes um código de
                            recuperação.</p>
                    </div>
                </div>
            </section>

            <section id="forgot-password">
                <div class="row justify-content-center">
                    <div class="col-xl-4 col-lg-6 col-md-8 col-11 m-auto">
                        <div class="card shadow-sm">
                            <div class="card-body p-4">

                                <!-- ── Alerts de notice (success) ── -->
                                <?php if ($notice): ?>
                                <div style="padding: 1rem;"
                                    class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3"
                                    role="alert">
                                    <i class="bi bi-check-circle-fill me-2 flex-shrink-0"></i>
                                    <div><?php echo htmlspecialchars($notice); ?></div>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Fechar"></button>
                                </div>
                                <?php endif; ?>

                                <!-- ── Alerts de erro ── -->
                                <?php if ($error): ?>
                                <div style="padding: 1rem;"
                                    class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3"
                                    role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0"></i>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Fechar"></button>
                                </div>
                                <?php endif; ?>

                                <form method="POST" action="forgot-password-process" class="needs-validation"
                                    id="forgot-form" novalidate>
                                    <input type="hidden" name="csrf_token"
                                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
                                    <input type="text" name="honeypot" style="display:none" value=""
                                        autocomplete="off" />

                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            E-mail <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="email" class="form-control" id="email" name="email" required
                                                autocomplete="email" placeholder="seuemail@exemplo.com" maxlength="60"
                                                value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>" />
                                            <span class="input-group-text"><i data-feather="mail"></i></span>
                                            <div class="invalid-feedback">Insere um e-mail válido.</div>
                                            <div class="valid-feedback">E-mail válido.</div>
                                        </div>
                                    </div>

                                    <div class="d-grid mb-3">
                                        <button class="btn btn-wasomupfy" type="submit" id="btn-submit">
                                            <span id="btn-text">Obter código de confirmação <i
                                                    data-feather="send"></i></span>
                                            <span id="btn-loading" class="d-none">
                                                <span class="spinner-border spinner-border-sm me-2"></span>A enviar...
                                            </span>
                                        </button>
                                    </div>

                                    <div class="text-center">
                                        <a href="login" class="text-decoration-none text-wasom">
                                            <i data-feather="arrow-left" style="width:14px;height:14px"></i>
                                            Voltar e conectar-se
                                        </a>
                                    </div>
                                </form>

                            </div><!-- /card-body -->
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

        </div><!-- /container -->
    </main>

    <?php include __DIR__ . '/_modal_support.php'; ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/josembengacosta/wasomupfy@main/js/app.js"></script>
    <script src="js/validacao.js"></script>
    <script>
    // ── Preloader ────────────────────────────────────────
    window.addEventListener('load', () => {
        requestAnimationFrame(() => document.body.classList.add('loaded'));
    });

    // ── Validação em tempo real ───────────────────────────
    document.getElementById('email').addEventListener('input', function() {
        const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value);
        this.classList.toggle('is-valid', valid);
        this.classList.toggle('is-invalid', !valid && this.value.length > 0);
    });

    // ── Loading state no submit ───────────────────────────
    document.getElementById('forgot-form').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value.trim();
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            e.preventDefault();
            document.getElementById('email').classList.add('is-invalid');
            return;
        }
        document.getElementById('btn-text').classList.add('d-none');
        document.getElementById('btn-loading').classList.remove('d-none');
        document.getElementById('btn-submit').disabled = true;
    });
    </script>
</body>

</html>