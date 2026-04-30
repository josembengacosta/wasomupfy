// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Contas de Saque
// Arquivo: dashboard/finances/js/withdraw.js
// ══════════════════════════════════════════════
// Depende das constantes injectadas pelo PHP em withdraw.php:
//   CSRF, BASE_URL

toastr.options = {
    positionClass: 'toast-top-right',
    timeOut:       5000,
    progressBar:   true,
    closeButton:   true
};

// ── Info toast ─────────────────────────────────────────
$(function () {
    $('#infoBtn').click(function () {
        toastr.info('Para visualizar os dados das suas carteiras basta clicar <strong>Express</strong> ou <strong>IBAN</strong>');
    });
});

// ── Seleccionar tipo no modal de criação ───────────────
let selectedNewType = '';

function selectNewType(type) {
    selectedNewType = type;
    ['express', 'iban'].forEach(t => {
        const el = document.getElementById('tab-' + t + '-new');
        if (el) el.classList.toggle('active', t === type);
    });
    document.getElementById('new-express-wrap').style.display = (type === 'express') ? 'block' : 'none';
    document.getElementById('new-iban-wrap').style.display    = (type === 'iban')    ? 'block' : 'none';
    document.getElementById('hr-divider').style.display       = 'none';
}

function preselectType(type) {
    setTimeout(() => selectNewType(type), 350);
}

// ── Preview BI ─────────────────────────────────────────
function previewBI(input, previewId) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById(previewId);
        img.src = e.target.result;
        img.classList.remove('d-none');
    };
    reader.readAsDataURL(file);
}

// ── Câmara ─────────────────────────────────────────────
let camTarget  = '';
let camStream  = null;
let capturedBI = {};

function openCam(target) {
    camTarget = target;
    const labels = {
        'front-express': 'Frente (Express)',
        'back-express':  'Verso (Express)',
        'front-iban':    'Frente (IBAN)',
        'back-iban':     'Verso (IBAN)'
    };
    document.getElementById('cam-title').textContent = 'Fotografar BI — ' + (labels[target] || target);
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
        .then(stream => {
            camStream = stream;
            document.getElementById('camVideo').srcObject = stream;
            new bootstrap.Modal(document.getElementById('cameraModal')).show();
        })
        .catch(() => toastr.warning('Câmara não disponível. Usa o upload.'));
}

function stopCam() {
    if (camStream) { camStream.getTracks().forEach(t => t.stop()); camStream = null; }
}

function captureCam() {
    const v = document.getElementById('camVideo');
    const c = document.getElementById('camCanvas');
    c.width  = v.videoWidth;
    c.height = v.videoHeight;
    c.getContext('2d').drawImage(v, 0, 0);
    c.toBlob(blob => {
        const file = new File([blob], `bi_${camTarget}_${Date.now()}.jpg`, { type: 'image/jpeg' });
        capturedBI[camTarget] = file;

        const img = document.getElementById('prev-' + camTarget);
        if (img) { img.src = URL.createObjectURL(blob); img.classList.remove('d-none'); }

        const parts   = camTarget.split('-');
        const inputEl = document.getElementById(`bi_${parts[0]}_${parts[1]}`);
        if (inputEl) {
            const dt = new DataTransfer();
            dt.items.add(file);
            inputEl.files = dt.files;
        }
        stopCam();
        bootstrap.Modal.getInstance(document.getElementById('cameraModal')).hide();
        toastr.success('Foto capturada!');
    }, 'image/jpeg', 0.9);
}

// ── Submeter criar conta (Ajax) ────────────────────────
function handleCreateSubmit(form, errId, type) {
    const errEl = document.getElementById(errId);
    errEl.classList.add('d-none');

    const name = form.querySelector('[name=full_name]').value.trim();
    if (!name || name.split(' ').filter(Boolean).length < 2) {
        errEl.textContent = 'Insere o nome completo (nome e apelido).';
        errEl.classList.remove('d-none');
        return;
    }
    if (!form.querySelector('[name=confirm_password]').value) {
        errEl.textContent = 'Insere a senha para confirmar.';
        errEl.classList.remove('d-none');
        return;
    }

    if (type === 'iban') {
        const iban = form.querySelector('[name=iban_number]').value.replace(/\s/g, '');
        if (!iban.startsWith('AO') || iban.length < 20) {
            errEl.textContent = 'IBAN inválido (começa AO, mín. 20 car.).';
            errEl.classList.remove('d-none');
            return;
        }
    } else {
        const num = form.querySelector('[name=express_number]').value.trim();
        if (!/^9\d{8}$/.test(num)) {
            errEl.textContent = 'Número Express inválido (9 dígitos, começa por 9).';
            errEl.classList.remove('d-none');
            return;
        }
    }

    const bi_front = form.querySelector('[name=bi_front]').files[0] || capturedBI[`front-${type}`];
    const bi_back  = form.querySelector('[name=bi_back]').files[0]  || capturedBI[`back-${type}`];
    if (!bi_front) { errEl.textContent = 'Faz o upload da frente do BI.'; errEl.classList.remove('d-none'); return; }
    if (!bi_back)  { errEl.textContent = 'Faz o upload do verso do BI.';  errEl.classList.remove('d-none'); return; }

    const btn     = form.querySelector('[type=submit]');
    btn.disabled  = true;
    btn.value     = 'A enviar...';

    const fd = new FormData(form);
    if (capturedBI[`front-${type}`] && !form.querySelector('[name=bi_front]').files[0]) fd.set('bi_front', capturedBI[`front-${type}`]);
    if (capturedBI[`back-${type}`]  && !form.querySelector('[name=bi_back]').files[0])  fd.set('bi_back',  capturedBI[`back-${type}`]);

    fetch(BASE_URL + '/finances/account_process', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                bootstrap.Modal.getInstance(document.getElementById('creatnewAccount')).hide();
                toastr.success('Conta submetida! Verificação em até 48 horas.', 'Conta criada');
                setTimeout(() => location.reload(), 2200);
            } else {
                errEl.textContent = data.message || 'Erro. Tenta novamente.';
                errEl.classList.remove('d-none');
                btn.disabled = false;
                btn.value    = 'Salva';
            }
        })
        .catch(() => {
            errEl.textContent = 'Erro de ligação.';
            errEl.classList.remove('d-none');
            btn.disabled = false;
            btn.value    = 'Salva';
        });
}

document.getElementById('form-creat-express').addEventListener('submit', function (e) {
    e.preventDefault();
    handleCreateSubmit(this, 'err-express', 'express');
});
document.getElementById('form-creat-iban').addEventListener('submit', function (e) {
    e.preventDefault();
    handleCreateSubmit(this, 'err-iban', 'iban');
});

// ── Submeter edição (Ajax) ─────────────────────────────
['form-express', 'form-iban'].forEach(fid => {
    const f = document.getElementById(fid);
    if (!f) return;
    f.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn     = this.querySelector('[type=submit]');
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>A guardar...';

        fetch(BASE_URL + '/finances/account_process', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                if (d.ok) toastr.success('Dados actualizados. Aguarda re-verificação.', 'Guardado');
                else      toastr.error(d.message || 'Erro ao guardar.', 'Erro');
                btn.disabled  = false;
                btn.innerHTML = '<i class="bi bi-save me-1"></i>Alterar';
            })
            .catch(() => {
                toastr.error('Erro de ligação.');
                btn.disabled  = false;
                btn.innerHTML = '<i class="bi bi-save me-1"></i>Alterar';
            });
    });
});

// ── Eliminar conta — SweetAlert2 ───────────────────────
function confirmDelete(accountId, type) {
    Swal.fire({
        title:              `Eliminar conta ${type}?`,
        html:               '<p class="text-muted" style="font-size:.9rem">Esta acção é irreversível. Introduz a tua senha para confirmar.</p>',
        input:              'password',
        inputPlaceholder:   'A tua senha Wasom Upfy',
        inputAttributes:    { autocomplete: 'current-password' },
        icon:               'warning',
        iconColor:          '#dc3545',
        showCancelButton:   true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor:  '#6c757d',
        confirmButtonText:  '<i class="bi bi-trash"></i> Sim, eliminar',
        cancelButtonText:   'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: (password) => {
            if (!password) {
                Swal.showValidationMessage('Insere a tua senha para confirmar.');
                return false;
            }
            const body = new URLSearchParams({
                action:           'delete',
                id_account:       accountId,
                confirm_password: password,
                csrf_token:       CSRF
            });
            return fetch(BASE_URL + '/finances/account_process', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    body.toString()
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) { Swal.showValidationMessage(data.message || 'Erro ao eliminar.'); return false; }
                return data;
            })
            .catch(() => { Swal.showValidationMessage('Erro de ligação.'); return false; });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then(result => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Conta eliminada', text: `A conta ${type} foi removida com sucesso.`, icon: 'success', timer: 2000, showConfirmButton: false })
                .then(() => location.reload());
        }
    });
}

// ── Offline ────────────────────────────────────────────
window.addEventListener('offline', () =>
    new bootstrap.Toast(document.getElementById('connectionToast')).show()
);

function tryReconnect() {
    if (navigator.onLine) location.reload();
    else toastr.warning('Ainda sem ligação à internet.');
}

// ── Badge de notificações — polling 60s ───────────────
(function () {
    function refreshBadge() {
        fetch(BASE_URL + '/ajax/notifications_api?action=count', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                const b = document.getElementById('navNotifBadge');
                if (!b) return;
                const n = parseInt(data.unread || 0);
                b.textContent   = n > 99 ? '99+' : n;
                b.style.display = n > 0 ? '' : 'none';
            }).catch(function () {});
    }
    setTimeout(function () {
        refreshBadge();
        setInterval(refreshBadge, 60000);
    }, 30000);
})();