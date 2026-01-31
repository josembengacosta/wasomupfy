// stores-performance.js - Desempenho por Lojas Digitais
document.addEventListener('DOMContentLoaded', function() {
    // Dados simulados de lojas digitais
    const mockStores = [
        {
            id: 1,
            name: 'Spotify',
            type: 'streaming',
            logoColor: '#1DB954',
            logoIcon: 'bi-spotify',
            revenue: 103245.89,
            streams: 3580000,
            downloads: 0,
            royaltyRate: 0.78,
            growth: 12.5,
            countries: ['BR', 'US', 'PT', 'DE', 'FR'],
            marketShare: 42,
            lastMonthRevenue: 91800.50,
            activeArtists: 245
        },
        {
            id: 2,
            name: 'Apple Music',
            type: 'streaming',
            logoColor: '#FA243C',
            logoIcon: 'bi-apple',
            revenue: 65432.10,
            streams: 1850000,
            downloads: 0,
            royaltyRate: 0.85,
            growth: 8.3,
            countries: ['US', 'GB', 'BR', 'DE'],
            marketShare: 27,
            lastMonthRevenue: 60420.30,
            activeArtists: 198
        },
        {
            id: 3,
            name: 'YouTube Music',
            type: 'streaming',
            logoColor: '#FF0000',
            logoIcon: 'bi-youtube',
            revenue: 42345.67,
            streams: 1580000,
            downloads: 0,
            royaltyRate: 0.65,
            growth: 34.2,
            countries: ['BR', 'US', 'IN', 'MX'],
            marketShare: 17,
            lastMonthRevenue: 31560.20,
            activeArtists: 167
        },
        {
            id: 4,
            name: 'Deezer',
            type: 'streaming',
            logoColor: '#00C7F2',
            logoIcon: 'bi-music-note-beamed',
            revenue: 15678.90,
            streams: 890000,
            downloads: 0,
            royaltyRate: 0.72,
            growth: -12.1,
            countries: ['FR', 'BR', 'PT'],
            marketShare: 6,
            lastMonthRevenue: 17845.60,
            activeArtists: 89
        },
        {
            id: 5,
            name: 'Amazon Music',
            type: 'streaming',
            logoColor: '#FF9900',
            logoIcon: 'bi-amazon',
            revenue: 23456.78,
            streams: 1200000,
            downloads: 0,
            royaltyRate: 0.68,
            growth: 5.6,
            countries: ['US', 'GB', 'DE'],
            marketShare: 10,
            lastMonthRevenue: 22220.40,
            activeArtists: 123
        },
        {
            id: 6,
            name: 'Tidal',
            type: 'streaming',
            logoColor: '#000000',
            logoIcon: 'bi-soundwave',
            revenue: 9876.54,
            streams: 450000,
            downloads: 0,
            royaltyRate: 0.90,
            growth: 2.4,
            countries: ['US', 'GB'],
            marketShare: 4,
            lastMonthRevenue: 9645.80,
            activeArtists: 45
        },
        {
            id: 7,
            name: 'Beatport',
            type: 'download',
            logoColor: '#FF6B6B',
            logoIcon: 'bi-arrow-down-circle',
            revenue: 8765.43,
            streams: 0,
            downloads: 12500,
            royaltyRate: 0.82,
            growth: 7.8,
            countries: ['US', 'GB', 'DE'],
            marketShare: 3,
            lastMonthRevenue: 8120.90,
            activeArtists: 67
        },
        {
            id: 8,
            name: 'Bandcamp',
            type: 'download',
            logoColor: '#629AA9',
            logoIcon: 'bi-bandcamp',
            revenue: 6543.21,
            streams: 0,
            downloads: 8900,
            royaltyRate: 0.88,
            growth: 15.3,
            countries: ['US', 'GB', 'DE'],
            marketShare: 2,
            lastMonthRevenue: 5678.40,
            activeArtists: 145
        },
        {
            id: 9,
            name: 'Instagram',
            type: 'social',
            logoColor: '#E4405F',
            logoIcon: 'bi-instagram',
            revenue: 5432.10,
            streams: 230000,
            downloads: 0,
            royaltyRate: 0.60,
            growth: 45.6,
            countries: ['BR', 'US', 'IN'],
            marketShare: 2,
            lastMonthRevenue: 3730.20,
            activeArtists: 210
        },
        {
            id: 10,
            name: 'TikTok',
            type: 'social',
            logoColor: '#000000',
            logoIcon: 'bi-tiktok',
            revenue: 4321.09,
            streams: 310000,
            downloads: 0,
            royaltyRate: 0.55,
            growth: 68.9,
            countries: ['US', 'BR', 'IN', 'MX'],
            marketShare: 2,
            lastMonthRevenue: 2560.80,
            activeArtists: 189
        },
        {
            id: 11,
            name: 'Pandora',
            type: 'radio',
            logoColor: '#224099',
            logoIcon: 'bi-radioactive',
            revenue: 3210.98,
            streams: 560000,
            downloads: 0,
            royaltyRate: 0.58,
            growth: -5.4,
            countries: ['US'],
            marketShare: 1,
            lastMonthRevenue: 3390.50,
            activeArtists: 78
        },
        {
            id: 12,
            name: 'SoundCloud',
            type: 'social',
            logoColor: '#FF3300',
            logoIcon: 'bi-cloud',
            revenue: 2109.87,
            streams: 420000,
            downloads: 0,
            royaltyRate: 0.62,
            growth: 9.7,
            countries: ['US', 'GB', 'DE'],
            marketShare: 1,
            lastMonthRevenue: 1920.30,
            activeArtists: 234
        }
    ];

    // Dados de países
    const countryData = [
        { code: 'BR', name: 'Brasil', flag: '🇧🇷', revenue: 125430.50, streams: 4250000 },
        { code: 'US', name: 'EUA', flag: '🇺🇸', revenue: 86420.30, streams: 3120000 },
        { code: 'PT', name: 'Portugal', flag: '🇵🇹', revenue: 32450.80, streams: 980000 },
        { code: 'DE', name: 'Alemanha', flag: '🇩🇪', revenue: 28760.40, streams: 845000 },
        { code: 'FR', name: 'França', flag: '🇫🇷', revenue: 19870.20, streams: 620000 },
        { code: 'GB', name: 'Reino Unido', flag: '🇬🇧', revenue: 25670.90, streams: 780000 },
        { code: 'AO', name: 'Angola', flag: '🇦🇴', revenue: 8765.40, streams: 210000 },
        { code: 'MZ', name: 'Moçambique', flag: '🇲🇿', revenue: 5432.10, streams: 145000 }
    ];

    // Estado da aplicação
    let currentStores = [...mockStores];
    let currentPage = 1;
    const itemsPerPage = 10;
    let currentFilter = {
        dateRange: 'last7',
        country: '',
        storeType: '',
        sortBy: 'revenue'
    };
    
    // Gráficos
    let revenueChart, streamsChart, growthChart, geographyChart, distributionChart, comparisonChart;
    
    // Elementos DOM
    const totalRevenue = document.getElementById('totalRevenue');
    const totalStreams = document.getElementById('totalStreams');
    const totalDownloads = document.getElementById('totalDownloads');
    const avgRoyalty = document.getElementById('avgRoyalty');
    const storesRanking = document.getElementById('storesRanking');
    const countriesPerformance = document.getElementById('countriesPerformance');
    const storesTable = document.getElementById('storesTable');
    const storesPagination = document.getElementById('storesPagination');
    const compareStoresContainer = document.getElementById('compareStoresContainer');
    
    // Modais
    const storeDetailsModal = new bootstrap.Modal(document.getElementById('storeDetailsModal'));
    const compareStoresModal = new bootstrap.Modal(document.getElementById('compareStoresModal'));

    // Inicializar
    function init() {
        calculateTotals();
        renderStoresRanking();
        renderCountriesPerformance();
        renderStoresTable();
        initCharts();
        setupEventListeners();
        
        // Atualização em tempo real
        const realTimeUpdate = document.getElementById('realTimeUpdate');
        if (realTimeUpdate.checked) {
            startRealTimeUpdates();
        }
    }

    // Calcular totais
    function calculateTotals() {
        let totalRev = 0;
        let totalStr = 0;
        let totalDwn = 0;
        let totalRoy = 0;
        
        currentStores.forEach(store => {
            totalRev += store.revenue;
            totalStr += store.streams;
            totalDwn += store.downloads;
            totalRoy += store.royaltyRate;
        });
        
        const avgRoy = totalRoy / currentStores.length;
        
        totalRevenue.textContent = formatCurrency(totalRev);
        totalStreams.textContent = formatNumber(totalStr);
        totalDownloads.textContent = formatNumber(totalDwn);
        avgRoyalty.textContent = `${(avgRoy * 100).toFixed(1)}%`;
    }

    // Renderizar ranking de lojas
    function renderStoresRanking() {
        const sortedStores = [...currentStores]
            .sort((a, b) => b.revenue - a.revenue)
            .slice(0, 10);
        
        storesRanking.innerHTML = sortedStores.map((store, index) => `
            <div class="d-flex align-items-center mb-3 fade-in" style="animation-delay: ${index * 0.1}s">
                <div class="store-rank rank-${index < 3 ? index + 1 : 'other'} me-3">
                    ${index + 1}
                </div>
                <div class="store-logo me-3" style="background-color: ${store.logoColor}">
                    <i class="${store.logoIcon}"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1">${store.name}</h6>
                    <small class="text-muted">${formatCurrency(store.revenue)}</small>
                </div>
                <div class="store-percentage">
                    <div class="store-percentage-bar" style="width: ${store.marketShare}%; background-color: ${store.logoColor}"></div>
                </div>
                <small class="text-muted">${store.marketShare}%</small>
            </div>
        `).join('');
    }

    // Renderizar performance por país
    function renderCountriesPerformance() {
        const sortedCountries = [...countryData]
            .sort((a, b) => b.revenue - a.revenue)
            .slice(0, 6);
        
        countriesPerformance.innerHTML = sortedCountries.map(country => `
            <div class="d-flex justify-content-between align-items-center mb-3 fade-in">
                <div class="d-flex align-items-center">
                    <span class="country-flag" style="font-size: 24px;">${country.flag}</span>
                    <div>
                        <h6 class="mb-0">${country.name}</h6>
                        <small class="text-muted">${formatNumber(country.streams)} streams</small>
                    </div>
                </div>
                <div class="text-end">
                    <h6 class="mb-0">${formatCurrency(country.revenue)}</h6>
                    <small class="text-muted">Receita</small>
                </div>
            </div>
        `).join('');
    }

    // Renderizar tabela de lojas
    function renderStoresTable() {
        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const paginatedStores = currentStores.slice(start, end);
        
        if (paginatedStores.length === 0) {
            storesTable.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <i class="bi bi-search fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="text-muted">Nenhuma loja encontrada</h5>
                        <p class="text-muted">Tente ajustar os filtros de busca</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        storesTable.innerHTML = paginatedStores.map((store, index) => `
            <tr class="fade-in" style="animation-delay: ${index * 0.05}s">
                <td>
                    <div class="store-rank rank-${index + start < 3 ? index + start + 1 : 'other'}">
                        ${index + start + 1}
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="store-logo me-3" style="background-color: ${store.logoColor}">
                            <i class="${store.logoIcon}"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">${store.name}</h6>
                            <small class="text-muted">${getStoreTypeLabel(store.type)}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-light text-dark">${getStoreTypeLabel(store.type)}</span>
                </td>
                <td>
                    <h6 class="mb-0">${formatCurrency(store.revenue)}</h6>
                    <small class="text-muted ${store.revenue > store.lastMonthRevenue ? 'text-success' : 'text-danger'}">
                        ${store.revenue > store.lastMonthRevenue ? '+' : ''}${((store.revenue - store.lastMonthRevenue) / store.lastMonthRevenue * 100).toFixed(1)}%
                    </small>
                </td>
                <td>
                    <h6 class="mb-0">${formatNumber(store.streams)}</h6>
                    <small class="text-muted">streams</small>
                </td>
                <td>
                    <h6 class="mb-0">${formatNumber(store.downloads)}</h6>
                    <small class="text-muted">downloads</small>
                </td>
                <td>
                    <h6 class="mb-0">${(store.royaltyRate * 100).toFixed(1)}%</h6>
                    <small class="text-muted">royalty</small>
                </td>
                <td>
                    <h6 class="mb-0 ${store.growth >= 0 ? 'text-success' : 'text-danger'}">
                        ${store.growth >= 0 ? '+' : ''}${store.growth.toFixed(1)}%
                    </h6>
                    <small class="text-muted">crescimento</small>
                </td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-outline-primary" onclick="showStoreDetails(${store.id})">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-success" onclick="addToComparison(${store.id})">
                            <i class="bi bi-graph-up"></i>
                        </button>
                        <button class="btn btn-outline-info" onclick="analyzeStore(${store.id})">
                            <i class="bi bi-bar-chart"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
        
        renderPagination();
    }

    // Renderizar paginação
    function renderPagination() {
        const totalPages = Math.ceil(currentStores.length / itemsPerPage);
        storesPagination.innerHTML = '';
        
        if (totalPages <= 1) return;
        
        // Botão anterior
        const prevLi = createPaginationItem(
            'previous',
            '&laquo;',
            currentPage === 1,
            () => { if (currentPage > 1) goToPage(currentPage - 1); }
        );
        storesPagination.appendChild(prevLi);
        
        // Páginas
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const pageLi = createPaginationItem(
                'page',
                i,
                i === currentPage,
                () => goToPage(i)
            );
            storesPagination.appendChild(pageLi);
        }
        
        // Botão próximo
        const nextLi = createPaginationItem(
            'next',
            '&raquo;',
            currentPage === totalPages,
            () => { if (currentPage < totalPages) goToPage(currentPage + 1); }
        );
        storesPagination.appendChild(nextLi);
    }

    function createPaginationItem(type, content, disabled, onClick) {
        const li = document.createElement('li');
        li.className = `page-item ${disabled ? 'disabled' : ''}`;
        li.innerHTML = `<a class="page-link" href="#">${content}</a>`;
        
        if (!disabled) {
            li.addEventListener('click', (e) => {
                e.preventDefault();
                onClick();
            });
        }
        
        return li;
    }

    function goToPage(page) {
        currentPage = page;
        renderStoresTable();
        
        // Rolar para a tabela
        storesTable.scrollIntoView({ behavior: 'smooth' });
    }

    // Inicializar gráficos
    function initCharts() {
        initRevenueChart();
        initStreamsChart();
        initGrowthChart();
        initGeographyChart();
        initDistributionChart();
    }

    function initRevenueChart() {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const sortedStores = [...currentStores]
            .sort((a, b) => b.revenue - a.revenue)
            .slice(0, 6);
        
        revenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sortedStores.map(s => s.name),
                datasets: [{
                    label: 'Receita (R$)',
                    data: sortedStores.map(s => s.revenue),
                    backgroundColor: sortedStores.map(s => s.logoColor),
                    borderColor: sortedStores.map(s => s.logoColor),
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Receita por Loja Digital (Top 6)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                }
            }
        });
    }

    function initStreamsChart() {
        const ctx = document.getElementById('streamsChart').getContext('2d');
        const sortedStores = [...currentStores]
            .sort((a, b) => b.streams - a.streams)
            .slice(0, 6);
        
        streamsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: sortedStores.map(s => s.name),
                datasets: [{
                    label: 'Streams (milhões)',
                    data: sortedStores.map(s => s.streams / 1000000),
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Streams por Loja Digital (Top 6)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Milhões de Streams'
                        }
                    }
                }
            }
        });
    }

    function initGrowthChart() {
        const ctx = document.getElementById('growthChart').getContext('2d');
        const sortedStores = [...currentStores]
            .sort((a, b) => b.growth - a.growth)
            .slice(0, 8);
        
        growthChart = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: sortedStores.map(s => s.name),
                datasets: [{
                    label: 'Crescimento (%)',
                    data: sortedStores.map(s => s.growth),
                    backgroundColor: 'rgba(54, 185, 204, 0.2)',
                    borderColor: '#36b9cc',
                    borderWidth: 2,
                    pointBackgroundColor: '#36b9cc'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Crescimento por Loja Digital'
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    function initGeographyChart() {
        const ctx = document.getElementById('geographyChart').getContext('2d');
        
        geographyChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: countryData.map(c => c.name),
                datasets: [{
                    data: countryData.map(c => c.revenue),
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                        '#6f42c1', '#858796', '#5a5c69'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    title: {
                        display: true,
                        text: 'Distribuição de Receita por País'
                    }
                }
            }
        });
    }

    function initDistributionChart() {
        const ctx = document.getElementById('distributionChart').getContext('2d');
        
        // Calcular distribuição por tipo
        const typeData = {
            streaming: 0,
            download: 0,
            social: 0,
            radio: 0
        };
        
        currentStores.forEach(store => {
            typeData[store.type] += store.revenue;
        });
        
        distributionChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Streaming', 'Download', 'Redes Sociais', 'Rádio'],
                datasets: [{
                    data: [
                        typeData.streaming,
                        typeData.download,
                        typeData.social,
                        typeData.radio
                    ],
                    backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e', '#e74a3b'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Distribuição por Tipo de Loja'
                    }
                }
            }
        });
    }

    // Aplicar filtros
    window.applyFilters = function() {
        currentFilter.dateRange = document.getElementById('dateRange').value;
        currentFilter.country = document.getElementById('countryFilter').value;
        currentFilter.storeType = document.getElementById('storeType').value;
        currentFilter.sortBy = document.getElementById('sortBy').value;
        
        filterStores();
        updateCharts();
        
        showNotification('Filtros aplicados com sucesso', 'success');
    };

    function filterStores() {
        let filtered = [...mockStores];
        
        // Filtrar por país
        if (currentFilter.country) {
            filtered = filtered.filter(store => 
                store.countries.includes(currentFilter.country)
            );
        }
        
        // Filtrar por tipo
        if (currentFilter.storeType) {
            filtered = filtered.filter(store => 
                store.type === currentFilter.storeType
            );
        }
        
        // Ordenar
        switch (currentFilter.sortBy) {
            case 'revenue':
                filtered.sort((a, b) => b.revenue - a.revenue);
                break;
            case 'streams':
                filtered.sort((a, b) => b.streams - a.streams);
                break;
            case 'downloads':
                filtered.sort((a, b) => b.downloads - a.downloads);
                break;
            case 'growth':
                filtered.sort((a, b) => b.growth - a.growth);
                break;
            case 'royalty':
                filtered.sort((a, b) => b.royaltyRate - a.royaltyRate);
                break;
        }
        
        currentStores = filtered;
        currentPage = 1;
        
        calculateTotals();
        renderStoresRanking();
        renderStoresTable();
    }

    // Redefinir filtros
    window.resetFilters = function() {
        document.getElementById('dateRange').value = 'last7';
        document.getElementById('countryFilter').value = '';
        document.getElementById('storeType').value = '';
        document.getElementById('sortBy').value = 'revenue';
        
        currentFilter = {
            dateRange: 'last7',
            country: '',
            storeType: '',
            sortBy: 'revenue'
        };
        
        currentStores = [...mockStores];
        currentPage = 1;
        
        calculateTotals();
        renderStoresRanking();
        renderStoresTable();
        updateCharts();
        
        showNotification('Filtros redefinidos', 'info');
    };

    // Buscar lojas
    window.searchStores = function() {
        const searchTerm = document.getElementById('storeSearch').value.toLowerCase().trim();
        
        if (!searchTerm) {
            currentStores = [...mockStores];
        } else {
            currentStores = mockStores.filter(store => 
                store.name.toLowerCase().includes(searchTerm) ||
                getStoreTypeLabel(store.type).toLowerCase().includes(searchTerm)
            );
        }
        
        currentPage = 1;
        renderStoresTable();
    };

    // Mostrar detalhes da loja
    window.showStoreDetails = function(storeId) {
        const store = mockStores.find(s => s.id === storeId);
        if (!store) return;
        
        const modalTitle = document.getElementById('storeModalTitle');
        const modalBody = document.getElementById('storeModalBody');
        
        modalTitle.textContent = `Análise Detalhada - ${store.name}`;
        
        modalBody.innerHTML = `
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="store-logo mx-auto mb-3" style="width: 100px; height: 100px; background-color: ${store.logoColor}">
                                <i class="${store.logoIcon} fs-1"></i>
                            </div>
                            <h4>${store.name}</h4>
                            <p class="text-muted">${getStoreTypeLabel(store.type)}</p>
                            
                            <div class="mb-3">
                                <span class="badge bg-primary">Market Share: ${store.marketShare}%</span>
                                <span class="badge bg-success ms-1">Artistas Ativos: ${store.activeArtists}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Países de Atuação</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                ${store.countries.map(countryCode => {
                                    const country = countryData.find(c => c.code === countryCode);
                                    return country ? `
                                        <span class="badge bg-light text-dark">
                                            ${country.flag} ${country.name}
                                        </span>
                                    ` : '';
                                }).join('')}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2">Receita Atual</h6>
                                    <h3 class="mb-1">${formatCurrency(store.revenue)}</h3>
                                    <small class="${store.revenue > store.lastMonthRevenue ? 'text-success' : 'text-danger'}">
                                        ${store.revenue > store.lastMonthRevenue ? '+' : ''}${((store.revenue - store.lastMonthRevenue) / store.lastMonthRevenue * 100).toFixed(1)}% vs mês anterior
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2">Streams</h6>
                                    <h3 class="mb-1">${formatNumber(store.streams)}</h3>
                                    <small class="text-muted">Total de reproduções</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2">Royalty Rate</h6>
                                    <h3 class="mb-1">${(store.royaltyRate * 100).toFixed(1)}%</h6>
                                    <small class="text-muted">Taxa média de royalty</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2">Crescimento</h6>
                                    <h3 class="mb-1 ${store.growth >= 0 ? 'text-success' : 'text-danger'}">
                                        ${store.growth >= 0 ? '+' : ''}${store.growth.toFixed(1)}%
                                    </h3>
                                    <small class="text-muted">Variação mensal</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Recomendações para ${store.name}</h6>
                        </div>
                        <div class="card-body">
                            ${generateStoreRecommendations(store)}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        storeDetailsModal.show();
    };

    // Adicionar para comparação
    window.addToComparison = function(storeId) {
        const store = mockStores.find(s => s.id === storeId);
        if (!store) return;
        
        // Carregar container de comparação
        loadComparisonStores();
        compareStoresModal.show();
        
        // Selecionar esta loja
        const checkbox = document.querySelector(`input[data-store-id="${storeId}"]`);
        if (checkbox) {
            checkbox.checked = true;
            updateComparisonChart();
        }
        
        showNotification(`${store.name} adicionada para comparação`, 'success');
    };

    // Carregar lojas para comparação
    function loadComparisonStores() {
        compareStoresContainer.innerHTML = mockStores.map(store => `
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="store-logo mx-auto mb-2" style="background-color: ${store.logoColor}">
                            <i class="${store.logoIcon}"></i>
                        </div>
                        <h6 class="mb-2">${store.name}</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" 
                                   data-store-id="${store.id}" 
                                   onchange="updateComparisonChart()">
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Atualizar gráfico de comparação
    window.updateComparisonChart = function() {
        const selectedStores = Array.from(document.querySelectorAll('#compareStoresContainer input[type="checkbox"]:checked'))
            .map(checkbox => {
                const storeId = parseInt(checkbox.dataset.storeId);
                return mockStores.find(s => s.id === storeId);
            })
            .filter(store => store !== undefined)
            .slice(0, 4); // Limitar a 4 lojas
        
        const metric = document.getElementById('compareMetric').value;
        
        if (selectedStores.length === 0) {
            if (comparisonChart) {
                comparisonChart.data.datasets[0].data = [];
                comparisonChart.update();
            }
            return;
        }
        
        const ctx = document.getElementById('comparisonChart').getContext('2d');
        
        if (comparisonChart) {
            comparisonChart.destroy();
        }
        
        comparisonChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: selectedStores.map(s => s.name),
                datasets: [{
                    label: getMetricLabel(metric),
                    data: selectedStores.map(s => getStoreMetric(s, metric)),
                    backgroundColor: selectedStores.map(s => s.logoColor),
                    borderColor: selectedStores.map(s => s.logoColor),
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: `Comparação de ${getMetricLabel(metric)}`
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return metric === 'revenue' ? 
                                    'R$ ' + value.toLocaleString('pt-BR', {minimumFractionDigits: 2}) :
                                    value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
    };

    // Analisar loja
    window.analyzeStore = function(storeId) {
        const store = mockStores.find(s => s.id === storeId);
        if (!store) return;
        
        // Em produção, isso abriria uma análise mais detalhada
        alert(`Análise detalhada de ${store.name}\n\nReceita: ${formatCurrency(store.revenue)}\nStreams: ${formatNumber(store.streams)}\nCrescimento: ${store.growth}%\nRoyalty: ${(store.royaltyRate * 100).toFixed(1)}%`);
    };

    // Exportar dados
    window.exportData = function() {
        const data = currentStores.map(store => ({
            Loja: store.name,
            Tipo: getStoreTypeLabel(store.type),
            Receita: store.revenue,
            Streams: store.streams,
            Downloads: store.downloads,
            Royalty: (store.royaltyRate * 100).toFixed(1) + '%',
            Crescimento: store.growth + '%',
            'Market Share': store.marketShare + '%',
            'Artistas Ativos': store.activeArtists
        }));
        
        const headers = Object.keys(data[0]).join(';');
        const rows = data.map(obj => Object.values(obj).join(';')).join('\n');
        const csvContent = `${headers}\n${rows}`;
        
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `lojas_digitais_${new Date().toISOString().split('T')[0]}.csv`;
        link.click();
        URL.revokeObjectURL(url);
        
        showNotification('Relatório exportado com sucesso!', 'success');
    };

    // Gerar relatório de comparação
    window.generateComparisonReport = function() {
        const selectedStores = Array.from(document.querySelectorAll('#compareStoresContainer input[type="checkbox"]:checked'))
            .map(checkbox => {
                const storeId = parseInt(checkbox.dataset.storeId);
                return mockStores.find(s => s.id === storeId);
            })
            .filter(store => store !== undefined);
        
        if (selectedStores.length === 0) {
            showNotification('Selecione pelo menos uma loja para comparar', 'warning');
            return;
        }
        
        const metric = document.getElementById('compareMetric').value;
        const comparisonData = selectedStores.map(store => ({
            Loja: store.name,
            [getMetricLabel(metric)]: getStoreMetric(store, metric),
            Tipo: getStoreTypeLabel(store.type),
            Royalty: (store.royaltyRate * 100).toFixed(1) + '%',
            Crescimento: store.growth + '%'
        }));
        
        const headers = Object.keys(comparisonData[0]).join(';');
        const rows = comparisonData.map(obj => Object.values(obj).join(';')).join('\n');
        const csvContent = `${headers}\n${rows}`;
        
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `comparacao_lojas_${new Date().toISOString().split('T')[0]}.csv`;
        link.click();
        URL.revokeObjectURL(url);
        
        showNotification('Comparação exportada com sucesso!', 'success');
        compareStoresModal.hide();
    };

    // Atualizar gráficos
    function updateCharts() {
        if (revenueChart) revenueChart.destroy();
        if (streamsChart) streamsChart.destroy();
        if (growthChart) growthChart.destroy();
        if (distributionChart) distributionChart.destroy();
        
        initRevenueChart();
        initStreamsChart();
        initGrowthChart();
        initDistributionChart();
    }

    // Configurar event listeners
    function setupEventListeners() {
        // Atualização em tempo real
        document.getElementById('realTimeUpdate').addEventListener('change', function() {
            if (this.checked) {
                startRealTimeUpdates();
            } else {
                stopRealTimeUpdates();
            }
        });
        
        // Métrica de comparação
        document.getElementById('compareMetric').addEventListener('change', updateComparisonChart);
    }

    // Atualização em tempo real
    function startRealTimeUpdates() {
        // Simular atualizações em tempo real
        window.realTimeInterval = setInterval(() => {
            // Atualizar alguns dados aleatoriamente
            mockStores.forEach(store => {
                if (Math.random() > 0.7) {
                    const change = (Math.random() - 0.5) * 0.1; // ±5%
                    store.revenue *= (1 + change);
                    store.streams *= (1 + change * 0.8);
                    store.growth += (Math.random() - 0.5) * 2; // ±1%
                }
            });
            
            filterStores();
            updateCharts();
            
            // Mostrar notificação de atualização
            if (Math.random() > 0.8) {
                showNotification('Dados atualizados em tempo real', 'info');
            }
        }, 10000); // Atualizar a cada 10 segundos
    }

    function stopRealTimeUpdates() {
        if (window.realTimeInterval) {
            clearInterval(window.realTimeInterval);
            window.realTimeInterval = null;
        }
    }

    // Funções auxiliares
    function getStoreTypeLabel(type) {
        const types = {
            'streaming': 'Streaming',
            'download': 'Download',
            'social': 'Rede Social',
            'radio': 'Rádio Digital'
        };
        return types[type] || type;
    }

    function getMetricLabel(metric) {
        const labels = {
            'revenue': 'Receita (R$)',
            'streams': 'Streams',
            'downloads': 'Downloads',
            'growth': 'Crescimento (%)',
            'royalty': 'Royalty (%)'
        };
        return labels[metric] || metric;
    }

    function getStoreMetric(store, metric) {
        switch (metric) {
            case 'revenue':
                return store.revenue;
            case 'streams':
                return store.streams;
            case 'downloads':
                return store.downloads;
            case 'growth':
                return store.growth;
            case 'royalty':
                return store.royaltyRate * 100;
            default:
                return store.revenue;
        }
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
            minimumFractionDigits: 2
        }).format(value);
    }

    function formatNumber(value) {
        if (value >= 1000000) {
            return (value / 1000000).toFixed(1) + 'M';
        } else if (value >= 1000) {
            return (value / 1000).toFixed(1) + 'K';
        }
        return value.toLocaleString('pt-BR');
    }

    function generateStoreRecommendations(store) {
        let recommendations = [];
        
        if (store.growth < 0) {
            recommendations.push(`Foco em recuperação: ${store.name} apresentou crescimento negativo de ${Math.abs(store.growth).toFixed(1)}%. Considere campanhas promocionais específicas.`);
        }
        
        if (store.royaltyRate > 0.8) {
            recommendations.push(`Alta rentabilidade: Royalty rate de ${(store.royaltyRate * 100).toFixed(1)}% é excelente. Priorize distribuição nesta plataforma.`);
        }
        
        if (store.marketShare > 20) {
            recommendations.push(`Líder de mercado: Com ${store.marketShare}% de market share, ${store.name} é essencial para sua estratégia.`);
        }
        
        if (store.type === 'social' && store.growth > 30) {
            recommendations.push(`Crescimento explosivo: ${store.name} cresceu ${store.growth.toFixed(1)}%. Explore mais conteúdos exclusivos para esta plataforma.`);
        }
        
        if (recommendations.length === 0) {
            recommendations.push(`Continue monitorando o desempenho de ${store.name}. Considere testar diferentes tipos de conteúdo para maximizar resultados.`);
        }
        
        return recommendations.map(rec => `
            <div class="d-flex mb-2">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <span>${rec}</span>
            </div>
        `).join('');
    }

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        notification.innerHTML = `
            <strong>${type === 'success' ? '✓ Sucesso!' : type === 'error' ? '✗ Erro!' : '⚠ Atenção!'}</strong>
            <span class="ms-1">${message}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // Inicializar
    init();
});