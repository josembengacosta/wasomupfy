
// Configurações
const artistsPerPage = 10;
let currentArtistPage = 1;
let currentEditingArtistId = null;

// Elementos DOM
const artistElements = {
    list: document.getElementById('artists-list'),
    pagination: document.getElementById('pagination'),
    resultsCount: document.getElementById('results-count'),
    searchInputs: {
        id: document.getElementById('artist-id'),
        name: document.getElementById('artist-name'),
        role: document.getElementById('artist-role'),
        date: document.getElementById('artist-date')
    },
    clearFiltersBtn: document.getElementById('clear-filters'),
    editModal: new bootstrap.Modal(document.getElementById('editArtistModal')),
    deleteModal: new bootstrap.Modal(document.getElementById('deleteArtistModal')),
    saveBtn: document.getElementById('save-artist-changes'),
    confirmDeleteBtn: document.getElementById('confirm-artist-delete'),
    deletePassword: document.getElementById('delete-artist-password')
};

// Inicialização
document.addEventListener('DOMContentLoaded', initArtists);

function initArtists() {
    setupArtistsEventListeners();
    filterArtists();
}

function setupArtistsEventListeners() {
    // Filtros
    Object.values(artistElements.searchInputs).forEach(input => {
        input.addEventListener('input', () => {
            currentArtistPage = 1;
            filterArtists();
        });
    });

    // Limpar filtros
    artistElements.clearFiltersBtn.addEventListener('click', clearArtistFilters);

    // Salvar edição
    artistElements.saveBtn.addEventListener('click', saveArtistChanges);

    // Confirmar exclusão
    artistElements.confirmDeleteBtn.addEventListener('click', confirmArtistDelete);
}

// Funções de filtro
function filterArtists() {
    const filters = getArtistFilters();
    const filteredArtists = applyArtistFilters(filters);
    
    renderArtists(filteredArtists);
    renderArtistPagination(filteredArtists.length);
    updateResultsCount(filteredArtists.length);
}

function getArtistFilters() {
    return {
        id: artistElements.searchInputs.id.value,
        name: artistElements.searchInputs.name.value.toLowerCase(),
        role: artistElements.searchInputs.role.value,
        date: artistElements.searchInputs.date.value
    };
}

function applyArtistFilters(filters) {
    return artists.filter(artist => {
        return (
            (!filters.id || artist.id.toString().includes(filters.id)) &&
            (!filters.name || artist.name.toLowerCase().includes(filters.name)) &&
            (!filters.role || artist.role === filters.role) &&
            (!filters.date || artist.creation_date === filters.date)
        );
    });
}

function clearArtistFilters() {
    Object.values(artistElements.searchInputs).forEach(input => {
        input.value = '';
    });
    currentArtistPage = 1;
    filterArtists();
}

function updateResultsCount(count) {
    artistElements.resultsCount.textContent = `${count} ${count === 1 ? 'resultado' : 'resultados'}`;
}

// Renderização
function renderArtists(filteredArtists) {
    artistElements.list.innerHTML = '';
    
    const paginatedArtists = filteredArtists.slice(
        (currentArtistPage - 1) * artistsPerPage,
        currentArtistPage * artistsPerPage
    );

    paginatedArtists.forEach(artist => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${artist.id}</td>
            <td>${artist.account}</td>
            <td>${artist.name}</td>
            <td>${getRoleBadge(artist.role)}</td>
            <td>
                ${artist.spotify ? `<a href="${artist.spotify}" target="_blank"><i class="bi bi-spotify text-success"></i></a>` : '-'}
            </td>
            <td>
                ${artist.apple_music ? `<a href="${artist.apple_music}" target="_blank"><i class="bi bi-apple text-primary"></i></a>` : '-'}
            </td>
            <td>${formatDate(artist.creation_date)}</td>
            <td>
                <button class="btn btn-sm btn-primary me-1 edit-artist-btn" data-id="${artist.id}">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-artist-btn" data-id="${artist.id}">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        artistElements.list.appendChild(row);
    });

    // Configurar eventos dos botões
    document.querySelectorAll('.edit-artist-btn').forEach(btn => {
        btn.addEventListener('click', () => openEditArtistModal(btn.dataset.id));
    });

    document.querySelectorAll('.delete-artist-btn').forEach(btn => {
        btn.addEventListener('click', () => openDeleteArtistModal(btn.dataset.id));
    });
}

// Funções auxiliares
function getRoleBadge(role) {
    const roles = {
        interpreter: { text: 'Intérprete', class: 'primary' },
        composer: { text: 'Compositor', class: 'info' },
        producer: { text: 'Produtor', class: 'warning' }
    };
    
    const roleInfo = roles[role] || { text: role, class: 'secondary' };
    return `<span class="badge bg-${roleInfo.class}">${roleInfo.text}</span>`;
}

function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('pt-AO', options);
}

// Paginação
function renderArtistPagination(totalItems) {
    artistElements.pagination.innerHTML = '';
    const totalPages = Math.ceil(totalItems / artistsPerPage);
    
    // Botão Anterior
    addArtistPaginationItem('Anterior', currentArtistPage - 1, currentArtistPage === 1);
    
    // Páginas
    for (let i = 1; i <= totalPages; i++) {
        addArtistPaginationItem(i, i, false, i === currentArtistPage);
    }
    
    // Botão Próximo
    addArtistPaginationItem('Próximo', currentArtistPage + 1, currentArtistPage === totalPages);
}

function addArtistPaginationItem(text, page, disabled = false, active = false) {
    const li = document.createElement('li');
    li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;
    
    const a = document.createElement('a');
    a.className = 'page-link';
    a.href = '#';
    a.textContent = text;
    
    if (!disabled && !active) {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            currentArtistPage = page;
            filterArtists();
        });
    }
    
    li.appendChild(a);
    artistElements.pagination.appendChild(li);
}

// Modal de Edição
function openEditArtistModal(artistId) {
    currentEditingArtistId = artistId;
    const artist = artists.find(a => a.id == artistId);
    
    if (!artist) return;
    
    document.getElementById('edit-artist-body').innerHTML = `
        <form id="artist-form">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nome do Artista</label>
                        <input type="text" class="form-control" name="name" value="${artist.name}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Conta</label>
                        <input type="text" class="form-control" name="account" value="${artist.account}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Função</label>
                        <select class="form-select" name="role" required>
                            <option value="interpreter" ${artist.role === 'interpreter' ? 'selected' : ''}>Intérprete</option>
                            <option value="composer" ${artist.role === 'composer' ? 'selected' : ''}>Compositor</option>
                            <option value="producer" ${artist.role === 'producer' ? 'selected' : ''}>Produtor</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Link Spotify</label>
                        <input type="url" class="form-control" name="spotify" value="${artist.spotify || ''}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link Apple Music</label>
                        <input type="url" class="form-control" name="apple_music" value="${artist.apple_music || ''}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data de Criação</label>
                        <input type="date" class="form-control" name="creation_date" value="${artist.creation_date}" required>
                    </div>
                </div>
            </div>
        </form>
    `;
    
    artistElements.editModal.show();
}

function saveArtistChanges() {
    // Aqui você implementaria a lógica para salvar no backend
    alert(`Artista ${currentEditingArtistId} atualizado com sucesso!`);
    artistElements.editModal.hide();
    filterArtists();
}

// Modal de Exclusão
function openDeleteArtistModal(artistId) {
    currentEditingArtistId = artistId;
    const artist = artists.find(a => a.id == artistId);
    
    if (!artist) return;
    
    const modalBody = artistElements.deleteModal._element.querySelector('.modal-body');
    modalBody.querySelector('p').textContent = `Tem certeza que deseja excluir o artista "${artist.name}"?`;
    
    artistElements.deletePassword.value = '';
    artistElements.deleteModal.show();
}

function confirmArtistDelete() {
    const password = artistElements.deletePassword.value.trim();
    
    if (!password) {
        artistElements.deletePassword.classList.add('is-invalid');
        return;
    }
    
    artistElements.deletePassword.classList.remove('is-invalid');
    
    // Aqui você faria a verificação da senha no backend
    if (confirm('Esta ação é irreversível. Confirmar exclusão?')) {
        alert(`Artista ${currentEditingArtistId} excluído com sucesso!`);
        artistElements.deleteModal.hide();
        filterArtists();
    }
}