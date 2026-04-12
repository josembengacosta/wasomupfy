<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Detalhes do Artista (Estatísticas)
// Arquivo: dashboard/analytics/artist-details.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
require_once __DIR__ . '/../include/platform.php';
startSecureSession();
checkRememberMe();
requireLogin();
$platform = checkDashboardStatus();
$user     = checkUserAccess((int)$_SESSION['id_users']);

$id_users        = (int)$user['id_users'];
$first_name      = htmlspecialchars($user['first_name']);
$user_name       = htmlspecialchars($user['user_name'] ?? '');
$email_verified  = (bool)$user['email_verified'];
$plan_selected   = $user['plan_selected'];
$onboard_done    = (bool)($user['onboarding_done'] ?? false);
$user_photo      = $user['photo_user'] ?? null;
$name_artist_band = htmlspecialchars($user['name_artist_band'] ?? 'Cria Perfil Artístico');
$notif_count     = getUnreadNotifCount($id_users);
$db              = getDB();

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
    if ($secs < 60) $session_duration_str = $secs . 's';
    elseif ($secs < 3600) $session_duration_str = floor($secs / 60) . 'min';
    elseif ($secs < 86400) $session_duration_str = floor($secs / 3600) . 'h ' . floor(($secs % 3600) / 60) . 'min';
    else $session_duration_str = floor($secs / 86400) . 'd ' . floor(($secs % 86400) / 3600) . 'h';
}
$member_since   = $user['creat_user'] ? date('d/m/Y', strtotime($user['creat_user'])) : '—';
$last_login_str = ($sec && $sec['last_login_at']) ? date('d/m/Y H:i', strtotime($sec['last_login_at'])) : '—';
$ua_raw   = $current_session['user_agent'] ?? '';
$browser  = 'Desconhecido';
if (str_contains($ua_raw, 'Edg')) $browser = 'Microsoft Edge';
elseif (str_contains($ua_raw, 'Chrome')) $browser = 'Google Chrome';
elseif (str_contains($ua_raw, 'Firefox')) $browser = 'Mozilla Firefox';
elseif (str_contains($ua_raw, 'Safari')) $browser = 'Safari';
elseif (str_contains($ua_raw, 'Opera')) $browser = 'Opera';
$sess_location = trim(($current_session['city'] ?? '') . ', ' . ($current_session['country'] ?? ''), ', ') ?: 'Desconhecida';
$sess_ip       = $current_session['ip_address'] ?? ($sec['last_login_ip'] ?? '—');

// ── Parâmetros da URL ─────────────────────────
$id_artist    = isset($_GET['artist']) ? (int)$_GET['artist'] : 0;
$filter_year  = isset($_GET['year'])   ? (int)$_GET['year']   : (int)date('Y');
$filter_store = isset($_GET['store'])  ? (int)$_GET['store']  : 0; // 0 = todos

if (!$id_artist) {
    redirect(APP_URL_PANEL . '/statistics#artist');
}

// ── Validar que o artista pertence ao utilizador ──
$artist_q = $db->prepare("
    SELECT id_artist, stage_name, real_name, photo_artist, cover_artist,
           bio, genre_main, genre_secondary, country, city,
           instagram_url, spotify_url, youtube_url
    FROM _artist
    WHERE id_artist = ? AND id_users = ? AND status_artist != 'blocked'
");
$artist_q->execute([$id_artist, $id_users]);
$artist = $artist_q->fetch();
if (!$artist) {
    redirect(APP_URL_PANEL . '/statistics#artist');
}

// ── Anos disponíveis ──────────────────────────
$years_q = $db->prepare("
    SELECT DISTINCT s.year_stream
    FROM _stream s
    JOIN _track t  ON t.id_track  = s.id_track
    JOIN _album al ON al.id_album = t.id_album
    WHERE al.id_artist = ? AND al.id_users = ?
    ORDER BY s.year_stream DESC
");
$years_q->execute([$id_artist, $id_users]);
$available_years = $years_q->fetchAll(PDO::FETCH_COLUMN);
if (empty($available_years)) {
    $available_years = [(int)date('Y')];
} else {
    $current_year = (int)date('Y');
    if (!in_array($current_year, $available_years)) {
        $available_years[] = $current_year;
        rsort($available_years);
    }
}

// ── Lojas activas ─────────────────────────────
$stores_q = $db->prepare("SELECT id_store, name_store, slug_store FROM _store WHERE is_active = 1 ORDER BY display_order ASC");
$stores_q->execute();
$stores    = $stores_q->fetchAll(PDO::FETCH_ASSOC);
$store_map = array_column($stores, null, 'id_store');

// ── TOTAIS do artista no ano (garante linha mesmo sem streams) ──
$totals_q = $db->prepare("
    SELECT
        COALESCE(SUM(s.streams), 0)   AS total_streams,
        COALESCE(SUM(s.downloads), 0) AS total_downloads,
        COALESCE(SUM(s.revenue), 0)   AS total_revenue,
        COUNT(DISTINCT t.id_track)    AS total_tracks
    FROM _artist a
    LEFT JOIN _album al ON al.id_artist = a.id_artist 
        AND al.status_album IN ('approved','active')
    LEFT JOIN _track t ON t.id_album = al.id_album 
        AND t.status_track IN ('active','approved')
    LEFT JOIN _stream s ON s.id_track = t.id_track 
        AND s.year_stream = ?
        " . ($filter_store ? "AND s.id_store = ?" : "") . "
    WHERE a.id_artist = ? AND a.id_users = ?
    GROUP BY a.id_artist
");
$p = [$filter_year];
if ($filter_store) $p[] = $filter_store;
$p[] = $id_artist;
$p[] = $id_users;
$totals_q->execute($p);
$totals = $totals_q->fetch();
if (!$totals) {
    $totals = ['total_streams' => 0, 'total_downloads' => 0, 'total_revenue' => 0, 'total_tracks' => 0];
}

// ── STREAMS POR PLATAFORMA ────────────────────
if ($filter_store) {
    // Mostrar a loja selecionada, mesmo com zero streams
    $platforms_q = $db->prepare("
        SELECT
            st.id_store,
            st.name_store,
            st.slug_store,
            COALESCE(SUM(s.streams), 0)  AS total_streams,
            COALESCE(SUM(s.revenue), 0)  AS total_revenue
        FROM _store st
        LEFT JOIN _stream s ON s.id_store = st.id_store 
            AND s.year_stream = ?
        LEFT JOIN _track t ON t.id_track = s.id_track
        LEFT JOIN _album al ON al.id_album = t.id_album 
            AND al.id_artist = ? AND al.id_users = ?
        WHERE st.id_store = ? AND st.is_active = 1
        GROUP BY st.id_store, st.name_store, st.slug_store
    ");
    $platforms_q->execute([$filter_year, $id_artist, $id_users, $filter_store]);
} else {
    // Mostrar apenas lojas que têm streams > 0
    $platforms_q = $db->prepare("
        SELECT
            st.id_store,
            st.name_store,
            st.slug_store,
            COALESCE(SUM(s.streams), 0)  AS total_streams,
            COALESCE(SUM(s.revenue), 0)  AS total_revenue
        FROM _store st
        LEFT JOIN _stream s ON s.id_store = st.id_store 
            AND s.year_stream = ?
        LEFT JOIN _track t ON t.id_track = s.id_track
        LEFT JOIN _album al ON al.id_album = t.id_album 
            AND al.id_artist = ? AND al.id_users = ?
        WHERE st.is_active = 1
        GROUP BY st.id_store, st.name_store, st.slug_store
        HAVING total_streams > 0
        ORDER BY total_streams DESC
    ");
    $platforms_q->execute([$filter_year, $id_artist, $id_users]);
}
$platforms_data = $platforms_q->fetchAll(PDO::FETCH_ASSOC);

// ── STREAMS POR MÊS + PLATAFORMA (gráfico) ───
$chart_q = $db->prepare("
    SELECT
        s.month_stream,
        s.id_store,
        st.name_store,
        st.slug_store,
        COALESCE(SUM(s.streams), 0) AS streams
    FROM _artist a
    LEFT JOIN _album al ON al.id_artist = a.id_artist 
        AND al.status_album IN ('approved','active')
    LEFT JOIN _track t ON t.id_album = al.id_album 
        AND t.status_track IN ('active','approved')
    LEFT JOIN _stream s ON s.id_track = t.id_track 
        AND s.year_stream = ?
        " . ($filter_store ? "AND s.id_store = ?" : "") . "
    LEFT JOIN _store st ON st.id_store = s.id_store
    WHERE a.id_artist = ? AND a.id_users = ?
    GROUP BY s.month_stream, s.id_store, st.name_store, st.slug_store
    ORDER BY s.month_stream ASC, st.display_order ASC
");
$pc = [$filter_year];
if ($filter_store) $pc[] = $filter_store;
$pc[] = $id_artist;
$pc[] = $id_users;
$chart_q->execute($pc);
$chart_raw = $chart_q->fetchAll(PDO::FETCH_ASSOC);

$store_colors = [
    'spotify'       => ['border' => '#1db954', 'bg' => 'rgba(29,185,84,0.4)'],
    'apple-music'   => ['border' => '#fc3c44', 'bg' => 'rgba(252,60,68,0.4)'],
    'amazon-music'  => ['border' => '#00a8e0', 'bg' => 'rgba(0,168,224,0.4)'],
    'deezer'        => ['border' => '#ff0089', 'bg' => 'rgba(255,0,137,0.4)'],
    'tidal'         => ['border' => '#00ffff', 'bg' => 'rgba(0,255,255,0.3)'],
    'youtube-music' => ['border' => '#ff0000', 'bg' => 'rgba(255,0,0,0.4)'],
    'boomplay'      => ['border' => '#f5a623', 'bg' => 'rgba(245,166,35,0.4)'],
    'tiktok'        => ['border' => '#69c9d0', 'bg' => 'rgba(105,201,208,0.4)'],
    'itunes'        => ['border' => '#c864c8', 'bg' => 'rgba(200,100,200,0.4)'],
    'default'       => ['border' => '#aaa',   'bg' => 'rgba(170,170,170,0.3)'],
];

$store_icons = [
    'spotify'       => 'bi-spotify',
    'apple-music'   => 'bi-apple',
    'amazon-music'  => 'bi-music-note-beamed',
    'deezer'        => 'bi-music-player',
    'tidal'         => 'bi-water',
    'youtube-music' => 'bi-youtube',
    'boomplay'      => 'bi-soundwave',
    'tiktok'        => 'bi-tiktok',
    'itunes'        => 'bi-music-note',
    'default'       => 'bi-music-note-beamed',
];

$chart_stores   = [];
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

$months_pt_short = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

// ── FAIXAS do artista com totais de streams ───
$tracks_q = $db->prepare("
    SELECT
        t.id_track,
        t.title_track,
        t.name_author,
        t.name_author_feat,
        t.explicit,
        t.duration_seconds,
        al.title_album,
        al.type_album,
        al.img_cover,
        al.release_date,
        COALESCE(SUM(s.streams), 0)   AS total_streams,
        COALESCE(SUM(s.downloads), 0) AS total_downloads,
        COALESCE(SUM(s.revenue), 0)   AS total_revenue
    FROM _track t
    JOIN _album al ON al.id_album = t.id_album
    LEFT JOIN _stream s ON s.id_track = t.id_track
        AND s.year_stream = ?
        " . ($filter_store ? "AND s.id_store = ?" : "") . "
    WHERE al.id_artist = ? AND al.id_users = ?
      AND t.status_track IN ('active','approved')
    GROUP BY t.id_track, t.title_track, t.name_author, t.name_author_feat,
             t.explicit, t.duration_seconds,
             al.title_album, al.type_album, al.img_cover, al.release_date
    ORDER BY total_streams DESC
");
$pt = [$filter_year];
if ($filter_store) $pt[] = $filter_store;
$pt[] = $id_artist;
$pt[] = $id_users;
$tracks_q->execute($pt);
$tracks = $tracks_q->fetchAll(PDO::FETCH_ASSOC);

function formatDuration(?int $sec): string
{
    if (!$sec) return '—';
    return gmdate($sec >= 3600 ? 'H:i:s' : 'i:s', $sec);
}

$base_url  = rtrim(APP_URL, '/');
$cover_url = $base_url . '/assets/comprovantes/uploads/covers/';
$photo_url = $base_url . '/assets/comprovantes/uploads/artists/';

// ── Função auxiliar para os banners de alerta ──
function wuAlert(string $type, string $icon, string $message, ?array $action = null, bool $dismiss = true, string $id = ''): void
{
    $alertColors = [
        'danger'  => ['bg' => 'rgba(239,68,68,.08)',  'border' => 'rgba(239,68,68,.25)',  'text' => '#ef4444'],
        'warning' => ['bg' => 'rgba(234,179,8,.08)',  'border' => 'rgba(234,179,8,.25)',  'text' => '#eab308'],
        'info'    => ['bg' => 'rgba(99,102,241,.08)', 'border' => 'rgba(99,102,241,.25)', 'text' => '#6366f1'],
    ];
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
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title><?php echo htmlspecialchars($artist['stage_name']); ?> — Estatísticas — <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/artist-list.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
    /* ══ Hero do artista ══ */
    .artist-hero {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 28px;
        min-height: 180px;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
    }

    .artist-hero .hero-cover {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: center;
        opacity: .22;
        filter: blur(2px);
    }

    .artist-hero .hero-body {
        position: relative;
        z-index: 1;
        padding: 28px 28px 24px;
        display: flex;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
    }

    .artist-photo {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(255, 0, 137, .6);
        background: rgba(255, 0, 137, .1);
        flex-shrink: 0;
    }

    .artist-photo-placeholder {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 3px solid rgba(255, 0, 137, .4);
        background: rgba(255, 0, 137, .08);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.8rem;
        flex-shrink: 0;
    }

    .artist-hero-info h2 {
        color: #fff;
        font-weight: 800;
        margin: 0 0 4px;
    }

    .artist-hero-info .meta {
        color: rgba(255, 255, 255, .6);
        font-size: .82rem;
    }

    .artist-hero-info .meta span {
        margin-right: 14px;
    }

    /* ══ Stat cards ══ */
    .stat-hero-card {
        border-radius: 16px;
        padding: 18px 20px;
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        position: relative;
        overflow: hidden;
    }

    .stat-hero-card .stat-label {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-muted, #6c757d);
        margin-bottom: 5px;
    }

    .stat-hero-card .stat-value {
        font-size: 1.65rem;
        font-weight: 900;
        line-height: 1;
    }

    .stat-hero-card .stat-icon {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 2.6rem;
        opacity: .07;
    }

    /* ══ Filtros ══ */
    .filter-bar {
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        border-radius: 14px;
        padding: 14px 18px;
        margin-bottom: 22px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
    }

    .filter-bar label {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-muted, #6c757d);
        display: block;
        margin-bottom: 3px;
    }

    /* ══ Plataformas ══ */
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

    /* ══ Tabela de faixas ══ */
    .track-cover {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
    }

    .track-cover-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: rgba(255, 0, 137, .08);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .explicit-badge {
        font-size: .6rem;
        background: #333;
        color: #fff;
        border-radius: 3px;
        padding: 1px 4px;
        vertical-align: middle;
        margin-left: 4px;
    }

    .feat-text {
        font-size: .72rem;
        color: var(--text-muted, #6c757d);
    }

    /* ══ Social links ══ */
    .social-links a {
        font-size: 1.3rem;
        color: var(--text-muted, #6c757d);
        margin-right: 10px;
        transition: color .2s;
    }

    .social-links a:hover {
        color: #FF0089;
    }

    /* ══ Empty ══ */
    .empty-section {
        text-align: center;
        padding: 36px 20px;
        color: var(--text-muted, #6c757d);
    }

    .empty-section .icon {
        font-size: 2.2rem;
        opacity: .15;
        margin-bottom: 8px;
    }
    </style>
</head>

<body>

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

        <!-- ── Hero do artista ── -->
        <div class="artist-hero">
            <?php if ($artist['cover_artist']): ?>
            <div class="hero-cover"
                style="background-image:url('<?php echo htmlspecialchars($photo_url . $artist['cover_artist']); ?>')">
            </div>
            <?php endif; ?>
            <div class="hero-body">
                <?php if ($artist['photo_artist']): ?>
                <img class="artist-photo" src="<?php echo htmlspecialchars($photo_url . $artist['photo_artist']); ?>"
                    onerror="this.outerHTML='<div class=\'artist-photo-placeholder\'>🎤</div>'"
                    alt="<?php echo htmlspecialchars($artist['stage_name']); ?>" />
                <?php else: ?>
                <div class="artist-photo-placeholder">🎤</div>
                <?php endif; ?>
                <div class="artist-hero-info">
                    <h2><?php echo htmlspecialchars($artist['stage_name']); ?></h2>
                    <div class="meta">
                        <?php if ($artist['genre_main']): ?>
                        <span><i
                                class="bi bi-music-note me-1"></i><?php echo htmlspecialchars($artist['genre_main']); ?><?php if ($artist['genre_secondary']): ?>
                            /
                            <?php echo htmlspecialchars($artist['genre_secondary']);
                                                                                                                            endif; ?></span>
                        <?php endif; ?>
                        <?php if ($artist['country']): ?>
                        <span><i
                                class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($artist['country']); ?><?php if ($artist['city']): ?>,
                            <?php echo htmlspecialchars($artist['city']);
                                                                                                                        endif; ?></span>
                        <?php endif; ?>
                        <span><i class="bi bi-disc me-1"></i><?php echo (int)$totals['total_tracks']; ?>
                            faixa<?php echo $totals['total_tracks'] != 1 ? 's' : ''; ?></span>
                    </div>
                    <?php if ($artist['instagram_url'] || $artist['spotify_url'] || $artist['youtube_url']): ?>
                    <div class="social-links mt-2">
                        <?php if ($artist['instagram_url']): ?>
                        <a href="<?php echo htmlspecialchars($artist['instagram_url']); ?>" target="_blank"
                            rel="noopener" data-bs-toggle="tooltip" title="Instagram"><i
                                class="bi bi-instagram"></i></a>
                        <?php endif; ?>
                        <?php if ($artist['spotify_url']): ?>
                        <a href="<?php echo htmlspecialchars($artist['spotify_url']); ?>" target="_blank" rel="noopener"
                            data-bs-toggle="tooltip" title="Spotify"><i class="bi bi-spotify"></i></a>
                        <?php endif; ?>
                        <?php if ($artist['youtube_url']): ?>
                        <a href="<?php echo htmlspecialchars($artist['youtube_url']); ?>" target="_blank" rel="noopener"
                            data-bs-toggle="tooltip" title="YouTube"><i class="bi bi-youtube"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="ms-auto d-flex gap-2 flex-wrap align-items-start">
                    <a href="statistics#artist" class="btn btn-sm"
                        style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:10px">
                        <i class="bi bi-arrow-left me-1"></i>Voltar
                    </a>
                    <a href="artist-details?artist=<?php echo $id_artist; ?>&year=<?php echo $filter_year; ?><?php echo $filter_store ? '&store=' . $filter_store : ''; ?>"
                        class="btn btn-sm"
                        style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:10px">
                        <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
                    </a>
                </div>
            </div>
        </div>

        <!-- ── Barra de filtros ── -->
        <form method="GET" action="artist-details">
            <input type="hidden" name="artist" value="<?php echo $id_artist; ?>" />
            <div class="filter-bar">
                <div>
                    <label>Ano</label>
                    <select name="year" class="form-select form-select-sm" style="min-width:100px"
                        onchange="this.form.submit()">
                        <?php foreach ($available_years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $filter_year ? 'selected' : ''; ?>>
                            <?php echo $y; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Plataforma</label>
                    <select name="store" class="form-select form-select-sm" style="min-width:160px"
                        onchange="this.form.submit()">
                        <option value="0" <?php echo !$filter_store ? 'selected' : ''; ?>>Todas as plataformas
                        </option>
                        <?php foreach ($stores as $st): ?>
                        <option value="<?php echo $st['id_store']; ?>"
                            <?php echo $st['id_store'] == $filter_store ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($st['name_store']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ms-auto d-flex align-items-end" style="font-size:.78rem;color:var(--text-muted,#6c757d)">
                    <i class="bi bi-info-circle me-1"></i>
                    <?php echo $filter_year; ?>
                    <?php echo $filter_store && isset($store_map[$filter_store]) ? '— ' . htmlspecialchars($store_map[$filter_store]['name_store']) : '— Todas as plataformas'; ?>
                </div>
            </div>
        </form>

        <!-- ── Cards de totais ── -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-hero-card">
                    <div class="stat-label">Streams</div>
                    <div class="stat-value" style="color:#FF0089">
                        <?php echo number_format((int)$totals['total_streams']); ?></div>
                    <i class="bi bi-headphones stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-hero-card">
                    <div class="stat-label">Downloads</div>
                    <div class="stat-value" style="color:#0d6efd">
                        <?php echo number_format((int)$totals['total_downloads']); ?></div>
                    <i class="bi bi-download stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-hero-card">
                    <div class="stat-label">Receita (Kz)</div>
                    <div class="stat-value" style="color:#198754;font-size:1.3rem">
                        Kz<?php echo number_format((float)$totals['total_revenue'], 2); ?></div>
                    <i class="bi bi-currency-dollar stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-hero-card">
                    <div class="stat-label">Faixas activas</div>
                    <div class="stat-value" style="color:#6c757d"><?php echo (int)$totals['total_tracks']; ?></div>
                    <i class="bi bi-disc stat-icon"></i>
                </div>
            </div>
        </div>

        <!-- ── Gráfico streams por mês ── -->
        <div class="chart-card mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-graph-up me-2 text-pink"></i>Streams por mês —
                        <?php echo $filter_year; ?></h6>
                </div>
                <?php if (empty($chart_datasets)): ?>
                <div class="empty-section">
                    <div class="icon"><i class="bi bi-bar-chart"></i></div>
                    <div class="small fw-semibold mb-1">Sem dados de streams para <?php echo $filter_year; ?>.</div>
                    <div class="small">Os streams são importados mensalmente após entrega dos relatórios pelas
                        plataformas.</div>
                </div>
                <?php else: ?>
                <div class="p-3">
                    <canvas id="streamChart" style="max-height:300px"></canvas>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Plataformas ── -->
        <?php if (!empty($platforms_data)): ?>
        <div class="card mb-4" style="border-radius:16px">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-collection me-2 text-pink"></i>
                    <?php if ($filter_store && isset($store_map[$filter_store])): ?>
                    Streams na <?php echo htmlspecialchars($store_map[$filter_store]['name_store']); ?>
                    <?php else: ?>
                    Streams por plataforma
                    <?php endif; ?>
                </h6>
            </div>
            <div class="card-body pt-2">
                <?php
                    $max_plat = max(array_column($platforms_data, 'total_streams') ?: [1]);
                    foreach ($platforms_data as $pd):
                        $slug   = $pd['slug_store'];
                        $colors = $store_colors[$slug] ?? $store_colors['default'];
                        $icon   = $store_icons[$slug]  ?? $store_icons['default'];
                        $pct    = $max_plat > 0 ? round(($pd['total_streams'] / $max_plat) * 100) : 0;
                    ?>
                <div class="platform-row">
                    <div class="platform-dot" style="background:<?php echo $colors['border']; ?>"></div>
                    <i class="<?php echo $icon; ?>"
                        style="font-size:1rem;color:<?php echo $colors['border']; ?>;min-width:20px"></i>
                    <div style="min-width:130px;font-size:.82rem;font-weight:600">
                        <?php echo htmlspecialchars($pd['name_store']); ?></div>
                    <div class="platform-bar-bg">
                        <div class="platform-bar-fill"
                            style="width:<?php echo $pct; ?>%;background:<?php echo $colors['border']; ?>"></div>
                    </div>
                    <div style="font-size:.78rem;font-weight:700;min-width:80px;text-align:right">
                        <?php echo number_format((int)$pd['total_streams']); ?>
                        <span style="font-size:.65rem;font-weight:400;color:var(--text-muted,#6c757d)">
                            streams</span>
                    </div>
                    <div style="font-size:.72rem;color:var(--text-muted,#6c757d);min-width:70px;text-align:right">
                        $<?php echo number_format((float)$pd['total_revenue'], 2); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Tabela de faixas ── -->
        <div class="table-card mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-music-note-list me-2 text-pink"></i>Faixas</h6>
                    <span class="badge bg-secondary"><?php echo count($tracks); ?></span>
                </div>
                <?php if (empty($tracks)): ?>
                <div class="empty-section">
                    <div class="icon"><i class="bi bi-music-note"></i></div>
                    <div class="small fw-semibold mb-1">Nenhuma faixa activa encontrada.</div>
                    <div class="small">As faixas aparecem aqui após aprovação pela equipa <?php echo APP_NAME ?>.</div>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table id="tracksTable" class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:52px">Capa</th>
                                <th>Faixa</th>
                                <th>Álbum</th>
                                <th>Duração</th>
                                <th>Streams <?php echo $filter_year; ?></th>
                                <th>Downloads</th>
                                <th>Receita (Kz)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tracks as $track): ?>
                            <tr>
                                <td>
                                    <?php if ($track['img_cover']): ?>
                                    <img class="track-cover"
                                        src="<?php echo htmlspecialchars($cover_url . $track['img_cover']); ?>"
                                        onerror="this.outerHTML='<div class=\'track-cover-placeholder\'>🎵</div>'"
                                        alt="" />
                                    <?php else: ?>
                                    <div class="track-cover-placeholder">🎵</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold" style="font-size:.87rem">
                                        <?php echo htmlspecialchars($track['title_track']); ?>
                                        <?php if ($track['explicit'] === 'YES'): ?>
                                        <span class="explicit-badge">E</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($track['name_author']): ?>
                                    <div class="feat-text">
                                        <?php echo htmlspecialchars($track['name_author']); ?><?php if ($track['name_author_feat']): ?>
                                        feat.
                                        <?php echo htmlspecialchars($track['name_author_feat']);
                                                                                                            endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:.82rem">
                                    <?php echo htmlspecialchars($track['title_album']); ?>
                                    <span class="badge bg-light text-muted ms-1"
                                        style="font-size:.6rem"><?php echo strtoupper($track['type_album']); ?></span>
                                </td>
                                <td class="small text-muted">
                                    <?php echo formatDuration($track['duration_seconds']); ?>
                                </td>
                                <td class="fw-bold" style="color:#FF0089">
                                    <?php echo number_format((int)$track['total_streams']); ?></td>
                                <td class="small"><?php echo number_format((int)$track['total_downloads']); ?></td>
                                <td class="small fw-semibold" style="color:#198754">
                                    $<?php echo number_format((float)$track['total_revenue'], 4); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Bio (se existir) ── -->
        <?php if ($artist['bio']): ?>
        <div class="card mb-4" style="border-radius:16px">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-person-lines-fill me-2 text-pink"></i>Biografia</h6>
            </div>
            <div class="card-body" style="font-size:.87rem;line-height:1.7;white-space:pre-line">
                <?php echo nl2br(htmlspecialchars($artist['bio'])); ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /container -->

    <script>
    const BASE_URL = <?php echo (APP_URL . '/' . APP_URL_PANEL); ?>;
    (function() {
        function refreshBadge() {
            fetch(BASE_URL + '/ajax/notifications_api.php?action=count', {
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

    <!-- ═══ JS ═══ -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <script>
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

    <?php if (!empty($tracks)): ?>
    $(document).ready(function() {
        $('#tracksTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            lengthChange: false,
            pageLength: 10,
            order: [
                [4, 'desc']
            ], // ordenar por streams DESC
            columnDefs: [{
                    orderable: false,
                    targets: [0]
                },
                {
                    type: 'num-fmt',
                    targets: [4, 5, 6]
                }
            ],
            language: {
                search: 'Pesquisar faixa:',
                info: 'A mostrar _START_ a _END_ de _TOTAL_ faixas',
                paginate: {
                    next: 'Próximo',
                    previous: 'Anterior'
                },
                emptyTable: 'Nenhuma faixa encontrada.'
            }
        });
    });
    <?php endif; ?>

    <?php if (!empty($chart_datasets)): ?>
    const ctx = document.getElementById('streamChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months_pt_short); ?>,
            datasets: <?php echo json_encode($chart_datasets); ?>
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                y: {
                    beginAtZero: true,
                    stacked: true,
                    title: {
                        display: true,
                        text: 'Streams'
                    }
                },
                x: {
                    stacked: true,
                    title: {
                        display: true,
                        text: 'Mês'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            }
        }
    });
    <?php endif; ?>
    </script>
</body>

</html>