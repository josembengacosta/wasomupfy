// ══════════════════════════════════════════════════
// WASOM UPFY v2.0 — Onboarding
// Arquivo: dashboard/assets/js/onboarding.js
//
// CSRF: lido do meta tag gerado pelo PHP na página:
//   <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
//
// NÃO incluir PHP inline neste ficheiro — é servido
// como .js estático pelo servidor.
// ══════════════════════════════════════════════════
(function () {
    const TOTAL = 4;
    let current = 1;

    const modal = document.getElementById('onboardingModal');
    if (!modal) return; // onboarding_done = true, modal não existe

    const btnNext   = document.getElementById('ob-next');
    const btnPrev   = document.getElementById('ob-prev');
    const btnFinish = document.getElementById('ob-finish');
    const btnSkip   = document.getElementById('ob-skip-artist');
    const dots      = document.querySelectorAll('.ob-dot');

    // CSRF a partir do meta tag (definido pelo PHP na página)
    // <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    // Abrir modal automaticamente
    const bsModal = new bootstrap.Modal(modal, { backdrop: 'static' });
    bsModal.show();

    function goTo(n) {
        document.getElementById('ob-' + current).classList.add('d-none');
        current = n;
        document.getElementById('ob-' + current).classList.remove('d-none');

        // Dots
        dots.forEach((d, i) => d.classList.toggle('active', i + 1 === current));

        // Botões
        btnPrev.classList.toggle('d-none', current === 1);
        btnNext.classList.toggle('d-none', current === TOTAL);
        btnFinish.classList.toggle('d-none', current !== TOTAL);
    }

    btnNext.addEventListener('click', () => {
        if (current < TOTAL) goTo(current + 1);
    });

    btnPrev.addEventListener('click', () => {
        if (current > 1) goTo(current - 1);
    });

    // Botão "Saltar criação de artista" → vai directo para o step final
    if (btnSkip) {
        btnSkip.addEventListener('click', () => goTo(TOTAL));
    }

    // Exposto globalmente para o botão ob-finish chamar via onclick
    window.finishOnboarding = function () {
        if (btnFinish) {
            btnFinish.disabled = true;
            btnFinish.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A guardar…';
        }

        fetch('../onboarding_done', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf: csrfToken }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                bsModal.hide();
                // Recarregar a página para reflectir onboarding_done = true
                // e garantir que os alertas do dashboard são actualizados
                window.location.reload();
            } else {
                console.error('[onboarding] Resposta não ok:', data);
                bsModal.hide();
            }
        })
        .catch(err => {
            console.error('[onboarding] Fetch falhou:', err);
            bsModal.hide(); // fechar mesmo assim — não bloquear o utilizador
        });
    };
})();