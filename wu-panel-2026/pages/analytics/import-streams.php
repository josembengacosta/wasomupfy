<?php
// ═══════════════════════════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Admin: Importação de Dados de Streaming
// Arquivo: wu-panel-2026/pages/analytics/import-streams.php
// Rota:    wu-panel-2026/analytics/import-streams
// ═══════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/../../include/platform_admin.php';
requirePermission($admin_id, 'analytics.edit');

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$db = getDB();
$msg = $_GET['msg'] ?? null;
$feedback = match ($msg) {
    'imported' => ['success', 'bi-check-circle', 'Dados importados com sucesso.'],
    'error'    => ['danger', 'bi-exclamation-triangle', 'Erro ao processar o ficheiro. Verifique o formato.'],
    default    => null,
};

// ── Estatísticas rápidas ─────────────────────────────────────────────────
$total_stream_records = (int)$db->query("SELECT COUNT(*) FROM _stream")->fetchColumn();
$total_country_records = (int)$db->query("SELECT COUNT(*) FROM _stream_country")->fetchColumn();
$pending_imports = 0; // Pode ser expandido com uma tabela de histórico

// ── Obter faixas, lojas e países para os selects manuais ─────────────────
$tracks = $db->query("SELECT t.id_track, t.title_track, u.first_name FROM _track t JOIN _users u ON t.id_users = u.id_users ORDER BY t.title_track LIMIT 500")->fetchAll();
$stores = $db->query("SELECT id_store, name_store FROM _store WHERE is_active=1 ORDER BY name_store")->fetchAll();
$countries = $db->query("SELECT DISTINCT country_code, country_name FROM _stream_country WHERE country_code IS NOT NULL ORDER BY country_name")->fetchAll();

$csrf = $_SESSION['admin_csrf_token'];

// Lista de países com código ISO (baseada no country_meta usado em country-details.php)
$country_meta = [
    'Angola'              => 'ao',
    'Brasil'              => 'br',
    'Brazil'              => 'br',
    'Portugal'            => 'pt',
    'USA'                 => 'us',
    'United States'       => 'us',
    'Cabo Verde'          => 'cv',
    'Cape Verde'          => 'cv',
    'Moçambique'          => 'mz',
    'Mozambique'          => 'mz',
    'São Tomé e Príncipe' => 'st',
    'Guiné-Bissau'        => 'gw',
    'Timor-Leste'         => 'tl',
    'Namíbia'             => 'na',
    'Namibia'             => 'na',
    'Congo'               => 'cd',
    'South Africa'        => 'za',
    'África do Sul'       => 'za',
    'Nigeria'             => 'ng',
    'Nigéria'             => 'ng',
    'Ghana'               => 'gh',
    'Kenya'               => 'ke',
    'France'              => 'fr',
    'França'              => 'fr',
    'United Kingdom'      => 'gb',
    'Reino Unido'         => 'gb',
    'Germany'             => 'de',
    'Alemanha'            => 'de',
    'Spain'               => 'es',
    'Espanha'             => 'es',
    'Canada'              => 'ca',
    'Canadá'              => 'ca',
    // Adicione outros conforme necessário
];

$countries = [];
foreach ($country_meta as $name => $iso) {
    $countries[] = ['country_code' => $iso, 'country_name' => $name];
}
// Remove duplicados (ex: Brasil e Brazil)
$countries = array_unique($countries, SORT_REGULAR);
usort($countries, fn($a, $b) => $a['country_name'] <=> $b['country_name']);
?>
<!DOCTYPE html>
<html lang="pt-ao">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf); ?>">
    <meta name="theme-color" content="#FF0089" />
    <title>Importar Streams — Wasom Upfy Admin</title>
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/img/icones/wasomupfy_fiv.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/libs/plugins.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/lastest-style.css" />
    <style>
    .an-stat {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e8e8f0);
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .an-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
    }

    .an-stat-val {
        font-size: 1.25rem;
        font-weight: 800;
        line-height: 1;
    }

    .an-stat-lbl {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .5px;
        opacity: .6;
        margin-top: 2px;
    }

    .card-custom {
        border-radius: 14px;
        border: 1px solid var(--border-color, #e8e8f0);
        background: var(--card-bg, #fff);
    }

    .upload-area {
        border: 2px dashed #ccc;
        border-radius: 12px;
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: background .2s;
    }

    .upload-area:hover {
        background: rgba(255, 0, 137, 0.02);
        border-color: #FF0089;
    }

    .mapping-table th {
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .mapping-table td {
        font-size: .8rem;
    }

    .preview-table {
        max-height: 300px;
        overflow: auto;
        font-size: .75rem;
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
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="welcome-text col-auto">
                        <h2 class="h4 mb-1"><i class="bi bi-cloud-upload me-2"></i>Importação de Dados de Streaming</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo APP_URL . '/' . ADMIN_PATH; ?>">Home</a>
                                </li>
                                <li class="breadcrumb-item"><a href="stores">Lojas</a></li>
                                <li class="breadcrumb-item active">Importar Streams</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="stores" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-shop"></i> Ver
                            Lojas</a>
                        <button class="btn btn-sm text-white" style="background:#FF0089" id="manualAddBtn"><i
                                class="bi bi-plus-lg"></i> Adicionar Manual</button>
                    </div>
                </div>

                <?php if ($feedback): ?>
                <div class="alert alert-<?php echo $feedback[0]; ?> alert-dismissible fade show mb-3">
                    <i class="bi <?php echo $feedback[1]; ?> me-2"></i><?php echo htmlspecialchars($feedback[2]); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Cards de resumo -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="an-stat">
                            <div class="an-stat-icon" style="background:#FF008922"><i class="bi bi-database"
                                    style="color:#FF0089"></i></div>
                            <div>
                                <div class="an-stat-val"><?php echo number_format($total_stream_records); ?></div>
                                <div class="an-stat-lbl">Registos em Streamg</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="an-stat">
                            <div class="an-stat-icon" style="background:#3b82f622"><i class="bi bi-globe2"
                                    style="color:#3b82f6"></i></div>
                            <div>
                                <div class="an-stat-val"><?php echo number_format($total_country_records); ?></div>
                                <div class="an-stat-lbl">Registos em Streamg em Paises</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="an-stat">
                            <div class="an-stat-icon" style="background:#22c55e22"><i class="bi bi-cloud-check"
                                    style="color:#22c55e"></i></div>
                            <div>
                                <div class="an-stat-val">0</div>
                                <div class="an-stat-lbl">Importações pendentes</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Abas: Importação CSV e Adição Manual -->
                <ul class="nav nav-tabs mb-3" id="importTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="csv-tab" data-bs-toggle="tab" data-bs-target="#csvImport"
                            type="button" role="tab"><i class="bi bi-filetype-csv me-1"></i>Importação CSV</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manualAdd"
                            type="button" role="tab"><i class="bi bi-pencil-square me-1"></i>Adição Manual</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- ABA CSV -->
                    <div class="tab-pane fade show active" id="csvImport" role="tabpanel">
                        <div class="card-custom p-4">
                            <h5 class="mb-3"><i class="bi bi-upload me-2"></i>Carregar ficheiro CSV de relatório</h5>
                            <p class="text-muted small">Formatos suportados: relatórios de streaming por país (Spotify,
                                Apple, etc.). Mapeie as colunas após o upload.</p>

                            <form id="csvUploadForm" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <div class="upload-area" id="dropArea">
                                    <i class="bi bi-cloud-arrow-up fs-1 text-muted"></i>
                                    <p class="mt-2 mb-1 fw-semibold">Arraste um ficheiro CSV ou clique para selecionar
                                    </p>
                                    <p class="small text-muted">Tamanho máximo: 20MB</p>
                                    <input type="file" name="csv_file" id="csvFileInput" accept=".csv,text/csv"
                                        style="display:none;">
                                </div>
                                <div id="fileInfo" class="mt-2 d-none">
                                    <span class="badge bg-light text-dark me-2" id="fileName"></span>
                                    <span class="text-muted small" id="fileSize"></span>
                                </div>

                                <!-- Seção de mapeamento (aparece após upload) -->
                                <div id="mappingSection" class="mt-4 d-none">
                                    <h6 class="mb-3">Mapeamento de colunas</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Tipo de dados</label>
                                            <select class="form-select form-select-sm" id="dataTypeSelect">
                                                <option value="country">Streams por País (streaming por países)</option>
                                                <option value="store">Streams por Loja (streaming por lojas)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Ano</label>
                                            <select class="form-select form-select-sm" id="yearSelect">
                                                <?php for($y=date('Y'); $y>=2020; $y--): ?>
                                                <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <table class="table mapping-table mt-3">
                                        <thead>
                                            <tr>
                                                <th>Coluna CSV</th>
                                                <th>Campo destino</th>
                                                <th>Exemplo</th>
                                            </tr>
                                        </thead>
                                        <tbody id="mappingRows"></tbody>
                                    </table>

                                    <div class="preview-table border rounded p-2 bg-light mb-3">
                                        <strong class="small">Pré-visualização (primeiras 5 linhas):</strong>
                                        <div id="csvPreview" class="mt-2"></div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-outline-secondary"
                                            id="cancelMappingBtn">Cancelar</button>
                                        <button type="button" class="btn text-white" style="background:#FF0089"
                                            id="importBtn" disabled><i class="bi bi-database"></i> Importar
                                            Dados</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ABA Manual -->
                    <div class="tab-pane fade" id="manualAdd" role="tabpanel">
                        <div class="card-custom p-4">
                            <h5 class="mb-3"><i class="bi bi-plus-circle me-2"></i>Adicionar registo de stream
                                manualmente</h5>
                            <form id="manualStreamForm">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Faixa <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" name="id_track" id="manualTrack"
                                            required>
                                            <option value="">Selecione uma faixa</option>
                                            <?php foreach($tracks as $t): ?>
                                            <option value="<?php echo $t['id_track']; ?>">
                                                <?php echo htmlspecialchars($t['title_track'] . ' (' . $t['first_name'] . ')'); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Ano</label>
                                        <select class="form-select form-select-sm" name="year_stream" required>
                                            <?php for($y=date('Y'); $y>=2020; $y--): ?>
                                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Mês</label>
                                        <select class="form-select form-select-sm" name="month_stream" required>
                                            <?php for($m=1; $m<=12; $m++): ?>
                                            <option value="<?php echo $m; ?>"><?php echo $m; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Tipo de registo</label>
                                        <select class="form-select form-select-sm" id="manualType">
                                            <option value="store">Streams por Loja (streaming por lojas)</option>
                                            <option value="country">Streams por País (streaming por países)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="storeSelectWrapper">
                                        <label class="form-label small fw-bold">Loja</label>
                                        <select class="form-select form-select-sm" name="id_store">
                                            <option value="">Selecione uma loja</option>
                                            <?php foreach($stores as $s): ?>
                                            <option value="<?php echo $s['id_store']; ?>">
                                                <?php echo htmlspecialchars($s['name_store']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 d-none" id="countrySelectWrapper">
                                        <label class="form-label small fw-bold">País <span
                                                class="text-danger">*</span></label>
                                        <input list="countriesDatalist" name="country_name" id="countryNameInput"
                                            class="form-control form-control-sm"
                                            placeholder="Digite ou selecione o país" autocomplete="off">
                                        <datalist id="countriesDatalist">
                                            <?php foreach ($countries as $c): ?>
                                            <option value="<?php echo htmlspecialchars($c['country_name']); ?>"
                                                data-code="<?php echo $c['country_code']; ?>">
                                                <?php endforeach; ?>
                                        </datalist>
                                        <input type="hidden" name="country_code" id="countryCodeHidden">
                                        <small class="text-muted">Comece a digitar para encontrar o país.</small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Streams</label>
                                        <input type="number" class="form-control form-control-sm" name="streams"
                                            value="0" min="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Downloads</label>
                                        <input type="number" class="form-control form-control-sm" name="downloads"
                                            value="0" min="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Receita (USD)</label>
                                        <input type="number" step="0.0001" class="form-control form-control-sm"
                                            name="revenue" value="0.0000">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Senha Admin <span
                                                class="text-danger">*</span></label>
                                        <input type="password" class="form-control form-control-sm"
                                            name="password_confirm" required>
                                    </div>
                                </div>
                                <div class="mt-3 text-end">
                                    <button type="submit" class="btn text-white" style="background:#FF0089"><i
                                            class="bi bi-save"></i> Adicionar Registo</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de progresso da importação -->
    <div class="modal fade" id="importProgressModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="spinner-border text-pink mb-3" style="color:#FF0089"></div>
                    <h6>Importando dados...</h6>
                    <p class="small text-muted mb-0" id="progressText">Processando registos</p>
                </div>
            </div>
        </div>
    </div>
    <footer>
        <div class="container">
            <div class="col-12 text-center py-2" style="font-size:.8rem">
                <p class="mb-0">© <?php echo date('Y'); ?> Wasom Upfy. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <img src="<?php echo APP_URL; ?>/assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="" />
            <div class="loader-progress"></div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo APP_URL; ?>/js/lastest.js"></script>
    <script>
    (function() {
        const BASE_URL = '<?php echo APP_URL; ?>';
        const ADMIN_PATH = '<?php echo ADMIN_PATH; ?>';
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const PROCESS_URL = BASE_URL + '/' + ADMIN_PATH + '/analytics/process-import';
        const MAX_CSV_SIZE = 20 * 1024 * 1024;

        // Elementos CSV
        const dropArea = document.getElementById('dropArea');
        const fileInput = document.getElementById('csvFileInput');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const mappingSection = document.getElementById('mappingSection');
        const mappingRows = document.getElementById('mappingRows');
        const importBtn = document.getElementById('importBtn');
        const importBtnLabel = importBtn.innerHTML;
        const csvPreview = document.getElementById('csvPreview');
        const dataTypeSelect = document.getElementById('dataTypeSelect');
        const yearSelect = document.getElementById('yearSelect');
        const cancelMappingBtn = document.getElementById('cancelMappingBtn');

        const manualForm = document.getElementById('manualStreamForm');
        const manualTypeSelect = document.getElementById('manualType');
        const manualAddBtn = document.getElementById('manualAddBtn');
        const manualSubmitBtn = manualForm.querySelector('button[type="submit"]');
        const manualSubmitLabel = manualSubmitBtn.innerHTML;
        const storeWrapper = document.getElementById('storeSelectWrapper');
        const countryWrapper = document.getElementById('countrySelectWrapper');
        const storeSelect = storeWrapper.querySelector('select[name="id_store"]');
        const countrySelect = countryWrapper.querySelector('select[name="country_code"]');
        const countryNameInput = countryWrapper.querySelector('input[name="country_name"]');

        let csvData = null;
        let csvHeaders = [];

        dropArea.addEventListener('click', () => fileInput.click());
        dropArea.addEventListener('dragover', e => {
            e.preventDefault();
            dropArea.classList.add('border-primary');
        });
        dropArea.addEventListener('dragleave', () => dropArea.classList.remove('border-primary'));
        dropArea.addEventListener('drop', e => {
            e.preventDefault();
            dropArea.classList.remove('border-primary');
            const file = e.dataTransfer.files[0];
            if (file) handleFile(file);
        });
        fileInput.addEventListener('change', () => {
            if (fileInput.files[0]) handleFile(fileInput.files[0]);
        });
        dataTypeSelect.addEventListener('change', () => {
            if (csvHeaders.length) buildMappingTable();
        });
        cancelMappingBtn.addEventListener('click', () => {
            resetCsvState();
            fileInput.value = '';
            fileInfo.classList.add('d-none');
        });

        function handleFile(file) {
            resetCsvState();

            if (!/\.csv$/i.test(file.name)) {
                showToast('warning', 'Ficheiro invalido', 'Selecione um ficheiro CSV.');
                return;
            }
            if (file.size > MAX_CSV_SIZE) {
                showToast('warning', 'Ficheiro muito grande', 'O CSV nao pode ultrapassar 20MB.');
                return;
            }

            fileName.textContent = file.name;
            fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
            fileInfo.classList.remove('d-none');

            const reader = new FileReader();
            reader.onload = event => {
                parseCSV(String(event.target?.result || ''));
            };
            reader.onerror = () => {
                showToast('error', 'Erro', 'Nao foi possivel ler o ficheiro.');
            };
            reader.readAsText(file, 'UTF-8');
        }

        function parseCSV(content) {
            const normalized = String(content || '').replace(/^\uFEFF/, '');
            const lines = normalized.split(/\r?\n/).filter(line => line.trim() !== '');

            if (lines.length < 2) {
                resetCsvState();
                showToast('warning', 'CSV vazio', 'O ficheiro nao contem dados suficientes.');
                return;
            }

            const delimiter = detectDelimiter(lines[0]);
            const headers = parseCsvLine(lines[0], delimiter).map(cleanCsvCell);
            const rows = lines.slice(1)
                .map(line => parseCsvLine(line, delimiter).map(cleanCsvCell))
                .filter(row => row.some(cell => cell !== ''));

            if (!headers.length || !rows.length) {
                resetCsvState();
                showToast('warning', 'CSV vazio', 'O ficheiro nao contem linhas validas para importar.');
                return;
            }

            csvHeaders = headers;
            csvData = rows;

            let previewHtml = '<table class="table table-sm small"><thead><tr>';
            csvHeaders.forEach(header => previewHtml += `<th>${escapeHtml(header)}</th>`);
            previewHtml += '</tr></thead><tbody>';
            rows.slice(0, 5).forEach(row => {
                previewHtml += '<tr>';
                csvHeaders.forEach((_, index) => {
                    previewHtml += `<td>${escapeHtml(row[index] || '')}</td>`;
                });
                previewHtml += '</tr>';
            });
            previewHtml += '</tbody></table>';
            csvPreview.innerHTML = previewHtml;

            buildMappingTable();
            mappingSection.classList.remove('d-none');
            importBtn.disabled = false;
        }

        function detectDelimiter(headerLine) {
            return parseCsvLine(headerLine, ';').length > parseCsvLine(headerLine, ',').length ? ';' : ',';
        }

        function resetCsvState() {
            csvData = null;
            csvHeaders = [];
            mappingRows.innerHTML = '';
            csvPreview.innerHTML = '';
            mappingSection.classList.add('d-none');
            importBtn.disabled = true;
        }

        function parseCsvLine(line, delimiter) {
            const cells = [];
            let current = '';
            let inQuotes = false;

            for (let i = 0; i < line.length; i++) {
                const char = line[i];

                if (char === '"') {
                    if (inQuotes && line[i + 1] === '"') {
                        current += '"';
                        i++;
                    } else {
                        inQuotes = !inQuotes;
                    }
                    continue;
                }

                if (char === delimiter && !inQuotes) {
                    cells.push(current);
                    current = '';
                    continue;
                }

                current += char;
            }

            cells.push(current);
            return cells;
        }

        function cleanCsvCell(value) {
            return String(value ?? '').replace(/^\uFEFF/, '').trim();
        }

        function buildMappingTable() {
            const dataType = dataTypeSelect.value;
            const fields = dataType === 'country' ? ['id_track', 'country_code', 'country_name', 'streams',
                'revenue', 'year_stream',
                'month_stream'
            ] : ['id_track', 'id_store', 'streams', 'downloads', 'revenue', 'year_stream',
                'month_stream'
            ];

            let html = '';
            fields.forEach(field => {
                html +=
                    `<tr><td><select class="form-select form-select-sm map-select" data-field="${field}"><option value="">-- Ignorar --</option>`;
                csvHeaders.forEach((header, index) => html +=
                    `<option value="${index}">${escapeHtml(header)}</option>`);
                html +=
                    `</select></td><td><code>${field}</code></td><td class="text-muted small">ex: ${getFieldExample(field)}</td></tr>`;
            });
            mappingRows.innerHTML = html;

            const normalizedHeaders = csvHeaders.map(header => normalizeHeader(header));
            document.querySelectorAll('.map-select').forEach(select => {
                const matchIndex = guessMappingIndex(select.dataset.field, normalizedHeaders);
                if (matchIndex >= 0) {
                    select.value = String(matchIndex);
                }
            });
        }

        function guessMappingIndex(field, normalizedHeaders) {
            const aliases = {
                id_track: ['id track', 'track id', 'track', 'isrc', 'song id', 'music id'],
                id_store: ['id store', 'store id', 'store', 'loja', 'platform'],
                country_code: ['country code', 'codigo pais', 'country iso', 'iso2'],
                country_name: ['country name', 'pais', 'country'],
                streams: ['streams', 'stream count', 'plays', 'reproducoes'],
                downloads: ['downloads', 'download count'],
                revenue: ['revenue', 'royalty', 'earnings', 'receita'],
                year_stream: ['year', 'ano', 'report year'],
                month_stream: ['month', 'mes', 'report month']
            };

            const candidates = aliases[field] || [field.replace(/_/g, ' ')];
            return normalizedHeaders.findIndex(header => candidates.some(candidate => header.includes(candidate)));
        }

        function normalizeHeader(header) {
            return String(header || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, ' ')
                .trim();
        }

        function getFieldExample(field) {
            return {
                id_track: '123 ou ISRC12345678',
                country_code: 'AO',
                country_name: 'Angola',
                streams: '1500',
                revenue: '2.50',
                id_store: '1 ou Spotify',
                downloads: '10',
                year_stream: '2025',
                month_stream: '3'
            } [field] || '';
        }

        // Importar CSV
        importBtn.addEventListener('click', async () => {
            if (!Array.isArray(csvData) || !csvData.length) {
                showToast('warning', 'CSV vazio', 'Carregue um ficheiro com dados antes de importar.');
                return;
            }

            const dataType = dataTypeSelect.value;
            const mappings = {};
            document.querySelectorAll('.map-select').forEach(sel => {
                if (sel.value !== '') mappings[sel.dataset.field] = Number.parseInt(sel.value,
                    10);
            });

            if (!hasMapping(mappings, 'id_track')) {
                showToast('warning', 'Mapeamento obrigatorio', 'A coluna id_track deve ser mapeada.');
                return;
            }
            if (dataType === 'country' && !hasMapping(mappings, 'country_code')) {
                showToast('warning', 'Mapeamento obrigatorio',
                    'A coluna country_code deve ser mapeada.');
                return;
            }
            if (dataType === 'store' && !hasMapping(mappings, 'id_store')) {
                showToast('warning', 'Mapeamento obrigatorio', 'A coluna id_store deve ser mapeada.');
                return;
            }

            const importModal = new bootstrap.Modal(document.getElementById('importProgressModal'));
            importModal.show();
            document.getElementById('progressText').textContent = 'Enviando dados...';
            setButtonBusy(importBtn, true,
                '<span class="spinner-border spinner-border-sm me-2"></span>Importando...',
                importBtnLabel);

            const payload = new FormData();
            payload.set('action', 'import_csv');
            payload.set('csrf_token', CSRF);
            payload.set('data_type', dataType);
            payload.set('year', yearSelect.value);
            payload.set('mappings', JSON.stringify(mappings));
            payload.set('csv_data', JSON.stringify(csvData));

            try {
                const json = await requestJson(PROCESS_URL, {
                    method: 'POST',
                    body: payload
                });

                importModal.hide();

                if (!json.ok) {
                    showToast('error', 'Erro', json.message || 'Nao foi possivel importar o ficheiro.');
                    return;
                }

                const summary = json.skipped > 0 ?
                    `${json.imported} registos importados e ${json.skipped} ignorados.` :
                    `${json.imported} registos importados.`;

                showToast('success', 'Importacao concluida', summary);
                window.setTimeout(() => {
                    location.href = location.pathname + '?msg=imported';
                }, 700);
            } catch (error) {
                importModal.hide();
                console.error(error);
                showToast('error', 'Erro', error.message || 'Falha na comunicacao.');
            } finally {
                setButtonBusy(importBtn, false, '', importBtnLabel);
            }
        });

        // Adição Manual
        manualTypeSelect.addEventListener('change', syncManualType);
        manualAddBtn.addEventListener('click', () => {
            document.getElementById('manual-tab').click();
        });
        countrySelect.addEventListener('change', () => {
            if (!countryNameInput.value.trim() && countrySelect.value) {
                countryNameInput.value = countrySelect.options[countrySelect.selectedIndex].text.trim();
            }
        });

        manualForm.addEventListener('submit', async e => {
            e.preventDefault();

            const formData = new FormData(manualForm);
            const dataType = manualTypeSelect.value;
            formData.set('action', 'manual_add');
            formData.set('csrf_token', CSRF);
            formData.set('data_type', dataType);

            if (!String(formData.get('id_track') || '').trim()) {
                showToast('warning', 'Campo obrigatorio', 'Selecione uma faixa.');
                return;
            }
            if (dataType === 'store' && !String(formData.get('id_store') || '').trim()) {
                showToast('warning', 'Campo obrigatorio', 'Selecione uma loja.');
                return;
            }
            if (dataType === 'country' && !String(formData.get('country_code') || '').trim()) {
                showToast('warning', 'Campo obrigatorio', 'Selecione um pais.');
                return;
            }
            if (!String(formData.get('password_confirm') || '').trim()) {
                showToast('warning', 'Campo obrigatorio', 'Informe a senha do admin.');
                return;
            }

            setButtonBusy(manualSubmitBtn, true,
                '<span class="spinner-border spinner-border-sm me-2"></span>A guardar...',
                manualSubmitLabel);

            try {
                const json = await requestJson(PROCESS_URL, {
                    method: 'POST',
                    body: formData
                });

                if (!json.ok) {
                    showToast('error', 'Erro', json.message || 'Nao foi possivel guardar o registo.');
                    return;
                }

                showToast('success', 'Registo adicionado', json.message ||
                    'Dados inseridos com sucesso.');
                manualForm.reset();
                syncManualType();
            } catch (error) {
                console.error(error);
                showToast('error', 'Erro', error.message || 'Falha na comunicacao.');
            } finally {
                setButtonBusy(manualSubmitBtn, false, '', manualSubmitLabel);
            }
        });

        syncManualType();

        // Helpers
        function syncManualType() {
            const isStore = manualTypeSelect.value === 'store';
            storeWrapper.classList.toggle('d-none', !isStore);
            countryWrapper.classList.toggle('d-none', isStore);
            storeSelect.required = isStore;
            countrySelect.required = !isStore;

            if (isStore) {
                countrySelect.value = '';
                countryNameInput.value = '';
            } else {
                storeSelect.value = '';
                if (!countryNameInput.value.trim() && countrySelect.value) {
                    countryNameInput.value = countrySelect.options[countrySelect.selectedIndex].text.trim();
                }
            }
        }

        async function requestJson(url, options = {}) {
            const headers = new Headers(options.headers || {});
            headers.set('X-Requested-With', 'XMLHttpRequest');

            const response = await fetch(url, {
                ...options,
                headers
            });

            const rawText = await response.text();
            let data = null;

            if (rawText) {
                try {
                    data = JSON.parse(rawText);
                } catch (error) {
                    const message = rawText.trim().startsWith('<') ?
                        `Resposta invalida do servidor (${response.status}).` :
                        rawText.trim();
                    throw new Error(message || 'O servidor devolveu um formato inesperado.');
                }
            }

            if (!response.ok) {
                throw new Error((data && data.message) || `Erro HTTP ${response.status}.`);
            }

            if (!data || typeof data !== 'object') {
                throw new Error('Resposta vazia do servidor.');
            }

            return data;
        }

        function setButtonBusy(button, busy, busyLabel, idleLabel) {
            button.disabled = busy;
            button.innerHTML = busy ? busyLabel : idleLabel;
        }

        function hasMapping(mappings, field) {
            return Object.prototype.hasOwnProperty.call(mappings, field);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>]/g, m => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;'
            })[m]);
        }

        function showToast(type, title, msg) {
            const container = document.querySelector('.toast-container');
            const id = 'toast-' + Date.now();
            const bg = type === 'success' ? 'bg-success' : (type === 'warning' ? 'bg-warning' : 'bg-danger');
            const html =
                `<div id="${id}" class="toast align-items-center text-white ${bg} border-0" data-bs-autohide="true"><div class="d-flex"><div class="toast-body"><strong>${escapeHtml(title)}</strong><br>${escapeHtml(msg)}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`;
            container.insertAdjacentHTML('beforeend', html);
            new bootstrap.Toast(document.getElementById(id)).show();
        }
    })();

    // Sincronizar código do país quando um item do datalist for selecionado
    const countryNameInput = document.getElementById('countryNameInput');
    const countryCodeHidden = document.getElementById('countryCodeHidden');
    const datalistOptions = document.querySelectorAll('#countriesDatalist option');

    countryNameInput.addEventListener('input', function() {
        const val = this.value;
        let found = false;
        datalistOptions.forEach(opt => {
            if (opt.value === val) {
                countryCodeHidden.value = opt.dataset.code;
                found = true;
            }
        });
        if (!found) countryCodeHidden.value = ''; // Limpa se não corresponder exatamente
    });
    </script>
</body>

</html>