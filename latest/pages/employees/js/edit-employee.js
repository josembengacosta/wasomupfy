// edit-employee.js - Página de Edição de Funcionários
document.addEventListener('DOMContentLoaded', function() {
    // Obter ID do funcionário da URL (simulado)
    const urlParams = new URLSearchParams(window.location.search);
    const employeeId = urlParams.get('id') || '1005';
    
    // Elementos DOM
    const employeeIdElement = document.getElementById('employeeId');
    const profileForm = document.getElementById('profileForm');
    const securityForm = document.getElementById('securityForm');
    const deleteEmployeeBtn = document.getElementById('deleteEmployeeBtn');
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const enable2FA = document.getElementById('enable2FA');
    const twoFASettings = document.getElementById('2faSettings');
    const save2FABtn = document.getElementById('save2FABtn');
    const savePermissionsBtn = document.getElementById('savePermissionsBtn');
    const passwordStrengthBar = document.getElementById('passwordStrengthBar');
    const passwordStrengthText = document.getElementById('passwordStrengthText');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const passwordMismatch = document.getElementById('passwordMismatch');
    
    // Dados do funcionário (simulados)
    let employeeData = {
        id: parseInt(employeeId),
        name: 'Maria Silva Santos',
        account: '@maria.silva',
        username: 'maria.silva',
        email: 'maria.silva@wasomupfy.com',
        phone: '+351 912 345 678',
        country: 'PT',
        city: 'Lisboa',
        status: 'active',
        roles: ['admin', 'financial'],
        plan: 'Enterprise',
        notes: 'Funcionária responsável pelo departamento financeiro. Acesso total ao sistema.',
        createdAt: '2022-03-15',
        avatar: 'https://i.pravatar.cc/150?img=32',
        twoFAEnabled: false
    };
    
    // Permissões do funcionário (simuladas)
    let permissions = {
        analytics: {
            view: true,
            reports: true,
            export: true,
            custom: false
        },
        music: {
            review: false,
            approve: false,
            reject: false,
            edit: false
        },
        users: {
            view: true,
            create: true,
            edit: true,
            delete: false
        },
        distribution: {
            view: true,
            releases: true,
            schedule: false,
            stores: false
        },
        financial: {
            payments: true,
            earnings: true,
            process: false,
            reports: false
        }
    };
    
    // Inicializar página
    function initPage() {
        // Atualizar ID do funcionário
        employeeIdElement.textContent = employeeData.id;
        
        // Carregar dados do funcionário
        loadEmployeeData();
        
        // Carregar permissões
        loadPermissions();
        
        // Configurar eventos
        setupEventListeners();
        
        // Inicializar força da senha
        updatePasswordStrength();
        
        // Configurar 2FA
        enable2FA.checked = employeeData.twoFAEnabled;
        twoFASettings.style.display = employeeData.twoFAEnabled ? 'block' : 'none';
    }
    
    // Carregar dados do funcionário nos formulários
    function loadEmployeeData() {
        // Atualizar informações no sidebar
        document.getElementById('employeeName').textContent = employeeData.name;
        document.getElementById('employeeEmail').textContent = employeeData.email;
        document.getElementById('employeePhone').textContent = employeeData.phone;
        document.getElementById('employeeLocation').textContent = `${employeeData.city}, ${translateCountry(employeeData.country)}`;
        document.getElementById('employeeSince').textContent = formatDate(employeeData.createdAt);
        document.getElementById('employeeStatus').textContent = translateStatus(employeeData.status);
        document.getElementById('employeeStatus').className = `status-badge status-${employeeData.status}`;
        
        // Atualizar avatar
        document.getElementById('profileAvatar').src = employeeData.avatar;
        
        // Atualizar funções
        const rolesContainer = document.querySelector('.d-flex.justify-content-center.mb-3');
        rolesContainer.innerHTML = employeeData.roles
            .map(role => `<span class="badge bg-primary badge-role me-2">${translateRole(role)}</span>`)
            .join('');
        
        // Preencher formulário de perfil
        document.getElementById('editName').value = employeeData.name;
        document.getElementById('editAccount').value = employeeData.account;
        document.getElementById('editUsername').value = employeeData.username;
        document.getElementById('editEmail').value = employeeData.email;
        document.getElementById('editPhone').value = employeeData.phone;
        document.getElementById('editCountry').value = employeeData.country;
        document.getElementById('editCity').value = employeeData.city;
        document.getElementById('editStatus').value = employeeData.status;
        document.getElementById('editPlan').value = employeeData.plan;
        document.getElementById('editNotes').value = employeeData.notes;
        
        // Selecionar funções
        const rolesSelect = document.getElementById('editRoles');
        Array.from(rolesSelect.options).forEach(option => {
            option.selected = employeeData.roles.includes(option.value);
        });
    }
    
    // Carregar permissões
    function loadPermissions() {
        // Análises
        document.getElementById('permissionAnalyticsView').checked = permissions.analytics.view;
        document.getElementById('permissionReportsGenerate').checked = permissions.analytics.reports;
        document.getElementById('permissionExportData').checked = permissions.analytics.export;
        document.getElementById('permissionCustomReports').checked = permissions.analytics.custom;
        
        // Músicas
        document.getElementById('permissionMusicReview').checked = permissions.music.review;
        document.getElementById('permissionMusicApprove').checked = permissions.music.approve;
        document.getElementById('permissionMusicReject').checked = permissions.music.reject;
        document.getElementById('permissionMusicEdit').checked = permissions.music.edit;
        
        // Usuários
        document.getElementById('permissionUsersView').checked = permissions.users.view;
        document.getElementById('permissionUsersCreate').checked = permissions.users.create;
        document.getElementById('permissionUsersEdit').checked = permissions.users.edit;
        document.getElementById('permissionUsersDelete').checked = permissions.users.delete;
        
        // Distribuição
        document.getElementById('permissionDistributionView').checked = permissions.distribution.view;
        document.getElementById('permissionReleasesManage').checked = permissions.distribution.releases;
        document.getElementById('permissionScheduleRelease').checked = permissions.distribution.schedule;
        document.getElementById('permissionStoreManage').checked = permissions.distribution.stores;
        
        // Financeiro
        document.getElementById('permissionPaymentsView').checked = permissions.financial.payments;
        document.getElementById('permissionEarningsView').checked = permissions.financial.earnings;
        document.getElementById('permissionProcessPayments').checked = permissions.financial.process;
        document.getElementById('permissionFinancialReports').checked = permissions.financial.reports;
    }
    
    // Configurar event listeners
    function setupEventListeners() {
        // Formulário de perfil
        profileForm.addEventListener('submit', handleProfileSubmit);
        
        // Formulário de segurança
        securityForm.addEventListener('submit', handleSecuritySubmit);
        
        // Botão de exclusão
        deleteEmployeeBtn.addEventListener('click', handleDeleteEmployee);
        
        // 2FA toggle
        enable2FA.addEventListener('change', function() {
            twoFASettings.style.display = this.checked ? 'block' : 'none';
        });
        
        // Salvar 2FA
        save2FABtn.addEventListener('click', save2FASettings);
        
        // Salvar permissões
        savePermissionsBtn.addEventListener('click', savePermissions);
        
        // Força da senha
        newPasswordInput.addEventListener('input', updatePasswordStrength);
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);
        
        // Confirmação de ação
        document.getElementById('confirmActionBtn').addEventListener('click', executeConfirmedAction);
    }
    
    // Manipular envio do formulário de perfil
    function handleProfileSubmit(e) {
        e.preventDefault();
        
        // Validar formulário
        if (!profileForm.checkValidity()) {
            profileForm.classList.add('was-validated');
            return;
        }
        
        // Coletar dados do formulário
        const formData = {
            name: document.getElementById('editName').value,
            account: document.getElementById('editAccount').value,
            username: document.getElementById('editUsername').value,
            email: document.getElementById('editEmail').value,
            phone: document.getElementById('editPhone').value,
            country: document.getElementById('editCountry').value,
            city: document.getElementById('editCity').value,
            status: document.getElementById('editStatus').value,
            roles: Array.from(document.getElementById('editRoles').selectedOptions).map(opt => opt.value),
            plan: document.getElementById('editPlan').value,
            notes: document.getElementById('editNotes').value
        };
        
        // Validar e-mail único (simulação)
        if (formData.email !== employeeData.email) {
            if (!confirmEmailUnique(formData.email)) {
                showNotification('Este e-mail já está em uso por outro funcionário.', 'error');
                return;
            }
        }
        
        // Atualizar dados do funcionário
        Object.assign(employeeData, formData);
        
        // Em produção, aqui seria uma chamada AJAX
        simulateAPICall('updateEmployee', employeeData)
            .then(response => {
                if (response.success) {
                    // Recarregar dados na interface
                    loadEmployeeData();
                    
                    // Mostrar notificação
                    showNotification('Perfil atualizado com sucesso!', 'success');
                    
                    // Registrar atividade
                    logActivity('Perfil atualizado', 'profile');
                } else {
                    showNotification('Erro ao atualizar perfil.', 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showNotification('Erro ao atualizar perfil.', 'error');
            });
    }
    
    // Manipular envio do formulário de segurança
    function handleSecuritySubmit(e) {
        e.preventDefault();
        
        // Validar senhas
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (newPassword && newPassword !== confirmPassword) {
            passwordMismatch.style.display = 'block';
            confirmPasswordInput.classList.add('is-invalid');
            return;
        }
        
        passwordMismatch.style.display = 'none';
        confirmPasswordInput.classList.remove('is-invalid');
        
        // Coletar dados
        const securityData = {
            newPassword: newPassword || null,
            forceLogout: document.getElementById('forceLogout').checked
        };
        
        // Validar força da senha
        if (newPassword && !validatePasswordStrength(newPassword)) {
            showNotification('A senha é muito fraca. Use pelo menos 8 caracteres com letras maiúsculas, minúsculas e números.', 'warning');
            return;
        }
        
        // Confirmar ação
        showConfirmModal(
            'Alterar Senha',
            'Tem certeza que deseja alterar a senha deste funcionário? O usuário será notificado por e-mail.',
            () => {
                // Em produção, aqui seria uma chamada AJAX
                simulateAPICall('updatePassword', {
                    employeeId: employeeData.id,
                    ...securityData
                })
                .then(response => {
                    if (response.success) {
                        // Limpar campos
                        newPasswordInput.value = '';
                        confirmPasswordInput.value = '';
                        
                        // Mostrar notificação
                        showNotification('Senha alterada com sucesso!', 'success');
                        
                        // Registrar atividade
                        logActivity('Senha alterada', 'security');
                    } else {
                        showNotification('Erro ao alterar senha.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showNotification('Erro ao alterar senha.', 'error');
                });
            }
        );
    }
    
    // Excluir funcionário
    function handleDeleteEmployee() {
        showConfirmModal(
            'Excluir Funcionário',
            `Tem certeza que deseja excluir o funcionário "${employeeData.name}"? Esta ação não pode ser desfeita.`,
            () => {
                // Em produção, aqui seria uma chamada AJAX
                simulateAPICall('deleteEmployee', { id: employeeData.id })
                    .then(response => {
                        if (response.success) {
                            showNotification('Funcionário excluído com sucesso!', 'success');
                            
                            // Redirecionar para lista de funcionários
                            setTimeout(() => {
                                window.location.href = 'all-employees.html';
                            }, 1500);
                        } else {
                            showNotification('Erro ao excluir funcionário.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        showNotification('Erro ao excluir funcionário.', 'error');
                    });
            }
        );
    }
    
    // Salvar configurações 2FA
    function save2FASettings() {
        const twoFAMethod = document.querySelector('input[name="2faMethod"]:checked').id;
        
        showConfirmModal(
            'Configurar Autenticação de Dois Fatores',
            employeeData.twoFAEnabled 
                ? 'Tem certeza que deseja atualizar as configurações de 2FA?'
                : 'Tem certeza que deseja ativar a autenticação de dois fatores para este funcionário?',
            () => {
                employeeData.twoFAEnabled = enable2FA.checked;
                
                // Em produção, aqui seria uma chamada AJAX
                simulateAPICall('update2FA', {
                    employeeId: employeeData.id,
                    enabled: enable2FA.checked,
                    method: twoFAMethod.replace('2fa', '')
                })
                .then(response => {
                    if (response.success) {
                        showNotification(
                            enable2FA.checked 
                                ? 'Autenticação de dois fatores configurada com sucesso!'
                                : 'Autenticação de dois fatores desativada.',
                            'success'
                        );
                        
                        // Registrar atividade
                        logActivity(
                            enable2FA.checked 
                                ? '2FA ativado' 
                                : '2FA desativado',
                            'security'
                        );
                    } else {
                        showNotification('Erro ao configurar 2FA.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showNotification('Erro ao configurar 2FA.', 'error');
                });
            }
        );
    }
    
    // Salvar permissões
    function savePermissions() {
        // Coletar permissões
        const updatedPermissions = {
            analytics: {
                view: document.getElementById('permissionAnalyticsView').checked,
                reports: document.getElementById('permissionReportsGenerate').checked,
                export: document.getElementById('permissionExportData').checked,
                custom: document.getElementById('permissionCustomReports').checked
            },
            music: {
                review: document.getElementById('permissionMusicReview').checked,
                approve: document.getElementById('permissionMusicApprove').checked,
                reject: document.getElementById('permissionMusicReject').checked,
                edit: document.getElementById('permissionMusicEdit').checked
            },
            users: {
                view: document.getElementById('permissionUsersView').checked,
                create: document.getElementById('permissionUsersCreate').checked,
                edit: document.getElementById('permissionUsersEdit').checked,
                delete: document.getElementById('permissionUsersDelete').checked
            },
            distribution: {
                view: document.getElementById('permissionDistributionView').checked,
                releases: document.getElementById('permissionReleasesManage').checked,
                schedule: document.getElementById('permissionScheduleRelease').checked,
                stores: document.getElementById('permissionStoreManage').checked
            },
            financial: {
                payments: document.getElementById('permissionPaymentsView').checked,
                earnings: document.getElementById('permissionEarningsView').checked,
                process: document.getElementById('permissionProcessPayments').checked,
                reports: document.getElementById('permissionFinancialReports').checked
            }
        };
        
        showConfirmModal(
            'Salvar Permissões',
            'Tem certeza que deseja atualizar as permissões deste funcionário?',
            () => {
                permissions = updatedPermissions;
                
                // Em produção, aqui seria uma chamada AJAX
                simulateAPICall('updatePermissions', {
                    employeeId: employeeData.id,
                    permissions: permissions
                })
                .then(response => {
                    if (response.success) {
                        showNotification('Permissões atualizadas com sucesso!', 'success');
                        
                        // Registrar atividade
                        logActivity('Permissões atualizadas', 'permissions');
                    } else {
                        showNotification('Erro ao atualizar permissões.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showNotification('Erro ao atualizar permissões.', 'error');
                });
            }
        );
    }
    
    // Funções auxiliares
    function showConfirmModal(title, message, confirmCallback) {
        document.getElementById('confirmModalTitle').textContent = title;
        document.getElementById('confirmModalBody').textContent = message;
        document.getElementById('confirmActionBtn').onclick = confirmCallback;
        confirmModal.show();
    }
    
    function showNotification(message, type = 'success') {
        // Criar elemento de notificação
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        notification.innerHTML = `
            <strong>${type === 'success' ? '✓ Sucesso!' : type === 'error' ? '✗ Erro!' : '⚠ Atenção!'}</strong>
            <span class="ms-1">${message}</span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        `;
        
        // Adicionar ao documento
        document.body.appendChild(notification);
        
        // Remover automaticamente após 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
    
    function generatePassword() {
        const length = 12;
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
        let password = "";
        
        // Garantir pelo menos um de cada tipo
        password += getRandomChar("abcdefghijklmnopqrstuvwxyz");
        password += getRandomChar("ABCDEFGHIJKLMNOPQRSTUVWXYZ");
        password += getRandomChar("0123456789");
        password += getRandomChar("!@#$%^&*");
        
        // Completar o resto
        for (let i = 4; i < length; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        
        // Embaralhar
        password = password.split('').sort(() => 0.5 - Math.random()).join('');
        
        // Atualizar campos
        newPasswordInput.value = password;
        confirmPasswordInput.value = password;
        
        // Atualizar força
        updatePasswordStrength();
        validatePasswordMatch();
        
        showNotification('Senha forte gerada automaticamente!', 'info');
    }
    
    function getRandomChar(charSet) {
        return charSet.charAt(Math.floor(Math.random() * charSet.length));
    }
    
    function togglePasswordVisibility(fieldId) {
        const field = document.getElementById(fieldId);
        const button = field.nextElementSibling.querySelector('i');
        
        if (field.type === 'password') {
            field.type = 'text';
            button.className = 'bi bi-eye-slash';
        } else {
            field.type = 'password';
            button.className = 'bi bi-eye';
        }
    }
    
    function updatePasswordStrength() {
        const password = newPasswordInput.value;
        const strength = calculatePasswordStrength(password);
        
        const colors = {
            0: '#dc3545',
            1: '#dc3545',
            2: '#fd7e14',
            3: '#ffc107',
            4: '#198754',
            5: '#198754'
        };
        
        const texts = [
            'Muito fraca',
            'Fraca',
            'Moderada',
            'Forte',
            'Muito forte',
            'Excelente'
        ];
        
        passwordStrengthBar.style.width = `${strength * 20}%`;
        passwordStrengthBar.style.backgroundColor = colors[strength];
        passwordStrengthText.textContent = `Força da senha: ${texts[strength]}`;
        passwordStrengthText.className = `text-muted d-block mt-1 ${strength < 2 ? 'text-danger' : strength < 4 ? 'text-warning' : 'text-success'}`;
    }
    
    function calculatePasswordStrength(password) {
        let strength = 0;
        
        if (password.length > 7) strength++;
        if (password.length > 11) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        
        return Math.min(strength, 5);
    }
    
    function validatePasswordStrength(password) {
        return password.length >= 8 &&
               /[a-z]/.test(password) &&
               /[A-Z]/.test(password) &&
               /[0-9]/.test(password);
    }
    
    function validatePasswordMatch() {
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (newPassword && confirmPassword && newPassword !== confirmPassword) {
            passwordMismatch.style.display = 'block';
            confirmPasswordInput.classList.add('is-invalid');
            return false;
        } else {
            passwordMismatch.style.display = 'none';
            confirmPasswordInput.classList.remove('is-invalid');
            return true;
        }
    }
    
    function confirmEmailUnique(email) {
        // Simulação - em produção, seria uma verificação no servidor
        const existingEmails = ['admin@wasomupfy.com', 'financeiro@wasomupfy.com'];
        return !existingEmails.includes(email);
    }
    
    function resetForm() {
        loadEmployeeData();
        showNotification('Formulário redefinido para os valores originais.', 'info');
    }
    
    function resetPermissions() {
        loadPermissions();
        showNotification('Permissões redefinidas para os valores originais.', 'info');
    }
    
    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profileAvatar').src = e.target.result;
                showNotification('Foto atualizada com sucesso!', 'success');
                
                // Em produção, aqui enviaria a imagem para o servidor
                logActivity('Foto de perfil atualizada', 'profile');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function executeConfirmedAction() {
        confirmModal.hide();
        // A ação real é definida no onclick do botão de confirmação
    }
    
    function logActivity(description, category) {
        // Em produção, seria uma chamada AJAX para registrar a atividade
        console.log(`Atividade: ${description} (${category})`);
    }
    
    function simulateAPICall(endpoint, data) {
        return new Promise((resolve) => {
            setTimeout(() => {
                console.log(`API Call: ${endpoint}`, data);
                resolve({ success: true, data: data });
            }, 500);
        });
    }
    
    function translateCountry(code) {
        const countries = {
            'AO': 'Angola',
            'PT': 'Portugal',
            'BR': 'Brasil',
            'MZ': 'Moçambique'
        };
        return countries[code] || code;
    }
    
    function translateRole(role) {
        const roles = {
            'admin': 'Administrador',
            'distributor': 'Distribuidor',
            'analyst': 'Analista',
            'financial': 'Financeiro'
        };
        return roles[role] || role;
    }
    
    function translateStatus(status) {
        const statuses = {
            'active': 'Ativo',
            'suspended': 'Suspenso',
            'review': 'Revisão'
        };
        return statuses[status] || status;
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }
    
    // Inicializar a página
    initPage();
    
    // Adicionar classe wasomupfy ao CSS global
    const style = document.createElement('style');
    style.textContent = `
        .btn-wasomupfy {
            background-color: #FF0089;
            border-color: #FF0089;
            color: white;
        }
        .btn-wasomupfy:hover {
            background-color: #e0007a;
            border-color: #e0007a;
            color: white;
        }
        .text-wasomupfy {
            color: #FF0089 !important;
        }
        .border-wasomupfy {
            border-color: #FF0089 !important;
        }
    `;
    document.head.appendChild(style);
});