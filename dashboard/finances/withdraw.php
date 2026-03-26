<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Contas de Saque
// Arquivo: dashboard/finances/withdraw.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
require_once __DIR__ . '/../include/platform.php';
startSecureSession();
checkRememberMe();
requireLogin();
$platform = checkDashboardStatus();
$user     = checkUserAccess((int)$_SESSION['id_users']);

$id_users       = (int)$user['id_users'];
$first_name     = htmlspecialchars($user['first_name']);
$user_name      = htmlspecialchars($user['user_name'] ?? '');
$email_verified = (bool)$user['email_verified'];
$plan_selected  = $user['plan_selected'];
$onboard_done   = (bool)($user['onboarding_done'] ?? false);
$user_photo     = $user['photo_user'] ?? null;
$name_artist_band = htmlspecialchars($user['name_artist_band'] ?? 'Cria Perfil Artístico');
$notif_count    = getUnreadNotifCount($id_users);
$db             = getDB();

// ── Saldo ─────────────────────────────────────
$w = $db->prepare('SELECT balance_aoa FROM _wallet WHERE id_users = ?');
$w->execute([$id_users]);
$balance = $w->fetch() ?: ['balance_aoa' => 0];

// ── Plano ─────────────────────────────────────
$plan_id     = (int)$user['plan_selected'];
$plan        = null;
$max_artists = 1;
if ($plan_id) {
    $ps = $db->prepare('SELECT * FROM _plans WHERE id_plan = ?');
    $ps->execute([$plan_id]);
    $plan = $ps->fetch();
    if ($plan) $max_artists = (int)($plan['max_artists'] ?? 1);
}
$plan_name = $plan ? htmlspecialchars($plan['name_plan']) : 'Sem plano';

// ── Plano ─────────────────────────────────────
$plan      = null;
$plan_paid = ($user['status_user'] === 'active' && !empty($user['plan_activated_at']));
if ($plan_selected) {
    $ps = $db->prepare('SELECT * FROM _plans WHERE id_plan = ?');
    $ps->execute([$plan_selected]);
    $plan = $ps->fetch();
}

// Adicionar verificação de expiração do plano
$plan_expired = false;
if ($plan_paid && !empty($user['plan_expires_at'])) {
    $plan_expired = strtotime($user['plan_expires_at']) < time();
}

// ── Artistas ──────────────────────────────────
$as = $db->prepare('SELECT COUNT(*) AS total FROM _artist WHERE id_users = ?');
$as->execute([$id_users]);
$has_artist = (int)($as->fetch()['total'] ?? 0) > 0;

// ── Conta bancária ────────────────────────────
$ba = $db->prepare("SELECT id_account FROM _account WHERE id_users = ? AND status_account = 'verified' LIMIT 1");
$ba->execute([$id_users]);
$bank_account = $ba->fetch();

// ── Conta rejeitada ───────────────────────────
$rejected_account = null;
if ($plan_paid) {
    $rj = $db->prepare("SELECT type_account, reject_reason FROM _account WHERE id_users = ? AND status_account = 'rejected' LIMIT 1");
    $rj->execute([$id_users]);
    $rejected_account = $rj->fetch();
}

// ── Sessão info (modal logout) ────────────────
$ls = $db->prepare('SELECT last_login_at, last_login_ip FROM _users_security WHERE id_users = ?');
$ls->execute([$id_users]);
$sec = $ls->fetch();

$sess_stmt = $db->prepare("
    SELECT ip_address, user_agent, country, city, creat_session, last_activity
    FROM _users_sessions WHERE id_users = ? AND is_active = 1
    ORDER BY last_activity DESC LIMIT 1
");
$sess_stmt->execute([$id_users]);
$current_session  = $sess_stmt->fetch();
$session_duration_str = '—';
if ($current_session && $current_session['creat_session']) {
    $secs = time() - strtotime($current_session['creat_session']);
    if ($secs < 60)     $session_duration_str = $secs . 's';
    elseif ($secs < 3600)  $session_duration_str = floor($secs / 60) . 'min';
    elseif ($secs < 86400) $session_duration_str = floor($secs / 3600) . 'h ' . floor(($secs % 3600) / 60) . 'min';
    else                   $session_duration_str = floor($secs / 86400) . 'd ' . floor(($secs % 86400) / 3600) . 'h';
}
$member_since   = $user['creat_user'] ? date('d/m/Y', strtotime($user['creat_user'])) : '—';
$last_login_str = ($sec && $sec['last_login_at']) ? date('d/m/Y H:i', strtotime($sec['last_login_at'])) : '—';
$ua_raw   = $current_session['user_agent'] ?? '';
$browser  = 'Desconhecido';
if (str_contains($ua_raw, 'Edg'))     $browser = 'Microsoft Edge';
elseif (str_contains($ua_raw, 'Chrome'))  $browser = 'Google Chrome';
elseif (str_contains($ua_raw, 'Firefox')) $browser = 'Mozilla Firefox';
elseif (str_contains($ua_raw, 'Safari'))  $browser = 'Safari';
elseif (str_contains($ua_raw, 'Opera'))   $browser = 'Opera';
$sess_location = trim(($current_session['city'] ?? '') . ', ' . ($current_session['country'] ?? ''), ', ') ?: 'Desconhecida';
$sess_ip       = $current_session['ip_address'] ?? ($sec['last_login_ip'] ?? '—');

$first_name       = htmlspecialchars($user['first_name']);
$user_artist_name = htmlspecialchars($user['name_artist_band'] ?? $user['first_name']);

// ─── Contas existentes ────────────────────────────────
$accs_stmt = getDB()->prepare("SELECT * FROM _account WHERE id_users = ? ORDER BY type_account ASC, creat_account DESC");
$accs_stmt->execute([$id_users]);
$accounts = $accs_stmt->fetchAll();

$acc_express = null;
$acc_iban    = null;
foreach ($accounts as $a) {
    if (in_array($a['type_account'], ['Express', 'Multicaixa']) && !$acc_express) $acc_express = $a;
    if ($a['type_account'] === 'IBAN' && !$acc_iban) $acc_iban = $a;
}

$can_add_express = ($acc_express === null);
$can_add_iban    = ($acc_iban    === null);

function statusBadge(string $s): string
{
    return match ($s) {
        'verified' => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Verificada</span>',
        'rejected' => '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejeitada</span>',
        default    => '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass me-1"></i>Em análise</span>',
    };
}

$banks = ['Banco Angolano de Investimentos (BAI)', 'Banco de Fomento Angola (BFA)', 'Banco BIC Angola (BIC)', 'Banco Comercial Angolano (BCA)', 'Banco de Comércio e Indústria (BCI)', 'Banco de Poupança e Crédito (BPC)', 'Banco Keve (KEVE)', 'Access Bank Angola, S.A', 'Banco Millennium Atlântico (BMA)', 'Banco Caixa Geral Angola (BCGA)', 'Banco SOL', 'Standard Bank Angola', 'Banco Comercial do Huambo (BCH)', 'Banco Angolano de Negócios e Comércio (BANC)', 'Outro'];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title>Conta de Saque — <?php echo APP_NAME; ?></title>

    <style>
    .upload-zone {
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        padding: 1.25rem;
        text-align: center;
        cursor: pointer;
        transition: all .2s;
        position: relative;
        background: #fafafa;
    }

    .upload-zone:hover,
    .upload-zone.drag-over {
        border-color: #FF0089;
        background: rgba(255, 0, 137, .04);
    }

    .upload-zone input[type=file] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }

    .upload-zone .bi-prev {
        max-height: 110px;
        border-radius: 8px;
        margin-top: .5rem;
        object-fit: cover;
    }

    .type-card-sel {
        cursor: pointer;
        border: 2px solid transparent;
        border-radius: 10px;
        padding: .5rem;
        transition: .2s;
    }

    .type-card-sel.active {
        border-color: #FF0089 !important;
    }

    .type-card-sel .card {
        transition: .2s;
    }

    .type-card-sel.active .card i {
        color: #FF0089 !important;
    }
    </style>
</head>

<body>

    <!-- ═══ NAVBAR ═══ -->
    <?php require_once __DIR__ . '/../include/sidebar.php'; ?>
    <!-- ════════════════════════════════════════════════════
     MAIN CONTENT
═════════════════════════════════════════════════════ -->
    <div class="container my-4">
        <?php /* ============================================
    BANNERS DE NOTIFICACAO DO PAINEL
    Estilo: inline CSS consistente com renderDashboardAlerts().
    Bootstrap alert nativo removido — um único sistema visual.
    Lógica de prioridade:
      Nível 1 (danger)  — bloqueia distribuição
      Nível 2 (warning) — importante, requer atenção
      Nível 3 (info)    — informativo / acção opcional
    ============================================ */ ?>

        <?php renderDashboardAlerts($user, $platform); ?>

        <?php
        // Cor map para helpers inline — idêntico ao renderDashboardAlerts()
        $alertColors = [
            'danger'  => ['bg' => 'rgba(239,68,68,.08)',  'border' => 'rgba(239,68,68,.25)',  'text' => '#ef4444'],
            'warning' => ['bg' => 'rgba(234,179,8,.08)',  'border' => 'rgba(234,179,8,.25)',  'text' => '#eab308'],
            'info'    => ['bg' => 'rgba(99,102,241,.08)', 'border' => 'rgba(99,102,241,.25)', 'text' => '#6366f1'],
        ];
        function wuAlert(string $type, string $icon, string $message, ?array $action = null, bool $dismiss = true, string $id = ''): void
        {
            global $alertColors;
            $c   = $alertColors[$type] ?? $alertColors['info'];
            $eid = $id ?: ('wuPanelAlert_' . md5($message));
            echo "<div id=\"{$eid}\" style=\"display:flex;align-items:flex-start;gap:10px;"
                . "background:{$c['bg']};border:1px solid {$c['border']};border-radius:12px;"
                . "padding:.75rem 1rem;font-size:.83rem;margin-bottom:.6rem;"
                . "transition:opacity .3s;\">";
            echo "<i class=\"bi {$icon}\" style=\"font-size:1rem;flex-shrink:0;margin-top:2px;color:{$c['text']};\"></i>";
            echo '<span class="wu-alert-msg">' . $message;
            if ($action) {
                echo " <a href=\"{$action['url']}\" style=\"color:{$c['text']};font-weight:700;"
                    . "text-decoration:underline;white-space:nowrap\">{$action['label']} &rarr;</a>";
            }
            echo '</span>';
            if ($dismiss) {
                echo "<button type=\"button\" class=\"wu-alert-dismiss\" aria-label=\"Fechar\""
                    . " onclick=\"(function(el){el.style.opacity='0';"
                    . "setTimeout(function(){el.style.display='none'},300)})(document.getElementById('{$eid}'))\">"
                    . "&times;</button>";
            }
            echo '</div>';
        }
        ?>

        <?php /* ── NÍVEL 1: Crítico — bloqueia distribuição ── */ ?>

        <?php if (!$email_verified): ?>
        <?php wuAlert(
                'danger',
                'bi-envelope-exclamation-fill',
                '<strong>Email não verificado.</strong> Verifica o teu e-mail para garantir o acesso à conta e receber notificações de pagamentos.',
                ['label' => 'Verificar agora', 'url' => APP_URL . '/' . APP_URL_PANEL . '/user/profile#perfil'],
                true,
                'banner-email'
            ); ?>
        <?php endif; ?>

        <?php if ($plan && !$plan_paid): ?>
        <?php wuAlert(
                'warning',
                'bi-clock-history',
                '<strong>Pagamento pendente — ' . htmlspecialchars($plan['name_plan']) . '.</strong> O plano foi seleccionado mas o pagamento ainda não foi confirmado. Os teus lançamentos estão pausados até confirmação.',
                ['label' => 'Finalizar pagamento', 'url' => APP_URL . '/' . APP_URL_PANEL . '/payment/pay'],
                true,
                'banner-plan-pending'
            ); ?>
        <?php elseif (!$plan): ?>
        <?php wuAlert(
                'danger',
                'bi-credit-card-fill',
                '<strong>Sem plano activo.</strong> Escolhe um plano para começar a distribuir a tua música para +150 plataformas.',
                ['label' => 'Ver planos', 'url' => APP_URL . '/' . APP_URL_PANEL . '/all-plans'],
                false,
                'banner-plan'
            ); ?>
        <?php endif; ?>

        <?php /* ── NÍVEL 2: Importante — perfil incompleto ── */ ?>

        <?php if ($plan_paid && !$has_artist): ?>
        <?php wuAlert(
                'info',
                'bi-person-plus-fill',
                '<strong>Cria o teu perfil de artista.</strong> Tens plano activo mas ainda não criaste um perfil. Precisas de um para poder lançar música.',
                ['label' => 'Criar agora', 'url' => APP_URL . '/' . APP_URL_PANEL . '/add-artist'],
                true,
                'banner-artist'
            ); ?>
        <?php endif; ?>

        <?php /* ── NÍVEL 3: Informativo — conta bancária ── */ ?>

        <?php if ($plan_paid && $has_artist && !$bank_account): ?>
        <?php wuAlert(
                'info',
                'bi-bank',
                '<strong>Conta bancária não registada.</strong> Para poder sacar os teus royalties, regista uma conta IBAN ou Multicaixa Express.',
                ['label' => 'Registar agora', 'url' => APP_URL . '/' . APP_URL_PANEL . '/withdraw'],
                true,
                'banner-bank'
            ); ?>
        <?php endif; ?>

        <?php /* ── NÍVEL 3: Conta bancária rejeitada ── */ ?>

        <?php
        $rejected_account = null;
        if ($plan_paid) {
            $rej_stmt = getDB()->prepare("SELECT type_account, reject_reason FROM _account WHERE id_users = ? AND status_account = 'rejected' LIMIT 1");
            $rej_stmt->execute([$id_users]);
            $rejected_account = $rej_stmt->fetch();
        }
        ?>
        <?php if ($rejected_account): ?>
        <?php
            $rej_msg = '<strong>Conta ' . htmlspecialchars($rejected_account['type_account']) . ' rejeitada.</strong>';
            if ($rejected_account['reject_reason']) {
                $rej_msg .= ' Motivo: <em>' . htmlspecialchars($rejected_account['reject_reason']) . '</em>.';
            }
            $rej_msg .= ' Actualiza os dados e submete novamente.';
            wuAlert(
                'danger',
                'bi-x-circle-fill',
                $rej_msg,
                ['label' => 'Corrigir agora', 'url' => APP_URL . '/' . APP_URL_PANEL . '/withdraw'],
                true,
                'banner-account-rejected'
            );
            ?>
        <?php endif; ?>
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <div class="page-header-compact">
                        <h1><i class="bi bi-wallet2 me-3"></i>Saques de Fundos</h1>
                        <p class="lead">Podes fazer o saque dos valores disponíveis na tua conta a partir desta sessão.
                            Preenche todos os campos para não ocorrer nenhum erro na operação.</p>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <?php if ($can_add_express || $can_add_iban): ?>
                    <button class="btn btn-pink" data-bs-toggle="modal" data-bs-target="#creatnewAccount">
                        <i class="bi bi-plus"></i> Criar conta
                    </button>
                    <?php else: ?>
                    <button class="btn btn-pink" disabled title="Já tens o máximo de 2 contas">
                        <i class="bi bi-plus"></i> Criar conta
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-light ms-2" onclick="window.location='overview'">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </button>
                </div>
            </div>
            <style>
            .page-header::before {
                content: '\F5A8';
            }
            </style>
        </div>

        <!-- Info -->
        <div class="w-25 mb-3">
            <div class="toastrDefaultInfo text-info" style="cursor:pointer" id="infoBtn">
                <i class="bi bi-info-square"></i> Info
            </div>
        </div>

        <!-- ─── Card Express ────────────────────────────── -->
        <div class="launch-card mb-4 mt-4">
            <div class="card">
                <div class="align-items-lg-center">
                    <div class="text-center">
                        <button data-bs-toggle="collapse" data-bs-target="#collapseOneAccount"
                            aria-expanded="<?php echo $acc_express ? 'true' : 'false'; ?>"
                            style="color:#ff0089;font-weight:bold;border-color:#ff0089"
                            class="btn btn-default w-100 d-flex justify-content-between align-items-center px-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-phone-fill fs-5"></i>
                                <h5 class="mb-0">Express</h5>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($acc_express): echo statusBadge($acc_express['status_account'] ?? 'pending');
                                endif; ?>
                                <i class="bi bi-chevron-down"></i>
                            </div>
                        </button>

                        <div id="collapseOneAccount"
                            class="accordion-collapse collapse <?php echo $acc_express ? 'show' : ''; ?>"
                            data-bs-parent="#accordionExample">
                            <div class="mt-3">
                                <?php if ($acc_express): ?>
                                <form action="finances/account_process" method="post"
                                    class="needs-validation mb-2 row text-start g-3" id="form-express" novalidate>
                                    <input type="hidden" name="csrf_token"
                                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id_account"
                                        value="<?php echo $acc_express['id_account']; ?>">
                                    <input type="hidden" name="account_type" value="express">

                                    <div class="mb-2 col-md-4">
                                        <label class="form-label">Nome completo</label>
                                        <input type="text" name="full_name" class="form-control" required minlength="4"
                                            value="<?php echo htmlspecialchars($acc_express['full_name_account']); ?>"
                                            placeholder="Nome do titular" />
                                    </div>
                                    <div class="mb-2 col-md-4">
                                        <label class="form-label">Número Express</label>
                                        <div class="input-group">
                                            <span class="input-group-text">+244</span>
                                            <input type="tel" name="express_number" class="form-control" required
                                                value="<?php echo preg_replace('/^\+?244/', '', $acc_express['tel_account'] ?? ''); ?>"
                                                placeholder="9XX XXX XXX" maxlength="9"
                                                oninput="this.value=this.value.replace(/\D/g,'')" />
                                        </div>
                                    </div>
                                    <div class="mb-2 col-md-4">
                                        <label class="form-label">Telefone alternativo</label>
                                        <input type="tel" name="tel_alt" class="form-control"
                                            value="<?php echo htmlspecialchars($acc_express['email_account'] ?? ''); ?>"
                                            placeholder="+244 9XX XXX XXX" />
                                    </div>

                                    <?php if (($acc_express['status_account'] ?? '') === 'rejected' && $acc_express['reject_reason']): ?>
                                    <div class="col-12">
                                        <div class="alert alert-danger py-2 small">
                                            <i class="bi bi-x-circle me-1"></i>
                                            <strong>Motivo de rejeição:</strong>
                                            <?php echo htmlspecialchars($acc_express['reject_reason']); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="mt-2 col-12 d-flex gap-2 justify-content-between">
                                        <button type="button" class="btn btn-danger btn-sm"
                                            onclick="confirmDelete(<?php echo $acc_express['id_account']; ?>, 'Express')">
                                            <i class="bi bi-trash me-1"></i>Eliminar conta Express
                                        </button>
                                        <button type="submit" class="btn btn-pink btn-sm">
                                            <i class="bi bi-save me-1"></i>Alterar
                                        </button>
                                    </div>
                                </form>
                                <?php else: ?>
                                <div class="text-center py-3 text-muted">
                                    <i class="bi bi-phone fs-2 mb-2 d-block" style="opacity:.4"></i>
                                    <p class="small mb-2">Sem conta Express registada.</p>
                                    <button class="btn btn-pink btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#creatnewAccount" onclick="preselectType('express')">
                                        <i class="bi bi-plus me-1"></i>Adicionar Express
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Card IBAN ────────────────────────────────── -->
        <div class="launch-card mb-4">
            <div class="card">
                <div class="align-items-lg-center">
                    <div class="text-center">
                        <button data-bs-toggle="collapse" data-bs-target="#collapseTwoAccount"
                            aria-expanded="<?php echo $acc_iban ? 'true' : 'false'; ?>"
                            style="color:#ff0089;font-weight:bold;border-color:#ff0089"
                            class="btn btn-default w-100 d-flex justify-content-between align-items-center px-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-bank fs-5"></i>
                                <h5 class="mb-0">IBAN</h5>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($acc_iban): echo statusBadge($acc_iban['status_account'] ?? 'pending');
                                endif; ?>
                                <i class="bi bi-chevron-down"></i>
                            </div>
                        </button>

                        <div id="collapseTwoAccount"
                            class="accordion-collapse collapse <?php echo $acc_iban ? 'show' : ''; ?>"
                            data-bs-parent="#accordionExample">
                            <div class="mt-3">
                                <?php if ($acc_iban): ?>
                                <form action="finances/account_process" method="post"
                                    class="needs-validation mb-2 row text-start g-3" id="form-iban" novalidate>
                                    <input type="hidden" name="csrf_token"
                                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id_account"
                                        value="<?php echo $acc_iban['id_account']; ?>">
                                    <input type="hidden" name="account_type" value="iban">

                                    <div class="mb-2 col-md-6">
                                        <label class="form-label">Nome completo do titular</label>
                                        <input type="text" name="full_name" class="form-control" required minlength="4"
                                            value="<?php echo htmlspecialchars($acc_iban['full_name_account']); ?>"
                                            placeholder="Nome exacto como consta no banco" />
                                    </div>
                                    <div class="mb-2 col-md-6">
                                        <label class="form-label">IBAN</label>
                                        <input type="text" name="iban_number" class="form-control font-monospace"
                                            required value="<?php echo htmlspecialchars($acc_iban['iban'] ?? ''); ?>"
                                            placeholder="AO06 XXXX XXXX XXXX XXXX XXXX X" maxlength="31"
                                            oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9 ]/g,'')" />
                                    </div>
                                    <div class="mb-2 col-md-6">
                                        <label class="form-label">Banco</label>
                                        <select class="form-select" name="bank_name">
                                            <option value="">Seleccionar banco</option>
                                            <?php foreach ($banks as $b): ?>
                                            <option value="<?php echo $b; ?>"
                                                <?php echo ($acc_iban['bank_name'] ?? '') === $b ? 'selected' : ''; ?>>
                                                <?php echo $b; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-2 col-md-6">
                                        <label class="form-label">E-mail associado (opcional)</label>
                                        <input type="email" name="email_account" class="form-control"
                                            value="<?php echo htmlspecialchars($acc_iban['email_account'] ?? ''); ?>"
                                            placeholder="email@banco.com" />
                                    </div>

                                    <?php if (($acc_iban['status_account'] ?? '') === 'rejected' && $acc_iban['reject_reason']): ?>
                                    <div class="col-12">
                                        <div class="alert alert-danger py-2 small">
                                            <i class="bi bi-x-circle me-1"></i>
                                            <strong>Motivo de rejeição:</strong>
                                            <?php echo htmlspecialchars($acc_iban['reject_reason']); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="mt-2 col-12 d-flex gap-2 justify-content-between">
                                        <button type="button" class="btn btn-danger btn-sm"
                                            onclick="confirmDelete(<?php echo $acc_iban['id_account']; ?>, 'IBAN')">
                                            <i class="bi bi-trash me-1"></i>Eliminar conta IBAN
                                        </button>
                                        <button type="submit" class="btn btn-pink btn-sm">
                                            <i class="bi bi-save me-1"></i>Alterar
                                        </button>
                                    </div>
                                </form>
                                <?php else: ?>
                                <div class="text-center py-3 text-muted">
                                    <i class="bi bi-bank fs-2 mb-2 d-block" style="opacity:.4"></i>
                                    <p class="small mb-2">Sem conta IBAN registada.</p>
                                    <button class="btn btn-pink btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#creatnewAccount" onclick="preselectType('iban')">
                                        <i class="bi bi-plus me-1"></i>Adicionar IBAN
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /container -->


    <!-- ════════════════════════════════════════════════════
     MODAL — CRIAR CONTA
═════════════════════════════════════════════════════ -->
    <div class="modal fade" id="creatnewAccount" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="creatnewAccountLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fs-5 text-dark" id="creatnewAccountLabel">Criar conta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="stats-description text-start">
                        Adicione uma nova conta para receber os seus pagamentos. Certifique-se de que os dados
                        informados
                        estejam correctos para todas as transferências futuras.
                    </p>

                    <!-- Selector de tipo -->
                    <div class="row g-3 mb-3" id="type-selector-modal">
                        <?php if ($can_add_express): ?>
                        <div class="col-6">
                            <div class="type-card-sel" id="tab-express-new" onclick="selectNewType('express')">
                                <div class="launch-card">
                                    <div class="card text-center py-3">
                                        <i class="bi bi-phone-fill fs-2 mb-1" style="color:#aaa"></i>
                                        <h6 class="mb-0 fw-bold">Express</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($can_add_iban): ?>
                        <div class="col-6">
                            <div class="type-card-sel" id="tab-iban-new" onclick="selectNewType('iban')">
                                <div class="launch-card">
                                    <div class="card text-center py-3">
                                        <i class="bi bi-bank fs-2 mb-1" style="color:#aaa"></i>
                                        <h6 class="mb-0 fw-bold">IBAN</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <ul class="list-group-item">
                        <div class="card-body">

                            <!-- Express section -->
                            <div class="launch-card mb-4" id="new-express-wrap" style="display:none">
                                <div class="card">
                                    <div class="align-items-lg-center">
                                        <div class="text-center">
                                            <div class="mt-3">
                                                <form method="post" action=""
                                                    class="needs-validation mb-2 row text-start g-3"
                                                    id="form-creat-express" novalidate>
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="action" value="create">
                                                    <input type="hidden" name="account_type" value="express">

                                                    <div class="mb-2 col-md-4">
                                                        <label class="form-label">Nome <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="full_name" class="form-control"
                                                            required minlength="4"
                                                            placeholder="Nome completo do titular"
                                                            value="<?php echo htmlspecialchars($first_name); ?>" />
                                                        <div class="invalid-feedback">Por favor insira o nome válido.
                                                        </div>
                                                    </div>
                                                    <div class="mb-2 col-md-4">
                                                        <label class="form-label">Número Express <span
                                                                class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">+244</span>
                                                            <input type="tel" name="express_number" class="form-control"
                                                                required placeholder="9XX XXX XXX" maxlength="9"
                                                                oninput="this.value=this.value.replace(/\D/g,'')" />
                                                        </div>
                                                        <div class="invalid-feedback">Número inválido.</div>
                                                    </div>
                                                    <div class="mb-2 col-md-4">
                                                        <label class="form-label">Telefone</label>
                                                        <input type="tel" name="tel_alt" autocomplete="tel"
                                                            class="form-control" placeholder="+244 9XX XXX XXX" />
                                                    </div>

                                                    <!-- BI Upload -->
                                                    <div class="col-12 mt-2">
                                                        <hr>
                                                        <p class="small text-muted mb-2"><i
                                                                class="bi bi-shield-check me-1"></i>Foto do BI
                                                            obrigatória para verificação</p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">BI — Frente <span
                                                                class="text-danger">*</span></label>
                                                        <div class="upload-zone" id="zone-front-express">
                                                            <input type="file" name="bi_front" id="bi_front_express"
                                                                accept="image/*"
                                                                onchange="previewBI(this,'prev-front-express','zone-front-express')">
                                                            <i class="bi bi-card-image fs-3 text-muted"></i>
                                                            <div class="small mt-1">Clica, arrasta ou</div>
                                                            <img id="prev-front-express" class="bi-prev d-none" alt="">
                                                        </div>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-secondary mt-1"
                                                            onclick="openCam('front-express')">
                                                            <i class="bi bi-camera me-1"></i>Tirar Foto
                                                        </button>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">BI — Verso <span
                                                                class="text-danger">*</span></label>
                                                        <div class="upload-zone" id="zone-back-express">
                                                            <input type="file" name="bi_back" id="bi_back_express"
                                                                accept="image/*"
                                                                onchange="previewBI(this,'prev-back-express','zone-back-express')">
                                                            <i class="bi bi-card-image fs-3 text-muted"></i>
                                                            <div class="small mt-1">Clica, arrasta ou</div>
                                                            <img id="prev-back-express" class="bi-prev d-none" alt="">
                                                        </div>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-secondary mt-1"
                                                            onclick="openCam('back-express')">
                                                            <i class="bi bi-camera me-1"></i>Tirar Foto
                                                        </button>
                                                    </div>

                                                    <div class="col-12 mt-2">
                                                        <label class="form-label">Confirmar com senha <span
                                                                class="text-danger">*</span></label>
                                                        <input type="password" name="confirm_password"
                                                            class="form-control" required
                                                            placeholder="Senha da tua conta Wasom Upfy"
                                                            autocomplete="current-password" />
                                                    </div>

                                                    <div class="mt-2 col-12">
                                                        <div id="err-express"
                                                            class="alert alert-danger d-none small py-2"></div>
                                                        <input type="submit" class="btn btn-pink form-control"
                                                            value="Salva" />
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr id="hr-divider" style="display:none" />

                            <!-- IBAN section -->
                            <div class="launch-card mb-4" id="new-iban-wrap" style="display:none">
                                <div class="card">
                                    <div class="align-items-lg-center">
                                        <div class="text-center">
                                            <div class="mt-3">
                                                <form method="post" action=""
                                                    class="needs-validation mb-2 row text-start g-3"
                                                    id="form-creat-iban" novalidate>
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="action" value="create">
                                                    <input type="hidden" name="account_type" value="iban">

                                                    <div class="mb-2 col-md-6">
                                                        <label class="form-label">Nome <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="full_name" class="form-control"
                                                            required minlength="4" placeholder="Nome exacto do titular"
                                                            value="<?php echo htmlspecialchars($first_name); ?>" />
                                                        <div class="invalid-feedback">Por favor insira o nome válido de
                                                            IBAN.</div>
                                                    </div>
                                                    <div class="mb-2 col-md-6">
                                                        <label class="form-label">IBAN <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" name="iban_number"
                                                            class="form-control font-monospace" required
                                                            placeholder="AO06 XXXX XXXX XXXX XXXX XXXX X" maxlength="34"
                                                            oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9 ]/g,'')" />
                                                        <div class="invalid-feedback">Por favor insira o IBAN válido.
                                                        </div>
                                                    </div>
                                                    <div class="mb-2 col-md-6">
                                                        <label class="form-label">Banco</label>
                                                        <select class="form-select" name="bank_name">
                                                            <option value="">Seleccionar banco</option>
                                                            <?php foreach ($banks as $b): ?>
                                                            <option value="<?php echo $b; ?>"><?php echo $b; ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="mb-2 col-md-6">
                                                        <label class="form-label">E-mail</label>
                                                        <input type="email" name="email_account" class="form-control"
                                                            placeholder="e-mail da conta bancária" />
                                                    </div>

                                                    <!-- BI Upload -->
                                                    <div class="col-12 mt-2">
                                                        <hr>
                                                        <p class="small text-muted mb-2"><i
                                                                class="bi bi-shield-check me-1"></i>Foto do BI
                                                            obrigatória para verificação</p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">BI — Frente <span
                                                                class="text-danger">*</span></label>
                                                        <div class="upload-zone" id="zone-front-iban">
                                                            <input type="file" name="bi_front" id="bi_front_iban"
                                                                accept="image/*"
                                                                onchange="previewBI(this,'prev-front-iban','zone-front-iban')">
                                                            <i class="bi bi-card-image fs-3 text-muted"></i>
                                                            <div class="small mt-1">Clica, arrasta ou</div>
                                                            <img id="prev-front-iban" class="bi-prev d-none" alt="">
                                                        </div>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-secondary mt-1"
                                                            onclick="openCam('front-iban')">
                                                            <i class="bi bi-camera me-1"></i>Tirar Foto
                                                        </button>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">BI — Verso <span
                                                                class="text-danger">*</span></label>
                                                        <div class="upload-zone" id="zone-back-iban">
                                                            <input type="file" name="bi_back" id="bi_back_iban"
                                                                accept="image/*"
                                                                onchange="previewBI(this,'prev-back-iban','zone-back-iban')">
                                                            <i class="bi bi-card-image fs-3 text-muted"></i>
                                                            <div class="small mt-1">Clica, arrasta ou</div>
                                                            <img id="prev-back-iban" class="bi-prev d-none" alt="">
                                                        </div>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-secondary mt-1"
                                                            onclick="openCam('back-iban')">
                                                            <i class="bi bi-camera me-1"></i>Tirar Foto
                                                        </button>
                                                    </div>

                                                    <div class="col-12 mt-2">
                                                        <label class="form-label">Confirmar com senha <span
                                                                class="text-danger">*</span></label>
                                                        <input type="password" name="confirm_password"
                                                            class="form-control" required
                                                            placeholder="Senha da tua conta Wasom Upfy"
                                                            autocomplete="current-password" />
                                                    </div>

                                                    <div class="mt-2 col-12">
                                                        <div id="err-iban" class="alert alert-danger d-none small py-2">
                                                        </div>
                                                        <input type="submit" class="btn btn-pink form-control"
                                                            value="Salva" />
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal criar conta fim -->

    <!-- Modal câmara -->
    <div class="modal fade" id="cameraModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cam-title">Tirar Foto do BI</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopCam()"></button>
                </div>
                <div class="modal-body text-center">
                    <video id="camVideo" autoplay playsinline
                        style="width:100%;border-radius:8px;background:#000;max-height:300px"></video>
                    <canvas id="camCanvas" class="d-none"></canvas>
                    <div class="mt-3">
                        <button type="button" class="btn btn-pink px-4" onclick="captureCam()">
                            <i class="bi bi-camera me-2"></i>Capturar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Scripts ────────────────────────────────────────── -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <script>
    // ── Config ─────────────────────────────────────────────
    toastr.options = {
        positionClass: 'toast-top-right',
        timeOut: 5000,
        progressBar: true,
        closeButton: true
    };

    const CSRF = '<?php echo $_SESSION['csrf_token']; ?>';

    // ── Info ────────────────────────────────────────────────
    $(function() {
        $("#infoBtn").click(function() {
            toastr.info(
                "Para visualizar os dados das suas carteiras basta clicar <strong>Express</strong> ou <strong>IBAN</strong>"
            );
        });
    });

    // ── Seleccionar tipo no modal de criação ────────────────
    let selectedNewType = '';

    function selectNewType(type) {
        selectedNewType = type;
        ['express', 'iban'].forEach(t => {
            const el = document.getElementById('tab-' + t + '-new');
            if (el) el.classList.toggle('active', t === type);
        });
        document.getElementById('new-express-wrap').style.display = (type === 'express') ? 'block' : 'none';
        document.getElementById('new-iban-wrap').style.display = (type === 'iban') ? 'block' : 'none';
        document.getElementById('hr-divider').style.display = 'none';
    }

    function preselectType(type) {
        // chamado antes do modal abrir — aplica após um tick
        setTimeout(() => selectNewType(type), 350);
    }

    // ── Preview BI ──────────────────────────────────────────
    function previewBI(input, previewId, zoneId) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById(previewId);
            img.src = e.target.result;
            img.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }

    // ── Camera ───────────────────────────────────────────────
    let camTarget = '',
        camStream = null,
        capturedBI = {};

    function openCam(target) {
        camTarget = target;
        const labels = {
            'front-express': 'Frente (Express)',
            'back-express': 'Verso (Express)',
            'front-iban': 'Frente (IBAN)',
            'back-iban': 'Verso (IBAN)'
        };
        document.getElementById('cam-title').textContent = 'Fotografar BI — ' + (labels[target] || target);
        navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment'
                },
                audio: false
            })
            .then(stream => {
                camStream = stream;
                document.getElementById('camVideo').srcObject = stream;
                new bootstrap.Modal(document.getElementById('cameraModal')).show();
            })
            .catch(() => toastr.warning('Câmara não disponível. Usa o upload.'));
    }

    function stopCam() {
        if (camStream) {
            camStream.getTracks().forEach(t => t.stop());
            camStream = null;
        }
    }

    function captureCam() {
        const v = document.getElementById('camVideo'),
            c = document.getElementById('camCanvas');
        c.width = v.videoWidth;
        c.height = v.videoHeight;
        c.getContext('2d').drawImage(v, 0, 0);
        c.toBlob(blob => {
            const file = new File([blob], `bi_${camTarget}_${Date.now()}.jpg`, {
                type: 'image/jpeg'
            });
            capturedBI[camTarget] = file;
            const previewId = 'prev-' + camTarget.replace('-', camTarget.includes('express') ? '-' : '-');
            const img = document.getElementById('prev-' + camTarget);
            if (img) {
                img.src = URL.createObjectURL(blob);
                img.classList.remove('d-none');
            }
            const parts = camTarget.split('-'); // e.g. ['front','express'] or ['back','iban']
            const side = parts[0],
                actype = parts[1];
            const inputId = `bi_${side}_${actype}`;
            const inputEl = document.getElementById(inputId);
            if (inputEl) {
                const dt = new DataTransfer();
                dt.items.add(file);
                inputEl.files = dt.files;
            }
            stopCam();
            bootstrap.Modal.getInstance(document.getElementById('cameraModal')).hide();
            toastr.success('Foto capturada!');
        }, 'image/jpeg', 0.9);
    }

    // ── Submeter criar conta (por formulário Ajax) ───────────
    function handleCreateSubmit(form, errId, type) {
        const errEl = document.getElementById(errId);
        errEl.classList.add('d-none');

        // Validações front
        const name = form.querySelector('[name=full_name]').value.trim();
        if (!name || name.split(' ').filter(Boolean).length < 2) {
            errEl.textContent = 'Insere o nome completo (nome e apelido).';
            errEl.classList.remove('d-none');
            return;
        }
        const pwd = form.querySelector('[name=confirm_password]').value;
        if (!pwd) {
            errEl.textContent = 'Insere a senha para confirmar.';
            errEl.classList.remove('d-none');
            return;
        }

        if (type === 'iban') {
            const iban = form.querySelector('[name=iban_number]').value.replace(/\s/g, '');
            if (!iban.startsWith('AO') || iban.length < 20) {
                errEl.textContent = 'IBAN inválido (começa AO, mín. 20 car.).';
                errEl.classList.remove('d-none');
                return;
            }
        } else {
            const num = form.querySelector('[name=express_number]').value.trim();
            if (!/^9\d{8}$/.test(num)) {
                errEl.textContent = 'Número Express inválido (9 dígitos, começa por 9).';
                errEl.classList.remove('d-none');
                return;
            }
        }

        const bi_front = form.querySelector('[name=bi_front]').files[0] || capturedBI[`front-${type}`];
        const bi_back = form.querySelector('[name=bi_back]').files[0] || capturedBI[`back-${type}`];
        if (!bi_front) {
            errEl.textContent = 'Faz o upload da frente do BI.';
            errEl.classList.remove('d-none');
            return;
        }
        if (!bi_back) {
            errEl.textContent = 'Faz o upload do verso do BI.';
            errEl.classList.remove('d-none');
            return;
        }

        const btn = form.querySelector('[type=submit]');
        btn.disabled = true;
        btn.value = 'A enviar...';

        const fd = new FormData(form);
        if (capturedBI[`front-${type}`] && !form.querySelector('[name=bi_front]').files[0]) fd.set('bi_front',
            capturedBI[`front-${type}`]);
        if (capturedBI[`back-${type}`] && !form.querySelector('[name=bi_back]').files[0]) fd.set('bi_back', capturedBI[
            `back-${type}`]);

        fetch('finances/account_process', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('creatnewAccount')).hide();
                    toastr.success('Conta submetida! Verificação em até 48 horas.', 'Conta criada');
                    setTimeout(() => location.reload(), 2200);
                } else {
                    errEl.textContent = data.message || 'Erro. Tenta novamente.';
                    errEl.classList.remove('d-none');
                    btn.disabled = false;
                    btn.value = 'Salva';
                }
            })
            .catch(() => {
                errEl.textContent = 'Erro de ligação.';
                errEl.classList.remove('d-none');
                btn.disabled = false;
                btn.value = 'Salva';
            });
    }

    document.getElementById('form-creat-express').addEventListener('submit', function(e) {
        e.preventDefault();
        handleCreateSubmit(this, 'err-express', 'express');
    });
    document.getElementById('form-creat-iban').addEventListener('submit', function(e) {
        e.preventDefault();
        handleCreateSubmit(this, 'err-iban', 'iban');
    });

    // ── Submeter edição (Ajax) ───────────────────────────────
    ['form-express', 'form-iban'].forEach(fid => {
        const f = document.getElementById(fid);
        if (!f) return;
        f.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('[type=submit]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>A guardar...';
            fetch('finances/account_process', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(r => r.json())
                .then(d => {
                    if (d.ok) toastr.success('Dados actualizados. Aguarda re-verificação.',
                        'Guardado');
                    else toastr.error(d.message || 'Erro ao guardar.', 'Erro');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-save me-1"></i>Alterar';
                })
                .catch(() => {
                    toastr.error('Erro de ligação.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-save me-1"></i>Alterar';
                });
        });
    });

    // ── Eliminar conta — SweetAlert2 com input de senha ──────
    function confirmDelete(accountId, type) {
        Swal.fire({
            title: `Eliminar conta ${type}?`,
            html: '<p class="text-muted" style="font-size:.9rem">Esta acção é irreversível. Introduz a tua senha para confirmar.</p>',
            input: 'password',
            inputPlaceholder: 'A tua senha Wasom Upfy',
            inputAttributes: {
                autocomplete: 'current-password'
            },
            icon: 'warning',
            iconColor: '#dc3545',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-trash"></i> Sim, eliminar',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: (password) => {
                if (!password) {
                    Swal.showValidationMessage('Insere a tua senha para confirmar.');
                    return false;
                }
                const body = new URLSearchParams({
                    action: 'delete',
                    id_account: accountId,
                    confirm_password: password,
                    csrf_token: CSRF
                });
                return fetch('finances/account_process', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: body.toString()
                }).then(r => r.json()).then(data => {
                    if (!data.ok) {
                        Swal.showValidationMessage(data.message || 'Erro ao eliminar.');
                        return false;
                    }
                    return data;
                }).catch(() => {
                    Swal.showValidationMessage('Erro de ligação.');
                    return false;
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed) {
                Swal.fire({
                        title: 'Conta eliminada',
                        text: `A conta ${type} foi removida com sucesso.`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    })
                    .then(() => location.reload());
            }
        });
    }

    // ── Offline ──────────────────────────────────────────────
    window.addEventListener('offline', () => new bootstrap.Toast(document.getElementById('connectionToast')).show());

    function tryReconnect() {
        if (navigator.onLine) location.reload();
        else toastr.warning('Ainda sem ligação à internet.');
    }

    // ── Badge de notificações — polling 60s ──────────────────
    (function() {
        function refreshBadge() {
            fetch('/ajax/notifications_api?action=count', {
                    credentials: 'same-origin'
                })
                .then(r => r.json())
                .then(data => {
                    var b = document.getElementById('navNotifBadge');
                    if (!b) return;
                    var n = parseInt(data.unread || 0);
                    b.textContent = n > 99 ? '99+' : n;
                    b.style.display = n > 0 ? '' : 'none';
                }).catch(function() {});
        }
        setTimeout(function() {
            refreshBadge();
            setInterval(refreshBadge, 60000);
        }, 30000);
    })();
    </script>

</body>

</html>