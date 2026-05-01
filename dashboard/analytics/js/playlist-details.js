// ════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Detalhes de Playlist JS
// Ficheiro: dashboard/analytics/js/playlist-details.js
// Depende de: HAS_TRACKS
// definido inline em playlist-details.php antes deste script.
// ════════════════════════════════════════════════════

// ── Tooltips Bootstrap ────────────────────────────
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    new bootstrap.Tooltip(el);
});

// ── DataTable — Top Faixas ────────────────────────
if (HAS_TRACKS) {
    $(document).ready(function () {
        $('#tracksTable').DataTable({
            paging:       true,
            searching:    true,
            ordering:     true,
            info:         true,
            lengthChange: false,
            pageLength:   10,
            order:        [[6, 'desc']], // streams DESC
            columnDefs: [
                { orderable: false, targets: [0, 1] },
                { type: 'num-fmt',  targets: [6, 7] }
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