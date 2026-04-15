<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Estatísticas (Colaboradores)
// Arquivo: dashboard/collab/statistics.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();

if (empty($_SESSION['collab_id']) || empty($_SESSION['collab_id_users'])) {
    header('Location: ' . rtrim(APP_URL, '/') . '/dashboard/account/collab-login');
    exit;
}
if (!empty($_SESSION['collab_must_change'])) {
    header('Location: ' . rtrim(APP_URL, '/') . '/dashboard/account/collab-login');
    exit;
}

$db        = getDB();
$id_collab = (int)$_SESSION['collab_id'];
$id_users  = (int)$_SESSION['collab_id_users'];
$role      = $_SESSION['collab_role'] ?? 'support';

$cs = $db->prepare("SELECT * FROM _collaborators WHERE id_collab = ? AND id_users = ? AND status_collab = 'active' LIMIT 1");
$cs->execute([$id_collab, $id_users]);
$collab = $cs->fetch();
if (!$collab) {
    session_destroy();
    header('Location: ' . rtrim(APP_URL, '/') . '/dashboard/account/collab-login?error=access');
    exit;
}

$db->prepare("UPDATE _collaborators SET last_seen_at = NOW() WHERE id_collab = ?")->execute([$id_collab]);

$owner = getUserById($id_users);
if (!$owner) {
    session_destroy();
    header('Location: ' . rtrim(APP_URL, '/') . '/dashboard/account/collab-login');
    exit;
}

$owner_artist_name = htmlspecialchars($owner['name_artist_band'] ?? $owner['first_name']);
$plan = null;
if ($owner['plan_selected']) {
    $ps = $db->prepare("SELECT * FROM _plans WHERE id_plan = ?");
    $ps->execute([$owner['plan_selected']]);
    $plan = $ps->fetch();
}
$plan_name = $plan ? htmlspecialchars($plan['name_plan']) : 'Sem plano';

// ── Permissões ────────────────────────────────
$can_view_releases = in_array($role, ['admin', 'editor', 'support']);
$can_view_artists  = in_array($role, ['admin', 'editor']);
$can_view_finances = in_array($role, ['admin', 'analyst']);
$can_view_stats    = in_array($role, ['admin', 'analyst', 'editor']);

if (!$can_view_stats) {
    header('Location: ' . rtrim(APP_URL, '/') . APP_URL_PANEL . '/collab/overview?error=noaccess');
    exit;
}

// ── Filtro de período ─────────────────────────
$year  = (int)($_GET['year']  ?? date('Y'));
$month = (int)($_GET['month'] ?? 0); // 0 = ano todo

// WHERE para queries que usam alias 's' (_stream)
$period_where = $month > 0
    ? "AND s.year_stream = ? AND s.month_stream = ?"
    : "AND s.year_stream = ?";
$period_params_base = $month > 0 ? [$year, $month] : [$year];

// WHERE para queries que usam alias 'sc' (_stream_country)
$period_where_sc = $month > 0
    ? "AND sc.year_stream = ? AND sc.month_stream = ?"
    : "AND sc.year_stream = ?";

// ── Streams totais + receita ──────────────────
// Joins: _stream → _track → _album → id_users
$streams_q = $db->prepare("
    SELECT
        SUM(s.streams)   AS total_streams,
        SUM(s.downloads) AS total_downloads,
        SUM(s.revenue)   AS total_revenue
    FROM _stream s
    JOIN _track  t  ON t.id_track  = s.id_track
    JOIN _album  a  ON a.id_album  = t.id_album
    WHERE a.id_users = ? $period_where
");
$streams_q->execute(array_merge([$id_users], $period_params_base));
$totals = $streams_q->fetch();

// ── Top faixas (por streams) ──────────────────
$top_tracks_q = $db->prepare("
    SELECT
        t.title_track,
        t.name_author,
        a.title_album,
        a.img_cover,
        SUM(s.streams) AS total_streams,
        SUM(s.revenue) AS total_revenue
    FROM _stream s
    JOIN _track t ON t.id_track = s.id_track
    JOIN _album a ON a.id_album = t.id_album
    WHERE a.id_users = ? $period_where
    GROUP BY t.id_track
    ORDER BY total_streams DESC
    LIMIT 10
");
$top_tracks_q->execute(array_merge([$id_users], $period_params_base));
$top_tracks = $top_tracks_q->fetchAll(PDO::FETCH_ASSOC);

// ── Streams por plataforma ────────────────────
$by_store_q = $db->prepare("
    SELECT
        st.name_store,
        st.slug_store,
        st.logo_store,
        SUM(s.streams)   AS total_streams,
        SUM(s.revenue)   AS total_revenue
    FROM _stream s
    JOIN _track  t  ON t.id_track  = s.id_track
    JOIN _album  a  ON a.id_album  = t.id_album
    JOIN _store  st ON st.id_store = s.id_store
    WHERE a.id_users = ? $period_where
    GROUP BY s.id_store
    ORDER BY total_streams DESC
    LIMIT 8
");
$by_store_q->execute(array_merge([$id_users], $period_params_base));
$by_store = $by_store_q->fetchAll(PDO::FETCH_ASSOC);

// ── Streams mensais (gráfico — últimos 12 meses) ──
$monthly_q = $db->prepare("
    SELECT s.year_stream, s.month_stream,
           SUM(s.streams) AS streams, SUM(s.revenue) AS revenue
    FROM _stream s
    JOIN _track t ON t.id_track = s.id_track
    JOIN _album a ON a.id_album = t.id_album
    WHERE a.id_users = ?
      AND (s.year_stream > ? OR (s.year_stream = ? AND s.month_stream >= ?))
    GROUP BY s.year_stream, s.month_stream
    ORDER BY s.year_stream ASC, s.month_stream ASC
");
$cur_month = (int)date('n');
$cur_year  = (int)date('Y');
$from_year  = $cur_month <= 12 ? $cur_year - 1 : $cur_year;
$from_month = $cur_month == 12 ? 1 : $cur_month + 1;
$monthly_q->execute([$id_users, $from_year, $from_year, $from_month]);
$monthly_raw = $monthly_q->fetchAll(PDO::FETCH_ASSOC);

// Preencher os 12 meses mesmo que sem dados
$monthly_chart = [];
for ($i = 11; $i >= 0; $i--) {
    $ts  = mktime(0, 0, 0, $cur_month - $i, 1, $cur_year);
    $y   = (int)date('Y', $ts);
    $m   = (int)date('n', $ts);
    $lbl = date('M/y', $ts);
    $monthly_chart[] = ['year' => $y, 'month' => $m, 'label' => $lbl, 'streams' => 0, 'revenue' => 0.0];
}
foreach ($monthly_raw as $row) {
    foreach ($monthly_chart as &$mc) {
        if ($mc['year'] == $row['year_stream'] && $mc['month'] == $row['month_stream']) {
            $mc['streams'] = (int)$row['streams'];
            $mc['revenue'] = (float)$row['revenue'];
        }
    }
}

// ── Top países ───────────────────────────────
$countries_q = $db->prepare("
    SELECT sc.country_name, sc.country_code,
           SUM(sc.streams) AS total_streams,
           SUM(sc.revenue) AS total_revenue
    FROM _stream_country sc
    JOIN _track t ON t.id_track = sc.id_track
    JOIN _album a ON a.id_album = t.id_album
    WHERE a.id_users = ? $period_where_sc
    GROUP BY sc.country_code
    ORDER BY total_streams DESC
    LIMIT 8
");
$countries_q->execute(array_merge([$id_users], $period_params_base));
$top_countries = $countries_q->fetchAll(PDO::FETCH_ASSOC);

// ── Anos disponíveis para o filtro ───────────
$years_q = $db->prepare("
    SELECT DISTINCT s.year_stream FROM _stream s
    JOIN _track t ON t.id_track = s.id_track
    JOIN _album a ON a.id_album = t.id_album
    WHERE a.id_users = ? ORDER BY s.year_stream DESC
");
$years_q->execute([$id_users]);
$available_years = $years_q->fetchAll(PDO::FETCH_COLUMN);
if (empty($available_years)) $available_years = [date('Y')];

// ── Helpers ───────────────────────────────────
$role_meta = [
    'admin'   => ['label' => 'Administrador', 'color' => '#dc3545', 'bg' => 'rgba(220,53,69,.1)',  'icon' => 'bi-shield-fill'],
    'editor'  => ['label' => 'Editor',       'color' => '#FF0089', 'bg' => 'rgba(255,0,137,.1)', 'icon' => 'bi-pencil-fill'],
    'analyst' => ['label' => 'Analista',     'color' => '#0d6efd', 'bg' => 'rgba(13,110,253,.1)', 'icon' => 'bi-bar-chart-fill'],
    'support' => ['label' => 'Suporte',      'color' => '#198754', 'bg' => 'rgba(25,135,84,.1)', 'icon' => 'bi-headset'],
];
$rm = $role_meta[$role] ?? $role_meta['support'];
$role_label = $rm['label'];

$months_pt = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
$logout_url = rtrim(APP_URL, '/') . APP_URL_PANEL . '/collab/logout';
$base_url   = rtrim(APP_URL, '/');
$cover_base = $base_url . '/assets/comprovantes/uploads/covers/';

function fmt_streams(int $n): string
{
    if ($n >= 1_000_000) return number_format($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)     return number_format($n / 1_000, 1) . 'K';
    return (string)$n;
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF2D66" />
    <title>Estatísticas — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/collab.css" />
    <style>
    /* Barra de plataforma */
    .store-bar {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 0;
        border-bottom: 1px solid var(--border);
    }

    .store-bar:last-child {
        border-bottom: none;
    }

    .store-logo {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        object-fit: contain;
        background: rgba(255, 0, 137, .05);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .7rem;
        font-weight: 700;
        color: var(--wasom);
        flex-shrink: 0;
    }

    .store-progress {
        flex: 1;
        height: 6px;
        background: var(--border);
        border-radius: 10px;
        overflow: hidden;
    }

    .store-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #FF0089, #FF4D4D);
        border-radius: 10px;
        transition: width .5s ease;
    }

    /* Top faixas */
    .track-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid var(--border);
    }

    .track-row:last-child {
        border-bottom: none;
    }

    .track-rank {
        width: 24px;
        font-size: .75rem;
        font-weight: 800;
        color: var(--muted);
        flex-shrink: 0;
        text-align: center;
    }

    .track-rank.top3 {
        color: var(--wasom);
    }

    .track-cover {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        object-fit: cover;
        background: rgba(255, 0, 137, .07);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .8rem;
        overflow: hidden;
    }

    .track-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Top países */
    .country-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 7px 0;
        border-bottom: 1px solid var(--border);
    }

    .country-row:last-child {
        border-bottom: none;
    }

    .country-flag {
        width: 28px;
        height: 20px;
        border-radius: 3px;
        object-fit: cover;
        flex-shrink: 0;
    }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <?php require_once __DIR__ . '/include/navbar-top.php'; ?>
    <!-- ═══ SIDEBAR OVERLAY (mobile) ═══ -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- ═══ SIDEBAR ═══ -->
    <?php require_once __DIR__ . '/include/sidebar.php'; ?>

    <!-- MAIN -->
    <main class="main-content">

        <!-- Cabeçalho -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h4 fw-bold mb-1"><i class="bi bi-bar-chart-fill me-2"
                        style="color:var(--wasom)"></i>Estatísticas</h1>
                <p class="text-muted small mb-0">Conta de <?php echo $owner_artist_name; ?></p>
            </div>
        </div>

        <!-- Filtro de período -->
        <div class="filter-bar">
            <i class="bi bi-funnel" style="color:var(--wasom)"></i>
            <span class="text-muted small fw-semibold">Período:</span>
            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                <select name="year" class="form-select form-select-sm" style="width:auto;border-color:var(--border)"
                    onchange="this.form.submit()">
                    <?php foreach ($available_years as $y): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select name="month" class="form-select form-select-sm" style="width:auto;border-color:var(--border)"
                    onchange="this.form.submit()">
                    <option value="0" <?php echo $month === 0 ? 'selected' : ''; ?>>Ano todo</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m === $month ? 'selected' : ''; ?>>
                        <?php echo $months_pt[$m]; ?></option>
                    <?php endfor; ?>
                </select>
                <?php if ($month > 0): ?>
                <a href="?year=<?php echo $year; ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></a>
                <?php endif; ?>
            </form>
            <span class="text-muted small ms-auto">
                <i class="bi bi-eye me-1"></i>Só leitura
            </span>
        </div>

        <!-- 3 Stat cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(255,0,137,.1)"><i class="bi bi-play-circle"
                            style="color:var(--wasom)"></i></div>
                    <div>
                        <div class="stat-value"><?php echo fmt_streams((int)($totals['total_streams'] ?? 0)); ?></div>
                        <div class="stat-label">Streams totais</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(13,110,253,.1)"><i class="bi bi-download"
                            style="color:#0d6efd"></i></div>
                    <div>
                        <div class="stat-value"><?php echo fmt_streams((int)($totals['total_downloads'] ?? 0)); ?></div>
                        <div class="stat-label">Downloads totais</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(25,135,84,.1)"><i class="bi bi-currency-dollar"
                            style="color:#198754"></i></div>
                    <div>
                        <div class="stat-value">$<?php echo number_format((float)($totals['total_revenue'] ?? 0), 2); ?>
                        </div>
                        <div class="stat-label">Receita bruta (USD)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de streams mensais -->
        <div class="dash-card mb-4">
            <div class="card-title"><i class="bi bi-graph-up"></i>Streams — últimos 12 meses</div>
            <div style="position:relative;height:220px">
                <canvas id="streamsChart"></canvas>
            </div>
        </div>

        <div class="row g-4">
            <!-- Top 10 faixas -->
            <div class="col-lg-6">
                <div class="dash-card h-100">
                    <div class="card-title"><i class="bi bi-music-note-list"></i>Top 10 faixas</div>
                    <?php if (empty($top_tracks)): ?>
                    <div class="empty-state">
                        <div class="icon">🎵</div>
                        <div class="small">Sem dados neste período</div>
                    </div>
                    <?php else:
                        $max_s = max(1, (int)$top_tracks[0]['total_streams']);
                        foreach ($top_tracks as $i => $tr):
                            $pct = min(100, round($tr['total_streams'] / $max_s * 100));
                        ?>
                    <div class="track-row">
                        <div class="track-rank <?php echo $i < 3 ? 'top3' : ''; ?>"><?php echo $i + 1; ?></div>
                        <div class="track-cover">
                            <?php if ($tr['img_cover']): ?>
                            <img src="<?php echo htmlspecialchars($cover_base . $tr['img_cover']); ?>"
                                onerror="this.parentElement.textContent='🎵'" alt="" />
                            <?php else: ?>🎵<?php endif; ?>
                        </div>
                        <div style="flex:1;min-width:0">
                            <div class="fw-semibold text-truncate" style="font-size:.82rem">
                                <?php echo htmlspecialchars($tr['title_track']); ?></div>
                            <div class="text-muted text-truncate" style="font-size:.7rem">
                                <?php echo htmlspecialchars($tr['name_author'] ?? $tr['title_album']); ?></div>
                            <div style="height:3px;background:var(--border);border-radius:4px;margin-top:4px">
                                <div
                                    style="width:<?php echo $pct; ?>%;height:100%;background:linear-gradient(90deg,#FF0089,#FF4D4D);border-radius:4px">
                                </div>
                            </div>
                        </div>
                        <div style="text-align:right;flex-shrink:0">
                            <div class="fw-bold" style="font-size:.82rem;color:var(--wasom)">
                                <?php echo fmt_streams((int)$tr['total_streams']); ?></div>
                            <div class="text-muted" style="font-size:.65rem">streams</div>
                        </div>
                    </div>
                    <?php endforeach;
                    endif; ?>
                </div>
            </div>

            <!-- Por plataforma + Top países -->
            <div class="col-lg-6 d-flex flex-column gap-4">
                <!-- Por plataforma -->
                <div class="dash-card">
                    <div class="card-title"><i class="bi bi-grid-3x3-gap"></i>Por plataforma</div>
                    <?php if (empty($by_store)): ?>
                    <div class="empty-state">
                        <div class="icon">🏪</div>
                        <div class="small">Sem dados</div>
                    </div>
                    <?php else:
                        $max_st = max(1, (int)$by_store[0]['total_streams']);
                        foreach ($by_store as $st):
                            $pct = min(100, round($st['total_streams'] / $max_st * 100));
                        ?>
                    <div class="store-bar">
                        <div class="store-logo">
                            <?php if ($st['logo_store']): ?>
                            <img src="<?php echo htmlspecialchars($base_url . '/' . $st['logo_store']); ?>"
                                style="width:28px;height:28px;border-radius:6px;object-fit:contain"
                                onerror="this.style.display='none'" alt="" />
                            <?php else: ?>/<?php echo mb_substr($st['name_store'], 0, 2); ?><?php endif; ?>
                        </div>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:.78rem;font-weight:600;margin-bottom:3px">
                                <?php echo htmlspecialchars($st['name_store']); ?></div>
                            <div class="store-progress">
                                <div class="store-progress-fill" style="width:<?php echo $pct; ?>%"></div>
                            </div>
                        </div>
                        <div style="text-align:right;flex-shrink:0;font-size:.78rem">
                            <div class="fw-bold"><?php echo fmt_streams((int)$st['total_streams']); ?></div>
                        </div>
                    </div>
                    <?php endforeach;
                    endif; ?>
                </div>

                <!-- Top países -->
                <div class="dash-card">
                    <div class="card-title"><i class="bi bi-globe"></i>Top países</div>
                    <?php if (empty($top_countries)): ?>
                    <div class="empty-state">
                        <div class="icon">🌍</div>
                        <div class="small">Sem dados</div>
                    </div>
                    <?php else:
                        $max_c = max(1, (int)$top_countries[0]['total_streams']);
                        foreach ($top_countries as $cc):
                            $pct = min(100, round($cc['total_streams'] / $max_c * 100));
                        ?>
                    <div class="country-row">
                        <img src="https://flagcdn.com/w40/<?php echo strtolower($cc['country_code']); ?>.png"
                            class="country-flag" onerror="this.style.display='none'"
                            alt="<?php echo $cc['country_code']; ?>" />
                        <div style="flex:1;min-width:0">
                            <div style="font-size:.78rem;font-weight:600;margin-bottom:3px">
                                <?php echo htmlspecialchars($cc['country_name'] ?: $cc['country_code']); ?></div>
                            <div class="store-progress">
                                <div class="store-progress-fill" style="width:<?php echo $pct; ?>%"></div>
                            </div>
                        </div>
                        <div style="text-align:right;flex-shrink:0;font-size:.78rem">
                            <div class="fw-bold"><?php echo fmt_streams((int)$cc['total_streams']); ?></div>
                        </div>
                    </div>
                    <?php endforeach;
                    endif; ?>
                </div>
            </div>
        </div>

    </main>

    <!-- Bottom nav -->
    <?php require_once __DIR__ . '/include/navbar-bottom.php'; ?>

    <?php require_once __DIR__ . '/include/modallogoutmyprofile.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
    const CHART_DATA = <?php echo json_encode(array_map(fn($r) => [
                                'label'   => $r['label'],
                                'streams' => $r['streams'],
                                'revenue' => round($r['revenue'], 2),
                            ], $monthly_chart)); ?>;

    // ── Gráfico ───────────────────────────────────
    const isDark = localStorage.getItem('wu_theme') === 'dark';
    const gridColor = isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)';
    const textColor = isDark ? '#9999bb' : '#6c757d';

    new Chart(document.getElementById('streamsChart'), {
        type: 'bar',
        data: {
            labels: CHART_DATA.map(r => r.label),
            datasets: [{
                label: 'Streams',
                data: CHART_DATA.map(r => r.streams),
                backgroundColor: 'rgba(255,0,137,.15)',
                borderColor: '#FF0089',
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: {
                        color: gridColor
                    },
                    ticks: {
                        color: textColor,
                        font: {
                            size: 10
                        }
                    }
                },
                y: {
                    grid: {
                        color: gridColor
                    },
                    ticks: {
                        color: textColor,
                        font: {
                            size: 10
                        },
                        callback: v => v >= 1000000 ? (v / 1000000).toFixed(1) + 'M' : v >= 1000 ? (v / 1000)
                            .toFixed(0) + 'K' : v
                    }
                }
            }
        }
    });

    // ── Sidebar / Theme ───────────────────────────
    function closeSidebar() {
        document.getElementById('collabSidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
    }
    document.getElementById('btn-sidebar-toggle')?.addEventListener('click', () => {
        const sb = document.getElementById('collabSidebar');
        const ov = document.getElementById('sidebarOverlay');
        sb.classList.toggle('open');
        ov.classList.toggle('show', sb.classList.contains('open'));
    });
    const html = document.documentElement;
    const saved = localStorage.getItem('wu_theme') || 'light';
    html.setAttribute('data-theme', saved);
    document.getElementById('themeIcon').className = saved === 'dark' ? 'bi bi-moon' : 'bi bi-sun';
    document.getElementById('themeToggle').addEventListener('click', () => {
        const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        localStorage.setItem('wu_theme', next);
        document.getElementById('themeIcon').className = next === 'dark' ? 'bi bi-moon' : 'bi bi-sun';
    });
    setInterval(() => fetch('<?php echo $base_url; ?>/<?php echo APP_URL_PANEL ?>/collab/ping', {
        method: 'POST'
    }).catch(() => {}), 120000);
    </script>
</body>

</html>