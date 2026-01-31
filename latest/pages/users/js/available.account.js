 // Configurações
        const itemsPerPage = 10;
        let currentPage = 1;
        let currentViewingId = null;
        let filteredData = [];

        

        // Inicialização
        document.addEventListener('DOMContentLoaded', () => {
            filteredData = [...usersData];
            renderTable();
            updateResultsCount();
            setupEventListeners();
            
            // Inicializa o select múltiplo
            new MultiSelectTag('user-role', {
                rounded: false,
                shadow: true,
                placeholder: 'Funções'
            });
        });

        
        // ==================== RENDERIZAÇÃO ====================
        function renderTable() {
            const usersList = document.getElementById('users-list');
            usersList.innerHTML = '';
            
            const startIdx = (currentPage - 1) * itemsPerPage;
            const paginatedItems = filteredData.slice(startIdx, startIdx + itemsPerPage);
            
            paginatedItems.forEach(user => {
                const rolesHTML = user.roles.map(role => 
                    `<span class="badge bg-secondary">${getRoleName(role)}</span>`
                ).join('');
                
                const row = `
                    <tr>
                        <td>${user.id}</td>
                        <td><img src="${user.profile_img}" class="profile-img" alt="${user.name}"></td>
                        <td>${user.account}</td>
                        <td>${user.name}</td>
                        <td>${user.username}</td>
                        <td>${rolesHTML}</td>
                        <td><a href="mailto:${user.email}" class="email-link">${user.email}</a></td>
                        <td>${getCountryName(user.country)}</td>
                        <td>${user.city}</td>
                        <td><span class="status-badge ${getStatusClass(user.status)}">${getStatusText(user.status)}</span></td>
                        <td>${formatDate(user.created_at)}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary view-btn" data-id="${user.id}" title="Ver perfil completo">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
                usersList.insertAdjacentHTML('beforeend', row);
            });
            
            renderPagination();
            setupActionButtons();
        }

        function renderPagination() {
            const pagination = document.getElementById('users-pagination');
            pagination.innerHTML = '';
            const totalPages = Math.ceil(filteredData.length / itemsPerPage);
            
            // Botão Anterior
            pagination.insertAdjacentHTML('beforeend', `
                <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="prev"><i class="bi bi-chevron-left"></i></a>
                </li>
            `);
            
            // Páginas
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                pagination.insertAdjacentHTML('beforeend', `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
            }
            
            // Botão Próximo
            pagination.insertAdjacentHTML('beforeend', `
                <li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="next"><i class="bi bi-chevron-right"></i></a>
                </li>
            `);
            
            // Eventos
            document.querySelectorAll('.page-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetPage = link.dataset.page;
                    
                    if (targetPage === 'prev' && currentPage > 1) {
                        currentPage--;
                    } else if (targetPage === 'next' && currentPage < totalPages) {
                        currentPage++;
                    } else if (!isNaN(targetPage)) {
                        currentPage = parseInt(targetPage);
                    }
                    
                    renderTable();
                });
            });
        }

        // ==================== FUNÇÕES AUXILIARES ====================
        function getStatusClass(status) {
            switch(status) {
                case 'active': return 'status-active';
                case 'suspended': return 'status-suspended';
                case 'review': return 'status-review';
                default: return 'bg-secondary';
            }
        }

        function getStatusText(status) {
            switch(status) {
                case 'active': return 'Ativo';
                case 'suspended': return 'Suspenso';
                case 'review': return 'Revisão';
                default: return status;
            }
        }

        function getRoleName(role) {
            switch(role) {
                case 'admin': return 'Administrador';
                case 'distributor': return 'Distribuidor';
                case 'analyst': return 'Analista';
                case 'financial': return 'Financeiro';
                default: return role;
            }
        }

        function getCountryName(code) {
            switch(code) {
                case 'AO': return 'Angola';
                case 'PT': return 'Portugal';
                case 'BR': return 'Brasil';
                case 'MZ': return 'Moçambique';
                default: return code;
            }
        }

        function formatDate(dateString) {
            const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
            return new Date(dateString).toLocaleDateString('pt-AO', options);
        }

        function formatDateTime(dateTimeString) {
            const options = { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return new Date(dateTimeString).toLocaleDateString('pt-AO', options);
        }

        function updateResultsCount() {
            document.getElementById('user-results-count').textContent = `${filteredData.length} resultados`;
        }

        function calculateStreamingPercentage(total, limit) {
            return Math.min(Math.round((total / limit) * 100), 100);
        }

        // ==================== FILTROS ====================
        function filterData() {
            const id = document.getElementById('user-id').value;
            const account = document.getElementById('user-account').value.toLowerCase();
            const name = document.getElementById('user-name').value.toLowerCase();
            const roles = Array.from(document.getElementById('user-role').selectedOptions).map(opt => opt.value);
            const country = document.getElementById('user-country').value;
            const status = document.getElementById('user-status').value;
            
            filteredData = usersData.filter(user => {
                // Verifica filtros básicos
                const basicFilters = (
                    (!id || user.id.toString().includes(id)) &&
                    (!account || user.account.toLowerCase().includes(account)) &&
                    (!name || user.name.toLowerCase().includes(name)) &&
                    (!country || user.country === country) &&
                    (!status || user.status === status)
                );
                
                // Verifica filtro de funções (se selecionado)
                const roleFilter = roles.length === 0 || 
                    roles.some(role => user.roles.includes(role));
                
                return basicFilters && roleFilter;
            });
            
            currentPage = 1;
            renderTable();
            updateResultsCount();
        }

        function clearFilters() {
            document.getElementById('user-id').value = '';
            document.getElementById('user-account').value = '';
            document.getElementById('user-name').value = '';
            document.getElementById('user-country').value = '';
            document.getElementById('user-status').value = '';
            
            // Limpa seleção múltipla
            const roleSelect = document.getElementById('user-role');
            Array.from(roleSelect.options).forEach(opt => opt.selected = false);
            
            filterData();
        }

        // ==================== MODAL DE VISUALIZAÇÃO ====================
        function openViewModal(user) {
            currentViewingId = user.id;
            
            const streamingPercentage = calculateStreamingPercentage(user.streaming_total, user.streaming_limit);
            const streamingColor = streamingPercentage >= 90 ? 'bg-danger' : 
                                streamingPercentage >= 70 ? 'bg-warning' : 'bg-success';
            
            const rolesHTML = user.roles.map(role => 
                `<span class="badge bg-primary me-1 mb-1">${getRoleName(role)}</span>`
            ).join('');
            
            const socialLinksHTML = user.social_links ? Object.entries(user.social_links).map(([platform, value]) => `
                <div class="col-6 col-md-4 mb-2">
                    <strong>${platform.charAt(0).toUpperCase() + platform.slice(1)}:</strong>
                    <span class="ms-1">${value}</span>
                </div>
            `).join('') : '<div class="col-12">Nenhuma rede social vinculada</div>';
            
            document.getElementById('view-user-body').innerHTML = `
                <div class="row">
                    <div class="col-md-4 text-center">
                        <img src="${user.profile_img}" class="profile-img-lg mb-3" alt="${user.name}">
                        <h4>${user.name}</h4>
                        <p class="text-muted">@${user.username}</p>
                        
                        <div class="d-grid gap-2">
                            <a href="mailto:${user.email}" class="btn btn-outline-danger">
                                <i class="bi bi-envelope"></i> Enviar E-mail
                            </a>
                            <a href="https://wa.me/${user.phone.replace(/[^0-9]/g, '')}" class="btn btn-outline-success" target="_blank">
                                <i class="bi bi-whatsapp"></i> Enviar WhatsApp
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h5>Informações Básicas</h5>
                                <p><strong>ID:</strong> ${user.id}</p>
                                <p><strong>Conta:</strong> ${user.account}</p>
                                <p><strong>Estado:</strong> <span class="badge ${getStatusClass(user.status)}">${getStatusText(user.status)}</span></p>
                                <p><strong>Funções:</strong> ${rolesHTML}</p>
                            </div>
                            <div class="col-md-6">
                                <h5>Localização</h5>
                                <p><strong>País:</strong> ${getCountryName(user.country)}</p>
                                <p><strong>Cidade:</strong> ${user.city}</p>
                                <p><strong>Criação:</strong> ${formatDate(user.created_at)}</p>
                                <p><strong>Último login:</strong> ${formatDateTime(user.last_login)}</p>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h5>Plano e Streaming</h5>
                                <p><strong>Plano:</strong> ${user.plan}</p>
                                <p><strong>Streaming total:</strong> ${user.streaming_total.toLocaleString()}</p>
                                <p><strong>Limite:</strong> ${user.streaming_limit.toLocaleString()}</p>
                                <div class="progress mt-2">
                                    <div class="progress-bar ${streamingColor} streaming-progress" 
                                         role="progressbar" 
                                         style="width: ${streamingPercentage}%" 
                                         aria-valuenow="${streamingPercentage}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="text-muted">${streamingPercentage}% do limite utilizado</small>
                            </div>
                            <div class="col-md-6">
                                <h5>Pagamento</h5>
                                <p><strong>Método:</strong> ${user.payment_method}</p>
                                <p><strong>Status:</strong> ${user.payment_status}</p>
                                <p><strong>Telefone:</strong> <a href="tel:${user.phone}">${user.phone}</a></p>
                                <p><strong>E-mail:</strong> <a href="mailto:${user.email}">${user.email}</a></p>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">Redes Sociais</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    ${socialLinksHTML}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            new bootstrap.Modal('#viewUserModal').show();
        }

        // ==================== EVENT LISTENERS ====================
        function setupEventListeners() {
            // Filtros
            document.getElementById('user-id').addEventListener('input', filterData);
            document.getElementById('user-account').addEventListener('input', filterData);
            document.getElementById('user-name').addEventListener('input', filterData);
            document.getElementById('user-country').addEventListener('change', filterData);
            document.getElementById('user-status').addEventListener('change', filterData);
            document.getElementById('user-role').addEventListener('change', filterData);
            
            // Limpar filtros
            document.getElementById('clear-user-filters').addEventListener('click', clearFilters);
        }

        function setupActionButtons() {
            // Botões de visualização dinâmicos
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const user = usersData.find(u => u.id === parseInt(btn.dataset.id));
                    if (user) openViewModal(user);
                });
            });
        }

        // ==================== MULTISELECT TAG ====================
        // Implementação simples de multiselect para manter a simplicidade
        class MultiSelectTag {
            constructor(id, options = {}) {
                this.select = document.getElementById(id);
                this.options = options;
                this.init();
            }
            
            init() {
                this.select.style.display = 'none';
                
                // Cria container
                this.container = document.createElement('div');
                this.container.className = 'multi-select-container';
                this.container.style.position = 'relative';
                
                // Cria display
                this.display = document.createElement('div');
                this.display.className = 'form-select';
                this.display.style.cursor = 'pointer';
                this.display.textContent = this.options.placeholder || 'Selecione opções';
                
                // Cria dropdown
                this.dropdown = document.createElement('div');
                this.dropdown.className = 'multi-select-dropdown';
                this.dropdown.style.display = 'none';
                this.dropdown.style.position = 'absolute';
                this.dropdown.style.width = '100%';
                this.dropdown.style.backgroundColor = 'white';
                this.dropdown.style.color = '#000';
                this.dropdown.style.border = '1px solid #ced4da';
                this.dropdown.style.borderRadius = '0.375rem';
                this.dropdown.style.zIndex = '1000';
                this.dropdown.style.maxHeight = '200px';
                this.dropdown.style.overflowY = 'auto';
                
                // Adiciona opções
                Array.from(this.select.options).forEach(option => {
                    if (option.value) {
                        const item = document.createElement('div');
                        item.className = 'multi-select-item';
                        item.style.padding = '0.375rem 0.75rem';
                        item.style.cursor = 'pointer';
                        
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.value = option.value;
                        checkbox.id = `ms-${this.select.id}-${option.value}`;
                        checkbox.style.marginRight = '0.5rem';
                        
                        const label = document.createElement('label');
                        label.htmlFor = checkbox.id;
                        label.textContent = option.text;
                        
                        item.appendChild(checkbox);
                        item.appendChild(label);
                        this.dropdown.appendChild(item);
                        
                        // Atualiza seleção
                        checkbox.addEventListener('change', () => {
                            option.selected = checkbox.checked;
                            this.updateDisplay();
                            filterData(); // Filtra os dados quando a seleção muda
                        });
                    }
                });
                
                // Eventos
                this.display.addEventListener('click', () => {
                    this.dropdown.style.display = this.dropdown.style.display === 'none' ? 'block' : 'none';
                });
                
                document.addEventListener('click', (e) => {
                    if (!this.container.contains(e.target)) {
                        this.dropdown.style.display = 'none';
                    }
                });
                
                // Monta estrutura
                this.container.appendChild(this.display);
                this.container.appendChild(this.dropdown);
                this.select.parentNode.insertBefore(this.container, this.select.nextSibling);
                
                // Estilo arredondado
                if (this.options.rounded) {
                    this.display.style.borderRadius = '50rem';
                    this.dropdown.style.borderRadius = '0 0 0.375rem 0.375rem';
                }
            }
            
            updateDisplay() {
                const selected = Array.from(this.select.selectedOptions).map(opt => opt.text);
                this.display.textContent = selected.length > 0 ? 
                    selected.join(', ') : 
                    (this.options.placeholder || 'Selecione opções');
            }
        }
    