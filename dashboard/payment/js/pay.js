// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Pagamento
// Arquivo: dashboard/payment/js/pay.js
// ══════════════════════════════════════════════
// Depende das constantes injectadas pelo PHP em pay.php:
//   EXPIRES_AT, BASE_URL, PAY_PAGE_URL, INTENT_PAGE_URL,
//   INTENT_ID, CSRF, PLAN_NAME, REFERENCE_CODE, AMOUNT_LABEL,
//   PROOF_STATUS, INTENT_STATUS, REJECT_REASON,
//   IS_PROCESSING, INITIAL_AUTO_APPROVE_TS, currentStep

// ── Constantes puras (sem PHP) ──────────────
const TOTAL_SECS               = 3600;
const AUTO_APPROVE_WINDOW_SECS = 1800;

let autoApproveInterval = null;

// ══════════════════════════════════════════════
// NAVEGAÇÃO ENTRE STEPS
// ══════════════════════════════════════════════
function goStep(n) {
    document.querySelectorAll('.pay-step').forEach(s => s.style.display = 'none');
    document.getElementById('step-' + n).style.display = 'block';
    currentStep = n;
    updateStepsNav(n);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateStepsNav(active) {
    document.querySelectorAll('.step-item').forEach(item => {
        const s      = parseInt(item.dataset.step);
        const circle = item.querySelector('.step-circle');
        item.classList.remove('active', 'done');
        if (s < active) {
            item.classList.add('done');
            circle.innerHTML = '<i class="bi bi-check"></i>';
        } else if (s === active) {
            item.classList.add('active');
            circle.textContent = s;
        } else {
            circle.textContent = s;
        }
    });
}

function statusCard() {
    const step4 = document.getElementById('step-4');
    return step4 ? step4.querySelector('.pay-card') : null;
}

function syncIntentUrl() {
    window.history.replaceState({}, '', INTENT_PAGE_URL);
}

// ══════════════════════════════════════════════
// AUTO-APPROVE COUNTDOWN
// ══════════════════════════════════════════════
function stopAutoApproveCountdown() {
    if (autoApproveInterval) {
        clearInterval(autoApproveInterval);
        autoApproveInterval = null;
    }
}

function startAutoApproveCountdown(autoApproveTs) {
    stopAutoApproveCountdown();
    if (!autoApproveTs) return;

    const timerEl = document.getElementById('auto-approve-countdown');
    const fillEl  = document.getElementById('auto-approve-fill');
    if (!timerEl || !fillEl) return;

    const tick = () => {
        const remaining  = Math.max(0, autoApproveTs - Math.floor(Date.now() / 1000));
        const minutes    = Math.floor(remaining / 60);
        const seconds    = remaining % 60;
        const percentage = Math.max(0, Math.min(100, (remaining / AUTO_APPROVE_WINDOW_SECS) * 100));

        timerEl.textContent = remaining > 0
            ? `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
            : 'A verificar...';
        fillEl.style.width = `${percentage}%`;

        if (remaining === 0) {
            stopAutoApproveCountdown();
            setTimeout(() => { window.location.href = INTENT_PAGE_URL; }, 3000);
        }
    };

    tick();
    autoApproveInterval = setInterval(tick, 1000);
}

// ══════════════════════════════════════════════
// RENDER STATES — Step 4
// ══════════════════════════════════════════════
function renderRejectedStatus(message = REJECT_REASON) {
    const card = statusCard();
    if (!card) return;

    card.innerHTML = `
        <div class="status-icon-wrap status-rejected">
            <i class="bi bi-x-circle-fill"></i>
        </div>
        <h4 class="fw-bold mb-2">Comprovativo Rejeitado</h4>
        ${message ? `
            <div class="alert-dark-warn mb-3" style="text-align:left">
                <i class="bi bi-chat-left-text me-2"></i>
                <strong>Motivo:</strong> ${message}
            </div>
        ` : ''}
        <p class="text-muted mb-3">Cria uma nova referencia e envia um novo comprovativo correcto.</p>
        <a href="${PAY_PAGE_URL}" class="btn-pay d-block">
            <i class="bi bi-arrow-repeat me-2"></i>Tentar Novamente
        </a>`;
}

function renderReviewStatus(message) {
    const card = statusCard();
    if (!card) return;

    card.innerHTML = `
        <div class="status-icon-wrap status-pending">
            <i class="bi bi-person-check-fill"></i>
        </div>
        <h4 class="fw-bold mb-2">Em Revisao</h4>
        <p class="text-muted mb-3">${message}</p>
        <div class="info-row text-start mb-2">
            <div>
                <div class="info-row-label">Plano</div>
                <div class="info-row-value">${PLAN_NAME}</div>
            </div>
        </div>
        <div class="info-row text-start mb-3">
            <div>
                <div class="info-row-label">Referencia</div>
                <div class="info-row-value">${REFERENCE_CODE}</div>
            </div>
        </div>
        <a href="${BASE_URL}/painel" class="btn-outline-pay d-block" style="text-align:center">
            <i class="bi bi-house me-2"></i>Voltar ao Painel
        </a>`;
}

function renderProcessingStatus(message, autoApproveTs) {
    const card = statusCard();
    if (!card) return;

    card.innerHTML = `
        <div class="status-icon-wrap" style="background:rgba(99,102,241,.1);border:2px solid #6366f1;color:#6366f1">
            <i class="bi bi-hourglass-split"></i>
        </div>
        <h4 class="fw-bold mb-2">A processar pagamento</h4>
        <p class="text-muted mb-3">${message}</p>
        <div id="processing-timer" style="background:rgba(99,102,241,.07);border:1px solid rgba(99,102,241,.25);border-radius:10px;padding:.875rem 1rem;margin-bottom:1.25rem">
            <div style="font-size:.78rem;color:#818cf8;margin-bottom:.35rem">Activacao automatica em</div>
            <div id="auto-approve-countdown" style="font-size:1.4rem;font-weight:700;color:#a5b4fc;font-variant-numeric:tabular-nums">--:--</div>
            <div class="countdown-bar mt-2">
                <div id="auto-approve-fill" class="countdown-fill" style="background:linear-gradient(90deg,#6366f1,#818cf8);width:100%"></div>
            </div>
        </div>
        <div class="info-row text-start mb-2">
            <div>
                <div class="info-row-label">Plano</div>
                <div class="info-row-value">${PLAN_NAME}</div>
            </div>
        </div>
        <div class="info-row text-start mb-2">
            <div>
                <div class="info-row-label">Referencia</div>
                <div class="info-row-value">${REFERENCE_CODE}</div>
            </div>
        </div>
        <div class="info-row text-start mb-3">
            <div>
                <div class="info-row-label">Valor</div>
                <div class="info-row-value" style="color:var(--pink)">${AMOUNT_LABEL}</div>
            </div>
        </div>
        <p style="font-size:.8rem;color:var(--muted);margin-top:1rem">
            Vais receber uma notificacao e um email quando o plano for activado.
        </p>
        <a href="${BASE_URL}/painel" class="btn-outline-pay d-block" style="text-align:center">
            <i class="bi bi-house me-2"></i>Voltar ao Painel
        </a>`;

    startAutoApproveCountdown(autoApproveTs);
}

// ══════════════════════════════════════════════
// SYNC DO ESTADO INICIAL NO STEP 4
// ══════════════════════════════════════════════
function syncInitialStep4State() {
    if (currentStep !== 4) return;

    syncIntentUrl();

    if (PROOF_STATUS === 'rejected' || INTENT_STATUS === 'rejected') {
        renderRejectedStatus(REJECT_REASON);
        return;
    }

    if (IS_PROCESSING) {
        renderProcessingStatus(
            'O teu comprovativo foi recebido. O plano sera activado automaticamente assim que a validacao terminar, normalmente em cerca de 30 minutos.',
            INITIAL_AUTO_APPROVE_TS
        );
        return;
    }

    if (PROOF_STATUS === 'pending' || INTENT_STATUS === 'under_review') {
        renderReviewStatus(
            'O teu comprovativo foi recebido e esta em validacao manual. Vais receber uma notificacao assim que o processo estiver concluido.'
        );
    }
}

// ══════════════════════════════════════════════
// MÉTODO DE PAGAMENTO (Express / IBAN)
// ══════════════════════════════════════════════
function switchMethod(method, btn) {
    document.querySelectorAll('.method-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.method-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panel-' + method).classList.add('active');
    document.getElementById('method_used').value = method;
    const sel = document.getElementById('method-select');
    if (sel) sel.value = method;
}

// ══════════════════════════════════════════════
// COPIAR PARA CLIPBOARD
// ══════════════════════════════════════════════
function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML  = '<i class="bi bi-check2 me-1"></i>Copiado!';
        btn.style.color = '#00c864';
        setTimeout(() => {
            btn.innerHTML  = orig;
            btn.style.color = '';
        }, 2000);
    });
}

// ══════════════════════════════════════════════
// COUNTDOWN DA REFERÊNCIA
// ══════════════════════════════════════════════
function startCountdown() {
    const timer = document.getElementById('countdown-timer');
    const fill  = document.getElementById('countdown-fill');
    if (!timer) return;

    function tick() {
        const remaining = Math.max(0, Math.floor((EXPIRES_AT - Date.now()) / 1000));
        const m = Math.floor(remaining / 60);
        const s = remaining % 60;
        timer.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        const pct = (remaining / TOTAL_SECS) * 100;
        fill.style.width = pct + '%';

        if (remaining <= 300) { // últimos 5 min → vermelho
            fill.style.background = 'linear-gradient(90deg, #ff4444, #ff2222)';
            timer.style.color     = '#ff4444';
        }
        if (remaining === 0) {
            timer.textContent = 'EXPIRADO';
            clearInterval(iv);
            document.getElementById('step-2').innerHTML +=
                '<div class="alert-dark-warn mt-3"><i class="bi bi-clock-history me-2"></i>A tua referência expirou. <a href="" style="color:var(--pink)">Clica aqui para gerar uma nova.</a></div>';
        }
    }
    tick();
    const iv = setInterval(tick, 1000);
}

// ══════════════════════════════════════════════
// CONFIRMAR QUE VIU AS INSTRUÇÕES
// ══════════════════════════════════════════════
function confirmPaymentSeen() {
    fetch('payment_process', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ action: 'seen', intent_id: INTENT_ID, csrf: CSRF })
    }).finally(() => goStep(3));
}

// ══════════════════════════════════════════════
// DRAG & DROP + PREVIEW FICHEIRO
// ══════════════════════════════════════════════
const uploadArea = document.getElementById('upload-area');
const fileInput  = document.getElementById('comprovativo');

uploadArea.addEventListener('dragover', e => {
    e.preventDefault();
    uploadArea.classList.add('drag-over');
});
uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag-over'));
uploadArea.addEventListener('drop', e => {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        fileInput.dispatchEvent(new Event('change'));
    }
});

fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];
    if (!file) return;

    document.getElementById('upload-icon-i').className = 'bi bi-file-earmark-check';
    document.getElementById('upload-label').textContent = file.name;

    const preview = document.getElementById('file-preview');
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.style.display = 'block';
            preview.innerHTML     =
                `<img src="${e.target.result}" style="max-height:150px;border-radius:8px;border:1px solid var(--border)" alt="preview">`;
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'block';
        preview.innerHTML     =
            `<div style="color:var(--muted)"><i class="bi bi-file-pdf fs-3"></i><br><small>PDF seleccionado</small></div>`;
    }
});

// ══════════════════════════════════════════════
// SUBMETER COMPROVATIVO
// ══════════════════════════════════════════════
// Nota: apenas o segundo listener (com useCapture=true) está ativo —
// o primeiro é cancelado por stopImmediatePropagation().
// Mantemos apenas o segundo aqui (o correto).
document.getElementById('proof-form').addEventListener('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    const btn = document.getElementById('submit-proof');
    const err = document.getElementById('upload-error');
    err.style.display = 'none';

    const file = fileInput.files[0];
    if (!file) {
        err.style.display = 'block';
        err.textContent   = 'Selecciona um comprovativo antes de enviar.';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        err.style.display = 'block';
        err.textContent   = 'O ficheiro e muito grande. Maximo 5 MB.';
        return;
    }

    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A enviar...';

    fetch('payment_process', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                err.style.display = 'block';
                err.textContent   = data.message || 'Erro ao enviar. Tenta novamente.';
                btn.disabled      = false;
                btn.innerHTML     = '<i class="bi bi-send me-2"></i>Enviar Comprovativo';
                return;
            }

            syncIntentUrl();
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-send me-2"></i>Enviar Comprovativo';

            if (data.state === 'processing') {
                renderProcessingStatus(
                    data.message || 'Comprovativo recebido. O teu plano esta em processamento.',
                    data.auto_approve_ts || null
                );
            } else {
                renderReviewStatus(
                    data.message || 'O teu comprovativo foi recebido e esta em validacao manual.'
                );
            }

            goStep(4);
        })
        .catch(() => {
            err.style.display = 'block';
            err.textContent   = 'Erro de ligacao. Verifica a tua internet e tenta novamente.';
            btn.disabled      = false;
            btn.innerHTML     = '<i class="bi bi-send me-2"></i>Enviar Comprovativo';
        });
}, true);

// ══════════════════════════════════════════════
// INIT
// ══════════════════════════════════════════════
startCountdown();
syncInitialStep4State();