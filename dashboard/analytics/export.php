<?php
// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Exportar Dados de Estatísticas
// Arquivo: dashboard/analytics/export.php
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
$count_streams_q = $db->prepare(
    "
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

$months_pt = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title>Exportar Dados — <?php echo APP_NAME; ?></title>
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
                    ['icon' => 'bi-bar-chart-line', 'label' => 'Relatório de Streams', 'desc' => 'Resumo mensal com gráficos por plataforma'],
                    ['icon' => 'bi-file-earmark-text', 'label' => 'Relatório Financeiro', 'desc' => 'Royalties, deduções e saldo no período'],
                    ['icon' => 'bi-person-badge', 'label' => 'Relatório por Artista', 'desc' => 'Performance individual por artista'],
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



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL  ?>/js/theme.wp.js"></script>
    <script src="<?php echo APP_URL  ?>/js/wp.tools.js"></script>
    <script>
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    </script>
</body>

</html>