// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Perfil do Utilizador
// Arquivo: dashboard/user/js/profile.js
// ══════════════════════════════════════════════
// Depende das constantes injectadas pelo PHP em profile.php:
//   CSRF, PROCESS, VERIFY_STATUS

toastr.options = {
    progressBar:   true,
    closeButton:   true,
    positionClass: 'toast-top-right',
    timeOut:       4000
};

// ══════════════════════════════════════════════
// SECTION NAV
// ══════════════════════════════════════════════
function showSection(id) {
    document.querySelectorAll('.profile-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
    document.querySelectorAll('.mobile-tab').forEach(b => b.classList.remove('btn-primary'));

    const sec = document.getElementById('sec-' + id);
    if (sec) sec.classList.add('active');

    const navLink = document.querySelector(`.sidebar-nav [data-section="${id}"]`);
    if (navLink) navLink.classList.add('active');

    const mobileBtn = document.querySelector(`.mobile-tab[data-section="${id}"]`);
    if (mobileBtn) mobileBtn.classList.add('btn-primary');

    history.replaceState(null, '', '#' + id);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Init nav from URL hash
const hash = location.hash.replace('#', '');
if (['perfil', 'seguranca', 'notificacoes', 'sessoes', 'perigo'].includes(hash)) showSection(hash);

document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => {
    l.addEventListener('click', e => { e.preventDefault(); showSection(l.dataset.section); });
});
document.querySelectorAll('.mobile-tab').forEach(b => {
    b.addEventListener('click', () => showSection(b.dataset.section));
});

// ══════════════════════════════════════════════
// SHARED HELPERS
// ══════════════════════════════════════════════
function togglePwd(id, btn) {
    const inp  = document.getElementById(id);
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
}

async function postJSON(payload) {
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    for (const [k, v] of Object.entries(payload)) fd.append(k, v);
    const r = await fetch(PROCESS, { method: 'POST', body: fd });
    return r.json();
}

function setLoad(textId, loadId, btnEl, loading) {
    document.getElementById(textId)?.classList.toggle('d-none', loading);
    document.getElementById(loadId)?.classList.toggle('d-none', !loading);
    if (btnEl) btnEl.disabled = loading;
}

function showFeedback(id, ok, msg) {
    const el = document.getElementById(id);
    if (!el) return;
    el.innerHTML = `<div class="alert alert-${ok ? 'success' : 'danger'} small py-2 d-flex gap-2"><i class="bi bi-${ok ? 'check-circle' : 'exclamation-circle'}-fill flex-shrink-0"></i><div>${msg}</div></div>`;
    el.classList.remove('d-none');
}

// ══════════════════════════════════════════════
// VERIFY EMAIL
// ══════════════════════════════════════════════
async function resendVerifyEmail() {
    const r = await postJSON({ action: 'resend_verify_email' });
    if (r.ok) {
        await Swal.fire({
            icon:             'success',
            iconColor:        '#FF0089',
            title:            'Email enviado!',
            text:             r.message,
            confirmButtonColor: '#FF0089',
            timer:            4000,
            timerProgressBar: true
        });
    } else toastr.error(r.message);
}

// ══════════════════════════════════════════════
// PHOTO PREVIEW (edit modal)
// ══════════════════════════════════════════════
document.getElementById('edit-photo-input').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) { toastr.error('Imagem demasiado grande (máx. 5MB).'); return; }
    const reader = new FileReader();
    reader.onload = ev => {
        document.getElementById('edit-avatar-ph')?.classList.add('d-none');
        const img = document.getElementById('edit-avatar-preview');
        img.src = ev.target.result;
        img.classList.remove('d-none');
    };
    reader.readAsDataURL(file);
});

// Bio counter
const bioTA    = document.querySelector('#edit-profile-form [name="about_user"]');
const bioCount = document.getElementById('bio-count');
if (bioTA && bioCount) {
    bioCount.textContent = bioTA.value.length + ' / 500';
    bioTA.addEventListener('input', () => bioCount.textContent = bioTA.value.length + ' / 500');
}

// ══════════════════════════════════════════════
// USERNAME CHECK
// ══════════════════════════════════════════════
let usernameTimer;

function checkUsername(val) {
    clearTimeout(usernameTimer);
    val = val.toLowerCase().replace(/[^a-z0-9_.]/g, '');
    document.getElementById('edit-username').value = val;
    if (val.length < 3) {
        document.getElementById('username-feedback').textContent = '';
        document.getElementById('username-icon').innerHTML = '<i class="bi bi-dash text-muted"></i>';
        return;
    }
    document.getElementById('username-icon').innerHTML = '<span class="spinner-border spinner-border-sm text-muted"></span>';
    usernameTimer = setTimeout(async () => {
        const r    = await postJSON({ action: 'check_username', username: val });
        const icon = document.getElementById('username-icon');
        const fb   = document.getElementById('username-feedback');
        const sug  = document.getElementById('username-suggestions');
        if (r.available) {
            icon.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
            fb.innerHTML   = `<span class="text-success small">${r.message}</span>`;
            sug.innerHTML  = '';
        } else {
            icon.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
            fb.innerHTML   = `<span class="text-danger small">${r.message}</span>`;
            if (r.suggestions?.length) {
                sug.innerHTML = '<small class="text-muted me-1">Sugestões:</small>' +
                    r.suggestions.map(s =>
                        `<button type="button" class="btn btn-outline-secondary btn-sm py-0" style="font-size:.75rem" onclick="document.getElementById('edit-username').value='${s}';checkUsername('${s}')">${s}</button>`
                    ).join('');
            }
        }
    }, 600);
}

// ══════════════════════════════════════════════
// SAVE PROFILE
// ══════════════════════════════════════════════
async function saveProfile() {
    const btn = document.getElementById('btn-save-profile');
    setLoad('save-profile-text', 'save-profile-load', btn, true);

    const fd = new FormData(document.getElementById('edit-profile-form'));
    fd.append('action', 'update_profile');
    fd.append('csrf_token', CSRF);
    const photo = document.getElementById('edit-photo-input').files[0];
    if (photo) fd.set('photo_user', photo);

    try {
        const r    = await fetch(PROCESS, { method: 'POST', body: fd });
        const data = await r.json();
        if (data.ok) {
            bootstrap.Modal.getInstance(document.getElementById('editProfileModal')).hide();
            if (data.photo_url) {
                document.querySelectorAll('#sidebar-avatar,#hero-avatar,#edit-avatar-preview').forEach(el => {
                    el.src = data.photo_url;
                    el.classList.remove('d-none');
                });
                document.querySelectorAll('#sidebar-avatar-ph,#hero-avatar-ph,#edit-avatar-ph').forEach(el => el?.classList.add('d-none'));
            }
            toastr.success(data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showFeedback('edit-profile-feedback', false, data.message);
        }
    } catch {
        toastr.error('Erro de ligação.');
    } finally {
        setLoad('save-profile-text', 'save-profile-load', btn, false);
    }
}

// ══════════════════════════════════════════════
// PASSWORD STRENGTH
// ══════════════════════════════════════════════
function checkStrength(pwd) {
    const bar   = document.getElementById('strength-bar');
    const label = document.getElementById('strength-label');
    let score   = 0;
    if (pwd.length >= 8)          score++;
    if (pwd.length >= 12)         score++;
    if (/[A-Z]/.test(pwd))        score++;
    if (/[0-9]/.test(pwd))        score++;
    if (/[^A-Za-z0-9]/.test(pwd)) score++;
    const map = [
        [20,  '#dc3545', 'Muito fraca'],
        [40,  '#fd7e14', 'Fraca'],
        [60,  '#ffc107', 'Razoável'],
        [80,  '#20c997', 'Boa'],
        [100, '#198754', 'Excelente']
    ];
    const [w, c, t]     = map[score - 1] || [10, '#dc3545', 'Muito fraca'];
    bar.style.width      = w + '%';
    bar.style.background = c;
    label.textContent    = t;
    label.style.color    = c;
}

// ══════════════════════════════════════════════
// CHANGE PASSWORD
// ══════════════════════════════════════════════
async function changePassword() {
    const old  = document.getElementById('old-password').value;
    const nw   = document.getElementById('new-password').value;
    const conf = document.getElementById('confirm-password').value;
    if (!old || !nw || !conf) { toastr.error('Preenche todos os campos.'); return; }
    if (nw !== conf)           { toastr.error('As senhas não coincidem.'); return; }
    if (nw.length < 8)         { toastr.error('A senha deve ter pelo menos 8 caracteres.'); return; }

    const r = await postJSON({ action: 'change_password', old_password: old, new_password: nw, confirm_password: conf });
    if (r.ok) {
        toastr.success(r.message);
        ['old-password', 'new-password', 'confirm-password'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('strength-bar').style.width    = '0';
        document.getElementById('strength-label').textContent  = '';
        document.getElementById('btn-gen-recovery')?.removeAttribute('disabled');
        Swal.fire({
            icon: 'success', iconColor: '#FF0089', title: 'Senha alterada!',
            text: r.message, confirmButtonColor: '#FF0089', timer: 3000, timerProgressBar: true
        });
    } else toastr.error(r.message);
}

// ══════════════════════════════════════════════
// 2FA
// ══════════════════════════════════════════════
let totpSecretGlobal = '';

async function toggle2FA(checkbox) {
    if (checkbox.checked) {
        const r = await postJSON({ action: 'toggle_2fa', enable: 1 });
        if (r.ok) {
            totpSecretGlobal = r.secret;
            document.getElementById('2fa-qr').src                       = r.qr_url;
            document.getElementById('2fa-secret-display').textContent   = r.secret;
            document.getElementById('2fa-setup').classList.remove('d-none');
            document.getElementById('2fa-disable').classList.add('d-none');
        } else {
            toastr.error(r.message);
            checkbox.checked = false;
        }
    } else {
        checkbox.checked = true;
        document.getElementById('2fa-disable').classList.remove('d-none');
        document.getElementById('2fa-setup').classList.add('d-none');
        document.getElementById('2fa-disable-pwd').value = '';
        document.getElementById('2fa-disable-pwd').focus();
    }
}

async function confirm2FA() {
    const code = document.getElementById('totp-code').value.trim();
    if (code.length !== 6) { toastr.error('Insere os 6 dígitos.'); return; }
    const r = await postJSON({ action: 'confirm_2fa', totp_code: code, totp_secret: totpSecretGlobal });
    if (r.ok) {
        document.getElementById('2fa-setup').classList.add('d-none');
        await Swal.fire({
            icon: 'success', iconColor: '#FF0089', title: '2FA Activado!',
            text: r.message, confirmButtonColor: '#FF0089', timer: 3000, timerProgressBar: true
        });
        location.reload();
    } else toastr.error(r.message);
}

async function disable2FA() {
    const pwd = document.getElementById('2fa-disable-pwd').value;
    if (!pwd) { toastr.error('Introduz a tua senha.'); return; }
    const r = await postJSON({ action: 'toggle_2fa', enable: 0, password_confirm: pwd });
    if (r.ok) {
        await Swal.fire({
            icon: 'success', iconColor: '#198754', title: '2FA Desactivado',
            text: r.message, confirmButtonColor: '#198754', timer: 2500, timerProgressBar: true
        });
        location.reload();
    } else toastr.error(r.message);
}

// ══════════════════════════════════════════════
// RECOVERY KEY
// ══════════════════════════════════════════════
async function generateRecovery() {
    const r = await postJSON({ action: 'generate_recovery_key' });
    if (r.ok) {
        const segments = r.key.split('-');
        document.getElementById('recovery-key-text').innerHTML = segments.map(s => `<span class="recovery-segment">${s}</span>`).join(' - ');
        document.getElementById('recovery-key-display').classList.remove('d-none');
        document.getElementById('btn-download-recovery').classList.remove('d-none');
        document.getElementById('btn-copy-recovery').classList.remove('d-none');
        document.getElementById('btn-gen-recovery').setAttribute('disabled', '');
        Swal.fire({
            icon: 'warning', iconColor: '#FF0089', title: 'Guarda a tua chave!',
            html: '<p>Esta chave é mostrada <strong>uma única vez</strong>. Copia ou faz download agora e guarda num local seguro offline.</p>',
            confirmButtonColor: '#FF0089', confirmButtonText: 'Entendido, guardei'
        });
    } else toastr.error(r.message);
}

async function downloadRecovery() {
    const r = await postJSON({ action: 'download_recovery_key' });
    if (r.ok) {
        const filename_txt = (r.filename || 'wasom_recovery.txt').replace(/\.json$/, '') + (r.filename?.endsWith('.txt') ? '' : '.txt');
        const blob = new Blob([
            '============================\n' +
            'WASOM UPFY — Chave de Recuperação\n' +
            'Gerada em: ' + new Date().toLocaleDateString('pt-PT') + '\n' +
            '============================\n\n' +
            r.key + '\n\n' +
            'ATENÇÃO: Guarda esta chave offline num local seguro.\n' +
            'Não a partilhes com ninguém.\n'
        ], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a   = document.createElement('a');
        a.href     = url;
        a.download = r.filename || filename_txt;
        a.click();
        URL.revokeObjectURL(url);
        toastr.success('Chave descarregada!');
    } else toastr.error(r.message);
}

function copyRecovery() {
    const txt = document.getElementById('recovery-key-text').textContent.replace(/\s+/g, ' ').trim();
    navigator.clipboard.writeText(txt).then(() => toastr.success('Chave copiada!'));
}

// ══════════════════════════════════════════════
// NOTIFICATIONS (auto-save)
// ══════════════════════════════════════════════
let notifTimer;

function saveNotifications() {
    clearTimeout(notifTimer);
    notifTimer = setTimeout(async () => {
        const payload = { action: 'update_notifications' };
        ['notif_email', 'notif_push', 'notif_weekly', 'notif_releases', 'notif_payments'].forEach(id => {
            payload[id] = document.getElementById(id)?.checked ? 1 : 0;
        });
        const r    = await postJSON(payload);
        const hint = document.getElementById('notif-save-hint');
        if (r.ok) {
            if (hint) hint.style.display = 'block';
            setTimeout(() => hint && (hint.style.display = 'none'), 3000);
        } else toastr.error(r.message);
    }, 800);
}

// ══════════════════════════════════════════════
// LOGOUT ALL DEVICES
// ══════════════════════════════════════════════
async function logoutAllDevices() {
    const pwd = document.getElementById('logout-all-pwd').value;
    if (!pwd) { showFeedback('logout-all-feedback', false, 'Introduz a tua senha.'); return; }
    setLoad('logout-all-text', 'logout-all-load', null, true);
    const r = await postJSON({ action: 'logout_all_sessions', password_confirm: pwd });
    setLoad('logout-all-text', 'logout-all-load', null, false);
    if (r.ok) {
        bootstrap.Modal.getInstance(document.getElementById('logoutAllModal')).hide();
        await Swal.fire({ icon: 'success', iconColor: '#FF0089', title: 'Sessões encerradas!', text: r.message, confirmButtonColor: '#FF0089', timer: 2500 });
        location.reload();
    } else showFeedback('logout-all-feedback', false, r.message);
}

// ══════════════════════════════════════════════
// DOWNLOAD DATA
// ══════════════════════════════════════════════
async function downloadData() {
    const pwd = document.getElementById('download-data-pwd').value;
    if (!pwd) { showFeedback('download-data-feedback', false, 'Introduz a tua senha.'); return; }
    setLoad('dl-data-text', 'dl-data-load', null, true);
    const r = await postJSON({ action: 'download_data', password_confirm: pwd });
    setLoad('dl-data-text', 'dl-data-load', null, false);
    if (r.ok) {
        const blob = new Blob([r.data], { type: 'application/json' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = r.filename;
        a.click();
        URL.revokeObjectURL(url);
        bootstrap.Modal.getInstance(document.getElementById('downloadDataModal')).hide();
        toastr.success('Download iniciado!');
    } else showFeedback('download-data-feedback', false, r.message);
}

// ══════════════════════════════════════════════
// DEACTIVATE ACCOUNT
// ══════════════════════════════════════════════
async function deactivateAccount() {
    const pwd = document.getElementById('deactivate-pwd').value;
    if (!pwd) { showFeedback('deactivate-feedback', false, 'Introduz a tua senha.'); return; }
    setLoad('deact-text', 'deact-load', null, true);
    const r = await postJSON({ action: 'deactivate_account', password_confirm: pwd });
    setLoad('deact-text', 'deact-load', null, false);
    if (r.ok) {
        await Swal.fire({ icon: 'info', title: 'Conta desactivada', text: r.message, confirmButtonColor: '#FF0089' });
        window.location.href = r.redirect || '/';
    } else showFeedback('deactivate-feedback', false, r.message);
}

// ══════════════════════════════════════════════
// DELETE ACCOUNT
// ══════════════════════════════════════════════
function checkDeleteText() {
    const val      = document.getElementById('delete-confirm-text').value.trim().toLowerCase();
    const expected = 'eliminar a minha conta permanentemente';
    const check    = document.getElementById('delete-text-check');
    const btn      = document.getElementById('btn-confirm-delete');
    if (val === expected) {
        check.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Texto correcto</span>';
        btn.disabled    = false;
    } else {
        check.innerHTML = val.length > 0 ? '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Texto incorrecto</span>' : '';
        btn.disabled    = true;
    }
}

async function deleteAccount() {
    const pwd  = document.getElementById('delete-pwd').value;
    const text = document.getElementById('delete-confirm-text').value.trim();
    if (!pwd) { showFeedback('delete-feedback', false, 'Introduz a tua senha.'); return; }
    setLoad('del-text', 'del-load', null, true);
    const r = await postJSON({ action: 'delete_account', password_confirm: pwd, confirm_text: text });
    setLoad('del-text', 'del-load', null, false);
    if (r.ok) {
        await Swal.fire({ icon: 'info', title: 'Conta eliminada', text: 'A tua conta foi eliminada.', confirmButtonColor: '#FF0089' });
        window.location.href = r.redirect || '/';
    } else showFeedback('delete-feedback', false, r.message);
}

// ══════════════════════════════════════════════
// VERIFY STATUS (injectado pelo PHP via VERIFY_STATUS)
// ══════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    if (VERIFY_STATUS === 'success') {
        Swal.fire({
            icon: 'success', iconColor: '#FF0089', title: 'Email verificado!',
            text: 'O teu email foi verificado com sucesso.',
            confirmButtonColor: '#FF0089', timer: 4000, timerProgressBar: true
        });
    } else if (VERIFY_STATUS === 'error') {
        Swal.fire({
            icon: 'error', title: 'Erro na verificação',
            text: 'O link é inválido ou expirou. Solicita um novo.',
            confirmButtonColor: '#FF0089'
        });
    }
});