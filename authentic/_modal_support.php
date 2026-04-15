<?php
/**
 * WASOM UPFY v2.0 — Modal de Suporte (include partilhado)
 * Arquivo: authentic/_modal_support.php
 *
 * Uso em qualquer página de autenticação:
 *   <?php include __DIR__ . '/_modal_support.php'; ?>
*
* Pré-requisito: Bootstrap 5 e jQuery já carregados na página.
* O e-mail pré-preenchido é opcional — se existir $prefill_email usa-o.
*/
$support_prefill_email = $prefill_email ?? ($reset_email ?? ($email ?? ''));
?>

<!-- ══ MODAL SUPORTE ════════════════════════════════════════ -->
<div class="modal fade" id="support" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header border-0 pb-1">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:40px;height:40px;background:rgba(255,0,137,.1)">
                        <i class="bi bi-headset" style="color:#FF0089;font-size:1.1rem"></i>
                    </div>
                    <div>
                        <h5 class="modal-title text-dark mb-0">Falar com o Suporte</h5>
                        <small class="text-muted">Respondemos em até 48 horas</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pt-2">

                <!-- Feedback (oculto até ao envio) -->
                <div id="support-ok" style="padding: 1rem;"
                    class="alert alert-success d-flex align-items-center gap-2 d-none mb-3" role="alert">
                    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                    <div id="support-ok-msg"></div>
                </div>
                <div id="support-err" style="padding: 1rem;"
                    class="alert alert-danger d-flex align-items-center gap-2 d-none mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <div id="support-err-msg">Erro ao enviar. Tenta novamente.</div>
                </div>

                <form id="support-form" novalidate>
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />

                    <div class="mb-3" id="support-fields">
                        <!-- E-mail -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                E-mail <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="support-email" name="email_user" required
                                    maxlength="60" placeholder="teu@email.com"
                                    value="<?php echo htmlspecialchars($support_prefill_email); ?>" />
                                <div class="invalid-feedback">Insere um e-mail válido.</div>
                            </div>
                        </div>

                        <!-- Assunto (select) -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Assunto <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="subject" required>
                                <option value="" disabled selected>Selecciona o assunto</option>
                                <option value="Problema com login ou senha">Problema com login ou senha</option>
                                <option value="Problema com pagamento">Problema com pagamento</option>
                                <option value="Problema com distribuição">Problema com distribuição</option>
                                <option value="Conta suspensa ou bloqueada">Conta suspensa ou bloqueada</option>
                                <option value="Outro">Outro</option>
                            </select>
                            <div class="invalid-feedback">Selecciona o assunto.</div>
                        </div>

                        <!-- Mensagem -->
                        <div class="mb-1">
                            <label class="form-label fw-semibold">
                                Mensagem <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" name="messenger" id="support-msg" rows="4" required
                                minlength="10" maxlength="400" placeholder="Descreve o teu problema..."></textarea>
                            <div class="d-flex justify-content-between">
                                <div class="invalid-feedback" style="display:block;visibility:hidden" id="msg-val-err">
                                    A mensagem deve ter pelo menos 10 caracteres.
                                </div>
                                <small class="text-muted ms-auto"><span id="support-char-count">0</span>/400</small>
                            </div>
                        </div>
                    </div>

                </form>
            </div><!-- /modal-body -->

            <div class="modal-footer border-0 pt-0" id="support-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-pink" id="support-submit-btn" onclick="submitSupportForm()">
                    <span id="support-btn-text"><i class="bi bi-send me-1"></i>Enviar mensagem</span>
                    <span id="support-btn-load" class="d-none">
                        <span class="spinner-border spinner-border-sm me-1"></span>A enviar...
                    </span>
                </button>
            </div>

        </div>
    </div>
</div>
<!-- ══ fim modal suporte ══════════════════════════════════ -->

<script>
// Contador de caracteres
document.getElementById('support-msg').addEventListener('input', function() {
    document.getElementById('support-char-count').textContent = this.value.length;
});

// Limpar estado ao abrir o modal
document.getElementById('support').addEventListener('show.bs.modal', function() {
    document.getElementById('support-ok').classList.add('d-none');
    document.getElementById('support-err').classList.add('d-none');
    document.getElementById('support-fields').classList.remove('d-none');
    document.getElementById('support-footer').classList.remove('d-none');
    document.getElementById('support-btn-text').classList.remove('d-none');
    document.getElementById('support-btn-load').classList.add('d-none');
    document.getElementById('support-submit-btn').disabled = false;
    document.getElementById('support-form').classList.remove('was-validated');
    document.getElementById('support-msg').value = '';
    document.getElementById('support-char-count').textContent = '0';
});

function submitSupportForm() {
    const form = document.getElementById('support-form');
    const email = document.getElementById('support-email').value.trim();
    const subj = form.querySelector('[name="subject"]').value;
    const msg = document.getElementById('support-msg').value.trim();

    // Validação client-side
    let valid = true;
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        document.getElementById('support-email').classList.add('is-invalid');
        valid = false;
    } else {
        document.getElementById('support-email').classList.remove('is-invalid');
    }
    if (!subj) {
        form.querySelector('[name="subject"]').classList.add('is-invalid');
        valid = false;
    } else {
        form.querySelector('[name="subject"]').classList.remove('is-invalid');
    }
    if (msg.length < 10) {
        document.getElementById('support-msg').classList.add('is-invalid');
        document.getElementById('msg-val-err').style.visibility = 'visible';
        valid = false;
    } else {
        document.getElementById('support-msg').classList.remove('is-invalid');
        document.getElementById('msg-val-err').style.visibility = 'hidden';
    }
    if (!valid) return;

    // Loading state
    document.getElementById('support-btn-text').classList.add('d-none');
    document.getElementById('support-btn-load').classList.remove('d-none');
    document.getElementById('support-submit-btn').disabled = true;
    document.getElementById('support-err').classList.add('d-none');

    fetch('support_process', {
            method: 'POST',
            body: new FormData(form)
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                // Esconder formulário, mostrar sucesso
                document.getElementById('support-fields').classList.add('d-none');
                document.getElementById('support-footer').classList.add('d-none');
                document.getElementById('support-ok-msg').innerHTML = data.message;
                document.getElementById('support-ok').classList.remove('d-none');
            } else {
                document.getElementById('support-err-msg').textContent = data.message || 'Erro ao enviar.';
                document.getElementById('support-err').classList.remove('d-none');
                document.getElementById('support-btn-text').classList.remove('d-none');
                document.getElementById('support-btn-load').classList.add('d-none');
                document.getElementById('support-submit-btn').disabled = false;
            }
        })
        .catch(() => {
            document.getElementById('support-err-msg').textContent = 'Erro de ligação. Tenta novamente.';
            document.getElementById('support-err').classList.remove('d-none');
            document.getElementById('support-btn-text').classList.remove('d-none');
            document.getElementById('support-btn-load').classList.add('d-none');
            document.getElementById('support-submit-btn').disabled = false;
        });
}
</script>