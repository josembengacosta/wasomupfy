// inbox.js - Caixa de Entrada
document.addEventListener('DOMContentLoaded', function() {
    // Dados simulados de emails
    const mockEmails = Array.from({length: 12}, (_, i) => ({
        id: i + 1,
        from: getRandomSender(),
        subject: getRandomSubject(),
        preview: getRandomPreview(),
        date: getRandomDate(),
        time: getRandomTime(),
        read: Math.random() > 0.5,
        starred: Math.random() > 0.7,
        important: Math.random() > 0.8,
        attachments: Math.random() > 0.6 ? Math.floor(Math.random() * 3) + 1 : 0,
        tags: getRandomTags(),
        avatarColor: getRandomColor(),
        avatarInitial: getAvatarInitial()
    }));

    function getRandomSender() {
        const senders = [
            'Suporte Wasom Upfy',
            'Financeiro Wasom Upfy',
            'Ana Silva (Suporte)',
            'Pedro Costa (Financeiro)',
            'Carlos Rocha (Gerente)',
            'Sistema de Notificações',
            'Equipe de Desenvolvimento',
            'Departamento Artístico'
        ];
        return senders[Math.floor(Math.random() * senders.length)];
    }

    function getRandomSubject() {
        const subjects = [
            'Seu ticket foi atualizado',
            'Pagamento processado com sucesso',
            'Nova atualização disponível',
            'Reunião agendada para amanhã',
            'Relatório mensal disponível',
            'Confirmação de cadastro',
            'Problema resolvido',
            'Solicitação de informações',
            'Feedback solicitado',
            'Convite para webinar'
        ];
        return subjects[Math.floor(Math.random() * subjects.length)];
    }

    function getRandomPreview() {
        const previews = [
            'Olá, gostaríamos de informar que sua solicitação foi processada...',
            'Seu pagamento do mês de Janeiro foi processado com sucesso...',
            'Temos o prazer de anunciar uma nova atualização do sistema...',
            'Confirmamos a reunião para amanhã às 14h no escritório principal...',
            'O relatório de desempenho do último mês está disponível para visualização...',
            'Seu cadastro foi confirmado com sucesso. Bem-vindo ao Wasom Upfy...',
            'O problema reportado no ticket TKT-1045 foi resolvido...',
            'Precisamos de algumas informações adicionais para processar sua solicitação...',
            'Gostaríamos de ouvir seu feedback sobre nossa última atualização...',
            'Você está convidado para nosso próximo webinar sobre distribuição digital...'
        ];
        return previews[Math.floor(Math.random() * previews.length)];
    }

    function getRandomDate() {
        const date = new Date();
        date.setDate(date.getDate() - Math.floor(Math.random() * 7));
        return date.toLocaleDateString('pt-BR');
    }

    function getRandomTime() {
        const hours = Math.floor(Math.random() * 12) + 8;
        const minutes = Math.floor(Math.random() * 60);
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
    }

    function getRandomTags() {
        const allTags = ['suporte', 'financeiro', 'artistas', 'urgente', 'sistema'];
        const count = Math.floor(Math.random() * 3) + 1;
        const shuffled = allTags.sort(() => 0.5 - Math.random());
        return shuffled.slice(0, count);
    }

    function getRandomColor() {
        const colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6f42c1'];
        return colors[Math.floor(Math.random() * colors.length)];
    }

    function getAvatarInitial() {
        const initials = ['SU', 'FI', 'AS', 'PC', 'CR', 'SN', 'ED', 'DA'];
        return initials[Math.floor(Math.random() * initials.length)];
    }

    // Elementos DOM
    const emailsList = document.getElementById('emailsList');
    const selectAll = document.getElementById('selectAll');

    // Inicializar
    function init() {
        renderEmails();
        setupEventListeners();
    }

    // Renderizar emails
    function renderEmails() {
        emailsList.innerHTML = mockEmails.map(email => `
            <div class="email-card mb-3 p-3 ${email.read ? '' : 'unread'} ${email.important ? 'important' : ''} ${email.starred ? 'starred' : ''}" 
                 onclick="viewEmail(${email.id})">
                <div class="d-flex align-items-start">
                    <div class="email-avatar me-3" style="background-color: ${email.avatarColor};">
                        ${email.avatarInitial}
                    </div>
                    
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <h6 class="mb-0 ${email.read ? '' : 'fw-bold'}">${email.from}</h6>
                                <small class="text-muted">${email.date} • ${email.time}</small>
                            </div>
                            
                            <div class="email-actions">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick="event.stopPropagation(); toggleStar(${email.id})">
                                        <i class="bi ${email.starred ? 'bi-star-fill text-warning' : 'bi-star'}"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick="event.stopPropagation(); toggleImportant(${email.id})">
                                        <i class="bi ${email.important ? 'bi-exclamation-circle-fill text-danger' : 'bi-exclamation-circle'}"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick="event.stopPropagation(); deleteEmail(${email.id})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <h6 class="mb-1 ${email.read ? '' : 'fw-bold'}">${email.subject}</h6>
                        <p class="email-preview mb-2">${email.preview}</p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="email-tags">
                                ${email.tags.map(tag => `
                                    <span class="email-tag">${tag}</span>
                                `).join('')}
                            </div>
                            
                            <div>
                                ${email.attachments > 0 ? `
                                    <small class="text-muted">
                                        <i class="bi bi-paperclip me-1"></i>
                                        ${email.attachments}
                                    </small>
                                ` : ''}
                                ${!email.read ? `
                                    <span class="badge bg-primary ms-2">Nova</span>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Ver email
    window.viewEmail = function(emailId) {
        const email = mockEmails.find(e => e.id === emailId);
        if (!email) return;
        
        // Marcar como lida
        email.read = true;
        renderEmails();
        
        // Em produção, abriria um modal ou nova página com o email completo
        alert(`Visualizando email de: ${email.from}\n\nAssunto: ${email.subject}\n\n${email.preview}\n\nData: ${email.date} ${email.time}`);
    };

    // Alternar estrela
    window.toggleStar = function(emailId) {
        const email = mockEmails.find(e => e.id === emailId);
        if (email) {
            email.starred = !email.starred;
            renderEmails();
            showNotification(email.starred ? 'Email marcado com estrela' : 'Estrela removida', 'success');
        }
    };

    // Alternar importante
    window.toggleImportant = function(emailId) {
        const email = mockEmails.find(e => e.id === emailId);
        if (email) {
            email.important = !email.important;
            renderEmails();
            showNotification(email.important ? 'Marcado como importante' : 'Removido dos importantes', 'success');
        }
    };

    // Excluir email
    window.deleteEmail = function(emailId) {
        if (confirm('Tem certeza que deseja mover este email para a lixeira?')) {
            const index = mockEmails.findIndex(e => e.id === emailId);
            if (index !== -1) {
                mockEmails.splice(index, 1);
                renderEmails();
                showNotification('Email movido para a lixeira', 'success');
            }
        }
    };

    // Configurar event listeners
    function setupEventListeners() {
        // Selecionar todos
        selectAll.addEventListener('change', function() {
            const checkboxes = emailsList.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Filtros
        document.getElementById('filterUnread').addEventListener('change', filterEmails);
        document.getElementById('filterStarred').addEventListener('change', filterEmails);
        document.getElementById('filterImportant').addEventListener('change', filterEmails);
        document.getElementById('filterAttachments').addEventListener('change', filterEmails);
    }

    // Filtrar emails
    function filterEmails() {
        // Em produção, implementaria a lógica de filtragem aqui
        showNotification('Filtros aplicados', 'info');
    }

    // Mostrar notificação
    function showNotification(message, type = 'info') {
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
            <strong>${type === 'success' ? '✓ Sucesso!' : '⚠ Atenção!'}</strong>
            <span class="ms-1">${message}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // Inicializar
    init();
});