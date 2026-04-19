<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Ver Artista (com estatísticas)
// Arquivo: wu-panel/pages/artist/view.php
// Rota:    wu-panel/artist/view?id=X
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'users.view');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/artist');

$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'blocked'   => ['warning', 'bi-lock',        'Artista bloqueado com sucesso.'],
    'unblocked' => ['success', 'bi-unlock',      'Artista desbloqueado com sucesso.'],
    'updated'   => ['success', 'bi-check-circle', 'Dados actualizados com sucesso.'],
    'error'     => ['danger',  'bi-x-circle',    'Ocorreu um erro. Tenta novamente.'],
    default     => null,
};

// ── Buscar artista ──
$stmt = $db->prepare("
    SELECT
        a.*,
        u.id_users          AS owner_id,
        u.first_name        AS owner_first,
        u.second_name       AS owner_second,
        u.email_user        AS owner_email,
        u.photo_user        AS owner_photo,
        u.status_user       AS owner_status,
        p.name_plan         AS owner_plan
    FROM _artist a
    LEFT JOIN _users u ON u.id_users = a.id_users
    LEFT JOIN _plans p ON p.id_plan = u.plan_selected
    WHERE a.id_artist = ?
");
$stmt->execute([$id]);
$artist = $stmt->fetch();
if (!$artist) adminRedirect('/' . ADMIN_PATH . '/artist?msg=not_found');

// ── Estatísticas de streams e royalties ──
$stats_artist = $db->prepare("
    SELECT
        COALESCE(SUM(s.streams), 0) AS total_streams,
        COALESCE(SUM(r.gross_revenue), 0) AS total_gross_revenue,
        COALESCE(SUM(r.net_royalty_aoa), 0) AS total_net_aoa
    FROM _track t
    INNER JOIN _album a ON a.id_album = t.id_album
    LEFT JOIN _stream s ON s.id_track = t.id_track
    LEFT JOIN _royalty r ON r.id_track = t.id_track
    WHERE a.id_artist = ?
");
$stats_artist->execute([$id]);
$stats = $stats_artist->fetch();

// Último pagamento de royalties para este artista
$last_payment = $db->prepare("
    SELECT r.paid_at, r.net_royalty_aoa, r.currency
    FROM _royalty r
    INNER JOIN _track t ON t.id_track = r.id_track
    INNER JOIN _album a ON a.id_album = t.id_album
    WHERE a.id_artist = ? AND r.status_royalty = 'paid'
    ORDER BY r.paid_at DESC
    LIMIT 1
");
$last_payment->execute([$id]);
$last_paid = $last_payment->fetch();

// ── Últimas 5 faixas com streams totais ──
$tracks = $db->prepare("
    SELECT
        t.id_track,
        t.title_track,
        COALESCE(SUM(s.streams), 0) AS streams,
        t.creat_track
    FROM _track t
    INNER JOIN _album a ON a.id_album = t.id_album
    LEFT JOIN _stream s ON s.id_track = t.id_track
    WHERE a.id_artist = ?
    GROUP BY t.id_track
    ORDER BY t.creat_track DESC
    LIMIT 5
");
$tracks->execute([$id]);
$track_list = $tracks->fetchAll();

// ── Actividade recente (já existente) ──
$activity = $db->prepare("
    SELECT activity_type, description, ip_address, creat_activity
    FROM _user_activity_log
    WHERE entity = 'artist' AND entity_id = ?
    ORDER BY creat_activity DESC
    LIMIT 20
");
$activity->execute([$id]);
$activity_list = $activity->fetchAll();

// ── Álbuns do artista ──
$albums = $db->prepare("
    SELECT id_album, title_album, status_album, creat_album
    FROM _album
    WHERE id_artist = ?
    ORDER BY creat_album DESC
    LIMIT 5
");
$albums->execute([$id]);
$album_list = $albums->fetchAll();

// Helpers
function av_fmt_date($date, bool $relative = true): string
{
    if (!$date) return '—';
    $ts = strtotime($date);
    if (!$ts) return '—';
    if (!$relative) return date('d/m/Y H:i', $ts);
    $diff = time() - $ts;
    if ($diff < 60)     return 'agora';
    if ($diff < 3600)   return floor($diff / 60)    . ' min atrás';
    if ($diff < 86400)  return floor($diff / 3600)  . 'h atrás';
    if ($diff < 604800) return floor($diff / 86400) . 'd atrás';
    return date('d/m/Y', $ts);
}

function av_initials(string $name): string
{
    $parts = explode(' ', trim($name));
    $init = '';
    foreach ($parts as $part) {
        if (mb_strlen($init) < 2 && !empty($part)) {
            $init .= mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8');
        }
    }
    return $init ?: 'A';
}

function av_avatar_color(string $name): string
{
    $colors = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308', '#ec4899', '#14b8a6', '#3b82f6', '#ef4444'];
    return $colors[abs(crc32($name)) % count($colors)];
}

function av_status_badge(string $s): string
{
    return match ($s) {
        'active'    => '<span class="badge av-s-active">Activo</span>',
        'inactive'  => '<span class="badge av-s-inactive">Inactivo</span>',
        'blocked'   => '<span class="badge av-s-blocked">Bloqueado</span>',
        'processing' => '<span class="badge av-s-processing">Processando</span>',
        default     => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}

function av_activity_icon(string $type): array
{
    return match (true) {
        str_contains($type, 'create')   => ['bi-plus-circle', '#22c55e'],
        str_contains($type, 'update')   => ['bi-pencil', '#eab308'],
        str_contains($type, 'delete')   => ['bi-trash', '#ef4444'],
        default                         => ['bi-activity', '#8b5cf6'],
    };
}

$fullname = $artist['stage_name'];
$ini = av_initials($fullname);
$color = av_avatar_color($fullname);
$owner_name = trim(($artist['owner_first'] ?? '') . ' ' . ($artist['owner_second'] ?? ''));
$owner_ini = av_initials($owner_name);
$owner_color = av_avatar_color($owner_name);
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title><?php echo htmlspecialchars($fullname); ?> — Artista · Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
        /* (todos os estilos anteriores permanecem iguais) */
        .av-s-active {
            background: rgba(34, 197, 94, .15);
            color: #166534;
        }

        .av-s-inactive {
            background: rgba(107, 114, 128, .15);
            color: #374151;
        }

        .av-s-blocked {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
        }

        .av-s-processing {
            background: rgba(234, 179, 8, .15);
            color: #92400e;
        }

        .dark-mode .av-s-active {
            background: rgba(34, 197, 94, .2);
            color: #4ade80;
        }

        .dark-mode .av-s-inactive {
            background: rgba(107, 114, 128, .2);
            color: #9ca3af;
        }

        .dark-mode .av-s-blocked {
            background: rgba(239, 68, 68, .2);
            color: #f87171;
        }

        .dark-mode .av-s-processing {
            background: rgba(234, 179, 8, .2);
            color: #facc15;
        }

        .av-hero {
            background: linear-gradient(135deg, #0f0f1a 0%, #1a0a12 60%, #0f0f1a 100%);
            border-radius: 16px;
            padding: 32px;
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .av-hero::before {
            content: '';
            position: absolute;
            top: -60px;
            left: -60px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 0, 137, .18) 0%, transparent 70%);
            pointer-events: none;
        }

        .av-hero::after {
            content: '';
            position: absolute;
            bottom: -40px;
            right: -40px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(108, 99, 255, .14) 0%, transparent 70%);
            pointer-events: none;
        }

        .av-avatar-wrap {
            position: relative;
            display: inline-block;
        }

        .av-avatar-lg {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 0, 137, .4);
        }

        .av-avatar-ini-lg {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.6rem;
            color: #fff;
            border: 3px solid rgba(255, 255, 255, .15);
            flex-shrink: 0;
        }

        .av-status-dot {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid #0f0f1a;
        }

        .av-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 14px;
            padding: 20px 22px;
            margin-bottom: 20px;
        }

        .av-card-title {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            opacity: .5;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .av-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 9px 0;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
            font-size: .83rem;
            gap: 12px;
        }

        .av-detail-label {
            opacity: .5;
            flex-shrink: 0;
            min-width: 110px;
        }

        .av-detail-value {
            font-weight: 500;
            text-align: right;
            word-break: break-word;
        }

        .av-activity-item {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
            align-items: flex-start;
        }

        .av-activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
            flex-shrink: 0;
        }

        .av-activity-type {
            font-size: .8rem;
            font-weight: 600;
        }

        .av-activity-meta {
            font-size: .73rem;
            opacity: .5;
        }

        .av-owner-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px;
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 12px;
            text-decoration: none;
            color: inherit;
            transition: all .2s;
        }

        .av-owner-card:hover {
            border-color: #FF0089;
            background: rgba(255, 0, 137, .04);
            color: inherit;
        }

        .av-owner-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 0, 137, .2);
            flex-shrink: 0;
        }

        .av-owner-ini {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .75rem;
            color: #fff;
            flex-shrink: 0;
        }

        .av-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 10px;
            font-size: .82rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all .2s;
            cursor: pointer;
        }

        .av-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .av-stat-card {
            background: rgba(255, 0, 137, .04);
            border-radius: 12px;
            padding: 12px;
            text-align: center;
        }

        .av-stat-number {
            font-size: 1.2rem;
            font-weight: 800;
            color: #FF0089;
            line-height: 1.2;
        }

        .av-stat-label {
            font-size: .7rem;
            opacity: .6;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php require_once __DIR__ . '/../../include/sidebar.php'; ?>
        <div class="content w-100" id="mainContent">
            <?php require_once __DIR__ . '/../../include/navbar.php'; ?>
            <div class="container-fluid p-0">
                <!-- Breadcrumb (igual) -->
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1"><i class="bi bi-mic-fill me-2"></i>Artista</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist"
                                        class="text-secondary">Artistas</a></li>
                                <li class="breadcrumb-item active text-white-stable">
                                    <?php echo htmlspecialchars($fullname); ?></li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        <?php if (hasPermission($admin_id, 'users.edit')): ?>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/edit?id=<?php echo $id; ?>"
                                class="av-action-btn text-white" style="background:#FF0089;border-color:#FF0089"><i
                                    class="bi bi-pencil"></i> Editar</a>
                        <?php endif; ?>
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist" class="av-action-btn"
                            style="border-color:var(--border-color,#e8e8f0)"><i class="bi bi-arrow-left"></i> Voltar</a>
                    </div>
                </div>

                <?php if ($feedback): ?>
                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3"><i
                            class="bi <?php echo $feedback[1]; ?> me-2"></i><?php echo htmlspecialchars($feedback[2]); ?><button
                            type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Coluna Principal (8/12) -->
                    <div class="col-lg-8">
                        <!-- Hero (igual) -->
                        <div class="av-hero">
                            <div class="d-flex align-items-center gap-4 position-relative" style="z-index:1">
                                <div class="av-avatar-wrap flex-shrink-0">
                                    <?php if (!empty($artist['photo_artist'])): ?>
                                        <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/artists/<?php echo htmlspecialchars($artist['photo_artist']); ?>"
                                            class="av-avatar-lg" alt=""
                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                                        <div class="av-avatar-ini-lg" style="background:<?php echo $color; ?>;display:none">
                                            <?php echo $ini; ?></div>
                                    <?php else: ?>
                                        <div class="av-avatar-ini-lg" style="background:<?php echo $color; ?>">
                                            <?php echo $ini; ?></div>
                                    <?php endif; ?>
                                    <?php $dot_color = match ($artist['status_artist']) {
                                        'active' => '#22c55e',
                                        'inactive' => '#6b7280',
                                        'blocked' => '#ef4444',
                                        default => '#eab308'
                                    }; ?>
                                    <div class="av-status-dot" style="background:<?php echo $dot_color; ?>"></div>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <h3 class="text-white mb-0" style="font-size:1.35rem;font-weight:800">
                                            <?php echo htmlspecialchars($fullname); ?></h3>
                                        <?php echo av_status_badge($artist['status_artist']); ?>
                                    </div>
                                    <?php if (!empty($artist['real_name'])): ?>
                                        <div style="color:rgba(255,255,255,.6);font-size:.9rem;margin-bottom:8px">
                                            <?php echo htmlspecialchars($artist['real_name']); ?></div>
                                    <?php endif; ?>
                                    <div class="d-flex flex-wrap gap-3"
                                        style="font-size:.8rem;color:rgba(255,255,255,.6)">
                                        <?php if (!empty($artist['country'])): ?><span><i
                                                    class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($artist['country']); ?><?php if (!empty($artist['city'])) echo ', ' . htmlspecialchars($artist['city']); ?></span><?php endif; ?>
                                        <span><i class="bi bi-calendar3 me-1"></i>Desde
                                            <?php echo date('d/m/Y', strtotime($artist['creat_artist'])); ?></span>
                                    </div>
                                </div>
                                <?php if (hasPermission($admin_id, 'users.edit')): ?>
                                    <div class="flex-shrink-0 d-none d-md-flex flex-column gap-2">
                                        <?php if ($artist['status_artist'] === 'active'): ?>
                                            <button onclick="toggleStatus(<?php echo $id; ?>,'blocked')"
                                                class="av-action-btn text-white"
                                                style="background:rgba(239,68,68,.2);border-color:rgba(239,68,68,.4);color:#f87171!important;justify-content:center"><i
                                                    class="bi bi-lock"></i> Bloquear</button>
                                        <?php elseif ($artist['status_artist'] === 'blocked'): ?>
                                            <button onclick="toggleStatus(<?php echo $id; ?>,'active')" class="av-action-btn"
                                                style="background:rgba(34,197,94,.2);border-color:rgba(34,197,94,.4);color:#4ade80;justify-content:center"><i
                                                    class="bi bi-unlock"></i> Desbloquear</button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Informações Pessoais (igual) -->
                        <div class="av-card">
                            <div class="av-card-title"><i class="bi bi-person"></i> Informações Pessoais</div>
                            <div class="av-detail-row"><span class="av-detail-label">Nome artístico</span><span
                                    class="av-detail-value"><?php echo htmlspecialchars($artist['stage_name']); ?></span>
                            </div>
                            <?php if (!empty($artist['real_name'])): ?>
                                <div class="av-detail-row"><span class="av-detail-label">Nome real</span><span
                                        class="av-detail-value"><?php echo htmlspecialchars($artist['real_name']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($artist['genre_main']) || !empty($artist['genre_secondary'])): ?>
                                <div class="av-detail-row"><span class="av-detail-label">Género</span><span
                                        class="av-detail-value"><?php echo htmlspecialchars($artist['genre_main']); ?><?php if (!empty($artist['genre_secondary'])) echo ' / ' . htmlspecialchars($artist['genre_secondary']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($artist['country'])): ?>
                                <div class="av-detail-row"><span class="av-detail-label">Localização</span><span
                                        class="av-detail-value"><?php echo htmlspecialchars($artist['country']); ?><?php if (!empty($artist['city'])) echo ', ' . htmlspecialchars($artist['city']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($artist['bio'])): ?>
                                <div class="av-detail-row"><span class="av-detail-label">Biografia</span><span
                                        class="av-detail-value"
                                        style="text-align:left"><?php echo nl2br(htmlspecialchars($artist['bio'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="av-detail-row"><span class="av-detail-label">ID</span><span
                                    class="av-detail-value"><code>#<?php echo str_pad($id, 6, '0', STR_PAD_LEFT); ?></code></span>
                            </div>
                            <div class="av-detail-row"><span class="av-detail-label">Criado em</span><span
                                    class="av-detail-value"><?php echo date('d/m/Y \à\s H:i', strtotime($artist['creat_artist'])); ?></span>
                            </div>
                            <?php if ($artist['modif_artist'] && $artist['modif_artist'] !== $artist['creat_artist']): ?>
                                <div class="av-detail-row"><span class="av-detail-label">Última alteração</span><span
                                        class="av-detail-value"><?php echo date('d/m/Y \à\s H:i', strtotime($artist['modif_artist'])); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Redes Sociais (igual) -->
                        <?php
                        $socials = [];
                        if (!empty($artist['facebook_url'])) $socials[] = ['Facebook', $artist['facebook_url'], 'bi-facebook'];
                        if (!empty($artist['instagram_url'])) $socials[] = ['Instagram', $artist['instagram_url'], 'bi-instagram'];
                        if (!empty($artist['youtube_url'])) $socials[] = ['YouTube', $artist['youtube_url'], 'bi-youtube'];
                        if (!empty($artist['spotify_url'])) $socials[] = ['Spotify', $artist['spotify_url'], 'bi-spotify'];
                        if (!empty($artist['apple_music_url'])) $socials[] = ['Apple Music', $artist['apple_music_url'], 'bi-apple'];
                        if (!empty($artist['tiktok_url'])) $socials[] = ['TikTok', $artist['tiktok_url'], 'bi-tiktok'];
                        if (!empty($artist['website_url'])) $socials[] = ['Website', $artist['website_url'], 'bi-browser-chrome'];
                        if (!empty($socials)): ?>
                            <div class="av-card">
                                <div class="av-card-title"><i class="bi bi-share"></i> Redes Sociais</div>
                                <?php foreach ($socials as [$name, $url, $icon]): ?>
                                    <div class="av-detail-row"><span class="av-detail-label"><?php echo $name; ?></span><span
                                            class="av-detail-value"><a href="<?php echo htmlspecialchars($url); ?>"
                                                target="_blank" rel="noopener" style="color:inherit"><i
                                                    class="bi <?php echo $icon; ?> me-1"></i>
                                                <?php echo htmlspecialchars($url); ?></a></span></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- NOVO: Estatísticas de Desempenho -->
                        <div class="av-card">
                            <div class="av-card-title"><i class="bi bi-bar-chart-line"></i> Estatísticas de Desempenho
                            </div>
                            <div class="av-stats-grid">
                                <div class="av-stat-card">
                                    <div class="av-stat-number"><?php echo number_format($stats['total_streams']); ?>
                                    </div>
                                    <div class="av-stat-label">Total de Streams</div>
                                </div>
                                <div class="av-stat-card">
                                    <div class="av-stat-number">
                                        <?php echo number_format($stats['total_gross_revenue'], 2); ?> USD</div>
                                    <div class="av-stat-label">Royalties Brutos</div>
                                </div>
                                <div class="av-stat-card">
                                    <div class="av-stat-number"><?php echo number_format($stats['total_net_aoa'], 2); ?>
                                        AOA</div>
                                    <div class="av-stat-label">Royalties Líquidos</div>
                                </div>
                                <?php if ($last_paid): ?>
                                    <div class="av-stat-card">
                                        <div class="av-stat-number">
                                            <?php echo number_format($last_paid['net_royalty_aoa'], 2); ?> AOA</div>
                                        <div class="av-stat-label">Último
                                            Pagamento<br><small><?php echo date('d/m/Y', strtotime($last_paid['paid_at'])); ?></small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="av-stat-card">
                                        <div class="av-stat-number">—</div>
                                        <div class="av-stat-label">Último Pagamento</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/artists?id=<?php echo $id; ?>"
                                    class="text-decoration-none small" style="color:#FF0089"><i
                                        class="bi bi-graph-up"></i> Ver detalhes de streams</a>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/finances/earnings?artist=<?php echo $id; ?>"
                                    class="text-decoration-none small" style="color:#FF0089"><i
                                        class="bi bi-currency-dollar"></i> Ver relatório de royalties</a>
                            </div>
                        </div>

                        <!-- Últimas Faixas -->
                        <?php if (!empty($track_list)): ?>
                            <div class="av-card">
                                <div class="av-card-title"><i class="bi bi-music-note-beamed"></i> Últimas Faixas</div>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($track_list as $track): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center"
                                            style="background:transparent;padding:10px 0">
                                            <div>
                                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/music/view?id=<?php echo $track['id_track']; ?>"
                                                    class="text-decoration-none"
                                                    style="color:inherit;font-weight:600"><?php echo htmlspecialchars($track['title_track']); ?></a>
                                                <div class="small text-muted"><?php echo number_format($track['streams']); ?>
                                                    streams · <?php echo date('d/m/Y', strtotime($track['creat_track'])); ?>
                                                </div>
                                            </div>
                                            <i class="bi bi-chevron-right text-muted"></i>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-end mt-2">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/music?artist=<?php echo $id; ?>"
                                        class="text-decoration-none small" style="color:#FF0089">Ver todas as faixas <i
                                            class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Últimos Álbuns -->
                        <?php if (!empty($album_list)): ?>
                            <div class="av-card">
                                <div class="av-card-title"><i class="bi bi-disc"></i> Últimos Álbuns</div>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($album_list as $album): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center"
                                            style="background:transparent;padding:10px 0">
                                            <div>
                                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/releases/view?id=<?php echo $album['id_album']; ?>"
                                                    class="text-decoration-none"
                                                    style="color:inherit;font-weight:600"><?php echo htmlspecialchars($album['title_album']); ?></a>
                                                <div class="small text-muted"><?php echo av_fmt_date($album['creat_album']); ?>
                                                </div>
                                            </div>
                                            <span class="badge bg-secondary"><?php echo $album['status_album']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-end mt-2">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/releases?artist=<?php echo $id; ?>"
                                        class="text-decoration-none small" style="color:#FF0089">Ver todos os álbuns <i
                                            class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Actividade Recente (igual) -->
                        <div class="av-card">
                            <div class="av-card-title"><i class="bi bi-activity"></i> Actividade Recente <span
                                    class="ms-auto badge bg-secondary"
                                    style="font-size:.65rem"><?php echo count($activity_list); ?></span></div>
                            <?php if (empty($activity_list)): ?>
                                <div class="text-center py-4" style="opacity:.35"><i class="bi bi-clock-history"
                                        style="font-size:2rem;display:block;margin-bottom:8px"></i><span
                                        style="font-size:.83rem">Nenhuma actividade registada</span></div>
                            <?php else: ?>
                                <?php foreach ($activity_list as $act):
                                    [$icon, $icon_color] = av_activity_icon($act['activity_type']);
                                    $desc = $act['description'] ?: str_replace('_', ' ', $act['activity_type']);
                                ?>
                                    <div class="av-activity-item">
                                        <div class="av-activity-icon" style="background:<?php echo $icon_color; ?>18"><i
                                                class="bi <?php echo $icon; ?>" style="color:<?php echo $icon_color; ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="av-activity-type"><?php echo htmlspecialchars($desc); ?></div>
                                            <div class="av-activity-meta">
                                                <?php if (!empty($act['ip_address'])) echo '<i class="bi bi-geo-alt"></i> ' . htmlspecialchars($act['ip_address']) . ' · '; ?><?php echo av_fmt_date($act['creat_activity']); ?>
                                            </div>
                                        </div>
                                        <span
                                            style="font-size:.72rem;opacity:.4;white-space:nowrap"><?php echo date('d/m H:i', strtotime($act['creat_activity'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Coluna Lateral (4/12) -->
                    <div class="col-lg-4">
                        <!-- Proprietário (igual) -->
                        <div class="av-card">
                            <div class="av-card-title"><i class="bi bi-person-circle"></i> Proprietário</div>
                            <?php if ($artist['owner_id']): ?>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $artist['owner_id']; ?>"
                                    class="av-owner-card">
                                    <?php if (!empty($artist['owner_photo'])): ?>
                                        <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($artist['owner_photo']); ?>"
                                            class="av-owner-avatar" alt=""
                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                                        <div class="av-owner-ini" style="background:<?php echo $owner_color; ?>;display:none">
                                            <?php echo $owner_ini; ?></div>
                                    <?php else: ?>
                                        <div class="av-owner-ini" style="background:<?php echo $owner_color; ?>">
                                            <?php echo $owner_ini; ?></div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1 min-w-0">
                                        <div style="font-weight:700;font-size:.88rem">
                                            <?php echo htmlspecialchars($owner_name ?: '—'); ?></div>
                                        <div
                                            style="font-size:.74rem;opacity:.5;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                            <?php echo htmlspecialchars($artist['owner_email'] ?? ''); ?></div>
                                        <?php if (!empty($artist['owner_plan'])): ?><span class="badge"
                                                style="background:#FF008915;color:#FF0089;font-size:.62rem;margin-top:3px"><?php echo htmlspecialchars($artist['owner_plan']); ?></span><?php endif; ?>
                                    </div>
                                    <i class="bi bi-arrow-right" style="opacity:.3;font-size:.9rem"></i>
                                </a>
                            <?php else: ?>
                                <div class="text-center py-3" style="opacity:.4;font-size:.83rem"><i
                                        class="bi bi-person-x fs-4 d-block mb-1"></i>Proprietário não encontrado</div>
                            <?php endif; ?>
                        </div>

                        <!-- Links Rápidos (novo) -->
                        <div class="av-card">
                            <div class="av-card-title"><i class="bi bi-link-45deg"></i> Links Rápidos</div>
                            <div class="d-grid gap-2">
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/analytics/artists?id=<?php echo $id; ?>"
                                    class="av-action-btn justify-content-center"
                                    style="border-color:var(--border-color,#e8e8f0)"><i class="bi bi-graph-up"></i>
                                    Estatísticas de Streaming</a>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/finances/earnings?artist=<?php echo $id; ?>"
                                    class="av-action-btn justify-content-center"
                                    style="border-color:var(--border-color,#e8e8f0)"><i
                                        class="bi bi-currency-dollar"></i> Relatório de Royalties</a>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/music?artist=<?php echo $id; ?>"
                                    class="av-action-btn justify-content-center"
                                    style="border-color:var(--border-color,#e8e8f0)"><i
                                        class="bi bi-music-note-list"></i> Todas as Faixas</a>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/releases?artist=<?php echo $id; ?>"
                                    class="av-action-btn justify-content-center"
                                    style="border-color:var(--border-color,#e8e8f0)"><i class="bi bi-disc"></i> Todos os
                                    Álbuns</a>
                            </div>
                        </div>

                        <!-- Acções (igual) -->
                        <?php if (hasPermission($admin_id, 'users.edit')): ?>
                            <div class="av-card">
                                <div class="av-card-title"><i class="bi bi-lightning"></i> Acções Rápidas</div>
                                <div class="d-grid gap-2">
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/edit?id=<?php echo $id; ?>"
                                        class="av-action-btn text-white justify-content-center"
                                        style="background:#FF0089;border-color:#FF0089"><i class="bi bi-pencil"></i> Editar
                                        dados</a>
                                    <?php if ($artist['status_artist'] === 'active'): ?>
                                        <button onclick="toggleStatus(<?php echo $id; ?>,'blocked')"
                                            class="av-action-btn justify-content-center"
                                            style="border-color:rgba(239,68,68,.4);color:#ef4444"><i class="bi bi-lock"></i>
                                            Bloquear acesso</button>
                                    <?php elseif ($artist['status_artist'] === 'blocked'): ?>
                                        <button onclick="toggleStatus(<?php echo $id; ?>,'active')"
                                            class="av-action-btn justify-content-center"
                                            style="border-color:rgba(34,197,94,.4);color:#22c55e"><i class="bi bi-unlock"></i>
                                            Desbloquear acesso</button>
                                    <?php endif; ?>
                                    <button onclick="deleteArtist(<?php echo $id; ?>)"
                                        class="av-action-btn justify-content-center"
                                        style="border-color:rgba(239,68,68,.3);color:#ef4444"><i class="bi bi-trash"></i>
                                        Excluir artista</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="col-12 text-center py-2">
                <p class="mb-0">© <?php echo date('Y'); ?> Wasom Upfy. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    <div class="page-loader" id="pageLoader">
        <div class="loader-content"><img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png"
                class="loader-image" alt="" />
            <div class="loader-progress"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
        (function() {
            const BASE_URL = '<?php echo APP_URL; ?>';
            const ADMIN_PATH = '<?php echo ADMIN_PATH; ?>';
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const PROCESS = BASE_URL + '/' + ADMIN_PATH + '/artist/process';

            async function postAction(payload) {
                const fd = new FormData();
                Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
                fd.append('csrf_token', CSRF);
                const r = await fetch(PROCESS, {
                    method: 'POST',
                    body: fd
                });
                return r.json();
            }

            window.toggleStatus = async function(id, newStatus) {
                const action = newStatus === 'blocked' ? 'bloquear' : 'desbloquear';
                const result = await Swal.fire({
                    title: action.charAt(0).toUpperCase() + action.slice(1) + ' artista?',
                    text: 'Tens a certeza que queres ' + action + ' este artista?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#FF0089',
                    confirmButtonText: 'Sim, ' + action,
                    cancelButtonText: 'Cancelar'
                });
                if (!result.isConfirmed) return;
                Swal.fire({
                    title: 'A processar...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                try {
                    const data = await postAction({
                        action: 'toggle_artist_status',
                        id_artist: id,
                        new_status: newStatus
                    });
                    if (data.ok) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Actualizado!',
                            text: data.message,
                            confirmButtonColor: '#FF0089'
                        });
                        location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: data.message,
                            confirmButtonColor: '#FF0089'
                        });
                    }
                } catch {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro de ligação',
                        text: 'Verifica a tua internet.',
                        confirmButtonColor: '#FF0089'
                    });
                }
            };

            window.deleteArtist = async function(id) {
                const {
                    value: password
                } = await Swal.fire({
                    title: 'Excluir artista',
                    html: '<p class="mb-1">Esta acção é <strong>irreversível</strong>.</p><p class="text-muted small mb-3">Confirma a tua senha de administrador para continuar.</p><input type="password" id="swal-pwd" class="swal2-input" placeholder="Senha do admin">',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Excluir',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => {
                        const pwd = document.getElementById('swal-pwd').value;
                        if (!pwd) {
                            Swal.showValidationMessage('A senha é obrigatória.');
                            return false;
                        }
                        return pwd;
                    }
                });
                if (!password) return;
                Swal.fire({
                    title: 'A processar...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                try {
                    const data = await postAction({
                        action: 'delete_artist',
                        id_artist: id,
                        password_confirm: password
                    });
                    if (data.ok) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Eliminado!',
                            text: data.message,
                            confirmButtonColor: '#FF0089'
                        });
                        window.location.href = BASE_URL + '/' + ADMIN_PATH + '/artist';
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: data.message,
                            confirmButtonColor: '#FF0089'
                        });
                    }
                } catch {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro de ligação',
                        text: 'Verifica a tua internet.',
                        confirmButtonColor: '#FF0089'
                    });
                }
            };
        })();
    </script>
</body>

</html>