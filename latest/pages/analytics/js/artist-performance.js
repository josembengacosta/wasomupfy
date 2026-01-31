// artist-performance.js - Sistema de Desempenho por Artista

// Dados de exemplo de desempenho
const performanceData = [
    {
        id: 1,
        account: "pro",
        accountName: "Conta Pro",
        track: "Melodia Tropical",
        artist: "Carlos Dias",
        artistAvatar: "https://randomuser.me/api/portraits/men/32.jpg",
        streams: 150000,
        platform: "deezer",
        platformName: "Deezer",
        playlist: "top50",
        playlistName: "Top 50",
        territory: "AO",
        territoryName: "Angola",
        playDate: "2025-06-15",
        revenue: 450.00,
        trends: {
            week: 12.5,
            month: 25.3,
            year: 45.8
        },
        details: {
            dailyAverage: 5000,
            peakDay: "2025-06-20",
            peakStreams: 8500,
            listenerCities: ["Luanda", "Benguela", "Huambo"],
            demographics: {
                age18_24: 45,
                age25_34: 35,
                age35_44: 15,
                age45_plus: 5
            }
        }
    },
    {
        id: 2,
        account: "basic",
        accountName: "Conta Basic",
        track: "Noite em Luanda",
        artist: "Ana Silva",
        artistAvatar: "https://randomuser.me/api/portraits/women/44.jpg",
        streams: 75000,
        platform: "apple",
        platformName: "Apple Music",
        playlist: "editorial",
        playlistName: "Editorial",
        territory: "AO",
        territoryName: "Angola",
        playDate: "2025-06-14",
        revenue: 225.00,
        trends: {
            week: 8.2,
            month: 18.7,
            year: 32.5
        },
        details: {
            dailyAverage: 2500,
            peakDay: "2025-06-18",
            peakStreams: 4200,
            listenerCities: ["Luanda", "Lobito", "Lubango"],
            demographics: {
                age18_24: 38,
                age25_34: 42,
                age35_44: 12,
                age45_plus: 8
            }
        }
    },
    {
        id: 3,
        account: "international",
        accountName: "Conta Internacional",
        track: "Saudades de Luanda",
        artist: "Banda Maravilha",
        artistAvatar: "https://randomuser.me/api/portraits/men/67.jpg",
        streams: 250000,
        platform: "spotify",
        platformName: "Spotify",
        playlist: "viral",
        playlistName: "Viral",
        territory: "PT",
        territoryName: "Portugal",
        playDate: "2025-06-18",
        revenue: 750.00,
        trends: {
            week: 15.8,
            month: 32.4,
            year: 68.9
        },
        details: {
            dailyAverage: 8333,
            peakDay: "2025-06-22",
            peakStreams: 12500,
            listenerCities: ["Lisboa", "Porto", "Coimbra"],
            demographics: {
                age18_24: 52,
                age25_34: 28,
                age35_44: 15,
                age45_plus: 5
            }
        }
    },
    {
        id: 4,
        account: "intl",
        accountName: "Conta Intl",
        track: "Saudade",
        artist: "Marco Pereira",
        artistAvatar: "https://randomuser.me/api/portraits/men/22.jpg",
        streams: 220000,
        platform: "discogs",
        platformName: "Discogs",
        playlist: "none",
        playlistName: "Sem playlist",
        territory: "BR",
        territoryName: "Brasil",
        playDate: "2025-06-10",
        revenue: 660.00,
        trends: {
            week: 9.3,
            month: 21.6,
            year: 41.2
        },
        details: {
            dailyAverage: 7333,
            peakDay: "2025-06-15",
            peakStreams: 11000,
            listenerCities: ["São Paulo", "Rio de Janeiro", "Belo Horizonte"],
            demographics: {
                age18_24: 41,
                age25_34: 36,
                age35_44: 18,
                age45_plus: 5
            }
        }
    },
    {
        id: 5,
        account: "pro",
        accountName: "Conta Pro",
        track: "Ritmo Quente",
        artist: "DJ Kamba",
        artistAvatar: "https://randomuser.me/api/portraits/men/45.jpg",
        streams: 180000,
        platform: "spotify",
        platformName: "Spotify",
        playlist: "new_music",
        playlistName: "New Music Friday",
        territory: "MZ",
        territoryName: "Moçambique",
        playDate: "2025-06-22",
        revenue: 540.00,
        trends: {
            week: 18.2,
            month: 42.8,
            year: 78.5
        },
        details: {
            dailyAverage: 6000,
            peakDay: "2025-06-25",
            peakStreams: 9500,
            listenerCities: ["Maputo", "Beira", "Nampula"],
            demographics: {
                age18_24: 48,
                age25_34: 32,
                age35_44: 15,
                age45_plus: 5
            }
        }
    },
    {
        id: 6,
        account: "basic",
        accountName: "Conta Basic",
        track: "Mar Azul",
        artist: "Sara Mendes",
        artistAvatar: "https://randomuser.me/api/portraits/women/28.jpg",
        streams: 95000,
        platform: "deezer",
        platformName: "Deezer",
        playlist: "radar",
        playlistName: "Radar",
        territory: "PT",
        territoryName: "Portugal",
        playDate: "2025-06-20",
        revenue: 285.00,
        trends: {
            week: 7.5,
            month: 16.3,
            year: 29.8
        },
        details: {
            dailyAverage: 3166,
            peakDay: "2025-06-23",
            peakStreams: 5200,
            listenerCities: ["Lisboa", "Faro", "Aveiro"],
            demographics: {
                age18_24: 39,
                age25_34: 40,
                age35_44: 16,
                age45_plus: 5
            }
        }
    },
    {
        id: 7,
        account: "international",
        accountName: "Conta Internacional",
        track: "African Sun",
        artist: "Tropical Band",
        artistAvatar: "https://randomuser.me/api/portraits/men/55.jpg",
        streams: 320000,
        platform: "youtube",
        platformName: "YouTube",
        playlist: "viral",
        playlistName: "Viral",
        territory: "US",
        territoryName: "Estados Unidos",
        playDate: "2025-06-25",
        revenue: 960.00,
        trends: {
            week: 22.4,
            month: 48.7,
            year: 92.3
        },
        details: {
            dailyAverage: 10666,
            peakDay: "2025-06-28",
            peakStreams: 18500,
            listenerCities: ["Nova Iorque", "Los Angeles", "Miami"],
            demographics: {
                age18_24: 55,
                age25_34: 30,
                age35_44: 10,
                age45_plus: 5
            }
        }
    },
    {
        id: 8,
        account: "intl",
        accountName: "Conta Intl",
        track: "Cidade Branca",
        artist: "Luanda Boys",
        artistAvatar: "https://randomuser.me/api/portraits/men/38.jpg",
        streams: 145000,
        platform: "tidal",
        platformName: "Tidal",
        playlist: "editorial",
        playlistName: "Editorial",
        territory: "AO",
        territoryName: "Angola",
        playDate: "2025-06-12",
        revenue: 435.00,
        trends: {
            week: 6.8,
            month: 14.9,
            year: 27.4
        },
        details: {
            dailyAverage: 4833,
            peakDay: "2025-06-16",
            peakStreams: 7200,
            listenerCities: ["Luanda", "Cabinda", "Soyo"],
            demographics: {
                age18_24: 42,
                age25_34: 38,
                age35_44: 15,
                age45_plus: 5
            }
        }
    },
    {
        id: 9,
        account: "pro",
        accountName: "Conta Pro",
        track: "Kizomba Nights",
        artist: "Nelson Beat",
        artistAvatar: "https://randomuser.me/api/portraits/men/29.jpg",
        streams: 275000,
        platform: "amazon",
        platformName: "Amazon Music",
        playlist: "top50",
        playlistName: "Top 50",
        territory: "BR",
        territoryName: "Brasil",
        playDate: "2025-06-28",
        revenue: 825.00,
        trends: {
            week: 14.7,
            month: 31.2,
            year: 65.8
        },
        details: {
            dailyAverage: 9166,
            peakDay: "2025-07-01",
            peakStreams: 13500,
            listenerCities: ["São Paulo", "Salvador", "Fortaleza"],
            demographics: {
                age18_24: 46,
                age25_34: 34,
                age35_44: 15,
                age45_plus: 5
            }
        }
    },
    {
        id: 10,
        account: "basic",
        accountName: "Conta Basic",
        track: "Semba Tradicional",
        artist: "Mama Africa",
        artistAvatar: "https://randomuser.me/api/portraits/women/33.jpg",
        streams: 85000,
        platform: "spotify",
        platformName: "Spotify",
        playlist: "none",
        playlistName: "Sem playlist",
        territory: "MZ",
        territoryName: "Moçambique",
        playDate: "2025-06-17",
        revenue: 255.00,
        trends: {
            week: 5.9,
            month: 12.8,
            year: 23.6
        },
        details: {
            dailyAverage: 2833,
            peakDay: "2025-06-20",
            peakStreams: 4800,
            listenerCities: ["Maputo", "Quelimane", "Tete"],
            demographics: {
                age18_24: 37,
                age25_34: 41,
                age35_44: 17,
                age45_plus: 5
            }
        }
    }
];

// Mapeamento de plataformas para ícones e cores
const platformInfo = {
    spotify: { icon: "bi-spotify", color: "platform-spotify" },
    deezer: { icon: "bi-music-note-beamed", color: "platform-deezer" },
    apple: { icon: "bi-apple", color: "platform-apple" },
    youtube: { icon: "bi-youtube", color: "platform-youtube" },
    discogs: { icon: "bi-vinyl", color: "platform-discogs" },
    tidal: { icon: "bi-droplet", color: "platform-tidal" },
    amazon: { icon: "bi-amazon", color: "platform-amazon" }
};

// Mapeamento de contas para classes
const accountClasses = {
    pro: "account-pro",
    basic: "account-basic",
    international: "account-international",
    intl: "account-intl"
};

// Mapeamento de territórios para bandeiras
const territoryFlags = {
    AO: "ao",
    PT: "pt",
    BR: "br",
    MZ: "mz",
    US: "us",
    ES: "es",
    FR: "fr"
};

// Formatar número com separadores
function formatNumber(num) {
    return num.toLocaleString('pt-BR');
}

// Formatar data
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

// Formatar moeda
function formatCurrency(amount) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Calcular tendência
function getTrendIcon(trend) {
    if (trend > 0) return '<i class="bi bi-arrow-up-right trend-up"></i>';
    if (trend < 0) return '<i class="bi bi-arrow-down-right trend-down"></i>';
    return '<i class="bi bi-dash-lg trend-neutral"></i>';
}

// Calcular total de streams
function calculateTotalStreams(data) {
    return data.reduce((sum, item) => sum + item.streams, 0);
}

// Calcular receita total
function calculateTotalRevenue(data) {
    return data.reduce((sum, item) => sum + item.revenue, 0);
}

// Calcular artistas únicos
function calculateUniqueArtists(data) {
    const artists = [...new Set(data.map(item => item.artist))];
    return artists.length;
}

// Calcular músicas em playlists
function calculatePlaylistTracks(data) {
    return data.filter(item => item.playlist !== 'none').length;
}

$(document).ready(function() {
    let performanceTable;
    let selectedTracks = new Set();
    let performanceChart = null;
    let currentFilters = {};
    
    // Inicializar DataTable
    function initDataTable() {
        if ($.fn.DataTable.isDataTable('#performance-table')) {
            performanceTable.destroy();
        }
        
        performanceTable = $('#performance-table').DataTable({
            data: performanceData,
            columns: [
                {
                    data: 'id',
                    render: function(data, type, row) {
                        return `
                            <input type="checkbox" class="form-check-input track-checkbox" data-id="${data}">
                        `;
                    },
                    orderable: false,
                    width: "30px"
                },
                { 
                    data: 'id'
                },
                { 
                    data: 'account',
                    render: function(data, type, row) {
                        return `<span class="badge ${accountClasses[data]} account-badge">${row.accountName}</span>`;
                    }
                },
                { 
                    data: null,
                    render: function(data) {
                        return `
                            <div class="d-flex align-items-center">
                                <img src="${data.artistAvatar}" alt="${data.artist}" class="artist-avatar">
                                <div>
                                    <div class="fw-medium">${data.track}</div>
                                    <small class="text-muted">${data.artist}</small>
                                </div>
                            </div>
                        `;
                    }
                },
                { 
                    data: 'streams',
                    render: function(data, type, row) {
                        const formatted = formatNumber(data);
                        const trend = getTrendIcon(row.trends.week);
                        return `
                            <div>
                                <div class="stream-count">${formatted}</div>
                                <small class="trend-indicator">
                                    ${trend} ${Math.abs(row.trends.week)}%
                                </small>
                            </div>
                        `;
                    }
                },
                { 
                    data: 'platform',
                    render: function(data, type, row) {
                        const info = platformInfo[data] || { icon: 'bi-question-circle', color: 'bg-secondary' };
                        return `
                            <span class="platform-badge ${info.color}">
                                <i class="bi ${info.icon}"></i>
                                ${row.platformName}
                            </span>
                        `;
                    }
                },
                { 
                    data: 'playlist',
                    render: function(data, type, row) {
                        if (data === 'none') {
                            return '<span class="text-muted">Nenhuma</span>';
                        }
                        return `<span class="playlist-badge">${row.playlistName}</span>`;
                    }
                },
                { 
                    data: 'territory',
                    render: function(data, type, row) {
                        const flagCode = territoryFlags[data] || 'globe';
                        return `
                            <div>
                                <i class="flag-icon flag-icon-${flagCode} territory-flag"></i>
                                ${row.territoryName}
                            </div>
                        `;
                    }
                },
                { 
                    data: 'playDate',
                    render: function(data) {
                        return formatDate(data);
                    }
                },
                {
                    data: 'id',
                    render: function(data, type, row) {
                        return `
                            <div class="action-buttons">
                                <button class="btn btn-outline-primary btn-sm view-details-btn" data-id="${data}" title="Ver detalhes">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-outline-success btn-sm compare-btn" data-id="${data}" title="Comparar">
                                    <i class="bi bi-bar-chart"></i>
                                </button>
                                <button class="btn btn-outline-info btn-sm share-btn" data-id="${data}" title="Compartilhar">
                                    <i class="bi bi-share"></i>
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
            order: [[3, 'desc']], // Ordenar por streams por padrão
            initComplete: function() {
                updateStats();
                renderTopArtists();
                renderPlatformDistribution();
                initPlatformSelector();
                setupEventListeners();
                updateResultsCount();
            }
        });
        
        // Adicionar evento para checkboxes
        $(document).on('change', '.track-checkbox', function() {
            const trackId = $(this).data('id');
            if ($(this).is(':checked')) {
                selectedTracks.add(trackId);
            } else {
                selectedTracks.delete(trackId);
            }
            updateSelectedCount();
        });
    }
    
    // Atualizar estatísticas
    function updateStats() {
        const totalStreams = calculateTotalStreams(performanceData);
        const totalRevenue = calculateTotalRevenue(performanceData);
        const activeArtists = calculateUniqueArtists(performanceData);
        const playlistTracks = calculatePlaylistTracks(performanceData);
        
        $('#total-streams').text(formatNumber(totalStreams));
        $('#total-revenue').text(formatCurrency(totalRevenue));
        $('#active-artists').text(activeArtists);
        $('#playlist-tracks').text(playlistTracks);
    }
    
    // Inicializar seletor de plataformas
    function initPlatformSelector() {
        const selector = $('#platform-selector');
        selector.empty();
        
        Object.entries(platformInfo).forEach(([key, info]) => {
            const platformName = key.charAt(0).toUpperCase() + key.slice(1);
            selector.append(`
                <div class="platform-option" data-platform="${key}">
                    <i class="bi ${info.icon}"></i>
                    <span>${platformName}</span>
                </div>
            `);
        });
        
        // Adicionar evento de clique
        $('.platform-option').click(function() {
            $(this).toggleClass('selected');
        });
    }
    
    // Configurar listeners de eventos
    function setupEventListeners() {
        // Aplicar filtros
        $('#apply-filters').click(applyFilters);
        
        // Limpar filtros
        $('#clear-filters').click(clearFilters);
        
        // Filtros rápidos
        $('.filter-quick').click(function() {
            $('.filter-quick').removeAttr('data-active');
            $(this).attr('data-active', 'true');
            applyQuickFilter($(this).data('filter'));
        });
        
        // Alternar filtros avançados
        $('#toggle-advanced-filters').click(function() {
            const filters = $('#advanced-filters');
            const icon = $(this).find('i');
            filters.toggleClass('show');
            icon.toggleClass('bi-chevron-down bi-chevron-up');
        });
        
        // Botão de comparar
        $('#compare-artists-btn').click(function() {
            $(this).toggleClass('active');
            $('#comparison-panel').toggle();
            if ($(this).hasClass('active')) {
                showComparison();
            }
        });
        
        // Fechar comparação
        $('#close-comparison').click(function() {
            $('#comparison-panel').hide();
            $('#compare-artists-btn').removeClass('active');
        });
        
        // Exportar dados
        $('#export-data-btn').click(function() {
            updateExportItemCount();
        });
        
        // Configurar exportação de data range
        $('#export-date-range').change(function() {
            if ($(this).val() === 'custom') {
                $('#custom-date-range').show();
            } else {
                $('#custom-date-range').hide();
            }
        });
    }
    
    // Aplicar filtros
    function applyFilters() {
        currentFilters = {
            artist: $('#filter-artist').val().toLowerCase(),
            track: $('#filter-track').val().toLowerCase(),
            account: $('#filter-account').val(),
            platform: $('#filter-platform').val(),
            territory: $('#filter-territory').val(),
            playlist: $('#filter-playlist').val(),
            dateFrom: $('#date-from').val(),
            dateTo: $('#date-to').val(),
            streamsMin: $('#streams-min').val(),
            streamsMax: $('#streams-max').val(),
            sortBy: $('#sort-by').val(),
            groupBy: $('#group-by').val(),
            selectedPlatforms: $('.platform-option.selected').map(function() {
                return $(this).data('platform');
            }).get()
        };
        
        performanceTable.search('').columns().search('').draw();
        
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex, rowData) {
                const item = performanceData[dataIndex];
                
                // Filtro por artista
                if (currentFilters.artist && 
                    !item.artist.toLowerCase().includes(currentFilters.artist)) {
                    return false;
                }
                
                // Filtro por música
                if (currentFilters.track && 
                    !item.track.toLowerCase().includes(currentFilters.track)) {
                    return false;
                }
                
                // Filtro por conta
                if (currentFilters.account && item.account !== currentFilters.account) {
                    return false;
                }
                
                // Filtro por plataforma
                if (currentFilters.platform && item.platform !== currentFilters.platform) {
                    return false;
                }
                
                // Filtro por plataformas selecionadas
                if (currentFilters.selectedPlatforms.length > 0 &&
                    !currentFilters.selectedPlatforms.includes(item.platform)) {
                    return false;
                }
                
                // Filtro por território
                if (currentFilters.territory && item.territory !== currentFilters.territory) {
                    return false;
                }
                
                // Filtro por playlist
                if (currentFilters.playlist && item.playlist !== currentFilters.playlist) {
                    return false;
                }
                
                // Filtro por data
                if (currentFilters.dateFrom && item.playDate < currentFilters.dateFrom) {
                    return false;
                }
                if (currentFilters.dateTo && item.playDate > currentFilters.dateTo) {
                    return false;
                }
                
                // Filtro por streams
                if (currentFilters.streamsMin && item.streams < parseInt(currentFilters.streamsMin)) {
                    return false;
                }
                if (currentFilters.streamsMax && item.streams > parseInt(currentFilters.streamsMax)) {
                    return false;
                }
                
                return true;
            }
        );
        
        // Ordenar dados
        if (currentFilters.sortBy) {
            let column, direction;
            switch(currentFilters.sortBy) {
                case 'streams':
                    column = 3; // Coluna de streams
                    direction = 'desc';
                    break;
                case 'streams_asc':
                    column = 3;
                    direction = 'asc';
                    break;
                case 'date':
                    column = 7; // Coluna de data
                    direction = 'desc';
                    break;
                case 'date_asc':
                    column = 7;
                    direction = 'asc';
                    break;
                case 'artist':
                    column = 2; // Coluna de artista
                    direction = 'asc';
                    break;
                case 'track':
                    column = 2; // Coluna de música/artista
                    direction = 'asc';
                    break;
            }
            performanceTable.order([column, direction]).draw();
        } else {
            performanceTable.draw();
        }
        
        updateResultsCount();
        updateStats();
        
        // Limpar filtros personalizados
        $.fn.dataTable.ext.search = [];
    }
    
    // Aplicar filtro rápido
    function applyQuickFilter(filterType) {
        const today = new Date();
        let startDate, endDate;
        
        switch(filterType) {
            case 'today':
                startDate = today.toISOString().split('T')[0];
                endDate = startDate;
                break;
            case 'week':
                const startOfWeek = new Date(today);
                startOfWeek.setDate(today.getDate() - today.getDay());
                startDate = startOfWeek.toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'month':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1)
                    .toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'quarter':
                const quarter = Math.floor(today.getMonth() / 3);
                const quarterStart = new Date(today.getFullYear(), quarter * 3, 1);
                startDate = quarterStart.toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'year':
                startDate = new Date(today.getFullYear(), 0, 1)
                    .toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'all':
                // Remover filtro de data
                $('#date-from').val('');
                $('#date-to').val('');
                applyFilters();
                return;
        }
        
        $('#date-from').val(startDate);
        $('#date-to').val(endDate);
        applyFilters();
    }
    
    // Limpar filtros
    function clearFilters() {
        $('#filter-artist').val('');
        $('#filter-track').val('');
        $('#filter-account').val('');
        $('#filter-platform').val('');
        $('#filter-territory').val('');
        $('#filter-playlist').val('');
        $('#date-from').val('2025-06-01');
        $('#date-to').val('2025-06-30');
        $('#streams-min').val('');
        $('#streams-max').val('');
        $('#sort-by').val('streams');
        $('#group-by').val('none');
        $('.platform-option').removeClass('selected');
        $('.filter-quick').removeAttr('data-active');
        
        currentFilters = {};
        performanceTable.search('').columns().search('').draw();
        updateResultsCount();
        updateStats();
    }
    
    // Atualizar contagem de resultados
    function updateResultsCount() {
        const filteredCount = performanceTable.rows({ search: 'applied' }).count();
        const totalCount = performanceTable.rows().count();
        
        if (filteredCount === totalCount) {
            $('#performance-results-count').text(`${totalCount} resultados`);
        } else {
            $('#performance-results-count').text(`${filteredCount} de ${totalCount} resultados`);
        }
    }
    
    // Atualizar contagem de selecionados
    function updateSelectedCount() {
        $('#selected-tracks-count').text(`${selectedTracks.size} selecionados`);
    }
    
    // Atualizar contagem de itens para exportação
    function updateExportItemCount() {
        const count = performanceTable.rows({ search: 'applied' }).count();
        $('#export-item-count').text(count);
    }
    
    // Renderizar top artistas
    function renderTopArtists() {
        const artists = {};
        
        performanceData.forEach(item => {
            if (!artists[item.artist]) {
                artists[item.artist] = {
                    streams: 0,
                    tracks: 0,
                    revenue: 0,
                    avatar: item.artistAvatar
                };
            }
            artists[item.artist].streams += item.streams;
            artists[item.artist].tracks += 1;
            artists[item.artist].revenue += item.revenue;
        });
        
        // Ordenar por streams
        const topArtists = Object.entries(artists)
            .sort((a, b) => b[1].streams - a[1].streams)
            .slice(0, 5);
        
        const container = $('#top-artists');
        container.empty();
        
        topArtists.forEach(([artist, data], index) => {
            container.append(`
                <div class="d-flex align-items-center mb-3">
                    <div class="position-relative me-3">
                        <span class="position-absolute top-0 start-100 translate-middle badge bg-primary">
                            ${index + 1}
                        </span>
                        <img src="${data.avatar}" alt="${artist}" class="artist-avatar">
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-medium">${artist}</div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">${formatNumber(data.streams)} streams</small>
                            <small class="text-success">${formatCurrency(data.revenue)}</small>
                        </div>
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: ${(data.streams / topArtists[0][1].streams) * 100}%">
                            </div>
                        </div>
                    </div>
                </div>
            `);
        });
    }
    
    // Renderizar distribuição por plataforma
    function renderPlatformDistribution() {
        const platforms = {};
        
        performanceData.forEach(item => {
            if (!platforms[item.platform]) {
                platforms[item.platform] = {
                    streams: 0,
                    count: 0,
                    revenue: 0
                };
            }
            platforms[item.platform].streams += item.streams;
            platforms[item.platform].count += 1;
            platforms[item.platform].revenue += item.revenue;
        });
        
        const ctx = document.createElement('canvas');
        $('#platform-distribution').html(ctx);
        
        const chartData = {
            labels: Object.keys(platforms).map(p => p.charAt(0).toUpperCase() + p.slice(1)),
            datasets: [{
                data: Object.values(platforms).map(p => p.streams),
                backgroundColor: [
                    '#1DB954', // Spotify
                    '#00C7F2', // Deezer
                    '#000000', // Apple
                    '#FF0000', // YouTube
                    '#333333', // Discogs
                    '#00FFFF', // Tidal
                    '#FF9900'  // Amazon
                ]
            }]
        };
        
        new Chart(ctx, {
            type: 'doughnut',
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const platform = context.label;
                                const data = platforms[platform.toLowerCase()];
                                return [
                                    `Streams: ${formatNumber(data.streams)}`,
                                    `Músicas: ${data.count}`,
                                    `Receita: ${formatCurrency(data.revenue)}`
                                ];
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Mostrar comparação
    function showComparison() {
        const selected = Array.from(selectedTracks);
        const content = $('#comparison-content');
        content.empty();
        
        if (selected.length === 0) {
            content.html(`
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Selecione pelo menos 2 itens para comparar.
                </div>
            `);
            return;
        }
        
        if (selected.length < 2) {
            content.html(`
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Selecione pelo menos 2 itens para comparar.
                </div>
            `);
            return;
        }
        
        const items = selected.map(id => 
            performanceData.find(item => item.id === id)
        );
        
        // Criar tabela de comparação
        let comparisonTable = `
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Métrica</th>
                            ${items.map(item => `<th>${item.track}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Artista</strong></td>
                            ${items.map(item => `<td>${item.artist}</td>`).join('')}
                        </tr>
                        <tr>
                            <td><strong>Streams</strong></td>
                            ${items.map(item => `<td class="stream-count">${formatNumber(item.streams)}</td>`).join('')}
                        </tr>
                        <tr>
                            <td><strong>Receita</strong></td>
                            ${items.map(item => `<td>${formatCurrency(item.revenue)}</td>`).join('')}
                        </tr>
                        <tr>
                            <td><strong>Plataforma</strong></td>
                            ${items.map(item => `<td>${item.platformName}</td>`).join('')}
                        </tr>
                        <tr>
                            <td><strong>Território</strong></td>
                            ${items.map(item => {
                                const flagCode = territoryFlags[item.territory] || 'globe';
                                return `<td><i class="flag-icon flag-icon-${flagCode}"></i> ${item.territoryName}</td>`;
                            }).join('')}
                        </tr>
                        <tr>
                            <td><strong>Tendência (semana)</strong></td>
                            ${items.map(item => {
                                const trend = getTrendIcon(item.trends.week);
                                return `<td>${trend} ${Math.abs(item.trends.week)}%</td>`;
                            }).join('')}
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <canvas id="comparison-chart"></canvas>
            </div>
        `;
        
        content.html(comparisonTable);
        
        // Criar gráfico de comparação
        setTimeout(() => {
            const chartCtx = document.getElementById('comparison-chart');
            if (chartCtx) {
                new Chart(chartCtx, {
                    type: 'bar',
                    data: {
                        labels: items.map(item => item.track),
                        datasets: [
                            {
                                label: 'Streams',
                                data: items.map(item => item.streams),
                                backgroundColor: '#6c5ce7',
                                borderColor: '#5649c0',
                                borderWidth: 1
                            },
                            {
                                label: 'Receita (USD)',
                                data: items.map(item => item.revenue * 100), // Escalar para visualização
                                backgroundColor: '#10b981',
                                borderColor: '#0da271',
                                borderWidth: 1,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Streams'
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Receita (x100 USD)'
                                },
                                grid: {
                                    drawOnChartArea: false
                                }
                            }
                        }
                    }
                });
            }
        }, 100);
    }
    
    // Exportar dados
    $('#export-form').submit(function(e) {
        e.preventDefault();
        
        const format = $('#export-format').val();
        const filename = $('#export-filename').val() || 'desempenho_artistas';
        
        // Coletar colunas selecionadas
        const columns = [];
        if ($('#col-id').is(':checked')) columns.push('ID');
        if ($('#col-account').is(':checked')) columns.push('Conta');
        if ($('#col-track').is(':checked')) columns.push('Música');
        if ($('#col-artist').is(':checked')) columns.push('Artista');
        if ($('#col-streams').is(':checked')) columns.push('Streams');
        if ($('#col-platform').is(':checked')) columns.push('Plataforma');
        if ($('#col-playlist').is(':checked')) columns.push('Playlist');
        if ($('#col-territory').is(':checked')) columns.push('Território');
        if ($('#col-date').is(':checked')) columns.push('Data de Reprodução');
        
        // Obter dados filtrados
        const indices = performanceTable.rows({ search: 'applied' }).indexes().toArray();
        const data = indices.map(idx => performanceData[idx]);
        
        if (data.length === 0) {
            showToast('warning', 'Sem dados', 'Não há dados para exportar com os filtros atuais.');
            return;
        }
        
        // Preparar dados para exportação
        const exportData = data.map(item => ({
            ID: item.id,
            Conta: item.accountName,
            Música: item.track,
            Artista: item.artist,
            Streams: item.streams,
            Plataforma: item.platformName,
            Playlist: item.playlistName,
            Território: item.territoryName,
            'Data de Reprodução': formatDate(item.playDate),
            Receita: formatCurrency(item.revenue)
        }));
        
        // Exportar baseado no formato
        switch(format) {
            case 'csv':
                exportToCSV(exportData, columns, `${filename}.csv`);
                break;
            case 'excel':
                exportToExcel(exportData, columns, `${filename}.xlsx`);
                break;
            case 'pdf':
                exportToPDF(exportData, columns, filename);
                break;
            case 'json':
                exportToJSON(exportData, `${filename}.json`);
                break;
        }
        
        $('#exportModal').modal('hide');
        showToast('success', 'Exportação Concluída', 'Dados exportados com sucesso.');
    });
    
    // Exportar para CSV
    function exportToCSV(data, columns, filename) {
        const headers = columns;
        const rows = data.map(row => 
            headers.map(header => `"${row[header] || ''}"`).join(',')
        );
        
        const csv = [headers.join(','), ...rows].join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        downloadFile(blob, filename);
    }
    
    // Exportar para Excel
    function exportToExcel(data, columns, filename) {
        try {
            const ws = XLSX.utils.json_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Desempenho");
            XLSX.writeFile(wb, filename);
        } catch (error) {
            console.error('Erro ao exportar Excel:', error);
            showToast('error', 'Erro na Exportação', 'Não foi possível exportar para Excel.');
        }
    }
    
    // Exportar para PDF
    function exportToPDF(data, columns, filename) {
        const element = document.createElement('div');
        element.innerHTML = `
            <h3>Relatório de Desempenho</h3>
            <p>Gerado em: ${new Date().toLocaleDateString('pt-BR')}</p>
            <table border="1" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        ${columns.map(col => `<th style="padding:8px;background:#f0f0f0;">${col}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${data.map(row => `
                        <tr>
                            ${columns.map(col => `<td style="padding:6px;">${row[col] || ''}</td>`).join('')}
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        
        const opt = {
            margin: 10,
            filename: `${filename}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        
        html2pdf().set(opt).from(element).save();
    }
    
    // Exportar para JSON
    function exportToJSON(data, filename) {
        const json = JSON.stringify(data, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        downloadFile(blob, filename);
    }
    
    // Função auxiliar para download
    function downloadFile(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    // Ver detalhes do item
    $(document).on('click', '.view-details-btn', function() {
        const trackId = $(this).data('id');
        const item = performanceData.find(i => i.id === trackId);
        
        if (item) {
            const flagCode = territoryFlags[item.territory] || 'globe';
            const platformInfo = platformInfo[item.platform] || { icon: 'bi-question-circle', color: 'bg-secondary' };
            
            $('#detail-content').html(`
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <img src="${item.artistAvatar}" alt="${item.artist}" class="artist-avatar-lg mb-3">
                                <h4>${item.track}</h4>
                                <p class="text-muted">${item.artist}</p>
                                
                                <div class="mb-3">
                                    <span class="badge ${accountClasses[item.account]} account-badge">${item.accountName}</span>
                                    <span class="platform-badge ${platformInfo.color}">
                                        <i class="bi ${platformInfo.icon}"></i>
                                        ${item.platformName}
                                    </span>
                                </div>
                                
                                <div class="d-flex justify-content-center gap-3 mb-3">
                                    <div class="text-center">
                                        <div class="h4 text-primary">${formatNumber(item.streams)}</div>
                                        <small class="text-muted">Streams</small>
                                    </div>
                                    <div class="text-center">
                                        <div class="h4 text-success">${formatCurrency(item.revenue)}</div>
                                        <small class="text-muted">Receita</small>
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
                                                <th>ID:</th>
                                                <td>${item.id}</td>
                                            </tr>
                                            <tr>
                                                <th>Data de Reprodução:</th>
                                                <td>${formatDate(item.playDate)}</td>
                                            </tr>
                                            <tr>
                                                <th>Playlist:</th>
                                                <td>
                                                    ${item.playlist === 'none' ? 
                                                        '<span class="text-muted">Nenhuma</span>' : 
                                                        `<span class="playlist-badge">${item.playlistName}</span>`}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Território:</th>
                                                <td>
                                                    <i class="flag-icon flag-icon-${flagCode}"></i>
                                                    ${item.territoryName}
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6><i class="bi bi-graph-up me-2"></i>Tendências</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <th>Esta Semana:</th>
                                                <td class="${item.trends.week > 0 ? 'trend-up' : 'trend-down'}">
                                                    ${getTrendIcon(item.trends.week)} ${Math.abs(item.trends.week)}%
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Este Mês:</th>
                                                <td class="${item.trends.month > 0 ? 'trend-up' : 'trend-down'}">
                                                    ${getTrendIcon(item.trends.month)} ${Math.abs(item.trends.month)}%
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Este Ano:</th>
                                                <td class="${item.trends.year > 0 ? 'trend-up' : 'trend-down'}">
                                                    ${getTrendIcon(item.trends.year)} ${Math.abs(item.trends.year)}%
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <h6><i class="bi bi-bar-chart me-2"></i>Estatísticas Detalhadas</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Média Diária:</th>
                                        <td>${formatNumber(item.details.dailyAverage)} streams</td>
                                    </tr>
                                    <tr>
                                        <th>Dia de Pico:</th>
                                        <td>${formatDate(item.details.peakDay)} (${formatNumber(item.details.peakStreams)} streams)</td>
                                    </tr>
                                    <tr>
                                        <th>Cidades Principais:</th>
                                        <td>${item.details.listenerCities.join(', ')}</td>
                                    </tr>
                                    <tr>
                                        <th>Demografia:</th>
                                        <td>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar bg-info" style="width: ${item.details.demographics.age18_24}%"
                                                     title="18-24 anos">${item.details.demographics.age18_24}%</div>
                                                <div class="progress-bar bg-success" style="width: ${item.details.demographics.age25_34}%"
                                                     title="25-34 anos">${item.details.demographics.age25_34}%</div>
                                                <div class="progress-bar bg-warning" style="width: ${item.details.demographics.age35_44}%"
                                                     title="35-44 anos">${item.details.demographics.age35_44}%</div>
                                                <div class="progress-bar bg-secondary" style="width: ${item.details.demographics.age45_plus}%"
                                                     title="45+ anos">${item.details.demographics.age45_plus}%</div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            $('#detailModal').modal('show');
        }
    });
    
    // Exportar detalhes
    $('#export-detail-btn').click(function() {
        const content = $('#detail-content').html();
        const opt = {
            margin: 10,
            filename: 'detalhes_desempenho.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        
        html2pdf().set(opt).from(content).save();
        showToast('success', 'PDF Gerado', 'Detalhes exportados como PDF.');
    });
    
    // Botões de ação em massa
    $('#select-all-header, #select-all-tracks').click(function() {
        const isChecked = $(this).is(':checked');
        $('.track-checkbox').prop('checked', isChecked);
        
        if (isChecked) {
            const visibleRows = performanceTable.rows({ search: 'applied' }).data();
            visibleRows.each(function(item) {
                selectedTracks.add(item.id);
            });
        } else {
            selectedTracks.clear();
        }
        updateSelectedCount();
    });
    
    $('#export-selected-btn').click(function() {
        if (selectedTracks.size === 0) {
            showToast('warning', 'Nenhum item selecionado', 'Selecione itens para exportar.');
            return;
        }
        
        const selectedData = performanceData.filter(item => selectedTracks.has(item.id));
        exportToCSV(selectedData, [
            'ID', 'Conta', 'Música', 'Artista', 'Streams', 'Plataforma', 'Playlist', 'Território', 'Data de Reprodução'
        ], 'itens_selecionados.csv');
        
        showToast('success', 'Exportação Concluída', `${selectedTracks.size} item(s) exportado(s).`);
    });
    
    $('#generate-report-btn').click(function() {
        const count = performanceTable.rows({ search: 'applied' }).count();
        showToast('info', 'Relatório em Andamento', `Gerando relatório com ${count} item(s)...`);
        // Implementar geração de relatório completo
    });
    
    $('#compare-selected-btn').click(function() {
        if (selectedTracks.size < 2) {
            showToast('warning', 'Seleção Insuficiente', 'Selecione pelo menos 2 itens para comparar.');
            return;
        }
        
        $('#compare-artists-btn').addClass('active');
        $('#comparison-panel').show();
        showComparison();
    });
    
    // Atualizar dados
    $('#refresh-data-btn').click(function() {
        // Simular atualização de dados
        showToast('info', 'Atualizando Dados', 'Buscando dados mais recentes...');
        setTimeout(() => {
            initDataTable();
            showToast('success', 'Dados Atualizados', 'Dados de desempenho foram atualizados.');
        }, 1000);
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
    
    // Inicializar
    initDataTable();
});