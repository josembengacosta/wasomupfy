<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Exportar Dados de Estatísticas
// Arquivo: dashboard/analytics/export.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];
$user     = getUserById($id_users);
if (!$user) { redirect('authentic/logout'); }

$first_name       = htmlspecialchars($user['first_name']);
$user_artist_name = htmlspecialchars($user['name_artist_band'] ?? $user['first_name']);

// ── Parâmetros ────────────────────────────────
$filter_year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$filter_store = isset($_GET['store']) ? (int)$_GET['store'] : 0;
$do_export    = isset($_GET['do_export']) ? $_GET['do_export'] : '';
// contexto vindo de compare.php
$context = isset($_GET['context']) ? $_GET['context'] : '';

// ── Anos disponíveis ──────────────────────────
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

// ── Lojas activas ─────────────────────────────
$stores_q = $db->prepare("SELECT id_store, name_store, slug_store FROM _store WHERE is_active = 1 ORDER BY display_order ASC");
$stores_q->execute();
$stores    = $stores_q->fetchAll(PDO::FETCH_ASSOC);
$store_map = array_column($stores, null, 'id_store');

// ═══════════════════════════════════════════════
// DOWNLOAD CSV — executado antes de qualquer HTML
// ═══════════════════════════════════════════════
if ($do_export === 'streams_csv') {
    // Validar CSRF
    if (!isset($_GET['csrf']) || !validateCsrf($_GET['csrf'])) {
        redirect('analytics/export');
    }

    $store_clause = $filter_store ? "AND s.id_store = ?" : "";
    $sql = "
        SELECT
            s.year_stream                           AS Ano,
            s.month_stream                          AS Mês,
            st.name_store                           AS Plataforma,
            a.stage_name                            AS Artista,
            al.title_album                          AS Álbum,
            al.type_album                           AS Tipo,
            t.title_track                           AS Faixa,
            t.isrc                                  AS ISRC,
            COALESCE(s.streams,   0)                AS Streams,
            COALESCE(s.downloads, 0)                AS Downloads,
            COALESCE(s.revenue,   0)                AS Receita_USD
        FROM _stream s
        JOIN _track  t  ON t.id_track  = s.id_track
        JOIN _album  al ON al.id_album = t.id_album
        JOIN _store  st ON st.id_store = s.id_store
        LEFT JOIN _artist a ON a.id_artist = al.id_artist
        WHERE t.id_users = ? AND s.year_stream = ?
          $store_clause
        ORDER BY s.year_stream ASC, s.month_stream ASC, st.display_order ASC, a.stage_name ASC
    ";
    $params = [$id_users, $filter_year];
    if ($filter_store) $params[] = $filter_store;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $store_part = $filter_store && isset($store_map[$filter_store]) ? '_' . preg_replace('/\W+/', '', $store_map[$filter_store]['slug_store']) : '';
    $filename   = "wasomupfy_streams_{$filter_year}{$store_part}.csv";

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $out = fopen('php://output', 'w');
    // BOM para Excel abrir UTF-8 correctamente
    fputs($out, "\xEF\xBB\xBF");

    if (!empty($rows)) {
        fputcsv($out, array_keys($rows[0]), ';');
        foreach ($rows as $row) {
            fputcsv($out, $row, ';');
        }
    } else {
        fputcsv($out, ['Sem dados para o período seleccionado.'], ';');
    }
    fclose($out);
    exit;
}

if ($do_export === 'royalties_csv') {
    if (!isset($_GET['csrf']) || !validateCsrf($_GET['csrf'])) {
        redirect('analytics/export');
    }

    $sql = "
        SELECT
            r.year_royalty                                  AS Ano,
            r.month_royalty                                 AS Mês,
            t.title_track                                   AS Faixa,
            t.isrc                                          AS ISRC,
            al.title_album                                  AS Álbum,
            a.stage_name                                    AS Artista,
            COALESCE(r.gross_revenue,    0)                 AS Receita_Bruta_USD,
            COALESCE(r.platform_fee,     0)                 AS Taxa_Plataforma_USD,
            COALESCE(r.net_royalty,      0)                 AS Royalty_Líquido_USD,
            COALESCE(r.net_royalty_aoa,  0)                 AS Royalty_Líquido_AOA,
            r.currency                                      AS Moeda,
            r.status_royalty                                AS Estado
        FROM _royalty r
        JOIN _track  t  ON t.id_track  = r.id_track
        JOIN _album  al ON al.id_album = t.id_album
        LEFT JOIN _artist a ON a.id_artist = al.id_artist
        WHERE r.id_users = ? AND r.year_royalty = ?
        ORDER BY r.year_royalty ASC, r.month_royalty ASC, a.stage_name ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_users, $filter_year]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "wasomupfy_royalties_{$filter_year}.csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    if (!empty($rows)) {
        fputcsv($out, array_keys($rows[0]), ';');
        foreach ($rows as $row) fputcsv($out, $row, ';');
    } else {
        fputcsv($out, ['Sem dados para o período seleccionado.'], ';');
    }
    fclose($out);
    exit;
}

if ($do_export === 'tracks_csv') {
    if (!isset($_GET['csrf']) || !validateCsrf($_GET['csrf'])) {
        redirect('analytics/export');
    }

    $store_clause = $filter_store ? "AND s.id_store = ?" : "";
    $sql = "
        SELECT
            t.isrc                                          AS ISRC,
            t.title_track                                   AS Faixa,
            t.name_author                                   AS Autor,
            t.name_author_feat                              AS Feat,
            al.title_album                                  AS Álbum,
            al.type_album                                   AS Tipo_Álbum,
            al.release_date                                 AS Data_Lançamento,
            al.territory                                    AS Território,
            al.label_name                                   AS Editora,
            a.stage_name                                    AS Artista,
            t.status_track                                  AS Estado,
            COALESCE(SUM(s.streams),  0)                    AS Streams_{$filter_year},
            COALESCE(SUM(s.downloads),0)                    AS Downloads_{$filter_year},
            COALESCE(SUM(s.revenue),  0)                    AS Receita_USD_{$filter_year}
        FROM _track t
        JOIN _album al ON al.id_album = t.id_album
        LEFT JOIN _artist a  ON a.id_artist  = al.id_artist
        LEFT JOIN _stream s  ON s.id_track   = t.id_track
                             AND s.year_stream = ?
                             $store_clause
        WHERE t.id_users = ?
          AND t.status_track IN ('active','approved')
        GROUP BY t.id_track, t.isrc, t.title_track, t.name_author, t.name_author_feat,
                 al.title_album, al.type_album, al.release_date, al.territory, al.label_name,
                 a.stage_name, t.status_track
        ORDER BY Streams_{$filter_year} DESC
    ";
    $params = [$filter_year];
    if ($filter_store) $params[] = $filter_store;
    $params[] = $id_users;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "wasomupfy_faixas_{$filter_year}.csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    if (!empty($rows)) {
        fputcsv($out, array_keys($rows[0]), ';');
        foreach ($rows as $row) fputcsv($out, $row, ';');
    } else {
        fputcsv($out, ['Sem dados para o período seleccionado.'], ';');
    }
    fclose($out);
    exit;
}

// ── Preview: contagens para UI ────────────────
$count_streams_q = $db->prepare("
    SELECT COUNT(*) FROM _stream s
    JOIN _track t ON t.id_track = s.id_track
    WHERE t.id_users = ? AND s.year_stream = ?
    " . ($filter_store ? "AND s.id_store = $filter_store" : "")
);
$count_streams_q->execute([$id_users, $filter_year]);
$count_streams = (int)$count_streams_q->fetchColumn();

$count_royalties_q = $db->prepare("SELECT COUNT(*) FROM _royalty WHERE id_users = ? AND year_royalty = ?");
$count_royalties_q->execute([$id_users, $filter_year]);
$count_royalties = (int)$count_royalties_q->fetchColumn();

$count_tracks_q = $db->prepare("
    SELECT COUNT(DISTINCT t.id_track) FROM _track t
    WHERE t.id_users = ? AND t.status_track IN ('active','approved')
");
$count_tracks_q->execute([$id_users]);
$count_tracks = (int)$count_tracks_q->fetchColumn();

// CSRF para links de download
$csrf = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(16));
$_SESSION['csrf_token'] = $csrf;

$months_pt = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png" />
    <link rel="manifest" href="../manifest.json" />
    <title>Exportar Dados — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="../../css/dashboard-style.css" />
    <link rel="stylesheet" href="../../css/lastest-style.css" />
    <style>
    .export-hero {
        background: linear-gradient(135deg, #0f2a1e 0%, #1a3a28 50%, #1a1a2e 100%);
        border-radius: 18px;
        padding: 2rem;
        margin-bottom: 2rem;
        color: #fff;
    }

    .export-card {
        border-radius: 16px;
        overflow: hidden;
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        background: var(--card-bg, #fff);
        transition: box-shadow .2s, transform .15s;
    }

    .export-card:hover {
        box-shadow: 0 6px 24px rgba(0, 0, 0, .1);
        transform: translateY(-2px);
    }

    .export-card-header {
        padding: 18px 20px 14px;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, .06));
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .export-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .export-card-body {
        padding: 16px 20px 20px;
    }

    .export-meta {
        font-size: .75rem;
        color: var(--text-muted, #6c757d);
    }

    .export-count {
        font-size: 1.1rem;
        font-weight: 800;
    }

    .btn-export-dl {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: .55rem 1.2rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: .85rem;
        text-decoration: none;
        transition: all .2s;
        border: 2px solid transparent;
    }

    .btn-export-csv {
        background: rgba(25, 135, 84, .1);
        color: #198754;
        border-color: rgba(25, 135, 84, .3);
    }

    .btn-export-csv:hover {
        background: #198754;
        color: #fff;
    }

    /* ── Filtros ── */
    .filter-bar {
        background: var(--card-bg, #fff);
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        border-radius: 14px;
        padding: 14px 18px;
        margin-bottom: 24px;
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

    /* ── Aviso ── */
    .export-notice {
        background: rgba(255, 193, 7, .06);
        border: 1px solid rgba(255, 193, 7, .25);
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 20px;
        font-size: .8rem;
        color: var(--text-muted, #6c757d);
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }
    </style>
</head>

<body>

    <!-- ═══ NAVBAR ═══ -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu">
                <span class="navbar-toggler-icon"><i class="bi bi-list text-white fs-1"></i></span>
            </button>
            <a class="navbar-brand" href="../painel">
                <span class="text-light" style="font-weight:bold;font-family:Arial,sans-serif">WASOM UPFY</span>
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav m-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i>
                            Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i>
                            Lançamentos</a></li>
                    <li class="nav-item"><a class="nav-link active" href="statistics"><i class="bi bi-bar-chart"></i>
                            Estatísticas</a></li>
                    <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
                                class="bi bi-currency-dollar"></i> Finanças</a></li>
                    <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i>
                            Artistas</a></li>
                    <li class="nav-item"><a class="nav-link" href="../artists/youtube/ucy"><i class="bi bi-youtube"></i>
                            YouTube</a></li>
                </ul>
            </div>
            <div class="user-menu d-flex align-items-center">
                <a class="theme-toggle text-white me-2" id="themeToggle"><i class="bi bi-sun" id="themeIcon"></i></a>
                <a href="../page/notifications" class="text-white me-2"><i class="bi bi-bell fs-4"></i></a>
                <a href="#" class="text-white" data-bs-toggle="dropdown"><i class="bi bi-person-circle fs-4"></i></a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="../user/profile">
                            <i class="bi bi-person me-2"></i><strong><?php echo $user_artist_name; ?></strong></a>
                        <div class="px-3 pb-1 text-muted" style="font-size:.72rem">Conta
                            <?php echo str_pad($id_users, 6, '0', STR_PAD_LEFT); ?></div>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li><a class="dropdown-item" href="../user/profile"><i class="bi bi-person me-2"></i> Meu Perfil</a>
                    </li>
                    <li><a class="dropdown-item" href="../account/manage-account"><i class="bi bi-tools me-2"></i>
                            Gestão de Conta</a></li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li><a class="dropdown-item" href="../page/settings"><i class="bi bi-gear me-2"></i>
                            Configurações</a></li>
                    <li><a class="dropdown-item" href="../page/notifications"><i class="bi bi-bell me-2"></i>
                            Notificações</a></li>
                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                            data-bs-target="#logoutwasomupfy">
                            <i class="bi bi-box-arrow-right me-2"></i> Desconectar-se</a></li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li><a class="dropdown-item" href="../page/support"><i class="bi bi-headset me-2"></i> Suporte</a>
                    </li>
                    <li><a class="dropdown-item" href="../page/faq"><i class="bi bi-chat-left-text me-2"></i> FAQ</a>
                    </li>
                    <li><span class="dropdown-item-text" id="versionDropdown"></span></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Offcanvas Mobile -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title text-light" style="font-weight:bold;font-family:Arial,sans-serif">WASOM UPFY</h5>
            <button type="button" class="btn-close text-white" data-bs-dismiss="offcanvas"><i
                    class="bi bi-x-lg"></i></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="../painel"><i class="bi bi-speedometer2"></i>
                        Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="../launch/releases"><i class="bi bi-disc"></i>
                        Lançamentos</a></li>
                <li class="nav-item"><a class="nav-link active" href="statistics"><i class="bi bi-bar-chart"></i>
                        Estatísticas</a></li>
                <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
                            class="bi bi-currency-dollar"></i> Finanças</a></li>
                <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i class="bi bi-person"></i>
                        Artistas</a></li>
                <li class="nav-item d-lg-none"><a class="nav-link text-danger" href="#" data-bs-toggle="modal"
                        data-bs-target="#logoutwasomupfy"><i class="bi bi-box-arrow-right"></i> Desconectar-se</a></li>
            </ul>
        </div>
    </div>

    <!-- ═══ MAIN ═══ -->
    <div class="container my-4">

        <!-- Hero -->
        <div class="export-hero">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="bi bi-download me-3"></i>Exportar Dados</h1>
                    <p class="lead mb-0">Faz download dos teus dados de streams, royalties e faixas em formato CSV,
                        compatível com Excel e Google Sheets.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="<?php echo $context === 'compare' ? 'compare' : 'statistics'; ?>" class="btn btn-pink">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <form method="GET" action="export" id="filterForm">
            <?php if ($context): ?><input type="hidden" name="context"
                value="<?php echo htmlspecialchars($context); ?>" /><?php endif; ?>
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
                        <option value="0" <?php echo !$filter_store ? 'selected' : ''; ?>>Todas as plataformas</option>
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
                    Dados de <?php echo $filter_year; ?>
                    <?php echo $filter_store && isset($store_map[$filter_store]) ? '— ' . htmlspecialchars($store_map[$filter_store]['name_store']) : ''; ?>
                </div>
            </div>
        </form>

        <!-- Aviso CSV -->
        <div class="export-notice">
            <i class="bi bi-exclamation-triangle-fill mt-1" style="color:#ffc107;flex-shrink:0"></i>
            <div>
                Os ficheiros CSV são gerados com separador <strong>ponto e vírgula (;)</strong> e codificação
                <strong>UTF-8 com BOM</strong>, para compatibilidade directa com o Microsoft Excel. No Google Sheets usa
                <em>Ficheiro → Importar</em> e selecciona "Ponto e vírgula" como separador.
            </div>
        </div>

        <!-- Cards de exportação -->
        <div class="row g-4">

            <!-- Streams por faixa/plataforma -->
            <div class="col-md-4">
                <div class="export-card h-100">
                    <div class="export-card-header">
                        <div class="export-icon" style="background:rgba(255,0,137,.1);color:#FF0089">
                            <i class="bi bi-headphones"></i>
                        </div>
                        <div>
                            <div class="fw-bold">Streams por Faixa</div>
                            <div class="export-meta">Detalhe mensal por plataforma</div>
                        </div>
                    </div>
                    <div class="export-card-body">
                        <div class="mb-3">
                            <span class="export-count"
                                style="color:#FF0089"><?php echo number_format($count_streams); ?></span>
                            <span class="export-meta ms-1">registos em <?php echo $filter_year; ?></span>
                            <?php if ($filter_store && isset($store_map[$filter_store])): ?>
                            <div class="export-meta mt-1"><i
                                    class="bi bi-funnel me-1"></i><?php echo htmlspecialchars($store_map[$filter_store]['name_store']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="export-meta mb-3">
                            Colunas: Ano, Mês, Plataforma, Artista, Álbum, Tipo, Faixa, ISRC, Streams, Downloads,
                            Receita USD
                        </div>
                        <?php if ($count_streams > 0): ?>
                        <a href="export?do_export=streams_csv&year=<?php echo $filter_year; ?>&store=<?php echo $filter_store; ?>&csrf=<?php echo urlencode($csrf); ?>"
                            class="btn-export-dl btn-export-csv w-100 justify-content-center">
                            <i class="bi bi-filetype-csv"></i> Download CSV
                        </a>
                        <?php else: ?>
                        <button class="btn-export-dl btn-export-csv w-100 justify-content-center" disabled
                            style="opacity:.4;cursor:not-allowed">
                            <i class="bi bi-filetype-csv"></i> Sem dados
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Royalties -->
            <div class="col-md-4">
                <div class="export-card h-100">
                    <div class="export-card-header">
                        <div class="export-icon" style="background:rgba(25,135,84,.1);color:#198754">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div>
                            <div class="fw-bold">Royalties</div>
                            <div class="export-meta">Receitas mensais por faixa</div>
                        </div>
                    </div>
                    <div class="export-card-body">
                        <div class="mb-3">
                            <span class="export-count"
                                style="color:#198754"><?php echo number_format($count_royalties); ?></span>
                            <span class="export-meta ms-1">registos em <?php echo $filter_year; ?></span>
                        </div>
                        <div class="export-meta mb-3">
                            Colunas: Ano, Mês, Faixa, ISRC, Álbum, Artista, Receita Bruta, Taxa, Royalty Líquido USD,
                            Royalty AOA, Estado
                        </div>
                        <?php if ($count_royalties > 0): ?>
                        <a href="export?do_export=royalties_csv&year=<?php echo $filter_year; ?>&csrf=<?php echo urlencode($csrf); ?>"
                            class="btn-export-dl btn-export-csv w-100 justify-content-center">
                            <i class="bi bi-filetype-csv"></i> Download CSV
                        </a>
                        <?php else: ?>
                        <button class="btn-export-dl btn-export-csv w-100 justify-content-center" disabled
                            style="opacity:.4;cursor:not-allowed">
                            <i class="bi bi-filetype-csv"></i> Sem dados
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Catálogo de faixas -->
            <div class="col-md-4">
                <div class="export-card h-100">
                    <div class="export-card-header">
                        <div class="export-icon" style="background:rgba(13,110,253,.1);color:#0d6efd">
                            <i class="bi bi-music-note-list"></i>
                        </div>
                        <div>
                            <div class="fw-bold">Catálogo de Faixas</div>
                            <div class="export-meta">Todas as faixas activas + streams <?php echo $filter_year; ?></div>
                        </div>
                    </div>
                    <div class="export-card-body">
                        <div class="mb-3">
                            <span class="export-count"
                                style="color:#0d6efd"><?php echo number_format($count_tracks); ?></span>
                            <span class="export-meta ms-1">faixas activas</span>
                        </div>
                        <div class="export-meta mb-3">
                            Colunas: ISRC, Faixa, Autor, Feat, Álbum, Tipo, Data Lançamento, Território, Editora,
                            Artista, Estado, Streams, Downloads, Receita USD
                        </div>
                        <?php if ($count_tracks > 0): ?>
                        <a href="export?do_export=tracks_csv&year=<?php echo $filter_year; ?>&store=<?php echo $filter_store; ?>&csrf=<?php echo urlencode($csrf); ?>"
                            class="btn-export-dl btn-export-csv w-100 justify-content-center">
                            <i class="bi bi-filetype-csv"></i> Download CSV
                        </a>
                        <?php else: ?>
                        <button class="btn-export-dl btn-export-csv w-100 justify-content-center" disabled
                            style="opacity:.4;cursor:not-allowed">
                            <i class="bi bi-filetype-csv"></i> Sem dados
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div><!-- /row -->

        <!-- Secção PDF — future -->
        <div class="mt-5 mb-2">
            <div class="d-flex align-items-center gap-2 mb-3">
                <h6 class="mb-0">Relatórios PDF</h6>
                <span class="badge bg-secondary" style="font-size:.65rem">Em breve</span>
            </div>
            <div class="row g-3">
                <?php
            $pdf_cards = [
                ['icon'=>'bi-bar-chart-line','label'=>'Relatório de Streams','desc'=>'Resumo mensal com gráficos por plataforma'],
                ['icon'=>'bi-file-earmark-text','label'=>'Relatório Financeiro','desc'=>'Royalties, deduções e saldo no período'],
                ['icon'=>'bi-person-badge','label'=>'Relatório por Artista','desc'=>'Performance individual por artista'],
            ];
            foreach ($pdf_cards as $pc):
            ?>
                <div class="col-md-4">
                    <div class="export-card" style="opacity:.5;pointer-events:none">
                        <div class="export-card-header">
                            <div class="export-icon" style="background:rgba(220,53,69,.1);color:#dc3545">
                                <i class="bi <?php echo $pc['icon']; ?>"></i>
                            </div>
                            <div>
                                <div class="fw-bold"><?php echo $pc['label']; ?></div>
                                <div class="export-meta"><?php echo $pc['desc']; ?></div>
                            </div>
                        </div>
                        <div class="export-card-body">
                            <button class="btn-export-dl w-100 justify-content-center" disabled
                                style="background:rgba(220,53,69,.08);color:#dc3545;border:2px solid rgba(220,53,69,.2)">
                                <i class="bi bi-filetype-pdf"></i> Em breve
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- /container -->

    <!-- Bottom Nav Mobile -->
    <nav class="bottom-nav d-lg-none">
        <ul class="nav justify-content-around">
            <li class="nav-item"><a class="nav-link" href="../painel"><i
                        class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../launch/releases"><i
                        class="bi bi-disc"></i><span>Lançamentos</span></a></li>
            <li class="nav-item"><a class="nav-link active" href="statistics"><i
                        class="bi bi-bar-chart"></i><span>Estatísticas</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../finances/overview"><i
                        class="bi bi-currency-dollar"></i><span>Finanças</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../artists/artists-list"><i
                        class="bi bi-person"></i><span>Artistas</span></a></li>
        </ul>
    </nav>

    <!-- Modal Logout -->
    <div class="modal fade" id="logoutwasomupfy" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">Terminar sessão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center text-dark">
                    <p>Tens a certeza de que desejas terminar sessão, <strong><?php echo $first_name; ?></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Não, continuar</button>
                    <a href="../logout" class="btn btn-danger">Sim, terminar sessão</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/theme.wp.js"></script>
    <script src="../../js/wp.tools.js"></script>
    <script>
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    </script>
</body>

</html>