// Função para renderizar a paginação
        function renderPagination(totalItems) {
            const totalPages = Math.ceil(totalItems / perPage);
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';

            // Botão "Anterior"
            pagination.insertAdjacentHTML('beforeend', `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">Anterior</a>
                </li>
            `);

            // Páginas numeradas
            for (let i = 1; i <= totalPages; i++) {
                pagination.insertAdjacentHTML('beforeend', `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
            }

            // Botão "Próximo"
            pagination.insertAdjacentHTML('beforeend', `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">Próximo</a>
                </li>
            `);

            // Adicionar eventos aos botões de paginação
            document.querySelectorAll('.page-link').forEach(link => {
                link.addEventListener('click', e => {
                    e.preventDefault();
                    const page = parseInt(e.target.getAttribute('data-page'));
                    if (page && !isNaN(page)) {
                        currentPage = page;
                        filterAlbums();
                    }
                });
            });
        }

        