    <?php
    // ═══════════════════════════════════════════════════════════════════════════
    // WASOM UPFY v2.0 — Verificação de Canais YouTube (Admin)
    // Arquivo: wu-panel/pages/integration/verify.php
    // Rota:    wu-panel/integration/verify
    // ═══════════════════════════════════════════════════════════════════════════
    require_once __DIR__ . '/../../include/platform_admin.php';
    requirePermission($admin_id, 'music.view');

    if (!isset($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }

    $db = getDB();


    // ═══════════════════════════════════════════════════════════════════════════
    // EXPORTAÇÃO CSV (antes de qualquer output HTML)
    // ═══════════════════════════════════════════════════════════════════════════
    $export_csv = $_GET['export_csv'] ?? '';
    if ($export_csv === '1' && isset($_GET['csrf'])) {
        if (!hash_equals($_SESSION['admin_csrf_token'], $_GET['csrf'])) {
            http_response_code(403);
            exit('Acesso negado.');
        }

        // Recolher os mesmos dados filtrados (sem paginação)
        $status = trim($_GET['status'] ?? '');
        $search = trim($_GET['search'] ?? '');
        $where  = [];
        $params = [];

        if ($status !== '') {
            $where[] = 'yc.status_youtube = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(yc.channel_name LIKE ? OR yc.channel_id LIKE ? OR u.email_user LIKE ? OR u.first_name LIKE ? OR u.second_name LIKE ? OR a.stage_name LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }
        $sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
        SELECT
            yc.id_youtube,
            yc.channel_name,
            yc.channel_id,
            yc.channel_url,
            yc.verified_code,
            yc.status_youtube,
            DATE_FORMAT(yc.creat_youtube, '%d/%m/%Y %H:%i') AS creat_date,
            DATE_FORMAT(yc.verified_at, '%d/%m/%Y %H:%i') AS verified_date,
            CONCAT(u.first_name, ' ', COALESCE(u.second_name, '')) AS user_name,
            u.email_user,
            a.stage_name AS artist_name
        FROM _youtube_channel yc
        LEFT JOIN _users u ON u.id_users = yc.id_users
        LEFT JOIN _artist a ON a.id_artist = yc.id_artist
        $sql_where
        ORDER BY yc.id_youtube ASC
    ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Gerar CSV ─────────────────────────────────────────────────────────
        $filename = 'canais_youtube_' . date('Y-m-d_H-i') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store');
        echo "\xEF\xBB\xBF"; // BOM UTF-8

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'ID',
            'Canal',
            'ID do Canal',
            'URL',
            'Código Verificação',
            'Estado',
            'Data Registo',
            'Verificado em',
            'Utilizador',
            'E-mail',
            'Artista'
        ], ';');

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id_youtube'],
                $r['channel_name'],
                $r['channel_id'],
                $r['channel_url'],
                $r['verified_code'],
                $r['status_youtube'],
                $r['creat_date'],
                $r['verified_date'] ?? '—',
                $r['user_name'],
                $r['email_user'],
                $r['artist_name'] ?? '—'
            ], ';');
        }
        fclose($out);
        exit;
    }

    // ── Estatísticas para os cards ───────────────────────────────────────────
    $stats = [
        'total'    => (int)$db->query("SELECT COUNT(*) FROM _youtube_channel")->fetchColumn(),
        'pending'  => (int)$db->query("SELECT COUNT(*) FROM _youtube_channel WHERE status_youtube = 'pending'")->fetchColumn(),
        'verified' => (int)$db->query("SELECT COUNT(*) FROM _youtube_channel WHERE status_youtube = 'verified'")->fetchColumn(),
        'rejected' => (int)$db->query("SELECT COUNT(*) FROM _youtube_channel WHERE status_youtube = 'rejected'")->fetchColumn(),
        'removed'  => (int)$db->query("SELECT COUNT(*) FROM _youtube_channel WHERE status_youtube = 'removed'")->fetchColumn(),
    ];

    // ── Filtros e paginação ──────────────────────────────────────────────────
    $per_page  = 20;
    $page      = max(1, (int)($_GET['page'] ?? 1));
    $status    = trim($_GET['status'] ?? '');
    $search    = trim($_GET['search'] ?? '');

    $where  = [];
    $params = [];

    if ($status !== '') {
        $where[] = 'yc.status_youtube = ?';
        $params[] = $status;
    }
    if ($search !== '') {
        $like = '%' . $search . '%';
        $where[] = '(yc.channel_name LIKE ? OR yc.channel_id LIKE ? OR u.email_user LIKE ? OR u.first_name LIKE ? OR u.second_name LIKE ? OR a.stage_name LIKE ?)';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }

    $sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Contagem total
    $count_sql = "
    SELECT COUNT(*)
    FROM _youtube_channel yc
    LEFT JOIN _users u ON u.id_users = yc.id_users
    LEFT JOIN _artist a ON a.id_artist = yc.id_artist
    $sql_where
";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_filtered = (int)$stmt->fetchColumn();
    $total_pages = max(1, ceil($total_filtered / $per_page));
    $page = min($page, $total_pages);
    $offset = ($page - 1) * $per_page;

    // Consulta principal
    $sql = "
    SELECT
        yc.*,
        u.id_users, u.first_name, u.second_name, u.email_user, u.photo_user,
        a.id_artist, a.stage_name, a.photo_artist,
        CONCAT(u.first_name,' ',COALESCE(u.second_name,'')) AS user_fullname
    FROM _youtube_channel yc
    LEFT JOIN _users u ON u.id_users = yc.id_users
    LEFT JOIN _artist a ON a.id_artist = yc.id_artist
    $sql_where
    ORDER BY FIELD(yc.status_youtube, 'pending', 'verified', 'rejected', 'removed'), yc.creat_youtube ASC
    LIMIT $per_page OFFSET $offset
";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $channels = $stmt->fetchAll();

    $csrf = $_SESSION['admin_csrf_token'];
    $base_url = APP_URL . '/' . ADMIN_PATH;

    // ── Helpers de apresentação ──────────────────────────────────────────────
    function yt_status_badge(string $status): string
    {
        return match ($status) {
            'pending'  => '<span class="badge bg-warning text-dark">Pendente</span>',
            'verified' => '<span class="badge bg-success">Verificado</span>',
            'rejected' => '<span class="badge bg-danger">Rejeitado</span>',
            'removed'  => '<span class="badge bg-secondary">Removido</span>',
            default    => '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>',
        };
    }

    function yt_avatar(string $name, ?string $photo, string $type, int $size = 32): string
    {
        $p = explode(' ', trim($name), 2);
        $ini = mb_strtoupper(mb_substr($p[0] ?? '', 0, 1, 'UTF-8'), 'UTF-8')
            . mb_strtoupper(mb_substr($p[1] ?? '', 0, 1, 'UTF-8'), 'UTF-8');
        $colors = ['#FF0089', '#f97316', '#8b5cf6', '#06b6d4', '#22c55e', '#eab308', '#3b82f6', '#ef4444'];
        $color = $colors[abs(crc32($name)) % count($colors)];
        $font_size = round($size * 0.3);
        if ($photo) {
            $path = $type === 'user' ? '/assets/comprovantes/uploads/users/' : '/assets/comprovantes/uploads/artists/';
            return '<img src="' . APP_URL . $path . htmlspecialchars($photo) . '"
                     width="' . $size . '" height="' . $size . '"
                     style="border-radius:50%;object-fit:cover;border:2px solid rgba(255,0,137,.2)"
                     onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'" alt="">
                <div style="width:' . $size . 'px;height:' . $size . 'px;border-radius:50%;background:' . $color . ';
                            display:none;align-items:center;justify-content:center;
                            font-weight:700;font-size:' . $font_size . 'px;color:#fff">' . $ini . '</div>';
        }
        return '<div style="width:' . $size . 'px;height:' . $size . 'px;border-radius:50%;background:' . $color . ';
                         display:flex;align-items:center;justify-content:center;
                         font-weight:700;font-size:' . $font_size . 'px;color:#fff">' . $ini . '</div>';
    }

    function fmt_num(int|float $v): string
    {
        if ($v >= 1_000_000) return number_format($v / 1_000_000, 1, ',', '.') . 'M';
        if ($v >= 1_000) return number_format($v / 1_000, 1, ',', '.') . 'K';
        return number_format($v, 0, ',', '.');
    }
    ?>
    <!DOCTYPE html>
    <html lang="pt-ao">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
        <title>Verificar Canais YouTube - Wasom Upfy Admin</title>
        <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png">
        <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
        <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <style>
            /* Estilos consistentes com analytics/artists.php */
            .stat-card {
                background: var(--card-bg, #fff);
                border: 1px solid var(--border-color, #e8e8f0);
                border-radius: 12px;
                padding: 14px 16px;
                display: flex;
                align-items: center;
                gap: 12px;
                transition: transform .2s, box-shadow .2s;
            }

            .stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(0, 0, 0, .06);
            }

            .stat-icon {
                width: 42px;
                height: 42px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.15rem;
            }

            .stat-value {
                font-size: 1.25rem;
                font-weight: 800;
                line-height: 1;
            }

            .stat-label {
                font-size: .7rem;
                text-transform: uppercase;
                letter-spacing: .5px;
                opacity: .6;
                margin-top: 2px;
            }

            .filter-bar {
                background: var(--card-bg, #fff);
                border: 1px solid var(--border-color, #e8e8f0);
                border-radius: 12px;
                padding: 14px 16px;
                margin-bottom: 24px;
            }

            .filter-bar .form-label {
                font-size: .75rem;
                font-weight: 600;
                margin-bottom: 3px;
            }

            .table thead th {
                font-size: .72rem;
                text-transform: uppercase;
                letter-spacing: .4px;
                font-weight: 700;
                white-space: nowrap;
            }

            .table td {
                font-size: .8rem;
                vertical-align: middle;
            }

            #verify-table th {
                font-size: .74rem;
                text-transform: uppercase;
                letter-spacing: .4px;
                font-weight: 700;
                white-space: nowrap;
                cursor: pointer;
            }

            #verify-table td {
                font-size: .82rem;
                vertical-align: middle;
            }



            .view-info-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0;
                border-bottom: 1px solid var(--border-color, #e8e8f0);
            }

            .view-info-lbl {
                font-size: .78rem;
                font-weight: 600;
                opacity: .6;
                min-width: 130px;
            }

            .view-info-val {
                font-size: .82rem;
                text-align: right;
            }

            .toast-container {
                z-index: 9999;
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
                    <!-- Breadcrumb -->
                    <div class="d-flex align-items-center gap-3 mb-3 mt-2">
                        <a href="<?php echo $base_url; ?>" class="btn btn-sm btn-outline-secondary"
                            style="border-radius:8px">
                            <i class="bi bi-arrow-left me-1"></i> Dashboard
                        </a>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item active text-white-stable">Verificar Canais YouTube</li>
                            </ol>
                        </nav>
                    </div>

                    <!-- Cards de estatísticas -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-4 col-xl">
                            <div class="stat-card">
                                <div class="stat-icon" style="background:#FF008922"><i class="bi bi-youtube"
                                        style="color:#FF0089"></i></div>
                                <div>
                                    <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                                    <div class="stat-label">Total</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl">
                            <div class="stat-card">
                                <div class="stat-icon" style="background:#f9731622"><i class="bi bi-clock-history"
                                        style="color:#f97316"></i></div>
                                <div>
                                    <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
                                    <div class="stat-label">Pendentes</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl">
                            <div class="stat-card">
                                <div class="stat-icon" style="background:#22c55e22"><i class="bi bi-check-circle"
                                        style="color:#22c55e"></i></div>
                                <div>
                                    <div class="stat-value"><?php echo number_format($stats['verified']); ?></div>
                                    <div class="stat-label">Verificados</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl">
                            <div class="stat-card">
                                <div class="stat-icon" style="background:#ef444422"><i class="bi bi-x-circle"
                                        style="color:#ef4444"></i></div>
                                <div>
                                    <div class="stat-value"><?php echo number_format($stats['rejected']); ?></div>
                                    <div class="stat-label">Rejeitados</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl">
                            <div class="stat-card">
                                <div class="stat-icon" style="background:#6c757d22"><i class="bi bi-trash"
                                        style="color:#6c757d"></i></div>
                                <div>
                                    <div class="stat-value"><?php echo number_format($stats['removed']); ?></div>
                                    <div class="stat-label">Removidos</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="filter-bar">
                        <form method="GET" id="filter-form">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Estado</label>
                                    <select name="status" class="form-select form-select-sm filter-instant">
                                        <option value="">Todos</option>
                                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>
                                            Pendente (<?php echo $stats['pending']; ?>)</option>
                                        <option value="verified"
                                            <?php echo $status === 'verified' ? 'selected' : ''; ?>>
                                            Verificado (<?php echo $stats['verified']; ?>)</option>
                                        <option value="rejected"
                                            <?php echo $status === 'rejected' ? 'selected' : ''; ?>>
                                            Rejeitado (<?php echo $stats['rejected']; ?>)</option>
                                        <option value="removed" <?php echo $status === 'removed' ? 'selected' : ''; ?>>
                                            Removido (<?php echo $stats['removed']; ?>)</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Pesquisar</label>
                                    <input type="text" name="search"
                                        class="form-control form-control-sm filter-debounce"
                                        placeholder="Nome do canal, ID, e-mail, artista..."
                                        value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm text-white w-100"
                                        style="background:#FF0089"><i class="bi bi-search"></i> Filtrar</button>
                                </div>
                                <div class="col-md-2">
                                    <a href="?" class="btn btn-sm btn-outline-secondary w-100">Limpar</a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Tabela -->
                    <div class="card" style="border-radius:14px;overflow:hidden">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2"
                            style="border-bottom:1px solid var(--border-color,#e8e8f0)">
                            <span style="font-size:.82rem;font-weight:600">
                                <i class="bi bi-youtube me-2" style="color:#FF0089"></i>
                                <?php echo number_format($total_filtered); ?> canal(is)
                            </span>
                            <?php if (hasPermission($admin_id, 'music.edit')): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                        data-bs-toggle="dropdown">
                                        <i class="bi bi-download me-1"></i> Exportar
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item"
                                                href="?export_csv=1&csrf=<?php echo urlencode($csrf); ?>&<?php echo http_build_query(array_merge($_GET, ['export_csv' => null])); ?>">
                                                <i class="bi bi-filetype-csv me-2"></i> CSV (Excel)
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="verify">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Canal</th>
                                        <th>Utilizador / Artista</th>
                                        <th>Código</th>
                                        <th>Estado</th>
                                        <th>Data</th>
                                        <th style="width:120px">Acções</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($channels)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-youtube"
                                                    style="font-size:2rem;opacity:.3"></i>
                                                <p class="mb-0 mt-2">Nenhum canal encontrado.</p>
                                            </td>
                                        </tr>
                                        <?php else: foreach ($channels as $ch): ?>
                                            <tr>
                                                <td>#<?php echo (int)$ch['id_youtube']; ?></td>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($ch['channel_name']); ?>
                                                    </div>
                                                    <div class="small text-muted"><a
                                                            href="<?php echo htmlspecialchars($ch['channel_url'] ?: '#'); ?>"
                                                            target="_blank"><?php echo htmlspecialchars($ch['channel_id']); ?></a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <?php echo yt_avatar($ch['user_fullname'], $ch['photo_user'], 'user', 28); ?>
                                                        <div>
                                                            <div class="fw-semibold">
                                                                <?php echo htmlspecialchars($ch['user_fullname']); ?></div>
                                                            <div class="small text-muted">
                                                                <?php echo htmlspecialchars($ch['email_user']); ?></div>
                                                            <?php if ($ch['stage_name']): ?><div class="small"><i
                                                                        class="bi bi-mic"></i>
                                                                    <?php echo htmlspecialchars($ch['stage_name']); ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo $ch['verified_code'] ? '<span class="badge bg-light text-dark font-monospace">' . htmlspecialchars($ch['verified_code']) . '</span>' : '—'; ?>
                                                </td>
                                                <td><?php echo yt_status_badge($ch['status_youtube']); ?></td>
                                                <td><small><?php echo date('d/m/Y H:i', strtotime($ch['creat_youtube'])); ?></small>
                                                </td>
                                                <td>
                                                    <div class="actions-dropdown dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false"><i
                                                                class="bi bi-three-dots-vertical"></i></button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="#"
                                                                    onclick="viewChannel(<?php echo htmlspecialchars(json_encode(['id' => $ch['id_youtube'], 'channel_name' => $ch['channel_name'], 'channel_id' => $ch['channel_id'], 'channel_url' => $ch['channel_url'], 'verified_code' => $ch['verified_code'], 'status_youtube' => $ch['status_youtube'], 'creat_youtube' => $ch['creat_youtube'], 'verified_at' => $ch['verified_at'], 'user_fullname' => $ch['user_fullname'], 'email_user' => $ch['email_user'], 'stage_name' => $ch['stage_name'], 'photo_user' => $ch['photo_user'], 'photo_artist' => $ch['photo_artist']])); ?>); return false"><i
                                                                        class="bi bi-eye text-info"></i> Visualizar</a></li>
                                                            <?php if (hasPermission($admin_id, 'music.edit')): ?>
                                                                <li><a class="dropdown-item"
                                                                        href="<?php echo $base_url; ?>/integration/edit?id=<?php echo $ch['id_youtube']; ?>"><i
                                                                            class="bi bi-pencil text-warning"></i> Editar</a></li>
                                                            <?php endif; ?>
                                                            <?php if ($ch['status_youtube'] === 'pending' && hasPermission($admin_id, 'music.approve')): ?>
                                                                <li>
                                                                    <hr class="dropdown-divider">
                                                                </li>
                                                                <li><a class="dropdown-item text-success" href="#"
                                                                        onclick="verifyChannel(<?php echo $ch['id_youtube']; ?>); return false"><i
                                                                            class="bi bi-check-lg"></i> Verificar</a></li>
                                                                <li><a class="dropdown-item text-danger" href="#"
                                                                        onclick="rejectChannel(<?php echo $ch['id_youtube']; ?>); return false"><i
                                                                            class="bi bi-x-lg"></i> Rejeitar</a></li>
                                                            <?php endif; ?>
                                                            <?php if (hasPermission($admin_id, 'music.edit') && in_array($ch['status_youtube'], ['pending', 'verified', 'rejected'])): ?>
                                                                <li>
                                                                    <hr class="dropdown-divider">
                                                                </li>
                                                                <li><a class="dropdown-item text-danger" href="#"
                                                                        onclick="removeChannel(<?php echo $ch['id_youtube']; ?>); return false"><i
                                                                            class="bi bi-trash"></i> Remover</a></li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                    <?php endforeach;
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($total_pages > 1): ?>
                            <div class="d-flex justify-content-center py-3">
                                <nav>
                                    <ul class="pagination pagination-sm mb-0"><?php for ($i = 1; $i <= $total_pages; $i++): ?><li
                                                class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link"
                                                    href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                            </li><?php endfor; ?></ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Visualizar Canal -->
        <div class="modal fade" id="modalViewChannel" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header" style="background:#FF0089">
                        <h5 class="modal-title text-white fw-bold"><i class="bi bi-youtube me-2"></i>Detalhes do Canal
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4" id="viewChannelBody"></div>
                    <div class="modal-footer border-0"><button type="button" class="btn btn-outline-secondary btn-sm"
                            data-bs-dismiss="modal">Fechar</button><button type="button" class="btn btn-sm text-white"
                            style="background:#FF0089" id="printChannelBtn"><i class="bi bi-file-earmark-pdf me-1"></i>
                            Download PDF</button></div>
                </div>
            </div>
        </div>

        <!-- Toast container -->
        <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>
        <div class="page-loader" id="pageLoader">
            <div class="loader-content"><img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png"
                    class="loader-image" alt="">
                <div class="loader-progress"></div>
            </div>
        </div>

        <!-- Footer -->
        <footer>
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center">
                        <p class="mb-2">© <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos os direitos
                            reservados.
                        </p>
                    </div>
                </div>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
        <script>
            (function() {
                const BASE_URL = '<?php echo APP_URL; ?>';
                const ADMIN_PATH = '<?php echo ADMIN_PATH; ?>';
                const CSRF = document.querySelector('meta[name="csrf-token"]').content;
                const PROCESS = BASE_URL + '/' + ADMIN_PATH + '/integration/verify-process';

                // Filtros com debounce/instant
                let dbt;
                document.querySelectorAll('.filter-debounce').forEach(el => el.addEventListener('input', () => {
                    clearTimeout(dbt);
                    dbt = setTimeout(() => document.getElementById('filter-form').submit(), 500);
                }));
                document.querySelectorAll('.filter-instant').forEach(el => el.addEventListener('change', () => document
                    .getElementById('filter-form').submit()));

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

                function showToast(type, title, message) {
                    const container = document.querySelector('.toast-container');
                    const id = 'toast-' + Date.now();
                    const bg = type === 'success' ? 'bg-success' : (type === 'warning' ? 'bg-warning' : 'bg-danger');
                    const html =
                        `<div id="${id}" class="toast align-items-center text-white ${bg} border-0" role="alert" data-bs-autohide="true" data-bs-delay="5000"><div class="d-flex"><div class="toast-body"><strong>${escapeHtml(title)}</strong><br>${escapeHtml(message)}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`;
                    container.insertAdjacentHTML('beforeend', html);
                    const toastEl = document.getElementById(id);
                    new bootstrap.Toast(toastEl).show();
                    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
                }

                function escapeHtml(str) {
                    return String(str).replace(/[&<>]/g, function(m) {
                        if (m === '&') return '&amp;';
                        if (m === '<') return '&lt;';
                        if (m === '>') return '&gt;';
                        return m;
                    });
                }

                // Visualizar
                let currentChannel = null;
                window.viewChannel = function(data) {
                    currentChannel = data;
                    const statusMap = {
                        pending: 'Pendente',
                        verified: 'Verificado',
                        rejected: 'Rejeitado',
                        removed: 'Removido'
                    };
                    const statusBadge = {
                        pending: 'bg-warning text-dark',
                        verified: 'bg-success',
                        rejected: 'bg-danger',
                        removed: 'bg-secondary'
                    } [data.status_youtube] || 'bg-secondary';
                    const html =
                        `<div class="row g-4"><div class="col-md-4 text-center"><div class="bg-light rounded-3 p-3 mb-3"><i class="bi bi-youtube" style="font-size:4rem;color:#FF0089"></i></div><div class="fw-bold">${escapeHtml(data.channel_name)}</div><div class="text-muted small">${escapeHtml(data.channel_id)}</div><div class="mt-2"><span class="badge ${statusBadge}">${statusMap[data.status_youtube]}</span></div></div><div class="col-md-8"><div class="view-info-row"><span class="view-info-lbl">URL</span><span class="view-info-val"><a href="${escapeHtml(data.channel_url)}" target="_blank">${escapeHtml(data.channel_url)}</a></span></div><div class="view-info-row"><span class="view-info-lbl">Código de Verificação</span><span class="view-info-val">${escapeHtml(data.verified_code || '—')}</span></div><div class="view-info-row"><span class="view-info-lbl">Utilizador</span><span class="view-info-val">${escapeHtml(data.user_fullname)} · ${escapeHtml(data.email_user)}</span></div><div class="view-info-row"><span class="view-info-lbl">Artista</span><span class="view-info-val">${escapeHtml(data.stage_name || '—')}</span></div><div class="view-info-row"><span class="view-info-lbl">Registo</span><span class="view-info-val">${new Date(data.creat_youtube).toLocaleString('pt-BR')}</span></div>${data.verified_at ? `<div class="view-info-row"><span class="view-info-lbl">Verificado em</span><span class="view-info-val">${new Date(data.verified_at).toLocaleString('pt-BR')}</span></div>` : ''}</div></div>`;
                    document.getElementById('viewChannelBody').innerHTML = html;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalViewChannel')).show();
                };
                document.getElementById('printChannelBtn')?.addEventListener('click', function() {
                    if (!currentChannel) return;
                    const pdfHtml =
                        `<div style="font-family:Arial;padding:20px"><div style="text-align:center;border-bottom:3px solid #FF0089"><h1 style="color:#FF0089">WASOM UPFY</h1><h2>Detalhes do Canal YouTube</h2><p>Gerado em ${new Date().toLocaleString('pt-AO')}</p></div><table style="width:100%;margin-top:20px"><tr><td><strong>Canal:</strong></td><td>${escapeHtml(currentChannel.channel_name)}</td></tr><tr><td><strong>ID:</strong></td><td>${escapeHtml(currentChannel.channel_id)}</td></tr><tr><td><strong>URL:</strong></td><td>${escapeHtml(currentChannel.channel_url)}</td></tr><tr><td><strong>Código:</strong></td><td>${escapeHtml(currentChannel.verified_code)}</td></tr><tr><td><strong>Estado:</strong></td><td>${currentChannel.status_youtube}</td></tr><tr><td><strong>Utilizador:</strong></td><td>${escapeHtml(currentChannel.user_fullname)} (${escapeHtml(currentChannel.email_user)})</td></tr></table></div>`;
                    html2pdf().set({
                        margin: 10,
                        filename: `canal_${currentChannel.channel_id}.pdf`,
                        image: {
                            type: 'jpeg',
                            quality: 0.98
                        },
                        html2canvas: {
                            scale: 2
                        },
                        jsPDF: {
                            unit: 'mm',
                            format: 'a4',
                            orientation: 'portrait'
                        }
                    }).from(pdfHtml).save();
                });

                // Verificar
                window.verifyChannel = async (id) => {
                    const confirm = await Swal.fire({
                        title: 'Verificar canal?',
                        text: 'O canal será marcado como verificado e o utilizador será notificado.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#22c55e'
                    });
                    if (!confirm.isConfirmed) return;
                    Swal.fire({
                        title: 'A processar...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    const data = await postAction({
                        action: 'verify',
                        id_youtube: id
                    });
                    if (data.ok) Swal.fire({
                        icon: 'success',
                        title: 'Verificado!',
                        text: data.message,
                        confirmButtonColor: '#FF0089'
                    }).then(() => location.reload());
                    else Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: data.message,
                        confirmButtonColor: '#FF0089'
                    });
                };

                // Rejeitar (com motivo via SweetAlert)
                window.rejectChannel = async (id) => {
                    const {
                        value: reason
                    } = await Swal.fire({
                        title: 'Rejeitar canal',
                        input: 'textarea',
                        inputLabel: 'Motivo da rejeição',
                        inputPlaceholder: 'Explique ao utilizador o motivo...',
                        inputAttributes: {
                            required: true
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Rejeitar',
                        confirmButtonColor: '#ef4444',
                        preConfirm: (r) => {
                            if (!r || r.length < 5) Swal.showValidationMessage(
                                'Motivo com pelo menos 5 caracteres');
                            return r;
                        }
                    });
                    if (!reason) return;
                    Swal.fire({
                        title: 'A processar...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    const data = await postAction({
                        action: 'reject',
                        id_youtube: id,
                        reason: reason
                    });
                    if (data.ok) Swal.fire({
                        icon: 'success',
                        title: 'Rejeitado!',
                        text: data.message,
                        confirmButtonColor: '#FF0089'
                    }).then(() => location.reload());
                    else Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: data.message,
                        confirmButtonColor: '#FF0089'
                    });
                };

                // Remover com senha (SweetAlert com input)
                window.removeChannel = async (id) => {
                    const {
                        value: password
                    } = await Swal.fire({
                        title: 'Remover canal',
                        text: 'Esta acção é irreversível. Confirma a tua senha de administrador.',
                        input: 'password',
                        inputPlaceholder: 'Senha do admin',
                        inputAttributes: {
                            required: true,
                            autocomplete: 'current-password'
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Remover',
                        confirmButtonColor: '#ef4444'
                    });
                    if (!password) return;
                    Swal.fire({
                        title: 'A processar...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    const data = await postAction({
                        action: 'remove',
                        id_youtube: id,
                        admin_password: password
                    });
                    if (data.ok) Swal.fire({
                        icon: 'success',
                        title: 'Removido!',
                        text: data.message,
                        confirmButtonColor: '#FF0089'
                    }).then(() => location.reload());
                    else Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: data.message,
                        confirmButtonColor: '#FF0089'
                    });
                };
            })();
        </script>
    </body>

    </html>