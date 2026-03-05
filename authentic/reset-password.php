<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Nova Senha
// Arquivo: authentic/reset-password.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/include/functions.php';
startSecureSession();

if (isLoggedIn()) {
    redirect('/dashboard/painel');
}

// ─── Guardar de reset válido na sessão ────────
// Se não há sessão de reset activa, redirecionar de volta
if (empty($_SESSION['reset_user_id']) || empty($_SESSION['reset_verified_at'])) {
    redirect('/forgot-password', ['error' => 'session_expired']);
}
// Token de reset expira 15 minutos após verificação do código
if (time() - $_SESSION['reset_verified_at'] > 15 * 60) {
    unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_verified_at']);
    redirect('/forgot-password', ['error' => 'session_expired']);
}

// ─── Emails e notices ─────────────────────────
$reset_email = htmlspecialchars($_SESSION['reset_email'] ?? '');
$notices = [
    'code_ok' => 'Código verificado! Define agora a tua nova senha.',
];
$errors = [
    'csrf'             => 'Sessão expirada. Tenta novamente.',
    'weak_password'    => 'A senha deve ter no mínimo 8 caracteres.',
    'password_mismatch' => 'As senhas não coincidem. Tenta novamente.',
    'session_expired'  => 'A sessão expirou. Solicita um novo código.',
];
$notice = isset($_GET['notice']) ? ($notices[$_GET['notice']] ?? '') : '';
$error  = isset($_GET['error'])  ? ($errors[$_GET['error']]  ?? 'Erro desconhecido.') : '';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <title>Nova senha — Wasom Upfy</title>
    <link rel="shortcut icon" href="assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/josembengacosta/wasomupfy@main/css/light.css" />
    <link rel="stylesheet" href="../css/login.css" />
    <link rel="stylesheet" href="../css/loanding.css" />
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
            border-radius: 5px;
            padding: 3px 6px;
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

        .spinner-border {
            border-bottom-color: #ff0089;
            border-top-color: #ff0089;
            border-left-color: #ff0089;
        }

        .form-control:focus {
            border-color: var(--wasom-primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 0, 137, .15);
        }

        /* ── Barra de força da senha ── */
        #strength-bar {
            height: 5px;
            border-radius: 4px;
            transition: width .3s ease, background-color .3s ease;
            width: 0;
            margin-top: 6px;
        }

        #strength-label {
            font-size: .75rem;
            margin-top: 3px;
        }

        /* ── Requisitos da senha ── */
        .req-item {
            font-size: .8rem;
            color: #aaa;
            transition: color .2s;
        }

        .req-item.ok {
            color: #198754;
        }

        .req-item .bi {
            margin-right: 4px;
        }

        .text-wasom {
            color: var(--wasom-primary) !important;
        }

        .email-chip {
            display: inline-block;
            background: rgba(255, 0, 137, .08);
            border: 1px solid rgba(255, 0, 137, .2);
            border-radius: 20px;
            padding: 2px 12px;
            font-size: .82rem;
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
                        <h1 class="h3 mt-3 text-wasom">Nova senha</h1>
                        <p class="text-muted small">Cria uma nova senha segura para a tua conta.</p>
                        <?php if ($reset_email): ?>
                            <div class="mt-1">
                                <span class="email-chip"><i
                                        class="bi bi-envelope me-1"></i><?php echo $reset_email; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section id="reset-password">
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

                                <form method="POST" action="reset-password-process" class="needs-validation"
                                    id="reset-form" novalidate>
                                    <input type="hidden" name="csrf_token"
                                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
                                    <input type="text" name="honeypot" style="display:none" value=""
                                        autocomplete="off" />

                                    <!-- Nova senha -->
                                    <div class="mb-2">
                                        <label for="password" class="form-label fw-semibold">
                                            Nova senha <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password"
                                                required autocomplete="new-password" placeholder="Cria uma nova senha"
                                                minlength="8" maxlength="60" oninput="checkStrength()" />
                                            <button type="button" class="btn btn-outline-secondary" id="btn-show"
                                                onclick="togglePwd('password','btn-show','btn-hide')"
                                                aria-label="Mostrar">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary d-none" id="btn-hide"
                                                onclick="togglePwd('password','btn-show','btn-hide')"
                                                aria-label="Esconder">
                                                <i class="bi bi-eye-slash"></i>
                                            </button>
                                            <div class="invalid-feedback">A senha deve ter no mínimo 8 caracteres.</div>
                                        </div>
                                        <!-- Barra de força -->
                                        <div id="strength-bar"></div>
                                        <div id="strength-label" class="text-muted"></div>
                                        <!-- Requisitos visuais -->
                                        <div class="mt-2 d-flex flex-wrap gap-2" id="pwd-reqs">
                                            <span class="req-item" id="req-len"><i class="bi bi-x-circle"></i>8+
                                                caracteres</span>
                                            <span class="req-item" id="req-upper"><i
                                                    class="bi bi-x-circle"></i>Maiúscula</span>
                                            <span class="req-item" id="req-num"><i
                                                    class="bi bi-x-circle"></i>Número</span>
                                            <span class="req-item" id="req-special"><i
                                                    class="bi bi-x-circle"></i>Símbolo</span>
                                        </div>
                                    </div>

                                    <!-- Confirmar senha -->
                                    <div class="mb-3 mt-3">
                                        <label for="confirm_password" class="form-label fw-semibold">
                                            Confirmar senha <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password"
                                                name="confirm_password" required autocomplete="new-password"
                                                placeholder="Repete a senha" minlength="8" maxlength="60"
                                                oninput="checkMatch()" />
                                            <button type="button" class="btn btn-outline-secondary" id="btn-show2"
                                                onclick="togglePwd('confirm_password','btn-show2','btn-hide2')"
                                                aria-label="Mostrar">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary d-none"
                                                id="btn-hide2"
                                                onclick="togglePwd('confirm_password','btn-show2','btn-hide2')"
                                                aria-label="Esconder">
                                                <i class="bi bi-eye-slash"></i>
                                            </button>
                                            <div class="invalid-feedback">As senhas não coincidem.</div>
                                        </div>
                                        <div id="match-feedback" class="small mt-1"></div>
                                    </div>

                                    <div class="d-grid mb-3">
                                        <button class="btn btn-wasomupfy" type="submit" id="btn-submit">
                                            <span id="btn-text"><i class="bi bi-shield-lock me-1"></i>Definir nova
                                                senha</span>
                                            <span id="btn-loading" class="d-none">
                                                <span class="spinner-border spinner-border-sm me-2"></span>A guardar...
                                            </span>
                                        </button>
                                    </div>

                                    <div class="text-center">
                                        <a href="login" class="text-decoration-none text-wasom small">
                                            <i class="bi bi-arrow-left me-1"></i>Voltar ao login
                                        </a>
                                    </div>
                                </form>

                            </div>
                            <div class="card-footer text-center py-2 small">
                                <a href="#support" data-bs-toggle="modal" data-bs-target="#support"
                                    class="text-wasom me-1">Suporte</a> |
                                <a href="#terms" data-bs-toggle="modal" data-bs-target="#terms"
                                    class="text-wasom mx-1">Termos</a> |
                                <a href="#privacy" data-bs-toggle="modal" data-bs-target="#privacy"
                                    class="text-wasom mx-1">Privacidade</a> |
                                <a href="home" class="text-muted ms-1">Voltar home</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <?php include __DIR__ . '/_modal_support.php'; ?>

    <!-- ══ MODAL PRIVACIDADE (scrollable) ═══════════════════ -->
    <div class="modal fade" id="privacy" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">Política de Privacidade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">A Wasom Upfy valoriza a tua privacidade e protege os teus dados
                        pessoais.</p>
                    <div class="accordion" id="privacyAcc">
                        <?php
                        $pSections = [
                            ['1. Recolha de Dados', 'Nome, e-mail, IP, telefone, dados bancários e informações necessárias para identificar o utilizador e proteger a plataforma.'],
                            ['2. Uso dos Dados', 'Gestão de transacções, envio de comunicações, análise de uso da plataforma e protecção contra ataques.'],
                            ['3. Armazenamento e Segurança', 'Dados armazenados em servidores seguros com criptografia. Mantemos medidas contra acesso não autorizado.'],
                            ['4. Partilha de Dados', 'Dados podem ser partilhados com terceiros de confiança para processamento de pagamentos e análise. Esses terceiros mantêm confidencialidade.'],
                            ['5. Os teus Direitos', 'Podes aceder, corrigir ou solicitar a eliminação dos teus dados. Contacta o Suporte para exercer esses direitos.'],
                            ['6. Segurança Adicional', 'Chaves de recuperação, bloqueio por IP, inactivação de contas suspeitas. Contas solicitadas para eliminação são recuperáveis por 29 dias úteis.'],
                            ['7. Cookies', 'Utilizamos cookies para personalizar a experiência e monitorar a performance. Podes desactivar nas definições do navegador.'],
                            ['8. Alterações', 'A Wasom Upfy pode modificar esta Política a qualquer momento. Serás notificado por e-mail sobre alterações significativas.'],
                        ];
                        foreach ($pSections as $i => [$title, $body]):
                        ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#pa<?php echo $i; ?>">
                                        <?php echo $title; ?>
                                    </button>
                                </h2>
                                <div id="pa<?php echo $i; ?>" class="accordion-collapse collapse"
                                    data-bs-parent="#privacyAcc">
                                    <div class="accordion-body small"><?php echo $body; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-muted small mt-3 mb-0">Actualizado em: 21/10/2024</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ MODAL TERMOS (scrollable) ════════════════════════ -->
    <div class="modal fade" id="terms" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">Termos de Uso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Ao usar a Wasom Upfy, concordas com os termos abaixo.</p>
                    <div class="accordion" id="termsAcc">
                        <?php
                        $tSections = [
                            ['1. Descrição dos Serviços', 'Plataforma de distribuição digital para +157 plataformas (Spotify, Apple Music, Amazon Music, etc.) para artistas em início ou fase estabelecida de carreira.'],
                            ['2. Responsabilidades', 'Podes solicitar relatórios e reembolsos (até 24h). Não podes aceder com múltiplos dispositivos simultaneamente nem solicitar funcionalidades inexistentes.'],
                            ['3. Propriedade Intelectual', 'Manténs a propriedade das tuas músicas. Concedes à Wasom Upfy o direito de distribuir em teu nome. Músicas identificadas por UPC e ISRC únicos.'],
                            ['4. Pagamentos e Royalties', 'Transacções geridas pela equipa Wasom Upfy. Serás notificado por e-mail quando um pagamento for processado, com comprovante.'],
                            ['5. Suspensão de Contas', 'Contas podem ser suspensas por comprovantes falsos, saques suspeitos ou dispositivos desconhecidos. Eliminadas definitivamente por fraude ou duplicação.'],
                            ['6. Limitações', 'A Wasom Upfy não se responsabiliza por falhas em plataformas de terceiros ou problemas técnicos fora do seu controlo.'],
                            ['7. Actualizações', 'Podemos modificar estes Termos a qualquer momento. Serás notificado sobre mudanças importantes.'],
                            ['8. Cookies', 'Utilizamos cookies para personalizar a experiência. Podes desactivar nas definições do navegador.'],
                        ];
                        foreach ($tSections as $i => [$title, $body]):
                        ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#ta<?php echo $i; ?>">
                                        <?php echo $title; ?>
                                    </button>
                                </h2>
                                <div id="ta<?php echo $i; ?>" class="accordion-collapse collapse"
                                    data-bs-parent="#termsAcc">
                                    <div class="accordion-body small"><?php echo $body; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-muted small mt-3 mb-0">Actualizado em: 26/06/2025</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
    <script src="../js/validacao.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/josembengacosta/wasomupfy@main/js/app.js"></script>
    <script>
        // ── Preloader ─────────────────────────────────────────
        window.addEventListener('load', () => requestAnimationFrame(() => document.body.classList.add('loaded')));

        // ── Mostrar/esconder senha ────────────────────────────
        function togglePwd(inputId, showId, hideId) {
            const inp = document.getElementById(inputId);
            const show = document.getElementById(showId);
            const hide = document.getElementById(hideId);
            const isHidden = inp.type === 'password';
            inp.type = isHidden ? 'text' : 'password';
            show.classList.toggle('d-none', isHidden);
            hide.classList.toggle('d-none', !isHidden);
        }

        // ── Força da senha ────────────────────────────────────
        function checkStrength() {
            const v = document.getElementById('password').value;
            const bar = document.getElementById('strength-bar');
            const lbl = document.getElementById('strength-label');

            const hasLen = v.length >= 8;
            const hasUpper = /[A-Z]/.test(v);
            const hasNum = /[0-9]/.test(v);
            const hasSpecial = /[^A-Za-z0-9]/.test(v);

            setReq('req-len', hasLen);
            setReq('req-upper', hasUpper);
            setReq('req-num', hasNum);
            setReq('req-special', hasSpecial);

            const score = [hasLen, hasUpper, hasNum, hasSpecial].filter(Boolean).length;
            const pct = score * 25;
            const colors = ['#dc3545', '#dc3545', '#ffc107', '#fd7e14', '#198754'];
            const labels = ['', 'Fraca', 'Fraca', 'Razoável', 'Boa', 'Forte'];

            bar.style.width = pct + '%';
            bar.style.backgroundColor = colors[score] ?? '#198754';
            lbl.textContent = labels[score] ?? '';
            lbl.style.color = colors[score] ?? '#198754';
        }

        function setReq(id, ok) {
            const el = document.getElementById(id);
            el.classList.toggle('ok', ok);
            el.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-x-circle';
        }

        // ── Confirmar senha ───────────────────────────────────
        function checkMatch() {
            const p1 = document.getElementById('password').value;
            const p2 = document.getElementById('confirm_password').value;
            const fb = document.getElementById('match-feedback');
            if (!p2) {
                fb.textContent = '';
                return;
            }
            if (p1 === p2) {
                fb.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Senhas coincidem</span>';
                document.getElementById('confirm_password').classList.remove('is-invalid');
                document.getElementById('confirm_password').classList.add('is-valid');
            } else {
                fb.innerHTML =
                    '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>As senhas não coincidem</span>';
                document.getElementById('confirm_password').classList.add('is-invalid');
                document.getElementById('confirm_password').classList.remove('is-valid');
            }
        }

        // ── Submit com loading state ──────────────────────────
        document.getElementById('reset-form').addEventListener('submit', function(e) {
            const p1 = document.getElementById('password').value;
            const p2 = document.getElementById('confirm_password').value;
            if (p1.length < 8 || p1 !== p2) {
                e.preventDefault();
                return;
            }
            document.getElementById('btn-text').classList.add('d-none');
            document.getElementById('btn-loading').classList.remove('d-none');
            document.getElementById('btn-submit').disabled = true;
        });

        // ── Modal suporte — contador e envio Ajax ─────────────
        const msgTextarea = document.querySelector('textarea[name="messenger"]');
        if (msgTextarea) {
            msgTextarea.addEventListener('input', function() {
                document.getElementById('msg-count').textContent = this.value.length;
            });
        }

        document.getElementById('support-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            document.getElementById('support-btn-text').classList.add('d-none');
            document.getElementById('support-btn-load').classList.remove('d-none');
            document.getElementById('support-submit').disabled = true;
            document.getElementById('support-error').classList.add('d-none');

            const fd = new FormData(form);
            fetch('support_process', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        document.getElementById('support-fields').classList.add('d-none');
                        document.getElementById('support-success').classList.remove('d-none');
                        document.getElementById('support-submit').classList.add('d-none');
                    } else {
                        document.getElementById('support-error-msg').textContent = data.message ||
                            'Erro ao enviar.';
                        document.getElementById('support-error').classList.remove('d-none');
                    }
                })
                .catch(() => {
                    document.getElementById('support-error-msg').textContent =
                        'Erro de ligação. Tenta novamente.';
                    document.getElementById('support-error').classList.remove('d-none');
                })
                .finally(() => {
                    document.getElementById('support-btn-text').classList.remove('d-none');
                    document.getElementById('support-btn-load').classList.add('d-none');
                    document.getElementById('support-submit').disabled = false;
                });
        });
    </script>
</body>

</html>