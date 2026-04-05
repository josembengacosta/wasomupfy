<?php
// ══════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Editar Canal YouTube (Admin)
// Arquivo: wu-panel-2026/pages/integration/edit.php
// Rota:    wu-panel-2026/integration/edit?id=123
// ══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'music.edit');

$id = (int)($_GET['id'] ?? 0);
if (!$id) die('ID do canal não fornecido.');

$db = getDB();
$stmt = $db->prepare("SELECT * FROM _youtube_channel WHERE id_youtube = ?");
$stmt->execute([$id]);
$channel = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$channel) die('Canal não encontrado.');

$csrf = $_SESSION['admin_csrf_token'];
$base_url = APP_URL . '/' . ADMIN_PATH;
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
    <title>Editar Canal YouTube - Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css">
</head>

<body>
    <div class="wrapper">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php require_once __DIR__ . '/../../include/sidebar.php'; ?>
        <div class="content w-100" id="mainContent">
            <?php require_once __DIR__ . '/../../include/navbar.php'; ?>
            <div class="container-fluid p-0">
                <div class="d-flex align-items-center gap-3 mb-3 mt-2">
                    <a href="<?php echo $base_url; ?>/integration/verify" class="btn btn-sm btn-outline-secondary"><i
                            class="bi bi-arrow-left"></i> Voltar</a>
                    <h2 class="h4 mb-0">Editar Canal: <?php echo htmlspecialchars($channel['channel_name']); ?></h2>
                </div>
                <div class="card" style="border-radius:14px">
                    <div class="card-body">
                        <form id="editForm">
                            <input type="hidden" name="id_youtube" value="<?php echo $channel['id_youtube']; ?>">
                            <div class="mb-3"><label class="form-label">Nome do Canal *</label><input type="text"
                                    name="channel_name" class="form-control"
                                    value="<?php echo htmlspecialchars($channel['channel_name']); ?>" required></div>
                            <div class="mb-3"><label class="form-label">ID do Canal *</label><input type="text"
                                    name="channel_id" class="form-control"
                                    value="<?php echo htmlspecialchars($channel['channel_id']); ?>" required></div>
                            <div class="mb-3"><label class="form-label">URL do Canal</label><input type="url"
                                    name="channel_url" class="form-control"
                                    value="<?php echo htmlspecialchars($channel['channel_url']); ?>"></div>
                            <div class="mb-3"><label class="form-label">Código de Verificação</label><input type="text"
                                    name="verified_code" class="form-control"
                                    value="<?php echo htmlspecialchars($channel['verified_code']); ?>"></div>
                            <div class="mb-3"><label class="form-label">Estado</label>
                                <select name="status_youtube" class="form-select">
                                    <option value="pending"
                                        <?php echo $channel['status_youtube']==='pending'?'selected':''; ?>>Pendente
                                    </option>
                                    <option value="verified"
                                        <?php echo $channel['status_youtube']==='verified'?'selected':''; ?>>Verificado
                                    </option>
                                    <option value="rejected"
                                        <?php echo $channel['status_youtube']==='rejected'?'selected':''; ?>>Rejeitado
                                    </option>
                                    <option value="removed"
                                        <?php echo $channel['status_youtube']==='removed'?'selected':''; ?>>Removido
                                    </option>
                                </select>
                            </div>
                            <div class="mt-4"><button type="submit" class="btn text-white"
                                    style="background:#FF0089">Salvar Alterações</button> <a
                                    href="<?php echo $base_url; ?>/integration/verify"
                                    class="btn btn-outline-secondary">Cancelar</a></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    const BASE_URL = '<?php echo APP_URL; ?>';
    const ADMIN_PATH = '<?php echo ADMIN_PATH; ?>';
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    document.getElementById('editForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('csrf_token', CSRF);
        Swal.fire({
            title: 'A guardar...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        const r = await fetch(BASE_URL + '/' + ADMIN_PATH + '/integration/edit-process', {
            method: 'POST',
            body: fd
        });
        const data = await r.json();
        if (data.ok) Swal.fire({
            icon: 'success',
            title: 'Guardado!',
            text: data.message,
            confirmButtonColor: '#FF0089'
        }).then(() => window.location.href = BASE_URL + '/' + ADMIN_PATH + '/integration/verify');
        else Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: data.message
        });
    });
    </script>
</body>

</html>