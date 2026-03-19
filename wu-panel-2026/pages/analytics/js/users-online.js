// users-online.js - Sistema de Gerenciamento de Usuários Online

// Dados de exemplo de usuários online
const sampleUsers = [
    {
        id: 1,
        username: "carlos_artista",
        email: "carlos@wasomupfy.com",
        fullName: "Carlos Silva",
        avatar: "https://randomuser.me/api/portraits/men/32.jpg",
        role: "artist",
        roleName: "Artista",
        country: "AO",
        countryName: "Angola",
        city: "Luanda",
        state: "Luanda",
        accountType: "premium",
        createdAt: "2023-05-15T10:30:00",
        lastLogin: new Date().toISOString(),
        status: "online",
        device: "desktop",
        browser: "Chrome",
        ip: "192.168.1.100",
        sessionStart: new Date(Date.now() - 45 * 60 * 1000).toISOString(), // 45 minutos atrás
        sessionDuration: 2700, // 45 minutos em segundos
        currentActivity: "Ouvindo música",
        activityDetails: "Playlist: 'Melhores Hits 2024'",
        songsUploaded: 24,
        totalStreams: 15200,
        isVerified: true,
        isSubscribed: true,
        plan: "premium",
        lastActivity: new Date().toISOString()
    },
    {
        id: 2,
        username: "ana_mod",
        email: "ana@wasomupfy.com",
        fullName: "Ana Pereira",
        avatar: "https://randomuser.me/api/portraits/women/44.jpg",
        role: "moderator",
        roleName: "Moderadora",
        country: "PT",
        countryName: "Portugal",
        city: "Lisboa",
        state: "Lisboa",
        accountType: "admin",
        createdAt: "2023-02-10T14:20:00",
        lastLogin: new Date().toISOString(),
        status: "away",
        device: "mobile",
        browser: "Safari",
        ip: "105.179.92.15",
        sessionStart: new Date(Date.now() - 30 * 60 * 1000).toISOString(),
        sessionDuration: 1800,
        currentActivity: "Revisando conteúdos",
        activityDetails: "Verificando novos uploads",
        songsUploaded: 0,
        totalStreams: 0,
        isVerified: true,
        isSubscribed: true,
        plan: "enterprise",
        lastActivity: new Date(Date.now() - 10 * 60 * 1000).toISOString() // 10 minutos atrás
    },
    {
        id: 3,
        username: "admin_master",
        email: "admin@wasomupfy.com",
        fullName: "Miguel Santos",
        avatar: "https://randomuser.me/api/portraits/men/67.jpg",
        role: "admin",
        roleName: "Administrador",
        country: "BR",
        countryName: "Brasil",
        city: "São Paulo",
        state: "SP",
        accountType: "admin",
        createdAt: "2022-11-20T09:15:00",
        lastLogin: new Date().toISOString(),
        status: "busy",
        device: "desktop",
        browser: "Firefox",
        ip: "177.200.40.10",
        sessionStart: new Date(Date.now() - 120 * 60 * 1000).toISOString(),
        sessionDuration: 7200,
        currentActivity: "Gerenciando sistema",
        activityDetails: "Atualizando configurações",
        songsUploaded: 0,
        totalStreams: 0,
        isVerified: true,
        isSubscribed: true,
        plan: "enterprise",
        lastActivity: new Date().toISOString()
    },
    {
        id: 4,
        username: "luisa_music",
        email: "luisa@wasomupfy.com",
        fullName: "Luísa Fernandes",
        avatar: "https://randomuser.me/api/portraits/women/28.jpg",
        role: "user",
        roleName: "Usuária",
        country: "MZ",
        countryName: "Moçambique",
        city: "Maputo",
        state: "Maputo",
        accountType: "basic",
        createdAt: "2024-01-05T16:45:00",
        lastLogin: new Date().toISOString(),
        status: "online",
        device: "mobile",
        browser: "Chrome",
        ip: "41.216.184.25",
        sessionStart: new Date(Date.now() - 15 * 60 * 1000).toISOString(),
        sessionDuration: 900,
        currentActivity: "Fazendo upload",
        activityDetails: "Upload: 'Minha Nova Música.mp3'",
        songsUploaded: 3,
        totalStreams: 450,
        isVerified: false,
        isSubscribed: false,
        plan: "free",
        lastActivity: new Date().toISOString()
    },
    {
        id: 5,
        username: "manager_joao",
        email: "joao@wasomupfy.com",
        fullName: "João Mendes",
        avatar: "https://randomuser.me/api/portraits/men/22.jpg",
        role: "manager",
        roleName: "Manager",
        country: "AO",
        countryName: "Angola",
        city: "Benguela",
        state: "Benguela",
        accountType: "business",
        createdAt: "2023-08-30T11:20:00",
        lastLogin: new Date().toISOString(),
        status: "online",
        device: "desktop",
        browser: "Edge",
        ip: "192.168.1.150",
        sessionStart: new Date(Date.now() - 90 * 60 * 1000).toISOString(),
        sessionDuration: 5400,
        currentActivity: "Analisando estatísticas",
        activityDetails: "Dashboard de vendas",
        songsUploaded: 12,
        totalStreams: 8900,
        isVerified: true,
        isSubscribed: true,
        plan: "business",
        lastActivity: new Date().toISOString()
    },
    {
        id: 6,
        username: "david_listener",
        email: "david@example.com",
        fullName: "David Costa",
        avatar: "https://randomuser.me/api/portraits/men/45.jpg",
        role: "user",
        roleName: "Usuário",
        country: "BR",
        countryName: "Brasil",
        city: "Rio de Janeiro",
        state: "RJ",
        accountType: "premium",
        createdAt: "2023-12-01T13:10:00",
        lastLogin: new Date().toISOString(),
        status: "online",
        device: "tablet",
        browser: "Chrome",
        ip: "200.150.100.25",
        sessionStart: new Date(Date.now() - 25 * 60 * 1000).toISOString(),
        sessionDuration: 1500,
        currentActivity: "Explorando playlists",
        activityDetails: "Descobrindo novos artistas",
        songsUploaded: 0,
        totalStreams: 0,
        isVerified: false,
        isSubscribed: true,
        plan: "premium",
        lastActivity: new Date().toISOString()
    },
    {
        id: 7,
        username: "sara_artist",
        email: "sara@wasomupfy.com",
        fullName: "Sara Oliveira",
        avatar: "https://randomuser.me/api/portraits/women/33.jpg",
        role: "artist",
        roleName: "Artista",
        country: "PT",
        countryName: "Portugal",
        city: "Porto",
        state: "Porto",
        accountType: "premium",
        createdAt: "2023-09-18T15:30:00",
        lastLogin: new Date().toISOString(),
        status: "invisible",
        device: "desktop",
        browser: "Opera",
        ip: "89.101.45.78",
        sessionStart: new Date(Date.now() - 60 * 60 * 1000).toISOString(),
        sessionDuration: 3600,
        currentActivity: "Editando perfil",
        activityDetails: "Atualizando informações",
        songsUploaded: 18,
        totalStreams: 12500,
        isVerified: true,
        isSubscribed: true,
        plan: "premium",
        lastActivity: new Date(Date.now() - 5 * 60 * 1000).toISOString()
    }
];

// Mapeamento de funções para cores
const roleColors = {
    admin: "role-color-admin",
    moderator: "role-color-moderator",
    user: "role-color-user",
    artist: "role-color-artist",
    manager: "role-color-manager"
};

// Mapeamento de status para classes
const statusClasses = {
    online: "status-online",
    away: "status-away",
    busy: "status-busy",
    invisible: "status-invisible"
};

// Mapeamento de status para texto
const statusTexts = {
    online: "Online",
    away: "Ausente",
    busy: "Ocupado",
    invisible: "Invisível"
};

// Mapeamento de países para códigos de bandeira
const countryFlags = {
    AO: "ao",
    PT: "pt",
    BR: "br",
    MZ: "mz"
};

// Atividades recentes
const recentActivities = [
    { userId: 1, username: "carlos_artista", action: "fez upload de uma nova música", time: "2 minutos atrás", icon: "bi-music-note-beamed" },
    { userId: 3, username: "admin_master", action: "atualizou as configurações do sistema", time: "5 minutos atrás", icon: "bi-gear" },
    { userId: 4, username: "luisa_music", action: "adicionou 3 músicas à playlist", time: "10 minutos atrás", icon: "bi-plus-circle" },
    { userId: 5, username: "manager_joao", action: "visualizou relatório de vendas", time: "15 minutos atrás", icon: "bi-graph-up" },
    { userId: 6, username: "david_listener", action: "começou a seguir 2 novos artistas", time: "20 minutos atrás", icon: "bi-person-plus" },
    { userId: 2, username: "ana_mod", action: "aprovou 5 conteúdos pendentes", time: "25 minutos atrás", icon: "bi-check-circle" },
    { userId: 7, username: "sara_artist", action: "atualizou informações do perfil", time: "30 minutos atrás", icon: "bi-pencil" }
];

// Formatar data
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    
    if (diffMins < 1) return "Agora mesmo";
    if (diffMins < 60) return `${diffMins} min atrás`;
    
    return date.toLocaleString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Formatar duração da sessão
function formatSessionDuration(seconds) {
    if (seconds < 60) return `${seconds}s`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}min`;
    return `${Math.floor(seconds / 3600)}h ${Math.floor((seconds % 3600) / 60)}min`;
}

// Calcular tempo desde o início da sessão
function getSessionTime(startTime) {
    const start = new Date(startTime);
    const now = new Date();
    const diffMs = now - start;
    return Math.floor(diffMs / 1000); // retorna em segundos
}

// Verificar se usuário está ativo (últimos 5 minutos)
function isUserActive(lastActivity) {
    const last = new Date(lastActivity);
    const now = new Date();
    const fiveMinutes = 5 * 60 * 1000;
    return (now - last) < fiveMinutes;
}

// Obter ícone do dispositivo
function getDeviceIcon(device) {
    switch(device) {
        case 'mobile': return 'bi-phone';
        case 'tablet': return 'bi-tablet';
        default: return 'bi-pc';
    }
}

// Obter ícone do navegador
function getBrowserIcon(browser) {
    switch(browser.toLowerCase()) {
        case 'chrome': return 'bi-browser-chrome';
        case 'firefox': return 'bi-browser-firefox';
        case 'safari': return 'bi-browser-safari';
        case 'edge': return 'bi-browser-edge';
        case 'opera': return 'bi-browser-opera';
        default: return 'bi-globe';
    }
}

$(document).ready(function() {
    let usersTable;
    let selectedUsers = new Set();
    let currentUser = null;
    let roleChart = null;
    let autoRefreshInterval = null;
    let countries = [...new Set(sampleUsers.map(u => u.countryName))];
    
    // Inicializar DataTable
    function initDataTable() {
        if ($.fn.DataTable.isDataTable('#users-table')) {
            usersTable.destroy();
        }
        
        // Atualizar durações de sessão
        sampleUsers.forEach(user => {
            user.sessionDuration = getSessionTime(user.sessionStart);
        });
        
        usersTable = $('#users-table').DataTable({
            data: sampleUsers,
            columns: [
                {
                    data: 'id',
                    render: function(data, type, row) {
                        return `
                            <input type="checkbox" class="form-check-input user-checkbox" data-id="${data}">
                        `;
                    },
                    orderable: false,
                    width: "30px"
                },
                { 
                    data: null,
                    render: function(data) {
                        const isActive = isUserActive(data.lastActivity);
                        const statusClass = statusClasses[data.status] || 'status-online';
                        
                        return `
                            <div class="d-flex align-items-center">
                                <div class="position-relative me-3">
                                    <img src="${data.avatar}" alt="${data.fullName}" class="user-avatar">
                                    <span class="badge-online ${statusClass}"></span>
                                </div>
                                <div>
                                    <div class="fw-medium">
                                        ${data.fullName}
                                        ${data.isVerified ? '<i class="bi bi-patch-check-fill text-primary ms-1" title="Verificado"></i>' : ''}
                                    </div>
                                    <small class="text-muted">@${data.username}</small>
                                    <div class="mt-1">
                                        <small class="${statusClass}">
                                            <span class="online-status ${statusClass}"></span>
                                            ${statusTexts[data.status] || 'Online'}
                                            ${isActive ? ' • Ativo' : ''}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                },
                { 
                    data: 'role',
                    render: function(data, type, row) {
                        const roleName = row.roleName || data;
                        return `<span class="badge ${roleColors[data] || 'bg-secondary'} badge-role">${roleName}</span>`;
                    }
                },
                { 
                    data: null,
                    render: function(data) {
                        return `
                            <div>
                                <div>
                                    <i class="flag-icon flag-icon-${countryFlags[data.country] || 'globe'}"></i>
                                    <strong>${data.countryName}</strong>
                                </div>
                                <small class="text-muted connection-info">
                                    ${data.city}, ${data.state}
                                </small>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        <i class="bi bi-envelope me-1"></i>${data.email}
                                    </small>
                                </div>
                            </div>
                        `;
                    }
                },
                { 
                    data: null,
                    render: function(data) {
                        const isActive = isUserActive(data.lastActivity);
                        const activityClass = isActive ? 'text-success' : 'text-muted';
                        
                        return `
                            <div>
                                <div class="fw-medium ${activityClass}">
                                    <i class="bi bi-activity me-1"></i>
                                    ${data.currentActivity}
                                </div>
                                <small class="text-muted last-active">
                                    ${data.activityDetails}
                                </small>
                                <div class="mt-1">
                                    <span class="badge bg-light text-dark device-badge">
                                        <i class="bi ${getDeviceIcon(data.device)}"></i> ${data.device}
                                    </span>
                                    <span class="badge bg-light text-dark device-badge">
                                        <i class="bi ${getBrowserIcon(data.browser)}"></i> ${data.browser}
                                    </span>
                                </div>
                            </div>
                        `;
                    }
                },
                { 
                    data: 'sessionDuration',
                    render: function(data, type, row) {
                        return `
                            <div>
                                <div class="session-time">
                                    <i class="bi bi-clock me-1"></i>
                                    ${formatSessionDuration(data)}
                                </div>
                                <small class="text-muted">
                                    Desde: ${formatDate(row.sessionStart)}
                                </small>
                                <div class="mt-1">
                                    <small>
                                        IP: <code class="ip-address">${row.ip}</code>
                                    </small>
                                </div>
                            </div>
                        `;
                    }
                },
                { 
                    data: null,
                    render: function(data) {
                        const accountType = data.accountType;
                        const accountBadge = accountType === 'admin' || accountType === 'enterprise' ? 
                            '<span class="badge bg-danger">Enterprise</span>' :
                            accountType === 'premium' || accountType === 'business' ? 
                            '<span class="badge bg-warning">Premium</span>' :
                            '<span class="badge bg-secondary">Free</span>';
                        
                        return `
                            <div>
                                <div>
                                    ${accountBadge}
                                    <span class="badge ${data.isSubscribed ? 'bg-success' : 'bg-secondary'}">
                                        ${data.isSubscribed ? 'Assinante' : 'Não assinante'}
                                    </span>
                                </div>
                                <small class="text-muted">
                                    Criado: ${formatDate(data.createdAt)}
                                </small>
                                <div class="mt-1">
                                    <small>
                                        ${data.songsUploaded} músicas • ${data.totalStreams.toLocaleString()} streams
                                    </small>
                                </div>
                            </div>
                        `;
                    }
                },
                {
                    data: 'id',
                    render: function(data, type, row) {
                        return `
                            <div class="action-buttons">
                                <button class="btn btn-outline-primary btn-sm view-user-btn" data-id="${data}" title="Ver detalhes">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-outline-success btn-sm message-user-btn" data-id="${data}" title="Enviar mensagem">
                                    <i class="bi bi-chat"></i>
                                </button>
                                <button class="btn btn-outline-info btn-sm profile-user-btn" data-id="${data}" title="Ver perfil">
                                    <i class="bi bi-person"></i>
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
                // Adicionar classes baseadas no status
                if (data.status === 'online') $(row).addClass('table-success');
                else if (data.status === 'away') $(row).addClass('table-warning');
                else if (data.status === 'busy') $(row).addClass('table-danger');
                else if (data.status === 'invisible') $(row).addClass('table-secondary');
                
                // Adicionar classe de atividade
                if (!isUserActive(data.lastActivity)) {
                    $(row).addClass('table-muted');
                }
            },
            initComplete: function() {
                updateStats();
                renderUserGrid();
                renderActivityStream();
                initRoleChart();
                loadCountryOptions();
                setupRealTimeFilters();
            }
        });
        
        updateResultsCount();
        
        // Adicionar evento para checkboxes
        $(document).on('change', '.user-checkbox', function() {
            const userId = $(this).data('id');
            if ($(this).is(':checked')) {
                selectedUsers.add(userId);
            } else {
                selectedUsers.delete(userId);
            }
            updateSelectedCount();
        });
    }
    
    // Renderizar grid de usuários
    function renderUserGrid() {
        const grid = $('#online-users-grid');
        grid.empty();
        
        // Filtrar apenas usuários ativos/online
        const activeUsers = sampleUsers.filter(user => 
            user.status === 'online' || isUserActive(user.lastActivity)
        );
        
        activeUsers.forEach(user => {
            const isActive = isUserActive(user.lastActivity);
            const statusClass = statusClasses[user.status] || 'status-online';
            const sessionTime = formatSessionDuration(getSessionTime(user.sessionStart));
            
            grid.append(`
                <div class="user-card ${user.status}">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="position-relative me-3">
                                <img src="${user.avatar}" alt="${user.fullName}" class="user-avatar-lg">
                                <span class="badge-online ${statusClass}"></span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            ${user.fullName}
                                            ${user.isVerified ? '<i class="bi bi-patch-check-fill text-primary ms-1" title="Verificado"></i>' : ''}
                                        </h6>
                                        <small class="text-muted">@${user.username}</small>
                                    </div>
                                    <span class="badge ${roleColors[user.role]} badge-role">${user.roleName}</span>
                                </div>
                                
                                <div class="mt-2">
                                    <small class="${statusClass}">
                                        <span class="online-status ${statusClass}"></span>
                                        ${statusTexts[user.status]} • ${sessionTime}
                                    </small>
                                </div>
                                
                                <div class="mt-2">
                                    <div class="fw-medium text-primary">
                                        <i class="bi bi-activity me-1"></i>
                                        ${user.currentActivity}
                                    </div>
                                    <small class="text-muted">${user.activityDetails}</small>
                                </div>
                                
                                <div class="mt-3">
                                    <div class="d-flex align-items-center">
                                        <i class="flag-icon flag-icon-${countryFlags[user.country] || 'globe'} me-2"></i>
                                        <small class="me-3">${user.city}, ${user.countryName}</small>
                                        <small class="ms-auto">
                                            <i class="bi ${getDeviceIcon(user.device)} me-1"></i>
                                            ${user.device}
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="mt-3 d-flex gap-2">
                                    <button class="btn btn-outline-primary btn-sm flex-fill view-user-btn" data-id="${user.id}">
                                        <i class="bi bi-eye me-1"></i> Ver
                                    </button>
                                    <button class="btn btn-outline-success btn-sm flex-fill message-user-btn" data-id="${user.id}">
                                        <i class="bi bi-chat me-1"></i> Mensagem
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        });
    }
    
    // Atualizar estatísticas
    function updateStats() {
        const onlineNow = sampleUsers.filter(u => u.status === 'online').length;
        const activeToday = sampleUsers.filter(u => {
            const lastLogin = new Date(u.lastLogin);
            const today = new Date();
            return lastLogin.toDateString() === today.toDateString();
        }).length;
        
        // Calcular novos usuários (últimos 30 dias)
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        const newUsers = sampleUsers.filter(u => {
            const createdAt = new Date(u.createdAt);
            return createdAt > thirtyDaysAgo;
        }).length;
        
        // Taxa de engajamento (usuários ativos / total usuários)
        const engagementRate = Math.round((onlineNow / sampleUsers.length) * 100);
        
        $('#online-now-count').text(onlineNow);
        $('#active-today').text(activeToday);
        $('#new-users').text(newUsers);
        $('#engagement-rate').text(`${engagementRate}%`);
        
        // Atualizar badge no menu
        $('#online-count-badge').text(onlineNow);
    }
    
    // Renderizar stream de atividades
    function renderActivityStream() {
        const stream = $('#activity-stream');
        stream.empty();
        
        recentActivities.forEach(activity => {
            const user = sampleUsers.find(u => u.id === activity.userId);
            if (user) {
                stream.append(`
                    <div class="activity-item">
                        <div class="flex-shrink-0 me-3">
                            <img src="${user.avatar}" alt="${user.username}" class="user-avatar-sm rounded-circle">
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>${user.fullName}</strong> 
                                    <span class="text-muted">${activity.action}</span>
                                </div>
                                <small class="activity-time">${activity.time}</small>
                            </div>
                            <div class="mt-1">
                                <span class="badge ${roleColors[user.role]} badge-role">${user.roleName}</span>
                                <small class="text-muted ms-2">
                                    <i class="bi ${activity.icon} me-1"></i>
                                    ${user.currentActivity}
                                </small>
                            </div>
                        </div>
                    </div>
                `);
            }
        });
    }
    
    // Inicializar gráfico de funções
    function initRoleChart() {
        const roleData = {};
        sampleUsers.forEach(user => {
            const role = user.role;
            roleData[role] = (roleData[role] || 0) + 1;
        });
        
        const ctx = document.createElement('canvas');
        $('#role-chart').html(ctx);
        
        roleChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(roleData).map(role => {
                    const user = sampleUsers.find(u => u.role === role);
                    return user ? user.roleName : role;
                }),
                datasets: [{
                    data: Object.values(roleData),
                    backgroundColor: [
                        '#ef4444', // admin
                        '#3b82f6', // moderator
                        '#10b981', // user
                        '#8b5cf6', // artist
                        '#f59e0b'  // manager
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
    
    // Carregar opções de países
    function loadCountryOptions() {
        const countries = [...new Set(sampleUsers.map(u => u.countryName))].sort();
        const filterSelect = $('#filter-country');
        const broadcastSelect = $('#broadcast-country');
        
        countries.forEach(country => {
            filterSelect.append(`<option value="${country}">${country}</option>`);
            broadcastSelect.append(`<option value="${country}">${country}</option>`);
        });
    }
    
    // Configurar filtros em tempo real
    function setupRealTimeFilters() {
        const filterIds = [
            'filter-name',
            'filter-role',
            'filter-country',
            'filter-status',
            'filter-activity'
        ];
        
        filterIds.forEach(filterId => {
            $(`#${filterId}`).on('keyup change', function() {
                applyFilters();
            });
        });
    }
    
    // Aplicar filtros
    function applyFilters() {
        usersTable.search('').columns().search('').draw();
        
        const name = $('#filter-name').val().toLowerCase();
        const role = $('#filter-role').val();
        const country = $('#filter-country').val();
        const status = $('#filter-status').val();
        const activity = $('#filter-activity').val();
        
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex, rowData) {
                const user = sampleUsers[dataIndex];
                const userActive = isUserActive(user.lastActivity);
                
                // Filtro por nome/usuário
                if (name && !user.fullName.toLowerCase().includes(name) && 
                    !user.username.toLowerCase().includes(name) &&
                    !user.email.toLowerCase().includes(name)) {
                    return false;
                }
                
                // Filtro por função
                if (role && user.role !== role) return false;
                
                // Filtro por país
                if (country && user.countryName !== country) return false;
                
                // Filtro por status
                if (status && user.status !== status) return false;
                
                // Filtro por atividade
                if (activity) {
                    switch(activity) {
                        case 'active':
                            if (!userActive) return false;
                            break;
                        case 'recent':
                            // Usuários ativos nos últimos 15 minutos
                            const fifteenMinutes = 15 * 60 * 1000;
                            const lastActive = new Date(user.lastActivity);
                            const now = new Date();
                            if ((now - lastActive) > fifteenMinutes) return false;
                            break;
                        case 'chatting':
                            if (!user.currentActivity.toLowerCase().includes('chat')) return false;
                            break;
                        case 'listening':
                            if (!user.currentActivity.toLowerCase().includes('ouvindo')) return false;
                            break;
                        case 'uploading':
                            if (!user.currentActivity.toLowerCase().includes('upload')) return false;
                            break;
                    }
                }
                
                return true;
            }
        );
        
        usersTable.draw();
        updateResultsCount();
        
        // Limpar filtros personalizados
        $.fn.dataTable.ext.search = [];
    }
    
    // Atualizar contagem de resultados
    function updateResultsCount() {
        const filteredCount = usersTable.rows({ search: 'applied' }).count();
        const totalCount = usersTable.rows().count();
        
        if (filteredCount === totalCount) {
            $('#users-results-count').text(`${totalCount} usuários`);
        } else {
            $('#users-results-count').text(`${filteredCount} de ${totalCount} usuários`);
        }
    }
    
    // Atualizar contagem de selecionados
    function updateSelectedCount() {
        $('#selected-users-count').text(`${selectedUsers.size} selecionados`);
    }
    
    // Inicializar
    initDataTable();
    
    // Configurar atualização automática
    function startAutoRefresh() {
        if (autoRefreshInterval) clearInterval(autoRefreshInterval);
        
        autoRefreshInterval = setInterval(() => {
            // Simular atualização de status
            sampleUsers.forEach(user => {
                // Simular mudanças de status aleatórias
                if (Math.random() < 0.1) { // 10% de chance de mudança
                    const statuses = ['online', 'away', 'busy'];
                    user.status = statuses[Math.floor(Math.random() * statuses.length)];
                }
                // Atualizar tempo de sessão
                user.sessionDuration = getSessionTime(user.sessionStart);
                // Atualizar última atividade
                if (user.status === 'online' && Math.random() < 0.3) {
                    user.lastActivity = new Date().toISOString();
                }
            });
            
            initDataTable();
            renderUserGrid();
            updateStats();
        }, 30000); // A cada 30 segundos
    }
    
    // Iniciar/Parar atualização automática
    $('#auto-refresh').change(function() {
        if ($(this).is(':checked')) {
            startAutoRefresh();
        } else {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            }
        }
    });
    
    // Iniciar atualização automática
    startAutoRefresh();
    
    // Botão de atualizar manual
    $('#refresh-users-btn').click(function() {
        // Simular novos usuários online
        const newUser = {
            id: sampleUsers.length + 1,
            username: `novo_usuario_${Math.floor(Math.random() * 1000)}`,
            email: `user${Math.floor(Math.random() * 1000)}@example.com`,
            fullName: `Usuário ${String.fromCharCode(65 + Math.floor(Math.random() * 26))}`,
            avatar: `https://randomuser.me/api/portraits/${Math.random() > 0.5 ? 'men' : 'women'}/${Math.floor(Math.random() * 99)}.jpg`,
            role: "user",
            roleName: "Usuário",
            country: "AO",
            countryName: "Angola",
            city: "Luanda",
            state: "Luanda",
            accountType: "basic",
            createdAt: new Date().toISOString(),
            lastLogin: new Date().toISOString(),
            status: "online",
            device: Math.random() > 0.5 ? "desktop" : "mobile",
            browser: "Chrome",
            ip: `192.168.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}`,
            sessionStart: new Date().toISOString(),
            sessionDuration: 0,
            currentActivity: "Explorando a plataforma",
            activityDetails: "Conhecendo novas funcionalidades",
            songsUploaded: 0,
            totalStreams: 0,
            isVerified: false,
            isSubscribed: false,
            plan: "free",
            lastActivity: new Date().toISOString()
        };
        
        sampleUsers.push(newUser);
        initDataTable();
        renderUserGrid();
        updateStats();
        
        showToast('info', 'Usuários Atualizados', 'Lista de usuários online foi atualizada.');
    });
    
    // Limpar filtros
    $('#clear-filters').click(function() {
        $('#filter-name').val('');
        $('#filter-role').val('');
        $('#filter-country').val('');
        $('#filter-status').val('');
        $('#filter-activity').val('');
        
        usersTable.search('').columns().search('').draw();
        updateResultsCount();
    });
    
    // Selecionar todos os usuários
    $('#select-all-header, #select-all-btn').click(function() {
        const isChecked = $(this).is(':checked');
        $('.user-checkbox').prop('checked', isChecked);
        
        if (isChecked) {
            const visibleUsers = usersTable.rows({ search: 'applied' }).data();
            visibleUsers.each(function(user) {
                selectedUsers.add(user.id);
            });
        } else {
            selectedUsers.clear();
        }
        updateSelectedCount();
    });
    
    // Ver detalhes do usuário
    $(document).on('click', '.view-user-btn, .view-user-btn-grid', function() {
        const userId = $(this).data('id');
        currentUser = sampleUsers.find(u => u.id === userId);
        
        if (currentUser) {
            const statusClass = statusClasses[currentUser.status] || 'status-online';
            const sessionTime = formatSessionDuration(getSessionTime(currentUser.sessionStart));
            
            $('#view-user-body').html(`
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="position-relative mb-3 d-inline-block">
                                    <img src="${currentUser.avatar}" alt="${currentUser.fullName}" class="user-avatar-lg">
                                    <span class="badge-online ${statusClass}"></span>
                                </div>
                                <h4>${currentUser.fullName}</h4>
                                <p class="text-muted">@${currentUser.username}</p>
                                
                                <div class="mb-3">
                                    <span class="badge ${roleColors[currentUser.role]} badge-role">${currentUser.roleName}</span>
                                    <span class="badge ${currentUser.accountType === 'premium' ? 'bg-warning' : 'bg-secondary'}">
                                        ${currentUser.accountType === 'premium' ? 'Premium' : 'Basic'}
                                    </span>
                                    ${currentUser.isVerified ? '<span class="badge bg-primary">Verificado</span>' : ''}
                                </div>
                                
                                <div class="d-flex justify-content-center gap-2 mb-3">
                                    <div class="text-center">
                                        <div class="fw-bold">${currentUser.songsUploaded}</div>
                                        <small class="text-muted">Músicas</small>
                                    </div>
                                    <div class="text-center">
                                        <div class="fw-bold">${currentUser.totalStreams.toLocaleString()}</div>
                                        <small class="text-muted">Streams</small>
                                    </div>
                                    <div class="text-center">
                                        <div class="fw-bold">${sessionTime}</div>
                                        <small class="text-muted">Sessão</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6><i class="bi bi-info-circle me-2"></i>Informações</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <th style='color:#718096!important'>E-mail:</th>
                                                <td>${currentUser.email}</td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>Status:</th>
                                                <td>
                                                    <span class="${statusClass}">
                                                        <span class="online-status ${statusClass}"></span>
                                                        ${statusTexts[currentUser.status]}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>Criado em:</th>
                                                <td>${formatDate(currentUser.createdAt)}</td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>Último login:</th>
                                                <td>${formatDate(currentUser.lastLogin)}</td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>IP Atual:</th>
                                                <td><code>${currentUser.ip}</code></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6><i class="bi bi-geo-alt me-2"></i>Localização</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <th style='color:#718096!important'>País:</th>
                                                <td>
                                                    <i class="flag-icon flag-icon-${countryFlags[currentUser.country] || 'globe'}"></i>
                                                    ${currentUser.countryName}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>Cidade:</th>
                                                <td>${currentUser.city}</td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>Estado:</th>
                                                <td>${currentUser.state}</td>
                                            </tr>
                                            <tr>
                                                <th style='color:#718096!important'>Fuso Horário:</th>
                                                <td>UTC+1</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6><i class="bi bi-laptop me-2"></i>Dispositivo & Atividade</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th style='color:#718096!important'>Dispositivo:</th>
                                        <td>
                                            <i class="bi ${getDeviceIcon(currentUser.device)}"></i>
                                            ${currentUser.device}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style='color:#718096!important'>Navegador:</th>
                                        <td>
                                            <i class="bi ${getBrowserIcon(currentUser.browser)}"></i>
                                            ${currentUser.browser}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style='color:#718096!important'>Atividade Atual:</th>
                                        <td class="text-primary">
                                            <i class="bi bi-activity me-1"></i>
                                            ${currentUser.currentActivity}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style='color:#718096!important'>Detalhes:</th>
                                        <td>${currentUser.activityDetails}</td>
                                    </tr>
                                    <tr>
                                        <th style='color:#718096!important'>Tempo Online:</th>
                                        <td>${sessionTime}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <h6><i class="bi bi-bar-chart me-2"></i>Estatísticas</h6>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="fw-bold h5">${currentUser.songsUploaded}</div>
                                        <small class="text-muted">Uploads</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold h5">${currentUser.totalStreams.toLocaleString()}</div>
                                        <small class="text-muted">Streams</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold h5">${currentUser.isSubscribed ? 'Sim' : 'Não'}</div>
                                        <small class="text-muted">Assinante</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            $('#viewUserModal').modal('show');
        }
    });
    
    // Configurar modal de anúncio
    $('#broadcast-audience').change(function() {
        const audience = $(this).val();
        $('#broadcast-role-select').hide();
        $('#broadcast-country-select').hide();
        
        if (audience === 'role') {
            $('#broadcast-role-select').show();
        } else if (audience === 'country') {
            $('#broadcast-country-select').show();
        }
        
        updateRecipientsCount();
    });
    
    // Atualizar contagem de destinatários
    function updateRecipientsCount() {
        const audience = $('#broadcast-audience').val();
        let count = 0;
        
        if (audience === 'all') {
            count = sampleUsers.length;
        } else if (audience === 'selected') {
            count = selectedUsers.size;
        } else if (audience === 'role') {
            const role = $('#broadcast-role').val();
            count = sampleUsers.filter(u => u.role === role).length;
        } else if (audience === 'country') {
            const country = $('#broadcast-country').val();
            count = sampleUsers.filter(u => u.countryName === country).length;
        }
        
        $('#recipients-count').text(count);
    }
    
    // Enviar anúncio
    $('#broadcast-form').submit(function(e) {
        e.preventDefault();
        
        const type = $('#broadcast-type').val();
        const message = $('#broadcast-message').val();
        const audience = $('#broadcast-audience').val();
        const recipients = $('#recipients-count').text();
        
        // Simular envio
        setTimeout(() => {
            $('#broadcastModal').modal('hide');
            $('#broadcast-message').val('');
            showToast('success', 'Anúncio Enviado', `Mensagem enviada para ${recipients} usuário(s).`);
        }, 1000);
    });
    
    // Enviar mensagem direta
    $(document).on('click', '.message-user-btn, .message-user-btn-grid', function() {
        const userId = $(this).data('id');
        currentUser = sampleUsers.find(u => u.id === userId);
        
        if (currentUser) {
            $('#message-recipient').val(`${currentUser.fullName} (@${currentUser.username})`);
            $('#messageModal').modal('show');
        }
    });
    
    $('#message-form').submit(function(e) {
        e.preventDefault();
        
        const subject = $('#message-subject').val();
        const content = $('#message-content').val();
        const copyToEmail = $('#copy-to-email').is(':checked');
        
        // Simular envio
        setTimeout(() => {
            $('#messageModal').modal('hide');
            $('#message-subject').val('');
            $('#message-content').val('');
            showToast('success', 'Mensagem Enviada', `Mensagem enviada para ${currentUser.fullName}.`);
        }, 1000);
    });
    
    // Ver perfil
    $(document).on('click', '.profile-user-btn, .view-profile-btn', function() {
        showToast('info', 'Abrir Perfil', 'Redirecionando para o perfil do usuário...');
        // Em implementação real, redirecionaria para a página de perfil
    });
    
    // Enviar mensagem para selecionados
    $('#message-selected-btn').click(function() {
        if (selectedUsers.size === 0) {
            showToast('warning', 'Nenhum usuário selecionado', 'Selecione pelo menos um usuário.');
            return;
        }
        
        // Simular envio para múltiplos usuários
        showToast('success', 'Mensagens Enviadas', `${selectedUsers.size} mensagem(s) enviada(s) com sucesso.`);
    });
    
    // Notificar selecionados
    $('#notify-selected-btn').click(function() {
        if (selectedUsers.size === 0) {
            showToast('warning', 'Nenhum usuário selecionado', 'Selecione pelo menos um usuário.');
            return;
        }
        
        // Simular notificação
        showToast('success', 'Notificações Enviadas', `${selectedUsers.size} notificação(ões) enviada(s).`);
    });
    
    // Exportar lista
    $('#export-list-btn').click(function() {
        const data = sampleUsers.map(user => ({
            Nome: user.fullName,
            Usuário: user.username,
            Função: user.roleName,
            email: user.email,
            País: user.countryName,
            Cidade: user.city,
            Status: statusTexts[user.status],
            'Última Atividade': formatDate(user.lastActivity),
            'Sessão': formatSessionDuration(user.sessionDuration)
        }));
        
        const csv = convertToCSV(data);
        downloadCSV(csv, 'usuarios_online.csv');
        showToast('success', 'Lista Exportada', 'Arquivo CSV baixado com sucesso.');
    });
    
    // Funções utilitárias
    function convertToCSV(data) {
        const headers = Object.keys(data[0]);
        const rows = data.map(obj => headers.map(header => `"${obj[header]}"`).join(','));
        return [headers.join(','), ...rows].join('\n');
    }
    
    function downloadCSV(csv, filename) {
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
    
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