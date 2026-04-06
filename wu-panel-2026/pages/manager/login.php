<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY for Business — Treasury Desk · Login
// Arquivo: wu-panel-2026/pages/manager/login.php
// Rota:    wu-panel-2026/manager/login
// ══════════════════════════════════════════════════════════════

require_once __DIR__ . '/../../include/platform_admin.php';
require_once __DIR__ . '/include/payment-guard.php';

requirePermission($admin_id, 'finances.view');

// ── Mesma ordem da versão original ────────────────────────────
paymentPanelEnsureCsrf();
paymentPanelExpireIfIdle(); // pode limpar payment_control_auth se a sessão expirou

$payment_base   = paymentPanelBaseUrl();
$current_target = paymentPanelCurrentTarget();
$auth_error     = '';
$attempts       = (int)($_SESSION['biz_attempts'] ?? 0);

// ── Processar submissão do formulário ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'unlock_payment_panel') {
    if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $auth_error = 'Sessão expirada. Recarrega a página.';
    } else {
        $return_to   = paymentPanelSanitizeReturnTarget($_POST['return_to'] ?? '');
        $access_code = trim((string)($_POST['access_code'] ?? ''));

        // Evitar loop: se return_to apontar para /login, forçar para /gestion
        if (
            $return_to === $payment_base . '/login' ||
            $return_to === paymentPanelDefaultTarget()
        ) {
            $return_to = $payment_base . '/gestion';
        }

        if ($access_code === '') {
            $auth_error = 'Introduz o código de acesso.';
        } elseif (paymentPanelVerifyAccessCode($db, (int)$admin_id, $access_code)) {
            $_SESSION['payment_control_auth'] = true;
            $_SESSION['biz_auth_time']        = time();
            unset($_SESSION['biz_attempts']);
            session_write_close();
            header('Location: ' . $return_to);
            exit;
        } else {
            $attempts++;
            $_SESSION['biz_attempts'] = $attempts;
            $auth_error = 'Código inválido. Tenta novamente.';
        }
    }
}

// ── Se já está autenticado, vai direto para o painel ──────────
// (verificação DEPOIS do expireIfIdle e do POST, igual ao original)
if (!empty($_SESSION['payment_control_auth'])) {
    paymentPanelTouch();
    header('Location: ' . $payment_base . '/gestion');
    exit;
}

// ── A partir daqui: utilizador NÃO autenticado ────────────────
// Prevenir cache do browser (importante após logout)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Calcular o return_to para o formulário (vem via GET quando há redirect com return_to=...)
$form_return_to = paymentPanelSanitizeReturnTarget($_GET['return_to'] ?? '');
if (
    $form_return_to === $payment_base . '/login' ||
    $form_return_to === paymentPanelDefaultTarget()
) {
    $form_return_to = $payment_base . '/gestion';
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Treasury Desk — Acesso · Wasom Upfy</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: "Segoe UI", Arial, sans-serif;
        background: #09101d;
        color: #fff;
    }

    .unlock {
        min-height: 100vh;
        display: grid;
        grid-template-columns: 1.1fr .9fr;
    }

    /* Lado esquerdo */
    .stage {
        padding: 52px;
        background:
            radial-gradient(circle at top left, rgba(255, 0, 137, .20), transparent 34%),
            linear-gradient(140deg, #09101d, #111a31 55%, #0f172a);
    }

    .tag,
    .chip {
        display: inline-flex;
        gap: 8px;
        align-items: center;
        border-radius: 999px;
        padding: 8px 14px;
        font-size: .78rem;
    }

    .tag {
        background: rgba(255, 255, 255, .09);
        border: 1px solid rgba(255, 255, 255, .10);
        color: #ffd2ec;
    }

    .chip {
        background: rgba(255, 255, 255, .08);
        color: #dbeafe;
    }

    h1 {
        font-size: 2.8rem;
        line-height: 1;
        margin: 18px 0 14px;
        max-width: 560px;
    }

    .copy {
        max-width: 620px;
        color: #d1d5db;
        line-height: 1.7;
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-top: 24px;
    }

    .metric {
        padding: 18px;
        border-radius: 20px;
        background: rgba(255, 255, 255, .07);
        border: 1px solid rgba(255, 255, 255, .08);
    }

    .metric strong {
        display: block;
        font-size: 1.2rem;
        margin-bottom: 6px;
    }

    .metric span {
        font-size: .8rem;
        color: #cbd5e1;
    }

    /* Lado direito */
    .panel {
        background: #fff;
        color: #111827;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 28px;
    }

    .cardx {
        width: min(460px, 100%);
        padding: 28px;
        border-radius: 28px;
        box-shadow: 0 30px 80px rgba(15, 23, 42, .18);
    }

    .brand {
        display: flex;
        gap: 14px;
        align-items: center;
        margin-bottom: 18px;
    }

    .brand img {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        object-fit: cover;
    }

    .brand strong {
        display: block;
        font-size: 1.35rem;
    }

    .brand span {
        color: #6b7280;
        font-size: .88rem;
    }

    .feat-list {
        display: grid;
        gap: 12px;
        margin: 22px 0;
    }

    .feat-item {
        display: flex;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 18px;
        background: #f8fafc;
    }

    .feat-item i {
        color: #ff0089;
        font-size: 1.1rem;
    }

    .feat-item strong {
        display: block;
        font-size: .9rem;
    }

    .feat-item .sub {
        font-size: .78rem;
        color: #6b7280;
    }

    .btn-unlock {
        min-height: 52px;
        border: 0;
        border-radius: 16px;
        font-weight: 700;
        font-size: 1rem;
        color: #fff;
        background: linear-gradient(135deg, #ff0089, #f97316);
        transition: opacity .2s;
    }

    .btn-unlock:hover {
        opacity: .88;
    }

    @media (max-width: 991px) {
        .unlock {
            grid-template-columns: 1fr;
        }

        .stage {
            display: none;
        }
    }

    @media (max-width: 576px) {
        .cardx {
            padding: 22px 18px;
        }
    }
    </style>
</head>

<body>
    <div class="unlock">

        <!-- Lado esquerdo: apresentação -->
        <section class="stage">
            <span class="tag"><i class="bi bi-shield-lock"></i> Access code obrigatório</span>
            <h1>Treasury Desk para saques, comprovativos e royalties.</h1>
            <p class="copy">
                Este cockpit isola a operação financeira do admin geral. O desbloqueio usa o
                <code>access_code</code> do colaborador autenticado em <em>Funcionários</em>.
            </p>
            <div class="metrics-grid">
                <div class="metric">
                    <strong>Saques</strong>
                    <span>Triagem, aprovação e fecho operacional.</span>
                </div>
                <div class="metric">
                    <strong>Royalties</strong>
                    <span>Crédito e trilha financeira no mesmo shell.</span>
                </div>
                <div class="metric">
                    <strong>Compliance</strong>
                    <span>Auditoria, comprovativos e passos registados.</span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <span class="chip"><i class="bi bi-clock-history"></i> Sessão expira em 4 h</span>
                <span class="chip"><i class="bi bi-person-badge"></i> Admin #<?php echo (int)$admin_id; ?></span>
            </div>
        </section>

        <!-- Lado direito: formulário -->
        <section class="panel">
            <div class="cardx">
                <div class="brand">
                    <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" alt="Wasom Upfy">
                    <div>
                        <strong>Desbloquear Treasury Desk</strong>
                        <span>Entra com o código de controlo financeiro.</span>
                    </div>
                </div>

                <div class="feat-list">
                    <div class="feat-item">
                        <i class="bi bi-wallet2"></i>
                        <div>
                            <strong>Sala de liquidação</strong>
                            <div class="sub">Assume um saque e fecha o pagamento no mesmo ecrã.</div>
                        </div>
                    </div>
                    <div class="feat-item">
                        <i class="bi bi-receipt-cutoff"></i>
                        <div>
                            <strong>Fila operacional</strong>
                            <div class="sub">Comprovativos pendentes e royalties prontos para crédito.</div>
                        </div>
                    </div>
                </div>

                <?php if ($auth_error !== ''): ?>
                <div class="alert alert-danger small mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <?php echo htmlspecialchars($auth_error); ?>
                </div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <input type="hidden" name="action" value="unlock_payment_panel">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
                    <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($form_return_to); ?>">

                    <label class="form-label fw-bold small" for="access_code">Access code</label>
                    <input class="form-control mb-3" type="password" id="access_code" name="access_code"
                        autocomplete="one-time-code" inputmode="numeric" placeholder="Ex: 482913" autofocus required>

                    <button class="btn btn-unlock w-100" type="submit">
                        <i class="bi bi-unlock-fill me-2"></i>Entrar no painel financeiro
                    </button>
                </form>

                <div class="text-muted small mt-3 text-center">
                    <?php if ($attempts > 0): ?>
                    Tentativas nesta sessão: <strong><?php echo $attempts; ?></strong>.
                    <?php else: ?>
                    O código é comparado com o <code>access_code</code> do colaborador.
                    <?php endif; ?>
                </div>
            </div>
        </section>

    </div>
</body>

</html>