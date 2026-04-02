<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Relatórios Financeiros
// Arquivo: dashboard/analytics/report.php
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

// ── Relatórios agrupados por mês/ano ──────────
// Campo report_file na tabela _royalty guarda o PDF gerado pela equipa
$reports_q = $db->prepare("
    SELECT
        r.year_royalty,
        r.month_royalty,
        SUM(r.net_royalty_aoa)  AS total_aoa,
        SUM(r.net_royalty)      AS total_usd,
        SUM(r.gross_revenue)    AS total_gross,
        COUNT(r.id_royalty)     AS num_tracks,
        MAX(r.status_royalty)   AS status_royalty,
        MAX(r.report_file)      AS report_file,
        MAX(r.paid_at)          AS paid_at
    FROM _royalty r
    WHERE r.id_users = ?
    GROUP BY r.year_royalty, r.month_royalty
    ORDER BY r.year_royalty DESC, r.month_royalty DESC
");
$reports_q->execute([$id_users]);
$reports = $reports_q->fetchAll(PDO::FETCH_ASSOC);

// ── Totais pagos ──────────────────────────────
$totals_q = $db->prepare("
    SELECT
        COALESCE(SUM(net_royalty_aoa), 0) AS grand_aoa,
        COALESCE(SUM(net_royalty), 0)     AS grand_usd
    FROM _royalty WHERE id_users = ? AND status_royalty = 'paid'
");
$totals_q->execute([$id_users]);
$totals = $totals_q->fetch();

// ── Helpers ────────────────────────────────────
$months_pt = [
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Março',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro'
];
$status_map = [
    'pending'    => ['label' => 'Pendente',    'class' => 'bg-warning text-dark'],
    'processing' => ['label' => 'A processar', 'class' => 'bg-primary text-white'],
    'paid'       => ['label' => 'Pago',        'class' => 'bg-success text-white'],
    'cancelled'  => ['label' => 'Cancelado',   'class' => 'bg-secondary text-white'],
];
$base_url    = rtrim(APP_URL, '/');
$reports_url = $base_url . '/';
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title>Relatórios — <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
</head>

<body>

    <!-- ═══ NAVBAR ═══ -->
    <?php require_once __DIR__ . '/../include/sidebar.php'; ?>

    <!-- ═══ MAIN ═══ -->
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
                ['label' => 'Ver planos', 'url' => 'all-plans'],
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
        <!-- Cabeçalho -->
        <div class="page-header">
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <div class="page-header-compact">
                        <h1><i class="bi bi-file-earmark-text-fill me-3"></i>Relatórios Financeiros</h1>
                        <p class="lead">
                            Todos os relatórios mensais dos conteúdos distribuídos por esta conta estão disponíveis
                            aqui.
                            Faz o download para análise detalhada no teu dispositivo.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="overview" class="btn btn-light">
                        <i class="bi bi-arrow-left-circle me-2"></i>Voltar às Finanças
                    </a>
                </div>
            </div>

            <style>
            .page-header::before {
                content: '\F45D';
                /* bi-file-earmark-text-fill */
            }
            </style>
        </div>

        <?php if (!empty($reports)): ?>
        <!-- Cards de resumo -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card h-100"
                    style="border-radius:16px;border:1.5px solid var(--border-color,rgba(0,0,0,.08))">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="bi bi-calendar-check me-1"></i>Períodos com
                            royalties</div>
                        <div class="fw-bold" style="font-size:1.6rem"><?php echo count($reports); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100"
                    style="border-radius:16px;border:1.5px solid var(--border-color,rgba(0,0,0,.08))">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="bi bi-currency-dollar me-1"></i>Total pago (USD)
                        </div>
                        <div class="fw-bold" style="font-size:1.6rem">
                            $<?php echo number_format((float)$totals['grand_usd'], 2); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100"
                    style="border-radius:16px;border:1.5px solid var(--border-color,rgba(0,0,0,.08))">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="bi bi-cash me-1"></i>Total pago (AOA)</div>
                        <div class="fw-bold" style="font-size:1.6rem">
                            <?php echo number_format((float)$totals['grand_aoa'], 2, ',', '.'); ?> Kz</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabela de relatórios -->
        <div class="table-card mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-file-earmark-text me-2 text-pink"></i>Relatórios Mensais</h6>
                    <span class="badge bg-secondary"><?php echo count($reports); ?> períodos</span>
                </div>
                <div class="table-responsive">
                    <?php if (empty($reports)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-file-earmark-text fs-1 d-block mb-2 opacity-25"></i>
                        <div class="small fw-semibold mb-1">Nenhum relatório disponível ainda.</div>
                        <div class="small">Os relatórios aparecem aqui após o processamento mensal dos teus royalties
                            pela equipa <?php echo APP_NAME ?>.</div>
                    </div>
                    <?php else: ?>
                    <table id="reportsWasomupfy" class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Mês</th>
                                <th>Ano</th>
                                <th class="text-center">Faixas</th>
                                <th>Valor (USD)</th>
                                <th>Valor (AOA)</th>
                                <th>Estado</th>
                                <th class="text-center">Arquivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $rep):
                                    $month_name = $months_pt[(int)$rep['month_royalty']] ?? '—';
                                    $st         = $status_map[$rep['status_royalty']] ?? $status_map['pending'];
                                    $has_file   = !empty($rep['report_file']);
                                ?>
                            <tr>
                                <td class="fw-semibold small"><?php echo $month_name; ?></td>
                                <td class="small"><?php echo (int)$rep['year_royalty']; ?></td>
                                <td class="small text-center"><?php echo (int)$rep['num_tracks']; ?></td>
                                <td class="small fw-semibold">$<?php echo number_format((float)$rep['total_usd'], 4); ?>
                                </td>
                                <td class="small fw-semibold">
                                    <?php echo $rep['total_aoa']
                                                ? number_format((float)$rep['total_aoa'], 2, ',', '.') . ' Kz'
                                                : '—'; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $st['class']; ?>"><?php echo $st['label']; ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($has_file): ?>
                                    <a href="<?php echo htmlspecialchars($reports_url . $rep['report_file']); ?>"
                                        class="btn btn-sm btn-outline-pink" target="_blank" rel="noopener" download
                                        data-bs-toggle="tooltip"
                                        title="Descarregar <?php echo $month_name . ' ' . $rep['year_royalty']; ?>">
                                        <i class="bi bi-download me-1"></i>PDF
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted small" data-bs-toggle="tooltip"
                                        title="O arquivo ainda não foi gerado pela equipa.">
                                        <i class="bi bi-clock me-1"></i>A aguardar
                                    </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Nota informativa -->
        <div class="p-3 mb-4"
            style="background:rgba(255,0,137,.04);border-radius:14px;border:1px solid rgba(255,0,137,.12)">
            <div class="d-flex gap-2 align-items-start">
                <i class="bi bi-info-circle-fill mt-1" style="color:#FF0089;flex-shrink:0"></i>
                <div style="font-size:.8rem;color:var(--text-muted,#6c757d)">
                    Os relatórios são gerados mensalmente pela equipa <?php echo APP_NAME ?> após o encerramento do
                    período de
                    reporte das plataformas de streaming.
                    Caso tenhas dúvidas sobre os valores apresentados, contacta o <a href="../page/support"
                        class="text-pink">suporte</a>.
                </div>
            </div>
        </div>

    </div><!-- /container -->

    <!-- ═══ JS ═══ -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <script>
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    <?php if (!empty($reports)): ?>
    $(document).ready(function() {
        $('#reportsWasomupfy').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            lengthChange: false,
            pageLength: 10,
            order: [
                [1, 'desc'],
                [0, 'desc']
            ],
            columnDefs: [{
                orderable: false,
                targets: 6
            }],
            language: {
                search: 'Pesquisar por mês ou ano:',
                info: 'A mostrar _START_ a _END_ de _TOTAL_ relatórios',
                paginate: {
                    next: 'Próximo',
                    previous: 'Anterior'
                },
                emptyTable: 'Nenhum relatório disponível.'
            }
        });
    });
    <?php endif; ?>
    </script>
</body>

</html>