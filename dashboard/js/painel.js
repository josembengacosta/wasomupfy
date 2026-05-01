// ══════════════════════════════════════════════
// WASOM UPFY v2.0 — Painel Principal
// Arquivo: dashboard/js/painel.js
// ══════════════════════════════════════════════
// Depende de window.WASOM_CONFIG e das constantes injectadas pelo PHP:
//   HAS_STREAMS, CHART_LABELS, CHART_DATASETS

// ── Tooltips Bootstrap ─────────────────────────────────
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(
    el => new bootstrap.Tooltip(el)
);

// ── Sincronizar badge PWA ao carregar ──────────────────
document.addEventListener('DOMContentLoaded', function () {
    const n = window.WASOM_CONFIG?.notifCount ?? 0;
    if (n > 0 && 'setAppBadge' in navigator) {
        navigator.setAppBadge(n).catch(function () {});
    }
});

// ── Badge de notificações — polling 60s ───────────────
(function () {
    function refreshNotifBadge() {
        fetch('https://wasomupfy.rf.gd/dashboard/ajax/notifications_api?action=count', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                const badge = document.getElementById('navNotifBadge');
                if (!badge) return;
                const count = parseInt(data.unread || 0);
                badge.textContent   = count > 99 ? '99+' : count;
                badge.style.display = count > 0 ? '' : 'none';
            })
            .catch(function () {});
    }
    setTimeout(function () {
        refreshNotifBadge();
        setInterval(refreshNotifBadge, 60000);
    }, 30000);
})();

// ── Gráfico de streams ─────────────────────────────────
(function () {
    if (!HAS_STREAMS || !CHART_LABELS || !CHART_DATASETS) return;
    const canvas = document.getElementById('streamChart');
    if (!canvas) return;

    new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels:   CHART_LABELS,
            datasets: CHART_DATASETS
        },
        options: {
            responsive:          true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    stacked:     true,
                    ticks: { callback: v => v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v },
                    title: { display: true, text: 'Streams' }
                },
                x: {
                    stacked: true,
                    title:   { display: true, text: 'Período' }
                }
            },
            plugins: {
                legend:  { position: 'top' },
                tooltip: {
                    mode: 'index', intersect: false,
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString('pt-AO')} streams`
                    }
                }
            },
            interaction: { mode: 'nearest', axis: 'x', intersect: false }
        }
    });
})();

// ── Onboarding ─────────────────────────────────────────
(function () {
    const TOTAL = 5;
    let current = 1;

    const modal = document.getElementById('onboardingModal');
    if (!modal) return;

    const btnNext  = document.getElementById('ob-next');
    const btnPrev  = document.getElementById('ob-prev');
    const btnSkip  = document.getElementById('ob-skip-artist');
    const dots     = document.querySelectorAll('.ob-dot');
    const progBar  = document.getElementById('ob-progress-bar');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    const bsModal = new bootstrap.Modal(modal, { backdrop: 'static' });
    bsModal.show();

    function goTo(n) {
        document.getElementById('ob-' + current).classList.add('d-none');
        current = n;
        document.getElementById('ob-' + current).classList.remove('d-none');
        dots.forEach((d, i) => d.classList.toggle('active', i + 1 === current));
        if (progBar) progBar.style.width = ((current / TOTAL) * 100) + '%';
        btnPrev.classList.toggle('d-none', current === 1);
        btnNext.classList.toggle('d-none', current === TOTAL);
    }

    btnNext.addEventListener('click', () => { if (current < TOTAL) goTo(current + 1); });
    btnPrev.addEventListener('click', () => { if (current > 1)     goTo(current - 1); });
    if (btnSkip) btnSkip.addEventListener('click', () => goTo(TOTAL));

    window.obRequestPush = function (btn) {
        if (!('Notification' in window) || !('serviceWorker' in navigator)) {
            document.getElementById('ob-push-status').textContent = 'Não suportado neste dispositivo.';
            btn.disabled = true;
            return;
        }
        if (Notification.permission === 'granted') {
            document.getElementById('ob-push-status').innerHTML =
                '<i class="bi bi-check-circle-fill text-success"></i> Já activadas';
            btn.disabled = true;
            return;
        }
        Notification.requestPermission().then(function (perm) {
            if (perm === 'granted') {
                document.getElementById('ob-push-status').innerHTML =
                    '<i class="bi bi-check-circle-fill text-success"></i> Activadas!';
                btn.disabled    = true;
                btn.innerHTML   = '<i class="bi bi-bell-fill me-1"></i>Notificações activas';
                btn.classList.replace('btn-outline-secondary', 'btn-outline-success');
            } else {
                document.getElementById('ob-push-status').textContent = 'Podes activar nas definições do browser.';
                btn.disabled = true;
            }
        });
    };

    window.finishOnboarding = function () {
        const btn = document.getElementById('ob-finish');
        if (btn) {
            btn.disabled  = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>A guardar…';
        }
        fetch('onboarding_done', {
            method:      'POST',
            credentials: 'same-origin',
            headers:     { 'Content-Type': 'application/json' },
            body:        JSON.stringify({ csrf: csrfToken })
        })
        .then(r => r.json())
        .then(data => { if (data.ok) { bsModal.hide(); window.location.reload(); } else bsModal.hide(); })
        .catch(() => bsModal.hide());
    };
})();