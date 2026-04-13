<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Conta e Serviços Disponíveis
// Arquivo: dashboard/services/available-services.php
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


$first_name       = htmlspecialchars($user['first_name'] ?? '');
$last_name        = htmlspecialchars($user['second_name'] ?? '');
$user_artist_name = htmlspecialchars($user['name_artist_band'] ?? $user['first_name'] ?? '');

// ── Plano activo do utilizador ────────────────
$active_plan = null;
try {
    $q = $db->prepare("
        SELECT p.id_plan, p.name_plan, p.slug_plan, p.price_plan, p.type_plan,
               p.royalty_rate, p.max_artists, p.max_releases, p.badge_text,
               up.status_plan, up.started_at, up.expires_at, up.releases_used, up.releases_limit
        FROM _user_plan up
        JOIN _plans p ON p.id_plan = up.id_plan
        WHERE up.id_users = ? AND up.status_plan = 'active'
        ORDER BY up.started_at DESC
        LIMIT 1
    ");
    $q->execute([$id_users]);
    $active_plan = $q->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $e) {
}

// Fallback: plan_selected no _users
if (!$active_plan && !empty($user['plan_selected'])) {
    try {
        $q2 = $db->prepare("
            SELECT id_plan, name_plan, slug_plan, price_plan, type_plan,
                   royalty_rate, max_artists, max_releases, badge_text
            FROM _plans WHERE id_plan = ?
        ");
        $q2->execute([$user['plan_selected']]);
        $row = $q2->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $active_plan = array_merge($row, [
                'status_plan'    => 'active',
                'started_at'     => $user['plan_activated_at'] ?? null,
                'expires_at'     => $user['plan_expires_at']   ?? null,
                'releases_used'  => 0,
                'releases_limit' => $row['max_releases'],
            ]);
        }
    } catch (PDOException $e) {
    }
}

// ── Todos os planos ───────────────────────────
$all_plans = [];
try {
    $q3 = $db->prepare("
        SELECT id_plan, name_plan, slug_plan, price_plan, price_annual, annual_qty,
               type_plan, royalty_rate, max_artists, max_releases, max_tracks_per_release,
               validity_days, badge_text, is_featured, img_plan
        FROM _plans
        WHERE is_active = 1
        ORDER BY display_order ASC
    ");
    $q3->execute();
    $all_plans = $q3->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}

// ── Features por plano ────────────────────────
$features_by_plan = [];
try {
    $q4 = $db->prepare("
        SELECT id_plan, feature_text, is_included
        FROM _plan_features
        ORDER BY id_plan, display_order ASC
    ");
    $q4->execute();
    foreach ($q4->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $features_by_plan[$f['id_plan']][] = $f;
    }
} catch (PDOException $e) {
}

// ── Ícone por slug ────────────────────────────
$plan_icons = [
    'single' => 'bi-music-note',
    'album'  => 'bi-disc-fill',
    'artist' => 'bi-person-badge-fill',
    'label'  => 'bi-building-fill',
];
$plan_colors = [
    'single' => '#6c757d',
    'album'  => '#0d6efd',
    'artist' => '#FF0089',
    'label'  => '#198754',
];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title>Conta e Serviços — <?php echo APP_NAME; ?></title>
    <style>
        /* ══ Header ══ */
        .services-header {
            background: linear-gradient(135deg, #FF0089 0%, #c8006e 55%, #7b0044 100%);
            border-radius: 20px;
            padding: 2.2rem 2.5rem;
            margin-bottom: 2rem;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .services-header::after {
            content: '\F541';
            font-family: 'bootstrap-icons';
            position: absolute;
            right: -20px;
            bottom: -24px;
            font-size: 9rem;
            opacity: .07;
            color: #fff;
            transform: rotate(15deg);
        }

        .services-header .header-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: 999px;
            padding: 4px 14px;
            font-size: .78rem;
            font-weight: 700;
            backdrop-filter: blur(4px);
            margin-bottom: .8rem;
        }

        /* ══ Plan cards ══ */
        .plan-card {
            border-radius: 18px;
            border: 2px solid var(--border-color, rgba(0, 0, 0, .08));
            transition: transform .2s, box-shadow .2s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .plan-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(255, 0, 137, .15);
        }

        .plan-card.active-plan {
            border-color: #FF0089;
            box-shadow: 0 0 0 3px rgba(255, 0, 137, .15), 0 8px 24px rgba(255, 0, 137, .12);
        }

        .plan-card.featured-plan {
            border-color: #FF0089;
        }

        .plan-card-header {
            padding: 1.4rem 1.4rem .8rem;
            border-radius: 16px 16px 0 0;
            position: relative;
        }

        .plan-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: .7rem;
        }

        .plan-name {
            font-size: 1.1rem;
            font-weight: 800;
            margin: 0;
        }

        .plan-badge-top {
            position: absolute;
            top: 14px;
            right: 14px;
            font-size: .68rem;
            font-weight: 700;
            border-radius: 999px;
            padding: 3px 10px;
        }

        .plan-price {
            font-size: 1.8rem;
            font-weight: 900;
            line-height: 1;
            color: #FF0089;
            margin: .5rem 0 .2rem;
        }

        .plan-price small {
            font-size: .8rem;
            font-weight: 500;
            color: var(--text-muted, #6c757d);
        }

        .plan-price-annual {
            font-size: .75rem;
            color: var(--text-muted, #6c757d);
            margin-bottom: .8rem;
        }

        .plan-features-list {
            list-style: none;
            padding: 0;
            margin: 0;
            flex: 1;
        }

        .plan-features-list li {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 5px 0;
            font-size: .82rem;
            border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .05));
        }

        .plan-features-list li:last-child {
            border-bottom: none;
        }

        .plan-features-list li.not-included {
            opacity: .45;
            text-decoration: line-through;
        }

        .plan-features-list li .feat-icon {
            font-size: .85rem;
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* ══ Current plan info card ══ */
        .current-plan-card {
            border-radius: 18px;
            padding: 1.5rem;
            border: 2px solid #FF0089;
            background: linear-gradient(135deg, rgba(255, 0, 137, .04), transparent);
        }

        .current-plan-stat {
            background: var(--metric-bg, rgba(0, 0, 0, .03));
            border-radius: 12px;
            padding: .85rem 1rem;
            text-align: center;
        }

        .current-plan-stat .stat-val {
            font-size: 1.3rem;
            font-weight: 800;
            color: #FF0089;
        }

        .current-plan-stat .stat-lbl {
            font-size: .7rem;
            color: var(--text-muted, #6c757d);
            margin-top: 2px;
        }

        /* ══ Section header ══ */
        .section-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #FF0089;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1.2rem;
        }

        /* ══ CTA contact ══ */
        .contact-info-bar {
            background: var(--metric-bg, rgba(0, 0, 0, .03));
            border-radius: 14px;
            padding: 1rem 1.4rem;
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        @media(max-width: 768px) {
            .services-header {
                padding: 1.5rem;
            }

            .plan-price {
                font-size: 1.4rem;
            }
        }
    </style>
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

        <!-- ══ HEADER ══ -->
        <div class="services-header">
            <div class="header-badge">
                <?php if ($active_plan): ?>
                    <i class="bi bi-star-fill"></i>
                    Plano <?php echo htmlspecialchars($active_plan['name_plan']); ?> activo
                <?php else: ?>
                    <i class="bi bi-star"></i>
                    Sem plano activo
                <?php endif; ?>
            </div>
            <h1 class="fw-bold mb-1"><i class="bi bi-layers-fill me-2"></i>Conta e Serviços</h1>
            <p class="lead mb-0" style="opacity:.85">
                Gere o teu plano, consulta os benefícios incluídos e compara todas as opções disponíveis.
            </p>
        </div>

        <!-- ══ PLANO ACTUAL ══ -->
        <div class="section-title"><i class="bi bi-shield-check"></i>O teu plano actual</div>

        <?php if ($active_plan): ?>
            <?php
            $slug        = $active_plan['slug_plan'];
            $plan_color  = $plan_colors[$slug]  ?? '#FF0089';
            $plan_icon   = $plan_icons[$slug]   ?? 'bi-star-fill';
            $is_sub      = $active_plan['type_plan'] === 'subscription';
            $expires_fmt = !empty($active_plan['expires_at'])
                ? date('d/m/Y', strtotime($active_plan['expires_at']))
                : ($is_sub ? '—' : 'Sem expiração');
            $started_fmt = !empty($active_plan['started_at'])
                ? date('d/m/Y', strtotime($active_plan['started_at']))
                : '—';
            $releases_used  = (int)($active_plan['releases_used'] ?? 0);
            $releases_limit = $active_plan['releases_limit'];
            ?>
            <div class="current-plan-card mb-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="plan-icon"
                        style="background:<?php echo $plan_color; ?>22; color:<?php echo $plan_color; ?>">
                        <i class="bi <?php echo $plan_icon; ?>"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($active_plan['name_plan']); ?>
                            <?php if ($active_plan['badge_text']): ?>
                                <span class="badge ms-2" style="background:<?php echo $plan_color; ?>;font-size:.65rem">
                                    <?php echo htmlspecialchars($active_plan['badge_text']); ?>
                                </span>
                            <?php endif; ?>
                        </h4>
                        <span class="badge bg-success">Activo</span>
                        <span class="badge ms-1" style="background:rgba(0,0,0,.12);color:#FF0089">
                            <?php echo $is_sub ? 'Subscrição anual' : 'Por lançamento'; ?>
                        </span>
                    </div>
                    <div class="ms-auto text-end">
                        <div style="font-size:1.4rem;font-weight:900;color:#FF0089">
                            <?php echo number_format($active_plan['price_plan'], 0, ',', '.'); ?> AOA
                        </div>
                        <div style="font-size:.72rem;color:var(--text-muted,#6c757d)">
                            <?php echo $is_sub ? 'por ano' : 'por lançamento'; ?>
                        </div>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="current-plan-stat">
                            <div class="stat-val"><?php echo $active_plan['royalty_rate']; ?>%</div>
                            <div class="stat-lbl">Royalties</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="current-plan-stat">
                            <div class="stat-val"><?php echo $active_plan['max_artists'] ?? '∞'; ?></div>
                            <div class="stat-lbl">Artistas</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="current-plan-stat">
                            <div class="stat-val"><?php echo $started_fmt; ?></div>
                            <div class="stat-lbl">Activado em</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="current-plan-stat">
                            <div class="stat-val" style="font-size:1rem">
                                <?php echo $is_sub ? $expires_fmt : ($releases_limit ? "$releases_used / $releases_limit" : '∞'); ?>
                            </div>
                            <div class="stat-lbl"><?php echo $is_sub ? 'Validade' : 'Lançamentos usados'; ?></div>
                        </div>
                    </div>
                </div>

                <?php if (!$is_sub && $releases_limit): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1" style="font-size:.78rem">
                            <span>Lançamentos utilizados</span>
                            <strong><?php echo $releases_used; ?> / <?php echo $releases_limit; ?></strong>
                        </div>
                        <div class="progress" style="height:6px;border-radius:999px">
                            <div class="progress-bar" role="progressbar"
                                style="width:<?php echo min(100, round($releases_used / $releases_limit * 100)); ?>%;background:#FF0089;border-radius:999px">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="d-flex flex-wrap gap-2 mt-2">
                    <a href="#all-plans" class="btn btn-sm"
                        style="background:linear-gradient(135deg,#FF0089,#c8006e);color:#fff;border:none;border-radius:9px;font-weight:600">
                        <i class="bi bi-arrow-up-circle me-1"></i>Alterar plano
                    </a>
                    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/support"
                        class="btn btn-sm btn-outline-secondary" style="border-radius:9px">
                        <i class="bi bi-headset me-1"></i>Contactar suporte
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert" style="border-radius:14px;border:2px dashed #FF0089;background:rgba(255,0,137,.04)"
                role="alert">
                <i class="bi bi-info-circle me-2" style="color:#FF0089"></i>
                Ainda não tens um plano activo. Escolhe um plano abaixo para começar a distribuir a tua música.
            </div>
        <?php endif; ?>

        <!-- ══ TODOS OS PLANOS ══ -->
        <div class="section-title mt-2" id="all-plans">
            <i class="bi bi-grid-3x2-gap"></i>Todos os planos disponíveis
        </div>

        <?php if (!empty($all_plans)): ?>
            <div class="row g-3 mb-4">
                <?php foreach ($all_plans as $plan):
                    $is_active_p = $active_plan && $active_plan['id_plan'] == $plan['id_plan'];
                    $pslug   = $plan['slug_plan'];
                    $pcolor  = $plan_colors[$pslug]  ?? '#FF0089';
                    $picon   = $plan_icons[$pslug]   ?? 'bi-star';
                    $is_sub_p = $plan['type_plan'] === 'subscription';
                    $feats   = $features_by_plan[$plan['id_plan']] ?? [];
                ?>
                    <div class="col-sm-6 col-lg-3">
                        <div
                            class="plan-card <?php echo $is_active_p ? 'active-plan' : ''; ?> <?php echo $plan['is_featured'] && !$is_active_p ? 'featured-plan' : ''; ?>">
                            <div class="plan-card-header">
                                <?php if ($is_active_p): ?>
                                    <span class="plan-badge-top badge bg-success">Activo</span>
                                <?php elseif ($plan['is_featured']): ?>
                                    <span class="plan-badge-top badge" style="background:#FF0089">Recomendado</span>
                                <?php elseif ($plan['badge_text']): ?>
                                    <span
                                        class="plan-badge-top badge bg-secondary"><?php echo htmlspecialchars($plan['badge_text']); ?></span>
                                <?php endif; ?>

                                <div class="plan-icon" style="background:<?php echo $pcolor; ?>18;color:<?php echo $pcolor; ?>">
                                    <i class="bi <?php echo $picon; ?>"></i>
                                </div>
                                <p class="plan-name"><?php echo htmlspecialchars($plan['name_plan']); ?></p>
                                <div class="plan-price">
                                    <?php echo number_format($plan['price_plan'], 0, ',', '.'); ?> <small>AOA</small>
                                </div>
                                <div class="plan-price-annual">
                                    <?php if ($is_sub_p): ?>
                                        por ano
                                    <?php elseif ($plan['price_annual'] && $plan['annual_qty']): ?>
                                        ou <?php echo number_format($plan['price_annual'], 0, ',', '.'); ?> AOA (pacote
                                        <?php echo $plan['annual_qty']; ?>x)
                                    <?php else: ?>
                                        por lançamento
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="px-3 pb-2" style="flex:1">
                                <ul class="plan-features-list">
                                    <?php foreach ($feats as $f): ?>
                                        <li class="<?php echo $f['is_included'] ? '' : 'not-included'; ?>">
                                            <i
                                                class="feat-icon bi <?php echo $f['is_included'] ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger'; ?>"></i>
                                            <?php echo htmlspecialchars($f['feature_text']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (empty($feats)): ?>
                                        <li><i class="feat-icon bi bi-check-circle-fill text-success"></i> Distribuição em 157+
                                            lojas</li>
                                        <li><i class="feat-icon bi bi-check-circle-fill text-success"></i>
                                            <?php echo $plan['royalty_rate']; ?>% dos royalties</li>
                                    <?php endif; ?>
                                </ul>
                            </div>

                            <div class="p-3 pt-2">
                                <?php if ($is_active_p): ?>
                                    <button class="btn w-100 btn-sm" disabled
                                        style="background:linear-gradient(135deg,#FF0089,#c8006e);color:#fff;border:none;border-radius:9px;font-weight:700;opacity:.7">
                                        <i class="bi bi-check-lg me-1"></i>Plano actual
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/support?plano=<?php echo urlencode($plan['slug_plan']); ?>"
                                        class="btn w-100 btn-sm btn-outline-secondary" style="border-radius:9px;font-weight:600">
                                        <i class="bi bi-headset me-1"></i>Contactar para activar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ══ NOTA / CTA ══ -->
        <div class="contact-info-bar mb-4">
            <i class="bi bi-info-circle-fill fs-4" style="color:#FF0089;flex-shrink:0"></i>
            <div style="flex:1">
                <div class="fw-semibold" style="font-size:.9rem">Pretende alterar o teu plano?</div>
                <div class="text-muted" style="font-size:.8rem">
                    Para efectuar alterações ao plano activo, entra em contacto com a nossa equipa de suporte.
                    Vamos ajudar-te a encontrar o plano certo para o teu projecto musical.
                </div>
            </div>
            <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/page/support" class="btn btn-sm flex-shrink-0"
                style="background:linear-gradient(135deg,#FF0089,#c8006e);color:#fff;border:none;border-radius:9px;font-weight:600;white-space:nowrap">
                <i class="bi bi-headset me-1"></i>Ir para suporte
            </a>
        </div>

        </main>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
        <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
        <script>
            // ── Badge de notificações — polling leve a cada 60s ──────────
            (function() {
                function refreshNotifBadge() {
                    fetch('http://localhost/wasomupfy/dashboard/ajax/notifications_api.php?action=count', {
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
</body>

</html>