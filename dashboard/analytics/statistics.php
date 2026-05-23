<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0.1.1 — Estatísticas
// Arquivo: dashboard/analytics/statistics.php
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

// ── Filtros da query string ────────────────────
$filter_year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$filter_store = isset($_GET['store']) ? (int)$_GET['store'] : 0; // 0 = todos

// ── Lojas activas ──────────────────────────────
$stores_q = $db->prepare("SELECT id_store, name_store, slug_store FROM _store WHERE is_active = 1 ORDER BY display_order ASC");
$stores_q->execute();
$stores = $stores_q->fetchAll(PDO::FETCH_ASSOC);
$store_map = array_column($stores, null, 'id_store');

// ── Anos disponíveis (para o selector) ────────
$years_q = $db->prepare("
    SELECT DISTINCT s.year_stream
    FROM _stream s
    JOIN _track t ON t.id_track = s.id_track
    WHERE t.id_users = ?
    ORDER BY s.year_stream DESC
");
$years_q->execute([$id_users]);
$available_years = $years_q->fetchAll(PDO::FETCH_COLUMN);
if (empty($available_years)) $available_years = [(int)date('Y')];

// ── TOTAIS GLOBAIS do ano ─────────────────────
$totals_q = $db->prepare("
    SELECT
        COALESCE(SUM(s.streams), 0)   AS total_streams,
        COALESCE(SUM(s.downloads), 0) AS total_downloads,
        COALESCE(SUM(s.revenue), 0)   AS total_revenue
    FROM _stream s
    JOIN _track t ON t.id_track = s.id_track
    WHERE t.id_users = ?
      AND s.year_stream = ?
      " . ($filter_store ? "AND s.id_store = ?" : "") . "
");
$params_totals = [$id_users, $filter_year];
if ($filter_store) $params_totals[] = $filter_store;
$totals_q->execute($params_totals);
$totals = $totals_q->fetch();

// ── STREAMS POR PLATAFORMA (tabela de plataformas) ──
$platforms_q = $db->prepare("
    SELECT
        s.id_store,
        st.name_store,
        st.slug_store,
        COALESCE(SUM(s.streams), 0)   AS total_streams,
        COALESCE(SUM(s.downloads), 0) AS total_downloads,
        COALESCE(SUM(s.revenue), 0)   AS total_revenue
    FROM _stream s
    JOIN _track t  ON t.id_track   = s.id_track
    JOIN _store st ON st.id_store  = s.id_store
    WHERE t.id_users = ?
      AND s.year_stream = ?
    GROUP BY s.id_store, st.name_store, st.slug_store
    ORDER BY total_streams DESC
");
$platforms_q->execute([$id_users, $filter_year]);
$platforms_data = $platforms_q->fetchAll(PDO::FETCH_ASSOC);

// ── STREAMS POR MÊS + PLATAFORMA (dados para o gráfico) ──
$chart_q = $db->prepare("
    SELECT
        s.month_stream,
        s.id_store,
        st.name_store,
        st.slug_store,
        COALESCE(SUM(s.streams), 0) AS streams
    FROM _stream s
    JOIN _track t  ON t.id_track  = s.id_track
    JOIN _store st ON st.id_store = s.id_store
    WHERE t.id_users = ?
      AND s.year_stream = ?
      " . ($filter_store ? "AND s.id_store = ?" : "") . "
    GROUP BY s.month_stream, s.id_store, st.name_store, st.slug_store
    ORDER BY s.month_stream ASC, st.display_order ASC
");
$params_chart = [$id_users, $filter_year];
if ($filter_store) $params_chart[] = $filter_store;
$chart_q->execute($params_chart);
$chart_raw = $chart_q->fetchAll(PDO::FETCH_ASSOC);

// Organizar dados do gráfico por plataforma e mês
$chart_stores = [];
$chart_by_store = [];
foreach ($chart_raw as $row) {
    $mid = (int)$row['month_stream'];
    $sid = (int)$row['id_store'];
    if (!isset($chart_stores[$sid])) {
        $chart_stores[$sid] = ['name' => $row['name_store'], 'slug' => $row['slug_store']];
        $chart_by_store[$sid] = array_fill(1, 12, 0);
    }
    $chart_by_store[$sid][$mid] = (int)$row['streams'];
}

// Paleta de cores por slug
$store_colors = [
    'spotify'       => ['border' => '#1db954', 'bg' => 'rgba(29,185,84,0.45)'],
    'apple-music'   => ['border' => '#fc3c44', 'bg' => 'rgba(252,60,68,0.45)'],
    'amazon-music'  => ['border' => '#00a8e0', 'bg' => 'rgba(0,168,224,0.45)'],
    'deezer'        => ['border' => '#ff0089', 'bg' => 'rgba(255,0,137,0.45)'],
    'tidal'         => ['border' => '#00ffff', 'bg' => 'rgba(0,255,255,0.3)'],
    'youtube-music' => ['border' => '#ff0000', 'bg' => 'rgba(255,0,0,0.4)'],
    'boomplay'      => ['border' => '#f5a623', 'bg' => 'rgba(245,166,35,0.4)'],
    'tiktok'        => ['border' => '#69c9d0', 'bg' => 'rgba(105,201,208,0.4)'],
    'default'       => ['border' => '#aaa', 'bg' => 'rgba(170,170,170,0.3)'],
];

// Construir datasets JSON para Chart.js
$chart_datasets = [];
foreach ($chart_stores as $sid => $sinfo) {
    $slug   = $sinfo['slug'];
    $colors = $store_colors[$slug] ?? $store_colors['default'];
    $chart_datasets[] = [
        'label'           => $sinfo['name'],
        'data'            => array_values($chart_by_store[$sid]),
        'borderColor'     => $colors['border'],
        'backgroundColor' => $colors['bg'],
        'fill'            => true,
        'stack'           => 'combined',
        'tension'         => 0.3,
    ];
}

// Labels dos 12 meses
$months_pt_short = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

// ── TOP ARTISTAS por streams ───────────────────
$artists_q = $db->prepare("
    SELECT
        a.id_artist,
        a.stage_name,
        a.photo_artist,
        COALESCE(SUM(s.streams), 0) AS total_streams
    FROM _artist a
    LEFT JOIN _album al ON al.id_artist = a.id_artist AND al.status_album IN ('approved','active')
    LEFT JOIN _track t ON t.id_album = al.id_album AND t.status_track IN ('active','approved')
    LEFT JOIN _stream s ON s.id_track = t.id_track AND s.year_stream = ?
    WHERE a.id_users = ?
      AND a.status_artist != 'blocked'
    GROUP BY a.id_artist
    ORDER BY total_streams DESC
    LIMIT 20
");
$artists_q->execute([$filter_year, $id_users]);
$artists_data = $artists_q->fetchAll(PDO::FETCH_ASSOC);

$artists_q = $db->prepare("
    SELECT
        a.id_artist,
        a.stage_name,
        a.photo_artist,
        COALESCE(SUM(s.streams), 0) AS total_streams,
        (
            SELECT al.img_cover
            FROM _album al
            JOIN _track t2 ON t2.id_album = al.id_album
            JOIN _stream s2 ON s2.id_track = t2.id_track
            WHERE al.id_artist = a.id_artist AND s2.year_stream = ?
            GROUP BY al.id_album
            ORDER BY SUM(s2.streams) DESC
            LIMIT 1
        ) AS top_album_cover,
        (
            SELECT al.title_album
            FROM _album al
            JOIN _track t2 ON t2.id_album = al.id_album
            JOIN _stream s2 ON s2.id_track = t2.id_track
            WHERE al.id_artist = a.id_artist AND s2.year_stream = ?
            GROUP BY al.id_album
            ORDER BY SUM(s2.streams) DESC
            LIMIT 1
        ) AS top_album_title
    FROM _artist a
    LEFT JOIN _album al ON al.id_artist = a.id_artist AND al.status_album IN ('approved','active')
    LEFT JOIN _track t ON t.id_album = al.id_album AND t.status_track IN ('active','approved')
    LEFT JOIN _stream s ON s.id_track = t.id_track AND s.year_stream = ?
    WHERE a.id_users = ?
      AND a.status_artist != 'blocked'
    GROUP BY a.id_artist
    ORDER BY total_streams DESC
    LIMIT 20
");
$artists_q->execute([$filter_year, $filter_year, $filter_year, $id_users]);
$artists_data = $artists_q->fetchAll(PDO::FETCH_ASSOC);

// Fallback: artistas sem streams também aparecem
if (empty($artists_data)) {
    $art_fallback = $db->prepare("SELECT id_artist, stage_name, photo_artist FROM _artist WHERE id_users = ? AND status_artist != 'blocked' LIMIT 10");
    $art_fallback->execute([$id_users]);
    $artists_data = $art_fallback->fetchAll(PDO::FETCH_ASSOC);
    foreach ($artists_data as &$a) $a['total_streams'] = 0;
    unset($a);
}

// ── PAÍSES (dados reais da _stream_country) ─────────────────────
$countries_data = [];
$countries_q = $db->prepare("
    SELECT
        LOWER(sc.country_code) AS country_code,
        sc.country_name,
        COUNT(DISTINCT t.id_track) AS num_tracks,
        COALESCE(SUM(sc.streams), 0) AS total_streams,
        COALESCE(SUM(sc.revenue), 0) AS total_revenue
    FROM _stream_country sc
    JOIN _track t ON t.id_track = sc.id_track
    WHERE t.id_users = ?
      AND sc.year_stream = ?
    GROUP BY sc.country_code, sc.country_name
    HAVING total_streams > 0
    ORDER BY total_streams DESC
    LIMIT 30
");
$countries_q->execute([$id_users, $filter_year]);
$countries_data = $countries_q->fetchAll(PDO::FETCH_ASSOC);


// ── TOP ÁLBUNS como "Playlists" ────────────────────────────────
$playlists_q = $db->prepare("
    SELECT
        al.id_album,
        al.title_album,
        al.type_album,
        al.img_cover,
        al.release_date,
        COALESCE(a.stage_name, u.name_artist_band, u.first_name) AS stage_name,
        COUNT(DISTINCT t.id_track)  AS num_tracks,
        COALESCE(SUM(s.streams), 0) AS total_streams,
        COALESCE(SUM(s.revenue), 0) AS total_revenue
    FROM _album al
    LEFT JOIN _artist a ON a.id_artist = al.id_artist
    LEFT JOIN _users  u ON u.id_users  = al.id_users
    LEFT JOIN _track  t ON t.id_album  = al.id_album AND t.status_track IN ('active','approved')
    LEFT JOIN _stream s ON s.id_track  = t.id_track
                       AND s.year_stream = ?
                       " . ($filter_store ? "AND s.id_store = ?" : "") . "
    WHERE al.id_users = ?
      AND al.status_album IN ('approved','active')
    GROUP BY al.id_album, al.title_album, al.type_album, al.img_cover,
             al.release_date, a.stage_name, u.name_artist_band, u.first_name
    ORDER BY total_streams DESC, al.release_date DESC
    LIMIT 20
");
$params_pl = [$filter_year];
if ($filter_store) $params_pl[] = $filter_store;
$params_pl[] = $id_users;
$playlists_q->execute($params_pl);
$playlists_data = $playlists_q->fetchAll(PDO::FETCH_ASSOC);

// ── TERRITÓRIOS (dos álbuns do utilizador) ─────────────────────
$territories_q = $db->prepare("
    SELECT
        al.territory,
        COUNT(DISTINCT al.id_album)  AS num_albums,
        COUNT(DISTINCT t.id_track)   AS num_tracks,
        COALESCE(SUM(s.streams), 0)  AS total_streams,
        COALESCE(SUM(s.revenue), 0)  AS total_revenue
    FROM _album al
    LEFT JOIN _track  t ON t.id_album  = al.id_album AND t.status_track IN ('active','approved')
    LEFT JOIN _stream s ON s.id_track  = t.id_track  AND s.year_stream = ?
    WHERE al.id_users = ?
      AND al.status_album IN ('approved','active')
      AND al.territory IS NOT NULL AND al.territory <> ''
    GROUP BY al.territory
    ORDER BY total_streams DESC, num_albums DESC
    LIMIT 20
");
$territories_q->execute([$filter_year, $id_users]);
$territories_data = $territories_q->fetchAll(PDO::FETCH_ASSOC);

$base_url      = rtrim(APP_URL, '/');
$cover_artists = $base_url . '/assets/comprovantes/uploads/artists/';
$cover_albums = $base_url . '/assets/comprovantes/uploads/covers/';
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title>Estatísticas — <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/statistics.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* ── Stat cards ── */
        .stat-hero-card {
            border-radius: 18px;
            padding: 20px 24px;
            position: relative;
            overflow: hidden;
            border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
            transition: box-shadow .2s;
        }

        .stat-hero-card:hover {
            box-shadow: 0 4px 24px rgba(255, 0, 137, .09);
        }

        .stat-hero-card .stat-label {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--text-muted, #6c757d);
            margin-bottom: 6px;
        }

        .stat-hero-card .stat-value {
            font-size: 1.8rem;
            font-weight: 900;
            line-height: 1;
        }

        .stat-hero-card .stat-sub {
            font-size: .75rem;
            color: var(--text-muted, #6c757d);
            margin-top: 4px;
        }

        .stat-hero-card .stat-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 3rem;
            opacity: .07;
        }

        /* ── Filtros ── */
        .filter-bar {
            border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }

        .filter-bar label {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--text-muted, #6c757d);
            display: block;
            margin-bottom: 4px;
        }

        .filter-bar select,
        .filter-bar input {
            font-size: .85rem;
            border-radius: 10px;
        }

        /* ── Plataformas list ── */
        .platform-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .06));
        }

        .platform-row:last-child {
            border-bottom: none;
        }

        .platform-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .platform-bar-bg {
            flex: 1;
            height: 6px;
            border-radius: 6px;
            background: var(--border-color, rgba(0, 0, 0, .07));
            overflow: hidden;
        }

        .platform-bar-fill {
            height: 100%;
            border-radius: 6px;
            transition: width .5s ease;
        }

        .platform-streams {
            font-size: .78rem;
            font-weight: 700;
            min-width: 80px;
            text-align: right;
        }

        /* ── Artists table ── */
        .artist-row-img {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            object-fit: cover;
            background: rgba(255, 0, 137, .08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .artist-row-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ── Empty state ── */
        .empty-section {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted, #6c757d);
        }

        .empty-section .icon {
            font-size: 2.5rem;
            opacity: .15;
            margin-bottom: 10px;
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
        <!-- header -->

        <div class="page-header mb-4">
            <div class="row align-items-center" style="position:relative;z-index:1">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb" style="margin-bottom:8px">
                        <ol class="breadcrumb mb-0" style="font-size:.90rem;opacity:.6">
                            <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/painel"
                                    class="text-white text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active text-white">Estatísticas</li>
                        </ol>
                    </nav>
                    <h1 class="fw-bold mb-1" style="font-size:1.6rem">
                        <i class="bi bi-bar-chart-fill me-2" style="color:#FF0089"></i>Estatísticas
                    </h1>
                    <p class="mb-0" style="font-size:.99rem;opacity:.7">
                        Stream Analytics — streams totais no Spotify, Apple Music, Deezer, Amazon, YouTube e muito
                        mais.
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#modalExport"
                        style="border:none;border-radius:20px;">
                        <i class="bi bi-download"></i> Exportar
                    </button>
                    <a href="<?php echo APP_URL . '/' . APP_URL_PANEL ?>/compare" class="btn btn-pink ms-2"
                        style="border-radius:20px">
                        <i class="bi bi-arrow-up-circle me-1"></i> Comparar
                    </a>
                </div>
            </div>
            <!-- Ícone decorativo: chart -->
            <style>
                .page-header::before {
                    content: '\F428';
                    /* bi-bar-chart-fill */
                }
            </style>
        </div>

        <!-- ── Barra de filtros ── -->
        <form method="GET" action="statistics">
            <div class="filter-bar">
                <div>
                    <label>Ano</label>
                    <select name="year" class="form-select form-select-sm" style="min-width:100px"
                        onchange="this.form.submit()">
                        <?php foreach ($available_years as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $filter_year ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Plataforma</label>
                    <select name="store" class="form-select form-select-sm" style="min-width:160px"
                        onchange="this.form.submit()">
                        <option value="0" <?php echo !$filter_store ? 'selected' : ''; ?>>Todas as plataformas</option>
                        <?php foreach ($stores as $st): ?>
                            <option value="<?php echo $st['id_store']; ?>"
                                <?php echo $st['id_store'] == $filter_store ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($st['name_store']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ms-auto d-flex align-items-end gap-2"
                    style="font-size:.78rem;color:var(--text-muted,#6c757d)">
                    <i class="bi bi-info-circle"></i>
                    A mostrar dados de <strong><?php echo $filter_year; ?></strong>
                    <?php if ($filter_store && isset($store_map[$filter_store])): ?>
                        — <?php echo htmlspecialchars($store_map[$filter_store]['name_store']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- ── Cards de totais ── -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-hero-card">
                    <div class="stat-label">Total de Streams</div>
                    <div class="stat-value" style="color:#FF0089">
                        <?php echo number_format((int)$totals['total_streams']); ?></div>
                    <div class="stat-sub">em <?php echo $filter_year; ?></div>
                    <i class="bi bi-headphones stat-icon"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-hero-card">
                    <div class="stat-label">Downloads</div>
                    <div class="stat-value" style="color:#0d6efd">
                        <?php echo number_format((int)$totals['total_downloads']); ?></div>
                    <div class="stat-sub">em <?php echo $filter_year; ?></div>
                    <i class="bi bi-download stat-icon"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-hero-card">
                    <div class="stat-label">Receita Bruta</div>
                    <div class="stat-value" style="color:#198754">
                        Kz<?php echo number_format((float)$totals['total_revenue'], 2); ?></div>
                    <div class="stat-sub">AO em <?php echo $filter_year; ?></div>
                    <i class="bi bi-currency-dollar stat-icon"></i>
                </div>
            </div>
        </div>

        <!-- ── Gráfico de streams ── -->
        <div class="chart-card platforms-card mb-4">
            <div class="card">
                <?php if (empty($chart_datasets)): ?>
                    <div class="empty-section">
                        <div class="icon"><i class="bi bi-bar-chart"></i></div>
                        <div class="small fw-semibold mb-1">Sem dados de streams para <?php echo $filter_year; ?>.</div>
                        <div class="small">Os streams aparecem aqui após a importação mensal de dados pelas plataformas.
                        </div>
                    </div>
                <?php else: ?>
                    <canvas id="streamChart" style="max-height:320px"></canvas>
                    <hr style="opacity:.07;margin:16px 0" />
                    <!-- Lista de plataformas com barras -->
                    <?php
                    $max_streams = max(array_column($platforms_data, 'total_streams') ?: [1]);
                    foreach ($platforms_data as $pd):
                        $slug   = $pd['slug_store'];
                        $colors = $store_colors[$slug] ?? $store_colors['default'];
                        $pct    = $max_streams > 0 ? round(($pd['total_streams'] / $max_streams) * 100) : 0;
                    ?>
                        <div class="platform-row">
                            <div class="platform-dot" style="background:<?php echo $colors['border']; ?>"></div>
                            <div style="min-width:120px;font-size:.82rem;font-weight:600">
                                <?php echo htmlspecialchars($pd['name_store']); ?></div>
                            <div class="platform-bar-bg">
                                <div class="platform-bar-fill"
                                    style="width:<?php echo $pct; ?>%;background:<?php echo $colors['border']; ?>"></div>
                            </div>
                            <div class="platform-streams"><?php echo number_format((int)$pd['total_streams']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Artistas ── -->
        <div class="table-card mb-4">
            <div class="card" id="artist">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Artistas</h6>
                    <span class="badge bg-secondary"><?php echo count($artists_data); ?></span>
                </div>
                <?php if (empty($artists_data)): ?>
                    <div class="empty-section">
                        <div class="icon"><i class="bi bi-person"></i></div>
                        <div class="small">Nenhum artista encontrado.</div>
                        <a href="../artists/add-artist" class="btn btn-sm btn-pink mt-3">Adicionar artista</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" id="artistsTable">
                            <thead>
                                <tr>
                                    <th style="width:52px">Foto</th>
                                    <th>Artista</th>
                                    <th>Streams <?php echo $filter_year; ?></th>
                                    <th class="text-center" style="width:60px">Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($artists_data as $art): ?>
                                    <tr>
                                        <td>
                                            <div class="artist-row-img">
                                                <?php if ($art['photo_artist']): ?>
                                                    <img src="<?php echo htmlspecialchars($cover_artists . $art['photo_artist']); ?>"
                                                        onerror="this.parentElement.innerHTML='🎤'" alt="" />
                                                    <?php else: ?>🎤<?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($art['stage_name']); ?></td>
                                        <td class="fw-bold" style="color:#FF0089">
                                            <?php echo number_format((int)$art['total_streams']); ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="artist-details?artist=<?php echo (int)$art['id_artist']; ?>&year=<?php echo $filter_year; ?>&store=<?php echo $filter_store; ?>"
                                                class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Playlists (Álbuns) ── -->
        <div class="table-card mb-4">
            <div class="card" id="playlist">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Playlists</h6>
                    <span class="badge bg-secondary"><?php echo count($playlists_data); ?></span>
                </div>
                <?php if (empty($playlists_data)): ?>
                    <div class="empty-section">
                        <div class="icon"><i class="bi bi-collection-play"></i></div>
                        <div class="small fw-semibold mb-1">Nenhum lançamento activo em <?php echo $filter_year; ?>.</div>
                        <div class="small">As playlists aparecem aqui após a aprovação dos teus álbuns e singles.</div>
                        <a href="<?php echo APP_URL . "/" . APP_URL_PANEL ?>/releases" class="btn btn-sm btn-pink mt-3">Ver
                            lançamentos</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" id="playlistsTable">
                            <thead>
                                <tr>
                                    <th style="width:52px">Capa</th>
                                    <th>Título</th>
                                    <th>Artista</th>
                                    <th>Tipo</th>
                                    <th>Faixas</th>
                                    <th>Streams <?php echo $filter_year; ?></th>
                                    <th>Receita (USD)</th>
                                    <th class="text-center" style="width:60px">Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($playlists_data as $pl):
                                    $type_colors = [
                                        'single'  => 'bg-primary',
                                        'EP'      => 'bg-warning text-dark',
                                        'album'   => 'bg-success',
                                        'mixtape' => 'bg-secondary',
                                    ];
                                    $tc = $type_colors[strtolower($pl['type_album'] ?? '')] ?? 'bg-secondary';
                                ?>
                                    <tr>
                                        <td>
                                            <?php if ($pl['img_cover']): ?>
                                                <div class="artist-row-img">
                                                    <img src="<?php echo htmlspecialchars($cover_albums . $pl['img_cover']); ?>"
                                                        onerror="this.parentElement.innerHTML='🎵'" alt="">
                                                </div>
                                            <?php else: ?>
                                                <div class="artist-row-img"
                                                    style="background:rgba(255,0,137,.06);display:flex;align-items:center;justify-content:center;font-size:1.2rem">
                                                    🎵</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold" style="font-size:.87rem">
                                                <?php echo htmlspecialchars($pl['title_album']); ?>
                                            </div>
                                            <?php if ($pl['release_date']): ?>
                                                <div style="font-size:.7rem;color:var(--text-muted,#6c757d)">
                                                    <?php echo date('d/m/Y', strtotime($pl['release_date'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small"><?php echo htmlspecialchars($pl['stage_name'] ?? '—'); ?></td>
                                        <td>
                                            <span class="badge <?php echo $tc; ?>" style="font-size:.6rem">
                                                <?php echo strtoupper($pl['type_album'] ?? ''); ?>
                                            </span>
                                        </td>
                                        <td class="text-center small"><?php echo (int)$pl['num_tracks']; ?></td>
                                        <td class="fw-bold" style="color:#FF0089">
                                            <?php echo number_format((int)$pl['total_streams']); ?>
                                        </td>
                                        <td class="small fw-semibold" style="color:#198754">
                                            $<?php echo number_format((float)$pl['total_revenue'], 4); ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="playlist-details?playlist=<?php echo urlencode($pl['title_album']); ?>&year=<?php echo $filter_year; ?><?php echo $filter_store ? '&store=' . $filter_store : ''; ?>"
                                                class="btn btn-sm btn-outline-secondary" style="border-radius:8px"
                                                title="Ver detalhes">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Países (Streams por País) ── -->
        <div class="table-card mb-4">
            <div class="card" id="countries">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        Países com streams
                    </h6>
                    <span class="badge bg-secondary"><?php echo count($countries_data); ?></span>
                </div>
                <?php if (empty($countries_data)): ?>
                    <div class="empty-section">
                        <div class="icon"><i class="bi bi-flag"></i></div>
                        <div class="small fw-semibold mb-1">Nenhum stream registado por país em <?php echo $filter_year; ?>.
                        </div>
                        <div class="small">Os dados geográficos aparecerão aqui quando as plataformas enviarem relatórios.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" id="countriesTable">
                            <thead>
                                <tr>
                                    <th style="width:40px">Bandeira</th>
                                    <th>País</th>
                                    <th>Faixas</th>
                                    <th>Streams <?php echo $filter_year; ?></th>
                                    <th>Receita (USD)</th>
                                    <th class="text-center" style="width:60px">Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Paleta de cores para barras (mesma lógica dos territórios)
                                $country_colors = ['#FF0089', '#6366f1', '#0d6efd', '#198754', '#f97316', '#06b6d4', '#8b5cf6', '#eab308'];
                                $max_country_streams = max(array_column($countries_data, 'total_streams') ?: [1]);
                                foreach ($countries_data as $idx => $c):
                                    $color = $country_colors[$idx % count($country_colors)];
                                    $pct   = $max_country_streams > 0 ? round(($c['total_streams'] / $max_country_streams) * 100) : 0;
                                    $country_code = $c['country_code'];
                                    $country_name_display = $c['country_name'] ?: strtoupper($country_code);
                                    $flag_url = "https://flagcdn.com/20x15/{$country_code}.png";
                                    // Link para country-details (já vai funcionar com o teu country-details.php melhorado)
                                    $country_link = urlencode($country_name_display);
                                ?>
                                    <tr>
                                        <td class="text-center">
                                            <img src="<?php echo $flag_url; ?>"
                                                style="width:24px; height:18px; border-radius:3px; object-fit:cover;"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';"
                                                alt="<?php echo $country_code; ?>" />
                                            <span style="display:none;"><?php echo substr($country_code, 0, 2); ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div>
                                                    <div class="fw-semibold" style="font-size:.87rem">
                                                        <?php echo htmlspecialchars($country_name_display); ?>
                                                    </div>
                                                    <!-- barra de proporção -->
                                                    <div
                                                        style="height:4px;border-radius:4px;background:rgba(0,0,0,.06);width:140px;margin-top:4px;overflow:hidden">
                                                        <div
                                                            style="height:100%;border-radius:4px;background:<?php echo $color; ?>;width:<?php echo $pct; ?>%;transition:width .4s">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center small"><?php echo (int)$c['num_tracks']; ?></td>
                                        <td class="fw-bold" style="color:#FF0089">
                                            <?php echo number_format((int)$c['total_streams']); ?>
                                        </td>
                                        <td class="small fw-semibold" style="color:#198754">
                                            $<?php echo number_format((float)$c['total_revenue'], 4); ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="country-details?country=<?php echo $country_link; ?>&year=<?php echo $filter_year; ?>"
                                                class="btn btn-sm btn-outline-secondary" style="border-radius:8px"
                                                title="Ver detalhes de <?php echo htmlspecialchars($country_name_display); ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Territórios ── -->
        <div class="table-card mb-4">
            <div class="card" id="country">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Territórios</h6>
                    <span class="badge bg-secondary"><?php echo count($territories_data); ?></span>
                </div>
                <?php if (empty($territories_data)): ?>
                    <div class="empty-section">
                        <div class="icon"><i class="bi bi-globe2"></i></div>
                        <div class="small fw-semibold mb-1">Nenhum território configurado.</div>
                        <div class="small">Define o campo <strong>Território</strong> nos teus álbuns para ver dados aqui.
                        </div>
                        <a href="<?php echo APP_URL . "/" . APP_URL_PANEL ?>/releases" class="btn btn-sm btn-pink mt-3">Ver
                            lançamentos</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" id="territoriesTable">
                            <thead>
                                <tr>
                                    <th>Território</th>
                                    <th>Álbuns</th>
                                    <th>Faixas</th>
                                    <th>Streams <?php echo $filter_year; ?></th>
                                    <th>Receita (USD)</th>
                                    <th class="text-center" style="width:60px">Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Paleta de cores para barras de território
                                $terr_colors = ['#FF0089', '#6366f1', '#0d6efd', '#198754', '#f97316', '#06b6d4', '#8b5cf6', '#eab308'];
                                $max_terr = max(array_column($territories_data, 'total_streams') ?: [1]);
                                foreach ($territories_data as $idx => $terr):
                                    $color = $terr_colors[$idx % count($terr_colors)];
                                    $pct   = $max_terr > 0 ? round(($terr['total_streams'] / $max_terr) * 100) : 0;
                                    // Extrair nome simples para o link (Worldwide ou o valor directo)
                                    $terr_link = urlencode($terr['territory']);
                                    $is_worldwide = stripos($terr['territory'], 'worldwide') !== false;
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($is_worldwide): ?>
                                                    <i class="bi bi-globe2"
                                                        style="color:<?php echo $color; ?>;font-size:1.1rem"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-geo-alt-fill"
                                                        style="color:<?php echo $color; ?>;font-size:1rem"></i>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-semibold" style="font-size:.87rem">
                                                        <?php echo htmlspecialchars(mb_substr($terr['territory'], 0, 40)); ?>
                                                        <?php if (mb_strlen($terr['territory']) > 40): ?><span
                                                                class="text-muted small">…</span><?php endif; ?>
                                                    </div>
                                                    <!-- barra de proporção -->
                                                    <div
                                                        style="height:4px;border-radius:4px;background:rgba(0,0,0,.06);width:140px;margin-top:4px;overflow:hidden">
                                                        <div
                                                            style="height:100%;border-radius:4px;background:<?php echo $color; ?>;width:<?php echo $pct; ?>%;transition:width .4s">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center small"><?php echo (int)$terr['num_albums']; ?></td>
                                        <td class="text-center small"><?php echo (int)$terr['num_tracks']; ?></td>
                                        <td class="fw-bold" style="color:#FF0089">
                                            <?php echo number_format((int)$terr['total_streams']); ?>
                                        </td>
                                        <td class="small fw-semibold" style="color:#198754">
                                            $<?php echo number_format((float)$terr['total_revenue'], 4); ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="country-details?country=<?php echo $terr_link; ?>&year=<?php echo $filter_year; ?>"
                                                class="btn btn-sm btn-outline-secondary" style="border-radius:8px"
                                                title="Ver detalhes do território">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /container -->

    <?php
    // CSRF para links de download no modal
    $csrf_export = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(16));
    $_SESSION['csrf_token'] = $csrf_export;
    ?>

    <!-- ════ MODAL — Exportar ════ -->
    <div class="modal fade" id="modalExport" tabindex="-1" aria-labelledby="modalExportLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalExportLabel">
                        <i class="bi bi-download me-2 text-success"></i>Exportar Dados
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Info do filtro activo -->
                    <div class="alert alert-secondary py-2 px-3 mb-4" style="font-size:.82rem;border-radius:10px">
                        <i class="bi bi-funnel me-2"></i>
                        A exportar dados de <strong><?php echo $filter_year; ?></strong>
                        <?php if ($filter_store && isset($store_map[$filter_store])): ?>
                            — <strong><?php echo htmlspecialchars($store_map[$filter_store]['name_store']); ?></strong>
                        <?php else: ?> — todas as plataformas<?php endif; ?>
                    </div>

                    <!-- Aviso CSV -->
                    <div class="alert alert-warning py-2 px-3 mb-4" style="font-size:.78rem;border-radius:10px">
                        <i class="bi bi-info-circle me-1"></i>
                        Ficheiros CSV com separador <strong>;</strong> e codificação UTF-8. No Excel abre directamente.
                        No Google Sheets usa <em>Ficheiro → Importar</em> e selecciona "Ponto e vírgula".
                    </div>

                    <!-- 3 opções de download -->
                    <div class="row g-3">
                        <!-- Streams -->
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 d-flex flex-column"
                                style="border-color:rgba(255,0,137,.2) !important">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div
                                        style="width:36px;height:36px;border-radius:10px;background:rgba(255,0,137,.1);display:flex;align-items:center;justify-content:center;color:#FF0089;font-size:1.2rem;flex-shrink:0">
                                        <i class="bi bi-headphones"></i>
                                    </div>
                                    <div class="fw-bold small">Streams por Faixa</div>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;flex:1">
                                    Detalhe mensal por plataforma — Ano, Mês, Plataforma, Artista, Álbum, Faixa, ISRC,
                                    Streams, Downloads, Receita USD
                                </div>
                                <a href="export?do_export=streams_csv&year=<?php echo $filter_year; ?>&store=<?php echo $filter_store; ?>&csrf=<?php echo urlencode($csrf_export); ?>"
                                    class="btn btn-sm mt-3 w-100 fw-bold"
                                    style="background:rgba(255,0,137,.1);color:#FF0089;border:1.5px solid rgba(255,0,137,.3);border-radius:8px"
                                    data-bs-dismiss="modal">
                                    <i class="bi bi-filetype-csv me-1"></i>Download CSV
                                </a>
                            </div>
                        </div>

                        <!-- Royalties -->
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 d-flex flex-column"
                                style="border-color:rgba(25,135,84,.2) !important">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div
                                        style="width:36px;height:36px;border-radius:10px;background:rgba(25,135,84,.1);display:flex;align-items:center;justify-content:center;color:#198754;font-size:1.2rem;flex-shrink:0">
                                        <i class="bi bi-cash-coin"></i>
                                    </div>
                                    <div class="fw-bold small">Royalties</div>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;flex:1">
                                    Receitas mensais — Faixa, ISRC, Álbum, Artista, Receita Bruta, Taxa, Royalty Líquido
                                    USD e AOA, Estado
                                </div>
                                <a href="export?do_export=royalties_csv&year=<?php echo $filter_year; ?>&csrf=<?php echo urlencode($csrf_export); ?>"
                                    class="btn btn-sm mt-3 w-100 fw-bold"
                                    style="background:rgba(25,135,84,.1);color:#198754;border:1.5px solid rgba(25,135,84,.3);border-radius:8px"
                                    data-bs-dismiss="modal">
                                    <i class="bi bi-filetype-csv me-1"></i>Download CSV
                                </a>
                            </div>
                        </div>

                        <!-- Catálogo -->
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 d-flex flex-column"
                                style="border-color:rgba(13,110,253,.2) !important">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div
                                        style="width:36px;height:36px;border-radius:10px;background:rgba(13,110,253,.1);display:flex;align-items:center;justify-content:center;color:#0d6efd;font-size:1.2rem;flex-shrink:0">
                                        <i class="bi bi-music-note-list"></i>
                                    </div>
                                    <div class="fw-bold small">Catálogo de Faixas</div>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;flex:1">
                                    Todas as faixas activas com totais de <?php echo $filter_year; ?> — ISRC, Autor,
                                    Álbum, Território, Editora, Streams, Receita
                                </div>
                                <a href="export?do_export=tracks_csv&year=<?php echo $filter_year; ?>&store=<?php echo $filter_store; ?>&csrf=<?php echo urlencode($csrf_export); ?>"
                                    class="btn btn-sm mt-3 w-100 fw-bold"
                                    style="background:rgba(13,110,253,.1);color:#0d6efd;border:1.5px solid rgba(13,110,253,.3);border-radius:8px"
                                    data-bs-dismiss="modal">
                                    <i class="bi bi-filetype-csv me-1"></i>Download CSV
                                </a>
                            </div>
                        </div>
                    </div><!-- /row -->

                    <!-- PDF future -->
                    <div class="mt-4 pt-3 border-top d-flex align-items-center gap-2" style="opacity:.5">
                        <i class="bi bi-filetype-pdf text-danger fs-5"></i>
                        <span class="small text-muted">Relatórios PDF (em breve) — Streams, Financeiro e por
                            Artista</span>
                        <span class="badge bg-secondary ms-1" style="font-size:.6rem">Em breve</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- ════ MODAL — Exportar FIM ════ -->

    <!-- ═══ JS ═══ -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <!-- ── Dados injectados pelo PHP para o JS ── -->
    <script>
        const HAS_CHART = <?php echo !empty($chart_datasets)  ? 'true' : 'false'; ?>;
        const HAS_COUNTRIES = <?php echo !empty($countries_data)   ? 'true' : 'false'; ?>;
        const CHART_LABELS = <?php echo json_encode($months_pt_short); ?>;
        const CHART_DATASETS = <?php echo json_encode($chart_datasets ?? []); ?>;
    </script>
    <!-- ── Lógica da página ── -->
    <script src="<?php echo APP_URL ?>/<?php echo APP_URL_PANEL ?>/analytics/js/statistics.js"></script>
</body>

</html>