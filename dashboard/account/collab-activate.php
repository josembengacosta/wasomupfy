<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Activação de Colaborador
// Arquivo: dashboard/account/collab-activate.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
// Não requer login — é a página de activação pública

$db    = getDB();
$token = trim($_GET['token'] ?? '');
$state = 'invalid'; // invalid | used | expired | valid
$collab = null;
$owner  = null;

if (!empty($token)) {
    $st = $db->prepare("
        SELECT c.*, u.first_name AS owner_first, u.second_name AS owner_second,
               u.email_user AS owner_email, p.name_plan
        FROM _collaborators c
        JOIN _users u ON u.id_users = c.id_users
        LEFT JOIN _plans p ON p.id_plan = u.plan_selected
        WHERE c.invite_token = ?
        LIMIT 1
    ");
    $st->execute([$token]);
    $row = $st->fetch();

    if (!$row) {
        $state = 'invalid';
    } elseif ($row['invite_token_used']) {
        $state = 'used';
        $collab = $row;
    } elseif (strtotime($row['invite_token_expires']) < time()) {
        $state = 'expired';
        $collab = $row;
    } else {
        $state  = 'valid';
        $collab = $row;
    }
}

$role_labels = ['admin'=>'Administrador','editor'=>'Editor','analyst'=>'Analista','support'=>'Suporte'];
$role_label  = $collab ? ($role_labels[$collab['role_collab']] ?? 'Colaborador') : '';
$owner_name  = $collab ? trim($collab['owner_first'] . ' ' . ($collab['owner_second'] ?? '')) : '';
$plan_name   = $collab ? ($collab['name_plan'] ?? APP_NAME) : APP_NAME;
$login_url   = rtrim(APP_URL,'/') . '/dashboard/account/collab-login';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="theme-color" content="#FF0089" />
    <title>Activar Conta — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <style>
    * {
        box-sizing: border-box;
    }

    body {
        background: linear-gradient(135deg, #1a0010 0%, #2d0020 50%, #0d0010 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', Arial, sans-serif;
        padding: 20px;
    }

    .card-activate {
        max-width: 480px;
        width: 100%;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 24px 80px rgba(0, 0, 0, .6);
    }

    .card-header-bg {
        background: linear-gradient(135deg, #FF0089, #FF4D4D);
        padding: 32px;
        text-align: center;
    }

    .card-body-bg {
        background: #fff;
        padding: 32px;
    }

    .avatar-wrap {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 3px solid rgba(255, 255, 255, .4);
        margin: 0 auto 12px;
        overflow: hidden;
        background: rgba(255, 255, 255, .2);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .avatar-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .role-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, .2);
        color: #fff;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: .78rem;
        font-weight: 700;
    }

    .info-pill {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: .83rem;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
    }

    .info-pill span:first-child {
        color: #888;
    }

    .info-pill span:last-child {
        font-weight: 700;
        color: #222;
    }

    .btn-wasom {
        background: linear-gradient(135deg, #FF0089, #FF4D4D);
        color: #fff;
        border: none;
        border-radius: 50px;
        padding: 13px 36px;
        font-weight: 800;
        font-size: .95rem;
        transition: transform .2s, box-shadow .2s;
        width: 100%;
    }

    .btn-wasom:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(255, 0, 137, .4);
        color: #fff;
    }

    .state-icon {
        font-size: 3.5rem;
        display: block;
        text-align: center;
        margin-bottom: 16px;
    }

    .strength-bar {
        height: 5px;
        border-radius: 4px;
        background: #e9ecef;
        margin-top: 6px;
    }

    .strength-fill {
        height: 100%;
        border-radius: 4px;
        transition: width .3s, background .3s;
    }

    .form-control:focus {
        border-color: #FF0089;
        box-shadow: 0 0 0 .2rem rgba(255, 0, 137, .18);
    }
    </style>
</head>

<body>

    <?php if ($state === 'invalid'): ?>
    <div class="card-activate">
        <div class="card-header-bg">
            <h1 style="color:#fff;font-size:1.3rem;margin:0"><?php echo APP_NAME; ?></h1>
        </div>
        <div class="card-body-bg text-center">
            <span class="state-icon">🔗</span>
            <h3 class="fw-bold mb-2">Link inválido</h3>
            <p class="text-muted">Este link de activação não existe ou já foi removido.</p>
            <a href="<?php echo htmlspecialchars($login_url); ?>" class="btn btn-outline-secondary mt-2">Ir para o
                login</a>
        </div>
    </div>

    <?php elseif ($state === 'used'): ?>
    <div class="card-activate">
        <div class="card-header-bg">
            <h1 style="color:#fff;font-size:1.3rem;margin:0"><?php echo APP_NAME; ?></h1>
        </div>
        <div class="card-body-bg text-center">
            <span class="state-icon">✅</span>
            <h3 class="fw-bold mb-2">Conta já activada</h3>
            <p class="text-muted">Este link de activação já foi utilizado. A tua conta já está activa.</p>
            <a href="<?php echo htmlspecialchars($login_url); ?>" class="btn btn-wasom mt-2">Entrar no painel</a>
        </div>
    </div>

    <?php elseif ($state === 'expired'): ?>
    <div class="card-activate">
        <div class="card-header-bg">
            <h1 style="color:#fff;font-size:1.3rem;margin:0"><?php echo APP_NAME; ?></h1>
        </div>
        <div class="card-body-bg text-center">
            <span class="state-icon">⏰</span>
            <h3 class="fw-bold mb-2">Link expirado</h3>
            <p class="text-muted">Este link de activação expirou (válido por 72h). Pede ao administrador da conta que
                reenvie o convite.</p>
            <a href="<?php echo htmlspecialchars($login_url); ?>" class="btn btn-outline-secondary mt-2">Ir para o
                login</a>
        </div>
    </div>

    <?php elseif ($state === 'valid'): ?>
    <div class="card-activate">
        <!-- Header -->
        <div class="card-header-bg">
            <div class="avatar-wrap">
                <?php if ($collab['photo_collab']): ?>
                <img src="<?php echo htmlspecialchars($collab['photo_collab']); ?>" alt="Foto"
                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                <div
                    style="display:none;width:100%;height:100%;align-items:center;justify-content:center;font-size:2rem;color:rgba(255,255,255,.6)">
                    👤</div>
                <?php else: ?>
                <div style="font-size:2rem;color:rgba(255,255,255,.7)">👤</div>
                <?php endif; ?>
            </div>
            <h2 style="color:#fff;margin:0;font-size:1.2rem;font-weight:800">
                Olá, <?php echo htmlspecialchars($collab['first_name']); ?>!
            </h2>
            <p style="color:rgba(255,255,255,.8);margin:6px 0 12px;font-size:.85rem">
                Foste convidado por <strong><?php echo htmlspecialchars($owner_name); ?></strong>
            </p>
            <span class="role-chip">
                <i class="bi bi-person-badge"></i><?php echo htmlspecialchars($role_label); ?>
            </span>
        </div>

        <!-- Body -->
        <div class="card-body-bg">
            <!-- Info pills -->
            <div class="info-pill">
                <span>Username</span><span>@<?php echo htmlspecialchars($collab['user_collab']); ?></span></div>
            <div class="info-pill">
                <span>Email</span><span><?php echo htmlspecialchars($collab['email_collab']); ?></span></div>
            <div class="info-pill"><span>Função</span><span><?php echo htmlspecialchars($role_label); ?></span></div>
            <div class="info-pill"><span>Plano da conta</span><span><?php echo htmlspecialchars($plan_name); ?></span>
            </div>

            <div class="alert alert-warning small d-flex gap-2 mt-3 mb-3">
                <i class="bi bi-shield-lock-fill flex-shrink-0 mt-1"></i>
                <div>Após activar, serás solicitado a <strong>alterar a tua senha</strong> por segurança antes do
                    primeiro acesso.</div>
            </div>

            <!-- Activation form -->
            <div id="step-accept">
                <button class="btn btn-wasom"
                    onclick="document.getElementById('step-accept').classList.add('d-none');document.getElementById('step-password').classList.remove('d-none')">
                    <i class="bi bi-check2-circle me-2"></i>Aceitar convite e activar conta
                </button>
                <div class="text-center mt-3">
                    <a href="<?php echo htmlspecialchars($login_url); ?>" class="text-muted small">Já tenho acesso.
                        Entrar</a>
                </div>
            </div>

            <!-- Password change step -->
            <div id="step-password" class="d-none">
                <p class="fw-semibold small mb-3"><i class="bi bi-key me-1" style="color:#FF0089"></i>Define a tua nova
                    senha de acesso</p>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nova Senha <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="new-pwd" placeholder="Mín. 8 caracteres"
                            oninput="checkStr(this.value)" required />
                        <button class="btn btn-outline-secondary" type="button" onclick="toggleP('new-pwd',this)"><i
                                class="bi bi-eye"></i></button>
                    </div>
                    <div class="strength-bar mt-1">
                        <div class="strength-fill" id="str-bar" style="width:0;background:#dc3545"></div>
                    </div>
                    <small id="str-label" class="text-muted"></small>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Confirmar Senha <span
                            class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="conf-pwd" placeholder="Repete a nova senha" />
                        <button class="btn btn-outline-secondary" type="button" onclick="toggleP('conf-pwd',this)"><i
                                class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div id="act-feedback" class="mb-2 d-none"></div>
                <button class="btn btn-wasom" id="btn-activate" onclick="activateAccount()">
                    <span id="btn-act-text"><i class="bi bi-unlock me-1"></i>Activar e definir senha</span>
                    <span id="btn-act-load" class="d-none"><span class="spinner-border spinner-border-sm"></span></span>
                </button>
            </div>

            <div id="step-done" class="d-none text-center">
                <span style="font-size:3rem;display:block;margin-bottom:12px">🎉</span>
                <h4 class="fw-bold mb-2">Conta activada!</h4>
                <p class="text-muted small mb-3">A tua senha foi definida. Podes entrar no painel agora.</p>
                <a id="btn-go-login" href="<?php echo htmlspecialchars($login_url); ?>" class="btn btn-wasom">
                    <i class="bi bi-arrow-right-circle me-1"></i>Entrar no painel
                </a>
            </div>
        </div>
    </div>

    <script>
    const TOKEN = '<?php echo htmlspecialchars($token); ?>';
    const LOGIN_URL = '<?php echo htmlspecialchars($login_url); ?>';
    const PROCESS = '<?php echo rtrim(APP_URL,"/"); ?>/dashboard/account/collab_activate_process';

    function toggleP(id, btn) {
        const inp = document.getElementById(id);
        inp.type = inp.type === 'password' ? 'text' : 'password';
        btn.querySelector('i').className = inp.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
    }

    function checkStr(pwd) {
        const bar = document.getElementById('str-bar');
        const lbl = document.getElementById('str-label');
        let s = 0;
        if (pwd.length >= 8) s++;
        if (pwd.length >= 12) s++;
        if (/[A-Z]/.test(pwd)) s++;
        if (/[0-9]/.test(pwd)) s++;
        if (/[^A-Za-z0-9]/.test(pwd)) s++;
        const m = [
            [20, '#dc3545', 'Muito fraca'],
            [40, '#fd7e14', 'Fraca'],
            [60, '#ffc107', 'Razoável'],
            [80, '#20c997', 'Boa'],
            [100, '#198754', 'Excelente']
        ];
        const [w, c, t] = m[s - 1] || [10, '#dc3545', 'Muito fraca'];
        bar.style.width = w + '%';
        bar.style.background = c;
        lbl.textContent = t;
        lbl.style.color = c;
    }

    async function activateAccount() {
        const pwd = document.getElementById('new-pwd').value;
        const conf = document.getElementById('conf-pwd').value;
        const fb = document.getElementById('act-feedback');
        const btn = document.getElementById('btn-activate');

        if (pwd.length < 8) {
            fb.innerHTML = '<div class="alert alert-danger small py-2">Mínimo 8 caracteres.</div>';
            fb.classList.remove('d-none');
            return;
        }
        if (pwd !== conf) {
            fb.innerHTML = '<div class="alert alert-danger small py-2">As senhas não coincidem.</div>';
            fb.classList.remove('d-none');
            return;
        }

        document.getElementById('btn-act-text').classList.add('d-none');
        document.getElementById('btn-act-load').classList.remove('d-none');
        btn.disabled = true;

        const fd = new FormData();
        fd.append('token', TOKEN);
        fd.append('new_password', pwd);
        fd.append('confirm_password', conf);

        try {
            const r = await fetch(PROCESS, {
                method: 'POST',
                body: fd
            });
            const data = await r.json();
            if (data.ok) {
                document.getElementById('step-password').classList.add('d-none');
                document.getElementById('step-done').classList.remove('d-none');
            } else {
                fb.innerHTML = `<div class="alert alert-danger small py-2">${data.message}</div>`;
                fb.classList.remove('d-none');
                document.getElementById('btn-act-text').classList.remove('d-none');
                document.getElementById('btn-act-load').classList.add('d-none');
                btn.disabled = false;
            }
        } catch {
            fb.innerHTML = '<div class="alert alert-danger small py-2">Erro de ligação.</div>';
            fb.classList.remove('d-none');
            btn.disabled = false;
        }
    }
    </script>
    <?php endif; ?>

</body>

</html>