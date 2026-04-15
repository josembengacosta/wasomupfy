<?php
// ════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Admin: Gestão de FAQs
// Arquivo: wu-panel-2026/pages/faq/faq.php
// ════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'content.edit'); // ajuste conforme sua permissão

$db = getDB();
$csrf = $_SESSION['admin_csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['admin_csrf_token'] = $csrf;

// Categorias disponíveis para o select
$categories = ['Geral', 'Conta', 'Pagamentos', 'Distribuição', 'Royalties', 'Lançamentos', 'Artistas', 'Problemas Técnicos'];
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
    <title>Gestão de FAQs — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css">
    <style>
    .faq-answer-preview {
        max-width: 300px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .modal-backdrop {
        background-color: rgba(0, 0, 0, 0.5);
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
                    <div class="col">
                        <h2 class="h4 mb-1"><i class="bi bi-question-circle me-2"></i>Gestão de FAQs</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>">Home</a>
                                </li>
                                <li class="breadcrumb-item active">FAQs</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-pink" id="btnAddFaq">
                            <i class="bi bi-plus-lg me-1"></i> Nova FAQ
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="faqTable" class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Categoria</th>
                                        <th>Pergunta</th>
                                        <th>Resposta</th>
                                        <th>Ordem</th>
                                        <th>Status</th>
                                        <th style="width:120px">Ações</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar/Editar FAQ -->
    <div class="modal fade" id="faqModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background:#FF0089">
                    <h5 class="modal-title text-white" id="modalTitle">
                        <i class="bi bi-question-circle me-2"></i>Nova FAQ
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="faqForm">
                    <input type="hidden" name="action" value="save_faq">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="id_faq" id="faqId">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Categoria</label>
                                <select class="form-select" name="category_faq" id="faqCategory" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>">
                                        <?php echo htmlspecialchars($cat); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Ordem</label>
                                <input type="number" class="form-control" name="display_order" id="faqOrder" value="0"
                                    min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Status</label>
                                <select class="form-select" name="status_faq" id="faqStatus">
                                    <option value="visible">Visível</option>
                                    <option value="hidden">Oculto</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Pergunta <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="question" id="faqQuestion" maxlength="500"
                                    required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Resposta <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="answer" id="faqAnswer" rows="6"
                                    required></textarea>
                                <small class="text-muted">Podes usar HTML básico (links, negrito, etc.).</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-pink" id="saveBtn">
                            <i class="bi bi-save me-1"></i>Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
        const csrf = $('meta[name="csrf-token"]').attr('content');
        let table = $('#faqTable').DataTable({
            ajax: {
                url: 'faq_process',
                type: 'POST',
                data: {
                    action: 'list_faqs',
                    csrf_token: csrf
                }
            },
            columns: [{
                    data: 'id_faq'
                },
                {
                    data: 'category_faq'
                },
                {
                    data: 'question'
                },
                {
                    data: 'answer',
                    render: data =>
                        `<div class="faq-answer-preview">${data.replace(/<[^>]*>/g, ' ').substring(0, 80)}…</div>`
                },
                {
                    data: 'display_order'
                },
                {
                    data: 'status_faq',
                    render: data => data === 'visible' ?
                        '<span class="badge bg-success">Visível</span>' :
                        '<span class="badge bg-secondary">Oculto</span>'
                },
                {
                    data: null,
                    orderable: false,
                    render: row => `
                    <button class="btn btn-sm btn-outline-secondary edit-btn" data-id="${row.id_faq}" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${row.id_faq}" data-question="${row.question.replace(/"/g, '&quot;')}" title="Eliminar">
                        <i class="bi bi-trash3"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning toggle-btn" data-id="${row.id_faq}" data-status="${row.status_faq}" title="Alternar visibilidade">
                        <i class="bi bi-eye${row.status_faq === 'visible' ? '-slash' : ''}"></i>
                    </button>
                `
                }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-PT.json'
            },
            order: [
                [0, 'desc']
            ]
        });

        // Abrir modal para nova FAQ
        $('#btnAddFaq').click(() => {
            $('#faqForm')[0].reset();
            $('#faqId').val('');
            $('#modalTitle').html('<i class="bi bi-plus-circle me-2"></i>Nova FAQ');
            $('#faqModal').modal('show');
        });

        // Editar
        $('#faqTable').on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            $.post('faq_process', {
                action: 'get_faq',
                id_faq: id,
                csrf_token: csrf
            }, response => {
                if (response.ok) {
                    const faq = response.faq;
                    $('#faqId').val(faq.id_faq);
                    $('#faqCategory').val(faq.category_faq);
                    $('#faqOrder').val(faq.display_order);
                    $('#faqStatus').val(faq.status_faq);
                    $('#faqQuestion').val(faq.question);
                    $('#faqAnswer').val(faq.answer);
                    $('#modalTitle').html('<i class="bi bi-pencil-square me-2"></i>Editar FAQ');
                    $('#faqModal').modal('show');
                } else {
                    Swal.fire('Erro', response.message, 'error');
                }
            }, 'json');
        });

        // Submeter formulário
        $('#faqForm').submit(e => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.set('csrf_token', csrf);

            $.ajax({
                url: 'faq_process',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: response => {
                    if (response.ok) {
                        $('#faqModal').modal('hide');
                        Swal.fire('Sucesso', response.message, 'success');
                        table.ajax.reload();
                    } else {
                        Swal.fire('Erro', response.message, 'error');
                    }
                },
                error: () => Swal.fire('Erro', 'Falha na comunicação.', 'error')
            });
        });

        // Eliminar
        $('#faqTable').on('click', '.delete-btn', function() {
            const id = $(this).data('id');
            const question = $(this).data('question');
            Swal.fire({
                title: 'Eliminar FAQ?',
                html: `Tem certeza que deseja eliminar permanentemente:<br><strong>${question}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Sim, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    $.post('faq_process', {
                        action: 'delete_faq',
                        id_faq: id,
                        csrf_token: csrf
                    }, response => {
                        if (response.ok) {
                            Swal.fire('Eliminado!', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Erro', response.message, 'error');
                        }
                    }, 'json');
                }
            });
        });

        // Alternar visibilidade
        $('#faqTable').on('click', '.toggle-btn', function() {
            const id = $(this).data('id');
            const currentStatus = $(this).data('status');
            const newStatus = currentStatus === 'visible' ? 'hidden' : 'visible';
            $.post('faq_process', {
                action: 'toggle_faq',
                id_faq: id,
                status: newStatus,
                csrf_token: csrf
            }, response => {
                if (response.ok) {
                    table.ajax.reload();
                } else {
                    Swal.fire('Erro', response.message, 'error');
                }
            }, 'json');
        });
    });
    </script>
</body>

</html>