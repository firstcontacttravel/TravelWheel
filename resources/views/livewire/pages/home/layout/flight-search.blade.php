<style>
    .fw-card-outer{width:100%;min-height:360px;display:flex;align-items:center;justify-content:center;padding:150px 30px;background:url('{{ asset('assets/image/slide.jpg') }}') center center/cover no-repeat;position:relative;box-sizing:border-box}
    .fw-card-outer::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(10,25,70,.65) 0%,rgba(0,0,0,.4) 100%);pointer-events:none}
    .fw-card{position:relative;z-index:2;width:100%;max-width:1180px;margin:0 auto;background:#fff;border-radius:18px;padding:30px 32px 26px;box-shadow:0 20px 70px rgba(0,0,0,.22),0 4px 18px rgba(0,0,0,.1);box-sizing:border-box}
    .fw-tabs{display:flex;gap:6px;margin-bottom:22px;flex-wrap:wrap}
    .fw-tab{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:999px;font-size:13px;font-weight:500;cursor:pointer;color:#6b7280;background:#f3f4f6;border:1.5px solid transparent;transition:all .2s;user-select:none;line-height:1;margin:0}
    .fw-tab:hover{background:#e5e7eb;color:#374151}
    .fw-tab.fw-active{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;font-weight:600}
    .fw-row{display:flex;align-items:flex-end;gap:10px;flex-wrap:nowrap}
    .fw-field{display:flex;flex-direction:column;min-width:0;flex:1}
    .fw-field-2x{flex:2!important}.fw-field-15x{flex:1.5!important}.fw-field-12x{flex:1.2!important}
    .fw-label{display:flex;align-items:center;gap:5px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#9ca3af;margin-bottom:7px;white-space:nowrap}
    .fw-input-wrap{position:relative}
    .fw-input{width:100%;height:48px;padding:0 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;color:#111827;background:#f9fafb;outline:none;box-sizing:border-box;transition:border-color .2s,box-shadow .2s,background .2s;-webkit-appearance:none;appearance:none;font-family:inherit;display:flex;align-items:center}
    .fw-input:focus{border-color:#3b82f6;background:#fff;box-shadow:0 0 0 3px rgba(59,130,246,.14)}
    .fw-input::placeholder{color:#d1d5db}
    .fw-select{cursor:pointer;padding-right:34px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center}
    .fw-error{font-size:11px;color:#ef4444;margin-top:4px}
    .fw-swap{width:40px;height:40px;min-width:40px;border-radius:50%;border:1.5px solid #e5e7eb;background:#fff;color:#6b7280;display:flex;align-items:center;justify-content:center;cursor:pointer;margin-bottom:4px;flex-shrink:0;transition:all .25s;padding:0}
    .fw-swap:hover{background:#eff6ff;border-color:#93c5fd;color:#1d4ed8;transform:rotate(180deg)}
    .fw-ac-dropdown{display:none;position:absolute;top:calc(100% + 6px);left:0;right:0;background:#fff;border:1.5px solid #e5e7eb;border-radius:12px;box-shadow:0 16px 48px rgba(0,0,0,.14);z-index:99999;overflow:hidden;max-height:260px;overflow-y:auto}
    .fw-ac-dropdown.fw-open{display:block}
    .fw-ac-item{display:flex;align-items:center;gap:12px;padding:11px 14px;cursor:pointer;border-bottom:1px solid #f3f4f6;transition:background .12s}
    .fw-ac-item:last-child{border-bottom:none}
    .fw-ac-item:hover,.fw-ac-item.fw-hi{background:#eff6ff}
    .fw-ac-iata{font-size:13px;font-weight:700;color:#1d4ed8;min-width:36px;font-family:'Courier New',monospace}
    .fw-ac-info{display:flex;flex-direction:column;overflow:hidden}
    .fw-ac-name{font-size:13px;font-weight:500;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .fw-ac-city{font-size:11px;color:#9ca3af}
    .fw-ac-empty{padding:14px;font-size:13px;color:#9ca3af;text-align:center}
    .fw-pax-trigger{display:flex;align-items:center;gap:8px;cursor:pointer;user-select:none}
    .fw-pax-dropdown{display:none;position:absolute;top:calc(100% + 6px);left:0;min-width:265px;background:#fff;border:1.5px solid #e5e7eb;border-radius:14px;box-shadow:0 16px 48px rgba(0,0,0,.14);z-index:99998;overflow:hidden}
    .fw-pax-dropdown.fw-open{display:block}
    .fw-pax-row{display:flex;align-items:center;justify-content:space-between;padding:13px 16px;border-bottom:1px solid #f3f4f6}
    .fw-pax-row:last-of-type{border-bottom:none}
    .fw-pax-lbl{font-size:13px;font-weight:600;color:#111827}
    .fw-pax-sub{font-size:11px;color:#9ca3af;margin-top:2px}
    .fw-pax-ctr{display:flex;align-items:center;gap:10px}
    .fw-pax-btn{width:30px;height:30px;border-radius:50%;border:1.5px solid #e5e7eb;background:#fff;font-size:18px;line-height:1;color:#374151;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;padding:0;font-family:inherit}
    .fw-pax-btn:hover{background:#eff6ff;border-color:#3b82f6;color:#1d4ed8}
    .fw-pax-num{font-size:14px;font-weight:700;color:#111827;min-width:20px;text-align:center}
    .fw-pax-done{display:block;width:calc(100% - 32px);margin:10px 16px;padding:9px;background:#1d4ed8;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:background .2s;font-family:inherit}
    .fw-pax-done:hover{background:#1e40af}
    .fw-multi-legs{display:flex;flex-direction:column;gap:12px}
    .fw-leg{display:flex;align-items:flex-end;gap:10px;padding:16px 18px;background:#f9fafb;border-radius:12px;border:1.5px solid #f0f0f0;flex-wrap:nowrap}
    .fw-leg-badge{width:26px;height:26px;background:#1d4ed8;color:#fff;font-size:12px;font-weight:700;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-bottom:5px}
    .fw-remove-btn{width:34px;height:34px;border-radius:50%;border:1.5px solid #fca5a5;background:#fff;color:#ef4444;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-bottom:5px;padding:0;transition:background .15s}
    .fw-remove-btn:hover{background:#fef2f2}
    .fw-add-leg-btn{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border:1.5px dashed #93c5fd;border-radius:10px;background:#eff6ff;color:#1d4ed8;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit}
    .fw-add-leg-btn:hover{background:#dbeafe;border-color:#3b82f6}
    .fw-search-row{display:flex;align-items:center;justify-content:flex-end;margin-top:22px;gap:14px}
    .fw-search-btn{display:inline-flex;align-items:center;gap:9px;padding:0 34px;height:52px;background:linear-gradient(135deg,#1d4ed8 0%,#2563eb 100%);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;box-shadow:0 4px 22px rgba(29,78,216,.38);transition:all .2s;letter-spacing:.01em;font-family:inherit;white-space:nowrap}
    .fw-search-btn:hover{background:linear-gradient(135deg,#1e40af 0%,#1d4ed8 100%);box-shadow:0 6px 30px rgba(29,78,216,.48);transform:translateY(-1px)}
    .fw-search-btn:active{transform:translateY(0)}
    /* Calendar */
    .fw-cal{display:none;position:absolute;top:calc(100% + 6px);left:0;background:#fff;border:1.5px solid #e5e7eb;border-radius:14px;box-shadow:0 16px 48px rgba(0,0,0,.15);z-index:99997;width:300px;padding:16px;box-sizing:border-box;font-family:inherit}
    .fw-cal.fw-open{display:block}
    .fw-cal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
    .fw-cal-title{font-size:14px;font-weight:700;color:#111827}
    .fw-cal-nav{width:28px;height:28px;border-radius:50%;border:1.5px solid #e5e7eb;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6b7280;padding:0;font-size:16px;line-height:1;transition:all .15s}
    .fw-cal-nav:hover{background:#eff6ff;border-color:#3b82f6;color:#1d4ed8}
    .fw-cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:2px}
    .fw-cal-dow{font-size:10px;font-weight:700;text-transform:uppercase;color:#9ca3af;text-align:center;padding:4px 0}
    .fw-cal-day{height:34px;border-radius:8px;border:none;background:none;font-size:13px;color:#111827;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;font-family:inherit;width:100%;padding:0}
    .fw-cal-day:hover:not(.fw-disabled):not(.fw-empty){background:#eff6ff;color:#1d4ed8}
    .fw-cal-day.fw-today{font-weight:700;color:#1d4ed8}
    .fw-cal-day.fw-selected{background:#1d4ed8!important;color:#fff!important;font-weight:700}
    .fw-cal-day.fw-disabled{color:#d1d5db;cursor:not-allowed}
    .fw-cal-day.fw-empty{visibility:hidden;pointer-events:none}
    /* Responsive */
    @media(max-width:960px){.fw-card{padding:24px 20px 20px}.fw-row{flex-wrap:wrap;gap:8px}.fw-field,.fw-field-2x,.fw-field-15x,.fw-field-12x{flex:1 1 calc(50% - 8px)!important;min-width:calc(50% - 8px)}.fw-swap{display:none!important}}
    @media(max-width:580px){.fw-card-outer{padding:20px 14px}.fw-card{padding:20px 16px;border-radius:14px}.fw-field,.fw-field-2x,.fw-field-15x,.fw-field-12x{flex:1 1 100%!important;min-width:100%}.fw-leg{flex-wrap:wrap}.fw-search-btn{width:100%;justify-content:center}.fw-search-row{margin-top:14px}.fw-tab{padding:7px 13px;font-size:12px}}
    .upper-space{margin-top:30px;}
    @media(max-width:650px){.upper-space{margin-top:0px;}}
</style>

{{--
    Flight Search Widget — Alpine.js (no Livewire)
    All state is managed client-side with Alpine.js x-data.
    On search, a standard form POST is submitted to the server.
--}}

<div
    class="fw-card-outer"
    x-data="flightWidget()"
    x-init="init()"
    @click.outside="closePax(); closeAllCals()"
>
    <div class="fw-card">

        {{-- ── Trip Type Tabs + Multi-city global passengers ── --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:22px;flex-wrap:wrap;">
            <div class="fw-tabs" style="margin-bottom:0;flex:1;min-width:0;">
                <button type="button"
                    class="fw-tab"
                    :class="{ 'fw-active': trip === 'OneWay' }"
                    @click="trip = 'OneWay'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    One Way
                </button>
                <button type="button"
                    class="fw-tab"
                    :class="{ 'fw-active': trip === 'Return' }"
                    @click="trip = 'Return'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                    Round Trip
                </button>
                <button type="button"
                    class="fw-tab"
                    :class="{ 'fw-active': trip === 'multi' }"
                    @click="trip = 'multi'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    Multi City
                </button>
            </div>

            {{-- Global passenger selector — visible only when Multi City is active --}}
            <div x-show="trip === 'multi'" x-transition style="position:relative;flex-shrink:0;">
                <div
                    class="fw-tab fw-active"
                    style="cursor:pointer;gap:8px;padding:8px 14px;border-radius:999px;border-color:#bfdbfe;"
                    @click.stop="paxOpen = !paxOpen"
                >
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span x-text="(adults + childs + kids) + ' Passenger' + (adults + childs + kids > 1 ? 's' : '')"></span>
                    <svg style="opacity:.5" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="fw-pax-dropdown" :class="{ 'fw-open': paxOpen }" @click.stop style="left:auto;right:0;">
                    <div class="fw-pax-row">
                        <div><div class="fw-pax-lbl">Adults</div><div class="fw-pax-sub">12+ yrs</div></div>
                        <div class="fw-pax-ctr">
                            <button type="button" class="fw-pax-btn" @click="adults = Math.max(1, adults - 1)">−</button>
                            <span class="fw-pax-num" x-text="adults"></span>
                            <button type="button" class="fw-pax-btn" @click="adults++">+</button>
                        </div>
                    </div>
                    <div class="fw-pax-row">
                        <div><div class="fw-pax-lbl">Children</div><div class="fw-pax-sub">2–11 yrs</div></div>
                        <div class="fw-pax-ctr">
                            <button type="button" class="fw-pax-btn" @click="childs = Math.max(0, childs - 1)">−</button>
                            <span class="fw-pax-num" x-text="childs"></span>
                            <button type="button" class="fw-pax-btn" @click="childs++">+</button>
                        </div>
                    </div>
                    <div class="fw-pax-row">
                        <div><div class="fw-pax-lbl">Infants</div><div class="fw-pax-sub">Under 2</div></div>
                        <div class="fw-pax-ctr">
                            <button type="button" class="fw-pax-btn" @click="kids = Math.max(0, kids - 1)">−</button>
                            <span class="fw-pax-num" x-text="kids"></span>
                            <button type="button" class="fw-pax-btn" @click="kids++">+</button>
                        </div>
                    </div>
                    <button type="button" class="fw-pax-done" @click="paxOpen = false">Done</button>
                </div>
            </div>
        </div>

        {{-- ── Simple Panel: One Way + Return ── --}}
        <div x-show="trip !== 'multi'" x-transition>
            <div class="fw-row">

                {{-- From --}}
                <div class="fw-field fw-field-2x" style="position:relative;">
                    <div class="fw-label">Flying From</div>
                    <div class="fw-input-wrap">
                        <input
                            class="fw-input"
                            type="text"
                            placeholder="City or airport"
                            x-model="from"
                            @input="searchAirport($event.target.value, 'fromResults')"
                            @focus="searchAirport(from, 'fromResults')"
                            @click.stop
                            autocomplete="off"
                        >
                        {{-- Autocomplete dropdown --}}
                        <div class="fw-ac-dropdown" :class="{ 'fw-open': fromResults.length && fromFocus }" @click.stop>
                            <template x-for="(a, i) in fromResults" :key="a.iata">
                                <div class="fw-ac-item" :class="{ 'fw-hi': i === 0 }" @mousedown.prevent="selectAirport('from', a)">
                                    <span class="fw-ac-iata" x-text="a.iata"></span>
                                    <span class="fw-ac-info">
                                        <span class="fw-ac-name" x-text="a.name"></span>
                                        <span class="fw-ac-city" x-text="[a.city, a.country].filter(Boolean).join(', ')"></span>
                                    </span>
                                </div>
                            </template>
                            <div x-show="fromResults.length === 0 && from.length >= 2" class="fw-ac-empty">No airports found</div>
                        </div>
                    </div>
                    <span x-show="errors.from" class="fw-error" x-text="errors.from"></span>
                </div>

                {{-- Swap button --}}
                <button class="fw-swap" type="button" @click="swapRoutes">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                </button>

                {{-- To --}}
                <div class="fw-field fw-field-2x" style="position:relative;">
                    <div class="fw-label">Flying To</div>
                    <div class="fw-input-wrap">
                        <input
                            class="fw-input"
                            type="text"
                            placeholder="City or airport"
                            x-model="to"
                            @input="searchAirport($event.target.value, 'toResults')"
                            @focus="searchAirport(to, 'toResults')"
                            @click.stop
                            autocomplete="off"
                        >
                        <div class="fw-ac-dropdown" :class="{ 'fw-open': toResults.length && toFocus }" @click.stop>
                            <template x-for="(a, i) in toResults" :key="a.iata">
                                <div class="fw-ac-item" :class="{ 'fw-hi': i === 0 }" @mousedown.prevent="selectAirport('to', a)">
                                    <span class="fw-ac-iata" x-text="a.iata"></span>
                                    <span class="fw-ac-info">
                                        <span class="fw-ac-name" x-text="a.name"></span>
                                        <span class="fw-ac-city" x-text="[a.city, a.country].filter(Boolean).join(', ')"></span>
                                    </span>
                                </div>
                            </template>
                            <div x-show="toResults.length === 0 && to.length >= 2" class="fw-ac-empty">No airports found</div>
                        </div>
                    </div>
                    <span x-show="errors.to" class="fw-error" x-text="errors.to"></span>
                </div>

                {{-- Depart date --}}
                <div class="fw-field fw-field-15x" style="position:relative;">
                    <div class="fw-label">Depart</div>
                    <div class="fw-input-wrap">
                        <input
                            class="fw-input"
                            type="text"
                            placeholder="dd/mm/yyyy"
                            x-model="depart"
                            @click.stop="toggleCal('depart')"
                            readonly
                            style="cursor:pointer;"
                        >
                        <div class="fw-cal" :class="{ 'fw-open': openCal === 'depart' }" @click.stop>
                            <div class="fw-cal-header">
                                <button type="button" class="fw-cal-nav" @click="prevMonth('depart')">&#8249;</button>
                                <span class="fw-cal-title" x-text="calTitle('depart')"></span>
                                <button type="button" class="fw-cal-nav" @click="nextMonth('depart')">&#8250;</button>
                            </div>
                            <div class="fw-cal-grid">
                                <template x-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']">
                                    <div class="fw-cal-dow" x-text="d"></div>
                                </template>
                                <template x-for="cell in calCells('depart')" :key="cell.key">
                                    <button
                                        type="button"
                                        class="fw-cal-day"
                                        :class="{
                                            'fw-empty': cell.empty,
                                            'fw-disabled': cell.disabled,
                                            'fw-today': cell.today,
                                            'fw-selected': cell.selected
                                        }"
                                        @click="pickDate('depart', cell)"
                                        x-text="cell.empty ? '' : cell.d"
                                    ></button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <span x-show="errors.depart" class="fw-error" x-text="errors.depart"></span>
                </div>

                {{-- Return date (only shown for Return trip) --}}
                <div class="fw-field fw-field-15x" x-show="trip === 'Return'" style="position:relative;">
                    <div class="fw-label">Return</div>
                    <div class="fw-input-wrap">
                        <input
                            class="fw-input"
                            type="text"
                            placeholder="dd/mm/yyyy"
                            x-model="returning"
                            @click.stop="toggleCal('returning')"
                            readonly
                            style="cursor:pointer;"
                        >
                        <div class="fw-cal" :class="{ 'fw-open': openCal === 'returning' }" @click.stop>
                            <div class="fw-cal-header">
                                <button type="button" class="fw-cal-nav" @click="prevMonth('returning')">&#8249;</button>
                                <span class="fw-cal-title" x-text="calTitle('returning')"></span>
                                <button type="button" class="fw-cal-nav" @click="nextMonth('returning')">&#8250;</button>
                            </div>
                            <div class="fw-cal-grid">
                                <template x-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']">
                                    <div class="fw-cal-dow" x-text="d"></div>
                                </template>
                                <template x-for="cell in calCells('returning')" :key="cell.key">
                                    <button
                                        type="button"
                                        class="fw-cal-day"
                                        :class="{
                                            'fw-empty': cell.empty,
                                            'fw-disabled': cell.disabled,
                                            'fw-today': cell.today,
                                            'fw-selected': cell.selected
                                        }"
                                        @click="pickDate('returning', cell)"
                                        x-text="cell.empty ? '' : cell.d"
                                    ></button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <span x-show="errors.returning" class="fw-error" x-text="errors.returning"></span>
                </div>

                {{-- Passengers --}}
                <div class="fw-field fw-field-12x" style="position:relative;">
                    <div class="fw-label">Passengers</div>
                    <div class="fw-input fw-pax-trigger" @click.stop="paxOpen = !paxOpen">
                        <span x-text="(adults + childs + kids) + ' Pax'"></span>
                        <svg style="margin-left:auto;opacity:.4;flex-shrink:0" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <div class="fw-pax-dropdown" :class="{ 'fw-open': paxOpen }" @click.stop>
                        <div class="fw-pax-row">
                            <div><div class="fw-pax-lbl">Adults</div><div class="fw-pax-sub">12+ yrs</div></div>
                            <div class="fw-pax-ctr">
                                <button type="button" class="fw-pax-btn" @click="adults = Math.max(1, adults - 1)">−</button>
                                <span class="fw-pax-num" x-text="adults"></span>
                                <button type="button" class="fw-pax-btn" @click="adults++">+</button>
                            </div>
                        </div>
                        <div class="fw-pax-row">
                            <div><div class="fw-pax-lbl">Children</div><div class="fw-pax-sub">2–11 yrs</div></div>
                            <div class="fw-pax-ctr">
                                <button type="button" class="fw-pax-btn" @click="childs = Math.max(0, childs - 1)">−</button>
                                <span class="fw-pax-num" x-text="childs"></span>
                                <button type="button" class="fw-pax-btn" @click="childs++">+</button>
                            </div>
                        </div>
                        <div class="fw-pax-row">
                            <div><div class="fw-pax-lbl">Infants</div><div class="fw-pax-sub">Under 2</div></div>
                            <div class="fw-pax-ctr">
                                <button type="button" class="fw-pax-btn" @click="kids = Math.max(0, kids - 1)">−</button>
                                <span class="fw-pax-num" x-text="kids"></span>
                                <button type="button" class="fw-pax-btn" @click="kids++">+</button>
                            </div>
                        </div>
                        <button type="button" class="fw-pax-done" @click="paxOpen = false">Done</button>
                    </div>
                </div>

                {{-- Cabin --}}
                <div class="fw-field fw-field-12x">
                    <div class="fw-label">Cabin</div>
                    <select class="fw-input fw-select" x-model="flightType">
                        <option value="Y">Economy</option>
                        <option value="S">Prem. Economy</option>
                        <option value="C">Business</option>
                        <option value="F">First Class</option>
                    </select>
                </div>

            </div>
        </div>

        {{-- ── Multi City Panel ── --}}
        <div x-show="trip === 'multi'" x-transition>
            <div class="fw-multi-legs">
                <template x-for="(leg, index) in multiLegs" :key="index">
                    <div class="fw-leg">
                        <span class="fw-leg-badge" x-text="index + 1"></span>

                        {{-- Leg From --}}
                        <div class="fw-field fw-field-2x" style="position:relative;">
                            <div class="fw-label">From</div>
                            <div class="fw-input-wrap">
                                <input
                                    class="fw-input"
                                    type="text"
                                    placeholder="City or airport"
                                    x-model="leg.from"
                                    @input="searchLegAirport($event.target.value, index, 'from')"
                                    @click.stop
                                    autocomplete="off"
                                >
                                <div class="fw-ac-dropdown" :class="{ 'fw-open': leg.fromResults && leg.fromResults.length && leg.fromFocus }" @click.stop>
                                    <template x-for="(a, i) in (leg.fromResults || [])" :key="a.iata">
                                        <div class="fw-ac-item" :class="{ 'fw-hi': i === 0 }" @mousedown.prevent="selectLegAirport(index, 'from', a)">
                                            <span class="fw-ac-iata" x-text="a.iata"></span>
                                            <span class="fw-ac-info">
                                                <span class="fw-ac-name" x-text="a.name"></span>
                                                <span class="fw-ac-city" x-text="[a.city, a.country].filter(Boolean).join(', ')"></span>
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Leg To --}}
                        <div class="fw-field fw-field-2x" style="position:relative;">
                            <div class="fw-label">To</div>
                            <div class="fw-input-wrap">
                                <input
                                    class="fw-input"
                                    type="text"
                                    placeholder="City or airport"
                                    x-model="leg.to"
                                    @input="searchLegAirport($event.target.value, index, 'to')"
                                    @click.stop
                                    autocomplete="off"
                                >
                                <div class="fw-ac-dropdown" :class="{ 'fw-open': leg.toResults && leg.toResults.length && leg.toFocus }" @click.stop>
                                    <template x-for="(a, i) in (leg.toResults || [])" :key="a.iata">
                                        <div class="fw-ac-item" :class="{ 'fw-hi': i === 0 }" @mousedown.prevent="selectLegAirport(index, 'to', a)">
                                            <span class="fw-ac-iata" x-text="a.iata"></span>
                                            <span class="fw-ac-info">
                                                <span class="fw-ac-name" x-text="a.name"></span>
                                                <span class="fw-ac-city" x-text="[a.city, a.country].filter(Boolean).join(', ')"></span>
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Leg Date --}}
                        <div class="fw-field fw-field-15x" style="position:relative;">
                            <div class="fw-label">Date</div>
                            <div class="fw-input-wrap">
                                <input
                                    class="fw-input"
                                    type="text"
                                    placeholder="dd/mm/yyyy"
                                    x-model="leg.depart"
                                    @click.stop="toggleLegCal(index)"
                                    readonly
                                    style="cursor:pointer;"
                                >
                                <div class="fw-cal" :class="{ 'fw-open': openLegCal === index }" @click.stop>
                                    <div class="fw-cal-header">
                                        <button type="button" class="fw-cal-nav" @click="prevLegMonth(index)">&#8249;</button>
                                        <span class="fw-cal-title" x-text="legCalTitle(index)"></span>
                                        <button type="button" class="fw-cal-nav" @click="nextLegMonth(index)">&#8250;</button>
                                    </div>
                                    <div class="fw-cal-grid">
                                        <template x-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']">
                                            <div class="fw-cal-dow" x-text="d"></div>
                                        </template>
                                        <template x-for="cell in legCalCells(index)" :key="cell.key">
                                            <button
                                                type="button"
                                                class="fw-cal-day"
                                                :class="{
                                                    'fw-empty': cell.empty,
                                                    'fw-disabled': cell.disabled,
                                                    'fw-today': cell.today,
                                                    'fw-selected': cell.selected
                                                }"
                                                @click="pickLegDate(index, cell)"
                                                x-text="cell.empty ? '' : cell.d"
                                            ></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Leg Cabin --}}
                        <div class="fw-field fw-field-12x">
                            <div class="fw-label">Cabin</div>
                            <select
                                class="fw-input fw-select"
                                :value="leg.cabin"
                                @change="multiLegs[index].cabin = $event.target.value; multiLegs = [...multiLegs]"
                            >
                                <option value="Y">Economy</option>
                                <option value="S">Prem. Economy</option>
                                <option value="C">Business</option>
                                <option value="F">First Class</option>
                            </select>
                        </div>

                        {{-- Remove button (only for legs beyond the first two) --}}
                        <button
                            x-show="index >= 2"
                            type="button"
                            class="fw-remove-btn"
                            @click="removeLeg(index)"
                        >
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            <button type="button" class="fw-add-leg-btn" style="margin-top:10px;" @click="addLeg">
                + Add another flight
            </button>

        </div>

        {{-- ── Search Button ── --}}
        <div class="fw-search-row">
            <span x-show="errors.general" class="fw-error" style="margin-right:auto;" x-text="errors.general"></span>
            <button class="fw-search-btn" type="button" @click="search">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Search Flights
            </button>
        </div>

        {{-- Hidden form for POST submission --}}
        <form id="fw-form" method="POST" action="{{ route('flights.search') }}" style="display:none;">
            @csrf
            <input type="hidden" name="trip"        x-bind:value="trip">
            <input type="hidden" name="from"        x-bind:value="from">
            <input type="hidden" name="to"          x-bind:value="to">
            <input type="hidden" name="depart"      x-bind:value="depart">
            <input type="hidden" name="returning"   x-bind:value="returning">
            <input type="hidden" name="adults"      x-bind:value="adults">
            <input type="hidden" name="childs"      x-bind:value="childs">
            <input type="hidden" name="kids"        x-bind:value="kids">
            <input type="hidden" name="flight_type" x-bind:value="flightType">
            <input type="hidden" name="multi_legs"  x-bind:value="JSON.stringify(multiLegs)">
        </form>

    </div>
</div>

<script>
function flightWidget() {
    return {
        // ── Core state ──
        trip: 'OneWay',
        from: '',
        to: '',
        depart: '',
        returning: '',
        adults: 1,
        childs: 0,
        kids: 0,
        flightType: 'Y',
        multiLegs: [
            { from: '', to: '', depart: '', cabin: 'Y', fromResults: [], toResults: [], fromFocus: false, toFocus: false, calY: new Date().getFullYear(), calM: new Date().getMonth() },
            { from: '', to: '', depart: '', cabin: 'Y', fromResults: [], toResults: [], fromFocus: false, toFocus: false, calY: new Date().getFullYear(), calM: new Date().getMonth() },
        ],

        // ── UI state ──
        paxOpen: false,
        openCal: null,       // 'depart' | 'returning' | null
        openLegCal: null,    // index | null
        calState: {
            depart:    { y: new Date().getFullYear(), m: new Date().getMonth() },
            returning: { y: new Date().getFullYear(), m: new Date().getMonth() },
        },

        // ── Airport data ──
        airports: [],
        fromResults: [],
        toResults: [],
        fromFocus: false,
        toFocus: false,

        // ── Validation errors ──
        errors: {},

        // ── Init ──
        init() {
            fetch('{{ asset('assets/data/airports.json') }}')
                .then(r => r.json())
                .then(d => { this.airports = d; })
                .catch(e => console.error('[FW] airports.json:', e));

            // Close dropdowns/calendars on outside click
            document.addEventListener('click', () => {
                this.fromFocus = false;
                this.toFocus   = false;
                this.openCal   = null;
                this.openLegCal = null;
                this.paxOpen   = false;
            });
        },

        // ── Airport search ──
        airportSearch(q) {
            q = q.toLowerCase().trim();
            if (q.length < 2) return [];
            return this.airports.filter(a =>
                (a.iata    && a.iata.toLowerCase().startsWith(q))  ||
                (a.city    && a.city.toLowerCase().includes(q))    ||
                (a.name    && a.name.toLowerCase().includes(q))    ||
                (a.country && a.country.toLowerCase().includes(q))
            ).slice(0, 8);
        },

        searchAirport(val, target) {
            const results = this.airportSearch(val);
            if (target === 'fromResults') {
                this.fromResults = results;
                this.fromFocus   = true;
            } else {
                this.toResults = results;
                this.toFocus   = true;
            }
        },

        selectAirport(field, airport) {
            const val = (airport.city || airport.name) + ' (' + airport.iata + ')';
            if (field === 'from') {
                this.from        = val;
                this.fromResults = [];
                this.fromFocus   = false;
            } else {
                this.to        = val;
                this.toResults = [];
                this.toFocus   = false;
            }
        },

        // Multi-leg airport search
        searchLegAirport(val, index, field) {
            const results = this.airportSearch(val);
            const leg = this.multiLegs[index];
            if (field === 'from') {
                leg.fromResults = results;
                leg.fromFocus   = true;
            } else {
                leg.toResults = results;
                leg.toFocus   = true;
            }
            this.multiLegs = [...this.multiLegs]; // trigger reactivity
        },

        selectLegAirport(index, field, airport) {
            const val = (airport.city || airport.name) + ' (' + airport.iata + ')';
            const leg = { ...this.multiLegs[index] };
            if (field === 'from') {
                leg.from        = val;
                leg.fromResults = [];
                leg.fromFocus   = false;
            } else {
                leg.to        = val;
                leg.toResults = [];
                leg.toFocus   = false;
            }
            this.multiLegs[index] = leg;
            this.multiLegs = [...this.multiLegs];
        },

        // ── Swap ──
        swapRoutes() {
            const tmp = this.from;
            this.from = this.to;
            this.to   = tmp;
        },

        // ── Passengers ──
        closePax() {
            this.paxOpen = false;
        },

        // ── Multi-leg management ──
        addLeg() {
            this.multiLegs.push({
                from: '', to: '', depart: '', cabin: 'Y',
                fromResults: [], toResults: [],
                fromFocus: false, toFocus: false,
                calY: new Date().getFullYear(), calM: new Date().getMonth(),
            });
        },

        removeLeg(index) {
            this.multiLegs.splice(index, 1);
        },

        // ── Calendar helpers ──
        MONTHS: ['January','February','March','April','May','June','July','August','September','October','November','December'],

        calTitle(field) {
            const s = this.calState[field];
            return this.MONTHS[s.m] + ' ' + s.y;
        },

        prevMonth(field) {
            const s = this.calState[field];
            s.m--;
            if (s.m < 0) { s.m = 11; s.y--; }
        },

        nextMonth(field) {
            const s = this.calState[field];
            s.m++;
            if (s.m > 11) { s.m = 0; s.y++; }
        },

        calCells(field) {
            const s      = this.calState[field];
            const today  = new Date(); today.setHours(0,0,0,0);
            const selStr = this[field]; // e.g. this.depart
            let selDate  = null;
            if (selStr) {
                const p = selStr.split('/');
                if (p.length === 3) selDate = new Date(+p[2], +p[1]-1, +p[0]);
            }
            const startDow = new Date(s.y, s.m, 1).getDay();
            const lastDay  = new Date(s.y, s.m + 1, 0).getDate();
            const cells    = [];

            for (let i = 0; i < startDow; i++) {
                cells.push({ key: 'e'+i, empty: true });
            }
            for (let d = 1; d <= lastDay; d++) {
                const date = new Date(s.y, s.m, d);
                cells.push({
                    key:      d,
                    d,
                    empty:    false,
                    disabled: date < today,
                    today:    date.toDateString() === today.toDateString(),
                    selected: selDate && date.toDateString() === selDate.toDateString(),
                    y: s.y, mo: s.m,
                });
            }
            return cells;
        },

        pickDate(field, cell) {
            if (cell.empty || cell.disabled) return;
            const val = String(cell.d).padStart(2,'0') + '/' + String(cell.mo+1).padStart(2,'0') + '/' + cell.y;
            this[field] = val;
            this.openCal = null;
        },

        toggleCal(field) {
            if (this.openCal === field) {
                this.openCal = null;
            } else {
                // Sync calendar state to existing value
                const val = this[field];
                if (val) {
                    const p = val.split('/');
                    if (p.length === 3) {
                        this.calState[field].m = +p[1]-1;
                        this.calState[field].y = +p[2];
                    }
                } else {
                    const n = new Date();
                    this.calState[field].m = n.getMonth();
                    this.calState[field].y = n.getFullYear();
                }
                this.openCal = field;
            }
            this.openLegCal = null;
        },

        closeAllCals() {
            this.openCal    = null;
            this.openLegCal = null;
        },

        // Leg-specific calendar
        legCalTitle(index) {
            const leg = this.multiLegs[index];
            return this.MONTHS[leg.calM] + ' ' + leg.calY;
        },

        prevLegMonth(index) {
            const leg = this.multiLegs[index];
            leg.calM--;
            if (leg.calM < 0) { leg.calM = 11; leg.calY--; }
            this.multiLegs = [...this.multiLegs];
        },

        nextLegMonth(index) {
            const leg = this.multiLegs[index];
            leg.calM++;
            if (leg.calM > 11) { leg.calM = 0; leg.calY++; }
            this.multiLegs = [...this.multiLegs];
        },

        legCalCells(index) {
            const leg    = this.multiLegs[index];
            const today  = new Date(); today.setHours(0,0,0,0);
            let selDate  = null;
            if (leg.depart) {
                const p = leg.depart.split('/');
                if (p.length === 3) selDate = new Date(+p[2], +p[1]-1, +p[0]);
            }
            const startDow = new Date(leg.calY, leg.calM, 1).getDay();
            const lastDay  = new Date(leg.calY, leg.calM + 1, 0).getDate();
            const cells    = [];

            for (let i = 0; i < startDow; i++) cells.push({ key: 'e'+i, empty: true });
            for (let d = 1; d <= lastDay; d++) {
                const date = new Date(leg.calY, leg.calM, d);
                cells.push({
                    key:      d,
                    d,
                    empty:    false,
                    disabled: date < today,
                    today:    date.toDateString() === today.toDateString(),
                    selected: selDate && date.toDateString() === selDate.toDateString(),
                    y: leg.calY, mo: leg.calM,
                });
            }
            return cells;
        },

        pickLegDate(index, cell) {
            if (cell.empty || cell.disabled) return;
            const val = String(cell.d).padStart(2,'0') + '/' + String(cell.mo+1).padStart(2,'0') + '/' + cell.y;
            this.multiLegs[index].depart = val;
            this.multiLegs = [...this.multiLegs];
            this.openLegCal = null;
        },

        toggleLegCal(index) {
            this.openLegCal = (this.openLegCal === index) ? null : index;
            this.openCal = null;
        },

        // ── Validation & Search ──
        validate() {
            this.errors = {};
            if (this.trip !== 'multi') {
                if (!this.from.trim())   this.errors.from   = 'Please enter a departure city or airport.';
                if (!this.to.trim())     this.errors.to     = 'Please enter a destination city or airport.';
                if (!this.depart.trim()) this.errors.depart = 'Please select a departure date.';
                if (this.trip === 'Return' && !this.returning.trim())
                    this.errors.returning = 'Please select a return date.';
            } else {
                const incomplete = this.multiLegs.some(l => !l.from || !l.to || !l.depart);
                if (incomplete) this.errors.general = 'Please complete all flight legs.';
            }
            return Object.keys(this.errors).length === 0;
        },

        search() {
            if (!this.validate()) return;
            document.getElementById('fw-form').submit();
        },
    };
}
</script>