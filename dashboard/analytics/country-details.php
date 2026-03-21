<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Detalhes por País (Estatísticas)
// Arquivo: dashboard/analytics/country-details.php
// ══════════════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$db       = getDB();
$id_users = (int)$_SESSION['id_users'];
$user     = getUserById($id_users);
if (!$user) {
    redirect('../logout');
}

$first_name       = htmlspecialchars($user['first_name']);
$user_artist_name = htmlspecialchars($user['name_artist_band'] ?? $user['first_name']);

// ── Parâmetros ────────────────────────────────
// ?country= vem como string do nome do país (vem da statistics.php)
$country_raw  = isset($_GET['country']) ? trim($_GET['country']) : '';
$filter_year  = isset($_GET['year'])    ? (int)$_GET['year']    : (int)date('Y');

// Sanitizar: máx 80 chars, apenas letras/espaços/hífens/parênteses
$country_name = preg_replace('/[^a-zA-ZÀ-ÿ0-9 \-\(\)\.]/u', '', $country_raw);
$country_name = mb_substr($country_name, 0, 80);

if (!$country_name) {
    redirect(APP_URL_PANEL . '/statistics#country');
}

// ── Mapa: país → coordenadas + ISO2 ──────────
// Subset dos países mais relevantes para a plataforma
$country_meta = [
    'Angola'            => ['lat' => -11.2027, 'lng' => 17.8739, 'iso' => 'ao'],
    'Brasil'            => ['lat' => -14.2350, 'lng' => -51.9253, 'iso' => 'br'],
    'Brazil'            => ['lat' => -14.2350, 'lng' => -51.9253, 'iso' => 'br'],
    'Portugal'          => ['lat' =>  39.3999, 'lng' => -8.2245, 'iso' => 'pt'],
    'USA'               => ['lat' =>  37.0902, 'lng' => -95.7129, 'iso' => 'us'],
    'United States'     => ['lat' =>  37.0902, 'lng' => -95.7129, 'iso' => 'us'],
    'Cabo Verde'        => ['lat' =>  16.0000, 'lng' => -24.0132, 'iso' => 'cv'],
    'Cape Verde'        => ['lat' =>  16.0000, 'lng' => -24.0132, 'iso' => 'cv'],
    'Moçambique'        => ['lat' => -18.6657, 'lng' =>  35.5296, 'iso' => 'mz'],
    'Mozambique'        => ['lat' => -18.6657, 'lng' =>  35.5296, 'iso' => 'mz'],
    'São Tomé e Príncipe' => ['lat' =>   0.1864, 'lng' =>   6.6131, 'iso' => 'st'],
    'Guiné-Bissau'      => ['lat' =>  11.8037, 'lng' => -15.1804, 'iso' => 'gw'],
    'Timor-Leste'       => ['lat' =>  -8.8742, 'lng' => 125.7275, 'iso' => 'tl'],
    'Namíbia'           => ['lat' => -22.9576, 'lng' =>  18.4904, 'iso' => 'na'],
    'Namibia'           => ['lat' => -22.9576, 'lng' =>  18.4904, 'iso' => 'na'],
    'Congo'             => ['lat' =>  -4.0383, 'lng' =>  21.7587, 'iso' => 'cd'],
    'South Africa'      => ['lat' => -30.5595, 'lng' =>  22.9375, 'iso' => 'za'],
    'África do Sul'     => ['lat' => -30.5595, 'lng' =>  22.9375, 'iso' => 'za'],
    'Nigeria'           => ['lat' =>   9.0820, 'lng' =>   8.6753, 'iso' => 'ng'],
    'Nigéria'           => ['lat' =>   9.0820, 'lng' =>   8.6753, 'iso' => 'ng'],
    'Ghana'             => ['lat' =>   7.9465, 'lng' =>  -1.0232, 'iso' => 'gh'],
    'Kenya'             => ['lat' =>  -0.0236, 'lng' =>  37.9062, 'iso' => 'ke'],
    'France'            => ['lat' =>  46.2276, 'lng' =>   2.2137, 'iso' => 'fr'],
    'França'            => ['lat' =>  46.2276, 'lng' =>   2.2137, 'iso' => 'fr'],
    'United Kingdom'    => ['lat' =>  55.3781, 'lng' =>  -3.4360, 'iso' => 'gb'],
    'Reino Unido'       => ['lat' =>  55.3781, 'lng' =>  -3.4360, 'iso' => 'gb'],
    'Germany'           => ['lat' =>  51.1657, 'lng' =>  10.4515, 'iso' => 'de'],
    'Alemanha'          => ['lat' =>  51.1657, 'lng' =>  10.4515, 'iso' => 'de'],
    'Spain'             => ['lat' =>  40.4637, 'lng' =>  -3.7492, 'iso' => 'es'],
    'Espanha'           => ['lat' =>  40.4637, 'lng' =>  -3.7492, 'iso' => 'es'],
    'Canada'            => ['lat' =>  56.1304, 'lng' => -106.3468, 'iso' => 'ca'],
    'Canadá'            => ['lat' =>  56.1304, 'lng' => -106.3468, 'iso' => 'ca'],
    'Worldwide'         => ['lat' =>   0.0000, 'lng' =>   0.0000, 'iso' => ''],
];

// Lookup case-insensitive
$meta = null;
foreach ($country_meta as $k => $v) {
    if (mb_strtolower($k) === mb_strtolower($country_name)) {
        $meta = $v;
        break;
    }
}
// Fallback: coordenadas do país detectado via slug parcial
if (!$meta) $meta = ['lat' => 0, 'lng' => 0, 'iso' => ''];

$flag_url = $meta['iso'] ? "https://flagcdn.com/48x36/{$meta['iso']}.png" : '';
$flag_20  = $meta['iso'] ? "https://flagcdn.com/20x15/{$meta['iso']}.png" : '';

// ── Anos disponíveis ──────────────────────────
$years_q = $db->prepare("
    SELECT DISTINCT s.year_stream
    FROM _stream s
    JOIN _track t  ON t.id_track  = s.id_track
    WHERE t.id_users = ?
    ORDER BY s.year_stream DESC
");
$years_q->execute([$id_users]);
$available_years = $years_q->fetchAll(PDO::FETCH_COLUMN);
if (empty($available_years)) $available_years = [(int)date('Y')];

// ── Álbuns distribuídos neste território ─────
// _album.territory pode ser 'Worldwide', nome de país, ou lista
// Fazemos LIKE para cobrir casos multi-valor
$albums_q = $db->prepare("
    SELECT
        al.id_album,
        al.title_album,
        al.type_album,
        al.img_cover,
        al.territory,
        al.release_date,
        al.genre_main,
        a.stage_name,
        COUNT(t.id_track)           AS num_tracks,
        COALESCE(SUM(s.streams), 0) AS total_streams,
        COALESCE(SUM(s.revenue), 0) AS total_revenue
    FROM _album al
    LEFT JOIN _artist a  ON a.id_artist = al.id_artist
    LEFT JOIN _track  t  ON t.id_album  = al.id_album AND t.status_track IN ('active','approved')
    LEFT JOIN _stream s  ON s.id_track  = t.id_track  AND s.year_stream = ?
    WHERE al.id_users = ?
      AND al.status_album IN ('approved','active')
      AND (
          al.territory LIKE '%Worldwide%'
          OR al.territory LIKE ?
      )
    GROUP BY al.id_album, al.title_album, al.type_album, al.img_cover,
             al.territory, al.release_date, al.genre_main, a.stage_name
    ORDER BY total_streams DESC, al.release_date DESC
");
$albums_q->execute([$filter_year, $id_users, '%' . $country_name . '%']);
$albums = $albums_q->fetchAll(PDO::FETCH_ASSOC);

// ── Totais agregados ──────────────────────────
$total_streams_all  = array_sum(array_column($albums, 'total_streams'));
$total_revenue_all  = array_sum(array_column($albums, 'total_revenue'));
$total_albums       = count($albums);
$total_tracks_all   = array_sum(array_column($albums, 'num_tracks'));

$base_url  = rtrim(APP_URL, '/');
$cover_url = $base_url . '/assets/comprovantes/uploads/covers/';
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#FF0089" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png" />
    <link rel="manifest" href="../manifest.json" />
    <title><?php echo htmlspecialchars($country_name); ?> — Estatísticas — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/dashboard-style.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/country.details.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
    /* ══ Hero do país ══ */
    .country-hero {
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 28px;
        background: linear-gradient(135deg, #0f3460 0%, #16213e 60%, #1a1a2e 100%);
        position: relative;
        min-height: 150px;
    }

    .country-hero .hero-body {
        position: relative;
        z-index: 1;
        padding: 28px 28px 24px;
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .country-flag-lg {
        width: 80px;
        height: 60px;
        border-radius: 8px;
        object-fit: cover;
        box-shadow: 0 4px 16px rgba(0, 0, 0, .4);
        flex-shrink: 0;
    }

    .country-flag-placeholder {
        width: 80px;
        height: 60px;
        border-radius: 8px;
        background: rgba(255, 255, 255, .1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
    }

    .country-hero-info h2 {
        color: #fff;
        font-weight: 800;
        margin: 0 0 4px;
    }

    .country-hero-info .meta {
        color: rgba(255, 255, 255, .6);
        font-size: .82rem;
    }

    .country-hero-info .meta span {
        margin-right: 14px;
    }

    /* ══ Cards ══ */
    .stat-hero-card {
        border-radius: 16px;
        padding: 18px 20px;
        border: 1.5px solid var(--border-color, rgba(0, 0, 0, .08));
        background: var(--card-bg, #fff);
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
        background: var(--card-bg, #fff);
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

    /* ══ Mapa ══ */
    #country-map {
        height: 260px;
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 24px;
    }

    /* ══ Tabela de álbuns ══ */
    .album-cover {
        width: 42px;
        height: 42px;
        border-radius: 8px;
        object-fit: cover;
    }

    .album-cover-placeholder {
        width: 42px;
        height: 42px;
        border-radius: 8px;
        background: rgba(255, 0, 137, .08);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .type-badge {
        font-size: .6rem;
        border-radius: 4px;
        padding: 1px 5px;
    }

    /* ══ Aviso ══ */
    .data-notice {
        background: rgba(255, 0, 137, .04);
        border: 1px solid rgba(255, 0, 137, .14);
        border-radius: 14px;
        padding: 14px 18px;
        margin-bottom: 22px;
        display: flex;
        gap: 10px;
        align-items: flex-start;
        font-size: .8rem;
        color: var(--text-muted, #6c757d);
    }

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
    <?php require_once __DIR__ . '/../include/head.php'; ?>
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
        <!-- ── Hero do país ── -->
        <div class="country-hero">
            <div class="hero-body">
                <?php if ($flag_url): ?>
                <img class="country-flag-lg" src="<?php echo $flag_url; ?>"
                    alt="<?php echo htmlspecialchars($country_name); ?>" />
                <?php else: ?>
                <div class="country-flag-placeholder"><i class="bi bi-globe"></i></div>
                <?php endif; ?>
                <div class="country-hero-info">
                    <h2>
                        <?php if ($flag_20): ?>
                        <img src="<?php echo $flag_20; ?>" alt=""
                            style="vertical-align:middle;margin-right:8px;border-radius:3px;height:16px" />
                        <?php endif; ?>
                        <?php echo htmlspecialchars($country_name); ?>
                    </h2>
                    <div class="meta">
                        <span><i class="bi bi-disc me-1"></i><?php echo $total_albums; ?>
                            álbum<?php echo $total_albums != 1 ? 'ns' : ''; ?> distribuídos</span>
                        <span><i class="bi bi-music-note me-1"></i><?php echo $total_tracks_all; ?>
                            faixa<?php echo $total_tracks_all != 1 ? 's' : ''; ?></span>
                    </div>
                </div>
                <div class="ms-auto d-flex gap-2 flex-wrap align-items-start">
                    <a href="statistics#country" class="btn btn-sm"
                        style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:10px">
                        <i class="bi bi-arrow-left me-1"></i>Voltar
                    </a>
                </div>
            </div>
        </div>

        <!-- ── Filtro de ano ── -->
        <form method="GET" action="country-details">
            <input type="hidden" name="country" value="<?php echo htmlspecialchars($country_name); ?>" />
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
                <div class="ms-auto d-flex align-items-end" style="font-size:.78rem;color:var(--text-muted,#6c757d)">
                    <i class="bi bi-info-circle me-1"></i>Dados de <?php echo $filter_year; ?>
                </div>
            </div>
        </form>

        <!-- ── Aviso dados geográficos ── -->
        <div class="data-notice">
            <i class="bi bi-info-circle-fill mt-1" style="color:#FF0089;flex-shrink:0"></i>
            <div>
                <strong>Dados de streams por país em breve.</strong> Os streams segmentados por território serão
                disponibilizados assim que as plataformas enviarem relatórios geográficos detalhados. Os valores abaixo
                representam os streams totais das tuas faixas distribuídas neste território.
            </div>
        </div>

        <!-- ── Cards de totais ── -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-hero-card">
                    <div class="stat-label">Álbuns distribuídos</div>
                    <div class="stat-value" style="color:#FF0089"><?php echo $total_albums; ?></div>
                    <i class="bi bi-disc stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-hero-card">
                    <div class="stat-label">Faixas activas</div>
                    <div class="stat-value" style="color:#0d6efd"><?php echo $total_tracks_all; ?></div>
                    <i class="bi bi-music-note stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-hero-card">
                    <div class="stat-label">Streams totais</div>
                    <div class="stat-value" style="color:#6f42c1"><?php echo number_format((int)$total_streams_all); ?>
                    </div>
                    <i class="bi bi-headphones stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-hero-card">
                    <div class="stat-label">Receita (USD)</div>
                    <div class="stat-value" style="color:#198754;font-size:1.3rem">
                        $<?php echo number_format((float)$total_revenue_all, 2); ?></div>
                    <i class="bi bi-currency-dollar stat-icon"></i>
                </div>
            </div>
        </div>

        <!-- ── Mapa Leaflet centrado no país ── -->
        <?php if ($meta['lat'] != 0 || $meta['lng'] != 0): ?>
        <div class="card mb-4" style="border-radius:16px;overflow:hidden">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-globe2 me-2 text-pink"></i>Localização</h6>
            </div>
            <div id="country-map"></div>
        </div>
        <?php endif; ?>

        <!-- ── Tabela de álbuns distribuídos ── -->
        <div class="table-card mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-disc me-2 text-pink"></i>Álbuns distribuídos em
                        <em><?php echo htmlspecialchars($country_name); ?></em>
                    </h6>
                    <span class="badge bg-secondary"><?php echo $total_albums; ?></span>
                </div>
                <?php if (empty($albums)): ?>
                <div class="empty-section">
                    <div class="icon"><i class="bi bi-disc"></i></div>
                    <div class="small fw-semibold mb-1">Nenhum álbum distribuído neste território.</div>
                    <div class="small">
                        Para distribuir para <?php echo htmlspecialchars($country_name); ?>, edita o campo
                        <strong>Território</strong> no teu álbum.
                    </div>
                    <a href="../launch/releases" class="btn btn-sm btn-pink mt-3">Ver lançamentos</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table id="albumsTable" class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:52px">Capa</th>
                                <th>Álbum</th>
                                <th>Artista</th>
                                <th>Tipo</th>
                                <th>Faixas</th>
                                <th>Streams <?php echo $filter_year; ?></th>
                                <th>Receita (USD)</th>
                                <th>Territórios</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($albums as $al):
                                    $type_colors = [
                                        'single'  => 'bg-primary',
                                        'EP'      => 'bg-warning text-dark',
                                        'album'   => 'bg-success',
                                        'mixtape' => 'bg-secondary',
                                    ];
                                    $tc = $type_colors[$al['type_album']] ?? 'bg-secondary';
                                ?>
                            <tr>
                                <td>
                                    <?php if ($al['img_cover']): ?>
                                    <img class="album-cover"
                                        src="<?php echo htmlspecialchars($cover_url . $al['img_cover']); ?>"
                                        onerror="this.outerHTML='<div class=\'album-cover-placeholder\'>🎵</div>'"
                                        alt="" />
                                    <?php else: ?><div class="album-cover-placeholder">🎵</div><?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold" style="font-size:.87rem">
                                        <?php echo htmlspecialchars($al['title_album']); ?></div>
                                    <?php if ($al['release_date']): ?>
                                    <div style="font-size:.7rem;color:var(--text-muted,#6c757d)">
                                        <?php echo date('d/m/Y', strtotime($al['release_date'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?php echo htmlspecialchars($al['stage_name'] ?? '—'); ?></td>
                                <td><span
                                        class="badge type-badge <?php echo $tc; ?>"><?php echo strtoupper($al['type_album']); ?></span>
                                </td>
                                <td class="small text-center"><?php echo (int)$al['num_tracks']; ?></td>
                                <td class="fw-bold" style="color:#FF0089">
                                    <?php echo number_format((int)$al['total_streams']); ?></td>
                                <td class="small fw-semibold" style="color:#198754">
                                    $<?php echo number_format((float)$al['total_revenue'], 4); ?></td>
                                <td>
                                    <span class="badge bg-light text-muted"
                                        style="font-size:.65rem;max-width:120px;white-space:normal;text-align:left">
                                        <?php echo htmlspecialchars(mb_substr($al['territory'], 0, 50)); ?>
                                    </span>
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



    <!-- ═══ JS ═══ -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <script>
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

    <?php if (!empty($albums)): ?>
    $(document).ready(function() {
        $('#albumsTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            lengthChange: false,
            pageLength: 10,
            order: [
                [5, 'desc']
            ], // streams DESC
            columnDefs: [{
                orderable: false,
                targets: [0, 7]
            }],
            language: {
                search: 'Pesquisar álbum:',
                info: 'A mostrar _START_ a _END_ de _TOTAL_ álbuns',
                paginate: {
                    next: 'Próximo',
                    previous: 'Anterior'
                },
                emptyTable: 'Nenhum álbum encontrado.'
            }
        });
    });
    <?php endif; ?>

    <?php if ($meta['lat'] != 0 || $meta['lng'] != 0): ?>
    // ── Mapa Leaflet ──────────────────────────────
    const map = L.map('country-map', {
            zoomControl: true,
            scrollWheelZoom: false
        })
        .setView([<?php echo $meta['lat']; ?>, <?php echo $meta['lng']; ?>], 4);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    L.circleMarker([<?php echo $meta['lat']; ?>, <?php echo $meta['lng']; ?>], {
            color: '#FF0089',
            fillColor: '#FF0089',
            fillOpacity: 0.5,
            radius: 14
        }).addTo(map)
        .bindPopup(
            '<b><?php echo htmlspecialchars($country_name, ENT_QUOTES); ?></b><br><?php echo $total_albums; ?> álbum<?php echo $total_albums != 1 ? 'ns' : ''; ?> distribuídos'
        )
        .openPopup();
    <?php endif; ?>
    </script>
</body>

</html>

<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <link rel="apple-touch-icon" href="../../assets/img/icones/wasomupfy_fiv_512.png" />
    <link rel="apple-touch-startup-image" href="../../assets/img/screenshots/splash.png" />
    <link rel="manifest" href="../manifest.json" />
    <title>Detalhes dos países — <?php echo APP_NAME; ?></title>
    <link rel="shortcut icon" href="../../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link href="
https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css
" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/dashboard-style.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="<?php echo APP_URL  ?>/css/country.details.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>

<body>

    <!-- Main Content -->
    <div class="container my-4">
        <div class="country-header">
            <div class="country-info">
                <img id="countryFlag" src="" alt="Country Flag" />
                <h2 id="countryName">Carregando...</h2>
            </div>
            <div class="col-auto ms-auto text-end mt-n1">
                <button class="btn btn-back shadow-sm" onclick="window.location.reload()">
                    <i class="bi bi-repeat"></i> Actualizar
                </button>
                <button class="btn btn-pink" onclick="window.location='statistics#country'">
                    <i class="bi bi-arrow-left"></i> Voltar
                </button>
            </div>
        </div>
        <!-- Description -->
        <p class="stats-description">
            Aqui podes ver as estatísticas das músicas reproduzidas dentro deste
            região de acordo com a disponibilidade de registo.
        </p>

        <div class="date-range">
            <div>
                <label>Intervalo de datas</label>
                <div>
                    <input type="date" id="startDate" value="2024-10-09" />
                    <input type="date" id="endDate" value="2024-12-30" />
                </div>
            </div>
            <button class="btn-apply" onclick="applyDateRange()">Aplicar</button>
            <div class="total-streams">
                Total de streams: <span id="totalStreams">0</span>
            </div>
        </div>

        <div class="filter-group">
            <div class="input-group">
                <i class="bi bi-search"></i>
                <input type="text" id="songSearch" placeholder="Pesquisar música" onkeyup="filterSongs()" />
            </div>
            <div class="input-group">
                <i class="bi bi-filter"></i>
                <input type="number" id="minStreams" placeholder="Streams mínimos" oninput="validateStreamsFilter()" />
            </div>
            <div class="input-group">
                <i class="bi bi-filter"></i>
                <input type="number" id="maxStreams" placeholder="Streams máximos" oninput="validateStreamsFilter()" />
            </div>
            <div class="input-group">
                <i class="bi bi-award"></i>
                <select id="awardsFilter" onchange="filterSongs()">
                    <option value="all">Todos</option>
                    <option value="with">Com prêmios</option>
                    <option value="without">Sem prêmios</option>
                </select>
            </div>
            <div class="input-group">
                <i class="bi bi-sort-down"></i>
                <select id="sortOrder" onchange="filterSongs()">
                    <option value="desc">Streams (Decrescente)</option>
                    <option value="asc">Streams (Crescente)</option>
                </select>
            </div>
            <button class="btn-apply" id="applyFilters" onclick="filterSongs()" disabled>
                Aplicar
            </button>
        </div>

        <ul class="songs-list" id="songsList"></ul>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
    // Dados simulados para cada país
    const countryData = {
        Brasil: {
            flag: "https://flagcdn.com/w20/br.png",
            totalStreams: 15000,
            songs: [{
                    title: "Samba de Verão",
                    awards: "Prêmio 27-02-2025",
                    streams: 9000,
                    cover: "https://via.placeholder.com/40",
                },
                {
                    title: "Bossa Nova",
                    awards: "",
                    streams: 4000,
                    cover: "https://via.placeholder.com/40",
                },
                {
                    title: "Tropicalia",
                    awards: "Prêmio 30-01-2025",
                    streams: 2000,
                    cover: "https://via.placeholder.com/40",
                },
            ],
        },
        Angola: {
            flag: "https://flagcdn.com/w20/ao.png",
            totalStreams: 8000,
            songs: [{
                    title: "Hosana",
                    awards: "Prêmio 27-02-2025",
                    streams: 10000,
                    cover: "https://via.placeholder.com/40",
                },
                {
                    title: "God Knows",
                    awards: "Prêmio 30-01-2025",
                    streams: 5000,
                    cover: "https://via.placeholder.com/40",
                },
                {
                    title: "CW Jays",
                    awards: "",
                    streams: 5000,
                    cover: "https://via.placeholder.com/40",
                },
            ],
        },
        Portugal: {
            flag: "https://flagcdn.com/w20/pt.png",
            totalStreams: 2500,
            songs: [{
                    title: "Fado",
                    awards: "Prêmio 15-03-2025",
                    streams: 1500,
                    cover: "https://via.placeholder.com/40",
                },
                {
                    title: "Lisboa",
                    awards: "",
                    streams: 800,
                    cover: "https://via.placeholder.com/40",
                },
                {
                    title: "Porto",
                    awards: "",
                    streams: 200,
                    cover: "https://via.placeholder.com/40",
                },
            ],
        },
        USA: {
            flag: "https://flagcdn.com/w20/us.png",
            totalStreams: 500,
            songs: [{
                    title: "Country Road",
                    awards: "",
                    streams: 300,
                    cover: "https://via.placeholder.com/40",
                },
                {
                    title: "Blues Night",
                    awards: "Prêmio 20-04-2025",
                    streams: 150,
                    cover: "https://via.placeholder.com/40",
                },
                {
                    title: "Jazz Vibes",
                    awards: "",
                    streams: 50,
                    cover: "https://via.placeholder.com/40",
                },
            ],
        },
    };

    // Carregar dados do país com base no parâmetro da URL
    const urlParams = new URLSearchParams(window.location.search);
    const country = decodeURIComponent(urlParams.get("country") || "Angola"); // Fallback para Angola
    console.log("Parâmetro country:", country);

    const currentData = countryData[country] || countryData["Angola"];
    console.log("Dados carregados:", currentData);

    const countryFlag = document.getElementById("countryFlag");
    const countryName = document.getElementById("countryName");
    const totalStreams = document.getElementById("totalStreams");
    const songsList = document.getElementById("songsList");

    countryFlag.src = currentData.flag;
    countryName.textContent = country;
    totalStreams.textContent = currentData.totalStreams.toLocaleString();

    // Função para carregar músicas
    function loadSongs(songs) {
        songsList.innerHTML = "";
        songs.forEach((song) => {
            const li = document.createElement("li");
            li.innerHTML = `
                    <img src="${song.cover}" alt="${song.title}">
                    <div class="song-info">
                        <div class="song-title">${song.title}</div>
                        <div class="awards">${song.awards || "-"}</div>
                    </div>
                    <div class="song-streams">${song.streams.toLocaleString()} Streams</div>
                `;
            songsList.appendChild(li);
        });
    }

    // Carregar músicas iniciais
    loadSongs(currentData.songs);

    // Aplicar intervalo de datas (placeholder para lógica futura)
    function applyDateRange() {
        const startDate = document.getElementById("startDate").value;
        const endDate = document.getElementById("endDate").value;
        console.log(`Intervalo aplicado: ${startDate} a ${endDate}`);
        filterSongs();
    }

    // Validar filtros de streams
    function validateStreamsFilter() {
        const minStreams = document.getElementById("minStreams").value;
        const maxStreams = document.getElementById("maxStreams").value;
        const applyButton = document.getElementById("applyFilters");
        const isMinValid =
            minStreams === "" || (!isNaN(minStreams) && minStreams >= 0);
        const isMaxValid =
            maxStreams === "" || (!isNaN(maxStreams) && maxStreams >= 0);
        const areBothFilled = minStreams !== "" && maxStreams !== "";
        const isRangeValid = areBothFilled ?
            parseInt(minStreams) <= parseInt(maxStreams) :
            true;
        applyButton.disabled = !(isMinValid && isMaxValid && isRangeValid);
    }

    // Filtrar músicas
    function filterSongs() {
        const searchTerm = document
            .getElementById("songSearch")
            .value.toLowerCase();
        const minStreams =
            parseInt(document.getElementById("minStreams").value) || 0;
        const maxStreams =
            parseInt(document.getElementById("maxStreams").value) ||
            Number.MAX_SAFE_INTEGER;
        const awardsFilter = document.getElementById("awardsFilter").value;
        const sortOrder = document.getElementById("sortOrder").value;

        let filteredSongs = currentData.songs.filter((song) => {
            const matchesSearch = song.title.toLowerCase().includes(searchTerm);
            const matchesStreams =
                song.streams >= minStreams && song.streams <= maxStreams;
            const matchesAwards =
                awardsFilter === "all" ?
                true :
                awardsFilter === "with" ?
                song.awards !== "" :
                song.awards === "";

            return matchesSearch && matchesStreams && matchesAwards;
        });

        filteredSongs.sort((a, b) => {
            return sortOrder === "asc" ?
                a.streams - b.streams :
                b.streams - a.streams;
        });

        const totalFilteredStreams = filteredSongs.reduce(
            (sum, song) => sum + song.streams,
            0
        );
        totalStreams.textContent = totalFilteredStreams.toLocaleString();

        loadSongs(filteredSongs);
    }
    </script>
</body>

</html>