// reports-performance.js - Desempenho por Lojas Digitais

// 1. VARIÁVEIS GLOBAIS E ESTADO DA APLICAÇÃO
// ============================================
let currentWizardStep = 1;
const totalWizardSteps = 4;
let selectedReportType = null;
let selectedReportFormat = 'excel';
let selectedVisualization = 'table';
let currentReportFilters = {};
let scheduledReports = [];

// Dados de exemplo (em um sistema real, viriam de uma API)
const reportTypes = [
    { id: 'financial', name: 'Financeiro', icon: 'bi-cash-stack', color: '#1cc88a', description: 'Receita, royalties e projeções financeiras' },
    { id: 'performance', name: 'Desempenho', icon: 'bi-graph-up', color: '#4e73df', description: 'Métricas de streams e downloads' },
    { id: 'analytics', name: 'Analítico', icon: 'bi-bar-chart', color: '#f6c23e', description: 'Análises detalhadas e tendências' },
    { id: 'custom', name: 'Personalizado', icon: 'bi-sliders', color: '#FF0089', description: 'Configure todos os parâmetros' }
];

const reportTemplates = [
    { id: 1, name: 'Relatório Financeiro Mensal', type: 'financial', popularity: 85, lastUsed: '2026-01-20' },
    { id: 2, name: 'Performance por Artista', type: 'performance', popularity: 72, lastUsed: '2026-01-19' },
    { id: 3, name: 'Análise de Audiência', type: 'analytics', popularity: 68, lastUsed: '2026-01-18' },
    { id: 4, name: 'Royalties Detalhados', type: 'financial', popularity: 54, lastUsed: '2026-01-15' }
];

const reportHistory = [
    { id: 1, name: 'Relatório Financeiro Jan/2026', type: 'financial', date: '2026-01-20 14:30', status: 'success', format: 'pdf', size: '2.4 MB' },
    { id: 2, name: 'Análise de Performance', type: 'performance', date: '2026-01-19 11:15', status: 'processing', format: 'excel', size: '1.8 MB' },
    { id: 3, name: 'Relatório de Audiência', type: 'analytics', date: '2026-01-18 09:45', status: 'error', format: 'csv', size: '0.9 MB' }
];

// 2. FUNÇÕES DO WIZARD (ASSISTENTE)
// ============================================

// Mostra/oculta o wizard
function showReportWizard() {
    document.getElementById('wizardStart').style.display = 'none';
    document.getElementById('reportWizard').style.display = 'block';
    loadReportTypes();
    updateProgressBar();
}

function cancelWizard() {
    document.getElementById('wizardStart').style.display = 'block';
    document.getElementById('reportWizard').style.display = 'none';
    resetWizard();
}

function resetWizard() {
    currentWizardStep = 1;
    selectedReportType = null;
    selectedReportFormat = 'excel';
    selectedVisualization = 'table';
    currentReportFilters = {};
    
    // Reset visual das seleções
    document.querySelectorAll('.report-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    document.querySelectorAll('.export-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Volta para o primeiro passo
    showStep(1);
    updateProgressBar();
    updateReportSummary();
}

// Navegação entre passos
function nextStep(step) {
    // Validação antes de avançar
    if (step === 2 && !selectedReportType) {
        showAlert('Por favor, selecione um tipo de relatório antes de continuar.', 'warning');
        return;
    }
    
    showStep(step);
    updateProgressBar();
    
    // Carrega conteúdo específico do passo
    if (step === 2) {
        loadReportParameters();
    } else if (step === 4) {
        updateReportSummary();
    }
}

function prevStep(step) {
    showStep(step);
    updateProgressBar();
}

function showStep(step) {
    // Oculta todos os passos
    document.querySelectorAll('.wizard-step').forEach(stepElement => {
        stepElement.classList.remove('active');
    });
    
    // Mostra o passo atual
    const currentStep = document.getElementById(`step${step}`);
    if (currentStep) {
        currentStep.classList.add('active');
        currentWizardStep = step;
    }
}

function updateProgressBar() {
    const progress = ((currentWizardStep - 1) / (totalWizardSteps - 1)) * 100;
    const progressBar = document.querySelector('.wizard-progress-bar');
    if (progressBar) {
        progressBar.style.width = `${progress}%`;
    }
}

// 3. FUNÇÕES DE SELEÇÃO E CONFIGURAÇÃO
// ============================================

// Tipos de relatório
function loadReportTypes() {
    const container = document.getElementById('reportTypes');
    if (!container) return;
    
    container.innerHTML = '';
    
    reportTypes.forEach(type => {
        const card = document.createElement('div');
        card.className = 'col-md-3';
        card.innerHTML = `
            <div class="report-card" onclick="selectReportType('${type.id}')" id="reportType-${type.id}">
                <div class="report-icon" style="background: linear-gradient(135deg, ${type.color}, ${darkenColor(type.color, 20)});">
                    <i class="bi ${type.icon}"></i>
                </div>
                <h6>${type.name}</h6>
                <p class="small text-muted">${type.description}</p>
            </div>
        `;
        container.appendChild(card);
    });
}

function selectReportType(typeId) {
    selectedReportType = typeId;
    
    // Atualiza seleção visual
    document.querySelectorAll('.report-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    const selectedCard = document.getElementById(`reportType-${typeId}`);
    if (selectedCard) {
        selectedCard.classList.add('selected');
    }
    
    // Atualiza pré-visualização
    updateParameterPreview();
}

// Parâmetros do relatório
function loadReportParameters() {
    const container = document.getElementById('parametersContainer');
    if (!container || !selectedReportType) return;
    
    // Limpa container
    container.innerHTML = '';
    
    // Adiciona parâmetros com base no tipo de relatório
    const params = getParametersForReportType(selectedReportType);
    
    params.forEach(param => {
        const paramGroup = document.createElement('div');
        paramGroup.className = 'filter-group mb-3';
        paramGroup.innerHTML = `
            <h6 class="mb-2">${param.label}</h6>
            ${param.input}
        `;
        container.appendChild(paramGroup);
    });
    
    updateParameterPreview();
}

function getParametersForReportType(type) {
    const baseParams = [
        {
            label: 'Período',
            input: `
                <select class="form-select" id="param-period" onchange="updateParameterPreview()">
                    <option value="last7">Últimos 7 dias</option>
                    <option value="last30">Últimos 30 dias</option>
                    <option value="month">Este mês</option>
                    <option value="lastMonth">Mês anterior</option>
                    <option value="year">Este ano</option>
                    <option value="custom">Personalizado</option>
                </select>
            `
        },
        {
            label: 'Artistas',
            input: `
                <select class="form-select" id="param-artists" multiple onchange="updateParameterPreview()">
                    <option value="all">Todos os artistas</option>
                    <option value="artist1">Artista 1</option>
                    <option value="artist2">Artista 2</option>
                    <option value="artist3">Artista 3</option>
                </select>
            `
        }
    ];
    
    if (type === 'financial') {
        baseParams.push({
            label: 'Tipo de Receita',
            input: `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="revenue-streaming" checked onchange="updateParameterPreview()">
                    <label class="form-check-label" for="revenue-streaming">Streaming</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="revenue-downloads" checked onchange="updateParameterPreview()">
                    <label class="form-check-label" for="revenue-downloads">Downloads</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="revenue-royalties" onchange="updateParameterPreview()">
                    <label class="form-check-label" for="revenue-royalties">Royalties</label>
                </div>
            `
        });
    }
    
    if (type === 'performance') {
        baseParams.push({
            label: 'Métricas',
            input: `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="metric-streams" checked onchange="updateParameterPreview()">
                    <label class="form-check-label" for="metric-streams">Total de Streams</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="metric-downloads" checked onchange="updateParameterPreview()">
                    <label class="form-check-label" for="metric-downloads">Downloads</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="metric-listeners" onchange="updateParameterPreview()">
                    <label class="form-check-label" for="metric-listeners">Ouvintes Únicos</label>
                </div>
            `
        });
    }
    
    return baseParams;
}

function updateParameterPreview() {
    const preview = document.getElementById('parameterPreview');
    if (!preview) return;
    
    if (!selectedReportType) {
        preview.innerHTML = '<p class="text-muted">Selecione um tipo de relatório para visualizar os parâmetros disponíveis.</p>';
        return;
    }
    
    // Coleta valores atuais dos parâmetros
    const period = document.getElementById('param-period')?.value || 'last7';
    const artistsSelect = document.getElementById('param-artists');
    const selectedArtists = artistsSelect ? Array.from(artistsSelect.selectedOptions).map(opt => opt.value) : ['all'];
    
    // Atualiza objeto de filtros
    currentReportFilters = {
        period: period,
        artists: selectedArtists,
        reportType: selectedReportType
    };
    
    // Gera pré-visualização
    const reportType = reportTypes.find(t => t.id === selectedReportType);
    preview.innerHTML = `
        <h6>Pré-visualização do Relatório</h6>
        <p><strong>Tipo:</strong> ${reportType?.name || 'Não selecionado'}</p>
        <p><strong>Período:</strong> ${getPeriodLabel(period)}</p>
        <p><strong>Artistas:</strong> ${selectedArtists.includes('all') ? 'Todos' : selectedArtists.length + ' selecionados'}</p>
        <div class="mt-3 p-2 bg-light rounded">
            <small class="text-muted">Esta é uma pré-visualização básica. O relatório final conterá gráficos e tabelas detalhadas.</small>
        </div>
    `;
}

function getPeriodLabel(periodValue) {
    const periods = {
        'last7': 'Últimos 7 dias',
        'last30': 'Últimos 30 dias',
        'month': 'Este mês',
        'lastMonth': 'Mês anterior',
        'year': 'Este ano',
        'custom': 'Período personalizado'
    };
    return periods[periodValue] || periodValue;
}

// Formato do relatório
function selectFormat(format) {
    selectedReportFormat = format;
    
    // Atualiza seleção visual
    document.querySelectorAll('#formatOptions .export-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    event.currentTarget.classList.add('selected');
    updateVisualizationPreview();
}

// Tipo de visualização
function selectVisualization(type) {
    selectedVisualization = type;
    
    // Atualiza seleção visual
    document.querySelectorAll('#visualizationOptions .export-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    event.currentTarget.classList.add('selected');
    updateVisualizationPreview();
}

function updateVisualizationPreview() {
    const preview = document.getElementById('visualizationPreview');
    if (!preview) return;
    
    let previewContent = '';
    
    switch (selectedVisualization) {
        case 'table':
            previewContent = `
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Artista</th>
                                <th>Streams</th>
                                <th>Receita</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Artista 1</td><td>125,430</td><td>R$ 2.845,00</td></tr>
                            <tr><td>Artista 2</td><td>98,760</td><td>R$ 1.987,50</td></tr>
                            <tr><td>Artista 3</td><td>87,210</td><td>R$ 1.543,20</td></tr>
                        </tbody>
                    </table>
                </div>
            `;
            break;
            
        case 'chart':
            previewContent = `
                <div class="chart-preview">
                    <canvas id="previewChartCanvas"></canvas>
                </div>
                <script>
                    // Renderiza gráfico de exemplo
                    setTimeout(() => {
                        const ctx = document.getElementById('previewChartCanvas').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: ['Jan', 'Fev', 'Mar', 'Abr'],
                                datasets: [{
                                    label: 'Receita (R$)',
                                    data: [3200, 4500, 3800, 5200],
                                    backgroundColor: '#4e73df'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false
                            }
                        });
                    }, 100);
                <\/script>
            `;
            break;
            
        case 'mixed':
            previewContent = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-preview mb-3">
                            <small>Gráfico de Barras</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr><td>Métrica 1</td><td>Valor 1</td></tr>
                                <tr><td>Métrica 2</td><td>Valor 2</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            break;
    }
    
    preview.innerHTML = previewContent;
}

// 4. FUNÇÕES DE RESUMO E GERAÇÃO
// ============================================

function updateReportSummary() {
    if (!selectedReportType) return;
    
    const reportType = reportTypes.find(t => t.id === selectedReportType);
    
    document.getElementById('summaryType').textContent = reportType?.name || '-';
    document.getElementById('summaryPeriod').textContent = getPeriodLabel(currentReportFilters.period || 'last7');
    document.getElementById('summaryFormat').textContent = selectedReportFormat.toUpperCase();
    
    // Filtros
    const filtersSummary = document.getElementById('summaryFilters');
    if (filtersSummary) {
        filtersSummary.innerHTML = `
            <span class="badge bg-secondary me-1">${currentReportFilters.artists?.includes('all') ? 'Todos artistas' : currentReportFilters.artists?.length + ' artistas'}</span>
            <span class="badge bg-secondary">${getPeriodLabel(currentReportFilters.period)}</span>
        `;
    }
    
    // Destino
    const destination = [];
    if (document.getElementById('sendEmail')?.checked) destination.push('E-mail');
    if (document.getElementById('saveDashboard')?.checked) destination.push('Painel');
    if (document.getElementById('downloadNow')?.checked) destination.push('Download');
    
    document.getElementById('summaryDestination').textContent = destination.join(', ') || '-';
}

function generateReport() {
    // Validação final
    if (!selectedReportType) {
        showAlert('Por favor, selecione um tipo de relatório.', 'warning');
        return;
    }
    
    // Simula processamento
    showAlert('Gerando relatório... Isso pode levar alguns segundos.', 'info');
    
    // Desabilita botão para evitar duplo clique
    const generateBtn = document.querySelector('#step4 .btn-wizard');
    const originalText = generateBtn.innerHTML;
    generateBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Gerando...';
    generateBtn.disabled = true;
    
    // Simula tempo de processamento
    setTimeout(() => {
        // Adiciona ao histórico
        const newReport = {
            id: Date.now(),
            name: `Relatório ${reportTypes.find(t => t.id === selectedReportType).name} - ${new Date().toLocaleDateString()}`,
            type: selectedReportType,
            date: new Date().toLocaleString(),
            status: 'success',
            format: selectedReportFormat,
            size: '1.5 MB'
        };
        
        reportHistory.unshift(newReport);
        
        // Atualiza interface
        showAlert('Relatório gerado com sucesso!', 'success');
        
        // Oferece download
        if (document.getElementById('downloadNow')?.checked) {
            simulateDownload(newReport);
        }
        
        // Redireciona para histórico
        document.getElementById('history-tab').click();
        loadReportHistory();
        
        // Reseta wizard
        cancelWizard();
        
        // Restaura botão
        generateBtn.innerHTML = originalText;
        generateBtn.disabled = false;
    }, 2000);
}

function simulateDownload(report) {
    const link = document.createElement('a');
    link.href = '#';
    link.download = `${report.name}.${report.format}`;
    link.click();
    
    showAlert(`Download iniciado: ${report.name}.${report.format}`, 'success');
}

// 5. FUNÇÕES DAS OUTRAS ABAS
// ============================================

// Aba: Modelos
function loadTemplates() {
    const container = document.getElementById('templatesList');
    if (!container) return;
    
    container.innerHTML = '';
    
    reportTemplates.forEach(template => {
        const typeInfo = reportTypes.find(t => t.id === template.type);
        const col = document.createElement('div');
        col.className = 'col-md-4 mb-3';
        col.innerHTML = `
            <div class="template-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6><i class="bi ${typeInfo.icon} me-2" style="color: ${typeInfo.color};"></i>${template.name}</h6>
                        <p class="small text-muted mb-1">Tipo: ${typeInfo.name}</p>
                    </div>
                    <span class="badge bg-primary">${template.popularity}% uso</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">Usado em: ${template.lastUsed}</small>
                    <button class="btn btn-sm btn-outline-primary" onclick="useTemplate(${template.id})">
                        <i class="bi bi-play-circle me-1"></i> Usar
                    </button>
                </div>
            </div>
        `;
        container.appendChild(col);
    });
}

function searchTemplates() {
    const searchTerm = document.getElementById('templateSearch').value.toLowerCase();
    const filteredTemplates = reportTemplates.filter(template =>
        template.name.toLowerCase().includes(searchTerm) ||
        template.type.toLowerCase().includes(searchTerm)
    );
    
    // Atualiza visualização (simplificado)
    if (searchTerm) {
        showAlert(`Encontrados ${filteredTemplates.length} modelos para "${searchTerm}"`, 'info');
    }
}

function useTemplate(templateId) {
    const template = reportTemplates.find(t => t.id === templateId);
    if (!template) return;
    
    // Preenche wizard com dados do template
    selectedReportType = template.type;
    showReportWizard();
    
    // Seleciona visualmente o tipo de relatório
    setTimeout(() => {
        const card = document.getElementById(`reportType-${template.type}`);
        if (card) {
            card.classList.add('selected');
        }
        
        showAlert(`Modelo "${template.name}" carregado. Continue configurando o relatório.`, 'success');
    }, 300);
}

function saveAsTemplate() {
    if (!selectedReportType) {
        showAlert('Crie um relatório primeiro para salvá-lo como modelo.', 'warning');
        document.getElementById('wizard-tab').click();
        return;
    }
    
    const name = prompt('Nome do modelo:');
    if (!name) return;
    
    const newTemplate = {
        id: reportTemplates.length + 1,
        name: name,
        type: selectedReportType,
        popularity: 0,
        lastUsed: new Date().toISOString().split('T')[0]
    };
    
    reportTemplates.unshift(newTemplate);
    showAlert('Modelo salvo com sucesso!', 'success');
    loadTemplates();
    document.getElementById('templates-tab').click();
}

// Aba: Histórico
function loadReportHistory() {
    const container = document.getElementById('reportsHistory');
    if (!container) return;
    
    // Aplica filtros
    const statusFilter = document.getElementById('historyFilter')?.value || 'all';
    const dateFilter = document.getElementById('historyDate')?.value || 'all';
    const typeFilter = document.getElementById('historyType')?.value || 'all';
    
    let filteredHistory = reportHistory;
    
    if (statusFilter !== 'all') {
        filteredHistory = filteredHistory.filter(report => report.status === statusFilter);
    }
    
    if (typeFilter !== 'all') {
        filteredHistory = filteredHistory.filter(report => report.type === typeFilter);
    }
    
    // Filtro por data (simplificado)
    if (dateFilter !== 'all') {
        const today = new Date();
        filteredHistory = filteredHistory.filter(report => {
            const reportDate = new Date(report.date);
            // Filtro simplificado - em um sistema real seria mais preciso
            return true; // Implementação completa dependeria da lógica de datas
        });
    }
    
    if (filteredHistory.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-clock-history fs-1 text-muted mb-3"></i>
                <h5 class="text-muted mb-2">Nenhum relatório encontrado</h5>
                <p class="text-muted">Tente ajustar os filtros ou crie um novo relatório.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = '';
    
    filteredHistory.forEach(report => {
        const typeInfo = reportTypes.find(t => t.id === report.type);
        const historyItem = document.createElement('div');
        historyItem.className = `history-item ${report.status}`;
        
        let statusBadge = '';
        if (report.status === 'success') statusBadge = '<span class="badge bg-success">Concluído</span>';
        if (report.status === 'processing') statusBadge = '<span class="badge bg-warning">Processando</span>';
        if (report.status === 'error') statusBadge = '<span class="badge bg-danger">Erro</span>';
        
        historyItem.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="mb-1">${report.name}</h6>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi ${typeInfo.icon} me-2" style="color: ${typeInfo.color};"></i>
                        <small class="text-muted me-3">${typeInfo.name}</small>
                        <small class="text-muted me-3">${report.date}</small>
                        <small class="text-muted">${report.format.toUpperCase()} • ${report.size}</small>
                    </div>
                    ${report.status === 'processing' ? 
                        '<div class="progress" style="height: 5px;"><div class="progress-bar bg-warning" style="width: 65%"></div></div>' : 
                        ''}
                </div>
                <div>
                    ${statusBadge}
                    <div class="btn-group btn-group-sm mt-2">
                        <button class="btn btn-outline-secondary" onclick="viewReport(${report.id})" ${report.status !== 'success' ? 'disabled' : ''}>
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-secondary" onclick="downloadReport(${report.id})" ${report.status !== 'success' ? 'disabled' : ''}>
                            <i class="bi bi-download"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteReport(${report.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(historyItem);
    });
}

function filterHistory() {
    loadReportHistory();
}

function viewReport(reportId) {
    const report = reportHistory.find(r => r.id === reportId);
    if (!report) return;
    
    const modalBody = document.getElementById('viewReportBody');
    if (modalBody) {
        modalBody.innerHTML = `
            <div class="text-center py-4">
                <i class="bi bi-file-earmark-pdf fs-1 text-danger mb-3"></i>
                <h5>${report.name}</h5>
                <p class="text-muted">Visualização do relatório (simulação)</p>
                <div class="border rounded p-4 mt-3">
                    <p>Este é um exemplo de como o relatório apareceria.</p>
                    <p>Em um sistema real, aqui estaria o conteúdo completo do relatório ${report.format.toUpperCase()}.</p>
                </div>
            </div>
        `;
    }
    
    // Abre modal
    const modal = new bootstrap.Modal(document.getElementById('viewReportModal'));
    modal.show();
}

function downloadReport(reportId) {
    const report = reportHistory.find(r => r.id === reportId);
    if (!report) return;
    
    simulateDownload(report);
}

function deleteReport(reportId) {
    if (confirm('Tem certeza que deseja excluir este relatório do histórico?')) {
        const index = reportHistory.findIndex(r => r.id === reportId);
        if (index !== -1) {
            reportHistory.splice(index, 1);
            loadReportHistory();
            showAlert('Relatório excluído do histórico.', 'success');
        }
    }
}

// Aba: Agendados
function loadScheduledReports() {
    const container = document.getElementById('scheduledReports');
    if (!container) return;
    
    if (scheduledReports.length === 0) {
        container.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <i class="bi bi-calendar-check fs-1 text-muted mb-3"></i>
                    <h5 class="text-muted mb-2">Nenhum relatório agendado</h5>
                    <p class="text-muted">Agende seu primeiro relatório automático.</p>
                </td>
            </tr>
        `;
        return;
    }
    
    container.innerHTML = '';
    
    scheduledReports.forEach(report => {
        const typeInfo = reportTypes.find(t => t.id === report.type);
        const row = document.createElement('tr');
        
        row.innerHTML = `
            <td>${report.name}</td>
            <td><i class="bi ${typeInfo.icon} me-1"></i> ${typeInfo.name}</td>
            <td><span class="badge bg-info">${report.frequency}</span></td>
            <td>${report.nextRun}</td>
            <td>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" ${report.active ? 'checked' : ''} 
                           onchange="toggleScheduledReport(${report.id})">
                </div>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-secondary" onclick="editScheduledReport(${report.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteScheduledReport(${report.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        container.appendChild(row);
    });
}

function scheduleNewReport() {
    if (!selectedReportType) {
        showAlert('Crie um relatório primeiro para agendá-lo.', 'warning');
        document.getElementById('wizard-tab').click();
        return;
    }
    
    showAlert('Use o assistente para configurar e agendar um relatório. Na etapa 4, marque "Agendar este relatório".', 'info');
    document.getElementById('wizard-tab').click();
    showReportWizard();
    nextStep(4);
    
    // Marca checkbox de agendamento
    setTimeout(() => {
        const scheduleCheckbox = document.getElementById('scheduleReport');
        if (scheduleCheckbox) {
            scheduleCheckbox.checked = true;
            scheduleCheckbox.dispatchEvent(new Event('change'));
        }
    }, 500);
}

function toggleScheduledReport(reportId) {
    const report = scheduledReports.find(r => r.id === reportId);
    if (report) {
        report.active = !report.active;
        showAlert(`Relatório ${report.active ? 'ativado' : 'desativado'} com sucesso.`, 'success');
    }
}

function editScheduledReport(reportId) {
    showAlert('Funcionalidade de edição em desenvolvimento.', 'info');
}

function deleteScheduledReport(reportId) {
    if (confirm('Tem certeza que deseja excluir este agendamento?')) {
        const index = scheduledReports.findIndex(r => r.id === reportId);
        if (index !== -1) {
            scheduledReports.splice(index, 1);
            loadScheduledReports();
            showAlert('Agendamento excluído.', 'success');
        }
    }
}

// 6. FUNÇÕES UTILITÁRIAS
// ============================================

function showAlert(message, type = 'info') {
    // Remove alertas anteriores
    const existingAlert = document.querySelector('.custom-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Cria novo alerta
    const alert = document.createElement('div');
    alert.className = `custom-alert alert alert-${type} alert-dismissible fade show`;
    alert.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    // Remove automaticamente após 5 segundos
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

function darkenColor(color, percent) {
    // Função simples para escurecer uma cor
    const num = parseInt(color.replace('#', ''), 16);
    const amt = Math.round(2.55 * percent);
    const R = (num >> 16) - amt;
    const G = (num >> 8 & 0x00FF) - amt;
    const B = (num & 0x0000FF) - amt;
    
    return '#' + (
        0x1000000 +
        (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
        (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
        (B < 255 ? B < 1 ? 0 : B : 255)
    ).toString(16).slice(1);
}

function downloadCurrentReport() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('viewReportModal'));
    if (modal) {
        modal.hide();
    }
    
    showAlert('Iniciando download do relatório...', 'success');
}

// 7. INICIALIZAÇÃO
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Página de Relatórios Personalizados carregada');
    
    // Inicializa componentes
    loadReportTypes();
    loadTemplates();
    loadReportHistory();
    loadScheduledReports();
    
    // Configura listeners
    const scheduleCheckbox = document.getElementById('scheduleReport');
    if (scheduleCheckbox) {
        scheduleCheckbox.addEventListener('change', function() {
            const options = document.getElementById('scheduleOptions');
            if (options) {
                options.style.display = this.checked ? 'block' : 'none';
            }
        });
    }
    
    // Adiciona alguns relatórios agendados de exemplo
    scheduledReports = [
        {
            id: 1,
            name: 'Relatório Financeiro Semanal',
            type: 'financial',
            frequency: 'Semanal',
            nextRun: '25/01/2026 09:00',
            active: true
        },
        {
            id: 2,
            name: 'Performance Mensal',
            type: 'performance',
            frequency: 'Mensal',
            nextRun: '01/02/2026 10:00',
            active: true
        }
    ];
    
    // Atualiza lista de agendados
    loadScheduledReports();
    
    // Configura tooltips do Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// 8. FUNÇÕES ADICIONAIS PARA O HTML
// ============================================

// Estas funções são chamadas diretamente do HTML
function searchTemplates() {
    const searchTerm = document.getElementById('templateSearch').value;
    if (searchTerm) {
        showAlert(`Buscando por: "${searchTerm}"`, 'info');
        // A implementação real de busca está na função searchTemplates() acima
    }
}