    // ══════════════════════════════════════
    // ONBOARDING
    // ══════════════════════════════════════
    (function() {
        const TOTAL = 4;
        let current = 1;

        const modal = document.getElementById('onboardingModal');
        if (!modal) return; // onboarding_done = true, modal nao existe

        const btnNext = document.getElementById('ob-next');
        const btnPrev = document.getElementById('ob-prev');
        const btnFinish = document.getElementById('ob-finish');
        const btnSkip = document.getElementById('ob-skip-artist');
        const dots = document.querySelectorAll('.ob-dot');

        // Abrir modal automaticamente
        const bsModal = new bootstrap.Modal(modal, {
            backdrop: 'static'
        });
        bsModal.show();

        function goTo(n) {
            document.getElementById('ob-' + current).classList.add('d-none');
            current = n;
            document.getElementById('ob-' + current).classList.remove('d-none');

            // Dots
            dots.forEach((d, i) => d.classList.toggle('active', i + 1 === current));

            // Botoes
            btnPrev.classList.toggle('d-none', current === 1);
            btnNext.classList.toggle('d-none', current === TOTAL);
            btnFinish.classList.toggle('d-none', current !== TOTAL);
        }

        btnNext.addEventListener('click', () => {
            if (current < TOTAL) goTo(current + 1);
        });
        btnPrev.addEventListener('click',
            () => {
                if (current > 1) goTo(current - 1);
            });
        if (btnSkip) btnSkip.addEventListener('click', () => goTo(TOTAL)); // ja e o step 4, avanca para finish

        window.finishOnboarding = function() {
            // Marcar onboarding como feito via fetch
            fetch('/wasomupfy/dashboard/onboarding_done', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    csrf: '<?php echo $_SESSION["csrf_token"]; ?>'
                })
            }).finally(() => bsModal.hide());
        };
    })();