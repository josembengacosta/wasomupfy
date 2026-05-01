// ════════════════════════════════════════════════════
// WASOM UPFY v2.0 — Notificações JS
// Ficheiro: dashboard/page/js/notifications.jst.
// ════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', function () {

    // ── Bootstrap modais ──────────────────────────────
    const notifModal  = new bootstrap.Modal(document.getElementById('notificationModal'));
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const feedToast   = new bootstrap.Toast(document.getElementById('feedbackToast'), {
        delay: 3000
    });

    // ── Toast helper ──────────────────────────────────
    function toast(msg, isOk = true) {
        var toastEl = document.getElementById('feedbackToast');
        var msgEl   = document.getElementById('feedbackToastMsg');
        msgEl.textContent = msg;
        toastEl.style.background = isOk ? 'rgba(25,135,84,.95)' : 'rgba(220,53,69,.95)';
        toastEl.style.color = '#fff';
        feedToast.show();
    }

    // ── AJAX helper ───────────────────────────────────
    async function api(data) {
        data.csrf_token = CSRF_TOKEN;
        try {
            var res = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data),
                credentials: 'same-origin'
            });
            return await res.json();
        } catch (e) {
            return { ok: false, message: 'Erro de rede.' };
        }
    }

    // ── Filtros ───────────────────────────────────────
    document.querySelectorAll('.btn-filter').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn-filter').forEach(function (b) {
                b.classList.remove('active');
            });
            this.classList.add('active');
            var filter = this.dataset.filter;
            var cards  = document.querySelectorAll('.notification-card');
            var visible = 0;

            cards.forEach(function (card) {
                var show = filter === 'all' ||
                    (filter === 'unread' && card.dataset.read === '0') ||
                    card.dataset.type === filter;
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            // Esconder/mostrar group labels
            document.querySelectorAll('.group-label').forEach(function (lbl) {
                var next = lbl.nextElementSibling;
                var hasVisible = false;
                while (next && !next.classList.contains('group-label')) {
                    if (next.classList.contains('notification-card') && next.style.display !== 'none') {
                        hasVisible = true;
                        break;
                    }
                    next = next.nextElementSibling;
                }
                lbl.style.display = hasVisible ? '' : 'none';
            });

            document.getElementById('emptyState').style.display = visible === 0 ? 'block' : 'none';
        });
    });

    // ── Abrir modal ao clicar no card ─────────────────
    document.querySelectorAll('.notification-card').forEach(function (card) {
        card.addEventListener('click', function (e) {
            if (e.target.closest('.card-actions')) return;
            openNotifModal(card);
        });
    });

    function openNotifModal(card) {
        var id     = card.dataset.id;
        var source = card.dataset.source;
        var type   = card.dataset.type;
        var title  = card.dataset.title;
        var body   = card.dataset.body;
        var ago    = card.dataset.ago;
        var action = card.dataset.action;
        var isRead = card.dataset.read === '1';

        document.getElementById('modalNotifTitle').textContent = title;

        // Ícone no modal
        var iconMap = {
            info:      ['bi-info-circle-fill',         'icon-info'],
            success:   ['bi-check-circle-fill',        'icon-success'],
            warning:   ['bi-exclamation-triangle-fill','icon-warning'],
            error:     ['bi-x-circle-fill',            'icon-error'],
            payment:   ['bi-currency-dollar',          'icon-payment'],
            music:     ['bi-disc-fill',                'icon-music'],
            system:    ['bi-gear-fill',                'icon-system'],
            broadcast: ['bi-broadcast',                'icon-broadcast'],
        };
        var [ico, icoClass] = iconMap[type] || ['bi-bell-fill', 'icon-info'];

        document.getElementById('modalNotifBody').innerHTML =
            '<div class="text-center mb-3">' +
            '  <div class="modal-notif-icon ' + icoClass + ' mx-auto"><i class="bi ' + ico + '"></i></div>' +
            '</div>' +
            '<p class="text-muted small text-center mb-3"><i class="bi bi-clock me-1"></i>' + ago + '</p>' +
            '<p style="font-size:.9rem;line-height:1.7">' + body.replace(/\n/g, '<br>') + '</p>';

        // Botão toggle read
        document.getElementById('modalToggleBtn').innerHTML = isRead
            ? '<button class="btn btn-sm btn-outline-secondary" id="modalBtnToggleRead"><i class="bi bi-envelope me-1"></i>Marcar como não lida</button>'
            : '<button class="btn btn-sm btn-outline-secondary" id="modalBtnToggleRead"><i class="bi bi-check2 me-1"></i>Marcar como lida</button>';

        document.getElementById('modalBtnToggleRead').addEventListener('click', function () {
            if (isRead) {
                doMarkUnread(id, source, card);
            } else {
                doMarkRead(id, source, card);
            }
            notifModal.hide();
        });

        // Botões de acção dependendo do tipo
        var actionsHtml = '';
        if (action) {
            actionsHtml += '<a href="' + action +
                '" class="btn-action-primary btn"><i class="bi bi-box-arrow-up-right me-1"></i>Ver agora</a>';
        }
        if (type === 'payment' || type === 'music') {
            actionsHtml +=
                '<button class="btn-action-later btn" data-bs-dismiss="modal" id="modalBtnLater">Ver mais tarde</button>';
        }
        actionsHtml +=
            '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>';

        document.getElementById('modalActionBtns').innerHTML = actionsHtml;

        // "Ver mais tarde" — volta ao estado não lido
        var laterBtn = document.getElementById('modalBtnLater');
        if (laterBtn) {
            laterBtn.addEventListener('click', function () {
                doMarkUnread(id, source, card);
            });
        }

        // Ao abrir o modal, se não lida, marca como lida automaticamente
        if (!isRead) {
            doMarkRead(id, source, card);
        }

        notifModal.show();
    }

    // ── Marcar como lida ──────────────────────────────
    async function doMarkRead(id, source, card) {
        var r = await api({ action: 'mark_read', id: id, source: source });
        if (r.ok) {
            card.classList.remove('unread');
            card.dataset.read = '1';
            var tb = card.querySelector('.btn-toggle-read');
            if (tb) {
                tb.title = 'Marcar como não lida';
                tb.querySelector('i').className = 'bi bi-envelope';
            }
            updateCounts();
        }
    }

    // ── Marcar como não lida ──────────────────────────
    async function doMarkUnread(id, source, card) {
        var r = await api({ action: 'mark_unread', id: id, source: source });
        if (r.ok) {
            card.classList.add('unread');
            card.dataset.read = '0';
            var tb = card.querySelector('.btn-toggle-read');
            if (tb) {
                tb.title = 'Marcar como lida';
                tb.querySelector('i').className = 'bi bi-check-lg';
            }
            updateCounts();
        }
    }

    // ── Botões inline dos cards ───────────────────────
    document.querySelectorAll('.notification-card').forEach(function (card) {
        var id     = card.dataset.id;
        var source = card.dataset.source;

        // Toggle read/unread
        var tb = card.querySelector('.btn-toggle-read');
        if (tb) {
            tb.addEventListener('click', function (e) {
                e.stopPropagation();
                if (card.dataset.read === '0') {
                    doMarkRead(id, source, card);
                } else {
                    doMarkUnread(id, source, card);
                }
            });
        }

        // Delete
        var db = card.querySelector('.btn-delete');
        if (db) {
            db.addEventListener('click', function (e) {
                e.stopPropagation();
                confirmAction('Eliminar esta notificação?', async function () {
                    var r = await api({ action: 'delete_one', id: id, source: source });
                    if (r.ok) {
                        card.style.transition = 'opacity .25s';
                        card.style.opacity    = '0';
                        setTimeout(function () {
                            card.remove();
                            updateCounts();
                        }, 260);
                        toast('Notificação eliminada.');
                    } else {
                        toast(r.message, false);
                    }
                });
            });
        }
    });

    // ── Marcar todas como lidas ───────────────────────
    document.getElementById('btnMarkAll').addEventListener('click', async function () {
        var r = await api({ action: 'mark_all_read' });
        if (r.ok) {
            document.querySelectorAll('.notification-card.unread').forEach(function (c) {
                c.classList.remove('unread');
                c.dataset.read = '1';
                var tb = c.querySelector('.btn-toggle-read');
                if (tb) {
                    tb.title = 'Marcar como não lida';
                    tb.querySelector('i').className = 'bi bi-envelope';
                }
            });
            updateCounts();
            toast('Todas as notificações marcadas como lidas.');
        } else {
            toast(r.message, false);
        }
    });

    // ── Limpar todas ──────────────────────────────────
    document.getElementById('btnDeleteAll').addEventListener('click', function () {
        confirmAction('Eliminar todas as notificações? Esta acção não pode ser desfeita.',
            async function () {
                var r = await api({ action: 'delete_all' });
                if (r.ok) {
                    document.querySelectorAll('.notification-card').forEach(function (c) { c.remove(); });
                    document.querySelectorAll('.group-label').forEach(function (g) { g.remove(); });
                    document.getElementById('emptyState').style.display = 'block';
                    updateCounts();
                    toast('Todas as notificações eliminadas.');
                } else {
                    toast(r.message, false);
                }
            });
    });

    // ── Actualizar ────────────────────────────────────
    document.getElementById('btnRefresh').addEventListener('click', function () {
        location.reload();
    });

    // ── Helper confirmação ────────────────────────────
    function confirmAction(msg, cb) {
        document.getElementById('confirmMsg').textContent = msg;
        var btn    = document.getElementById('confirmOkBtn');
        var newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        newBtn.addEventListener('click', function () {
            confirmModal.hide();
            cb();
        });
        confirmModal.show();
    }

    // ── Actualizar contadores ─────────────────────────
    function updateCounts() {
        var cards   = document.querySelectorAll('.notification-card');
        var unread  = document.querySelectorAll('.notification-card.unread');
        var total   = cards.length;
        var unrdCnt = unread.length;
        var rdCnt   = total - unrdCnt;

        document.getElementById('statTotal').textContent   = total;
        document.getElementById('statUnread').textContent  = unrdCnt;
        document.getElementById('statRead').textContent    = rdCnt;
        document.getElementById('countAll').textContent    = total;
        document.getElementById('countUnread').textContent = unrdCnt;

        // Barra de progresso lidas
        var pct  = total > 0 ? Math.round(rdCnt / total * 100) : 0;
        var fill = document.getElementById('ratioFill');
        if (fill) fill.style.width = pct + '%';

        // Badge navbar
        var badge = document.getElementById('navBadge');
        if (badge) {
            if (unrdCnt > 0) {
                badge.textContent   = unrdCnt > 99 ? '99+' : unrdCnt;
                badge.style.display = '';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    // ── Guardar preferências ──────────────────────────
    document.getElementById('btnSavePrefs').addEventListener('click', async function () {
        var prefs = {};
        document.querySelectorAll('.pref-switch').forEach(function (sw) {
            prefs[sw.dataset.pref] = sw.checked ? 1 : 0;
        });
        var r = await api(Object.assign({ action: 'save_prefs' }, prefs));
        if (r.ok) {
            toast('Preferências guardadas!');
        } else {
            toast(r.message, false);
        }
    });

    // ── Push Notifications (Web Push API) ────────────
    var pushCard = document.getElementById('pushCard');
    var btnPush  = document.getElementById('btnEnablePush');
    var pushStat = document.getElementById('pushStatus');

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = window.atob(base64);
        var output  = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) output[i] = rawData.charCodeAt(i);
        return output;
    }

    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.register('../../sw-wasomupfy.js')
            .then(function (reg) {
                if (Notification.permission === 'granted') {
                    pushCard.style.display = 'none';
                }

                btnPush.addEventListener('click', async function () {
                    if (!VAPID_PUBLIC_KEY) {
                        pushStat.style.display  = '';
                        pushStat.textContent    = 'VAPID key não configurada no servidor.';
                        return;
                    }
                    try {
                        var permission = await Notification.requestPermission();
                        if (permission !== 'granted') {
                            pushStat.style.display = '';
                            pushStat.textContent   = 'Permissão negada pelo browser.';
                            return;
                        }
                        var subscription = await reg.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
                        });
                        var r = await api({
                            action: 'subscribe_push',
                            subscription: JSON.stringify(subscription)
                        });
                        if (r.ok) {
                            pushCard.style.display = 'none';
                            toast('Notificações push activadas!');
                            document.getElementById('prefPush').checked = true;
                        } else {
                            pushStat.style.display = '';
                            pushStat.textContent   = r.message;
                        }
                    } catch (err) {
                        pushStat.style.display = '';
                        pushStat.textContent   = 'Erro: ' + err.message;
                    }
                });
            })
            .catch(function () {
                pushCard.style.display = 'none';
            });
    } else {
        pushCard.style.display = 'none';
    }

    // ── Polling do badge (a cada 30s) ─────────────────
    async function pollBadge() {
        try {
            var r = await api({ action: 'get_count' });
            if (r.ok) {
                var badge = document.getElementById('navBadge');
                if (badge) {
                    if (r.count > 0) {
                        badge.textContent   = r.count > 99 ? '99+' : r.count;
                        badge.style.display = '';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        } catch (e) {}
    }
    setInterval(pollBadge, 30000);

}); // fim DOMContentLoaded