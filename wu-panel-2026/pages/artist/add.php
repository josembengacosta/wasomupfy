<?php
// ═══════════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Adicionar Artista
// Arquivo: wu-panel-2026/pages/artist/add.php
// Rota:    wu-panel-2026/artist/add
// ═══════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'artists.edit');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'dupe_stage'      => ['danger', 'bi-x-circle', 'Já existe um artista com este nome artístico para este utilizador.'],
    'invalid_image'   => ['danger', 'bi-x-circle', 'Formato de imagem inválido. Use JPG, PNG ou WEBP.'],
    'image_too_large' => ['danger', 'bi-x-circle', 'A imagem excede o limite de 2 MB.'],
    'upload_failed'   => ['danger', 'bi-x-circle', 'Falha ao enviar a imagem. Tenta novamente.'],
    'error'           => ['danger', 'bi-x-circle', 'Ocorreu um erro. Tenta novamente.'],
    default           => null,
};

// Lista de utilizadores (para select)
$users = $db->query("
    SELECT id_users, CONCAT(first_name, ' ', COALESCE(second_name, '')) AS name, email_user
    FROM _users
    WHERE status_user = 'active'
    ORDER BY first_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title>Adicionar Artista — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
    .aa-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 14px;
        padding: 22px 24px;
        margin-bottom: 20px;
    }

    .aa-card-title {
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

    .aa-form-label {
        font-size: .78rem;
        font-weight: 600;
        margin-bottom: 5px;
        opacity: .7;
    }

    .aa-hint {
        font-size: .72rem;
        opacity: .45;
        margin-top: 3px;
    }

    .select2-container--default .select2-selection--single {
        background-color: var(--input-bg, #fff);
        border-color: var(--border-color, #ced4da);
        border-radius: 0.375rem;
        height: calc(2.25rem + 2px);
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 2.25rem;
        color: var(--text-color, #212529);
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 2.25rem;
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
                        <h2 class="h4 mb-1"><i class="bi bi-mic me-2"></i>Adicionar Artista</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>"
                                        class="text-secondary">Home</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist"
                                        class="text-secondary">Artistas</a></li>
                                <li class="breadcrumb-item active text-white-stable">Adicionar</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Voltar
                        </a>
                    </div>
                </div>

                <?php if ($feedback): ?>
                <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3">
                    <i class="bi <?php echo $feedback[1]; ?> me-2"></i>
                    <?php echo htmlspecialchars($feedback[2]); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist/add-process"
                    enctype="multipart/form-data" id="form-artist">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
                    <input type="hidden" name="action" value="add_artist">

                    <div class="row g-4">
                        <div class="col-lg-8">
                            <!-- Dados básicos -->
                            <div class="aa-card">
                                <div class="aa-card-title"><i class="bi bi-person"></i> Dados do Artista</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="aa-form-label">Nome artístico *</label>
                                        <input type="text" class="form-control" name="stage_name" required />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="aa-form-label">Nome real</label>
                                        <input type="text" class="form-control" name="real_name" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="aa-form-label">Género principal</label>
                                        <input type="text" class="form-control" name="genre_main" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="aa-form-label">Género secundário</label>
                                        <input type="text" class="form-control" name="genre_secondary" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="aa-form-label">País</label>
                                        <input type="text" class="form-control" name="country" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="aa-form-label">Cidade</label>
                                        <input type="text" class="form-control" name="city" />
                                    </div>
                                    <div class="col-12">
                                        <label class="aa-form-label">Biografia</label>
                                        <textarea class="form-control" name="bio" rows="4"></textarea>
                                        <div class="aa-hint">Breve descrição sobre o artista.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Redes Sociais -->
                            <div class="aa-card">
                                <div class="aa-card-title"><i class="bi bi-share"></i> Redes Sociais e Links</div>
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="aa-form-label">Facebook</label><input type="url"
                                            class="form-control" name="facebook_url"
                                            placeholder="https://facebook.com/..." /></div>
                                    <div class="col-md-6"><label class="aa-form-label">Instagram</label><input
                                            type="url" class="form-control" name="instagram_url"
                                            placeholder="https://instagram.com/..." /></div>
                                    <div class="col-md-6"><label class="aa-form-label">YouTube</label><input type="url"
                                            class="form-control" name="youtube_url"
                                            placeholder="https://youtube.com/..." /></div>
                                    <div class="col-md-6"><label class="aa-form-label">Spotify</label><input type="url"
                                            class="form-control" name="spotify_url"
                                            placeholder="https://open.spotify.com/artist/..." /></div>
                                    <div class="col-md-6"><label class="aa-form-label">Apple Music</label><input
                                            type="url" class="form-control" name="apple_music_url"
                                            placeholder="https://music.apple.com/..." /></div>
                                    <div class="col-md-6"><label class="aa-form-label">TikTok</label><input type="url"
                                            class="form-control" name="tiktok_url"
                                            placeholder="https://tiktok.com/@..." /></div>
                                    <div class="col-12"><label class="aa-form-label">Website</label><input type="url"
                                            class="form-control" name="website_url" placeholder="https://..." /></div>
                                </div>
                            </div>

                            <!-- Foto e Estado -->
                            <!-- Foto de Perfil -->
                            <div class="aa-card">
                                <div class="aa-card-title"><i class="bi bi-image"></i> Foto de Perfil</div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="aa-form-label">Fotografia do Artista</label>
                                        <input type="file" class="form-control" name="photo_artist"
                                            accept="image/jpeg,image/png,image/webp" />
                                        <div class="aa-hint">Formatos permitidos: JPG, PNG, WebP. Tamanho máximo: 5 MB.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="aa-card">
                                <div class="aa-card-title"><i class="bi bi-toggles"></i> Estado</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="aa-form-label">Estado da conta</label>
                                        <select class="form-select" name="status_artist">
                                            <option value="active" selected>Activo</option>
                                            <option value="inactive">Inactivo</option>
                                            <option value="blocked">Bloqueado</option>
                                            <option value="processing">Processando</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <!-- Proprietário -->
                            <div class="aa-card">
                                <div class="aa-card-title"><i class="bi bi-person-circle"></i> Proprietário</div>
                                <select class="form-select select2" name="id_users" required>
                                    <option value="">Selecionar utilizador</option>
                                    <?php foreach ($users as $u): ?>
                                    <option value="<?php echo $u['id_users']; ?>">
                                        <?php echo htmlspecialchars($u['name'] ?: $u['email_user']); ?>
                                        (<?php echo htmlspecialchars($u['email_user']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="aa-hint mt-2">O utilizador dono do artista.</div>
                            </div>

                            <!-- Ações -->
                            <div class="aa-card">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn text-white"
                                        style="background:#FF0089;border-color:#FF0089">
                                        <i class="bi bi-save"></i> Criar Artista
                                    </button>
                                    <a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>/artist"
                                        class="btn btn-outline-secondary">Cancelar</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Selecionar utilizador',
            allowClear: true
        });
    });
    </script>
</body>

</html>