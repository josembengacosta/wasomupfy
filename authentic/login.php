<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Login
// Arquivo: authentic/login.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/include/functions.php';
startSecureSession();

if (isLoggedIn()) {
    redirect('/dashboard/painel');
}

$notices = [
    'account_created'     => 'Conta criada com sucesso! Faz login para continuar.',
    'password_reset'      => 'Senha redefinida. Podes iniciar sessao.',
    'logout'              => 'Sessao terminada com sucesso.',
    'session'             => 'Sessao expirada. Inicia sessao novamente.',
    'account_deactivated' => 'Conta desactivada. Tens 29 dias para recuperar — basta iniciares sessão.',
    'account_deleted'     => 'A tua conta foi eliminada permanentemente. Obrigado por teres usado o ' . (defined('APP_NAME') ? APP_NAME : 'Wasom Upfy') . '.',
];
$errors = [
    'csrf'             => 'Sessao expirada. Tenta novamente.',
    'empty'            => 'Preenche o e-mail e a senha.',
    'invalid'          => 'E-mail ou senha incorretos.',
    'suspended'        => 'Conta suspensa. Contacta o suporte.',
    'fraud'            => 'Conta bloqueada por atividade suspeita. Contacta o suporte.',
    'blocked'          => isset($_GET['msg']) ? urldecode($_GET['msg']) : 'Conta temporariamente bloqueada.',
    'inactive_expired' => 'O prazo de recuperação da tua conta expirou. Contacta o suporte se precisares de ajuda.',
];
$notice = isset($_GET['notice']) ? ($notices[$_GET['notice']] ?? '') : '';
$error = isset($_GET['error']) ? ($errors[$_GET['error']] ?? '') : '';
if (isset($_GET['remaining']) && (int)$_GET['remaining'] > 0) {
    $r = (int)$_GET['remaining'];
    $error .= " ({$r} tentativa(s) restante(s) antes do bloqueio)";
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <title>Wasom Upfy - Entrar</title>
    <base href="/wasomupfy/">
    <link rel="shortcut icon" href="assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="css/login.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/josembengacosta/wasomupfy@main/css/light.css" />
    <style>
        :root {
            --wasom-primary: #ff0089;
            --wasom-secondary: #e04385;
            --wasom-light: #fff0f7;
            --wasom-dark: #cc0070;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.95);
            margin: auto;
        }

        .btn-wasomupfy {
            background: linear-gradient(45deg, #ff0089, #ff0089);
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 3px 6px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .btn-wasomupfy:hover {
            background: linear-gradient(45deg, #e04385, #cc0070);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(172, 19, 19, 0.2);
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
            transition: opacity 0.5s ease;
        }

        .loaded .preloader {
            opacity: 0;
            pointer-events: none;
        }

        .text-wasom {
            color: var(--wasom-primary) !important;
        }

        .form-control:focus {
            border-color: var(--wasom-primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 0, 137, 0.15);
        }

        .spinner-border {
            border-bottom-color: #ff0089;
            border-top-color: #ff0089;
            border-left-color: #ff0089;
        }
    </style>
</head>

<body data-theme="default" data-layout="fluid">
    <div class="preloader">
        <div class="spinner-border text-link" role="status">
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
                            alt="Brand-WasomUpfy" />

                        <h1 class="h3 mt-3 text-wasom">Bem-vindo de volta</h1>
                        <p class="text-muted">Entre na sua conta para continuar</p>
                    </div>
                </div>
            </section>
            <section id="sigin">
                <div class="row justify-content-center">
                    <div class="col-xl-4 col-lg-6 col-md-8 col-11 m-auto">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <form class="needs-validation" method="post" action="login_process" novalidate>
                                    <input type="hidden" name="csrf_token"
                                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
                                    <input type="text" name="honeypot" style="display:none" value=""
                                        autocomplete="off" />

                                    <?php if ($notice): ?>
                                        <div style="padding: 1rem;"
                                            class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3"
                                            role="alert">
                                            <i class="bi bi-check-circle-fill me-2"></i>
                                            <div><?php echo htmlspecialchars($notice); ?></div>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($error): ?>
                                        <div style="padding: 1rem;"
                                            class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3"
                                            role="alert">
                                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                            <div><?php echo htmlspecialchars($error); ?></div>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label for="email_user" class="form-label">E-mail <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="email" class="form-control" id="email_user" required
                                                name="email_user" placeholder="Insira o endereço do e-mail"
                                                maxlength="60" />
                                            <span class="input-group-text"><i data-feather="mail"></i></span>
                                            <div class="invalid-feedback">
                                                Por favor entre com um e-mail válido.
                                            </div>
                                            <div class="valid-feedback">O seu e-mail é válido.</div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password_user" class="form-label">Senha <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password_user" required
                                                name="password_user" placeholder="Insira a sua senha" maxlength="60"
                                                autocomplete="off" />
                                            <button type="button" class="btn btn-outline-secondary"
                                                onclick="togglePasswordVisibility()" id="mostrar"
                                                aria-label="Mostrar senha">
                                                <i data-feather="eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary"
                                                onclick="togglePasswordVisibility()" style="display: none" id="mostrar1"
                                                aria-label="Esconder senha">
                                                <i data-feather="eye-off"></i>
                                            </button>
                                            <div class="invalid-feedback">
                                                Por favor insira a sua senha.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-4 d-flex align-items-center justify-content-between">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="remember_token"
                                                name="remember_token" />
                                            <label class="form-check-label" for="remember_token">Lembrar-me</label>
                                        </div>
                                        <a href="forgot-password" class="text-decoration-none text-dark">Esqueceu
                                            sua
                                            senha?</a>
                                    </div>
                                    <div class="d-grid">
                                        <button class="btn btn-wasomupfy" type="submit">
                                            Conecta-se <i data-feather="arrow-right"></i>
                                        </button>
                                    </div>
                                </form>
                                <div class="text-center mt-3">
                                    <a href="register" class="text-decoration-underline fw-bold"
                                        style="color: #ff0089; font-weight: bold">Precisa de
                                        uma conta? Crie uma
                                        agora.</a>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="#support" data-bs-toggle="modal" data-bs-target="#support"
                                        class="text-decoration-none me-2" style="color: #ff0089">Suporte</a>|
                                    <a href="page/politicies/terms" target="_blank" class="text-decoration-none me-2"
                                        style="color: #ff0089">Termos</a>|
                                    <a href="page/politicies/privacy" target="_blank" class="text-decoration-none me-2"
                                        style="color: #ff0089">Privacidade</a>|
                                    <a onclick="window.location.href='home'"
                                        class="text-muted text-decoration-none">Voltar home</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php include __DIR__ . '/_modal_support.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/validacao.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/josembengacosta/wasomupfy@main/js/app.js"></script>
    <script>
        window.addEventListener("load", function() {
            requestAnimationFrame(() => {
                document.querySelector("body").classList.add("loaded");
            });
        });

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById("password_user");
            const mostrar = document.getElementById("mostrar");
            const mostrar1 = document.getElementById("mostrar1");
            if (!passwordInput || !mostrar || !mostrar1) return;
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                mostrar.style.display = "none";
                mostrar1.style.display = "block";
            } else {
                passwordInput.type = "password";
                mostrar1.style.display = "none";
                mostrar.style.display = "block";
            }
        }
        // Real-time email validation
        document
            .getElementById("email_user")
            .addEventListener("input", function() {
                const email = this.value;
                const feedback = this.nextElementSibling.nextElementSibling;
                if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                    this.classList.add("is-invalid");
                    feedback.style.display = "block";
                } else {
                    this.classList.remove("is-invalid");
                    this.classList.add("is-valid");
                    feedback.style.display = "none";
                }
            });
    </script>
</body>

</html>