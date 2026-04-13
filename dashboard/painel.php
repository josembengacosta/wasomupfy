<?php
// WASOM UPFY v2.0 - Painel Principal
// Arquivo: dashboard/painel.php
require_once __DIR__ . '/../authentic/include/functions.php';
require_once __DIR__ . '/include/platform.php';
startSecureSession();
checkRememberMe();
requireLogin();
$platform = checkDashboardStatus();
$user     = getUserById($_SESSION['id_users']);
$db = getDB();

$user = getUserById((int)$_SESSION['id_users']);

if (!$user) {
    session_destroy();
    redirect('/login', ['error' => 'csrf']);
}

$first_name     = htmlspecialchars($user['first_name']);
$user_name      = htmlspecialchars($user['user_name'] ?? '');
$name_artist_band      = htmlspecialchars($user['name_artist_band'] ?? 'Cria Perfil Artistíco');
$email_verified = (bool)$user['email_verified'];
$plan_selected  = $user['plan_selected'];
$onboard_done   = (bool)($user['onboarding_done'] ?? false);
$id_users       = (int)$user['id_users'];

// Saldo
$w = getDB()->prepare('SELECT balance_aoa, balance_usd FROM _wallet WHERE id_users = ?');
$w->execute([$id_users]);
$balance = $w->fetch() ?: ['balance_aoa' => 0, 'balance_usd' => 0];

// Plano
$plan = null;
$plan_paid = false; // true = plano activo e pago
if ($plan_selected) {
    $ps = getDB()->prepare('SELECT * FROM _plans WHERE id_plan = ?');
    $ps->execute([$plan_selected]);
    $plan = $ps->fetch();
}
// Plano considerado pago se status_user = 'active' E plan_activated_at preenchido
$plan_paid = ($user['status_user'] === 'active' && !empty($user['plan_activated_at']));

// Foto do utilizador — usada em ambos os lugares do dropdown (conta + perfil)
$user_photo = $user['photo_user'] ?? null;

// Contagem de notificações não lidas (badge no navbar)
$notif_count = getUnreadNotifCount($id_users);

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

// Payment intent activo (para mostrar referência inline no onboarding step 3)
$ob_intent = null;
if ($plan_selected && !$plan_paid) {
    $pi = getDB()->prepare("
        SELECT reference_code, amount_expected, expires_at
        FROM _payment_intent
        WHERE id_users = ? AND status IN ('created','waiting_payment','under_review')
        ORDER BY creat_intent DESC LIMIT 1
    ");
    $pi->execute([$id_users]);
    $ob_intent = $pi->fetch() ?: null;
}

// Conta bancaria para saque (tabela _account existente)
$bank_stmt = getDB()->prepare("
    SELECT * FROM _account
    WHERE id_users = ? AND status_account = 'verified' AND is_default = 1
    LIMIT 1
");
$bank_stmt->execute([$id_users]);
$bank_account = $bank_stmt->fetch() ?: null;

// Saldo em AOA (float)
$balance_aoa = (float)($balance['balance_aoa'] ?? 0);
$min_withdrawal = 10000.00; // Minimo de saque: 10.000 Kz
$can_withdraw = $plan_paid && $bank_account && ($balance_aoa >= $min_withdrawal);


$current_year  = date('Y');
$current_month = (int)date('n');
?>

<?php
// ════════════════════════════════════════════════════════════════
// STREAMS & GRÁFICO (ANO MAIS RECENTE COM DADOS, 12 MESES FIXOS)
// ════════════════════════════════════════════════════════════════

// 1. Streams para a lista (apenas lojas com total > 0)
$streams_stmt = getDB()->prepare("
    SELECT
        st.id_store,
        st.name_store,
        st.slug_store,
        COALESCE(SUM(s.streams), 0) as total
    FROM _store st
    LEFT JOIN _stream s ON s.id_store = st.id_store
    LEFT JOIN _track t ON t.id_track = s.id_track AND t.id_users = ?
    WHERE st.is_active = 1
    GROUP BY st.id_store
    HAVING total > 0
    ORDER BY total DESC
    LIMIT 10
");
$streams_stmt->execute([$id_users]);
$streams = $streams_stmt->fetchAll();
$has_streams = !empty($streams);

// 2. Descobrir o ano mais recente com streams para este utilizador
$year_stmt = getDB()->prepare("
    SELECT MAX(s.year_stream) 
    FROM _stream s
    JOIN _track t ON t.id_track = s.id_track
    WHERE t.id_users = ?
");
$year_stmt->execute([$id_users]);
$latest_year = $year_stmt->fetchColumn();

// Se não houver nenhum stream, usar o ano atual como fallback
$display_year = $latest_year ? (int)$latest_year : (int)date('Y');

// 3. Dados mensais para o gráfico (ano fixo, 12 meses)
$chart_stmt = getDB()->prepare("
    SELECT
        st.slug_store,
        st.name_store,
        s.month_stream,
        SUM(s.streams) as total
    FROM _stream s
    JOIN _track  t  ON t.id_track = s.id_track AND t.id_users = ?
    JOIN _store  st ON st.id_store = s.id_store
    WHERE s.year_stream = ?
    GROUP BY st.id_store, st.slug_store, st.name_store, s.month_stream
    ORDER BY s.month_stream
");
$chart_stmt->execute([$id_users, $display_year]);
$chart_rows = $chart_stmt->fetchAll();

// 4. Total de lançamentos
$rel_stmt = getDB()->prepare("SELECT COUNT(*) as total FROM _album WHERE id_users = ?");
$rel_stmt->execute([$id_users]);
$total_releases = (int)($rel_stmt->fetch()['total'] ?? 0);

// 5. Cores por slug
$platform_colors = [
    'spotify'       => ['border' => '#1db954', 'bg' => 'rgba(29,185,84,0.45)'],
    'apple-music'   => ['border' => '#fa586a', 'bg' => 'rgba(250,88,106,0.45)'],
    'apple_music'   => ['border' => '#fa586a', 'bg' => 'rgba(250,88,106,0.45)'],
    'deezer'        => ['border' => '#ff0089', 'bg' => 'rgba(255,0,137,0.45)'],
    'amazon-music'  => ['border' => '#00a8e0', 'bg' => 'rgba(0,168,224,0.45)'],
    'amazon_music'  => ['border' => '#00a8e0', 'bg' => 'rgba(0,168,224,0.45)'],
    'youtube-music' => ['border' => '#ff0000', 'bg' => 'rgba(255,0,0,0.45)'],
    'youtube_music' => ['border' => '#ff0000', 'bg' => 'rgba(255,0,0,0.45)'],
    'tidal'         => ['border' => '#00ffff', 'bg' => 'rgba(0,255,255,0.35)'],
    'boomplay'      => ['border' => '#ff6600', 'bg' => 'rgba(255,102,0,0.45)'],
    'soundcloud'    => ['border' => '#ff5500', 'bg' => 'rgba(255,85,0,0.45)'],
    'tiktok'        => ['border' => '#010101', 'bg' => 'rgba(0,0,0,0.45)'],
    'itunes'        => ['border' => '#c864c8', 'bg' => 'rgba(200,100,200,0.4)'],
    'resso'         => ['border' => '#ff6b6b', 'bg' => 'rgba(255,107,107,0.4)'],
    'claro-music'   => ['border' => '#d90429', 'bg' => 'rgba(217,4,41,0.4)'],
    'facebook'      => ['border' => '#1877f2', 'bg' => 'rgba(24,119,242,0.4)'],
    'youtube'       => ['border' => '#ff0000', 'bg' => 'rgba(255,0,0,0.4)'],
    'pandora'       => ['border' => '#3668b0', 'bg' => 'rgba(54,104,176,0.4)'],
];

// 6. Labels fixas: 12 meses do ano
$months_pt = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
$chart_labels = [];
for ($m = 1; $m <= 12; $m++) {
    $chart_labels[] = $months_pt[$m-1] . ' ' . $display_year;
}

// 7. Agrupar dados por plataforma (meses 1-12 preenchidos com zero)
$by_platform = [];
foreach ($chart_rows as $r) {
    $slug = $r['slug_store'];
    $month = (int)$r['month_stream'];
    $by_platform[$slug]['name'] = $r['name_store'];
    $by_platform[$slug]['data'][$month] = (int)$r['total'];
}

// 8. Construir datasets para Chart.js (12 pontos fixos)
$chart_datasets = [];
foreach ($by_platform as $slug => $info) {
    $color = $platform_colors[$slug] ?? ['border' => '#aaaaaa', 'bg' => 'rgba(170,170,170,0.4)'];
    $data = [];
    for ($m = 1; $m <= 12; $m++) {
        $data[] = $info['data'][$m] ?? 0;
    }
    $chart_datasets[] = [
        'label'           => $info['name'],
        'data'            => $data,
        'borderColor'     => $color['border'],
        'backgroundColor' => $color['bg'],
        'fill'            => true,
        'tension'         => 0.4,
    ];
}

// Se não houver datasets, criar vazios para lojas ativas
if (empty($chart_datasets)) {
    $all_stores_stmt = getDB()->prepare("
        SELECT name_store, slug_store
        FROM _store
        WHERE is_active = 1
        ORDER BY display_order
    ");
    $all_stores_stmt->execute();
    $all_stores = $all_stores_stmt->fetchAll();
    foreach ($all_stores as $store) {
        $slug  = $store['slug_store'];
        $color = $platform_colors[$slug] ?? ['border' => '#aaaaaa', 'bg' => 'rgba(170,170,170,0.4)'];
        $chart_datasets[] = [
            'label'           => $store['name_store'],
            'data'            => array_fill(0, 12, 0),
            'borderColor'     => $color['border'],
            'backgroundColor' => $color['bg'],
            'fill'            => true,
            'tension'         => 0.4,
        ];
    }
}

$chart_json_labels   = json_encode($chart_labels);
$chart_json_datasets = json_encode($chart_datasets); ?>

<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/include/head.php'; ?>
    <title>Dashboard — <?php echo APP_NAME; ?></title>
    <!-- <style>
        @font-face {
            font-family: 'FonteLogo';
            src: url("../css/fonts/bubblegum-sans-regular.otf.ttf");
        }
        .brand_wp {
            font-weight: bold;
            box-sizing: border-box;
            text-transform: uppercase;
            font-family: 'FonteLogo', sans-serif;
        }
    </style> -->

    <style>
    /* ─── Onboarding ─────────────────────── */
    .ob-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #dee2e6;
        display: inline-block;
        transition: background .3s;
    }

    .ob-dot.active {
        background: #FF0089;
        transform: scale(1.3);
    }

    /* ─── Verification badge ─────────────── */
    .verification-badge {
        background: linear-gradient(135deg, #FF0089, #FF4D4D);
        color: white;
        padding: .25rem 1rem;
        border-radius: 20px;
        font-size: .875rem;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
    }

    .unverified-badge {
        background: #fff3cd;
        color: #856404;
        padding: .25rem 1rem;
        border-radius: 20px;
        font-size: .875rem;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        border: 1px solid #ffc107;
    }
    </style>
</head>

<body>
    <?php include __DIR__ . '/finances/_modal_withdrawal.php'; ?>
    <!-- ═══ NAVBAR ═══ -->
    <?php require_once __DIR__ . '/include/sidebar.php'; ?>
    <!-- Main Content -->
    <div class="container my-4">
        <?php
        require_once __DIR__ . '/include/alert.php';
        renderPanelAlerts($user, $plan, $plan_paid, $bank_account, $db, $id_users);

        // Restaurar variáveis necessárias para o restante da página
        $total_artists = (int)$db->query("SELECT COUNT(*) FROM _artist WHERE id_users = $id_users")->fetchColumn();
        $has_any_artist = $total_artists > 0;
        ?>
        <!-- Header da Pagina Inicial -->
        <div class="page-header">
            <h1>
                <i class="bi bi-house-door-fill me-3"></i> Olá, seja
                bem-vindo(a) de volta <br> <span class="text-warning"><?php echo $first_name; ?>!</span>
            </h1>
            <p class="lead">
                Aqui está um resumo do desempenho recente dos seus lançamentos e o que
                está acontecendo com sua conta. Continue acompanhando para não perder
                nenhuma novidade!
            </p>
            <!-- Ícone decorativo: casa -->
            <style>
            .page-header::before {
                content: '\F1D0';
                /* bi-house-door-fill */
            }
            </style>
        </div>
        <!-- ════ ONBOARDING MODAL ════ -->
        <?php if (!$onboard_done): ?>
        <div class="modal fade" id="onboardingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="onboardingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">

                    <!-- Header gradiente -->
                    <div class="modal-header border-0 pb-0"
                        style="background:linear-gradient(135deg,#FF0089,#FF4D4D);color:#fff;border-radius:.5rem .5rem 0 0">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-stars fs-5"></i>
                            <h5 class="modal-title fw-bold mb-0" id="onboardingModalLabel">
                                Bem-vindo(a) ao <?php echo APP_NAME  ?>, <?php echo $first_name; ?>!
                            </h5>
                        </div>
                    </div>

                    <!-- Barra de progresso contínua -->
                    <div style="background:rgba(255,0,137,.15);height:4px">
                        <div id="ob-progress-bar"
                            style="height:100%;background:#FF0089;transition:width .35s ease;width:20%"></div>
                    </div>

                    <div class="modal-body p-4">

                        <!-- Progress dots + label de step -->
                        <div class="d-flex justify-content-center align-items-center gap-3 mb-4">
                            <span class="ob-dot active" data-step="1"></span>
                            <span class="ob-dot" data-step="2"></span>
                            <span class="ob-dot" data-step="3"></span>
                            <span class="ob-dot" data-step="4"></span>
                            <span class="ob-dot" data-step="5"></span>
                        </div>

                        <!-- ── STEP 1: Boas-vindas ── -->
                        <div class="ob-step" id="ob-1">
                            <div class="text-center mb-3">
                                <i class="bi bi-emoji-smile-fill" style="font-size:3rem;color:#FF0089"></i>
                            </div>
                            <h5 class="text-center fw-bold">A tua conta foi criada com sucesso!</h5>
                            <p class="text-muted text-center mb-3">
                                O <?php echo APP_NAME  ?> distribui a tua música para mais de 150 lojas digitais
                                mundiais.
                            </p>
                            <hr>
                            <ul class="list-unstyled mb-3">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Distribui para +150 plataformas incluindo Spotify, Apple Music e Deezer
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Recebe royalties directamente na tua carteira
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Acompanha streams e estatísticas em tempo real
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Suporte dedicado em português
                                </li>
                            </ul>

                            <!-- Push notifications -->
                            <div class="border rounded-3 p-3 mb-3"
                                style="background:rgba(99,102,241,.06);border-color:rgba(99,102,241,.2)!important">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-bell-fill mt-1 flex-shrink-0" style="color:#6366f1"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold small mb-1">Activa as notificações push</div>
                                        <p class="text-muted mb-2" style="font-size:.8rem">
                                            Sabe quando os teus pagamentos são confirmados, lançamentos aprovados e
                                            mais — sem precisares de verificar o dashboard.
                                        </p>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="ob-btn-push"
                                            onclick="obRequestPush(this)">
                                            <i class="bi bi-bell me-1"></i>Activar notificações
                                        </button>
                                        <span id="ob-push-status" class="ms-2 small text-muted"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info small mb-0">
                                <i class="bi bi-clock me-1"></i>
                                <strong>Aviso de sessão:</strong> Se não iniciares sessão por
                                <strong>30 dias</strong> consecutivos, a sessão expira automaticamente.
                            </div>
                        </div>

                        <!-- ── STEP 2: Email ── -->
                        <div class="ob-step d-none" id="ob-2">
                            <div class="text-center mb-3">
                                <i class="bi bi-envelope-check-fill" style="font-size:3rem;color:#FF0089"></i>
                            </div>
                            <h5 class="text-center fw-bold">Verificação de Email</h5>
                            <?php if (!$email_verified): ?>
                            <p class="text-muted text-center mb-3">
                                Enviámos um código de 6 dígitos para
                                <strong><?php echo htmlspecialchars($user['email_user']); ?></strong>.
                            </p>
                            <div class="alert alert-warning">
                                <i class="bi bi-clock-history me-2"></i>
                                O teu email ainda não foi verificado. Podes continuar e verificar mais tarde em
                                <strong>Perfil &rsaquo; Segurança</strong>. O código não expira.
                            </div>
                            <div class="d-flex justify-content-center">
                                <a href="user/profile#perfil" class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-envelope me-1"></i>Verificar email agora
                                </a>
                            </div>
                            <?php else: ?>
                            <p class="text-muted text-center">
                                <strong><?php echo htmlspecialchars($user['email_user']); ?></strong>
                            </p>
                            <div class="alert alert-success text-center mb-0">
                                <i class="bi bi-patch-check-fill me-2"></i>Email verificado com sucesso!
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- ── STEP 3: Plano + Instruções de pagamento ── -->
                        <div class="ob-step d-none" id="ob-3">
                            <div class="text-center mb-3">
                                <i class="bi bi-star-fill" style="font-size:3rem;color:#FF0089"></i>
                            </div>
                            <h5 class="text-center fw-bold">O teu Plano</h5>

                            <?php if ($plan_paid): ?>
                            <!-- Plano activo e pago -->
                            <div class="alert alert-success text-center mb-3">
                                <i class="bi bi-patch-check-fill me-2"></i>
                                Plano <strong><?php echo htmlspecialchars($plan['name_plan']); ?></strong> activo!
                                A distribuição está disponível.
                            </div>

                            <?php elseif ($plan && !$plan_paid): ?>
                            <!-- Plano seleccionado mas por pagar -->
                            <div class="card border-0 p-3 mb-3"
                                style="border-left:4px solid #FF0089!important;background:rgba(255,0,137,.06)">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($plan['name_plan']); ?>
                                        </h6>
                                        <p class="text-muted small mb-1">
                                            <?php echo htmlspecialchars($plan['description_plan'] ?? ''); ?>
                                        </p>
                                        <small>
                                            <i class="bi bi-percent me-1"></i>
                                            <?php echo $plan['royalty_rate'] ?? '90'; ?>% royalties para ti
                                        </small>
                                    </div>
                                    <div class="text-end flex-shrink-0 ms-3">
                                        <span class="fw-bold" style="color:#FF0089">
                                            <?php echo number_format($plan['price_plan'], 0, ',', '.'); ?> AOA
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Instruções de pagamento inline -->
                            <div class="card border-0 mb-0"
                                style="background:rgba(234,179,8,.06);border:1px solid rgba(234,179,8,.25)!important;border-radius:12px">
                                <div class="card-body p-3">
                                    <div class="fw-semibold small mb-2">
                                        <i class="bi bi-credit-card me-1" style="color:#eab308"></i>
                                        Como efectuar o pagamento
                                    </div>
                                    <?php if ($ob_intent): ?>
                                    <!-- Referência gerada -->
                                    <div class="d-flex align-items-center justify-content-between mb-2 p-2 rounded"
                                        style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)">
                                        <div>
                                            <div class="text-muted" style="font-size:.7rem">Referência de pagamento
                                            </div>
                                            <div class="fw-bold font-monospace"
                                                style="font-size:1.1rem;letter-spacing:.1em;color:#FF0089">
                                                <?php echo htmlspecialchars($ob_intent['reference_code']); ?>
                                            </div>
                                        </div>
                                        <button type="button"
                                            onclick="navigator.clipboard.writeText('<?php echo $ob_intent['reference_code']; ?>');this.innerHTML='<i class=\'bi bi-check-lg\'></i>';setTimeout(()=>this.innerHTML='<i class=\'bi bi-copy\'></i>',2000)"
                                            class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>
                                    <?php if ($ob_intent['expires_at']): ?>
                                    <div class="text-muted mb-2" style="font-size:.75rem">
                                        <i class="bi bi-clock me-1"></i>
                                        Referência válida até
                                        <strong><?php echo date('d/m/Y H:i', strtotime($ob_intent['expires_at'])); ?></strong>
                                    </div>
                                    <?php endif; ?>
                                    <?php endif; ?>

                                    <ul class="list-unstyled small mb-2 text-muted">
                                        <li class="mb-1">
                                            <i class="bi bi-1-circle me-1" style="color:#eab308"></i>
                                            Transferência bancária ou Multicaixa Express
                                        </li>
                                        <li class="mb-1">
                                            <i class="bi bi-2-circle me-1" style="color:#eab308"></i>
                                            Usa a referência acima como descrição/motivo
                                        </li>
                                        <li class="mb-1">
                                            <i class="bi bi-3-circle me-1" style="color:#eab308"></i>
                                            Submete o comprovativo em <strong>Finanças › Pagamento</strong>
                                        </li>
                                    </ul>
                                    <a href="payment/pay" class="btn btn-sm btn-wasomupfy w-100">
                                        <i class="bi bi-upload me-1"></i>Submeter comprovativo &rarr;
                                    </a>
                                </div>
                            </div>

                            <?php else: ?>
                            <!-- Sem plano seleccionado -->
                            <p class="text-center text-muted mb-3">Ainda não escolheste um plano.</p>
                            <div class="d-grid gap-2">
                                <a href="all-plans#single" class="btn btn-outline-pink btn-sm text-start">
                                    <i class="bi bi-music-note me-2"></i>
                                    <strong>Single</strong> — Lançamentos avulsos
                                </a>
                                <a href="all-plans#album" class="btn btn-outline-pink btn-sm text-start">
                                    <i class="bi bi-disc me-2"></i>
                                    <strong>Album</strong> — Pacote de lançamentos
                                </a>
                                <a href="all-plans#artist" class="btn btn-outline-pink btn-sm text-start">
                                    <i class="bi bi-person-badge me-2"></i>
                                    <strong>Artist</strong> — Para artistas activos
                                </a>
                                <a href="all-plans#label" class="btn btn-outline-pink btn-sm text-start">
                                    <i class="bi bi-building me-2"></i>
                                    <strong>Label</strong> — Para editoras
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- ── STEP 4: Perfil de Artista ── -->
                        <div class="ob-step d-none" id="ob-4">
                            <div class="text-center mb-3">
                                <i class="bi bi-person-badge-fill" style="font-size:3rem;color:#FF0089"></i>
                            </div>
                            <h5 class="text-center fw-bold">Perfil de Artista</h5>
                            <p class="text-muted text-center">
                                Para distribuir música precisas de um perfil de artista.
                            </p>
                            <?php if ($has_any_artist): ?>
                            <div class="alert alert-success text-center mb-3">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                Já tens um perfil de artista. Tudo pronto!
                            </div>
                            <div class="d-grid">
                                <a href="releases" class="btn btn-wasomupfy">
                                    <i class="bi bi-rocket-takeoff me-2"></i>Começar a distribuir agora
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info small">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Nome artístico:</strong> No plano Single, o nome de selo é atribuído por nós.
                                Nos planos Album, Artist e Label podes personalizar ao criar um lançamento.
                            </div>
                            <div class="d-grid gap-2 mt-3">
                                <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/add-artist"
                                    class="btn btn-wasomupfy">
                                    <i class="bi bi-person-plus me-2"></i>Criar Perfil de Artista
                                </a>
                                <button type="button" class="btn btn-link text-muted" id="ob-skip-artist">
                                    Criar mais tarde
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- ── STEP 5: Pronto! ── -->
                        <div class="ob-step d-none" id="ob-5">
                            <div class="text-center mb-3">
                                <i class="bi bi-trophy-fill" style="font-size:3rem;color:#FF0089"></i>
                            </div>
                            <h5 class="text-center fw-bold">Estás pronto(a)!</h5>
                            <p class="text-muted text-center mb-4">
                                O teu painel <?php echo APP_NAME  ?> está configurado. Aqui está o resumo do que tens a
                                fazer a
                                seguir:
                            </p>

                            <!-- Checklist dinâmica -->
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item d-flex align-items-center gap-2 px-0">
                                    <?php if ($email_verified): ?>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <span>Email verificado</span>
                                    <?php else: ?>
                                    <i class="bi bi-circle text-warning fs-5"></i>
                                    <span>
                                        <a href="user/profile#perfil" class="text-warning fw-semibold">
                                            Verificar email
                                        </a>
                                    </span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item d-flex align-items-center gap-2 px-0">
                                    <?php if ($plan_paid): ?>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <span>Plano activo —
                                        <?php echo htmlspecialchars($plan['name_plan'] ?? ''); ?></span>
                                    <?php elseif ($plan): ?>
                                    <i class="bi bi-clock-fill text-warning fs-5"></i>
                                    <span>
                                        <a href="payment/pay" class="text-warning fw-semibold">
                                            Confirmar pagamento do plano
                                        </a>
                                    </span>
                                    <?php else: ?>
                                    <i class="bi bi-circle text-danger fs-5"></i>
                                    <span>
                                        <a href="all-plans" class="text-danger fw-semibold">
                                            Escolher um plano
                                        </a>
                                    </span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item d-flex align-items-center gap-2 px-0">
                                    <?php if ($has_any_artist): ?>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <span>Perfil de artista criado</span>
                                    <?php else: ?>
                                    <i class="bi bi-circle text-muted fs-5"></i>
                                    <span>
                                        <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/add-artist"
                                            class="text-muted">
                                            Criar perfil de artista
                                        </a>
                                        <span class="badge bg-secondary ms-1" style="font-size:.65rem">opcional
                                            agora</span>
                                    </span>
                                    <?php endif; ?>
                                </li>
                            </ul>

                            <div class="d-grid">
                                <button type="button" class="btn btn-wasomupfy btn-lg" id="ob-finish"
                                    onclick="finishOnboarding()">
                                    <i class="bi bi-house-door me-2"></i>Entrar no Painel
                                </button>
                            </div>
                        </div>

                    </div><!-- /modal-body -->

                    <div class="modal-footer d-flex justify-content-between align-items-center border-top">
                        <button type="button" class="btn btn-outline-secondary d-none" id="ob-prev">
                            <i class="bi bi-arrow-left me-1"></i>Anterior
                        </button>
                        <div></div>
                        <button type="button" class="btn btn-wasomupfy" id="ob-next">
                            Continuar <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                        <!-- ob-finish está dentro do step 5 para melhor layout -->
                        <span id="ob-finish-placeholder"></span>
                    </div>

                </div>
            </div>
        </div>
        <?php endif; ?>
        <!-- ════ FIM ONBOARDING MODAL ════ -->


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
                    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/withdraw" class="btn btn-pink">
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

        <!-- Launch Card -->
        <div class="card mb-4">
            <div class="card">
                <div class="d-flex align-items-lg-center">
                    <i class="bi bi-vinyl-fill gt text-7xl me-4"></i>
                    <div class="m-auto w-100 text-center welcome-text">
                        <?php if (!$plan_paid): ?>
                        <h5>Activa o teu plano para lançar música</h5>
                        <p>Tens de completar o pagamento do plano antes de poder distribuir música nas plataformas.</p>
                        <a href="payment" class="btn btn-pink w-100">
                            <i class="bi bi-credit-card me-2"></i> Finalizar Pagamento
                        </a>
                        <?php elseif (!$has_any_artist): ?>
                        <h5>Cria o teu perfil de artista</h5>
                        <p>Antes de lançar música, precisa de um perfil de artista associado à tua conta.</p>
                        <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/add-artist" class="btn btn-pink w-100">
                            <i class="bi bi-person-plus me-2"></i> Criar Perfil de Artista
                        </a>
                        <?php else: ?>
                        <h5>Pronto para lançar a tua próxima música?</h5>
                        <p>Cria um novo lançamento com código UPC exclusivo e distribui para +150 plataformas em até 72
                            horas.</p>
                        <a href="creat-release" class="btn btn-pink w-100">
                            <i class="bi bi-plus me-2"></i> Novo Lançamento
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Card -->
        <div class="chart-card mb-4 platforms-card data-simplebar">
            <div class="text-center">
                <h5>Desempenho de streams por plataforma</h5>
                <p>Acompanhe a evolução dos teus streams e identifique onde a tua música tem mais impacto.</p>
            </div>

            <?php if (!$plan_paid): ?>
            <!-- Estado: sem plano activo -->
            <div class="card text-center py-5">
                <i class="bi bi-lock fs-1 text-muted mb-3"></i>
                <h6 class="text-muted">Estatísticas bloqueadas</h6>
                <p class="text-muted small">Activa o teu plano para começar a distribuir e ver os teus streams.</p>
                <a href="payment" class="btn btn-pink btn-sm mt-2">
                    <i class="bi bi-credit-card me-1"></i>Activar Plano
                </a>
            </div>

            <?php elseif ($total_releases === 0): ?>
            <!-- Estado: tem plano mas sem lançamentos -->
            <div class="card text-center py-5">
                <i class="bi bi-vinyl fs-1 text-muted mb-3" style="opacity:.4"></i>
                <h6 class="text-muted">Ainda sem lançamentos</h6>
                <p class="text-muted small">
                    Cria o teu primeiro lançamento para começar a receber streams nas plataformas.
                </p>
                <?php if ($has_any_artist): ?>
                <a href="creat-release" class="btn btn-pink btn-sm mt-2">
                    <i class="bi bi-plus me-1"></i>Criar Lançamento
                </a>
                <?php else: ?>
                <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/add-artist" class="btn btn-pink btn-sm mt-2">
                    <i class="bi bi-person-plus me-1"></i>Criar Perfil de Artista
                </a>
                <?php endif; ?>
            </div>

            <?php elseif (!$has_streams): ?>
            <!-- Estado: tem lançamentos mas streams ainda a chegar -->
            <div class="card text-center py-5">
                <div class="mb-3">
                    <span class="spinner-border spinner-border-sm text-muted me-2"></span>
                    <i class="bi bi-hourglass-split fs-1 text-muted" style="opacity:.5"></i>
                </div>
                <h6 class="text-muted">A aguardar primeiros streams</h6>
                <p class="text-muted small">
                    Os teus lançamentos estão em distribuição. Os streams começam a aparecer em 24–72 horas após
                    aprovação.
                </p>
                <a href="statistics" class="btn btn-outline-secondary btn-sm mt-2">
                    <i class="bi bi-bar-chart me-1"></i>Ver Estatísticas
                </a>
            </div>

            <?php else: ?>
            <!-- Estado: exibir gráfico (sempre, mesmo sem streams) -->
            <div class="card">
                <?php if (!empty($chart_datasets)): ?>
                <canvas id="streamChart" style="max-height:300px"></canvas>
                <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-calendar-x fs-1 mb-2" style="opacity:.4"></i>
                    <p>Sem dados de streams disponíveis.</p>
                </div>
                <?php endif; ?>
                <hr />
                <ul class="mt-2">
                    <?php
                        $platform_icons = [
                            // ── Streaming de áudio principal ──────────────────────────────────────
                            'spotify'           => '<img src="https://upload.wikimedia.org/wikipedia/commons/1/19/Spotify_logo_without_text.svg" width="22" alt="Spotify">',
                            'apple_music'       => '<img src="https://upload.wikimedia.org/wikipedia/commons/5/5f/Apple_Music_icon.svg" width="22" alt="Apple Music">',
                            'apple-music'       => '<img src="https://upload.wikimedia.org/wikipedia/commons/5/5f/Apple_Music_icon.svg" width="22" alt="Apple Music">',
                            'deezer'            => '<img src="https://e-cdns-files.dzcdn.net/img/common/logos/deezer-logo.svg" width="22" alt="Deezer">',
                            'tidal'             => '<img src="https://upload.wikimedia.org/wikipedia/commons/1/12/Tidal_%28service%29_logo.svg" width="22" alt="Tidal">',
                            'amazon_music'      => '<img src="https://upload.wikimedia.org/wikipedia/commons/d/d5/Amazon_Music_logo.svg" width="22" alt="Amazon Music">',
                            'amazon-music'      => '<img src="https://upload.wikimedia.org/wikipedia/commons/d/d5/Amazon_Music_logo.svg" width="22" alt="Amazon Music">',
                            'amazon_unlimited'  => '<img src="https://upload.wikimedia.org/wikipedia/commons/d/d5/Amazon_Music_logo.svg" width="22" alt="Amazon Unlimited">',
                            'soundcloud'        => '<img src="https://upload.wikimedia.org/wikipedia/commons/a/a2/SoundCloud_logo_svg.svg" width="22" alt="SoundCloud">',
                            'pandora'           => '<img src="https://upload.wikimedia.org/wikipedia/commons/f/f7/Pandora_logo_with_new_icon.svg" width="22" alt="Pandora">',
                            'napster'           => '<img src="https://upload.wikimedia.org/wikipedia/commons/4/42/Napster_corporate_logo.svg" width="22" alt="Napster">',
                            'qobuz'             => '<img src="https://upload.wikimedia.org/wikipedia/commons/4/4e/Qobuz_logo.svg" width="22" alt="Qobuz">',
                            'kkbox'             => '<img src="https://upload.wikimedia.org/wikipedia/commons/e/e7/KKBOX_logo.svg" width="22" alt="KKBOX">',
                            'anghami'           => '<img src="https://upload.wikimedia.org/wikipedia/commons/8/89/Anghami-logo.svg" width="22" alt="Anghami">',
                            'audiomack'         => '<img src="https://upload.wikimedia.org/wikipedia/commons/8/8e/Audiomack_logo.svg" width="22" alt="Audiomack">',
                            'gaana'             => '<img src="https://upload.wikimedia.org/wikipedia/commons/f/f3/Gaana_logo.svg" width="22" alt="Gaana">',
                            'jiosaavn'          => '<img src="https://upload.wikimedia.org/wikipedia/commons/2/29/JioSaavn_Logo.svg" width="22" alt="JioSaavn">',
                            'wynk'              => '<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/8d/Wynk_Logo.jpg/240px-Wynk_Logo.jpg" width="22" alt="Wynk">',
                            'hungama'           => '<img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Hungama_logo.svg" width="22" alt="Hungama">',
                            'yandex_music'      => '<img src="https://upload.wikimedia.org/wikipedia/commons/d/da/Yandex_Music_icon_ru.svg" width="22" alt="Yandex Music">',
                            'boomplay'          => '<img src="https://play-lh.googleusercontent.com/cxCsZIh08Nj2Hd0OmNnMp7CAMq3kRfnz3VObgFH7RCrUiKDMRYzTHblZAMxGEopbcc=w240-h480-rw" width="22" alt="Boomplay">',
                            'mdundo'            => '<i class="bi bi-music-note-list fs-5" style="color:#e85d04;"></i>',
                            'boomplay_free'     => '<img src="https://play-lh.googleusercontent.com/cxCsZIh08Nj2Hd0OmNnMp7CAMq3kRfnz3VObgFH7RCrUiKDMRYzTHblZAMxGEopbcc=w240-h480-rw" width="22" alt="Boomplay Free">',
                            'resso'             => '<img src="https://upload.wikimedia.org/wikipedia/commons/8/8d/Resso-icon.png" width="22" alt="Resso">',

                            // ── Vídeo & social ────────────────────────────────────────────────────
                            'youtube'           => '<img src="https://upload.wikimedia.org/wikipedia/commons/0/09/YouTube_full-color_icon_%282017%29.svg" width="22" alt="YouTube">',
                            'youtube_music'     => '<img src="https://upload.wikimedia.org/wikipedia/commons/6/6a/Youtube_Music_icon.svg" width="22" alt="YouTube Music">',
                            'youtube-music'     => '<img src="https://upload.wikimedia.org/wikipedia/commons/6/6a/Youtube_Music_icon.svg" width="22" alt="YouTube Music">',
                            'youtube_premium'   => '<img src="https://upload.wikimedia.org/wikipedia/commons/6/6a/Youtube_Music_icon.svg" width="22" alt="YouTube Premium">',
                            'tiktok'            => '<img src="https://upload.wikimedia.org/wikipedia/commons/e/e9/Tiktok_icon.svg" width="22" alt="TikTok">',
                            'tiktok_music'      => '<img src="https://upload.wikimedia.org/wikipedia/commons/e/e9/Tiktok_icon.svg" width="22" alt="TikTok Music">',
                            'facebook'          => '<img src="https://upload.wikimedia.org/wikipedia/commons/0/05/Facebook_Logo_%282019%29.png" width="22" alt="Facebook">',
                            'instagram'         => '<img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Instagram_icon.png" width="22" alt="Instagram">',
                            'snapchat'          => '<img src="https://upload.wikimedia.org/wikipedia/commons/c/c4/Snapchat_logo.svg" width="22" alt="Snapchat">',
                            'twitter'           => '<img src="https://upload.wikimedia.org/wikipedia/commons/5/57/X_logo_2023_%28white%29.png" width="22" alt="X / Twitter">',
                            'x'                 => '<img src="https://upload.wikimedia.org/wikipedia/commons/5/57/X_logo_2023_%28white%29.png" width="22" alt="X">',
                            'twitch'            => '<img src="https://upload.wikimedia.org/wikipedia/commons/d/d3/Twitch_Glitch_Logo_Purple.svg" width="22" alt="Twitch">',
                            'triller'           => '<img src="https://upload.wikimedia.org/wikipedia/commons/0/05/Triller_app_logo.png" width="22" alt="Triller">',
                            'kwai'              => '<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a9/Kwai_logo.png/240px-Kwai_logo.png" width="22" alt="Kwai">',

                            // ── Lojas / download ──────────────────────────────────────────────────
                            'itunes'            => '<img src="https://upload.wikimedia.org/wikipedia/commons/5/5f/Apple_Music_icon.svg" width="22" alt="iTunes">',
                            'itunes_store'      => '<img src="https://upload.wikimedia.org/wikipedia/commons/5/5f/Apple_Music_icon.svg" width="22" alt="iTunes Store">',
                            'google_play'       => '<img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" width="40" alt="Google Play">',
                            'google-play'       => '<img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" width="40" alt="Google Play">',
                            'beatport'          => '<img src="https://upload.wikimedia.org/wikipedia/commons/a/ac/Beatport-logo.svg" width="22" alt="Beatport">',
                            'traxsource'        => '<i class="bi bi-vinyl fs-5" style="color:#00b4d8;"></i>',
                            'juno_download'     => '<i class="bi bi-download fs-5 text-secondary"></i>',
                            'bandcamp'          => '<img src="https://upload.wikimedia.org/wikipedia/commons/b/b3/Bandcamp-button-bc-circle-black.svg" width="22" alt="Bandcamp">',
                            'amazon_download'   => '<img src="https://upload.wikimedia.org/wikipedia/commons/d/d5/Amazon_Music_logo.svg" width="22" alt="Amazon Download">',
                            '7digital'          => '<i class="bi bi-7-circle fs-5 text-danger"></i>',

                            // ── Rádio / podcasts ─────────────────────────────────────────────────
                            'iheartradio'       => '<img src="https://upload.wikimedia.org/wikipedia/commons/1/16/IHeartRadio_logo.svg" width="22" alt="iHeartRadio">',
                            'iheart'            => '<img src="https://upload.wikimedia.org/wikipedia/commons/1/16/IHeartRadio_logo.svg" width="22" alt="iHeart">',
                            'tunein'            => '<img src="https://upload.wikimedia.org/wikipedia/commons/a/a8/TuneIn_logo.svg" width="22" alt="TuneIn">',

                            // ── Regionais / outros ────────────────────────────────────────────────
                            'claro_music'       => '<i class="bi bi-vinyl fs-5 text-danger"></i>',
                            'claro-music'       => '<i class="bi bi-vinyl fs-5 text-danger"></i>',
                            'netease'           => '<img src="https://upload.wikimedia.org/wikipedia/commons/f/fd/NetEase_Music.png" width="22" alt="NetEase">',
                            'qq_music'          => '<img src="https://upload.wikimedia.org/wikipedia/commons/2/29/QQ_Music_Logo.png" width="22" alt="QQ Music">',
                            'joox'              => '<img src="https://upload.wikimedia.org/wikipedia/commons/f/f8/Joox-logo.svg" width="22" alt="JOOX">',
                            'melon'             => '<img src="https://upload.wikimedia.org/wikipedia/commons/7/75/Melon_Logo.svg" width="22" alt="Melon">',
                            'bugs'              => '<i class="bi bi-music-note-beamed fs-5" style="color:#ff6b6b;"></i>',
                            'naver_music'       => '<i class="bi bi-music-note fs-5 text-success"></i>',
                            'vibe'              => '<i class="bi bi-soundwave fs-5" style="color:#7209b7;"></i>',
                            'saavn'             => '<img src="https://upload.wikimedia.org/wikipedia/commons/2/29/JioSaavn_Logo.svg" width="22" alt="Saavn">',
                            'zvuk'              => '<i class="bi bi-headphones fs-5" style="color:#7c3aed;"></i>',
                            'tencent'           => '<img src="https://upload.wikimedia.org/wikipedia/commons/2/29/QQ_Music_Logo.png" width="22" alt="Tencent">',

                            // ── Fallback ──────────────────────────────────────────────────────────
                            'default'           => '<i class="bi bi-music-note-beamed fs-5 text-muted"></i>',
                        ];
                        if (!empty($streams)):
                            foreach (array_slice($streams, 0, 6) as $s):
                                $slug = $s['slug_store'];
                                $icon = $platform_icons[$slug] ?? '<i class="bi bi-music-note-beamed fs-5 text-muted me-2"></i>';
                        ?>
                    <li>
                        <div class="platform-info">
                            <?php echo $icon; ?>
                            <span class="me-3"> <?php echo htmlspecialchars($s['name_store']); ?></span>
                        </div>
                        <span class="stream-count"
                            style="color: #ff0089"><?php echo number_format($s['total'], 0, ',', '.'); ?> streams</span>
                    </li>
                    <?php endforeach;
                        else: ?>
                    <li class="text-muted">Nenhuma plataforma com streams ainda.</li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <div class="card mb-4">
            <div class="card">
                <a href="statistics" class="d-block text-center mt-3 btn btn-pink">
                    <i class="bi bi-bar-chart me-2"></i> Ver todas as estatísticas
                </a>
            </div>
        </div>

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
                        <form method="post" action="finances/withdrawal_process" class="needs-validation row g-3"
                            novalidate id="withdrawal-form">
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
                                    placeholder="Senha da tua conta <?php echo APP_NAME  ?>"
                                    autocomplete="current-password">
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
                            <a href="finances/withdraw" class="btn btn-pink btn-sm">
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
    </div>


    <script>
    // ── Badge de notificações — polling leve a cada 60s ──────────
    (function() {
        function refreshNotifBadge() {
            fetch('ajax/notifications_api?action=count', {
                    credentials: 'same-origin'
                })
                .then(r => r.json())
                .then(data => {
                    var badge = document.getElementById('navNotifBadge');
                    if (!badge) return;
                    var count = parseInt(data.unread || 0);
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.style.display = '';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(function() {});
        }
        // Primeira actualização após 30s para não sobrecarregar o load inicial
        setTimeout(function() {
            refreshNotifBadge();
            setInterval(refreshNotifBadge, 60000);
        }, 30000);
    })();
    </script>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <script>
    const tooltipTriggerList = document.querySelectorAll(
        '[data-bs-toggle="tooltip"]'
    );
    const tooltipList = [...tooltipTriggerList].map(
        (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
    );
    </script>
    <script>
    // ── Gráfico de streams — dados reais da BD ──────────────
    <?php if ($has_streams && !empty($chart_datasets)): ?>
        (function() {
            const canvas = document.getElementById('streamChart');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo $chart_json_labels; ?>,
                    datasets: <?php echo $chart_json_datasets; ?>
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            stacked: true,
                            ticks: {
                                callback: v => v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v
                            },
                            title: {
                                display: true,
                                text: 'Streams'
                            }
                        },
                        x: {
                            stacked: true,
                            title: {
                                display: true,
                                text: 'Período'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: ctx =>
                                    ` ${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString('pt-AO')} streams`
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        })();
    <?php endif; ?>
    </script>

    <script>
    // ══════════════════════════════════════════════════
    // ONBOARDING — lógica (TOTAL agora é 5)
    // CSRF lido do meta tag — não hardcoded em PHP inline
    // ══════════════════════════════════════════════════
    (function() {
        const TOTAL = 5;
        let current = 1;

        const modal = document.getElementById('onboardingModal');
        if (!modal) return;

        const btnNext = document.getElementById('ob-next');
        const btnPrev = document.getElementById('ob-prev');
        const btnSkip = document.getElementById('ob-skip-artist');
        const dots = document.querySelectorAll('.ob-dot');
        const progBar = document.getElementById('ob-progress-bar');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? '';

        const bsModal = new bootstrap.Modal(modal, {
            backdrop: 'static'
        });
        bsModal.show();

        function goTo(n) {
            document.getElementById('ob-' + current).classList.add('d-none');
            current = n;
            document.getElementById('ob-' + current).classList.remove('d-none');

            // Dots
            dots.forEach((d, i) => d.classList.toggle('active', i + 1 === current));

            // Barra de progresso
            if (progBar) progBar.style.width = ((current / TOTAL) * 100) + '%';

            // Botões do footer
            btnPrev.classList.toggle('d-none', current === 1);
            btnNext.classList.toggle('d-none', current === TOTAL);
        }

        btnNext.addEventListener('click', () => {
            if (current < TOTAL) goTo(current + 1);
        });
        btnPrev.addEventListener('click', () => {
            if (current > 1) goTo(current - 1);
        });

        // "Criar mais tarde" — salta para o step final
        if (btnSkip) btnSkip.addEventListener('click', () => goTo(TOTAL));

        // Push notifications — pedido de permissão
        window.obRequestPush = function(btn) {
            if (!('Notification' in window) || !('serviceWorker' in navigator)) {
                document.getElementById('ob-push-status').textContent = 'Não suportado neste dispositivo.';
                btn.disabled = true;
                return;
            }
            if (Notification.permission === 'granted') {
                document.getElementById('ob-push-status').innerHTML =
                    '<i class="bi bi-check-circle-fill text-success"></i> Já activadas';
                btn.disabled = true;
                return;
            }
            Notification.requestPermission().then(function(perm) {
                if (perm === 'granted') {
                    document.getElementById('ob-push-status').innerHTML =
                        '<i class="bi bi-check-circle-fill text-success"></i> Activadas!';
                    btn.disabled = true;
                    btn.innerHTML = '<i class="bi bi-bell-fill me-1"></i>Notificações activas';
                    btn.classList.replace('btn-outline-secondary', 'btn-outline-success');
                } else {
                    document.getElementById('ob-push-status').textContent =
                        'Podes activar nas definições do browser.';
                    btn.disabled = true;
                }
            });
        };

        // finishOnboarding — chamado pelo botão no step 5
        window.finishOnboarding = function() {
            var btn = document.getElementById('ob-finish');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A guardar…';
            }
            fetch('onboarding_done', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        csrf: csrfToken
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        bsModal.hide();
                        window.location.reload();
                    } else {
                        bsModal.hide();
                    }
                })
                .catch(() => bsModal.hide());
        };
    })();
    </script>
</body>

</html>