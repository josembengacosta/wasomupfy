// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Lançamentos
// Arquivo: dashboard/launch/js/releases.js
// ══════════════════════════════════════════════
// Depende das constantes injectadas pelo PHP em releases.php:
//   CSRF, BASE_URL, ALBUMS_DB, DRAFT_KEY

// Ativar tooltips Bootstrap
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

// ════════════════════════════════════════════════
// CONSTANTES DERIVADAS
// ════════════════════════════════════════════════
const COVER_BASE = BASE_URL + '/assets/comprovantes/uploads/covers/';

// ════════════════════════════════════════════════
// RASCUNHOS — localStorage
// ════════════════════════════════════════════════
function getDrafts() {
    try {
        return JSON.parse(localStorage.getItem(DRAFT_KEY) || '[]');
    } catch (e) {
        return [];
    }
}

function saveDraft(draft) {
    const drafts = getDrafts();
    const idx = drafts.findIndex(d => d.id === draft.id);
    if (idx >= 0) drafts[idx] = draft;
    else drafts.push(draft);
    localStorage.setItem(DRAFT_KEY, JSON.stringify(drafts));
    updateDraftBadge();
}

function deleteDraft(draftId) {
    const drafts = getDrafts().filter(d => d.id !== draftId);
    localStorage.setItem(DRAFT_KEY, JSON.stringify(drafts));
    updateDraftBadge();
}

function updateDraftBadge() {
    const n = getDrafts().length;
    document.getElementById('draft-count-badge').textContent = n;
    document.getElementById('draft-count-badge').style.display = n ? '' : 'none';
}

// ════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════
const STATUS_LABEL = {
    approved:     'Aprovado',
    pending:      'Pendente',
    under_review: 'Em revisão',
    rejected:     'Reprovado',
    draft:        'Rascunho',
    deleting:     'A eliminar...'
};
const STATUS_CLASS = {
    approved:     'approved',
    pending:      'pending',
    under_review: 'warning',
    rejected:     'rejected',
    draft:        'draft',
    deleting:     'warning'
};
const TYPE_LABEL = {
    single:   'Single',
    ep:       'EP',
    EP:       'EP',
    album:    'Álbum',
    mixtape:  'Mixtape'
};

function fmt_date(str) {
    if (!str) return '—';
    const d = new Date(str);
    if (isNaN(d)) return str;
    return d.toLocaleDateString('pt-PT', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function cover_url(path) {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    return COVER_BASE + path;
}

// ════════════════════════════════════════════════
// STORE ICONS — 100% Bootstrap Icons (slug → ícone + cor)
// ════════════════════════════════════════════════
const STORE_ICONS = {
    // Streaming Global
    'spotify':              { icon: 'bi-spotify',               color: '#1db954' },
    'apple-music':          { icon: 'bi-apple',                 color: '#fc3c44' },
    'amazon-music':         { icon: 'bi-amazon',                color: '#ff9900' },
    'deezer':               { icon: 'bi-music-note-beamed',     color: '#ef5466' },
    'tidal':                { icon: 'bi-water',                 color: '#00ffff' },
    'boomplay':             { icon: 'bi-play-circle-fill',      color: '#f85d2f' },
    'youtube-music':        { icon: 'bi-youtube',               color: '#ff0000' },
    'soundcloud':           { icon: 'bi-soundwave',             color: '#ff5500' },
    'napster':              { icon: 'bi-music-note-list',       color: '#009bd9' },
    'iheart-radio':         { icon: 'bi-broadcast',             color: '#c6002b' },
    'audiomack':            { icon: 'bi-soundwave',             color: '#ffa500' },
    'qobuz':                { icon: 'bi-vinyl-fill',            color: '#003d7a' },
    // Streaming Ásia
    'jiosaavn':             { icon: 'bi-music-note-list',       color: '#2bc5b4' },
    'gaana':                { icon: 'bi-music-note-beamed',     color: '#e72a2a' },
    'wynk-music':           { icon: 'bi-headphones',            color: '#5a50f0' },
    'hungama':              { icon: 'bi-collection-play-fill',  color: '#e31837' },
    'netease-cloud-music':  { icon: 'bi-cloud-fill',            color: '#e60026' },
    'qq-music':             { icon: 'bi-music-player-fill',     color: '#fcb900' },
    'kugou':                { icon: 'bi-disc-fill',             color: '#1677ff' },
    'kuwo-music':           { icon: 'bi-music-note-beamed',     color: '#e60012' },
    'melon':                { icon: 'bi-circle-fill',           color: '#00cd3c' },
    'genie':                { icon: 'bi-stars',                 color: '#005bac' },
    'bugs':                 { icon: 'bi-music-note',            color: '#ff4f00' },
    'flo':                  { icon: 'bi-play-btn-fill',         color: '#7b2fff' },
    'kkbox':                { icon: 'bi-grid-fill',             color: '#009fee' },
    'joox':                 { icon: 'bi-play-circle-fill',      color: '#00c040' },
    'line-music':           { icon: 'bi-chat-fill',             color: '#00b900' },
    'awa':                  { icon: 'bi-soundwave',             color: '#111111' },
    'recochoku':            { icon: 'bi-headset',               color: '#e60020' },
    'anghami':              { icon: 'bi-music-note-beamed',     color: '#5b35d5' },
    'yandex-music':         { icon: 'bi-music-note-list',       color: '#fc3f1d' },
    'vk-music':             { icon: 'bi-person-fill',           color: '#0077ff' },
    'fizy':                 { icon: 'bi-music-note-beamed',     color: '#6b00d7' },
    // Streaming LATAM / Brasil
    'imusica':              { icon: 'bi-music-note-beamed',     color: '#e4002b' },
    'tim-music':            { icon: 'bi-phone-fill',            color: '#0033a0' },
    'triller':              { icon: 'bi-camera-video-fill',     color: '#ff3b30' },
    'claro-music':          { icon: 'bi-reception-4',           color: '#e30613' },
    // Streaming Rússia / Outros
    'zvuk':                 { icon: 'bi-vinyl',                 color: '#7b2fff' },
    'pandora':              { icon: 'bi-broadcast',             color: '#3668ff' },
    'resso':                { icon: 'bi-music-player',          color: '#ff4040' },
    // Download
    'itunes':               { icon: 'bi-bag-music-fill',        color: '#ea4cc0' },
    'beatport':             { icon: 'bi-headphones-fill',       color: '#02e75c' },
    'traxsource':           { icon: 'bi-vinyl-fill',            color: '#e4002b' },
    'bandcamp':             { icon: 'bi-bandcamp',              color: '#1da0c3' },
    '7digital':             { icon: 'bi-7-circle-fill',         color: '#e4002b' },
    'hdtracks':             { icon: 'bi-soundwave',             color: '#333333' },
    'juno-download':        { icon: 'bi-cloud-arrow-down-fill', color: '#e4002b' },
    'emusic':               { icon: 'bi-download',              color: '#2c7be5' },
    // Social
    'tiktok':               { icon: 'bi-tiktok',               color: '#010101' },
    'facebook':             { icon: 'bi-facebook',             color: '#1877f2' },
    'snapchat':             { icon: 'bi-snapchat',             color: '#f7c300' },
    'instagram':            { icon: 'bi-instagram',            color: '#e1306c' },
    'x-twitter':            { icon: 'bi-twitter-x',           color: '#000000' },
    'twitch':               { icon: 'bi-twitch',               color: '#9146ff' },
    'kwai':                 { icon: 'bi-camera-reels-fill',    color: '#ff5c00' },
    'vk':                   { icon: 'bi-person-video3',        color: '#0077ff' },
    'likee':                { icon: 'bi-heart-fill',           color: '#ff2d55' },
    // Vídeo
    'youtube':              { icon: 'bi-youtube',              color: '#ff0000' },
    'vevo':                 { icon: 'bi-play-btn-fill',        color: '#e4002b' },
    'dailymotion':          { icon: 'bi-play-circle-fill',     color: '#003f8a' },
    'vimeo':                { icon: 'bi-vimeo',                color: '#1ab7ea' },
    // Fallback
    'default':              { icon: 'bi-shop',                 color: '#6c757d' },
};

// Helper: devolve o ícone+cor para um slug, com fallback
function getStoreIcon(slug) {
    return STORE_ICONS[slug] ?? STORE_ICONS['default'];
}

// Helper: renderiza um badge de loja (ícone + nome + link opcional)
function renderStoreBadge(store, size = '1.4rem', showLabel = false) {
    const si    = getStoreIcon(store.slug_store);
    const url   = store.store_release_url || '#';
    const title = store.name_store;
    const label = showLabel ? `<span style="font-size:.65rem;display:block;line-height:1;margin-top:2px">${title}</span>` : '';
    const live  = store.store_status === 'live';
    const opacity = live ? '1' : '0.4';
    const tooltip = live ? title : `${title} (pendente)`;

    return `<a href="${ url}" target="_blank" rel="noopener"
               title="${tooltip}"
               style="color:${si.color};font-size:${size};opacity:${opacity};text-decoration:none;display:inline-flex;flex-direction:column;align-items:center">
                <i class="bi ${si.icon}"></i>
                ${label}
            </a>`;
}

// ════════════════════════════════════════════════
// ESTADO
// ════════════════════════════════════════════════
const PER_PAGE = 12;
let currentPage = 1;
let filtered = [];

// ════════════════════════════════════════════════
// RENDER CARD
// ════════════════════════════════════════════════
function renderCard(alb) {
    const coverUrl  = cover_url(alb.img_cover);
    const coverHtml = coverUrl
        ? `<img src="${coverUrl}" class="release-cover" alt="${alb.title_album}" onclick="openModal(${alb.id_album})" loading="lazy"/>`
        : `<div class="release-cover-placeholder" onclick="openModal(${alb.id_album})"><i class="bi bi-disc text-white-50" style="font-size:3rem"></i></div>`;

    const artist      = alb.stage_name || alb.real_name || '—';
    const statusClass = STATUS_CLASS[alb.status_album] || 'draft';
    const statusLabel = STATUS_LABEL[alb.status_album] || alb.status_album;
    const trackCount  = alb.track_count || (alb.tracks ? alb.tracks.length : 0);

    // Botão Detalhes (sempre presente)
    let actionBtns = `<button class="btn btn-apply btn-sm flex-fill" onclick="openModal(${alb.id_album})"><i class="bi bi-eye me-1"></i>Detalhes</button>`;

    // Botões específicos por status
    if (alb.status_album === 'rejected') {
        actionBtns += `
        <button class="btn btn-sm flex-fill" style="background:#FF0089;color:#fff" onclick="openReview(${alb.id_album})">
            <i class="bi bi-arrow-repeat me-1"></i>Revisão
        </button>`;
    }

    if (['draft', 'pending', 'rejected', 'under_review'].includes(alb.status_album)) {
        actionBtns += `
        <a href="${BASE_URL}/dashboard/edit-release?id=${alb.id_album}" 
           class="btn btn-outline-secondary btn-sm" title="Editar">
            <i class="bi bi-pencil"></i>
        </a>`;
    }

    if (['approved', 'pending', 'rejected', 'draft'].includes(alb.status_album)) {
        const deleteType = alb.status_album === 'draft' ? 'draft' : 'published';
        actionBtns += `
        <button class="btn btn-outline-danger btn-sm" 
                onclick='openDeleteModal(${alb.id_album}, "${deleteType}", {
                    title: "${alb.title_album.replace(/'/g, "\\'")}",
                    artist: "${(alb.stage_name || alb.real_name || '—').replace(/'/g, "\\'")}",
                    meta: "${trackCount} faixas",
                    cover: "${cover_url(alb.img_cover) || ''}"
                })'
                title="Eliminar lançamento">
            <i class="bi bi-trash"></i>
        </button>`;
    } else if (alb.status_album === 'deleting') {
        actionBtns = `
        <button class="btn btn-warning btn-sm flex-fill" onclick="openDeleteStatusModal(${alb.id_album})">
            <i class="bi bi-hourglass-split me-1"></i>Pedido pendente
        </button>`;
    }

    return `
    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
      <div class="release-card">
        <span class="status-badge status-${statusClass}">${statusLabel}</span>
        ${coverHtml}
        <div class="release-body">
          <p class="release-title" title="${alb.title_album}">${alb.title_album}</p>
          <p class="release-artist">${artist}</p>
          <p class="release-meta"><i class="bi bi-music-note me-1"></i>${trackCount} faixa${trackCount !== 1 ? 's' : ''} &nbsp;·&nbsp; ${TYPE_LABEL[alb.type_album] || alb.type_album || '—'}</p>
        </div>
        <div class="release-actions">${actionBtns}</div>
      </div>
    </div>`;
}

// ════════════════════════════════════════════════
// RENDER GRID + PAGINAÇÃO
// ════════════════════════════════════════════════
function renderGrid() {
    const grid  = document.getElementById('releases-grid');
    const start = (currentPage - 1) * PER_PAGE;
    const page  = filtered.slice(start, start + PER_PAGE);

    if (filtered.length === 0) {
        grid.innerHTML = `
        <div class="col-12">
          <div class="empty-state">
            <i class="bi bi-disc"></i>
            <h5 class="text-muted">Nenhum lançamento encontrado</h5>
            <p class="text-reset small">Altera os filtros ou cria um novo lançamento.</p>
            <a href="${BASE_URL}/dashboard/creat-release" class="btn btn-sm mt-2" style="background:#FF0089;color:#fff">
              <i class="bi bi-plus me-1"></i>Novo lançamento
            </a>
          </div>
        </div>`;
        document.getElementById('pagination').innerHTML   = '';
        document.getElementById('result-count').textContent = '0 lançamentos';
        return;
    }

    grid.innerHTML = page.map(renderCard).join('');
    document.getElementById('result-count').textContent =
        `${filtered.length} lançamento${filtered.length !== 1 ? 's' : ''}`;

    // Paginação
    const totalPages = Math.ceil(filtered.length / PER_PAGE);
    const pag = document.getElementById('pagination');
    if (totalPages <= 1) { pag.innerHTML = ''; return; }

    let html = `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-p="${currentPage - 1}">‹</a></li>`;
    for (let i = 1; i <= totalPages; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" data-p="${i}">${i}</a></li>`;
    }
    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-p="${currentPage + 1}">›</a></li>`;
    pag.innerHTML = html;

    pag.querySelectorAll('.page-link').forEach(a => a.addEventListener('click', e => {
        e.preventDefault();
        const p = parseInt(a.dataset.p);
        if (p && p !== currentPage) {
            currentPage = p;
            renderGrid();
            window.scrollTo(0, 0);
        }
    }));
}

// ════════════════════════════════════════════════
// FILTROS
// ════════════════════════════════════════════════
function applyFilters() {
    const t  = document.getElementById('f-title').value.toLowerCase();
    const ar = document.getElementById('f-artist').value.toLowerCase();
    const u  = document.getElementById('f-upc').value.toLowerCase();
    const tp = document.getElementById('f-type').value;
    const st = document.getElementById('f-status').value;

    filtered = ALBUMS_DB.filter(a =>
        (!t  || a.title_album.toLowerCase().includes(t)) &&
        (!ar || (a.stage_name || a.real_name || '').toLowerCase().includes(ar)) &&
        (!u  || (a.upc || '').toLowerCase().includes(u)) &&
        (!tp || a.type_album.toLowerCase() === tp.toLowerCase()) &&
        (!st || a.status_album === st)
    );
    currentPage = 1;
    renderGrid();
}

['f-title', 'f-artist', 'f-upc'].forEach(id =>
    document.getElementById(id).addEventListener('input', applyFilters)
);
['f-type', 'f-status'].forEach(id =>
    document.getElementById(id).addEventListener('change', applyFilters)
);
document.getElementById('btn-clear-filters').addEventListener('click', () => {
    ['f-title', 'f-artist', 'f-upc'].forEach(id => document.getElementById(id).value = '');
    ['f-type', 'f-status'].forEach(id => document.getElementById(id).value = '');
    document.querySelectorAll('#status-tabs button').forEach(b => b.classList.remove('active'));
    document.querySelector('#status-tabs button[data-tab=""]').classList.add('active');
    applyFilters();
});

// Tabs de status rápidos (fora do DOMContentLoaded, já existe no init abaixo)
document.querySelectorAll('#status-tabs button').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#status-tabs button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('f-status').value = btn.dataset.tab;
        applyFilters();
    });
});

// ════════════════════════════════════════════════
// MODAL DETALHES
// ════════════════════════════════════════════════
function openModal(albumId) {
    const alb = ALBUMS_DB.find(a => a.id_album == albumId);
    if (!alb) return;

    const coverUrl = cover_url(alb.img_cover);
    document.getElementById('m-cover').src           = coverUrl || '../assets/img/placeholder-album.png';
    document.getElementById('m-title').textContent   = alb.title_album;
    document.getElementById('m-artist').textContent  = alb.stage_name || alb.real_name || '—';
    document.getElementById('m-type').textContent    = TYPE_LABEL[alb.type_album] || alb.type_album || '—';
    document.getElementById('m-genre').textContent   = alb.genre_main || '—';
    document.getElementById('m-subgenre').textContent = alb.genre_secondary || '—';
    document.getElementById('m-language').textContent = alb.language || '—';
    document.getElementById('m-date').textContent    = fmt_date(alb.release_date);
    document.getElementById('m-label').textContent   = alb.label_name || '—';
    document.getElementById('m-copyright-p').textContent = alb.copyright_p || '—';
    document.getElementById('m-copyright-c').textContent = alb.copyright_c || '—';
    document.getElementById('m-upc').textContent     = alb.upc || '—';
    document.getElementById('m-id').textContent      = alb.id_album || '—';
    document.getElementById('m-created').textContent = alb.creat_album
        ? new Date(alb.creat_album).toLocaleString('pt-PT') : '—';
    document.getElementById('m-updated').textContent = alb.modif_album
        ? new Date(alb.modif_album).toLocaleString('pt-PT') : '—';

    // Status badge
    const statusClass = STATUS_CLASS[alb.status_album] || 'draft';
    const statusLabel = STATUS_LABEL[alb.status_album] || alb.status_album;
    document.getElementById('m-status-badge').innerHTML =
        `<span class="status-badge status-${statusClass}" style="position:static">${statusLabel}</span>`;

    // Motivo de rejeição
    const rejWrap = document.getElementById('m-reject-wrap');
    if (alb.status_album === 'rejected' && alb.rejection_reason) {
        document.getElementById('m-reject-reason').textContent = alb.rejection_reason;
        rejWrap.classList.remove('d-none');
    } else {
        rejWrap.classList.add('d-none');
    }

    // Faixas — Acordeão
const tracks = alb.tracks || [];
document.getElementById('m-track-count').textContent = tracks.length;
let totalSeconds = 0;
const accordion = document.getElementById('tracksAccordion');

if (tracks.length === 0) {
    accordion.innerHTML = '<p class="text-reset small">Nenhuma faixa registada.</p>';
} else {
    accordion.innerHTML = tracks.map((t, i) => {
        // Calcular duração
        if (t.duration_track) {
            const parts = t.duration_track.split(':');
            if (parts.length === 2) {
                totalSeconds += parseInt(parts[0]) * 60 + parseInt(parts[1]);
            }
        }

        const trackId    = `track-${alb.id_album}-${i}`;
        const headerId   = `heading-${trackId}`;
        const collapseId = `collapse-${trackId}`;
        const trackNum   = t.position_track || (i + 1);
        const explicitBadge = t.explicit === 'YES' ? '<span class="badge bg-danger ms-2" style="font-size:.65rem">Explícito</span>' : '';

        return `
        <div class="accordion-item">
            <h2 class="accordion-header" id="${headerId}">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="false" aria-controls="${collapseId}">
                    <span class="track-num me-3">${trackNum}</span>
                    <span>${t.title_track}</span>${explicitBadge}
                    <span class="ms-auto me-3 text-muted small">${t.duration_track || ''}</span>
                </button>
            </h2>
            <div id="${collapseId}" class="accordion-collapse collapse" aria-labelledby="${headerId}" data-bs-parent="#tracksAccordion">
                <div class="accordion-body">
                    <div class="track-detail-row"><span class="track-detail-label">Título</span><span class="track-detail-value">${t.title_track}</span></div>
                    <div class="track-detail-row"><span class="track-detail-label">Artista Principal</span><span class="track-detail-value">${t.name_author || '—'}</span></div>
                    ${t.featuring_track ? `<div class="track-detail-row"><span class="track-detail-label">Feat.</span><span class="track-detail-value">${t.featuring_track}</span></div>` : ''}
                    <div class="track-detail-row"><span class="track-detail-label">Compositor</span><span class="track-detail-value">${t.name_composer || '—'}</span></div>
                    <div class="track-detail-row"><span class="track-detail-label">Produtor</span><span class="track-detail-value">${t.name_producer || '—'}</span></div>
                    <div class="track-detail-row"><span class="track-detail-label">ISRC</span><span class="track-detail-value">${t.isrc || '—'}</span></div>
                    <div class="track-detail-row"><span class="track-detail-label">Idioma</span><span class="track-detail-value">${t.language || '—'}</span></div>
                    <div class="track-detail-row"><span class="track-detail-label">Duração</span><span class="track-detail-value">${t.duration_track || '—'}</span></div>
                    <div class="track-detail-row"><span class="track-detail-label">Explícito</span><span class="track-detail-value">${t.explicit === 'YES' ? 'Sim' : 'Não'}</span></div>
                    ${t.audio_file ? `<div class="track-detail-row"><span class="track-detail-label">Ficheiro de áudio</span><span class="track-detail-value">${t.audio_file}</span></div>` : ''}
                </div>
            </div>
        </div>`;
    }).join('');
}

// Atualizar duração total
const hours   = Math.floor(totalSeconds / 3600);
const minutes = Math.floor((totalSeconds % 3600) / 60);
const secs    = totalSeconds % 60;
let durStr = '';
if (hours > 0) durStr += `${hours}h `;
if (minutes > 0 || hours > 0) durStr += `${minutes}min `;
durStr += `${secs}s`;
document.getElementById('m-total-duration').textContent = durStr;
document.getElementById('m-explicit-count').textContent = tracks.filter(t => t.explicit === 'YES').length;

   // ── Streaming links (aprovado) ────────────────────────────
const streamWrap = document.getElementById('m-streaming-wrap');
if (alb.status_album === 'approved' && alb.stores && alb.stores.length > 0) {
    streamWrap.classList.remove('d-none');
    document.getElementById('m-streaming-links').innerHTML =
        alb.stores.map(s => renderStoreBadge(s, '1.6rem', false)).join('');
} else {
    streamWrap.classList.add('d-none');
}

// ── Plataformas de distribuição ───────────────────────────
const platformsList = document.getElementById('m-platforms-list');
if (alb.stores && alb.stores.length > 0) {
    platformsList.innerHTML = alb.stores
        .map(s => renderStoreBadge(s, '1.2rem', true))
        .join('');
} else {
    platformsList.innerHTML = '<span class="text-muted small">Sem plataformas associadas</span>';
}

    // Botões do footer
    const btnEdit   = document.getElementById('m-btn-edit');
const btnReview = document.getElementById('m-btn-review');
btnEdit.classList.add('d-none');
btnReview.classList.add('d-none');

// Estados que permitem editar: draft, pending, rejected, approved
const editableStatuses = ['draft', 'pending', 'rejected', 'approved'];
if (editableStatuses.includes(alb.status_album)) {
    btnEdit.href = alb.status_album === 'draft' 
        ? `${BASE_URL}/dashboard/creat-release?draft=${alb.id_album}`
        : `${BASE_URL}/dashboard/edit-release?id=${alb.id_album}`;
    btnEdit.innerHTML = alb.status_album === 'draft'
        ? '<i class="bi bi-play-fill me-1"></i>Continuar rascunho'
        : '<i class="bi bi-pencil me-1"></i>Editar';
    btnEdit.classList.remove('d-none');
}

// Botão de revisão apenas para rejected
if (alb.status_album === 'rejected') {
    btnReview.classList.remove('d-none');
    btnReview.onclick = () => {
        bootstrap.Modal.getInstance(document.getElementById('albumModal')).hide();
        openReview(albumId);
    };
}

    // Modal

    new bootstrap.Modal(document.getElementById('albumModal')).show();
}

// ════════════════════════════════════════════════
// MODAL REVISÃO
// ════════════════════════════════════════════════
function openReview(albumId) {
    const alb = ALBUMS_DB.find(a => a.id_album == albumId);
    if (!alb) return;

    document.getElementById('rev-album-id').value         = albumId;
    document.getElementById('rev-album-title').textContent = alb.title_album + ' — ' + (alb.stage_name || alb.real_name || '');
    document.getElementById('rev-reason').value           = '';
    document.getElementById('rev-char-count').textContent = '0';
    document.getElementById('rev-feedback').classList.add('d-none');

    const rejDisplay = document.getElementById('rev-reject-display');
    if (alb.rejection_reason) {
        document.getElementById('rev-reject-text').textContent = alb.rejection_reason;
        rejDisplay.classList.remove('d-none');
    } else {
        rejDisplay.classList.add('d-none');
    }

    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

// Contador do textarea
document.getElementById('rev-reason').addEventListener('input', function () {
    document.getElementById('rev-char-count').textContent = this.value.length;
});

// Enviar solicitação de revisão
document.getElementById('rev-submit-btn').addEventListener('click', function () {
    const albumId = document.getElementById('rev-album-id').value;
    const reason  = document.getElementById('rev-reason').value.trim();
    const fb      = document.getElementById('rev-feedback');

    if (reason.length < 20) {
        fb.innerHTML = '<div class="alert alert-danger small py-2">A justificação deve ter pelo menos 20 caracteres.</div>';
        fb.classList.remove('d-none');
        return;
    }

    document.getElementById('rev-btn-text').classList.add('d-none');
    document.getElementById('rev-btn-load').classList.remove('d-none');
    this.disabled = true;

    fetch(BASE_URL + '/dashboard/release_process', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=request_review&id_album=${encodeURIComponent(albumId)}&reason=${encodeURIComponent(reason)}&csrf_token=${encodeURIComponent(CSRF)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            fb.innerHTML = '<div class="alert alert-success small py-2"><i class="bi bi-check-circle me-1"></i>' + data.message + '</div>';
            fb.classList.remove('d-none');
            document.getElementById('rev-submit-btn').style.display = 'none';
            toastr.success('Solicitação de revisão enviada!');
            setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide(), 2000);
        } else {
            fb.innerHTML = '<div class="alert alert-danger small py-2">' + (data.message || 'Erro. Tenta novamente.') + '</div>';
            fb.classList.remove('d-none');
        }
    })
    .catch(() => {
        fb.innerHTML = '<div class="alert alert-danger small py-2">Erro de ligação. Verifica a tua internet.</div>';
        fb.classList.remove('d-none');
    })
    .finally(() => {
        document.getElementById('rev-btn-text').classList.remove('d-none');
        document.getElementById('rev-btn-load').classList.add('d-none');
        document.getElementById('rev-submit-btn').disabled = false;
    });
});

// ════════════════════════════════════════════════
// MODAL RASCUNHOS (LOCAIS + BD)
// ════════════════════════════════════════════════
document.getElementById('btn-drafts').addEventListener('click', async () => {
    const modal = new bootstrap.Modal(document.getElementById('draftsModal'));
    modal.show();
    carregarRascunhosLocais();
    await carregarRascunhosBD();
});

function carregarRascunhosLocais() {
    const drafts    = getDrafts();
    const container = document.getElementById('local-drafts-list');

    if (drafts.length === 0) {
        container.innerHTML = `
        <div class="text-center py-4 text-muted">
            <i class="bi bi-pencil fs-1 d-block mb-3 opacity-25"></i>
            <p>Não tens rascunhos guardados neste dispositivo.</p>
            <p class="small">Os rascunhos locais são guardados quando começas a preencher um novo lançamento sem o terminar.</p>
        </div>`;
        return;
    }

    container.innerHTML = drafts.map(d => `
        <div class="draft-item d-flex align-items-center gap-3 p-3 border rounded mb-2">
            <i class="bi bi-file-earmark-music fs-3 text-muted flex-shrink-0"></i>
            <div class="flex-grow-1 overflow-hidden">
                <div class="fw-semibold text-truncate">${d.title || 'Sem título'}</div>
                <div class="text-reset small">${d.artist_names || '—'} &nbsp;·&nbsp; ${d.type || '—'}</div>
                <div class="text-reset small">Guardado: ${d.saved_at ? new Date(d.saved_at).toLocaleString('pt-PT') : '—'}</div>
                <span class="badge bg-secondary">Local</span>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <a href="${BASE_URL}/dashboard/creat-release?local_draft=${d.id}" 
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-play-fill"></i> Continuar
                </a>
                <button class="btn btn-sm btn-outline-danger" onclick="removerRascunhoLocal('${d.id}')">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

async function carregarRascunhosBD() {
    const container = document.getElementById('bd-drafts-list');

    try {
        const res  = await fetch(BASE_URL + '/dashboard/get_drafts', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `csrf_token=${encodeURIComponent(CSRF)}`
        });
        const data = await res.json();

        if (data.drafts && data.drafts.length > 0) {
            container.innerHTML = data.drafts.map(d => {
                const title  = (d.title_album || 'Sem título').replace(/'/g, "\\'");
                const artist = (d.name_author_band || '—').replace(/'/g, "\\'");
                const cover  = d.cover_url ? d.cover_url : '';

                return `
                <div class="draft-item d-flex align-items-center gap-3 p-3 border rounded mb-2">
                    <i class="bi bi-cloud fs-3 text-wasom flex-shrink-0"></i>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold text-truncate">${d.title_album || 'Sem título'}</div>
                        <div class="text-reset small">${d.name_author_band || '—'} &nbsp;·&nbsp; ${d.type_album || '—'}</div>
                        <div class="text-reset small">Criado: ${d.creat_album ? new Date(d.creat_album).toLocaleString('pt-PT') : '—'}</div>
                        <span class="badge bg-wasom">Na Nuvem</span>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <a href="${BASE_URL}/dashboard/creat-release?draft=${d.id_album}" 
                           class="btn btn-sm" style="background:#FF0089;color:#fff">
                            <i class="bi bi-play-fill me-1"></i>Continuar
                        </a>
                        <button class="btn btn-sm btn-outline-danger" onclick='openDeleteModal(${d.id_album}, "draft", {
                            title: "${title}",
                            artist: "${artist}",
                            meta: "${d.track_count || 0} faixas",
                            cover: "${cover}"
                        })'>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>`;
            }).join('');
        } else {
            container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="bi bi-cloud fs-1 d-block mb-3 opacity-25"></i>
                <p>Não tens rascunhos guardados na nuvem.</p>
                <p class="small">Os rascunhos na nuvem são lançamentos que começaste mas não finalizaste.</p>
            </div>`;
        }
    } catch (err) {
        container.innerHTML = `<div class="alert alert-danger small">Erro ao carregar rascunhos da nuvem.</div>`;
    }
}

// ════════════════════════════════════════════════
// MODAL DE ELIMINAÇÃO
// ════════════════════════════════════════════════
function openDeleteModal(itemId, itemType, itemData = {}) {
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));

    document.getElementById('deleteItemId').value   = itemId;
    document.getElementById('deleteItemType').value = itemType;

    document.getElementById('deleteAlbumTitle').textContent  = itemData.title  || 'Sem título';
    document.getElementById('deleteAlbumArtist').textContent = itemData.artist || '—';
    document.getElementById('deleteAlbumMeta').textContent   = itemData.meta   || '';

    if (itemData.cover) {
        document.getElementById('deleteAlbumCover').src          = itemData.cover;
        document.getElementById('deleteAlbumCover').style.display = 'block';
    } else {
        document.getElementById('deleteAlbumCover').src = '../assets/img/placeholder-album.png';
    }

    // Reset do modal
    document.getElementById('deletePassword').value         = '';
    document.getElementById('deleteConfirmCheck').checked   = false;
    document.getElementById('deleteConfirmBtn').disabled    = true;
    document.getElementById('passwordField').classList.add('d-none');
    document.getElementById('deleteFeedback').classList.add('d-none');

    let warningHtml = '';
    let subtitle    = '';

    switch (itemType) {
        case 'local':
            subtitle    = 'Rascunho local (neste dispositivo)';
            warningHtml = `
            <div class="d-flex gap-2">
                <i class="bi bi-pc-display fs-4 flex-shrink-0"></i>
                <div>
                    <strong>Rascunho local</strong>
                    <p class="mb-0 small">Este rascunho está apenas neste dispositivo. 
                    Ao eliminar, não poderás recuperá-lo.</p>
                </div>
            </div>`;
            document.getElementById('deleteConfirmLabel').innerHTML =
                'Compreendo que este rascunho local será eliminado permanentemente.';
            break;

        case 'draft':
            subtitle    = 'Rascunho na nuvem';
            warningHtml = `
            <div class="d-flex gap-2">
                <i class="bi bi-cloud-arrow-up fs-4 flex-shrink-0 text-warning"></i>
                <div>
                    <strong>Rascunho na nuvem</strong>
                    <p class="mb-0 small">Ao solicitares a eliminação, este rascunho será movido para a lixeira 
                    e eliminado permanentemente após <strong>72 horas</strong>.</p>
                </div>
            </div>`;
            document.getElementById('passwordField').classList.remove('d-none');
            document.getElementById('passwordHelp').textContent =
                'Confirma a tua senha para solicitar a eliminação.';
            document.getElementById('deleteConfirmLabel').innerHTML =
                'Compreendo que após 72 horas o rascunho será eliminado.';
            break;

        case 'published':
            subtitle    = 'Lançamento publicado';
            warningHtml = `
            <div class="d-flex gap-2">
                <i class="bi bi-globe2 fs-4 flex-shrink-0 text-danger"></i>
                <div>
                    <strong>Lançamento publicado</strong>
                    <p class="mb-0 small">Este lançamento está ativo nas plataformas. 
                    Ao solicitares a remoção, será removido após <strong>72 horas</strong>.</p>
                </div>
            </div>`;
            document.getElementById('passwordField').classList.remove('d-none');
            document.getElementById('passwordHelp').textContent =
                'Confirma a tua senha para solicitar a remoção.';
            document.getElementById('deleteConfirmLabel').innerHTML =
                'Compreendo que após 72 horas será removido.';
            break;
    }

    document.getElementById('deleteModalSubtitle').textContent  = subtitle;
    document.getElementById('deleteWarning').innerHTML          = warningHtml;
    document.getElementById('deleteWarning').className         =
        itemType === 'published' ? 'alert alert-danger' : 'alert alert-warning';

    modal.show();
}

// Ativar checkbox
document.getElementById('deleteConfirmCheck').addEventListener('change', function () {
    document.getElementById('deleteConfirmBtn').disabled = !this.checked;
});

// Ação de eliminar
document.getElementById('deleteConfirmBtn').addEventListener('click', async function () {
    const itemId   = document.getElementById('deleteItemId').value;
    const itemType = document.getElementById('deleteItemType').value;
    const password = document.getElementById('deletePassword').value;
    const feedback = document.getElementById('deleteFeedback');

    document.getElementById('deleteBtnText').classList.add('d-none');
    document.getElementById('deleteBtnLoad').classList.remove('d-none');
    this.disabled = true;

    try {
        if (itemType === 'local') {
            deleteDraft(itemId);
            toastr.success('Rascunho local eliminado!');
            bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();
            carregarRascunhosLocais();
            document.getElementById('deleteBtnText').classList.remove('d-none');
            document.getElementById('deleteBtnLoad').classList.add('d-none');
            this.disabled = false;
            return;
        }

        if (!password) {
            feedback.innerHTML = '<div class="alert alert-danger small py-2">A senha é obrigatória.</div>';
            feedback.classList.remove('d-none');
            document.getElementById('deleteBtnText').classList.remove('d-none');
            document.getElementById('deleteBtnLoad').classList.add('d-none');
            this.disabled = false;
            return;
        }

        const formData = new FormData();
        formData.append('action',   itemType === 'draft' ? 'delete_draft' : 'delete_release_request');
        formData.append('id_album', itemId);
        if (itemType !== 'draft') formData.append('password', password);
        formData.append('csrf_token', CSRF);

        const response = await fetch(BASE_URL + '/dashboard/release_process', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.ok) {
            bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();

            await Swal.fire({
                icon:  'success',
                title: 'Solicitação enviada!',
                html: `
                <p class="mb-2">${data.message}</p>
                <p class="mb-0 text-reset small">O álbum será eliminado em 72 horas, a menos que canceles o pedido.</p>`,
                confirmButtonColor: '#FF0089'
            });

            if (itemType === 'draft') {
                carregarRascunhosBD();
            } else {
                setTimeout(() => window.location.reload(), 1500);
            }
        } else {
            feedback.innerHTML = `<div class="alert alert-danger small py-2">${data.message}</div>`;
            feedback.classList.remove('d-none');
            document.getElementById('deleteBtnText').classList.remove('d-none');
            document.getElementById('deleteBtnLoad').classList.add('d-none');
            this.disabled = false;
        }

    } catch (err) {
        console.error('Erro detalhado:', err);
        feedback.innerHTML = '<div class="alert alert-danger small py-2">Erro de ligação. Tenta novamente.</div>';
        feedback.classList.remove('d-none');
        document.getElementById('deleteBtnText').classList.remove('d-none');
        document.getElementById('deleteBtnLoad').classList.add('d-none');
        this.disabled = false;
    }
});

// ════════════════════════════════════════════════
// ELIMINAR RASCUNHO LOCAL
// ════════════════════════════════════════════════
function removerRascunhoLocal(draftId) {
    const draft = getDrafts().find(d => d.id === draftId);
    if (!draft) { toastr.error('Rascunho não encontrado'); return; }

    openDeleteModal(draftId, 'local', {
        title:  draft.title || 'Sem título',
        artist: draft.artist_names || '—',
        meta:   'Rascunho local',
        cover:  ''
    });
}

// ════════════════════════════════════════════════
// MODAL DE STATUS DE ELIMINAÇÃO
// ════════════════════════════════════════════════
function openDeleteStatusModal(albumId) {
    const alb = ALBUMS_DB.find(a => a.id_album == albumId);
    if (!alb) return;

    document.getElementById('statusAlbumTitle').textContent  = alb.title_album || 'Sem título';
    document.getElementById('statusAlbumArtist').textContent = alb.stage_name || alb.real_name || '—';
    document.getElementById('statusAlbumMeta').textContent   = `${alb.track_count || 0} faixas`;

    const coverUrl = cover_url(alb.img_cover);
    document.getElementById('statusAlbumCover').src = coverUrl || '../assets/img/placeholder-album.png';

    document.getElementById('deleteStatusAlbumId').value = albumId;

    if (alb.delete_requested_at && alb.delete_expires_at) {
        const requested = new Date(alb.delete_requested_at);
        const expires   = new Date(alb.delete_expires_at);
        const now       = new Date();

        document.getElementById('deleteRequestedAt').textContent = requested.toLocaleString('pt-PT');
        document.getElementById('deleteExpiresAt').textContent   = expires.toLocaleString('pt-PT');

        const totalTime   = expires - requested;
        const elapsedTime = now - requested;
        const progress    = Math.min(100, Math.max(0, (elapsedTime / totalTime) * 100));

        document.getElementById('deleteProgressBar').style.width = progress + '%';

        const timeLeft = expires - now;
        if (timeLeft > 0) {
            const hoursLeft   = Math.floor(timeLeft / (1000 * 60 * 60));
            const minutesLeft = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            document.getElementById('deleteTimeRemaining').textContent =
                `${hoursLeft}h ${minutesLeft}min restantes`;
            document.getElementById('deleteTimeDetail').textContent =
                `A eliminação automática ocorrerá em ${hoursLeft}h ${minutesLeft}min`;
        } else {
            document.getElementById('deleteTimeRemaining').textContent = 'A processar...';
            document.getElementById('deleteTimeDetail').textContent =
                'O prazo expirou. A eliminação será processada em breve.';
        }
    } else {
        document.getElementById('deleteRequestedAt').textContent  = '—';
        document.getElementById('deleteExpiresAt').textContent    = '—';
        document.getElementById('deleteTimeRemaining').textContent = 'Em processamento';
        document.getElementById('deleteTimeDetail').textContent   = 'O pedido está a ser processado.';
        document.getElementById('deleteProgressBar').style.width  = '50%';
    }

    new bootstrap.Modal(document.getElementById('deleteStatusModal')).show();
}

// ════════════════════════════════════════════════
// CANCELAR PEDIDO DE ELIMINAÇÃO
// ════════════════════════════════════════════════
document.getElementById('cancelDeleteRequestBtn').addEventListener('click', async function () {
    const albumId  = document.getElementById('deleteStatusAlbumId').value;
    const feedback = document.getElementById('deleteStatusFeedback');

    const confirmResult = await Swal.fire({
        title:              'Cancelar pedido?',
        text:               'Tens a certeza que queres cancelar o pedido de eliminação? O álbum voltará ao estado anterior.',
        icon:               'question',
        showCancelButton:   true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor:  '#6c757d',
        confirmButtonText:  'Sim, cancelar pedido',
        cancelButtonText:   'Não, manter'
    });

    if (!confirmResult.isConfirmed) return;

    Swal.fire({
        title:            'A processar...',
        html:             'A cancelar pedido de eliminação',
        allowOutsideClick: false,
        didOpen:          () => { Swal.showLoading(); }
    });

    try {
        const formData = new FormData();
        formData.append('action',     'cancel_delete_request');
        formData.append('id_album',   albumId);
        formData.append('csrf_token', CSRF);

        const response = await fetch(BASE_URL + '/dashboard/release_process', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        Swal.close();

        if (data.ok) {
            await Swal.fire({
                icon:  'success',
                title: 'Pedido cancelado!',
                html: `
                <p class="mb-2">${data.message}</p>
                <p class="mb-0 text-reset small">O álbum voltou ao estado anterior e não será eliminado.</p>`,
                confirmButtonColor: '#FF0089'
            });
            bootstrap.Modal.getInstance(document.getElementById('deleteStatusModal')).hide();
            setTimeout(() => window.location.reload(), 1500);
        } else {
            await Swal.fire({
                icon:               'error',
                title:              'Erro ao cancelar',
                text:               data.message || 'Ocorreu um erro ao cancelar o pedido.',
                confirmButtonColor: '#FF0089'
            });
        }
    } catch (err) {
        console.error('Erro:', err);
        Swal.close();
        await Swal.fire({
            icon:               'error',
            title:              'Erro de ligação',
            text:               'Verifica a tua internet e tenta novamente.',
            confirmButtonColor: '#FF0089'
        });
    }
});

// ════════════════════════════════════════════════
// INIT — carregar dados, configurar filtros, badges
// ════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function () {
    updateDraftBadge();

    // Garantir tab "Todos" activa
    const tabs = document.querySelectorAll('#status-tabs button');
    if (tabs.length > 0) {
        tabs.forEach(b => b.classList.remove('active'));
        document.querySelector('#status-tabs button[data-tab=""]')?.classList.add('active');
    }

    // Garantir filtro de status vazio
    const statusFilter = document.getElementById('f-status');
    if (statusFilter) statusFilter.value = '';

    // Carregar todos os álbuns
    filtered = [...ALBUMS_DB];

    // Renderizar primeira página
    currentPage = 1;
    renderGrid();

    // Tabs de status
    document.querySelectorAll('#status-tabs button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#status-tabs button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('f-status').value = btn.dataset.tab;
            applyFilters();
        });
    });
});

// Toastr config
toastr.options = {
    positionClass: 'toast-top-right',
    timeOut:       4000,
    progressBar:   true,
    closeButton:   true
};

// ── Badge de notificações — polling 60s ──────────────────
(function () {
    function refreshBadge() {
        fetch('./ajax/notifications_api.php?action=count', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                const b = document.getElementById('navNotifBadge');
                if (!b) return;
                const n = parseInt(data.unread || 0);
                b.textContent    = n > 99 ? '99+' : n;
                b.style.display  = n > 0 ? '' : 'none';
            }).catch(function () {});
    }
    setTimeout(function () {
        refreshBadge();
        setInterval(refreshBadge, 60000);
    }, 30000);
})();