<style>
    .tw-landing-hero{width:100%;min-height:670px;display:flex;align-items:flex-start;justify-content:center;padding:60px 24px 30px;background:url('{{ asset('assets/figma/landing/asset-03.jpeg') }}') center center/cover no-repeat;position:relative;box-sizing:border-box;overflow:visible}
    .tw-landing-hero::before{content:'';display:block!important;position:absolute;inset:0;background:linear-gradient(96deg,rgba(48,49,145,.92) 0%,rgba(48,49,145,.84) 34%,rgba(0,153,51,.72) 100%),rgba(0,0,0,.2);pointer-events:none}
    .tw-hero-inner{position:relative;z-index:2;width:100%;max-width:1306px;text-align:center}
    .tw-hero-title{margin:0;color:#fff!important;font-family:var(--tw-font-sans,var(--font-primary));font-size:var(--tw-text-5xl);font-weight:800;line-height:var(--tw-leading-tight,1.15)}
    .tw-hero-subtitle{margin:8px 0 26px;color:rgba(255,255,255,.9)!important;font-family:var(--tw-font-sans,var(--font-primary));font-size:var(--tw-text-2xl);font-weight:700;line-height:var(--tw-leading-snug,1.3)}
    .tw-product-tabs{display:inline-flex;align-items:center;justify-content:center;gap:16px;margin:0 auto 24px;padding:16px 28px;background:rgba(21,22,76,.79);border:1px solid rgba(255,255,255,.8);border-radius:11px}
    .tw-product-tab{width:88px;height:66px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;border-radius:12px;background:rgba(255,255,255,.21);color:#fff!important;text-decoration:none;font-size:var(--tw-text-sm);font-weight:700}
    .tw-product-tab img{width:26px;height:26px;object-fit:contain;filter:brightness(0) invert(1)}
    .tw-product-tab span{color:#fff!important}
    .fw-card-outer{width:100%;position:relative;box-sizing:border-box}
    .fw-card-outer:not(.tw-landing-hero)::before{display:none}
    .fw-card{position:relative;z-index:2;width:100%;max-width:1306px;margin:0 auto;background:linear-gradient(180deg,#fff 0%,#fbfbff 100%);border:1px solid rgba(255,255,255,.78);border-radius:var(--tw-radius-xl,17px);padding:28px 38px 29px;box-shadow:0 20px 31.4px rgba(0,0,0,.25);box-sizing:border-box;text-align:left}
    .fw-tabs{display:flex;gap:17px;margin-bottom:17px;flex-wrap:wrap;border-bottom:1px solid #d9d9d9;padding-bottom:17px}
    .fw-tab{display:inline-flex;align-items:center;gap:7px;padding:9px 13px;border-radius:30px;font-size:12px;font-weight:700;cursor:pointer;color:#2f2f35;background:#ecebef;border:1px solid transparent;transition:background .2s,color .2s,border-color .2s,box-shadow .2s,transform .2s;user-select:none;line-height:1;margin:0}
    .fw-tab:hover{background:#eef0ff;color:#303191;transform:translateY(-1px)}
    .fw-tab.fw-active{background:#dbdcff;color:#303191;border-color:rgba(48,49,145,.16);font-weight:700;box-shadow:0 8px 18px rgba(48,49,145,.12)}
    .fw-row{display:flex;align-items:flex-end;gap:14px;flex-wrap:nowrap}
    .fw-field{display:flex;flex-direction:column;min-width:0;flex:1}
    .fw-field-2x{flex:1.72!important}.fw-field-15x{flex:1.16!important}.fw-field-12x{flex:1.16!important}
    .fw-label{display:flex;align-items:center;gap:5px;font-size:var(--tw-text-xs);font-weight:700;text-transform:uppercase;letter-spacing:0;color:#111;margin-bottom:8px;white-space:nowrap}
    .fw-input-wrap{position:relative}
    .fw-input{width:100%;height:51px;padding:0 11px;border:1px solid #d9d9d9;border-radius:6px;font-size:var(--tw-text-md);color:#111;background:#f9f9f9;outline:none;box-sizing:border-box;transition:border-color .2s,box-shadow .2s,background .2s;-webkit-appearance:none;appearance:none;font-family:var(--tw-font-sans,var(--font-primary));display:flex;align-items:center;text-align:left;justify-content:flex-start}
    .fw-input:focus{border-color:#303191;background:#fff;box-shadow:0 0 0 3px rgba(48,49,145,.12),0 8px 20px rgba(48,49,145,.08)}
    .fw-input::placeholder{color:#a6a6a6}
    .fw-select{cursor:pointer;padding-right:28px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center}
    .fw-error{font-size:11px;color:#ef4444;margin-top:4px}
    .fw-swap{width:33px;height:33px;min-width:33px;border-radius:50%;border:1px solid #dbdcff;background:#fff;color:#303191;display:flex;align-items:center;justify-content:center;cursor:pointer;margin-bottom:9px;flex-shrink:0;transition:all .25s;padding:0}
    .fw-swap:hover{background:#dbdcff;border-color:#303191;color:#303191;transform:rotate(180deg)}
    .fw-ac-dropdown{display:none;position:absolute;top:calc(100% + 9px);left:0;right:auto;width:clamp(286px,100%,360px);max-height:302px;background:#fff;border:1px solid rgba(48,49,145,.1);border-radius:14px;box-shadow:0 18px 42px rgba(23,23,74,.18);z-index:99999;overflow:hidden;overflow-y:auto;padding:8px;box-sizing:border-box}
    .fw-ac-dropdown.fw-open{display:block}
    .fw-ac-item{display:grid;grid-template-columns:52px minmax(0,1fr);align-items:center;gap:11px;padding:10px;border-radius:10px;margin:0 0 4px;cursor:pointer;border:1px solid transparent;transition:background .15s,border-color .15s,box-shadow .15s,transform .15s}
    .fw-ac-item:last-child{margin-bottom:0}
    .fw-ac-item:hover,.fw-ac-item.fw-hi{background:#f7f7fb;border-color:rgba(48,49,145,.12);box-shadow:0 8px 18px rgba(48,49,145,.06);transform:translateY(-1px)}
    .fw-ac-iata{display:flex;align-items:center;justify-content:center;width:52px;height:42px;border-radius:10px;background:#f1f2ff;color:#303191;font-size:16px;font-weight:800;line-height:1;font-family:'Open Sans',var(--font-primary),Arial,sans-serif;letter-spacing:.02em}
    .fw-ac-info{display:flex;flex-direction:column;min-width:0;overflow:hidden}
    .fw-ac-name{font-size:13px;font-weight:700;color:#111;line-height:17px;white-space:normal;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow-wrap:anywhere}
    .fw-ac-city{font-size:11px;color:#666;line-height:15px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .fw-ac-empty{padding:18px 14px;font-size:13px;color:#6b7280;text-align:center;background:#f9fafb;border-radius:10px}
    .fw-pax-trigger{display:flex;align-items:center;gap:8px;cursor:pointer;user-select:none}
    .fw-pax-dropdown{display:none;position:absolute;top:calc(100% + 7px);left:0;width:183px;height:157px;min-width:183px;background:#fff;border:0;border-radius:10px;box-shadow:0 14px 34px rgba(0,0,0,.15);z-index:99998;overflow:hidden;padding:13px;box-sizing:border-box}
    .fw-pax-dropdown.fw-open{display:block}
    .fw-pax-row{display:flex;align-items:center;justify-content:space-between;padding:0 0 9px;border-bottom:0}
    .fw-pax-row:last-of-type{border-bottom:none}
    .fw-pax-lbl{font-size:14px;font-weight:700;color:#111827;line-height:15px}
    .fw-pax-sub{font-size:10px;color:#7d7d7d;margin-top:1px;line-height:11px}
    .fw-pax-ctr{display:flex;align-items:center;gap:8px}
    .fw-pax-btn{width:18px;height:18px;border-radius:50%;border:1px solid #d9d9d9;background:#fff;font-size:14px;line-height:1;color:#303191;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;padding:0;font-family:inherit}
    .fw-pax-btn:hover{background:#dbdcff;border-color:#303191;color:#303191}
    .fw-pax-btn:disabled{opacity:.45;cursor:not-allowed;background:#f9fafb;color:#9ca3af;border-color:#e5e7eb}
    .fw-pax-btn:disabled:hover{background:#f9fafb;color:#9ca3af;border-color:#e5e7eb}
    .fw-pax-num{font-size:14px;font-weight:700;color:#111827;min-width:12px;text-align:center}
    .fw-pax-limit{margin:10px 16px 0;padding:9px 10px;border-radius:8px;background:#fef2f2;color:#b91c1c;font-size:11.5px;font-weight:600;line-height:1.35}
    .fw-pax-done{display:none}
    .fw-pax-done:hover{background:#252675}
    .fw-multi-legs{display:flex;flex-direction:column;gap:12px}
    .fw-leg{display:flex;align-items:flex-end;gap:10px;padding:16px 18px;background:#f9fafb;border-radius:12px;border:1.5px solid #f0f0f0;flex-wrap:nowrap}
    .fw-leg-badge{width:26px;height:26px;background:#303191;color:#fff;font-size:12px;font-weight:700;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-bottom:12px}
    .fw-remove-btn{width:34px;height:34px;border-radius:50%;border:1.5px solid #fca5a5;background:#fff;color:#ef4444;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-bottom:5px;padding:0;transition:background .15s}
    .fw-remove-btn:hover{background:#fef2f2}
    .fw-add-leg-btn{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border:1.5px dashed #303191;border-radius:10px;background:#dbdcff;color:#303191;font-size:13px;font-weight:700;cursor:pointer;transition:all .2s;font-family:inherit}
    .fw-add-leg-btn:hover{background:#cfd1ff;border-color:#303191}
    .fw-search-row{display:flex;align-items:center;justify-content:flex-end;margin-top:23px;gap:14px}
    .fw-search-btn{position:relative;display:inline-flex;align-items:center;justify-content:center;gap:10px;width:214px;padding:0;height:64px;background:#303191;color:#fff!important;border:1px solid #303191;border-radius:12px;font-size:var(--tw-text-lg);font-weight:800;cursor:pointer;box-shadow:0 12px 24px rgba(48,49,145,.22);transition:background .18s,border-color .18s,transform .18s,box-shadow .18s;letter-spacing:0;font-family:var(--tw-font-sans,var(--font-primary));white-space:nowrap;overflow:hidden}
    .fw-search-btn svg{position:relative;z-index:1;width:22px!important;height:22px!important;color:#fff!important;stroke:#fff!important;fill:none}
    .fw-search-btn svg *{color:#fff!important;stroke:#fff!important}
    .fw-search-btn:hover{background:#252675;border-color:#252675;box-shadow:0 16px 30px rgba(48,49,145,.28);transform:translateY(-1px)}
    .fw-search-btn:active{transform:translateY(0)}

    /* Calendar */
    .fw-cal{display:none;position:absolute;top:calc(100% + 7px);left:0;background:#fff;border:0;border-radius:10px;box-shadow:0 14px 34px rgba(0,0,0,.15);z-index:99997;width:220px;height:220px;padding:13px 14px;box-sizing:border-box;font-family:'Open Sans',var(--font-primary),Arial,sans-serif}
    .fw-cal.fw-open{display:block}
    .fw-cal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
    .fw-cal-title{font-size:14px;font-weight:700;color:#303191}
    .fw-cal-nav{width:20px;height:20px;border-radius:50%;border:0;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#303191;padding:0;font-size:16px;line-height:1;transition:all .15s}
    .fw-cal-nav:hover{background:#eff6ff;border-color:#3b82f6;color:#1d4ed8}
    .fw-cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:2px}
    .fw-cal-dow{font-size:10px;font-weight:700;text-transform:none;color:#303191;text-align:center;padding:2px 0}
    .fw-cal-day{height:22px;border-radius:50%;border:none;background:none;font-size:12px;color:#111827;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .12s,color .12s;font-family:inherit;width:100%;padding:0}
    .fw-cal-day:hover:not(.fw-disabled):not(.fw-empty){background:#dbdcff;color:#303191}
    .fw-cal-day.fw-today{font-weight:700;color:#303191}
    /* Range styles: range-start/end override in-range */
    .fw-cal-day.fw-in-range{background:#dbdcff !important;color:#303191;border-radius:0}
    .fw-cal-day.fw-range-start{background:#009933 !important;color:#fff !important;font-weight:700;border-radius:8px 0 0 8px !important}
    .fw-cal-day.fw-range-end{background:#009933 !important;color:#fff !important;font-weight:700;border-radius:0 8px 8px 0 !important}
    /* When start == end (same day selected on both calendars) show full circle */
    .fw-cal-day.fw-range-start.fw-range-end{border-radius:8px !important}
    .fw-cal-day.fw-selected{background:#009933 !important;color:#fff !important;font-weight:700;border-radius:8px;box-shadow:0 5px 12px rgba(0,153,51,.25)}
    .fw-cal-day.fw-disabled{color:#d1d5db !important;cursor:not-allowed;background:none !important}
    .fw-cal-day.fw-empty{visibility:hidden;pointer-events:none}
    .fw-cabin-field{position:relative}
    .fw-cabin-dropdown{display:none;position:absolute;top:calc(100% + 7px);left:0;width:158px;height:117px;background:#fff;border:0;border-radius:10px;box-shadow:0 14px 34px rgba(0,0,0,.15);z-index:99997;padding:12px 14px;box-sizing:border-box}
    .fw-cabin-dropdown.fw-open{display:block}
    .fw-cabin-option{display:block;width:100%;border:0;background:transparent;text-align:left;color:#111!important;font-size:14px;font-weight:500;line-height:17px;padding:0 0 7px;cursor:pointer;font-family:'Open Sans',var(--font-primary),Arial,sans-serif}
    .fw-cabin-option:last-child{padding-bottom:0}
    .fw-cabin-option:hover{color:#303191!important}
    .fw-multi-controls{display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:wrap}
    .fw-multi-cabin{position:relative;flex-shrink:0}
    .fw-multi-cabin-trigger{display:inline-flex;align-items:center;gap:7px;height:36px;padding:0 14px;border:1.5px solid #303191;border-radius:999px;background:#fff;color:#303191;font-size:12px;font-weight:600;cursor:pointer}
    .fw-multi-cabin .fw-cabin-dropdown{left:auto;right:0;top:calc(100% + 7px)}
    .fw-panel-transition{transform-origin:top center}

    /* Responsive */
    @media(max-width:1100px){.tw-product-tabs{display:grid;grid-template-columns:repeat(4,88px)}.fw-card{padding:24px 20px 20px}.fw-row{flex-wrap:wrap;gap:10px}.fw-field,.fw-field-2x,.fw-field-15x,.fw-field-12x{flex:1 1 calc(50% - 10px)!important;min-width:calc(50% - 10px)}.fw-swap{display:none!important}.fw-ac-dropdown{width:min(360px,calc(100vw - 48px))}}
    @media(max-width:640px){.tw-landing-hero{min-height:auto;padding:48px 14px 42px}.tw-hero-title{white-space:normal}.tw-hero-subtitle{margin-bottom:20px}.tw-product-tabs{width:100%;grid-template-columns:repeat(2,1fr);gap:10px;padding:12px}.tw-product-tab{width:100%}.fw-card{padding:20px 16px;border-radius:14px}.fw-field,.fw-field-2x,.fw-field-15x,.fw-field-12x{flex:1 1 100%!important;min-width:100%}.fw-leg{flex-wrap:wrap}.fw-search-btn{width:100%;justify-content:center;height:56px;font-size:var(--tw-text-base)}.fw-search-row{margin-top:14px}.fw-tab{padding:8px 11px;font-size:12px}.fw-multi-controls{width:100%;justify-content:flex-start}.fw-multi-cabin{flex:1}.fw-multi-cabin-trigger{width:100%;justify-content:space-between}}
    main.navbarmain.upper-space{margin-top:113px!important}
    @media(max-width:650px){body > div > section.navbarmain{padding-top:0!important}main.navbarmain.upper-space{margin-top:104px!important;padding-top:0!important}}
</style>

<div
    class="tw-landing-hero fw-card-outer"
    x-data="flightWidget()"
    x-init="init()"
    @click.outside="closePax(); closeAllCals()"
>
    <div class="tw-hero-inner">
        <h1 class="tw-hero-title">All Your Travel Needs In One Place</h1>
        <p class="tw-hero-subtitle">Simplifying Access To Travel.</p>

        <div class="tw-product-tabs" aria-label="TravelWheel services">
            <a class="tw-product-tab" href="{{ url('/') }}"><img src="{{ asset('assets/Flight 70.png') }}" alt=""><span>Flights</span></a>
            <!-- <a class="tw-product-tab" href="{{ route('air.hotel') }}"><img src="{{ asset('assets/Hotel 70.png') }}" alt=""><span>Hotels</span></a> -->
            <a class="tw-product-tab" href="{{ route('air.lounge') }}"><img src="{{ asset('assets/Lounge 70.png') }}" alt=""><span>Lounge</span></a>
            <a class="tw-product-tab" href="{{ route('air.protocol') }}"><img src="{{ asset('assets/Protocol 70.png') }}" alt=""><span>Protocol</span></a>
            <a class="tw-product-tab" href="{{ route('air.insurance') }}"><img src="{{ asset('assets/Insurance 70.png') }}" alt=""><span>Insurance</span></a>
            <a class="tw-product-tab" href="{{ route('air.visa') }}"><img src="{{ asset('assets/Visa 70.png') }}" alt=""><span>Visa</span></a>
            <a class="tw-product-tab" href="{{ route('air.cargo') }}"><img src="{{ asset('assets/Air Cargo 70.png') }}" alt=""><span>Cargo</span></a>
            <a class="tw-product-tab" href="{{ route('air.support') }}"><img src="{{ asset('assets/Support 70.png') }}" alt=""><span>Support</span></a>
            <a class="tw-product-tab" href="{{ route('air.carhire') }}"><img src="{{ asset('assets/Car Hire 70.png') }}" alt=""><span>Car Hire</span></a>

        </div>

        <div class="fw-card">

        {{-- Trip Type Tabs --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:22px;flex-wrap:wrap;">
            <div class="fw-tabs" style="margin-bottom:0;flex:1;min-width:0;">
                <button type="button" class="fw-tab" :class="{ 'fw-active': trip === 'OneWay' }" @click="setTrip('OneWay')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    One Way
                </button>
                <button type="button" class="fw-tab" :class="{ 'fw-active': trip === 'Return' }" @click="setTrip('Return')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                    Round Trip
                </button>
                <button type="button" class="fw-tab" :class="{ 'fw-active': trip === 'multi' }" @click="setTrip('multi')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="2.4"/><circle cx="18" cy="6" r="2.4"/><circle cx="6" cy="18" r="2.4"/><circle cx="18" cy="18" r="2.4"/><path d="M8.4 6h7.2M6 8.4v7.2M8.4 18h7.2M18 8.4v7.2"/></svg>
                    Multi City
                </button>
            </div>

            <div class="fw-multi-controls" x-show="trip === 'multi'" x-transition>
            <div class="fw-multi-cabin">
                <button type="button" class="fw-multi-cabin-trigger" @click.stop="cabinOpen = !cabinOpen; paxOpen = false">
                    <span x-text="cabinLabel(flightType)"></span>
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="fw-cabin-dropdown" :class="{ 'fw-open': cabinOpen }" @click.stop>
                    <button type="button" class="fw-cabin-option" @click="setSharedCabin('Y')">Economy</button>
                    <button type="button" class="fw-cabin-option" @click="setSharedCabin('S')">Premium Economy</button>
                    <button type="button" class="fw-cabin-option" @click="setSharedCabin('C')">Business</button>
                    <button type="button" class="fw-cabin-option" @click="setSharedCabin('F')">First Class</button>
                </div>
            </div>

            {{-- Global passenger selector for Multi City --}}
            <div style="position:relative;flex-shrink:0;">
                <div class="fw-tab fw-active" style="cursor:pointer;gap:8px;padding:8px 14px;border-radius:999px;border-color:#bfdbfe;" @click.stop="paxOpen = !paxOpen">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span x-text="(adults + childs + kids) + ' Passenger' + (adults + childs + kids > 1 ? 's' : '')"></span>
                    <svg style="opacity:.5" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="fw-pax-dropdown" :class="{ 'fw-open': paxOpen }" @click.stop style="left:auto;right:0;">
                    <div class="fw-pax-row">
                        <div><div class="fw-pax-lbl">Adults</div><div class="fw-pax-sub">18+ yrs</div></div>
                        <div class="fw-pax-ctr">
                            <button type="button" class="fw-pax-btn" @click="adults = Math.max(1, adults - 1)">-</button>
                            <span class="fw-pax-num" x-text="adults"></span>
                            <button type="button" class="fw-pax-btn" :disabled="totalPassengers() >= 9" @click="addPassenger('adults')">+</button>
                        </div>
                    </div>
                    <div class="fw-pax-row">
                        <div><div class="fw-pax-lbl">Children</div><div class="fw-pax-sub">2-12 yrs</div></div>
                        <div class="fw-pax-ctr">
                            <button type="button" class="fw-pax-btn" @click="childs = Math.max(0, childs - 1)">-</button>
                            <span class="fw-pax-num" x-text="childs"></span>
                            <button type="button" class="fw-pax-btn" :disabled="totalPassengers() >= 9" @click="addPassenger('childs')">+</button>
                        </div>
                    </div>
                    <div class="fw-pax-row">
                        <div><div class="fw-pax-lbl">Infants</div><div class="fw-pax-sub">Under 2</div></div>
                        <div class="fw-pax-ctr">
                            <button type="button" class="fw-pax-btn" @click="kids = Math.max(0, kids - 1)">-</button>
                            <span class="fw-pax-num" x-text="kids"></span>
                            <button type="button" class="fw-pax-btn" :disabled="totalPassengers() >= 9" @click="addPassenger('kids')">+</button>
                        </div>
                    </div>
                    <div x-show="errors.passengers || totalPassengers() >= 9" x-transition class="fw-pax-limit" x-text="errors.passengers || 'Maximum 9 passengers per booking.'"></div>
                    <button type="button" class="fw-pax-done" @click="paxOpen = false">Done</button>
                </div>
            </div>
            </div>
        </div>

        {{-- Simple Panel: One Way + Return --}}
        <div class="fw-panel-transition" x-show="trip !== 'multi'" x-transition.opacity.scale.95.duration.220ms>
            <div class="fw-row">

                {{-- From --}}
                <div class="fw-field fw-field-2x" style="position:relative;">
                    <div class="fw-label">FROM</div>
                    <div class="fw-input-wrap">
                        <input class="fw-input" type="text" placeholder="City or airport"
                            x-model="from"
                            @input="searchAirport($event.target.value, 'fromResults')"
                            @focus="searchAirport(from, 'fromResults'); fromFocus = true"
                            @blur="setTimeout(() => fromFocus = false, 150)"
                            @click.stop autocomplete="off">
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

                {{-- Swap --}}
                <button class="fw-swap" type="button" @click="swapRoutes">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                </button>

                {{-- To --}}
                <div class="fw-field fw-field-2x" style="position:relative;">
                    <div class="fw-label">TO</div>
                    <div class="fw-input-wrap">
                        <input class="fw-input" type="text" placeholder="City or airport"
                            x-model="to"
                            @input="searchAirport($event.target.value, 'toResults')"
                            @focus="searchAirport(to, 'toResults'); toFocus = true"
                            @blur="setTimeout(() => toFocus = false, 150)"
                            @click.stop autocomplete="off">
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
                    <div class="fw-label">DEPARTURE DATE</div>
                    <div class="fw-input-wrap">
                        <input class="fw-input" type="text" placeholder="dd/mm/yyyy"
                            x-model="depart"
                            @click.stop="toggleCal('depart')"
                            readonly style="cursor:pointer;">
                        <div class="fw-cal" :class="{ 'fw-open': openCal === 'depart' }" @click.stop>
                            <div class="fw-cal-header">
                                <button type="button" class="fw-cal-nav" @click="prevMonth('depart')">&#8249;</button>
                                <span class="fw-cal-title" x-text="calTitle('depart')"></span>
                                <button type="button" class="fw-cal-nav" @click="nextMonth('depart')">&#8250;</button>
                            </div>
                            <div class="fw-cal-grid">
                                <template x-for="d in ['Mo','Tu','We','Th','Fr','Sa','Su']">
                                    <div class="fw-cal-dow" x-text="d"></div>
                                </template>
                                <template x-for="cell in calCells('depart')" :key="cell.key">
                                    <button type="button" class="fw-cal-day"
                                        :class="{
                                            'fw-empty':      cell.empty,
                                            'fw-disabled':   cell.disabled,
                                            'fw-today':      cell.today && !cell.rangeStart && !cell.selected,
                                            'fw-range-start': cell.rangeStart,
                                            'fw-in-range':   cell.inRange,
                                            'fw-range-end':  cell.rangeEnd,
                                            'fw-selected':   cell.selected && !cell.rangeStart && !cell.rangeEnd
                                        }"
                                        @click="pickDate('depart', cell)"
                                        x-text="cell.empty ? '' : cell.d">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <span x-show="errors.depart" class="fw-error" x-text="errors.depart"></span>
                </div>

                {{-- Return date - only shown for Round Trip --}}
                <div class="fw-field fw-field-15x" x-show="trip === 'Return'" x-transition.opacity.scale.95.duration.180ms style="position:relative;">
                    <div class="fw-label">RETURN DATE</div>
                    <div class="fw-input-wrap">
                        <input class="fw-input" type="text" placeholder="dd/mm/yyyy"
                            x-model="returning"
                            @click.stop="toggleCal('returning')"
                            readonly style="cursor:pointer;">
                        <div class="fw-cal" :class="{ 'fw-open': openCal === 'returning' }" @click.stop>
                            <div class="fw-cal-header">
                                <button type="button" class="fw-cal-nav" @click="prevMonth('returning')">&#8249;</button>
                                <span class="fw-cal-title" x-text="calTitle('returning')"></span>
                                <button type="button" class="fw-cal-nav" @click="nextMonth('returning')">&#8250;</button>
                            </div>
                            <div class="fw-cal-grid">
                                <template x-for="d in ['Mo','Tu','We','Th','Fr','Sa','Su']">
                                    <div class="fw-cal-dow" x-text="d"></div>
                                </template>
                                <template x-for="cell in calCells('returning')" :key="cell.key">
                                    <button type="button" class="fw-cal-day"
                                        :class="{
                                            'fw-empty':      cell.empty,
                                            'fw-disabled':   cell.disabled,
                                            'fw-today':      cell.today && !cell.rangeEnd && !cell.selected,
                                            'fw-range-start': cell.rangeStart,
                                            'fw-in-range':   cell.inRange,
                                            'fw-range-end':  cell.rangeEnd,
                                            'fw-selected':   cell.selected && !cell.rangeStart && !cell.rangeEnd
                                        }"
                                        @click="pickDate('returning', cell)"
                                        x-text="cell.empty ? '' : cell.d">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <span x-show="errors.returning" class="fw-error" x-text="errors.returning"></span>
                </div>

                {{-- Passengers --}}
                <div class="fw-field fw-field-12x" style="position:relative;">
                    <div class="fw-label">PASSENGERS</div>
                    <div class="fw-input fw-pax-trigger" @click.stop="paxOpen = !paxOpen">
                        <span x-text="(adults + childs + kids) + ' Pax'"></span>
                        <svg style="margin-left:auto;opacity:.4;flex-shrink:0" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <div class="fw-pax-dropdown" :class="{ 'fw-open': paxOpen }" @click.stop>
                        <div class="fw-pax-row">
                            <div><div class="fw-pax-lbl">Adults</div><div class="fw-pax-sub">18+ yrs</div></div>
                            <div class="fw-pax-ctr">
                                <button type="button" class="fw-pax-btn" @click="adults = Math.max(1, adults - 1)">-</button>
                                <span class="fw-pax-num" x-text="adults"></span>
                                <button type="button" class="fw-pax-btn" :disabled="totalPassengers() >= 9" @click="addPassenger('adults')">+</button>
                            </div>
                        </div>
                        <div class="fw-pax-row">
                            <div><div class="fw-pax-lbl">Children</div><div class="fw-pax-sub">2-12 yrs</div></div>
                            <div class="fw-pax-ctr">
                                <button type="button" class="fw-pax-btn" @click="childs = Math.max(0, childs - 1)">-</button>
                                <span class="fw-pax-num" x-text="childs"></span>
                                <button type="button" class="fw-pax-btn" :disabled="totalPassengers() >= 9" @click="addPassenger('childs')">+</button>
                            </div>
                        </div>
                        <div class="fw-pax-row">
                            <div><div class="fw-pax-lbl">Infants</div><div class="fw-pax-sub">Under 2</div></div>
                            <div class="fw-pax-ctr">
                                <button type="button" class="fw-pax-btn" @click="kids = Math.max(0, kids - 1)">-</button>
                                <span class="fw-pax-num" x-text="kids"></span>
                                <button type="button" class="fw-pax-btn" :disabled="totalPassengers() >= 9" @click="addPassenger('kids')">+</button>
                            </div>
                        </div>
                        <div x-show="errors.passengers || totalPassengers() >= 9" x-transition class="fw-pax-limit" x-text="errors.passengers || 'Maximum 9 passengers per booking.'"></div>
                        <button type="button" class="fw-pax-done" @click="paxOpen = false">Done</button>
                    </div>
                </div>

                {{-- Cabin --}}
                <div class="fw-field fw-field-12x fw-cabin-field">
                    <div class="fw-label">CLASS CABIN</div>
                    <button type="button" class="fw-input fw-select" @click.stop="cabinOpen = !cabinOpen" x-text="cabinLabel(flightType)"></button>
                    <div class="fw-cabin-dropdown" :class="{ 'fw-open': cabinOpen }" @click.stop>
                        <button type="button" class="fw-cabin-option" @click="flightType = 'Y'; cabinOpen = false">Economy</button>
                        <button type="button" class="fw-cabin-option" @click="flightType = 'S'; cabinOpen = false">Premium Economy</button>
                        <button type="button" class="fw-cabin-option" @click="flightType = 'C'; cabinOpen = false">Business</button>
                        <button type="button" class="fw-cabin-option" @click="flightType = 'F'; cabinOpen = false">First Class</button>
                    </div>
                </div>

            </div>
        </div>

        {{-- Multi City Panel --}}
        <div class="fw-panel-transition" x-show="trip === 'multi'" x-transition.opacity.scale.95.duration.220ms>
            <div class="fw-multi-legs">
                <template x-for="(leg, index) in multiLegs" :key="index">
                    <div class="fw-leg">
                        <span class="fw-leg-badge" x-text="index + 1"></span>

                        <div class="fw-field fw-field-2x" style="position:relative;">
                            <div class="fw-label">From</div>
                            <div class="fw-input-wrap">
                                <input class="fw-input" type="text" placeholder="City or airport"
                                    x-model="leg.from"
                                    @input="searchLegAirport($event.target.value, index, 'from')"
                                    @focus="leg.fromFocus = true; searchLegAirport(leg.from, index, 'from')"
                                    @blur="setTimeout(() => { multiLegs[index].fromFocus = false; multiLegs = [...multiLegs] }, 150)"
                                    @click.stop autocomplete="off">
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

                        <div class="fw-field fw-field-2x" style="position:relative;">
                            <div class="fw-label">To</div>
                            <div class="fw-input-wrap">
                                <input class="fw-input" type="text" placeholder="City or airport"
                                    x-model="leg.to"
                                    @input="searchLegAirport($event.target.value, index, 'to')"
                                    @focus="leg.toFocus = true; searchLegAirport(leg.to, index, 'to')"
                                    @blur="setTimeout(() => { multiLegs[index].toFocus = false; multiLegs = [...multiLegs] }, 150)"
                                    @click.stop autocomplete="off">
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

                        <div class="fw-field fw-field-15x" style="position:relative;">
                            <div class="fw-label">Date</div>
                            <div class="fw-input-wrap">
                                <input class="fw-input" type="text" placeholder="dd/mm/yyyy"
                                    x-model="leg.depart"
                                    @click.stop="toggleLegCal(index)"
                                    readonly style="cursor:pointer;">
                                <div class="fw-cal" :class="{ 'fw-open': openLegCal === index }" @click.stop>
                                    <div class="fw-cal-header">
                                        <button type="button" class="fw-cal-nav" @click="prevLegMonth(index)">&#8249;</button>
                                        <span class="fw-cal-title" x-text="legCalTitle(index)"></span>
                                        <button type="button" class="fw-cal-nav" @click="nextLegMonth(index)">&#8250;</button>
                                    </div>
                                    <div class="fw-cal-grid">
                                        <template x-for="d in ['Mo','Tu','We','Th','Fr','Sa','Su']">
                                            <div class="fw-cal-dow" x-text="d"></div>
                                        </template>
                                        <template x-for="cell in legCalCells(index)" :key="cell.key">
                                            <button type="button" class="fw-cal-day"
                                                :class="{
                                                    'fw-empty':    cell.empty,
                                                    'fw-disabled': cell.disabled,
                                                    'fw-today':    cell.today,
                                                    'fw-selected': cell.selected
                                                }"
                                                @click="pickLegDate(index, cell)"
                                                x-text="cell.empty ? '' : cell.d">
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button x-show="index >= 2" type="button" class="fw-remove-btn" @click="removeLeg(index)">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </template>
            </div>
            <button type="button" class="fw-add-leg-btn" style="margin-top:10px;" @click="addLeg" :disabled="multiLegs.length >= 6">
                + Add another flight
            </button>
        </div>

        {{-- Search Button --}}
        <div class="fw-search-row">
            <span x-show="errors.general || errors.passengers" class="fw-error" style="margin-right:auto;" x-text="errors.general || errors.passengers"></span>
            <button class="fw-search-btn" type="button" @click="search" :disabled="submitting">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.2 4.2"/></svg>
                <span x-text="submitting ? 'Searching…' : 'Search Flights'"></span>
            </button>
        </div>

        {{-- Hidden form --}}
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
</div>

<script>
function flightWidget() {
    const defaults = window.travelwheelFlightWidgetDefaults || {};
    const defaultLegs = Array.isArray(defaults.multiLegs) && defaults.multiLegs.length
        ? defaults.multiLegs
        : [
            { from:'', to:'', depart:'', cabin:'Y' },
            { from:'', to:'', depart:'', cabin:'Y' },
        ];

    return {

        // Core state
        trip:       defaults.trip || 'Return',
        from:       defaults.from || '',
        to:         defaults.to || '',
        depart:     defaults.depart || '',
        returning:  defaults.returning || '',
        adults:     Number(defaults.adults ?? 1),
        childs:     Number(defaults.childs ?? 0),
        kids:       Number(defaults.kids ?? 0),
        flightType: defaults.flightType || 'Y',
        submitting: false,

        multiLegs: defaultLegs.map(leg => ({
            from: leg.from || '',
            to: leg.to || '',
            depart: leg.depart || '',
            cabin: defaults.flightType || 'Y',
            cabinOpen:false,
            fromResults:[],
            toResults:[],
            fromFocus:false,
            toFocus:false,
            calY:new Date().getFullYear(),
            calM:new Date().getMonth()
        })),

        // UI state
        paxOpen:    false,
        cabinOpen:  false,
        openCal:    null,   // 'depart' | 'returning' | null
        openLegCal: null,   // index | null
        calState: {
            depart:    { y: new Date().getFullYear(), m: new Date().getMonth() },
            returning: { y: new Date().getFullYear(), m: new Date().getMonth() },
        },

        // Airport data
        airports:    [],
        fromResults: [],
        toResults:   [],
        fromFocus:   false,
        toFocus:     false,

        // Errors
        errors: {},

        // ------------------------------------------------------------
        //  INIT
        // ------------------------------------------------------------
        init() {
            fetch('{{ asset('assets/data/airports.json') }}')
                .then(r => r.json())
                .then(d => { this.airports = d; })
                .catch(e => console.error('[FW] airports.json:', e));

            // Global click closes all dropdowns/calendars
            document.addEventListener('click', () => {
                this.fromFocus  = false;
                this.toFocus    = false;
                this.openCal    = null;
                this.openLegCal = null;
                this.paxOpen    = false;
                this.cabinOpen  = false;
                this.multiLegs = this.multiLegs.map(leg => ({ ...leg, cabinOpen: false }));
            });
        },

        // ------------------------------------------------------------
        //  HELPERS
        // ------------------------------------------------------------

        // Parse "dd/mm/yyyy" to midnight Date (local), or null
        _parseDate(str) {
            if (!str) return null;
            const p = str.split('/');
            if (p.length !== 3) return null;
            const d = new Date(+p[2], +p[1] - 1, +p[0]);
            d.setHours(0, 0, 0, 0);
            return isNaN(d.getTime()) ? null : d;
        },

        // Format a Date to "dd/mm/yyyy"
        _fmtDate(date) {
            return String(date.getDate()).padStart(2,'0') + '/' +
                   String(date.getMonth() + 1).padStart(2,'0') + '/' +
                   date.getFullYear();
        },

        // ------------------------------------------------------------
        //  TRIP TABS
        // ------------------------------------------------------------
        setTrip(value) {
            this.trip    = value;
            this.openCal = null;
            // Switching away from Return: clear return date
            if (value !== 'Return') {
                this.returning = '';
            }
        },

        // ------------------------------------------------------------
        //  AIRPORT AUTOCOMPLETE
        // ------------------------------------------------------------
        airportSearch(q) {
            q = (q || '').toLowerCase().trim();
            if (q.length < 2) return [];
            return this.airports.filter(a =>
                (a.iata    && a.iata.toLowerCase().startsWith(q))  ||
                (a.city    && a.city.toLowerCase().includes(q))    ||
                (a.name    && a.name.toLowerCase().includes(q))    ||
                (a.country && a.country.toLowerCase().includes(q))
            ).slice(0, 8);
        },

        searchAirport(val, target) {
            if (target === 'fromResults') {
                this.fromResults = this.airportSearch(val);
                this.fromFocus   = true;
            } else {
                this.toResults = this.airportSearch(val);
                this.toFocus   = true;
            }
        },

        selectAirport(field, airport) {
            const display = (airport.city || airport.name) + ' (' + airport.iata + ')';

            if (field === 'from') {
                this.from        = display;
                this.fromResults = [];
                this.fromFocus   = false;
            } else {
                this.to        = display;
                this.toResults = [];
                this.toFocus   = false;
            }
        },

        // Multi-leg autocomplete
        searchLegAirport(val, index, field) {
            const results = this.airportSearch(val);
            const leg = { ...this.multiLegs[index] };
            if (field === 'from') { leg.fromResults = results; leg.fromFocus = true; }
            else                  { leg.toResults   = results; leg.toFocus   = true; }
            this.multiLegs[index] = leg;
            this.multiLegs = [...this.multiLegs];
        },

        selectLegAirport(index, field, airport) {
            const val = (airport.city || airport.name) + ' (' + airport.iata + ')';

            const leg = { ...this.multiLegs[index] };

            if (field === 'from') {
                leg.from = val;
                leg.fromResults = [];
                leg.fromFocus = false;
            } else {
                leg.to = val;
                leg.toResults = [];
                leg.toFocus = false;
            }

            this.multiLegs[index] = leg;
            this.multiLegs = [...this.multiLegs];
        },


        // ------------------------------------------------------------
        //  MISC
        // ------------------------------------------------------------
        swapRoutes() { [this.from, this.to] = [this.to, this.from]; },
        closePax()   { this.paxOpen = false; },
        cabinLabel(value) {
            return ({ Y: 'Economy', S: 'Premium Economy', C: 'Business', F: 'First Class' })[value] || 'Economy';
        },
        setSharedCabin(value) {
            this.flightType = value;
            this.cabinOpen = false;
            this.multiLegs = this.multiLegs.map(leg => ({ ...leg, cabin: value, cabinOpen: false }));
        },
        totalPassengers() { return this.adults + this.childs + this.kids; },
        addPassenger(type) {
            if (this.totalPassengers() >= 9) {
                this.errors.passengers = 'The total number of passengers must not exceed 9 per booking.';
                return;
            }

            this[type]++;
            delete this.errors.passengers;
        },
        addLeg() {
            this.multiLegs.push({
                from:'', to:'', depart:'', cabin:this.flightType, cabinOpen:false,
                fromResults:[], toResults:[],
                fromFocus:false, toFocus:false,
                calY: new Date().getFullYear(), calM: new Date().getMonth(),
            });
        },
        removeLeg(index) { this.multiLegs.splice(index, 1); },

        // ------------------------------------------------------------
        //  CALENDAR
        // ------------------------------------------------------------
        MONTHS: ['January','February','March','April','May','June',
                 'July','August','September','October','November','December'],

        calTitle(field) {
            const s = this.calState[field];
            return this.MONTHS[s.m] + ' ' + s.y;
        },

        prevMonth(field) {
            const s = this.calState[field];
            if (--s.m < 0) { s.m = 11; s.y--; }
        },

        nextMonth(field) {
            const s = this.calState[field];
            if (++s.m > 11) { s.m = 0; s.y++; }
        },

        toggleCal(field) {
            if (this.openCal === field) {
                this.openCal = null;
                return;
            }

            // Sync calendar view month
            if (field === 'returning') {
                // Always open from the depart date (or today if none set)
                const anchor = this._parseDate(this.depart) || new Date();
                this.calState.returning = { y: anchor.getFullYear(), m: anchor.getMonth() };
            } else {
                // Open from the currently selected date, or today
                const anchor = this._parseDate(this[field]) || new Date();
                this.calState[field] = { y: anchor.getFullYear(), m: anchor.getMonth() };
            }

            this.openCal    = field;
            this.openLegCal = null;
        },

        closeAllCals() {
            this.openCal    = null;
            this.openLegCal = null;
        },

        // Build the array of cell objects for a given calendar field
        calCells(field) {
            const s   = this.calState[field];
            const now = new Date(); now.setHours(0, 0, 0, 0);

            const departDate  = this._parseDate(this.depart);
            const returnDate  = this._parseDate(this.returning);

            const startDow = (new Date(s.y, s.m, 1).getDay() + 6) % 7;
            const lastDay  = new Date(s.y, s.m + 1, 0).getDate();
            const cells    = [];

            // Empty lead cells for day-of-week alignment
            for (let i = 0; i < startDow; i++) {
                cells.push({ key: 'e' + i, empty: true });
            }

            for (let d = 1; d <= lastDay; d++) {
                const date = new Date(s.y, s.m, d);
                date.setHours(0, 0, 0, 0);

                // Disabled logic
                // All past dates are always disabled
                let disabled = date < now;

                // Return calendar: also disable dates strictly before depart
                if (!disabled && field === 'returning' && departDate && date < departDate) {
                    disabled = true;
                }

                // Range-highlight logic
                // We compute these the same way for both calendars so the
                // full range is visible when navigating either calendar.
                const isDepart = departDate && date.getTime() === departDate.getTime();
                const isReturn = returnDate && date.getTime() === returnDate.getTime();
                const between  = departDate && returnDate && date > departDate && date < returnDate;

                // rangeStart = the depart date (only when a return is also set)
                const rangeStart = isDepart && !!returnDate;
                // rangeEnd   = the return date (only when a depart is also set)
                const rangeEnd   = isReturn && !!departDate;
                // inRange    = strictly between depart and return
                const inRange    = !!between;
                // selected   = the "own" date of this calendar without a pair
                //              (e.g. depart selected but no return yet)
                const selected   = (isDepart && !returnDate) || (isReturn && !departDate);

                cells.push({
                    key:        s.y + '-' + s.m + '-' + d,
                    d,
                    y:          s.y,
                    m:          s.m,
                    empty:      false,
                    disabled,
                    today:      date.getTime() === now.getTime(),
                    selected,
                    rangeStart,
                    inRange,
                    rangeEnd,
                });
            }

            return cells;
        },

        pickDate(field, cell) {
            if (cell.empty || cell.disabled) return;

            const picked = new Date(cell.y, cell.m, cell.d);
            picked.setHours(0, 0, 0, 0);
            const formatted = this._fmtDate(picked);

            if (field === 'depart') {
                this.depart = formatted;

                // If existing return is now before depart, clear it
                const ret = this._parseDate(this.returning);
                if (ret && ret < picked) {
                    this.returning = '';
                }

                if (this.trip === 'Return') {
                    if (!this.returning) {
                        // Auto-fill return = depart + 1 day
                        const next = new Date(picked);
                        next.setDate(next.getDate() + 1);
                        this.returning = this._fmtDate(next);
                    }
                    // Auto-open return calendar, starting from depart month
                    this.calState.returning = { y: cell.y, m: cell.m };
                    this.openCal = 'returning';
                } else {
                    this.openCal = null;
                }

            } else {
                // Picking return date: just save and close
                this.returning = formatted;
                this.openCal   = null;
            }
        },

        // Multi-leg calendars
        legCalTitle(index) {
            const leg = this.multiLegs[index];
            return this.MONTHS[leg.calM] + ' ' + leg.calY;
        },

        prevLegMonth(index) {
            const leg = this.multiLegs[index];
            if (--leg.calM < 0) { leg.calM = 11; leg.calY--; }
            this.multiLegs = [...this.multiLegs];
        },

        nextLegMonth(index) {
            const leg = this.multiLegs[index];
            if (++leg.calM > 11) { leg.calM = 0; leg.calY++; }
            this.multiLegs = [...this.multiLegs];
        },

        legCalCells(index) {
            const leg = this.multiLegs[index];
            const now = new Date(); now.setHours(0, 0, 0, 0);
            const sel = this._parseDate(leg.depart);

            // Minimum selectable date = later of today OR previous leg's date
            const prevDate = index > 0 ? this._parseDate(this.multiLegs[index - 1].depart) : null;
            const minDate  = prevDate && prevDate > now ? prevDate : now;

            const startDow = (new Date(leg.calY, leg.calM, 1).getDay() + 6) % 7;
            const lastDay  = new Date(leg.calY, leg.calM + 1, 0).getDate();
            const cells    = [];

            for (let i = 0; i < startDow; i++) cells.push({ key: 'e' + i, empty: true });

            for (let d = 1; d <= lastDay; d++) {
                const date = new Date(leg.calY, leg.calM, d);
                date.setHours(0, 0, 0, 0);
                cells.push({
                    key:      leg.calY + '-' + leg.calM + '-' + d,
                    d,
                    y:        leg.calY,
                    m:        leg.calM,
                    empty:    false,
                    disabled: date < minDate,
                    today:    date.getTime() === now.getTime(),
                    selected: sel && date.getTime() === sel.getTime(),
                });
            }
            return cells;
        },

        pickLegDate(index, cell) {
            if (cell.empty || cell.disabled) return;

            const picked = new Date(cell.y, cell.m, cell.d);
            picked.setHours(0, 0, 0, 0);

            this.multiLegs[index].depart = this._fmtDate(picked);

            // Clear any later leg dates that are now before this picked date
            for (let i = index + 1; i < this.multiLegs.length; i++) {
                const existing = this._parseDate(this.multiLegs[i].depart);
                if (existing && existing < picked) {
                    this.multiLegs[i].depart = '';
                }
            }

            // Auto-fill the very next leg with picked date + 1 day (if empty)
            const nextIndex = index + 1;
            if (nextIndex < this.multiLegs.length && !this.multiLegs[nextIndex].depart) {
                const next = new Date(picked);
                next.setDate(next.getDate() + 1);
                this.multiLegs[nextIndex].depart = this._fmtDate(next);
                // Sync next leg calendar view to that month
                this.multiLegs[nextIndex].calY = next.getFullYear();
                this.multiLegs[nextIndex].calM = next.getMonth();
            }

            this.multiLegs  = [...this.multiLegs];
            this.openLegCal = null;
        },

        toggleLegCal(index) {
            if (this.openLegCal === index) {
                this.openLegCal = null;
                return;
            }

            // Sync calendar view: own date > previous leg date > today
            const leg     = this.multiLegs[index];
            const ownDate = this._parseDate(leg.depart);
            const prevDate = index > 0 ? this._parseDate(this.multiLegs[index - 1].depart) : null;
            const anchor  = ownDate || prevDate || new Date();

            this.multiLegs[index].calY = anchor.getFullYear();
            this.multiLegs[index].calM = anchor.getMonth();
            this.multiLegs = [...this.multiLegs];

            this.openLegCal = index;
            this.openCal    = null;
        },

        // ------------------------------------------------------------
        //  VALIDATION & SEARCH
        // ------------------------------------------------------------
        validate() {
            this.errors = {};
            if (this.totalPassengers() > 9) {
                this.errors.passengers = 'The total number of passengers must not exceed 9 per booking.';
            }
            if (this.kids > this.adults) {
                this.errors.passengers = 'Each infant must be accompanied by an adult.';
            }
            if (this.trip !== 'multi') {
                if (!this.from.trim())   this.errors.from    = 'Please enter a departure city or airport.';
                if (!this.to.trim())     this.errors.to      = 'Please enter a destination city or airport.';
                if (!this.depart.trim()) this.errors.depart  = 'Please select a departure date.';
                if (this.from.trim() && this.from.trim() === this.to.trim())
                    this.errors.to = 'Origin and destination must be different.';
                if (this.trip === 'Return' && !this.returning.trim())
                    this.errors.returning = 'Please select a return date.';
            } else {
                if (this.multiLegs.some(l => !l.from || !l.to || !l.depart))
                    this.errors.general = 'Please complete all flight legs.';
                if (this.multiLegs.some(l => l.from && l.from === l.to))
                    this.errors.general = 'Each flight leg must have a different origin and destination.';
                for (let i = 1; i < this.multiLegs.length; i++) {
                    const previous = this._parseDate(this.multiLegs[i - 1].depart);
                    const current = this._parseDate(this.multiLegs[i].depart);
                    if (previous && current && current < previous) {
                        this.errors.general = 'Multi-city dates must be in travel order.';
                        break;
                    }
                }
            }
            return Object.keys(this.errors).length === 0;
        },

        search() {
            if (this.submitting || !this.validate()) return;
            this.submitting = true;
            if (this.trip === 'multi') {
                this.multiLegs = this.multiLegs.map(leg => ({ ...leg, cabin: this.flightType, cabinOpen: false }));
            }
            document.getElementById('fw-form').submit();
        },
    };
}
</script>
