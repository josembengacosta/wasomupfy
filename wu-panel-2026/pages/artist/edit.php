<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Editar Artista
// Arquivo: wu-panel-2026/pages/artist/edit.php
// Rota:    wu-panel-2026/artist/edit?id=X
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'users.edit');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) adminRedirect('/' . ADMIN_PATH . '/artist');

$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'updated'      => ['success', 'bi-check-circle', 'Dados actualizados com sucesso.'],
    'error'        => ['danger',  'bi-x-circle',     'Ocorreu um erro. Tenta novamente.'],
    'dupe_stage'   => ['danger',  'bi-x-circle',     'Já existe um artista com este nome artístico para este utilizador.'],
    default        => null,
};

$stmt = $db->prepare("
    SELECT a.*, u.id_users AS owner_id, u.first_name AS owner_first, u.second_name AS owner_second, u.email_user AS owner_email, u.photo_user AS owner_photo
    FROM _artist a
    LEFT JOIN _users u ON u.id_users = a.id_users
    WHERE a.id_artist = ?
");
$stmt->execute([$id]);
$artist = $stmt->fetch();
if (!$artist) adminRedirect('/' . ADMIN_PATH . '/artist?msg=not_found');

// Actividade recente
$activity = $db->prepare("
    SELECT activity_type, description, creat_activity
    FROM _user_activity_log
    WHERE entity = 'artist' AND entity_id = ?
    ORDER BY creat_activity DESC
    LIMIT 5
");
$activity->execute([$id]);
$activity_list = $activity->fetchAll();

$fullname = $artist['stage_name'];
$ini = function($name) {
    $parts = explode(' ', trim($name));
    $init = '';
    foreach ($parts as $part) if (mb_strlen($init) < 2 && !empty($part)) $init .= mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'));
    return $init ?: 'A';
};
$color = function($name) {
    $colors = ['#FF0089','#f97316','#8b5cf6','#06b6d4','#22c55e','#eab308','#ec4899','#14b8a6','#3b82f6','#ef4444'];
    return $colors[abs(crc32($name)) % count($colors)];
};
$avatar_color = $color($fullname);
$owner_name = trim(($artist['owner_first'] ?? '') . ' ' . ($artist['owner_second'] ?? ''));
$owner_ini = $ini($owner_name);
$owner_color = $color($owner_name);
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title>Editar <?php echo htmlspecialchars($fullname); ?> — Artista · Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
    .ae-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 14px;
        padding: 22px 24px;
        margin-bottom: 20px;
    }

    .ae-card-title {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        opacity: .5;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .ae-profile-card {
        background: linear-gradient(160deg, #0f0f1a 0%, #1a0a12 100%);
        border-radius: 16px;
        padding: 28px 22px;
        text-align: center;
        position: relative;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .ae-avatar-wrap {
        position: relative;
        display: inline-block;
    }

    .ae-avatar-lg {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(255, 0, 137, .35);
    }

    .ae-avatar-ini-lg {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.5rem;
        color: #fff;
        border: 3px solid rgba(255, 255, 255, .15);
        margin: 0 auto;
    }

    .ae-form-label {
        font-size: .78rem;
        font-weight: 600;
        margin-bottom: 5px;
        opacity: .7;
    }

    .ae-hint {
        font-size: .72rem;
        opacity: .45;
        margin-top: 3px;
    }

    .ae-act-item {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-color, #e8e8f0);
        font-size: .78rem;
    }

    .ae-act-item:last-child {
        border-bottom: none;
    }

    .ae-act-dot {
        width: 26px;
        height: 26px;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .78rem;
        flex-shrink: 0;
    }

    .ae-owner-mini {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 10px;
        text-decoration: none;
        color: inherit;
    }

    .ae-owner-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }

    .ae-owner-ini {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: .68rem;
        color: #fff;
        flex-shrink: 0;
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
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1"><i class="bi bi-pencil-square me-2"></i>Editar Artista</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist"
                                        class="text-secondary">Artistas</a></li>
                                <li class="breadcrumb-item"><a
                                        href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/view?id=<?php echo $id; ?>"
                                        class="text-secondary"><?php echo htmlspecialchars($fullname); ?></a></li>
                                <li class="breadcrumb-item active text-white-stable">Editar</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/view?id=<?php echo $id; ?>"
                            class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i> Visualizar</a>
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist"
                            class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Voltar</a>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Coluna Esquerda (3) -->
                    <div class="col-lg-3">
                        <div class="ae-profile-card">
                            <div class="ae-avatar-wrap mb-3">
                                <?php if (!empty($artist['photo_artist'])): ?>
                                <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/artists/<?php echo htmlspecialchars($artist['photo_artist']); ?>"
                                    class="ae-avatar-lg" id="avatar-preview" alt=""
                                    onerror="this.style.display='none';document.getElementById('avatar-placeholder').style.display='flex'" />
                                <div class="ae-avatar-ini-lg" id="avatar-placeholder"
                                    style="background:<?php echo $avatar_color; ?>;display:none">
                                    <?php echo $ini($fullname); ?></div>
                                <?php else: ?>
                                <div class="ae-avatar-ini-lg" id="avatar-placeholder"
                                    style="background:<?php echo $avatar_color; ?>"><?php echo $ini($fullname); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="text-white fw-700 mb-1" id="preview-name"
                                style="font-size:.95rem;font-weight:700"><?php echo htmlspecialchars($fullname); ?>
                            </div>
                            <?php if (!empty($artist['real_name'])): ?>
                            <div style="color:rgba(255,255,255,.6);font-size:.8rem;margin-bottom:5px" id="preview-real">
                                <?php echo htmlspecialchars($artist['real_name']); ?></div>
                            <?php endif; ?>
                            <div style="color:rgba(255,255,255,.45);font-size:.77rem">
                                <?php echo $artist['status_artist'] === 'active' ? 'Activo' : ($artist['status_artist'] === 'blocked' ? 'Bloqueado' : 'Inactivo'); ?>
                            </div>
                        </div>

                        <div class="ae-card">
                            <div class="ae-card-title"><i class="bi bi-activity"></i> Actividade</div>
                            <?php if (empty($activity_list)): ?>
                            <div style="text-align:center;opacity:.35;font-size:.78rem;padding:12px 0">Sem actividade
                                registada</div>
                            <?php else: ?>
                            <?php foreach ($activity_list as $act):
                                    $desc = $act['description'] ?: str_replace('_', ' ', $act['activity_type']);
                                ?>
                            <div class="ae-act-item">
                                <div class="ae-act-dot" style="background:rgba(255,0,137,.1)"><i
                                        class="bi bi-lightning-charge-fill" style="color:#FF0089"></i></div>
                                <div>
                                    <div style="font-weight:600"><?php echo htmlspecialchars($desc); ?></div>
                                    <div style="opacity:.4;font-size:.7rem"><?php
                                                $ts = strtotime($act['creat_activity']);
                                                $diff = time() - $ts;
                                                if ($diff < 3600) echo floor($diff / 60) . ' min atrás';
                                                elseif ($diff < 86400) echo floor($diff / 3600) . 'h atrás';
                                                else echo date('d/m/Y', $ts);
                                            ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($artist['owner_id']): ?>
                        <div class="ae-card">
                            <div class="ae-card-title"><i class="bi bi-person-circle"></i> Proprietário</div>
                            <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/users/view?id=<?php echo $artist['owner_id']; ?>"
                                class="ae-owner-mini">
                                <?php if (!empty($artist['owner_photo'])): ?>
                                <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/users/<?php echo htmlspecialchars($artist['owner_photo']); ?>"
                                    class="ae-owner-avatar" alt=""
                                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                                <div class="ae-owner-ini" style="background:<?php echo $owner_color; ?>;display:none">
                                    <?php echo $owner_ini; ?></div>
                                <?php else: ?>
                                <div class="ae-owner-ini" style="background:<?php echo $owner_color; ?>">
                                    <?php echo $owner_ini; ?></div>
                                <?php endif; ?>
                                <div>
                                    <div style="font-size:.82rem;font-weight:700">
                                        <?php echo htmlspecialchars($owner_name ?: '—'); ?></div>
                                    <div
                                        style="font-size:.72rem;opacity:.45;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:140px">
                                        <?php echo htmlspecialchars($artist['owner_email'] ?? ''); ?></div>
                                </div>
                                <i class="bi bi-arrow-right ms-auto" style="opacity:.3;font-size:.85rem"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Coluna Principal (9) -->
                    <div class="col-lg-9">
                        <form method="POST" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/edit-process"
                            enctype="multipart/form-data" id="form-profile">
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
                            <input type="hidden" name="id_artist" value="<?php echo $id; ?>">
                            <input type="hidden" name="action" value="update_profile">

                            <?php if ($feedback): ?>
                            <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3"><i
                                    class="bi <?php echo $feedback[1]; ?> me-2"></i><?php echo htmlspecialchars($feedback[2]); ?><button
                                    type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                            <?php endif; ?>

                            <!-- Dados básicos -->
                            <div class="ae-card">
                                <div class="ae-card-title"><i class="bi bi-person"></i> Dados do Artista</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="ae-form-label">Nome artístico *</label>
                                        <input type="text" class="form-control" name="stage_name"
                                            value="<?php echo htmlspecialchars($artist['stage_name']); ?>" required
                                            id="inp-stage" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="ae-form-label">Nome real</label>
                                        <input type="text" class="form-control" name="real_name"
                                            value="<?php echo htmlspecialchars($artist['real_name'] ?? ''); ?>"
                                            id="inp-real" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="ae-form-label">Género principal</label>
                                        <input type="text" class="form-control" name="genre_main"
                                            value="<?php echo htmlspecialchars($artist['genre_main'] ?? ''); ?>" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="ae-form-label">Género secundário</label>
                                        <input type="text" class="form-control" name="genre_secondary"
                                            value="<?php echo htmlspecialchars($artist['genre_secondary'] ?? ''); ?>" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="ae-form-label">País</label>
                                        <input type="text" class="form-control" name="country"
                                            value="<?php echo htmlspecialchars($artist['country'] ?? ''); ?>" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="ae-form-label">Cidade</label>
                                        <input type="text" class="form-control" name="city"
                                            value="<?php echo htmlspecialchars($artist['city'] ?? ''); ?>" />
                                    </div>
                                    <div class="col-12">
                                        <label class="ae-form-label">Biografia</label>
                                        <textarea class="form-control" name="bio"
                                            rows="4"><?php echo htmlspecialchars($artist['bio'] ?? ''); ?></textarea>
                                        <div class="ae-hint">Breve descrição sobre o artista.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Redes Sociais -->
                            <div class="ae-card">
                                <div class="ae-card-title"><i class="bi bi-share"></i> Redes Sociais e Links</div>
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="ae-form-label">Facebook</label><input type="url"
                                            class="form-control" name="facebook_url"
                                            value="<?php echo htmlspecialchars($artist['facebook_url'] ?? ''); ?>"
                                            placeholder="https://facebook.com/..." /></div>
                                    <div class="col-md-6"><label class="ae-form-label">Instagram</label><input
                                            type="url" class="form-control" name="instagram_url"
                                            value="<?php echo htmlspecialchars($artist['instagram_url'] ?? ''); ?>"
                                            placeholder="https://instagram.com/..." /></div>
                                    <div class="col-md-6"><label class="ae-form-label">YouTube</label><input type="url"
                                            class="form-control" name="youtube_url"
                                            value="<?php echo htmlspecialchars($artist['youtube_url'] ?? ''); ?>"
                                            placeholder="https://youtube.com/..." /></div>
                                    <div class="col-md-6"><label class="ae-form-label">Spotify</label><input type="url"
                                            class="form-control" name="spotify_url"
                                            value="<?php echo htmlspecialchars($artist['spotify_url'] ?? ''); ?>"
                                            placeholder="https://open.spotify.com/artist/..." /></div>
                                    <div class="col-md-6"><label class="ae-form-label">Apple Music</label><input
                                            type="url" class="form-control" name="apple_music_url"
                                            value="<?php echo htmlspecialchars($artist['apple_music_url'] ?? ''); ?>"
                                            placeholder="https://music.apple.com/..." /></div>
                                    <div class="col-md-6"><label class="ae-form-label">TikTok</label><input type="url"
                                            class="form-control" name="tiktok_url"
                                            value="<?php echo htmlspecialchars($artist['tiktok_url'] ?? ''); ?>"
                                            placeholder="https://tiktok.com/@..." /></div>
                                    <div class="col-12"><label class="ae-form-label">Website</label><input type="url"
                                            class="form-control" name="website_url"
                                            value="<?php echo htmlspecialchars($artist['website_url'] ?? ''); ?>"
                                            placeholder="https://..." /></div>
                                </div>
                            </div>

                            <!-- Foto e Estado -->
                            <div class="ae-card">
                                <div class="ae-card-title"><i class="bi bi-image"></i> Foto de Perfil</div>
                                <div class="row g-3 align-items-center">
                                    <div class="col-auto">
                                        <?php if (!empty($artist['photo_artist'])): ?>
                                        <img src="<?php echo APP_URL; ?>/assets/comprovantes/uploads/artists/<?php echo htmlspecialchars($artist['photo_artist']); ?>"
                                            id="photo-preview"
                                            style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,0,137,.3)"
                                            alt=""
                                            onerror="this.style.display='none';document.getElementById('photo-ini').style.display='flex'" />
                                        <div id="photo-ini"
                                            style="background:<?php echo $avatar_color; ?>;width:64px;height:64px;border-radius:50%;display:none;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:1.2rem">
                                            <?php echo $ini($fullname); ?></div>
                                        <?php else: ?>
                                        <div id="photo-ini"
                                            style="background:<?php echo $avatar_color; ?>;width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:1.2rem">
                                            <?php echo $ini($fullname); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col">
                                        <label class="ae-form-label">URL externa da foto</label>
                                        <input type="url" class="form-control" name="photo_artist"
                                            value="<?php echo htmlspecialchars($artist['photo_artist'] ?? ''); ?>"
                                            placeholder="https://exemplo.com/foto.jpg" id="photo-url-input" />
                                        <div class="ae-hint">Cole o URL de uma imagem pública. Deixa em branco para
                                            remover.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="ae-card">
                                <div class="ae-card-title"><i class="bi bi-toggles"></i> Estado</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="ae-form-label">Estado da conta</label>
                                        <select class="form-select" name="status_artist">
                                            <?php foreach (['active' => 'Activo', 'inactive' => 'Inactivo', 'blocked' => 'Bloqueado', 'processing' => 'Processando'] as $v => $l): ?>
                                            <option value="<?php echo $v; ?>"
                                                <?php echo $artist['status_artist'] === $v ? 'selected' : ''; ?>>
                                                <?php echo $l; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/view?id=<?php echo $id; ?>"
                                    class="btn btn-outline-secondary">Cancelar</a>
                                <button type="submit" class="btn text-white" id="btn-save"
                                    style="background:#FF0089;border-color:#FF0089;min-width:130px">
                                    <span id="btn-save-text"><i class="bi bi-check-lg me-1"></i> Guardar
                                        Alterações</span>
                                    <span id="btn-save-spin" class="d-none"><span
                                            class="spinner-border spinner-border-sm me-1"></span> A guardar...</span>
                                </button>
                            </div>
                        </form>
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
        // Preview live
        document.getElementById('inp-stage')?.addEventListener('input', function() {
            const preview = document.getElementById('preview-name');
            if (preview) preview.textContent = this.value || '';
        });
        document.getElementById('inp-real')?.addEventListener('input', function() {
            const preview = document.getElementById('preview-real');
            if (preview) preview.textContent = this.value || '';
        });
        document.getElementById('photo-url-input')?.addEventListener('input', function() {
            const url = this.value.trim();
            const preview = document.getElementById('photo-preview');
            const ini = document.getElementById('photo-ini');
            if (url) {
                if (preview) {
                    preview.src = url;
                    preview.style.display = '';
                }
                if (ini) ini.style.display = 'none';
            } else {
                if (preview) preview.style.display = 'none';
                if (ini) ini.style.display = 'flex';
            }
        });
        // Spinner submit
        document.getElementById('form-profile')?.addEventListener('submit', function() {
            document.getElementById('btn-save-text').classList.add('d-none');
            document.getElementById('btn-save-spin').classList.remove('d-none');
            document.getElementById('btn-save').disabled = true;
        });
    })();
    </script>
</body>

</html>