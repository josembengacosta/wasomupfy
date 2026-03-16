// js/employees.js
document.addEventListener('DOMContentLoaded', function() {
    // Verifica se os dados estão disponíveis
    if (typeof window.employeesData === 'undefined') {
        console.error('Dados de funcionários não encontrados!');
        showNotification('Erro ao carregar dados dos funcionários', 'error');
        return;
    }

    // Configuração inicial
    let currentPage = 1;
    const rowsPerPage = 10;
    let filteredEmployees = [];
    let sortColumn = 'id';
    let sortDirection = 'asc';

    // Elementos DOM
    const usersList = document.getElementById('users-list');
    const usersPagination = document.getElementById('users-pagination');
    const userResultsCount = document.getElementById('user-results-count');
    const clearFiltersBtn = document.getElementById('clear-user-filters');
    const viewUserModal = new bootstrap.Modal(document.getElementById('viewUserModal'));
    const confirmDeleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));

    // Elementos de filtro
    const filters = {
        id: document.getElementById('user-id'),
        account: document.getElementById('user-account'),
        name: document.getElementById('user-name'),
        role: document.getElementById('user-role'),
        country: document.getElementById('user-country'),
        status: document.getElementById('user-status')
    };

    // Traduções
    const translations = {
        country: {
            'AO': 'Angola',
            'PT': 'Portugal',
            'BR': 'Brasil',
            'MZ': 'Moçambique'
        },
        role: {
            'admin': 'Administrador',
            'distributor': 'Distribuidor',
            'analyst': 'Analista',
            'financial': 'Financeiro'
        },
        status: {
            'active': 'Ativo',
            'suspended': 'Suspenso',
            'review': 'Revisão'
        }
    };

    // Funções principais
    function loadEmployees() {
        const allEmployees = window.employeesData.getEmployees();
        filteredEmployees = [...allEmployees];
        applyFilters();
    }

    function applyFilters() {
        const idFilter = filters.id.value;
        const accountFilter = filters.account.value.toLowerCase();
        const nameFilter = filters.name.value.toLowerCase();
        const roleFilters = Array.from(filters.role.selectedOptions).map(opt => opt.value);
        const countryFilter = filters.country.value;
        const statusFilter = filters.status.value;

        const allEmployees = window.employeesData.getEmployees();
        
        filteredEmployees = allEmployees.filter(employee => {
            return (
                (!idFilter || employee.id.toString().includes(idFilter)) &&
                (!accountFilter || employee.account.toLowerCase().includes(accountFilter)) &&
                (!nameFilter || employee.name.toLowerCase().includes(nameFilter)) &&
                (roleFilters.length === 0 || roleFilters.some(role => employee.roles.includes(role))) &&
                (!countryFilter || employee.country === countryFilter) &&
                (!statusFilter || employee.status === statusFilter)
            );
        });

        // Ordenar
        sortData();
        
        // Renderizar
        currentPage = 1;
        renderTable();
        renderPagination();
        updateResultsCount();
    }

    function sortData() {
        filteredEmployees.sort((a, b) => {
            let aValue = a[sortColumn];
            let bValue = b[sortColumn];

            // Converter datas
            if (sortColumn === 'createdAt') {
                aValue = new Date(aValue);
                bValue = new Date(bValue);
            }

            // Converter strings para minúsculas
            if (typeof aValue === 'string') {
                aValue = aValue.toLowerCase();
                bValue = bValue.toLowerCase();
            }

            // Comparar
            if (aValue < bValue) return sortDirection === 'asc' ? -1 : 1;
            if (aValue > bValue) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });
    }

    function renderTable() {
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const paginatedEmployees = filteredEmployees.slice(start, end);

        if (paginatedEmployees.length === 0) {
            usersList.innerHTML = `
                <tr>
                    <td colspan="12" class="text-center py-5">
                        <i class="bi bi-exclamation-circle fs-1 text-muted"></i>
                        <p class="mt-2">Nenhum funcionário encontrado</p>
                        <small class="text-muted">Tente ajustar os filtros de busca</small>
                    </td>
                </tr>
            `;
            return;
        }

        usersList.innerHTML = paginatedEmployees.map(employee => `
            <tr>
                <td>${employee.id}</td>
                <td>
                    ${employee.avatar 
                        ? `<img src="${employee.avatar}" class="profile-img" alt="${employee.name}" title="${employee.name}">`
                        : `<div class="profile-img bg-secondary d-flex align-items-center justify-content-center" title="${employee.name}">
                            <i class="bi bi-person text-white"></i>
                           </div>`
                    }
                </td>
                <td>${employee.account}</td>
                <td>${employee.name}</td>
                <td>${employee.username}</td>
                <td>
                    ${employee.roles.map(role => 
                        `<span class="badge bg-primary badge-role me-1 mb-1">
                            ${translations.role[role] || role}
                        </span>`
                    ).join('')}
                </td>
                <td>
                    <a href="mailto:${employee.email}" class="email-link" title="Enviar e-mail">
                        ${employee.email}
                    </a>
                </td>
                <td>${translations.country[employee.country] || employee.country}</td>
                <td>${employee.city}</td>
                <td>
                    <span class="status-badge status-${employee.status}">
                        ${translations.status[employee.status] || employee.status}
                    </span>
                </td>
                <td>${formatDate(employee.createdAt)}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Ações">
                        <button type="button" class="btn btn-outline-primary view-employee-btn" 
                                data-id="${employee.id}" title="Visualizar">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success edit-employee-btn" 
                                data-id="${employee.id}" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger delete-employee-btn" 
                                data-id="${employee.id}" title="Excluir">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        // Adicionar event listeners
        attachEventListeners();
    }

    function attachEventListeners() {
        // Visualizar
        document.querySelectorAll('.view-employee-btn').forEach(btn => {
            btn.addEventListener('click', () => showEmployeeDetails(btn.dataset.id));
        });

        // Editar
        document.querySelectorAll('.edit-employee-btn').forEach(btn => {
            btn.addEventListener('click', () => editEmployee(btn.dataset.id));
        });

        // Excluir
        document.querySelectorAll('.delete-employee-btn').forEach(btn => {
            btn.addEventListener('click', () => confirmDelete(btn.dataset.id));
        });
    }

    function renderPagination() {
        const totalPages = Math.ceil(filteredEmployees.length / rowsPerPage);
        usersPagination.innerHTML = '';

        if (totalPages <= 1) return;

        // Botão anterior
        const prevLi = createPaginationItem(
            'previous',
            '&laquo;',
            currentPage === 1,
            () => { if (currentPage > 1) goToPage(currentPage - 1); }
        );
        usersPagination.appendChild(prevLi);

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
            usersPagination.appendChild(pageLi);
        }

        // Botão próximo
        const nextLi = createPaginationItem(
            'next',
            '&raquo;',
            currentPage === totalPages,
            () => { if (currentPage < totalPages) goToPage(currentPage + 1); }
        );
        usersPagination.appendChild(nextLi);
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
        renderTable();
        renderPagination();
        updateResultsCount();
        
        // Rolar para o topo da tabela
        usersList.parentElement.scrollIntoView({ behavior: 'smooth' });
    }

    function updateResultsCount() {
        const total = filteredEmployees.length;
        const showing = Math.min(rowsPerPage, total - (currentPage - 1) * rowsPerPage);
        const start = total > 0 ? (currentPage - 1) * rowsPerPage + 1 : 0;
        
        let text = `${total} funcionário${total !== 1 ? 's' : ''}`;
        if (total > 0) {
            text += ` (mostrando ${start}-${start + showing - 1})`;
        }
        
        userResultsCount.textContent = text;
    }

    function showEmployeeDetails(employeeId) {
        const employee = window.employeesData.getEmployeeById(employeeId);
        if (!employee) {
            showNotification('Funcionário não encontrado', 'error');
            return;
        }

        const modalBody = document.getElementById('view-user-body');
        modalBody.innerHTML = `
            <div class="row">
                <div class="col-md-3 text-center">
                    ${employee.avatar 
                        ? `<img src="${employee.avatar}" class="profile-img-lg mb-3" alt="${employee.name}">`
                        : `<div class="profile-img-lg bg-secondary d-flex align-items-center justify-content-center mx-auto mb-3">
                            <i class="bi bi-person text-white fs-1"></i>
                          </div>`
                    }
                    <h5>${employee.name}</h5>
                    <p class="text-muted">@${employee.account}</p>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-info-circle me-1"></i> Informações Pessoais</h6>
                            <p><strong>ID:</strong> ${employee.id}</p>
                            <p><strong>Usuário:</strong> ${employee.username}</p>
                            <p><strong>E-mail:</strong> <a href="mailto:${employee.email}">${employee.email}</a></p>
                            <p><strong>Telefone:</strong> ${employee.phone || 'Não informado'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-briefcase me-1"></i> Informações Profissionais</h6>
                            <p><strong>Funções:</strong> ${employee.roles.map(role => translations.role[role] || role).join(', ')}</p>
                            <p><strong>Plano:</strong> ${employee.plan}</p>
                            <p><strong>Localização:</strong> ${employee.city}, ${translations.country[employee.country] || employee.country}</p>
                            <p><strong>Status:</strong> <span class="status-badge status-${employee.status}">${translations.status[employee.status] || employee.status}</span></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6><i class="bi bi-clock-history me-1"></i> Histórico</h6>
                            <p><strong>Conta criada em:</strong> ${formatDate(employee.createdAt)}</p>
                            <p><strong>Último acesso:</strong> Hoje às ${new Date().getHours()}:${new Date().getMinutes().toString().padStart(2, '0')}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        viewUserModal.show();
    }

    function editEmployee(employeeId) {
        const employee = window.employeesData.getEmployeeById(employeeId);
        if (!employee) {
            showNotification('Funcionário não encontrado', 'error');
            return;
        }

        // Preencher formulário de edição
        document.getElementById('emp-name').value = employee.name;
        document.getElementById('emp-account').value = employee.account;
        document.getElementById('emp-username').value = employee.username;
        document.getElementById('emp-email').value = employee.email;
        document.getElementById('emp-phone').value = employee.phone;
        document.getElementById('emp-country').value = employee.country;
        document.getElementById('emp-city').value = employee.city;
        document.getElementById('emp-status').value = employee.status;
        document.getElementById('emp-plan').value = employee.plan;
        
        // Limpar e selecionar funções
        const rolesSelect = document.getElementById('emp-roles');
        Array.from(rolesSelect.options).forEach(option => {
            option.selected = employee.roles.includes(option.value);
        });

        // Abrir modal
        const modal = new bootstrap.Modal(document.getElementById('addArtistwasomupfy'));
        modal.show();
        
        // Atualizar título do modal
        document.getElementById('addArtistwasomupfyLabel').textContent = 'Editar Funcionário';
        
        // Atualizar botão de salvar
        const saveBtn = document.getElementById('save-employee-btn');
        saveBtn.textContent = 'Atualizar Funcionário';
        saveBtn.dataset.editId = employeeId;
    }

    function confirmDelete(employeeId) {
        const employee = window.employeesData.getEmployeeById(employeeId);
        if (!employee) {
            showNotification('Funcionário não encontrado', 'error');
            return;
        }

        document.getElementById('delete-message').textContent = 
            `Tem certeza que deseja excluir o funcionário "${employee.name}" (ID: ${employee.id})? Esta ação não pode ser desfeita.`;
        
        const confirmBtn = document.getElementById('confirm-delete-btn');
        confirmBtn.onclick = function() {
            const result = window.employeesData.deleteEmployee(employeeId);
            if (result.success) {
                confirmDeleteModal.hide();
                showNotification('Funcionário excluído com sucesso!', 'success');
                loadEmployees();
            } else {
                showNotification('Erro ao excluir funcionário', 'error');
            }
        };
        
        confirmDeleteModal.show();
    }

    function clearFilters() {
        Object.values(filters).forEach(filter => {
            if (filter.type === 'select-multiple') {
                filter.selectedIndex = -1;
            } else if (filter.type === 'select-one') {
                filter.selectedIndex = 0;
            } else {
                filter.value = '';
            }
        });
        
        applyFilters();
        showNotification('Filtros limpos', 'info');
    }

    function sortTable(column) {
        if (sortColumn === column) {
            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            sortColumn = column;
            sortDirection = 'asc';
        }
        
        // Remover classes de ordenação de todas as colunas
        document.querySelectorAll('#users-table th').forEach(th => {
            th.classList.remove('sorting-asc', 'sorting-desc');
        });
        
        // Adicionar classe à coluna atual
        const currentHeader = document.querySelector(`#users-table th[data-sort="${column}"]`);
        if (currentHeader) {
            currentHeader.classList.add(`sorting-${sortDirection}`);
        }
        
        applyFilters();
    }

    // Funções auxiliares
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }

    // Inicialização
    function init() {
        // Carregar dados
        loadEmployees();
        
        // Configurar event listeners
        clearFiltersBtn.addEventListener('click', clearFilters);
        
        // Configurar filtros
        Object.values(filters).forEach(filter => {
            if (filter.tagName === 'SELECT') {
                filter.addEventListener('change', applyFilters);
            } else {
                filter.addEventListener('input', applyFilters);
            }
        });
        
        // Configurar ordenação
        document.querySelectorAll('#users-table th[data-sort]').forEach(th => {
            th.addEventListener('click', () => sortTable(th.dataset.sort));
        });
        
        // Evento para recarregar tabela
        window.addEventListener('reloadTable', loadEmployees);
        
        // Mostrar notificação de carregamento
        setTimeout(() => {
            const stats = window.employeesData.getEmployeeStats();
            showNotification(`Sistema carregado com ${stats.total} funcionários`, 'success');
        }, 1000);
    }

    // Iniciar o sistema
    init();
});