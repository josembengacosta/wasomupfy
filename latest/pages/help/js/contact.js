// contact.js - Sistema de Suporte
document.addEventListener('DOMContentLoaded', function() {
    // Dados simulados de tickets
    const mockTickets = Array.from({length: 15}, (_, i) => ({
        id: `TKT-${1000 + i}`,
        subject: getRandomSubject(),
        description: getRandomDescription(),
        category: getRandomCategory(),
        priority: getRandomPriority(),
        status: getRandomStatus(),
        createdAt: getRandomDate(),
        updatedAt: getRandomUpdateDate(),
        lastResponse: getRandomLastResponse(),
        assignedTo: getRandomAgent(),
        replies: getRandomReplies(),
        attachments: Math.random() > 0.7 ? [{
            name: 'screenshot.png',
            size: '2.4 MB'
        }] : []
    }));

    function getRandomSubject() {
        const subjects = [
            'Problema com upload de música',
            'Dúvida sobre pagamentos',
            'Configuração de loja digital',
            'Erro no relatório de analytics',
            'Solicitação de novo recurso',
            'Problema de login',
            'Dúvida sobre distribuição',
            'Configuração de artista',
            'Problema com notificações',
            'Solicitação de integração'
        ];
        return subjects[Math.floor(Math.random() * subjects.length)];
    }

    function getRandomDescription() {
        const descriptions = [
            'Não consigo fazer upload de arquivos WAV maiores que 100MB.',
            'Os pagamentos do último mês não aparecem no sistema.',
            'Preciso configurar a integração com Spotify for Artists.',
            'O relatório de analytics está mostrando dados incorretos.',
            'Seria útil ter uma funcionalidade de agendamento automático.',
            'Estou com problemas para fazer login em minha conta.',
            'Como configurar a distribuição para Apple Music?',
            'Não consigo adicionar colaboradores a um artista.',
            'Não estou recebendo notificações por e-mail.',
            'É possível integrar com o sistema de contabilidade?'
        ];
        return descriptions[Math.floor(Math.random() * descriptions.length)];
    }

    function getRandomCategory() {
        const categories = ['technical', 'billing', 'account', 'feature', 'bug', 'other'];
        return categories[Math.floor(Math.random() * categories.length)];
    }

    function getRandomPriority() {
        const priorities = ['low', 'medium', 'high', 'urgent'];
        const weights = [0.3, 0.4, 0.2, 0.1];
        return weightedRandom(priorities, weights);
    }

    function getRandomStatus() {
        const statuses = ['open', 'pending', 'closed'];
        const weights = [0.4, 0.3, 0.3];
        return weightedRandom(statuses, weights);
    }

    function weightedRandom(items, weights) {
        const totalWeight = weights.reduce((sum, weight) => sum + weight, 0);
        let random = Math.random() * totalWeight;
        
        for (let i = 0; i < items.length; i++) {
            random -= weights[i];
            if (random <= 0) {
                return items[i];
            }
        }
        
        return items[items.length - 1];
    }

    function getRandomDate() {
        const date = new Date();
        date.setDate(date.getDate() - Math.floor(Math.random() * 30));
        return date.toISOString();
    }

    function getRandomUpdateDate() {
        const date = new Date();
        date.setDate(date.getDate() - Math.floor(Math.random() * 7));
        return date.toISOString();
    }

    function getRandomLastResponse() {
        const responses = [
            'Nossa equipe está analisando seu caso.',
            'Precisamos de mais informações para ajudá-lo.',
            'O problema foi identificado e será corrigido na próxima atualização.',
            'Sua solicitação foi encaminhada para o departamento responsável.',
            'A correção foi aplicada, por favor verifique.',
            'Aguardamos sua confirmação para prosseguir.'
        ];
        return responses[Math.floor(Math.random() * responses.length)];
    }

    function getRandomAgent() {
        const agents = [
            { name: 'Ana Silva', role: 'Suporte Técnico' },
            { name: 'Pedro Costa', role: 'Suporte Financeiro' },
            { name: 'Maria Santos', role: 'Suporte Artístico' },
            { name: 'Carlos Rocha', role: 'Gerente de Suporte' }
        ];
        return agents[Math.floor(Math.random() * agents.length)];
    }

    function getRandomReplies() {
        const count = Math.floor(Math.random() * 5);
        return Array.from({length: count}, (_, i) => ({
            id: i + 1,
            sender: i % 2 === 0 ? 'support' : 'user',
            message: `Mensagem de resposta ${i + 1}`,
            timestamp: new Date(Date.now() - (i * 3600000)).toISOString(),
            attachments: i === 0 && Math.random() > 0.8 ? [{
                name: 'documento.pdf',
                size: '1.2 MB'
            }] : []
        }));
    }

    // Estado da aplicação
    let currentFilter = 'all';
    let currentTickets = [...mockTickets];
    let activeChatMessages = [];

    // Elementos DOM
    const ticketsList = document.getElementById('ticketsList');
    const ticketSearch = document.getElementById('ticketSearch');
    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    const agentStatus = document.getElementById('agentStatus');
    const chatStatus = document.getElementById('chatStatus');
    
    // Contadores
    const openTicketsCount = document.getElementById('openTicketsCount');
    const pendingTicketsCount = document.getElementById('pendingTicketsCount');
    const closedTicketsCount = document.getElementById('closedTicketsCount');
    const urgentTicketsCount = document.getElementById('urgentTicketsCount');

    // Modais
    const ticketDetailsModal = new bootstrap.Modal(document.getElementById('ticketDetailsModal'));

    // Inicializar
    function init() {
        renderTickets();
        updateCounters();
        initChat();
        setupEventListeners();
        
        // Iniciar chat com mensagens iniciais
        setTimeout(() => {
            agentStatus.textContent = 'Conectado com Ana Silva';
            addChatMessage('received', 'Olá! Em que posso ajudá-lo hoje?');
        }, 1000);
    }

    // Renderizar lista de tickets
    function renderTickets() {
        let filtered = currentTickets;
        
        if (currentFilter !== 'all') {
            filtered = currentTickets.filter(ticket => {
                if (currentFilter === 'open') return ticket.status === 'open';
                if (currentFilter === 'pending') return ticket.status === 'pending';
                if (currentFilter === 'closed') return ticket.status === 'closed';
                if (currentFilter === 'urgent') return ticket.priority === 'urgent';
                return true;
            });
        }
        
        if (filtered.length === 0) {
            ticketsList.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhum ticket encontrado</h5>
                    <p class="text-muted">Tente ajustar os filtros ou criar um novo ticket.</p>
                </div>
            `;
            return;
        }
        
        ticketsList.innerHTML = filtered.map(ticket => `
            <div class="ticket-card mb-3 p-3 ${ticket.status}" onclick="showTicketDetails('${ticket.id}')">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="mb-1">${ticket.subject}</h6>
                        <small class="text-muted">ID: ${ticket.id}</small>
                    </div>
                    <div class="text-end">
                        <span class="priority-badge priority-${ticket.priority} mb-2 d-inline-block">
                            ${translatePriority(ticket.priority)}
                        </span>
                        <br>
                        <span class="status-badge status-${ticket.status}">
                            ${translateStatus(ticket.status)}
                        </span>
                    </div>
                </div>
                <p class="text-muted mb-2 small">${ticket.description.substring(0, 100)}...</p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-calendar me-1"></i>
                        ${formatDate(ticket.createdAt)}
                    </small>
                    <small class="text-muted">
                        <i class="bi bi-person me-1"></i>
                        ${ticket.assignedTo.name}
                    </small>
                </div>
                ${ticket.attachments.length > 0 ? `
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="bi bi-paperclip me-1"></i>
                            ${ticket.attachments.length} anexo(s)
                        </small>
                    </div>
                ` : ''}
            </div>
        `).join('');
    }

    // Filtrar tickets
    window.filterTickets = function(filter) {
        currentFilter = filter;
        renderTickets();
        
        // Atualizar botões ativos
        document.querySelectorAll('.quick-action-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event?.target?.classList?.add('active');
    };

    // Buscar tickets
    function searchTickets() {
        const term = ticketSearch.value.toLowerCase().trim();
        
        if (!term) {
            currentTickets = [...mockTickets];
        } else {
            currentTickets = mockTickets.filter(ticket => 
                ticket.subject.toLowerCase().includes(term) ||
                ticket.description.toLowerCase().includes(term) ||
                ticket.id.toLowerCase().includes(term) ||
                ticket.assignedTo.name.toLowerCase().includes(term)
            );
        }
        
        renderTickets();
        updateCounters();
    }

    // Atualizar contadores
    function updateCounters() {
        const open = mockTickets.filter(t => t.status === 'open').length;
        const pending = mockTickets.filter(t => t.status === 'pending').length;
        const closed = mockTickets.filter(t => t.status === 'closed').length;
        const urgent = mockTickets.filter(t => t.priority === 'urgent').length;
        
        openTicketsCount.textContent = open;
        pendingTicketsCount.textContent = pending;
        closedTicketsCount.textContent = closed;
        urgentTicketsCount.textContent = urgent;
    }

    // Mostrar detalhes do ticket
    window.showTicketDetails = function(ticketId) {
        const ticket = mockTickets.find(t => t.id === ticketId);
        if (!ticket) return;
        
        const modalTitle = document.getElementById('ticketModalTitle');
        const modalBody = document.getElementById('ticketModalBody');
        
        modalTitle.textContent = `Ticket ${ticket.id}: ${ticket.subject}`;
        
        modalBody.innerHTML = `
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Descrição</h6>
                        </div>
                        <div class="card-body">
                            <p>${ticket.description}</p>
                            ${ticket.attachments.length > 0 ? `
                                <div class="mt-3">
                                    <h6>Anexos:</h6>
                                    ${ticket.attachments.map(att => `
                                        <div class="attachment-badge d-inline-block me-2">
                                            <i class="bi bi-paperclip me-1"></i>
                                            ${att.name} (${att.size})
                                            <button class="btn btn-sm btn-outline-primary ms-2">
                                                <i class="bi bi-download"></i>
                                            </button>
                                        </div>
                                    `).join('')}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Histórico de Conversa</h6>
                        </div>
                        <div class="card-body">
                            ${ticket.replies.length > 0 ? `
                                <div class="chat-messages" style="height: 300px;">
                                    ${ticket.replies.map(reply => `
                                        <div class="message ${reply.sender === 'support' ? 'received' : 'sent'} mb-3">
                                            <div class="message-content">
                                                <p class="mb-1">${reply.message}</p>
                                                ${reply.attachments.length > 0 ? `
                                                    <div class="attachment-badge">
                                                        <i class="bi bi-paperclip me-1"></i>
                                                        ${reply.attachments[0].name}
                                                    </div>
                                                ` : ''}
                                                <div class="message-time">
                                                    ${formatDateTime(reply.timestamp)}
                                                </div>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                                <div class="mt-3">
                                    <textarea class="form-control mb-2" rows="3" 
                                              placeholder="Digite sua resposta..."></textarea>
                                    <button class="btn btn-primary btn-sm">
                                        <i class="bi bi-send me-1"></i> Enviar Resposta
                                    </button>
                                </div>
                            ` : `
                                <div class="text-center py-4">
                                    <i class="bi bi-chat-text fs-1 text-muted"></i>
                                    <p class="text-muted mt-2">Nenhuma resposta ainda</p>
                                </div>
                            `}
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Informações do Ticket</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <small class="text-muted">ID</small>
                                <p class="mb-0">${ticket.id}</p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Status</small>
                                <p>
                                    <span class="status-badge status-${ticket.status}">
                                        ${translateStatus(ticket.status)}
                                    </span>
                                </p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Prioridade</small>
                                <p>
                                    <span class="priority-badge priority-${ticket.priority}">
                                        ${translatePriority(ticket.priority)}
                                    </span>
                                </p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Categoria</small>
                                <p class="mb-0">${translateCategory(ticket.category)}</p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Criado em</small>
                                <p class="mb-0">${formatDateTime(ticket.createdAt)}</p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Atualizado em</small>
                                <p class="mb-0">${formatDateTime(ticket.updatedAt)}</p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Atribuído a</small>
                                <p class="mb-0">
                                    <strong>${ticket.assignedTo.name}</strong><br>
                                    <small class="text-muted">${ticket.assignedTo.role}</small>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Ações</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-chat me-1"></i> Responder
                                </button>
                                <button class="btn btn-outline-success btn-sm">
                                    <i class="bi bi-check-circle me-1"></i> Marcar como Resolvido
                                </button>
                                <button class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-clock me-1"></i> Colocar em Espera
                                </button>
                                ${ticket.status !== 'closed' ? `
                                    <button class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-x-circle me-1"></i> Fechar Ticket
                                    </button>
                                ` : ''}
                                <button class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-download me-1"></i> Exportar Conversa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        ticketDetailsModal.show();
    };

    // Inicializar chat
    function initChat() {
        activeChatMessages = [
            {
                type: 'received',
                message: 'Olá! Sou a Ana do suporte. Como posso ajudá-lo hoje?',
                time: '10:30 AM'
            },
            {
                type: 'sent',
                message: 'Preciso de ajuda com a configuração de um novo artista no sistema.',
                time: '10:32 AM'
            },
            {
                type: 'received',
                message: 'Claro! Posso te guiar passo a passo. Primeiro, vá até a seção "Artistas" no menu principal.',
                attachments: [{ name: 'guia_configuracao.pdf' }],
                time: '10:33 AM'
            }
        ];
        
        renderChatMessages();
    }

    // Renderizar mensagens do chat
    function renderChatMessages() {
        chatMessages.innerHTML = activeChatMessages.map(msg => `
            <div class="message ${msg.type} fade-in">
                <div class="message-content">
                    <p class="mb-1">${msg.message}</p>
                    ${msg.attachments ? `
                        <div class="attachment-badge">
                            <i class="bi bi-paperclip me-1"></i>
                            <span>${msg.attachments[0].name}</span>
                        </div>
                    ` : ''}
                    <div class="message-time">${msg.time}</div>
                </div>
            </div>
        `).join('');
        
        // Rolagem automática para baixo
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Adicionar mensagem ao chat
    function addChatMessage(type, message, attachments = null) {
        const now = new Date();
        const time = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;
        
        activeChatMessages.push({
            type,
            message,
            attachments,
            time
        });
        
        renderChatMessages();
        
        // Resposta automática do suporte (simulação)
        if (type === 'sent') {
            setTimeout(() => {
                const responses = [
                    'Entendo, vou verificar isso para você.',
                    'Pode me dar mais detalhes sobre o problema?',
                    'Já identifiquei a causa, estou trabalhando na solução.',
                    'Você poderia tentar reiniciar o aplicativo?',
                    'Isso parece ser um problema conhecido, temos uma correção em andamento.'
                ];
                const randomResponse = responses[Math.floor(Math.random() * responses.length)];
                
                addChatMessage('received', randomResponse);
                
                // Atualizar status do agente
                setTimeout(() => {
                    agentStatus.textContent = 'Digitando...';
                    setTimeout(() => {
                        agentStatus.textContent = 'Conectado com Ana Silva';
                    }, 1000);
                }, 500);
            }, 1000);
        }
    }

    // Enviar mensagem
    function sendChatMessage() {
        const message = chatInput.value.trim();
        if (!message) return;
        
        addChatMessage('sent', message);
        chatInput.value = '';
        chatInput.focus();
    }

    // Adicionar resposta rápida
    window.addQuickResponse = function(response) {
        chatInput.value = response;
        chatInput.focus();
    };

    // Submeter novo ticket
    window.submitNewTicket = function() {
        const subject = document.getElementById('ticketSubject').value;
        const category = document.getElementById('ticketCategory').value;
        const priority = document.getElementById('ticketPriority').value;
        const description = document.getElementById('ticketDescription').value;
        
        if (!subject || !category || !description) {
            showNotification('Por favor, preencha todos os campos obrigatórios.', 'warning');
            return;
        }
        
        // Simular envio
        setTimeout(() => {
            showNotification('Ticket criado com sucesso! Nossa equipe entrará em contato em breve.', 'success');
            
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('newTicketModal'));
            modal.hide();
            
            // Resetar formulário
            document.getElementById('newTicketForm').reset();
            
            // Atualizar lista de tickets
            setTimeout(() => {
                mockTickets.unshift({
                    id: `TKT-${1000 + mockTickets.length}`,
                    subject,
                    description,
                    category,
                    priority,
                    status: 'open',
                    createdAt: new Date().toISOString(),
                    updatedAt: new Date().toISOString(),
                    lastResponse: 'Aguardando primeira resposta',
                    assignedTo: getRandomAgent(),
                    replies: [],
                    attachments: []
                });
                
                renderTickets();
                updateCounters();
            }, 500);
        }, 1500);
    };

    // Submeter solicitação de chamada
    window.submitCallRequest = function() {
        const phone = document.getElementById('callPhone').value;
        const subject = document.getElementById('callSubject').value;
        
        if (!phone || !subject) {
            showNotification('Por favor, preencha todos os campos obrigatórios.', 'warning');
            return;
        }
        
        // Simular envio
        setTimeout(() => {
            showNotification('Solicitação de chamada enviada! Nossa equipe entrará em contato em até 1 hora útil.', 'success');
            
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('callModal'));
            modal.hide();
            
            // Resetar formulário
            document.getElementById('callRequestForm').reset();
        }, 1500);
    };

    // Configurar event listeners
    function setupEventListeners() {
        // Busca de tickets
        ticketSearch.addEventListener('input', debounce(searchTickets, 300));
        
        // Chat
        sendMessageBtn.addEventListener('click', sendChatMessage);
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendChatMessage();
            }
        });
        
        // Status do chat
        setInterval(() => {
            const statuses = ['Online', 'Digitando...', 'Online'];
            const randomStatus = statuses[Math.floor(Math.random() * statuses.length)];
            if (Math.random() > 0.7) {
                chatStatus.textContent = randomStatus;
                chatStatus.className = randomStatus === 'Online' ? 'badge bg-success' : 'badge bg-warning';
            }
        }, 5000);
    }

    // Funções auxiliares
    function translatePriority(priority) {
        const translations = {
            'low': 'Baixa',
            'medium': 'Média',
            'high': 'Alta',
            'urgent': 'Urgente'
        };
        return translations[priority] || priority;
    }

    function translateStatus(status) {
        const translations = {
            'open': 'Aberto',
            'pending': 'Pendente',
            'closed': 'Fechado'
        };
        return translations[status] || status;
    }

    function translateCategory(category) {
        const translations = {
            'technical': 'Técnico',
            'billing': 'Faturamento',
            'account': 'Conta',
            'feature': 'Funcionalidade',
            'bug': 'Bug',
            'other': 'Outro'
        };
        return translations[category] || category;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }

    function formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('pt-BR');
    }

    function showNotification(message, type = 'info') {
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
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Estilos CSS dinâmicos
    const style = document.createElement('style');
    style.textContent = `
        .ticket-card.open { border-left-color: #28a745; }
        .ticket-card.pending { border-left-color: #ffc107; }
        .ticket-card.closed { border-left-color: #6c757d; }
        .ticket-card.urgent { border-left-color: #dc3545; }
        
        .priority-low { background-color: #d1fae5; color: #065f46; }
        .priority-medium { background-color: #fef3c7; color: #92400e; }
        .priority-high { background-color: #fee2e2; color: #b91c1c; }
        .priority-urgent { background-color: #dc3545; color: white; }
        
        .status-open { background-color: #d1fae5; color: #065f46; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-closed { background-color: #e9ecef; color: #495057; }
        
        .btn-wasomupfy { background-color: #FF0089; border-color: #FF0089; color: white; }
        .btn-wasomupfy:hover { background-color: #e0007a; border-color: #e0007a; color: white; }
        
        .quick-action-btn.active { background-color: #007bff; color: white; }
    `;
    document.head.appendChild(style);

    // Inicializar
    init();
});