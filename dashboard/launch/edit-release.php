<?php
// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Editar Lançamento
// Arquivo: dashboard/launch/edit-release.php
// ══════════════════════════════════════════════
require_once __DIR__ . '/../../authentic/include/functions.php';
startSecureSession();
checkRememberMe();
requireLogin();

$user = getUserById((int)$_SESSION['id_users']);
if (!$user) {
    session_destroy();
    redirect('/login', ['error' => 'csrf']);
}

$id_users = (int)$user['id_users'];
$db = getDB();

// Verificar se foi passado um ID
$id_album = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id_album) {
    redirect('/dashboard/launch/releases', ['error' => 'invalid_release']);
}

// Buscar dados do álbum (verificar se pertence ao utilizador)
$album_stmt = $db->prepare("
    SELECT * FROM _album 
    WHERE id_album = ? AND id_users = ?
");
$album_stmt->execute([$id_album, $id_users]);
$album = $album_stmt->fetch(PDO::FETCH_ASSOC);

if (!$album) {
    redirect('/dashboard/launch/releases', ['error' => 'release_not_found']);
}

// Buscar artista principal
$artist_stmt = $db->prepare("
    SELECT id_artist, stage_name, real_name, photo_artist 
    FROM _artist WHERE id_artist = ?
");
$artist_stmt->execute([$album['id_artist']]);
$main_artist = $artist_stmt->fetch(PDO::FETCH_ASSOC);

// Buscar todos os artistas do utilizador (para o select)
$artists_stmt = $db->prepare("
    SELECT id_artist, stage_name, real_name, photo_artist 
    FROM _artist WHERE id_users = ? AND status_artist = 'active' 
    ORDER BY stage_name
");
$artists_stmt->execute([$id_users]);
$user_artists = $artists_stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar faixas do álbum
$tracks_stmt = $db->prepare("
    SELECT * FROM _track WHERE id_album = ? ORDER BY track_number
");
$tracks_stmt->execute([$id_album]);
$tracks = $tracks_stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as stores
$stores_stmt = $db->query("
    SELECT id_store, name_store, slug_store, logo_store 
    FROM _store WHERE is_active = 1 ORDER BY display_order
");
$stores = $stores_stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar stores selecionadas para este álbum
$selected_stmt = $db->prepare("
    SELECT id_store FROM _album_store WHERE id_album = ?
");
$selected_stmt->execute([$id_album]);
$selected_stores = $selected_stmt->fetchAll(PDO::FETCH_COLUMN);

// Buscar plano do utilizador
$plan_stmt = $db->prepare("SELECT * FROM _plans WHERE id_plan = ?");
$plan_stmt->execute([$user['plan_selected']]);
$plan = $plan_stmt->fetch(PDO::FETCH_ASSOC);

$plan_slug = $plan['slug_plan'] ?? 'single';
$max_tracks = $plan['max_tracks_per_release'] ?? null;
$can_label = ($plan_slug !== 'single');
$ui_max_tracks = $max_tracks ?? ($plan_slug === 'label' ? 50 : 30);

$csrf = htmlspecialchars($_SESSION['csrf_token']);

// Store icons map
$store_icons = [
    'spotify'       => ['icon' => 'bi-spotify',      'color' => '#1db954'],
    'apple-music'   => ['icon' => 'bi-apple',        'color' => '#fc3c44'],
    'amazon-music'  => ['icon' => 'bi-amazon',       'color' => '#ff9900'],
    'deezer'        => ['icon' => 'bi-music-note',   'color' => '#ef5466'],
    'tidal'         => ['icon' => 'bi-water',        'color' => '#00ffff'],
    'boomplay'      => ['icon' => 'bi-headphones',   'color' => '#f85d2f'],
    'youtube-music' => ['icon' => 'bi-youtube',      'color' => '#ff0000'],
    'itunes'        => ['icon' => 'bi-bag-music',    'color' => '#ea4cc0'],
    'pandora'       => ['icon' => 'bi-broadcast',    'color' => '#3668ff'],
    'resso'         => ['icon' => 'bi-music-player', 'color' => '#ff4040'],
    'claro-music'   => ['icon' => 'bi-music-note-beamed', 'color' => '#e30613'],
    'tiktok'        => ['icon' => 'bi-tiktok',       'color' => '#000000'],
    'facebook'      => ['icon' => 'bi-facebook',     'color' => '#1877f2'],
    'snapchat'      => ['icon' => 'bi-snapchat',     'color' => '#fffc00'],
    'youtube'       => ['icon' => 'bi-youtube',      'color' => '#ff0000'],
];

// Extrair ano dos copyrights (se existirem)
$copyright_year = '';
$phonogram_year = '';
if (!empty($album['copyright_c'])) {
    preg_match('/\d{4}/', $album['copyright_c'], $matches);
    $copyright_year = $matches[0] ?? date('Y');
}
if (!empty($album['copyright_p'])) {
    preg_match('/\d{4}/', $album['copyright_p'], $matches);
    $phonogram_year = $matches[0] ?? date('Y');
}
?>

<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <?php require_once __DIR__ . '/../include/head.php'; ?>
    <title>Editar Lançamento — <?php echo APP_NAME; ?></title>
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />

    <style>
    :root {
        --wasom: #FF0089;
        --wasom-dark: #cc006d;
    }

    .track-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 16px;
        position: relative;
        background: #fff;
    }

    .track-number {
        position: absolute;
        top: -10px;
        left: 16px;
        background: var(--wasom);
        color: white;
        padding: 2px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .store-card {
        border: 2px solid transparent;
        border-radius: 8px;
        padding: 12px 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        background: #f8f9fa;
    }

    .store-card:hover {
        border-color: var(--wasom);
        transform: translateY(-2px);
    }

    .store-card.selected {
        border-color: var(--wasom);
        background: rgba(255, 0, 137, 0.05);
    }

    .store-card i {
        font-size: 1.8rem;
        margin-bottom: 4px;
    }

    .cover-preview {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #dee2e6;
    }

    .dark-theme .track-card {
        background: #2d2d2d;
        border-color: #404040;
    }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="../painel">
                <span class="text-light" style="
              font-weight: bold;
              box-sizing: border-box;
              text-transform: uppercase;
              font-family: Arial, sans-serif;
            "><?php echo APP_NAME; ?></span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white-50 small">
                    <i class="bi bi-pencil me-1"></i>Editar Lançamento
                </span>
                <a href="releases" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Voltar
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4" style="max-width:900px">
        <div class="card p-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                    style="width:50px;height:50px;background:rgba(255,0,137,.1)">
                    <i class="bi bi-pencil-square fs-4" style="color:var(--wasom)"></i>
                </div>
                <div>
                    <h4 class="mb-0">Editar Lançamento</h4>
                    <p class="text-muted small mb-0">ID: #<?php echo $id_album; ?> • Criado em
                        <?php echo date('d/m/Y', strtotime($album['creat_album'])); ?></p>
                </div>
            </div>

            <form id="edit-form">
                <input type="hidden" name="action" value="edit_release">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="id_album" value="<?php echo $id_album; ?>">

                <!-- STEP 1: Informações Básicas -->
                <div class="mb-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"
                            style="color:var(--wasom)"></i>Informações Básicas</h5>

                    <div class="row">
                        <?php if (!empty($album['img_cover'])): ?>
                        <div class="col-md-2 mb-3">
                            <img src="/wasomupfy/assets/comprovantes/uploads/covers/<?php echo $album['img_cover']; ?>"
                                class="cover-preview" alt="Capa">
                        </div>
                        <div class="col-md-10">
                            <?php else: ?>
                            <div class="col-md-12">
                                <?php endif; ?>
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label fw-semibold">Título do Lançamento</label>
                                        <input type="text" class="form-control" name="title_album"
                                            value="<?php echo htmlspecialchars($album['title_album']); ?>"
                                            maxlength="150" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">Tipo</label>
                                        <select class="form-select" name="type_album" required>
                                            <option value="single"
                                                <?php echo $album['type_album'] == 'single' ? 'selected' : ''; ?>>Single
                                            </option>
                                            <option value="EP"
                                                <?php echo $album['type_album'] == 'EP' ? 'selected' : ''; ?>>EP
                                            </option>
                                            <option value="album"
                                                <?php echo $album['type_album'] == 'album' ? 'selected' : ''; ?>>Álbum
                                            </option>
                                            <option value="mixtape"
                                                <?php echo $album['type_album'] == 'mixtape' ? 'selected' : ''; ?>>
                                                Mixtape</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Língua Principal</label>
                                <select class="form-select" name="language">
                                    <option value="pt" <?php echo $album['language'] == 'pt' ? 'selected' : ''; ?>>🇦🇴
                                        Português (Angola)</option>
                                    <option value="pt-br"
                                        <?php echo $album['language'] == 'pt-br' ? 'selected' : ''; ?>>🇧🇷 Português
                                        (Brasil)</option>
                                    <option value="pt-pt"
                                        <?php echo $album['language'] == 'pt-pt' ? 'selected' : ''; ?>>🇵🇹 Português
                                        (Portugal)</option>
                                    <option value="en" <?php echo $album['language'] == 'en' ? 'selected' : ''; ?>>🇬🇧
                                        Inglês</option>
                                    <option value="es" <?php echo $album['language'] == 'es' ? 'selected' : ''; ?>>🇪🇸
                                        Espanhol</option>
                                    <option value="fr" <?php echo $album['language'] == 'fr' ? 'selected' : ''; ?>>🇫🇷
                                        Francês</option>
                                    <option value="ki" <?php echo $album['language'] == 'ki' ? 'selected' : ''; ?>>🇦🇴
                                        Kimbundo</option>
                                    <option value="kg" <?php echo $album['language'] == 'kg' ? 'selected' : ''; ?>>🇦🇴
                                        Kikongo</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Data de Lançamento</label>
                                <input type="date" class="form-control" name="release_date"
                                    value="<?php echo $album['release_date']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Créditos -->
                    <div class="mb-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-people me-2" style="color:var(--wasom)"></i>Créditos
                        </h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Artista Principal</label>
                                <select class="form-select" name="id_artist" required>
                                    <option value="">Seleciona um artista</option>
                                    <?php foreach ($user_artists as $artist): ?>
                                    <option value="<?php echo $artist['id_artist']; ?>"
                                        <?php echo $artist['id_artist'] == $album['id_artist'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($artist['stage_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nome do Artista (para exibição)</label>
                                <input type="text" class="form-control" name="name_author_band"
                                    value="<?php echo htmlspecialchars($album['name_author_band']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Género Principal</label>
                                <input type="text" class="form-control" name="genre_main"
                                    value="<?php echo htmlspecialchars($album['genre_main']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Subgénero</label>
                                <input type="text" class="form-control" name="genre_secondary"
                                    value="<?php echo htmlspecialchars($album['genre_secondary']); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Selo/Gravadora</label>
                            <input type="text" class="form-control" name="label_name"
                                value="<?php echo htmlspecialchars($album['label_name']); ?>">
                        </div>
                    </div>

                    <!-- STEP 3: Faixas -->
                    <div class="mb-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-music-note-list me-2"
                                style="color:var(--wasom)"></i>Faixas</h5>

                        <div id="tracks-container">
                            <?php foreach ($tracks as $index => $track): ?>
                            <div class="track-card">
                                <span class="track-number">Faixa <?php echo $index + 1; ?></span>

                                <input type="hidden" name="tracks[<?php echo $index; ?>][id_track]"
                                    value="<?php echo $track['id_track']; ?>">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Título</label>
                                        <input type="text" class="form-control form-control-sm"
                                            name="tracks[<?php echo $index; ?>][title_track]"
                                            value="<?php echo htmlspecialchars($track['title_track']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Versão do Mix</label>
                                        <input type="text" class="form-control form-control-sm"
                                            name="tracks[<?php echo $index; ?>][mix_version]"
                                            value="<?php echo htmlspecialchars($track['mix_version'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Artistas</label>
                                        <input type="text" class="form-control form-control-sm"
                                            name="tracks[<?php echo $index; ?>][name_author]"
                                            value="<?php echo htmlspecialchars($track['name_author']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Feat.</label>
                                        <input type="text" class="form-control form-control-sm"
                                            name="tracks[<?php echo $index; ?>][name_author_feat]"
                                            value="<?php echo htmlspecialchars($track['name_author_feat'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Compositores</label>
                                        <input type="text" class="form-control form-control-sm"
                                            name="tracks[<?php echo $index; ?>][name_composer]"
                                            value="<?php echo htmlspecialchars($track['name_composer'] ?? ''); ?>"
                                            required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Produtores</label>
                                        <input type="text" class="form-control form-control-sm"
                                            name="tracks[<?php echo $index; ?>][name_producer]"
                                            value="<?php echo htmlspecialchars($track['name_producer']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">ISRC</label>
                                        <input type="text" class="form-control form-control-sm"
                                            name="tracks[<?php echo $index; ?>][isrc]"
                                            value="<?php echo htmlspecialchars($track['isrc'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Língua</label>
                                        <select class="form-select form-select-sm"
                                            name="tracks[<?php echo $index; ?>][language]">
                                            <option value="pt"
                                                <?php echo ($track['language'] ?? '') == 'pt' ? 'selected' : ''; ?>>
                                                Português</option>
                                            <option value="en"
                                                <?php echo ($track['language'] ?? '') == 'en' ? 'selected' : ''; ?>>
                                                Inglês</option>
                                            <option value="es"
                                                <?php echo ($track['language'] ?? '') == 'es' ? 'selected' : ''; ?>>
                                                Espanhol</option>
                                            <option value="fr"
                                                <?php echo ($track['language'] ?? '') == 'fr' ? 'selected' : ''; ?>>
                                                Francês</option>
                                            <option value="ki"
                                                <?php echo ($track['language'] ?? '') == 'ki' ? 'selected' : ''; ?>>
                                                Kimbundo</option>
                                            <option value="kg"
                                                <?php echo ($track['language'] ?? '') == 'kg' ? 'selected' : ''; ?>>
                                                Kikongo</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Explícito?</label>
                                        <select class="form-select form-select-sm"
                                            name="tracks[<?php echo $index; ?>][explicit]">
                                            <option value="NO"
                                                <?php echo ($track['explicit'] ?? 'NO') == 'NO' ? 'selected' : ''; ?>>
                                                Não</option>
                                            <option value="YES"
                                                <?php echo ($track['explicit'] ?? 'NO') == 'YES' ? 'selected' : ''; ?>>
                                                Sim</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Duração (segundos)</label>
                                        <input type="number" class="form-control form-control-sm"
                                            name="tracks[<?php echo $index; ?>][duration_seconds]"
                                            value="<?php echo htmlspecialchars($track['duration_seconds'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- STEP 4: Distribuição -->
                    <div class="mb-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-calendar-event me-2"
                                style="color:var(--wasom)"></i>Distribuição</h5>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Plataformas de Distribuição</label>
                            <div class="row g-2" id="stores-grid">
                                <?php foreach ($stores as $store):
                                    $slug = $store['slug_store'];
                                    $icon = $store_icons[$slug] ?? ['icon' => 'bi-music-note', 'color' => '#888'];
                                    $selected = in_array($store['id_store'], $selected_stores);
                                ?>
                                <div class="col-4 col-md-3 col-lg-2">
                                    <div class="store-card <?php echo $selected ? 'selected' : ''; ?>"
                                        data-store-id="<?php echo $store['id_store']; ?>" onclick="toggleStore(this)">
                                        <i class="bi <?php echo $icon['icon']; ?>"
                                            style="color:<?php echo $icon['color']; ?>"></i>
                                        <div class="small"><?php echo htmlspecialchars($store['name_store']); ?></div>
                                        <input type="checkbox" class="store-checkbox d-none" name="stores[]"
                                            value="<?php echo $store['id_store']; ?>"
                                            <?php echo $selected ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 5: Copyright -->
                    <div class="mb-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-c-circle me-2" style="color:var(--wasom)"></i>Copyright
                        </h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">© Copyright</label>
                                <input type="text" class="form-control" name="copyright_c"
                                    value="<?php echo htmlspecialchars($album['copyright_c']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">℗ Copyright</label>
                                <input type="text" class="form-control" name="copyright_p"
                                    value="<?php echo htmlspecialchars($album['copyright_p']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="releases" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn" style="background:var(--wasom);color:#fff" id="btn-save">
                            <i class="bi bi-check-lg me-1"></i>Guardar Alterações
                        </button>
                    </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
    const CSRF = '<?php echo $csrf; ?>';
    const BASE_URL = '<?php echo (APP_URL . '/' . APP_URL_PANEL); ?>';

    toastr.options = {
        progressBar: true,
        closeButton: true,
        timeOut: 3000
    };

    // Toggle stores
    function toggleStore(card) {
        card.classList.toggle('selected');
        const checkbox = card.querySelector('.store-checkbox');
        checkbox.checked = card.classList.contains('selected');
    }

    // Submeter formulário
    document.getElementById('edit-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const btn = document.getElementById('btn-save');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A guardar...';
        btn.disabled = true;

        const formData = new FormData(this);

        try {
            const res = await fetch(BASE_URL + '/launch/creat_release_process', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (data.ok) {
                toastr.success('Lançamento atualizado com sucesso!');
                setTimeout(() => window.location.href = 'releases', 1500);
            } else {
                toastr.error(data.message || 'Erro ao atualizar');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        } catch (err) {
            toastr.error('Erro de ligação. Tenta novamente.');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
    </script>
</body>

</html>