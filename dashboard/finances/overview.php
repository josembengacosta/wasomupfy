<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Finanças: Visão Geral
// Arquivo: dashboard/finances/overview.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
require_once __DIR__ . '/../include/platform.php';
startSecureSession();
checkRememberMe();
requireLogin();
$platform = checkDashboardStatus();
$user     = checkUserAccess((int)$_SESSION['id_users']);

$id_users         = (int)$user['id_users'];
$first_name       = htmlspecialchars($user['first_name']);
$user_name        = htmlspecialchars($user['user_name'] ?? '');
$user_photo       = $user['photo_user'] ?? null;
$name_artist_band = htmlspecialchars($user['name_artist_band'] ?? 'Cria Perfil Artístico');
$notif_count      = getUnreadNotifCount($id_users);

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];
$user     = getUserById($id_users);
if (!$user) {
    session_destroy();
    redirect(APP_URL  . '/' . 'login', ['error' => 'csrf']);
}
// Sessão — para o modal de logout
$ls = getDB()->prepare('SELECT last_login_at, last_login_ip FROM _users_security WHERE id_users = ?');
$ls->execute([$id_users]);
$sec = $ls->fetch();

$sess_stmt = getDB()->prepare("
    SELECT ip_address, user_agent, country, city, creat_session, last_activity
    FROM _users_sessions WHERE id_users = ? AND is_active = 1
    ORDER BY last_activity DESC LIMIT 1
");
$sess_stmt->execute([$id_users]);
$current_session = $sess_stmt->fetch();

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
$ua_raw         = $current_session['user_agent'] ?? '';
$browser        = 'Desconhecido';
if (str_contains($ua_raw, 'Edg'))         $browser = 'Microsoft Edge';
elseif (str_contains($ua_raw, 'Chrome'))  $browser = 'Google Chrome';
elseif (str_contains($ua_raw, 'Firefox')) $browser = 'Mozilla Firefox';
elseif (str_contains($ua_raw, 'Safari'))  $browser = 'Safari';
elseif (str_contains($ua_raw, 'Opera'))   $browser = 'Opera';
$sess_location = trim(($current_session['city'] ?? '') . ', ' . ($current_session['country'] ?? ''), ', ') ?: 'Desconhecida';
$sess_ip       = $current_session['ip_address'] ?? ($sec['last_login_ip'] ?? '—');


// ── Dados básicos ─────────────────────────────
$first_name     = htmlspecialchars($user['first_name']);
$email_verified = (bool)$user['email_verified'];
$plan_selected  = $user['plan_selected'];

// ── Plano ──────────────────────────────────────
$plan      = null;
$plan_paid = ($user['status_user'] === 'active' && !empty($user['plan_activated_at']));
if ($plan_selected) {
    $ps = $db->prepare('SELECT * FROM _plans WHERE id_plan = ?');
    $ps->execute([$plan_selected]);
    $plan = $ps->fetch();
}
$plan_name = $plan ? htmlspecialchars($plan['name_plan']) : 'Sem plano';


// Tem artistas?
$as = getDB()->prepare('SELECT COUNT(*) as total FROM _artist WHERE id_users = ?');
$as->execute([$id_users]);
$has_artist = (int)($as->fetch()['total'] ?? 0) > 0;

// Dados de sessão e segurança
$ls = getDB()->prepare('SELECT last_login_at, last_login_ip FROM _users_security WHERE id_users = ?');
$ls->execute([$id_users]);
$sec = $ls->fetch();
$days_inactive = 0;
if ($sec && $sec['last_login_at']) {
    $days_inactive = (int)floor((time() - strtotime($sec['last_login_at'])) / 86400);
}

// Sessão activa actual
$sess_stmt = getDB()->prepare("
    SELECT ip_address, user_agent, country, city, creat_session, last_activity
    FROM _users_sessions
    WHERE id_users = ? AND is_active = 1
    ORDER BY last_activity DESC LIMIT 1
");
$sess_stmt->execute([$id_users]);
$current_session = $sess_stmt->fetch();

// Calcular tempo de sessão activa
$session_duration_str = '—';
if ($current_session && $current_session['creat_session']) {
    $secs = time() - strtotime($current_session['creat_session']);
    if ($secs < 60) $session_duration_str = $secs . 's';
    elseif ($secs < 3600) $session_duration_str = floor($secs / 60) . 'min';
    elseif ($secs < 86400) $session_duration_str = floor($secs / 3600) . 'h ' . floor(($secs % 3600) / 60) . 'min';
    else $session_duration_str = floor($secs / 86400) . 'd ' . floor(($secs % 86400) / 3600) . 'h';
}

// Conta desde quando
$member_since = $user['creat_user'] ? date('d/m/Y', strtotime($user['creat_user'])) : '—';
$last_login_str = ($sec && $sec['last_login_at'])
    ? date('d/m/Y H:i', strtotime($sec['last_login_at']))
    : '—';

// Browser simplificado a partir do user_agent
$ua_raw    = $current_session['user_agent'] ?? '';
$browser   = 'Navegador desconhecido';
if (str_contains($ua_raw, 'Edg'))     $browser = 'Microsoft Edge';
elseif (str_contains($ua_raw, 'Chrome'))  $browser = 'Google Chrome';
elseif (str_contains($ua_raw, 'Firefox')) $browser = 'Mozilla Firefox';
elseif (str_contains($ua_raw, 'Safari'))  $browser = 'Safari';
elseif (str_contains($ua_raw, 'Opera'))   $browser = 'Opera';

$sess_location = trim(($current_session['city'] ?? '') . ', ' . ($current_session['country'] ?? ''), ', ');
if (!$sess_location) $sess_location = 'Localização desconhecida';
$sess_ip = $current_session['ip_address'] ?? ($sec['last_login_ip'] ?? '—');

// ── Wallet ──────────────────────────────────────
$w = $db->prepare('SELECT * FROM _wallet WHERE id_users = ?');
$w->execute([$id_users]);
$wallet = $w->fetch() ?: ['balance_aoa' => 0, 'balance_usd' => 0, 'total_earned' => 0, 'total_withdrawn' => 0];
$balance_aoa  = (float)$wallet['balance_aoa'];
$balance_usd  = (float)$wallet['balance_usd'];
$total_earned = (float)$wallet['total_earned'];
$total_withdrawn = (float)$wallet['total_withdrawn'];

// ── Conta bancária padrão verificada ──────────
$bq = $db->prepare("SELECT * FROM _account WHERE id_users = ? AND status_account = 'verified' AND is_default = 1 LIMIT 1");
$bq->execute([$id_users]);
$bank_account = $bq->fetch() ?: null;

// Todas as contas verificadas (para o modal de saque)
$allbanks_q = $db->prepare("SELECT * FROM _account WHERE id_users = ? AND status_account = 'verified' ORDER BY is_default DESC");
$allbanks_q->execute([$id_users]);
$all_banks = $allbanks_q->fetchAll(PDO::FETCH_ASSOC);

// ── Condição de saque ──────────────────────────
$min_withdrawal = 10000.00;
$can_withdraw   = $plan_paid && $bank_account && ($balance_aoa >= $min_withdrawal);

// ── Royalties reais ───────────────────────────
$royalties_q = $db->prepare("
    SELECT r.id_royalty, r.year_royalty, r.month_royalty,
           r.gross_revenue, r.net_royalty, r.net_royalty_aoa,
           r.currency, r.status_royalty, r.paid_at,
           t.title_track, a.title_album
    FROM _royalty r
    JOIN _track t ON t.id_track = r.id_track
    JOIN _album a ON a.id_album = t.id_album
    WHERE r.id_users = ?
    ORDER BY r.year_royalty DESC, r.month_royalty DESC
    LIMIT 50
");
$royalties_q->execute([$id_users]);
$royalties = $royalties_q->fetchAll(PDO::FETCH_ASSOC);

// ── Saques reais ──────────────────────────────
$withdrawals_q = $db->prepare("
    SELECT w.id_withdrawal, w.amount_requested, w.amount_net,
           w.currency, w.status_withdrawal, w.creat_withdrawal,
           w.paid_at, ac.type_account, ac.full_name_account
    FROM _withdrawal w
    LEFT JOIN _account ac ON ac.id_account = w.id_account
    WHERE w.id_users = ?
    ORDER BY w.creat_withdrawal DESC
    LIMIT 50
");
$withdrawals_q->execute([$id_users]);
$withdrawals = $withdrawals_q->fetchAll(PDO::FETCH_ASSOC);

// ── Saque pendente (bloqueia novo pedido) ──────
$pending_q = $db->prepare("SELECT COUNT(*) FROM _withdrawal WHERE id_users = ? AND status_withdrawal IN ('pending','processing')");
$pending_q->execute([$id_users]);
$has_pending_withdrawal = (int)$pending_q->fetchColumn() > 0;

// ── Helpers ────────────────────────────────────
$months_pt = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

$royalty_status = [
    'pending'    => ['label' => 'Pendente',    'class' => 'bg-warning text-dark'],
    'processing' => ['label' => 'A processar', 'class' => 'bg-primary text-white'],
    'paid'       => ['label' => 'Pago',        'class' => 'bg-success text-white'],
    'cancelled'  => ['label' => 'Cancelado',   'class' => 'bg-secondary text-white'],
];
$withdrawal_status = [
    'pending'    => ['label' => 'Pendente',    'class' => 'bg-warning text-dark'],
    'processing' => ['label' => 'A processar', 'class' => 'bg-primary text-white'],
    'approved'   => ['label' => 'Aprovado',    'class' => 'bg-success text-white'],
    'rejected'   => ['label' => 'Recusado',    'class' => 'bg-danger text-white'],
    'cancelled'  => ['label' => 'Cancelado',   'class' => 'bg-secondary text-white'],
];

$user_artist_name = htmlspecialchars($user['name_artist_band'] ?? $user['first_name']);
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title>Finanças — <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
</head>

<body>

    <?php include __DIR__ . '/_modal_withdrawal.php'; ?>
    <!-- ═══ NAVBAR ═══ -->
    <?php require_once __DIR__ . '/../include/sidebar.php'; ?>
    <!-- Main Content -->
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
                '<strong>Cria o teu perfil artístico.</strong> Tens plano activo mas ainda não criaste um perfil artístico. Precisas de um para poder lançar música.',
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


        <!-- Cabeçalho -->
        <div class="page-header">
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <div class="page-header-compact">
                        <h1><i class="bi bi-currency-dollar me-3"></i>Finanças</h1>
                        <p class="lead">Acompanha o histórico de receitas, saques realizados e saldo disponível.</p>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="report" class="btn btn-pink">
                        <i class="bi bi-file-text me-1"></i> Relatórios
                    </a>
                </div>
            </div>
        </div>

        <!-- Balance Card -->
        <div class="balance-card mb-4">
            <div class="card">
                <h6 style="color: #ff0089">Saldo disponível para saque</h6>
                <h2 id="balance"><?php echo number_format($balance_aoa, 2, ",", "."); ?> AOA</h2>

                <?php if (!$plan_paid): ?>
                    <p class="text-warning small mb-2">
                        <i class="bi bi-lock-fill me-1"></i>
                        Activa o teu plano para começar a receber royalties.
                    </p>
                <?php elseif (!$bank_account): ?>
                    <p class="text-muted small mb-2">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        Para sacar, primeiro regista uma conta bancária.
                    </p>
                <?php elseif ($balance_aoa < $min_withdrawal): ?>
                    <p class="text-muted small mb-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Mínimo para saque: <strong>10.000 Kz</strong>
                        (tens <?php echo number_format($balance_aoa, 0, ',', '.'); ?> Kz).
                    </p>
                <?php else: ?>
                    <p class="small mb-2" style="color:#ccc">
                        Os teus rendimentos estão prontos. Solicita o saque agora.
                    </p>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-outline-pink disabled" onclick="setMoeda('AOA')" id="btnAOA"
                        data-bs-toggle="tooltip" data-bs-placement="top" title="Ver em Kwanza">
                        <i class="bi bi-currency-exchange"></i> AOA
                    </button>

                    <?php if (!$bank_account): ?>
                        <!-- Sem conta: leva para criar conta bancária -->
                        <a href="withdraw" class="btn btn-pink">
                            <i class="bi bi-bank me-1"></i> Criar Conta Bancária
                        </a>
                    <?php elseif ($can_withdraw): ?>
                        <!-- Pode sacar -->
                        <button class="btn btn-pink" data-bs-toggle="modal" data-bs-target="#sake">
                            <i class="bi bi-wallet2 me-2"></i> Sacar
                        </button>
                    <?php else: ?>
                        <!-- Saldo insuficiente ou plano inactivo -->
                        <button class="btn btn-pink" disabled title="Saldo mínimo de 10.000 Kz necessário">
                            <i class="bi bi-wallet2 me-2"></i> Sacar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Atalhos -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="launch-card">
                    <div class="card">
                        <div class="d-flex align-items-center">
                            <div class="m-auto w-100 text-center">
                                <a href="withdraw" class="btn btn-default w-100" style="color:#ff0089;font-weight:bold">
                                    <h5 class="mb-0"><i class="bi bi-credit-card-fill me-3"></i>Contas de Saque</h5>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="launch-card">
                    <div class="card">
                        <div class="d-flex align-items-center">
                            <div class="m-auto w-100 text-center">
                                <a href="transactions" class="btn btn-default w-100"
                                    style="color:#ff0089;font-weight:bold">
                                    <h5 class="mb-0"><i class="bi bi-cash-coin me-3"></i>Divisão de Royalties</h5>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela Royalties -->
        <div class="table-card mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-music-note-beamed me-2 text-pink"></i>Histórico de Royalties</h6>
                    <span class="badge bg-secondary"><?php echo count($royalties); ?> registos</span>
                </div>
                <div class="table-responsive">
                    <?php if (empty($royalties)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-music-note-list fs-1 d-block mb-2 opacity-25"></i>
                            <div class="small">Nenhum royalty registado ainda.</div>
                            <div class="small">Os royalties aparecem aqui após a aprovação dos teus lançamentos.</div>
                        </div>
                    <?php else: ?>
                        <table id="royaltiesTable" class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Faixa / Álbum</th>
                                    <th>Período</th>
                                    <th>Bruto (USD)</th>
                                    <th>Líquido (USD)</th>
                                    <th>Líquido (AOA)</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($royalties as $r):
                                    $rs  = $royalty_status[$r['status_royalty']] ?? $royalty_status['pending'];
                                    $per = $months_pt[(int)$r['month_royalty']] . '/' . $r['year_royalty'];
                                ?>
                                    <tr>
                                        <td class="text-muted small"><?php echo (int)$r['id_royalty']; ?></td>
                                        <td>
                                            <div class="fw-semibold small"><?php echo htmlspecialchars($r['title_track']); ?>
                                            </div>
                                            <div class="text-muted" style="font-size:.72rem">
                                                <?php echo htmlspecialchars($r['title_album']); ?></div>
                                        </td>
                                        <td class="small"><?php echo $per; ?></td>
                                        <td class="small fw-semibold">
                                            $<?php echo number_format((float)$r['gross_revenue'], 4); ?></td>
                                        <td class="small fw-semibold text-success">
                                            $<?php echo number_format((float)$r['net_royalty'], 4); ?></td>
                                        <td class="small fw-semibold">
                                            <?php echo $r['net_royalty_aoa'] ? number_format((float)$r['net_royalty_aoa'], 2, ',', '.') . ' Kz' : '—'; ?>
                                        </td>
                                        <td><span class="badge <?php echo $rs['class']; ?>"><?php echo $rs['label']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tabela Saques -->
        <div class="table-card mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-cash-stack me-2 text-pink"></i>Histórico de Saques</h6>
                    <span class="badge bg-secondary"><?php echo count($withdrawals); ?> registos</span>
                </div>
                <div class="table-responsive">
                    <?php if (empty($withdrawals)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-bank fs-1 d-block mb-2 opacity-25"></i>
                            <div class="small">Nenhum saque realizado ainda.</div>
                        </div>
                    <?php else: ?>
                        <table id="withdrawalsTable" class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Data</th>
                                    <th>Conta destino</th>
                                    <th>Valor pedido</th>
                                    <th>Valor líquido</th>
                                    <th>Moeda</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($withdrawals as $w):
                                    $ws = $withdrawal_status[$w['status_withdrawal']] ?? $withdrawal_status['pending'];
                                ?>
                                    <tr>
                                        <td class="text-muted small"><?php echo (int)$w['id_withdrawal']; ?></td>
                                        <td class="small"><?php echo date('d/m/Y', strtotime($w['creat_withdrawal'])); ?></td>
                                        <td class="small">
                                            <div class="fw-semibold">
                                                <?php echo htmlspecialchars($w['full_name_account'] ?? '—'); ?></div>
                                            <div class="text-muted" style="font-size:.7rem">
                                                <?php echo htmlspecialchars($w['type_account'] ?? ''); ?></div>
                                        </td>
                                        <td class="small fw-semibold">
                                            <?php echo number_format((float)$w['amount_requested'], 2, ',', '.'); ?></td>
                                        <td class="small fw-semibold text-success">
                                            <?php echo number_format((float)$w['amount_net'], 2, ',', '.'); ?></td>
                                        <td class="small"><?php echo htmlspecialchars($w['currency']); ?></td>
                                        <td><span class="badge <?php echo $ws['class']; ?>"><?php echo $ws['label']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /container -->

    <!-- Modal para saque contas -->
    <div class="modal fade" id="sake" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="sakeLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark" id="sakeLabel">
                        <i class="bi bi-wallet2 me-2 text-pink"></i>Solicitar Saque
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($can_withdraw && $bank_account): ?>

                        <!-- ─ Estado: pode sacar ─ -->
                        <p class="text-muted small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            O valor é processado pela equipa em até 48 horas. Receberás uma notificação por e-mail.
                        </p>
                        <form method="post" action="finances/withdrawal_process" class="needs-validation row g-3" novalidate
                            id="withdrawal-form">
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <!-- Valor (preenchido automaticamente) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Valor de Saque <span
                                        class="text-muted">(AOA)</span></label>
                                <input type="text" class="form-control" readonly
                                    value="<?php echo number_format($balance_aoa, 2, ',', '.'); ?>">
                                <div class="form-text">Valor total disponível</div>
                            </div>

                            <!-- Conta destino -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Conta Destino</label>
                                <div class="form-control bg-light d-flex align-items-center gap-2"
                                    style="height:auto;padding:.6rem .9rem">
                                    <?php if (in_array($bank_account['type_account'], ['IBAN', 'Multicaixa'])): ?>
                                        <i class="bi bi-bank text-primary"></i>
                                        <div>
                                            <div class="fw-semibold small">
                                                <?php echo htmlspecialchars($bank_account['full_name_account']); ?></div>
                                            <div class="text-muted" style="font-size:.75rem">IBAN ·
                                                <?php echo $bank_account['iban'] ? substr(htmlspecialchars($bank_account['iban']), -8) : 'N/A'; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <i class="bi bi-phone text-success"></i>
                                        <div>
                                            <div class="fw-semibold small">
                                                <?php echo htmlspecialchars($bank_account['full_name_account']); ?></div>
                                            <div class="text-muted" style="font-size:.75rem">Express ·
                                                <?php echo htmlspecialchars($bank_account['tel_account'] ?? 'N/A'); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Senha de confirmação -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Confirmar com a tua senha <span
                                        class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" required
                                    placeholder="Senha da tua conta Wasom Upfy" autocomplete="current-password">
                                <div class="invalid-feedback">Insere a tua senha para confirmar o saque.</div>
                            </div>

                            <div class="col-12">
                                <div class="alert alert-warning py-2 small mb-0">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Ao confirmar, autorizes o envio de
                                    <strong><?php echo number_format($balance_aoa, 2, ',', '.'); ?> AOA</strong>
                                    para a conta registada. Esta operação não pode ser revertida.
                                </div>
                            </div>

                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-pink">
                                    <i class="bi bi-send me-2"></i>Confirmar Saque
                                </button>
                            </div>
                        </form>

                    <?php else: ?>
                        <!-- ─ Estado: não pode sacar ─ -->
                        <div class="text-center py-4">
                            <i class="bi bi-lock fs-1 text-muted mb-3 d-block"></i>
                            <?php if (!$plan_paid): ?>
                                <h6>Plano não activo</h6>
                                <p class="text-muted small">Activa o teu plano para começar a receber royalties e fazer
                                    saques.</p>
                                <a href="payment" class="btn btn-pink btn-sm">Activar Plano</a>
                            <?php elseif (!$bank_account): ?>
                                <h6>Sem conta bancária registada</h6>
                                <p class="text-muted small">Para sacar os teus royalties, primeiro regista uma conta
                                    bancária (IBAN ou Multicaixa Express).</p>
                                <a href="withdraw" class="btn btn-pink btn-sm">
                                    <i class="bi bi-bank me-1"></i>Registar Conta Bancária
                                </a>
                            <?php else: ?>
                                <h6>Saldo insuficiente</h6>
                                <p class="text-muted small">O mínimo para saque é <strong>10.000 Kz</strong>. O teu saldo
                                    actual é <strong><?php echo number_format($balance_aoa, 2, ',', '.'); ?> AOA</strong>.
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
    <!-- Modal para saque contas fim -->

    <!-- ═══ JS ═══ -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <script>
        // Tooltips
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

        // DataTables (apenas se a tabela tiver linhas)
        $(document).ready(function() {
            <?php if (!empty($royalties)): ?>
                $('#royaltiesTable').DataTable({
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    lengthChange: false,
                    pageLength: 10,
                    order: [
                        [2, 'desc']
                    ],
                    language: {
                        search: 'Pesquisar royalties:',
                        info: 'A mostrar _START_ a _END_ de _TOTAL_',
                        paginate: {
                            next: 'Próximo',
                            previous: 'Anterior'
                        },
                        emptyTable: 'Sem royalties registados.'
                    }
                });
            <?php endif; ?>

            <?php if (!empty($withdrawals)): ?>
                $('#withdrawalsTable').DataTable({
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    lengthChange: false,
                    pageLength: 10,
                    order: [
                        [1, 'desc']
                    ],
                    language: {
                        search: 'Pesquisar saques:',
                        info: 'A mostrar _START_ a _END_ de _TOTAL_',
                        paginate: {
                            next: 'Próximo',
                            previous: 'Anterior'
                        },
                        emptyTable: 'Sem saques registados.'
                    }
                });
            <?php endif; ?>
        });

        // Validação form saque
        <?php if ($can_withdraw && !$has_pending_withdrawal): ?>
            document.getElementById('withdrawal-form')?.addEventListener('submit', function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                }
                this.classList.add('was-validated');
            });
        <?php endif; ?>
    </script>

    <script>
        (function() {
            function refreshBadge() {
                fetch('../ajax/notifications_api.php?action=count', {
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