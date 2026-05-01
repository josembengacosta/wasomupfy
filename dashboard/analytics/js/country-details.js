// ════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Detalhes por País JS
// Ficheiro: dashboard/analytics/js/country-details.js
// Depende de: HAS_ALBUMS, HAS_TRACKS, IS_WORLDWIDE,
//             MAP_LAT, MAP_LNG, MAP_HAS_COORDS,
//             COUNTRY_NAME, TOTAL_ALBUMS, MAP_COUNTRIES
// definidos inline em country-details.php antes deste script.
// ════════════════════════════════════════════════════

(function () {

    // ── Tooltips Bootstrap ────────────────────────
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // ── DataTable — Álbuns ────────────────────────
    if (HAS_ALBUMS) {
        $(document).ready(function () {
            if ($('#albumsTable').length) {
                $('#albumsTable').DataTable({
                    paging:       true,
                    searching:    true,
                    ordering:     true,
                    info:         true,
                    lengthChange: false,
                    pageLength:   10,
                    order:        [[5, 'desc']],
                    columnDefs: [
                        { orderable: false, targets: [0, 7] }
                    ],
                    language: {
                        search:   'Pesquisar álbum:',
                        info:     'A mostrar _START_ a _END_ de _TOTAL_ álbuns',
                        paginate: { next: 'Próximo', previous: 'Anterior' },
                        emptyTable: 'Nenhum álbum encontrado.'
                    }
                });
            }
        });
    }

    // ── DataTable — Faixas ────────────────────────
    if (HAS_TRACKS) {
        $(document).ready(function () {
            if ($('#tracksTable').length) {
                $('#tracksTable').DataTable({
                    paging:       true,
                    searching:    true,
                    ordering:     true,
                    info:         true,
                    lengthChange: false,
                    pageLength:   10,
                    order:        [[6, 'desc']],
                    columnDefs: [
                        { orderable: false, targets: [0] }
                    ],
                    language: {
                        search:   'Pesquisar faixa:',
                        info:     'A mostrar _START_ a _END_ de _TOTAL_ faixas',
                        paginate: { next: 'Próximo', previous: 'Anterior' },
                        emptyTable: 'Nenhuma faixa encontrada.'
                    }
                });
            }
        });
    }

    // ── Mapa Leaflet ──────────────────────────────
    var mapElement = document.getElementById('country-map');
    if (!mapElement) return;

    var map = null;
    var tileLayer = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    var tileAttr  = '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';

    if (!IS_WORLDWIDE && MAP_HAS_COORDS) {
        // ── País específico — marcador único ──────
        map = L.map('country-map', { zoomControl: true, scrollWheelZoom: false })
               .setView([MAP_LAT, MAP_LNG], 4);
        L.tileLayer(tileLayer, { attribution: tileAttr }).addTo(map);
        L.circleMarker([MAP_LAT, MAP_LNG], {
            color:       '#FF0089',
            fillColor:   '#FF0089',
            fillOpacity: 0.5,
            radius:      14
        }).addTo(map)
          .bindPopup(
              '<b>' + COUNTRY_NAME + '</b><br>' +
              TOTAL_ALBUMS + ' álbum' + (TOTAL_ALBUMS !== 1 ? 'ns' : '') + ' distribuídos'
          )
          .openPopup();

    } else if (IS_WORLDWIDE && MAP_COUNTRIES.length > 0) {
        // ── Worldwide — múltiplos marcadores ─────
        map = L.map('country-map', { zoomControl: true, scrollWheelZoom: false })
               .setView([20, 0], 2);
        L.tileLayer(tileLayer, { attribution: tileAttr }).addTo(map);
        MAP_COUNTRIES.forEach(function (c) {
            if (c.lat && c.lng) {
                var size = Math.min(20, 8 + Math.log(c.streams + 1) * 2);
                L.circleMarker([c.lat, c.lng], {
                    color:       '#FF0089',
                    fillColor:   '#FF0089',
                    fillOpacity: 0.5,
                    radius:      size
                }).addTo(map)
                  .bindPopup('<b>' + c.name + '</b><br>' + c.streams.toLocaleString() + ' streams');
            }
        });
    }

})();