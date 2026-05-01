// ════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Relatórios Financeiros JS
// Ficheiro: dashboard/analytics/js/report.js
// Depende de: HAS_REPORTS
// definido inline em report.php antes deste script.
// ════════════════════════════════════════════════════

// ── Tooltips Bootstrap ────────────────────────────
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    new bootstrap.Tooltip(el);
});

// ── DataTable — Relatórios mensais ────────────────
if (HAS_REPORTS) {
    $(document).ready(function () {
        $('#reportsWasomupfy').DataTable({
            paging:       true,
            searching:    true,
            ordering:     true,
            info:         true,
            lengthChange: false,
            pageLength:   10,
            order:        [[1, 'desc'], [0, 'desc']],
            columnDefs: [
                { orderable: false, targets: 6 }
            ],
            language: {
                search:   'Pesquisar por mês ou ano:',
                info:     'A mostrar _START_ a _END_ de _TOTAL_ relatórios',
                paginate: { next: 'Próximo', previous: 'Anterior' },
                emptyTable: 'Nenhum relatório disponível.'
            }
        });
    });
}