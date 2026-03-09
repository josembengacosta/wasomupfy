<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Verificar E-mail por Token
// Arquivo: authentic/verify-email.php
//
// Acedido via link no email: ?token=XXXXXXXX
// Comportamentos:
//   ✓ Token válido      → verifica email, inicia sessão, redireciona
//   ✗ Token inválido    → mostra erro com opção de reenviar
//   ✓ Já verificado     → avisa que já está activo
//   ✗ Token expirado    → avisa e permite reenviar
// ══════════════════════════════════════════════
require_once __DIR__ . '/include/functions.php';
startSecureSession();

if (isLoggedIn()) {
    redirect('/dashboard/painel');
}

$token = trim($_GET['token'] ?? '');

// ─── Sem token: redirecionar ──────────────────
if (empty($token)) {
    redirect('/login', ['error' => 'invalid_link']);
}

$db = getDB();

// ─── Verificar se o token existe na BD (incluindo usados/expirados) ──
$stmt = $db->prepare("
    SELECT t.*, u.email_user, u.first_name, u.email_verified, u.id_users, u.status_user
    FROM _users_tokens t
    JOIN _users u ON u.id_users = t.id_users
    WHERE t.token = ? AND t.type = 'email_verify'
    LIMIT 1
");
$stmt->execute([$token]);
$row = $stmt->fetch();

// ─── Token não existe ─────────────────────────
if (!$row) {
    $state = 'invalid';
    $email = '';
}
// ─── Email já verificado ──────────────────────
elseif ($row['email_verified'] == 1) {
    $state = 'already_verified';
    $email = $row['email_user'];
}
// ─── Token expirado ───────────────────────────
elseif ($row['is_used'] == 1 || strtotime($row['expires_at']) < time()) {
    $state = 'expired';
    $email = $row['email_user'];
}
// ─── Token válido: verificar ──────────────────
else {
    $state = 'success';
    $id_users  = (int)$row['id_users'];
    $email     = $row['email_user'];
    $firstName = $row['first_name'];

    // Activar email e actualizar estado da conta
    $db->prepare("
        UPDATE _users
        SET email_verified = 1, email_verified_at = NOW(),
            status_user = 'pending_plan', modif_user = NOW()
        WHERE id_users = ? AND email_verified = 0
    ")->execute([$id_users]);

    // Marcar token como usado
    $db->prepare("
        UPDATE _users_tokens SET is_used = 1 WHERE id_token = ?
    ")->execute([$row['id_token']]);

    // Registar actividade
    logActivity($id_users, 'email_verified', 'E-mail verificado via link no email');

    // Iniciar sessão autenticada
    session_regenerate_id(true);
    $_SESSION['id_users']   = $id_users;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['email']      = $email;
    $_SESSION['status']     = 'pending_plan';

    // Registar sessão na tabela
    $db->prepare("
        INSERT INTO _users_sessions
            (id_users, session_token, ip_address, user_agent, is_active)
        VALUES (?, ?, ?, ?, 1)
    ")->execute([
        $id_users,
        session_id(),
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
}

// ─── Verificação tratada, determinar plano pré-sel. ───
$plan_redirect = null;
if ($state === 'success' && !empty($row['extra_data'])) {
    $extra = json_decode($row['extra_data'], true);
    $plan_redirect = $extra['plan_slug'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <title>Verificar e-mail — Wasom Upfy</title>
    <link rel="shortcut icon" href="assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/josembengacosta/wasomupfy@main/css/light.css" />
    <link rel="stylesheet" href="../css/login.css" />
    <style>
        :root {
            --wasom-primary: #ff0089;
        }

        body {
            background: #f8f9fa;
        }

        .verify-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 8px 32px rgba(0, 0, 0, .1);
            overflow: hidden;
        }

        .verify-header {
            background: #FF0089;
            padding: 32px;
            text-align: center;
        }

        .verify-body {
            padding: 40px 32px;
            text-align: center;
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.2rem;
        }

        .btn-wasom {
            background: #FF0089;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px 32px;
            font-size: 1rem;
            font-weight: 600;
            transition: all .3s ease;
            display: inline-block;
            text-decoration: none;
        }

        .btn-wasom:hover {
            background: #cc0070;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 0, 137, .3);
        }

        .countdown {
            font-size: 2.5rem;
            font-weight: 700;
            color: #FF0089;
        }

        .preloader {
            position: fixed;
            inset: 0;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity .4s ease;
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
    </style>
</head>

<body>

    <div class="preloader">
        <div class="spinner-border" role="status"><span class="visually-hidden">A verificar...</span></div>
    </div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-xl-4 col-lg-5 col-md-7 col-11">

                <div class="text-center mb-4">
                    <img src="assets/img/brand/wasomupfy_authentic.png" width="80" alt="Wasom Upfy" />
                </div>

                <div class="card verify-card">
                    <div class="verify-header">
                        <h1 style="color:#fff;font-size:1.3rem;margin:0">Wasom Upfy</h1>
                    </div>
                    <div class="verify-body">

                        <?php if ($state === 'success'): ?>
                            <!-- ── SUCESSO ─────────────────────── -->
                            <div class="icon-circle" style="background:rgba(25,135,84,.1)">
                                <i class="bi bi-check-circle-fill text-success"></i>
                            </div>
                            <h3 class="text-dark mb-2">E-mail confirmado!</h3>
                            <p class="text-muted mb-1">
                                Olá <strong><?php echo htmlspecialchars($firstName); ?></strong>, a tua conta está activa.
                            </p>
                            <p class="text-muted small mb-4">
                                Serás redirecionado em <span id="countdown" class="fw-bold text-success">5</span>s...
                            </p>
                            <?php
                            $redirect_url = $plan_redirect
                                ? (APP_URL . '/dashboard/all-plans?plan=' . urlencode($plan_redirect) . '&welcome=1')
                                : (APP_URL . '/dashboard/all-plans?welcome=1');
                            ?>
                            <a href="<?php echo $redirect_url; ?>" class="btn-wasom">
                                <i class="bi bi-arrow-right me-2"></i>Continuar
                            </a>
                            <script>
                                let s = 5;
                                const el = document.getElementById('countdown');
                                const iv = setInterval(() => {
                                    s--;
                                    if (el) el.textContent = s;
                                    if (s <= 0) {
                                        clearInterval(iv);
                                        window.location = '<?php echo $redirect_url; ?>';
                                    }
                                }, 1000);
                            </script>

                        <?php elseif ($state === 'already_verified'): ?>
                            <!-- ── JÁ VERIFICADO ──────────────── -->
                            <div class="icon-circle" style="background:rgba(13,110,253,.1)">
                                <i class="bi bi-patch-check-fill text-primary fs-1"></i>
                            </div>
                            <h3 class="text-dark mb-2">Conta já activa</h3>
                            <p class="text-muted mb-4">
                                O e-mail <strong><?php echo htmlspecialchars($email); ?></strong>
                                já foi verificado anteriormente. Podes fazer login directamente.
                            </p>
                            <a href="login" class="btn-wasom">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Fazer login
                            </a>

                        <?php elseif ($state === 'expired'): ?>
                            <!-- ── EXPIRADO ────────────────────── -->
                            <div class="icon-circle" style="background:rgba(255,193,7,.1)">
                                <i class="bi bi-clock-history" style="color:#ffc107;font-size:2.2rem"></i>
                            </div>
                            <h3 class="text-dark mb-2">Link expirado</h3>
                            <p class="text-muted mb-4">
                                O link de verificação já expirou (validade de 48 horas).<br>
                                Solicita um novo link abaixo.
                            </p>
                            <!-- Formulário de reenvio -->
                            <form id="resend-form" class="mb-3">
                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>" />
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
                                <button type="submit" class="btn-wasom" id="resend-btn">
                                    <span id="resend-text"><i class="bi bi-envelope-arrow-up me-2"></i>Reenviar link de
                                        verificação</span>
                                    <span id="resend-load" class="d-none"><span
                                            class="spinner-border spinner-border-sm me-2"></span>A enviar...</span>
                                </button>
                            </form>
                            <div id="resend-feedback" class="small"></div>
                            <div class="mt-3">
                                <a href="login" class="text-decoration-none small" style="color:#ff0089">
                                    <i class="bi bi-arrow-left me-1"></i>Voltar ao login
                                </a>
                            </div>

                        <?php else: /* invalid */ ?>
                            <!-- ── INVÁLIDO ────────────────────── -->
                            <div class="icon-circle" style="background:rgba(220,53,69,.1)">
                                <i class="bi bi-x-circle-fill text-danger fs-1"></i>
                            </div>
                            <h3 class="text-dark mb-2">Link inválido</h3>
                            <p class="text-muted mb-4">
                                Este link de verificação não existe ou já foi utilizado.<br>
                                Se acabaste de te registar e não recebeste o email, verifica a pasta de spam.
                            </p>
                            <a href="login" class="btn-wasom">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Ir para o login
                            </a>
                        <?php endif; ?>

                    </div><!-- /verify-body -->
                </div><!-- /card -->

                <div class="text-center mt-3 small text-muted">
                    Precisas de ajuda?
                    <a href="#" data-bs-toggle="modal" data-bs-target="#support" style="color:#ff0089">
                        Contacta o Suporte
                    </a>
                </div>

            </div>
        </div>
    </div>

    <?php include __DIR__ . '/_modal_support.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('load', () => requestAnimationFrame(() => document.body.classList.add('loaded')));

        // ── Reenviar link de verificação (estado expirado) ────
        const resendForm = document.getElementById('resend-form');
        if (resendForm) {
            resendForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = document.getElementById('resend-btn');
                const text = document.getElementById('resend-text');
                const load = document.getElementById('resend-load');
                const fb = document.getElementById('resend-feedback');

                text.classList.add('d-none');
                load.classList.remove('d-none');
                btn.disabled = true;

                fetch('resend-verification', {
                        method: 'POST',
                        body: new FormData(this)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.ok) {
                            fb.innerHTML =
                                '<span class="text-success"><i class="bi bi-check-circle me-1"></i>' + data
                                .message + '</span>';
                            btn.style.display = 'none';
                        } else {
                            fb.innerHTML =
                                '<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>' + data
                                .message + '</span>';
                        }
                    })
                    .catch(() => {
                        fb.innerHTML = '<span class="text-danger">Erro de ligação. Tenta novamente.</span>';
                    })
                    .finally(() => {
                        text.classList.remove('d-none');
                        load.classList.add('d-none');
                        btn.disabled = false;
                    });
            });
        }
    </script>
</body>

</html>