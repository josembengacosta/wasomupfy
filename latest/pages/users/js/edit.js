// Configurações
const itemsPerPage = 10;
let currentPage = 1;
let currentEditingId = null; // Renomeado para ficar claro que é para edição
let filteredData = [];


// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    filteredData = [...usersData];
    renderTable();
    updateResultsCount();
    setupEventListeners();
    
    // Inicializa o select múltiplo de funções
    new MultiSelectTag('user-role', {
        rounded: false,
        shadow: true,
        placeholder: 'Funções'
    });

    // Listener para salvar alterações
    document.getElementById('save-user-changes').addEventListener('click', saveUserChanges);
});

// ==================== RENDERIZAÇÃO DA TABELA ====================
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
                    <button class="btn btn-sm btn-outline-primary edit-btn" data-id="${user.id}" title="Editar usuário">
                        <i class="bi bi-pencil"></i>
                    </button>
                </td>
            </tr>
        `;
        usersList.insertAdjacentHTML('beforeend', row);
    });
    
    renderPagination();
    setupActionButtons(); // Configura os botões de edição
}

// ==================== MODAL DE EDIÇÃO ====================
function openEditModal(userId) {
    const user = usersData.find(u => u.id === userId);
    if (!user) return;

    currentEditingId = user.id;
    
    // Preenche o formulário
    document.getElementById('edit-name').value = user.name;
    document.getElementById('edit-username').value = user.username;
    document.getElementById('edit-account').value = user.account;
    document.getElementById('edit-email').value = user.email;
    document.getElementById('edit-phone').value = user.phone;
    document.getElementById('edit-plan').value = user.plan;
    document.getElementById('edit-country').value = user.country;
    document.getElementById('edit-city').value = user.city;
    document.getElementById('edit-status').value = user.status;
    
    // Marca os checkboxes de funções
    document.querySelectorAll('.role-checkbox').forEach(checkbox => {
        checkbox.checked = user.roles.includes(checkbox.value);
    });
    
    // Atualiza o ID no título
    document.getElementById('edit-user-id-badge').textContent = `#${user.id}`;
    
    // Abre o modal
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function saveUserChanges() {
    const userIndex = usersData.findIndex(u => u.id === currentEditingId);
    if (userIndex === -1) return;

    // Atualiza os dados do usuário
    usersData[userIndex] = {
        ...usersData[userIndex], // Mantém os dados não editados (ex: foto, streaming)
        name: document.getElementById('edit-name').value,
        username: document.getElementById('edit-username').value,
        account: document.getElementById('edit-account').value,
        email: document.getElementById('edit-email').value,
        phone: document.getElementById('edit-phone').value,
        plan: document.getElementById('edit-plan').value,
        country: document.getElementById('edit-country').value,
        city: document.getElementById('edit-city').value,
        status: document.getElementById('edit-status').value,
        roles: Array.from(document.querySelectorAll('.role-checkbox:checked')).map(cb => cb.value)
    };

    // Fecha o modal e atualiza a tabela
    bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
    renderTable();
    
    console.log('Usuário atualizado:', usersData[userIndex]); // Para debug
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

function updateResultsCount() {
    document.getElementById('user-results-count').textContent = `${filteredData.length} resultados`;
}

// ==================== FILTROS E PAGINAÇÃO ====================
function filterData() {
    const id = document.getElementById('user-id').value;
    const account = document.getElementById('user-account').value.toLowerCase();
    const name = document.getElementById('user-name').value.toLowerCase();
    const roles = Array.from(document.getElementById('user-role').selectedOptions).map(opt => opt.value);
    const country = document.getElementById('user-country').value;
    const status = document.getElementById('user-status').value;
    
    filteredData = usersData.filter(user => {
        const matchesBasicFilters = (
            (!id || user.id.toString().includes(id)) &&
            (!account || user.account.toLowerCase().includes(account)) &&
            (!name || user.name.toLowerCase().includes(name)) &&
            (!country || user.country === country) &&
            (!status || user.status === status)
        );
        
        const matchesRoles = roles.length === 0 || roles.some(role => user.roles.includes(role));
        
        return matchesBasicFilters && matchesRoles;
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
    
    const roleSelect = document.getElementById('user-role');
    Array.from(roleSelect.options).forEach(opt => opt.selected = false);
    
    filterData();
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
    
    // Eventos de clique
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
    // Botões de edição na tabela
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const userId = parseInt(btn.dataset.id);
            openEditModal(userId);
        });
    });
}

// ==================== MULTISELECT TAG (PARA FILTRO DE FUNÇÕES) ====================
class MultiSelectTag {
    constructor(id, options = {}) {
        this.select = document.getElementById(id);
        this.options = options;
        this.init();
    }
    
    init() {
        this.select.style.display = 'none';
        
        this.container = document.createElement('div');
        this.container.className = 'multi-select-container';
        this.container.style.position = 'relative';
        
        this.display = document.createElement('div');
        this.display.className = 'form-select';
        this.display.style.cursor = 'pointer';
        this.display.textContent = this.options.placeholder || 'Selecione opções';
        
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'multi-select-dropdown';
        this.dropdown.style.display = 'none';
        this.dropdown.style.position = 'absolute';
        this.dropdown.style.width = '100%';
        this.dropdown.style.backgroundColor = 'white';
        this.dropdown.style.border = '1px solid #ced4da';
        this.dropdown.style.borderRadius = '0.375rem';
        this.dropdown.style.zIndex = '1000';
        this.dropdown.style.maxHeight = '200px';
        this.dropdown.style.overflowY = 'auto';
        
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
                
                checkbox.addEventListener('change', () => {
                    option.selected = checkbox.checked;
                    this.updateDisplay();
                    filterData();
                });
            }
        });
        
        this.display.addEventListener('click', () => {
            this.dropdown.style.display = this.dropdown.style.display === 'none' ? 'block' : 'none';
        });
        
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                this.dropdown.style.display = 'none';
            }
        });
        
        this.container.appendChild(this.display);
        this.container.appendChild(this.dropdown);
        this.select.parentNode.insertBefore(this.container, this.select.nextSibling);
    }
    
    updateDisplay() {
        const selected = Array.from(this.select.selectedOptions).map(opt => opt.text);
        this.display.textContent = selected.length > 0 ? 
            selected.join(', ') : 
            (this.options.placeholder || 'Selecione opções');
    }
}