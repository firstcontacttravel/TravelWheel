@component('layouts.app', ['title' => 'Searching Flights'])
@php
    $trip = strtolower((string) ($search['trip'] ?? 'oneway'));
    $from = $search['from'] ?? '';
    $to = $search['to'] ?? '';
    $depart = $search['depart'] ?? '';
    $returning = $search['returning'] ?? '';
    $adults = (int) ($search['adults'] ?? 1);
    $children = (int) ($search['childs'] ?? 0);
    $infants = (int) ($search['kids'] ?? 0);
    $pax = $adults + $children + $infants;
    $cabin = ['Y' => 'Economy', 'S' => 'Premium Economy', 'C' => 'Business', 'F' => 'First'][$search['flight_type'] ?? 'Y'] ?? 'Economy';
    $routeLabel = $trip === 'multi'
        ? 'Multi-city trip'
        : trim(($from ?: 'Origin') . ' to ' . ($to ?: 'Destination'));
@endphp

<style>
    :root {
        --tw-brand: #39328f;
        --tw-brand-dark: #24205f;
        --tw-green: #049a63;
        --tw-ink: #101828;
        --tw-muted: #667085;
        --tw-soft: #f7f8fb;
        --tw-line: #e6e9f0;
        --tw-amber: #d97706;
    }
    body { background: #f7f8fb; }
    .fl-wrap { max-width: 1180px; margin: 0 auto; padding: 30px 18px 72px; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color: var(--tw-ink); }
    .fl-grid { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(330px, .75fr); gap: 22px; align-items: start; }
    .fl-card { background: #fff; border: 1px solid var(--tw-line); border-radius: 10px; box-shadow: 0 18px 45px rgba(16,24,40,.07); overflow: hidden; }
    .fl-main { padding: 28px; }
    .fl-kicker { display: inline-flex; align-items: center; gap: 8px; padding: 7px 11px; border-radius: 999px; background: #f5f7ff; border: 1px solid rgba(57,50,143,.14); color: var(--tw-brand); font-size: 12px; font-weight: 800; }
    .fl-title { margin-top: 18px; font-size: clamp(28px, 5vw, 50px); line-height: 1.04; letter-spacing: 0; font-weight: 900; }
    .fl-copy { margin-top: 14px; max-width: 720px; font-size: 15px; line-height: 1.7; color: var(--tw-muted); }
    .fl-route { margin-top: 22px; display: flex; flex-wrap: wrap; gap: 10px; }
    .fl-pill { display: inline-flex; align-items: center; gap: 8px; min-height: 34px; padding: 8px 12px; border: 1px solid var(--tw-line); border-radius: 999px; background: #fbfcfe; font-size: 13px; font-weight: 750; color: var(--tw-ink); }
    .fl-plane { width: 68px; height: 68px; border-radius: 18px; background: var(--tw-brand); color: #fff; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 16px 28px rgba(57,50,143,.24); animation: flPlane 2.4s ease-in-out infinite; }
    @keyframes flPlane { 0%,100% { transform: translateY(0) rotate(-8deg); } 50% { transform: translateY(-8px) rotate(4deg); } }
    .fl-progress { margin-top: 26px; height: 10px; border-radius: 999px; background: #eef1f6; overflow: hidden; }
    .fl-progress span { display: block; height: 100%; width: 8%; border-radius: inherit; background: linear-gradient(90deg,var(--tw-brand),#246b74,var(--tw-green)); transition: width .6s ease; }
    .fl-status { margin-top: 12px; display: flex; justify-content: space-between; gap: 12px; font-size: 12px; font-weight: 800; color: var(--tw-muted); }
    .fl-steps { margin-top: 24px; display: grid; gap: 10px; }
    .fl-step { display: flex; align-items: center; gap: 12px; padding: 13px 14px; border: 1px solid var(--tw-line); border-radius: 9px; background: #fff; color: var(--tw-muted); transition: border-color .25s, background .25s, color .25s, transform .25s; }
    .fl-step.is-active { border-color: rgba(57,50,143,.38); background: #fbfcff; color: var(--tw-ink); transform: translateX(3px); }
    .fl-step.is-done { border-color: rgba(4,154,99,.28); background: #f5fffa; color: var(--tw-ink); }
    .fl-dot { width: 22px; height: 22px; border-radius: 999px; border: 2px solid #cfd4df; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; }
    .fl-step.is-active .fl-dot { border-color: var(--tw-brand); }
    .fl-step.is-done .fl-dot { border-color: var(--tw-green); background: var(--tw-green); }
    .fl-step.is-done .fl-dot::after { content: ""; width: 8px; height: 5px; border-left: 2px solid #fff; border-bottom: 2px solid #fff; transform: rotate(-45deg); margin-top: -2px; }
    .fl-step strong { display: block; font-size: 13px; }
    .fl-step small { display: block; margin-top: 2px; font-size: 12px; color: var(--tw-muted); }
    .fl-feed { padding: 20px; }
    .fl-feed-title { font-size: 15px; font-weight: 900; }
    .fl-feed-sub { margin-top: 4px; font-size: 12.5px; color: var(--tw-muted); line-height: 1.55; }
    .fl-source-list { margin-top: 16px; display: grid; gap: 9px; }
    .fl-source { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 12px; border-radius: 8px; background: #fbfcfe; border: 1px solid #eef1f6; font-size: 12.5px; color: var(--tw-muted); }
    .fl-source strong { color: var(--tw-ink); }
    .fl-source em { font-style: normal; color: var(--tw-green); font-weight: 800; opacity: 0; transition: opacity .25s; }
    .fl-source.is-on em { opacity: 1; }
    .fl-products { margin-top: 18px; display: grid; gap: 12px; }
    .fl-product { display: grid; grid-template-columns: 44px 1fr; gap: 12px; padding: 14px; border-radius: 9px; border: 1px solid var(--tw-line); background: #fff; }
    .fl-product-icon { width: 44px; height: 44px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; background: #f5f7ff; color: var(--tw-brand); }
    .fl-product-title { font-size: 13px; font-weight: 900; }
    .fl-product-copy { margin-top: 3px; font-size: 12px; line-height: 1.5; color: var(--tw-muted); }
    .fl-skeleton { margin-top: 22px; display: grid; gap: 10px; }
    .fl-skel-row { height: 74px; border-radius: 9px; background: linear-gradient(90deg,#f0f2f6 0%,#fbfcfe 45%,#f0f2f6 100%); background-size: 220% 100%; animation: flShimmer 1.3s infinite linear; border: 1px solid #eef1f6; }
    @keyframes flShimmer { from { background-position: 200% 0; } to { background-position: -20% 0; } }
    .fl-error { display: none; margin-top: 18px; padding: 14px; border-radius: 9px; border: 1px solid #fecaca; background: #fef2f2; color: #b91c1c; font-size: 13px; line-height: 1.5; }
    .fl-error a { color: #7f1d1d; font-weight: 900; }
    .fl-wrap {
        max-width: none;
        margin: 0;
        padding: 0;
        background: #eaf2f6;
    }
    .fl-grid { display: none; }
    .lw-search-bar {
        background: #19333b;
        padding: 42px 18px;
    }
    .lw-search-inner {
        max-width: 1320px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1.05fr .92fr 1.05fr 2fr 1.18fr 140px;
        gap: 6px;
    }
    .lw-box {
        min-height: 76px;
        background: #294a55;
        padding: 16px 18px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 0;
    }
    .lw-label {
        color: rgba(255,255,255,.68);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 750;
    }
    .lw-value {
        margin-top: 4px;
        color: #fff;
        font-size: 20px;
        line-height: 1.12;
        font-weight: 800;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .lw-search-btn {
        border: 1px solid #f47a20;
        background: #fff;
        color: #f47a20;
        font-size: 15px;
        font-weight: 850;
    }
    .lw-hero {
        min-height: 230px;
        display: grid;
        place-items: center;
        background: #fff;
    }
    .lw-mark {
        width: 82px;
        height: 82px;
        color: #ff6558;
        animation: flPlane 2.2s ease-in-out infinite;
    }
    .lw-bar {
        height: 8px;
        background: #19333b;
        overflow: hidden;
    }
    .lw-bar span {
        display: block;
        height: 100%;
        width: 8%;
        background: #f7d51d;
        transition: width .45s ease;
    }
    .lw-content {
        text-align: center;
        padding: 72px 18px 48px;
    }
    .lw-title {
        margin: 0;
        font-size: clamp(22px, 2.2vw, 32px);
        line-height: 1.25;
        font-weight: 900;
        letter-spacing: 0;
    }
    .lw-sub {
        margin: 12px auto 0;
        max-width: 620px;
        color: #6b7280;
        font-size: 14px;
        line-height: 1.65;
    }
    .lw-status {
        margin-top: 20px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-height: 36px;
        padding: 8px 14px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid rgba(20,45,52,.12);
        box-shadow: 0 10px 26px rgba(16,24,40,.06);
        color: #142d34;
        font-size: 13px;
        font-weight: 800;
    }
    .lw-pulse {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #049a63;
        box-shadow: 0 0 0 0 rgba(4,154,99,.35);
        animation: lwPulse 1.25s infinite;
    }
    .lw-products {
        margin: 34px auto 0;
        max-width: 800px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }
    .lw-product {
        background: rgba(255,255,255,.76);
        border: 1px solid rgba(20,45,52,.1);
        border-radius: 8px;
        padding: 14px;
        text-align: left;
    }
    .lw-product strong {
        display: block;
        color: #142d34;
        font-size: 13px;
        font-weight: 900;
    }
    .lw-product span {
        display: block;
        margin-top: 4px;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.45;
    }
    .lw-runner { display: none; width: 0; height: 0; border: 0; }
    @keyframes lwPulse {
        70% { box-shadow: 0 0 0 10px rgba(4,154,99,0); }
        100% { box-shadow: 0 0 0 0 rgba(4,154,99,0); }
    }
    @media (max-width: 900px) {
        .fl-grid { grid-template-columns: 1fr; }
        .fl-main { padding: 22px; }
        .lw-search-inner { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .lw-search-btn { grid-column: 1 / -1; min-height: 58px; }
    }
    @media (max-width: 640px) {
        .fl-wrap { padding: 0; }
        .fl-route { flex-direction: column; }
        .fl-pill { border-radius: 8px; }
        .lw-search-bar { padding: 20px 12px; }
        .lw-search-inner { grid-template-columns: 1fr; }
        .lw-box { min-height: 62px; padding: 12px 14px; }
        .lw-value { font-size: 17px; }
        .lw-hero { min-height: 188px; }
        .lw-content { padding: 44px 12px 36px; }
        .lw-products { grid-template-columns: 1fr; }
    }
</style>

<div class="fl-wrap" x-data="flightLoading()" x-init="start()">
    <section class="lw-search-bar">
        <div class="lw-search-inner">
            <div class="lw-box">
                <div class="lw-label">Trip Type</div>
                <div class="lw-value">{{ $trip === 'return' ? 'Round Trip' : ($trip === 'multi' ? 'Multi City' : 'One Way') }}</div>
            </div>
            <div class="lw-box">
                <div class="lw-label">From</div>
                <div class="lw-value">{{ $from ?: 'Departure' }}</div>
            </div>
            <div class="lw-box">
                <div class="lw-label">To</div>
                <div class="lw-value">{{ $to ?: 'Destination' }}</div>
            </div>
            <div class="lw-box">
                <div class="lw-label">Departure</div>
                <div class="lw-value">{{ $depart ?: '-' }}@if($trip === 'return' && $returning) - {{ $returning }}@endif</div>
            </div>
            <div class="lw-box">
                <div class="lw-label">Passenger</div>
                <div class="lw-value">{{ $pax }} Passenger{{ $pax === 1 ? '' : 's' }}, {{ $cabin }}</div>
            </div>
            <button type="button" class="lw-search-btn">Search</button>
        </div>
    </section>

    <section class="lw-hero">
        <svg class="lw-mark" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M22 54V20h24v34"/>
            <path d="M18 54h32"/>
            <path d="M27 27h4M37 27h4M27 35h4M37 35h4M27 43h4M37 43h4"/>
            <path d="M26 20v-6h16v6"/>
            <path d="M13 54V40"/>
            <path d="M8 46c0-5 3-9 5-9s5 4 5 9-3 8-5 8-5-3-5-8Z"/>
            <path d="M50 54c5 0 8-2 8-5s-3-5-6-4c-1-4-6-5-9-2"/>
        </svg>
    </section>

    <div class="lw-bar"><span x-bind:style="'width:' + progress + '%'"></span></div>

    <main class="lw-content">
        <h1 class="lw-title">Please wait as we find the best flight options for you</h1>
        <p class="lw-sub">We are comparing live airline availability and fare combinations for {{ $routeLabel }}.</p>

        <div class="lw-status">
            <span class="lw-pulse"></span>
            <span x-text="statusText"></span>
        </div>

        <div class="lw-products">
            <div class="lw-product">
                <strong>TravelFlex</strong>
                <span>Eligible fares can be split into simple instalments.</span>
            </div>
            <div class="lw-product">
                <strong>Hotels</strong>
                <span>Add a stay after choosing your preferred flight.</span>
            </div>
            <div class="lw-product">
                <strong>Visa support</strong>
                <span>Get guidance for documents and airport protocol.</span>
            </div>
        </div>

        <div class="fl-error" x-ref="errorBox">
            This search is taking longer than expected. <a href="{{ route('air') }}">Start a new search</a> or wait a moment.
        </div>
    </main>

    <div x-ref="runnerWrap"></div>

    <div class="fl-grid">
        <section class="fl-card fl-main">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;">
                <div>
                    <div class="fl-kicker">
                        <span>Live flight search</span>
                    </div>
                    <h1 class="fl-title">Finding the best flights for your trip</h1>
                    <p class="fl-copy">We are comparing airline availability, fare rules, baggage options, and TravelFlex eligibility before showing your results.</p>
                </div>
                <div class="fl-plane" aria-hidden="true">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M17.8 19.2 16 11l4.8-4.8c1-1 1.3-2.4.6-3.1s-2.1-.4-3.1.6L13.5 8.5 5.3 6.7 3.9 8.1l6.7 3.5-4.1 4.1-2.6-.4-1.1 1.1 3.8 1.9 1.9 3.8 1.1-1.1-.4-2.6 4.1-4.1 3.5 6.7z"/></svg>
                </div>
            </div>

            <div class="fl-route">
                <div class="fl-pill">{{ $routeLabel }}</div>
                <div class="fl-pill">{{ $depart ?: 'Departure date' }}@if($trip === 'return' && $returning) - {{ $returning }}@endif</div>
                <div class="fl-pill">{{ $pax }} passenger{{ $pax === 1 ? '' : 's' }}</div>
                <div class="fl-pill">{{ $cabin }}</div>
            </div>

            <div class="fl-progress"><span x-bind:style="'width:' + progress + '%'"></span></div>
            <div class="fl-status"><span x-text="statusText"></span><span x-text="progress + '%'"></span></div>

            <div class="fl-steps">
                <template x-for="(step, index) in steps" :key="step.title">
                    <div class="fl-step" :class="{ 'is-active': index === activeStep, 'is-done': index < activeStep }">
                        <span class="fl-dot"></span>
                        <span><strong x-text="step.title"></strong><small x-text="step.copy"></small></span>
                    </div>
                </template>
            </div>

            <div class="fl-skeleton" aria-hidden="true">
                <div class="fl-skel-row"></div>
                <div class="fl-skel-row"></div>
            </div>

            <div class="fl-error" x-ref="errorBox">
                The search is taking longer than expected. <a href="{{ route('air') }}">Start a new search</a> or wait a moment and refresh.
            </div>
        </section>

        <aside>
            <div class="fl-card fl-feed">
                <div class="fl-feed-title">Checking travel partner feeds</div>
                <div class="fl-feed-sub">Comparing thousands of fare combinations across airline and travel partner feeds.</div>
                <div class="fl-source-list">
                    <template x-for="(source, index) in sources" :key="source">
                        <div class="fl-source" :class="{ 'is-on': index <= activeSource }">
                            <strong x-text="source"></strong>
                            <em x-text="index < activeSource ? 'Checked' : (index === activeSource ? 'Checking' : 'Queued')"></em>
                        </div>
                    </template>
                </div>
            </div>

            <div class="fl-products">
                <article class="fl-product">
                    <span class="fl-product-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5z"/><path d="m9 12 2 2 4-4"/></svg></span>
                    <div><div class="fl-product-title">TravelFlex</div><div class="fl-product-copy">Spread eligible flight payments while keeping your route locked in.</div></div>
                </article>
                <article class="fl-product">
                    <span class="fl-product-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 22V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14"/><path d="M9 22v-6h6v6"/><path d="M8 10h.01M12 10h.01M16 10h.01M8 14h.01M16 14h.01"/></svg></span>
                    <div><div class="fl-product-title">Hotels and stays</div><div class="fl-product-copy">Add a stay after choosing your flight for a smoother trip plan.</div></div>
                </article>
                <article class="fl-product">
                    <span class="fl-product-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/><path d="M8 14h8"/></svg></span>
                    <div><div class="fl-product-title">Visa and protocol support</div><div class="fl-product-copy">Get help with travel documents, airport protocol, and lounge access.</div></div>
                </article>
            </div>
        </aside>
    </div>
</div>

<script>
function flightLoading() {
    return {
        progress: 6,
        activeStep: 0,
        activeSource: 0,
        statusText: 'Preparing your search',
        steps: [
            { title: 'Reading your route', copy: 'Checking dates, passenger mix, and cabin preference.' },
            { title: 'Checking airline availability', copy: 'Looking for live seats and valid fare classes.' },
            { title: 'Comparing fare combinations', copy: 'Reviewing price, stops, duration, and baggage rules.' },
            { title: 'Finding TravelFlex options', copy: 'Marking refundable and installment-eligible fares.' },
            { title: 'Preparing results', copy: 'Sorting the best options for easy comparison.' },
        ],
        sources: ['Airline inventory', 'Fare families', 'Baggage rules', 'Refundability checks', 'TravelFlex eligibility', 'Travel partner feeds'],
        start() {
            const labels = [
                'Preparing your search',
                'Checking airline availability',
                'Comparing fare combinations',
                'Reviewing baggage and cabin rules',
                'Sorting the best options'
            ];
            const interval = setInterval(() => {
                this.progress = Math.min(98, this.progress + (this.progress < 72 ? 7 : 2));
                this.activeStep = Math.min(this.steps.length - 1, Math.floor(this.progress / 22));
                this.activeSource = Math.min(this.sources.length - 1, Math.floor(this.progress / 16));
                this.statusText = labels[Math.min(labels.length - 1, this.activeStep)];
            }, 650);

            setTimeout(() => {
                const frame = document.createElement('iframe');
                frame.className = 'lw-runner';
                frame.src = '{{ route('flights.search.run') }}';
                frame.onload = () => {
                    clearInterval(interval);
                    this.progress = 100;
                    this.statusText = 'Opening your results';
                    setTimeout(() => { window.location.href = '{{ route('air.flight-s') }}'; }, 350);
                };
                this.$refs.runnerWrap.appendChild(frame);
            }, 900);

            setTimeout(() => {
                this.$refs.errorBox.style.display = 'block';
            }, 70000);
        }
    };
}
</script>
@endcomponent
