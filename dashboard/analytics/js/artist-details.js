// ════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Detalhes do Artista JS
// Ficheiro: dashboard/analytics/js/artist-details.js
// Depende de: BASE_URL, CHART_LABELS, CHART_DATASETS,
//             HAS_TRACKS, HAS_CHART
// definidos inline em artist-details.php antes deste script.
// ════════════════════════════════════════════════════

// ── Badge polling ──────────────────────────────────
(function () {
    function refreshBadge() {
        fetch(BASE_URL + '/ajax/notifications_api?action=count', {
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var b = document.getElementById('navNotifBadge');
                if (!b) return;
                var n = parseInt(data.unread || 0);
                b.textContent   = n > 99 ? '99+' : n;
                b.style.display = n > 0 ? '' : 'none';
            })
            .catch(function () {});
    }
    setTimeout(function () {
        refreshBadge();
        setInterval(refreshBadge, 60000);
    }, 30000);
})();

// ── Tooltips Bootstrap ────────────────────────────
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    new bootstrap.Tooltip(el);
});

// ── DataTable das faixas ──────────────────────────
if (HAS_TRACKS) {
    $(document).ready(function () {
        $('#tracksTable').DataTable({
            paging:       true,
            searching:    true,
            ordering:     true,
            info:         true,
            lengthChange: false,
            pageLength:   10,
            order:        [[4, 'desc']], // ordenar por streams DESC
            columnDefs: [
                { orderable: false, targets: [0] },
                { type: 'num-fmt',  targets: [4, 5, 6] }
            ],
            language: {
                search:   'Pesquisar faixa:',
                info:     'A mostrar _START_ a _END_ de _TOTAL_ faixas',
                paginate: { next: 'Próximo', previous: 'Anterior' },
                emptyTable: 'Nenhuma faixa encontrada.'
            }
        });
    });
}

// ── Gráfico Chart.js ──────────────────────────────
if (HAS_CHART) {
    var ctx = document.getElementById('streamChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels:   CHART_LABELS,
            datasets: CHART_DATASETS
        },
        options: {
            responsive:          true,
            maintainAspectRatio: true,
            interaction: {
                mode:      'index',
                intersect: false
            },
            scales: {
                y: {
                    beginAtZero: true,
                    stacked:     true,
                    title: { display: true, text: 'Streams' }
                },
                x: {
                    stacked: true,
                    title:   { display: true, text: 'Mês' }
                }
            },
            plugins: {
                legend:  { position: 'top' },
                tooltip: { mode: 'index', intersect: false }
            }
        }
    });
}