<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Login
// Arquivo: authentic/login.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/include/functions.php';
startSecureSession();

if (!isset($_GET['session']) || $_GET['session'] !== 'expired') {
    unset($_SESSION['redirect_after_login']);
}

if (isLoggedIn()) {
    redirect(APP_URL_PANEL . '/painel');
}


$notices = [
    'account_created'     => 'Conta criada com sucesso! Faz login para continuar.',
    'password_reset'      => 'Senha redefinida. Podes iniciar sessão.',
    'logout'              => 'Sessão terminada com sucesso.',
    'session'             => 'Sessão expirada. Inicia sessão novamente.',
    'account_deactivated' => 'Conta desactivada. Tens 29 dias para recuperar — basta iniciares sessão.',
    'account_deleted'     => 'A tua conta foi eliminada permanentemente. Obrigado por teres usado o ' . APP_NAME . '.',
];

$errors = [
    'csrf'                => 'Sessão expirada. Tenta novamente.',
    'empty'               => 'Preenche o e-mail e a senha.',
    'invalid'             => 'E-mail ou senha incorretos.',
    'disabled'            => 'Os logins estão temporariamente desativados pela equipa de Wasom Upfy.',
    'global_disabled'     => getPlatformMaintenanceMsg() ?: 'Acesso ao sistema desativado pela equipa de Wasom Upfy.',
    'suspended'           => 'Conta suspensa. Contacta o suporte.',
    'fraud'               => 'Conta bloqueada por atividade suspeita. Contacta o suporte.',
    'blocked'             => isset($_GET['msg']) ? urldecode($_GET['msg']) : 'Conta temporariamente bloqueada.',
    'timeout'             => 'A tua sessão expirou. Inicia sessão novamente.',
    'session_timeout'     => 'O teu tempo de sessão expirou. Inicia sessão novamente.',
    'inactive_expired'    => 'O prazo de recuperação da tua conta expirou. Contacta o suporte se precisares de ajuda.',
    'session_expired'     => 'Sessão expirada. Tenta novamente.',
    'reactivation_expired' => 'O tempo para confirmar a reactivação expirou. Inicia sessão novamente.',
];

// 1. Prioridade: erro vindo da sessão (definido pelo login_process)
$error = '';
if (isset($_SESSION['login_error'])) {
    $error_key = $_SESSION['login_error'];
    $error = $errors[$error_key] ?? 'Ocorreu um erro.';
    if (isset($_SESSION['remaining_attempts']) && $_SESSION['remaining_attempts'] > 0) {
        $error .= " ({$_SESSION['remaining_attempts']} tentativa(s) restante(s))";
    }
    unset($_SESSION['login_error'], $_SESSION['remaining_attempts']);
}

// 2. Se não há erro de sessão, verificar URL (fallback para links antigos)
if (empty($error) && isset($_GET['error'])) {
    $error_key = $_GET['error'];
    $error = $errors[$error_key] ?? '';
    if (isset($_GET['remaining']) && (int)$_GET['remaining'] > 0) {
        $error .= " ({$_GET['remaining']} tentativa(s) restante(s) antes do bloqueio)";
    }
}

// 3. Notices (sucesso) vindos da URL
$notice = isset($_GET['notice']) ? ($notices[$_GET['notice']] ?? '') : '';
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
    <link rel="shortcut icon" href="assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="<?php echo $siteUrl; ?>/css/login.css" />
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

    /* ── Modal de Reactivação Profissional ── */
    .modal-content {
        border: none;
        border-radius: 20px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
    }

    .modal-header {
        padding: 1.5rem 1.5rem 0.5rem;
        border-bottom: none;
    }

    .modal-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1a1a2e;
        letter-spacing: -0.01em;
    }

    .modal-body {
        padding: 1.5rem;
        color: #334155;
        font-size: 0.95rem;
        line-height: 1.6;
    }

    .modal-body strong {
        color: #0f172a;
        font-weight: 600;
    }

    .reactivate-highlight {
        background: linear-gradient(135deg, rgba(255, 0, 137, 0.03) 0%, rgba(255, 0, 137, 0.08) 100%);
        border-left: 4px solid #FF0089;
        padding: 1rem 1.25rem;
        border-radius: 12px;
        margin: 1.2rem 0;
        font-size: 0.9rem;
    }

    .reactivate-dates {
        display: flex;
        justify-content: space-between;
        background: #f8fafc;
        padding: 0.75rem 1rem;
        border-radius: 12px;
        margin: 1rem 0;
    }

    .reactivate-date-item {
        text-align: center;
        flex: 1;
    }

    .reactivate-date-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 4px;
    }

    .reactivate-date-value {
        font-weight: 700;
        color: #0f172a;
        font-size: 1rem;
    }

    .modal-footer {
        padding: 1rem 1.5rem 1.5rem;
        border-top: none;
        gap: 12px;
    }

    .btn-outline-secondary {
        border: 1.5px solid #e2e8f0;
        background: white;
        color: #475569;
        font-weight: 500;
        padding: 0.6rem 1.5rem;
        border-radius: 12px;
        transition: all 0.2s ease;
    }

    .btn-outline-secondary:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }

    .btn-reactivate {
        background: linear-gradient(135deg, #FF0089 0%, #e6007a 100%);
        color: white;
        border: none;
        font-weight: 600;
        padding: 0.6rem 1.8rem;
        border-radius: 12px;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(255, 0, 137, 0.25);
    }

    .btn-reactivate:hover {
        background: linear-gradient(135deg, #e6007a 0%, #cc0070 100%);
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(255, 0, 137, 0.3);
        color: white;
    }

    .modal.fade .modal-dialog {
        transition: transform 0.3s ease-out;
        transform: scale(0.95);
    }

    .modal.show .modal-dialog {
        transform: scale(1);
    }

    .reactivate-date-item small {
        display: block;
        font-size: 0.7rem;
        color: #64748b;
        margin-top: 2px;
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

    <?php if (isset($_GET['confirm_reactivate']) && isset($_SESSION['pending_reactivation'])): 
    $pending = $_SESSION['pending_reactivation'];
    
    // Define fuso horário de Angola para exibição
    $tz = new DateTimeZone('Africa/Luanda');
    
    // Cria objeto DateTime a partir do prazo final (já em UTC ou local, tratado como UTC)
    $deadline = new DateTime($pending['deact_until'], new DateTimeZone('UTC'));
    $deadline->setTimezone($tz);
    
    // Data de desativação: 29 dias antes
    $deact = clone $deadline;
    $deact->sub(new DateInterval('P29D'));
    
    $meses_abrev = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    $meses_full  = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    
    $deact_date = $deact->format('d') . ' ' . $meses_abrev[(int)$deact->format('n')-1] . ' ' . $deact->format('Y');
    $deact_full = $deact->format('d') . ' de ' . $meses_full[(int)$deact->format('n')-1] . ' de ' . $deact->format('Y');
    
    $deadline_date = $deadline->format('d') . ' ' . $meses_abrev[(int)$deadline->format('n')-1] . ' ' . $deadline->format('Y');
    $deadline_full = $deadline->format('d') . ' de ' . $meses_full[(int)$deadline->format('n')-1] . ' de ' . $deadline->format('Y');
    
    $now = new DateTime('now', $tz);
    $days_left = $deadline < $now ? 0 : $deadline->diff($now)->days;
?>

    <!-- Modal de Reactivação -->
    <div class="modal fade" id="reactivateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-counterclockwise me-2" style="color: #FF0089;"></i>
                        Reativar a tua conta?
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Desativaste a tua conta Wasom Upfy em <strong><?php echo $deact_full; ?></strong>.</p>

                    <div class="reactivate-dates">
                        <div class="reactivate-date-item">
                            <div class="reactivate-date-label">Data da desativação</div>
                            <div class="reactivate-date-value"><?php echo $deact_date; ?></div>
                        </div>
                        <div class="reactivate-date-item">
                            <div class="reactivate-date-label">Prazo final</div>
                            <div class="reactivate-date-value"><?php echo $deadline_date; ?></div>
                        </div>
                    </div>

                    <div class="reactivate-highlight">
                        <i class="bi bi-exclamation-triangle-fill me-2" style="color: #FF0089;"></i>
                        A partir de <strong><?php echo $deadline_full; ?></strong> não será mais possível restaurar a
                        tua conta.
                        <?php if ($days_left > 0): ?>
                        <br><span class="badge bg-warning text-dark mt-2">Faltam <?php echo $days_left; ?> dia(s)</span>
                        <?php endif; ?>
                    </div>

                    <p class="mb-0 small text-muted">
                        Ao clicar em "Sim, reativar", interromperás o processo de desativação e terás acesso imediato à
                        tua conta.
                    </p>
                </div>
                <div class="modal-footer">
                    <form method="post" action="login_process" class="d-flex gap-2">
                        <input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="confirm_reactivate">
                        <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-reactivate">
                            <i class="bi bi-check-circle me-2"></i>Sim, reativar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    window.addEventListener('DOMContentLoaded', function() {
        var modalElement = document.getElementById('reactivateModal');
        if (modalElement) {
            var modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    });
    </script>
    <?php endif; ?>

    <?php include __DIR__ . '/_modal_support.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $siteUrl; ?>/js/validacao.js"></script>
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