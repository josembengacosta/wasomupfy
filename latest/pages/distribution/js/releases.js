// Configurações
const perPage = 10; // Itens por página
let currentPage = 1;
let currentAlbumId = null;

// Elementos DOM
const elements = {
    albumList: document.getElementById('album-list'),
    pagination: document.getElementById('pagination'),
    resultCount: document.getElementById('result-count'),
    searchInputs: {
        id: document.getElementById('search-id'),
        title: document.getElementById('search-title'),
        artist: document.getElementById('search-artist'),
        year: document.getElementById('search-year'),
        upc: document.getElementById('search-upc'),
        status: document.getElementById('search-status')
    },
    clearFiltersBtn: document.getElementById('clear-filters'),
    editModal: new bootstrap.Modal(document.getElementById('editModal')),
    deleteModal: new bootstrap.Modal(document.getElementById('deleteModal')),
    saveChangesBtn: document.getElementById('save-changes'),
    confirmDeleteBtn: document.getElementById('confirm-delete'),
    deletePassword: document.getElementById('delete-password')
};

// Inicialização
document.addEventListener('DOMContentLoaded', init);

function init() {
    setupEventListeners();
    filterAlbums();
}

function setupEventListeners() {
    // Filtros
    Object.values(elements.searchInputs).forEach(input => {
        input.addEventListener('input', () => {
            currentPage = 1;
            filterAlbums();
        });
    });

    // Limpar filtros
    elements.clearFiltersBtn.addEventListener('click', clearFilters);

    // Salvar edição
    elements.saveChangesBtn.addEventListener('click', saveChanges);

    // Confirmar exclusão
    elements.confirmDeleteBtn.addEventListener('click', confirmDelete);
}

// Função principal de filtragem
function filterAlbums() {
    const filters = getCurrentFilters();
    const filteredAlbums = applyFilters(filters);

    renderAlbums(filteredAlbums);
    renderPagination(filteredAlbums.length);
}

function getCurrentFilters() {
    return {
        id: elements.searchInputs.id.value,
        title: elements.searchInputs.title.value.toLowerCase(),
        artist: elements.searchInputs.artist.value.toLowerCase(),
        year: elements.searchInputs.year.value,
        upc: elements.searchInputs.upc.value.toLowerCase(),
        status: elements.searchInputs.status.value
    };
}

function applyFilters(filters) {
    return albums.filter(album => {
        return (
            (!filters.id || album.id.toString().includes(filters.id)) &&
            (!filters.title || album.title.toLowerCase().includes(filters.title)) &&
            (!filters.artist || album.artist.toLowerCase().includes(filters.artist)) &&
            (!filters.year || album.year.toString().includes(filters.year)) &&
            (!filters.upc || album.upc.toLowerCase().includes(filters.upc)) &&
            (!filters.status || album.status === filters.status)
        );
    });
}

function clearFilters() {
    Object.values(elements.searchInputs).forEach(input => {
        input.value = '';
    });
    currentPage = 1;
    filterAlbums();
}

// Renderização da tabela
function renderAlbums(filteredAlbums) {
    elements.albumList.innerHTML = '';

    const paginatedAlbums = filteredAlbums.slice(
        (currentPage - 1) * perPage,
        currentPage * perPage
    );

    elements.resultCount.textContent = `${filteredAlbums.length} álbuns encontrados`;

    paginatedAlbums.forEach(album => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${album.id}</td>
            <td>${album.title}</td>
            <td>${album.artist}</td>
            <td>${album.upc}</td>
            <td>${renderStatusBadge(album.status)}</td>
            <td>${album.genre}</td>
            <td>${album.year}</td>
            <td><img src="${album.cover}" alt="Capa" class="img-fluid" width="70"></td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-1 edit-btn" data-id="${album.id}">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${album.id}">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        elements.albumList.appendChild(row);
    });

    // Configurar eventos dos botões
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => openEditModal(btn.dataset.id));
    });

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', () => openDeleteModal(btn.dataset.id));
    });
}

function renderStatusBadge(status) {
    const statusMap = {
        approved: { text: 'Aprovado', class: 'success' },
        rejected: { text: 'Reprovado', class: 'danger' },
        pending: { text: 'Pendente', class: 'warning' },
        draft: { text: 'Rascunho', class: 'secondary' }
    };

    const statusInfo = statusMap[status] || { text: status, class: 'info' };
    return `<span class="badge bg-${statusInfo.class}">${statusInfo.text}</span>`;
}

// Paginação
function renderPagination(totalItems) {
    elements.pagination.innerHTML = '';
    const totalPages = Math.ceil(totalItems / perPage);

    // Botão Anterior
    addPaginationItem('Anterior', currentPage - 1, currentPage === 1);

    // Páginas
    for (let i = 1; i <= totalPages; i++) {
        addPaginationItem(i, i, false, i === currentPage);
    }

    // Botão Próximo
    addPaginationItem('Próximo', currentPage + 1, currentPage === totalPages);
}

function addPaginationItem(text, page, disabled = false, active = false) {
    const li = document.createElement('li');
    li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;

    const a = document.createElement('a');
    a.className = 'page-link';
    a.href = '#';
    a.textContent = text;

    if (!disabled && !active) {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            currentPage = page;
            filterAlbums();
        });
    }

    li.appendChild(a);
    elements.pagination.appendChild(li);
}

// Modal de Edição
function openEditModal(albumId) {
    currentAlbumId = albumId;
    const album = albums.find(a => a.id == albumId);

    if (!album) return;

    document.getElementById('edit-modal-body').innerHTML = `
        <form id="album-form">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" class="form-control" name="title" value="${album.title}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Artista</label>
                        <input type="text" class="form-control" name="artist" value="${album.artist}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ano</label>
                        <input type="number" class="form-control" name="year" value="${album.year}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">UPC</label>
                        <input type="text" class="form-control" name="upc" value="${album.upc}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gênero</label>
                        <input type="text" class="form-control" name="genre" value="${album.genre}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="approved" ${album.status === 'approved' ? 'selected' : ''}>Aprovado</option>
                            <option value="rejected" ${album.status === 'rejected' ? 'selected' : ''}>Reprovado</option>
                            <option value="pending" ${album.status === 'pending' ? 'selected' : ''}>Pendente</option>
                            <option value="draft" ${album.status === 'draft' ? 'selected' : ''}>Rascunho</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">URL da Capa</label>
                <input type="text" class="form-control" name="cover" value="${album.cover}">
            </div>
            <div class="mb-3">
                <label class="form-label">Faixas</label>
                <textarea class="form-control" name="tracks" rows="3">${album.tracks}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea class="form-control" name="description" rows="3">${album.description}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Link</label>
                <input type="url" class="form-control" name="link" value="${album.link}">
            </div>
        </form>
    `;

    elements.editModal.show();
}

function saveChanges() {
    // Aqui você implementaria a lógica para salvar no backend
    alert(`Álbum ${currentAlbumId} atualizado com sucesso!`);
    elements.editModal.hide();
    filterAlbums();
}

// Modal de Exclusão
function openDeleteModal(albumId) {
    currentAlbumId = albumId;
    const album = albums.find(a => a.id == albumId);

    if (!album) return;

    const modalBody = elements.deleteModal._element.querySelector('.modal-body');
    modalBody.querySelector('p').textContent = `Tem certeza que deseja excluir permanentemente o álbum "${album.title}"?`;

    elements.deletePassword.value = '';
    elements.deleteModal.show();
}

function confirmDelete() {
    const password = elements.deletePassword.value.trim();

    if (!password) {
        elements.deletePassword.classList.add('is-invalid');
        return;
    }

    elements.deletePassword.classList.remove('is-invalid');

    // Aqui você faria a verificação da senha no backend
    // Simulação de sucesso:
    if (confirm('Esta ação é irreversível. Confirmar exclusão?')) {
        alert(`Álbum ${currentAlbumId} excluído com sucesso!`);
        elements.deleteModal.hide();
        filterAlbums();
    }
}

// Exportar funções se necessário
export { init, filterAlbums, clearFilters };