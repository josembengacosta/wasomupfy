<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Visualizar Utilizador
// Arquivo: admin/pages/users/view-user.php
// Rota:    admin/users/view?id=X
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'users.view');

$id = (int)($_GET['id'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/users');

// ── Feedback ──
$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'updated'   => ['success', 'bi-check-circle', 'Utilizador actualizado.'],
    'blocked'   => ['warning', 'bi-lock',          'Utilizador suspenso.'],
    'unblocked' => ['success', 'bi-unlock',        'Utilizador activado.'],
    'error'     => ['danger',  'bi-x-circle',      'Ocorreu um erro.'],
    default     => null,
};

// ── Carregar utilizador ──
$stmt = $db->prepare("
    SELECT
        u.id_users, u.first_name, u.second_name, u.user_name,
        u.email_user, u.tel_user, u.photo_user,
        u.country_user, u.city_user, u.status_user,
        u.creat_user, u.modif_user,
        us.last_login_at, us.last_login_ip, us.login_attempts,
        us.block_until, us.is_fraud_blocked,
        pl.name_plan, pl.id_plan,
        up.started_at AS plan_started
    FROM _users u
    LEFT JOIN _users_security us ON us.id_users = u.id_users
    LEFT JOIN (
        SELECT id_users, id_plan, started_at
        FROM _user_plan
        WHERE (id_users, started_at) IN (
            SELECT id_users, MAX(started_at) FROM _user_plan GROUP BY id_users
        )
    ) up ON up.id_users = u.id_users
    LEFT JOIN _plans pl ON pl.id_plan = up.id_plan
    WHERE u.id_users = ?
    LIMIT 1
");
$stmt->execute([$id]);
$usr = $stmt->fetch();
if (!$usr) adminRedirect('/' . ADMIN_PATH . '/users');

// ── Artistas ligados ao utilizador ──
$artists = $db->prepare("
    SELECT a.id_artist, a.stage_name, a.photo_artist, a.status_artist
    FROM _artist a
    WHERE a.id_users = ?
    ORDER BY a.stage_name
    LIMIT 10
");
$artists->execute([$id]);
$artist_list = $artists->fetchAll();

// ── Lançamentos recentes ──
$albums = $db->prepare("
    SELECT al.id_album, al.title_album, al.img_cover,
           al.status_album, al.creat_album
    FROM _album al
    INNER JOIN _artist a ON a.id_artist = al.id_artist
    WHERE a.id_users = ?
    ORDER BY al.creat_album DESC
    LIMIT 6
");
$albums->execute([$id]);
$album_list = $albums->fetchAll();

// ── Pagamentos recentes ──
$payments = $db->prepare("
    SELECT p.id_payment, p.amount, p.status_payment,
           p.payment_method, p.creat_payment
    FROM _payment p
    WHERE p.id_users = ?
    ORDER BY p.creat_payment DESC
    LIMIT 5
");
$payments->execute([$id]);
$payment_list = $payments->fetchAll();

// ── Actividade recente ──
$audit = $db->prepare("
    SELECT action, entity, entity_id, creat_log, ip_address
    FROM _audit_log
    WHERE id_users = ?
    ORDER BY creat_log DESC
    LIMIT 12
");
$audit->execute([$id]);
$audit_list = $audit->fetchAll();

$fullname = trim($usr['first_name'] . ' ' . ($usr['second_name'] ?? ''));
$ini      = adm_initials($usr['first_name'], $usr['second_name'] ?? '');
$color    = adm_avatar_color($fullname);

function usr_view_status_badge(string $s): string
{
    return match ($s) {
        'active'    => '<span class="badge uv-s-active">Activo</span>',
        'suspended' => '<span class="badge uv-s-suspended">Suspenso</span>',
        'review'    => '<span class="badge uv-s-review">Em revisão</span>',
        'blocked'   => '<span class="badge uv-s-blocked">Bloqueado</span>',
        'inactive'  => '<span class="badge uv-s-inactive">Inactivo</span>',
        default     => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}
function usr_album_status(string $s): string
{
    return match ($s) {
        'aprroved'   => '<span class="badge bg-success">Publicado</span>',
        'pending'     => '<span class="badge bg-warning text-dark">Pendente</span>',
        'under_review' => '<span class="badge bg-info text-dark">Em revisão</span>',
        'rejected'    => '<span class="badge bg-danger">Rejeitado</span>',
        'deleting'    => '<span class="badge bg-secondary">A eliminar</span>',
        default       => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}
function usr_payment_status(string $s): string
{
    return match ($s) {
        'completed' => '<span class="badge bg-success">Completo</span>',
        'pending'   => '<span class="badge bg-warning text-dark">Pendente</span>',
        'failed'    => '<span class="badge bg-danger">Falhado</span>',
        'refunded'  => '<span class="badge bg-secondary">Reembolsado</span>',
        default     => '<span class="badge bg-secondary">' . ucfirst($s) . '</span>',
    };
}
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <title><?php echo htmlspecialchars($fullname); ?> — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <style>
        /* Status */
        .uv-s-active {
            background: rgba(34, 197, 94, .15);
            color: #166534;
        }

        .uv-s-suspended {
            background: rgba(239, 68, 68, .15);
            color: #991b1b;
        }

        .uv-s-review {
            background: rgba(234, 179, 8, .15);
            color: #92400e;
        }

        .uv-s-blocked {
            background: rgba(107, 114, 128, .15);
            color: #374151;
        }

        .uv-s-inactive {
            background: rgba(59, 130, 246, .15);
            color: #1e40af;
        }

        /* Hero */
        .usr-hero {
            background: linear-gradient(135deg, #0f0f17 0%, #1a1a2e 60%, #16213e 100%);
            border-radius: 16px;
            padding: 28px 32px;
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, .05);
        }

        .usr-hero::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 240px;
            height: 240px;
            background: radial-gradient(circle, rgba(255, 0, 137, .18) 0%, transparent 70%);
            pointer-events: none;
        }

        .usr-hero::after {
            content: '';
            position: absolute;
            bottom: -40px;
            left: 25%;
            width: 160px;
            height: 160px;
            background: radial-gradient(circle, rgba(108, 99, 255, .14) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Avatar */
        .usr-av-wrap {
            position: relative;
            flex-shrink: 0;
        }

        .usr-av-img {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 0, 137, .4);
            box-shadow: 0 0 0 6px rgba(255, 0, 137, .1), 0 8px 24px rgba(0, 0, 0, .3);
        }

        .usr-av-ini {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.65rem;
            color: #fff;
            border: 3px solid rgba(255, 0, 137, .4);
            box-shadow: 0 0 0 6px rgba(255, 0, 137, .1), 0 8px 24px rgba(0, 0, 0, .3);
        }

        .usr-status-dot {
            position: absolute;
            bottom: 3px;
            right: 3px;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            border: 2.5px solid #1a1a2e;
        }

        .usr-status-dot.active {
            background: #22c55e;
        }

        .usr-status-dot.suspended {
            background: #ef4444;
        }

        .usr-status-dot.review {
            background: #eab308;
        }

        .usr-status-dot.blocked {
            background: #6b7280;
        }

        .usr-status-dot.inactive {
            background: #3b82f6;
        }

        .usr-hero-name {
            font-size: 1.3rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 5px;
        }

        .usr-hero-meta {
            font-size: .81rem;
            color: rgba(255, 255, 255, .5);
            display: flex;
            flex-wrap: wrap;
            gap: 4px 0;
            margin-top: 6px;
        }

        .usr-hero-meta span {
            margin-right: 16px;
            white-space: nowrap;
        }

        .usr-hero-meta i {
            margin-right: 4px;
            color: rgba(255, 0, 137, .75);
        }

        /* Info card */
        .info-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e8e8f0);
            border-radius: 14px;
            padding: 20px 22px;
            margin-bottom: 16px;
        }

        .dark-mode .info-card {
            background: var(--dark-card, #1a1a27);
            border-color: var(--dark-border, #2e2e42);
        }

        .info-card-title {
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .55px;
            color: #FF0089;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color, #e8e8f0);
        }

        .dark-mode .info-card-title {
            border-color: var(--dark-border, #2e2e42);
        }

        /* Detail row */
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 9px 0;
            border-bottom: 1px solid var(--border-color, #f0f0f8);
            font-size: .84rem;
            gap: 14px;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: var(--text-muted, #888);
            flex-shrink: 0;
            min-width: 110px;
        }

        .detail-value {
            font-weight: 600;
            text-align: right;
            word-break: break-all;
        }

        .dark-mode .detail-row {
            border-color: var(--dark-border, #2e2e42);
        }

        .dark-mode .detail-value {
            color: var(--text-light, #e8e8f5);
        }

        /* Artist mini card */
        .artist-mini {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color, #f0f0f8);
            font-size: .82rem;
        }

        .artist-mini:last-child {
            border-bottom: none;
        }

        .artist-mini img,
        .artist-mini-ini {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .artist-mini-ini {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .65rem;
            color: #fff;
        }

        /* Security indicator */
        .sec-ind {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: .82rem;
            margin-bottom: 8px;
            border: 1px solid;
        }

        .sec-ind:last-child {
            margin-bottom: 0;
        }

        .sec-ind i {
            flex-shrink: 0;
        }

        .sec-ind.ok {
            background: rgba(34, 197, 94, .07);
            border-color: rgba(34, 197, 94, .2);
            color: #166534;
        }

        .sec-ind.warn {
            background: rgba(234, 179, 8, .07);
            border-color: rgba(234, 179, 8, .25);
            color: #92400e;
        }

        .sec-ind.danger {
            background: rgba(239, 68, 68, .07);
            border-color: rgba(239, 68, 68, .2);
            color: #991b1b;
        }

        .sec-ind.neutral {
            background: rgba(107, 114, 128, .07);
            border-color: rgba(107, 114, 128, .2);
            color: #374151;
        }

        /* Audit row */
        .audit-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color, #f0f0f8);
            font-size: .8rem;
        }

        .audit-row:last-child {
            border-bottom: none;
        }

        .audit-icon {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 0, 137, .1);
            color: #FF0089;
            font-size: .82rem;
        }

        /* Action btn */
        .action-btn {
            padding: 7px 14px;
            border-radius: 8px;
            font-size: .8rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            white-space: nowrap;
            transition: all .2s;
        }

        .action-btn:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
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

                <!-- Cabeçalho -->
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1"><i class="bi bi-person-lines-fill me-2"></i>Perfil do Utilizador</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                                        class="text-secondary">Utilizadores</a></li>
                                <li class="breadcrumb-item active text-white-stable">
                                    <?php echo htmlspecialchars($usr['first_name']); ?></li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        <?php if (hasPermission($admin_id, 'users.edit')): ?>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/edit?id=<?php echo $id; ?>"
                                class="btn btn-sm btn-warning text-dark">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Voltar
                        </a>
                    </div>
                </div>

                <!-- Feedback -->
                <?php if ($feedback): ?>
                    <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3">
                        <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                        <?php echo htmlspecialchars($feedback[2]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Hero -->
                <div class="usr-hero">
                    <div class="d-flex align-items-center gap-4 flex-wrap position-relative" style="z-index:1">
                        <div class="usr-av-wrap">
                            <?php if (!empty($usr['photo_user'])): ?>
                                <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($usr['photo_user']); ?>"
                                    class="usr-av-img" alt=""
                                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                                <div class="usr-av-ini" style="background:<?php echo $color; ?>;display:none">
                                    <?php echo $ini; ?></div>
                            <?php else: ?>
                                <div class="usr-av-ini" style="background:<?php echo $color; ?>"><?php echo $ini; ?></div>
                            <?php endif; ?>
                            <div class="usr-status-dot <?php echo $usr['status_user']; ?>"></div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="usr-hero-name"><?php echo htmlspecialchars($fullname); ?></div>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <?php echo usr_view_status_badge($usr['status_user']); ?>
                                <?php if ($usr['name_plan']): ?>
                                    <span class="badge" style="background:rgba(255,0,137,.2);color:#FF0089">
                                        <?php echo htmlspecialchars($usr['name_plan']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($usr['is_fraud_blocked']): ?>
                                    <span class="badge bg-danger"><i
                                            class="bi bi-shield-exclamation me-1"></i>Anti-fraude</span>
                                <?php endif; ?>
                            </div>
                            <div class="usr-hero-meta">
                                <?php if ($usr['user_name']): ?>
                                    <span><i class="bi bi-at"></i><?php echo htmlspecialchars($usr['user_name']); ?></span>
                                <?php endif; ?>
                                <span><i
                                        class="bi bi-envelope"></i><?php echo htmlspecialchars($usr['email_user']); ?></span>
                                <?php if ($usr['tel_user']): ?>
                                    <span><i
                                            class="bi bi-telephone"></i><?php echo htmlspecialchars($usr['tel_user']); ?></span>
                                <?php endif; ?>
                                <span><i class="bi bi-calendar3"></i>Desde
                                    <?php echo date('d/m/Y', strtotime($usr['creat_user'])); ?></span>
                            </div>
                        </div>
                        <!-- Acções rápidas -->
                        <?php if (hasPermission($admin_id, 'users.edit')): ?>
                            <div class="d-flex flex-column gap-2">
                                <?php if ($usr['status_user'] === 'active'): ?>
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/unavailable-account?id=<?php echo $id; ?>"
                                        class="action-btn" style="background:rgba(234,179,8,.15);color:#92400e">
                                        <i class="bi bi-lock-fill"></i>Suspender
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/available-account?id=<?php echo $id; ?>"
                                        class="action-btn" style="background:rgba(34,197,94,.15);color:#166534">
                                        <i class="bi bi-unlock-fill"></i>Activar
                                    </a>
                                <?php endif; ?>
                                <?php if ($admin_role === 'super_admin'): ?>
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/delete?id=<?php echo $id; ?>"
                                        class="action-btn" style="background:rgba(239,68,68,.12);color:#991b1b">
                                        <i class="bi bi-trash-fill"></i>Excluir
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row g-4">

                    <!-- Coluna esquerda -->
                    <div class="col-xl-8">

                        <!-- Informações pessoais -->
                        <div class="info-card">
                            <div class="info-card-title"><i class="bi bi-person-badge"></i>Informações Pessoais</div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="detail-row"><span class="detail-label">Primeiro Nome</span><span
                                            class="detail-value"><?php echo htmlspecialchars($usr['first_name']); ?></span>
                                    </div>
                                    <div class="detail-row"><span class="detail-label">Apelido</span><span
                                            class="detail-value"><?php echo htmlspecialchars($usr['second_name'] ?? '—'); ?></span>
                                    </div>
                                    <div class="detail-row"><span class="detail-label">Username</span><span
                                            class="detail-value">@<?php echo htmlspecialchars($usr['user_name'] ?? '—'); ?></span>
                                    </div>
                                    <div class="detail-row"><span class="detail-label">Telefone</span><span
                                            class="detail-value"><?php echo htmlspecialchars($usr['tel_user'] ?? '—'); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-row"><span class="detail-label">E-mail</span><span
                                            class="detail-value" style="font-size:.8rem"><a
                                                href="mailto:<?php echo htmlspecialchars($usr['email_user']); ?>"
                                                style="color:inherit"><?php echo htmlspecialchars($usr['email_user']); ?></a></span>
                                    </div>
                                    <div class="detail-row"><span class="detail-label">País</span><span
                                            class="detail-value"><?php echo htmlspecialchars($usr['country_user'] ?? '—'); ?></span>
                                    </div>
                                    <div class="detail-row"><span class="detail-label">Cidade</span><span
                                            class="detail-value"><?php echo htmlspecialchars($usr['city_user'] ?? '—'); ?></span>
                                    </div>
                                    <div class="detail-row"><span class="detail-label">Estado</span><span
                                            class="detail-value"><?php echo usr_view_status_badge($usr['status_user']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Artistas -->
                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="bi bi-music-note-beamed"></i>Artistas Ligados
                                <span style="margin-left:auto;font-size:.72rem;font-weight:400;opacity:.6">
                                    <?php echo count($artist_list); ?> artistas
                                </span>
                            </div>
                            <?php if (empty($artist_list)): ?>
                                <p style="font-size:.82rem;opacity:.4;text-align:center;padding:12px 0">Sem artistas
                                    registados.</p>
                            <?php else: ?>
                                <?php foreach ($artist_list as $art): ?>
                                    <div class="artist-mini">
                                        <?php if ($art['photo_artist']): ?>
                                            <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/artists/<?php echo htmlspecialchars($art['photo_artist']); ?>"
                                                class="artist-mini-ini" alt="" style="border-radius:8px" />
                                        <?php else: ?>
                                            <div class="artist-mini-ini"
                                                style="background:<?php echo adm_avatar_color($art['stage_name']); ?>">
                                                <?php echo mb_strtoupper(mb_substr($art['stage_name'], 0, 2, 'UTF-8'), 'UTF-8'); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <div style="font-weight:600;font-size:.82rem">
                                                <?php echo htmlspecialchars($art['stage_name']); ?></div>
                                        </div>
                                        <span
                                            class="badge bg-<?php echo $art['status_artist'] === 'active' ? 'success' : 'secondary'; ?>"
                                            style="font-size:.7rem">
                                            <?php echo ucfirst($art['status_artist']); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Lançamentos recentes -->
                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="bi bi-vinyl"></i>Lançamentos Recentes
                                <span style="margin-left:auto;font-size:.72rem;font-weight:400;opacity:.6">
                                    Últimos <?php echo count($album_list); ?>
                                </span>
                            </div>
                            <?php if (empty($album_list)): ?>
                                <p style="font-size:.82rem;opacity:.4;text-align:center;padding:12px 0">Sem lançamentos.</p>
                            <?php else: ?>
                                <div class="row g-2">
                                    <?php foreach ($album_list as $al): ?>
                                        <div class="col-6 col-md-4">
                                            <div
                                                style="background:var(--border-color,#f8f7fc);border-radius:10px;padding:10px;font-size:.78rem">
                                                <?php if ($al['img_cover']): ?>
                                                    <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/covers/<?php echo htmlspecialchars($al['img_cover']); ?>"
                                                        style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:6px;margin-bottom:6px" />
                                                <?php else: ?>
                                                    <div
                                                        style="width:100%;aspect-ratio:1;background:rgba(255,0,137,.1);border-radius:6px;margin-bottom:6px;display:flex;align-items:center;justify-content:center">
                                                        <i class="bi bi-music-note" style="color:#FF0089;font-size:1.5rem"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div
                                                    style="font-weight:600;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                                    <?php echo htmlspecialchars($al['title_album']); ?>
                                                </div>
                                                <?php echo usr_album_status($al['status_album']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Pagamentos -->
                        <div class="info-card">
                            <div class="info-card-title"><i class="bi bi-credit-card"></i>Pagamentos Recentes</div>
                            <?php if (empty($payment_list)): ?>
                                <p style="font-size:.82rem;opacity:.4;text-align:center;padding:12px 0">Sem pagamentos
                                    registados.</p>
                            <?php else: ?>
                                <?php foreach ($payment_list as $pay): ?>
                                    <div class="detail-row">
                                        <span class="detail-label"><?php echo adm_fmt_date($pay['creat_payment']); ?></span>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php echo usr_payment_status($pay['status_payment']); ?>
                                            <span style="font-weight:700;color:#FF0089">
                                                <?php echo adm_fmt_aoa((float)$pay['amount']); ?>
                                            </span>
                                            <span
                                                style="font-size:.74rem;opacity:.5"><?php echo htmlspecialchars($pay['amount'] ?? ''); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Actividade -->
                        <div class="info-card">
                            <div class="info-card-title">
                                <i class="bi bi-clock-history"></i>Actividade Recente
                                <span
                                    style="margin-left:auto;font-size:.72rem;font-weight:400;opacity:.6"><?php echo count($audit_list); ?>
                                    acções</span>
                            </div>
                            <?php if (empty($audit_list)): ?>
                                <p style="font-size:.82rem;opacity:.4;text-align:center;padding:12px 0">Sem actividade
                                    registada.</p>
                            <?php else: ?>
                                <?php foreach ($audit_list as $log): ?>
                                    <div class="audit-row">
                                        <div class="audit-icon"><i class="bi bi-activity"></i></div>
                                        <div class="flex-grow-1">
                                            <div style="font-weight:600;font-size:.8rem">
                                                <?php echo htmlspecialchars($log['action']); ?></div>
                                            <div style="font-size:.73rem;opacity:.6">
                                                <?php if ($log['ip_address']): ?><code
                                                        style="font-size:.7rem"><?php echo htmlspecialchars($log['ip_address']); ?></code>
                                                    · <?php endif; ?>
                                                <?php echo adm_fmt_date($log['creat_log']); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                    </div><!-- /col-xl-8 -->

                    <!-- Coluna direita -->
                    <div class="col-xl-4">

                        <!-- Conta -->
                        <div class="info-card">
                            <div class="info-card-title"><i class="bi bi-calendar3"></i>Conta</div>
                            <div class="detail-row"><span class="detail-label">ID</span><span class="detail-value"
                                    style="font-family:monospace;font-size:.8rem">#<?php echo $usr['id_users']; ?></span>
                            </div>
                            <div class="detail-row"><span class="detail-label">Criada em</span><span
                                    class="detail-value"
                                    style="font-size:.8rem"><?php echo date('d/m/Y H:i', strtotime($usr['creat_user'])); ?></span>
                            </div>
                            <?php if ($usr['modif_user']): ?>
                                <div class="detail-row"><span class="detail-label">Modificada</span><span
                                        class="detail-value"
                                        style="font-size:.8rem"><?php echo date('d/m/Y H:i', strtotime($usr['modif_user'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($usr['name_plan']): ?>
                                <div class="detail-row"><span class="detail-label">Plano</span><span
                                        class="detail-value"><span
                                            style="color:#FF0089;font-weight:700"><?php echo htmlspecialchars($usr['name_plan']); ?></span></span>
                                </div>
                                <?php if ($usr['plan_started']): ?>
                                    <div class="detail-row"><span class="detail-label">Plano desde</span><span
                                            class="detail-value"
                                            style="font-size:.8rem"><?php echo date('d/m/Y', strtotime($usr['plan_started'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Segurança -->
                        <div class="info-card">
                            <div class="info-card-title"><i class="bi bi-shield-lock"></i>Segurança</div>
                            <div class="detail-row"><span class="detail-label">Último login</span><span
                                    class="detail-value"
                                    style="font-size:.8rem"><?php echo $usr['last_login_at'] ? adm_fmt_date($usr['last_login_at']) : '—'; ?></span>
                            </div>
                            <div class="detail-row"><span class="detail-label">IP do login</span><span
                                    class="detail-value"
                                    style="font-family:monospace;font-size:.78rem"><?php echo htmlspecialchars($usr['last_login_ip'] ?? '—'); ?></span>
                            </div>
                            <div class="mt-3 d-flex flex-column gap-2">
                                <?php $att = (int)($usr['login_attempts'] ?? 0); ?>
                                <div class="sec-ind <?php echo $att === 0 ? 'ok' : ($att >= 3 ? 'danger' : 'warn'); ?>">
                                    <i class="bi bi-key"></i>
                                    <div>
                                        <div style="font-weight:600">Tentativas falhadas</div>
                                        <div style="font-size:.74rem;opacity:.75"><?php echo $att; ?>
                                            tentativa<?php echo $att !== 1 ? 's' : ''; ?></div>
                                    </div>
                                </div>
                                <?php if ($usr['block_until'] && strtotime($usr['block_until']) > time()): ?>
                                    <div class="sec-ind danger">
                                        <i class="bi bi-clock-fill"></i>
                                        <div>
                                            <div style="font-weight:600">Bloqueio temp.</div>
                                            <div style="font-size:.74rem;opacity:.75">Até
                                                <?php echo date('H:i d/m', strtotime($usr['block_until'])); ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="sec-ind <?php echo $usr['is_fraud_blocked'] ? 'danger' : 'ok'; ?>">
                                    <i
                                        class="bi bi-<?php echo $usr['is_fraud_blocked'] ? 'shield-fill-exclamation' : 'shield-check'; ?>"></i>
                                    <div>
                                        <div style="font-weight:600">Anti-fraude</div>
                                        <div style="font-size:.74rem;opacity:.75">
                                            <?php echo $usr['is_fraud_blocked'] ? 'Bloqueado por suspeita' : 'Sem alertas'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Acções rápidas -->
                        <div class="info-card">
                            <div class="info-card-title"><i class="bi bi-lightning-charge"></i>Acções Rápidas</div>
                            <div class="d-flex flex-column gap-2">
                                <?php if (hasPermission($admin_id, 'users.edit')): ?>
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/edit?id=<?php echo $id; ?>"
                                        class="btn btn-sm btn-warning text-dark w-100 text-start">
                                        <i class="bi bi-pencil me-2"></i>Editar utilizador
                                    </a>
                                <?php endif; ?>
                                <a href="mailto:<?php echo htmlspecialchars($usr['email_user']); ?>"
                                    class="btn btn-sm btn-outline-secondary w-100 text-start">
                                    <i class="bi bi-envelope me-2"></i>Enviar e-mail
                                </a>
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users"
                                    class="btn btn-sm btn-outline-secondary w-100 text-start">
                                    <i class="bi bi-arrow-left me-2"></i>Ver lista
                                </a>
                            </div>
                        </div>

                    </div><!-- /col-xl-4 -->
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="col-12 text-center py-2" style="font-size:.8rem">
                <p class="mb-0">© 2026 Wasom Upfy. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="" />
            <div class="loader-progress"></div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
        window.__BASE_URL__ = '<?php echo APP_URL; ?>';
        window.__ADMIN_PATH__ = '<?php echo ADMIN_PATH; ?>';
    </script>
</body>

</html>