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
    $tripLabel = $trip === 'return' ? 'Round Trip' : ($trip === 'multi' ? 'Multi City' : 'One Way');
    $multiLegsRaw = $search['multi_legs'] ?? [];
    $multiLegs = is_string($multiLegsRaw) ? json_decode($multiLegsRaw, true) : $multiLegsRaw;
    $multiLegs = is_array($multiLegs)
        ? array_values(array_filter($multiLegs, fn ($leg) => is_array($leg) && (! empty($leg['from']) || ! empty($leg['to']) || ! empty($leg['depart']))))
        : [];
    $firstLeg = $multiLegs[0] ?? [];
    $lastLeg = $multiLegs ? $multiLegs[array_key_last($multiLegs)] : [];
    $routeLabel = $trip === 'multi'
        ? trim(($firstLeg['from'] ?? 'Origin') . ' to ' . ($lastLeg['to'] ?? 'Destination'))
        : trim(($from ?: 'Origin') . ' to ' . ($to ?: 'Destination'));
    $dateLabel = match ($trip) {
        'return' => trim(($depart ?: '-') . (($returning ?? '') ? ' - ' . $returning : '')),
        'multi' => $multiLegs
            ? trim(($firstLeg['depart'] ?? '-') . ((count($multiLegs) > 1 && ! empty($lastLeg['depart'])) ? ' - ' . $lastLeg['depart'] : ''))
            : '-',
        default => $depart ?: '-',
    };
    $legSummary = $trip === 'multi' ? count($multiLegs) . ' leg' . (count($multiLegs) === 1 ? '' : 's') : ($trip === 'return' ? 'Outbound + return' : 'Direct search');
@endphp

<style>
    :root { --tw-loader-nav-height: 113px; }
    html, body { height: 100%; overflow: hidden; background: #f7f8fb; }
    body > div > .footter { display: none !important; }
    main.navbarmain.upper-space {
        height: calc(100vh - var(--tw-loader-nav-height));
        height: calc(100dvh - var(--tw-loader-nav-height));
        margin-top: var(--tw-loader-nav-height) !important;
        padding-top: 0 !important;
        overflow: hidden;
    }

    .tw-loader {
        height: 100%;
        overflow: hidden;
        background:
            radial-gradient(circle at 18% 8%, rgba(57,50,143,.08), transparent 28%),
            radial-gradient(circle at 82% 18%, rgba(4,154,99,.08), transparent 26%),
            #f7f8fb;
        color: #101828;
        font-family: var(--font-primary, 'Open Sans', Arial, sans-serif);
    }

    .tw-loader-shell {
        width: min(1120px, calc(100% - 32px));
        height: 100%;
        margin: 0 auto;
        padding: clamp(12px, 3vh, 34px) 0 clamp(12px, 3vh, 32px);
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .tw-search-summary {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1.2fr 1.1fr;
        gap: 1px;
        overflow: hidden;
        border: 1px solid rgba(57,50,143,.12);
        border-radius: 14px;
        background: #e4e8f0;
        box-shadow: 0 18px 45px rgba(16,24,40,.06);
    }

    .tw-summary-item {
        min-width: 0;
        padding: 16px 18px;
        background: rgba(255,255,255,.94);
    }

    .tw-summary-label {
        display: block;
        color: #667085;
        font-size: 11px;
        line-height: 1.2;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 800;
    }

    .tw-summary-value {
        display: block;
        margin-top: 6px;
        color: #101828;
        font-size: 16px;
        line-height: 1.25;
        font-weight: 850;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .tw-leg-strip {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .tw-leg-chip {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        border: 1px solid #e6e9f0;
        border-radius: 12px;
        background: rgba(255,255,255,.86);
        padding: 11px 12px;
        box-shadow: 0 10px 25px rgba(16,24,40,.04);
    }

    .tw-leg-no {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        background: #f5f7ff;
        color: #39328f;
        font-size: 12px;
        font-weight: 900;
    }

    .tw-leg-main {
        min-width: 0;
        color: #101828;
        font-size: 13px;
        line-height: 1.35;
        font-weight: 850;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .tw-leg-date {
        margin-top: 2px;
        color: #667085;
        font-size: 11.5px;
        font-weight: 700;
    }

    .tw-loader-card {
        margin: clamp(10px, 2.5vh, 28px) auto 0;
        max-width: 760px;
        width: 100%;
        min-height: 0;
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
        background: rgba(255,255,255,.86);
        border: 1px solid rgba(230,233,240,.95);
        border-radius: 18px;
        box-shadow: 0 24px 70px rgba(16,24,40,.08);
        padding: clamp(14px, 3.5vh, 42px) 28px clamp(14px, 3vh, 34px);
        backdrop-filter: blur(10px);
    }

    .tw-loader-media {
        width: min(320px, 82vw, 34vh);
        max-height: 25vh;
        height: auto;
        object-fit: contain;
        display: block;
        margin: 0 auto 12px;
    }

    .tw-loader-title {
        margin: 0;
        color: #101828;
        font-size: clamp(22px, 3vw, 32px);
        line-height: 1.22;
        letter-spacing: 0;
        font-weight: 900;
    }

    .tw-loader-copy {
        max-width: 560px;
        margin: 12px auto 0;
        color: #667085;
        font-size: 14px;
        line-height: 1.7;
    }

    .tw-progress-wrap {
        margin: 24px auto 0;
        max-width: 560px;
    }

    .tw-progress-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        color: #667085;
        font-size: 12px;
        font-weight: 800;
    }

    .tw-status {
        display: inline-flex;
        align-items: center;
        min-width: 0;
        gap: 8px;
    }

    .tw-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        flex: 0 0 auto;
        background: #049a63;
        box-shadow: 0 0 0 0 rgba(4,154,99,.28);
        animation: twPulse 1.25s infinite;
    }

    .tw-progress-track {
        height: 8px;
        margin-top: 10px;
        border-radius: 999px;
        overflow: hidden;
        background: #e9edf5;
    }

    .tw-progress-bar {
        display: block;
        height: 100%;
        width: 6%;
        border-radius: inherit;
        background: linear-gradient(90deg, #39328f, #276f78, #049a63);
        transition: width .45s ease;
    }

    .tw-products {
        margin: 26px auto 0;
        max-width: 650px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .tw-product {
        border: 1px solid #e6e9f0;
        border-radius: 12px;
        background: #fff;
        padding: 13px;
        text-align: left;
    }

    .tw-product-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        background: #f5f7ff;
        color: #39328f;
        font-size: 17px;
    }

    .tw-product-icon img {
        width: 24px;
        height: 24px;
        object-fit: contain;
        display: block;
    }

    .tw-product strong {
        display: block;
        color: #101828;
        font-size: 13px;
        line-height: 1.35;
        font-weight: 900;
    }

    .tw-product span {
        display: block;
        margin-top: 4px;
        color: #667085;
        font-size: 11.5px;
        line-height: 1.45;
    }

    .tw-loader-error {
        display: none;
        margin: 22px auto 0;
        max-width: 520px;
        border: 1px solid #fecaca;
        border-radius: 12px;
        background: #fff7f7;
        color: #b91c1c;
        padding: 12px 14px;
        font-size: 13px;
        line-height: 1.5;
    }

    .tw-loader-error a {
        color: #7f1d1d;
        font-weight: 900;
        text-decoration: underline;
    }

    .tw-runner {
        display: none;
        width: 0;
        height: 0;
        border: 0;
    }

    @keyframes twPulse {
        70% { box-shadow: 0 0 0 10px rgba(4,154,99,0); }
        100% { box-shadow: 0 0 0 0 rgba(4,154,99,0); }
    }

    @media (max-width: 900px) {
        .tw-search-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .tw-summary-item:last-child { grid-column: 1 / -1; }
    }

    @media (max-width: 650px) {
        :root { --tw-loader-nav-height: 104px; }
        .tw-loader-shell { width: min(100% - 20px, 1120px); padding: 10px 0; }
        .tw-search-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); border-radius: 12px; }
        .tw-summary-item { padding: 8px 10px; }
        .tw-summary-item:last-child { grid-column: 1 / -1; }
        .tw-summary-label { font-size: 9px; }
        .tw-summary-value { margin-top: 3px; font-size: 12px; }
        .tw-leg-strip { display: flex; gap: 6px; overflow: hidden; margin-top: 6px; }
        .tw-leg-chip { flex: 1 1 0; padding: 6px 7px; gap: 6px; }
        .tw-leg-no { width: 22px; height: 22px; font-size: 10px; }
        .tw-leg-main { font-size: 10px; }
        .tw-leg-date { font-size: 9px; }
        .tw-loader-card { margin-top: 8px; border-radius: 14px; padding: 12px 12px 10px; }
        .tw-loader-media { width: min(190px, 52vw, 24vh); max-height: 18vh; margin-bottom: 4px; }
        .tw-loader-title { font-size: clamp(17px, 5vw, 22px); }
        .tw-loader-copy { margin-top: 5px; font-size: 11px; line-height: 1.4; }
        .tw-progress-wrap { margin-top: 10px; }
        .tw-products { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; margin-top: 10px; }
        .tw-product { padding: 7px 4px; }
        .tw-product-icon { margin: 0 auto 3px; }
        .tw-product strong { font-size: 10px; }
        .tw-product span { display: none; }
    }

    @media (max-height: 700px) {
        .tw-loader-shell { padding-top: 8px; padding-bottom: 8px; }
        .tw-summary-item { padding-top: 8px; padding-bottom: 8px; }
        .tw-loader-card { margin-top: 8px; padding-top: 10px; padding-bottom: 9px; }
        .tw-loader-media { width: min(220px, 29vh); max-height: 18vh; margin-bottom: 2px; }
        .tw-loader-copy { margin-top: 4px; line-height: 1.4; }
        .tw-progress-wrap, .tw-products { margin-top: 9px; }
    }

    @media (max-height: 560px) {
        .tw-loader-media { width: min(150px, 19vh); max-height: 13vh; }
        .tw-loader-title { font-size: 17px; }
        .tw-loader-copy { font-size: 10px; }
        .tw-product span { display: none; }
    }
</style>

<div class="tw-loader" x-data="travelwheelFlightLoader()" x-init="start()">
    <div class="tw-loader-shell">
        <section class="tw-search-summary" aria-label="Flight search summary">
            <div class="tw-summary-item">
                <span class="tw-summary-label">Trip Type</span>
                <span class="tw-summary-value">{{ $tripLabel }}</span>
            </div>
            <div class="tw-summary-item">
                <span class="tw-summary-label">From</span>
                <span class="tw-summary-value">{{ $trip === 'multi' ? ($firstLeg['from'] ?? 'Origin') : ($from ?: 'Departure') }}</span>
            </div>
            <div class="tw-summary-item">
                <span class="tw-summary-label">To</span>
                <span class="tw-summary-value">{{ $trip === 'multi' ? ($lastLeg['to'] ?? 'Destination') : ($to ?: 'Destination') }}</span>
            </div>
            <div class="tw-summary-item">
                <span class="tw-summary-label">{{ $trip === 'return' ? 'Dates' : ($trip === 'multi' ? 'Travel Dates' : 'Departure') }}</span>
                <span class="tw-summary-value">{{ $dateLabel }}</span>
            </div>
            <div class="tw-summary-item">
                <span class="tw-summary-label">{{ $trip === 'multi' ? 'Legs' : 'Passenger' }}</span>
                <span class="tw-summary-value">{{ $trip === 'multi' ? $legSummary : ($pax . ' Passenger' . ($pax === 1 ? '' : 's') . ', ' . $cabin) }}</span>
            </div>
        </section>

        @if($trip === 'multi' && count($multiLegs))
            <section class="tw-leg-strip" aria-label="Multi-city flight legs">
                @foreach(array_slice($multiLegs, 0, 4) as $index => $leg)
                    <div class="tw-leg-chip">
                        <span class="tw-leg-no">{{ $index + 1 }}</span>
                        <div style="min-width:0;">
                            <div class="tw-leg-main">{{ $leg['from'] ?? 'Origin' }} to {{ $leg['to'] ?? 'Destination' }}</div>
                            <div class="tw-leg-date">{{ $leg['depart'] ?? 'Date pending' }} · {{ ['Y' => 'Economy', 'S' => 'Premium Economy', 'C' => 'Business', 'F' => 'First'][$leg['cabin'] ?? 'Y'] ?? $cabin }}</div>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif

        <section class="tw-loader-card">
            <img class="tw-loader-media" src="{{ asset('assets/img/Flight Loader.svg') }}" alt="Searching flights">
            <h1 class="tw-loader-title">Finding the best flight options for you</h1>
            <p class="tw-loader-copy">We are comparing live airline availability and fare combinations for {{ $routeLabel }}.</p>

            <div class="tw-progress-wrap">
                <div class="tw-progress-meta">
                    <span class="tw-status"><span class="tw-status-dot"></span><span x-text="statusText"></span></span>
                    <span x-text="progress + '%'"></span>
                </div>
                <div class="tw-progress-track"><span class="tw-progress-bar" x-bind:style="'width:' + progress + '%'"></span></div>
            </div>

            <div class="tw-products">
                <div class="tw-product">
                    <span class="tw-product-icon"><img src="{{ asset('assets/Support 70.png') }}" alt=""></span>
                    <div><strong>TravelFlex</strong><span>Split eligible fares into simple instalments.</span></div>
                </div>
                <div class="tw-product">
                    <span class="tw-product-icon"><img src="{{ asset('assets/Hotel 70.png') }}" alt=""></span>
                    <div><strong>Hotels</strong><span>Add a stay after choosing your preferred flight.</span></div>
                </div>
                <div class="tw-product">
                    <span class="tw-product-icon"><img src="{{ asset('assets/Visa 70.png') }}" alt=""></span>
                    <div><strong>Visa support</strong><span>Get guidance for documents and travel support.</span></div>
                </div>
            </div>

            <div class="tw-loader-error" x-ref="errorBox">
                This search is taking longer than expected. <a href="{{ route('air') }}">Start a new search</a> or wait a moment.
            </div>
        </section>
    </div>

    <div x-ref="runnerWrap"></div>
</div>

<script>
function travelwheelFlightLoader() {
    return {
        progress: 6,
        statusText: 'Preparing your search',
        statuses: [
            'Preparing your search',
            'Checking airline availability',
            'Comparing fare combinations',
            'Reviewing baggage and cabin rules',
            'Sorting the best options'
        ],
        start() {
            let interval = setInterval(() => {
                this.progress = Math.min(98, this.progress + (this.progress < 72 ? 7 : 2));
                this.statusText = this.statuses[Math.min(this.statuses.length - 1, Math.floor(this.progress / 22))];
            }, 650);

            setTimeout(() => {
                const frame = document.createElement('iframe');
                frame.className = 'tw-runner';
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
