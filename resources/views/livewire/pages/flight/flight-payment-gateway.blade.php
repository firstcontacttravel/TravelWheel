{{-- resources/views/livewire/pages/flight/flight-payment-gateway.blade.php --}}
@component('layouts.app', ['title' => 'Secure Payment'])

@php
    $currency = $flight['currency'] ?? 'NGN';
    $sym      = match($currency) { 'NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', default => $currency.' ' };
    $fmt      = fn($v) => $sym . number_format((float)$v, 2);

    $fare       = (float) ($flight['price'] ?? 0);
    $extras     = (float) ($extrasTotal ?? 0);
    $total      = $fare + $extras;
    $base       = (float) ($flight['baseFare'] ?? 0);
    // Whatever sits between the base fare and the fare charged is taxes,
    // carrier fees and our service charge. Stating it keeps the summary
    // adding up, the same way the booking page's does.
    $fees       = max(0, $fare - $base);

    $cabinLabel = \App\Support\FlightDisplay::cabin($flight ?? []);
    $segments   = $flight['segments'] ?? [];
    $multiLegs  = $flight['multiLegs'] ?? [];
    $retSegs    = $flight['returnSegments'] ?? [];
    $isMulti    = count($multiLegs) > 0;
    $firstSeg   = $segments[0] ?? [];
    $lastSeg    = !empty($segments) ? $segments[count($segments) - 1] : [];

    // SkyLink reports 12-hour clock times ("07:15 pm"); TravelNext reports
    // 24-hour. Every other page in this funnel normalises, so a departure does
    // not change format between reviewing the booking and paying for it.
    $pgTime = function ($value): string {
        $raw = trim((string) $value);
        if (! preg_match('/^(\d{1,2}):(\d{2})\s*([ap])\.?m\.?$/i', $raw, $m)) { return $raw; }
        $hour = ((int) $m[1] % 12) + (strtolower($m[3]) === 'p' ? 12 : 0);
        return str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':'.$m[2];
    };

    $legs = [];
    if ($isMulti) {
        foreach ($multiLegs as $li => $leg) {
            $ls = $leg['segments'] ?? [];
            $legs[] = [
                'label' => 'Leg '.($li + 1),
                'from'  => $ls[0]['fromCity'] ?? $ls[0]['from'] ?? ($leg['from'] ?? ''),
                'to'    => count($ls) ? ($ls[count($ls)-1]['toCity'] ?? $ls[count($ls)-1]['to'] ?? '') : ($leg['to'] ?? ''),
                'date'  => $leg['departDateLabel'] ?? '',
                'depart' => $pgTime($ls[0]['departTime'] ?? ''),
                'arrive' => count($ls) ? $pgTime($ls[count($ls)-1]['arriveTime'] ?? '') : '',
                'dur'   => $leg['totalTimeLabel'] ?? '',
            ];
        }
    } else {
        $legs[] = [
            'label' => count($retSegs) ? 'Outbound' : '',
            'from'  => $firstSeg['fromCity'] ?? $firstSeg['from'] ?? '',
            'to'    => $lastSeg['toCity'] ?? $lastSeg['to'] ?? '',
            'date'  => $flight['departDateLabel'] ?? '',
            'depart' => $pgTime($firstSeg['departTime'] ?? ''),
            'arrive' => $pgTime($lastSeg['arriveTime'] ?? ''),
            'dur'   => $flight['totalTimeLabel'] ?? '',
        ];
        if (count($retSegs)) {
            $rl = $retSegs[count($retSegs) - 1];
            $legs[] = [
                'label' => 'Return',
                'from'  => $retSegs[0]['fromCity'] ?? $retSegs[0]['from'] ?? '',
                'to'    => $rl['toCity'] ?? $rl['to'] ?? '',
                'date'  => $flight['returnDateLabel'] ?? '',
                'depart' => $pgTime($retSegs[0]['departTime'] ?? ''),
                'arrive' => $pgTime($rl['arriveTime'] ?? ''),
                'dur'   => $flight['returnTotalTimeLabel'] ?? '',
            ];
        }
    }

    $pax      = collect($passengers ?? [])->filter(fn ($p) => ! empty($p['last_name']))->values();
    $paxCount = $pax->count();
    $leadName = $paxCount ? trim(($pax[0]['title'] ?? '').' '.strtoupper($pax[0]['first_name'] ?? '').' '.strtoupper($pax[0]['last_name'] ?? '')) : '';

    // This page is reached two ways: TravelNext WebFare fares (pay first, then
    // book — the only fare type routed here) and every SkyLink fare (SkyLink
    // has no hold concept, so it always pays first regardless of fare type).
    // "Low Cost Carrier (LCC)" is TravelNext's own fare-type jargon — accurate
    // for the first case, meaningless for the second — so the copy describes
    // the actual mechanic instead of a fare-type label that fits only one.
    $isSkylink = ($flight['source'] ?? null) === 'skylink';
@endphp

<link rel="stylesheet" href="{{ asset('css/travelwheel-ui.css') }}">
<style>
    /* The site chrome is two stacked fixed bars (topbar 0-51px, nav 48-128px),
       so the page needs 128px of clearance plus breathing room. Without any
       of this the shell header slid under the nav and the page title was
       unreadable; the 90px other pages use is still short of it. */
    main.navbarmain.upper-space { padding-top: 142px; }
    .tw-ui-page { padding-top: 20px; }
    @media (max-width: 650px) { main.navbarmain.upper-space { padding-top: 96px; } }

    .fpg-back{display:inline-flex;align-items:center;gap:6px;margin-bottom:14px;color:var(--tw-ui-muted);font-size:13px;font-weight:600;text-decoration:none}
    .fpg-back:hover{color:var(--tw-ui-ink)}

    /* One leg of the trip, read as a line rather than boxed like a card. */
    .fpg-leg{display:flex;align-items:flex-start;gap:12px;padding:13px 0}
    .fpg-leg + .fpg-leg{border-top:1px solid var(--tw-ui-line)}
    .fpg-leg-ic{flex-shrink:0;width:30px;height:30px;border-radius:8px;background:#eef0ff;color:var(--tw-ui-brand);display:flex;align-items:center;justify-content:center}
    .fpg-leg-txt{min-width:0;flex:1}
    .fpg-leg-route{display:flex;align-items:center;gap:8px;flex-wrap:wrap;color:var(--tw-ui-ink);font-size:14.5px;font-weight:700;line-height:1.4}
    .fpg-leg-tag{flex-shrink:0;padding:2px 8px;border-radius:6px;background:var(--tw-ui-surface-soft);border:1px solid var(--tw-ui-line);color:var(--tw-ui-muted);font-size:11px;font-weight:600;line-height:1.45}
    .fpg-leg-meta{margin-top:2px;color:var(--tw-ui-muted);font-size:12px;line-height:1.5}
    .fpg-leg-times{display:flex;align-items:baseline;gap:8px;margin-top:6px;font-family:'DM Mono',monospace;font-size:13px;color:var(--tw-ui-ink)}
    .fpg-leg-times .sep{color:var(--tw-ui-subtle)}
    .fpg-leg-dur{font-family:var(--tw-font-sans,'Open Sans',sans-serif);font-size:11.5px;color:var(--tw-ui-muted)}

    .fpg-row{display:flex;align-items:baseline;justify-content:space-between;gap:16px;padding:11px 0;font-size:13px}
    .fpg-row + .fpg-row{border-top:1px solid var(--tw-ui-line)}
    .fpg-row-lbl{flex-shrink:0;color:var(--tw-ui-muted)}
    .fpg-row-val{color:var(--tw-ui-ink);font-weight:600;text-align:right;min-width:0}
    .fpg-divider{height:1px;background:var(--tw-ui-line);margin:2px 0}

    /* The single action on the page. */
    .fpg-pay{padding:4px 0 2px}
    .fpg-pay-copy{margin:0 0 18px;color:var(--tw-ui-muted);font-size:13px;line-height:1.6}
    .fpg-pay-btn{width:100%;flex-direction:column;gap:2px;height:auto;padding:15px}
    .fpg-pay-btn small{display:block;font-size:11px;font-weight:600;opacity:.85}
    .fpg-assure{display:flex;flex-direction:column;gap:8px;margin-top:16px}
    .fpg-assure-item{display:flex;align-items:flex-start;gap:9px;color:var(--tw-ui-muted);font-size:11.5px;line-height:1.5}
    .fpg-assure-item svg{flex-shrink:0;margin-top:1px;color:var(--tw-ui-subtle)}

    .fpg-summary-note{margin-top:12px;color:var(--tw-ui-subtle);font-size:11px;line-height:1.55}
    @media(max-width:800px){.fpg-leg-route{font-size:13.5px}}
</style>

<x-ui.page-shell
    eyebrow="Secure checkout"
    title="Pay for your booking"
    copy="Your ticket is issued automatically the moment payment is confirmed.">

    <a href="{{ route('flights.booking') }}" class="fpg-back">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
        Back to booking
    </a>

    @if(session('error') || $errors->has('error'))
        <x-ui.alert variant="error" role="alert">
            {{ session('error') ?? $errors->first('error') ?? 'Payment error occurred' }}
        </x-ui.alert>
    @endif

    <x-ui.card title="What you are paying for">
        @foreach($legs as $leg)
            <div class="fpg-leg">
                <span class="fpg-leg-ic">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
                </span>
                <span class="fpg-leg-txt">
                    <span class="fpg-leg-route">
                        {{ $leg['from'] }}
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="color:var(--tw-ui-subtle);flex-shrink:0;"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                        {{ $leg['to'] }}
                        @if($leg['label'])<span class="fpg-leg-tag">{{ $leg['label'] }}</span>@endif
                    </span>
                    <span class="fpg-leg-meta">
                        {{ $flight['airline'] ?? '' }}@if(!empty($firstSeg['flightNo'])) {{ $firstSeg['flightNo'] }}@endif
                        @if($leg['date']) &middot; {{ $leg['date'] }} @endif
                        &middot; {{ $cabinLabel }}
                    </span>
                    @if($leg['depart'] || $leg['arrive'])
                        <span class="fpg-leg-times">
                            <span>{{ $leg['depart'] }}</span>
                            <span class="sep">&ndash;</span>
                            <span>{{ $leg['arrive'] }}</span>
                            @if($leg['dur'])<span class="fpg-leg-dur">{{ $leg['dur'] }}</span>@endif
                        </span>
                    @endif
                </span>
            </div>
        @endforeach

        <div class="fpg-divider"></div>

        @if($paxCount)
            <div class="fpg-row">
                <span class="fpg-row-lbl">Travelling</span>
                <span class="fpg-row-val">
                    {{ $leadName }}@if($paxCount > 1) and {{ $paxCount - 1 }} {{ $paxCount === 2 ? 'other' : 'others' }}@endif
                </span>
            </div>
        @endif
        @if(!empty($contact['email']))
            <div class="fpg-row">
                <span class="fpg-row-lbl">Confirmation to</span>
                <span class="fpg-row-val">{{ $contact['email'] }}</span>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Pay">
        <div class="fpg-pay">
            <p class="fpg-pay-copy">
                You will be taken to our payment partner to complete this payment, then brought straight back.
                Your ticket is issued automatically once the payment is confirmed &mdash; there is no separate
                confirmation step to wait for.
            </p>

            <form method="POST" action="{{ route('flights.payment.gateway.process') }}" id="gw-form">
                @csrf
                <x-ui.button type="submit" size="lg" class="fpg-pay-btn" id="gw-btn">
                    <span style="display:flex;align-items:center;gap:8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                        Pay {{ $fmt($total) }}
                    </span>
                    <small>Ticket issued as soon as payment clears</small>
                </x-ui.button>
            </form>

            <div class="fpg-assure">
                <span class="fpg-assure-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Encrypted and processed by a PCI DSS compliant payment provider.
                </span>
                <span class="fpg-assure-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
                    Your card details are never seen or stored by TravelWheel.
                </span>
            </div>
        </div>
    </x-ui.card>

    <x-slot:summary>
        <x-ui.card title="Amount to pay">
            <div class="tw-ui-price">
                @php $breakdown = $flight['fareBreakdown'] ?? []; @endphp
                {{-- TravelNext supplies a real per-passenger-type total, which says
                     more than a base/tax split for a multi-traveller booking.
                     SkyLink carries only a supplier base fare with no per-type tax
                     data, so the same rows there would read far too low against the
                     total below — it gets the base-and-charges split instead, which
                     always reconciles because the charges line is the remainder. --}}
                @if(!empty($breakdown) && !$isSkylink)
                    <p class="tw-ui-price__group-title">Fare breakdown</p>
                    @foreach($breakdown as $fb)
                        @php
                            $ptype = match($fb['passengerType'] ?? 'ADT') { 'ADT' => 'Adult', 'CHD' => 'Child', 'INF' => 'Infant', default => 'Traveller' };
                            $qty   = $fb['qty'] ?? 1;
                        @endphp
                        <div class="tw-ui-price__row">
                            <span class="tw-ui-price__label">{{ $ptype }} × {{ $qty }}</span>
                            <span class="tw-ui-price__amount">{{ $fmt(($fb['totalFare'] ?? 0) * $qty) }}</span>
                        </div>
                    @endforeach
                @elseif($base > 0)
                    <div class="tw-ui-price__row">
                        <span class="tw-ui-price__label">Base fare</span>
                        <span class="tw-ui-price__amount">{{ $fmt($base) }}</span>
                    </div>
                    @if($fees > 0)
                        <div class="tw-ui-price__row">
                            <span class="tw-ui-price__label">Taxes, fees and charges</span>
                            <span class="tw-ui-price__amount">{{ $fmt($fees) }}</span>
                        </div>
                    @endif
                @else
                    <div class="tw-ui-price__row">
                        <span class="tw-ui-price__label">Flight</span>
                        <span class="tw-ui-price__amount">{{ $fmt($fare) }}</span>
                    </div>
                @endif

                @if($extras > 0)
                    <p class="tw-ui-price__group-title">Extras</p>
                    @foreach($selectedExtras['baggage'] ?? [] as $baggage)
                        <div class="tw-ui-price__row">
                            <span class="tw-ui-price__label">{{ $baggage['description'] ?? 'Extra baggage' }} &times; {{ $baggage['quantity'] ?? 1 }}</span>
                            <span class="tw-ui-price__amount">{{ $fmt($baggage['line_total'] ?? 0) }}</span>
                        </div>
                    @endforeach
                    @foreach($selectedExtras['meal'] ?? [] as $meal)
                        <div class="tw-ui-price__row">
                            <span class="tw-ui-price__label">{{ $meal['description'] ?? 'Meal' }}</span>
                            <span class="tw-ui-price__amount">{{ $fmt($meal['unit_price'] ?? 0) }}</span>
                        </div>
                    @endforeach
                @endif

                <div class="tw-ui-price__total">
                    <span>Total</span>
                    <span class="tw-ui-price__total-amount">{{ $fmt($total) }}</span>
                </div>
            </div>

            <p class="fpg-summary-note">
                @if($paxCount > 1)
                    {{ $fmt($total / $paxCount) }} per traveller. This is the final amount &mdash; nothing further is added at the payment step.
                @else
                    This is the final amount &mdash; nothing further is added at the payment step.
                @endif
            </p>
        </x-ui.card>
    </x-slot:summary>

</x-ui.page-shell>

<script>
    document.getElementById('gw-form').addEventListener('submit', function () {
        const btn = document.getElementById('gw-btn');
        btn.disabled = true;
        btn.innerHTML = '<span style="display:flex;align-items:center;gap:8px;">Taking you to payment…</span>';
    });
</script>
@endcomponent
