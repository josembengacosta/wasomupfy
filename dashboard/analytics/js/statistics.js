// ════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Estatísticas JS
// Ficheiro: dashboard/js/statistics.js
// Depende de: HAS_CHART, HAS_COUNTRIES, CHART_LABELS,
//             CHART_DATASETS
// definidos inline em statistics.php antes deste script.
// ════════════════════════════════════════════════════

// ── Tooltips Bootstrap ────────────────────────────
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
  new bootstrap.Tooltip(el);
});

// ── Chart.js — Streams por mês/plataforma ─────────
if (HAS_CHART) {
  var ctx = document.getElementById("streamChart").getContext("2d");
  var streamChart = new Chart(ctx, {
    type: "line",
    data: {
      labels: CHART_LABELS,
      datasets: CHART_DATASETS,
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      interaction: {
        mode: "index",
        intersect: false,
      },
      scales: {
        y: {
          beginAtZero: true,
          stacked: true,
          title: { display: true, text: "Streams" },
        },
        x: {
          stacked: true,
          title: { display: true, text: "Mês" },
        },
      },
      plugins: {
        legend: { position: "top" },
        tooltip: { mode: "index", intersect: false },
      },
    },
  });
}

// ── DataTable — Países ────────────────────────────
if (HAS_COUNTRIES) {
  $(document).ready(function () {
    $("#countriesTable").DataTable({
      paging: true,
      searching: true,
      ordering: true,
      info: true,
      lengthChange: false,
      pageLength: 10,
      order: [[3, "desc"]], // ordenar por streams (coluna 3)
      columnDefs: [
        { orderable: false, targets: [0, 5] }, // bandeira e detalhes não ordenáveis
      ],
      language: {
        search: "Pesquisar país:",
        info: "A mostrar _START_ a _END_ de _TOTAL_ países",
        paginate: { next: "Próximo", previous: "Anterior" },
        emptyTable: "Nenhum país com streams.",
      },
    });
  });
}
