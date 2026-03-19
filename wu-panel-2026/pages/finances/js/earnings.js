// Configurações
const accountsPerPage = 10;
let currentAccountPage = 1;
let currentEditingAccountId = null;

// Elementos DOM
const accountElements = {
    list: document.getElementById('accounts-list'),
    pagination: document.getElementById('pagination'),
    searchInputs: {
        id: document.getElementById('account-id'),
        name: document.getElementById('account-name'),
        operator: document.getElementById('account-operator'),
        currency: document.getElementById('account-currency'),
        date: document.getElementById('account-date')
    },
    clearFiltersBtn: document.getElementById('clear-filters'),
    editModal: new bootstrap.Modal(document.getElementById('editAccountModal')),
    deleteModal: new bootstrap.Modal(document.getElementById('deleteAccountModal')),
    saveBtn: document.getElementById('save-account-changes'),
    confirmDeleteBtn: document.getElementById('confirm-account-delete'),
    deletePassword: document.getElementById('delete-account-password')
};

// Inicialização
document.addEventListener('DOMContentLoaded', initAccounts);

function initAccounts() {
    setupAccountsEventListeners();
    filterAccounts();
}

function setupAccountsEventListeners() {
    // Filtros
    Object.values(accountElements.searchInputs).forEach(input => {
        input.addEventListener('input', () => {
            currentAccountPage = 1;
            filterAccounts();
        });
    });

    // Limpar filtros
    accountElements.clearFiltersBtn.addEventListener('click', clearAccountFilters);

    // Salvar edição
    accountElements.saveBtn.addEventListener('click', saveAccountChanges);

    // Confirmar exclusão
    accountElements.confirmDeleteBtn.addEventListener('click', confirmAccountDelete);
}

// Funções de filtro
function filterAccounts() {
    const filters = getAccountFilters();
    const filteredAccounts = applyAccountFilters(filters);
    
    renderAccounts(filteredAccounts);
    renderAccountPagination(filteredAccounts.length);
}

function getAccountFilters() {
    return {
        id: accountElements.searchInputs.id.value,
        name: accountElements.searchInputs.name.value.toLowerCase(),
        operator: accountElements.searchInputs.operator.value.toLowerCase(),
        currency: accountElements.searchInputs.currency.value,
        date: accountElements.searchInputs.date.value
    };
}

function applyAccountFilters(filters) {
    return accounts.filter(account => {
        return (
            (!filters.id || account.id.toString().includes(filters.id)) &&
            (!filters.name || account.account.toLowerCase().includes(filters.name)) &&
            (!filters.operator || account.operator.toLowerCase().includes(filters.operator)) &&
            (!filters.currency || account.currency === filters.currency) &&
            (!filters.date || account.operation_date === filters.date)
        );
    });
}

function clearAccountFilters() {
    Object.values(accountElements.searchInputs).forEach(input => {
        input.value = '';
    });
    currentAccountPage = 1;
    filterAccounts();
}

// Renderização
function renderAccounts(filteredAccounts) {
    accountElements.list.innerHTML = '';
    
    const paginatedAccounts = filteredAccounts.slice(
        (currentAccountPage - 1) * accountsPerPage,
        currentAccountPage * accountsPerPage
    );

    paginatedAccounts.forEach(account => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${account.id}</td>
            <td>${account.account}</td>
            <td>${account.operator}</td>
            <td>${formatCurrency(account.previous_balance, account.currency)}</td>
            <td>${formatCurrency(account.additional_balance, account.currency)}</td>
            <td><strong>${formatCurrency(account.current_balance, account.currency)}</strong></td>
            <td>${getCurrencySymbol(account.currency)}</td>
            <td>${formatDate(account.operation_date)}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-1 edit-account-btn" data-id="${account.id}">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-account-btn" data-id="${account.id}">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        accountElements.list.appendChild(row);
    });

    // Configurar eventos dos botões
    document.querySelectorAll('.edit-account-btn').forEach(btn => {
        btn.addEventListener('click', () => openEditAccountModal(btn.dataset.id));
    });

    document.querySelectorAll('.delete-account-btn').forEach(btn => {
        btn.addEventListener('click', () => openDeleteAccountModal(btn.dataset.id));
    });
}

// Funções auxiliares
function formatCurrency(amount, currency) {
    const formatter = new Intl.NumberFormat(getLocale(currency), {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2
    });
    return formatter.format(amount);
}

function getCurrencySymbol(currency) {
    const symbols = {
        USD: '$',
        EUR: '€',
        AOA: 'Kz'
    };
    return symbols[currency] || currency;
}

function getLocale(currency) {
    const locales = {
        USD: 'en-US',
        EUR: 'de-DE',
        AOA: 'pt-AO'
    };
    return locales[currency] || 'pt-PT';
}

function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('pt-AO', options);
}

// Paginação
function renderAccountPagination(totalItems) {
    accountElements.pagination.innerHTML = '';
    const totalPages = Math.ceil(totalItems / accountsPerPage);
    
    // Botão Anterior
    addAccountPaginationItem('Anterior', currentAccountPage - 1, currentAccountPage === 1);
    
    // Páginas
    for (let i = 1; i <= totalPages; i++) {
        addAccountPaginationItem(i, i, false, i === currentAccountPage);
    }
    
    // Botão Próximo
    addAccountPaginationItem('Próximo', currentAccountPage + 1, currentAccountPage === totalPages);
}

function addAccountPaginationItem(text, page, disabled = false, active = false) {
    const li = document.createElement('li');
    li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;
    
    const a = document.createElement('a');
    a.className = 'page-link';
    a.href = '#';
    a.textContent = text;
    
    if (!disabled && !active) {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            currentAccountPage = page;
            filterAccounts();
        });
    }
    
    li.appendChild(a);
    accountElements.pagination.appendChild(li);
}

// Modal de Edição
function openEditAccountModal(accountId) {
    currentEditingAccountId = accountId;
    const account = accounts.find(a => a.id == accountId);
    
    if (!account) return;
    
    document.getElementById('edit-account-body').innerHTML = `
        <form id="account-form">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nome da Conta</label>
                        <input type="text" class="form-control" name="account" value="${account.account}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Operador</label>
                        <input type="text" class="form-control" name="operator" value="${account.operator}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Moeda</label>
                        <select class="form-select" name="currency" required>
                            <option value="AOA" ${account.currency === 'AOA' ? 'selected' : ''}>Kwanza (AOA)</option>
                            <option value="USD" ${account.currency === 'USD' ? 'selected' : ''}>Dólar (USD)</option>
                            <option value="EUR" ${account.currency === 'EUR' ? 'selected' : ''}>Euro (EUR)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Saldo Anterior</label>
                        <input type="number" class="form-control" name="previous_balance" value="${account.previous_balance}" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Saldo Adicional</label>
                        <input type="number" class="form-control" name="additional_balance" value="${account.additional_balance}" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data da Operação</label>
                        <input type="date" class="form-control" name="operation_date" value="${account.operation_date}" required>
                    </div>
                </div>
            </div>
        </form>
    `;
    
    accountElements.editModal.show();
}

function saveAccountChanges() {
    // Aqui você implementaria a lógica para salvar no backend
    alert(`Conta ${currentEditingAccountId} atualizada com sucesso!`);
    accountElements.editModal.hide();
    filterAccounts();
}

// Modal de Exclusão
function openDeleteAccountModal(accountId) {
    currentEditingAccountId = accountId;
    const account = accounts.find(a => a.id == accountId);
    
    if (!account) return;
    
    const modalBody = accountElements.deleteModal._element.querySelector('.modal-body');
    modalBody.querySelector('p').textContent = `Tem certeza que deseja excluir a conta "${account.account}"?`;
    
    accountElements.deletePassword.value = '';
    accountElements.deleteModal.show();
}

function confirmAccountDelete() {
    const password = accountElements.deletePassword.value.trim();
    
    if (!password) {
        accountElements.deletePassword.classList.add('is-invalid');
        return;
    }
    
    accountElements.deletePassword.classList.remove('is-invalid');
    
    // Aqui você faria a verificação da senha no backend
    if (confirm('Esta ação é irreversível. Confirmar exclusão?')) {
        alert(`Conta ${currentEditingAccountId} excluída com sucesso!`);
        accountElements.deleteModal.hide();
        filterAccounts();
    }
}