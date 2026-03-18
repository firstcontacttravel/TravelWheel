
        (function(){
        'use strict';

        /* ── Current trip state (mirrors Livewire, but instant) ── */
        var fwTrip = '{{ $trip ?? 'OneWay' }}';

        /* ── Tab switching — instant, no server round-trip ── */
        window.fwSetTrip = function(trip) {
            fwTrip = trip;

            /* Update tab styles */
            ['oneway','return','multi'].forEach(function(t) {
                var tab = document.getElementById('fw-tab-' + t);
                if (tab) tab.classList.remove('fw-active');
            });
            var activeTab = document.getElementById('fw-tab-' + (trip === 'OneWay' ? 'oneway' : trip === 'Return' ? 'return' : 'multi'));
            if (activeTab) activeTab.classList.add('fw-active');

            /* Check correct radio */
            var radio = document.getElementById('fw-radio-' + (trip === 'OneWay' ? 'oneway' : trip === 'Return' ? 'return' : 'multi'));
            if (radio) radio.checked = true;

            /* Show/hide panels */
            var simple = document.getElementById('fw-panel-simple');
            var multi  = document.getElementById('fw-panel-multi');
            var retField = document.getElementById('fw-return-field');

            if (trip === 'multi') {
                if (simple) simple.style.display = 'none';
                if (multi)  multi.style.display  = 'block';
            } else {
                if (simple) simple.style.display = 'block';
                if (multi)  multi.style.display  = 'none';
                if (retField) retField.style.display = (trip === 'Return') ? 'flex' : 'none';
            }

            /* Close any open calendars/dropdowns */
            document.querySelectorAll('.fw-cal').forEach(function(c){ c.classList.remove('fw-open'); });
            fwClosePax();

            /* Sync to Livewire in background */
            fwSyncLivewireField('trip', trip);
        };

        /* ── Livewire sync — finds flight-search component specifically ── */
        function fwGetFlightComp() {
            if (!window.Livewire) return null;
            /* Walk all Livewire components and find flight-search */
            var all = Livewire.all ? Livewire.all() : [];
            for (var i = 0; i < all.length; i++) {
                var name = all[i].name || (all[i].__instance && all[i].__instance.name) || '';
                if (name === 'flight-search') return all[i];
            }
            /* Fallback: find the wire:id on the fw-card-outer element */
            var outer = document.querySelector('.fw-card-outer');
            if (!outer) return null;
            var root = outer.closest('[wire\\:id]') || outer.parentElement && outer.parentElement.closest('[wire\\:id]');
            if (!root) return null;
            return window.Livewire.find(root.getAttribute('wire:id'));
        }

        function fwSyncLivewireField(field, value) {
            var comp = fwGetFlightComp();
            if (comp) comp.set(field, value);
        }

        function fwSync(input, value) {
            var model = input.getAttribute('wire:model.defer') || input.getAttribute('wire:model');
            if (!model) return;
            var comp = fwGetFlightComp();
            if (comp) comp.set(model, value);
        }

        /* ── Airport Autocomplete ── */
        var airports = [];
        fetch('{{ asset('assets/data/airports.json') }}')
            .then(function(r){ return r.json(); })
            .then(function(d){ airports = d; console.log('[FW] ' + d.length + ' airports loaded'); })
            .catch(function(e){ console.error('[FW] airports.json:', e); });

        function fwSearch(q) {
            q = q.toLowerCase().trim();
            if (q.length < 2) return [];
            return airports.filter(function(a) {
                return (a.iata    && a.iata.toLowerCase().startsWith(q))  ||
                    (a.city    && a.city.toLowerCase().includes(q))    ||
                    (a.name    && a.name.toLowerCase().includes(q))    ||
                    (a.country && a.country.toLowerCase().includes(q));
            }).slice(0, 8);
        }

        function fwRenderAC(drop, results, input) {
            drop.innerHTML = '';
            if (!results.length) {
                drop.innerHTML = '<div class="fw-ac-empty">No airports found</div>';
            } else {
                results.forEach(function(a, i) {
                    var el = document.createElement('div');
                    el.className = 'fw-ac-item' + (i === 0 ? ' fw-hi' : '');
                    el.innerHTML = '<span class="fw-ac-iata">' + (a.iata || '—') + '</span>' +
                        '<span class="fw-ac-info"><span class="fw-ac-name">' + a.name + '</span>' +
                        '<span class="fw-ac-city">' + [a.city, a.country].filter(Boolean).join(', ') + '</span></span>';
                    el.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        var val = (a.city || a.name) + ' (' + a.iata + ')';
                        input.value = val;
                        fwSync(input, val);
                        drop.classList.remove('fw-open');
                    });
                    drop.appendChild(el);
                });
            }
            drop.classList.add('fw-open');
        }

        function fwAttachAC(input, drop) {
            if (!input || !drop || input.dataset.fwAc) return;
            input.dataset.fwAc = '1';
            input.addEventListener('input', function() {
                if (this.value.length < 2) { drop.classList.remove('fw-open'); return; }
                fwRenderAC(drop, fwSearch(this.value), input);
            });
            input.addEventListener('focus', function() {
                if (this.value.length >= 2) fwRenderAC(drop, fwSearch(this.value), input);
            });
            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !drop.contains(e.target))
                    drop.classList.remove('fw-open');
            });
        }

        function fwInitAC() {
            fwAttachAC(document.getElementById('fw-from'), document.getElementById('fw-drop-from'));
            fwAttachAC(document.getElementById('fw-to'),   document.getElementById('fw-drop-to'));
            document.querySelectorAll('[id^="fw-mfrom-"]').forEach(function(el) {
                fwAttachAC(el, document.getElementById('fw-mdrop-from-' + el.id.replace('fw-mfrom-', '')));
            });
            document.querySelectorAll('[id^="fw-mto-"]').forEach(function(el) {
                fwAttachAC(el, document.getElementById('fw-mdrop-to-' + el.id.replace('fw-mto-', '')));
            });
        }

        /* ── Pure JS Calendar ── */
        var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var DAYS   = ['Su','Mo','Tu','We','Th','Fr','Sa'];

        function fwBuildCal(input) {
            var wrap = input.closest('.fw-input-wrap');
            if (!wrap || wrap.querySelector('.fw-cal')) return; /* already built */

            var cal = document.createElement('div');
            cal.className = 'fw-cal';
            wrap.appendChild(cal);

            var state = { y: new Date().getFullYear(), m: new Date().getMonth() };

            function draw() {
                var today = new Date(); today.setHours(0,0,0,0);
                var selected = null;
                if (input.value) {
                    var p = input.value.split('/');
                    if (p.length === 3) selected = new Date(+p[2], +p[1]-1, +p[0]);
                }
                var startDow = new Date(state.y, state.m, 1).getDay();
                var lastDay  = new Date(state.y, state.m + 1, 0).getDate();

                var h = '<div class="fw-cal-header">' +
                    '<button class="fw-cal-nav fw-cal-prev">&#8249;</button>' +
                    '<span class="fw-cal-title">' + MONTHS[state.m] + ' ' + state.y + '</span>' +
                    '<button class="fw-cal-nav fw-cal-next">&#8250;</button>' +
                    '</div><div class="fw-cal-grid">';

                DAYS.forEach(function(d){ h += '<div class="fw-cal-dow">' + d + '</div>'; });
                for (var i = 0; i < startDow; i++) h += '<button class="fw-cal-day fw-empty" tabindex="-1"></button>';
                for (var d = 1; d <= lastDay; d++) {
                    var date = new Date(state.y, state.m, d);
                    var cls  = 'fw-cal-day';
                    if (date < today)  cls += ' fw-disabled';
                    if (date.toDateString() === today.toDateString())    cls += ' fw-today';
                    if (selected && date.toDateString() === selected.toDateString()) cls += ' fw-selected';
                    h += '<button class="' + cls + '" data-d="' + d + '">' + d + '</button>';
                }
                h += '</div>';
                cal.innerHTML = h;

                cal.querySelector('.fw-cal-prev').addEventListener('click', function(e) {
                    e.stopPropagation();
                    state.m--; if (state.m < 0) { state.m = 11; state.y--; } draw();
                });
                cal.querySelector('.fw-cal-next').addEventListener('click', function(e) {
                    e.stopPropagation();
                    state.m++; if (state.m > 11) { state.m = 0; state.y++; } draw();
                });
                cal.querySelectorAll('.fw-cal-day:not(.fw-disabled):not(.fw-empty)').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        var day = parseInt(this.dataset.d);
                        var val = String(day).padStart(2,'0') + '/' + String(state.m+1).padStart(2,'0') + '/' + state.y;
                        input.value = val;
                        fwSync(input, val);
                        cal.classList.remove('fw-open');
                    });
                });
            }

            input.addEventListener('click', function(e) {
                e.stopPropagation();
                var isOpen = cal.classList.contains('fw-open');
                document.querySelectorAll('.fw-cal').forEach(function(c){ c.classList.remove('fw-open'); });
                if (!isOpen) {
                    if (input.value) {
                        var p = input.value.split('/');
                        if (p.length === 3) { state.m = +p[1]-1; state.y = +p[2]; }
                    } else {
                        var n = new Date(); state.m = n.getMonth(); state.y = n.getFullYear();
                    }
                    draw();
                    cal.classList.add('fw-open');
                }
            });

            document.addEventListener('click', function(e) {
                if (!cal.contains(e.target) && e.target !== input)
                    cal.classList.remove('fw-open');
            });
        }

        function fwInitDP() {
            document.querySelectorAll('.fw-datepicker').forEach(function(el) {
                if (el.dataset.fwDp) return;
                el.dataset.fwDp = '1';
                fwBuildCal(el);
            });
        }

        /* ── Passenger dropdowns ── */
        window.fwClosePax = function() {
            document.querySelectorAll('.fw-pax-dropdown').forEach(function(d){ d.classList.remove('fw-open'); });
        };

        function fwInitPax() {
            ['fw-pax-trigger','fw-pax-trigger-m'].forEach(function(tid) {
                var t = document.getElementById(tid);
                var d = document.getElementById(tid.replace('trigger','dropdown'));
                if (!t || !d || t.dataset.fwPax) return;
                t.dataset.fwPax = '1';
                t.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var open = d.classList.contains('fw-open');
                    window.fwClosePax();
                    if (!open) d.classList.add('fw-open');
                });
            });
            if (!window._fwPaxDoc) {
                window._fwPaxDoc = true;
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.fw-pax-trigger') && !e.target.closest('.fw-pax-dropdown'))
                        window.fwClosePax();
                });
            }
        }

        /* ── Swap ── */
        function fwInitSwap() {
            var btn = document.getElementById('fw-swap-btn');
            if (!btn || btn.dataset.fwSwap) return;
            btn.dataset.fwSwap = '1';
            btn.addEventListener('click', function() {
                var f = document.getElementById('fw-from');
                var t = document.getElementById('fw-to');
                if (!f || !t) return;
                var tmp = f.value; f.value = t.value; t.value = tmp;
                fwSync(f, f.value); fwSync(t, t.value);
            });
        }

        /* ── Boot ── */
        function fwBoot() {
            fwInitAC();
            fwInitDP();
            fwInitPax();
            fwInitSwap();
            /* Apply correct initial state */
            fwSetTrip(fwTrip);
        }

        if (document.readyState === 'loading')
            document.addEventListener('DOMContentLoaded', fwBoot);
        else
            fwBoot();

        document.addEventListener('livewire:updated', fwBoot);

        })();
   