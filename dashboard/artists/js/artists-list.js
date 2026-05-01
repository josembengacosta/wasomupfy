// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Lista de Artistas
// Arquivo: dashboard/artists/js/artists-list.js
// ══════════════════════════════════════════════
// Depende das constantes injectadas pelo PHP em artists-list.php:
//   ARTISTS, ALBUMS_MAP, PHOTO_BASE, COVER_BASE, BASE_URL

toastr.options = {
    progressBar:   true,
    closeButton:   true,
    positionClass: 'toast-top-right',
    timeOut:       3000
};

// ══════════════════════════════════════════════
// VIEW TOGGLE
// ══════════════════════════════════════════════
let currentView = 'grid';

function setView(v) {
    currentView = v;
    document.getElementById('view-grid').classList.toggle('d-none', v !== 'grid');
    document.getElementById('view-list').classList.toggle('d-none', v !== 'list');
    document.getElementById('btn-grid').classList.toggle('active', v === 'grid');
    document.getElementById('btn-list').classList.toggle('active', v === 'list');
    localStorage.setItem('wasom_artists_view', v);
}

const savedView = localStorage.getItem('wasom_artists_view');
if (savedView === 'list') setView('list');

// ══════════════════════════════════════════════
// SEARCH + FILTER
// ══════════════════════════════════════════════
let activeStatus = '';

function setPill(el) {
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    el.classList.add('active');
    activeStatus = el.dataset.status;
    applyFilters();
}

function applyFilters() {
    const q     = (document.getElementById('search-input')?.value || '').toLowerCase().trim();
    const items = document.querySelectorAll('.artist-item');
    let visible = 0;

    items.forEach(item => {
        const matchQ = !q || (item.dataset.name || '').includes(q) || (item.dataset.real || '').includes(q) || (item.dataset.genre || '').includes(q);
        const matchS = !activeStatus || item.dataset.status === activeStatus;
        const show   = matchQ && matchS;
        item.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const noG = document.getElementById('no-results');
    const noL = document.getElementById('no-results-list');
    if (noG) noG.style.display = visible === 0 ? 'block' : 'none';
    if (noL) noL.classList.toggle('d-none', visible > 0);
    document.getElementById('pill-count-all').textContent = visible;
}

// ══════════════════════════════════════════════
// DETAIL OFFCANVAS
// ══════════════════════════════════════════════
const detailOffcanvas = new bootstrap.Offcanvas(document.getElementById('artistDetail'), { scroll: true });

const STATUS_MAP   = { active: 'Activo', processing: 'Em análise', inactive: 'Inactivo', blocked: 'Bloqueado' };
const STATUS_BADGE = { active: 'sb-approved', processing: 'sb-processing', inactive: 'sb-draft', blocked: 'sb-rejected' };
const TYPE_MAP     = { single: 'Single', EP: 'EP', album: 'Álbum', mixtape: 'Mixtape' };

const ALBUM_STATUS_BADGE = { approved: 'sb-approved', pending: 'sb-pending', rejected: 'sb-rejected', draft: 'sb-draft', under_review: 'sb-under_review' };
const ALBUM_STATUS_LABEL = { approved: 'Aprovado', pending: 'Pendente', rejected: 'Rejeitado', draft: 'Rascunho', under_review: 'Em revisão' };

const SOCIAL_META = {
    spotify_url:   { icon: 'bi-spotify',   label: 'Spotify',    bg: '#1db954' },
    youtube_url:   { icon: 'bi-youtube',   label: 'YouTube',    bg: '#ff0000' },
    instagram_url: { icon: 'bi-instagram', label: 'Instagram',  bg: '#e1306c' },
    tiktok_url:    { icon: 'bi-tiktok',    label: 'TikTok',     bg: '#010101' },
    facebook_url:  { icon: 'bi-facebook',  label: 'Facebook',   bg: '#1877f2' },
    website_url:   { icon: 'bi-globe',     label: 'Site / Apple', bg: '#6c757d' },
};

function openDetail(id) {
    const artist = ARTISTS.find(a => a.id_artist == id);
    if (!artist) return;
    const albums = ALBUMS_MAP[id] || [];

    // Cover
    document.getElementById('det-cover').style.background = 'linear-gradient(135deg,#FF0089,#FF4D4D)';

    // Avatar
    const avatarWrap = document.getElementById('det-avatar-wrap');
    avatarWrap.innerHTML = artist.photo_artist
        ? `<img src="${PHOTO_BASE}${artist.photo_artist}" class="detail-avatar" alt="${artist.stage_name}"/>`
        : `<div class="detail-avatar-ph"><i class="bi bi-person"></i></div>`;

    // Name + badges
    document.getElementById('det-name').textContent = artist.stage_name;
    document.getElementById('det-real').textContent = artist.real_name || '';

    const sc = STATUS_BADGE[artist.status_artist] || 'sb-draft';
    let badges = '';
    if (artist.default_role)    badges += `<span class="badge me-1" style="background:rgba(13,110,253,.12);color:#0d6efd"><i class="bi bi-person-badge me-1"></i>${artist.default_role.replace('_', ' ')}</span>`;
    if (artist.genre_main)      badges += `<span class="badge me-1" style="background:rgba(255,0,137,.12);color:var(--wasom)">${artist.genre_main}</span>`;
    if (artist.genre_secondary) badges += `<span class="badge me-1 bg-secondary text-white">${artist.genre_secondary}</span>`;
    badges += `<span class="status-badge ${sc}">${STATUS_MAP[artist.status_artist] || '—'}</span>`;
    document.getElementById('det-badges').innerHTML = badges;

    // Meta
    let meta = '';
    if (artist.city || artist.country) meta += `<span><i class="bi bi-geo-alt-fill me-1" style="font-size:.65rem"></i>${[artist.city, artist.country].filter(Boolean).join(', ')}</span>`;
    if (artist.creat_artist) {
        const d = new Date(artist.creat_artist);
        meta += `<span><i class="bi bi-calendar3 me-1" style="font-size:.65rem"></i>Desde ${d.toLocaleDateString('pt-PT', { month: 'long', year: 'numeric' })}</span>`;
    }
    document.getElementById('det-meta').innerHTML = meta;

    // Bio
    const biowrap = document.getElementById('det-bio-wrap');
    document.getElementById('det-bio').textContent = artist.bio || '';
    biowrap.classList.toggle('d-none', !artist.bio);

    // Stats
    document.getElementById('det-stats').innerHTML = `
        <div class="col-4"><div class="detail-stat-card"><div class="num">${artist.total_albums}</div><div class="lbl">Total</div></div></div>
        <div class="col-4"><div class="detail-stat-card"><div class="num">${artist.albums_approved}</div><div class="lbl">Activos</div></div></div>
        <div class="col-4"><div class="detail-stat-card"><div class="num">${artist.albums_pending}</div><div class="lbl">Pendentes</div></div></div>`;

    // Socials
    const socialsWrap = document.getElementById('det-socials-wrap');
    const socialsEl   = document.getElementById('det-socials');
    let socialsHTML = '';
    for (const [key, m] of Object.entries(SOCIAL_META)) {
        if (artist[key]) {
            socialsHTML += `
            <a href="${artist[key]}" target="_blank" rel="noopener"
               class="d-flex align-items-center gap-2 text-decoration-none rounded-3 px-3 py-2"
               style="background:${m.bg}15;border:1px solid ${m.bg}40;color:inherit;font-size:.8rem;flex:1;min-width:130px">
                <span class="social-pill" style="background:${m.bg};width:28px;height:28px;font-size:.75rem;flex-shrink:0"><i class="bi ${m.icon}"></i></span>
                <div>
                    <div style="font-weight:700;font-size:.75rem">${m.label}</div>
                    <div class="text-muted text-truncate" style="font-size:.65rem;max-width:120px">${artist[key].replace('https://', '')}</div>
                </div>
                <i class="bi bi-box-arrow-up-right ms-auto text-muted" style="font-size:.65rem"></i>
            </a>`;
        }
    }
    if (socialsHTML) {
        socialsEl.innerHTML = `<div class="d-flex flex-wrap gap-2">${socialsHTML}</div>`;
        socialsWrap.classList.remove('d-none');
    } else {
        socialsWrap.classList.add('d-none');
    }

    // Albums
    const albumsEl = document.getElementById('det-albums');
    const seeAllEl = document.getElementById('det-see-all');
    seeAllEl.href  = `${BASE_URL}/releases?artist=${id}`;

    if (albums.length === 0) {
        albumsEl.innerHTML = `<p class="text-muted small">Nenhum lançamento ainda.</p>`;
    } else {
        let html = '';
        albums.slice(0, 6).forEach(alb => {
            const imgEl      = alb.img_cover ? `<img src="${COVER_BASE}${alb.img_cover}" class="album-cover-sm" alt="${alb.title_album}"/>` : `<div class="album-cover-sm-ph"><i class="bi bi-disc"></i></div>`;
            const sbClass    = ALBUM_STATUS_BADGE[alb.status_album] || 'sb-draft';
            const sbLabel    = ALBUM_STATUS_LABEL[alb.status_album] || alb.status_album;
            const releaseDate = alb.release_date ? new Date(alb.release_date).toLocaleDateString('pt-PT') : '—';
            html += `
            <div class="album-row">
                ${imgEl}
                <div style="flex:1;min-width:0">
                    <div class="fw-semibold text-truncate" style="font-size:.83rem">${alb.title_album}</div>
                    <div class="text-muted" style="font-size:.72rem">${TYPE_MAP[alb.type_album] || alb.type_album} · ${releaseDate}</div>
                </div>
                <span class="status-badge ${sbClass} ms-2">${sbLabel}</span>
            </div>`;
        });
        if (albums.length > 6) {
            const extra = albums.length - 6;
            html += `<div class="text-center mt-2"><a href="${seeAllEl.href}" style="font-size:.78rem;color:var(--wasom)">Ver mais ${extra} lançamento${extra > 1 ? 's' : ''} →</a></div>`;
        }
        albumsEl.innerHTML = html;
    }

    // Edit button
    document.getElementById('det-edit-btn').href = `add-artist?edit=${id}`;

    detailOffcanvas.show();
}