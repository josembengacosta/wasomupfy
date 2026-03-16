
        // Inicialização
        document.addEventListener('DOMContentLoaded', () => {
            filteredData = [...usersData];
            renderTable();
            updateResultsCount();
            setupEventListeners();
        });

        // ==================== RENDERIZAÇÃO ====================
        function renderTable() {
            const usersList = document.getElementById('users-list');
            usersList.innerHTML = '';
            
            const startIdx = (currentPage - 1) * itemsPerPage;
            const paginatedItems = filteredData.slice(startIdx, startIdx + itemsPerPage);
            
            paginatedItems.forEach(user => {
                const row = `
                    <tr>
                        <td>${user.id}</td>
                        <td>${user.account}</td>
                        <td><a href="mailto:${user.email}" class="email-link">${user.email}</a></td>
                        <td><a href="https://wa.me/${user.phone.replace(/[^0-9]/g, '')}" class="phone-link" target="_blank">${user.phone}</a></td>
                        <td>${getCountryName(user.country)}</td>
                        <td>${user.city}</td>
                        <td>${getPlanName(user.plan)}</td>
                        <td><span class="status-badge ${getStatusClass(user.status)}">${getStatusText(user.status)}</span></td>
                        <td>${formatDate(user.created_at)}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary edit-btn me-1" data-id="${user.id}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${user.id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                usersList.insertAdjacentHTML('beforeend', row);
            });
            
            renderPagination();
            setupActionButtons();
        }

        function renderPagination() {
            const pagination = document.getElementById('users-pagination');
            pagination.innerHTML = '';
            const totalPages = Math.ceil(filteredData.length / itemsPerPage);
            
            // Botão Anterior
            pagination.insertAdjacentHTML('beforeend', `
                <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="prev"><i class="bi bi-chevron-left"></i></a>
                </li>
            `);
            
            // Páginas
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                pagination.insertAdjacentHTML('beforeend', `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
            }
            
            // Botão Próximo
            pagination.insertAdjacentHTML('beforeend', `
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
            switch(status) {
                case 'active': return 'status-active';
                case 'suspended': return 'status-suspended';
                case 'review': return 'status-review';
                default: return 'bg-secondary';
            }
        }

        function getStatusText(status) {
            switch(status) {
                case 'active': return 'Ativo';
                case 'suspended': return 'Suspenso';
                case 'review': return 'Revisão';
                default: return status;
            }
        }

        function getPlanName(plan) {
            switch(plan) {
                case 'free': return 'Free';
                case 'premium': return 'Premium';
                case 'enterprise': return 'Enterprise';
                default: return plan;
            }
        }

        function getCountryName(code) {
            switch(code) {
                case 'AO': return 'Angola';
                case 'PT': return 'Portugal';
                case 'BR': return 'Brasil';
                default: return code;
            }
        }

        function formatDate(dateString) {
            const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
            return new Date(dateString).toLocaleDateString('pt-AO', options);
        }

        function updateResultsCount() {
            document.getElementById('user-results-count').textContent = `${filteredData.length} resultados`;
        }

        // ==================== FILTROS ====================
        function filterData() {
            const id = document.getElementById('user-id').value;
            const account = document.getElementById('user-account').value.toLowerCase();
            const email = document.getElementById('user-email').value.toLowerCase();
            const plan = document.getElementById('user-plan').value;
            const status = document.getElementById('user-status').value;
            const country = document.getElementById('user-country').value;
            
            filteredData = usersData.filter(user => {
                return (
                    (!id || user.id.toString().includes(id)) &&
                    (!account || user.account.toLowerCase().includes(account)) &&
                    (!email || user.email.toLowerCase().includes(email)) &&
                    (!plan || user.plan === plan) &&
                    (!status || user.status === status) &&
                    (!country || user.country === country)
                );
            });
            
            currentPage = 1;
            renderTable();
            updateResultsCount();
        }

        function clearFilters() {
            document.getElementById('user-id').value = '';
            document.getElementById('user-account').value = '';
            document.getElementById('user-email').value = '';
            document.getElementById('user-plan').value = '';
            document.getElementById('user-status').value = '';
            document.getElementById('user-country').value = '';
            
            filterData();
        }

        // ==================== MODAIS ====================
        function openEditModal(user) {
            currentEditingId = user.id;
            
            document.getElementById('edit-user-body').innerHTML = `
                <form id="edit-user-form">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Conta</label>
                            <input type="text" class="form-control" id="edit-account" value="${user.account}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="edit-email" value="${user.email}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="tel" class="form-control" id="edit-phone" value="${user.phone}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Plano</label>
                            <select class="form-select" id="edit-plan" required>
                                <option value="free" ${user.plan === 'free' ? 'selected' : ''}>Free</option>
                                <option value="premium" ${user.plan === 'premium' ? 'selected' : ''}>Premium</option>
                                <option value="enterprise" ${user.plan === 'enterprise' ? 'selected' : ''}>Enterprise</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">País</label>
                            <select class="form-select" id="edit-country" required>
                                <option value="AO" ${user.country === 'AO' ? 'selected' : ''}>Angola</option>
                                <option value="PT" ${user.country === 'PT' ? 'selected' : ''}>Portugal</option>
                                <option value="BR" ${user.country === 'BR' ? 'selected' : ''}>Brasil</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="edit-city" value="${user.city}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" id="edit-status" required>
                                <option value="active" ${user.status === 'active' ? 'selected' : ''}>Ativo</option>
                                <option value="suspended" ${user.status === 'suspended' ? 'selected' : ''}>Suspenso</option>
                                <option value="review" ${user.status === 'review' ? 'selected' : ''}>Revisão</option>
                            </select>
                        </div>
                    </div>
                </form>
            `;
            
            new bootstrap.Modal('#editUserModal').show();
        }

        function openDeleteModal(user) {
            currentEditingId = user.id;
            document.getElementById('delete-user-message').textContent = 
                `Tem certeza que deseja excluir o usuário "${user.account}" (ID: ${user.id})?`;
            new bootstrap.Modal('#deleteUserModal').show();
        }

        function saveUserChanges() {
            // Obter valores do formulário
            const account = document.getElementById('edit-account').value;
            const email = document.getElementById('edit-email').value;
            const phone = document.getElementById('edit-phone').value;
            const plan = document.getElementById('edit-plan').value;
            const country = document.getElementById('edit-country').value;
            const city = document.getElementById('edit-city').value;
            const status = document.getElementById('edit-status').value;
            
            // Atualizar dados (em uma aplicação real, seria uma chamada AJAX)
            const index = usersData.findIndex(u => u.id === currentEditingId);
            if (index !== -1) {
                usersData[index] = {
                    ...usersData[index],
                    account,
                    email,
                    phone,
                    plan,
                    country,
                    city,
                    status
                };
            }
            
            // Fechar modal e atualizar tabela
            bootstrap.Modal.getInstance('#editUserModal').hide();
            filterData();
            alert("Alterações salvas com sucesso!");
        }

        function confirmUserDelete() {
            const password = document.getElementById('delete-user-password').value;
            
            if (!password) {
                document.getElementById('delete-user-password').classList.add('is-invalid');
                return;
            }
            
            // Simular chamada AJAX
            setTimeout(() => {
                const index = usersData.findIndex(u => u.id === currentEditingId);
                if (index !== -1) {
                    usersData.splice(index, 1);
                }
                
                bootstrap.Modal.getInstance('#deleteUserModal').hide();
                document.getElementById('delete-user-password').value = '';
                document.getElementById('delete-user-password').classList.remove('is-invalid');
                filterData();
                alert("Usuário excluído com sucesso!");
            }, 500);
        }

        // ==================== EVENT LISTENERS ====================
        function setupEventListeners() {
            // Filtros
            document.getElementById('user-id').addEventListener('input', filterData);
            document.getElementById('user-account').addEventListener('input', filterData);
            document.getElementById('user-email').addEventListener('input', filterData);
            document.getElementById('user-plan').addEventListener('change', filterData);
            document.getElementById('user-status').addEventListener('change', filterData);
            document.getElementById('user-country').addEventListener('change', filterData);
            
            // Limpar filtros
            document.getElementById('clear-user-filters').addEventListener('click', clearFilters);
            
            // Salvar alterações
            document.getElementById('save-user-changes').addEventListener('click', saveUserChanges);
            
            // Confirmar exclusão
            document.getElementById('confirm-user-delete').addEventListener('click', confirmUserDelete);
        }

        function setupActionButtons() {
            // Botões de edição/exclusão dinâmicos
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const user = usersData.find(u => u.id === parseInt(btn.dataset.id));
                    if (user) openEditModal(user);
                });
            });
            
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const user = usersData.find(u => u.id === parseInt(btn.dataset.id));
                    if (user) openDeleteModal(user);
                });
            });
        }