// Configurações
const itemsPerPage = 10; // Itens por página (teste com 2 para ver paginação)
let currentPage = 1;
let currentEditingId = null;
let filteredData = [];

// Elementos DOM
const elements = {
    list: document.getElementById('youtube-list'),
    pagination: document.getElementById('pagination'),
    searchInputs: {
        id: document.getElementById('youtube-id'),
        account: document.getElementById('youtube-account'),
        artist: document.getElementById('youtube-artist'),
        status: document.getElementById('youtube-status'),
        date: document.getElementById('youtube-date')
    },
    clearBtn: document.getElementById('clear-filters'),
    editModal: new bootstrap.Modal('#editYoutubeModal'),
    deleteModal: new bootstrap.Modal('#deleteYoutubeModal'),
    saveBtn: document.getElementById('save-youtube-changes'),
    confirmDeleteBtn: document.getElementById('confirm-delete'),
    deletePassword: document.getElementById('delete-password'),
    deleteConfirmText: document.getElementById('delete-confirm-text')
};

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    filteredData = [...youtubeChannels];
    renderTable();
    setupEventListeners();
});

// ==================== RENDERIZAÇÃO ====================
function renderTable() {
    elements.list.innerHTML = '';
    
    const startIdx = (currentPage - 1) * itemsPerPage;
    const paginatedItems = filteredData.slice(startIdx, startIdx + itemsPerPage);
    
    paginatedItems.forEach(item => {
        const row = `
            <tr>
                <td>${item.id}</td>
                <td>${item.account}</td>
                <td>${item.artist}</td>
                <td><a href="${item.channel_link}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-youtube"></i> Ver canal</a></td>
                <td><span class="badge ${getStatusClass(item.status)}">${getStatusText(item.status)}</span></td>
                <td>${formatDate(item.request_date)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary edit-btn me-1" data-id="${item.id}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${item.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        elements.list.insertAdjacentHTML('beforeend', row);
    });
    
    renderPagination();
    setupActionButtons();
}

function renderPagination() {
    elements.pagination.innerHTML = '';
    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    
    // Botão Anterior
    elements.pagination.insertAdjacentHTML('beforeend', `
        <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="prev"><i class="bi bi-chevron-left"></i></a>
        </li>
    `);
    
    // Páginas
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        elements.pagination.insertAdjacentHTML('beforeend', `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `);
    }
    
    // Botão Próximo
    elements.pagination.insertAdjacentHTML('beforeend', `
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
    const classes = {
        verified: "bg-success",
        pending: "bg-warning text-dark",
        rejected: "bg-danger",
        expired: "bg-secondary"
    };
    return classes[status] || "bg-secondary";
}

function getStatusText(status) {
    const texts = {
        verified: "Verificado",
        pending: "Pendente",
        rejected: "Rejeitado",
        expired: "Expirado"
    };
    return texts[status] || status;
}

function formatDate(dateString) {
    const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('pt-AO', options);
}

// ==================== FILTROS ====================
function filterData() {
    const filters = {
        id: elements.searchInputs.id.value,
        account: elements.searchInputs.account.value.toLowerCase(),
        artist: elements.searchInputs.artist.value.toLowerCase(),
        status: elements.searchInputs.status.value,
        date: elements.searchInputs.date.value
    };
    
    filteredData = youtubeChannels.filter(item => {
        return (
            (!filters.id || item.id.toString().includes(filters.id)) &&
            (!filters.account || item.account.toLowerCase().includes(filters.account)) &&
            (!filters.artist || item.artist.toLowerCase().includes(filters.artist)) &&
            (!filters.status || item.status === filters.status) &&
            (!filters.date || item.request_date === filters.date)
        );
    });
    
    currentPage = 1;
    renderTable();
}

// ==================== MODAIS ====================
function openEditModal(item) {
    currentEditingId = item.id;
    
    document.getElementById('edit-youtube-body').innerHTML = `
        <form id="edit-form">
            <div class="mb-3">
                <label class="form-label">Conta</label>
                <input type="text" class="form-control" id="edit-account" value="${item.account}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Artista</label>
                <input type="text" class="form-control" id="edit-artist" value="${item.artist}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Link do Canal</label>
                <input type="url" class="form-control" id="edit-link" value="${item.channel_link}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Estado</label>
                <select class="form-select" id="edit-status" required>
                    <option value="verified" ${item.status === 'verified' ? 'selected' : ''}>Verificado</option>
                    <option value="pending" ${item.status === 'pending' ? 'selected' : ''}>Pendente</option>
                    <option value="rejected" ${item.status === 'rejected' ? 'selected' : ''}>Rejeitado</option>
                    <option value="expired" ${item.status === 'expired' ? 'selected' : ''}>Expirado</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Data de Pedido</label>
                <input type="date" class="form-control" id="edit-date" value="${item.request_date}" required>
            </div>
        </form>
    `;
    
    elements.editModal.show();
}

function openDeleteModal(item) {
    currentEditingId = item.id;
    elements.deleteConfirmText.textContent = `Tem certeza que deseja excluir o canal "${item.artist}"?`;
    elements.deleteModal.show();
}

function saveChanges() {
    const account = document.getElementById('edit-account').value;
    const artist = document.getElementById('edit-artist').value;
    const link = document.getElementById('edit-link').value;
    const status = document.getElementById('edit-status').value;
    const date = document.getElementById('edit-date').value;
    
    // Atualiza os dados (substitua por chamada AJAX)
    const index = youtubeChannels.findIndex(c => c.id === currentEditingId);
    if (index !== -1) {
        youtubeChannels[index] = {
            ...youtubeChannels[index],
            account,
            artist,
            channel_link: link,
            status,
            request_date: date
        };
    }
    
    elements.editModal.hide();
    filterData(); // Reaplica os filtros
    alert("Alterações salvas com sucesso!");
}

function confirmDelete() {
    const password = elements.deletePassword.value;
    
    if (!password) {
        elements.deletePassword.classList.add('is-invalid');
        return;
    }
    
    // Simula chamada AJAX (substitua pela real)
    setTimeout(() => {
        const index = youtubeChannels.findIndex(c => c.id === currentEditingId);
        if (index !== -1) {
            youtubeChannels.splice(index, 1);
        }
        
        elements.deleteModal.hide();
        elements.deletePassword.value = '';
        elements.deletePassword.classList.remove('is-invalid');
        filterData(); // Reaplica os filtros
        alert("Canal excluído com sucesso!");
    }, 500);
}

// ==================== EVENT LISTENERS ====================
function setupEventListeners() {
    // Filtros
    Object.values(elements.searchInputs).forEach(input => {
        input.addEventListener('input', filterData);
    });
    
    // Limpar filtros
    elements.clearBtn.addEventListener('click', () => {
        Object.values(elements.searchInputs).forEach(input => input.value = '');
        filterData();
    });
    
    // Modais
    elements.saveBtn.addEventListener('click', saveChanges);
    elements.confirmDeleteBtn.addEventListener('click', confirmDelete);
    
    // Fechar modal limpa senha
    elements.deleteModal._element.addEventListener('hidden.bs.modal', () => {
        elements.deletePassword.value = '';
        elements.deletePassword.classList.remove('is-invalid');
    });
}

function setupActionButtons() {
    // Botões de edição/exclusão dinâmicos
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const item = youtubeChannels.find(c => c.id === parseInt(btn.dataset.id));
            if (item) openEditModal(item);
        });
    });
    
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const item = youtubeChannels.find(c => c.id === parseInt(btn.dataset.id));
            if (item) openDeleteModal(item);
        });
    });
}