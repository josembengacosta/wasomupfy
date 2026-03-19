// Configurações
const itemsPerPage = 5;
let currentPage = 1;
let currentEditingId = null;

// Elementos DOM
const elements = {
    list: document.getElementById("stats-list"),
    pagination: document.getElementById("stats-pagination"),
    resultsCount: document.getElementById("results-count"),
    searchInputs: {
        id: document.getElementById("stats-id"),
        account: document.getElementById("stats-account"),
        track: document.getElementById("stats-track"),
        artist: document.getElementById("stats-artist"),
        playlist: document.getElementById("stats-playlist"),
        territory: document.getElementById("stats-territory"),
    },
    clearBtn: document.getElementById("clear-stats-filters"),
    viewModal: new bootstrap.Modal("#viewStatsModal"),
    editModal: new bootstrap.Modal("#editStatsModal"),
    deleteModal: new bootstrap.Modal("#deleteStatsModal"),
    saveBtn: document.getElementById("save-stats-changes"),
    confirmDeleteBtn: document.getElementById("confirm-stats-delete"),
    deletePassword: document.getElementById("delete-stats-password"),
};

// Inicialização
document.addEventListener("DOMContentLoaded", initStats);

function initStats() {
    renderStats(statsData);
    setupEventListeners();
}

// Renderização principal
function renderStats(data) {
    elements.list.innerHTML = "";

    const paginatedData = data.slice(
        (currentPage - 1) * itemsPerPage,
        currentPage * itemsPerPage
    );

    elements.resultsCount.textContent = `${data.length} ${data.length === 1 ? "resultado" : "resultados"
        }`;

    paginatedData.forEach((item) => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${item.id}</td>
            <td>${item.account}</td>
            <td>${item.track}</td>
            <td>${item.artist}</td>
            <td>${formatNumber(item.streams)}</td>
            <td>${getPlatformIcon(item.platform)} ${getPlatformName(
            item.platform
        )}</td>
            <td>${item.playlist}</td>
            <td>${getTerritoryFlag(item.territory)} ${getTerritoryName(
            item.territory
        )}</td>
            <td>${formatDate(item.date)}</td>
            <td>
                <button class="btn btn-sm btn-outline-info view-btn me-1" data-id="${item.id
            }" title="Visualizar">
                    <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary edit-btn me-1" data-id="${item.id
            }" title="Editar">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${item.id
            }" title="Excluir">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        elements.list.appendChild(row);
    });

    renderPagination(data.length);
    setupActionButtons();
}

// Funções auxiliares
function formatNumber(num) {
    return new Intl.NumberFormat("pt-AO").format(num);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString("pt-AO");
}

function getTerritoryFlag(territory) {
    const flags = {
        AO: "🇦🇴",
        PT: "🇵🇹",
        BR: "🇧🇷",
    };
    return flags[territory] || "";
}

function getTerritoryName(territory) {
    const names = {
        AO: "Angola",
        PT: "Portugal",
        BR: "Brasil",
    };
    return names[territory] || territory;
}

function getPlatformIcon(platform) {
    const platformIcons = {
        spotify: '<i class="bi bi-spotify" style="color: #1DB954;"></i>',
        apple: '<i class="bi bi-apple" style="color: #e4e4e4ff;"></i>',
        snapchat: '<i class="bi bi-snapchat" style="color: #fcd63cff;"></i>',
        deezer: '<i class="bi bi-music-player" style="color: #f20089ff;"></i>',
        youtube: '<i class="bi bi-youtube" style="color: #FF0000;"></i>',
        "youtube-music": '<i class="bi bi-youtube" style="color: #FF0000;"></i>',
        tidal: '<i class="bi bi-music-note-list" style="color: #00FFFF;"></i>',
        "amazon-music": '<i class="bi bi-music-note" style="color: #fcd63cff;"></i>',
        soundcloud: '<i class="bi bi-cloud" style="color: #FF5500;"></i>',
        pandora: '<i class="bi bi-radioactive" style="color: #005483;"></i>',
        napster: '<i class="bi bi-music-note" style="color: #000000;"></i>',
        kkbox: '<i class="bi bi-music-note" style="color: #33CC33;"></i>',
        qobuz: '<i class="bi bi-music-note" style="color: #1A1A1A;"></i>',
        iheartradio: '<i class="bi bi-heart-fill" style="color: #C6002B;"></i>',
        audiomack: '<i class="bi bi-music-note" style="color: #FFA200;"></i>',
        bandcamp: '<i class="bi bi-music-note" style="color: #629AA9;"></i>',
        anghami: '<i class="bi bi-music-note" style="color: #F70F0F;"></i>',
        boomplay: '<i class="bi bi-music-note" style="color: #00D1B2;"></i>',
        joox: '<i class="bi bi-music-note" style="color: #FF5F5F;"></i>',
        gaana: '<i class="bi bi-music-note" style="color: #FF3D6E;"></i>',
        wynk: '<i class="bi bi-music-note" style="color: #4CAF50;"></i>',
        jiosaavn: '<i class="bi bi-music-note" style="color: #00B0FF;"></i>',
        tiktok: '<i class="bi bi-tiktok" style="color: #010101;"></i>',
        triller: '<i class="bi bi-music-note" style="color: #FF0080;"></i>',
        shazam: '<i class="bi bi-search" style="color: #0088FF;"></i>',
        discogs: '<i class="bi bi-vinyl-fill" style="color: #333333;"></i>',
        beatport: '<i class="bi bi-music-note" style="color: #94D500;"></i>',
        junodownload: '<i class="bi bi-music-note" style="color: #FF6600;"></i>',
        traxsource: '<i class="bi bi-music-note" style="color: #00B4FF;"></i>',
        reverbnation: '<i class="bi bi-music-note" style="color: #E4352B;"></i>',
        audiophile: '<i class="bi bi-music-note" style="color: #6B3FA0;"></i>',
    };

    return (
        platformIcons[platform.toLowerCase()] ||
        `<i class="bi bi-music-note" style="color: #6c757d;"></i> ${platform}`
    );
}

function getPlatformName(platform) {
    const platformNames = {
        // Plataformas globais
        spotify: "Spotify",
        apple: "Apple Music",
        deezer: "Deezer",
        youtube: "YouTube Music",
        "youtube-music": "YouTube Music",
        tidal: "Tidal",
        "amazon-music": "Amazon Music",
        soundcloud: "SoundCloud",
        pandora: "Pandora",
        napster: "Napster",
        iheartradio: "iHeartRadio",
        audiomack: "Audiomack",
        bandcamp: "Bandcamp",
        shazam: "Shazam",

        // Plataformas asiáticas
        kkbox: "KKBOX",
        joox: "JOOX",
        melon: "Melon",
        "line-music": "LINE Music",
        bugs: "Bugs!",

        // Plataformas indianas
        gaana: "Gaana",
        wynk: "Wynk",
        jiosaavn: "JioSaavn",
        hungama: "Hungama",

        // Plataformas africanas
        anghami: "Anghami",
        boomplay: "Boomplay",
        mdundo: "Mdundo",

        // Plataformas latino-americanas
        "claro-musica": "Claro Música",
        "tigo-music": "Tigo Music",
        "movistar-musica": "Movistar Música",

        // Plataformas de DJs/produção
        beatport: "Beatport",
        traxsource: "Traxsource",
        junodownload: "Juno Download",
        bandlab: "BandLab",

        // Plataformas de descoberta
        discogs: "Discogs",
        lastfm: "Last.fm",
        musixmatch: "Musixmatch",

        // Redes sociais/short-form
        tiktok: "TikTok",
        triller: "Triller",
        instagram: "Instagram",
        snapchat: "Snapchat",

        // Plataformas especializadas
        qobuz: "Qobuz",
        idagio: "Idagio",
        primephonic: "Primephonic",

        // Plataformas de áudio 3D
        sonicast: "Sonicast",
        dolby: "Dolby Atmos Music",

        // Outras
        "8tracks": "8tracks",
        slacker: "Slacker Radio",
        rhapsody: "Rhapsody",
        groove: "Groove Music",
        "yandex-music": "Yandex Music",
        "vk-music": "VK Music",
    };

    // Verifica se a plataforma existe no dicionário (case insensitive)
    const normalizedPlatform = platform.toLowerCase().replace(/\s+/g, "-");
    return (
        platformNames[normalizedPlatform] ||
        platform
            .split("-")
            .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(" ")
    );
}

// Ações (Visualizar, Editar, Excluir)
function setupActionButtons() {
    // Visualizar
    document.querySelectorAll(".view-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
            const itemId = parseInt(btn.dataset.id);
            const item = statsData.find((item) => item.id === itemId);
            showViewModal(item);
        });
    });

    // Editar
    document.querySelectorAll(".edit-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
            currentEditingId = parseInt(btn.dataset.id);
            const item = statsData.find((item) => item.id === currentEditingId);
            openEditModal(item);
        });
    });

    // Excluir
    document.querySelectorAll(".delete-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
            currentEditingId = parseInt(btn.dataset.id);
            const item = statsData.find((item) => item.id === currentEditingId);
            document.getElementById(
                "delete-stats-message"
            ).textContent = `Tem certeza que deseja excluir os dados de "${item.track}"?`;
            elements.deleteModal.show();
        });
    });
}

// Modal de Visualização
function showViewModal(item) {
    document.getElementById("stats-cover").src = item.cover;
    document.getElementById("view-track").textContent = item.track;
    document.getElementById("view-artist").textContent = item.artist;
    document.getElementById("view-streams").textContent = formatNumber(
        item.streams
    );
    document.getElementById("view-platform").innerHTML = `${getPlatformIcon(
        item.platform
    )} ${getPlatformName(item.platform)}`;
    document.getElementById("view-playlist").textContent = item.playlist;
    document.getElementById("view-territory").textContent = `${getTerritoryFlag(
        item.territory
    )} ${getTerritoryName(item.territory)}`;
    document.getElementById("view-date").textContent = formatDate(item.date);
    document.getElementById("view-link").href = item.link;

    elements.viewModal.show();
}

// Modal de Edição
function openEditModal(item) {
    document.getElementById("edit-stats-body").innerHTML = `
        <form id="edit-stats-form">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Música</label>
                        <input type="text" class="form-control" id="edit-track" value="${item.track
        }" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Artista</label>
                        <input type="text" class="form-control" id="edit-artist" value="${item.artist
        }" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Streams</label>
                        <input type="number" class="form-control" id="edit-streams" value="${item.streams
        }" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Plataforma</label>
                        <select class="form-select" id="edit-platform" required>
                            <option value="spotify" ${item.platform === "spotify" ? "selected" : ""
        }>Spotify</option>
                            <option value="apple" ${item.platform === "apple" ? "selected" : ""
        }>Apple Music</option>
                            <option value="deezer" ${item.platform === "deezer" ? "selected" : ""
        }>Deezer</option>
                            <option value="youtube" ${item.platform === "youtube" ? "selected" : ""
        }>YouTube</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Playlist</label>
                        <input type="text" class="form-control" id="edit-playlist" value="${item.playlist
        }">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Território</label>
                        <select class="form-select" id="edit-territory" required>
                            <option value="AO" ${item.territory === "AO" ? "selected" : ""
        }>Angola</option>
                            <option value="PT" ${item.territory === "PT" ? "selected" : ""
        }>Portugal</option>
                            <option value="BR" ${item.territory === "BR" ? "selected" : ""
        }>Brasil</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">URL da Capa</label>
                <input type="url" class="form-control" id="edit-cover" value="${item.cover
        }">
            </div>
            <div class="mb-3">
                <label class="form-label">Link</label>
                <input type="url" class="form-control" id="edit-link" value="${item.link
        }">
            </div>
        </form>
    `;

    elements.editModal.show();
}

// Filtros
function applyFilters() {
    const filters = {
        id: elements.searchInputs.id.value,
        account: elements.searchInputs.account.value.toLowerCase(),
        track: elements.searchInputs.track.value.toLowerCase(),
        artist: elements.searchInputs.artist.value.toLowerCase(),
        playlist: elements.searchInputs.playlist.value,
        territory: elements.searchInputs.territory.value,
    };

    const filtered = statsData.filter((item) => {
        return (
            (!filters.id || item.id.toString().includes(filters.id)) &&
            (!filters.account ||
                item.account.toLowerCase().includes(filters.account)) &&
            (!filters.track || item.track.toLowerCase().includes(filters.track)) &&
            (!filters.artist || item.artist.toLowerCase().includes(filters.artist)) &&
            (!filters.playlist || item.playlist === filters.playlist) &&
            (!filters.territory || item.territory === filters.territory)
        );
    });

    currentPage = 1;
    renderStats(filtered);
}

// Paginação
function renderPagination(totalItems) {
    elements.pagination.innerHTML = "";
    const totalPages = Math.ceil(totalItems / itemsPerPage);

    // Botão Anterior
    const prevLi = document.createElement("li");
    prevLi.className = `page-item ${currentPage === 1 ? "disabled" : ""}`;
    prevLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage - 1
        }">Anterior</a>`;
    elements.pagination.appendChild(prevLi);

    // Páginas
    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement("li");
        li.className = `page-item ${i === currentPage ? "active" : ""}`;
        li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
        elements.pagination.appendChild(li);
    }

    // Botão Próximo
    const nextLi = document.createElement("li");
    nextLi.className = `page-item ${currentPage === totalPages ? "disabled" : ""
        }`;
    nextLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage + 1
        }">Próximo</a>`;
    elements.pagination.appendChild(nextLi);

    // Eventos
    document.querySelectorAll(".page-link").forEach((link) => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            const page = parseInt(link.dataset.page);
            if (!isNaN(page) && page >= 1 && page <= totalPages) {
                currentPage = page;
                applyFilters();
            }
        });
    });
}

// Event Listeners
function setupEventListeners() {
    // Filtros
    Object.values(elements.searchInputs).forEach((input) => {
        input.addEventListener("input", applyFilters);
    });

    // Limpar filtros
    elements.clearBtn.addEventListener("click", () => {
        Object.values(elements.searchInputs).forEach((input) => {
            if (input.tagName === "SELECT") {
                input.value = "";
            } else {
                input.value = "";
            }
        });
        currentPage = 1;
        renderStats(statsData);
    });

    // Salvar edição
    elements.saveBtn.addEventListener("click", () => {
        // Implemente a lógica para salvar
        alert(`Dados ${currentEditingId} salvos com sucesso!`);
        elements.editModal.hide();
    });

    // Confirmar exclusão
    elements.confirmDeleteBtn.addEventListener("click", () => {
        if (!elements.deletePassword.value) {
            elements.deletePassword.classList.add("is-invalid");
            return;
        }

        // Implemente a lógica para excluir
        alert(`Dados ${currentEditingId} excluídos com sucesso!`);
        elements.deleteModal.hide();
        elements.deletePassword.value = "";
        elements.deletePassword.classList.remove("is-invalid");
    });
}
