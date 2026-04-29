// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Criar Lançamento
// Arquivo: dashboard/launch/js/creat-release.js
// ══════════════════════════════════════════════
// Depende das constantes injectadas pelo PHP em creat-release.php:
//   CSRF, BASE_URL, PLAN_SLUG, MAX_TRACKS, UI_MAX_TRACKS,
//   CAN_LABEL, USER_ARTISTS, STORES_DATA, DRAFT_KEY, DRAFT_FROM_DB

toastr.options = {
    progressBar: true,
    closeButton: true,
    positionClass: 'toast-top-right',
    timeOut: 3000
};

// ═══════════════════════════════════════════════
// CARREGAR RASCUNHO (localStorage OU Banco de Dados)
// ═══════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);

    // Verificar se é rascunho da BD
    if (urlParams.has('draft') && DRAFT_FROM_DB) {
        console.log('A carregar rascunho da base de dados:', DRAFT_FROM_DB);
        preencherDraftFromDB(DRAFT_FROM_DB);
        toastr.success('Rascunho carregado da base de dados!');
    }
    // Verificar se é rascunho do localStorage
    else if (urlParams.has('local_draft')) {
        const draftId = urlParams.get('local_draft');
        const drafts = JSON.parse(localStorage.getItem(DRAFT_KEY) || '[]');
        const draft = drafts.find(d => d.id === draftId);

        if (draft) {
            console.log('A carregar rascunho do localStorage:', draft);
            setTimeout(() => preencherRascunho(draft), 500);
            toastr.success('Rascunho local carregado!');
        }
    }
});

// Função para preencher rascunho da BD
function preencherDraftFromDB(draft) {
    const album  = draft.album;
    const tracks = draft.tracks || [];
    const stores = draft.stores || [];

    // STEP 1 - Informações básicas
    if (album.title_album) {
        const titleMatch = album.title_album.match(/^(.*?)(?:\s*\((.*?)\))?$/);
        document.getElementById('title').value = titleMatch[1].trim();
        if (titleMatch[2]) {
            const versionSelect = document.getElementById('version');
            const option = Array.from(versionSelect.options).find(opt => opt.value === titleMatch[2]);
            if (option) versionSelect.value = titleMatch[2];
        }
    }

    if (album.type_album)  document.getElementById('type_album').value = album.type_album;
    if (album.language)    document.getElementById('language').value   = album.language;

    // STEP 2 - Créditos
    if (album.genre_main) {
        document.getElementById('genre').value = album.genre_main;
        document.getElementById('genre').dispatchEvent(new Event('change'));
        setTimeout(() => {
            if (album.genre_secondary) {
                document.getElementById('subgenre').value = album.genre_secondary;
            }
        }, 200);
    }

    if (album.label_name)        document.getElementById('label_name').value      = album.label_name;
    if (album.copyright_c)       document.getElementById('copyright-year').value  = album.copyright_c.match(/\d{4}/)?.[0] || '';
    if (album.copyright_p)       document.getElementById('phonogram-year').value  = album.copyright_p.match(/\d{4}/)?.[0] || '';

    // STEP 3 - Faixas
    if (tracks.length > 0) {
        document.getElementById('tracks-container').innerHTML = '';
        trackCount = 0;

        function adicionarFaixaDBComDelay(index) {
            if (index >= tracks.length) {
                setTimeout(() => preencherSelect2Tracks(tracks), 100);
                return;
            }
            if (index > 0) addTrack();
            setTimeout(() => {
                const cards = document.querySelectorAll('.track-card');
                const card  = cards[index];
                if (!card) return;
                const track = tracks[index];
                if (track.title_track)     card.querySelector('.track-title').value          = track.title_track;
                if (track.language)        card.querySelector('.track-language').value        = track.language;
                if (track.recording_date)  card.querySelector('.track-recording-date').value = track.recording_date;
                if (track.explicit)        card.querySelector('.track-explicit').value        = track.explicit;
                if (track.isrc)            card.querySelector('.track-isrc').value            = track.isrc;
                adicionarFaixaDBComDelay(index + 1);
            }, 300);
        }
        adicionarFaixaDBComDelay(0);
    }

    // STEP 4 - Distribuição
    if (album.release_date) document.getElementById('release-date').value = album.release_date;

    if (stores.length > 0) {
        document.querySelectorAll('.store-card').forEach(c => {
            c.classList.remove('selected');
            c.querySelector('.store-checkbox').checked = false;
        });

        stores.forEach(storeId => {
            const card = document.querySelector(`.store-card[data-store-id="${storeId}"]`);
            if (card) {
                card.classList.add('selected');
                card.querySelector('.store-checkbox').checked = true;
            }
        });
        updateStoreCount();
    }

    if (album.img_cover) {
        toastr.info('A capa será mantida. Podes substituir se desejares.');
    }

    updateTrackUI();
}

// Função para preencher rascunho do localStorage
function preencherRascunho(draft) {
    // STEP 1 - Informações básicas
    if (draft.title)      document.getElementById('title').value      = draft.title;
    if (draft.version)    document.getElementById('version').value    = draft.version;
    if (draft.type_album) document.getElementById('type_album').value = draft.type_album;
    if (draft.language)   document.getElementById('language').value   = draft.language;

    // STEP 2 - Créditos
    if (draft.artists && draft.artists.length > 0) {
        $('#artists').val(draft.artists).trigger('change');
    }

    if (draft.genre_main) {
        document.getElementById('genre').value = draft.genre_main;
        document.getElementById('genre').dispatchEvent(new Event('change'));
        setTimeout(() => {
            if (draft.genre_secondary) {
                document.getElementById('subgenre').value = draft.genre_secondary;
            }
        }, 200);
    }

    if (draft.label_name)      document.getElementById('label_name').value      = draft.label_name;
    if (draft.copyright_year)  document.getElementById('copyright-year').value  = draft.copyright_year;
    if (draft.phonogram_year)  document.getElementById('phonogram-year').value  = draft.phonogram_year;

    // STEP 3 - Faixas
    const tracks = draft.tracks || [];
    if (tracks.length > 0) {
        document.getElementById('tracks-container').innerHTML = '';
        trackCount = 0;

        function adicionarFaixaComDelay(index) {
            if (index >= tracks.length) {
                setTimeout(() => preencherSelect2Tracks(tracks), 100);
                return;
            }
            if (index > 0) addTrack();
            setTimeout(() => {
                const cards = document.querySelectorAll('.track-card');
                const card  = cards[index];
                if (!card) return;

                const track = tracks[index];
                if (track.title_track)    card.querySelector('.track-title').value          = track.title_track;
                if (track.language)       card.querySelector('.track-language').value        = track.language;
                if (track.recording_date) card.querySelector('.track-recording-date').value = track.recording_date;
                if (track.explicit)       card.querySelector('.track-explicit').value        = track.explicit;
                if (track.isrc)           card.querySelector('.track-isrc').value            = track.isrc;

                if (track.title_track) {
                    const versionMatch = track.title_track.match(/\((.*?)\)$/);
                    if (versionMatch) {
                        const versionSelect = card.querySelector('.track-mix-version');
                        const option = Array.from(versionSelect.options).find(opt => opt.value === versionMatch[1]);
                        if (option) versionSelect.value = versionMatch[1];
                    }
                }
                adicionarFaixaComDelay(index + 1);
            }, 300);
        }
        adicionarFaixaComDelay(0);
    }

    // STEP 4 - Distribuição
    if (draft.release_date) document.getElementById('release-date').value = draft.release_date;

    const stores = draft.stores || [];
    if (stores.length > 0) {
        document.querySelectorAll('.store-card').forEach(c => {
            c.classList.remove('selected');
            c.querySelector('.store-checkbox').checked = false;
        });
        stores.forEach(storeId => {
            const card = document.querySelector(`.store-card[data-store-id="${storeId}"]`);
            if (card) {
                card.classList.add('selected');
                card.querySelector('.store-checkbox').checked = true;
            }
        });
        updateStoreCount();
    }

    updateTrackUI();
}

// Função auxiliar para preencher os Select2 das faixas
function preencherSelect2Tracks(tracks) {
    const cards = document.querySelectorAll('.track-card');
    cards.forEach((card, idx) => {
        const track = tracks[idx];
        if (!track) return;
        const setSelect2 = (selector, value) => {
            if (value) {
                const names  = value.split(',').map(s => s.trim()).filter(Boolean);
                const select = $(card).find(selector);
                names.forEach(name => {
                    if (!select.find(`option[value="${name}"]`).length) {
                        select.append(new Option(name, name, true, true));
                    }
                });
                select.val(names).trigger('change');
            }
        };
        setSelect2('.track-main-artists', track.name_author);
        setSelect2('.track-feat',          track.name_author_feat);
        setSelect2('.track-composers',     track.name_composer);
        setSelect2('.track-producers',     track.name_producer);
    });
}

// ═══════════════════════════════════════════════
// STEP NAVIGATION
// ═══════════════════════════════════════════════
let currentStep = 1;
const TOTAL_STEPS = 5;

function goStep(n) {
    if (n < 1 || n > TOTAL_STEPS) return;
    if (n > currentStep && !validateStep(currentStep)) return;

    document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + n).classList.add('active');

    document.querySelectorAll('.step-item').forEach((item, i) => {
        item.classList.remove('active', 'done');
        const step = i + 1;
        if (step < n)      item.classList.add('done');
        else if (step === n) item.classList.add('active');
    });

    document.querySelectorAll('.step-circle').forEach((c, i) => {
        if (i + 1 < n) c.innerHTML = '<i class="bi bi-check"></i>';
        else c.textContent = i + 1;
    });

    if (n === 5) buildReview();

    currentStep = n;
    window.scrollTo(0, 0);
}

function validateStep(step) {
    if (step === 1) {
        if (!coverBlob) {
            toastr.error('Adiciona uma imagem de capa.');
            return false;
        }
        if (!document.getElementById('title').value.trim()) {
            toastr.error('Preenche o título do lançamento.');
            return false;
        }
    }
    if (step === 2) {
        const artists = $('#artists').val();
        if (!artists || artists.length === 0) {
            toastr.error('Seleciona pelo menos um artista.');
            return false;
        }
        if (!document.getElementById('genre').value) {
            toastr.error('Seleciona o género principal.');
            return false;
        }
    }
    if (step === 3) {
        const cards = document.querySelectorAll('.track-card');
        let ok = true;
        cards.forEach((card, i) => {
            const title       = card.querySelector('.track-title').value.trim();
            const mainArtists = $(card).find('.track-main-artists').val();
            const composers   = $(card).find('.track-composers').val();
            const producers   = $(card).find('.track-producers').val();

            if (!title) {
                toastr.error(`Faixa ${i + 1}: preenche o título.`);
                ok = false;
            }
            if (!mainArtists || mainArtists.length === 0) {
                toastr.error(`Faixa ${i + 1}: seleciona pelo menos um artista principal.`);
                ok = false;
            }
            if (!composers || composers.length === 0) {
                toastr.error(`Faixa ${i + 1}: seleciona pelo menos um compositor.`);
                ok = false;
            }
            if (!producers || producers.length === 0) {
                toastr.error(`Faixa ${i + 1}: seleciona pelo menos um produtor.`);
                ok = false;
            }
            const audioFile = card.querySelector('.track-audio').files[0];
            if (!audioFile) {
                toastr.error(`Faixa ${i + 1}: seleciona o arquivo de áudio.`);
                ok = false;
            } else if (!audioFile.type.includes('wav') && !audioFile.type.includes('flac')) {
                toastr.error(`Faixa ${i + 1}: formato inválido. Use WAV ou FLAC.`);
                ok = false;
            } else if (audioFile.size > 200 * 1024 * 1024) {
                toastr.error(`Faixa ${i + 1}: arquivo muito grande (máx. 200MB).`);
                ok = false;
            }
        });
        return ok;
    }
    if (step === 4) {
        if (!document.getElementById('release-date').value) {
            toastr.error('Define a data de lançamento.');
            return false;
        }
        const sel = document.querySelectorAll('.store-card.selected').length;
        if (sel === 0) {
            toastr.error('Seleciona pelo menos uma plataforma.');
            return false;
        }
    }
    return true;
}

// ═══════════════════════════════════════════════
// COVER IMAGE + CROPPER
// ═══════════════════════════════════════════════
let coverBlob = null;
let cropper   = null;

document.getElementById('cover-input').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;

    const errEl = document.getElementById('cover-error');
    errEl.classList.add('d-none');

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        errEl.textContent = 'Formato inválido. Usa JPG, PNG ou WebP.';
        errEl.classList.remove('d-none');
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        errEl.textContent = 'Imagem demasiado grande (máx. 10 MB).';
        errEl.classList.remove('d-none');
        return;
    }

    const reader = new FileReader();
    reader.onload = (ev) => {
        const img = new Image();
        img.onload = () => {
            if (img.width < 1400 || img.height < 1400) {
                errEl.textContent = `Imagem muito pequena (${img.width}×${img.height}px). Mínimo 1400×1400 px.`;
                errEl.classList.remove('d-none');
                return;
            }
            if (img.width !== img.height) {
                openCropper(ev.target.result);
            } else {
                setCover(ev.target.result, file);
            }
        };
        img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
});

function setCover(dataUrl, file = null) {
    document.getElementById('cover-preview').src = dataUrl;
    document.getElementById('cover-drop').classList.add('has-image');
    document.getElementById('btn-crop').classList.remove('d-none');
    document.getElementById('btn-remove-cover').classList.remove('d-none');

    if (file) {
        coverBlob = file;
    } else {
        fetch(dataUrl).then(r => r.blob()).then(b => { coverBlob = b; });
    }
}

function openCropper(src) {
    document.getElementById('cropper-img').src = src;
    const modal = new bootstrap.Modal(document.getElementById('cropperModal'));
    modal.show();

    document.getElementById('cropperModal').addEventListener('shown.bs.modal', () => {
        if (cropper) { cropper.destroy(); cropper = null; }
        cropper = new Cropper(document.getElementById('cropper-img'), {
            aspectRatio: 1 / 1,
            viewMode: 2,
            background: false,
            minCropBoxWidth: 1400,
            minCropBoxHeight: 1400
        });
    }, { once: true });
}

document.getElementById('btn-crop-confirm').addEventListener('click', () => {
    if (!cropper) return;
    cropper.getCroppedCanvas({ width: 3000, height: 3000 }).toBlob((blob) => {
        const url = URL.createObjectURL(blob);
        coverBlob = blob;
        setCover(url);
        bootstrap.Modal.getInstance(document.getElementById('cropperModal')).hide();
    }, 'image/jpeg', 0.95);
});

document.getElementById('btn-crop').addEventListener('click', () => {
    const src = document.getElementById('cover-preview').src;
    if (src) openCropper(src);
});

document.getElementById('btn-remove-cover').addEventListener('click', () => {
    coverBlob = null;
    document.getElementById('cover-preview').src = '';
    document.getElementById('cover-drop').classList.remove('has-image');
    document.getElementById('btn-crop').classList.add('d-none');
    document.getElementById('btn-remove-cover').classList.add('d-none');
    document.getElementById('cover-input').value = '';
});

// Drag & drop
const coverDrop = document.getElementById('cover-drop');
coverDrop.addEventListener('dragover', e => {
    e.preventDefault();
    coverDrop.style.borderColor = 'var(--wasom)';
});
coverDrop.addEventListener('dragleave', () => {
    coverDrop.style.borderColor = '';
});
coverDrop.addEventListener('drop', e => {
    e.preventDefault();
    coverDrop.style.borderColor = '';
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('cover-input').files = dt.files;
        document.getElementById('cover-input').dispatchEvent(new Event('change'));
    }
});

// ═══════════════════════════════════════════════
// SELECT2 — ARTISTS
// ═══════════════════════════════════════════════
let artistNewName = '';

$(document).ready(function () {
    $('#artists').select2({
        theme: 'bootstrap-5',
        placeholder: 'Escreve ou seleciona artistas...',
        allowClear: true,
        tags: false,
        width: '100%',
        escapeMarkup: function (markup) { return markup; },
        language: {
            noResults: function () {
                return '<div style="padding:8px">Artista não encontrado. <a href="#" id="s2-create-link" class="fw-bold" style="color:var(--wasom)">Criar artista</a></div>';
            }
        }
    });

    $(document).on('click', '#s2-create-link', function (e) {
        e.preventDefault();
        artistNewName = $('.select2-search__field').val() || '';
        document.getElementById('artist-name').value = artistNewName;
        $('#artists').select2('close');
        new bootstrap.Modal(document.getElementById('createArtistModal')).show();
    });
});

document.getElementById('btn-create-artist').addEventListener('click', () => {
    artistNewName = '';
    ['artist-name','artist-real-name','artist-email','artist-genre','artist-role',
     'artist-genre-secondary','artist-country','artist-city','artist-bio',
     'artist-spotify','artist-apple','artist-youtube'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('artist-create-feedback').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('createArtistModal')).show();
});

document.getElementById('artist-photo-input').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
        document.getElementById('artist-photo-icon').classList.add('d-none');
        const img = document.getElementById('artist-photo-img');
        img.src = ev.target.result;
        img.classList.remove('d-none');
    };
    reader.readAsDataURL(file);
});

async function saveArtist() {
    const name  = document.getElementById('artist-name').value.trim();
    const email = document.getElementById('artist-email').value.trim();
    const fb    = document.getElementById('artist-create-feedback');

    if (!name) {
        fb.innerHTML = '<div class="alert alert-danger py-2 small">O nome artístico é obrigatório.</div>';
        fb.classList.remove('d-none');
        return;
    }
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        fb.innerHTML = '<div class="alert alert-danger py-2 small">O email do artista é obrigatório e deve ser válido.</div>';
        fb.classList.remove('d-none');
        return;
    }

    document.getElementById('save-artist-text').classList.add('d-none');
    document.getElementById('save-artist-load').classList.remove('d-none');
    document.getElementById('btn-save-artist').disabled = true;

    const fd = new FormData();
    fd.append('action',           'create_artist');
    fd.append('csrf_token',       CSRF);
    fd.append('stage_name',       name);
    fd.append('real_name',        document.getElementById('artist-real-name').value.trim());
    fd.append('artist_email',     email);
    fd.append('default_role',     document.getElementById('artist-role').value);
    fd.append('genre_main',       document.getElementById('artist-genre').value.trim());
    fd.append('genre_secondary',  document.getElementById('artist-genre-secondary').value.trim());
    fd.append('country',          document.getElementById('artist-country').value.trim());
    fd.append('city',             document.getElementById('artist-city').value.trim());
    fd.append('bio',              document.getElementById('artist-bio').value.trim());
    fd.append('spotify_url',      document.getElementById('artist-spotify').value.trim());
    fd.append('website_url',      document.getElementById('artist-apple').value.trim());
    fd.append('youtube_url',      document.getElementById('artist-youtube').value.trim());
    const photo = document.getElementById('artist-photo-input').files[0];
    if (photo) fd.append('photo', photo);

    try {
        const res  = await fetch(BASE_URL + '/creat_release_process', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) {
            const opt = new Option(name, data.id_artist, true, true);
            $('#artists').append(opt).trigger('change');
            toastr.success(`Artista "${name}" criado com sucesso!`);
            bootstrap.Modal.getInstance(document.getElementById('createArtistModal')).hide();
            USER_ARTISTS.push({ id_artist: data.id_artist, stage_name: name });
        } else {
            fb.innerHTML = `<div class="alert alert-danger py-2 small">${data.message || 'Erro ao criar artista.'}</div>`;
            fb.classList.remove('d-none');
        }
    } catch {
        fb.innerHTML = '<div class="alert alert-danger py-2 small">Erro de ligação. Tenta novamente.</div>';
        fb.classList.remove('d-none');
    } finally {
        document.getElementById('save-artist-text').classList.remove('d-none');
        document.getElementById('save-artist-load').classList.add('d-none');
        document.getElementById('btn-save-artist').disabled = false;
    }
}

// ═══════════════════════════════════════════════
// GENRE / SUBGENRE
// ═══════════════════════════════════════════════
const SUBGENRES = {
    pop:          ['Pop Rock','Synth-Pop','Indie Pop','Electro Pop','Dance Pop','K-Pop','Teen Pop'],
    rock:         ['Rock Clássico','Rock Alternativo','Indie Rock','Grunge','Punk Rock','Hard Rock','Post-Rock'],
    hip_hop:      ['Trap','Boom Bap','Gangsta Rap','Rap Consciente','Drill','Phonk','Lo-fi Hip-Hop','Afrorap'],
    r_and_b:      ['Soul','Neo-Soul','Contemporary R&B','Funk R&B','Quiet Storm'],
    afrobeats:    ['Afropop','Afrofusion','Highlife','Afrohouse','Afrobeats Drill'],
    semba:        ['Semba Tradicional','Semba Moderno','Semba Jazz'],
    kizomba:      ['Kizomba Clássica','Kizomba Fusion','Tarraxo','Ghetto Zouk'],
    kuduro:       ['Kuduro Tradicional','Kuduro Moderno','Afro Kuduro'],
    funaná:       ['Funaná Tradicional','Funaná Moderno'],
    eletronica:   ['House','Tech House','Deep House','Techno','Drum & Bass','Dubstep','Trance','Ambient','EDM','Afrotech'],
    jazz:         ['Smooth Jazz','Bebop','Fusion','Free Jazz','Jazz Vocal','Latin Jazz'],
    classica:     ['Barroco','Romântico','Moderno','Minimalismo','Ópera','Câmara','Sinfónico'],
    gospel:       ['Gospel Contemporâneo','Gospel Tradicional','CCM','Praise & Worship','Gospel Afro'],
    reggae:       ['Roots Reggae','Dancehall','Ska','Reggaeton','Lovers Rock'],
    samba:        ['Samba Tradicional','Pagode','Samba Rock','Samba Enredo'],
    funk:         ['Funk Carioca','Funk Proibidão','Funk Melody','Miami Bass'],
    pagode:       ['Pagode Romântico','Pagode Baiano'],
    forro:        ['Forró Pé-de-Serra','Forró Universitário','Xote','Baião'],
    folk:         ['Folk Tradicional','Singer-Songwriter','Indie Folk','Bluegrass','Celtic'],
    metal:        ['Heavy Metal','Death Metal','Black Metal','Power Metal','Metalcore','Nu-Metal'],
    alternativo:  ['Indie Rock','Post-Punk','Shoegaze','Dream Pop','Emo','Grunge'],
    country:      ['Country Clássico','Country Pop','Country Rock','Bluegrass'],
    blues:        ['Delta Blues','Chicago Blues','Electric Blues','Blues Rock'],
    latin:        ['Salsa','Merengue','Bachata','Cumbia','Bossa Nova','Flamenco','Tango'],
    amapiano:     ['Amapiano Log Drum','Amapiano Vocals','Street Amapiano'],
    dancehall:    ['Dancehall Roots','Modern Dancehall','Ragga'],
    instrumental: ['Jazz Instrumental','Clássico Instrumental','Lo-Fi','Ambient','Chillout'],
    spoken:       ['Spoken Word','Poesia','Slam Poetry','Audiolivro'],
    outros:       ['World Music','Fusion','Experimental'],
};

document.getElementById('genre').addEventListener('change', function () {
    const genre = this.value;
    const sub   = document.getElementById('subgenre');
    sub.innerHTML = '<option value="">Seleciona um subgénero</option>';
    sub.disabled  = !genre;
    if (genre && SUBGENRES[genre]) {
        SUBGENRES[genre].forEach(sg => {
            const opt = document.createElement('option');
            opt.value       = sg.toLowerCase().replace(/[^a-z0-9]/g, '_');
            opt.textContent = sg;
            sub.appendChild(opt);
        });
    }
});

// ═══════════════════════════════════════════════
// COPYRIGHT YEAR SELECT
// ═══════════════════════════════════════════════
function populateYears() {
    const cur = new Date().getFullYear();
    ['copyright-year', 'phonogram-year'].forEach(id => {
        const sel = document.getElementById(id);
        for (let y = cur + 1; y >= 1950; y--) {
            const opt = document.createElement('option');
            opt.value       = y;
            opt.textContent = y;
            if (y === cur) opt.selected = true;
            sel.appendChild(opt);
        }
    });
}
populateYears();

// ═══════════════════════════════════════════════
// TRACKS
// ═══════════════════════════════════════════════
let trackCount = 0;

function addTrack() {
    if (UI_MAX_TRACKS && trackCount >= UI_MAX_TRACKS) {
        toastr.warning(`O teu plano permite no máximo ${UI_MAX_TRACKS} faixas.`);
        return;
    }
    trackCount++;
    const template = document.getElementById('track-template');
    const clone    = template.content.cloneNode(true);
    const card     = clone.querySelector('.track-card');

    card.dataset.trackIndex = trackCount;
    card.querySelector('.track-num-label').textContent = trackCount;

    if (trackCount > 1) card.querySelector('.btn-remove-track').classList.remove('d-none');

    document.getElementById('tracks-container').appendChild(card);
    initTrackSelects(card);
    renumberTracks();
    updateTrackUI();
}

function removeTrack(btn) {
    if (trackCount <= 1) return;
    btn.closest('.track-card').remove();
    trackCount--;
    renumberTracks();
    updateTrackUI();
}

function initTrackSelects(card) {
    $(card).find('.artist-select').each(function () {
        $(this).select2({
            placeholder:   $(this).data('placeholder'),
            allowClear:    true,
            width:         '100%',
            data:          USER_ARTISTS.map(a => ({ id: a.stage_name, text: a.stage_name })),
            tags:          false,
            escapeMarkup:  function (m) { return m; }
        });
    });
}

function renumberTracks() {
    document.querySelectorAll('.track-card').forEach((card, i) => {
        card.dataset.trackIndex = i + 1;
        card.querySelector('.track-num-label').textContent = i + 1;
        card.querySelector('.btn-remove-track').classList.toggle('d-none', trackCount <= 1);
    });
}

function updateTrackUI() {
    document.getElementById('track-counter').textContent = `${trackCount} / ${UI_MAX_TRACKS}`;
    const btn = document.getElementById('btn-add-track');
    if (btn) btn.disabled = (UI_MAX_TRACKS && trackCount >= UI_MAX_TRACKS);
}

// Init primeira faixa
addTrack();

// ═══════════════════════════════════════════════
// STORES
// ═══════════════════════════════════════════════
function toggleStore(card) {
    card.classList.toggle('selected');
    card.querySelector('.store-checkbox').checked = card.classList.contains('selected');
    updateStoreCount();
}

function selectAllStores() {
    document.querySelectorAll('.store-card').forEach(c => {
        c.classList.add('selected');
        c.querySelector('.store-checkbox').checked = true;
    });
    updateStoreCount();
}

function deselectAllStores() {
    document.querySelectorAll('.store-card').forEach(c => {
        c.classList.remove('selected');
        c.querySelector('.store-checkbox').checked = false;
    });
    updateStoreCount();
}

function updateStoreCount() {
    const n = document.querySelectorAll('.store-card.selected').length;
    document.getElementById('stores-selected-count').textContent = n;
}

// ═══════════════════════════════════════════════
// MIN DATE (today + 2 days)
// ═══════════════════════════════════════════════
(function () {
    const d = new Date();
    d.setDate(d.getDate() + 2);
    const min = d.toISOString().split('T')[0];
    document.getElementById('release-date').min   = min;
    document.getElementById('release-date').value = min;
})();

// ═══════════════════════════════════════════════
// REVIEW — Build summary on step 5
// ═══════════════════════════════════════════════
function buildReview() {
    const title       = document.getElementById('title').value;
    const version     = document.getElementById('version').value;
    const type_alb    = document.getElementById('type_album').value;
    const genreEl     = document.getElementById('genre');
    const genreText   = genreEl.options[genreEl.selectedIndex]?.text || '—';
    const subEl       = document.getElementById('subgenre');
    const subText     = subEl.value ? subEl.options[subEl.selectedIndex]?.text : '';
    const dateVal     = document.getElementById('release-date').value;
    const storeCount  = document.querySelectorAll('.store-card.selected').length;
    const artistNames = $('#artists').select2('data').map(o => o.text).join(', ');
    const tracks      = document.querySelectorAll('.track-card');

    document.getElementById('rev-title').textContent   = title || '—';
    document.getElementById('rev-type').textContent    = `${type_alb}${version ? ' — ' + version : ''}`;
    document.getElementById('rev-artists').textContent = artistNames || '—';
    document.getElementById('rev-genre').textContent   = genreText + (subText ? ' › ' + subText : '');
    document.getElementById('rev-tracks').textContent  = `${tracks.length} faixa${tracks.length !== 1 ? 's' : ''}`;
    document.getElementById('rev-date').textContent    = dateVal
        ? new Date(dateVal + 'T00:00').toLocaleDateString('pt-PT') : '—';
    document.getElementById('rev-stores').textContent  = `${storeCount} plataforma${storeCount !== 1 ? 's' : ''}`;

    const prev    = document.getElementById('cover-preview').src;
    const revCover = document.getElementById('rev-cover');
    if (prev && prev !== window.location.href) revCover.src = prev;
    else revCover.style.display = 'none';
}

// ═══════════════════════════════════════════════
// SUBMIT
// ═══════════════════════════════════════════════
async function submitRelease() {
    if (!validateStep(4)) return;
    if (!document.getElementById('terms-check').checked) {
        toastr.error('Aceita os Termos e Políticas de Privacidade para continuar.');
        return;
    }

    const btn = document.getElementById('btn-distribute');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A enviar...';
    btn.disabled  = true;

    const tracks     = [];
    const trackCards = document.querySelectorAll('.track-card');

    for (let i = 0; i < trackCards.length; i++) {
        const card      = trackCards[i];
        const audioFile = card.querySelector('.track-audio').files[0];

        if (!audioFile) {
            toastr.error(`Faixa ${i + 1}: seleciona o arquivo de áudio.`);
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Distribuir';
            btn.disabled  = false;
            return;
        }

        const validTypes = ['audio/wav', 'audio/x-wav', 'audio/flac', 'audio/x-flac'];
        const fileName   = audioFile.name.toLowerCase();
        if (!validTypes.includes(audioFile.type) && !fileName.endsWith('.wav') && !fileName.endsWith('.flac')) {
            toastr.error(`Faixa ${i + 1}: formato inválido. Use WAV ou FLAC.`);
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Distribuir';
            btn.disabled  = false;
            return;
        }
        if (audioFile.size > 200 * 1024 * 1024) {
            toastr.error(`Faixa ${i + 1}: arquivo muito grande (máx. 200MB).`);
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Distribuir';
            btn.disabled  = false;
            return;
        }

        tracks.push({
            track_number:    i + 1,
            title_track:     card.querySelector('.track-title').value.trim(),
            mix_version:     card.querySelector('.track-mix-version').value,
            name_author:     $(card).find('.track-main-artists').val().join(', '),
            name_author_feat: $(card).find('.track-feat').val().join(', '),
            name_composer:   $(card).find('.track-composers').val().join(', '),
            name_producer:   $(card).find('.track-producers').val().join(', '),
            language:        card.querySelector('.track-language').value,
            recording_date:  card.querySelector('.track-recording-date').value,
            explicit:        card.querySelector('.track-explicit').value,
            isrc:            card.querySelector('.track-isrc').value.trim().toUpperCase()
        });
    }

    const stores = [];
    document.querySelectorAll('.store-card.selected').forEach(c => stores.push(c.dataset.storeId));

    const copyrightYear = document.getElementById('copyright-year').value;
    const phonogramYear = document.getElementById('phonogram-year').value;

    const fd = new FormData();
    fd.append('action',          'create_release');
    fd.append('csrf_token',      CSRF);
    fd.append('title_album',     document.getElementById('title').value.trim());
    fd.append('version_album',   document.getElementById('version').value);
    fd.append('type_album',      document.getElementById('type_album').value);
    fd.append('language',        document.getElementById('language').value);
    fd.append('artists',         JSON.stringify($('#artists').val()));
    fd.append('genre_main',      document.getElementById('genre').value);
    fd.append('genre_secondary', document.getElementById('subgenre').value);
    fd.append('label_name',      CAN_LABEL ? document.getElementById('label_name').value.trim() : '102022 WU Records');
    fd.append('copyright_c',     `© ${copyrightYear}  - 102022 WU Records`);
    fd.append('copyright_p',     `℗ ${phonogramYear}  - 102022 WU Records`);
    fd.append('release_date',    document.getElementById('release-date').value);
    fd.append('tracks',          JSON.stringify(tracks));
    fd.append('stores',          JSON.stringify(stores));
    fd.append('audio_count',     tracks.length);

    if (coverBlob) fd.append('cover', coverBlob, 'cover.jpg');

    trackCards.forEach((card, index) => {
        const audioFile = card.querySelector('.track-audio').files[0];
        if (audioFile) {
            const safeFileName = `track_${index + 1}_${audioFile.name.replace(/[^a-zA-Z0-9.]/g, '_')}`;
            fd.append(`audio_${index + 1}`, audioFile, safeFileName);
        }
    });

    try {
        const res = await fetch(BASE_URL + '/creat_release_process', { method: 'POST', body: fd });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data = await res.json();
        if (data.ok) {
            await Swal.fire({
                icon: 'success',
                iconColor: '#FF0089',
                title: '<i class="bi bi-music-note-beamed me-2"></i>Lançamento enviado!',
                html: `<p class="mb-2">O teu lançamento <strong>${document.getElementById('title').value}</strong> foi submetido com sucesso!</p>
                    <p class="mb-0 text-muted small">
                        <i class="bi bi-clock me-1"></i>
                        A nossa equipa irá rever o teu lançamento em até 48h.
                        Receberás uma notificação quando for aprovado.
                    </p>`,
                confirmButtonText:  'Ver Lançamentos',
                confirmButtonColor: '#FF0089',
                showCancelButton:   false,
                allowOutsideClick:  false
            });
            window.location.href = BASE_URL + '/releases';
        } else {
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Distribuir';
            btn.disabled  = false;
            Swal.fire({
                icon:               'error',
                title:              'Erro',
                text:               data.message || 'Erro ao submeter. Tenta novamente.',
                confirmButtonColor: '#FF0089'
            });
        }
    } catch (err) {
        console.error('Erro detalhado:', err);
        btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Distribuir';
        btn.disabled  = false;
        Swal.fire({
            icon:               'error',
            title:              'Erro de Ligação',
            text:               'Verifica a tua internet e tenta novamente. Detalhes: ' + err.message,
            confirmButtonColor: '#FF0089'
        });
    }
}

// ═══════════════════════════════════════════════
// AUDIO FILE HELPERS
// ═══════════════════════════════════════════════
function clearAudioFile(btn) {
    const card       = btn.closest('.track-card');
    const audioInput = card.querySelector('.track-audio');
    audioInput.value = '';
    btn.style.display = 'none';
    card.querySelector('.audio-filename').textContent = '';
    card.querySelector('.audio-size').textContent     = '';
    card.classList.remove('has-audio');
}

document.addEventListener('change', function (e) {
    if (e.target.classList.contains('track-audio')) {
        const card         = e.target.closest('.track-card');
        const clearBtn     = card.querySelector('.track-audio-clear');
        const filenameSpan = card.querySelector('.audio-filename');
        const sizeSpan     = card.querySelector('.audio-size');

        if (e.target.files[0]) {
            const file   = e.target.files[0];
            filenameSpan.textContent = file.name;
            const sizeMB = (file.size / (1024 * 1024)).toFixed(1);
            sizeSpan.textContent  = `(${sizeMB} MB)`;
            clearBtn.style.display = 'block';
            card.classList.add('has-audio');

            if (!file.type.includes('wav') && !file.type.includes('flac')) {
                showAudioError(card, 'Formato inválido. Use WAV ou FLAC.');
            } else {
                hideAudioError(card);
            }
        } else {
            filenameSpan.textContent  = '';
            sizeSpan.textContent      = '';
            clearBtn.style.display    = 'none';
            card.classList.remove('has-audio');
        }
    }
});

function showAudioError(card, message) {
    const errorDiv = card.querySelector('.audio-error');
    errorDiv.textContent = message;
    errorDiv.classList.remove('d-none');
}

function hideAudioError(card) {
    card.querySelector('.audio-error').classList.add('d-none');
}

// ═══════════════════════════════════════════════
// AUTO-DRAFT COMPLETO
// ═══════════════════════════════════════════════
function saveCompleteDraft() {
    const draft = {
        id:        'local_' + Date.now(),
        saved_at:  new Date().toISOString(),

        // STEP 1
        title:       document.getElementById('title').value,
        version:     document.getElementById('version').value,
        type_album:  document.getElementById('type_album').value,
        language:    document.getElementById('language').value,
        coverBlob:   coverBlob ? true : false,

        // STEP 2
        artists:         $('#artists').val(),
        artists_names:   $('#artists').select2('data').map(o => o.text).join(', '),
        genre_main:      document.getElementById('genre').value,
        genre_secondary: document.getElementById('subgenre').value,
        label_name:      document.getElementById('label_name').value,
        copyright_year:  document.getElementById('copyright-year').value,
        phonogram_year:  document.getElementById('phonogram-year').value,

        // STEP 3
        tracks: [],

        // STEP 4
        release_date:     document.getElementById('release-date').value,
        release_time:     document.getElementById('release-time').value,
        release_timezone: document.getElementById('release-timezone').value,
        stores:           Array.from(document.querySelectorAll('.store-card.selected')).map(c => c.dataset.storeId)
    };

    document.querySelectorAll('.track-card').forEach((card, index) => {
        draft.tracks.push({
            track_number:    index + 1,
            title_track:     card.querySelector('.track-title').value,
            mix_version:     card.querySelector('.track-mix-version').value,
            name_author:     card.querySelector('.track-main-artists').value,
            name_author_feat: card.querySelector('.track-feat').value,
            name_composer:   card.querySelector('.track-composers').value,
            name_producer:   card.querySelector('.track-producers').value,
            language:        card.querySelector('.track-language').value,
            recording_date:  card.querySelector('.track-recording-date').value,
            explicit:        card.querySelector('.track-explicit').value,
            isrc:            card.querySelector('.track-isrc').value
        });
    });

    if (draft.title || draft.tracks.length > 0) {
        let drafts = [];
        try { drafts = JSON.parse(localStorage.getItem(DRAFT_KEY) || '[]'); } catch {}
        const idx = drafts.findIndex(d => d.id && d.id.startsWith('local_'));
        if (idx >= 0) drafts[idx] = draft;
        else drafts.push(draft);
        localStorage.setItem(DRAFT_KEY, JSON.stringify(drafts));
    }
}

// Guardar a cada 30 segundos
setInterval(saveCompleteDraft, 30000);

// Guardar ao sair da página
window.addEventListener('beforeunload', function () {
    saveCompleteDraft();
});

// Warn before leaving with unsaved data
window.addEventListener('beforeunload', e => {
    if (document.getElementById('title').value.trim()) {
        e.preventDefault();
        e.returnValue = '';
    }
});