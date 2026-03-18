/* ============================================================
   FLIGHT WIDGET — public/assets/js/flight-widget.js
============================================================ */
(function () {
    'use strict';

    var AIRPORTS_URL = '/assets/data/airports.json';
    var airports = [];

    /* ── Load airports JSON once ── */
    fetch(AIRPORTS_URL)
        .then(function (r) { return r.json(); })
        .then(function (d) {
            airports = d;
            console.log('[FW] Loaded ' + d.length + ' airports');
        })
        .catch(function (e) {
            console.error('[FW] Could not load airports JSON:', e);
        });

    /* ── Search ── */
    function fwSearch(q) {
        q = q.toLowerCase().trim();
        if (q.length < 2) return [];
        return airports.filter(function (a) {
            return (a.iata    && a.iata.toLowerCase().startsWith(q))   ||
                   (a.city    && a.city.toLowerCase().includes(q))     ||
                   (a.name    && a.name.toLowerCase().includes(q))     ||
                   (a.country && a.country.toLowerCase().includes(q));
        }).slice(0, 8);
    }

    /* ── Render dropdown ── */
    function fwRender(drop, results, input) {
        drop.innerHTML = '';
        if (!results.length) {
            drop.innerHTML = '<div class="fw-ac-empty">No airports found</div>';
        } else {
            results.forEach(function (a, i) {
                var el = document.createElement('div');
                el.className = 'fw-ac-item' + (i === 0 ? ' fw-hi' : '');
                el.innerHTML =
                    '<span class="fw-ac-iata">' + (a.iata || '—') + '</span>' +
                    '<span class="fw-ac-info">' +
                        '<span class="fw-ac-name">' + a.name + '</span>' +
                        '<span class="fw-ac-city">' + [a.city, a.country].filter(Boolean).join(', ') + '</span>' +
                    '</span>';
                el.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    var val = (a.city || a.name) + ' (' + a.iata + ')';
                    input.value = val;
                    fwSyncLivewire(input, val);
                    drop.classList.remove('fw-open');
                });
                drop.appendChild(el);
            });
        }
        drop.classList.add('fw-open');
    }

    /* ── Sync value to Livewire ── */
    function fwSyncLivewire(input, value) {
        var model = input.getAttribute('wire:model.defer') || input.getAttribute('wire:model');
        if (!model || !window.Livewire) return;
        var root = input.closest('[wire\\:id]');
        if (!root) return;
        var comp = window.Livewire.find(root.getAttribute('wire:id'));
        if (comp) comp.set(model, value);
    }

    /* ── Attach autocomplete to one input/dropdown pair ── */
    function fwAttachAC(input, drop) {
        if (!input || !drop || input.dataset.fwAc) return;
        input.dataset.fwAc = '1';

        input.addEventListener('input', function () {
            if (this.value.length < 2) { drop.classList.remove('fw-open'); return; }
            fwRender(drop, fwSearch(this.value), input);
        });

        input.addEventListener('focus', function () {
            if (this.value.length >= 2) fwRender(drop, fwSearch(this.value), input);
        });

        document.addEventListener('click', function (e) {
            if (!input.contains(e.target) && !drop.contains(e.target))
                drop.classList.remove('fw-open');
        });
    }

    /* ── Init all airport inputs ── */
    function fwInitAC() {
        var from = document.getElementById('fw-from');
        var to   = document.getElementById('fw-to');
        if (from) fwAttachAC(from, document.getElementById('fw-drop-from'));
        if (to)   fwAttachAC(to,   document.getElementById('fw-drop-to'));

        document.querySelectorAll('[id^="fw-mfrom-"]').forEach(function (el) {
            fwAttachAC(el, document.getElementById('fw-mdrop-from-' + el.id.replace('fw-mfrom-', '')));
        });
        document.querySelectorAll('[id^="fw-mto-"]').forEach(function (el) {
            fwAttachAC(el, document.getElementById('fw-mdrop-to-' + el.id.replace('fw-mto-', '')));
        });
    }

    /* ── Datepicker ── */
    function fwInitDP() {
        if (typeof $ === 'undefined' || !$.fn || !$.fn.datepicker) return;
        document.querySelectorAll('.fw-datepicker').forEach(function (el) {
            if (el.dataset.fwDp) return;
            el.dataset.fwDp = '1';
            $(el).datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true,
                startDate: new Date()
            }).on('changeDate', function () {
                fwSyncLivewire(el, el.value);
            });
        });
    }

    /* ── Passenger dropdowns ── */
    window.fwClosePax = function () {
        document.querySelectorAll('.fw-pax-dropdown').forEach(function (d) {
            d.classList.remove('fw-open');
        });
    };

    function fwInitPax() {
        ['fw-pax-trigger', 'fw-pax-trigger-m'].forEach(function (tid) {
            var trigger = document.getElementById(tid);
            var drop    = document.getElementById(tid.replace('trigger', 'dropdown'));
            if (!trigger || !drop || trigger.dataset.fwPax) return;
            trigger.dataset.fwPax = '1';
            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                var isOpen = drop.classList.contains('fw-open');
                window.fwClosePax();
                if (!isOpen) drop.classList.add('fw-open');
            });
        });
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.fw-pax-trigger') && !e.target.closest('.fw-pax-dropdown'))
                window.fwClosePax();
        });
    }

    /* ── Swap airports ── */
    function fwInitSwap() {
        var btn = document.getElementById('fw-swap-btn');
        if (!btn || btn.dataset.fwSwap) return;
        btn.dataset.fwSwap = '1';
        btn.addEventListener('click', function () {
            var f = document.getElementById('fw-from');
            var t = document.getElementById('fw-to');
            if (!f || !t) return;
            var tmp = f.value; f.value = t.value; t.value = tmp;
            fwSyncLivewire(f, f.value);
            fwSyncLivewire(t, t.value);
        });
    }

    /* ── Boot ── */
    function fwBoot() {
        fwInitAC();
        fwInitDP();
        fwInitPax();
        fwInitSwap();
    }

    if (document.readyState === 'loading')
        document.addEventListener('DOMContentLoaded', fwBoot);
    else
        fwBoot();

    document.addEventListener('livewire:updated', fwBoot);

})();