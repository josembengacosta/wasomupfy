 toastr.options = {
            progressBar: true,
            closeButton: true,
            positionClass: 'toast-top-right',
            timeOut: 3000
        };

        let draftId = null;
        let lastSavedState = null;

         const subgenres = {
            pop: ['pop_rock', 'synth_pop', 'indie_pop', 'electro_pop'],
            rock: ['classic_rock', 'alternative_rock', 'indie_rock', 'grunge'],
            hip_hop: ['trap', 'boom_bap', 'gangsta_rap', 'conscious_rap'],
            eletronica: ['house', 'techno', 'drum_and_bass', 'dubstep'],
            jazz: ['smooth_jazz', 'bebop', 'fusion', 'free_jazz'],
            classica: ['baroque', 'romantic', 'modern', 'minimalist']
        };
         document.getElementById('genre').addEventListener('change', function() {
            const genre = this.value;
            const subgenreSelect = document.getElementById('subgenre');
            subgenreSelect.innerHTML = '<option value="">Selecione um subgênero</option>';
            
            if (subgenres[genre]) {
                subgenres[genre].forEach(subgenre => {
                    const option = document.createElement('option');
                    option.value = subgenre;
                    option.textContent = subgenre.charAt(0).toUpperCase() + subgenre.slice(1).replace(/_/g, ' ');
                    subgenreSelect.appendChild(option);
                });
            }
        });

        // Preencher anos para copyright e phonogram
        function populateYears() {
            const currentYear = new Date().getFullYear();
            const startYear = 1900;
            const copyrightYearSelect = document.getElementById('copyright-year');
            const phonogramYearSelect = document.getElementById('phonogram-year');

            for (let year = currentYear + 1; year >= startYear; year--) {
                const option1 = document.createElement('option');
                option1.value = year;
                option1.textContent = year;
                copyrightYearSelect.appendChild(option1);

                const option2 = document.createElement('option');
                option2.value = year;
                option2.textContent = year;
                phonogramYearSelect.appendChild(option2);
            }
        }

        // Chamar ao inicializar
        populateYears();
   document.getElementById('cover').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('cover-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        $(document).ready(function() {
            $('#artists').select2();
            populateYears();
            lastSavedState = getFormState();
        });

        function getFormState() {
            return {
                title: document.getElementById('title').value,
                version: document.getElementById('version').value,
                artists: JSON.stringify($('#artists').val() || []),
                language: document.getElementById('language').value,
                genre: document.getElementById('genre').value,
                subgenre: document.getElementById('subgenre').value,
                label: document.getElementById('label').value,
                copyright: `© ${document.getElementById('copyright-year').value} ${document.getElementById('copyright-holder').value}`.trim(),
                phonogram: `℗ ${document.getElementById('phonogram-year').value} ${document.getElementById('phonogram-holder').value}`.trim(),
                track_count: document.getElementById('track_count').value,
                cover: document.getElementById('cover').files[0] ? document.getElementById('cover').files[0].name : ''
            };
        }

        function hasFormChanged() {
            if (!lastSavedState) return true;
            const currentState = getFormState();
            return Object.keys(currentState).some(key => {
                if (key === 'cover') return false;
                return currentState[key] !== lastSavedState[key];
            });
        }

        async function saveDraft(redirect = false) {
            if (!hasFormChanged() && !redirect) return;

            const formData = new FormData();
            formData.append('draft_id', draftId || '');
            formData.append('title', document.getElementById('title').value);
            formData.append('version', document.getElementById('version').value);
            formData.append('artists', JSON.stringify($('#artists').val() || []));
            formData.append('language', document.getElementById('language').value);
            formData.append('genre', document.getElementById('genre').value);
            formData.append('subgenre', document.getElementById('subgenre').value);
            formData.append('label', document.getElementById('label').value);
            formData.append('copyright', `© ${document.getElementById('copyright-year').value} ${document.getElementById('copyright-holder').value}`.trim());
            formData.append('phonogram', `℗ ${document.getElementById('phonogram-year').value} ${document.getElementById('phonogram-holder').value}`.trim());
            formData.append('track_count', document.getElementById('track_count').value);
            const coverFile = document.getElementById('cover').files[0];
            if (coverFile) formData.append('cover', coverFile);

            const draftData = {
                title: document.getElementById('title').value,
                version: document.getElementById('version').value,
                artists: $('#artists').val() || [],
                language: document.getElementById('language').value,
                genre: document.getElementById('genre').value,
                subgenre: document.getElementById('subgenre').value,
                label: document.getElementById('label').value,
                copyright: `© ${document.getElementById('copyright-year').value} ${document.getElementById('copyright-holder').value}`.trim(),
                phonogram: `℗ ${document.getElementById('phonogram-year').value} ${document.getElementById('phonogram-holder').value}`.trim(),
                track_count: document.getElementById('track_count').value,
                cover: coverFile ? URL.createObjectURL(coverFile) : ''
            };
            localStorage.setItem('album_draft', JSON.stringify(draftData));

            try {
                console.log('Salvando rascunho...');
                const response = await fetch('api.php?action=save_draft', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                console.log('Resposta do save_draft:', result);
                if (result.success) {
                    draftId = result.id;
                    lastSavedState = getFormState();
                    toastr.success('Rascunho salvo com sucesso!', 'Sucesso');
                    if (redirect) {
                        window.location.href = `draft.html?id=${draftId}`;
                    }
                } else {
                    throw new Error(result.error || 'Erro ao salvar rascunho');
                }
            } catch (error) {
                console.error('Erro ao salvar rascunho:', error);
                toastr.error(`Erro ao salvar rascunho: ${error.message}`, 'Erro');
            }
        }

        document.getElementById('release-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const title = document.getElementById('title').value.trim();
            const genre = document.getElementById('genre').value;
            const subgenre = document.getElementById('subgenre').value;
            const trackCount = document.getElementById('track_count').value;
            const artists = $('#artists').val();
            if (!title || !genre || !subgenre || !trackCount || !artists || artists.length === 0) {
                toastr.error('Preencha título, gênero, subgênero, número de faixas e pelo menos um artista.', 'Erro');
                return;
            }

            const formData = new FormData();
            formData.append('draft_id', draftId || '');
            formData.append('title', title);
            formData.append('version', document.getElementById('version').value);
            formData.append('artists', JSON.stringify(artists));
            formData.append('language', document.getElementById('language').value || 'pt');
            formData.append('genre', genre);
            formData.append('subgenre', subgenre);
            formData.append('label', document.getElementById('label').value);
            formData.append('copyright', `© ${document.getElementById('copyright-year').value || ''} ${document.getElementById('copyright-holder').value || ''}`.trim());
            formData.append('phonogram', `℗ ${document.getElementById('phonogram-year').value || ''} ${document.getElementById('phonogram-holder').value || ''}`.trim());
            formData.append('track_count', trackCount);
            formData.append('status', 'pending');
            const coverFile = document.getElementById('cover').files[0];
            if (coverFile) formData.append('cover', coverFile);

            try {
                console.log('Enviando formulário para api.php?action=submit');
                const response = await fetch('api.php?action=submit', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                console.log('Resposta do backend:', result);
                if (result.success) {
                    console.log('Envio bem-sucedido, redirecionando...');
                    localStorage.removeItem('album_draft');
                    draftId = null;
                    lastSavedState = {};
                    window.location.href = `draft.html?id=${result.id}`;
                } else {
                    throw new Error(result.error || 'Erro desconhecido no backend');
                }
            } catch (error) {
                console.error('Erro ao enviar:', error);
                toastr.error(`Erro ao enviar lançamento: ${error.message}`, 'Erro');
            }
        });

        setInterval(() => saveDraft(), 5000);

        window.addEventListener('beforeunload', (e) => {
            if (hasFormChanged()) {
                e.preventDefault();
                e.returnValue = '';
            }
        });