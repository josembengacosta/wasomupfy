// compose.js - Enviar Mensagem
document.addEventListener('DOMContentLoaded', function() {
    // Elementos DOM
    const composeForm = document.getElementById('composeForm');
    const toRecipients = document.getElementById('toRecipients');
    const recipientsContainer = document.getElementById('recipientsContainer');
    const emailAttachments = document.getElementById('emailAttachments');
    const attachmentsPreview = document.getElementById('attachmentsPreview');
    const attachmentsList = document.getElementById('attachmentsList');
    const emailMessage = document.getElementById('emailMessage');
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    
    // Estado
    let recipients = [];
    let attachments = [];
    let isRichText = true;

    // Inicializar
    function init() {
        setupEventListeners();
        initEditor();
    }

    // Configurar event listeners
    function setupEventListeners() {
        // Adicionar destinatário
        toRecipients.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === 'Tab' || e.key === ',') {
                e.preventDefault();
                addRecipient(this.value.trim());
                this.value = '';
            }
        });
        
        toRecipients.addEventListener('blur', function() {
            if (this.value.trim()) {
                addRecipient(this.value.trim());
                this.value = '';
            }
        });
        
        // Anexos
        emailAttachments.addEventListener('change', handleAttachments);
        
        // Formulário
        composeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            previewEmail();
        });
        
        // Arrastar e soltar arquivos
        const dropZone = document.querySelector('.attachment-preview');
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight(e) {
            dropZone.classList.add('border-primary');
        }
        
        function unhighlight(e) {
            dropZone.classList.remove('border-primary');
        }
        
        dropZone.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }
    }

    // Inicializar editor
    function initEditor() {
        emailMessage.innerHTML = `
            <p>Olá,</p>
            <p><br></p>
            <p>Atenciosamente,<br>
            <strong>Equipe Wasom Upfy</strong></p>
        `;
    }

    // Adicionar destinatário
    function addRecipient(email) {
        if (!isValidEmail(email)) {
            showNotification('Por favor, insira um email válido', 'warning');
            return;
        }
        
        if (recipients.includes(email)) {
            showNotification('Este email já foi adicionado', 'warning');
            return;
        }
        
        recipients.push(email);
        renderRecipients();
    }

    // Remover destinatário
    function removeRecipient(email) {
        recipients = recipients.filter(r => r !== email);
        renderRecipients();
    }

    // Renderizar destinatários
    function renderRecipients() {
        recipientsContainer.innerHTML = recipients.map(email => `
            <span class="recipient-tag">
                ${email}
                <button type="button" onclick="removeRecipient('${email}')">
                    <i class="bi bi-x"></i>
                </button>
            </span>
        `).join('');
    }

    // Validar email
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Manipular anexos
    function handleAttachments(e) {
        const files = Array.from(e.target.files);
        handleFiles(files);
    }

    function handleFiles(files) {
        files.forEach(file => {
            if (file.size > 25 * 1024 * 1024) {
                showNotification(`O arquivo "${file.name}" excede o limite de 25MB`, 'error');
                return;
            }
            
            attachments.push(file);
        });
        
        renderAttachments();
    }

    function renderAttachments() {
        if (attachments.length === 0) {
            attachmentsPreview.style.display = 'none';
            return;
        }
        
        attachmentsPreview.style.display = 'block';
        attachmentsList.innerHTML = attachments.map((file, index) => `
            <div class="attachment-item">
                <div>
                    <i class="bi bi-paperclip me-2"></i>
                    <span>${file.name}</span>
                    <small class="text-muted ms-2">(${formatFileSize(file.size)})</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" 
                        onclick="removeAttachment(${index})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `).join('');
    }

    window.removeAttachment = function(index) {
        attachments.splice(index, 1);
        renderAttachments();
    };

    // Formatar texto
    window.formatText = function(command) {
        document.execCommand(command, false, null);
        emailMessage.focus();
    };

    window.insertLink = function() {
        const url = prompt('Digite a URL:', 'https://');
        if (url) {
            document.execCommand('createLink', false, url);
        }
    };

    window.insertImage = function() {
        const url = prompt('Digite a URL da imagem:', 'https://');
        if (url) {
            document.execCommand('insertImage', false, url);
        }
    };

    // Carregar template
    window.loadTemplate = function(template) {
        const templates = {
            greeting: `<p>Prezado(a),</p><p><br></p><p>Agradecemos o seu contato.</p><p><br></p><p>Atenciosamente,<br><strong>Equipe Wasom Upfy</strong></p>`,
            followup: `<p>Olá,</p><p><br></p><p>Estou entrando em contato para acompanhar o andamento do seu ticket.</p><p><br></p><p>Atenciosamente,<br><strong>Equipe de Suporte</strong></p>`,
            support: `<p>Olá,</p><p><br></p><p>Agradecemos pelo seu contato. Nossa equipe está analisando sua solicitação e retornará em breve.</p><p><br></p><p>Atenciosamente,<br><strong>Suporte Wasom Upfy</strong></p>`
        };
        
        emailMessage.innerHTML = templates[template] || '';
        showNotification('Template carregado com sucesso', 'success');
    };

    // Salvar rascunho
    window.saveDraft = function() {
        const subject = document.getElementById('emailSubject').value;
        const message = emailMessage.innerHTML;
        
        if (!subject && !message) {
            showNotification('Nada para salvar', 'warning');
            return;
        }
        
        // Em produção, salvaria no servidor
        localStorage.setItem('emailDraft', JSON.stringify({
            subject,
            message,
            recipients,
            attachments: attachments.map(f => f.name),
            savedAt: new Date().toISOString()
        }));
        
        showNotification('Rascunho salvo com sucesso', 'success');
    };

    // Visualizar email
    window.previewEmail = function() {
        const subject = document.getElementById('emailSubject').value;
        const message = emailMessage.innerHTML;
        
        if (!subject || !message || recipients.length === 0) {
            showNotification('Por favor, preencha todos os campos obrigatórios', 'warning');
            return;
        }
        
        const previewContent = document.getElementById('previewContent');
        previewContent.innerHTML = `
            <div class="mb-3">
                <strong>Para:</strong> ${recipients.join(', ')}
            </div>
            <div class="mb-3">
                <strong>Assunto:</strong> ${subject}
            </div>
            <hr>
            <div class="email-content">
                ${message}
            </div>
            ${attachments.length > 0 ? `
                <hr>
                <div class="mt-3">
                    <strong>Anexos (${attachments.length}):</strong>
                    <ul class="mb-0">
                        ${attachments.map(f => `<li>${f.name} (${formatFileSize(f.size)})</li>`).join('')}
                    </ul>
                </div>
            ` : ''}
        `;
        
        previewModal.show();
    };

    // Enviar email
    window.sendEmail = function() {
        const subject = document.getElementById('emailSubject').value;
        const message = emailMessage.innerHTML;
        const cc = document.getElementById('ccRecipients').value;
        const bcc = document.getElementById('bccRecipients').value;
        
        // Simular envio
        showNotification('Enviando email...', 'info');
        
        setTimeout(() => {
            previewModal.hide();
            
            // Limpar formulário
            composeForm.reset();
            recipients = [];
            attachments = [];
            emailMessage.innerHTML = '';
            
            renderRecipients();
            renderAttachments();
            initEditor();
            
            showNotification('Email enviado com sucesso!', 'success');
        }, 2000);
    };

    // Agendar email
    window.scheduleEmail = function() {
        const subject = document.getElementById('emailSubject').value;
        const message = emailMessage.innerHTML;
        
        if (!subject || !message || recipients.length === 0) {
            showNotification('Por favor, preencha todos os campos obrigatórios', 'warning');
            return;
        }
        
        const date = prompt('Digite a data e hora para envio (DD/MM/AAAA HH:MM):');
        if (date) {
            showNotification(`Email agendado para ${date}`, 'success');
        }
    };

    // Funções auxiliares
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

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
            <strong>${type === 'success' ? '✓ Sucesso!' : type === 'error' ? '✗ Erro!' : '⚠ Atenção!'}</strong>
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