// visitors.js - Sistema Completo de Gerenciamento de Visitantes

// Dados de exemplo de visitantes
const sampleVisitors = [
    {
        id: 1,
        ip: "192.168.1.100",
        userAgent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
        browser: "chrome",
        browserVersion: "120.0.0.0",
        os: "Windows",
        osVersion: "10",
        device: "desktop",
        deviceType: "Desktop",
        screenResolution: "1920x1080",
        language: "pt-BR",
        country: "AO",
        countryName: "Angola",
        city: "Luanda",
        region: "Luanda",
        isp: "Angola Telecom",
        latitude: -8.8383,
        longitude: 13.2344,
        timezone: "Africa/Luanda",
        firstVisit: "2024-01-10T08:30:00",
        lastVisit: "2024-01-15T14:25:30",
        visitCount: 12,
        pagesVisited: ["/home", "/pricing", "/features", "/contact"],
        sessionDuration: 1850,
        isBlocked: false,
        isSuspicious: false,
        isBot: false,
        status: "active",
        userId: null,
        referrer: "https://google.com",
        connectionType: "broadband",
        vpn: false,
        proxy: false,
        activities: [
            { time: "14:20", action: "Página visitada: /home" },
            { time: "14:22", action: "Página visitada: /pricing" },
            { time: "14:25", action: "Clique em botão: 'Saiba Mais'" }
        ]
    },
    {
        id: 2,
        ip: "105.179.92.15",
        userAgent: "Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15",
        browser: "safari",
        browserVersion: "16.6",
        os: "iOS",
        osVersion: "16.6",
        device: "mobile",
        deviceType: "iPhone 13",
        screenResolution: "1170x2532",
        language: "pt-PT",
        country: "PT",
        countryName: "Portugal",
        city: "Lisboa",
        region: "Lisboa",
        isp: "NOS Comunicações",
        latitude: 38.7167,
        longitude: -9.1333,
        timezone: "Europe/Lisbon",
        firstVisit: "2024-01-12T10:15:00",
        lastVisit: "2024-01-15T13:45:00",
        visitCount: 5,
        pagesVisited: ["/home", "/login", "/dashboard"],
        sessionDuration: 420,
        isBlocked: false,
        isSuspicious: true,
        isBot: false,
        status: "suspicious",
        userId: 501,
        referrer: "Direct",
        connectionType: "cellular",
        vpn: true,
        proxy: false,
        activities: [
            { time: "13:40", action: "Tentativa de login falhou" },
            { time: "13:42", action: "Tentativa de login falhou" },
            { time: "13:45", action: "IP marcado como suspeito" }
        ]
    },
    {
        id: 3,
        ip: "2001:0db8:85a3:0000:0000:8a2e:0370:7334",
        userAgent: "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
        browser: "bot",
        browserVersion: "2.1",
        os: "Bot",
        osVersion: "",
        device: "bot",
        deviceType: "Crawler",
        screenResolution: "N/A",
        language: "en-US",
        country: "US",
        countryName: "United States",
        city: "Mountain View",
        region: "California",
        isp: "Google LLC",
        latitude: 37.3861,
        longitude: -122.0839,
        timezone: "America/Los_Angeles",
        firstVisit: "2024-01-01T00:00:00",
        lastVisit: "2024-01-15T12:00:00",
        visitCount: 150,
        pagesVisited: ["/robots.txt", "/sitemap.xml", "/home", "/blog"],
        sessionDuration: 5,
        isBlocked: false,
        isSuspicious: false,
        isBot: true,
        status: "bot",
        userId: null,
        referrer: "",
        connectionType: "datacenter",
        vpn: false,
        proxy: false,
        activities: [
            { time: "12:00", action: "Bot crawl: /robots.txt" },
            { time: "12:01", action: "Bot crawl: /sitemap.xml" }
        ]
    },
    {
        id: 4,
        ip: "177.200.40.10",
        userAgent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/119.0",
        browser: "firefox",
        browserVersion: "119.0",
        os: "Windows",
        osVersion: "10",
        device: "desktop",
        deviceType: "Desktop",
        screenResolution: "1366x768",
        language: "pt-BR",
        country: "BR",
        countryName: "Brasil",
        city: "São Paulo",
        region: "SP",
        isp: "Vivo",
        latitude: -23.5505,
        longitude: -46.6333,
        timezone: "America/Sao_Paulo",
        firstVisit: "2024-01-14T09:20:00",
        lastVisit: "2024-01-15T15:30:00",
        visitCount: 3,
        pagesVisited: ["/home", "/about", "/contact"],
        sessionDuration: 650,
        isBlocked: true,
        isSuspicious: false,
        isBot: false,
        status: "blocked",
        userId: null,
        referrer: "https://facebook.com",
        connectionType: "broadband",
        vpn: false,
        proxy: true,
        activities: [
            { time: "15:25", action: "Bloqueado por atividade suspeita" },
            { time: "15:30", action: "Tentativa de acesso bloqueada" }
        ]
    },
    {
        id: 5,
        ip: "41.216.184.25",
        userAgent: "Mozilla/5.0 (Linux; Android 13; SM-A536B) AppleWebKit/537.36",
        browser: "chrome",
        browserVersion: "119.0.6045.163",
        os: "Android",
        osVersion: "13",
        device: "mobile",
        deviceType: "Samsung Galaxy A53",
        screenResolution: "1080x2400",
        language: "pt-MZ",
        country: "MZ",
        countryName: "Moçambique",
        city: "Maputo",
        region: "Maputo",
        isp: "Tmcel",
        latitude: -25.9692,
        longitude: 32.5732,
        timezone: "Africa/Maputo",
        firstVisit: "2024-01-15T11:10:00",
        lastVisit: "2024-01-15T11:45:00",
        visitCount: 1,
        pagesVisited: ["/home", "/pricing"],
        sessionDuration: 2100,
        isBlocked: false,
        isSuspicious: false,
        isBot: false,
        status: "active",
        userId: 502,
        referrer: "https://instagram.com",
        connectionType: "cellular",
        vpn: false,
        proxy: false,
        activities: [
            { time: "11:10", action: "Usuário logado" },
            { time: "11:30", action: "Página visitada: /pricing" },
            { time: "11:45", action: "Sessão encerrada" }
        ]
    }
];

// Mapeamento de navegadores para ícones
const browserIcons = {
    chrome: "bi-browser-chrome",
    firefox: "bi-browser-firefox",
    safari: "bi-browser-safari",
    edge: "bi-browser-edge",
    opera: "bi-browser-opera",
    bot: "bi-robot"
};

// Mapeamento de sistemas operacionais para ícones
const osIcons = {
    windows: "bi-windows",
    ios: "bi-apple",
    android: "bi-android",
    linux: "bi-ubuntu",
    macos: "bi-apple",
    bot: "bi-cpu"
};

// Mapeamento de dispositivos para ícones
const deviceIcons = {
    desktop: "bi-pc",
    mobile: "bi-phone",
    tablet: "bi-tablet",
    bot: "bi-robot"
};

// Mapeamento de países para códigos de bandeira
const countryFlags = {
    AO: "ao",
    PT: "pt",
    BR: "br",
    MZ: "mz",
    US: "us"
};

// Formatar data
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Formatar duração
function formatDuration(seconds) {
    if (seconds < 60) return `${seconds} segundos`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)} minutos`;
    return `${Math.floor(seconds / 3600)} horas`;
}

// Obter ícone do navegador
function getBrowserIcon(browser) {
    return browserIcons[browser] || "bi-question-circle";
}

// Obter ícone do sistema operacional
function getOSIcon(os) {
    const osLower = os.toLowerCase();
    if (osLower.includes('windows')) return osIcons.windows;
    if (osLower.includes('ios')) return osIcons.ios;
    if (osLower.includes('android')) return osIcons.android;
    if (osLower.includes('linux')) return osIcons.linux;
    if (osLower.includes('mac')) return osIcons.macos;
    return osIcons[osLower] || "bi-question-circle";
}

// Obter ícone do dispositivo
function getDeviceIcon(device) {
    return deviceIcons[device] || "bi-question-circle";
}

// Obter classe de status
function getStatusClass(status) {
    switch(status) {
        case 'active': return 'status-active';
        case 'blocked': return 'status-blocked';
        case 'suspicious': return 'status-suspicious';
        case 'bot': return 'status-bot';
        default: return 'status-active';
    }
}

// Obter texto de status
function getStatusText(status) {
    switch(status) {
        case 'active': return 'Ativo';
        case 'blocked': return 'Bloqueado';
        case 'suspicious': return 'Suspeito';
        case 'bot': return 'Bot/Crawler';
        default: return 'Desconhecido';
    }
}

$(document).ready(function() {
    let visitorsTable;
    let currentVisitor = null;
    let selectedVisitors = new Set();
    let browserChart = null;
    
    // Inicializar DataTable
    function initDataTable() {
        if ($.fn.DataTable.isDataTable('#visitors-table')) {
            visitorsTable.destroy();
        }
        
        visitorsTable = $('#visitors-table').DataTable({
            data: sampleVisitors,
            columns: [
                {
                    data: 'id',
                    render: function(data, type, row) {
                        return `
                            <input type="checkbox" class="form-check-input visitor-checkbox" data-id="${data}">
                        `;
                    },
                    orderable: false,
                    width: "30px"
                },
                { 
                    data: null,
                    render: function(data) {
                        const isOnline = isVisitorOnline(data.lastVisit);
                        const onlineBadge = isOnline ? 
                            '<span class="badge bg-success badge-pill ms-2" title="Online agora">●</span>' : '';
                        
                        return `
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="bi ${getDeviceIcon(data.device)} fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-medium">
                                        ${data.userId ? `Usuário #${data.userId}` : 'Visitante Anônimo'}
                                        ${onlineBadge}
                                    </div>
                                    <small class="text-muted">
                                        ${formatDate(data.lastVisit)}
                                    </small>
                                </div>
                            </div>
                        `;
                    }
                },
                { 
                    data: null,
                    render: function(data) {
                        const flagClass = countryFlags[data.country] ? 
                            `flag-icon flag-icon-${countryFlags[data.country]}` : 
                            'bi-globe';
                        
                        return `
                            <div>
                                <div>
                                    <i class="${flagClass}"></i>
                                    <strong>${data.countryName}</strong>
                                    <span class="badge bg-light text-dark geo-badge">${data.city}</span>
                                </div>
                                <small class="text-muted connection-info">
                                    ${data.isp}
                                    ${data.vpn ? '<span class="badge bg-warning ms-1">VPN</span>' : ''}
                                    ${data.proxy ? '<span class="badge bg-info ms-1">Proxy</span>' : ''}
                                </small>
                            </div>
                        `;
                    }
                },
                { 
                    data: null,
                    render: function(data) {
                        return `
                            <div>
                                <div>
                                    <i class="bi ${getOSIcon(data.os)} os-icon"></i>
                                    <span class="badge bg-secondary device-badge">${data.os} ${data.osVersion}</span>
                                    <i class="bi ${getBrowserIcon(data.browser)} browser-icon"></i>
                                    <span class="badge bg-primary device-badge">${data.browser} ${data.browserVersion}</span>
                                </div>
                                <div class="mt-1">
                                    <span class="badge bg-light text-dark device-badge">
                                        <i class="bi ${getDeviceIcon(data.device)}"></i>
                                        ${data.deviceType}
                                    </span>
                                    <small class="text-muted">${data.screenResolution}</small>
                                </div>
                            </div>
                        `;
                    }
                },
                { 
                    data: null,
                    render: function(data) {
                        const isIPv6 = data.ip.includes(':');
                        const ipClass = isIPv6 ? 'text-info' : '';
                        
                        return `
                            <div>
                                <div class="ip-address ${ipClass}">${data.ip}</div>
                                <small class="text-muted connection-info">
                                    ${data.connectionType}
                                    • ${data.language}
                                    • ${data.timezone}
                                </small>
                                <div class="mt-1">
                                    <small>
                                        <i class="bi bi-clock-history me-1"></i>
                                        ${formatDuration(data.sessionDuration)}
                                    </small>
                                </div>
                            </div>
                        `;
                    }
                },
                { 
                    data: null,
                    render: function(data) {
                        const visitCount = data.visitCount;
                        const pagesCount = data.pagesVisited.length;
                        const firstVisit = new Date(data.firstVisit);
                        const now = new Date();
                        const daysSinceFirst = Math.floor((now - firstVisit) / (1000 * 60 * 60 * 24));
                        
                        return `
                            <div>
                                <div>
                                    <span class="badge bg-light text-dark me-1">${visitCount} visitas</span>
                                    <span class="badge bg-light text-dark">${pagesCount} páginas</span>
                                </div>
                                <small class="text-muted">
                                    Primeira visita: ${daysSinceFirst} dias atrás
                                </small>
                            </div>
                        `;
                    }
                },
                { 
                    data: 'status',
                    render: function(data, type, row) {
                        const statusClass = getStatusClass(data);
                        const statusText = getStatusText(data);
                        return `<span class="visitor-status ${statusClass}">${statusText}</span>`;
                    }
                },
                {
                    data: 'id',
                    render: function(data, type, row) {
                        return `
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary view-visitor-btn" data-id="${data}" title="Detalhes">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-outline-warning block-visitor-btn" data-id="${data}" title="Bloquear">
                                    <i class="bi bi-shield-lock"></i>
                                </button>
                                <button class="btn btn-outline-danger delete-visitor-btn" data-id="${data}" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
            },
            responsive: true,
            pageLength: 10,
            dom: '<"top"lf>rt<"bottom"ip><"clear">',
            rowCallback: function(row, data) {
                if (data.isBlocked) {
                    $(row).addClass('table-danger');
                } else if (data.isSuspicious) {
                    $(row).addClass('table-warning');
                } else if (data.isBot) {
                    $(row).addClass('table-info');
                }
            },
            initComplete: function() {
                updateStats();
                setupRealTimeFilters();
                initBrowserChart();
                loadActivityTimeline();
                loadCountryOptions();
            }
        });
        
        updateResultsCount();
        
        // Adicionar evento para checkboxes
        $(document).on('change', '.visitor-checkbox', function() {
            const visitorId = $(this).data('id');
            if ($(this).is(':checked')) {
                selectedVisitors.add(visitorId);
            } else {
                selectedVisitors.delete(visitorId);
            }
            updateSelectedCount();
        });
    }
    
    // Verificar se visitante está online (últimos 5 minutos)
    function isVisitorOnline(lastVisit) {
        const lastVisitTime = new Date(lastVisit).getTime();
        const now = new Date().getTime();
        const fiveMinutes = 5 * 60 * 1000;
        return (now - lastVisitTime) < fiveMinutes;
    }
    
    // Atualizar estatísticas
    function updateStats() {
        const onlineCount = sampleVisitors.filter(v => isVisitorOnline(v.lastVisit)).length;
        const totalCount = sampleVisitors.length;
        const blockedCount = sampleVisitors.filter(v => v.isBlocked).length;
        const countries = [...new Set(sampleVisitors.map(v => v.countryName))].length;
        
        $('#online-count').text(onlineCount);
        $('#total-count').text(totalCount);
        $('#blocked-count').text(blockedCount);
        $('#countries-count').text(countries);
    }
    
    // Configurar filtros em tempo real
    function setupRealTimeFilters() {
        const filterIds = [
            'filter-ip',
            'filter-country',
            'filter-browser',
            'filter-device',
            'filter-status',
            'filter-date'
        ];
        
        filterIds.forEach(filterId => {
            $(`#${filterId}`).on('keyup change', function() {
                applyFilters();
            });
        });
    }
    
    // Aplicar filtros
    function applyFilters() {
        visitorsTable.search('').columns().search('').draw();
        
        const ip = $('#filter-ip').val();
        const country = $('#filter-country').val();
        const browser = $('#filter-browser').val();
        const device = $('#filter-device').val();
        const status = $('#filter-status').val();
        const dateRange = $('#filter-date').val();
        
        // Aplicar filtros combinados
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex, rowData) {
                const visitor = sampleVisitors[dataIndex];
                const lastVisit = new Date(visitor.lastVisit);
                const now = new Date();
                
                // Filtro por IP
                if (ip && !visitor.ip.includes(ip)) return false;
                
                // Filtro por país
                if (country && visitor.countryName !== country) return false;
                
                // Filtro por navegador
                if (browser && visitor.browser !== browser) return false;
                
                // Filtro por dispositivo
                if (device) {
                    if (device === 'bot' && !visitor.isBot) return false;
                    if (device !== 'bot' && visitor.device !== device) return false;
                }
                
                // Filtro por status
                if (status && visitor.status !== status) return false;
                
                // Filtro por data
                if (dateRange) {
                    const timeDiff = now - lastVisit;
                    const daysDiff = timeDiff / (1000 * 60 * 60 * 24);
                    
                    switch(dateRange) {
                        case 'today':
                            if (daysDiff > 1) return false;
                            break;
                        case 'yesterday':
                            if (daysDiff < 1 || daysDiff > 2) return false;
                            break;
                        case 'week':
                            if (daysDiff > 7) return false;
                            break;
                        case 'month':
                            if (daysDiff > 30) return false;
                            break;
                    }
                }
                
                return true;
            }
        );
        
        visitorsTable.draw();
        updateResultsCount();
        
        // Limpar filtros personalizados
        $.fn.dataTable.ext.search = [];
    }
    
    // Atualizar contagem de resultados
    function updateResultsCount() {
        const filteredCount = visitorsTable.rows({ search: 'applied' }).count();
        const totalCount = visitorsTable.rows().count();
        
        if (filteredCount === totalCount) {
            $('#visitor-results-count').text(`${totalCount} resultados`);
        } else {
            $('#visitor-results-count').text(`${filteredCount} de ${totalCount} resultados`);
        }
    }
    
    // Atualizar contagem de selecionados
    function updateSelectedCount() {
        $('#selected-count').text(`${selectedVisitors.size} selecionados`);
    }
    
    // Carregar opções de países
    function loadCountryOptions() {
        const countries = [...new Set(sampleVisitors.map(v => v.countryName))].sort();
        const select = $('#filter-country');
        countries.forEach(country => {
            select.append(`<option value="${country}">${country}</option>`);
        });
    }
    
    // Inicializar gráfico de navegadores
    function initBrowserChart() {
        const browserData = {};
        sampleVisitors.forEach(visitor => {
            const browser = visitor.browser;
            browserData[browser] = (browserData[browser] || 0) + 1;
        });
        
        const ctx = document.createElement('canvas');
        $('#browser-chart').html(ctx);
        
        browserChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(browserData),
                datasets: [{
                    data: Object.values(browserData),
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // Carregar timeline de atividades
    function loadActivityTimeline() {
        const timeline = $('#activity-timeline');
        timeline.empty();
        
        // Pegar atividades recentes (últimas 10)
        const allActivities = [];
        sampleVisitors.forEach(visitor => {
            visitor.activities.forEach(activity => {
                allActivities.push({
                    visitorId: visitor.id,
                    ip: visitor.ip,
                    time: activity.time,
                    action: activity.action,
                    country: visitor.countryName
                });
            });
        });
        
        // Ordenar por tempo (mais recente primeiro) e pegar as últimas 10
        allActivities.sort((a, b) => new Date(`2000-01-01T${b.time}`) - new Date(`2000-01-01T${a.time}`));
        const recentActivities = allActivities.slice(0, 10);
        
        recentActivities.forEach(activity => {
            timeline.append(`
                <div class="timeline-item">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted">${activity.time}</small>
                                    <p class="mb-1">${activity.action}</p>
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt me-1"></i>${activity.country}
                                        • <span class="ip-address">${activity.ip}</span>
                                    </small>
                                </div>
                                <span class="badge bg-light text-dark">Visitante #${activity.visitorId}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        });
    }
    
    // Inicializar DataTable
    initDataTable();
    
    // Atualizar dados periodicamente (simulação de tempo real)
    setInterval(() => {
        updateStats();
    }, 30000); // A cada 30 segundos
    
    // Botão de atualizar
    $('#refresh-visitors-btn').click(function() {
        // Simular novos visitantes
        const newVisitor = {
            id: sampleVisitors.length + 1,
            ip: `192.168.1.${Math.floor(Math.random() * 255)}`,
            userAgent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
            browser: "chrome",
            browserVersion: "120.0.0.0",
            os: "Windows",
            osVersion: "10",
            device: "desktop",
            deviceType: "Desktop",
            screenResolution: "1920x1080",
            language: "pt-BR",
            country: "AO",
            countryName: "Angola",
            city: "Luanda",
            region: "Luanda",
            isp: "Angola Telecom",
            latitude: -8.8383,
            longitude: 13.2344,
            timezone: "Africa/Luanda",
            firstVisit: new Date().toISOString(),
            lastVisit: new Date().toISOString(),
            visitCount: 1,
            pagesVisited: ["/home"],
            sessionDuration: 120,
            isBlocked: false,
            isSuspicious: false,
            isBot: false,
            status: "active",
            userId: null,
            referrer: "https://google.com",
            connectionType: "broadband",
            vpn: false,
            proxy: false,
            activities: [
                { time: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }), 
                  action: "Novo visitante detectado" }
            ]
        };
        
        sampleVisitors.unshift(newVisitor);
        initDataTable();
        showToast('info', 'Visitantes Atualizados', 'Novos dados foram carregados.');
    });
    
    // Limpar filtros
    $('#clear-filters').click(function() {
        $('#filter-ip').val('');
        $('#filter-country').val('');
        $('#filter-browser').val('');
        $('#filter-device').val('');
        $('#filter-status').val('');
        $('#filter-date').val('week');
        
        visitorsTable.search('').columns().search('').draw();
        updateResultsCount();
    });
    
    // Selecionar todos os visitantes
    $('#select-all-header, #select-all-visitors').click(function() {
        const isChecked = $(this).is(':checked');
        $('.visitor-checkbox').prop('checked', isChecked);
        
        if (isChecked) {
            sampleVisitors.forEach(visitor => {
                selectedVisitors.add(visitor.id);
            });
        } else {
            selectedVisitors.clear();
        }
        updateSelectedCount();
    });
    
    // Visualizar detalhes do visitante
    $(document).on('click', '.view-visitor-btn', function() {
        const visitorId = $(this).data('id');
        currentVisitor = sampleVisitors.find(v => v.id === visitorId);
        
        if (currentVisitor) {
            const isOnline = isVisitorOnline(currentVisitor.lastVisit);
            const onlineStatus = isOnline ? 
                '<span class="badge bg-success ms-2">Online Agora</span>' : 
                '<span class="badge bg-secondary ms-2">Offline</span>';
            
            const securityBadges = [];
            if (currentVisitor.vpn) securityBadges.push('<span class="badge bg-warning">VPN</span>');
            if (currentVisitor.proxy) securityBadges.push('<span class="badge bg-info">Proxy</span>');
            if (currentVisitor.isBot) securityBadges.push('<span class="badge bg-primary">Bot/Crawler</span>');
            
            const securityHtml = securityBadges.length > 0 ? 
                `<div class="mb-3">${securityBadges.join(' ')}</div>` : '';
            
            $('#view-visitor-body').html(`
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="bi ${getDeviceIcon(currentVisitor.device)} display-1 text-primary"></i>
                                </div>
                                <h5>${currentVisitor.userId ? `Usuário #${currentVisitor.userId}` : 'Visitante Anônimo'}</h5>
                                <p class="text-muted">
                                    <span class="${getStatusClass(currentVisitor.status)}">
                                        ${getStatusText(currentVisitor.status)}
                                    </span>
                                    ${onlineStatus}
                                </p>
                                
                                ${securityHtml}
                                
                                <div class="mt-3">
                                    <h6><i class="bi bi-info-circle me-2"></i>Informações Básicas</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <th style='color:#718096!important'>ID Visitante:</th>
                                            <td>${currentVisitor.id}</td>
                                        </tr>
                                        <tr>
                                            <th style='color:#718096!important'>Primeira Visita:</th>
                                            <td>${formatDate(currentVisitor.firstVisit)}</td>
                                        </tr>
                                        <tr>
                                            <th style='color:#718096!important'>Última Visita:</th>
                                            <td>${formatDate(currentVisitor.lastVisit)}</td>
                                        </tr>
                                        <tr>
                                            <th style='color:#718096!important'>Total Visitas:</th>
                                            <td>${currentVisitor.visitCount}</td>
                                        </tr>
                                        <tr>
                                            <th style='color:#718096!important'>Duração Sessão:</th>
                                            <td>${formatDuration(currentVisitor.sessionDuration)}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6><i class="bi bi-geo-alt me-2"></i>Localização</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <th style='color:#718096!important'>IP:</th>
                                                <td><code>${currentVisitor.ip}</code></td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>País:</th>
                                                <td>
                                                    <i class="flag-icon flag-icon-${countryFlags[currentVisitor.country] || 'globe'}"></i>
                                                    ${currentVisitor.countryName}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>Cidade/Região:</th>
                                                <td>${currentVisitor.city}, ${currentVisitor.region}</td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>ISP:</th>
                                                <td>${currentVisitor.isp}</td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>Fuso Horário:</th>
                                                <td>${currentVisitor.timezone}</td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>Coordenadas:</th>
                                                <td>${currentVisitor.latitude}, ${currentVisitor.longitude}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6><i class="bi bi-laptop me-2"></i>Dispositivo</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <th style='color:#718096!important'>Sistema Operacional:</th>
                                                <td>
                                                    <i class="bi ${getOSIcon(currentVisitor.os)}"></i>
                                                    ${currentVisitor.os} ${currentVisitor.osVersion}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>Navegador:</th>
                                                <td>
                                                    <i class="bi ${getBrowserIcon(currentVisitor.browser)}"></i>
                                                    ${currentVisitor.browser} ${currentVisitor.browserVersion}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>Tipo Dispositivo:</th>
                                                <td>
                                                    <i class="bi ${getDeviceIcon(currentVisitor.device)}"></i>
                                                    ${currentVisitor.deviceType}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>Resolução:</th>
                                                <td>${currentVisitor.screenResolution}</td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>Idioma:</th>
                                                <td>${currentVisitor.language}</td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>Conexão:</th>
                                                <td>${currentVisitor.connectionType}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <h6><i class="bi bi-activity me-2"></i>Atividade Recente</h6>
                                <div class="visitor-timeline">
                                    ${currentVisitor.activities.map(activity => `
                                        <div class="timeline-item">
                                            <div class="card">
                                                <div class="card-body p-3">
                                                    <small class="text-muted">${activity.time}</small>
                                                    <p class="mb-0">${activity.action}</p>
                                                </div>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                                
                                <h6 class="mt-4"><i class="bi bi-link me-2"></i>Páginas Visitadas</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    ${currentVisitor.pagesVisited.map(page => `
                                        <span class="badge bg-light text-dark">${page}</span>
                                    `).join('')}
                                </div>
                                
                                <h6 class="mt-4"><i class="bi bi-shield me-2"></i>Informações de Referência</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th style='color:#718096!important'>Referência:</th>
                                        <td>${currentVisitor.referrer || 'Direto'}</td>
                                    </tr>
                                    <tr>
                                        <th style='color:#718096!important'>User Agent:</th>
                                        <td><small class="text-muted">${currentVisitor.userAgent}</small></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            $('#viewVisitorModal').modal('show');
        }
    });
    
    // Bloquear visitante
    $(document).on('click', '.block-visitor-btn', function() {
        const visitorId = $(this).data('id');
        currentVisitor = sampleVisitors.find(v => v.id === visitorId);
        
        if (currentVisitor) {
            const isAlreadyBlocked = currentVisitor.isBlocked;
            
            $('#block-visitor-details').html(`
                <div class="alert ${isAlreadyBlocked ? 'alert-info' : 'alert-warning'}">
                    <h6><i class="bi bi-shield-exclamation me-2"></i>
                        ${isAlreadyBlocked ? 'DESBLOQUEAR Visitante' : 'BLOQUEAR Visitante'}
                    </h6>
                    <p class="mb-0">
                        ${isAlreadyBlocked ? 
                            'Você está prestes a desbloquear este visitante. Ele poderá acessar o sistema novamente.' : 
                            'Você está prestes a bloquear este visitante. Ele não poderá mais acessar o sistema.'}
                    </p>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h6>Detalhes do Visitante:</h6>
                        <table class="table table-sm">
                            <tr>
                                <th style='color:#718096!important'>ID:</th>
                                <td><strong>${currentVisitor.id}</strong></td>
                            </tr>
                            <tr>
                                <th style='color:#718096!important'>IP:</th>
                                <td><code>${currentVisitor.ip}</code></td>
                            </tr>
                            <tr>
                                <th style='color:#718096!important'>Localização:</th>
                                <td>
                                    <i class="flag-icon flag-icon-${countryFlags[currentVisitor.country] || 'globe'}"></i>
                                    ${currentVisitor.countryName}, ${currentVisitor.city}
                                </td>
                            </tr>
                            <tr>
                                <th style='color:#718096!important'>Dispositivo:</th>
                                <td>${currentVisitor.deviceType} (${currentVisitor.browser})</td>
                            </tr>
                            <tr>
                                <th style='color:#718096!important'>Status Atual:</th>
                                <td><span class="${getStatusClass(currentVisitor.status)}">${getStatusText(currentVisitor.status)}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            `);
            
            // Ajustar título do modal baseado no status atual
            const title = isAlreadyBlocked ? 'Desbloquear Visitante' : 'Bloquear Visitante';
            $('#blockVisitorModal .modal-title').html(`<i class="bi bi-shield-exclamation me-2"></i>${title}`);
            
            $('#blockVisitorModal').modal('show');
        }
    });
    
    // Configurar tipo de bloqueio dinâmico
    $('#block-type').change(function() {
        const type = $(this).val();
        if (type === 'custom') {
            $('#custom-block-duration').show();
        } else {
            $('#custom-block-duration').hide();
        }
    });
    
    // Confirmar bloqueio/desbloqueio
    $('#block-visitor-form').submit(function(e) {
        e.preventDefault();
        
        if (currentVisitor) {
            const isAlreadyBlocked = currentVisitor.isBlocked;
            const reason = $('#block-reason').val();
            const notes = $('#block-notes').val();
            const blockType = $('#block-type').val();
            
            // Alternar status de bloqueio
            currentVisitor.isBlocked = !isAlreadyBlocked;
            currentVisitor.status = currentVisitor.isBlocked ? 'blocked' : 'active';
            
            // Adicionar atividade
            const action = currentVisitor.isBlocked ? 'Bloqueado' : 'Desbloqueado';
            currentVisitor.activities.unshift({
                time: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
                action: `${action} - Motivo: ${reason}`
            });
            
            // Atualizar DataTable
            initDataTable();
            
            // Mostrar confirmação
            $('#blockVisitorModal').modal('hide');
            $('#block-reason').val('');
            $('#block-notes').val('');
            
            showToast(
                currentVisitor.isBlocked ? 'warning' : 'success',
                currentVisitor.isBlocked ? 'Visitante Bloqueado' : 'Visitante Desbloqueado',
                `O visitante ${currentVisitor.ip} foi ${currentVisitor.isBlocked ? 'bloqueado' : 'desbloqueado'} com sucesso.`
            );
        }
    });
    
    // Excluir visitante individual
    $(document).on('click', '.delete-visitor-btn', function() {
        const visitorId = $(this).data('id');
        const visitor = sampleVisitors.find(v => v.id === visitorId);
        
        if (visitor && confirm(`Tem certeza que deseja excluir o visitante ${visitor.ip}?`)) {
            const index = sampleVisitors.findIndex(v => v.id === visitorId);
            if (index !== -1) {
                sampleVisitors.splice(index, 1);
                initDataTable();
                showToast('success', 'Visitante Excluído', `O visitante ${visitor.ip} foi removido.`);
            }
        }
    });
    
    // Botões de ação em massa
    $('#export-selected-btn').click(function() {
        if (selectedVisitors.size === 0) {
            showToast('warning', 'Nenhum item selecionado', 'Selecione pelo menos um visitante para exportar.');
            return;
        }
        $('#exportModal').modal('show');
    });
    
    $('#block-selected-btn').click(function() {
        if (selectedVisitors.size === 0) {
            showToast('warning', 'Nenhum item selecionado', 'Selecione pelo menos um visitante para bloquear.');
            return;
        }
        
        if (confirm(`Deseja bloquear ${selectedVisitors.size} visitante(s) selecionado(s)?`)) {
            selectedVisitors.forEach(visitorId => {
                const visitor = sampleVisitors.find(v => v.id === visitorId);
                if (visitor) {
                    visitor.isBlocked = true;
                    visitor.status = 'blocked';
                }
            });
            initDataTable();
            selectedVisitors.clear();
            updateSelectedCount();
            showToast('success', 'Visitantes Bloqueados', `${selectedVisitors.size} visitante(s) foram bloqueados.`);
        }
    });
    
    $('#delete-selected-btn').click(function() {
        if (selectedVisitors.size === 0) {
            showToast('warning', 'Nenhum item selecionado', 'Selecione pelo menos um visitante para excluir.');
            return;
        }
        
        if (confirm(`Deseja excluir permanentemente ${selectedVisitors.size} visitante(s) selecionado(s)?`)) {
            // Remover visitantes selecionados
            sampleVisitors = sampleVisitors.filter(v => !selectedVisitors.has(v.id));
            initDataTable();
            selectedVisitors.clear();
            updateSelectedCount();
            showToast('success', 'Visitantes Excluídos', `${selectedVisitors.size} visitante(s) foram removidos.`);
        }
    });
    
    // Exportar dados
    $('#export-form').submit(function(e) {
        e.preventDefault();
        
        const format = $('#export-format').val();
        const range = $('#export-range').val();
        
        // Filtrar dados baseado no range
        let dataToExport = [];
        if (range === 'selected') {
            dataToExport = sampleVisitors.filter(v => selectedVisitors.has(v.id));
        } else if (range === 'filtered') {
            // Pegar dados filtrados da DataTable
            const indices = visitorsTable.rows({ search: 'applied' }).indexes().toArray();
            dataToExport = indices.map(idx => sampleVisitors[idx]);
        } else {
            dataToExport = [...sampleVisitors];
        }
        
        if (dataToExport.length === 0) {
            showToast('warning', 'Sem dados', 'Não há dados para exportar com os critérios selecionados.');
            return;
        }
        
        // Gerar arquivo
        let content = '';
        let filename = '';
        
        switch(format) {
            case 'csv':
                filename = `visitantes_${new Date().toISOString().split('T')[0]}.csv`;
                content = generateCSV(dataToExport);
                downloadFile(content, filename, 'text/csv');
                break;
                
            case 'json':
                filename = `visitantes_${new Date().toISOString().split('T')[0]}.json`;
                content = JSON.stringify(dataToExport, null, 2);
                downloadFile(content, filename, 'application/json');
                break;
                
            case 'pdf':
                // Implementação simplificada - em produção use uma biblioteca PDF
                showToast('info', 'PDF em desenvolvimento', 'A exportação para PDF está em desenvolvimento.');
                break;
                
            case 'excel':
                showToast('info', 'Excel em desenvolvimento', 'A exportação para Excel está em desenvolvimento.');
                break;
        }
        
        $('#exportModal').modal('hide');
    });
    
    // Gerar CSV
    function generateCSV(data) {
        const headers = ['IP', 'País', 'Cidade', 'Navegador', 'Dispositivo', 'Status', 'Última Visita', 'Visitas'];
        const rows = data.map(visitor => [
            visitor.ip,
            visitor.countryName,
            visitor.city,
            visitor.browser,
            visitor.deviceType,
            getStatusText(visitor.status),
            formatDate(visitor.lastVisit),
            visitor.visitCount
        ]);
        
        return [headers, ...rows].map(row => 
            row.map(cell => `"${cell}"`).join(',')
        ).join('\n');
    }
    
    // Download de arquivo
    function downloadFile(content, filename, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    // Limpeza em massa
    $('#bulk-delete-form').submit(function(e) {
        e.preventDefault();
        
        const option = $('#delete-option').val();
        const confirmText = $('#delete-confirm').val();
        const password = $('#admin-password-bulk').val();
        
        if (confirmText !== 'EXCLUIR TUDO') {
            showToast('error', 'Confirmação incorreta', 'Digite exatamente "EXCLUIR TUDO" para confirmar.');
            return;
        }
        
        if (password !== 'admin123') {
            showToast('error', 'Senha incorreta', 'A senha de administrador está incorreta.');
            return;
        }
        
        let deleteCount = 0;
        
        switch(option) {
            case 'all':
                deleteCount = sampleVisitors.length;
                sampleVisitors.length = 0;
                break;
                
            case 'older_30':
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                
                sampleVisitors = sampleVisitors.filter(visitor => {
                    const lastVisit = new Date(visitor.lastVisit);
                    const shouldKeep = lastVisit > thirtyDaysAgo;
                    if (!shouldKeep) deleteCount++;
                    return shouldKeep;
                });
                break;
                
            case 'older_90':
                const ninetyDaysAgo = new Date();
                ninetyDaysAgo.setDate(ninetyDaysAgo.getDate() - 90);
                
                sampleVisitors = sampleVisitors.filter(visitor => {
                    const lastVisit = new Date(visitor.lastVisit);
                    const shouldKeep = lastVisit > ninetyDaysAgo;
                    if (!shouldKeep) deleteCount++;
                    return shouldKeep;
                });
                break;
                
            case 'blocked':
                sampleVisitors = sampleVisitors.filter(visitor => {
                    const shouldKeep = !visitor.isBlocked;
                    if (!shouldKeep) deleteCount++;
                    return shouldKeep;
                });
                break;
        }
        
        // Resetar formulário
        $('#delete-option').val('');
        $('#delete-confirm').val('');
        $('#admin-password-bulk').val('');
        
        // Fechar modal
        $('#bulkDeleteModal').modal('hide');
        
        // Atualizar tabela
        initDataTable();
        
        // Mostrar resultado
        showToast('success', 'Limpeza Concluída', `${deleteCount} registro(s) foram removidos.`);
    });
    
    // Botões no modal de detalhes
    $('#toggle-block-btn').click(function() {
        if (currentVisitor) {
            currentVisitor.isBlocked = !currentVisitor.isBlocked;
            currentVisitor.status = currentVisitor.isBlocked ? 'blocked' : 'active';
            initDataTable();
            $('#viewVisitorModal').modal('hide');
            showToast(
                currentVisitor.isBlocked ? 'warning' : 'success',
                currentVisitor.isBlocked ? 'Visitante Bloqueado' : 'Visitante Desbloqueado',
                `O visitante foi ${currentVisitor.isBlocked ? 'bloqueado' : 'desbloqueado'}.`
            );
        }
    });
    
    $('#delete-visitor-btn').click(function() {
        if (currentVisitor && confirm(`Excluir permanentemente o visitante ${currentVisitor.ip}?`)) {
            const index = sampleVisitors.findIndex(v => v.id === currentVisitor.id);
            if (index !== -1) {
                sampleVisitors.splice(index, 1);
                initDataTable();
                $('#viewVisitorModal').modal('hide');
                showToast('success', 'Visitante Excluído', 'O registro foi removido permanentemente.');
            }
        }
    });
    
    // Função para mostrar toast
    function showToast(type, title, message) {
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong><br>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        const toastContainer = $('.toast-container');
        if (toastContainer.length === 0) {
            $('body').append('<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
        }
        
        $('.toast-container').append(toastHtml);
        
        const toast = new bootstrap.Toast(document.getElementById(toastId), {
            delay: 5000
        });
        toast.show();
        
        $(`#${toastId}`).on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
});